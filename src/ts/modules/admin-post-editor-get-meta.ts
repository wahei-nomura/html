/**
 * メタデータを取得
 * 
 * @param $ jQuery
 */
export default ($: any) => {
	// カスタムフィールドの保存
	const meta = {};
	const fd:any = new FormData($('#事業者用').parents('form').get(0));
	for (let d of Array.from(fd.entries()) ) {
		const name = d[0].match(/\[(.+?)\]/);
		if ( ! name ) continue;
		// 配列型：文字列型
		meta[ name[1] ] = d[0].match(/\[\]/) ? fd.getAll(d[0]) : fd.get(d[0]);
		// json型はパースしてから渡す
		try { meta[ name[1] ] = JSON.parse(meta[ name[1] ]); } catch {}
	}
	return meta;
}