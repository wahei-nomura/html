import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import noscript from './noscript'
import frontAjax from "./front-ajax";
import frontSearch from "./front-search";
import product from "./product";
import list from "./list";
console.log(homeUrl(window));
export default () => {
	noscript();
	frontAjax();
	list();
	if(location.search.match(/[&?]p=/)) product();
	frontSearch();
};
