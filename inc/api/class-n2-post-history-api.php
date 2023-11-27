<?php
/**
 * class-n2-post-history-api.php
 * 履歴のためのAPI（jsでもPHPでも）
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
		add_action( 'wp_ajax_nopriv_n2_post_history_api', array( $this, 'get' ) );
		add_action( 'wp_ajax_n2_checkout_revision_api', array( $this, 'checkout_revision' ) );
	}

	/**
	 * 履歴差分取得のためのAPI
	 *
	 * @param array|string $args パラメータ
	 */
	public function get( $args ) {
		global $n2;
		$args   = wp_parse_args( $args ?? array(), array( 'post_status' => 'any' ) );
		$args   = wp_parse_args( $_GET, $args );
		$action = $args['action'] ?? false;
		$type   = $args['type'] ?? 'json';
		$post   = $args['post_id'] ?? get_posts( $args )[0];

		// リビジョンを整形
		$diff = $this->get_history_diff( wp_get_post_revisions( $post ) );
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
					$name = 'portal_item_data' === $post->post_type ? 'portal-item' : 'post';
					get_template_part( "template/admin-ajax/view-{$name}-history", null, $diff );
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
	 * 時を戻すためのAPI
	 */
	public function checkout_revision() {
		header( 'Content-Type: application/json; charset=utf-8' );
		if ( empty( $_GET['id'] ) ) {
			echo 'ERROR: idが不正です';
			exit;
		}
		$revision = get_post( $_GET['id'] );
		if ( ! $revision ) {
			echo 'ERROR: データがありません';
			exit;
		}
		$data = json_decode( $revision->post_content, true );
		if ( empty( $_GET['update'] ) ) {
			echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
			exit;
		}
		$meta   = array_filter( $data, fn( $k ) => ! preg_match( '/タイトル|ステータス|事業者名|事業者コード|_n2_required/u', $k ), ARRAY_FILTER_USE_KEY );
		$post   = array(
			'ID'           => $revision->post_parent,
			'post_status'  => $data['ステータス'],
			'post_title'   => $revision->post_title,
			'post_content' => $revision->post_content,
			'meta_input'   => $meta,
		);
		$author = $this->get_userid_by_usermeta( 'last_name', $data['事業者コード'] ?? '' );
		if ( $author ) {
			$post['post_author'] = $author;
		}
		wp_insert_post( $post );
		echo wp_json_encode( $post, JSON_UNESCAPED_UNICODE );
		exit;
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
				'ID'     => $value->ID,
				'author' => get_the_author_meta( 'display_name', $value->post_author ),
				'date'   => $value->post_date,
				'before' => json_decode( $revisions[ $key + 1 ]->post_content ?? '', true ),
				'after'  => json_decode( $value->post_content, true ),
			);
			// ポータル差分チェッカーの場合
			if ( 'portal_item_data' === get_post_type( $value->post_parent ) ) {
				$before = array_map( fn( $v ) => wp_json_encode( $v ), (array) $data['before'] );
				$after  = array_map( fn( $v ) => wp_json_encode( $v ), (array) $data['after'] );
				// 差分を取る
				$data['before'] = array_values( array_map( fn( $v ) => json_decode( $v, true ), array_diff( $before, $after ) ) );
				$data['after']  = array_values( array_map( fn( $v ) => json_decode( $v, true ), array_diff( $after, $before ) ) );
				$diff[] = $data;
			} else {
				// beforeとafterの差分チェック
				foreach ( $data['after'] as $k => $v ) {
					// アンダースコアで始まるフィールドと変更無いフィールドは破棄
					if ( preg_match( '/^_/', $k ) || ( $data['before'][ $k ] ?? '' ) === $data['after'][ $k ] ) {
						unset( $data['before'][ $k ], $data['after'][ $k ] );
					}
				}
				if ( ! empty( $data['after'] ) ) {
					$diff[] = $data;
				}
			}
		}
		return $diff;
	}

	/**
	 * usermetaからユーザーIDゲットだぜ
	 *
	 * @param string $field 名
	 * @param string $value 名
	 */
	public function get_userid_by_usermeta( $field, $value ) {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				$field,
				$value
			)
		);
		return $id;
	}
}
