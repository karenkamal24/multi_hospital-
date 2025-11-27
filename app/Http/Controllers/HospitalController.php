<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindNearestHospitalRequest;
use App\Http\Resources\HospitalResource;
use App\Http\Resources\SosRequestResource;
use App\Services\HospitalService;
use App\Services\SosService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HospitalController extends Controller
{
    public function __construct(
        private HospitalService $hospitalService,
        private SosService $sosService
    ) {}

    /**
     * Find nearest hospital
     */
    public function nearest(FindNearestHospitalRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $hospital = $this->hospitalService->findNearest(
                $data['latitude'],
                $data['longitude'],
                $data['radius'] ?? null
            );

            if (!$hospital) {
                return ApiResponse::notFound([
                    'ar' => 'لا يوجد مستشفى قريب',
                    'en' => 'No nearby hospital found',
                ]);
            }

            return ApiResponse::success(
                [
                    'ar' => 'تم العثور على أقرب مستشفى',
                    'en' => 'Nearest hospital found',
                ],
                new HospitalResource((object) $hospital)
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء البحث عن المستشفى: ' . $e->getMessage(),
                'en' => 'An error occurred while searching for hospital: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get SOS requests for hospital
     */
    public function sosRequests(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'hospital') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المستشفى يمكنه عرض طلبات SOS',
                    'en' => 'Only hospitals can view SOS requests',
                ]);
            }

            $hospital = $user->hospital;

            if (!$hospital) {
                return ApiResponse::notFound([
                    'ar' => 'المستشفى غير موجود',
                    'en' => 'Hospital not found',
                ]);
            }

            $status = request()->get('status');
            $sosRequests = $this->sosService->getHospitalSosRequests($hospital, $status);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب طلبات SOS بنجاح',
                    'en' => 'SOS requests retrieved successfully',
                ],
                SosRequestResource::collection($sosRequests),
                $sosRequests
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الطلبات: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving requests: ' . $e->getMessage(),
            ]);
        }
    }
}

