<?php
$title = 'הפעלות Q';
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= $title ?></h1>
        <a href="/admin" class="btn btn-secondary">חזרה</a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item">קודי הזמנה</a>
            <a href="/admin/pages" class="menu-item">דפים</a>
            <a href="/admin/q-activations" class="menu-item active">הפעלות Q</a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
            <a href="/admin/logs" class="menu-item">לוגים מערכת</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2>ניהול הפעלות Q</h2>
            </div>

            <div class="filters">
                <input type="number" id="filterQ" placeholder="מספר Q">
                <button class="btn btn-secondary" onclick="loadActivations()">חפש</button>
            </div>

            <div class="table-container">
                <table id="activationsTable">
                    <thead>
                        <tr>
                            <th>מספר Q</th>
                            <th>דף מקושר</th>
                            <th>סטטוס</th>
                            <th>נוצר ב</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?= BASE_URL ?? '/' ?>';
        window.csrfToken = '<?= $csrf_token ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadActivations);

        async function loadActivations() {
            const q = document.getElementById('filterQ').value;
            try {
                const result = await API.get(`/api/admin/q-activations?q_number=${q}`);
                if (result.ok) {
                    const tbody = document.querySelector('#activationsTable tbody');
                    tbody.innerHTML = result.activations.map(a => `
                        <tr>
                            <td><strong>${a.q_number}</strong></td>
                            <td>${a.page_unique_id || 'לא מקושר'}</td>
                            <td><span class="badge ${a.status}">${a.status}</span></td>
                            <td>${formatDate(a.created_at)}</td>
                        </tr>
                    `).join('');
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
