import { ajaxUrl } from './modules/functions'
import '../../node_modules/bootstrap/dist/js/bootstrap';

jQuery(function($) {
	
	console.log('admin-n2-sync.ts読み込み中')

	const sheetIdInput = $('input[name="n2_sync_settings_spreadsheet[id]"]');

	// IDでもURLでもIDに変換
	const extractSheetId = (inputValue:string):string => inputValue.match(/spreadsheets\/d\/(.*?)\/|$/i)[1] || inputValue ;

	const sheetId = extractSheetId(sheetIdInput.val() as string);

	// リンクへセット
	const createSheetLink = (inputValue:string):void => {
		const linkUrl = 'https://docs.google.com/spreadsheets/d/' + inputValue
		sheetIdInput.parent().find('a').text(linkUrl).prop('href', linkUrl);
	}

	createSheetLink(sheetId)

	sheetIdInput.on('keyup',(e)=>{
		const value = $(e.target).val() as string
		const setSheetId = extractSheetId(value)
		createSheetLink(setSheetId);
	})
});