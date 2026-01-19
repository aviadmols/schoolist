<?php
$title = $i18n->t('login');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('login'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <img src="/public/assets/files/logo.svg" alt="<?= htmlspecialchars($i18n->t('app_name'), ENT_QUOTES, 'UTF-8') ?>" style="max-width: 200px; height: auto; margin-bottom: 1.5rem; display: block; margin-left: auto; margin-right: auto;">
        <?php if (!empty($invitation_code ?? '')): ?>
            <div class="invitation-notice" style="background: #E8F4FD; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #0C4A6E;">
                <strong>קוד הזמנה: <?= htmlspecialchars($invitation_code, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>
        <form id="loginForm" class="auth-form">
            <div class="form-group">
                <label for="email"><?= htmlspecialchars($i18n->t('email'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" id="email" name="email" required placeholder="<?= htmlspecialchars($i18n->t('enter_email'), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <?php if (!empty($invitation_code ?? '')): ?>
                <div class="form-group">
                    <label for="code">קוד הזמנה</label>
                    <input type="text" id="code" name="code" required placeholder="הזן קוד הזמנה" value="<?= htmlspecialchars($invitation_code ?? '', ENT_QUOTES, 'UTF-8') ?>" style="text-transform: uppercase;">
                </div>
                <button type="submit" class="btn btn-primary">התחבר עם קוד</button>
            <?php else: ?>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($i18n->t('request_otp'), ENT_QUOTES, 'UTF-8') ?></button>
            <?php endif; ?>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        const invitationCode = '<?= htmlspecialchars($invitation_code ?? '', ENT_QUOTES, 'UTF-8') ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/auth.js"></script>
</body>
</html>

