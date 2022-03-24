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
	 * @return array
	 */
	public function add_posts_columns( $columns ){
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => '返礼品名',
			'money'     => '寄附金額',
			'thumbnail' => '画像',
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
		$image_url = ! empty( $post_data['画像1'] ) ? $post_data['画像1'] : '';
		$money     = ! empty( $post_data['寄附金額'] ) ? $post_data['寄附金額'] : 0;

		switch ( $column_name ) {
			case 'thumbnail':
				echo "<img src='{$image_url}' style='max-width:100%;max-height:100px'>";
				break;
			case 'money':
				echo "<div>{$money}</div>";
				break;
		}
	}
}
