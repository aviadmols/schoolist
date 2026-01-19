# התקנת מסד נתונים מקומי

מדריך זה מסביר איך להתקין ולהגדיר מסד נתונים MySQL מקומי לפיתוח.

## אפשרויות התקנה

### אפשרות 1: XAMPP (מומלץ - הכי קל)

1. הורד והתקן XAMPP מ: https://www.apachefriends.org/download.html
2. הפעל את XAMPP Control Panel
3. הפעל את שירות MySQL (לחץ על "Start")
4. MySQL יהיה זמין ב-`localhost` עם משתמש `root` (ללא סיסמה כברירת מחדל)

### אפשרות 2: WAMP

1. הורד והתקן WAMP מ: https://www.wampserver.com/
2. הפעל את WAMP
3. לחץ על האייקון ב-system tray ובחר "Start All Services"
4. MySQL יהיה זמין ב-`localhost` עם משתמש `root` (ללא סיסמה כברירת מחדל)

### אפשרות 3: MySQL Community Server

1. הורד MySQL מ: https://dev.mysql.com/downloads/mysql/
2. התקן את MySQL (בחר "Developer Default" או "Server only")
3. הגדר סיסמה ל-root במהלך ההתקנה
4. MySQL יהיה זמין ב-`localhost`

## הגדרת מסד הנתונים

### שיטה 1: שימוש בסקריפט האוטומטי (מומלץ)

הרץ את הסקריפט PowerShell:

```powershell
.\setup_local_db.ps1
```

הסקריפט יבצע:
- בדיקה אם MySQL מותקן
- יצירת מסד נתונים חדש
- ייבוא הסכמה (schema) מהקובץ `database/schema.sql`
- עדכון אוטומטי של `config/config.local.php`

### שיטה 2: הגדרה ידנית

1. **צור מסד נתונים**:
   ```sql
   CREATE DATABASE schoolist_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **ייבא את הסכמה**:
   ```bash
   mysql -u root -p schoolist_local < database/schema.sql
   ```

3. **עדכן את `config/config.local.php`**:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'schoolist_local');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // או הסיסמה שלך
   define('DB_PREFIX', 'sl_');
   ```

## בדיקת החיבור

לאחר ההגדרה, בדוק את החיבור:

```bash
php test_db.php
```

או פשוט הרץ את האפליקציה:

```bash
php -S localhost:8000 -t .
```

ואז פתח בדפדפן: `http://localhost:8000`

## העתקת נתונים מהשרת (אופציונלי)

אם אתה רוצה להעתיק נתונים מהשרת למסד הנתונים המקומי:

### דרך phpMyAdmin:

1. התחבר ל-phpMyAdmin בשרת
2. בחר את מסד הנתונים
3. לחץ על "Export" (ייצוא)
4. שמור את הקובץ
5. התחבר ל-phpMyAdmin המקומי (אם יש)
6. בחר את מסד הנתונים המקומי
7. לחץ על "Import" (ייבוא) ובחר את הקובץ

### דרך שורת פקודה:

```bash
# ייצוא מהשרת (אם יש לך גישה)
mysqldump -u username -p database_name > backup.sql

# ייבוא למקומי
mysql -u root -p schoolist_local < backup.sql
```

## פתרון בעיות

### שגיאת "Access denied"

- ודא שהסיסמה נכונה
- אם זה XAMPP/WAMP, בדרך כלל אין סיסמה (השאר ריק)
- אם זה MySQL Community, השתמש בסיסמה שהגדרת בהתקנה

### שגיאת "Can't connect to MySQL server"

- ודא ש-MySQL פועל (בדוק ב-XAMPP/WAMP Control Panel)
- נסה `localhost` במקום `127.0.0.1`
- בדוק את הפורט (ברירת מחדל: 3306)

### שגיאת "Unknown database"

- ודא שיצרת את מסד הנתונים
- בדוק את שם מסד הנתונים ב-`config.local.php`

## הערות

- מסד הנתונים המקומי הוא רק לפיתוח
- הנתונים לא מסונכרנים עם השרת
- אם אתה צריך נתונים מהשרת, תצטרך לייבא אותם ידנית













