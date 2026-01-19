<?php
$title = $i18n->t('setup');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('setup'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/setup.css">
</head>
<body class="setup-page">
    <div class="setup-container">
        <h1><?= htmlspecialchars($i18n->t('setup_welcome'), ENT_QUOTES, 'UTF-8') ?></h1>
        <div id="setup-wizard">
            <div class="setup-step active">
                <h2>שלב 1: בדיקת דרישות</h2>
                <div id="requirements-check">
                    <p>טוען...</p>
                </div>
                <div class="setup-actions">
                    <button class="btn btn-primary" onclick="checkRequirements()">בדוק דרישות</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
        window.currentStep = <?= (int)($step ?? 1) ?>;
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/setup.js"></script>
</body>
</html>

