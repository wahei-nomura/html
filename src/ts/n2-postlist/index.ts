import ajax from "./ajax";
import search from "./search";
import copyPost from "./copypost";

export default () => {
	ajax();
	search();
	copyPost();
};
