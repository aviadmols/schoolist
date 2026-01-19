# מדריך העלייה לשרת - Schoolist App

מדריך מפורט לעליית הפרויקט לשרת ייצור.

## 📋 דרישות מוקדמות

### דרישות שרת:
- **PHP 8.3** או גבוה יותר
- **MySQL 5.7+** או **MariaDB 10.3+**
- **Apache** עם **mod_rewrite** מופעל
- **PHP Extensions**:
  - PDO
  - PDO_MySQL
  - curl
  - mbstring
  - json
  - gd (לעיבוד תמונות)

### מידע נדרש:
- ✅ פרטי מסד הנתונים (host, שם DB, משתמש, סיסמה)
- ✅ כתובת URL של האתר (למשל: `https://app.schoolist.co.il`)
- ✅ פרטי SMTP (אם רוצים לשלוח אימיילים)
- ✅ OpenAI API Key (אופציונלי - רק אם רוצים תכונות AI)

---

## 🚀 שלב 1: הכנות לפני העלייה

### 1.1 בדיקת קבצים מקומיים

ודא שהפרויקט עובד מקומית:
```bash
# בדוק שהשרת המקומי רץ
php -S localhost:8000 router.php
```

### 1.2 ניקוי קבצים לא נחוצים

הקבצים הבאים **לא** צריכים לעלות לשרת:
- ❌ `config/config.local.php` (קובץ מקומי בלבד)
- ❌ `test_*.php` (קבצי בדיקה)
- ❌ `admin_helper.php` (קובץ עזר מקומי)
- ❌ `router.php` (רק לפיתוח מקומי)
- ❌ `setup_local_db.ps1` (סקריפט מקומי)
- ❌ `SSH/` (קבצי SSH מקומיים)
- ❌ `Back up.zip` (גיבויים)
- ❌ `logs/*.log` (לוגים ישנים)
- ❌ `storage/ratelimit/*` (קבצי rate limit)

**הערה:** הקבצים האלה כבר ב-`.gitignore`, אז אם אתה משתמש ב-git, הם לא יעלו.

### 1.3 בדיקת .gitignore

ודא ש-`.gitignore` כולל:
```
config/config.local.php
logs/*.log
storage/ratelimit/*
test_*.php
admin_helper.php
```

---

## 📤 שלב 2: העלאת הקבצים לשרת

### 2.1 שיטת העלאה

**אפשרות 1: FTP/SFTP (FileZilla, WinSCP)**
1. התחבר לשרת דרך FTP/SFTP
2. העלה את כל הקבצים לתיקיית ה-web root (למשל: `public_html` או `www`)

**אפשרות 2: Git (מומלץ)**
```bash
# בשרת, clone את ה-repository
git clone <repository-url> .
```

**אפשרות 3: SSH + tar/zip**
```bash
# מקומי - צור ארכיון
tar -czf schoolist.tar.gz --exclude='config/config.local.php' --exclude='logs/*.log' --exclude='storage/ratelimit/*' .

# העלה לשרת דרך SCP
scp schoolist.tar.gz user@server:/path/to/webroot/

# בשרת - חלץ
ssh user@server
cd /path/to/webroot
tar -xzf schoolist.tar.gz
```

### 2.2 מבנה התיקיות בשרת

הקבצים צריכים להיות במבנה הבא:
```
/public_html/  (או www, או תיקיית ה-web root שלך)
├── app/
├── config/
├── database/
├── public/
├── storage/
├── logs/
├── index.php
├── .htaccess
└── ...
```

**חשוב:** ודא ש-`index.php` ו-`.htaccess` נמצאים בתיקיית ה-root של האתר.

---

## 🔐 שלב 3: הגדרת הרשאות

הרץ את הפקודות הבאות בשרת (דרך SSH או cPanel File Manager):

```bash
# הרשאות לתיקיות
chmod 755 config/
chmod 755 public/uploads/
chmod 755 logs/
chmod 755 storage/
chmod 755 storage/ratelimit/

# הרשאות לקבצים
chmod 644 .htaccess
chmod 644 index.php
chmod 644 config/config.php (אחרי שייווצר)

# אם יש לך גישה ל-chown, הגדר את הבעלים:
chown -R www-data:www-data .  # Linux
# או
chown -R apache:apache .       # CentOS/RHEL
```

