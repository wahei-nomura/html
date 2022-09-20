<?php
/**
 * template/front-search.php
 *
 * @package neoneng
 */

?>
<section class="product-sidebar">
	<form method="get">
	<p>【探す】</p>
	<input type="text" class="s" name="s" placeholder="キーワードを入力">
	<?php /*<p>金額</p>
	<select name="price" class="price" id="">
		<option value="1">0〜5,000</option>
		<option value="2">5,001〜10,000</option>
		<option value="3">10,001〜50,000</option>
		<option value="4">50,001〜100,000</option>
		<option value="5">100,001〜</option>
	</select>
	<p>登録日</p>
	<a href="">新しい順</a> <a href="">古い順</a>
	<p>事業者HP</p>
	<a href="">あり</a> <a href="">なし</a>
	*/ ?>
	<p>ポータルサイト</p>
	<label for="portal_rakuten"><input type="checkbox" name="portal_rakuten" class="portalsite" id="portal_rakuten" value="1">楽天</label>
	<label for="portal_choice"><input type="checkbox" name="portal_choice" class="portalsite" id="portal_choice" value="1">チョイス</label>
	<label for="portal_furunavi"><input type="checkbox" name="portal_furunavi" class="portalsite" id="portal_furunavi" value="1">ふるなび</label>
	<div class="front-submit-wrap">
	<input type="submit" value="絞り込み">
	</div>
	</form>
</section>