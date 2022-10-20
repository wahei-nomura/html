<section class="portal-links">
    <h2>各ポータルサイト</h2>
    <ul class="link-list">
    <?php foreach ( $args as $portal => $param ) : ?>
        <li class="link-btn">
            <a href="<?php echo $param['item_url']; ?>"><?php echo $portal; ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
</section>