var banners_loaded = true;
function bannersBanner(id, url){
    if (typeof jQuery != 'undefined') {
        $(document).ready(function(){
            bannersInitBanner($('#'+id));
        });
    }
    else{
        document.addEventListener('DOMContentLoaded', function(){
            if (typeof jQuery == 'undefined') {
                (function(){
                    var n = document.createElement("script"); n.type = "text/javascript";
                    n.src = url+"wa-content/js/jquery/jquery-1.8.2.min.js";
                    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(n, s);
                })();
            }
            bannersInitBanner($('#'+id));
        });
    }
}

function bannersInitBanner(element){
    var slides = element.find('.smartbs_slide');

    var effectIn = element.data('animation_in');
    var effectOut = element.data('animation_out');
    var animation_speed = element.data('animation_speed');
    var animation_delay = animation_speed*0.6;
    var slide_time = element.data('time');
    var arrow_buttons_size = element.data('arrow_buttons');
    var navigation_size = element.data('navigation');
    var button_color = element.data('button_color');
    var button_background = element.data('button_background');

    var slide_timer = null;
    var animation_process = false;
    var mouse_hover = false;
    function showSlide(next){
        var current = element.find(".smartbs_slide.current");
        element.find('.smartbs_navigation > a.current').removeClass('current');
        setAnimation(current, effectOut, function(){
            current.removeClass('current');
        });
        setTimeout(function(){
            next.addClass('current');
            setAnimation(next, effectIn, function(){
                animation_process = false;
                setSlideTimer();
            });
        }, animation_delay);
    }
    function showSlideById(id){
        if(!animation_process){
            animation_process = true;
            showSlide(slides.eq(id));
            element.find('.smartbs_navigation > a').eq(id).addClass('current');
        }
    }
    function setAnimation(slide, effect, callback){
        slide.addClass('animated '+effect);
        slide.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function(){
            slide.removeClass('animated').removeClass(effect);
            if (typeof callback == 'function') {
                callback();
            }
        });
    }
    function showNextSlide(){
        var next_index = 0;
        slides.each(function(i){
            if($(this).hasClass('current')){
                next_index = i+1;
            }
        });
        if(next_index >= slides.length){
            next_index = 0;
        }
        showSlideById(next_index);
    }
    function showPrevSlide(){
        var next_index = 0;
        slides.each(function(i){
            if($(this).hasClass('current')){
                next_index = i-1;
            }
        });
        if(next_index < 0){
            next_index = slides.length - 1;
        }
        showSlideById(next_index);
    }
    function addArrows(){
        if(arrow_buttons_size!='none'){
            var link_prev = $('<a href="#" class="smartbs_arrow smartbs_arrow_prev"> </a>');
            var link_next = $('<a href="#" class="smartbs_arrow smartbs_arrow_next"> </a>');
            link_prev.addClass('size_'+arrow_buttons_size);
            link_next.addClass('size_'+arrow_buttons_size);
            link_next.click(function(){
                showNextSlide();
                return false
            });
            link_prev.click(function(){
                showPrevSlide();
                return false
            });
            element.append(link_prev);
            element.append(link_next);
        }
    }
    function addNavigation(){
        if(navigation_size!='none'){
            var navigation_block = $('<div class="smartbs_navigation"></div>');
            navigation_block.addClass('size_'+navigation_size);
            slides.each(function(i){
                var link = $('<a href="#"></a>');
                var that = $(this);
                link.click(function(){
                    showSlideById(i);
                    return false
                });
                navigation_block.append(link);
            });
            navigation_block.find("> a:first-child").addClass('current');
            element.append(navigation_block);
        }
    }
    function setSlideTimer(){
        if(!mouse_hover){
            if(slide_timer) clearTimeout(slide_timer);
            slide_timer = setTimeout(function(){showNextSlide()}, slide_time);
        }
    }
    function addHover(){
        element.hover(function(){
            mouse_hover = true;
            if(slide_timer) clearTimeout(slide_timer);
        },function(){
            mouse_hover = false;
            setSlideTimer();
        });
    }

    slides.css({
        'animation-duration':animation_speed+'ms',
        '-webkit-animation-duration':animation_speed+'ms',
        '-moz-animation-duration':animation_speed+'ms',
        '-o-animation-duration':animation_speed+'ms'
    });
    element.find(".smartbs_slide:first-child").addClass('current');
    setSlideTimer();
    addArrows();
    addNavigation();
    addHover();
}