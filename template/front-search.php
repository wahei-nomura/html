<?php
/**
 * template/front-search.php
 *
 * @package neoneng
 */

?>
<section class="product-sidebar">
	<?php 
		$home_url = get_home_url() . '/';
	?>

	<form method="get" action="<?php echo $home_url; ?>" class="n2-search-form">
	<p>フリーワード検索</p>
	<input type="text" class="s" name="s" placeholder="キーワードを入力">
	<p>出品事業者</p>
	<div>
		<?php
			// 事業者検索 ===============================================================
			$show_author      = '';
			$get_jigyousya_id = filter_input( INPUT_GET, 'jigyousya', FILTER_VALIDATE_INT );
			$get_henreihin_codes = filter_input(INPUT_GET, '返礼品コード', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		?>

		<datalist id="jigyousya-list">
			<?php foreach ( get_users( 'role=jigyousya' ) as $user ) : ?>
				<?php
					$author_id   = (int) $user->ID;
					$author_name = $user->display_name;
				if ( $author_id === $get_jigyousya_id ) {
					$show_author = $author_name;
				}
				?>
				<option value="<?php echo $author_name; ?>" data-id="<?php echo $author_id; ?>">
			<?php endforeach; ?>
		</datalist>

		<input type='text' name='' id='jigyousya-list-tag' list='jigyousya-list' value='<?php echo $show_author; ?>' placeholder='事業者入力'>
		<input id='jigyousya-value' type='hidden' name='jigyousya' value='<?php echo $get_jigyousya_id; ?>'>

		<p>返礼品コード</p>
		<select name="返礼品コード[]" class="search-code-list" multiple>
		</select>
		
		<?php
		// 返礼品コード検索
		/*
		echo '<select name="返礼品コード[]" multiple>';
		echo '<option value="">返礼品コード</option>';
		if ( empty( $_GET['事業者'] ) ) {
			$get_code = filter_input( INPUT_GET, '返礼品コード', FILTER_SANITIZE_ENCODED );
			$posts    = get_posts( 'post_status=any' );
			foreach ( $posts as $post ) {
				$code = get_post_meta( $post->ID, '返礼品コード', 'true' );
				if ( '' !== $code ) {
					printf( '<option value="%s">%s</option>', $post->ID, $code );
				}
			}
		}
		echo '</select>';*/
		?>
	</div>
	<?php
	/*
	<p>金額</p>
	<div class="n2-search-price-wrap">
	<input type="text" class="min-price" name="min-price" placeholder="MIN"><span class="n2-search-section">〜</span><input type="text" class="max-price" name="max-price" placeholder="MAX">
	</div> */
	?>
	<?php
	/*
	<p>登録日</p>
	<a href="">新しい順</a> <a href="">古い順</a>
	<p>事業者HP</p>
	<a href="">あり</a> <a href="">なし</a>
	*/
	?>
	<?php
	/*
	<p>ポータルサイト</p>
	<div class="front-portal-wrap n2-checkbox-wrap">
		<input type="checkbox" name="portal_rakuten" class="portalsite" id="portal_rakuten" value="1"><label for="portal_rakuten">楽天</label>
		<input type="checkbox" name="portal_choice" class="portalsite" id="portal_choice" value="1"><label for="portal_choice">チョイス</label>
		<input type="checkbox" name="portal_furunavi" class="portalsite" id="portal_furunavi" value="1"><label for="portal_furunavi">ふるなび</label>
	</div>*/
	?>
	<div class="front-move-wrap">
	<input type="submit" value="絞り込み">
	<a href="<?php echo $home_url; ?>">条件クリア</a>
	</div>

	</form>
</section>
