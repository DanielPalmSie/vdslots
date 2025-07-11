<?php
require_once __DIR__.'/TournamentBoxBase.php';

class TournamentLobbyBoxBase extends TournamentBoxBase{

    private $num_players = 0;
    private $entries = [];


    function init($tid = ''){
        $this->th 	= phive('Tournament');
        if(!empty($_REQUEST['tid']) || !empty($tid)){
            //clear cache
            $tidn = empty($tid) ? $_REQUEST['tid'] : $tid;
            $this->th->deleteTournamentCache($tidn);
            $this->t = $this->th->byId($tidn);
        }

        $this->g = $this->th->getGame($this->t);
        //$this->e = $this->th->entryByTidUid($this->t, cuPl()->data);
        if (!empty($this->t)) {
            $this->entries = $this->th->getLeaderBoard($this->t, true, $this->th->getSetting('get_from_mem'));
            $this->num_players = count($this->entries);

            // This variable contains the aliases properly formatted and will be used instead of "entries" for the JS object containing the entries.
            // otherwise the JS logic inside tournaments.js will rely on the full name and break the layout.
            $this->entries_with_formatted_alias = array_map(
                function($entry) {
                    $entry['dname'] = $this->th->formatBattleAliasForDisplay($entry['dname']);
                    return $entry;
                }, $this->entries
            );
        }
    }

    function ajaxLobbyLeaderBoard(){
        $this->leaderboard(null, null, true);
    }

    function leaderBoard($t, $entries = array(), $allEntries = false){
        $t       = empty($t) ? $this->t : $t;
        $entries = empty($entries) ? $this->entries : $entries;
        $leaders = 4;
        if(ctype_digit($_REQUEST['leaders'])){
            $leaders = (int)$_REQUEST['leaders'];
        }
        $bLeaderShown = false;

        foreach($entries as $i => $r):

            if($allEntries === false) {
                if ($r['user_id'] != $_SESSION['mg_id'] && $bLeaderShown === false && $i >= ($leaders - 1)) {
                    // we still need our own position to be shown so continue to next entry to see if user_id matches
                    continue;
                } else if ($r['user_id'] != $_SESSION['mg_id']) {
                    if ($i >= $leaders) {
                        continue;
                    }
                } else {
                    // we found user_id match so its shown
                    $bLeaderShown = true;
                }
            }
            $fname = $this->th->formatBattleAliasForDisplay($r['dname']);
            ?>
            <tr id="mpuser-<?php echo $r['user_id'] ?>">
                <td class="race-position"><?php echo $i + 1  ?></td>
                <td class="race-fname">
                    <?php echo $r['user_id'] == $_SESSION['mg_id'] ? '<span class="red">'.$fname.'</span>' : $fname;  ?>
                    <?php echo $r['joker']   == 1 ? '<img height="12", width="12" src="' . getMediaServiceUrl() . '/file_uploads/tournaments/mtt-jester-hat.png">' : ''; ?>
                    <?php echo $r['bounty']  == 1 ? '<img height="12", width="12" src="' . getMediaServiceUrl() . '/file_uploads/tournaments/mtt-bounty.png">'     : ''; ?>
                </td>
                <?php if($t['play_format'] == 'xspin'): ?>
                    <td class="race-left">
                        <span class="value"><?php echo $r['spins_left'] ?></span>
                        <?php if($t['play_format'] == 'xspin'): ?>
                            <span class="text"><?php et('spins.left') ?></span>
                        <?php endif; ?>
                    </td>
                <?php endif ?>
                <td class="race-amount">
                    <span class="value"><?php echo $r['win_amount'] ?></span>
                    <span class="text"><?php et('score') ?></span>
                </td>
                <td class="race-arrow"></td>
            </tr>
        <?php endforeach ?>
        <?php
    }

    function leaderBoardHeader($t){ ?>
        <th>#</th>
        <th><?php et('alias') ?></th>
        <?php if($t['play_format'] == 'xspin'): ?>
            <th><?php et('spins.left') ?></th>
        <?php endif ?>
        <th style="text-align: left;"><?php et('score') ?></th>
        <?php
    }

