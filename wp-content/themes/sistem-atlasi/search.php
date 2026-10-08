<?php get_header(); ?>
<?php
$query_str  = get_search_query(false);
$type       = isset($_GET['type']) && is_string($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : 'all';
if (!in_array($type, ['all', 'os', 'posts'], true)) $type = 'all';
$has_query  = mb_strlen(trim($query_str)) >= 2;

$systems = null;
$articles = null;

if ($has_query) {
    if ($type === 'all' || $type === 'os') {
        $systems = new WP_Query(array_merge([
            'post_type'      => 'atlas_os',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            's'              => $query_str,
            'atlas_os_search' => true,
            'orderby'        => 'relevance',
        ], atlas_query_language()));
    }
    if ($type === 'all' || $type === 'posts') {
        $articles = new WP_Query(array_merge([
            'post_type'      => 'atlas_article',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            's'              => $query_str,
            'orderby'        => 'relevance',
        ], atlas_query_language()));
    }
}

$base_url = atlas_search_url() . rawurlencode($query_str);
$has_systems = $systems instanceof WP_Query && $systems->have_posts();
$has_articles = $articles instanceof WP_Query && $articles->have_posts();
?>

<div class="page-shell page-shell-narrow">
    <header class="page-hero">
        <h1><?php echo esc_html(atlas_t('search.title')); ?></h1>
        <p><?php echo esc_html(atlas_t('search.subtitle')); ?></p>
    </header>

    <section class="search-console">
        <form class="search-field search-field-large" method="get" action="<?php echo esc_url(atlas_home_url()); ?>">
            <?php echo atlas_icon('search', 'w-6 h-6'); ?>
            <label for="site-search" class="sr-only"><?php echo esc_html(atlas_t('nav.search')); ?></label>
            <input id="site-search" type="search" name="s" value="<?php echo esc_attr($query_str); ?>" placeholder="<?php echo esc_attr(atlas_t('search.input_placeholder')); ?>">
            <?php if ($type !== 'all') : ?><input type="hidden" name="type" value="<?php echo esc_attr($type); ?>"><?php endif; ?>
        </form>
        <div class="filter-tabs">
            <a href="<?php echo esc_url($base_url); ?>" <?php if ($type === 'all') : ?>aria-current="page"<?php endif; ?> class="<?php echo $type === 'all' ? 'is-active' : ''; ?>"><?php echo esc_html(atlas_t('search.type_all')); ?></a>
            <a href="<?php echo esc_url($base_url . '&type=os'); ?>" <?php if ($type === 'os') : ?>aria-current="page"<?php endif; ?> class="<?php echo $type === 'os' ? 'is-active' : ''; ?>"><?php echo esc_html(atlas_t('search.type_os')); ?></a>
            <a href="<?php echo esc_url($base_url . '&type=posts'); ?>" <?php if ($type === 'posts') : ?>aria-current="page"<?php endif; ?> class="<?php echo $type === 'posts' ? 'is-active' : ''; ?>"><?php echo esc_html(atlas_t('search.type_posts')); ?></a>
        </div>
    </section>

    <?php if ($has_query) : ?>
        <div class="search-results">
            <?php if ($has_systems) : ?>
            <section class="result-group">
                <header><h2><?php echo esc_html(atlas_t('search.os_results')); ?></h2><span><?php echo (int) $systems->found_posts; ?></span></header>
                <div class="result-list">
                    <?php while ($systems->have_posts()) : $systems->the_post(); ?>
                    <a href="<?php the_permalink(); ?>" class="result-row">
                        <span class="result-type"><?php echo esc_html(atlas_family_name(atlas_family_slug(get_the_ID()))); ?></span>
                        <span><strong><?php the_title(); ?></strong><small><?php echo esc_html(wp_trim_words(get_the_excerpt(), 15, '...')); ?></small></span>
                        <?php echo atlas_icon('chevron-right', 'w-4 h-4'); ?>
                    </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($has_articles) : ?>
            <section class="result-group">
                <header><h2><?php echo esc_html(atlas_t('search.post_results')); ?></h2><span><?php echo (int) $articles->found_posts; ?></span></header>
                <div class="result-list">
                    <?php while ($articles->have_posts()) : $articles->the_post(); ?>
                    <a href="<?php the_permalink(); ?>" class="result-row">
                        <span class="result-type"><?php echo esc_html(get_the_date('d.m.Y')); ?></span>
                        <span><strong><?php the_title(); ?></strong><small><?php echo esc_html(wp_trim_words(get_the_excerpt(), 15, '...')); ?></small></span>
                        <?php echo atlas_icon('chevron-right', 'w-4 h-4'); ?>
                    </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!$has_systems && !$has_articles) : ?>
                <div class="empty-panel"><p><?php echo esc_html(atlas_t('search.empty')); ?></p></div>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="search-hint"><p><?php echo esc_html(atlas_t('search.hint')); ?></p></div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
