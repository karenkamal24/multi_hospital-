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
                msg('login.success'),
                [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::badRequest(
                msg('login.invalid_credentials')
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                msg('login.failed')
            );
        }
    }
}
