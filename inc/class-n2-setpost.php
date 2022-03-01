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

		$henreihin = array(
			'価格'      => isset( $post_data['価格'] ) ? $post_data['価格'] : '',
			'キャッチコピー' => isset( $post_data['キャッチコピー'] ) ? $post_data['キャッチコピー'] : '',
			'説明文'     => isset( $post_data['説明文'] ) ? $post_data['説明文'] : '',
		);

		?>

		<form method="post" action="admin.php?page=item_setting">
			<div>
				<div>
					<p><label for="価格">価格（税込）</label></p>
					<div>
						<input type="text" id="価格" name="価格" value="<?php echo $henreihin['価格']; ?>">
					</div>
				</div>
				<hr>
				<div>
					<p><label for="キャッチコピー">キャッチコピー(30文字以内)</label></p>
					<div>
						<input style="display:block; width:100%;" type="text" id="キャッチコピー" name="キャッチコピー" value="<?php echo $henreihin['キャッチコピー']; ?>" maxlength="30">
						<label>0文字</label>
					</div>
				</div>
				<hr>
				<div>
					<p><label for="説明文">説明文(900文字以内)</label></p>
					<div>
						<textarea style="display:block; width:100%; height:200px" id="説明文" name="説明文"><?php echo $henreihin['説明文']; ?></textarea>
						<label>0文字</label>
					</div>
				</div>
				
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
			'価格'      => $this->h( $_POST['価格'] ),
			'キャッチコピー' => $this->h( $_POST['キャッチコピー'] ),
			'説明文'     => $this->h( $_POST['説明文'] ),
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
