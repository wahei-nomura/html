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
		'filename'      => 'N2データ.csv',
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
		'error'   => array(),
		'string'  => '',
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_filter( mb_strtolower( get_class( $this ) ) . '_walk_values', array( $this, 'check_error' ), 10, 3 );
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ), array( $this, 'export' ) );
	}

	/**
	 * エクスポートページ
	 */
	public function export() {

		// メモリUP
		wp_raise_memory_limit();
		add_filter( 'admin_memory_limit', fn() => '512M' );

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

		// 独自のデータをセット（基本的にこの２つを拡張）
		$this->set_header();
		$this->set_data();

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
		/**
		 * [hook] n2_item_export_base_set_n2field
		 */
		$this->data['n2field'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_n2field', $n2field );
	}

	/**
	 * N2データ取得してセット
	 */
	private function set_n2data() {
		$n2data = array();
		// 投稿制御
		$args = array(
			'post_status' => $_GET['post_status'] ?? 'any',
			'numberposts' => $_GET['numberposts'] ?? '-1',
			'fields'      => 'ids',
		);
		// POSTされた投稿ID
		$ids = filter_input( INPUT_POST, 'ids' );
		$ids = $ids ? explode( ',', $ids ) : get_posts( $args );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			// タイトル追加
			$n2data[ $id ]['タイトル'] = $post->post_title;
			// 事業者コード追加
			$n2data[ $id ]['事業者コード'] = get_user_meta( $post->post_author, 'last_name', true );
			// 提供事業者名・ポータル表示名があれば取得
			$portal_site_display_name = get_post_meta( $id, '提供事業者名', true ) ?: get_user_meta( $post->post_author, 'portal_site_display_name', true );
			// 事業者名
			$n2data[ $id ]['事業者名'] = match ( $portal_site_display_name ) {
				'記載しない' => '',
				'' => get_user_meta( $post->post_author, 'first_name', true ),
				default => $portal_site_display_name
			};
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
		/**
		 * [hook] n2_item_export_base_set_n2data
		 */
		$this->data['n2data'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_n2data', $n2data );
	}

	/**
	 * ヘッダー配列の作成（基本的に拡張で上書きする）
	 */
	protected function set_header() {
		// n2dataをもとに配列を作成
		$this->data['header'] = array_keys( reset( $this->data['n2data'] ) );
		/**
		 * [hook] n2_item_export_base_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * 内容を配列で作成
	 */
	private function set_data() {
		$data = array();
		foreach ( $this->data['n2data'] as $id => $values ) {
			$values['id'] = $id;
			// ヘッダーをセット
			$data[ $id ] = $this->data['header'];
			array_walk( $data[ $id ], array( $this, 'walk_values' ), $values );
			$data[ $id ] = array_combine( $this->data['header'], $data[ $id ] );
		}
		/**
		 * [hook] n2_item_export_base_set_data
		 */
		$data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data', $data );
		$data = array_diff_key( $data, $this->data['error'] );
		// dataをセット
		$this->data['data'] = $data;
	}

	/**
	 * データのマッピング（基本的に拡張で上書きする）
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		// 最終的に入る項目の値（文字列）
		$data = $n2values[ $val ] ?: '';
		if ( is_array( $data ) ) {
			// 多次元になっているものはラベルだけの配列に変更
			$data = array_column( $data, 'label' ) ?: $data;
			// |で連結
			$data = implode( '|', $data );
		}
		/**
		 * [hook] n2_item_export_base_walk_values
		 *
		 * @param string $data 項目値
		 * @param string $val 項目名
		 * @param array  $n2values n2dataのループ中の値
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
	}

	/**
	 * ヘッダー文字列生成
	 */
	private function set_header_string() {
		if ( false === $this->settings['header_string'] ) {
			$this->settings['header_string'] = '';
		} else {
			// ダブルクオーテーションで囲む
			$this->settings['header_string'] .= '"' . implode( "\"{$this->settings['delimiter']}\"", $this->data['header'] ) . '"' . PHP_EOL;
		}
		/**
		 * [hook] n2_item_export_base_set_header_string
		 */
		$this->settings['header_string'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header_string', $this->settings['header_string'] );
	}

	/**
	 * 配列をCSV・TSVに変換
	 */
	private function set_data_string() {
		$str = '';
		foreach ( $this->data['data'] as $key => $value ) {
			// ダブルクオーテーションのエスケープ処理
			$value = array_map( fn( $v ) => str_replace( '"', '""', $v ), $value );
			// その他文字列の痴漢
			$value = array_map( array( $this, 'special_str_convert' ), $value );
			$str  .= '"' . implode( "\"{$this->settings['delimiter']}\"", $value ) . '"' . PHP_EOL;
		}
		/**
		 * [hook] n2_item_export_base_set_data_string
		 */
		$this->data['string'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data_string', $str );
	}

	/**
	 * 文字列の置換（拡張で上書き可能）
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		/**
		 * [hook] n2_item_export_base_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}

	/**
	 * エラーチェック
	 *
	 * @param string $data 項目値
	 * @param string $val 項目名
	 * @param array  $n2values n2dataのループ中の値
	 */
	public function check_error( $data, $val, $n2values ) {
		if ( '' !== $data ) {
			return $data;
		}
		if ( preg_match( '/タイトル/', $val ) ) {
			$this->add_error( $n2values['id'], $val );
		}
		return $data;
	}

	/**
	 * エラー追加
	 *
	 * @param string $id 投稿ID
	 * @param string $val 項目名
	 */
	protected function add_error( $id, $val ) {
		$this->data['error'][ $id ][] = $val;
	}

	/**
	 * エラー表示
	 */
	private function display_error() {
		$html = '';
		$pattern = '<tr><th><a href="%s" target="_blank">%s</a></th><td>%s</td></tr>';
		foreach ( $this->data['error'] as $id => $errors ) {
			$html .= wp_sprintf( $pattern, get_edit_post_link( $id ), $id, implode( '<br>', $errors ) );
		}
		?>
		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>エラー項目</th></tr>
			</thead>
			<?php echo $html; ?>
		</table>
		<?php
	}

	/**
	 * ダウンロード
	 */
	private function download() {
		global $n2;
		/**
		 * [hook] n2_item_export_base_charset
		 */
		$charset = apply_filters( mb_strtolower( get_class( $this ) ) . '_charset', $this->settings['charset'] );
		/**
		 * [hook] n2_item_export_base_filename
		 */
		$filename = apply_filters( mb_strtolower( get_class( $this ) ) . '_filename', $n2->town . $this->settings['filename'] );

		// POST送信されたか判定
		$str = filter_input( INPUT_POST, 'str' );
		if ( ! $str ) {
			$this->set_header_string();
			$this->set_data_string();
			// 出力文字列
			$str = $this->settings['header_string'] . $this->data['string'];
			// エラー
			$error = $this->data['error'];
		}
		if ( empty( $error ) ) {
			// 文字コード変換
			$str = mb_convert_encoding( $str, $charset, 'utf-8' );
			header( 'Content-Type: application/octet-stream' );
			header( "Content-Disposition: attachment; filename={$filename}" );
			echo htmlspecialchars_decode( $str );
			exit;
		}
		if ( ! $str ) {
			echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">';
			$this->display_error();
			exit;
		}
		?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
		<form method="post" class="p-3 m-0 sticky-top justify-content-center d-flex bg-dark">
			<input type="hidden" name="str" value="<?php echo esc_attr( $str ); ?>">
			<button id="download" class="btn btn-success px-5">エラーが無い返礼品のみダウンロードする</button>
		</form>
		<?php
		$this->display_error();
		exit;
	}

	/**
	 * スプレットシートに値貼り付け用
	 */
	private function spreadsheet() {
		$this->settings['delimiter']     = "\t";// タブを強制
		$this->settings['header_string'] = '';// ヘッダーを強制
		$this->set_header_string();
		$this->set_data_string();
		// データを文字列に
		$this->display_error();
		?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
		<title>スプレットシートに値貼り付け</title>
		<textarea class="form-control"style="height:100%"><?php echo esc_html( $this->settings['header_string'] . $this->data['string'] ); ?></textarea>
		<?php
		exit;
	}

	/**
	 * デバッグ用
	 */
	private function debug() {
		$this->set_header_string();
		$this->set_data_string();
		echo '<pre>';
		print_r( $this->settings );
		print_r( $this->data );
		echo '</pre>';
		exit;
	}
}

