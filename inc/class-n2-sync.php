<?php
/**
 * class-n2-setusers.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Sync' ) ) {
	new N2_Sync();
	return;
}

/**
 * Setusers
 */
class N2_Sync {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2sync', array( $this, 'sync' ) );
	}

	/**
	 * NNS
	 *
	 * @return void
	 */
	public function sync() {
		global $current_blog;
		$town = $current_blog->path;
		$url  = "https://steamship.co.jp{$town}wp-json/wp/v2/posts";
		// params
		$params = array(
			'per_page' => 100,
			'page'     => 1,
		);
		// トータルページ数（仮）
		$pages  = 1;
		$before = microtime( true );
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		while ( $params['page'] <= $pages ) {
			// $http_response_header使いたいので鬼教官許して
			$data    = file_get_contents( "{$url}?" . http_build_query( $params ) );
			$headers = iconv_mime_decode_headers( implode( "\n", $http_response_header ) );
			// 合計情報
			$total = $headers['X-WP-Total'];
			$pages = $headers['X-WP-TotalPages'];
			$params['page']++;
			$arr = json_decode( $data, true );
			foreach ( $arr as $v ) {
				// 返礼品情報を生成
				$postarr = array(
					'status'            => $v['status'],
					'post_date'         => $v['date'],
					'post_date_gmt'     => $v['date_gmt'],
					'post_modified'     => $v['modified'],
					'post_modified_gmt' => $v['modified_gmt'],
					'type'              => $v['type'],
					'post_title'        => $v['title']['rendered'],
					'post_author'       => $v['author'],
					'meta_input'        => $v['acf'],
				);
				// 「返礼品コード」が既に登録済みか調査
				$args = array(
					'post_type'   => 'post',
					'meta_key'    => '返礼品コード',
					'meta_value'  => $v['acf']['返礼品コード'],
					'post_status' => 'any',
				);
				// 返礼品の投稿IDを取得
				$p = get_posts( $args )[0];
				// 登録済みの場合
				if ( $p->ID ) {
					// 更新されてない場合はスキップ
					if ( new DateTime( $p->post_modified ) === new DateTime( $postarr['post_modified'] ) ) {
						continue;
					}
					$postarr['ID'] = $p->ID;
				}
				add_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ), 99, 2 );
				wp_insert_post( $postarr );
				remove_filter( 'wp_insert_post_data', array( $this, 'alter_post_modification_time' ) );
			}
		}
		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
		$after = microtime( true );
		echo ( $after - $before ) . ' sec';
		exit;
	}
	/**
	 * 更新日時も登録可能にする
	 * 参考：https://wordpress.stackexchange.com/questions/224161/cant-edit-post-modified-in-wp-insert-post-bug
	 *
	 * @param array $data post_data
	 * @param array $postarr postarr
	 * @return $data
	 */
	public function alter_post_modification_time( $data, $postarr ) {
		if ( ! empty( $postarr['post_modified'] ) && ! empty( $postarr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $postarr['post_modified'];
			$data['post_modified_gmt'] = $postarr['post_modified_gmt'];
		}
		return $data;
	}
}
