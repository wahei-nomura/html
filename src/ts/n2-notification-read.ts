import Vue, { ComponentOptions } from "vue";

const unko: ComponentOptions<Vue> = {
	data: () => {
		return {
			moji: "unko",
		};
	},
	template: `
		<div>
			<input v-model="moji" type="text" />
			<span>{{ moji }}</span>
		</div>
	`,
};

window.addEventListener("DOMContentLoaded", () => {
	// eslint-disable-next-line no-new
	new Vue({
		el: "#app",
		components: {
			unko,
		},
	});
});
