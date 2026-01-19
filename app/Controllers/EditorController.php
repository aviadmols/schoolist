<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PageRepository;
use App\Repositories\BlockRepository;
use App\Repositories\AnnouncementRepository;
use App\Repositories\EventRepository;
use App\Repositories\HomeworkRepository;
use App\Repositories\UserRepository;
use App\Services\Logger;
use App\Services\Migration;
use Throwable;

class EditorController extends BaseController
{
    // Required block types that must always exist and cannot be duplicated or deleted
    private const REQUIRED_BLOCK_TYPES = [
        'schedule',      // מערכת שעות
        'calendar',      // לוח חופשות
        'whatsapp',      // קבוצות וואצטאפ
        'links',         // קישורים שימושיים
        'contact_page',  // דף קשר
        'contacts'       // אנשי קשר חשובים
    ];

    public function index(array $params): void
    {
        $pageId = (int)($params['pageId'] ?? 0);
        $user = $this->container->get('user');

        if (!$this->db) {
            $this->response->json(['ok' => false, 'error_code' => 'DB_ERROR'], 500);
            return;
        }

        $pageRepo = new PageRepository($this->db);
        $page = $pageRepo->findById($pageId);

        if (!$page) {
            $this->response->view('editor/error', ['message' => 'Page not found']);
            return;
        }

        // Verify access
        if ($user['role'] !== 'system_admin') {
            $userRepo = new UserRepository($this->db);
            if (!$userRepo->isPageAdmin($user['id'], $pageId)) {
                $this->response->view('editor/error', ['message' => 'Access denied']);
                return;
            }
        }

        $blockRepo = new BlockRepository($this->db);
        $announcementRepo = new AnnouncementRepository($this->db);

        $blocks = $blockRepo->findByPage($pageId);
        $announcements = $announcementRepo->findByPage($pageId);

        foreach ($blocks as &$block) {
            $block['data'] = json_decode($block['data_json'], true) ?? [];
        }

        $this->response->view('editor/index', [
            'page' => $page,
            'blocks' => $blocks,
            'announcements' => $announcements,
            'container' => $this->container
        ]);
    }

    public function getPage(array $params): void
    {
        $pageId = (int)($params['pageId'] ?? 0);
        $pageRepo = new PageRepository($this->db);
        $page = $pageRepo->findById($pageId);

        if (!$page) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        $page['settings'] = json_decode($page['settings_json'], true) ?? [];
        unset($page['settings_json']);

        $this->response->json(['ok' => true, 'page' => $page]);
    }

