<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Logger;
use Throwable;

/**
 * Service for sending emails via SMTP or PHP mail() fallback.
 */
class EmailService
{
    // Connection Constants
    private const PORT_SSL = 465;
    private const PORT_TLS = 587;
    private const DEFAULT_TIMEOUT = 15;

    // Default Sender Info
    private const DEFAULT_FROM_EMAIL = 'noreply@schoolist.co.il';
    private const DEFAULT_FROM_NAME = 'Schoolist';

    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $smtpFrom;
    private string $smtpFromName;
    private bool $useSmtp;
    private string $lastError = '';

    /**
     * Initialize service settings from system constants.
     */
    public function __construct()
    {
        $this->smtpHost = defined('SMTP_HOST') ? (string)SMTP_HOST : '';
        $this->smtpPort = defined('SMTP_PORT') ? (int)SMTP_PORT : self::PORT_TLS;
        $this->smtpUser = defined('SMTP_USER') ? (string)SMTP_USER : '';
        $this->smtpPass = defined('SMTP_PASS') ? (string)SMTP_PASS : '';
        $this->smtpFrom = defined('SMTP_FROM') ? (string)SMTP_FROM : self::DEFAULT_FROM_EMAIL;
        $this->smtpFromName = defined('SMTP_FROM_NAME') ? (string)SMTP_FROM_NAME : self::DEFAULT_FROM_NAME;
        $this->useSmtp = defined('SMTP_ENABLED') && (bool)SMTP_ENABLED;
    }

    /**
     * Get the last error message.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Sends an OTP verification email.
     * 
     * @param string $to Recipient email.
     * @param string $code The verification code.
     * @return bool Success status.
     */
    public function sendOtp(string $to, string $code): bool
    {
        $subject = 'קוד אימות - Schoolist';
        $body = "
        <html dir='rtl'>
        <body style='font-family: Arial, sans-serif; text-align: right;'>
            <h2>קוד אימות</h2>
            <p>קוד האימות שלך הוא:</p>
            <h1 style='font-size: 32px; letter-spacing: 5px;'>{$code}</h1>
            <p>קוד זה תקף ל-10 דקות.</p>
        </body>
        </html>";

        return $this->send($to, $subject, $body);
    }

    /**
     * Sends an invitation link to a new page admin.
     */
    public function sendInvitationLink(string $to, string $schoolName, string $loginLink): bool
    {
        $subject = 'הזמנה לניהול דף כיתה - Schoolist';
        $body = "
        <html dir='rtl'>
        <body style='font-family: Arial, sans-serif; text-align: right; direction: rtl; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px;'>
                <h2 style='color: #0C4A6E;'>שלום!</h2>
                <p>הוזמנת לנהל את דף הכיתה של <strong>{$schoolName}</strong>.</p>
                <div style='text-align: center; margin: 30px;'>
                    <a href='{$loginLink}' style='background: #0C4A6E; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>התחבר וערוך את הדף</a>
                </div>
            </div>
        </body>
        </html>";

        return $this->send($to, $subject, $body);
    }

    /**
     * Core sending logic with SMTP and mail() support.
     */
    private function send(string $to, string $subject, string $body): bool
    {
        Logger::info("EmailService::send: Starting", ['to' => $to, 'useSmtp' => $this->useSmtp]);
        
        if ($this->useSmtp && !empty($this->smtpHost)) {
            $result = $this->sendViaSmtp($to, $subject, $body);
            if (!$result) {
                Logger::error("EmailService::send: SMTP failed", ['error' => $this->lastError]);
                return false;
            }
            return true;
        }

        return $this->sendViaMail($to, $subject, $body);
    }

    /**
     * Fallback to native PHP mail() function.
     */
    private function sendViaMail(string $to, string $subject, string $body): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=utf-8',
            'From: ' . $this->smtpFromName . ' <' . $this->smtpFrom . '>',
            'X-Mailer: PHP/' . phpversion()
        ];

        $result = @mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$result) {
            $this->lastError = "PHP mail() failed";
            Logger::error("EmailService::sendViaMail: Error", ['to' => $to]);
        }
        return $result;
    }

    /**
     * Detailed SMTP implementation using sockets.
     */
    private function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        try {
            $socket = $this->connect();
            if (!$socket) return false;

            $this->readResponse($socket, '220', "Initial connection");
            
            $this->sendCommand($socket, "EHLO " . $this->smtpHost);
            $this->readResponse($socket, '250', "EHLO");

            if ($this->smtpPort === self::PORT_TLS) {
                $this->startTls($socket);
            }

            $this->authenticate($socket);
            $this->sendMailCommands($socket, $to, $subject, $body);
            
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            return true;

        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            Logger::error("EmailService::SMTP: Exception", ['msg' => $e->getMessage()]);
            return false;
        }
    }

    // --- Private Helper Methods for SMTP ---

    private function connect()
    {
        $host = ($this->smtpPort === self::PORT_SSL) ? "ssl://{$this->smtpHost}" : $this->smtpHost;
        $context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        
        $socket = @stream_socket_client(
            "{$host}:{$this->smtpPort}",
            $errno, $errstr, self::DEFAULT_TIMEOUT,
            STREAM_CLIENT_CONNECT, $context
        );

        if (!$socket) {
            throw new \Exception("Connection failed: $errstr ($errno)");
        }
        return $socket;
    }

    private function sendCommand($socket, string $cmd): void
    {
        fputs($socket, $cmd . "\r\n");
    }

    private function readResponse($socket, string $expected, string $context)
    {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        if (substr($response, 0, strlen($expected)) !== $expected) {
            throw new \Exception("$context failed: " . trim($response));
        }
        return $response;
    }

    private function startTls($socket): void
    {
        $this->sendCommand($socket, "STARTTLS");
        $this->readResponse($socket, '220', "STARTTLS");
        
        $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if (!$crypto) throw new \Exception("TLS encryption failed");

        $this->sendCommand($socket, "EHLO " . $this->smtpHost);
        $this->readResponse($socket, '250', "EHLO after TLS");
    }

    private function authenticate($socket): void
    {
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->readResponse($socket, '334', "AUTH LOGIN request");

        $this->sendCommand($socket, base64_encode($this->smtpUser));
        $this->readResponse($socket, '334', "Username");

        $this->sendCommand($socket, base64_encode($this->smtpPass));
        $this->readResponse($socket, '235', "Authentication");
    }

    private function sendMailCommands($socket, $to, $subject, $body): void
    {
        $this->sendCommand($socket, "MAIL FROM: <" . $this->smtpFrom . ">");
        $this->readResponse($socket, '250', "MAIL FROM");

        $this->sendCommand($socket, "RCPT TO: <" . $to . ">");
        $this->readResponse($socket, '250', "RCPT TO");

        $this->sendCommand($socket, "DATA");
        $this->readResponse($socket, '354', "DATA command");

        $msg = "From: " . $this->smtpFromName . " <" . $this->smtpFrom . ">\r\n";
        $msg .= "To: <" . $to . ">\r\n";
        $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $msg .= $body . "\r\n.\r\n";

        $this->sendCommand($socket, $msg);
        $this->readResponse($socket, '250', "Message delivery");
    }
}
