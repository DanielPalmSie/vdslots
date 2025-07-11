<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

use Laraphive\Domain\Content\DataTransferObjects\Requests\EventsRequestData;

class ActivityFeedBoxBase extends DiamondBox{

    /** @var string $excluded_countries Controls whether we want to hide it for a country or countries*/
    public string $excluded_countries;

  function init(){
    $this->handlePost(
            ['show_feed_rows', 'show_btn', 'show_news_rows', 'excluded_countries'],
            ['show_feed_rows' => 2, 'show_btn' => 'yes', 'show_news_rows' => 1, 'excluded_countries' => ' ']
    );
    $this->can_edit 	= p("News.edit");
    $this->can_delete 	= p("News.delete");
    if(hasMp()){
      $this->th          = phive('Tournament');
      $this->mp_rows     = $this->show_news_rows * 2;
      $tmp               = $this->th->getListing([], "t.status NOT IN('finished', 'cancelled', 'in.progress')");
      if(!empty($tmp)){
        $res               = array();
        $res[]             = $tmp[0];
        unset($tmp[0]);
        for($i = 0; $i < count($tmp); $i++){
          if($tmp[$i]['start_format'] == 'mtt'){
            $res[] = $tmp[$i];
            unset($tmp[$i]);
            break;
          }
        }
        $res = array_merge($res, $tmp);
        $this->tournaments = array_slice($res, 0, $this->mp_rows);
      }else{
        //$this->news = phive('LimitedNewsHandler')->getLatestTopList(cLang(), "news", "APPROVED", "", $this->show_news_rows);

      }
    }

    $this->setup();
  }

    public function hideByCountry()
    {
        if (in_array(licJur(), explode(' ', $this->excluded_countries))) {
            return true;
        }
        return false;
    }

  function setup(){
    $this->height_modifier = $this->show_feed_rows > 2 ? 18 : 3;
  }

  function drawFeedItem($item){ ?>
    <div class="act-feed-item" style="display: none;">
      <div class="act-feed-img">
        <img src="<?php phive('UserHandler')->eventImage($item)  ?>" />
      </div>
        <div class="act-feed-headline">
            <?php echo phive('UserHandler')->fixFname($item->fname) ?>
        </div>
        <span class="act-feed-content"><?php echo phive('UserHandler')->eventString($item) ?></span>
    </div>
  <?php }

  function getEvents(){
    addCacheHeaders("cache60");
    $this->fetch_num = (int) phive('Config')->getValue('activity-feed', 'num-events');
    $eventRequestAr = ['start'=>0, 'offset'=>$this->fetch_num];

    foreach(phive('UserHandler')->getEvents(EventsRequestData::fromArray($eventRequestAr)) as $ev)
      $this->drawFeedItem($ev);
  }

  function printCSS(){
    loadCss("/phive/js/nanoScroller/nanoscroller.css");
  }

