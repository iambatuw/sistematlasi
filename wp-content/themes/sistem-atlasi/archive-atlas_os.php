<?php get_header(); ?>
<?php
$current_family = isset($_GET['family']) && is_string($_GET['family']) ? sanitize_key(wp_unslash($_GET['family'])) : '';
$search_q       = isset($_GET['q']) && is_string($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
$families       = atlas_families();
$paged          = max(1, (int) get_query_var('paged'));

// Build custom query (main query is already modified by pre_get_posts but we need URL param handling)
$args = [
    'post_type'      => 'atlas_os',
    'post_status'    => 'publish',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($current_family && in_array($current_family, ['linux', 'windows', 'macos'], true)) {
    $args['tax_query'] = [[
        'taxonomy' => 'atlas_family',
        'field'    => 'term_id',
        'terms'    => atlas_family_term_id($current_family),
    ]];
}
if ($search_q) {
    $args['s'] = $search_q;
    $args['atlas_os_search'] = true;
}
$systems = new WP_Query(array_merge($args, atlas_query_language()));
?>

<div class="page-shell">
    <header class="page-hero">
        <h1><?php echo esc_html(atlas_t('catalog.title')); ?></h1>
        <p><?php echo esc_html(atlas_t('catalog.subtitle')); ?></p>
    </header>

    <section class="filter-panel" aria-label="<?php echo esc_attr(atlas_t('catalog.title')); ?>">
        <form class="search-field" method="get" action="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>">
            <?php echo atlas_icon('search', 'w-5 h-5'); ?>
            <label for="catalog-search" class="sr-only"><?php echo esc_html(atlas_t('nav.search')); ?></label>
            <input id="catalog-search" type="search" name="q" value="<?php echo esc_attr($search_q); ?>" placeholder="<?php echo esc_attr(atlas_t('catalog.search_placeholder')); ?>…">
            <?php if ($search_q !== '') : ?>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . ($current_family ? '?family=' . urlencode($current_family) : '')); ?>" class="search-field-clear" aria-label="<?php echo esc_attr(atlas_t('catalog.reset_filters')); ?>">&times;</a>
            <?php endif; ?>
            <?php if ($current_family) : ?><input type="hidden" name="family" value="<?php echo esc_attr($current_family); ?>"><?php endif; ?>
        </form>
        <div class="filter-tabs">
            <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . ($search_q ? '?q=' . urlencode($search_q) : '')); ?>"
               <?php if ($current_family === '') : ?>aria-current="page"<?php endif; ?>
               class="<?php echo $current_family === '' ? 'is-active' : ''; ?>"><?php echo esc_html(atlas_t('catalog.all_families')); ?></a>
            <?php foreach ($families as $key => $fam) : ?>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . '?family=' . $key . ($search_q ? '&q=' . urlencode($search_q) : '')); ?>"
                   <?php if ($current_family === $key) : ?>aria-current="page"<?php endif; ?>
                   class="<?php echo $current_family === $key ? 'is-active' : ''; ?>">
                    <?php echo esc_html($fam['name_' . atlas_locale()]); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="catalog-ledger">
        <div class="catalog-ledger-head">
            <span><?php echo esc_html(atlas_t('catalog.title')); ?></span>
            <span><?php echo esc_html(atlas_t('catalog.kernel')); ?></span>
            <span><?php echo esc_html(atlas_t('catalog.release_year')); ?></span>
        </div>
        <?php if ($systems->have_posts()) : ?>
            <?php while ($systems->have_posts()) : $systems->the_post(); ?>
                <a class="catalog-ledger-row" href="<?php the_permalink(); ?>">
                    <div>
                        <small><?php echo esc_html(atlas_family_name(atlas_family_slug(get_the_ID()))); ?></small>
                        <h2><?php the_title(); ?></h2>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                    </div>
                    <span><?php echo esc_html(get_post_meta(get_the_ID(), 'atlas_kernel', true) ?: '—'); ?></span>
                    <span><?php echo esc_html(get_post_meta(get_the_ID(), 'atlas_release_year', true) ?: '—'); ?></span>
                    <b>↗</b>
                </a>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="empty-panel">
                <h2><?php echo esc_html(atlas_t('catalog.empty_title')); ?></h2>
                <p><?php echo esc_html(atlas_t('catalog.empty_desc')); ?></p>
                <?php if ($search_q || $current_family) : ?>
                    <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>" class="button button-solid"><?php echo esc_html(atlas_t('catalog.reset_filters')); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($systems->max_num_pages > 1) : ?>
    <div class="pagination-wrap">
        <?php echo paginate_links([
            'total'     => $systems->max_num_pages,
            'current'   => $paged,
            'add_args'  => array_filter(['q' => $search_q, 'family' => $current_family], static fn($v) => $v !== null && $v !== ''),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]); ?>
    </div>
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>
<?php get_footer(); ?>
