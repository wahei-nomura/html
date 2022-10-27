<section class="product-info">
    <h2 class="mordal-btn">商品詳細ページ</h2>
    <div class="inner-info">
        <!-- 商品詳細 -->
        <?php 
            N2_Functions::get_template_part_with_args('template/product-page/product-detail','',$args);
        ?>
        <!-- 申込詳細 -->
        <?php 
            N2_Functions::get_template_part_with_args('template/product-page/product-application','',$args);
        ?>
    </div>
</section>