<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:male,female'],
            'user_type' => ['required', 'in:patient,donner'],
            'blood' => ['nullable', 'string', 'max:10'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'fcm_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => msg('validation.required', ['attribute' => msg('user.name')]),
            'name.string' => msg('validation.string', ['attribute' => msg('user.name')]),
            'name.max' => msg('validation.max', ['attribute' => msg('user.name'), 'max' => '255']),
            'email.required' => msg('validation.required', ['attribute' => msg('user.email')]),
            'email.email' => msg('validation.email', ['attribute' => msg('user.email')]),
            'email.unique' => msg('register.email_exists'),
            'phone.string' => msg('validation.string', ['attribute' => msg('user.phone')]),
            'phone.max' => msg('validation.max', ['attribute' => msg('user.phone'), 'max' => '20']),
            'gender.in' => msg('validation.in', ['attribute' => msg('user.gender')]),
            'user_type.required' => msg('validation.required', ['attribute' => msg('user.user_type')]),
            'user_type.in' => msg('register.user_type_invalid'),
            'blood.string' => msg('validation.string', ['attribute' => msg('user.blood')]),
            'blood.max' => msg('validation.max', ['attribute' => msg('user.blood'), 'max' => '10']),
            'password.required' => msg('validation.required', ['attribute' => msg('user.password')]),
            'password.confirmed' => msg('validation.confirmed', ['attribute' => msg('user.password')]),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => msg('user.name'),
            'email' => msg('user.email'),
            'phone' => msg('user.phone'),
            'gender' => msg('user.gender'),
            'user_type' => msg('user.user_type'),
            'blood' => msg('user.blood'),
            'password' => msg('user.password'),
            'password_confirmation' => msg('user.password_confirmation'),
        ];
    }
}
