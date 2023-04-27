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
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'name'      => 'n2_export_base.tsv',
		'delimiter' => "\t",
		'charset'   => 'utf-8',
	);

	/**
	 * データ
	 *
	 * @var array
	 */
	public $data = array(
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
			'type' => 'download',
		);
		// デフォルト値を$_GETで上書き
		$get = wp_parse_args( $_GET, $defaults );

		// n2としてのデータをセット
		$this->set_n2field();
		$this->set_n2data();

		// 独自のデータをセット
		$this->set_header();
		$this->set_data();
		$this->set_string();

		$this->{$get['type']}();
	}

	/**
	 * N2カスタムフィールド全設定項目をセット
	 */
	protected function set_n2field() {
		global $n2;
		$n2field = array(
			...array_keys( $n2->custom_field['スチームシップ用'] ),
			...array_keys( $n2->custom_field['事業者用'] ),
		);
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
	protected function set_n2data() {
		$n2data = array();
		// $ids  = filter_input( INPUT_POST, 'ids' );
		// $ids  = explode( ',', $ids );
		$ids = get_posts( 'fields=ids&post_status=any&numberposts=10' );
		foreach ( $ids as $id ) {
			$n2data[ $id ]['タイトル'] = get_the_title( $id );
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
	 * ヘッダーの作成
	 */
	protected function set_header() {
		$this->data['header'] = $this->data['n2field'];
	}

	/**
	 * 内容を配列で作成
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
				$values[ $name ] = "\"{$values[ $name ]}\"";
			}
			$data[ $id ] = $values;
		}
		$this->data['data'] = $data;
	}

	/**
	 * 配列をCSV・TSVに変換
	 */
	protected function set_string() {
		$str = '';
		$str = '"' . implode( "\"\t\"", $this->data['header'] ) . '"';
		foreach ( $this->data['data'] as $key => $value ) {
			
		}
		$this->data['string'] = $str;
	}

	/**
	 * ダウンロード
	 */
	protected function download() {
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$this->settings['name']}" );
		echo htmlspecialchars_decode( $this->data['string'] );
		exit;
	}

	/**
	 * ビュー
	 */
	protected function view() {
		// デリミタをタブに矯正
		?>
		<p>このままスプレットシートに貼付け可能</p>
		<textarea><?php echo $this->settings['string']; ?></textarea>
		<?php
		exit;
	}

	/**
	 * デバッグ
	 */
	protected function debug() {
		echo '<pre>';
		print_r( $this->settings );
		print_r( $this->data );
		echo '</pre>';
		exit;
	}
}

