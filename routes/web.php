<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');use App\Services\NotificationService;

Route::get('/test-fcm', function () {

    $testToken = "eop5koVXS_iOPvvOh84N47:APA91bGdyX0zhIF82ZBaeLsv8NVYIVT9YBOOqnPMVTRq4UABhMidlekNn8_1etmWIWHJs8eh7-EUnZi2O4DXPiZN4vdmCsGDRARQFO60z5J3de1TlKp5AUM";

    try {
        $service = new NotificationService();

        $service->sendToToken(
            $testToken,
            "🔥 اختبار FCM",
            "نجح الإرسال عبر NotificationService!"
        );

        return ["success" => true];

    } catch (\Exception $e) {
        return [
            "success" => false,
            "error" => $e->getMessage()
        ];
    }
});
