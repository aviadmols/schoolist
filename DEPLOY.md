# מדריך פריסה - Schoolist App

## הכנה לפריסה

### 1. גיבוי מסד נתונים קיים (אם קיים)

```bash
mysqldump -u your_user -p your_database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. העתקת קבצים לשרת

#### באמצעות SCP:
```bash
scp -r * user@your-server.com:/var/www/schoolist/
```

#### באמצעות Git:
```bash
git clone your-repo-url /var/www/schoolist/
```

#### באמצעות FTP/SFTP:
העתק את כל הקבצים והתיקיות לשרת.

### 3. הגדרת הרשאות

```bash
cd /var/www/schoolist
chmod -R 755 .
chmod -R 777 logs/
chmod -R 777 storage/
chmod -R 777 public/uploads/
chown -R www-data:www-data .
```

### 4. הגדרת קובץ קונפיגורציה

```bash
cp config/config.local.php.example config/config.local.php
nano config/config.local.php
```

עדכן את הפרטים הבאים:
- `DB_HOST` - כתובת שרת מסד הנתונים
- `DB_NAME` - שם מסד הנתונים
- `DB_USER` - שם משתמש למסד הנתונים
- `DB_PASS` - סיסמת מסד הנתונים
- `DB_PREFIX` - קידומת לטבלאות (בדרך כלל `sl_`)
- `BASE_URL` - כתובת הבסיס של האתר

### 5. יצירת מסד נתונים

```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON your_database_name.* TO 'your_database_user'@'localhost';
FLUSH PRIVILEGES;
```

### 6. הרצת סקריפט ההתקנה

#### דרך הדפדפן:
פתח: `http://your-domain.com/install.php`

#### דרך שורת הפקודה:
```bash
php install.php
```

הסקריפט יבצע:
- ✅ בדיקת חיבור למסד הנתונים
- ✅ יצירת כל הטבלאות הנדרשות
- ✅ הרצת כל המיגרציות
- ✅ עדכון טבלאות קיימות
- ✅ בדיקת תקינות ההתקנה

### 7. הגדרת Web Server

#### Apache

ודא ש-mod_rewrite מופעל:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

ודא שקובץ `.htaccess` קיים בתיקיית השורש.

#### Nginx

הוסף את ההגדרות הבאות ל-config של Nginx:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/schoolist/public;
    index index.php router.php;

    location / {
        try_files $uri $uri/ /router.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index router.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 8. בדיקת התקנה

1. פתח את האתר בדפדפן
2. אם זו התקנה ראשונה, תועבר לעמוד ההתקנה (`/setup`)
3. צור משתמש מנהל מערכת ראשון
4. צור קוד הזמנה לדף ראשון

## עדכון גרסה קיימת

1. **גבה את מסד הנתונים:**
   ```bash
   mysqldump -u your_user -p your_database_name > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **העתק את הקבצים החדשים:**
   ```bash
   # שמור את config/config.local.php
   cp config/config.local.php /tmp/config_backup.php
   
   # העתק קבצים חדשים
   # ... העתקת קבצים ...
   
   # החזר את קובץ הקונפיגורציה
   cp /tmp/config_backup.php config/config.local.php
   ```

3. **הרץ את סקריפט ההתקנה:**
   ```bash
   php install.php
   ```
   
   הסקריפט יעדכן אוטומטית:
   - טבלאות חדשות
   - עמודות חדשות בטבלאות קיימות
   - אינדקסים חדשים

4. **נקה cache (אם יש):**
   ```bash
   rm -rf storage/ratelimit/*.json
   ```

## רשימת קבצים חשובים

### קבצים שצריך לשמור בעדכון:
- `config/config.local.php` - קובץ הקונפיגורציה
- `public/uploads/` - קבצים שהועלו
- `logs/` - לוגים (אופציונלי)

### קבצים שצריך לעדכן:
- כל הקבצים ב-`app/`
- כל הקבצים ב-`public/assets/`
- `index.php`
- `router.php`
- `install.php`

## פתרון בעיות נפוצות

### שגיאת 500 Internal Server Error
- בדוק את לוגי השגיאות: `tail -f logs/app.log`
- ודא שהרשאות נכונות
- ודא ש-PHP error reporting מופעל

### שגיאת חיבור למסד נתונים
- ודא שפרטי החיבור נכונים ב-`config/config.local.php`
- ודא שמסד הנתונים קיים
- ודא שלמשתמש יש הרשאות

### שגיאת mod_rewrite
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### שגיאת הרשאות
```bash
chmod -R 755 .
chmod -R 777 logs/ storage/ public/uploads/
chown -R www-data:www-data .
```

## אבטחה

לאחר התקנה:
1. **הסר את `install.php` או הגבל גישה אליו:**
   ```bash
   chmod 600 install.php
   # או
   rm install.php
   ```

2. **ודא שקובץ הקונפיגורציה מוגן:**
   ```bash
   chmod 600 config/config.local.php
   ```

3. **הפעל HTTPS:**
   - התקן SSL certificate
   - עדכן את `BASE_URL` ל-`https://`

4. **הגבל גישה לתיקיות רגישות:**
   - ודא ש-`.htaccess` מגן על תיקיית `config/`
   - ודא ש-`logs/` לא נגיש דרך הדפדפן

## תמיכה

לשאלות ותמיכה, אנא צור issue או פנה למפתח.

