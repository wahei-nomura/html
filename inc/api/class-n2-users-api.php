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
		add_action( 'wp_ajax_n2_user_destruct_self_account', array( $this, 'destruct_self_account' ) );
		add_action( 'wp_ajax_n2_post_author_update', array( $this, 'post_author_update' ) );
	}

	/**
	 * 全ユーザーを出力する
	 */
	public function get() {
		$args = $_GET;
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$args = wp_parse_args( $args, $_POST );
		}
		$users_all_data = get_users( $args );

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

	/**
	 * 返礼品の作成者を変更する
	 */
	public function post_author_update () {
		$post_id   = isset( $_POST['post_id'] ) ? filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT ) : false;
		$author_id = isset( $_POST['author_id'] ) ? filter_input( INPUT_POST, 'author_id', FILTER_VALIDATE_INT ) : false;

		if ( ! $post_id || ! $author_id ) {
			echo 'post_idまたはauthor_idがありません';
			exit;
		}

		$post = array(
			'ID'          => $post_id,
			'post_author' => $author_id,
		);

		wp_update_post( $post );

		echo wp_json_encode( array('message' => 'success') );
		exit;
	}

	/**
	 * 自アカウントのみ自爆ボタンを起爆する
	 */
	public function destruct_self_account ( $args ) {
		global $n2;
		// $_GETを引数で上書き
		$params = wp_parse_args( $args, $_GET );
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}

		$user = $n2->current_user;
		global $wpdb;
		$admin = get_user_by( 'login', 'fullfrontal' );
		// 無作為にできないように自アカウントのみに制限
		if ( $user->ID !== (int) $params['id'] ) {
			echo 'アカウントの削除に失敗しました';
			error_log( '自爆ボタン不発...' );
			error_log( 'current :' . $user->ID );
			error_log( 'param :' . $params['id'] );
			exit;
		}
		if ( $admin ) {
			// フルフロンタルへ引き継ぐ
			$wpdb->update(
				$wpdb->posts,
				array('post_author' => $admin->ID),
				array('post_author' => $user->ID)
			);
		} else {
			error_log( 'フルフロンタルが見つかりません。引き継ぎに失敗しました...' );
		}
		wpmu_delete_user( $user->ID );
		echo 'アカウントを削除しました。おつかれさまでした。';
		exit;
	}
}
