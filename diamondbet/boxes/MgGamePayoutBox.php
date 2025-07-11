<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/MgGameChooseBoxBase.php';
require_once __DIR__.'/../../phive/modules/Former/FormerCommon.php';
class MgGamePayoutBox extends MgGameChooseBoxBase{

    /**
     * @var array
     */
    public array $order_by;
    /**
     * @var array
     */
    public array $payout_type;

    /**
     * @var array
     */
    public array $per_page;
  function init(){
    $this->handlePost(
      array('cron_h'),
      array("cron_h" => "4"));

    if(date('G') < $this->cron_h)
      $this->cron_day = phive()->yesterday();
    else
      $this->cron_day = phive()->today();
    $this->setupGames();

    $this->order_by = array(
        'bs DESC' 		=> t('most.played'),
        'bs ASC' 		=> t('least.played'),
        'payout_ratio DESC'     => t('highest.payouts'),
        'payout_ratio ASC' 	=> t('lowest.payouts')
    );

    $this->payout_type = array(
        'actual' => t('actual'),
        'theoretical' => t('theoretical')
    );

    $this->per_page = array(10 => 10, 25 => 25, 50 => 50, 100 => 100, 1000 => t('all') );
  }

  function setSorting(){
    $this->setCommonAjax('payment_sorting');
  }

  /*
  function setSearch(){
    $this->setCommonAjax('search_payout');
  }
  */

  function setupGames(){
    $this->mg		= phive("MicroGames");
    $this->p 		= phive('Paginator');
    $this->setCommonAjax('per_page');
    $per_page 		= empty($_SESSION['per_page']) ? 10 : $_SESSION['per_page'];
    $this->th = $th	= 1000;

    $this->setSorting();

    $this->setCommonAjax('payout_type');

    if($_SESSION['payout_type'] == 'theoretical')
      $this->payment_sorting = str_replace('payout_ratio', 'rtp', $this->payment_sorting);
    if($_SESSION['payout_type'] == 'actual')
      $this->payment_sorting = str_replace('rtp', 'payout_ratio', $this->payment_sorting);

    if(empty($_REQUEST['func']))
      $_SESSION['period'] = '';
    else if(isset($_REQUEST['period']) && empty($_REQUEST['period']))
      $_SESSION['period'] = '';
    else
      $this->setCommonAjax('period');

    if(!empty($_REQUEST['search_payout'])){
      $this->where_extra 	= " AND mg.game_name LIKE '%{$_REQUEST['search_payout']}%' "; /*SQLi*/
    }else{
      $this->setSubTag();
    }

    $this->tag_id	= empty($this->subtag) ? '' : $this->mg->getTagIdFromAlias($this->subtag);
    $this->ym		= empty($this->period) ? '' : $this->period;

    $payment_ratios = $this->mg->getByPaymentRatio($this->ym, $this->payment_sorting, 'flash', $this->tag_id, $this->where_extra);

    $games = array_filter($payment_ratios, function($el) use ($th) {
        return $el['bs'] >= $th;
    });

    $this->p->setPages(count($games), '', $per_page);
    $this->games = array_slice($games, $this->p->getOffset($per_page), $per_page);
    $this->str_tag = $this->tag = 'all';
    $this->setupSubTags();
  }

  function getArrow($g){
    if($g['bs'] < $this->th)
      return 'yellow_right_arrow';

    if($g['payout_ratio'] > $g['prior_payout_ratio'])
      return 'green_up_arrow';
    else if($g['payout_ratio'] < $g['prior_payout_ratio'])
      return 'red_down_arrow';
    else
      return 'green_up_arrow';
  }

  function getPayoutRatio($g){
    if($_SESSION['payout_type'] == 'theoretical')
      return number_format($g['rtp'] * 100, 1).'%';
    else
      return $g['bs'] < $this->th ? '' : number_format($g['payout_ratio'] * 100, 1).'%';
  }

  function printGameList(){
    if(empty($this->games))
      et('game.payout.noresult.html');
    ?>
    <?php foreach($this->games as $g):
        $payout_ratio = $this->getPayoutRatio($g);
        if(empty($payout_ratio))
          continue;
    ?>
      <div class="game-payout-row">

          <?php
          if(phive()->isMobile()) {
              $this->printGamePayoutRowMobile($g);
          } else {
              $this->printGamePayoutRowDesktop($g);
          }
          ?>

      </div>
    <?php endforeach ?>
    <div class="clear"></div>
    <div>
      <?php
      if(phive()->isMobile()) {
          $this->printViewMore();
      } else {
          $this->p->render('goToPage');
      }
      ?>
    </div>
  <?php }

