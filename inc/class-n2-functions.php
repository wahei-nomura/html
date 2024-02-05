<?php
/**
 * class-n2-functions.php
 * グローバルに使い回す関数を保管
 *
 * @package neoneng
 */

/**
 * Functions
 */
class N2_Functions {

	/**
	 * カスタムフィールド全取得
	 *
	 * @param Object $object 現在の投稿の詳細データ
	 * @return Array 全カスタムフィールド情報
	 */
	public static function get_all_meta( $object ) {

		$all = get_post_meta( $object->ID );
		foreach ( $all as $k => $v ) {
			if ( preg_match( '/^_/', $k ) ) {
				unset( $all[ $k ] );
				continue;
			}
			$all[ $k ] = get_post_meta( $object->ID, $k, true );
		}
		return $all;
	}

	/**
	 * send_slack_notification
	 *
	 * @param  character $send_message メッセージ
	 * @param  character $channel_name 通知するチャンネル名
	 * @param  string $bot_name botの名前
	 * @param  string $icon_url アイコンのURL
	 * @return array
	 */
	public static function send_slack_notification( $send_message, $channel_name = 'コーディング', $bot_name = 'SS BOT', $icon_url = 'https://ca.slack-edge.com/T6C6YQR62-UAD93DP6F-a394aaeabd28-72' ) {
		global $n2;

		$bot_url       = 'https://hooks.slack.com/services/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';
		$payload_items = array(
			'channel'  => $channel_name,
			'username' => $bot_name,
			'icon_url' => $icon_url,
			'text'     => $send_message,
		);

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_URL            => $bot_url,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => array( 'payload' => json_encode( $payload_items ) ),
		);
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		$result      = curl_exec( $ch );
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header      = substr( $result, 0, $header_size );
		$result      = substr( $result, $header_size );
		curl_close( $ch );
		return array(
			'Header' => $header,
			'Result' => $result,
		);
	}
}
