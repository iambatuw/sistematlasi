<?php get_header(); ?>
<?php
// Query data
$featured_query = new WP_Query(array_merge([
    'post_type'      => 'atlas_os',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'meta_key'       => 'atlas_featured',
    'meta_value'     => '1',
    'orderby'        => 'date',
    'order'          => 'DESC',
], atlas_query_language()));
if (!$featured_query->have_posts()) {
    $featured_query = new WP_Query(array_merge([
        'post_type'      => 'atlas_os',
        'post_status'    => 'publish',
        'posts_per_page' => 6,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ], atlas_query_language()));
}

$latest_posts = new WP_Query(array_merge([
    'post_type'      => 'atlas_article',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'orderby'        => 'date',
    'order'          => 'DESC',
], atlas_query_language()));

$system_count = atlas_count_published('atlas_os');
$post_count   = atlas_count_published('atlas_article');
$family_count = count(atlas_families());
$locale_codes = ['TR', 'EN'];
$families     = atlas_families();
?>

    <section class="editorial-hero">
        <div class="site-shell">
            <div class="editorial-hero-grid">
                <h1><?php echo esc_html(atlas_t('home.title')); ?></h1>
                <div class="editorial-hero-note">
                    <p><?php echo esc_html(atlas_t('home.intro')); ?></p>
                    <div class="hero-actions">
                        <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>" class="button button-ink"><?php echo esc_html(atlas_t('hero.explore_btn')); ?></a>
                        <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>" class="underlined-link"><?php echo esc_html(atlas_t('hero.read_blog_btn')); ?> <span>↗</span></a>
                    </div>
                </div>
            </div>

            <dl class="edition-stats">
                <div><dt><?php echo (int) $system_count; ?></dt><dd><?php echo esc_html(atlas_t('hero.fact_profiles')); ?></dd></div>
                <div><dt><?php echo (int) $post_count; ?></dt><dd><?php echo esc_html(atlas_t('hero.fact_articles')); ?></dd></div>
                <div><dt><?php echo (int) $family_count; ?></dt><dd><?php echo esc_html(atlas_t('hero.fact_families')); ?></dd></div>
                <div><dt><?php echo esc_html(implode(' / ', $locale_codes)); ?></dt><dd><?php echo esc_html(atlas_t('hero.fact_languages')); ?></dd></div>
            </dl>
        </div>
    </section>

    <section class="family-index">
        <div class="site-shell">
            <header class="editorial-section-header">
                <h2><?php echo esc_html(atlas_t('home.families_title')); ?></h2>
                <span><?php echo esc_html(atlas_t('home.families_note')); ?></span>
            </header>

            <div class="family-index-list">
                <?php
                $family_icons = ['linux' => 'linux', 'windows' => 'windows', 'macos' => 'macos'];
                foreach ($families as $slug => $fam) :
                ?>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . '?family=' . $slug); ?>">
                    <?php echo atlas_icon($family_icons[$slug] ?? 'linux', 'family-index-icon'); ?>
                    <div>
                        <h3><?php echo esc_html($fam['name_' . atlas_locale()]); ?></h3>
                        <p><?php echo esc_html(atlas_t('families.' . $slug . '_desc')); ?></p>
                    </div>
                    <span class="family-index-meta"><?php echo esc_html(atlas_t('families.' . $slug . '_meta')); ?></span>
                    <span class="family-index-arrow">↗</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="selected-systems">
        <div class="site-shell selected-layout">
            <header>
                <h2><?php echo esc_html(atlas_t('home.selected_title')); ?></h2>
                <p><?php echo esc_html(atlas_t('home.selected_intro')); ?></p>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>" class="underlined-link"><?php echo esc_html(atlas_t('sections.all_systems')); ?> <span>↗</span></a>
            </header>

            <div class="system-ledger">
                <div class="ledger-head">
                    <span><?php echo esc_html(atlas_t('catalog.title')); ?></span>
                    <span><?php echo esc_html(atlas_t('catalog.kernel')); ?></span>
                    <span><?php echo esc_html(atlas_t('catalog.version')); ?></span>
                </div>
                <?php if ($featured_query->have_posts()) : ?>
                    <?php while ($featured_query->have_posts()) : $featured_query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>">
                            <span class="ledger-title">
                                <strong><?php the_title(); ?></strong>
                                <em><?php echo esc_html(atlas_family_name(atlas_family_slug(get_the_ID()))); ?></em>
                            </span>
                            <span><?php echo esc_html(get_post_meta(get_the_ID(), 'atlas_kernel', true) ?: '—'); ?></span>
                            <span><?php echo esc_html(get_post_meta(get_the_ID(), 'atlas_version', true) ?: '—'); ?></span>
                            <b>↗</b>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <p><?php echo esc_html(atlas_t('catalog.empty_title')); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="editorial-method">
        <div class="site-shell">
            <header class="editorial-section-header">
                <h2><?php echo esc_html(atlas_t('home.method_title')); ?></h2>
            </header>
            <div class="method-columns">
                <article><h3><?php echo esc_html(atlas_t('method.architecture_title')); ?></h3><p><?php echo esc_html(atlas_t('method.architecture_desc')); ?></p></article>
                <article><h3><?php echo esc_html(atlas_t('method.experience_title')); ?></h3><p><?php echo esc_html(atlas_t('method.experience_desc')); ?></p></article>
                <article><h3><?php echo esc_html(atlas_t('method.ecosystem_title')); ?></h3><p><?php echo esc_html(atlas_t('method.ecosystem_desc')); ?></p></article>
            </div>
        </div>
    </section>

    <section class="journal-section">
        <div class="site-shell journal-layout">
            <header>
                <h2><?php echo esc_html(atlas_t('home.journal_title')); ?></h2>
            </header>
            <div class="journal-ledger">
                <?php if ($latest_posts->have_posts()) : ?>
                    <?php while ($latest_posts->have_posts()) : $latest_posts->the_post(); ?>
                        <article>
                            <div>
                                <span><?php
                                    $fslug = atlas_family_slug(get_the_ID());
                                    echo esc_html($fslug ? atlas_family_name($fslug) : atlas_t('blog.general'));
                                ?></span>
                                <time><?php echo esc_html(get_the_date('d.m.Y')); ?></time>
                            </div>
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20, '...')); ?></p>
                            <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(atlas_t('blog.read_more')); ?>">↗</a>
                        </article>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <p><?php echo esc_html(atlas_t('blog.empty_title')); ?></p>
                <?php endif; ?>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>" class="underlined-link all-journal-link"><?php echo esc_html(atlas_t('sections.all_posts')); ?> <span>↗</span></a>
            </div>
        </div>
    </section>

    <section class="home-search" aria-labelledby="home-search-title">
        <div class="site-shell">
            <h2 id="home-search-title"><?php echo esc_html(atlas_t('closing.title')); ?></h2>
            <p><?php echo esc_html(atlas_t('closing.description')); ?></p>
            <form class="home-search-form" role="search" method="get" action="<?php echo esc_url(atlas_home_url()); ?>">
                <div class="search-field">
                    <?php echo atlas_icon('search'); ?>
                    <label for="home-search-input" class="sr-only"><?php echo esc_html(atlas_t('nav.search')); ?></label>
                    <input id="home-search-input" type="search" name="s" placeholder="<?php echo esc_attr(atlas_t('search.input_placeholder')); ?>" required>
                </div>
                <button type="submit" class="button button-ink"><?php echo esc_html(atlas_t('closing.button')); ?></button>
            </form>
        </div>
    </section>

<?php get_footer(); ?>
