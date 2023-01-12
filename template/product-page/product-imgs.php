<?php if ( isset( $args ) && $args ) : ?>
<section class="product-imgs">
	<div class="img-box">
		<img class='main-img' src="<?php echo $args[0]; ?>" width='100%'>
	</div>
	<div class="wrapper-img-list">
		<ul class='sub-imgs'>
			<!-- <li><img id='sub-img-templete' class='sub-img' src="" width='100%' height="100%" style="display:none;"></li> -->
			<?php for ( $i = 0; $i < 2; $i++ ) : ?>
				<?php foreach ( $args as $img_url ) : ?>
					<li><img class='sub-img' src="<?php echo $img_url; ?>" width='100%' height="100%"></li>
				<?php endforeach; ?>
			<?php endfor; ?>
		</ul>
	</div>
</section>
<?php else : ?>
<section class="product-imgs">
	<div class="img-box">
		<div class="product-img-wrap">
			<span class="product-img-section">No Image</span>
		</div>
	</div>
</section>
<?php endif; ?>
