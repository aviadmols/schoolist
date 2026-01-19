// Public Page JavaScript

// Unified modal header creation function
function createModalHeader(title, closeCallback, useH2 = false) {
    const header = document.createElement('div');
    header.className = 'modal-header-unified';
    
    const titleEl = useH2 ? document.createElement('h2') : document.createElement('h3');
    titleEl.textContent = title;
    
    const closeBtn = document.createElement('button');
    closeBtn.className = 'modal-close-btn';
    closeBtn.onclick = closeCallback;
    closeBtn.title = 'סגור';
    closeBtn.innerHTML = '<img src="/assets/files/cross.svg" alt="סגור" style="height: 24px;">';
    
    // In RTL: title first (right), then close button (left)
    header.appendChild(titleEl);
    header.appendChild(closeBtn);
    
    return header;
}

// Function to get greeting based on Israel time
function getGreetingByTime() {
    // Get current time in Israel timezone (Asia/Jerusalem)
    const now = new Date();
    // Use Intl.DateTimeFormat to get time in Israel timezone
    const israelTime = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Jerusalem',
        hour: 'numeric',
        hour12: false
    });
    const hour = parseInt(israelTime.format(now));
    
    if (hour >= 5 && hour < 12) {
        return 'בוקר טוב';
    } else if (hour >= 12 && hour < 17) {
        return 'צהריים טובים';
    } else if (hour >= 17 && hour < 22) {
        return 'ערב טוב';
    } else {
        return 'לילה טוב';
    }
}

// Function to get class name for display
function getClassName() {
    if (typeof pageData === 'undefined' || !pageData) {
        return '';
    }
    
    const classGrade = pageData.class_grade || '';
    const classNumber = pageData.class_number || '';
    
    if (classGrade && classNumber) {
        return classGrade + "'" + classNumber;
    } else if (pageData.class_title) {
        return pageData.class_title;
    }
    
    return '';
}

// Update weather greeting text
function updateWeatherGreeting(isTomorrow = null) {
    const greetingTextEl = document.getElementById('weather-greeting-text');
    
    if (!greetingTextEl) return;
    
    // Determine if showing tomorrow's weather (from 16:00)
    if (isTomorrow === null) {
        const currentHour = new Date().getHours();
        isTomorrow = (currentHour >= 16);
    }
    
    // Build weather text: "המזג האוויר היום" or "המזג האוויר מחר"
    const greetingText = isTomorrow ? 'מחר' : 'היום';
    
    greetingTextEl.textContent = greetingText;
}

// Get weather recommendation based on temperature
function getWeatherRecommendation(temperature, isRaining = false) {
    if (isRaining) {
        return 'מעיל גשם, מגפיים ומטריה.';
    }
    
    if (temperature >= 26) {
        return 'חולצה קצרה, כובע ובקבוק מים.';
    } else if (temperature >= 20) {
        return 'חולצה קצרה ומכנסיים דקים (אפשר סריג דק לשעות הבוקר).';
    } else if (temperature >= 15) {
        return 'חולצה ארוכה דקה, או חולצה קצרה עם קפוצ\'ון מעל.';
    } else if (temperature >= 10) {
        return 'מכנסיים ארוכים עבים, סווטשירט חם ומעיל.';
    } else {
        return 'התעטף היטב – מעיל חם, צעיף, כפפות ומכנסי פוטר.';
    }
}

// Fetch weather data for Tel Aviv
async function updateWeatherRecommendation() {
    const recommendationEl = document.getElementById('weather-recommendation');
    if (!recommendationEl) return;
    
    try {
        // Use backend endpoint to protect API key
        const baseUrl = typeof API !== 'undefined' && API.baseUrl ? API.baseUrl : '/';
        const url = baseUrl.replace(/\/$/, '') + '/api/weather/tel-aviv';
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error('Failed to fetch weather');
        }
        
        const data = await response.json();
        
        if (data.ok && data.weather) {
            const temp = Math.round(data.weather.temp);
            const tempMin = data.weather.tempMin ? Math.round(data.weather.tempMin) : null;
            const tempMax = data.weather.tempMax ? Math.round(data.weather.tempMax) : null;
            const isRaining = data.weather.rain || data.weather.weatherMain === 'Rain';
            const isTomorrow = data.weather.isTomorrow || false;
            
            // Update weather greeting text
            updateWeatherGreeting(isTomorrow);
            
            // Update temperature display
            const tempEl = document.getElementById('weather-temperature');
            if (tempEl) {
                if (tempMin !== null && tempMax !== null && tempMin !== tempMax) {
                    tempEl.textContent = `${tempMin}-${tempMax}°`;
                } else {
                    tempEl.textContent = `${temp}°`;
                }
            }
            
            const recommendation = getWeatherRecommendation(temp, isRaining);
            recommendationEl.textContent = recommendation;
        } else {
            // Fallback: determine if tomorrow based on current hour
            const currentHour = new Date().getHours();
            const isTomorrow = (currentHour >= 16);
            updateWeatherGreeting(isTomorrow);
            recommendationEl.textContent = 'חולצה קצרה ומכנסיים דקים (אפשר סריג דק לשעות הבוקר).';
        }
    } catch (error) {
        console.error('Weather fetch error:', error);
        // Fallback to default recommendation
        recommendationEl.textContent = 'חולצה קצרה ומכנסיים דקים (אפשר סריג דק לשעות הבוקר).';
    }
}

// Function to check if homework content is empty (only <br> tags)
function isEmptyHomeworkContent(html) {
    if (!html) return true;
    const trimmed = html.trim();
    if (!trimmed) return true;
    // Check for various empty patterns
    return trimmed === '<br>' || 
           trimmed === '<br/>' || 
           trimmed === '<br />' ||
           /^<p>\s*<br\s*\/?>\s*<\/p>$/i.test(trimmed) ||
           /^<p><br\s*\/?><\/p>$/i.test(trimmed);
}

// Hide homework content if it's empty
function hideEmptyHomeworkContent() {
    const homeworkContents = document.querySelectorAll('.homework-content');
    homeworkContents.forEach(content => {
        const html = content.innerHTML.trim();
        if (isEmptyHomeworkContent(html)) {
            content.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Update weather recommendation
    updateWeatherRecommendation();
    
    // Debug: Log admin status
    console.log('isPageAdmin:', typeof isPageAdmin !== 'undefined' ? isPageAdmin : 'undefined');
    console.log('pageDbId:', typeof pageDbId !== 'undefined' ? pageDbId : 'undefined');
    
    // Debug: Check if admin controls are visible
    const editControls = document.querySelectorAll('.block-edit-controls');
    const floatingButton = document.querySelector('.floating-add-block');
    console.log('Edit controls found:', editControls.length);
    console.log('Floating add button found:', floatingButton ? 'yes' : 'no');
    
    // Weather greeting is updated automatically in updateWeatherRecommendation()
    
    // Hide empty homework content
    hideEmptyHomeworkContent();
});

function toggleBlock(cardElement) {
    const accordion = cardElement.closest('.block-accordion');
    if (!accordion) return;
    
    // Close any open menus
    document.querySelectorAll('.block-menu-popup').forEach(menu => {
        menu.classList.remove('active');
    });
    
    // Get block info
    const blockType = accordion.getAttribute('data-block-type');
    const blockId = accordion.getAttribute('data-block-id');
    
    if (!blockId) {
        console.error('Block ID missing from accordion');
        return;
    }
    
    // Open all blocks as modal
    openBlockModal(blockId, blockType);
}

// Open block as modal
async function openBlockModal(blockId, blockType) {
    if (typeof pageDbId === 'undefined' || typeof API === 'undefined') {
        console.error('API or pageDbId not available');
        return;
    }
    
    
    try {
        const result = await API.get(`/api/pages/${pageDbId}/blocks/${blockId}`);
        
        if (!result || !result.ok || !result.block) {
            alert('שגיאה בטעינת בלוק: ' + (result?.message_he || 'בלוק לא נמצא'));
            return;
        }
        
        const block = result.block;
        
        // Get or create modal
        let modal = document.getElementById('blockViewModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'blockViewModal';
            modal.className = 'announcement-modal-fullscreen';
            const header = createModalHeader(block.title || 'בלוק', closeBlockViewModal, true);
            header.querySelector('h2').id = 'blockViewModalTitle';
            
            modal.innerHTML = `
                <div class="announcement-modal-container" onclick="event.stopPropagation()">
                    <div class="announcement-modal-body" id="blockViewModalBody">
                    </div>
                </div>
            `;
            modal.querySelector('.announcement-modal-container').insertBefore(header, modal.querySelector('.announcement-modal-container').firstChild);
            document.body.appendChild(modal);
        }
        
        // Update title
        const titleEl = document.getElementById('blockViewModalTitle');
        if (titleEl) {
            titleEl.textContent = block.title || 'בלוק';
        }
        
        // Render block content
        const body = document.getElementById('blockViewModalBody');
        if (body) {
            let html = '';
            
            switch (block.type) {
                case 'schedule':
                    html += renderSchedule(block.data, blockId, block.type);
                    break;
                case 'contacts':
                    html += renderContacts(block.data, blockId, block.type);
                    break;
                case 'whatsapp':
                    html += renderWhatsApp(block.data, blockId, block.type);
                    break;
                case 'links':
                    html += renderLinks(block.data, blockId, block.type);
                    break;
                case 'calendar':
                    html += renderCalendar(block.data, blockId, block.type);
                    break;
                case 'contact_page':
                    html += renderContactPage(block.data, blockId, block.type);
                    break;
                default:
                    html += '<p>תוכן לא זמין</p>';
            }
            
            body.innerHTML = html;
        }
        
        // Open modal
        openModalWithAnimation('blockViewModal');
        
    } catch (error) {
        console.error('Error loading block:', error);
        alert('שגיאה בטעינת בלוק: ' + error.message);
    }
}

function closeBlockViewModal() {
    closeModalWithAnimation('blockViewModal');
}

function renderSchedule(data, blockId = null, blockType = null) {
    if (!data || !data.schedule) {
        let html = '<p>אין מערכת שעות זמינה</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div style="margin-top: 1rem; text-align: center;">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});" style="padding: 0.75rem 1.5rem; border-radius: 8px;">
                    הוסף מערכת שעות
                </button>
            </div>`;
        }
        return html;
    }
    
    const schedule = data.schedule;
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    const dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי'];
    const lessonNumbers = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שביעי', 'שמיני', 'תשיעי', 'עשירי'];
    
    function getLessonNumber(index) {
        return lessonNumbers[index] || `שיעור ${index + 1}`;
    }
    
    let html = '<div class="schedule-days-container">';
    
    // Create a separate table for each day
    dayNames.forEach((day, dayIndex) => {
        const dayLessons = schedule[day] || [];
        
        // Skip days with no lessons
        if (dayLessons.length === 0) {
            return;
        }
        
        html += '<div class="schedule-day-table-wrapper">';
        html += `<div class="schedule-day-title">יום ${dayNamesHe[dayIndex]}</div>`;
        html += '<table class="schedule-day-table">';
        
        // Header row
        html += '<thead><tr>';
        html += '<th class="lesson-number-header">שיעור</th>';
        html += '<th class="lesson-content-header">שיעור</th>';
        html += '</tr></thead>';
        
        // Body rows - one row per lesson
        html += '<tbody>';
        dayLessons.forEach((lesson, lessonIndex) => {
            const lessonNum = getLessonNumber(lessonIndex);
            html += '<tr>';
            html += `<td class="lesson-number">${lessonNum}</td>`;
            html += '<td class="lesson-cell">';
            if (lesson) {
                html += '<div class="lesson-content">';
                if (lesson.subject) {
                    html += `<div class="lesson-subject">${escapeHtml(lesson.subject)}</div>`;
                }
                if (lesson.teacher || lesson.room) {
                    html += '<div class="lesson-details">';
                    if (lesson.teacher) {
                        html += `<span class="lesson-teacher">${escapeHtml(lesson.teacher)}</span>`;
                    }
                    if (lesson.room) {
                        html += `<span class="lesson-room">חדר ${escapeHtml(lesson.room)}</span>`;
                    }
            html += '</div>';
        }
                html += '</div>';
            } else {
                html += '<span class="lesson-empty">-</span>';
            }
            html += '</td>';
            html += '</tr>';
        });
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
    });
    
    html += '</div>';
    
    if (html === '<div class="schedule-days-container"></div>') {
        return '<p>אין מערכת שעות זמינה</p>';
    }
    
    return html;
}

function renderContacts(data, blockId = null, blockType = null) {
    if (!data || !data.contacts || data.contacts.length === 0) {
        let html = '<p>אין אנשי קשר זמינים</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div style="margin-top: 1rem; text-align: center;">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});" style="padding: 0.75rem 1.5rem; border-radius: 8px;">
                    הוסף איש קשר
                </button>
            </div>`;
        }
        return html;
    }
    
    let html = '<div class="contacts-list">';
    data.contacts.forEach((contact, index) => {
        const name = contact.name || contact.child_name || '';
        const role = contact.role || '';
        const phone = contact.phone || contact.parent_phone || '';
        const nameEncoded = encodeURIComponent(name);
        const roleEncoded = encodeURIComponent(role || '');
        const phoneEncoded = encodeURIComponent(phone || '');
        
        html += `<div class="contact-item" data-contact-index="${index}">
            <div class="contact-info">
                <div class="contact-name"><strong>${escapeHtml(name)}</strong></div>
                ${role ? `<div class="contact-role">${escapeHtml(role)}</div>` : ''}
                ${phone ? `<div class="contact-phone">${escapeHtml(phone)}</div>` : ''}
            </div>
            ${phone ? `<button class="btn-add-to-contacts" 
                data-contact-name="${nameEncoded}" 
                data-contact-role="${roleEncoded}" 
                data-contact-phone="${phoneEncoded}" 
                onclick="addContactToPhone(this)" 
                title="הוסף לטלפון">
                <img src="/assets/files/user-add.svg" alt="הוסף לטלפון" style="width: 20px; height: 20px;">
            </button>` : ''}
        </div>`;
    });
    html += '</div>';
    return html;
}

function renderContactPage(data, blockId = null, blockType = null) {
    if (!data || !data.children || data.children.length === 0) {
        let html = '<p>אין ילדים רשומים</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div style="margin-top: 1rem; text-align: center;">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});" style="padding: 0.75rem 1.5rem; border-radius: 8px;">
                    הוסף ילד
                </button>
            </div>`;
        }
        return html;
    }
    
    let html = '<div class="contact-page-content">';
    
    data.children.forEach((child, childIndex) => {
        const childName = child.name || '';
        const birthDate = child.birth_date || '';
        const parents = child.parents || [];
        
        html += `<div class="child-contact-item">`;
        html += `<div class="child-contact-header" onclick="toggleChildContactDropdown(this)">`;
        html += `<div class="child-contact-info">`;
        html += `<h4 class="child-contact-name">${escapeHtml(childName)}</h4>`;
        if (birthDate) {
            // Format date from YYYY-MM-DD to DD/MM/YYYY
            const dateParts = birthDate.split('-');
            const formattedDate = dateParts.length === 3 ? `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}` : birthDate;
            html += `<div class="child-contact-birth">${escapeHtml(formattedDate)}</div>`;
        }
        html += `</div>`;
        html += `<button class="child-contact-dropdown-arrow" onclick="event.stopPropagation(); toggleChildContactDropdown(this.closest('.child-contact-header'))">`;
        html += `<img src="/assets/files/angle-small-down.svg" alt="הצג" class="dropdown-icon">`;
        html += `</button>`;
        html += `</div>`;
        
        html += `<div class="child-contact-dropdown-content">`;
        if (parents.length > 0) {
            html += `<div class="parents-list">`;
            parents.forEach((parent, parentIndex) => {
                const parentName = parent.name || '';
                const parentPhone = parent.phone || '';
                const parentRole = parent.role || 'אבא';
                const roleText = parentRole === 'אמא' ? 'אמא' : 'אבא';
                
                html += `<div class="parent-contact-item">`;
                html += `<div class="parent-contact-info">`;
                html += `<div class="parent-contact-name-role">${escapeHtml(parentName)} - ${roleText}</div>`;
                if (parentPhone) {
                    html += `<a href="tel:${escapeHtml(parentPhone)}" class="parent-contact-phone">${escapeHtml(parentPhone)}</a>`;
                }
                html += `</div>`;
                html += `<div class="parent-contact-actions">`;
                if (parentPhone) {
                    // WhatsApp button
                    const phoneForWhatsApp = parentPhone.replace(/[^0-9]/g, ''); // Remove non-numeric characters
                    html += `<a href="https://wa.me/${phoneForWhatsApp}" target="_blank" class="btn-open-whatsapp" title="פתח וואטסאפ">`;
                    html += `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">`;
                    html += `<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" fill="currentColor"/>`;
                    html += `</svg>`;
                    html += `</a>`;
                }
                if (parentPhone && parentName) {
                    const parentNameEncoded = encodeURIComponent(parentName);
                    const parentPhoneEncoded = encodeURIComponent(parentPhone);
                    const roleEncoded = encodeURIComponent(roleText);
                    const childNameEncoded = encodeURIComponent(childName);
                    html += `<button class="btn-add-parent-to-contacts" onclick="addParentToContacts(this)" data-parent-name="${parentNameEncoded}" data-parent-phone="${parentPhoneEncoded}" data-parent-role="${roleEncoded}" data-child-name="${childNameEncoded}" title="הוסף לאנשי קשר">`;
                    html += `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">`;
                    html += `<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;
                    html += `<circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;
                    html += `<path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;
                    html += `<path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;
                    html += `</svg>`;
                    html += `</button>`;
                }
                html += `</div>`;
                html += `</div>`;
            });
            html += `</div>`;
        } else {
            html += `<div class="parents-empty-message">אין הורים רשומים</div>`;
        }
        html += `</div>`;
        html += `</div>`;
    });
    
    html += '</div>';
    return html;
}

