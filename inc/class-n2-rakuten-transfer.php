<?php
/**
 * class-n2-rakuten-transfer.php
 *
 * @package neoneng
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'N2_Rakuten_Transfer' ) ) {
	new N2_Rakuten_Transfer();
	return;
}
require_once( ABSPATH . '/wp-admin/includes/file.php' );
/**
 * Rakuten_Transfer
 */
class N2_Rakuten_Transfer {
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		add_action( 'wp_ajax_transfer_rakuten', array( $this, 'transfer_rakuten' ) );
	}
	/**
	 * 楽天への転送機能
	 *
	 * @return void
	 */
	public function transfer_rakuten() {
		$opt = get_option( 'N2_Setupmenu' );
		extract( $_POST );
		if ( 'ftp_img' === $judge ) {
			setlocale( LC_ALL, 'ja_JP.UTF-8' );
			extract( $_FILES[ $judge ] ); // $name $type $tmp_name
			if ( ! empty( $tmp_name[0] ) ) {
				// テンポラリディレクトリ作成
				$tmp = wp_tempnam( __CLASS__, dirname( __DIR__ ) . '/' );
				unlink( $tmp );
				mkdir( $tmp );
				extract( $opt['rakuten'] );
				$img_dir = rtrim( $img_dir, '/' ) . '/';

				// GOLD（ne.jp）とキャビネット（co.jp）を判定して接続先を変更
				$server = preg_match( '/ne\.jp/', $img_dir ) ? 'ftp_server' : 'upload_server';
				$port = "{$server}_port";
				$conn_id = ftp_connect( $$server, $$port ); // 可変変数
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
								preg_match( '/^([a-z]{2,3})[0-9]{2,3}[-]*[0-9]*\.jpg/', $name[$k], $m );
								if ( $m[1] ) { // 商品画像の場合
									if ( ftp_mkdir( $conn_id, $remote_dir ) ) {
										echo "{$remote_dir}を作成\n";
									};
									$remote_dir .= $m[1];
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
			extract( $_FILES[$judge] );
			if ( ! empty( $tmp_name[0] ) ) {
				extract( $opt['rakuten'] );
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
	}
}
