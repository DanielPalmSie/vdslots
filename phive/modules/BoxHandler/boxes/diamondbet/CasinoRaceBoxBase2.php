<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class CasinoRaceBoxBase2 extends DiamondBox{

    function init(){
        $this->handlePost(array('race_id'));
        $this->stars = array('star_gold.png', 'star_silver.png', 'star_bronze.png', 'star_none.png');
        $this->uh = phive('UserHandler');
        $this->cur_uid = uid();
        $this->race = phive('Race')->getRace($this->race_id);
    }

    function printRaceAmount($sum, $race){
        if($race['race_type'] == 'spins')
            echo empty($sum) ? 0 : $sum;
        else
            efEuro(mc($sum));
    }

      // Seems like this method is called via ajax without first initializing the box?
      // $this->cur_uid doesn't exist if not reinitialized first?
    public function playPageRace()
    {
        $this->cur_uid = uid();
        $rh = phive('Race');
        $race = array_shift($rh->getActiveRaces());
        //if($_REQUEST['action'] == 'update')
        //  $new_lb = $rh->leaderBoard($race);
        // Not logged in? Then we just take the top 20.
        $limit = empty($this->cur_uid) ? 20 : '';
        $lb = $rh->leaderBoard($race, false, $limit, $this->cur_uid);
        //$lb = $rh->getMemLeaderboard($race);
        list($es, $ps) = $lb;
        list($cspot, $cprize) = $rh->curSpotPrize($es, $ps, $this->cur_uid);
        $tinfo = prettyTimeInterval(strtotime($race['end_time']) - time());

        if (!empty($this->cur_uid)) {
            $cur_entry = $rh->raceEntry($race, $this->cur_uid);
        }
        ?>
          <div class="pad-stuff-five">
              <div class="right">
                  <img src="/diamondbet/images/<?= brandedCss() ?>game_page2/refresh_gamepage.png" onclick="getRace('play')" class="pointer"/>
              </div>
              <div class="a-big"><?php et('live.casino.race') ?></div>
              <div class="header-big"><?php et('prize.pool') ?></div>
              <div class="acc-left-headline"><?php echo ciso().' '.nfCents(mc($rh->getTotalPrizePool($race)), true) ?></div>
              <div class="header-big"><?php echo t('period').': '.phive()->lcDate($race['start_time'], '%x').' - '.phive()->lcDate($race['end_time'], '%x') ?></div>
              <div class="header-big"><?php echo t('days.left').': '.$tinfo['days'] ?></div>
            <?php if (!empty($this->cur_uid)) :?>
                <span class="header-big">
                        <?php echo t("rakerace.{$race['race_type']}.place").': ' ?>
                    </span>
                <span id="cur-race-balance-<?php echo $this->cur_uid ?>" class="header-big">
                        <?php echo $cur_entry['race_balance'] ?? 0 ?>
                    </span>
                <div id="race-info" uid="<?php echo $this->cur_uid ?>" style="<?php if(empty($cspot)) echo 'display: none;' ?>">
                        <span class="header-big">
                            <?php echo t('position').' ' ?>
                        </span>
                    <span id="cur-race-position-<?php echo $this->cur_uid ?>" class="header-big">
                            <?php echo $cspot ?>
                        </span>
                    <span class="header-big">
                            <?php echo t('prize').':' ?>
                        </span>
                    <span id="cur-race-prize-<?php echo $this->cur_uid ?>" class="header-big">
                            <?php if (!empty($cprize)) {
                                echo ciso().' '.(mc($cprize) / 100);
                            } ?>
                        </span>
                    <span id="cur-race-arrow-<?php echo $this->cur_uid ?>"></span>
                </div>
            <?php endif ?>
              <br/>
              <div id="race-nano" class="nano nano-big-win">
                  <div class="nano-content nano-content-big-win gpage-race">
                    <?php $this->printRace($race, true, $lb);?>
                  </div>
              </div>
          </div>
        <?php
    }

  //The JS/ws functionality can't handle more than one active race at the same time
function printRace($race, $ajax = false, $lb = false, $limit = null){
    list($entries, $prizes) = empty($lb) ? phive('Race')->leaderBoard($race, false, $limit) : $lb;
    if(!$ajax && phive('Race')->isActive($race))
        $this->printRaceJs(true);
    $is_active = phive('Race')->isActive($race);
    $has_account_view_permission = p('account.view');
?>
    <?php if(!$ajax): ?>
        <script>
         jQuery(document).ready(function(){
             $("#race-hide-<?php echo $race['id'] ?>").click(function(){
                 getWholeRace(<?php echo $race['id'] ?>, <?php echo $this->getId() ?>);
             });

             $("#race-hideall-<?php echo $race['id'] ?>").click(function(){
                 tbl = $("#race-cont-<?php echo $race['id'] ?>").find('table');
                 tbl.find('tr').each(function(i){
	             if(i > 10){
	                 $(this).hide();
	             }
                 });
                 $(this).find('.hide-rows-viewall').html("<?php et('view.all') ?>");
                 $(this).click(function(){
                     getWholeRace(<?php echo $race['id'] ?>, <?php echo $this->getId() ?>);
                 });
             });
         });
        </script>
    <?php endif ?>

    <div id="race-cont-<?php echo $race['id'] ?>" class="<?php echo $ajax ? '' : 'pad-stuff mobile-pad-stuff' ?>">
        <?php if(!$ajax): ?>
            <?php et("race.info.html") ?>
        <?php endif ?>
        <table id="<?php if($is_active) echo 'race-tab-table' ?>" class="zebra-tbl" style="width: <?php echo $ajax ? '265px' : '100%' ?>;">

            <?php if($ajax): ?>
                <colgroup>
                    <col width="15"/>
                    <col width="100"/>
                    <col width="19"/>
                    <col width="90"/>
                    <col width="10"/>
                </colgroup>
            <?php endif ?>

            <tr class="<?php echo $ajax ? "odd" : 'zebra-header' ?>">
                <td>#</td>
                <?php if(!$ajax): ?>
                    <td></td>
                <?php endif ?>
                <td><?php et('rakerace.firstname') ?></td>
                <td><?php et("rakerace.{$race['race_type']}.place") ?></td>
                <td><?php et('rakerace.prize') ?></td>
                <td></td>
            </tr>
            <?php $i = 0; foreach($prizes as $p):
                 $e = $entries[$i];

                  if($e['race_balance'] != '' &&  $e['spot'] != 0) :
                //$pos = $i + 1;
                    $current_user = !empty($e['user_id']) ? cu($e['user_id']) : null;
                    $show_name = $current_user ? empty($current_user->getSetting('privacy-pinfo-hidename')) : true;
                    $anonymous = 'Anonymous' . base_convert($e['user_id'], 10, 9);
                ?>
                    <tr <?php if(!empty($e['user_id']) && $is_active) echo 'id="raceuser-'.$e['user_id'].'"' ?>  class="<?php echo $i % 2 == 0 ? "even" : "odd"; ?>">
                        <td class="race-position"><?php echo $e['spot'] ?></td>
                        <?php if(!$ajax): ?>
                            <td class="star">
                                <div class="star <?php echo str_replace(array('.png','star_'),'',($i < 4) ? $this->stars[$i] : $this->stars[3]) ?>"></div>
                                <img src="/diamondbet/images/<?= brandedCss() ?>stars/<?php echo ($i < 4) ? $this->stars[$i] : $this->stars[3] ?>" alt="star"/>
                            </td>
                        <?php endif ?>
                        <td class="race-fname">
                            <?php if($has_account_view_permission && !empty($e['user_id'])): ?>
                                <a href="<?php echo getUserBoLink($e['user_id']) ?>">
                                    <?php echo $e['user_id'] ?>
                                </a>
                            <?php elseif($e['user_id'] == $this->cur_uid && !empty($e['user_id'])): ?>
                                <span class="red"><?php echo ucfirst(phive()->ellipsis(strtolower($e['firstname']), 12)) ?></span>
                            <?php else: ?>
                                <?php echo !$show_name ? $anonymous  : ucfirst(phive()->ellipsis(strtolower($e['firstname']), 12)) ?>
                            <?php endif ?>
                        </td>
                        <td class="race-amount">
                            <?php
                            $this->printRaceAmount($e['race_balance'], $race);
                            ?>
                        </td>
                        <td class="race-prize"><?php echo $this->fmtRacePrize($p, $ajax) ?></td>
                        <td class="race-arrow"></td>
                    </tr>
                <?php endif; ?>
            <?php $i++; endforeach; ?>
        </table>

        <?php if(!$ajax && !empty($limit)): ?>
            <div id="race-hide-<?php echo $race['id'] ?>">
                <div class="hide-rows-viewall"><?php et('view.all') ?></div>
            </div>
        <?php elseif(empty($limit) && !$ajax): ?>
            <div id="race-hideall-<?php echo $race['id'] ?>">
                <div class="hide-rows-viewall"><?php et('hide.all') ?></div>
            </div>
        <?php endif ?>

    </div>
<?php }

function printWholeRace(){
    $this->printRace($this->race, false, false, null);
}

function printHTML(){
?>
    <script>
        function getWholeRace(raceId, boxId){
            ajaxGetBoxHtml({func: 'printWholeRace'}, cur_lang, boxId, function(ret){
                $("#race-cont-"+raceId).replaceWith(ret);
            });
        }
    </script>
    <?php
    $this->printRace($this->race, false, false, 10);
}

function printExtra(){ ?>
    <p>
        Show race by id:
        <?php dbInput('race_id', $this->race_id) ?>
    </p>
<?php }

}
