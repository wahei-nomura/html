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
	 * NENG AJAX URL
	 *
	 * @var string
	 */
	private $neng_ajax_url;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		global $current_blog;
		$this->neng_ajax_url = "https://steamship.co.jp{$current_blog->path}wp-admin/admin-ajax.php";

		add_action( 'wp_ajax_n2_sync_users', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_nopriv_n2_sync_users', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_nopriv_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_n2_multi_sync_posts', array( $this, 'multi_sync_posts' ) );
		add_action( 'wp_ajax_nopriv_n2_multi_sync_posts', array( $this, 'multi_sync_posts' ) );
		// add_action( 'wp_ajax_n2_sync_posts_by_rest_api', array( $this, 'sync_posts_by_rest_api' ) );
	}

	/**
	 * 超爆速 multi_sync_posts
	 * posts_per_page=10 が最速なのかはまだ未検証
	 */
	public function multi_sync_posts() {
		$before = microtime( true );

		if ( ! WP_Filesystem() ) {
			return;
		}
		global $wp_filesystem;

		// params
		$params = array(
			'action'         => 'postsdata',
			'post_type'      => 'post',
			'posts_per_page' => $_GET['posts_per_page'] ?? 10,
			'paged'          => 1,
		);

		$json = $wp_filesystem->get_contents( "{$this->neng_ajax_url}?" . http_build_query( $params ) );
		$data = json_decode( $json, true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		// IP制限等で終了のケース
		if ( ! $data ) {
			$logs[] = $json;
			$this->log( $logs );
			echo $json;
			exit;
		}

		// ページ数取得
		$max_num_pages = $data['max_num_pages'];

		// $params変更
		$params['action'] = 'n2_sync_posts';

		// n2_sync_posts に Multi cURL
		$mh       = curl_multi_init();
		$ch_array = array();
		while ( $max_num_pages >= $params['paged'] ) {
			$ch         = curl_init();
			$ch_array[] = $ch;
			// localでSSLでうまくアクセスできないので$schema必須
			$schema  = preg_match( '/localhost/', get_network()->domain ) ? 'http' : 'admin';
			$options = array(
				CURLOPT_URL            => admin_url( 'admin-ajax.php?', $schema ) . http_build_query( $params ),
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_TIMEOUT        => 30,
			);
			curl_setopt_array( $ch, $options );
			curl_multi_add_handle( $mh, $ch );
			$params['paged']++;
		}
		do {
			curl_multi_exec( $mh, $running );
			curl_multi_select( $mh );
		} while ( $running > 0 );

		foreach ( $ch_array as $ch ) {
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );
		echo 'N2-Multi-Sync-Posts「' . get_bloginfo( 'name' ) . 'の返礼品」旧NENGとシンクロ完了！（' . number_format( microtime( true ) - $before, 2 ) . ' 秒）';
		$logs[] = '返礼品シンクロ完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
		exit;
	}

	/**
	 * N2ユーザーデータ吸い上げ
	 */
	public function sync_users() {
		$before = microtime( true );
		if ( ! WP_Filesystem() ) {
			return;
		}
		global $wp_filesystem;
		$json = $wp_filesystem->get_contents( "{$this->neng_ajax_url}?action=userdata" );
		$data = json_decode( $json, true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		// IP制限等で終了のケース
		if ( ! $data ) {
			$logs[] = $json;
			$this->log( $logs );
			echo $json;
			exit;
		}

		// ユーザー登録
		foreach ( $data as $k => $v ) {
			$userdata = $v['data'];
			// フルフロンタルは除外
			if ( 'fullfrontal' === $userdata['user_login'] ) {
				continue;
			}
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

			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_raw_user_pass' ), 10, 4 );
			$user_id = wp_insert_user( $userdata );
			remove_filter( 'wp_pre_insert_user_data', array( $this, 'insert_raw_user_pass' ) );

			// // 特定のユーザーを特権管理者に昇格（不要？）
			// if ( 'ss-crew' === $userdata['role'] ) {
			// grant_super_admin( $user_id );
			// }
		}
		echo 'N2-User-Sync「' . get_bloginfo( 'name' ) . '」ユーザーデータ旧NENGとシンクロ完了！';
		$logs[] = 'ユーザーシンクロ完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
		exit;
	}

	/**
	 * N2返礼品吸い上げ
	 * posts_per_page
	 */
	public function sync_posts() {
		if ( ! WP_Filesystem() ) {
			return;
		}
		global $wp_filesystem;

		// params
		$params            = $_GET;
		$params['action']  = 'postsdata';
		$params['orderby'] = 'ID';

		// 投稿を部分同期
		$json = $wp_filesystem->get_contents( "{$this->neng_ajax_url}?" . http_build_query( $params ) );
		$data  = json_decode( $json, true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		// IP制限等で終了のケース
		if ( ! $data ) {
			$logs[] = $json;
			$this->log( $logs );
			echo $json;
			exit;
		}

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		foreach ( $data['posts'] as $k => $v ) {

			// 返礼品情報を生成
			$postarr = array(
				'post_status'       => $v['post_status'],
				'post_date'         => $v['post_date'],
				'post_date_gmt'     => $v['post_date_gmt'],
				'post_modified'     => $v['post_modified'],
				'post_modified_gmt' => $v['post_modified_gmt'],
				'type'              => $v['type'],
				'post_title'        => $v['post_title'],
				'post_author'       => $this->get_userid_by_last_name( $v['post_author_last_name'] ),
				'meta_input'        => $v['acf'],
			);
			$postarr['meta_input']['_neng_id'] = $v['ID']; // 同期用 裏カスタムフィールドNENGのID追加

			// 登録済みか調査
			$args = array(
				'post_type'   => 'post',
				'meta_key'    => '_neng_id',
				'meta_value'  => $v['ID'],
				'post_status' => 'any',
			);
			// 裏カスタムフィールドのNENGの投稿IDで登録済み調査
			$p = get_posts( $args )[0];
			// 登録済みの場合
			if ( $p->ID ) {
				// 更新されてない場合はスキップ
				if ( $p->post_modified === $postarr['post_modified'] ) {
					continue;
				}
				$postarr['ID'] = $p->ID;
				// ログ生成
				$this->log( array( ...$logs, "「{$p->post_title}」を更新しました。{$p->post_modified}  {$v['post_modified']}" ) );
			}
			add_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
			wp_insert_post( $postarr );
			remove_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ) );
		}
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		exit;
	}

	/**
	 * REST API経由でN2返礼品吸い上げ
	 */
	public function sync_posts_by_rest_api() {
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

				// 返礼品コードから事業者判定
				preg_match( '/[A-Z]{2,3}/', $v['acf']['返礼品コード'], $m );
				// 事業者コードが取得できない場合はスキップ
				if ( empty( $m ) ) {
					echo '<pre>事業者コードがありません。</pre>';
					continue;
				}
				$user_id = $this->get_userid_by_last_name( $m[0] );

				// 返礼品情報を生成
				$postarr = array(
					'post_status'       => $v['status'],
					'post_date'         => $v['date'],
					'post_date_gmt'     => $v['date_gmt'],
					'post_modified'     => $v['modified'],
					'post_modified_gmt' => $v['modified_gmt'],
					'type'              => $v['type'],
					'post_title'        => $v['title']['rendered'],
					'post_author'       => $user_id,
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
	 * ログファイル生成
	 *
	 * @param array $arr ログ用の追加配列
	 */
	private function log( $arr ) {
		$logs = array(
			date_i18n( 'Y/m/d H:i:s' ),
			get_bloginfo( 'name' ),
			...$arr,
		);
		error_log( implode( ' | ', $logs ) . PHP_EOL, 3, ABSPATH . '/n2-sync.log' );
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

	/**
	 * last_nameからユーザーIDゲットだぜ
	 *
	 * @param string $last_name 名
	 */
	public function get_userid_by_last_name( $last_name ) {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				'last_name',
				$last_name
			)
		);
		return $id;
	}

}
