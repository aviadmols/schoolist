<?php
/**
 * Schoolist Sync Tool
 * Usage: 
 *   php sync_env.php --to-local    (Copies Railway variables to config.local.php)
 */

require __DIR__ . '/app/bootstrap.php';

$action = $argv[1] ?? '';

if ($action === '--to-local') {
    echo "Fetching variables from Railway...\n";
    $output = shell_exec('railway variables --json');
    $vars = json_decode($output, true);

    if (!$vars) {
        die("Error: Could not fetch variables. Make sure you are logged in to Railway CLI.\n");
    }

    $content = "<?php\ndeclare(strict_types=1);\n\n/**\n * Local Development Configuration\n * Synced from Railway\n */\n\n";
    
    // Database
    $content .= "// Database\ndefine('DB_HOST', '" . ($vars['MYSQLHOST'] ?? '') . "');\n";
    $content .= "define('DB_PORT', '" . ($vars['MYSQLPORT'] ?? '') . "');\n";
    $content .= "define('DB_NAME', '" . ($vars['MYSQLDATABASE'] ?? '') . "');\n";
    $content .= "define('DB_USER', '" . ($vars['MYSQLUSER'] ?? '') . "');\n";
    $content .= "define('DB_PASS', '" . ($vars['MYSQLPASSWORD'] ?? '') . "');\n";
    $content .= "define('DB_PREFIX', '" . ($vars['DB_PREFIX'] ?? 'sl_') . "');\n\n";
    
    $content .= "// Admin\ndefine('ADMIN_EMAIL', '" . ($vars['ADMIN_EMAIL'] ?? '') . "');\n";
    $content .= "define('ADMIN_PHONE', '" . ($vars['ADMIN_PHONE'] ?? '') . "');\n";
    $content .= "define('ADMIN_MASTER_CODE', '" . ($vars['ADMIN_MASTER_CODE'] ?? '') . "');\n\n";
    
    $content .= "// SMS\ndefine('SMS_019_TOKEN', '" . ($vars['SMS_019_TOKEN'] ?? '') . "');\n";
    $content .= "define('SMS_SOURCE', '" . ($vars['SMS_SOURCE'] ?? '') . "');\n\n";
    
    $content .= "// SMTP\ndefine('SMTP_ENABLED', " . (($vars['SMTP_ENABLED'] ?? 'false') === 'true' ? 'true' : 'false') . ");\n";
    $content .= "define('SMTP_HOST', '" . ($vars['SMTP_HOST'] ?? '') . "');\n";
    $content .= "define('SMTP_PORT', " . ($vars['SMTP_PORT'] ?? 587) . ");\n";
    $content .= "define('SMTP_USER', '" . ($vars['SMTP_USER'] ?? '') . "');\n";
    $content .= "define('SMTP_PASS', '" . ($vars['SMTP_PASS'] ?? '') . "');\n";
    $content .= "define('SMTP_FROM', '" . ($vars['SMTP_FROM'] ?? '') . "');\n";
    $content .= "define('SMTP_FROM_NAME', '" . ($vars['SMTP_FROM_NAME'] ?? '') . "');\n\n";
    
    $content .= "define('BASE_URL', 'http://localhost:8000/');\n";

    file_put_contents(CONFIG_PATH . '/config.local.php', $content);
    echo "Success! config.local.php updated from Railway.\n";
} else {
    echo "Usage:\n";
    echo "  php sync_env.php --to-local    - Sync Railway variables to your local config\n";
}
