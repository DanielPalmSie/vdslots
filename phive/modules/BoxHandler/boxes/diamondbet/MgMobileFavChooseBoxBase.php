<?php
require_once __DIR__.'/MgMobileGameChooseBoxBase.php';

class MgMobileFavChooseBoxBase extends MgMobileGameChooseBoxBase{

  function init()
  {
    parent::init(true);
    $arg0 = html_entity_decode($_REQUEST['arg0'] ?: $_GET['arg0'] ?: '', ENT_QUOTES | ENT_XHTML);
    $this->mode = phive('SQL')->escape($arg0, false);

    if (empty($this->mode)) {
        $this->mode = 'favs';
    }
    $sort_by      = html_entity_decode($_REQUEST['sortby'] ?: 'defaults', ENT_QUOTES|ENT_XHTML);
    $this->sortby = phive('SQL')->escape($sort_by, false);
    $this->uid    = $_SESSION['mg_id'];

    if(empty($this->uid)){
      phive('Redirect')->to(llink('/'));
    }

    $this->mg     = phive('MicroGames');
    $this->rcount = empty($_REQUEST['rcount']) ? 3 : (int)$_REQUEST['rcount'];
    $search_string = html_entity_decode($_REQUEST['search_str'] ?: 'all', ENT_QUOTES|ENT_XHTML);
    $this->search_str = phive('SQL')->escape($search_string, false);
    $this->p 		= phive('Paginator');

    if($_REQUEST['action'] == 'GetBoxHtml')
      $this->setupGames();
  }

  function setupGames(){
    $this->setCommonAjax('per_page');
    $this->setCommonAjax('search_str', $this->search_str);
    $per_page 		= empty($_SESSION['per_page']) ? $this->rcount * 6 : $_SESSION['per_page'];
    $extra = $this->search_str != "all" ? " mg.game_name LIKE '%{$this->search_str}%' " : "";

    if($this->mode == 'add' || $this->mode == 'remove'){
      $this->favids = $this->mg->favIds($this->uid);
    }

    if($this->mode == 'favs' || $this->mode == 'remove') {
      $games = $this->mg->getFavorites($this->uid, $extra, $this->sortby, 'html5');
    }

    if($this->mode == 'add'){
      $sqlExcludeGamesIds = '';
      $excludeGamesIds =  implode(',', $this->mg->favIds($this->uid));

      if(! empty($excludeGamesIds)) {
          $sqlExcludeGamesIds = " AND mg.id NOT IN($excludeGamesIds)";
      }

      $extra = $this->search_str != "all" ? " mg.active = 1 AND mg.game_name LIKE '%{$this->search_str}%' " : " mg.active = 1 ";
      $games = $this->mg->getAllGames("{$extra} $sqlExcludeGamesIds", "*", "html5", true);
    }

    $this->p->setPages(count($games), '', $per_page);
    $this->games 	= array_slice($games, $this->p->getOffset($per_page), $per_page);
    $this->games = array_chunk($this->games, $this->rcount);

  }

  function is404(){
    return false;
  }

  function isFavorite($gid){
    return in_array($gid, $this->favids) ? true : false;
  }

  function getIconClass() {
    if($this->mode == 'add'){
      return 'icon-vs-star';
    }
    if($this->mode == 'remove'){
      return 'icon-close';
    }
    return '';
  }

  function overlayClass($gid){
    if($this->mode == 'add'){
      return $this->isFavorite($gid) ? 'overlay-add-action' : 'overlay-add';
    }

    if($this->mode == 'remove'){
      return $this->isFavorite($gid) ? 'overlay-remove' : 'overlay-remove-default';
    }
  }

  function printGameList($games = array()){
    $games = empty($games) ? $this->games : $games;
    ?>
    <?php if(empty($games)): ?>
      <div class="pad10">
        <?php et('no.favorites.yet') ?>
      </div>
    <?php else: ?>
      <?php foreach($games as $g_chunk): ?>
        <table class="game-tbl">
          <tr>
            <?php for($i = 0; $i < $this->rcount; $i++):
              $g = $g_chunk[$i];
            ?>
              <td style="width: <?php echo 100 / $this->rcount ?>%;">
                <?php if(!empty($g)): ?>
                  <div class="game" <?php if($this->mode == 'favs'): ?> onclick="playMobileGame('<?php echo $g['ext_game_name'];?>');" <?php endif;?> >
                    <div class="game-top">
                      <img id="image-<?php echo $g['id'] ?>" src="<?php echo $this->mg->carouselPic($g) ?>" title="<?php echo $g['game_name'] ?>" alt="<?php echo $g['game_name'] ?>" />
                      <div id="<?php echo $this->mode.'-'.$g['id'] ?>" class="overlay-marker <?php echo $this->overlayClass($g['id']) ?>">
                        <span class="icon <?php echo $this->getIconClass()?>"> </span>
                      </div>
                    </div>

                    <div class="game-text" onclick="goTo('<?php echo llink("/mobile/games/".trim($g['game_url'])."/") ?>')">
                      <?php echo $g['game_name'] ?>
                    </div>

                  </div>
                <?php endif  ?>
              </td>
            <?php endfor ?>
          </tr>
        </table>
        <br clear="all"/>
      <?php endforeach ?>
      <?php $this->printPaginator(); ?>
    <?php endif ?>
    <?php }

