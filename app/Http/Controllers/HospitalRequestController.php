<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveHospitalRequestRequest;
use App\Http\Requests\HospitalRequestRequest;
use App\Models\Hospital;
use App\Models\HospitalRequest;
use App\Models\SosRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HospitalRequestController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send hospital request from patient or donor
     * Finds nearest hospital and sends request
     *
     * @param HospitalRequestRequest $request
     * @return JsonResponse
     */
    public function store(HospitalRequestRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // التحقق من نوع المستخدم
            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه إرسال طلب للمستشفى');
            }

            $data = $request->validated();
            $requestType = $user->user_type === 'patient' ? 'patient' : 'donner';

            // التحقق من وجود المستشفى
            $hospital = Hospital::find($data['hospital_id']);
            if (!$hospital) {
                return ApiResponse::notFound('المستشفى غير موجود');
            }

            // التحقق من وجود SOS request إذا تم إرساله
            $sosRequest = null;
            if (isset($data['sos_request_id'])) {
                $sosRequest = SosRequest::find($data['sos_request_id']);
                if (!$sosRequest) {
                    return ApiResponse::notFound('طلب SOS غير موجود');
                }
            }

            // التحقق من عدم وجود طلب مسبق للمستشفى نفسه
            $existingRequest = HospitalRequest::where('hospital_id', $hospital->id)
                ->where('user_id', $user->id)
                ->where('request_type', $requestType)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return ApiResponse::badRequest('يوجد طلب معلق مسبقاً لهذا المستشفى');
            }

            // إنشاء الطلب
            $hospitalRequest = HospitalRequest::create([
                'hospital_id' => $hospital->id,
                'user_id' => $user->id,
                'sos_request_id' => $data['sos_request_id'] ?? null,
                'request_type' => $requestType,
                'status' => 'pending',
                'user_notes' => $data['user_notes'] ?? null,
            ]);

            // إرسال إشعار للمستشفى
            $hospitalUser = $hospital->user;
            if ($hospitalUser && $hospitalUser->fcm_token) {
                $typeLabel = $requestType === 'patient' ? 'مريض' : 'متبرع';
                $title = "طلب جديد من {$typeLabel}";
                $body = "تم استلام طلب جديد من {$user->name} ({$typeLabel})";

                $this->notificationService->sendToToken(
                    $hospitalUser->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'hospital_request',
                        'request_id' => (string) $hospitalRequest->id,
                        'request_type' => $requestType,
                        'user_id' => (string) $user->id,
                        'user_name' => $user->name,
                    ]
                );
            }

            return ApiResponse::created(
                [
                    'ar' => 'تم إرسال الطلب للمستشفى بنجاح',
                    'en' => 'Request sent to hospital successfully',
                ],
                [
                    'request_id' => $hospitalRequest->id,
                    'hospital_id' => $hospital->id,
                    'hospital_name' => $hospital->name,
                    'status' => $hospitalRequest->status,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage(),
                'en' => 'Error sending request: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Find nearest hospital and send request automatically
     * Used when patient/donor wants to send request to nearest hospital
     *
     * @return JsonResponse
     */
    public function sendToNearest(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه إرسال طلب للمستشفى');
            }

            if (!$user->latitude || !$user->longitude) {
                return ApiResponse::badRequest('يجب تحديث الموقع أولاً');
            }

            // إيجاد أقرب مستشفى
            $nearestHospital = Hospital::findNearest($user->latitude, $user->longitude);

            if (!$nearestHospital) {
                return ApiResponse::notFound('لا يوجد مستشفى قريب');
            }

            // البحث عن طلب SOS نشط للمستخدم (إذا كان هناك واحد)
            $sosRequest = null;
            if ($user->user_type === 'patient') {
                // إذا كان المريض، نبحث عن آخر طلب SOS نشط له
                $sosRequest = SosRequest::where('user_id', $user->id)
                    ->where('status', 'open')
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                // إذا كان المتبرع، نبحث عن آخر طلب SOS نشط قريب منه وفصيلة دمه متوافقة
                if ($user->blood) {
                    $compatibleBloodTypes = getCompatibleBloodTypes($user->blood);
                    if (!empty($compatibleBloodTypes)) {
                        $sosRequest = SosRequest::where('status', 'open')
                            ->whereIn('blood', $compatibleBloodTypes)
                            ->selectRaw("
                                sos_requests.*,
                                sos_requests.radius_km,
                                (6371 * acos(
                                    cos(radians(?)) * cos(radians(sos_requests.latitude)) *
                                    cos(radians(sos_requests.longitude) - radians(?)) +
                                    sin(radians(?)) * sin(radians(sos_requests.latitude))
                                )) AS distance
                            ", [$user->latitude, $user->longitude, $user->latitude])
                            ->havingRaw('distance <= radius_km')
                            ->orderBy('created_at', 'desc')
                            ->first();
                    }
                }
            }

            // إنشاء الطلب
            $requestType = $user->user_type === 'patient' ? 'patient' : 'donner';

            $hospitalRequest = HospitalRequest::create([
                'hospital_id' => $nearestHospital->id,
                'user_id' => $user->id,
                'sos_request_id' => $sosRequest?->id, // ربط الطلب بـ SOS request إذا كان موجوداً
                'request_type' => $requestType,
                'status' => 'pending',
            ]);

            // إرسال إشعار للمستشفى
            $hospitalUser = $nearestHospital->user;
            if ($hospitalUser && $hospitalUser->fcm_token) {
                $typeLabel = $requestType === 'patient' ? 'مريض' : 'متبرع';
                $title = "طلب جديد من {$typeLabel}";
                $body = "تم استلام طلب جديد من {$user->name} ({$typeLabel})";

                $this->notificationService->sendToToken(
                    $hospitalUser->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'hospital_request',
                        'request_id' => (string) $hospitalRequest->id,
                        'request_type' => $requestType,
                        'user_id' => (string) $user->id,
                        'user_name' => $user->name,
                    ]
                );
            }

            return ApiResponse::created(
                [
                    'ar' => 'تم إرسال الطلب لأقرب مستشفى بنجاح',
                    'en' => 'Request sent to nearest hospital successfully',
                ],
                [
                    'request_id' => $hospitalRequest->id,
                    'hospital' => [
                        'id' => $nearestHospital->id,
                        'name' => $nearestHospital->name,
                        'address' => $nearestHospital->address,
                    ],
                    'sos_request' => $sosRequest ? [
                        'id' => $sosRequest->id,
                        'type' => $sosRequest->type,
                        'blood' => $sosRequest->blood,
                        'description' => $sosRequest->description,
                    ] : null,
                    'status' => $hospitalRequest->status,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء إرسال الطلب: ' . $e->getMessage(),
                'en' => 'Error sending request: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all requests for a hospital (hospital view)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'hospital') {
                return ApiResponse::forbidden('فقط المستشفى يمكنه عرض الطلبات');
            }

            $hospital = $user->hospital;
            if (!$hospital) {
                return ApiResponse::notFound('المستشفى غير موجود');
            }

            $requests = HospitalRequest::where('hospital_id', $hospital->id)
                ->with(['user', 'sosRequest'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'user' => [
                            'id' => $request->user->id,
                            'name' => $request->user->name,
                            'phone' => $request->user->phone,
                            'blood' => $request->user->blood,
                            'user_type' => $request->user->user_type,
                        ],
                        'request_type' => $request->request_type,
                        'status' => $request->status,
                        'user_notes' => $request->user_notes,
                        'notes' => $request->notes,
                        'sos_request' => $request->sosRequest ? [
                            'id' => $request->sosRequest->id,
                            'type' => $request->sosRequest->type,
                            'blood' => $request->sosRequest->blood,
                            'description' => $request->sosRequest->description,
                        ] : null,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                    ];
                });

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب الطلبات بنجاح',
                    'en' => 'Requests retrieved successfully',
                ],
                $requests->toArray()
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الطلبات: ' . $e->getMessage(),
                'en' => 'Error retrieving requests: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve or reject a hospital request
     *
     * @param int $id
     * @param ApproveHospitalRequestRequest $request
     * @return JsonResponse
     */
    public function approveOrReject(int $id, ApproveHospitalRequestRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user->user_type !== 'hospital') {
                return ApiResponse::forbidden('فقط المستشفى يمكنه الموافقة أو الرفض');
            }

            $hospital = $user->hospital;
            if (!$hospital) {
                return ApiResponse::notFound('المستشفى غير موجود');
            }

            $hospitalRequest = HospitalRequest::where('id', $id)
                ->where('hospital_id', $hospital->id)
                ->first();

            if (!$hospitalRequest) {
                return ApiResponse::notFound('الطلب غير موجود');
            }

            if ($hospitalRequest->status !== 'pending') {
                return ApiResponse::badRequest('الطلب تم معالجته مسبقاً');
            }

            $data = $request->validated();
            $hospitalRequest->update([
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            // إرسال إشعار للمستخدم
            $requestUser = $hospitalRequest->user;
            if ($requestUser && $requestUser->fcm_token) {
                $statusLabel = $data['status'] === 'approved' ? 'موافق عليه' : 'مرفوض';
                $title = "تحديث حالة الطلب";
                $body = "تم {$statusLabel} طلبك للمستشفى {$hospital->name}";

                $this->notificationService->sendToToken(
                    $requestUser->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'hospital_request_update',
                        'request_id' => (string) $hospitalRequest->id,
                        'status' => $data['status'],
                        'hospital_id' => (string) $hospital->id,
                        'hospital_name' => $hospital->name,
                    ]
                );
            }

            return ApiResponse::success(
                [
                    'ar' => $data['status'] === 'approved' ? 'تم الموافقة على الطلب' : 'تم رفض الطلب',
                    'en' => $data['status'] === 'approved' ? 'Request approved' : 'Request rejected',
                ],
                [
                    'request_id' => $hospitalRequest->id,
                    'status' => $hospitalRequest->status,
                    'notes' => $hospitalRequest->notes,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage(),
                'en' => 'Error processing request: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get user's hospital requests (patient/donor view)
     *
     * @return JsonResponse
     */
    public function myRequests(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه عرض طلباته');
            }

            $requests = HospitalRequest::where('user_id', $user->id)
                ->with(['hospital', 'sosRequest'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'hospital' => [
                            'id' => $request->hospital->id,
                            'name' => $request->hospital->name,
                            'address' => $request->hospital->address,
                        ],
                        'request_type' => $request->request_type,
                        'status' => $request->status,
                        'user_notes' => $request->user_notes,
                        'notes' => $request->notes,
                        'sos_request' => $request->sosRequest ? [
                            'id' => $request->sosRequest->id,
                            'type' => $request->sosRequest->type,
                            'blood' => $request->sosRequest->blood,
                        ] : null,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                    ];
                });

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب الطلبات بنجاح',
                    'en' => 'Requests retrieved successfully',
                ],
                $requests->toArray()
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الطلبات: ' . $e->getMessage(),
                'en' => 'Error retrieving requests: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Find nearest hospital to user's location
     *
     * @return JsonResponse
     */
    public function findNearest(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user->latitude || !$user->longitude) {
                return ApiResponse::badRequest('يجب تحديث الموقع أولاً');
            }

            $nearestHospital = Hospital::findNearest($user->latitude, $user->longitude);

            if (!$nearestHospital) {
                return ApiResponse::notFound('لا يوجد مستشفى قريب');
            }

            return ApiResponse::success(
                [
                    'ar' => 'تم العثور على أقرب مستشفى',
                    'en' => 'Nearest hospital found',
                ],
                [
                    'hospital' => [
                        'id' => $nearestHospital->id,
                        'name' => $nearestHospital->name,
                        'address' => $nearestHospital->address,
                        'latitude' => $nearestHospital->latitude,
                        'longitude' => $nearestHospital->longitude,
                    ],
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء البحث عن المستشفى: ' . $e->getMessage(),
                'en' => 'Error finding hospital: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send requests from both patient and donor to the same hospital
     * This is the main scenario: patient and donor both send requests to nearest hospital
     *
     * @param HospitalRequestRequest $request
     * @return JsonResponse
     */
    public function sendBothRequests(HospitalRequestRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            // يجب أن يكون المستخدم مريض أو متبرع
            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه إرسال طلب للمستشفى');
            }

            // إيجاد المستخدم الآخر (المريض أو المتبرع)
            $otherUserType = $user->user_type === 'patient' ? 'donner' : 'patient';

            // إذا كان هناك sos_request_id، نجد المستخدم الآخر من خلاله
            $otherUser = null;
            if (isset($data['sos_request_id'])) {
                $sosRequest = SosRequest::find($data['sos_request_id']);
                if ($sosRequest) {
                    // إذا كان المستخدم الحالي هو المريض، نجد متبرع قريب
                    if ($user->user_type === 'patient') {
                        // نجد متبرع قريب من نفس الموقع
                        $otherUser = User::where('user_type', 'donner')
                            ->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->whereNotNull('fcm_token')
                            ->selectRaw("
                                users.*,
                                (6371 * acos(
                                    cos(radians(?)) * cos(radians(latitude)) *
                                    cos(radians(longitude) - radians(?)) +
                                    sin(radians(?)) * sin(radians(latitude))
                                )) AS distance
                            ", [$user->latitude, $user->longitude, $user->latitude])
                            ->orderBy('distance')
                            ->first();
                    } else {
                        // إذا كان المستخدم الحالي متبرع، نجد المريض من SOS request
                        $otherUser = $sosRequest->user;
                    }
                }
            }

            // التحقق من وجود المستشفى
            $hospital = Hospital::find($data['hospital_id']);
            if (!$hospital) {
                return ApiResponse::notFound('المستشفى غير موجود');
            }

            // إرسال طلب من المستخدم الحالي
            $currentRequest = HospitalRequest::create([
                'hospital_id' => $hospital->id,
                'user_id' => $user->id,
                'sos_request_id' => $data['sos_request_id'] ?? null,
                'request_type' => $user->user_type === 'patient' ? 'patient' : 'donner',
                'status' => 'pending',
                'user_notes' => $data['user_notes'] ?? null,
            ]);

            $requests = [$currentRequest];
            $otherRequest = null;

            // إرسال طلب من المستخدم الآخر إذا وجد
            if ($otherUser) {
                $otherRequest = HospitalRequest::create([
                    'hospital_id' => $hospital->id,
                    'user_id' => $otherUser->id,
                    'sos_request_id' => $data['sos_request_id'] ?? null,
                    'request_type' => $otherUserType,
                    'status' => 'pending',
                ]);
                $requests[] = $otherRequest;
            }

            // إرسال إشعار للمستشفى
            $hospitalUser = $hospital->user;
            if ($hospitalUser && $hospitalUser->fcm_token) {
                $title = "طلبات جديدة من مريض ومتبرع";
                $body = "تم استلام طلبات جديدة من {$user->name}";
                if ($otherUser) {
                    $body .= " و {$otherUser->name}";
                }

                $this->notificationService->sendToToken(
                    $hospitalUser->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'hospital_requests',
                        'request_ids' => array_map(fn($r) => (string) $r->id, $requests),
                        'hospital_id' => (string) $hospital->id,
                    ]
                );
            }

            return ApiResponse::created(
                [
                    'ar' => 'تم إرسال الطلبات للمستشفى بنجاح',
                    'en' => 'Requests sent to hospital successfully',
                ],
                [
                    'requests' => array_map(function ($req) {
                        return [
                            'id' => $req->id,
                            'user_id' => $req->user_id,
                            'request_type' => $req->request_type,
                            'status' => $req->status,
                        ];
                    }, $requests),
                    'hospital' => [
                        'id' => $hospital->id,
                        'name' => $hospital->name,
                    ],
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء إرسال الطلبات: ' . $e->getMessage(),
                'en' => 'Error sending requests: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get available/new cases for patient or donor
     * Shows new hospital requests that were approved or pending
     *
     * @return JsonResponse
     */
    public function getAvailableCases(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه عرض الحالات المتاحة');
            }

            $requestType = $user->user_type === 'patient' ? 'patient' : 'donner';

            // جلب الطلبات الخاصة بالمستخدم
            $requests = HospitalRequest::where('user_id', $user->id)
                ->where('request_type', $requestType)
                ->with(['hospital', 'sosRequest'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'hospital' => [
                            'id' => $request->hospital->id,
                            'name' => $request->hospital->name,
                            'address' => $request->hospital->address,
                            'phone' => $request->hospital->user->phone ?? null,
                        ],
                        'status' => $request->status,
                        'status_label' => $request->status === 'approved' ? 'موافق عليه' :
                                         ($request->status === 'rejected' ? 'مرفوض' : 'في الانتظار'),
                        'user_notes' => $request->user_notes,
                        'hospital_notes' => $request->notes,
                        'sos_request' => $request->sosRequest ? [
                            'id' => $request->sosRequest->id,
                            'type' => $request->sosRequest->type,
                            'blood' => $request->sosRequest->blood,
                            'description' => $request->sosRequest->description,
                        ] : null,
                        'is_new' => $request->status === 'approved' &&
                                   $request->updated_at->diffInHours(now()) < 24, // جديد إذا تم الموافقة خلال 24 ساعة
                        'created_at' => $request->created_at,
                        'updated_at' => $request->updated_at,
                    ];
                });

            // فصل الطلبات حسب الحالة
            $pending = $requests->where('status', 'pending')->values();
            $approved = $requests->where('status', 'approved')->values();
            $rejected = $requests->where('status', 'rejected')->values();
            $newCases = $requests->where('is_new', true)->values();

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب الحالات المتاحة بنجاح',
                    'en' => 'Available cases retrieved successfully',
                ],
                [
                    'all_requests' => $requests->toArray(),
                    'pending' => $pending->toArray(),
                    'approved' => $approved->toArray(),
                    'rejected' => $rejected->toArray(),
                    'new_cases' => $newCases->toArray(),
                    'summary' => [
                        'total' => $requests->count(),
                        'pending_count' => $pending->count(),
                        'approved_count' => $approved->count(),
                        'rejected_count' => $rejected->count(),
                        'new_cases_count' => $newCases->count(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب الحالات: ' . $e->getMessage(),
                'en' => 'Error retrieving cases: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get new/updated cases count (for notifications badge)
     *
     * @return JsonResponse
     */
    public function getNewCasesCount(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!in_array($user->user_type, ['patient', 'donner'])) {
                return ApiResponse::forbidden('فقط المريض أو المتبرع يمكنه عرض عدد الحالات الجديدة');
            }

            $requestType = $user->user_type === 'patient' ? 'patient' : 'donner';

            // عدد الطلبات الجديدة (تم الموافقة عليها خلال آخر 24 ساعة)
            $newCount = HospitalRequest::where('user_id', $user->id)
                ->where('request_type', $requestType)
                ->where('status', 'approved')
                ->where('updated_at', '>=', now()->subHours(24))
                ->count();

            // عدد الطلبات المعلقة
            $pendingCount = HospitalRequest::where('user_id', $user->id)
                ->where('request_type', $requestType)
                ->where('status', 'pending')
                ->count();

            return ApiResponse::success(
                [
                    'ar' => 'تم جلب عدد الحالات الجديدة',
                    'en' => 'New cases count retrieved',
                ],
                [
                    'new_cases_count' => $newCount,
                    'pending_count' => $pendingCount,
                    'total_new' => $newCount + $pendingCount,
                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء جلب عدد الحالات: ' . $e->getMessage(),
                'en' => 'Error retrieving cases count: ' . $e->getMessage(),
            ]);
        }
    }
}

