import Vue from "vue";
import MountingPortal from "portal-vue";

/**
 * https://v2.portal-vue.linusb.org/
 * https://v2.portal-vue.linusb.org/guide/advanced.html#rendering-outside-of-the-vue-app
 */
Vue.use(MountingPortal);

/**
 * 投稿のリスト表示
 */
const PostList = {
	props: {
		/**
		 * カスタム投稿の配列
		 * WP_Post[]
		 */
		posts: Array,
	},
	emits: ["open"],
	template: `
		<div class="vue-ul">
			<div v-for="p in posts" :key="p.ID" class="vue-li">
				<span class="vue-li-title" @click="$emit('open', p)">
					{{ p.post_title }}
				</span>
				<span class="vue-li-date">
					{{ p.post_date }}
				</span>
			</div>
		</div>
	`,
};

/**
 * 個別の投稿を表示
 */
const PostModal = {
	props: {
		/**
		 * モーダルの内容
		 * WP_Post
		 */
		post: Object,
	},
	emits: ["close"],
	template: `
		<MountingPortal v-if="post" mountTo="#wpwrap" append>
			<div @click.self="$emit('close')" class="vue-modal">
				<!-- Background Layer -->
				<div class="vue-modal-content">
					<!-- Content Layer -->
					<h1>{{ post.post_title }}</h1>
					<div>{{ post.post_date }}</div>
					<div v-html="post.post_content"></div>
				</div>
			</div>
		</MountingPortal>
	`,
};

const N2NotificationRead = {
	props: {
		/**
		 * カスタム投稿の配列
		 * WP_Post[]
		 */
		customPosts: Array,
	},
	components: { PostList, PostModal },
	data() {
		return {
			/**
			 * モーダルの内容
			 * WP_Post
			 */
			modalContent: undefined,
		};
	},
	computed: {
		// 日付の部分だけ日本語に変換
		formattedPosts() {
			return this.customPosts.map((p) => {
				p.post_date = this.formatDate(p.post_date);
				return p;
			});
		},
	},
	methods: {
		openModal(wpPost) {
			this.modalContent = wpPost;
		},
		closeModal() {
			this.modalContent = undefined;
		},
		formatDate(dateString) {
			const date = new Date(dateString);
			const y = date.getFullYear();
			const m = String(date.getMonth() + 1).padStart(2, "0");
			const d = String(date.getDate()).padStart(2, "0");
			return `${y}年${m}月${d}日`;
		},
	},
	template: `
		<div>
			<div v-if="customPosts.length === 0"> お知らせはありません </div>
			<PostList :posts="formattedPosts" @open="openModal" />
			<PostModal :post="modalContent" @close="closeModal" />
		</div>
	`,
};

window.addEventListener("DOMContentLoaded", () => {
	// eslint-disable-next-line no-new
	new Vue({
		el: "#app",
		components: {
			N2NotificationRead,
		},
	});
});
