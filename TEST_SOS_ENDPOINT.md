# 🧪 دليل اختبار SOS Endpoint

## 📋 الخطوات بالترتيب

### الخطوة 1: إنشاء مستخدم Patient

**Endpoint:** `POST /api/auth/register`

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "name": "أحمد المريض",
    "email": "patient@test.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "user_type": "patient",
    "blood": "A+",
    "phone": "01012345678",
    "gender": "male",
    "fcm_token": "patient_fcm_token_123"
}
```

**Response المتوقع:**
```json
{
    "status": 201,
    "message": "تم التسجيل بنجاح",
    "data": {
        "id": 1,
        "name": "أحمد المريض",
        "email": "patient@test.com",
        "user_type": "patient"
    }
}
```

---

### الخطوة 2: إنشاء مستخدم Donner (متبرع)

**Endpoint:** `POST /api/auth/register`

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "name": "محمد المتبرع",
    "email": "donner@test.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "user_type": "donner",
    "blood": "A+",
    "phone": "01012345679",
    "gender": "male",
    "fcm_token": "donner_fcm_token_456"
}
```

**⚠️ مهم:** 
- `blood` يجب أن يكون متوافق مع فصيلة المريض (A+ في المثال)
- ستحتاج لتحديث موقعه بعدين

---

### الخطوة 3: تسجيل الدخول كـ Patient

**Endpoint:** `POST /api/auth/login`

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "email": "patient@test.com",
    "password": "12345678"
}
```

**Response المتوقع:**
```json
{
    "status": 200,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": 1,
            "name": "أحمد المريض",
            "email": "patient@test.com",
            "user_type": "patient",
            "blood": "A+"
        },
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

**⚠️ مهم جداً:** انسخ الـ `token` من الـresponse - ستحتاجه في الخطوات التالية

---

### الخطوة 4: تحديث موقع Patient

**Endpoint:** `POST /api/location`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
Accept-Language: ar
```

**Body (raw JSON):**
```json
{
    "lat": "30.0444",
    "lng": "31.2357"
}
```

**استبدل `YOUR_TOKEN_HERE`** بالـtoken من الخطوة السابقة

**Response المتوقع:**
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

---

### الخطوة 5: تسجيل الدخول كـ Donner وتحديث موقعه

**أ) تسجيل الدخول:**
```json
POST /api/auth/login
{
    "email": "donner@test.com",
    "password": "12345678"
}
```

**ب) تحديث موقع Donner (قريب من Patient):**
```json
POST /api/location
Authorization: Bearer DONNER_TOKEN
{
    "lat": "30.0450",
    "lng": "31.2360"
}
```

**⚠️ مهم:** 
- الموقع يجب أن يكون قريب من موقع Patient (في نفس المسافة المحددة في `sos_radius_km`)
- المسافة الافتراضية = 10 كم
- الفرق بين الموقعين في المثال = ~0.1 كم (قريب جداً ✅)

---

### الخطوة 6: إرسال SOS (الخطوة الرئيسية)

**Endpoint:** `POST /api/sos`

**Headers:**
```
Authorization: Bearer PATIENT_TOKEN_HERE
Content-Type: application/json
Accept-Language: ar
```

**Body (raw JSON):**
```json
{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "description": "طلب عاجل للتبرع بالدم"
}
```

**استبدل `PATIENT_TOKEN_HERE`** بالـtoken من الخطوة 3

**Response المتوقع (نجاح):**
```json
{
    "status": 201,
    "message": "تم إنشاء طلب SOS بنجاح وإرسال الإشعارات للمتبرعين القريبين",
    "meta": null,
    "data": {
        "sos_id": 1,
        "donors_count": 1,
        "notifications": {
            "success": 1,
            "failure": 0
        }
    }
}
```

---

## 🧪 أمثلة باستخدام Postman

### 1. إنشاء Collection جديد

1. افتح Postman
2. اضغط **New** > **Collection**
3. اسمه: `Multi Hospital API`

### 2. إضافة Environment

1. اضغط على **Environments** من القائمة الجانبية
2. اضغط **+** لإنشاء جديد
3. أضف المتغيرات:
   - `base_url` = `http://localhost:8000`
   - `patient_token` = (سيتم ملؤه تلقائياً)
   - `donner_token` = (سيتم ملؤه تلقائياً)

### 3. إنشاء Requests

#### Request 1: Register Patient
- **Method:** POST
- **URL:** `{{base_url}}/api/auth/register`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "name": "أحمد المريض",
    "email": "patient@test.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "user_type": "patient",
    "blood": "A+"
}
```

#### Request 2: Login Patient
- **Method:** POST
- **URL:** `{{base_url}}/api/auth/login`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "email": "patient@test.com",
    "password": "12345678"
}
```
- **Tests (Script):**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("patient_token", jsonData.data.token);
}
```

#### Request 3: Update Location (Patient)
- **Method:** POST
- **URL:** `{{base_url}}/api/location`
- **Headers:**
  - `Authorization: Bearer {{patient_token}}`
  - `Content-Type: application/json`
  - `Accept-Language: ar`
- **Body (raw JSON):**
```json
{
    "lat": "30.0444",
    "lng": "31.2357"
}
```

#### Request 4: Register Donner
- **Method:** POST
- **URL:** `{{base_url}}/api/auth/register`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "name": "محمد المتبرع",
    "email": "donner@test.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "user_type": "donner",
    "blood": "A+"
}
```

