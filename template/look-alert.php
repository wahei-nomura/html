<style>

	.ledText {
		overflow: hidden;
		position: fixed;
		bottom: 0;
		z-index: 999999999;
		padding:5px 0;
		color: yellow;
		font-size: 60px;
		font-weight: bold;
		background: #333333;
		margin: 0;
	}

	/* CSS3グラデーションでドット感を出す */
	.ledText:after {
		content: ' ';
		display: block;
		position: absolute;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background-image: linear-gradient(#0a0600 1px, transparent 0px), linear-gradient(0, #0a0600 1px, transparent 1px);
		background-image: -webkit-linear-gradient(#0a0600 1px, transparent 0px), -webkit-linear-gradient(0, #0a0600 1px, transparent 1px);
		background-size: 2px 2px;
		z-index: 10;
	}

	/* CSS3アニメーションでスクロール */
	.ledText span {
		display: inline-block;
		white-space: nowrap;
		padding-left: 50%;
		-webkit-animation-name: marquee;
		-webkit-animation-timing-function: linear;
		-webkit-animation-iteration-count: infinite;
		-webkit-animation-duration: 15s;
		-moz-animation-name: marquee;
		-moz-animation-timing-function: linear;
		-moz-animation-iteration-count: infinite;
		-moz-animation-duration: 15s;
		animation-name: marquee;
		animation-timing-function: linear;
		animation-iteration-count: infinite;
		animation-duration: 15s;
	}

	@-webkit-keyframes marquee {
		from   { -webkit-transform: translate(0%);}
		99%,to { -webkit-transform: translate(-100%);}
	}
	@-moz-keyframes marquee {
		from   { -moz-transform: translate(0%);}
		99%,to { -moz-transform: translate(-100%);}
	}
	@keyframes marquee {
		from   { transform: translate(0%);}
		99%,to { transform: translate(-100%);}
	}
</style>
<p class="ledText" onclick="this.remove()"><span><b>※「修正してほしい」にした場合は商品ごとのコメント欄に入力してください。</b>　この表示はクリックすると消えます。</span></p>