<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\InvitationRepository;
use App\Repositories\PageRepository;
use App\Repositories\QActivationRepository;
use App\Repositories\UserRepository;
use App\Repositories\BlockRepository;
use App\Repositories\LinkRepository;
use App\Services\Logger;
use App\Services\EmailService;
use App\Services\Migration;
use Throwable;

/**
 * System Administration Controller.
 * Handles user management, invitations, pages, and system settings.
 */
class AdminController extends BaseController
{
    // View paths
    private const VIEW_INDEX = 'admin/index';
    private const VIEW_LOGS = 'admin/logs';
    private const VIEW_INVITATIONS = 'admin/invitations';
    private const VIEW_PAGES = 'admin/pages';
    private const VIEW_USERS = 'admin/users';
    private const VIEW_SMS_SETTINGS = 'admin/sms_settings';
    private const VIEW_AI_SETTINGS = 'admin/ai_settings';
    private const VIEW_Q_ACTIVATIONS = 'admin/q_activations';

    public function __construct(\App\Core\Container $container)
    {
        parent::__construct($container);
        // Ensure database is up to date for admin operations
        if ($this->db) {
            (new Migration($this->db))->migrateAll();
        }
    }

    /**
     * Dashboard main page.
     */
    public function index(): void
    {
        $this->renderView(self::VIEW_INDEX);
    }

    /**
     * Display system logs.
     */
    public function logs(): void
    {
        $logs = Logger::getLogs(500);
        $this->renderView(self::VIEW_LOGS, ['logs' => $logs]);
    }

    // --- User Management ---

    /**
     * Users management page.
     */
    public function users(): void
    {
        $this->renderView(self::VIEW_USERS);
    }

    /**
     * List all users via API.
     */
    public function listUsers(): void
    {
        $filters = [
            'email' => $this->request->input('email'),
            'name' => $this->request->input('name'),
            'role' => $this->request->input('role')
        ];
        $repo = new UserRepository($this->db);
        $this->response->json(['ok' => true, 'users' => $repo->list($filters)]);
    }

