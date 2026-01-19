<?php
$title = 'שגיאה';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שגיאה</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>שגיאה</h1>
        <p class="message error"><?= htmlspecialchars($message ?? 'שגיאה לא ידועה', ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</body>
</html>



