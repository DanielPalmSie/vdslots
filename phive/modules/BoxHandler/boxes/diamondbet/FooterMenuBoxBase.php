<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class FooterMenuBoxBase extends DiamondBox{

  const BOX_ID = 967;

  function init(){
    $this->handlePost(array('tags', 'show_clock'), array('tags' => 'new'));
    $this->tarr = explode(',', $this->tags);
    $this->mg = phive("MicroGames");
    $this->cur_lang = phive('Localizer')->getLanguage();
    $this->item_width = 97;
    if(isLogged() && phive('UserHandler')->getSetting('has_notifications')){
      $this->tot_count = phive('UserHandler')->getNotificationCountSince('2000-01-01 00:00:00');
      $this->not_height = min(425, max(1 , $this->tot_count) * 85);
    }
      $this->cur_user = cuPl();
  }

  function earnedCashback(){
    phive('UserHandler')->earnedCashback();
    echo 'ok';
  }

  function drawNotificationItem($item, $hbars = false){ ?>
    <div class="notification-item gradient-normal">
      <div class="notification-img">
        <img src="<?php if($hbars) echo '{{img}}'; else phive('UserHandler')->eventImage($item);  ?>" />
      </div>
      <div class="notification-content"><?php echo $hbars ? '{{{str}}}' : phive('UserHandler')->eventString($item, 'you.'); ?></div>
      <div class="notification-x"><span class="icon icon-vs-close"></span></div>
    </div>
  <?php }

  function drawNotificationListItem($item, $class = 'notification-list-holder'){
    $uh = phive('UserHandler');
    if (phive()->isMobile()) { ?>
      <div class="vs-notification-message">
          <div class="vs-notification-message__image-container">
            <img class="vs-notification-message__image" src="<?= $uh->eventImage($item) ?>">
          </div>
          <div class="vs-notification-message__html"><?= $uh->eventString($item, 'you.') ?></div>
        </div>
      <?php
    } else { ?>
    <div class="notification-list-line"></div>
    <div class="<?php echo $class ?>">
      <div class="notification-list-item">
        <div class="notification-img">
          <img src="<?php $uh->eventImage($item)  ?>" />
        </div>
        <div class="notification-content"><?= $uh->eventString($item, 'you.') ?></div>
      </div>
    </div>
  <?php
    }
  }

  /*
   * This function will modify all links in the notifications to display the game manually closed popup for spanish players
   * Note: only works if player is Spanish, player is actively playing a game, and the current game is not within BOS
   */
  function changeNotificationRedirection(){ ?>
      <script>
        function notifRedirection(link){
            gameCloseRedirection(decodeURIComponent(link));
        }

        if ((typeof extSessHandler !== 'undefined')  && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
            var notifs = document.querySelectorAll(".notification-content");
            notifs.forEach(notif => {
               if(notif.lastElementChild !== null && notif.lastElementChild.hasAttribute("onclick")){
                   var redirectFunc = notif.lastElementChild.getAttribute("onclick");
                   notif.lastElementChild.setAttribute("onclick", "notifRedirection(\'" + redirectFunc.replace(/'/g, "%27") + "\')");
               }
            });
       }
      </script>
  <?php }

  function drawNotificationMsg($str){ ?>
    <div class="notification-list-line"></div>
    <div class="notification-list-holder notification-list-holder-light">
      <div class="notification-list-item">
        <div class="notification-msg"><?php et($str) ?></div>
      </div>
    </div>
  <?php }

  function getLatestNotifications(){
    if(empty($_SESSION['mg_id']))
      die('no');
    if(empty($this->tot_count))
      die($this->drawNotificationMsg('no.notifications'));
    $count = $this->notificationCount();
    $_SESSION['ncount_since'] = phive()->hisNow();
    $ns = phive('UserHandler')->getLatestNotifications('', 12);
    if(empty($ns))
      die('no');
    $i = 0;
    foreach($ns as $ev){
      $this->drawNotificationListItem($ev, $i < $count ? 'notification-list-holder notification-list-holder-light' : 'notification-list-holder');
      $i++;
    }
    $this->changeNotificationRedirection();
  }

  function getNotifications(){
    $ns = phive('UserHandler')->getNotifications();
    if(empty($ns))
      die('no');
    foreach($ns as $ev)
      $this->drawNotificationItem($ev);
  }

  function hasNotifications(){
    if(isLogged() && cuSetting('show_notifications') !== '0' && phive('UserHandler')->getSetting('has_notifications') === true)
      return true;
    return false;
  }

  function notificationCount(){
    $since = empty($_SESSION['ncount_since']) ? cuPlAttr('last_login') : $_SESSION['ncount_since'];
    $count = phive('UserHandler')->getNotificationCountSince($since);
    return $count;
  }

  function printCSS(){
    loadCss("/diamondbet/css/" . brandedCss() . "game-footer.css");
    loadCss("/phive/js/nanoScroller/nanoscroller.css");
  }

  function printHTML(){ ?>
    <?php loadJs("/phive/js/underscore.js") ?>
    <?php loadJs("/phive/js/jquery.flexslider-min.js") ?>
    <?php loadJs("/phive/modules/Micro/play_mode.js") ?>
    <?php if($this->hasNotifications()): ?>
      <?php loadJs("/phive/js/handlebars.js") ?>
      <script id="notification-tpl" type="text/x-handlebars-template">
        <?php $this->drawNotificationItem('', true) ?>
      </script>
      <script>
        var notificationTpl = Handlebars.compile($('#notification-tpl').html());
      </script>
    <?php endif ?>
    <script>
      //var currentlyPlaying = '', filterNetwork = '';
      var gamesFooterStatus = 'down';
      var gamesFooterMode = 'single'; // other option 'multi' meaning multiple games in a grid
      var multiSelectorClicked = false;
      var unlockNetwork = ''; // if clicking on game change in multi view, filtering for the game network in that particular window should be not done!
      var loadGameAmount = 100;
      var loadMoreAMount = 50;
      var allGames = [];
      var gameTarget = 0;

      function gamesFooterMove(status, dist, fdist, callb){
        if(gamesFooterStatus == status){
          if(typeof callb === 'function')
            callb.call();
          return;
        }
        gamesFooterStatus = status;
        var windowWidth = $(window).width();
        var minWidth = 1100;
        if(status === "up" && windowWidth < minWidth){
          fdist += 30;
        }

        $(".games-footer").animate({"bottom": fdist+'px'});
        if($("#notifications").length > 0){
          var sign = dist < 0 ? '-=' : '+=';
          var absDist = Math.abs(dist);
          $('#notifications').animate({"bottom": sign+absDist+'px'}, 200, 'linear');
          $('#notifications-list').animate({"bottom": sign+absDist+'px'}, 200, 'linear');
        }
      }

      function gamesFooterUp(callb){
        gamesFooterMove('up', footerMovement.up.dist, footerMovement.up.fdist, callb);
        $(".gfooter-direction-nav a").animate({opacity: 1});
        $("#games-footer-down").show();
        $("#gfooter-result .gfooter-viewport").show();
      }

      function gamesFooterDown(callb){
        $("#games-footer-down").hide();
        gamesFooterMove('down', footerMovement.down.dist, footerMovement.down.fdist, callb);
        multiSelectorClicked = false;
        unlockNetwork = '';
        $(".gfooter-direction-nav a").animate({opacity: 0});
        $("#gfooter-result .gfooter-viewport").hide();
      }

      function footerGames(params){
        params.func = 'getBySubTag';
        ajaxGetBoxHtml(params, cur_lang, <?php echo $this->getId() ?>, function(ret){
          $("#gfooter-result").html(ret);
          gamesFooterUp();
        });
      }

      /**
      * Created a function to clear the flexslider data, viewport element and direction(prev, next) elements
      * By default everything will be true and remove all the elements.
      * Pass clearFlex,clearViewPort,clearDirectionNav as an options when needed.
      * @param options
      */
      function clearSlider(options){
           var params = {
               clearFlex: options === undefined || options.clearFlex === undefined || options.clearFlex === true,
               clearViewPort: options === undefined || options.clearViewPort === undefined || options.clearViewPort === true,
               clearDirectionNav: options === undefined || options.clearDirectionNav === undefined || options.clearDirectionNav === true
           };

           if(params.clearFlex) {
               $('#gfooter-result').removeData("flexslider");
           }

           if(params.clearViewPort) {
               $(".gfooter-viewport").remove();
           }

           if(params.clearDirectionNav) {
               $(".gfooter-direction-nav").remove();
           }
      }

     function isCurrentlyPlaying(ng){
       return _.find(currentlyPlaying, function(g){ return ng.game_id == g.game_id; }) != undefined;
     }

     function onFooterResult(ret){
       var tmp;
       var result;
       result = typeof ret === 'string' ? this : ret;
       if(typeof result === 'object'){
         result = Object.keys(result).map(function(key){
           return result[key];
         });
       }
       clearSlider();
       var sr = $("#gfooter-result");
       var wH = $(window).height();
       var os = $('#play-box').offset();
       var first100Data = [];
       allGames = result;
       if (multiSelectorClicked == false) {
         first100Data = allGames.splice(0, loadGameAmount);
         tmp = '<ul class="slides">';
         _.each(first100Data, function(game){
           tmp += getGameThumbnail(game, 'playGameDepositCheckBonus');
         });
         tmp += '</ul>';
         sr.html(tmp);
       }else{
         var h = wH - os.top;
         sr.css({'height': h + 'px'});
         tmp = '<div id="nano-holder" class="nano"><div class="nano-content"><ul>';
         _.each(result, function(game){
             // filter currently played game and networks that can have two games at the same time
             if (multiSelectorClicked == true && (isCurrentlyPlaying(game) || ((filterNetwork.indexOf(game.network) != -1) && game.network !== unlockNetwork))) {
                 //TODO what is to be done here?
             }else {
                 //TODO I think we only need game-id here
                 allGames.push(game);
             }
         });

         first100Data = allGames.splice(0, loadGameAmount);
         _.each(first100Data, function(game){
             var tempChildEl = getGameThumbnail(game, 'chooseGame');
             tmp += tempChildEl;
             currentGameIndex = gameTarget;
         });

         var loadMoreButton = '';
         if(first100Data.length >= loadGameAmount ){
           loadMoreButton =  '<div class="load-more-games"><button class="btn btn-l btn-default-l" onclick="loadMoreGames()"><span><?php et('mobile.game.list.paginator.load.more') ?></span></button></div>';
         }

         tmp += '</ul>'+ loadMoreButton + '</div></div></div>';
         sr.html(tmp);
       }

       if (!multiSelectorClicked) {
         var nItems = $(window).width() / (<?php echo $this->item_width ?> +10);

         if(sr.find('img').length > nItems){
           sr.flexslider({
             touch: false,
             animation: "slide",
             animationLoop: false,
             slideshow: false,
             itemWidth: <?php echo $this->item_width ?>,
             itemMargin: 5,
             minItems: nItems,
             maxItems: nItems,
             controlNav: false,
             prevText: '&#xAB;',
             nextText: '&#xBB;',
             namespace: "gfooter-",
             move: Math.round(nItems / 2),
             before: function () {
               var games = allGames.splice(0, 20);
               _.each(games, function(game){
                 var tmp = getGameThumbnail(game, 'playGameDepositCheckBonus');
                 sr.data('flexslider').addSlide(tmp);
               });
             }
           });
         }else{
           $("#gfooter-result").animate({'margin-left': '-30px'});
         }
         gamesFooterUp();
       }else{
         gamesFooterMove('up', 110, (wH - os.top) - 110);
         $('#gfooter-result').css({'margin-left' : '0px'});
         $('#nano-holder').nanoScroller();
       }
     }

     function gameThumbnailPopupRedirect(gameId, gameUrl){
       var redirectString = "playGameDepositCheckBonus(\'" + gameId + '\', \'\', \''+gameUrl+"\')";
       gameCloseRedirection(redirectString);
     }

     function getGameThumbnail(game, type){
         var mediaUrl = '<?php echo getMediaServiceUrl(); ?>/file_uploads/thumbs/'+ game.game_id +'_c.jpg';
         var liElement;

         if(type === 'chooseGame'){
            liElement = '<li data-game-id="'+ game.game_id +'" onclick="chooseGame(this, '+ gameTarget +')">';
         }else if(type === 'playGameDepositCheckBonus'){
           if((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')){
               liElement = '<li onclick="gameThumbnailPopupRedirect(\'' + game.game_id + '\', \''+game.game_url+'\')">';
           }else{
               liElement = '<li onclick="playGameDepositCheckBonus(\'' + game.game_id + '\', \'\', \''+game.game_url+'\')">';
           }
         }

         liElement  += '<img class="img-thumb" title="'+ game.game_name +'" src="'+ mediaUrl +'" />';
         if(game.ribbon_pic){
             liElement += getRibbon(game);
         }

         liElement += "</li>";

         return liElement;
     }

     function getRibbon(game){
       var ribbon = '';
       if(game.ribbon_pic.includes('live-casino')){
          // Live casino
           ribbon += '<img src="<?php echo getMediaServiceUrl(); ?>/file_uploads/ribbons/'+ game.ribbon_pic +'.png" class="thumb-ribbon" />'
       }else if(game.ribbon_pic){
         // Others
           ribbon = '<img src="<?php echo getMediaServiceUrl(); ?>/file_uploads/ribbons/'+ game.ribbon_pic +'_'+ cur_lang +'.png" class="thumb-ribbon" />'
       }

       return ribbon;
     }

     function loadMoreGames() {
       var games = allGames.splice(0, loadMoreAMount);
       _.each(games, function(game){
             var tempChildEl= getGameThumbnail(game, 'chooseGame');
             $("#nano-holder ul").append(tempChildEl);
             currentGameIndex = gameTarget;
         });
     }

     function upFooterList(func, sel, params) {
       gamesFooterUp();
       footerList(func, sel, params);
     }

      function footerList(func, sel, params){
        if(typeof params == 'undefined') {
          params = {"func": func};
        }
        else {
          params.func = func;
        }

        // this variables comes from MgPlayBoxBase4 when playing with 2/4 games.
        if(typeof currentGames != "undefined") {
            params.currentGames = currentGames;
        }
        if(typeof currentGameIndex != "undefined") {
            params.currentGameIndex = currentGameIndex;
        }

        $(".games-footer").find('li').removeClass('gfooter-selected');

        $(sel).addClass('gfooter-selected');

        ajaxGetBoxJson(params, cur_lang, <?php echo $this->getId() ?>, function(ret){
          onFooterResult(ret);
        });
     }

     function hideNotifications(){
       $("#notifications").animate({"right": '-220px'}, 200, 'linear', function(){
         $('.notification-item').remove();
       });
     }

     function showNotifications(){
       $("#notifications").animate({"right": '20px'}, 200, 'linear');
     }

     var notificationListStatus = 'down';
     function showNotificationList(){
       notificationListStatus = 'up';
       $('#notifications-list').animate({"bottom": '40px'}, 400, 'linear');
     }

     function hideNotificationList(){
       notificationListStatus = 'down';
       $('#notifications-list').animate({"bottom": '-500px'}, 400, 'linear');
     }

     function toggleNotificationList(){
       if(notificationListStatus == 'up'){
         hideNotificationList();
         return;
       }
       if (hasCustomNotificationHandler() && messageProcessor.pauseNotifications()) {
         showNotificationList();
         return;
       }
       ajaxGetBoxHtml({func: 'getLatestNotifications'}, cur_lang, <?php echo $this->getId() ?>, function(ret){
         if(ret != 'no'){
           $("#notification-count")
            .data('count', 0)
            .html('<span class="games-footer-icon icon icon-00-notifications"></span>')
           ;

           $("#notifications-nano-content").html(ret);
           $("#notifications-nano").nanoScroller();
           showNotificationList();
         }
       });
     }

     function hasCustomNotificationHandler() {
         return typeof messageProcessor !== 'undefined' && typeof messageProcessor.handleNotifications === "function";
     }

     function doNotification(content){
       if (hasCustomNotificationHandler()){
           messageProcessor.handleNotifications(content, showNotification);
           return;
       }
       showNotification(content);
     }

     function updateNotificationsCounter(){
        var $notificationCount = $('#notification-count');
        var notificationCount = parseInt($notificationCount.data('count'));

        if (notificationCount >= 0 && notificationCount < 9) {
          notificationCount = notificationCount + 1;
          $notificationCount.data('count', notificationCount);
          $notificationCount.html('<span class="games-footer-icon icon icon-0' + notificationCount + '-notifications"></span>');
        } else if (notificationCount >= 9) {
          $notificationCount.html('<span class="games-footer-icon icon icon-0-notifications"></span>');
        }
     }

     function showNotification(content) {
       updateNotificationsCounter();

       var notification = $(content);
       $("#notifications").append(notification);
       notification.animate({"top": '-=75px', "min-height": '75px'}, 200, 'linear');
       setTimeout(function(){
         notification.animate({"margin-left": '220px'}, 200, 'linear', function(){
           notification.remove();
         });
       }, 10000);
       notification.find('.notification-x').click(function(){
         notification.hide(200);
       });
     }

     function getNotifications(){
       ajaxGetBoxHtml({func: 'getNotifications'}, cur_lang, <?php echo $this->getId() ?>, function(ret){
         if(ret != 'no'){
           $(ret).each(function(i){
             doNotification($(this).clone().wrap('<p>').parent().html());
           });
         }
       });
     }

     $(document).ready(function(){
        ajaxGetBoxHtml({func: 'getLatestNotifications'}, cur_lang, <?php echo $this->getId() ?>, function(ret){
         if(ret != 'no'){
           $("#notifications-nano-content").html(ret);
           $("#notifications-nano").nanoScroller();
         }
       });

        $(".games-footer").find("li[class*='cgames']").each(function(){
          $(this).click(function(){
            var cls = $(this).classes(function(c, i){ return c.match(/cgames/); })[0];
            gamesFooterUp(); // 2024.06.28 -- this makes the gamesFooterUp function execute twice
            footerList('getBySubTag', '.'+cls, {tag: cls.replace(/-/g, '.')});
          });
       });

        $("#games-footer-down").click(function(){ gamesFooterDown(); });

        $('body').click(function (e){
          if($('.games-footer').length > 0){
            var tmp = $('.games-footer').offset();
            if(e.pageY < tmp.top)
              gamesFooterDown();
          }
        });

       $("#footer_search").click(function(){
         $("#gfooter-result").html('<ul id="gfooter-result-ul" class="slides"></ul>');
         if(!multiSelectorClicked || gamesFooterMode != 'multi'){
           clearSlider();
           gamesFooterUp();
         }
        });

        setupCasinoSearch(
          function(i, o){
            var lang = (cur_lang === default_lang) ? '' : ('/' + cur_lang);
            li = $('<li></li>');
            if (gamesFooterMode == 'multi' && multiSelectorClicked) {
              currentGameIndex = gameTarget;
              li.attr("onclick", "chooseGame(this, "+gameTarget+")");
              li.attr("data-game-id", this.game_id);
            }else {
                if((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
                    li.attr("onclick", "playGameDepositCheckBonusRef('"+this.game_id+"', '', '"+this.game_url+"')");
                }
                else {
                    li.attr("onclick", "playGameDepositCheckBonus('"+this.game_id+"', '', '"+this.game_url+"');");
                }
            }
            img = $('<img class="img-thumb" />');
            //IMGTODO
            img = img.attr("src", "<?php echo getMediaServiceUrl(); ?>/file_uploads/thumbs/"+this.game_id+"_c.jpg");
            li.append(img);

            if(this.ribbon_pic){
              var ribbon = getRibbon(this);

              li.append(ribbon);
            }

            // FILTERING
            if (gamesFooterMode == 'multi' && multiSelectorClicked == true && this.network != unlockNetwork) {
              if (isCurrentlyPlaying(this))
                return;
            //  if (filterNetwork.indexOf(this.network) != -1) return;
            }

            $("#gfooter-result-ul").append(li);
          },
          function(){
            $("#gfooter-result").html('<ul id="gfooter-result-ul" class="slides"></ul>');
          },
          "#gfooter-result-ul",
          "#footer_search",
          function(){
             if(multiSelectorClicked === false){
               var nItems = $(window).width() / (<?php echo $this->item_width ?> +10);
               var gfooterResult = $("#gfooter-result");
               if(gfooterResult.find('img').length > nItems){
                 //Calling the clearSlider function to remove the slider and associated navigation(prev,next) links.
                 clearSlider({clearFlex: true, clearViewPort: false, clearDirectionNav: true});
                 gfooterResult.flexslider({
                   touch: false,
                   animation: "slide",
                   animationLoop: false,
                   slideshow: false,
                   itemWidth: <?php echo $this->item_width ?>,
                   itemMargin: 5,
                   minItems: nItems,
                   maxItems: nItems,
                   controlNav: false,
                   prevText: '&#xAB;',
                   nextText: '&#xBB;',
                   namespace: "gfooter-",
                   move: Math.round(nItems / 2)
                 });
                 gfooterResult.removeClass('remove-slide');
                 gamesFooterUp();
               }else{
                 gfooterResult.addClass('remove-slide');
                 // Calling the clearSlider function to remove the associated navigation(prev,next) links.
                 clearSlider({clearFlex: false, clearViewPort: false, clearDirectionNav: true});
               }
             }
          }
        );

       <?php if($this->show_clock == 'yes'): ?>
         setupClock();
       <?php endif ?>

        if($("#play-box").length == 0)
          $(window).on("resize", gamesFooterDown);

       $(".games-footer-hot").click(function(){ upFooterList('getHot', '.games-footer-hot'); });
       $(".games-footer-popular").click(function(){ upFooterList('getPopular', '.games-footer-popular'); });
       $(".games-footer-lastplayed").click(function(){ upFooterList('getLastPlayed', '.games-footer-lastplayed'); });
       $(".games-footer-favorites").click(function(){ upFooterList('getFavs', '.games-footer-favorites', {uid: '<?php echo $_SESSION['mg_id'] ?>'}); });

       <?php if($this->hasNotifications()): ?>
       if(!hasWs()){
         getNotifications();
         setInterval(function(){ getNotifications(); }, 10000);
       }else{
           doWs('<?php echo phive('UserHandler')->wsUrl('notifications') ?>', function(e) {
               doNotification(notificationTpl(JSON.parse(e.data)));
           });
       }

       <?php if(!empty($_SESSION['mg_id']) && !phive()->isEmpty(phMget(mKey($_SESSION['mg_id'], "earned-loyalty")))): ?>
         setTimeout(function(){ ajaxGetBoxHtml({func: "earnedCashback"}, cur_lang, <?php echo $this->getId() ?>, function(ret){ }) }, 2000);
       <?php endif ?>
       <?php endif ?>

      });
    </script>
    <?php if(phive("Pager")->edit_boxes || phive("Pager")->edit_strings || phive('UserHandler')->doForceDeposit()): ?>
      <div>
    <?php else: ?>
      <div class="games-footer">
    <?php endif ?>
      <ul class="games-footer-topbar">
        <li id="games-footer-search">
          <?php dbInput("footer_search", strtoupper(t('search'))) ?>
          <span class="icon icon-vs-search"></span>
        </li>
        <?php foreach($this->tarr as $tag): ?>
          <?php
            $iconsMap = [
              "new" => "icon-newgames-icon",
              "featured" => "icon-vs-thumbs-up-featured"
            ];
          ?>
          <li class="games-footer-image <?php echo $tag.'-cgames' ?>">
            <span class="games-footer-icon icon <?php echo $iconsMap[$tag]; ?>"></span>
          </li>
          <li class="games-footer-text <?php echo $tag.'-cgames' ?>">
            <?php et($tag.'.cgames') ?>
          </li>
        <?php endforeach ?>

        <li class="games-footer-image games-footer-hot">
          <span class="games-footer-icon icon icon-hot"></span>
        </li>
        <li class="games-footer-text-hot games-footer-hot">
          <?php echo strtoupper(t('hot')) ?>
        </li>

        <li class="games-footer-image games-footer-popular">
          <span class="games-footer-icon icon icon-vs-popular-icon"></span>
        </li>
        <li class="games-footer-text games-footer-popular">
          <?php et('popular') ?>
        </li>

        <li class="games-footer-image games-footer-lastplayed">
          <span class="games-footer-icon icon icon-last-played"></span>
        </li>
        <li class="games-footer-text games-footer-lastplayed">
          <?php et('last.played') ?>
        </li>
        <?php if(isLogged()): ?>
          <li class="games-footer-image games-footer-favorites">
            <span class="games-footer-icon icon icon-vs-star"></span>
          </li>
          <li class="games-footer-text games-footer-favorites">
            <?php et('my.favorites') ?>
          </li>
        <?php endif ?>
        <li id="games-footer-down">
          <span class="icon-vs-arrow-down"></span>
        </li>

        <?php if($this->show_clock == 'yes'): ?>
          <li class="games-footer-text games-footer-clock">
              <?php digitalClock() ?>

          </li>
        <?php endif ?>

        <?php if(isLogged()): ?>
            <?php if(phive('UserHandler')->getSetting('has_notifications') === true):
            $count = $this->notificationCount();
            ?>
                <li class="games-footer-notifications-icon pointer" onclick="toggleNotificationList()">
                    <?php if (!empty($count)): ?>
                      <?php if ($count > 0 && $count < 10): ?>
                        <div id="notification-count" data-count="<?php echo $count;?>">
                          <span class="games-footer-icon icon icon-0<?php echo $count; ?>-notifications"></span>
                        </div>
                      <?php elseif ($count > 9): ?>
                        <div id="notification-count" data-count="<?php echo $count;?>">
                          <span class="games-footer-icon icon icon-0-notifications"></span>
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                        <div id="notification-count" data-count="0">
                          <span class="games-footer-icon icon icon-00-notifications"></span>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
        <?php endif; ?>
        <?php licHtml('footer_menu', null, false) ?>
      </ul>
      <br clear="all" />
      <div id="gfooter-result">
      </div>
      </div>

        <?php loadJs("/phive/js/nanoScroller/jquery.nanoscroller.js") ?>
      <?php if(isLogged() && phive('UserHandler')->getSetting('has_notifications') === true): ?>
        <div id="notifications">
        </div>
        <div id="notifications-list" class="notifications-list">
          <div class="notification-x" onclick="hideNotificationList()"><span class="icon icon-vs-close"></span></div>
          <div class="notifications-list-top">
            <?php et('notifications') ?>
          </div>
          <div id="notifications-nano" class="nano" style="height:<?php echo $this->not_height ?>px;">
            <div id="notifications-nano-content" class="nano-content">
            </div>
          </div>
          <div class="notifications-footer">
            <div class="left">
              <?php accLink($_SESSION['mg_id'], 'update-account', 'turn.off.notifications') ?>
            </div>
            <div class="right">
              <?php accLink($_SESSION['mg_id'], 'notifications', 'view.more') ?>
            </div>
          </div>
        </div>

        <script>
            // this is needed to handle the complexity of nesting strings inside each other as function parameters
            // which will produce unexpected behaviours with the function parameters' string formatting
            function redirectTo(link){
                gameCloseRedirection("gotoLang(\'" + link + "\')");
            }

            function changeNotificationFooterRedirects(){
                if((typeof extSessHandler !== 'undefined')  && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
                    var turnOffNotifs = document.getElementsByClassName("left")[0].lastElementChild;
                    var viewMore = document.getElementsByClassName("right")[0].lastElementChild;

                    var tOffLink = turnOffNotifs.getAttribute("href");
                    var vMoreLink = viewMore.getAttribute("href");

                    turnOffNotifs.removeAttribute("href");
                    viewMore.removeAttribute("href");

                    turnOffNotifs.style.cursor = 'pointer';
                    viewMore.style.cursor = 'pointer';

                    turnOffNotifs.setAttribute("onclick", "redirectTo(\'" + tOffLink + "\')");
                    viewMore.setAttribute("onclick", "redirectTo(\'" + vMoreLink + ")\')");
                }
            }

            // extSessHandler was being observed to be undefined here (not loaded in time), this ensures that
            // extSessHandler becomes available for the function above to execute accordingly
            window.onload = function() {
                changeNotificationFooterRedirects();
            };
        </script>
      <?php endif ?>
  <?php }

  function renderGames($games, $assoc = false) {
    if ($assoc) {
      echo json_encode(array_values($games));
    }
    else {
      echo json_encode($games);
    }
  }

  function renderOlgGames($games){ ?>
    <ul class="slides">
      <?php foreach($games as $g): ?>
        <li onclick="playGameDepositCheckBonus('<?php echo $g['game_id'] ?>', '', '<?php echo $g['game_url'] ?>');">
          <img src="<?php echo $this->mg->carouselPic($g) ?>" title="<?php echo $g['game_name'] ?>"/>
        </li>
      <?php endforeach ?>
    </ul>
  <?php }

  function getPopular(){ // TODO check why this one is using getTaggedBy instead of getPopular from MicroGames... probably an error.
    $this->renderGames($this->mg->getTaggedByWrapper('popular'));
  }

  function getHot(){
    $this->renderGames($this->mg->getTaggedByWrapper('hot'));
  }

  function getBySubTag(){
    $games = $this->mg->getTaggedByWrapper('subtag_footer', 'desktop', $_GET['tag']); // Ex. new.cgames or featured.cgames
    $this->renderGames($games);
  }

  function getLastPlayed(){
    $games = $this->mg->getLastPlayed('flash_last_played');
    $this->renderGames($games, true);
  }

  function getFavs(){
    $this->renderGames($this->mg->getFavorites($_SESSION['mg_id']));
  }

  function printExtra(){?>
    <p>
      Game tags that will prepend .cgames, ex: new,hot:
      <input type="text" name="tags" value="<?php echo $this->tags ?>"/>
    </p>
    <p>
      Show clock (yes/no):
      <input type="text" name="show_clock" value="<?php echo $this->show_clock ?>"/>
    </p>
  <?php }
}
