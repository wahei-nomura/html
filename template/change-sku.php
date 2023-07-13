<?php
	/**
	 * template/change-sku.php
	 *
	 * @package neoneng
	 */

?>

<!DOCTYPE html>

<style>
	#n2-change-sku-modal {
		padding: 24px;
	}
</style>

<div id="n2-change-sku-modal-wrapper">
	<div id="n2-change-sku-modal" class="media-modal-content">
		<h1>item.csvとselect.csvからnormal-item.csvを作るモノ</h1>
		<h2>【ナニコレ？】</h2>
		<p>7/12(水)より一部自治体で始まる楽天SKU化に伴い、これまで使っていたitem.csv、select.csvに変わる「normal-item.csv」を作成するモノです。<br>ゆくゆくはN2上でサクッと出力できるようにする予定ですが、それまでのつなぎとして使っていただければ幸いです。</p>
		<h2>【手順】</h2>
		<ol>
			<li>N1なりN2で作成したitem.csvとselect.csvを同時にアップロードしてください。(ファイル選択時にCtrlを押しながらで複数ファイルを選択できます)<br>※ファイル名を認識して処理するので「item」と「select」は必ずファイル名に入れておいてください</li>
			<li>「実行！」ボタンを押すとnormal-item.csvが勝手にダウンロードされます。</li>
		</ol>
		<form action="/wp-admin/admin-ajax.php?action=n2_change_sku_firstaid&mode=debug" target="_blank" method="post" enctype="multipart/form-data">
				<input name="item_files[]" type="file" multiple="multiple">
				<input type="submit" class="button" value="実行！">
		</form>

	</div>
</div>
