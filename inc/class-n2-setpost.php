<?php
/**
 * class-n2-setpost.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Setpost' ) ) {
	new N2_Setpost();
	return;
}

/**
 * Setpost
 */
class N2_Setpost {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'remove_editor_support' ) );
		add_action( 'admin_menu', array( $this, 'add_customfields' ) );
		add_action( 'save_post', array( $this, 'save_customfields' ) );
	}

	/**
	 * remove_editor_support
	 */
	public function remove_editor_support() {
		$supports = array(
			'editor',
			'thumbnail',
			'excerpt',
			'trackbacks',
			'comments',
			'post-formats',
		);

		foreach ( $supports as $support ) {
			remove_post_type_support( 'post', $support );
		}

		$taxonomys = array(
			'category',
			'post_tag',
		);

		foreach ( $taxonomys as $taxonomy ) {
			unregister_taxonomy_for_object_type( $taxonomy, 'post' );
		}
	}

	/**
	 * add_customfields
	 */
	public function add_customfields() {
		add_meta_box(
			'item_setting',
			'返礼品詳細',
			array( $this, 'show_customfields' ),
			'post',
			'normal'
		);
	}

	/**
	 * show_customfields
	 */
	public function show_customfields() {
		global $post;
		$post_data = get_post_meta( $post->ID, 'post_data', true );

		$fields = array(
			'価格'      => array(
				'type'      => 'text',
				'value'     => isset( $post_data['価格'] ) ? $post_data['価格'] : '',
				'補足'        => '（税込）',
				'maxlength' => '',
			),

			'キャッチコピー' => array(
				'type'      => 'text',
				'value'     => isset( $post_data['キャッチコピー'] ) ? $post_data['キャッチコピー'] : '',
				'補足'        => '（30文字以内）',
				'maxlength' => '30',
			),

			'説明文'     => array(
				'type'      => 'textarea',
				'value'     => isset( $post_data['説明文'] ) ? $post_data['説明文'] : '',
				'補足'        => '（900文字以内）',
				'maxlength' => '900',
			),
		);

		$input_tags = array(
			'text'     => '<input type="text" id="%1$s" name="%1$s" value="%2$s" maxlength="%3$s">',
			'select'   => '',
			'textarea' => '<textarea style="display:block; width:100%; height:200px" id="%1$s" name="%1$s" maxlength="%3$s">%2$s</textarea>',
			'checkbox' => '',
		);

		// プラグインn2-developのn2_setpost_show_customfields呼び出し
		list($fields,$post_data) = apply_filters( 'n2_setpost_show_customfields', array( $fields, $post_data ) );

		// // プラグイン無効の場合$dataに$post_dataが含まれてしまう
		// var_dump( count( $data ) );
		// if ( count( $data ) === 1 ) {
		// 	$fields = $data;
		// }
		?>

		<form method="post" action="admin.php?page=item_setting">
			<div>
				<?php foreach ( $fields as $field => $detail ) : ?>
				<div>
					<p><label for="<?php echo $field; ?>"><?php echo $field; ?><?php echo $detail['補足']; ?></label></p>
					<div>
						<?php printf( $input_tags[ $detail['type'] ], $field, $detail['value'], $detail['maxlength'] ); ?>
					</div>
				</div>
				<hr>
				<?php endforeach; ?>
			</div>
		</form>
		<?php
	}

	/**
	 * save_customfields
	 *
	 * @param int $post_id first parameter
	 * @return void
	 */
	public function save_customfields( $post_id ) {

		if ( empty( $_POST ) ) {
			return;
		}
		$post_data = array(
			'価格'      => isset( $_POST['価格'] ) ? $this->h( $_POST['価格'] ) : '',
			'キャッチコピー' => isset( $_POST['キャッチコピー'] ) ? $this->h( $_POST['キャッチコピー'] ) : '',
			'説明文'     => isset( $_POST['説明文'] ) ? $this->h( $_POST['説明文'] ) : '',
			'追加テスト'   => isset( $_POST['追加テスト'] ) ? $this->h( $_POST['追加テスト'] ) : '',
			'追加テスト2'  => isset( $_POST['追加テスト2'] ) ? $this->h( $_POST['追加テスト2'] ) : '',
		);

		update_post_meta( $post_id, 'post_data', $post_data );
	}

	/**
	 * エスケープ処理の簡易関数
	 *
	 * @param string $str first parameter
	 * @return string
	 */
	public function h( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
	}

}
