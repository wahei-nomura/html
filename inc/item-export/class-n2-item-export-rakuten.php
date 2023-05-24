<?php
/**
 * 楽天の商品エクスポート専用
 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
 * class-n2-item-export-rakuten.php
 * デバッグモード：admin-ajax.php?action=n2_item_export_rakuten&mode=debug
 *
 * @package neoneng
 */

if ( class_exists( 'N2_Item_Export_Rakuten' ) ) {
	new N2_Item_Export_Rakuten();
	return;
}

/**
 * N2_Item_Export_Base
 */
class N2_Item_Export_Rakuten extends N2_Item_Export_Base {

	/**
	 * 設定
	 *
	 * @var array
	 */
	public $settings = array(
		'filename'      => 'n2_export_rakuten.csv',
		'delimiter'     => ',',
		'charset'       => 'sjis',
		'header_string' => '', // 基本は自動設定、falseでヘッダー文字列無し
	);

	/**
	 * 楽天CSVヘッダーを取得
	 */
	protected function set_header() {
		global $n2;
		// CSVヘッダー本体
		$csv_header = trim( $n2->rakuten['item_csv'] );
		// CSVヘッダー配列化
		$this->data['header'] = explode( "\t", $csv_header );
		/**
		 * [hook] n2_item_export_rakuten_set_header
		 */
		$this->data['header'] = apply_filters( mb_strtolower( get_class( $this ) ) . '_set_header', $this->data['header'] );
	}

