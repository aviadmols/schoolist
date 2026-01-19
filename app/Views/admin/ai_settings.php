<?php
$title = 'הגדרות AI';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגדרות AI</title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1>הגדרות AI</h1>
        <a href="/admin" class="btn btn-secondary">חזרה</a>
        <a href="/api/auth/logout" class="btn btn-secondary">התנתק</a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item">קודי הזמנה</a>
            <a href="/admin/pages" class="menu-item">דפים</a>
            <a href="/admin/q-activations" class="menu-item">הפעלות Q</a>
            <a href="/admin/ai-settings" class="menu-item active">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2>הגדרות AI</h2>
                <p>הגדר את מפתח ה-API של OpenAI לחילוץ מערכת שעות ואנשי קשר מתמונות</p>
            </div>
            <form id="aiSettingsForm" class="settings-form">
                <div class="form-group">
                    <label for="api_key">מפתח OpenAI API</label>
                    <input type="password" id="api_key" name="api_key" value="<?= htmlspecialchars($settings['api_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="sk-..." required>
                    <small>המפתח ישמש לחילוץ מערכת שעות ואנשי קשר מתמונות</small>
                </div>
                
                <div class="form-group">
                    <label for="model">מודל OpenAI</label>
                    <select id="model" name="model" required>
                        <option value="gpt-4o" <?= ($settings['model'] ?? 'gpt-4o') === 'gpt-4o' ? 'selected' : '' ?>>gpt-4o (מומלץ - תומך בתמונות)</option>
                        <option value="gpt-4o-mini" <?= ($settings['model'] ?? '') === 'gpt-4o-mini' ? 'selected' : '' ?>>gpt-4o-mini (זול יותר)</option>
                        <option value="gpt-4-turbo" <?= ($settings['model'] ?? '') === 'gpt-4-turbo' ? 'selected' : '' ?>>gpt-4-turbo</option>
                        <option value="gpt-4" <?= ($settings['model'] ?? '') === 'gpt-4' ? 'selected' : '' ?>>gpt-4</option>
                        <option value="gpt-3.5-turbo" <?= ($settings['model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : '' ?>>gpt-3.5-turbo (לא תומך בתמונות)</option>
                    </select>
                    <small>בחר את המודל לחילוץ נתונים. gpt-4o מומלץ כי הוא תומך בתמונות ומהיר.</small>
                </div>
                
                <div class="form-group">
                    <label for="schedule_prompt">פרומפט לחילוץ מערכת שעות</label>
                    <textarea id="schedule_prompt" name="schedule_prompt" rows="8" style="width: 100%; font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($settings['schedule_prompt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    <small>הפרומפט שנשלח ל-OpenAI לחילוץ מערכת שעות מתמונה</small>
                </div>
                
                <div class="form-group">
                    <label for="contacts_prompt">פרומפט לחילוץ אנשי קשר</label>
                    <textarea id="contacts_prompt" name="contacts_prompt" rows="8" style="width: 100%; font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($settings['contacts_prompt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    <small>הפרומפט שנשלח ל-OpenAI לחילוץ אנשי קשר מתמונה</small>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">שמור הגדרות</button>
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">בדוק חיבור</button>
                </div>
            </form>
            <div id="message" class="message" style="margin-top: 1rem;"></div>
            <div id="testResult" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="/public/assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('aiSettingsForm');
            const messageEl = document.getElementById('message');

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = {
                    api_key: formData.get('api_key'),
                    model: formData.get('model'),
                    schedule_prompt: formData.get('schedule_prompt'),
                    contacts_prompt: formData.get('contacts_prompt'),
                    csrf_token: csrfToken
                };

                try {
                    const result = await API.post('/api/admin/ai-settings', data);
                    if (result.ok) {
                        showMessage(messageEl, result.message_he, 'success');
                    } else {
                        showMessage(messageEl, result.message_he || 'שגיאה בשמירת ההגדרות', 'error');
                    }
                } catch (error) {
                    console.error('Error saving AI settings:', error);
                    showMessage(messageEl, 'שגיאה: ' + error.message, 'error');
                }
            });
        });
        
        async function testConnection() {
            const messageEl = document.getElementById('message');
            const testResultEl = document.getElementById('testResult');
            const apiKey = document.getElementById('api_key').value;
            const model = document.getElementById('model').value;
            
            if (!apiKey) {
                showMessage(messageEl, 'נא להזין מפתח API לפני הבדיקה', 'error');
                return;
            }
            
            testResultEl.innerHTML = '<div class="message info">בודק חיבור עם מודל ' + model + '...</div>';
            
            try {
                const result = await API.post('/api/admin/ai-test', {
                    api_key: apiKey,
                    model: model,
                    csrf_token: csrfToken
                });
                
                if (result.ok) {
                    testResultEl.innerHTML = '<div class="message success">✓ החיבור עובד בהצלחה!<br>' + 
                        (result.message || '') + '</div>';
                } else {
                    testResultEl.innerHTML = '<div class="message error">✗ החיבור נכשל<br>' + 
                        (result.message_he || result.reason || 'שגיאה לא ידועה') + '</div>';
                }
            } catch (error) {
                testResultEl.innerHTML = '<div class="message error">✗ שגיאה בבדיקה: ' + error.message + '</div>';
                console.error('Test connection error:', error);
            }
        }
    </script>
</body>
</html>

