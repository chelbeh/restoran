$(document).ready(function () {

    VK.init(function() { 
        // API initialization succeeded 
        VK.callMethod("setTitle", document.title);
        VK.callMethod("resizeWindow", $("body").outerWidth(), $("body").outerHeight());
    });
    
    $('input[type="button"]').addClass('vk-button');
    $('input[type="submit"]').addClass('vk-button');

});


$(window).load(function () {
    VK.callMethod("resizeWindow", $("body").outerWidth(), $("body").outerHeight());
});