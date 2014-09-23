var blinkPanel = function(event, obj){
    if(event == 'add') cl = 'green';
    if(event == 'remove') cl = 'red';
    obj.addClass(cl);
    setTimeout(function(){ obj.removeClass(cl); }, 200);
}

$(function(){
    $('#favorite-link .text, #compare-link .text').each(function(){
        $(this).html($(this).text().replace(/(.)/g, '<span class="letter">$1</span>'));
    });
    $('#main-addition-2 a').click(function(){
        if($(this).parent().hasClass('disabled')) return false;
    });
    /*compare*/
    $('body#shop').off('click.compare').on('click.compare', '.compare-add', function (event) {
        var compare = $.cookie('shop_compare');
        
        if($(this).hasClass('active')){
            if (compare) { compare = compare.split(','); } else { compare = []; }
            var i = $.inArray($(this).data('product') + '', compare);
            if (i != -1) { compare.splice(i, 1); }
            if (compare.length) {
                $.cookie('shop_compare', compare.join(','), { expires: 30, path: '/'});
                var url = $("#compare-link").data('href').replace(/compare\/.*$/, 'compare/' + compare.join(',') + '/');
            } else {
                $.cookie('shop_compare', null);
                var url = 'javascript:void(0);';
            }
            
            $("#compare-link").attr('href', url);
            $('.compare-add.active[data-product="' + $(this).data('product') + '"]').removeClass('active');
            $("#compare-link span.count").text(compare.length);
            if (compare.length < 2) { $("#compare-link").parent().addClass('disabled'); }
            blinkPanel('remove', $("#compare-link"));
        }else{
            if (compare) { compare = $(this).data('product') + ',' + compare; } else { compare = '' + $(this).data('product'); }
            
            var url = $("#compare-link").data('href').replace(/compare\/.*$/, 'compare/' + compare + '/');
            $("#compare-link").attr('href', url);
            $("#compare-link span.count").text(compare.split(',').length);
            if (compare.split(',').length > 1) { $("#compare-link").parent().removeClass('disabled'); }
        	
            $.cookie('shop_compare', compare, { expires: 30, path: '/'});
            $('.compare-add[data-product="' + $(this).data('product') + '"]').addClass('active');
            blinkPanel('add', $("#compare-link"));
        }
        var title = $(this).data('title');
        $(this).data('title', $(this).attr('title'));
        $(this).attr('title', title);
        
        //event.stopImmediatePropagation();
        return false;
    });
    /*favorite*/
    $('body#shop').off('click.favorite').on('click.favorite', '.favorite-add', function (event) {
        var favorite = $.cookie('shop_favorite');
        
        if($(this).hasClass('active')){
            if (favorite) { favorite = favorite.split(','); } else { favorite = []; }
            var i = $.inArray($(this).data('product') + '', favorite);
            if (i != -1) { favorite.splice(i, 1); }
            if (favorite.length) {
                $.cookie('shop_favorite', favorite.join(','), { expires: 30, path: '/'});
            } else { 
                $.cookie('shop_favorite', null, { expires: 0, path: '/'});
            }
            
            $('.favorite-add.active[data-product="' + $(this).data('product') + '"]').removeClass('active');
            
            if(!favorite.length){
                if($('#product-list.favorite').length){
                    window.location.assign(location.href.replace(/search.*/, ''));
                    //event.stopImmediatePropagation();
                    return false;
                }else{
                    $("#favorite-link").parent().addClass('disabled');
                }
            }
            if($('#product-list.favorite').length) $(this).closest('li').remove();
            $("#favorite-link span.count").text(favorite.length);
            blinkPanel('remove', $("#favorite-link"));
        }else{
            if (favorite) { favorite = $(this).data('product') + ',' + favorite; } else { favorite = '' + $(this).data('product'); }
            
            $("#favorite-link span.count").text(favorite.split(',').length);
            if (favorite.split(',').length > 0) { $("#favorite-link").parent().removeClass('disabled'); }
        	
            $.cookie('shop_favorite', favorite, { expires: 30, path: '/'});
            $('.favorite-add[data-product="' + $(this).data('product') + '"]').addClass('active');
            blinkPanel('add', $("#favorite-link"));
        }
        var title = $(this).data('title');
        $(this).data('title', $(this).attr('title'));
        $(this).attr('title', title);
        
        //event.stopImmediatePropagation();
        return false;
    });
});