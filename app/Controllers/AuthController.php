<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Repositories\UserRepository;
use App\Repositories\OtpRepository;
use App\Repositories\AuthTokenRepository;
use App\Repositories\InvitationRepository;
use App\Services\EmailService;
use App\Services\SmsService;
use App\Services\RateLimiter;
use App\Services\Logger;
use Throwable;

/**
 * Controller handling user authentication, OTP requests, and registration.
 */
class AuthController extends BaseController
{
    // View paths
    private const VIEW_LOGIN = 'auth/login';
    private const VIEW_VERIFY = 'auth/verify';
    private const VIEW_REGISTER = 'auth/register';
    private const VIEW_REDEEM = 'auth/redeem';

    // Redirect paths
    private const REDIRECT_ADMIN = '/admin';
    private const REDIRECT_DASHBOARD = '/dashboard';
    private const REDIRECT_LOGIN = '/login';

    /**
     * Renders the login page. Redirects if user is already authenticated.
     * 
     * @return void
     */
    public function showLogin(): void
    {
        if ($this->db) {
            $auth = $this->getAuthService();
            $user = $auth->getUser();
            
            if ($user) {
                $this->redirectBasedOnRole($user);
                return;
            }
        }
        
        $this->response->view(self::VIEW_LOGIN);
    }

