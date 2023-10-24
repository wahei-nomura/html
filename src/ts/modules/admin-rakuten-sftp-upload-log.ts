import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	async created(){
		await this.updateSFTPLog();
	},
	computed:{
		...mapState([
			'sftpLog'
		]),
	},
	methods:{
		...mapActions([
			'updateSFTPLog'
		]),
	},
	template:`
	<div>
		<template v-if="sftpLog?.items?.length">
			sftp
		</template>
		<template v-else>
			アップロードログはありません。
		</template>
	</div>
	`,
});