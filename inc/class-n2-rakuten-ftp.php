<?php
/**
 * class-n2-rakuten-ftp.php
 * 全体的に超絶突貫なので、後で作り直す
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_FTP' ) ) {
	new N2_Rakuten_FTP();
	return;
}

/**
 * 楽天FTPページ
 */
class N2_Rakuten_FTP {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_n2_rakuten_ftp', array( $this, 'ftp' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'wp_ajax_n2_upload_to_rakuten', array( $this, 'upload_to_rakuten' ) );
	}

	/**
	 * N2 SYNC　メニューの追加
	 */
	public function add_menu() {
		global $n2;
		if ( isset( $n2->settings['楽天'] ) ) {
			add_menu_page( '楽天FTP', '楽天FTP', 'ss_crew', 'n2_rakuten_ftp_upload', array( $this, 'ftp_ui' ), 'dashicons-admin-site-alt3' );
			add_submenu_page( 'n2_rakuten_ftp_upload', '楽天エラーログ', '楽天エラーログ', 'ss_crew', 'n2_rakuten_ftp_error_log', array( $this, 'ftp_ui' ) );
		}
	}

	/**
	 * FTP UI
	 */
	public function ftp_ui() {
		$template = str_replace( array('n2_rakuten_ftp_','_'),	array('','-'), $_GET['page'] );
		$args = match ( $template ) {
			'error-log' => $this->error_log_args(),
			'upload' => $this->upload_args(),
			default     => null,
		};
		?>
		<div class="wrap">
			<h1>楽天FTP</h1>
			<?php get_template_part( 'template/admin-menu/sftp', $template, $args ); ?>
		</div>
		<?php
	}

	/**
	 * upload
	 */
	public function upload_args() {
		return array(
			'action' => 'n2_upload_to_rakuten',
			'file'   => 'ftp_files[]',
			'radio'  => array(
				'ftp_img' => '商品画像',
				'ftp_file' => '商品CSV',
			),
		);
	}

	/**
	 * 楽天への転送機能（超突貫）
	 *
	 * @return void
	 */
	public function upload_to_rakuten() {
		header( 'Content-Type: application/json; charset=utf-8' );
		// 各種設定読み込み
		global $n2;
		$rakuten = $n2->settings['楽天'];
		// print_r($rakuten);
		// setlocale(LC_ALL, 'ja_JP.UTF-8');
		$error_options = array();

		if ( ! isset( $rakuten['FTP']['user'] ) || ! $rakuten['FTP']['user'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > FTPユーザー' );
		}
		if ( ! isset( $rakuten['FTP']['pass'] ) || ! $rakuten['FTP']['pass'] ) {
			$error_options = array( ...$error_options, '楽天セットアップ > FTPパスワード' );
		}
		if ( $error_options ) {
			// エラー出力して終了
			echo '楽天セットアップが完了していません。';
			exit();
			die();
		}
		$ftp_user = $rakuten['FTP']['user'];
		$ftp_pass = $rakuten['FTP']['pass'];

		extract( $_POST );

		if ( 'ftp_img' === $judge ) {
			setlocale( LC_ALL, 'ja_JP.UTF-8' );
			extract( $_FILES[ 'ftp_files' ] ); // $name $type $tmp_name
			if ( ! empty( $tmp_name[0] ) ) {
				// テンポラリディレクトリ作成
				$tmp = wp_tempnam( __CLASS__, dirname( __DIR__ ) . '/' );
				unlink( $tmp );
				mkdir( $tmp );
				$img_dir = rtrim( $rakuten['商品画像ディレクトリ'], '/' ) . '/';
				// GOLD（ne.jp）とキャビネット（co.jp）を判定して接続先を変更
				$server = preg_match( '/ne\.jp/', $img_dir ) ? 'ftp_server' : 'upload_server';
				$port = "{$server}_port";
				$set_server = $rakuten['FTP'][ $server ];
				$set_port   = $rakuten['FTP'][ $port ];
				$conn_id    = ftp_connect( $set_server, $set_port ); // 可変変数
				$login = ftp_login( $conn_id, $ftp_user, $ftp_pass );
				// ログインできない場合は末尾を２に変更
				if ( ! $login ) {
					$login = ftp_login( $conn_id, $ftp_user, substr( $ftp_pass, 0, 7 ) . '2' );
				}
				if ( $login ) {
					ftp_pasv( $conn_id, true );
					foreach ( $tmp_name as $k => $file ) {
						// 画像圧縮処理
						$quality = isset( $quality ) ? $quality : 50;
						move_uploaded_file( $file, "{$tmp}/{$name[$k]}" );
						exec( "mogrify -quality {$quality} {$tmp}/{$name[$k]}" );

						// jpg以外処理中止
						if ( strpos( $name[$k], '.jpg' ) !== false ) {

							// GOLDの場合
							if ( preg_match( '/ne\.jp/', $img_dir ) ) {
								preg_match( '/https:\/\/([^\/]+\/){3}/u', $img_dir, $match );
								$remote_file = str_replace( $match[0], '', $img_dir ) . $name[$k];
								echo $remote_file;
								if ( ftp_put( $conn_id, $remote_file, "{$tmp}/{$name[$k]}", FTP_ASCII ) ) {
									echo "楽天GOLDに転送成功 $name[$k]\n";
								} else {
									echo "楽天GOLDに転送失敗 $name[$k]\n";
								}
							}
							// キャビネットの場合
							else {
								// $img_dir からキャビネットのディレクトリ構造を作成
								$remote_dir = preg_replace( '/^.*cabinet/', 'cabinet/images', $img_dir );
								preg_match( '/^([0-9]{0,2})([a-z]{2,4})[0-9]{2,3}[-]*[0-9]*\.jpg/', $name[$k], $m );
								if ( $m[1] || $m[2] ) { // 商品画像の場合
									if ( ftp_mkdir( $conn_id, $remote_dir ) ) {
										echo "{$remote_dir}を作成\n";
									};
									$remote_dir .= $m[1] . $m[2];
									if ( ftp_mkdir( $conn_id, $remote_dir ) ) {
										echo "{$remote_dir}を作成\n";
									};
									$remote_file = "{$remote_dir}/{$name[$k]}";
									if ( ftp_put( $conn_id, $remote_file, "{$tmp}/{$name[$k]}", FTP_ASCII ) ) {
										echo "キャビネットに転送成功 $name[$k]\n";
									} else {
										echo "キャビネットに転送失敗 $name[$k]\n";
									}
								} else {
									echo 'ファイルが違います';
								}
							}
						} else {
							echo 'ファイルが違います！';
						}
					}
					ftp_close( $conn_id );
				} else {
					echo 'パスワードが違います';
					}
				exec( "rm -Rf {$tmp}" );
			} else {
				echo 'ファイルをセットしてください。';
			}
		}
		// 楽天Uにデータ転送
		elseif ( 'ftp_file' === $judge ) {
			setlocale( LC_ALL, 'ja_JP.UTF-8' );
			extract( $_FILES['ftp_files'] );
			if ( ! empty( $tmp_name[0] ) ) {
				$upload_server = $rakuten['FTP']['upload_server'];
				$upload_server_port = $rakuten['FTP']['upload_server_port'];
				$conn_id = ftp_connect( $upload_server, $upload_server_port );
				$login = ftp_login( $conn_id, $ftp_user, $ftp_pass );
				if ( ! $login ) {
					$login = ftp_login( $conn_id, $ftp_user, substr( $ftp_pass, 0, 7 ) . '2' );
				} // ログインできない場合は末尾を２に変更
				if ( $login ) {
					ftp_pasv( $conn_id, true );
					foreach ( $tmp_name as $k => $file ) {
						if ( strpos( $name[$k], '.csv' ) !== false ) {
							$remote_file = 'ritem/batch/' . $name[$k];
							if ( ftp_put( $conn_id, $remote_file, $file, FTP_ASCII ) ) {
								echo "転送成功 $name[$k]\n";
							} else {
								echo "転送失敗 $name[$k]\n";
							}
						} else {
							echo 'ファイルが違います！';
						}
					}
					ftp_close( $conn_id );
				} else {
					echo 'パスワードが違います';
				}
			} else {
				echo 'ファイルをセットしてください。';
			}
		}
		exit();
		die();
	}

	/**
	 * エラーログ変数
	 */
	public function error_log_args() {
		global $n2;
		// テンプレート用
		$args = array();

		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpext.php';
		$opt = array(
			'hostname' => $n2->settings['楽天']['FTP']['upload_server'],
			'username' => $n2->settings['楽天']['FTP']['ftp_user'],
			'password' => $n2->settings['楽天']['FTP']['ftp_pass'],
		);
		$ftp = new WP_Filesystem_FTPext( $opt );
		$args['connect'] = $ftp->connect();
		if ( ! $args['connect'] ) {
			$opt['password'] = rtrim( $opt['password'], 1 ) .'2';
			$ftp             = new WP_Filesystem_FTPext( $opt );
			$args['connect'] = $ftp->connect();
		}
		if ( ! $args['connect'] ) {
			return $args;
		}
		$args['dir'] = 'ritem/logs';
		$logs = $ftp->dirlist( $args['dir'] );
		$logs = array_reverse( $logs );
		$args['logs'] = array_map(function( $log ) use( $args,$ftp ) {
			$contents = $ftp->get_contents( "{$args['dir']}/{$log['name']}" );
			$contents = htmlspecialchars( mb_convert_encoding( $contents, 'utf-8', 'sjis' ) );
			return array(
				'name' => $log['name'],
				'time' => "{$log['year']} {$log['month']} {$log['day']}",
				'contents' => $contents,
			);
		},$logs);
		return $args;
	}

	/**
	 * 楽天FTP
	 * パラメータで機能を分離
	 */
	public function ftp() {
		$params = $_GET;
		$fn     = $params['fn'] ?: false;
		if ( ! $fn ) {
			echo 'パラメータ不足です。';
			exit;
		}
		global $n2;
		WP_Filesystem();
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php';
		$opt = array(
			'hostname' => $n2->settings['楽天']['FTP']['upload_server'],
			'username' => $n2->settings['楽天']['FTP']['ftp_user'],
			'password' => $n2->settings['楽天']['FTP']['ftp_pass'],
		);
		$ftp = new WP_Filesystem_ftpsockets( $opt );
		$ftp->connect();
		$this->$fn( $ftp, $params );
		exit;
	}

	/**
	 * ディレクトリリストを表示
	 *
	 * @param object $ftp 接続FTPオブジェクト
	 * @param array  $params パラメータ
	 */
	private function ftp_dirlist( $ftp, $params ) {
		$path = $params['path'] ?: false;
		if ( ! $path ) {
			echo 'パラメータ「path」が不足しています。';
			exit;
		}
		$dirlist = $ftp->dirlist( $path );
		$type    = $params['type'] ?: 'json';
		switch ( $type ) {
			case 'json':
				header( 'Content-Type: application/json; charset=utf-8' );
				echo wp_json_encode( $dirlist );
				break;
			case 'array':
				echo '<pre>';
				print_r( $dirlist );
				break;
		}
		exit;
	}

	private function ftp_view_contents( $ftp ) {
		echo 'ftp_view_contents';
		exit;
	}

	private function ftp_download( $ftp ) {
		echo 'ftp_download';
		exit;
	}

}
