<?php
if (!defined('ABSPATH')) { exit; }

/* ── Translation helper ── */
function atlas_t(string $key): string {
    $locale = atlas_locale();
    static $strings = [];
    if (!isset($strings[$locale])) {
        $file = get_theme_file_path("languages/{$locale}.php");
        $strings[$locale] = file_exists($file) ? (array) require $file : [];
    }
    // Support dot notation: 'nav.home' => $strings['nav']['home']
    $parts = explode('.', $key);
    $value = $strings[$locale];
    foreach ($parts as $part) {
        if (is_array($value) && isset($value[$part])) {
            $value = $value[$part];
        } else {
            return $key; // fallback: return the key itself
        }
    }
    return is_string($value) ? $value : $key;
}

/* ── Locale helper ── */
function atlas_locale(): string {
    if (function_exists('pll_current_language')) {
        $language = pll_current_language('slug');
        if (in_array($language, ['tr', 'en'], true)) return $language;
    }
    return 'tr';
}

function atlas_query_language(): array {
    return function_exists('pll_current_language') ? ['lang' => atlas_locale()] : [];
}

function atlas_count_published(string $post_type): int {
    $query = new WP_Query(array_merge([
        'post_type' => $post_type,
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
    ], atlas_query_language()));
    return (int) $query->found_posts;
}

/* ── URL helpers ── */
function atlas_home_url(string $path = '', ?string $language = null): string {
    $language ??= atlas_locale();
    if (function_exists('pll_home_url')) {
        return trailingslashit(pll_home_url($language)) . ltrim($path, '/');
    }
    return home_url('/' . $language . '/' . ltrim($path, '/'));
}

function atlas_archive_url(string $post_type, ?string $language = null): string {
    $slug = $post_type === 'atlas_article' ? 'blog' : 'os';
    return atlas_home_url($slug . '/', $language);
}

function atlas_search_url(?string $language = null): string {
    return atlas_home_url('', $language) . '?s=';
}

/* ── Locale switcher URL ── */
function atlas_locale_switch_url(): string {
    $target = atlas_locale() === 'tr' ? 'en' : 'tr';
    if (is_singular() && function_exists('pll_get_post')) {
        $translated = pll_get_post(get_queried_object_id(), $target);
        if ($translated && get_post_status($translated) === 'publish') {
            return get_permalink($translated);
        }
    }
    if (is_post_type_archive('atlas_os') || is_post_type_archive('atlas_article')) {
        $url = atlas_archive_url(is_post_type_archive('atlas_os') ? 'atlas_os' : 'atlas_article', $target);
        $query = [];
        foreach (['q', 'family'] as $key) {
            if (isset($_GET[$key]) && is_string($_GET[$key])) {
                $query[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }
        return $query ? add_query_arg($query, $url) : $url;
    }
    if (is_search()) {
        $url = atlas_search_url($target) . rawurlencode(get_search_query(false));
        if (isset($_GET['type']) && is_string($_GET['type'])) {
            $url = add_query_arg('type', sanitize_key($_GET['type']), $url);
        }
        return $url;
    }
    return atlas_home_url('', $target);
}

function atlas_locale_label(): string {
    return atlas_locale() === 'tr' ? 'EN' : 'TR';
}

/* ── Icon helper ── */
function atlas_icon(string $name, string $class = 'w-5 h-5'): string {
    $width  = 20;
    $height = 20;
    if (preg_match('/\bw-(\d+)\b/', $class, $wm)) {
        $width = (int) $wm[1] * 4;
    }
    if (preg_match('/\bh-(\d+)\b/', $class, $hm)) {
        $height = (int) $hm[1] * 4;
    }
    if (str_contains($class, 'family-index-icon')) {
        $width  = 30;
        $height = 30;
    }
    if ($width <= 0)  $width = 20;
    if ($height <= 0) $height = $width;

    $style = sprintf('width: %1$dpx; height: %2$dpx; min-width: %1$dpx; min-height: %2$dpx; max-width: %1$dpx; max-height: %2$dpx; flex-shrink: 0;', $width, $height);

    $icons = [
        'linux' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2a4 4 0 0 0-4 4v6a4 4 0 0 0 8 0V6a4 4 0 0 0-4-4z"/><path d="M8 12c-2 0-4 2-4 5s2 5 5 5h6c3 0 5-2 5-5s-2-5-4-5"/><circle cx="10" cy="6" r="1" fill="currentColor"/><circle cx="14" cy="6" r="1" fill="currentColor"/><path d="M11 9h2"/></svg>',
        'windows' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5.5L10.5 4.5V11.5H3V5.5ZM11.5 4.3L21 3V11.5H11.5V4.3ZM3 12.5H10.5V19.5L3 18.5V12.5ZM11.5 12.5H21V21L11.5 19.7V12.5Z"/></svg>',
        'macos' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.09 22C7.79 22.05 6.8 20.68 5.96 19.47C4.25 17 2.94 12.45 4.7 9.39C5.57 7.87 7.13 6.91 8.82 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.13 16.69C20.06 16.91 19.67 18.27 18.71 19.5ZM15.97 4.54C16.55 3.84 16.94 2.87 16.83 1.9C16 1.93 14.97 2.45 14.39 3.13C13.87 3.73 13.42 4.72 13.55 5.67C14.48 5.74 15.42 5.19 15.97 4.54Z"/></svg>',
        'sun' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
        'moon' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'search' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
        'chevron-right' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>',
        'external-link' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        'check' => '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>',
    ];
    $svg = $icons[$name] ?? '<svg class="%1$s" width="%2$d" height="%3$d" style="%4$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
    return sprintf($svg, esc_attr($class), $width, $height, esc_attr($style));
}

/* ── Theme Setup ── */
add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo');
    add_theme_support('editor-styles');
});

