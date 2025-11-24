# Firebase Setup Instructions

## إعداد Firebase Cloud Messaging

### الطريقة المفضلة: استخدام ملف JSON (موصى به)

#### 1. إضافة متغيرات Firebase إلى .env

أضف المتغيرات التالية إلى ملف `.env`:

```env
FIREBASE_CREDENTIALS=firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
FIREBASE_PROJECT_ID=season-9ede3
```

**ملاحظات:**
- `FIREBASE_CREDENTIALS`: المسار النسبي لملف JSON من `storage/app/`
- `FIREBASE_PROJECT_ID`: معرف مشروع Firebase

#### 2. التحقق من وجود الملف

تأكد من أن الملف موجود في المسار:
```
storage/app/firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
```

إذا كان اسم الملف مختلف، قم بتحديث `FIREBASE_CREDENTIALS` في `.env` ليطابق اسم الملف الفعلي.

### الطريقة البديلة: استخدام .env (غير موصى به)

إذا لم يكن ملف JSON متاحاً، يمكنك إضافة البيانات إلى `.env`:

```env
FIREBASE_TYPE=service_account
FIREBASE_PROJECT_ID=season-9ede3
FIREBASE_PRIVATE_KEY_ID=08a9f61c5ba9907be3cb4b069f7c11fecace38c3
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-fbsvc@season-9ede3.iam.gserviceaccount.com
FIREBASE_CLIENT_ID=106617930989900361754
FIREBASE_AUTH_URI=https://accounts.google.com/o/oauth2/auth
FIREBASE_TOKEN_URI=https://oauth2.googleapis.com/token
FIREBASE_AUTH_PROVIDER_X509_CERT_URL=https://www.googleapis.com/oauth2/v1/certs
FIREBASE_CLIENT_X509_CERT_URL=https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40season-9ede3.iam.gserviceaccount.com
FIREBASE_UNIVERSE_DOMAIN=googleapis.com
```

### 3. تثبيت Firebase Package

قم بتشغيل الأمر التالي:

```bash
composer require kreait/firebase-php
```

### 4. تشغيل Migrations

قم بتشغيل migrations لتحديث قاعدة البيانات:

```bash
php artisan migrate
```

### 5. اختبار الإشعارات

بعد إعداد Firebase، يمكنك اختبار الإشعارات من خلال:
- إنشاء طلب SOS جديد (سيتم إرسال إشعارات للمتبرعين القريبين)
- قبول طلب SOS (سيتم إرسال إشعارات للمريض والمتبرع)

## ملاحظات

- **الأفضل:** استخدام ملف JSON بدلاً من `.env` للأمان والتنظيم
- تأكد من أن ملف JSON موجود في المسار الصحيح
- إذا واجهت مشاكل، تحقق من logs في `storage/logs/laravel.log`
- النظام يحاول استخدام ملف JSON أولاً، ثم ينتقل إلى `.env` كبديل

