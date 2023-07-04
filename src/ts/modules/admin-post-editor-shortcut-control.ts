/**
 * ショートカットキーの制御
 */
export default () => {
	const wp = window['wp'];
	// 「戻る」の制御をデフォルトに戻す
	wp.data.dispatch( 'core/keyboard-shortcuts' ).unregisterShortcut('core/editor/undo')
	// 「進む」の制御をデフォルトに戻す
	wp.data.dispatch( 'core/keyboard-shortcuts' ).unregisterShortcut('core/editor/redo')
};