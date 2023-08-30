import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';
import axios,{ AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import { cabinetFolder,cabinetImage } from './admin-menu-cabinet-interface';



Vue.use(Vuex);

export default new Vuex.Store({
  // state, mutations, actions, getters などを定義します
	state: {
		files: [],
		selectedFile: null,
		folders: [],
		rootFolder: null,
		selectedFolder: null,
		viewMode : 'grid',
		isClick : true,
		isTrashBox: false,
		isLoading: false,
		offsetHeight: {},
		modalUrl: "",
		modal: null,
		tree:{},
		n2nonce: null,
		defaultFormData: {
			action: 'n2_rms_cabinet_api_ajax',
			mode: 'json',
		},
		addFormData: {},
	},
	mutations: {
		SET_FILES (state, files) {
			state.files = files;
			state.isLoading = false;
		},
		SET_SELECTED_FILE ( state, file:cabinetImage ) {
			state.selectedFile = file;
		},
		SET_FOLDERS (state, folders:cabinetFolder[]) {
			state.folders = folders;
		},
		SET_ROOT_FOLDER (state, root:cabinetFolder) {
			state.rootFolder = root;
		},
		SET_SELECTED_FOLDER ( state, folder:cabinetFolder ) {
			state.selectedFolder = folder;
		},
		IS_TRASHBOX( state, bool:boolean) {
			state.isTrashBox = bool;
		},
		IS_LOADING( state, bool:boolean) {
			state.isLoading = bool;
		},
		SET_VIEW_MODE( state, viewMode:string) {
			state.viewMode = viewMode;
		},
		IS_CLICK(state, bool:boolean) {
			state.isClick = bool;
		},
		SET_OFFSET_HEIGHT(state, top){
			state.offsetHeight = {
				height: `calc(100vh - ${top}px )`,
			}
		},
		SET_MODAL_URL(state, url:string) {
			state.modalUrl = url
		},
		SET_MODAL(state, name:string) {
			state.modal = name
		},
		SET_FORMDATA(state,formData){
			state.addFormData = formData;
		},
		SET_N2NONCE(state, n2nonce:string){
			state.n2nonce = n2nonce;
		},
		SET_TREE(state, folders:cabinetFolder[]){
			const tree = {};
			const root = folders[0];
			// Recursive function to build the tree
			const buildTreeRecursive = (parent, path:string[]) => {
				const p = path.shift();
				parent[p] = parent[p] || {};
				if (path.length) {
					buildTreeRecursive(parent[p], path);
				}
			};
			// Build the tree structure for each folder
			folders.filter((_,i)=>i).map(f => {
				buildTreeRecursive(tree, [...f.ParseFolderPath]); // Using a spread operator to avoid modifying the original array
				return f;
			});
			state.tree = {
				[root.FolderName]: tree,
			};
		},
	},
	actions: {
		async updateFileSet({state, commit, getters},update){
			for ( const key in update ) {
				switch (key) {
					case 'isTrashBox':
						commit("IS_TRASHBOX",update[key]);
						break;
					case 'isLoading':
						commit("IS_LOADING",update[key]);
						break;
					case 'selectedFile':
						commit("SET_SELECTED_FILE",update[key]);
						break;
					case 'selectedFolder':
						commit("SET_SELECTED_FOLDER",update[key]);
						break;
					default:
						break;
				}
			}
		},
		async updateFiles ({state, commit, getters, dispatch},folder: cabinetFolder) {
			dispatch(
				'updateFileSet',
				{
					isTrashBox: false,
					isLoading: true,
					selectedFile: null,
					selectedFolder: folder,
				}
			)
			await commit('SET_FORMDATA',{
				call: "files_get",
				folderId: folder.FolderId,
			});
			const data = await dispatch('makeFormData')
			await axios.post(
				window["n2"]["ajaxurl"],
				data,
			).then(resp => {
				commit('SET_FILES',resp.data);
				commit('IS_LOADING',false);
			});
			return getters.filterFiles ?? state.files;
		},
		async updateTrahBoxFiles ({state, commit, getters,dispatch}) {
			dispatch(
				'updateFileSet',
				{
					isTrashBox: true,
					isLoading: true,
					selectedFile: null,
					selectedFolder: {FolderName:'ゴミ箱'},
				}
			)
			const data = await dispatch('makeFormData')
			await axios.post(
				window["n2"].ajaxurl,
				data
			).then(resp=>{
				commit('SET_FILES',resp.data);
				commit('IS_LOADING',false);
			});
			return getters.filterFiles ?? state.files;
		},
		async updateFolders( { commit,dispatch } ) {
			await commit('SET_FORMDATA',{
				call: "folders_get",
			});
			const data = await dispatch('makeFormData')
			await axios.post(
				window["n2"]["ajaxurl"],
				data,
			).then((resp:AxiosResponse<cabinetFolder[]>)=>{
				return resp.data.map(folder=>{
					resp.data.sort((a, b) => a.FolderPath.localeCompare(b.FolderPath));
					folder.ParseFolderPath = folder.FolderPath.split('/').filter(f=>f);
					return folder;
				});
			}).then(folders => {
				const root = folders.filter(folder=> folder.FolderPath === '/' )[0];
				commit('SET_ROOT_FOLDER',root)
				commit('SET_FOLDERS',folders)
				commit('IS_LOADING',false);
			});
		},
		showModal({ commit },type:string){
			commit('SET_MODAL',type);
		},
		makeFormData({commit,getters}) {
			const data = new FormData();
			const params = getters.mergeForm;
			for ( const key in params ) {
				data.append(key,params[key])
			}
			// 追加分をリセット
			commit('SET_FORMDATA',{});
			return data;
		},
	},
	getters: {
		filterFiles: state => {
			return state.files?.filter(file=>{
				return file?.length || file.hasOwnProperty('FileId');
			});
		},
		mergeForm: state => {
			return { ...state.defaultFormData, ...state.addFormData, n2nonce:state.n2nonce };
		}
	}
});