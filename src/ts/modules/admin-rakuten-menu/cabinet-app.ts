import Vue from 'vue/dist/vue.min';
import Vuex,{mapActions,mapMutations} from 'vuex/dist/vuex.min';
import Main from './cabinet-main'
import Header from './cabinet-header'
import LeftAside from './cabinet-left-aside'
import RightAside from './cabinet-right-aside'

Vue.use(Vuex);
	
export default Vue.extend({
		name: 'App',
		data() {
		},
		methods:{
			updateOffsetTop(){
				const top = this.getOffsetTop(this.$refs.body);
				const paddingBottom = window.getComputedStyle(document.getElementById("wpbody-content")).paddingBottom;
				const offset = top + Number(paddingBottom.replace(/[^0-9]/g,""));
				this.$store.commit('SET_OFFSET_HEIGHT',Math.ceil(offset) + 5);
			},
			getOffsetTop(element) {
				let offsetTop = 0;
				while(element) {
					offsetTop += element.offsetTop;
					element = element.offsetParent;
				}
				return offsetTop;
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
	
