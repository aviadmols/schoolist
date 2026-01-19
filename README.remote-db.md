# חיבור למסד נתונים מרוחק ב-uPress

## הבעיה

uPress (כמו רוב ה-hosting providers) לא מאפשר חיבורים חיצוניים ישירים ל-MySQL מסיבות אבטחה. זה אומר שאתה לא יכול להתחבר למסד הנתונים מהמחשב המקומי שלך ישירות.

## פתרונות

### פתרון 1: SSH Tunnel (מומלץ)

השתמש ב-SSH tunnel כדי ליצור חיבור מאובטח דרך SSH:

#### Windows (PowerShell):

```powershell
# התקן plink אם עדיין לא התקנת
# או השתמש ב-PuTTY

# צור SSH tunnel
plink -ssh username@v-il-171-0k1.upress.io -L 3307:localhost:3306 -N

# או עם PuTTY:
# Connection > SSH > Tunnels
# Source port: 3307
# Destination: localhost:3306
# לחץ Add, ואז Open
```

ואז עדכן את `config/config.local.php`:
```php
define('DB_HOST', '127.0.0.1'); // או 'localhost'
define('DB_PORT', '3307'); // הפורט של ה-tunnel
```

**הערה**: אם אתה משתמש ב-PDO, ייתכן שתצטרך לעדכן את ה-DSN:
```php
$dsn = "mysql:host=127.0.0.1;port=3307;dbname=...";
```

### פתרון 2: בדוק את קובץ ה-config בשרת

אם יש לך גישה ל-`config/config.php` בשרת, בדוק מה ה-`DB_HOST` שם. לפעמים זה `localhost` אבל רק כשמתחברים מהשרת עצמו.

### פתרון 3: השתמש ב-phpMyAdmin

אם יש לך גישה ל-phpMyAdmin (https://v-il-171-0k1.upress.io/fI4ahb5amIwGzMV4Pj3VMpKf5H/), תוכל:
1. לראות את פרטי החיבור שם
2. לבדוק אם יש אפשרות לפתוח חיבורים חיצוניים

### פתרון 4: עבוד ישירות על השרת

אם יש לך גישה ל-SSH לשרת, תוכל:
1. לעבוד ישירות על השרת
2. או להשתמש ב-SSH tunnel (פתרון 1)

## בדיקת פרטי החיבור

אם יש לך גישה ל-`config/config.php` בשרת, בדוק שם את הערכים:
- `DB_HOST` - זה ה-hostname הנכון
- `DB_NAME` - שם מסד הנתונים
- `DB_USER` - שם משתמש
- `DB_PASS` - סיסמה

## הערות חשובות

1. **אבטחה**: לעולם אל תעלה את `config.local.php` ל-git (הוא כבר ב-.gitignore)
2. **SSH Tunnel**: זה הפתרון הבטוח ביותר לחיבור למסד נתונים מרוחק
3. **Firewall**: גם אם תפתח את הפורט, ייתכן שה-firewall של uPress עדיין יחסום

## צעדים הבאים

1. נסה ליצור SSH tunnel (פתרון 1)
2. או בדוק את קובץ ה-config בשרת כדי לראות את ה-hostname הנכון
3. אם יש לך גישה ל-cPanel/uPress panel, בדוק אם יש אפשרות לפתוח חיבורים חיצוניים













