<?php 
// プラグインn2-developのn2_setpost_show_customfields呼び出し
$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
$fields       = apply_filters( 'n2_setpost_show_customfields', $n2_fields, 'default' );
?>
<h3 class='detail'>商品詳細</h3>
<table class="detail tabList">
    <thead>
        <tr><th width="35%">項目</th><th width="65%">内容</th></tr>
    </thead>
    <tbody>
        <?php $product_detail = array( '価格', '内容量・規格等', 'アレルゲン', 'アレルゲン注釈', '賞味期限', '消費期限', '限定数量' ); ?>
        <?php foreach ( $product_detail as $key ) : ?>
        <?php
        if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
            $options = $fields[ $key ]['option'];
            // var_dump($options);
            // foreach ( $options as $option ) {
            // }
            $checked = '';
            if ( 'checkbox' === $fields[ $key ]['type'] ) {
                if ( ! empty( $args[ $key ] ) ) {
                    $checked_arr = array_filter(
                        $fields[ $key ]['option'],
                        fn( $value) => in_array( array_search( $value, $fields[ $key ]['option'] ), $args[ $key ] )
                    );
                    $checked    .= implode( ',', $checked_arr );
                }
            } else {
                $checked = 'なし';
            };
            ?>
        <tr>
            <th><?php echo $key; ?></th>
            <td><?php echo 'select' === $fields[ $key ]['type'] ? $options[ $fields [ $key ] ] : $checked; ?></td>
        </tr>
        <?php else : ?>
            <tr>
            <th><?php echo $key; ?></th>
            <td><?php echo ! empty( $args[ $key ] ) ? nl2br( $args[ $key ] ) : '入力無し'; ?></td>
        </tr>
    <?php endif; ?>
    <?PHP endforeach; ?>
    </tbody>
</table>