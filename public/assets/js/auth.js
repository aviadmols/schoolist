/**
 * Auth module for handling Login and OTP verification.
 * Organized with clear function names and structured logic.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Login form if present
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        initLoginFormHandler(loginForm);
    }

    // Initialize OTP verification form if present
    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
        initVerifyFormHandler(verifyForm);
    }
});

/**
 * Attaches the submit handler to the login form.
 * Supports both OTP requests and direct code login.
 * 
 * @param {HTMLFormElement} form
 */
function initLoginFormHandler(form) {
    const messageBox = document.getElementById('message');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const emailField = document.getElementById('email');
        const userIdentifier = emailField ? emailField.value.trim() : '';
        
        if (!userIdentifier) return;

        try {
            setLoading(submitBtn, true);
            
            // Scenario 1: Logging in with an invitation code (one-step)
            const invitationCodeField = document.getElementById('code');
            if (invitationCodeField && window.invitationCode) {
                await processDirectCodeLogin(userIdentifier, invitationCodeField.value.trim(), messageBox);
                return;
            }

            // Scenario 2: Requesting a new OTP (two-step)
            await requestNewOtp(userIdentifier, messageBox);

        } catch (error) {
            showMessage(messageBox, error.message, 'error');
        } finally {
            setLoading(submitBtn, false);
        }
    });
}

/**
 * Sends a request to generate and send an OTP code.
 */
async function requestNewOtp(identifier, messageBox) {
    const payload = { email: identifier };
    // If we arrived via /login?q=..., pass the Q-number so that /verify יקבל אותה ויחזיר אותנו ל-/q/{number}
    if (typeof qNumber !== 'undefined' && qNumber) {
        payload.q = qNumber;
    }
    const response = await API.post('/api/auth/request-otp', payload);
    if (response.ok) {
        showMessage(messageBox, response.message_he, 'success');
        if (response.redirect) {
            setTimeout(() => window.location.href = response.redirect, 1500);
        }
    }
}

/**
 * Logs in directly using an invitation code.
 */
async function processDirectCodeLogin(email, code, messageBox) {
    const response = await API.post('/api/auth/login-with-code', { email, code });
    if (response.ok) {
        if (response.token) localStorage.setItem('auth_token', response.token);
        showMessage(messageBox, 'התחברת בהצלחה!', 'success');
        setTimeout(() => window.location.href = response.redirect || '/dashboard', 1000);
    }
}

/**
 * Attaches the submit handler to the OTP verification form.
 * 
 * @param {HTMLFormElement} form
 */
function initVerifyFormHandler(form) {
    const messageBox = document.getElementById('message');
    const submitBtn = form.querySelector('button[type="submit"]');
    const resendBtn = document.getElementById('resendOtpBtn');

    // Handle form submission
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        
        const codeInput = document.getElementById('code');
        const emailInput = document.getElementById('email'); // Hidden field
        
        const code = codeInput ? codeInput.value.trim() : '';
        const identifier = emailInput ? emailInput.value : '';

        try {
            setLoading(submitBtn, true);
            const response = await API.post('/api/auth/verify-otp', { email: identifier, code });

            if (response.ok) {
                // Securely store the auth token
                if (response.token) {
                    localStorage.setItem('auth_token', response.token);
                }
                
                showMessage(messageBox, 'התחברת בהצלחה!', 'success');
                
                // Redirect:
                // 1) אם השרת מחזיר redirect (למשל /redeem) – נלך לשם
                // 2) אחרת, אם יש nextUrl גלובלי (למשל חזרנו מ-/q/NUMBER) – נשתמש בו
                // 3) אחרת, נבחר ברירת מחדל לפי תפקיד
                let defaultUrl = '/dashboard';
                if (response.user && response.user.role === 'system_admin') {
                    defaultUrl = '/admin';
                }
                const targetUrl = response.redirect || (typeof nextUrl !== 'undefined' && nextUrl ? nextUrl : defaultUrl);
                setTimeout(() => window.location.href = targetUrl, 1000);
            }
        } catch (error) {
            showMessage(messageBox, error.message, 'error');
        } finally {
            setLoading(submitBtn, false);
        }
    });

    // Handle resend button
    if (resendBtn) {
        resendBtn.addEventListener('click', async () => {
            const emailInput = document.getElementById('email');
            const identifier = emailInput ? emailInput.value : '';
            if (!identifier) return;

            try {
                setLoading(resendBtn, true);
                const response = await API.post('/api/auth/request-otp', { email: identifier });
                if (response.ok) {
                    showMessage(messageBox, 'קוד חדש נשלח אליך!', 'success');
                }
            } catch (error) {
                showMessage(messageBox, error.message, 'error');
            } finally {
                setLoading(resendBtn, false);
            }
        });
    }
}
