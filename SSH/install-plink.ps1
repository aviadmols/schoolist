# סקריפט להתקנת Plink (מ-PuTTY) לחיבור SSH עם סיסמה
# Plink מאפשר חיבור אוטומטי עם סיסמה ב-Windows

Write-Host "בודק אם Plink מותקן..." -ForegroundColor Cyan

$plinkPath = Get-Command plink -ErrorAction SilentlyContinue
if ($plinkPath) {
    Write-Host "Plink כבר מותקן!" -ForegroundColor Green
    Write-Host "מיקום: $($plinkPath.Source)" -ForegroundColor Yellow
} else {
    Write-Host "Plink לא מותקן." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "אפשרויות התקנה:" -ForegroundColor Cyan
    Write-Host "1. הורד PuTTY מ: https://www.putty.org/" -ForegroundColor White
    Write-Host "2. או התקן דרך Chocolatey: choco install putty" -ForegroundColor White
    Write-Host "3. או התקן דרך winget: winget install PuTTY.PuTTY" -ForegroundColor White
    Write-Host ""
    
    $install = Read-Host "האם תרצה להתקין דרך winget? (y/n)"
    if ($install -eq "y" -or $install -eq "Y") {
        Write-Host "מתקין Plink דרך winget..." -ForegroundColor Cyan
        winget install PuTTY.PuTTY
    }
}






