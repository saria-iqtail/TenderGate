# دليل التثبيت المفصل - TenderGate

## المتطلبات الأساسية

### متطلبات الخادم
- **نظام التشغيل**: Ubuntu 20.04+ / CentOS 8+ / Windows Server 2019+
- **ذاكرة الوصول العشوائي**: 2GB كحد أدنى، 4GB مُوصى به
- **مساحة القرص الصلب**: 10GB كحد أدنى
- **معالج**: 2 نواة كحد أدنى

### البرامج المطلوبة
- **Apache**: 2.4.41+
- **PHP**: 7.4+ (مُوصى به 8.0+)
- **MySQL**: 5.7+ أو MariaDB 10.3+

## التثبيت على Ubuntu/Debian

### 1. تحديث النظام

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. تثبيت Apache

```bash
sudo apt install apache2 -y
sudo systemctl start apache2
sudo systemctl enable apache2
```

### 3. تثبيت MySQL

```bash
sudo apt install mysql-server -y
sudo systemctl start mysql
sudo systemctl enable mysql

# تأمين MySQL
sudo mysql_secure_installation
```

### 4. تثبيت PHP والإضافات المطلوبة

```bash
sudo apt install php libapache2-mod-php php-mysql php-mbstring php-xml php-curl php-zip php-gd -y

# إعادة تشغيل Apache
sudo systemctl restart apache2
```

### 5. إعداد قاعدة البيانات

```bash
# الدخول إلى MySQL
sudo mysql -u root -p

# تنفيذ الأوامر التالية في MySQL
```

```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE tendergate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- إنشاء مستخدم
CREATE USER 'tendergate_user'@'localhost' IDENTIFIED BY 'TenderGate@2024!';

-- منح الصلاحيات
GRANT ALL PRIVILEGES ON tendergate.* TO 'tendergate_user'@'localhost';
FLUSH PRIVILEGES;

-- الخروج
EXIT;
```

### 6. تثبيت ملفات المشروع

```bash
# إنشاء مجلد المشروع
sudo mkdir -p /var/www/html/tendergate

# نسخ الملفات (افترض أن الملفات في /home/user/TenderGate)
sudo cp -r /path/to/TenderGate/* /var/www/html/tendergate/

# تعيين الصلاحيات
sudo chown -R www-data:www-data /var/www/html/tendergate/
sudo chmod -R 755 /var/www/html/tendergate/

# صلاحيات خاصة لمجلد الرفع
sudo chmod -R 777 /var/www/html/tendergate/uploads/
```

### 7. استيراد قاعدة البيانات

```bash
mysql -u tendergate_user -p tendergate < /var/www/html/tendergate/database.sql
```

### 8. تكوين Apache

```bash
# إنشاء ملف تكوين الموقع
sudo nano /etc/apache2/sites-available/tendergate.conf
```

```apache
<VirtualHost *:80>
    ServerName tendergate.local
    DocumentRoot /var/www/html/tendergate
    
    <Directory /var/www/html/tendergate>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/tendergate_error.log
    CustomLog ${APACHE_LOG_DIR}/tendergate_access.log combined
</VirtualHost>
```

```bash
# تفعيل الموقع
sudo a2ensite tendergate.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## التثبيت على CentOS/RHEL

### 1. تحديث النظام

```bash
sudo yum update -y
```

### 2. تثبيت Apache

```bash
sudo yum install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd
```

### 3. تثبيت MySQL/MariaDB

```bash
sudo yum install mariadb-server mariadb -y
sudo systemctl start mariadb
sudo systemctl enable mariadb
sudo mysql_secure_installation
```

### 4. تثبيت PHP

```bash
sudo yum install php php-mysql php-mbstring php-xml php-curl php-zip php-gd -y
sudo systemctl restart httpd
```

### 5. إعداد Firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## التثبيت على Windows Server

### 1. تثبيت XAMPP

1. تحميل XAMPP من الموقع الرسمي
2. تثبيت XAMPP مع Apache, MySQL, PHP
3. تشغيل Apache و MySQL من لوحة تحكم XAMPP

### 2. إعداد المشروع

1. نسخ ملفات المشروع إلى `C:\xampp\htdocs\tendergate\`
2. تعديل ملف `includes/config.php` حسب إعدادات XAMPP

### 3. إعداد قاعدة البيانات

1. فتح phpMyAdmin من `http://localhost/phpmyadmin`
2. إنشاء قاعدة بيانات جديدة باسم `tendergate`
3. استيراد ملف `database.sql`

## إعداد البريد الإلكتروني

### استخدام Gmail SMTP

