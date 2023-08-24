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
		global $wpdb, $n2;
		$args   = $args ? wp_parse_args( $args ) : $_GET;
		$action = $args['action'] ?? false;
		$type   = $args['type'] ?? 'json';
		$order  = $args['order'] ?? 'asc';
		$query  = <<<SELECT_SQL
		SELECT * FROM wp_wsal_occurrences INNER JOIN wp_wsal_metadata ON wp_wsal_metadata.occurrence_id = wp_wsal_occurrences.id WHERE post_id = %d AND site_id = %d ORDER BY occurrence_id;
		SELECT_SQL;
		/**
		 * クエリの書き換えフック
		 *
		 * @since 2.1.0
		 *
		 * @param array $result 検索結果
		 * @param array $args 検索条件
		*/
		$query = apply_filters( 'n2_post_history_api_sql', $query, $args );
		// 履歴取得
		$result = $wpdb->get_results( $wpdb->prepare( $query, $args['post_id'], $n2->site_id ), ARRAY_A );
		$result = $this->data_shaping( $result );
		$result = 'desc' === $order ? array_reverse( $result ) : $result;

		// リビジョンを整形
		$revisions = array_values( wp_get_post_revisions( $args['post_id'] ) );
		$revisions = array_map(
			function( $k, $v ) use ( $revisions ) {
				$v->post_content = json_decode( $v->post_content, true );
				$before = $revisions[ $k + 1 ] ?? false;
				if ( $before ) {
					$v->post_content = array_diff_assoc( $v->post_content, json_decode( $before->post_content, true ) );
					$v->post_content = array_filter( $v->post_content, fn( $key ) => ! preg_match( '/^_/', $key ), ARRAY_FILTER_USE_KEY );
				}
				return $v;
			},
			array_keys( $revisions ),
			$revisions
		);
		$revisions = array_values( array_filter( $revisions, fn( $v ) => $v->post_content ) );
		// admin-ajax.phpアクセス時
		if ( $action ) {
			switch ( $type ) {
				case 'array':
					echo '<pre>';
					print_r( $result );
					break;
				case 'table':
					if ( empty( $result ) ) {
						echo '履歴がありません';
						exit;
					}
					$result = array_filter( $result, fn( $v ) => $v['MetaKey'] && $v['MetaValue'] );
					$header = array(
						'PostDate'     => '日付',
						'MetaKey'      => '項目',
						'MetaValue'    => '変更後',
						'MetaValueOld' => '変更前',
						'post_status'  => 'ステータス',
						'user'         => 'ユーザー',
					);
					// echo '<pre>';print_r($result);echo '</pre>';
					?>
					<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
					<table class="table table-striped small" style="max-width: 100%;">
						<thead>
							<tr>
								<?php foreach ( $header as $label ) : ?>
								<th nowrap><?php echo $label; ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<?php foreach ( $result as $v ) : ?>
							<tr>
								<?php foreach ( $header as $name => $label ) : ?>
									<td><?php echo nl2br( $v[ $name ] ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</table>
					<?php
					break;
				case 'view':
					get_template_part( 'template/admin-ajax/view-post-history', null, $revisions );
					break;
				default:
					header( 'Content-Type: application/json; charset=utf-8' );
					echo wp_json_encode( $result );
			}
			exit;
		}
		// N2_Post_History_API::get()呼び出し
		return $result;
	}

	/**
	 * データの整形
	 *
	 * @param array $data データ
	 * @return array
	 */
	protected function data_shaping( $data ) {
		$arr = array();
		foreach ( $data as $d ) {
			foreach ( $d as $name => $val ) {
				$arr[ $d['occurrence_id'] ][ $name ] = $val;
			}
			$arr[ $d['occurrence_id'] ][ str_replace( 'New', '', $d['name'] ) ] = $d['value'];
			$arr[ $d['occurrence_id'] ] = wp_parse_args(
				$arr[ $d['occurrence_id'] ],
				array(
					'PostDate'     => '',
					'PostTitle'    => '',
					'MetaKey'      => '',
					'MetaValue'    => '',
					'MetaValueOld' => '',
					'post_status'  => '',
					'user'         => get_user_by( 'id', $arr[ $d['occurrence_id'] ]['user_id'] )->display_name,
					'user_roles'   => '',
				)
			);
			unset( $arr[ $d['occurrence_id'] ]['name'], $arr[ $d['occurrence_id'] ]['value'] );
		}
		return $arr;
	}
}
