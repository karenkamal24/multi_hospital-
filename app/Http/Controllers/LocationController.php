<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocationRequest;
use App\Services\LocationService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function __construct(
        private LocationService $locationService
    ) {}

    /**
     * Update user location
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            $user = $this->locationService->updateLocation(
                $user,
                $data['lat'],
                $data['lng']
            );

            return ApiResponse::success(
                [
                    'ar' => 'تم تحديث الموقع بنجاح',
                    'en' => 'Location updated successfully',
                ],
                [
                    'latitude' => $user->latitude,
                    'longitude' => $user->longitude,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء تحديث الموقع: ' . $e->getMessage(),
                'en' => 'An error occurred while updating location: ' . $e->getMessage(),
            ]);
        }
    }
}

