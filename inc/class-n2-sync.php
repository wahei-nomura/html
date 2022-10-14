<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Sync' ) ) {
	new N2_Sync();
	return;
}

/**
 * Setusers
 */
class N2_Sync {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_n2_sync_users', array( $this, 'sync_users' ) );
	}

	/**
	 * N2ユーザーデータ吸い上げ
	 */
	public function sync_users() {
		if ( ! WP_Filesystem() ) {
			return;
		}
		global $wp_filesystem, $current_blog;
		$data = $wp_filesystem->get_contents( "https://steamship.co.jp/{$current_blog->path}/wp-admin/admin-ajax.php?action=userdata" );
		$data = json_decode( $data, true );
		foreach ( $data as $k => $v ) {
			$userdata = $v['data'];
			unset( $userdata['ID'] );

			// 既存ユーザーは更新するのでIDを突っ込む
			$user = get_user_by( 'login', $userdata['user_login'] );
			if ( $user ) {
				$userdata['ID'] = $user->ID;
			}

			// 権限変換（NENG → NEONENG）
			switch ( $v['roles'][0] ) {
				case 'administrator':
					$userdata['role'] = 'ss-crew';
					break;
				case 'contributor':
					$userdata['role'] = 'jigyousya';
					break;
			}

			// metaをセット
			$usermeta = $userdata['meta'];
			unset( $userdata['meta'] );
			foreach ( array( 'nickname', 'first_name', 'last_name', 'description' ) as $key ) {
				$userdata[ $key ] = $usermeta[ $key ][0];
			}
			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_raw_user_pass' ), 10, 4 );
			$user_id = wp_insert_user( $userdata );
			remove_filter( 'wp_pre_insert_user_data', array( $this, 'insert_raw_user_pass' ) );

			// // 特定のユーザーを特権管理者に昇格（不要？）
			// if ( 'ss-crew' === $userdata['role'] ) {
			// grant_super_admin( $user_id );
			// }
		}
		echo 'ユーザーデータ更新完了！';
		exit;
	}

	/**
	 * N2返礼品吸い上げ
	 */
	public function sync_posts() {
		global $current_blog;
		$town = $current_blog->path;
		$url  = "https://steamship.co.jp{$town}wp-json/wp/v2/posts";
		// params
		$params = array(
			'per_page' => 100,
			'page'     => 1,
		);
		// トータルページ数（仮）
		$pages  = 1;
		$before = microtime( true );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		while ( $params['page'] <= $pages ) {
			// $http_response_header使いたいので鬼教官許して
			$data    = file_get_contents( "{$url}?" . http_build_query( $params ) );
			$headers = iconv_mime_decode_headers( implode( "\n", $http_response_header ) );
			// 合計情報
			$total = $headers['X-WP-Total'];
			$pages = $headers['X-WP-TotalPages'];
			$params['page']++;
			$arr = json_decode( $data, true );
			foreach ( $arr as $v ) {
				// 返礼品情報を生成
				$postarr = array(
					'status'            => $v['status'],
					'post_date'         => $v['date'],
					'post_date_gmt'     => $v['date_gmt'],
					'post_modified'     => $v['modified'],
					'post_modified_gmt' => $v['modified_gmt'],
					'type'              => $v['type'],
					'post_title'        => $v['title']['rendered'],
					'post_author'       => $v['author'],
					'meta_input'        => $v['acf'],
				);
				// 「返礼品コード」が既に登録済みか調査
				$args = array(
					'post_type'   => 'post',
					'meta_key'    => '返礼品コード',
					'meta_value'  => $v['acf']['返礼品コード'],
					'post_status' => 'any',
				);
				// 返礼品の投稿IDを取得
				$p = get_posts( $args )[0];
				// 登録済みの場合
				if ( $p->ID ) {
					// 更新されてない場合はスキップ
					if ( new DateTime( $p->post_modified ) === new DateTime( $postarr['post_modified'] ) ) {
						continue;
					}
					$postarr['ID'] = $p->ID;
				}
				add_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
				wp_insert_post( $postarr );
				remove_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ) );
			}
		}
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		$after = microtime( true );
		echo ( $after - $before ) . ' sec';
		exit;
	}

	/**
	 * 強制生パスワード注入
	 * https://github.com/WordPress/wordpress-develop/blob/6.0.2/src/wp-includes/user.php#L2328
	 *
	 * @param array    $data {
	 *     Values and keys for the user.
	 *
	 *     @type string $user_login      The user's login. Only included if $update == false
	 *     @type string $user_pass       The user's password.
	 *     @type string $user_email      The user's email.
	 *     @type string $user_url        The user's url.
	 *     @type string $user_nicename   The user's nice name. Defaults to a URL-safe version of user's login
	 *     @type string $display_name    The user's display name.
	 *     @type string $user_registered MySQL timestamp describing the moment when the user registered. Defaults to
	 *                                   the current UTC timestamp.
	 * }
	 * @param bool     $update   Whether the user is being updated rather than created.
	 * @param int|null $user_id  ID of the user to be updated, or NULL if the user is being created.
	 * @param array    $userdata The raw array of data passed to wp_insert_user().
	 */
	public function insert_raw_user_pass( $data, $update, $user_id, $userdata ) {
		$data['user_pass'] = wp_unslash( $userdata['user_pass'] );
		return $data;
	}

	/**
	 * 更新日時も登録可能にする
	 * 参考：https://wordpress.stackexchange.com/questions/224161/cant-edit-post-modified-in-wp-insert-post-bug
	 *
	 * @param array $data post_data
	 * @param array $postarr postarr
	 * @return $data
	 */
	public function alter_post_modification_time( $data, $postarr ) {
		if ( ! empty( $postarr['post_modified'] ) && ! empty( $postarr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $postarr['post_modified'];
			$data['post_modified_gmt'] = $postarr['post_modified_gmt'];
		}
		return $data;
	}

}
