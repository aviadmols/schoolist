<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private ?array $jsonCache = null;

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function input(string $key, $default = null)
    {
        $data = array_merge($_GET, $_POST, $this->json());
        return $data[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        try {
            $content = file_get_contents('php://input');
            if (empty($content)) {
                $this->jsonCache = [];
                return [];
            }
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('JSON decode error: ' . json_last_error_msg());
                $this->jsonCache = [];
                return [];
            }
            $this->jsonCache = is_array($data) ? $data : [];
            return $this->jsonCache;
        } catch (Throwable $e) {
            error_log('Request::json() error: ' . $e->getMessage());
            return [];
        }
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $name = strtoupper(str_replace('-', '_', $name));
        $key = 'HTTP_' . $name;
        return $_SERVER[$key] ?? null;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrf(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

