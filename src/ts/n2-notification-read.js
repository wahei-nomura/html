import Vue from "vue";
import MountingPortal from "portal-vue";

/**
 * https://v2.portal-vue.linusb.org/
 * https://v2.portal-vue.linusb.org/guide/advanced.html#rendering-outside-of-the-vue-app
 */
Vue.use(MountingPortal);

// n2をリアクティブな値として参照
// あくまでもwindow.n2の値をコピーしているだけなので注意
Vue.use({
	install(Vue) {
		Vue.prototype.$n2 = Vue.observable({
			...window.n2,
		});
	},
});

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
			<template v-for="p in posts">
				<div class="vue-li">
					<span class="vue-li-title" @click="$emit('open', p)">
						{{ p.post_title }}
					</span>
					<span class="vue-li-date">
						{{ p.post_date }}
					</span>
				</div>
				<div>
					<span>{{ p.is_read ? '既読' : '未読' }}</span>
					<span>{{ p.is_force ? '強制表示' : '強制しない' }}</span>
				</div>
			</template>
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
				<article class="vue-modal-content">
					<!-- Content Layer -->
					<div>
						<button type="button" @click="$emit('close')">閉じる</button>
					</div>
					<h1>#{{ post.ID }} - {{ post.post_title }}</h1>
					<div>{{ post.post_date }}</div>
					<section v-html="post.post_content"></section>
					<form method="post" action="">
						<input type="hidden" name="user" :value="$n2.current_user.ID" />
						<input type="hidden" name="post" :value="post.ID" />
						<div>
							<button>
								確認しました！！！
							</button>
						</div>
					</form>
				</article>
			</div>
		</MountingPortal>
	`,
};

window.addEventListener("DOMContentLoaded", () => {
	// eslint-disable-next-line no-new
	new Vue({
		el: "#app",
		components: { PostList, PostModal },
		data() {
			return {
				modalContent: undefined,
			};
		},
		computed: {
			// 日付の部分だけ日本語に変換
			formattedPosts() {
				const notifications = Vue.prototype.$n2.notifications || [];
				return notifications.map((p) => {
					p.post_date = this.formatDate(p.post_date);
					return p;
				});
			},
			// 分別
			waketaPosts() {
				const yomu = [];
				const yoman = [];
				this.formattedPosts.forEach((p) => {
					console.log(p.is_force, p.is_read);
					(p.is_force && !p.is_read ? yomu : yoman).push(p);
				});
				return { yomu, yoman };
			},
		},
		watch: {},
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
				<h2>確認が必要なお知らせ {{ waketaPosts.yomu.length }}件</h2>
				<PostList :posts="waketaPosts.yomu" @open="openModal" />

				<h2>それ以外 {{ waketaPosts.yoman.length }}件</h2>
				<PostList :posts="waketaPosts.yoman" @open="openModal" />

				<PostModal :post="modalContent" @close="closeModal" />
			</div>
		`,
	});
});