function addParentToContacts(button) {
    const name = decodeURIComponent(button.getAttribute('data-parent-name') || '');
    const phone = decodeURIComponent(button.getAttribute('data-parent-phone') || '');
    const role = decodeURIComponent(button.getAttribute('data-parent-role') || 'אבא');
    const childName = decodeURIComponent(button.getAttribute('data-child-name') || '');
    
    if (!name && !phone) {
        alert('אין פרטים זמינים להוספה');
        return;
    }
    
    // Create vCard content
    const vCardLines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        `FN:${name}`,
        `TEL;TYPE=CELL:${phone}`,
        `NOTE:${role} של ${childName || ''}`,
        'END:VCARD'
    ];
    
    const vCardContent = vCardLines.join('\n');
    const blob = new Blob([vCardContent], { type: 'text/vcard' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `${name}.vcf`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

function addContactToPhone(button) {
    const name = decodeURIComponent(button.getAttribute('data-contact-name') || '');
    const role = decodeURIComponent(button.getAttribute('data-contact-role') || '');
    const phone = decodeURIComponent(button.getAttribute('data-contact-phone') || '');
    
    if (!name && !phone) {
        alert('אין פרטים זמינים להוספה');
        return;
    }
    
    // Create vCard content
    const vCardLines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        `FN:${name || 'איש קשר'}`,
        `N:${name || 'איש קשר'};;;`
    ];
    
    if (role) {
        vCardLines.push(`TITLE:${role}`);
    }
    
    if (phone) {
        // Remove spaces and special characters from phone
        const cleanPhone = phone.replace(/[\s\-\(\)]/g, '');
        vCardLines.push(`TEL;TYPE=CELL:${cleanPhone}`);
    }
    
    vCardLines.push('END:VCARD');
    
    const vCard = vCardLines.join('\r\n');
    
    // Create blob and download
    const blob = new Blob([vCard], { type: 'text/vcard;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const fileName = (name || 'contact').replace(/[^a-z0-9א-ת]/gi, '_') + '.vcf';
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function renderWhatsApp(data, blockId = null, blockType = null) {
    if (!data || !data.links || data.links.length === 0) {
        let html = '<p>אין קבוצות זמינות</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div style="margin-top: 1rem; text-align: center;">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});" style="padding: 0.75rem 1.5rem; border-radius: 8px;">
                    הוסף קבוצה
                </button>
            </div>`;
        }
        return html;
    }
    
    let html = '<div class="links-list">';
    data.links.forEach(link => {
        html += `<a href="${escapeHtml(link.url || '#')}" target="_blank" class="btn btn-primary" style="display: block; margin-bottom: 0.5rem;">
            ${escapeHtml(link.title || 'קישור')}
        </a>`;
    });
    html += '</div>';
    return html;
}

function renderLinks(data, blockId = null, blockType = null) {
    if (!data || !data.links || data.links.length === 0) {
        let html = '<p>אין קישורים זמינים</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div style="margin-top: 1rem; text-align: center;">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});" style="padding: 0.75rem 1.5rem; border-radius: 8px;">
                    הוסף קישור
                </button>
            </div>`;
        }
        return html;
    }
    return renderWhatsApp(data, blockId, blockType); // Same structure
}

function renderCalendar(data, blockId = null, blockType = null) {
    if (!data || !data.holidays || !Array.isArray(data.holidays) || data.holidays.length === 0) {
        let html = '<p class="calendar-empty-message">אין חופשות וחגים זמינים</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div class="calendar-empty-actions">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});">
                    הוסף חופשה/חג
                </button>
            </div>`;
        }
        return html;
    }
    
    // Get today's date (set to start of day for comparison)
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayStr = today.toISOString().split('T')[0];
    
    // Normalize holidays - handle both old format (date) and new format (start_date/end_date)
    const normalizedHolidays = data.holidays.map(holiday => {
        const startDate = holiday.start_date || holiday.date || '';
        const endDate = holiday.end_date || '';
        const name = holiday.name || '';
        const hasCamp = holiday.has_camp || false;
        
        // Return as range or single date
        return {
            start_date: startDate,
            end_date: endDate,
                    name: name,
            has_camp: hasCamp
        };
    });
    
    // Filter only future holidays (end date must be today or later)
    const futureHolidays = normalizedHolidays.filter(holiday => {
        const endDate = holiday.end_date || holiday.start_date || '';
        if (!endDate) return false;
        const holidayEndDate = new Date(endDate + 'T00:00:00');
        return holidayEndDate >= today;
    });
    
    if (futureHolidays.length === 0) {
        let html = '<p class="calendar-empty-message">אין חופשות וחגים עתידיים</p>';
        if (typeof isPageAdmin !== 'undefined' && isPageAdmin && blockId) {
            html += `<div class="calendar-empty-actions">
                <button class="btn btn-primary" onclick="closeBlockViewModal(); editBlock(${blockId});">
                    הוסף חופשה/חג
                </button>
            </div>`;
        }
        return html;
    }
    
    // Sort by start date (ascending - closest first)
    futureHolidays.sort((a, b) => {
        const dateA = a.start_date || '';
        const dateB = b.start_date || '';
        return dateA.localeCompare(dateB);
    });
    
    // Build table HTML
    let html = '<div class="calendar-table-wrapper">';
    html += '<table class="calendar-table">';
    html += '<thead>';
    html += '<tr>';
    html += '<th class="calendar-th-date title-section">תאריך</th>';
    html += '<th class="calendar-th-name title-section">שם החג/חופשה</th>';
    html += '<th class="calendar-th-camp title-section">קייטנה</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    futureHolidays.forEach(holiday => {
        const startDate = holiday.start_date || '';
        const endDate = holiday.end_date || '';
        const name = holiday.name || '';
        const hasCamp = holiday.has_camp || false;
        
        if (!startDate) return;
        
        // Format date display
        const start = new Date(startDate + 'T00:00:00');
        let dateDisplay = '';
        
        if (endDate && endDate !== startDate) {
            // Date range
            const end = new Date(endDate + 'T00:00:00');
            const startFormatted = start.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const endFormatted = end.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
            dateDisplay = `${startFormatted} - ${endFormatted}`;
            } else {
            // Single date
            dateDisplay = start.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        
        html += '<tr class="calendar-row">';
        html += `<td class="calendar-td-date">${dateDisplay}</td>`;
        html += `<td class="calendar-td-name">${escapeHtml(name)}</td>`;
        html += `<td class="calendar-td-camp">${hasCamp ? '🎉 יש קייטנה' : '-'}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    return html;
}

function sharePage() {
    const headerTitleEl = document.querySelector('.page-header h1');
    const headerSubtitleEl = document.querySelector('.page-header h2');

    const title = headerTitleEl ? headerTitleEl.textContent : document.title;
    const text = headerSubtitleEl ? headerSubtitleEl.textContent : '';

    if (navigator.share) {
        navigator.share({
            title,
            text,
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('הקישור הועתק ללוח');
        });
    }
}

// Universal modal functions with animations
function openModalWithAnimation(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const isFullscreenModal = modal.classList.contains('announcement-modal-fullscreen');
    const isBlockEditModal = modal.classList.contains('block-edit-modal-fullscreen');
    const isBirthdayModal = modal.classList.contains('birthday-modal');
    
    if (isFullscreenModal || isBlockEditModal) {
        // For fullscreen modals, use ::before pseudo-element as overlay
        // Close modal when clicking on the modal itself (but not on the container)
        if (!modal.hasAttribute('data-overlay-attached')) {
            modal.setAttribute('data-overlay-attached', 'true');
            modal.addEventListener('click', function(e) {
                // Close if clicking on the modal background (not on the container)
                if (e.target === modal || e.target.classList.contains('announcement-modal-overlay')) {
                    closeModalWithAnimation(modalId);
                }
            });
        }
    } else if (isBirthdayModal) {
        // For birthday modal, ensure overlay exists and is clickable
        let overlay = modal.querySelector('.birthday-modal-overlay');
        if (overlay && !overlay.hasAttribute('data-click-attached')) {
            overlay.setAttribute('data-click-attached', 'true');
            overlay.addEventListener('click', function() {
                closeModalWithAnimation(modalId);
            });
        }
    } else {
        // For regular modals, ensure modal has overlay
        let overlay = modal.querySelector('.modal-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            modal.insertBefore(overlay, modal.firstChild);
            
            // Close modal when clicking overlay
            overlay.addEventListener('click', function() {
                closeModalWithAnimation(modalId);
            });
        }
        
        // Ensure modal-content exists
        let modalContent = modal.querySelector('.modal-content');
        if (!modalContent) {
            // Find the content element (could be modal-content or other)
            modalContent = modal.querySelector('[class*="modal"]:not(.modal-overlay)') || modal.children[modal.children.length - 1];
            if (modalContent && !modalContent.classList.contains('modal-content')) {
                modalContent.classList.add('modal-content');
            }
        }
    }
    
    // Remove closing class if exists
    modal.classList.remove('closing');
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Trigger animation
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function closeModalWithAnimation(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    // Add closing class for animation
    modal.classList.add('closing');
    modal.classList.remove('show');
    
    // Start overlay fadeOut immediately
    const overlay = modal.querySelector('.modal-overlay') || 
                    modal.querySelector('.birthday-modal-overlay') || 
                    modal.querySelector('.floating-add-menu-overlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease';
    }
    
    // For fullscreen modals with ::before pseudo-element
    if (modal.classList.contains('announcement-modal-fullscreen') || 
        modal.classList.contains('block-edit-modal-fullscreen')) {
        // The ::before pseudo-element will fade out via CSS
    }
    
    // Wait for animation to complete
    setTimeout(() => {
        modal.style.display = 'none';
        modal.classList.remove('closing');
        document.body.style.overflow = '';
        if (overlay) {
            overlay.style.animation = '';
        }
    }, 300);
}

// Add block functionality for page admins
function openAddBlockModal() {
    openModalWithAnimation('addBlockModal');
    // Reset form
    const form = document.getElementById('addBlockForm');
    if (form) {
        form.reset();
    }
}

function closeModal(modalId) {
    closeModalWithAnimation(modalId);
}

if (typeof isPageAdmin !== 'undefined' && isPageAdmin && typeof pageDbId !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize drag and drop for blocks
        if (typeof Sortable !== 'undefined') {
            const blocksSection = document.getElementById('blocks-section');
            if (blocksSection) {
                // Allow dragging only by the drag handle (not by the entire card to avoid conflict with toggle)
                const sortable = new Sortable(blocksSection, {
                    handle: '.drag-handle-block',
                    filter: '.btn-edit-block, .btn-delete-block, .block-card, .block-edit-controls, .block-icon, .block-content, .block-chevron',
                    preventOnFilter: true,
                    animation: 200,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    forceFallback: false,
                    draggable: '.block-accordion',
                    swapThreshold: 0.65,
                    onStart: function(evt) {
                        console.log('Drag started', evt);
                        // Prevent toggle when dragging
                        evt.item.style.pointerEvents = 'none';
                        evt.item.classList.add('is-dragging');
                    },
                    onEnd: async function(evt) {
                        console.log('Drag ended', evt);
                        // Restore pointer events
                        evt.item.style.pointerEvents = '';
                        evt.item.classList.remove('is-dragging');
                        
                        const oldIndex = evt.oldIndex;
                        const newIndex = evt.newIndex;
                        
                        if (oldIndex === newIndex) {
                            console.log('No change in position');
                            return;
                        }
                        
                        // Get all block IDs in new order
                        const blockAccordions = Array.from(blocksSection.querySelectorAll('.block-accordion'));
                        const blockIds = blockAccordions.map(accordion => {
                            const blockId = accordion.getAttribute('data-block-id');
                            const parsed = parseInt(blockId);
                            console.log('Block ID:', blockId, 'Parsed:', parsed);
                            return parsed;
                        }).filter(id => !isNaN(id));
                        
                        console.log('Block IDs:', blockIds);
                        
                        if (blockIds.length === 0) {
                            console.error('No block IDs found');
                            window.location.reload();
                            return;
                        }
                        
                        try {
                            if (typeof API === 'undefined') {
                                console.error('API is not defined');
                                window.location.reload();
                                return;
                            }
                            
                            console.log('Sending reorder request:', { pageDbId, blockIds });
                            const result = await API.post(`/api/pages/${pageDbId}/blocks/reorder`, {
                                block_ids: blockIds,
                                csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
                            });
                            
                            console.log('Reorder result:', result);
                            
                            if (result && result.ok) {
                                // Success - no need to reload, order is already updated visually
                                console.log('Blocks reordered successfully');
                            } else {
                                console.error('Error reordering blocks:', result);
                                // Revert the order
                                window.location.reload();
                            }
                        } catch (error) {
                            console.error('Error reordering blocks:', error);
                            // Revert the order
                            window.location.reload();
                        }
                    }
                });
                
                console.log('Sortable initialized for blocks-section');
            }
        }
        
        // Block creation form handler removed - block creation is no longer available
    });
}

// Edit block function - opens modal editor in frontend
let currentBlockId = null;
const DAY_NAMES = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
const DAY_NAMES_HE = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי'];

async function editBlock(blockId) {
    if (typeof pageDbId === 'undefined') {
        console.error('pageDbId is not defined');
        return;
    }
    
    // Close any open menus
    document.querySelectorAll('.block-menu-popup').forEach(menu => {
        menu.classList.remove('active');
    });
    
    currentBlockId = blockId;
    await loadBlockEditor(blockId);
}

async function loadBlockEditor(id) {
    try {
        const result = await API.get(`/api/pages/${pageDbId}/blocks/${id}`);
        
        if (!result || !result.ok || !result.block) {
            alert('שגיאה בטעינת בלוק: ' + (result?.message_he || 'בלוק לא נמצא'));
            return;
        }
        
        const block = result.block;
        const modal = document.getElementById('blockEditModal');
        const body = document.getElementById('blockEditModalBody');
        
        if (!modal || !body) {
            // Create modal if it doesn't exist
            createBlockEditModal();
            return loadBlockEditor(id); // Retry after creating modal
        }
        
        // Update title
        const titleEl = document.getElementById('blockEditModalTitle');
        if (titleEl) {
            titleEl.textContent = `עריכת ${escapeHtml(block.title || 'בלוק')}`;
        }
        
        let html = `<div id="blockEditMessage" class="block-edit-message"></div>`;
        html += `<form id="blockEditForm" class="block-editor-form" data-block-type="${block.type}">`;
        
        switch (block.type) {
            case 'schedule':
                html += renderScheduleEditor(block);
                break;
            case 'contacts':
                html += renderContactsEditor(block);
                break;
            case 'whatsapp':
            case 'links':
                html += renderLinksEditor(block);
                break;
            case 'calendar':
                html += renderCalendarEditor(block);
                break;
            case 'contact_page':
                html += renderContactPageEditor(block);
                break;
        }
        
        html += `</form>`;
        
        body.innerHTML = html;
        openModalWithAnimation('blockEditModal');
        
        // Scroll to top
        modal.scrollTop = 0;
        
        // Load contacts/children/schedule/holidays/links after DOM is ready
        if (block.type === 'contacts') {
            setTimeout(() => {
                loadContacts(block);
            }, 100);
        } else if (block.type === 'contact_page') {
            setTimeout(() => {
                loadChildren(block);
            }, 100);
        } else if (block.type === 'schedule') {
            setTimeout(() => {
                loadSelectedDays();
            }, 100);
        } else if (block.type === 'calendar') {
            setTimeout(() => {
                loadHolidays(block);
            }, 100);
        } else if (block.type === 'links' || block.type === 'whatsapp') {
            setTimeout(() => {
                loadLinks(block);
            }, 100);
        }
        
        // Attach form handler
        const form = document.getElementById('blockEditForm');
        
        // Store block data in form dataset for schedule editor (before clone)
        if (form && block.data) {
            form.dataset.blockData = JSON.stringify(block.data);
            if (block.type === 'schedule') {
                console.log('loadBlockEditor: Stored block data before clone', block.data);
            }
        }
        
        if (form) {
            // Remove existing handlers
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            
            // Add new handler
            const updatedForm = document.getElementById('blockEditForm');
            if (updatedForm) {
                // Restore block data after clone (important for schedule editor)
                if (block.data) {
                    updatedForm.dataset.blockData = JSON.stringify(block.data);
                    if (block.type === 'schedule') {
                        console.log('loadBlockEditor: Restored block data after clone', block.data);
                    }
                }
                
                updatedForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    await saveBlock(id);
                });
                
                // For schedule blocks, trigger loadSelectedDays after form is ready
                if (block.type === 'schedule') {
                    setTimeout(() => {
                        console.log('loadBlockEditor: Triggering loadSelectedDays for schedule block');
                        loadSelectedDays();
                    }, 150);
                }
                
                // For calendar blocks, load holidays after form is ready
                if (block.type === 'calendar') {
                    setTimeout(() => {
                        loadHolidays(block);
                    }, 150);
                }
                
                // For contact_page blocks, load children after form is ready
                if (block.type === 'contact_page') {
                    setTimeout(() => {
                        loadChildren(block);
                    }, 150);
                }
            }
        }
        
        // Update save button to use the correct block ID
        const saveBtn = document.querySelector('.btn-block-edit-save');
        if (saveBtn) {
            saveBtn.onclick = function() {
                const form = document.getElementById('blockEditForm');
                if (form) {
                    form.requestSubmit();
                }
            };
        }
    } catch (error) {
        console.error('Error loading block editor:', error);
        alert('שגיאה בטעינת בלוק: ' + error.message);
    }
}

function createBlockEditModal() {
    // Remove existing modal if any
    const existing = document.getElementById('blockEditModal');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.id = 'blockEditModal';
    modal.className = 'block-edit-modal-fullscreen';
    const header = createModalHeader('עריכת בלוק', closeBlockEditModal, true);
    header.querySelector('h2').id = 'blockEditModalTitle';
    
    modal.innerHTML = `
        <div class="block-edit-modal-container">
            <div class="block-edit-modal-body" id="blockEditModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="block-edit-modal-footer">
                <button type="button" class="btn-block-edit-cancel" onclick="closeBlockEditModal()">ביטול</button>
                <button type="button" class="btn-block-edit-save" onclick="document.getElementById('blockEditForm')?.requestSubmit()">שמירה</button>
            </div>
        </div>
    `;
    modal.querySelector('.block-edit-modal-container').insertBefore(header, modal.querySelector('.block-edit-modal-container').firstChild);
    document.body.appendChild(modal);
}

function closeBlockEditModal() {
    closeModalWithAnimation('blockEditModal');
    currentBlockId = null;
    
    // Clear form data
    const form = document.getElementById('blockEditForm');
    if (form) {
        form.reset();
    }
}

async function saveBlock(blockId) {
    try {
        const form = document.getElementById('blockEditForm');
        if (!form) {
            alert('טופס לא נמצא');
            return;
        }
        
        const blockType = form.dataset.blockType || '';
        let data = {};
        
        if (blockType === 'schedule') {
            data = collectScheduleData(form);
        } else if (blockType === 'contacts') {
            data = collectContactsData(form);
        } else if (blockType === 'whatsapp' || blockType === 'links') {
            data = collectLinksData(form);
        } else if (blockType === 'calendar') {
            data = collectCalendarData(form);
        } else if (blockType === 'contact_page') {
            data = collectContactPageData(form);
        }
        
        const messageEl = document.getElementById('blockEditMessage');
        if (messageEl) {
            messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-blue); color: white; border-radius: 8px; text-align: center;">שומר...</div>';
        }
        
        const result = await API.put(`/api/pages/${pageDbId}/blocks/${blockId}`, {
            data: data,
            csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
        });
        
        if (result && result.ok) {
            if (messageEl) {
                messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px; text-align: center;">✅ הבלוק נשמר בהצלחה!</div>';
            }
            setTimeout(() => {
                closeBlockEditModal();
                window.location.reload();
            }, 1000);
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בשמירה';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px; text-align: center;">❌ ${errorMsg}</div>`;
            } else {
                alert('שגיאה בשמירה: ' + errorMsg);
            }
        }
    } catch (error) {
        console.error('Error saving block:', error);
        const messageEl = document.getElementById('blockEditMessage');
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px; text-align: center;">❌ שגיאה: ${error.message}</div>`;
        } else {
            alert('שגיאה בשמירת בלוק: ' + error.message);
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========== Block Editor Functions ==========

function renderScheduleEditor(block) {
    const schedule = (block.data && block.data.schedule) || {};
    const dayNames = DAY_NAMES;
    const dayNamesHe = DAY_NAMES_HE;
    
    let html = `
        <div class="form-group" style="margin-top: 2rem;">
            <label>עריכה ידנית - בחר ימים לעריכה</label>
            <div id="daysCheckboxContainer" class="days-checkbox-container">
    `;
    
    dayNames.forEach((day, index) => {
        const hasData = schedule[day] && schedule[day].length > 0;
        html += `
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 1rem; background: ${hasData ? '#E3F2FD' : '#F5F5F5'}; border-radius: 8px; border: 2px solid ${hasData ? 'var(--color-blue)' : '#E0E0E0'}; transition: all 0.2s;">
                    <input type="checkbox" class="day-checkbox" value="${day}" onchange="loadSelectedDays()" ${hasData ? 'checked' : ''}>
                    <span>יום ${dayNamesHe[index]}</span>
                    ${hasData ? `<span style="color: var(--color-blue); font-size: 0.85rem;">(${schedule[day].length} שיעורים)</span>` : ''}
                </label>
        `;
    });
    
    html += `
            </div>
        </div>
        <div id="manualScheduleEditor" style="display: block; margin-top: 1rem;">
            <div id="dayLessonsList"></div>
        </div>
        <div class="form-group" style="margin-top: 2rem;">
            <label>העלה תמונת מערכת שעות</label>
            <div class="upload-area" onclick="document.getElementById('scheduleUpload').click()" style="border: 2px dashed var(--color-blue); border-radius: 12px; padding: 2rem; text-align: center; cursor: pointer; background: #F5F5F5;">
                <p>לחץ להעלאת תמונה או גרור לכאן</p>
                <input type="file" id="scheduleUpload" accept="image/*" style="display: none;" onchange="handleScheduleFileSelect(this)">
            </div>
            <div id="schedulePreview" style="margin-top: 1rem;"></div>
            <div id="scheduleProcessingStatus" style="display: none; margin-top: 1rem; padding: 1rem; background: #E3F2FD; border-radius: 12px; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <div class="spinner" style="width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid var(--color-blue); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <span style="font-weight: bold; color: var(--color-blue);">מעבד תמונה... זה עשוי לקחת כמה שניות</span>
                </div>
            </div>
        </div>
    `;
    
    // Load all days with data - will be called from loadBlockEditor after form is ready
    // This setTimeout is a fallback in case loadBlockEditor doesn't call it
    setTimeout(() => {
        const form = document.getElementById('blockEditForm');
        if (form && form.dataset.blockData) {
        loadSelectedDays();
        }
    }, 200);
    
    return html;
}

function loadSelectedDays() {
    const editor = document.getElementById('manualScheduleEditor');
    const list = document.getElementById('dayLessonsList');
    
    // Get all days with data (not just checked ones)
    const checkboxes = document.querySelectorAll('.day-checkbox');
    const selectedDays = Array.from(checkboxes)
        .filter(cb => {
            const day = cb.value;
            const form = document.getElementById('blockEditForm');
            if (form && form.dataset.blockData) {
                try {
                    const blockData = JSON.parse(form.dataset.blockData);
                    if (blockData && blockData.schedule && blockData.schedule[day] && blockData.schedule[day].length > 0) {
                        return true;
                    }
                } catch (e) {
                    console.error('Error parsing block data:', e);
                }
            }
            // Also check checked boxes
            return cb.checked;
        })
        .map(cb => cb.value);
    
    // If no days with data, show all checked days
    if (selectedDays.length === 0) {
        const checkedDays = Array.from(document.querySelectorAll('.day-checkbox:checked')).map(cb => cb.value);
        if (checkedDays.length === 0) {
            if (editor) editor.style.display = 'none';
            return;
        }
        selectedDays.push(...checkedDays);
    }
    
    if (!editor || !list) {
        console.error('loadSelectedDays: Editor elements not found');
        return;
    }
    
    editor.style.display = 'block';
    
    let schedule = {};
    
    const form = document.getElementById('blockEditForm');
    if (form) {
        if (form.dataset.blockData) {
        try {
            const blockData = JSON.parse(form.dataset.blockData);
            if (blockData && blockData.schedule) {
                schedule = blockData.schedule;
                    console.log('loadSelectedDays: Loaded schedule from form.dataset.blockData', schedule);
            }
        } catch (e) {
            console.error('Error parsing block data:', e);
        }
        } else {
            console.warn('loadSelectedDays: form.dataset.blockData not found, schedule will be empty');
        }
    } else {
        console.error('loadSelectedDays: Form not found');
    }
    
    list.innerHTML = '';
    
    selectedDays.forEach(day => {
        const dayIndex = DAY_NAMES.indexOf(day);
        const dayNameHe = dayIndex >= 0 ? DAY_NAMES_HE[dayIndex] : day;
        const dayLessons = (schedule[day] || []).filter(lesson => lesson && (lesson.time || lesson.subject));
        
        const processedLessons = dayLessons.map((lesson, index) => {
            const processedLesson = { ...lesson };
            if (processedLesson.time === 'Unknown' || processedLesson.time === '') {
                processedLesson.time = String(index + 1);
            }
            return processedLesson;
        });
        
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-editor-header';
        dayHeader.textContent = `יום ${dayNameHe}`;
        list.appendChild(dayHeader);
        
        // Create lessons container for this day with sortable
        const lessonsContainer = document.createElement('div');
        lessonsContainer.className = 'lessons-container';
        lessonsContainer.dataset.day = day;
        lessonsContainer.id = `lessons-container-${day}`;
        
        if (processedLessons.length > 0) {
            processedLessons.forEach((lesson, index) => {
                lessonsContainer.appendChild(createLessonItem(day, index, lesson));
            });
        }
        // Always add an empty lesson at the end (like WhatsApp)
        lessonsContainer.appendChild(createLessonItem(day, processedLessons.length, {}));
        
        list.appendChild(lessonsContainer);
        
        // Initialize Sortable for this day's lessons
        if (typeof Sortable !== 'undefined') {
            new Sortable(lessonsContainer, {
                handle: '.lesson-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function(evt) {
                    updateLessonNumbers(day);
                }
            });
        }
        
        // Empty lesson item is already added at the end of the container
    });
}

// Create compact lesson item (display only)
function createLessonItem(day, index, lesson) {
    const div = document.createElement('div');
    div.className = 'lesson-item';
    div.dataset.day = day;
    div.dataset.index = index;
    
    const lessonNumber = index + 1;
    const subject = lesson.subject || '';
    const teacher = lesson.teacher || '';
    const room = lesson.room || '';
    
    // Check if this is an empty lesson (for adding new)
    const isEmpty = !subject && !teacher && !room;
    
    div.innerHTML = `
        <div class="lesson-item-content">
            <div class="lesson-drag-handle">
                <img src="/assets/files/menu-burger.svg" alt="גרור">
            </div>
            <div class="lesson-number">${lessonNumber}</div>
            <div class="lesson-info">
                <div class="lesson-subject-display ${isEmpty ? 'lesson-subject-empty' : ''}" onclick="toggleLessonEditorBySubject(this)" data-day="${day}" data-index="${index}" style="cursor: pointer;">
                    ${escapeHtml(subject || 'לחץ להוספת שיעור')}
                </div>
                ${teacher || room ? `<div class="lesson-details">${escapeHtml(teacher)}${teacher && room ? ' • ' : ''}${escapeHtml(room)}</div>` : ''}
            </div>
            <button type="button" class="btn-remove-lesson" onclick="removeLessonFromDay(this)">
                <img src="/assets/files/trash.svg" alt="מחק">
            </button>
        </div>
        <div class="lesson-editor-panel" style="display: none;">
            <div class="lesson-editor-form">
                <input type="text" class="lesson-subject-input" placeholder="מקצוע" value="${escapeHtml(subject)}" 
                       data-day="${day}" data-index="${index}" style="display: none;">
                <input type="text" class="lesson-teacher-input" placeholder="מורה (אופציונלי)" value="${escapeHtml(teacher)}" 
                       data-day="${day}" data-index="${index}">
                <input type="text" class="lesson-room-input" placeholder="חדר (אופציונלי)" value="${escapeHtml(room)}" 
                       data-day="${day}" data-index="${index}">
            </div>
        </div>
    `;
    return div;
}

// Legacy function for backward compatibility
function createLessonEditor(day, index, lesson) {
    return createLessonItem(day, index, lesson);
}

function addLessonToDay(day = null) {
    if (!day) {
        const checkbox = document.querySelector('.day-checkbox:checked');
        day = checkbox ? checkbox.value : null;
    }
    
    if (!day) {
        alert('נא לבחור יום תחילה');
        return;
    }
    
    const container = document.getElementById(`lessons-container-${day}`);
    if (!container) return;
    
    const existingLessons = container.querySelectorAll('.lesson-item');
    const newIndex = existingLessons.length;
    
    const newLesson = createLessonItem(day, newIndex, {});
    container.appendChild(newLesson);
    
    updateLessonNumbers(day);
}

function removeLessonFromDay(button) {
    const lessonItem = button.closest('.lesson-item');
    if (lessonItem) {
        const day = lessonItem.dataset.day;
        lessonItem.remove();
        updateLessonNumbers(day);
    }
}

function toggleLessonEditor(button) {
    const lessonItem = button.closest('.lesson-item');
    if (!lessonItem) return;
    
    const editorPanel = lessonItem.querySelector('.lesson-editor-panel');
    if (!editorPanel) return;
    
    // Close all other editors
    document.querySelectorAll('.lesson-editor-panel').forEach(panel => {
        if (panel !== editorPanel) {
            panel.style.display = 'none';
        }
    });
    
    // Toggle current editor
    if (editorPanel.style.display === 'none') {
        editorPanel.style.display = 'block';
    } else {
        editorPanel.style.display = 'none';
    }
}

