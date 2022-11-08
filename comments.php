<?php

/**
 * comments.php
 *
 * @package neoneng
 */

?>

<?php if ( comments_open() ) : ?>
	<div id="comments">
		<p>コメント</p>
		<?php if ( have_comments() ) : ?>
			<ol>
				<?php wp_list_comments(); ?>
			</ol>
		<?php endif; ?>
		<?php comment_form(); ?>
	</div>
<?php endif; ?>