    /**
     * Processes an OTP request for a given email or phone number.
     * 
     * @return void
     */
    public function requestOtp(): void
    {
        try {
            $data = $this->request->json();
            $identifier = trim($data['email'] ?? $data['identifier'] ?? '');
            $qNumber = trim((string)($data['q'] ?? ''));

            if (empty($identifier)) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'INVALID_INPUT',
                    'message_he' => 'נא להזין אימייל או מספר טלפון'
                ], 400);
                return;
            }

            if (!$this->db) {
                Logger::error("AuthController::requestOtp: Database not available");
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'DB_ERROR',
                    'message_he' => 'שגיאת מסד נתונים'
                ], 500);
                return;
            }

            $auth = $this->getAuthService();
            $result = $auth->requestOtp($identifier, $this->request->ip());
            
            if ($result['ok']) {
                $redirect = '/verify?email=' . urlencode($identifier);
                if ($qNumber !== '') {
                    $redirect .= '&q=' . urlencode($qNumber);
                }
                $this->response->json([
                    'ok' => true,
                    'requires_otp' => true,
                    'message_he' => $result['message_he'],
                    'redirect' => $redirect
                ], 200);
            } else {
                $this->response->json($result, 400);
            }
        } catch (Throwable $e) {
            Logger::error('AuthController::requestOtp error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->response->json([
                'ok' => false,
                'error_code' => 'INTERNAL_ERROR',
                'message_he' => 'שגיאה פנימית: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handles initial entry via an invitation code link.
     * 
     * @param array $params Contains the invitation code.
     * @return void
     */
    public function loginWithCode(array $params): void
    {
        $code = strtoupper(trim($params['code'] ?? ''));
        $email = $_GET['email'] ?? '';
        
        if (empty($code)) {
            $this->response->redirect(self::REDIRECT_LOGIN);
            return;
        }
        
        $invitationRepo = new InvitationRepository($this->db);
        $invitation = $invitationRepo->findByCode($code);
        
        if (!$invitation || $invitation['status'] !== 'active') {
            $this->response->view(self::VIEW_LOGIN, [
                'invitation_code' => $code,
                'email' => $email,
                'error' => $invitation ? 'קוד הזמנה כבר שומש או הושבת' : 'קוד הזמנה לא תקין'
            ]);
            return;
        }
        
        $view = empty($invitation['child_name']) ? self::VIEW_REGISTER : self::VIEW_LOGIN;
        $this->response->view($view, [
            'invitation_code' => $code,
            'email' => $email
        ]);
    }

    /**
     * Processes registration data for a new invited user.
     * 
     * @return void
     */
    public function registerWithCode(): void
    {
        try {
            $data = $this->request->json();
            $code = strtoupper(trim($data['code'] ?? ''));
            $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
            
            if (!$this->validateRegistrationData($data)) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'INVALID_INPUT',
                    'message_he' => 'נא למלא את כל השדות'
                ], 400);
                return;
            }

            $invitationRepo = new InvitationRepository($this->db);
            $invitation = $invitationRepo->findByCode($code);

            if (!$invitation || $invitation['status'] !== 'active') {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'INVALID_CODE',
                    'message_he' => 'קוד הזמנה לא תקין או כבר שומש'
                ], 400);
                return;
            }

            if (strtolower($invitation['admin_email']) !== strtolower($email)) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'EMAIL_MISMATCH',
                    'message_he' => 'קוד הזמנה זה מיועד לאימייל אחר'
                ], 400);
                return;
            }

            $invitationRepo->update($invitation['id'], [
                'child_name' => $data['child_name'],
                'parent1_name' => $data['parent1_name'],
                'parent1_role' => $data['parent1_role'],
                'parent1_phone' => $data['parent1_phone'],
                'parent2_name' => $data['parent2_name'],
                'parent2_role' => $data['parent2_role'],
                'parent2_phone' => $data['parent2_phone'],
                'child_birth_date' => $data['child_birth_date']
            ]);

            $this->completeRegistrationAndLogin($invitationRepo->findByCode($code), $email);
        } catch (Throwable $e) {
            Logger::error('AuthController::registerWithCode error', ['message' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה פנימית'], 500);
        }
    }

    /**
     * Renders the OTP verification page.
     * 
     * @return void
     */
    public function showVerify(): void
    {
        $this->response->view(self::VIEW_VERIFY, [
            'email' => $_GET['email'] ?? ''
        ]);
    }

    /**
     * Verifies the submitted OTP code.
     * 
     * @return void
     */
    public function verifyOtp(): void
    {
        $data = $this->request->json();
        $identifier = trim($data['email'] ?? $data['identifier'] ?? '');
        $code = $data['code'] ?? '';

        if (empty($identifier) || empty($code)) {
            $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
            return;
        }

        $auth = $this->getAuthService();
        $result = $auth->verifyOtp($identifier, $code, $this->request->ip(), $this->request->userAgent());

        if ($result['ok']) {
            $userRepo = new UserRepository($this->db);
            $user = $userRepo->findByEmail($identifier);
            
            if ($user && $user['role'] === 'page_admin') {
                $pageAdmins = $this->db->fetchAll("SELECT page_id FROM {$this->db->table('page_admins')} WHERE user_id = ?", [$user['id']]);
                if (empty($pageAdmins)) {
                    $this->response->json([
                        'ok' => true,
                        'needs_redeem' => true,
                        'token' => $result['token'] ?? null,
                        'redirect' => '/redeem'
                    ]);
                    return;
                }
            }
        }

        $this->response->json($result, $result['ok'] ? 200 : 400);
    }

    /**
     * Logs the current user out and destroys the session.
     * 
     * @return void
     */
    public function logout(): void
    {
        if ($this->db) {
            $user = $this->container->get('user');
            if ($user) {
                (new AuthTokenRepository($this->db))->revoke($user['id']);
            }
        }

        $auth = $this->getAuthService();
        $auth->logout();

        $this->response->json(['ok' => true, 'message_he' => 'התנתקת בהצלחה']);
    }

    /**
     * Handles login via invitation code (POST).
     */
    public function loginWithCodePost(): void
    {
        try {
            $data = $this->request->json();
            $code = strtoupper(trim($data['code'] ?? ''));
            $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);

            if (empty($code) || empty($email)) {
                $this->response->json(['ok' => false, 'message_he' => 'נא למלא את כל השדות'], 400);
                return;
            }

            $invitationRepo = new InvitationRepository($this->db);
            $invitation = $invitationRepo->findByCode($code);

            if (!$invitation || $invitation['status'] !== 'active') {
                $this->response->json(['ok' => false, 'message_he' => 'קוד הזמנה לא תקין או כבר שומש'], 400);
                return;
            }

            // Check if registration is required
            if (empty($invitation['child_name'])) {
                $this->response->json([
                    'ok' => false,
                    'error_code' => 'REGISTRATION_REQUIRED',
                    'message_he' => 'נדרשת הרשמה',
                    'redirect' => '/login/' . $code . '?email=' . urlencode($email)
                ], 400);
                return;
            }

            $this->completeRegistrationAndLogin($invitation, $email);
        } catch (Throwable $e) {
            Logger::error('AuthController::loginWithCodePost error', ['msg' => $e->getMessage()]);
            $this->response->json(['ok' => false, 'message_he' => 'שגיאה פנימית'], 500);
        }
    }

    /**
     * Refreshes the authentication token.
     */
    public function refresh(): void
    {
        $data = $this->request->json();
        $token = $data['token'] ?? '';

        if (empty($token)) {
            $this->response->json(['ok' => false, 'message_he' => 'אסימון לא תקין'], 400);
            return;
        }

        $auth = $this->getAuthService();
        $user = $auth->validateToken($token);

        if (!$user) {
            $this->response->json(['ok' => false, 'message_he' => 'אסימון לא תקין או שפג תוקפו'], 401);
            return;
        }

        $this->response->json([
            'ok' => true,
            'user' => ['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]
        ]);
    }

    /**
     * Returns the currently authenticated user's data.
     */
    public function me(): void
    {
        $user = $this->container->get('user');
        if (!$user) {
            $this->response->json(['ok' => false, 'message_he' => 'נדרשת התחברות'], 401);
            return;
        }

        $this->response->json(['ok' => true, 'user' => $user]);
    }

    /**
     * Renders the invitation redemption page.
     */
    public function showRedeem(): void
    {
        $this->response->view(self::VIEW_REDEEM);
    }

    /**
     * Redeems an invitation code for an existing user.
     */
    public function redeemInvitation(): void
    {
        if (!$this->validateCsrf()) return;

        $user = $this->container->get('user');
        if (!$user) {
            $this->response->json(['ok' => false, 'message_he' => 'נדרשת התחברות'], 401);
            return;
        }

        $data = $this->request->json();
        $code = strtoupper(trim($data['code'] ?? ''));

        if (empty($code)) {
            $this->response->json(['ok' => false, 'message_he' => 'נא להזין קוד הזמנה'], 400);
            return;
        }

        $invitationRepo = new InvitationRepository($this->db);
        $invitation = $invitationRepo->findByCode($code);

        if (!$invitation || $invitation['status'] !== 'active') {
            $this->response->json(['ok' => false, 'message_he' => 'קוד הזמנה לא תקין או כבר שומש'], 400);
            return;
        }

        $this->completeRegistrationAndLogin($invitation, $user['email']);
    }

    // --- Private Helper Methods ---

    /**
     * Instantiates and returns the Auth service.
     */
    private function getAuthService(): Auth
    {
        $userRepo = new UserRepository($this->db);
        $otpRepo = new OtpRepository($this->db);
        $tokenRepo = new AuthTokenRepository($this->db);
        $emailService = $this->container->get(EmailService::class);
        $smsService = $this->container->get(SmsService::class);
        $rateLimiter = $this->container->get(RateLimiter::class);
        
        return new Auth($userRepo, $otpRepo, $tokenRepo, $emailService, $smsService, $rateLimiter);
    }

    /**
     * Redirects the user to the correct page based on their system role.
     */
    private function redirectBasedOnRole(array $user): void
    {
        if ($user['role'] === 'system_admin') {
            $this->response->redirect(self::REDIRECT_ADMIN);
            return;
        }

        $pages = $this->db->fetchAll(
            "SELECT p.unique_numeric_id FROM {$this->db->table('pages')} p
             INNER JOIN {$this->db->table('page_admins')} pa ON p.id = pa.page_id
             WHERE pa.user_id = ?",
            [$user['id']]
        );
        
        if (count($pages) === 1) {
            $this->response->redirect('/c/' . $pages[0]['unique_numeric_id']);
        } else {
            $this->response->redirect(self::REDIRECT_DASHBOARD);
        }
    }

    /**
     * Validates that all required registration fields are present.
     */
    private function validateRegistrationData(array $data): bool
    {
        $required = ['code', 'email', 'child_name', 'parent1_name', 'parent1_role', 'parent1_phone', 'child_birth_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) return false;
        }
        return true;
    }

    /**
     * Completes registration by creating/updating the user and their first page.
     */
    private function completeRegistrationAndLogin(array $invitation, string $email): void
    {
        $userRepo = new UserRepository($this->db);
        $tokenRepo = new AuthTokenRepository($this->db);
        
        $user = $userRepo->findByEmail($email) ?: $userRepo->findById($userRepo->create($email, 'page_admin', 0));
        
        if ($user['role'] !== 'page_admin' || $user['status'] !== 'active') {
            $this->db->query("UPDATE {$this->db->table('users')} SET role = 'page_admin', status = 'active' WHERE id = ?", [$user['id']]);
        }

        // Handle page assignment
        $pageRepo = new \App\Repositories\PageRepository($this->db);
        if ($invitation['used_page_id']) {
            $pageId = $invitation['used_page_id'];
            $this->db->query("INSERT IGNORE INTO {$this->db->table('page_admins')} (page_id, user_id) VALUES (?, ?)", [$pageId, $user['id']]);
        } else {
            $pageId = $pageRepo->create([
                'unique_numeric_id' => $pageRepo->generateUniqueId(),
                'school_name' => $invitation['school_name'],
                'class_title' => $invitation['school_name'] . ' - כיתה',
                'settings_json' => '{}'
            ]);
            $this->db->insert('page_admins', ['page_id' => $pageId, 'user_id' => $user['id']]);
            (new InvitationRepository($this->db))->markUsed($invitation['id'], $user['id'], $pageId);
        }

        // Auth & Session
        $token = bin2hex(random_bytes(32));
        $tokenRepo->create($user['id'], hash('sha256', $token), $this->request->ip(), $this->request->userAgent());
        $userRepo->updateLastLogin($user['id']);
        $_SESSION['user_id'] = $user['id'];

        $page = $pageRepo->findById($pageId);
        $this->response->json([
            'ok' => true,
            'token' => $token,
            'message_he' => 'נרשמת בהצלחה!',
            'redirect' => $page ? '/c/' . $page['unique_numeric_id'] : self::REDIRECT_DASHBOARD
        ]);
    }
}
