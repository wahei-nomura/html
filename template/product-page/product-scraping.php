<section class='portal-scraper'>
    <h2 class="mordal-btn">ポータル比較</h2>
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
            <?php $args_th = array( '寄付額', '納期', '在庫' ); ?>
            <?php foreach ( $args_th as $th ) : ?>
            <tr>
                <th><?php echo $th; ?></th>
                <?php foreach ( $args as $portal => $params ) : ?>
                    <?php if ( '寄付額' === $th ) : ?>
                        <td class="price"><?php echo number_format( $params[ $th ] ); ?></td>
                    <?php else : ?>
                        <td><?php echo $params[ $th ]; ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>