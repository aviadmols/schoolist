<?php
$title = $i18n->t('error');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('error'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1><?= htmlspecialchars($i18n->t('error'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($message ?? 'שגיאה', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</body>
</html>

