<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Services\Database;
use App\Services\I18n;
use App\Services\Logger;
use Throwable;

abstract class BaseController
{
    protected Container $container;
    protected Request $request;
    protected Response $response;
    protected ?Database $db = null;
    protected I18n $i18n;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->request = $container->get(Request::class) ?? new Request();
        $this->response = $container->get(Response::class) ?? new Response();
        $this->db = $container->get(Database::class);
        $this->i18n = $container->get(I18n::class) ?? new I18n('he');
        
        if (!$this->request) {
            error_log("CRITICAL: Request is null in BaseController constructor!");
        }
    }

    protected function validateCsrf(): bool
    {
        try {
            // Try to get token from various sources
            $token = $this->request->input('csrf_token') 
                  ?? $this->request->header('X-CSRF-Token') 
                  ?? $this->request->header('X-Requested-With'); // Fallback

            if (!$token) {
                Logger::error("CSRF Validation failed: No token provided", [
                    'uri' => $_SERVER['REQUEST_URI'],
                    'method' => $_SERVER['REQUEST_METHOD']
                ]);
                $this->response->json(['ok' => false, 'message_he' => 'אסימון אבטחה (CSRF) חסר'], 403);
                return false;
            }
            
            $isValid = $this->request->validateCsrf((string)$token);
            
            if (!$isValid) {
                Logger::error("CSRF Validation failed: Token mismatch", [
                    'provided' => substr((string)$token, 0, 10) . '...',
                    'session_exists' => isset($_SESSION['csrf_token']),
                    'ip' => $this->request->ip()
                ]);
                $this->response->json(['ok' => false, 'message_he' => 'אסימון אבטחה לא תקין. נא לרענן את הדף.'], 403);
                return false;
            }
            return true;
        } catch (Throwable $e) {
            Logger::error("CSRF Error: " . $e->getMessage());
            return false;
        }
    }

    protected function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6>';
        $allowedAttrs = ['href', 'src', 'alt', 'title', 'target'];
        
        $html = strip_tags($html, $allowed);
        
        // Remove dangerous attributes
        $html = preg_replace_callback('/<([^>]+)>/', function($matches) use ($allowedAttrs) {
            $tag = $matches[1];
            $parts = explode(' ', $tag, 2);
            $tagName = $parts[0];
            $attrs = $parts[1] ?? '';
            
            if (empty($attrs)) {
                return "<$tag>";
            }
            
            preg_match_all('/(\w+)="([^"]*)"/', $attrs, $attrMatches, PREG_SET_ORDER);
            $safeAttrs = [];
            foreach ($attrMatches as $attr) {
                if (in_array($attr[1], $allowedAttrs)) {
                    $safeAttrs[] = $attr[1] . '="' . htmlspecialchars($attr[2], ENT_QUOTES, 'UTF-8') . '"';
                }
            }
            
            return '<' . $tagName . (!empty($safeAttrs) ? ' ' . implode(' ', $safeAttrs) : '') . '>';
        }, $html);
        
        return $html;
    }
}

