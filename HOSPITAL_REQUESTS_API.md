# Hospital Requests API Documentation

## نظرة عامة
هذا الـ API يتيح للمرضى والمتبرعين إرسال طلبات للمستشفيات، والمستشفيات يمكنها الموافقة أو الرفض على هذه الطلبات.

## السيناريو الرئيسي
1. المريض والمتبرع يجدون أقرب مستشفى بناءً على موقعهم
2. يرسلون طلبين منفصلين للمستشفى (من المريض ومن المتبرع)
3. المستشفى يتأكد من العملية والبيانات ويوافق أو يرفض

## Endpoints

### 1. إيجاد أقرب مستشفى
**GET** `/api/hospital-requests/find-nearest`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": 200,
  "message": "تم العثور على أقرب مستشفى",
  "data": {
    "items": {
      "hospital": {
        "id": 1,
        "name": "مستشفى النور",
        "address": "شارع الملك فهد",
        "latitude": 24.7136,
        "longitude": 46.6753
      }
    }
  }
}
```

### 2. إرسال طلب للمستشفى (من مريض أو متبرع)
**POST** `/api/hospital-requests/`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "hospital_id": 1,
  "sos_request_id": 5,
  "user_notes": "ملاحظات إضافية"
}
```

**Response:**
```json
{
  "status": 201,
  "message": "تم إرسال الطلب للمستشفى بنجاح",
  "data": {
    "items": {
      "request_id": 1,
      "hospital_id": 1,
      "hospital_name": "مستشفى النور",
      "status": "pending"
    }
  }
}
```

### 3. إرسال طلب لأقرب مستشفى تلقائياً
**POST** `/api/hospital-requests/nearest`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": 201,
  "message": "تم إرسال الطلب لأقرب مستشفى بنجاح",
  "data": {
    "items": {
      "request_id": 1,
      "hospital": {
        "id": 1,
        "name": "مستشفى النور",
        "address": "شارع الملك فهد"
      },
      "status": "pending"
    }
  }
}
```

### 4. إرسال طلبين معاً (من المريض والمتبرع)
**POST** `/api/hospital-requests/both`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "hospital_id": 1,
  "sos_request_id": 5,
  "user_notes": "ملاحظات إضافية"
}
```

**ملاحظة:** هذا الـ endpoint يرسل طلبين:
- طلب من المستخدم الحالي (مريض أو متبرع)
- طلب من المستخدم الآخر (إذا كان مرتبطاً بـ SOS request)

**Response:**
```json
{
  "status": 201,
  "message": "تم إرسال الطلبات للمستشفى بنجاح",
  "data": {
    "items": {
      "requests": [
        {
          "id": 1,
          "user_id": 2,
          "request_type": "patient",
          "status": "pending"
        },
        {
          "id": 2,
          "user_id": 3,
          "request_type": "donner",
          "status": "pending"
        }
      ],
      "hospital": {
        "id": 1,
        "name": "مستشفى النور"
      }
    }
  }
}
```

### 5. عرض طلباتي (للمريض/المتبرع)
**GET** `/api/hospital-requests/my-requests`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب الطلبات بنجاح",
  "data": {
    "items": [
      {
        "id": 1,
        "hospital": {
          "id": 1,
          "name": "مستشفى النور",
          "address": "شارع الملك فهد"
        },
        "request_type": "patient",
        "status": "pending",
        "user_notes": "ملاحظات",
        "notes": null,
        "created_at": "2025-11-24T10:00:00.000000Z"
      }
    ]
  }
}
```

### 6. عرض طلبات المستشفى (للمستشفى)
**GET** `/api/hospital-requests/`

**Headers:**
```
Authorization: Bearer {token}
```

**ملاحظة:** يجب أن يكون المستخدم من نوع `hospital`

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب الطلبات بنجاح",
  "data": {
    "items": [
      {
        "id": 1,
        "user": {
          "id": 2,
          "name": "أحمد محمد",
          "phone": "0501234567",
          "blood": "O+",
          "user_type": "patient"
        },
        "request_type": "patient",
        "status": "pending",
        "user_notes": "ملاحظات",
        "notes": null,
        "sos_request": {
          "id": 5,
          "type": "blood",
          "blood": "O+",
          "description": "وصف"
        },
        "created_at": "2025-11-24T10:00:00.000000Z"
      }
    ]
  }
}
```

