import Vue from 'vue/dist/vue.min';
import Vuex ,{mapState,mapActions} from 'vuex/dist/vuex.min';
import axios from 'axios';

Vue.use(Vuex);

export default Vue.extend({
    name: 'RightAside',
    data() {
        return {
            maxSpace : null,
            availSpace : null,
			isActive: false,
			activeTimer : null,
        };
    },
	computed:{
		useSpaceRate(){
			return Math.round( ( 1 - this.availSpace / this.maxSpace ) * 100 * 10 ) / 10 || 0;
		},
		...mapState([
			'selectedFile',
			'n2nonce',
		]),
		thumbnailUrl(){
			if (this.selectedFile) {
				return this.selectedFile?.FileUrl.replace(
					"image.rakuten.co.jp",
					"thumbnail.image.rakuten.co.jp/@0_mall"
				) + "?_ex=200x200";
			}
		},
		formatTimeStamp(){
			if(this.selectedFile) {
				return this.selectedFile.TimeStamp.split(/\s/)[0].replace(/-/g, "/");
			}
		},
	},
	async created(){
		await this.updateUsage();
	},
    methods: {
		...mapActions([
			'showModal',
		]),
        // フォルダを選択するメソッド
        selectFolder(folder) {
            this.selectedFolder = folder;
        },
		async updateUsage(){
			this.$store.commit('SET_FORMDATA',{
				call: "usage_get",
			})
			const formData = await this.$store.dispatch('makeFormData');
			return await axios.post(
				window['n2'].ajaxurl,
				formData,
			).then(resp=>resp.data.cabinetUsageGetResult)
			.then(result=>{
				this.maxSpace = ( result.MaxSpace / 1000 );
				this.availSpace = Math.round( result.AvailSpace / 1000 / 1000 * 10 ) / 10;
			});
		},
		copiedLink(){
			this.isActive = true;
			if( this.activeTimer ) {
				clearTimeout(this.activeTimer);
			}
			navigator.clipboard.writeText(this.selectedFile.FileUrl);
			this.activeTimer = setTimeout(()=>{
				this.isActive = false;
				this.activeTimer = null;
			},1000);
		}
    },
	template:`
		<aside id="right-aside" class="col-3 pt-3" :class="{'d-none': !selectedFile}">
			<div>
				<div class="progress">
					<div class="progress-bar" role="progressbar" :style="'width:' + useSpaceRate + '%'" :aria-valuenow="useSpaceRate" aria-valuemin="0" aria-valuemax="100">{{useSpaceRate}}%</div>
				</div>
				<div class="text-end" style="font-size: .8rem;">空き容量 : {{availSpace}}GB / {{maxSpace}}GB</div>
			</div>
			<div class="card p-0">
				<img @click="showModal('image')" id="right-aside-list-img" class="card-img-top"
					data-bs-toggle="modal" data-bs-target="#CabinetModal" role="button" decoding=“async”
					:src="thumbnailUrl" :alt="selectedFile?.FileName"
				>
				<div class="card-body p-0">
					<ul id="right-aside-list" class="list-group list-group-flush">
						<li class="list-group-item" data-label="画像名" data-key="FileName">
							{{selectedFile?.FileName}}
						</li>
						<li class="list-group-item" data-label="ファイル名" data-key="FilePath">
							{{selectedFile?.FilePath}}
						</li>
						<li class="list-group-item" data-label="登録/変更日" data-key="TimeStamp">
							{{formatTimeStamp}}
						</li>
						<li class="list-group-item" data-label="サイズ" data-key="FileSize">
							{{selectedFile?.FileSize}}
						</li>
						<li class="list-group-item d-flex align-items-center justify-content-between" data-label="画像保存先" data-key="FileUrl">
							<button type="button" @click="copiedLink" class="url-clipboard btn btn-secondary" :class="{active: isActive}" >
								<i class="bi bi-clipboard"></i>
							</button>
						</li>
					</ul>
				</div>
			</div>
		</aside>
	`
});