    public function getBlock(array $params): void
    {
        Logger::info("EditorController::getBlock: Starting");
        $blockId = (int)($params['blockId'] ?? 0);
        $pageId = (int)($params['pageId'] ?? 0);
        
        try {
            $blockRepo = new BlockRepository($this->db);
            $block = $blockRepo->findById($blockId);

            if (!$block) {
                Logger::error("EditorController::getBlock: Block not found", ['id' => $blockId]);
                $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
                return;
            }

            if ($block['page_id'] != $pageId) {
                Logger::error("EditorController::getBlock: Page mismatch", ['block_page' => $block['page_id'], 'req_page' => $pageId]);
                $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
                return;
            }

            $block['data'] = json_decode($block['data_json'], true) ?? [];
            unset($block['data_json']);
            
            $this->response->json(['ok' => true, 'block' => $block]);
        } catch (\Throwable $e) {
            Logger::error("EditorController::getBlock: Exception", ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'error_code' => 'INTERNAL_ERROR', 'message_he' => 'שגיאה פנימית: ' . $e->getMessage()], 500);
        }
    }

    public function updateBlock(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $blockId = (int)($params['blockId'] ?? 0);
        $data = $this->request->json();

        $blockRepo = new BlockRepository($this->db);
        $block = $blockRepo->findById($blockId);

        if (!$block) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        $updateData = [];
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['data'])) {
            $updateData['data_json'] = $data['data'];
        }

        $blockRepo->update($blockId, $updateData);

        $block = $blockRepo->findById($blockId);
        $block['data'] = json_decode($block['data_json'], true) ?? [];

        $this->response->json(['ok' => true, 'block' => $block]);
    }

    public function deleteBlock(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $blockId = (int)($params['blockId'] ?? 0);
        $blockRepo = new BlockRepository($this->db);
        $block = $blockRepo->findById($blockId);

        if (!$block) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        // Prevent deletion of required blocks
        if (in_array($block['type'], self::REQUIRED_BLOCK_TYPES, true)) {
            $this->response->json([
                'ok' => false,
                'error_code' => 'CANNOT_DELETE_REQUIRED_BLOCK',
                'message_he' => 'לא ניתן למחוק בלוק מסוג זה - הוא נדרש תמיד להיות קיים'
            ], 400);
            return;
        }

        $blockRepo->delete($blockId);

        $this->response->json(['ok' => true]);
    }

    public function reorderBlocks(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();
        $blockIds = array_map('intval', $data['block_ids'] ?? []);

        $blockRepo = new BlockRepository($this->db);
        $blockRepo->reorder($pageId, $blockIds);

        $this->response->json(['ok' => true]);
    }

    public function createAnnouncement(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure columns exist
        $migration = new \App\Services\Migration($this->db);
        $migration->addAnnouncementFields();

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();

        // Handle HTML content - allow null or empty string
        $htmlValue = $data['html'] ?? null;
        if ($htmlValue === null || $htmlValue === '') {
            $html = '';
        } else {
            $html = $this->sanitizeHtml($htmlValue);
        }
        error_log("createAnnouncement: html value = " . ($htmlValue ? 'has value' : 'empty/null') . ", length = " . strlen($html));
        
        $title = !empty($data['title']) ? trim($data['title']) : null;
        $date = !empty($data['date']) ? trim($data['date']) : null;

        $announcementRepo = new AnnouncementRepository($this->db);
        $orderIndex = $announcementRepo->getMaxOrderIndex($pageId) + 1;

        $announcementId = $announcementRepo->create($pageId, $html, $orderIndex, $title, $date);

        $announcement = $announcementRepo->findById($announcementId);

        $this->response->json(['ok' => true, 'announcement' => $announcement]);
    }

    public function getAnnouncement(array $params): void
    {
        $announcementId = (int)($params['announcementId'] ?? 0);
        $pageId = (int)($params['pageId'] ?? 0);
        
        $announcementRepo = new AnnouncementRepository($this->db);
        $announcement = $announcementRepo->findById($announcementId);

        if (!$announcement) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        // Verify the announcement belongs to the page
        if ($announcement['page_id'] != $pageId) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        $this->response->json(['ok' => true, 'announcement' => $announcement]);
    }

    public function updateAnnouncement(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure columns exist
        $migration = new \App\Services\Migration($this->db);
        $migration->addAnnouncementFields();

        $announcementId = (int)($params['announcementId'] ?? 0);
        $data = $this->request->json();

        $updateData = [];
        // Always update html if it's provided in the request
        if (isset($data['html'])) {
            $html = $data['html'];
            // Handle null, empty string, or placeholder HTML
            if ($html === null || $html === '' || $html === '<p><br></p>' || $html === '<p></p>') {
                $updateData['html'] = '';
            } else {
                // Sanitize and save the HTML content
                $updateData['html'] = $this->sanitizeHtml((string)$html);
            }
            error_log("updateAnnouncement: html input = " . ($html ? 'has value' : 'empty/null') . ", length = " . strlen((string)$html));
            error_log("updateAnnouncement: html output length = " . strlen($updateData['html'] ?? ''));
        }
        if (isset($data['title'])) {
            $updateData['title'] = !empty($data['title']) ? trim($data['title']) : null;
        }
        if (isset($data['date'])) {
            $updateData['date'] = !empty($data['date']) ? trim($data['date']) : null;
        }

        error_log("updateAnnouncement: updateData = " . json_encode($updateData));

        $announcementRepo = new AnnouncementRepository($this->db);
        $announcementRepo->update($announcementId, $updateData);

        $announcement = $announcementRepo->findById($announcementId);
        error_log("updateAnnouncement: saved announcement html length = " . strlen($announcement['html'] ?? ''));

        $this->response->json(['ok' => true, 'announcement' => $announcement]);
    }

    public function deleteAnnouncement(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $announcementId = (int)($params['announcementId'] ?? 0);
        $announcementRepo = new AnnouncementRepository($this->db);
        $announcementRepo->delete($announcementId);

        $this->response->json(['ok' => true]);
    }

    public function reorderAnnouncements(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();
        $announcementIds = array_map('intval', $data['announcement_ids'] ?? []);

        $announcementRepo = new AnnouncementRepository($this->db);
        $announcementRepo->reorder($pageId, $announcementIds);

        $this->response->json(['ok' => true]);
    }

    public function createEvent(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure table exists
        $migration = new \App\Services\Migration($this->db);
        $migration->createEventsTable();

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();

        $eventRepo = new EventRepository($this->db);
        $eventId = $eventRepo->create($pageId, $data);

        $event = $eventRepo->findById($eventId);

        $this->response->json(['ok' => true, 'event' => $event]);
    }

    public function getEvent(array $params): void
    {
        $eventId = (int)($params['eventId'] ?? 0);
        $pageId = (int)($params['pageId'] ?? 0);
        
        $eventRepo = new EventRepository($this->db);
        $event = $eventRepo->findById($eventId);

        if (!$event) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        // Verify the event belongs to the page
        if ($event['page_id'] != $pageId) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        $this->response->json(['ok' => true, 'event' => $event]);
    }

    public function updateEvent(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $eventId = (int)($params['eventId'] ?? 0);
        $data = $this->request->json();

        $eventRepo = new EventRepository($this->db);
        $eventRepo->update($eventId, $data);

        $event = $eventRepo->findById($eventId);

        $this->response->json(['ok' => true, 'event' => $event]);
    }

    public function deleteEvent(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $eventId = (int)($params['eventId'] ?? 0);
        $eventRepo = new EventRepository($this->db);
        $eventRepo->delete($eventId);

        $this->response->json(['ok' => true]);
    }

    public function createHomework(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure table exists
        $migration = new \App\Services\Migration($this->db);
        $migration->createHomeworkTable();

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();

        // Handle HTML content - allow null or empty string
        $htmlValue = $data['html'] ?? '';
        if ($htmlValue === null || $htmlValue === '') {
            $html = '';
        } else {
            $html = $this->sanitizeHtml($htmlValue);
        }
        error_log("createHomework: html length = " . strlen($html));
        
        $title = !empty($data['title']) ? trim($data['title']) : null;
        $date = !empty($data['date']) ? trim($data['date']) : date('Y-m-d');
        // Extract only date part (remove time if exists)
        if ($date && strpos($date, ' ') !== false) {
            $dateParts = explode(' ', $date);
            $date = $dateParts[0];
        }
        error_log("createHomework: date = " . $date);

        $homeworkRepo = new HomeworkRepository($this->db);
        $homeworkId = $homeworkRepo->create($pageId, [
            'title' => $title,
            'html' => $html,
            'date' => $date
        ]);

        $homework = $homeworkRepo->findById($homeworkId);

        $this->response->json(['ok' => true, 'homework' => $homework]);
    }

    public function getHomework(array $params): void
    {
        $homeworkId = (int)($params['homeworkId'] ?? 0);
        $pageId = (int)($params['pageId'] ?? 0);
        
        $homeworkRepo = new HomeworkRepository($this->db);
        $homework = $homeworkRepo->findById($homeworkId);

        if (!$homework) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        // Verify the homework belongs to the page
        if ($homework['page_id'] != $pageId) {
            $this->response->json(['ok' => false, 'error_code' => 'NOT_FOUND'], 404);
            return;
        }

        $this->response->json(['ok' => true, 'homework' => $homework]);
    }

    public function updateHomework(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure table exists
        $migration = new \App\Services\Migration($this->db);
        $migration->createHomeworkTable();

        $homeworkId = (int)($params['homeworkId'] ?? 0);
        $data = $this->request->json();

        $updateData = [];
        if (isset($data['html'])) {
            $htmlValue = $data['html'] ?? '';
            if ($htmlValue === null || $htmlValue === '') {
                $updateData['html'] = '';
            } else {
                $updateData['html'] = $this->sanitizeHtml($htmlValue);
            }
            error_log("updateHomework: html length = " . strlen($updateData['html']));
        }
        if (isset($data['title'])) {
            $updateData['title'] = !empty($data['title']) ? trim($data['title']) : null;
        }
        if (isset($data['date'])) {
            $date = !empty($data['date']) ? trim($data['date']) : date('Y-m-d');
            // Extract only date part (remove time if exists)
            if ($date && strpos($date, ' ') !== false) {
                $dateParts = explode(' ', $date);
                $date = $dateParts[0];
            }
            $updateData['date'] = $date;
            error_log("updateHomework: date = " . $updateData['date']);
        }

        $homeworkRepo = new HomeworkRepository($this->db);
        $homeworkRepo->update($homeworkId, $updateData);

        $homework = $homeworkRepo->findById($homeworkId);

        $this->response->json(['ok' => true, 'homework' => $homework]);
    }

    public function deleteHomework(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $homeworkId = (int)($params['homeworkId'] ?? 0);
        $homeworkRepo = new HomeworkRepository($this->db);
        $homeworkRepo->delete($homeworkId);

        $this->response->json(['ok' => true]);
    }

    public function updateSettings(array $params): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        // Run migration to ensure page fields exist
        $migration = new \App\Services\Migration($this->db);
        $migration->migrateAll();

        $pageId = (int)($params['pageId'] ?? 0);
        $data = $this->request->json();

        $pageRepo = new PageRepository($this->db);
        $updateData = [];

        if (isset($data['school_name'])) {
            $updateData['school_name'] = $data['school_name'];
        }
        if (isset($data['city_name'])) {
            $updateData['city'] = $data['city_name'];
        }
        if (isset($data['class_grade'])) {
            $updateData['class_type'] = $data['class_grade'];
        }
        if (isset($data['class_number'])) {
            $updateData['class_number'] = (int)$data['class_number'];
        }
        
        // Rebuild class title to match admin format
        if (isset($updateData['class_type']) || isset($updateData['class_number'])) {
            $type = $updateData['class_type'] ?? 'א';
            $num = $updateData['class_number'] ?? 1;
            $updateData['class_title'] = "כיתה {$type}' {$num}";
        }

        if (isset($data['settings'])) {
            $updateData['settings_json'] = $data['settings'];
        }

        $pageRepo->update($pageId, $updateData);

        $page = $pageRepo->findById($pageId);

        $this->response->json(['ok' => true, 'page' => $page]);
    }
}

