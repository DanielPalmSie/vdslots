// according to official docs the range 4000–4999 can be used by application
// source: https://developer.mozilla.org/en-US/docs/Web/API/CloseEvent/code
var WS_PREVENT_RECONNECT = 4001;
var showJurisdictionPopup = false;

// global variables for all brands
var cashierWidth = 0;
var cashierHeight = 0;

// generic values for moving the footer, later overridden by megariches based on `--brand` css variable
var footerMovement = {
  up: {
    dist: 110,
    fdist: -5
  },
  down: {
    dist: -110,
    fdist: -100
  }
}

$(function() {
  // read the brand from CSS variable (--brand) from :root
  var brand = window.getComputedStyle(document.body).getPropertyValue('--brand');

  // variables for newer designs
  var newDesigns = ['megariches', 'dbet'];

  cashierWidth = newDesigns.includes(brand) ? 956 : 1067;
  cashierHeight = newDesigns.includes(brand) ? 608 : 580;

  // for gameplay footer search: update of thumbnails needs higher footer area:
  // https://videoslots.atlassian.net/browse/SITE-3300
  // and this was requested to be reverted by this story:
  // https://videoslots.atlassian.net/browse/SITE-3451
  // changes are not deleted for future use!
  // if (brand === 'megariches') {
  //   footerMovement = {
  //     up: {
  //       dist: 110,
  //       fdist: -5
  //     },
  //     down: {
  //       dist: -110,
  //       fdist: -148
  //     }
  //   }
  // }
});

//sportsbook odds custom fractional values
declareConstantIfUndeclared('ODDS_CUSTOM_FRACTION_VALUE', {
    '1.03': '1/33',
    '1.07': '1/14',
    '1.14': '1/7',
    '1.18': '2/11',
    '1.22': '2/9',
    '1.29': '2/7',
    '1.33': '1/3',
    '1.36': '4/11',
    '1.44': '4/9',
    '1.47': '8/17',
    '1.53': '8/15',
    '1.57': '4/7',
    '1.60': '6/10',
    '1.62': '8/13',
    '1.66': '4/6',
    '1.72': '8/11',
    '1.83': '5/6',
    '1.91': '10/11',
    '1.95': '20/21',
    '2.50': '6/4',
    '4.33': '10/3'
});

// From https://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid
// Should work OK for all browsers since we don't want to rely on crypto being present.
function Uuid() {
    //Timestamp
    var d = new Date().getTime();
    //Time in microseconds since page-load or 0 if unsupported
    var d2 = ((typeof performance !== 'undefined') && performance.now && (performance.now()*1000)) || 0;
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16; //random number between 0 and 16
        if(d > 0){ //Use timestamp until depleted
            r = (d + r)%16 | 0;
            d = Math.floor(d/16);
        } else { //Use microseconds since page-load if supported
            r = (d2 + r)%16 | 0;
            d2 = Math.floor(d2/16);
        }
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}

// From: https://developer.mozilla.org/en-US/docs/Glossary/Base64
function utf8ToB64( str ) {
    return window.btoa(unescape(encodeURIComponent( str )));
}

function b64ToUtf8( str ) {
    return decodeURIComponent(escape(window.atob( str )));
}

function objectifyForm(formId) {
    var formArray = $(formId).serializeArray();
    var returnArray = {};
    for (var i = 0; i < formArray.length; i++){
        returnArray[formArray[i]['name']] = formArray[i]['value'];
    }
    return returnArray;
}

if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, '');
  }
}

var dClicks = {};
function dClick(id){
  if(dClicks[id] === true)
    return true;
  dClicks[id] = true;
}

//Used to stagger WS induced refreshes of elements to make sure that they don't update too often
//which would put pressure on the web server in the form of Ajax calls.
var staggerTimers = {};
function stagger(func, timer, timeout){
  if(staggerTimers[timer] === true)
    return;
  staggerTimers[timer] = true;
  setTimeout(function(){
    func.call();
    staggerTimers[timer] = false;
  }, timeout);
}

function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

//var wsQfs = {};
var wsQIntvs = {};
function wsQf(tag, freq, f){
    //wsQfs[tag] = f;
    wsQIntvs[tag] = setInterval(function(){
        if(typeof wsQd[tag] == 'undefined')
            return;
        //console.log(wsQd);
        var msg = wsQd[tag].shift();
        if(typeof msg != 'undefined')
            f.call(this, msg);
    }, freq);
}