function toggleLessonEditorBySubject(subjectDisplay) {
    const lessonItem = subjectDisplay.closest('.lesson-item');
    if (!lessonItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingLesson();
    
    // Close all other inline editors
    document.querySelectorAll('.lesson-subject-display.editing').forEach(el => {
        if (el !== subjectDisplay) {
            finishEditingSubject(el);
        }
    });
    
    // Convert display to input
    const currentText = subjectDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'lesson-subject-input-inline';
    input.value = currentText === 'לחץ להוספת שיעור' ? '' : currentText;
    input.placeholder = 'מקצוע';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit;';
    
    // Replace display with input
    subjectDisplay.style.display = 'none';
    subjectDisplay.classList.add('editing');
    subjectDisplay.parentNode.insertBefore(input, subjectDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingSubject(subjectDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
    
    // Also open the editor panel below for teacher and room
    const editorPanel = lessonItem.querySelector('.lesson-editor-panel');
    if (editorPanel) {
        // Close all other editor panels
        document.querySelectorAll('.lesson-editor-panel').forEach(panel => {
            if (panel !== editorPanel) {
                panel.style.display = 'none';
            }
        });
        // Open this editor panel
        editorPanel.style.display = 'block';
    }
}

function finishEditingSubject(subjectDisplay, input) {
    if (!input) {
        // Find the input if not provided
        input = subjectDisplay.parentNode.querySelector('.lesson-subject-input-inline');
    }
    
    if (!input) return;
    
    const lessonItem = subjectDisplay.closest('.lesson-item');
    if (!lessonItem) return;
    
    const newValue = input.value.trim();
    
    // Update display
    if (newValue) {
        subjectDisplay.textContent = newValue;
        subjectDisplay.classList.remove('lesson-subject-empty');
    } else {
        subjectDisplay.textContent = 'לחץ להוספת שיעור';
        subjectDisplay.classList.add('lesson-subject-empty');
    }
    
    // Remove input and show display
    input.remove();
    subjectDisplay.style.display = '';
    subjectDisplay.classList.remove('editing');
    
    // Update the hidden input in editor panel
    const editorPanel = lessonItem.querySelector('.lesson-editor-panel');
    if (editorPanel) {
        const hiddenInput = editorPanel.querySelector('.lesson-subject-input');
        if (hiddenInput) {
            hiddenInput.value = newValue;
        }
    }
    
    // Auto-save the lesson
    saveLessonData(lessonItem);
}

function saveCurrentEditingLesson() {
    // Save any currently editing subject field
    document.querySelectorAll('.lesson-subject-display.editing').forEach(subjectDisplay => {
        const input = subjectDisplay.parentNode.querySelector('.lesson-subject-input-inline');
        if (input) {
            finishEditingSubject(subjectDisplay, input);
        }
    });
}

function saveLessonData(lessonItem) {
    if (!lessonItem) return;
    
    const day = lessonItem.dataset.day;
    const subjectInput = lessonItem.querySelector('.lesson-subject-input');
    const teacherInput = lessonItem.querySelector('.lesson-teacher-input');
    const roomInput = lessonItem.querySelector('.lesson-room-input');
    
    const subject = subjectInput ? subjectInput.value.trim() : '';
    const teacher = teacherInput ? teacherInput.value.trim() : '';
    const room = roomInput ? roomInput.value.trim() : '';
    
    // Update display
    const subjectDisplay = lessonItem.querySelector('.lesson-subject-display');
    const detailsDisplay = lessonItem.querySelector('.lesson-details');
    
    if (subjectDisplay) {
        if (subject) {
            subjectDisplay.textContent = subject;
            subjectDisplay.classList.remove('lesson-subject-empty');
        } else {
            subjectDisplay.textContent = 'לחץ להוספת שיעור';
            subjectDisplay.classList.add('lesson-subject-empty');
        }
    }
    
    if (teacher || room) {
        if (!detailsDisplay) {
            const lessonInfo = lessonItem.querySelector('.lesson-info');
            if (lessonInfo) {
                const newDetails = document.createElement('div');
                newDetails.className = 'lesson-details';
                lessonInfo.appendChild(newDetails);
                newDetails.textContent = `${teacher}${teacher && room ? ' • ' : ''}${room}`;
            }
        } else {
            detailsDisplay.textContent = `${teacher}${teacher && room ? ' • ' : ''}${room}`;
        }
    } else if (detailsDisplay) {
        detailsDisplay.remove();
    }
    
    // If there's content, add new empty lesson at the end
    if (subject || teacher || room) {
        const container = document.getElementById(`lessons-container-${day}`);
        if (container) {
            const allLessons = container.querySelectorAll('.lesson-item');
            const lastLesson = allLessons[allLessons.length - 1];
            const lastSubject = lastLesson?.querySelector('.lesson-subject-input')?.value.trim();
            const lastTeacher = lastLesson?.querySelector('.lesson-teacher-input')?.value.trim();
            const lastRoom = lastLesson?.querySelector('.lesson-room-input')?.value.trim();
            
            if (lastSubject || lastTeacher || lastRoom) {
                const newIndex = allLessons.length;
                const newEmptyLesson = createLessonItem(day, newIndex, {});
                container.appendChild(newEmptyLesson);
                updateLessonNumbers(day);
            }
        }
    }
}

function closeLessonEditor(button) {
    const editorPanel = button.closest('.lesson-editor-panel');
    if (editorPanel) {
        editorPanel.style.display = 'none';
    }
}

function saveLessonEditor(button) {
    const lessonItem = button.closest('.lesson-item');
    if (!lessonItem) return;
    
    const day = lessonItem.dataset.day;
    const index = parseInt(lessonItem.dataset.index);
    
    const subjectInput = lessonItem.querySelector('.lesson-subject-input');
    const teacherInput = lessonItem.querySelector('.lesson-teacher-input');
    const roomInput = lessonItem.querySelector('.lesson-room-input');
    
    const subject = subjectInput ? subjectInput.value.trim() : '';
    const teacher = teacherInput ? teacherInput.value.trim() : '';
    const room = roomInput ? roomInput.value.trim() : '';
    
    // Update display
    const subjectDisplay = lessonItem.querySelector('.lesson-subject-display');
    const detailsDisplay = lessonItem.querySelector('.lesson-details');
    
    if (subjectDisplay) {
        if (subject) {
            subjectDisplay.textContent = subject;
            subjectDisplay.classList.remove('lesson-subject-empty');
        } else {
            subjectDisplay.textContent = 'לחץ להוספת שיעור';
            subjectDisplay.classList.add('lesson-subject-empty');
        }
    }
    
    if (teacher || room) {
        if (!detailsDisplay) {
            const lessonInfo = lessonItem.querySelector('.lesson-info');
            if (lessonInfo) {
                const newDetails = document.createElement('div');
                newDetails.className = 'lesson-details';
                lessonInfo.appendChild(newDetails);
                newDetails.textContent = `${teacher}${teacher && room ? ' • ' : ''}${room}`;
            }
        } else {
            detailsDisplay.textContent = `${teacher}${teacher && room ? ' • ' : ''}${room}`;
        }
    } else if (detailsDisplay) {
        detailsDisplay.remove();
    }
    
    // Close editor if there's content
    const editorPanel = lessonItem.querySelector('.lesson-editor-panel');
    if (editorPanel) {
        if (subject || teacher || room) {
        editorPanel.style.display = 'none';
            
            // Add new empty lesson at the end if this one has content
            const day = lessonItem.dataset.day;
            const container = document.getElementById(`lessons-container-${day}`);
            if (container) {
                const allLessons = container.querySelectorAll('.lesson-item');
                const lastLesson = allLessons[allLessons.length - 1];
                const lastSubject = lastLesson?.querySelector('.lesson-subject-input')?.value.trim();
                const lastTeacher = lastLesson?.querySelector('.lesson-teacher-input')?.value.trim();
                const lastRoom = lastLesson?.querySelector('.lesson-room-input')?.value.trim();
                
                if (lastSubject || lastTeacher || lastRoom) {
                    const newIndex = allLessons.length;
                    const newEmptyLesson = createLessonItem(day, newIndex, {});
                    container.appendChild(newEmptyLesson);
                    updateLessonNumbers(day);
                }
            }
        } else {
            // Keep editor open if empty
            editorPanel.style.display = 'block';
        }
    }
}

function updateLessonNumbers(day) {
    const container = document.getElementById(`lessons-container-${day}`);
    if (!container) return;
    
    const lessons = container.querySelectorAll('.lesson-item');
    lessons.forEach((lesson, index) => {
        const numberEl = lesson.querySelector('.lesson-number');
        if (numberEl) {
            numberEl.textContent = index + 1;
        }
        lesson.dataset.index = index;
        
        // Update all data-index attributes in inputs
        lesson.querySelectorAll('input[data-index]').forEach(input => {
            input.dataset.index = index;
        });
        
        // Update button data-index
        lesson.querySelectorAll('button[data-index]').forEach(btn => {
            btn.dataset.index = index;
        });
    });
}

// Auto-save on blur for teacher and room inputs
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('lesson-teacher-input') || 
            e.target.classList.contains('lesson-room-input')) {
            const lessonItem = e.target.closest('.lesson-item');
            if (lessonItem) {
                saveLessonData(lessonItem);
            }
        }
    }, true);
});

function collectScheduleData(form) {
    const manualSchedule = collectManualScheduleData(form);
    if (manualSchedule && Object.keys(manualSchedule).length > 0) {
        return { schedule: manualSchedule };
    }
    
    return { schedule: {} };
}

function collectManualScheduleData(form) {
    const schedule = {};
    
    DAY_NAMES.forEach(day => {
        const lessons = [];
        const container = document.getElementById(`lessons-container-${day}`);
        if (!container) {
            schedule[day] = [];
            return;
        }
        
        const lessonItems = container.querySelectorAll('.lesson-item');
        lessonItems.forEach((lessonItem, index) => {
            const subjectInput = lessonItem.querySelector('.lesson-subject-input');
            const teacherInput = lessonItem.querySelector('.lesson-teacher-input');
            const roomInput = lessonItem.querySelector('.lesson-room-input');
            
            const subject = subjectInput ? subjectInput.value.trim() : '';
            const teacher = teacherInput ? teacherInput.value.trim() : '';
            const room = roomInput ? roomInput.value.trim() : '';
            
            // Number is automatically set by order (index + 1)
            const time = String(index + 1);
            
            if (subject) {
                lessons.push({
                    time: time,
                    subject: subject,
                    teacher: teacher || '',
                    room: room || ''
                });
            }
        });
        
        schedule[day] = lessons;
    });
    
    return schedule;
}

function handleScheduleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const preview = document.getElementById('schedulePreview');
    if (preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div style="border: 2px dashed var(--color-blue); border-radius: 12px; padding: 1rem; text-align: center; background: #fff;">
                    <img src="${e.target.result}" alt="תצוגה מקדימה" style="max-width: 100%; max-height: 300px; border-radius: 4px; margin-bottom: 0.5rem;">
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">${file.name}</p>
                    <button type="button" id="processScheduleImageBtn" style="width: 100%; padding: 0.75rem; background: var(--color-blue); color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer;" onclick="processScheduleImage()">
                        🔄 עבד מסמך והוסף סיכום
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

async function processScheduleImage() {
    const input = document.getElementById('scheduleUpload');
    if (!input || !input.files || !input.files[0]) {
        alert('נא לבחור תמונה קודם');
        return;
    }
    
    const file = input.files[0];
    const statusEl = document.getElementById('scheduleProcessingStatus');
    const processBtn = document.getElementById('processScheduleImageBtn');
    const messageEl = document.getElementById('blockEditMessage');
    
    if (statusEl) statusEl.style.display = 'block';
    if (processBtn) {
        processBtn.disabled = true;
        processBtn.textContent = '⏳ מעבד...';
    }
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');
    
    try {
        const apiUrl = '/api/ai/extract-schedule';
            
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (statusEl) statusEl.style.display = 'none';
        
        if (result.ok && result.schedule) {
            if (messageEl) {
                messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px;">✅ מערכת השעות נחלצה בהצלחה!</div>';
            }
            
            // Schedule data is now handled only through the manual editor
            
            const form = document.getElementById('blockEditForm');
            if (form) {
                form.dataset.blockData = JSON.stringify({ schedule: result.schedule });
            }
            
            const manualEditor = document.getElementById('manualScheduleEditor');
            if (manualEditor) manualEditor.style.display = 'block';
            
            DAY_NAMES.forEach(day => {
                const checkbox = document.querySelector(`.day-checkbox[value="${day}"]`);
                if (checkbox && result.schedule[day] && result.schedule[day].length > 0) {
                    checkbox.checked = true;
                }
            });
            
            loadSelectedDays();
        } else {
            const errorMsg = result.message_he || result.reason || 'לא ניתן לחלץ את המערכת';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('שגיאה: ' + errorMsg);
        }
    } catch (error) {
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ שגיאה: ${error.message}</div>`;
        }
        alert('שגיאה בעיבוד התמונה: ' + error.message);
    } finally {
        if (processBtn) {
            processBtn.disabled = false;
            processBtn.textContent = '🔄 עבד מסמך והוסף סיכום';
        }
        if (statusEl) statusEl.style.display = 'none';
    }
}

function renderContactsEditor(block) {
    let html = `
        <div class="form-group">
            <label>אנשי קשר חשובים</label>
            <p class="contacts-description">
                ניתן לערוך, למחוק ולהוסיף אנשי קשר. לחץ על שם איש קשר כדי לערוך.
            </p>
            <div id="contactsList" class="contacts-list-editor"></div>
        </div>
    `;
    
    return html;
}

function loadContacts(block) {
    const list = document.getElementById('contactsList');
    if (!list) return;
    
    const contacts = (block.data && block.data.contacts) || [];
    
    list.innerHTML = '';
    
    if (contacts.length > 0) {
        contacts.forEach((contact, index) => {
            const contactItem = createContactItem(index, contact);
            list.appendChild(contactItem);
        });
    }
    
    // Always add an empty contact item at the end
    list.appendChild(createContactItem(contacts.length, {}));
}

function createContactItem(index, contact) {
    const div = document.createElement('div');
    div.className = 'contact-item-editor';
    div.dataset.index = index;
    
    const contactData = contact || {};
    const contactName = contactData.name || contactData.child_name || '';
    const contactRole = contactData.role || '';
    const contactPhone = contactData.phone || contactData.parent_phone || '';
    
    // Check if this is an empty contact (for adding new)
    const isEmpty = !contactName;
    
    div.innerHTML = `
        <div class="contact-item-content-editor" onblur="setTimeout(() => checkCloseContactEditor(this.closest('.contact-item-editor')), 200)">
            <div class="contact-item-header-editor">
                <div class="contact-info-editor">
                    <div class="contact-name-display ${isEmpty ? 'contact-name-empty' : ''}" onclick="toggleContactEditorByName(this)" data-index="${index}">
                        ${escapeHtml(contactName || 'לחץ להוספת איש קשר')}
                    </div>
                    ${contactRole ? `<div class="contact-role-display">${escapeHtml(contactRole)}</div>` : ''}
                    ${contactPhone ? `<div class="contact-phone-display">${escapeHtml(contactPhone)}</div>` : ''}
                </div>
                <div class="contact-item-actions-editor">
                    <button type="button" class="btn-remove-contact" onclick="removeContactItem(this)">
                        <img src="/assets/files/trash.svg" alt="מחק">
                    </button>
                </div>
            </div>
            <div class="contact-editor-panel">
                <div class="contact-editor-form">
                    <input type="text" class="contact-name-input" placeholder="שם" value="${escapeHtml(contactName)}" 
                           data-index="${index}">
                    <input type="text" class="contact-role-input" placeholder="תפקיד" value="${escapeHtml(contactRole)}" 
                           data-index="${index}" onblur="saveContactData(this.closest('.contact-item-editor')); setTimeout(() => checkCloseContactEditor(this.closest('.contact-item-editor')), 200)">
                    <input type="text" class="contact-phone-input" placeholder="טלפון" value="${escapeHtml(contactPhone)}" 
                           data-index="${index}" onblur="saveContactData(this.closest('.contact-item-editor')); setTimeout(() => checkCloseContactEditor(this.closest('.contact-item-editor')), 200)">
                </div>
            </div>
                </div>
            `;
    return div;
}

function toggleContactEditorByName(nameDisplay) {
    const contactItem = nameDisplay.closest('.contact-item-editor');
    if (!contactItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingContact();
    
    // Close all other contact editor panels
    document.querySelectorAll('.contact-item-editor').forEach(item => {
        if (item !== contactItem) {
            const panel = item.querySelector('.contact-editor-panel');
            if (panel) {
                panel.style.display = 'none';
            }
            // Finish any active inline editing in other items
            const editingDisplay = item.querySelector('.contact-name-display.editing');
            if (editingDisplay) {
                const input = editingDisplay.nextElementSibling;
                if (input && input.classList.contains('contact-name-input-inline')) {
                    finishEditingContactName(editingDisplay, input);
                }
            }
        }
    });
    
    // Close all other inline editors
    document.querySelectorAll('.contact-name-display.editing').forEach(el => {
        if (el !== nameDisplay) {
            finishEditingContactName(el);
        }
    });
    
    // Convert display to input
    const currentText = nameDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'contact-name-input-inline';
    input.value = currentText === 'לחץ להוספת איש קשר' ? '' : currentText;
    input.placeholder = 'שם';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit; text-align: right;';
    
    // Replace display with input
    nameDisplay.style.display = 'none';
    nameDisplay.classList.add('editing');
    nameDisplay.parentNode.insertBefore(input, nameDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Open editor panel
    const editorPanel = contactItem.querySelector('.contact-editor-panel');
    if (editorPanel) {
        editorPanel.style.display = 'block';
    }
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingContactName(nameDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
}

function finishEditingContactName(nameDisplay, input) {
    if (!nameDisplay || !input) return;
    
    const contactItem = nameDisplay.closest('.contact-item-editor');
    if (!contactItem) return;
    
    const newName = input.value.trim();
    const index = parseInt(nameDisplay.dataset.index || '0');
    
    // Update display
    if (newName) {
        nameDisplay.textContent = newName;
        nameDisplay.classList.remove('contact-name-empty');
    } else {
        nameDisplay.textContent = 'לחץ להוספת איש קשר';
        nameDisplay.classList.add('contact-name-empty');
    }
    
    // Update hidden input
    const hiddenInput = contactItem.querySelector('.contact-name-input');
    if (hiddenInput) {
        hiddenInput.value = newName;
    }
    
    // Remove input and show display
    input.remove();
    nameDisplay.style.display = '';
    nameDisplay.classList.remove('editing');
    
    // Save contact data
    saveContactData(contactItem);
    
    // Check if we should close the panel (if name is empty and no other content)
    const editorPanel = contactItem.querySelector('.contact-editor-panel');
    if (editorPanel) {
        if (!newName) {
            // If name is empty, check if there's any other content
            const roleInput = contactItem.querySelector('.contact-role-input');
            const phoneInput = contactItem.querySelector('.contact-phone-input');
            const hasRole = roleInput && roleInput.value.trim();
            const hasPhone = phoneInput && phoneInput.value.trim();
            
            // Close panel only if there's no content at all
            if (!hasRole && !hasPhone) {
                editorPanel.style.display = 'none';
            } else {
                // Keep panel open if there's other content
                editorPanel.style.display = 'block';
            }
        } else {
            // Keep panel open if name exists
            editorPanel.style.display = 'block';
        }
    }
}

function saveCurrentEditingContact() {
    const editingDisplay = document.querySelector('.contact-name-display.editing');
    if (editingDisplay) {
        const input = editingDisplay.nextElementSibling;
        if (input && input.classList.contains('contact-name-input-inline')) {
            finishEditingContactName(editingDisplay, input);
        }
    }
}

function checkCloseContactEditor(contactItem) {
    if (!contactItem) return;
    
    // Check if we should close the editor panel
    const nameInput = contactItem.querySelector('.contact-name-input');
    const nameDisplay = contactItem.querySelector('.contact-name-display');
    const roleInput = contactItem.querySelector('.contact-role-input');
    const phoneInput = contactItem.querySelector('.contact-phone-input');
    
    const name = nameInput ? nameInput.value.trim() : '';
    const nameDisplayText = nameDisplay ? nameDisplay.textContent.trim() : '';
    const hasName = (name && name !== '') || (nameDisplayText && nameDisplayText !== 'לחץ להוספת איש קשר');
    const hasRole = roleInput && roleInput.value.trim();
    const hasPhone = phoneInput && phoneInput.value.trim();
    
    const editorPanel = contactItem.querySelector('.contact-editor-panel');
    if (editorPanel) {
        // Close panel only if there's no content at all and user clicked outside
        if (!hasName && !hasRole && !hasPhone) {
            // Small delay to allow other events to fire first
            setTimeout(() => {
                // Double check that we're not in the middle of editing
                const isEditing = contactItem.querySelector('.contact-name-display.editing');
                if (!isEditing) {
                    editorPanel.style.display = 'none';
                }
            }, 150);
        }
    }
}

function saveContactData(contactItem) {
    if (!contactItem) return;
    
    const index = parseInt(contactItem.dataset.index || '0');
    const nameInput = contactItem.querySelector('.contact-name-input');
    const roleInput = contactItem.querySelector('.contact-role-input');
    const phoneInput = contactItem.querySelector('.contact-phone-input');
    
    // Update hidden name input
    const nameDisplay = contactItem.querySelector('.contact-name-display');
    if (nameDisplay && nameInput) {
        const currentName = nameDisplay.textContent.trim();
        if (currentName && currentName !== 'לחץ להוספת איש קשר') {
            nameInput.value = currentName;
        }
    }
    
    // If contact has a name, ensure there's an empty contact item at the end
    if (nameInput && nameInput.value.trim()) {
        const contactsList = document.getElementById('contactsList');
        if (contactsList) {
            const items = contactsList.querySelectorAll('.contact-item-editor');
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.contact-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                if (lastName && lastName !== 'לחץ להוספת איש קשר') {
                    // Add new empty contact
                    const newIndex = items.length;
                    contactsList.appendChild(createContactItem(newIndex, {}));
                }
            }
        }
    }
}

function removeContactItem(button) {
    const contactItem = button.closest('.contact-item-editor');
    if (!contactItem) return;
    
    contactItem.remove();
    
    // Re-index remaining items
    const contactsList = document.getElementById('contactsList');
    if (contactsList) {
        const items = contactsList.querySelectorAll('.contact-item-editor');
        items.forEach((item, index) => {
            item.dataset.index = index;
            const nameDisplay = item.querySelector('.contact-name-display');
            if (nameDisplay) {
                nameDisplay.dataset.index = index;
            }
            const inputs = item.querySelectorAll('input');
            inputs.forEach(input => {
                input.dataset.index = index;
            });
        });
    }
}

function addContactItem() {
    const list = document.getElementById('contactsList');
    if (!list) return;
    const index = list.querySelectorAll('.contact-item').length;
    const item = document.createElement('div');
    item.className = 'contact-item';
    item.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;';
    item.innerHTML = `
        <input type="text" name="contact_name_${index}" placeholder="שם" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" name="contact_role_${index}" placeholder="תפקיד" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" name="contact_phone_${index}" placeholder="טלפון" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
        <button type="button" class="btn-remove-item" onclick="removeContactItem(this)"><img src="/assets/files/trash.svg" alt="מחק"></button>
    `;
    list.appendChild(item);
}

function removeContactItem(button) {
    button.closest('.contact-item').remove();
}

async function handleContactsUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    const messageEl = document.getElementById('blockEditMessage');
    if (messageEl) {
        messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-blue); color: white; border-radius: 8px;">מעבד תמונה...</div>';
    }
    
    input.disabled = true;
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');
    
    try {
        const response = await fetch(API.baseUrl.replace(/\/$/, '') + `/api/ai/extract-contacts`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.ok && result.contacts) {
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px;">✅ נמצאו ${result.contacts.length} אנשי קשר</div>`;
            }
            displayContacts(result.contacts);
        } else {
            const errorMsg = result.message_he || result.reason || 'לא ניתן לחלץ את אנשי הקשר';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('שגיאה: ' + errorMsg);
        }
    } catch (error) {
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ שגיאה: ${error.message}</div>`;
        }
        alert('שגיאה: ' + error.message);
    } finally {
        input.disabled = false;
        input.value = '';
    }
}

function displayContacts(contacts) {
    const list = document.getElementById('contactsList');
    if (!list) return;
    let html = '';
    contacts.forEach((contact, index) => {
        html += `
            <div class="contact-item" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                <input type="text" name="contact_name_${index}" value="${escapeHtml(contact.name || contact.child_name || '')}" placeholder="שם" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                <input type="text" name="contact_role_${index}" value="${escapeHtml(contact.role || '')}" placeholder="תפקיד" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                <input type="text" name="contact_phone_${index}" value="${escapeHtml(contact.phone || contact.parent_phone || '')}" placeholder="טלפון" style="flex: 1; min-width: 150px; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
                <button type="button" class="btn-remove-item" onclick="removeContactItem(this)"><img src="/assets/files/trash.svg" alt="מחק"></button>
            </div>
        `;
    });
    list.innerHTML = html;
}

function collectContactsData(form) {
    const contacts = [];
    const contactItems = form.querySelectorAll('.contact-item-editor');
    contactItems.forEach(item => {
        const nameInput = item.querySelector('.contact-name-input');
        const roleInput = item.querySelector('.contact-role-input');
        const phoneInput = item.querySelector('.contact-phone-input');
        const name = nameInput ? nameInput.value.trim() : '';
        const role = roleInput ? roleInput.value.trim() : '';
        const phone = phoneInput ? phoneInput.value.trim() : '';
        if (name || role || phone) {
            contacts.push({ name: name, role: role, phone: phone });
        }
    });
    return { contacts };
}

function renderContactPageEditor(block) {
    let html = `
        <div class="form-group">
            <label>אנשי קשר - ילדים והורים</label>
            <p class="children-description">
                ניתן לערוך, למחוק ולהוסיף ילדים והורים. לחץ על שם הילד כדי לערוך.
            </p>
            <div id="childrenList" class="children-list"></div>
            </div>
    `;
    
    return html;
}

function loadChildren(block) {
    const list = document.getElementById('childrenList');
    if (!list) return;
    
    const children = (block.data && block.data.children) || [];
    
    list.innerHTML = '';
    
    if (children.length > 0) {
        children.forEach((child, index) => {
            const childItem = createChildItem(index, child);
            list.appendChild(childItem);
            
            // Load parents for this child
            const parents = child.parents || [];
            const parentsList = childItem.querySelector(`#parentsList_${index}`);
            if (parentsList && parents.length > 0) {
                parents.forEach((parent, parentIndex) => {
                    parentsList.appendChild(createParentItem(index, parentIndex, parent));
                });
                // Always add an empty parent item at the end if less than 2
                if (parents.length < 2) {
                    parentsList.appendChild(createParentItem(index, parents.length, {}));
                }
                updateAddParentButtonState(index);
            }
        });
    }
    
    // Always add an empty child item at the end
    list.appendChild(createChildItem(children.length, {}));
}

