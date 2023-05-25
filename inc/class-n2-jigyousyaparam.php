<?php
/**
 * class-n2-jigyousyaparam.php
 *
 * @package neoneng
 */

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

	/**
	 * usermetaに登録させたい項目をymlから取得
	 *
	 * @return Array $jigyousya_meta
	 */
	private function jigyousya_meta() {
		$jigyousya_meta = yaml_parse_file( get_theme_file_path() . '/config/n2-jigyousya-meta.yml' );

		return apply_filters( 'n2_jigyousyaparam_meta', $jigyousya_meta );
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

		$jigyousya_meta = $this->jigyousya_meta();

		// 食品取扱登録用モーダルテンプレートをinclude
		if ( empty( get_user_meta( $user->ID, '商品タイプ', true ) ) ) {
			get_template_part(
				'template/jigyousya-paramset',
				null,
				$args = array(
					'jigyousya_meta' => $jigyousya_meta,
					'cls'            => $this->cls,
				)
			);
		}

	}

	/**
	 * ajaxでDBに登録する
	 *
	 * @return void
	 */
	public function update_setupmenu() {
		if ( empty( $_POST ) ) {
			echo 'パラメータが不正です';
			exit;
		}

		global $n2;
		$jigyousya_meta = $this->jigyousya_meta();
		$item_types     = ! empty( $n2->current_user->data->meta['商品タイプ'] ) ? $n2->current_user->data->meta['商品タイプ'] : array();

		foreach ( $jigyousya_meta as $item_type => $value ) {
			if ( ! empty( $_POST[ $item_type ] ) && '' !== $_POST[ $item_type ] ) {
				$item_types[ $item_type ] = $_POST[ $item_type ];
			}
		}

		update_user_meta( wp_get_current_user()->ID, '商品タイプ', $item_types );
		echo '商品タイプ更新完了';
		die();
	}

	/**
	 * add_setup_menu
	 * クルー用セットアップ管理ページを追加
	 */
	public function add_setup_menu() {
		if ( ! current_user_can( 'administrator' ) ) {
			add_menu_page( '返礼品の設定', '返礼品の設定', 'jigyousya', 'n2_jigyousya_menu', array( $this, 'add_jigyousya_setup_menu_page' ), 'dashicons-list-view' );
		}
	}

	/**
	 * 事業者食品用メニュー描画
	 *
	 * @return void
	 */
	public function add_jigyousya_setup_menu_page() {
		$jigyousya_meta = $this->jigyousya_meta();

		get_template_part(
			'template/jigyousya-paramset',
			null,
			$args = array(
				'jigyousya_meta' => $jigyousya_meta,
				'cls'            => $this->cls,
			)
		);
	}
}
