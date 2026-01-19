# הרצה לוקאלית עם חיבור למסד נתונים בשרת

## הגדרה

1. **צור קובץ `config/config.local.php`** (או העתק את `config/config.local.php.example`):
   ```php
   <?php
   declare(strict_types=1);
   
   // פרטי החיבור למסד הנתונים בשרת
   define('DB_HOST', 'your-server-host.com'); // כתובת השרת או IP
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('DB_PREFIX', 'sl_');
   
   // כתובת בסיס לוקאלית
   define('BASE_URL', 'http://localhost:8000/');
   ```

2. **מלא את פרטי החיבור למסד הנתונים**:
   - `DB_HOST`: כתובת השרת (hostname או IP)
   - `DB_NAME`: שם מסד הנתונים
   - `DB_USER`: שם משתמש
   - `DB_PASS`: סיסמה
   - `DB_PREFIX`: קידומת טבלאות (בדרך כלל `sl_`)

3. **ודא שהפורט MySQL פתוח בשרת**:
   - MySQL משתמש בפורט 3306 כברירת מחדל
   - אם יש firewall, ודא שהוא מאפשר חיבורים מהכתובת שלך
   - אם השרת מאחורי proxy, ייתכן שתצטרך להשתמש בפורט אחר

4. **הרץ את השרת המקומי**:
   ```bash
   php -S localhost:8000 -t .
   ```
   
   או עם XAMPP/WAMP:
   - העתק את הפרויקט לתיקיית `htdocs` או `www`
   - גש ל-`http://localhost/schoolist-app`

## פתרון בעיות

### שגיאת חיבור למסד נתונים
- ודא שפרטי החיבור נכונים
- ודא שהפורט 3306 פתוח בשרת
- בדוק אם יש firewall שחוסם את החיבור
- אם השרת מאחורי proxy, ייתכן שתצטרך להשתמש בכתובת IP ישירה

### שגיאת "Access denied"
- ודא שהמשתמש במסד הנתונים מורשה להתחבר מהכתובת שלך
- ייתכן שתצטרך להוסיף את כתובת ה-IP שלך לרשימת המורשים ב-MySQL:
  ```sql
  GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'your-ip-address' IDENTIFIED BY 'password';
  FLUSH PRIVILEGES;
  ```

### שגיאת "Connection timeout"
- בדוק אם השרת זמין
- בדוק אם יש firewall שחוסם את החיבור
- נסה להשתמש ב-SSH tunnel אם השרת לא מאפשר חיבורים חיצוניים

## הערות

- הקובץ `config.local.php` לא נשמר ב-git (מופיע ב-.gitignore)
- אם `config.local.php` קיים, הוא יטען במקום `config.php`
- זה מאפשר לך לעבוד לוקאלית עם מסד נתונים בשרת מבלי לשנות את הקונפיגורציה של הפרודקשן













