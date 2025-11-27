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
    protected $projectId;
    protected $credentialsPath;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Get Firebase project information
     */
    public function getFirebaseInfo(): array
    {
        $credentialsPath = storage_path(
            'app/firebase/wahb-59a2c-firebase-adminsdk-fbsvc-601c64adce.json'
        );

        $info = [
            'credentials_file_exists' => file_exists($credentialsPath),
            'credentials_path' => $credentialsPath,
            'messaging_initialized' => $this->messaging !== null,
            'project_id' => $this->projectId ?? null,
        ];

        if (file_exists($credentialsPath)) {
            try {
                $jsonContent = file_get_contents($credentialsPath);
                $jsonData = json_decode($jsonContent, true);
                $info['project_id'] = $jsonData['project_id'] ?? null;
                $info['client_email'] = $jsonData['client_email'] ?? null;
            } catch (\Exception $e) {
                $info['json_error'] = $e->getMessage();
            }
        }

        return $info;
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

            if (!file_exists($credentialsPath)) {
                Log::error('Firebase JSON file not found!', [
                    'expected_path' => $credentialsPath,
                    'storage_path' => storage_path('app/firebase'),
                    'directory_exists' => is_dir(storage_path('app/firebase')),
                ]);
                return;
            }

            // Validate JSON file
            $jsonContent = file_get_contents($credentialsPath);
            $jsonData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Firebase JSON file is invalid!', [
                    'path' => $credentialsPath,
                    'json_error' => json_last_error_msg(),
                ]);
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
            $this->projectId = $jsonData['project_id'] ?? null;
            $this->credentialsPath = $credentialsPath;

            Log::info('Firebase initialized successfully using direct JSON path', [
                'path' => $credentialsPath,
                'project_id' => $this->projectId ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Validate FCM token by attempting to send a test message
     * Note: This will actually try to send, but catches errors to validate
     */
    public function validateToken(string $fcmToken): array
    {
        if (empty($fcmToken)) {
            return [
                'valid' => false,
                'error' => 'Token is empty',
            ];
        }

        if (!$this->messaging) {
            return [
                'valid' => false,
                'error' => 'Firebase messaging not initialized',
            ];
        }

        try {
            // Try to send a minimal test message to validate token
            // We use data-only message to avoid showing notification during validation
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withData(['_test' => '1']);

            $this->messaging->send($message);

            return [
                'valid' => true,
                'error' => null,
            ];
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            return [
                'valid' => false,
                'error' => 'Token not found or expired. The token may be invalid, expired, or from a different app.',
                'details' => $e->getMessage(),
            ];
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            return [
                'valid' => false,
                'error' => 'Invalid token format',
                'details' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send FCM push notification to a single user using fcm_token
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($fcmToken)) {
            Log::warning('FCM token is empty');
            throw new \Exception('FCM token is empty');
        }

        if (!$this->messaging) {
            $error = 'Firebase messaging not initialized. Check if credentials file exists.';
            Log::error($error);
            throw new \Exception($error);
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $result = $this->messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'message_id' => $result,
                'token_preview' => substr($fcmToken, 0, 25) . '...',
            ]);

            return true;

        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            $error = 'Invalid FCM token or argument: ' . $e->getMessage() .
                     '. Please check if the token format is correct and matches your Firebase project.';
            Log::error('FCM invalid argument', [
                'message' => $e->getMessage(),
                'fcm_token' => substr($fcmToken, 0, 25) . '...',
            ]);
            throw new \Exception($error, 0, $e);
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            $projectInfo = $this->projectId ? " (Firebase Project: {$this->projectId})" : '';
            $error = 'FCM token not found or invalid: ' . $e->getMessage() .
                     '. The token may be expired, invalid, or from a different Firebase project/app.' . $projectInfo .
                     ' Please ensure: 1) The token is from the same Firebase project, 2) The app package name matches, 3) Generate a new token from your mobile app.';
            Log::error('FCM token not found', [
                'message' => $e->getMessage(),
                'fcm_token' => substr($fcmToken, 0, 25) . '...',
                'project_id' => $this->projectId,
                'hint' => 'Token may be expired or from different app/package/project',
            ]);
            throw new \Exception($error, 0, $e);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            $errorMessage = $e->getMessage();
            $isSenderIdMismatch = str_contains($errorMessage, 'SenderId mismatch') ||
                                  str_contains($errorMessage, 'MismatchSenderId');

            if ($isSenderIdMismatch) {
                $error = 'FCM SenderId mismatch: ' . $errorMessage .
                         '. الـ token تم إنشاؤه من تطبيق/مشروع Firebase مختلف عن المشروع الحالي (' . ($this->projectId ?? 'unknown') . '). ' .
                         'الحل: يجب على المستخدم تسجيل الدخول مرة أخرى من التطبيق للحصول على token جديد من المشروع الصحيح.';
                Log::error('FCM SenderId mismatch', [
                    'message' => $errorMessage,
                    'fcm_token' => substr($fcmToken, 0, 25) . '...',
                    'project_id' => $this->projectId,
                    'hint' => 'Token is from different Firebase project/app - user needs to login again',
                    'solution' => 'User must login again from mobile app to get new token from correct Firebase project',
                ]);
            } else {
                $error = 'FCM notification failed: ' . $errorMessage;
                Log::error('FCM messaging exception', [
                    'message' => $errorMessage,
                    'class' => get_class($e),
                    'fcm_token' => substr($fcmToken, 0, 25) . '...',
                ]);
            }
            throw new \Exception($error, 0, $e);
        } catch (\Exception $e) {
            $error = 'FCM notification failed: ' . $e->getMessage();
            Log::error('FCM notification exception', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
                'fcm_token' => substr($fcmToken, 0, 25) . '...',
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception($error, 0, $e);
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
     * Note: This method will not throw exceptions - it logs errors instead
     * Returns array with notification status for debugging
     */
    public function sendAcceptanceNotification(SosRequest $sosRequest, User $patient, User $donor, Hospital $hospital): array
    {
        $result = [
            'patient_notification' => ['sent' => false, 'error' => null],
            'donor_notification' => ['sent' => false, 'error' => null],
        ];

        // Notify patient
        if ($patient->fcm_token) {
            try {
                Log::info('Sending acceptance notification to patient', [
                    'patient_id' => $patient->id,
                    'sos_request_id' => $sosRequest->id,
                    'token_preview' => substr($patient->fcm_token, 0, 30) . '...',
                ]);

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

                $result['patient_notification']['sent'] = true;
                Log::info('Patient notification sent successfully', [
                    'patient_id' => $patient->id,
                    'sos_request_id' => $sosRequest->id,
                ]);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $isSenderIdMismatch = str_contains($errorMessage, 'SenderId mismatch') ||
                                      str_contains($errorMessage, 'MismatchSenderId');

                if ($isSenderIdMismatch) {
                    // حذف token غير الصالح من قاعدة البيانات
                    try {
                        $patient->update(['fcm_token' => null]);
                        Log::info('Removed invalid FCM token from patient due to SenderId mismatch', [
                            'patient_id' => $patient->id,
                            'patient_name' => $patient->name,
                        ]);
                    } catch (\Exception $updateException) {
                        Log::warning('Failed to remove invalid FCM token from patient', [
                            'patient_id' => $patient->id,
                            'error' => $updateException->getMessage(),
                        ]);
                    }

                    $result['patient_notification']['error'] = 'SenderId mismatch: token المريض من تطبيق/مشروع Firebase مختلف. تم حذف الـ token القديم. يجب على المريض تسجيل الدخول مرة أخرى للحصول على token جديد.';
                    $result['patient_notification']['error_code'] = 'SENDER_ID_MISMATCH';
                    $result['patient_notification']['solution'] = 'تم حذف الـ token القديم. يجب على المريض تسجيل الدخول من التطبيق للحصول على token جديد';
                    $result['patient_notification']['token_removed'] = true;
                } else {
                    $result['patient_notification']['error'] = $errorMessage;
                }

                Log::error('Failed to send acceptance notification to patient', [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->name,
                    'sos_request_id' => $sosRequest->id,
                    'error' => $errorMessage,
                    'error_class' => get_class($e),
                    'is_sender_id_mismatch' => $isSenderIdMismatch,
                ]);
            }
        } else {
            Log::warning('Patient has no FCM token', [
                'patient_id' => $patient->id,
                'sos_request_id' => $sosRequest->id,
            ]);
            $result['patient_notification']['error'] = 'No FCM token';
        }

        // Notify donor
        if ($donor->fcm_token) {
            try {
                Log::info('Sending acceptance notification to donor', [
                    'donor_id' => $donor->id,
                    'sos_request_id' => $sosRequest->id,
                    'token_preview' => substr($donor->fcm_token, 0, 30) . '...',
                ]);

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

                $result['donor_notification']['sent'] = true;
                Log::info('Donor notification sent successfully', [
                    'donor_id' => $donor->id,
                    'sos_request_id' => $sosRequest->id,
                ]);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $isSenderIdMismatch = str_contains($errorMessage, 'SenderId mismatch') ||
                                      str_contains($errorMessage, 'MismatchSenderId');

                if ($isSenderIdMismatch) {
                    // حذف token غير الصالح من قاعدة البيانات
                    try {
                        $donor->update(['fcm_token' => null]);
                        Log::info('Removed invalid FCM token from donor due to SenderId mismatch', [
                            'donor_id' => $donor->id,
                            'donor_name' => $donor->name,
                        ]);
                    } catch (\Exception $updateException) {
                        Log::warning('Failed to remove invalid FCM token from donor', [
                            'donor_id' => $donor->id,
                            'error' => $updateException->getMessage(),
                        ]);
                    }

                    $result['donor_notification']['error'] = 'SenderId mismatch: token المتبرع من تطبيق/مشروع Firebase مختلف. تم حذف الـ token القديم. يجب على المتبرع تسجيل الدخول مرة أخرى.';
                    $result['donor_notification']['error_code'] = 'SENDER_ID_MISMATCH';
                    $result['donor_notification']['solution'] = 'تم حذف الـ token القديم. يجب على المتبرع تسجيل الدخول من التطبيق للحصول على token جديد';
                    $result['donor_notification']['token_removed'] = true;
                } else {
                    $result['donor_notification']['error'] = $errorMessage;
                }

                Log::error('Failed to send acceptance notification to donor', [
                    'donor_id' => $donor->id,
                    'donor_name' => $donor->name,
                    'sos_request_id' => $sosRequest->id,
                    'error' => $errorMessage,
                    'error_class' => get_class($e),
                    'is_sender_id_mismatch' => $isSenderIdMismatch,
                ]);
            }
        } else {
            Log::warning('Donor has no FCM token', [
                'donor_id' => $donor->id,
                'sos_request_id' => $sosRequest->id,
            ]);
            $result['donor_notification']['error'] = 'No FCM token';
        }

        return $result;
    }

    /**
     * Send approval notification to donor when SOS request is approved
     */
    public function sendApprovalNotification(SosRequest $sosRequest, ?Hospital $hospital = null): void
    {
        $donor = $sosRequest->acceptedDonor;

        if (!$donor || !$donor->fcm_token) {
            Log::info('No donor or FCM token for approval notification', [
                'sos_request_id' => $sosRequest->id,
            ]);
            return;
        }

        try {
            $hospital = $hospital ?? $sosRequest->hospital;
            $hospitalName = $hospital ? $hospital->name : 'المستشفى';

            $this->sendToToken(
                $donor->fcm_token,
                'تم الموافقة على طلبك',
                "تم الموافقة على طلب التبرع الخاص بك. المستشفى: {$hospitalName}",
                [
                    'type' => 'sos_approved',
                    'sos_id' => (string) $sosRequest->id,
                    'hospital_id' => $hospital ? (string) $hospital->id : '',
                    'hospital_name' => $hospitalName,
                    'status' => 'completed',
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send approval notification to donor', [
                'donor_id' => $donor->id,
                'sos_request_id' => $sosRequest->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - approval should succeed even if notification fails
        }
    }

    /**
     * Send operation completion notification to patient and donor
     */
    public function sendOperationCompletionNotification(SosRequest $sosRequest): void
    {
        $patient = $sosRequest->user;
        $donor = $sosRequest->acceptedDonor;
        $hospital = $sosRequest->hospital;
        $hospitalName = $hospital ? $hospital->name : 'المستشفى';

        // Notify patient
        if ($patient && $patient->fcm_token) {
            try {
                $this->sendToToken(
                    $patient->fcm_token,
                    'اكتملت عملية التبرع',
                    "تم إكمال عملية التبرع بنجاح. المستشفى: {$hospitalName}",
                    [
                        'type' => 'operation_completed',
                        'sos_id' => (string) $sosRequest->id,
                        'hospital_id' => $hospital ? (string) $hospital->id : '',
                        'hospital_name' => $hospitalName,
                        'status' => 'completed',
                    ]
                );
                Log::info('Operation completion notification sent to patient', [
                    'patient_id' => $patient->id,
                    'sos_request_id' => $sosRequest->id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send operation completion notification to patient', [
                    'patient_id' => $patient->id,
                    'sos_request_id' => $sosRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify donor
        if ($donor && $donor->fcm_token) {
            try {
                $this->sendToToken(
                    $donor->fcm_token,
                    'اكتملت عملية التبرع',
                    "تم إكمال عملية التبرع بنجاح. المستشفى: {$hospitalName}",
                    [
                        'type' => 'operation_completed',
                        'sos_id' => (string) $sosRequest->id,
                        'hospital_id' => $hospital ? (string) $hospital->id : '',
                        'hospital_name' => $hospitalName,
                        'status' => 'completed',
                    ]
                );
                Log::info('Operation completion notification sent to donor', [
                    'donor_id' => $donor->id,
                    'sos_request_id' => $sosRequest->id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to send operation completion notification to donor', [
                    'donor_id' => $donor->id,
                    'sos_request_id' => $sosRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
