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

	/**
	 * usermetaに登録させたい項目をymlから取得
	 *
	 * @return Array $params
	 */
	private function params() {
		$params = yaml_parse_file( get_template_directory() . '/config/n2-jigyousya-params.yml' );

		return apply_filters( 'n2_jigyousyaparam_params', $params );
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

		foreach ( $params as $key => $value ) {
			if ( ! empty( get_user_meta( $user->ID, $value['meta'], true ) ) && '' !== get_user_meta( $user->ID, $value['meta'], true ) ) {
				// すでに登録済みの項目は出さない
				unset( $params[ $key ] );
			}
		}

		// 食品取扱登録用モーダルテンプレートをinclude
		if ( count( $params ) > 0 ) {
			get_template_part(
				'template/jigyousya-paramset',
				null,
				$args = array(
					'params' => $params,
					'cls'    => $this->cls,
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
		$params = $this->params();
		foreach ( $params as $key => $value ) {
			if ( ! empty( $_POST[ $key ] ) && '' !== $_POST[ $key ] ) {
				update_user_meta( wp_get_current_user()->ID, $value['meta'], filter_input( INPUT_POST, $key ) );
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
		$params = $this->params();

		get_template_part(
			'template/jigyousya-paramset',
			null,
			$args = array(
				'params' => $params,
				'cls'    => $this->cls,
			)
		);
	}
}
