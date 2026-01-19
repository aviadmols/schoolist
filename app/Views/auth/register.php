<?php
$title = 'הרשמה';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הרשמה</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <style>
        .auth-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .parent-group {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .parent-group h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #0C4A6E;
        }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
            }
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            margin-top: 1rem;
        }
        .message {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 6px;
        }
        .message.success {
            background: #f9f9f9;
            color: #000;
            border: 1px solid #ddd;
        }
        .message.error {
            background: #f9f9f9;
            color: #000;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1><?= htmlspecialchars($i18n->t('app_name'), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="invitation-notice" style="background: #E8F4FD; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 2px solid #0C4A6E;">
            <strong>קוד הזמנה: <?= htmlspecialchars($invitation_code ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <h2>הרשמה</h2>
        <p>אנא מלא את הפרטים הבאים כדי להשלים את ההרשמה:</p>
        <form id="registerForm" class="auth-form">
            <div class="form-group">
                <label for="child_name">שם הילד *</label>
                <input type="text" id="child_name" name="child_name" required placeholder="הזן שם הילד">
            </div>

            <div class="form-group">
                <label for="child_birth_date">תאריך לידה של הילד *</label>
                <input type="date" id="child_birth_date" name="child_birth_date" required>
            </div>

            <div class="parent-group">
                <h3>הורה ראשון</h3>
                <div class="form-group">
                    <label for="parent1_name">שם הורה ראשון *</label>
                    <input type="text" id="parent1_name" name="parent1_name" required placeholder="הזן שם הורה ראשון">
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="parent1_role">תפקיד *</label>
                        <select id="parent1_role" name="parent1_role" required>
                            <option value="">בחר</option>
                            <option value="אבא">אבא</option>
                            <option value="אמא">אמא</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parent1_phone">טלפון *</label>
                        <input type="tel" id="parent1_phone" name="parent1_phone" required placeholder="05X-XXXXXXX" pattern="[0-9]{10}">
                    </div>
                </div>
            </div>

            <div class="parent-group">
                <h3>הורה שני</h3>
                <div class="form-group">
                    <label for="parent2_name">שם הורה שני *</label>
                    <input type="text" id="parent2_name" name="parent2_name" required placeholder="הזן שם הורה שני">
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="parent2_role">תפקיד *</label>
                        <select id="parent2_role" name="parent2_role" required>
                            <option value="">בחר</option>
                            <option value="אבא">אבא</option>
                            <option value="אמא">אמא</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parent2_phone">טלפון *</label>
                        <input type="tel" id="parent2_phone" name="parent2_phone" required placeholder="05X-XXXXXXX" pattern="[0-9]{10}">
                    </div>
                </div>
            </div>

            <input type="hidden" id="code" name="code" value="<?= htmlspecialchars($invitation_code ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="email" name="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
            
            <button type="submit" class="btn btn-primary">אישור והרשמה</button>
            <div id="message" class="message"></div>
        </form>
    </div>
    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/auth.js"></script>
</body>
</html>








