<?php
$title = $i18n->t('dashboard');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('dashboard'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="public-page">
    <div class="page-header">
        <h1><?= htmlspecialchars($i18n->t('my_pages'), ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    
    <div class="blocks-section">
        <?php foreach ($pages as $page): ?>
            <div class="block-card" onclick="window.location.href='/p/<?= $page['unique_numeric_id'] ?>'">
                <div class="block-content">
                    <h3><?= htmlspecialchars($page['school_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($page['class_title'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="block-chevron">›</div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <footer class="page-footer">
        <a href="/api/auth/logout" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('logout'), ENT_QUOTES, 'UTF-8') ?></a>
    </footer>
</body>
</html>

