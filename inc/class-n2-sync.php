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
 * N2のいろいろ同期
 *
 * N1とユーザー同期
 * N1と返礼品同期
 * スプレットシートとの同期
 */
class N2_Sync {

	/**
	 * NENG AJAX URL
	 * N1のデータ取得用URL
	 *
	 * @var string
	 */
	private $n1_ajax_url;

	/**
	 * スプレットシート取得用APIのURL
	 * SSツールのものを一旦利用
	 *
	 * @var string
	 */
	private $spreadsheet_api_url = 'https://app.steamship.co.jp/ss-tool/php/get-spreadsheet.php';

	/**
	 * スプレットシートAPIの認証用jsonのpath（非公開領域に置く）
	 *
	 * @var string
	 */
	private $spreadsheet_auth_path = '/var/www/keys/steamship-gcp.json';

	/**
	 * スプレットシートAPIの認証用jsonの中身
	 *
	 * @var string
	 */
	private $spreadsheet_auth;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		global $current_blog, $wp_filesystem;
		$this->n1_ajax_url = "https://steamship.co.jp{$current_blog->path}wp-admin/admin-ajax.php";
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( WP_Filesystem() ) {
			$this->spreadsheet_auth = $wp_filesystem->get_contents( $this->spreadsheet_auth_path );
		}

		add_action( 'wp_ajax_n2_sync_users_from_n1', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_users_from_spreadsheet', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_nopriv_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_n2_multi_sync_posts', array( $this, 'multi_sync_posts' ) );
		add_action( 'wp_ajax_n2_insert_post_from_spreadsheet', array( $this, 'insert_post_from_spreadsheet' ) );

