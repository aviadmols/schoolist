<?php
declare(strict_types=1);

/**
 * Schoolist Application Bootstrap
 * Initializes constants, autoloader, session, and configurations.
 */

// 1. Error Reporting Configuration
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// 2. Core Path Definitions
define('ROOT_PATH', __DIR__ . '/..');
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DATABASE_PATH', ROOT_PATH . '/database');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOGS_PATH', ROOT_PATH . '/logs');

// 3. Helper for safe constant definition
if (!function_exists('safe_define')) {
    function safe_define(string $name, $value): void {
        if (!defined($name)) define($name, $value);
    }
}

// 4. PSR-4 Compliant Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($file)) require_once $file;
});

// 5. Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    // Railway specific temp path
    if (getenv('RAILWAY_PUBLIC_DOMAIN')) session_save_path(sys_get_temp_dir());
    
    session_start([
        'cookie_httponly' => true, 
        'cookie_secure' => $isHttps, 
        'cookie_samesite' => 'Lax'
    ]);
}

// 6. Configuration Loading
try {
    // A. Priority: Environment Variables for DATABASE ONLY (e.g., Railway)
    $envVars = [
        'DB_HOST' => 'MYSQLHOST',
        'DB_NAME' => 'MYSQLDATABASE',
        'DB_USER' => 'MYSQLUSER',
        'DB_PASS' => 'MYSQLPASSWORD',
        'DB_PORT' => 'MYSQLPORT'
    ];

    foreach ($envVars as $const => $env) {
        if ($val = getenv($env)) safe_define($const, $val);
    }

    // B. SMTP & Other Keys (without SMS – SMS comes from config files first)
    $settingsVars = [
        'ADMIN_EMAIL', 'ADMIN_PHONE', 'ADMIN_MASTER_CODE', 
        'SMTP_ENABLED', 'SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 
        'OPENAI_API_KEY'
    ];

    foreach ($settingsVars as $var) {
        if (($val = getenv($var)) !== false) {
            if ($var === 'SMTP_ENABLED') $val = ($val === 'true' || $val === '1');
            safe_define($var, $val);
        }
    }

    // C. Config Files Fallback (local/prod PHP config – includes SMS settings)
    // IMPORTANT: SMS settings MUST come from config files, NOT from admin panel updates
    $configLocal = CONFIG_PATH . '/config.local.php';
    if (file_exists($configLocal)) {
        require_once $configLocal;
        // Log what SMS values were loaded from config.local.php
        if (defined('SMS_019_TOKEN') || defined('SMS_SOURCE') || defined('SMS_USERNAME')) {
            error_log("Bootstrap: SMS settings loaded from config.local.php - Token: " . 
                (defined('SMS_019_TOKEN') ? substr(SMS_019_TOKEN, 0, 10) . '...' : 'NOT_SET') . 
                ", Source: " . (defined('SMS_SOURCE') ? SMS_SOURCE : 'NOT_SET') . 
                ", Username: " . (defined('SMS_USERNAME') ? SMS_USERNAME : 'NOT_SET'));
        }
    }
    
    $configProd = CONFIG_PATH . '/config.php';
    if (file_exists($configProd)) {
        require_once $configProd;
        // Log if SMS values came from config.php instead
        if (defined('SMS_019_TOKEN') || defined('SMS_SOURCE') || defined('SMS_USERNAME')) {
            error_log("Bootstrap: SMS settings loaded from config.php");
        }
    }

    // D. Optional: override SMS settings from environment ONLY if not already defined in config
    foreach (['SMS_019_TOKEN', 'SMS_SOURCE', 'SMS_USERNAME'] as $var) {
        if (!defined($var) && ($val = getenv($var)) !== false) {
            safe_define($var, $val);
            error_log("Bootstrap: SMS setting $var loaded from ENV (fallback)");
        }
    }

    // E. Global Defaults
    safe_define('BASE_URL', '/');
    safe_define('SMTP_ENABLED', false);
    safe_define('SMTP_PORT', 587);
    safe_define('SMS_USERNAME', 'Aviadmols');

} catch (Throwable $e) {
    error_log("Bootstrap Error: " . $e->getMessage());
}

// 7. Timezone Configuration
date_default_timezone_set('Asia/Jerusalem');