  function printGameSection(){
    $this->printGameList();
  }

  function favForm(){
  ?>
    <div class="fav-top-container ">
          <?php if($this->mode == 'add'): ?>
            <button class="fav-top-btn gradient-default" id="fav-mode"><?php et('my.favorites') ?></button>
          <?php elseif($this->mode == 'favs' || $this->mode == 'remove'): ?>
            <button class="fav-top-btn green-btn" id="add-mode"><?php et('add.games') ?></button>
          <?php endif ?>
          <?php if($this->mode == 'remove'): ?>
            <button class="fav-top-btn gradient-default" id="fav-mode"><?php et('my.favorites') ?></button>
          <?php elseif($this->mode == 'favs' || $this->mode == 'add'): ?>
            <button class="fav-top-btn red-btn" id="remove-mode"><?php et('remove.games') ?></button>
          <?php endif ?>
  </div>
  <div class="fav-table">
          <?php if($this->mode == 'add'): ?>
            <h3><?php et('add.games') ?></h3>
          <?php elseif($this->mode == 'remove'): ?>
            <h3><?php et('remove.games') ?></h3>
          <?php else: ?>
          <h3><?php et('my.favorites') ?></h3>
          <?php endif ?>
    </div>
    <?php $this->searchInput() ?>
  <?php }

  function printPaginator(){
  ?>
    <br clear="all"/>
        <div>
          <?php $this->p->render('goToPage') ?>
      </div>
      <script>
        function goToPage(pnr){
          showGames.call(undefined, getRcount(), 'no', '', '', "<?php echo $this->search_str?>", pnr);
        }
      </script>
  <?php }

  function js(){
    ?>
    <script>
     function toggleMobileFav(el, gid, curClass, otherClass){
       if(el.hasClass(curClass))
         el.removeClass(curClass).addClass(otherClass);
       else
         el.removeClass(otherClass).addClass(curClass);
       ajaxGetBoxHtml({func: 'toggleFav', gid: gid}, cur_lang, <?php echo $this->getId() ?>, function(ret){});
     }

     function positionOverlay(el, curClass, otherClass){
       var id = getSuffix(el.attr('id'));
       el.click(function(){
         toggleMobileFav(el, id, curClass, otherClass);
       });
     }

     function positionOverlays(){
       $("div[id^='add-']").each(function(i){
         positionOverlay($(this), "overlay-add", "overlay-add-action");
       });

       $("div[id^='remove-']").each(function(i){
         positionOverlay($(this), "overlay-remove", "overlay-remove-default");
       });
     }

     var showGames = function(num, rot, sorting, mode, search_str ='', page = 1 ){
       num = getRcount(num);

       if(empty(mode))
         mode = '<?php echo $this->mode ?>';

       if(empty(sorting))
         sorting = 'default';

       if(typeof rot == 'undefined')
         rot = 'yes';


       listGames({rcount: num, rotation: rot, arg0: mode, sortby: sorting, search_str:search_str, page: page}, function(ret){
         $("#gch-list").html(ret);
         styleGameSection(num);
         positionOverlays();
       });
     }

     addOrFunc(showGames);

    $(document).ready(function(){
      showGames.call(undefined, getRcount(), 'no', '<?php echo $this->sortby ?>');
      $('#add-mode').click(function(){
        goTo('/'+cur_lang+'/mobile/favourites/add/');
      });

      $('#remove-mode').click(function(){
        goTo('/'+cur_lang+'/mobile/favourites/remove/');
      });

      $('#fav-mode').click(function(){
        goTo('/'+cur_lang+'/mobile/favourites/');
      });

      $("#fav-sort").change(function(){
        goTo('/'+cur_lang+'/mobile/favourites/?sortby='+$(this).val());
        //showGames.call(undefined, getRcount(), 'no', $(this).val(), 'favs');
      });

      $("#search_fav").keyup(function(){
        search_str = $(this).val();
        showGames.call(undefined, getRcount(), 'no', '', '',search_str);
      });
    });
    </script>
  <?php }

  function searchInput($id = "search_fav", $alias = 'search.games'){
    $placeholder = t2($alias);
    ?>
    <div class="search-cont">
      <div>
        <?php dbInput($id, '', "text", "search-games", "placeholder='{$placeholder}'" ) ?>
      </div>
    </div>
  <?php }

  function printHTML(){
    $this->includes();
    $this->js();
    ?>
    <div class="pad10 left mobile__fav">
      <div>
          <?php $this->favForm() ?>
        <div id="gch-list"></div>
      </div>
    </div>
  <?php }

}
