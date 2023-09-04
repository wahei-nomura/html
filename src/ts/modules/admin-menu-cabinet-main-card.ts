import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'ImageCard',
	data(){
		return {
			url: this.image.FileUrl,
			thumbnailSize: "?_ex=137x137",
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
			'isTrashBox',
		]),
		...mapGetters([
			'filterFiles',
		]),
		thumbnailUrl(){
			return this.url.replace(
				"image.rakuten.co.jp",
				"thumbnail.image.rakuten.co.jp/@0_mall"
			) + this.thumbnailSize;
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
		<div v-if="! isTrashBox" @click="focus" class="card shadow me-2" :class="{'flex-fill':!url, active:checkSelected() }" :style="!url&&maxWidth">
			<div v-if="url" class="card-header d-flex align-items-center justify-content-between">
				<input type="checkbox" name="selected" :checked="isSelected" @click.stop @change="toggleSelection">
				<span class="card-text">{{image.FileSize}}</span>
			</div>
			<img @click="showModal('image')" v-if="url" :src="thumbnailUrl" class="card-img-top cabinet-img" :alt="image.FileName">
			<div class="text-center" :class="{'card-img-overlay':url, 'card-body': !url}">
				<h6 v-if="url" class="card-title text-truncate">{{image.FileName}}</h6>
				<p v-if="url" class="card-text">{{image.FilePath}}</p>
				<p v-else class="card-text">フォルダは空です。</p>
			</div>
		</div>
	`,
});