import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import frontAjax from "./front-ajax";
import frontSearch from "./front-search";
import product from "./product";
console.log(homeUrl(window));
export default () => {
	frontAjax();
	if(location.search.match(/[&?]p=/)) product();
	frontSearch();
};
