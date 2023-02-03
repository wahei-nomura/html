<?php
/**
 * form rakuten-genreid
 * 楽天 全商品ディレクトリID
 *
 * @package neoneng
 */

$defaults = array();
$args     = wp_parse_args( $args, $defaults );
$alert    = $args['alert'];
unset( $args['alert'] );
$attr  = '';
foreach ( $args as $k => $v ) {
	$attr .= " {$k}=\"{$v}\"";
}
?>
<input type="number"<?php echo $attr; ?> @focus="get_genreid(true)">
<ol class="breadcrumb m-0 mt-1">
	<li v-if="全商品ディレクトリID.list.current" class="breadcrumb-item">
		<span 
			style="text-decoration: underline;"
			role="button"
			@click="全商品ディレクトリID.text = 0;get_genreid(true);"
			v-text="`全商品ディレクトリID`"
		></span>
	</li>
	<li class="breadcrumb-item" v-for="v in 全商品ディレクトリID.list.parents">
		<span
			style="text-decoration: underline;"
			role="button"
			@click="全商品ディレクトリID.text = v.parent.genreId;get_genreid(true);"
			v-text="v.parent.genreName"
		></span>
	</li>
	<li v-if="全商品ディレクトリID.list.current" class="breadcrumb-item">
		{{全商品ディレクトリID.list.current.genreName}}
	</li>
</ol>
<div v-if="全商品ディレクトリID.list.children">
	<small class="text-danger" v-if="全商品ディレクトリID.list.children.length">
		<?php echo $alert; ?>
	</small>
	<div>
		<span 
			v-for="v in 全商品ディレクトリID.list.children"
			class="btn btn-dark btn-sm me-1 mb-1 py-0"
			@click="全商品ディレクトリID.text = v.child.genreId;get_genreid(true);"
			v-text="v.child.genreName"
		></span>
	</div>
</div>
