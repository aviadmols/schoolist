# סקריפט חיבור SSH
# שנה את הפרטים הבאים לפי הצורך

param(
    [Parameter(Mandatory=$false)]
    [string]$Host = "v-il-171-0k1.upress.io",
    
    [Parameter(Mandatory=$false)]
    [string]$User = "appsxhi6",
    
    [Parameter(Mandatory=$false)]
    [int]$Port = 22,
    
    [Parameter(Mandatory=$false)]
    [string]$KeyPath = "",
    
    [Parameter(Mandatory=$false)]
    [SecureString]$Password = (ConvertTo-SecureString "1Yb4s8dbaBvgwcRd7bn1" -AsPlainText -Force)
)

Write-Host "מתחבר לשרת SSH..." -ForegroundColor Cyan
Write-Host "שרת: $Host" -ForegroundColor Yellow
Write-Host "משתמש: $User" -ForegroundColor Yellow
Write-Host "פורט: $Port" -ForegroundColor Yellow

# בדיקה אם יש מפתח SSH
if ($KeyPath -ne "" -and (Test-Path $KeyPath)) {
    Write-Host "משתמש במפתח SSH: $KeyPath" -ForegroundColor Green
    ssh -i $KeyPath -p $Port $User@$Host
} else {
    if ($KeyPath -ne "") {
        Write-Host "אזהרה: קובץ המפתח לא נמצא, מתחבר עם סיסמה" -ForegroundColor Yellow
    }
    
    # המרת SecureString לסיסמה רגילה לשימוש ב-Plink
    $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($Password)
    $plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
    
    # בדיקה אם Plink זמין (מ-PuTTY) - הדרך הכי טובה לחיבור עם סיסמה ב-Windows
    $plinkPath = Get-Command plink -ErrorAction SilentlyContinue
    if ($plinkPath) {
        Write-Host "משתמש ב-Plink לחיבור עם סיסמה" -ForegroundColor Green
        Write-Output "y" | plink -ssh -P $Port -pw $plainPassword $User@$Host
    } else {
        # אם Plink לא זמין, נשתמש ב-SSH רגיל (תתבקש להזין סיסמה ידנית)
        Write-Host "Plink לא נמצא. מתחבר עם SSH רגיל..." -ForegroundColor Yellow
        Write-Host "תתבקש להזין את הסיסמה ידנית" -ForegroundColor Yellow
        Write-Host "סיסמה: $plainPassword" -ForegroundColor Cyan
        ssh -p $Port $User@$Host
    }
}

