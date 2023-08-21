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

$cabinet_usage = N2_RMS_Cabinet_API::ajax(
	array(
		'call' => 'usage_get',
		'mode' => 'func',
	),
)->cabinetUsageGetResult;

$cabinet_max_space = round( floatval($cabinet_usage->MaxSpace) / 1000, 1 );
$cabinet_avail_space = round( floatval($cabinet_usage->AvailSpace) / 1000 / 1000, 1 );

$use_sapace_rate =  ( 1 - $cabinet_avail_space / $cabinet_max_space ) * 100;

global $n2;

?>
<div id="ss-cabinet" class="container-fluid">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
		<main class="border-start border-dark overflow-auto col-9">
			<nav class="navbar navbar-light bg-light position-sticky top-0 start-0 align-items-strech">
				<div class="navbar-brand" id="current-direcotry">基本フォルダ</div>
				<div class="navbar-text me-auto" id="file-count"></div>
				<div class="d-flex ms-auto">
					<div class="d-flex align-items-center">
						選択した画像を
						<div class="btn-group" role="group">
							<button id="cabinet-navbar-btn-move" class="btn btn-outline-secondary rounded-pill px-4 py-0" type="button" name="file_move" data-bs-toggle="modal" data-bs-target="#fileMoveModal">移動</button>
							<form>
								<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
								<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
								<input type="hidden" name="mode" value="json">
								<input type="hidden" name="call" value="">
								<button id="cabinet-navbar-btn" class="btn btn-outline-warning rounded-pill px-4 py-0" name="file_delete">削除</button>
							</form>
							<form action="<?php echo esc_url( $n2->ajaxurl ); ?>" method="POST" enctype="multipart/form-data">
								<input type="hidden" name="action" value="n2_download_multiple_image_by_url">
								<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
								<button id="cabinet-navbar-btn-dl" class="btn btn-outline-secondary rounded-pill px-4 py-0" name="file_download">DL</button>
							</form>
							</div>
						</div>
					<div class="px-3">
						<label>
							<input class="grid-radio view-radio" type="radio" name="view-mode" value="1" hidden checked>
							<i class="radio-icon bi bi-grid-3x2-gap-fill fs-4" style="transform: translateX(5px);"></i>
						</label>
						<label>
							<input class="list-radio view-radio" type="radio" name="view-mode" value="2" hidden>
							<i class="radio-icon bi bi-list-task fs-4"></i>
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
							<th scope="col">登録/変更日<i class="bi bi-caret-down"></i></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<div class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light">
				ファイルをドラッグ&ドロップで転送する
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
		</main>
		<aside id="right-aside" class="col-3 pt-3" style="display:none;">
			<div>
				<div class="progress">
					<div class="progress-bar" role="progressbar" style="width: <?php echo $use_sapace_rate; ?>%" aria-valuenow="<?php echo $use_sapace_rate; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $use_sapace_rate; ?>%</div>
				</div>
				<div class="text-end" style="font-size: .8rem;">空き容量 : <?php echo $cabinet_avail_space; ?>GB / <?php echo $cabinet_max_space; ?>GB</div>
			</div>
			<div class="card p-0">
				<img id="right-aside-list-img" src="" class="card-img-top" alt="" data-bs-toggle="modal" data-bs-target="#CabinetModal" role="button" decoding=“async”>
				<div class="card-body p-0">
					<ul id="right-aside-list" class="list-group list-group-flush">
						<li class="list-group-item" data-label="画像名" data-key="FileName">
							画像作成テンプレ
						</li>
						<li class="list-group-item" data-label="ファイル名" data-key="FilePath">
							imgrc0081597574.jpg
						</li>
						<li class="list-group-item" data-label="登録/変更日" data-key="TimeStamp">
							2023/08/10
						</li>
						<li class="list-group-item" data-label="サイズ" data-key="FileSize">
							700x700 / 304KB
						</li>
						<li class="list-group-item d-flex align-items-center justify-content-between" data-label="画像保存先" data-key="FileUrl">
							<button type="button" class="url-clipboard btn btn-secondary">
								<i class="bi bi-clipboard"></i>
							</button>
						</li>
					</ul>
				</div>
			</div>
		</aside>
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
						<div>
							<h4>新規作成</h4>
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
	<div class="modal fade" id="fileMoveModal" tabindex="-1" aria-labelledby="fileMoveModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<form>
						<div class="d-none">
							<input type="hidden" name="action" value="n2_rms_cabinet_api_ajax">
							<input type="hidden" name="n2nonce" value="<?php echo esc_attr( wp_create_nonce( 'n2nonce' ) ); ?>">
							<input type="hidden" name="mode" value="json">
							<input type="hidden" name="call" value="file_move">
						</div>
						<div>
							<h4>フォルダ移動</h4>
						</div>
						<div class="input-group pb-2">
							<span class="input-group-text">移動元</span>
							<input type="hidden" name="currentFolderId" value="">
							<input type="text" name="currentFolderName" value="" readonly="readonly">
						</div>
						<div class="input-group pb-2">
							<span class="input-group-text">移動先</span>
							<input type="hidden" name="targetFolderId" value="">
							<datalist id='folders' >
								<?php foreach ( $folders as $folder ) : ?>
									<option value="<?php echo $folder['FolderName'];?>"></option>
								<?php endforeach; ?>
							</datalist>
							<input type="text" name="targetFolderName" list="folders">
						</div>
						<div class="d-flex pb-2">
							<button class="btn btn-secondary flex-fill" type="submit" data-bs-dismiss="modal">移動する</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
