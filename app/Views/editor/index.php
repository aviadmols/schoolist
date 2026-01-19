<?php
$title = $i18n->t('editor');
$no_layout = true;
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($i18n->t('editor'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/assets/css/main.css">
    <link rel="stylesheet" href="/public/assets/css/editor.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">
</head>
<body class="editor-page">
    <nav class="editor-nav">
        <h2><?= htmlspecialchars($page['school_name'] . ' - ' . $page['class_title'], ENT_QUOTES, 'UTF-8') ?></h2>
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="/p/<?= $page['unique_numeric_id'] ?>" target="_blank" class="btn btn-primary" style="background: linear-gradient(135deg, #0C4A6E 0%, #075985 100%);">
                👁️ צפה בדף
            </a>
            <?php 
            $user = $container->get('user');
            $backUrl = ($user && $user['role'] === 'system_admin') ? '/admin/pages' : '/dashboard';
            ?>
            <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('back'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/api/auth/logout" class="btn btn-secondary"><?= htmlspecialchars($i18n->t('logout'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </nav>

    <div class="editor-container">
        <div class="editor-tabs">
            <button class="tab-btn active" data-tab="content">תוכן</button>
            <button class="tab-btn" data-tab="settings"><?= htmlspecialchars($i18n->t('page_settings'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <div class="tab-content active" id="content-tab">
            <div class="content-section">
                <div class="section-header">
                    <h3><?= htmlspecialchars($i18n->t('announcements'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <button class="btn btn-primary" onclick="openAnnouncementEditor()"><?= htmlspecialchars($i18n->t('add_announcement'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
                <div id="announcements-list" class="sortable-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-item" data-id="<?= $announcement['id'] ?>">
                            <div class="drag-handle">☰</div>
                            <div class="content"><?= $announcement['html'] ?></div>
                            <button class="btn-icon" onclick="editAnnouncement(<?= $announcement['id'] ?>)"><?= htmlspecialchars($i18n->t('edit'), ENT_QUOTES, 'UTF-8') ?></button>
                            <button class="btn-icon" onclick="deleteAnnouncement(<?= $announcement['id'] ?>)"><?= htmlspecialchars($i18n->t('delete'), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="content-section" style="margin-top: 2rem;">
                <div class="section-header">
                    <h3><?= htmlspecialchars($i18n->t('blocks'), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
                <div id="blocks-list" class="sortable-list">
                    <?php 
                    $requiredBlockTypes = ['calendar', 'whatsapp', 'links', 'contact_page', 'contacts'];
                    foreach ($blocks as $block): 
                        $isRequired = in_array($block['type'], $requiredBlockTypes, true);
                    ?>
                        <div class="block-item" data-id="<?= $block['id'] ?>" data-type="<?= htmlspecialchars($block['type'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="drag-handle">☰</div>
                            <div class="content">
                                <strong><?= htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="type-badge"><?= htmlspecialchars($block['type'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <button class="btn-icon" onclick="editBlock(<?= $block['id'] ?>)"><?= htmlspecialchars($i18n->t('edit'), ENT_QUOTES, 'UTF-8') ?></button>
                            <?php if (!$isRequired): ?>
                                <button class="btn-icon" onclick="deleteBlock(<?= $block['id'] ?>)"><?= htmlspecialchars($i18n->t('delete'), ENT_QUOTES, 'UTF-8') ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="settings-tab">
            <form id="settingsForm" class="settings-form">
                <div class="form-group">
                    <label><?= htmlspecialchars($i18n->t('school_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="school_name" value="<?= htmlspecialchars($page['school_name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label><?= htmlspecialchars($i18n->t('class_name'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="class_title" value="<?= htmlspecialchars($page['class_title'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($i18n->t('save'), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>

    <!-- Modals -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><?= htmlspecialchars($i18n->t('add_announcement'), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label><strong>עיבוד מסמך באמצעות AI</strong></label>
                <div class="upload-area" onclick="document.getElementById('documentUpload').click()" style="margin-bottom: 0.5rem;">
                    <p>לחץ להעלאת מסמך או תמונה או גרור לכאן</p>
                    <input type="file" id="documentUpload" accept="image/*" style="display: none;" onchange="handleDocumentFileSelect(this)">
                </div>
                <div id="documentPreview" style="margin-top: 0.5rem;"></div>
                <div id="documentProcessingStatus" style="display: none; margin-top: 0.5rem; padding: 1rem; background: #E3F2FD; border-radius: 12px 12px 12px 12px; text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <div class="spinner" style="width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #2196F3; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                        <span style="font-weight: bold; color: #2196F3;">מעבד מסמך... זה עשוי לקחת כמה שניות</span>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>כותרת ההודעה (אופציונלי)</label>
                <input type="text" id="announcementTitle" class="form-input" placeholder="הזן כותרת...">
            </div>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>תאריך רלוונטי (אופציונלי)</label>
                <input type="date" id="announcementDate" class="form-input">
            </div>
            <div id="announcementEditor"></div>
            <button class="btn btn-primary" onclick="saveAnnouncement()"><?= htmlspecialchars($i18n->t('save'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>

    <div id="blockModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="blockModalBody"></div>
        </div>
    </div>

    <script>
        const BASE_URL = '<?= BASE_URL ?? '/' ?>';
        const pageId = <?= $page['id'] ?>;
        const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    </script>
    <script src="/public/assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="/public/assets/js/editor.js"></script>
</body>
</html>

