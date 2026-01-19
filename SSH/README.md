# חיבור SSH

## שימוש

### אפשרות 1: שימוש בסקריפט PowerShell
```powershell
.\connect.ps1 -Host "your-server.com" -User "username" -Port 22
```

### אפשרות 2: חיבור ישיר
```powershell
ssh username@your-server.com
```

### אפשרות 3: חיבור עם מפתח SSH
```powershell
.\connect.ps1 -Host "your-server.com" -User "username" -KeyPath "C:\path\to\your\key.pem"
```

## עריכת הפרטים

ערוך את הקובץ `connect.ps1` ושנה את הערכים הבאים:
- `$Host` - כתובת השרת או שם הדומיין
- `$User` - שם המשתמש
- `$Port` - פורט SSH (ברירת מחדל: 22)
- `$KeyPath` - נתיב למפתח SSH (אופציונלי)






