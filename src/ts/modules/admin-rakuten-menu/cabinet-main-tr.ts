import Vue from 'vue/dist/vue.min';
import {mapState,mapGetters,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	name: 'TableRow',
	data(){
		return {
			url: this.image.FileUrl,
			thumbnailSize: "?_ex=50x28",
		};
	},
	props:{
		image:{
			type: Object,
			required: true,
		}
	},
	computed:{
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
		formatTimeStamp(){
			return this.image.TimeStamp.split(/\s/)[0].replace(/-/g, "/");
		},
		isSelected(){
			return this.$store.state.selectedFiles.includes(this.image.FileId)
		}
	},
	methods:{
		...mapActions([
			'showModal',
			'toggleFileSelection',
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
			this.toggleFileSelection(this.image.FileId);
		},
	},
	template:`
	<tr @click.stop="focus" :class="{'table-active':checkSelected()}">
		<td>
			<input type="checkbox" name="selected" :checked="isSelected" @click.stop @change="toggleSelection">
		</td>
		<td v-if="!isTrashBox">
			<img @click="showModal('image')" class="cabinet-img" :src="thumbnailUrl" :alt="image.FileName">
		</td>
		<td>{{image.FileName}}</td>
		<td data-label="サイズ">{{image.FileSize}}</td>
		<td>{{formatTimeStamp}}</td>
	</tr>
	`
})