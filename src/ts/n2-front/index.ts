import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import frontAjax from "./front-ajax";
import productDetail from "./product-detail";
export default () => {
	frontAjax();
	if(location.search.match(/[&?]p=/)) productDetail();
};
