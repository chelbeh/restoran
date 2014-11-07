$(function() {
 /*
    var height = $(".navbar-default ul.nav li").height() + 2;
    $(".navbar-default ul.nav li:not([id])").filter(function() {
        return this.offsetTop + $(this).height() > height;
    }).wrapAll("<ul>").parent().appendTo("#overflow .dropdown-menu");
*/
    /* Форма авторизации */
    $("a[data-auth=1]").click(function(){
        $('#signupModal').modal('hide');
        $('#forgotModal').modal('hide');
        $('#loginModal').modal('show');
        return false;
    });
    
    /* Форма регистрации */
    $("a[data-signup=1]").click(function(){
        $('#loginModal').modal('hide');
        $('#forgotModal').modal('hide');
        $('#signupModal').modal('show');
        return false;
    });
    /* Форма восстановления пароля */
    $("a[data-forgot=1]").click(function(){
        $('#loginModal').modal('hide');
        $('#signupModal').modal('hide');
        $('#forgotModal').modal('show');
        return false;
    });

    $('.modal .wa-auth-adapters img').each(function() {
        var tn = this.nextSibling;
        tn.parentNode.removeChild(tn);
    });

    if($('#cart').length){
        $('#cart').hover(function(){
            var cart = $(this), fcart = cart.find('.flying-cart');
            if(!cart.hasClass('highlight')) return false;
            clearTimeout(fcart.data('timer'));
            if(fcart.is(':animated') || fcart.hasClass('active')) return false;
            fcart.css('bottom', -fcart.outerHeight()-40);
            fcart.stop().animate({bottom: cart.outerHeight()+20}, 'slow', function(){
                $(this).addClass('active');
            });
        }, function(){
            if(!$('#cart').hasClass('highlight')) return false;
            var fcart = $(this).find('.flying-cart');
            fcart.data('timer', setTimeout(function(){
                fcart.stop().animate({bottom: -fcart.outerHeight()+20}, "slow", function(){
                    $(this).removeClass('active').css('bottom', -340);
                });
            }, 1000));
        });
        
        $("body").off('click.fcart-del').on('click.fcart-del', '.fcart-del', function(){
            var item = $(this).closest('.row'), icon = item.find('.fa');
            icon.removeClass('fa-times').addClass('fa-spinner fa-spin');
            $.post($.dsv.shop_url + 'cart/delete/', {html: 1, id: item.data('id')}, function (response) {
                item.animate({opacity: 0}, 'slow', function(){
                    if (response.data.count == 0) {
                        response.data.total = '0';
                        $('#cart').removeClass('highlight');
                        //$('.soaring-block').removeClass('active').css('top', -1000);
                    }
                    $(".cart-total,.fcart-total").html(response.data.total);
                    $(".cart-count").html(response.data.count);
                    
                    item.next('.divider').remove();

                    item.remove();
                });
                icon.removeClass('fa-spinner fa-spin').addClass('fa-times');
            }, "json");
            return false;
        });
        
        $("body").off('change.fcart-qty').on('change.fcart-qty', '.fcart-qty', function(){
            var self = $(this), item = self.closest('.row'), icon = item.find('.fa');
            self.val(self.val() > 0 ? self.val() : 1);
            
            icon.removeClass('fa-times').addClass('fa-spinner fa-spin');

            $.post($.dsv.shop_url + 'cart/save/', {html: 1, id: item.data('id'), quantity: self.val()}, function (response) {
                item.find('.fcart-price').html(response.data.item_total);
                if (response.data.q) {
                    self.val(response.data.q);
                }
                if (response.data.error) {
                    alert(response.data.error);
                } else {
                    self.removeClass('error');
                }
                $(".cart-total,.fcart-total").html(response.data.total);
                $(".cart-count").html(response.data.count);
                icon.removeClass('fa-spinner fa-spin').addClass('fa-times');
            }, "json");
        });
    }
});