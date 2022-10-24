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

		$set_data = array(
			'複写後商品名' => filter_input( INPUT_POST, '複写後商品名', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			'定期'     => filter_input( INPUT_POST, '定期', FILTER_VALIDATE_INT ),
			'同月回数'   => filter_input( INPUT_POST, '同月回数', FILTER_VALIDATE_INT ),
			'初回発送日'  => filter_input( INPUT_POST, '初回発送日', FILTER_VALIDATE_INT ),
			'毎月回数'   => filter_input( INPUT_POST, '毎月回数', FILTER_VALIDATE_INT ),
		);

		// 新しい返礼品情報設定
		$new_post = array(
			'post_title'  => '' !== $set_data['定期'] ? "【全{$set_data['定期']}回定期便】{$set_data['複写後商品名']}" : $set_data['複写後商品名'],
			'post_status' => 'pending',
			'post_author' => $author_id,
		);

		// 作成
		$newpost_id = wp_insert_post( $new_post );

		// metaを上書き
		foreach ( $post_all_meta as $key => $value ) {
			if ( '定期便' === $key ) {
				update_post_meta( $newpost_id, $key, $set_data['複写後商品名'] );
			} elseif ( '寄附金額' === $key ) {
				// 自動計算
				$auto_price = N2_Functions::kifu_auto_pattern( 'php', array( $post_all_meta['価格'], $post_all_meta['送料'] ) ) * $set_data['teiki'];
				update_post_meta( $newpost_id, $key, $auto_price );
			} elseif ( '説明文' === $key && $set_data['定期'] > 1 ) {
				$converted_item_description = preg_match( '/全[0-9]{1,2}回/', $post_all_meta['説明文'] ) ?
					preg_replace( '/全[0-9]{1,2}回/', "全{$set_data['定期']}回", $post_all_meta['説明文'] ) :
					"※こちらは全{$set_data['定期']}回お届けいたします。\n{$post_all_meta['説明文']}";

				update_post_meta( $newpost_id, $key, $converted_item_description );
			} elseif ( '内容量・規格等' === $key && $set_data['定期'] > 1 ) {
				$comverted_monthly_number = preg_match( '/月[0-9]{1,2}回/', $post_all_meta[ $key ] ) ?
					preg_replace( '/月[0-9]{1,2}回/', "月{$set_data['同月回数']}回", $post_all_meta[ $key ] ) :
					"以下の内容を全{$set_data['定期']}回（月{$set_data['同月回数']}回）お届けいたします。\n{$post_all_meta[$key]}";
					"以下の内容を全{$set_data['定期']}回（月{$set_data['同月回数']}回）お届けいたします。\n{$post_all_meta[$key]}";
					update_post_meta( $newpost_id, $key, $comverted_monthly_number );
			} elseif ( '配送期間' === $key && $set_data['定期'] > 1 ) {
				$comverted_delivery_date = preg_match( '/毎月[0-9]{1,2}日/', $post_all_meta['配送期間'] ) ?
					preg_replace( '/翌月の[0-9]{1,2}日/', "翌月の{$set_data['初回発送日']}日", preg_replace( '/毎月[0-9]{1,2}日/', "毎月{$set_data['毎月発送日']}日", $post_all_meta['配送期間'] ) ) :
					"※初回発送はお申込み翌月の{$set_data['初回発送日']}日までに発送致します。なお2回目以降も毎月{$set_data['毎月発送日']}日までに発送致します。\n{$post_all_meta['配送期間']}";
				update_post_meta( $newpost_id, $key, $comverted_delivery_date );
			} elseif ( '返礼品コード' === $key ) {
				// 同事業者の返礼品コードの数字部分のみを配列化
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
				$new_item_code = $prefix . sprintf( "%0{$num_length}d", max( ...$item_code_numbers ) + 1 );

				update_post_meta( $newpost_id, $key, $new_item_code );

			} else {
				update_post_meta( $newpost_id, $key, $value );
			}
		}

		echo wp_json_encode( $set_data );
		die();
	}
}
