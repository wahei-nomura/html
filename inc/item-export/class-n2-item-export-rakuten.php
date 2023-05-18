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
		// 説明文
		$n2values['説明文'] .= $n2->portal_common_discription ? "\n\n{$n2->portal_common_discription}" : '';
		$n2values['説明文'] .= $n2values['検索キーワード'] ? "\n\n{$n2values['検索キーワード']}" : '';

		// 内容量
		$n2values['内容量・規格等'] = array(
			$n2values['内容量・規格等'],
			$n2values['原料原産地'] ? "【原料原産地】\n{$n2values['原料原産地']}" : '',
			$n2values['加工地'] ? "【加工地】\n{$n2values['加工地']}" : '',
		);// implode用の配列作成
		$n2values['内容量・規格等'] = implode( "\n\n", array_filter( $n2values['内容量・規格等'] ) );// 空要素削除して連結

		// アレルゲン
		$n2values['アレルゲン'] = array_column( (array) $n2values['アレルゲン'], 'label' );// ラベルだけにする
		$n2values['アレルゲン'] = preg_replace( '/（.*?）/', '', $n2values['アレルゲン'] );// 不純物（カッコの部分）を削除

		// 賞味期限・消費期限
		$n2values['消費期限'] = array(
			$n2values['賞味期限'] ? "【賞味期限】\n{$n2values['賞味期限']}" : '',
			$n2values['消費期限'],
		);// implode用の配列作成
		$n2values['消費期限'] = implode( "\n\n【消費期限】\n", array_filter( $n2values['消費期限'] ) );// 空要素削除して連結

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
			preg_match( '/^送料$/', $val )  => $n2values['送料'],
			preg_match( '/^のし対応$/', $val )  =>  ( '有り' === $n2values['のし対応'] ) ? 1 : '',
			preg_match( '/^商品画像URL$/', $val )  => $this->get_img_urls( $n2values['id'] ),
			preg_match( '/^PC用商品説明文$/', $val )  => $this->pc_item_description( $n2values['id'] ),
			preg_match( '/^PC用販売説明文$/', $val )  => $this->pc_item_description( $n2values['id'] ),
			// preg_match( '/^スマートフォン用商品説明文$/', $val )  => $this->sp_item_description( $n2values['id'] ),
			// preg_match( '/^在庫タイプ$/', $val )  => $n2values['返礼品コード'],// 1,000文字以内
			// preg_match( '/^在庫数$/', $val )  => $n2values['返礼品コード'],// 1,000文字以内
			// preg_match( '/^カタログID$/', $val )  => $n2values['返礼品コード'],// 1,000文字以内
			// preg_match( '/^カタログIDなしの理由$/', $val )  => $n2values['返礼品コード'],// 1,000文字以内
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
	 * @param int    $post_id id
	 * @param string $return_type 戻り値判定用(string|html|array)
	 * @return string|array 楽天の画像URLを(文字列|配列)で取得する
	 */
	public function get_img_urls( $post_id, $return_type = 'string' ) {
		global $n2;
		// get_post_metaのkey
		$post_keys = array(
			'返礼品コード',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		$item_num_low   = trim( mb_strtolower( $post_meta_list['返礼品コード'] ) );
		$img_dir        = rtrim( $n2->rakuten['img_dir'], '/' );

		// GOLD（ne.jp）とキャビネット（co.jp）を判定してキャビネットは事業者コードディレクトリを追加
		preg_match( '/^[a-z]{2,3}/', $item_num_low, $m );// 事業者コード
		if ( ! preg_match( '/ne\.jp/', $img_dir ) && array_key_exists( 0, $m ) ) {
			$img_dir .= "/{$m[0]}";// キャビネットの場合事業者コード追加
		}

		$result = array();
		for ( $i = 0; $i < 15; ++$i ) {
			$img_url = "{$img_dir}/{$item_num_low}";
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
							<img src=""<?php echo $img_url; ?>"" width=""100%""><br><br>
						<?php else : ?>
							<img src=""<?php echo $img_url; ?>"" width=""100%"">
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
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_sales_description( $post_id, $return_string = true ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		// ========[html]PC用販売説明文========
		$html = function() use ( $post_meta_list, $post_id ) {
			global $n2;
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php $this->get_img_urls( $post_id, 'html' ); ?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php $this->make_itemtable( $post_id, false ); ?><br><br>
			<?php
				echo $n2->portal_common_discription
					. apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, 'PC用販売説明文' )
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
				?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のPC用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のPC用商品説明文を(文字列|HTML出力)する
	 */
	public function pc_item_description( $post_id, $return_string = true ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天SPAカテゴリー',
			'原料原産地',
			'加工地',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// ========[html]PC用商品説明文========
		$html = function() use ( $post_meta_list, $post_id ) {
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php echo $formatter( '内容量・規格等' ); ?><br>
			<?php if ( $post_meta_list['賞味期限'] ) : ?>
				<br>【賞味期限】<br><?php echo $formatter( '賞味期限' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['消費期限'] ) : ?>
				<br>【消費期限】<br><?php echo $formatter( '消費期限' ); ?><br>
			<?php endif; ?>
			<?php echo apply_filters( 'n2_item_export_rakuten_porcelain_text', '', $post_id, '対応機器' ); ?>
			<?php if ( $post_meta_list['原料原産地'] ) : ?>
				<br><br>【原料原産地】<br>
				<?php echo $formatter( '原料原産地' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['加工地'] ) : ?>
				<br><br>【加工地】<br>
				<?php echo $formatter( '加工地' ); ?><br>
			<?php endif; ?>
			<?php if ( $post_meta_list['検索キーワード'] ) : ?>
				<br><br><?php echo $formatter( '検索キーワード' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo $formatter( '楽天SPAカテゴリー' ); ?>
			<?php endif; ?>
			<?php
		};

		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * 楽天のSP用商品説明文
	 *
	 * @param int  $post_id id
	 * @param bool $return_string 戻り値判定用(基本は文字列|HTML)
	 * @return string|void 楽天のSP用商品説明文を(文字列|HTML出力)する
	 */
	public function sp_item_description( $post_id, $return_string = true ) {
		// get_post_metaのkey
		$post_keys = array(
			'説明文',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'検索キーワード',
			'楽天SPAカテゴリー',
			'原料原産地',
			'加工地',
		);
		// post_meta格納用
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );

		// ========[html]SP用商品説明文========
		$html = function() use ( $post_id, $post_meta_list, ) {
			global $n2;
			$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
			?>
			<?php $this->get_img_urls( $post_id, 'html' ); ?>
			<?php echo $formatter( '説明文' ); ?><br><br>
			<?php $this->make_itemtable( $post_id, false ); ?>
			<?php if ( $post_meta_list['検索キーワード'] ) : ?>
				<br><br><?php echo $formatter( '検索キーワード' ); ?>
			<?php endif; ?>
			<?php if ( $post_meta_list['楽天SPAカテゴリー'] ) : ?>
				<br><br><?php echo $formatter( '楽天SPAカテゴリー' ); ?>
			<?php endif ?>
			<?php
				echo $n2->portal_common_discription
					. str_replace( '\"', '""', $n2->rakuten['html'] ?? '' );
			?>
			<?php
		};
		// ========戻り値判定========
		// 文字列を返却
		if ( $return_string ) {
			return N2_Functions::html2str( $html );
		}
		// html出力
		$html();
	}
	/**
	 * アレルギ表示
	 *
	 * @param string $post_id id
	 * @param string $type type
	 *
	 * @return string
	 */
	public static function allergy_display( $post_id, $type = '' ) {
		$post_meta_list            = get_post_meta( $post_id, '', true );
		$post_meta_list['アレルゲン']   = unserialize( $post_meta_list['アレルゲン'][0] );
		$post_meta_list['アレルゲン注釈'] = $post_meta_list['アレルゲン注釈'][0];
		$allergens                 = array();
		$is_food                   = in_array( '食品', get_post_meta( $post_id, '商品タイプ', true ), true );
		$has_allergy               = in_array( 'アレルギー品目あり', get_post_meta( $post_id, 'アレルギー有無確認', true ) ?: array(), true );
		foreach ( $post_meta_list['アレルゲン'] as $v ) {
			if ( is_numeric( $v['value'] ) ) {
				$allergens = array( ...$allergens, $v['label'] );
			}
		}
		$allergens                 = implode( '・', $allergens );
		$post_meta_list['アレルゲン注釈'] = $post_meta_list['アレルゲン注釈'] ? '<br>※' . $post_meta_list['アレルゲン注釈'] : '';
		$result                    = '';
		switch ( true ) {
			case ! $is_food && 'print' === $type:
				$result = 'アレルギー表示しない';
				break;
			case ! $is_food:
				break;
			case ! $has_allergy:
				$result = 'アレルギーなし食品';
				break;
			case '' !== $allergens || '' !== $post_meta_list['アレルゲン注釈']:
				$allergens = $allergens ?: 'なし';
				$result    = "含んでいる品目：{$allergens}{$post_meta_list['アレルゲン注釈']}";
				break;
		}
		return $result;
	}

	/**
	 * 商品説明テーブル
	 *
	 * @param int  $post_id post_id
	 * @param bool $return_string 戻り値を文字列で返す
	 *
	 * @return string 商品説明テーブル
	 */
	public function make_itemtable( $post_id, $return_string = true ) {
		$config         = $this->get_config( 'item_csv' );
		$post_keys      = array(
			'表示名称',
			'略称',
			'内容量・規格等',
			'賞味期限',
			'消費期限',
			'発送方法',
			'配送期間',
			'提供事業者名',
			'アレルゲン',
			'アレルゲン注釈',
			'アレルギー品目あり',
			'原料原産地',
			'加工地',
		);
		$post_meta_list = N2_Functions::get_post_meta_multiple( $post_id, $post_keys );
		// アレルギー表示
		$allergy_display_str = $this->allergy_display( $post_id );

		$formatter = fn( $post_key ) => nl2br( N2_Functions::special_str_convert( $post_meta_list[ $post_key ] ) );
		$trs       = array(
			'名称'      => array(
				'td' => ( $formatter( '表示名称' ) ?: $formatter( '略称' ) ?: N2_Functions::special_str_convert( get_the_title( $post_id ) ) ),
			),
			'内容量'     => array(
				'td' => $formatter( '内容量・規格等' ),
			),
			'原料原産地'   => array(
				'td'        => $formatter( '原料原産地' ),
				'condition' => $post_meta_list['原料原産地'],
			),
			'加工地'     => array(
				'td'        => $formatter( '加工地' ),
				'condition' => $post_meta_list['加工地'],
			),
			'賞味期限'    => array(
				'td'        => $formatter( '賞味期限' ),
				'condition' => $post_meta_list['賞味期限'],
			),
			'消費期限'    => array(
				'td'        => $formatter( '消費期限' ),
				'condition' => $post_meta_list['消費期限'],
			),
			'アレルギー表示' => array(
				'td'        => $allergy_display_str,
				'condition' => $allergy_display_str,
			),
			'配送方法'    => array(
				'td' => $formatter( '発送方法' ),
			),
			'配送期日'    => array(
				'td' => $formatter( '配送期間' ),
			),
			'提供事業者'   => array(
				'td'        => $post_meta_list['提供事業者名']
				?: preg_replace(
					'/\（.+?\）/',
					'',
					(
					get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) )
					?: get_the_author_meta( 'first_name', get_post_field( 'post_author', $post_id ) )
					)
				),
				'condition' => '記載しない' !== get_the_author_meta( 'portal', get_post_field( 'post_author', $post_id ) ),
			),
		);

		// 内容を追加、または上書きするためのフック
		$trs = apply_filters( 'n2_item_export_make_itemtable', $trs, $post_id );

		// ========[html]商品説明テーブル========
		$itemtable_html = function() use ( $trs ) {
			?>
			<!-- 商品説明テーブル -->
			<p><b><font size=""5"">商品説明</font></b></p><hr noshade color=""black""><br>
			<table border=""1"" width=""100%"" cellspacing=""0"" cellpadding=""10"" bordercolor=""black"">
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
