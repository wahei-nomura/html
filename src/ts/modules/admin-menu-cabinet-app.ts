import Vue from 'vue/dist/vue.min';
import Vuex,{mapActions,mapMutations} from 'vuex/dist/vuex.min';
import Main from './admin-menu-cabinet-main'
import Modals from './admin-menu-cabinet-modals'
import Header from './admin-menu-cabinet-header'
import LeftAside from './admin-menu-cabinet-left-aside'
import RightAside from './admin-menu-cabinet-right-aside'
import $ from 'jquery';

Vue.use(Vuex);
	
export default Vue.extend({
		name: 'App',
		data() {
			return {
				message:'test',
			};
		},
		methods:{
			buildTree(){
				const tree = {};
				const root = this.folders.shift();
			
				// Recursive function to build the tree
				const buildTreeRecursive = (parent, path) => {
					const p = path.shift();
					parent[p] = parent[p] || {};
					if (path.length) {
						buildTreeRecursive(parent[p], path);
					}
				};
			
				// Build the tree structure for each folder
				this.folders = this.folders.map(f => {
					buildTreeRecursive(tree, [...f.FolderPath]); // Using a spread operator to avoid modifying the original array
					f.FolderPath = '/' + f.FolderPath.join('/');
					return f;
				});
			
				root.FolderPath = '/' + root.FolderPath.join('/');
			
				const finalTree = {
					[root.FolderName]: tree
				};
				this.folders = [ root, ...this.folders ];
			
				return finalTree;
			},
			initFiles() {
	
			},
			dropFiles(e) {
				if ( this.isClick || ! e.originalEvent.dataTransfer.files.length ) {
					return;
				}
				e.preventDefault();
	
				// ドロップされたファイルを取得
				const files = e.originalEvent.dataTransfer.files;
				const $form = $(this).find("form");
				$form.find('input[type="file"]').prop("files", files);
				const formData = new FormData($form[0] as HTMLFormElement);
	
				// アップロード
				$.ajax({
					url: window["n2"]["ajaxurl"],
					type: "POST",
					data: formData,
					processData: false, // FormDataを処理しないように設定
					contentType: false, // コンテンツタイプを設定しないように設定
				}).then(async (response) => {
					let faildCount = 0;
					Object.keys(response).forEach((index) => {
						const res = response[index];
						if (!res.success) {
							++faildCount;
							const xmlDoc = $.parseXML(res.body);
							const message = $(xmlDoc).find("message").text();
							alert(message);
						}
					});
					const alertMessage = [
						Object.keys(response).length -
							faildCount +
							"件アップロードが完了しました。",
						"画像の登録、更新、削除後の情報が反映されるまでの時間は最短10秒です。",
					];
					$(this).removeClass("dragover")
					alert(alertMessage.join("\n"));
				});
			},
			setModal(e){
				this.modal = $(e.target).attr('name');
			},
		},
		components: {
			Main,
			Header,
			Modals,
			LeftAside,
			RightAside,
		},
		template:`
			<div>
				<Header/>
				<div ref="body" :style="" class="row row-cols-1 row-cols-md-2 border-top border-dark">
					<LeftAside/>
					<Main/>
					<RightAside/>
				</div>
				<Modals/>
			</div>
			`
	});
	
