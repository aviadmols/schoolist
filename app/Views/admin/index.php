<?php
$title = $i18n->t('admin');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('admin'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= htmlspecialchars($i18n->t('admin'), ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="/api/auth/logout" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('logout'), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item"><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/pages" class="menu-item"><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/q-activations" class="menu-item"><?= htmlspecialchars($i18n->t('q_activations'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
            <a href="/admin/logs" class="menu-item">לוגים מערכת</a>
        </div>
        <div class="admin-content">
            <p><?= htmlspecialchars($i18n->t('welcome'), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($i18n->t('admin'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/admin.js"></script>
</body>
</html>

