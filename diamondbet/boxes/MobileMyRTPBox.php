<?php
require_once __DIR__.'/AccountBox.php';

class MobileMyRTPBox extends AccountBox{

    function rtpSearch()
    {
        $res = phQget($cache_key = $this->getRtpCacheKey());
        if(!empty($res)) {
            die($this->rtpReturn($res));
        }
        $vars = $this->rtpSetup();
        $per_page = 5;
        $this->cur_user = empty($this->cur_user) ? cu() : $this->cur_user;
        //print_r($this->cur_user);
        $type = $_REQUEST['type'];

        //print_r($vars);

        $dt_from       = phive('Localizer')->getStampFromIntl("{$vars['dt_from']}", 'object');
        $dt_to         = phive('Localizer')->getStampFromIntl("{$vars['dt_to']}", 'object');
        $date_from_to  = [date_format($dt_from, "Y-m-d {$vars['time_from']}:00"), date_format($dt_to, "Y-m-d {$vars['time_to']}:00")];

        /** @var MicroGames $mg */
        $mg = phive('MicroGames');

        //phive()->dumpTbl('date-from-to', $date_from_to);

        $result = [];
        $order  = empty($vars['order']) ? 'DESC' : 'ASC';
        switch($type){
            case 'rtp_hi':
                $result = $mg->rtpGetListByUser($this->cur_user, $vars['game'], $date_from_to, 'DESC', [(int) $_REQUEST['from'], $per_page]);
                break;
            case 'rtp_low':
                $result = $mg->rtpGetListByUser($this->cur_user, $vars['game'], $date_from_to, 'ASC', [(int) $_REQUEST['from'], $per_page]);
                break;
            case 'rtp_all':
                $result = $mg->rtpGetListAll($this->cur_user, $vars['game'], $date_from_to, $order, [(int) $_REQUEST['from'], $per_page]);
                break;
            case 'rtp_game':
                $result = $mg->rtpGetGameSessions($this->cur_user, (int) $_REQUEST['game_id'], $date_from_to, $order, [(int) $_REQUEST['from'], $per_page]);
                break;
            case 'bets_wins':
                $result = $mg->rtpGetBetsWins($this->cur_user, $vars['session'], [(int) $_REQUEST['from'], $per_page]);
                break;
        }

        $output = $mg->formatRtpResult($result, $this->cur_user);

        phQset($cache_key, $output, 15);

        echo $this->rtpReturn($output);
    }

    function rtpFormTable($id, $title = ''){
        ?>
        <div class="simple-box pad10 margin-ten-top left">
            <div class="rtp-score-table rtp-score-table--half">
                <h2><?php et(empty($title) ? "account.$id.headline" : $title )?></h2>
                <table class="rtp-table" id="<?php echo $id ?>_table">
                    <?php if($id !== 'rtp_all') : ?>
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php echo t('date') . ' / ' . t('time') ?></th>
                            <th><?php et('rtp') ?></th>
                        </tr>
                    </thead>
                    <?php endif ?>
                    <tbody></tbody>
                </table>
                <a href="#" onclick="rtp.searchMobile('<?php echo $id ?>', false, 1);return false;" class="rtp-more"><?php et('view.more') ?></a>
            </div>
        </div>
        <?php
    }

    function rtpBackLink(){
        //TODO FIX AFTER CHANGE PAGE URL
        $base_link               = llink('/mobile/rtp/'.cuAttr('id').'/rtp/');
        $arr                     = [];
        $arr[0]['alias']         = 'my.rtp';
        $arr[0]['replacements']  = [];
        $arr[0]['link']          = $base_link;

        if(!empty($this->game)){
            $arr[1]['link']         = $base_link.'?game='.$this->game['id'];
            $arr[1]['replacements'] = [$this->game['game_name']];
            $arr[1]['alias']        = 'my.rtp.game.link';
        }

        if(!empty($_GET['session'])){
            $arr[2]['link']         = $base_link.'?game='.$this->game['id']."&session={$_GET['session']}";
            $arr[2]['replacements'] = [$_GET['session']];
            $arr[2]['alias']        = 'my.rtp.session.link';
        }

        ?>
        <div class="rtp-crumbs">
            <?php $i = 0; foreach($arr as $crumb): ?>
                <?php if($i > 0): ?>
                    &nbsp;&nbsp;&raquo;&nbsp;&nbsp;
                <?php endif ?>
                <a class="rtp-crumb-link" href="<?php echo $crumb['link'] ?>">
                    <?php et2($crumb['alias'], $crumb['replacements']) ?>
                </a>
            <?php $i++; endforeach; ?>
        </div>
        <?php
    }

