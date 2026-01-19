<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\OtpRepository;
use App\Repositories\AuthTokenRepository;
use App\Services\EmailService;
use App\Services\SmsService;
use App\Services\RateLimiter;
use App\Services\Logger;
use Throwable;

/**
 * Core Authentication Service handling OTP requests, verification and session management.
 */
class Auth
{
    // OTP Configuration
    private const OTP_LENGTH = 6;
    private const MAX_VERIFY_ATTEMPTS = 5;
    private const RATE_LIMIT_OTP_ID = 3;      // Max 3 requests per identifier
    private const RATE_LIMIT_OTP_IP = 10;     // Max 10 requests per IP
    private const RATE_LIMIT_WINDOW = 300;    // 5 minutes window

    // User Roles
    public const ROLE_SYSTEM_ADMIN = 'system_admin';
    public const ROLE_PAGE_ADMIN = 'page_admin';
    public const ROLE_PARENT = 'parent';

    private UserRepository $userRepo;
    private OtpRepository $otpRepo;
    private AuthTokenRepository $tokenRepo;
    private EmailService $email;
    private SmsService $sms;
    private RateLimiter $rateLimiter;

    public function __construct(
        UserRepository $userRepo,
        OtpRepository $otpRepo,
        AuthTokenRepository $tokenRepo,
        EmailService $email,
        SmsService $sms,
        RateLimiter $rateLimiter
    ) {
        $this->userRepo = $userRepo;
        $this->otpRepo = $otpRepo;
        $this->tokenRepo = $tokenRepo;
        $this->email = $email;
        $this->sms = $sms;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Requests a new OTP for a given email or phone number.
     * 
     * @param string $identifier Email or Phone number.
     * @param string $ip User's IP address.
     * @return array Result with 'ok' status and message.
     */
    public function requestOtp(string $identifier, string $ip): array
    {
        try {
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
            $isPhone = $this->isValidPhone($identifier);

            if (!$isEmail && !$isPhone) {
                return ['ok' => false, 'error_code' => 'INVALID_IDENTIFIER', 'message_he' => 'נא להזין כתובת אימייל או מספר טלפון תקין.'];
            }

            if (!$this->checkRateLimits($identifier, $ip)) {
                return ['ok' => false, 'error_code' => 'RATE_LIMIT', 'message_he' => 'יותר מדי בקשות. נסה שוב בעוד כמה דקות.'];
            }

            $code = $this->generateOtp();
            $this->otpRepo->create($identifier, password_hash($code, PASSWORD_BCRYPT), $ip);

            return $this->dispatchOtp($identifier, $code, $isPhone);

        } catch (Throwable $e) {
            Logger::error('Auth::requestOtp: Exception', ['msg' => $e->getMessage()]);
            return ['ok' => false, 'error_code' => 'INTERNAL_ERROR', 'message_he' => 'שגיאה פנימית במערכת.'];
        }
    }

    /**
     * Verifies the OTP code and creates a session/token.
     */
    public function verifyOtp(string $identifier, string $code, string $ip, string $userAgent): array
    {
        // Handle Master Code bypass for admins
        if ($this->isMasterCode($identifier, $code)) {
            return $this->completeLogin($identifier, $ip, $userAgent);
        }

        if (!$this->rateLimiter->check("otp_verify:{$identifier}", self::MAX_VERIFY_ATTEMPTS, self::RATE_LIMIT_WINDOW)) {
            return ['ok' => false, 'error_code' => 'RATE_LIMIT', 'message_he' => 'יותר מדי ניסיונות. נסה שוב מאוחר יותר.'];
        }

        $otp = $this->otpRepo->findValid($identifier);
        if (!$otp || !password_verify($code, $otp['code_hash'])) {
            if ($otp) $this->otpRepo->incrementAttempts($otp['id']);
            return ['ok' => false, 'error_code' => 'INVALID_OTP', 'message_he' => 'קוד אימות לא תקין או שפג תוקפו.'];
        }

        $this->otpRepo->markUsed($otp['id']);
        return $this->completeLogin($identifier, $ip, $userAgent);
    }

    /**
     * Gets the currently authenticated user from session or Bearer token.
     */
    public function getUser(): ?array
    {
        // Check Session
        if (isset($_SESSION['user_id'])) {
            return $this->userRepo->findById($_SESSION['user_id']);
        }

        // Check Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $user = $this->validateToken($matches[1]);
            if ($user) {
                $this->setSession($user);
                return $user;
            }
        }

        return null;
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_role']);
        session_destroy();
    }

