# תיקון בעיות התחברות

## בעיה 1: logo.svg לא נמצא (404)

**הסיבה:** הקובץ מחפש ב-`/assets/files/logo.svg` אבל צריך להיות `/public/assets/files/logo.svg`

**פתרון:**

### אופציה 1: עדכן את הנתיב ב-login.php

פתח את `app/Views/auth/login.php` ושורה 15 שנה מ:
```php
<img src="/assets/files/logo.svg"
```
ל:
```php
<img src="/public/assets/files/logo.svg"
```

### אופציה 2: ודא ש-.htaccess עובד

ודא שהקובץ `.htaccess` קיים ב-root של האתר ושהוא עובד. אם לא, הוסף את זה ל-`.htaccess`:

```apache
# Serve assets directly
RewriteCond %{REQUEST_URI} ^/assets/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^assets/(.*)$ /public/assets/$1 [L]
```

---

## בעיה 2: שגיאת רשת בהתחברות

**הסיבה:** BASE_URL לא מוגדר נכון או שהנתיבים לא נכונים

**פתרונות:**

### 1. בדוק את config.php

פתח את `config/config.php` בשרת ובדוק ש-BASE_URL מוגדר נכון:

```php
define('BASE_URL', 'https://app.schoolist.co.il/');
```

**חשוב:**
- חייב להיות עם `https://` (לא `http://`)
- חייב להיות עם `/` בסוף
- חייב להיות הכתובת המלאה של האתר

### 2. בדוק את Console בדפדפן

פתח את Developer Tools (F12) → Console ותראה מה ה-URL שהמערכת מנסה לגשת אליו.

אם אתה רואה משהו כמו:
```
API Request: { url: '/api/auth/request-otp', baseUrl: 'http://localhost:8000/', fullUrl: 'http://localhost:8000/api/auth/request-otp' }
```

זה אומר ש-BASE_URL לא נכון בשרת.

### 3. בדוק Network Tab

פתח Developer Tools → Network → לחץ על "Login" → בדוק מה ה-URL של הבקשה.

אם הבקשה הולכת ל-`http://localhost:8000/...` במקום `https://app.schoolist.co.il/...`, זה אומר ש-BASE_URL לא נכון.

### 4. תיקון מהיר - עדכן את config.php

אם BASE_URL לא נכון, עדכן אותו:

```php
// בשרת, פתח config/config.php
define('BASE_URL', 'https://app.schoolist.co.il/'); // עדכן לכתובת הנכונה
```

### 5. אם עדיין לא עובד - בדוק את bootstrap.php

אם BASE_URL לא מוגדר ב-config.php, המערכת מנסה לזהות אותו אוטומטית. זה יכול לגרום לבעיות.

**פתרון:** תמיד הגדר BASE_URL ב-config.php במפורש.

---

## בדיקות מהירות

### בדיקה 1: האם logo.svg נגיש?

פתח בדפדפן:
```
https://app.schoolist.co.il/public/assets/files/logo.svg
```

אם זה עובד - הבעיה היא בנתיב ב-login.php
אם זה לא עובד - הקובץ לא עלה לשרת

### בדיקה 2: האם BASE_URL נכון?

פתח את `config/config.php` ובדוק:
```php
echo BASE_URL; // צריך להדפיס: https://app.schoolist.co.il/
```

### בדיקה 3: בדוק Console בדפדפן

פתח את דף ההתחברות → F12 → Console → לחץ "התחבר" → ראה מה מודפס:

אם אתה רואה:
```
API Request: { baseUrl: 'http://localhost:8000/', ... }
```

זה אומר ש-BASE_URL לא נכון.

---

## פתרון מהיר - סקריפט בדיקה

צור קובץ `check_config.php` ב-root ופתח אותו בדפדפן:

```php
<?php
require_once __DIR__ . '/app/bootstrap.php';

echo "<h1>Configuration Check</h1>";
echo "<p><strong>BASE_URL:</strong> " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "</p>";
echo "<p><strong>DB_HOST:</strong> " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</p>";
echo "<p><strong>DB_NAME:</strong> " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "</p>";

echo "<h2>Asset Paths</h2>";
echo "<p>Logo exists: " . (file_exists(__DIR__ . '/public/assets/files/logo.svg') ? 'YES' : 'NO') . "</p>";
echo "<p>Logo path: " . __DIR__ . '/public/assets/files/logo.svg' . "</p>";

echo "<h2>Test URLs</h2>";
echo "<p><a href='" . BASE_URL . "public/assets/files/logo.svg'>Test Logo</a></p>";
echo "<p><a href='" . BASE_URL . "api/auth/request-otp'>Test API (will fail but shows URL)</a></p>";
?>
```

**חשוב:** מחק את הקובץ אחרי הבדיקה!

---

## סיכום - צעדים לתיקון

1. ✅ בדוק ש-`config/config.php` קיים ומוגדר נכון
2. ✅ ודא ש-BASE_URL מוגדר: `https://app.schoolist.co.il/`
3. ✅ עדכן את `login.php` - שנה `/assets/files/logo.svg` ל-`/public/assets/files/logo.svg`
4. ✅ בדוק ש-`.htaccess` קיים ופועל
5. ✅ בדוק ש-`logo.svg` קיים ב-`public/assets/files/logo.svg`
6. ✅ נסה להתחבר שוב

**אם עדיין לא עובד:**
- פתח Console בדפדפן (F12) ובדוק מה השגיאות
- בדוק את Network tab - איזה URL הבקשה הולכת אליו
- בדוק את `logs/app.log` בשרת


