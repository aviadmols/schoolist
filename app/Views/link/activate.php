<?php
$title = 'הפעלת קישור';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הפעלת קישור</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>הפעלת קישור</h1>
        <p>מספר קישור: <strong><?= htmlspecialchars((string)$link_number, ENT_QUOTES, 'UTF-8') ?></strong></p>
        <form id="activateForm" class="auth-form">
            <input type="hidden" id="link_number" value="<?= (int)$link_number ?>">
            <div class="form-group">
                <label for="activation_code">הזן קוד מזהה של הכיתה</label>
                <input type="text" id="activation_code" name="activation_code" required placeholder="הזן קוד מזהה">
            </div>
            <button type="submit" class="btn btn-primary">הפעל</button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/link.js"></script>
</body>
</html>