**הערה:** אם אתה משתמש ב-cPanel, הרשאות 755 בדרך כלל מספיקות.

---

## 🗄️ שלב 4: יצירת מסד הנתונים

### 4.1 דרך cPanel (מומלץ)

1. התחבר ל-cPanel
2. לחץ על **"MySQL Databases"**
3. צור מסד נתונים חדש (למשל: `schoolist_db`)
4. צור משתמש חדש (למשל: `schoolist_user`)
5. הקצה את המשתמש למסד הנתונים עם הרשאות מלאות
6. **שמור את הפרטים** - תצטרך אותם בשלב הבא

### 4.2 דרך phpMyAdmin

1. פתח **phpMyAdmin** מ-cPanel
2. לחץ על **"New"** (מסד נתונים חדש)
3. הזן שם (למשל: `schoolist_db`)
4. בחר **Collation**: `utf8mb4_unicode_ci`
5. לחץ **"Create"**

### 4.3 ייבוא הסכמה (Schema)

**אפשרות 1: דרך phpMyAdmin**
1. פתח את מסד הנתונים שיצרת
2. לחץ על **"Import"**
3. בחר את הקובץ `database/schema.sql`
4. לחץ **"Go"**

**אפשרות 2: דרך SSH**
```bash
mysql -u schoolist_user -p schoolist_db < database/schema.sql
```

**הערה:** אם אתה משתמש ב-prefix שונה מ-`sl_`, עדכן את הקובץ `schema.sql` לפני הייבוא.

---

## ⚙️ שלב 5: הרצת Setup Wizard

### 5.1 גישה ל-Setup Wizard

פתח בדפדפן:
```
https://your-domain.com/setup
```

אם האתר בתת-תיקייה:
```
https://your-domain.com/subfolder/setup
```

### 5.2 תהליך ההתקנה

התקן את השלבים הבאים:

#### שלב 1: בדיקת דרישות
- המערכת תבדוק את גרסת PHP וההרחבות הנדרשות
- אם משהו חסר, תקבל הודעה

#### שלב 2: הגדרת מסד נתונים
הזן את הפרטים:
- **DB Host**: בדרך כלל `localhost` (או כתובת IP של MySQL)
- **DB Name**: שם מסד הנתונים שיצרת
- **DB User**: שם המשתמש
- **DB Password**: הסיסמה
- **Table Prefix**: `sl_` (או prefix אחר אם צריך)

#### שלב 3: הגדרת Base URL
הזן את כתובת האתר המלאה:
```
https://app.schoolist.co.il/
```
**חשוב:** הוסף `/` בסוף!

#### שלב 4: הגדרת Email (SMTP)
אם יש לך פרטי SMTP:
- **SMTP Enabled**: כן
- **SMTP Host**: `smtp.example.com`
- **SMTP Port**: `587` (או `465` ל-SSL)
- **SMTP User**: כתובת האימייל
- **SMTP Password**: סיסמת האימייל
- **From Email**: `noreply@schoolist.co.il`
- **From Name**: `Schoolist`

אם אין SMTP, השאר את זה כבוי - המערכת תשתמש ב-`mail()` של PHP.

#### שלב 5: סיום והגדרת Admin
- **OpenAI API Key**: (אופציונלי) אם יש לך
- **Admin Email**: כתובת האימייל של המנהל הראשי

לאחר סיום, המערכת:
1. תיצור את כל הטבלאות
2. תכתוב את `config/config.php`
3. תיצור את משתמש המנהל הראשי
4. תיצור את `setup.lock` (כדי למנוע התקנה חוזרת)

---

## ✅ שלב 6: בדיקות

### 6.1 בדיקת התקנה

1. **בדוק שההתקנה הסתיימה:**
   ```
   https://your-domain.com/setup
   ```
   צריך להציג: "המערכת כבר מותקנת"

2. **בדוק התחברות:**
   ```
   https://your-domain.com/login
   ```
   התחבר עם האימייל שהזנת בשלב 5

3. **בדוק Admin Panel:**
   ```
   https://your-domain.com/admin
   ```

4. **בדוק דף ציבורי:**
   ```
   https://your-domain.com/p/123456
   ```
   (תצטרך ליצור דף קודם דרך ה-Admin Panel)

