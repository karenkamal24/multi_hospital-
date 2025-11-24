<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle a login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return ApiResponse::success(
                [
                    'ar' => 'تم تسجيل الدخول بنجاح',
                    'en' => 'Login successful',
                ],
                [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest([
                'ar' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
                'en' => 'Invalid email or password',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء تسجيل الدخول: ' . $e->getMessage(),
                'en' => 'An error occurred during login: ' . $e->getMessage(),
            ]);
        }
    }
}
