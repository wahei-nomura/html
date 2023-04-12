<?php
/**
 * class-n2-users-api.php
 * ユーザー情報を扱うAPI
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Users_API' ) ) {
	new N2_Users_API();
	return;
}

/**
 * ユーザー情報を扱うAPI
 */
class N2_Users_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_users_api', array( $this, 'get' ) );
	}

	/**
	 * 全ユーザーを出力する
	 */
	public function get() {
		$role           = isset( $_GET['role'] ) ? filter_input( INPUT_GET, 'role' ) : '';
		$users_all_data = get_users( "role={$role}" );

		// userデータをシンプルに加工
		$users = array_map(
			function( $user ) {

				// 出したくないデータを削除
				unset( $user->data->deleted );
				unset( $user->data->spam );
				unset( $user->data->user_activation_key );
				unset( $user->data->user_pass );

				// roleがないので追加
				$user->data->role = $user->roles[0];

				return $user->data;
			},
			$users_all_data
		);

		echo wp_json_encode( $users );
		exit;
	}
}