  function printJS(){ ?>
    <?php loadJs("/phive/js/nanoScroller/jquery.nanoscroller.js") ?>
    <script>
     var afIntv;
     var negTop = '-76px';
     function moveEvent(){
       if (typeof messageProcessor !== "undefined" && typeof messageProcessor.pauseNotifications === "function" && messageProcessor.pauseNotifications()) {
           return;
       }
       var el = $("#event-q div").first();
       if(el.length == 0 && $("#feed-items").children().length > 0){
         getEvents();
       }else{
         el.css({top: negTop}).prependTo("#feed-items").slideDown(1000);
         $("#af-nano").nanoScroller();
       }
     }

     function getEvents(height) {
         clearInterval(afIntv);
         if ($('#feed-items').length == 0)
             $("#activity-feed").html('<div id="af-nano" class="nano nano-big-win" style="height:' + height + 'px;"><div id="feed-items" class="nano-content nano-content-big-win"></div></div> <div id="event-q" style="display: none;"></div>');
         ajaxGetBoxHtml({func: 'getEvents'}, cur_lang, 'ActivityFeedBox', function (ret) {
             if (!ret.length){
                 //fallback to EN events
                 ajaxGetBoxHtml({func: 'getEvents'}, default_lang, 'ActivityFeedBox', function (ret) {
                     displayEvents(ret);
                 });
             } else {
                 displayEvents(ret);
             }
         });
     }

     function redirectFromFeed(link){
         gameCloseRedirection(decodeURIComponent(link));
     }

     function displayEvents(ret){
         $("#event-q").html(ret);
         var fItems = $("#feed-items").children();
         if (fItems.length == 0) {
             for (i = 0; i < <?php echo $this->show_feed_rows ?>; i++)
                 $("#event-q div").first().show().prependTo("#feed-items");
             $("#event-q div").first().show().prependTo("#feed-items").css({
                 top: negTop,
                 "margin-bottom": negTop
             });
         } else {
             fItems.slice(<?php echo $this->show_feed_rows + 1 ?>).remove();
         }
         $("#af-nano").nanoScroller();
         afIntv = setInterval(function () {
             moveEvent();
         }, 4000);
         
         // here we modify the activity feed links to trigger the game session closed popup for Spanish users
         if((typeof extSessHandler !== 'undefined')  && (typeof mpUserId === 'undefined') && (cur_country === 'ES')){
             var feedItems = $(".act-feed-content");
             feedItems.each(function() {
                 var fItem = $(this);
                 
                 if(fItem.children().last().is("[onclick]")){
                     var fItemLink = fItem.children().last().attr("onclick");
                     if(!fItemLink.includes("redirectFromFeed")) {  //this helps us set the new onclick function only once
                         fItem.children().last().attr("onclick", "redirectFromFeed(\'" + fItemLink.replace(/'/g, "%27") + "\')");
                         return;
                     }
                 }

                 // this handles feed items that contain win messages as they are formatted differently
                 if(fItem.children().is("[onclick]")){
                     var reqChildLink = fItem.children().first().attr("onclick");
                     if(!reqChildLink.includes("redirectFromFeed")){  //this helps us set the new onclick function only once
                         fItem.children().first().attr("onclick", "redirectFromFeed(\'" + reqChildLink.replace(/'/g, "%27") + "\')");
                     }
                 }
             });
         }
     }
    </script>
  <?php }

  function printNews(){
    $this->blist = new NewsListBox();
    $this->blist->cur_lang = cLang();
    foreach($this->news as $n)
      $this->blist->printRow($n);
  }

  function printBigWinNews(){
    foreach($this->news as $n): ?>
    <div class="list-news-item big-win-item">
      <div class="img-left">
        <img src="<?php echo phive('MicroGames')->carouselPic($n->getUrlName()) ?>" style="width: 150px; height: 133px" />
      </div>
      <div class="list-news-content big-win-wrapper">
        <h3 class="big_headline">
          <?php echo $n->getHeadline() ?>
        </h3>
        <div class="big-win-content">
          <?php echo $n->getContent() ?>
        </div>
        <?php btnDefaultL(t('play.game.now'), '', "playGameDepositCheckBonus('{$n->getUrlName()}')", 150) ?>
        &nbsp;
        <?php if($this->show_btn == 'yes') btnCancelL(t('more.winners'), llink('/winners/'), '', 150) ?>
      </div>
    </div>
    <?php if($this->can_edit || $this->can_delete): ?>
      <p class="author">
        <?php if ($this->can_edit): ?>
          <a href="<?php echo llink("/news/editnews/".$n->getId()); ?>/"><?php echo t("newsfull.edit"); ?></a>
        <?php endif ?>
        <?php if ($this->can_delete): ?>
          <a href="/news/deletenews/<?php echo $n->getId(); ?>/delete" onclick="return confirm_delete()">
            <?php echo t("newsfull.delete"); ?>
          </a>
        <?php endif ?>
      </p>
    <?php endif ?>
    <?php if($this->show_news_rows > 1) echo '<br/>' ?>
  <?php
  endforeach;
  }

  function printTournaments(){
    $me = $this;
  ?>
    <div class="mp-activity-container">
      <h3><?php et('mp.activity.headline') ?> - <span class="mp-activity-view-link" onclick="showMpBox('/tournament/')"><?php et('mp.view.tournaments') ?></span></h3>
      <?php foreach($this->tournaments as $t): ?>
        <div class="mp-activity-t-row">
          <img class="mp-activity-t-img" src="<?php echo phive('MicroGames')->carouselPic($t) ?>"/>
          <div class="mp-activity-t-right">
            <div class="mp-activity-t-name"><?php echo $t['tournament_name'] ?></div>
            <?php if($t['start_format'] != 'sng'): ?>
              <span><?php echo t('mp.start').': '.$this->th->getStartOrStatus($t, false) ?></span>
            <?php endif ?>
            <span><?php echo t('mp.bet').': '.$this->th->fmSym($t['min_bet']) ?></span>
            <?php $this->th->prSpinInfo($t, function() use ($t, $me){ ?> <span><?php echo t('mp.spins').': '.$me->th->getXspinInfo($t, 'tot_spins') ?></span>  <?php }) ?>
            <span><?php echo t('mp.buyin').': '.$this->th->getBuyIn($t) ?></span>
            <span><?php echo t('mp.enrolled').': '.$this->th->displayRegs($t) ?></span>
            <div class="mp-activity-btn" onclick="toLobbyWin('<?php echo $t['id'] ?>')"></div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
    <?php
  }

  function printHTML(){
    if ($this->hideByCountry() === true) {
        return;
    }
    $this->printJS();
  ?>
    <script>
     $(document).ready(function(){
       getEvents();
     });
    </script>
    <div class="frame-block">
      <div class="frame-holder pad-top-bottom-10">
        <table class="v-align-top af-main-page">
          <tr>
            <td>
              <div class="front-page-news-container">
                <?php if(empty($this->tournaments)): ?>
                  <?php //hasMp() ? $this->printNews() : $this->printBigWinNews() ?>
                  <?php $this->printBigWinNews() ?>
                <?php else: ?>
                  <?php $this->printTournaments() ?>
                <?php endif ?>
              </div>
            </td>
            <td>
              <?php $this->printRight() ?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  <?php }

  function printGamePage(){ ?>
    <div id="af-nano" class="nano nano-big-win">
      <div id="feed-items" class="nano-content nano-content-big-win">
      </div>
    </div>
    <div id="event-q" style="display: none;"></div>
  <?php }


  function printRight(){ ?>
    <div id="af-nano" class="nano nano-big-win" style="height: <?php echo ($this->show_feed_rows * 76) - $this->height_modifier ?>px;">
      <div id="feed-items" class="nano-content nano-content-big-win">
      </div>
    </div>
    <div id="event-q" style="display: none;"></div>
  <?php }

  function printExtra(){ ?>
    <p>
      Show # in feed:
      <input type="text" name="show_feed_rows" value="<?php echo $this->show_feed_rows ?>"/>
    </p>
    <p>
      Show more winners button (yes/no):
      <input type="text" name="show_btn" value="<?php echo $this->show_btn ?>"/>
    </p>
    <p>
      Show # news:
      <input type="text" name="show_news_rows" value="<?php echo $this->show_news_rows ?>"/>
    </p>
    <p>
      Excluded countries:
      <input type="text" name="excluded_countries" value="<?php echo $this->excluded_countries ?>"/>
    </p>
  <?php }
}
