<?php
if (!defined('ABSPATH')) {
    exit;
}

function atlas_register_content(): void
{
    $types = [
        'atlas_os' => [
            'plural' => 'İşletim Sistemleri',
            'singular' => 'İşletim Sistemi',
            'slug' => 'os',
            'icon' => 'dashicons-desktop',
        ],
        'atlas_article' => [
            'plural' => 'Teknik Yazılar',
            'singular' => 'Teknik Yazı',
            'slug' => 'blog',
            'icon' => 'dashicons-media-text',
        ],
    ];

    foreach ($types as $type => $cfg) {
        register_post_type($type, [
            'labels' => [
                'name' => $cfg['plural'],
                'singular_name' => $cfg['singular'],
                'add_new_item' => 'Yeni ' . $cfg['singular'] . ' ekle',
                'edit_item' => $cfg['singular'] . ' düzenle',
                'all_items' => 'Tümü',
                'search_items' => 'Ara',
                'not_found' => 'Bulunamadı',
            ],
            'public' => true,
            'has_archive' => $cfg['slug'],
            'rewrite' => ['slug' => $cfg['slug'], 'with_front' => false],
            'menu_icon' => $cfg['icon'],
            'show_in_rest' => true,
            'taxonomies' => ['atlas_family'],
            'supports' => ['title', 'editor', 'excerpt', 'author', 'revisions', 'thumbnail', 'custom-fields'],
            'map_meta_cap' => true,
        ]);
    }

    register_taxonomy('atlas_family', array_keys($types), [
        'labels' => [
            'name' => 'Sistem Aileleri',
            'singular_name' => 'Sistem Ailesi',
            'all_items' => 'Tüm Aileler',
            'edit_item' => 'Aileyi Düzenle',
            'add_new_item' => 'Yeni Aile Ekle',
        ],
        'public' => true,
        'hierarchical' => true,
        'rewrite' => false,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ]);

    // OS-specific metadata
    $os_fields = [
        'atlas_developer' => 'string',
        'atlas_version' => 'string',
        'atlas_kernel' => 'string',
        'atlas_license' => 'string',
        'atlas_release_year' => 'integer',
        'atlas_official_url' => 'string',
        'atlas_verified_at' => 'string',
        'atlas_featured' => 'boolean',
        'atlas_source_id' => 'integer',
        'atlas_legacy_slug' => 'string',
        'atlas_original_published_at' => 'string',
        // guide fields
        'atlas_guide_intro' => 'string',
        'atlas_guide_best_for' => 'string',
        'atlas_guide_requirements' => 'string',
        'atlas_guide_steps' => 'string',
    ];

    // article-specific metadata
    $article_fields = [
        'atlas_featured' => 'boolean',
        'atlas_source_id' => 'integer',
        'atlas_legacy_slug' => 'string',
        'atlas_original_published_at' => 'string',
    ];

    foreach ($os_fields as $key => $kind) {
        register_post_meta('atlas_os', $key, [
            'type' => $kind,
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => 'atlas_can_edit_post_meta',
            'sanitize_callback' => static fn($v) => atlas_sanitize_meta($v, $key),
        ]);
    }

    foreach ($article_fields as $key => $kind) {
        register_post_meta('atlas_article', $key, [
            'type' => $kind,
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => 'atlas_can_edit_post_meta',
            'sanitize_callback' => static fn($v) => atlas_sanitize_meta($v, $key),
        ]);
    }

    // Family taxonomy metadata
    // Base keys contain Turkish for existing imports; English lives in suffixed keys.
    $family_meta = ['accent', 'tagline', 'architecture', 'strengths', 'considerations'];
    foreach ($family_meta as $suffix) {
        foreach ($suffix === 'accent' ? [''] : ['', '_en'] as $language_suffix) {
            $meta_key = 'atlas_family_' . $suffix . $language_suffix;
            register_term_meta('atlas_family', $meta_key, [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => 'atlas_can_edit_family_meta',
                'sanitize_callback' => static fn($v) => atlas_sanitize_meta($v, $meta_key),
            ]);
        }
    }

    register_term_meta('atlas_family', 'atlas_family_key', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => 'atlas_can_edit_family_meta',
        'sanitize_callback' => static fn($v) => sanitize_key((string) $v),
    ]);
}
add_action('init', 'atlas_register_content');

// Polylang must see these even when the theme is inactive or switched.
add_filter('pll_get_post_types', static function (array $types): array {
    $types['atlas_os'] = 'atlas_os';
    $types['atlas_article'] = 'atlas_article';
    return $types;
});
add_filter('pll_get_taxonomies', static function (array $taxonomies): array {
    $taxonomies['atlas_family'] = 'atlas_family';
    return $taxonomies;
});

