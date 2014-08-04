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
                $(".banner-del-link").click(function () {
                    
    				$.banner.bannerDel($(this).attr('id'));
    				return false;
			    })
            });
		},
		
		bannerAction: function (params) {
			$.get('?action=banner', {id: params[0]}, function (response) {
				var html = '<div class="block">' +  
				'</div><div class="block padded">' + response + '</div>';
				$("#content").html(html);
                
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

        itemEdit: function (id, on) {
            var banner_id = $.banner.currentActionAttr;
            
			$.post('?action=edit', {item_id: id, on:on, banner_id:banner_id}, function (response) {
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