<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container;
use App\Services\Database;
use App\Repositories\UserRepository;

class Page_adminMiddleware
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(array $params): bool
    {
        $user = $this->container->get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error_code' => 'UNAUTHORIZED', 'message_he' => 'נדרשת התחברות']);
            return false;
        }

        // System admin can access everything
        if ($user['role'] === 'system_admin') {
            return true;
        }

        // Check if page admin
        if ($user['role'] !== 'page_admin') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error_code' => 'FORBIDDEN', 'message_he' => 'אין הרשאה']);
            return false;
        }

        // If pageId is in params, verify access
        if (isset($params['pageId'])) {
            $db = $this->container->get(\App\Services\Database::class);
            $userRepo = new UserRepository($db);
            $pageId = (int)$params['pageId'];

            if (!$userRepo->isPageAdmin($user['id'], $pageId)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error_code' => 'FORBIDDEN', 'message_he' => 'אין הרשאה לדף זה']);
                return false;
            }
        }

        return true;
    }
}















