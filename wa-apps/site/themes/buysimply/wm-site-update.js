$(document).ready(function(){
	var sidebar = $('#main .sidebar');
	var content = $('#main .content');

	if(sidebar.length){        
		var showPanel = function() {
		    sidebar.css({ 'height' : 'auto', 'overflow' : 'auto' });
			content.css('margin-right', -240);
			sidebar.animate({ marginLeft: '+=240'}, 200, function(){ 
              $(this).addClass('visible').css({'box-shadow':'none', 'background':'#fff'});
			});
		};
		var hidePanel = function() {                           	
		sidebar.animate({ marginLeft: '-=240' }, 200, function(){ $(this).removeClass('visible').css({'box-shadow':'0 0 5px rgba(255,221,0,0.5)', 'background':'rgba(255,221,0,0.5)'});
		sidebar.css({ 'height' : '100px', 'overflow' : 'hidden' });
		content.css('margin-right', 0); });              	
		};

		var $sticker = $('#panel-sticker');
      
     
      
		$sticker.children('span').click(function() {
			if(sidebar.hasClass('visible')){
				hidePanel();
				$(this).text('Открыть панель навигации').addClass('signin').removeClass('signout');              	
			}else{
				showPanel();
				$(this).text('Закрыть панель навигации').addClass('signout').removeClass('signin');              	
			}
		});
	}

$('ul.category-tree.dhtml ul li:last-child, ul.category-tree.dhtml li:last-child').addClass('last');				
$('ul.category-tree-s.dhtml .selected').parents().each( function() {
if ($(this).is('li')) $(this).children('a').addClass('selected');});
$('ul.category-tree.dhtml .selected').parents().each( function() {
if ($(this).is('li')) $(this).children('a').addClass('selected');});

/*hack hover click sorting*/
$('.sorting-selection').click(function(){ $(this).find('ul').toggle(); });
$('.sorting-selection').hover(function(){ $(this).find('ul').show(); }, function(){	$(this).find('ul').hide(); });

});