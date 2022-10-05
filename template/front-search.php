<?php
/**
 * template/front-search.php
 *
 * @package neoneng
 */

?>
<section class="product-sidebar">
	<form method="get" class="n2-search-form">
	<p>【探す】</p>
	<input type="text" class="s" name="s" placeholder="キーワードを入力">
	<p>金額</p>
	<input type="text" class="min-price" name="min-price" placeholder="MIN"><span>〜</span><input type="text" class="max-price" name="max-price" placeholder="MAX">
	<?php /*<p>登録日</p>
	<a href="">新しい順</a> <a href="">古い順</a>
	<p>事業者HP</p>
	<a href="">あり</a> <a href="">なし</a>
	*/ ?>
	<p>ポータルサイト</p>
	<div class="front-portal-wrap n2-checkbox-wrap">
		<input type="checkbox" name="portal_rakuten" class="portalsite" id="portal_rakuten" value="1"><label for="portal_rakuten">楽天</label>
		<input type="checkbox" name="portal_choice" class="portalsite" id="portal_choice" value="1"><label for="portal_choice">チョイス</label>
		<input type="checkbox" name="portal_furunavi" class="portalsite" id="portal_furunavi" value="1"><label for="portal_furunavi">ふるなび</label>
	</div>
	<div class="front-submit-wrap">
	<input type="submit" value="絞り込み">
	</div>
	</form>
</section>