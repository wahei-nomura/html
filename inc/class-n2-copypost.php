<?php
/**
 * class-n2-copypost.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Copypost' ) ) {
	new N2_Copypost();
	return;
}

/**
 * Setpost
 */
class N2_Copypost {
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
		add_action( "wp_ajax_{$this->cls}", array( $this, 'copy_create_post' ) );
	}

	/**
	 * 返礼品複製
	 *
	 * @return void
	 */
	public function copy_create_post() {
		// 複製元の返礼品情報取得
		$post          = get_post( filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT ) );
		$post_all_meta = N2_Functions::get_all_meta( $post );
		$author_id     = $post->post_author;

		// POST受信を配列か
		$set_data = array(
			'複写後商品名' => filter_input( INPUT_POST, '複写後商品名', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			'定期'     => filter_input( INPUT_POST, '定期', FILTER_VALIDATE_INT ),
			'初回発送日'  => filter_input( INPUT_POST, '初回発送日', FILTER_VALIDATE_INT ),
			'同月回数'   => filter_input( INPUT_POST, '同月回数', FILTER_VALIDATE_INT ),
			'毎月発送日'  => filter_input( INPUT_POST, '毎月発送日', FILTER_VALIDATE_INT ),
		);

		// metaを上書き ----------------------------------------------------------------------------------------------------------------------------------------
		$post_all_meta['定期便']  = $set_data['定期'];
		$post_all_meta['寄附金額'] = N2_Functions::kifu_auto_pattern( 'php', array( (int) $post_all_meta['価格'], (int) $post_all_meta['送料'] ) ) * $set_data['定期'];

		// 定期便
		if ( $set_data['定期'] > 1 ) {
			$post_all_meta['説明文'] = preg_match( '/全[0-9]{1,2}回/', mb_convert_kana( $post_all_meta['説明文'], 'n' ) ) ?
			preg_replace( '/全[0-9]{1,2}回/', "全{$set_data['定期']}回", mb_convert_kana( $post_all_meta['説明文'], 'n' ) ) : "※こちらは全{$set_data['定期']}回お届けいたします。\n{$post_all_meta['説明文']}";

			$post_all_meta['内容量・規格等'] = preg_match( '/月[0-9]{1,2}回/', mb_convert_kana( $post_all_meta['内容量・規格等'], 'n' ) ) ?
			preg_replace( '/月[0-9]{1,2}回/', "月{$set_data['同月回数']}回", mb_convert_kana( $post_all_meta['内容量・規格等'], 'n' ) ) :
			"以下の内容を全{$set_data['定期']}回（月{$set_data['同月回数']}回）お届けいたします。\n{$post_all_meta['内容量・規格等']}";

			$post_all_meta['配送期間'] = preg_match( '/毎月[0-9]{1,2}日/', mb_convert_kana( $post_all_meta['配送期間'], 'n' ) ) ?
			preg_replace( '/翌月の[0-9]{1,2}日/', "翌月の{$set_data['初回発送日']}日", preg_replace( '/毎月[0-9]{1,2}日/', "毎月{$set_data['毎月発送日']}日", mb_convert_kana( $post_all_meta['配送期間'], 'n' ) ) ) :
			"※初回発送はお申込み翌月の{$set_data['初回発送日']}日までに発送致します。なお2回目以降も毎月{$set_data['毎月発送日']}日までに発送致します。\n{$post_all_meta['配送期間']}";
		}

		// 返礼品コード加工
		$item_code_numbers = array_map(
			function ( $item ) {
				return (int) preg_replace( '/[A-Z]/', '', get_post_meta( $item->ID, '返礼品コード', true ) );
			},
			get_posts( "author={$author_id}&post_status=any" )
		);

		$prefix = preg_replace( '/[0-9]{2,3}/', '', $post_all_meta['返礼品コード'] );

		// 桁違い対応
		$num_length = mb_strlen( $post_all_meta['返礼品コード'] ) - mb_strlen( $prefix );

		// 0詰め
		$new_item_code           = $prefix . sprintf( "%0{$num_length}d", max( ...$item_code_numbers ) + 1 );
		$post_all_meta['返礼品コード'] = $new_item_code;
		// meta上書き　ここまで ------------------------------------------------------------------------------------------------------------------------------

		// 新しい返礼品情報設定
		$new_post = array(
			'post_title'  => $set_data['定期'] > 1 ? "【全{$set_data['定期']}回定期便】{$set_data['複写後商品名']}" : $set_data['複写後商品名'],
			'post_status' => 'pending',
			'post_author' => $author_id,
			'meta_input'  => $post_all_meta,
		);

		wp_insert_post( $new_post );

		wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		exit;
	}
}
