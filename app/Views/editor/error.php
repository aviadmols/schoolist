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
<body class="public-page">
    <div class="page-header">
        <h1><?= htmlspecialchars($i18n->t('error'), ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    
    <div class="empty-state">
        <p><?= htmlspecialchars($message ?? 'שגיאה', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="/dashboard" class="btn btn-primary">חזור ללוח הבקרה</a>
    </div>
</body>
</html>