/* ── Enqueue assets ── */
add_action('wp_enqueue_scripts', function (): void {
    $ver = wp_get_theme()->get('Version');

    wp_enqueue_style('atlas-foundation', get_theme_file_uri('assets/css/foundation.css'), [], $ver);
    wp_enqueue_style('atlas-site', get_theme_file_uri('assets/css/site.css'), ['atlas-foundation'], $ver);
    wp_enqueue_style('atlas-wordpress', get_theme_file_uri('assets/css/wordpress.css'), ['atlas-site'], $ver);

    if (is_404()) {
        wp_enqueue_style('atlas-errors', get_theme_file_uri('assets/css/errors.css'), ['atlas-wordpress'], $ver);
    }

    if (function_exists('wp_enqueue_script_module')) {
        wp_enqueue_script_module('sistem-atlasi/app', get_theme_file_uri('assets/js/app.js'), [], $ver);
    } else {
        wp_enqueue_script('atlas-app', get_theme_file_uri('assets/js/app.js'), [], $ver, true);
        add_filter('script_loader_tag', function (string $tag, string $handle): string {
            return $handle === 'atlas-app' ? str_replace('<script ', '<script type="module" ', $tag) : $tag;
        }, 10, 2);
    }
});

/* ── Security headers ── */
add_action('send_headers', function (): void {
    if (is_admin()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
});

/* ── Custom excerpt length ── */
add_filter('excerpt_length', fn() => 30, 999);

/* ── Custom query modifications ── */
add_action('pre_get_posts', function (\WP_Query $query): void {
    if (is_admin() || !$query->is_main_query()) return;

    // OS archive: handle family filter and search
    if ($query->is_post_type_archive('atlas_os')) {
        $query->set('posts_per_page', 9);
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');

        if (isset($_GET['family']) && is_string($_GET['family'])) {
            $family = sanitize_key(wp_unslash($_GET['family']));
            if (in_array($family, ['linux', 'windows', 'macos'], true)) {
                $query->set('tax_query', [[
                    'taxonomy' => 'atlas_family',
                    'field'    => 'term_id',
                    'terms'    => atlas_family_term_id($family),
                ]]);
            }
        }
        if (isset($_GET['q']) && is_string($_GET['q'])) {
            $term = trim(sanitize_text_field(wp_unslash($_GET['q'])));
            if ($term !== '') {
                $query->set('s', $term);
                $query->set('atlas_os_search', true);
            }
        }
    }

    // Article archive: handle search
    if ($query->is_post_type_archive('atlas_article')) {
        $query->set('posts_per_page', 9);
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');

        if (isset($_GET['q']) && is_string($_GET['q'])) {
            $query->set('s', sanitize_text_field(wp_unslash($_GET['q'])));
        }
    }
});


/* The directory also searches the kernel and developer technical fields. */
add_filter('posts_search', function (string $search, \WP_Query $query): string {
    if (!$query->get('atlas_os_search') || $query->get('post_type') !== 'atlas_os') return $search;

    $term = trim((string) $query->get('s'));
    if ($term === '') return $search;

    global $wpdb;
    $like = '%' . $wpdb->esc_like($term) . '%';
    return $wpdb->prepare(
        " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} AS atlas_m WHERE atlas_m.post_id = {$wpdb->posts}.ID AND atlas_m.meta_key IN ('atlas_kernel', 'atlas_developer') AND atlas_m.meta_value LIKE %s))",
        $like, $like, $like, $like
    );
}, 10, 2);

/* ── Reading time helper ── */
function atlas_reading_time(string $content): int {
    $plain = trim(wp_strip_all_tags($content));
    return max(1, (int) ceil(count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []) / 200));
}

