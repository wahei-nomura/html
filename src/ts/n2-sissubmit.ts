export default () =>{
	jQuery(function($){
		// 各種セットアップの更新
		$('.sissubmit').on('click',function(e){
			e.preventDefault();
			const 
			ajaxurl = (window) :string  => window.tmp_path.ajax_url,
			$this :any = $(this),
			data :string = $this.parents('form').serialize();
			if(ajaxurl(window)){
				$this.val("　更新中...　");
				$.ajax({
					type: "POST",
					url : ajaxurl(window),
					data: data,
				})
				.done((data) =>{
					console.log(data)
					alert("更新完了！");
					$this.val("　更新する　");
				});
			}else{
				alert('更新に失敗しました')
			}
			return false;
		});
	});
};