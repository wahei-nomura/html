import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';
import { mapState,mapActions,mapGetters } from 'vuex/dist/vuex.min';
import axios,{ AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import { cabinetFolder,cabinetImage } from './admin-menu-cabinet-interface';

Vue.use(Vuex);

export default Vue.extend({
    name: 'Header',
    data() {
        return {
			call: "files_search",
			keywords: ''
        };
    },
	computed:{
		...mapState([
			'n2nonce',
			'files'
		]),
	},
    methods: {
		async search(){
			const formData = {
				call: this.call,
			};
			const keywords = this.keywords.split(/\s/g).filter((x) => x);
			keywords.forEach((keyword:string, i:number) => {
				formData[`keywords[${i}]`] = keyword;
			});
			await this.$store.commit('SET_FORMDATA',formData);
			const data = await this.$store.dispatch('makeFormData');
			this.$store.dispatch('updateFileSet',{
				isTrashBox: false,
				isLoading: true,
				selectedFile: null,
				selectedFolder: {FolderName:'検索結果'},
			});
			await axios.post(
				window["n2"]["ajaxurl"],
				data,
			).then(resp=>{
				let files = Object.values(resp.data).flat() as cabinetImage[];
				files = files.filter(file=>{
					return file.hasOwnProperty('FileId');
				})
				this.$store.commit('SET_FILES',files);
				this.$store.commit('IS_LOADING',false);
			});
		},
    },
	template: `
	<div class="d-flex justify-content-between pb-1">
		<h2>CABINET</h2>
		<form id="cabinet-search" class="d-flex col-5 p-1">
			<input class="form-control me-2" type="search" name="keywords" placeholder="キーワード" aria-label="キーワード" v-model="keywords">
			<button @click.prevent="search" id="cabinet-search-btn" class="btn btn-outline-success" style="text-wrap:nowrap;">検索</button>
		</form>
	</div>
	`
});