function createChildItem(index, child) {
    const div = document.createElement('div');
    div.className = 'child-item';
    div.dataset.index = index;
    
    const childData = child || {};
    const childName = childData.name || '';
    const birthDate = childData.birth_date || '';
    const parents = childData.parents || [];
    
    // Check if this is an empty child (for adding new)
    const isEmpty = !childName;
    
    // Format birth date for display
    let birthDateDisplay = '';
    if (birthDate) {
        const date = new Date(birthDate + 'T00:00:00');
        birthDateDisplay = date.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    div.innerHTML = `
        <div class="child-item-content" onblur="setTimeout(() => checkCloseChildEditor(this.closest('.child-item')), 200)">
            <div class="child-item-header">
                <div class="child-info">
                    <div class="child-name-display ${isEmpty ? 'child-name-empty' : ''}" onclick="toggleChildEditorByName(this)" data-index="${index}">
                        ${escapeHtml(childName || 'לחץ להוספת ילד')}
                    </div>
                    ${birthDateDisplay ? `<div class="child-birth-display">${birthDateDisplay}</div>` : ''}
                </div>
                <div class="child-item-actions">
                <button type="button" class="btn-remove-child" onclick="removeChildItem(this)">
                        <img src="/assets/files/trash.svg" alt="מחק">
                </button>
            </div>
                    </div>
            <div class="child-editor-panel">
                <div class="child-editor-form">
                    <input type="text" class="child-name-input" placeholder="שם הילד" value="${escapeHtml(childName)}" 
                           data-index="${index}">
                    <input type="date" class="child-birth-date-input" placeholder="תאריך לידה" value="${birthDate}" 
                           data-index="${index}" onblur="saveChildData(this.closest('.child-item')); setTimeout(() => checkCloseChildEditor(this.closest('.child-item')), 200)">
                <div class="parents-section">
                    <div class="parents-section-header">
                        <button type="button" class="btn-add-parent" onclick="addParentToChild(${index})" ${parents.length >= 2 ? 'disabled' : ''}>+ הוסף הורה</button>
                    </div>
                        <div id="parentsList_${index}" class="parents-list-editor" onblur="setTimeout(() => checkCloseChildEditor(this.closest('.child-item')), 200)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    return div;
}

function toggleChildEditorByName(nameDisplay) {
    const childItem = nameDisplay.closest('.child-item');
    if (!childItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingChild();
    
    // Close all other child editor panels
    document.querySelectorAll('.child-item').forEach(item => {
        if (item !== childItem) {
            const panel = item.querySelector('.child-editor-panel');
            if (panel) {
                panel.style.display = 'none';
            }
            // Finish any active inline editing in other items
            const editingDisplay = item.querySelector('.child-name-display.editing');
            if (editingDisplay) {
                const input = editingDisplay.nextElementSibling;
                if (input && input.classList.contains('child-name-input-inline')) {
                    finishEditingChildName(editingDisplay, input);
                }
            }
        }
    });
    
    // Close all other inline editors
    document.querySelectorAll('.child-name-display.editing').forEach(el => {
        if (el !== nameDisplay) {
            finishEditingChildName(el);
        }
    });
    
    // Check if panel is already open
    const editorPanel = childItem.querySelector('.child-editor-panel');
    const isPanelOpen = editorPanel && editorPanel.style.display !== 'none';
    
    // Convert display to input
    const currentText = nameDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'child-name-input-inline';
    input.value = currentText === 'לחץ להוספת ילד' ? '' : currentText;
    input.placeholder = 'שם הילד';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit; text-align: right;';
    
    // Replace display with input
    nameDisplay.style.display = 'none';
    nameDisplay.classList.add('editing');
    nameDisplay.parentNode.insertBefore(input, nameDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Open editor panel
    if (editorPanel) {
        editorPanel.style.display = 'block';
    }
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingChildName(nameDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
}

function finishEditingChildName(nameDisplay, input) {
    if (!nameDisplay || !input) return;
    
    const childItem = nameDisplay.closest('.child-item');
    if (!childItem) return;
    
    const newName = input.value.trim();
    const index = parseInt(nameDisplay.dataset.index || '0');
    
    // Update display
    if (newName) {
        nameDisplay.textContent = newName;
        nameDisplay.classList.remove('child-name-empty');
    } else {
        nameDisplay.textContent = 'לחץ להוספת ילד';
        nameDisplay.classList.add('child-name-empty');
    }
    
    // Update hidden input
    const hiddenInput = childItem.querySelector('.child-name-input');
    if (hiddenInput) {
        hiddenInput.value = newName;
    }
    
    // Remove input and show display
    input.remove();
    nameDisplay.style.display = '';
    nameDisplay.classList.remove('editing');
    
    // Save child data
    saveChildData(childItem);
    
    // Check if we should close the panel (if name is empty and no other content)
    const editorPanel = childItem.querySelector('.child-editor-panel');
    if (editorPanel) {
        if (!newName) {
            // If name is empty, check if there's any other content
            const birthDateInput = childItem.querySelector('.child-birth-date-input');
            const parentsList = childItem.querySelector('.parents-list-editor');
            const hasParents = parentsList && parentsList.querySelectorAll('.parent-item').length > 0;
            const hasBirthDate = birthDateInput && birthDateInput.value;
            
            // Close panel only if there's no content at all
            if (!hasBirthDate && !hasParents) {
                editorPanel.style.display = 'none';
            } else {
                // Keep panel open if there's other content
                editorPanel.style.display = 'block';
            }
        } else {
            // Keep panel open if name exists
            editorPanel.style.display = 'block';
        }
    }
}

function saveCurrentEditingChild() {
    const editingDisplay = document.querySelector('.child-name-display.editing');
    if (editingDisplay) {
        const input = editingDisplay.nextElementSibling;
        if (input && input.classList.contains('child-name-input-inline')) {
            finishEditingChildName(editingDisplay, input);
        }
    }
}

function checkCloseChildEditor(childItem) {
    if (!childItem) return;
    
    // Check if we should close the editor panel
    const nameInput = childItem.querySelector('.child-name-input');
    const nameDisplay = childItem.querySelector('.child-name-display');
    const birthDateInput = childItem.querySelector('.child-birth-date-input');
    const parentsList = childItem.querySelector('.parents-list-editor');
    
    const name = nameInput ? nameInput.value.trim() : '';
    const nameDisplayText = nameDisplay ? nameDisplay.textContent.trim() : '';
    const hasName = (name && name !== '') || (nameDisplayText && nameDisplayText !== 'לחץ להוספת ילד');
    const hasBirthDate = birthDateInput && birthDateInput.value;
    const hasParents = parentsList && parentsList.querySelectorAll('.parent-item').length > 0;
    
    const editorPanel = childItem.querySelector('.child-editor-panel');
    if (editorPanel) {
        // Close panel only if there's no content at all and user clicked outside
        if (!hasName && !hasBirthDate && !hasParents) {
            // Small delay to allow other events to fire first
            setTimeout(() => {
                // Double check that we're not in the middle of editing
                const isEditing = childItem.querySelector('.child-name-display.editing');
                if (!isEditing) {
                    editorPanel.style.display = 'none';
                }
            }, 150);
        }
    }
}

function toggleChildContactDropdown(header) {
    const childContactItem = header.closest('.child-contact-item');
    if (!childContactItem) return;
    
    const dropdownContent = childContactItem.querySelector('.child-contact-dropdown-content');
    const arrow = header.querySelector('.dropdown-icon');
    
    if (!dropdownContent) return;
    
    const isOpen = dropdownContent.classList.contains('open');
    
    // Close all other dropdowns
    document.querySelectorAll('.child-contact-dropdown-content.open').forEach(content => {
        if (content !== dropdownContent) {
            content.classList.remove('open');
            const otherArrow = content.closest('.child-contact-item').querySelector('.dropdown-icon');
            if (otherArrow) {
                otherArrow.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    if (isOpen) {
        dropdownContent.classList.remove('open');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    } else {
        dropdownContent.classList.add('open');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }
}

function toggleChildDropdown(header) {
    const childItem = header.closest('.child-item');
    if (!childItem) return;
    
    const dropdownContent = childItem.querySelector('.child-dropdown-content');
    const arrow = header.querySelector('.dropdown-icon');
    
    if (!dropdownContent) return;
    
    const isOpen = dropdownContent.classList.contains('open');
    
    // Close all other dropdowns
    document.querySelectorAll('.child-dropdown-content.open').forEach(content => {
        if (content !== dropdownContent) {
            content.classList.remove('open');
            const otherArrow = content.closest('.child-item').querySelector('.dropdown-icon');
            if (otherArrow) {
                otherArrow.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    if (isOpen) {
        dropdownContent.classList.remove('open');
        if (arrow) arrow.style.transform = 'rotate(0deg)';
    } else {
        dropdownContent.classList.add('open');
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }
}

function saveChildData(childItem) {
    if (!childItem) return;
    
    const index = parseInt(childItem.dataset.index || '0');
    const nameInput = childItem.querySelector('.child-name-input');
    const birthDateInput = childItem.querySelector('.child-birth-date-input');
    
    // Update hidden name input
    const nameDisplay = childItem.querySelector('.child-name-display');
    if (nameDisplay && nameInput) {
        const currentName = nameDisplay.textContent.trim();
        if (currentName && currentName !== 'לחץ להוספת ילד') {
            nameInput.value = currentName;
        }
    }
    
    // If child has a name, ensure there's an empty child item at the end
    if (nameInput && nameInput.value.trim()) {
        const childrenList = document.getElementById('childrenList');
        if (childrenList) {
            const items = childrenList.querySelectorAll('.child-item');
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.child-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                if (lastName && lastName !== 'לחץ להוספת ילד') {
                    // Add new empty child
                    const newIndex = items.length;
                    childrenList.appendChild(createChildItem(newIndex, {}));
                }
            }
        }
    }
}

function createParentItem(childIndex, parentIndex, parent) {
    const div = document.createElement('div');
    div.className = 'parent-item';
    div.dataset.childIndex = childIndex;
    div.dataset.parentIndex = parentIndex;
    
    const parentData = parent || {};
    const parentName = parentData.name || '';
    const parentPhone = parentData.phone || '';
    const parentRole = parentData.role || 'אבא';
    
    // Check if this is an empty parent (for adding new)
    const isEmpty = !parentName && !parentPhone;
    
    div.innerHTML = `
        <div class="parent-item-content">
            <div class="parent-info">
                <div class="parent-name-display ${isEmpty ? 'parent-name-empty' : ''}" onclick="toggleParentEditorByName(this)" data-child-index="${childIndex}" data-parent-index="${parentIndex}">
                    ${escapeHtml(parentName || 'לחץ להוספת הורה')}
                </div>
                ${parentPhone ? `<div class="parent-phone-display">${escapeHtml(parentPhone)}</div>` : ''}
            </div>
                <button type="button" class="btn-remove-parent" onclick="removeParentFromChild(${childIndex}, ${parentIndex})">
                <img src="/assets/files/trash.svg" alt="מחק">
                </button>
            </div>
        <div class="parent-editor-panel">
            <div class="parent-editor-form">
                <input type="text" class="parent-name-input" placeholder="שם ההורה" value="${escapeHtml(parentName)}" 
                       data-child-index="${childIndex}" data-parent-index="${parentIndex}" style="display: none;">
                <select class="parent-role-select" data-child-index="${childIndex}" data-parent-index="${parentIndex}" onchange="saveParentData(this.closest('.parent-item'))">
                        <option value="אבא" ${parentRole === 'אבא' ? 'selected' : ''}>אבא</option>
                        <option value="אמא" ${parentRole === 'אמא' ? 'selected' : ''}>אמא</option>
                    </select>
                <input type="tel" class="parent-phone-input" placeholder="טלפון" value="${escapeHtml(parentPhone)}" 
                       data-child-index="${childIndex}" data-parent-index="${parentIndex}" onblur="saveParentData(this.closest('.parent-item'))">
            </div>
        </div>
    `;
    return div;
}

// Keep old function name for backward compatibility, but use new structure
function createParentEditorItem(childIndex, parentIndex, parent) {
    const parentItem = createParentItem(childIndex, parentIndex, parent);
    return parentItem.outerHTML;
}

function toggleParentEditorByName(nameDisplay) {
    const parentItem = nameDisplay.closest('.parent-item');
    if (!parentItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingParent();
    
    // Close all other parent editor panels (not just inline editors)
    const childIndex = parseInt(nameDisplay.dataset.childIndex || '0');
    const parentsList = document.getElementById(`parentsList_${childIndex}`);
    if (parentsList) {
        parentsList.querySelectorAll('.parent-item').forEach(item => {
            if (item !== parentItem) {
                // Close editor panel
                const panel = item.querySelector('.parent-editor-panel');
                if (panel) {
                    panel.style.display = 'none';
                }
                // Finish any active inline editing
                const editingDisplay = item.querySelector('.parent-name-display.editing');
                if (editingDisplay) {
                    const input = editingDisplay.nextElementSibling;
                    if (input && input.classList.contains('parent-name-input-inline')) {
                        finishEditingParentName(editingDisplay, input);
                    }
                }
            }
        });
    }
    
    // Close all other inline editors
    document.querySelectorAll('.parent-name-display.editing').forEach(el => {
        if (el !== nameDisplay) {
            finishEditingParentName(el);
        }
    });
    
    // Check if this panel is already open
    const editorPanel = parentItem.querySelector('.parent-editor-panel');
    const isAlreadyOpen = editorPanel && editorPanel.style.display !== 'none';
    
    // Convert display to input
    const currentText = nameDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'parent-name-input-inline';
    input.value = currentText === 'לחץ להוספת הורה' ? '' : currentText;
    input.placeholder = 'שם ההורה';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit; text-align: right;';
    
    // Replace display with input
    nameDisplay.style.display = 'none';
    nameDisplay.classList.add('editing');
    nameDisplay.parentNode.insertBefore(input, nameDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Open editor panel (if not already open)
    if (editorPanel && !isAlreadyOpen) {
        editorPanel.style.display = 'block';
    }
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingParentName(nameDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
}

function finishEditingParentName(nameDisplay, input) {
    if (!nameDisplay || !input) return;
    
    const parentItem = nameDisplay.closest('.parent-item');
    if (!parentItem) return;
    
    const newName = input.value.trim();
    const childIndex = parseInt(nameDisplay.dataset.childIndex || '0');
    const parentIndex = parseInt(nameDisplay.dataset.parentIndex || '0');
    
    // Update display
    if (newName) {
        nameDisplay.textContent = newName;
        nameDisplay.classList.remove('parent-name-empty');
    } else {
        nameDisplay.textContent = 'לחץ להוספת הורה';
        nameDisplay.classList.add('parent-name-empty');
    }
    
    // Update hidden input
    const hiddenInput = parentItem.querySelector('.parent-name-input');
    if (hiddenInput) {
        hiddenInput.value = newName;
    }
    
    // Remove input and show display
    input.remove();
    nameDisplay.style.display = '';
    nameDisplay.classList.remove('editing');
    
    // Save parent data
    saveParentData(parentItem);
    
    // If name was added, ensure editor panel stays open
    if (newName) {
        const editorPanel = parentItem.querySelector('.parent-editor-panel');
        if (editorPanel) {
            editorPanel.style.display = 'block';
        }
    }
}

function saveCurrentEditingParent() {
    const editingDisplay = document.querySelector('.parent-name-display.editing');
    if (editingDisplay) {
        const input = editingDisplay.nextElementSibling;
        if (input && input.classList.contains('parent-name-input-inline')) {
            finishEditingParentName(editingDisplay, input);
        }
    }
}

function saveParentData(parentItem) {
    if (!parentItem) return;
    
    const childIndex = parseInt(parentItem.dataset.childIndex || '0');
    const parentIndex = parseInt(parentItem.dataset.parentIndex || '0');
    const nameInput = parentItem.querySelector('.parent-name-input');
    const phoneInput = parentItem.querySelector('.parent-phone-input');
    const roleSelect = parentItem.querySelector('.parent-role-select');
    
    // Update name display if needed
    const nameDisplay = parentItem.querySelector('.parent-name-display');
    if (nameDisplay && nameInput) {
        const currentName = nameDisplay.textContent.trim();
        if (currentName && currentName !== 'לחץ להוספת הורה') {
            nameInput.value = currentName;
        }
    }
    
    // Update phone display
    const phoneDisplay = parentItem.querySelector('.parent-phone-display');
    if (phoneInput && phoneInput.value.trim()) {
        if (!phoneDisplay) {
            const parentInfo = parentItem.querySelector('.parent-info');
            if (parentInfo) {
                const newPhoneDisplay = document.createElement('div');
                newPhoneDisplay.className = 'parent-phone-display';
                newPhoneDisplay.textContent = phoneInput.value.trim();
                parentInfo.appendChild(newPhoneDisplay);
            }
        } else {
            phoneDisplay.textContent = phoneInput.value.trim();
        }
    } else if (phoneDisplay) {
        phoneDisplay.remove();
    }
    
    // If parent has a name, ensure there's an empty parent item at the end
    if (nameInput && nameInput.value.trim()) {
        const parentsList = document.getElementById(`parentsList_${childIndex}`);
        if (parentsList) {
            const items = parentsList.querySelectorAll('.parent-item');
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.parent-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                if (lastName && lastName !== 'לחץ להוספת הורה') {
                    // Check if we have less than 2 parents
                    if (items.length < 2) {
                        // Add new empty parent
                        const newIndex = items.length;
                        parentsList.appendChild(createParentItem(childIndex, newIndex, {}));
                        updateAddParentButtonState(childIndex);
                    }
                }
            }
        }
    }
}

function addChildItem() {
    const childrenList = document.getElementById('childrenList');
    if (!childrenList) return;
    
    // Find the last empty child item or add a new one
    const items = childrenList.querySelectorAll('.child-item');
    const lastItem = items[items.length - 1];
    
    if (lastItem) {
        const lastNameDisplay = lastItem.querySelector('.child-name-display');
        const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
        if (!lastName || lastName === 'לחץ להוספת ילד') {
            // Click on the last empty item to open it
            if (lastNameDisplay) {
                toggleChildEditorByName(lastNameDisplay);
            }
        } else {
            // Add new empty child
            const newIndex = items.length;
            childrenList.appendChild(createChildItem(newIndex, {}));
            // Open it
            const newNameDisplay = childrenList.querySelector(`.child-item[data-index="${newIndex}"] .child-name-display`);
            if (newNameDisplay) {
                toggleChildEditorByName(newNameDisplay);
            }
        }
    } else {
        // No items, add first one
        const newIndex = 0;
        childrenList.appendChild(createChildItem(newIndex, {}));
        const newNameDisplay = childrenList.querySelector(`.child-item[data-index="${newIndex}"] .child-name-display`);
        if (newNameDisplay) {
            toggleChildEditorByName(newNameDisplay);
        }
    }
}

function removeChildItem(button) {
    const childItem = button.closest('.child-item');
    if (childItem) {
        childItem.remove();
        
        // Re-index remaining items
        const childrenList = document.getElementById('childrenList');
        if (childrenList) {
            const items = childrenList.querySelectorAll('.child-item');
                items.forEach((item, index) => {
                item.dataset.index = index;
                
                // Update all input names and data-index attributes
                item.querySelectorAll('input, select, button, .child-name-display').forEach(el => {
                    const name = el.getAttribute('name');
                        if (name) {
                            const newName = name.replace(/_\d+(_\d+)?$/, (match) => {
                                if (match.includes('_')) {
                                    const parts = match.split('_');
                                    return '_' + index + '_' + (parts[parts.length - 1] || '');
                                }
                                return '_' + index;
                            });
                        el.setAttribute('name', newName);
                    }
                    const dataIndex = el.getAttribute('data-index');
                    if (dataIndex !== null) {
                        el.setAttribute('data-index', index);
                    }
                    const onclick = el.getAttribute('onclick');
                        if (onclick) {
                        el.setAttribute('onclick', onclick.replace(/\d+/g, (match, offset, string) => {
                                if (string.indexOf('addParentToChild') !== -1 || string.indexOf('removeParentFromChild') !== -1) {
                                    return index.toString();
                                }
                                return match;
                            }));
                        }
                    });
                
                // Update parentsList id
                const parentsList = item.querySelector('.parents-list-editor');
                if (parentsList) {
                    parentsList.id = `parentsList_${index}`;
                }
            });
            
            // Ensure there's always an empty child at the end
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.child-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                if (lastName && lastName !== 'לחץ להוספת ילד') {
                    const newIndex = items.length;
                    childrenList.appendChild(createChildItem(newIndex, {}));
                }
            } else {
                // No items, add first empty one
                childrenList.appendChild(createChildItem(0, {}));
            }
        }
    }
}

function addParentToChild(childIndex) {
    const parentsList = document.getElementById(`parentsList_${childIndex}`);
    if (!parentsList) return;
    
    const parentItems = parentsList.querySelectorAll('.parent-item');
    if (parentItems.length >= 2) {
        alert('ניתן להוסיף עד 2 הורים לכל ילד');
        return;
    }
    
    // Find the last empty parent item or add a new one
    const lastItem = parentItems[parentItems.length - 1];
    
    if (lastItem) {
        const lastNameDisplay = lastItem.querySelector('.parent-name-display');
        const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
        if (!lastName || lastName === 'לחץ להוספת הורה') {
            // Click on the last empty item to open it
            if (lastNameDisplay) {
                toggleParentEditorByName(lastNameDisplay);
            }
        } else {
            // Add new empty parent
            const newIndex = parentItems.length;
            const newParentItem = createParentItem(childIndex, newIndex, {});
            parentsList.appendChild(newParentItem);
            // Open it
            const newNameDisplay = newParentItem.querySelector('.parent-name-display');
            if (newNameDisplay) {
                toggleParentEditorByName(newNameDisplay);
            }
        }
    } else {
        // No items, add first one
        const newIndex = 0;
        const newParentItem = createParentItem(childIndex, newIndex, {});
        parentsList.appendChild(newParentItem);
        const newNameDisplay = newParentItem.querySelector('.parent-name-display');
        if (newNameDisplay) {
            toggleParentEditorByName(newNameDisplay);
        }
    }
    
    // Remove empty message if exists
    const emptyMsg = parentsList.querySelector('p.parents-empty-message');
    if (emptyMsg) emptyMsg.remove();
    
    // Update add button state
    updateAddParentButtonState(childIndex);
}

function updateAddParentButtonState(childIndex) {
    const parentsList = document.getElementById(`parentsList_${childIndex}`);
    if (!parentsList) return;
    
    const parentItems = parentsList.querySelectorAll('.parent-item');
    const addButton = parentsList.previousElementSibling.querySelector('.btn-add-parent');
    
    if (addButton) {
        if (parentItems.length >= 2) {
        addButton.disabled = true;
        addButton.style.background = '#ccc';
        addButton.style.cursor = 'not-allowed';
        } else {
            addButton.disabled = false;
            addButton.style.background = 'var(--color-blue)';
            addButton.style.cursor = 'pointer';
        }
    }
}

function removeParentFromChild(childIndex, parentIndex) {
    const parentsList = document.getElementById(`parentsList_${childIndex}`);
    if (!parentsList) return;
    
    const parentItem = parentsList.querySelector(`[data-parent-index="${parentIndex}"]`);
    if (parentItem) {
        parentItem.remove();
        
        // Re-index remaining parents
        const parentItems = parentsList.querySelectorAll('.parent-item');
        if (parentItems.length === 0) {
            parentsList.innerHTML = '<p class="parents-empty-message">אין הורים. לחץ על "הוסף הורה" כדי להוסיף (עד 2 הורים).</p>';
        } else {
            parentItems.forEach((item, newIndex) => {
                item.dataset.parentIndex = newIndex;
                
                // Update all data attributes and onclick handlers
                item.querySelectorAll('input, select, .parent-name-display, button').forEach(el => {
                    const dataChildIndex = el.getAttribute('data-child-index');
                    const dataParentIndex = el.getAttribute('data-parent-index');
                    if (dataParentIndex !== null) {
                        el.setAttribute('data-parent-index', newIndex);
                    }
                    const onclick = el.getAttribute('onclick');
                    if (onclick) {
                        el.setAttribute('onclick', onclick.replace(/removeParentFromChild\(\d+,\s*\d+\)/, `removeParentFromChild(${childIndex}, ${newIndex})`));
                    }
                });
            });
        }
        
        // Ensure there's always an empty parent at the end if less than 2
        if (parentItems.length < 2) {
            const lastItem = parentItems[parentItems.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.parent-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                if (lastName && lastName !== 'לחץ להוספת הורה') {
                    const newIndex = parentItems.length;
                    parentsList.appendChild(createParentItem(childIndex, newIndex, {}));
                }
            } else {
                // No items, add first empty one
                parentsList.appendChild(createParentItem(childIndex, 0, {}));
            }
        }
        
        // Update add button state
        updateAddParentButtonState(childIndex);
    }
}

function renderLinksEditor(block) {
    const blockType = block.type || '';
    const isWhatsApp = blockType === 'whatsapp';
    const label = isWhatsApp ? 'קבוצות וואטסאפ' : 'קישורים שימושיים';
    const description = isWhatsApp 
        ? 'ניתן לערוך, למחוק ולהוסיף קבוצות וואטסאפ. לחץ על שם הקישור כדי לערוך.'
        : 'ניתן לערוך, למחוק ולהוסיף קישורים. לחץ על שם הקישור כדי לערוך.';
    
    let html = `
        <div class="form-group">
            <label>${label}</label>
            <p class="links-description">
                ${description}
            </p>
            <div id="linksList" class="links-list-editor"></div>
            </div>
        `;
    
    return html;
}

function loadLinks(block) {
    const list = document.getElementById('linksList');
    if (!list) return;
    
    const links = (block.data && block.data.links) || [];
    
    list.innerHTML = '';
    
    if (links.length > 0) {
        links.forEach((link, index) => {
            const linkItem = createLinkItem(index, link);
            list.appendChild(linkItem);
        });
    }
    
    // Always add an empty link item at the end
    list.appendChild(createLinkItem(links.length, {}));
}

function createLinkItem(index, link) {
    const div = document.createElement('div');
    div.className = 'link-item-editor';
    div.dataset.index = index;
    
    const linkData = link || {};
    const linkTitle = linkData.title || '';
    const linkUrl = linkData.url || '';
    
    // Check if this is an empty link (for adding new)
    const isEmpty = !linkTitle;
    
    div.innerHTML = `
        <div class="link-item-content-editor" onblur="setTimeout(() => checkCloseLinkEditor(this.closest('.link-item-editor')), 200)">
            <div class="link-item-header-editor">
                <div class="link-info-editor">
                    <div class="link-title-display ${isEmpty ? 'link-title-empty' : ''}" onclick="toggleLinkEditorByName(this)" data-index="${index}">
                        ${escapeHtml(linkTitle || 'לחץ להוספת קישור')}
                    </div>
                    ${linkUrl ? `<div class="link-url-display">${escapeHtml(linkUrl)}</div>` : ''}
                </div>
                <div class="link-item-actions-editor">
                    <button type="button" class="btn-remove-link" onclick="removeLinkItem(this)">
                        <img src="/assets/files/trash.svg" alt="מחק">
                    </button>
                </div>
            </div>
            <div class="link-editor-panel">
                <div class="link-editor-form">
                    <input type="text" class="link-title-input" placeholder="כותרת" value="${escapeHtml(linkTitle)}" 
                           data-index="${index}">
                    <input type="url" class="link-url-input" placeholder="כתובת URL" value="${escapeHtml(linkUrl)}" 
                           data-index="${index}" onblur="saveLinkData(this.closest('.link-item-editor')); setTimeout(() => checkCloseLinkEditor(this.closest('.link-item-editor')), 200)">
                </div>
            </div>
                </div>
            `;
    return div;
}

function toggleLinkEditorByName(nameDisplay) {
    const linkItem = nameDisplay.closest('.link-item-editor');
    if (!linkItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingLink();
    
    // Close all other link editor panels
    document.querySelectorAll('.link-item-editor').forEach(item => {
        if (item !== linkItem) {
            const panel = item.querySelector('.link-editor-panel');
            if (panel) {
                panel.style.display = 'none';
            }
            // Finish any active inline editing in other items
            const editingDisplay = item.querySelector('.link-title-display.editing');
            if (editingDisplay) {
                const input = editingDisplay.nextElementSibling;
                if (input && input.classList.contains('link-title-input-inline')) {
                    finishEditingLinkName(editingDisplay, input);
                }
            }
        }
    });
    
    // Close all other inline editors
    document.querySelectorAll('.link-title-display.editing').forEach(el => {
        if (el !== nameDisplay) {
            finishEditingLinkName(el);
        }
    });
    
    // Convert display to input
    const currentText = nameDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'link-title-input-inline';
    input.value = currentText === 'לחץ להוספת קישור' ? '' : currentText;
    input.placeholder = 'כותרת';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit; text-align: right;';
    
    // Replace display with input
    nameDisplay.style.display = 'none';
    nameDisplay.classList.add('editing');
    nameDisplay.parentNode.insertBefore(input, nameDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Open editor panel
    const editorPanel = linkItem.querySelector('.link-editor-panel');
    if (editorPanel) {
        editorPanel.style.display = 'block';
    }
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingLinkName(nameDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
}

function finishEditingLinkName(nameDisplay, input) {
    if (!nameDisplay || !input) return;
    
    const linkItem = nameDisplay.closest('.link-item-editor');
    if (!linkItem) return;
    
    const newTitle = input.value.trim();
    const index = parseInt(nameDisplay.dataset.index || '0');
    
    // Update display
    if (newTitle) {
        nameDisplay.textContent = newTitle;
        nameDisplay.classList.remove('link-title-empty');
    } else {
        nameDisplay.textContent = 'לחץ להוספת קישור';
        nameDisplay.classList.add('link-title-empty');
    }
    
    // Update hidden input
    const hiddenInput = linkItem.querySelector('.link-title-input');
    if (hiddenInput) {
        hiddenInput.value = newTitle;
    }
    
    // Remove input and show display
    input.remove();
    nameDisplay.style.display = '';
    nameDisplay.classList.remove('editing');
    
    // Save link data
    saveLinkData(linkItem);
    
    // Check if we should close the panel (if title is empty and no other content)
    const editorPanel = linkItem.querySelector('.link-editor-panel');
    if (editorPanel) {
        if (!newTitle) {
            // If title is empty, check if there's any other content
            const urlInput = linkItem.querySelector('.link-url-input');
            const hasUrl = urlInput && urlInput.value.trim();
            
            // Close panel only if there's no content at all
            if (!hasUrl) {
                editorPanel.style.display = 'none';
            } else {
                // Keep panel open if there's other content
                editorPanel.style.display = 'block';
            }
        } else {
            // Keep panel open if title exists
            editorPanel.style.display = 'block';
        }
    }
}

function saveCurrentEditingLink() {
    const editingDisplay = document.querySelector('.link-title-display.editing');
    if (editingDisplay) {
        const input = editingDisplay.nextElementSibling;
        if (input && input.classList.contains('link-title-input-inline')) {
            finishEditingLinkName(editingDisplay, input);
        }
    }
}

function checkCloseLinkEditor(linkItem) {
    if (!linkItem) return;
    
    // Check if we should close the editor panel
    const titleInput = linkItem.querySelector('.link-title-input');
    const titleDisplay = linkItem.querySelector('.link-title-display');
    const urlInput = linkItem.querySelector('.link-url-input');
    
    const title = titleInput ? titleInput.value.trim() : '';
    const titleDisplayText = titleDisplay ? titleDisplay.textContent.trim() : '';
    const hasTitle = (title && title !== '') || (titleDisplayText && titleDisplayText !== 'לחץ להוספת קישור');
    const hasUrl = urlInput && urlInput.value.trim();
    
    const editorPanel = linkItem.querySelector('.link-editor-panel');
    if (editorPanel) {
        // Close panel only if there's no content at all and user clicked outside
        if (!hasTitle && !hasUrl) {
            // Small delay to allow other events to fire first
            setTimeout(() => {
                // Double check that we're not in the middle of editing
                const isEditing = linkItem.querySelector('.link-title-display.editing');
                if (!isEditing) {
                    editorPanel.style.display = 'none';
                }
            }, 150);
        }
    }
}

function saveLinkData(linkItem) {
    if (!linkItem) return;
    
    const index = parseInt(linkItem.dataset.index || '0');
    const titleInput = linkItem.querySelector('.link-title-input');
    const urlInput = linkItem.querySelector('.link-url-input');
    
    // Update hidden title input
    const titleDisplay = linkItem.querySelector('.link-title-display');
    if (titleDisplay && titleInput) {
        const currentTitle = titleDisplay.textContent.trim();
        if (currentTitle && currentTitle !== 'לחץ להוספת קישור') {
            titleInput.value = currentTitle;
        }
    }
    
    // If link has a title, ensure there's an empty link item at the end
    if (titleInput && titleInput.value.trim()) {
        const linksList = document.getElementById('linksList');
        if (linksList) {
            const items = linksList.querySelectorAll('.link-item-editor');
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastTitleDisplay = lastItem.querySelector('.link-title-display');
                const lastTitle = lastTitleDisplay ? lastTitleDisplay.textContent.trim() : '';
                if (lastTitle && lastTitle !== 'לחץ להוספת קישור') {
                    // Add new empty link
                    const newIndex = items.length;
                    linksList.appendChild(createLinkItem(newIndex, {}));
                }
            }
        }
    }
}

function removeLinkItem(button) {
    const linkItem = button.closest('.link-item-editor');
    if (!linkItem) return;
    
    linkItem.remove();
    
    // Re-index remaining items
    const linksList = document.getElementById('linksList');
    if (linksList) {
        const items = linksList.querySelectorAll('.link-item-editor');
        items.forEach((item, index) => {
            item.dataset.index = index;
            const titleDisplay = item.querySelector('.link-title-display');
            if (titleDisplay) {
                titleDisplay.dataset.index = index;
            }
            const inputs = item.querySelectorAll('input');
            inputs.forEach(input => {
                input.dataset.index = index;
            });
        });
    }
}

function addLinkItem() {
    const linksList = document.getElementById('linksList');
    if (!linksList) return;
    const index = linksList.querySelectorAll('.link-item').length;
    const linkItem = document.createElement('div');
    linkItem.className = 'link-item';
    linkItem.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem;';
    linkItem.innerHTML = `
        <input type="text" name="link_title_${index}" placeholder="כותרת" style="flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
        <input type="url" name="link_url_${index}" placeholder="כתובת" style="flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;">
        <button type="button" class="btn btn-danger" onclick="removeLinkItem(this)" style="padding: 0.5rem; background: #E74C3C; color: white; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><img src="/assets/files/trash.svg" alt="מחק" style="width: 16px; height: 16px; filter: brightness(0) invert(1);"></button>
    `;
    linksList.appendChild(linkItem);
}

function removeLinkItem(button) {
    button.closest('.link-item').remove();
}

function collectContactPageData(form) {
    const children = [];
    const childItems = form.querySelectorAll('.child-item');
    
    childItems.forEach((childItem) => {
        const index = parseInt(childItem.dataset.index || '0');
        const nameInput = childItem.querySelector('.child-name-input');
        const birthDateInput = childItem.querySelector('.child-birth-date-input');
        
        const childName = nameInput ? nameInput.value.trim() : '';
        const birthDate = birthDateInput && birthDateInput.value ? birthDateInput.value.trim() : '';
        
        if (!childName) return; // Skip if no name
        
        const parents = [];
        const parentsList = document.getElementById(`parentsList_${index}`);
        if (parentsList) {
            const parentItems = parentsList.querySelectorAll('.parent-item');
            
            parentItems.forEach((parentItem) => {
                const parentIndex = parseInt(parentItem.dataset.parentIndex || '0');
                const roleSelect = parentItem.querySelector('.parent-role-select');
                const nameInput = parentItem.querySelector('.parent-name-input');
                const phoneInput = parentItem.querySelector('.parent-phone-input');
            
            const role = roleSelect ? roleSelect.value : '';
            const name = nameInput ? nameInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            
            if (name && phone && role) {
                parents.push({ role, name, phone });
            }
        });
        }
        
        children.push({
            name: childName,
            birth_date: birthDate || null,
            parents: parents
        });
    });
    
    return { children };
}

function collectLinksData(form) {
    const links = [];
    const linkItems = form.querySelectorAll('.link-item-editor');
    linkItems.forEach(item => {
        const titleInput = item.querySelector('.link-title-input');
        const urlInput = item.querySelector('.link-url-input');
        const title = titleInput ? titleInput.value.trim() : '';
        const url = urlInput ? urlInput.value.trim() : '';
        if (title && url) {
            links.push({ title, url });
        }
    });
    return { links };
}

function renderCalendarEditor(block) {
    // כל החגים והמועדים של שנת הלימודים תשפ"ו (2025-2026) לפי מערכת החינוך בישראל
    // מסודר בסדר כרונולוגי
    const defaultHolidays = [
        // ראש השנה תשפ"ו (ספטמבר 2025)
        { name: 'ראש השנה', start_date: '2025-09-15', end_date: '2025-09-16' },
        
        // יום כיפור (אוקטובר 2025)
        { name: 'יום כיפור', start_date: '2025-10-02', end_date: '2025-10-02' },
        
        // סוכות (אוקטובר 2025)
        { name: 'סוכות', start_date: '2025-10-07', end_date: '2025-10-13' },
        
        // שמיני עצרת ושמחת תורה (אוקטובר 2025)
        { name: 'שמיני עצרת', start_date: '2025-10-14', end_date: '2025-10-14' },
        { name: 'שמחת תורה', start_date: '2025-10-15', end_date: '2025-10-15' },
        
        // חנוכה (דצמבר 2025)
        { name: 'חנוכה', start_date: '2025-12-14', end_date: '2025-12-21' },
        
        // ט"ו בשבט תשפ"ו - 2 בפברואר 2026 (יום שני) - יום לימודים
        { name: 'ט"ו בשבט', start_date: '2026-02-02', end_date: '2026-02-02' },
        
        // פורים תשפ"ו - 3-4 במרץ 2026 (שלישי-רביעי) - חופשה
        { name: 'פורים', start_date: '2026-03-03', end_date: '2026-03-04' },
        
        // יום הזיכרון לשואה ולגבורה (אפריל 2026)
        { name: 'יום הזיכרון לשואה ולגבורה', start_date: '2026-04-21', end_date: '2026-04-21' },
        
        // חופשת פסח תשפ"ו - 24 במרץ עד 8 באפריל 2026 (שלישי עד רביעי) - חופשה
        { name: 'פסח', start_date: '2026-03-24', end_date: '2026-04-08' },
        
        // יום הזיכרון לחללי מערכות ישראל (אפריל 2026)
        { name: 'יום הזיכרון לחללי מערכות ישראל', start_date: '2026-04-21', end_date: '2026-04-21' },
        
        // יום העצמאות תשפ"ו - 22 באפריל 2026 (רביעי)
        { name: 'יום העצמאות', start_date: '2026-04-22', end_date: '2026-04-22' },
        
        // ל"ג בעומר תשפ"ו - 5 במאי 2026 (שלישי) - לא לימודים
        { name: 'ל"ג בעומר', start_date: '2026-05-05', end_date: '2026-05-05' },
        
        // יום ירושלים תשפ"ו - 15 במאי 2026 (שישי) - יום לימודים
        { name: 'יום ירושלים', start_date: '2026-05-15', end_date: '2026-05-15' },
        
        // חג השבועות תשפ"ו - 21-22 במאי 2026 (חמישי-שישי) - חופשה
        { name: 'שבועות', start_date: '2026-05-21', end_date: '2026-05-22' },
        
        // חופשת קיץ (יולי-אוגוסט 2026)
        { name: 'חופשת קיץ', start_date: '2026-07-01', end_date: '2026-08-31' }
    ];
    
    let holidays = [];
    if (block.data && block.data.holidays && Array.isArray(block.data.holidays)) {
        holidays = block.data.holidays.map(h => {
            if (h.date && !h.start_date) {
                return {
                    start_date: h.date,
                    end_date: h.end_date || '',
                    name: h.name,
                    has_camp: h.has_camp || false
                };
            }
            return {
                start_date: h.start_date || h.date || '',
                end_date: h.end_date || '',
                name: h.name,
                has_camp: h.has_camp || false
            };
        });
    } else if (block.data && block.data.holidays && Object.keys(block.data.holidays).length > 0) {
        holidays = Object.entries(block.data.holidays).map(([date, name]) => ({ 
            start_date: date, 
            end_date: '',
            name: name,
            has_camp: false
        }));
    } else {
        holidays = defaultHolidays.map(h => ({
            start_date: h.start_date || h.date || '',
            end_date: h.end_date || '',
            name: h.name || '',
            has_camp: false
        }));
    }
    
    holidays.sort((a, b) => (a.start_date || '').localeCompare(b.start_date || ''));
    
    let html = `
        <div class="form-group">
            <label>לוח חופשות וחגים</label>
            <p class="holidays-description">
                ניתן לערוך, למחוק ולהוסיף חופשות וחגים. ניתן לבחור תאריך בודד או טווח תאריכים.
            </p>
            <div id="holidaysList" class="holidays-list"></div>
        </div>
    `;
    
    return html;
}

function loadHolidays(block) {
    const list = document.getElementById('holidaysList');
    if (!list) return;
    
    // Get holidays from block data or use defaults
    const defaultHolidays = [
        { name: 'ראש השנה', start_date: '2025-09-15', end_date: '2025-09-16' },
        { name: 'יום כיפור', start_date: '2025-10-02', end_date: '2025-10-02' },
        { name: 'סוכות', start_date: '2025-10-07', end_date: '2025-10-13' },
        { name: 'שמיני עצרת', start_date: '2025-10-14', end_date: '2025-10-14' },
        { name: 'שמחת תורה', start_date: '2025-10-15', end_date: '2025-10-15' },
        { name: 'חנוכה', start_date: '2025-12-14', end_date: '2025-12-21' },
        { name: 'ט"ו בשבט', start_date: '2026-02-02', end_date: '2026-02-02' },
        { name: 'פורים', start_date: '2026-03-03', end_date: '2026-03-04' },
        { name: 'יום הזיכרון לשואה ולגבורה', start_date: '2026-04-21', end_date: '2026-04-21' },
        { name: 'פסח', start_date: '2026-03-24', end_date: '2026-04-08' },
        { name: 'יום הזיכרון לחללי מערכות ישראל', start_date: '2026-04-21', end_date: '2026-04-21' },
        { name: 'יום העצמאות', start_date: '2026-04-22', end_date: '2026-04-22' },
        { name: 'ל"ג בעומר', start_date: '2026-05-05', end_date: '2026-05-05' },
        { name: 'יום ירושלים', start_date: '2026-05-15', end_date: '2026-05-15' },
        { name: 'שבועות', start_date: '2026-05-21', end_date: '2026-05-22' },
        { name: 'חופשת קיץ', start_date: '2026-07-01', end_date: '2026-08-31' }
    ];
    
    let holidays = [];
    if (block.data && block.data.holidays && Array.isArray(block.data.holidays)) {
        holidays = block.data.holidays.map(h => {
            if (h.date && !h.start_date) {
                return {
                    start_date: h.date,
                    end_date: h.end_date || '',
                    name: h.name,
                    has_camp: h.has_camp || false
                };
            }
            return {
                start_date: h.start_date || h.date || '',
                end_date: h.end_date || '',
                name: h.name,
                has_camp: h.has_camp || false
            };
        });
    } else if (block.data && block.data.holidays && Object.keys(block.data.holidays).length > 0) {
        holidays = Object.entries(block.data.holidays).map(([date, name]) => ({ 
            start_date: date, 
            end_date: '',
            name: name,
            has_camp: false
        }));
    } else {
        holidays = defaultHolidays.map(h => ({
            start_date: h.start_date || h.date || '',
            end_date: h.end_date || '',
            name: h.name || '',
            has_camp: false
        }));
    }
    
    holidays.sort((a, b) => (a.start_date || '').localeCompare(b.start_date || ''));
    
    list.innerHTML = '';
    
    if (holidays.length > 0) {
        holidays.forEach((holiday, index) => {
            const holidayItem = createHolidayItem(index, holiday);
            list.appendChild(holidayItem);
        });
    }
    
    // Always add an empty holiday item at the end
    list.appendChild(createHolidayItem(holidays.length, {}));
}

// Create holiday item (similar to createLessonItem)
function createHolidayItem(index, holiday) {
    const div = document.createElement('div');
    div.className = 'holiday-item';
    div.dataset.index = index;
    
    const name = holiday.name || '';
    const startDate = holiday.start_date || '';
    const endDate = holiday.end_date || '';
    const hasCamp = holiday.has_camp || false;
    
    // Check if this is an empty holiday (for adding new)
    const isEmpty = !name && !startDate;
    
    // Format dates for display
    let datesDisplay = '';
    if (startDate) {
        const start = new Date(startDate + 'T00:00:00');
        const startFormatted = start.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
        if (endDate && endDate !== startDate) {
            const end = new Date(endDate + 'T00:00:00');
            const endFormatted = end.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
            datesDisplay = `${startFormatted} - ${endFormatted}`;
        } else {
            datesDisplay = startFormatted;
        }
    }
    
    div.innerHTML = `
        <div class="holiday-item-content">
            <div class="holiday-info">
                <div class="holiday-name-display ${isEmpty ? 'holiday-name-empty' : ''}" onclick="toggleHolidayEditorByName(this)" data-index="${index}" style="cursor: pointer;">
                    ${escapeHtml(name || 'לחץ להוספת חופשה')}
            </div>
                ${datesDisplay ? `<div class="holiday-dates-display">${datesDisplay}</div>` : ''}
            </div>
            <button type="button" class="btn-remove-holiday" onclick="removeHolidayItem(this)">
                <img src="/assets/files/trash.svg" alt="מחק">
            </button>
        </div>
        <div class="holiday-editor-panel" style="display: none;">
            <div class="holiday-editor-form">
                <input type="text" class="holiday-name-input" placeholder="שם החג/חופשה" value="${escapeHtml(name)}" 
                       data-index="${index}" style="display: none;">
                <div class="holiday-dates-row">
                    <input type="date" class="holiday-start-date-input" placeholder="תאריך התחלה" value="${startDate}" 
                           data-index="${index}" onblur="saveHolidayData(this.closest('.holiday-item'))">
                <span class="holiday-date-separator">עד</span>
                    <input type="date" class="holiday-end-date-input" placeholder="תאריך סיום (אופציונלי)" value="${endDate}" 
                           data-index="${index}" onblur="saveHolidayData(this.closest('.holiday-item'))">
            </div>
                <label class="holiday-camp-label">
                    <input type="checkbox" class="holiday-camp-checkbox" ${hasCamp ? 'checked' : ''} data-index="${index}" onchange="saveHolidayData(this.closest('.holiday-item'))">
                    <span>יש קייטנה</span>
                </label>
            </div>
        </div>
    `;
    return div;
}

function toggleHolidayEditorByName(nameDisplay) {
    const holidayItem = nameDisplay.closest('.holiday-item');
    if (!holidayItem) return;
    
    // Save any currently editing field first
    saveCurrentEditingHoliday();
    
    // Close all other inline editors
    document.querySelectorAll('.holiday-name-display.editing').forEach(el => {
        if (el !== nameDisplay) {
            finishEditingHolidayName(el);
        }
    });
    
    // Convert display to input
    const currentText = nameDisplay.textContent.trim();
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'holiday-name-input-inline';
    input.value = currentText === 'לחץ להוספת חופשה' ? '' : currentText;
    input.placeholder = 'שם החג/חופשה';
    input.style.cssText = 'width: 100%; padding: 0; border: none; outline: none; box-shadow: none; background: transparent; font-size: inherit; font-family: inherit; color: inherit; text-align: right;';
    
    // Replace display with input
    nameDisplay.style.display = 'none';
    nameDisplay.classList.add('editing');
    nameDisplay.parentNode.insertBefore(input, nameDisplay);
    
    // Focus and select
    input.focus();
    input.select();
    
    // Handle blur (save when clicking away)
    input.addEventListener('blur', function() {
        finishEditingHolidayName(nameDisplay, input);
    });
    
    // Handle Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
        }
    });
    
    // Also open the editor panel below for dates and camp
    const editorPanel = holidayItem.querySelector('.holiday-editor-panel');
    if (editorPanel) {
        // Close all other editor panels
        document.querySelectorAll('.holiday-editor-panel').forEach(panel => {
            if (panel !== editorPanel) {
                panel.style.display = 'none';
            }
        });
        // Open this editor panel
        editorPanel.style.display = 'block';
    }
}

function finishEditingHolidayName(nameDisplay, input) {
    if (!input) {
        // Find the input if not provided
        input = nameDisplay.parentNode.querySelector('.holiday-name-input-inline');
    }
    
    if (!input) return;
    
    const holidayItem = nameDisplay.closest('.holiday-item');
    if (!holidayItem) return;
    
    const newValue = input.value.trim();
    
    // Update display
    if (newValue) {
        nameDisplay.textContent = newValue;
        nameDisplay.classList.remove('holiday-name-empty');
    } else {
        nameDisplay.textContent = 'לחץ להוספת חופשה';
        nameDisplay.classList.add('holiday-name-empty');
    }
    
    // Remove input and show display
    input.remove();
    nameDisplay.style.display = '';
    nameDisplay.classList.remove('editing');
    
    // Update hidden input
    const hiddenInput = holidayItem.querySelector('.holiday-name-input');
    if (hiddenInput) {
        hiddenInput.value = newValue;
    }
    
    // Save holiday data
    saveHolidayData(holidayItem);
}

function saveCurrentEditingHoliday() {
    const editingDisplay = document.querySelector('.holiday-name-display.editing');
    if (editingDisplay) {
        const input = editingDisplay.parentNode.querySelector('.holiday-name-input-inline');
        if (input) {
            finishEditingHolidayName(editingDisplay, input);
        }
    }
}

function saveHolidayData(holidayItem) {
    const index = parseInt(holidayItem.dataset.index);
    const nameInput = holidayItem.querySelector('.holiday-name-input');
    const startDateInput = holidayItem.querySelector('.holiday-start-date-input');
    const endDateInput = holidayItem.querySelector('.holiday-end-date-input');
    const campCheckbox = holidayItem.querySelector('.holiday-camp-checkbox');
    
    const name = nameInput ? nameInput.value.trim() : '';
    const startDate = startDateInput ? startDateInput.value.trim() : '';
    const endDate = endDateInput ? endDateInput.value.trim() : '';
    const hasCamp = campCheckbox ? campCheckbox.checked : false;
    
    // Update display
    const nameDisplay = holidayItem.querySelector('.holiday-name-display');
    const datesDisplay = holidayItem.querySelector('.holiday-dates-display');
    
    if (nameDisplay && name) {
        nameDisplay.textContent = name;
        nameDisplay.classList.remove('holiday-name-empty');
    }
    
    // Update dates display
    if (datesDisplay) {
        if (startDate) {
            const start = new Date(startDate + 'T00:00:00');
            const startFormatted = start.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
            if (endDate && endDate !== startDate) {
                const end = new Date(endDate + 'T00:00:00');
                const endFormatted = end.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
                datesDisplay.textContent = `${startFormatted} - ${endFormatted}`;
            } else {
                datesDisplay.textContent = startFormatted;
            }
        } else {
            datesDisplay.textContent = '';
        }
    } else if (startDate) {
        // Create dates display if it doesn't exist
        const datesDiv = document.createElement('div');
        datesDiv.className = 'holiday-dates-display';
        const start = new Date(startDate + 'T00:00:00');
        const startFormatted = start.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
        if (endDate && endDate !== startDate) {
            const end = new Date(endDate + 'T00:00:00');
            const endFormatted = end.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
            datesDiv.textContent = `${startFormatted} - ${endFormatted}`;
        } else {
            datesDiv.textContent = startFormatted;
        }
        const infoDiv = holidayItem.querySelector('.holiday-info');
        if (infoDiv) {
            infoDiv.appendChild(datesDiv);
        }
    }
    
    // If this holiday has content and there's no empty holiday at the end, add one
    if (name || startDate) {
        const list = document.getElementById('holidaysList');
        if (list) {
            const items = list.querySelectorAll('.holiday-item');
            const lastItem = items[items.length - 1];
            if (lastItem) {
                const lastNameDisplay = lastItem.querySelector('.holiday-name-display');
                const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
                const lastStartDate = lastItem.querySelector('.holiday-start-date-input');
                const lastStartDateValue = lastStartDate ? lastStartDate.value.trim() : '';
                
                if (lastName !== 'לחץ להוספת חופשה' || lastStartDateValue) {
                    // Add new empty holiday
                    const newIndex = items.length;
                    list.appendChild(createHolidayItem(newIndex, {}));
                }
            }
        }
    }
}

function addHolidayItem() {
    const list = document.getElementById('holidaysList');
    if (!list) return;
    
    // Remove empty holiday at the end if exists
    const items = list.querySelectorAll('.holiday-item');
    if (items.length > 0) {
        const lastItem = items[items.length - 1];
        const lastNameDisplay = lastItem.querySelector('.holiday-name-display');
        const lastName = lastNameDisplay ? lastNameDisplay.textContent.trim() : '';
        const lastStartDate = lastItem.querySelector('.holiday-start-date-input');
        const lastStartDateValue = lastStartDate ? lastStartDate.value.trim() : '';
        
        if (lastName === 'לחץ להוספת חופשה' && !lastStartDateValue) {
            // Last item is empty, just open it for editing
            if (lastNameDisplay) {
                toggleHolidayEditorByName(lastNameDisplay);
            }
            return;
        }
    }
    
    // Add new empty holiday
    const newIndex = items.length;
    const newHoliday = createHolidayItem(newIndex, {});
    list.appendChild(newHoliday);
    
    // Open it for editing
    const nameDisplay = newHoliday.querySelector('.holiday-name-display');
    if (nameDisplay) {
        setTimeout(() => {
            toggleHolidayEditorByName(nameDisplay);
        }, 50);
    }
}

function removeHolidayItem(button) {
    button.closest('.holiday-item').remove();
}

function collectCalendarData(form) {
    const holidays = [];
    const holidayItems = form.querySelectorAll('.holiday-item');
    holidayItems.forEach(item => {
        const startDateInput = item.querySelector('.holiday-start-date-input');
        const endDateInput = item.querySelector('.holiday-end-date-input');
        const nameInput = item.querySelector('.holiday-name-input');
        const campCheckbox = item.querySelector('.holiday-camp-checkbox');
        
        const startDate = startDateInput ? startDateInput.value.trim() : '';
        const endDate = endDateInput ? endDateInput.value.trim() : '';
        const name = nameInput ? nameInput.value.trim() : '';
        const hasCamp = campCheckbox ? campCheckbox.checked : false;
        
        // Only include holidays with at least a name or start date
        if (startDate || name) {
            const holiday = {
                start_date: startDate || '',
                name: name || '',
                has_camp: hasCamp
            };
            
            if (endDate) {
                holiday.end_date = endDate;
            }
            holidays.push(holiday);
        }
    });
    return { holidays };
}


// ========== Announcement Functions ==========
let announcementQuill = null;
let currentAnnouncementId = null;

function toggleAddMenu() {
    const menu = document.getElementById('floating-add-menu-items');
    const icon = document.getElementById('floating-add-icon');
    if (menu && icon) {
        const isOpen = menu.classList.contains('show');
        
        if (!isOpen) {
            // Open menu
            const overlay = menu.querySelector('.floating-add-menu-overlay');
            if (overlay && !overlay.hasAttribute('data-click-attached')) {
                overlay.setAttribute('data-click-attached', 'true');
                overlay.addEventListener('click', function() {
                    toggleAddMenu();
                });
            }
            
            menu.classList.remove('closing');
            menu.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Trigger animation
            setTimeout(() => {
                menu.classList.add('show');
            }, 10);
            
            icon.classList.add('icon-open');
        } else {
            // Close menu
            menu.classList.remove('show');
            menu.classList.add('closing');
            icon.classList.remove('icon-open');
            document.body.style.overflow = '';
            
            setTimeout(() => {
                menu.style.display = 'none';
                menu.classList.remove('closing');
            }, 300); // Match CSS transition duration
        }
    }
}

async function addBlockDirectly(blockType) {
    // Block creation is no longer available - only editing existing blocks
    const existingBlock = blocks.find(b => b.type === blockType);
    if (existingBlock) {
        // Open edit modal for existing block
        await editBlock(existingBlock.id);
        return;
    }
    
    alert('לא ניתן ליצור בלוקים חדשים. ניתן לערוך בלוקים קיימים בלבד.');
}

function openAddAnnouncementModal() {
    currentAnnouncementId = null;
    const modal = document.getElementById('announcementModal');
    if (!modal) {
        console.error('Announcement modal not found');
        return;
    }
    
    // Reset form
    const titleInput = document.getElementById('announcementTitle');
    const dateInput = document.getElementById('announcementDate');
    const messageEl = document.getElementById('announcementModalMessage');
    const documentUpload = document.getElementById('announcementDocumentUpload');
    const documentPreview = document.getElementById('announcementDocumentPreview');
    const statusEl = document.getElementById('announcementDocumentProcessingStatus');
    
    if (titleInput) titleInput.value = '';
    // Set default date to tomorrow
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const year = tomorrow.getFullYear();
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const day = String(tomorrow.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;
    }
    const dayInput = document.getElementById('announcementDay');
    if (dayInput) dayInput.value = '';
    if (messageEl) messageEl.innerHTML = '';
    if (documentUpload) documentUpload.value = '';
    if (documentPreview) documentPreview.innerHTML = '';
    if (statusEl) statusEl.style.display = 'none';
    
    // Reset date type selection
    const dateTypeNone = document.getElementById('announcementDateTypeNone');
    if (dateTypeNone) dateTypeNone.checked = true;
    if (typeof toggleAnnouncementDateType === 'function') {
        toggleAnnouncementDateType();
    }
    
    // Clear Quill editor
    if (announcementQuill) {
        announcementQuill.setContents([]);
    }
    
    // Update modal title
    const titleEl = document.getElementById('announcementModalTitle');
    if (titleEl) titleEl.textContent = 'הוסף הודעה חדשה';
    
    // Show modal first
    openModalWithAnimation('announcementModal');
    
    // Scroll to top
    modal.scrollTop = 0;
    
    // Initialize Quill after modal is shown (to ensure element is visible)
    setTimeout(() => {
        if (!announcementQuill) {
            const editorEl = document.getElementById('announcementEditor');
            if (editorEl && typeof Quill !== 'undefined') {
                console.log('Initializing Quill editor...');
                announcementQuill = new Quill('#announcementEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'הזן הודעה...',
                    direction: 'rtl'
                });
                // Set RTL for the editor content
                const editorContainer = document.querySelector('#announcementEditor .ql-editor');
                if (editorContainer) {
                    editorContainer.style.textAlign = 'right';
                    editorContainer.style.direction = 'rtl';
                }
                console.log('Quill editor initialized successfully');
            } else {
                console.error('Quill not available or editor element not found', {
                    editorEl: !!editorEl,
                    quillAvailable: typeof Quill !== 'undefined'
                });
            }
        } else {
            announcementQuill.setContents([]);
        }
    }, 100);
    
    // Setup form handler
    const form = document.getElementById('announcementForm');
    if (form) {
        // Remove existing handlers
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Add new handler
        const updatedForm = document.getElementById('announcementForm');
        if (updatedForm) {
            updatedForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await saveAnnouncement();
            });
        }
    }
    
    // Update save button to use the form submit
    setTimeout(() => {
        const saveBtn = document.querySelector('.btn-announcement-edit-save');
        if (saveBtn) {
            saveBtn.onclick = function() {
                const form = document.getElementById('announcementForm');
                if (form) {
                    form.requestSubmit();
                }
            };
        }
    }, 150);
}

// Announcement menu functions
function toggleAnnouncementMenu(event, announcementId) {
    event.stopPropagation();
    
    // Close all other menus
    document.querySelectorAll('.announcement-menu-popup').forEach(menu => {
        if (menu.id !== `announcement-menu-${announcementId}`) {
            menu.classList.remove('active');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`announcement-menu-${announcementId}`);
    if (menu) {
        const isActive = menu.classList.contains('active');
        if (isActive) {
            closeAnnouncementMenu(announcementId);
        } else {
            menu.classList.add('active');
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenuOnClick(e) {
                    if (!menu.contains(e.target) && !e.target.closest('.btn-announcement-menu')) {
                        closeAnnouncementMenu(announcementId);
                        document.removeEventListener('click', closeMenuOnClick);
                    }
                }, { once: true });
            }, 10);
        }
    }
}

function closeAnnouncementMenu(announcementId) {
    const menu = document.getElementById(`announcement-menu-${announcementId}`);
    if (menu) {
        menu.classList.remove('active');
    }
}

async function editAnnouncement(id) {
    // Close any open menus
    document.querySelectorAll('.announcement-menu-popup').forEach(menu => {
        menu.classList.remove('active');
    });
    
    currentAnnouncementId = id;
    const modal = document.getElementById('announcementModal');
    if (!modal) {
        console.error('Announcement modal not found');
        return;
    }
    
    // Reset document upload
    const documentUpload = document.getElementById('announcementDocumentUpload');
    const documentPreview = document.getElementById('announcementDocumentPreview');
    const statusEl = document.getElementById('announcementDocumentProcessingStatus');
    if (documentUpload) documentUpload.value = '';
    if (documentPreview) documentPreview.innerHTML = '';
    if (statusEl) statusEl.style.display = 'none';
    
    // Update modal title
    const titleEl = document.getElementById('announcementModalTitle');
    if (titleEl) titleEl.textContent = 'ערוך הודעה';
    
    // Show modal first
    openModalWithAnimation('announcementModal');
    
    // Scroll to top
    modal.scrollTop = 0;
    
    // Initialize Quill after modal is shown
    setTimeout(() => {
        if (!announcementQuill) {
            const editorEl = document.getElementById('announcementEditor');
            if (editorEl && typeof Quill !== 'undefined') {
                console.log('Initializing Quill editor...');
                announcementQuill = new Quill('#announcementEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'הזן הודעה...',
                    direction: 'rtl'
                });
                // Set RTL for the editor content
                const editorContainer = document.querySelector('#announcementEditor .ql-editor');
                if (editorContainer) {
                    editorContainer.style.textAlign = 'right';
                    editorContainer.style.direction = 'rtl';
                }
                console.log('Quill editor initialized successfully');
            }
        }
        
        // Load announcement data after Quill is ready
        loadAnnouncementData(id);
    }, 100);
}

async function loadAnnouncementData(id) {
    // Load announcement data
    try {
        const result = await API.get(`/api/pages/${pageDbId}/announcements/${id}`);
        if (result.ok && result.announcement) {
            const announcement = result.announcement;
            
            // Set title and date
            const titleInput = document.getElementById('announcementTitle');
            const dateInput = document.getElementById('announcementDate');
            const dayInput = document.getElementById('announcementDay');
            
            if (titleInput) titleInput.value = announcement.title || '';
            
            // Handle date or day selection
            if (announcement.date) {
                // Check if it's a day of week (0-6) or a date
                const dateValue = announcement.date;
                // Try to parse as date first
                const dateObj = new Date(dateValue);
                if (!isNaN(dateObj.getTime())) {
                    // It's a valid date
                    const dateTypeDate = document.getElementById('announcementDateTypeDate');
                    if (dateTypeDate) dateTypeDate.checked = true;
                    toggleAnnouncementDateType();
                    if (dateInput) {
                        // Format date as YYYY-MM-DD
                        const year = dateObj.getFullYear();
                        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        dateInput.value = `${year}-${month}-${day}`;
                    }
                } else {
                    // Might be a day of week (0-6)
                    const dayNum = parseInt(dateValue);
                    if (!isNaN(dayNum) && dayNum >= 0 && dayNum <= 6) {
                        const dateTypeDay = document.getElementById('announcementDateTypeDay');
                        if (dateTypeDay) dateTypeDay.checked = true;
                        toggleAnnouncementDateType();
                        if (dayInput) dayInput.value = String(dayNum);
                    }
                }
            } else {
                // No date - permanent announcement
                const dateTypeNone = document.getElementById('announcementDateTypeNone');
                if (dateTypeNone) dateTypeNone.checked = true;
                toggleAnnouncementDateType();
            }
            
            // Set content in Quill
            if (announcementQuill && announcement.html) {
                // Check if content is just placeholder
                const htmlContent = announcement.html.trim();
                if (htmlContent && htmlContent !== '<p><br></p>' && htmlContent !== '<p></p>') {
                const delta = announcementQuill.clipboard.convert({ html: announcement.html });
                announcementQuill.setContents(delta);
                } else {
                    // Clear if empty
                    announcementQuill.setContents([]);
                }
            } else if (announcementQuill) {
                // Clear if no content
                announcementQuill.setContents([]);
            }
        }
    } catch (error) {
        console.error('Failed to load announcement:', error);
        alert('שגיאה בטעינת ההודעה: ' + error.message);
        return;
    }
    
    // Setup form handler
    const form = document.getElementById('announcementForm');
    if (form) {
        // Remove existing handlers
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Add new handler
        const updatedForm = document.getElementById('announcementForm');
        if (updatedForm) {
            updatedForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                await saveAnnouncement();
            });
        }
    }
    
    // Update save button to use the form submit
    setTimeout(() => {
        const saveBtn = document.querySelector('.btn-announcement-edit-save');
        if (saveBtn) {
            saveBtn.onclick = function() {
                const form = document.getElementById('announcementForm');
                if (form) {
                    form.requestSubmit();
                }
            };
        }
    }, 150);
}

async function saveAnnouncement() {
    if (!announcementQuill) {
        alert('עורך ההודעות לא זמין');
        return;
    }
    
    // Get HTML content and check if it's empty (just placeholder)
    let html = announcementQuill.root.innerHTML.trim();
    // Remove empty paragraphs and check if there's actual content
    if (html === '<p><br></p>' || html === '<p></p>' || html === '' || !html) {
        html = null;
    }
    
    // Log for debugging
    console.log('Saving announcement - HTML length:', html ? html.length : 0);
    console.log('Saving announcement - HTML preview:', html ? html.substring(0, 100) : 'null');
    
    const titleInput = document.getElementById('announcementTitle');
    const dateInput = document.getElementById('announcementDate');
    const dayInput = document.getElementById('announcementDay');
    const messageEl = document.getElementById('announcementModalMessage');
    
    // Determine date based on selection type
    let dateValue = null;
    const dateTypeDay = document.getElementById('announcementDateTypeDay');
    const dateTypeDate = document.getElementById('announcementDateTypeDate');
    
    if (dateTypeDay && dateTypeDay.checked && dayInput && dayInput.value) {
        // Day of week selected - calculate next occurrence
        const selectedDay = parseInt(dayInput.value);
        const today = new Date();
        const currentDay = today.getDay(); // 0 = Sunday
        const currentHour = today.getHours();
        
        // If it's after 16:00, show from tomorrow
        let daysToAdd = selectedDay - currentDay;
        if (daysToAdd < 0) {
            daysToAdd += 7; // Next week
        } else if (daysToAdd === 0 && currentHour >= 16) {
            daysToAdd = 7; // Next week
        }
        
        const targetDate = new Date(today);
        targetDate.setDate(today.getDate() + daysToAdd);
        
        // Format as YYYY-MM-DD
        const year = targetDate.getFullYear();
        const month = String(targetDate.getMonth() + 1).padStart(2, '0');
        const day = String(targetDate.getDate()).padStart(2, '0');
        dateValue = `${year}-${month}-${day}`;
    } else if (dateTypeDate && dateTypeDate.checked && dateInput && dateInput.value) {
        // Specific date selected
        dateValue = dateInput.value;
    }
    // If dateTypeNone is selected, dateValue remains null (permanent announcement)
    
    // Ensure html is always a string (empty string if null)
    const htmlToSend = html || '';
    
    // Log for debugging
    console.log('Saving announcement - Final HTML length:', htmlToSend.length);
    console.log('Saving announcement - Final HTML preview:', htmlToSend.substring(0, 100));
    
    const data = {
        html: htmlToSend,
        title: titleInput ? titleInput.value.trim() : null,
        date: dateValue,
        csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
    };
    
    try {
        if (messageEl) {
            messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-blue); color: white; border-radius: 8px;">שומר...</div>';
        }
        
        let result;
        if (currentAnnouncementId) {
            result = await API.put(`/api/pages/${pageDbId}/announcements/${currentAnnouncementId}`, data);
        } else {
            result = await API.post(`/api/pages/${pageDbId}/announcements`, data);
        }
        
        if (result && result.ok) {
            if (messageEl) {
                messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px;">✅ ההודעה נשמרה בהצלחה!</div>';
            }
            setTimeout(() => {
                closeAnnouncementModal();
                window.location.reload();
            }, 1000);
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בשמירה';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('שגיאה בשמירה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error saving announcement:', error);
        const errorMsg = error.message || 'שגיאה בשמירה';
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
        }
        alert('שגיאה בשמירת הודעה: ' + errorMsg);
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק הודעה זו?')) {
        return;
    }
    
    try {
        const result = await API.delete(`/api/pages/${pageDbId}/announcements/${id}`);
        if (result && result.ok) {
            window.location.reload();
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה במחיקה';
            alert('שגיאה במחיקה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error deleting announcement:', error);
        alert('שגיאה במחיקה: ' + error.message);
    }
}

async function removeAnnouncement(id) {
    if (!confirm('האם אתה בטוח שברצונך להסיר הודעה זו? ההודעה תימחק.')) {
        return;
    }
    
    try {
        const result = await API.delete(`/api/pages/${pageDbId}/announcements/${id}`);
        if (result && result.ok) {
            window.location.reload();
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בהסרה';
            alert('שגיאה בהסרה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error removing announcement:', error);
        alert('שגיאה בהסרה: ' + error.message);
    }
}

function closeAnnouncementModal() {
    closeModalWithAnimation('announcementModal');
    currentAnnouncementId = null;
    
    // Clear form data
    const form = document.getElementById('announcementForm');
    if (form) {
        form.reset();
    }
    
    // Clear Quill editor
    if (announcementQuill) {
        announcementQuill.setContents([]);
    }
    
    // Reset document upload
    const documentUpload = document.getElementById('announcementDocumentUpload');
    const documentPreview = document.getElementById('announcementDocumentPreview');
    const statusEl = document.getElementById('announcementDocumentProcessingStatus');
    if (documentUpload) documentUpload.value = '';
    if (documentPreview) documentPreview.innerHTML = '';
    if (statusEl) statusEl.style.display = 'none';
}

function handleAnnouncementDocumentFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const preview = document.getElementById('announcementDocumentPreview');
    if (preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div style="border: 2px dashed var(--color-blue); border-radius: 12px; padding: 1rem; text-align: center; background: #fff;">
                    <img src="${e.target.result}" alt="תצוגה מקדימה" style="max-width: 100%; max-height: 200px; border-radius: 4px; margin-bottom: 0.5rem; box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);">
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">${escapeHtml(file.name)}</p>
                    <button type="button" id="processAnnouncementDocumentBtn" style="width: 100%; padding: 0.75rem; background: var(--color-blue); color: white; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; font-size: 1rem; box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);" onclick="processAnnouncementDocument()">
                        🔄 עבד מסמך והוסף סיכום
                    </button>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
}

async function processAnnouncementDocument() {
    const input = document.getElementById('announcementDocumentUpload');
    if (!input || !input.files || !input.files[0]) {
        alert('נא לבחור מסמך קודם');
        return;
    }
    
    const file = input.files[0];
    const statusEl = document.getElementById('announcementDocumentProcessingStatus');
    const processBtn = document.getElementById('processAnnouncementDocumentBtn');
    const messageEl = document.getElementById('announcementModalMessage');
    
    if (statusEl) statusEl.style.display = 'block';
    if (processBtn) {
        processBtn.disabled = true;
        processBtn.textContent = '⏳ מעבד...';
        processBtn.style.opacity = '0.6';
        processBtn.style.cursor = 'not-allowed';
    }
    
    const formData = new FormData();
    formData.append('image', file);
    formData.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');
    
    try {
        const apiUrl = '/api/ai/extract-document';
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        // Hide processing status
        if (statusEl) {
            statusEl.style.display = 'none';
        }
        
        if (result.ok && result.summary) {
            console.log('Document processed successfully, summary:', result.summary);
            
            // Set title if available and title field is empty
            if (result.title) {
                const titleInput = document.getElementById('announcementTitle');
                if (titleInput && !titleInput.value.trim()) {
                    titleInput.value = result.title.trim();
                    console.log('Title set:', result.title);
                }
            }
            
            // Add summary to Quill editor
            if (announcementQuill) {
                console.log('Quill editor found, current length:', announcementQuill.getLength());
                
                const currentLength = announcementQuill.getLength();
                
                // Prepare the summary HTML with image link if available
                let summaryHtml = '';
                if (currentLength > 1) {
                    summaryHtml = '<p><br></p><p><strong>סיכום מסמך:</strong></p>';
                } else {
                    summaryHtml = '<p><strong>סיכום מסמך:</strong></p>';
                }
                
                // Add image link if available
                if (result.image_path) {
                    const imageUrl = '/' + result.image_path;
                    summaryHtml += `<p><a href="${imageUrl}" target="_blank" style="color: #0066FF; text-decoration: underline;">📎 צפה במסמך המקורי</a></p>`;
                }
                
                // Process the summary HTML from AI
                let processedSummary = result.summary.trim();
                
                // If the summary doesn't start with a tag, wrap it in <p>
                if (!processedSummary.startsWith('<')) {
                    processedSummary = '<p>' + processedSummary + '</p>';
                }
                
                // Replace <br> and <br/> with proper paragraph breaks
                processedSummary = processedSummary.replace(/<br\s*\/?>/gi, '</p><p>');
                
                // Ensure all content is wrapped in paragraphs
                if (!processedSummary.includes('<p>')) {
                    processedSummary = '<p>' + processedSummary + '</p>';
                }
                
                summaryHtml += processedSummary;
                
                console.log('Converting HTML to Delta and inserting, HTML:', summaryHtml);
                
                // Get current HTML
                const currentHtml = announcementQuill.root.innerHTML;
                
                // Append the new HTML to current content
                let newHtml = currentHtml;
                if (currentLength > 1 && !currentHtml.endsWith('<p><br></p>')) {
                    newHtml += '<p><br></p>';
                }
                newHtml += summaryHtml;
                
                console.log('New HTML:', newHtml);
                
                // Convert the complete HTML to Delta and set it
                const delta = announcementQuill.clipboard.convert(newHtml);
                announcementQuill.setContents(delta, 'user');
                
                // Move cursor to end and scroll to show the new content
                const newLength = announcementQuill.getLength();
                announcementQuill.setSelection(newLength, 'user');
                
                console.log('Text inserted, new length:', newLength);
                
                // Force update and scroll to bottom
                setTimeout(() => {
                    announcementQuill.root.scrollTop = announcementQuill.root.scrollHeight;
                    announcementQuill.focus();
                    console.log('Editor scrolled and focused');
                }, 100);
            } else {
                console.error('Quill editor not found!');
                alert('עורך ההודעות לא זמין. נא לנסות שוב.');
            }
            
            // Show success message
            if (messageEl) {
                messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px;">✅ המסמך עובד בהצלחה! הסיכום נוסף להודעה.</div>';
                setTimeout(() => {
                    messageEl.innerHTML = '';
                }, 5000);
            }
            
            // Clear the file input and preview
            if (input) {
                input.value = '';
            }
            const preview = document.getElementById('announcementDocumentPreview');
            if (preview) {
                preview.innerHTML = '';
            }
        } else {
            const errorMsg = result.message_he || result.reason || 'לא ניתן לעבד את המסמך';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('❌ שגיאה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Document processing error:', error);
        const errorMsg = 'שגיאה בעיבוד המסמך: ' + (error.message || 'שגיאה לא ידועה');
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
        }
        alert('❌ ' + errorMsg);
    } finally {
        // Re-enable the process button
        if (processBtn) {
            processBtn.disabled = false;
            processBtn.textContent = '🔄 עבד מסמך והוסף סיכום';
            processBtn.style.opacity = '1';
            processBtn.style.cursor = 'pointer';
        }
        
        // Hide processing status
        if (statusEl) {
            statusEl.style.display = 'none';
        }
    }
}

async function deleteBlock(blockId) {
    if (!confirm('האם אתה בטוח שברצונך למחוק בלוק זה?')) {
        return;
    }
    
    try {
        const result = await API.delete(`/api/pages/${pageDbId}/blocks/${blockId}`);
        if (result && result.ok) {
            window.location.reload();
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה במחיקה';
            alert('שגיאה במחיקה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error deleting block:', error);
        alert('שגיאה במחיקה: ' + error.message);
    }
}

// Block menu functions
function toggleBlockMenu(event, blockId) {
    event.stopPropagation();
    
    // Close all other menus
    document.querySelectorAll('.block-menu-popup').forEach(menu => {
        if (menu.id !== `block-menu-${blockId}`) {
            menu.classList.remove('active');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`block-menu-${blockId}`);
    if (menu) {
        const isActive = menu.classList.contains('active');
        if (isActive) {
            closeBlockMenu(blockId);
        } else {
            menu.classList.add('active');
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenuOnClick(e) {
                    if (!menu.contains(e.target) && !e.target.closest('.btn-block-menu')) {
                        closeBlockMenu(blockId);
                        document.removeEventListener('click', closeMenuOnClick);
                    }
                }, { once: true });
            }, 10);
        }
    }
}

function closeBlockMenu(blockId) {
    const menu = document.getElementById(`block-menu-${blockId}`);
    if (menu) {
        menu.classList.remove('active');
    }
}

function openEditPageTitleModal() {
    const modal = document.getElementById('editPageTitleModal');
    if (!modal) {
        console.error('Edit page title modal not found');
        return;
    }
    
    // Get current values from page data (passed from server)
    const schoolNameInput = document.getElementById('editSchoolName');
    const cityNameInput = document.getElementById('editCityName');
    const classGradeInput = document.getElementById('editClassGrade');
    const classNumberInput = document.getElementById('editClassNumber');
    const messageEl = document.getElementById('editPageTitleMessage');
    
    // Try to get values from page data if available
    if (typeof pageData !== 'undefined' && pageData) {
        if (schoolNameInput && pageData.school_name) schoolNameInput.value = pageData.school_name;
        if (cityNameInput) {
            if (pageData.city_name) {
                // Check if it's a select or input
                if (cityNameInput.tagName === 'SELECT') {
                    cityNameInput.value = pageData.city_name;
                    // Check if value exists in options, if not show custom input
                    const optionExists = Array.from(cityNameInput.options).some(opt => opt.value === pageData.city_name);
                    if (!optionExists) {
                        const customInput = document.getElementById('editCityNameCustom');
                        if (customInput) {
                            customInput.value = pageData.city_name;
                            customInput.style.display = 'block';
                            cityNameInput.value = '';
                        }
                    }
                } else {
                    cityNameInput.value = pageData.city_name;
                }
            }
        }
        if (classGradeInput && pageData.class_grade) {
            classGradeInput.value = pageData.class_grade;
        }
        if (classNumberInput && pageData.class_number) {
            classNumberInput.value = pageData.class_number;
        }
        
        // Setup city custom input handler after values are set
        const cityNameCustom = document.getElementById('editCityNameCustom');
        if (cityNameInput && cityNameInput.tagName === 'SELECT' && cityNameCustom) {
            // Remove existing listeners
            const newSelect = cityNameInput.cloneNode(true);
            cityNameInput.parentNode.replaceChild(newSelect, cityNameInput);
            const newCityNameInput = document.getElementById('editCityName');
            
            newCityNameInput.addEventListener('change', function() {
                if (this.value === '') {
                    cityNameCustom.style.display = 'block';
                } else {
                    cityNameCustom.style.display = 'none';
                    cityNameCustom.value = '';
                }
            });
            
            // Show custom input if city is not in list
            if (pageData.city_name) {
                const optionExists = Array.from(newCityNameInput.options).some(opt => opt.value === pageData.city_name);
                if (!optionExists) {
                    cityNameCustom.value = pageData.city_name;
                    cityNameCustom.style.display = 'block';
                    newCityNameInput.value = '';
                }
            }
        }
    } else {
        // Fallback: try to parse from title text
        const titleText = document.getElementById('page-title-text');
        if (titleText) {
            const fullText = titleText.textContent.trim();
            const parts = fullText.split(' | ');
            if (schoolNameInput && parts[0]) schoolNameInput.value = parts[0];
        }
    }
    
    if (messageEl) messageEl.innerHTML = '';
    
    openModalWithAnimation('editPageTitleModal');
    
    // Setup form handler
    const form = document.getElementById('editPageTitleForm');
    if (form) {
        // Remove existing handlers
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Add new handler
        document.getElementById('editPageTitleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            await savePageTitle();
        });
    }
}

function closeEditPageTitleModal() {
    closeModalWithAnimation('editPageTitleModal');
}

async function savePageTitle() {
    const schoolNameInput = document.getElementById('editSchoolName');
    const cityNameInput = document.getElementById('editCityName');
    const classGradeInput = document.getElementById('editClassGrade');
    const classNumberInput = document.getElementById('editClassNumber');
    const messageEl = document.getElementById('editPageTitleMessage');
    
    if (!schoolNameInput || !cityNameInput || !classGradeInput || !classNumberInput) {
        alert('שדות לא נמצאו');
        return;
    }
    
    const schoolName = schoolNameInput.value.trim();
    
    // Get city name - check if custom input exists and has value
    let cityName = '';
    const cityNameCustom = document.getElementById('editCityNameCustom');
    if (cityNameCustom && cityNameCustom.value.trim()) {
        cityName = cityNameCustom.value.trim();
    } else if (cityNameInput) {
        cityName = cityNameInput.value.trim();
    }
    
    const classGrade = classGradeInput ? classGradeInput.value.trim() : '';
    const classNumber = classNumberInput ? classNumberInput.value.trim() : '';
    
    if (!schoolName || !cityName || !classGrade || !classNumber) {
        if (messageEl) {
            messageEl.innerHTML = '<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">נא למלא את כל השדות</div>';
        }
        return;
    }
    
    // Build class_title from grade and number
    const classTitle = `כיתה ${classGrade}${classNumber}`;
    
    try {
        if (messageEl) {
            messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-blue); color: white; border-radius: 8px;">שומר...</div>';
        }
        
        const result = await API.post(`/api/pages/${pageDbId}/settings`, {
            school_name: schoolName,
            city_name: cityName,
            class_grade: classGrade,
            class_number: classNumber,
            class_title: classTitle,
            csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
        });
        
        if (result && result.ok) {
            if (messageEl) {
                messageEl.innerHTML = '<div style="padding: 1rem; background: var(--color-green); color: white; border-radius: 8px;">✅ הכותרת נשמרה בהצלחה!</div>';
            }
            setTimeout(() => {
                closeEditPageTitleModal();
                window.location.reload();
            }, 1000);
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בשמירה';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('שגיאה בשמירה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error saving page title:', error);
        const errorMsg = error.message || 'שגיאה בשמירה';
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
        }
        alert('שגיאה בשמירת כותרת: ' + errorMsg);
    }
}

// Event functions
let currentEventId = null;

function openAddEventModal() {
    currentEventId = null;
    const modal = document.getElementById('eventModal');
    if (!modal) {
        console.error('Event modal not found');
        return;
    }
    
    // Reset form
    const nameInput = document.getElementById('eventName');
    const dateInput = document.getElementById('eventDate');
    const timeInput = document.getElementById('eventTime');
    const locationInput = document.getElementById('eventLocation');
    const descriptionInput = document.getElementById('eventDescription');
    const publishedInput = document.getElementById('eventPublished');
    const messageEl = document.getElementById('eventModalMessage');
    
    if (nameInput) nameInput.value = '';
    // Set default date to tomorrow
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const year = tomorrow.getFullYear();
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const day = String(tomorrow.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;
    }
    if (timeInput) timeInput.value = '';
    if (locationInput) locationInput.value = '';
    if (descriptionInput) descriptionInput.value = '';
    if (publishedInput) publishedInput.checked = false;
    if (messageEl) messageEl.innerHTML = '';
    
    // Update modal title
    const titleEl = document.getElementById('eventModalTitle');
    if (titleEl) titleEl.textContent = 'הוסף אירוע חדש';
    
    // Show modal
    openModalWithAnimation('eventModal');
    
    // Scroll to top
    modal.scrollTop = 0;
    
    // Attach form handler
    setTimeout(() => {
        const form = document.getElementById('eventForm');
        if (form) {
            form.onsubmit = async function(e) {
                e.preventDefault();
                await saveEvent();
            };
        }
    }, 150);
}

function closeEventModal() {
    closeModalWithAnimation('eventModal');
    currentEventId = null;
    
    // Clear form data
    const form = document.getElementById('eventForm');
    if (form) {
        form.reset();
    }
}

async function editEvent(id) {
    currentEventId = id;
    const modal = document.getElementById('eventModal');
    if (!modal) {
        console.error('Event modal not found');
        return;
    }
    
    try {
        const result = await API.get(`/api/pages/${pageDbId}/events/${id}`);
        if (!result || !result.ok || !result.event) {
            alert('שגיאה בטעינת אירוע: ' + (result?.message_he || 'אירוע לא נמצא'));
            return;
        }
        
        const event = result.event;
        
        // Reset form
        const nameInput = document.getElementById('eventName');
        const dateInput = document.getElementById('eventDate');
        const timeInput = document.getElementById('eventTime');
        const locationInput = document.getElementById('eventLocation');
        const descriptionInput = document.getElementById('eventDescription');
        const publishedInput = document.getElementById('eventPublished');
        const messageEl = document.getElementById('eventModalMessage');
        
        if (nameInput) nameInput.value = event.name || '';
        
        // Parse date and time from event.date
        if (dateInput && event.date) {
            const dateTime = event.date.split(' ');
            if (dateTime.length >= 1) {
                dateInput.value = dateTime[0];
            }
        } else if (dateInput) {
            dateInput.value = '';
        }
        
        // Set time from event.time or from date string
        if (timeInput) {
            if (event.time) {
                timeInput.value = event.time;
            } else if (event.date && event.date.includes(' ')) {
                const parts = event.date.split(' ');
                if (parts.length >= 2) {
                    timeInput.value = parts[1];
                } else {
                    timeInput.value = '';
                }
            } else {
                timeInput.value = '';
            }
        }
        
        if (locationInput) locationInput.value = event.location || '';
        if (descriptionInput) descriptionInput.value = event.description || '';
        if (publishedInput) publishedInput.checked = event.published ? true : false;
        if (messageEl) messageEl.innerHTML = '';
        
        // Update modal title
        const titleEl = document.getElementById('eventModalTitle');
        if (titleEl) titleEl.textContent = 'ערוך אירוע';
        
        // Show modal
        openModalWithAnimation('eventModal');
        
        // Scroll to top
        modal.scrollTop = 0;
        
        // Attach form handler
        setTimeout(() => {
            const form = document.getElementById('eventForm');
            if (form) {
                form.onsubmit = async function(e) {
                    e.preventDefault();
                    await saveEvent();
                };
            }
        }, 150);
    } catch (error) {
        console.error('Failed to load event:', error);
        alert('שגיאה בטעינת האירוע: ' + error.message);
    }
}

async function saveEvent() {
    const nameInput = document.getElementById('eventName');
    const dateInput = document.getElementById('eventDate');
    const timeInput = document.getElementById('eventTime');
    const locationInput = document.getElementById('eventLocation');
    const descriptionInput = document.getElementById('eventDescription');
    const publishedInput = document.getElementById('eventPublished');
    const messageEl = document.getElementById('eventModalMessage');
    
    const data = {
        name: nameInput ? nameInput.value.trim() : '',
        date: dateInput ? dateInput.value : '',
        time: timeInput && timeInput.value ? timeInput.value : null,
        location: locationInput ? locationInput.value.trim() : null,
        description: descriptionInput ? descriptionInput.value.trim() : null,
        published: publishedInput ? publishedInput.checked : false,
        csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
    };
    
    if (!data.name || !data.date) {
        if (messageEl) {
            messageEl.innerHTML = '<div class="message-error">נא למלא שם האירוע ותאריך</div>';
        }
        return;
    }
    
    try {
        if (messageEl) {
            messageEl.innerHTML = '<div class="message-info">שומר...</div>';
        }
        
        let result;
        if (currentEventId) {
            result = await API.put(`/api/pages/${pageDbId}/events/${currentEventId}`, data);
        } else {
            result = await API.post(`/api/pages/${pageDbId}/events`, data);
        }
        
        if (result && result.ok) {
            if (messageEl) {
                messageEl.innerHTML = '<div class="message-success">✅ האירוע נשמר בהצלחה!</div>';
            }
            setTimeout(() => {
                closeEventModal();
                window.location.reload();
            }, 1000);
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בשמירה';
            if (messageEl) {
                messageEl.innerHTML = `<div class="message-error">❌ ${errorMsg}</div>`;
            }
        }
    } catch (error) {
        console.error('Error saving event:', error);
        const errorMsg = error.message || 'שגיאה בשמירה';
        if (messageEl) {
            messageEl.innerHTML = `<div class="message-error">❌ ${errorMsg}</div>`;
        }
        alert('שגיאה בשמירת אירוע: ' + errorMsg);
    }
}

function addEventToCalendar(event) {
    const eventDate = event.date || '';
    const eventTime = event.time || '';
    const eventName = event.name || '';
    const eventLocation = event.location || '';
    const eventDescription = event.description || '';
    
    if (!eventDate) {
        alert('לא ניתן להוסיף אירוע ללא תאריך ליומן');
        return;
    }
    
    // Format date and time for calendar
    // Google Calendar expects format: YYYYMMDDTHHMMSS
    let startDateTime = eventDate.replace(/-/g, '');
    let endDateTime = eventDate.replace(/-/g, '');
    
    if (eventTime) {
        // Parse time (format: HH:MM:SS or HH:MM)
        const timeParts = eventTime.split(':');
        const hours = timeParts[0].padStart(2, '0');
        const minutes = (timeParts[1] || '00').padStart(2, '0');
        const seconds = (timeParts[2] || '00').padStart(2, '0');
        
        startDateTime += 'T' + hours + minutes + seconds;
        
        // End time is 1 hour after start (or end of day if no time specified)
        const endHours = (parseInt(hours) + 1).toString().padStart(2, '0');
        endDateTime += 'T' + endHours + minutes + seconds;
    } else {
        // If no time, set to all day (format: YYYYMMDD)
        // For all-day events, we don't add T
        startDateTime = eventDate.replace(/-/g, '');
        // End date is same day (all-day event)
        endDateTime = eventDate.replace(/-/g, '');
    }
    
    // Google Calendar URL
    const googleUrl = new URL('https://www.google.com/calendar/render');
    googleUrl.searchParams.set('action', 'TEMPLATE');
    googleUrl.searchParams.set('text', eventName);
    googleUrl.searchParams.set('dates', startDateTime + '/' + endDateTime);
    if (eventLocation) {
        googleUrl.searchParams.set('location', eventLocation);
    }
    if (eventDescription) {
        googleUrl.searchParams.set('details', eventDescription);
    }
    
    // Open calendar URL
    window.open(googleUrl.toString(), '_blank');
}

// Event menu functions
function toggleEventMenu(event, eventId) {
    event.stopPropagation();
    
    // Close all other menus
    document.querySelectorAll('.announcement-menu-popup').forEach(menu => {
        if (menu.id !== `event-menu-${eventId}`) {
            menu.classList.remove('active');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`event-menu-${eventId}`);
    if (menu) {
        const isActive = menu.classList.contains('active');
        if (isActive) {
            closeEventMenu(eventId);
        } else {
            menu.classList.add('active');
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenuOnClick(e) {
                    if (!menu.contains(e.target) && !e.target.closest('.btn-announcement-menu')) {
                        closeEventMenu(eventId);
                        document.removeEventListener('click', closeMenuOnClick);
                    }
                }, { once: true });
            }, 10);
        }
    }
}

function closeEventMenu(eventId) {
    const menu = document.getElementById(`event-menu-${eventId}`);
    if (menu) {
        menu.classList.remove('active');
    }
}

async function deleteEvent(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק אירוע זה?')) {
        return;
    }
    
    try {
        const result = await API.delete(`/api/pages/${pageDbId}/events/${id}`);
        if (result && result.ok) {
            window.location.reload();
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה במחיקה';
            alert('שגיאה במחיקה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        alert('שגיאה במחיקה: ' + error.message);
    }
}

async function openEventView(eventId) {
    try {
        const result = await API.get(`/api/pages/${pageDbId}/events/${eventId}`);
        if (!result || !result.ok || !result.event) {
            alert('שגיאה בטעינת אירוע: ' + (result?.message_he || 'אירוע לא נמצא'));
            return;
        }
        
        const event = result.event;
        
        // Get or create modal
        let modal = document.getElementById('announcementFullViewModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'announcementFullViewModal';
            modal.className = 'announcement-modal-fullscreen';
            modal.innerHTML = `
                <div class="announcement-modal-container" onclick="event.stopPropagation()">
                    <div class="announcement-modal-header">
                        <h2 id="announcementFullViewModalTitle"></h2>
                        <button class="modal-close-btn" onclick="closeAnnouncementFullViewModal()" title="סגור">
                            <img src="/assets/files/cross.svg" alt="סגור">
                        </button>
                    </div>
                    <div class="announcement-modal-body" id="announcementFullViewModalBody">
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        const titleEl = document.getElementById('announcementFullViewModalTitle');
        const bodyEl = document.getElementById('announcementFullViewModalBody');
        
        if (titleEl) {
            titleEl.textContent = event.name || 'אירוע';
        }
        
        if (bodyEl) {
            let html = '';
            
            // Date and time
            if (event.date) {
                const date = new Date(event.date + (event.time ? 'T' + event.time : ''));
                const formattedDate = date.toLocaleDateString('he-IL', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const formattedTime = event.time ? date.toLocaleTimeString('he-IL', { hour: '2-digit', minute: '2-digit' }) : '';
                html += `<div style="margin-bottom: 1rem; color: #666; font-size: 0.95rem;">
                    <strong>תאריך:</strong> ${formattedDate}${formattedTime ? ' בשעה ' + formattedTime : ''}
                </div>`;
            }
            
            // Location
            if (event.location) {
                html += `<div style="margin-bottom: 1rem; color: #666; font-size: 0.95rem;">
                    <strong>מיקום:</strong> ${escapeHtml(event.location)}
                </div>`;
            }
            
            // Description
            if (event.description) {
                html += `<div style="margin-top: 1rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; line-height: 1.6; color: #333; font-family: 'Polin', sans-serif; font-size: 1rem; text-align: right; direction: rtl;">
                    ${escapeHtml(event.description)}
                </div>`;
            }
            
            // Add to calendar button
            if (event.published) {
                html += `<div style="margin-top: 1.5rem; text-align: center;">
                    <button class="btn-add-to-calendar" onclick="addEventToCalendar(${JSON.stringify(event).replace(/"/g, '&quot;')})" style="padding: 0.75rem 1.5rem; border-radius: 8px; background: var(--color-blue); color: white; border: none; cursor: pointer; font-weight: 600;">
                        <img src="/assets/files/calendar-pen.svg" alt="הוסף ליומן" style="width: 18px; height: 18px; margin-left: 0.5rem; vertical-align: middle;">
                        הוסף ליומן
                    </button>
                </div>`;
            }
            
            bodyEl.innerHTML = html;
        }
        
        openModalWithAnimation('announcementFullViewModal');
        
    } catch (error) {
        console.error('Error loading event:', error);
        alert('שגיאה בטעינת אירוע: ' + error.message);
    }
}

// Homework functions
let homeworkQuill = null;
let currentHomeworkId = null;

function openAddHomeworkModal() {
    currentHomeworkId = null;
    const modal = document.getElementById('homeworkModal');
    if (!modal) {
        console.error('Homework modal not found');
        return;
    }
    
    // Reset form
    const titleInput = document.getElementById('homeworkTitle');
    const dateInput = document.getElementById('homeworkDate');
    const messageEl = document.getElementById('homeworkModalMessage');
    const documentUpload = document.getElementById('homeworkDocumentUpload');
    const documentPreview = document.getElementById('homeworkDocumentPreview');
    const statusEl = document.getElementById('homeworkDocumentProcessingStatus');
    
    if (titleInput) titleInput.value = '';
    // Set default date to tomorrow
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const year = tomorrow.getFullYear();
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const day = String(tomorrow.getDate()).padStart(2, '0');
        dateInput.value = `${year}-${month}-${day}`;
    }
    if (messageEl) messageEl.innerHTML = '';
    if (documentUpload) documentUpload.value = '';
    if (documentPreview) documentPreview.innerHTML = '';
    if (statusEl) statusEl.style.display = 'none';
    
    // Update modal title
    const titleEl = document.getElementById('homeworkModalTitle');
    if (titleEl) titleEl.textContent = 'הוסף שיעורי בית חדש';
    
    // Show modal first
    openModalWithAnimation('homeworkModal');
    
    // Scroll to top
    modal.scrollTop = 0;
    
    // Initialize Quill editor
    setTimeout(() => {
        const editorEl = document.getElementById('homeworkEditor');
        if (editorEl && !homeworkQuill) {
            homeworkQuill = new Quill('#homeworkEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link', 'image'],
                        ['clean']
                    ]
                },
                placeholder: 'הזן את תוכן שיעורי הבית כאן...',
                direction: 'rtl'
            });
            // Set RTL for the editor content
            const editorContainer = document.querySelector('#homeworkEditor .ql-editor');
            if (editorContainer) {
                editorContainer.style.textAlign = 'right';
                editorContainer.style.direction = 'rtl';
            }
        } else if (editorEl && homeworkQuill) {
            homeworkQuill.setContents([]);
        }
        
        // Attach form handler
        const form = document.getElementById('homeworkForm');
        if (form) {
            form.onsubmit = async function(e) {
                e.preventDefault();
                await saveHomework();
            };
        }
        
        const saveBtn = document.querySelector('#homeworkModal .btn-announcement-edit-save');
        if (saveBtn) {
            saveBtn.onclick = function() {
                const form = document.getElementById('homeworkForm');
                if (form) {
                    form.requestSubmit();
                }
            };
        }
    }, 150);
}

