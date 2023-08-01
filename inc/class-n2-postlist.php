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
		add_action( 'admin_footer-edit.php', array( $this, 'save_post_ids_ui' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_columns' ), 10, 2 );
		add_action( 'init', array( $this, 'change_postlabel' ) );
		add_filter( 'manage_posts_custom_column', array( $this, 'add_posts_columns_row' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_author_posts' ) );
		add_filter( 'gettext', array( $this, 'change_status' ) );
		add_filter( 'ngettext', array( $this, 'change_status' ) );
		add_filter( 'post_row_actions', array( $this, 'hide_editbtn' ) );
		add_filter( 'bulk_actions-edit-post', '__return_false' );
		add_filter( 'disable_months_dropdown', '__return_true' );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
		add_action( "wp_ajax_{$this->cls}_deletepost", array( $this, 'delete_post' ) );
		add_action( "wp_ajax_{$this->cls}_recoverypost", array( $this, 'recovery_post' ) );
		add_action( "wp_ajax_{$this->cls}_ban_portal_list", array( $this, 'ban_portal_list' ) );
	}

	/**
	 * 投稿ID保持＆一括ツールUI
	 */
	public function save_post_ids_ui() {
		if ( current_user_can( 'ss-crew' ) || current_user_can( 'municipal-office' ) ) {
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
		if ( $n2->settings['N2']['役場確認'] ) {
			$columns['yakuba'] = '役場確認';
		}
		if ( 'municipal-office' !== wp_get_current_user()->roles[0] ) {
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
	 * change_postlabel
	 */
	public function change_postlabel() {
		global $wp_post_types;
		$name                       = '返礼品';
		$labels                     = &$wp_post_types['post']->labels;
		$labels->name               = $name;
		$labels->singular_name      = $name;
		$labels->add_new_item       = $name . 'の新規追加';
		$labels->edit_item          = $name . 'の編集';
		$labels->new_item           = '新規' . $name;
		$labels->view_item          = $name . 'を表示';
		$labels->search_items       = $name . 'を検索';
		$labels->not_found          = $name . 'が見つかりませんでした';
		$labels->not_found_in_trash = 'ゴミ箱に' . $name . 'は見つかりませんでした';
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
		$yakuba          = $post_data['役場確認'] ?? '';
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
	 * pre_get_author_posts
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
	 * hide_editbtn
	 * タイトル下の編集リンクなどを削除
	 *
	 * @param object $actions a
	 * @return object @actions
	 */
	public function hide_editbtn( $actions ) {
		unset( $actions['edit'] );
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['view'] );
		unset( $actions['trash'] );
		return $actions;
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

	/**
	 * 出品禁止ポータル一覧取得
	 */
	public function ban_portal_list() {
		global $n2;
		$export_portals = array_keys( $n2->export );
		$ban_list       = array_fill_keys( $export_portals, array() );
		$func           = __FUNCTION__;
		$ids            = explode( ',', filter_input( INPUT_POST, 'ids', FILTER_SANITIZE_SPECIAL_CHARS ) );
		foreach ( $ids as $id ) {
			$ban_portal = get_post_meta( $id, '出品禁止ポータル', 'true' );
			if ( ! $ban_portal ) {
				continue;
			}
			$item_code = get_post_meta( $id, '返礼品コード', 'true' ) ?: $id;
			// 空の要素を削除
			$ban_portals = array_values( array_filter( $ban_portal ) );
			foreach ( $ban_portals as $portal ) {
				$ban_list[ $portal ] = array( ...$ban_list[ $portal ], $item_code );
			}
		}
		echo wp_json_encode( $ban_list );
		die();
	}
}
