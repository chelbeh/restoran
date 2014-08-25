$(document).ready(function () {

    $('.dialog').on('click', 'a.dialog-close', function () {
	    /*$(this).closest('.dialog').hide().find('.cart').empty();*/
	    $(this).closest('.dialog').hide().find('.dialog-window').empty().append('<div class="cart"></div>');
	    return false;
	});
    $(document).bind('keyup.dialog', function(e) { 
        if (e.keyCode == 27) { $(".dialog:visible").hide().find('.dialog-window').empty().append('<div class="cart"></div>'); }
    });
    //autofit for jQuery UI Autocomplete 1.8.2!
    $("#search.autofit, #search-m.autofit").each(function(){
    	var self = $(this);
    	self.autocomplete({
    		delay: 500,
    		minLength: 3,
    		search: function(event, ui) {
    			if($(this).val().replace(/^\s+|\s+$/g, '').length < 3){
    				$(this).autocomplete("close");
    				return false;
    			}
    		},
    		source: function(request, response){
    			request.term = request.term.replace(/^\s+|\s+$/g, '');
    			var query = request.term.replace(/\s+/g, '+');
    			$.ajax({
    				url: $.buysimply.shop_url+'search/?query='+encodeURIComponent(query),
    				type: "GET",
    				dataType: "html",
    				success: function(data){
    					var items = $.map($(data).find('.product-list li:lt('+$.buysimply.autofit_visible_item+')'), function(item){
    						var regexp = new RegExp("(" + request.term.replace(/\s+/, "|", 'g') +")", "ig");
    						var name = $(item).find('span[itemprop="name"]').text();
    						return {
    							label: name,
    							value: name,
    							url: $(item).find('span[itemprop="name"]').parent().attr('href'),
    							text: '<img width="48" height="48" src="'+$(item).find('img').attr('src').replace(/^(.*\/[0-9]+\.)(.*)(\..*)$/, '$1' + '96x96' + '$3')+'" alt=""><span class="autofit-name">'+name.replace(regexp, '<span class="match">$1</span>')+'</span><span class="autofit-price">'+$(item).find('.product-price').html()+'</span>'
    						}
    					});
    					if($(data).find('.product-list li').length > $.buysimply.autofit_visible_item) items[items.length] = {
    						label: ''+query,
    						value: ''+query,
    						url: $.buysimply.shop_url+'search/?query='+encodeURIComponent(query),
    						text: $.buysimply.locale.showall
    					}
    					response(items);
    				}
    			});
    		},
    		select: function( event, ui ) {
    			location.href = ui.item.url;
    			//return false;
    		}
    	}).data("autocomplete")._renderMenu = function( ul, items ) {
    		//var _width = Math.max(self.innerWidth()+30, 200);
    		var _width = self.innerWidth();
    		ul.addClass('autofit-product');
    		
    		$.each( items, function( index, item ) {
    			$('<li style="width: '+_width+'px;"></li>')
    				.data('item.autocomplete', item)
    				.append('<a href="'+item.url+'">'+item.text+'</a>')
    				.appendTo(ul);
    		});
    	};
    	$(window).bind('resize', function(){ self.autocomplete("close"); });
    });
    
    var auth_load = function(auth_url, data, submit){
        if(data == 'undefined') data = {};
        if(submit == 'undefined') sumbit = false;
        var loading = $('<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.5);"><i class="icon16 loading" style="position: absolute; top: 50%; margin-top: -8px; left: 50%; margin-left: -8px;"></i></div>');
        var d = $('#dialog');
        var c = d.find('.cart');
        d.show();
        c.addClass('authentification').append(loading).load(auth_url + ' #page', data, function () {
            if(submit && !c.find('.wa-error').length){
                if(auth_url == $.buysimply.auth_base_url){
                    c.empty();
                    var text = '<div id="page"><h1>'+$.buysimply.locale.cong+'</h1><p>'+$.buysimply.locale.isauth+'</p>'+
                        '<br><br><div>'+$.buysimply.auth_my_link+$.buysimply.auth_home_link+'</div></div>';
                    c.append(text);
                    $(document).unbind('keyup.dialog');
                    $('.dialog').off('click').on('click', 'a.dialog-close', function(){
                        $(this).attr('href', c.find('.auth-home-link').attr('href'));
                    });
                }else{
                    if(!c.find('#page>a').length){
                        var text = '<br><br><div>'+$.buysimply.auth_my_link+$.buysimply.auth_home_link+'</div>';
                        c.find('#page').append(text);
                        $(document).unbind('keyup.dialog');
                        $('.dialog').off('click').on('click', 'a.dialog-close', function(){
                            $(this).attr('href', c.find('.auth-home-link').attr('href'));
                        });
                    }
                }
            }
            c.parent().prepend(c.find('h1'));
            c.css('margin-top', d.find('h1').innerHeight());
            d.find('h1').append('<a href="#" class="dialog-close"><i class="icon-remove"></i></a>');
            
            c.find('form').attr('action', auth_url).submit(function(){
                var fields = $(this).serializeArray();
            	var params = {};
            	for (var i = 0; i < fields.length; i++) {
            		if (fields[i].value !== '') {
            		    params[fields[i].name] = ''+fields[i].value;
            		}
            	}
            	d.find('.dialog-window').empty().append('<div class="cart"></div>');
            	auth_load(auth_url, params, true);
                
                return false;
            });
            c.find('div.wa-auth-adapters a').click(function () {
                var left = (screen.width - 600) / 2;
                var top = (screen.height - 400) / 2;
                window.open($(this).attr('href'),'oauth', "width=600,height=400,left="+left+",top="+top+",status=no,toolbar=no,menubar=no");
                d.hide().find('.dialog-window').empty().append('<div class="cart"></div>');
                return false;
            });
            c.find('.wa-captcha-refresh, .wa-captcha-img').unbind('click').click(function(){
                var div = $(this).parents('div.wa-captcha');
                var captcha = div.find('.wa-captcha-img');
                if(captcha.length) {
                    captcha.attr('src', captcha.attr('src').replace(/\?.*$/,'?rid='+Math.random()));
                    captcha.one('load', function() {
                        div.find('.wa-captcha-input').focus();
                    });
                };
                div.find('input').val('');
                return false;
            });
            c.find('.wa-submit a, #page>a').click(function(){
                d.find('.dialog-window').empty().append('<div class="cart"></div>');
                auth_load($(this).attr('href'));
                return false;
            });
            
            if ((($(window).height()*2/3) > c.outerHeight(true))) {
                c.css('bottom', 'auto');
            } else {
                c.css('bottom', '15%');
            }
        });
    }

    $('.auth-popup').click(function(){
        auth_load($(this).attr('href'));
        
        return false;
    });

    // scroll-dependent animations
    $(window).scroll(function() {    
      	if ( $(this).scrollTop()>=35 ) {
            if (!$("#cart").hasClass('empty')) { $("#cart").addClass( "fixed" ); }
    	} else if ( $(this).scrollTop()<30 ) {
    		$("#cart").removeClass( "fixed" );
    	}    
    });
    
    $('.horizontal-tree-one>li').each(function(){
        var div = $(this).find('div'); pos = div.position().left;
        addDL(div.children('ul'), pos+230);
    });
    //category dynamize
    if(!$('ul.vertical-tree-three.dhtml').hasClass('dynamized'))
	{		
		$('ul.vertical-tree-three.dhtml ul').prev().before("<span class='grower icon-angle-down OPEN'> </span>");
		$('ul.vertical-tree-three.dhtml span.grower.OPEN').addClass('CLOSE').removeClass('OPEN').parent().find('ul:first').hide();
		$('ul.vertical-tree-three.dhtml').show();
		$('ul.vertical-tree-three.dhtml .selected').parents().each( function() {
			if ($(this).is('ul')) toggleBranch($(this).prev().prev(), true);
		});
		toggleBranch($('ul.vertical-tree-three.dhtml .selected').find('span:first'), true);		
		$('ul.vertical-tree-three.dhtml span.grower').click(function(){
			toggleBranch($(this));
		});		
		$('ul.vertical-tree-three.dhtml').addClass('dynamized');
		$('ul.vertical-tree-three.dhtml').removeClass('dhtml');
	}
	//dynamize mobile
	if(!$('.b-mob-tree.dhtml').hasClass('dynamized'))
	{		
		$('.b-mob-tree.dhtml ul').prev().before("<span class='grower icon-angle-down OPEN'> </span>");
		$('.b-mob-tree.dhtml span.grower.OPEN').addClass('CLOSE').removeClass('OPEN').parent().find('ul:first').hide();
		$('.b-mob-tree.dhtml').show();
		$('.b-mob-tree.dhtml .selected').parents('ul').each( function() { toggleBranch($(this).prev().prev(), true); });
		toggleBranch($('.b-mob-tree.dhtml .selected').find('span:first'), true);
		$('.b-mob-tree.dhtml span.grower').click(function(){ toggleBranch($(this)); });		
		$('.b-mob-tree.dhtml').addClass('dynamized');
		$('.b-mob-tree.dhtml').removeClass('dhtml');
	}
	/*add branch selected*/
	$('.buysimply-horizontal-tree, .buysimply-vertical-tree').find('.selected').parents('li').addClass('selected');
	$('.pages-navigation .selected').parents('li').addClass('selected');
	//tags cloud
	if($('#tagsCanvasContent').length){
        var option = {
            textColour: $('.tags-block .caption span').css('border-color'),
            outlineColour: $('.tags-block a').css('color'),
            outlineMethod: "colour",
            outlineThickness: 1,
            reverse: true,
            hideTags: false,
            depth: 0.8,
            wheelZoom: false,
            maxSpeed: 0.05
        }
        if($('html').hasClass('no-canvas') || !$('#tagsCanvas').tagcanvas(option, 'tagsCanvasContent')){
            $('#tagsCanvas').parent().hide();
            $('#tagsCanvasContent').show();
        }
    }
    // scroll up
	$("#back-top").hide();
	$(window).scroll(function () {
	    var back = $("#back-top");
		if ($(this).scrollTop() > 100) { back.fadeIn();	} else { back.fadeOut(); }
		if(back.position().top > $('.wrapper-bottom').position().top-140)
		    back.addClass('back-top-up');
		if(back.position().top < $('.wrapper-bottom').position().top-140)
		    back.removeClass('back-top-up');
	});
	// scroll body to 0px on click
	$('#back-top a').click(function () {
		$('body,html').animate({ scrollTop: 0 }, 800); return false;
	});
	// open menu adaptive
	var sidebar = $('#main .sidebar');
	var content = $('#main .content');
	if(sidebar.length){        
		var showPanel = function() {
		    sidebar.css({ 'height' : 'auto', 'overflow' : 'auto' });
			content.css('margin-right', -240);
			sidebar.animate({ marginLeft: '+=240' }, 200, function(){ 
                $(this).addClass('visible');
			});
		};
		var hidePanel = function() {                           	
		    sidebar.animate({ marginLeft: '-=240' }, 200, function(){ $(this).removeClass('visible');
		    sidebar.css({ 'height' : '100px', 'overflow' : 'hidden' });
		    content.css('margin-right', 0); });              	
		};
		var $sticker = $('#panel-sticker');
      
		$sticker.children('span').click(function() {
			if(sidebar.hasClass('visible')){
				hidePanel();
				var text = $(this).data('text');
				$(this).data('text', $(this).text());
				$(this).text(text).addClass('signin').removeClass('signout');     	
			}else{
				showPanel();
				var text = $(this).data('text');
				$(this).data('text', $(this).text());
				$(this).text(text).addClass('signout').removeClass('signin');
			}
		});
	}
	
	if($('#cart').length){
	    
	    $('#cart').hover(function(){
	        var self = $(this), soa = self.find('.soaring-block');
	        if(self.hasClass('empty')) return false;
	        clearTimeout(soa.data('timer'));
	        if(soa.is(':animated') || soa.hasClass('active')) return false;
	        soa.css('top', -soa.outerHeight()-8);
	        soa.stop().animate({top: self.outerHeight()}, 'slow', function(){
	            $(this).addClass('active');
	            //self.find('a:first').css('border-bottom', 'none');
	        });
	    }, function(){
	        if($('#cart').hasClass('empty')) return false;
	        var soa = $(this).find('.soaring-block');
	        soa.data('timer', setTimeout(function(){
	            soa.stop().animate({top: -soa.outerHeight()-8}, "slow", function(){
	                $(this).removeClass('active').css('top', -1000);
	                //soa.parent().find('a:first').css('border-bottom', '1px solid');
	            });
	        }, 1000));
	    });
	    
        $("body").off('click.soaring-cart-del').on('click.soaring-cart-del', '.soaring-cart-del', function(){
            var li = $(this).closest('li');
            
            addSoaringLoading();
            $.post($.buysimply.shop_url + 'cart/delete/', {html: sumbolrub, id: li.data('id')}, function (response) {
                li.animate({opacity: 0}, 'slow', function(){
                    
                    if (response.data.count == 0) {
                        response.data.total = $.buysimply.locale.empty;
                        $('#cart').addClass('empty').removeClass('fixed');
                        $('.soaring-block').removeClass('active').css('top', -1000);
                    }
                    $(".cart-total").html(response.data.total);
                    
                    li.remove();
                    setSoaringHeight();
                });
                removeSoaringLoading();
            }, "json");
            return false;
        });
        
        $("body").off('change.soaring-cart-qty').on('change.soaring-cart-qty', '.soaring-cart-qty', function(){
            var self = $(this), li = self.closest('li');
            self.val(self.val() > 0 ? self.val() : 1);
            
            addSoaringLoading();
            $.post($.buysimply.shop_url + 'cart/save/', {html: sumbolrub, id: li.data('id'), quantity: self.val()}, function (response) {
                li.find('.price').html(response.data.item_total);
                if (response.data.q) {
                    self.val(response.data.q);
                }
                if (response.data.error) {
                    alert(response.data.error);
                } else {
                    self.removeClass('error');
                }
                
                $(".cart-total").html(response.data.total);
                removeSoaringLoading();
            }, "json");
        });
        /*
        $("body").off('click.soaring-cart-minus').on('click.soaring-cart-minus', '.soaring-cart-minus', function(){
            var input = $(this).parent().find('input');
    		if(parseInt(input.val()) >= 2){ input.val(parseInt(input.val()) - 1).change(); }
    		return false;
        });
        
        $("body").off('click.soaring-cart-plus').on('click.soaring-cart-plus', '.soaring-cart-plus', function(){
            var input = $(this).parent().find('input');
    		input.val(parseInt(input.val()) + 1).change();
    		return false;
        });
        */
        if($('#soaring-cart').length) setSoaringHeight();
        
    }

});

