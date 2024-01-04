
jQuery(($) => {
	$.ajax({
		url:`${window['n2'].ajaxurl}?action=n2_dashboard_custom_help_widget_text`
	}).then(res => {
		$('#custom_help_widget .inside').html(res);
	});
	
	$.ajax({
		url:`${window['n2'].ajaxurl}?action=n2_dashboard_jichitai_widget_text`
	}).then(res => {
		$('#jichitai_widget .inside').html(res);
	});
});
