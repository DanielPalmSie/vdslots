<?php

use Laraphive\Domain\User\DataTransferObjects\AccountHistoryData;
use Laraphive\Domain\User\DataTransferObjects\GameHistoryData;
use Videoslots\RgLimits\Factories\RgLimitsBuilderFactory;
use Videoslots\User\Profile\ProfileService;

require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/GmsSignupUpdateBoxBase.php';

class AccountBox extends GmsSignupUpdateBoxBase{

    /** @var RgLimits */
    public RgLimits $rg;

    /** @var bool  */
    private bool $is_api;
    private string $new_version_jquery_ui;

    /**
     * @param bool $is_api
     *
     * @return void
     */
    function init(bool $is_api = false)
    {
        $this->is_api = $is_api;
        $this->new_version_jquery_ui = phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';
        if($this->is_api) {
            $this->mg = phive('QuickFire');
            $this->p = phive("Paginator");

            return;
        }

        parent::init();
        //if(isIpad())
        //    $this->site_type = 'ipad';
    }

    /**
     * Return the serialized $_REQUEST
     *
     * @return string
     */
    function getRtpCacheKey() {
        return serialize($_REQUEST);
    }

    function rtpSetup(){
        $this->pOrSelfStop();

        $params = explode('&', $_REQUEST['params']);
        $this->vars = [];
        foreach($params as $param) {
            $var                 = explode('=', $param);
            $this->vars[$var[0]] = urldecode($var[1]);
        }

        if(!empty($_GET['game'])){
            $this->game = phive('MicroGames')->getById($_GET['game']);
        }

        if(!empty($this->default_start_date))
            return;

        if(!empty($_REQUEST['dt_from'])){
            $this->default_start_date = $_REQUEST['dt_from'];
            $this->default_end_date = $_REQUEST['dt_to'];
        }else{
            $this->default_start_date = phive()->lcDate(strtotime("-7 days"), '%x');
            $this->default_end_date = phive()->lcDate(time(), '%x');
        }

        return $this->vars;
    }

    function rtpSearch()
    {
        $res = phQget($cache_key = $this->getRtpCacheKey());
        if(!empty($res)) {
            die($this->rtpReturn($res));
        }

        $vars = $this->rtpSetup();

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

        $output = $result = [];
        $order  = empty($vars['order']) ? 'DESC' : 'ASC';

        switch($type){
            case 'rtp_hi':
                $result = $mg->rtpGetListByUser($this->cur_user, $vars['game'], $date_from_to, 'DESC');
                break;
            case 'rtp_low':
                $result = $mg->rtpGetListByUser($this->cur_user, $vars['game'], $date_from_to, 'ASC');
                break;
            case 'rtp_all':
                $result = $mg->rtpGetListAll($this->cur_user, $vars['game'], $date_from_to, $order, [(int) $_REQUEST['from'], 30]);
                break;
            case 'rtp_game':
                $result = $mg->rtpGetGameSessions($this->cur_user, (int) $_REQUEST['game_id'], $date_from_to, $order, [(int) $_REQUEST['from'], 30]);
                break;
            case 'bets_wins':
                $result = $mg->rtpGetBetsWins($this->cur_user, $vars['session'], [(int) $_REQUEST['from'], 30]);
                break;
        }

        foreach ($result as $row) {

            if (!empty($row['start_time'])) {
                $d = new DateTime($row['start_time']);
                $row['start_time_dt'] = phive()->lcDate($d->getTimestamp(), '%x');
                $row['start_time']    = $d->format('H:i:s');
            }
            if (!empty($row['created_at'])) {
                $d = new DateTime($row['created_at']);
                $row['created_at']      = phive()->lcDate($d->getTimestamp(), '%x');
                $row['created_at_time'] = $d->format('H:i:s');
            }

            $row['rtp_prc'] = formatRTP($row['rtp']);
            $row['rtp_prc_month'] = formatRTP($row['rtp_month']);
            $row['rtp_prc_month_prev'] = formatRTP($row['rtp_month_prev']);

            $row['payout_prc']         = round($row['payout_percent'], 2);
            //$row['img_url']            = fupUri('thumbs/'.$row['game_id'].'_c.jpg', true);
            $row['img_url']            = $mg->carouselPic($row);

            if(empty($row['bet_amount']) && !empty($row['win_amount'])){
                // We have session with wins but no bets (yes it is possible)
                $row['rtp_prc'] = 'N / A';
                $row['rtp']     = 'N / A';
            }

            if (!empty($row['win_amount'])) {
                $row['win_amount'] = phive()->twoDec($row['win_amount']);
            }

            if (!empty($row['bet_amount'])) {
                $row['bet_amount'] = phive()->twoDec($row['bet_amount']);
            }

            if (!empty($row['amount'])) {
                $row['amount'] = phive()->twoDec($row['amount']);
            }

            $output[] = $row;
        }

        phQset($cache_key, $output, 15);

        echo $this->rtpReturn($output);
    }

    function rtpReturn($data){
        return json_encode(['data' => $data, 'translations' => ['view' => t('view'), 'week' => t('week'), 'bet' => t('bet'), 'win' => t('win')]]);
    }

    function rtpGraph() {

        $vars = $this->rtpSetup();

        $mg     = phive('MicroGames');

        if (!empty($vars['session'])) {
            $result = $mg->rtpGetSessionGraph($this->cur_user, $vars['session']);
            echo json_encode($result);
            return;
        }

        $dt_from       = phive('Localizer')->getStampFromIntl("{$vars['dt_from']}", 'object');
        $dt_to         = phive('Localizer')->getStampFromIntl("{$vars['dt_to']}", 'object');
        $date_from_to  = [date_format($dt_from, "Y-m-d {$vars['time_from']}:00"), date_format($dt_to, "Y-m-d {$vars['time_to']}:00")];

        $result       = $mg->rtpGetGraph($this->cur_user, $_REQUEST['game'], $_REQUEST['rtp'], $date_from_to, $_REQUEST['type']);
        echo $this->rtpReturn($result);
    }

