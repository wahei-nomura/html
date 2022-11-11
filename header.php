<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>NEO NENG(仮)【<?php bloginfo( 'name' ); ?>】</title>

	<meta name="viewport" content="width=device-width">
	<!-- google Material -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
	<!-- Bootstrap5 -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
	<?php wp_head(); ?>
</head>
<body>
<?php wp_body_open(); ?>

<header class="p-3 bg-dark text-white text-center">
	<h1><a href="<?php echo empty( $_GET['look'] ) ? home_url() : ''; ?>" style="text-decoration:none;color:inherit">NEO NENG(仮)【<?php bloginfo( 'name' ); ?>】</a></h1>
</header>