	/**
	 * データのマッピング（正しい値かどうかここでチェックする）
	 * 楽天CSVの仕様：https://steamship.docbase.io/posts/2774108
	 *
	 * @param string $val 項目名
	 * @param string $index インデックス
	 * @param array  $n2values n2dataのループ中の値
	 */
	protected function walk_values( &$val, $index, $n2values ) {
		global $n2;
		// アレルゲン
		$n2values['アレルゲン'] = array_column( (array) $n2values['アレルゲン'], 'label' );// ラベルだけにする
		$n2values['アレルゲン'] = preg_replace( '/（.*?）/', '', $n2values['アレルゲン'] );// 不純物（カッコの部分）を削除

		// 自治体のタグIDと返礼品タグIDをいい感じに結合する
		$n2values['タグID'] = $n2->rakuten['tag_id'] . '/' . $n2values['タグID'];
		$n2values['タグID'] = implode( '/', array_filter( explode( '/', $n2values['タグID'] ) ) );

		// preg_matchで判定
		$data = match ( 1 ) {
			preg_match( '/^コントロールカラム$/', $val )  => 'n',
			preg_match( '/^商品管理番号（商品URL）$/', $val )  => mb_strtolower( $n2values['返礼品コード'] ),
			preg_match( '/^商品番号$/', $val )  => $n2values['返礼品コード'],
			preg_match( '/^全商品ディレクトリID$/', $val ) => $n2values['全商品ディレクトリID'],
			preg_match( '/^タグID$/', $val ) => $n2values['タグID'],
			preg_match( '/^PC用キャッチコピー$/', $val )  => $n2values['キャッチコピー'],
			preg_match( '/^モバイル用キャッチコピー$/', $val )  => $n2values['キャッチコピー'],
			preg_match( '/商品名$/', $val )  => '【ふるさと納税】' . $n2values['タイトル'] . '[' . $n2values['返礼品コード'] . ']',
			preg_match( '/^販売価格$/', $val )  => $n2values['寄附金額'],
			preg_match( '/^送料$/', $val )  => 1,
			preg_match( '/^のし対応$/', $val )  =>  ( '有り' === $n2values['のし対応'] ) ? 1 : '',
			preg_match( '/^PC用商品説明文$/', $val )  => $this->pc_item_description( $n2values ),
			preg_match( '/^スマートフォン用商品説明文$/', $val )  => $this->sp_item_description( $n2values ),
			preg_match( '/^PC用販売説明文$/', $val )  => $this->pc_sales_description( $n2values ),
			preg_match( '/^商品画像URL$/', $val )  => $this->get_img_urls( $n2values ),
			preg_match( '/^代引料$/', $val )  => 1,
			preg_match( '/^在庫タイプ$/', $val )  => 1,
			preg_match( '/^在庫数$/', $val )  => 0,
			// preg_match( '/^カタログID$/', $val )  => "", // 初期値空白なので記入必要なし
			preg_match( '/^カタログIDなしの理由$/', $val )  => 1,
			default => '',
		};
		/**
		 * [hook] n2_item_export_rakuten_walk_values
		 */
		$val = apply_filters( mb_strtolower( get_class( $this ) ) . '_walk_values', $data, $val, $n2values );
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
		// 必須漏れエラー
		if ( preg_match( '/（必須）|必要寄付金額/', $name ) && '' === $value ) {
			$this->add_error( $n2values['id'], "「{$name}」がありません。" );
		}
		// 文字数制限エラー
		$len       = mb_strlen( $value );// $valueの文字数
		$maxlength = array(
			40   => 'キャッチコピー',
			64   => 'サイト表示事業者名',
			100  => '地場産品類型番号',
			200  => '（必須）お礼の品名',
			1000 => '^説明$|^容量$|^申込期日$|^発送期日$|アレルギー特記事項|消費期限',
		);
		foreach ( $maxlength as $max => $pattern ) {
			if ( preg_match( "/{$pattern}/", $name ) && $len > $max ) {
				$over = $len - $max;
				$this->add_error( $n2values['id'], "<div title='{$value}'>「{$name}」の文字数が{$over}文字多いです。</div>" );
			}
		}
		return $value;
	}
	/**
	 * 文字列の置換
	 *
	 * @param string $str 文字列
	 * @return string $str 置換後の文字列
	 */
	protected function special_str_convert( $str ) {
		global $n2;
		$str = str_replace( array_keys( $n2->special_str_convert ), array_values( $n2->special_str_convert ), $str );
		/**
		 * [hook] n2_item_export_rakuten_special_str_convert
		 */
		$str = apply_filters( mb_strtolower( get_class( $this ) ) . '_special_str_convert', $str );
		return $str;
	}
	/**
	 * 楽天の画像URLを取得
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param string $return_type 戻り値判定用(string|html|array)
	 * @return string|array 楽天の画像URLを(文字列|配列)で取得する
	 */
	public function get_img_urls( $n2values, $return_type = 'string' ) {
		global $n2;
		$img_dir      = rtrim( $n2->rakuten['img_dir'], '/' );
		$gift_code = mb_strtolower( $n2values['返礼品コード'] );
		$business_code = mb_strtolower( $n2values['事業者コード'] );
		// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
		if ( ! preg_match( '/ne\.jp/', $img_dir ) ) {
			$img_dir .= "/{$business_code}";// キャビネットの場合事業者コード追加
		}

		$result = array();
		for ( $i = 0; $i < 15; ++$i ) {
			$img_url = "{$img_dir}/{$gift_code}";
			if ( 0 === $i ) {
				$img_url .= '.jpg';
			} else {
				$img_url .= "-{$i}.jpg";
			}
			$response = wp_remote_get( $img_url );
			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
				$result[ $i ] = $img_url;
			}
		}
		// ========戻り値判定========
		switch ( $return_type ) {
			// 文字列を返却
			case 'string':
				return implode( ' ', $result );
			case 'html':
				$html = function() use ( $result ) {
					?>
					<?php foreach ( $result  as $index => $img_url ) : ?>
						<?php if ( array_key_last( $result ) === $index ) : ?>
							<img src="<?php echo $img_url; ?>" width="100%"><br><br>
						<?php else : ?>
							<img src="<?php echo $img_url; ?>" width="100%">
						<?php endif; ?>
					<?php endforeach; ?>
					<?php
				};
				$html();
				break;
			// 配列出力
			default:
				return $result;
		}
	}
	/**
	 * 楽天のPC用販売説明文
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_sales_description( $n2values ) {
		// ========[html]PC用販売説明文========
		$html = function() use ( $n2values ) {
			global $n2;
			?>
			<?php $this->get_img_urls( $n2values, 'html' ); ?>
			<?php echo nl2br($n2values['説明文']); ?><br><br>
			<?php $this->make_itemtable( $n2values, false ); ?><br><br>
			<?php
				echo $n2->portal_common_discription
					. apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $n2values['id'], 'PC用販売説明文' )
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
				?>
			<?php
		};
		// html出力
		$html();
	}
	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_item_description( $n2values ) {

		// ========[html]PC用商品説明文========
		$html = function() use ( $n2values ) {
			?>
			<?php echo nl2br($n2values['説明文']); ?><br><br>
			<?php echo nl2br($n2values['内容量・規格等']); ?><br>
			<?php if ( $n2values['賞味期限'] ) : ?>
				<br>【賞味期限】<br><?php echo nl2br($n2values['賞味期限']); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['消費期限'] ) : ?>
				<br>【消費期限】<br><?php echo nl2br($n2values['消費期限']); ?><br>
			<?php endif; ?>
			<?php echo apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $n2values['id'], '対応機器' ); ?>
			<?php if ( $n2values['原料原産地'] ) : ?>
				<br><br>【原料原産地】<br>
				<?php echo nl2br($n2values['原料原産地']); ?>
			<?php endif; ?>
			<?php if ( $n2values['加工地'] ) : ?>
				<br><br>【加工地】<br>
				<?php echo nl2br($n2values['加工地']); ?><br>
			<?php endif; ?>
			<?php if ( $n2values['検索キーワード'] ) : ?>
				<br><br><?php echo nl2br($n2values['検索キーワード']); ?>
			<?php endif; ?>
			<?php if ( $n2values['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo nl2br($n2values['楽天SPAカテゴリー']); ?>
			<?php endif; ?>
			<?php
		};

		// html出力
		$html();
	}
	/**
	 * 楽天のSP用商品説明文
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のSP用商品説明文を(文字列|HTML出力)する
	 */
	public function sp_item_description( $n2values ) {
		// ========[html]SP用商品説明文========
		$html = function() use ( $n2values ) {
			global $n2;
			?>
			<?php $this->get_img_urls( $n2values, 'html' ); ?>
			<?php echo nl2br($n2values['説明文']); ?><br><br>
			<?php $this->make_itemtable( $n2values, false ); ?>
			<?php if ( $n2values['検索キーワード'] ) : ?>
				<br><br><?php echo nl2br($n2values['検索キーワード']); ?>
			<?php endif; ?>
			<?php if ( $n2values['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo nl2br($n2values['楽天SPAカテゴリー']); ?>
			<?php endif ?>
			<?php
				echo $n2->portal_common_discription
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
			?>
			<?php
		};
		// html出力
		$html();
	}
	/**
	 * アレルギー表示
	 *
	 * @param array  $n2values n2dataのループ中の値
	 * @param string $type type
	 *
	 * @return string
	 */
	public static function allergy_display( $n2values, $type = '' ) {
		$allergy_annotation = $n2values['アレルゲン注釈'];
		$allergens                 = $n2values['アレルゲン'];
		$allergens                 = implode( '・', $allergens );
		$not_food                  = in_array( '食品ではない', $n2values['アレルゲン'], true );
		$not_allergy               = in_array( 'アレルゲンなし食品', $n2values['アレルゲン'] ?: array(), true );
		$allergy_annotation = $allergy_annotation ? '<br>※' . $allergy_annotation : '';
		$result                    = '';
		switch ( true ) {
			case ! $not_food && 'print' === $type:
				$result = 'アレルギー表示しない';
				break;
			case $not_food:
				break;
			case $not_allergy:
				$result = 'アレルギーなし食品';
				break;
			case '' !== $allergens || '' !== $allergy_annotation:
				$allergens = $allergens ?: 'なし';
				$result    = "含んでいる品目：{$allergens}{$allergy_annotation}";
				break;
		}
		return $result;
	}

	/**
	 * 商品説明テーブル
	 *
	 * @param int  $post_id post_id
	 * @param array  $n2values n2dataのループ中の値
	 * @param bool $return_string 戻り値を文字列で返す
	 *
	 * @return string 商品説明テーブル
	 */
	public function make_itemtable( $n2values, $return_string = true ) {
		// アレルギー表示
		$allergy_display_str = $this->allergy_display( $n2values );
		$trs       = array(
			'名称'      => array(
				'td' => $n2values['LH表示名'] ?: $n2values['タイトル'],
			),
			'内容量'     => array(
				'td' => $n2values['内容量・規格等'],
			),
			'原料原産地'   => array(
				'td'        => nl2br( $n2values['原料原産地'] ),
				'condition' => $n2values['原料原産地'],
			),
			'加工地'     => array(
				'td'        => nl2br( $n2values['加工地'] ),
				'condition' => $n2values['加工地'],
			),
			'賞味期限'    => array(
				'td'        => nl2br( $n2values['賞味期限'] ),
				'condition' => $n2values['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => nl2br( $n2values['消費期限'] ),
				'condition' => $n2values['消費期限'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => nl2br( $n2values['発送方法'] ),
			),
			'配送期日'    => array(
				'td' => nl2br( $n2values['配送期間'] ),
			),
			'提供事業者'   => array(
				'td'        => $n2values['提供事業者']
				?: preg_replace(
					'/\（.+?\）/',
					'',
					(
					get_the_author_meta( 'portal_site_display_name', get_post_field( 'post_author', $n2values['id'] ) )
					?: get_the_author_meta( 'first_name', get_post_field( 'post_author', $n2values['id'] ) )
					)
				),
				'condition' => '記載しない' !== get_the_author_meta( 'portal_site_display_name', get_post_field( 'post_author', $n2values['id'] ) ), // ポータル表示名に「記載しない」の表記があったら事業者名出力しない
			),
		);

		// 内容を追加、または上書きするためのフック
		$trs = apply_filters( 'n2_item_export_make_itemtable', $trs, $n2values['id'] );

		// ========[html]商品説明テーブル========
		$itemtable_html = function() use ( $trs ) {
			?>
			<!-- 商品説明テーブル -->
			<p><b><font size="5">商品説明</font></b></p><hr noshade color="black"><br>
			<table border="1" width="100%" cellspacing="0" cellpadding="10" bordercolor="black">
			<?php foreach ( $trs as $th => $td_params ) : ?>
				<?php if ( ! isset( $td_params['condition'] ) || $td_params['condition'] ) : ?>
				<tr><th><?php echo $th; ?></th><td><?php echo $td_params['td']; ?></td></tr>
				<?php endif; ?>
			<?php endforeach; ?>
			</table>
			<!-- /商品説明テーブル -->
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $itemtable_html );
		}
		// htmlで出力
		$itemtable_html();
	}
}
