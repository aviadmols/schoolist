<?php
declare(strict_types=1);

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Allow direct access to assets (CSS, JS, images, etc.)
try {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    
    // Check if this is a public/assets request
    if (preg_match('#^/public/assets/#', $uri)) {
        $requestedFile = __DIR__ . $uri;
        if (file_exists($requestedFile) && is_file($requestedFile)) {
            $ext = pathinfo($requestedFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            $mimeType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            readfile($requestedFile);
            exit;
        }
    }
    
    // Check if this is an asset request (backward compatibility)
    if (preg_match('#^/assets/#', $uri)) {
        $requestedFile = __DIR__ . '/public' . $uri;
        if (file_exists($requestedFile) && is_file($requestedFile)) {
            $ext = pathinfo($requestedFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            $mimeType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            readfile($requestedFile);
            exit;
        }
    }
    
    // Check for uploads
    if (preg_match('#^/public/uploads/#', $uri)) {
        $requestedFile = __DIR__ . $uri;
        if (file_exists($requestedFile) && is_file($requestedFile)) {
            $ext = pathinfo($requestedFile, PATHINFO_EXTENSION);
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            $mimeType = $mimeTypes[strtolower($ext)] ?? 'application/octet-stream';
            header('Content-Type: ' . $mimeType);
            readfile($requestedFile);
            exit;
        }
    }
} catch (Throwable $e) {
    // If asset serving fails, continue to application
    error_log("Asset serving error: " . $e->getMessage());
}

try {
    // Bootstrap
    require_once __DIR__ . '/app/bootstrap.php';
    
    // Ensure Application class is loaded
    if (!class_exists('App\Core\Application')) {
        // Try to load it manually
        $appFile = __DIR__ . '/app/Core/Application.php';
        if (file_exists($appFile)) {
            require_once $appFile;
        } else {
            throw new Exception("Application class file not found: {$appFile}");
        }
    }

    $app = new App\Core\Application();
    $app->run();
} catch (Throwable $e) {
    // Check if this is an API call or setup API call
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $isApiCall = str_starts_with($uri, '/api/') || 
                 str_starts_with($uri, '/setup/step/') || 
                 str_starts_with($uri, '/setup/process-step');
    
    if ($isApiCall) {
        // Return JSON error for API calls
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error_code' => 'INTERNAL_ERROR',
            'message_he' => 'שגיאה פנימית: ' . $e->getMessage(),
            'details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Show HTML error for regular pages
        echo "<h1>Error</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    error_log("Application error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}

