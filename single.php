<?php
/**
 * single.php
 *
 * @package neoneng
 */

global $post;
$post_data = N2_Functions::get_all_meta( $post );
// echo esc_html(get_post_type_object(get_post_type())->name);
// $ini = parse_ini_file( get_theme_file_path() . '/config/n2-fields.ini', true );

// // プラグインn2-developのn2_setpost_show_customfields呼び出し
// $fields = apply_filters( 'n2_setpost_show_customfields', $ini, 'default' );

?>

<style>
	table{
		table-layout: fixed;
		width: 100%;
		border-collapse: collapse;
		border-spacing: 0;
	}
	table th,table td{
		padding: 10px 24px;
	}
	table td:nth-child(odd){
		text-align: center;
	}
	table tr:nth-child(odd){
		background-color: #eee;
	}
	table th {
		position: sticky;
		top: 0;
		z-index: 1;
		background-color: gray;
		color:#fff;
	}
</style>

<?php get_header(); ?>
<body <?php body_class(); ?>>


<?php
get_template_part('template/item_details');
