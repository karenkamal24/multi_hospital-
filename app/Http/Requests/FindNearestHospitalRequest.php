<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindNearestHospitalRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'خط العرض مطلوب',
            'latitude.numeric' => 'خط العرض يجب أن يكون رقماً',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'longitude.required' => 'خط الطول مطلوب',
            'longitude.numeric' => 'خط الطول يجب أن يكون رقماً',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
            'radius.numeric' => 'نصف القطر يجب أن يكون رقماً',
            'radius.min' => 'نصف القطر يجب أن يكون أكبر من أو يساوي 0',
            'radius.max' => 'نصف القطر يجب أن يكون أقل من أو يساوي 100',
        ];
    }
}

