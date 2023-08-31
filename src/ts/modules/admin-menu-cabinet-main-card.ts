import Vue from 'vue/dist/vue.min';
import Vuex,{mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';
import FolderTree from './admin-menu-cabinet-folder-tree';
import { cabinetFolder,cabinetImage } from './admin-menu-cabinet-interface';

export default Vue.extend({
	name: 'ImageCard',
	data(){
		return {
			url: this.image.FileUrl,
			maxWidth: {
				"max-width": "100%",
			},
		};
	},
	props: {
		image: {
			type: Object,
			required: true
		},
	},
	computed: {
		...mapState([
			'focusFile',
			'modal',
		]),
		...mapGetters([
			'filterFiles',
		]),
		thumbnailUrl(){
			return this.url.replace(
				"image.rakuten.co.jp",
				"thumbnail.image.rakuten.co.jp/@0_mall"
			) + "?_ex=137x137";
		},
		isSelected(){
			return this.$store.state.selectedFiles.includes(this.image.FileId)
		}
	},
	methods: {
		...mapActions([
			'showModal',
		]),
		checkSelected(){
			return this.filterFiles.length && this.image.FileId === this.focusFile?.FileId;
		},
		focus(){
			if(this.filterFiles.length) {
				this.$store.commit('SET_FOCUS_FILE',this.image);
			}
		},
		toggleSelection(){
			this.$store.dispatch('toggleFileSelection',this.image.FileId);
		}
	},
	template:`
		<div @click="focus" class="card shadow me-2" :class="{'flex-fill':!url, active:checkSelected() }" :style="!url&&maxWidth">
			<div v-if="url" class="card-header d-flex align-items-center justify-content-between">
				<input type="checkbox" name="selected" :checked="isSelected" @click.stop @change="toggleSelection">
				<span class="card-text">{{image.FileSize}}</span>
			</div>
			<img @click="showModal('image')" v-if="url" :src="thumbnailUrl" class="card-img-top cabinet-img" :alt="image.FileName"
				data-bs-toggle="modal" data-bs-target="#CabinetModal" :data-file-id="image.FileId"
				data-file-size="image.FileSize" data-file-path="image.FilePath"
				data-folder-path="image.FolderPath" data-time-stamp="image.TimeStamp"
				role="button" decoding=“async”
			>
			<div class="text-center" :class="{'card-img-overlay':url, 'card-body': !url}">
				<h6 v-if="url" class="card-title text-truncate">{{image.FileName}}</h6>
				<p v-if="url" class="card-text">{{image.FilePath}}</p>
				<p v-else class="card-text">フォルダは空です。</p>
			</div>
		</div>
	`,
});