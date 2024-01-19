import Vue, { ComponentOptions, PropType } from "vue";
import axios, { AxiosResponse } from "axios";

type NotificationPostData = {
	ID: number;
	post_title: string;
	post_content: string;
	post_date: string;
};
type ApiResponseJson = {
	items: NotificationPostData[];
	params: any;
};

const NotificationPost: ComponentOptions<Vue> = {
	props: {
		post: {
			type: Object as PropType<NotificationPostData>,
			required: true,
		},
	},
	data() {
		return {};
	},
	template: `
		<div>
			<a href="#">{{ post.post_title }}</a>
			<span>{{ post.post_date }}</span>
		</div>
	`,
};

const RootComponent: ComponentOptions<Vue> = {
	components: {
		NotificationPost,
	},
	data() {
		return {
			posts: [],
		};
	},
	methods: {
		createUrl() {
			return "https://wp-multi.ss.localhost/wp-admin/admin-ajax.php?action=n2_items_api&post_type=notification";
		},
	},
	created() {
		axios
			.get(this.createUrl())
			.then((response: AxiosResponse<ApiResponseJson>) => {
				console.log(response.data.items);
				this.posts = response.data.items;
			});
	},
	template: `
		<div>
			<NotificationPost v-for="p in posts" :key="p.ID" :post="p" />
		</div>
	`,
};

// ここは#app内で<NotificationList />を使えるようにするだけ
window.addEventListener("DOMContentLoaded", () => {
	// eslint-disable-next-line no-new
	new Vue({
		el: "#app",
		components: {
			NotificationList: RootComponent,
		},
	});
});