    function rtp() {
        $this->rtpSetup();
        loadCss("/diamondbet/css/" . brandedCss() . "rtp.css");
        loadCss("/diamondbet/css/" . brandedCss() . "mobile-rtp.css");
        loadJs('/phive/js/account_rtp_page.js');
        $new_version_jquery_ui = phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';
        ?>
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $new_version_jquery_ui ?>jquery-ui.min.css">
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
        <?php
        $mg      = phive('MicroGames');
        $game_id = !empty($_REQUEST['game']) ? (int) $_REQUEST['game'] : 0;
        $game    = $mg->getById($game_id);

        ?>
        <script src="/phive/js/jquery.flot.js"></script>
        <script src="/phive/js/jquery.flot.time.js"></script>
        <script>
            rtp.initOptions({
                game: '<?php echo $game['ext_game_name'] ?>',
                rtp: '<?php echo $game['payout_percent'] * 100 ?>',
                game_id: <?php echo (int) $_REQUEST['game'] ?>,
                thumb_dir: '<?php echo phive('Filer')->getSetting('UPLOAD_PATH_URI').'/thumbs/' ?>',
                gobj: <?php echo json_encode($game) ?>,
                userId: <?php echo $this->cur_user->getId() ?>
            });
        </script>

        <?php

        if ($game_id)
             return $this->printRTPGame($game_id);

        return  $this->printRTPGeneral();


    }

    public function printRTPGeneral(){
        ?>
            <!-- <script language="JavaScript">
             $(document).ready(function() {
                 $('input.rtp-date').datepicker({
                     showButtonPanel: false,
                     dateFormat: '<?php echo strtolower(phive('Localizer')->getIntlDtFormat()) ?>'
                 });
                 rtp.searchMobile('rtp_hi', true);
                 rtp.searchMobile('rtp_low', true);
                 rtp.searchMobile('rtp_all', true);
             } );
            </script> -->
            <div class="rtp-boxes-container">
                <?php $this->rtpSummary() ?>
                <?php $this->rtpAllSearchForm() ?>
                <?php $this->rtpFormTable('rtp_hi') ?>
                <?php $this->rtpFormTable('rtp_low') ?>
                <?php $this->rtpFormTable('rtp_all', 'account.highest-rtp.headline') ?>
            </div>
            <?php
    }

