/**
 * Schoolist Core JS Utilities
 * Handles API communications and UI helpers.
 */

const AppConfig = {
    BASE_URL: (window.BASE_URL || '/').replace(/\/$/, ''), // Remove trailing slash
    CSRF_TOKEN: window.csrfToken || ''
};

/**
 * Main API communication object
 */
const ApiService = {
    /**
     * Internal request wrapper
     */
    async request(endpoint, options = {}) {
        // Ensure endpoint starts with / and remove potential double slashes
        let path = endpoint.startsWith('http') ? endpoint : '/' + endpoint.replace(/^\//, '');
        
        let url = path;
        
        // Only use absolute URL if endpoint is absolute or BASE_URL is set to a remote server
        if (!path.startsWith('http')) {
            const baseUrl = (window.BASE_URL || '').replace(/\/$/, '');
            if (baseUrl && baseUrl.startsWith('http')) {
                url = baseUrl + path;
            }
        }
        
        console.log(`ApiService: Requesting ${url}`);
        
        const defaultHeaders = {
            'Content-Type': 'application/json'
        };

        // Add CSRF token if available
        const csrf = window.csrfToken || '';
        if (csrf) {
            defaultHeaders['X-CSRF-Token'] = csrf;
        }

        const token = localStorage.getItem('auth_token');
        if (token) {
            defaultHeaders['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            ...options,
            headers: { ...defaultHeaders, ...options.headers }
        };

        try {
            const response = await fetch(url, config);
            
            if (response.status === 404) {
                throw new Error('הנתיב המבוקש לא נמצא בשרת (404)');
            }

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 401) {
                    localStorage.removeItem('auth_token');
                }
                throw new Error(data.message_he || data.error || 'Server error');
            }

            return data;
        } catch (error) {
            console.error(`ApiService Error [${endpoint}]:`, error);
            // Translate "Failed to fetch" to something more meaningful
            if (error.message === 'Failed to fetch') {
                throw new Error('לא ניתן להתחבר לשרת. וודא שאתה מחובר לאינטרנט.');
            }
            throw error;
        }
    },

    get(endpoint) { return this.request(endpoint, { method: 'GET' }); },
    post(endpoint, data) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(data) }); },
    put(endpoint, data) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(data) }); },
    delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }
};

/**
 * UI Notification Helpers
 */
const UiHelper = {
    showStatusMessage(element, text, type = 'info') {
        if (!element) return;
        element.textContent = text;
        element.className = `message ${type} visible`;
        setTimeout(() => element.classList.remove('visible'), 5000);
    },

    setButtonLoading(button, isLoading) {
        if (!button) return;
        if (isLoading) {
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'טוען...';
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
    },

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'block';
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    },

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('he-IL', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });
    }
};

window.API = {
    ...ApiService,
    baseUrl: (window.BASE_URL || '/').replace(/\/$/, ''),
    csrfToken: window.csrfToken || ''
};
window.showMessage = UiHelper.showStatusMessage;
window.setLoading = UiHelper.setButtonLoading;
window.openModal = UiHelper.openModal;
window.closeModal = UiHelper.closeModal;
window.formatDate = UiHelper.formatDate;
