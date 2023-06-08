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
		add_filter( 'bulk_actions-edit-post', array( $this, 'hide_bulk_btn' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_search_filter' ) );
		add_action( 'posts_request', array( $this, 'posts_request' ) );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
		add_action( "wp_ajax_{$this->cls}_deletepost", array( $this, 'delete_post' ) );
		add_action( "wp_ajax_{$this->cls}_recoverypost", array( $this, 'recovery_post' ) );
		add_action( "wp_ajax_{$this->cls}_bulk_update_status", array( $this, 'bulk_update_status' ) );
		add_action( "wp_ajax_{$this->cls}_ban_portal_list", array( $this, 'ban_portal_list' ) );
		add_filter( 'bulk_actions-edit-post', array( $this, 'bulk_manipulate' ) );
	}

	/**
	 * show_exportbtns
	 * エクスポートボタン群表示
	 *
	 * @return void
	 */
	public function save_post_ids_ui() {
		if ( current_user_can( 'ss_crew' ) ) {
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

		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'item-title'      => '返礼品名',
			'poster'          => '事業者名',
			'code'            => "<div class='text-center'><a href='{$sort_base_url}&orderby=返礼品コード&order={$asc_or_desc}'>返礼品<br>コード{$this->judging_icons_order('返礼品コード')}</a></div>",
			'goods_price'     => "<div class='text-center'><a href='{$sort_base_url}&orderby=価格&order={$asc_or_desc}'>価格{$this->judging_icons_order('価格')}</a></div>",
			'donation_amount' => "<a href='{$sort_base_url}&orderby=寄附金額&order={$asc_or_desc}'>寄附金額{$this->judging_icons_order('寄附金額')}</a>",
			'teiki'           => "<a href='{$sort_base_url}&orderby=定期便&order={$asc_or_desc}'>定期便{$this->judging_icons_order('定期便')}</a>",
			'thumbnail'       => '<div class="text-center">画像</div>',
			'modified-last'   => "<div class='text-center'><a href='{$sort_base_url}&orderby=date&order={$asc_or_desc}'>最終<br>更新日{$this->judging_icons_order('date')}</a></div>",
		);
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
		$code_no_class   = empty( $post_data['返礼品コード'] ) ? ' no-code' : '';
		$ssmemo          = ! empty( $post_data['社内共有事項'] ) ? nl2br( $post_data['社内共有事項'] ) : '';
		$ssmemo_isset    = $ssmemo ? 'n2-postlist-ssmemo' : '';
		$modified_last   = get_the_modified_date( 'Y/m/d' );

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
				echo "<div class='text-center'>{$donation_amount}</div>";
				break;
			case 'teiki':
				echo "<div class='text-center'>{$teiki}</div>";
				break;
			case 'code':
				echo "<div class='text-center{$code_no_class}'>{$code}</div>";
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
		// var_dump( $n2->current_user->roles );
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
		$status = str_ireplace( '非公開', '入力中', $status );
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
	 * hide_editbtn
	 * タイトル下の一括編集ボタンの中身を削除
	 *
	 * @param object $actions a
	 * @return object @actions
	 */
	public function hide_bulk_btn( $actions ) {
		global $n2;
		switch ( $n2->current_user->roles[0] ) {
			case 'municipal-office':
				unset( $actions['edit'] );
				break;
		}
		return $actions;
	}

	/**
	 * add_search_filter
	 * 絞り込み検索用のセレクトボックス表示
	 * return void
	 */
	public function add_search_filter() {
		global $n2,$post_type,$pagenow;
		if ( ! is_admin()
			|| $this->page !== $pagenow
			|| 'jigyousya' === $n2->current_user->roles[0]
			|| 'post' !== $post_type
		) {
			return;
		}

		// 事業者検索 ===============================================================

		echo '<div class="n2-jigyousya-selectbox"><select name="事業者[]" multiple size="1">';
		echo '<option value="" style="padding-top: 4px;">事業者複数選択</option>';
		foreach ( get_users( 'role=jigyousya' ) as $user ) {
				$author_id   = (int) $user->ID;
				$author_name = $user->display_name;

				printf( '<option value="%s">%s</option>', $author_id, $author_name );
		}

		echo '</select></div>';
		// ここまで事業者 ===========================================================

		// 返礼品コード検索
		echo '<div class="n2-code-selectbox"><span class="badge bg-secondary">←選択後<br>返礼品コード選択</span><select name="返礼品コード[]" multiple size="1">';
		echo '<option value="" style="padding-top: 4px;">返礼品コード</option>';
		if ( empty( $_GET['事業者'] ) ) {
			$get_code = filter_input( INPUT_GET, '返礼品コード', FILTER_SANITIZE_ENCODED );
			$posts    = get_posts( 'post_status=any' );
			foreach ( $posts as $post ) {
				$code = get_post_meta( $post->ID, '返礼品コード', 'true' );
				if ( '' !== $code ) {
					printf( '<option value="%s">%s</option>', $post->ID, $code );
				}
			}
		}
		echo '</select></div>';

		// ステータス検索
		$status = array(
			'draft'      => '入力中',
			'pending'    => 'スチームシップ確認待ち',
			'publish'    => 'ポータル登録準備中',
			'registered' => 'ポータル登録済',
		);
		echo '<select name="ステータス">';
		echo '<option value="">ステータス</option>';
		foreach ( $status as $key => $value ) {
			$get_status = filter_input( INPUT_GET, 'ステータス', FILTER_SANITIZE_ENCODED );
			printf( '<option value="%s" %s>%s</option>', $key, selected( $key, $get_status, false ), $value );
		}
		echo '</select>';

		// 定期便検索
		echo '<select name="定期便">';
		echo '<option value="">定期回数</option>';
		for ( $i = 1; $i <= 12; $i++ ) {
			$get_teiki = filter_input( INPUT_GET, '定期便', FILTER_VALIDATE_INT );
			printf( '<option value="%s" %s>%s</option>', $i, selected( $i, $get_teiki, false ), $i > 1 ? "{$i}回定期便のみ" : '定期便以外' );
		}
		echo '</select>';

		// クリアボタン
		echo '<button id="ss-search-clear" type="button">条件クリア</button>';
	}

	/**
	 * 一定条件下でSQLを全書き換え
	 *
	 * @param string $query sql
	 * @return string $query sql
	 */
	public function posts_request( $query ) {
		global $n2, $post_type, $pagenow;

		// 事業者管理画面
		if ( is_admin() && 'jigyousya' === wp_get_current_user()->roles[0] ) {
			return $query;
		}

		// 完全削除時
		if ( isset( $_GET['delete_all'] ) && 'ゴミ箱を空にする' === $_GET['delete_all'] ) {
			return $query;
		}
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] ) {
			return $query;
		}
		if ( isset( $_GET['action2'] ) && 'delete' === $_GET['action2'] ) {
			return $query;
		}

		if ( ! is_admin()
			|| $this->page !== $pagenow
			|| 'jigyousya' === $n2->current_user->roles[0]
			|| 'post' !== $post_type
		) {
			/**
			 * ここから超突貫のフロント用query
			 * 絶対後で綺麗にしてね！
			 * 2023-1-17 taiki
			 */
			if ( ! is_search() ) {
				return $query;
			}
			global $wpdb;
			global $template;
			$temp_name = basename( $template );
			// 最終的に$query内に代入するWHERE句
			$page_number  = 100;
			$current_pgae = get_query_var( 'paged' );  // ページ数取得
			$current_pgae = 0 === $current_pgae ? '1' : $current_pgae;
			$now_page     = ( $current_pgae - 1 ) * $page_number;
			$where        = "
		AND (
			(
				{$wpdb->posts}.post_type = 'post'
				AND (
					{$wpdb->posts}.post_status = 'publish'
					)
		";
			$order        = "{$wpdb->posts}.post_date DESC";

			// $wpdbのprepareでプレイスフォルダーに代入するための配列
			$args = array();
			// キーワード検索 ----------------------------------------
			if ( ! empty( $_GET['s'] ) && '' !== $_GET['s'] ) {
				// 全角空白は半角空白へ変換し、複数キーワードを配列に
				$s_arr = explode( ' ', mb_convert_kana( $_GET['s'], 's' ) );
				// キーワード前後の空白
				$s_arr = array_filter( $s_arr );

				// WHERE句連結
				$where .= 'AND(';
				foreach ( $s_arr as $key => $s ) {
					if ( 0 !== $key ) {
						$where .= 'AND';
					}

					$where .= "
						(
							{$wpdb->postmeta}.meta_value LIKE '%%%s%%'
							OR {$wpdb->posts}.post_title LIKE '%%%s%%'
						)
					";
					array_push( $args, $s ); // カスタムフィールド
					array_push( $args, $s ); // タイトル
				}
				$where .= ')';
			}
			// ここまでキーワード ------------------------------------
			// 出品禁止ポータル絞り込み ---------------------------------
			// if ( empty( $_GET['portal_rakuten'] ) ) { // 楽天除外
			// $where .= 'AND (';
			// $where .= "
			// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			// ";
			// array_push( $args, '楽天' );
			// $where .= ')';
			// }

			// if ( empty( $_GET['portal_choice'] ) ) { // チョイス除外
			// $where .= 'AND (';
			// $where .= "
			// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			// ";
			// array_push( $args, 'チョイス' );
			// $where .= ')';
			// }

			// if ( empty( $_GET['portal_furunavi'] ) ) { // チョイス除外
			// $where .= 'AND (';
			// $where .= "
			// {$wpdb->postmeta}.meta_key = '出品禁止ポータル'
			// AND {$wpdb->postmeta}.meta_value NOT LIKE '%%%s%%'
			// ";
			// array_push( $args, 'ふるなび' );
			// $where .= ')';
			// }
			// ここまで出品禁止ポータル ------------------------------------
			// 価格絞り込み ---------------------------------
			if ( ! empty( $_GET['min-price'] ) && '' !== $_GET['min-price'] ) { // 最低額
				$min_price = $_GET['min-price'];
				$where    .= 'AND (';
				$where    .= "
			{$wpdb->postmeta}.meta_key = '寄附金額'
			AND {$wpdb->postmeta}.meta_value >= '%s'
			";
				array_push( $args, $min_price );
				$where .= ')';
			}
			if ( ! empty( $_GET['max-price'] ) && '' !== $_GET['max-price'] ) { // 最高額
				$max_price = $_GET['max-price'];
				$where    .= 'AND (';
				$where    .= "
			{$wpdb->postmeta}.meta_key = '寄附金額'
			AND {$wpdb->postmeta}.meta_value <= '%s'
			";
				array_push( $args, $max_price );
				$where .= ')';
			}

			// 事業者絞り込み ----------------------------------------
			if ( ! empty( $_GET['jigyousya'] ) && '' !== $_GET['jigyousya'] ) {
				$where .= "AND {$wpdb->posts}.post_author = '%s'";
				array_push( $args, filter_input( INPUT_GET, 'jigyousya', FILTER_VALIDATE_INT ) );
			}
			// ここまで事業者 ----------------------------------------

			// 返礼品コード絞り込み------------------------------------
			if ( ! empty( $_GET['返礼品コード'] ) ) {
				$code_arr = $_GET['返礼品コード'];
				$where   .= 'AND (';
				foreach ( $code_arr as $key => $code ) {
					if ( 0 !== $key ) {
						$where .= ' OR '; // 複数返礼品コードをOR検索(前後の空白必須)
					}
					$where .= "{$wpdb->posts}.ID = '%s'";
					array_push( $args, $code );
				}
				$where .= ')';
			}
			// ここまで返礼品コード ----------------------------------------

			// 並び替え------------------------------------
			if ( ! empty( $_GET['sortcode'] ) ) {
				if ( 'sortbycode' === $_GET['sortcode'] ) { // 返礼品コードで並び替え
					$where .= 'AND (';
					$where .= "{$wpdb->postmeta}.meta_key = '返礼品コード'";
					$where .= ')';
					// order文入れ替え(コード順(昇順)に)
					$order = "{$wpdb->postmeta}.meta_value ASC";
				}
			}

			// ここまで並び替え ----------------------------------------

			// ここまで価格 ------------------------------------
			// WHER句末尾連結
			$where .= '))';

			// SQL（postsとpostmetaテーブルを結合）
			$sql = "
				SELECT SQL_CALC_FOUND_ROWS *
				FROM {$wpdb->posts}
				INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				WHERE 1 = 1 {$where}
				GROUP BY {$wpdb->posts}.ID
				ORDER BY {$order}
				LIMIT {$now_page}, 100
			";

			// 検索用GETパラメータがある場合のみ$queryを上書き
			$query = count( $args ) > 0 ? $wpdb->prepare( $sql, ...$args ) : $sql;
			return $query;
			/**
			 * ここまで汚物
			 */
		}

		global $wpdb;

		// 最終的に$query内に代入するWHERE句
		$page_number  = 300;
		$current_pgae = get_query_var( 'paged' );  // ページ数取得
		$current_pgae = 0 === $current_pgae ? '1' : $current_pgae;
		$now_page     = ( $current_pgae - 1 ) * $page_number;
		$where        = "
		AND (
			(
				{$wpdb->posts}.post_type = 'post'
				AND (
					{$wpdb->posts}.post_status = 'publish'
					OR {$wpdb->posts}.post_status = 'future'
					OR {$wpdb->posts}.post_status = 'draft'
					OR {$wpdb->posts}.post_status = 'pending'
					OR {$wpdb->posts}.post_status = 'private'
					OR {$wpdb->posts}.post_status = 'registered'
					)
		";

		$order_meta_key = '';
		$orderby        = "{$wpdb->posts}.post_date DESC";

		// $wpdbのprepareでプレイスフォルダーに代入するための配列
		$args = array();

		// ソート
		if ( isset( $_GET['orderby'] ) ) {
			// フィールドの値が数値の項目
			$int_metakey_perttern = array( '価格', '寄附金額', '定期便' );

			$order_pattern = ! isset( $_GET['order'] ) || 'desc' === $_GET['order'] ? 'DESC' : 'ASC';

			// 数値なのか
			if ( in_array( $_GET['orderby'], $int_metakey_perttern ) ) {
				$orderby = "CAST({$wpdb->postmeta}.meta_value AS UNSIGNED) {$order_pattern}";
			} else {
				$orderby = "{$wpdb->postmeta}.meta_value {$order_pattern}";
			}

			// 日付なのか
			if ( 'date' === $_GET['orderby'] ) {
				$orderby = "{$wpdb->posts}.post_date {$order_pattern}";
			} else {
				$order_meta_key = "AND {$wpdb->postmeta}.meta_key = '%s'";
				array_push( $args, filter_input( INPUT_GET, 'orderby' ) );
			}
		}

		// キーワード検索 ----------------------------------------
		if ( ! empty( $_GET['s'] ) ) {
			// 全角空白は半角空白へ変換し、複数キーワードを配列に
			$s_arr = explode( ' ', mb_convert_kana( $_GET['s'], 's' ) );
			// キーワード前後の空白
			$s_arr = array_filter( $s_arr );
			// OR検索対応
			$sql_pattern = ! empty( $_GET['or'] ) && '1' === $_GET['or'] ? 'OR' : 'AND';

			// WHERE句連結
			$where .= 'AND(';
			foreach ( $s_arr as $key => $s ) {
				if ( 0 !== $key ) {
					$where .= $sql_pattern;
				}

				$where .= "
						(
							{$wpdb->postmeta}.meta_value LIKE '%%%s%%'
							OR {$wpdb->posts}.post_title LIKE '%%%s%%'
						)
					";
				array_push( $args, $s ); // カスタムフィールド
				array_push( $args, $s ); // タイトル
			}
			$where .= ')';
		}
		// ここまでキーワード ------------------------------------

		// 事業者絞り込み ----------------------------------------
		if ( ! empty( $_GET['事業者'] ) ) {
			$jigyousya_arr = $_GET['事業者'];
			$where        .= 'AND (';
			foreach ( $jigyousya_arr as $key => $author_id ) {
				if ( 0 !== $key ) {
					$where .= ' OR '; // 複数事業者をOR検索(前後の空白必須)
				}
				$where .= "{$wpdb->posts}.post_author = '%s'";
				array_push( $args, $author_id );
			}
			$where .= ')';
		}

		// 返礼品コード絞り込み------------------------------------
		if ( ! empty( $_GET['返礼品コード'] ) ) {
			$code_arr = $_GET['返礼品コード'];
			$where   .= 'AND (';
			foreach ( $code_arr as $key => $code ) {
				if ( 0 !== $key ) {
					$where .= ' OR '; // 複数返礼品コードをOR検索(前後の空白必須)
				}
				$where .= "{$wpdb->posts}.ID = '%s'";
				array_push( $args, $code );
			}
			$where .= ')';
		}

		// ステータス絞り込み ------------------------------------
		if ( ! empty( $_GET['ステータス'] ) ) {
			$where .= "AND {$wpdb->posts}.post_status = '%s'";
			array_push( $args, filter_input( INPUT_GET, 'ステータス' ) );
		}

		// 定期便絞り込み ---------------------------------------
		if ( ! empty( $_GET['定期便'] ) ) {
			$where .= "
					AND {$wpdb->postmeta}.meta_key = '定期便'
					AND {$wpdb->postmeta}.meta_value = '%s'
				";
			array_push( $args, filter_input( INPUT_GET, '定期便', FILTER_VALIDATE_INT ) );
		}

		// WHER句末尾連結
		$where .= '))';

		// SQL（postsとpostmetaテーブルを結合）
		$sql = "
		SELECT SQL_CALC_FOUND_ROWS *
		FROM {$wpdb->posts}
		INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id {$order_meta_key}
		WHERE 1 = 1 {$where}
		GROUP BY {$wpdb->posts}.ID
		ORDER BY {$orderby}
		LIMIT {$now_page}, {$page_number}
		";

		// 検索用GETパラメータがある場合のみ$queryを上書き
		$query = count( $args ) > 0 ? $wpdb->prepare( $sql, ...$args ) : $query;

		return $query;
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
	 * ステータス一括変更
	 *
	 * @return void
	 */
	public function bulk_update_status() {
		$ids    = explode( ',', filter_input( INPUT_POST, 'ids', FILTER_SANITIZE_SPECIAL_CHARS ) );
		$status = filter_input( INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS );

		foreach ( $ids as $id ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => $status,
				)
			);
		}

		echo '更新完了';

		die();
	}

	/**
	 * 一括操作項目操作
	 */
	public function bulk_manipulate( $actions ) {
		unset( $actions['edit'] );
		return $actions;
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