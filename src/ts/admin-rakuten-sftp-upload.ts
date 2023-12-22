jQuery(function($){
	
	document.getElementById('ss-sftp').addEventListener('change',event =>{
		const fileInput = document.getElementById('ss-sftp') as HTMLInputElement;
		if ( ! fileInput.files.length ) return;
		console.log(fileInput.files);
		const hasDeleteCSV = Array.from(fileInput.files).filter(file=>file.name.indexOf( 'item-delete') !== -1 ).length;
		if ( hasDeleteCSV && ! confirm( 'item-delete.csv が選択されています。続けますか？' ) ) fileInput.value = null;
	})
})