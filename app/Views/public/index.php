<?php
$title = $i18n->t('app_name');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('app_name'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="public-page">
    <div class="page-header">
        <h1><?= htmlspecialchars($i18n->t('app_name'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p>מערכת ניהול דפי כיתה</p>
    </div>
    
    <div class="blocks-section">
        <a href="/login" class="btn btn-primary" style="display: block; text-align: center; margin-bottom: 1rem;">התחברות</a>
    </div>
</body>
</html>