    function playBoxLeaderBoardTop(){
        $this->init();
        $prize_pool = $this->th->totalPrizeAmount($this->t, false);
        $ud = ud();
        list($pos, $entry) = $this->th->findPosition($this->entries, $ud);
        //$entry = $this->th->entryByTidUid($this->t, $ud);
        if($prize_pool !== -1){
            ?>
            <div class="mp-leaderboard-prize-top-headline"><?php echo t('mp.total.prize') ?></div><div
                id="mp-total-prize-top" class="mp-leaderboard-prize-top">
                <?php echo $this->th->fAmount($prize_pool) ?>
            </div>
            <?php
        } else {
            jsTag("mpLeaderboardMarginBottom = 203;");
        }
    }

    function playBoxLeaderBoard(){
        $this->init();
        ?>
        <table id="mp-tab-table" class="zebra-tbl">
            <?php if($this->t['play_format'] !== 'xspin'): ?>
                <colgroup>
                    <col width="15"/>
                    <col width="125"/>
                    <col width="20"/>
                    <col width="90"/>
                </colgroup>
            <?php endif ?>
            <?php $this->leaderBoard($this->t) ?>
        </table>
        <?php
    }

    function printPrizeListPopup($tid){ ?>
        <div class="mp-popup-header gradient-default">
            <?php et("mp.prizes.headline") ?>
        </div>
        <div class="tournament-content">
            <div class="pad-stuff mp-popup-prize-list-container gradient-light">
                <?php $this->printPrizeList('', $tid) ?>
            </div>
            <?php okCenterBtn("mboxClose('mp-prize-list');") ?>
        </div>
        <?php
    }

    function printPrizeList($prize_list, $tid, $count = 0, $prize_pool = ''){
        $prize_list = $this->th->getPrizeListForRender($tid, $count, $prize_list, $prize_pool);
        ?>
        <table>
            <?php foreach($prize_list as $row): ?>
                <tr>
                <?php if($row['status'] == 'upcoming'): ?>
                    <td><?php echo $row['place'].": ".$row['descr'] ?></td>
                <?php else: ?>
                    <td><?php echo $row['place'] ?></td>
                    <?php if(!empty($row['descr'])): ?>
                        <td><?php echo $row['descr'] ?></td>
                    <?php else: ?>
                        <td><?php echo $row['award'] ?></td>
                        <td><?php echo $row['pool_percentage'] ?></td>
                    <?php endif ?>
                    </tr>
                <?php endif ?>
            <?php endforeach ?>
        </table>
        <?php
    }


    function setupHandleBarsChat(){ ?>
        <?php loadJs("/phive/js/handlebars.js") ?>
        <script id="mp-chat-tpl" type="text/x-handlebars-template">
            <?php $this->drawChatItem(array(), true) ?>
        </script>
        <script>
            var chatTpl = Handlebars.compile($('#mp-chat-tpl').html());
        </script>
        <?php
    }

    function trChatItem(&$item, $key){
        $item[$key] = is_array($item[$key]) ? $item[$key][cLang()] : $item[$key];
    }

    function drawChatItem($item, $hbars = false, $cls = '', $key = 0){
        $this->trChatItem($item, 'msg');
        $this->trChatItem($item, 'firstname');
        $onlyForAdminChatWsUpdates = privileged(cuPl()) && $hbars;
        ?>
        <tr class="mp-chat-item" eid="<?php $this->hbars($item, 'entry_id', $hbars) ?>" userid="<?php $this->hbars($item, 'user_id', $hbars) ?>" messageid="<?php $this->hbars($item, 'id', $hbars) ?>">
            <?php // me|system| or nothing ?>
            <td valign="top" class="mp-player <?php echo $onlyForAdminChatWsUpdates ? '{{#if only}}only{{/if}}' : $cls ?>">
                <?php $this->hbars($item, 'firstname', $hbars) ?>:
                <?php $this->hbars($item, 'msg', $hbars) ?>
            </td>
            <td valign="top" class="mp-chat-hi" width="37"><?php $this->hbars($item, 'hi', $hbars) ?></td>
        </tr>
        <?php
    }

    function drawChatMsgs($func = 'getChatContents'){
        foreach($this->th->$func($this->t) as $key => $item){
            $cls = '';
            if(empty($item['user_id'])) {
                $cls = 'system';
            }
            else {
                if($item['user_id'] === cuPlId()) {
                    $cls = 'me';
                }
            }

            if(($item['only'] == $item['user_id'] && privileged(cuPl()))) {
                $cls = 'only';
            }

            // messages with "only" set, will be just displayed to that user, or to admin user (This is used for banned players)
            if(empty($item['only']) || $item['only'] == cuPlId() || ($item['only'] == $item['user_id'] && privileged(cuPl()))) {
                $this->drawChatItem($item, false, $cls, $key);
            }
        }
    }

