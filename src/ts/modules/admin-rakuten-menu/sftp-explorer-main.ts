import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions,mapMutations} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'Main',
	data(){
		return {
			table:{
				header:{
					name:'ファイル名',
					size: 'サイズ',
				},
			},
			selectedFile:{},
			selectAll:false,
			currentFileContens: '',
		}
	},
	computed:{
		...mapState([
			'currentDir',
			'currentFile',
		]),
		hasFiles(){
			const children = this.currentDir.children;
			if( Array.isArray( children ) ) return false;
			return Object.keys( children ).filter(key=>children[key].type ==='f').length > 0;
		},
		splitFileContens(){
			if( !this.currentFileContens) return;
			return this.currentFileContens.trim().replace(/,/g,'\t');
			// return this.csv2arr(this.currentFileContens)
		},
		isSelectedFile(){
			return Object.entries(this.selectedFile).some(([_,selected])=>{
				return selected;
			});
		},
	},
	methods:{
		...mapMutations([
			'SET_CURRENT_FILE',
			'SET_LOADING',
		]),
		...mapActions([
			'sftpRequest',
			'refleshDir',
		]),
		handleFileAreaClick(){
			this.$refs.file.click();
		},
		handleFileDrop(e){
			const files =  e.dataTransfer.files;
			if ( ! files.length ) {
				return;
			}
			this.upload(files);
			this.$refs.file.value = null;
		},
		handleFileChange(){
			const files = this.$refs.file.files
			if ( ! files.length ) {
				return;
			}
			this.upload(files);
			this.$refs.file.value = null;
		},
		async upload(files){
			this.SET_LOADING({is:true,status:'転送中...'});
			const config = {
				headers: {
					'Content-Type': 'multipart/form-data'
				},
			};
			const data = {
				judge:'upload',
				path: this.currentDir.path,
			};
			Object.keys(files).forEach(i=>{
				data[`sftp_file[${i}]`] = files[i];
			});

			await this.sftpRequest({data,config}).then( res=>{
				console.log(res);
				this.refleshDir(this.currentDir.path);
				this.selectAll = false;
			});
		},
		async deleteFiles(){
			if(!confirm('選択中のファイルを削除します。続けますか？')) return;
			if(!confirm('本当に削除しますか？この操作は元に戻せません。')) return;
			this.SET_LOADING({is:true,status:'削除中...'});
			const target = Object.entries(this.selectedFile)
				.filter(([_,value])=>value)
				.map(([file,_])=>`${this.currentDir.path}/${file}`);
			const data = {
				judge:'delete',
			};
			target.forEach((_,i)=>{
				data[`paths[${i}]`] = target[i];
			});
			await this.sftpRequest({data}).then(res=>{
				console.log(res);
				this.refleshDir(this.currentDir.path);
				this.selectAll = false;
			});
		},
		async downloadFiles(){
			const target = Object.entries(this.selectedFile)
				.filter(([_,value])=>value)
				.map(([file,_])=>file);
			const data = {
				judge:'download',
				path: this.currentDir.path,
			};
			const config = {
				responseType:'blob',
			};
			target.forEach((_,i)=>{
				data[`files[${i}]`] = target[i];
			});
			this.SET_LOADING({is:true,status:'DL中...'});
			// 一覧
			await this.sftpRequest({data,config}).then( res => {
				const url = URL.createObjectURL(res.data);
				// `<a>`タグを作成し、ダウンロードリンクとして使用します。
				const a = document.createElement('a');
				const timestamp = new Date().toLocaleDateString('sv-SE',{
					year: 'numeric',
					month: '2-digit',
					day: '2-digit',
					hour: '2-digit',
					minute: '2-digit',
				});
				a.href = url;
				a.download = `【${window['n2'].town}】楽天SFTP_${timestamp}.zip`;  // ダウンロードされるファイル名を指定します。
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				this.SET_LOADING({is:false,status:'DL完了'});
			});
		},
		async reflesh(){
			this.refleshDir(this.currentDir.path);
		},
		async getContents(){
			const data = {
				judge: 'get_contents',
				path: `${this.currentDir.path}/${this.currentFile.name}`
			};
			this.currentFileContens = await this.sftpRequest({data}).then(res=>{
				return res.data
			});
		},
		formatSize(byte){
			if( byte >> 10 < 1 ) return byte.toFixed(1) + 'B';
			if( byte >> 20 < 1 ) return ((byte >> 7) / 8).toFixed(1) + 'KB';
			if( byte >> 30 < 1 ) return ((byte >> 17) / 8).toFixed(1) + 'MB';
			else 				 return ((byte >> 27) / 8).toFixed(1) + 'GB';
		},
		csv2arr(str: string){
			const arr = str.split(/("\n)/g);
			return arr.map((val, index) => {
				if (!val) {
					return '';
				}
				return val.replace(/"/g, "").split(/,/);
			}).filter(x=> x && x[0] !== '\n');
		},
	},
	watch:{
		currentDir(newDir,oldDir){
			if(JSON.stringify(newDir) !== JSON.stringify(oldDir)){
				const children = newDir.children;
				// 初期化
				if( Array.isArray( children ) ) this.selectedFile = {};
				else this.selectedFile = Object.keys( children ).filter(key=>children[key].type ==='f').reduce((obj,key)=>{
					if(key)	obj[key] = false;
					return obj;
				},{});
				this.SET_CURRENT_FILE(null);
			}
		},
		selectAll(newVal,_){
			this.selectedFile = Object.keys(this.selectedFile).reduce((obj,key)=>{
				if(key)	obj[key] = newVal;
				return obj;
			},{});
			if ( ! newVal ) {
				this.SET_CURRENT_FILE(null);
			}
		}
	},
	template:`
	<main class="d-flex flex-column justify-content-between">
		<div popover id="popover-file-contents" class="p-4"
			 style="width: 80%; height: 80%; overflow:hidden;"
		>
			<textarea class="w-100 h-100" v-text="splitFileContens">
			</textarea>
		</div>
		<div>
			<nav class="navbar navbar-light bg-light px-2 position-sticky top-0 start-0 align-items-strech">
				<div class="btn btn-outline-secondary rounded-pill px-4 py-0"
					@click="reflesh"
				>
					更新
				</div>
				<div class="d-flex ms-auto">
					<div class="d-flex align-items-center gap-2">
						<span>選択したファイルを</span>
						<div class="btn btn-outline-secondary rounded-pill px-4 py-0"
							:class="{disabled:!isSelectedFile}"
							@click="downloadFiles"
						>
							DL
						</div>
						<div class="btn btn-outline-danger rounded-pill px-4 py-0"
							:class="{disabled:!isSelectedFile}"
							@click="deleteFiles"
						>
							削除
						</div>
					</div>
				</div>
			</nav>
			<table class="table table-hover">
				<thead>
					<tr>
						<th><input type="checkbox" v-model="selectAll"></th>
						<th v-for="(label,th) in table.header">{{label}}</th>
						<th>最終更新日</th>
					</tr>
					</thead>
				<tbody>
					<template v-if="currentDir.children && hasFiles">
						<tr v-for="(meta,child) in currentDir.children" v-if="meta.type ==='f'">
							<td><input type="checkbox" v-model="selectedFile[meta.name]"></td>
							<template v-for="(label,th) in table.header" v-if="meta[th]">
								<td v-if="th==='size'">{{formatSize(meta[th])}}</td>
								<td v-else-if="th==='name' && meta[th].includes('.csv')">
									<button class="btn btn-info btn-sm text-light"
										 @click="SET_CURRENT_FILE(meta); getContents()"
										 popovertarget="popover-file-contents"
									>
										{{meta[th]}}
									</button>
								</td>
								<td v-else>{{meta[th]}}</td>
							</template>
							<td>{{meta.lastmod + ' ' + meta.time}}</td>
						</tr>
					</template>
					<template v-else>
						<tr>
							<td colspan="4" class="text-center">no files<td>
						</tr>
					</template>
				</tbody>
			</table>
		</div>
		<div @click="handleFileAreaClick" @drop.prevent="handleFileDrop" @dragover.prevent
			class="dragable-area p-5 mt-3 border border-5 text-center w-100 position-sticky bottom-0 end-0 bg-light"
		>
			ファイルをドラッグ&ドロップで転送する
			<form style="display:none;">
				<input ref=file @change="handleFileChange" type="file" multiple="multiple" class="d-none">
			</form>
		</div>
	</main>
	`,
});