//Queue websocket updates to prevent too many updates in too short a time, it's a CPU vs RAM tradeoff.
var wsQd = {};
function wsQ(tag, msg){
    if(typeof wsQd[tag] == 'undefined')
        wsQd[tag] = [];
    wsQd[tag].push(msg);
}

function reloadIframe(el){
  el.attr('src', function ( i, val ) { return val; });
}

function cleanUpNumber(num){
    num = num.trim();
    var re = /[^0-9]/;
    if(num.match(re)){
        var arr   = num.split(re);
        var cents = parseInt(arr.pop());
        return parseFloat(arr.join('') + '.' + cents);
    }
    return parseFloat(num + '.00');
}

//comment
function isEncHTML(str) {
  if(str.search(/&amp;/g) != -1 || str.search(/&lt;/g) != -1 || str.search(/&gt;/g) != -1)
    return true;
  else
    return false;
};

function decHTMLifEnc(str){
  if(isEncHTML(str))
    return str.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'");
  return str;
}

function encHTML(str){
  if(isEncHTML(str))
    return str;
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g, '&#039;'); //'"
}

function getInt(str){
  return str.replace(/[^\d]/g, "");
}

function getSuffix(str){
  var arr = str.split('-');
  arr.shift();
  return arr.join('-');
}

function getPrefix(str){
  var arr = str.split('-');
  arr.pop();
  return arr.join('-');
}

function llink(url) {
    if (empty(cur_lang) || (cur_lang === default_lang) || url.indexOf('http') === 0) {
        return url;
    }
    return '/' + cur_lang + url;
}

function gotoLang(url){
  var goto = llink(url);
  goTo(goto);
}

function gotoAccUrl(){
    var url = '/account/';
    if(isMobileDevice()){
        url = '/mobile' + url;
    }
    gotoLang(url);
}

function getCashierBaseUrl() {
    return  isMobile() ? '/mobile/cashier/deposit/' : '/cashier/deposit/';
}

function gotoDepositCashier() {
    goTo(getCashierBaseUrl());
}

function isIframe(){
    return window.self !== window.parent;
}

/**
 * This will take care of redirecting via JS to the provided "url", by default the current window (_self) is the target.
 * If a different "target" is specified that will be taken into account.
 * If the "openTop" param is passed then the redirect will apply to the topmost window.
 * The redirect to the top level will be applied by default if the current window and the top windows are different
 * except if "openTop" is passed as false (that will enforce the redirect inside the iframe)
 *
 * @param url - the redirect URL
 * @param target - _blank | _parent | _self | _top | name
 * @param openTop - Boolean
 * @param forceOpenTop
 */
function goTo(url, target, openTop, forceOpenTop) {
    if (forceOpenTop || (openTop === true && isIframe())){
        window.top.location.href = url;
    } else {
        window.open(url, typeof target == 'undefined' ? '_self' : target);
    }
}

/**
 * This function will handle showing an ES user their game summary first before they are redirected away
 * from the game. If the user is not an ES user, then the popup is not shown and only the redirection
 * happens.
 *
 * @param func - A function in string format that we want to execute for the redirection behaviour
 */
