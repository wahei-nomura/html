<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Sync' ) ) {
	$GLOBALS['n2_sync'] = new N2_Sync();
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
	 * 同期の間隔（複数起動するとへんな挙動になるため）
	 *
	 * @var int
	 */
	private $sleep = 300;

	/**
	 * ファイルのリダイレクト対策
	 *
	 * @var array
	 */
	private $redirect = array(
		'from' => '#steamship.co.jp/[a-z]*/wp-content/uploads#',
		'to'   => 'steamship.co.jp/wp-content/uploads',
	);

	/**
	 * エラー
	 *
	 * @var array
	 */
	private $error = array();

	/**
	 * エラー
	 *
	 * @var array
	 */
	private $log = array();
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		global $current_blog, $wp_filesystem;
		$n1_path           = preg_replace( '/f[0-9]{6}-/', '', $current_blog->path );
		$this->n1_ajax_url = "https://steamship.co.jp{$n1_path}wp-admin/admin-ajax.php";

		// 一旦フルフロンタル以外できなくする
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}
		add_action( 'wp_ajax_n2_sync_users_from_n1', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_nopriv_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_n2_multi_sync_posts', array( $this, 'multi_sync_posts' ) );
		add_action( 'wp_ajax_n2_get_spreadsheet_data_api', array( $this, 'get_spreadsheet_data_api' ) );
		add_action( 'wp_ajax_n2_sync_from_spreadsheet', array( $this, 'sync_from_spreadsheet' ) );
		add_action( 'wp_ajax_n2_insert_posts', array( $this, 'insert_posts' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'init', array( $this, 'cron' ) );
	}

	/**
	 * クーロン
	 */
	public function cron() {
		global $n2;
		$default  = array(
			'auto_sync_users' => 1,
			'auto_sync_posts' => 1,
		);
		$settings = get_option( 'n2_sync_settings_n1', $default );
		// N2稼働中はN1同期不要
		if ( $n2->settings['N2']['稼働中'] ) {
			$settings = array_map( fn( $v ) => 0 * $v, $settings );
		}
		add_filter( 'cron_schedules', array( $this, 'intervals' ) );
		if ( $settings['auto_sync_users'] ) {
			if ( ! wp_next_scheduled( 'wp_ajax_n2_sync_users_from_n1' ) ) {
				wp_schedule_event( time(), 'daily', 'wp_ajax_n2_sync_users_from_n1' );
			}
		} else {
			wp_clear_scheduled_hook( 'wp_ajax_n2_sync_users_from_n1' );
		}
		if ( $settings['auto_sync_posts'] ) {
			if ( ! wp_next_scheduled( 'wp_ajax_n2_multi_sync_posts' ) ) {
				wp_schedule_event( time() + 100, 'daily', 'wp_ajax_n2_multi_sync_posts' );
			}
		} else {
			wp_clear_scheduled_hook( 'wp_ajax_n2_multi_sync_posts' );
		}
	}

	/**
	 * N2 SYNC　メニューの追加
	 */
	public function add_menu() {
		global $n2;
		if ( $n2->settings['N2']['稼働中'] ) {
			add_menu_page( 'N2 SYNC', 'N2 SYNC', 'ss_crew', 'sync_ui_spreadsheet', array( $this, 'sync_ui' ), 'dashicons-update' );
			register_setting( 'n2_sync_settings_spreadsheet', 'n2_sync_settings_spreadsheet' );
		} else {
			add_menu_page( 'N2 SYNC', 'N2 SYNC', 'ss_crew', 'sync_ui_n1', array( $this, 'sync_ui' ), 'dashicons-update' );
			add_submenu_page( 'sync_ui_n1', 'N1（旧NENG）', 'N1（旧NENG）', 'ss_crew', 'sync_ui_n1', array( $this, 'sync_ui' ), 1 );
			register_setting( 'n2_sync_settings_n1', 'n2_sync_settings_n1' );
			add_submenu_page( 'sync_ui_n1', 'Google スプレットシート', 'G スプレットシート', 'ss_crew', 'sync_ui_spreadsheet', array( $this, 'sync_ui' ), 2 );
			register_setting( 'n2_sync_settings_spreadsheet', 'n2_sync_settings_spreadsheet' );
		}
	}

	/**
	 * 同期の為のUI
	 */
	public function sync_ui() {
		global $n2;
		$template = $_GET['page'];
		$tabs     = array(
			'sync_ui_n1'          => 'N1（旧NENG）',
			'sync_ui_spreadsheet' => 'Googleスプレットシート',
		);
		?>
		<div class="wrap">
			<h1>N2 SYNC</h1>
			<?php if ( ! $n2->settings['N2']['稼働中'] ) : ?>
			<div id="crontrol-header">
				<nav class="nav-tab-wrapper">
					<?php
					foreach ( $tabs as $page => $name ) {
						printf( '<a href="?page=%s" class="nav-tab%s">%s</a>', $page, $page === $template ? ' nav-tab-active' : '', $name );
					}
					?>
				</nav>
			</div>
			<?php endif; ?>
			<ul style="padding: 1em; background: white; margin: 2em 0; border: 1px solid;">
				<li>※ 返礼品もユーザーもIDがあれば更新、無ければ追加します。</li>
				<li>※ <b>シート雛形</b>は<a href="https://docs.google.com/spreadsheets/d/13HZn6w6S0XaXgAd_3RSkB46XUrRDkMQArVeE99pFD9Q/edit#gid=0" target="_blank">ココ</a>。複製して使用してください！</li>
			</ul>
			<div id="n2sync">
				<?php echo $this->$template(); ?>
			</div>
			<div id="n2sync-loading" class="spinner"></div>
		</div>
		<?php
	}

	/**
	 * N1 同期の為のUI
	 */
	public function sync_ui_n1() {
		global $n2;
		$default  = array(
			'auto_sync_users' => 1,
			'auto_sync_posts' => 1,
		);
		$label    = array(
			'auto_sync_users' => 'ユーザー',
			'auto_sync_posts' => '返礼品',
		);
		$settings = get_option( 'n2_sync_settings_n1', $default );
		?>
		<h2>N1（旧NENG）との同期</h2>
		<ul style="padding: 1em; background: white; margin: 2em 0; border: 1px solid;">
			<li>※ N1（旧NENG）と同期した情報（返礼品・ユーザー）が、N1から無くなるとN2からも削除されます。</li>
			<li>※ N2で追加された情報（返礼品・ユーザー）は、同期しても保持されます。</li>
		</ul>
		<div style="padding: 1em 0;">
			<a href="<?php echo "{$n2->ajaxurl}?action=n2_sync_users_from_n1"; ?>" class="button button" target="_blank" style="margin-right: 1em;">
				今すぐユーザーを同期
			</a>
			<a href="<?php echo "{$n2->ajaxurl}?action=n2_multi_sync_posts"; ?>" class="button button-primary" target="_blank">
				今すぐ返礼品を同期
			</a>
		</div>
		<form method="post" action="options.php">
			<?php settings_fields( 'n2_sync_settings_n1' ); ?>
			<table class="widefat striped" style="margin-bottom: 2em;">
				<?php foreach ( $settings as $name => $value ) : ?>
				<tr>
					<th><?php echo $label[ $name ]; ?>の自動同期</th>
					<td>
						<label style="margin-right: 2em;">
							<input type="radio" name="n2_sync_settings_n1[<?php echo $name; ?>]" value="1" <?php checked( $value, 1 ); ?>> ON
						</label>
						<label>
							<input type="radio" name="n2_sync_settings_n1[<?php echo $name; ?>]" value="0" <?php checked( $value, 0 ); ?>> OFF
						</label>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
	}

	/**
	 * Googleスプレットシート同期の為のUI
	 */
	public function sync_ui_spreadsheet() {
		get_template_part( 'template/sync/item' );
		get_template_part( 'template/sync/user' );
		get_template_part( 'template/sync/save' );
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
		global $n2;
		if ( is_main_site() || $n2->settings['N2']['稼働中'] ) {
			exit;
		}

		$before = microtime( true );

		// params
		$params = array(
			'action'         => 'postsdata',
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => $_GET['posts_per_page'] ?? 100,
			'paged'          => 1,
		);
		$json   = wp_remote_get( "{$this->n1_ajax_url}?" . http_build_query( $params ) )['body'];
		$data   = json_decode( $json, true );
		// ページ数取得
		$max_num_pages = $data['max_num_pages'];
		$found_posts   = $data['found_posts'];

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		// IP制限等で終了のケース
		if ( ! $data || ! $found_posts ) {
			$logs[] = $json;
			$this->insert_log_file( $logs );
			echo $json;
			exit;
		}

		// 強制リフレッシュ同期
		if ( isset( $_GET['refresh'] ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->posts}" );
			$wpdb->query( "DELETE FROM {$wpdb->postmeta};" );
		}

		// $params変更
		$params['action']  = 'n2_sync_posts';
		$params['n2nonce'] = wp_create_nonce( 'n2nonce' );
		$params['before']  = $_GET['before'] ?? false;

		// n2_sync_posts に Multi cURL
		$mh       = curl_multi_init();
		$ch_array = array();
		while ( $max_num_pages >= $params['paged'] ) {

			// ツイン起動しないためにSync中のフラグをチェックして終了
			$sleep = $_GET['sleep'] ?? $this->sleep;
			if ( $sleep > ( strtotime( 'now' ) - get_option( "n2syncing-{$params['paged']}", strtotime( '-1 hour' ) ) ) ) {
				$logs[] = '2重起動防止のため終了';
				$this->insert_log_file( $logs );
				echo '2重起動防止のため終了';
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
				// ログインセッションを渡してajax
				CURLOPT_HTTPHEADER     => array(
					'Cookie: ' . urldecode( http_build_query( $_COOKIE, '', '; ' ) ),
				),
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
		$this->insert_log_file( $logs );
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

		// params
		$params            = $_GET;
		$params['action']  = 'postsdata';
		$params['orderby'] = 'ID';

		if ( ! wp_verify_nonce( $params['n2nonce'] ?? '', 'n2nonce' ) ) {
			$msg    = '不正アクセス';
			$logs[] = $msg;
			$this->insert_log_file( $logs );
			echo $msg;
			exit;
		}

		// Syncフラグを記録
		update_option( "n2syncing-{$params['paged']}", strtotime( 'now' ) );

		// 投稿を部分同期
		$json = wp_remote_get( "{$this->n1_ajax_url}?" . http_build_query( $params ) )['body'];
		$data = json_decode( $json, true );

		// IP制限等で終了のケース
		if ( ! $data ) {
			$logs[] = $json;
			$this->insert_log_file( $logs );
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
				'post_status'       => 'publish' === $v['post_status'] ? 'registered' : $v['post_status'],
				'post_date'         => $v['post_date'],
				'post_date_gmt'     => $v['post_date_gmt'],
				'post_modified'     => $v['post_modified'],
				'post_modified_gmt' => $v['post_modified_gmt'],
				'post_type'         => $v['post_type'],
				'post_title'        => $v['post_title'],
				'post_author'       => $this->get_userid_by_usermeta( 'last_name', $v['post_author_last_name'] ),
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

			// 金額系のやつは数値だけにする
			$postarr['meta_input']['価格'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $postarr['meta_input']['価格'], 'n' ) );
			$postarr['meta_input']['寄附金額'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $postarr['meta_input']['寄附金額'], 'n' ) );

			// 寄附金額をロックする
			$postarr['meta_input']['寄附金額固定'] = 'draft' !== $postarr['post_status'] ? array( '固定する' ) : array();

			// 「取り扱い方法1〜2」を「取り扱い方法」に変換
			$handling                        = array_filter( $postarr['meta_input'], fn( $k ) => preg_match( '/取り扱い方法[0-9]/u', $k ), ARRAY_FILTER_USE_KEY );
			$postarr['meta_input']['取り扱い方法'] = array_values( array_filter( array_values( $handling ), fn( $v ) => $v ) );
			foreach ( array_keys( $handling ) as $k ) {
				unset( $postarr['meta_input'][ $k ] );
			}

			// 「商品画像１〜８」を「商品画像」に変換
			$images = array_filter( $postarr['meta_input'], fn( $k ) => preg_match( '/商品画像[０-９]/u', $k ), ARRAY_FILTER_USE_KEY );
			// 値の浄化
			$postarr['meta_input']['商品画像'] = array_filter( array_values( $images ), fn( $v ) => $v );
			foreach ( $postarr['meta_input']['商品画像'] as $index => $value ) {
				// 商品画像説明の場合
				if ( ! is_array( $value ) ) {
					if ( isset( $postarr['meta_input']['商品画像'][ $index - 1 ] ) ) {
						$postarr['meta_input']['商品画像'][ $index - 1 ]['description'] = $value;
					}
					unset( $postarr['meta_input']['商品画像'][ $index ] );
					continue;
				}
				if ( ! isset( $value['sizes'] ) ) {
					// pdfの場合jpgを入れる
					$postarr['meta_input']['商品画像'][ $index ]['url'] = preg_replace( '/\.(\w+)$/', '-$1.jpg', $value['url'] );
					// サムネイルにurlを入れる
					$postarr['meta_input']['商品画像'][ $index ]['sizes']['thumbnail'] = $postarr['meta_input']['商品画像'][ $index ]['url'];
				}
			}
			$postarr['meta_input']['商品画像'] = array_values( $postarr['meta_input']['商品画像'] );

			// URLの置換のためにjson化
			$str = wp_json_encode( $postarr['meta_input']['商品画像'], JSON_UNESCAPED_SLASHES );
			if ( $str ) {
				// N1自治体ごとリダイレクトのため実URLに変更
				$str = preg_replace( $this->redirect['from'], $this->redirect['to'], $str );
				// jsonを配列に戻して代入
				$postarr['meta_input']['商品画像'] = json_decode( $str, true );
			}
			foreach ( array_keys( $images ) as $k ) {
				unset( $postarr['meta_input'][ $k ] );
			}
			if ( isset( $postarr['meta_input']['商品画像をzipファイルでまとめて送る']['url'] ) ) {
				$postarr['meta_input']['N1zip'] = $postarr['meta_input']['商品画像をzipファイルでまとめて送る']['url'];
				$postarr['meta_input']['N1zip'] = preg_replace( $this->redirect['from'], $this->redirect['to'], $postarr['meta_input']['N1zip'] );
			}
			unset( $postarr['meta_input']['商品画像をzipファイルでまとめて送る'] );

			// 商品タイプ
			$postarr['meta_input']['商品タイプ'] = array();
			// やきもの
			if ( 'やきもの' === ( $postarr['meta_input']['やきもの'] ?? '' ) ) {
				$postarr['meta_input']['商品タイプ'][] = 'やきもの';
				// オリジナル商品変換
				if ( isset( $postarr['meta_input']['オリジナル商品'] )  ) {
					if ( ! empty( $postarr['meta_input']['オリジナル商品'] ) ) {
						$postarr['meta_input']['返礼品ルール'] = array( 'A', 'B' );
					}
					$postarr['meta_input']['オリジナル商品'] = match ( $postarr['meta_input']['オリジナル商品'] ) {
						'適' => array( 'オリジナル商品である' ),
						default => array(),
					};
				}
			}
			if ( 'やきもの' !== ( $postarr['meta_input']['やきもの'] ?? '' ) ) {
				unset( $postarr['meta_input']['製造元'], $postarr['meta_input']['オリジナル商品'], $postarr['meta_input']['オリジナル商品理由'] );
			}
			// eチケット
			if ( '該当する' === ( $postarr['meta_input']['eチケット'] ?? '' ) ) {
				$postarr['meta_input']['商品タイプ'][] = 'eチケット';
			}
			unset( $postarr['meta_input']['やきもの'], $postarr['meta_input']['eチケット'] );

			// クレジット決済限定
			if ( 'クレジット決済限定' === ( $postarr['meta_input']['クレジット決済限定'] ?? '' ) ) {
				$postarr['meta_input']['オンライン決済限定'][] = '限定';
			}
			unset( $postarr['meta_input']['クレジット決済限定'] );

			// アレルギー関連
			if ( isset( $postarr['meta_input']['アレルゲン'] ) ) {
				$allergen = array_column( $postarr['meta_input']['アレルゲン'], 'value' );
				if ( $allergen ) {
					if ( in_array( '食品ではない', $allergen, true ) ) {
						unset( $postarr['meta_input']['アレルゲン'] );
					} else {
						$postarr['meta_input']['商品タイプ'][] = '食品';
						if ( in_array( 'アレルゲンなし食品', $allergen, true ) ) {
							$postarr['meta_input']['アレルギー有無確認'] = array( '' );
							unset( $postarr['meta_input']['アレルゲン'] );
						} else {
							$postarr['meta_input']['アレルギー有無確認'] = array( 'アレルギー品目あり' );
							// アレルゲンをラベルだけに変更
							$postarr['meta_input']['アレルゲン'] = array_column( $postarr['meta_input']['アレルゲン'], 'label' );
							// アレルゲンをいい感じにN2に存在するものに変換する
							$postarr['meta_input']['アレルゲン'] = $this->change_n2_allergen( $postarr['meta_input']['アレルゲン'] );

						}
					}
				}
			}
			// 地場産品類型互換
			$postarr['meta_input']['地場産品類型'] = $postarr['meta_input']['地場産品類型']['value'] ?? '';

			// LH表示名
			$postarr['meta_input']['LH表示名'] = $postarr['meta_input']['略称'] ?? '';
			unset( $postarr['meta_input']['略称'] );

			// キャッチコピー１と楽天カテゴリーの変換
			$postarr['meta_input']['キャッチコピー']    = $postarr['meta_input']['キャッチコピー１'] ?? '';
			$postarr['meta_input']['楽天SPAカテゴリー'] = $postarr['meta_input']['楽天カテゴリー'] ?? '';
			unset( $postarr['meta_input']['キャッチコピー１'], $postarr['meta_input']['楽天カテゴリー'] );

			// 発送サイズ関連
			$postarr['meta_input']['発送サイズ'] = match ( $postarr['meta_input']['発送サイズ'] ?? '' ) {
				'レターパック' => 'レターパックプラス',
				'ゆうパケット1cm' => 'ゆうパケット厚さ1cm',
				'ゆうパケット2cm' => 'ゆうパケット厚さ2cm',
				'ゆうパケット3cm' => 'ゆうパケット厚さ3cm',
				'その他（ヤマト以外）' => 'その他',
				default => $postarr['meta_input']['発送サイズ'] ?? '',
			};

			// 自治体確認
			if ( isset( $postarr['meta_input']['市役所確認'] ) ) {
				$postarr['meta_input']['自治体確認'] = match ( $postarr['meta_input']['市役所確認'] ) {
					'不要', '要', '済' => '承諾',
					default => '未',
				};
				unset( $postarr['meta_input']['市役所確認'] );
			}

			// 旧コードを社内共有事項に付ける
			if ( ! empty( $postarr['meta_input']['旧コード'] ) ) {
				$postarr['meta_input']['社内共有事項'] .= "\n旧コード：{$postarr['meta_input']['旧コード']}";
				unset( $postarr['meta_input']['旧コード'] );
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

				if ( $params['before'] ) {
					// beforeパラメータの日時以降はスキップ
					if ( $p->post_modified > $params['before'] ) {
						continue;
					}
					// N2のカスタムフィールド（空は削除）
					$meta = array_keys( array_filter( (array) json_decode( $p->post_content, true ) ) );
					// N2に値が入っているものを排除
					$postarr['meta_input'] = array_filter(
						$postarr['meta_input'],
						function ( $v, $k ) use ( $meta ) {
							// N2のカスタムフィールドに値が無い　かつ　N1も空じゃない
							return ! in_array( $k, $meta, true ) && ! empty( array_filter( (array) $v ) );
						},
						ARRAY_FILTER_USE_BOTH
					);
					if ( empty( $postarr['meta_input'] ) ) {
						continue;
					}
					$postarr = array_filter( $postarr, fn( $k ) => 'meta_input' === $k, ARRAY_FILTER_USE_KEY );// メタデータのみに
					$postarr = wp_parse_args( $postarr, wp_parse_args( $p ) );// 既存の返礼品データにメタデータのみをマージ
					// 更新日時とpost_contentは破棄
					unset( $postarr['post_modified'], $postarr['post_modified_gmt'], $postarr['post_content'] );
				} else {
					// 更新されてない場合はスキップ
					if ( $p->post_modified >= $postarr['post_modified'] ) {
						continue;
					}
				}
				$postarr['ID'] = $p->ID;
				// ログ生成
				$this->insert_log_file( array( ...$logs, "「{$p->post_title}」を更新しました。{$p->post_modified}  {$v['post_modified']}" ) );
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
	 * - スプレットシートからも追加
	 */
	public function sync_users() {

		if ( is_main_site() ) {
			exit;
		}
		require_once ABSPATH . 'wp-admin/includes/user.php';
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
				if ( $n2->settings['N2']['稼働中'] ) {
					exit;
				}
				$json = wp_remote_get( "{$this->n1_ajax_url}?action=userdata" )['body'];
				$data = json_decode( $json, true );
				break;
			case 'from_spreadsheet':
				$default  = array(
					'id'         => '',
					'user_range' => '',
					'item_range' => '',
				);
				$settings = get_option( 'n2_sync_settings_spreadsheet', $default );

				// GETパラメータ優先、なければDB
				$input_id         = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS );
				$input_user_range = filter_input( INPUT_GET, 'user_range', FILTER_SANITIZE_SPECIAL_CHARS );

				$sheet_id   = $input_id && '' !== $input_id ? $input_id : $settings['id'];
				$user_range = $input_user_range && '' !== $input_user_range ? $input_user_range : $settings['user_range'];
				// データ取得
				$data = $this->get_spreadsheet_data( $sheet_id, $user_range );
				break;
		}
		// IP制限 or データが無い
		if ( ! $data ) {
			$text   = $json ?: 'データがありません。';
			$logs[] = $text;
			$this->insert_log_file( $logs );
			echo $text;
			exit;
		}

		// データの整形（N1・スプレットシートを共通にする）
		$data = array_map(
			function( $v ) use ( $from ) {
				// N1の場合
				if ( isset( $v['data'] ) ) {
					unset( $v['data']['ID'] );
					// 権限変換用配列
					$role = array(
						'administrator' => match ( 1 ) {
							preg_match( '/市役所$/', $v['data']['first_name'] ) => 'local-government',
						default => 'ss-crew',
						},
						'contributor'   => 'jigyousya',
					);
					// dataの変換・追加
					$v['data']['role']                     = $role[ $v['roles'][0] ];
					$v['data']['portal_site_display_name'] = $v['data']['portal'] ?: '';
					$v['data'][ $from ]                    = 1;
					return $v['data'];
				} else {
					// スプレットシートのラベル名をいい感じに存在するmeta_nameに変更
					$label2name = array(
						'/.*(アカウント|ログイン|ユーザー|id).*/ui' => 'user_login',
						'/.*(メール|mail).*/ui'           => 'user_email',
						'/.*(パスワード|pass).*/ui'         => 'user_pass',
						'/.*事業者.*名.*/ui'               => 'first_name',
						'/.*事業者.*コード.*/ui'             => 'last_name',
						'/.*ポータル.*表示名.*/ui'            => 'portal_site_display_name',
						'/.*(権限|ロール).*/ui'             => 'role',
					);
					// キーの変換の準備
					$keys   = array_keys( $v );
					$values = array_values( $v );
					// キーの変換
					$keys = preg_replace( array_keys( $label2name ), array_values( $label2name ), $keys );
					// $vの再生成
					$v = array_combine( $keys, $values );
					// 表示名自動生成
					if ( ! empty( $v['last_name'] ) && ! empty( $v['first_name'] ) ) {
						$v['display_name'] = "{$v['last_name']} {$v['first_name']}";
					}
					// from 付与
					$v[ $from ] = 1;
					return $v;
				}
			},
			$data
		);

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

			// user_login_beforeでuser_loginを変更可能にする（要 N2_Setusers->change_user_login）
			$user = get_user_by( 'login', $userdata['user_login_before'] ?? $userdata['user_login'] );
			unset( $userdata['user_login_before'] );
			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ), 10, 4 );
			// 既存ユーザーの場合
			if ( $user ) {
				$userdata['ID'] = $user->ID;// 既存ユーザーは更新するのでIDを突っ込む
				$from_id        = wp_update_user( $userdata );
				if ( ! is_wp_error( $from_id ) ) {
					$from_ids[] = $from_id;
				}
			} else {
				// パスワードの適切な加工
				$from_id = wp_insert_user( $userdata );
				if ( ! is_wp_error( $from_id ) ) {
					$from_ids[] = $from_id;
				}
			}
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

		$this->insert_log_file( $logs );
		exit;
	}

	/**
	 * スプレットシートユーザー同期
	 * 一意の値のもの　user_login user_email
	 *
	 * @param array $data スプシから作った返礼品データ
	 */
	public function sync_spreadsheet_user( $data = array() ) {
		global $n2, $wp_roles;
		// 区切り文字
		$sep = '/[,|、|\s|\/|\||｜|／]/u';
		// 投稿配列
		$userdata = array();
		// 存在するrole
		$exist_roles = array_keys( $wp_roles->role_names );
		foreach ( $data as $k => $d ) {
			$name_label = array(
				'user_login'               => '/.*(アカウント|ログイン|ユーザー).*/ui',
				'user_email'               => '/.*(メール|mail).*/ui',
				'user_pass'                => '/.*(パスワード|pass).*/ui',
				'first_name'               => '/.*事業者.*名.*/ui',
				'last_name'                => '/.*事業者.*コード.*/ui',
				'portal_site_display_name' => '/.*ポータル.*表示名.*/ui',
				'role'                     => '/.*(権限|ロール).*/ui',
			);
			// キーの変換の準備
			$keys   = array_keys( $d );
			$values = array_values( $d );
			// キーの変換
			$keys = preg_replace( array_values( $name_label ), array_keys( $name_label ), $keys );
			// $dの再生成
			$d = array_combine( $keys, $values );
			// IDを追加
			if ( isset( $d['id'] ) || isset( $d['ID'] ) ) {
				$d['ID'] = mb_convert_kana( $d['id'] ?? $d['ID'], 'n' );
				unset( $d['id'] );
			}
			// 既存のユーザーかチェック
			if ( empty( $d['ID'] ) ) {
				// user_loginチェック
				if ( isset( $d['user_login'] ) ) {
					$user = get_user_by( 'login', $d['user_login'] );
					if ( $user ) {
						$this->error[ $k ][] = "ログインアカウント名「{$d['user_login']}」が既に存在します。このユーザーを更新したい場合は、id列に「{$user->ID}」を入力して下さい。";
					}
				}
				// user_emailチェック
				if ( isset( $d['user_email'] ) ) {
					$user = get_user_by( 'email', $d['user_email'] );
					if ( $user ) {
						$this->error[ $k ][] = "メールアドレス「{$d['user_email']}」が既に存在します。このユーザーを更新したい場合は、id列に「{$user->ID}」を入力して下さい。";
					}
				}
			}
			// 表示名自動生成
			if ( ! empty( $d['last_name'] ) && ! empty( $d['first_name'] ) ) {
				$d['display_name'] = "{$d['last_name']} {$d['first_name']}";
			}
			if ( isset( $d['role'] ) ) {
				// roleの変換
				$d['role'] = match ( $d['role'] ) {
					'SSクルー', 'ss', 'sscrew' => 'ss-crew',
					'自治体', '役場', '市役所', '県庁', 'jichitai', 'jititai' => 'local-government',
					'事業者', 'jigyousha' => 'jigyousya',
					default => $d['role'],
				};
				if ( ! in_array( $d['role'], $exist_roles, true ) ) {
					$this->error[ $k ][] = "role「{$d['role']}」が存在しません。";
				}
			}
			// ユーザーメタ
			$meta_array = array(
				'portal_site_display_name',
				// ここに追加したいメタフィールド名
			);
			foreach ( $meta_array as $meta_name ) {
				if ( isset( $d[ $meta_name ] ) ) {
					$d['meta_input'][ $meta_name ] = $d[ $meta_name ];
					unset( $d[ $meta_name ] );
				}
			}
			$userdata[ $k ] = $d;
		}
		$this->check_error_spreadsheet();// エラー発生の場合はストップ
		foreach ( $userdata as $user ) {
			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ), 10, 4 );
			$fn = $user['ID'] ? 'wp_update_user' : 'wp_insert_user';
			$id = $fn( $user );
			remove_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ) );
			// ログ追加
			if ( $id ) {
				$this->log[ $id ] = array(
					'mode'         => empty( $user['ID'] ) ? 'insert' : 'update',
					'user_login'   => get_the_author_meta( 'user_login', $id ),
					'display_name' => get_the_author_meta( 'display_name', $id ),
				);
			}
		}
	}

	/**
	 * スプレットシート返礼品同期
	 *
	 * @param array $data スプシから作った返礼品データ
	 */
	public function sync_spreadsheet_item( $data = array() ) {
		global $n2;
		// 区切り文字
		$sep = '/[,|、|\s|\/|\||｜|／]/u';
		// 投稿配列
		$postarr = array();
		foreach ( $data as $k => $d ) {
			$d = array_map( fn( $v ) => wp_slash( $v ), $d );
			// カスタムフィールド以外
			{
				// ID（大文字・小文字対応）
				if ( ! empty( $d['id'] ) || ! empty( $d['ID'] ) ) {
					$postarr[ $k ]['ID'] = mb_convert_kana( $d['id'] ?? $d['ID'], 'n' );
				}
				// post_title
				if ( ! empty( $d['タイトル'] ) ) {
					$postarr[ $k ]['post_title'] = $d['タイトル'];
					if ( mb_strlen( $d['タイトル'] ) > 100 ) {
						$this->error[ $k ][] = 'タイトルが長すぎます。タイトルは100文字までにして下さい。';
					}
				}
				// post_status
				if ( ! empty( $d['ステータス'] ) ) {
					$postarr[ $k ]['post_status'] = match ( 1 ) {
						preg_match( '/入力中/u', $d['ステータス'] ) => 'draft',
						preg_match( '/確認中/u', $d['ステータス'] ) => 'pending',
						preg_match( '/準備中/u', $d['ステータス'] ) => 'publish',
						preg_match( '/登録済/u', $d['ステータス'] ) => 'registered',
						preg_match( '/非公開/u', $d['ステータス'] ) => 'private',
						default => $d['ステータス'],
					};
					// N2のステータス
					$n2_statuses = array( ...array_keys( get_post_statuses() ), 'registered' );
					if ( ! in_array( $postarr[ $k ]['post_status'], $n2_statuses, true ) ) {
						$this->error[ $k ][] = "存在しないステータス「{$d['ステータス']}」が設定されています。";
					}
				}
				// 事業者コードからuserid取得
				if ( ! empty( $d['事業者コード'] ) ) {
					$userid = $this->get_userid_by_usermeta( 'last_name', strtoupper( mb_convert_kana( $d['事業者コード'], 'n' ) ) );
				}
				// 事業者名からuserid取得
				if ( ! empty( $d['事業者名'] ) && empty( $userid ) ) {
					$userid = $this->get_userid_by_usermeta( 'first_name', $d['事業者名'] );
				}
				// post_author
				if ( ! empty( $userid ) ) {
					$postarr[ $k ]['post_author'] = $userid;
				}
				unset( $d['id'], $d['タイトル'], $d['ステータス'], $d['事業者コード'], $d['事業者名'] );// 破棄
			}
			// 返礼品コード
			if ( isset( $d['返礼品コード'] ) ) {
				$d['返礼品コード'] = strtoupper( mb_convert_kana( $d['返礼品コード'], 'n' ) );
			}
			// 寄附金額固定（入力あれば固定）
			if ( isset( $d['寄附金額固定'] ) ) {
				$d['寄附金額固定'] = array( $d['寄附金額固定'] ? '固定する' : '' );
			}
			// 寄附金額を半角数字のみに
			if ( isset( $d['寄附金額'] ) ) {
				$d['寄附金額'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $d['寄附金額'], 'n' ) );
			}
			// 定期便
			if ( isset( $d['定期便'] ) ) {
				$d['定期便'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $d['定期便'], 'n' ) ) ?: 1;
			}
			// 価格を半角数字のみに
			if ( isset( $d['価格'] ) ) {
				$d['価格'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $d['価格'], 'n' ) );
				// 自動価格調整
				$d['価格'] = N2_Donation_Amount_API::adjust_price(
					array(
						'price'        => (int) $d['価格'],
						'subscription' => (int) $d['定期便'],
					)
				);
			}
			// 全商品ディレクトリID
			if ( isset( $d['全商品ディレクトリID'] ) ) {
				$d['全商品ディレクトリID'] = mb_convert_kana( $d['全商品ディレクトリID'], 'n' );
				if ( preg_match( '/[^0-9]/', $d['全商品ディレクトリID'] ) ) {
					$this->error[ $k ][] = '全商品ディレクトリIDは数値です。';
				}
			}
			// 商品属性
			if ( isset( $d['商品属性'] ) ) {
				$d['商品属性'] = array_values( array_filter( preg_split( '/\r\n|\n|\r/', $d['商品属性'] ) ) );	
				$d['商品属性'] = preg_replace( '/:{2,}/i', ':', $d['商品属性'] );// コロンが二つ続いてると単位と見做されるので大文字小文字共にreplace
				$d['商品属性'] = array_map(
					function( $value ) {
						$value = preg_split( '/:|：/', $value );
						return array(
							'nameJa'     => trim( rtrim( $value[0], '*' ) ),
							'value'      => trim( $value[1] ),
							'unitValue'  => isset( $value[2] ) ? trim( $value[2] ) : null,
							'properties' => array(
								'rmsMandatoryFlg' => (bool) strpos( $value[0], '*' ),
							),
						);
					},
					$d['商品属性']
				);
			}
			// タグID
			if ( isset( $d['タグID'] ) ) {
				$d['タグID'] = array_filter( preg_split( $sep, $d['タグID'] ) );
				foreach ( $d['タグID'] as $id ) {
					if ( preg_match( '/[^0-9]/', $id ) ) {
						$this->error[ $k ][] = 'タグIDは数値です。';
					}
				}
				$d['タグID'] = implode( '/', $d['タグID'] );
			}
			// 出品禁止ポータル
			if ( isset( $d['出品禁止ポータル'] ) ) {
				$d['出品禁止ポータル'] = array_filter( preg_split( $sep, $d['出品禁止ポータル'] ) );
				foreach ( $d['出品禁止ポータル']  as $name ) {
					if ( ! in_array( $name, $n2->settings['N2']['出品ポータル'], true ) ) {
						$this->error[ $k ][] = "そもそも出品していないポータル「{$name}」が出品禁止ポータルに設定されています。";
					}
				}
			}
			// 地場産品類型
			if ( isset( $d['地場産品類型'] ) ) {
				$num = $d['地場産品類型'];
				// 数字は半角に
				$d['地場産品類型'] = mb_convert_kana( mb_substr( $d['地場産品類型'], 0, 1 ), 'n' ) . mb_substr( $d['地場産品類型'], 1 );
				if ( ! in_array( $d['地場産品類型'], array_map( 'strval', array_keys( $n2->custom_field['スチームシップ用']['地場産品類型']['option'] ?? array() ) ), true ) ) {
					$this->error[ $k ][] = "存在しない地場産品類型「{$num}」が設定されています。";
				}
			}
			// 商品タイプ（区切り文字列でいい感じに配列化）
			if ( isset( $d['商品タイプ'] ) ) {
				$d['商品タイプ'] = array_values( array_filter( preg_split( $sep, $d['商品タイプ'] ) ) );
				foreach ( $d['商品タイプ'] as $name ) {
					if ( ! in_array( $name, $n2->settings['N2']['商品タイプ'], true ) ) {
						$this->error[ $k ][] = "存在しない商品タイプ「{$name}」が設定されています。";
					}
				}
			}
			// アレルギーの有無確認（入力あればアレルギー品目あり）
			if ( isset( $d['アレルギー有無確認'] ) ) {
				$d['アレルギー有無確認'] = array( $d['アレルギー有無確認'] ? 'アレルギー品目あり' : '' );
			}
			// アレルゲン（完全一致じゃなく部分一致にする）
			if ( isset( $d['アレルゲン'] ) ) {
				// 区切り文字列でいい感じに配列化
				$d['アレルゲン'] = array_values( array_filter( preg_split( $sep, $d['アレルゲン'] ) ) );
				// アレルゲンをいい感じにN2に存在するものに変換する
				$d['アレルゲン'] = $this->change_n2_allergen( $d['アレルゲン'] );
			}
			// 包装対応
			foreach ( array( '包装', 'のし' ) as $name ) {
				if ( isset( $d[ "{$name}対応" ] ) ) {
					$d[ "{$name}対応" ] = match ( $d[ "{$name}対応" ] ) {
						'有り','あり','する', '対応する', '可' => '有り',
						'無し','なし', 'しない', '対応しない', '不可', '' => '無し',
						default => false,
					};
					if ( ! $d[ "{$name}対応" ] ) {
						$this->error[ $k ][] = "{$name}対応に不適切な文字が入力されています。「有り」か「無し」で入力して下さい。";
					}
				}
			}
			// 発送方法
			if ( isset( $d['発送方法'] ) ) {
				if ( ! in_array( $d['発送方法'], $n2->custom_field['事業者用']['発送方法']['option'], true ) ) {
					$this->error[ $k ][] = "存在しない発送方法「{$d['発送方法']}」が設定されています。";
				}
			}
			// 取り扱い方法（区切り文字列でいい感じに配列化）
			if ( isset( $d['取り扱い方法'] ) ) {
				$d['取り扱い方法'] = array_values( array_filter( preg_split( $sep, $d['取り扱い方法'] ) ) );
				if ( count( $d['取り扱い方法'] ) > 2 ) {
					$this->error[ $k ][] = '取り扱い方法は最大2つまでです。';
				}
				foreach ( $d['取り扱い方法'] as $name ) {
					if ( ! in_array( $name, $n2->custom_field['事業者用']['取り扱い方法']['option'], true ) ) {
						$this->error[ $k ][] = "存在しない取り扱い方法「{$name}」が設定されています。";
					}
				}
			}
			// 発送サイズの想定入力ミスに対応
			if ( isset( $d['発送サイズ'] ) ) {
				// 発送サイズの一時退避
				$delivery_size = $d['発送サイズ'];
				// 発送サイズの変換
				$d['発送サイズ'] = match ( 1 ) {
					preg_match( '/[1１][0０][0-8０-８]/u', $d['発送サイズ'], $m ) => '0' . mb_convert_kana( $m[0], 'n' ),// 0が抜けた発送サイズコードの場合
					preg_match( '/[1-9１-９]{1,2}[0０]/u', $d['発送サイズ'], $m ) => '010' . ( mb_convert_kana( $m[0], 'n' ) - 40 ) / 20,// 発送サイズ名で入れたの場合（全角にも対応）
					preg_match( '/コンパクト/u', $d['発送サイズ'] ) => '0100',
					default => $d['発送サイズ'],
				};
				$n2_delivery_sizes   = array_keys( array_filter( $n2->settings['寄附金額・送料']['送料'] ) );
				$n2_delivery_sizes[] = 'その他';// その他追加
				if ( ! in_array( $d['発送サイズ'], $n2_delivery_sizes, true ) ) {
					$this->error[ $k ][] = "存在しない発送サイズ「{$delivery_size}」が設定されています。";
				}
			}
			// 送料
			if ( isset( $d['送料'] ) ) {
				$d['送料'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $d['送料'], 'n' ) ) ?? 0;
				if ( empty( $d['送料'] ) && ! empty( $d['発送サイズ'] ) && ! empty( $d['発送方法'] ) ) {
					$delivery_code = N2_Donation_Amount_API::create_delivery_code( $d['発送サイズ'], $d['発送方法'] );
					// 送料計算
					$d['送料'] = $n2->settings['寄附金額・送料']['送料'][ $delivery_code ] ?? 0;
				}
			}
			// LHカテゴリー
			if ( isset( $d['LHカテゴリー'] ) ) {
				if ( ! in_array( $d['LHカテゴリー'], $n2->custom_field['事業者用']['LHカテゴリー']['option'], true ) ) {
					$this->error[ $k ][] = "存在しないLHカテゴリー「{$d['LHカテゴリー']}」が設定されています。";
				}
			}
			// $postarrにセット
			$postarr[ $k ]['meta_input'] = $d;
		}

		$this->check_error_spreadsheet();// エラー発生の場合はストップ
		$this->log = $this->multi_insert_posts( $postarr, 50 );
	}
	/**
	 * 同期を止める
	 */
	public function check_error_spreadsheet() {
		global $n2;
		// エラー発生の場合はストップ
		if ( ! empty( $this->error ) ) {
			$error_count = count( array_reduce( $this->error, 'array_merge', array() ) );
			$error_point = ( $n2->current_user->data->meta['error_point'] ?? 0 ) + $error_count;
			update_user_meta( $n2->current_user->ID, 'error_point', $error_point );
			printf( "%1\$s様\n\nおめでとうございます！？\n\n%2\$s件のエラーのため処理を中止し、あなたにN2エラーポイントを「%2\$spt」付与しました。\n現在のあなたの合計N2エラーポイントは%3\$sptです。（たまり過ぎると様々なN2の制限がかかります）", $n2->current_user->data->display_name, $error_count, $error_point );
			echo "\n\n行数\tエラー内容\n";
			foreach ( $this->error as $i => $values ) {
				$i = $i + 2;
				foreach ( $values as $value ) {
					echo "{$i}行目\t{$value}\n";
				}
			}
			exit;
		}
	}

	/**
	 * スプレットシート同期
	 */
	public function sync_from_spreadsheet() {
		global $n2;
		echo '<style>body{margin:0;background: black;color: white;}</style><pre style="min-height: 100%;margin: 0;padding: 1em;">';
		$default = array(
			'spreadsheetid' => '',
			'range'         => '',
			'target_cols'   => array(),
		);
		$params  = get_option( 'n2_sync_settings_spreadsheet' );
		$params  = wp_parse_args( $params, $default );
		$params  = wp_parse_args( $_GET, $params );
		// データ取得
		$data = $this->get_spreadsheet_data( $params['spreadsheetid'], $params['range'] );

		// 対象カラム指定なし
		if ( empty( $params['target_cols'] ) ) {
			$data = array();
		}

		$params['target_cols'][] = 'id';
		$params['target_cols'][] = 'ID';
		// データの絞りこみ
		$data = array_map(
			fn( $v ) => array_filter( $v, fn( $k ) => in_array( $k, $params['target_cols'], true ), ARRAY_FILTER_USE_KEY ),
			$data
		);

		// IP制限等で終了のケース
		if ( ! $data ) {
			$text = '認証情報が間違っている、またはデータが存在しないので終了します。';
			echo $text;
			exit;
		}

		// スプシヘッダーの異物混入を疑う
		if ( ! in_array( array_keys( $data[0] )[0], array( 'id', 'ID' ), true ) ) {
			$this->error[-1][] = 'スプレットシートのヘッダー行に異物混入しています。1行目はヘッダー行ですのでその上に行を追加等はしないで下さい。';
		}
		$sync_mode = "sync_spreadsheet_{$params['mode']}";
		if ( ! method_exists( $this, $sync_mode ) ) {
			$this->error[][] = "{$params['mode']}は存在しません。誰だ？直にURLを叩いているやつは！？";
			$this->check_error_spreadsheet();// エラー発生の場合はストップ
		}
		$this->$sync_mode( $data );

		printf(
			"追加: %d件\n更新: %d件\n-----------\n合計: %d件\n\n\n",
			count( array_filter( $this->log, fn( $v ) => 'insert' === $v['mode'] ) ),
			count( array_filter( $this->log, fn( $v ) => 'update' === $v['mode'] ) ),
			count( $this->log )
		);
		foreach ( $this->log as $id => $val ) {
			// 最初はヘッダーを追加
			if ( reset( $this->log ) === $val ) {
				echo "id\t" . implode( "\t", array_keys( $val ) ) . PHP_EOL;
			}
			echo "{$id}\t" . implode( "\t", $val ) . PHP_EOL;
		}
		exit;
	}

	/**
	 * OAuth2.0でスプレットシートデータを取得
	 * client_id,client_secret,refresh_tokenの3つの情報が入っているjsonを用意し、$this->spreadsheet_auth_pathに配置する
	 * refresh_token以外は https://console.cloud.google.com/apis/credentials?project=steamship-gcp で取得
	 *
	 * === refresh_tokenの取得方法 ===
	 * ① ここにアクセスしてoauthで認証 https://accounts.google.com/o/oauth2/v2/auth?scope=https://www.googleapis.com/auth/spreadsheets&response_type=code&access_type=offline&redirect_uri=http://localhost&client_id=クライアントID
	 * ② リダイレクトされたパラメータcodeを使って下記curlでリフレッシュトークン取得
	 * curl -X POST -d 'code=ここにcode&client_id=クライアントID&client_secret=クライアントシークレット&redirect_uri=http://localhost&grant_type=authorization_code' https://accounts.google.com/o/oauth2/token
	 *
	 * @param string $spreadsheetid スプレットシートのIDまたはurl
	 * @param string $range スプレットシートの範囲
	 * @param bool   $debug デバッグモードの情報を取得するかどうか
	 */
	public function get_spreadsheet_data( $spreadsheetid, $range = '', $debug = false ) {
		// $rangeが渡ってきていない時
		if ( empty( $range ) ) {
			preg_match( '/\#gid\=([0-9]*)/', $spreadsheetid, $m );
			if ( isset( $m[1] ) ) {
				$sheet_id = (int) $m[1];
			}
		}
		// $spreadsheetidの浄化
		preg_match( '/spreadsheets\/d\/(.*?)(\/|$)/', $spreadsheetid, $m );
		if ( ! empty( $m[1] ) ) {
			$spreadsheetid = $m[1];
		}

		$secret = wp_json_file_decode( $this->spreadsheet_auth_path );
		// token取得
		$url  = 'https://www.googleapis.com/oauth2/v4/token';
		$args = array(
			'body' => array(
				'refresh_token' => $secret->refresh_token,
				'client_id'     => $secret->client_id,
				'client_secret' => $secret->client_secret,
				'grant_type'    => 'refresh_token',
			),
		);
		$data = wp_remote_post( $url, $args );
		if ( 200 !== $data['response']['code'] ) {
			return false;
		}
		$body = json_decode( $data['body'] );
		if ( ! $body->access_token ) {
			return false;
		}
		if ( isset( $sheet_id ) ) {
			// url生成
			$url  = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetid}";
			$args = array(
				'headers' => array(
					'Authorization' => "Bearer {$body->access_token}",
				),
			);
			// $range生成
			$res   = wp_remote_get( $url, $args );
			$res   = json_decode( $res['body'], true );
			$title = $res['properties']['title'];
			$range = array_values( array_filter( $res['sheets'], fn( $v ) => $sheet_id === $v['properties']['sheetId'] ) );
			$range = $range[0]['properties']['title'];
		}
		// $rangeからヘッダーを分離
		$range_header = preg_replace( '/[A-Z]*([0-9]*)\:[A-Z]*[0-9]*$/', '1:1', $range );
		// url生成
		$url  = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetid}/values:batchGet?ranges={$range_header}&ranges={$range}";
		$args = array(
			'headers' => array(
				'Authorization' => "Bearer {$body->access_token}",
			),
		);
		// データ取得を試みる
		$data = wp_remote_get( $url, $args );
		if ( 200 !== $data['response']['code'] ) {
			return false;
		}
		$data   = json_decode( $data['body'], true )['valueRanges'];
		$header = $data[0]['values'][0];
		$data   = $data[1]['values'];
		if ( $header === $data[0] ) {
			unset( $data[0] );
			$data = array_values( $data );
		}
		// ヘッダーを利用して連想配列化
		$data = array_map(
			function( $v ) use ( $header ) {
				$v = array_slice( $v, 0, count( $header ) );
				$v = array_pad( $v, count( $header ), '' );
				return array_combine( $header, $v );
			},
			$data
		);
		if ( $debug ) {
			$data = array(
				'spreadsheetid' => $spreadsheetid,
				'range'         => $range,
				'header'        => $header,
				'data_count'    => count( $data ),
				'data'          => $data,
			);
			if ( $title ) {
				$data['title'] = $title;
			}
		}
		return $data;
	}

	/**
	 * スプレッドシートデータを取得するAPI
	 */
	public function get_spreadsheet_data_api() {
		$params = array(
			'spreadsheetid' => '',
			'range'         => '',
		);
		$params = wp_parse_args( $_GET, $params );
		$data   = $this->get_spreadsheet_data( $params['spreadsheetid'], $params['range'], true );
		$data   = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		header( 'Content-Type: application/json; charset=utf-8' );
		echo $data;
		exit;
	}

	/**
	 * マルチcURLを使ってwp_insert_postを高速化
	 *
	 * @param array $postarr 投稿の多次元配列
	 * @param int   $multi_insert_num 1スレットあたりのinsert数
	 * @return array $logs ログ
	 */
	public function multi_insert_posts( $postarr, $multi_insert_num = 50 ) {
		$mh       = curl_multi_init();
		$ch_array = array();
		$params   = array(
			'action'  => 'n2_insert_posts',
			'n2nonce' => wp_create_nonce( 'n2nonce' ),
		);
		foreach ( array_chunk( $postarr, $multi_insert_num ) as $index => $values ) {
			$params['posts'] = wp_json_encode( $values );

			$ch         = curl_init();
			$ch_array[] = $ch;
			// localでSSLでうまくアクセスできないので$schema必須
			$schema  = preg_match( '/localhost/', get_network()->domain ) ? 'http' : 'admin';
			$options = array(
				CURLOPT_URL            => admin_url( 'admin-ajax.php', $schema ),
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $params,
				CURLOPT_HEADER         => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_TIMEOUT        => 300,
				CURLOPT_USERPWD        => 'ss:ss',
				// ログインセッションを渡してajax
				CURLOPT_HTTPHEADER     => array(
					'Cookie: ' . urldecode( http_build_query( $_COOKIE, '', '; ' ) ),
				),
			);
			curl_setopt_array( $ch, $options );
			curl_multi_add_handle( $mh, $ch );
		}
		do {
			curl_multi_exec( $mh, $running );
			curl_multi_select( $mh );
		} while ( $running > 0 );
		$logs = array();
		foreach ( $ch_array as $ch ) {
			$res = json_decode( curl_multi_getcontent( $ch ), true );
			if ( $res ) {
				$logs = $logs + $res;
			}
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );
		return $logs;
	}

	/**
	 * multi_insert_posts用
	 */
	public function insert_posts() {
		if ( ! wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) || ! isset( $_POST['posts'] ) ) {
			exit;
		}
		$posts = json_decode( stripslashes( $_POST['posts'] ), true );
		if ( empty( $posts ) ) {
			exit;
		}
		$logs = array();
		foreach ( $posts as $post ) {
			if ( empty( $post['ID'] ) ) {
				$id = wp_insert_post( $post );
				// ログ追加
				if ( ! is_wp_error( $id ) ) {
					wp_save_post_revision( $id );// 初回リビジョンの登録
				}
			} else {
				$id = wp_update_post( $post );
			}
			// ログ追加
			if ( $id ) {
				$logs[ $id ] = array(
					'mode'  => empty( $post['ID'] ) ? 'insert' : 'update',
					'code'  => get_post_meta( $id, '返礼品コード', true ),
					'title' => get_the_title( $id ),
				);
			}
		}
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $logs );
		exit;
	}

	/**
	 * ログファイル生成
	 *
	 * @param array $arr ログ用の追加配列
	 */
	private function insert_log_file( $arr ) {
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
	 * usermetaからユーザーIDゲットだぜ
	 *
	 * @param string $field 名
	 * @param string $value 名
	 */
	public function get_userid_by_usermeta( $field, $value ) {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				$field,
				$value
			)
		);
		return $id;
	}

	/**
	 * アレルゲンをいい感じにN2に存在するものに変換する
	 *
	 * @param array $allergens アレルゲン配列
	 */
	private function change_n2_allergen( $allergens ) {
		global $n2;
		$allergens = array_map(
			function( $v ) use ( $n2 ) {
				$pattern = trim( preg_split( '/\(|（/', $v )[0] );// カッコの前だけにする
				$pattern = $n2->allergen_convert[ $pattern ] ?? $pattern;// カタカナとか漢字も互換する
				$str     = '';// 最終的にリターンする文字列
				foreach ( $n2->custom_field['事業者用']['アレルゲン']['option'] as $value ) {
					if ( preg_match( "/{$pattern}/", $value ) ) {
						$str = $value;
					}
				}
				return $str;
			},
			$allergens
		);
		$allergens = array_filter( $allergens );
		return $allergens;
	}

}
