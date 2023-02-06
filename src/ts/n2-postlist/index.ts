import ajax from "./ajax";
import search from "./search";
import copyPost from "./copypost";
import ajax_dl from "./ajax-dl";
import ajax_rakuten_transfer from "./ajax-rakuten-transfer";


export default () => {
	ajax();
	search();
	copyPost();
	ajax_dl();
	ajax_rakuten_transfer();
};
