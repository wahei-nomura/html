<?php
/**
 * class-n2-foodparam.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Foodparam' ) ) {
	new N2_Foodparam();
	return;
}

/**
 * Foodparam
 */
class N2_Foodparam {
	/**
	 * クラス名
	 *
	 * @var string
	 */
	private $cls;
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls = get_class( $this );
		add_action( 'wp_login', array( $this, 'jigyousya_add_food' ), 11, 2 );
	}

	/**
	 * judge_jigyousya
	 *
	 * @param Object $user_login user_login
	 * @param Object $user user
	 * @return void
	 */
	public function jigyousya_add_food( $user_login, $user ) {
		// 事業者ユーザーでなければreturn
		if ( ! empty( $user->roles[0] ) && 'jigyousya' !== $user->roles[0] ) {
			return;
		}

		// user_metaに食品取扱いがない、またはからの場合
		if ( empty( get_user_meta( $user->ID, '食品取扱い', true ) ) || '' === get_user_meta( $user->ID, '食品取扱い', true ) ) {
			wp_redirect( site_url() . '/wp-admin/admin.php?page=n2_food_menu' );
			exit;
		}
	}
}
