import { ajaxUrl } from './_functions'

jQuery(function($) {
	
	console.log('submit.ts読み込み中')
	// 各種セットアップの更新
	$('.sissubmit').on('click',function(e){
		e.preventDefault();
		const 
		$this :any = $(this),
		data :string = $this.parents('form').serialize();
		if(!$this.parents('form')[0].reportValidity()){
			alert("入力されていない項目があります");
			return false;
		}
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