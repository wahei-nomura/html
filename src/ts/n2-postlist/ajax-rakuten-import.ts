import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

/**
 * 返礼品一覧ページで使用するAjax用のファイル
 */
export default () => {
	jQuery(function ($) {
		$(".sisfile").on("submit", function(e){
			e.preventDefault();
			var
			$this	= $(this),
			fd = new FormData($this[0]),
			txt = $this.find('[type="submit"]').val();
			$this.find('[type="submit"]').val(txt.replace("転送","転送中..."));
			// fd.append('action', "N2_Rakuten_Import");
			fd.append('judge', $this.find('[type="file"]').attr('name').replace("[]",""));
			console.log($this);
			console.log(fd);
			console.log(txt);
			$.ajax({
				url: ajaxurl,
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
