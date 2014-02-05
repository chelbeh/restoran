$(document).ready(function () {

    // scroll-dependent animations
    $(window).scroll(function() {    
      	if ( $(this).scrollTop()>=35 ) {
            if (!$("#cart").hasClass('empty')) {
              	$("#cart").addClass( "fixed" );
            }
    	}
    	else if ( $(this).scrollTop()<30 ) {
    		$("#cart").removeClass( "fixed" );
    	}    
    });
	
	//search
	$('#search').focus(function() {	$(this).val(''); });
	$('#search').blur(function() { if($(this).val() == '') $(this).val($(this).data('place')); });
  
  
  // прокрутка страницы вверх 31,10,2013
	$("#back-top").hide();
		$(window).scroll(function () {
		    var back = $("#back-top");
			if ($(this).scrollTop() > 100) {
				back.fadeIn();
			} else {
				back.fadeOut();
			}
			//11-11-13
			if(back.position().top > $('.wrapper-bottom').position().top-140)
			    back.addClass('back-top-up');
			if(back.position().top < $('.wrapper-bottom').position().top-140)
			    back.removeClass('back-top-up'); 
			//11-11-13
		});
		// scroll body to 0px on click
		$('#back-top a').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});

});