### 7. الموافقة أو الرفض على طلب (للمستشفى)
**POST** `/api/hospital-requests/{id}/approve`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "status": "approved",
  "notes": "تمت الموافقة بعد التأكد من البيانات"
}
```

**ملاحظة:** `status` يجب أن يكون `approved` أو `rejected`

**Response:**
```json
{
  "status": 200,
  "message": "تم الموافقة على الطلب",
  "data": {
    "items": {
      "request_id": 1,
      "status": "approved",
      "notes": "تمت الموافقة بعد التأكد من البيانات"
    }
  }
}
```

## حالات الطلب (Status)
- `pending`: في انتظار المراجعة
- `approved`: موافق عليه
- `rejected`: مرفوض

## أنواع الطلبات (Request Type)
- `patient`: طلب من مريض
- `donner`: طلب من متبرع

### 8. عرض الحالات المتاحة (للمريض/المتبرع)
**GET** `/api/hospital-requests/available-cases`

**Headers:**
```
Authorization: Bearer {token}
```

**ملاحظة:** يعرض جميع طلبات المستخدم مع فصلها حسب الحالة (pending, approved, rejected, new)

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب الحالات المتاحة بنجاح",
  "data": {
    "items": {
      "all_requests": [...],
      "pending": [...],
      "approved": [...],
      "rejected": [...],
      "new_cases": [...],
      "summary": {
        "total": 5,
        "pending_count": 2,
        "approved_count": 2,
        "rejected_count": 1,
        "new_cases_count": 1
      }
    }
  }
}
```

### 9. عدد الحالات الجديدة (للمريض/المتبرع)
**GET** `/api/hospital-requests/new-count`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب عدد الحالات الجديدة",
  "data": {
    "items": {
      "new_cases_count": 2,
      "pending_count": 1,
      "total_new": 3
    }
  }
}
```

## Endpoints إضافية للحالات المتاحة

### 10. عرض SOS Cases المتاحة (للمتبرع)
**GET** `/api/available-sos-cases`

**Headers:**
```
Authorization: Bearer {token}
```

**ملاحظة:** للمتبرع فقط - يعرض طلبات SOS المتاحة التي يمكنه التبرع لها

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب الحالات المتاحة بنجاح",
  "data": {
    "items": {
      "available_cases": [
        {
          "id": 5,
          "patient": {
            "id": 2,
            "name": "أحمد محمد",
            "phone": "0501234567"
          },
          "type": "blood",
          "type_label": "دم",
          "blood_type": "O+",
          "description": "وصف",
          "distance_km": 5.2,
          "is_new": true,
          "created_at": "2025-11-24T10:00:00.000000Z"
        }
      ],
      "new_cases": [...],
      "summary": {
        "total": 3,
        "new_count": 1
      }
    }
  }
}
```

### 11. عدد الحالات الجديدة (للمريض/المتبرع)
**GET** `/api/new-cases-count`

**Headers:**
```
Authorization: Bearer {token}
```

**ملاحظة:** 
- للمتبرع: يعرض عدد SOS requests الجديدة
- للمريض: يعرض عدد hospital requests التي تم الموافقة عليها حديثاً

**Response:**
```json
{
  "status": 200,
  "message": "تم جلب عدد الحالات الجديدة",
  "data": {
    "items": {
      "new_cases_count": 2
    }
  }
}
```

## ملاحظات مهمة
1. يجب أن يكون المستخدم قد قام بتحديث موقعه (`latitude` و `longitude`) قبل استخدام endpoints التي تعتمد على الموقع
2. المستشفى يجب أن يكون لديه `latitude` و `longitude` في قاعدة البيانات
3. عند الموافقة أو الرفض، سيتم إرسال إشعار للمستخدم (مريض/متبرع)
4. عند إرسال طلب جديد، سيتم إرسال إشعار للمستشفى
5. **الحالات الجديدة**: تعتبر حالة "جديدة" إذا:
   - للمتبرع: SOS request تم إنشاؤه خلال آخر 24 ساعة
   - للمريض: Hospital request تم الموافقة عليه خلال آخر 24 ساعة
6. يمكن استخدام `/api/new-cases-count` لعرض badge في التطبيق يوضح عدد الحالات الجديدة
7. يمكن استخدام `/api/available-sos-cases` للمتبرع لمعرفة الحالات المتاحة للتبرع
8. يمكن استخدام `/api/hospital-requests/available-cases` للمريض/المتبرع لمعرفة حالة طلباتهم للمستشفيات

