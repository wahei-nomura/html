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
	 * コンストラクタ
	 */
	public function __construct() {
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_columns' ), 10, 2 );
		add_filter( 'manage_posts_custom_column', array( $this, 'add_posts_columns_row' ), 10, 2 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_author_posts' ) );
		add_filter( 'gettext', array( $this, 'change_status' ) );
		add_filter( 'ngettext', array( $this, 'change_status' ) );
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
			'poster'        => '事業者名',
			'code'          => '返礼品コード',
			'money'         => '寄附金額',
			'thumbnail'     => '画像',
			'modified-last' => '最終更新日',
		);
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

		$post_data = get_post_meta( $post->ID, 'post_data', true );

		$title    = get_the_title();
		$post_url = get_edit_post_link();

		$image_url = ! empty( $post_data['画像1'] ) ? $post_data['画像1'] : 'https://placehold.jp/50x50.png?text=NoImage';
		$money     = ! empty( $post_data['寄附金額'] ) ? $post_data['寄附金額'] : 0;
		$poster    = ! empty( $post_data['post_author'] ) ? get_userdata( $post->post_author )->display_name : '';
		$code      = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '';

		$status = '';

		if ( 'draft' === get_post_status() || 'inherit' === get_post_status() ) {
			$status = '事業者下書き';
		}
		if ( 'pending' === get_post_status() ) {
			$status = 'Steamship確認待ち';
		}
		if ( 'publish' === get_post_status() ) {
			$status = 'Steamship確認済み';
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
				echo "<img src='{$image_url}' style='width:50px;height:50px'>";
				break;
			case 'modified-last':
				the_modified_date( 'Y年Md日' );
				echo '<br>' . $status;
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
				is_admin() && ! current_user_can( 'edit_others_posts' ) && $query->is_main_query() &&
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
		$status = str_ireplace( 'レビュー待ち', 'Steamship確認待ち', $status );
		$status = str_ireplace( '公開済み', 'Steamship確認済み', $status );

		return $status;
	}
}
