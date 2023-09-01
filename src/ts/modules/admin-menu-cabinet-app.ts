import Vue from 'vue/dist/vue.min';
import Vuex,{mapActions,mapMutations} from 'vuex/dist/vuex.min';
import Main from './admin-menu-cabinet-main'
import Header from './admin-menu-cabinet-header'
import LeftAside from './admin-menu-cabinet-left-aside'
import RightAside from './admin-menu-cabinet-right-aside'
import $ from 'jquery';

Vue.use(Vuex);
	
export default Vue.extend({
		name: 'App',
		data() {
		},
		methods:{
			updateOffsetTop(){
				const top = $(this.$refs.body).offset().top;
				const paddingBottom = window.getComputedStyle(document.getElementById("wpbody-content")).paddingBottom;
				const offset = top + Number(paddingBottom.replace(/[^0-9]/g,""));
				this.$store.commit('SET_OFFSET_HEIGHT',Math.ceil(offset) + 1);
			},
		},
		mounted(){
			this.updateOffsetTop();
			window.addEventListener('resize', this.updateOffsetTop);
		},
		beforeDestroy() {
			// イベントリスナーを削除
			window.removeEventListener('resize', this.updateOffsetTop);
		},
		components: {
			Main,
			Header,
			LeftAside,
			RightAside,
		},
		template:`
			<div>
				<Header/>
				<div ref="body" class="row row-cols-1 row-cols-md-2 border-top border-dark">
					<LeftAside/>
					<Main/>
					<RightAside/>
				</div>
			</div>
			`
	});
	
