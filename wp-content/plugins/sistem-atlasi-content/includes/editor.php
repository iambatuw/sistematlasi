<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', static function (): void {
    add_meta_box('atlas_os_tech', 'Teknik Bilgiler', 'atlas_os_tech_box', 'atlas_os', 'normal', 'high');
    add_meta_box('atlas_os_guide', 'Rehber Verileri', 'atlas_os_guide_box', 'atlas_os', 'normal');
    add_meta_box('atlas_article_opts', 'Yazı Seçenekleri', 'atlas_article_opts_box', 'atlas_article', 'side');
    foreach (['atlas_os', 'atlas_article'] as $type) {
        add_meta_box('atlas_import', 'Aktarım Bilgileri', 'atlas_import_box', $type, 'side');
    }
});

function atlas_editor_input(int $post_id, string $key, string $label, string $type = 'text'): void
{
    printf(
        '<tr><th><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="regular-text"></td></tr>',
        esc_attr($key),
        esc_html($label),
        esc_attr($type),
        esc_attr((string) get_post_meta($post_id, $key, true))
    );
}

function atlas_editor_json(int $post_id, string $key, string $label, string $example): void
{
    printf(
        '<p><label for="%1$s"><strong>%2$s</strong></label><br><small>%3$s</small></p><textarea id="%1$s" name="%1$s" rows="5" class="large-text code">%4$s</textarea>',
        esc_attr($key),
        esc_html($label),
        esc_html($example),
        esc_textarea((string) get_post_meta($post_id, $key, true))
    );
}

function atlas_os_tech_box(\WP_Post $post): void
{
    wp_nonce_field('atlas_os_tech_save', '_atlas_os_tech_nonce');
    echo '<table class="form-table"><tbody>';
    foreach ([
        'atlas_developer' => ['Geliştirici', 'text'],
        'atlas_version' => ['Güncel Sürüm', 'text'],
        'atlas_kernel' => ['Çekirdek', 'text'],
        'atlas_license' => ['Lisans', 'text'],
        'atlas_release_year' => ['İlk Çıkış Yılı', 'number'],
        'atlas_official_url' => ['Resmî Web Sitesi', 'url'],
        'atlas_verified_at' => ['Son Doğrulama (YYYY-MM-DD)', 'date'],
    ] as $key => [$label, $type]) {
        atlas_editor_input($post->ID, $key, $label, $type);
    }
    echo '<tr><th>Öne Çıkan</th><td><label><input type="checkbox" name="atlas_featured" value="1"'
        . checked((bool) get_post_meta($post->ID, 'atlas_featured', true), true, false)
        . '> Bu sistemi öne çıkar</label></td></tr></tbody></table>';
}

function atlas_os_guide_box(\WP_Post $post): void
{
    wp_nonce_field('atlas_os_guide_save', '_atlas_os_guide_nonce');
    printf(
        '<p><label for="atlas_guide_intro"><strong>Rehber Giriş Metni</strong></label></p><textarea id="atlas_guide_intro" name="atlas_guide_intro" rows="3" class="large-text">%s</textarea>',
        esc_textarea((string) get_post_meta($post->ID, 'atlas_guide_intro', true))
    );
    atlas_editor_json($post->ID, 'atlas_guide_best_for', 'Uygun Kullanım Alanları', '["Birinci alan", "İkinci alan"]');
    atlas_editor_json($post->ID, 'atlas_guide_requirements', 'Kurulum Gereksinimleri', '["Birinci gereksinim", "İkinci gereksinim"]');
    atlas_editor_json($post->ID, 'atlas_guide_steps', 'Kurulum Adımları', '[{"title":"Başlık", "body":"Açıklama"}]');
}

function atlas_article_opts_box(\WP_Post $post): void
{
    wp_nonce_field('atlas_article_opts_save', '_atlas_article_opts_nonce');
    echo '<label><input type="checkbox" name="atlas_featured" value="1"'
        . checked((bool) get_post_meta($post->ID, 'atlas_featured', true), true, false)
        . '> Öne çıkan yazı</label>';
}

function atlas_import_box(\WP_Post $post): void
{
    wp_nonce_field('atlas_import_save', '_atlas_import_nonce');
    echo '<p>Eski URL eşlemesi ve içe aktarma kimliği. Kaynak kimliğini değiştirirken çakışmaları kontrol edin.</p><table class="form-table"><tbody>';
    atlas_editor_input($post->ID, 'atlas_source_id', 'Kaynak ID', 'number');
    atlas_editor_input($post->ID, 'atlas_legacy_slug', 'Eski URL slug');
    atlas_editor_input($post->ID, 'atlas_original_published_at', 'Özgün yayın tarihi');
    echo '</tbody></table>';
}

function atlas_editor_nonce(string $key, string $action): bool
{
    return isset($_POST[$key]) && is_string($_POST[$key])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$key])), $action);
}

function atlas_editor_save_fields(int $post_id, array $keys): void
{
    foreach ($keys as $key) {
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            continue;
        }
        $value = atlas_sanitize_meta(wp_unslash($_POST[$key]), $key);
        if ($value === '' && str_starts_with($key, 'atlas_guide_') && $key !== 'atlas_guide_intro'
            && trim(wp_unslash($_POST[$key])) !== '') {
            atlas_editor_notice('Geçersiz rehber JSON verisi kaydedilmedi.');
            continue;
        }
        update_post_meta($post_id, $key, $value);
    }
}