function closeHomeworkModal() {
    closeModalWithAnimation('homeworkModal');
    currentHomeworkId = null;
    
    // Clear form data
    const form = document.getElementById('homeworkForm');
    if (form) {
        form.reset();
    }
    
    // Clear Quill editor
    if (homeworkQuill) {
        homeworkQuill.setContents([]);
    }
}

async function saveHomework() {
    if (!homeworkQuill) {
        alert('עורך שיעורי הבית לא זמין');
        return;
    }
    
    // Get HTML content and check if it's empty
    let html = homeworkQuill.root.innerHTML.trim();
    if (html === '<p><br></p>' || html === '<p></p>' || html === '' || !html) {
        html = '';
    }
    
    const titleInput = document.getElementById('homeworkTitle');
    const dateInput = document.getElementById('homeworkDate');
    const messageEl = document.getElementById('homeworkModalMessage');
    
    // Get date value
    let dateValue = dateInput ? dateInput.value : '';
    
    // Log for debugging
    console.log('Saving homework - HTML length:', html.length);
    console.log('Saving homework - Date:', dateValue);
    
    const data = {
        html: html,
        title: titleInput ? titleInput.value.trim() : null,
        date: dateValue || null,
        csrf_token: typeof csrfToken !== 'undefined' ? csrfToken : ''
    };
    
    if (!data.date) {
        if (messageEl) {
            messageEl.innerHTML = '<div class="message-error">נא למלא תאריך</div>';
        }
        return;
    }
    
    try {
        if (messageEl) {
            messageEl.innerHTML = '<div class="message-info">שומר...</div>';
        }
        
        let result;
        if (currentHomeworkId) {
            result = await API.put(`/api/pages/${pageDbId}/homework/${currentHomeworkId}`, data);
        } else {
            result = await API.post(`/api/pages/${pageDbId}/homework`, data);
        }
        
        if (result && result.ok) {
            if (messageEl) {
                messageEl.innerHTML = '<div class="message-success">✅ שיעורי הבית נשמרו בהצלחה!</div>';
            }
            setTimeout(() => {
                closeHomeworkModal();
                window.location.reload();
            }, 1000);
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה בשמירה';
            if (messageEl) {
                messageEl.innerHTML = `<div class="message-error">❌ ${errorMsg}</div>`;
            }
            alert('שגיאה בשמירה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error saving homework:', error);
        const errorMsg = error.message || 'שגיאה בשמירה';
        if (messageEl) {
            messageEl.innerHTML = `<div class="message-error">❌ ${errorMsg}</div>`;
        }
        alert('שגיאה בשמירת שיעורי בית: ' + errorMsg);
    }
}

async function editHomework(id) {
    currentHomeworkId = id;
    const modal = document.getElementById('homeworkModal');
    if (!modal) {
        console.error('Homework modal not found');
        return;
    }
    
    try {
        const result = await API.get(`/api/pages/${pageDbId}/homework/${id}`);
        if (!result || !result.ok || !result.homework) {
            alert('שגיאה בטעינת שיעורי בית: ' + (result?.message_he || 'שיעורי בית לא נמצא'));
            return;
        }
        
        const homework = result.homework;
        
        // Reset form
        const titleInput = document.getElementById('homeworkTitle');
        const dateInput = document.getElementById('homeworkDate');
        const messageEl = document.getElementById('homeworkModalMessage');
        const documentUpload = document.getElementById('homeworkDocumentUpload');
        const documentPreview = document.getElementById('homeworkDocumentPreview');
        const statusEl = document.getElementById('homeworkDocumentProcessingStatus');
        
        if (titleInput) titleInput.value = homework.title || '';
        
        // Parse date from homework.date (remove time if exists)
        if (dateInput && homework.date) {
            const dateTime = homework.date.split(' ');
            if (dateTime.length >= 1) {
                dateInput.value = dateTime[0];
            }
        } else if (dateInput) {
            dateInput.value = '';
        }
        if (messageEl) messageEl.innerHTML = '';
        if (documentUpload) documentUpload.value = '';
        if (documentPreview) documentPreview.innerHTML = '';
        if (statusEl) statusEl.style.display = 'none';
        
        // Update modal title
        const titleEl = document.getElementById('homeworkModalTitle');
        if (titleEl) titleEl.textContent = 'ערוך שיעורי בית';
        
        // Show modal
        openModalWithAnimation('homeworkModal');
        
        // Scroll to top
        modal.scrollTop = 0;
        
        // Initialize Quill editor and load content
        setTimeout(() => {
            const editorEl = document.getElementById('homeworkEditor');
            if (editorEl && !homeworkQuill) {
                homeworkQuill = new Quill('#homeworkEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'הזן את תוכן שיעורי הבית כאן...'
                });
            }
            
            if (homeworkQuill && homework.html) {
                homeworkQuill.root.innerHTML = homework.html;
            }
            
            // Attach form handler
            const form = document.getElementById('homeworkForm');
            if (form) {
                form.onsubmit = async function(e) {
                    e.preventDefault();
                    await saveHomework();
                };
            }
            
            const saveBtn = document.querySelector('#homeworkModal .btn-announcement-edit-save');
            if (saveBtn) {
                saveBtn.onclick = function() {
                    const form = document.getElementById('homeworkForm');
                    if (form) {
                        form.requestSubmit();
                    }
                };
            }
        }, 150);
    } catch (error) {
        console.error('Error loading homework:', error);
        alert('שגיאה בטעינת שיעורי בית: ' + error.message);
    }
}

