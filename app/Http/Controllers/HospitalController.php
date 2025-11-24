<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindNearestHospitalRequest;
use App\Http\Resources\HospitalResource;
use App\Services\HospitalService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HospitalController extends Controller
{
    public function __construct(
        private HospitalService $hospitalService
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
}

