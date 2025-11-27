<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // استخدام Log لتسجيل الاستجابات

class GeminiService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-2.0-flash');

        // تعديل الـ baseUrl ليتضمن نقطة النهاية (endpoint) مباشرة
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent";
    }

    /**
     * إرسال طلب إلى Gemini API واسترجاع النتيجة
     */
    public function search(string $prompt, array $context = []): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'error' => '❌ لا يوجد مفتاح Gemini. أضف GEMINI_API_KEY في ملف .env'
            ];
        }

        try {
            $promptContent = $this->buildPrompt($prompt, $context);

            $response = Http::timeout(30)->post( // زيادة المهلة إلى 30 ثانية
                $this->baseUrl . '?key=' . $this->apiKey,
                [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $promptContent]
                            ]
                        ]
                    ],
                    // إزالة "responseMimeType" لأنه غير مدعوم في generateContent لـ gemini-2.0-flash
                    // إذا كنت تستخدم نموذج يدعم JSON (مثل gemini-2.5-flash)، يمكنك إضافته هنا
                ]
            );

            $json = $response->json();
            Log::info('Gemini Raw Response:', $json); // تسجيل الاستجابة الخام للمراجعة

            // التحقق من أخطاء HTTP (مثال: مفتاح غير صالح، نموذج غير موجود)
            if ($response->failed()) {
                $errorMessage = $json['error']['message'] ?? 'خطأ غير معروف في واجهة برمجة التطبيقات.';
                return [
                    "success" => false,
                    "error" => "❌ فشل الطلب (HTTP Status: {$response->status()}): {$errorMessage}",
                    "raw" => $json
                ];
            }

            // محاولة استخراج النص
            $text = $this->extractText($json);

            if (!$text) {
                // التحقق من سبب عدم وجود نتيجة (مثل الحجب)
                $finishReason = $json['candidates'][0]['finishReason'] ?? null;

                if ($finishReason === 'SAFETY') {
                     return [
                        "success" => false,
                        "error" => "⚠️ تم حجب النتيجة لأسباب تتعلق بالسلامة.",
                        "raw" => $json
                    ];
                }

                return [
                    "success" => false,
                    "error" => "⚠️ لا توجد نتيجة نصية في رد Gemini.",
                    "raw" => $json
                ];
            }

            return [
                "success" => true,
                "result" => $text
            ];

        } catch (\Throwable $e) {
            return [
                "success" => false,
                "error" => "❌ " . $e->getMessage()
            ];
        }
    }

    /**
     * استخراج النص بغض النظر عن مكانه في الرد
     */
    private function extractText($json)
    {
        // النمط القياسي لـ Gemini API
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        // صيغة الرد التي قد تحتوي على 'text' في مستوى أعمق
        return $this->deepSearch($json);
    }

    /**
     * تفتيش عميق داخل JSON لإيجاد أول حقل 'text'
     */
    private function deepSearch($arr)
    {
        if (!is_array($arr)) return null;

        foreach ($arr as $k => $v) {
            if ($k === "text" && is_string($v)) return $v;

            if (is_array($v)) {
                $found = $this->deepSearch($v);
                if ($found) return $found;
            }
        }
        return null;
    }

    /**
     * بناء البـرومبت (التعليمات والنص)
     */
    private function buildPrompt(string $prompt, array $context = []): string
    {
        $base = "أنت مساعد ذكي متخصص في أنظمة المستشفيات والتبرع بالدم.\n\n";

        if ($context) {
            $base .= "السياق:\n";
            foreach ($context as $k => $v) $base .= "- {$k}: {$v}\n";
            $base .= "\n";
        }

        return $base . "السؤال: {$prompt}\n\nأجب بالعربية بشكل واضح.";
    }

    /**
     * تحليل البيانات باستخدام Gemini
     */
    public function analyzeData(string $type, array $data): array
    {
        return $this->search(
            "قم بتحليل البيانات التالية من نوع {$type}:\n\n" .
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * إنشاء تقرير باستخدام Gemini
     */
    public function generateReport(array $data, string $type = 'عام'): array
    {
        return $this->search(
            "قم بإنشاء تقرير {$type} بناءً على البيانات:\n\n" .
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
