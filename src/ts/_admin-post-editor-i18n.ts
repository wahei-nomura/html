/**
 * ブロックエディタの翻訳
 *
 * - wp.i18n.setLocaleDataを利用
 */
export default () => {
	window['wp'].i18n.setLocaleData( {
		"Submit for Review": ["スチームシップに送信"],
		"Pending review": ["スチームシップ確認中"],
		"Save as pending": ["スチームシップ確認中として保存"],
		"Switch to draft": ["事業者入力可能にする"],
		"Publish": ['スチームシップ確認'],
		"Update": ['スチームシップ確認'],
		"Are you ready to submit for review?": ["スチームシップに送信後の変更はできません"],
		"When you’re ready, submit your work for review, and an Editor will be able to approve it for you.": ["スチームシップに送信後は基本的にはデータの変更はできません。入力中のデータが正しいか確認後に送信してください。"],
		"Always show pre-publish checks.": ["このパネルを常に表示する"],
		"Are you sure you want to unpublish this post?": ["事業者入力可能になります。よろしいですか？"],
		"List View": ["目次"]
	} );
};