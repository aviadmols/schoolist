// Setup Wizard JavaScript
// Prevent double loading
if (typeof window.setupWizardLoaded !== 'undefined') {
    console.warn('setup.js already loaded');
} else {
    window.setupWizardLoaded = true;
}

const totalSteps = 5;

// Initialize currentStep from window if available, or use default
if (typeof window.currentStep === 'undefined') {
    window.currentStep = 1;
}

var currentStep = Number(window.currentStep);

document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadStep === 'function') {
        loadStep(currentStep);
    }
});

function loadStep(step) {
    const wizard = document.getElementById('setup-wizard');
    
    switch (step) {
        case 1:
            wizard.innerHTML = renderStep1();
            break;
        case 2:
            wizard.innerHTML = renderStep2();
            break;
        case 3:
            wizard.innerHTML = renderStep3();
            break;
        case 4:
            wizard.innerHTML = renderStep4();
            break;
        case 5:
            wizard.innerHTML = renderStep5();
            break;
    }
    
    currentStep = step;
}

function renderStep1() {
    return `
        <div class="setup-step active">
            <h2>שלב 1: בדיקת דרישות</h2>
            <div id="requirements-check"></div>
            <div class="setup-actions">
                <button class="btn btn-primary" onclick="checkRequirements()">בדוק דרישות</button>
            </div>
        </div>
    `;
}

function renderStep2() {
    return `
        <div class="setup-step active">
            <h2>שלב 2: הגדרות מסד נתונים</h2>
            <form id="dbForm" class="setup-form">
                <div class="form-group">
                    <label>שרת מסד נתונים</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>שם מסד נתונים</label>
                    <input type="text" name="db_name" required>
                </div>
                <div class="form-group">
                    <label>שם משתמש</label>
                    <input type="text" name="db_user" required>
                </div>
                <div class="form-group">
                    <label>סיסמה</label>
                    <input type="password" name="db_pass">
                </div>
                <div class="form-group">
                    <label>קידומת טבלאות</label>
                    <input type="text" name="db_prefix" value="sl_" required>
                </div>
            </form>
            <div class="setup-actions">
                <button class="btn btn-secondary" onclick="loadStep(1)">חזור</button>
                <button class="btn btn-primary" onclick="saveStep2()">הבא</button>
            </div>
        </div>
    `;
}

function renderStep3() {
    return `
        <div class="setup-step active">
            <h2>שלב 3: כתובת בסיס</h2>
            <form id="urlForm" class="setup-form">
                <div class="form-group">
                    <label>כתובת בסיס (Base URL)</label>
                    <input type="text" name="base_url" value="${window.location.origin}" required>
                    <small>לדוגמה: https://app.schoolist.co.il</small>
                </div>
            </form>
            <div class="setup-actions">
                <button class="btn btn-secondary" onclick="loadStep(2)">חזור</button>
                <button class="btn btn-primary" onclick="saveStep3()">הבא</button>
            </div>
        </div>
    `;
}

function renderStep4() {
    return `
        <div class="setup-step active">
            <h2>שלב 4: הגדרות אימייל</h2>
            <form id="emailForm" class="setup-form">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="smtp_enabled" onchange="toggleSmtp(this.checked)">
                        השתמש ב-SMTP
                    </label>
                </div>
                <div id="smtpFields" style="display: none;">
                    <div class="form-group">
                        <label>שרת SMTP</label>
                        <input type="text" name="smtp_host">
                    </div>
                    <div class="form-group">
                        <label>פורט SMTP</label>
                        <input type="number" name="smtp_port" value="587">
                    </div>
                    <div class="form-group">
                        <label>שם משתמש SMTP</label>
                        <input type="text" name="smtp_user">
                    </div>
                    <div class="form-group">
                        <label>סיסמת SMTP</label>
                        <input type="password" name="smtp_pass">
                    </div>
                    <div class="form-group">
                        <label>כתובת שולח</label>
                        <input type="email" name="smtp_from" value="noreply@schoolist.co.il">
                    </div>
                    <div class="form-group">
                        <label>שם שולח</label>
                        <input type="text" name="smtp_from_name" value="Schoolist">
                    </div>
                </div>
            </form>
            <div class="setup-actions">
                <button class="btn btn-secondary" onclick="loadStep(3)">חזור</button>
                <button class="btn btn-primary" onclick="saveStep4()">הבא</button>
            </div>
        </div>
    `;
}

