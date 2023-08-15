<?php
/**
 * SFTP CLIENT VIEW
 *
 * @package neo-neng
 */

$folders = N2_RMS_Cabinet_API::ajax(
	array(
		'call' => 'folders_get',
		'mode' => 'func',
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
	<ul <?php echo ( null !== $path ) ? ' class="d-none"' : ''; ?>>
	<?php foreach ( $parent as $li => $child ) : ?>
		<?php
			$dir    = null !== $path ? "{$path}/{$li}" : '';
			$index  = array_search( $dir ?: '/', array_column( $folders, 'FolderPath' ), true );
			$folder = $folders[ $index ];
		?>
		<?php if ( $child ) : ?>
		<li class="hasChildren">
		<?php else : ?>
		<li>
		<?php endif; ?>
			<label class='folder-open'>
				<input name='folder-open' type="checkbox">
			</label>
			
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

global $n2;

?>
<div id="ss-cabinet" class="container">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<div class="d-flex justify-content-between pb-1">
		<h2>CABINET</h2>
		<form id="cabinet-search" class="d-flex col-5 p-1">
			<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
			<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
			<input type="hidden" name="mode" value="json">
			<input type="hidden" name="call" value="files_search">
			<input class="form-control me-2" type="search" name="keywords" placeholder="キーワード" aria-label="キーワード">
			<button id="cabinet-search-btn" class="btn btn-outline-success" style="text-wrap:nowrap;">検索</button>
		</form>
	</div>
	<div class="row row-cols-1 row-cols-md-2  border-top border-dark">
		<aside class='cabinet-aside overflow-auto col-3'>
			<nav class="d-flex justify-content-around">
				<button class="btn btn-outline-secondary btn-sm" type="button" name="folder_insert" data-bs-toggle="modal" data-bs-target="#folderInsertModal">新規作成</button>
				<form>
					<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
					<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
					<input type="hidden" name="mode" value="json">
					<input type="hidden" name="call" value="trashbox_files_get">
					<button id="show-trashbox-btn" class="btn btn-outline-warning btn-sm" type="button" name="trashbox_files_get">ゴミ箱を確認</button>
				</form>
			</nav>
			<div class="tree overflow-auto">
				<?php $tree_list( $tree ); ?>
			</div>
		</aside>
		<main class="col-9 border-start border-dark overflow-auto">
			<nav class="navbar navbar-light bg-light position-sticky top-0 start-0 flex-nowrap align-items-strech">
				<div class="navbar-brand me-0" id="current-direcotry">基本フォルダ</div>
				<div class="container-fluid">
					<form>
						<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
						<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
						<input type="hidden" name="mode" value="json">
						<input type="hidden" name="call" value="">
						<div>
							選択した画像を
							<button id="cabinet-navbar-btn" class="btn btn-warning rounded-pill ps-5 pe-5 pt-1 pb-1" name="file_delete">削除</button>
						</div>
					</form>
					<div>
						<label>
							<input class="grid-radio view-radio" type="radio" name="view-mode" value="1" hidden checked>
							<i class="radio-icon bi bi-grid-3x2-gap-fill"></i>
						</label>
						<label>
							<input class="list-radio view-radio" type="radio" name="view-mode" value="2" hidden>
							<i class="radio-icon bi bi-list-task"></i>
						</label>
					</div>
				</div>
			</nav>
			<div id="ss-cabinet-images" class="pb-3 position-relative d-flex align-content-start justify-content-start align-items-start flex-wrap"></div>
			<div id="ss-cabinet-lists" class="d-none">
				<table class="table align-middle lh-1">
					<thead>
						<tr>
							<th scope="col"><input type="checkbox" name="selectedAll"></th>
							<th scope="col">画像</th>
							<th scope="col">ファイル名<i class="bi bi-caret-down"></i></th>
							<th scope="col">サイズ<i class="bi bi-caret-down"></i></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<div class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light">
				ファイルをドラッグ&ドロップしてください
			</div>
		</main>
	</div>
	<div id="card-template" style="display:none;">
		<div class="card shadow me-2">
			<div class="card-header d-flex align-items-center justify-content-between">
				<input type="checkbox" name="selected">
				<span class="card-text"></span>
			</div>
			<img src="" class="card-img-top" alt="" data-bs-toggle="modal" data-bs-target="#CabinetModal" role="button" decoding=“async”>
			<div class="card-img-overlay text-center">
				<h6 class="card-title text-truncate"></h6>
				<p class="card-text">フォルダは空です。</p>
			</div>
		</div>
	</div>
	<div class="modal fade" id="CabinetModal" tabindex="-1" aria-labelledby="CabinetModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content" data-bs-dismiss="modal">
				<div class="modal-body">
					<img src="" class="img-fluid" id="CabinetModalImage" data-bs-dismiss="modal" aria-label="Close" />
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="folderInsertModal" tabindex="-1" aria-labelledby="folderInsertModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<form>
						<div class="d-none">
							<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
							<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
							<input type="hidden" name="mode" value="json">
							<input type="hidden" name="call" value="folder_insert">
							<input type="hidden" name="upperFolderId" value="">
						</div>
						<div class="input-group pb-2">
							<span class="input-group-text">フォルダ名</span>
							<input type="text" class="form-control" placehodler="directoryName" name="directoryName">
						</div>
						<div class="input-group pb-2">
							<span class="input-group-text">表示名</span>
							<input type="text" class="form-control" placehodler="folderName" name="folderName">
						</div>
						<div class="d-flex pb-2">
							<button class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">フォルダを作成</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<form action="<?php echo esc_url( $n2->ajaxurl ); ?>" method="POST" enctype="multipart/form-data" style="display:none;">
		<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
		<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
		<input type="hidden" name="mode" value="json">
		<input type="hidden" name="call" value="file_insert">
		<input type="hidden" name="folderId" value="">
		<input type="file" multiple="multiple" name="cabinet_file[]">
		<input type="submit" value="リクエストを送信">
	</form>
</div>
