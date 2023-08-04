<?php
/**
 * class-n2-postlist.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Postlist' ) ) {
	new N2_Postlist();
	return;
}

/**
 * Postlist
 */
class N2_Postlist {
	/**
	 * クラス名
	 *
	 * @var string
	 */
	private $cls;
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->cls  = get_class( $this );
		$this->page = 'edit.php';
		add_filter( 'bulk_actions-edit-post', '__return_false' );// デフォルトのツール削除
		add_filter( 'disable_months_dropdown', '__return_true' );// 月のドロップダウンリスト削除
		add_filter( 'post_row_actions', '__return_empty_array' );// 投稿の削除とかのリンクを削除
		add_action( 'init', array( $this, 'change_postlabel' ) );// 投稿のラベル変更
		add_action( 'pre_get_posts', array( $this, 'pre_get_author_posts' ) );// 事業者権限だと自分の投稿のみに
		add_filter( 'wp_count_posts', array( $this, 'adjust_count_post' ), 10, 3 );// 事業者アカウントの投稿数の調整
		add_action( 'admin_footer-edit.php', array( $this, 'save_post_ids_ui' ) );// 投稿ID保持＆一括ツールUI
		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ), 10, 2 );// カラム調整
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'manage_posts_sortable_columns' ) );// ソート可能なカラムの調整
		add_filter( 'manage_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );
		add_filter( 'request', array( $this, 'posts_columns_sort_param' ) );
		add_filter( 'gettext', array( $this, 'change_status' ) );
		add_filter( 'ngettext', array( $this, 'change_status' ) );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
		add_action( "wp_ajax_{$this->cls}_deletepost", array( $this, 'delete_post' ) );
		add_action( "wp_ajax_{$this->cls}_recoverypost", array( $this, 'recovery_post' ) );
	}

	/**
	 * 投稿のラベル変更
	 */
	public function change_postlabel() {
		global $wp_post_types;
		$labels = &$wp_post_types['post']->labels;
		$labels = wp_json_encode( $labels, JSON_UNESCAPED_UNICODE );
		$labels = str_replace( '投稿', '返礼品', $labels );
		$labels = json_decode( $labels );
	}

	/**
	 * 事業者権限だと自分の投稿のみに
	 *
	 * @param object $query WP_Query
	 * @return void
	 */
	public function pre_get_author_posts( $query ) {
		global $n2;
		if (
				is_admin() &&
				in_array( 'jigyousya', $n2->current_user->roles ?? array(), true ) &&
				// ! current_user_can( 'ss_crew' ) &&
				$query->is_main_query() &&
				( ! isset( $_GET['author'] ) || intval( $_GET['author'] ) === get_current_user_id() )
		) {
			$query->set( 'author', get_current_user_id() );
			unset( $_GET['author'] );
		}
	}

	/**
	 * 事業者アカウントの投稿数の調整
	 *
	 * @param stdClass $counts An object containing the current post_type's post
	 *                         counts by status.
	 * @param string   $type   Post type.
	 * @param string   $perm   The permission to determine if the posts are 'readable'
	 *                         by the current user.
	 */
	public function adjust_count_post( $counts, $type, $perm ) {
		global $wpdb, $n2;
		if ( in_array( 'jigyousya', $n2->current_user->roles ?? array(), true ) ) {
			$query   = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d GROUP BY post_status";
			$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, get_current_user_id() ), ARRAY_A );
			$counts  = array_fill_keys( get_post_stati(), 0 );
			foreach ( $results as $row ) {
				$counts[ $row['post_status'] ] = $row['num_posts'];
			}
			$counts = (object) $counts;
			wp_cache_set( $cache_key, $counts, 'counts' );
		}
		return $counts;
	}

	/**
	 * 投稿ID保持＆一括ツールUI
	 */
	public function save_post_ids_ui() {
		if ( current_user_can( 'ss-crew' ) || current_user_can( 'local-government' ) ) {
			get_template_part( 'template/admin-postlist/save-post-ids' );
		}
	}

	/**
	 * カラムヘッダーをソート時にアイコン表示
	 *
	 * @param string $param_name orderbyのgetパラメータ
	 * @return string iconタグ
	 */
	private function judging_icons_order( $param_name ) {
		if ( ( isset( $_GET['orderby'] ) && $param_name !== $_GET['orderby'] ) || empty( $_GET['order'] ) ) {
			return;
		}

		return 'asc' === $_GET['order'] ? '<span class="dashicons dashicons-arrow-up"></span>' : '<span class="dashicons dashicons-arrow-down"></span>';
	}

	/**
	 * カラム調整
	 *
	 * @param array  $columns カラム名の配列
	 * @param string $post_type 投稿タイプ
	 * @return array $columns
	 */
	public function manage_posts_columns( $columns, $post_type ) {
		if ( 'post' === $post_type ) {
			unset( $columns['title'], $columns['author'], $columns['date'] );
			$columns['modified']        = '更新日';
			$columns['code']            = '返礼品コード';
			$columns['title']           = '返礼品名';
			$columns['author']          = '事業者名';
			$columns['subscription']    = '定期便';
			$columns['price']           = '価格';
			$columns['donation-amount'] = '寄附金額';
			$columns['rate']            = '返礼率';
			$columns['thumbnail']       = '画像';
			$columns['tools']           = 'ツール';
		}
		return $columns;
	}

	/**
	 * ソート可能なカラムの調整
	 *
	 * @param array $sortable_columns ソート可能なカラム
	 * @return array $sortable_columns
	 */
	public function manage_posts_sortable_columns( $sortable_columns ) {
		$sortable_columns['modified']        = 'modified';
		$sortable_columns['code']            = '返礼品コード';
		$sortable_columns['author']          = '事業者名';
		$sortable_columns['subscription']    = '定期便';
		$sortable_columns['price']           = '価格';
		$sortable_columns['donation-amount'] = '寄附金額';
		$sortable_columns['rate']            = '返礼率';
		return $sortable_columns;
	}

	/**
	 * add_posts_columns_row
	 *
	 * @param string $column_name 追加されているカラム名
	 * @param int    $post_id 投稿ID
	 */
	public function manage_posts_custom_column( $column_name, $post_id ) {
		$meta = json_decode( get_the_content(), true );
		// サムネイル
		$thumbnail = $meta['商品画像']
			? ( $meta['商品画像'][0]['sizes']['thumbnail']['url'] ?? $meta['商品画像'][0]['sizes']['thumbnail'] )
			: false;
		// html生成
		$html = match ( $column_name ) {
			'modified' => get_the_modified_date( 'y年 m/d' ) . '<br>' . get_the_modified_date( 'H:i:s' ),
			'code' => $meta['返礼品コード'],
			'subscription' => $meta['定期便'] > 1 ? $meta['定期便'] : '-',
			'price' => number_format( $meta['価格'] ?? 0 ) . '<small>円</small>',
			'donation-amount' => number_format( $meta['寄附金額'] ?? 0 ) . '<small>円</small>',
			// 'rate' => $meta['返礼率'],
			'thumbnail' => $thumbnail ? "<img src='{$thumbnail}' width='50'>" : '-',
			'tools' => 'template<br>呼び出し',
			default => '',
		};
		echo $html;
	}

	/**
	 * ソートを有効に
	 *
	 * @param array $query_vars The array of requested query variables.
	 * @return $query_vars
	 */
	public function posts_columns_sort_param( $query_vars ) {
		$sorts = array(
			'返礼品コード' => 'meta_value',
			'定期便'    => 'meta_value_num',
			'価格'     => 'meta_value_num',
			'寄附金額'   => 'meta_value_num',
			'返礼率'    => 'meta_value_num',
		);
		if ( array_key_exists( $query_vars['orderby'] ?? '', $sorts ) ) {
			$query_vars['meta_key'] = $query_vars['orderby'];
			$query_vars['orderby']  = $sorts[ $query_vars['orderby'] ];
		}
		return $query_vars;
	}

	/**
	 * change_status
	 * ステータス表示名を変更する
	 *
	 * @param string $status ステータス
	 * @return string $status ステータス
	 */
	public function change_status( $status ) {
		$re = array(
			'下書き'    => '入力中',
			'レビュー待ち' => 'スチームシップ確認待ち',
			'公開済み'   => 'ポータル登録準備中',
		);
		// 変換
		$status = str_replace( array_keys( $re ), $re, $status );
		return $status;
	}

	/**
	 * JSに返礼品コード一覧を渡す
	 *
	 * @return void
	 */
	public function ajax() {
		$jigyousya = filter_input( INPUT_GET, '事業者' );

		if ( empty( $jigyousya ) ) {
			echo wp_json_encode( array() );
			die();
		}

		$posts = get_posts( "author={$jigyousya}&post_status=any&posts_per_page=-1" );
		$arr   = array();
		foreach ( $posts as $post ) {
			if ( ! empty( get_post_meta( $post->ID, '返礼品コード', 'true' ) ) ) {

				array_push(
					$arr,
					array(
						'id'   => $post->ID,
						'code' => get_post_meta(
							$post->ID,
							'返礼品コード',
							'true'
						),
					),
				);

			}
		}

		usort(
			$arr,
			function( $a, $b ) {
				return strcmp( $a['code'], $b['code'] );
			}
		);

		echo wp_json_encode( $arr );

		die();
	}

	/**
	 * 返礼品を削除
	 *
	 * @return void
	 */
	public function delete_post() {
		$post_id      = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		$trash_result = wp_trash_post( $post_id );

		if ( $trash_result ) {
			echo 'ゴミ箱へ移動しました';
		} else {
			echo 'ゴミ箱への移動に失敗しました';
		}

		die();
	}

	/**
	 * 返礼品を復元
	 *
	 * @return void
	 */
	public function recovery_post() {
		$post_id        = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );
		$untrash_result = wp_untrash_post( $post_id );

		if ( $untrash_result ) {
			echo '復元';
		} else {
			echo '復元に失敗しました';
		}

		die();
	}
}
