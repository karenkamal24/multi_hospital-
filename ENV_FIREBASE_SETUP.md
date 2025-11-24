# إضافة متغيرات Firebase إلى ملف .env

## الموقع
ملف `.env` موجود في **جذر المشروع** (نفس المجلد الذي يحتوي على `composer.json` و `artisan`)

## المتغيرات المطلوبة

أضف السطور التالية إلى ملف `.env`:

```env
FIREBASE_CREDENTIALS=firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
FIREBASE_PROJECT_ID=season-9ede3
```

## خطوات الإضافة

1. افتح ملف `.env` في جذر المشروع
2. أضف السطرين أعلاه في أي مكان في الملف
3. احفظ الملف

## مثال على ملف .env

```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

# ... باقي المتغيرات ...

# Firebase Configuration
FIREBASE_CREDENTIALS=firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
FIREBASE_PROJECT_ID=season-9ede3
```

## ملاحظات مهمة

- **المسار النسبي**: `FIREBASE_CREDENTIALS` يجب أن يكون مساراً نسبياً من `storage/app/`
- **اسم الملف**: تأكد من أن اسم الملف في `FIREBASE_CREDENTIALS` يطابق اسم الملف الفعلي في `storage/app/firebase/`
- **لا مسافات**: تأكد من عدم وجود مسافات حول علامة `=`

## التحقق من الملف

تأكد من وجود ملف JSON في:
```
storage/app/firebase/season-9ede3-firebase-adminsdk-fbsvc-c1b9e2f2e7.json
```

إذا كان اسم الملف مختلف، قم بتحديث `FIREBASE_CREDENTIALS` في `.env` ليطابق اسم الملف الفعلي.

