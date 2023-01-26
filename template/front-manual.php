<?php
/**
 * template/front-manual.php
 *
 * @package neoneng
 */

?>

<!-- Load sass.js -->
<script src="https://cdn.jsdelivr.net/gh/steamships/in-browser-sass/dist/in-browser-sass.bundle.min.js"></script>

<style type="text/scss">

</style>

<!-- Button trigger modal -->
<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">
  お読みください
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
	<div class="modal-content">
		<div class="modal-header">
		<h2 class="modal-title" id="exampleModalLabel">返礼品のご確認について</h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
			<div class="container">
				<section>
					<h3 class="border-bottom border-3 border-info p-3">確認していただきたいこと</h3>
					<figure class="figure">
						<img src="https://placehold.jp/3d4070/ffffff/900x600.png?text=image" class="figure-img img-fluid rounded" alt="...">
					<figcaption class="figure-caption">サンプル商品を表示しております</figcaption>
					</figure>
					<p>このページの商品が全て確認済みとなりましたら寄附受付を開始いたします。</p>
					<p>修正点等がございましたら、ご遠慮なくお申し付けくださいませ。</p>
					<p>各商品の詳細ページからも承認に関するご要望を投稿することができますのでぜひご活用ください。詳細ページ内にもマニュアルがございます。</p>
					<p class='fw-bold text-danger'>※寄附金額のご確認を必ずお願い致します。</p>
				</section>
			</div>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
		</div>
	</div>
	</div>
</div>
