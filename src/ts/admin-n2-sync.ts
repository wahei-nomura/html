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

		$('#n2sync-link-wrapper a').each((i,v) => {
			const parser = new URL($(v).prop('href'));
			parser.searchParams.set('id',value)
			$(v).prop('href', parser);
		})
	})

	$('input[name="n2_sync_settings_spreadsheet[user_range]"]').on('keyup',(e)=>{
		const value = $(e.target).val() as string

		$('#n2sync-link-wrapper a').each((i,v) => {
			const parser = new URL($(v).prop('href'));
			parser.searchParams.set('user_range',value)
			$(v).prop('href', parser);
		})
	})

	$('input[name="n2_sync_settings_spreadsheet[item_range]"]').on('keyup',(e)=>{
		const value = $(e.target).val() as string

		$('#n2sync-link-wrapper a').each((i,v) => {
			const parser = new URL($(v).prop('href'));
			parser.searchParams.set('item_range',value)
			$(v).prop('href', parser);
		})
	})
});