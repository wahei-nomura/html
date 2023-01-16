import ajax from "./ajax";
import search from "./search";
import copyPost from "./copypost";
import ajax_dl from "./ajax-dl";
import ajax_rakuten_import from "./ajax-rakuten-import";


export default () => {
	ajax();
	search();
	copyPost();
	ajax_dl();
	ajax_rakuten_import();
};
