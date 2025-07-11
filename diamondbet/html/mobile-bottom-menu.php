<?
require_once __DIR__.'/../boxes/MgMobileGameChooseBox.php';
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/FooterMenuBoxBase.php';
?>
<div class="vs-mobile-bottom-menu">
  <div class="css-flex-container css-flex-valing-center">
    <a onclick="showSearch()" href="#" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-vs-search"></div>
    </a>
    <a href="<?= llink('/mobile/casino/?tag=new.cgames') ?>" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-newgames-icon"></div>
    </a>
    <a href="<?= llink('/mobile/casino/?tag=hot') ?>" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-hot"></div>
    </a>
    <a href="<?= llink('/mobile/casino/?tag=popular.cgames') ?>" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-vs-popular-icon"></div>
    </a>
    <a href="<?= llink('/mobile/casino/?tag=last.played') ?>" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-last-played"></div>
    </a>
    <a onclick="<?php if (cu()) { echo 'showNotifications()'; } else { echo "showLoginBox('login')"; } ?>" id="vs-button__notifications" href="#" class="css-flex-uniform-section css-flex-column">
      <div class="icon icon-vs-bell"></div>
    </a>
  </div>
</div>

<script>
  function showNotifications() {
      $('.multibox-close').trigger('click');
      if (!$("#notifications-box").length) {

          $.multibox({
              url: '/phive/modules/BoxHandler/html/ajaxActions.php',
              type: 'get',
              lang: cur_lang,
              params: {
                  action: 'GetBoxHtml',
                  lang: cur_lang,
                  func: 'getLatestNotifications',
                  boxid: '<?= FooterMenuBoxBase::BOX_ID ?>'
              },
              offset: {
                  'top': '-40px',
                  'bottom': '50px'
              },
              hideOverlay: true,
              showClose: true,
              id: 'notifications-box',
              onComplete: function(){
                  $("#notifications-box").find(".multibox-outer").prepend('<div class="mbox-msg-title-bar"><?= t('notifications') ?></div>');
                  resizePopupContentHandler($("#search-box .multibox-content"));
                  $("body").addClass('scroll-lock');
              },
              onClose: function(){
                  $("body").removeClass('scroll-lock');
              }
          });
      }
  }

  function showSearch() {
      $('.multibox-close').trigger('click');
      if (!$("#search-box").length) {
          $.multibox({
              type: 'html',
              lang: cur_lang,
              content: '<div class="game-filter" style="margin-top:10px"><div class="type-search">\n' +
                  '      <input class="search-input" placeholder="Search">\n' +
                  '      <button class="clear"><span class="icon icon-vs-close" style="position: relative; top: 2px"></span>\n' +
                  '      </button>\n' +
                  '    </div></div>' +
                  '<div class="search-result"></div>',
              offset: {
                  'top': '0px',
                  'bottom': '50px',
              },
              hideOverlay: true,
              showClose: true,
              id: 'search-box',
              onComplete: function(){
                  $("#search-box").find(".multibox-outer").prepend('<div class="mbox-msg-title-bar"><?= t('search') ?></div>');
                  resizePopupContentHandler($("#search-box .multibox-content"));
                  $("body").addClass('scroll-lock');

                  $(document).on('keyup', "#search-box .search-input", debounce(function(event){

                      var cur = $(this);
                      if(cur.val().length >= 2) {
                          params = {
                              func: 'printGameSection',
                              search_str: cur.val(),
                              rcount: 4,
                          };
                          if(typeof(func) == 'undefined') {
                              func = function(ret){
                                  $("#search-box .search-result").html(ret);
                                  $('#search-box .game-tbl').css({
                                      'width': '100%',
                                      'table-layout': 'fixed',
                                      'text-align': 'center'
                                  }).removeClass('game-tbl');
                                  $('#search-box .game-text').addClass('text-cut')
                              }
                          }
                          ajaxGetBoxHtml(params, cur_lang, <?= MgMobileGameChooseBox::BOX_ID ?>, func);
                      }
                  }, 500));

                  $(document).on('click', "#search-box .clear", function(event){
                      $('#search-box .search-input').val('');
                      $('#search-box .search-result').html('');
                  });
              },
              onClose: function(){
                  $("body").removeClass('scroll-lock');
              }
          });
      }
  }

  function resizePopupContentHandler($selector) {
      $(window).on("resize", function() {
          setTimeout(function() {
              $selector.height($(window).height() - 90);
          }, 500);
      });
  }

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
  }
</script>