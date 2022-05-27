<style>
	/* パターン1 */
	.progressbar {
		margin: 40px 0;
		padding: 0;
		height: 150px;
		counter-reset: step;
		z-index: 0;
		position: relative;
	}
	.progressbar li {
		list-style-type: none;
		width: 25%;
		float: left;
		font-size: 24px;
		position: relative;
		text-align: center;
		text-transform: uppercase;
		color: #aaa;
	}
	.progressbar li:before {
		width: 120px;
		height: 120px;
		content: counter(step);
		counter-increment: step;
		line-height: 120px;
		display: block;
		text-align: center;
		margin: 0 auto 10px auto;
		border-radius: 50%;
		background-color: #eee;
	}
	.progressbar li:after {
		width: 100%;
		height: 4px;
		content: '';
		position: absolute;
		background-color: #eee;
		top: 60px;
		left: -50%;
		z-index: -1;
	}
	.progressbar li:first-child:after {
		content: none;
	}
	.progressbar li.active {
		color: #1a4899;
	}
	.progressbar li.active:before {
		background-color: #1a4899;
		color:#fff;
	}
	.progressbar li.active + li:after {
		background-color: #1a4899;
	}
</style>

<ul class="progressbar">
	<li class="">商品基本情報入力</li>
	<li class="active">スチームシップ確認作業</li>
	<li>スチームシップ確認済み</li>
	<li>ポータルサイト登録</li>
</ul>