```php
// في ملف includes/functions.php
// إضافة إعدادات SMTP

function sendEmailSMTP($to, $subject, $message) {
    require_once 'PHPMailer/PHPMailer.php';
    require_once 'PHPMailer/SMTP.php';
    require_once 'PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';
    $mail->Password = 'your-app-password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    
    $mail->setFrom('your-email@gmail.com', 'TenderGate');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $message;
    $mail->isHTML(true);
    
    return $mail->send();
}
```

## إعداد SSL (HTTPS)

### باستخدام Let's Encrypt

```bash
# تثبيت Certbot
sudo apt install certbot python3-certbot-apache -y

# الحصول على شهادة SSL
sudo certbot --apache -d yourdomain.com

# تجديد تلقائي
sudo crontab -e
# إضافة السطر التالي:
0 12 * * * /usr/bin/certbot renew --quiet
```

## تحسين الأداء

### 1. تفعيل ضغط Gzip

```apache
# في ملف .htaccess
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
```

### 2. تفعيل التخزين المؤقت

```apache
# في ملف .htaccess
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>
```

## النسخ الاحتياطي

### نسخ احتياطي لقاعدة البيانات

```bash
#!/bin/bash
# ملف backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/tendergate"
DB_NAME="tendergate"
DB_USER="tendergate_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# نسخ احتياطي لقاعدة البيانات
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# نسخ احتياطي للملفات
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/tendergate/uploads/

# حذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

```bash
# جعل الملف قابل للتنفيذ
chmod +x backup.sh

# إضافة مهمة مجدولة للنسخ الاحتياطي اليومي
crontab -e
# إضافة السطر التالي:
0 2 * * * /path/to/backup.sh
```

## مراقبة النظام

### مراقبة سجلات الأخطاء

```bash
# مراقبة سجلات Apache
sudo tail -f /var/log/apache2/error.log

# مراقبة سجلات PHP
sudo tail -f /var/log/php_errors.log

# مراقبة سجلات MySQL
sudo tail -f /var/log/mysql/error.log
```

### إعداد تنبيهات

```bash
# إنشاء ملف مراقبة
nano monitor.sh
```

```bash
#!/bin/bash
# مراقبة استخدام القرص
DISK_USAGE=$(df / | grep -vE '^Filesystem|tmpfs|cdrom' | awk '{ print $5 }' | sed 's/%//g')

if [ $DISK_USAGE -gt 80 ]; then
    echo "تحذير: استخدام القرص $DISK_USAGE%" | mail -s "تحذير خادم TenderGate" admin@yourdomain.com
fi

# مراقبة استخدام الذاكرة
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}')

if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    echo "تحذير: استخدام الذاكرة $MEMORY_USAGE%" | mail -s "تحذير خادم TenderGate" admin@yourdomain.com
fi
```

## استكشاف الأخطاء الشائعة

### 1. خطأ 500 Internal Server Error

```bash
# فحص سجلات الأخطاء
sudo tail -f /var/log/apache2/error.log

# فحص صلاحيات الملفات
ls -la /var/www/html/tendergate/

# فحص ملف .htaccess
cat /var/www/html/tendergate/.htaccess
```

### 2. خطأ في الاتصال بقاعدة البيانات

```bash
# اختبار الاتصال بقاعدة البيانات
mysql -u tendergate_user -p tendergate

# فحص حالة خدمة MySQL
sudo systemctl status mysql
```

### 3. مشاكل رفع الملفات

```bash
# فحص إعدادات PHP
php -i | grep upload

# فحص صلاحيات مجلد uploads
ls -la /var/www/html/tendergate/uploads/

# تعيين الصلاحيات الصحيحة
sudo chmod -R 777 /var/www/html/tendergate/uploads/
```

## الأمان والحماية

### 1. تحديث كلمات المرور الافتراضية

```sql
-- تحديث كلمة مرور المدير
UPDATE users SET password = '$2y$10$newhashedpassword' WHERE email = 'admin@tendergate.com';
```

### 2. إعداد جدار الحماية

```bash
# Ubuntu/Debian
sudo ufw enable
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443

# CentOS/RHEL
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 3. تأمين PHP

```ini
# في ملف php.ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
```

## الصيانة الدورية

### مهام يومية
- فحص سجلات الأخطاء
- مراقبة استخدام الموارد
- النسخ الاحتياطي

### مهام أسبوعية
- تحديث النظام والبرامج
- فحص أمان الملفات
- تنظيف الملفات المؤقتة

### مهام شهرية
- مراجعة أداء قاعدة البيانات
- تحليل سجلات الوصول
- اختبار النسخ الاحتياطي

---

هذا الدليل يغطي جميع جوانب تثبيت وإعداد نظام TenderGate. في حالة واجهت أي مشاكل، يرجى مراجعة قسم استكشاف الأخطاء أو التواصل مع فريق الدعم.

