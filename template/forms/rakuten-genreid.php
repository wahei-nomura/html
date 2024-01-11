<?php
/**
 * form rakuten-genreid
 * 楽天 全商品ディレクトリID
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$attr     = '';
foreach ( $args as $k => $v ) {
	$v     = esc_attr( $v );// エスケープしないとバグる
	$attr .= " {$k}=\"{$v}\"";
}
?>
<!-- 最後まで選択されてないとID表示されないようにする -->
<input :type="tmp.楽天ジャンルID.children && tmp.楽天ジャンルID.children.length > 0 ? 'password' : 'number'" <?php echo $attr; ?> @focus="set_info($event.target);get_genreid()" @change="get_genreid()">
<ol class="breadcrumb m-0 mt-1">
	<li v-if="tmp.楽天ジャンルID.current" class="breadcrumb-item">
		<span 
			style="text-decoration: underline;"
			role="button"
			@click="全商品ディレクトリID = 0;get_genreid();"
			v-text="`楽天ジャンルID`"
		></span>
	</li>
	<li class="breadcrumb-item" v-for="v in tmp.楽天ジャンルID.parents">
		<span
			style="text-decoration: underline;"
			role="button"
			@click="全商品ディレクトリID = v.parent.genreId;get_genreid();"
			v-text="v.parent.genreName"
		></span>
	</li>
	<li v-if="tmp.楽天ジャンルID.current" class="breadcrumb-item">
		{{tmp.楽天ジャンルID.current.genreName}}
	</li>
</ol>
<div v-if="tmp.楽天ジャンルID.children">
	<div>
		<span 
			v-for="v in tmp.楽天ジャンルID.children"
			class="btn btn-dark btn-sm me-1 mb-1 py-0"
			@click="全商品ディレクトリID = v.child.genreId;get_genreid();"
			v-text="v.child.genreName"
		></span>
	</div>
</div>
