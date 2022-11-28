<?php
/**
 * class-n2-front-comment.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Front_Comment' ) ) {
	new N2_Front_Comment();
	return;
}

/**
 * Front
 */
class N2_Front_Comment {
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
		add_action( 'comment_post', array( $this, 'comment_post' ) );
	}

	/**
	 * コメントに画像投稿機能
	 *
	 * @param int $comment_id comment_id
	 */
	public function comment_post( $comment_id ) {
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}

		$field_name = 'image';
		$post_id    = 0;
		$ids        = array();

		$attachments = $_FILES[ $field_name ];

		$names       = (array) $attachments['name'];
		$types       = (array) $attachments['type'];
		$tmp_names   = (array) $attachments['tmp_name'];
		$error_codes = (array) $attachments['error'];
		$sizes       = (array) $attachments['size'];

		foreach ( $names as $key => $value ) {
			$file = array(
				'name'     => $value,
				'type'     => $types[ $key ],
				'tmp_name' => $tmp_names[ $key ],
				'error'    => $error_codes[ $key ],
				'size'     => $sizes[ $key ],
			);

			$_FILES[ $field_name ] = $file;

			$attachment_id = media_handle_upload( $field_name, $post_id );

			if ( ! is_wp_error( $attachment_id ) ) {
				$ids[] = $attachment_id;
			}
		}
		$attachment_url = wp_get_attachment_url( $ids[0] );
		update_comment_meta( $comment_id, $field_name, $attachment_url );

	}
}
