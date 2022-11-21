<?php
/**
 * template/all-town.php
 *
 * @package neoneng
 */

$all_site = get_sites();
$town_datas = array();

?>

<!-- Bootstrap5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.1.js" integrity="sha256-3zlB5s2uwoUzrXK3BT7AX3FyvojsraNFxCc2vC/7pNI=" crossorigin="anonymous"></script>

<table class="table table-dark table-hover">
  <thead>
	<tr>
		<th scope="col">自治体</th>
		<th scope="col">事業者確認状況</th>
		<th scope="col">コメント状況</th>
	</tr>
  </thead>
  <tbody>
  <?php
	foreach ( $all_site as $site ) :
		$site_details = get_blog_details( $site->blog_id );
		if ( '1' !== $site->blog_id ) :
			$town_datas[ $site_details->blogname ] = $site_details->siteurl;

?>
	<tr>
		<td><a href="<?php echo $site_details->siteurl; ?>" target="_blank"><?php echo $site_details->blogname; ?></a></td>
		<td id="<?php echo $site_details->blogname; ?>"></td>
		<td></td>
	</tr>
	<?php
		endif;
	endforeach;

	$town_datas = json_encode( $town_datas );
?>
  </tbody>
</table>

<script>
	jQuery(($)=>{
		const townDatas = <?php echo $town_datas; ?>;
		// console.log(townDatas)
		for( town in townDatas ){
			console.log(town)
			$.ajax({
				url: townDatas[town] + '/wp-admin/admin-ajax.php',
				dataType: "json",
				data: {
					action: "N2_All_Town_getdata",
					townName: town,
					siteUrl: townDatas[town],
				},
			}).done(res=>{
				console.log(res)
				$(`#${res.townName}`).html(`<a href="${res.townUrl}?crew=check" target="_blank">${res.count}</a>`)
			})
		}
	})
</script>
