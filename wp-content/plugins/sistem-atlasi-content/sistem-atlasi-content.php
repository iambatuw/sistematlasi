<?php
/**
 * Plugin Name: Sistem Atlası İçerik
 * Description: İşletim sistemi profilleri, teknik yazılar ve rehber alanları.
 * Version: 1.0.0
 * Requires PHP: 8.3
 * Text Domain: sistem-atlasi-content
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/editor.php';
require_once __DIR__ . '/includes/redirects.php';

register_activation_hook(__FILE__, function (): void {
    atlas_register_content();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
