<?php get_header(); ?>
<?php
$search_q = isset($_GET['q']) && is_string($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$paged    = max(1, (int) get_query_var('paged'));

$args = [
    'post_type'      => 'atlas_article',
    'post_status'    => 'publish',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($search_q) {
    $args['s'] = $search_q;
}
$posts_query = new WP_Query(array_merge($args, atlas_query_language()));
?>

<div class="page-shell">
    <header class="page-hero">
        <h1><?php echo esc_html(atlas_t('blog.title')); ?></h1>
        <p><?php echo esc_html(atlas_t('blog.subtitle')); ?></p>
    </header>

    <section class="filter-panel filter-panel-simple">
        <form class="search-field" method="get" action="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>">
            <?php echo atlas_icon('search', 'w-5 h-5'); ?>
            <label for="article-search" class="sr-only"><?php echo esc_html(atlas_t('nav.search')); ?></label>
            <input id="article-search" type="search" name="q" value="<?php echo esc_attr($search_q); ?>" placeholder="<?php echo esc_attr(atlas_t('blog.search_placeholder')); ?>…">
            <?php if ($search_q !== '') : ?>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>" class="search-field-clear" aria-label="<?php echo esc_attr(atlas_t('catalog.reset_filters')); ?>">&times;</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="article-ledger">
        <?php if ($posts_query->have_posts()) : ?>
            <?php while ($posts_query->have_posts()) : $posts_query->the_post(); ?>
                <article>
                    <div class="article-card-meta">
                        <span><?php
                            $fslug = atlas_family_slug(get_the_ID());
                            echo esc_html($fslug ? atlas_family_name($fslug) : atlas_t('blog.general'));
                        ?></span>
                        <time><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
                    </div>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                    <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(atlas_t('blog.read_more')); ?>">↗</a>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="empty-panel">
                <h2><?php echo esc_html(atlas_t('blog.empty_title')); ?></h2>
                <p><?php echo esc_html(atlas_t('blog.empty_desc')); ?></p>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($posts_query->max_num_pages > 1) : ?>
    <div class="pagination-wrap">
        <?php echo paginate_links([
            'total'     => $posts_query->max_num_pages,
            'current'   => $paged,
            'add_args'  => array_filter(['q' => $search_q], static fn($v) => $v !== null && $v !== ''),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]); ?>
    </div>
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>
<?php get_footer(); ?>
