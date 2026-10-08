<?php get_header(); ?>
<?php
if (have_posts()) {
    the_post();
}
$post_id = get_the_ID();
$fslug   = atlas_family_slug($post_id);
$content = get_the_content();

// Related posts
$related = new WP_Query(array_merge([
    'post_type'      => 'atlas_article',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'post__not_in'   => [$post_id],
    'orderby'        => 'date',
    'order'          => 'DESC',
], atlas_query_language()));
?>

<article class="article-page">
    <div class="article-shell">
        <nav class="breadcrumb" aria-label="<?php echo esc_attr(atlas_t('accessibility.breadcrumb')); ?>">
            <a href="<?php echo esc_url(atlas_home_url()); ?>"><?php echo esc_html(atlas_t('nav.home')); ?></a><span>/</span>
            <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>"><?php echo esc_html(atlas_t('nav.blog')); ?></a><span>/</span>
            <strong><?php the_title(); ?></strong>
        </nav>
        <header class="article-header">
            <p class="eyebrow"><span></span><?php echo esc_html($fslug ? atlas_family_name($fslug) : atlas_t('blog.general')); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="article-lead"><?php echo esc_html(get_the_excerpt()); ?></p>
            <div class="article-byline">
                <span><?php echo esc_html(atlas_t('blog.author')); ?> <strong><?php echo esc_html(get_the_author() ?: atlas_t('brand_name')); ?></strong></span>
                <span><?php echo esc_html(atlas_t('blog.published_at')); ?> <strong><?php echo esc_html(get_the_date('d.m.Y')); ?></strong></span>
            </div>
        </header>
        <div class="article-body prose-content">
            <?php echo wp_kses_post(apply_filters('the_content', $content)); ?>
        </div>
    </div>

    <?php if ($related->have_posts()) : ?>
    <section class="related-section site-shell">
        <h2><?php echo esc_html(atlas_t('sections.latest_posts')); ?></h2>
        <div class="related-grid">
            <?php while ($related->have_posts()) : $related->the_post(); ?>
            <a href="<?php the_permalink(); ?>">
                <strong><?php the_title(); ?></strong>
                <span><?php echo esc_html(get_the_date('d.m.Y')); ?></span>
                <?php echo atlas_icon('chevron-right', 'w-4 h-4'); ?>
            </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
