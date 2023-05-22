<?php
/**
 * class-n2-loginlimit.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Loginlimit' ) ) {
	new N2_Loginlimit();
	return;
}

/**
 * Dashboard
 */
class N2_Loginlimit {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_login', array( $this, 'judge_administrator_ip' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'judge_sscrew_ip' ), 10, 2 );
		add_action( 'after_switch_theme', array( $this, 'set_aio_wp_security_configs' ) );
	}

	/**
	 * 管理者ログインのIP判定
	 *
	 * @param Object $user_login login
	 * @param Object $user user
	 * @return void
	 */
	public function judge_administrator_ip( $user_login, $user ) {
		global $n2;
		if ( ! empty( $user->roles[0] ) && 'administrator' !== $user->roles[0] ) {
			return;
		}

		if ( 'wp-multi.ss.localhost' !== get_network()->domain && ! in_array( $_SERVER['REMOTE_ADDR'], $n2->ss_ip_address ) ) {
			wp_logout();
			echo $_SERVER['REMOTE_ADDR'];
			exit;
		}

	}

	/**
	 * ss-crewログインのIP判定
	 *
	 * @param Object $user_login login
	 * @param Object $user user
	 * @return void
	 */
	public function judge_sscrew_ip( $user_login, $user ) {
		if ( ( ! empty( $user->roles[0] ) && 'ss-crew' !== $user->roles[0] ) || 'wp-multi.ss.localhost' === get_network()->domain ) {
			return;
		}

		// APIで国内IPか判定
		$url = 'http://ip-api.com/json/' . $_SERVER['REMOTE_ADDR'];

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $ch );
		$result   = json_decode( $response, true );

		curl_close( $ch );

		if ( 'Japan' !== $result['country'] ) {
			wp_logout();
			echo $_SERVER['REMOTE_ADDR'];
			exit;
		}

	}

	/**
	 * All In One WP Securityの初期設定
	 */
	public function set_aio_wp_security_configs() {
		global $n2;
		$configs = get_option( 'aio_wp_security_configs' );
		if ( empty( $configs ) ) {
			return;
		}
		// ログインページ変更設定
		$configs['aiowps_enable_rename_login_page'] = 1;
		$configs['aiowps_login_page_slug']          = 'MSN-06S';
		// ホワイトIPリスト
		$configs['aiowps_lockdown_enable_whitelisting']  = 1;
		$configs['aiowps_lockdown_allowed_ip_addresses'] = implode( "\n", $n2->ss_ip_address );
		update_option( 'aio_wp_security_configs', $configs );
	}
}
