<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
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
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'خط العرض (lat) مطلوب',
            'lat.numeric' => 'خط العرض (lat) يجب أن يكون رقماً',
            'lat.between' => 'خط العرض (lat) يجب أن يكون بين -90 و 90',
            'lng.required' => 'خط الطول (lng) مطلوب',
            'lng.numeric' => 'خط الطول (lng) يجب أن يكون رقماً',
            'lng.between' => 'خط الطول (lng) يجب أن يكون بين -180 و 180',
        ];
    }
}
