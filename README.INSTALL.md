# הוראות התקנה מהירה - Schoolist App

## התקנה מהירה (5 דקות)

### 1. העתק קבצים לשרת
העתק את כל קבצי הפרויקט לשרת שלך.

### 2. צור קובץ קונפיגורציה
```bash
cp config/config.local.php.example config/config.local.php
```

ערוך את `config/config.local.php` ועדכן:
- `DB_HOST` - כתובת שרת מסד הנתונים
- `DB_NAME` - שם מסד הנתונים  
- `DB_USER` - שם משתמש
- `DB_PASS` - סיסמה
- `DB_PREFIX` - קידומת (בדרך כלל `sl_`)

### 3. צור מסד נתונים
```sql
CREATE DATABASE your_database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. הרץ התקנה
פתח בדפדפן: `http://your-domain.com/install.php`

או בשורת פקודה:
```bash
php install.php
```

### 5. הגדר הרשאות
```bash
chmod -R 755 .
chmod -R 777 logs/ storage/ public/uploads/
```

### 6. בדוק התקנה
פתח את האתר - אם זו התקנה ראשונה, תועבר לעמוד ההתקנה.

---

## רשימת קבצים להעתקה

העתק את כל התיקיות והקבצים הבאים:

```
app/
config/
database/
public/
index.php
router.php
install.php
.htaccess
```

**אל תעתיק:**
- `logs/*.log` (אבל כן את התיקייה)
- `storage/ratelimit/*.json` (אבל כן את התיקייה)
- `config/config.local.php` (צור אותו מחדש בשרת)

---

## עדכון גרסה קיימת

1. **גבה מסד נתונים:**
   ```bash
   mysqldump -u user -p database_name > backup.sql
   ```

2. **שמור קובץ קונפיגורציה:**
   ```bash
   cp config/config.local.php /tmp/config_backup.php
   ```

3. **העתק קבצים חדשים**

4. **החזר קובץ קונפיגורציה:**
   ```bash
   cp /tmp/config_backup.php config/config.local.php
   ```

5. **הרץ התקנה:**
   ```bash
   php install.php
   ```

---

## פתרון בעיות

### שגיאת חיבור למסד נתונים
- ודא שפרטי החיבור נכונים
- ודא שמסד הנתונים קיים
- ודא הרשאות למשתמש

### שגיאת הרשאות
```bash
chmod -R 755 .
chmod -R 777 logs/ storage/ public/uploads/
chown -R www-data:www-data .
```

### שגיאת mod_rewrite (Apache)
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

לפרטים נוספים, ראה `INSTALL.md` או `DEPLOY.md`

