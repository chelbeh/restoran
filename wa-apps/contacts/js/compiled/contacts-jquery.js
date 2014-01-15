//
// Note: This file depends on the jQuery library.
//

// Automatically calls all functions in FORMALIZE.init
jQuery(document).ready(function() {
	FORMALIZE.go();
});

// Module pattern:
// http://yuiblog.com/blog/2007/06/12/module-pattern/
var FORMALIZE = (function($, window, document, undefined) {
	// Private constants.
	var PLACEHOLDER_SUPPORTED = 'placeholder' in document.createElement('input');
	var AUTOFOCUS_SUPPORTED = 'autofocus' in document.createElement('input');
	var WEBKIT = 'webkitAppearance' in document.createElement('select').style;
	var IE6 = !!($.browser.msie && parseInt($.browser.version, 10) === 6);
	var IE7 = !!($.browser.msie && parseInt($.browser.version, 10) === 7);

	// Expose innards of FORMALIZE.
	return {
		// FORMALIZE.go
		go: function() {
			for (var i in FORMALIZE.init) {
				FORMALIZE.init[i]();
			}
		},
		// FORMALIZE.init
		init: {
			detect_webkit: function() {			
				if (!WEBKIT) {
					return;
				}

				// Tweaks for Safari + Chrome.
				$('html').addClass('is_webkit');
			},
			// FORMALIZE.init.full_input_size
			full_input_size: function() {
				if (!IE7 || !$('textarea, input.input_full').length) {
					return;
				}

				// This fixes width: 100% on <textarea> and class="input_full".
				// It ensures that form elements don't go wider than container.
				$('textarea, input.input_full').wrap('<span class="input_full_wrap"></span>');
			},
			// FORMALIZE.init.ie6_skin_inputs
			ie6_skin_inputs: function() {
				// Test for Internet Explorer 6.
				if (!IE6 || !$('input, select, textarea').length) {
					// Exit if the browser is not IE6,
					// or if no form elements exist.
					return;
				}

				// For <input type="submit" />, etc.
				var button_regex = /button|submit|reset/;

				// For <input type="text" />, etc.
				var type_regex = /date|datetime|datetime-local|email|month|number|password|range|search|tel|text|time|url|week/;

				$('input').each(function() {
					var el = $(this);

					// Is it a button?
					if (this.getAttribute('type').match(button_regex)) {
						el.addClass('ie6_button');

						/* Is it disabled? */
						if (this.disabled) {
							el.addClass('ie6_button_disabled');
						}
					}
					// Or is it a textual input?
					else if (this.getAttribute('type').match(type_regex)) {
						el.addClass('ie6_input');

						/* Is it disabled? */
						if (this.disabled) {
							el.addClass('ie6_input_disabled');
						}
					}
				});

				$('textarea, select').each(function() {
					/* Is it disabled? */
					if (this.disabled) {
						$(this).addClass('ie6_input_disabled');
					}
				});
			},
			// FORMALIZE.init.placeholder
			placeholder: function() {
				if (PLACEHOLDER_SUPPORTED || !$(':input[placeholder]').length) {
					// Exit if placeholder is supported natively,
					// or if page does not have any placeholder.
					return;
				}

				$(':input[placeholder]').each(function() {
					var el = $(this);
					var text = el.attr('placeholder');

					function add_placeholder() {
						if (!el.val() || el.val() === text) {
							el.val(text).addClass('placeholder_text');
						}
					}

					add_placeholder();

					el.focus(function() {
						if (el.val() === text) {
							el.val('').removeClass('placeholder_text');;
						}
					}).blur(function() {
						add_placeholder();
					});

					// Prevent <form> from accidentally
					// submitting the placeholder text.
					el.closest('form').submit(function() {
						if (el.val() === text) {
							el.val('');
						}
					}).bind('reset', function() {
						setTimeout(add_placeholder, 50);
					});
				});
			},
			// FORMALIZE.init.autofocus
			autofocus: function() {
				if (AUTOFOCUS_SUPPORTED || !$(':input[autofocus]').length) {
					return;
				}

				$(':input[autofocus]:visible:first').select();
			}
		}
	};
// Alias jQuery, window, document.
})(jQuery, this, this.document);;
/**
 * jQuery History Plugin (balupton edition) - Simple History Handler/Remote for Hash, State, Bookmarking, and Forward Back Buttons
 * Copyright (C) 2008-2009 Benjamin Arthur Lupton
 * http://www.balupton/projects/jquery_history/
 *
 * This file is part of jQuery History Plugin (balupton edition).
 * 
 * jQuery History Plugin (balupton edition) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * jQuery History Plugin (balupton edition) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with jQuery History Plugin (balupton edition).  If not, see <http://www.gnu.org/licenses/>.
 *
 * @name jqsmarty: jquery.history.js
 * @package jQuery History Plugin (balupton edition)
 * @version 1.0.1-final
 * @date July 11, 2009
 * @category jquery plugin
 * @author Benjamin "balupton" Lupton {@link http://www.balupton.com}
 * @copyright (c) 2008-2009 Benjamin Arthur Lupton {@link http://www.balupton.com}
 * @license GNU Affero General Public License - {@link http://www.gnu.org/licenses/agpl.html}
 * @example Visit {@link http://jquery.com/plugins/project/jquery_history_bal} for more information.
 * 
 * 
 * I would like to take this space to thank the following projects, blogs, articles and people:
 * - jQuery {@link http://jquery.com/}
 * - jQuery UI History - Klaus Hartl {@link http://www.stilbuero.de/jquery/ui_history/}
 * - Really Simple History - Brian Dillard and Brad Neuberg {@link http://code.google.com/p/reallysimplehistory/}
 * - jQuery History Plugin - Taku Sano (Mikage Sawatari) {@link http://www.mikage.to/jquery/jquery_history.html}
 * - jQuery History Remote Plugin - Klaus Hartl {@link http://stilbuero.de/jquery/history/}
 * - Content With Style: Fixing the back button and enabling bookmarking for ajax apps - Mike Stenhouse {@link http://www.contentwithstyle.co.uk/Articles/38/fixing-the-back-button-and-enabling-bookmarking-for-ajax-apps}
 * - Bookmarks and Back Buttons {@link http://ajax.howtosetup.info/options-and-efficiencies/bookmarks-and-back-buttons/}
 * - Ajax: How to handle bookmarks and back buttons - Brad Neuberg {@link http://dev.aol.com/ajax-handling-bookmarks-and-back-button}
 *
 **
 ***
 * CHANGELOG
 **
 * v1.0.1-final, July 11, 2009
 * - Restructured a little bit
 * - Documented
 * - Cleaned go/request
 *
 * v1.0.0-final, June 19, 2009
 * - Been stable for over a year now, pushing live.
 * 
 * v0.1.0-dev, July 24, 2008
 * - Initial Release
 * 
 */

