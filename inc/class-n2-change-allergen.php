<?php
/**
 * class-n2-change-allergen.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Change_Allergen' ) ) {
	new N2_Change_Allergen();
	return;
}

/**
 * N2_Change_Allergen
 */
class N2_Change_Allergen {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_change_allergen', array( $this, 'change_allergen' ) );
	}

	/**
	 * アレルゲン配列の組み換え&更新
	 *
	 * @return void
	 */
	public function change_allergen() {
		foreach ( get_posts( 'posts_per_page=-1&post_status=any' ) as $post ) {
			$allergens = get_post_meta( $post->ID, 'アレルゲン', true );
			if ( ! empty( $allergens ) ) {
				$allergens = array_map( fn( $v ) => isset( $v['label'] ) ? $v['label'] : $v, $allergens );// labelありの場合はlabelのみ、それ以外はそのまま
				$allergens = str_replace( '（ピーナッツ）', '', $allergens );// チョイスの新仕様の「落花生（ピーナッツ）」は「落花生」
				update_post_meta( $post->ID, 'アレルゲン', $allergens );// アレルゲン更新
			}
			wp_insert_post( $post );// API更新フック発火
		}
		echo 'アレルゲン更新と、ついでにAPI更新完了！';
		exit;
	}
}
