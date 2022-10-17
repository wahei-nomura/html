import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import frontAjax from "./front-ajax";
import itemDetail from "./item-detail";
export default () => {
	frontAjax();
	if(location.search.match(/[&?]p=/)) itemDetail();
};