/* ── Active nav class helper ── */
function atlas_nav_class(string $check): string {
    $active = false;
    switch ($check) {
        case 'home':
            $active = is_front_page();
            break;
        case 'os':
            $active = is_post_type_archive('atlas_os') || is_singular('atlas_os');
            break;
        case 'blog':
            $active = is_post_type_archive('atlas_article') || is_singular('atlas_article');
            break;
        case 'search':
            $active = is_search();
            break;
    }
    return $active ? 'is-active' : '';
}

function atlas_aria_current(string $check): string {
    $cls = atlas_nav_class($check);
    return $cls ? ' aria-current="page"' : '';
}

/* ── Title filter ── */
add_filter('document_title_parts', function (array $title): array {
    if (is_front_page()) {
        $title['title'] = atlas_t('brand_name') . ' — ' . atlas_t('brand_tagline');
    }
    return $title;
});

add_filter('document_title_separator', fn() => '·');

function atlas_family_term_id(string $slug): int {
    $term = get_term_by('slug', $slug, 'atlas_family');
    if (!$term || is_wp_error($term)) return 0;
    if (function_exists('pll_get_term')) {
        $translated = pll_get_term($term->term_id, atlas_locale());
        if ($translated) return (int) $translated;
    }
    return (int) $term->term_id;
}

/* ── Family config (used in templates) ── */
function atlas_families(): array {
    return [
        'linux' => [
            'name_tr' => 'Linux', 'name_en' => 'Linux',
            'badge' => 'Açık Kaynak & Dağıtımlar', 'badge_en' => 'Open Source & Distributions',
            'color' => '#E09F3E',
        ],
        'windows' => [
            'name_tr' => 'Windows', 'name_en' => 'Windows',
            'badge' => 'Masaüstü & Kurumsal Sunucu', 'badge_en' => 'Desktop & Enterprise Server',
            'color' => '#3E6F91',
        ],
        'macos' => [
            'name_tr' => 'macOS', 'name_en' => 'macOS',
            'badge' => 'Unix Temeli & Donanım Bütünlüğü', 'badge_en' => 'Unix Core & Hardware Synergy',
            'color' => '#8C5E7A',
        ],
    ];
}

function atlas_family_name(string $slug): string {
    $families = atlas_families();
    $locale = atlas_locale();
    $key = 'name_' . $locale;
    return $families[$slug][$key] ?? $slug;
}
