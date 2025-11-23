<?php

namespace App\Http\Controllers;

use App\Http\Requests\SosRequest as SosRequestValidation;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\SosRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SosController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Create a new SOS request and notify nearby donors
     *
     * @param SosRequestValidation $request
     * @return JsonResponse
     */
    public function store(SosRequestValidation $request): JsonResponse
    {
        try {
            $user = Auth::user();


            if ($user->user_type !== 'patient') {
                return ApiResponse::forbidden('فقط المريض يمكنه عمل طلب SOS');
            }

            $data = $request->validated();


            $radiusKm = (float) Setting::get('sos_radius_km', 10);


            $bloodType = $data['blood'] ?? $user->blood;

            if (!$bloodType) {
                return ApiResponse::badRequest('يجب تحديد فصيلة الدم');
            }


            $sos = SosRequest::create([
                'user_id' => $user->id,
                'type' => $data['type'],
                'blood' => $bloodType,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius_km' => $radiusKm,
                'status' => 'open',
                'description' => $data['description'] ?? null,
            ]);


            $user->update([
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);


            $compatibleBloodTypes = getCompatibleBloodTypes($bloodType);

            if (empty($compatibleBloodTypes)) {
                return ApiResponse::badRequest('فصيلة الدم غير صحيحة');
            }


            $lat = $data['latitude'];
            $lng = $data['longitude'];

            $donors = User::where('user_type', 'donner')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('fcm_token')
                ->whereIn('blood', $compatibleBloodTypes)
                ->selectRaw("
                    users.*,
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )) AS distance
                ", [$lat, $lng, $lat])
                ->having('distance', '<=', $radiusKm)
                ->orderBy('distance')
                ->get()
                ->toArray();


            $notificationResult = ['success' => 0, 'failure' => 0];

            if (!empty($donors)) {
                $notificationResult = $this->notificationService->sendSosNotification(
                    $user,
                    $donors,
                    $data['type'],
                    $bloodType
                );
            }

            $locale = request()->header('Accept-Language', 'ar');
            $locale = strtolower(explode(',', $locale)[0]);
            $locale = in_array($locale, ['ar', 'en']) ? $locale : 'ar';

            return ApiResponse::created(
                [
                    'ar' => msg('sos.success') . ' وإرسال الإشعارات للمتبرعين القريبين',
                    'en' => msg('sos.success', [], 'en') . ' and notifications sent to nearby donors',
                ],
                [
                    'sos_id' => $sos->id,
                    'donors_count' => count($donors),
                    'notifications' => $notificationResult,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => msg('sos.failed') . ': ' . $e->getMessage(),
                'en' => msg('sos.failed', [], 'en') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update user location
     *
     * @param UpdateLocationRequest $request
     * @return JsonResponse
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

         
            $user->update([
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
            ]);

            return ApiResponse::success(
                [
                    'ar' => msg('location.updated'),
                    'en' => msg('location.updated', [], 'en'),
                ],
                [
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => msg('location.error') . ': ' . $e->getMessage(),
                'en' => msg('location.error', [], 'en') . ': ' . $e->getMessage(),
            ]);
        }
    }
}

