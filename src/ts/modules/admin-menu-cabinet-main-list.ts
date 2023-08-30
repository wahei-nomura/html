import Vue from 'vue/dist/vue.min';
import Vuex,{mapState} from 'vuex/dist/vuex.min';
import FolderTree from './admin-menu-cabinet-folder-tree';

export default Vue.extend({
	name: 'ImageList',
	data(){
		return {

		};
	},
	props: {
		image: {
			type: Object,
			required: true
		},
	},
	methods: {

	},
	template:`
		<div class="card shadow me-2">
			<div class="card-header d-flex align-items-center justify-content-between">
				<input type="checkbox" name="selected">
				<span class="card-text"></span>
			</div>
			<img src="" class="card-img-top" alt="" data-bs-toggle="modal" data-bs-target="#CabinetModal" role="button" decoding=“async”>
			<div class="card-img-overlay text-center">
				<h6 class="card-title text-truncate"></h6>
				<p class="card-text">フォルダは空です。</p>
			</div>
		</div>
	`,
});