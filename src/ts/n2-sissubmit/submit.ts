import { prefix, neoNengPath, ajaxUrl } from '../functions/index'

export default () => {
	jQuery(function($){
		// 各種セットアップの更新
		$('.sissubmit').on('click',function(e){
			e.preventDefault();
			const 
			$this :any = $(this),
			data :string = $this.parents('form').serialize();
			if(ajaxUrl(window)){
				$this.val("　更新中...　");
				$.ajax({
					type: "POST",
					url : ajaxUrl(window),
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