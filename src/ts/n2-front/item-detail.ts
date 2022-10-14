import jQuery from "jquery";
// jQuery拡張用に型定義
declare global{
    interface JQuery{
        animate2(properties:Object, duration:number, ease:String): void;
        imgResize(): void;
    }
}
export default () => {
    jQuery(function($){
        // transformの各パラメータ
        // transform用のアニメーション
        $.fn.animate2 = function (properties:Object, duration:number, ease:String):void {
           ease = ease || 'ease';
           var $this = this;
           var cssOrig = { transition: $this.css('transition') };
           return $this.queue(next => {
               properties['transition'] = 'all ' + duration + 'ms ' + ease;
               $this.css(properties);
               setTimeout(function () {
                   $this.css(cssOrig);
                   next();
               }, duration);
           });
        };
        // 画像サイズの小数点以下を切り捨て
        $.fn.imgResize = function(){
           jQuery(this).each(function(){
               jQuery(this).css({
                   width:'',
                   height:''
               }).css({
                   width:Math.floor(jQuery(this).width()),
                   height:Math.floor(jQuery(this).height())
               })
           })
           jQuery('.sub-imgs').css({
               
               transform: function(){
                   return `translate(${ -jQuery('.sub-imgs').data('count') * (10 + jQuery('.sub-img').width())}px,0)`
               }()
           })
        }
        const transform = () => {
            let matrix = {};
            if('none' !== jQuery('.sub-imgs').css('transform')){
                const transform = jQuery('.sub-imgs').css('transform').split('(')[1].split(')')[0].split(', ')
                if( 6 === transform.length ) {
                    matrix = {
                        'scale-x':transform[0],
                        'rotate-p':transform[1],
                        'rotate-m':transform[2],
                        'scale-y':transform[3],
                        'translate-x':transform[4],
                        'translate-y':transform[5]
                    };
                } else if ( 16 === transform.length ) {
                    matrix = {
                        'scale-x':transform[0],
                        'rotate-z-p':transform[1],
                        'rotate-y-p':transform[2],
                        'perspective1':transform[3],
                        'rotate-z-m':transform[4],
                        'scale-y':transform[5],
                        'rotate-x-p':transform[6],
                        'perspective2':transform[7],
                        'rotate-y-m':transform[8],
                        'rotate-x-m':transform[9],
                        'scale-z':transform[10],
                        'perspective3':transform[11],
                        'translate-x':transform[12],
                        'translate-y':transform[13],
                        'translate-z':transform[14],
                        'perspective4':transform[15]
                    };
                }
            }
            return matrix;
        }
       
    
        jQuery('.sub-img').on('click',function(){
            jQuery('.main-img').attr('src',jQuery(this).attr('src'))
        })
        jQuery('.mordal-btn').on('click',function(){
            jQuery('.is-mordal').removeClass('is-mordal')
            console.log(jQuery(this).next());
            
            let html = jQuery(this).next().clone(false).show().prop('outerHTML');
            let class_name = jQuery(this).attr('class');
            console.log(class_name);
            jQuery(this).addClass('is-mordal')
            jQuery('.mordal').css({opacity:0}).show().find('.mordal-wrapper').html(html)
            jQuery('.mordal').animate({opacity:1},500)

            console.log($(this).parent().hasClass('item-detail'));
            
            if($(this).parent().hasClass('item-detail')){
                jQuery('.mordal').find('.mordal-wrapper').children().prepend($('.description').clone(false));
            }
            jQuery('body').css('overflow-y', 'hidden')
        })
        jQuery('.mordal').on('click',function(){
            jQuery(this).hide()
            jQuery('body').css('overflow-y','')
        })
        jQuery('.mordal').on('click','*', function(e){
            e.stopPropagation();
            if(jQuery(this).hasClass('close-btn')){
                $(this).addClass('close');
                console.log('close',this);
                $('.mordal').delay(100).animate({opacity:0},700)
                setTimeout(()=>{
                    jQuery('.mordal').hide()
                    jQuery('body').css('overflow-y','')
                    $(this).removeClass('close');
                },1000)
            }
        })
        // 画像が選択状態か判断
        let mousedown_flg = false;
        jQuery('.sub-imgs').on('mousedown',function(){
            mousedown_flg = true;
        })
        jQuery(window).on('mouseup dragend',function(){
            mousedown_flg = false;
        });
        jQuery(window).resize(function(){
            jQuery('.sub-img').imgResize();
        })
        window.setInterval(
            function(e){
                if ( ! mousedown_flg ) {
                    jQuery('.sub-imgs').css(function(){
                        if (jQuery('.sub-img').length / 2 === Math.floor(- Number(transform()['translate-x'])/ (jQuery('.sub-img').width() + 10))) {
                            return {
                                'transform': 'translate(0,0)',
                                'transition':  ''
                            };
                        }else{
                            return {'color':''};
                        }
                    }()).animate2({
                        transform: `translate(${ Number(transform()['translate-x']) - jQuery('.sub-img').width() - 10 }px,0)`
                    },500,'')
                    jQuery('.sub-imgs')
                    .data('count',Math.floor(- Number(transform()['translate-x'])/ (jQuery('.sub-img').width() + 10))+1)
                    .data('mx',jQuery('.sub-img').width()+10)
                    console.log(jQuery('.sub-imgs').data());
                }
        },2000)
        
        jQuery(window).on('load',function(){
            jQuery('.sub-img').imgResize();
        })
    })
}