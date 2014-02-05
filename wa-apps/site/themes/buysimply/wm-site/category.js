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

$(document).ready(function (){
	if(!$('ul.category-tree.dhtml').hasClass('dynamized'))
	{		
		$('ul.category-tree.dhtml ul').prev().before("<span class='grower icon-angle-down OPEN'> </span>");		
		$('ul.category-tree.dhtml ul li:last-child, ul.category-tree.dhtml li:last-child').addClass('last');		
		$('ul.category-tree.dhtml span.grower.OPEN').addClass('CLOSE').removeClass('OPEN').parent().find('ul:first').hide();
		$('ul.category-tree.dhtml').show();
		$('ul.category-tree.dhtml .selected').parents().each( function() {
			if ($(this).is('ul')) toggleBranch($(this).prev().prev(), true);
		});
		toggleBranch($('ul.category-tree.dhtml .selected').prev(), true);		
		$('ul.category-tree.dhtml span.grower').click(function(){
			toggleBranch($(this));
		});		
		$('ul.category-tree.dhtml').addClass('dynamized');
		$('ul.category-tree.dhtml').removeClass('dhtml');
	}
});