import { prefix, neoNengPath, ajaxUrl, homeUrl } from "../functions/index";
import frontAjax from "./front-ajax";
console.log(homeUrl(window));
export default () => {
	frontAjax();
};
