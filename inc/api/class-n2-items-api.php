<?php
/**
 * N2返礼品データAPI
 * get_postsの引数で取得可能
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Items_API' ) ) {
	new N2_Items_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Items_API {

	/**
	 * データ
	 *
	 * @var array
	 */
	public static $data = array(
		'params' => array(), // $_GET $_POST パラメータ
		'items'  => array(), // N2データ
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// post_contentに必要なデータを全部ぶっこむ
		add_action( 'wp_insert_post_data', array( $this, 'insert_post_data' ), 10, 4 );
		add_filter( 'posts_results', array( $this, 'add_required_posts' ), 10, 2 );// 取得時に最低必要事項確認フラグ注入（取得が遅くなる）
		add_action( 'wp_ajax_n2_items_api', array( $this, 'api' ) );
		add_action( 'wp_ajax_nopriv_n2_items_api', array( $this, 'api' ) );
		add_action( 'profile_update', array( $this, 'update_api_from_user' ), 10, 3 );
	}

	/**
	 * API
	 */
	public function api() {
		self::$data['items'] = self::get_items();
		// モード判定
		$mode = self::$data['params']['mode'];
		$mode = method_exists( 'N2_Items_API', $mode ) ? $mode : 'json';
		header( 'Content-Type: application/json; charset=utf-8' );
		$this->{$mode}();
		exit;
	}

	/**
	 * パラメータのセット
	 *
	 * @param array $params パラメータ
	 */
	public static function set_params( $params ) {
		global $n2;
		$params = $params ?: $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		// デフォルト値
		$defaults = array(
			'suppress_filters' => false,
			'post_status'      => 'any',
			'post_type'        => 'post',
			'numberposts'      => -1,
			'mode'             => 'json',
			'n2_active_flag'   => $n2->settings['N2']['稼働中'],
		);
		// デフォルト値を$paramsで上書き
		self::$data['params'] = wp_parse_args( $params, $defaults );
	}

	/**
	 * データ取得
	 *
	 * @param array $params パラメータ
	 */
	public static function get_items( $params = array() ) {
		$params = wp_parse_args( $params );
		self::set_params( $params );
		$posts = get_posts( self::$data['params'] );
		$posts = array_map(
			function ( $v ) {
				$post_content = json_decode( $v->post_content, true );
				if ( 'post' === self::$data['params']['post_type'] ) {
					// idを混ぜ込む
					$post_content['id'] = $v->ID;
					return $post_content;
				} else {
					return array(
						'ID'           => $v->ID,
						'post_title'   => $v->post_title,
						'post_date'    => $v->post_date,
						'post_content' => $post_content ?? $v->post_content,
					);
				}
			},
			$posts
		);
		$posts = array_filter( $posts );
		/**
		 * [hook] n2_items_api_get_items
		 */
		return apply_filters( 'n2_items_api_get_items', $posts );
	}

	/**
	 * json
	 */
	public function json() {
		echo wp_json_encode( self::$data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * デバッグ用
	 */
	public function debug() {
		print_r( self::$data );
	}

	/**
	 * 全件アップデート
	 */
	public static function update() {
		if ( ! is_user_logged_in() ) {
			echo '不正アクセス';
			exit;
		}
		set_time_limit( 0 );
		$params = self::$data['params'];
		/**
		 *  [hook] n2_items_api_before_update
		 */
		do_action( 'n2_items_api_before_update', $params );
		foreach ( get_posts( $params ) as $post ) {
			$post->post_status = ! empty( $params['change_status'] ) ? $params['change_status'] : $post->post_status;
			$post->post_author = ! empty( $params['change_author'] ) ? $params['change_author'] : $post->post_author;
			/**
			 *  [hook] n2_items_api_update
			 */
			$post = apply_filters( 'n2_items_api_update', $post, $params );
			wp_insert_post( $post );
		}
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		}
	}

	/**
	 * 削除
	 */
	public function delete() {
		if ( ! is_user_logged_in() ) {
			echo '不正アクセス';
			exit;
		}
		$params = self::$data['params'];
		wp_trash_post( $params['p'] );
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		}
	}

	/**
	 * ゴミ箱から復元
	 */
	public function untrash() {
		if ( ! is_user_logged_in() ) {
			echo '不正アクセス';
			exit;
		}
		$params = self::$data['params'];
		wp_untrash_post( $params['p'] );
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		}
	}

	/**
	 * 複製
	 */
	public function copy() {
		if ( ! is_user_logged_in() ) {
			echo '不正アクセス';
			exit;
		}
		$params = self::$data['params'];
		$post   = get_post( $params['p'] );
		$meta   = json_decode( $post->post_content, true );
		unset(
			$meta['タイトル'],
			$meta['事業者コード'],
			$meta['事業者名'],
			$meta['ステータス'],
			$meta['返礼品コード'],
			$meta['寄附金額'],
			$meta['LH表示名'],
			$meta['社内共有事項'],
			$meta['配送伝票表示名'],
			$meta['寄附金額固定'],
			$meta['_neng_id'],
			$meta['_edit_lock'],
			$meta['_n2_required'],
			$meta['自治体確認'],
		);
		$postarr = array(
			'post_title'  => "（コピー） {$post->post_title}",
			'post_status' => 'draft',
			'post_author' => $post->post_author,
			'meta_input'  => $meta,
		);
		$id      = wp_insert_post( $postarr );
		wp_safe_redirect( admin_url( "post.php?post={$id}&action=edit" ) );
	}

	/**
	 * 投稿保存時にpost_contentにAPIデータを全部ぶち込む
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 * @param array $unsanitized_postarr An array of slashed yet *unsanitized* and unprocessed post data as
	 *                                   originally passed to wp_insert_post().
	 * @param bool  $update              Whether this is an existing post being updated.
	 */
	public function insert_post_data( $data, $postarr, $unsanitized_postarr, $update ) {
		if ( 'post' !== $postarr['post_type'] || ( ! isset( $postarr['meta_input'] ) && ! $update ) ) {
			return $data;
		}
		$post_content = array();
		// タイトル追加
		$post_content['タイトル'] = $data['post_title'];

		// 特定のカスタムフィールド値のみ更新することがあるので既存の値とマージしないといけない（が、無くなったフィールドは消したい）
		$meta_input = array();
		$meta       = get_post_meta( $postarr['ID'] );
		if ( ! empty( $meta ) ) {
			foreach ( array_keys( $meta ) as $key ) {
				$meta_input[ $key ] = get_post_meta( $postarr['ID'], $key, true );
			}
		}
		$meta_input = wp_parse_args( wp_unslash( $postarr['meta_input'] ?? array() ), $meta_input );

		// 事業者コード追加
		$post_content['事業者コード'] = get_user_meta( $data['post_author'], 'last_name', true );

		// 事業者名
		$post_content['事業者名'] = get_user_meta( $data['post_author'], 'first_name', true );

		// 投稿ステータス追加
		$post_content['ステータス'] = $data['post_status'];

		// 最低必要事項確認フラグ注入
		$post_content['_n2_required'] = $this->check_required( $meta_input );

		// n2fieldのカスタムフィールド全取得
		foreach ( $meta_input as $key => $meta ) {
			$meta = match ( true ) {
				// 値が配列の場合、空は削除
				is_array( $meta ) => array_values( array_filter( $meta, fn( $v ) => $v ) ),
				// それ以外は文字列型で保存
				default => (string) $meta,
			};
			$post_content[ $key ] = $meta;
		}
		$data['post_content'] = addslashes( wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE ) );
		do_action( 'n2_items_api_after_insert_post_data', $data, $meta_input );
		return $data;
	}

	/**
	 * 最低必要事項確認フラグ作成
	 *
	 * @param array $meta メタデータ
	 */
	public function check_required( $meta ) {
		global $n2;
		// 最低必要事項
		$required = array( '返礼品コード', '価格', '寄附金額' );
		// eチケット以外なのに送料なし
		if ( isset( $meta['商品タイプ'] ) && ! in_array( 'eチケット', $meta['商品タイプ'], true ) ) {
			$required[] = '送料';

			// 楽天なのにジャンルID or 商品属性なし
			if ( in_array( '楽天', $n2->settings['N2']['出品ポータル'], true ) && ! in_array( '楽天', (array) ( $meta['出品禁止ポータル'] ?? '' ), true ) ) {
				array_push( $required, '全商品ディレクトリID', '商品属性' );
			}

			// アレルギーあるのにアレルゲンなし
			if (
				! empty( array_filter( (array) ( $meta['アレルギー有無確認'] ?? array() ) ) )
				&& empty( array_filter( (array) ( $meta['アレルゲン'] ?? array() ) ) )
			) {
				$required[] = 'アレルゲン';
			}
		}
		// 最低必要事項の調査（数値に関しては0は許したい）
		$check_required = array_filter(
			$meta ?? array(),
			function ( $v, $k ) use ( $required ) {
				if ( in_array( $k, $required, true ) ) {
					if ( is_array( $v ) ) {
						$bool = ! empty( array_filter( $v ) );
					} else {
						$bool = '' !== $v;
					}
				}
				return $bool ?? false;
			},
			ARRAY_FILTER_USE_BOTH
		);
		// 最低必須項目が埋まっていないものリスト
		return array_values( array_diff( $required, array_keys( $check_required ) ) );
	}

	/**
	 * 最低必要事項確認フラグがない場合は混ぜ込む（API更新したら不要）
	 *
	 * @param array $posts メタデータ
	 * @param array $query クエリ
	 */
	public function add_required_posts( $posts, $query ) {
		if ( 'post' !== ( $query->query['post_type'] ?? '' ) ) {
			return $posts;
		}
		foreach ( $posts as $i => $post ) {
			$post_content = json_decode( $posts[ $i ]->post_content, true );
			// 最低必要事項確認フラグ注入
			if ( isset( $post_content['_n2_required'] ) ) {
				continue;
			}
			$post_content['_n2_required'] = $this->check_required( $post_content );
			$posts[ $i ]->post_content    = wp_json_encode( $post_content, JSON_UNESCAPED_UNICODE );
		}
		return $posts;
	}

	/**
	 * 事業者名更新時にAPI側もアップデート
	 *
	 * @param int     $user_id       User ID.
	 * @param WP_User $old_user_data Object containing user's data prior to update.
	 * @param array   $userdata      The raw array of data passed to wp_insert_user().
	 */
	public function update_api_from_user( $user_id, $old_user_data, $userdata ) {
		global $n2;
		// 返礼品ページのユーザー設定変更で発火してほしくない
		if ( $old_user_data->data->display_name === $userdata['display_name'] ) {
			return;
		}
		$args = array(
			'body'      => array(
				'action' => 'n2_items_api',
				'mode'   => 'update',
				'author' => $user_id,
			),
			'cookies'   => $_COOKIE,
			'sslverify' => false,
		);
		wp_remote_get( $n2->ajaxurl, $args );
	}
}
