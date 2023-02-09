/**
 * アラートを表示して強制的にさっきのページに戻す
 *
 * @param judge アラートを出す条件
 * @param message アラートメッセージ
 */
export default (judge: boolean, message: string) => {
	if ( judge ) {
		alert( message );
		history.back();
		return;
	}
};