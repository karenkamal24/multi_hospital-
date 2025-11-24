<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class SosService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new SOS request and notify nearby donors
     */
    public function createSosRequest(User $patient, array $data): array
    {
        $bloodType = $data['blood'] ?? $patient->blood;

        if (!$bloodType) {
            throw new \InvalidArgumentException('يجب تحديد فصيلة الدم');
        }

        $radiusKm = (float) \App\Models\Setting::get('sos_radius_km', 10);

        $sos = SosRequest::create([
            'user_id' => $patient->id,
            'type' => $data['type'],
            'blood' => $bloodType,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_km' => $radiusKm,
            'status' => 'active',
            'description' => $data['description'] ?? null,
        ]);

        // Update patient location
        $patient->update([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
        ]);

        // Find compatible donors
        $compatibleBloodTypes = getCompatibleBloodTypes($bloodType);
        $donors = $this->findNearbyDonors($data['latitude'], $data['longitude'], $radiusKm, $compatibleBloodTypes);

        // Send notifications
        $notificationResult = ['success' => 0, 'failure' => 0];
        if (!empty($donors)) {
            $notificationResult = $this->notificationService->sendSosNotification(
                $patient,
                $donors,
                $data['type'],
                $bloodType
            );
        }

        return [
            'sos' => $sos,
            'donors_count' => count($donors),
            'notifications' => $notificationResult,
        ];
    }

    /**
     * Find nearby compatible donors
     */
    protected function findNearbyDonors(float $latitude, float $longitude, float $radiusKm, array $compatibleBloodTypes): array
    {
        if (empty($compatibleBloodTypes)) {
            return [];
        }

        return User::where('user_type', 'donner')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereNotNull('fcm_token')
            ->whereIn('blood', $compatibleBloodTypes)
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) <= ?
            ", [$latitude, $longitude, $latitude, $radiusKm])
            ->selectRaw("
                users.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->orderByRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                ))
            ", [$latitude, $longitude, $latitude])
            ->get()
            ->toArray();
    }

    /**
     * Get available SOS requests for a donor
     */
    public function getAvailableSosRequests(User $donor): array
    {
        if (!$donor->latitude || !$donor->longitude || !$donor->blood) {
            return [];
        }

        $compatibleBloodTypes = getCompatibleBloodTypes($donor->blood);
        if (empty($compatibleBloodTypes)) {
            return [];
        }

        $donorLat = $donor->latitude;
        $donorLng = $donor->longitude;

        return SosRequest::where('status', 'active')
            ->whereIn('blood', $compatibleBloodTypes)
            ->with('user:id,name,phone,blood')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                    cos(radians(sos_requests.longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(sos_requests.latitude))
                )) <= sos_requests.radius_km
            ", [$donorLat, $donorLng, $donorLat])
            ->selectRaw("
                sos_requests.*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                    cos(radians(sos_requests.longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(sos_requests.latitude))
                )) AS distance
            ", [$donorLat, $donorLng, $donorLat])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($sosRequest) {
                return [
                    'id' => $sosRequest->id,
                    'type' => $sosRequest->type,
                    'blood' => $sosRequest->blood,
                    'patient' => [
                        'id' => $sosRequest->user_id,
                        'name' => $sosRequest->user->name ?? null,
                        'phone' => $sosRequest->user->phone ?? null,
                        'blood' => $sosRequest->user->blood ?? null,
                    ],
                    'latitude' => $sosRequest->latitude,
                    'longitude' => $sosRequest->longitude,
                    'description' => $sosRequest->description,
                    'distance' => round($sosRequest->distance ?? 0, 2),
                    'status' => $sosRequest->status,
                    'created_at' => $sosRequest->created_at,
                    'accepted_donors_count' => $sosRequest->accepted_donor_id ? 1 : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get patient's SOS requests
     */
    public function getPatientSosRequests(User $patient, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = SosRequest::where('user_id', $patient->id)
            ->with(['acceptedDonor:id,name,phone,email', 'hospital:id,name,address,latitude,longitude,phone']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(request()->get('per_page', 10));
    }

    /**
     * Accept SOS request by donor
     */
    public function acceptSosRequest(SosRequest $sosRequest, User $donor): array
    {
        if ($sosRequest->status !== 'active') {
            throw new \InvalidArgumentException('الطلب غير متاح للقبول');
        }

        // Find nearest hospital
        $hospital = Hospital::findNearest(
            $sosRequest->latitude,
            $sosRequest->longitude
        );

        if (!$hospital) {
            throw new \InvalidArgumentException('لا يوجد مستشفى قريب');
        }

        // Update SOS request
        $sosRequest->update([
            'accepted_donor_id' => $donor->id,
            'hospital_id' => $hospital->id,
            'status' => 'pending',
        ]);

        // Calculate distance to hospital
        $hospitalDistance = $this->calculateDistance(
            $sosRequest->latitude,
            $sosRequest->longitude,
            $hospital->latitude,
            $hospital->longitude
        );

        // Send notifications to patient and donor
        $patient = $sosRequest->user;
        $this->notificationService->sendAcceptanceNotification($sosRequest, $patient, $donor, $hospital);

        return [
            'sos_request' => $sosRequest->fresh(['acceptedDonor', 'hospital']),
            'hospital' => [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'address' => $hospital->address,
                'latitude' => $hospital->latitude,
                'longitude' => $hospital->longitude,
                'phone' => $hospital->phone,
                'distance' => round($hospitalDistance, 2),
            ],
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ],
            'communication' => [
                'patient_phone' => $patient->phone,
                'patient_email' => $patient->email,
                'donor_phone' => $donor->phone,
                'donor_email' => $donor->email,
            ],
        ];
    }

    /**
     * Get SOS request details
     */
    public function getSosRequestDetails(SosRequest $sosRequest): SosRequest
    {
        return $sosRequest->load([
            'user:id,name,phone,email,blood',
            'acceptedDonor:id,name,phone,email,blood',
            'hospital:id,name,address,latitude,longitude,phone',
        ]);
    }

    /**
     * Get communication info for SOS request
     */
    public function getCommunicationInfo(SosRequest $sosRequest, User $user): array
    {
        $patient = $sosRequest->user;
        $donor = $sosRequest->acceptedDonor;
        $hospital = $sosRequest->hospital;

        if (!$donor || !$hospital) {
            throw new \InvalidArgumentException('الطلب لم يتم قبوله بعد');
        }

        // Verify user is either patient or donor
        if ($user->id !== $patient->id && $user->id !== $donor->id) {
            throw new \InvalidArgumentException('غير مصرح لك بالوصول لهذه المعلومات');
        }

        return [
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ],
            'donor' => [
                'id' => $donor->id,
                'name' => $donor->name,
                'phone' => $donor->phone,
                'email' => $donor->email,
            ],
            'hospital' => [
                'id' => $hospital->id,
                'name' => $hospital->name,
                'phone' => $hospital->phone,
                'address' => $hospital->address,
            ],
        ];
    }

    /**
     * Get donation history for donor
     */
    public function getDonationHistory(User $donor, ?string $status = null): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = SosRequest::where('accepted_donor_id', $donor->id)
            ->with(['user:id,name,blood', 'hospital:id,name']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate(request()->get('per_page', 10));
    }

    /**
     * Calculate distance between two coordinates in kilometers
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

