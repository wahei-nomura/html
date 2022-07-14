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

		$ips = array(
			'219.111.49.195', // 波佐見
			'121.2.77.80', // 吉野ヶ里
			'202.241.189.211', // 糸島
			'219.111.24.202', // 有田
			'122.103.81.78', // 出島
			'183.177.128.173', // 土岐
			'217.178.116.13', // 大村
			'175.41.201.54', // SSVPN
			'127.0.0.1', // 自分
		);

		if ( 'ore.steamship.co.jp' !== $_SERVER['HTTP_HOST'] && ! in_array( $_SERVER['REMOTE_ADDR'], $ips ) ) {
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
		if ( ( ! empty( $user->roles[0] ) && 'ss-crew' !== $user->roles[0] ) || 'ore.steamship.co.jp' === $_SERVER['HTTP_HOST'] ) {
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
