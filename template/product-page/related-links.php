<?php if ( get_the_author_meta('user_url') || $args ) : ?>
<section class="related-links">
    <h2>関連リンク</h2>
    <ul class="link-list">
        <li class="link-btn">
            <?php if ( get_the_author_meta('user_url') ) : ?>
                <a href="<?php the_author_meta('user_url'); ?>" target="_blank">
                    <?php the_author(); ?>
                </a>
            <?php endif; ?>
        </li>
        <?php foreach( $args as $name =>$url ) : ?>
        <li class="link-btn">
            <a href="<?php $url;?>" target="_blank">
                <?php echo $name; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>