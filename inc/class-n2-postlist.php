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
			'title'         => '返礼品名',
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

		$image_url = ! empty( $post_data['画像1'] ) ? $post_data['画像1'] : 'https://placehold.jp/50x50.png?text=NoImage';
		$money     = ! empty( $post_data['寄附金額'] ) ? $post_data['寄附金額'] : 0;
		$poster    = ! empty( $post_data['post_author'] ) ? get_userdata( $post->post_author )->display_name : '';
		$code      = ! empty( $post_data['返礼品コード'] ) ? $post_data['返礼品コード'] : '';

		switch ( $column_name ) {
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
				break;
		}
	}
}
