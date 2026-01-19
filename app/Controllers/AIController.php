<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AIService;
use App\Services\FileUploader;
use App\Repositories\UserRepository;
use App\Services\Logger;
use Throwable;

class AIController extends BaseController
{
    /**
     * Get the current page ID for the authenticated user
     * Returns the page ID if user has access to a single page, or from request parameter
     */
    private function getCurrentPageId(): ?int
    {
        $user = $this->container->get('user');
        if (!$user || !$this->db) {
            return null;
        }

        // System admin - try to get pageId from request
        if ($user['role'] === 'system_admin') {
            $pageId = $this->request->input('page_id') ?? $this->request->input('pageId');
            if ($pageId) {
                return (int)$pageId;
            }
            return null;
        }

        // Page admin - get their page(s)
        if ($user['role'] === 'page_admin') {
            // Try to get pageId from request first
            $pageId = $this->request->input('page_id') ?? $this->request->input('pageId');
            if ($pageId) {
                $userRepo = new UserRepository($this->db);
                if ($userRepo->isPageAdmin($user['id'], (int)$pageId)) {
                    return (int)$pageId;
                }
            }

            // If no pageId in request, get user's pages
            $pages = $this->db->fetchAll(
                "SELECT p.id FROM {$this->db->table('pages')} p
                 INNER JOIN {$this->db->table('page_admins')} pa ON p.id = pa.page_id
                 WHERE pa.user_id = ?
                 ORDER BY p.created_at DESC
                 LIMIT 1",
                [$user['id']]
            );

            if (!empty($pages)) {
                return (int)$pages[0]['id'];
            }
        }

        return null;
    }

    public function extractSchedule(): void
    {
        // Increase execution time for AI processing
        set_time_limit(180); // 3 minutes
        
        if (!$this->validateCsrf()) {
            return;
        }

        $file = $this->request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->response->json([
                'ok' => false,
                'error_code' => 'UPLOAD_ERROR',
                'message_he' => 'שגיאה בהעלאת הקובץ'
            ], 400);
            return;
        }

