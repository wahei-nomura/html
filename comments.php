<?php

/**
 * comments.php
 *
 * @package neoneng
 */

?>

<!-- Load sass.js -->
<script src="https://cdn.jsdelivr.net/gh/steamships/in-browser-sass/dist/in-browser-sass.bundle.min.js"></script>

<style type="text/scss">
	/* #comments {
		li {
			list-style: none;
		}
		ol {
			max-height: 600px;
			overflow: scroll;
			background-color: lightgray;
			padding: 16px;
		}
		img {
			display: none;
		}

		a {
			text-decoration: none;
			pointer-events: none;
			color: black;
		}

		.comment-body {
			border-bottom: solid 1px white;
			padding: 8px 0;
			.comment-author {
				font-size: 20px;
				color: darkblue;
			}
			.commentmetadata {

			}
			p {
				padding: 8px;
				border-radius: 4px;
				background-color: white;
			}
		}

		#respond {
			background-color: pink;
			padding: 16px;

		}
		a.comment-reply-link {
			display: none;
		}
	} */
</style>

<?php if ( comments_open() ) : ?>
	<div id="comments" class="container">
		<h2>スチームシップとこのページの返礼品について連絡コーナー</h2>

		<?php
			global $post;

			$comments = get_comments(
				array(
					'post_id' => $post->ID,
					'order'   => 'ASC',
				)
			);
		?>

		<?php if ( have_comments() ) : ?>
			<ul class='list-group mb-4 shadow'>
			<?php foreach ( $comments as $comment ) : ?>
				<li class="list-group-item list-group-item-action bg-second p-4 bg-light" id="comment-<?php echo $comment->comment_ID; ?>">
					<div class='d-flex justify-content-between text-primary'>
						<div class='fs-4'><?php echo $comment->comment_author; ?></div>
						<div><?php echo $comment->comment_date; ?></div>
					</div>
					<div class='border border-primary p-2 rounded-2 bg-white'>
						<p><?php echo $comment->comment_content; ?></p>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		$selected = selected( in_array( $_SERVER['REMOTE_ADDR'], N2_IPS ), true );
		$author = get_userdata( $post->post_author )->display_name;
		?>

		<div class='card shadow text-dark bg-light mb-3 p-4'>
			<div class='card-body'>
				<h3 class='card-title text-primary mb-5'>返礼品に関する変更要望など <small><a rel="nofollow" href="/ojika/?p=3990&amp;look=true#respond" style="display:none;">コメントをキャンセル</a></small></h3>
				<form action="<?php echo home_url( '/wp-comments-post.php' ); ?>" method="post" id="commentform" class="comment-form">
					<div class="row mb-3">
						<label class='col-sm-2 col-form-label col-form-label-sm fs-5'>内容</label>
						<div class='col-sm-10'>
							<textarea id="comment" class='form-control' name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea>
						</div>
					</div>		
					<div class="row mb-5">
						<label for="author" class='col-sm-2 col-form-label col-form-label-sm fs-5'>送信者</label>
						<div class='col-sm-6'>
							<select id="author" name="author" class='form-control'>
								<option value="<?php echo $author; ?>"><?php echo $author; ?></option>
								<option value="スチームシップ" <?php echo $selected; ?>>スチームシップ</option>
							</select>
						</div>
					</div>
					<div class="row">
						<input name="submit" type="submit" class="btn btn-outline-primary" value="コメントを送信">
						<input type="hidden" name="comment_post_ID" value="<?php echo $post->ID; ?>" id="comment_post_ID">
						<input type="hidden" name="comment_parent" id="comment_parent" value="0">
					</div>
				</form>
			</div>
		</div>
	</div>
<?php endif; ?>
