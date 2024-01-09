/**
 * メタデータを取得
 * 
 * @param any $ jQuery
 */
export default ($: any = jQuery) => {
	// カスタムフィールドの保存
	let meta: object = {};
	const fd: any = new FormData($('#事業者用').parents('form').get(0));
	for (let d of Array.from(fd.entries()) ) {
		const name = d[0].match(/\[(.+?)\]/);
		if ( ! name ) continue;
		// 配列型：文字列型
		meta[ name[1] ] = d[0].match(/\[\]/) ? fd.getAll(d[0]) : fd.get(d[0]);
		// json型はパースしてから渡す
		try { meta[ name[1] ] = JSON.parse(meta[ name[1] ]); } catch {}
	}

	meta = set_default_meta($, meta);
	console.log('meta', meta);
	return meta;
}
/**
 * Vueで非表示になったフォームデータは空で取得（見えないのに値が残るバグが起こるため）
 * 出力されている全フォームを対象とする（出力されていないものは非対象）
 * この副作用に注意：https://github.com/steamships/neo-neng/issues/626
 **/
export const set_default_meta = ($: any = jQuery, meta: object) => {
	const n2 = window['n2'];
	// ログインしている権限で編集できるものを対象とする（隠れているものも）
	$('#事業者用').parent().children().each( (i,e) => {
		for ( let d of Object.keys(n2.custom_field[e.id]) ) {
			// 初期化
			if ( ! ( d in meta ) || '' === meta[d] ) {
				meta[d] = 'object' === typeof n2.custom_field[e.id][d]['value'] ? [] : '';
			}
		}
	});
	return meta;
}