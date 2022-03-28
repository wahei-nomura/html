<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Setusers' ) ) {
	new N2_Setusers();
	return;
}

/**
 * Setusers
 */
class N2_Setusers {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_usertype' ) );
	}

	/**
	 * add_usertype
	 *
	 * @return void
	 */
	public function add_usertype() {
		global $wp_roles;
		if ( empty( $wo_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$wp_roles->add_role( 'jigyousya', '事業者', array() );
		$wp_roles->add_cap( 'jigyousya', 'read' );
		$wp_roles->add_cap( 'jigyousya', 'edit_posts' );
	}

}
