/**
 * Editor Module - Handles content blocks, announcements and sorting.
 * Features:
 * - Rich text editing with Quill
 * - Drag & drop sorting with Sortable
 * - AI processing for schedules and documents
 * - Specialized block editors
 */

const EditorConfig = {
    ENDPOINTS: {
        ANNOUNCEMENTS: '/api/pages/{pageId}/announcements',
        BLOCKS: '/api/pages/{pageId}/blocks',
        AI_SCHEDULE: '/api/ai/extract-schedule',
        AI_DOCUMENT: '/api/ai/extract-document'
    },
    DAYS: ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    DAYS_HE: ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי'],
    REQUIRED_BLOCKS: ['calendar', 'whatsapp', 'links', 'contact_page', 'contacts']
};

let quillEditor = null;
let activeAnnouncementId = null;
let activeBlockId = null;

document.addEventListener('DOMContentLoaded', () => {
    initEditorPage();
});

/**
 * Initialize page components
 */
function initEditorPage() {
    // 1. Initialize Quill
    if (document.getElementById('announcementEditor')) {
        quillEditor = new Quill('#announcementEditor', {
            theme: 'snow',
            modules: { toolbar: [['bold', 'italic', 'underline'], [{ 'list': 'ordered'}, { 'list': 'bullet' }], ['link', 'image'], ['clean']] },
            placeholder: 'הזן הודעה...'
        });
    }

    // 2. Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === `${tab}-tab`));
        });
    });

    // 3. Sortable initialization
    initSorting();
}

/**
 * Initialize drag and drop sorting
 */
function initSorting() {
    const lists = [
        { id: 'announcements-list', type: 'announcements' },
        { id: 'blocks-list', type: 'blocks' }
    ];

    lists.forEach(list => {
        const el = document.getElementById(list.id);
        if (el) {
            new Sortable(el, {
            handle: '.drag-handle',
            animation: 150,
                onEnd: async () => {
                    const ids = Array.from(el.children).map(i => parseInt(i.dataset.id));
                    const payload = list.type === 'blocks' ? { block_ids: ids } : { announcement_ids: ids };
                    try {
                        await API.post(`/api/pages/${window.pageDbId}/${list.type}/reorder`, payload);
                    } catch (e) { console.error(e); }
            }
        });
    }
    });
}

// --- Announcement Functions ---

window.openAnnouncementEditor = function(id = null) {
    activeAnnouncementId = id;
    const modal = document.getElementById('announcementModal');
    if (!modal) return;

    // Reset fields
    document.getElementById('announcementTitle').value = '';
    document.getElementById('announcementDate').value = '';
    if (quillEditor) quillEditor.setContents([]);
    
    if (id) {
        API.get(`/api/pages/${window.pageDbId}/announcements/${id}`).then(res => {
            if (res.ok) {
                document.getElementById('announcementTitle').value = res.announcement.title || '';
                document.getElementById('announcementDate').value = res.announcement.date || '';
                quillEditor.root.innerHTML = res.announcement.html || '';
            }
        });
    }
    modal.style.display = 'block';
};

window.saveAnnouncement = async function() {
    const data = {
        title: document.getElementById('announcementTitle').value.trim(),
        date: document.getElementById('announcementDate').value,
        html: quillEditor.root.innerHTML
    };
    
    try {
        const url = `/api/pages/${window.pageDbId}/announcements` + (activeAnnouncementId ? `/${activeAnnouncementId}` : '');
        const method = activeAnnouncementId ? 'put' : 'post';
        const res = await API[method](url, data);
        if (res.ok) location.reload();
    } catch (e) { alert(e.message); }
};

window.deleteAnnouncement = async function(id) {
    if (!confirm('למחוק הודעה זו?')) return;
    try {
        const res = await API.delete(`/api/pages/${window.pageDbId}/announcements/${id}`);
        if (res.ok) location.reload();
    } catch (e) { alert(e.message); }
};

// --- Block Management ---

window.editBlock = function(id) {
    activeBlockId = id;
        const modal = document.getElementById('blockModal');
        const body = document.getElementById('blockModalBody');
    if (!modal || !body) return;

    API.get(`/api/pages/${window.pageDbId}/blocks/${id}`).then(res => {
        if (!res.ok) return alert(res.message_he);
        
        const block = res.block;
        body.innerHTML = `<h3>עריכת ${escapeHtml(block.title)}</h3>
            <div id="blockModalMessage"></div>
            <form id="blockEditForm" onsubmit="event.preventDefault(); window.saveActiveBlock(${id})">
                ${renderBlockSpecificFields(block)}
                <button type="submit" class="btn btn-primary" style="margin-top:1rem; width:100%">שמור שינויים</button>
            </form>`;
        modal.style.display = 'block';
    });
};

