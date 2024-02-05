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
 * タブ
 */
const PostTab = {
	props: {
		value: Boolean,
	},
	emits: ["input"],
	template: `
		<div class="vue-tab">
			<button
				type="button"
				class="vue-tab-button"
				@click="$emit('input', true)"
				:class="{'vue-tab-button__selected': value}">
				確認が必要なお知らせ
			</button>
			<button
				type="button"
				class="vue-tab-button"
				@click="$emit('input', false)"
				:class="{'vue-tab-button__selected': !value}">
				すべてのお知らせ
			</button>
		</div>
	`,
};

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
	methods: {
		convertHTMLToPlainText(html) {
			// DOMParserを使用してHTML文字列から新しいDOMを作成
			const parser = new DOMParser();
			const doc = parser.parseFromString(html, "text/html");
			// documentElementのtextContentプロパティから平文を取得
			return doc.documentElement.textContent;
		},
	},
	template: `
		<div class="vue-ul">
			<div v-for="p in posts" :key="p.ID" class="vue-li">
				<div class="vue-li-header">
					<span class="vue-li-header-title" @click="$emit('open', p)">
						{{ p.post_title }}
					</span>
					<span class="vue-li-header-date">
						{{ p.post_date }}
					</span>
				</div>
				<div class="vue-li-text">
					{{ convertHTMLToPlainText(p.post_content) }}
				</div>
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
	computed: {
		isShouldRead() {
			return this.post.is_force && !this.post.is_read;
		},
	},
	template: `
		<MountingPortal v-if="post" mountTo="#wpwrap" append>
			<div @click.self="$emit('close')" class="vue-modal">
				<!-- Background Layer -->
				<article class="vue-modal-inner">
					<!-- Content Layer -->
					<div class="vue-modal-header">
						<button class="vue-modal-header-btn" type="button" @click="$emit('close')">
							閉じる
						</button>
						<h1 class="vue-modal-header-title">
							{{ post.post_title }}
						</h1>
						<div>
							{{ post.post_date }}
						</div>
					</div>
					<section class="vue-modal-content" v-html="post.post_content"></section>
					<form method="post" action="">
						<input type="hidden" name="user" :value="$n2.current_user.ID" />
						<input type="hidden" name="post" :value="post.ID" />
						<div class="vue-modal-footer">
							<button v-if="isShouldRead" type="submit">
								確認しました！
							</button>
							<button v-else type="button" @click="$emit('close')">
								閉じる
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
		components: { PostTab, PostList, PostModal },
		data() {
			return {
				tabValue: true,
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
			// 強制表示用
			forceReadPosts() {
				return [...this.formattedPosts].filter(
					(p) => p.is_force && !p.is_read
				);
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
			<div class="vue-wrap">
				<!-- タブ -->
				<PostTab v-model="tabValue" />
				<!-- 投稿 -->
				<template v-if="tabValue">
					<PostList v-if="forceReadPosts.length > 0" :posts="forceReadPosts" @open="openModal" />
					<p v-else class="vue-zero">確認が必要なお知らせはありません</p>
				</template>
				<template v-else>
					<PostList v-if="formattedPosts.length > 0" :posts="formattedPosts" @open="openModal" />
					<p v-else class="vue-zero">お知らせはありません</p>
				</template>
				<!-- モーダル -->
				<PostModal :post="modalContent" @close="closeModal" />
			</div>
		`,
	});
});
