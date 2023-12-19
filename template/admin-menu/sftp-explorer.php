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

// フラット化
echo '<pre>';
var_dump( $tree );
echo '</pre><br>';

?>
<h3>SFTPサーバー</h3>
<div class="row border-top border-dark" style="height: calc(100vh - 205px);">
	<aside id="n2_sftp_explorer__left-aside" class="col-3">
		<ul class="n2-tree-parent">
			<li class="n2-tree-node">
				<label>
					<span class="dashicons dashicons-open-folder"></span>
					<span data-path="/">root</span>
					<input type="checkbox">
				</label>
				<?php build_tree_ul_element( $tree ); ?>
			</li>
		</ul>
	</aside>
	<main class="col-9 border-start border-dark">
		main
	</main>
</div>
<style>
	.n2-tree-parent {
		padding-left: 1rem;
	}
	.n2-tree-node {
		position: relative;
	}
	.n2-tree-node > label:not(:has(:checked))~ul {
		display: none;
	}
	.n2-tree-node > label > span:not(.dashicons):before {
		border: 2px solid #000;
		border-left: none;
		border-top: none;
		content: "";
		position: absolute;
		left: -10px;
		top: 0%;
		transform: translate(-50%,100%) rotate(-45deg);
		transition: .2s;
		width: 9px;
		height: 9px;
	}
	.n2-tree-node > label > [type="checkbox"] {
		display: none;
	}
	.n2-tree-node > label:has(:checked) > span:not(.dashicons):before {
		transform: translate(-50%,100%) rotate(45deg);
	}
</style>
