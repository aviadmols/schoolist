<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Centralized logging utility for system events and errors.
 */
class Logger
{
    private const LOG_FILENAME = 'system.log';
    private const MAX_READ_LINES = 500;

    private static ?string $logPath = null;

    /**
     * Resolves and returns the full path to the log file.
     */
    private static function getLogPath(): string
    {
        if (self::$logPath === null) {
            $dir = defined('ROOT_PATH') ? ROOT_PATH . '/logs' : __DIR__ . '/../../logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            self::$logPath = $dir . '/' . self::LOG_FILENAME;
        }
        return self::$logPath;
    }

    /**
     * Internal log writer.
     */
    public static function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        @file_put_contents(self::getLogPath(), $formattedMessage, FILE_APPEND);
        
        // Push to server error log for visibility in cloud consoles (like Railway)
        error_log("[$level] $message");
    }

    /**
     * Log an error message with optional context.
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::formatWithContext($message, $context), 'ERROR');
    }

    /**
     * Log an info message with optional context.
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::formatWithContext($message, $context), 'INFO');
    }

    /**
     * Retrieve last N lines from the log file.
     * 
     * @param int $limit Number of lines to return.
     * @return array
     */
    public static function getLogs(int $limit = self::MAX_READ_LINES): array
    {
        $path = self::getLogPath();
        if (!file_exists($path)) return [];
        
        $lines = file($path);
        return array_slice($lines, -$limit);
    }

    /**
     * Helper to append JSON context to a log message.
     */
    private static function formatWithContext(string $message, array $context): string
    {
        if (empty($context)) return $message;
        return $message . " | Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
}
