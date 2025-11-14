<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user (patient or donner only)
     */
    public function register(array $data): User
    {
        // Ensure only patient or donner can register
        if (!in_array($data['user_type'], ['patient', 'donner'])) {
            throw new \InvalidArgumentException(msg('register.user_type_invalid'));
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
            'user_type' => $data['user_type'],
            'blood' => $data['blood'] ?? null,
            'password' => Hash::make($data['password']),
            'fcm_token' => $data['fcm_token'] ?? null,
        ]);

        return $user;
    }

    /**
     * Login user and return token
     */
    public function login(array $credentials): array
    {
        $password = $credentials['password'];
        $email = $credentials['email'];
        $fcmToken = $credentials['fcm_token'] ?? null;

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            throw new \InvalidArgumentException(msg('login.invalid_credentials'));
        }

        $user = Auth::user();

        // Update FCM token if provided
        if ($fcmToken) {
            $user->update(['fcm_token' => $fcmToken]);
            $user->refresh();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}

