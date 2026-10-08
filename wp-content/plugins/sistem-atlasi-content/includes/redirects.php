<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normalizes request paths: trims surrounding slashes and decodes path entities.
 */
function atlas_get_requested_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = (string) wp_parse_url($uri, PHP_URL_PATH);
    $path = rawurldecode($path);
    return trim($path, '/');
}

/**
 * Resolves a legacy slug to the post published in the same language.
 */
function atlas_resolve_legacy_post(string $post_type, string $slug, string $locale): ?int
{
    $posts = get_posts([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'meta_key' => 'atlas_legacy_slug',
        'meta_value' => $slug,
        'numberposts' => 10,
        'fields' => 'ids',
        'suppress_filters' => false,
    ]);

    if (!$posts) {
        return null;
    }

    if (function_exists('pll_get_post_language')) {
        foreach ($posts as $candidate_id) {
            if (pll_get_post_language($candidate_id, 'slug') === $locale) {
                return $candidate_id;
            }
        }
        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($posts[0], $locale);
            if ($translated_id && get_post_status($translated_id) === 'publish') {
                return $translated_id;
            }
        }
    }

    // Unilingual or fallback: verify legacy slug post language match
    foreach ($posts as $candidate_id) {
        $candidate_slug = get_post_field('post_name', $candidate_id);
        if ($locale === 'en' && str_ends_with($candidate_slug, '-en')) {
            return $candidate_id;
        }
        if ($locale === 'tr' && !str_ends_with($candidate_slug, '-en')) {
            return $candidate_id;
        }
    }

    return $posts[0];
}

/**
 * Performs a redirect only when the target URL does not match the request path.
 */
function atlas_safe_redirect(string $target_url, int $status = 301): void
{
    $target_path = (string) wp_parse_url($target_url, PHP_URL_PATH);
    $current_path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if (rtrim($target_path, '/') === rtrim($current_path, '/')) {
        return;
    }

    wp_redirect($target_url, $status);
    exit;
}

/**
 * Redirect /yonetim and /yonetim/* requests to /wp-admin/.
 */
add_action('init', static function (): void {
    if (is_admin()) {
        return;
    }
    $path = atlas_get_requested_path();
    if ($path === 'yonetim' || str_starts_with($path, 'yonetim/')) {
        wp_safe_redirect(admin_url(), 301);
        exit;
    }
}, 1);

/**
 * Legacy URL redirects for old Laravel routes.
 * Maps /{locale}/os/{slug} and /{locale}/blog/{slug} to their WordPress equivalent in the same language.
 */
add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }

    $path = atlas_get_requested_path();
    if ($path === '') {
        return;
    }

    $segments = explode('/', $path);
    if (count($segments) !== 3) {
        return;
    }

    [$locale, $section, $slug] = $segments;
    if (!in_array($locale, ['tr', 'en'], true)) {
        return;
    }

    $type_map = [
        'os' => 'atlas_os',
        'blog' => 'atlas_article',
    ];

    if (!isset($type_map[$section])) {
        return;
    }

    $post_id = atlas_resolve_legacy_post($type_map[$section], $slug, $locale);
    if ($post_id) {
        $target = get_permalink($post_id);
        if ($target) {
            atlas_safe_redirect($target, 301);
        }
    }
}, 10);

/**
 * Redirect root / to default language (/tr/) when language slug negotiation is expected
 * and root has not been handled by WordPress.
 */
add_action('template_redirect', static function (): void {
    if (is_admin()) {
        return;
    }
    $path = atlas_get_requested_path();
    if ($path === '' && function_exists('pll_default_language') && function_exists('pll_home_url')) {
        $default_lang = pll_default_language('slug') ?: 'tr';
        $target = pll_home_url($default_lang);
        if ($target) {
            atlas_safe_redirect($target, 301);
        }
    }
}, 5);
