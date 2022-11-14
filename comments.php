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
	#comments {
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
			/* background-color: white; */
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
	}
</style>

<?php if ( comments_open() ) : ?>
	<div id="comments" class="container">
		<h2>スチームシップとこのページの返礼品について連絡コーナー</h2>
		<?php if ( have_comments() ) : ?>
			<ol>
				<?php wp_list_comments(); ?>
			</ol>
		<?php endif; ?>

		<?php
		global $post;
		$selected = selected( in_array( $_SERVER['REMOTE_ADDR'], N2_IPS ), true );
		$author = get_userdata( $post->post_author )->display_name;
		?>

		<div id="respond" class="comment-respond">
			<h3 id="reply-title" class="comment-reply-title">返礼品に関する変更要望など <small><a rel="nofollow" id="cancel-comment-reply-link" href="/ojika/?p=3990&amp;look=true#respond" style="display:none;">コメントをキャンセル</a></small></h3>
			<form action="<?php echo home_url( '/wp-comments-post.php' ); ?>" method="post" id="commentform" class="comment-form">
				<p class="comment-form-comment">
					<label>内容</label>
					<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea>
				</p>		
				<p class="comment-form-author">
					<label for="author">送信者</label>
					<select id="author" name="author">
						<option value="<?php echo $author; ?>"><?php echo $author; ?></option>
						<option value="スチームシップ" <?php echo $selected; ?>>スチームシップ</option>
					</select>
				</p>
				<p class="form-submit"><input name="submit" type="submit" id="submit" class="submit" value="コメントを送信">
					<input type="hidden" name="comment_post_ID" value="<?php echo $post->ID; ?>" id="comment_post_ID">
					<input type="hidden" name="comment_parent" id="comment_parent" value="0">
				</p>
			</form>
		</div>
	</div>
<?php endif; ?>
