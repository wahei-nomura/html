<?php
/**
 * class-n2-donation-amount-api.php
 * 寄附金額の計算のためのAPI（jsでもPHPでも）
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Post_History_API' ) ) {
	new N2_Post_History_API();
	return;
}

/**
 * 寄附金額の計算のためのAPI
 */
class N2_Post_History_API {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_post_history_api', array( $this, 'get' ) );
	}

	/**
	 * 寄附金額の計算のためのAPI
	 *
	 * @param array|string $args パラメータ
	 */
	public function get( $args ) {
		global $n2;
		$args   = $args ? wp_parse_args( $args ) : $_GET;
		$action = $args['action'] ?? false;
		$type   = $args['type'] ?? 'json';

		// リビジョンを整形
		$diff = $this->get_history_diff( wp_get_post_revisions( $args['post_id'] ) );
		// admin-ajax.phpアクセス時
		if ( $action ) {
			switch ( $type ) {
				case 'array':
					echo '<pre>';
					print_r( $diff );
					break;
				case 'table':
					if ( empty( $diff ) ) {
						echo '履歴がありません';
						exit;
					}
					get_template_part( 'template/admin-ajax/view-post-history', null, $diff );
					break;
				default:
					header( 'Content-Type: application/json; charset=utf-8' );
					echo wp_json_encode( $diff );
			}
			exit;
		}
		// N2_Post_History_API::get()呼び出し
		return $result;
	}

	/**
	 * 履歴の差分表示用の配列作成
	 *
	 * @param array $revisions リビジョン配列
	 * @return array $diff
	 */
	protected function get_history_diff( $revisions ) {
		// 変更した人、変更日時、変更前、変更後
		$diff      = array();
		$revisions = array_values( $revisions );// キーを連番に変更
		foreach ( $revisions as $key => $value ) {
			$data = array(
				'author' => get_the_author_meta( 'display_name', $value->post_author ),
				'date'   => $value->post_date,
				'before' => json_decode( $revisions[ $key + 1 ]->post_content ?? '', true ),
				'after'  => json_decode( $value->post_content, true ),
			);
			// beforeとafterの差分チェック
			foreach ( $data['after'] as $k => $v ) {
				// アンダースコアで始まるフィールドと変更無いフィールドは破棄
				if ( preg_match( '/^_/', $k ) || ( $data['before'][ $k ] ?? '' ) == $data['after'][ $k ] ) {
					unset( $data['before'][ $k ], $data['after'][ $k ] );
				}
			}
			// diff 生成
			if ( ! empty( $data['after'] ) ) {
				$diff[] = $data;
			}
		}
		return $diff;
	}
}
