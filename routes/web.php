<?php

use Illuminate\Support\Facades\Route;
use App\Services\NotificationService;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/test-fcm', function () {
    $testToken = "dsP9g0onQSKy4L6HnVXbGm:APA91bFM9X68wkow9qMekvxXNhH_x9HxR4Z9_3dz8pyoxYnbPEXIvODxxToLzzGaT2qLbi1FCJY2BVms--G4aNd5bGKBQiupDKCXHP4d1i-A-cG7ICDAbkU";

    try {
        $service = new \App\Services\NotificationService();
        $firebaseInfo = $service->getFirebaseInfo();

        // Try to send directly (skip validation)
        $result = $service->sendToToken(
            $testToken,
            "🔥 اختبار FCM",
            "نجح الإرسال عبر NotificationService!"
        );

        return [
            "success" => true,
            "message" => "Notification sent successfully",
            "firebase_info" => $firebaseInfo,
        ];

    } catch (\Exception $e) {
        $service = new \App\Services\NotificationService();
        $firebaseInfo = $service->getFirebaseInfo();

        $errorMessage = $e->getMessage();
        $isTokenNotFound = str_contains($errorMessage, 'not found') || str_contains($errorMessage, 'Requested entity was not found');

        return [
            "success" => false,
            "error" => $errorMessage,
            "error_class" => get_class($e),
            "firebase_info" => $firebaseInfo,
            "token_preview" => substr($testToken, 0, 30) . "...",
            "diagnosis" => $isTokenNotFound ? [
                "issue" => "Token is invalid, expired, or from different Firebase project/app",
                "firebase_project" => $firebaseInfo['project_id'] ?? 'unknown',
                "possible_causes" => [
                    "1. Token expired - FCM tokens can expire when app is uninstalled/reinstalled",
                    "2. Token from different app - Package name in Firebase Console doesn't match app",
                    "3. Token from different Firebase project - App is using different Firebase project",
                    "4. Token format invalid - Token was corrupted or incorrectly stored"
                ],
                "solutions" => [
                    "1. Open the mobile app and login again to generate a new token",
                    "2. Check Firebase Console -> Project Settings -> Your apps -> Package name matches",
                    "3. Verify the app is using Firebase project: " . ($firebaseInfo['project_id'] ?? 'unknown'),
                    "4. Use /users-with-tokens to find users with valid tokens",
                    "5. Use /test-fcm-user/{userId} to test with a real user token from database"
                ]
            ] : [
                "issue" => "Other error occurred",
                "details" => $errorMessage
            ],
        ];
    }
});

// Test FCM with user ID from database
Route::get('/test-fcm-user/{userId}', function ($userId) {
    try {
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return [
                "success" => false,
                "error" => "User not found",
                "user_id" => $userId,
            ];
        }

        if (empty($user->fcm_token)) {
            return [
                "success" => false,
                "error" => "User has no FCM token",
                "user_id" => $userId,
                "user_name" => $user->name,
                "hint" => "The user needs to login from the mobile app to generate an FCM token",
            ];
        }

        $service = new \App\Services\NotificationService();
        $firebaseInfo = $service->getFirebaseInfo();

        // Validate token first
        $validation = $service->validateToken($user->fcm_token);

        if (!$validation['valid']) {
            return [
                "success" => false,
                "error" => "Token validation failed",
                "validation_error" => $validation['error'],
                "validation_details" => $validation['details'] ?? null,
                "user_id" => $userId,
                "user_name" => $user->name,
                "firebase_info" => $firebaseInfo,
                "token_preview" => substr($user->fcm_token, 0, 30) . "...",
                "hint" => "The user's FCM token is invalid or expired. They need to login again from the mobile app.",
            ];
        }

        // Send notification
        $result = $service->sendToToken(
            $user->fcm_token,
            "🔥 اختبار FCM",
            "تم إرسال الإشعار بنجاح للمستخدم: {$user->name}"
        );

        return [
            "success" => true,
            "message" => "Notification sent successfully",
            "user_id" => $userId,
            "user_name" => $user->name,
            "token_valid" => true,
            "firebase_info" => $firebaseInfo,
        ];

    } catch (\Exception $e) {
        $service = new \App\Services\NotificationService();
        $firebaseInfo = $service->getFirebaseInfo();

        return [
            "success" => false,
            "error" => $e->getMessage(),
            "error_class" => get_class($e),
            "user_id" => $userId,
            "firebase_info" => $firebaseInfo,
        ];
    }
});

// Get Firebase configuration info
Route::get('/firebase-info', function () {
    try {
        $service = new \App\Services\NotificationService();
        $info = $service->getFirebaseInfo();

        return [
            "success" => true,
            "firebase_info" => $info,
        ];
    } catch (\Exception $e) {
        return [
            "success" => false,
            "error" => $e->getMessage(),
        ];
    }
});

// List users with FCM tokens
Route::get('/users-with-tokens', function () {
    try {
        $users = \App\Models\User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->select('id', 'name', 'email', 'user_type', 'fcm_token', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'token_preview' => substr($user->fcm_token, 0, 30) . '...',
                    'token_length' => strlen($user->fcm_token),
                    'last_updated' => $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : null,
                    'test_url' => url("/test-fcm-user/{$user->id}"),
                ];
            });

        $service = new \App\Services\NotificationService();
        $firebaseInfo = $service->getFirebaseInfo();

        return [
            "success" => true,
            "count" => $users->count(),
            "users" => $users,
            "firebase_info" => $firebaseInfo,
            "hint" => "Use /test-fcm-user/{id} to test a specific user's token. " .
                     "Newer tokens (recently updated) are more likely to be valid.",
        ];
    } catch (\Exception $e) {
        return [
            "success" => false,
            "error" => $e->getMessage(),
        ];
    }
});
