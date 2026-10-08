<?php
/**
 * Sistem Atlası — Importer Helper Functions and Classes
 *
 * Provides pure logic components for the SQLite to WordPress importer:
 * - Markdown to sanitized HTML conversion
 * - Guide data formatting and localization
 * - Date validation and future-date detection
 * - Post identity matching (keyed source_id + language)
 */

declare(strict_types=1);

namespace SistemAtlasi\Importer;

use DateTimeImmutable;
use InvalidArgumentException;
use League\CommonMark\CommonMarkConverter;
use RuntimeException;

class AtlasMarkdownConverter
{
    private ?CommonMarkConverter $converter = null;

    public function __construct()
    {
        if (class_exists(CommonMarkConverter::class)) {
            $this->converter = new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }
    }

    /**
     * Converts source Markdown into sanitized HTML.
     */
    public function toSanitizedHtml(?string $markdown): string
    {
        $markdown = (string) $markdown;
        if (trim($markdown) === '') {
            return '';
        }

        if ($this->converter !== null) {
            $html = (string) $this->converter->convert($markdown);
        } else {
            $html = nl2br(htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        if (function_exists('wp_kses_post')) {
            return wp_kses_post($html);
        }

        return self::safeFallbackSanitize($html);
    }

    /**
     * Safe HTML sanitization fallback when running in isolated test environments.
     */
    public static function safeFallbackSanitize(string $html): string
    {
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><em><b><i><a><code><pre><blockquote><hr><table><thead><tbody><tr><th><td>';
        $stripped = strip_tags($html, $allowedTags);
        // Disallow dangerous URI schemes in href / src attributes
        return (string) preg_replace('#\s*(href|src)\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\2#i', '', $stripped);
    }
}

class AtlasGuideFormatter
{
    public static function localized(mixed $value, string $lang): string
    {
        if (is_array($value)) {
            return (string) ($value[$lang] ?? '');
        }
        return (string) ($value ?? '');
    }

    public static function items(array $items, string $lang, bool $isObjectList = false): string
    {
        $result = [];
        foreach ($items as $item) {
            if ($isObjectList && is_array($item)) {
                $result[] = [
                    'title' => self::localized($item['title'] ?? '', $lang),
                    'body'  => self::localized($item['body'] ?? '', $lang),
                ];
            } else {
                $result[] = self::localized($item, $lang);
            }
        }
        return (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Returns meta fields for a specific family term in its own language.
     */
    public static function formatFamilyMeta(string $familyKey, array $guide, string $lang): array
    {
        return [
            'atlas_family_key'            => $familyKey,
            'atlas_family_accent'         => (string) ($guide['accent'] ?? ''),
            'atlas_family_tagline'        => self::localized($guide['tagline'] ?? '', $lang),
            'atlas_family_architecture'   => self::items($guide['architecture'] ?? [], $lang, true),
            'atlas_family_strengths'      => self::items($guide['strengths'] ?? [], $lang, false),
            'atlas_family_considerations' => self::items($guide['considerations'] ?? [], $lang, false),
        ];
    }

    /**
     * Legacy sibling fields for English stored on the Turkish term for backward compatibility.
     */
    public static function formatLegacyFamilyEnSiblings(array $guide): array
    {
        return [
            'atlas_family_tagline_en'        => self::localized($guide['tagline'] ?? '', 'en'),
            'atlas_family_architecture_en'   => self::items($guide['architecture'] ?? [], 'en', true),
            'atlas_family_strengths_en'      => self::items($guide['strengths'] ?? [], 'en', false),
            'atlas_family_considerations_en' => self::items($guide['considerations'] ?? [], 'en', false),
        ];
    }

    /**
     * Returns OS guide meta fields in the requested language.
     */
    public static function formatSystemGuideMeta(array $systemGuide, string $lang): array
    {
        return [
            'atlas_guide_intro'        => self::localized($systemGuide['intro'] ?? '', $lang),
            'atlas_guide_best_for'     => self::items($systemGuide['best_for'] ?? [], $lang, false),
            'atlas_guide_requirements' => self::items($systemGuide['requirements'] ?? [], $lang, false),
            'atlas_guide_steps'        => self::items($systemGuide['steps'] ?? [], $lang, true),
        ];
    }
}

class AtlasImportDate
{
    /**
     * Validates and normalizes date string to Y-m-d H:i:s.
     */
    public static function validateAndFormat(?string $value, string $context): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        if (!$date || $date->format('Y-m-d H:i:s') !== $value) {
            throw new InvalidArgumentException("Invalid date in {$context}: {$value}. Expected Y-m-d H:i:s format.");
        }
        return $value;
    }

    /**
     * Checks if date is in the future relative to reference time.
     */
    public static function isFuture(string $value, ?string $referenceNow = null): bool
    {
        $date = new DateTimeImmutable($value);
        $now = $referenceNow !== null ? new DateTimeImmutable($referenceNow) : new DateTimeImmutable();
        return $date > $now;
    }
}

class AtlasPostFinder
{
    /**
     * Finds an existing post ID by source_id and language, handling both new
     * marker meta and legacy imports safely.
     *
     * @param int[] $candidatePostIds Post IDs that match post_type and atlas_source_id
     * @param callable $getMetaFn function(int $id, string $key): mixed
     * @param callable $getLangFn function(int $id): string
     * @param callable $getSlugFn function(int $id): string
     */
    public static function findMatchingPostId(
        array $candidatePostIds,
        string $targetLang,
        string $expectedSlug,
        callable $getMetaFn,
        callable $getLangFn,
        callable $getSlugFn
    ): int {
        $matches = [];

        foreach ($candidatePostIds as $id) {
            $id = (int) $id;
            $marker = (string) $getMetaFn($id, 'atlas_source_lang');
            $assignedLang = (string) $getLangFn($id);

            // If explicit marker exists, it must match
            if ($marker !== '') {
                if ($marker === $targetLang) {
                    $matches[] = $id;
                }
                continue;
            }

            // Fallback for legacy imports created before atlas_source_lang marker
            if ($assignedLang !== '') {
                if ($assignedLang === $targetLang) {
                    $matches[] = $id;
                }
                continue;
            }

            // Final fallback: exact slug match
            $currentSlug = (string) $getSlugFn($id);
            if ($currentSlug === $expectedSlug) {
                $matches[] = $id;
            }
        }

        if (count($matches) > 1) {
            throw new RuntimeException("Ambiguous duplicate posts found for target lang {$targetLang}: " . implode(', ', $matches));
        }

        return $matches[0] ?? 0;
    }
}
