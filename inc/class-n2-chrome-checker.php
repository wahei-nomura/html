<?php
/**
 * class-n2-chrome-checker.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Chrome_Checker' ) ) {
	new N2_Chrome_Checker();
	return;
}

/**
 * N2_Chrome_Checker
 */
class N2_Chrome_Checker {
	/**
	 * Chromeじゃないことを判定する
	 *
	 * @var bool
	 */
	private $is_not_chrome;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		global $is_chrome;
		$this->is_not_chrome = ! $is_chrome;
		add_filter( 'login_message', array( $this, 'chrome_checker_login' ) );
		add_action( 'admin_init', array( $this, 'chrome_checker_admin' ) );
	}

	/**
	 * ログイン画面アラート
	 */
	public function chrome_checker_login() {
		if ( $this->is_not_chrome ) {
			return '<div class="message" style="margin-top: 15px; padding: 20px; border-left-color: #FFF; color: #9c2c34; font-weight: bold;"><p>Google Chromeでの閲覧を推奨しています！</p></div>';
		}
	}

	/**
	 * 管理画面アラート
	 */
	public function chrome_checker_admin() {
		if ( $this->is_not_chrome ) {
			echo '<div class="notice notice-warning is-dismissible" style="border-left-color: #9c2c34; color: #9c2c34; font-weight: bold;"><p>Google Chromeでの閲覧を推奨しています！</p></div>';
		}
	}
}
