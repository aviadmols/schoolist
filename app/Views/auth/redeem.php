<?php
$title = 'פדיון קוד הזמנה';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>פדיון קוד הזמנה</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>פדיון קוד הזמנה</h1>
        <p>הזן את קוד ההזמנה שקיבלת ממנהל המערכת</p>
        <form id="redeemForm" class="auth-form">
            <div class="form-group">
                <label for="code">קוד הזמנה</label>
                <input type="text" id="code" name="code" required placeholder="ABCD1234" style="text-transform: uppercase;">
            </div>
            <button type="submit" class="btn btn-primary">פדה קוד</button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const redeemForm = document.getElementById('redeemForm');
            if (redeemForm) {
                redeemForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const code = document.getElementById('code').value.toUpperCase();
                    const messageEl = document.getElementById('message');

                    try {
                        const result = await API.post('api/auth/redeem-invitation', { code });
                        if (result.ok) {
                            showMessage(messageEl, result.message_he, 'success');
                            setTimeout(() => {
                                window.location.href = result.redirect || '/dashboard';
                            }, 1500);
                        }
                    } catch (error) {
                        showMessage(messageEl, error.message, 'error');
                    }
                });
            }
        });
    </script>
</body>
</html>

