<?php
/**
 * template/all-town.php
 *
 * @package neoneng
 */

?>

<div class="container pt-2 d-flex align-items-start">
	<button type="button" class="btn btn-danger shadow me-3"><a href="https://steamship.docbase.io/posts/2667466" target="_blank" class="link-light">マニュアルはこちら</a></button>
	<div>
		<p>ページは全て開発中のものです。急に仕様が変わる場合がございます。</p>
		<p>ご不明点は<span class="fw-bold text-info">#ヘルプデスク</span>で<span class="fw-bold text-warning">@エンジニア</span>まで！</p>
	</div>
</div>

<div class="container d-flex flex-wrap p-5 mt-5 rounded-3 bg-secondary shadow-lg">
	<?php
	foreach ( get_sites() as $site ) :
		$site_details = get_blog_details( $site->blog_id );
		if ( '1' !== $site->blog_id ) :
	?>
		<button type="button" class="btn btn-primary p-3 m-2 fw-bold fs-2 shadow"><a class="link-light" href="<?php echo $site_details->siteurl; ?>" target="_blank"><?php echo $site_details->blogname; ?></a></button>
	<?php
		endif;
	endforeach;
	?>
</div>
