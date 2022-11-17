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
		// 検索条件をクリア
		$('.front-search-clear').on('click', e => {
			$('.s').val('');
			$('#jigyousya-list-tag').val('');
			$('#jigyousya-value').val('');
			$('.search-code-list option').each(function(index,elem){
				if(true == $(this).prop("selected")){
					$(this).prop("selected", false);
				}
			});
			$('#search-code-list').addClass('d-none');
			return false;
		})
		if(location.search.match(/sortcode=/)){
			$('input[name="sortcode"]').each(function(int,val){
			});
		}
	})
}