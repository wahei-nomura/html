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
		<h2>事業者とスチームシップ</h2>
		<?php if ( have_comments() ) : ?>
			<ol>
				<?php wp_list_comments(); ?>
			</ol>
		<?php endif; ?>
		<?php
			$args = array(
				'comment_field' => '
						<p class="comment-form-comment">
							<label>内容</label>
							<textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea>
						</p>
					'
				,
			);
			comment_form( $args );
		?>
	</div>
<?php endif; ?>
