<?php get_header(); ?>
<?php
if (have_posts()) {
    the_post();
}
$post_id    = get_the_ID();
$os_family  = atlas_family_slug($post_id);
$families   = atlas_families();

// Meta fields
$developer    = get_post_meta($post_id, 'atlas_developer', true);
$version      = get_post_meta($post_id, 'atlas_version', true);
$kernel       = get_post_meta($post_id, 'atlas_kernel', true);
$license      = get_post_meta($post_id, 'atlas_license', true);
$release_year = get_post_meta($post_id, 'atlas_release_year', true);
$official_url = get_post_meta($post_id, 'atlas_official_url', true);
$verified_at  = get_post_meta($post_id, 'atlas_verified_at', true);

// Guide data
$guide_intro    = get_post_meta($post_id, 'atlas_guide_intro', true) ?: '';
$best_for       = atlas_guide_array($post_id, 'best_for');
$requirements   = atlas_guide_array($post_id, 'requirements');
$steps          = atlas_guide_array($post_id, 'steps');

// Family guide data
$family_accent  = atlas_family_meta($os_family, 'accent') ?: ($families[$os_family]['color'] ?? '#666');
$family_tagline = atlas_family_meta($os_family, 'tagline') ?: '';
$architecture   = atlas_family_meta($os_family, 'architecture') ?: [];
$strengths      = atlas_family_meta($os_family, 'strengths') ?: [];
$considerations = atlas_family_meta($os_family, 'considerations') ?: [];

// Reading time
$content      = get_the_content();
$reading_time = atlas_reading_time($content);

// Related systems
$related = new WP_Query(array_merge([
    'post_type'      => 'atlas_os',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'post__not_in'   => [$post_id],
    'tax_query'      => $os_family ? [[
        'taxonomy' => 'atlas_family',
        'field'    => 'term_id',
        'terms'    => atlas_family_term_id($os_family),
    ]] : [],
    'orderby'        => 'date',
    'order'          => 'DESC',
], atlas_query_language()));
?>

<div class="reading-progress" data-reading-progress aria-hidden="true"><span></span></div>