  function drawChatAdmin(){ ?>
      <?php $this->setupHandleBarsChat() ?>
      <script>
       doWs('<?php echo phive('UserHandler')->wsUrl('mp-chat-admin', false) ?>', function(e){
           var res = JSON.parse(e.data);
           //console.log(res);
           //.mpAddMsgToChat(chatTpl(res), addBlockBtn);
           var html = chatTpl(res);
           $("#mp-chat-msgs table").append(html);
           addBlockBtn();
           scrollChatAdmin();
       });
       $(document).ready(function(){
           addBlockBtn();
           scrollChatAdmin();
       });
      </script>
      <div id="mp-chat-msgs" class="mp-chat-msgs">
          <table class="mp-admin-chat">
            <?php $this->drawChatMsgs('getAllChatMsgs') ?>
        </table>
    </div>
  <?php
  }

    function chatBoxJs($chat_ws = false){
        $this->setupHandleBarsChat();
        loadJs("/phive/js/nanoScroller/jquery.nanoscroller.js");
        loadCss("/phive/js/nanoScroller/nanoscroller.css");
        ?>

        <script>

            <?php if($chat_ws): ?>
            doWs('<?php echo phive('UserHandler')->wsUrl('lobbychat'.$this->t['id'], false) ?>', function(e) {
                var res = JSON.parse(e.data);
                handleChatMsg(res);
            });
            <?php endif ?>

            function handleChatMsg(res){
                if(res.msg instanceof Object)
                    res.msg = res.msg[cur_lang];
                if(res.firstname instanceof Object)
                    res.firstname = res.firstname[cur_lang];
                if(res.wstag == 'update_msg') {
                    $('tr.mp-chat-item[messageid="'+res.id+'"]').remove();
                } else {
                    if(res.wstag == 'smsg'){
                        // add to beginning of array
                        mpSystemMessages.unshift(res.msg);
                        //console.log('SYSTEM-MESS', mpSystemMessages.length, mpSystemMessages);
                        if(mpSystemMessages.length > 10){
                            // remove from to end of array
                            //console.log('SYSTEM-MESS-REMOVED', mpSystemMessages);
                            mpSystemMessages.pop();
                        }

                        if(mpSystemMessages.length === 1){
                            $(rotateSystemMessage);
                        }
                    } else {
                        if(res.only === '' || res.only == '<?php echo cuPlId() ?>') {
                            mpAddMsgToChat(chatTpl(res), '', '<?php echo cuPlId() ?>');
                        }
                    }
                }
            }

            function submitMessage(){
                if($("#mp-chat-write").val() !== ''){
                    var msg = $("#mp-chat-write").val();
                    $("#mp-chat-write").val('');
                    if(!empty(msg))
                        mpSendChatMsg(msg, <?php echo $this->t['id'] ?>);
                }
            }

            $(document).ready(function(){

                curTid = <?php echo $this->t['id'] ?>;

                // on page load we want to know how many players can fit into the leaders board
                var leaders = getLeaderBoardBoxCount();
                leaderboardEntries = JSON.parse('<?php echo json_encode($this->entries_with_formatted_alias); ?>');
                // when the browser is resized recalculate leaderboard on resize with debounce for better performance
                $(window).on('resize', debounce(handleDisplayedLeaderboardRows, 200, true));

                function handleDisplayedLeaderboardRows() {
                    // the current amount of leaders that can fit into the leaders board container
                    // by getting the current leaders board height and divide this by the height of a single leader box
                    var calcLeaders = Math.floor(getLeaderBoardHeight()/leaderHeight);

                    if(leaders !== calcLeaders){
                        // depending on the current height of the leaders board container it seems the amount of players that can fit into this container
                        // has been either reduced (browser window was resized to smaller) or increased (browser window was resized to bigger).
                        // so store the newly amount of leaders and trigger an ajax call to refresh the board with the new leaders
                        leaders = calcLeaders;
                        refreshLeaderBoard(curTid);
                    } else {
                        updateLeaderBoardHeight();
                    }
                }

                // test system messages don't remove
                /*
                   setInterval(function(){
                   addSystemMess();
                   }, 1000);
                   addSystemMess(10);
                 */


                <?php $this->startAliasPicking() ?>

                <?php if($chat_ws): ?>
                <?php if(!$this->th->isRegistered($this->t['id'])): ?>
                $(".lobby-right").css({height: '265px'});
                <?php endif ?>
                $('#mp-nano').css({ width: '300px' });
                $('.nano').nanoScroller();
                $('#mp-nano').nanoScroller({ scroll: 'bottom' });
                <?php endif ?>

                $('#mp-chat-write').keypress(function(e) {
                    if (e.which == 13 && $(this).val().trim() !== '') {
                        toggleChatAndLeaderboard('chat');
                        submitMessage();
                        e.preventDefault();
                    }
                });

                let lastChatValue = null
                $('#mp-chat-write').focus(function() {
                    let currentValue = $(this).val().trim();
                    if (isMpChatToggle || lastChatValue === null || currentValue !== lastChatValue) {
                        toggleChatAndLeaderboard('chat');
                        lastChatValue = currentValue;
                    }
                });

                $('button.mp-send-message').on('click', function() {
                    submitMessage();
                    return false;
                });

                let isMpChatToggle = false;
                $('div.close-chat').on('click',function(){
                    toggleChatAndLeaderboard('leaderboard');
                    isMpChatToggle = true;
                });

                function toggleChatAndLeaderboard(type) {
                    if(type === undefined) {
                        type = 'leaderboard';
                    }
                    if(type === 'leaderboard') {
                        $('#mp-chat-msgs').hide();
                        $('#mp-lobby-lb').addClass('full-list');
                    }
                    if(type === 'chat') {
                        $('#mp-chat-msgs').show();
                        $('#mp-lobby-lb').removeClass('full-list');
                    }
                    handleDisplayedLeaderboardRows();
                }
            });
        </script>
        <?php
    }

