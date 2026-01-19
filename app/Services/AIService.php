<?php
declare(strict_types=1);

namespace App\Services;

/**
 * AI Service
 * 
 * Generic AI service that uses OpenRouter API with HTTP requests (no official PHP SDK available).
 * Configured to use:
 * - Model: Nemotron 3 Nano 30B A3B (free) or other models via configuration
 * - API Key: Set via OPENROUTER_API_KEY constant or uses default
 * - Headers: HTTP-Referer and X-Title for OpenRouter rankings
 */
final class AIService
{
    private const DEFAULT_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL = 'google/gemini-2.0-flash-lite:free'; // Google Gemini 2.0 Flash Lite (Free)
    private const MAX_IMAGE_BYTES = 20971520;
    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    private readonly string $apiKey;
    private readonly string $apiUrl;
    private readonly string $model;
    private readonly bool $debug;
    private readonly bool $forceJson;
    private readonly string $siteUrl;
    private readonly string $siteName;

    public function __construct()
    {
        $this->apiKey = (string) ($this->getConst('OPENROUTER_API_KEY') ?? $this->getConst('OPENAI_API_KEY') ?? '');
        
        // Auto-detect API URL based on key format
        $defaultUrl = self::DEFAULT_API_URL;
        if (str_starts_with($this->apiKey, 'sk-proj-')) {
            $defaultUrl = 'https://api.openai.com/v1/chat/completions';
        }
        
        $this->apiUrl = (string) ($this->getConst('OPENROUTER_API_URL') ?? $this->getConst('OPENAI_API_URL') ?? $defaultUrl);

        // Resolve model in a local variable first to avoid reassigning readonly property
        $model = (string) ($this->getConst('OPENROUTER_MODEL') ?? $this->getConst('OPENAI_MODEL') ?? self::DEFAULT_MODEL);
        // If it's an OpenAI key and we're using the default model (which is OpenRouter specific), switch to gpt-4o-mini
        if (str_starts_with($this->apiKey, 'sk-proj-') && $model === self::DEFAULT_MODEL) {
            $model = 'gpt-4o-mini';
        }
        $this->model = $model;

        $this->debug = (bool) ($this->getConst('OPENAI_DEBUG') ?? true); 
        
        $isNemotron = str_contains(strtolower($this->model), 'nemotron');
        $isGemini = str_contains(strtolower($this->model), 'gemini');
        $forceJsonConst = $this->getConst('OPENAI_FORCE_JSON');
        
        if ($forceJsonConst !== null) {
            $this->forceJson = (bool) $forceJsonConst;
        } else {
            $this->forceJson = !$isNemotron && !$isGemini;
        }
        
        $this->log("Initialized: Model={$this->model}, URL={$this->apiUrl}, ForceJSON=" . ($this->forceJson ? 'Yes' : 'No'));
        $this->siteUrl = (string) ($this->getConst('BASE_URL') ?? 'https://app.schoolist.co.il');
        $this->siteName = 'Schoolist App';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function extractSchedule(string $imagePath): array
    {
        $prompt = (string) ($this->getConst('OPENAI_SCHEDULE_PROMPT') ?? $this->defaultSchedulePrompt());
        // Use specific model for schedule extraction: google/gemini-3-flash-preview
        return $this->processImage($imagePath, $prompt, 'schedule', 'google/gemini-3-flash-preview');
    }

    public function extractContacts(?string $imagePath = null, ?string $text = null): array
    {
        if ($imagePath !== null && $imagePath !== '') {
            $prompt = (string) ($this->getConst('OPENAI_CONTACTS_PROMPT') ?? $this->defaultContactsImagePrompt());
            return $this->processImage($imagePath, $prompt, 'contacts');
        }

        if ($text !== null && trim($text) !== '') {
            $prompt = (string) ($this->getConst('OPENAI_CONTACTS_PROMPT') ?? $this->defaultContactsTextPrompt());
            return $this->processText($text, $prompt, 'contacts');
        }

        return ['ok' => false, 'reason' => 'No input provided'];
    }

    public function extractDocument(string $imagePath): array
    {
        $prompt = (string) ($this->getConst('OPENAI_DOCUMENT_PROMPT') ?? $this->defaultDocumentPrompt());
        return $this->processImage($imagePath, $prompt, 'document');
    }

    public function analyzeQuickAdd(?string $imagePath = null, ?string $text = null): array
    {
        // Get today's date and day of week in Hebrew (Israel timezone)
        $timezone = new \DateTimeZone('Asia/Jerusalem');
        $today = new \DateTime('now', $timezone);
        $todayDate = $today->format('Y-m-d');
        $todayTime = $today->format('H:i');
        $timezoneName = 'Asia/Jerusalem (UTC+2/+3)';
        
        // Map day of week to Hebrew
        $dayMap = [
            0 => 'יום ראשון',
            1 => 'יום שני',
            2 => 'יום שלישי',
            3 => 'יום רביעי',
            4 => 'יום חמישי',
            5 => 'יום שישי',
            6 => 'יום שבת',
        ];
        $todayDayOfWeek = $dayMap[(int)$today->format('w')];
        
        $prompt = $this->defaultQuickAddPrompt($todayDate, $todayDayOfWeek, $timezoneName, $todayTime);
        
        if ($imagePath !== null && $imagePath !== '') {
            return $this->processImage($imagePath, $prompt, 'quick-add');
        }
        
        if ($text !== null && trim($text) !== '') {
            return $this->processText($text, $prompt, 'quick-add');
        }
        
        return ['ok' => false, 'reason' => 'No input provided'];
    }

    private function processImage(string $imagePath, string $prompt, string $type, ?string $customModel = null): array
    {
        if (!is_file($imagePath)) {
            $this->log("Image file not found: {$imagePath}");
            return ['ok' => false, 'reason' => 'קובץ התמונה לא נמצא'];
        }

        $size = (int) filesize($imagePath);
        if ($size <= 0) {
            return ['ok' => false, 'reason' => 'קובץ התמונה לא תקין'];
        }

        if ($size > self::MAX_IMAGE_BYTES) {
            return ['ok' => false, 'reason' => 'התמונה גדולה מדי. נא להעלות תמונה קטנה מ-20MB'];
        }

        $mime = $this->detectMimeType($imagePath);
        if ($mime === null) {
            return ['ok' => false, 'reason' => 'פורמט תמונה לא נתמך'];
        }

        $raw = @file_get_contents($imagePath);
        if ($raw === false || $raw === '') {
            return ['ok' => false, 'reason' => 'לא ניתן לקרוא את התמונה'];
        }

        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($raw);

        $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                ],
            ],
        ];

        return $this->callApi($messages, $type, $customModel);
    }

    private function processText(string $text, string $prompt, string $type): array
    {
        $messages = [
            [
                'role' => 'user',
                'content' => $prompt . "\n\nText:\n" . $text,
            ],
        ];

        return $this->callApi($messages, $type);
    }

    private function callApi(array $messages, string $type, ?string $customModel = null): array
    {
        if ($this->apiKey === '') {
            return ['ok' => false, 'reason' => 'OpenRouter API key not configured'];
        }

        // Use custom model if provided, otherwise use default model
        $modelToUse = $customModel ?? $this->model;
        
        // Check if custom model is Gemini and adjust forceJson accordingly
        $isCustomGemini = $customModel && str_contains(strtolower($customModel), 'gemini');
        $isCustomNemotron = $customModel && str_contains(strtolower($customModel), 'nemotron');
        $shouldForceJson = $this->forceJson;
        
        // If using custom model, check if it supports JSON mode
        if ($customModel) {
            $shouldForceJson = !$isCustomNemotron && !$isCustomGemini;
            $this->log("Using custom model for {$type}: {$modelToUse}, isGemini: " . ($isCustomGemini ? 'yes' : 'no') . ", forceJson: " . ($shouldForceJson ? 'yes' : 'no'));
        }

        $payload = [
            'model' => $modelToUse,
            'messages' => $messages,
            'max_tokens' => 4000, // Increased for better extraction of all days
            'temperature' => 0.1, // Low temperature for consistent, accurate extraction
        ];

        if ($shouldForceJson) {
            $payload['response_format'] = ['type' => 'json_object'];
            $this->log("Using JSON mode for API request with model: {$modelToUse}");
        } else {
            $this->log("NOT using JSON mode for API request (forceJson = false) with model: {$modelToUse}");
        }

        $resp = $this->request($payload);
        if ($resp['error'] !== '') {
            return ['ok' => false, 'reason' => 'שגיאת חיבור: ' . $resp['error']];
        }

        if ($resp['http_code'] !== 200) {
            $handled = $this->handleHttpError($resp['http_code'], $resp['body']);
            if ($handled !== null) {
                if (!empty($handled['retry_without_json_mode'])) {
                    unset($payload['response_format']);
                    $resp2 = $this->request($payload);

                    if ($resp2['error'] !== '') {
                        return ['ok' => false, 'reason' => 'שגיאת חיבור: ' . $resp2['error']];
                    }

                    if ($resp2['http_code'] !== 200) {
                        $handled2 = $this->handleHttpError($resp2['http_code'], $resp2['body']);
                        $reason2 = $handled2 !== null && isset($handled2['reason'])
                            ? (string) $handled2['reason']
                            : ('שגיאת API (' . $resp2['http_code'] . ')');

                        return ['ok' => false, 'reason' => $reason2];
                    }

                    return $this->parseAndNormalize($resp2['body'], $messages, $type);
                }

                return ['ok' => false, 'reason' => (string) $handled['reason']];
            }

            return ['ok' => false, 'reason' => 'שגיאת API (' . $resp['http_code'] . ')'];
        }

        return $this->parseAndNormalize($resp['body'], $messages, $type);
    }

    private function parseAndNormalize(string $body, array $messages, string $type): array
    {
        if ($body === '') {
            return ['ok' => false, 'reason' => 'תגובה ריקה מ-API'];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            $this->log('JSON decode error for API body.');
            return ['ok' => false, 'reason' => 'שגיאה בפענוח תגובת API'];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            return ['ok' => false, 'reason' => 'תגובה לא תקינה מ-API'];
        }

        $json = $this->parseContentJson($content);
        if ($json === null) {
            return $this->retryFixJson($messages, $content, $type);
        }

        if ($this->isOkFalse($json)) {
            $reason = (string) ($json['reason'] ?? $json['message'] ?? 'Unknown error');
            return ['ok' => false, 'reason' => $this->translateError($reason)];
        }

        if ($type === 'schedule') {
            return $this->normalizeSchedule($json, $content);
        }

        if ($type === 'contacts') {
            return $this->normalizeContacts($json, $content);
        }

        if ($type === 'document') {
            return $this->normalizeDocument($json, $content);
        }

        if ($type === 'quick-add') {
            return $this->normalizeQuickAdd($json, $content);
        }

        $json['ok'] = $json['ok'] ?? true;
        return $json;
    }

    private function normalizeSchedule(array $json, string $rawContent): array
    {
        if (!isset($json['schedule'])) {
            $schedule = $this->extractScheduleFromAlternateShapes($json);
            if ($schedule === null) {
                $this->log('Schedule missing in response. Content prefix: ' . substr($rawContent, 0, 600));
                return ['ok' => false, 'reason' => 'פורמט מערכת שעות לא תקין. התגובה מה-API לא מכילה שדה "schedule".'];
            }
            $json = ['ok' => true, 'schedule' => $schedule];
        }

        if (!is_array($json['schedule'])) {
            return ['ok' => false, 'reason' => 'פורמט מערכת שעות לא תקין. השדה "schedule" אינו מערך.'];
        }

        $schedule = $json['schedule'];
        foreach (self::DAYS as $day) {
            if (!isset($schedule[$day]) || !is_array($schedule[$day])) {
                $schedule[$day] = [];
            }
        }

        foreach (array_keys($schedule) as $day) {
            if (!is_array($schedule[$day])) {
                $schedule[$day] = [];
                continue;
            }

            $normalizedLessons = [];
            foreach ($schedule[$day] as $lesson) {
                if (!is_array($lesson)) {
                    continue;
                }

                $time = isset($lesson['time']) ? (string) $lesson['time'] : '';
                $subject = isset($lesson['subject']) ? (string) $lesson['subject'] : '';

                if ($time === '' || $subject === '') {
                    continue;
                }

                $item = ['time' => $time, 'subject' => $subject];

                if (isset($lesson['teacher']) && $lesson['teacher'] !== '') {
                    $item['teacher'] = (string) $lesson['teacher'];
                }
                if (isset($lesson['room']) && $lesson['room'] !== '') {
                    $item['room'] = (string) $lesson['room'];
                }

                $normalizedLessons[] = $item;
            }

            $schedule[$day] = $normalizedLessons;
        }

        $json['ok'] = true;
        $json['schedule'] = $schedule;

        return $json;
    }

    private function normalizeContacts(array $json, string $rawContent): array
    {
        if (!isset($json['contacts'])) {
            $contacts = $this->extractContactsFromAlternateShapes($json);
            if ($contacts === null) {
                $this->log('Contacts missing in response. Content prefix: ' . substr($rawContent, 0, 600));
                return ['ok' => false, 'reason' => 'פורמט אנשי קשר לא תקין. התגובה מה-API לא מכילה שדה "contacts".'];
            }
            $json['contacts'] = $contacts;
        }

        if (!is_array($json['contacts'])) {
            if (is_string($json['contacts'])) {
                $decoded = json_decode($json['contacts'], true);
                if (is_array($decoded)) {
                    $json['contacts'] = $decoded;
                } else {
                    return ['ok' => false, 'reason' => 'פורמט אנשי קשר לא תקין. השדה "contacts" אינו מערך.'];
                }
            } else {
                return ['ok' => false, 'reason' => 'פורמט אנשי קשר לא תקין. השדה "contacts" אינו מערך.'];
            }
        }

        $normalizedContacts = [];
        foreach ($json['contacts'] as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $childName = '';
            if (isset($contact['child_name'])) {
                $childName = (string) $contact['child_name'];
            } elseif (isset($contact['name'])) {
                $childName = (string) $contact['name'];
            }

            $parentPhone = '';
            if (isset($contact['parent_phone'])) {
                $parentPhone = (string) $contact['parent_phone'];
            } elseif (isset($contact['phone'])) {
                $parentPhone = (string) $contact['phone'];
            }

            if ($childName === '' && $parentPhone === '') {
                continue;
            }

            $item = [];
            if ($childName !== '') {
                $item['child_name'] = $childName;
            }
            if ($parentPhone !== '') {
                $item['parent_phone'] = $parentPhone;
            }

            if (isset($contact['role']) && $contact['role'] !== '') {
                $item['role'] = (string) $contact['role'];
            }
            if (isset($contact['notes']) && $contact['notes'] !== '') {
                $item['notes'] = (string) $contact['notes'];
            }

            $normalizedContacts[] = $item;
        }

        if ($normalizedContacts === []) {
            return ['ok' => false, 'reason' => 'לא נמצאו אנשי קשר תקינים בתגובה. נא לבדוק את התמונה או לנסות שוב.'];
        }

        $json['ok'] = true;
        $json['contacts'] = $normalizedContacts;

        return $json;
    }

    private function normalizeDocument(array $json, string $rawContent): array
    {
        if (!isset($json['summary'])) {
            // Try to find summary in alternate keys
            $summary = $json['text'] ?? $json['content'] ?? $json['result'] ?? $json['message'] ?? null;
            if ($summary !== null && is_string($summary)) {
                $json['summary'] = $summary;
            } else {
                $this->log('Summary missing in response. Content prefix: ' . substr($rawContent, 0, 600));
                return ['ok' => false, 'reason' => 'פורמט מסמך לא תקין. התגובה מה-API לא מכילה שדה "summary".'];
            }
        }

        if (!is_string($json['summary'])) {
            return ['ok' => false, 'reason' => 'פורמט מסמך לא תקין. השדה "summary" אינו מחרוזת.'];
        }

        $json['ok'] = true;
        return $json;
    }

    private function extractScheduleFromAlternateShapes(array $json): ?array
    {
        $possibleKeys = ['schedule', 'schedules', 'data', 'result', 'schedule_data'];
        foreach ($possibleKeys as $key) {
            if (isset($json[$key]) && is_array($json[$key])) {
                return $json[$key];
            }
        }

        $dayNames = array_merge(self::DAYS, ['saturday']);
        foreach ($dayNames as $day) {
            if (array_key_exists($day, $json)) {
                return $json;
            }
        }

        return null;
    }

    private function extractContactsFromAlternateShapes(array $json): ?array
    {
        $possibleKeys = ['contacts', 'contact', 'data', 'result', 'contact_list', 'people', 'students', 'children'];
        foreach ($possibleKeys as $key) {
            if (!isset($json[$key])) {
                continue;
            }

            if (is_array($json[$key])) {
                return $json[$key];
            }

            if (is_string($json[$key])) {
                $decoded = json_decode($json[$key], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        foreach ($json as $value) {
            if (!is_array($value) || $value === []) {
                continue;
            }
            $first = reset($value);
            if (is_array($first) && (isset($first['child_name']) || isset($first['name']) || isset($first['parent_phone']) || isset($first['phone']))) {
                return $value;
            }
        }

        if ($json !== [] && array_is_list($json)) {
            $first = reset($json);
            if (is_array($first) && (isset($first['child_name']) || isset($first['name']) || isset($first['parent_phone']) || isset($first['phone']))) {
                return $json;
            }
        }

        return null;
    }

    private function parseContentJson(string $content): ?array
    {
        $trim = trim($content);
        $direct = json_decode($trim, true);
        if (is_array($direct) && json_last_error() === JSON_ERROR_NONE) {
            return $direct;
        }

        return $this->extractJson($content);
    }

    private function retryFixJson(array $originalMessages, string $previousContent, string $type): array
    {
        $fixPrompt = 'Return ONLY valid JSON, no other text. ' . ($type === 'schedule'
                ? 'Format: {"ok": true, "schedule": {...}}'
                : 'Format: {"ok": true, "contacts": [...]}');

        $messages = $originalMessages;
        $messages[] = ['role' => 'assistant', 'content' => $previousContent];
        $messages[] = ['role' => 'user', 'content' => $fixPrompt];

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 2000,
            'temperature' => 0.0,
        ];

        if ($this->forceJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $resp = $this->request($payload);
        if ($resp['error'] !== '') {
            return ['ok' => false, 'reason' => 'שגיאת חיבור: ' . $resp['error']];
        }

        if ($resp['http_code'] !== 200) {
            $handled = $this->handleHttpError($resp['http_code'], $resp['body']);
            $reason = $handled !== null && isset($handled['reason'])
                ? (string) $handled['reason']
                : ('שגיאת API (' . $resp['http_code'] . ')');

            return ['ok' => false, 'reason' => $reason];
        }

        return $this->parseAndNormalize($resp['body'], $messages, $type);
    }

    private function request(array $payload): array
    {
        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            return ['http_code' => 0, 'body' => '', 'error' => 'curl_init failed'];
        }

        // Log API key (first 10 chars only for security)
        $this->log('OpenRouter API Request - URL: ' . $this->apiUrl);
        $this->log('OpenRouter API Request - Model: ' . $this->model);
        $this->log('OpenRouter API Request - API Key (first 10): ' . substr($this->apiKey, 0, 10) . '...');
        $this->log('OpenRouter API Request - Payload keys: ' . implode(', ', array_keys($payload)));

        // Prepare headers for OpenRouter
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: ' . $this->siteUrl,
            'X-Title: ' . $this->siteName,
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        $this->log('OpenRouter API Response - HTTP Code: ' . $httpCode);
        if ($httpCode !== 200) {
            $this->log('OpenRouter API Response - Body: ' . (is_string($body) ? $body : 'NON-STRING BODY'));
        }

        if ($errno !== 0 || $error !== '') {
            $this->log("curl error ({$errno}): {$error}");
            return ['http_code' => $httpCode, 'body' => is_string($body) ? $body : '', 'error' => $error !== '' ? $error : 'curl error'];
        }

        return ['http_code' => $httpCode, 'body' => is_string($body) ? $body : '', 'error' => ''];
    }

    private function handleHttpError(int $httpCode, string $body): ?array
    {
        $errorMessage = 'שגיאת API';
        $retryWithoutJsonMode = false;

        // Log the error for debugging - FULL body first
        $this->log("=== HTTP Error {$httpCode} ===");
        $this->log("Full body (first 1000 chars): " . substr($body, 0, 1000));

        $decoded = json_decode($body, true);
        $apiMsg = $decoded['error']['message'] ?? null;
        $apiType = $decoded['error']['type'] ?? null;
        $apiCode = $decoded['error']['code'] ?? null;
        
        // Also check for error message in different formats (OpenRouter might have different structure)
        if (!$apiMsg && isset($decoded['error']) && is_string($decoded['error'])) {
            $apiMsg = $decoded['error'];
        }
        
        // Check top-level fields too
        if (!$apiMsg && isset($decoded['message'])) {
            $apiMsg = $decoded['message'];
        }

        if (is_string($apiMsg) && $apiMsg !== '') {
            $errorMessage = $apiMsg;
        } elseif (is_string($apiType) && $apiType !== '') {
            $errorMessage = $apiType;
        }
        
        $this->log("Extracted error message: {$errorMessage}");
        $this->log("Error type: " . ($apiType ?? 'null'));
        $this->log("Error code: " . ($apiCode ?? 'null'));

        $errorLower = strtolower((string) $errorMessage);
        
        // Check if error mentions json_object or response_format (even in metadata)
        $bodyLower = strtolower($body);
        
        // Extract the raw error message from metadata if available
        $metadata = $decoded['error']['metadata'] ?? null;
        $rawErrorMessage = $metadata['raw'] ?? $apiMsg ?? $errorMessage;
        
        // FIRST check for rate limit in ANY format - even in 400 errors
        $hasRateLimit = str_contains((string)$bodyLower, 'rate limit') || 
                       str_contains((string)$bodyLower, 'too many requests') ||
                       str_contains((string)$bodyLower, 'rate_limit') ||
                       str_contains((string)$bodyLower, 'ratelimit') ||
                       str_contains((string)$bodyLower, 'rate-limit') ||
                       str_contains((string)$errorLower, 'rate limit') ||
                       str_contains((string)$errorLower, 'too many requests') ||
                       str_contains((string)($apiCode ?? ''), 'rate_limit') ||
                       str_contains((string)($apiCode ?? ''), 'rate_limit_exceeded') ||
                       str_contains((string)($apiCode ?? ''), 'rate_limit_error');
        
        // For rate limit errors, return the raw message from API
        if ($hasRateLimit && ($httpCode === 400 || $httpCode === 429)) {
            $this->log("Rate limit detected in {$httpCode} error, returning raw message");
            return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => false];
        }
        $hasJsonObjectError = str_contains((string)$errorLower, 'json_object') || 
                              str_contains((string)$errorLower, 'response_format') ||
                              str_contains((string)$bodyLower, 'json_object') ||
                              str_contains((string)$bodyLower, 'response format') ||
                              str_contains((string)$bodyLower, 'invalid parameter') ||
                              str_contains((string)$errorLower, 'not supported');

        // For 400 errors, check if it's related to JSON mode or invalid request
        $isNemotron = str_contains(strtolower($this->model), 'nemotron');
        
        // If it's a 400 error and we're using Nemotron with JSON mode, always retry without JSON
        if ($httpCode === 400 && $isNemotron && $this->forceJson) {
            $this->log("400 error with Nemotron model and JSON mode enabled, will retry without JSON mode");
            return ['reason' => 'invalid response_format', 'retry_without_json_mode' => true];
        }
        
        // Check for JSON-related errors
        if (($httpCode === 400 || $httpCode === 405) && ($hasJsonObjectError || $apiCode === 'invalid_request_error')) {
            if ($hasJsonObjectError) {
                $this->log("Detected JSON mode issue in error message, will retry without JSON mode");
                return ['reason' => 'invalid response_format', 'retry_without_json_mode' => true];
            }
        }

        if ($httpCode === 429) {
            // Return the raw error message from API
            $metadata = $decoded['error']['metadata'] ?? null;
            $rawMessage = $metadata['raw'] ?? $apiMsg ?? $errorMessage;
            return ['reason' => $rawMessage, 'retry_without_json_mode' => false];
        }

        if ($httpCode === 401) {
            return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => false];
        }

        if ($httpCode === 500 || $httpCode === 502 || $httpCode === 503) {
            return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => false];
        }

        if ($httpCode === 400) {
            // Log the full error for debugging - log MORE details
            $this->log("400 Bad Request - Full error details:");
            $this->log("  Error message: {$errorMessage}");
            $this->log("  Error type: " . ($apiType ?? 'null'));
            $this->log("  Error code: " . ($apiCode ?? 'null'));
            $this->log("  Full body: " . substr($body, 0, 1000));
            $this->log("  Model: {$this->model}");
            $this->log("  Force JSON: " . ($this->forceJson ? 'yes' : 'no'));
            
            // Check if error body contains rate limit information (sometimes 400 can include rate limit info)
            $hasRateLimitInBody = str_contains((string)$bodyLower, 'rate limit') || 
                                 str_contains((string)$bodyLower, 'too many requests') ||
                                 str_contains((string)$bodyLower, 'rate_limit') ||
                                 str_contains((string)$bodyLower, 'ratelimit') ||
                                 str_contains((string)$errorLower, 'rate limit') ||
                                 str_contains((string)$errorLower, 'too many requests') ||
                                 str_contains((string)($apiCode ?? ''), 'rate_limit') ||
                                 str_contains((string)($apiCode ?? ''), 'rate_limit_exceeded');
            
            if ($hasRateLimitInBody) {
                $this->log("400 error contains rate limit information, treating as rate limit, returning raw message");
                return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => false];
            }
            
            // ALWAYS try without JSON mode for Nemotron if we're using JSON mode and get 400
            $isNemotron = str_contains(strtolower($this->model), 'nemotron');
            if ($isNemotron && $this->forceJson) {
                $this->log("400 error with Nemotron model and JSON mode enabled - ALWAYS retry without JSON mode");
                return ['reason' => 'invalid response_format', 'retry_without_json_mode' => true];
            }
            
            // If it's a generic 400 error and we're using JSON mode, try without it (even for non-Nemotron)
            if ($this->forceJson && !$hasJsonObjectError && !$isNemotron) {
                $this->log("400 error with JSON mode enabled (non-Nemotron), will retry without JSON mode");
                return ['reason' => 'invalid response_format', 'retry_without_json_mode' => true];
            }
            
            // For 400 errors, return the raw message
            return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => $retryWithoutJsonMode];
        }

        // For any other errors, return the raw message
        return ['reason' => $rawErrorMessage, 'retry_without_json_mode' => $retryWithoutJsonMode];
    }

    private function extractJson(string $text): ?array
    {
        if (preg_match('/```(?:json)?\s*\n?([\s\S]*?)\s*\n?```/u', $text, $m)) {
            $candidate = trim((string) $m[1]);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        $firstBrace = strpos($text, '{');
        if ($firstBrace !== false) {
            $candidate = $this->sliceBalanced($text, $firstBrace, '{', '}');
            if ($candidate !== null) {
                $decoded = json_decode($candidate, true);
                if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        $firstBracket = strpos($text, '[');
        if ($firstBracket !== false) {
            $candidate = $this->sliceBalanced($text, $firstBracket, '[', ']');
            if ($candidate !== null) {
                $decoded = json_decode($candidate, true);
                if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    private function sliceBalanced(string $text, int $startPos, string $open, string $close): ?string
    {
        $depth = 0;
        $len = strlen($text);
        for ($i = $startPos; $i < $len; $i++) {
            $ch = $text[$i];
            if ($ch === $open) {
                $depth++;
            } elseif ($ch === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $startPos, $i - $startPos + 1);
                }
            }
        }
        return null;
    }

    private function isOkFalse(array $json): bool
    {
        if (!array_key_exists('ok', $json)) {
            return false;
        }
        $v = $json['ok'];
        return $v === false || $v === 0 || $v === 'false' || $v === '0';
    }

    private function translateError(string $error): string
    {
        $translations = [
            'Image is not readable or not a schedule' => 'התמונה לא קריאה או לא מכילה מערכת שעות',
            'Image is not readable or contains no contact information' => 'התמונה לא קריאה או לא מכילה מידע על אנשי קשר',
            'Image is not readable' => 'התמונה לא קריאה',
            'not a schedule' => 'לא מערכת שעות',
            'contains no contact information' => 'לא מכילה מידע על אנשי קשר',
            'No contact information found' => 'לא נמצא מידע על אנשי קשר',
            'No input provided' => 'לא הוזן קלט',
            'Image file not found' => 'קובץ התמונה לא נמצא',
            'API request failed' => 'בקשת API נכשלה',
            'Invalid API response' => 'תגובת API לא תקינה',
            'Failed to extract data' => 'נכשל בחילוץ הנתונים',
        ];

        if (isset($translations[$error])) {
            return $translations[$error];
        }

        foreach ($translations as $en => $he) {
            if (stripos($error, $en) !== false) {
                return $he;
            }
        }

        return $error . ' (אם השגיאה נמשכת, נא לנסות עם תמונה אחרת)';
    }

    private function detectMimeType(string $path): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);
        if (!is_string($mime) || $mime === '') {
            return null;
        }

        $allowed = [
            'image/png' => true,
            'image/jpeg' => true,
            'image/jpg' => true,
            'image/webp' => true,
            'image/gif' => true,
        ];

        $mimeLower = strtolower($mime);
        return isset($allowed[$mimeLower]) ? $mimeLower : null;
    }

    private function log(string $message): void
    {
        if ($this->debug) {
            Logger::info('AI Service: ' . $message);
        }
    }

    private function getConst(string $name): mixed
    {
        return defined($name) ? constant($name) : null;
    }

    private function defaultContactsImagePrompt(): string
    {
        return "Extract contact information from this image of a class contact list. The image contains a list of students with their parents' phone numbers. Return ONLY valid JSON in this exact format:
{
  \"ok\": true,
  \"contacts\": [
    {\"child_name\": \"שם הילד\", \"parent_phone\": \"050-1234567\"}
  ]
}
Each contact should have:
- child_name: The name of the child/student
- parent_phone: The phone number of the child's parents
If the image is unreadable or contains no contacts, return: {\"ok\": false, \"reason\": \"Image is not readable or contains no contact information\"}";
    }

    private function defaultContactsTextPrompt(): string
    {
        return "Extract contact information from this text of a class contact list. The text contains a list of students with their parents' phone numbers. Return ONLY valid JSON in this exact format:
{
  \"ok\": true,
  \"contacts\": [
    {\"child_name\": \"שם הילד\", \"parent_phone\": \"050-1234567\"}
  ]
}
Each contact should have:
- child_name: The name of the child/student
- parent_phone: The phone number of the child's parents
If the text contains no contacts, return: {\"ok\": false, \"reason\": \"No contact information found\"}";
    }

    private function defaultDocumentPrompt(): string
    {
        return <<<'PROMPT'
You are an expert document analysis specialist. Your task is to analyze this document/image and write a SHORT, CONCISE message in Hebrew about the document's content.

CRITICAL INSTRUCTIONS:
1. Read and understand the ENTIRE document carefully
2. Identify the MAIN topic and KEY points only
3. Write a SHORT message (2-4 sentences maximum) that summarizes the essential information
4. Use HTML tags for formatting: <strong> for bold, <em> for italic, <u> for underline, <br> for line breaks
5. DO NOT use markdown syntax (**, __, etc.) - ONLY use HTML tags
6. Be VERY accurate and precise - only include the most important information
7. If the document contains dates, names, or specific details, include them accurately
8. If the document is in Hebrew, keep the original Hebrew terms and names
9. If the document is in another language, translate key terms to Hebrew but preserve important names and dates
10. The message should be clear, concise, and informative - like a brief reminder or announcement

FORMATTING RULES:
- Use <strong>text</strong> for bold text (NOT **text**)
- Use <em>text</em> for italic text (NOT *text*)
- Use <u>text</u> for underlined text
- Use <br> for line breaks
- You can combine tags: <strong><em>text</em></strong>
- Keep formatting minimal - only use when necessary for emphasis

Return ONLY valid JSON in this exact format (no markdown, no code blocks, no explanations):
{
  "ok": true,
  "title": "כותרת קצרה ומדויקת של המסמך (2-5 מילים)",
  "summary": "<strong>כותרת קצרה:</strong> הודעה קצרה ומדויקת על תוכן המסמך בעברית עם HTML tags..."
}

IMPORTANT: The "title" field should be a SHORT, concise title (2-5 words) that summarizes the document's main topic. It should NOT include HTML tags - just plain text.

If the image is completely blank or contains no readable text, return:
{
  "ok": false,
  "reason": "המסמך לא קריא או ריק"
}
PROMPT;
    }

    private function defaultSchedulePrompt(): string
    {
        return <<<'PROMPT'
You are an expert OCR and data extraction specialist. Your task is to extract a class schedule from this image.

CRITICAL INSTRUCTIONS:
1. Analyze the ENTIRE image carefully - look at every pixel, every line, every character
2. The image might contain a schedule in various formats: tables, lists, handwritten text, printed text
3. Days might appear in Hebrew (ראשון, שני, שלישי, רביעי, חמישי, שישי, שבת) or English (Sunday, Monday, etc.) or abbreviated (א', ב', ג', etc.)
4. Times might be in various formats: 08:00, 8:00, 08:00-09:00, 8:00-9:00, or text like "שמונה עד תשע" or "8:00-9:00"
5. Subjects might be in Hebrew (מתמטיקה, אנגלית, מדעים, וכו') or English (Math, English, Science, etc.) - PRESERVE THE ORIGINAL LANGUAGE
6. The image might be rotated, blurry, or have poor quality - STILL TRY TO READ IT
7. Even if you can only read PART of the schedule, extract what you can see
8. If there are multiple tables or sections, extract ALL of them
9. Be VERY aggressive - if there's ANY text that could be schedule-related, extract it
10. IMPORTANT: Extract times accurately - look for clock times, time ranges, or any time indicators

EXTRACTION STRATEGY:
- Start by identifying the structure: Is it a table? A list? Handwritten notes? A grid? Multiple sections?
- Look for ANY patterns that could indicate a schedule: days of the week, times, subjects, teachers, rooms
- Scan the image systematically: left to right, top to bottom, and also check for multiple columns or sections
- If text is unclear or partially visible, make your best educated guess based on context and surrounding text
- If you see ANY information that could be schedule-related, extract it - even if incomplete
- CRITICAL: You MUST extract ALL days of the week (Sunday through Friday). The schedule typically contains 6 days: ראשון (Sunday), שני (Monday), שלישי (Tuesday), רביעי (Wednesday), חמישי (Thursday), שישי (Friday)
- IMPORTANT: The schedule might be organized in multiple ways:
  * A table where columns are days and rows are time slots (or vice versa)
  * A list format where each day appears as a separate section
  * Multiple tables or sections, each showing different days
  * A grid format with days as headers
- If days are in Hebrew (ראשון, שני, שלישי, רביעי, חמישי, שישי, שבת), translate them to English day names (sunday, monday, tuesday, wednesday, thursday, friday) for the JSON keys
- BUT: Keep subjects in their ORIGINAL language (Hebrew or English) - DO NOT translate them
- For times: Look VERY carefully for ANY time indicators in EVERY row/entry:
  * Clock times: 08:00, 8:00, 08:30, 8:30, 09:00, 9:00, etc.
  * Time ranges: 08:00-09:00, 8:00-9:00, 08:00-09:30, etc.
  * Text times: "שמונה", "תשע", "עשר", "8:00", "9:00", "10:00", etc.
  * Period numbers: "שיעור 1", "שיעור 2", "Period 1", "Period 2" (you can infer times from these)
  * Sequential order: If you see subjects in a list without explicit times, assign sequential times based on position (e.g., first lesson = 08:00, second = 09:00, etc.)
  * If you see subjects but no explicit times, try to infer times from the order or position in the schedule
  * Only use "Unknown" as an absolute last resort if you truly cannot see or infer ANY time information
- If you see a table with rows and columns, carefully map which dimension represents days and which represents time slots
- If you see a list format, identify which items are days, which are times, and which are subjects
- IMPORTANT: Even if the schedule appears to show only one day, look for ALL days. The schedule might be organized in a way where all days are visible in the same image
- DOUBLE CHECK: After extraction, verify that you have extracted lessons for ALL 6 days (sunday through friday). If a day has no lessons, include it as an empty array

CRITICAL OUTPUT FORMAT - READ THIS CAREFULLY:
You MUST return ONLY valid JSON. Do NOT include markdown, code blocks, explanations, or any text outside the JSON.

The JSON MUST start with { and end with }. The structure MUST be exactly:

{
  "ok": true,
  "schedule": {
    "sunday": [{"time": "08:00-09:00", "subject": "מתמטיקה", "teacher": "שם המורה", "room": "101"}],
    "monday": [{"time": "08:00-09:00", "subject": "Math", "teacher": "John Doe", "room": "101"}],
    "tuesday": [],
    "wednesday": [],
    "thursday": [],
    "friday": []
  }
}

ABSOLUTE REQUIREMENTS:
1. The response MUST start with the character { (opening brace)
2. The response MUST end with the character } (closing brace)
3. The response MUST contain the field "schedule" as an object
4. The "schedule" object MUST contain keys: "sunday", "monday", "tuesday", "wednesday", "thursday", "friday"
5. Do NOT wrap the JSON in ```json or ``` code blocks
6. Do NOT add any text before or after the JSON
7. Do NOT include explanations or comments
8. The "schedule" field is REQUIRED and MUST be an object
9. Return ONLY the JSON object, nothing else
10. Do NOT include any markdown formatting

FORMAT RULES:
- Every day (sunday through friday) MUST be present as an array, even if empty
- You MUST extract lessons for ALL days that appear in the schedule (typically all 6 days: sunday, monday, tuesday, wednesday, thursday, friday)
- If a day appears in the image but has no lessons, include it as an empty array: "sunday": []
- Each lesson object MUST have: "time" (required), "subject" (required)
- Optional fields: "teacher", "room"
- Time format: Use "HH:MM-HH:MM" or "HH:MM" format. Extract actual times from the image. If no time is visible, try to infer from context or use "Unknown" only as last resort.
- Subject: Keep in original language (Hebrew or English) - DO NOT translate
- Teacher and Room: Keep in original language if visible
- CRITICAL: Do not stop after extracting one day. Continue extracting ALL days from the schedule image. Scan the ENTIRE image multiple times to ensure you haven't missed any days.
- VERIFICATION: Before returning, count how many days you've extracted. You should have exactly 6 days (sunday through friday). If you're missing any, scan the image again.. Scan the ENTIRE image multiple times to ensure you haven't missed any days.
- VERIFICATION: Before returning, count how many days you've extracted. You should have exactly 6 days (sunday through friday). If you're missing any, scan the image again.
- If the image is COMPLETELY blank or contains NO text at all, return: {"ok": false, "reason": "Image is completely blank or contains no text"}
- If the image contains text but NO schedule-related content (e.g., only random text, no days/times/subjects), return: {"ok": false, "reason": "Image contains text but no schedule information"}
- ONLY return ok: false if you are 100% certain there is NO schedule in the image
PROMPT;
    }

    private function normalizeQuickAdd(array $json, string $rawContent): array
    {
        if (!isset($json['suggestions']) || !is_array($json['suggestions'])) {
            $this->log('Suggestions missing in response. Content prefix: ' . substr($rawContent, 0, 600));
            return ['ok' => false, 'reason' => 'פורמט תגובה לא תקין. התגובה מה-API לא מכילה שדה "suggestions".'];
        }

        $suggestions = $json['suggestions'];
        if (empty($suggestions)) {
            return ['ok' => false, 'reason' => 'לא נמצאו הצעות מתאימות'];
        }

        // Normalize suggestions
        $normalizedSuggestions = [];
        foreach ($suggestions as $suggestion) {
            if (!is_array($suggestion)) {
                continue;
            }

            if (!isset($suggestion['type'])) {
                continue;
            }

            $normalized = [
                'type' => (string) $suggestion['type'],
                'confidence' => isset($suggestion['confidence']) ? (float) $suggestion['confidence'] : null,
                'reason' => isset($suggestion['reason']) ? (string) $suggestion['reason'] : null,
                'extracted_data' => isset($suggestion['extracted_data']) && is_array($suggestion['extracted_data']) 
                    ? $suggestion['extracted_data'] 
                    : []
            ];

            $normalizedSuggestions[] = $normalized;
        }

        if (empty($normalizedSuggestions)) {
            return ['ok' => false, 'reason' => 'לא נמצאו הצעות תקינות'];
        }

        // Sort by confidence (highest first)
        usort($normalizedSuggestions, function($a, $b) {
            $confA = $a['confidence'] ?? 0;
            $confB = $b['confidence'] ?? 0;
            return $confB <=> $confA;
        });

        // Extract common data from all suggestions
        $extractedData = [];
        if (!empty($normalizedSuggestions[0]['extracted_data'])) {
            $extractedData = $normalizedSuggestions[0]['extracted_data'];
        }

        return [
            'ok' => true,
            'suggestions' => $normalizedSuggestions,
            'extracted_data' => $extractedData
        ];
    }

    private function defaultQuickAddPrompt(string $todayDate, string $todayDayOfWeek, string $timezone, string $currentTime): string
    {
        return <<<PROMPT
TODAY_DATE: {$todayDate}
TODAY_DAY_OF_WEEK: {$todayDayOfWeek}
TIMEZONE: {$timezone}
CURRENT_TIME: {$currentTime}

You are an expert content analyzer for a school management system.
Your task is to analyze the provided text or image, determine what type of content it represents, and extract structured information.

You MUST always return structured JSON exactly according to the format defined below.
Do NOT include explanations, markdown, or any text outside the JSON.

CONTENT TYPES

announcement
General announcements, reminders, or messages without a specific scheduled occurrence.

event
School events, birthdays, meetings, trips, celebrations, or gatherings that occur at a specific time and/or date.

homework
Homework assignments, tasks, or exercises given to students, usually with a due date.

contact
Contact information for a single person (name + role + phone).

contact_page
Child profile information that includes the child's personal details (such as birth date) and one or two parents' contact details.

links
Useful links or URLs that should be added to the links block. This can include website URLs, online resources, educational links, etc.

calendar
Holidays, vacations, or special days that should be added to the calendar/holidays block. This includes Hebrew holidays, school vacations, special events with dates.

whatsapp
WhatsApp group information (group name and link/phone number) that should be added to the WhatsApp groups block.

schedule
Class schedule information with days, times, subjects, teachers, and rooms. This should be added to the schedule block. The schedule should include lessons for specific days (sunday, monday, tuesday, wednesday, thursday, friday) with time, subject, teacher (optional), and room (optional).

CORE ANALYSIS INSTRUCTIONS

Analyze the ENTIRE content carefully (text or image).

Identify the PRIMARY content type.

Extract ALL relevant information based on the content type.

Always return:

confidence (0.0–1.0)

reason (in Hebrew, short and clear)

If more than one content type is possible, return multiple suggestions sorted by confidence (highest first).

Never invent data. Infer only when logically unavoidable.

DATE & TIME HANDLING (CRITICAL)
1️⃣ Explicit dates (highest priority)

If the content includes a specific date, such as:

15/01

15.01.2026

15 בינואר

15 בינואר 2026

➡️ Convert it to YYYY-MM-DD and use it.
➡️ Ignore any weekday mentioned alongside it.

2️⃣ Any weekday mention (DEFAULT BEHAVIOR)

If the content mentions ANY weekday, for example:

יום ראשון

יום רביעי

בראשון

ברביעי

יום חמישי

AND no explicit date is provided:

➡️ You MUST:

Assume the intention is the nearest upcoming occurrence of that weekday in the future

Calculate the real calendar date based on today ({$todayDate}, {$todayDayOfWeek}) in timezone {$timezone} (current time: {$currentTime})

Return the date in YYYY-MM-DD

NEVER return the weekday name itself

This rule applies EVEN IF the word "הקרוב" is NOT written.

3️⃣ Same weekday as today (important edge case)

If:

Today is the same weekday mentioned in the content
(for example: today is {$todayDayOfWeek} ({$todayDate}) in timezone {$timezone} (current time: {$currentTime}) and the text says "{$todayDayOfWeek}")

➡️ You MUST choose the NEXT week, not today
UNLESS the content explicitly says:

"היום"

"הערב"

"עכשיו"

4️⃣ Relative time modifiers (override default)

If the content includes relative timing such as:

"בעוד שבוע"

"בעוד שבועיים"

"בעוד X ימים"

"עוד שבוע"

➡️ You MUST:

Calculate the offset from today ({$todayDate}) in timezone {$timezone} (current time: {$currentTime})

If a weekday is also mentioned, align the result to that weekday

Return the final calculated date in YYYY-MM-DD

Examples:

"יום רביעי בעוד שבועיים" → Wednesday + 14 days

"אירוע בעוד שבוע" → today + 7 days

5️⃣ Time extraction

If a time is mentioned:

Extract it in HH:MM (24-hour format)

If a range exists, extract the starting time only

6️⃣ Absolute rules

NEVER return weekday strings as dates

NEVER return both weekday and date

Always return a real calculated date (YYYY-MM-DD) when a weekday is mentioned

CONTENT EXTRACTION BY TYPE
announcement

title

content

date (only if present or inferred)

event

name

date (calculated if needed)

time (if mentioned)

location (if mentioned)

description (optional)

homework

title (if not explicit, infer logically)

content (tasks, exercises, pages)

date (due date, calculated if needed)

contact

name

role

phone

contact_page

child_name

child_birth_date (YYYY-MM-DD)

parent1_name

parent1_role

parent1_phone

parent2_name (optional)

parent2_role (optional)

parent2_phone (optional)

links

title (link name/description)

url (the URL/link address)

whatsapp

title (group name)

url (WhatsApp group link or phone number)

calendar

name (holiday/vacation name)

start_date (YYYY-MM-DD, required)

end_date (YYYY-MM-DD, optional - if not provided, use start_date as single date)

has_camp (boolean, optional - indicates if there's a camp during this holiday)

schedule

schedule (object with day keys: sunday, monday, tuesday, wednesday, thursday, friday. Each day is an array of lessons. Each lesson has: time (required, format: "HH:MM" or "HH:MM-HH:MM"), subject (required), teacher (optional), room (optional))

OUTPUT FORMAT (STRICT)

Return ONLY valid JSON in this exact structure:

{
  "ok": true,
  "suggestions": [
    {
      "type": "event",
      "confidence": 0.95,
      "reason": "הטקסט מתאר אירוע עם תאריך ושעה",
      "extracted_data": {
        "name": "",
        "date": "",
        "time": "",
        "location": "",
        "description": ""
      }
    }
  ]
}

FALLBACKS
If the content exists but cannot be clearly categorized:
{
  "ok": true,
  "suggestions": [
    {
      "type": "unknown",
      "confidence": 0.3,
      "reason": "לא ניתן לזהות בבירור את סוג התוכן",
      "extracted_data": {}
    }
  ]
}

If the content is completely unreadable or empty:
{
  "ok": false,
  "reason": "התוכן לא קריא או ריק"
}
PROMPT;
    }
}