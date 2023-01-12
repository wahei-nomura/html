<section class="portal-links">
    <h2 class="">各ポータルサイト</h2>
    <ul class="link-list">
        <li class="link-error">
            <span>現在各ポータルサイト登録作業中です</span>
        </li>
        <?php foreach ( $args as $portal => $param ) : ?>
            <?php if ( $param['item_url'] ) : ?>
            <li class="link-btn">
                <a href="<?php echo $param['item_url']; ?>" target="_blank"><?php echo $portal; ?></a>
            </li>
            <?php else : ?>
                <li class="link-btn disable">
                    <span><?php echo $portal; ?></span>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</section>