function openBranch(jQueryElement, noAnimation) {
	jQueryElement.addClass('OPEN icon-angle-up').removeClass('CLOSE icon-angle-down');
	if(noAnimation)
		jQueryElement.parent().find('ul:first').show();
	else
		jQueryElement.parent().find('ul:first').slideDown();
}
function closeBranch(jQueryElement, noAnimation) {
	jQueryElement.addClass('CLOSE icon-angle-down').removeClass('OPEN icon-angle-up');
	if(noAnimation)
		jQueryElement.parent().find('ul:first').hide();
	else
		jQueryElement.parent().find('ul:first').slideUp();
}
function toggleBranch(jQueryElement, noAnimation) {
	if(jQueryElement.hasClass('OPEN'))
		closeBranch(jQueryElement, noAnimation);
	else
		openBranch(jQueryElement, noAnimation);
}

function goToByScroll(id){
    $('html, body').animate({ scrollTop: $("#"+id).offset().top },'slow');
}

var addDL = function($ul, pos){
    if(pos>980) $ul.parent().addClass('drop-left');
    $ul.children('li').each(function(){
        addDL($(this).children('ul'), pos+230);
    });
}

var addSoaringLoading = function(){
    if(typeof loading == 'undefined')
        var loading = $('<div id="soaring-cart-loading"><i class="icon16 loading"></i></div>');
    $('#soaring-cart').parent().append(loading);
    $('#soaring-cart-total .button, #cart>a').addClass('disabled').bind('click', function(){ return false; });
}
var removeSoaringLoading = function(){
    $('#soaring-cart-loading').remove();
    $('#soaring-cart-total .button, #cart>a').removeClass('disabled').unbind('click');
}
var setSoaringHeight = function(){
    var soa_h = 0;
    $('#soaring-cart li').filter(':lt('+$.buysimply.soaring_visible_item+')').each(function(){
        soa_h += $(this).outerHeight();
    });
    $('#soaring-cart').css('max-height', soa_h);
}