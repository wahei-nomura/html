<?php
/**
 * 投稿IDを選択して保存するUI
 * 削除・印刷・エクスポート・画像ダウンロード
 *
 * @package neoneng
 */

/**
 * 出力バッファによる書き換え（[hook] n2_save_post_ids_html）
 * ※[コメント]はHTMLコメントにN2プラグインからフックで書き換えあり
 */
ob_start();
global $n2;
?>
<div id="n2-user-export">
	<!-- エクスポート -->
	<?php if ( current_user_can( 'ss_crew' ) ) : ?>
		<span id="n2-user-export-header">ユーザーエクスポート</span>
		<form method="post" action="admin-ajax.php" target="_blank">
			<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
			<input type="hidden" name="action" value="n2_user_export" v-model="fd.action">
			<!-- タイプ選択 -->
			<div style="margin-bottom: 1em;" v-if="fd.action.match(/lhcloud|ledghome/)">
				<label><input type="radio" name="type" value="jigyousya" checked>事業者</label>
				<label><input type="radio" name="type" value="ss-crew">ssクルー</label>
				<label><input type="radio" name="type" value="all_user">全ユーザー</label>
			</div>
			<!-- モード選択 -->
			<div style="margin-bottom: 1em;">
				<span>モード選択 ：　</span>
				<label><input type="radio" name="mode" value="download" v-model="fd.mode"> CSVダウンロード</label>
				<label><input type="radio" name="mode" value="spreadsheet" v-model="fd.mode"> スプレットシート貼付</label>
				<label><input type="radio" name="mode" value="debug" v-model="fd.mode"> デバッグモード</label>
			</div>
			<!-- 送信 -->
			<button>エクスポート実行</button>
		</form>
	<?php endif; ?>
</div>
<?php
