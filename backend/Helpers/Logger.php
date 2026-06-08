<?php

namespace App\Helpers;

class Logger
{
    private static ?string $logFile = null;

    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (self::$logFile === null) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logLine = "[{$timestamp}] {$level}: {$message}";
        if ($contextStr) {
            $logLine .= " | Context: {$contextStr}";
        }
        $logLine .= PHP_EOL;

        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