    function chatBox($style = "display: none;") { ?>
        <div id="mp-chat-msgs" class="mp-chat-msgs" style="<?php echo $style; ?>">
            <div class="close-chat">X</div>
            <div class="nano" id="mp-nano">
                <div class="nano-content">
                    <table border="0" cellpadding="0" width="100%" cellspacing="0">
                        <tbody id="mp-nano-content"><?php $this->drawChatMsgs() ?></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if($this->th->isRegistered($this->t['id'])): ?>
            <div class="mp-chat-write-container">
                <input id="mp-chat-write" maxlength="160" type="text" class="mp-chat-write" placeholder="<?php et('type.a.message.here') ?>..." /><button type="submit" class="mp-send-message">&nbsp;</button>
            </div>
        <?php endif ?>
        <div id="mp-system-container"><span></span></div>
        <?php
    }

    function getCdown(){
        $start_time = $this->th->getStartStamp($this->t);
        $end_time   = $start_time + ($this->t['duration_minutes'] * 60);
        $intv       = prettyTimeInterval($end_time - time());
        echo json_encode(array('hours' => $intv['hours'], 'mins' => $intv['mins'], 'secs' => $intv['seconds']));
    }

    function printCSS(){
        $this->loadCssJs();
    }

    /**
     * Initialize javascript variables.
     * @return void
     */
    function js()
    {
        ?>
        <script>
            <?php $this->tournamentCommon(); ?>
        </script>
        <?php
    }

    function printHTML(){
        $this->js();
        if(phive('MicroGames')->blockMisc())
            die('deposit.country.ip.restriction');
        ?>
        <script>
            $(document).ready(function(){
                <?php echo "mpLobbyWs('".phive('UserHandler')->wsUrl('mp-tournament-lobby'.$this->t['id'], false)."');" ?>
                minuteCdown();
            });
        </script>
        <div id="tournament-lobby-wrapper">
            <?php $this->printHtmlContent(); ?>
        </div>
        <?php
    }

    function prLine($alias, $exec, $class = ''){
        if(is_array($exec))
            list($cond, $func, $class) = $exec;
        else
            $func = $exec;
        if(!empty($cond) && $cond() === false)
            return;
        ?>
        <div>
            <strong class="<?php echo $class ?>"><?php et($alias) ?>:</strong>
            <?php $func() ?>
        </div>
        <?php
    }

