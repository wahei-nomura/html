<?php
/**
 * class-n2-foodparam.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Foodparam' ) ) {
	new N2_Foodparam();
	return;
}

/**
 * Foodparam
 */
class N2_Foodparam {
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
		add_action( 'wp_login', array( $this, 'jigyousya_add_food' ), 11, 2 );
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
	}

	/**
	 * ajaxでDBに登録する
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		if ( empty( $_POST['food'] ) || '' === $_POST['food'] ) {
			return;
		}
		update_user_meta( wp_get_current_user()->ID, '食品取扱い', filter_input( INPUT_POST, 'food' ) );
		die();
	}

	/**
	 * judge_jigyousya
	 *
	 * @param Object $user_login user_login
	 * @param Object $user user
	 * @return void
	 */
	public function jigyousya_add_food( $user_login, $user ) {
		// 事業者ユーザーでなければreturn
		if ( ! empty( $user->roles[0] ) && 'jigyousya' !== $user->roles[0] ) {
			return;
		}

		// user_metaに食品取扱いがない、またはからの場合
		if ( empty( get_user_meta( $user->ID, '食品取扱い', true ) ) || '' === get_user_meta( $user->ID, '食品取扱い', true ) ) {
			wp_redirect( site_url() . '/wp-admin/admin.php?page=n2_food_menu' );
			exit;
		}
	}

	/**
	 * add_setup_menu
	 * クルー用セットアップ管理ページを追加
	 */
	public function add_setup_menu() {
		add_menu_page( '食品取扱設定', '食品取扱設定', 'jigyousya', 'n2_food_menu', array( $this, 'add_jigyousya_setup_menu_page' ), 'dashicons-list-view' );
	}

	/**
	 * 事業者食品用メニュー描画
	 *
	 * @return void
	 */
	public function add_jigyousya_setup_menu_page() {
		?>
			<form>
				<h2>事業者様の食品取扱いの有無を登録</h2>
				<div>
					<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
					<label for="foodyes"><input type="radio" name="food" id="foodyes" value="有">食品を取り扱っている</label>
					<label for="foodno"><input type="radio" name="food" id="foodno" value="無">食品を取り扱っていない</label>
				</div>
				<p>※返礼品登録時のアレルギー選択項目の表示に使用します。</p>
				<div>
					<button type="submit" class="button button-primary sissubmit">更新する</button>
				</div>
			</form>
		<?php
	}
}
