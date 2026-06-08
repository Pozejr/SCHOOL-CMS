<?php

namespace App\Helpers;

class Security
{
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeHtml(string $input): string
    {
        // Allow safe HTML tags for rich text content
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><blockquote><img><table><tr><td><th><thead><tbody><span><div><hr><pre><code>';
        return strip_tags(trim($input), $allowedTags);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function validateFileType(string $tmpName, array $allowedMimes): bool
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        return in_array($mimeType, $allowedMimes);
    }

    public static function validateFileSize(int $size, int $maxSize): bool
    {
        return $size <= $maxSize && $size > 0;
    }

    public static function generateSecureFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return uniqid('file_', true) . '_' . time() . '.' . $extension;
    }

    public static function setSecureHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Type: application/json; charset=utf-8');
        
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
