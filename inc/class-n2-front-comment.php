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
		// add_filter( 'preprocess_comment' ,array( $this, 'preprocess_comment' ) );
		// add_filter( 'comment_post' ,array( $this, 'preprocess_comment' ) );
	}

	public function preprocess_comment( $comment_data ) {
		var_dump($comment_data);
		var_dump($_FILES['image']);
		exit;
	}

	public function save_attachment( $comment_id, $comment_approved, $comment ) {
		$field_name = 'attachment';
		if ( ! isset( $_FILES[ $field_name ] ) ) {
			return;
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$post_id = 0;

		$ids = array();
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$attachments = $_FILES[ $field_name ];
		// phpcs:enable
		$names       = (array) $attachments['name'];
		$types       = (array) $attachments['type'];
		$tmp_names   = (array) $attachments['tmp_name'];
		$error_codes = (array) $attachments['error'];
		$sizes       = (array) $attachments['size'];

		foreach ( $names as $key => $value ) {
			// Emulate the upload of each file separately, because the `media_handle_upload`
			// function doesn't support uploading multiple files.
			$file                  = array(
				'name'     => $value,
				'type'     => $types[ $key ],
				'tmp_name' => $tmp_names[ $key ],
				'error'    => $error_codes[ $key ],
				'size'     => $sizes[ $key ],
			);
			$_FILES[ $field_name ] = $file;

			$this->enable_filter_upload();
			$attachment_id = media_handle_upload( $field_name, $post_id );
			$this->disable_filter_upload();

			if ( ! is_wp_error( $attachment_id ) ) {
				$ids[] = $attachment_id;
			}
		}

		if ( $ids ) {
			$this->assign_attachment( $comment_id, $ids );
		}

		$_FILES[ $field_name ] = $attachments;
	}
}
