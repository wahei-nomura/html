export default () =>{
	jQuery(function($){
		// 各種セットアップの更新
		$('.sissubmit').on('click',function(e){
			e.preventDefault();
			let
			$this	= $(this),
			data = $this.parents('form').serializeArray(),
			ajaxurl = 'https://ore.steamship.co.jp/wp/wp-admin/admin-ajax.php'; //変更要
			$this.val("　更新中...　");
			$.ajax({
				type: "POST",
				url : ajaxurl,
				data: data,
				success: function(data) {
					console.log(data)
					alert("更新完了！");
					$this.val("　更新する　");
				}
			});
			return false;
		});
	});
};