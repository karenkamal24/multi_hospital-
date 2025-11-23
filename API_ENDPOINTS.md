# 📡 دليل API Endpoints - نظام المستشفيات المتعدد

## 🔐 Authentication Endpoints

### 1. تسجيل مستخدم جديد (Register)

**Endpoint:** `POST /api/auth/register`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "password": "password123",
    "user_type": "patient",
    "blood": "A+",
    "phone": "01234567890",
    "gender": "male",
    "fcm_token": "fcm_token_here_123",
    "latitude": 30.0444,
    "longitude": 31.2357
}
```

**ملاحظات:**
- `user_type` يجب أن يكون: `patient` أو `donner` فقط
- `blood` يمكن أن يكون: `O-`, `O+`, `A-`, `A+`, `B-`, `B+`, `AB-`, `AB+`
- `gender` يمكن أن يكون: `male` أو `female`
- `fcm_token`, `latitude`, `longitude` اختيارية

**Response (نجاح):**
```json
{
    "status": 200,
    "message": "تم التسجيل بنجاح",
    "data": {
        "user": {
            "id": 1,
            "name": "أحمد محمد",
            "email": "ahmed@example.com",
            "user_type": "patient",
            "blood": "A+"
        },
        "token": "1|xxxxxxxxxxxxx..."
    }
}
```

**مثال باستخدام cURL:**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "password": "password123",
    "user_type": "patient",
    "blood": "A+",
    "phone": "01234567890",
    "gender": "male"
  }'
```

---

### 2. تسجيل الدخول (Login)

**Endpoint:** `POST /api/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
    "email": "ahmed@example.com",
    "password": "password123",
    "fcm_token": "updated_fcm_token_here"
}
```

**ملاحظات:**
- `fcm_token` اختياري - إذا أرسلته سيتم تحديثه في قاعدة البيانات

**Response (نجاح):**
```json
{
    "status": 200,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": 1,
            "name": "أحمد محمد",
            "email": "ahmed@example.com",
            "user_type": "patient",
            "blood": "A+",
            "fcm_token": "updated_fcm_token_here",
            "latitude": "30.0444",
            "longitude": "31.2357"
        },
        "token": "2|yyyyyyyyyyyyy..."
    }
}
```

**مثال باستخدام cURL:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ahmed@example.com",
    "password": "password123",
    "fcm_token": "fcm_token_123"
  }'
```

**⚠️ مهم:** احفظ الـ `token` من الـresponse - ستحتاجه في الـendpoints التالية

---

### 3. الحصول على بيانات المستخدم الحالي

**Endpoint:** `GET /api/user`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Response (نجاح):**
```json
{
    "id": 1,
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "user_type": "patient",
    "blood": "A+",
    "latitude": "30.0444",
    "longitude": "31.2357"
}
```

**مثال باستخدام cURL:**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 📍 Location Endpoints

### 4. تحديث موقع المستخدم

**Endpoint:** `POST /api/location`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
    "lat": "30.0444",
    "lng": "31.2357"
}
```

**ملاحظات:**
- `lat` يجب أن يكون بين -90 و 90
- `lng` يجب أن يكون بين -180 و 180
- يعمل لجميع أنواع المستخدمين

**Response (نجاح):**
```json
{
    "status": 200,
    "message": "تم تحديث الموقع بنجاح",
    "data": {
        "latitude": "30.0444",
        "longitude": "31.2357"
    }
}
```

**مثال باستخدام cURL:**
```bash
curl -X POST http://localhost:8000/api/location \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "lat": "30.0444",
    "lng": "31.2357"
  }'
```

---

## 🆘 SOS Endpoints

### 5. إرسال طلب SOS (للتبرع بالدم/الأعضاء)

**Endpoint:** `POST /api/sos`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "description": "طلب عاجل للتبرع بالدم"
}
```

**ملاحظات:**
- **فقط `patient` يمكنه إرسال SOS**
- `type` يجب أن يكون: `blood` أو `organ`
- `blood` اختياري - إذا لم ترسله سيستخدم فصيلة دم المستخدم
- `latitude` و `longitude` مطلوبة
- `description` اختياري

**Response (نجاح):**
```json
{
    "status": 201,
    "message": "تم إنشاء طلب SOS بنجاح وإرسال الإشعارات للمتبرعين القريبين",
    "data": {
        "sos_id": 1,
        "donors_count": 3,
        "notifications": {
            "success": 3,
            "failure": 0
        }
    }
}
```

**مثال باستخدام cURL:**
```bash
curl -X POST http://localhost:8000/api/sos \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "description": "طلب عاجل"
  }'
