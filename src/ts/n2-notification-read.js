import Vue, { computed } from "vue";
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
				過去のお知らせ
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
			// 分別
			waketaPosts() {
				const yomu = [];
				const yoman = [];
				this.formattedPosts.forEach((p) => {
					(p.is_force && !p.is_read ? yomu : yoman).push(p);
				});
				return { yomu, yoman };
			},
			// 表示する方のリスト
			displayPosts() {
				return this.tabValue
					? this.waketaPosts.yomu
					: this.waketaPosts.yoman;
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
				<PostTab v-model="tabValue" />
				<PostList v-if="displayPosts.length > 0" :posts="displayPosts" @open="openModal" />
				<p v-else class="vue-zero">お知らせはありません</p>
				<PostModal :post="modalContent" @close="closeModal" />
			</div>
		`,
	});
});
