# TenderGate - نظام إدارة العطاءات

## نظرة عامة

TenderGate هو نظام متكامل لإدارة العطاءات مطور باستخدام التقنيات التالية:
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript, jQuery
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Icons**: Font Awesome 6

## الميزات الرئيسية

### للمستخدمين
- ✅ تسجيل حساب جديد مع التحقق من البريد الإلكتروني
- ✅ تسجيل الدخول الآمن
- ✅ إدارة الملف الشخصي مع إمكانية تحديث البيانات
- ✅ تصفح العطاءات المتاحة
- ✅ البحث والفلترة في العطاءات
- ✅ تقديم طلبات للعطاءات مع رفع الملفات
- ✅ متابعة حالة الطلبات المقدمة
- ✅ استقبال الإشعارات عبر البريد الإلكتروني
- ✅ استعادة كلمة المرور

### للإدارة
- ✅ لوحة تحكم شاملة مع الإحصائيات
- ✅ إدارة المستخدمين (إضافة، حذف، تفعيل)
- ✅ إدارة العطاءات (إضافة، تعديل، حذف)
- ✅ مراجعة طلبات المستخدمين
- ✅ إرسال الإشعارات للمستخدمين
- ✅ تصدير البيانات

## متطلبات النظام

- **خادم ويب**: Apache 2.4+ أو Nginx 1.18+
- **PHP**: الإصدار 7.4 أو أحدث
- **MySQL**: الإصدار 5.7 أو أحدث
- **Extensions PHP المطلوبة**:
  - mysqli
  - session
  - mail
  - fileinfo
  - json

## تعليمات التثبيت

### 1. تحضير البيئة

```bash
# تأكد من تثبيت Apache و PHP و MySQL
sudo apt update
sudo apt install apache2 php mysql-server php-mysql php-mbstring
```

### 2. إعداد قاعدة البيانات

```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE tendergate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم قاعدة البيانات
CREATE USER 'tendergate_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON tendergate.* TO 'tendergate_user'@'localhost';
FLUSH PRIVILEGES;

-- استيراد هيكل قاعدة البيانات
mysql -u tendergate_user -p tendergate < database.sql
```

### 3. تثبيت الملفات

```bash
# نسخ ملفات المشروع إلى مجلد الخادم
sudo cp -r TenderGate/ /var/www/html/

# تعيين الصلاحيات
sudo chown -R www-data:www-data /var/www/html/TenderGate/
sudo chmod -R 755 /var/www/html/TenderGate/
sudo chmod -R 777 /var/www/html/TenderGate/uploads/
```

### 4. إعداد التكوين

```php
// تحديث ملف includes/config.php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'tendergate_user');
define('DB_PASSWORD', 'your_secure_password');
define('DB_NAME', 'tendergate');
```

### 5. إعداد البريد الإلكتروني (اختياري)

```php
// في ملف includes/functions.php
// تحديث إعدادات البريد الإلكتروني حسب خادمك
```

## هيكل المشروع

```
TenderGate/
├── admin/                  # لوحة تحكم الإدارة
│   ├── dashboard.php      # الصفحة الرئيسية للإدارة
│   ├── users.php          # إدارة المستخدمين
│   ├── tenders.php        # إدارة العطاءات
│   ├── add-tender.php     # إضافة عطاء جديد
│   └── applications.php   # مراجعة الطلبات
├── assets/                # الملفات الثابتة
│   ├── css/
│   │   └── style.css      # ملف CSS الرئيسي
│   └── js/
│       └── main.js        # ملف JavaScript الرئيسي
├── includes/              # الملفات المشتركة
│   ├── config.php         # إعدادات قاعدة البيانات
│   └── functions.php      # الوظائف المساعدة
├── public/                # الصفحات العامة
│   ├── index.php          # الصفحة الرئيسية
│   ├── login.php          # تسجيل الدخول
│   ├── register.php       # التسجيل
│   ├── forgot-password.php # استعادة كلمة المرور
│   └── reset-password.php # إعادة تعيين كلمة المرور
├── uploads/               # ملفات المستخدمين
│   ├── applications/      # ملفات طلبات العطاءات
│   ├── profiles/          # صور الملفات الشخصية
│   └── tenders/           # ملفات العطاءات
├── user/                  # صفحات المستخدمين
│   ├── dashboard.php      # لوحة تحكم المستخدم
│   ├── profile.php        # الملف الشخصي
│   ├── tenders.php        # عرض العطاءات
│   ├── tender-details.php # تفاصيل العطاء
│   └── my-applications.php # طلبات المستخدم
├── database.sql           # هيكل قاعدة البيانات
└── README.md             # هذا الملف
```

