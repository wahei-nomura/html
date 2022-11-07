<section class="portal-links">
    <h2 class="">各ポータルサイト</h2>
    <ul class="link-list">
    <?php foreach ( $args as $portal => $param ) : ?>
        <li class="link-btn">
            <a href="<?php echo $param['item_url']; ?>" target="_blank"><?php echo $portal; ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
</section>