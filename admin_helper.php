<?php
/**
 * Admin Helper Script
 * Use this script to:
 * 1. Create an admin user
 * 2. Generate an OTP code for login
 * 3. Reset rate limits
 * 
 * SECURITY: Delete this file after use!
 */

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

// Check if config exists
if (!file_exists(__DIR__ . '/config/config.php')) {
    die("Error: config.php not found. Please run setup first.\n");
}

require_once __DIR__ . '/config/config.php';

// Get database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'sl_';

echo "=== Admin Helper ===\n\n";

// Get email from command line or prompt
$email = $argv[1] ?? null;
if (!$email) {
    echo "Enter admin email: ";
    $email = trim(fgets(STDIN));
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address.\n");
}

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM {$prefix}users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Create admin user
    echo "Creating admin user...\n";
    $stmt = $pdo->prepare("INSERT INTO {$prefix}users (email, role, status, created_at) VALUES (?, 'system_admin', 'active', NOW())");
    $stmt->execute([$email]);
    echo "Admin user created!\n\n";
} else {
    // Update to admin if not already
    if ($user['role'] !== 'system_admin') {
        echo "Updating user to system_admin...\n";
        $stmt = $pdo->prepare("UPDATE {$prefix}users SET role = 'system_admin' WHERE email = ?");
        $stmt->execute([$email]);
        echo "User updated to system_admin!\n\n";
    } else {
        echo "User already exists as system_admin.\n\n";
    }
}

// Generate OTP
echo "Generating OTP code...\n";
$code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$codeHash = password_hash($code, PASSWORD_BCRYPT);

// Store OTP
$stmt = $pdo->prepare("INSERT INTO {$prefix}otp_codes (email, code_hash, expires_at, created_at, ip) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW(), '127.0.0.1')");
$stmt->execute([$email, $codeHash]);

// Reset rate limits
echo "Resetting rate limits...\n";
$rateLimitDir = __DIR__ . '/storage/ratelimit/';
if (is_dir($rateLimitDir)) {
    $files = glob($rateLimitDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    echo "Rate limits reset!\n\n";
}

echo "=== SUCCESS ===\n";
echo "Email: {$email}\n";
echo "OTP Code: {$code}\n";
echo "\n";
echo "Now you can:\n";
echo "1. Go to: http://app.schoolist.co.il/verify?email=" . urlencode($email) . "\n";
echo "2. Enter the OTP code: {$code}\n";
echo "3. You will be logged in as system admin\n";
echo "\n";
echo "SECURITY: Delete this file (admin_helper.php) after use!\n";















