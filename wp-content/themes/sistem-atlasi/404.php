<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f2ee">
    <title>404 · <?php echo esc_html(atlas_t('brand_name')); ?></title>
    <link rel="icon" type="image/png" href="<?php echo esc_url(get_theme_file_uri('assets/images/logo.png')); ?>">
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('atlas-theme-v3');
                document.documentElement.setAttribute('data-theme', ['light', 'dark'].includes(stored) ? stored : 'light');
            } catch (_) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <a href="#error-content" class="skip-link"><?php echo esc_html(atlas_t('accessibility.skip')); ?></a>
    <header class="error-header">
        <a href="<?php echo esc_url(atlas_home_url()); ?>" class="wordmark"><?php echo esc_html(atlas_t('brand_name')); ?></a>
        <div>
            <a href="<?php echo esc_url(atlas_locale_switch_url()); ?>" class="header-tool atlas-language-link"><?php echo esc_html(atlas_locale_label()); ?></a>
            <button type="button" data-theme-toggle class="header-tool" aria-label="<?php echo esc_attr(atlas_t('theme.toggle_label')); ?>">
                <span data-theme-icon="dark"><?php echo atlas_icon('moon', 'w-5 h-5'); ?></span>
                <span data-theme-icon="light"><?php echo atlas_icon('sun', 'w-5 h-5'); ?></span>
            </button>
        </div>
    </header>

    <main id="error-content" class="error-layout">
        <p class="margin-label">404</p>
        <h1><?php echo esc_html(atlas_t('errors.404_title')); ?></h1>
        <p><?php echo esc_html(atlas_t('errors.404_description')); ?></p>
        <div class="error-actions">
            <a href="<?php echo esc_url(atlas_home_url()); ?>" class="button button-ink"><?php echo esc_html(atlas_t('errors.home')); ?></a>
            <a href="<?php echo esc_url(atlas_search_url()); ?>" class="underlined-link"><?php echo esc_html(atlas_t('errors.search')); ?></a>
        </div>
    </main>

    <?php wp_footer(); ?>
</body>
</html>