#### Request 5: Login Donner
- **Method:** POST
- **URL:** `{{base_url}}/api/auth/login`
- **Headers:**
  - `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
    "email": "donner@test.com",
    "password": "12345678"
}
```
- **Tests (Script):**
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    pm.environment.set("donner_token", jsonData.data.token);
}
```

#### Request 6: Update Location (Donner)
- **Method:** POST
- **URL:** `{{base_url}}/api/location`
- **Headers:**
  - `Authorization: Bearer {{donner_token}}`
  - `Content-Type: application/json`
  - `Accept-Language: ar`
- **Body (raw JSON):**
```json
{
    "lat": "30.0450",
    "lng": "31.2360"
}
```

#### Request 7: Send SOS ⭐ (الرئيسي)
- **Method:** POST
- **URL:** `{{base_url}}/api/sos`
- **Headers:**
  - `Authorization: Bearer {{patient_token}}`
  - `Content-Type: application/json`
  - `Accept-Language: ar`
- **Body (raw JSON):**
```json
{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "description": "طلب عاجل للتبرع بالدم"
}
```

---

## 🧪 أمثلة باستخدام cURL

### 1. Register Patient
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "أحمد المريض",
    "email": "patient@test.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "user_type": "patient",
    "blood": "A+"
  }'
```

### 2. Login Patient
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "patient@test.com",
    "password": "12345678"
  }'
```

**انسخ الـtoken من الـresponse**

### 3. Update Location
```bash
curl -X POST http://localhost:8000/api/location \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept-Language: ar" \
  -d '{
    "lat": "30.0444",
    "lng": "31.2357"
  }'
```

### 4. Send SOS ⭐
```bash
curl -X POST http://localhost:8000/api/sos \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept-Language: ar" \
  -d '{
    "type": "blood",
    "blood": "A+",
    "latitude": 30.0444,
    "longitude": 31.2357,
    "description": "طلب عاجل"
  }'
```

---

## ✅ Checklist للاختبار

- [ ] تم إنشاء مستخدم `patient` بنجاح
- [ ] تم إنشاء مستخدم `donner` بنجاح (بنفس فصيلة الدم)
- [ ] تم تسجيل الدخول كـ `patient` والحصول على `token`
- [ ] تم تحديث موقع `patient`
- [ ] تم تسجيل الدخول كـ `donner` وتحديث موقعه (قريب من patient)
- [ ] تم إرسال SOS بنجاح
- [ ] `donors_count > 0` في الـresponse
- [ ] `notifications.success > 0` في الـresponse

---

## 🔍 استكشاف الأخطاء

### `donors_count = 0`

**الأسباب المحتملة:**
1. **المسافة كبيرة:** 
   - تحقق من `sos_radius_km` في Filament (افتراضي = 10 كم)
   - تأكد أن موقع Donner قريب من موقع Patient

2. **فصيلة الدم غير متوافقة:**
   - Patient: A+ → Donner يجب أن يكون: O-, O+, A-, A+
   - راجع قواعد التوافق في `app/helpers.php`

3. **Donner ليس لديه fcm_token:**
   - تأكد من إضافة `fcm_token` عند التسجيل أو Login

### `notifications.success = 0`

**الأسباب:**
1. `FCM_SERVER_KEY` غير موجود في `.env`
2. `fcm_token` غير صحيح أو منتهي الصلاحية
3. Cloud Messaging API غير مفعل في Firebase

**الحل:**
- تحقق من `storage/logs/laravel.log` لرؤية الخطأ التفصيلي

---

## 📝 ملاحظات مهمة

1. **الترتيب مهم:**
   - يجب إنشاء Patient و Donner أولاً
   - يجب تحديث المواقع قبل إرسال SOS

2. **فصائل الدم:**
   - Patient: A+ → Donner يجب أن يكون: A+, A-, O+, O-
   - Patient: O- → Donner يجب أن يكون: O- فقط

3. **المسافة:**
   - المسافة الافتراضية = 10 كم
   - يمكن تعديلها من Filament > الإعدادات > `sos_radius_km`

4. **اللغة:**
   - أضف `Accept-Language: ar` للعربية
   - أضف `Accept-Language: en` للإنجليزية
   - بدون header = عربي افتراضي

---

## 🎯 مثال كامل سريع

```bash
# 1. Register Patient
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"أحمد","email":"patient@test.com","password":"12345678","password_confirmation":"12345678","user_type":"patient","blood":"A+"}'

# 2. Login Patient (انسخ token)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"patient@test.com","password":"12345678"}'

# 3. Update Location
curl -X POST http://localhost:8000/api/location \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept-Language: ar" \
  -d '{"lat":"30.0444","lng":"31.2357"}'

# 4. Register Donner
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"محمد","email":"donner@test.com","password":"12345678","password_confirmation":"12345678","user_type":"donner","blood":"A+","fcm_token":"test_token"}'

# 5. Login Donner وتحديث موقعه (قريب)
curl -X POST http://localhost:8000/api/location \
  -H "Authorization: Bearer DONNER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"lat":"30.0450","lng":"31.2360"}'

# 6. Send SOS ⭐
curl -X POST http://localhost:8000/api/sos \
  -H "Authorization: Bearer PATIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept-Language: ar" \
  -d '{"type":"blood","blood":"A+","latitude":30.0444,"longitude":31.2357}'
```

---

جاهز للاختبار! 🚀


