import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';
import { mapState,mapActions,mapGetters } from 'vuex/dist/vuex.min';
import { cabinetFolder,cabinetImage } from './cabinet-interface';

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
			this.$store.dispatch('commitStates',{
				isTrashBox: false,
				isSearchResult: true,
				isLoading: true,
				focusFile: null,
				selectedFolder: {FolderName:'検索結果'},
			});
			await this.$store.dispatch('ajaxPost',{
				formData:formData,
			})
			.then(resp=>{
				let files = Object.values(resp.data).flat() as cabinetImage[];
				files = files.filter(file=>{
					return file.hasOwnProperty('FileId');
				})
				this.$store.commit('SET_FILES',files);
				this.$store.commit('IS_LOADING',false);
				this.$sotre.commit('REMOVE_ALL_SELECTED_FILE_ID')
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