<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    private ?Container $container = null;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
    public function json(array $data, int $status = 200): void
    {
        try {
            error_log("Response::json: Starting, status=$status");
            
            // Clear any previous output
            if (ob_get_level() > 0) {
                ob_clean();
            }
            
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            
            error_log("Response::json: Encoding data");
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false) {
                error_log('JSON encode error: ' . json_last_error_msg() . ', Data keys: ' . implode(', ', array_keys($data)));
                http_response_code(500);
                $data = [
                    'ok' => false,
                    'error_code' => 'JSON_ENCODE_ERROR',
                    'message_he' => 'שגיאה בעיבוד התגובה'
                ];
                $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            error_log("Response::json: Outputting JSON, length=" . strlen($json));
            
            // Flush any existing output buffers first
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            echo $json;
            error_log("Response::json: JSON outputted, exiting");
            
            // Force flush
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                flush();
            }
            
            exit(0);
        } catch (Throwable $e) {
            error_log('Response::json() error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה בעיבוד התגובה'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function view(string $template, array $data = []): void
    {
        try {
            extract($data);
            $i18n = $GLOBALS['i18n'] ?? ($this->container ? $this->container->get(\App\Services\I18n::class) : null) ?? null;
            
            // Ensure i18n exists
            if (!$i18n) {
                $i18n = new \App\Services\I18n('he');
            }
            
            // Ensure BASE_URL is defined
            if (!defined('BASE_URL')) {
                define('BASE_URL', '/');
            }
            
            $viewFile = __DIR__ . '/../Views/' . $template . '.php';
            
            // Check if view file exists
            if (!file_exists($viewFile)) {
                echo "Error: View file not found: " . htmlspecialchars($viewFile);
                exit;
            }
            
            // Start output buffering
            ob_start();
            require $viewFile;
            $content = ob_get_clean();
            
            // Wrap in layout if not already wrapped
            if (!isset($no_layout)) {
                $title = $data['title'] ?? 'Schoolist';
                $extra_css = $data['extra_css'] ?? [];
                $extra_js = $data['extra_js'] ?? [];
                require __DIR__ . '/../Views/layout.php';
            } else {
                echo $content;
            }
            exit;
        } catch (Throwable $e) {
            echo "<h1>View Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            error_log("View error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            exit;
        }
    }

    public function redirect(string $url): void
    {
        // If URL is relative, make it absolute using BASE_URL
        if (!preg_match('/^https?:\/\//', $url)) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : '/';
            if ($url[0] === '/') {
                // Absolute path
                $url = rtrim($baseUrl, '/') . $url;
            } else {
                // Relative path
                $url = rtrim($baseUrl, '/') . '/' . $url;
            }
        }
        header("Location: $url");
        exit;
    }
}

