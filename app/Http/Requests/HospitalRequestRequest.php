<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HospitalRequestRequest extends FormRequest
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
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
            'sos_request_id' => ['nullable', 'integer', 'exists:sos_requests,id'],
            'user_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hospital_id.required' => 'معرف المستشفى مطلوب',
            'hospital_id.exists' => 'المستشفى المحدد غير موجود',
            'sos_request_id.exists' => 'طلب SOS المحدد غير موجود',
            'user_notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
        ];
    }
}


