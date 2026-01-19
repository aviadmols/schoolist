<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Container;
use App\Services\Auth;
use App\Services\Database;
use App\Repositories\UserRepository;
use App\Repositories\OtpRepository;
use App\Repositories\AuthTokenRepository;
use App\Services\EmailService;
use App\Services\RateLimiter;

class AuthMiddleware
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(array $params): bool
    {
        error_log("AuthMiddleware: Starting authentication check");
        
        $db = $this->container->get(\App\Services\Database::class);
        if (!$db) {
            error_log("AuthMiddleware: Database not available");
            http_response_code(500);
            echo json_encode(['ok' => false, 'error_code' => 'DB_ERROR', 'message_he' => 'שגיאת מסד נתונים']);
            return false;
        }

        $userRepo = new \App\Repositories\UserRepository($db);
        $otpRepo = new \App\Repositories\OtpRepository($db);
        $tokenRepo = new \App\Repositories\AuthTokenRepository($db);
        
        $email = $this->container->get(\App\Services\EmailService::class);
        $sms = $this->container->get(\App\Services\SmsService::class);
        $rateLimiter = $this->container->get(\App\Services\RateLimiter::class);

        $auth = new Auth($userRepo, $otpRepo, $tokenRepo, $email, $sms, $rateLimiter);
        $user = $auth->getUser();

        if (!$user) {
            error_log("AuthMiddleware: User not authenticated");
            $response = $this->container->get(\App\Core\Response::class);
            if ($response) {
                $response->json([
                    'ok' => false,
                    'error_code' => 'UNAUTHORIZED',
                    'message_he' => 'נדרשת התחברות'
                ], 401);
            }
            return false;
        }

        error_log("AuthMiddleware: User authenticated: " . $user['email'] . " (role: " . $user['role'] . ")");
        $this->container->set(Auth::class, $auth);
        $this->container->set('user', $user);
        return true;
    }
}