        try {
            $aiService = new AIService();
            if (!$aiService->isConfigured()) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'AI_NOT_CONFIGURED',
                    'message_he' => 'מפתח AI API לא מוגדר. נא להגדיר במערכת הניהול.'
                ], 400);
                return;
            }
            
            $pageId = $this->getCurrentPageId();
            $uploader = new FileUploader();
            $imagePath = $uploader->upload($file, 'schedule', $pageId);

            $result = $aiService->extractSchedule($imagePath);

            if (!$result['ok']) {
                if (file_exists($imagePath)) unlink($imagePath);
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'EXTRACTION_FAILED',
                    'message_he' => $result['reason'] ?? 'לא ניתן לחלץ את המערכת. אנא נסה עם תמונה אחרת.'
                ], 400);
                return;
            }

            // Store image path in result
            $result['image_path'] = str_replace(PUBLIC_PATH . '/', '', $imagePath);

            $this->response->json($result);
        } catch (Throwable $e) {
            Logger::error('AI extraction error', ['msg' => $e->getMessage()]);
            $this->response->json([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה בעיבוד התמונה'
            ], 500);
        }
    }

    public function extractContacts(): void
    {
        // Increase execution time for AI processing
        set_time_limit(180); // 3 minutes
        
        if (!$this->validateCsrf()) {
            return;
        }

        $data = $this->request->json();
        $text = $data['text'] ?? '';
        $file = $this->request->file('image');

        if (empty($text) && !$file) {
            $this->response->json([
                'ok' => false,
                'error_code' => 'INVALID_INPUT',
                'message_he' => 'נא להעלות תמונה או להזין טקסט'
            ], 400);
            return;
        }

        try {
            $aiService = new AIService();
            if (!$aiService->isConfigured()) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'AI_NOT_CONFIGURED',
                    'message_he' => 'מפתח AI API לא מוגדר. נא להגדיר במערכת הניהול.'
                ], 400);
                return;
            }
            
            $imagePath = null;

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $pageId = $this->getCurrentPageId();
                $uploader = new FileUploader();
                $imagePath = $uploader->upload($file, 'contacts', $pageId);
                $result = $aiService->extractContacts($imagePath);
            } else {
                $result = $aiService->extractContacts(null, $text);
            }

            if (!$result['ok']) {
                if ($imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'EXTRACTION_FAILED',
                    'message_he' => $result['reason'] ?? 'לא ניתן לחלץ את אנשי הקשר. אנא נסה שוב.'
                ], 400);
                return;
            }

            if ($imagePath) {
                $result['image_path'] = str_replace(PUBLIC_PATH . '/', '', $imagePath);
            }

            $this->response->json($result);
        } catch (Throwable $e) {
            Logger::error('AI contacts extraction error', ['msg' => $e->getMessage()]);
            $this->response->json([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה בעיבוד הנתונים'
            ], 500);
        }
    }

    public function extractDocument(): void
    {
        // Increase execution time for AI processing
        set_time_limit(180); // 3 minutes
        
        if (!$this->validateCsrf()) {
            return;
        }

        $file = $this->request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->response->json([
                'ok' => false,
                'error_code' => 'UPLOAD_ERROR',
                'message_he' => 'שגיאה בהעלאת הקובץ'
            ], 400);
            return;
        }

        try {
            $aiService = new AIService();
            if (!$aiService->isConfigured()) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'AI_NOT_CONFIGURED',
                    'message_he' => 'מפתח AI API לא מוגדר. נא להגדיר במערכת הניהול.'
                ], 400);
                return;
            }
            
            $pageId = $this->getCurrentPageId();
            $uploader = new FileUploader();
            $imagePath = $uploader->upload($file, 'document', $pageId);

            $result = $aiService->extractDocument($imagePath);

            if (!$result['ok']) {
                if (file_exists($imagePath)) unlink($imagePath);
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'EXTRACTION_FAILED',
                    'message_he' => $result['reason'] ?? 'לא ניתן לעבד את המסמך. אנא נסה עם תמונה אחרת.'
                ], 400);
                return;
            }

            // Store image path in result (relative to public directory)
            $result['image_path'] = str_replace(PUBLIC_PATH . '/', '', $imagePath);

            $this->response->json($result);
        } catch (Throwable $e) {
            Logger::error('AI document extraction error', ['msg' => $e->getMessage()]);
            $this->response->json([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה בעיבוד המסמך'
            ], 500);
        }
    }

    public function analyzeQuickAdd(): void
    {
        // Increase execution time for AI processing
        set_time_limit(180); // 3 minutes
        
        if (!$this->validateCsrf()) {
            return;
        }

        // Handle multipart form data (for file uploads)
        $text = '';
        $file = $this->request->file('image');
        
        // Try to get text from POST data first (multipart form)
        if (isset($_POST['text'])) {
            $text = $_POST['text'];
        } else {
            // Try JSON if available
            $data = $this->request->json();
            if ($data && isset($data['text'])) {
                $text = $data['text'];
            }
        }

        if (empty($text) && (!$file || $file['error'] !== UPLOAD_ERR_OK)) {
            $this->response->json([
                'ok' => false,
                'error_code' => 'INVALID_INPUT',
                'message_he' => 'נא להזין טקסט'
            ], 400);
            return;
        }

        try {
            // Log for debugging
            Logger::info("analyzeQuickAdd: Request received", [
                'text_len' => strlen($text),
                'has_file' => ($file ? 'yes' : 'no'),
                'file_error' => ($file ? $file['error'] : 'n/a')
            ]);
            
            $aiService = new AIService();
            if (!$aiService->isConfigured()) {
                Logger::error("analyzeQuickAdd: AI not configured");
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'AI_NOT_CONFIGURED',
                    'message_he' => 'מפתח AI API לא מוגדר. נא להגדיר במערכת הניהול.'
                ], 400);
                return;
            }
            
            $imagePath = null;
            $imageUrl = null;

            // Save image if provided
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $pageId = $this->getCurrentPageId();
                $uploader = new FileUploader();
                $imagePath = $uploader->upload($file, 'quick-add', $pageId);
                $imageUrl = str_replace(PUBLIC_PATH . '/', '', $imagePath);
                Logger::info("analyzeQuickAdd: image uploaded", ['path' => $imagePath]);
            }

            // Analyze content
            if ($imagePath) {
                $result = $aiService->analyzeQuickAdd($imagePath, $text);
            } else {
                $result = $aiService->analyzeQuickAdd(null, $text);
            }
            
            if (!$result['ok']) {
                if ($imagePath && file_exists($imagePath)) {
                    unlink($imagePath);
                }
                Logger::error("analyzeQuickAdd failed", [
                    'reason' => $result['reason'] ?? 'unknown',
                    'text_len' => strlen($text),
                    'has_image' => ($imagePath ? 'yes' : 'no')
                ]);
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'ANALYSIS_FAILED',
                    'message_he' => $result['reason'] ?? 'לא ניתן לנתח את התוכן. אנא נסה שוב.'
                ], 400);
                return;
            }

            // Add image URL to result if image was uploaded
            if ($imageUrl) {
                $result['image_path'] = $imageUrl;
            }

            $this->response->json($result);
        } catch (Throwable $e) {
            Logger::error('AI quick add analysis error', ['msg' => $e->getMessage()]);
            $this->response->json([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה בניתוח התוכן'
            ], 500);
        }
    }
}
