<?php

namespace App\Helpers;

class SlugGenerator
{
    public static function generate(string $text, callable $existsCallback = null, int $maxAttempts = 100): string
    {
        $slug = self::toSlug($text);
        
        if ($existsCallback === null) {
            return $slug;
        }

        $original = $slug;
        $counter = 1;

        while ($existsCallback($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
            if ($counter > $maxAttempts) {
                $slug = $original . '-' . time();
                break;
            }
        }

        return $slug;
    }

    private static function toSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}
