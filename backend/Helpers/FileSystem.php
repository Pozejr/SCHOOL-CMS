<?php

namespace App\Helpers;

class FileSystem
{
    public static function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public static function deleteFile(string $path): bool
    {
        if (file_exists($path) && is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    public static function getFileSize(string $path): int
    {
        return file_exists($path) ? filesize($path) : 0;
    }

    public static function getHumanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