    // --- Private Helper Methods ---

    private function isValidPhone(string $identifier): bool
    {
        return preg_match('/^[0-9+]{8,15}$/', preg_replace('/\D/', '', $identifier)) === 1;
    }

    private function generateOtp(): string
    {
        return str_pad((string)random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function checkRateLimits(string $id, string $ip): bool
    {
        // Skip for system admin
        if (defined('ADMIN_EMAIL') && strtolower($id) === strtolower(ADMIN_EMAIL)) return true;
        if (defined('ADMIN_PHONE') && preg_replace('/\D/', '', $id) === preg_replace('/\D/', '', ADMIN_PHONE)) return true;

        return $this->rateLimiter->check("otp_req:{$id}", self::RATE_LIMIT_OTP_ID, self::RATE_LIMIT_WINDOW) &&
               $this->rateLimiter->check("otp_ip:{$ip}", self::RATE_LIMIT_OTP_IP, self::RATE_LIMIT_WINDOW);
    }

    private function dispatchOtp(string $to, string $code, bool $isPhone): array
    {
        $type = $isPhone ? 'SMS' : 'Email';
        Logger::info("Auth::dispatchOtp: Sending via $type", ['to' => $to]);

        $sent = $isPhone ? $this->sms->sendOtp($to, $code) : $this->email->sendOtp($to, $code);
        
        if (!$sent) {
            $error = $isPhone ? $this->sms->getLastError() : $this->email->getLastError();
            Logger::error("Auth::dispatchOtp: Failed", ['type' => $type, 'error' => $error]);
            return ['ok' => false, 'message_he' => "נכשלנו בשליחה. שגיאה: $error"];
        }

        return ['ok' => true, 'message_he' => "קוד אימות נשלח ל" . ($isPhone ? 'טלפון' : 'אימייל') . " שלך."];
    }

    private function completeLogin(string $identifier, string $ip, string $userAgent): array
    {
        $user = $this->userRepo->findByEmail($identifier);
        if (!$user) {
            $role = $this->isAdminIdentifier($identifier) ? self::ROLE_SYSTEM_ADMIN : self::ROLE_PAGE_ADMIN;
            $userId = $this->userRepo->create([
                'email' => $identifier,
                'role' => $role
            ]);
            $user = $this->userRepo->findById($userId);
        }

        $token = bin2hex(random_bytes(32));
        $this->tokenRepo->create($user['id'], hash('sha256', $token), $ip, $userAgent);
        $this->userRepo->updateLastLogin($user['id']);
        $this->setSession($user);

        return ['ok' => true, 'token' => $token, 'user' => $user];
    }

    private function setSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
    }

    private function isAdminIdentifier(string $id): bool
    {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : '';
        $adminPhone = defined('ADMIN_PHONE') ? ADMIN_PHONE : '';
        return (strtolower($id) === strtolower($adminEmail)) || 
               (preg_replace('/\D/', '', $id) === preg_replace('/\D/', '', $adminPhone));
    }

    private function isMasterCode(string $id, string $code): bool
    {
        $master = defined('ADMIN_MASTER_CODE') ? ADMIN_MASTER_CODE : '';
        return $this->isAdminIdentifier($id) && !empty($master) && $code === $master;
    }

    private function validateToken(string $token): ?array
    {
        $tokenData = $this->tokenRepo->findValid(hash('sha256', $token));
        if (!$tokenData) return null;

        $user = $this->userRepo->findById($tokenData['user_id']);
        if (!$user || $user['status'] !== 'active') return null;

        $this->tokenRepo->updateLastUsed($tokenData['id']);
        return $user;
    }
}
