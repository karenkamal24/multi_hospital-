<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM)
    | يمكن استخدام service account JSON file أو server key
    |
    */

    'server_key' => env('FCM_SERVER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | معرف مشروع Firebase
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', 'season-9ede3'),

    /*
    |--------------------------------------------------------------------------
    | Service Account JSON File Path
    |--------------------------------------------------------------------------
    |
    | مسار ملف service account JSON
    | يمكن تحديده في .env كـ FIREBASE_CREDENTIALS
    | المسار يكون نسبي من storage/app/
    | مثال: firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
    |
    */
    'credentials_path' => env('FIREBASE_CREDENTIALS')
        ? storage_path('app/' . env('FIREBASE_CREDENTIALS'))
        : storage_path('app/firebase/season-9ede3-firebase-adminsdk-fbsvc-08a9f61c5b.json'),

    /*
    |--------------------------------------------------------------------------
    | Service Account (Alternative - from .env)
    |--------------------------------------------------------------------------
    |
    | يمكن استخدام هذه الطريقة بدلاً من ملف JSON
    | لكن الأفضل استخدام ملف JSON
    |
    */
    'service_account' => [
        'type' => env('FIREBASE_TYPE', 'service_account'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID'),
        'private_key' => env('FIREBASE_PRIVATE_KEY'),
        'client_email' => env('FIREBASE_CLIENT_EMAIL'),
        'client_id' => env('FIREBASE_CLIENT_ID'),
        'auth_uri' => env('FIREBASE_AUTH_URI', 'https://accounts.google.com/o/oauth2/auth'),
        'token_uri' => env('FIREBASE_TOKEN_URI', 'https://oauth2.googleapis.com/token'),
        'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_PROVIDER_X509_CERT_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
        'client_x509_cert_url' => env('FIREBASE_CLIENT_X509_CERT_URL'),
        'universe_domain' => env('FIREBASE_UNIVERSE_DOMAIN', 'googleapis.com'),
    ],

];

