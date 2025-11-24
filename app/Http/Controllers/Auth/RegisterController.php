<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle a registration request.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $this->authService->register($data);
            $token = $user->createToken('auth-token')->plainTextToken;

            return ApiResponse::created(
                [
                    'ar' => 'تم التسجيل بنجاح',
                    'en' => 'Registration successful',
                ],
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                ]
            );

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest([
                'ar' => $e->getMessage(),
                'en' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error([
                'ar' => 'حدث خطأ أثناء التسجيل: ' . $e->getMessage(),
                'en' => 'An error occurred during registration: ' . $e->getMessage(),
            ]);
        }
    }
}