async function deleteHomework(id) {
    if (!confirm('האם אתה בטוח שברצונך למחוק שיעורי בית אלה?')) {
        return;
    }
    
    try {
        const result = await API.delete(`/api/pages/${pageDbId}/homework/${id}`);
        if (result && result.ok) {
            window.location.reload();
        } else {
            const errorMsg = result?.message_he || result?.error || 'שגיאה במחיקה';
            alert('שגיאה במחיקה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Error deleting homework:', error);
        alert('שגיאה במחיקה: ' + error.message);
    }
}

function openHomeworkView(homeworkId) {
    // Find homework in the homework array
    const hw = homework.find(h => h.id === homeworkId);
    if (!hw) {
        console.error('Homework not found:', homeworkId);
        return;
    }
    
    // Use the announcement full view modal for homework
    const modal = document.getElementById('announcementFullViewModal');
    if (!modal) {
        console.error('Announcement full view modal not found');
        return;
    }
    
    const titleEl = document.getElementById('announcementFullViewModalTitle');
    const bodyEl = document.getElementById('announcementFullViewModalBody');
    
    if (titleEl) {
        titleEl.textContent = hw.title || 'שיעורי בית';
    }
    
    if (bodyEl) {
        const html = hw.html || '';
        if (html && html.trim() && html !== '<p><br></p>' && html !== '<p></p>') {
            bodyEl.innerHTML = `<div class="announcement-content-display">${html}</div>`;
        } else {
            bodyEl.innerHTML = '<div class="announcement-no-content">אין תוכן נוסף</div>';
        }
    }
    
    // Show modal
    openModalWithAnimation('announcementFullViewModal');
}

function toggleHomeworkMenu(event, homeworkId) {
    event.stopPropagation();
    
    // Close all other menus
    document.querySelectorAll('.homework-menu-popup').forEach(menu => {
        if (menu.id !== `homework-menu-${homeworkId}`) {
            menu.classList.remove('active');
        }
    });
    
    // Toggle current menu
    const menu = document.getElementById(`homework-menu-${homeworkId}`);
    if (menu) {
        menu.classList.toggle('active');
    }
}

function closeHomeworkMenu(homeworkId) {
    const menu = document.getElementById(`homework-menu-${homeworkId}`);
    if (menu) {
        menu.classList.remove('active');
    }
}

async function handleHomeworkDocumentFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    const preview = document.getElementById('homeworkDocumentPreview');
    const statusEl = document.getElementById('homeworkDocumentProcessingStatus');
    const messageEl = document.getElementById('homeworkModalMessage');
    
    // Show preview
    if (preview) {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; border-radius: 8px; margin-bottom: 1rem;">`;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = `<p style="color: #666;">קובץ: ${file.name}</p>`;
        }
    }
    
    // Process with AI
    if (statusEl) {
        statusEl.style.display = 'block';
    }
    
    try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', typeof csrfToken !== 'undefined' ? csrfToken : '');
        
        const result = await API.postForm('/api/ai/extract-document', formData);
        
        if (result && result.ok && result.summary) {
            const summaryHtml = result.summary;
            
            if (homeworkQuill) {
                const currentHtml = homeworkQuill.root.innerHTML;
                let newHtml = currentHtml;
                if (currentHtml && !currentHtml.endsWith('<p><br></p>')) {
                    newHtml += '<p><br></p>';
                }
                newHtml += summaryHtml;
                
                const delta = homeworkQuill.clipboard.convert(newHtml);
                homeworkQuill.setContents(delta, 'user');
                
                const newLength = homeworkQuill.getLength();
                homeworkQuill.setSelection(newLength, 'user');
                
                setTimeout(() => {
                    homeworkQuill.root.scrollTop = homeworkQuill.root.scrollHeight;
                    homeworkQuill.focus();
                }, 100);
            } else {
                console.error('Quill editor not found!');
                alert('עורך שיעורי הבית לא זמין. נא לנסות שוב.');
            }
            
            if (preview) {
                preview.innerHTML = '';
            }
        } else {
            const errorMsg = result?.message_he || result?.reason || 'לא ניתן לעבד את המסמך';
            if (messageEl) {
                messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
            }
            alert('❌ שגיאה: ' + errorMsg);
        }
    } catch (error) {
        console.error('Document processing error:', error);
        const errorMsg = 'שגיאה בעיבוד המסמך: ' + (error.message || 'שגיאה לא ידועה');
        if (messageEl) {
            messageEl.innerHTML = `<div style="padding: 1rem; background: #f44336; color: white; border-radius: 8px;">❌ ${errorMsg}</div>`;
        }
        alert('❌ ' + errorMsg);
    } finally {
        if (statusEl) {
            statusEl.style.display = 'none';
        }
    }
}

