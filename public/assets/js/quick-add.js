/**
 * Quick Add Module - AI-Powered Content Creation
 * Handles AI analysis and pre-filling editors.
 */

const QuickAddConfig = {
    ENDPOINTS: {
        ANALYZE: '/api/ai/analyze-quick-add',
        SAVE_ANNOUNCEMENT: '/api/pages/{pageId}/announcements',
        SAVE_EVENT: '/api/pages/{pageId}/events',
        SAVE_HOMEWORK: '/api/pages/{pageId}/homework'
    }
};

window.quickAddImageFile = null;
window.quickAddAnalysisResult = null;

/**
 * Global entry point to open the modal
 */
window.openQuickAddModal = function() {
    const modal = document.getElementById('quickAddModal');
    if (!modal) return;
    
    resetQuickAddForm();
    modal.style.display = 'flex';
    modal.classList.add('active', 'modal-active');
    
    setTimeout(() => document.getElementById('quickAddText')?.focus(), 100);
};

/**
 * Global entry point to close the modal
 */
window.closeQuickAddModal = function() {
    const modal = document.getElementById('quickAddModal');
    if (!modal) return;
    modal.classList.remove('active', 'modal-active');
    setTimeout(() => modal.style.display = 'none', 300);
};

/**
 * Form reset logic
 */
function resetQuickAddForm() {
    ['quickAddText', 'quickAddImage'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    
    ['quickAddImagePreview', 'quickAddSuggestions', 'quickAddPreview'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    document.getElementById('quickAddMessage').innerHTML = '';
    document.getElementById('quickAddForm').style.display = 'block';
    window.quickAddImageFile = null;
    window.quickAddAnalysisResult = null;
}

/**
 * Handle file selection
 */
window.handleQuickAddImageSelect = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    window.quickAddImageFile = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview = document.getElementById('quickAddImagePreview');
        const img = document.getElementById('quickAddImagePreviewImg');
        if (preview && img) {
            img.src = e.target.result;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
};

/**
 * Remove image
 */
window.removeQuickAddImage = function() {
    window.quickAddImageFile = null;
    document.getElementById('quickAddImage').value = '';
    document.getElementById('quickAddImagePreview').style.display = 'none';
};

/**
 * AI Analysis trigger
 */
window.analyzeQuickAddContent = async function() {
    const text = document.getElementById('quickAddText')?.value.trim();
    if (!text && !window.quickAddImageFile) return alert('אנא הזן טקסט או בחר תמונה');

    const btn = document.getElementById('quickAddAnalyzeBtn');
    const msg = document.getElementById('quickAddMessage');

    try {
        setLoading(btn, true);
        msg.innerHTML = 'מנתח תוכן...';

        const formData = new FormData();
        if (text) formData.append('text', text);
        if (window.quickAddImageFile) formData.append('image', window.quickAddImageFile);
        formData.append('csrf_token', window.csrfToken);

        const response = await fetch(QuickAddConfig.ENDPOINTS.ANALYZE, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` },
            body: formData
        });
        
        const result = await response.json();
        
        if (response.status === 400 || !result.ok) {
            throw new Error(result.message_he || result.reason || 'שגיאה בניתוח התוכן');
        }

        window.quickAddAnalysisResult = result;
        if (window.quickAddImageFile) window.quickAddAnalysisResult.uploadedImagePath = result.image_path;

        renderSuggestions(result.suggestions);

    } catch (e) {
        msg.innerHTML = `<div class="error">${e.message}</div>`;
    } finally {
        setLoading(btn, false);
    }
};

/**
 * Render suggestions list
 */
function renderSuggestions(suggestions) {
    const container = document.getElementById('quickAddSuggestions');
    const list = document.getElementById('quickAddSuggestionsList');
    if (!container || !list) return;

    list.innerHTML = '';
    suggestions.forEach((s, index) => {
        const div = document.createElement('div');
        div.className = 'quick-add-suggestion-card';
        div.innerHTML = `
            <div class="quick-add-suggestion-content">
                <div class="quick-add-suggestion-info">
                    <strong class="quick-add-suggestion-type">${getLabel(s.type)}</strong>
                    <p class="quick-add-suggestion-reason">${s.reason || ''}</p>
                </div>
                <button class="btn btn-primary" onclick="applySuggestionByIndex(${index})">בחר</button>
            </div>
        `;
        list.appendChild(div);
    });

    container.style.display = 'block';
    document.getElementById('quickAddForm').style.display = 'none';
}

/**
 * Global suggestion applier by index
 */
window.applySuggestionByIndex = function(index) {
    if (!window.quickAddAnalysisResult || !window.quickAddAnalysisResult.suggestions[index]) return;
    window.applySuggestion(window.quickAddAnalysisResult.suggestions[index]);
};

function getLabel(type) {
    const map = {
        'announcement': 'הודעה', 'event': 'אירוע', 'homework': 'שיעורי בית',
        'contact': 'איש קשר', 'links': 'קישור', 'schedule': 'מערכת שעות'
    };
    return map[type] || type;
}

/**
 * Global suggestion applier
 */
window.applySuggestion = function(suggestion) {
    const data = suggestion.extracted_data || (window.quickAddAnalysisResult ? window.quickAddAnalysisResult.extracted_data : {});
    window.closeQuickAddModal();

    switch (suggestion.type) {
        case 'announcement': initAnnouncement(data); break;
        case 'event': initEvent(data); break;
        case 'homework': initHomework(data); break;
        case 'contact': initContact(data); break;
        case 'schedule': initSchedule(); break;
    }
};

// --- Pre-fill Helpers ---

function initAnnouncement(data) {
    if (typeof openAddAnnouncementModal !== 'function') return;
    openAddAnnouncementModal();
    setTimeout(() => {
        const title = document.getElementById('announcementTitle');
        if (title) title.value = data.title || '';
        if (typeof announcementQuill !== 'undefined') {
            announcementQuill.root.innerHTML = data.content || '';
        }
    }, 500);
}

function initEvent(data) {
    if (typeof openAddEventModal !== 'function') return;
    openAddEventModal();
    setTimeout(() => {
        const name = document.getElementById('eventName');
        if (name) name.value = data.name || '';
        const date = document.getElementById('eventDate');
        if (date) date.value = data.date || '';
        const time = document.getElementById('eventTime');
        if (time) time.value = data.time || '';
    }, 500);
}

function initHomework(data) {
    if (typeof openAddHomeworkModal !== 'function') return;
    openAddHomeworkModal();
    setTimeout(() => {
        const title = document.getElementById('homeworkTitle');
        if (title) title.value = data.title || '';
        const date = document.getElementById('homeworkDate');
        if (date) date.value = data.date || '';
        if (typeof homeworkQuill !== 'undefined') {
            homeworkQuill.root.innerHTML = data.content || '';
        }
    }, 500);
}

function initContact(data) {
    const block = document.querySelector('[data-block-type="contacts"]');
    if (!block) return;
    if (typeof editBlock === 'function') {
        editBlock(parseInt(block.dataset.blockId));
    }
}

function initSchedule() {
    const block = document.querySelector('[data-block-type="schedule"]');
    if (!block) return;
    if (typeof editBlock === 'function') {
        editBlock(parseInt(block.dataset.blockId));
    }
}

function setLoading(btn, isLoading) {
    if (!btn) return;
    if (isLoading) {
        btn.disabled = true;
        btn._oldText = btn.textContent;
        btn.textContent = 'טוען...';
    } else {
        btn.disabled = false;
        btn.textContent = btn._oldText || btn.textContent;
    }
}
