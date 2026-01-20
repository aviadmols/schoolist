<?php
$title = 'כניסת מנהל מערכת';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>כניסת מנהל מערכת</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <img src="/public/assets/files/logo.svg" alt="Schoolist" style="max-width: 200px; height: auto; margin-bottom: 1.5rem; display: block; margin-left: auto; margin-right: auto;">
        <h1>כניסת מנהל מערכת</h1>
        <p style="margin-bottom: 1rem;">הכניסו את שם המשתמש והסיסמה הסודיים של המנהל (מוגדרים בקובץ הקונפיג).</p>
        <form id="adminMasterLoginForm" class="auth-form">
            <div class="form-group">
                <label for="identifier">אימייל או טלפון מנהל</label>
                <input type="text" id="identifier" name="identifier" required placeholder="למשל: <?= htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="password">סיסמת מנהל (Master Code)</label>
                <input type="password" id="password" name="password" required placeholder="קוד מנהל סודי">
            </div>
            <button type="submit" class="btn btn-primary">התחברות לאדמין</button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('adminMasterLoginForm');
            const messageEl = document.getElementById('message');

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const identifier = document.getElementById('identifier').value.trim();
                const password = document.getElementById('password').value;

                if (!identifier || !password) return;

                try {
                    const result = await API.post('/api/auth/admin-master-login', {
                        identifier,
                        password,
                        csrf_token: csrfToken
                    });

                    if (result.ok) {
                        if (result.token) {
                            localStorage.setItem('auth_token', result.token);
                        }
                        if (typeof showMessage === 'function') {
                            showMessage(messageEl, result.message_he || 'התחברת בהצלחה!', 'success');
                        } else {
                            messageEl.textContent = result.message_he || 'התחברת בהצלחה!';
                            messageEl.className = 'message success';
                        }
                        setTimeout(() => {
                            window.location.href = result.redirect || '/admin';
                        }, 1000);
                    } else {
                        if (typeof showMessage === 'function') {
                            showMessage(messageEl, result.message_he || 'שם משתמש או סיסמה לא נכונים', 'error');
                        } else {
                            messageEl.textContent = result.message_he || 'שם משתמש או סיסמה לא נכונים';
                            messageEl.className = 'message error';
                        }
                    }
                } catch (error) {
                    if (typeof showMessage === 'function') {
                        showMessage(messageEl, error.message || 'שגיאה בהתחברות', 'error');
                    } else {
                        messageEl.textContent = error.message || 'שגיאה בהתחברות';
                        messageEl.className = 'message error';
                    }
                }
            });
        });
    </script>
</body>
</html>