```

---

## 📋 ترتيب الاختبار الموصى به

### الخطوة 1: إنشاء مستخدم Patient
```bash
POST /api/auth/register
{
    "name": "أحمد المريض",
    "email": "patient@test.com",
    "password": "password123",
    "user_type": "patient",
    "blood": "A+",
    "fcm_token": "patient_fcm_token_123",
    "latitude": 30.0444,
    "longitude": 31.2357
}
```
**احفظ الـ token من الـresponse**

---

### الخطوة 2: إنشاء مستخدم Donner
```bash
POST /api/auth/register
{
    "name": "محمد المتبرع",
    "email": "donner@test.com",
    "password": "password123",
    "user_type": "donner",
    "blood": "A+",
    "fcm_token": "donner_fcm_token_456",
    "latitude": 30.0450,
    "longitude": 31.2360
}
```

---

### الخطوة 3: تسجيل الدخول كـ Patient
```bash
POST /api/auth/login
{
    "email": "patient@test.com",
    "password": "password123",
    "fcm_token": "updated_patient_token"
}
```
**احفظ الـ token الجديد**

---

### الخطوة 4: تحديث الموقع (اختياري)
```bash
POST /api/location
Authorization: Bearer PATIENT_TOKEN
{
    "lat": "30.0444",
    "lng": "31.2357"
}
```

---

### الخطوة 5: إرسال SOS
```bash
POST /api/sos
Authorization: Bearer PATIENT_TOKEN
{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357
}
```

---

## 🧪 أمثلة باستخدام Postman

### 1. Register Request
- **Method:** POST
- **URL:** `http://localhost:8000/api/auth/register`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "name": "أحمد المريض",
    "email": "patient@test.com",
    "password": "password123",
    "user_type": "patient",
    "blood": "A+"
}
```

### 2. Login Request
- **Method:** POST
- **URL:** `http://localhost:8000/api/auth/login`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "email": "patient@test.com",
    "password": "password123"
}
```

### 3. Update Location Request
- **Method:** POST
- **URL:** `http://localhost:8000/api/location`
- **Headers:**
  - `Authorization: Bearer YOUR_TOKEN_HERE`
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "lat": "30.0444",
    "lng": "31.2357"
}
```

### 4. Send SOS Request
- **Method:** POST
- **URL:** `http://localhost:8000/api/sos`
- **Headers:**
  - `Authorization: Bearer YOUR_TOKEN_HERE`
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357
}
```

---

## 🔍 فصائل الدم المتوافقة

| فصيلة المريض | الفصائل المتوافقة (يمكنها التبرع) |
|-------------|--------------------------------|
| O- | O- |
| O+ | O-, O+ |
| A- | O-, A- |
| A+ | O-, O+, A-, A+ |
| B- | O-, B- |
| B+ | O-, O+, B-, B+ |
| AB- | O-, A-, B-, AB- |
| AB+ | جميع الفصائل |

---

## ⚠️ ملاحظات مهمة

1. **جميع الـendpoints المحمية بـ token تحتاج:**
   ```
   Authorization: Bearer YOUR_TOKEN_HERE
   ```

2. **للحصول على token:**
   - سجل دخول من `/api/auth/login`
   - أو سجل حساب جديد من `/api/auth/register`

3. **فقط `patient` يمكنه إرسال SOS:**
   - إذا حاول `donner` أو `hospital` إرسال SOS سيحصل على خطأ 403

4. **لاختبار SOS بنجاح:**
   - تأكد من وجود `donner` قريب (في نفس المسافة المحددة في `sos_radius_km`)
   - تأكد من أن فصيلة دم `donner` متوافقة مع فصيلة دم `patient`
   - تأكد من وجود `fcm_token` للمتبرع

5. **لاختبار الإشعارات:**
   - تأكد من إضافة `FCM_SERVER_KEY` في ملف `.env`
   - `fcm_token` يجب أن يكون token حقيقي من Firebase

---

## 📝 Checklist للاختبار

- [ ] تم إنشاء مستخدم `patient`
- [ ] تم إنشاء مستخدم `donner` (قريب + فصيلة متوافقة)
- [ ] تم تسجيل الدخول والحصول على `token`
- [ ] تم تحديث الموقع بنجاح
- [ ] تم إرسال SOS بنجاح
- [ ] `donors_count > 0` في الـresponse
- [ ] `notifications.success > 0` في الـresponse

---

## 🐛 استكشاف الأخطاء

### خطأ 401 Unauthorized
- تحقق من صحة الـtoken
- تأكد من إرسال `Authorization: Bearer TOKEN`

### خطأ 403 Forbidden (في SOS)
- تأكد من أن `user_type = "patient"`

### `donors_count = 0`
- تحقق من المسافة بين المريض والمتبرع
- تحقق من فصائل الدم المتوافقة
- تحقق من وجود `fcm_token` للمتبرع

### `notifications.success = 0`
- تحقق من `FCM_SERVER_KEY` في `.env`
- تحقق من صحة `fcm_token`


