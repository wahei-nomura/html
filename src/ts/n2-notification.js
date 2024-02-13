import Vue from "vue";

const CustomCheckboxes = {
	props: {
		/**
		 * inputタグのname属性
		 */
		name: String,
		/**
		 * チェックボックス生成用。キーと値を配列でまとめた配列くれ。
		 * [value, label][]
		 */
		options: Array,
		/**
		 * 初期値。新規作成ならundefinedくる。編集なら配列くる。
		 * value[]
		 */
		initial: Array,
	},
	data() {
		return {
			checkedValue: [],
		};
	},
	created() {
		// 初期値をコピー
		this.checkedValue = [...this.initial];
	},
	methods: {
		getAllValues() {
			return this.options.map(([value]) => value);
		},
		setAllValues() {
			this.checkedValue = this.getAllValues();
		},
		resetValues() {
			this.checkedValue = [];
		},
	},
	computed: {
		isCheckedAll: {
			get() {
				return this.checkedValue.length === this.options.length;
			},
			set(isAll) {
				isAll ? this.setAllValues() : this.resetValues();
			},
		},
	},
	template: `
		<div>
			<label class="vue-checkbox-all">
				<input v-model="isCheckedAll" type="checkbox" />
				<span>
					<slot name="label-all">すべて</slot>
				</span>
			</label>
			<div v-for="[value, label] in options" :key="value">
				<label class="vue-checkbox-each">
					<input
						v-model="checkedValue"
						type="checkbox"
						:name="name"
						:value="value"
					/>
					<span>{{ label }}</span>
				</label>
			</div>
		</div>
	`,
};

window.addEventListener("DOMContentLoaded", () => {
	[
		"#notification-input-roles", // ユーザー権限
		"#notification-input-regions", // 自治体
	].map(
		(el) =>
			new Vue({
				el,
				components: {
					CustomCheckboxes,
				},
			})
	);
});
