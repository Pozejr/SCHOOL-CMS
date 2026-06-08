<?php

namespace App\Helpers;

/**
 * Smart Content Parser Engine
 * 
 * Converts plain text written by non-technical administrators
 * into clean, semantic, SEO-friendly HTML5.
 * 
 * Parsing rules:
 *   - Blank lines           → paragraph separator
 *   - Lines starting with # → sub-heading (h3)
 *   - Lines starting with ## → sub-heading (h4)
 *   - Lines starting with - or * → bullet list item
 *   - Lines starting with 1. 2. etc → numbered list item
 *   - Lines starting with > → blockquote
 *   - Lines starting with --- → horizontal rule
 *   - Lines that are bare URLs → linked
 *   - All other text → wrapped in <p>
 */
class ContentParser
{
    /**
     * In-request cache keyed by content hash.
     * Avoids re-parsing identical content across multiple sections on the same page.
     */
    private static array $cache = [];

    /**
     * Parse plain text content into semantic HTML.
     */
    public static function parse(string $content): string
    {
        if (empty(trim($content))) {
            return '';
        }

        $cacheKey = md5($content);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        $blocks = self::buildBlocks($lines);
        $html = self::renderBlocks($blocks);
        $result = trim($html);

        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Generate an excerpt from plain text content.
     */
    public static function excerpt(string $content, int $maxLength = 160): string
    {
        $plain = self::plainText($content);
        if (mb_strlen($plain) <= $maxLength) {
            return $plain;
        }
        return mb_substr($plain, 0, $maxLength) . '...';
    }

    /**
     * Strip all formatting and return plain text.
     */
    public static function plainText(string $content): string
    {
        // Remove markdown-like prefixes
        $text = preg_replace('/^[#*>-]+\s*/m', '', $content);
        $text = preg_replace('/^\d+\.\s*/m', '', $text);
        $text = preg_replace('/^---$/m', '', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Build structured blocks from lines of text.
     */
    private static function buildBlocks(array $lines): array
    {
        $blocks = [];
        $currentParagraph = [];
        $currentList = ['type' => null, 'items' => []];
        $inList = false;

        $lineCount = count($lines);

        for ($i = 0; $i < $lineCount; $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            // Empty line — close current paragraph
            if ($trimmed === '') {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                continue;
            }

            // Horizontal rule
            if (preg_match('/^---+$/', $trimmed)) {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                $blocks[] = ['type' => 'hr'];
                continue;
            }

            // H4 heading (## )
            if (str_starts_with($trimmed, '## ')) {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                $blocks[] = ['type' => 'heading', 'level' => 4, 'text' => self::cleanText(substr($trimmed, 3))];
                continue;
            }

            // H3 heading (# )
            if (str_starts_with($trimmed, '# ')) {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                $blocks[] = ['type' => 'heading', 'level' => 3, 'text' => self::cleanText(substr($trimmed, 2))];
                continue;
            }

            // Bullet list item (- or *)
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches)) {
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                if ($inList && $currentList['type'] !== 'ul') {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                }
                $inList = true;
                $currentList['type'] = 'ul';
                $currentList['items'][] = self::cleanText($matches[1]);
                continue;
            }

            // Numbered list item (1. 2. etc.)
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $matches)) {
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                if ($inList && $currentList['type'] !== 'ol') {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                }
                $inList = true;
                $currentList['type'] = 'ol';
                $currentList['items'][] = self::cleanText($matches[1]);
                continue;
            }

            // Blockquote (> )
            if (str_starts_with($trimmed, '> ')) {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                $blocks[] = ['type' => 'blockquote', 'text' => self::cleanText(substr($trimmed, 2))];
                continue;
            }

            // Bare URL → link
            if (preg_match('/^https?:\/\/\S+$/', $trimmed)) {
                if ($inList) {
                    $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                    $currentList = ['type' => null, 'items' => []];
                    $inList = false;
                }
                if (!empty($currentParagraph)) {
                    $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
                    $currentParagraph = [];
                }
                $blocks[] = ['type' => 'link', 'url' => self::cleanText($trimmed), 'text' => self::cleanText($trimmed)];
                continue;
            }

            // Regular text — add to current paragraph
            if ($inList) {
                $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
                $currentList = ['type' => null, 'items' => []];
                $inList = false;
            }
            $currentParagraph[] = self::cleanText($trimmed);
        }

        // Flush remaining
        if ($inList) {
            $blocks[] = ['type' => 'list', 'list_type' => $currentList['type'], 'items' => $currentList['items']];
        }
        if (!empty($currentParagraph)) {
            $blocks[] = ['type' => 'paragraph', 'text' => implode(' ', $currentParagraph)];
        }

        return $blocks;
    }

    /**
     * Render blocks to HTML.
     */
    private static function renderBlocks(array $blocks): string
    {
        $html = [];

        foreach ($blocks as $block) {
            switch ($block['type']) {
                case 'heading':
                    $tag = 'h' . $block['level'];
                    $html[] = "<{$tag}>{$block['text']}</{$tag}>";
                    break;

                case 'paragraph':
                    $html[] = "<p>{$block['text']}</p>";
                    break;

                case 'list':
                    $tag = $block['list_type'];
                    $items = array_map(fn($item) => "<li>{$item}</li>", $block['items']);
                    $html[] = "<{$tag}>" . implode('', $items) . "</{$tag}>";
                    break;

                case 'blockquote':
                    $html[] = "<blockquote><p>{$block['text']}</p></blockquote>";
                    break;

                case 'hr':
                    $html[] = '<hr>';
                    break;

                case 'link':
                    $html[] = "<p><a href=\"{$block['url']}\" target=\"_blank\" rel=\"noopener noreferrer\">{$block['text']}</a></p>";
                    break;
            }
        }

        return implode("\n", $html);
    }

    /**
     * Clean and sanitize text for HTML output.
     */
    private static function cleanText(string $text): string
    {
        // Encode HTML entities
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Convert inline **bold** to <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Convert inline *italic* to <em>
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Convert inline `code` to <code>
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

        // Convert inline links [text](url)
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text);

        return $text;
    }
}
