import Vue from "vue";

/**
 * 投稿のリスト表示
 */
const PostList = {
	props: {
		post: Object,
	},
	emits: ["open"],
	template: `
		<div>
			<span @click="$emit('open')">{{ post.post_title }}</span>
			<span>{{ post.post_date }}</span>
		</div>
	`,
};

/**
 * 個別の投稿を表示
 */
const PostModal = {
	props: {
		post: Object,
	},
	emits: ["close"],
	data() {
		return {};
	},
	template: `
		<div v-if="post" @click.self="$emit('close')">
			<!-- Background Layer -->
			(ここは背景)
			<div>
				<!-- Content Layer -->
				<h1>{{ post.post_title }}</h1>
				<div v-html="post.post_content"></div>
			</div>
		</div>
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
	methods: {
		openModal(wpPost) {
			this.modalContent = wpPost;
		},
		closeModal() {
			this.modalContent = undefined;
		},
	},
	template: `
		<div>
			<div v-if="customPosts.length === 0"> お知らせはありません </div>
			<PostList v-for="p in customPosts" :key="p.ID" :post="p" @open="openModal(p)" />
			<PostModal :post="modalContent" @close="closeModal()" />
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
