<?php
/**
 * search.php
 *
 * @package neoneng
 */

$template = ! empty( $_GET['crew'] ) ? 'crew-check' : 'front-list';

?>
<?php get_header(); ?>

<?php
	if ( ! empty( $_GET['look'] ) ) {
		get_template_part( 'template/front-manual' );
	}
?>
	<?php
		$user_lists = get_users( 'role=jigyousya' );
		// var_dump($user_lists);
		$search_params = $_GET;
		$search_result = '';
		foreach ( $search_params as $key => $sch_prm ) {
			if('' != $sch_prm){
				if ( '' != $search_result ) {
					$search_result .= ', ';
				}
				if( 'jigyousya' == $key){
					$keyNo = array_search($sch_prm, array_column($user_lists, 'ID'));
					$search_result .= $user_lists[$keyNo]->display_name;
				}elseif('返礼品コード' == $key){
					foreach( $sch_prm as $codeKey => $code_prm){
						$code_meta_data = get_post_meta($code_prm);
						$codes = $code_meta_data['返礼品コード'];
						foreach($codes as $cdKey => $cd){
							$search_result .= $cd;
						}
					if($codeKey != array_key_last($sch_prm)){
						$search_result .= '/';
					}
				}
				}else{
					$search_result .= $sch_prm;
				}
			}
		}
		if ( '' != $search_result ) {
			echo '<h2 class="search-result-header text-primary">絞り込み：' . $search_result . '</h2>';
		}

	?>

<article class="product-wrap search">
	<?php
	if ( empty( $_GET['look'] ) ) {
		get_template_part( 'template/front-search' );
	}
	get_template_part( "template/{$template}" );
	?>
</article>
<?php get_footer(); ?>
