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
	 * アレルゲン配列の組み換え&更新
	 *
	 * @return void
	 */
	public function change_allergen() {
		global $n2;
		$all_ids = get_posts(array(
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids',
		));
		$new_allergen_array = [];
		foreach($all_ids as $id_key => $all_id){
			$original_allergen_array = get_post_meta( $all_id, 'アレルゲン', true );
			foreach($original_allergen_array as $original_allergen){
				if(isset($original_allergen['label'])){ // 「label」がある場合だけ処理
					if( $original_allergen['label'] != '食品ではない' && $original_allergen['label'] != 'アレルゲンなし食品' ){
						$is_near  = false;
						preg_match( '/ピーナ/', $original_allergen['label'], $is_near ); // 「ピーナツ」や「ピーナッツ」を「落花生」にまるめ
						if( $is_near ){
							$original_allergen['label'] = '落花生';
						}
						$new_allergen_array[$all_id][] = $original_allergen['label'];
					}
				}
			}
			if(is_array( $new_allergen_array[$all_id] )){
				update_post_meta($all_id, 'アレルゲン', $new_allergen_array[$all_id]);
			}
		}		
	}
}