import { prefix, neoNengPath, ajaxUrl } from '../functions/index'

export default () => {
	/** ===============================================================
	 * 
	 * 検索用
	 * 
	================================================================== */
	jQuery(function($) {
		
		const changeItemcode=():void => {
			$.ajax({
				url: ajaxUrl(window),
				data: {
					action: 'N2_Postlist',
					事業者: $('select[name="事業者"]').val(),
				},
			}).done(res => {
				const data=JSON.parse(res)
				console.log(data)
				$('select[name="返礼品コード"]>*').remove()
				$('select[name="返礼品コード"]').append('<option value="">返礼品コード</option>')

				const url=new URL(location.href)
				const params=url.searchParams
				
				Object.keys(data).forEach(key => {
					const selected=params.get('返礼品コード')===key? 'selected':'';
					$('select[name="返礼品コード"]').append($(`<option value="${key}" ${selected}>${data[key]}</option>`));
					
				})
			})	
		}

		changeItemcode()
		
		$('select[name="事業者"]').on('change', () => {
			changeItemcode()
		})
	})
}
