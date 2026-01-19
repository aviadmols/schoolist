<?php
$title = 'הגדרות SMS';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגדרות SMS</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1>הגדרות SMS</h1>
        <a href="/admin" class="btn btn-secondary">חזרה</a>
        <a href="/api/auth/logout" class="btn btn-secondary">התנתק</a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item">קודי הזמנה</a>
            <a href="/admin/pages" class="menu-item">דפים</a>
            <a href="/admin/q-activations" class="menu-item">הפעלות Q</a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item active">הגדרות SMS</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2>ניהול הגדרות SMS</h2>
                <p>הגדר את פרטי החיבור ל-019sms לשליחת קודי אימות</p>
            </div>
            <form id="smsSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="username">שם משתמש (Username)</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($settings['username'] ?? 'schoolist', ENT_QUOTES, 'UTF-8') ?>" placeholder="הזן שם משתמש" required>
                    <small>שם המשתמש המשויך לטוקן ב-019sms</small>
                </div>

                <div class="form-group">
                    <label for="token">טוקן API (019sms)</label>
                    <input type="password" id="token" name="token" value="<?= htmlspecialchars($settings['token'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="הזן טוקן API" required>
                    <small>הטוקן שקיבלת מ-019sms</small>
                </div>
                
                <div class="form-group">
                    <label for="source">מספר טלפון שולח (Source)</label>
                    <input type="text" id="source" name="source" value="<?= htmlspecialchars($settings['source'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="למשל: 0503222012" required>
                    <small>מספר הטלפון שיופיע כשולח ה-SMS (חייב להיות מאושר ב-019sms)</small>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">שמור הגדרות</button>
                </div>
            </form>
            <div id="message" class="message" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('smsSettingsForm');
            const messageEl = document.getElementById('message');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = {
                    username: formData.get('username'),
                    token: formData.get('token'),
                    source: formData.get('source'),
                    csrf_token: csrfToken
                };

                try {
                    const result = await API.post('/api/admin/sms-settings', data);
                    if (result.ok) {
                        showMessage(messageEl, result.message_he, 'success');
                    } else {
                        showMessage(messageEl, result.message_he || 'שגיאה בשמירת ההגדרות', 'error');
                    }
                } catch (error) {
                    console.error('Error saving SMS settings:', error);
                    showMessage(messageEl, 'שגיאה: ' + error.message, 'error');
                }
            });
        });
    </script>
</body>
</html>
