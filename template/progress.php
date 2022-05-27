<?php
/**
 * template/progress.php
 *
 * @package neoneng
 */

	$current_status = get_post_status();

	$status_pattern = array(
		'draft'   => '商品基本情報入力',
		'pending' => 'スチームシップ確認作業',
		'publish' => '商品基本情報入力',
		'output'  => 'ポータルサイト登録', // ここはまだ未定
	);

	?>

<style>
	#neo-neng-progress-tracker {
		margin-top: 60px;
		background-color: #fff;
	}
	#neo-neng-progress-tracker ul {
		margin: 40px 0;
		padding: 24px;
		height: 150px;
		counter-reset: step;
		z-index: 0;
		position: relative;
	}
	#neo-neng-progress-tracker li {
		list-style-type: none;
		width: 25%;
		float: left;
		font-size: 24px;
		position: relative;
		text-align: center;
		text-transform: uppercase;
		color: gray;
	}
	#neo-neng-progress-tracker li:before {
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
	#neo-neng-progress-tracker li:after {
		width: 100%;
		height: 4px;
		content: '';
		position: absolute;
		background-color: #eee;
		top: 60px;
		left: -50%;
		z-index: -1;
	}
	#neo-neng-progress-tracker li:first-child:after {
		content: none;
	}
	#neo-neng-progress-tracker li.active {
		color: #1a4899;
	}
	#neo-neng-progress-tracker li.active:before {
		background-color: #1a4899;
		color:#fff;
	}
	#neo-neng-progress-tracker li.active + li:after {
		background-color: #1a4899;
	}
</style>

<div id="neo-neng-progress-tracker">
	<h2>返礼品登録進捗状況</h2>
	<ul>
		<?php foreach ( $status_pattern as $status => $text ) : ?>
			<li class="<?php echo $status === $current_status ? 'active' : ''; ?>"><?php echo $text; ?></li>
		<?php endforeach; ?>
	</ul>
</div>