// Birthday Modal Functions
function showAllBirthdays() {
    openModalWithAnimation('birthdayModal');
}

function closeBirthdayModal() {
    closeModalWithAnimation('birthdayModal');
}

// Announcement View Modal (Bottom Sheet)
async function openAnnouncementViewModal(id) {
    try {
        const result = await API.get(`/api/pages/${pageDbId}/announcements/${id}`);
        if (result.ok && result.announcement) {
            const announcement = result.announcement;
            const modal = document.getElementById('announcementFullViewModal');
            const titleEl = document.getElementById('announcementFullViewModalTitle');
            const contentEl = document.getElementById('announcementFullViewModalBody');
            
            if (modal && titleEl && contentEl) {
                // Set title
                if (announcement.title) {
                    titleEl.textContent = announcement.title;
                } else {
                    // Extract first line or first 50 chars as title
                    const htmlText = announcement.html ? announcement.html.replace(/<[^>]*>/g, '').trim() : '';
                    const titlePreview = htmlText.length > 50 ? htmlText.substring(0, 50) + '...' : htmlText;
                    titleEl.textContent = titlePreview || 'הודעה';
                }
                
                // Display HTML content with proper styling
                const html = announcement.html || '';
                if (html && html.trim() && html !== '<p><br></p>' && html !== '<p></p>' && html !== '<p></p>') {
                    contentEl.innerHTML = `<div class="announcement-content-display">${html}</div>`;
                } else {
                    contentEl.innerHTML = '<div class="announcement-no-content">אין תוכן נוסף</div>';
                }
                
                // Use openModalWithAnimation to show the modal
                openModalWithAnimation('announcementFullViewModal');
            } else {
                console.error('Announcement view modal elements not found', { modal: !!modal, titleEl: !!titleEl, contentEl: !!contentEl });
            }
        } else {
            console.error('Failed to load announcement:', result);
            alert('שגיאה בטעינת ההודעה: ' + (result?.message_he || result?.error || 'לא נמצא'));
        }
    } catch (error) {
        console.error('Failed to load announcement:', error);
        alert('שגיאה בטעינת ההודעה: ' + error.message);
    }
}

