<?php
/**
 * ポータルデータ関連
 *
 * @package neoneng
 */

 if ( class_exists( 'N2_Portal_Item_Data' ) ) {
	new N2_Portal_Item_Data();
	return;
}

/**
 * ポータルスクレイピング
 */
class N2_Portal_Item_Data {

	/**
	 * ポータルデータ保存用投稿タイプ名
	 *
	 * @var array
	 */
	public $post_type = 'portal_item_data';

	/**
	 * 保存時のタイトル
	 *
	 * @var array
	 */
	public $post_title = 'portal';

	/**
	 * 保存データ
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// 子クラスでのみ実行
		if ( is_subclass_of( $this, 'N2_Portal_Item_Data' ) ) {
			// 旧APIのURLに対応（Kintone対応後削除）
			if ( preg_match( '/api_(.*)/', mb_strtolower( get_class( $this ) ), $m ) ) {
				add_action( "wp_ajax_n2_{$m[1]}_items_api", array( $this, 'get' ) );
			}
			add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_update', array( $this, 'update' ) );
			if ( ! wp_next_scheduled( 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_update' ) ) {
				wp_schedule_event( time() + 200, 'hourly', 'wp_ajax_' . mb_strtolower( get_class( $this ) ) . '_update' );
			}
		} else {
			add_action( 'init', array( $this, 'create_post_type' ) );
			add_action( 'init', array( $this, 'enable_portal_items_api' ) );
		}
	}

	/**
	 * 旧APIのURLに対応（Kintone対応後削除）
	 */
	public function get() {
		$args = array(
			'post_type' => 'portal_item_data',
			'title'     => $this->post_title,
		);
		$data = N2_Items_API::get_items( $args );
		$data = array(
			'update' => $data[0]['post_date'],
			'data'   => $data[0]['post_content'],
		);
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * 投稿タイプ追加
	 */
	public function create_post_type() {
		register_post_type(
			$this->post_type,
			array(
				'supports'      => array(
					'revisions',
				),
			)
		);
	}

	/**
	 * 有効化されているポータル分だけ読込
	 */
	public function enable_portal_items_api() {
		global $n2;
		$translates = array_flip( N2_Settings::$settings );
		foreach ( array_filter( $n2->settings['N2']['出品ポータル'] ?? array() ) as $name ) {
			require_once get_theme_file_path( "/inc/api/class-n2-items-api-{$translates[ $name ]}.php" );
		}
	}

	/**
	 * APIデータのアップデート
	 */
	public function update() {
		exit;
	}

	/**
	 * ポータルデータをwp_postsに登録
	 */
	public function insert_portal_data() {
		global $n2;
		// goods_g_numでソートする
		array_multisort( array_column( $this->data, 'goods_g_num' ), SORT_ASC, $this->data );
		$this->data = array_values( $this->data );
		// 保存用配列
		$args = array(
			'post_type'    => $this->post_type,
			'post_title'   => $this->post_title,
			'post_content' => wp_json_encode( $this->data, JSON_UNESCAPED_UNICODE ),
		);
		$ids  = get_posts( "title={$this->post_title}&post_type={$this->post_type}&post_status=any&fields=ids" );
		kses_remove_filters();
		if ( empty( $ids ) ) {
			$id = wp_insert_post( $args );
			// ログ追加
			if ( ! is_wp_error( $id ) ) {
				wp_save_post_revision( $id );// 初回リビジョンの登録
			}
		} else {
			$args['ID'] = $ids[0];
			$id         = wp_update_post( $args );
		}
		kses_init_filters();
		header( 'Content-Type: application/json; charset=utf-8' );
		printf( "ポータルデータ更新完了\n\n自治体：%s\nポータル：%s\n件数：%s", $n2->town, $this->post_title, count( $this->data ) );
	}

	/**
	 * エラー終了
	 *
	 * @param array|string $error エラー時の表示
	 */
	public function exit( $error ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		print_r( $error );
		exit;
	}
}
