<?php
/**
 * Quick Add Modal - AI-Powered Content Creation
 * 
 * This modal allows users to quickly add content (announcements, events, homework, contacts)
 * by entering text or uploading an image. The AI analyzes the content and suggests
 * the appropriate content type, then opens the relevant editor with pre-filled data.
 */
?>
<div id="quickAddModal" class="modal quick-add-modal-hidden" onclick="if(event.target === this) closeQuickAddModal()">
    <div class="modal-content modal-content-large">
        <div class="modal-header-unified">
            <h3>הוספה מהירה</h3>
            <button class="modal-close-btn" onclick="closeQuickAddModal()">
                <img src="/assets/files/cross.svg" alt="סגור">
            </button>
        </div>
        
        <div class="modal-body">
            <form id="quickAddForm">
                <div class="form-group">
                    <textarea 
                        id="quickAddText" 
                        class="form-control" 
                        rows="6" 
                        placeholder="הזן טקסט... (לדוגמה: 'יש אירוע ב-15/01 בשעה 10:00' או 'שיעורי בית למחר - לקרוא עמוד 5')"
                    ></textarea>
                </div>
                
                <div class="form-group">
                    <label for="quickAddImage" class="quick-add-file-upload-label">
                        <input 
                            type="file" 
                            id="quickAddImage" 
                            name="image" 
                            accept="image/*" 
                            onchange="handleQuickAddImageSelect(event)"
                        >
                        <span class="btn btn-secondary quick-add-upload-btn">
                            העלה תמונה
                        </span>
                    </label>
                    <div id="quickAddImagePreview" class="quick-add-preview-hidden">
                        <img id="quickAddImagePreviewImg" src="" alt="תצוגה מקדימה">
                        <button type="button" class="quick-add-remove-image-btn" onclick="removeQuickAddImage()">
                            הסר תמונה
                        </button>
                    </div>
                </div>
                
                <div id="quickAddMessage" class="message"></div>
                
                <div class="form-group quick-add-form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeQuickAddModal()">
                        ביטול
                    </button>
                    <button type="button" class="btn btn-primary" onclick="analyzeQuickAddContent()" id="quickAddAnalyzeBtn">
                        עדכן
                    </button>
                </div>
            </form>
            
            <!-- AI Suggestions Section -->
            <div id="quickAddSuggestions" class="quick-add-preview-hidden">
                <h4 class="quick-add-suggestions-title">אנחנו רואים שהזה:</h4>
                <div id="quickAddSuggestionsList">
                    <!-- Suggestions will be populated here by JavaScript -->
                </div>
            </div>
            
            <!-- Preview Section (shown when there's a single high-confidence match) -->
            <div id="quickAddPreview" class="quick-add-preview-hidden">
                <div class="quick-add-preview-content">
                    <div class="quick-add-preview-header">
                        <h4 class="quick-add-preview-type" id="quickAddPreviewType"></h4>
                    </div>
                    <div class="quick-add-preview-body" id="quickAddPreviewBody">
                        <!-- Preview content will be populated here -->
                    </div>
                    <div class="quick-add-preview-date" id="quickAddPreviewDate"></div>
                    <div class="quick-add-preview-actions">
                        <button type="button" class="btn btn-secondary" onclick="cancelQuickAddPreview()">
                            ביטול
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="editQuickAddPreview()">
                            עריכה
                        </button>
                        <button type="button" class="btn btn-primary" onclick="confirmQuickAddPreview()" id="quickAddPreviewConfirmBtn">
                            אישור
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

