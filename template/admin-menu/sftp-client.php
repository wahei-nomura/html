<?php
/**
 * SFTP CLIENT VIEW
 *
 * @package neo-neng
 */

$folders = N2_RMS_Cabinet_API::ajax(
	array(
		'request' => 'folders_get',
		'mode'    => 'func',
	),
);

if ( ! $folders ) {
	echo 'RMS CABINETに接続できませんでした。';
	die();
}
$tree = array();

// sort
usort(
	$folders,
	function( $a, $b ) {
	return $a['FolderPath'] > $b['FolderPath'];
	}
);

$folders = array_map(
	function( $folder ) {
	$folder['FolderPath'] = explode( '/', ltrim( $folder['FolderPath'], '/' ) );
	return $folder;
	},
	$folders
);

$root = array_shift( $folders );

// 再帰的にtreeを生成
$build_tree = function ( &$parent, $path ) use ( &$build_tree ) {
	$p            = array_shift( $path );
	$parent[ $p ] = $parent[ $p ] ?? array();
	if ( $path ) {
		$build_tree( $parent[ $p ], $path );
	}
};

$folders = array_map(
	function( $f ) use ( &$tree, $build_tree ) {
	$build_tree( $tree, $f['FolderPath'] );

	$f['FolderPath'] = '/' . implode( '/', $f['FolderPath'] );
	return $f;
	},
	$folders
);

$root['FolderPath'] = '/' . implode( '/', $root['FolderPath'] );

$tree = array(
	$root['FolderName'] => $tree,
);

$folders = array( ...$folders, $root );


$tree_list = function ( $parent, $path = null ) use ( &$tree_list, $folders ) {
	?>
	<ul <?php echo ( null !== $path ) ? ' class="invisible"' : ''; ?>>
	<?php foreach ( $parent as $li => $child ) : ?>
		<?php
			$dir    = null !== $path ? "{$path}/{$li}" : '';
			$index  = array_search( $dir ?: '/', array_column( $folders, 'FolderPath' ), true );
			$folder = $folders[ $index ];
		?>
		<?php if ( $child ) : ?>
		<li class="hasChirdlen">
		<?php else : ?>
		<li>
		<?php endif; ?>
			<span data-path="<?php echo esc_attr( $dir ?: '/' ); ?>" data-id="<?php echo esc_attr( $folder['FolderId'] ); ?>">
				<i class="bi bi-folder2-open close"></i><?php echo esc_html( $folder['FolderName'] ); ?>
			</span>
		<?php if ( $child ) : ?>
			<?php $tree_list( $child, $dir ); ?>
		<?php endif; ?>
		</li>        
	<?php endforeach; ?>
	</ul>
	<?php
};

?>
<div id="ss-cabinet" class="container">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<h2>CABINET</h2>
	<div class="row row-cols-1 row-cols-md-2  border-top border-dark">
		<div class="tree overflow-auto col-3">
			<?php $tree_list( $tree ); ?>
		</div>
		<div id="ss-cabinet-images" class="overflow-auto border-start border-dark col-9 d-flex align-content-start justify-content-start align-items-start flex-wrap"></div>
	</div>
	<div id="card-template" style="display:none;">
		<div class="card shadow text-center me-2">
			<img src="" class="card-img-top" alt="">
			<div class="card-body">
				<h6 class="card-title text-truncate"></h6>
				<p class="card-text">フォルダは空です。</p>
			</div>
		</div>
	</div>
</div>

<script>
	jQuery( function($){

		const get_files = async (id) => {
			return await $.ajax({
				url : window['n2']['ajaxurl'],
				type : 'GET',
				data : {
					action : 'n2_rms_cabinet_api_ajax',
					request : 'files_get',
					mode : 'json',
					folderId : id,
				},
			});
		}

		// 
		const top = $('#ss-cabinet .row').offset().top;
		const left = $('#ss-cabinet .row').offset().left;
		const $tree = $('.tree')
		$tree.on('click','li > span', async function(event){
			const icons = ['spinner-border spinner-border-sm','bi bi-folder2-open'];
			if (event.target === this) {
				$(this).toggleClass('close').siblings('ul').toggleClass('invisible');

				const $cardGroup = $('#ss-cabinet-images');
				$cardGroup.css({
					height: `calc(100vh - ${top}px )`,
				})
				$('.tree').css({
					height: `calc(100vh - ${top}px )`,
				})
				if( $(this).hasClass('active') ) {
					return true;
				} else {
					$('span.active').removeClass('active');
					$(this).children('i').attr('class', icons[0] );
					$cardGroup.addClass('loading');
				}
				
				$(this).addClass('active');
				const res = await get_files($(this).data('id'));
				$cardGroup.empty();
				console.log(res.length,res);
				
				res.forEach( async file => {
					const $card = $('#card-template .card').clone(false);
					let url = file['FileUrl'];
					if ( ! url ) {
						$card.addClass('flex-fill');
						$card.find('img').remove();
						$card.css('max-width','100%');
						$cardGroup.append( $card );
						return;
					}
					url = url.replace('image.rakuten.co.jp','thumbnail.image.rakuten.co.jp/@0_mall');
					url += '?_ex=137x137';
					$card.find('img').attr('src', url );
					$card.find('.card-title').text(file['FileName']);
					$card.find('.card-text').text(file['FilePath']);
					$cardGroup.append( $card );
				});
				$cardGroup.removeClass('loading');
				$(this).children('i').attr('class', icons[1] );
			}
		})
		$tree.find('li > span').eq(0).trigger('click');
	})
</script>

<style>
	#ss-cabinet {
		position: relative;
		#ss-cabinet-images {
			&.loading {
				animation: loading 3s infinite;
			}
			.card { /* Bootstrapを上書き */
				width: 18rem;
				max-width: 160px;
				padding: 0.5rem 0.7rem 1rem;
			}
			.card-img-top {
				height: 136px;
				width: 136px;
				object-fit: contain;
			}
			.card-body {
				padding: 1rem .3rem;
			}
			.card-text {
				text-wrap: nowrap;
			}
		}
		.tree {/*親要素*/
			position: relative;
			background: white;
			padding: 30px;
			font-size: .85rem;
			font-weight: 400;
			line-height: 1.5;
			color: #212529;
			& ul {
				padding-left: 5px;
				list-style: none;
				& li {
					position: relative;
					padding-top: 5px;
					padding-bottom: 5px;
					padding-left: 10px;
					box-sizing: border-box;
					margin-bottom: 0px;
					& .bi {
						padding-right: 5px;
					}
					&.hasChirdlen{
						> span{
							&:before {
								content: '';
								position: absolute;
								width: 5px;
								height: 5px;
								border: 1px solid #000;
								border-left: none;
								border-top: none;
								top: 15px;
								left: 0px;
								transform: translate(-50%,-50%) rotate(-45deg);
							}
							&.close {
								&:before {
									transform: translate(-50%,-50%) rotate(45deg);
								}
							}
						}
					}
					&:last-child:after {/*これ以下は別フォルダになる事を明示する為、少しだけ離す*/
						height: 15px;
					}
					
				}
			}
			& span {/*ファイル名部分*/
				cursor: pointer;
				&.active {
					background-color: #00f2;
				}
				
			}
		}
	}
	@keyframes loading {
		0%, 100% {
			background-color: #fff;
		}
		50% {
			background-color: #0002;
		}
	}
</style>
