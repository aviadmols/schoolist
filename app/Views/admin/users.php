<?php
$title = 'ניהול משתמשים';
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
            width: 100%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;
        }
    </style>
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= $title ?></h1>
        <a href="/admin" class="btn btn-secondary">חזרה</a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/users" class="menu-item active">משתמשים</a>
            <a href="/admin/invitations" class="menu-item">קודי הזמנה</a>
            <a href="/admin/pages" class="menu-item">דפים</a>
            <a href="/admin/q-activations" class="menu-item">הפעלות Q</a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
            <a href="/admin/logs" class="menu-item">לוגים מערכת</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2>ניהול משתמשי מערכת</h2>
                <button class="btn btn-primary" onclick="openUserModal()">הוסף משתמש חדש</button>
            </div>

            <div class="filters">
                <input type="text" id="filterName" placeholder="שם פרטי / משפחה">
                <input type="text" id="filterEmail" placeholder="אימייל">
                <button class="btn btn-secondary" onclick="loadUsers()">סנן</button>
            </div>

            <div class="table-container">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>שם</th>
                            <th>אימייל</th>
                            <th>טלפון</th>
                            <th>תפקיד</th>
                            <th>סטטוס</th>
                            <th>פעולות</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeUserModal()">&times;</span>
            <h3 id="modalTitle">הוסף משתמש חדש</h3>
            <form id="userForm">
                <input type="hidden" name="id" id="userId">
                <div class="form-group">
                    <label>שם פרטי</label>
                    <input type="text" name="first_name" id="userFirstName" required>
                </div>
                <div class="form-group">
                    <label>שם משפחה</label>
                    <input type="text" name="last_name" id="userLastName" required>
                </div>
                <div class="form-group">
                    <label>אימייל</label>
                    <input type="email" name="email" id="userEmail" required>
                </div>
                <div class="form-group">
                    <label>טלפון</label>
                    <input type="text" name="phone" id="userPhone">
                </div>
                <div class="form-group">
                    <label>תפקיד</label>
                    <select name="role" id="userRole">
                        <option value="parent">הורה</option>
                        <option value="page_admin">מנהל דף</option>
                        <option value="system_admin">מנהל מערכת</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>סטטוס</label>
                    <select name="status" id="userStatus">
                        <option value="active">פעיל</option>
                        <option value="inactive">לא פעיל</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">שמור</button>
            </form>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?= BASE_URL ?? '/' ?>';
        window.csrfToken = '<?= $csrf_token ?>';

        async function loadUsers() {
            const name = document.getElementById('filterName').value;
            const email = document.getElementById('filterEmail').value;
            try {
                const result = await API.get(`/api/admin/users?name=${name}&email=${email}`);
                if (result.ok) {
                    const tbody = document.querySelector('#usersTable tbody');
                    tbody.innerHTML = result.users.map(u => `
                        <tr>
                            <td>${u.first_name || ''} ${u.last_name || ''}</td>
                            <td>${u.email}</td>
                            <td>${u.phone || '-'}</td>
                            <td>${u.role === 'system_admin' ? 'מנהל מערכת' : (u.role === 'parent' ? 'הורה' : 'מנהל דף')}</td>
                            <td><span class="badge ${u.status}">${u.status === 'active' ? 'פעיל' : 'לא פעיל'}</span></td>
                            <td>
                                <button class="btn-icon" onclick="editUser(${JSON.stringify(u).replace(/"/g, '&quot;')})">ערוך</button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (e) { console.error(e); }
        }

        function openUserModal() {
            document.getElementById('modalTitle').textContent = 'הוסף משתמש חדש';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userModal').classList.add('active');
        }

        function closeUserModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function editUser(user) {
            document.getElementById('modalTitle').textContent = 'ערוך משתמש';
            document.getElementById('userId').value = user.id;
            document.getElementById('userFirstName').value = user.first_name || '';
            document.getElementById('userLastName').value = user.last_name || '';
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPhone').value = user.phone || '';
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userModal').classList.add('active');
        }

        window.loadUsers = loadUsers;
        window.openUserModal = openUserModal;
        window.closeUserModal = closeUserModal;
        window.editUser = editUser;
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();
            
            document.getElementById('userForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                data.csrf_token = window.csrfToken;

                try {
                    const result = await API.post('/api/admin/users/save', data);
                    if (result.ok) {
                        closeUserModal();
                        loadUsers();
                    } else { alert(result.message_he); }
                } catch (e) { alert(e.message); }
            });
        });
    </script>
</body>
</html>
