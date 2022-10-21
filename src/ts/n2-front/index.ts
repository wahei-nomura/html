import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import frontAjax from "./front-ajax";
import frontSearch from "./front-search";
console.log(homeUrl(window));
export default () => {
	frontAjax();
	frontSearch();
};