function closeAnnouncementFullViewModal() {
    const modal = document.getElementById('announcementFullViewModal');
    if (modal) {
        // Use closeModalWithAnimation to close the modal
        closeModalWithAnimation('announcementFullViewModal');
    }
}

function toggleAnnouncementDateType() {
    const dateTypeDay = document.getElementById('announcementDateTypeDay');
    const dateTypeDate = document.getElementById('announcementDateTypeDate');
    const dateTypeNone = document.getElementById('announcementDateTypeNone');
    const daySelector = document.getElementById('announcementDaySelector');
    const dateSelector = document.getElementById('announcementDateSelector');
    
    if (daySelector) daySelector.classList.add('announcement-selector-hidden');
    if (dateSelector) dateSelector.classList.add('announcement-selector-hidden');
    
    if (dateTypeDay && dateTypeDay.checked && daySelector) {
        daySelector.classList.remove('announcement-selector-hidden');
    } else if (dateTypeDate && dateTypeDate.checked && dateSelector) {
        dateSelector.classList.remove('announcement-selector-hidden');
    }
}

function toggleAnnouncementDateType() {
    const dateTypeDay = document.getElementById('announcementDateTypeDay');
    const dateTypeDate = document.getElementById('announcementDateTypeDate');
    const dateTypeNone = document.getElementById('announcementDateTypeNone');
    const daySelector = document.getElementById('announcementDaySelector');
    const dateSelector = document.getElementById('announcementDateSelector');
    
    if (daySelector) daySelector.classList.add('announcement-selector-hidden');
    if (dateSelector) dateSelector.classList.add('announcement-selector-hidden');
    
    if (dateTypeDay && dateTypeDay.checked && daySelector) {
        daySelector.classList.remove('announcement-selector-hidden');
    } else if (dateTypeDate && dateTypeDate.checked && dateSelector) {
        dateSelector.classList.remove('announcement-selector-hidden');
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeBirthdayModal();
    }
});

// Schedule day switching
let scheduleData = null;
let currentScheduleDay = null;

function initScheduleData() {
    // Use scheduleDataForJS if available (from PHP)
    if (typeof scheduleDataForJS !== 'undefined' && scheduleDataForJS) {
        scheduleData = scheduleDataForJS;
        
        // Use defaultScheduleDay if available
        if (typeof defaultScheduleDay !== 'undefined' && defaultScheduleDay) {
            const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            currentScheduleDay = dayNames.indexOf(defaultScheduleDay);
        }
    } else {
        // Fallback: Find schedule block from blocks array
        if (typeof blocks !== 'undefined' && blocks) {
            const scheduleBlock = blocks.find(b => b.type === 'schedule');
            if (scheduleBlock && scheduleBlock.data && scheduleBlock.data.schedule) {
                scheduleData = scheduleBlock.data.schedule;
                
                // Determine default day based on time (16:00 rule)
                const currentHour = new Date().getHours();
                const currentDay = new Date().getDay(); // 0 = Sunday, 6 = Saturday
                
                if (currentHour >= 16) {
                    // Show tomorrow's schedule
                    currentScheduleDay = (currentDay + 1) % 7;
                } else {
                    // Show today's schedule
                    currentScheduleDay = currentDay;
                }
                
                // Convert to day key
                const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                const dayKey = dayNames[currentScheduleDay];
                
                // Check if this day has lessons, if not find first day with lessons
                if (!scheduleData[dayKey] || scheduleData[dayKey].length === 0) {
                    for (let i = 0; i < 7; i++) {
                        const checkDay = (currentScheduleDay + i) % 7;
                        const checkDayKey = dayNames[checkDay];
                        if (scheduleData[checkDayKey] && scheduleData[checkDayKey].length > 0) {
                            currentScheduleDay = checkDay;
                            break;
                        }
                    }
                }
            }
        }
    }
}

function switchScheduleDay(dayKey) {
    if (!scheduleData || !scheduleData[dayKey] || scheduleData[dayKey].length === 0) {
        return;
    }
    
    // Update active button
    document.querySelectorAll('.schedule-day-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.day === dayKey) {
            btn.classList.add('active');
        }
    });
    
    // Update schedule list
    const scheduleList = document.getElementById('schedule-list');
    const greetingEl = document.getElementById('schedule-greeting');
    
    if (!scheduleList) return;
    
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const dayNamesHe = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שבת'];
    const dayIndex = dayNames.indexOf(dayKey);
    const dayName = dayNamesHe[dayIndex];
    
    const lessons = scheduleData[dayKey];
    const lessonNumbers = ['ראשון', 'שני', 'שלישי', 'רביעי', 'חמישי', 'שישי', 'שביעי', 'שמיני', 'תשיעי', 'עשירי'];
    
    let html = '';
    lessons.forEach((lesson, index) => {
        const lessonNum = lessonNumbers[index] || 'שיעור ' + (index + 1);
        const time = lesson.time || ('שיעור ' + lessonNum);
        html += `
            <div class="today-schedule-item">
                <span class="lesson-subject normal-text">
                    ${escapeHtml(lesson.subject || '')}
                    ${lesson.teacher ? `<span class="lesson-teacher">${escapeHtml(lesson.teacher)}</span>` : ''}
                </span>

            <span class="lesson-time small-text">${escapeHtml(time)}</span>
                ${lesson.room ? `<span class="lesson-room">חדר ${escapeHtml(lesson.room)}</span>` : ''}
            </div>
        `;
    });
    
    scheduleList.innerHTML = html;
    
    // Update greeting - only show time greeting for active day (defaultScheduleDay)
    if (greetingEl) {
        const isActiveDay = (typeof defaultScheduleDay !== 'undefined' && defaultScheduleDay === dayKey);
        
        // Determine greeting text (today or tomorrow) based on active day
        const currentHour = new Date().getHours();
        let greetingText = '';
        if (isActiveDay) {
            // For active day, determine if it's today or tomorrow based on 16:00 rule
            if (currentHour >= 16) {
                greetingText = 'מחר';
            } else {
                greetingText = 'היום';
            }
        } else {
            // For non-active days, just show the day name without "היום" or "מחר"
            greetingText = '';
        }
        
        let greetingHtml = 'יום ' + dayName;
        if (greetingText) {
            greetingHtml += ' ' + greetingText + '!';
        }
        
        // Add time-based greeting only for active day
        if (isActiveDay) {
            let timeGreeting = '';
            if (currentHour >= 5 && currentHour < 12) {
                timeGreeting = 'בוקר טוב';
            } else if (currentHour >= 12 && currentHour < 17) {
                timeGreeting = 'צהריים טובים';
            } else if (currentHour >= 17 && currentHour < 22) {
                timeGreeting = 'ערב טוב';
            } else {
                timeGreeting = 'לילה טוב';
            }
            greetingHtml += ' <span class="Boldsubheading">' + escapeHtml(timeGreeting) + '</span>';
        }
        
        greetingEl.innerHTML = greetingHtml;
    }
    
    currentScheduleDay = dayIndex;
}

// Announcement checkmark with confetti
function toggleAnnouncementCheck(announcementId, event) {
    if (event) {
        event.stopPropagation();
    }
    
    const announcementItem = document.querySelector(`.announcement-item[data-id="${announcementId}"]`);
    if (!announcementItem) return;
    
    const checkmark = announcementItem.querySelector('.announcement-checkmark');
    if (!checkmark) return;
    
    const isChecked = checkmark.classList.contains('checked');
    
    if (!isChecked) {
        // Mark as checked
        checkmark.classList.add('checked');
        announcementItem.classList.add('announcement-checked');
        
        // Trigger confetti
        triggerConfetti();
        
        // Save to localStorage
        const checkedAnnouncements = JSON.parse(localStorage.getItem('checkedAnnouncements') || '[]');
        if (!checkedAnnouncements.includes(announcementId)) {
            checkedAnnouncements.push(announcementId);
            localStorage.setItem('checkedAnnouncements', JSON.stringify(checkedAnnouncements));
        }
    } else {
        // Uncheck
        checkmark.classList.remove('checked');
        announcementItem.classList.remove('announcement-checked');
        
        // Remove from localStorage
        const checkedAnnouncements = JSON.parse(localStorage.getItem('checkedAnnouncements') || '[]');
        const index = checkedAnnouncements.indexOf(announcementId);
        if (index > -1) {
            checkedAnnouncements.splice(index, 1);
            localStorage.setItem('checkedAnnouncements', JSON.stringify(checkedAnnouncements));
        }
    }
}

// Confetti function
function triggerConfetti() {
    if (typeof confetti !== 'undefined') {
        confetti({
            particleCount: 150,
            spread: 60
        });
    }
}

// Restore checked announcements on page load
function restoreCheckedAnnouncements() {
    const checkedAnnouncements = JSON.parse(localStorage.getItem('checkedAnnouncements') || '[]');
    checkedAnnouncements.forEach(id => {
        const announcementItem = document.querySelector(`.announcement-item[data-id="${id}"]`);
        if (announcementItem) {
            const checkmark = announcementItem.querySelector('.announcement-checkmark');
            if (checkmark) {
                checkmark.classList.add('checked');
                announcementItem.classList.add('announcement-checked');
            }
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initScheduleData();
    restoreCheckedAnnouncements();
});



