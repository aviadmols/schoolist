# הוראות התקנה - Schoolist App

## דרישות מערכת

- PHP 7.4 או גבוה יותר
- MySQL 5.7 או גבוה יותר (או MariaDB 10.2+)
- Apache/Nginx עם mod_rewrite
- הרשאות כתיבה לתיקיות: `logs/`, `storage/`, `public/uploads/`

## שלבי התקנה

### 1. העתקת קבצים לשרת

העתק את כל קבצי הפרויקט לתיקיית ה-web server שלך:

```bash
# לדוגמה:
scp -r * user@your-server.com:/var/www/schoolist/
```

או באמצעות FTP/SFTP - העתק את כל התיקיות והקבצים.

### 2. הגדרת קובץ קונפיגורציה

1. העתק את קובץ הדוגמה:
   ```bash
   cp config/config.local.php.example config/config.local.php
   ```

2. ערוך את `config/config.local.php` ועדכן את פרטי מסד הנתונים:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('DB_PREFIX', 'sl_'); // קידומת לטבלאות
   ```

3. עדכן את שאר ההגדרות לפי הצורך:
   - `BASE_URL` - כתובת הבסיס של האתר
   - `SMTP_*` - הגדרות SMTP לשליחת אימיילים
   - `OPENAI_API_KEY` - מפתח API של OpenAI (אופציונלי)

### 3. יצירת מסד נתונים

צור מסד נתונים חדש ב-MySQL:

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. הרצת סקריפט ההתקנה

יש לך שתי אפשרויות:

#### אפשרות א': דרך הדפדפן
1. פתח בדפדפן: `http://your-domain.com/install.php`
2. הסקריפט יבצע את כל המיגרציות אוטומטית

#### אפשרות ב': דרך שורת הפקודה
```bash
php install.php
```

הסקריפט יבצע:
- בדיקת חיבור למסד הנתונים
- יצירת כל הטבלאות הנדרשות
- הרצת כל המיגרציות
- בדיקת תקינות ההתקנה

### 5. הגדרת הרשאות

ודא שהתיקיות הבאות ניתנות לכתיבה:

```bash
chmod 755 logs/
chmod 755 storage/
chmod 755 public/uploads/
```

### 6. הגדרת Web Server

#### Apache (.htaccess)
ודא ש-mod_rewrite מופעל וקובץ `.htaccess` קיים בתיקיית השורש.

#### Nginx
הוסף את ההגדרות הבאות:

```nginx
location / {
    try_files $uri $uri/ /router.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index router.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 7. בדיקת התקנה

1. פתח את האתר בדפדפן
2. אם זו התקנה ראשונה, תועבר לעמוד ההתקנה (`/setup`)
3. צור משתמש מנהל מערכת ראשון
4. צור קוד הזמנה לדף ראשון

## עדכון מסד נתונים

אם אתה מעדכן גרסה קיימת, פשוט הרץ שוב את `install.php`:
- הוא יבדוק אילו טבלאות קיימות
- יוסיף טבלאות חדשות אם נדרש
- יעדכן טבלאות קיימות עם עמודות חדשות

## פתרון בעיות

### שגיאת חיבור למסד נתונים
- ודא שפרטי החיבור נכונים ב-`config/config.local.php`
- ודא שמסד הנתונים קיים
- ודא שלמשתמש יש הרשאות מתאימות

### שגיאת הרשאות
```bash
chmod -R 755 logs/ storage/ public/uploads/
chown -R www-data:www-data logs/ storage/ public/uploads/
```

### שגיאת mod_rewrite
ב-Apache, ודא ש-mod_rewrite מופעל:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## גיבוי מסד נתונים

לפני עדכון, מומלץ לגבות את מסד הנתונים:

```bash
mysqldump -u your_user -p your_database_name > backup_$(date +%Y%m%d).sql
```

## תמיכה

לשאלות ותמיכה, אנא צור issue ב-GitHub או פנה למפתח.