## الاستخدام

### للمستخدمين الجدد

1. **التسجيل**: انتقل إلى `/public/register.php` وأنشئ حساباً جديداً
2. **تسجيل الدخول**: استخدم بياناتك للدخول عبر `/public/login.php`
3. **تصفح العطاءات**: اذهب إلى `/user/tenders.php` لعرض العطاءات المتاحة
4. **تقديم طلب**: اضغط على "عرض التفاصيل" ثم "تقديم طلب"

### للإدارة

1. **الدخول كمدير**: استخدم حساب المدير الافتراضي
2. **إدارة العطاءات**: أضف عطاءات جديدة من `/admin/add-tender.php`
3. **مراجعة الطلبات**: تابع طلبات المستخدمين من `/admin/applications.php`
4. **إدارة المستخدمين**: أضف أو احذف مستخدمين من `/admin/users.php`

## الحساب الافتراضي للإدارة

```
البريد الإلكتروني: admin@tendergate.com
كلمة المرور: Admin@123
```

**⚠️ مهم**: يرجى تغيير كلمة مرور المدير فور التثبيت!

## الأمان

- جميع كلمات المرور مشفرة باستخدام `password_hash()`
- حماية من هجمات SQL Injection
- تنظيف جميع المدخلات
- حماية الملفات المرفوعة
- جلسات آمنة

## استكشاف الأخطاء

### مشاكل شائعة

1. **خطأ في الاتصال بقاعدة البيانات**
   - تحقق من إعدادات `config.php`
   - تأكد من تشغيل خدمة MySQL

2. **لا يمكن رفع الملفات**
   - تحقق من صلاحيات مجلد `uploads/`
   - تأكد من إعدادات PHP للرفع

3. **لا تصل رسائل البريد الإلكتروني**
   - تحقق من إعدادات خادم البريد
   - تأكد من تفعيل وظيفة `mail()` في PHP

## التطوير والتخصيص

### إضافة ميزات جديدة

1. أضف الجداول المطلوبة في `database.sql`
2. أنشئ الوظائف في `includes/functions.php`
3. أضف الصفحات في المجلد المناسب
4. حدث ملفات CSS و JavaScript حسب الحاجة

### تخصيص التصميم

- عدل ملف `assets/css/style.css` لتغيير الألوان والخطوط
- استخدم متغيرات Bootstrap لتخصيص سريع
- أضف CSS مخصص في نهاية الملف

## الدعم والمساهمة

### الإبلاغ عن الأخطاء

إذا واجهت أي مشاكل، يرجى التحقق من:
1. سجلات خطأ Apache/Nginx
2. سجلات خطأ PHP
3. سجلات MySQL

### المساهمة

نرحب بالمساهمات! يرجى:
1. إنشاء fork للمشروع
2. إنشاء branch للميزة الجديدة
3. إرسال pull request

## الترخيص

هذا المشروع مطور لأغراض تعليمية وتجارية.

## معلومات الاتصال

- **المطور**: فريق TenderGate
- **البريد الإلكتروني**: support@tendergate.com
- **الموقع**: www.tendergate.com

---
*: هذا النظام مطور وفقاً لأفضل الممارسات في تطوير الويب ويتضمن جميع الميز
**ملاحظة*ات المطلوبة في المواصفات.

