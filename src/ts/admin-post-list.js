import save_post_ids from "./modules/admin-post-list-save-post-ids";
import post_list_tool from "./modules/admin-post-list-tool";

const n2 = window['n2'];
jQuery( $ => {
	save_post_ids($);
	post_list_tool($);
});