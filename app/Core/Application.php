<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Container;
use App\Services\Database;
use App\Services\I18n;
use App\Services\Logger;
use App\Services\EmailService;
use App\Services\SmsService;
use App\Services\RateLimiter;
use Throwable;

/**
 * Main Application kernel.
 * Responsible for service registration, routing, and dispatching.
 */
class Application
{
    private Router $router;
    private Container $container;
    private ?Database $db = null;

    /**
     * Initialize the application kernel.
     */
    public function __construct()
    {
        try {
            $this->container = new Container();
            $this->router = new Router($this->container);
            $this->registerCoreServices();
            $this->registerRoutes();
        } catch (Throwable $e) {
            Logger::error('Application construction failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Main entry point to run the application.
     */
    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Registers all core services into the dependency container.
     */
    private function registerCoreServices(): void
    {
        // 1. Database
        if (defined('DB_HOST')) {
            $this->db = new Database(
                DB_HOST,
                DB_NAME,
                DB_USER,
                DB_PASS,
                defined('DB_PREFIX') ? DB_PREFIX : 'sl_',
                (int)(defined('DB_PORT') ? DB_PORT : 3306)
            );
            $this->container->set(Database::class, $this->db);
        }

        // 2. Localization
        $this->container->set(I18n::class, new I18n($_SESSION['lang'] ?? 'he'));

        // 3. Request / Response
        $request = new Request();
        $this->container->set(Request::class, $request);

        $response = new Response();
        $response->setContainer($this->container);
        $this->container->set(Response::class, $response);

        // 4. Application Services
        $this->container->set(EmailService::class, new EmailService());
        $this->container->set(SmsService::class, new SmsService());
        $this->container->set(RateLimiter::class, new RateLimiter());
    }

    /**
     * Registers all application routes and their handlers.
     */
    private function registerRoutes(): void
    {
        // --- Public Routes ---
        $this->router->get('/', 'PublicController@index');
        $this->router->get('/c/{pageId}', 'PublicController@page');
        $this->router->get('/q/{number}', 'QController@handle');
        $this->router->post('/api/q/activate', 'QController@activate');
        $this->router->get('/link/{number}', 'LinkController@handle');
        $this->router->post('/api/link/activate', 'LinkController@activate');
        $this->router->get('/api/weather/tel-aviv', 'PublicController@getWeather');

        // --- Authentication Routes ---
        $this->router->get('/login', 'AuthController@showLogin');
        $this->router->get('/login/{code}', 'AuthController@loginWithCode');
        $this->router->get('/admin/master-login', 'AuthController@showAdminMasterLogin');
        $this->router->post('/api/auth/request-otp', 'AuthController@requestOtp');
        $this->router->post('/api/auth/login-with-code', 'AuthController@loginWithCodePost');
        $this->router->post('/api/auth/register-with-code', 'AuthController@registerWithCode');
        $this->router->get('/verify', 'AuthController@showVerify');
        $this->router->post('/api/auth/verify-otp', 'AuthController@verifyOtp');
        $this->router->post('/api/auth/admin-master-login', 'AuthController@adminMasterLogin');
        $this->router->post('/api/auth/refresh', 'AuthController@refresh');
        $this->router->post('/api/auth/logout', 'AuthController@logout');
        $this->router->get('/api/me', 'AuthController@me');
        $this->router->get('/redeem', 'AuthController@showRedeem', ['auth']);
        $this->router->post('/api/auth/redeem-invitation', 'AuthController@redeemInvitation', ['auth']);

        // --- Dashboard & Management ---
        $this->router->get('/dashboard', 'DashboardController@index', ['auth']);

        // Block Management
        $this->router->get('/api/pages/{pageId}', 'EditorController@getPage', ['auth', 'page_admin']);
        $this->router->get('/api/pages/{pageId}/blocks/{blockId}', 'EditorController@getBlock', ['auth', 'page_admin']);
        $this->router->put('/api/pages/{pageId}/blocks/{blockId}', 'EditorController@updateBlock', ['auth', 'page_admin']);
        $this->router->delete('/api/pages/{pageId}/blocks/{blockId}', 'EditorController@deleteBlock', ['auth', 'page_admin']);
        $this->router->post('/api/pages/{pageId}/blocks/reorder', 'EditorController@reorderBlocks', ['auth', 'page_admin']);
        
        // Content Management
        $this->router->post('/api/pages/{pageId}/announcements', 'EditorController@createAnnouncement', ['auth', 'page_admin']);
        $this->router->put('/api/pages/{pageId}/announcements/{id}', 'EditorController@updateAnnouncement', ['auth', 'page_admin']);
        $this->router->delete('/api/pages/{pageId}/announcements/{id}', 'EditorController@deleteAnnouncement', ['auth', 'page_admin']);
        $this->router->post('/api/pages/{pageId}/events', 'EditorController@createEvent', ['auth', 'page_admin']);
        $this->router->put('/api/pages/{pageId}/events/{id}', 'EditorController@updateEvent', ['auth', 'page_admin']);
        $this->router->delete('/api/pages/{pageId}/events/{id}', 'EditorController@deleteEvent', ['auth', 'page_admin']);
        $this->router->post('/api/pages/{pageId}/homework', 'EditorController@createHomework', ['auth', 'page_admin']);
        $this->router->put('/api/pages/{pageId}/homework/{id}', 'EditorController@updateHomework', ['auth', 'page_admin']);
        $this->router->delete('/api/pages/{pageId}/homework/{id}', 'EditorController@deleteHomework', ['auth', 'page_admin']);
        
        // AI Integration
        $this->router->post('/api/ai/extract-schedule', 'AIController@extractSchedule', ['auth', 'page_admin']);
        $this->router->post('/api/ai/extract-contacts', 'AIController@extractContacts', ['auth', 'page_admin']);
        $this->router->post('/api/ai/analyze-quick-add', 'AIController@analyzeQuickAdd', ['auth', 'page_admin']);

        // --- System Admin Routes ---
        $this->router->get('/admin', 'AdminController@index', ['auth', 'system_admin']);
        $this->router->get('/admin/logs', 'AdminController@logs', ['auth', 'system_admin']);
        
        // User Management
        $this->router->get('/admin/users', 'AdminController@users', ['auth', 'system_admin']);
        $this->router->get('/api/admin/users', 'AdminController@listUsers', ['auth', 'system_admin']);
        $this->router->post('/api/admin/users/save', 'AdminController@saveUser', ['auth', 'system_admin']);
        $this->router->get('/api/admin/users/search', 'AdminController@searchUsers', ['auth', 'system_admin']);

        // Invitation Management
        $this->router->get('/admin/invitations', 'AdminController@invitations', ['auth', 'system_admin']);
        $this->router->get('/api/admin/invitations', 'AdminController@listInvitations', ['auth', 'system_admin']);
        $this->router->post('/api/admin/invitations', 'AdminController@createInvitation', ['auth', 'system_admin']);
        $this->router->put('/api/admin/invitations/{id}', 'AdminController@updateInvitation', ['auth', 'system_admin']);

        // Page Management (System Admin)
        $this->router->get('/admin/pages', 'AdminController@pages', ['auth', 'system_admin']);
        $this->router->get('/api/admin/pages', 'AdminController@listPages', ['auth', 'system_admin']);
        $this->router->post('/api/admin/pages', 'AdminController@createPage', ['auth', 'system_admin']);
        $this->router->delete('/api/admin/pages/{id}', 'AdminController@deletePage', ['auth', 'system_admin']);

        // Q Activations
        $this->router->get('/admin/q-activations', 'AdminController@qActivations', ['auth', 'system_admin']);
        $this->router->get('/api/admin/q-activations', 'AdminController@listQActivations', ['auth', 'system_admin']);
        
        // SMS & AI Settings
        $this->router->get('/admin/sms-settings', 'AdminController@smsSettings', ['auth', 'system_admin']);
        $this->router->post('/api/admin/sms-settings', 'AdminController@updateSmsSettings', ['auth', 'system_admin']);
        $this->router->get('/admin/ai-settings', 'AdminController@aiSettings', ['auth', 'system_admin']);
        $this->router->get('/api/admin/ai-settings', 'AdminController@getAiSettings', ['auth', 'system_admin']);
        $this->router->post('/api/admin/ai-settings', 'AdminController@updateAiSettings', ['auth', 'system_admin']);
        $this->router->post('/api/admin/ai-test', 'AdminController@testAiConnection', ['auth', 'system_admin']);

        // Setup Wizard
        $this->router->get('/setup', 'SetupController@index');
        $this->router->post('/setup/step/{step}', 'SetupController@processStep');
    }

    /**
     * Global exception handler for the application.
     */
    private function handleException(Throwable $e): void
    {
        Logger::error('Application Exception', [
            'msg' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
            
        if ($isApi) {
                http_response_code(500);
                echo json_encode([
                    'ok' => false,
                    'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה פנימית במערכת'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
            echo "<h1>System Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
