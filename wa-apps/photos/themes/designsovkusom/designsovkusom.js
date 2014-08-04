$(document).ready(function(){
    $("#search").focus(function(){
        $(".searchform").addClass("active_search");
    });
    
    $("#search").focusout(function(){
        $(".searchform").removeClass("active_search");
    });
    
    $("#back-top").hide();

	$(window).scroll(function (){
		if ($(this).scrollTop() > 100){
			$("#back-top").fadeIn();
		} else{
			$("#back-top").fadeOut();
		}
	});

	$("#back-top a").click(function (){
		$("body,html").animate({
			scrollTop:0
		}, 800);
		return false;
	});
	
	function checkCount(){
        var viewed = $.cookie('dsvviewed');
        var compare = $.cookie('shop_compare');
        if(viewed){
            viewed = viewed.split(',');
            if(viewed.length > 0){
                $("#count-viewed .count").html(viewed.length);
                $("#count-viewed").addClass("highlight");
            }
        }

        if(compare){
            compare = compare.split(',');
            if(compare.length > 0){
                $("#count-compare .count").html(compare.length);
                $("#count-compare").addClass("highlight");
            }
        }

        checkWishCount();

    }

    function checkWishCount(){
        var wishlist = $.cookie('dsvwishlist');
        if(wishlist){
            wishlist = wishlist.split(',');
            if(wishlist.length>0){
                $("#count-wishlist").addClass("highlight")
                $("#count-wishlist .count").html(wishlist.length);
            }
        }
        else {
            $("#count-wishlist").removeClass("highlight")
            $("#count-wishlist .count").html('0');
        }
    }
    checkCount();
});