    function printGamePayoutRowMobile($g)
    {
        $old_design = phive()->getSetting('old_design');
        ?>
        <div class="game-payout-first">
            <img src="<?php echo $this->mg->carouselPic($g) ?>" title="<?php echo $g['game_name'] ?>" alt="<?php echo $g['game_name'] ?>" />
        </div>
        <div class="game-payout-middle">
            <span class="headline-default-m"><?php echo $g['game_name'] ?></span>
            <?php
            btnDefaultS(t('play.now'), '', "playGameDepositCheckBonus('{$g['game_id']}')", '', 'mobile-play-button');
            ?>
        </div>
        <div class="game-payout-last">
            <div class="game-payout-last-left">
                <span class="header-small">
                   <?php et('payout') ?>:
                </span>
                <span class="headline-default-xl-mobile">
                  <?php echo $this->getPayoutRatio($g) ?>
                </span>
                <div class="header-small-mobile"><?php et('last.updated') ?>:</div>
                <?php if (!$old_design): ?>
                    <span class="header-small-mobile"><?= phive()->lcDate("{$this->cron_day} 0{$this->cron_h}:00:00") . ' ' . t('cur.timezone'); ?></span>
                <?php endif; ?>
            </div>
            <div class="game-payout-last-right">
                <img src="/diamondbet/images/<?= brandedCss() ?><?php echo $this->getArrow($g) ?>.png" />
                <?php if ($old_design): ?>
                    <div class="header-small-mobile">
                        <?= phive()->lcDate("{$this->cron_day} 0{$this->cron_h}:00:00") . ' ' . t('cur.timezone'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="clear"></div>
        <?php
    }

    function printGamePayoutRowDesktop($g)
    {
        $old_design = phive()->getSetting('old_design');
        ?>
        <table>
          <tr>
            <td class="game-payout-first">
              <img src="<?php echo $this->mg->carouselPic($g) ?>" title="<?php echo $g['game_name'] ?>" alt="<?php echo $g['game_name'] ?>" />
            </td>
            <td class="game-payout-middle">
              <div>
                <span class="headline-default-m"><?php echo $g['game_name'] ?></span>
                <br/>
                <br/>
                <?php
                btnDefaultXL(t('play.now'), '', "playGameDepositCheckBonus('{$g['game_id']}')", '');
                ?>
              </div>
            </td>
            <td class="game-payout-last">
                <div class="game-payout-last-container">
                    <div class="game-payout-last-info">
                        <span class="header-small">
                            <?php et('payout') ?>:
                        </span>
                        <span class="headline-default-xl">
                          <?php echo $this->getPayoutRatio($g) ?>
                        </span>
                        <div class="header-small__last-updated">
                            <?php et('last.updated'); ?>:
                            <?php if (!$old_design): ?>
                                <span><?= phive()->lcDate("{$this->cron_day} 0{$this->cron_h}:00:00") . ' ' . t('cur.timezone'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="game-payout-last-arrow">
                        <img src="/diamondbet/images/<?= brandedCss() ?><?php echo $this->getArrow($g) ?>.png" />
                        <?php if ($old_design): ?>
                            <div class="header-small__last-updated">
                                <?= phive()->lcDate("{$this->cron_day} 0{$this->cron_h}:00:00") . ' ' . t('cur.timezone'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
          </tr>
        </table>
        <?php
    }

  function printViewMore()
  {
      if(empty($this->p->cur_page) || $this->p->cur_page + 1 == $this->p->total_page_count) {
          return '';
      }

      $next_page = $this->p->cur_page + 1;

      ?>
    <div id="game-payouts-view-more" class="view-more-mobile" onclick="goToPage(<?php echo $next_page; ?>)">
        <?php et('view.more'); ?>
    </div>
      <?php
  }

  function js(){ ?>
    <script>

      // When using the filters, we need to replace the list, not append it
      function listGames(params, append){
        params.func = 'printGameList';
        ajaxGetBoxHtml(params, cur_lang, <?php echo $this->getId() ?>, function(ret){
            if(append) {
                // remove View More button before appending the list
                $("#game-payouts-view-more").remove();

                $(ret).hide().appendTo("#gch-list").fadeIn(1000);
            } else {
                $("#gch-list").html(ret);
            }
        });
      }

      function goToPage(pnr){
        <?php if(phive()->isMobile()): ?>
            var append = true;
        <?php else: ?>
            var append = false;
        <?php endif; ?>

        listGames({page: pnr}, append);
      }

      function filterBySubTag(sub){
        listGames({subtag: sub});
      }

      function descAsc(val){
        listGames({payment_sorting: val});
      }

      function filterBySubTag(sub){
        listGames({subtag: sub});
      }

      $(document).ready(function(){
        <?php $this->setupSubTagDropDown() ?>

        $("#search_payout").click(function(){
          $(this).val('');
        });

        $("#search_payout").keydown(function(event){
          var cur = $(this);
          var val = cur.val().length >= 2 ? cur.val() : '';
          listGames({search_payout: val});
        });

        $("#desc_asc").selectbox({
          <?php if(phive()->isMobile()): ?>
          classHolder: 'sbSmallHolder',
          classOptions: 'sbSmallOptions',
          <?php else: ?>
          classHolder: 'sbMediumHolder',
          classOptions: 'sbMediumOptions',
          <?php endif; ?>
          onChange: function(val, inst) {
            descAsc(val)
          }
        });

        $("#payout_type").selectbox({
          <?php if(!phive()->isMobile()): ?>
          classHolder: 'sbMediumHolder',
          classOptions: 'sbMediumOptions',
          <?php endif; ?>
          onChange: function(val, inst) {
            listGames({payout_type: val});
          }
        });

        $("#per_page").selectbox({
          classHolder: 'sbSmallHolder',
          classOptions: 'sbSmallOptions',
          onChange: function(val, inst) {
            listGames({per_page: val})
          }
        });

        $("#period").selectbox({
         classHolder: 'sbSmallHolder',
         classOptions: 'sbSmallOptions',
         onChange: function(val, inst) {
            listGames({period: val})
          }
        });
      });
    </script>
  <?php }

  function printCSS(){
    if(phive()->isMobile()) {
        loadCss("/diamondbet/css/" . brandedCss() . "game-payouts-mobile.css");
        if(isIpad()) {
            loadCss("/diamondbet/css/game-payouts-ipad.css");
        }
    } else {
        loadCss("/diamondbet/css/" . brandedCss() . "game-payouts.css");
    }
  }

  function getMonths(){
      $f = new FormerCommon();
      return array_merge(array('' => t('all.time')), array_reverse($f->getYearMonths(date('Y') - 1), true));
  }
  function renderDescAscSelect() {
        dbSelect('desc_asc', array(
            'bs DESC' => t('most.played'),
            'bs ASC' => t('least.played'),
            'payout_ratio DESC' => t('highest.payouts'),
            'payout_ratio ASC' => t('lowest.payouts')
        ));
    }

  function printHTML(){
    $this->includes();
    $this->js();
    $f = new FormerCommon();
    //$months = array('' => 'all.time', date('Y-m') => 'current.month', phive()->lastMonth() => 'last.month');
    $months = $this->getMonths();

    ?>
    <div class="frame-block">
      <div class="frame-holder">

          <?php if(phive()->isMobile()): ?>
              <h2 class="headline-default-m"><?php et('hottest-games'); ?></h2>
          <?php endif; ?>

        <div class="gch-left">
          <?php $this->searchInput('search_payout', 'search.games') ?>
        </div>

        <div class="gch-right">
          <div class="gch-right-top gch-payout-top2">

            <!--
            <div class="gch-txt">
              <?php et('sort.by') ?>:
            </div>
            -->

            <div class="gch-item">
              <?php dbSelect('payout_type', $this->payout_type, $_SESSION['payout_type']) ?>
            </div>

            <div class="gch-item">
              <?php dbSelect('subtag', $this->subsel, $this->subtag, array('all', t('all.'.$this->str_tag)))?>
            </div>

            <!--
            <div class="gch-txt">
              <?php et('payout.type') ?>:
            </div>
            -->


            <!--
            <div class="gch-txt">
              <?php et('period') ?>:
            </div>
            -->

            <!--
            <div class="gch-txt">
              <?php et('list.by') ?>:
            </div>
            -->

            <div class="gch-item">
              <?php dbSelect('desc_asc', $this->order_by);
              ?>
            </div>

            <div class="gch-item">
              <?php dbSelect('per_page', $this->per_page) ?>
            </div>

            <div class="gch-item">
              <?php dbSelect('period', $months) ?>
            </div>

          </div>
        </div>

        <br clear="all" />

        <div id="gch-list">
          <?php $this->printGameList() ?>
        </div>

      </div>
    </div>
  <?php }

  function printExtra(){?>
    <p>
      Cron job hour (1-24):
      <input type="text" name="cron_h" value="<?php echo $this->cron_h ?>"/>
    </p>
  <?php }

}
