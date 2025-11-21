<?php

namespace App\Helpers;

class MessagesHelper
{
    /**
     * Get messages based on locale
     */
    public static function get(string $key, string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        $messages = self::messages();

        return $messages[$locale][$key] ?? $messages['en'][$key] ?? $key;
    }

    /**
     * All messages in both languages
     */
    protected static function messages(): array
    {
        return [
            'en' => [
       
                'validation.failed' => 'Validation failed',
                'validation.required' => 'The :attribute field is required',
                'validation.email' => 'The :attribute must be a valid email address',
                'validation.unique' => 'The :attribute has already been taken',
                'validation.max' => 'The :attribute may not be greater than :max characters',
                'validation.in' => 'The selected :attribute is invalid',
                'validation.confirmed' => 'The :attribute confirmation does not match',
                'validation.string' => 'The :attribute must be a string',
                'validation.nullable' => 'The :attribute field is optional',

                // Register messages
                'register.success' => 'Registration successful',
                'register.failed' => 'An error occurred during registration',
                'register.user_type_invalid' => 'User type must be patient or donner only',
                'register.email_exists' => 'Email already exists',

                // Login messages
                'login.success' => 'Login successful',
                'login.failed' => 'Login failed',
                'login.invalid_credentials' => 'Invalid email or password',

                // User attributes
                'user.name' => 'Name',
                'user.email' => 'Email',
                'user.phone' => 'Phone',
                'user.gender' => 'Gender',
                'user.user_type' => 'User Type',
                'user.blood' => 'Blood Type',
                'user.password' => 'Password',
                'user.password_confirmation' => 'Password Confirmation',
            ],
            'ar' => [
                // Validation messages
                'validation.failed' => 'فشل التحقق من البيانات',
                'validation.required' => 'هذا الحقل مطلوب',
                'validation.email' => 'يجب أن يكون بريد إلكتروني صحيح',
                'validation.unique' => 'هذه القيمة مستخدمة بالفعل',
                'validation.max' => 'يجب ألا يتجاوز :max حرف',
                'validation.in' => 'القيمة المحددة غير صالحة',
                'validation.confirmed' => 'تأكيد كلمة المرور غير متطابق',
                'validation.string' => 'يجب أن يكون نص',
                'validation.nullable' => 'هذا الحقل اختياري',

                // Register messages
                'register.success' => 'تم التسجيل بنجاح',
                'register.failed' => 'حدث خطأ أثناء التسجيل',
                'register.user_type_invalid' => 'نوع المستخدم يجب أن يكون patient أو donner فقط',
                'register.email_exists' => 'البريد الإلكتروني مستخدم بالفعل',

                // Login messages
                'login.success' => 'تم تسجيل الدخول بنجاح',
                'login.failed' => 'فشل تسجيل الدخول',
                'login.invalid_credentials' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',

                // User attributes
                'user.name' => 'الاسم',
                'user.email' => 'البريد الإلكتروني',
                'user.phone' => 'رقم الهاتف',
                'user.gender' => 'الجنس',
                'user.user_type' => 'نوع المستخدم',
                'user.blood' => 'فصيلة الدم',
                'user.password' => 'كلمة المرور',
                'user.password_confirmation' => 'تأكيد كلمة المرور',
            ],
        ];
    }

    /**
     * Replace placeholders in message
     */
    public static function replace(string $key, array $replace = [], string $locale = null): string
    {
        $message = self::get($key, $locale);

        foreach ($replace as $placeholder => $value) {
            $message = str_replace(':' . $placeholder, $value, $message);
        }

        return $message;
    }
}

