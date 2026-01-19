<?php
$title = $i18n->t('pages');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background-color: #fff; padding: 2rem; border-radius: 12px;
            width: 100%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative; max-height: 90vh; overflow-y: auto;
        }
        .autocomplete-container { position: relative; }
        .autocomplete-results {
            position: absolute; top: 100%; left: 0; right: 0;
            background: white; border: 1px solid #ddd; border-top: none;
            z-index: 100; max-height: 200px; overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .autocomplete-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; }
        .autocomplete-item:hover { background: #f0f7ff; }
        .selected-admins { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .admin-chip {
            background: #e0f2fe; color: #0369a1; padding: 4px 10px;
            border-radius: 16px; font-size: 13px; display: flex; align-items: center; gap: 6px;
        }
        .admin-chip .remove { cursor: pointer; font-weight: bold; }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="/admin" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('back'), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/users" class="menu-item">משתמשים</a>
            <a href="/admin/invitations" class="menu-item"><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/pages" class="menu-item active"><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/q-activations" class="menu-item"><?= htmlspecialchars($i18n->t('q_activations'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
            <a href="/admin/logs" class="menu-item">לוגים מערכת</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2>ניהול דפי בית ספר</h2>
                <button class="btn btn-primary" onclick="openCreatePage()">צור דף חדש</button>
            </div>

            <div class="filters">
                <input type="text" id="filterSchool" placeholder="בית ספר">
                <select id="filterCity">
                    <option value="">כל הערים</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" onclick="loadPages()">סנן</button>
            </div>

            <div class="table-container">
                <table id="pagesTable">
                    <thead>
                        <tr>
                            <th>מזהה</th>
                            <th>עיר</th>
                            <th>בית ספר</th>
                            <th>כיתה</th>
                            <th>מנהלים</th>
                            <th>פעולות</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Page Modal -->
    <div id="createPageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createPageModal')">&times;</span>
            <h3>צור דף חדש</h3>
            <form id="createPageForm">
                <div class="form-group">
                    <label>עיר</label>
                    <select name="city" required>
                        <option value="">בחר עיר...</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>שם בית ספר</label>
                    <input type="text" name="school_name" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>כיתה</label>
                        <select name="class_type" required>
                            <?php foreach(['א','ב','ג','ד','ה','ו','ז','ח','ט','י','יא','יב'] as $g): ?>
                                <option value="<?= $g ?>"><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>מספר כיתה</label>
                        <input type="number" name="class_number" value="1" min="1" required>
                    </div>
                </div>

                <div class="form-group autocomplete-container">
                    <label>שיוך מנהלי דף (חפש לפי שם או אימייל)</label>
                    <input type="text" id="adminSearch" placeholder="הקלד לחיפוש משתמשים...">
                    <div id="adminSearchResults" class="autocomplete-results" style="display: none;"></div>
                    <div id="selectedAdmins" class="selected-admins"></div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">צור דף</button>
            </form>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?= BASE_URL ?? '/' ?>';
        window.csrfToken = '<?= $request->csrfToken() ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script>
        let selectedAdmins = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadPages();
            setupAdminAutocomplete();
            
            document.getElementById('createPageForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                data.admin_ids = selectedAdmins.map(a => a.id);
                
                // Add CSRF token
                data.csrf_token = window.csrfToken;
                
                try {
                    const result = await API.post('/api/admin/pages', data);
                    if (result.ok) {
                        closeModal('createPageModal');
                        loadPages();
                        selectedAdmins = [];
                        renderSelectedAdmins();
                        this.reset();
                    } else { alert(result.message_he); }
                } catch (e) { alert(e.message); }
            });
        });

        function setupAdminAutocomplete() {
            const input = document.getElementById('adminSearch');
            const results = document.getElementById('adminSearchResults');
            let timeout;

            input.addEventListener('input', () => {
                clearTimeout(timeout);
                const term = input.value.trim();
                if (term.length < 2) { results.style.display = 'none'; return; }

                timeout = setTimeout(async () => {
                    const data = await API.get(`/api/admin/users/search?term=${encodeURIComponent(term)}`);
                    if (data.ok && data.users.length > 0) {
                        results.innerHTML = data.users.map(u => `
                            <div class="autocomplete-item" onclick="addAdmin(${JSON.stringify(u).replace(/"/g, '&quot;')})">
                                ${u.label}
                            </div>
                        `).join('');
                        results.style.display = 'block';
                    } else { results.style.display = 'none'; }
                }, 300);
            });
        }

        function addAdmin(user) {
            if (!selectedAdmins.find(a => a.id === user.id)) {
                selectedAdmins.push(user);
                renderSelectedAdmins();
            }
            document.getElementById('adminSearch').value = '';
            document.getElementById('adminSearchResults').style.display = 'none';
        }

        function removeAdmin(id) {
            selectedAdmins = selectedAdmins.filter(a => a.id !== id);
            renderSelectedAdmins();
        }

        function renderSelectedAdmins() {
            const container = document.getElementById('selectedAdmins');
            container.innerHTML = selectedAdmins.map(a => `
                <div class="admin-chip">
                    ${a.label}
                    <span class="remove" onclick="removeAdmin(${a.id})">&times;</span>
                </div>
            `).join('');
        }

        async function loadPages() {
            const filters = {
                school_name: document.getElementById('filterSchool').value,
                city: document.getElementById('filterCity').value
            };
            const params = new URLSearchParams(filters);
            try {
                const result = await API.get(`/api/admin/pages?${params}`);
                if (result.ok) {
                    document.querySelector('#pagesTable tbody').innerHTML = result.pages.map(p => `
                        <tr>
                            <td><strong>${p.unique_numeric_id}</strong></td>
                            <td>${p.city || '-'}</td>
                            <td>${p.school_name}</td>
                            <td>${p.class_title}</td>
                            <td><small>${p.admin_names || '-'}</small></td>
                    <td class="actions">
                                <a href="/c/${p.unique_numeric_id}" target="_blank" class="btn-icon">צפה</a>
                                <button onclick="deletePage(${p.id})" class="btn-icon btn-danger" style="color: white;">מחק</button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        function openCreatePage() {
            document.getElementById('createPageModal').classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        async function deletePage(id) {
            if (!confirm('האם אתה בטוח שברצונך למחוק דף זה? כל המידע (הודעות, אירועים וכו\') יימחק לצמיתות.')) return;
            
            try {
                const result = await API.delete(`/api/admin/pages/${id}`);
                if (result.ok) {
                    loadPages();
                } else {
                    alert(result.message_he || 'שגיאה במחיקה');
                }
            } catch (e) {
                alert('שגיאה: ' + e.message);
            }
        }

        // Export to window for onclick handlers
        window.openCreatePage = openCreatePage;
        window.closeModal = closeModal;
        window.addAdmin = addAdmin;
        window.removeAdmin = removeAdmin;
        window.loadPages = loadPages;
        window.deletePage = deletePage;
    </script>
</body>
</html>
