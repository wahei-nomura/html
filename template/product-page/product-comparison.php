<?php
    // 比較で表示する見出し一覧
    $args_th = array( '寄付額', '納期', '在庫' );
	
    // 田代出来てるポータルのみに絞る
    foreach ( $args as $portal => $params) {
        if ( $params['status'] === 'NG' ){
            unset( $args[ $portal ] );
        }
    }
?>
<section class='portal-scraper'>
    <h2 class="mordal-btn">主要ポータル比較</h2>
    <table border="1" style="display:none;" class="is-block">
        <thead>
            <tr>
                <th>-</th>
                <?php foreach ( $args as $portal => $params ) : ?>
                    <th><?php echo isset( $portal ) ? $portal : 'unknown'; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $args_th as $th ) : ?>
            <tr>
                <th><?php echo $th; ?></th>
                <?php foreach ( $args as $portal => $params ) : ?>

                    <?php if ( ! isset($params['params'][ $th ]) ) : ?>
                        <td></td>
                    <?php elseif ( '寄付額' === $th && is_numeric($params['params'][ $th ]) ) : ?>
                        <td class="price"><?php echo number_format( $params['params'][ $th ] ); ?></td>
                    <?php elseif ( '寄付額' === $th  ) : ?>
                        <td class="price"><?php echo $params['params'][ $th ]; ?></td>
                    <?php else : ?>
                        <td><?php echo $params['params'][ $th ]; ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>