function atlas_can_edit_post_meta(bool $allowed, string $key, int $post_id, int $user_id): bool
{
    return $post_id > 0 && $user_id > 0 && user_can($user_id, 'edit_post', $post_id);
}

function atlas_can_edit_family_meta(bool $allowed, string $key, int $term_id, int $user_id): bool
{
    return $term_id > 0 && $user_id > 0 && user_can($user_id, 'edit_term', $term_id);
}

/** Preserve the importer’s JSON-string storage format, with field-specific shapes. */
function atlas_sanitize_meta(mixed $value, string $key): mixed
{
    if ($key === 'atlas_featured') {
        return rest_sanitize_boolean($value);
    }
    if (in_array($key, ['atlas_release_year', 'atlas_source_id'], true)) {
        return absint($value);
    }
    if (!is_scalar($value) && $value !== null) {
        return '';
    }
    $value = (string) $value;
    if ($key === 'atlas_official_url') {
        $url = esc_url_raw($value, ['https', 'http']);
        $parts = wp_parse_url($url);
        return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && !empty($parts['host']) && filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
    if (in_array($key, ['atlas_verified_at', 'atlas_original_published_at'], true)) {
        $date = trim($value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T]\d{2}:\d{2}:\d{2})?$/D', $date, $matches)
            || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return '';
        }
        return $date;
    }
    if ($key === 'atlas_family_accent') {
        return sanitize_hex_color($value) ?: '';
    }
    if ($key === 'atlas_legacy_slug') {
        return sanitize_title($value);
    }

    $list_fields = [
        'atlas_guide_best_for', 'atlas_guide_requirements',
        'atlas_family_strengths', 'atlas_family_considerations',
        'atlas_family_strengths_en', 'atlas_family_considerations_en',
    ];
    $entry_fields = ['atlas_guide_steps', 'atlas_family_architecture', 'atlas_family_architecture_en'];
    if (in_array($key, $list_fields, true) || in_array($key, $entry_fields, true)) {
        $decoded = json_decode($value, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return '';
        }
        $clean = [];
        $structured = in_array($key, $entry_fields, true);
        foreach (array_slice($decoded, 0, 20) as $item) {
            if ($structured) {
                if (!is_array($item) || !isset($item['title'], $item['body'])
                    || !is_string($item['title']) || !is_string($item['body'])) {
                    return '';
                }
                $clean[] = [
                    'title' => sanitize_text_field($item['title']),
                    'body' => sanitize_textarea_field($item['body']),
                ];
            } else {
                if (!is_string($item)) {
                    return '';
                }
                $clean[] = sanitize_text_field($item);
            }
        }
        return wp_json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
    if ($key === 'atlas_guide_intro' || str_starts_with($key, 'atlas_family_tagline')) {
        return sanitize_textarea_field($value);
    }
    return sanitize_text_field($value);
}

// A translated taxonomy term may use a language suffix. Templates use canonical
// family keys for names/icons/filter URLs, not the localized term's actual slug.
function atlas_family_slug(int $post_id): string
{
    $terms = get_the_terms($post_id, 'atlas_family');
    if (!is_array($terms) || !$terms) {
        return '';
    }
    $term = reset($terms);
    $key = get_term_meta($term->term_id, 'atlas_family_key', true);
    return is_string($key) && $key !== '' ? sanitize_key($key) : preg_replace('/-en$/D', '', $term->slug);
}

// Helper: decode a JSON guide field into array
function atlas_guide_array(int $post_id, string $key): array
{
    $raw = get_post_meta($post_id, 'atlas_guide_' . $key, true);
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
}

// Helper: get localized family term meta (decoded JSON or plain string).
function atlas_family_meta(string $slug, string $suffix): mixed
{
    $language = function_exists('pll_current_language') ? pll_current_language('slug') : substr(get_locale(), 0, 2);
    $term = get_term_by('slug', $slug, 'atlas_family');
    if ($language === 'en') {
        if ($term && !is_wp_error($term) && function_exists('pll_get_term')) {
            $translated_id = pll_get_term($term->term_id, 'en');
            if ($translated_id) {
                $translated = get_term($translated_id, 'atlas_family');
                if ($translated && !is_wp_error($translated)) {
                    $term = $translated;
                }
            }
        } elseif (!$term || is_wp_error($term)) {
            $term = get_term_by('slug', $slug . '-en', 'atlas_family');
        }
    }
    if (!$term || is_wp_error($term)) {
        return null;
    }
    $key = 'atlas_family_' . $suffix;
    $term_language = function_exists('pll_get_term_language') ? pll_get_term_language($term->term_id, 'slug') : '';
    if ($language === 'en' && $suffix !== 'accent' && $term_language !== 'en'
        && metadata_exists('term', $term->term_id, $key . '_en')) {
        $key .= '_en';
    }
    $raw = get_term_meta($term->term_id, $key, true);
    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : $raw;
}
