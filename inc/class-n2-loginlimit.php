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
	}

	/**
	 * 管理者ログインのIP判定
	 *
	 * @param Object $user_login login
	 * @param Object $user user
	 * @return void
	 */
	public function judge_administrator_ip( $user_login, $user ) {
		if ( ! empty( $user->roles[0] ) && 'administrator' !== $user->roles[0] ) {
			return;
		}

		// N2_IPSはconfig/config.phpで定義
		if ( 'wp-multi.ss.localhost' !== get_network()->domain && ! in_array( $_SERVER['REMOTE_ADDR'], N2_IPS ) ) {
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
}
