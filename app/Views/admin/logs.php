<?php
$title = 'לוגים מערכת';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .log-container {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.5;
            overflow-x: auto;
            max-height: 70vh;
            white-space: pre-wrap;
            direction: ltr;
            text-align: left;
        }
        .log-entry {
            margin-bottom: 5px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .log-error { color: #f44336; }
        .log-info { color: #4caf50; }
        .log-timestamp { color: #888; font-size: 11px; }
        .admin-content { max-width: 1200px; width: 100%; }
        .refresh-btn { margin-bottom: 15px; }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="nav-links">
            <a href="/admin" class="btn btn-secondary">חזרה לתפריט</a>
            <a href="/api/auth/logout" class="btn btn-secondary">התנתק</a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item"><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/pages" class="menu-item"><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/q-activations" class="menu-item"><?= htmlspecialchars($i18n->t('q_activations'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
            <a href="/admin/logs" class="menu-item active">לוגים מערכת</a>
        </div>
        <div class="admin-content">
            <div class="admin-header">
                <h2>לוגים אחרונים</h2>
                <button onclick="location.reload()" class="btn btn-primary refresh-btn">רענן</button>
            </div>
            
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <p>אין לוגים זמינים.</p>
                <?php else: ?>
                    <?php foreach (array_reverse($logs) as $line): ?>
                        <div class="log-entry <?= str_contains($line, '[ERROR]') ? 'log-error' : (str_contains($line, '[INFO]') ? 'log-info' : '') ?>">
                            <?= htmlspecialchars($line) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
</body>
</html>
