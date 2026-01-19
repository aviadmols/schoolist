<?php
$title = $i18n->t('invitation_codes');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/admin.css">
</head>
<body class="admin-page">
    <nav class="admin-nav">
        <h1><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></h1>
        <a href="/admin" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('back'), ENT_QUOTES, 'UTF-8') ?></a>
        <a href="/api/auth/logout" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('logout'), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>

    <div class="admin-container">
        <div class="admin-menu">
            <a href="/admin/invitations" class="menu-item active"><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/pages" class="menu-item"><?= htmlspecialchars($i18n->t('pages'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/q-activations" class="menu-item"><?= htmlspecialchars($i18n->t('q_activations'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/admin/ai-settings" class="menu-item">הגדרות AI</a>
            <a href="/admin/sms-settings" class="menu-item">הגדרות SMS</a>
        </div>
        <div class="admin-content">
            <div class="section-header">
                <h2><?= htmlspecialchars($i18n->t('invitation_codes'), ENT_QUOTES, 'UTF-8') ?></h2>
                <button class="btn btn-primary" onclick="openCreateInvitation()"><?= htmlspecialchars($i18n->t('create_invitation'), ENT_QUOTES, 'UTF-8') ?></button>
            </div>

            <div class="filters">
                <input type="text" id="filterSchool" placeholder="סנן לפי שם בית ספר">
                <input type="text" id="filterEmail" placeholder="סנן לפי אימייל">
                <select id="filterStatus">
                    <option value="">כל הסטטוסים</option>
                    <option value="active">פעיל</option>
                    <option value="used">משומש</option>
                    <option value="disabled">מושבת</option>
                </select>
                <button class="btn btn-secondary" onclick="loadInvitations()">סנן</button>
            </div>

            <div class="table-container">
                <table id="invitationsTable">
                    <thead>
                        <tr>
                            <th>קוד</th>
                            <th>שם בית ספר</th>
                            <th>אימייל מנהל</th>
                            <th>פרטי נרשם</th>
                            <th>לינק התחברות</th>
                            <th>סטטוס</th>
                            <th>נוצר ב</th>
                            <th>שומש ב</th>
                            <th>פעולות</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Invitation Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createModal')">&times;</span>
            <h3><?= htmlspecialchars($i18n->t('create_invitation'), ENT_QUOTES, 'UTF-8') ?></h3>
            <form id="createInvitationForm">
                <div class="form-group">
                    <label><?= htmlspecialchars($i18n->t('school_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="school_name" required>
                </div>
                <div class="form-group">
                    <label><?= htmlspecialchars($i18n->t('admin_email'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="email" name="admin_email" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($i18n->t('create_invitation'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <script>
        window.BASE_URL = '<?= BASE_URL ?? '/' ?>';
        window.csrfToken = '<?= $csrf_token ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadInvitations();
            
            document.getElementById('createInvitationForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                // Add CSRF token to data
                data.csrf_token = window.csrfToken;
                
                try {
                    const result = await API.post('/api/admin/invitations', data);
                    if (result.ok) {
                        closeModal('createModal');
                        // Show login link
                        if (result.login_link) {
                            const message = `קוד הזמנה נוצר בהצלחה!\n\nלינק התחברות:\n${result.login_link}\n\nהלינק נשלח ללקוח באימייל.`;
                            alert(message);
                            // Copy to clipboard if possible
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(result.login_link).then(() => {
                                    console.log('Login link copied to clipboard');
                                });
                            }
                        }
                        loadInvitations();
                    }
                } catch (error) {
                    alert('שגיאה: ' + error.message);
                }
            });
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function openCreateInvitation() {
            if (typeof openModal === 'function') {
                openModal('createModal');
            } else {
                console.error('openModal is not defined. Make sure main.js is loaded.');
            }
        }
        
        async function loadInvitations() {
            const filters = {
                school_name: document.getElementById('filterSchool').value,
                admin_email: document.getElementById('filterEmail').value,
                status: document.getElementById('filterStatus').value
            };
            
            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });
            
            try {
                const result = await API.get('/api/admin/invitations?' + params.toString());
                if (result.ok) {
                    renderInvitationsTable(result.invitations);
                }
            } catch (error) {
                console.error('Failed to load invitations:', error);
            }
        }
        
        function renderInvitationsTable(invitations) {
            const tbody = document.querySelector('#invitationsTable tbody');
            const baseUrl = typeof BASE_URL !== 'undefined' ? BASE_URL : window.location.origin + '/';
            tbody.innerHTML = invitations.map(inv => {
                const loginLink = rtrim(baseUrl, '/') + '/login/' + inv.code + '?email=' + encodeURIComponent(inv.admin_email);
                const hasRegistration = inv.child_name && inv.child_name.trim() !== '';
                
                return `
                <tr>
                    <td><strong>${escapeHtml(inv.code)}</strong></td>
                    <td>${escapeHtml(inv.school_name)}</td>
                    <td>${escapeHtml(inv.admin_email)}</td>
                    <td>
                        ${hasRegistration ? `<button class="btn-icon" onclick="showRegistrationDetails(${inv.id})" title="הצג פרטים" style="padding: 6px 12px; font-size: 0.85rem;">👤 פרטים</button>` : '<span style="color: #999;">-</span>'}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="text" value="${loginLink}" readonly style="flex: 1; padding: 6px 10px; border: 1px solid #E0E0E0; border-radius: 6px; font-size: 0.85rem;" id="link-${inv.id}">
                            <button class="btn-icon" onclick="copyLink(${inv.id})" title="העתק לינק" style="padding: 6px 12px; font-size: 0.85rem;">📋</button>
                        </div>
                    </td>
                    <td><span class="status-badge ${inv.status}">${getStatusText(inv.status)}</span></td>
                    <td>${formatDate(inv.created_at)}</td>
                    <td>${inv.used_at ? formatDate(inv.used_at) : '-'}</td>
                    <td class="actions">
                        ${inv.status === 'active' ? `<button class="btn-icon" onclick="disableInvitation(${inv.id})">השבת</button>` : ''}
                    </td>
                </tr>
            `;
            }).join('');
            
            // Store registration details for modal
            window.invitationsData = invitations;
        }
        
        function formatBirthDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('he-IL', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        function showRegistrationDetails(invId) {
            const inv = window.invitationsData.find(i => i.id === invId);
            if (!inv || !inv.child_name) {
                alert('אין פרטי הרשמה עבור קוד זה');
                return;
            }
            
            const details = `
                <div style="text-align: right; padding: 1rem;">
                    <h3 style="margin-top: 0;">פרטי הרשמה</h3>
                    <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <h4>פרטי הילד</h4>
                        <p><strong>שם הילד:</strong> ${escapeHtml(inv.child_name)}</p>
                        <p><strong>תאריך לידה:</strong> ${inv.child_birth_date ? formatBirthDate(inv.child_birth_date) : '-'}</p>
                    </div>
                    <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <h4>הורה ראשון</h4>
                        <p><strong>שם:</strong> ${escapeHtml(inv.parent1_name || '-')}</p>
                        <p><strong>תפקיד:</strong> ${escapeHtml(inv.parent1_role || '-')}</p>
                        <p><strong>טלפון:</strong> ${escapeHtml(inv.parent1_phone || '-')}</p>
                    </div>
                    <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px;">
                        <h4>הורה שני</h4>
                        <p><strong>שם:</strong> ${escapeHtml(inv.parent2_name || '-')}</p>
                        <p><strong>תפקיד:</strong> ${escapeHtml(inv.parent2_role || '-')}</p>
                        <p><strong>טלפון:</strong> ${escapeHtml(inv.parent2_phone || '-')}</p>
                    </div>
                </div>
            `;
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'registrationModal';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 600px;">
                    <span class="close" onclick="closeModal('registrationModal')">&times;</span>
                    ${details}
                    <button class="btn btn-secondary" onclick="closeModal('registrationModal')" style="margin-top: 1rem;">סגור</button>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function rtrim(str, char) {
            if (typeof str !== 'string') return str;
            while (str.endsWith(char)) {
                str = str.slice(0, -1);
            }
            return str;
        }
        
        function copyLink(invId) {
            const input = document.getElementById(`link-${invId}`);
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999); // For mobile devices
                try {
                    document.execCommand('copy');
                    alert('הלינק הועתק ללוח!');
                } catch (err) {
                    // Fallback for modern browsers
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(input.value).then(() => {
                            alert('הלינק הועתק ללוח!');
                        });
                    } else {
                        alert('לא ניתן להעתיק. נא להעתיק ידנית: ' + input.value);
                    }
                }
            }
        }
        
        function getStatusText(status) {
            const texts = {
                'active': 'פעיל',
                'used': 'משומש',
                'disabled': 'מושבת'
            };
            return texts[status] || status;
        }
        
        async function disableInvitation(id) {
            if (!confirm('האם אתה בטוח שברצונך להשבית קוד זה?')) return;
            
            try {
                await API.put(`/api/admin/invitations/${id}`, { status: 'disabled' });
                loadInvitations();
            } catch (error) {
                alert('שגיאה: ' + error.message);
            }
        }
    </script>
</body>
</html>

