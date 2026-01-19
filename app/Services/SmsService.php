<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\Logger;
use Throwable;

/**
 * Service for sending SMS messages via 019sms provider.
 */
class SmsService
{
    // API Configuration
    private const API_URL = 'https://019sms.co.il/api';
    private const DEFAULT_SOURCE = 'Schoolist';
    private const DEFAULT_USERNAME = 'Aviadmols';
    private const TIMEOUT = 15;

    // Response Statuses
    private const STATUS_SUCCESS = '0';

    private string $token;
    private string $lastError = '';

    /**
     * Initialize the service with the API token from configuration.
     */
    public function __construct()
    {
        $this->token = defined('SMS_019_TOKEN') ? SMS_019_TOKEN : '';
    }

    /**
     * Get the last error message that occurred during an operation.
     * 
     * @return string The error message.
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Sends a One-Time Password (OTP) code to a phone number.
     * 
     * @param string $to Recipient phone number.
     * @param string $code The verification code.
     * @return bool True if sent successfully, false otherwise.
     */
    public function sendOtp(string $to, string $code): bool
    {
        Logger::info("SmsService::sendOtp: Starting process", ['to' => $to]);
        
        $to = $this->formatPhoneNumber($to);
        $message = "קוד האימות שלך ל-Schoolist הוא: {$code}";
        
        if (empty($this->token)) {
            $this->lastError = "019sms Token missing in config";
            Logger::error("SmsService::sendOtp: " . $this->lastError);
            return false;
        }

        return $this->send($to, $message);
    }

    /**
     * Formats a phone number to digits only.
     * 
     * @param string $number Raw phone number.
     * @return string Formatted phone number.
     */
    private function formatPhoneNumber(string $number): string
    {
        return preg_replace('/\D/', '', $number);
    }

    /**
     * Internal method to execute the SMS sending via CURL.
     * 
     * @param string $to Recipient phone number.
     * @param string $message The message content.
     * @return bool Success status.
     */
    private function send(string $to, string $message): bool
    {
        $source = defined('SMS_SOURCE') ? SMS_SOURCE : self::DEFAULT_SOURCE;
        $username = defined('SMS_USERNAME') ? SMS_USERNAME : self::DEFAULT_USERNAME;

        // Construct the nested payload structure exactly as requested
        $payload = [
            'sms' => [
                'user' => [
                    'username' => $username
                ],
                'source' => $source,
                'destinations' => [
                    'phone' => [
                        ['_' => $to]
                    ]
                ],
                'message' => $message,
                'includes_international' => '0'
            ]
        ];

        try {
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

            // Debug log: full request structure (headers + body) WITHOUT לחשוף את הטוקן המלא
            $maskedToken = substr($this->token, 0, 6) . '***';
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $maskedToken,
            ];

            Logger::info("SmsService::send: Requesting 019 API with nested structure", [
                'to' => $to,
                'username' => $username,
                'url' => self::API_URL,
                'headers' => $headers,
                'payload' => $payload,
                'json_payload' => $jsonPayload,
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => self::API_URL,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->token
                ],
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $this->lastError = "cURL Error: " . $curlError;
                Logger::error("SmsService::send: " . $this->lastError);
                return false;
            }

            $success = $this->processResponse($response, $httpCode, $to);
            
            if (!$success) {
                Logger::error('SmsService::send API failure details', [
                    'username' => $username,
                    'http_code' => $httpCode,
                    'response' => $response,
                    'payload_preview' => str_replace($this->token, '***', $jsonPayload)
                ]);
            }

            return $success;

        } catch (Throwable $e) {
            $this->lastError = "Exception: " . $e->getMessage();
            Logger::error("SmsService::send: Exception caught", [
                'message' => $e->getMessage(),
                'file' => $e->getFile()
            ]);
            return false;
        }
    }

    /**
     * Parses and validates the API response.
     * 
     * @param string|bool $response Raw response from API.
     * @param int $httpCode HTTP status code.
     * @param string $to Recipient for logging.
     * @return bool Success status.
     */
    private function processResponse($response, int $httpCode, string $to): bool
    {
        if (empty($response)) {
            $this->lastError = "Empty response from SMS API (HTTP {$httpCode})";
            Logger::error("SmsService::send: " . $this->lastError);
            return false;
        }

        $result = json_decode((string)$response, true);
        
        // Handle non-JSON responses (some older 019 APIs return plain text)
        if (json_last_error() !== JSON_ERROR_NONE) {
            $trimmed = trim((string)$response);
            if ($trimmed === self::STATUS_SUCCESS || stripos($trimmed, 'success') !== false) {
                Logger::info("SmsService::send: Success (plain text response)", ['to' => $to]);
                return true;
            }
            $this->lastError = "Invalid API response format (HTTP $httpCode)";
            Logger::error("SmsService::send: " . $this->lastError, ['response' => substr($trimmed, 0, 100)]);
            return false;
        }

        // Check success in various possible JSON structures
        // The API might wrap the status in 'sms' or return it at the root
        $status = (string)($result['status'] ?? $result['sms']['status'] ?? '-1');
        
        if ($status === self::STATUS_SUCCESS) {
            Logger::info("SmsService::send: Message delivered successfully", ['to' => $to]);
            return true;
        }

        $this->lastError = "API Error: " . ($result['message'] ?? $result['sms']['message'] ?? 'Unknown API error');
        Logger::error("SmsService::send: Failed", ['error' => $this->lastError]);
        return false;
    }
}