    function printMainLeft(){
        $sstamp                                               = $this->th->getStartStamp($this->t);
        list($start_wday, $start_dtime)                       = $this->getTimeFormats($sstamp);
        list($time_since_start, $running_time, $start_ing_ed) = $this->getTimeInfo($this->t, $sstamp);
        $prize_pool                                           = $this->th->totalPrizeAmount($this->t, false);
        $time_left                                            = $this->th->getTimeLeft($this->t);
        $reg_labels                                           = array('upcoming' => 'mp.reg.start', 'late.registration' => 'mp.late.reg.ending');
        $reg_label                                            = $reg_labels[$this->t['status']];
        $me                                                   = $this;

        $is_calculating = $this->th->isCalculating($this->t);

        $full_prize_list = $this->th->getPrizeList($this->t);

        $max_players =  $this->t['max_players'];
        //Limit slice length to the maximum number of players if necessary
        $slice_length = min($max_players, 5);

        $prize_list = array_slice($full_prize_list, 0, $slice_length);
        $cu_currency = cuPlAttr('currency');

        $to_draw = array(
            'mp.id' => function() use ($me){ echo $me->t['id'];  },
            'mp.status'   => function() use ($me){ et('mp.'.$me->t['status']);  },
            $start_ing_ed => array(
                function() use ($me){
                    return $me->t['start_format'] != 'sng' || $me->th->hasStarted($me->t);
                },
                function() use ($me, $running_time, $start_dtime, $time_since_start, $sstamp){
                    if($running_time < $me->t['duration_minutes'] && $running_time > 0)
                        echo $start_dtime.' '.$me->getRunningTimeStr($me->t, $running_time);
                    else if($time_since_start > $me->t['duration_minutes'])
                        echo $me->fTime($sstamp + ($me->t['duration_minutes'] * 60));
                    else
                        echo $me->fTime($sstamp);
                }
            ),
            'mp.max.players' => function() use ($me){ echo $me->t['max_players']; },
            'mp.type'     => function() use ($me){ et($me->t['category'].'.tournament');  },
            'mp.game'     => function() use ($me){ echo phive('MicroGames')->nameByRef($me->t['game_ref']);  },
            'mp.buyin'    => function() use ($me, $cu_currency){ echo $me->th->getBuyin($me->t, false, $cu_currency);  },
            $reg_label    => array(
                function() use ($me, $reg_label){ return $me->t['start_format'] == 'mtt' && !empty($reg_label); },
                function() use ($me, $reg_label){ echo $me->th->prettyTime($me->t, $me->th->getRegStartTime($me->t)); },
                'ymd-cdown'
            ),
            'mp.time.left.headline' => array(
                function() use($time_left){ return $time_left !== false; },
                function() use($time_left){ echo $time_left; }
            ),
            'mp.xspins' => array(
                function() use($me){ return $me->t['play_format'] == 'xspin'; },
                function() use($me){ echo $me->th->getXspinInfo($me->t, 'tot_spins'); }
            ),
            'mp.time.limit' => function() use ($me){ echo $me->t['duration_minutes'].' '.t('minutes');  },
            'mp.min.players' => array(
                function() use($me){ return !empty($me->t['min_players']); },
                function() use($me){ echo $me->t['min_players']; }
            ),
            'mp.bet.interval' => function() use ($me){ echo $me->getBetInterval($me->t); },
            'mp.rebuys' => array(
                function() use($me){ return !empty($me->t['rebuy_times']); },
                function() use($me){ echo $me->t['rebuy_times']; } //TODO one's own rebuys left within parenthesises
            ),
            'mp.rebuy.end.time' => array(
                function() use($me){ return !empty($me->t['duration_rebuy_minutes']); },
                function() use($me){ echo $me->th->getRebuyEndTime($me->t); }
            ),
            'mp.get.race'    => function() use ($me){ echo empty($me->t['get_race']) ? t('no') : t('yes');  },
            'mp.get.loyalty' => function() use ($me){ echo empty($me->t['get_loyalty']) ? t('no') : t('yes');  },
            'mp.get.trophy'  => function() use ($me){ echo empty($me->t['get_trophy']) ? t('no') : t('yes');  },
            'mp.jokers'      => function() use ($me){ echo $me->t['number_of_jokers'];  },
            'mp.bounty'      => function() use ($me){ echo empty($me->t['bounty_award_id']) ? t('no') : rep(phive('Trophy')->getAward($me->t['bounty_award_id'])['description']);  }
        );
        ?>
        <table class="v-align-top lobby-left gradient-light">
            <tr>
                <td>
                    <?php
                    foreach($to_draw as $alias => $func)
                        $this->prLine($alias, $func);
                    ?>
                </td>
                <td>
                    <?php if($prize_pool >= 0 || $is_calculating): ?>
                        <div>
                            <strong><?php et('mp.prize.pool') ?>:</strong>
                            <?php echo $is_calculating ? t('mp.calculating.prizes') : $this->th->fullFmSym($cu_currency, $prize_pool) ?>
                        </div>
                    <?php endif ?>
                    <div>
                        <strong><?php et('mp.prize.type') ?>:</strong>
                        <?php et("mp.prize.type.{$this->t['prize_type']}") ?>
                    </div>
                    <div>
                        <strong><?php et('mp.registered.players') ?>:</strong>
                        <?php  echo $this->t['registered_players'] ?>
                        <?php // echo $this->num_players; ?>
                    </div>
                    <div>
                        <?php if($is_calculating): ?>
                            <?php et('mp.calculating.prizes') ?>
                        <?php else: ?>
                            <?php $this->printPrizeList($prize_list, $this->t, 0, $prize_pool) ?>
                            <?php
                            if(count($prize_list) < count($full_prize_list))
                                btnRequestS(t('see.more'), '', "showPrizeList({$this->t['id']})");
                            ?>
                        <?php endif ?>
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    function printHtmlContent($chat_ws = true){

        if(empty($this->t))
            $this->t = phive('Tournament')->byId($_REQUEST['tid']);
        ?>
        <script>
            $(document).ready(function(){
                $("#bos-lobby-reload").click(function(){
                    showLoader(function(){
                        ajaxGetBoxHtml({func: 'ajaxLobbyLeaderBoard', tid: <?php echo $this->t['id'] ?>}, cur_lang, '<?php echo $this->getId() ?>', function(ret){
                            $("#mp-lobby-lb").find('tbody').html(ret);
                        });
                    }, false);
                });
            });
        </script>
        <div class="tournament-lobby-wrapper">
            <img id="bos-lobby-reload" src="/diamondbet/images/<?= brandedCss() ?>reload_green.png" />
            <?php depositTopBar('mp.lobby', "parent.$.multibox('close', 'mp-lobby-box')") ?>
            <div class="tournament-content">
                <table class="v-align-top tournament-lobby">
                    <tr>
                        <td>
                            <div id="tournament-name-headline" class="gradient-dark lobby-main-headline thin-border">
                                <?php echo $this->t['tournament_name'] ?>
                            </div>
                            <div id="mp-lobby-left">
                                <?php $this->printMainLeft() ?>
                            </div>
                        </td>
                        <td>
                            <table id="mp-lobby-lb" class="tournament-list lobby-right gradient-light full-list">
                                <colgroup>
                                    <col width="20"/>
                                    <?php if($this->t['play_format'] == 'xspin'): ?>
                                        <col width="140"/>
                                        <col width="50"/>
                                        <col width="90"/>
                                    <?php else: ?>
                                        <col width="200"/>
                                        <col width="80"/>
                                    <?php endif ?>
                                </colgroup>
                                <thead>
                                <tr class="tournament-header">
                                    <?php $this->leaderBoardHeader($this->t) ?>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $this->leaderBoard($this->t, array(), true) ?>
                                </tbody>
                            </table>
                            <?php $this->chatBoxJs($chat_ws); $this->chatBox(); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="center-stuff" colspan="2">
                            <?php
                            if(!isLogged()) {
                                $_SESSION['show_signup'] = true;
                                btnDefaultL(t('register'), '', "top.goTo('/?signup=true')", 250, 'gradient-default');
                            } else
                                $this->prRegBtn($this->t, 'mp-lobby-bottom-btn btn btn-l');
                            //else if($this->th->canPlay($this->t, $this->e))
                            //  btnActionL(t('start.tournament'), '', "top.goTo('{$this->th->playUrl($this->e, $this->g)}')", 250);
                            //else if($this->th->canRegister($this->t, '', $this->e))
                            //  btnDefaultL(t('register'), '', "top.showTournamentRegBox()", 250);
                            btnDefaultL(t('mp.back.to.main.lobby'), '', "backToMainLobby()", 250, 'gradient-default');
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
}