### 6.2 בדיקת הרשאות

ודא שהתיקיות הבאות ניתנות לכתיבה:
- `public/uploads/` - להעלאת קבצים
- `logs/` - ללוגים
- `storage/ratelimit/` - ל-rate limiting

אם יש שגיאות, בדוק את הרשאות התיקיות.

### 6.3 בדיקת מסד נתונים

פתח phpMyAdmin ובדוק:
- כל הטבלאות נוצרו (עם ה-prefix שבחרת)
- אין שגיאות בטבלאות

---

## 🔒 שלב 7: אבטחה

### 7.1 הגנה על קבצים רגישים

ודא ש-`.htaccess` כולל:
```apache
<FilesMatch "^(config\.php|\.env|setup\.lock)$">
    Require all denied
</FilesMatch>
```

### 7.2 מחיקת קבצים לא נחוצים

לאחר ההתקנה, מחק (אם קיימים):
- `admin_helper.php`
- `test_*.php`
- `router.php` (רק לפיתוח מקומי)

### 7.3 עדכון BASE_URL

ודא שב-`config/config.php` ה-BASE_URL נכון:
```php
define('BASE_URL', 'https://your-domain.com/');
```

### 7.4 בדיקת HTTPS

ודא שהאתר רץ על HTTPS (לא HTTP). זה חשוב לאבטחה.

---

## 🐛 פתרון בעיות נפוצות

### בעיה: "500 Internal Server Error"

**פתרונות:**
1. בדוק את `logs/app.log` לשגיאות
2. ודא שהרשאות התיקיות נכונות (755)
3. בדוק ש-`.htaccess` קיים ופועל
4. בדוק את פרטי מסד הנתונים ב-`config/config.php`

### בעיה: "Database Connection Failed"

**פתרונות:**
1. ודא שפרטי מסד הנתונים נכונים
2. בדוק שהמסד נתונים קיים
3. בדוק שהמשתמש יש לו הרשאות
4. אם השרת מרוחק, בדוק את ה-hostname (לפעמים זה לא `localhost`)

### בעיה: "mod_rewrite not working"

**פתרונות:**
1. ודא ש-`.htaccess` קיים
2. בדוק ש-mod_rewrite מופעל בשרת
3. אם זה לא עובד, עדכן את ה-`.htaccess`:
   ```apache
   RewriteBase /subfolder/  # אם האתר בתת-תיקייה
   ```

### בעיה: "Cannot write to config/config.php"

**פתרונות:**
1. בדוק הרשאות: `chmod 755 config/`
2. ודא שהקובץ לא read-only
3. אם צריך, צור את הקובץ ידנית והעתק את התוכן

### בעיה: "OTP emails not sending"

**פתרונות:**
1. בדוק את הגדרות SMTP
2. נסה עם `mail()` של PHP (כבה SMTP)
3. בדוק את תיבת הספאם
4. בדוק את לוגי השרת

---

## 📝 רשימת בדיקה סופית

לפני שאתה מסיים, ודא:

- [ ] כל הקבצים עלו לשרת
- [ ] הרשאות התיקיות נכונות (755)
- [ ] מסד הנתונים נוצר ומיובא
- [ ] Setup Wizard הושלם בהצלחה
- [ ] `config/config.php` נוצר
- [ ] `setup.lock` קיים
- [ ] התחברות לאתר עובדת
- [ ] Admin Panel נגיש
- [ ] העלאת קבצים עובדת (`public/uploads/`)
- [ ] אימיילים נשלחים (אם הוגדר SMTP)
- [ ] HTTPS פעיל
- [ ] קבצים רגישים מוגנים

---

## 🎉 סיום

אם כל הבדיקות עברו, המערכת מוכנה לשימוש!

**צעדים הבאים:**
1. התחבר כ-System Admin
2. צור קודי הזמנה (Invitation Codes)
3. צור דפי כיתות
4. התחל להשתמש במערכת!

---

## 📞 תמיכה

אם נתקלת בבעיות:
1. בדוק את `logs/app.log`
2. בדוק את לוגי השרת
3. בדוק את הגדרות cPanel/Apache
4. פנה לתמיכה טכנית

**בהצלחה! 🚀**


