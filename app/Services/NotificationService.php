<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Initialize Firebase with service account
     */
    protected function initializeFirebase(): void
    {
        try {
            // المسار الصحيح مباشرة بدون الاعتماد على env أو config
            $credentialsPath = storage_path(
                'app/firebase/wahb-59a2c-firebase-adminsdk-fbsvc-601c64adce.json'
            );

            if (file_exists($credentialsPath)) {

                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();

                Log::info('Firebase initialized successfully using direct JSON path', [
                    'path' => $credentialsPath,
                ]);

                return;
            }

            // إذا الملف مش موجود
            Log::error('Firebase JSON file not found!', [
                'expected_path' => $credentialsPath,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send FCM push notification to a single user using fcm_token
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) {
            Log::warning('FCM token is empty');
            return false;
        }

        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);
            return true;

        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'message' => $e->getMessage(),
                'fcm_token' => substr($fcmToken, 0, 25) . '...',
            ]);
            return false;
        }
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToMultipleTokens(array $fcmTokens, string $title, string $body, array $data = []): array
    {
        $fcmTokens = array_filter($fcmTokens);

        if (empty($fcmTokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return ['success' => 0, 'failure' => count($fcmTokens)];
        }

        $notification = Notification::create($title, $body);

        $success = 0;
        $failure = 0;

        foreach ($fcmTokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);

                $this->messaging->send($message);
                $success++;

            } catch (\Exception $e) {
                $failure++;
                Log::warning('Failed to send to a token', [
                    'token' => substr($token, 0, 25) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => $success,
            'failure' => $failure,
        ];
    }

    /**
     * Send SOS notification
     */
    public function sendSosNotification(User $patient, array $donors, string $type = 'blood', ?string $bloodType = null): array
    {
        $typeLabel = $type === 'organ' ? 'أعضاء' : 'دم';
        $bloodLabel = $bloodType ? " (فصيلة: {$bloodType})" : '';

        $title = "طلب تبرع عاجل - {$typeLabel}";
        $body  = "يوجد مريض يحتاج تبرع {$typeLabel}{$bloodLabel} في منطقتك. الرجاء المساعدة!";

        $tokens = array_column($donors, 'fcm_token');

        $data = [
            'type'   => 'sos_request',
            'sos_type' => $type,
            'patient_id' => (string) $patient->id,
            'patient_name' => $patient->name,
            'blood_type' => $bloodType ?? '',
            'latitude' => $patient->latitude ? (string) $patient->latitude : '',
            'longitude' => $patient->longitude ? (string) $patient->longitude : '',
        ];

        return $this->sendToMultipleTokens($tokens, $title, $body, $data);
    }

    /**
     * Send acceptance notification
     */
    public function sendAcceptanceNotification(SosRequest $sosRequest, User $patient, User $donor, Hospital $hospital): void
    {
        // Notify patient
        if ($patient->fcm_token) {
            $this->sendToToken(
                $patient->fcm_token,
                'تم قبول طلبك',
                "تم قبول طلب التبرع. المستشفى: {$hospital->name}",
                [
                    'type' => 'sos_accepted',
                    'sos_id' => (string) $sosRequest->id,
                    'hospital_id' => (string) $hospital->id,
                    'hospital_name' => $hospital->name,
                ]
            );
        }

        // Notify donor
        if ($donor->fcm_token) {
            $this->sendToToken(
                $donor->fcm_token,
                'تم قبول طلبك بنجاح',
                "تم قبول طلب التبرع. المستشفى: {$hospital->name}",
                [
                    'type' => 'sos_accepted',
                    'sos_id' => (string) $sosRequest->id,
                    'hospital_id' => (string) $hospital->id,
                    'hospital_name' => $hospital->name,
                ]
            );
        }
    }
}
