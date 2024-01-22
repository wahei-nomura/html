import Vue from "vue";
import axios from "axios";

const NotificationPost = {
	props: {
		post: Object,
	},
	data() {
		return {};
	},
	template: `
		<div>
			<span>{{ post.post_title }}</span>
			<span>{{ post.post_date }}</span>
		</div>
	`,
};

const RootComponent = {
	props: {
		allRolls: Object,
		allRegions: Object,
	},
	components: {
		NotificationPost,
	},
	data() {
		return {
			posts: [],
			targetRolls: this.getAllRollValues(),
			targetRegions: this.getAllRegionKeys(),
		};
	},
	methods: {
		getAllRollValues() {
			return Object.values(this.allRolls);
		},
		getAllRegionKeys() {
			return Object.keys(this.allRegions);
		},
	},
	computed: {
		searchUrl() {
			return `${window.n2.ajaxurl}?action=n2_items_api&post_type=notification&get_post_meta`;
		},
	},
	created() {
		axios.get(this.searchUrl).then((response) => {
			console.log(response.data.items);
			this.posts = response.data.items;
		});
	},
	template: `
		<div>
			<div>
				({{ posts.length }})
			</div>
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
