# تعليمات إدخال بيانات الاختبار

## كيفية تشغيل Seeder

### الطريقة 1: تشغيل Seeder مباشرة
```bash
php artisan db:seed --class=TestDataSeeder
```

### الطريقة 2: تشغيل جميع Seeders
```bash
php artisan db:seed
```

### الطريقة 3: إعادة إنشاء قاعدة البيانات مع Seeders
```bash
php artisan migrate:fresh --seed
```

---

## البيانات التي سيتم إنشاؤها

### 1. Hospital Users (2)

#### Hospital 1 - مستشفى النور
- **Email:** `hospital1@hospital.com`
- **Password:** `password123`
- **Name:** مستشفى النور
- **Phone:** 0501111111
- **Hospital Name:** مستشفى النور التخصصي
- **Address:** شارع الملك فهد، الرياض، السعودية
- **Location:** الرياض، السعودية
- **Coordinates:** 
  - Latitude: 24.7136
  - Longitude: 46.6753

#### Hospital 2 - مستشفى الأمل
- **Email:** `hospital2@hospital.com`
- **Password:** `password123`
- **Name:** مستشفى الأمل
- **Phone:** 0502222222
- **Hospital Name:** مستشفى الأمل
- **Address:** شارع العليا، الرياض، السعودية
- **Location:** الرياض، السعودية
- **Coordinates:**
  - Latitude: 24.7236
  - Longitude: 46.6853

### 2. Patients (3 مرضى)

#### Patient 1
- **Email:** `patient1@test.com`
- **Password:** `password123`
- **Name:** أحمد محمد
- **Phone:** 0503333333
- **Blood Type:** O+
- **Gender:** male
- **Location:** Latitude: 24.7136, Longitude: 46.6753

#### Patient 2
- **Email:** `patient2@test.com`
- **Password:** `password123`
- **Name:** فاطمة أحمد
- **Phone:** 0503333334
- **Blood Type:** A+
- **Gender:** female
- **Location:** Latitude: 24.7200, Longitude: 46.6800

#### Patient 3
- **Email:** `patient3@test.com`
- **Password:** `password123`
- **Name:** خالد سعيد
- **Phone:** 0503333335
- **Blood Type:** B+
- **Gender:** male
- **Location:** Latitude: 24.7150, Longitude: 46.6700

### 3. Donors (5 متبرعين)

#### Donor 1
- **Email:** `donor1@test.com`
- **Password:** `password123`
- **Name:** محمد علي
- **Phone:** 0504444444
- **Blood Type:** O+
- **Gender:** male
- **Location:** Latitude: 24.7236, Longitude: 46.6853

#### Donor 2
- **Email:** `donor2@test.com`
- **Password:** `password123`
- **Name:** سارة حسن
- **Phone:** 0504444445
- **Blood Type:** O+
- **Gender:** female
- **Location:** Latitude: 24.7100, Longitude: 46.6600

#### Donor 3
- **Email:** `donor3@test.com`
- **Password:** `password123`
- **Name:** علي محمود
- **Phone:** 0504444446
- **Blood Type:** A+
- **Gender:** male
- **Location:** Latitude: 24.7250, Longitude: 46.6900

#### Donor 4
- **Email:** `donor4@test.com`
- **Password:** `password123`
- **Name:** نورا إبراهيم
- **Phone:** 0504444447
- **Blood Type:** B+
- **Gender:** female
- **Location:** Latitude: 24.7180, Longitude: 46.6750

#### Donor 5
- **Email:** `donor5@test.com`
- **Password:** `password123`
- **Name:** يوسف عبدالله
- **Phone:** 0504444448
- **Blood Type:** AB+
- **Gender:** male
- **Location:** Latitude: 24.7000, Longitude: 46.6500

### 4. Settings (إعدادات)
- **sos_radius_km:** 10 (مسافة البحث عن المتبرعين بالكيلومتر)

---

## الأدوار (Roles)

سيتم إنشاء الأدوار التالية تلقائياً:
- `super_admin`
- `hospital`
- `patient`
- `donner`

---

## خطوات الاختبار السريع

### 1. تشغيل Seeder
```bash
php artisan db:seed --class=TestDataSeeder
```

### 2. تسجيل الدخول إلى Filament Dashboard
- اذهب إلى: `http://127.0.0.1:8000/admin`
- استخدم: `admin@hospital.com` / `password123`

### 3. اختبار API

#### تسجيل الدخول كمريض
```bash
POST /api/auth/login
{
  "email": "patient1@test.com",
  "password": "password123"
}
```

أو يمكنك استخدام:
- `patient2@test.com` (فاطمة أحمد - A+)
- `patient3@test.com` (خالد سعيد - B+)

#### إنشاء طلب SOS
```bash
POST /api/sos
Authorization: Bearer {patient_token}
{
  "type": "blood",
  "blood": "O+",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "description": "أحتاج تبرع دم عاجل"
}
```

#### إيجاد أقرب مستشفى
```bash
GET /api/hospital-requests/find-nearest
Authorization: Bearer {patient_token}
```

#### إرسال طلب للمستشفى
```bash
POST /api/hospital-requests/
Authorization: Bearer {patient_token}
{
  "hospital_id": 1,
  "user_notes": "أحتاج عملية نقل دم عاجلة"
}
```

---

## ملاحظات مهمة

1. **كلمة المرور:** جميع المستخدمين يستخدمون نفس كلمة المرور: `password123`
2. **الإحداثيات:** تم اختيار إحداثيات في الرياض، السعودية
3. **فصائل الدم:** 
   - المرضى: O+, A+, B+
   - المتبرعين: O+, O+, A+, B+, AB+ (متنوعة ومتوافقة)
4. **المسافة:** جميع المستخدمين قريبون من المستشفيات (أقل من 10 كم)

---

## بعد تشغيل Seeder

بعد تشغيل Seeder بنجاح، يمكنك:

1. ✅ تسجيل الدخول إلى Filament Dashboard
2. ✅ رؤية المستشفيات في قائمة المستشفيات
3. ✅ رؤية المستخدمين في قائمة المستخدمين
4. ✅ اختبار API endpoints
5. ✅ إنشاء طلبات SOS
6. ✅ إرسال طلبات للمستشفيات
7. ✅ الموافقة/الرفض من Dashboard

---

## استكشاف الأخطاء

### المشكلة: Seeder لا يعمل
**الحل:**
```bash
php artisan migrate:fresh
php artisan db:seed --class=TestDataSeeder
```

### المشكلة: لا يمكن تسجيل الدخول
**الحل:**
- تأكد من تشغيل migrations أولاً
- تحقق من أن كلمة المرور صحيحة
- تأكد من أن email موجود في قاعدة البيانات

### المشكلة: لا تظهر المستشفيات
**الحل:**
- تأكد من أن المستخدمين من نوع `hospital` موجودين
- تحقق من أن المستشفيات مرتبطة بـ `user_id` الصحيح

---

## جاهز للاختبار! 🚀

بعد تشغيل Seeder، اتبع الخطوات في `TESTING_SCENARIO.md` لاختبار النظام بالكامل.

