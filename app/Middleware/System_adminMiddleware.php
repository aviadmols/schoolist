<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container;

class System_adminMiddleware
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(array $params): bool
    {
        error_log("System_adminMiddleware: Checking user permissions");
        $user = $this->container->get('user');
        
        if (!$user) {
            error_log("System_adminMiddleware: User not found in container");
            $response = $this->container->get(\App\Core\Response::class);
            if ($response) {
                $response->json([
                    'ok' => false,
                    'error_code' => 'FORBIDDEN',
                    'message_he' => 'נדרשת הרשאת מנהל מערכת'
                ], 403);
            }
            return false;
        }
        
        error_log("System_adminMiddleware: User found: " . $user['email'] . " (role: " . ($user['role'] ?? 'none') . ")");
        
        if ($user['role'] !== 'system_admin') {
            error_log("System_adminMiddleware: User is not system_admin (role: " . ($user['role'] ?? 'none') . ")");
            $response = $this->container->get(\App\Core\Response::class);
            if ($response) {
                $response->json([
                    'ok' => false,
                    'error_code' => 'FORBIDDEN',
                    'message_he' => 'נדרשת הרשאת מנהל מערכת'
                ], 403);
            }
            return false;
        }

        error_log("System_adminMiddleware: User is system_admin, allowing access");
        return true;
    }
}

