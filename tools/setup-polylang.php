<?php
if (!defined('ABSPATH')) {
    exit;
}

global $polylang;
if (!$polylang || !isset($polylang->model)) {
    WP_CLI::error("Polylang model not loaded.");
}

$model = $polylang->model;

// Check existing languages
$languages = $model->get_languages_list();
$slugs = wp_list_pluck($languages, 'slug');

// Add Turkish if not present
if (!in_array('tr', $slugs, true)) {
    $res_tr = $model->add_language([
        'name'       => 'Türkçe',
        'slug'       => 'tr',
        'locale'     => 'tr_TR',
        'rtl'        => 0,
        'term_group' => 1,
        'flag'       => 'tr',
    ]);
    WP_CLI::log("Added Turkish (tr): " . ($res_tr ? 'OK' : 'FAIL'));
} else {
    WP_CLI::log("Turkish (tr) already exists.");
}

// Add English if not present
if (!in_array('en', $slugs, true)) {
    $res_en = $model->add_language([
        'name'       => 'English',
        'slug'       => 'en',
        'locale'     => 'en_US',
        'rtl'        => 0,
        'term_group' => 2,
        'flag'       => 'us',
    ]);
    WP_CLI::log("Added English (en): " . ($res_en ? 'OK' : 'FAIL'));
} else {
    WP_CLI::log("English (en) already exists.");
}

// Update Polylang options
$options = get_option('polylang', []);
if (!is_array($options)) {
    $options = [];
}

$options['default_lang']  = 'tr';
$options['force_lang']    = 1; // The language is set from the directory name in pretty permalinks
$options['hide_default']  = 0; // Do not hide URL language information for default language
$options['rewrite']       = 1; // Remove /language/ in permalinks
$options['redirect_lang'] = 1; // The front page url contains the language code
$options['browser']       = 0; // Do not detect browser language
$options['post_types']    = array_unique(array_merge($options['post_types'] ?? [], ['atlas_os', 'atlas_article']));
$options['taxonomies']    = array_unique(array_merge($options['taxonomies'] ?? [], ['atlas_family']));

update_option('polylang', $options);
WP_CLI::success("Polylang configured: default=tr, force_lang=1, hide_default=0, post_types=[atlas_os, atlas_article], taxonomies=[atlas_family]");

// Flush rewrite rules
flush_rewrite_rules(true);
