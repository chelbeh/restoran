(function ($) {
	$.banner = {
		init: function () {
			if (typeof($.History) != "undefined") {
				$.History.bind(function (hash) {
					$.banner.dispatch(hash);
				});
			}			
			$("#banner-add-link").click(function () {
				$.banner.bannerAdd();
				return false;
			})
            
			this.dispatch();
		},
		
		dispatch: function (hash) {
			if (hash === undefined) {
				hash = location.hash.replace(/^[^#]*#\/*/, '');
			}			
			if (hash) {
				// clear hash
				hash = hash.replace(/^.*#/, '');
				hash = hash.split('/');
				if (hash[0]) {				
					var actionName = "";
					var attrMarker = hash.length;
					for (var i = 0; i < hash.length; i++) {
						var h = hash[i];
						if (i < 2) {
							if (i === 0) {
								actionName = h;
							} else if (parseInt(h, 10) != h) {
								actionName += h.substr(0,1).toUpperCase() + h.substr(1);
							} else {
								attrMarker = i;
								break;
							}
						} else {
							attrMarker = i;
							break;
						}
					}
					var attr = hash.slice(attrMarker);
					// call action if it exists
					if (this[actionName + 'Action']) {
						this.currentAction = actionName;
						this.currentActionAttr = attr;
						this[actionName + 'Action'](attr);
					} else {
						if (console) {
							console.log('Invalid action name:', actionName+'Action');
						}
					}
				} else {
					// call default action
					this.defaultAction();					
				}
			} else {
				// call default action
				this.defaultAction();
			}
		},
		
		defaultAction: function () {
			$("#content").load('?action=banners', function(){
                var old_value;
                $(".banner-del-link").click(function () {
                    
    				$.banner.bannerDel($(this).attr('id'));
    				return false;
			    });
                
                $(".banner-size").focus(function(){
                    $(this).addClass("edit");
                    old_value = $(this).val();
                });
                
                
                $(".banner-size").blur(function(){
                    $(this).removeClass("edit");
                    
                    if ($(this).val() != old_value) {
                        var banner_id = $(this).data("id");
                        if ($(this).attr("name") == 'width') {
                            $.banner.bannerEdit(banner_id, {width: $(this).val()});
                        } else if ($(this).attr("name") == 'height') {
                            $.banner.bannerEdit(banner_id, {height: $(this).val()});
                        }  
                    }
   
                });
/*                $('.banner-size').keyup(function(e) {
                    if(e.keyCode == 13){
                        $(this).blur();
                    }
                });*/
                
                $(".banner-edit-ico").click(function(){
                    var banner_id = $(this).data("id");
                    old_value = $("input#banner-name-edit-" + banner_id).val();
                    $("input#banner-name-edit-" + banner_id).show();
                    $("input#banner-name-edit-" + banner_id).focus();
                    $("a#banner-name-link-" + banner_id).hide();
                    $("#banner-edit-ico-" + banner_id).hide();
                    
                });
                
                $(".banner-name").blur(function(){
                    var banner_id = $(this).data("id");
                    if ($(this).val() != old_value) {
                        $.banner.bannerEdit(banner_id, {title: $(this).val()});
                    } else {
                        $(this).hide();
                        $("a#banner-name-link-" + banner_id).show();
                        $("#banner-edit-ico-" + banner_id).show();
                    }
                });
                $('.banner-param').keyup(function(e) {
                    if(e.keyCode == 13){
                        $(this).blur();
                    }
                });
                
            });
		},
		
		bannerAction: function (params) {
			$.get('?action=banner', {id: params[0]}, function (response) {
				var html = '<div class="block">' +  
				'</div><div class="block padded">' + response + '</div>';
				$("#content").html(html);
                
                var old_value;
                
                $("#item-add-link").click(function () {
    				$.banner.itemAdd();
    				return false;
    			});
                
                $(".item-del-link").click(function () {
    				$.banner.itemDel($(this).attr('id'));
    				return false;
			    });
                
                $(".banner-del-link").click(function () {
    				$.banner.bannerDel($.banner.currentActionAttr);
    				return false;
			    });

                $(".banner-param").click(function(){
                    $(this).addClass("edit");
                    old_value = $(this).val();
                });
                $(".banner-param").blur(function(){
                    $(this).removeClass("edit");
                    
                    if ($(this).val() != old_value) {
                        var item_id = $(this).data("id");
                        if ($(this).attr("name") == 'link') {
                            $.banner.itemEdit(item_id, {link: $(this).val()});
                        } else if ($(this).attr("name") == 'alt') {
                            $.banner.itemEdit(item_id, {alt: $(this).val()});
                        } else if ($(this).attr("name") == 'title') {
                            $.banner.itemEdit(item_id, {title: $(this).val()});
                        }    
                        old_value= '';
                    }
                    
                    
                });
                $('.banner-param').keyup(function(e) {
                    if(e.keyCode == 13){
                        $(this).blur();
                    }
                });	
                $(".banner-param-chk").change(function(){
                    var item_id = $(this).data("id");
                    if ($(this).attr('checked')) {
                        $.banner.itemEdit(item_id, {nofollow: 1});
                    } else {
                        $.banner.itemEdit(item_id, {nofollow: 0});
                    }
                });

			});
		},

		bannerAdd: function () {
			$("#banner-add").waDialog();
		},
        	
		itemAdd: function () {
			$("#item-add").waDialog();
		},
        
        itemDel: function (id) {
            var banner_id = $.banner.currentActionAttr;
            $("#delete_banner_id").val(banner_id);
            $("#delete_item_id").val(id);
            var del_img = $("#img_" + id).attr("src");
            $("#delete_img").attr("src", del_img);
			$("#item-del").waDialog();
		},

        itemEdit: function (id, values) {
            var banner_id = $.banner.currentActionAttr;
            $("body").addClass("wait");
			$.post('?action=edit', {item_id: id, values:values, banner_id:banner_id}, function (response) {
				if (response.status == 'ok') {
                    location.reload();
				} else {
					alert(response.errors);
				}
			}, 'json');
		},


        bannerEdit: function (id, values) {
            $("body").addClass("wait");
			$.post('?action=edit', {banner_id: id, values:values}, function (response) {
				if (response.status == 'ok') {
                    location.reload();
				} else {
					alert(response.errors);
				}
			}, 'json');
		},
        
        bannerDel: function (banner_id) {
            $("#bannerdel_id").val(banner_id);
            $("#banner-del").waDialog();
		}
	}
})(jQuery);