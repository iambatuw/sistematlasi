<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="light">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f2ee">
    <link rel="icon" type="image/png" href="<?php echo esc_url(get_theme_file_uri('assets/images/logo.png')); ?>">
    <script>
        (() => {
            document.documentElement.classList.add('js');
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
    <a href="#main-content" class="skip-link"><?php echo esc_html(atlas_t('accessibility.skip')); ?></a>

    <header class="site-header" data-site-header>
        <div class="site-shell header-inner">
            <a href="<?php echo esc_url(atlas_home_url()); ?>" class="wordmark" aria-label="<?php echo esc_attr(atlas_t('brand_name')); ?>"><?php echo esc_html(atlas_t('brand_name')); ?></a>

            <nav class="desktop-nav" aria-label="<?php echo esc_attr(atlas_t('accessibility.main_nav')); ?>">
                <a href="<?php echo esc_url(atlas_home_url()); ?>" class="<?php echo esc_attr(atlas_nav_class('home')); ?>"<?php echo atlas_aria_current('home'); ?>><?php echo esc_html(atlas_t('nav.home')); ?></a>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>" class="<?php echo esc_attr(atlas_nav_class('os')); ?>"<?php echo atlas_aria_current('os'); ?>><?php echo esc_html(atlas_t('nav.os')); ?></a>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>" class="<?php echo esc_attr(atlas_nav_class('blog')); ?>"<?php echo atlas_aria_current('blog'); ?>><?php echo esc_html(atlas_t('nav.blog')); ?></a>
                <a href="<?php echo esc_url(atlas_search_url()); ?>" class="<?php echo esc_attr(atlas_nav_class('search')); ?>"<?php echo atlas_aria_current('search'); ?>><?php echo esc_html(atlas_t('nav.search')); ?></a>
            </nav>

            <div class="header-actions">
                <a href="<?php echo esc_url(atlas_locale_switch_url()); ?>" class="header-tool atlas-language-link"><?php echo esc_html(atlas_locale_label()); ?></a>
                <button type="button" data-theme-toggle class="header-tool" aria-label="<?php echo esc_attr(atlas_t('theme.toggle_label')); ?>">
                    <span data-theme-icon="dark"><?php echo atlas_icon('moon', 'w-5 h-5'); ?></span>
                    <span data-theme-icon="light"><?php echo atlas_icon('sun', 'w-5 h-5'); ?></span>
                </button>
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(admin_url()); ?>" class="header-auth-btn" aria-label="<?php echo esc_attr(atlas_t('nav.admin')); ?>">
                        <span><?php echo esc_html(atlas_t('nav.admin')); ?></span>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="header-auth-btn" aria-label="<?php echo esc_attr(atlas_t('nav.login')); ?>">
                        <span><?php echo esc_html(atlas_t('nav.login')); ?></span>
                    </a>
                <?php endif; ?>
                <details class="mobile-menu">
                    <summary aria-label="<?php echo esc_attr(atlas_t('accessibility.menu')); ?>"><span></span><span></span></summary>
                    <nav aria-label="<?php echo esc_attr(atlas_t('accessibility.mobile_nav')); ?>">
                        <a href="<?php echo esc_url(atlas_home_url()); ?>"><?php echo esc_html(atlas_t('nav.home')); ?></a>
                        <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>"><?php echo esc_html(atlas_t('nav.os')); ?></a>
                        <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>"><?php echo esc_html(atlas_t('nav.blog')); ?></a>
                        <a href="<?php echo esc_url(atlas_search_url()); ?>"><?php echo esc_html(atlas_t('nav.search')); ?></a>
                        <?php if (is_user_logged_in()) : ?>
                            <a href="<?php echo esc_url(admin_url()); ?>"><?php echo esc_html(atlas_t('nav.admin')); ?></a>
                        <?php else : ?>
                            <a href="<?php echo esc_url(wp_login_url()); ?>"><?php echo esc_html(atlas_t('nav.login')); ?></a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url(atlas_locale_switch_url()); ?>"><?php echo esc_html(atlas_locale_label()); ?></a>
                    </nav>
                </details>
            </div>
        </div>
    </header>

    <main id="main-content">
