<?php
/**
 * class-n2-change-allergen.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Changeallergen' ) ) {
	new N2_Changeallergen();
	return;
}

/**
 * N2_Changeallergen
 */
class N2_Changeallergen {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_changeallergen', array( $this, 'change_allergen' ) );
	}

	/**
	 * チョイスのエクスポート用TSV生成
	 *
	 * @return void
	 */
	public function change_allergen() {
		global $n2;
		// ajaxで渡ってきたpostidの配列
		$ids = explode( ',', filter_input( INPUT_POST, 'changeallergen' ) );
		$all_ids = get_posts(array(
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids',
		));
		$new_allergen_array = [];
		foreach($all_ids as $all_id){
			// print_r($all_id);
			$original_allergen_array = get_post_meta( $all_id, 'アレルゲン', true );
			foreach($original_allergen_array as $original_allergen){
				// print_r('あれい:'. $original_allergen['label']);
					$new_allergen_array[$all_id][] = $original_allergen['label'];
			}
		}
	}
}