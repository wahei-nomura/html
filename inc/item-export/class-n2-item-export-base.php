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
		'params'       => array(),
		'memory_usage' => array(),
		'header'       => array(),
		'n2field'      => array(),
		'n2data'       => array(),
		'data'         => array(),
		'error'        => array(),
		'string'       => '',
	);

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->set_memory_usage( 'start' );
		add_filter( mb_strtolower( get_class( $this ) ) . '_walk_values', array( $this, 'check_error' ), 10, 3 );
		add_action( 'wp_ajax_' . mb_strtolower( get_class( $this ) ), array( $this, 'export' ) );
		add_filter( mb_strtolower( get_class( $this ) ) . '_filename', array( $this, 'add_town_to_filename' ) );
	}

	/**
	 * ファイル名に自治体名を追加する
	 *
	 * @param string $filename filename
	 * @return string
	 */
	public function add_town_to_filename( $filename ) {
		global $n2;
		preg_match( '/\.(tsv|csv)/', $filename, $match );
		$extensions = $match[0];
		return str_replace( $extensions, "_{$n2->town}{$extensions}", $filename );
	}

	/**
	 * エクスポートページ
	 */
	public function export() {
		$this->data['time']['start'] = microtime( true );
		// ↓ここから計測したいPHPの処理を記述する↓
		// パラメーターをセット
		$this->set_params();

		// n2としてのデータをセット
		$this->set_n2field();
		$this->set_n2data();

		// 独自のデータをセット（基本的にこの２つを拡張）
		$this->set_header();
		$this->set_data();

		// n2_log($this->data['memory_usage']);
		$this->{$this->data['params']['mode']}();
	}

	/**
	 * パラメータのセット
	 */
	private function set_params() {
		$params = $_GET;
		// $_POSTを$paramsで上書き
		if ( wp_verify_nonce( $_POST['n2nonce'] ?? '', 'n2nonce' ) ) {
			$params = wp_parse_args( $params, $_POST );
		}
		$defaults = array(
			'post_status' => 'any',
			'numberposts' => -1,
			'mode'        => 'download',
			'sort'        => '返礼品コード',
			'order'       => '',
		);
		// デフォルト値を$paramsで上書き
		$this->data['params'] = wp_parse_args( $params, $defaults );
		$this->set_memory_usage( 'set_params' );
	}

	/**
	 * N2カスタムフィールド全設定項目をセット
	 */
	private function set_n2field() {
		global $n2;
		$n2field = array(
			...array_keys( $n2->custom_field['スチームシップ用'] ),
			...array_keys( $n2->custom_field['自治体用']),
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
		$this->set_memory_usage( 'set_n2field' );
	}

	/**
	 * N2データ取得してセット
	 */
	private function set_n2data() {
		$n2data = array();
		$fields = array(
			'id',
			'タイトル',
			'事業者コード',
			'事業者名',
			'ステータス',
			...$this->data['n2field'],
			'_n2_required',
		);
		foreach ( N2_Items_API::get_items() as $v ) {
			// fieldを絞る
			foreach ( $fields as $key ) {
				$n2data[ $v['id'] ][ $key ] = $v[ $key ] ?? '';
			}
		}
		/**
		 * [hook] n2_item_export_base_set_n2data
		 */
		$n2data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_n2data', $n2data );
		// ソート
		array_multisort(
			array_column( $n2data, $this->data['params']['sort'] ),
			'desc' === strtolower( $this->data['params']['order'] ) ? SORT_DESC : SORT_ASC,
			$n2data
		);
		$this->data['n2data'] = $n2data;
		$this->set_memory_usage( 'set_n2data' );
	}

	/**
	 * ヘッダー配列の作成（基本的に拡張で上書きする）
	 */
	protected function set_header() {
		// n2dataをもとに配列を作成
		$header = reset( $this->data['n2data'] );
		$header = array_keys( $header );
		// アンダースコアで始まるものを排除
		$header = array_filter( $header, fn( $v ) => ! preg_match( '/^_/', $v ) );
		/**
		 * [hook] n2_item_export_base_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $header );
		$this->set_memory_usage( 'set_header' );
	}

	/**
	 * 内容を配列で作成
	 */
	protected function set_data() {
		$data = array();
		$this->check_fatal_error( $this->data['header'], 'ヘッダーが正しくセットされていません' );
		// データをセットする
		foreach ( $this->data['n2data'] as $key => $values ) {
			$id = $values['id'];
			// ヘッダーをセット
			$data[ $id ] = $this->data['header'];
			array_walk( $data[ $id ], array( $this, 'walk_values' ), $values );
			$data[ $id ] = array_filter( $data[ $id ], fn( $v ) => ! is_array( $v ) || ! empty( $v ) );
			if ( ! empty( $data[ $id ] ) ) {
				$data[ $id ] = array_combine( $this->data['header'], $data[ $id ] );
			}
		}
		/**
		 * [hook] n2_item_export_base_set_data
		 */
		$data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data', $data );
		// エラーは排除
		$data = array_diff_key( $data, $this->data['error'] );
		$data = array_values( $data );
		// dataをセット
		$this->data['data'] = array_filter( $data );// 空は削除
		$this->set_memory_usage( 'set_data' );
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
		// 商品属性だけ階層が深くなっているので必要なデータだけ掘りだして格納
		$data = match ( $val ) {
			'商品属性' => is_array( $data )
			? implode(
				"\n",
				array_map(
					fn( $v ) => sprintf(
						'%s%s：%s%s',
						$v['nameJa'],
						$v['properties']['rmsMandatoryFlg'] ? '*' : '',
						$v['value'],
						! empty( $v['unitValue'] ) ? '：' . $v['unitValue'] : ''
					),
					$data
				)
			)
			: '',
			default => $data,
		};
		if ( is_array( $data ) ) {
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
			// $valueが多次元配列の場合は行列入れ替え
			$value = match ( count( $value, COUNT_RECURSIVE ) ) {
				count( $value ) => array( $value ),
				default => array_map( null, ...array_values( $value ) ),
			};
			foreach ( $value as $val ) {
				// ダブルクオーテーションのエスケープ処理
				$val = array_map( fn( $v ) => str_replace( '"', '""', $v ), $val );
				// その他文字列の痴漢
				$val  = array_map( array( $this, 'special_str_convert' ), $val );
				$str .= '"' . implode( "\"{$this->settings['delimiter']}\"", $val ) . '"' . PHP_EOL;
			}
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
	 * @param string $value 項目値
	 * @param string $name 項目名
	 * @param array  $n2values n2dataのループ中の値
	 *
	 * @return $value
	 */
	public function check_error( $value, $name, $n2values ) {
		if ( '' !== $value ) {
			return $value;
		}
		if ( preg_match( '/タイトル/', $name ) ) {
			$this->add_error( $n2values['id'], "<div>「{$name}」がありません。</div>" );
		}
		return $value;
	}

	/**
	 * エラー追加
	 *
	 * @param string $id 投稿ID
	 * @param string $name 項目名
	 */
	protected function add_error( $id, $name ) {
		$this->data['error'][ $id ][] = $name;
	}

	/**
	 * エラー表示
	 */
	private function display_error() {
		if ( empty( $this->data['error'] ) ) {
			return;
		}
		$html    = '';
		$pattern = '<tr><th><a href="%s" target="_blank">%s</a></th><td><ul class="mb-0"><li>%s</li></ul></td></tr>';
		foreach ( $this->data['error'] as $id => $errors ) {
			$html .= wp_sprintf( $pattern, get_edit_post_link( $id ), $id, implode( '</li><li>', $errors ) );
		}
		?>
		<table class="table table-striped">
			<thead>
				<tr><th>ID</th><th>エラー内容</th></tr>
			</thead>
			<?php echo $html; ?>
		</table>
		<?php
	}

	/**
	 * 致命的なエラーのチェック
	 *
	 * @param array  $data チェックするデータ
	 * @param string $message メッセージ
	 */
	protected function check_fatal_error( $data, $message ) {
		if ( ! $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo $message;
			exit;
		}
	}

	/**
	 * 事業者名の変換（提供事業者名 > ポータル表示名 > 事業者名）
	 *
	 * @param array $data ループ中の返礼品データ
	 */
	protected function get_author_name( $data ) {
		$author = $data['事業者名'];
		// ポータル表示名
		$portal_site_display_name = get_user_meta(
			get_post( $data['id'] )->post_author,
			'portal_site_display_name',
			true
		);
		// ポータル表示名ロジック
		$author = match ( $portal_site_display_name ) {
			'記載しない' => '',
			'' => $author,
			default => $portal_site_display_name
		};
		// 提供事業者名
		$author = match ( $data['提供事業者名'] ?? '' ) {
			'' => $author,
			default => $data['提供事業者名'],
		};
		return $author;
	}

	/**
	 * ダウンロード
	 */
	private function download() {
		/**
		 * [hook] n2_item_export_base_charset
		 */
		$charset = apply_filters( mb_strtolower( get_class( $this ) ) . '_charset', $this->settings['charset'] );
		/**
		 * [hook] n2_item_export_base_filename
		 */
		$filename = apply_filters( mb_strtolower( get_class( $this ) ) . '_filename', $this->settings['filename'] );
		/**
		 * [hook] n2_item_export_base_download_add_btn
		 */
		$add_btn = apply_filters( mb_strtolower( get_class( $this ) ) . '_download_add_btn', array() );

		// POST送信されたか判定
		$str      = filter_input( INPUT_POST, 'str' );
		$option   = filter_input( INPUT_POST, 'option' );
		$n2nonce  = filter_input( INPUT_POST, 'n2nonce' );
		$includes = filter_input( INPUT_POST, 'include', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		/**
		 * [hook] n2_item_export_base_download_str
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_download_str', $str, $option );

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
		// エラーしか無い場合
		if ( empty( $this->data['data'] ) ) {
			echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">';
			$this->display_error();
			exit;
		}
		?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
		<div class=" sticky-top justify-content-evenly d-flex bg-dark">

			<form method="post" class="p-3 m-0">
				<input type="hidden" name="action" value="<?php echo esc_attr( mb_strtolower( get_class( $this ) ) ); ?>">
				<input type="hidden" name="str" value="<?php echo esc_attr( $str ); ?>">
				<button id="download" class="btn btn-success px-5">エラーが無い返礼品のみダウンロードする</button>
			</form>
			<?php if ( ! empty( $add_btn ) ) : ?>  
			<?php foreach ( $add_btn as $btn ) : ?>
			<form method="post" class="p-3 m-0">
				<input type="hidden" name="option" value="<?php echo esc_attr( $btn['id'] ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( mb_strtolower( get_class( $this ) ) ); ?>">
				<input type="hidden" name="n2nonce" value="<?php echo esc_attr( $n2nonce ); ?>">
				<?php foreach ( $includes as $include ) : ?>
					<input type="hidden" name="include[]" value="<?php echo esc_attr( $include ); ?>">
				<?php endforeach; ?>
				<button id="<?php echo $btn['id']; ?>" class="btn px-5 <?php echo $btn['class']; ?>"><?php echo $btn['text']; ?></button>
			</form method="post" class="p-3 m-0 sticky-top justify-content-evenly d-flex bg-dark">
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		$this->display_error();
		exit;
	}

	/**
	 * スプレットシートに値貼り付け用
	 */
	private function spreadsheet() {
		$this->settings['header_string'] = $this->settings['header_string'] ?: '';// ヘッダーを強制
		// タブを強制
		$this->settings['header_string'] = str_replace( $this->settings['delimiter'], "\t", $this->settings['header_string'] );
		$this->settings['delimiter']     = "\t";
		$this->set_header_string();
		$this->set_data_string();
		// データを文字列に
		$this->display_error();
		?>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
		<title>スプレットシートに値貼り付け</title>
		<?php
		echo "<pre>";
		echo var_dump($this);
		echo "</pre>";
		?>
		<textarea class="form-control"style="height:100%"><?php echo esc_html( $this->settings['header_string'] . $this->data['string'] ); ?></textarea>
		<?php
		exit;
	}

	/**
	 * デバッグ用
	 */
	private function json() {
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $this->data, JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * デバッグ用
	 */
	private function debug() {
		$this->set_header_string();
		$this->set_data_string();
		$time = microtime( true ) - $this->data['time']['start'];
		echo '<style>body{margin:0;}</style><pre style="background: black;color: white;">';
		print_r( '実行結果: ' . $time . '秒 ' ); // 実行時間を出力する
		print_r( $this->settings );
		print_r( $this->data );
		exit;
	}

	/**
	 * メモリ使用量のログ
	 *
	 * @param string $name 名前
	 */
	private function set_memory_usage( $name ) {
		$memory = array(
			'usage' => ceil( memory_get_usage() / ( 1024 * 1024 ) ) . 'MB',
			'peak'  => ceil( memory_get_peak_usage() / ( 1024 * 1024 ) ) . 'MB',
		);
		// 記録
		if ( $name ) {
			$this->data['memory_usage'][ $name ] = $memory;
		} else {
			$this->data['memory_usage'][] = $memory;
		}
	}
}
