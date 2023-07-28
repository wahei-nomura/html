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
	 * コンストラクタ
	 */
	public function __construct() {
		global $current_blog, $wp_filesystem;
		$n1_path           = preg_replace( '/f[0-9]{6}-/', '', $current_blog->path );
		$this->n1_ajax_url = "https://steamship.co.jp{$n1_path}wp-admin/admin-ajax.php";
		add_action( 'wp_ajax_n2_sync_users_from_n1', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_users_from_spreadsheet', array( $this, 'sync_users' ) );
		add_action( 'wp_ajax_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_nopriv_n2_sync_posts', array( $this, 'sync_posts' ) );
		add_action( 'wp_ajax_n2_multi_sync_posts', array( $this, 'multi_sync_posts' ) );
		add_action( 'wp_ajax_n2_sync_posts_from_spreadsheet', array( $this, 'sync_posts_from_spreadsheet' ) );
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
			<?php echo $this->$template(); ?>
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
		global $n2;
		if ( ! $n2->settings['寄附金額・送料']['送料'] ) {
			?>
			<br><p>Googleスプレットシート同期を使うには「<a href="?page=n2_settings_formula-delivery">N2設定 > 寄附金額・送料</a>」の値を適切に入力して下さい。</p>
			<?php
			return;
		}
		$default  = array(
			'id'         => '',
			'user_range' => '',
			'item_range' => '',
		);
		$settings = get_option( 'n2_sync_settings_spreadsheet', $default );
		?>
		<h2>Googleスプレットシートからの追加・上書き</h2>
		<ul style="padding: 1em; background: white; margin: 2em 0; border: 1px solid;">
			<li>※ ユーザーの更新はスプレットシートにある情報を追加、既に存在する場合は上書きします。</li>
			<li>※ 返礼品の更新モードは、返礼品コードで照合して返礼品があれば更新、無ければ追加します。</li>
			<li>※ 特定の項目だけ更新したい場合は、<b>項目を空欄にするのではなくカラムごと消して下さい。</b>空欄にすると空で更新されます。</li>
			<li>※ <b>シート雛形</b>は<a href="https://docs.google.com/spreadsheets/d/13HZn6w6S0XaXgAd_3RSkB46XUrRDkMQArVeE99pFD9Q/edit#gid=0" target="_blank">ココ</a>。複製して使用してください！</li>
			<li>※ シートの範囲については<a href="https://developers.google.com/sheets/api/guides/concepts?hl=ja#expandable-1" target="_blank">ココ</a>を参照。</li>
		</ul>
		<div id="n2sync-link-wrapper" style="padding: 1em 0;">
			<a href="<?php echo "{$n2->ajaxurl}?action=n2_sync_users_from_spreadsheet"; ?>" class="button" target="_blank" style="margin-right: 1em;">
				今すぐユーザーを更新
			</a>
			<a href="<?php echo "{$n2->ajaxurl}?action=n2_sync_posts_from_spreadsheet&update=1"; ?>" class="button" target="_blank" style="margin-right: 1em;">
				今すぐ返礼品を更新
			</a>
			<a href="<?php echo "{$n2->ajaxurl}?action=n2_sync_posts_from_spreadsheet"; ?>" class="button button-primary" target="_blank">
				＋　今すぐ返礼品を追加
			</a>
		</div>
		<form method="post" action="options.php">
			<?php settings_fields( 'n2_sync_settings_spreadsheet' ); ?>
			<table class="widefat striped" style="margin-bottom: 2em;">
				<tr>
					<th>スプレットシートのIDまたはURL</th>
					<td><input type="text" class="large-text" name="n2_sync_settings_spreadsheet[id]" value="<?php echo $settings['id']; ?>" placeholder="スプレッドシートのIDはたまURL"><a target="_blank"></a></td>
				</tr>
				<tr>
					<th>ユーザーシートの範囲</th>
					<td><input type="text" class="regular-text" name="n2_sync_settings_spreadsheet[user_range]" value="<?php echo $settings['user_range']; ?>" placeholder="user!A:ZZ"></td>
				</tr>
				<tr>
					<th>返礼品シートの範囲</th>
					<td><input type="text" class="regular-text" name="n2_sync_settings_spreadsheet[item_range]" value="<?php echo $settings['item_range']; ?>" placeholder="item!A:ZZ"></td>
				</tr>
			</table>
			<button class="button button-primary">設定を保存</button>
		</form>
		<?php
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

		// $params変更
		$params['action']  = 'n2_sync_posts';
		$params['n2nonce'] = wp_create_nonce( 'n2nonce' );

		// n2_sync_posts に Multi cURL
		$mh       = curl_multi_init();
		$ch_array = array();
		while ( $max_num_pages >= $params['paged'] ) {

			// ツイン起動しないためにSync中のフラグをチェックして終了
			$sleep = $_GET['sleep'] ?? $this->sleep;
			if ( $sleep > ( strtotime( 'now' ) - get_option( "n2syncing-{$params['paged']}", strtotime( '-1 hour' ) ) ) ) {
				$logs[] = '2重起動防止のため終了';
				$this->log( $logs );
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

		// params
		$params            = $_GET;
		$params['action']  = 'postsdata';
		$params['orderby'] = 'ID';

		if ( ! wp_verify_nonce( $params['n2nonce'] ?? '', 'n2nonce' ) ) {
			$msg    = '不正アクセス';
			$logs[] = $msg;
			$this->log( $logs );
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

			// 寄附金額をロックする
			$postarr['meta_input']['寄附金額固定'] = array( '固定する' );

			// 「取り扱い方法1〜2」を「取り扱い方法」に変換
			$handling                        = array_filter( $postarr['meta_input'], fn( $k ) => preg_match( '/取り扱い方法[0-9]/u', $k ), ARRAY_FILTER_USE_KEY );
			$postarr['meta_input']['取り扱い方法'] = array_filter( array_values( $handling ), fn( $v ) => $v );
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
			$postarr['meta_input']['発送サイズ'] = $postarr['meta_input']['発送サイズ'] ?? '';
			// 発送サイズの「レターパック」互換
			if ( 'レターパック' === $postarr['meta_input']['発送サイズ'] ) {
				$postarr['meta_input']['発送サイズ'] = 'レターパックプラス';
			}
			// 発送サイズの「その他」の統一
			if ( 'その他（ヤマト以外）' === $postarr['meta_input']['発送サイズ'] ) {
				$postarr['meta_input']['発送サイズ'] = 'その他';
			}
			// 役場確認
			if ( isset( $postarr['meta_input']['市役所確認'] ) ) {
				$postarr['meta_input']['役場確認'] = match ( $postarr['meta_input']['市役所確認'] ) {
					'不要', '要', '済' => '承諾',
					default => '未',
				};
				unset( $postarr['meta_input']['市役所確認'] );
			}
			// オリジナル商品変換
			if ( isset( $postarr['meta_input']['オリジナル商品'] ) ) {
				$postarr['meta_input']['オリジナル商品'] = match ( $postarr['meta_input']['オリジナル商品'] ) {
					'適' => array( 'オリジナル商品である' ),
					default => array(),
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
				// 更新されてない場合はスキップ
				if ( $p->post_modified >= $postarr['post_modified'] ) {
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

				// GETパラメータで受け取ったidとuser_rangeも保存
				update_option(
					'n2_sync_settings_spreadsheet',
					array(
						'id'         => $sheet_id,
						'user_range' => $user_range,
						'item_range' => $settings['item_range'],
					)
				);
				break;
		}
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
				// N1の場合
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
					$v['display_name'] = "{$v['last_name']} {$v['first_name']}";
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

			// 既存ユーザーは更新するのでIDを突っ込む
			$user = get_user_by( 'login', $userdata['user_login'] );
			if ( $user ) {
				$userdata['ID'] = $user->ID;
			}

			// パスワードの適切な加工
			add_filter( 'wp_pre_insert_user_data', array( $this, 'insert_user_pass' ), 10, 4 );
			$from_id = wp_insert_user( $userdata );
			if ( ! is_wp_error( $from_id ) ) {
				$from_ids[] = $from_id;
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

		$this->log( $logs );
		exit;
	}

	/**
	 * スプレットシートから返礼品のインポート
	 */
	public function sync_posts_from_spreadsheet() {
		global $n2;
		$before = microtime( true );

		// ログテキスト
		$logs   = array();
		$logs[] = __METHOD__;

		$default  = array(
			'spreadsheet' => array(
				'id'         => '',
				'user_range' => '',
				'item_range' => '',
			),
		);
		$settings = get_option( 'n2_sync_settings_spreadsheet', $default );

		// GETパラメータ優先、なければDB
		$input_id         = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS );
		$input_item_range = filter_input( INPUT_GET, 'item_range', FILTER_SANITIZE_SPECIAL_CHARS );

		$sheet_id   = $input_id && '' !== $input_id ? $input_id : $settings['id'];
		$item_range = $input_item_range && '' !== $input_item_range ? $input_item_range : $settings['item_range'];

		// データ取得
		$data = $this->get_spreadsheet_data( $sheet_id, $item_range );

		// IP制限等で終了のケース
		if ( ! $data ) {
			$text   = '認証情報が間違っている、またはデータが存在しないので終了します。';
			$logs[] = $text;
			$this->log( $logs );
			echo $text;
			exit;
		}
		// 区切り文字
		$sep = '/[,|、|\s|\/|\||｜|／]/u';
		// 投稿配列
		$postarr = array();
		foreach ( $data as $k => $d ) {
			$author_code = $d['事業者コード'] ?? preg_replace( '/[0-9]*/', '', $d['返礼品コード'] );
			// 投稿配列作成
			$postarr[ $k ]['post_title']  = $d['タイトル'] ?? '';
			$postarr[ $k ]['post_author'] = $this->get_userid_by_last_name( $author_code );
			unset( $d['タイトル'], $d['事業者コード'], $d['事業者名'] );
			// 寄附金額固定（入力あれば固定）
			if ( isset( $d['寄附金額固定'] ) ) {
				$d['寄附金額固定'] = array( $d['寄附金額固定'] ? '固定する' : '' );
			}
			// 寄附金額を半角数字のみに
			if ( isset( $d['寄附金額'] ) ) {
				$d['寄附金額'] = preg_replace( '/[^0-9]/', '', mb_convert_kana( $d['寄附金額'], 'n' ) );
			}
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
			// 商品タイプ（入力値をそのまま出力）
			if ( isset( $d['商品タイプ'] ) ) {
				$d['商品タイプ'] = array( $d['商品タイプ'] );
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
			// 取り扱い方法（区切り文字列でいい感じに配列化）
			if ( isset( $d['取り扱い方法'] ) ) {
				$d['取り扱い方法'] = array_values( array_filter( preg_split( $sep, $d['取り扱い方法'] ) ) );
			}
			// 発送サイズの想定入力ミスに対応
			if ( isset( $d['発送サイズ'] ) ) {
				$d['発送サイズ'] = match ( 1 ) {
					preg_match( '/[1１][0０][0-8０-８]/u', $d['発送サイズ'], $m ) => '0' . mb_convert_kana( $m[0], 'n' ),// 0が抜けた発送サイズコードの場合
					preg_match( '/[1-9１-９]{1,2}[0０]/u', $d['発送サイズ'], $m ) => '010' . ( mb_convert_kana( $m[0], 'n' ) - 40 ) / 20,// 発送サイズ名で入れたの場合（全角にも対応）
					preg_match( '/コンパクト/u', $d['発送サイズ'] ) => '0100',
					default => $d['発送サイズ'],
				};
			}
			if ( empty( $d['送料'] ) && ! empty( $d['発送サイズ'] ) && ! empty( $d['発送方法'] ) ) {
				$delivery_code = N2_Donation_Amount_API::create_delivery_code( $d['発送サイズ'], $d['発送方法'] );
				// 送料計算
				$d['送料'] = $n2->settings['寄附金額・送料']['送料'][ $delivery_code ] ?? '';
			}
			// $postarrにセット
			$postarr[ $k ]['meta_input'] = $d;
		}
		$this->multi_insert_posts( $postarr, 100, $_GET['update'] ?? false );
		// GETパラメータで受け取ったidとitem_rangeも保存
		update_option(
			'n2_sync_settings_spreadsheet',
			array(
				'id'         => $sheet_id,
				'user_range' => $settings['user_range'],
				'item_range' => $item_range,
			)
		);

		echo "N2-Insert-Posts-From-Spreadsheet「{$n2->town}の返礼品」スプレットシートからの追加完了！" . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$logs[] = '返礼品の追加完了 ' . number_format( microtime( true ) - $before, 2 ) . ' sec';
		$this->log( $logs );
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
	 * @param string $sheetid スプレットシートのID
	 * @param string $range スプレットシートの範囲
	 */
	public function get_spreadsheet_data( $sheetid, $range ) {

		// URLが渡ってきた場合はSheetIDを抜き出す
		if ( false !== strpos( $sheetid, 'spreadsheet' ) ) {
			preg_match( '/spreadsheets\/d\/(.*?)(\/|$)/', $sheetid, $m );
			$sheetid = $m[1];
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
		// $rangeからヘッダーを分離
		$range_header = preg_replace( '/[A-Z]*([0-9]*)\:[A-Z]*[0-9]*$/', '1:1', $range );
		// url生成
		$url  = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetid}/values:batchGet?ranges={$range_header}&ranges={$range}";
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
		return $data;
	}

	/**
	 * マルチcURLを使ってwp_insert_postを高速化
	 *
	 * @param array $postarr 投稿の多次元配列
	 * @param int   $multi_insert_num 1スレットあたりのinsert数
	 * @param bool  $update 上書きモードかどうか
	 */
	public function multi_insert_posts( $postarr, $multi_insert_num = 50, $update = false ) {
		$mh       = curl_multi_init();
		$ch_array = array();
		$params   = array(
			'action'  => 'n2_insert_posts',
			'n2nonce' => wp_create_nonce( 'n2nonce' ),
		);
		if ( $update ) {
			$params['update'] = 1;
		}
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
				CURLOPT_TIMEOUT        => 30,
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

		foreach ( $ch_array as $ch ) {
			curl_multi_remove_handle( $mh, $ch );
			curl_close( $ch );
		}
		curl_multi_close( $mh );
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
		foreach ( $posts as $post ) {
			// 「updateモード」かつ「登録済み」
			$p = get_posts( "meta_key=返礼品コード&meta_value={$post['meta_input']['返礼品コード']}&post_status=any" );
			if ( isset( $_POST['update'] ) && ! empty( $p ) ) {
				$post['ID']          = $p[0]->ID;
				$post['post_status'] = $post['post_status'] ?? $p[0]->post_status;
				$post['post_title']  = $post['post_title'] ?: $p[0]->post_title;
				$post['post_author'] = $post['post_author'] ?: $p[0]->post_author;
			}
			wp_insert_post( $post );
		}
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
