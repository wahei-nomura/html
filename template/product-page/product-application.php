<?php 
// プラグインn2-developのn2_setpost_show_customfields呼び出し
$n2_fields      = yaml_parse_file( get_theme_file_path() . '/config/n2-fields.yml' );
$fields       = apply_filters( 'n2_setpost_show_customfields', $n2_fields, 'default' );
?>
<h3 class="application">申込詳細</h3>
<table class="application tabList">
    <thead>
        <tr><th width="35%">項目</th><th width="65%">内容</th></tr>
    </thead>
    <tbody>
        <?php $application = array( '申込期間', '配送期間', 'のし対応', '取り扱い方法1', '取り扱い方法2', '発送方法', '発送サイズ' ); ?>
        <?php foreach ( $application as $key ) : ?>
        <tr>
            <th><?php echo $key; ?></th>
            <?php
            if ( 'checkbox' === $fields[ $key ]['type'] || 'select' === $fields[ $key ]['type'] ) :
                $new_options = $fields[ $key ]['option'];
                $cheked      = '';
                if ( 'checkbox' === $fields[ $key ]['type'] ) {
                    if ( ! empty( $fields[ $key ] ) ) {
                        foreach ( $fields[ $key ] as $chekedkey ) {
                            $cheked .= implode( ',', $new_options[ $chekedkey ] );
                        }
                    }
                } else {
                    $cheked = 'なし';
                }
            ?>
            <td><?php echo 'select' === $fields[ $key ]['type'] ? $new_options[ $args[ $key ] ] : $cheked; ?></td>
            <?php else : ?>
            <td><?php echo ! empty( $args[ $key ] ) ? nl2br( $args[ $key ] ) : '入力無し'; ?></td>
            <?php endif; ?>
        </tr>
        <?PHP endforeach; ?>
    </tbody>
</table>