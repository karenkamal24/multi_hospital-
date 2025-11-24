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
            // Try to use JSON file first (recommended)
            $credentialsPath = config('firebase.credentials_path');

            if ($credentialsPath && file_exists($credentialsPath)) {
                // Use JSON file
                $factory = (new Factory)->withServiceAccount($credentialsPath);
                $this->messaging = $factory->createMessaging();
                Log::info('Firebase initialized successfully using JSON file', [
                    'path' => $credentialsPath,
                ]);
                return;
            }

            Log::warning('Firebase credentials file not found', [
                'path' => $credentialsPath,
            ]);

            // Fallback to .env configuration
            $serviceAccount = config('firebase.service_account');

            // Check if service account is configured
            if (empty($serviceAccount['project_id']) || empty($serviceAccount['private_key'])) {
                Log::warning('Firebase service account not fully configured. Check credentials_path or .env');
                return;
            }

            // Create service account array from .env
            $serviceAccountArray = [
                'type' => $serviceAccount['type'] ?? 'service_account',
                'project_id' => $serviceAccount['project_id'],
                'private_key_id' => $serviceAccount['private_key_id'] ?? null,
                'private_key' => str_replace('\\n', "\n", $serviceAccount['private_key']),
                'client_email' => $serviceAccount['client_email'],
                'client_id' => $serviceAccount['client_id'] ?? null,
                'auth_uri' => $serviceAccount['auth_uri'] ?? 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => $serviceAccount['auth_provider_x509_cert_url'] ?? null,
                'client_x509_cert_url' => $serviceAccount['client_x509_cert_url'] ?? null,
                'universe_domain' => $serviceAccount['universe_domain'] ?? 'googleapis.com',
            ];

            $factory = (new Factory)->withServiceAccount($serviceAccountArray);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send FCM push notification to a single user using fcm_token
     *
     * @param string $fcmToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
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
                ->withData($data)
                ->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'default',
                    ],
                ])
                ->withApnsConfig([
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ]);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'message' => $e->getMessage(),
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
            ]);

            return false;
        }
    }

    /**
     * Send FCM push notification to multiple users using fcm_token
     *
     * @param array $fcmTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return array
     */
    public function sendToMultipleTokens(array $fcmTokens, string $title, string $body, array $data = []): array
    {
        // فلترة tokens الفارغة
        $fcmTokens = array_filter($fcmTokens);

        if (empty($fcmTokens)) {
            return ['success' => 0, 'failure' => 0];
        }

        if (!$this->messaging) {
            Log::warning('Firebase messaging not initialized');
            return ['success' => 0, 'failure' => count($fcmTokens)];
        }

        $notification = Notification::create($title, $body);
        $totalSuccess = 0;
        $totalFailure = 0;

        try {
            // Send to each token individually (Firebase Admin SDK handles batching)
            foreach ($fcmTokens as $token) {
                try {
                    $message = CloudMessage::withTarget('token', $token)
                        ->withNotification($notification)
                        ->withData($data)
                        ->withAndroidConfig([
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                                'channel_id' => 'default',
                            ],
                        ])
                        ->withApnsConfig([
                            'headers' => [
                                'apns-priority' => '10',
                            ],
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                    'badge' => 1,
                                ],
                            ],
                        ]);

                    $this->messaging->send($message);
                    $totalSuccess++;
                } catch (\Exception $e) {
                    $totalFailure++;
                    Log::warning('Failed to send notification to token', [
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'success' => $totalSuccess,
                'failure' => $totalFailure,
            ];
        } catch (\Exception $e) {
            Log::error('FCM batch notification exception', [
                'message' => $e->getMessage(),
            ]);

            return ['success' => 0, 'failure' => count($fcmTokens)];
        }
    }

    /**
     * Send SOS notification to donors using their fcm_token
     *
     * @param User $patient
     * @param array $donors Array of donor users (with fcm_token)
     * @param string $type
     * @param string|null $bloodType
     * @return array
     */
    public function sendSosNotification(User $patient, array $donors, string $type = 'blood', ?string $bloodType = null): array
    {
        $typeLabel = $type === 'organ' ? 'أعضاء' : 'دم';
        $bloodLabel = $bloodType ? " (فصيلة: {$bloodType})" : '';

        $title = "طلب تبرع عاجل - {$typeLabel}";
        $body = "يوجد مريض يحتاج تبرع {$typeLabel}{$bloodLabel} في منطقتك. الرجاء المساعدة!";

        // استخراج fcm_token من المتبرعين
        $fcmTokens = [];
        foreach ($donors as $donor) {
            if (isset($donor['fcm_token']) && !empty($donor['fcm_token'])) {
                $fcmTokens[] = $donor['fcm_token'];
            }
        }

        if (empty($fcmTokens)) {
            Log::info('No FCM tokens found for donors', [
                'donors_count' => count($donors),
            ]);
            return ['success' => 0, 'failure' => 0];
        }

        $data = [
            'type' => 'sos_request',
            'sos_type' => $type,
            'patient_id' => (string) $patient->id,
            'patient_name' => $patient->name,
            'blood_type' => $bloodType ?? '',
            'latitude' => $patient->latitude ? (string) $patient->latitude : '',
            'longitude' => $patient->longitude ? (string) $patient->longitude : '',
        ];

        return $this->sendToMultipleTokens($fcmTokens, $title, $body, $data);
    }

    /**
     * Send acceptance notification to patient and donor
     */
    public function sendAcceptanceNotification(SosRequest $sosRequest, User $patient, User $donor, Hospital $hospital): void
    {
        // Notification to patient
        if ($patient->fcm_token) {
            $patientTitle = 'تم قبول طلبك';
            $patientBody = "تم قبول طلبك من قبل متبرع. المستشفى: {$hospital->name}";
            $patientData = [
                'type' => 'sos_accepted',
                'sos_id' => (string) $sosRequest->id,
                'hospital_id' => (string) $hospital->id,
                'hospital_name' => $hospital->name,
                'hospital_address' => $hospital->address ?? '',
                'hospital_phone' => $hospital->phone ?? '',
                'donor_name' => $donor->name,
            ];
            $this->sendToToken($patient->fcm_token, $patientTitle, $patientBody, $patientData);
        }

        // Notification to donor
        if ($donor->fcm_token) {
            $donorTitle = 'تم قبول طلبك بنجاح';
            $donorBody = "تم قبول طلبك. المستشفى: {$hospital->name}";
            $donorData = [
                'type' => 'sos_accepted',
                'sos_id' => (string) $sosRequest->id,
                'hospital_id' => (string) $hospital->id,
                'hospital_name' => $hospital->name,
                'hospital_address' => $hospital->address ?? '',
                'hospital_phone' => $hospital->phone ?? '',
                'patient_name' => $patient->name,
                'patient_phone' => $patient->phone ?? '',
            ];
            $this->sendToToken($donor->fcm_token, $donorTitle, $donorBody, $donorData);
        }
    }
}