function gameCloseRedirection(func){
    var gameRefs = _.map(currentGames, function (game) {
        return game.ext_game_name;
    });

    mgAjax({action: 'close-game-session', game_refs: gameRefs}, function() {
        // only show popup if extSessHandler exists, game is not BOS, and user is from Spain
        if ((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
            extSessHandler.showGameSummary(func);
        }
        else{
            gotoLang('/');
        }
    });
}

/**
 * This function is used to manually trigger the popup shown when an ES player tries to open more than 1 game session
 * simultaneously
 */
function showDuplicateSessionClosePopup(){
    if ((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
        extSessHandler.showClosedByNewSessionPopup();
    }
}

/**
 * Will perform a redirection as window.location.href but will not store on browser history the current url (used to redirect between domains)
 * @param url
 */
function replaceUrl(url, forceOpenTop) {
    if (forceOpenTop) {
        window.top.location.replace(url);
    } else {
        window.location.replace(url);
    }
}

/**
 * @deprecated Use jsReloadWithParams instead
 */
function jsReload(extra) {
    window.location.href = document.URL + (typeof extra == 'undefined' ? '' : extra);
}

function jsReloadWithParams(params) {
    let reloadUrl = document.URL;
    if (typeof params !== 'undefined') {
        reloadUrl += reloadUrl.includes('?') ? '&' : '?';
        reloadUrl += Object.entries(params).map(([key, val]) => `${key}=${val}`).join('&');
    }
    window.location.href = reloadUrl;
}

function parentGoTo(url){
  parent.window.location.href = url;
}

function loadCss(cssf, sel){
  sel = sel == undefined ? 'head' : sel;
  $(sel).append( $('<link rel="stylesheet" type="text/css" />').attr('href', cssf) );
}

function empty(val){

  if(typeof val == 'undefined')
    return true;

  if(val !== val) //NaN
    return true;

  if(val == null || val == undefined || val == '0' || val == 0 || val == 'null')
    return true;

  switch(typeof val){
    case 'string':
      if(val.length != 0)
        return false;
      break;
    case 'number':
      if(val != 0)
        return false;
      break;
    case 'boolean':
      if(val == true)
        return false;
      break;
    case 'object':
      var hasProps = false;
      for(var i in val) {
        hasProps = true;
        break;
      }
      if(hasProps){
        if(val['length'] != 0)
          return false;
      }
      break;
    case 'function':
      return false;
      break;
    default:
      return false;
      break;
  }
  return true;
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function isFunction(functionToCheck) {
    return functionToCheck && {}.toString.call(functionToCheck) === '[object Function]';
}

function preciseRound(num, decimals){
  decimals = typeof decimals == 'undefined' ? 2 : decimals;
  var sign = num >= 0 ? 1 : -1;
  return (Math.round((num*Math.pow(10,decimals))+(sign*0.001)) / Math.pow(10,decimals)).toFixed(decimals); ///
}

function showId(elId, speed){
  $("#"+elId).show(speed);
}

function randInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function S4() {
  return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
}

function uniqid(){
  return S4()+S4()+S4();
}

function jsReloadBase(){
  window.location.href = jsGetBase();
}

function jsGetBase(url){
    url     = empty(url) ? document.URL : url;
    var arr = url.split(/[\?=]/);
    return arr[0];
}

function popupWindow(url, width, height, toolbar, status){
  var toolbar = empty(toolbar) 	? 0 : 1;
  var status 	= empty(status) 	? 0 : 1;
  var width 	= empty(width) 		? 1024 : width;
  var height 	= empty(height) 	? 768 : height;
  return window.open(url,'','scrollbars=1,resizable=1,toolbar='+toolbar+',status='+status+',width='+width+',height='+height);
}

function goToBlank(url){
  return window.open(url, '_blank');
}

function ellipsis(str, len){
  if(str.length < len)
    return str;
  return str.slice(0, len)+'...';
}

function incDim(jObj, options){
  jObj.width( jObj.width() + options.width );
  jObj.height( jObj.height() + options.height );
}

function incContent(jObj, num){
  jObj.html(parseInt(jObj.html()) + num);
}

/*
function moveToOffset(options, wkmsTop, wkmsLeft, otherTop, otherLeft){
  if($.browser.msie || $.browser.webkit){
    options.left += wkmsLeft;
    options.top += wkmsTop;
  }else{
    options.left += otherLeft;
    options.top += otherTop;
  }
  return options;
}
*/

function moveTo(el, target, inc){
  pos = target.offset();
  pos.top += inc.top;
  pos.left += inc.left;
  el.offset(pos);
}

if (!window.vs_ws) {
    window.vs_ws = []
}

function closeVsWS() {
    window.vs_ws.forEach(function(ws) {
        try {
            ws.close()
        } catch (e) {}
    })
}

function closeWs(conn){
  $(window).on('beforeunload', function(){
    conn.close();
  });
}

function doWs(url, func, onClose, onReconnect, onOpen) {
    if (empty(url))
        return;
    var conn = new WebSocket(url);
    conn.onmessage = func;
    closeWs(conn);

    if (typeof onReconnect === 'function') {
        onReconnect(conn)
    }

    if (typeof onOpen === 'function') {
        conn.onopen = function () {
            onOpen(conn)
        }
    }
    conn.onclose = function (event) {
        if (typeof event === 'object' && event.code === WS_PREVENT_RECONNECT) {
            return
        }
        if (onClose)
            onClose.call();
        console.log('ws connection lost');
        setTimeout(function () {
            console.log('reconnecting to ws');
            doWs(url, func, onClose, onReconnect);
        }, randInt(5, 15) * 1000);
    }

    window.vs_ws.push(conn)
    return conn;
}

function confirmJump(url, extra){
  extra = typeof extra == 'undefined' ? '' : extra;
  if(confirm("Are you sure?"))
    window.location.href = url+extra;
}

function buildIds(){
  $("div[id^='pay-']").each(function(){
    trans_ids.push( $(this).attr("id").split("-").pop() );
  });
}

function httpGet(key, qs) {
  qs = empty(qs) ? document.location.search : qs;
  qs = qs.split("+").join(" ");
  var params = {},
      tokens,
      re = /[?&]?([^=]+)=([^&]*)/g;

  while (tokens = re.exec(qs))
    params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);

  return empty(key) ? params : params[key];
}

function doAll(me, id, dataTable, php, phpAction, obj, func){

  if(empty(obj))
    var obj = {};

  me.hide();

  if (empty(obj.ids)) {
    var all_ids = [];

    $("div[id^='" + id + "-']").each(function(){
      all_ids.push($(this).attr("id").split("-").pop());
    });

    obj.ids = $.toJSON(all_ids);
  }

  obj.action = phpAction;

  if(empty(func)){
    func = function(res){
      $(dataTable).html('<img src="/phive/images/ajax-loader.gif"/>');
      if (res == 'fail')
        $("#doAllResult").html("The do all action failed!");
      else {
        $("#doAllResult").html(res);
        $(dataTable).remove();
      }
    }
  }

  $.post(php, obj, func);
}

jQuery.fn.center = function(parent, addScroll) {
  if (typeof addScroll === 'undefined') {
      addScroll = true
  }
  if (parent) {
    parent = this.parent();
  } else {
    parent = window;
  }

  var top, left;

  if (addScroll) {
      top = (($(parent).height() - this.outerHeight()) / 2) + $(parent).scrollTop();
      left = (($(parent).width() - this.outerWidth()) / 2) + $(parent).scrollLeft();
  } else {
      top = (($(parent).height() - this.outerHeight()) / 2);
      left = (($(parent).width() - this.outerWidth()) / 2);
  }

  const element = $('#rg-top-bar')[0];

  if(element && top < element.offsetHeight) {
      // On very small screens, make sure a negative value does not make part of the window fall above the screen
      top = $('#rg-top-bar')[0].offsetHeight;
  }

  if(left < 10) {
      // On very small screens, make sure a negative value does not make part of the window fall left of the screen
      left = 0;
  }

  this.css({
    "top": (top + "px"),
    "left": (left + "px")
  });
  return this;
}

function animateInc(sel, dist, duration, easing, callb){
  if($(sel).length > 0){
    var tmp = $(sel).offset();
    $(sel).animate({"left": tmp.left+dist[0]+'px', "top": tmp.top+dist[1]+'px'}, duration, easing, callb);
  }
}

function resizeInc(sel, dist, attr){
  if($(sel).length > 0){
    if(typeof attr == 'undefined')
      attr = 'width';
    var orig = parseInt($(sel).width());
    $(sel).css(attr, (orig + parseInt(dist)) + 'px');
  }
}

function moveInc(sel, dist){
  if($(sel).length > 0){
    var tmp = $(sel).offset();
    $(sel).css({"left": tmp.left+dist[0]+'px', "top": tmp.top+dist[1]+'px'});
  }
}

function animateIncRel(sel, dist, duration, easing, callb){
  if($(sel).length > 0){
    var tmp = $(sel).position();
    var css = {};
    if(!empty(dist[0]))
      css.left = tmp.left+dist[0]+'px';

    if(!empty(dist[1]))
      css.top = tmp.top+dist[1]+'px';

    $(sel).animate(css, callb, duration, easing);
  }
}

Object.size = function(obj) {
  var size = 0, key;
  for (key in obj) {
    if (obj.hasOwnProperty(key)) size++;
  }
  return size;
};

function getPair(obj, func){
  for(var k in obj){
    if(!obj.hasOwnProperty(k)) continue;
    if(func(k, obj[k]))
      return [k, obj[k]];
  }
  return [];
}

// FIXME load the jquery.cookie lib globally? otherwise if this function is called before it's loaded return a js error (Ex. remove the "isTest()" condition on diamondbet generic.php)
// Create client side cookies (no httpOnly)
function sCookie(name, value)
{
    $.cookie(name, value, {path: '/', domain: cookie_domain, secure: cookie_secure});
}

function sCookieExpiry(name, value, expiry)
{
    $.cookie(name, value, {path: '/', domain: cookie_domain, secure: cookie_secure, expires: expiry});
}

function sCookieDays(name, value, days)
{
    var options = {path: '/', domain: cookie_domain, secure: cookie_secure};
    if (days) {
        options.expires = days;
    }
    $.cookie(name, value, options);
}

function padNum(num, padNum){
  var str = num + '';
  var diff = padNum - str.length;
  if(diff > 0){
    for(i = 0; i < diff; i++)
      str = '0' + str;
  }
  return str;
}

function nfCents(n, noDecimals, div){
    if(typeof div == 'undefined'){
        div = 100;
    }

    n = n / div;

    var res = n.toFixed(2);

    if(n > 1000){
        var res = res.replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    if(noDecimals === true){
        return res.replace(/\.\d\d/g, '');
    }

    return res;
}

function toCents(n){
    return parseFloat( n.replace(',', '') ) * 100;
}

function toTwoDec(n){
    return (toCents(n) / 100).toFixed(2);
}

function hasWs(){
  return (("WebSocket" in window && window.WebSocket != undefined) ||
                     ("MozWebSocket" in window));
}

function addHtml(sel, html){
  $(sel).html($(sel).html() + html);
}

function fmtMoney(amount, fractionDigits){
    if(empty(fractionDigits)){
        fractionDigits = 2;
    }

    return parseFloat(amount).toLocaleString('en-US', { style: 'decimal', maximumFractionDigits: fractionDigits, minimumFractionDigits: fractionDigits });
}

function isIos(){
    return navigator.userAgent.match(/iPhone|iPad|iPod/i);
}

function isAndroid(){
    return (/android/gi).test(navigator.userAgent);
}

function isIosChrome(){
    return navigator.userAgent.match('CriOS');
}

// https://stackoverflow.com/questions/8348139/detect-ios-version-less-than-5-with-javascript
function iOSversion () {
    if (/iP(hone|od|ad)/.test(navigator.platform)) {
        var v = (navigator.appVersion).match(/OS (\d+)_(\d+)_?(\d+)?/);
        return [parseInt(v[1], 10), parseInt(v[2], 10), parseInt(v[3] || 0, 10)];
    }
}

function isOnIpadOs () {
    return (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isIpad() {
    return (/^(iPad)/.test(navigator.platform) || isOnIpadOs());
}

function isIos13orHigher () {
    if (isOnIpadOs()) {
        return true;
    }

    if (iOSversion() == null) {
        return false;
    }

    return iOSversion()[0] >= 13;
}

function getIphoneVersion(){

    // Adapted from https://stackoverflow.com/questions/46192280/detect-if-the-device-is-iphone-x/46316292

    if(!isIos()){
        return false;
    }

    // Get the device pixel ratio
    var ratio = window.devicePixelRatio || 1;

    var map = {
        1136: '5',
        1334: '6-8',
        1920: '6+-8+',
        2208: '6+-8+',
        2436: 'X,XS',
        2688: 'XS Max',
        1792: 'XR'
    };

    var height = window.screen.height * ratio;

    return map[height];
}

function isIphone() {
    return isIos() && !isIpad();
}

function isIphoneX(){
    var version = getIphoneVersion();
    return ['X,XS', 'XS Max', 'XR'].indexOf(version) !== -1;
}

function isMobileDevice(){
    return isIos() || isAndroid();
}

/**
 * Dynamically calls a function based on a path string and parameters.
 * This function parses the path to determine the correct function context and reference,
 * and then calls that function using the `apply` method with specified parameters.
 *
 * @param {Object} obj - The root object from which to start parsing the path, typically `window`.
 * @param {string} methodPath - A dot-separated path string that leads to the function to call (e.g., 'obj.subObj.func').
 * @param {Array} params - An array of parameters to pass to the function being called.
 * @returns {*} - The result of the function call. Returns `null` if the function does not exist.
 *
 * @example
 * // Call a function located at window.licFuncs.showAlert with parameters ["Hello", "world!"]
 * dynamicCall(window, 'licFuncs.showAlert', ["Hello", "world!"]);
 *
 * @example
 * // Call a global function named showAlert directly on window with one parameter
 * dynamicCall(window, 'showAlert', ["Welcome to dynamic calling!"]);
 */
function dynamicCall(obj, methodPath, params) {
    const parts = methodPath.split('.');
    const funcName = parts.pop(); // Remove and get the last part as function name
    const context = parts.reduce((acc, cur) => acc && acc[cur], obj); // Determine the context
    const func = context ? context[funcName] : obj[funcName]; // Get the function reference

    if (func) {
        return func.apply(context, params); // Call the function with the specified context and parameters
    } else {
        console.error('Function not found', methodPath);
        return null; // Handle as needed for your application
    }
}


function getGobj(){
    return Function('return this')() || (42, eval)('this');
}

// var redirect = 'http://www.website.com/page?id=23231';
// $.redirectPost(redirect, {x: 'example', y: 'abc'});
$.extend({
    redirectPost: function(location, args){
        var form = '';
        _.each( args, function( value, key ) {
            //console.log([key, value]);
            form += '<input type="hidden" name="'+key+'" value="'+value+'">';
        });
        $('<form action="' + location + '" method="POST">' + form + '</form>').appendTo($('body')).submit();
    }
});

;!(function ($) {
  $.fn.classes = function (callback) {
    var classes = [];
    $.each(this, function (i, v) {
      var splitClassName = v.className.split(/\s+/);
      for (var j in splitClassName) {
        var className = splitClassName[j];
        if ($.inArray(className, classes) === -1) {
          classes.push(className);
        }
      }
    });
    if ('function' === typeof callback)
      classes = $.grep(classes, callback);
    return classes;
  };

  $.fn.pop = function() {
    var top = this.get(-1);
    this.splice(this.length - 1, 1);
    return top;
  };

  $.fn.shift = function() {
    var bottom = this.get(0);
    this.splice(0, 1);
    return bottom;
  };

})(jQuery);

// Javascript method to get query string parameters from the iframe's parent url.
function getParentParameterByName(name) {
   name = name.replace(/[\[\]]/g, '\\$&');

   var url     = parent.window.location.href;
   var regex   = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
       results = regex.exec(url);

   if(!results) {
       return null;
   }
   if(!results[2]) {
        return '';
   }
   return decodeURIComponent(results[2].replace(/\+/g, ' '));
}


// https://davidwalsh.name/javascript-debounce-function

// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

function isMobile(){
    return siteType == 'mobile';
}

function isPopupDisplayed() {
    return $('#dobbox-wrapper, .multibox-wrap').length != 0;
}

function deviceExec(func){
    if(typeof deviceHelpers != 'undefined' && !empty(deviceHelpers[func])){
        deviceHelpers[func].call(deviceHelpers);
    }
}

/**
 * Plain js function to get cookies to be used when $ is not on page
 *
 * @param name
 * @returns {*}
 */
function getCookieValue(name) {
    var b = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return b ? b.pop() : '';
}

/**
 * Returns the fingerprint from input or cookie.
 *
 * @returns {jQuery|*}
 */
function getFingerprint() {
    var cookie = document.getElementById('device-fingerprint').value;
    if(empty(cookie)) {
        cookie = getCookieValue("device-fingerprint");
    }
    return cookie;
}

/**
 * Common function to close a multibox popup when we click outside.
 * When invoking this consider that "bind()" prepend the extra params in the function args.
 * TODO replace existing usages with this Ex. TrophyListBoxBase
 *
 * @param box_id
 * @param e
 */
var hideMultiboxOnOutsideClick = function(box_id, e) {
    var container = $("#"+box_id+"");
    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        $.multibox('close', box_id);
        // for some reason passing the single function handler is not working so we remove all mousedown events (atm there were none except this one)
        // $(document).unbind('mousedown', hideMultiboxOnOutsideClick);
        $(document).off('mousedown');
    }
}

/**
 * Gets the prefix for visibility supported by most browsers (97.75%)
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Page_Visibility_API
 * @see http://caniuse.com/#feat=pagevisibility
 * To use with 'visibilitychange' event
 * Resume: It returns "hidden" if:
 *       The user agent is minimized.
 *       The user agent is not minimized, but doc is on a background tab.
 *       The user agent is to unload doc.
 *       The Operating System lock screen is shown.
 * @returns {string|null}
 */
function getPrefixedVisibility(attribute) {
    // Check for support of the un-prefixed property.
    if (attribute in document) {
        // No prefix needed, return null.
        return attribute;
    }

    // Create an array of the possible prefixes.
    var prefixes = ['moz', 'ms', 'o', 'webkit'];

    // Loop through each prefix to see if it is supported.
    for (var i = 0; i < prefixes.length; i++) {
        var testPrefix = prefixes[i] + ucfirst(attribute);
        if (testPrefix in document) {
            // Prefix is supported!
            // Return the current prefixed property name.
            return testPrefix;
        }
    }

    // The API must not be supported in this browser, return null.
    return null;
}
/**
 * Calculates the height of an element if bigger than screen height then gives new height
 * Otherwise return the given height
 *
 * @param height
 * @return number
 */
function getResizedHeight(height){
  var newHeight;
  var visibleWindowSize = getVisibleWindowSize()

  if(!isNumber(height) || height < visibleWindowSize){
    newHeight = height;
  } else {
    newHeight = visibleWindowSize
  }

  return newHeight
}

function getVisibleWindowSize() {
    var topBarHeight = parent.$(".rg-top__container").height() || 0;
    var bottomBarHeight = (parent.$('.games-footer').height() || 0 ) + parseInt(parent.$('.games-footer').css('bottom') || 0);

    return window.parent.innerHeight - topBarHeight - bottomBarHeight;
}


/**
 * Checks if the browser is firefox.
 *
 * @return {boolean}
 */
function isFirefox(){
  return navigator.userAgent.toLowerCase().indexOf('firefox') > -1;
}

/**
 * Plays a sound via XMLHttpRequest and buffer
 * In cases if we cannot play the audios with autoplay html attribute then we can use this function.
 * For an example: We needed this because firefox is not letting us to play the videos in load.
 *
 * @param filePath filepath of the audio
 */
function playSound(filePath) {
  var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  var xhr = new XMLHttpRequest();
  xhr.open('GET', filePath);
  xhr.responseType = 'arraybuffer';

  var startSound = function (audioBuffer) {
    var source = audioCtx.createBufferSource();
    source.buffer = audioBuffer;
    source.connect(audioCtx.destination);
    source.loop = false;
    source.start();
  };

  xhr.addEventListener('load', function() {
    audioCtx.decodeAudioData(xhr.response).then(startSound);
  });

  xhr.send();
}

/**
 * Checks the user jurisdiction to either show or not to show
 * the jurisdiction / access blocked popup on login
 *
 */

function checkJurisdictionPopupOnLogin() {
    if (!showJurisdictionPopup && typeof countryInJurisdiction !== 'undefined') {
        lic('showJurisdictionalNotice', []);
    } else {
        isMobile() ? goTo(llink('/mobile/login/')) : showLoginBox('login');
    }
}

/**
 * Checks the user jurisdiction to either show or not to show
 * the access blocked popup on login
 */

function checkJurisdictionOnRegistration(qUrl) {
    isMobile() ? goTo(llink('/mobile/register/')) : showRegistrationBox(qUrl);
}

/**
 * Kungaslottet login button click
 * */

function showPayNPlayPopupOnLogin() {
    showPayNPlayLogin();
}
function showPayNPlayPopupOnDeposit() {
    mgAjax({action: 'is_deposit_blocked'}, function (ret) {
        const response = JSON.parse(ret);

        if (response.is_deposit_blocked) {
            PayNPlay.showErrorPopup('deposit-block');
            return;
        }

        showPayNPlayLogin(false);
    });
}


/**
 * Truncate the number to 2 decimal places without rounding off
 *
 * @param decimalOdds
 * @return String
 */

 function toFixedDecimal(decimalOdds) {
  const splitedValues = String(decimalOdds.toLocaleString()).split('.');
  let decimalValue = splitedValues.length > 1 ? splitedValues[1] : '';
  decimalValue = decimalValue.concat('00').substr(0,2);

  return splitedValues[0] + '.' + decimalValue;
}

/**
* Returns custom fractional odds from ODDS_CUSTOM_FRACTION_VALUE
*/

function getCustomFractionalOdds(decimalOdds) {
  return ODDS_CUSTOM_FRACTION_VALUE[toFixedDecimal(decimalOdds)] ?? null;
}

/**
* Convert decimals odds into a fraction by subtracting 1, and using 1 as the denominator
* https://mybettingsites.co.uk/bet-calculator/odds-converter/
*
* @param decimalOdds
* @return String
*/

function convertToFractionalOdds(decimalOdds) {
  const baseFraction = (decimalOdds - 1).toFixed(3);
  let fraction = baseFraction;
  let denominator = 1;

  let tick = 0;
  // find the smallest denominator that makes the fraction round number
  while (fraction % 1 !== 0 && tick <= 100) {
      denominator += 1;
      tick += 1;
      fraction = (baseFraction * denominator).toFixed(3);
  }

  return `${Math.round(Number(fraction))}/${denominator}`;
}

/**
* @typedef {Object} TimeInterval
 * @property {number} days  - The difference in days
 * @property {number} hours  - The difference in hours
 * @property {number} mins  - The difference in mins
 * @property {number} seconds  - The difference in seconds
*/

/**
 * Returns detailed info on how much time has / will elapse(d) in a certain amount of seconds
 * Note that both stime and etime needs to **both** be passed in or not
 * @see Phive::timeIntervalArr() in Phive.base.php
 * @param {number|null} secs The amount of seconds we want to use for display
 * @param {Date|null} stime Optional start stamp used as base
 * @param {Date|null} etime Optional end stamp used to subtract from the base stamp
 * @return TimeInterval|null
 */
function timeInterval(secs, stime, etime)
{
    if(stime instanceof Date && etime instanceof Date) {
        secs = Math.round(Math.abs(stime.getTime() - etime.getTime()) / 1000);
    }

    if(!secs) {
        return null;
    }

    var days = Math.floor(secs / 86400);
    var rem = secs > 86400 ? secs % 86400 : secs;
    var hours 	= Math.floor(rem / 3600);
    rem	= secs > 3600 ? secs % 3600 : secs;
    var mins	= Math.floor(rem / 60);
    var seconds	= Math.floor(secs) % 60;

    return {days, hours, mins, seconds};
}

/**
 * Checks and Declares Constant type if undefined
 * @param constantName
 * @param value
 */
function declareConstantIfUndeclared(constantName, value) {
    if (typeof window[constantName] === 'undefined') {
        Object.defineProperty(window, constantName, {
            value: value,
            writable: false
        });
    }
}

// Channel name, this channel should match flutter channel name
const MOBILE_APP_CHANNEL_NAME = 'MOBILE_APP_MESSAGES';

/**
 * Sends data to flutter via webview channel
 * @param constantName
 * @param data
 */
function sendToFlutter(data) {
    window[MOBILE_APP_CHANNEL_NAME].postMessage(JSON.stringify(data));
}

function getWebviewParams() {
    const urlParams = new URLSearchParams(window.location.search);

    const displayMode = urlParams.get('display_mode') || '';
    const authToken = urlParams.get('auth_token') || '';

    return displayMode && authToken ? '&display_mode=${displayMode}&auth_token=${authToken}' : '';
}

/**
 * Function to detect iOS 15 on iPhone devices
 * * @param element
 */
function detectIOS15AndAddClass(element) {
  const userAgent = navigator.userAgent;
  if (/iPhone OS 15_\d+(_\d+)? like Mac OS X/i.test(userAgent)) {
      const domElement = element[0] || element;
      if (domElement.classList) {
        domElement.classList.add('ios15');
      }
  }
}

/**
 * Returns a promise that resolves when all images within the specified container are loaded.
 * @param {string} containerId - The ID of the container element.
 * @returns {Promise}
 */
function waitForImagesToLoad(containerId) {
  var $images = $('#' + containerId + ' img');
  var deferreds = [];
  $images.each(function() {
      if (!this.complete) {
          var deferred = $.Deferred();
          $(this).one('load', deferred.resolve);
          $(this).one('error', deferred.resolve);
          deferreds.push(deferred);
      }
  });

  return $.when.apply($, deferreds);
}

function checkForGACookieId()
{
    /**
     * Check if GTM dataLayer is loaded and gtm started
     * @returns {boolean}
     */
    function checkGTMConnection() {
        let gtmStartedEvent = window.dataLayer.find(element => element['gtm.start']);
        if (window.dataLayer && Array.isArray(window.dataLayer) && gtmStartedEvent) {
            return true;
        } else if (typeof gtmStartedEvent !== 'undefined' && !gtmStartedEvent['gtm.uniqueEventId']) {
            return false;
        } else {
            return false;
        }
    }

    /**
     * Check if GA4 client ID is created
     * This means GTM is loaded and functional
     * @returns {boolean|string}
     */
    function getGA4ClientId() {
        const ga4Cookie = document.cookie.split('; ').find(row => row.startsWith('_ga='));
        if (ga4Cookie) {
            const clientId = ga4Cookie.split('.').slice(-2).join('.');
            return clientId;
        } else {
            return false;
        }
    }

    /**
     * Check if GAU client ID is created
     * This means GTM is loaded and functional
     * @returns {boolean|string}
     */
    function getGAuClientId() {
        const gaCookie = document.cookie.split('; ').find(row => row.startsWith('_ga='));
        if (gaCookie) {
            const clientId = gaCookie.split('=')[1].split('.').slice(-2).join('.');
            return clientId;
        } else {
            return false;
        }
    }

    if (checkGTMConnection() && (getGA4ClientId() || getGAuClientId()) && !getCookieValue('ga_cookie_id'))
    {
        if (getGA4ClientId()) {
            sCookieExpiry('ga_cookie_id', getGA4ClientId(), 365);
        } else if(getGAuClientId()) {
            sCookieExpiry('ga_cookie_id', getGAuClientId(), 365);
        }
    }
}