function renderBlockSpecificFields(block) {
    const data = block.data || {};
    switch (block.type) {
        case 'schedule': return renderScheduleEditor(data.schedule || {});
        case 'links':
        case 'whatsapp': return renderLinksEditor(data.links || []);
        case 'contacts': return renderContactsEditor(data.contacts || []);
        case 'calendar': return renderCalendarEditor(data.holidays || []);
        default: return `<p>סוג בלוק לא מוכר</p>`;
    }
}

// --- Specific Renderers ---

function renderScheduleEditor(schedule) {
    let html = `
        <div class="form-group">
            <label>חילוץ אוטומטי מתמונה</label>
            <input type="file" id="scheduleUpload" accept="image/*" onchange="window.processScheduleImage(this)">
            <div id="scheduleProcessingStatus" style="display:none">⏳ מעבד...</div>
            </div>
        <div id="dayLessonsList">`;
    
    EditorConfig.DAYS.forEach((day, i) => {
        html += `<div class="day-section">
            <label>יום ${EditorConfig.DAYS_HE[i]}</label>
            <div id="lessons-${day}" class="lessons-container">
                ${(schedule[day] || []).map((l, idx) => renderLessonRow(day, idx, l)).join('')}
            </div>
            <button type="button" class="btn-small" onclick="window.addLesson('${day}')">+ הוסף שיעור</button>
        </div>`;
    });
    
    html += `</div>`;
    return html;
}

function renderLessonRow(day, idx, lesson = {}) {
    return `<div class="lesson-row" data-day="${day}">
        <input type="text" placeholder="שעה" value="${lesson.time || ''}" class="inp-time">
        <input type="text" placeholder="מקצוע" value="${lesson.subject || ''}" class="inp-subject">
        <input type="text" placeholder="מורה" value="${lesson.teacher || ''}" class="inp-teacher">
        <button type="button" onclick="this.parentElement.remove()" class="btn-del">×</button>
    </div>`;
}

function renderLinksEditor(links) {
    return `<div id="linksList">
        ${links.map((l, i) => `
            <div class="link-row">
                <input type="text" name="title[]" value="${escapeHtml(l.title)}" placeholder="כותרת">
                <input type="url" name="url[]" value="${escapeHtml(l.url)}" placeholder="לינק">
                <button type="button" onclick="this.parentElement.remove()">×</button>
                    </div>
        `).join('')}
                </div>
    <button type="button" onclick="window.addLinkRow()">+ הוסף קישור</button>`;
}

// --- Global Actions for Forms ---

window.addLesson = function(day) {
    const container = document.getElementById(`lessons-${day}`);
    if (container) {
        const div = document.createElement('div');
        div.innerHTML = renderLessonRow(day, container.children.length);
        container.appendChild(div.firstChild);
    }
};

window.processScheduleImage = async function(input) {
    if (!input.files[0]) return;
    const status = document.getElementById('scheduleProcessingStatus');
    status.style.display = 'block';
    
    const formData = new FormData();
    formData.append('image', input.files[0]);
    formData.append('csrf_token', window.csrfToken);
    
    try {
        const res = await fetch('/api/ai/extract-schedule', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` },
            body: formData
        });
        const result = await res.json();
        if (result.ok) {
            // Update UI with extracted data
            Object.keys(result.schedule).forEach(day => {
                const container = document.getElementById(`lessons-${day}`);
                if (container) {
                    container.innerHTML = result.schedule[day].map((l, i) => renderLessonRow(day, i, l)).join('');
                }
            });
        } else { alert(result.message_he || 'נכשל בחילוץ'); }
    } catch (e) { alert(e.message); }
    finally { status.style.display = 'none'; }
};

window.saveActiveBlock = async function(id) {
    const form = document.getElementById('blockEditForm');
    const type = form.dataset.blockType;
    const data = {};
    
    if (type === 'schedule') {
        data.schedule = {};
        EditorConfig.DAYS.forEach(day => {
            data.schedule[day] = Array.from(document.querySelectorAll(`#lessons-${day} .lesson-row`)).map(row => ({
                time: row.querySelector('.inp-time').value,
                subject: row.querySelector('.inp-subject').value,
                teacher: row.querySelector('.inp-teacher').value
            }));
        });
    } else if (type === 'links' || type === 'whatsapp') {
        const rows = Array.from(document.querySelectorAll('#linksList .link-row'));
        data.links = rows.map(r => ({
            title: r.querySelector('[name="title[]"]').value,
            url: r.querySelector('[name="url[]"]').value
        }));
    }

    try {
        const res = await API.put(`/api/pages/${window.pageDbId}/blocks/${id}`, { data });
        if (res.ok) location.reload();
    } catch (e) { alert(e.message); }
};

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
