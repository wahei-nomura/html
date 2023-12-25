<?php
/**
 * explorer
 *
 * @package neo-neng
 */

global $n2;

if ( ! $args['connect'] ) {
	echo '接続エラー';
	die;
}

/**
 * 木構造のディレクトリを作成
 */
function build_tree( $dirlist, $prefix ) {
	/**
	 * ディレクトリのみにフィルター
	 *
	 * @param array $l list
	 */
	$folder_list = fn( $l ) => array_filter( $l, fn( $d )=> 'd' === $d['type'] );
	return array_map(
		fn ( $v ) => array(
			'path'     => "{$prefix}/{$v['name']}",
			'children' => match ( count( $folder_list( $v['files'] ) ) > 0 ) {
				true => build_tree( $v['files'], "{$prefix}/{$v['name']}" ),
				default => array(),
			},
		),
		$folder_list( $dirlist ),
	);
}

function build_tree_ul_element( $tree ) {
	?>
	<ul class="n2-tree-parent">
	<?php foreach ( $tree as $node => $val ) : ?>
		<li class="n2-tree-node">
			<?php if ( count( $val['children'] ) ) : ?>
			<label>
				<span class="dashicons dashicons-open-folder"></span>
				<span>
					<?php echo $node; ?>
				</span>
				<input type="checkbox">
			</label>
			<?php build_tree_ul_element( $val['children'] ); ?>
			<?php else : ?>
				<label>
					<span class="dashicons dashicons-open-folder"></span>
					<?php echo $node; ?>
				</label>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * array flatten
 *
 * @param array $arr array
 */
function array_flatten( $arr ) {
	$it       = new RecursiveArrayIterator( $arr );
	$iterator = new RecursiveIteratorIterator( $it );
	return iterator_to_array( $iterator, false );
}

$tree = build_tree( $args['dirlist'], '' );

?>
<div id="n2-sftp-explorer">
	<?php wp_nonce_field( 'n2nonce', 'n2nonce' ); ?>
</div>

