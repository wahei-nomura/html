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
		add_action( 'admin_footer', array( $this, 'show_food_modal' ) );
		add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( "wp_ajax_{$this->cls}", array( &$this, 'update_setupmenu' ) );
	}
	/**
	 * 事業者ユーザーが食品取扱データを持っていない場合モーダル表示
	 *
	 * @return void
	 */
	public function show_food_modal() {
		$user = wp_get_current_user();
		if ( 'jigyousya' !== $user->roles[0] ) {
			return;
		}

		if ( empty( get_user_meta( $user->ID, '食品取扱い', true ) ) || '' === get_user_meta( $user->ID, '食品取扱い', true ) ) {
		$value = get_user_meta( wp_get_current_user()->ID, '食品取扱い', true ) ? get_user_meta( wp_get_current_user()->ID, '食品取扱い', true ) : '';
		?>
			<div class="ss-food-modal" style="position:fixed;top:50%;left:50%;z-index:100000;background-color: pink;">
				<form>
					<h2>事業者様の食品取扱いの有無を登録</h2>
					<div>
						<input type="hidden" name="action" value="<?php echo $this->cls; ?>">
						<label for="foodyes"><input type="radio" name="food" id="foodyes" value="有"<?php checked( $value, '有' ); ?>>食品を取り扱っている</label>
						<label for="foodno"><input type="radio" name="food" id="foodno" value="無"<?php checked( $value, '無' ); ?>>食品を取り扱っていない</label>
					</div>
					<p>※返礼品登録時のアレルギー選択項目の表示に使用します。</p>
					<div>
						<button type="button" class="button button-primary sissubmit">更新する</button>
					</div>
				</form>
				<script>
					jQuery(function($){
						$('.sissubmit').on('click',()=>{
							setTimeout(()=>{
								$('.ss-food-modal').remove()
							},1000)
						})
					})
				</script>
			</div>
		<?php
		}

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
		echo '食品取扱い有無更新完了';
		die();
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
