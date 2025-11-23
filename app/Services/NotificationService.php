<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Get FCM server key from config (.env file)
     *
     * @return string|null
     */
    protected function getServerKey(): ?string
    {
        return config('firebase.server_key') ?? config('services.fcm.server_key');
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

        $serverKey = $this->getServerKey();

        if (!$serverKey) {
            Log::warning('FCM server key not configured. Add FCM_SERVER_KEY in .env file');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'message' => $e->getMessage(),
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

        $serverKey = $this->getServerKey();

        if (!$serverKey) {
            Log::warning('FCM server key not configured');
            return ['success' => 0, 'failure' => count($fcmTokens)];
        }

        // FCM allows max 1000 tokens per batch
        $chunks = array_chunk($fcmTokens, 1000);
        $totalSuccess = 0;
        $totalFailure = 0;

        try {
            foreach ($chunks as $chunk) {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $chunk,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                    ],
                    'data' => $data,
                    'priority' => 'high',
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $totalSuccess += $result['success'] ?? 0;
                    $totalFailure += $result['failure'] ?? 0;
                } else {
                    Log::error('FCM batch notification failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $totalFailure += count($chunk);
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
}
