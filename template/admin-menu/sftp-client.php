<?php
/**
 * SFTP CLIENT VIEW
 *
 * @package neo-neng
 */

$folders = N2_RMS_Cabinet_API::ajax(
	array(
		'request' => 'folders_get',
		'mode'    => 'func',
	),
);

if ( ! $folders ) {
	echo 'RMS CABINETに接続できませんでした。';
	die();
}
$tree = array();

// sort
usort(
	$folders,
	function( $a, $b ) {
	return $a['FolderPath'] > $b['FolderPath'];
	}
);

$folders = array_map(
	function( $folder ) {
	$folder['FolderPath'] = explode( '/', ltrim( $folder['FolderPath'], '/' ) );
	return $folder;
	},
	$folders
);

$root = array_shift( $folders );

// 再帰的にtreeを生成
$build_tree = function ( &$parent, $path ) use ( &$build_tree ) {
	$p            = array_shift( $path );
	$parent[ $p ] = $parent[ $p ] ?? array();
	if ( $path ) {
		$build_tree( $parent[ $p ], $path );
	}
};

$folders = array_map(
	function( $f ) use ( &$tree, $build_tree ) {
	$build_tree( $tree, $f['FolderPath'] );

	$f['FolderPath'] = '/' . implode( '/', $f['FolderPath'] );
	return $f;
	},
	$folders
);

$root['FolderPath'] = '/' . implode( '/', $root['FolderPath'] );

$tree = array(
	$root['FolderName'] => $tree,
);

$folders = array( ...$folders, $root );


$tree_list = function ( $parent, $path = null ) use ( &$tree_list, $folders ) {
	?>
	<ul <?php echo ( null !== $path ) ? ' class="invisible"' : ''; ?>>
	<?php foreach ( $parent as $li => $child ) : ?>
		<?php
			$dir    = null !== $path ? "{$path}/{$li}" : '';
			$index  = array_search( $dir ?: '/', array_column( $folders, 'FolderPath' ), true );
			$folder = $folders[ $index ];
		?>
		<?php if ( $child ) : ?>
		<li class="hasChirdlen">
		<?php else : ?>
		<li>
		<?php endif; ?>
			<span data-path="<?php echo esc_attr( $dir ?: '/' ); ?>" data-id="<?php echo esc_attr( $folder['FolderId'] ); ?>">
				<i class="bi bi-folder2-open close"></i><?php echo esc_html( $folder['FolderName'] ); ?>
			</span>
		<?php if ( $child ) : ?>
			<?php $tree_list( $child, $dir ); ?>
		<?php endif; ?>
		</li>        
	<?php endforeach; ?>
	</ul>
	<?php
};

?>
<div id="ss-cabinet" class="container">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<h2>CABINET</h2>
	<div class="row row-cols-1 row-cols-md-2  border-top border-dark">
		<div class="tree overflow-auto col-3">
			<?php $tree_list( $tree ); ?>
		</div>
		<div id="ss-cabinet-images" class="position-relative overflow-auto border-start border-dark col-9 d-flex align-content-between justify-content-start align-items-start flex-wrap"></div>
	</div>
	<div id="card-template" style="display:none;">
		<div class="card shadow text-center me-2">
			<img src="" class="card-img-top" alt="" data-bs-toggle="modal" data-bs-target="#CabinetModal" role="button" decoding=“async”>
			<div class="card-body">
				<h6 class="card-title text-truncate"></h6>
				<p class="card-text">フォルダは空です。</p>
			</div>
		</div>
	</div>
	<div class="modal fade" id="CabinetModal" tabindex="-1" aria-labelledby="CabinetModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-fullscreen">
			<div class="modal-content" data-bs-dismiss="modal">
				<div class="modal-body  d-flex align-items-center justify-content-center">
					<img src="" class="img-fluid" id="CabinetModalImage" data-bs-dismiss="modal" aria-label="Close" />
				</div>
			</div>
		</div>
	</div>
	<div id="dragable-area-template" style="display:none;">
		<div class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light">
			ファイルをドラッグ&ドロップしてください
		</div>
	</div>
	<form action="">
		<input type="file" multiple="multiple" name="files">
	</form>
</div>
