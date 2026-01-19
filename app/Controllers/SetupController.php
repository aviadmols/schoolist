<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Logger;
use App\Services\I18n;
use Throwable;
use PDO;

/**
 * Controller for handling the initial application setup wizard.
 */
class SetupController extends BaseController
{
    private const MIN_PHP_VERSION = '8.2.0';
    private const SETUP_LOCK_FILE = ROOT_PATH . '/setup.lock';

    /**
     * Renders the setup wizard page.
     * 
     * @return void
     */
    public function index(): void
    {
        try {
            if ($this->isAlreadyInstalled() && !isset($_GET['force_setup'])) {
                $this->response->view('setup/already_installed');
                return;
            }

            $step = (int)($this->request->input('step') ?? 1);
            if (!$this->i18n) $this->i18n = new I18n('he');
            
            $this->response->view('setup/index', ['step' => $step]);
        } catch (Throwable $e) {
            Logger::error("SetupController::index error", ['msg' => $e->getMessage()]);
            echo "<h1>Setup Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    /**
     * Processes a specific setup step via API.
     * 
     * @param array $params Route parameters.
     * @return void
     */
    public function processStep(array $params): void
    {
        try {
            $step = (int)($params['step'] ?? 1);
            $data = $this->request->json();

            switch ($step) {
                case 1: $this->validateRequirements(); break;
                case 2: $this->validateDatabase($data); break;
                case 3: $this->saveBaseUrl($data); break;
                case 4: $this->saveSmtpSettings($data); break;
                case 5: $this->completeInstallation($data); break;
                default: $this->response->json(['ok' => false, 'message_he' => 'שלב לא תקין'], 400);
            }
        } catch (Throwable $e) {
            Logger::error("Setup step $step failed", ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה: ' . $e->getMessage()], 500);
        }
    }

    // --- Setup Step Handlers ---

    /**
     * Step 1: Checks if server environment meets requirements.
     */
    private function validateRequirements(): void
    {
        $checks = [
            'php_version' => version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'curl' => extension_loaded('curl'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'writable_config' => is_writable(CONFIG_PATH) || getenv('MYSQLHOST'),
            'writable_uploads' => is_writable(UPLOADS_PATH) || (@mkdir(UPLOADS_PATH, 0755, true) && is_writable(UPLOADS_PATH))
        ];
        
        $ok = array_reduce($checks, fn($c, $i) => $c && $i, true);
        $this->response->json(['ok' => $ok, 'checks' => $checks]);
    }

    /**
     * Step 2: Validates database credentials.
     */
    private function validateDatabase(array $data): void
    {
        $host = $data['db_host'] ?? '';
        $name = $data['db_name'] ?? '';
        $user = $data['db_user'] ?? '';
        $pass = $data['db_pass'] ?? '';
        
        // Attempt connection
        new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $_SESSION['setup_db'] = [
            'host' => $host, 
            'name' => $name, 
            'user' => $user, 
            'pass' => $pass, 
            'prefix' => $data['db_prefix'] ?? 'sl_'
        ];
        
        $this->response->json(['ok' => true]);
    }

    /**
     * Step 3: Saves application base URL.
     */
    private function saveBaseUrl(array $data): void
    {
        $_SESSION['setup_base_url'] = rtrim($data['base_url'] ?? '', '/');
        $this->response->json(['ok' => true]);
    }

    /**
     * Step 4: Saves SMTP mail server settings.
     */
    private function saveSmtpSettings(array $data): void
    {
        $_SESSION['setup_smtp'] = [
            'enabled' => !empty($data['smtp_enabled']),
            'host' => $data['smtp_host'] ?? '',
            'port' => (int)($data['smtp_port'] ?? 587),
            'user' => $data['smtp_user'] ?? '',
            'pass' => $data['smtp_pass'] ?? '',
            'from' => $data['smtp_from'] ?? '',
            'from_name' => $data['smtp_from_name'] ?? 'Schoolist'
        ];
        $this->response->json(['ok' => true]);
    }

    /**
     * Step 5: Executes database schema and writes config file.
     */
    private function completeInstallation(array $data): void
    {
        if (empty($_SESSION['setup_db'])) {
            $this->response->json(['ok' => false, 'message_he' => 'נתונים חסרים'], 400);
            return;
        }

        $db = $_SESSION['setup_db'];
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // 1. Run Schema
        $sql = file_get_contents(DATABASE_PATH . '/schema.sql');
        $sql = str_replace('sl_', $db['prefix'], $sql);
        $pdo->exec($sql);
        
        // 2. Run Fixes if exist
        $linksPath = DATABASE_PATH . '/fix_create_links_table.sql';
        if (file_exists($linksPath)) $pdo->exec(file_get_contents($linksPath));

        // 3. Write config.php
        $this->writeConfigFile($db, $_SESSION['setup_base_url'] ?? '/', $_SESSION['setup_smtp'] ?? [], $data['openai_key'] ?? '');
        
        // 4. Create first admin user
        if (!empty($data['admin_email'])) {
            $stmt = $pdo->prepare("INSERT INTO {$db['prefix']}users (email, role, status, created_at) VALUES (?, 'system_admin', 'active', NOW())");
            $stmt->execute([$data['admin_email']]);
        }
        
        file_put_contents(self::SETUP_LOCK_FILE, date('Y-m-d H:i:s'));
        $this->response->json(['ok' => true]);
    }

    /**
     * Generates and saves the main config.php file.
     */
    private function writeConfigFile($db, $baseUrl, $smtp, $openaiKey): void
    {
        $content = "<?php\ndeclare(strict_types=1);\n\n";
        $content .= "/**\n * Auto-generated Configuration\n */\n\n";
        
        foreach(['HOST','NAME','USER','PASS','PREFIX'] as $k) {
            $content .= "safe_define('DB_$k', '".addslashes($db[strtolower($k)])."');\n";
        }
        
        $content .= "safe_define('BASE_URL', '".addslashes($baseUrl)."');\n";
        $content .= "safe_define('SMTP_ENABLED', ".($smtp['enabled'] ? 'true' : 'false').");\n";
        
        if ($smtp['enabled']) {
            foreach(['HOST','PORT','USER','PASS','FROM','FROM_NAME'] as $k) {
                $val = $smtp[strtolower($k)];
                $content .= "safe_define('SMTP_$k', ". (is_int($val) ? $val : "'".addslashes($val)."'") .");\n";
            }
        }
        
        if ($openaiKey) {
            $content .= "safe_define('OPENAI_API_KEY', '".addslashes($openaiKey)."');\n";
        }
        
        file_put_contents(CONFIG_PATH . '/config.php', $content);
    }

    /**
     * Checks if the system is already installed.
     */
    private function isAlreadyInstalled(): bool
    {
        return (defined('DB_HOST') && !empty(DB_HOST)) || file_exists(self::SETUP_LOCK_FILE);
    }
}