function renderStep5() {
    return `
        <div class="setup-step active">
            <h2>שלב 5: סיום התקנה</h2>
            <form id="finalForm" class="setup-form">
                <div class="form-group">
                    <label>מפתח API של OpenAI (אופציונלי)</label>
                    <input type="text" name="openai_key" placeholder="sk-...">
                    <small>נדרש לחילוץ מערכת שעות ואנשי קשר מתמונות</small>
                </div>
                <div class="form-group">
                    <label>אימייל מנהל ראשוני (אופציונלי)</label>
                    <input type="email" name="admin_email" placeholder="admin@example.com">
                </div>
            </form>
            <div class="setup-actions">
                <button class="btn btn-secondary" onclick="loadStep(4)">חזור</button>
                <button class="btn btn-primary" onclick="install()">התקן</button>
            </div>
            <div id="installProgress"></div>
        </div>
    `;
}

async function checkRequirements() {
    const checkEl = document.getElementById('requirements-check');
    checkEl.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
    
    try {
        const result = await API.post('setup/step/1', {});
        
        let html = '<ul class="requirements-list">';
        Object.entries(result.checks).forEach(([key, value]) => {
            html += `<li class="${value ? 'success' : 'error'}">
                ${key}: ${value ? '✓' : '✗'}
            </li>`;
        });
        html += '</ul>';
        
        if (result.ok) {
            html += '<p style="color: green; margin-top: 1rem;">כל הדרישות מתקיימות!</p>';
            setTimeout(() => loadStep(2), 2000);
        } else {
            html += '<p style="color: red; margin-top: 1rem;">יש לתקן את הבעיות לפני המשך</p>';
        }
        
        checkEl.innerHTML = html;
    } catch (error) {
        checkEl.innerHTML = `<p style="color: red;">שגיאה: ${error.message}</p>`;
    }
}

async function saveStep2() {
    const form = document.getElementById('dbForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const result = await API.post('setup/step/2', data);
        if (result.ok) {
            loadStep(3);
        } else {
            alert(result.message_he || 'שגיאה');
        }
    } catch (error) {
        alert('שגיאה: ' + error.message);
    }
}

async function saveStep3() {
    const form = document.getElementById('urlForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const result = await API.post('setup/step/3', data);
        if (result.ok) {
            loadStep(4);
        }
    } catch (error) {
        alert('שגיאה: ' + error.message);
    }
}

async function saveStep4() {
    const form = document.getElementById('emailForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    try {
        const result = await API.post('setup/step/4', data);
        if (result.ok) {
            loadStep(5);
        }
    } catch (error) {
        alert('שגיאה: ' + error.message);
    }
}

function toggleSmtp(enabled) {
    document.getElementById('smtpFields').style.display = enabled ? 'block' : 'none';
}

async function install() {
    const form = document.getElementById('finalForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const progressEl = document.getElementById('installProgress');
    
    progressEl.innerHTML = '<div class="loading"><div class="spinner"></div><p>מתקין...</p></div>';
    
    try {
        const result = await API.post('setup/step/5', data);
        if (result.ok) {
            progressEl.innerHTML = `
                <div class="success-message">
                    <h2>ההתקנה הושלמה בהצלחה!</h2>
                    <p>אתה יכול כעת להתחבר למערכת.</p>
                    <a href="/login" class="btn btn-primary">התחבר</a>
                </div>
            `;
        } else {
            progressEl.innerHTML = `<p style="color: red;">${result.message_he || 'שגיאה בהתקנה'}</p>`;
        }
    } catch (error) {
        progressEl.innerHTML = `<p style="color: red;">שגיאה: ${error.message}</p>`;
    }
}

