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

		// リビジョンを整形（投稿タイプによってメソッド変更）
		$get_history_diff = match ( $post->post_type ) {
			'portal_item_data' => 'get_poratal_item_history_diff',
			default            => 'get_history_diff',
		};
		$diff = $this->{$get_history_diff}( wp_get_post_revisions( $post ) );
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
		return $diff;
	}

	/**
	 * ポータル履歴の差分表示用の配列作成（追加・削除・更新）
	 *
	 * @param array $revisions リビジョン配列
	 * @return array $diff
	 */
	protected function get_poratal_item_history_diff( $revisions ) {
		$diff      = array();
		$revisions = array_values( $revisions );// キーを連番に変更
		foreach ( $revisions as $key => $value ) {
			$diff[ $key ] = array(
				'ID'   => $value->ID,
				'date' => $value->post_date,
			);
			// before/after
			$before = (array) json_decode( $revisions[ $key + 1 ]->post_content ?? '' );
			$after  = (array) json_decode( $value->post_content );
			// １つ１つを文字列にしてarray_diffで差分をとれるようにする
			$jsons = array(
				'before' => array_map( 'json_encode', $before ),
				'after'  => array_map( 'json_encode', $after ),
			);
			// 差分を取る
			$before = array_values( array_map( 'json_decode', array_diff( $jsons['before'], $jsons['after'] ) ) );
			$after  = array_values( array_map( 'json_decode', array_diff( $jsons['after'], $jsons['before'] ) ) );
			// 返礼品コードで差分をとる
			$codes = array(
				'before' => array_map( fn( $v ) => $v->goods_g_num, $before ),
				'after'  => array_map( fn( $v ) => $v->goods_g_num, $after ),
			);
			$add    = array_values( array_diff( $codes['after'], $codes['before'] ) );// 追加
			$delete = array_values( array_diff( $codes['before'], $codes['after'] ) );// 削除
			$codes  = array_unique( array( ...$codes['before'], ...$codes['after'] ) );// 全コード
			$update = array_diff( $codes, $delete, $add );// 更新
			foreach ( compact( 'add', 'delete', 'update' ) as $k => $codes ) {
				foreach ( $codes as $code ) {
					// 返礼品コードでターゲットを絞る
					$tgt = array(
						'before' => array_values( array_filter( $before, fn( $v ) => $code === $v->goods_g_num ) )[0] ?? array(),
						'after'  => array_values( array_filter( $after, fn( $v ) => $code === $v->goods_g_num ) )[0] ?? array(),
					);
					// urlを突っ込む
					$diff[ $key ][ $k ][ $code ]['url'] = $tgt['after']->url ?? $tgt['before']->url;
					// parent_code
					if ( isset( $tgt['before']->parent_code ) ) {
						$diff[ $key ][ $k ][ $code ]['parent_code'] = $tgt['before']->parent_code;
						unset( $tgt['before']->parent_code );
					}
					if ( isset( $tgt['after']->parent_code ) ) {
						$diff[ $key ][ $k ][ $code ]['parent_code'] = $tgt['after']->parent_code;
						unset( $tgt['after']->parent_code );
					}
					// update時はbefore/after
					if ( 'update' === $k ) {
						$diff[ $key ][ $k ][ $code ]['before'] = array_diff( (array) $tgt['before'], (array) $tgt['after'] );
						$diff[ $key ][ $k ][ $code ]['after']  = array_diff( (array) $tgt['after'], (array) $tgt['before'] );
					}
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
