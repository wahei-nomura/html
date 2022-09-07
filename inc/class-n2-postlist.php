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
	 * add_posts_columns
	 *
	 * @param array $columns カラム名の配列
	 * @return array $columns 一覧に追加するカラム
	 */
	public function add_posts_columns( $columns ) {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'item-title'    => '返礼品名',
			'progress-bar'  => '進捗',
			'poster'        => '事業者名',
			'code'          => '返礼品コード',
			'money'         => '寄附金額',
			'thumbnail'     => '画像',
			'modified-last' => '最終更新日',
		);

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
		$post_data = N2_Functions::get_all_meta( $post );

		$title = get_the_title();

		// アカウントやステータスによってリンクを変える
		if ( current_user_can( 'ss_crew' ) ) {
			$post_url = get_edit_post_link();
		} else {
			if ( 'pending' === get_post_status() || 'publish' === get_post_status() ) {
				$post_url = home_url( '/' ) . "?p={$post->ID}";
			} else {
				$post_url = get_edit_post_link();
			}
		}

		$image        = ! empty( $post_data['商品画像'] ) ? "<img class='n2-postlist-imgicon' src='{$post_data['商品画像'][0]}'>" : 'なし';
		$money        = ! empty( $post_data['寄附金額'] ) ? $post_data['寄附金額'] : 0;
		$poster       = ! empty( $post_data['post_author'] ) ? get_userdata( $post->post_author )->display_name : '';
		$code         = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '';
		$ssmemo       = ! empty( $post_data['社内共有事項'] ) ? nl2br( $post_data['社内共有事項'] ) : '';
		$ssmemo_isset = $ssmemo ? 'n2-postlist-ssmemo' : '';

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
				echo "<div><a href='{$post_url}'>{$title}</a></div>";
				break;
			case 'poster':
				echo "<div>{$poster}</div>";
				break;
			case 'money':
				echo "<div>{$money}</div>";
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

		global $wpdb;
		// 事業者検索 ===============================================================
		$users_sql        = "SELECT * FROM $wpdb->users ;";
		$users_results    = $wpdb->get_results( $users_sql );
		$show_author      = '';
		$get_jigyousya_id = filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT );

		// datalist生成
		echo '<datalist id="jigyousya-list">';
		foreach ( $users_results as $row ) {
			$author_id   = (int) $row->ID;
			$author_name = $row->display_name;
			if ( $author_id === $get_jigyousya_id ) {
				$show_author = $author_name;
			}
			if ( '' !== $author_name ) {
				echo "<option value='{$author_name}' data-id='{$author_id}'>";
			}
		}
		echo '</datalist>';

		// 表示用と送信用にinput生成
		echo "<input type='text' name='' id='jigyousya-list-tag' list='jigyousya-list' value='{$show_author}' placeholder='事業者入力'>";
		echo "<input id='jigyousya-value' type='hidden' name='事業者' value='{$get_jigyousya_id}'>";
		// ここまで事業者 ===========================================================

		// 返礼品コード検索
		echo '<select name="返礼品コード[]" multiple>';
		echo '<option value="">返礼品コード</option>';
		if ( empty( $_GET['事業者'] ) || '' === $_GET['事業者'] ) {
			$posts_sql     = "SELECT * FROM $wpdb->posts ;";
			$posts_results = $wpdb->get_results( $posts_sql );
			foreach ( $posts_results as $row ) {
				$post_id  = $row->ID;
				$code     = get_post_meta( $post_id, '返礼品コード', 'true' );
				$get_code = filter_input( INPUT_GET, '返礼品コード', FILTER_SANITIZE_ENCODED );
				if ( '' !== $code ) {
					echo "<option value='{$post_id}'>{$code}</option>";
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
			$selected   = selected( $key, $get_status, false );
			echo "<option value='{$key}'{$selected}>{$value}</option>";
		}
		echo '</select>';

		// 定期便検索
		echo '<select name="定期便">';
		echo '<option value="">定期便検索</option>';
		for ( $i = 1; $i <= 12; $i++ ) {
			$text      = $i > 1 ? "{$i}回定期便のみ" : '定期便以外';
			$get_teiki = filter_input( INPUT_GET, '定期便', FILTER_VALIDATE_INT );
			$selected  = selected( $i, $get_teiki, false );
			echo "<option value='{$i}'{$selected}>{$text}</option>";
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

		if ( N2_Functions::admin_param_judge( $this->page ) ) {
			return $query;
		}

		global $wpdb;

		// 最終的に$query内に代入するWHERE句
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
		if ( ! empty( $_GET['s'] ) && '' !== $_GET['s'] ) {
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
		if ( ! empty( $_GET['事業者'] ) && '' !== $_GET['事業者'] ) {
			$where .= "AND {$wpdb->posts}.post_author = '%s'";
			array_push( $args, filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT ) );
		}

		// 返礼品コード絞り込み------------------------------------
		if ( ! empty( $_GET['返礼品コード'] ) && '' !== $_GET['返礼品コード'] ) {
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
		if ( ! empty( $_GET['ステータス'] ) && '' !== $_GET['ステータス'] ) {
			$where .= "AND {$wpdb->posts}.post_status = '%s'";
			array_push( $args, filter_input( INPUT_GET, 'ステータス' ) );
		}

		// 定期便絞り込み ---------------------------------------
		if ( ! empty( $_GET['定期便'] ) && '' !== $_GET['定期便'] ) {
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
		global $wpdb;
		$jigyousya = filter_input( INPUT_GET, '事業者', FILTER_VALIDATE_INT );

		if ( ! empty( $jigyousya ) && '' !== $jigyousya ) {
			$sql = "SELECT * FROM $wpdb->posts WHERE $wpdb->posts.post_author = $jigyousya ;";
		} else {
			$sql = "SELECT * FROM $wpdb->posts ;";
		}
		$result = $wpdb->get_results( $sql );
		$arr    = array();
		foreach ( $result as $row ) {
			if (
				! empty( get_post_meta( $row->ID, '返礼品コード', 'true' ) ) &&
				'' !== get_post_meta( $row->ID, '返礼品コード', 'true' )
				) {
				$arr[ $row->ID ] = get_post_meta( $row->ID, '返礼品コード', 'true' );
			}
		}

		echo json_encode( $arr );

		die();
	}

}