    function rtpBackLink(){
        $base_link               = llink('/account/'.cuAttr('id').'/rtp/');
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

    function rtpFormTable($id, $action){
    ?>
    <div class="rtp-score-table rtp-score-table--half">
        <h2><?php et("account.$action.headline") ?></h2>
        <form class="rtp-filter" id="<?php echo $action ?>">
            <fieldset>
                <div class="rtp-filter-label">
                    <label><?php et('search.game') ?></label>
                    <input class="rtp-search" name="game" type="text" placeholder="<?php et('all.games') ?>"></input>
                </div>
                <div class="rtp-filter-label">
                    <label><?php et('from') ?></label>
                    <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                    <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
                </div>
                <div class="rtp-filter-label">
                    <label><?php et('to') ?></label>
                    <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                    <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
                </div>
                <button onclick="rtp.search('<?php echo $action ?>');return false;" class="rtp-btn rtp-btn-search icon icon-vs-search"></button>
            </fieldset>
        </form>
        <?php $this->rtpTable($id) ?>
    </div>
    <?php
    }

    function rtpTable($id){
    ?>
        <table class="rtp-table" id="<?php echo $id ?>">
            <colgroup>
                <col width="35">
                <col width="63">
                <col width="50">
                <col width="130">
                <col width="50">
                <col width="70">
            </colgroup>
            <thead>
                <tr>
                    <th></th>
                    <th><?php et('date') ?></th>
                    <th><?php et('time') ?></th>
                    <th><?php et('game.name') ?></th>
                    <th><?php et('rtp') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    <?php
    }

    function rtp() {
        $this->rtpSetup();
        loadCss("/diamondbet/css/" . brandedCss() . "rtp.css");
        loadJs('/phive/js/account_rtp_page.js');
        ?>
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.min.css">
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
        <?php
        $mg      = phive('MicroGames');
        $game_id = !empty($_REQUEST['game']) ? (int) $_REQUEST['game'] : 0;
        $game    = $mg->getById($game_id);
        //echo '<pre>'.print_r($game, true).'</pre>';

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

        if ($game_id) {

            $info_list    = $mg->featuresList($game_id, 'info');
            $feature_list = $mg->featuresList($game_id, 'feature');

        ?>

            <script>
             var getSession = '<?php echo $_GET['session'] ?>';
             graph.initOptions({
                 session: getSession
             });

             $(document).ready(function () {

                 $('input.rtp-date').datepicker({
                     showButtonPanel: false,
                     dateFormat: '<?php echo strtolower(phive('Localizer')->getIntlDtFormat()) ?>'
                 });

                 rtp.search( empty(getSession) ? 'rtp_game' : 'bets_wins' );
                 rtp.searchGraph( empty(getSession) ? 'week' : 'session' );
             });
            </script>

            <div class="rtp-score">
                <div class="rtp-score-table rtp-score-table--full">

                    <?php $this->rtpBackLink() ?>
                    <form class="rtp-filter" id="rtp_graph">
                        <?php if(!empty($_GET['session'])):?>
                            <input type="hidden" name="session" value="<?php echo $_GET['session'] ?>">
                        <?php else: ?>
                            <fieldset>
                                <div class="rtp-filter-label">
                                    <label><?php et('from') ?></label>
                                    <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                                    <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
                                </div>
                                <div class="rtp-filter-label">
                                    <label><?php et('to') ?></label>
                                    <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                                    <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
                                </div>
                                <button onclick="rtp.searchGraph();return false;" id="graph-btn-search" class="rtp-btn rtp-btn-search icon icon-vs-search"></button>
                                <div class="filter-tabs">
                                    <a href="#" onclick="rtp.setType(this);return false;" data-type="day" class="rtp-btn inactive"><?php et('day') ?></a>
                                    <a href="#" onclick="rtp.setType(this);return false;" data-type="week" class="rtp-btn active"><?php et('week') ?></a>
                                    <a href="#" onclick="rtp.setType(this);return false;" data-type="month" class="rtp-btn inactive"><?php et('month') ?></a>
                                </div>
                            </fieldset>
                        <?php endif; ?>
                    </form>

                    <div class="rtp-g-wrapper">
			<div class="rtp-g-graph">
                            <div class="graph-info">
                                <?php echo !empty($_GET['session']) ? t('amount') : 'RTP %' ?>
                            </div>
                            <!-- Graph HTML -->
                            <div id="graph-wrapper">
                                <div class="graph-container" style="mergin-left: 200px;">
                                    <div id="graph-lines"></div>
                                </div>
                            </div>
                            <!-- end Graph HTML -->
                        </div>
			<div class="rtp-g">
			    <div class="rtp-g-item">
                    <div class="rtp-g-item-avatar"><img src="/diamondbet/images/<?= brandedCss() ?>hand.png" alt=""></div>
				    <h3 id="rtp_range"></h3>
				    <h6><?php et('my.rtp') ?></h6>
			    </div>
			    <div class="rtp-g-item">
                    <div class="rtp-g-item-avatar"><img src="/diamondbet/images/<?= brandedCss() ?>star.png" alt=""></div>
				    <h3 id="hitrate-stats"></h3>
				    <h6><?php et('hit.rate') ?></h6>
			    </div>
			    <div class="rtp-g-item">
                    <div class="rtp-g-item-avatar"><img src="/diamondbet/images/<?= brandedCss() ?>tspins_big.png" alt=""></div>
				    <h3 id="totalspins"></h3>
				    <h6><?php et('total.spins') ?></h6>
			    </div>
			    <div class="rtp-g-item">
                    <div class="rtp-g-item-avatar"><img src="/diamondbet/images/<?= brandedCss() ?>casino_rtp.png" alt=""></div>
				    <h3 id="average_bet_ammount"></h3>
				    <h6><?php et('bet.average') ?></h6>
			    </div>
			</div>
		    </div>

                    <div class="rtp-g-big">
                        <div class="rtp-g-item-big">
                            <div class="rtp-g-item-avatar-big"><img src="/diamondbet/images/<?= brandedCss() ?>spsession_big.png" alt=""></div>
                            <h3><?php et('spins.per.session') ?></h3>
                            <h4>
                                <strong><span id="avgtime-stats"></span> <i><?php et('time') ?></i></strong>
                                <strong><span id="avgbets-stats"></span> <i><?php et('spins') ?></i></strong>
                            </h4>
                        </div>
                        <div class="rtp-g-item-big">
                            <div class="rtp-g-item-avatar-big"><img src="/diamondbet/images/<?= brandedCss() ?>bwin_big.png" alt=""></div>
                            <h3><?php et('biggest.win') ?></h3>
                            <h4>
                                <strong><span id="biggestwin"></span> <i><?php et('win') ?></i></strong>
                                <strong><span id="biggestwin-bet"></span> <i><?php et('bet') ?></i></strong>
                            </h4>
                        </div>
                    </div>

                    <?php if(p('rtp.game.info')): ?>
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
                <?php endif ?>
                </div>

                <div class="rtp-score-table rtp-score-table--full">
                    <?php $this->rtpBackLink() ?>
                    <form class="rtp-filter" id="<?php if(!empty($_GET['session'])):?>bets_wins<?php else: ?>rtp_game<?php endif; ?>">
                        <?php if(!empty($_GET['session'])):?>
                            <input type="hidden" name="session" value="<?php echo $_GET['session'] ?>">
                        <?php else: ?>
                            <fieldset>
                                <div class="rtp-filter-label">
                                    <label><?php et('from') ?></label>
                                    <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                                    <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
                                </div>
                                <div class="rtp-filter-label">
                                    <label><?php et('to') ?></label>
                                    <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                                    <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
                                </div>
                                <div class="rtp-filter-label">
                                    <div class="rtp-select">
                                        <select name="order">
                                            <option value="0"><?php et('newest') ?></option>
                                            <option value="1"><?php et('oldest') ?></option>
                                        </select>
                                    </div>
                                </div>
                                <button onclick="rtp.search('rtp_game', false, false);return false;" class="rtp-btn rtp-btn-search icon icon-vs-search"></button>
                            </fieldset>
                        <?php endif;?>
                    </form>
                    <div class="rtp-table-wrapper">
                        <table class="rtp-table" id="<?php if(!empty($_GET['session'])):?>bets_wins<?php else: ?>rtp_game<?php endif; ?>_table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php if(!empty($_GET['session'])):?>
                                        <th><?php et('date') ?></th>
                                        <th><?php et('time') ?></th>
                                        <th><?php et('type') ?></th>
                                        <th><?php et('game.name') ?></th>
                                        <th><?php et('amount') ?></th>
                                    <?php else: ?>
                                        <th><?php et('date') ?></th>
                                        <th><?php et('time') ?></th>
                                        <th><?php et('game.name') ?></th>
                                        <th><?php et('bet') ?></th>
                                        <th><?php et('win') ?></th>
                                        <th>RTP %</th>
                                        <th></th>
                                    <?php endif;?>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <a href="#" onclick="rtp.search('<?php if(!empty($_GET['session'])):?>bets_wins<?php else:?>rtp_game<?php endif;?>', false, 1);return false;" class="rtp-more"><?php et('view.more') ?></a>
                    </div>
                </div>
            </div>
            <?php
        }
        else {
            $rtp = phQget($cache_key = $this->getRtpCacheKey());
            if(empty($rtp)) {
                $rtp = $mg->rtpGetByUser($this->cur_user);
                phQset($cache_key, $rtp, 15);
            }
            ?>
            <script language="JavaScript">
             $(document).ready(function() {

                 $('input.rtp-date').datepicker({
                     showButtonPanel: false,
                     dateFormat: '<?php echo strtolower(phive('Localizer')->getIntlDtFormat()) ?>'
                 });

                 rtp.search('rtp_hi', true);
                 rtp.search('rtp_low', true);
                 rtp.search('rtp_all', true);
             } );
            </script>

            <div class="rtp">
                <div class="rtp-item">
                    <div class="rtp-item-avatar"><img src="/diamondbet/images/<?= brandedCss() ?><?php echo ucfirst($this->cur_user->data['sex'])?>_Profile.jpg" width="90"></div>
                    <h6><?php et('account.overall-rtp.headline') ?></h6>
                    <h3><?php echo $rtp['overall']?>%</h3>
                </div>
                <div class="rtp-item">
                    <div class="rtp-item-avatar"><img src="<?php fupUri("thumbs/".$rtp['low']['game_id'].'_c.jpg') ?>" width="90" height="90"></div>
                    <h6><?php et('account.rtp_low.headline') ?></h6>
                    <h3><?php echo $rtp['low']['rtp']?>%</h3>
                </div>
                <div class="rtp-item">
                    <div class="rtp-item-avatar"><img src="<?php fupUri("thumbs/".$rtp['hi']['game_id'].'_c.jpg') ?>" width="90" height="90"></div>
                    <h6><?php et('account.rtp_hi.headline') ?></h6>
                    <h3><?php echo $rtp['hi']['rtp']?>%</h3>
                </div>
            </div>

            <div class="rtp-score">

                <?php $this->rtpFormTable('rtp_hi_table', 'rtp_hi') ?>
                <?php $this->rtpFormTable('rtp_low_table', 'rtp_low') ?>

                <div class="rtp-score-table rtp-score-table--full">
                    <h2><?php et('account.highest-rtp.headline') ?></h2>
                    <form class="rtp-filter" id="rtp_all">
                        <fieldset>
                            <div class="rtp-filter-label">
                                <label><?php et('search.game') ?></label>
                                <input class="rtp-search" name="game" type="text" placeholder="<?php et('all.games') ?>"></input>
                            </div>
                            <div class="rtp-filter-label">
                                <label><?php et('from') ?></label>
                                <input class="rtp-date" type="text" name="dt_from" value="<?php echo $this->default_start_date ?>"></input>
                                <input class="rtp-time" type="text" name="time_from" value="00:00"></input>
                            </div>
                            <div class="rtp-filter-label">
                                <label><?php et('to') ?></label>
                                <input class="rtp-date" type="text" name="dt_to" value="<?php echo $this->default_end_date ?>"></input>
                                <input class="rtp-time" type="text" name="time_to" value="23:59"></input>
                            </div>
                            <div class="rtp-filter-label">
                                <div class="rtp-select">
                                    <select name="order">
                                        <option value="0"><?php et('newest') ?></option>
                                        <option value="1"><?php et('oldest') ?></option>
                                    </select>
                                </div>
                            </div>
                            <button onclick="rtp.search('rtp_all');return false;" class="rtp-btn rtp-btn-search icon icon-vs-search"></button>
                        </fieldset>
                    </form>
                    <div class="rtp-table-wrapper">
                        <table class="rtp-table" id="rtp_all_table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><?php et('date') ?></th>
                                    <th><?php et('time') ?></th>
                                    <th><?php et('game.name') ?></th>
                                    <th><?php et('all.time.rtp') ?></th>
                                    <th><?php et('this.month.rtp') ?></th>
                                    <th><?php et('previous.month.rtp') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <a href="#" onclick="rtp.search('rtp_all', false, 1);return false;" class="rtp-more"><?php et('view.more') ?></a>
                    </div>
                </div>
            </div>
            <?php
        }
    }

  function notificationHistory(){
    $ns = phive('UserHandler')->getLatestNotifications($this->cur_user->getId(), 20);
    $this->p->setPages(count($ns), '', 10);
    $offs = $this->p->getOffset(10);
    $ns = array_slice($ns, $offs, 10);
    $uh = phive('UserHandler');
?>
    <div class="general-account-holder">
        <div class="simple-box pad-stuff-ten">
          <h3><?php et('notification.history') ?></h3>
          <table class="account-tbl">
            <tr>
              <td style="vertical-align: top;">
                <table class="zebra-tbl">
                  <col width="180"/>
                  <col width="390"/>
                  <col width="90"/>
                  <tr class="zebra-header">
                    <td><?php echo t('trans.time') ?></td>
                    <td><?php echo t('description') ?></td>
                    <td></td>
                  </tr>
                  <?php $i = 0; foreach($ns as $row): ?>
                  <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                    <td><?php echo phive()->lcDate($row->created_at) .' '.t('cur.timezone') ?></td>
                    <td><?php echo $uh->eventString($row, 'you.') ?></td>
                    <td><img style="width: 35px; height: 35px;" style="padding: 0px;" src="<?php $uh->eventImage($row) ?>" /></td>
                  </tr>
                  <?php $i++; endforeach ?>
                </table>
              </td>
            </tr>
          </table>
        </div>
        <br/>
      <?php $this->p->render() ?>
    </div>
    <br/>
    <br/>
    <?php
  }

    function notificationHistoryMobile()
    {
        $uh = phive('UserHandler');
        $ns = phive('UserHandler')->getLatestNotifications($this->cur_user->getId(), 20);

        loadCss('/diamondbet/css/' . brandedCss() . 'mobile-notification-history.css');

        ?>
        <div class="general-account-holder">
            <div class="simple-box pad-stuff-ten">
                <h3><?php et('notification.history') ?></h3>
                <?php $i = 0; foreach($ns as $row): ?>


                <div class="notification_item">
                    <div class="notification_item-icon">
                        <img style="width: 35px; height: 35px;" style="padding: 0px;" src="<?php $uh->eventImage($row) ?>" />
                    </div>
                    <div class="notification_item-description">
                        <?php echo $uh->eventString($row, 'you.') ?>
                    </div>
                    <div class="notification_item-date">
                        <?php echo phive()->lcDate($row->created_at) .' '.t('cur.timezone') ?>
                    </div>


                </div>

                <?php $i++; endforeach ?>
            </div>
            <br/>
        </div>
        <br/>
        <br/>
        <?php
    }

    /**
     * @param Laraphive\Domain\User\DataTransferObjects\AccountHistoryData $accountHistoryData
     *
     * @return array
     */
    function getAccountHistory(AccountHistoryData $accountHistoryData): array
    {
        if (!$this->is_api) {
            $this->handleCancelPending();
        }

        $user_id    = $this->cur_user->getId();

        $start_date = phive()->validateDate($accountHistoryData->getStartDate()) ?
            phive()->fDate($accountHistoryData->getStartDate()) :
            phive()->modDate(null, '-1 day');
        $end_date   = phive()->validateDate($accountHistoryData->getEndDate()) ?
            phive()->fDate($accountHistoryData->getEndDate()) :
            phive()->modDate(null, '+1 day');

        /* This value was already hard-coded in later down the page. In short, it acts as a default in the case
        that there is not any information pulled from the front end about the starting date. */
        $start_date = $start_date ?: "2011-01-01";

        /* The $end_date variable should not be later than today as it isn't possible to retrieve information from days that haven't occurred yet. As a result we use the 'phive->hisNow()' function to check if $end_date is later than the current time and if so we alter $end_date accordingly. */
        $end_date = phive()->hisNow() < $end_date ? phive()->hisNow() : $end_date;

        /* The same logic applied as above but with the understanding that
        $start_date should always be earlier than $end_date. */
        $start_date = $start_date < $end_date ? $start_date : $end_date;

        if (empty($this->user_psps)) {
            $methods = array_merge(
                array_column(phive('Cashier')->getDepositMethodsByUserId($user_id), 'dep_type'),
                array_column(phive('Cashier')->getWithdrawalMethodsByUserId($user_id), 'payment_method')
            );
            $this->user_psps = array_combine($methods, $methods);
        }

        $provider = $this->user_psps[$accountHistoryData->getProvider()] ?? '';
        $providerQuery = $provider ? "AND dep_type = '$provider'" : "";

        $data = phive('Cashier')->getTransactionSumsByUserIdProvider($user_id, $start_date, $end_date, $provider);
        $sum_deposits    = $data['sum_deposits'];
        $sum_withdrawals = $data['sum_withdrawals'];

        if ($accountHistoryData->getFilterBy() === 'transactions') {
            $deposit_and_withdrawal_types = [3, 8];
            $this->show_types = array_merge($this->show_types, $deposit_and_withdrawal_types);
        }

        $transactions 	= phive('Cashier')->getUserTransactions($this->cur_user, $this->show_types, '', [$start_date, $end_date], 'timestamp');

        $this->p->setPages(count($transactions), '', $accountHistoryData->getLimit());
        $page 		    = empty($accountHistoryData->getPage()) ? 1 : $accountHistoryData->getPage();
        $limit 		    = 'LIMIT '.(($page - 1) * $accountHistoryData->getLimit()) . ',' . $accountHistoryData->getLimit();
        $offs = ($accountHistoryData->getPage() - 1) * $accountHistoryData->getLimit();
        $withdrawals    = phive('Cashier')->getPendingsUser($user_id, " NOT IN('pending', 'initiated') ", $limit, "$provider", '', true, $start_date, $end_date, "LEFT JOIN cash_transactions ct ON ct.parent_id = pending_withdrawals.id AND ct.transactiontype = 103 AND ct.user_id = $user_id", ", ct.id as undone");
        $pendings       = phive('Cashier')->getPendingsUser($user_id, " = 'pending' ", '', "$provider", '', false, $start_date, $end_date);
        $deposits       = phive('Cashier')->getDeposits($start_date, $end_date, $user_id, '', false, '', $providerQuery);
        $withdrawalsTotal = phive('Cashier')->getTotalPendingsCounts($user_id, " NOT IN('pending', 'initiated') ", $limit, "$provider", '', true, $start_date, $end_date, "LEFT JOIN cash_transactions ct ON ct.parent_id = pending_withdrawals.id AND ct.transactiontype = 103 AND ct.user_id = $user_id", ", ct.id as undone");
        $total    = $this->p->getTotalCount($transactions, $limit);
        $transactions 	= array_slice($transactions, $offs, $accountHistoryData->getLimit());

        if ($accountHistoryData->getFilterBy() =='withdrawals'){
            $total = $withdrawalsTotal;
        }

        if ($accountHistoryData->getFilterBy() =='deposits'){
            $total = count($deposits);
        }
        if ($accountHistoryData->getFilterBy() == "all"){
            $total += count($deposits) + $withdrawalsTotal;
        }

        $deposits 	= array_slice($deposits, $offs, $accountHistoryData->getLimit());

        if($this->site_type == 'mobile')
            $cols = array(120, 50, 150, 50);
        else
            $cols = array(200, 120, 240, 100);

        if($this->site_type == 'mobile')
            $cols1 = array(120, 210, 50);
        else
            $cols1 = array(200, 360, 100);

        $params = array(
            'transactions'    => $transactions,
            'start_date'      => $start_date,
            'end_date'        => $end_date,
            'page'            => $page,
            'pages_count'     => (int) ceil($total / $accountHistoryData->getLimit()),
            'limit'           => $accountHistoryData->getLimit(),
            'withdrawals'     => $withdrawals,
            'sum_withdrawals' => efEuro($sum_withdrawals, true),
            'pendings'        => $pendings,
            'deposits'        => $deposits,
            'sum_deposits'    => efEuro($sum_deposits, true),
            'sum_net'         => efEuro($sum_withdrawals - $sum_deposits, true),
            'user_providers'  => $this->user_psps,
            'provider'        => $provider,
            'cols'            => $cols,
            'cols1'           => $cols1,
            'total'           => $total
        );

        return $params;
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\AccountHistoryData $data
     * @throws Exception
     */
    public function printAccountHistory(AccountHistoryData $accountHistoryData)
    {
        $account_history = $this->getAccountHistory($accountHistoryData);
        $this->printAccountHistoryHTML($account_history);
    }

    function printAccountHistoryHTML($params)
    {
        extract($params);
        loadCss("/diamondbet/css/" . brandedCss() . "g-a-history.css");

        // Clean up the URLs
        $start_date_GET_Request = empty($start_date) ? "" : "&start_date=$start_date";
        $end_date_GET_Request = empty($end_date) ? "" : "&end_date=$end_date";
        $provider_GET_Request = empty($provider) ? "" : "&provider=$provider";
        $GET_Requests = "$start_date_GET_Request$end_date_GET_Request$provider_GET_Request";

        ?>
    <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.min.css">
    <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
        <script type="text/javascript">
        $(document).ready(function () {
            $('input.g-a-date').datepicker({
            showButtonPanel: false,
                dateFormat: 'yy-mm-dd'
            });
        });
        </script>
    <!-- <?php $this->printTopMenu() ?> -->
    <br clear="all" />
    <div class="general-account-holder">
        <?php if(empty(phive()->getSetting('hide_all_time'))): ?>
        <div class="simple-box pad-stuff-ten">
            <h3><?php et('my.all.time') ?></h3>
            <form class="g-a-filter-form account-history-form" id="form" method="get" autocomplete="off">
            <?php if (phive()->isMobile()): ?>
                <div class="g-a-filter-label__block">
                    <div class="g-a-filter-label g-a-filter-label--mobile">
                        <label><?php et('all.providers') ?></label>
                        <div class="g-a-select--mobile--wrapper">
                            <select class="g-a-select g-a-select--mobile" name="provider" >
                                <?php if (!in_array($provider, $user_providers, true)) { ?>
                                    <option selected="selected" value=""><?php et('all.providers') ?></option>
                                    <?php foreach ($user_providers as $p) { ?>
                                        <option value="<?php echo $p ?>"><?php translateOrKey(ucfirst($p)) ?></option>
                                    <?php }
                                } else { ?>
                                    <option value=""><?php et('all.providers') ?></option>
                                    <?php foreach ($user_providers as $p) { ?>
                                        <option <?php echo $provider == $p ? 'selected=selected' : '' ?> value="<?php echo $p ?>"><?php translateOrKey(ucfirst($p)) ?></option>
                                    <?php }
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="g-a-filter-label">
                    <label><?php et('all.providers') ?></label>
                    <select class="g-a-select" name="provider" >
                        <?php if (!in_array($provider, $user_providers, true)) { ?>
                                  <option selected="selected" value=""><?php et('all.providers') ?></option>
                                    <?php foreach ($user_providers as $p) { ?>
                                            <option value="<?php echo $p ?>"><?php echo translateOrKey(ucfirst($p)) ?></option>
                                    <?php }
                                    } else { ?>
                                <option value=""><?php et('all.providers') ?></option>
                                <?php foreach ($user_providers as $p) { ?>
                                        <option <?php echo $provider == $p ? 'selected=selected' : '' ?> value="<?php echo $p ?>"><?php echo translateOrKey(ucfirst($p)) ?></option>
                                <?php }
                                    } ?>
                    </select>
                </div>
            <?php endif ?>
            <?php if (phive()->isMobile()): ?>
                <div class="g-a-filter-label__block">
                    <div class="g-a-filter-label g-a-filter-label--mobile">
                        <label><?php et('from') ?></label>
                        <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>"></input>
                    </div>
                    <div class="g-a-filter-label g-a-filter-label--mobile">
                        <label><?php et('to') ?></label>
                        <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>"></input>
                    </div>
                    <div class="g-a-filter-label">
                        <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                    </div>
                </div>
            <?php else: ?>
                <div class="g-a-filter-label">
                    <label><?php et('from') ?></label>
                    <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>"></input>
                </div>
                <div class="g-a-filter-label">
                    <label><?php et('to') ?></label>
                    <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>"></input>
                </div>
                <div class="g-a-filter-label">
                    <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                </div>
            <?php endif ?>
            </form>
            <?php if (phive()->isMobile()): ?>
                <div class="g-a-container-mobile">
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.deposits') ?></span>
                        <span><?= $sum_deposits ?></span>
                    </div>
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.withdrawals') ?></span>
                        <span><?= $sum_withdrawals ?></span>
                    </div>
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.net') ?></span>
                        <span><?= $sum_net ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="g-a-container">
                    <div class="g-a-item">
                        <h3><?php echo $sum_deposits ?></h3><br/><h6><?php et('my.all.time.deposits') ?></h6>
                    </div>
                    <div class="g-a-item">
                        <h3><?php echo $sum_withdrawals ?></h3><br/><h6><?php et('my.all.time.withdrawals') ?></h6>
                    </div>
                    <div class="g-a-item">
                        <h3><?php echo $sum_net ?></h3><br/><h6><?php et('my.all.time.net') ?></h6>
                    </div>
                </div>
            <?php endif ?>

        </div>
        <br/>
        <?php endif; ?>
        <?php foreach(array('pending.withdrawals' => $pendings, 'withdrawals' => $withdrawals) as $headline => $rows):
        if(empty($rows))
            continue;
        ?>

                <div class="simple-box pad-stuff-ten">
                  <?php $this->printTrTable($rows, $headline) ?>
                  <strong>
                    <?php if($page > 1): ?>
                      <a href="?page=<?php echo $page - 1 . "$GET_Requests" ?>">&#xAB; <?php et('later') ?></a>
                        <a href="?page=<?php echo $page + 1 . "$GET_Requests" ?>"> <?php et('earlier') ?> &#xBB;</a>
                    <?php endif ?>
                    &nbsp;

                  </strong>
              </div>
              <br/>
            <?php endforeach ?>
            <div class="simple-box pad-stuff-ten">
            <h3><?php et('deposits') ?></h3>
            <table class="account-tbl">
              <tr>
                <td style="vertical-align: top;">
                  <table class="zebra-tbl">
                    <col width="<?php echo isIpad() ? $cols[0]+25 : $cols[0] ?>"/>
                    <col width="<?php echo isIpad() ? $cols[1]+25 : $cols[1] ?>"/>
                    <col width="<?php echo isIpad() ? $cols[2]+25 : $cols[2] ?>"/>
                    <col width="<?php echo isIpad() ? $cols[3]+25 : $cols[3] ?>"/>
                    <tr class="zebra-header">
                      <td><?php et('trans.time') ?></td>
                      <td><?php et('trans.type') ?></td>
                      <td><?php et('card.num') ?></td>
                      <td><?php echo t('amount') ?></td>
                    </tr>
                    <?php $i = 0; foreach($deposits as $t): ?>
                      <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                        <td><?php lcDate($t['timestamp'], true, true, '%x %T') ?></td>
                        <td style="cursor:default;" title="<?php echo $t['ext_id']; ?>">
                            <?php echo mb_strtoupper(pt($t['display_name']), 'UTF-8') ?>
                        </td>
                        <td><?php echo $t['card_hash'] ?></td>
                        <td><?php echo cs() . " " . rnfCents($t['amount'], ".", "") ?></td>
                      </tr>
                    <?php $i++; endforeach ?>
                  </table>
                </td>
              </tr>
            </table>
            <strong>
              <?php if($page > 1): ?>
                <a href="?page=<?php echo ($page - 1) . "$GET_Requests"?>">&#xAB; <?php et('later') ?></a>
               &nbsp;<a href="?page=<?php echo ($page + 1) . "$GET_Requests"?>"> <?php et('earlier') ?> &#xBB; </a>
              <?php endif ?>
            </strong>
            </div>
            <br/>
            <div class="simple-box pad-stuff-ten">
            <h3><?php et('other.transactions') ?></h3>
            <table class="account-tbl">
              <tr>
                <td style="vertical-align: top;">
                  <table class="zebra-tbl">
                    <col width="<?php echo isIpad() ? $cols[0]+50 : $cols1[0] ?>"/>
                    <col width="<?php echo isIpad() ? $cols[1]+80 : $cols1[1] ?>"/>
                    <col width="<?php echo isIpad() ? $cols[2]+25 : $cols1[2] ?>"/>
                    <tr class="zebra-header">
                      <td><?php et('trans.time') ?></td>
                      <td><?php et('trans.type') ?></td>
                      <td><?php echo t('amount') ?></td>
                    </tr>
                    <?php $i = 0; foreach($transactions as $t): ?>
                      <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                        <td><?php echo phive()->lcDate($t['timestamp']).' '.t('cur.timezone') ?></td>
                        <td><?php $this->prTrDescr($t) ?></td>
                        <td><?php echo ' '.cs() . " " . rnfCents($t['amount'], ".", "") ?></td>
                      </tr>
                    <?php $i++; endforeach ?>
                  </table>
                </td>
              </tr>
            </table>
            </div>
            <br/>
            <?php licHtml('download_financial_data'); ?>
            <?php $this->p->render('', "&action={$_GET['action']}" . "&start_date=" . $_GET['start_date'] . "&end_date=" . $_GET['end-date'] . "&provider=" . $_GET['provider']); ?>
            </div>
            <br/>
            <br/>
        <?php }

  function printSportsBettingHistoryHTML($params)
    {
        extract($params);
        $mobile_suffix = phive()->isMobile() ? "--mobile" : "";
        loadCss("/diamondbet/css/" . brandedCss() . "g-a-history.css");
        loadCss('/diamondbet/css/bet-receipt.css');
        /** @var Sportsbook $sportsbook */
        $sportsbook = phive('Micro/Sportsbook')->init($start_date, $end_date, $user_id);
        $ticket_ids = array_column($params['transactions'], 'ticket_id');
        $wins = array_merge(

            $sportsbook->getByBetTypeAndProductForTicketIds('win', $ticket_ids),
            $sportsbook->getByBetTypeAndProductForTicketIds('void', $ticket_ids)
        );

        ?>
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.min.css">
        <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
        <script type="text/javascript">
            $(document).ready(function () {
                $('input.g-a-date').datepicker({
                    showButtonPanel: false,
                    dateFormat: 'yy-mm-dd'
                });
            });
            function showBetSlipReceipt(id, user_id) {
                extBoxAjax('get_raw_html', 'mbox-msg', {module: 'Micro', file: 'bet_receipt', id: id, user_id: user_id});
                var betslipsContainer = $('.bet-receipt');
                var hasVerticalScrollbar = betslipsContainer.scrollHeight > betslipsContainer.clientHeight;
                if(hasVerticalScrollbar) {
                    var scrollbarWidth = betslipsContainer.offsetWidth - betslipsContainer.clientWidth;
                    $('.lic-mbox-close-box').marginRight(scrollbarWidth);
                }
            }
        </script>

        <div class="general-account-holder">
            <div class="simple-box pad-stuff-ten">
                <h3><?php et('sports-history.my.all.time') ?></h3>
                <form class="g-a-filter-form" id="form" method="get" autocomplete="off">
                    <?php if (phive()->isMobile()): ?><div class="g-a-filter-label__block"><?php endif; ?>
                    <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                        <label><?php et('sports-history.filters.sports') ?></label>
                        <div <?= !empty($mobile_suffix) ? 'class="g-a-select--mobile--wrapper"' : '' ?>>
                            <select class="g-a-select g-a-select<?= $mobile_suffix ?>" name="sport">
                                <?php if (!in_array($sport, array_keys($sports), true)) { ?>
                                    <option selected="selected" value=""><?php et('sports-history.filters.sports.all') ?></option>
                                    <?php foreach ($sports as $k => $p) { ?>
                                        <option value="<?= $k ?>"><?= ucfirst(t($p)) ?></option>
                                    <?php }
                                } else { ?>
                                    <option value=""><?php et('sports-history.filters.sports.all') ?></option>
                                    <?php foreach ($sports as $k => $p) { ?>
                                        <option <?php echo $sport == $k ? 'selected=selected' : '' ?> value="<?= $k ?>"><?= ucfirst(t($p)) ?></option>
                                    <?php }
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                        <label><?php et('sports-history.filters.types') ?></label>
                        <div <?= !empty($mobile_suffix) ? 'class="g-a-select--mobile--wrapper"' : '' ?>>
                            <select class="g-a-select g-a-select<?= $mobile_suffix ?>" name="bet_type">
                                <?php if (!in_array($bet_type, array_keys($bet_types), true)) { ?>
                                    <option selected="selected" value=""><?php et('sports-history.filters.types.all') ?></option>
                                    <?php foreach ($bet_types as $p) { ?>
                                        <option value="<?php echo $p ?>"><?= ucfirst(t("sports-history.filters.types.$p")) ?></option>
                                    <?php }
                                } else { ?>
                                    <option value=""><?php et('sports-history.filters.types') ?></option>
                                    <?php foreach ($bet_types as $p) { ?>
                                        <option <?php echo $bet_type == $p ? 'selected=selected' : '' ?> value="<?php echo $p ?>"><?= ucfirst(t("sports-history.filters.types.$p")) ?></option>
                                    <?php }
                                } ?>
                            </select>
                        </div>
                    </div>
                    <?php if (phive()->isMobile()): ?></div><?php endif; ?>

                    <?php if (phive()->isMobile()): ?><div class="g-a-filter-label__block"><?php endif ?>
                        <div class="g-a-filter-container<?= $mobile_suffix ?>">
                            <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                                <label><?php et('from') ?></label>
                                <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" />
                            </div>
                            <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                                <label><?php et('to') ?></label>
                                <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" />
                            </div>
                        </div>
                    <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?> g-a-filter-label__search<?= $mobile_suffix ?>">
                        <?php if (phive()->isMobile()): ?>
                            <button id="btn-search" class="btn g-a-btn g-h-btn-search<?= $mobile_suffix ?>">
                                <div class="icon icon-vs-search">
                                    <?php echo t('search'); ?>
                                </div>
                            </button>
                        <?php else: ?>
                            <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                        <?php endif ?>
                    </div>
                    <?php if (phive()->isMobile()): ?>
                        <div class="g-a-filter-label__block">
                    <?php endif ?>
                </form>
                <?php if (phive()->isMobile()): ?>
                    <div class="g-a-container-mobile">
                        <div class="g-a-item-mobile">
                            <span><?= et('sports-history.my.all.time.staked') ?></span>
                            <span><?= $stats['staked'] ?></span>
                        </div>
                        <div class="g-a-item-mobile">
                            <span><?= et('sports-history.my.all.time.won') ?></span>
                            <span><?= $stats['won'] ?></span>
                        </div>
                        <div class="g-a-item-mobile">
                            <span><?= et('sports-history.my.all.time.void') ?></span>
                            <span><?= $stats['void'] ?></span>
                        </div>
                        <div class="g-a-item-mobile">
                            <span><?= et('sports-history.my.all.time.lost') ?></span>
                            <span><?= $stats['lost'] ?></span>
                        </div>
                    </div> <!-- This is the closing tag for .g-a-filter-label__block --->
                </div>
                <?php else: ?>
                    <style>
                    .g-a-item {width: 150px}
                    </style>
                    <div class="g-a-container"><br/>
                        <div class="g-a-item">
                            <h3><?= $stats['staked'] ?></h3><br/><h6><?php et('sports-history.my.all.time.staked') ?></h6>
                        </div>
                        <div class="g-a-item">
                            <h3><?= $stats['won'] ?></h3><br/><h6><?php et('sports-history.my.all.time.won') ?></h6>
                        </div>
                        <div class="g-a-item">
                            <h3><?= $stats['void'] ?></h3><br/><h6><?php et('sports-history.my.all.time.void') ?></h6>
                        </div>
                        <div class="g-a-item">
                            <h3><?= $stats['lost'] ?></h3><br/><h6><?php et('sports-history.my.all.time.lost') ?></h6>
                        </div>
                    </div>
                <?php endif ?>
                <br style="clear: both"/>
            </div>
            <br style="clear: both"/>
        </div>
        <br style="clear: both" />
        <div class="simple-box pad-stuff-ten">
            <h3 style="float: left; margin-top: 15px;"><?php et('sports-history.betslip') ?></h3>
            <br style="clear: both" />
            <table class="account-tbl sb-history-tbl">
                <tr>
                    <td style="vertical-align: top;">
                        <table class="zebra-tbl">
                            <col width="<?php echo $cols[0] ?>"/>
                            <col width="<?php echo $cols[1] ?>"/>
                            <col width="<?php echo $cols[2] ?>"/>
                            <col width="<?php echo $cols[3] ?>"/>
                            <tr class="zebra-header">
                                <td><?php et('trans.time') ?></td>
                                <td><?php et('sports-history.betslip.id') ?></td>
                                <td><?php et('sports-history.betslip.stake') ?></td>
                                <td><?php et('sports-history.betslip.status') ?></td>
                                <td></td>
                            </tr>
                            <?php foreach ($transactions as $i => $t): ?>
                                <?php
                                    $result = $this->calculateBetResult($t,$wins);
                                 ?>
                                <tr
                                    class="zebra-tbl-body__row"
                                    <?php if(phive()->isMobile()) echo "onclick='showBetSlipReceipt({$t['id']}, {$t['user_id']})'" ?>
                                >
                                    <td><?= lcDate($t['created_at']) ?></td>
                                    <td><?= $t['ticket_id'] ?></td>
                                    <td><?= cs() ?> <?= nfCents($t['amount']) ?></td>
                                    <td><?= (!$result['bet_win']) ? '' : cs() ?> <?= (!$result['bet_win']) ? ((!$result['bet_lost'])? t('sports-history.betslip.open') : cs() . ' ' . 0) : nfCents($result['bet_win']) ?></td>
                                    <td>
                                        <button
                                            id="btn-search" class="g-a-btn g-a-btn-arrow"
                                            <?php if(!phive()->isMobile()) echo "onclick='showBetSlipReceipt({$t['id']}, {$t['user_id']})'" ?>
                                        >
                                            <img src="/diamondbet/images/right-arrow.png" alt="" width="12">
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    <?php
    $this->p->render("", "&sport={$sport}&bet_type={$bet_type}&start_date={$start_date}&end_date={$end_date}");
    }

  function printSupertipsetBettingHistoryHTML($params)
  {
      extract($params);
      $mobile_suffix = phive()->isMobile() ? "--mobile" : "";
      loadCss("/diamondbet/css/" . brandedCss() . "g-a-history.css");
      loadCss('/diamondbet/css/bet-receipt.css');
      /** @var Sportsbook $sportsbook */
      $sportsbook = phive('Micro/Sportsbook')->init($start_date, $end_date, $user_id);
      $ticket_ids = array_column($params['transactions'], 'ticket_id');
      $wins = array_merge(
          $sportsbook->getByBetTypeAndProductForTicketIds('win', $ticket_ids, true, Sportsbook::POOL_BET_PRODUCT),
          $sportsbook->getByBetTypeAndProductForTicketIds('void', $ticket_ids, true,Sportsbook::POOL_BET_PRODUCT),
      );

      ?>
      <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.min.css">
      <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
      <script type="text/javascript">
          $(document).ready(function () {
              $('input.g-a-date').datepicker({
                  showButtonPanel: false,
                  dateFormat: 'yy-mm-dd'
              });
          });
          function showBetSlipReceipt(id, user_id) {
              extBoxAjax('get_raw_html', 'mbox-msg', {module: 'Micro', file: 'bet_receipt', id: id, user_id: user_id});
              var betslipsContainer = $('.bet-receipt');
              var hasVerticalScrollbar = betslipsContainer.scrollHeight > betslipsContainer.clientHeight;
              if(hasVerticalScrollbar) {
                  var scrollbarWidth = betslipsContainer.offsetWidth - betslipsContainer.clientWidth;
                  $('.lic-mbox-close-box').marginRight(scrollbarWidth);
              }
          }
      </script>

      <style>
        .g-a-filter-label__block {flex-direction: column}
      </style>

      <div class="general-account-holder">
          <div class="simple-box pad-stuff-ten">
              <h3><?php et('supertipset-history.my.all.time') ?></h3>
              <form class="g-a-filter-form" id="form" method="get" autocomplete="off">
                  <?php if (phive()->isMobile()): ?><div class="g-a-filter-label__block"><?php endif ?>
                      <div class="g-a-filter-container<?= $mobile_suffix ?>">
                          <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                              <label><?php et('from') ?></label>
                              <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" />
                          </div>
                          <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?>">
                              <label><?php et('to') ?></label>
                              <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" />
                          </div>
                      </div>
                      <div class="g-a-filter-label g-a-filter-label<?= $mobile_suffix ?> g-a-filter-label__search<?= $mobile_suffix ?>">
                          <?php if (phive()->isMobile()): ?>
                              <button id="btn-search" class="btn g-a-btn g-h-btn-search<?= $mobile_suffix ?>">
                                  <div class="icon icon-vs-search">
                                      <?php echo t('search'); ?>
                                  </div>
                              </button>
                          <?php else: ?>
                              <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                          <?php endif ?>
                      </div>
                      <?php if (phive()->isMobile()): ?>
                      <div class="g-a-filter-label__block">
                          <?php endif ?>
              </form>
              <?php if (phive()->isMobile()): ?>
              <div class="g-a-container-mobile">
                  <div class="g-a-item-mobile">
                      <span><?= et('sports-history.my.all.time.staked') ?></span>
                      <span><?= $stats['staked'] ?></span>
                  </div>
                  <div class="g-a-item-mobile">
                      <span><?= et('sports-history.my.all.time.won') ?></span>
                      <span><?= $stats['won'] ?></span>
                  </div>
                  <div class="g-a-item-mobile">
                      <span><?= et('sports-history.my.all.time.void') ?></span>
                      <span><?= $stats['void'] ?></span>
                  </div>
                  <div class="g-a-item-mobile">
                      <span><?= et('sports-history.my.all.time.lost') ?></span>
                      <span><?= $stats['lost'] ?></span>
                  </div>
              </div> <!-- This is the closing tag for .g-a-filter-label__block --->
          </div>
          <?php else: ?>
              <style>
                  .g-a-item {width: 150px}
              </style>
              <div class="g-a-container"><br/>
                  <div class="g-a-item">
                      <h3><?= $stats['staked'] ?></h3><br/><h6><?php et('sports-history.my.all.time.staked') ?></h6>
                  </div>
                  <div class="g-a-item">
                      <h3><?= $stats['won'] ?></h3><br/><h6><?php et('sports-history.my.all.time.won') ?></h6>
                  </div>
                  <div class="g-a-item">
                      <h3><?= $stats['void'] ?></h3><br/><h6><?php et('sports-history.my.all.time.void') ?></h6>
                  </div>
                  <div class="g-a-item">
                      <h3><?= $stats['lost'] ?></h3><br/><h6><?php et('sports-history.my.all.time.lost') ?></h6>
                  </div>
              </div>
          <?php endif ?>
          <br style="clear: both"/>
      </div>
      <br style="clear: both"/>
      </div>
      <br style="clear: both" />
      <div class="simple-box pad-stuff-ten">
          <h3 style="float: left; margin-top: 15px;"><?php et('sports-history.betslip') ?></h3>
          <br style="clear: both" />
          <table class="account-tbl sb-history-tbl">
              <tr>
                  <td style="vertical-align: top;">
                      <table class="zebra-tbl">
                          <col width="<?php echo $cols[0] ?>"/>
                          <col width="<?php echo $cols[1] ?>"/>
                          <col width="<?php echo $cols[2] ?>"/>
                          <col width="<?php echo $cols[3] ?>"/>
                          <tr class="zebra-header">
                              <td><?php et('trans.time') ?></td>
                              <td><?php et('sports-history.betslip.id') ?></td>
                              <td><?php et('sports-history.betslip.stake') ?></td>
                              <td><?php et('sports-history.betslip.status') ?></td>
                              <td></td>
                          </tr>
                          <?php foreach ($transactions as $i => $t): ?>
                              <?php
                                $result = $this->calculateBetResult($t,$wins);
                              ?>
                              <tr
                                  class="zebra-tbl-body__row"
                                  <?php if(phive()->isMobile()) echo "onclick='showBetSlipReceipt({$t['id']}, {$t['user_id']})'" ?>
                              >
                                  <td><?= lcDate($t['created_at']) ?></td>
                                  <td><?= $t['ticket_id'] ?></td>
                                  <td><?= cs() ?> <?= nfCents($t['amount']) ?></td>
                                  <td><?= (!$result['bet_win']) ? '' : cs() ?> <?= (!$result['bet_win']) ? ((!$result['bet_lost'])? t('sports-history.betslip.open') : cs() . ' ' . 0) : nfCents($result['bet_win']) ?></td>
                                  <td></td>
                              </tr>
                          <?php endforeach ?>
                      </table>
                  </td>
              </tr>
          </table>
      </div>
      <?php
      $this->p->render("", "&start_date={$start_date}&end_date={$end_date}");
  }

    private function calculateBetResult(array $transaction, array $wins): array
    {
        $bet_win = null;
        $bet_lost = false;

        $win = array_search($transaction['ticket_id'], array_column($wins, 'ticket_id'));

        if (count($win) > 0 && !is_bool($win)) {
            $bet_win = $wins[$win]['amount'];
            $bet_lost = false;
        }

        if (!$win && $transaction['ticket_settled']) {
            $bet_lost = true;
            $bet_win = null;
        }

        return [
            'bet_win' => $bet_win,
            'bet_lost' => $bet_lost,
        ];
    }

  function printMyBonuses($tbl = false, $count = 6, $op = 'getUserBonuses'){
    $this->printBonusJs();
    $bonuses = phive('Bonuses')->$op( $this->cur_user->getId(), '', '', "IN('casino', 'casinowager')", true );
    $this->p->setPages(count($bonuses), '', $count);
    $bonuses = array_slice($bonuses, $this->p->getOffset($count), $count);
      ?>
      <div class="general-account-holder">
        <?php if ($tbl) :?><table>
        <tr>
          <th><?php et('bonus') ?></th>
          <th><?php et('bonus.status') ?></th>
          <th><?php et('days.left') ?></th>
          <th><?php et('bonus.activation.time') ?></th>
          <th><?php et('bonus.progress') ?></th>
        </tr>
        <?php endif ?>
        <?php
        foreach( $bonuses as $b )
          $this->printBonus($b);
        ?>
        <?php if ($tbl) :?></table><?php endif ?>
      </div>
    <?php $this->p->render('', "&action={$_GET['action']}") ?>
  <?php }

    /**
     * @api
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\GameHistoryData $data
     *
     * @return array
     * @throws Exception
     */
    public function getGameHistory(GameHistoryData $gameHistoryData): array
    {
        $user_id    = $this->cur_user->getId();
        $start_date = phive()->validateDate($gameHistoryData->getStartDate()) ?
            phive()->fDate($gameHistoryData->getStartDate()) :
            phive()->modDate(null, '-1 day');
        $end_date   = phive()->validateDate($gameHistoryData->getEndDate()) ?
            phive()->fDate($gameHistoryData->getEndDate()) :
            phive()->modDate(null, '+1 day');

        $game = $provider = '';
        $game_id = $mobile = 0;
        $game_name = "";

        // Format the date so that only the year, month, and day appear.
        // I saw during my work that on occasion the $end_date variable will have the time appended to the end of the input, this will prevent this from happening.
        $start_date = (new DateTime($start_date))->format(("Y-m-d"));
        $end_date = (new DateTime($end_date))->format("Y-m-d");
        $game_categories = lic('getAccountGameTypeFilters');
        $game_category = array_key_exists($gameHistoryData->getGameCategory(), $game_categories) ? $gameHistoryData->getGameCategory() : '';
        $category_game_refs = [];

        // Being this info on master only we need a separate query to retrieve the game list.
        if(!empty($game_category)){
            $category_game_refs = phive('MicroGames')->getGameRefsFilteredByExpandedCategory($game_category, $mobile);
        }

        if (empty($this->user_games)) {
            $this->user_games = phive('Casino')->getUserGameData($user_id, "{$start_date} 00:00:00", "{$end_date} 23:59:59");
        }
        $user_providers = array_unique(array_column($this->user_games, 'provider'));
        $game_isset     = in_array($gameHistoryData->getGameId(), array_column($this->user_games, 'game_id'));
        $provider_isset = in_array($gameHistoryData->getProvider(), $user_providers, true);
        if ($game_isset) {
            $game_id = $gameHistoryData->getGameId();
            foreach ($this->user_games as $g) {
                if ($game_id == $g['game_id']) {
                    $game     = $g['game_ref'];
                    $mobile   = $g['mobile'];
                    $game_name = $g['game_name'];
                    $provider = $g['provider'];
                    break;
                }
            }
        } elseif ($provider_isset) {
            $provider = $gameHistoryData->getProvider();
        }

        /* There were inconsistencies shown in the results fetched by the query vs in the total shown at the top of
           the page. I have chosen under advice to discard this query and calculate the results from the other query.
           This code is commented out as opposed to being removed as a precaution. */
        //$data = phive('Casino')->getSumsBetsWinsByUserId($user_id, "{$start_date} 00:00:00", "{$end_date} 23:59:59", $game, $mobile, $provider, $category_game_refs);
        //$sum_wins = $data['sum_wins'];
        //$sum_bets = $data['sum_bets'];

        $sum_wins = $this->mg->getWins($this->cur_user, '', true, $category_game_refs, $provider, $game_name, $start_date, $end_date);
        $sum_bets = $this->mg->getBets($this->cur_user, '', true, $category_game_refs, $provider, $game_name, $start_date, $end_date);
        $losses = $this->mg->getRoundsLosses($this->cur_user, $provider, $game_name, $start_date, $end_date);

        $total = 0;
        foreach ($sum_wins as $win)   // Here we just take the results from the results of the queries
            $total += $win['amount']; // and loop through the resulting arrays adding up the amount for each.
        $sum_wins = $total;           // Total is a placeholder variable we can use to count from zero.
        $total = 0;
        foreach ($sum_bets as $bet)
            $total += $bet['amount'];
        $sum_bets = $total;
        $total = 0;
        foreach ($losses as $loss) {
            $total += abs(intval($loss['net']));
        }
        $sum_losses = $total;

        $bets = $this->mg->getBets($this->cur_user, 100, true, $category_game_refs, $provider, $game_name, $start_date, $end_date);
        $wins = $this->mg->getWins($this->cur_user, 100, true, $category_game_refs, $provider, $game_name, $start_date, $end_date);

        $total = 0;
        $filterBy = $gameHistoryData->getFilterBy();
        if(!$filterBy){
            $total = count(array_merge($bets, $wins));
        }
        else{
            $total = count(($filterBy == 'bets') ? $bets : $wins);
        }

        $limit = $gameHistoryData->getLimit();
        $this->p->setPages($total, '', $limit);
        $offs = is_numeric($gameHistoryData->getPage())
            ? ((int) $gameHistoryData->getPage() - 1) *  $limit
            : 0;

        return [
            'provider'       => $provider,
            'user_games'     => $this->user_games,
            'user_providers' => $user_providers,
            'game'           => $game,
            'game_id'        => $game_id,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'wins'           => array_slice($wins, $offs, $limit),
            'sum_wins'       => efEuro($sum_wins, true),
            'bets'           => array_slice($bets, $offs, $limit),
            'sum_bets'       => efEuro($sum_bets, true),
            'sum_net'        => efEuro($sum_wins - $sum_bets, true),
            'sum_losses'     => efEuro($sum_losses, true),
            'offs'           => $offs,
            'game_category'  => $game_category,
            'game_categories'=> $game_categories,
            'pages_count' =>  (int) ceil($total / $limit),
            'limit' => $limit,
            'page' => (int) $gameHistoryData->getPage(),
            'total'=> $total,
        ];
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\GameHistoryData $data
     * @throws Exception
     */
    public function printGameHistory(GameHistoryData $gameHistoryData)
    {
        $game_history = $this->getGameHistory($gameHistoryData);
        $this->printGameHistoryHTML($game_history);
    }

    function printGameHistoryHTML($params){
      extract($params);
        loadCss("/diamondbet/css/" . brandedCss() . "g-a-history.css");

      // Clean up the URLs
      $start_date_GET_Request = empty($start_date) ? "" : "&start_date=$start_date";
      $end_date_GET_Request = empty($end_date) ? "" : "&end_date=$end_date";
      $provider_GET_Request = empty($provider) ? "" : "&provider=$provider";
      $game_id_GET_Request = empty($game_id) ? "" : "&game_id=$game_id";

        ?>
      <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.min.css">
      <link rel="stylesheet" type="text/css" href="/phive/js/jQuery-UI/<?= $this->new_version_jquery_ui ?>jquery-ui.theme.min.css?v3">
      <script type="text/javascript">
      $(document).ready(function () {
          $('input.g-a-date').datepicker({
              showButtonPanel: false,
              dateFormat: 'yy-mm-dd'
          });
      });
      </script>
      <div class="general-account-holder">

      <?php if(empty(phive()->getSetting('hide_all_time'))): ?>
          <div class="simple-box pad-stuff-ten">
              <h3 class="g-a-title"><?php et('my.all.time') ?></h3>
              <form class="g-a-filter-form <?php echo !empty($game_categories) ? 'game-type' : '' ?>" action="" method="get" autocomplete="off">
                <?php if (phive()->isMobile()): ?>
                  <div class="g-a-filter-label__block">
                    <?php if (!empty($game_categories)): ?>
                        <div class="g-a-filter-label g-a-filter-label--mobile">
                          <label><?php et('game.category') ?></label>
                          <div class="g-a-select--mobile--wrapper">
                              <?php dbSelect('game_category', $game_categories, $game_category, array( '', t('all.game.categories') ), 'g-a-select g-a-select--mobile') ?>
                          </div>
                        </div>
                    <?php endif ?>
                    <div class="g-a-filter-label g-a-filter-label--mobile">
                      <label><?php et('all.networks') ?></label>
                      <div class="g-a-select--mobile--wrapper">
                          <select class="g-a-select g-a-select--mobile" name="provider"> <?php
                              if (empty($provider)) { ?>
                                  <option selected="selected" value=""><?php et('all.networks') ?></option>
                                  <?php foreach ($user_providers as $p) { ?>
                                      <option value="<?php echo $p ?>"><?php echo ucfirst($p) ?></option>
                                  <?php }
                              } else { ?>
                                  <option value=""><?php et('all.networks') ?></option>
                                  <?php foreach ($user_providers as $p) { ?>
                                      <option <?php echo $provider === $p ? 'selected=selected' : '' ?> value="<?php echo $p ?>"><?php echo ucfirst($p) ?></option>
                                  <?php }
                              } ?>
                          </select>
                      </div>
                  </div>
                  <div class="g-a-filter-label g-a-filter-label--mobile">
                    <label><?php et('all.last.played') ?></label>
                    <div class="g-a-select--mobile--wrapper">
                        <select class="g-a-select g-a-select--mobile" name="game_id" > <?php
                            if (empty($game)) { ?>
                                <option value="" selected="selected"><?php et('mgchoose.all.headline') ?></option>
                                <?php foreach ($user_games as $g) {
                                    if ($g['provider'] === $provider || $provider === '') { ?>
                                        <option value="<?php echo $g['game_id'] ?>"><?php echo ($g['mobile'] === "1") ? "{$g['game_name']} (mobile)" : $g['game_name'] ?></option>
                                    <?php } }
                            } else { ?>
                                <option value=""><?php et('mgchoose.all.headline') ?></option>
                                <?php foreach ($user_games as $g) {
                                    if ($g['provider'] === $provider || $provider === '') { ?>
                                        <option <?php echo $game_id == $g['game_id'] ? 'selected=selected' : '' ?> value="<?php echo $g['game_id'] ?>"><?php echo ($g['mobile'] === "1") ? "{$g['game_name']} (mobile)" : $g['game_name'] ?></option>
                                    <?php } }
                            } ?>
                        </select>
                    </div>
                  </div>
                  </div>
                <?php else: ?>
                  <?php if (!empty($game_categories)): ?>
                      <div class="g-a-filter-label">
                          <label><?php et('game.category') ?></label>
                          <?php dbSelect('game_category', $game_categories, $game_category, array( '', t('all.game.categories') ), 'g-a-select') ?>
                      </div>
                  <?php endif ?>
                  <div class="g-a-filter-label">
                      <label><?php et('all.networks') ?></label>
                      <select class="g-a-select" name="provider"> <?php
                          if (empty($provider)) { ?>
                              <option selected="selected" value=""><?php et('all.networks') ?></option>
                              <?php foreach ($user_providers as $p) { ?>
                                  <option value="<?php echo $p ?>"><?php echo ucfirst($p) ?></option>
                              <?php }
                          } else { ?>
                              <option value=""><?php et('all.networks') ?></option>
                              <?php foreach ($user_providers as $p) { ?>
                                  <option <?php echo $provider === $p ? 'selected=selected' : '' ?> value="<?php echo $p ?>"><?php echo ucfirst($p) ?></option>
                              <?php }
                          } ?>
                      </select>
                  </div>
                  <div class="g-a-filter-label">
                      <label><?php et('all.last.played') ?></label>
                      <select class="g-a-select" name="game_id" > <?php
                          if (empty($game)) { ?>
                              <option value="" selected="selected"><?php et('mgchoose.all.headline') ?></option>
                              <?php foreach ($user_games as $g) {
                                        if ($g['provider'] === $provider || $provider === '') { ?>
                                            <option value="<?php echo $g['game_id'] ?>"><?php echo ($g['mobile'] === "1") ? "{$g['game_name']} (mobile)" : $g['game_name'] ?></option>
                              <?php } }

                          }
                          else { ?>
                              <option value=""><?php et('mgchoose.all.headline') ?></option>
                              <?php foreach ($user_games as $g) {
                                        if ($g['provider'] === $provider || $provider === '') { ?>
                                            <option <?php echo $game_id == $g['game_id'] ? 'selected=selected' : '' ?> value="<?php echo $g['game_id'] ?>"><?php echo ($g['mobile'] === "1") ? "{$g['game_name']} (mobile)" : $g['game_name'] ?></option>
                              <?php } }
                          }
                          ?>
                      </select>

                  </div>
                <?php endif ?>
                  <?php if (phive()->isMobile()): ?>
                    <div class="g-a-filter-label__block">
                        <div class="g-a-filter-container--mobile">
                            <div class="g-a-filter-label g-a-filter-label--mobile">
                                <label><?php et('from') ?></label>
                                <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" ></input>
                            </div>
                            <div class="g-a-filter-label g-a-filter-label--mobile">
                                <label><?php et('to') ?></label>
                                <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" ></input>
                            </div>
                        </div>
                        <div class="g-a-filter-label g-a-filter-label--mobile">
                            <button id="btn-search" class="btn g-h-btn-search--mobile">
                                <div class="icon icon-vs-search">
                                    <?php echo t('search'); ?>
                                </div>
                            </button>
                        </div>
                    </div>
                  <?php else: ?>
                    <div class="g-a-filter-label">
                          <label><?php et('from') ?></label>
                          <input class="g-a-date" type="text" name="start_date" value="<?php echo $start_date ?>" ></input>
                      </div>
                    <div class="g-a-filter-label">
                          <label><?php et('to') ?></label>
                          <input class="g-a-date" type="text" name="end_date" value="<?php echo $end_date ?>" ></input>
                      </div>
                    <div class="g-a-filter-label">
                          <button id="btn-search" class="g-a-btn g-a-btn-search icon icon-vs-search"></button>
                    </div>
                <?php endif ?>
              </form>
              <?php if (phive()->isMobile()): ?>
                <div class="g-a-container-mobile">
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.wagered') ?></span>
                        <span><?= $sum_bets ?></span>
                    </div>
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.winnings') ?></span>
                        <span><?= $sum_wins ?></span>
                    </div>
                    <?php if (licSetting('game_history')['show_total_losses']): ?>
                        <div class="g-a-item-mobile">
                            <span><?= et('my.all.time.losses') ?></span>
                            <span><?= $sum_losses ?></span>
                        </div>
                    <?php endif ?>
                    <div class="g-a-item-mobile">
                        <span><?= et('my.all.time.net') ?></span>
                        <span><?= $sum_net ?></span>
                    </div>
                </div>
            <?php else: ?>
              <div class="g-a-container">
                <div class="g-a-item">
                    <h3><?php echo $sum_bets ?></h3><br/><h6><?php et('my.all.time.wagered') ?></h6>
                </div>
                <div class="g-a-item">
                    <h3><?php echo $sum_wins ?></h3><br/><h6><?php et('my.all.time.winnings') ?></h6>
                </div>
                <?php if (licSetting('game_history')['show_total_losses']): ?>
                  <style>
                      .g-a-item {width: 150px}
                  </style>
                  <div class="g-a-item">
                      <h3><?php echo $sum_losses ?></h3><br/><h6><?php et('my.all.time.losses') ?></h6>
                  </div>
                <? endif; ?>
                <div class="g-a-item">
                    <h3><?php echo $sum_net ?></h3><br/><h6><?php et('my.all.time.net') ?></h6>
                </div>
              </div>
            <? endif; ?>
          </div>
          <br/>
          <?php endif; ?>
          <?php foreach(array('wins' => $wins, 'wagers' => $bets) as $str => $transactions): ?>
          <div class="simple-box pad-stuff-ten">
              <h3><?php et($str) ?></h3>
              <table class="account-tbl">
                <tr>
                  <td style="vertical-align: top;">
                    <table class="zebra-tbl">
                      <col width="180"/>
                      <col width="340"/>
                      <col width="140"/>
                      <tr class="zebra-header">
                        <td><?php echo t('trans.time') ?></td>
                        <td><?php echo t('game.name') ?></td>
                        <td><?php echo t("$str.amount") ?></td>
                      </tr>
                      <?php $i = 0; foreach($transactions as $row): ?>
                        <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
                          <td><?php echo phive()->lcDate($row['created_at']) .' '.t('cur.timezone') ?></td>
                          <td><?php echo $row['game_name'] ?></td>
                          <td><?php echo cs().' '.rnfCents($this->nullToZero($row['amount']), ".", "") ?></td>
                        </tr>
                      <?php $i++; endforeach ?>
                    </table>
                  </td>
                </tr>
              </table>
          </div>
          <br/>
          <?php endforeach ?>
          <?php licHtml('game_session_balances'); ?>
          <?php
              $GET_Requests = "$game_id_GET_Request$provider_GET_Request$start_date_GET_Request$end_date_GET_Request";
              $this->p->render('', "&action={$_GET['action']}$GET_Requests");
          ?>
      </div>
      <br/>
      <br/>
    <?php }

  function printTopMenu(){
    if($this->page != '' && in_array($this->site_type, array('mobile', 'ipad')))
            return;
    if($this->page === 'update-account') return;

    $acc_menu = phive('Menuer')->forRender('top-account-menu', '', true, cuPlId());
    ?>
    <div class="left" style="padding-bottom: 20px;">
    <?php foreach($acc_menu as $item): ?>
      <div class="left margin-twenty-left">
        <?php if($item['current']): ?>
          <?php btnDefaultL($item['txt'], $item['plink'], '', 142) ?>
        <?php else: ?>
          <?php btnCancelL($item['txt'], $item['plink'], '', 142) ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    </div>
  <?php }

  function printBankMenu(){
    if($this->page != '' && in_array($this->site_type, array('mobile', 'ipad')))
      return;
      ?>
    <div class="left pad-top-bottom" style="margin-left: 80px;">
      <div class="left margin-twenty-left">
        <?php btnDefaultXl(t('deposit'), '', depGo(), 142) ?>
      </div>
      <div class="left margin-twenty-left">
        <?php btnDefaultXl(t('withdraw'), '', withdrawalGo(), 142) ?>
      </div>

      <div class="left margin-twenty-left">
      <?php $user_id = cuPlId(); ?>
        <?php btnDefaultXl(t('documents'), $this->loc->fullLangLink("/account/{$user_id}/documents/"), '', 142) ?>
      </div>

    </div>
    <?php
  }

    public function printDetails()
    {
        $service = new ProfileService($this);
        $data = $service->getProfileData();
        ?>
        <div class="simple-box" style="margin-left: 5px; padding: 10px;">
            <?php if ($this->site_type == "mobile"): ?>
                <h3><?= t('my-profile') ?></h3>
            <?php endif; ?>
            <table class="zebra-tbl">
                <col width="25"/>
                <col width="295"/>
                <col width="295"/>
                <col width="25"/>
                <?php foreach ($data as $i => $item): ?>
                    <?php $this->drawProfileInfo($i, $item) ?>
                <?php endforeach ?>
            </table>
        </div>
    <?php }

  function printProfile(){
    $pendings = phive('Cashier')->getPendingsUser($this->cur_user->getId(), " IN ('pending', 'processing') ");

    if(phive()->moduleExists('Trophy')){
        if(phive()->isMobile()) {
            $trophy_box = phive('BoxHandler')->getRawBox('MobileTrophyListBox');
        } else {
            $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        }
        $trophy_box->init($this->cur_user);
        $trophy_box->printRewardsPage($this->cur_user, $pendings);
        return;
    }

    // Is the code below never used anymore?
    $this->handleCancelPending();

    $this->no_regupdate = true;
    ?>

    <?php $this->printTopMenu() ?>

    <br clear="all" />
    <?php $this->drawCurrentBalances() ?>

    <?php if(!empty($pendings)): ?>
      <br clear="all" />
      <div class="simple-box" style="margin-left: 5px; padding: 10px;">
        <?php $this->printTrTable($pendings, 'pending.withdrawals') ?>
      </div>
    <?php endif ?>

    <?php $this->drawRecentAccHistory() ?>

    <?php $this->printBankMenu() ?>
    <br clear="all"/>
    <br clear="all"/>
  <?php }

  function printMainMenu(){
      if($this->page != '' && in_array($this->site_type, array('mobile', 'ipad ')))
        return;

      $this->acc_menu = $this->site_type == 'mobile' ? 'mobile-account-menu' : 'account-menu' ;
      $acc_menu = phive('Menuer')->forRender($this->acc_menu, 'my-profile', true, $this->cur_user->getId());
  ?>
    <div class="acc-left-headline"><?php echo t('my.profile') ?></div>
    <ul>
    <?php foreach ($acc_menu as $item) {
        $link = $item['params'];

        if (strpos($link, 'withdraw') !== false) {
            $action = withdrawalGo();
            $link = "onclick='$action'";
        }

        if (strpos($link, 'deposit') !== false) {
            $action = depGo();
            $link = "onclick='$action'";
        }
        ?>
        <li>
          <a <?php echo $link ?>>
            <?php echo $item['current'] ? '&raquo;' : '' ?>
            <?php echo $item['txt']?>
            <?php echo $item['current'] ? '&laquo;' : '' ?>
          </a>
        </li>
      <?php } ?>
    </ul>
  <?php }

  public function printHTML(){

    setCur($this->cur_user);

    if($_GET['signout'] == 'true'){
      phive('UserHandler')->logout('logout');
      $this->jsRedirect('/');
      return;
    }

    if($this->canView() !== false){
      //$acc_menu = phive('Menuer')->forRender($this->acc_menu, 'my-profile', true, $this->username);
      ?>

      <div class="container-acc-holder">
      <div class="boxes-acc-container">
      <?php if(!in_array($this->page, ['documents', 'rtp'])): ?>
          <?php if($this->site_type == 'mobile' && $this->page != ''): ?>
              <?php $this->printMainMenu() ?>
          <?php else: ?>
              <div class="acc-left-menu">
                <?php $this->printMainMenu() ?>
            </div>
        <?php endif ?>
        <div class="acc-right-content">
          <?php $this->switchAction() ?>
        </div>
      <?php else: ?>
        <?php $this->switchAction() ?>
      <?php endif ?>
      </div>
      </div>

      <?php
    }else
      $this->jsRedirect('/');

  }

  function printAdmin($action = '', $show = true, $get_pendings = true, $return = false, $handle_post = true){
    return parent::printAdmin($action, $show, $get_pendings, $return, $handle_post);
  }


  public function checkVoucherCode($action, $allowed_captcha_attempt)
  {
	  $redeem_result = phive('Vouchers')->redeem($this->cur_user->getId(), $_POST['vcode'], $_POST['vcode']);
	  if($redeem_result !== true) {
		  $result = $redeem_result;
		  limitAttempts($action, $_REQUEST['captcha_code'], $allowed_captcha_attempt);
	  } elseif($GLOBALS['bonus_activation'] === false) {
		  $result = 'bonus.activation.failed.html';
		  limitAttempts($action, $_REQUEST['captcha_code'], $allowed_captcha_attempt);
	  } else {
		  $result = 'voucher.redeem.success';
	  }

      return $result;
  }

  public function checkCaptchaCode($action, &$show_captcha)
  {
	  $result = false;

	  if(PhiveValidator::captchaCode() !== $_POST['captcha_code']) {
		  $result = 'voucher.captcha.error';
		  $show_captcha = true;
	  } else {
		  $this->refreshVoucherForm($action);
	  }

      return $result;
  }

  function voucherCheckData(&$show_captcha)
  {
	  $allowed_captcha_attempt = phive()->getSetting('allowed_captcha_attempt', 5);
	  $action = $_REQUEST['action'] ?? 'voucher';
      $return = false;

	  if(isset($_POST['vcode'])) {
		  $return = $this->checkVoucherCode($action, $allowed_captcha_attempt);
	  } elseif (isset($_POST['captcha_code'])) {
		  $return = $this->checkCaptchaCode($action, $show_captcha);
	  } elseif(!empty($_POST['submit'])) {
		  $return = 'voucher.empty.code.or.name';
	  }

	  if (!$show_captcha && getLimitAttemptCount($action) > $allowed_captcha_attempt) {
		  $show_captcha = true;
		  $return = 'voucher.captcha.header.text';
	  }

      return $return;
  }

  public function printVouchers()
  {
	  $show_captcha = false;

      $msg = $this->voucherCheckData($show_captcha);

	  if(phive('Bonuses')->hasActiveExclusives($this->cur_user->getId()) && empty($_POST['vcode'])) {
		  $msg2 = t('has.active.exclusives');
	  }

      ?>

      <script>
        jQuery(document).ready(function(){
          $("#voucher-submit").click(function(event){
            <?php if(!empty($msg2)): ?>
              event.preventDefault();
              fancyShow($(".errors").html());
            <?php endif ?>
          });
            if (isAndroid()) {
                $('.voucher-box').addClass('width-max');
            }
        });
      </script>

      <div class="general-account-holder voucher-box">
          <div class="simple-box pad-stuff-ten">
              <h3><?php echo t('redeem.headline') ?></h3>
              <?php if(!empty($msg)): ?>
                  <div class="errors">
                      <?php echo t($msg) ?>
                  </div>
              <?php endif ?>

              <?php if(!empty($msg2)): ?>
                  <div class="errors">
                      <?php echo $msg2 ?>
                  </div>
              <?php endif ?>
              <form id="voucher-form" method="post" action="">
                  <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                  <div class="registerform">
                      <div class="form-field">
                          <?php if ($show_captcha) : ?>
                              <div class="captcha-container">
                                  <img class="captcha-img" id="captcha_img" alt="Captcha" src="<?= PhiveValidator::captchaImg() ?>">
                                  <div class="captcha-input-wrapper">
                                      <input type="text" class="captcha-input" name="captcha_code" placeholder="<?php echo et('login.captcha.code') ?>" />
                                      <button type="button" class="captcha-reset" onclick="licFuncs.resetCaptcha()"><?php et('reset.code') ?></button>
                                  </div>
                                  <input onclick="voucherSubmit();" id="voucher-submit" type="submit" value="<?= et('submit') ?>" name="submit" class="btn btn-l btn-default-l"/>
                              </div>
                          <?php else: ?>
                              <label for="vcode"><?= t('voucher.code') ?></label>
                              <?php dbInput('vcode', $_POST['vcode']) ?>
                              <input onclick="voucherSubmit();" id="voucher-submit" type="submit" value="<?= et('submit') ?>" name="submit" class="btn btn-l btn-default-l"/>
                          <?php endif ?>
                      </div>
                  </div>
              </form>
          </div>
      </div>
      <?php
  }

    function rgRemoveLimitBtn($type, $id=null){
        if(lic('hideRgRemoveLimit', [$type], $this->cur_user)){
            return;
        }
    ?>
        <button class="btn btn-l btn-default-l w-125 grey-bkg" id='<?php echo $id ?>' onclick="removeRgLimit('<?php echo $type ?>')">
            <?php et('remove.limits') ?>
        </button>
        <?php
    }

    private function canShowCrossBrandLimit($type, $user = null)
    {
        $user = $user ?: $this->cur_user;
        return !empty(lic('showCrossBrandLimitExtra', [$type, $user], $user));
    }

	private function refreshVoucherForm($action)
	{
		PhiveValidator::removeCaptchaSessionData();
		unsetLimitAttemptCount($action);
		$_POST = array();
		echo '<script>window.location.href = window.location.href</script>';
	}

    public function showCrossBrandLimitCheckbox($type, $user = null)
    {
        if(empty($this->canShowCrossBrandLimit($type, $user))) {
            return;
        }
        ?>
        <label class="cross-brand-limit-checkbox-label">
            <input type="checkbox" name="cross-brand-limit-<?= $type ?>" id="cross-brand-limit-<?= $type ?>" value="yes"/>
            <?php et("rg.apply.to.all.accounts.checkbox") ?>
        </label>
        <?php
    }

    public function showCrossBrandLimitText($type, $user = null)
    {
        if(empty($this->canShowCrossBrandLimit($type, $user))) {
            return;
        }
        et("rg.apply.to.all.accounts.explanation");
    }

    /**
     * @deprecated
     */
    function resettableLimit($type){
        $clims      = $this->clims[$type];
        $changes_at = current($clims)['changes_at'];
        $disp_unit  = $this->rg->displayUnit($type, $this->cur_user);
        $is_mobile  = siteType() == 'mobile';
    ?>
        <div class="simple-box pad-stuff-ten">
            <div class="account-headline">
                <?php et("$type.limit.headline") ?>
            </div>
            <?php et2("$type.limit.info.html", ['cooloff_period' => lic('getCooloffPeriod', [$this->cur_user->getCountry()])]) ?>
            <div class="account-sub-box rg-resettable">

                <table class="rg-resettable-tbl">
                    <?php if($is_mobile): ?>

                    <tr>
                        <th>
                            <?php et('my.limits') ?>
                        </th>
                        <?php foreach(['active.limit', 'remaining'] as $alias): ?>
                            <th>
                                <div class="left"><?php et($alias) ?></div>
                                <div class="right">(<?php echo $disp_unit ?>)</div>
                            </th>
                        <?php endforeach ?>
                    </tr>

                    <?php foreach($this->rg->time_spans as $tspan):
                        $rgl = $clims[$tspan];
                    ?>
                        <tr>
                            <td valign="top" style="width: 50px;">
                                <div class="margin-five-top rg-tspan-headline"><?php et("$tspan.ly") ?></div>
                                <div class="margin-five-top rg-tspan-descr"><?php et("$type.$tspan.ly.descr") ?></div>
                            </td>
                            <td valign="top">
                                <?php dbInput("{$type}-{$tspan}-remaining", $this->rg->prettyLimit($type, $rgl['cur_lim']), 'text', 'input-normal input-rg-limit-disabled', 'disabled') ?>
                            </td>
                            <td valign="top">
                                <?php dbInput('', $this->rg->prettyLimit($type, $this->rg->getRemaining($rgl)), 'text', 'input-normal input-rg-limit-disabled', 'disabled') ?>
                            </td>
                        </tr>
                        <?php if(!empty($rgl) && !phive()->isEmpty($rgl['resets_at'])): ?>
                            <tr>
                                <td>
                                    &nbsp;
                                </td>
                                <td colspan="2">
                                    <div class="right">
                                        <span class="vip-color"><?php et('resets.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($rgl['resets_at'], '%x %R') ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endif ?>
                        <tr>
                            <td>
                                <?php et('new.limit') ?>
                            </td>
                            <td colspan="2">
                                <?php dbInput("{$type}-{$tspan}", $this->rg->prettyLimit($type, $rgl['new_lim']), 'text', 'input-normal input-rg-new-limit') ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="height: 20px;">
                                &nbsp;
                            </td>
                        </tr>
                    <?php endforeach ?>


                <?php else: // Desktop ?>

                    <tr>
                        <th>
                            <?php et('my.limits') ?>
                        </th>
                        <?php foreach(['active.limit', 'remaining', 'new.limit'] as $alias): ?>
                            <th>
                                <div class="left"><?php et($alias) ?></div>
                                <div class="right">(<?php echo $disp_unit ?>)</div>
                            </th>
                        <?php endforeach ?>
                    </tr>
                    <?php foreach($this->rg->time_spans as $tspan):
                    $rgl = $clims[$tspan];
                    ?>
                        <tr>
                            <td valign="top">
                                <div class="margin-five-top rg-tspan-headline"><?php et("$tspan.ly") ?></div>
                                <div class="margin-five-top rg-tspan-descr"><?php et("$type.$tspan.ly.descr") ?></div>
                            </td>
                            <td valign="top">
                                <?php dbInput("{$type}-{$tspan}-remaining", $this->rg->prettyLimit($type, $rgl['cur_lim']), 'text', 'input-normal', 'disabled') ?>
                            </td>
                            <td valign="top">
                                <?php dbInput('', $this->rg->prettyLimit($type, $this->rg->getRemaining($rgl)), 'text', 'input-normal', 'disabled') ?>
                                <?php if(!empty($rgl) && !phive()->isEmpty($rgl['resets_at'])): ?>
                                    <span class="vip-color"><?php et('resets.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($rgl['resets_at'], '%x %R') ?></span>
                                <?php endif ?>
                            </td>
                            <td valign="top">
                                <?php dbInput("{$type}-{$tspan}", $this->rg->prettyLimit($type, $rgl['new_lim']), 'text', 'input-normal') ?>
                            </td>
                        </tr>
                    <?php endforeach ?>

                <?php endif ?>
                </table>
                <?php if(!$is_mobile): ?>
                    <br clear="all"/>
                <?php endif ?>
                <div class="right">
                    <div class="rg-limits-actions__checkbox">
                        <?php $this->showCrossBrandLimitCheckbox($type) ?>
                    </div>
                    <div class="rg-limits-actions__buttons">
                        <button class="btn btn-l btn-default-l w-125" id="rg-limits-action-button" onclick="setResettableLimit('<?php echo $type ?>')">
                            <?php et('set.a.limit') ?>
                        </button>
                        &nbsp;
                        <?php $this->rgRemoveLimitBtn($type, 'rg-limits-remove-button') ?>
                    </div>
                    <div class="rg-limits-actions__extra-text">
                        <?php $this->showCrossBrandLimitText($type) ?>
                    </div>
                </div>
                <br clear="all"/>
                <br clear="all"/>
                <?php if(!phive()->isEmpty($changes_at)): ?>
                    <div class="left">
                        <span class="vip-color"><?php et('changes.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($changes_at, '%x %R') ?></span>
                    </div>
                <?php endif ?>
                <br clear="all"/>
            </div>
        </div>
        <br clear="all"/>
        <script>
            $(document).ready(function() {
                var disableDepositFieldsOptions = '<?php echo lic('disableDepositFieldsOptions', [$this->cur_user], $this->cur_user) ?>';
                lic('disableDepositFields', [disableDepositFieldsOptions]);

                $('.rg-resettable-tbl .input-normal').on('change blur', function(e){
                     e.target.value = getMaxIntValue(e.target.value);
                });

                var type = '<?php echo $type ?>';

                if (licFuncs.assistOnLimitsChange && type !== 'login') {
                    licFuncs.assistOnLimitsChange(type);
                }

                if (licFuncs.assistOnLoginLimitsChange && type === 'login') {
                     licFuncs.assistOnLoginLimitsChange(type);
                }
            });

            // Button setup.
            var reSpans = <?php echo json_encode($this->rg->time_spans) ?>;
        </script>
    <?php }


    /**
    * Outputs a section in the responsible gambling page to group two divs of resettable limits under the same heading
    * and description
    *
    * @param string $group_name
    * @param array $list_of_types
     *
     * @deprecated
    */
    public function groupedResettableLimit($group_name = '', $list_of_types = []){

        $is_mobile  = phive()->isMobile();

    ?>
        <div class="simple-box pad-stuff-ten">
            <div class="account-headline">
                <?php et("$group_name.limit.headline") ?>
            </div>
            <?php et2("$group_name.limit.info.html", ['cooloff_period' => lic('getCooloffPeriod', [$this->cur_user->getCountry()])]) ?>
    <?php

        foreach ($list_of_types as $type) {
            $type_split = explode("-", $type);
            if(count($type_split) > 1 && lic('getLicSetting', [$type_split[1]]) !== true) {
                continue;
            }
            $clims      = $this->clims[$type];
            $changes_at = current($clims)['changes_at'];
            $disp_unit  = $this->rg->displayUnit($type, $this->cur_user);

            ?>

            <div class="account-sub-box-wrapper">
		        <?php if (lic('isSportsbookEnabled')): ?>
                    <div class="account-headline">
                        <?php et("$type.section.limit.headline") ?>
                    </div>
		        <?endif?>
                <div class="account-sub-box rg-resettable">

                    <table class="rg-resettable-tbl">
                        <?php if($is_mobile): ?>

                        <tr>
                            <th>
                                <?php et('my.limits') ?>
                            </th>
                            <?php foreach(['active.limit', 'remaining'] as $alias): ?>
                                <th>
                                    <div class="left"><?php et($alias) ?></div>
                                    <div class="right">(<?php echo $disp_unit ?>)</div>
                                </th>
                            <?php endforeach ?>
                        </tr>

                        <?php foreach($this->rg->time_spans as $tspan):
                            $rgl = $clims[$tspan];
                        ?>
                            <tr>
                                <td valign="top" style="width: 50px;">
                                    <div class="margin-five-top rg-tspan-headline"><?php et("$tspan.ly") ?></div>
                                    <div class="margin-five-top rg-tspan-descr"><?php et("$type.$tspan.ly.descr") ?></div>
                                </td>
                                <td valign="top">
                                    <?php dbInput("{$type}-{$tspan}-remaining", $this->rg->prettyLimit($type, $rgl['cur_lim']), 'text', 'input-normal input-rg-limit-disabled', 'disabled') ?>
                                </td>
                                <td valign="top">
                                    <?php dbInput('', $this->rg->prettyLimit($type, $this->rg->getRemaining($rgl)), 'text', 'input-normal input-rg-limit-disabled', 'disabled') ?>
                                </td>
                            </tr>
                            <?php if(!empty($rgl) && !phive()->isEmpty($rgl['resets_at'])): ?>
                                <tr>
                                    <td>
                                        &nbsp;
                                    </td>
                                    <td colspan="2">
                                        <div class="right">
                                            <span class="vip-color"><?php et('resets.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($rgl['resets_at'], '%x %R') ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif ?>
                            <tr>
                                <td>
                                    <?php et('new.limit') ?>
                                </td>
                                <td colspan="2">
                                    <?php dbInput("{$type}-{$tspan}", $this->rg->prettyLimit($type, $rgl['new_lim']), 'text', 'input-normal input-rg-new-limit') ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" style="height: 20px;">
                                    &nbsp;
                                </td>
                            </tr>
                        <?php endforeach ?>


                    <?php else: // Desktop ?>

                        <tr>
                            <th>
                                <?php et('my.limits') ?>
                            </th>
                            <?php foreach(['active.limit', 'remaining', 'new.limit'] as $alias): ?>
                                <th>
                                    <div class="left"><?php et($alias) ?></div>
                                    <div class="right">(<?php echo $disp_unit ?>)</div>
                                </th>
                            <?php endforeach ?>
                        </tr>
                        <?php foreach($this->rg->time_spans as $tspan):
                        $rgl = $clims[$tspan];
                        ?>
                            <tr>
                                <td valign="top">
                                    <div class="margin-five-top rg-tspan-headline"><?php et("$tspan.ly") ?></div>
                                    <div class="margin-five-top rg-tspan-descr"><?php et("$type.$tspan.ly.descr") ?></div>
                                </td>
                                <td valign="top">
                                    <?php dbInput("{$type}-{$tspan}-remaining", $this->rg->prettyLimit($type, $rgl['cur_lim']), 'text', 'input-normal', 'disabled') ?>
                                </td>
                                <td valign="top">
                                    <?php dbInput('', $this->rg->prettyLimit($type, $this->rg->getRemaining($rgl)), 'text', 'input-normal', 'disabled') ?>
                                    <?php if(!empty($rgl) && !phive()->isEmpty($rgl['resets_at'])): ?>
                                        <span class="vip-color"><?php et('resets.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($rgl['resets_at'], '%x %R') ?></span>
                                    <?php endif ?>
                                </td>
                                <td valign="top">
                                    <?php dbInput("{$type}-{$tspan}", $this->rg->prettyLimit($type, $rgl['new_lim']), 'text', 'input-normal') ?>
                                </td>
                            </tr>
                        <?php endforeach ?>

                    <?php endif ?>
                    </table>
                    <?php if(!$is_mobile): ?>
                        <br clear="all"/>
                    <?php endif ?>
                    <div class="right">
                        <button class="btn btn-l btn-default-l w-125" onclick="setResettableLimit('<?php echo $type ?>')">
                            <?php et('set.a.limit') ?>
                        </button>
                        &nbsp;
                        <?php $this->rgRemoveLimitBtn($type) ?>
                    </div>
                    <br clear="all"/>
                    <br clear="all"/>
                    <?php if(!phive()->isEmpty($changes_at)): ?>
                        <div class="left">
                            <span class="vip-color"><?php et('changes.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($changes_at, '%x %R') ?></span>
                        </div>
                    <?php endif ?>
                    <br clear="all"/>
                </div>

            </div>

            <?php

        }

    ?>
        </div>
        <br clear="all"/>
        <script>
            $(document).ready(function() {
                $('.rg-resettable-tbl .input-normal').on('change blur', function(e){
                     e.target.value = getMaxIntValue(e.target.value);
                });

                if (licFuncs.assistOnLimitsChange) {
                  <?php foreach ($list_of_types as $type) :?>

                  licFuncs.assistOnLimitsChange('<?php echo $type ?>');

                  <?php endforeach; ?>
                }
            });

            // Button setup.
            var reSpans = <?php echo json_encode($this->rg->time_spans) ?>;
        </script>
    <?php }

    /**
     * @deprecated
     */
    function newLimitSection(){
        $this->rg    = rgLimits();
        $this->clims = $this->rg->getGrouped($this->cur_user, [], true);

        $list_of_resettables = $this->rg->resettable;

        $list_of_resettables_to_remove = ['net_deposit'];

        foreach ($this->rg->grouped_resettables as $groupname => $group_list) {
            $this->groupedResettableLimit($groupname, $group_list);

            foreach ($group_list as $resettable) {
                array_push($list_of_resettables_to_remove, $resettable);
            }
        }

        $list_of_resettables = array_diff($list_of_resettables, $list_of_resettables_to_remove);

        foreach($list_of_resettables as $type) {

            if ( in_array($type, $this->rg->grouped_resettables)
                || $type == 'login' && !lic('showLoginLimit', [], $this->cur_user) ) {
                continue;
            }

            $this->resettableLimit($type);
        }
    }

    /**
     * @deprecated
     */
    function newSingleLimitSection($type){
        $rgl        = $this->rg->getSingleLimit($this->cur_user, $type);
        $tspan      = empty($rgl['time_span']) ? 'na' : $rgl['time_span'];
        $changes_at = $rgl['changes_at'];
        $disp_unit  = $this->rg->displayUnit($type, $this->cur_user);
        $is_mobile  = siteType() == 'mobile';
        $input_type = 'text';
        if($type == 'rc') {
            $rc_data = lic('getRcConfigs',[], $this->cur_user);
            $input_type = 'number';
        }

        $limit_parts = ['active.limit', 'remaining', 'new.limit'];
        if($type == 'betmax') {
            $limit_parts = ['active.limit', 'new.limit'];
        }
    ?>
        <div class="simple-box pad-stuff-ten">
            <div class="account-headline">
                <?php et("$type.limit.headline") ?>
            </div>
            <?php et2("$type.limit.info.html", ['cooloff_period' => lic('getCooloffPeriod', [$this->cur_user->getCountry()])]) ?>
            <?php if($type == 'betmax'): ?>
            <div class="rg-duration" style="margin-left: 9px;margin-bottom: 10px;margin-top: 10px;">
                <form id ='rg-duration-form'>
                    <div id="rg-duration-<?php echo $type ?>" class="left">
                        <div class="left">
                            <input class="left" type="radio" name="rg_duration" value="na" <?php if($tspan == 'na') echo 'checked="checked"' ?> />
                            <div class="left" style="margin-top: 2px;">
                              <?php et("rg.none.cooloff") ?>
                            </div>
                        </div>
                        <div class="left">
                            <input class="left" type="radio" name="rg_duration" value="day" <?php if($tspan == 'day') echo 'checked="checked"' ?> />
                            <div class="left" style="margin-top: 2px;">
                                <?php et("rg.day.cooloff") ?>
                            </div>
                        </div>
                        <div class="left">
                            <input class="left" type="radio" name="rg_duration" value="week" <?php if($tspan == 'week') echo 'checked="checked"' ?> />
                            <div class="left" style="margin-top: 2px;">
                                <?php et("rg.week.cooloff") ?>
                            </div>
                        </div>
                        <div class="left">
                            <input class="left" type="radio" name="rg_duration" value="month" <?php if($tspan == 'month') echo 'checked="checked"' ?> />
                            <div class="left" style="margin-top: 2px;">
                                <?php et("rg.month.cooloff") ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif ?>
            <div class="account-sub-box rg-single">
                <table class="rg-single-tbl">
                    <tr>
                        <?php foreach($limit_parts as $alias): ?>
                            <th>
                                <div class="left"><?php et($alias) ?></div>
                                <div class="right">(<?php echo $disp_unit ?>)</div>
                            </th>
                        <?php endforeach ?>
                    </tr>
                    <tr>
                        <td valign="top">
                            <?php dbInput("{$type}-remaining", $this->rg->prettyLimit($type, $rgl['cur_lim']), $input_type, 'input-normal', 'disabled') ?>
                        </td>
                        <?php if(in_array('remaining', $limit_parts)): ?>
                        <td valign="top">
                            <?php dbInput('', $this->rg->prettyLimit($type, $this->rg->getRemaining($rgl)), $input_type, 'input-normal', 'disabled') ?>
                            <?php if(!empty($rgl) && !phive()->isEmpty($rgl['resets_at'])): ?>
                                <span class="vip-color"><?php et('resets.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($rgl['resets_at'], '%x %R') ?></span>
                            <?php endif ?>
                        </td>
                        <?php endif ?>
                        <td valign="top">
                            <?php dbInput("{$type}", $this->rg->prettyLimit($type, $rgl['new_lim']), $input_type, 'input-normal', '', true, $rc_data['rc_min_interval'], $rc_data['rc_max_interval']) ?>
                        </td>
                    </tr>
                </table>
                <br clear="all"/>
                <div class="right">
                    <button class="btn btn-l btn-default-l w-125" onclick="setSingleLimit('<?php echo $type ?>')">
                        <?php et('set.a.limit') ?>
                    </button>

                    <?php if($type !== 'balance'): ?>
                        <?php $this->rgRemoveLimitBtn($type) ?>
                    <?php endif; ?>
                </div>
                <br clear="all"/>
                <br clear="all"/>
                <?php if(!phive()->isEmpty($changes_at)): ?>
                    <div class="left">
                        <span class="vip-color"><?php et('changes.on') ?>:</span>&nbsp;<span><?php echo phive()->lcDate($changes_at, '%x %R') ?></span>
                    </div>
                <?php endif ?>
                <br clear="all"/>
            </div>
        </div>
        <br clear="all"/>
        <?php
    }

    function responsibleGaming(){
      //require_once __DIR__.'/../../phive/modules/DBUserHandler/html/responsible_gambling.php';
      $admin_dep_limit = $this->cur_user->getSetting('permanent_dep_lim');
      $params = array('admin_dep_limit' => $admin_dep_limit);
      $this->responsibleGamingHTML($params);
    }

    function sportsBettingHistory()
    {
        $start_date = phive()->validateDate($_GET['start_date']) ? phive()->fDate($_GET['start_date']) : phive()->modDate(null, '-1 day');
        $end_date = phive()->validateDate($_GET['end_date']) ? phive()->fDate($_GET['end_date']) : phive()->modDate(null, '+1 day');
        $user_id = $this->cur_user->getId();
        $page = empty($_GET['page']) ? 1 : (int)$_GET['page'];
        $limit = 'LIMIT ' . (($page - 1) * 10) . ',10';
        $sport = $_GET['sport'];
        $type = $_GET['bet_type'];

        /** @var Sportsbook $sportsbook */
        $sportsbook = phive('Micro/Sportsbook')->init($start_date, $end_date, $user_id);

        $this->p->setPages($sportsbook->getStats('count', $sport, $type), '', 10);

        $staked = efEuro($sportsbook->getStats('staked', $sport, $type), true);
        $won = efEuro($sportsbook->getStats('won', $sport, $type), true);
        $lost_amount = (int)$sportsbook->getStats('lost', $sport, $type);
        $lost = efEuro($lost_amount, true);
        $void = efEuro($sportsbook->getStats('void', $sport, $type), true);

        $params = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'page' => $page,
            'transactions' => $sportsbook->getTransactions($limit, $sport, $type),
            'bet_types' => $sportsbook::BET_TYPES,
            'bet_type' => $type,
            'cols' => $this->site_type === 'mobile' ? [100, 100, 100, 70] : [200, 240, 120, 100],
            'stats' => [
                'staked' => $staked,
                'won' =>  $won,
                'void' =>  $void,
                'lost' =>  $lost,
            ],
            'sports' => $sportsbook->getSportsBettedOn(),
            'sport' => $sport,
            'user_id' => $user_id,
        ];
        $this->printSportsBettingHistoryHTML($params);
    }

    public function supertipsetBettingHistory()
    {
        $start_date = phive()->validateDate($_GET['start_date']) ? phive()->fDate($_GET['start_date']) : phive()->modDate(null, '-1 day');
        $end_date = phive()->validateDate($_GET['end_date']) ? phive()->fDate($_GET['end_date']) : phive()->modDate(null, '+1 day');
        $user_id = $this->cur_user->getId();
        $page = empty($_GET['page']) ? 1 : (int)$_GET['page'];
        $limit = 'LIMIT ' . (($page - 1) * 10) . ',10';

        /** @var Sportsbook $sportsbook */
        $sportsbook = phive('Micro/Sportsbook')->init($start_date, $end_date, $user_id);

        $this->p->setPages($sportsbook->getStatsPoolx('count'), '', 10);

        $staked = efEuro($sportsbook->getStatsPoolx('staked'), true);
        $won = efEuro($sportsbook->getStatsPoolx('won'), true);
        $lost_amount = (int)$sportsbook->getStatsPoolx('lost');
        $lost = efEuro($lost_amount, true);
        $void = efEuro($sportsbook->getStatsPoolx('void'), true);

        $params = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'page' => $page,
            'transactions' => $sportsbook->getPoolxTransactions($limit),
            'cols' => $this->site_type === 'mobile' ? [100, 100, 100, 70] : [200, 240, 120, 100],
            'stats' => [
                'staked' => $staked,
                'won' =>  $won,
                'void' =>  $void,
                'lost' =>  $lost,
            ],
            'user_id' => $user_id,
        ];

        $this->printSupertipsetBettingHistoryHTML($params);
    }

    function responsibleGamingHTML($params = '') {
        extract($params);
        $this->printAccSaveDialog();
        licHtml('increase_deposit_limit_handler');
    ?>
        <?php licHtml('self_exclusion'); ?>

      <script>

       var messages = {};

       function removeRgLimit(type){
           var pData = JSON.stringify({type: type});
           accSaveRgLimit('remove', pData);
       }

       function lockXdays(numDays){
           if(empty(numDays)){
               numDays = $("#lock-hours").val();
           }
           var options = licFuncs.onRgLockClick({num_days: numDays});
           accSave('lock', options, function(ret){
               goTo('/?signout=true');
           });
       }
       function lockGames24Hours(){
           // return false when no options are selected so the popup window does not open
           if ($('#gamebreak_24 input[type="checkbox"]:checked').length === 0) {
               return false;
           }

           var options = {
               extra: $(".hours-24-lock-games:checked").map(function() {
                    return $(this).val();
                }).toArray(),
               html: `<?= t('rg.lock-category-popup.html') ?>`,
           };

           accSave('lock-games-categories', options, function() {
               jsReloadBase();
           });
       }

       function lockUnlockGamesIndefinite(){
           var options = {
               checked: $(".indefinite-lock-games:checked").map(function() {
                   return $(this).val();
               }).toArray(),
               unchecked: $(".indefinite-lock-games:unchecked").map(function() {
                   return $(this).val();
               }).toArray()
           };

           let message = "<?= sprintf(t('game-category-block-indefinite.message.info'), lic('getLicSetting', ['gamebreak_indefinite_cool_off_period'])) ?>";
           accSave('lock-unlock-games-categories-indefinite', options, function(){
               mboxMsg(
                   message,
                   true,
                   function(){ jsReloadBase(); },
                   420,
                   false,
                   undefined,
                   'Game Category Block'
               );
           });
       }

       function undoWithdrawalsOptInOut() {
           var options = {
               opted_in: $("#undo_withdrawals-yes:checked").length ? 1 : 0
           };

           accSave('undo-withdrawals-opt-in-out', options, function () {
               jsReloadBase();
           });
       }
       function setupBtns(){
           $("button[id^='limbtn'], button[id^='changebtn']").click(function(i){
               var setting = $(this).attr('id').split('_')[1];
               $("#limform_"+setting).show(200);
           });

           $("button[id^='cancelbtn']").click(function(i){
               var targetId = '#limform_' + $(this).attr('id').split('_')[1];
               $(targetId).hide(200);
           });

         $("#excludebtn").click(function(i){
             <?php licHtml('self_exclusion_popup'); ?>
         });

         $("#excludebtn_permanent").click(function(i){
           accSave('exclude-permanent', {}, function(ret){
               if(typeof ret['msg'] !== 'undefined'  && ret['msg'] != '') {
                   mboxMsg(ret['msg'], true, function(){ goTo('/?signout=true'); }, 500, undefined, true)
               } else {
                   goToLang('/?signout=true');
               }
           });
         });

           $("#excludebtn_indefinite").click(function(i){
               accSave('exclude-indefinite', {}, function(ret){
                   if(typeof ret['msg'] !== 'undefined'  && ret['msg'] != '') {
                       mboxMsg(ret['msg'], true, function(){ goTo('/?signout=true'); }, 500, undefined, true)
                   } else {
                       goToLang('/?signout=true');
                   }
               });
           });
       }

       $(document).ready(function() {
         setupBtns();

         const lockForm = $('#limform_lock');
         const lockInput = $('#lock-hours');
         const lockError = $('#lock_error');

         const hideError = () => lockError.hide();
         licFuncs.onRgReady({ hideError });

         lockInput.on('input', function() {
           this.value = this.value.replace(/[^0-9]/g, '');
         });

         lockForm.on('submit', function(event) {
           if (!licFuncs.isRgLockDataEntered()) {
             lockError.show();
             event.preventDefault();

             return;
           }

           lockError.hide();
           lockXdays();
         });
       });
      </script>

      <div class="general-account-holder">
        <?php
          $rgLimitsBuilder = RgLimitsBuilderFactory::createBuilder($this->cur_user);
          $rgLimitsBuilder->renderData();
        ?>
      </div>
      <br clear="all"/>
      <br clear="all"/>
    <?php
    }

    /**
     * @deprecated
     */
    function printSelfExcludeSection(){

      if($this->canDo('user.block')) { ?>
      <div class="simple-box pad-stuff-ten margin-ten-top left">
        <div class="account-headline"><?php et('exclude.account') ?></div>
        <?php et('exclude.account.info.html') ?>
        <?php echo lic('getSelfExclusionExtraInfo', [], $this->cur_user); ?>
        <button id="limbtn_exclude" class="btn btn-l btn-default-l w-150 right"><?php et('self.exclude') ?></button>
        <br clear="all" />
        <div id="limform_exclude" class="account-sub-box hidden">
          <div class="account-sub-middle">
            <div><?php et('exclude.duration') ?></div>
            <br clear="all" />
            <div id="rg-duration-exclude">
                <?php $this->printRgRadios(lic('getSelfExclusionTimeOptions', [], $this->cur_user), 'excl_duration', 'days') ?>
            </div>
            <button id="cancelbtn_exclude" class="btn btn-l btn-cancel-l w-100"><?php et('cancel') ?></button>
            <span class="account-spacer">&nbsp;</span>
            <button id="excludebtn" class="btn btn-l btn-default-l w-100"><?php et('lock') ?></button>
          </div>
        </div>
      </div>
    <?php }
    }

    /**
     * @deprecated
     */
    function printPermanentSelfExcludeSection(){
      if($this->canDo('user.block') && lic('permanentSelfExclusion', [], $this->cur_user)) { ?>
      <div class="simple-box pad-stuff-ten margin-ten-top left">
        <div class="account-headline"><?php et('exclude.account.permanent') ?></div>
        <?php et('exclude.account.permanent.info.html') ?>
        <button id="excludebtn_permanent" class="btn btn-l btn-default-l w-150 right"><?php et('self.exclude') ?></button>
        <br clear="all" />
     </div>
    <?php }
    }

    function printIndefiniteSelfExcludeSection(){
        if($this->canDo('user.block') && lic('indefiniteSelfExclusion', [], $this->cur_user)) { ?>
            <div class="simple-box pad-stuff-ten margin-ten-top left">
                <div class="account-headline"><?php et('exclude.account.indefinite') ?></div>
                <?php et('exclude.account.indefinite.info.html') ?>
                <button id="excludebtn_indefinite" class="btn btn-l btn-default-l w-150 right"><?php et('self.exclude') ?></button>
                <br clear="all" />
            </div>
        <?php }
    }

    function printRgRadios($arr, $name, $str_suffix, $more = null, $extraStyle = ''){
    ?>
      <div class="rg-radios-duration-exclude <?php echo $extraStyle ?>" >
        <?php $i = 0; foreach($arr as $num): ?>
          <div class="left">
            <input class="left" type="radio" name="<?php echo $name ?>" value="<?php echo $num ?>" <?php if($i === 0) echo 'checked="checked"' ?> />
            <div class="left" style="margin-top: 2px;">
              <?php et("exclude.$num.$str_suffix") ?>
            </div>
          </div>
        <?php $i++; endforeach; ?>
        <?php if(!empty($more)) $more(); ?>
      </div>
      <?php
    }

  /**
   * @deprecated
  */
  function printLockAccountSection()
  {
    if (empty(lic('hasRgSection', ['lock'], $this->cur_user))) {
      return;
    }
    $lock_string_aliases = lic('getLockAccountMessages', [], $this->cur_user);
    if($this->canDo('user.block')) { ?>
      <div class="simple-box pad-stuff-ten margin-ten-top left">
        <div class="account-headline"><?php et($lock_string_aliases['headline']) ?></div>
        <?php et2($lock_string_aliases['description'], ['cooloff_period' => lic('getCooloffPeriod', [$this->cur_user->getCountry()])]) ?>
        <button id="limbtn_lock" class="btn btn-l btn-default-l w-150 right"><?php et('set.a.lock') ?></button>
        <br clear="all" />

        <div id="limform_lock" class="account-sub-box hidden">
          <div class="account-sub-middle">
            <div><?php et($lock_string_aliases['submenu']) ?></div>
            <br clear="all" />
            <?php if(!lic('rgLockSection', [$this], $this->cur_user)): // NOTE that this method also needs to output HTML /Henrik ?>
                <?php dbInput('lock-hours', '', 'text', 'input-normal') ?>
            <?php endif ?>
            <br clear="all" />
            <br clear="all" />
            <button id="cancelbtn_lock" class="btn btn-l btn-cancel-l w-100"><?php et('cancel') ?></button>
            <span class="account-spacer">&nbsp;</span>
            <button id="lockbtn" onclick="lockXdays()" class="btn btn-l btn-default-l w-100"><?php et('lock') ?></button>
          </div>
        </div>

      </div>
      <br clear="all" />
    <?php }
  }

}
