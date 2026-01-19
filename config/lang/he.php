<?php
declare(strict_types=1);

return [
    // General
    'app_name' => 'Schoolist',
    'welcome' => 'ברוכים הבאים',
    'loading' => 'טוען...',
    'save' => 'שמור',
    'cancel' => 'ביטול',
    'delete' => 'מחק',
    'edit' => 'ערוך',
    'add' => 'הוסף',
    'close' => 'סגור',
    'back' => 'חזור',
    'next' => 'הבא',
    'previous' => 'קודם',
    'submit' => 'שלח',
    'search' => 'חפש',
    'filter' => 'סנן',
    'share' => 'שיתוף',
    
    // Auth
    'login' => 'התחברות',
    'logout' => 'התנתקות',
    'email' => 'אימייל או טלפון',
    'enter_email' => 'הזן אימייל או מספר טלפון',
    'otp_code' => 'קוד אימות',
    'enter_otp' => 'הזן קוד אימות',
    'request_otp' => 'שלח קוד אימות',
    'verify' => 'אימות',
    'invalid_otp' => 'קוד אימות לא תקין',
    'otp_sent' => 'קוד אימות נשלח אליך',
    
    // Dashboard
    'dashboard' => 'לוח בקרה',
    'my_pages' => 'הדפים שלי',
    'no_pages' => 'אין דפים זמינים',
    
    // Editor
    'editor' => 'עורך',
    'page_settings' => 'הגדרות דף',
    'school_name' => 'שם בית הספר',
    'class_name' => 'שם הכיתה',
    'announcements' => 'הודעות',
    'add_announcement' => 'הוסף הודעה',
    'blocks' => 'בלוקים',
    'add_block' => 'הוסף בלוק',
    'reorder' => 'סדר מחדש',
    'block_schedule' => 'מערכת שעות',
    'block_contacts' => 'אנשי קשר חשובים',
    'block_whatsapp' => 'קבוצות וואטסאפ ועדכונים',
    'block_links' => 'קישורים שימושיים',
    'block_calendar' => 'לוח חופשות, חגים וימים מיוחדים',
    'upload_image' => 'העלה תמונה',
    'paste_text' => 'הדבק טקסט',
    'extract_schedule' => 'חלץ מערכת שעות',
    'extract_contacts' => 'חלץ אנשי קשר',
    'today_schedule' => 'מערכת שעות היום',
    'full_schedule' => 'מערכת שעות מלאה',
    'contact_name' => 'שם',
    'contact_role' => 'תפקיד',
    'contact_phone' => 'טלפון',
    'contact_notes' => 'הערות',
    'add_link' => 'הוסף קישור',
    'link_title' => 'כותרת קישור',
    'link_url' => 'כתובת קישור',
    
    // Admin
    'admin' => 'מנהל מערכת',
    'invitation_codes' => 'קודי הזמנה',
    'create_invitation' => 'צור קוד הזמנה',
    'invitation_code' => 'קוד הזמנה',
    'admin_email' => 'אימייל מנהל',
    'status' => 'סטטוס',
    'active' => 'פעיל',
    'used' => 'משומש',
    'disabled' => 'מושבת',
    'created_at' => 'נוצר ב',
    'used_at' => 'שומש ב',
    'pages' => 'דפים',
    'q_activations' => 'הפעלות /q',
    'q_number' => 'מספר /q',
    'page_id' => 'מזהה דף',
    'last_used' => 'שימוש אחרון',
    
    // Public
    'public_page' => 'דף כיתה',
    'share_page' => 'שיתוף הדף',
    'weather' => 'מזג אוויר',
    'no_announcements' => 'אין הודעות',
    'no_blocks' => 'אין בלוקים',
    
    // Q Activation
    'activate_q' => 'הפעלת קישור קצר',
    'enter_page_id' => 'הזן מזהה דף',
    'activate' => 'הפעל',
    'already_activated' => 'קישור זה כבר מופעל',
    
    // Errors
    'error' => 'שגיאה',
    'not_found' => 'לא נמצא',
    'unauthorized' => 'נדרשת התחברות',
    'forbidden' => 'אין הרשאה',
    'server_error' => 'שגיאת שרת',
    'invalid_input' => 'קלט לא תקין',
    'rate_limit' => 'יותר מדי בקשות. נסה שוב מאוחר יותר',
    
    // Setup
    'setup' => 'התקנה',
    'setup_welcome' => 'ברוכים הבאים להתקנת Schoolist',
    'setup_step' => 'שלב',
    'check_requirements' => 'בדיקת דרישות',
    'database_config' => 'הגדרות מסד נתונים',
    'base_url_config' => 'הגדרת כתובת בסיס',
    'email_config' => 'הגדרות אימייל',
    'finalize' => 'סיום התקנה',
    'db_host' => 'שרת מסד נתונים',
    'db_name' => 'שם מסד נתונים',
    'db_user' => 'שם משתמש',
    'db_pass' => 'סיסמה',
    'db_prefix' => 'קידומת טבלאות',
    'base_url' => 'כתובת בסיס',
    'smtp_enabled' => 'השתמש ב-SMTP',
    'smtp_host' => 'שרת SMTP',
    'smtp_port' => 'פורט SMTP',
    'smtp_user' => 'שם משתמש SMTP',
    'smtp_pass' => 'סיסמת SMTP',
    'smtp_from' => 'כתובת שולח',
    'smtp_from_name' => 'שם שולח',
    'openai_key' => 'מפתח API של OpenAI',
    'admin_email_setup' => 'אימייל מנהל ראשוני (אופציונלי)',
    'install' => 'התקן',
    'installation_complete' => 'ההתקנה הושלמה בהצלחה',
];


















