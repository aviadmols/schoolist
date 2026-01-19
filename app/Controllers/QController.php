<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\QActivationRepository;
use App\Repositories\PageRepository;
use App\Services\Logger;
use Throwable;

/**
 * Controller for handling Q-code activations and redirects.
 */
class QController extends BaseController
{
    private const VIEW_ACTIVATE = 'q/activate';
    private const VIEW_ERROR = 'q/error';

    /**
     * Handles the Q-code entry point. Redirects if active, shows form otherwise.
     * 
     * @param array $params Route parameters containing the Q-number.
     */
    public function handle(array $params): void
    {
        $number = (int)($params['number'] ?? 0);

        if (!$this->db) {
            $this->response->view(self::VIEW_ERROR, ['message' => 'Database not available']);
            return;
        }

        // If user is not authenticated, redirect to login with this Q-number,
        // so that after הזדהות ב-SMS נחזור לקישור /q/{number}
        $user = $this->container->get('user') ?? null;
        if (!$user) {
            // Try to auto-detect user from token/session (same as PublicController)
            try {
                $userRepo = new \App\Repositories\UserRepository($this->db);
                $otpRepo = new \App\Repositories\OtpRepository($this->db);
                $tokenRepo = new \App\Repositories\AuthTokenRepository($this->db);
                $emailService = $this->container->get(\App\Services\EmailService::class);
                $smsService = $this->container->get(\App\Services\SmsService::class);
                $rateLimiter = $this->container->get(\App\Services\RateLimiter::class);
                $auth = new \App\Services\Auth($userRepo, $otpRepo, $tokenRepo, $emailService, $smsService, $rateLimiter);
                $user = $auth->getUser();

                if ($user) {
                    $this->container->set('user', $user);
                }
            } catch (\Throwable $e) {
                Logger::error('QController::handle auth detection failed', ['msg' => $e->getMessage()]);
            }
        }

        if (!$user) {
            $this->response->redirect('/login?q=' . $number);
            return;
        }

        $qRepo = new QActivationRepository($this->db);
        $activation = $qRepo->findByNumber($number);

        if ($activation) {
            // Already activated - Update last used time and redirect to class page
            $qRepo->updateLastUsed($activation['id']);
            $this->response->redirect('/c/' . $activation['page_unique_numeric_id']);
            return;
        }

        // Not activated - Render activation form
        $this->response->view(self::VIEW_ACTIVATE, ['q_number' => $number]);
    }

    /**
     * Processes the activation request for a Q-code.
     */
    public function activate(): void
    {
        if (!$this->validateCsrf()) return;

        try {
            $data = $this->request->json();
            $qNumber = (int)($data['q_number'] ?? 0);
            $pageUniqueId = (int)($data['page_unique_id'] ?? 0);

            if ($qNumber <= 0 || $pageUniqueId <= 0) {
                $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
                return;
            }

            $qRepo = new QActivationRepository($this->db);
            
            // Check if already active
            if ($qRepo->findByNumber($qNumber)) {
                $this->response->json(['ok' => false, 'message_he' => 'מספר זה כבר מופעל'], 400);
                return;
            }

            // Verify target page exists
            $pageRepo = new PageRepository($this->db);
            if (!$pageRepo->findByUniqueId($pageUniqueId)) {
                $this->response->json(['ok' => false, 'message_he' => 'דף לא נמצא'], 404);
                return;
            }

            // Create activation record
            $qRepo->create($qNumber, $pageUniqueId, $this->request->ip());

            $this->response->json([
                'ok' => true,
                'redirect_url' => '/c/' . $pageUniqueId
            ]);

        } catch (Throwable $e) {
            Logger::error("QController::activate failed", ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה פנימית'], 500);
        }
    }
}
