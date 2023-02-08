import { prefix, neoNengPath, ajaxUrl } from "./_functions";

/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function ($) {
		$(".sisfile").on("submit", function(e){
			e.preventDefault();
			var
			$this = $(this),
			fd = new FormData($this[0] as HTMLFormElement),
			txt = $this.find('[type="submit"]').val() as string;
			$this.find('[type="submit"]').val(txt.replace("転送","転送中..."));
			fd.append('action', "transfer_rakuten");
			fd.append('judge', $this.find('[type="file"]').attr('name').replace("[]",""));
			$.ajax({
				url: ajaxUrl(window),
				type: 'POST',
				data: fd,
				dataType: 'html',
				contentType:false,
				processData:false,
				success: function(data){
					console.log(data);
					alert(data);
					$this.find('[type="submit"]').val(txt);
				}
			});
		});

	});
};
