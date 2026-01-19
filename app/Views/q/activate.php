<?php
$title = $i18n->t('activate_q');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('activate_q'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1><?= htmlspecialchars($i18n->t('activate_q'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p>מספר קישור: <strong><?= htmlspecialchars((string)$q_number, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <form id="activateForm" class="auth-form">
            <input type="hidden" id="q_number" value="<?= (int)$q_number ?>">
            <div class="form-group">
                <label for="page_unique_id"><?= htmlspecialchars($i18n->t('enter_page_id'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="number" id="page_unique_id" name="page_unique_id" required placeholder="123456">
            </div>
            <button type="submit" class="btn btn-primary"><?= htmlspecialchars($i18n->t('activate'), ENT_QUOTES, 'UTF-8') ?></button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/q.js"></script>
</body>
</html>

