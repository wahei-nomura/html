<?php
/**
 * class-n2-enqueuescript.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_enqueuescript' ) ) {
	new N2_Enqueuescript();
	return;
}

/**
 * enqueuescript
 */
class N2_Enqueuescript {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'login_enqueue_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_script' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
		add_action( 'admin_footer', array( $this, 'noscript' ) );
		add_action( 'wp_footer', array( $this, 'noscript' ) );
	}

	/**
	 * 管理画面
	 *
	 * @param string $hook_suffix ページ名
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		global $n2;
		wp_enqueue_style( 'n2-google-font-PTmono', 'https://fonts.googleapis.com/css2?family=PT+Mono&display=swap', array(), 'PTMono' );
		wp_enqueue_script( 'n2-admin', get_theme_file_uri( 'dist/js/admin.js' ), array( 'jquery', 'jquery-touch-punch' ), $n2->cash_buster, false );
		wp_enqueue_style( 'n2-admin', get_theme_file_uri( 'dist/css/admin.css' ), array( 'n2-google-font-PTmono' ), $n2->cash_buster );
		$n2->hook_suffix = $hook_suffix;
		wp_localize_script( 'n2-admin', 'n2', $n2 );
		wp_localize_script( 'n2-admin', 'tmp_path', $this->get_tmp_path() );
		$name = match ( $hook_suffix ) {
			'post.php', 'post-new.php' => 'admin-post-editor',
			'edit.php' => 'admin-post-list',
			'toplevel_page_n2_crew_setup_menu' => 'admin-setup',
			mb_strtolower( rawurlencode( '楽天sftp_page_n2_rakuten_sftp_client' ) ) => 'admin-menu-rakuten-cabinet',
			mb_strtolower( rawurlencode( '楽天sftp_page_n2_rakuten_auto_update' ) ) => 'admin-menu-rakuten-auto-update',
			'profile.php', 'user-edit.php' => 'admin-user-profile',
			'user-new.php' => 'admin-user-new',
			'users.php' => 'admin-users',
			'n2-sync_page_sync_ui_spreadsheet', 'toplevel_page_sync_ui_spreadsheet' => 'admin-n2-sync',
			default => false,
		};
		if ( $name ) {
			wp_enqueue_script( $name, get_theme_file_uri( "dist/js/{$name}.js" ), array( 'jquery' ), $n2->cash_buster, false );
			wp_enqueue_style( $name, get_theme_file_uri( "dist/css/{$name}.css" ), array(), $n2->cash_buster );
		}
	}

	/**
	 * フロントのjs,cssの読み込み
	 *
	 * @param string $hook_suffix ページ名
	 * @return void
	 */
	public function enqueue_front_script( $hook_suffix ) {
		global $n2;
		wp_enqueue_script( 'n2-front', get_theme_file_uri( 'dist/js/front.js' ), array( 'jquery' ), $n2->cash_buster, false );
		wp_enqueue_style( 'n2-front', get_theme_file_uri( 'dist/css/front.css' ), array(), $n2->cash_buster );
		$n2->hook_suffix = $hook_suffix;
		wp_localize_script( 'n2-front', 'n2', $n2 );
		wp_localize_script( 'n2-front', 'tmp_path', $this->get_tmp_path() );
	}

	/**
	 *
	 * ログイン画面
	 *
	 * @param string $hook_suffix suffix
	 * @return void
	 */
	public function login_enqueue_script( $hook_suffix ) {
		global $n2;
		wp_enqueue_script( 'n2-login', get_theme_file_uri( 'dist/js/admin-login.js' ), array( 'jquery' ), $n2->cash_buster, false );
		wp_enqueue_style( 'n2-login', get_theme_file_uri( 'dist/css/admin-login.css' ), array(), $n2->cash_buster );
		$n2->hook_suffix = $hook_suffix;
		// 表示用で自治体名だけ渡しておく
		wp_localize_script( 'n2-login', 'n2_my_town', $n2->town );
	}

	/**
	 * 管理画面のbodyにクラス付与
	 *
	 * @param string $classes bodyに追加するclass
	 * @return $classes
	 */
	public function add_admin_body_class( $classes ) {
		global $n2, $is_chrome, $is_safari;
		$classes .= isset( $_COOKIE['n2-darkmode'] ) ? ' n2-darkmode' : '';
		$classes .= $is_chrome ? ' is_chrome' : '';
		$classes .= $is_safari ? ' is_safari' : '';
		return $classes;
	}

	/**
	 * JSに渡す用のwindowのグローバル変数を配列としてreturn
	 *
	 * @return Array tmp_path
	 */
	private function get_tmp_path() {
		return array(
			'tmp_url'  => get_theme_file_uri(),
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'home_url' => home_url(),
		);
	}
	/**
	 * jsがオフの場合は警告する
	 *
	 * @return void
	 */
	public function noscript() {
		// 事業者ごとの電話番号、メール設定は一旦保留
		// $tel = NENG_OPTION['contact']['tel'];
		// $email = NENG_OPTION['contact']['email'];
		echo "<noscript>
		<pre style='padding: 16px; font-size: 18px;'>
		<span style='color:red;font-weight:bold;'>※ NEONENGシステムをご利用になるためにはブラウザのJavaScript機能をオンにする必要があります。</span><br>
		JavaScriptをオンにする方法はこちらの<a href='https://support.biglobe.ne.jp/settei/browser/chrome/chrome-010.html#:~:text=%E3%83%A1%E3%83%8B%E3%83%A5%E3%83%BC%E4%B8%8B%E3%81%AE%5B%E8%A8%AD%E5%AE%9A%5D%E3%82%92,%E3%82%92%5B%E3%82%AA%E3%83%B3%5D%E3%81%AB%E3%81%99%E3%82%8B%E3%80%82'>リンク</a>をご覧ください。<br><br>
		ご不明な場合は以下までご連絡くださいますようお願いいたします。<br><br>
		【株式会社スチームシップ】<br>
		TEL：050-8885-0484<br>
		メール：info@steamship.co.jp
		</pre>
		</noscript>";
	}
}
