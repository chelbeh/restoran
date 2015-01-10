function initBanner(){
    $( document ).ready(function() {
        $("a.toggle_link").click(function(){
            $("#"+$(this).data('id')).slideToggle(300);
            return false;
        });
        $("a.toggle_status").live('click',function(){
            var input = $(this).parents('.value').find('input');
            var icon = $(this).parents('.value').find('i');
            if(input.val()==1){
                input.val(0);
                icon.removeClass('status-red').addClass('status-green');
                $(this).text('включено');
            }
            else{
                input.val(1);
                icon.removeClass('status-green').addClass('status-red');
                $(this).text('выключено');
            }
            return false;
        });
    });
}

function clog(o){
    console.log(o);
}

function addImages(element, files){
    $("#block_images").show();
    element.append(tmpl('template-smartb-images', {
        images: files,
        banner_id: element.data('id')
    }));
}

function showImages(element, files){
    element.html('');
    if(files.length==0)return;
    addImages(element, files);
    element.sortable({
        handle: ".drag_handle",
        axis: "y",
        update: function (event, ui) {
            element.find('.image_block').each(function(i){
                $(this).find('.sort_input').val(i);
            })
        }
    });
}

function initColor(input, icon, picker){
    picker.hide();
    var farbtastic = $.farbtastic(picker, function(color) {
        icon.css('background', color);
        input.val(color.substr(1));
    });
    farbtastic.setColor('#'+input.val());
    icon.click(function() {
        picker.slideToggle(200);
        return false;
    });
    var timer_id;
    input.unbind('keydown').bind('keydown', function() {
        if (timer_id) {
            clearTimeout(timer_id);
        }
        timer_id = setTimeout(function() {
            farbtastic.setColor('#'+input.val());
        }, 250);
    });
}