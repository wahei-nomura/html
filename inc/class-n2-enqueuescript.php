<?php
/**
 * class-n2-enqueuescript.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_setpost_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_script' ) );
		add_action( 'admin_footer', array( $this, 'noscript' ) );
	}

	/**
	 * js,cssの読み込み
	 *
	 * @return void
	 */
	public function enqueue_setpost_script() {
		wp_enqueue_media();
		wp_enqueue_script( 'n2-script', get_theme_file_uri( 'dist/admin.js' ), array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_script( 'jquery-touch-punch', false, array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_style( 'n2-style', get_theme_file_uri( 'dist/admin.css' ), array(), N2_CASH_BUSTER );

		wp_localize_script( 'n2-script', 'tmp_path', $this->get_tmp_path() );
	}

	/**
	 * フロントのjs,cssの読み込み
	 *
	 * @return void
	 */
	public function enqueue_front_script() {
		wp_enqueue_script( 'n2-front-script', get_theme_file_uri( 'dist/front.js' ), array( 'jquery' ), N2_CASH_BUSTER, true );
		wp_enqueue_style( 'n2-front-style', get_theme_file_uri( 'dist/front.css' ), array(), N2_CASH_BUSTER );

		wp_localize_script( 'n2-front-script', 'tmp_path', $this->get_tmp_path() );
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
