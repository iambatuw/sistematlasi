<?php
/** Fallback template for pages and standard WordPress posts. */
get_header();
?>
<div class="page-shell page-shell-narrow">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article class="article-page">
                <header class="page-hero"><h1><?php echo esc_html(get_the_title()); ?></h1></header>
                <div class="article-body prose-content"><?php echo wp_kses_post(apply_filters('the_content', get_the_content())); ?></div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <div class="empty-panel">
            <h1><?php echo esc_html(atlas_t('search.empty')); ?></h1>
            <a href="<?php echo esc_url(atlas_home_url()); ?>" class="underlined-link"><?php echo esc_html(atlas_t('nav.home')); ?></a>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
