<?php
/**
 * class-n2-item-export.php
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export' ) ) {
	new N2_Item_Export();
	return;
}

/**
 * Foodparam
 */
class N2_Item_Export {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// エクスポート関連全読込
		$path = get_theme_file_path( '/inc/item-export/*.php' );
		foreach ( glob( $path ) as $filename ) {
			require_once $filename;
		}
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * エクスポートページを追加
	 */
	public function add_menu() {
		add_submenu_page( 'post-new.php', 'エクスポート', 'エクスポート', 'ss_crew', 'n2-item-export', array( $this, 'ui' ) );
	}

	/**
	 * エクスポートページ コンテンツ
	 */
	public function ui() {
		?>
		<form action="admin-ajax.php" target="_blank" method="post">
			<input type="hidden" name="action" value="n2_item_export_base">
			<input type="hidden" name="mode" value="debug">
			<input type="hidden" name="numberposts" value="5">
			<input type="hidden" name="n2nonce" value="<?php echo wp_create_nonce( 'n2nonce' ); ?>">
			<input type="checkbox" name="ids[]" value="22344">
			<input type="checkbox" name="ids[]" value="22365">
			<button>ダウンロード</button>
		</form>
		<?php
	}

	/**
	 * メニューバー追加
	 *
	 * @param object $wp_admin_bar メニューバーオブジェクト
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {

		$wp_admin_bar->add_menu(
			array(
				'id'    => 'n2-item-export',
				'title' => 'エクスポート',
				'href'  => '#',
			)
		);
		$exports = array(
			'ledghome'        => 'LedgHOME',
			'furusato-choice' => 'ふるさとチョイス',
			'rakuten'         => '楽天 [ item.csv ]',
			'rakuten-select'  => '楽天 [ select.csv ]',
		);
		foreach ( $exports as $id => $title ) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'n2-item-export',
					'id'     => $id,
					'title'  => $title,
					'href'   => 'admin-ajax.php',
					'meta'   => array(
						'target'  => '_blank',
						'onclick' => 'alert("hoge")',
					),
				)
			);
		}
	}
}
