<?php if ( get_the_author_meta('user_url') || $args ) : ?>
<section class="related-links">
    <h2>関連リンク</h2>
    <ul class="link-list">
        <li class="link-btn"><a href="<?php the_author_meta('user_url'); ?>">
            <?php the_author(); ?>
        </a></li>
    </ul>
</section>
<?php endif; ?>