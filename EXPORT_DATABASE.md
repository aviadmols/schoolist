# מדריך ייצוא מסד נתונים - Schoolist

## שיטה 1: דרך phpMyAdmin (הכי קל) ⭐

### שלבים:

1. **התחבר ל-phpMyAdmin** בשרת שלך
   - דרך cPanel: לחץ על "phpMyAdmin"
   - או גש ישירות: `https://your-domain.com/phpMyAdmin`

2. **בחר את מסד הנתונים**
   - בצד שמאל, לחץ על `appsxhi6_Schoolist` (או שם מסד הנתונים שלך)

3. **ייצוא**
   - לחץ על הטאב **"Export"** (ייצוא) בחלק העליון
   - בחר **"Quick"** (מהיר) או **"Custom"** (מותאם אישית)

4. **הגדרות ייצוא (אם בחרת Custom):**
   - **Format**: בחר `SQL`
   - **Structure**: סמן ✓ (מבנה הטבלאות)
   - **Data**: סמן ✓ (הנתונים)
   - **Add DROP TABLE**: סמן ✓ (אם רוצה למחוק טבלאות קיימות בעת ייבוא)
   - **Add CREATE DATABASE**: סמן ✓ (אם רוצה ליצור את מסד הנתונים בעת ייבוא)

5. **לחץ על "Go"** (המשך)
   - הקובץ יורד אוטומטית (שם: `appsxhi6_Schoolist.sql`)

---

## שיטה 2: דרך SSH (למתקדמים)

### פקודת mysqldump:

```bash
mysqldump -u your_username -p appsxhi6_Schoolist > backup.sql
```

**פירוט:**
- `your_username` - שם המשתמש של MySQL
- `appsxhi6_Schoolist` - שם מסד הנתונים
- `backup.sql` - שם קובץ הגיבוי

**דוגמה:**
```bash
mysqldump -u appsxhi6_user -p appsxhi6_Schoolist > schoolist_backup_2025.sql
```

**אם צריך גם ליצור את מסד הנתונים:**
```bash
mysqldump -u your_username -p --databases appsxhi6_Schoolist > backup_with_db.sql
```

**אם רוצה לדחוס את הקובץ:**
```bash
mysqldump -u your_username -p appsxhi6_Schoolist | gzip > backup.sql.gz
```

---

## שיטה 3: דרך cPanel (אם יש)

1. התחבר ל-**cPanel**
2. לחץ על **"Backup"** או **"Backup Wizard"**
3. בחר **"Download a MySQL Database Backup"**
4. בחר את מסד הנתונים `appsxhi6_Schoolist`
5. לחץ **"Generate Backup"**
6. הקובץ יורד אוטומטית

---

## מה הקובץ מכיל?

הקובץ שיוצא (`.sql`) יכיל:
- ✅ כל הטבלאות עם המבנה שלהן
- ✅ כל הנתונים (users, pages, blocks, announcements, וכו')
- ✅ אינדקסים
- ✅ Foreign keys
- ✅ הגדרות charset

---

## טיפים חשובים:

1. **גודל הקובץ**: אם מסד הנתונים גדול, הקובץ יכול להיות גדול (MB רבים)
2. **זמן ייצוא**: ייצוא גדול יכול לקחת כמה דקות
3. **גיבוי**: שמור את הקובץ במקום בטוח!
4. **דחיסה**: אם הקובץ גדול, דחוס אותו (ZIP) לפני הורדה

---

## ייבוא חזרה (אם צריך):

### דרך phpMyAdmin:
1. בחר את מסד הנתונים
2. לחץ על **"Import"**
3. בחר את קובץ ה-SQL
4. לחץ **"Go"**

### דרך SSH:
```bash
mysql -u your_username -p appsxhi6_Schoolist < backup.sql
```

---

## בדיקה שהקובץ תקין:

אחרי הייצוא, פתח את הקובץ (בתוכנת עריכת טקסט) ובדוק:
- יש `CREATE TABLE` statements
- יש `INSERT INTO` statements
- הקובץ לא ריק

**בהצלחה! 🎉**


