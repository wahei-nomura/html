import $ from 'jquery';

export default () => {
	/** ===============================================================
	 * 
	 * フロント一覧関連
	 * 
	================================================================== */
	jQuery(function ($) {
		if(location.search.match(/look=true/)){

			// 確認ステータスの絞り込み表示
			$('#n2-status-toggle-btns input[type="checkbox"]').on('change',e=>{
				const status = $(e.target).attr('id')
				const flag = $(e.target).prop('checked')

				$.each( $('.n2-jigyousya-radiobox input[type="radio"]:checked'), (i,v)=>{
					if($(v).hasClass(status) && flag === false){
						$($('.product-list li')[i]).addClass('d-none')
					} else if($(v).hasClass(status) && flag === true) {
						$($('.product-list li')[i]).removeClass('d-none')
					}
				})

			})

		}
	})
}
