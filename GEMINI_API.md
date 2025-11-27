# Gemini AI API Documentation

## الإعداد

أضف المفتاح التالي إلى ملف `.env`:

```env
GEMINI_API_KEY=your_gemini_api_key_here
```

يمكنك الحصول على API key من: https://makersuite.google.com/app/apikey

## Endpoints

جميع الـ endpoints تتطلب مصادقة (`auth:sanctum`).

### 1. البحث (Search)

**POST** `/api/gemini/search`

البحث باستخدام Gemini AI مع prompt مخصص.

**Request Body:**
```json
{
    "prompt": "ما هي أفضل الممارسات لإدارة طلبات التبرع بالدم؟",
    "context": {
        "user_type": "hospital",
        "hospital_id": "1"
    }
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": {
        "ar": "تم الحصول على النتيجة بنجاح",
        "en": "Result retrieved successfully"
    },
    "data": {
        "result": "الإجابة من Gemini..."
    }
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": {
        "ar": "فشل في الحصول على النتيجة",
        "en": "Failed to get result"
    },
    "error": "Error message"
}
```

### 2. تحليل البيانات (Analyze)

**POST** `/api/gemini/analyze`

تحليل البيانات باستخدام Gemini AI.

**Request Body:**
```json
{
    "data_type": "طلبات SOS",
    "data": {
        "total_requests": 50,
        "completed": 30,
        "pending": 15,
        "cancelled": 5
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": {
        "ar": "تم تحليل البيانات بنجاح",
        "en": "Data analyzed successfully"
    },
    "data": {
        "result": "تحليل البيانات..."
    }
}
```

### 3. إنشاء تقرير (Generate Report)

**POST** `/api/gemini/report`

إنشاء تقرير بناءً على البيانات المقدمة.

**Request Body:**
```json
{
    "report_type": "إحصائيات شهرية",
    "data": {
        "month": "يناير 2024",
        "total_requests": 100,
        "completed": 80,
        "hospitals": 5
    }
}
```

**Response:**
```json
{
    "success": true,
    "message": {
        "ar": "تم إنشاء التقرير بنجاح",
        "en": "Report generated successfully"
    },
    "data": {
        "result": "التقرير..."
    }
}
```

## أمثلة الاستخدام

### مثال 1: البحث البسيط

```bash
curl -X POST http://your-domain.com/api/gemini/search \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "كيف يمكن تحسين نظام إدارة طلبات التبرع؟"
  }'
```

### مثال 2: تحليل البيانات

```bash
curl -X POST http://your-domain.com/api/gemini/analyze \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "data_type": "إحصائيات المستشفى",
    "data": {
      "hospital_name": "مستشفى النور",
      "total_requests": 25,
      "success_rate": 85
    }
  }'
```

### مثال 3: إنشاء تقرير

```bash
curl -X POST http://your-domain.com/api/gemini/report \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "report_type": "تقرير سنوي",
    "data": {
      "year": 2024,
      "total_donations": 500,
      "hospitals": 10
    }
  }'
```

## ملاحظات

1. جميع الـ endpoints تتطلب مصادقة باستخدام Sanctum token
2. الـ prompt يجب ألا يتجاوز 2000 حرف
3. الـ context اختياري ويمكن استخدامه لتوفير معلومات إضافية
4. جميع الردود باللغة العربية


