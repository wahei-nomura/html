import Vue from 'vue/dist/vue.min';
import {mapState,mapActions} from 'vuex/dist/vuex.min';

export default Vue.extend({
	async created(){
		await this.updateSFTPLog();
	},
	data() {
		return {
			tr: {
				id: {
					name:'ID'
				},
				upload_type: {
					name: '転送モード',
					value:{
						img_upload: '商品画像',
						csv_upload: '商品CSV',
					},
				},
				upload_log: {
					name:'ログ',
					details: true,
				},
				upload_data: {
					name:'データ',
					details: true,
				},
			}
		};
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
	<table class="table align-middle lh-1">
		<thead>
			<tr>
				<th v-for="th in tr">{{th.name}}</th>
			</tr>
		</thead>
		<tbody>
			<template v-if="sftpLog.items.length">
			<tr v-for="item in sftpLog.items">
				<td v-for="(td,meta) in tr">
					<template v-if="td?.details">
						<button type="button" :popovertarget="meta + item.id" class="button button-primary">詳細</button>
						<div popover="auto" :id="meta + item.id" style="width: 80%; max-height: 80%; overflow-y: scroll;">
							<pre>{{item[meta]}}</pre>
						</div>
					</template>
					<template v-else-if="td.value">
						{{td.value[item[meta]]}}
					</template>
					<template v-else>
						{{item[meta]}}
					</template>
				</td>
			</tr>
			</template>
			<template v-else>
			<tr>
				<td :colspan="Object.keys(tr).length">アップロードログはありません</td>
			</tr>
			</template>
		</tbody>
	</table>
	`,
});