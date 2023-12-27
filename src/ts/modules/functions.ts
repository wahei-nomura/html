/**
 * 複数ファイルで使いまわしたい変数や関数があればここに
 *
 * 読み込むファイルではimport { prefix, neoNengPath, ajaxUrl } from '../n2-functions/index'を記載
 */

// クラス名にプレフィックスを付けてるところがある
export const prefix = "neo-neng";

// PHPからこのテーマのディレクトリパスを受けとっている
export const neoNengPath = (window): string => {
	return window.tmp_path.tmp_url;
};

// wp_ajax用のパスを受け取っている
export const ajaxUrl = (window): string => {
	return window.tmp_path.ajax_url;
};

// PHPからWordpressのトップパスを受け取っている
export const homeUrl = (window): string => {
	return window.tmp_path.home_url;
};

/**
 * Jsで値渡しするときの関数
 * @param value コピー対象のブツ
 * @param toString オブジェクト以外の型をstringに
 * @returns コピーされたブツ
 */
export const copy = ( value, toString = false ) => {
	return JSON.parse( JSON.stringify( value, (k,v) => toString && typeof v != 'object' ? v.toString() : v ) );
} 