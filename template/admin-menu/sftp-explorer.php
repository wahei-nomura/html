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
		fn ( $v ) => match ( count( $folder_list( $v['files'] ) ) > 0 ) {
			true => build_tree( $v['files'], "{$prefix}/{$v['name']}" ),
			default => "{$prefix}/{$v['name']}",
		},
		$folder_list( $dirlist ),
	);
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

// フラット化
echo '<pre>';
var_dump( array_flatten( build_tree( $args['dirlist'], '' ) ) );
echo '</pre><br>';

?>
<h3>SFTPサーバー</h3>