    /**
     * Create or update a user.
     */
    public function saveUser(): void
    {
        if (!$this->validateCsrf()) return;
        
        try {
            $data = $this->request->json();
            $id = isset($data['id']) ? (int)$data['id'] : null;
            $repo = new UserRepository($this->db);

            if (empty($data['email'])) {
                $this->response->json(['ok' => false, 'message_he' => 'אימייל הוא שדה חובה'], 400);
                return;
            }

            if ($id) {
                $repo->update($id, $data);
                $message = 'משתמש עודכן בהצלחה';
            } else {
                $existing = $repo->findByEmail($data['email']);
                if ($existing) {
                    $this->response->json(['ok' => false, 'message_he' => 'משתמש עם אימייל זה כבר קיים'], 400);
                    return;
                }
                $repo->create($data);
                $message = 'משתמש נוסף בהצלחה';
            }

            $this->response->json(['ok' => true, 'message_he' => $message]);
        } catch (Throwable $e) {
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search users for autocomplete.
     */
    public function searchUsers(): void
    {
        $term = $this->request->input('term') ?? '';
        $repo = new UserRepository($this->db);
        $this->response->json(['ok' => true, 'users' => $repo->search($term)]);
    }

    // --- Invitation Management ---

    /**
     * Manage invitation codes page.
     */
    public function invitations(): void
    {
        $this->renderView(self::VIEW_INVITATIONS);
    }

    /**
     * List invitations via API.
     */
    public function listInvitations(): void
    {
        $filters = [
            'school_name' => $this->request->input('school_name'),
            'admin_email' => $this->request->input('admin_email'),
            'status' => $this->request->input('status')
        ];

        $repo = new InvitationRepository($this->db);
        $this->response->json(['ok' => true, 'invitations' => $repo->list($filters)]);
    }

    /**
     * Create a new invitation and send login link via email.
     */
    public function createInvitation(): void
    {
        try {
            if (!$this->validateCsrf()) return;

            $data = $this->request->json();
            $schoolName = trim($data['school_name'] ?? '');
            $adminEmail = filter_var($data['admin_email'] ?? '', FILTER_SANITIZE_EMAIL);

            if (empty($schoolName) || empty($adminEmail)) {
                $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
                return;
            }

            $repo = new InvitationRepository($this->db);
            $code = $repo->generateCode();
            $id = $repo->create($code, $schoolName, $adminEmail);

            $invitation = $this->db->fetch("SELECT * FROM {$this->db->table('invitation_codes')} WHERE id = ?", [$id]);
            
            // Send email
            $loginLink = $this->generateLoginLink($code, $adminEmail);
            $emailService = $this->container->get(EmailService::class);
            $emailSent = $emailService->sendInvitationLink($adminEmail, $schoolName, $loginLink);

            if (!$emailSent) Logger::error("Admin: Failed to send invitation email", ['email' => $adminEmail]);

            $this->response->json(['ok' => true, 'invitation' => $invitation, 'login_link' => $loginLink, 'email_sent' => $emailSent]);

        } catch (Throwable $e) {
            Logger::error("Admin::createInvitation: " . $e->getMessage());
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an invitation status or details.
     */
    public function updateInvitation(array $params): void
    {
        if (!$this->validateCsrf()) return;

        $id = (int)($params['id'] ?? 0);
        $data = $this->request->json();

        $repo = new InvitationRepository($this->db);
        $repo->update($id, $data);

        $invitation = $this->db->fetch("SELECT * FROM {$this->db->table('invitation_codes')} WHERE id = ?", [$id]);
        $this->response->json(['ok' => true, 'invitation' => $invitation]);
    }

    // --- Page Management ---

    /**
     * Manage pages page.
     */
    public function pages(): void
    {
        $cities = [];
        try {
            $tableName = $this->db->table('cities');
            $cities = $this->db->fetchAll("SELECT name FROM `{$tableName}` ORDER BY name ASC");
        } catch (Throwable $e) {
            Logger::error("Failed to fetch cities: " . $e->getMessage());
        }
        $this->renderView(self::VIEW_PAGES, [
            'cities' => array_column($cities, 'name')
        ]);
    }

    /**
     * List all pages with filters.
     */
    public function listPages(): void
    {
        $filters = [
            'school_name' => $this->request->input('school_name'),
            'class_title' => $this->request->input('class_title'),
            'unique_id' => $this->request->input('unique_id'),
            'city' => $this->request->input('city')
        ];

        $repo = new PageRepository($this->db);
        $this->response->json(['ok' => true, 'pages' => $repo->list($filters)]);
    }

    /**
     * Manual page creation by admin.
     */
    public function createPage(): void
    {
        if (!$this->validateCsrf()) return;

        try {
            $data = $this->request->json();
            $schoolName = trim($data['school_name'] ?? '');
            $city = trim($data['city'] ?? '');
            $classType = trim($data['class_type'] ?? '');
            $classNumber = (int)($data['class_number'] ?? 0);
            $adminIds = $data['admin_ids'] ?? []; // Array of user IDs

            if (empty($schoolName) || empty($city) || empty($classType)) {
                $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
                return;
            }

            $pageRepo = new PageRepository($this->db);
            
            // Format class title
            $classTitle = "כיתה {$classType}' {$classNumber}";

            // Create page
            $pageId = $pageRepo->create([
                'unique_numeric_id' => $pageRepo->generateUniqueId(),
                'school_name' => $schoolName,
                'city' => $city,
                'class_type' => $classType,
                'class_number' => $classNumber,
                'class_title' => $classTitle,
                'settings_json' => '{}'
            ]);

            // Setup default blocks
            $this->initializeDefaultBlocks($pageId);

            // Assign admins
            foreach ($adminIds as $userId) {
                $this->db->query("INSERT IGNORE INTO {$this->db->table('page_admins')} (page_id, user_id) VALUES (?, ?)", [$pageId, (int)$userId]);
            }

            $this->response->json(['ok' => true, 'page' => $pageRepo->findById($pageId)]);
        } catch (Throwable $e) {
            Logger::error('Admin::createPage error', ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה ביצירת הדף: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a class page.
     */
    public function deletePage(array $params): void
    {
        if (!$this->validateCsrf()) return;

        try {
            $id = (int)($params['id'] ?? 0);
            $repo = new PageRepository($this->db);
            $repo->delete($id);
            $this->response->json(['ok' => true, 'message_he' => 'הדף נמחק בהצלחה']);
        } catch (Throwable $e) {
            Logger::error('Admin::deletePage error', ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה במחיקת הדף'], 500);
        }
    }

    // --- Q Activations & Links ---

    public function qActivations(): void
    {
        $this->renderView(self::VIEW_Q_ACTIVATIONS);
    }

    public function listQActivations(): void
    {
        $filters = [
            'q_number' => $this->request->input('q_number'),
            'page_unique_id' => $this->request->input('page_unique_id'),
            'status' => $this->request->input('status')
        ];
        $repo = new QActivationRepository($this->db);
        $this->response->json(['ok' => true, 'activations' => $repo->list($filters)]);
    }

    public function listLinks(): void
    {
        $filters = ['link_number' => $this->request->input('link_number'), 'q_number' => $this->request->input('q_number')];
        $repo = new LinkRepository($this->db);
        $this->response->json(['ok' => true, 'links' => $repo->list($filters)]);
    }

    public function createLink(): void
    {
        $data = $this->request->json();
        $linkNumber = (int)($data['link_number'] ?? 0);
        $qNumber = (int)($data['q_number'] ?? 0);

        if ($linkNumber <= 0 || $qNumber <= 0) {
            $this->response->json(['ok' => false, 'message_he' => 'נתונים לא תקינים'], 400);
            return;
        }

        $repo = new LinkRepository($this->db);
        if ($repo->findByNumber($linkNumber)) {
            $this->response->json(['ok' => false, 'message_he' => 'מספר קישור זה כבר קיים'], 400);
            return;
        }

        $repo->create($linkNumber, $qNumber);
        $this->response->json(['ok' => true]);
    }

    public function deleteLink(): void
    {
        $linkNumber = (int)($this->request->json()['link_number'] ?? 0);
        if ($linkNumber > 0) {
            (new LinkRepository($this->db))->delete($linkNumber);
            $this->response->json(['ok' => true]);
        } else {
            $this->response->json(['ok' => false, 'message_he' => 'מספר קישור לא תקין'], 400);
        }
    }

    // --- Settings Management ---

    /**
     * AI Settings page.
     */
    public function aiSettings(): void
    {
        $keys = ['OPENAI_API_KEY', 'OPENAI_MODEL', 'OPENAI_SCHEDULE_PROMPT', 'OPENAI_CONTACTS_PROMPT'];
        $this->renderView(self::VIEW_AI_SETTINGS, ['settings' => $this->loadConfigSettings($keys)]);
    }

    /**
     * Update AI configuration.
     */
    public function updateAiSettings(): void
    {
        if (!$this->validateCsrf()) return;
        $data = $this->request->json();
        
        $updates = [
            'OPENAI_API_KEY' => trim($data['api_key'] ?? ''),
            'OPENAI_MODEL' => trim($data['model'] ?? 'gpt-4o'),
            'OPENAI_SCHEDULE_PROMPT' => $data['schedule_prompt'] ?? '',
            'OPENAI_CONTACTS_PROMPT' => $data['contacts_prompt'] ?? ''
        ];

        if (empty($updates['OPENAI_API_KEY'])) {
            $this->response->json(['ok' => false, 'message_he' => 'נא להזין מפתח API'], 400);
            return;
        }

        $this->updateLocalConfig($updates);
        $this->response->json(['ok' => true, 'message_he' => 'הגדרות AI נשמרו בהצלחה']);
    }

    /**
     * Get current AI settings.
     */
    public function getAiSettings(): void
    {
        $settings = $this->loadConfigSettings(['OPENAI_API_KEY', 'OPENAI_MODEL', 'OPENAI_SCHEDULE_PROMPT', 'OPENAI_CONTACTS_PROMPT']);
        $this->response->json(['ok' => true, 'settings' => $settings]);
    }

    /**
     * Test the AI connection.
     */
    public function testAiConnection(): void
    {
        if (!$this->validateCsrf()) return;

        $data = $this->request->json();
        $apiKey = trim($data['api_key'] ?? '');
        
        if (empty($apiKey)) {
            $this->response->json(['ok' => false, 'message_he' => 'נא להזין מפתח API'], 400);
            return;
        }

        $testData = [
            'model' => trim($data['model'] ?? 'gpt-4o'),
            'messages' => [['role' => 'user', 'content' => 'Say OK']],
            'max_tokens' => 5
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($testData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $this->response->json(['ok' => true, 'message' => 'חיבור ה-AI עובד בהצלחה!']);
        } else {
            $err = json_decode((string)$response, true);
            $msg = $err['error']['message'] ?? 'שגיאת חיבור';
            $this->response->json(['ok' => false, 'message_he' => "שגיאה ($httpCode): $msg"], 400);
        }
    }

    /**
     * SMS Settings management page.
     */
    public function smsSettings(): void
    {
        $settings = $this->loadConfigSettings(['SMS_019_TOKEN', 'SMS_SOURCE', 'SMS_USERNAME']);
        $this->renderView(self::VIEW_SMS_SETTINGS, ['settings' => $settings]);
    }

    /**
     * Update SMS provider configuration.
     */
    public function updateSmsSettings(): void
    {
        if (!$this->validateCsrf()) return;

        $data = $this->request->json();
        $updates = [
            'SMS_019_TOKEN' => trim($data['token'] ?? ''),
            'SMS_SOURCE' => trim($data['source'] ?? ''),
            'SMS_USERNAME' => trim($data['username'] ?? '')
        ];

        if (empty($updates['SMS_019_TOKEN']) || empty($updates['SMS_SOURCE'])) {
            $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
            return;
        }

        $this->updateLocalConfig($updates);
        $this->response->json(['ok' => true, 'message_he' => 'הגדרות SMS נשמרו בהצלחה']);
    }

    // --- Private Utility Methods ---

    /**
     * Helper to render views with consistent data.
     */
    private function renderView(string $view, array $data = []): void
    {
        $data['csrf_token'] = $this->request->csrfToken();
        $data['request'] = $this->request;
        $this->response->view($view, $data);
    }

    /**
     * Initializes a new page with required default blocks.
     */
    private function initializeDefaultBlocks(int $pageId): void
    {
        $blockRepo = new BlockRepository($this->db);
        $defaults = [
            'schedule' => 'מערכת שעות',
            'calendar' => 'לוח חופשות וחגים',
            'whatsapp' => 'קבוצות וואטסאפ',
            'links' => 'קישורים שימושיים',
            'contact_page' => 'דף קשר',
            'contacts' => 'אנשי קשר חשובים'
        ];
        
        $i = 0;
        foreach ($defaults as $type => $title) {
            $blockRepo->create($pageId, $type, $title, [], $i++);
        }
    }

    /**
     * Generates a fully qualified login link for an invitation.
     */
    private function generateLoginLink(string $code, string $email): string
    {
        $baseUrl = defined('BASE_URL') ? BASE_URL : 'http://localhost:8000/';
        return rtrim($baseUrl, '/') . '/login/' . $code . '?email=' . urlencode($email);
    }

    /**
     * Helper to read current defines from config.local.php.
     */
    private function loadConfigSettings(array $keys): array
    {
        $settings = [];
        $path = ROOT_PATH . '/config/config.local.php';
        if (!file_exists($path)) return [];

        $content = file_get_contents($path);
        $map = [
            'SMS_019_TOKEN' => 'token', 'SMS_SOURCE' => 'source', 'SMS_USERNAME' => 'username',
            'OPENAI_API_KEY' => 'api_key', 'OPENAI_MODEL' => 'model', 
            'OPENAI_SCHEDULE_PROMPT' => 'schedule_prompt', 'OPENAI_CONTACTS_PROMPT' => 'contacts_prompt'
        ];

        foreach ($keys as $key) {
            $friendlyKey = $map[$key] ?? strtolower($key);
            if (preg_match("/safe_define\('$key',\s*'((?:[^'\\\\]|\\\\.)*)'\);/s", $content, $matches)) {
                $settings[$friendlyKey] = stripslashes($matches[1]);
            } elseif (preg_match("/define\('$key',\s*'((?:[^'\\\\]|\\\\.)*)'\);/s", $content, $matches)) {
                $settings[$friendlyKey] = stripslashes($matches[1]);
            }
        }
        return $settings;
    }

    /**
     * Updates config.local.php with new values while preserving other data.
     */
    private function updateLocalConfig(array $updates): void
    {
        $path = ROOT_PATH . '/config/config.local.php';
        $content = file_exists($path) ? file_get_contents($path) : "<?php\ndeclare(strict_types=1);";

        foreach ($updates as $key => $val) {
            $content = preg_replace("/safe_define\('$key',\s*'[^']+'\);\n?/", '', $content);
            $content = preg_replace("/define\('$key',\s*'[^']+'\);\n?/", '', $content);
            $content = rtrim($content) . "\nsafe_define('$key', '" . addslashes((string)$val) . "');\n";
        }

        file_put_contents($path, $content);
    }
}
