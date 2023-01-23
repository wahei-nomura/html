import ajax from "./ajax";
import search from "./search";
import copyPost from "./copypost";
import ajax_dl from "./ajax-dl";


export default () => {
	ajax();
	search();
	copyPost();
	ajax_dl();

};