<article class="record-page" style="--guide-accent: <?php echo esc_attr($family_accent); ?>">
    <header class="record-hero">
        <div class="site-shell">
            <nav class="breadcrumb" aria-label="<?php echo esc_attr(atlas_t('accessibility.breadcrumb')); ?>">
                <a href="<?php echo esc_url(atlas_home_url()); ?>"><?php echo esc_html(atlas_t('nav.home')); ?></a><span>/</span>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>"><?php echo esc_html(atlas_t('nav.os')); ?></a><span>/</span>
                <strong><?php the_title(); ?></strong>
            </nav>
            <div class="record-hero-grid">
                <div class="record-intro">
                    <p class="margin-label"><?php echo esc_html(atlas_family_name($os_family)); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <?php if ($family_tagline) : ?><p class="record-tagline"><?php echo esc_html($family_tagline); ?></p><?php endif; ?>
                    <p class="record-summary"><?php echo esc_html(get_the_excerpt()); ?></p>
                    <div class="record-actions">
                        <a href="#installation" class="button button-ink"><?php echo esc_html(atlas_t('detail.installation')); ?></a>
                        <?php if ($official_url) : ?>
                            <a href="<?php echo esc_url($official_url); ?>" target="_blank" rel="noopener noreferrer" class="underlined-link"><?php echo esc_html(atlas_t('detail.official_download')); ?> <span>↗</span></a>
                        <?php endif; ?>
                    </div>
                </div>
                <dl class="record-sheet">
                    <div><dt><?php echo esc_html(atlas_t('catalog.version')); ?></dt><dd><?php echo esc_html($version ?: '—'); ?></dd></div>
                    <div><dt><?php echo esc_html(atlas_t('catalog.kernel')); ?></dt><dd><?php echo esc_html($kernel ?: '—'); ?></dd></div>
                    <div><dt><?php echo esc_html(atlas_t('catalog.release_year')); ?></dt><dd><?php echo esc_html($release_year ?: '—'); ?></dd></div>
                    <div><dt><?php echo esc_html(atlas_t('catalog.developer')); ?></dt><dd><?php echo esc_html($developer ?: '—'); ?></dd></div>
                    <div><dt><?php echo esc_html(atlas_t('catalog.license')); ?></dt><dd><?php echo esc_html($license ?: '—'); ?></dd></div>
                    <div><dt><?php echo esc_html(atlas_t('detail.reading_time')); ?></dt><dd><?php echo (int) $reading_time; ?></dd></div>
                </dl>
            </div>
        </div>
    </header>

    <?php if ($guide_intro || !empty($best_for)) : ?>
    <section id="overview" class="record-section">
        <div class="site-shell editorial-split">
            <header><h2><?php echo esc_html(atlas_t('detail.overview_title')); ?></h2></header>
            <?php if ($guide_intro) : ?><div class="overview-statement"><p><?php echo esc_html($guide_intro); ?></p></div><?php endif; ?>
            <?php if (!empty($best_for)) : ?>
            <aside class="audience-list">
                <h3><?php echo esc_html(atlas_t('detail.best_for')); ?></h3>
                <ul><?php foreach ($best_for as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
            </aside>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($architecture)) : ?>
    <section class="record-section architecture-record">
        <div class="site-shell">
            <header class="record-section-head">
                <h2><?php echo esc_html(atlas_t('detail.architecture')); ?></h2>
                <span><?php echo esc_html(atlas_t('detail.architecture_desc')); ?></span>
            </header>
            <div class="architecture-ledger">
                <?php foreach ($architecture as $layer) : ?>
                <article>
                    <h3><?php echo esc_html(is_array($layer) ? ($layer['title'] ?? '') : ''); ?></h3>
                    <p><?php echo esc_html(is_array($layer) ? ($layer['body'] ?? '') : $layer); ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section id="installation" class="record-section installation-record">
        <div class="site-shell">
            <header class="record-section-head">
                <h2><?php echo esc_html(atlas_t('detail.installation')); ?></h2>
                <span><?php echo esc_html(atlas_t('detail.installation_desc')); ?></span>
            </header>
            <div class="install-layout">
                <aside class="install-aside">
                    <h3><?php echo esc_html(atlas_t('detail.requirements')); ?></h3>
                    <?php if (!empty($requirements)) : ?>
                    <ul><?php foreach ($requirements as $req) : ?><li><?php echo esc_html($req); ?></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                    <?php if ($official_url) : ?>
                        <a href="<?php echo esc_url($official_url); ?>" target="_blank" rel="noopener noreferrer" class="underlined-link"><?php echo esc_html(atlas_t('detail.official_download')); ?> <span>↗</span></a>
                    <?php endif; ?>
                </aside>
                <?php if (!empty($steps)) : ?>
                <ol class="install-list">
                    <?php foreach ($steps as $si => $step) : ?>
                    <li>
                        <span><?php echo str_pad((string) ($si + 1), 2, '0', STR_PAD_LEFT); ?></span>
                        <div>
                            <h3><?php echo esc_html(is_array($step) ? ($step['title'] ?? '') : ''); ?></h3>
                            <p><?php echo esc_html(is_array($step) ? ($step['body'] ?? '') : $step); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($strengths) || !empty($considerations)) : ?>
    <section class="record-section decision-record">
        <div class="site-shell decision-columns">
            <?php if (!empty($strengths)) : ?>
            <article>
                <h2><?php echo esc_html(atlas_t('detail.strengths')); ?></h2>
                <ul><?php foreach ($strengths as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
            </article>
            <?php endif; ?>
            <?php if (!empty($considerations)) : ?>
            <article>
                <h2><?php echo esc_html(atlas_t('detail.considerations')); ?></h2>
                <ul><?php foreach ($considerations as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul>
            </article>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="record-section technical-record">
        <div class="site-shell technical-layout">
            <aside>
                <h2><?php echo esc_html(atlas_t('detail.deep_dive')); ?></h2>
                <?php if ($verified_at) : ?>
                    <p><?php echo esc_html(atlas_t('catalog.verified_at')); ?><br><strong><?php echo esc_html($verified_at); ?></strong></p>
                <?php endif; ?>
            </aside>
            <div class="detail-content prose-content">
                <?php echo wp_kses_post(apply_filters('the_content', $content)); ?>
            </div>
        </div>
    </section>

    <?php if ($related->have_posts()) : ?>
    <section class="record-section related-record">
        <div class="site-shell">
            <header class="record-section-head">
                <h2><?php echo esc_html(atlas_t('detail.related')); ?></h2>
            </header>
            <div class="related-ledger">
                <?php while ($related->have_posts()) : $related->the_post(); ?>
                <a href="<?php the_permalink(); ?>">
                    <strong><?php the_title(); ?></strong>
                    <small><?php echo esc_html(get_post_meta(get_the_ID(), 'atlas_kernel', true)); ?></small>
                    <b>↗</b>
                </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</article>

<?php get_footer(); ?>
