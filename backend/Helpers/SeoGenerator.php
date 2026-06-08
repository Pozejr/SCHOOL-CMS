<?php

namespace App\Helpers;

/**
 * SEO Generator
 * 
 * Automatically generates SEO metadata from page content.
 */
class SeoGenerator
{
    /**
     * Generate SEO data from a page and its sections.
     */
    public static function generate(array $page, array $sections = [], string $siteName = 'School CMS'): array
    {
        $title = $page['title'] ?? '';
        $metaDescription = $page['meta_description'] ?? '';
        $featuredImage = $page['featured_image'] ?? null;

        // Auto-generate meta description from first section content
        if (empty($metaDescription) && !empty($sections)) {
            $firstContent = $sections[0]['content'] ?? '';
            if (!empty($firstContent)) {
                $metaDescription = ContentParser::excerpt($firstContent, 160);
            }
        }

        // Auto-select OG image from featured image or first section image
        $ogImage = $featuredImage;
        if (empty($ogImage) && !empty($sections)) {
            foreach ($sections as $section) {
                if (!empty($section['image_url'])) {
                    $ogImage = $section['image_url'];
                    break;
                }
            }
        }

        $metaTitle = $page['meta_title'] ?? '';
        if (empty($metaTitle)) {
            $metaTitle = "{$title} — {$siteName}";
        }

        $slug = $page['slug'] ?? '';

        return [
            'h1' => $title,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'og_title' => $title,
            'og_description' => $metaDescription,
            'og_image' => $ogImage,
            'og_type' => 'website',
            'canonical_url' => "/{$slug}",
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $metaDescription,
                'url' => "/{$slug}",
                'image' => $ogImage,
            ],
        ];
    }
}
