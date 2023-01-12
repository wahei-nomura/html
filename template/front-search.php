<?php
/**
 * template/front-search.php
 *
 * @package neoneng
 */

?>
<section class="product-sidebar">
	<div class="accordion">
	<div class="accordion-item">
	<p class="accordion-header" id="headingOne"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        検索
      </button>
</p>
<div id="collapseOne" class="accordion-collapse collapse<?php if(!wp_is_mobile()){ echo ' show';} ?>" aria-labelledby="headingOne" data-bs-parent="#accordionExample">

	<?php
		$home_url = get_home_url() . '/';
	?>

	<form method="get" action="<?php echo $home_url; ?>" class="n2-search-form">

  <div class="mb-3">
	<label for="inputFreeword" class="form-label">フリーワード検索</label>
	<input type="text" class="s form-control" id="inputFreeword" name="s" placeholder="キーワードを入力" value="<?php echo empty( $_GET['s'] ) ? '' : $_GET['s']; ?>">
  </div>
  <div class="mb-3 jigyousa-search-wrap">
  <?php
			// 事業者検索 ===============================================================
			$show_author         = '';
			$get_jigyousya_id    = filter_input( INPUT_GET, 'jigyousya', FILTER_VALIDATE_INT );
			$get_henreihin_codes = filter_input( INPUT_GET, '返礼品コード', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	?>

	<label for="exampleInputPassword1" class="form-label">出品事業者</label>
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

		<input type='text' name='' class="form-control" id='jigyousya-list-tag' list='jigyousya-list' value='<?php echo $show_author; ?>' placeholder='事業者入力'>
		<input id='jigyousya-value' type='hidden' name='jigyousya' value='<?php echo $get_jigyousya_id; ?>'>

	</div>
	<div class="mb-3 d-none" id='search-code-list'>
		<label class="form-label">返礼品コード</label>
		<select name="返礼品コード[]" class="form-select search-code-list" multiple></select>
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
	<div class="mb-3">
		<label class="form-label">並び順選択</label><br>
		<div class="form-check">
			<?php 
				$sort_key = 'sortbyday'; // 並び替え初期値は登録順
				if( !empty($_GET['sortcode']) ){
					$sort_key = $_GET['sortcode'];
				}
			?>
			<input class="form-check-input" type="radio" name="sortcode" id="flexRadioDefault1" value="sortbyday" <?php checked($sort_key, "sortbyday"); ?>>
			<label class="form-check-label" for="flexRadioDefault1">
				登録順
			</label>
			</div>
			<div class="form-check">
			<input class="form-check-input" type="radio" name="sortcode" id="flexRadioDefault2" value="sortbycode" <?php checked($sort_key, "sortbycode"); ?>>
			<label class="form-check-label" for="flexRadioDefault2">
				コード順
			</label>
			</div>	
	</div>
	<div class="mb-3">
	<button type="submit" class="btn btn-primary">絞り込み</button>
	</div>
	<div class="mb-3 mb-sm-0">
	<button type="reset" class="btn btn-secondary front-search-clear">条件クリア</button>
	</div>
	</form>
			</div><!--accordion-collapse-->
	</div><!--accordion-item-->
			</div><!--accordion-->
</section>
