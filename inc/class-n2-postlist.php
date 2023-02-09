<?php
/**
 * class-n2-postlist.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		add_action( 'admin_head-edit.php', array( $this, 'show_exportbtns' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_columns' ), 10, 2 );
		add_action( 'init', array( $this, 'change_postlabel' ) );
		add_filter( 'manage_posts_custom_column', array( $this, 'add_posts_columns_row' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_author_posts' ) );
		add_filter( 'gettext', array( $this, 'change_status' ) );
		add_filter( 'ngettext', array( $this, 'change_status' ) );
		add_filter( 'post_row_actions', array( $this, 'hide_editbtn' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_search_filter' ) );
		add_action( 'posts_request', array( $this, 'posts_request' ) );
		add_action( "wp_ajax_{$this->cls}", array( $this, 'ajax' ) );
	}

	/**
	 * show_exportbtns
	 * エクスポートボタン群表示
	 *
	 * @return void
	 */
	public function show_exportbtns() {
		if ( current_user_can( 'ss_crew' ) ) {
			get_template_part( 'template/export-btns' );
		}
	}

	/**
	 * カラムヘッダーをソート時にアイコン表示
	 *
	 * @param string $param_name orderbyのgetパラメータ
	 * @return string iconタグ
	 */
	private function judging_icons_order( $param_name ) {
		if ( (isset($_GET['orderby']) && $param_name !== $_GET['orderby'] )|| empty( $_GET['order'] ) ) {
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

		$sort_base_url = admin_url();
		$asc_or_desc   = empty( $_GET['order'] ) || 'desc' === $_GET['order'] ? 'asc' : 'desc';

		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'item-title'      => "<a href='{$sort_base_url}edit.php?orderby=返礼品名&order={$asc_or_desc}'>返礼品名{$this->judging_icons_order('返礼品名')}</a>",
			'progress-bar'    => '進捗',
			'poster'          => "<a href='{$sort_base_url}edit.php?orderby=事業者&order={$asc_or_desc}'>事業者名{$this->judging_icons_order('事業者')}</a>",
			'code'            => "<a href='{$sort_base_url}edit.php?orderby=返礼品コード&order={$asc_or_desc}'>返礼品コード{$this->judging_icons_order('返礼品コード')}</a>",
			'goods_price'     => "<a href='{$sort_base_url}edit.php?orderby=価格&order={$asc_or_desc}'>価格{$this->judging_icons_order('価格')}</a>",
			'donation_amount' => "<a href='{$sort_base_url}edit.php?orderby=寄附金額&order={$asc_or_desc}'>寄附金額{$this->judging_icons_order('寄附金額')}</a>",
			'teiki'           => '定期便',
			'thumbnail'       => '画像',
			'modified-last'   => "<a href='{$sort_base_url}edit.php?orderby=date&order={$asc_or_desc}'>最終更新日{$this->judging_icons_order('date')}</a>",
		);

		if ( 'jigyousya' !== wp_get_current_user()->roles[0] ) {
			$columns = array_merge(
				$columns,
				array(
					'ssmemo'    => 'SSメモ',
					'copy-post' => '複製',
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
		$post_data = N2_Functions::get_all_meta( $post );

		$title = get_the_title();

		$post_edit_url   = get_edit_post_link();
		$image           = isset( $post_data['商品画像'][0] ) ? "<img width='70' src='{$post_data['商品画像'][0]['url']}' />" : '-';
		$goods_price     = ! empty( $post_data['価格'] ) && 0 !== $post_data['価格'] ? number_format( $post_data['価格'] ) : '-';
		$donation_amount = ! empty( $post_data['寄附金額'] ) && 0 !== $post_data['寄附金額'] ? number_format( $post_data['寄附金額'] ) : '-';
		$teiki           = ! empty( $post_data['定期便'] ) && 1 !== (int) $post_data['定期便'] ? $post_data['定期便'] : '-';
		$poster          = ! empty( get_userdata( $post->post_author ) ) ? get_userdata( $post->post_author )->display_name : '';
		$code            = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '';
		$ssmemo          = ! empty( $post_data['社内共有事項'] ) ? nl2br( $post_data['社内共有事項'] ) : '';
		$ssmemo_isset    = $ssmemo ? 'n2-postlist-ssmemo' : '';

		$status     = '';
		$status_bar = 0;

		if ( 'draft' === get_post_status() || 'inherit' === get_post_status() ) {
			$status = '事業者下書き';
		}
		if ( 'pending' === get_post_status() ) {
			$status     = 'スチームシップ確認待ち';
			$status_bar = 30;
		}
		if ( 'publish' === get_post_status() ) {
			$status     = 'スチームシップ確認済み';
			$status_bar = 60;
		}

		switch ( $column_name ) {
			case 'item-title':
				echo "<div><a href='{$post_edit_url}'>{$title}</a>";
				break;
			case 'poster':
				echo "<div>{$poster}</div>";
				break;
			case 'goods_price':
				echo "<div>{$goods_price}</div>";
				break;
			case 'donation_amount':
				echo "<div>{$donation_amount}</div>";
				break;
			case 'teiki':
				echo "<div>{$teiki}</div>";
				break;
			case 'code':
				echo "<div>{$code}</div>";
				break;
			case 'thumbnail':
				echo $image;
				break;
			case 'modified-last':
				the_modified_date( 'Y年Md日' );
				break;
			case 'progress-bar':
				echo "<div class='n2-postlist-status'><progress max='100' value='{$status_bar}'></progress><span>{$status}</span></div>";
				break;
			case 'ssmemo':
				echo "<div class='{$ssmemo_isset}'><p>{$ssmemo}</p></div>";
				break;
			case 'copy-post':
				echo "<button type='button' class='neo-neng-copypost-btn'>複製</button></div>";
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
		if (
				is_admin() && ! current_user_can( 'ss_crew' ) && $query->is_main_query() &&
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
		$status = str_ireplace( '非公開', '事業者下書き', $status );
		$status = str_ireplace( '下書き', '事業者下書き', $status );
		$status = str_ireplace( 'レビュー待ち', 'スチームシップ確認待ち', $status );
		$status = str_ireplace( '公開済み', 'スチームシップ確認済み', $status );

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
	 * add_search_filter
	 * 絞り込み検索用のセレクトボックス表示
	 * return void
	 */
	public function add_search_filter() {

		if ( N2_Functions::admin_param_judge( $this->page ) ) {
			return;
		}

		// 事業者検索 ===============================================================
		$show_author      = '';
		$get_jigyousya_id = filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT );

		// datalist生成
		echo '<datalist id="jigyousya-list">';
		foreach ( get_users( 'role=jigyousya' ) as $user ) {
			$author_id   = (int) $user->ID;
			$author_name = $user->display_name;
			if ( $author_id === $get_jigyousya_id ) {
				$show_author = $author_name;
			}

			printf( '<option value="%s" data-id="%s">', $author_name, $author_id );
		}
		echo '</datalist>';

		// 表示用と送信用にinput生成
		echo "<input type='text' name='' id='jigyousya-list-tag' list='jigyousya-list' value='{$show_author}' placeholder='事業者入力'>";
		echo "<input id='jigyousya-value' type='hidden' name='事業者' value='{$get_jigyousya_id}'>";
		// ここまで事業者 ===========================================================

		// 返礼品コード検索
		echo '<select name="返礼品コード[]" multiple>';
		echo '<option value="">返礼品コード</option>';
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
		echo '</select>';

		// ステータス検索
		$status = array(
			'draft'   => '事業者下書き',
			'pending' => 'スチームシップ確認待ち',
			'publish' => 'スチームシップ確認済み',
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
		echo '<option value="">定期便検索</option>';
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

		// 事業者管理画面
		if ( is_admin() && 'jigyousya' === wp_get_current_user()->roles[0] ) {
			return $query;
		}

		if ( N2_Functions::admin_param_judge( $this->page ) ) {

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
		$where = "
		AND (
			(
				{$wpdb->posts}.post_type = 'post'
				AND (
					{$wpdb->posts}.post_status = 'publish'
					OR {$wpdb->posts}.post_status = 'future'
					OR {$wpdb->posts}.post_status = 'draft'
					OR {$wpdb->posts}.post_status = 'pending'
					OR {$wpdb->posts}.post_status = 'private'
					)
		";

		// $wpdbのprepareでプレイスフォルダーに代入するための配列
		$args = array();

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
			$where .= "AND {$wpdb->posts}.post_author = '%s'";
			array_push( $args, filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT ) );
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
		INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
		WHERE 1 = 1 {$where}
		GROUP BY {$wpdb->posts}.ID
		ORDER BY {$wpdb->posts}.post_date DESC
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
		$jigyousya = filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT );

		if ( empty( $jigyousya ) ) {
			echo wp_json_encode( array() );
			die();
		}

		$posts = get_posts( "author={$jigyousya}&post_status=any" );
		$arr   = array();
		foreach ( $posts as $post ) {
			if ( ! empty( get_post_meta( $post->ID, '返礼品コード', 'true' ) ) ) {
				$arr[ $post->ID ] = get_post_meta( $post->ID, '返礼品コード', 'true' );
			}
		}

		echo wp_json_encode( $arr );

		die();
	}

}
