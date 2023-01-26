<!-- 404.php -->
<?php
if(isset($e_state) && $e_state == "事業者の返礼品が存在しない"){
?>
    <div id="main">
        <h2>返礼品が見つかりませんでした</h2>
        <p>返礼品が登録されていないか、入力内容が間違っている可能性があります。</p>
        <p><a href="<?php echo home_url(); ?>" class="btn btn-warning">トップページへ</a></p>
    </div>
<?php
}else{
?>
    <?php get_header(); ?>
    <div id="main" style="text-align:center;margin-top:10vh;">
        <h1>ページが見つかりませんでした</h1>
        <p>このページは削除されたか、変更された可能性があります。</p>
        <p><a href="<?php echo home_url(); ?>" class="btn btn-warning">トップページへ</a></p>
    </div>
<?php
}
?>
