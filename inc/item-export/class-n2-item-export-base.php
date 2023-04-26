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
	 * ファイル名
	 *
	 * @var string
	 */
	public $name = 'n2_export_base.tsv';

	/**
	 * デリミタ
	 *
	 * @var string
	 */
	public $delimiter = "\t";

	/**
	 * 文字コード
	 *
	 * @var string
	 */
	public $charset = 'utf-8';

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
		// typeに応じたエクスポート
		$this->{$get['type']}( $this->create() );
	}

	/**
	 * データの作成
	 */
	public function create() {
		$args = array(
			'data' => array(),
		);
		$ids  = get_posts( 'fields=ids&post_status=any&numberposts=10' );
		foreach ( $ids as $id ) {
			$args['data'][ $id ] = get_post_meta( $id );
			foreach ( $args['data'][ $id ] as $key => $value ) {
				$args['data'][ $id ][ $key ] = get_post_meta( $id, $key, true );
			}
		}
		return $args;
	}

	/**
	 * 配列をCSV・TSVに変換
	 *
	 * @param array $args パラメータ
	 * @return string csv or tsv
	 */
	public function array2csv( $args ) {
		$defaults = array(
			'header'    => array(),
			'items'     => array(),
			'delimitor' => $this->delimitor,
			'charset'   => $this->charset,
		);
		// デフォルト値を引数で上書き
		$args = wp_parse_args( $args, $defaults );
		// 文字列生成
		$str = '';
		return $str;
	}

	/**
	 * ダウンロード
	 *
	 * @param array $args パラメータ
	 */
	public function download( $args ) {
		$defaults = array(
			'name' => $this->name,
			'data' => '',
		);
		// デフォルト値を引数で上書き
		$args = wp_parse_args( $args, $defaults );
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename={$args['name']}" );
		echo htmlspecialchars_decode( $args['data'] );
		exit;
	}

	/**
	 * ビュー
	 *
	 * @param array $args パラメータ
	 */
	public function view( $args ) {
		$defaults = array(
			'data' => '',
		);
		// デフォルト値を引数で上書き
		$args = wp_parse_args( $args, $defaults );
		echo "<pre>{$args['data']}</pre>";
		exit;
	}

	/**
	 * デバッグ
	 *
	 * @param array $args パラメータ
	 */
	public function debug( $args ) {
		$defaults = array(
			'data' => '',
		);
		// デフォルト値を引数で上書き
		$args = wp_parse_args( $args, $defaults );
		echo '<pre>';
		print_r( $args );
		echo '</pre>';
		exit;
	}
}

