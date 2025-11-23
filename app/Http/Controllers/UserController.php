<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\SosRequest;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponse::forbidden([
                    'ar' => 'غير مصرح لك بالوصول',
                    'en' => 'Unauthorized access',
                ]);
            }

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب بيانات المستخدم بنجاح',
                    'en' => 'User profile retrieved successfully',
                ],
                new UserResource($user)
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب بيانات المستخدم: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving user profile: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get donation history for donor (SOS requests that notifications were sent for)
     *
     * @return JsonResponse
     */
    public function donationHistory(): JsonResponse
    {
        try {
            $user = Auth::user();

            // فقط donner يمكنه رؤية تاريخ التبرعات
            if ($user->user_type !== 'donner') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المتبرع يمكنه رؤية تاريخ التبرعات',
                    'en' => 'Only donors can view their donation history',
                ]);
            }

            // التحقق من وجود موقع وفصيلة دم للمتبرع
            if (!$user->latitude || !$user->longitude || !$user->blood) {
                return ApiResponse::success(
                    [
                        'ar' => 'تم جلب تاريخ التبرعات بنجاح',
                        'en' => 'Donation history retrieved successfully',
                    ],
                    []
                );
            }

            // الحصول على فصائل الدم المتوافقة
            $compatibleBloodTypes = getCompatibleBloodTypes($user->blood);

            if (empty($compatibleBloodTypes)) {
                return ApiResponse::success(
                    [
                        'ar' => 'تم جلب تاريخ التبرعات بنجاح',
                        'en' => 'Donation history retrieved successfully',
                    ],
                    []
                );
            }

            // البحث عن طلبات SOS المفتوحة التي المتبرع يمكنه التبرع لها
            // (فصيلة دم متوافقة + قريب من موقع الطلب)
            $donorLat = $user->latitude;
            $donorLng = $user->longitude;

            $sosRequests = SosRequest::where('status', 'open')
                ->whereIn('blood', $compatibleBloodTypes)
                ->with('user')
                ->selectRaw("
                    sos_requests.*,
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                        cos(radians(sos_requests.longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(sos_requests.latitude))
                    )) AS distance
                ", [$donorLat, $donorLng, $donorLat])
                ->having('distance', '<=', DB::raw('sos_requests.radius_km'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($sosRequest) {
                    return [
                        'id' => $sosRequest->id,
                        'patient_id' => $sosRequest->user_id,
                        'patient_name' => $sosRequest->user->name ?? null,
                        'patient_phone' => $sosRequest->user->phone ?? null,
                        'type' => $sosRequest->type,
                        'blood_type' => $sosRequest->blood,
                        'description' => $sosRequest->description,
                        'latitude' => $sosRequest->latitude,
                        'longitude' => $sosRequest->longitude,
                        'distance_km' => round($sosRequest->distance ?? 0, 2),
                        'status' => $sosRequest->status,
                        'created_at' => $sosRequest->created_at,
                        'updated_at' => $sosRequest->updated_at,
                    ];
                });

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب تاريخ التبرعات بنجاح',
                    'en' => 'Donation history retrieved successfully',
                ],
                $sosRequests->toArray()
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب تاريخ التبرعات: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving donation history: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available SOS cases for donor (new cases that need donation)
     * This shows SOS requests that the donor can help with
     *
     * @return JsonResponse
     */
    public function getAvailableSosCases(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'donner') {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المتبرع يمكنه عرض الحالات المتاحة',
                    'en' => 'Only donors can view available cases',
                ]);
            }

            // التحقق من وجود موقع وفصيلة دم للمتبرع
            if (!$user->latitude || !$user->longitude || !$user->blood) {
                return ApiResponse::success(
                    [
                        'ar' => 'تم جلب الحالات المتاحة بنجاح',
                        'en' => 'Available cases retrieved successfully',
                    ],
                    [
                        'available_cases' => [],
                        'new_cases' => [],
                        'summary' => [
                            'total' => 0,
                            'new_count' => 0,
                        ],
                    ]
                );
            }

            // الحصول على فصائل الدم المتوافقة
            $compatibleBloodTypes = getCompatibleBloodTypes($user->blood);

            if (empty($compatibleBloodTypes)) {
                return ApiResponse::success(
                    [
                        'ar' => 'تم جلب الحالات المتاحة بنجاح',
                        'en' => 'Available cases retrieved successfully',
                    ],
                    [
                        'available_cases' => [],
                        'new_cases' => [],
                        'summary' => [
                            'total' => 0,
                            'new_count' => 0,
                        ],
                    ]
                );
            }

            $donorLat = $user->latitude;
            $donorLng = $user->longitude;

            // البحث عن طلبات SOS المفتوحة
            $sosRequests = SosRequest::where('status', 'open')
                ->whereIn('blood', $compatibleBloodTypes)
                ->with('user')
                ->selectRaw("
                    sos_requests.*,
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                        cos(radians(sos_requests.longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(sos_requests.latitude))
                    )) AS distance
                ", [$donorLat, $donorLng, $donorLat])
                ->having('distance', '<=', DB::raw('sos_requests.radius_km'))
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($sosRequest) {
                    return [
                        'id' => $sosRequest->id,
                        'patient' => [
                            'id' => $sosRequest->user_id,
                            'name' => $sosRequest->user->name ?? null,
                            'phone' => $sosRequest->user->phone ?? null,
                        ],
                        'type' => $sosRequest->type,
                        'type_label' => $sosRequest->type === 'blood' ? 'دم' : 'أعضاء',
                        'blood_type' => $sosRequest->blood,
                        'description' => $sosRequest->description,
                        'latitude' => $sosRequest->latitude,
                        'longitude' => $sosRequest->longitude,
                        'distance_km' => round($sosRequest->distance ?? 0, 2),
                        'status' => $sosRequest->status,
                        'is_new' => $sosRequest->created_at->diffInHours(now()) < 24, // جديد إذا تم إنشاؤه خلال 24 ساعة
                        'created_at' => $sosRequest->created_at,
                        'updated_at' => $sosRequest->updated_at,
                    ];
                });

            // فصل الحالات الجديدة
            $newCases = $sosRequests->where('is_new', true)->values();

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب الحالات المتاحة بنجاح',
                    'en' => 'Available cases retrieved successfully',
                ],
                [
                    'available_cases' => $sosRequests->toArray(),
                    'new_cases' => $newCases->toArray(),
                    'summary' => [
                        'total' => $sosRequests->count(),
                        'new_count' => $newCases->count(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الحالات المتاحة: ' . $e->getMessage(),
                'en' => 'An error occurred while retrieving available cases: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get new cases count for notifications badge
     * Returns count of new SOS cases for donor or new hospital requests for patient
     *
     * @return JsonResponse
     */
    public function getNewCasesCount(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden([
                    'ar' => 'فقط المريض أو المتبرع يمكنه عرض عدد الحالات الجديدة',
                    'en' => 'Only patients or donors can view new cases count',
                ]);
            }

            $count = 0;

            if ($user->user_type === 'donner') {
                // للمتبرع: عدد SOS requests الجديدة
                if ($user->latitude && $user->longitude && $user->blood) {
                    $compatibleBloodTypes = getCompatibleBloodTypes($user->blood);

                    if (!empty($compatibleBloodTypes)) {
                        $donorLat = $user->latitude;
                        $donorLng = $user->longitude;

                        $count = SosRequest::where('status', 'open')
                            ->whereIn('blood', $compatibleBloodTypes)
                            ->where('created_at', '>=', now()->subHours(24))
                            ->selectRaw("
                                sos_requests.*,
                                (6371 * acos(
                                    cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                                    cos(radians(sos_requests.longitude) - radians(?)) +
                                    sin(radians(?)) * sin(radians(sos_requests.latitude))
                                )) AS distance
                            ", [$donorLat, $donorLng, $donorLat])
                            ->having('distance', '<=', DB::raw('sos_requests.radius_km'))
                            ->count();
                    }
                }
            } else {
                // للمريض: عدد hospital requests الجديدة (تم الموافقة عليها)
                $count = \App\Models\HospitalRequest::where('user_id', $user->id)
                    ->where('request_type', 'patient')
                    ->where('status', 'approved')
                    ->where('updated_at', '>=', now()->subHours(24))
                    ->count();
            }

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب عدد الحالات الجديدة',
                    'en' => 'New cases count retrieved',
                ],
                [
                    'new_cases_count' => $count,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب عدد الحالات: ' . $e->getMessage(),
                'en' => 'Error retrieving new cases count: ' . $e->getMessage(),
            ]);
        }
    }
}
