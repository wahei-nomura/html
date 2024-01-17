<?php
/**
 * class-n2-user-export-base.php
 * ユーザー情報エクスポート
 *
 * @package neoneng
 */

if ( class_exists( 'N2_User_Export_Base' ) ) {
	new N2_User_Export_Base();
	return;
}

/**
 * N2_User_Export_Base
 */
class N2_User_Export_Base {

	/**
	 * 設定（基本的に拡張で上書きする）
	 *
	 * @var array
	 */
	protected $settings = array(
		'filename'      => 'N2ユーザーデータ.csv',
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
		'params'  => array(),
		'header'  => array(),
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

		// 独自のデータをセット（基本的にこの２つを拡張）
		$this->set_header();
		$this->set_data();

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
	}

	/**
	 * ヘッダー配列の作成（基本的に拡張で上書きする）
	 */
	protected function set_header() {
		$header = ['user_login', 'user_email', 'user_pass', 'first_name', 'last_name', 'display_name', 'role'];
		/**
		 * [hook] n2_user_export_base_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $header );
	}

	/**
	 * 内容を配列で作成
	 */
	protected function set_data() {
		$users = get_users();
		// データをセットする
		foreach ( $users as $key => $values ) {
			$id = $values->data->ID;
			$meta_users = get_user_meta($id);
			$user_values['user_login'] = $values->data->user_login;
			$user_values['user_email'] = $values->data->user_pass;
			$user_values['user_pass'] = $values->data->user_email;
			$user_values['first_name'] = $meta_users['first_name'];
			$user_values['last_name'] = $meta_users['last_name'];
			$user_values['display_name'] = $values->data->display_name;
			$user_values['role'] = $values->roles;
			// ヘッダーをセット
			$data[ $id ] = $this->data['header'];
			array_walk( $data[ $id ], array( $this, 'walk_values' ), $user_values );
			$data[ $id ] = array_filter( $data[ $id ], fn( $v ) => ! is_array( $v ) || ! empty( $v ) );
			if ( ! empty( $data[ $id ] ) ) {
				$data[ $id ] = array_combine( $this->data['header'], $data[ $id ] );
			}
		}
		/**
		 * [hook] n2_user_export_base_set_data
		 */
		$data = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_data', $data );
		// エラーは排除
		$data = array_diff_key( $data, $this->data['error'] );
		$data = array_values( $data );
		// dataをセット
		$this->data['data'] = array_filter( $data );// 空は削除
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
		 * [hook] n2_user_export_base_walk_values
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
		 * [hook] n2_user_export_base_set_header_string
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
		 * [hook] n2_user_export_base_set_data_string
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
		 * [hook] n2_user_export_base_special_str_convert
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
		 * [hook] n2_user_export_base_charset
		 */
		$charset = apply_filters( mb_strtolower( get_class( $this ) ) . '_charset', $this->settings['charset'] );
		/**
		 * [hook] n2_user_export_base_filename
		 */
		$filename = apply_filters( mb_strtolower( get_class( $this ) ) . '_filename', $this->settings['filename'] );
		/**
		 * [hook] n2_user_export_base_download_add_btn
		 */
		$add_btn = apply_filters( mb_strtolower( get_class( $this ) ) . '_download_add_btn', array() );

		// POST送信されたか判定
		$str      = filter_input( INPUT_POST, 'str' );
		$option   = filter_input( INPUT_POST, 'option' );
		$n2nonce  = filter_input( INPUT_POST, 'n2nonce' );
		$includes = filter_input( INPUT_POST, 'include', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		/**
		 * [hook] n2_user_export_base_download_str
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
}

