<?php
/**
 * class-n2-item-export-base.php
 * BasicなN2エクスポート
 * このクラスを拡張して他のチョイスなど対応する
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Base' ) ) {
	new N2_Item_Export_Base();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Base {

	/**
	 * 設定（基本的に拡張で上書きする）
	 *
	 * @var array
	 */
	protected $settings = array(
		'name'          => 'n2_export_base.csv',
		'delimiter'     => ',',
		'charset'       => 'utf-8',
		'header_string' => '', // 基本は自動設定、falseでヘッダー文字列無し
	);

	/**
	 * データ
	 *
	 * @var array
	 */
	protected $data = array(
		'header'  => array(),
		'n2field' => array(),
		'n2data'  => array(),
		'data'    => array(),
		'string'  => '',
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ), array( $this, 'export' ) );
	}

	/**
	 * エクスポートページ
	 */
	public function export() {
		$defaults = array(
			'mode' => 'download',
		);
		// デフォルト値を$_GETで上書き
		$get = wp_parse_args( $_GET, $defaults );

		if ( ! method_exists( 'N2_Item_Export_Base', $get['mode'] ) ) {
			echo "「{$get['mode']}」メソッドは存在しません。";
			exit;
		}

		// n2としてのデータをセット
		$this->set_n2field();
		$this->set_n2data();

		// 独自のデータをセット
		$this->set_header();
		$this->set_data();

		// データを文字列に
		$this->set_header_string();
		$this->set_data_string();

		$this->{$get['mode']}();
	}

	/**
	 * N2カスタムフィールド全設定項目をセット
	 */
	private function set_n2field() {
		global $n2;
		$n2field = array(
			...array_keys( $n2->custom_field['スチームシップ用'] ),
			...array_keys( $n2->custom_field['事業者用'] ),
		);
		// 重複削除
		$n2field = array_unique( $n2field );
		// 商品画像とN1zipは使いようがないので対象から外す
		$exclusion = array(
			'商品画像',
			'N1zip',
		);
		// フィルタ
		$n2field = array_filter( $n2field, fn( $v ) => ! in_array( $v, $exclusion, true ) );
		// 代入
		$this->data['n2field'] = $n2field;
	}

	/**
	 * N2データ取得してセット
	 */
	private function set_n2data() {
		$n2data = array();
		// POSTされた投稿ID
		$ids = filter_input( INPUT_POST, 'ids' );
		$ids = $ids ? explode( ',', $ids ) : get_posts( 'post_status=any&fields=ids&numberposts=-1' );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			// タイトル追加
			$n2data[ $id ]['タイトル'] = $post->post_title;
			// 事業者コード追加
			$n2data[ $id ]['事業者コード'] = get_user_meta( $post->post_author, 'last_name', true );
			// 事業者名追加
			$n2data[ $id ]['事業者名'] = get_user_meta( $post->post_author, 'first_name', true );
			// 投稿ステータス追加
			$n2data[ $id ]['ステータス'] = get_post_status( $id );
			// n2fieldのカスタムフィールド全取得
			foreach ( $this->data['n2field'] as $key ) {
				$meta = get_post_meta( $id, $key, true );
				// 値が配列の場合、空は削除
				if ( is_array( $meta ) ) {
					$meta = array_filter( $meta, fn( $v ) => $v );
				}
				$n2data[ $id ][ $key ] = $meta;
			}
		}
		$this->data['n2data'] = $n2data;
	}

	/**
	 * ヘッダー配列の作成（基本的に拡張で上書きする）
	 */
	protected function set_header() {
		$this->data['header'] = array(
			'タイトル',
			'事業者コード',
			'事業者名',
			'ステータス',
			...$this->data['n2field'],
		);
	}

	/**
	 * 内容を配列で作成（基本的に拡張で上書きする）
	 */
	protected function set_data() {
		$data = array();
		foreach ( $this->data['n2data'] as $id => $values ) {
			foreach ( $values as $name => $val ) {
				if ( is_array( $val ) ) {
					// 多次元になっているものはラベルだけの配列に変更
					$values[ $name ] = array_column( $val, 'label' ) ?: $val;
					// |で連結
					$values[ $name ] = implode( '|', $values[ $name ] );
				}
				$values[ $name ] = $values[ $name ];
			}
			$data[ $id ] = $values;
		}
		$this->data['data'] = $data;
	}

	/**
	 * ヘッダー文字列生成
	 */
	private function set_header_string() {
		if ( false === $this->settings['header_string'] ) {
			$this->settings['header_string'] = '';
		} else {
			$this->settings['header_string'] .= '"' . implode( "\"{$this->settings['delimiter']}\"", $this->data['header'] ) . '"' . PHP_EOL;
		}
	}

	/**
	 * 配列をCSV・TSVに変換
	 */
	private function set_data_string() {
		$str = $this->settings['header_string'];
		foreach ( $this->data['data'] as $key => $value ) {
			// ダブルクオーテーションのエスケープ処理
			$value = array_map( fn( $v ) => str_replace( '"', '""', $v ), $value );
			// その他文字列の痴漢
			$value = array_map( array( $this, 'special_str_convert' ), $value );
			$str  .= '"' . implode( "\"{$this->settings['delimiter']}\"", $value ) . '"' . PHP_EOL;
		}
		$this->data['string'] = $str;
	}

	/**
	 * 文字列の置換（拡張で上書き可能）
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		/**
		 * hook n2_item_export_base_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}

	/**
	 * ダウンロード
	 */
	private function download() {
		$this->data['string'] = mb_convert_encoding( $this->data['string'], $this->settings['charset'], 'utf-8' );
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$this->settings['name']}" );
		echo htmlspecialchars_decode( $this->data['string'] );
		exit;
	}

	/**
	 * スプレットシートに値貼り付け用
	 */
	private function spreadsheet() {
		// ヘッダーを強制的に付与
		if ( '' === $this->settings['header_string'] ) {
			$this->set_header_string();
		}
		$this->settings['delimiter'] = "\t"; // デリミタをタブに変更
		$this->set_data_string();
		?>
		<title>スプレットシートに値貼り付け</title>
		<pre><?php echo esc_html( $this->data['string'] ); ?></pre>
		<?php
		exit;
	}

	/**
	 * デバッグ用
	 */
	private function debug() {
		echo '<pre>';
		print_r( $this->settings );
		print_r( $this->data );
		echo '</pre>';
		exit;
	}
}

