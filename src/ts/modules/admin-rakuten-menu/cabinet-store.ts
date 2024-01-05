import Vue from 'vue/dist/vue.min';
import Vuex from 'vuex/dist/vuex.min';
import axios,{ AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import { cabinetFolder,cabinetImage } from './cabinet-interface';

Vue.use(Vuex);
export default new Vuex.Store({
  // state, mutations, actions, getters などを定義します
	state: {
		files: [],
		focusFile: null,
		selectedFiles: [],
		folders: [],
		rootFolder: null,
		selectedFolder: null,
		viewMode : 'grid',
		isClick : true,
		isTrashBox: false,
		isSearchResult: false,
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
		SET_FOCUS_FILE ( state, file:cabinetImage ) {
			state.focusFile = file;
		},
		ADD_SELECTED_FILE_ID(state, fileId:string) {
			state.selectedFiles = [...state.selectedFiles, fileId];
		},
		ADD_ALL_SELECTED_FILE_ID(state, fileIds:string[]) {
			state.selectedFiles = fileIds;
		},
		REMOVE_SELECTED_FILE_ID(state, fileId:string) {
			state.selectedFiles = state.selectedFiles.filter(id=>id !== fileId);
		},
		REMOVE_ALL_SELECTED_FILE_ID(state) {
			state.selectedFiles = [];
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
		IS_SEARCH_RESULT( state, bool:boolean) {
			state.isSearchResult = bool;
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
		SET_TREE(state){
			const tree = {};
			const root = state.folders[0];
			// Recursive function to build the tree
			const buildTreeRecursive = (parent, path:string[]) => {
				const p = path.shift();
				parent[p] = parent[p] || {};
				if (path.length) {
					buildTreeRecursive(parent[p], path);
				}
			};
			// Build the tree structure for each folder
			state.folders.filter((_,i)=>i).map(f => {
				buildTreeRecursive(tree, [...f.ParseFolderPath]); // Using a spread operator to avoid modifying the original array
				return f;
			});
			state.tree = {
				[root.FolderName]: tree,
			};
		},
	},
	actions: {
		async commitStates({commit},update){
			for ( const key in update ) {
				switch (key) {
					case 'resetFiles':
						commit("SET_FILES",[]);
						break;
					case 'isTrashBox':
						commit("IS_TRASHBOX",update[key]);
						break;
					case 'isSearchResult':
						commit("IS_SEARCH_RESULT",update[key]);
						break;
					case 'isLoading':
						commit("IS_LOADING",update[key]);
						break;
					case 'focusFile':
						commit("SET_FOCUS_FILE",update[key]);
						break;
					case 'selectedFolder':
						commit("SET_SELECTED_FOLDER",update[key]);
						break;
					case 'viewMode':
						commit("SET_VIEW_MODE",update[key]);
						break;
					default:
						break;
				}
			}
		},
		async updateFiles ({state, commit, getters, dispatch},folder: cabinetFolder) {
			const updateStates = {
					isTrashBox: false,
					isLoading: true,
					isSearchResult: false,
					focusFile: null,
					selectedFolder: folder,
			};
			if (this.state.isTrashBox ) {
				updateStates['resetFiles'] = true;
			}
			dispatch(
				'commitStates',
				updateStates,
			)
			await dispatch('ajaxPost',{formData:{
				call: "files_get",
				folderId: folder.FolderId,
			}})
			.then(resp => {
				commit('SET_FILES',resp.data);
				commit('IS_LOADING',false);
				commit('REMOVE_ALL_SELECTED_FILE_ID');
			});
			return getters.filterFiles ?? state.files;
		},
		async updateTrahBoxFiles ({state, commit, getters,dispatch}) {
			dispatch(
				'commitStates',
				{
					viewMode: 'list',
					isTrashBox: true,
					isSearchResult: false,
					isLoading: true,
					focusFile: null,
					selectedFolder: {FolderName:'ゴミ箱'},
				}
			)
			await dispatch('ajaxPost',{formData:{call:'trashbox_files_get'}})
			.then(resp=>{
				commit('SET_FILES',resp.data);
				commit('IS_LOADING',false);
				commit('REMOVE_ALL_SELECTED_FILE_ID');
			});
			return getters.filterFiles ?? state.files;
		},
		async updateFolders( { commit,dispatch } ) {
			await dispatch('ajaxPost',{formData:{
				call: "folders_get",
			}})
			.then((resp:AxiosResponse<cabinetFolder[]>)=>{
				return resp.data.map(folder=>{
					resp.data.sort((a, b) => a.FolderPath.localeCompare(b.FolderPath));
					folder.ParseFolderPath = folder.FolderPath.split('/').filter(f=>f);
					let path = '';
					folder.FolderNamePath = '';
					folder.ParseFolderPath.map(p=>{
						path += '/' + p;
						folder.FolderNamePath += '/' + resp.data.filter(f=>f.FolderPath === path )[0]?.FolderName;
					});
					folder.FolderNamePath = folder.FolderNamePath.replace(/^\//,'')
					return folder;
				});
			}).then( folders => {
				const root = folders.filter(folder=> folder.FolderPath === '/' )[0];
				root.FolderNamePath = root.FolderName;
				commit('SET_ROOT_FOLDER',root)
				commit('SET_FOLDERS',folders)
				commit('IS_LOADING',false);
				commit('SET_TREE');
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
		toggleFileSelection({commit,state},fileId:string){
			if (state.selectedFiles.includes(fileId)) {
				commit('REMOVE_SELECTED_FILE_ID', fileId);
			} else {
				commit('ADD_SELECTED_FILE_ID', fileId);
			}
		},
		async ajaxPost(
			{commit,dispatch},
			data={
				formData:{},
				config: {},
			},
		){
			commit('IS_LOADING',true)
			commit('SET_FORMDATA',data.formData);
			const formData =  await dispatch('makeFormData');
			return axios.post(
				window['n2'].ajaxurl,
				formData,
				data.config,
			).then(resp=>
				{
					commit('IS_LOADING',false)
					return resp;
				});
		},
		ajaxPostMulti(
			{dispatch},
			data={
				formData:{},
				config: {},
			},
		){
			return dispatch('ajaxPost',data).then(resp=>{
				let count = 0;
				Object.values(resp.data).forEach((res: any) => {
					if (!res.success) {
						const parser = new DOMParser();
						const xmlDoc = parser.parseFromString(res.body, "text/xml");
						const message = xmlDoc.querySelector("message").textContent;
						alert(message);
					} else {
						++count;
					}
				});
				const alertMessage = [
					count + "件の処理が完了しました。",
					"画像の登録、更新、削除後の情報が反映されるまでの時間は最短10秒です。",
				];
				alert(alertMessage.join("\n"));
			});
		},
		ajaxPostSelectedFileIds(
			{state,dispatch},
			data={
				formData:{},
				config: {},
			},
		){
			state.selectedFiles.forEach((fileId:string, i:number) => {
				data.formData[`fileIds[${i}]`] = fileId;
			});
			return dispatch('ajaxPostMulti',data).then((resp)=>{
				dispatch('updateFiles', state.selectedFolder);
				return resp;
			});
		},
		selectionAllFiles({commit,getters}){
			const fileIds = getters.filterFiles.map(file=> file.FileId); 
			commit('ADD_ALL_SELECTED_FILE_ID',fileIds);
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
		},
	}
});