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
            $user = $this->authService->register($request->validated());

            return ApiResponse::created(
                msg('register.success'),
                new UserResource($user)
            );

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest(
                msg('register.user_type_invalid')
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                msg('register.failed')
            );
        }
    }
}
