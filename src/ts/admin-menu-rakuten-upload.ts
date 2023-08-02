import '../../node_modules/bootstrap/dist/js/bootstrap';

jQuery( function($){

	const get_files = async (id:string ) => {
		return await $.ajax({
			url : window['n2']['ajaxurl'],
			type : 'GET',
			data : {
				action : 'n2_rms_cabinet_api_ajax',
				request : 'files_get',
				mode : 'json',
				folderId : id,
			},
		});
	}

	// 
	const top = $('#ss-cabinet .row').offset().top;
	const $tree = $('.tree')

	// フォルダツリー制御
	$tree.on('click','li > span', async function(event){
		const icons = ['spinner-border spinner-border-sm','bi bi-folder2-open'];
		if (event.target === this) {
			$(this).toggleClass('close').siblings('ul').toggleClass('d-none');

			const $cardGroup = $('#ss-cabinet-images');
			$cardGroup.css({
				height: `calc(100vh - ${top}px )`,
			})
			$('.tree').css({
				height: `calc(100vh - ${top}px )`,
			})
			if( $(this).hasClass('active') ) {
				return true;
			} else {
				$('span.active').removeClass('active');
				$(this).children('i').attr('class', icons[0] );
				$cardGroup.addClass('loading');
			}
			
			$(this).addClass('active');
			const $active = $(this);
			const res = await get_files($(this).data('id'));
			$cardGroup.empty();
			res.forEach( async file => {
				const $card = $('#card-template .card').clone(false);
				const url = file['FileUrl'];
				if ( ! url ) {
					$card.addClass('flex-fill');
					$card.find('img').remove();
					$card.css('max-width','100%');
					$cardGroup.append( $card );
					return;
				}
				let thumbnailUrl = url.replace('image.rakuten.co.jp','thumbnail.image.rakuten.co.jp/@0_mall');
				thumbnailUrl += '?_ex=137x137';
				$card.find('img').attr({
					src: thumbnailUrl,
					alt: file['FileName'],
					'data-url': url,
				});
				$card.find('.card-title').text(file['FileName']);
				$card.find('.card-text').text(file['FilePath']);
				$cardGroup.append( $card );
			});
			const $dragArea = $('#dragable-area-template .dragable-area').clone(false);
			$cardGroup.append($dragArea);
			$('#ss-cabinet form').find('input').each((_,input)=>{
				switch ($('#ss-cabinet form').find('input').eq(_).attr('name')) {
					case 'filePath':
						$('#ss-cabinet form').find('input').eq(_).val( $active.data('path') );
						break;
					case 'folderId':
						$('#ss-cabinet form').find('input').eq(_).val( $active.data('id') );
						break;
				}
			})
			

			$cardGroup.removeClass('loading');
			$(this).children('i').attr('class', icons[1] );
		}
	})
	$tree.find('li > span').eq(0).trigger('click');

	// モーダル制御
	$(document).on('click','.card-img-top',function(){
		$('#CabinetModalImage').attr({
			src: $(this).data('url')
		});
	});

	// drag&drop制御
	{

		$(document).on('dragover','.dragable-area',function(e){
			e.preventDefault();
			$(this).addClass('dragover');
		})
		// ドラッグ＆ドロップエリアからドラッグが外れたときのイベントを追加
		$(document).on('dragleave','.dragable-area', function(e) {
			e.preventDefault();
			$(this).removeClass('dragover');
		  });
		  // ドラッグ＆ドロップエリアにファイルがドロップされたときのイベントを追加
		  $(document).on('drop','.dragable-area', function(e) {
			e.preventDefault();
			$(this).removeClass('dragover');
	  
			// ドロップされたファイルを取得
			const files = e.originalEvent.dataTransfer.files;

			$("#ss-cabinet").find('form input[type="file"]').prop('files',files);
			$("#ss-cabinet").find('form input[type="submit"]').trigger('click');
		  });
	}
})