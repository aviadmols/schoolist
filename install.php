<?php
/**
 * Schoolist App - Installation Script
 */

declare(strict_types=1);

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// --- Path Constants (Same as bootstrap.php) ---
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('DATABASE_PATH', ROOT_PATH . '/database');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOGS_PATH', ROOT_PATH . '/logs');
// ----------------------

// Start output buffering
ob_start();

// Set error handler to display errors in HTML
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    echo '<div class="step error"><div class="step-title">שגיאת PHP</div>';
    echo '<p class="error">' . htmlspecialchars($errstr) . ' בקובץ ' . htmlspecialchars($errfile) . ' בשורה ' . $errline . '</p></div>';
    return true;
});

?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התקנת Schoolist App</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; direction: rtl; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0C4A6E; margin-bottom: 30px; }
        .step { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; border-right: 4px solid #0C4A6E; }
        .step-title { font-weight: bold; color: #0C4A6E; margin-bottom: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #0C4A6E; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>התקנת Schoolist App</h1>
        <?php

// Load configuration
if (getenv('MYSQLHOST')) {
    define('DB_HOST', getenv('MYSQLHOST'));
    define('DB_NAME', getenv('MYSQLDATABASE'));
    define('DB_USER', getenv('MYSQLUSER'));
    define('DB_PASS', getenv('MYSQLPASSWORD'));
    define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
    define('DB_PREFIX', getenv('DB_PREFIX') ?: 'sl_');
} else {
    $configLocal = CONFIG_PATH . '/config.local.php';
    $configProd = CONFIG_PATH . '/config.php';
    if (file_exists($configLocal)) require_once $configLocal;
    elseif (file_exists($configProd)) require_once $configProd;
}

if (!defined('DB_HOST')) {
    echo '<div class="step error"><div class="step-title">שגיאה: פרטי מסד הנתונים לא הוגדרו</div>';
    echo '<p>אנא ודא שקובץ הקונפיגורציה או Environment Variables מוגדרים.</p></div>';
    exit;
}

// Load autoloader manually
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\Database;
use App\Services\Migration;

$errors = []; $warnings = []; $success = [];
ob_flush(); flush();

echo '<div class="step"><div class="step-title">שלב 1: בדיקת חיבור למסד הנתונים</div>';
try {
    $dbPort = (int)(defined('DB_PORT') ? DB_PORT : 3306);
    $dbPrefix = defined('DB_PREFIX') ? DB_PREFIX : 'sl_';
    $db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, $dbPrefix, $dbPort);
    $db->query('SELECT 1');
    echo '<p class="success">✓ חיבור למסד הנתונים הצליח</p>';
    $success[] = 'Database connection';
} catch (Throwable $e) {
    echo '<p class="error">✗ שגיאה בחיבור למסד הנתונים: ' . htmlspecialchars($e->getMessage()) . '</p>';
    $errors[] = 'Database connection: ' . $e->getMessage();
    exit;
}
echo '</div>';

echo '<div class="step"><div class="step-title">שלב 2: יצירת טבלאות בסיס</div>';
try {
    $schemaFiles = [DATABASE_PATH . '/schema.sql', DATABASE_PATH . '/fix_create_links_table.sql'];
    foreach ($schemaFiles as $schemaPath) {
        if (!file_exists($schemaPath)) continue;
        $schema = file_get_contents($schemaPath);
        $statements = array_filter(array_map('trim', explode(';', $schema)), fn($stmt) => !empty($stmt) && !preg_match('/^--|^\/\*/', $stmt));
        foreach ($statements as $statement) {
            if (strlen(trim($statement)) > 10) {
                try { $db->query($statement); } 
                catch (Exception $e) {
                    if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate') === false) {
                        $warnings[] = basename($schemaPath) . ': ' . $e->getMessage();
                    }
                }
            }
        }
    }
    echo '<p class="success">✓ טבלאות בסיס נוצרו/נבדקו</p>';
    $success[] = 'Base tables';
} catch (Exception $e) {
    echo '<p class="error">✗ שגיאה ביצירת טבלאות: ' . htmlspecialchars($e->getMessage()) . '</p>';
    $errors[] = 'Base tables: ' . $e->getMessage();
}
echo '</div>';

echo '<div class="step"><div class="step-title">שלב 3: הרצת מיגרציות</div>';
try {
    $migration = new Migration($db);
    $migrations = [
        'Announcement Fields' => fn() => $migration->addAnnouncementFields(),
        'Events Table' => fn() => $migration->createEventsTable(),
        'Homework Table' => fn() => $migration->createHomeworkTable(),
        'Page Fields' => fn() => $migration->addPageFields(),
        'Cities Table' => fn() => $migration->createCitiesTable(),
        'Invitation Fields' => fn() => $migration->addInvitationFields()
    ];
    foreach ($migrations as $name => $func) {
        try {
            if ($func()) { echo "<p class='success'>✓ $name - הושלם</p>"; $success[] = $name; }
            else { echo "<p class='warning'>⚠ $name - הושלם עם אזהרות</p>"; $warnings[] = $name; }
        } catch (Exception $e) { echo "<p class='error'>✗ $name - שגיאה: {$e->getMessage()}</p>"; $errors[] = "$name: {$e->getMessage()}"; }
    }
} catch (Exception $e) { echo "<p class='error'>✗ שגיאה בהרצת מיגרציות: {$e->getMessage()}</p>"; $errors[] = "Migrations: {$e->getMessage()}"; }
echo '</div>';

echo '<div class="step"><div class="step-title">שלב 4: סיכום</div>';
if (empty($errors)) {
    echo '<p class="success"><strong>התקנה הושלמה בהצלחה!</strong></p><a href="/" class="btn">עבור לאפליקציה</a>';
} else {
    echo '<p class="error"><strong>התקנה הושלמה עם שגיאות:</strong></p><ul>';
    foreach ($errors as $error) echo "<li class='error'>".htmlspecialchars($error)."</li>";
    echo '</ul>';
}
echo '</div>';
ob_end_flush();
?>
    </div>
</body>
</html>
