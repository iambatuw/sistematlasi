    </main>

    <footer class="site-footer">
        <div class="site-shell footer-main">
            <div class="footer-intro">
                <a href="<?php echo esc_url(atlas_home_url()); ?>" class="wordmark"><?php echo esc_html(atlas_t('brand_name')); ?></a>
                <p><?php echo esc_html(atlas_t('footer.summary')); ?></p>
            </div>
            <div class="footer-column">
                <h2><?php echo esc_html(atlas_t('footer.explore')); ?></h2>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os')); ?>"><?php echo esc_html(atlas_t('nav.os')); ?></a>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_article')); ?>"><?php echo esc_html(atlas_t('nav.blog')); ?></a>
                <a href="<?php echo esc_url(atlas_search_url()); ?>"><?php echo esc_html(atlas_t('nav.search')); ?></a>
            </div>
            <div class="footer-column">
                <h2><?php echo esc_html(atlas_t('footer.families')); ?></h2>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . '?family=linux'); ?>">Linux</a>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . '?family=windows'); ?>">Windows</a>
                <a href="<?php echo esc_url(atlas_archive_url('atlas_os') . '?family=macos'); ?>">macOS</a>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