		// cron登録処理
		add_filter( 'cron_schedules', array( $this, 'intervals' ) );
		if ( ! wp_next_scheduled( 'wp_ajax_n2_sync_users_from_n1' ) ) {
			wp_schedule_event( time(), 'daily', 'wp_ajax_n2_sync_users_from_n1' );
		}
		if ( ! wp_next_scheduled( 'wp_ajax_n2_multi_sync_posts' ) ) {
			wp_schedule_event( time() + 100, '30min', 'wp_ajax_n2_multi_sync_posts' );
		}
	}

	/**
	 * WP CRONのオリジナルスケジュール
	 *
	 * @param array $schedules スケジュール配列
	 */
	public function intervals( $schedules ) {
		$schedules['30min'] = array(
			'interval' => 1800,
			'display'  => '30分毎',
		);
		$schedules['5min']  = array(
			'interval' => 300,
			'display'  => '5分毎',
		);
		return $schedules;
	}

	/**
	 * 超爆速 multi_sync_posts
	 * posts_per_page=10 が最速なのかはまだ未検証
	 */
	public function multi_sync_posts() {
		if ( is_main_site() ) {
			exit;
		}
		$before = microtime( true );

		// params
		$params = array(
			'action'         => 'postsdata',
			'post_type'      => 'post',
			'posts_per_page' => $_GET['posts_per_page'] ?? 100,
			'paged'          => 1,
		);
		$json   = wp_remote_get( "{$this->n1_ajax_url}?" . http_build_query( $params ) )['body'];
		$data   = json_decode( $json, true );

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

		// 強制リフレッシュ同期
		if ( isset( $_GET['refresh'] ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->posts}" );
			$wpdb->query( "DELETE FROM {$wpdb->postmeta};" );
		}
		// ページ数取得
		$max_num_pages = $data['max_num_pages'];
		$found_posts   = $data['found_posts'];

		// $params変更
		$params['action'] = 'n2_sync_posts';
		$params['nonce']  = wp_create_nonce( 'sync_posts' );

		// n2_sync_posts に Multi cURL
		$mh       = curl_multi_init();
		$ch_array = array();
		while ( $max_num_pages >= $params['paged'] ) {

			// ツイン起動しないためにSync中のフラグをチェックして終了
			$sleep = $_GET['sleep'] ?? 300;
			if ( $sleep > ( strtotime( 'now' ) - get_option( "n2syncing-{$params['paged']}", strtotime( '-1 hour' ) ) ) ) {
				$logs[] = '2重起動防止のため終了';
				$this->log( $logs );
				exit;
			}
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
				CURLOPT_USERPWD        => 'ss:ss',
			);
			curl_setopt_array( $ch, $options );
			curl_multi_add_handle( $mh, $ch );
			$params['paged']++;
		}
		do {
			curl_multi_exec( $mh, $running );
			curl_multi_select( $mh );
		} while ( $running > 0 );

		$neng_ids = array();// NENGに登録済みの投稿ID配列
		foreach ( $ch_array as $ch ) {
			$ids = json_decode( curl_multi_getcontent( $ch ) );
			if ( is_array( $ids ) ) {
				$neng_ids = array( ...$neng_ids, ...$ids );
			}
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );

		// NENGとNEONENGの差分を抽出するための準備
		$neng_ids    = array_values( $neng_ids );
		$neoneng_ids = get_posts( 'posts_per_page=-1&post_status=any&meta_key=_neng_id&fields=ids' );
		// NENGから削除されているものを削除（N2で追加したものに関してはスルー）
		if ( $found_posts < count( $neoneng_ids ) ) {
			$deleted_ids = array_diff( $neoneng_ids, $neng_ids );
			foreach ( $deleted_ids as $del ) {
				wp_delete_post( $del );
			}
		}

		echo 'N2-Multi-Sync-Posts「' . get_bloginfo( 'name' ) . 'の返礼品」旧NENGとシンクロ完了！（' . number_format( microtime( true ) - $before, 2 ) . ' 秒）';
		$logs[] = '返礼品シンクロ完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
		exit;
	}

	/**
	 * N2返礼品吸い上げ
	 * posts_per_page
	 */
	public function sync_posts() {

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		// ここでnonceを検証し、直アクセスを禁ずる
		if ( ! wp_verify_nonce( $_GET['nonce'], 'sync_posts' ) ) {
			$text   = 'nonceが正しくありません。';
			$logs[] = $text;
			$this->log( $logs );
			echo $text;
			exit;
		}

		// params
		$params            = $_GET;
		$params['action']  = 'postsdata';
		$params['orderby'] = 'ID';

		// Syncフラグを記録
		update_option( "n2syncing-{$params['paged']}", strtotime( 'now' ) );

		// 投稿を部分同期
		$json = wp_remote_get( "{$this->n1_ajax_url}?" . http_build_query( $params ) )['body'];
		$data = json_decode( $json, true );

		// IP制限等で終了のケース
		if ( ! $data ) {
			$logs[] = $json;
			$this->log( $logs );
			echo $json;
			exit;
		}
		// NENGにあるものの投稿ID（削除用）
		$neng_ids = array();
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
				'post_type'         => $v['post_type'],
				'post_title'        => $v['post_title'],
				'post_author'       => $this->get_userid_by_last_name( $v['post_author_last_name'] ),
				'meta_input'        => (array) $v['acf'],
				'comment_status'    => 'open',
			);
			// brを除去
			array_walk_recursive(
				$postarr['meta_input'],
				function ( &$val, $key ) {
					$val = preg_replace( '/<br[ \/]*>/', '', $val );
				}
			);
			// 同期用 裏カスタムフィールドNENGのID追加
			$postarr['meta_input']['_neng_id'] = $v['ID'];

			// 「取り扱い方法1〜2」を「取り扱い方法」に変換
			$handling                        = array_filter( $postarr['meta_input'], fn( $k ) => preg_match( '/取り扱い方法[0-9]/u', $k ), ARRAY_FILTER_USE_KEY );
			$postarr['meta_input']['取り扱い方法'] = array_filter( array_values( $handling ), fn( $v ) => $v );
			foreach ( array_keys( $handling ) as $k ) {
				unset( $postarr['meta_input'][ $k ] );
			}

			// 「商品画像１〜８」を「商品画像」に変換
			$images                        = array_filter( $postarr['meta_input'], fn( $k ) => preg_match( '/商品画像[０-９]/u', $k ), ARRAY_FILTER_USE_KEY );
			$postarr['meta_input']['商品画像'] = array_filter( array_values( $images ), fn( $v ) => $v );
			foreach ( array_keys( $images ) as $k ) {
				unset( $postarr['meta_input'][ $k ] );
			}
			unset( $postarr['meta_input']['商品画像をzipファイルでまとめて送る'] );

			// 商品タイプ
			$postarr['meta_input']['商品タイプ'] = array();
			if ( 'やきもの' === ( $postarr['meta_input']['やきもの'] ?? '' ) ) {
				$postarr['meta_input']['商品タイプ'][] = 'やきもの';
			}

			// アレルギー関連
			if ( isset( $postarr['meta_input']['アレルゲン'] ) ) {
				$allergen = array_column( $postarr['meta_input']['アレルゲン'], 'value' );
				if ( $allergen ) {
					if ( ! in_array( '食品ではない', $allergen, true ) ) {
						$postarr['meta_input']['商品タイプ'][] = '食品';
						if ( ! in_array( 'アレルゲンなし食品', $allergen, true ) ) {
							$postarr['meta_input']['アレルギー有無確認'] = array( 'アレルギー品目あり' );
						}
					}
				}
			}
			// 地場産品類型互換
			$postarr['meta_input']['地場産品類型'] = $postarr['meta_input']['地場産品類型']['value'] ?? '';

			// キャッチコピー１と楽天カテゴリーの変換
			$postarr['meta_input']['キャッチコピー']    = $postarr['meta_input']['キャッチコピー１'] ?? '';
			$postarr['meta_input']['楽天SPAカテゴリー'] = $postarr['meta_input']['楽天カテゴリー'] ?? '';
			unset( $postarr['meta_input']['キャッチコピー１'], $postarr['meta_input']['楽天カテゴリー'] );

			// 発送サイズ関連
			$postarr['meta_input']['発送サイズ'] = $postarr['meta_input']['発送サイズ'] ?? '';
			// 発送サイズの「レターパック」互換
			if ( 'レターパック' === $postarr['meta_input']['発送サイズ'] ) {
				$postarr['meta_input']['発送サイズ'] = 'レターパックプラス';
			}
			// 発送サイズの「その他」の統一
			if ( 'その他（ヤマト以外）' === $postarr['meta_input']['発送サイズ'] ) {
				$postarr['meta_input']['発送サイズ'] = 'その他';
			}

			// 事業者確認を強制執行
			if ( strtotime( '-1 week' ) > strtotime( $v['post_modified'] ) ) {
				$postarr['meta_input']['事業者確認'] = array( '確認済', '2022-10-30 00:00:00', 'ssofice' );
			}

			// 登録済みか調査
			$args = array(
				'post_type'   => 'post',
				'meta_key'    => '_neng_id',
				'meta_value'  => $v['ID'],
				'post_status' => 'any',
			);
			// 裏カスタムフィールドのNENGの投稿IDで登録済み調査
			$p = get_posts( $args );
			// 登録済みの場合
			if ( ! empty( $p ) ) {
				$p = $p[0];
				// 事業者確認を強制執行
				$confirm = get_post_meta( $p->ID, '事業者確認', true );
				if ( empty( $confirm ) ) {
					$confirm = array(
						strtotime( '-2 week' ) > strtotime( $p->post_modified ) ? '確認済' : '確認未',
						'2022-10-30 00:00:00',
						'ssofice',
					);
					update_post_meta( $p->ID, '事業者確認', $confirm );
				}
				$neng_ids[] = $p->ID;
				// 更新されてない場合はスキップ
				if ( $p->post_modified === $postarr['post_modified'] ) {
					continue;
				}
				$postarr['ID'] = $p->ID;
				// ログ生成
				$this->log( array( ...$logs, "「{$p->post_title}」を更新しました。{$p->post_modified}  {$v['post_modified']}" ) );
			}
			add_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
			$neng_ids[] = wp_insert_post( $postarr );
			remove_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ) );
		}
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		update_option( "n2syncing-{$params['paged']}", 0 );

		// NENG登録済みの投稿idをjsonで返す
		echo wp_json_encode( $neng_ids );

		exit;
	}

	/**
	 * N2ユーザーデータ吸い上げ
	 * - 既存ユーザーは更新
	 * - N1にいなくなったら削除
	 * - N1ダイレクト時は強制生パスワード
	 */
	public function sync_users() {

		if ( is_main_site() ) {
			exit;
		}
		$before = microtime( true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		global $n2;

		// 同期元
		$from = isset( $_GET['action'] )
			? str_replace( 'n2_sync_users_', '', $_GET['action'] )
			: 'from_n1';

		switch ( $from ) {
			case 'from_n1':
				$json = wp_remote_get( "{$this->n1_ajax_url}?action=userdata" )['body'];
				break;
			case 'from_spreadsheet':
				$args = array(
					'body' => array(
						'auth'    => $this->spreadsheet_auth,
						'sheetid' => '1lIYQRNRLdZytrE3n9ANIjfXZWjD37uGdWXMvjfaINDs',
						'range'   => 'user!A:Z',
					),
				);
				// データ取得
				$data = wp_remote_post( $this->spreadsheet_api_url, $args );
				$json = $data['body'];
				break;
		}

		$data = json_decode( $json, true );

		// IP制限 or データが無い
		if ( ! $data ) {
			$text   = $json ?: 'データがありません。';
			$logs[] = $text;
			$this->log( $logs );
			echo $text;
			exit;
		}

		// データの整形（N1・スプレットシートを共通にする）
		$data = array_map(
			function( $v ) use ( $from ) {
				if ( isset( $v['data'] ) ) {
					unset( $v['data']['ID'] );
					// 権限変換用配列
					$role = array(
						'administrator' => 'ss-crew',
						'contributor'   => 'jigyousya',
					);
					// dataの変換・追加
					$v['data']['role']                     = $role[ $v['roles'][0] ];
					$v['data']['portal_site_display_name'] = $v['data']['portal'] ?: '';
					$v['data'][ $from ]                    = 1;
					return $v['data'];
				}
				$v[ $from ] = 1;
				return $v;
			},
			$data
		);

		// echo "<pre>";print_r($data);exit;

		// N1との差分削除用配列
		$from_ids = array();

		// ユーザー登録
		foreach ( $data as $k => $userdata ) {
			// フルフロンタルは除外
			if ( 'fullfrontal' === $userdata['user_login'] ) {
				continue;
			}

			// ユーザーメタ追加（主にスプレットシート）
			$meta_array = array(
				$from,
				'portal_site_display_name',
				// ここに追加したいメタフィールド名
			);
			foreach ( $meta_array as $meta_name ) {
				if ( isset( $userdata[ $meta_name ] ) ) {
					$userdata['meta_input'][ $meta_name ] = $userdata[ $meta_name ];
					unset( $userdata[ $meta_name ] );
				}
			}

			// 既存ユーザーは更新するのでIDを突っ込む
			$user = get_user_by( 'login', $userdata['user_login'] );
			if ( $user ) {
				$userdata['ID'] = $user->ID;
			}

			// パスワードの適切な加工
			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ), 10, 4 );
			$from_ids[] = wp_insert_user( $userdata );
			remove_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ) );
		}

		// NENGから削除されているものを削除（refreshパラメータで完全同期 or $from以外で追加したものに関してはスルー）
		if ( 'from_n1' === $from ) {
			$n2ids = get_users( isset( $_GET['refresh'] ) ? 'fields=ids' : "meta_key={$from}&meta_value=1&fields=ids" );
			if ( count( $from_ids ) < count( $n2ids ) ) {
				$deleted_ids = array_diff( $n2ids, $from_ids );
				foreach ( $deleted_ids as $del ) {
					wp_delete_user( $del );
				}
			}
		}
		echo "N2-User-Sync「{$n2->town}」ユーザーデータを同期しました。";
		$logs[] = 'ユーザーシンクロ完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
		exit;
	}

	/**
	 * スプレットシートから返礼品のインポート
	 */
	public function insert_post_from_spreadsheet() {
		global $n2;
		$before = microtime( true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		$args = array(
			'body' => array(
				'auth'    => $this->spreadsheet_auth,
				'sheetid' => '1lIYQRNRLdZytrE3n9ANIjfXZWjD37uGdWXMvjfaINDs',
				'range'   => 'item!A:ZZ',
			),
		);
		// データ取得
		$data = wp_remote_post( $this->spreadsheet_api_url, $args );
		$data = json_decode( $data['body'], true );

		// IP制限等で終了のケース
		if ( ! $data ) {
			$text = '認証情報が間違っている、またはデータが存在しないので終了します。';
			$logs[] = $text;
			$this->log( $logs );
			echo $text;
			exit;
		}
		// 区切り文字
		$sep = '/[,|、|\s|\/|\||｜|／]/u';
		foreach ( $data as $d ) {
			$postarr                = array();
			$postarr['post_title']  = $d['タイトル'];
			$postarr['post_author'] = $this->get_userid_by_last_name( $d['事業者コード'] );
			unset( $d['タイトル'], $d['事業者コード'], $d['事業者名'] );
			// 寄附金額固定（入力あれば固定）
			$d['寄附金額固定'] = array( $d['寄附金額固定'] ? '固定する' : '' );
			// 商品タイプ（入力値をそのまま出力）
			$d['商品タイプ'] = array( $d['商品タイプ'] );
			// アレルギーの有無確認（入力あればアレルギー品目あり）
			$d['アレルギー有無確認'] = array( $d['アレルギー有無確認'] ? 'アレルギー品目あり' : '' );
			// アレルゲン（label,value必要）
			$d['アレルゲン'] = array_map(
				function( $v ) use ( $n2 ) {
					return array(
						'label' => $v,
						'value' => (string) array_search( $v, $n2->custom_field['事業者用']['アレルゲン']['option'], true ),
					);
				},
				// 区切り文字列でいい感じに配列化
				array_values( array_filter( preg_split( $sep, $d['アレルゲン'] ) ) )
			);
			// 取り扱い方法（区切り文字列でいい感じに配列化）
			$d['取り扱い方法'] = array_values( array_filter( preg_split( $sep, $d['取り扱い方法'] ) ) );
			// $postarrにセット
			$postarr['meta_input'] = $d;
			wp_insert_post( $postarr );
		}
		echo "N2-Insert-Posts-From-Spreadsheet「{$n2->town}の返礼品」スプレットシートからの追加完了！" . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$logs[] = '返礼品の追加完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
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
	 * N1からの場合は強制生パスワード注入
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
	public function insert_user_pass( $data, $update, $user_id, $userdata ) {
		$data['user_pass'] = isset( $userdata['meta_input']['from_n1'] )
			? wp_unslash( $userdata['user_pass'] )
			: wp_hash_password( $userdata['user_pass'] );
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
