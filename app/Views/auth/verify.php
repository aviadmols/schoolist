<?php
$title = $i18n->t('verify');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('verify'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1><?= htmlspecialchars($i18n->t('verify'), ENT_QUOTES, 'UTF-8') ?></h1>
        <form id="verifyForm" class="auth-form">
            <input type="hidden" id="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="code"><?= htmlspecialchars($i18n->t('otp_code'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" id="code" name="code" required maxlength="6" pattern="[0-9]{6}" placeholder="<?= htmlspecialchars($i18n->t('enter_otp'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($i18n->t('verify'), ENT_QUOTES, 'UTF-8') ?></button>
            <div style="margin-top: 1rem; text-align: center;">
                <button type="button" id="resendOtpBtn" class="btn btn-secondary" style="background: none; border: none; color: #0C4A6E; text-decoration: underline; box-shadow: none; padding: 0;">שלח קוד מחדש</button>
            </div>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/auth.js"></script>
</body>
</html>

