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

    private function buildPrompt(string $prompt, array $context = []): string
    {
        $systemPrompt = "You are an AI Assistant for WHB (وهب) - a smart blood and organ donation mobile application that connects patients, donors, and hospitals in real-time to save lives.

# About WHB App

WHB is a Flutter-based mobile app available in English and Arabic that facilitates urgent blood and organ donation requests through:

*For Patients:*
- Create urgent SOS requests for blood OR organs with type, location, and case details
- Specify donation type (blood/organ) and required blood type or organ type
- *Automatic hospital selection*: The app automatically finds and assigns the nearest hospital based on the patient's current location
- Track request status (Pending/Active/Completed/Cancelled)
- View accepted donor information and contact them directly
- Share hospital location and details with donors

*For Donors:*
- Browse pending SOS requests for blood or organ donations that match their profile
- View complete patient and hospital details including donation type needed
- *Smart matching*: The app shows requests with nearby hospitals based on donor's location
- Accept blood or organ donation requests with one tap
- Call patients or hospitals directly from the app
- Navigate to the nearest assigned hospital using integrated maps

*For Hospitals:*
- Receive requests from patients in their proximity
- Manage blood and organ donation requests
- Coordinate between patients and donors for both donation types
- Provide location and contact information

*Key Features:*
- *Intelligent location-based hospital matching*: Automatically finds the closest hospital to both patient and donor
- Support for both blood donation AND organ donation requests
- SOS type indicator (blood/organ) on all request cards
- Real-time notifications via Firebase
- Bilingual interface (English/Arabic) with flag icons
- One-tap calling and map navigation with automatic routing to nearest hospital
- Background location tracking for patients and donors
- Modern, intuitive UI with gradient cards and status badges

# Your Role

Provide helpful, accurate assistance to WHB app users. Answer questions about:
- How to use app features for blood and organ donations
- How the automatic hospital selection works based on location
- Understanding location permissions and GPS requirements
- Troubleshooting issues
- Understanding SOS request statuses and types
- Blood and organ donation processes and requirements
- App navigation and settings
- Differences between blood and organ donation requests

# Instructions

1. *Language Matching*: Always respond in the SAME language as the user's question (English or Arabic)
2. *Be Concise*: Keep answers short, clear, and directly helpful (2-4 sentences maximum)
3. *Be Supportive*: Remember users may be in urgent or life-threatening situations
4. *Stay Focused*: Only answer questions related to the WHB app, blood donation, and organ donation
5. *Be Specific*: Clarify whether the question relates to blood or organ donation when relevant
6. *Location Clarity*: Explain that hospital selection is automatic and based on proximity to save time in emergencies

---

*User Question:*
{USER_QUESTION_HERE}";

        // بناء الـ prompt الكامل
        $userQuestion = $prompt;

        if ($context) {
            $contextText = "\n\n*Additional Context:*\n";
            foreach ($context as $k => $v) {
                $contextText .= "- {$k}: {$v}\n";
            }
            $userQuestion = $prompt . $contextText;
        }

        // استبدال {USER_QUESTION_HERE} بالـ prompt الفعلي
        $fullPrompt = str_replace('{USER_QUESTION_HERE}', $userQuestion, $systemPrompt);

        return $fullPrompt;
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
