<script>
 var myScroll = null;

 var initLeftMenu = function (){
   myScroll = new iScroll('mobile-left-menu', { hScrollbar: false, vScrollbar: false, hScroll: true });
 }

 var lmenuOut = false;
 function toggleMenu(){
   
   var animSpeed = 200;
   var animType = 'linear';
   var lmenu = $("#mobile-left-menu");
   var lmenuBtn = $(".mobile-fold-btn");
   var wrapper = $("#wrapper,#bottom-sticky");

   //lmenu.removeClass('mobile-left-menu').addClass('mobile-left-menu-animated');
   if(lmenuOut){
     wrapper.animate({"margin-left": '0px'}, animSpeed, animType, function(){ lmenu.animate({left: '-300px'}, animSpeed, animType); });
     //lmenu.removeClass('lmenuSlideRight').addClass('animated lmenuSlideLeft');
     //wrapper.removeClass('wrapperSlideRight').addClass('animated wrapperSlideLeft');
     lmenuBtn.css({left: '-500px'});
     lmenuOut = false;
     /* Change the X with a Hamburger */
     $('.vs-mobile-menu__item.vs-mobile-menu__item-menu.icon').removeClass('vs-close').addClass('icon-vs-hamburger');
   }else{
     //lmenu.removeClass('lmenuSlideLeft').addClass('animated lmenuSlideRight');
     //wrapper.removeClass('wrapperSlideLeft').addClass('animated wrapperSlideRight');
     lmenu.animate({left: '0px'}, animSpeed, animType, function(){ wrapper.animate({"margin-left": '250px'}, animSpeed, animType); });
     lmenuBtn.css({left: '250px'});
     lmenuOut = true;

     /* Change the hamburger with an X */
     $('.vs-mobile-menu__item.vs-mobile-menu__item-menu.icon').removeClass('icon-vs-hamburger').addClass('icon-vs-close');
   }

     //if lmenuOut is active the main page will not be scrollable.
     if(lmenuOut === true){
         $('.container-holder').addClass('fixed-position')
     }else{
         $('.container-holder').removeClass('fixed-position')
     }
 }
 
 var refreshIscroll = function(){
   setTimeout(function(){
    if (myScroll) {
     myScroll.refresh()
    }
   }, 500);
 }
 
 addOrFunc(refreshIscroll);
 
 window.addEventListener("resize", refreshIscroll, false);
 

let stickyScroll = (function(){
  return {
    leftMenu: null,
    leftMenuContainer: null,
    init() {
      this.leftMenu = $('.acc-left-mobile-menu');
      this.leftMenuContainer = $('#mobile-left-menu');
      window.addEventListener("scroll", () => this.fixAmountScrolled(this.leftMenu, this.leftMenuContainer));
    },
    fixAmountScrolled(leftMenu, leftMenuContainer) {
      var winheight= window.innerHeight || (document.documentElement || document.body).clientHeight
      const scrollTop = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;

      leftMenu.css('padding-top', scrollTop);
      leftMenuContainer.css('height', winheight + scrollTop);
    }
  };
})();

 jQuery(document).ready(function(){
   stickyScroll.init();
   initLeftMenu.call();
   x$('.mobile-left-menu').swipe(function(e, data) {
     if(data.direction == 'left')
     toggleMenu();
   }, 
		                 {
       swipeCapture: true,
       longTapCapture: false,
       doubleTapCapture: false,
       simpleTapCapture: false
     });
   refreshIscroll.call();
 });
 
</script>
<div id="mobile-left-menu" class="mobile-left-menu">
  <?php phive("BoxHandler")->boxHtml(BoxHandler::MENU_BOX_MOBILE) ?>
</div>
<div class="mobile-fold-btn" onclick="toggleMenu()"> </div>
