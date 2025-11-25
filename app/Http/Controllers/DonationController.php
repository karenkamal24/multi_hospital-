<?php

namespace App\Http\Controllers;

use App\Http\Resources\SosRequestResource;
use App\Services\SosService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    public function __construct(
        private SosService $sosService
    ) {}

    /**
     * Get donation history for donor
     */
    public function history(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'donner') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المتبرع يمكنه عرض سجل التبرعات',
                    'en' => 'Only donors can view donation history',
                ]);
            }

            $status = request()->get('status');
            $donations = $this->sosService->getDonationHistory($user, $status);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب سجل التبرعات بنجاح',
                    'en' => 'Donation history retrieved successfully',
                ],
                $donations,
                SosRequestResource::class
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب السجل: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving history: ' . $e->getMessage(),
            ]);
        }
    }
}