function atlas_editor_notice(string $text): void
{
    set_transient('atlas_editor_notice_' . get_current_user_id(), $text, 60);
}
add_action('admin_notices', static function (): void {
    $key = 'atlas_editor_notice_' . get_current_user_id();
    $notice = get_transient($key);
    if ($notice) {
        delete_transient($key);
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($notice) . '</p></div>';
    }
});

function atlas_save_post_editor(int $post_id): void
{
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $type = get_post_type($post_id);
    if ($type === 'atlas_os') {
        if (atlas_editor_nonce('_atlas_os_tech_nonce', 'atlas_os_tech_save')) {
            atlas_editor_save_fields($post_id, [
                'atlas_developer', 'atlas_version', 'atlas_kernel', 'atlas_license',
                'atlas_release_year', 'atlas_official_url', 'atlas_verified_at',
            ]);
            update_post_meta($post_id, 'atlas_featured', isset($_POST['atlas_featured']));
        }
        if (atlas_editor_nonce('_atlas_os_guide_nonce', 'atlas_os_guide_save')) {
            atlas_editor_save_fields($post_id, [
                'atlas_guide_intro', 'atlas_guide_best_for', 'atlas_guide_requirements', 'atlas_guide_steps',
            ]);
        }
    } elseif ($type === 'atlas_article' && atlas_editor_nonce('_atlas_article_opts_nonce', 'atlas_article_opts_save')) {
        update_post_meta($post_id, 'atlas_featured', isset($_POST['atlas_featured']));
    }
    if (in_array($type, ['atlas_os', 'atlas_article'], true)
        && atlas_editor_nonce('_atlas_import_nonce', 'atlas_import_save')) {
        atlas_editor_save_fields($post_id, ['atlas_source_id', 'atlas_legacy_slug', 'atlas_original_published_at']);
    }
}
add_action('save_post_atlas_os', 'atlas_save_post_editor');
add_action('save_post_atlas_article', 'atlas_save_post_editor');

function atlas_family_editor_fields(): array
{
    return [
        'accent' => ['Renk (#rrggbb)', 'color'],
        'tagline' => ['Kısa açıklama', 'textarea'],
        'architecture' => ['Mimari katmanlar', 'json_struct'],
        'strengths' => ['Güçlü yönler', 'json_list'],
        'considerations' => ['Dikkat edilmesi gerekenler', 'json_list'],
    ];
}

function atlas_family_editor_control(string $key, string $label, string $type, string $value): void
{
    printf('<label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr($key), esc_html($label));
    if ($type === 'color') {
        printf('<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="#72a7ff" class="regular-text">', esc_attr($key), esc_attr($value));
    } else {
        $hint = $type === 'json_struct' ? '[{"title":"Başlık", "body":"Açıklama"}]'
            : ($type === 'json_list' ? '["İlk madde", "İkinci madde"]' : '');
        if ($hint) {
            echo '<small>' . esc_html($hint) . '</small><br>';
        }
        printf(
            '<textarea id="%1$s" name="%1$s" rows="%2$d" class="large-text%3$s">%4$s</textarea>',
            esc_attr($key),
            $hint ? 5 : 2,
            $hint ? ' code' : '',
            esc_textarea($value)
        );
    }
}

function atlas_family_editor_form(?\WP_Term $term = null): void
{
    wp_nonce_field('atlas_family_save', '_atlas_family_nonce');
    $language = $term && function_exists('pll_get_term_language') ? pll_get_term_language($term->term_id, 'slug') : '';
    echo '<p>Alanlar bu terimin dilinde gösterilir. Paylaşılan eski terimlerde İngilizce alanları ayrıca doldurabilirsiniz.</p>';
    foreach (atlas_family_editor_fields() as $suffix => [$label, $type]) {
        foreach ($suffix === 'accent' ? [''] : ['', '_en'] as $language_suffix) {
            if ($language === 'en' && $language_suffix === '_en') {
                continue;
            }
            $key = 'atlas_family_' . $suffix . $language_suffix;
            $name = $label . ($language_suffix ? ' (EN — ortak terim)' : '');
            $value = $term ? (string) get_term_meta($term->term_id, $key, true) : '';
            if ($term) {
                echo '<tr class="form-field"><th scope="row">';
                atlas_family_editor_control($key, $name, $type, $value);
                echo '</th></tr>';
            } else {
                echo '<div class="form-field">';
                atlas_family_editor_control($key, $name, $type, $value);
                echo '</div>';
            }
        }
    }
}
add_action('atlas_family_add_form_fields', static function (): void {
    atlas_family_editor_form();
});
add_action('atlas_family_edit_form_fields', static function (\WP_Term $term): void {
    atlas_family_editor_form($term);
});

function atlas_family_save_editor(int $term_id): void
{
    if (!atlas_editor_nonce('_atlas_family_nonce', 'atlas_family_save') || !current_user_can('edit_term', $term_id)) {
        return;
    }
    foreach (atlas_family_editor_fields() as $suffix => $field) {
        foreach ($suffix === 'accent' ? [''] : ['', '_en'] as $language_suffix) {
            $key = 'atlas_family_' . $suffix . $language_suffix;
            if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
                continue;
            }
            $input = wp_unslash($_POST[$key]);
            $value = atlas_sanitize_meta($input, $key);
            if ($value === '' && in_array($field[1], ['json_list', 'json_struct'], true) && trim($input) !== '') {
                atlas_editor_notice('Geçersiz aile rehberi JSON verisi kaydedilmedi.');
                continue;
            }
            update_term_meta($term_id, $key, $value);
        }
    }
}
add_action('created_atlas_family', 'atlas_family_save_editor');
add_action('edited_atlas_family', 'atlas_family_save_editor');