    public function printRTPGame($game_id){
        $mg      = phive('MicroGames');
        $info_list    = $mg->featuresList($game_id, 'info');
        $feature_list = $mg->featuresList($game_id, 'feature');
        ?>
        <script>
            var getSession = '<?php echo $_GET['session'] ?>';
            mobile_graph.initOptions({
                session: getSession
            });
            $(document).ready(function () {
                $('input.rtp-date').datepicker({
                    showButtonPanel: false,
                    dateFormat: '<?php echo strtolower(phive('Localizer')->getIntlDtFormat()) ?>'
                });
                rtp.searchMobile( empty(getSession) ? 'rtp_game' : 'bets_wins' );
                rtp.searchMobileGraph( empty(getSession) ? 'week' : 'session' );
            });
        </script>
        <?php
            if(empty($_GET['session']))
                $this->rtpGameSearchForm();
        ?>
        <div class="rtp-graph-box margin-ten-top margin-five-bottom left">
            <div class="rtp-score-table rtp-score-table--half">
                <div class="rtp-g-wrapper">
                    <div class="rtp-g-graph">
                        <?php if(!empty($_GET['session'])) : ?>
                            <?php $this->rtpBackLink();?>
                            <form id="rpt-filters-form">
                                <input type="hidden" name="session" value="<?php echo $_GET['session'] ?>">
                            </form>
                        <?php endif ?>
                        <div class="graph-info pad10">
                            <?php echo !empty($_GET['session']) ? t('amount') : 'RTP %' ?>
                        </div>
                        <!-- Graph HTML -->
                        <div id="graph-wrapper">
                            <div class="graph-container">
                                <div id="graph-lines"></div>
                            </div>
                        </div>
                        <!-- end Graph HTML -->
                    </div>
                </div>
            </div>
        </div>

        <div class="rtp-g">
            <div class="rtp-g-item">
                <div class="rtp-g-item-avatar">
                    <img src="/diamondbet/images/<?= brandedCss() ?>hand.png" alt="">
                </div>
                <h3 id="rtp_range"></h3>
                <h6><?php et('my.rtp') ?></h6>
            </div>
            <div class="rtp-g-item">
                <div class="rtp-g-item-avatar">
                    <img src="/diamondbet/images/<?= brandedCss() ?>star.png" alt="">
                </div>
                <h3 id="hitrate-stats"></h3>
                <h6><?php et('hit.rate') ?></h6>
            </div>
            <div class="rtp-g-item">
                <div class="rtp-g-item-avatar">
                    <img src="/diamondbet/images/<?= brandedCss() ?>tspins_big.png" alt="">
                </div>
                <h3 id="totalspins"></h3>
                <h6><?php et('total.spins') ?></h6>
            </div>
            <div class="rtp-g-item">
                <div class="rtp-g-item-avatar">
                    <img src="/diamondbet/images/<?= brandedCss() ?>casino_rtp.png" alt="">
                </div>
                <h3 id="average_bet_ammount"></h3>
                <h6><?php et('bet.average') ?></h6>
            </div>
        </div>

        <div class="rtp-g-big margin-ten-bottom margin-five-top">
            <div class="rtp-g-item-big">
                <div class="rtp-g-item-avatar-big"><img src="/diamondbet/images/<?= brandedCss() ?>spsession_big.png" alt=""></div>
                <div class="rtp-g-item-text">
                    <h6><?php et('spins.per.session') ?></h6>
                    <div class="rpt-g-values">
                        <div class="rpt-g-values_item">
                            <strong>
                                <span id="avgtime-stats"></span>
                            </strong>
                            <h6><?php et('time') ?></h6>
                        </div>
                        <div class="rpt-g-values_item">
                            <strong>
                                <span id="avgbets-stats"></span>
                            </strong>
                            <h6><?php et('spins') ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rtp-g-big">
            <div class="rtp-g-item-big">
                <div class="rtp-g-item-avatar-big"><img src="/diamondbet/images/<?= brandedCss() ?>bwin_big.png" alt=""></div>
                <div class="rtp-g-item-text">
                    <h6><?php et('biggest.win') ?></h6>
                    <div class="rpt-g-values">
                        <div class="rpt-g-values_item">
                            <strong>
                                <span id="biggestwin"></span>
                            </strong>
                            <h6><?php et('win') ?></h6>
                        </div>
                        <div class="rpt-g-values_item">
                            <strong>
                                <span id="biggestwin-bet"></span>
                            </strong>
                            <h6><?php et('bet') ?></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if(p('rtp.game.info')): ?>
        <div class="simple-box pad10 margin-ten-top left">
            <div class="rtp-subtotal">
                <div class="rtp-subtotal-tabs">
                    <a href="#" onclick="rtpTabs.set(1);return false;" id="rtptabbtn1" class="overview active"><?php et('overview') ?></a>
                    <?php if(!empty($feature_list)): ?>
                    <a href="#" onclick="rtpTabs.set(2);return false;" id="rtptabbtn2" class="features"><?php et('features') ?></a>
                    <?php endif ?>
                    <?php if(!empty($info_list)): ?>
                    <a href="#" onclick="rtpTabs.set(3);return false;" id="rtptabbtn3" class="moreinfo"><?php et('more.info') ?></a>
                    <?php endif ?>
                    <a href="/play/<?php echo $game['game_url']?>" class="playnow"><?php et('play.now') ?></a>
                </div>
                <div class="rtp_tabs">
                    <div id="rtptab1">
                        <table>
                            <tbody>
                                <tr>
                                    <td><strong><?php et('pay.lines') ?>:</strong></td>
                                    <td><?php echo $game['num_lines']?></td>
                                    <td><strong><?php et('variance') ?>:</strong></td>
                                    <td><?php echo ($game['volatility'] < 4) ? 'MIN' : (($game['volatility'] >= 5 && $game['volatility'] <= 7) ? 'MID' : 'MAX')?></td>
                                    <td><strong><?php et('game.has.frb') ?>:</strong></td>
                                    <td><?php echo empty($info_list['free_spin_awarded']) ? '-' : $info_list['free_spin_awarded']['value'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>RTP:</strong></td>
                                    <td><?php echo phive()->decimal($game['payout_percent'] * 100)?>%</td>
                                    <td><strong><?php et('reel.layout') ?>:</strong></td>
                                    <td><?php echo $mg->featuresList($game_id, 'info', 'reels')[0]['value']?></td>
                                    <td><strong><?php et('reel.animation') ?>:</strong></td>
                                    <td><?php echo empty($info_list['reel_animation_type']) ? '-' : strtoupper($info_list['reel_animation_type']['value']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php if(!empty($feature_list)): ?>
                    <div id="rtptab2">
                        <table>
                            <tbody>
                            <?php
                            $game_features = $mg->featuresList($game_id, 'feature');
                            $i = 0;
                            foreach ($game_features as $feature) {
                                if ($i % 3 == 0) { ?><tr><?php }
                                ?>
                                <td><strong><?php echo t('rtp-'.$feature['name'])?></strong></td>
                                <td><?php echo strtoupper($feature['value'])?></td>
                                <?php
                                if ($i % 3 == 2) { ?></tr><?php }
                                $i++;
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif ?>

                    <?php if(!empty($info_list)): ?>
                    <div id="rtptab3">
                        <table>
                            <tbody>
                            <?php
                            $game_features = $mg->featuresList($game_id, 'info');
                            $i = 0;
                            foreach ($game_features as $feature) {
                                if ($i % 3 == 0) { ?><tr><?php }
                                ?>
                                <td><strong><?php echo t('rtp-'.$feature['name'])?></strong></td>
                                <td><?php echo strtoupper($feature['value'])?></td>
                                <?php
                                if ($i % 3 == 2) { ?></tr><?php }
                                $i++;
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; // The rtp.game.info permission ?>
                </div>
            </div>
        </div>
        <?php endif ?>

        <div class="simple-box pad10 margin-ten-top left">
            <form class="rtp-filter" id="<?php if(!empty($_GET['session'])):?>bets_wins<?php else: ?>rtp_game<?php endif; ?>">
                <?php if(!empty($_GET['session'])):?>
                    <input type="hidden" name="session" value="<?php echo $_GET['session'] ?>">
                <?php else: ?>
                    <fieldset>
                    <h2><?php $this->rtpBackLink() ?></h2>
                        <div class="rtp-filter-label right">
                            <div class="rtp-select">
                                <select name="order">
                                    <option value="0"><?php et('newest') ?></option>
                                    <option value="1"><?php et('oldest') ?></option>
                                </select>
                            </div>
                        </div>
                        <?php $this->dateTimeRow() ?>
                        <button onclick="rtp.searchMobile('rtp_game', false, false);return false;" class="rtp-btn rtp-button--search w-100-pc">
                            <?php et('search') ?>
                        </button>
                    </fieldset>
                <?php endif;?>
            </form>
            <div class="rtp-table-wrapper">
                <table class="rtp-table" id="<?php if(!empty($_GET['session'])):?>bets_wins<?php else: ?>rtp_game<?php endif; ?>_table">
                    <?php if($_GET['session']) : ?>
                    <thead>
                        <tr>
                            <th></th>
                            <th><?php echo t('date') . ' / ' . t('time') ?></th>
                            <th><?php et('type') ?></th>
                            <th><?php et('amount') ?></th>
                        </tr>
                    </thead>
                    <?php endif ?>
                    <tbody></tbody>
                </table>
                <a href="#" onclick="rtp.searchMobile('<?php if(!empty($_GET['session'])):?>bets_wins<?php else:?>rtp_game<?php endif;?>', false, 1);return false;" class="rtp-more"><?php et('view.more') ?></a>
            </div>
        </div>
        <?php

    }
    public function rtpGameSearchForm() {
        ?>
        <div class="simple-box pad10 margin-ten-top left">
            <form class="rtp-filter" id="rpt-filters-form">
                <h2><?php $this->rtpBackLink() ?></h2>
                <fieldset>
                    <?php $this->dateTimeRow() ?>
                    <div class="rtp-buttons-wrapper left">
                        <a href="#" onclick="rtp.setTypeMobile(this);return false;" data-type="day" class="rtp-btn inactive"><?php et('day') ?></a>
                        <a href="#" onclick="rtp.setTypeMobile(this);return false;" data-type="week" class="rtp-btn active"><?php et('week') ?></a>
                        <a href="#" onclick="rtp.setTypeMobile(this);return false;" data-type="month" class="rtp-btn inactive"><?php et('month') ?></a>
                    </div>
                    <button onclick="rtp.searchMobileGraph();return false;" id="graph-btn-search" class="rtp-btn rtp-button--search w-100-pc">
                        <?php et('search') ?>
                    </button>
                </fieldset>
            </form>
        </div>
        <?php
    }

    public function dateTimeRow() {
        ?>
        <div class="rtp-filter-label rtp-date-row w-100-pc">
            <div class="rtp-date-wrapper">
                <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
            </div>
            <div class="rtp-date-wrapper">
                <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
            </div>
        </div>
        <?php
    }

    public function rtpAllSearchForm() {
        ?>
        <script>
            function searchSelectedRTPBoxMobile() {
                    var selected_box = jQuery('[name="box_selected"]').val();
                    rtp.searchMobile(selected_box, true);
            };
            $(document).ready(function () {
                $('.rtp-date').datepicker({
                    showButtonPanel: false,
                    dateFormat: '<?php echo strtolower(phive('Localizer')->getIntlDtFormat()) ?>'
                });
                $('.rtp-buttons-wrapper .rtp-btn').on('click', function() {
                    $(this).toggleClass('inactive');
                    $(this).siblings().addClass('inactive');
                    var selected_box = jQuery(this).data('box_id')
                    jQuery('[name="box_selected"]').val(selected_box);
                    searchSelectedRTPBoxMobile();
                });
                rtp.searchMobile('rtp_hi', true);
                rtp.searchMobile('rtp_low', true);
                rtp.searchMobile('rtp_all', true);
            });
        </script>
        <div class="simple-box pad10 margin-ten-top left">
            <form class="rtp-filter" id="rpt-filters-form">
                <h2><?php et('my.rtp') ?></h2>
                <fieldset>
                    <div class="rtp-filter-label w-100-pc">
                        <input class="rtp-search" name="game" type="text" placeholder="<?php et('all.games') ?>"></input>
                    </div>
                    <div class="rtp-filter-label rtp-date-row w-100-pc">
                        <div class="rtp-date-wrapper">
                            <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                            <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
                        </div>
                        <div class="rtp-date-wrapper">
                            <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                            <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
                        </div>
                    </div>
                    <div class="rtp-buttons-wrapper left">
                        <input type="hidden" name="box_selected" value="rtp_hi">
                        <div class="rtp-btn left" data-box_id="rtp_hi">
                            <?php et('highest') ?>
                        </div>
                        <div class="rtp-btn inactive left" data-box_id="rtp_low">
                            <?php et('lowest') ?>
                        </div>
                        <div class="rtp-btn inactive left" data-box_id="rtp_all">
                            <?php et('all') ?>
                        </div>
                    </div>
                    <div class="rtp-filter-label w-100-pc">
                        <button type="button" onclick="searchSelectedRTPBoxMobile();" class="rtp-btn rtp-button--search w-100-pc">
                            <?php et('search') ?>
                        </button>
                    </div>
                </fieldset>
            </form>
        </div>
        <?php
    }

    public function printHTML(){
        setCur($this->cur_user);
        if($_GET['signout'] == 'true'){
            phive('UserHandler')->logout('logout');
            $this->jsRedirect('/');
            return;
        }

        if($this->canView() !== false){
            ?>
            <div class="container-acc-holder">
            <div class="boxes-acc-container">
            <div class="acc-right-content">
                <div class="general-account-holder">
                    <?php $this->switchAction() ?>
                </div>
            </div>
            </div>
            </div>
            <?php
        }else
            $this->jsRedirect('/');

    }

    function setup($route){
        $username = ($_SESSION['mg_username']);
        $page = 'rtp';
        return array($username, $page);
    }


    function rtpSummary() {
        $rtp = phQget($cache_key = $this->getRtpCacheKey());
        if(empty($rtp)) {
            $rtp = phive('MicroGames')->rtpGetByUser($this->cur_user);
            phQset($cache_key, $rtp, 15);
        }
        ?>
        <div class="rtp-box">
        <div class="rtp-item rtp-item--big left">
            <div class="rtp-item-avatar rtp-item-avatar--user"><img src="/diamondbet/images/<?= brandedCss() ?><?php echo ucfirst($this->cur_user->data['sex'])?>_Profile.jpg" width="50"></div>
            <div class="margin-ten-bottom margin-ten-top" >
                <h6><?php et('account.overall-rtp.headline') ?></h6>
                <h3><?php echo $rtp['overall']?>%</h3>
            </div>
        </div>
        <div class="rtp-item right rtp-item--margin-five-bottom">
            <div class="flex-center">
                <div class="rtp-item-avatar"><img src="<?php fupUri("thumbs/".$rtp['low']['game_id'].'_c.jpg') ?>" width="35" height="35"></div>
                <div class="rpt-item_text">
                    <h6><?php et('account.rtp_low.headline') ?></h6>
                    <h3><?php echo $rtp['low']['rtp']?>%</h3>
                </div>
            </div>
        </div>
        <div class="rtp-item right">
            <div class="flex-center">
                <div class="rtp-item-avatar"><img src="<?php fupUri("thumbs/".$rtp['hi']['game_id'].'_c.jpg') ?>" width="35" height="35"></div>
                <div class="rpt-item_text">
                    <h6><?php et('account.rtp_hi.headline') ?></h6>
                    <h3><?php echo $rtp['hi']['rtp']?>%</h3>
                </div>
            </div>
        </div>
        </div>
        <?php
    }
}
