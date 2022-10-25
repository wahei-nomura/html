import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * フロントページ検索
	 * 
	================================================================== */
	jQuery(function ($) {
		// 事業者絞り込みコンボボックス
		$('#jigyousya-list-tag').on('change', e => {
			const id: number = $(`#jigyousya-list option[value="${$(e.target).val()}"]`).data('id')
			$('#jigyousya-value').val(id)
		})
	})
}