// Start of our jQuery Plugin
(function($)
{	// Create our Plugin function, with $ as the argument (we pass the jQuery object over later)
	// More info: http://docs.jquery.com/Plugins/Authoring#Custom_Alias
	
	// Debug
	
	if (typeof console === 'undefined') {
		console = typeof window.console !== 'undefined' ? window.console : {};
	}
	console.log			= console.log 			|| function(){};
	console.debug		= console.debug 		|| console.log;
	console.warn		= console.warn			|| console.log;
	console.error		= console.error			|| function(){var args = [];for (var i = 0; i < arguments.length; i++) { args.push(arguments[i]); } alert(args.join("\n")); };
	console.trace		= console.trace			|| console.log;
	console.group		= console.group			|| console.log;
	console.groupEnd	= console.groupEnd		|| console.log;
	console.profile		= console.profile		|| console.log;
	console.profileEnd	= console.profileEnd	|| console.log;
	
	
	// Declare our class
	$.History = {
		// Our Plugin definition
		
		// -----------------
		// Options
		
		options: {
			debug: false
		},
		
		// -----------------
		// Variables
		
		state:		'',
		$window:	null,
		$iframe:	null,
		handlers:	{
			generic:	[],
			specific:	{}
		},
		
		// --------------------------------------------------
		// Functions
		
		/**
		 * Format a hash into a proper state
		 * @param {String} hash
		 */
		format: function ( hash ) {
			// Format the hash
			hash = hash.replace(/^.+?#/g,'').replace(/^#?\/?|\/?$/g, '');
			// Return the hash
			return hash;
		},
		
		/**
		 * Get the current state of the application
		 */
        getState: function ( ) {
			var History = $.History;
			// Get the current state
			return History.state;
        },
		/**
		 * Set the current state of the application
		 * @param {String} hash
		 */
		setState: function ( state ) {
			var History = $.History;
			// Format the state
			state = History.format(state)
			// Apply the state
			History.state = state;
			// Return the state
			return History.state;
		},
		
		/**
		 * Get the current hash of the browser
		 */
		getHash: function ( ) {
			var History = $.History;
			// Get hash
			if (parent && !$.browser.msie) {
				var hash = parent.window.location.hash;
			} else {
				var hash = window.location.hash || location.hash;
			}
			// Format the hash
			hash = History.format(hash);
			// Return the hash
			return hash;
		},
		/**
		 * Set the current hash of the browser
		 * @param {String} hash
		 */
		setHash: function ( hash ) {
			var History = $.History;
			// Prepare hash
			hash = $.History.format(hash);
			hash = hash.replace(/^\/?|\/?(\?)|\/?$/g, '/$1');
			
			// Write hash
			if ( typeof window.location.hash !== 'undefined' ) {
				//window.location.hash = hash;
			} else {
				location.hash = hash;
			}
			
			// Update IE<8 History
			if ( $.browser.msie && parseInt($.browser.version, 10) < 8 )
			{	// We are IE<8
				$.History.$iframe.contentWindow.document.open();
				$.History.$iframe.contentWindow.document.close();
				//$.History.$iframe.contentWindow.document.location.hash = $.History.getState();						
			}
			
		},
		
		/**
		 * Go to the specific state - does not force a history entry like setHash
		 * @param {String} state
		 */
		go: function ( state ) {
			var History = $.History;
			
			// Format the state
			state = History.format(state);
			
			// Get the current hash
			var hash = History.getHash();
			
			// Are they different?
			if ( hash !== state ) {
				// Yes, create a history entry
				History.setHash(state);
				// Wait for hashchange
			} else {
				// No change, but update state and fire
				History.setState(state);
				History.trigger();
			}
			
			// Done
			return true;
		},
		
		/**
		 * Fired when the hash is changed, either automaticly or manually
		 * @param {Event} e
		 */
		hashchange: function ( e ) {

			var History = $.History;
			
			// Debug
			if ( History.options.debug ) {
				console.debug('History.hashchange', this, arguments);
			}
			
			// Get Hash
			var hash = History.getHash();
			var state = History.getState();
			
			// Prevent IE 8 from fireing this twice
			if ( (!History.$iframe && state === hash) || (History.$iframe && History.hash === History.$iframe.contentWindow.document.location.hash) ) {
				// For some reason this works
				return false;
			}
			
			// Check
			if ( state === hash ) {
				// Nothing to do
				return false;
			}
			
			// Update the state with the new hash
			History.setState(hash);
			
			// Fire the handler
			History.trigger();
			
			// All done
			return true;
		},
		
		/**
		 * Bind a handler to a hash
		 * @param {Object} state
		 * @param {Object} handler
		 */
		bind: function ( state, handler ) {
			var History = $.History;
			// 
			if ( handler ) {
				// We have a state specific handler
				// Prepare
				if ( typeof History.handlers.specific[state] === 'undefined' )
				{	// Make it an array
					History.handlers.specific[state] = [];
				}
				// Push new handler
				History.handlers.specific[state].push(handler);
			}
			else {
				// We have a generic handler
				handler = state;
				History.handlers.generic.push(handler);
			}
			
			// Done
			return true;
		},
		
		/**
		 * Trigger a handler for a state
		 * @param {String} state
		 */
		trigger: function ( state ) {
			var History = $.History;
			
			// Prepare
			if ( typeof state === 'undefined' ) {
				// Use current
				state = History.getState();
			}
			var i, n, handler, list;
			
			// Fire specific
			if ( typeof History.handlers.specific[state] !== 'undefined' ) {
				// We have specific handlers
				list = History.handlers.specific[state];
				for ( i = 0, n = list.length; i < n; ++i ) {
					// Fire the specific handler
					handler = list[i];
					handler(state);
				}
			}
			
			// Fire generics
			list = History.handlers.generic;
			for ( i = 0, n = list.length; i < n; ++i ) {
				// Fire the specific handler
				handler = list[i];
				handler(state);
			}
			
			// Done
			return true;
		},
		
		// --------------------------------------------------
		// Constructors
		
		/**
		 * Construct our application
		 */
		construct: function ( ) {
			var History = $.History;
			
			// Modify the document
			$(document).ready(function() {
				// Prepare the document
				History.domReady();
			});
			
			// Done
			return true;
		},
		
		/**
		 * Configure our application
		 * @param {Object} options
		 */
		configure: function ( options ) {
			var History = $.History;
			
			// Set options
			History.options = $.extend(History.options, options);
			
			// Done
			return true;
		},
		
		domReadied: false,
		domReady: function ( ) {
			var History = $.History;
			
			// Runonce
			if ( History.domRedied ) {
				return;
			}
			History.domRedied = true;
			
			// Define window
			History.$window = $(window);
			
			// Apply the hashchange function
			History.$window.bind('hashchange', this.hashchange);
			
			// Force hashchange support for all browsers
			setTimeout(History.hashchangeLoader, 200);
			
			// All done
			return true;
		},
		
		/**
		 * Enable hashchange for all browsers
		 */
		hashchangeLoader: function () {
			var History = $.History;
			
			// More is needed for non IE8 browsers
			if ( !($.browser.msie && parseInt($.browser.version) >= 8) ) {	
				// We are not IE8
			
				// State our checker function, it is used to constantly check the location to detect a change
				var checker;
				
				// Handle depending on the browser
				if ( $.browser.msie ) {
					// We are still IE
				
					// Append and $iframe to the document, as $iframes are required for back and forward
					// Create a hidden $iframe for hash change tracking
					History.$iframe = $('<iframe id="jquery-history-iframe" style="display: none;"></$iframe>').prependTo(document.body)[0];
					
					// Create initial history entry
					History.$iframe.contentWindow.document.open();
					History.$iframe.contentWindow.document.close();
					
					// Check for initial state
					var hash = History.getHash();
					if ( hash ) {
						// Apply it to the iframe
						History.$iframe.contentWindow.document.location.hash = hash;
					}
					
					// Define the checker function (for bookmarks)
					checker = function ( ) {
						var iframeHash = History.format(History.$iframe.contentWindow.document.location.hash);
						if ( History.getState() !== iframeHash ) {
							// Back Button Change
							History.setHash(History.$iframe.contentWindow.document.location.hash);
						}
						var hash = History.getHash();
						if ( History.getState() !== hash ) {
							// The has has changed
							History.go(hash);
						}
					};
				}
				else {
					// We are not IE
				
					// Define the checker function (for bookmarks, back, forward)
					checker = function ( ) {
						var hash = History.getHash();
						if ( History.getState() !== hash ) {
							// The has has changed
							History.go(hash);
						}
					};
				}
				
				// Apply the checker function
				if ( !($.browser.msie && parseInt($.browser.version) < 8) ) {
					setInterval(checker, 200);
				} else {
					setInterval(checker, 1500);
				}
			}
			else {
				// We are IE8
				var hash = History.getHash();
				if (hash) {
					History.$window.trigger('hashchange');
				}
			}
			
			// Done
			return true;
		}
	
	}; // We have finished extending/defining our Plugin

	// --------------------------------------------------
	// Finish up
	
	// Instantiate
	$.History.construct();

// Finished definition

})(jQuery); // We are done with our plugin, so lets call it with jQuery as the argument
;
(function($){var abs=Math.abs,max=Math.max,min=Math.min,round=Math.round;function div(){return $('<div/>')}$.imgAreaSelect=function(img,options){var $img=$(img),imgLoaded,$box=div(),$area=div(),$border=div().add(div()).add(div()).add(div()),$outer=div().add(div()).add(div()).add(div()),$handles=$([]),$areaOpera,left,top,imgOfs,imgWidth,imgHeight,$parent,parOfs,zIndex=0,position='absolute',startX,startY,scaleX,scaleY,resizeMargin=10,resize,minWidth,minHeight,maxWidth,maxHeight,aspectRatio,shown,x1,y1,x2,y2,selection={x1:0,y1:0,x2:0,y2:0,width:0,height:0},docElem=document.documentElement,$p,d,i,o,w,h,adjusted;function viewX(x){return x+imgOfs.left-parOfs.left}function viewY(y){return y+imgOfs.top-parOfs.top}function selX(x){return x-imgOfs.left+parOfs.left}function selY(y){return y-imgOfs.top+parOfs.top}function evX(event){return event.pageX-parOfs.left}function evY(event){return event.pageY-parOfs.top}function getSelection(noScale){var sx=noScale||scaleX,sy=noScale||scaleY;return{x1:round(selection.x1*sx),y1:round(selection.y1*sy),x2:round(selection.x2*sx),y2:round(selection.y2*sy),width:round(selection.x2*sx)-round(selection.x1*sx),height:round(selection.y2*sy)-round(selection.y1*sy)}}function setSelection(x1,y1,x2,y2,noScale){var sx=noScale||scaleX,sy=noScale||scaleY;selection={x1:round(x1/sx),y1:round(y1/sy),x2:round(x2/sx),y2:round(y2/sy)};selection.width=selection.x2-selection.x1;selection.height=selection.y2-selection.y1}function adjust(){if(!$img.width())return;imgOfs={left:round($img.offset().left),top:round($img.offset().top)};imgWidth=$img.width();imgHeight=$img.height();minWidth=options.minWidth||0;minHeight=options.minHeight||0;maxWidth=min(options.maxWidth||1<<24,imgWidth);maxHeight=min(options.maxHeight||1<<24,imgHeight);if($().jquery=='1.3.2'&&position=='fixed'&&!docElem['getBoundingClientRect']){imgOfs.top+=max(document.body.scrollTop,docElem.scrollTop);imgOfs.left+=max(document.body.scrollLeft,docElem.scrollLeft)}parOfs=$.inArray($parent.css('position'),['absolute','relative'])+1?{left:round($parent.offset().left)-$parent.scrollLeft(),top:round($parent.offset().top)-$parent.scrollTop()}:position=='fixed'?{left:$(document).scrollLeft(),top:$(document).scrollTop()}:{left:0,top:0};left=viewX(0);top=viewY(0);if(selection.x2>imgWidth||selection.y2>imgHeight)doResize()}function update(resetKeyPress){if(!shown)return;$box.css({left:viewX(selection.x1),top:viewY(selection.y1)}).add($area).width(w=selection.width).height(h=selection.height);$area.add($border).add($handles).css({left:0,top:0});$border.width(max(w-$border.outerWidth()+$border.innerWidth(),0)).height(max(h-$border.outerHeight()+$border.innerHeight(),0));$($outer[0]).css({left:left,top:top,width:selection.x1,height:imgHeight});$($outer[1]).css({left:left+selection.x1,top:top,width:w,height:selection.y1});$($outer[2]).css({left:left+selection.x2,top:top,width:imgWidth-selection.x2,height:imgHeight});$($outer[3]).css({left:left+selection.x1,top:top+selection.y2,width:w,height:imgHeight-selection.y2});w-=$handles.outerWidth();h-=$handles.outerHeight();switch($handles.length){case 8:$($handles[4]).css({left:w/2});$($handles[5]).css({left:w,top:h/2});$($handles[6]).css({left:w/2,top:h});$($handles[7]).css({top:h/2});case 4:$handles.slice(1,3).css({left:w});$handles.slice(2,4).css({top:h})}if(resetKeyPress!==false){if($.imgAreaSelect.keyPress!=docKeyPress)$(document).unbind($.imgAreaSelect.keyPress,$.imgAreaSelect.onKeyPress);if(options.keys)$(document)[$.imgAreaSelect.keyPress]($.imgAreaSelect.onKeyPress=docKeyPress)}if($.browser.msie&&$border.outerWidth()-$border.innerWidth()==2){$border.css('margin',0);setTimeout(function(){$border.css('margin','auto')},0)}}function doUpdate(resetKeyPress){adjust();update(resetKeyPress);x1=viewX(selection.x1);y1=viewY(selection.y1);x2=viewX(selection.x2);y2=viewY(selection.y2)}function hide($elem,fn){options.fadeSpeed?$elem.fadeOut(options.fadeSpeed,fn):$elem.hide()}function areaMouseMove(event){var x=selX(evX(event))-selection.x1,y=selY(evY(event))-selection.y1;if(!adjusted){adjust();adjusted=true;$box.one('mouseout',function(){adjusted=false})}resize='';if(options.resizable){if(y<=resizeMargin)resize='n';else if(y>=selection.height-resizeMargin)resize='s';if(x<=resizeMargin)resize+='w';else if(x>=selection.width-resizeMargin)resize+='e'}$box.css('cursor',resize?resize+'-resize':options.movable?'move':'');if($areaOpera)$areaOpera.toggle()}function docMouseUp(event){$('body').css('cursor','');if(options.autoHide||selection.width*selection.height==0)hide($box.add($outer),function(){$(this).hide()});options.onSelectEnd(img,getSelection());$(document).unbind('mousemove',selectingMouseMove);$box.mousemove(areaMouseMove)}function areaMouseDown(event){if(event.which!=1)return false;adjust();if(resize){$('body').css('cursor',resize+'-resize');x1=viewX(selection[/w/.test(resize)?'x2':'x1']);y1=viewY(selection[/n/.test(resize)?'y2':'y1']);$(document).mousemove(selectingMouseMove).one('mouseup',docMouseUp);$box.unbind('mousemove',areaMouseMove)}else if(options.movable){startX=left+selection.x1-evX(event);startY=top+selection.y1-evY(event);$box.unbind('mousemove',areaMouseMove);$(document).mousemove(movingMouseMove).one('mouseup',function(){options.onSelectEnd(img,getSelection());$(document).unbind('mousemove',movingMouseMove);$box.mousemove(areaMouseMove)})}else $img.mousedown(event);return false}function fixAspectRatio(xFirst){if(aspectRatio)if(xFirst){x2=max(left,min(left+imgWidth,x1+abs(y2-y1)*aspectRatio*(x2>x1||-1)));y2=round(max(top,min(top+imgHeight,y1+abs(x2-x1)/aspectRatio*(y2>y1||-1))));x2=round(x2)}else{y2=max(top,min(top+imgHeight,y1+abs(x2-x1)/aspectRatio*(y2>y1||-1)));x2=round(max(left,min(left+imgWidth,x1+abs(y2-y1)*aspectRatio*(x2>x1||-1))));y2=round(y2)}}function doResize(){x1=min(x1,left+imgWidth);y1=min(y1,top+imgHeight);if(abs(x2-x1)<minWidth){x2=x1-minWidth*(x2<x1||-1);if(x2<left)x1=left+minWidth;else if(x2>left+imgWidth)x1=left+imgWidth-minWidth}if(abs(y2-y1)<minHeight){y2=y1-minHeight*(y2<y1||-1);if(y2<top)y1=top+minHeight;else if(y2>top+imgHeight)y1=top+imgHeight-minHeight}x2=max(left,min(x2,left+imgWidth));y2=max(top,min(y2,top+imgHeight));fixAspectRatio(abs(x2-x1)<abs(y2-y1)*aspectRatio);if(abs(x2-x1)>maxWidth){x2=x1-maxWidth*(x2<x1||-1);fixAspectRatio()}if(abs(y2-y1)>maxHeight){y2=y1-maxHeight*(y2<y1||-1);fixAspectRatio(true)}selection={x1:selX(min(x1,x2)),x2:selX(max(x1,x2)),y1:selY(min(y1,y2)),y2:selY(max(y1,y2)),width:abs(x2-x1),height:abs(y2-y1)};update();options.onSelectChange(img,getSelection())}function selectingMouseMove(event){x2=resize==''||/w|e/.test(resize)||aspectRatio?evX(event):viewX(selection.x2);y2=resize==''||/n|s/.test(resize)||aspectRatio?evY(event):viewY(selection.y2);doResize();return false}function doMove(newX1,newY1){x2=(x1=newX1)+selection.width;y2=(y1=newY1)+selection.height;$.extend(selection,{x1:selX(x1),y1:selY(y1),x2:selX(x2),y2:selY(y2)});update();options.onSelectChange(img,getSelection())}function movingMouseMove(event){x1=max(left,min(startX+evX(event),left+imgWidth-selection.width));y1=max(top,min(startY+evY(event),top+imgHeight-selection.height));doMove(x1,y1);event.preventDefault();return false}function startSelection(){adjust();x2=x1;y2=y1;doResize();resize='';if($outer.is(':not(:visible)'))$box.add($outer).hide().fadeIn(options.fadeSpeed||0);shown=true;$(document).unbind('mouseup',cancelSelection).mousemove(selectingMouseMove).one('mouseup',docMouseUp);$box.unbind('mousemove',areaMouseMove);options.onSelectStart(img,getSelection())}function cancelSelection(){$(document).unbind('mousemove',startSelection);hide($box.add($outer));selection={x1:selX(x1),y1:selY(y1),x2:selX(x1),y2:selY(y1),width:0,height:0};options.onSelectChange(img,getSelection());options.onSelectEnd(img,getSelection())}function imgMouseDown(event){if(event.which!=1||$outer.is(':animated'))return false;adjust();startX=x1=evX(event);startY=y1=evY(event);$(document).one('mousemove',startSelection).one('mouseup',cancelSelection);return false}function windowResize(){doUpdate(false)}function imgLoad(){imgLoaded=true;setOptions(options=$.extend({classPrefix:'imgareaselect',movable:true,resizable:true,parent:'body',onInit:function(){},onSelectStart:function(){},onSelectChange:function(){},onSelectEnd:function(){}},options));$box.add($outer).css({visibility:''});if(options.show){shown=true;adjust();update();$box.add($outer).hide().fadeIn(options.fadeSpeed||0)}setTimeout(function(){options.onInit(img,getSelection())},0)}var docKeyPress=function(event){var k=options.keys,d,t,key=event.keyCode;d=!isNaN(k.alt)&&(event.altKey||event.originalEvent.altKey)?k.alt:!isNaN(k.ctrl)&&event.ctrlKey?k.ctrl:!isNaN(k.shift)&&event.shiftKey?k.shift:!isNaN(k.arrows)?k.arrows:10;if(k.arrows=='resize'||(k.shift=='resize'&&event.shiftKey)||(k.ctrl=='resize'&&event.ctrlKey)||(k.alt=='resize'&&(event.altKey||event.originalEvent.altKey))){switch(key){case 37:d=-d;case 39:t=max(x1,x2);x1=min(x1,x2);x2=max(t+d,x1);fixAspectRatio();break;case 38:d=-d;case 40:t=max(y1,y2);y1=min(y1,y2);y2=max(t+d,y1);fixAspectRatio(true);break;default:return}doResize()}else{x1=min(x1,x2);y1=min(y1,y2);switch(key){case 37:doMove(max(x1-d,left),y1);break;case 38:doMove(x1,max(y1-d,top));break;case 39:doMove(x1+min(d,imgWidth-selX(x2)),y1);break;case 40:doMove(x1,y1+min(d,imgHeight-selY(y2)));break;default:return}}return false};function styleOptions($elem,props){for(option in props)if(options[option]!==undefined)$elem.css(props[option],options[option])}function setOptions(newOptions){if(newOptions.parent)($parent=$(newOptions.parent)).append($box.add($outer));$.extend(options,newOptions);adjust();if(newOptions.handles!=null){$handles.remove();$handles=$([]);i=newOptions.handles?newOptions.handles=='corners'?4:8:0;while(i--)$handles=$handles.add(div());$handles.addClass(options.classPrefix+'-handle').css({position:'absolute',fontSize:0,zIndex:zIndex+1||1});if(!parseInt($handles.css('width'))>=0)$handles.width(5).height(5);if(o=options.borderWidth)$handles.css({borderWidth:o,borderStyle:'solid'});styleOptions($handles,{borderColor1:'border-color',borderColor2:'background-color',borderOpacity:'opacity'})}scaleX=options.imageWidth/imgWidth||1;scaleY=options.imageHeight/imgHeight||1;if(newOptions.x1!=null){setSelection(newOptions.x1,newOptions.y1,newOptions.x2,newOptions.y2);newOptions.show=!newOptions.hide}if(newOptions.keys)options.keys=$.extend({shift:1,ctrl:'resize'},newOptions.keys);$outer.addClass(options.classPrefix+'-outer');$area.addClass(options.classPrefix+'-selection');for(i=0;i++<4;)$($border[i-1]).addClass(options.classPrefix+'-border'+i);styleOptions($area,{selectionColor:'background-color',selectionOpacity:'opacity'});styleOptions($border,{borderOpacity:'opacity',borderWidth:'border-width'});styleOptions($outer,{outerColor:'background-color',outerOpacity:'opacity'});if(o=options.borderColor1)$($border[0]).css({borderStyle:'solid',borderColor:o});if(o=options.borderColor2)$($border[1]).css({borderStyle:'dashed',borderColor:o});$box.append($area.add($border).add($handles).add($areaOpera));if($.browser.msie){if(o=$outer.css('filter').match(/opacity=([0-9]+)/))$outer.css('opacity',o[1]/100);if(o=$border.css('filter').match(/opacity=([0-9]+)/))$border.css('opacity',o[1]/100)}if(newOptions.hide)hide($box.add($outer));else if(newOptions.show&&imgLoaded){shown=true;$box.add($outer).fadeIn(options.fadeSpeed||0);doUpdate()}aspectRatio=(d=(options.aspectRatio||'').split(/:/))[0]/d[1];$img.add($outer).unbind('mousedown',imgMouseDown);if(options.disable||options.enable===false){$box.unbind('mousemove',areaMouseMove).unbind('mousedown',areaMouseDown);$(window).unbind('resize',windowResize)}else{if(options.enable||options.disable===false){if(options.resizable||options.movable)$box.mousemove(areaMouseMove).mousedown(areaMouseDown);$(window).resize(windowResize)}if(!options.persistent)$img.add($outer).mousedown(imgMouseDown)}options.enable=options.disable=undefined}this.remove=function(){$img.unbind('mousedown',imgMouseDown);$box.add($outer).remove()};this.getOptions=function(){return options};this.setOptions=setOptions;this.getSelection=getSelection;this.setSelection=setSelection;this.update=doUpdate;$p=$img;while($p.length){zIndex=max(zIndex,!isNaN($p.css('z-index'))?$p.css('z-index'):zIndex);if($p.css('position')=='fixed')position='fixed';$p=$p.parent(':not(body)')}zIndex=options.zIndex||zIndex;if($.browser.msie)$img.attr('unselectable','on');$.imgAreaSelect.keyPress=$.browser.msie||$.browser.safari?'keydown':'keypress';if($.browser.opera)$areaOpera=div().css({width:'100%',height:'100%',position:'absolute',zIndex:zIndex+2||2});$box.add($outer).css({visibility:'hidden',position:position,overflow:'hidden',zIndex:zIndex||'0'});$box.css({zIndex:zIndex+2||2});$area.add($border).css({position:'absolute',fontSize:0});img.complete||img.readyState=='complete'||!$img.is('img')?imgLoad():$img.one('load',imgLoad)};$.fn.imgAreaSelect=function(options){options=options||{};this.each(function(){if($(this).data('imgAreaSelect')){if(options.remove){$(this).data('imgAreaSelect').remove();$(this).removeData('imgAreaSelect')}else $(this).data('imgAreaSelect').setOptions(options)}else if(!options.remove){if(options.enable===undefined&&options.disable===undefined)options.enable=true;$(this).data('imgAreaSelect',new $.imgAreaSelect(this,options))}});if(options.instance)return $(this).data('imgAreaSelect');return this}})(jQuery);;
jQuery.JSON = {
    useHasOwn : ({}.hasOwnProperty ? true : false),
    pad : function(n) {
        return n < 10 ? "0" + n : n;
    },
    m : {
        "\b": '\\b',
        "\t": '\\t',
        "\n": '\\n',
        "\f": '\\f',
        "\r": '\\r',
        '"' : '\\"',
        "\\": '\\\\'
    },
    encodeString : function(s){
        if (/["\\\x00-\x1f]/.test(s)) {
            return '"' + s.replace(/([\x00-\x1f\\"])/g, function(a, b) {
                    var c = jQuery.JSON.m[b];
                    if(c){
                        return c;
                    }
                    c = b.charCodeAt();
                    return "\\u00" +
                    Math.floor(c / 16).toString(16) +
                    (c % 16).toString(16);
            }) + '"';
        }
        return '"' + s + '"';
    },
    encodeArray : function(o){
        var a = ["["], b, i, l = o.length, v;
        for (i = 0; i < l; i += 1) {
            v = o[i];
            switch (typeof v) {
            case "undefined":
            case "function":
            case "unknown":
                break;
            default:
                if (b) {
                    a.push(',');
                }
                a.push(v === null ? "null" : this.encode(v));
                b = true;
            }
        }
        a.push("]");
        return a.join("");
    },
    encodeDate : function(o){
        return '"' + o.getFullYear() + "-" +
        pad(o.getMonth() + 1) + "-" +
        pad(o.getDate()) + "T" +
        pad(o.getHours()) + ":" +
        pad(o.getMinutes()) + ":" +
        pad(o.getSeconds()) + '"';
    },
    encode : function(o){
        if(typeof o == "undefined" || o === null){
            return "null";
        }else if(o instanceof Array){
            return this.encodeArray(o);
        }else if(o instanceof Date){
            return this.encodeDate(o);
        }else if(typeof o == "string"){
            return this.encodeString(o);
        }else if(typeof o == "number"){
            return isFinite(o) ? String(o) : "null";
        }else if(typeof o == "boolean"){
            return String(o);
        }else {
            var self = this;
            var a = ["{"], b, i, v;
            for (i in o) {
                if(!this.useHasOwn || o.hasOwnProperty(i)) {
                    v = o[i];
                    switch (typeof v) {
                    case "undefined":
                    case "function":
                    case "unknown":
                        break;
                    default:
                        if(b){
                            a.push(',');
                        }
                        a.push(self.encode(i), ":",
                            v === null ? "null" : self.encode(v));
                        b = true;
                    }
                }
            }
            a.push("}");
            return a.join("");
        }
    },
    decode : function(json){
        return eval("(" + json + ')');
    }
};
;
/**
 * Copyright (c) 2007-2012 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * @author Ariel Flesler
 * @version 1.4.3.1
 */
;(function($){var h=$.scrollTo=function(a,b,c){$(window).scrollTo(a,b,c)};h.defaults={axis:'xy',duration:parseFloat($.fn.jquery)>=1.3?0:1,limit:true};h.window=function(a){return $(window)._scrollable()};$.fn._scrollable=function(){return this.map(function(){var a=this,isWin=!a.nodeName||$.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!isWin)return a;var b=(a.contentWindow||a).document||a.ownerDocument||a;return/webkit/i.test(navigator.userAgent)||b.compatMode=='BackCompat'?b.body:b.documentElement})};$.fn.scrollTo=function(e,f,g){if(typeof f=='object'){g=f;f=0}if(typeof g=='function')g={onAfter:g};if(e=='max')e=9e9;g=$.extend({},h.defaults,g);f=f||g.duration;g.queue=g.queue&&g.axis.length>1;if(g.queue)f/=2;g.offset=both(g.offset);g.over=both(g.over);return this._scrollable().each(function(){if(e==null)return;var d=this,$elem=$(d),targ=e,toff,attr={},win=$elem.is('html,body');switch(typeof targ){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ)){targ=both(targ);break}targ=$(targ,this);if(!targ.length)return;case'object':if(targ.is||targ.style)toff=(targ=$(targ)).offset()}$.each(g.axis.split(''),function(i,a){var b=a=='x'?'Left':'Top',pos=b.toLowerCase(),key='scroll'+b,old=d[key],max=h.max(d,a);if(toff){attr[key]=toff[pos]+(win?0:old-$elem.offset()[pos]);if(g.margin){attr[key]-=parseInt(targ.css('margin'+b))||0;attr[key]-=parseInt(targ.css('border'+b+'Width'))||0}attr[key]+=g.offset[pos]||0;if(g.over[pos])attr[key]+=targ[a=='x'?'width':'height']()*g.over[pos]}else{var c=targ[pos];attr[key]=c.slice&&c.slice(-1)=='%'?parseFloat(c)/100*max:c}if(g.limit&&/^\d+$/.test(attr[key]))attr[key]=attr[key]<=0?0:Math.min(attr[key],max);if(!i&&g.queue){if(old!=attr[key])animate(g.onAfterFirst);delete attr[key]}});animate(g.onAfter);function animate(a){$elem.animate(attr,f,g.easing,a&&function(){a.call(this,e,g)})}}).end()};h.max=function(a,b){var c=b=='x'?'Width':'Height',scroll='scroll'+c;if(!$(a).is('html,body'))return a[scroll]-$(a)[c.toLowerCase()]();var d='client'+c,html=a.ownerDocument.documentElement,body=a.ownerDocument.body;return Math.max(html[scroll],body[scroll])-Math.min(html[d],body[d])};function both(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);;
// origin: http://www.json.org/json2.js

// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

if (!this.JSON) {
    this.JSON = {};
}

(function () {

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function (key) {

            return isFinite(this.valueOf()) ?
                   this.getUTCFullYear()   + '-' +
                 f(this.getUTCMonth() + 1) + '-' +
                 f(this.getUTCDate())      + 'T' +
                 f(this.getUTCHours())     + ':' +
                 f(this.getUTCMinutes())   + ':' +
                 f(this.getUTCSeconds())   + 'Z' : null;
        };

        String.prototype.toJSON =
        Number.prototype.toJSON =
        Boolean.prototype.toJSON = function (key) {
            return this.valueOf();
        };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ?
            '"' + string.replace(escapable, function (a) {
                var c = meta[a];
                return typeof c === 'string' ? c :
                    '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
            }) + '"' :
            '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
                typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
        case 'string':
            return quote(value);

        case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

            return isFinite(value) ? String(value) : 'null';

        case 'boolean':
        case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

            return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

        case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

            if (!value) {
                return 'null';
            }

// Make an array to hold the partial results of stringifying this object value.

            gap += indent;
            partial = [];

// Is the value an array?

            if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                length = value.length;
                for (i = 0; i < length; i += 1) {
                    partial[i] = str(i, value) || 'null';
                }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                v = partial.length === 0 ? '[]' :
                    gap ? '[\n' + gap +
                            partial.join(',\n' + gap) + '\n' +
                                mind + ']' :
                          '[' + partial.join(',') + ']';
                gap = mind;
                return v;
            }

// If the replacer is an array, use it to select the members to be stringified.

            if (rep && typeof rep === 'object') {
                length = rep.length;
                for (i = 0; i < length; i += 1) {
                    k = rep[i];
                    if (typeof k === 'string') {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            } else {

// Otherwise, iterate through all of the keys in the object.

                for (k in value) {
                    if (Object.hasOwnProperty.call(value, k)) {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

            v = partial.length === 0 ? '{}' :
                gap ? '{\n' + gap + partial.join(',\n' + gap) + '\n' +
                        mind + '}' : '{' + partial.join(',') + '}';
            gap = mind;
            return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                    (typeof replacer !== 'object' ||
                     typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/
.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
.replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function' ?
                    walk({'': j}, '') : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());;
/*
 * jQuery store - Plugin for persistent data storage using localStorage, userData (and window.name)
 * 
 * Authors: Rodney Rehm
 * Web: http://medialize.github.com/jQuery-store/
 * 
 * Licensed under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 */

/**********************************************************************************
 * INITIALIZE EXAMPLES:
 **********************************************************************************
 * 	// automatically detect best suited storage driver and use default serializers
 *	$.storage = new $.store();
 *	// optionally initialize with specific driver and or serializers
 *	$.storage = new $.store( [driver] [, serializers] );
 *		driver		can be the key (e.g. "windowName") or the driver-object itself
 *		serializers	can be a list of named serializers like $.store.serializers
 **********************************************************************************
 * USAGE EXAMPLES:
 **********************************************************************************
 *	$.storage.get( key );			// retrieves a value
 *	$.storage.set( key, value );	// saves a value
 *	$.storage.del( key );			// deletes a value
 *	$.storage.flush();				// deletes aall values
 **********************************************************************************
 */

(function($,undefined){

/**********************************************************************************
 * $.store base and convinience accessor
 **********************************************************************************/

$.store = function( driver, serializers )
{
	var that = this;
	
	if( typeof driver == 'string' )
	{
		if( $.store.drivers[ driver ] )
			this.driver = $.store.drivers[ driver ];
		else
			throw new Error( 'Unknown driver '+ driver );
	}
	else if( typeof driver == 'object' )
	{
		var invalidAPI = !$.isFunction( driver.init )
			|| !$.isFunction( driver.get )
			|| !$.isFunction( driver.set )
			|| !$.isFunction( driver.del )
			|| !$.isFunction( driver.flush );
			
		if( invalidAPI )
			throw new Error( 'The specified driver does not fulfill the API requirements' );
		
		this.driver = driver;
	}
	else
	{
		// detect and initialize storage driver
		$.each( $.store.drivers, function()
		{
			// skip unavailable drivers
			if( !$.isFunction( this.available ) || !this.available() )
				return true; // continue;
			
			that.driver = this;
			if( that.driver.init() === false )
			{
				that.driver = null;
				return true; // continue;
			}
			
			return false; // break;
		});
	}
	
	// use default serializers if not told otherwise
	if( !serializers )
		serializers = $.store.serializers;
	
	// intialize serializers
	this.serializers = {};
	$.each( serializers, function( key, serializer )
	{
		// skip invalid processors
		if( !$.isFunction( this.init ) )
			return true; // continue;
		
		that.serializers[ key ] = this;
		that.serializers[ key ].init( that.encoders, that.decoders );
	});
};


/**********************************************************************************
 * $.store API
 **********************************************************************************/

$.extend( $.store.prototype, {
	get: function( key )
	{
		var value = this.driver.get( key );
		return this.driver.encodes ? value : this.unserialize( value );
	},
	set: function( key, value )
	{
		this.driver.set( key, this.driver.encodes ? value : this.serialize( value ) );
	},
	del: function( key )
	{
		this.driver.del( key );
	},
	flush: function()
	{
		this.driver.flush();
	},
	driver : undefined,
	encoders : [],
	decoders : [],
	serialize: function( value )
	{
		var that = this;
		
		$.each( this.encoders, function()
		{
			var serializer = that.serializers[ this + "" ];
			if( !serializer || !serializer.encode )
				return true; // continue;
			try
			{
				value = serializer.encode( value );
			}
			catch( e ){}
		});

		return value;
	},
	unserialize: function( value )
	{
		var that = this;
		if( !value )
			return value;
		
		$.each( this.decoders, function()
		{
			var serializer = that.serializers[ this + "" ];
			if( !serializer || !serializer.decode )
				return true; // continue;

			value = serializer.decode( value );
		});

		return value;
	}
});


/**********************************************************************************
 * $.store drivers
 **********************************************************************************/

$.store.drivers = {
	// Firefox 3.5, Safari 4.0, Chrome 5, Opera 10.5, IE8
	'localStorage': {
		// see https://developer.mozilla.org/en/dom/storage#localStorage
		ident: "$.store.drivers.localStorage",
		scope: 'browser',
		available: function()
		{
			try
			{
				return !!window.localStorage;
			}
			catch(e)
			{
				// Firefox won't allow localStorage if cookies are disabled
				return false;
			}
		},
		init: $.noop,
		get: function( key )
		{
			return window.localStorage.getItem( key );
		},
		set: function( key, value )
		{
			window.localStorage.setItem( key, value );
		},
		del: function( key )
		{
			window.localStorage.removeItem( key );
		},
		flush: function()
		{
			window.localStorage.clear();
		}
	},
	
	// IE6, IE7
	'userData': {
		// see http://msdn.microsoft.com/en-us/library/ms531424.aspx
		ident: "$.store.drivers.userData",
		element: null,
		nodeName: 'userdatadriver',
		scope: 'browser',
		initialized: false,
		available: function()
		{
			try
			{
				return !!( document.documentElement && document.documentElement.addBehavior );
			}
			catch(e)
			{
				return false;
			}
		},
		init: function()
		{
			// $.store can only utilize one userData store at a time, thus avoid duplicate initialization
			if( this.initialized )
				return;
			
			try
			{
				// Create a non-existing element and append it to the root element (html)
				this.element = document.createElement( this.nodeName );
				document.documentElement.insertBefore( this.element, document.getElementsByTagName('title')[0] );
				// Apply userData behavior
				this.element.addBehavior( "#default#userData" );
				this.initialized = true;
			}
			catch( e )
			{
				return false; 
			}
		},
		get: function( key )
		{
			this.element.load( this.nodeName );
			return this.element.getAttribute( key );
		},
		set: function( key, value )
		{
			this.element.setAttribute( key, value );
			this.element.save( this.nodeName );
		},
		del: function( key )
		{
			this.element.removeAttribute( key );
			this.element.save( this.nodeName );
			
		},
		flush: function()
		{
			// flush by expiration
			this.element.expires = (new Date).toUTCString();
			this.element.save( this.nodeName );
		}
	},
	
	// most other browsers
	'windowName': {
		ident: "$.store.drivers.windowName",
		scope: 'window',
		cache: {},
		encodes: true,
		available: function()
		{
			return true;
		},
		init: function()
		{
			this.load();
		},
		save: function()
		{
			window.name = $.store.serializers.json.encode( this.cache );
		},
		load: function()
		{
			try
			{
				this.cache = $.store.serializers.json.decode( window.name + "" );
				if( typeof this.cache != "object" )
					this.cache = {};
			}
			catch(e)
			{
				this.cache = {};
				window.name = "{}";
			}
		},
		get: function( key )
		{
			return this.cache[ key ];
		},
		set: function( key, value )
		{
			this.cache[ key ] = value;
			this.save();
		},
		del: function( key )
		{
			try
			{
				delete this.cache[ key ];
			}
			catch(e)
			{
				this.cache[ key ] = undefined;
			}
			
			this.save();
		},
		flush: function()
		{
			window.name = "{}";
		}
	}
};

/**********************************************************************************
 * $.store serializers
 **********************************************************************************/

$.store.serializers = {
	
	'json': {
		ident: "$.store.serializers.json",
		init: function( encoders, decoders )
		{
			encoders.push( "json" );
			decoders.push( "json" );
		},
		encode: ((typeof(JSON) == 'object')?JSON.stringify:$.JSON.stringify),
		decode: ((typeof(JSON) == 'object')?JSON.parse:$.JSON.parse)
	},
	
	// TODO: html serializer
	// 'html' : {},
	
	'xml': {
		ident: "$.store.serializers.xml",
		init: function( encoders, decoders )
		{
			encoders.unshift( "xml" );
			decoders.push( "xml" );
		},
		
		// wouldn't be necessary if jQuery exposed this function
		isXML: function( value )
		{
			var documentElement = ( value ? value.ownerDocument || value : 0 ).documentElement;
			return documentElement ? documentElement.nodeName.toLowerCase() !== "html" : false;
		},

		// encodes a XML node to string (taken from $.jStorage, MIT License)
		encode: function( value )
		{
			if( !value || value._serialized || !this.isXML( value ) )
				return value;

			var _value = { _serialized: this.ident, value: value };
			
			try
			{
				// Mozilla, Webkit, Opera
				_value.value = new XMLSerializer().serializeToString( value );
				return _value;
			}
			catch(E1)
			{
				try
				{
					// Internet Explorer
					_value.value = value.xml;
					return _value;
				}
				catch(E2){}
			}
			
			return value;
		},
		
		// decodes a XML node from string (taken from $.jStorage, MIT License)
		decode: function( value )
		{
			if( !value || !value._serialized || value._serialized != this.ident )
				return value;

			var dom_parser = ( "DOMParser" in window && (new DOMParser()).parseFromString );
			if( !dom_parser && window.ActiveXObject )
			{
				dom_parser = function( _xmlString )
				{
					var xml_doc = new ActiveXObject( 'Microsoft.XMLDOM' );
					xml_doc.async = 'false';
					xml_doc.loadXML( _xmlString );
					return xml_doc;
				}
			}

			if( !dom_parser )
			{
				return undefined;
			}
			
			value.value = dom_parser.call(
				"DOMParser" in window && (new DOMParser()) || window, 
				value.value, 
				'text/xml'
			);
			
			return this.isXML( value.value ) ? value.value : undefined;
		}
	}
};

})(jQuery);;
/**
* hoverIntent r6 // 2011.02.26 // jQuery 1.5.1+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
* 
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne brian(at)cherne(dot)net
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev])}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev])};var handleHover=function(e){var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t)}if(e.type=="mouseenter"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob)},cfg.timeout)}}};return this.bind('mouseenter',handleHover).bind('mouseleave',handleHover)}})(jQuery);;
jQuery.fn.waDialog = function (options) {
    options = jQuery.extend({
        loading_header: '',
        title: '',
        esc: true,
        buttons: null,
        url: null,
        url_reload: true,
        'class': null, // className is a synonym
        content: null,
        'width': 0,
        'height': 0,
        'min-width': 0,
        'min-height': 0,
        offsetTop: null,
        offsetLeft: null,
        disableButtonsOnSubmit: false,
        onLoad: null,
        onCancel: null,
        onSubmit: null
    }, options || {});

    var d = $(this);

    var id = d.attr('id');
    if (id && !d.hasClass('dialog')) {
        d.removeAttr('id');
        if ($("#" + id).length) {
            if (options.url) {
                d = $("#" + id);
                if (!options.url_reload) {
                    options.url = null;
                }
            } else {
                $("#" + id).remove();
            }
        }
    }

    var cl = (options['class'] || options['className']) ? (options['class'] || options['className']) : (d.attr('class') || '');

    if (!d.hasClass('dialog')) {
        var content = $(this);
        var d = $('<div ' + (id ? 'id = "' + id + '"' : '') + ' class="dialog ' + cl + '" style="display: none">'+
                    '<div class="dialog-background"></div>'+
                    '<div class="dialog-window"></div>'+
              '</div>').appendTo('body');
        if (content.find('.dialog-content').length || content.find('.dialog-buttons').length) {
            $('.dialog-window', d).append(content.show());
            var dc = content.find('.dialog-content');
            if (dc.length) {
                var tmp = $('<div class="dialog-content-indent"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
            dc = content.find('.dialog-buttons');
            if (dc.length) {
                var tmp = $('<div class="dialog-buttons-gradient"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
        } else {
            $('.dialog-window', d).append(
                    (options.onSubmit ? '<form method="post" action="">' : '') +
                    '<div class="dialog-content">'+
                        '<div class="dialog-content-indent">'+
                            // content goes here
                        '</div>'+
                    '</div>'+
                    '<div class="dialog-buttons">'+
                        '<div class="dialog-buttons-gradient">'+
                            // buttons go here
                        '</div>'+
                    '</div>'+
                    (options.onSubmit ? '</form>' : '')
            );
            d.find('.dialog-content-indent').append(content.show());
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
        if (options.url) {
            d.find('.dialog-content-indent').append('<h1>'+(options.loading_header || '')+'<i class="icon16 loading"></i></h1>');
        } else if (options.content) {
            d.find('.dialog-content-indent').append(options.content);
        }
        if (options.title) {
            d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
        }
    } else {
        if (options.content) {
            d.find('.dialog-content-indent').html(options.content);
            if (options.title) {
                d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
            }
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
    }

    if (!d.find('.dialog-background').length) {
        d.prepend('<div class="dialog-background"> </div>');
    }

    d.unbind('close').bind('close', function () {
        if (options.onClose) {
            options.onClose.call($(this));
        }
        $(this).hide();
    });

    var css = ['width', 'height', 'min-width', 'min-height'];
    for (var k = 0; k < css.length; k++) {
        if (options[css[k]]) {
            if ((css[k] == 'height' && options[css[k]] < '300px') || (css[k] == 'width' && options[css[k]] < '400px')) {
                d.find('div.dialog-window').css('min-' + css[k], options[css[k]]);
            }
            d.find('div.dialog-window').css(css[k], options[css[k]]);
        }
    }

    if (options.disableButtonsOnSubmit) {
        d.find("input[type=submit]").removeAttr('disabled');
    }

    if (!d.parent().length) {
        d.appendTo('body');
    }


    d.show();

    if (options.url) {
        jQuery.get(options.url, function (response) {
            var el = $(response);
            if (el.find('.dialog-content').length || el.find('.dialog-buttons').length) {
                if (el.find('.dialog-content').length) {
                    d.find('.dialog-content-indent').empty().append(el.find('.dialog-content').contents());
                }
                if (el.find('.dialog-buttons').length) {
                    d.find('.dialog-buttons-gradient').empty().append(el.find('.dialog-buttons').contents());
                }
            } else {
                d.find('.dialog-content-indent').html(response);
            }
            d.trigger('wa-resize');
            if (options.onLoad) {
                options.onLoad.call(d.get(0));
            }
        });
    } else {
        if (options.onLoad) {
            options.onLoad.call(d.get(0));
        }
    }

    d.find('.dialog-buttons').delegate('.cancel', 'click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (options.onCancel) {
            options.onCancel.call(d.get(0));
        }
        d.trigger('close');
        return false;
    });


    if (options.onSubmit) {
        d.find('form').unbind('submit').submit(function () {
            if (options.disableButtonsOnSubmit) {
                d.find("input[type=submit]").attr('disabled', 'disabled');
            }
            return options.onSubmit.apply(this, [d]);
        });
    }

    d.unbind('wa-resize').bind('wa-resize', function () {
        var el = jQuery(this).find('.dialog-window');
        var dw = el.width();
        var dh = el.height();

        jQuery("body").css('min-height', dh+'px');

        var ww = jQuery(window).width();
        var wh = jQuery(window).height()-60;

        //centralize dialog
        var w = (ww-dw)/2 / ww;
        var h = (wh-dh-60)/2 / wh; //60px is the height of .dialog-buttons div
        if (h < 0) h = 0;
        if (w < 0) w = 0;

        el.css({
            'left': options.offsetLeft || (Math.round(w*100)+'%'),
            'top': options.offsetTop || (Math.round(h*100)+'%')
        });
    }).trigger('wa-resize');

    if (options.esc) {
        d.unbind('esc').bind('esc', function () {
            d.trigger('close');
        });
    }
    return d;
}

jQuery(window).resize(function () {
    jQuery(".dialog:visible").trigger('wa-resize');
});

jQuery(document).keyup(function(e) {
    //all dialogs should be closed when Escape is pressed
    if (e.keyCode == 27) {
        jQuery(".dialog:visible").trigger('esc');
    }
});;
