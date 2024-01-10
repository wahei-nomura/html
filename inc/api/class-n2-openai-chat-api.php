<?php
/**
 * OpenAI Chat API
 *
 * @package neoneng
 */

if ( class_exists( 'N2_OpenAI_Chat_API' ) ) {
	new N2_OpenAI_Chat_API();
	return;
}

/**
 * OpenAI Chat API
 */
class N2_OpenAI_Chat_API extends N2_OpenAI_Base_API {

	/**
	 * OpenAIにリクエスト送る関数：Chat機能
	 *
	 * @param string $usecase パラメータ
	 * @param string $user_message パラメータ
	 */
	public static function chat( $usecase = '', $user_message = '' ) {
		global $n2;

		// もし引数がなかったらURLパラメータから変数生成
		$usecase      = empty( $usecase ) ? ( static::$data['params']['usecase'] ?? '' ) : $usecase;
		$user_message = empty( $user_message ) ? ( static::$data['params']['user_message'] ?? '' ) : $user_message;

		// openai_template設定を取得
		$openai_template = $n2->openai_template[ $usecase ];
		$model           = $openai_template['model'] ?? 'gpt-3.5-turbo';
		$temperature     = $openai_template['temperature'] ?? 0.5;
		$max_tokens      = $openai_template['max_tokens'] ?? 1000;
		$system_message  = $openai_template['system_message'] ? array(
			'role'    => 'system',
			'content' => $openai_template['system_message'],
		) : array();
		$user_message    = $user_message ? array(
			'role'    => 'user',
			'content' => $user_message,
		) : array();
		$messages        = array_filter( array( $system_message, $user_message ) );

		// Chatへのメッセージ無しだったら処理を停止する
		if ( empty( $messages ) ) {
			echo 'メッセージが空です。';
			exit;
		}

		// リクエストエンドポイント
		$url = static::$settings['endpoint'] . '/chat/completions';

		// リクエストbody構築
		$request_body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		// リクエスト構築
		$request = array(
			'method'  => 'POST',
			'headers' => static::$data['header'],
			'body'    => wp_json_encode( $request_body ),
			'timeout' => 120,
		);

		// OpenAI APIへのリクエスト実行、最終的にメッセージだけを返すように：string
		$response = wp_remote_request( $url, $request );
		$response = json_decode( $response['body'] );
		$response = $response->choices[0]->message->content;

		return $response;
	}
}
