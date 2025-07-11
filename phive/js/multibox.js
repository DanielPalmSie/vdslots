(function ($) {
    var multibox_vars = {};


    var multibox_methods = {
        toggleOverflow: function (show) {
            if (typeof show === 'undefined') {
                show = true
            }
            if (show) {
                $('html').css({height: '100vh', overflow: 'hidden'});
                $('body').addClass("modal-open-adjustment");
            } else {
                $('html').css({height: 'auto', overflow: 'auto'});
                $('body').removeClass("modal-open-adjustment");
            }
        },
        show: function (id) {
            if (typeof multibox_vars[id] !== 'undefined' && multibox_vars[id].els) {
                multibox_vars[id].els.wrap.fadeIn(200);
                multibox_methods.toggleOverflow(true);
            }
        },
        hide: function (id) {
            if (typeof multibox_vars[id] !== 'undefined' && multibox_vars[id].els) {
                multibox_vars[id].els.wrap.fadeOut(0);
                multibox_methods.toggleOverflow(false);
            }
        },

        init: function (options) {
            window.top.popup_show_in_progress = options.id;
      multibox_vars[options.id] = {boxType: empty(options.boxType) ? 'default' : options.boxType};

      if(options.replaceSame && $("#"+options.id).length > 0)
        $.multibox('close', options.id);
      else if($("#"+options.id).length > 0)
        return;

      multibox_vars[options.id].options = options;

      var cls = typeof(options.cls) == 'undefined' ? 'multibox' : options.cls;
      var zOffset = 4;
      var baseZ = (options.baseZIndex ?? 2000) + (Object.size(multibox_vars) * zOffset);

            var topMobileLogos = "";
            if (options.topMobileLogos) {
                topMobileLogos = "has-sticky-bar";
            }
            var containerClass = options.containerClass ? options.containerClass : ""
            wrapDiv = $('<div id="' + options.id + '" class="' + cls + '-wrap ' + topMobileLogos + ' ' + containerClass + '"></div>');
            $('body').append(wrapDiv);

            outerDiv = $('<div class="' + cls + '-outer"></div>');
            if (!empty(options.bkg))
                outerDiv.css({"background-image": 'url(' + options.bkg + ')'});

            wrapDiv.append(outerDiv);

            outerDiv.append(
                contentDiv = $('<div class="' + cls + '-content"></div>'),
                closeDiv = $('<a class="' + cls + '-close"><span class="icon icon-vs-close"></span></a>'),
                titleDiv = $('<div class="' + cls + '-title"></div>')
            );
            if (typeof options.hideOverlay == 'undefined') {
                $('body').append(overlay = $('<div id="multibox-overlay-' + options.id + '" class="multibox-overlay"></div>'));
                overlay.css({
                    'z-index': baseZ,
                    height: $('body').height(),
                    width: $('body').width(),
                    opacity: typeof options.overlayOpacity == 'undefined' ? 0.8 : options.overlayOpacity
                });
                overlay.hide().fadeIn(200);
            }

            switch (options.type) {
                case 'html' :
                    contentDiv.html(options.content);
                    delete window.top.popup_show_in_progress;
                    break;
                case 'ajax' :
                case 'get' :
                    if(typeof(options.params) == 'undefined')
                    options.params = {};
                    var callback_fn = function(ret){
                    contentDiv.html(ret);
                    $.multibox('posMiddle', options.id);
                    if(typeof(options.callb) != 'undefined'){
                        options.callb.call(this, ret);
                    }

                    /*
                    images are taking time to render in browser, after they gets rendered modal height/width got changed.
                    so we should trigger posMiddle once image gets rendered
                    */
                    waitForImagesToLoad(options.id).done(function() {
                        $.multibox('posMiddle', options.id);
                    });

                    delete window.top.popup_show_in_progress;
                    };

                    if (options.type === 'ajax') {
                        $.post(options.url, options.params, callback_fn);
                    } else {
                        $.get(options.url, options.params, callback_fn);
                    }
                    break;
          case 'iframe' :
                    var iframeId = "mbox-iframe-" + options.id;
                    var optionsAttribute = options.attribute || '';
                    // below we set use of iframe `scrolling` attribute
                    // it's a legacy, obsolete thing!, see https://caniuse.com/?search=iframe%20scrolling
                    // but we still have to use it by default until we refactor all popups with iframes
                    // actually we are removing this attribute and related logic for:
                    // -  `registration-box` (`phive/js/mg_casino.js`, showRegistrationBox())
                    var useIframeScrollingAttr = (options.useIframeScrollingAttr != null) ? options.useIframeScrollingAttr : true;
                    var iframeScrollingTag =  'scrolling="no"';

                    contentDiv.append(iframeTag = $('<iframe id="' + iframeId + '" class="' + cls + '-frame" src="' + options.url +
                        '" name="' + options.name + '" ' + iframeScrollingTag + ' hspace="0" border="0" frameborder="0" ' + optionsAttribute + '></iframe>'));

                    contentDiv.css({padding: '0px', background: "#000"});

                    if(typeof options.callb === "function"){
                        $("#mbox-iframe-" + options.id).bind("load", function(){
                        options.callb();
                        });
                    }
                    delete window.top.popup_show_in_progress;
                    break;
            }

            multibox_vars[options.id]['els'] = {
                'iframe': iframeTag,
                'wrap': wrapDiv,
                'content': contentDiv
            }

            wrapDiv.css({"z-index": baseZ + 1, display: 'block'});
            contentDiv.show().css({"z-index": baseZ + 2});

            if (isIos()) {
                contentDiv.addClass("is-ios");
            }
            if (isAndroid()) {
                contentDiv.addClass("is-android");
            }

            if (options.showClose)
                closeDiv.show().css({"z-index": baseZ + 3}).click(function () {
                    $.multibox('close', options.id);
                });

            $.multibox('setDim', 'height', options);
            $.multibox('setDim', 'width', options);

            if (typeof (options.offset) != 'undefined') {
                wrapDiv.css(options.offset);
            } else {
                $.multibox('posMiddle', wrapDiv);
                $(window).resize(function () {
                    $.multibox('posMiddle', options.id);
                });
            }

            if (typeof options.iframeCss == 'undefined')
                options.iframeCss = {overflow: 'hidden'};

            var iframeTag = contentDiv.find('iframe');

            iframeTag.css(options.iframeCss);

            wrapDiv.hide().fadeIn(200, function () {
                if (typeof options.onComplete != 'undefined') {
                    options.onComplete.call();
                }
            });

            if (multibox_vars[options.id].boxType != 'loader') {
                multibox_methods.toggleOverflow(true)
                deviceExec('onPoupInit');
            }

            if (!isMobile() && wrapDiv.height() >= window.innerHeight) { // when the pop up doesn't fit the screen
                var height = getResizedHeight(wrapDiv.height());
                if (options.type == "html" && options.id != "mbox-loader") { // for loader we don't need to add scroll
                    wrapDiv.css({"max-height": height, "overflow-y": "scroll"});
                    var scrollBarWidth = wrapDiv[0].offsetWidth - wrapDiv[0].clientWidth; // get scrollbar width. we calculate it after scroll appears.
                    scrollBarWidth = scrollBarWidth < 10 ? 10 : scrollBarWidth; // for privacy dashboard pop up scroll we need 10px.
                    wrapDiv.css({"width": wrapDiv.width() + scrollBarWidth})
                } else if (options.type == "iframe") {
                    wrapDiv.css({"height": height});
                    if (useIframeScrollingAttr) {
                        iframeTag[0].scrolling = "yes";
                        iframeTag.css({"height": "103%"}); // for hiding the scroll for now we use this way.
                        iframeTag.css({"width": "103%"});
                    }
                }
            }

            multibox_vars[options.id].canClose = true;
            if (options.type === "iframe") {
                multibox_vars[options.id].canClose = false;
                iframeTag.on('load', function () {
                    multibox_vars[options.id].canClose = true;
                    // append a CSS class to the iframe `html` and `body` for further contextual CSS styling
                    var iframeContent = document.getElementById(iframeId).contentDocument;
                    if (iframeContent) {
                        iframeContent.querySelector('html').classList.add(options.id + '-inside-iframe');
                        iframeContent.body.classList.add(options.id + '-inside-iframe');
                    }

                    if (useIframeScrollingAttr === false) {
                        $.multibox('setIframeScrollable', options, iframeId);
                        // we need the scrolling attribute only before the iframe loads
                        // because then scrollbars are hidden using injected CSS class
                        iframeTag.removeAttr('scrolling');
                    }
                });
            }

            if(options.type === "ajax") {
                $(document).ready(function(){
                    if (options.enableScrollbar === true) {
                        $.multibox('setScrollable', options);
                    }
                });
            }

            return multibox_methods;
        },
        /*
        setGlobalStyle: function(cssObj){
        wrapDiv.css(cssObj);
        contentDiv.css(cssObj);
        contentDiv.find('iframe').css(cssObj);
      },
        */
        setDim: function (dim, options) {
            var cssObj = {};
            if (isMobile() && (dim === 'width')) {
                cssObj[dim] = typeof (options[dim]) != 'undefined' ? options[dim] : "100%";
            } else {
                cssObj[dim] = typeof (options[dim]) != 'undefined' ? options[dim] : 'auto';
            }
            //alert(dim+' '+cssObj[dim]);
            if (typeof multibox_vars[options.id].els == 'undefined')
                return;
            wrapDiv = multibox_vars[options.id].els.wrap;
            wrapDiv.css(cssObj);
            if (isMobile() && (dim === 'height')) {
                cssObj[dim] = '100vh';
            } else {
                cssObj[dim] = '100%';
            }
            contentDiv = multibox_vars[options.id].els.content;
            contentDiv.css(cssObj);
            contentDiv.find('iframe').css(cssObj);
        },
        posMiddle: function (wrapDivOrId) {
            //center is defined in utility.js
            if (typeof wrapDivOrId == 'string' && !empty(multibox_vars[wrapDivOrId]) && !empty(multibox_vars[wrapDivOrId].els)) {
                wrapDiv = multibox_vars[wrapDivOrId].els.wrap;
            } else
                wrapDiv = wrapDivOrId;

            if (typeof wrapDiv == 'undefined' || wrapDiv.length == 0 || typeof wrapDiv == 'string')
                return;

            if (!isMobile()) {
                wrapDiv.center(false, false);
            } else {
                wrapDiv.css({
                    "top": "0px",
                    "left": "0px"
                });
            }

        },
        noScroll: function (id) {
            if (typeof (multibox_vars[id]) == 'undefined')
                return;
            wrapDiv = multibox_vars[id].els.wrap;
            wrapDiv.css({"overflow": "hidden"});
        },
        resize: function (id, width, height, resizeIframe, hasScroll, preventForceScreenHeight) {
            if (typeof (multibox_vars[id]) == 'undefined') {
                return;
            }

            wrapDiv = multibox_vars[id].els.wrap;
            if (width) {
                wrapDiv.width(width);
            }

            if (height) {
                if (!isMobile() && !preventForceScreenHeight) {
                    height = getResizedHeight(height);
                }
                if (multibox_vars[id].options.animateResize) {
                    wrapDiv.animate({height: height}, 100);
                } else {
                    wrapDiv.height(height);
                }
            }

            if (!empty(resizeIframe) && !empty(multibox_vars[id].els.iframe)) {
                var iframe = multibox_vars[id].els.iframe;
                iframe[0].scrolling = hasScroll ? "yes" : "no";
                if (width) {
                    if (multibox_vars[id].options.animateResize) {
                        iframe.animate({width: width}, 100);
                    } else {
                        iframe.width(width);
                    }
                }

                if (height) {
                    if (multibox_vars[id].options.animateResize) {
                        iframe.animate({height: height}, 100);
                    } else {
                        iframe.height(height);
                    }
                }
            }

            wrapDiv.center(false,false);
        },
        offset: function (id, offsX, offsY) {
            if (typeof (multibox_vars[id]) == 'undefined')
                return;
            wrapDiv = multibox_vars[id].els.wrap;
            var offs = wrapDiv.offset();
            var newX = offs.left;
            var newY = offs.top;
            if (!empty(offsX))
                newX += offsX;
            if (!empty(offsY))
                newY += offsY;
            wrapDiv.css({"left": newX + 'px', "top": newY + 'px'});
        },
        close: function (id, callb) {
            var closeFade = 200;

            if (typeof multibox_vars === 'undefined' || typeof multibox_vars[id] === 'undefined' || multibox_vars[id].canClose === false) {
                return;
            }

            multibox_vars[id].closing = true;

            $("#multibox-overlay-" + id).fadeOut(closeFade, function () {
                $(this).remove();
            });

            var wrapRemove = function () {
                if (multibox_vars[id].options && typeof multibox_vars[id].options.onClose != 'undefined') {
                    multibox_vars[id].options.onClose.call();
                }

                if (multibox_vars[id].boxType != 'loader') {
                    $('html').css({height: 'auto', overflow: 'auto'});
                    $('body').removeClass("modal-open-adjustment");
                    deviceExec('onPoupClose');
                }

                multibox_vars[id] = {};
                $(this).remove();
                if (!empty(callb)) {
                    callb.call();
                }
            };

            if (typeof (id) == 'undefined') {
                $('.multibox-wrap').fadeOut(closeFade, wrapRemove);
            } else {
                $("#" + id).fadeOut(closeFade, wrapRemove);
            }

        },
        remove: function (id) {
            if (typeof multibox_vars == 'undefined')
                return;
            if (typeof multibox_vars[id] == 'undefined')
                return;

            $("#multibox-overlay-" + id).remove();

            if (typeof multibox_vars[id].options != 'undefined' && typeof multibox_vars[id].options.onClose != 'undefined') {
                multibox_vars[id].options.onClose.call();
            }
            multibox_vars[id] = {};

            if (typeof (id) == 'undefined')
                $('.multibox-wrap').remove();
            else
                $("#" + id).remove();

            if (multibox_vars[id].boxType != 'loader') {
                $('html').css({height: 'auto', overflow: 'auto'});
                $('body').removeClass("modal-open-adjustment");
                deviceExec('onPoupRemove');
            }
        },

        // sets iframe scrollability without legacy `scrolling` attribute
        setIframeScrollable: function (options, iframeId) {
            var $wrapperBox = $('#' + options.id);
            var $iframeBox =  $('#' + iframeId);

            if ($wrapperBox.length < 1 || $iframeBox.length < 1)
                return;

            var $observedElement = $wrapperBox[0];

            // make the $iframeBox scrollable on small screens
            $wrapperBox.css({'max-height': '100vh'});

            function _setScrollability ($wrapperBox, $iframeBox) {
                // a total height of elements which can overlap the popup
                // actually set to 35 (a height of games footer)
                var fixedFooterHeight = 0;
                if (parent.$('.games-footer').length > 0) {
                    fixedFooterHeight = (parent.$('.games-footer').outerHeight()) + parseInt($(window).height() - $('.games-footer')[0].getBoundingClientRect().bottom);
                }
                var totalFixedTopAndBottomHeight = 0;
                if ($('#rg-top-bar').length > 0) {
                    totalFixedTopAndBottomHeight = fixedFooterHeight + $('#rg-top-bar')[0].offsetHeight;
                }
                var iframeContent = $iframeBox.contents();
                var $iframeContentBody = iframeContent.find('body');
                var wrapperBoxHeight = $wrapperBox[0].getBoundingClientRect().height;
                var wrapperBoxTop = $wrapperBox[0].getBoundingClientRect().top;
                // `scrollable-iframe` CSS class allows adding of extra bottom paddding to the container
                // so full iframe content is visible when user scrolls the iframe
                if ($iframeContentBody.length > 0) {
                    if((window.innerHeight - totalFixedTopAndBottomHeight) <= (wrapperBoxHeight + wrapperBoxTop)) {
                        $iframeContentBody.addClass('scrollable-iframe');

                        if(options.enableScrollbar) {
                            var visiblePopupHeight = (window.innerHeight - totalFixedTopAndBottomHeight);
                            var headerHeight = iframeContent.find('.registration-header').height();
                            var footerHeight = iframeContent.find('.registration-footer').outerHeight(true) ?? 0;

                            $wrapperBox.css({'max-height': visiblePopupHeight});
                            iframeContent.find('#registration-wrapper').css({'max-height': visiblePopupHeight});
                            $('registration-box').css({maxHeight: (wrapperBoxHeight - headerHeight - footerHeight)});
                            iframeContent.find('.registration-container')
                                .css({'max-height': (visiblePopupHeight - headerHeight - footerHeight)});
                        }
                    } else {
                        $iframeContentBody.removeClass('scrollable-iframe');

                        if(options.enableScrollbar) {
                            $wrapperBox.css({'max-height': ''});
                            iframeContent.find('#registration-wrapper').css({'max-height': ''});
                            $('registration-box').css({maxHeight: ''});
                            iframeContent.find('.registration-container')
                                .css({'max-height': ''});
                        }
                    }
                }
            }

            function setScrollability () {
                _setScrollability($wrapperBox, $iframeBox);
            }

            if (ResizeObserver) {
                var resizeObserver = new ResizeObserver(function (entries, observer) {
                    // if the observed element was removed (popup closed)
                    // remove all size observations
                    if (!$.contains(document.body, $observedElement)) {
                        $(window).off('resize', setScrollability);
                        observer.disconnect();

                        return;
                    }

                    setScrollability();
                });

                resizeObserver.observe($observedElement);

                // observe window size change as it also influences the scrollability
                $(window)
                    .off('resize', setScrollability)
                    .on('resize', setScrollability)
                ;

                return;
            }

            // fallback for browser which aren't supporting ResizeObserver
            // but support MutationObserver
            if (MutationObserver) {
                var options = {
                    attributes: true,
                    attributeFilter: ['style']
                };
                var mutationsCallback = function (mutations, observer) {
                    // if the observed element was removed (popup closed)
                    // remove all size observations
                    if (!$.contains(document.body, $observedElement)) {
                        $(window).off('resize', setScrollability);
                        observer.disconnect();

                        return;
                    }

                    for (var i = 0; i < mutations.length; i++) {
                        var mutation = mutations[i];
                        if (mutation.type === 'attributes') {
                            setScrollability();
                        }
                    }
                };
                var observer = new MutationObserver(mutationsCallback);

                observer.observe($observedElement, options);

                // observe window size change as it also influences the scrollability
                $(window)
                    .off('resize', setScrollability)
                    .on('resize', setScrollability)
                ;

                return;
            }
        },

        // sets lic-mbox-container scrollability
        setScrollable: function (options) {
            var $wrapperBox = $('#' + options.id);

            if ($wrapperBox.length < 1)
                return;

            var $observedElement = $wrapperBox[0];

            // make the $multiBox scrollable on small screens
            $wrapperBox.css({'max-height': '100vh'});

            function _setScrollability ($wrapperBox) {
                // a total height of elements which can overlap the popup
                // actually set to 35 (a height of games footer)
                var fixedFooterHeight =  0;
                if(parent.$('.games-footer').length > 0) {
                    fixedFooterHeight = (parent.$('.games-footer').outerHeight()) + parseInt($(window).height() - $('.games-footer')[0].getBoundingClientRect().bottom);
                }
                var totalFixedTopAndBottomHeight = fixedFooterHeight;
                if($('#rg-top-bar').length > 0) {
                    totalFixedTopAndBottomHeight += $('#rg-top-bar')[0].offsetHeight;
                }

                var $wrapperBoxContent = $wrapperBox.contents();
                var $wrapperBoxContentBody = $wrapperBoxContent.find('.multibox-content');
                var wrapperBoxHeight = $wrapperBox[0].getBoundingClientRect().height;
                var wrapperBoxTop = $wrapperBox[0].getBoundingClientRect().top;

                if ($wrapperBoxContentBody.length > 0) {
                    if((window.innerHeight - totalFixedTopAndBottomHeight) <= (wrapperBoxHeight + wrapperBoxTop)) {
                        if(options.enableScrollbar) {
                            var visiblePopupHeight = (window.innerHeight - totalFixedTopAndBottomHeight);
                            var headerHeight = $wrapperBoxContent.find('.lic-mbox-header').height() ?? 0;

                            $wrapperBox.css({'max-height': visiblePopupHeight});
                            $wrapperBoxContent.find('.lic-mbox-wrapper').css({'max-height': visiblePopupHeight});
                            $wrapperBoxContent.find('.lic-mbox-container')
                                .css({'max-height': (visiblePopupHeight - headerHeight)});
                            $wrapperBoxContent.find('.lic-mbox-container').addClass('scrollable');
                        }
                    } else {
                      if(options.enableScrollbar) {
                            $wrapperBox.css({'max-height': ''});
                            $wrapperBoxContent.find('.lic-mbox-wrapper').css({'max-height': ''});
                            $wrapperBoxContent.find('.lic-mbox-container').css({'max-height': ''});
                            $wrapperBoxContent.find('.lic-mbox-container').removeClass('scrollable');
                        }
                    }
                }
            }

            function setScrollability () {
                _setScrollability($wrapperBox);
            }

            if (ResizeObserver) {
                var resizeObserver = new ResizeObserver(function (entries, observer) {
                    // if the observed element was removed (popup closed)
                    // remove all size observations
                    if (!$.contains(document.body, $observedElement)) {
                        $(window).off('resize', setScrollability);
                        observer.disconnect();

                        return;
                    }


                    const fixedElement = $wrapperBox.find('.lic-mbox-container');
                    if (fixedElement) {
                        // Function to execute after the element is loaded
                        setScrollability();
                        // If you only need to execute this once, disconnect the observer
                        // observer.disconnect();
                        return;
                    }
                });

                resizeObserver.observe($observedElement);

                // observe window size change as it also influences the scrollability
                $(window)
                    .off('resize', setScrollability)
                    .on('resize', setScrollability)
                ;

                return;
            }

            //fallback for browser which aren't supporting ResizeObserver
            // but support MutationObserver
            if (MutationObserver) {
                var options = {
                    attributes: true,
                    attributeFilter: ['style']
                };
                var mutationsCallback = function (mutations, observer) {
                    // if the observed element was removed (popup closed)
                    // remove all size observations
                    if (!$.contains(document.body, $observedElement)) {
                        $(window).off('resize', setScrollability);
                        observer.disconnect();

                        return;
                    }

                    const fixedElement = $wrapperBox.find('.lic-mbox-container');
                    if (fixedElement) {
                        // Function to execute after the element is loaded
                        setScrollability();
                        // If you only need to execute this once, disconnect the observer
                        // observer.disconnect();
                        return;
                    }

                    for (var i = 0; i < mutations.length; i++) {
                        var mutation = mutations[i];
                        if (mutation.type === 'attributes') {
                            setScrollability();
                        }
                    }
                };
                var observer = new MutationObserver(mutationsCallback);

                observer.observe($observedElement, options);

                // observe window size change as it also influences the scrollability
                $(window)
                    .off('resize', setScrollability)
                    .on('resize', setScrollability)
                ;

                return;
            }
        }
    };

    $.multibox = function (method) {
        if (multibox_methods[method])
            return multibox_methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        else if (typeof method === 'object' || !method) {
            return multibox_methods.init.apply(this, arguments);
        } else
            console.error('Method ' + method + ' does not exist on jQuery.multiBox');
    };
})(jQuery);

