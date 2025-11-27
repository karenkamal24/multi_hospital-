<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptSosRequest;
use App\Http\Requests\SosRequest as SosRequestValidation;
use App\Http\Resources\SosRequestResource;
use App\Models\SosRequest;
use App\Services\SosService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SosController extends Controller
{
    public function __construct(
        private SosService $sosService
    ) {}

    /**
     * Create a new SOS request
     */
    public function store(SosRequestValidation $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'patient') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المريض يمكنه عمل طلب SOS',
                    'en' => 'Only patients can create SOS requests',
                ]);
            }

            $result = $this->sosService->createSosRequest($user, $request->validated());

            return ApiResponse::created(
                [
                    'ar' => 'تم إنشاء طلب SOS بنجاح وإرسال الإشعارات للمتبرعين القريبين',
                    'en' => 'SOS request created successfully and notifications sent to nearby donors',
                ],
                [
                    'sos_id' => $result['sos']->id,
                    'donors_count' => $result['donors_count'],
                    'notifications' => $result['notifications'],
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest([
                'ar' => $e->getMessage(),
                'en' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء إنشاء طلب SOS: ' . $e->getMessage(),
                'en' => 'An error occurred while creating SOS request: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available SOS requests for donor
     */
    public function available(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'donner') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المتبرع يمكنه عرض الطلبات المتاحة',
                    'en' => 'Only donors can view available requests',
                ]);
            }

            $sosRequests = $this->sosService->getAvailableSosRequests($user);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب الطلبات المتاحة بنجاح',
                    'en' => 'Available requests retrieved successfully',
                ],
                $sosRequests
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الطلبات: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving requests: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get patient's SOS requests
     */
    public function myRequests(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'patient') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المريض يمكنه عرض طلباته',
                    'en' => 'Only patients can view their requests',
                ]);
            }

            $status = request()->get('status');
            $sosRequests = $this->sosService->getPatientSosRequests($user, $status);

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

    /**
     * Accept SOS request
     */
    public function accept(AcceptSosRequest $request, SosRequest $sosRequest): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'donner') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المتبرع يمكنه قبول طلب SOS',
                    'en' => 'Only donors can accept SOS requests',
                ]);
            }
            $this->sosService->acceptSosRequest($sosRequest, $user);

            return ApiResponse::success(
                [
                    'ar' => 'تم قبول الطلب',
                    'en' => 'Request accepted successfully',
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest([
                'ar' => $e->getMessage(),
                'en' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء قبول الطلب: ' . $e->getMessage(),
                'en' => 'An error occurred while accepting request: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get SOS request details
     */
    public function show(SosRequest $sosRequest): JsonResponse
    {
        try {
            $sosRequest = $this->sosService->getSosRequestDetails($sosRequest);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب تفاصيل الطلب بنجاح',
                    'en' => 'Request details retrieved successfully',
                ],
                new SosRequestResource($sosRequest)
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب التفاصيل: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving details: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get communication info
     */
    public function communication(SosRequest $sosRequest): JsonResponse
    {
        try {
            $user = Auth::user();
            $communication = $this->sosService->getCommunicationInfo($sosRequest, $user);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب معلومات التواصل بنجاح',
                    'en' => 'Communication info retrieved successfully',
                ],
                $communication
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest([
                'ar' => $e->getMessage(),
                'en' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب معلومات التواصل: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving communication info: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get SOS history for patient
     */
    public function history(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'patient') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المريض يمكنه عرض سجل الطلبات',
                    'en' => 'Only patients can view request history',
                ]);
            }

            $status = request()->get('status');
            $sosRequests = $this->sosService->getPatientSosRequests($user, $status);

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب سجل الطلبات بنجاح',
                    'en' => 'Request history retrieved successfully',
                ],
                SosRequestResource::collection($sosRequests),
                $sosRequests
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب السجل: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving history: ' . $e->getMessage(),
            ]);
        }
    }
}
