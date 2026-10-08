<?php
/**
 * Sistem Atlası · SQLite to WordPress Migration Tool
 *
 * Runs exclusively via WP-CLI:
 *   wp eval-file /path/to/project/wordpress/tools/import-from-sqlite.php --path=/path/to/wordpress
 *
 * Prerequisites:
 *   1. WordPress installed and active.
 *   2. Polylang (free) plugin activated with languages 'tr' (default) and 'en'.
 *   3. Polylang settings: Custom Post Types and Taxonomies enabled for atlas_os, atlas_article, atlas_family.
 *   4. sistem-atlasi-content plugin activated.
 *   5. SQLite3 PHP extension enabled.
 *
 * Safety:
 *   - Source database/database.sqlite is opened strictly with SQLITE3_OPEN_READONLY.
 *   - The importer is idempotent: rerunning updates existing records without duplication.
 */

if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || !WP_CLI || !defined('ABSPATH')) {
    fwrite(STDERR, "Error: This importer must be executed via WP-CLI:\n  wp eval-file /path/to/project/wordpress/tools/import-from-sqlite.php --path=/path/to/wordpress\n");
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Path Resolution & Environment Checks
// ─────────────────────────────────────────────────────────────────────────────

$project_root = dirname(__DIR__);

$sqlite_path = getenv('ATLAS_SQLITE_PATH') ?: (file_exists($project_root . '/_laravel_archive/database/database.sqlite') ? $project_root . '/_laravel_archive/database/database.sqlite' : $project_root . '/database/database.sqlite');
$guides_path = getenv('ATLAS_GUIDES_PATH') ?: (file_exists($project_root . '/_laravel_archive/config/system_guides.php') ? $project_root . '/_laravel_archive/config/system_guides.php' : $project_root . '/config/system_guides.php');

if (!file_exists($sqlite_path)) {
    WP_CLI::error("Source SQLite database not found at: {$sqlite_path}\nSet ATLAS_SQLITE_PATH environment variable if located elsewhere.");
}

if (!file_exists($guides_path)) {
    WP_CLI::error("System guides configuration not found at: {$guides_path}\nSet ATLAS_GUIDES_PATH environment variable if located elsewhere.");
}

// Check required CPTs and Taxonomies
if (!post_type_exists('atlas_os') || !post_type_exists('atlas_article') || !taxonomy_exists('atlas_family')) {
    WP_CLI::error("Required content structures are missing. Please activate the 'sistem-atlasi-content' plugin first.");
}

// Check Polylang functions
$has_polylang = function_exists('pll_set_post_language')
    && function_exists('pll_save_post_translations')
    && function_exists('pll_set_term_language')
    && function_exists('pll_save_term_translations');

if (!$has_polylang) {
    WP_CLI::warning("Polylang functions not detected. Multilingual linking will be bypassed. It is strongly recommended to activate Polylang first.");
}

// Autoload composer if available for CommonMark converter
$autoload_path = file_exists($project_root . '/_laravel_archive/vendor/autoload.php') ? $project_root . '/_laravel_archive/vendor/autoload.php' : $project_root . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Helper Functions
// ─────────────────────────────────────────────────────────────────────────────

function atlas_import_fail(string $message): never
{
    WP_CLI::error($message);
    exit(1);
}

function atlas_import_check(mixed $result, string $context): void
{
    if (is_wp_error($result) || $result === false || $result === 0) {
        $msg = is_wp_error($result) ? $result->get_error_message() : 'Operation failed';
        atlas_import_fail("{$context}: {$msg}");
    }
}

/**
 * Convert Markdown text to clean HTML
 */
function atlas_import_markdown(string $markdown): string
{
    static $converter = null;
    if ($converter === null) {
        if (class_exists('League\CommonMark\CommonMarkConverter')) {
            $converter = new League\CommonMark\CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        } elseif (class_exists('Illuminate\Support\Str')) {
            $converter = 'laravel_str';
        } else {
            $converter = false;
        }
    }

    if ($converter instanceof League\CommonMark\CommonMarkConverter) {
        return (string) $converter->convert($markdown);
    }

    if ($converter === 'laravel_str') {
        return (string) Illuminate\Support\Str::markdown($markdown);
    }

    // Basic regex markdown fallback
    $html = esc_html($markdown);
    $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
    return wpautop($html);
}

function atlas_import_localized(mixed $value, string $lang): string
{
    return (string) (is_array($value) ? ($value[$lang] ?? '') : ($value ?? ''));
}

function atlas_import_items(array $items, string $lang, bool $structured = false): string
{
    $result = [];
    foreach ($items as $item) {
        if ($structured) {
            $result[] = [
                'title' => atlas_import_localized($item['title'] ?? '', $lang),
                'body'  => atlas_import_localized($item['body'] ?? '', $lang),
            ];
        } else {
            $result[] = atlas_import_localized($item, $lang);
        }
    }
    return wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function atlas_import_meta(int $id, array $values, bool $is_term = false): void
{
    foreach ($values as $key => $value) {
        if ($is_term) {
            update_term_meta($id, $key, $value);
        } else {
            update_post_meta($id, $key, $value);
        }
    }
}

/**
 * Locate existing imported post by source ID and language marker
 */
function atlas_import_find_post(string $post_type, int $source_id, string $lang, string $slug): int
{
    $query = new WP_Query([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => 10,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'   => 'atlas_source_id',
                'value' => $source_id,
            ],
        ],
        'suppress_filters' => true,
    ]);

    $posts = $query->posts;
    if (empty($posts)) {
        return 0;
    }

    foreach ($posts as $post_id) {
        $post_id = (int) $post_id;
        $saved_lang = get_post_meta($post_id, 'atlas_source_lang', true);
        if ($saved_lang === $lang) {
            return $post_id;
        }
        if (function_exists('pll_get_post_language') && pll_get_post_language($post_id, 'slug') === $lang) {
            return $post_id;
        }
        if (!$saved_lang && get_post_field('post_name', $post_id) === $slug) {
            return $post_id;
        }
    }

    return 0;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. Importer Execution
// ─────────────────────────────────────────────────────────────────────────────

WP_CLI::line("======================================================");
WP_CLI::line(" Sistem Atlası · SQLite -> WordPress İçe Aktarma     ");
WP_CLI::line("======================================================");
WP_CLI::line("Kaynak veritabanı : " . $sqlite_path);
WP_CLI::line("Rehber dosyası    : " . $guides_path);

// Open SQLite in read-only mode
try {
    $db = new SQLite3($sqlite_path, SQLITE3_OPEN_READONLY);
} catch (Exception $e) {
    WP_CLI::error("SQLite veritabanı salt okunur olarak açılamadı: " . $e->getMessage());
}

$guides = require $guides_path;
$family_guides = $guides['families'] ?? [];
$system_guides = $guides['systems'] ?? [];

// Determine default author
$author_id = get_current_user_id();
if (!$author_id) {
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ids']);
    $author_id = !empty($admins) ? (int) $admins[0] : 1;
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. Family Taxonomy Terms & Metadata
// ─────────────────────────────────────────────────────────────────────────────

WP_CLI::line("\n[1/3] Sistem aileleri ve rehber meta verileri işleniyor...");

$family_names = [
    'linux'   => 'Linux',
    'windows' => 'Windows',
    'macos'   => 'macOS',
];

$family_terms = []; // [family][lang] => term_id

foreach ($family_names as $family_key => $family_name) {
    $fg = $family_guides[$family_key] ?? [];
    $accent = $fg['accent'] ?? '#0ea5e9';

    // Turkish Term (slug: 'linux')
    $tr_slug = $family_key;
    $tr_term = get_term_by('slug', $tr_slug, 'atlas_family');
    if (!$tr_term) {
        $ins = wp_insert_term($family_name, 'atlas_family', ['slug' => $tr_slug]);
        atlas_import_check($ins, "Family TR term {$tr_slug}");
        $tr_id = (int) $ins['term_id'];
    } else {
        $tr_id = (int) $tr_term->term_id;
    }

    // English Term (slug: 'linux-en' for Polylang free slug distinction)
    $en_slug = $family_key . '-en';
    $en_term = get_term_by('slug', $en_slug, 'atlas_family');
    if (!$en_term) {
        $ins = wp_insert_term($family_name, 'atlas_family', ['slug' => $en_slug]);
        atlas_import_check($ins, "Family EN term {$en_slug}");
        $en_id = (int) $ins['term_id'];
    } else {
        $en_id = (int) $en_term->term_id;
    }

    if (function_exists('pll_set_term_language')) {
        pll_set_term_language($tr_id, 'tr');
        pll_set_term_language($en_id, 'en');
    }
    if (function_exists('pll_save_term_translations')) {
        pll_save_term_translations(['tr' => $tr_id, 'en' => $en_id]);
    }

    // Set canonical family key on both terms
    update_term_meta($tr_id, 'atlas_family_key', $family_key);
    update_term_meta($en_id, 'atlas_family_key', $family_key);
    update_term_meta($tr_id, 'atlas_family_accent', $accent);
    update_term_meta($en_id, 'atlas_family_accent', $accent);

    // TR Term metadata
    $tr_tagline = atlas_import_localized($fg['tagline'] ?? '', 'tr');
    $tr_arch    = atlas_import_items($fg['architecture'] ?? [], 'tr', true);
    $tr_str     = atlas_import_items($fg['strengths'] ?? [], 'tr', false);
    $tr_con     = atlas_import_items($fg['considerations'] ?? [], 'tr', false);

    atlas_import_meta($tr_id, [
        'atlas_family_tagline'        => $tr_tagline,
        'atlas_family_architecture'   => $tr_arch,
        'atlas_family_strengths'      => $tr_str,
        'atlas_family_considerations' => $tr_con,
    ], true);

    // EN Term metadata
    $en_tagline = atlas_import_localized($fg['tagline'] ?? '', 'en');
    $en_arch    = atlas_import_items($fg['architecture'] ?? [], 'en', true);
    $en_str     = atlas_import_items($fg['strengths'] ?? [], 'en', false);
    $en_con     = atlas_import_items($fg['considerations'] ?? [], 'en', false);

    atlas_import_meta($en_id, [
        'atlas_family_tagline'        => $en_tagline,
        'atlas_family_architecture'   => $en_arch,
        'atlas_family_strengths'      => $en_str,
        'atlas_family_considerations' => $en_con,
    ], true);

    // Also set _en fallback keys on TR term for backward compatibility
    atlas_import_meta($tr_id, [
        'atlas_family_tagline_en'        => $en_tagline,
        'atlas_family_architecture_en'   => $en_arch,
        'atlas_family_strengths_en'      => $en_str,
        'atlas_family_considerations_en' => $en_con,
    ], true);

    $family_terms[$family_key] = ['tr' => $tr_id, 'en' => $en_id];
    WP_CLI::line("  ✓ Aile: {$family_name} (TR: #{$tr_id}, EN: #{$en_id})");
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Operating Systems (atlas_os)
// ─────────────────────────────────────────────────────────────────────────────

WP_CLI::line("\n[2/3] İşletim sistemleri (atlas_os) aktarılıyor...");

$os_stmt = $db->prepare("SELECT * FROM operating_systems ORDER BY id ASC");
$os_result = $os_stmt->execute();
$os_count = 0;

while ($row = $os_result->fetchArray(SQLITE3_ASSOC)) {
    $os_count++;
    $source_id = (int) $row['id'];
    $slug      = (string) $row['slug'];
    $family    = (string) $row['family'];
    $name      = (string) $row['name'];

    $tr_slug = $slug;
    $en_slug = $slug . '-en';

    $tr_content = atlas_import_markdown((string) $row['body_tr']);
    $en_content = atlas_import_markdown((string) $row['body_en']);

    $pub_date = (string) ($row['published_at'] ?: current_time('mysql'));
    $pub_gmt  = get_gmt_from_date($pub_date);

    // ── Turkish Post ──
    $existing_tr_id = atlas_import_find_post('atlas_os', $source_id, 'tr', $tr_slug);
    $tr_post_data = [
        'post_title'    => $name,
        'post_name'     => $tr_slug,
        'post_excerpt'  => (string) $row['summary_tr'],
        'post_content'  => $tr_content,
        'post_status'   => 'publish',
        'post_type'     => 'atlas_os',
        'post_author'   => $author_id,
        'post_date'     => $pub_date,
        'post_date_gmt' => $pub_gmt,
    ];

    if ($existing_tr_id > 0) {
        $tr_post_data['ID'] = $existing_tr_id;
        $tr_post_id = wp_update_post($tr_post_data);
        atlas_import_check($tr_post_id, "Update OS TR {$slug}");
    } else {
        $tr_post_id = wp_insert_post($tr_post_data);
        atlas_import_check($tr_post_id, "Insert OS TR {$slug}");
    }

    // ── English Post ──
    $existing_en_id = atlas_import_find_post('atlas_os', $source_id, 'en', $en_slug);
    $en_post_data = [
        'post_title'    => $name,
        'post_name'     => $en_slug,
        'post_excerpt'  => (string) $row['summary_en'],
        'post_content'  => $en_content,
        'post_status'   => 'publish',
        'post_type'     => 'atlas_os',
        'post_author'   => $author_id,
        'post_date'     => $pub_date,
        'post_date_gmt' => $pub_gmt,
    ];

    if ($existing_en_id > 0) {
        $en_post_data['ID'] = $existing_en_id;
        $en_post_id = wp_update_post($en_post_data);
        atlas_import_check($en_post_id, "Update OS EN {$slug}");
    } else {
        $en_post_id = wp_insert_post($en_post_data);
        atlas_import_check($en_post_id, "Insert OS EN {$slug}");
    }

    // Assign Polylang languages & link
    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($tr_post_id, 'tr');
        pll_set_post_language($en_post_id, 'en');
    }
    if (function_exists('pll_save_post_translations')) {
        pll_save_post_translations(['tr' => $tr_post_id, 'en' => $en_post_id]);
    }

    // Assign family terms
    if (isset($family_terms[$family])) {
        wp_set_object_terms($tr_post_id, [$family_terms[$family]['tr']], 'atlas_family');
        wp_set_object_terms($en_post_id, [$family_terms[$family]['en']], 'atlas_family');
    }

    // Shared Technical Meta
    $shared_meta = [
        'atlas_developer'             => (string) $row['developer'],
        'atlas_version'               => (string) $row['current_version'],
        'atlas_kernel'                => (string) $row['kernel'],
        'atlas_license'               => (string) $row['license'],
        'atlas_release_year'          => (int) $row['initial_release_year'],
        'atlas_official_url'          => (string) $row['official_url'],
        'atlas_verified_at'           => (string) $row['verified_at'],
        'atlas_featured'              => (bool) $row['featured'],
        'atlas_source_id'             => $source_id,
        'atlas_legacy_slug'           => $slug,
        'atlas_original_published_at' => $pub_date,
    ];

    atlas_import_meta($tr_post_id, array_merge($shared_meta, ['atlas_source_lang' => 'tr']));
    atlas_import_meta($en_post_id, array_merge($shared_meta, ['atlas_source_lang' => 'en']));

    // Deep Guide Data from config/system_guides.php
    $sg = $system_guides[$slug] ?? [];
    if (!empty($sg)) {
        $tr_intro = atlas_import_localized($sg['intro'] ?? '', 'tr');
        $en_intro = atlas_import_localized($sg['intro'] ?? '', 'en');

        $tr_best_for = atlas_import_items($sg['best_for'] ?? [], 'tr', false);
        $en_best_for = atlas_import_items($sg['best_for'] ?? [], 'en', false);

        $tr_reqs = atlas_import_items($sg['requirements'] ?? [], 'tr', false);
        $en_reqs = atlas_import_items($sg['requirements'] ?? [], 'en', false);

        $tr_steps = atlas_import_items($sg['steps'] ?? [], 'tr', true);
        $en_steps = atlas_import_items($sg['steps'] ?? [], 'en', true);

        atlas_import_meta($tr_post_id, [
            'atlas_guide_intro'        => $tr_intro,
            'atlas_guide_best_for'     => $tr_best_for,
            'atlas_guide_requirements' => $tr_reqs,
            'atlas_guide_steps'        => $tr_steps,
        ]);

        atlas_import_meta($en_post_id, [
            'atlas_guide_intro'        => $en_intro,
            'atlas_guide_best_for'     => $en_best_for,
            'atlas_guide_requirements' => $en_reqs,
            'atlas_guide_steps'        => $en_steps,
        ]);
    }

    WP_CLI::line("  ✓ OS #{$source_id}: {$name} ({$slug}) -> TR:#{$tr_post_id}, EN:#{$en_post_id}");
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Technical Articles (atlas_article)
// ─────────────────────────────────────────────────────────────────────────────

WP_CLI::line("\n[3/3] Teknik yazılar (atlas_article) aktarılıyor...");

$post_stmt = $db->prepare("SELECT * FROM posts ORDER BY id ASC");
$post_result = $post_stmt->execute();
$post_count = 0;

while ($row = $post_result->fetchArray(SQLITE3_ASSOC)) {
    $post_count++;
    $source_id = (int) $row['id'];
    $slug      = (string) $row['slug'];
    $family    = (string) ($row['family'] ?? '');

    $tr_slug = $slug;
    $en_slug = $slug . '-en';

    $tr_content = atlas_import_markdown((string) $row['body_tr']);
    $en_content = atlas_import_markdown((string) $row['body_en']);

    $pub_date = (string) ($row['published_at'] ?: current_time('mysql'));
    $pub_gmt  = get_gmt_from_date($pub_date);

    // ── Turkish Article ──
    $existing_tr_id = atlas_import_find_post('atlas_article', $source_id, 'tr', $tr_slug);
    $tr_post_data = [
        'post_title'    => (string) $row['title_tr'],
        'post_name'     => $tr_slug,
        'post_excerpt'  => (string) $row['excerpt_tr'],
        'post_content'  => $tr_content,
        'post_status'   => 'publish',
        'post_type'     => 'atlas_article',
        'post_author'   => $author_id,
        'post_date'     => $pub_date,
        'post_date_gmt' => $pub_gmt,
    ];

    if ($existing_tr_id > 0) {
        $tr_post_data['ID'] = $existing_tr_id;
        $tr_post_id = wp_update_post($tr_post_data);
        atlas_import_check($tr_post_id, "Update Article TR {$slug}");
    } else {
        $tr_post_id = wp_insert_post($tr_post_data);
        atlas_import_check($tr_post_id, "Insert Article TR {$slug}");
    }

    // ── English Article ──
    $existing_en_id = atlas_import_find_post('atlas_article', $source_id, 'en', $en_slug);
    $en_post_data = [
        'post_title'    => (string) $row['title_en'],
        'post_name'     => $en_slug,
        'post_excerpt'  => (string) $row['excerpt_en'],
        'post_content'  => $en_content,
        'post_status'   => 'publish',
        'post_type'     => 'atlas_article',
        'post_author'   => $author_id,
        'post_date'     => $pub_date,
        'post_date_gmt' => $pub_gmt,
    ];

    if ($existing_en_id > 0) {
        $en_post_data['ID'] = $existing_en_id;
        $en_post_id = wp_update_post($en_post_data);
        atlas_import_check($en_post_id, "Update Article EN {$slug}");
    } else {
        $en_post_id = wp_insert_post($en_post_data);
        atlas_import_check($en_post_id, "Insert Article EN {$slug}");
    }

    // Assign Polylang languages & link
    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($tr_post_id, 'tr');
        pll_set_post_language($en_post_id, 'en');
    }
    if (function_exists('pll_save_post_translations')) {
        pll_save_post_translations(['tr' => $tr_post_id, 'en' => $en_post_id]);
    }

    // Assign family term if available
    if ($family !== '' && isset($family_terms[$family])) {
        wp_set_object_terms($tr_post_id, [$family_terms[$family]['tr']], 'atlas_family');
        wp_set_object_terms($en_post_id, [$family_terms[$family]['en']], 'atlas_family');
    }

    // Metadata
    $shared_meta = [
        'atlas_featured'              => (bool) $row['featured'],
        'atlas_source_id'             => $source_id,
        'atlas_legacy_slug'           => $slug,
        'atlas_original_published_at' => $pub_date,
    ];

    atlas_import_meta($tr_post_id, array_merge($shared_meta, ['atlas_source_lang' => 'tr']));
    atlas_import_meta($en_post_id, array_merge($shared_meta, ['atlas_source_lang' => 'en']));

    WP_CLI::line("  ✓ Yazı #{$source_id}: {$row['title_tr']} -> TR:#{$tr_post_id}, EN:#{$en_post_id}");
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. Completion Summary
// ─────────────────────────────────────────────────────────────────────────────

WP_CLI::line("\n======================================================");
WP_CLI::success("İçe aktarma başarıyla tamamlandı!");
WP_CLI::line(sprintf("  • Sistem Aileleri    : 3 aile (6 terim: 3 TR, 3 EN)"));
WP_CLI::line(sprintf("  • İşletim Sistemleri : %d kayıt (toplam %d yazı: %d TR, %d EN)", $os_count, $os_count * 2, $os_count, $os_count));
WP_CLI::line(sprintf("  • Teknik Yazılar     : %d kayıt (toplam %d yazı: %d TR, %d EN)", $post_count, $post_count * 2, $post_count, $post_count));
WP_CLI::line("  • Çeviri Bağlantıları: Polylang ile çift yönlü bağlandı.");
WP_CLI::line("  • Eski Adres Eşlemesi: atlas_legacy_slug ile 301 yönlendirmeye hazır.");
WP_CLI::line("======================================================");
WP_CLI::line("Lütfen WordPress yönetiminde Ayarlar -> Kalıcı Bağlantılar sayfasını ziyaret edip yeniden kaydedin.\n");
