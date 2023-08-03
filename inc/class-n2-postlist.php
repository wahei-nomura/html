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
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_columns' ), 10, 2 );
		add_filter( 'manage_posts_custom_column', array( $this, 'add_posts_columns_row' ), 10, 2 );
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
	 * add_posts_columns
	 *
	 * @param array $columns カラム名の配列
	 * @return array $columns 一覧に追加するカラム
	 */
	public function add_posts_columns( $columns ) {
		global $n2;
		$get_param_array = array();
		foreach ( $_GET as $key => $value ) {
			if ( is_array( $value ) ) {
				$get_param_array[ $key ] = "{$key}[]=" . implode( "&{$key}[]=", $value );
				continue;
			}
			if ( $value ) {
				$get_param_array[ $key ] = "{$key}={$value}";
			}
		}

		$sort_base_url = admin_url() . 'edit.php?' . implode( '&', $get_param_array );
		$asc_or_desc   = empty( $_GET['order'] ) || 'asc' === $_GET['order'] ? 'desc' : 'asc';
		$include_fee   = $n2->settings['寄附金額・送料']['送料乗数'];
		$rr_header     = '(返礼率)';

		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'item-title'      => '返礼品名',
			'poster'          => '事業者名',
			'code'            => "<div class='text-center'><a href='{$sort_base_url}&orderby=返礼品コード&order={$asc_or_desc}'>返礼品<br>コード{$this->judging_icons_order('返礼品コード')}</a></div>",
			'goods_price'     => "<div class='text-center'><a href='{$sort_base_url}&orderby=価格&order={$asc_or_desc}'>価格{$this->judging_icons_order('価格')}</a></div>",
			'donation_amount' => "<a href='{$sort_base_url}&orderby=寄附金額&order={$asc_or_desc}'>寄附金額{$this->judging_icons_order('寄附金額')}</a><br>{$rr_header}",
			'teiki'           => "<a href='{$sort_base_url}&orderby=定期便&order={$asc_or_desc}'>定期便{$this->judging_icons_order('定期便')}</a>",
			'thumbnail'       => '<div class="text-center">画像</div>',
			'modified-last'   => "<div class='text-center'><a href='{$sort_base_url}&orderby=date&order={$asc_or_desc}'>最終<br>更新日{$this->judging_icons_order('date')}</a></div>",
		);
		if ( $n2->settings['N2']['自治体確認'] ) {
			$columns['yakuba'] = '自治体確認';
		}
		if ( 'local-government' !== wp_get_current_user()->roles[0] ) {
			$columns = array(
				...$columns,
				...array(
					'tools' => '<div class="text-center">ツール</div>',
				),
			);
		}
		if ( 'jigyousya' !== wp_get_current_user()->roles[0] ) {
			$columns = array_merge(
				$columns,
				array(
					'ssmemo' => 'SSメモ',
				)
			);
		}

		return $columns;
	}

	/**
	 * add_posts_columns_row
	 *
	 * @param string $column_name 追加されているカラム名
	 * @return void
	 */
	public function add_posts_columns_row( $column_name ) {
		global $post;
		global $n2;

		$post_data = N2_Functions::get_all_meta( $post );

		$title = get_the_title();

		if ( isset( $post_data['商品画像'][0] ) ) {
			$thumbnail_url = $post_data['商品画像'][0]['sizes']['thumbnail']['url'] ?? $post_data['商品画像'][0]['sizes']['thumbnail'];
		}

		$post_edit_url   = get_edit_post_link();
		$image           = isset( $post_data['商品画像'][0] ) ? "<img class='n2-postlist-imgicon' src='{$thumbnail_url}' />" : '-';
		$goods_price     = ! empty( $post_data['価格'] ) && 0 !== $post_data['価格'] ? number_format( $post_data['価格'] ) : '-';
		$donation_amount = ! empty( $post_data['寄附金額'] ) && 0 !== $post_data['寄附金額'] ? number_format( $post_data['寄附金額'] ) : '-';
		$teiki           = ! empty( $post_data['定期便'] ) && 1 !== (int) $post_data['定期便'] ? $post_data['定期便'] : '-';
		$poster          = ! empty( get_userdata( $post->post_author ) ) ? get_userdata( $post->post_author )->display_name : '-';
		$code            = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '未(id:' . $post->ID . ')';
		$yakuba          = $post_data['自治体確認'] ?? '';
		$code_no_class   = empty( $post_data['返礼品コード'] ) ? ' no-code' : '';
		$ssmemo          = ! empty( $post_data['社内共有事項'] ) ? nl2br( $post_data['社内共有事項'] ) : '';
		$ssmemo_isset    = $ssmemo ? 'n2-postlist-ssmemo' : '';
		$modified_last   = get_the_modified_date( 'Y/m/d' );
		$return_rate     = N2_Donation_Amount_API::calc_return_rate( $post_data ); // 返礼率計算
		$include_fee     = $n2->settings['寄附金額・送料']['送料乗数'];
		$rr_threshold    = N2_Donation_Amount_API::calc_return_rate( $post_data, true ); // 返礼率がしきい値(0.3 or 0.35)を超えてるかチェック
		$rr_caution      = false === $rr_threshold ?: '; color:red; font-weight:bold'; // 返礼率がしきい値を超えてたら装飾

		$status       = '';
		$status_bar   = 0;
		$status_color = '';
		if ( 'draft' === get_post_status() || 'inherit' === get_post_status() ) {
			$status       = '入力中';
			$status_bar   = 30;
			$status_color = 'secondary';

		}
		if ( 'pending' === get_post_status() ) {
			$status       = 'スチームシップ確認中';
			$status_bar   = 60;
			$status_color = 'danger';
		}
		if ( 'publish' === get_post_status() ) {
			$status       = 'ポータル登録準備中';
			$status_bar   = 80;
			$status_color = 'primary';
		}
		if ( 'registered' === get_post_status() ) {
			$status       = 'ポータル登録済';
			$status_bar   = 100;
			$status_color = 'success';
		}

		$tools_setting = array(
			array(
				'text'      => '複製',
				'add_class' => 'neo-neng-copypost-btn',
				'is_show'   => ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'],
			),
			array(
				'text'      => 'ゴミ箱へ移動',
				'add_class' => 'neo-neng-deletepost-btn',
				'is_show'   => ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) // ゴミ箱ページじゃない
								// かつ事業者以外または、事業者でステータスが下書き
								&& ( 'jigyousya' !== $n2->current_user->roles[0] || ( '入力中' === $status && 'jigyousya' === $n2->current_user->roles[0] ) ),
			),
			array(
				'text'      => '事業者変更',
				'add_class' => 'neo-neng-change-author-btn',
				'is_show'   => ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) && ( 'jigyousya' !== $n2->current_user->roles[0] ),
			),
			array(
				'text'      => '復元',
				'add_class' => 'neo-neng-recoverypost-btn',
				'is_show'   => isset( $_GET['post_status'] ) && 'trash' === $_GET['post_status'],
			),
		);

		switch ( $column_name ) {
			case 'item-title':
				echo "
						<div class='text-truncate' data-bs-toggle='tooltip' data-bs-placement='bottom' title='{$title}'><a href='{$post_edit_url}'>{$title}</a></div>
						<div class='progress mt-1' style='height: 10px; font-size:8px;'>
							<div class='progress-bar bg-{$status_color}' role='progressbar' style='width: {$status_bar}%;' aria-valuenow='{$status_bar}' aria-valuemin='0' aria-valuemax='100'>{$status}</div>
			  			</div>
						<button type='button' class='toggle-row'></button>
					";
				break;
			case 'poster':
				echo "<div>{$poster}</div>";
				break;
			case 'goods_price':
				echo "<div class='text-center'>{$goods_price}</div>";
				break;
			case 'donation_amount':
				echo "<div class='text-center'>{$donation_amount}<br><span style='font-size:.7rem{$rr_caution};'>({$return_rate})</span></div>";
				break;
			case 'teiki':
				echo "<div class='text-center'>{$teiki}</div>";
				break;
			case 'code':
				echo "<div class='text-center{$code_no_class}'>{$code}</div>";
				break;
			case 'yakuba':
				echo "<div class='text-center'>{$yakuba}</div>";
				break;
			case 'thumbnail':
				echo "<div class='text-center'>{$image}</div>";
				break;
			case 'modified-last':
				echo "<div class='text-center'>{$modified_last}</div>";
				break;
			case 'ssmemo':
				echo "<div class='{$ssmemo_isset}'><p>{$ssmemo}</p></div>";
				break;
			case 'tools':
				?>
					<div class="dropdown text-center n2-list-tooles">
						<span class="dashicons dashicons-admin-tools dropdown-toggle" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false"></span>
						<ul class="dropdown-menu border" aria-labelledby="dropdownMenuLink">
							<?php foreach ( $tools_setting as $setting ) : ?>
								<?php if ( $setting['is_show'] ) : ?>
									<li><button type="button" class="dropdown-item <?php echo $setting['add_class']; ?>"><?php echo $setting['text']; ?></button></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php
				break;
		}
	}



	/**
	 * change_status
	 * ステータス表示名を変更する
	 *
	 * @param string $status ステータス
	 * @return string $status ステータス
	 */
	public function change_status( $status ) {
		$status = str_ireplace( '非公開', '非公開', $status );
		$status = str_ireplace( '下書き', '入力中', $status );
		$status = str_ireplace( 'レビュー待ち', 'スチームシップ確認待ち', $status );
		$status = str_ireplace( '公開済み', 'ポータル登録準備中', $status );
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
