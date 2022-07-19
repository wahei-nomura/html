<?php
/**
 * class-n2-jigyousyaparam.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Jigyousyaparam' ) ) {
	new N2_Jigyousyaparam();
	return;
}

/**
 * Foodparam
 */
class N2_Jigyousyaparam {
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
		$this->cls = get_class( $this );
		add_action( 'admin_footer', array( $this, 'show_login_modal' ) );
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
	}

	private function params(){
		$params = array(
			"food"=>"食品取扱い",
			"yakimono"=>"やきもの取り扱い",
		);

		return $params;
	}

	/**
	 * 事業者ユーザーが食品取扱データを持っていない場合モーダル表示
	 *
	 * @return void
	 */
	public function show_login_modal() {
		$user = wp_get_current_user();
		if ( 'jigyousya' !== $user->roles[0] ) {
			return;
		}

		$params = $this->params();

		$modal = false;
		foreach( $params as $key => $meta ){
			$modal = empty( get_user_meta( $user->ID, $meta, true ) ) || '' === get_user_meta( $user->ID, $meta, true ) ? true : $modal;
		}

		// 食品取扱登録用モーダルテンプレートをinclude
		if ( $modal ) {
			include get_theme_file_path( 'template/jigyousya-login-modal.php' );
		}

	}

	/**
	 * ajaxでDBに登録する
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		$params = $this->params();
		foreach( $params as $key => $meta ){
			if ( ! empty( $_POST[$key] ) && '' !== $_POST[$key] ) {
				update_user_meta( wp_get_current_user()->ID, $meta, filter_input( INPUT_POST, $key ) );
			}
		}
		echo '食品取扱い有無更新完了';
		die();
	}

	/**
	 * add_setup_menu
	 * クルー用セットアップ管理ページを追加
	 */
	public function add_setup_menu() {
		add_menu_page( '返礼品の設定', '返礼品の設定', 'jigyousya', 'n2_jigyousya_menu', array( $this, 'add_jigyousya_setup_menu_page' ), 'dashicons-list-view' );
	}

	/**
	 * 事業者食品用メニュー描画
	 *
	 * @return void
	 */
	public function add_jigyousya_setup_menu_page() {
		$value = get_user_meta( wp_get_current_user()->ID, '食品取扱い', true ) ? get_user_meta( wp_get_current_user()->ID, '食品取扱い', true ) : '';
		?>
			<form>
				<h2>事業者様の食品取扱いの有無を登録</h2>
				<div>
					<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
					<label for="foodyes"><input type="radio" name="food" id="foodyes" value="有"<?php checked( $value, '有' ); ?>>食品を取り扱っている</label>
					<label for="foodno"><input type="radio" name="food" id="foodno" value="無"<?php checked( $value, '無' ); ?>>食品を取り扱っていない</label>
				</div>
				<p>※返礼品登録時のアレルギー選択項目の表示に使用します。</p>
				<div>
					<button type="submit" class="button button-primary sissubmit">更新する</button>
				</div>
			</form>
		<?php
	}
}
