import $ from "jquery";
import { prefix, neoNengPath, ajaxUrl, homeUrl } from "./_functions";
import {portalScrapingAjax} from "./_front-ajax";
// jQuery拡張用に型定義
declare global {
	interface JQuery {
		animate2(properties: Object, duration: number, ease: String): void;
		imgResize(): void;
	}
}




$(function ($) {

	console.log('here');

	const element:HTMLInputElement = <HTMLInputElement>document.getElementById('product_id');

	const productID:string = element.value;
	const townName = homeUrl(window).split('/').slice(-1)[0];

	const urlSearchParams = new URLSearchParams(window.location.search);
	const getParams = Object.fromEntries(urlSearchParams.entries());
	const postID = Number(getParams.p);

	// ---------ポータル田代のインターバル関連---------
	const scrapingTimestamp = $("#scraping_timestamp").attr('value') ?? '';
	const scrapingInterval  = Number($("#scraping_interval").attr('value') ?? '0');
	const now = new Date();
	const pre = scrapingTimestamp ? new Date(scrapingTimestamp) : now;
	const minute_ago = Math.floor((now.getTime() -pre.getTime()) / (1000 *60) );
	// ----------------------------------------------
	const skElement:HTMLInputElement = <HTMLInputElement>document.getElementById('scraping_key');
	const postMetaKey = skElement.value
	const ajaxData = {
		product_id:productID,
		town_name: townName,
		post_id: postID,
		post_meta_key: postMetaKey,
	};
	portalScrapingAjax('POST', ajaxData ).done(res =>{
		// スクレイピングしてなければ何もしない
		if( res['status'] == 'NG' ) return;
		// 寄付金額の表示を楽天に変えておく
		const donationAmount = res['params']['楽天']['寄付額'] ?? $('.donation-amount .price').text();
		$('.donation-amount .price').text(donationAmount);

		// 商品画像のリンク修正
		const imgUrl:Array<string> = res['params']['楽天']['imgs'] ?? ['params']['チョイス']['imgs'];
		const imgUrlLen = imgUrl.length
		if( imgUrlLen !== 0 ) {
			const $subImgs = $('.sub-imgs');
			const $subImg = $('.sub-img').eq(0);
			const $subImgLen = $('.sub-img').length;

			imgUrl.forEach( url =>{
				console.log(url);
				
			} )
		}

		
		// 各ポータルサイトのリンク修正
		$('.link-btn a').each((index,elem)=>{
			const portalName = $(elem).text();
			$(elem).attr('href',res['params'][portalName]['url']);
		});
		let successPortal = Object.keys(res).filter((x)=>res[x].status === 'OK')
		// ポータル比較th
		$('.portal-scraper thead th').each((index,elem)=>{
			const portal = $(elem).text();
			if( index === 0 ){
				return;
			} else if ( successPortal.indexOf( portal ) !== -1 ){
				successPortal = successPortal.filter( x => x !== portal);
				return;
			}
			$(elem).parent().append(`<th>${portal}</th>`);
		})
		// ポータル比較td
		$('.portal-scraper tbody tr').each((index,elem)=>{
			const th = $(elem).find('th').text();
			successPortal.forEach(portal=>{
				const td = successPortal[portal]?.params?.[th].trim();
				$(elem).append(`<td>${td}</td>`);
			})
		});

	}).catch(err=>{
		console.log(err.responseText);
	})
	// transformの各パラメータ
	// transform用のアニメーション
	$.fn.animate2 = function (
		properties: Object,
		duration: number,
		ease: String
	): void {
		ease = ease || "ease";
		const $this = this;
		const cssOrig = { transition: $this.css("transition") };
		return $this.queue((next) => {
			properties["transition"] = "all " + duration + "ms " + ease;
			$this.css(properties);
			setTimeout(function () {
				$this.css(cssOrig);
				next();
			}, duration);
		});
	};
	// 画像サイズの小数点以下を切り捨て
	$.fn.imgResize = function () {
		$(this).each(function () {
			$(this)
				.css({
					width: "",
					height: "",
				})
				.css({
					width: Math.floor($(this).width()),
					height: Math.floor($(this).height()),
				});
		});
		$(".sub-imgs").css({
			transform: (function () {
				return `translate(${
					-$(".sub-imgs").data("count") *
					(10 + $(".sub-img").width())
				}px,0)`;
			})(),
		});
	};
	const transform = () => {
		let matrix = {};
		if ("none" !== $(".sub-imgs").css("transform")) {
			const transform = $(".sub-imgs")
				.css("transform")
				.split("(")[1]
				.split(")")[0]
				.split(", ");
			if (transform.length === 6) {
				matrix = {
					"scale-x": transform[0],
					"rotate-p": transform[1],
					"rotate-m": transform[2],
					"scale-y": transform[3],
					"translate-x": transform[4],
					"translate-y": transform[5],
				};
			} else if (transform.length === 16) {
				matrix = {
					"scale-x": transform[0],
					"rotate-z-p": transform[1],
					"rotate-y-p": transform[2],
					perspective1: transform[3],
					"rotate-z-m": transform[4],
					"scale-y": transform[5],
					"rotate-x-p": transform[6],
					perspective2: transform[7],
					"rotate-y-m": transform[8],
					"rotate-x-m": transform[9],
					"scale-z": transform[10],
					perspective3: transform[11],
					"translate-x": transform[12],
					"translate-y": transform[13],
					"translate-z": transform[14],
					perspective4: transform[15],
				};
			}
		}
		return matrix;
	};

	$(".sub-img").on("click", function () {
		$(".main-img").attr("src", $(this).attr("src"));
	});
	$(".mordal-btn").on("click", function () {
		$(".is-mordal").removeClass("is-mordal");
		console.log($(this).next());

		const html = $(this)
			.next()
			.clone(false)
			.show()
			.prop("outerHTML");
		const className = $(this).attr("class");
		console.log(className);
		$(this).addClass("is-mordal");
		$(".mordal")
			.css({ opacity: 0 })
			.show()
			.find(".mordal-wrapper")
			.html(html);
		$(".mordal").animate({ opacity: 1 }, 500);

		console.log($(this).parent().hasClass("product-info"));

		if ($(this).parent().hasClass("product-info")) {
			$(".mordal")
				.find(".mordal-wrapper")
				.children()
				.prepend($(".description").clone(false));
		}
		$("body").css("overflow-y", "hidden");
	});
	$(".mordal").on("click", function () {
		$(this).hide();
		$("body").css("overflow-y", "");
	});
	$(".mordal").on("click", "*", function (e) {
		e.stopPropagation();
		if ($(this).hasClass("close-btn")) {
			$(this).addClass("close");
			console.log("close", this);
			$(".mordal").delay(100).animate({ opacity: 0 }, 700);
			setTimeout(() => {
				$(".mordal").hide();
				$("body").css("overflow-y", "");
				$(this).removeClass("close");
			}, 1000);
		}
	});
	// 画像が選択状態か判断
	let mousedownFlag = false;
	$(".sub-imgs").on("mousedown", function () {
		mousedownFlag = true;
	});
	$(window).on("mouseup dragend", function () {
		mousedownFlag = false;
	});
	$(window).resize(function () {
		// $(".sub-img").imgResize();
		if ($(this).width() > 576) {
			$(".sub .sticky").append($(".worker, .portal-links, .related-links"));
		} else {
			$(".main").append($(".worker, .portal-links, .related-links"));
		}
	});
	window.setInterval(function (e) {
		if (!mousedownFlag) {
			$(".sub-imgs")
				.css(
					(function () {
						if (
							$(".sub-img").length / 2 ===
							Math.floor(
								-Number(transform()["translate-x"]) /
									($(".sub-img").width() + 10)
							)
						) {
							return {
								transform: "translate(0,0)",
								transition: "",
							};
						} else {
							return { color: "" };
						}
					})()
				)
				.animate2(
					{
						transform: `translate(${
							Number(transform()["translate-x"]) -
							$(".sub-img").width() -
							10
						}px,0)`,
					},
					500,
					""
				);
			$(".sub-imgs")
				.data(
					"count",
					Math.floor(
						-Number(transform()["translate-x"]) /
							($(".sub-img").width() + 10)
					) + 1
				)
				.data("mx", $(".sub-img").width() + 10);
		}
	}, 2000);

	$(window).on("load", function () {
		// $(".sub-img").imgResize();
	});

	$(window).trigger("resize");
	// PCサイズ切替
	$('.hilight-layer').on('click',function(){
		$(this).remove();
	})

	$('.portal-links').on('change','.link-btn',function(){
		const $error = $('.portal-links .link-error');
		if ( $('.portal-links .link-btn a').length > 0 ){
			$error.hide();
		}else {
			$error.show();
		}
	})
	$('.portal-links  .link-btn').trigger('change');
});

