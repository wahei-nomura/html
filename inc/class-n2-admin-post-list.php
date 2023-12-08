<?php
/**
 * class-n2-admin-post-list.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Admin_Post_List' ) && is_admin() ) {
	new N2_Admin_Post_List();
	return;
}

/**
 * 管理画面の投稿一覧
 */
class N2_Admin_Post_List {
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
		add_filter( 'bulk_actions-edit-post', '__return_empty_array' );// デフォルトのツール削除
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
			get_template_part( 'template/admin-post-list/save-post-ids' );
		}
		get_template_part( 'template/admin-post-list/tool' );
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
			$columns['status_government'] = '<span title="総務省申請">総</span>';
			$columns['tool']              = '';
			$columns['title']             = '返礼品名';
			$columns['code']              = 'コード';
			$columns['author']            = '事業者名';
			$columns['thumbnail']         = '画像';
			$columns['price']             = '価格';
			$columns['donation-amount']   = '寄附金額';
			$columns['rate']              = '返礼率';
			$columns['subscription']      = '定期便';
			$columns['modified']          = '更新日';
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
		$sortable_columns['author']          = 'author';
		$sortable_columns['subscription']    = '定期便';
		$sortable_columns['price']           = '価格';
		$sortable_columns['donation-amount'] = '寄附金額';
		// $sortable_columns['rate']            = '返礼率';
		return $sortable_columns;
	}

	/**
	 * add_posts_columns_row
	 *
	 * @param string $column_name 追加されているカラム名
	 * @param int    $post_id 投稿ID
	 */
	public function manage_posts_custom_column( $column_name, $post_id ) {
		// デフォルト
		$defaults = array(
			'id'    => $post_id,
			'総務省申請' => '未',
		);
		// メタデータ
		$meta = json_decode( get_the_content(), true );
		$meta = wp_parse_args( $meta, $defaults );

		// 出力
		echo match ( $column_name ) {

			// 総務省申請ステータス
			'status_government' => ( function() use ( $meta ) {
				$title = empty( $meta['総務省申請不要理由'] ) ? $meta['総務省申請'] : "{$meta['総務省申請']}: {$meta['総務省申請不要理由']}";
				$s     = '未' === $meta['総務省申請'] ? '未 OR  -総務省申請' : $meta['総務省申請'];
				$icon  = "<a href='?s=総務省申請:{$s}' class='dashicons %s'  title='{$title}'></a>";
				return match ( $meta['総務省申請'] ) {
					'未' => sprintf( $icon, 'dashicons-minus' ),
					'不要' => sprintf( $icon, 'dashicons-yes' ),
					'申請前' => sprintf( $icon, 'dashicons-arrow-right-alt' ),
					'申請中' => sprintf( $icon, 'dashicons-hourglass' ),
					'差戻' =>  sprintf( $icon, 'dashicons-undo' ),
					'却下' => sprintf( $icon, 'dashicons-dismiss' ),
					'承認済' => sprintf( $icon, 'dashicons-yes-alt' ),
					default => '',
				};
			} )(),

			// ツール
			'tool' => ( function() use ( $meta ) {
				$class = empty( $meta['_n2_required'] ?? 1 ) ? 'n2-ready' : '';
				return "<div class='n2-admin-post-list-tool-open {$class}' data-id='{$meta['id']}'></div>";
			} )(),

			// 返礼品コード
			'code' => $meta['返礼品コード'] ?? "<div onclick='navigator.clipboard.writeText({$post_id});' title='{$post_id}'>-</div>",

			// 画像
			'thumbnail' => ( function() use ( $meta ) {
				$thumbnail = ! empty( $meta['商品画像'] )
				? ( $meta['商品画像'][0]['sizes']['thumbnail']['url'] ?? $meta['商品画像'][0]['sizes']['thumbnail'] )
				: false;
				return $thumbnail ? "<img src='{$thumbnail}' class='n2-admin-post-list-tool-open'>" : '<div class="empty-thumbnail">-</div>';
			} )(),

			// 価格
			'price' => number_format( (int) ( $meta['価格'] ?? 0 ) ) . '<small>円</small>',

			// 寄附金額
			'donation-amount' => number_format( (int) ( $meta['寄附金額'] ?? 0 ) ) . '<small>円</small>',

			// 返礼率
			'rate' => ( function() use ( $meta ) {
				$rate = N2_Donation_Amount_API::calc_return_rate( $meta );
				return sprintf( $rate > 30 ? '<span style="color:red;">%s<small>%s</small></span>' : '%s<small>%s</small>', $rate, '%' );
			} )(),

			// 定期便
			'subscription' => ( $meta['定期便'] ?? 1 ) > 1 ? "{$meta['定期便']}<small>回</small>" : '-',

			// 更新日
			'modified' => get_the_modified_date( 'y年 m/d' ) . '<br>' . get_the_modified_date( 'H:i:s' ),

			default => '',
		};
	}

	/**
	 * ソートを有効に
	 *
	 * @param array $query_vars The array of requested query variables.
	 * @return $query_vars
	 */
	public function posts_columns_sort_param( $query_vars ) {
		$orderby = $query_vars['orderby'] ?? '';
		$sorts   = array(
			'返礼品コード' => 'meta_value',
			'定期便'    => 'meta_value_num',
			'価格'     => 'meta_value_num',
			'寄附金額'   => 'meta_value_num',
		);
		if ( array_key_exists( $orderby, $sorts ) ) {
			// meta_queryでフィールドが存在しなくても対象とする
			$query_vars['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => $orderby,
					'compare' => 'EXISTS', // フィールドが存在する
				),
				array(
					'key'     => $orderby,
					'compare' => 'NOT EXISTS', // フィールドが存在しない
				),
			);
			// orderby
			$query_vars['orderby'] = $sorts[ $orderby ];
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
}
