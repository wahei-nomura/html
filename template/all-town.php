<?php
/**
 * template/all-town.php
 *
 * @package neoneng
 */

$all_site = get_sites();
	foreach ( $all_site as $site ) :
		$site_details = get_blog_details( $site->blog_id );
		if ( '1' !== $site->blog_id ) :
?>
	<p><a href="<?php echo $site_details->siteurl; ?>" target="_blank"><?php echo $site_details->blogname; ?></a></p>
<?php
		endif;
	endforeach;
