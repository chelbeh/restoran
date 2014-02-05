(function ($) { "use strict";

$.storage = new $.store();
$.wa.mailer = {
    options: {
        lang: 'en'
    },
    init: function (options) {
        if (typeof($.History) != "undefined") {
            $.History.bind(function () {
                $.wa.mailer.dispatch();
            });
        }
        this.options = $.extend(this.options, options);
        var hash = window.location.hash;
        if (hash === '#/' || !hash) {
            hash = $.storage.get('mailer/hash');
            if (hash && hash != null) {
                $.wa.setHash('#/' + hash);
            } else {
                this.dispatch();
            }
        } else {
            $.wa.setHash(hash);
        }

        $(window).bind('wa.dispatched', function() {
            // Highlight current item in sidebar, if exists
            $.wa.mailer.highlightSidebar();

            // Remove all non-persistent dialogs
            $('.dialog:not(.persistent)').empty().remove();
        });

        // Set up AJAX error handler
        $.wa.errorHandler = function() {};
        $(document).ajaxError(function(e, xhr, settings, exception) {

            // Never save pages causing an error as last hashes
            $.storage.del('mailer/hash');
            $.wa.mailer.stopDispatch(1);
            window.location.hash = '';

            // Show error in a nice safe iframe
            if (xhr.responseText) {
                var iframe = $('<iframe src="about:blank" style="width:100%;height:auto;min-height:500px;"></iframe>');
                $("#content").addClass('shadowed').empty().append(iframe);
                var ifrm = (iframe[0].contentWindow) ? iframe[0].contentWindow : (iframe[0].contentDocument.document) ? iframe[0].contentDocument.document : iframe[0].contentDocument;
                ifrm.document.open();
                ifrm.document.write(xhr.responseText);
                ifrm.document.close();

                // Close all existing dialogs
                $('.dialog:visible').trigger('close').remove();
            }
        });

        // Collapsible sidebar sections
        var toggleCollapse = function () {
            $.wa.mailer.collapseSidebarSection(this, 'toggle');
        };
        $(".collapse-handler", $('#wa-app')).die('click').live('click', toggleCollapse);
        this.restoreCollapsibleStatusInSidebar();

        // Reload sidebar once a minute when there are campaigns currently sending
        setTimeout($.wa.mailer.updateSendingSidebar, 60000);

        // development hotkeys for redispatch and sidebar reloading
        $(document).keypress(function(e) {
            if ((e.which == 10 || e.which == 13) && e.shiftKey) {
                $('#wa-app .sidebar .icon16').first().attr('class', 'icon16 loading');
                $.wa.mailer.reloadSidebar();
            }
            if ((e.which == 10 || e.which == 13) && e.ctrlKey) {
                $.wa.mailer.redispatch();
            }
        });
    },

    // if this is > 0 then this.dispatch() decrements it and ignores a call
    skipDispatch: 0,

    /** Cancel the next n automatic dispatches when window.location.hash changes */
    stopDispatch: function (n) {
        this.skipDispatch = n;
    },

    /** Force reload current hash-based 'page'. */
    redispatch: function() {
        this.currentHash = null;
        this.dispatch();
    },

    // last hash processed by this.dispatch()
    currentHash: null,

    /**
      * Called automatically when window.location.hash changes. Should not be called directly.
      * Call a corresponding handler by concatenating leading non-int parts of hash,
      * e.g. for #/aaa/bbb/ccc/111/dd/12/ee/ff
      * a method $.wa.controller.AaaBbbCccAction(['111', 'dd', '12', 'ee', 'ff']) will be called.
      */
    dispatch: function (hash) {
        if (this.skipDispatch > 0) {
            this.skipDispatch--;
            this.currentHash = null;
            return false;
        }
        if (hash == undefined) {
            hash = this.getHash();
        } else {
            hash = this.cleanHash(hash);
        }

        if (this.currentHash == hash) {
            return;
        }
        var old_hash = this.currentHash;
        this.currentHash = hash;

        var e = new $.Event('wa.before_dispatched');
        $(window).trigger(e);
        if (e.isDefaultPrevented()) {
            this.currentHash = old_hash;
            window.location.hash = old_hash;
            return false;
        }

        hash = hash.replace(/^[^#]*#\/*/, ''); /* */
        if (hash) {
            hash = hash.split('/');
            if (hash[0]) {
                var actionName = "";
                var attrMarker = hash.length;
                for (var i = 0; i < hash.length; i++) {
                    var h = hash[i];
                    if (i < 2) {
                        if (i === 0) {
                            actionName = h;
                        } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
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

                if (this[actionName + 'Action']) {
                    this[actionName + 'Action'].apply(this, attr);
                    // save last page to return to by default later
                    $.storage.set('mailer/hash', hash.join('/'));
                } else {
                    if (console) {
                        console.log('Invalid action name:', actionName+'Action');
                    }
                }
            } else {
                this.defaultAction();
            }
        } else {
            this.defaultAction();
        }

        $(window).trigger('wa.dispatched');
    },

    //
    // Actions called from this.dispatch()
    //

    defaultAction: function () {
        this.campaignsNewAction();
    },

    // List of templates
    templatesAction: function () {
        this.load("?module=templates");
    },

    // Import template from archive page
    templatesImportAction: function () {
        this.load("?module=templates&action=import1");
    },

    // Template editor
    templateAction: function (id) {
        this.load("?module=templates&action=edit&id=" + id);
    },

    // New template page
    templatesAddAction: function () {
        this.load("?module=templates&action=add");
    },

    // New campaign page: select template to use
    campaignsNewAction: function () {
        this.load("?module=campaigns&action=step0");
    },

    // Campaign editor: subject and body
    campaignsLetterAction: function(campaign_id, template_id) {
        if (campaign_id && campaign_id !== 'new') {
            this.load("?module=campaigns&action=step1&campaign_id="+campaign_id);
            return;
        }

        var template = '';
        if (template_id) {
            template = '&template_id='+template_id;
        }
        this.load("?module=campaigns&action=step1"+template);
    },

    // Campaign editor: recipients selection page
    campaignsRecipientsAction: function(p) {
        this.load("?module=campaigns&action=recipients&campaign_id="+p);
    },

    // Campaign editor: settings
    campaignsSendAction: function(campaign_id) {
        this.load("?module=campaigns&action=settings&campaign_id="+campaign_id);
    },

    // Report page for campaign sent or being sent
    campaignsReportAction: function(campaign_id) {
        this.load("?module=campaigns&action=report&campaign_id="+campaign_id);
    },

    // Options used for campaign sent or being sent
    campaignsOptionsAction: function(campaign_id) {
        this.load("?module=campaigns&action=settingsReadOnly&campaign_id="+campaign_id);
    },

    // List of campaigns currently sending
    campaignsSendingAction: function() {
        this.load("?module=campaigns&action=sending");
    },

    // List of campaigns successfully sent
    campaignsArchiveAction: function(start, order) {
        var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/campaigns/archive/'+start+'/'+order+'/').length).replace('/', '')) || '';
        if (search) {
            search = '&search='+encodeURIComponent(search);
        }
        start = start || '';
        if (start) {
            start = '&start='+encodeURIComponent(start);
        }
        order = order || $.storage.get('mailer/archive_order') || '';
        if (order) {
            $.storage.set('mailer/archive_order', order);
            order = '&order='+encodeURIComponent(order);
        }
        this.load("?module=campaigns&action=archive"+start+search+order);
    },

    // List of contacts unsubscribed from campaigns
    unsubscribedAction: function(start, order) {
        var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/unsubscribed/'+start+'/'+order+'/').length).replace('/', '')) || '';
        if (search) {
            search = '&search='+encodeURIComponent(search);
        }
        start = start || '';
        if (start) {
            start = '&start='+encodeURIComponent(start);
        }
        order = order || $.storage.get('mailer/unsubscribed_order') || '';
        if (order) {
            $.storage.set('mailer/unsubscribed_order', order);
            order = '&order='+encodeURIComponent(order);
        }
        this.load("?module=unsubscribed&action=list"+start+search+order);
    },

    // List of emails used to have delivering errors in the past
    undeliverableAction: function(start, order) {
        var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/undeliverable/'+start+'/'+order+'/').length).replace('/', '')) || '';
        if (search) {
            search = '&search='+encodeURIComponent(search);
        }
        start = start || '';
        if (start) {
            start = '&start='+encodeURIComponent(start);
        }
        order = order || $.storage.get('mailer/undeliverable_order') || '';
        if (order) {
            $.storage.set('mailer/undeliverable_order', order);
            order = '&order='+encodeURIComponent(order);
        }
        this.load("?module=undeliverable&action=list"+start+search+order);
    },

    // List of subscribers
    subscribersAction: function(start, order) {
        var search = decodeURIComponent($.wa.mailer.getHash().substr(('#/subscribers/'+start+'/'+order+'/').length).replace('/', '')) || '';
        if (search) {
            search = '&search='+encodeURIComponent(search);
        }
        start = start || '';
        if (start) {
            start = '&start='+encodeURIComponent(start);
        }
        order = order || $.storage.get('mailer/subscribers_order') || '';
        if (order) {
            $.storage.set('mailer/subscribers_order', order);
            order = '&order='+encodeURIComponent(order);
        }

        this.load("?module=subscribers&action=list"+start+search+order);
    },

    // Test message with SpamAssassin
    spamtestAction: function(id) {
        this.load("?module=spamtest&action=assassin&id="+id);
    },

    //
    // Helper functions
    //

    /* Show "Search results" link above the campaign page, if needed. */
    showLastSearchBreadcrumb: function(campaign_id) {
        if(!($.wa.mailer.showLastSearchBreadcrumb.last_search_ids || {})[campaign_id]) {
            return false;
        }
        var div = $('#content .m-envelope-stripes-2 .m-core-header');
        var a = div.find('a.last-search');
        if (!a.length) {
            a = $('<a href="" class="no-underline">'+$_('Search results')+'</a>');
            div.append('<i class="icon10 larr"></i>').append(a);
        }
        a.attr('href', '#/campaigns/archive/0/!id/'+encodeURIComponent($.wa.mailer.showLastSearchBreadcrumb.last_search_string||'')+'/').show();
        return true;
    },

    // Helper used across the campaign editor pages to save campaign via XHR.
    saveCampaign: function(form, button, callback, no_saved_hint) {
        var were_disabled = button.attr('disabled');
        button.attr('disabled', true).siblings('.process-message').remove();
        var process_message = $('<span class="process-message"><i class="icon16 loading" style="margin:6px 0 0 .5em"></i></span>');
        button.parent().append(process_message);
        $.post(form.attr('action'), form.serialize(), function(r) {
            if (!were_disabled) {
                button.attr('disabled', false);
            }
            if (r.status == 'ok') {
                var message_id = r.data;
                form.find('input[name="id"]').val(message_id);
                if (no_saved_hint) {
                    process_message.remove();
                } else {
                    process_message.find('.loading').removeClass('loading').addClass('yes');
                    process_message.append('<span> '+$_('Saved')+'</span>');
                    process_message.animate({ opacity: 0 }, 2000, function() {
                        process_message.remove();
                    });
                }
            } else {
                process_message.find('.loading').removeClass('loading').addClass('exclamation');
                console.log('Error saving campaign:', r);
            }

            if (callback) {
                callback.call(this, r);
            }
        }, 'json');
        return false;
    },

    // Helper to show number of campaign recipients in right sidebar
    showRecipientsInRightSidebar: function(rnum) {
        if(rnum && rnum !== '0' && rnum !== 'null') {
            if (rnum === true) {
                rnum = '1';
                $('#right-sidebar-recipients-number').hide();
            } else {
                $('#right-sidebar-recipients-number').show();
            }
            $('#right-sidebar-recipients-number').html(''+rnum);
            //$('#right-sidebar-recipients-number').html($_("Total selected:")+' '+rnum).removeClass('red').addClass('green');
        } else {
            rnum = '0';
            $('#right-sidebar-recipients-number').html(''+rnum).show();
            //$('#right-sidebar-recipients-number').html($_("not specified yet")).removeClass('green').addClass('red').show();
        }
    },

    /** Gracefully reload sidebar. */
    reloadSidebar: function() {
        $.get("?module=backend&action=sidebar", null, function (response) {
            var sb = $("#wa-app > .sidebar");
            sb.css('height', sb.height()+'px').html(response).css('height', ''); // prevents blinking in some browsers
            $.wa.mailer.highlightSidebar();
            $.wa.mailer.restoreCollapsibleStatusInSidebar();
        });
    },

    /**
     * Reload sidebar when there are campaigns currently sending.
     * Called every minute, see init().
     */
    updateSendingSidebar: function() {
        if ($.wa.mailer.sending_count && $.wa.mailer.getHash().substr(0, 19) != '#/campaigns/archive') {
            $.wa.mailer.reloadSidebar();
        }
        setTimeout($.wa.mailer.updateSendingSidebar, 60000);
    },

    /** Resize the editor depending on window height */
    autoresizeWYSIWYG: function(workzone, statusbar_height) {
        var statusbar = workzone.siblings('.statusbar');
        var r = Math.random();
        $.wa.mailer.step1_random = r;
        var resizeEditor = function() {
            // Unbind self if user left current page
            var buttons = $('#editor-step-1-buttons');
            if ($.wa.mailer.step1_random != r) {
                $(window).unbind('resize', resizeEditor);
                return;
            }

            // Calculate and update editor height
            var new_workzone_height = $(window).height() - workzone.offset().top - (statusbar.outerHeight() || statusbar_height || 0) - buttons.outerHeight() - 5;
            new_workzone_height = Math.max(300, new_workzone_height);
            workzone.height(new_workzone_height).find('iframe').height(new_workzone_height);
            workzone.find('textarea').height(new_workzone_height - 12);
            workzone.find('.CodeMirror-scroll').height(new_workzone_height);
        };
        $(window).resize(resizeEditor);
        setTimeout(resizeEditor, 1);
    },

    /** One-time set up for elrte editor. */
    elrteOneTimeSetUp: function() {
        if (elRTE.prototype.options.toolbars.mailerToolbar) {
            return false;
        }

        elRTE.prototype.options.lang = this.options.lang;

        // Set up mailer toolbar
        elRTE.prototype.beforeSave = function () {};
        elRTE.prototype.options.panels.wa_mailer = ["wa_mailer_smarty_vars"];
        elRTE.prototype.options.toolbars.mailerToolbar = ['wa_style', 'alignment', 'colors', 'format', 'indent', 'lists', 'wa_image', 'wa_links', 'wa_elements', 'wa_tables', 'direction', 'wa_mailer'];

        // Toolbar button to select smarty variables
        elRTE.prototype.options.buttons.wa_mailer_smarty_vars = ' ';
        elRTE.prototype.ui.prototype.buttons.wa_mailer_smarty_vars = function(rte, name) {
            this.constructor.prototype.constructor.call(this, rte, name);
            var self = this;
            var popup_div = null;

            // Helper to (re)init the popup div.
            var initPopupDiv = function() {
                if (popup_div) {
                    popup_div.remove();
                }
                var button = rte.toolbar.find('.wa_mailer_smarty_vars');
                popup_div = $('<div id="elrte-wa_mailer_smarty_vars"></div>').hide().appendTo($('body'));
                $('#available-smarty-variables').children().clone().appendTo(popup_div);
                popup_div.on('click', 'a', function() {
                    var div = $(this).closest('.one-var');
                    self.rte.history.add();
                    var code = div.find('.var-code').text();
                    var node;
                    if (code.indexOf('<') >= 0) {
                        var nodes = $.parseHTML(code);
                        if (nodes.length == 1) {
                            node = nodes[0];
                        } else {
                            node = document.createDocumentFragment();
                            $.each(nodes, function(i, n) {
                                node.appendChild(n);
                            });
                        }
                    } else {
                        node = document.createTextNode(code);
                    }
                    self.rte.selection.insertNode(node);
                    self.rte.ui.update();
                });
            };

            var hidePopupDiv = function() {
                popup_div.slideUp();
            };

            var showPopupDiv = function() {
                var button = rte.toolbar.find('.wa_mailer_smarty_vars');
                popup_div.css({
                    top: button.offset().top + button.height() + 2,
                    left: button.offset().left
                }).slideDown();
                $(document).one('click', function() {
                    hidePopupDiv();
                });
            };

            // Executed when user clicks the toolbar button.
            this.command = function() {
                if(!popup_div) {
                    initPopupDiv();
                }
                if (popup_div.is(':visible')) {
                    hidePopupDiv();
                } else {
                    showPopupDiv();
                }
            };

            this.update = function() {
                this.domElem.removeClass('disabled');
            };

            this.domElem.append('<span class="img"></span>').append($('<span class="text"></span>').text($_('Insert variable')));
        };

        // Workaround for elrte bug. It used to add 1px borders to all tables.
        elRTE.prototype.filter.prototype.replaceAttrs.border = function(a) {
            if (a.style['border']) {
                return;
            }
            !a.style['border-width'] && (a.style['border-width'] = (parseInt(a.border)||0)+'px');
            !a.style['border-style'] && (a.style['border-style'] = 'solid');
            delete a.border;
        };

        // Workaround for elrte bug. It used to remove all background images.
        var old_utils = elRTE.prototype.utils;
        elRTE.prototype.utils = function(rte) {
            old_utils.call(this, rte);
            var old_compactStyle = this.compactStyle;
            this.compactStyle = function(s) {
                if (s['background-image']) {
                    if (s['background-color']) {
                        s['background-color'] = this.color2Hex(s['background-color']);
                    }
                    s.background = (s['background-color']?(s['background-color']+' '):'')+s['background-image']+' '+(s['background-position']||'0 0')+' '+(s['background-repeat']||'repeat');
                    delete s['background-image'];
                    delete s['background-image'];
                    delete s['background-position'];
                    delete s['background-repeat'];
                }
                return old_compactStyle.call(this, s);
            };
        };

        // Workaround to keep table centering via <td align="center">
        var old_align = elRTE.prototype.filter.prototype.replaceAttrs.align;
        elRTE.prototype.filter.prototype.replaceAttrs.align = function(a, n) {
            if (n == 'td') {
                //a.style['text-align'] = a.align;
                return;
            }
            old_align.call(this, a, n);
        };

        return true;
    },

    /** Initialize eLRTE in template editor page and in step 1 page. */
    initWYSIWYG: function(textarea, message_id) {
        $.wa.mailer.elrteOneTimeSetUp();

        var elrte = textarea.elrte({
             height: '500',
             toolbar: 'mailerToolbar',
             wa_image_upload: '?module=files&action=uploadimage'+(message_id ? '&message_id='+message_id : ''),
             width: "100%"
        })[0].elrte;

        // Make elRTE keep <, >, & and " in smarty tags
        elrte.filter._chains.source.push(function(html) {
            html = html.replace(/%7B\$wa_url%7D/, '{$wa_url}');
            html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
                var i = full.indexOf("</script", offset + match.length);
                var j = full.indexOf('<script', offset + match.length);
                if (i == -1 || (j != -1 && j < i)) {
                    match = match.replace(/&gt;/g, '>');
                    match = match.replace(/&lt;/g, '<');
                    match = match.replace(/&amp;/g, '&');
                    match = match.replace(/&quot;/g, '"');
                }
                return match;
            });
            return html;
        });

        // Close all dialogs when user hits Escape
        $(elrte.iframe).contents().keyup(function(e) {
            if (e.keyCode == 27) {
                $(".dialog:visible").trigger('esc');
            }
        });

        // PLain text field
        elrte.plain_text = $(
            '<div class="plain-text-preview"><textarea class="padded preview" disabled></textarea></div>'
        ).appendTo(elrte.workzone).hide();

        // Codemirror (syntax-highlighted HTML editor)
        var codemirror = CodeMirror(
            elrte.workzone[0], {
                mode: "text/html",
                tabMode: "indent",
                height: "dynamic",
                lineWrapping: true
            }
        );
        $(codemirror.getWrapperElement()).hide();

        // debugging helpers
        this.last_elrte = elrte;
        this.last_codemirror = codemirror;

        // Helper to replace 'save' button with a hint text when switched to plain-text tab
        var buttons = $('#editor-step-1-buttons');
        buttons.button_replacement = $(
            '<span class="button-replacement italic">'+
                $_('This plain-text version of your message is automatically created from HTML version and displayed if recipients have disabled HTML view in their email programs.')+
            '</span>'
        ).appendTo(buttons).hide();
        buttons.hide = function() {
            this.children().hide();
            this.button_replacement.show();
        };
        buttons.show = function() {
            this.children().show();
            this.button_replacement.hide();
        };

        textarea.parents('.m-editor').find('.mode-toggle > li > a').click(function() {
            var li = $(this).parent();
            if (li.hasClass('selected')) {
                return false;
            }

            // Hide previous tab
            var prev_selected = li.siblings('.selected').removeClass('selected');
            if (prev_selected.hasClass('wysiwyg')) {
                elrte.updateSource();
                elrte.source.val($.wa.mailer.style_html(elrte.source.val()));
                $(elrte.iframe).hide();
                elrte.toolbar.hide();
                elrte.ui.disable();
                elrte.statusbar.empty();
            } else if (prev_selected.hasClass('plain-text')) {
                elrte.plain_text.hide();
                buttons.show();
            } else {
                $(codemirror.getWrapperElement()).hide();
                textarea.val(codemirror.getValue());
            }

            // Show new tab
            li.addClass('selected');
            if (li.hasClass('wysiwyg')) {
                // switched to WYSIWYG tab
                $(elrte.iframe).show();
                elrte.updateEditor();
                elrte.window.focus();
                elrte.ui.update(true);
                elrte.toolbar.show();
            } else if (li.hasClass('plain-text')) {
                // switched to plain-text tab
                elrte.plain_text.show().find('textarea').hide();
                buttons.hide();

                // Fetch plain-text version from server
                $('<i class="icon16 loading after-button"></i>').appendTo(elrte.plain_text);
                $.post('?module=campaigns&action=getPlainText', { html: elrte.source.val() }, function(r) {
                    elrte.plain_text.find('textarea').val(r.data).show().siblings('.loading').remove();
                }, 'json');
            } else {
                // switched to HTML source tab
                codemirror.setValue(elrte.source.val());
                $(codemirror.getWrapperElement()).show();
                codemirror.refresh();
            }

            $(window).resize();

            return false;
        });


        return [elrte, codemirror];
    },

    style_html: function(v) {
        if (typeof style_html === 'function') {
            return style_html(v, {
                max_char: 0
            });
        }
        return v;
    },

    /** Animates campaign report progressbar and countdown. */
    updateCampaignReportProgress: function(campaign_id, percent_complete_precise, campaign_estimated_finish_timestamp, campaign_send_datetime, php_time, campaign_paused) {

        var start_ts = (new Date()).getTime();
        $.wa.mailer.random = start_ts;

        // Delay between page reloads in milliseconds and seconds
        var RELOAD_TIME = 5000;
        var RELOAD_TIME_SEC = RELOAD_TIME/1000;

        // Set up progressbar
        var previous_time = $.storage.get('mailer/campaign/'+campaign_id+'/time');
        var previous_value = $.storage.get('mailer/campaign/'+campaign_id+'/value');
        var current_time = php_time;
        var current_value = percent_complete_precise;
        if (!previous_time) {
            previous_time = campaign_send_datetime;
            previous_value = 0;
        }
        var set_time, set_value;
        if (current_time - previous_time < RELOAD_TIME_SEC) {
            set_time = current_time;
            set_value = previous_value;
        } else {
            set_time = current_time - RELOAD_TIME_SEC;
            set_value = previous_value + (current_value - previous_value) * (current_time - previous_time - RELOAD_TIME_SEC) / (current_time - previous_time);
        }

        // When campaign is paused, everything is simple.
        if (campaign_paused) {
            $('#progressbar-text').text(''+Math.round(current_value)+'%');
            $('#progressbar-status').css('width', ''+Math.round(current_value)+'%');
            $.storage.set('mailer/campaign/'+campaign_id+'/value', current_value);
            $('#campaign-sending-time-left').parent().hide();
            return;
        }

        // Make sure progress-bar always moves (even if in reality there's no visible progress yet)
        if (current_value <= set_value) {
            if (set_value > previous_value) {
                // This is safe: user didn't see anything greater than previous_value yet
                set_value = Math.max(set_value - 1, previous_value);
            } else {
                // This is a cheat: real progress is less than what we'll show.
                // Although after about 50% this makes current_value = set_value
                current_value = set_value + Math.max(0, 0.4 - Math.log(set_value + 2)/10)*2;
            }
        }

        // Animate progressbar
        var progressbar_text = $('#progressbar-text').text(''+Math.round(set_value)+'%');
        $('#progressbar-status').width(''+set_value+'%').animate({
            width: ''+current_value+'%'
        }, {
            easing: 'linear',
            duration: RELOAD_TIME * 1.1,
            step: function(value) {
                var current_value = progressbar_text.text();
                $.storage.set('mailer/campaign/'+campaign_id+'/value', value);
                if (current_value && current_value.replace(/[^0-9]/g, '') - 0 < value) {
                    if (!value || value <= 0.1) {
                        value = '≈0';
                    } else if (value <= 1) {
                        value = '<1';
                    } else {
                        value = Math.round(value);
                    }
                    progressbar_text.text(''+value+'%');
                }
            }
        });

        // Helper to pad string with zeros
        var strPad = function(i,l,s) {
            var o = i.toString();
            if (!s) { s = '0'; }
            while (o.length < l) {
                o = s + o;
            }
            return o;
        };

        // Helper to format time in [hh:]mm:ss format.
        var formatTime = function(fullseconds) {
            if(fullseconds < 60) {
                return $_('%ds').replace('%d', fullseconds);
            } else if (fullseconds < 60 * 60) {
                var seconds = fullseconds % 60;
                var minutes = Math.floor(fullseconds/60);
                return $_('%dm').replace('%d', minutes) + ' ' + $_('%ds').replace('%d', strPad(seconds, 2));
            } else {
                var seconds = fullseconds % 60;
                var minutes = Math.floor(fullseconds/60) % 60;
                var hours = Math.floor(fullseconds/(60*60));
                return $_('%dh').replace('%d', hours) + ' ' + $_('%dm').replace('%d', strPad(minutes, 2)) + ' ' + $_('%ds').replace('%d', strPad(seconds, 2));
            }
        };

        // Helper to update total campaign duration and time left
        var updateDuration = function() {
            if (campaign_estimated_finish_timestamp) {
                var current_time_ms = (new Date()).getTime();
                var seconds_left = Math.max(0, campaign_estimated_finish_timestamp - current_time_ms / 1000);

                var old = $.wa.mailer.campaign_countdown;
                if (old && old.campaign_id == campaign_id) {
                    // When old estimate differs from reality too much (more than 20%), then reset the estimate
                    if (old.seconds_left > 10 && ((old.seconds_left * 1.2 < seconds_left) || (seconds_left * 1.2 < old.seconds_left))) {
                        $('#campaign-sending-time-left').html('<i class="icon16 loading"></i>');
                        $.wa.mailer.campaign_countdown = null;
                        return;
                    }
                    // Otherwise, decrement old predition by 1 "fake second" whose length depends on difference
                    // between old and new estimate. This makes the timer run smoothly.
                    else if (old.seconds_left > 0 && current_time_ms - old.current_time_ms < 10000) {
                        // How much real time (in seconds) passed since `old` update
                        var time_diff = Math.min(seconds_left, (current_time_ms - old.current_time_ms) / 1000);
                        // What would the old estimate be if we simply decrement it by real time_diff
                        var bad_estimate = old.seconds_left - time_diff;
                        // New estimate is calculated from previous one (which makes the timer run smoothly)
                        // with a small addition depending on how far our old estimate is from new, updated estimate.
                        seconds_left = bad_estimate + (seconds_left - bad_estimate) * time_diff / old.seconds_left;
                        // Make sure the estimate never goes beyond zero and never increases.
                        seconds_left = Math.max(0, Math.min(seconds_left, old.seconds_left));
                    }
                }
                $.wa.mailer.campaign_countdown = {
                    campaign_id: campaign_id,
                    current_time_ms: current_time_ms,
                    seconds_left: seconds_left
                };
                $('#campaign-sending-time-left').html(formatTime(Math.floor(seconds_left))).parent().show();
            }
        };
        updateDuration();

        // Updates duration counters and progressbar last update time in localStorage
        var interval = setInterval(function() {
            if ($.wa.mailer.random != start_ts) {
                window.clearInterval(interval);
                return;
            }
            var time_passed = (new Date()).getTime() - start_ts;
            var time;
            if (time_passed >= RELOAD_TIME * 1.1) {
                window.clearInterval(interval);
                interval = null;
                time = current_time;
            } else {
                time = set_time + (current_time - set_time)*time_passed/(RELOAD_TIME*1.1);
            }
            $.storage.set('mailer/campaign/'+campaign_id+'/time', time);
            updateDuration();
        }, 250);

        // Reload once every several seconds to keep numbers and progressbar fresh
        var reloadInterval = window.setInterval(function() {
            if ($.wa.mailer.random != start_ts) {
                window.clearInterval(reloadInterval);
                return;
            }
            $.get('?module=campaigns&action=reportUpdate&campaign_id='+campaign_id, function(html) {
                if ($.wa.mailer.random != start_ts) {
                    window.clearInterval(reloadInterval);
                    return;
                }
                $('#update-container').html(html);
            });
        }, RELOAD_TIME);
    },

    /**
      * Remove from localStorage any data that no longer needed for archived campaign,
      * and remove progressbar from report page.
      */
    cleanupSentCampaign: function(campaign_id) {
        $.storage.del('mailer/campaign/'+campaign_id+'/time');
        $.storage.del('mailer/campaign/'+campaign_id+'/value');

        var progressbar_text = $('#progressbar-text');
        if ($('#report-wrapper .progressbar').length > 0) {
            $('#progressbar-status').animate({
                width: '100%'
            }, {
                easing: 'linear',
                duration: 3000,
                complete: function() {
                    $.wa.mailer.redispatch();
                },
                step: function(value) {
                    if (!value || value <= 0.1) {
                        value = '≈0';
                    } else if (value <= 1) {
                        value = '<1';
                    } else {
                        value = Math.round(value);
                    }
                    progressbar_text.text(''+value+'%');
                }
            });
        }
    },

    /** Draws pie chart for campaign report in #pie-graph. */
    drawReportPie: function(app_static_url, stats) {
        var r = Raphael($('#pie-graph').empty()[0]);
        var pie = r.piechart(
            120, 120, 100, // center_x, center_y, radius
            stats, {
                colors: [
                    'green',
                    'url('+app_static_url+'img/strokegreen.png)',
                    'red',
                    'url('+app_static_url+'img/strokered.png)',
                    '#ccc',
                    'white'
                ],
                no_sort: true,
                minPercent: -1,
                matchColors: true
            }
        );
        $.wa.mailer.pie = pie; // debugging helper

        pie.hover(function () {
            this.sector.stop().transform('S'+[1.1, 1.1, this.cx, this.cy].join(','));

            var dot = $('#pie-legend .legend-dot')[this.value.order];
            if (!dot) {
                return;
            }
            var dot = $(dot);
            var large_dot = $('<b class="large legend-dot"></b>').appendTo(dot);
            large_dot.css({
                'background-color': dot.css('background-color'),
                'background-image': dot.css('background-image')
            });
        }, function () {
            this.sector.animate({ transform: 's1 1 ' + this.cx + ' ' + this.cy }, 500, "bounce");
            $('#pie-legend .legend-dot .legend-dot').remove();
        });
    },

    /** Helper to add submit handler. Disables submit button until response is received. */
    onSubmit: function (form, callback) {
        form.submit(function () {
            var $f = $(this);
            var submits = $f.find("input[type=submit]:enabled").attr('disabled', 'disabled');
            $.post($f.attr('action'), $f.serialize(), function(data, textStatus, jqXHR) {
                    submits.attr('disabled', false);
                    callback.call(this, data, textStatus, jqXHR);
            }, "json");
            return false;
        });
    },

    /** Load HTML content from url and put it into main #content div */
    load: function (url, callback) {
        this.showLoading();
        var r = Math.random();
        $.wa.mailer.random = r;
        $.get(url, function(result) {
            if ($.wa.mailer.random != r) {
                // too late: user clicked something else.
                return;
            }
            $("#content").addClass('shadowed').addClass('blank').html(result);
            $.wa.mailer.hideLoading();
            $('html, body').animate({scrollTop:0}, 200);
            if (callback) {
                callback.call(this);
            }
        });
    },

    /** Show loading indicator in the header */
    showLoading: function() {
        var h1 = $('h1:visible').first();
        if(h1.size() <= 0) {
            $('#c-core-content .block').first().prepend('<i class="icon16 loading"></i>');
            return;
        }
        if (h1.find('.loading').show().size() > 0) {
            return;
        }
        h1.append('<i class="icon16 loading"></i>');
    },

    /** Hide all loading indicators in h1 headers */
    hideLoading: function() {
        $('h1 .loading').hide();
    },

    /** Add .selected css class to li with <a> whose href attribute matches current hash.
      * If no such <a> found, then the first partial match is highlighted.
      * Hashes are compared after this.cleanHash() applied to them. */
    highlightSidebar: function(sidebar) {
        var currentHash = this.cleanHash(location.hash);
        var partialMatch = false;
        var partialMatchLength = 2;
        var match = false;

        if (!sidebar) {
            sidebar = $('#wa-app > .sidebar');
        }

        sidebar.find('li a').each(function(k, v) {
            v = $(v);
            if (!v.attr('href')) {
                return;
            }
            var h = $.wa.mailer.cleanHash(v.attr('href'));

            // Perfect match?
            if (h == currentHash) {
                match = v;
                return false;
            }

            // Partial match? (e.g. for urls that differ in paging only)
            if (h.length > partialMatchLength && currentHash.substr(0, h.length) === h) {
                partialMatch = v;
                partialMatchLength = h.length;
            }
        });

        if (!match && partialMatch) {
            match = partialMatch;
        }

        if (match) {
            sidebar.find('.selected').removeClass('selected');

            // Only highlight items that are outside of dropdown menus
            if (match.parents('ul.dropdown').size() <= 0) {
                var p = match.parent();
                while(p.size() > 0 && p[0].tagName.toLowerCase() != 'li') {
                    p = p.parent();
                }
                if (p.size() > 0) {
                    p.addClass('selected');
                }
            }
        }
    },

    /** Collapse sections in sidebar according to status previously set in $.storage */
    restoreCollapsibleStatusInSidebar: function() {
        // collapsibles
        $("#wa-app .collapse-handler").each(function(i,el) {
            $.wa.mailer.collapseSidebarSection(el, 'restore');
        });
    },

    /** Collapse/uncollapse section in sidebar. */
    collapseSidebarSection: function(el, action) {
        if (!action) {
            action = 'coollapse';
        }
        el = $(el);
        if(el.size() <= 0) {
            return;
        }

        var arr = el.find('.darr, .rarr');
        if (arr.size() <= 0) {
            arr = $('<i class="icon16 darr">');
            el.prepend(arr);
        }
        var newStatus;
        var id = el.attr('id');
        var oldStatus = arr.hasClass('darr') ? 'shown' : 'hidden';

        var hide = function() {
            el.nextAll('.collapsible, .collapsible1').first().hide();
            arr.removeClass('darr').addClass('rarr');
            newStatus = 'hidden';
        };
        var show = function() {
            el.nextAll('.collapsible, .collapsible1').first().show();
            arr.removeClass('rarr').addClass('darr');
            newStatus = 'shown';
        };

        switch(action) {
            case 'toggle':
                if (oldStatus == 'shown') {
                    hide();
                } else {
                    show();
                }
                break;
            case 'restore':
                if (id) {
                    var status = $.storage.get('mailer/collapsible/'+id);
                    if (status == 'hidden') {
                        hide();
                    } else {
                        show();
                    }
                }
                break;
            case 'uncollapse':
                show();
                break;
            //case 'collapse':
            default:
                hide();
                break;
        }

        // save status in persistent storage
        if (id && newStatus) {
            $.storage.set('mailer/collapsible/'+id, newStatus);
        }
    },

    /**
     * Helper to make elements (usually table td's) clickable by ctearing <a> elements that wrap their content.
     * @param elements jQuery collection
     * @param getLink callback(el) to get href attribute
     */
    makeClickable: function(elements, getLink) {
        elements.each(function() {
            var el = $(this);
            var a = $('<a href="'+getLink(el)+'" style="color:inherit !important"></a>').html(el.html());
            el.empty().append(a);
        });
    },

    /** Current hash. No URI decoding is performed. */
    getHash: function () {
        return this.cleanHash();
    },

    /** Make sure hash has a # in the begining and exactly one / at the end.
      * For empty hashes (including #, #/, #// etc.) return an empty string.
      * Otherwise, return the cleaned hash.
      * When hash is not specified, current hash is used. No URI decoding is performed. */
    cleanHash: function (hash) {
        if(typeof hash == 'undefined') {
            // cross-browser way to get current hash as is, with no URI decoding
            hash = window.location.toString().split('#')[1] || '';
        }

        if (!hash) {
            return '';
        } else if (!hash.length) {
            hash = ''+hash;
        }
        while (hash.length > 0 && hash[hash.length-1] === '/') {
            hash = hash.substr(0, hash.length-1);
        }
        hash += '/';

        if (hash[0] != '#') {
            if (hash[0] != '/') {
                hash = '/' + hash;
            }
            hash = '#' + hash;
        } else if (hash[1] && hash[1] != '/') {
            hash = '#/' + hash.substr(1);
        }

        if(hash == '#/') {
            return '';
        }

        return hash;
    }

}; // end of $.wa.mailer

})(jQuery);