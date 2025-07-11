<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$GLOBALS['no-session-refresh'] = true;

phive('Localizer')->setFromReq();

/** @var Tournament $th */
$th = phive('Tournament');

$tid = (int)$_REQUEST['tid'];
$eid = (int)$_REQUEST['eid'];

$args = [];
if($_REQUEST['use_ticket'] == 'yes') {
    $args['use_ticket'] = true;
}
if (!empty($_REQUEST['pwd'])) {
    $args['pwd'] = $_REQUEST['pwd'];
}

switch($_REQUEST['action']){
    case 'spin-end-status':
        $has_t_entry = $th->entryById($_REQUEST['tEntryId']);
        if ($has_t_entry && $has_t_entry['status'] === 'finished') {
            $res['status'] = true;
        } else {
            $res['status'] = false;
        }
        break;
    case 'set-alias':
        $res = $th->setBattleAlias($_REQUEST['alias']);
        $res['msg'] = t($res['msg']);
        break;
    case 'ie-msg':
        die(et('mp.ie.msg'));
    case 'queue-reg':
        dclickStart('mp_reg_ok');
        $res = dclickEnd('mp_reg_ok', $th->queueReg($tid, cuPl(), $args));
        $res['msg'] = t($res['msg']);
        break;
    case 'tournament-reg':
        dclickStart('mp_reg_ok');
        $t = $th->byId($tid);
        $res = dclickEnd('mp_reg_ok', $th->regRebuyCommon('register', $t, 'reg', uid(), $args));
        //phive()->q('mp-reg', 'Tournament', 'regRebuyCommon', ['register', $tid, 'reg']);
        if (isset($th->reg_sums)) {
            $t['wager_sum'] = $th->reg_sums['wager_sum'];
            $t['dep_sum'] = $th->reg_sums['dep_sum'];
        }
        $res['msg'] = t2($res['msg'], $t);
        break;
    case 'print-prize-list':
        $html =  phive('BoxHandler')->getRawBox('TournamentLobbyBox', true)->printPrizeListPopup($_REQUEST['tid']);
        die($html);
    case 'set-setting':
        $setting = $_REQUEST['setting'];
        if(in_array($setting, array('mp-hiw-types-understood', 'mp-hiw-general-understood')))
            cuPl()->setSetting($setting, 'yes');
        $res = array('msg' => 'OK');
        break;
    case 'update':
        switch($_REQUEST['type']){
            case 'tournament-lobby':
                $lobby_box = phive('BoxHandler')->getRawBox('TournamentLobbyBox', true);
                if ($_REQUEST['subtype'] == 'main') {
                    $left_html = $lobby_box->printMainLeft(false);
                }
                $leader_board = $lobby_box->leaderboard(false);
                $res = array('html' => array('left' => $left_html, 'leader_board' => $leader_board));
                break;
            case 'tournament-cancelled-popup':
                //$res = array('html' => phive('BoxHandler')->getRawBoxHtml('TournamentBox', 'mpCancelledBox', $t));
                $res = array('html' => t('mp.cancelled.html'));
                break;
        }
        break;
    case 'tournament-unreg':
        dclickStart('mp_unreg_ok');
        $t = $th->byId($tid);
        $res = dclickEnd('mp_unreg_ok', $th->cancelEntry('', '', $t, false));
		$th->initMem($t);
        break;
    case 'tournament-unqueue':
        $t = $th->byId($tid);
        phMdelShard('tplqueue'.$t['tpl_id'], uid());
        $res = ['ok'];
        break;
    case 'tournament-rebuy':
        $t = $th->byId($tid);
        $res = $th->regRebuyCommon('rebuy', $eid, 'rebuy', uid(), $args);
        $res['msg'] = t2($res['msg'], $t);
        break;
    case 'tournament-descr':
        $t = $th->byId($tid);
        $content = rep(tAssoc('mp.umique.descr.html', $t));
        $main_box = phive('BoxHandler')->getRawBox('TournamentBox', true);
        if (!empty($t['bet_levels'])) {
            $content .= tAssoc('mp.bet.levels.html', ['bet_levels' => $main_box->getBetInterval($t)]);
        }
        $html = $main_box->prMpLimitMsgTbl('', $t, $content, 'start');
        die($html);
        break;
    case 'finish-entry':
        $th->finishEntry($eid);
        die('ok');
        break;
    case 'mp-chat-block':
        $th->doChatBlock($_REQUEST['uid'], $_REQUEST['eid'], $_REQUEST['days']);
        $th->doChatBlockRemote($_REQUEST['uid'], $_REQUEST['eid'], $_REQUEST['days']);
        $res = array('ok');
        break;
    case 'mp-chat-delete':
        $th->doChatDelete($_REQUEST['uid'], $_REQUEST['eid'], $_REQUEST['messageId']);
        $res = array('ok');
        break;
    case 'fi-state':
        $t = $th->byId($tid);
        $th->wsSystemMsg(cuPl(), $t, $_REQUEST['locAlias']);
        $res = array('ok');
        break;
    case 'chat-msg':
        $res = $th->addChatMessage($tid, $_REQUEST['msg']);
        break;
    case 'remove-session-var-for-back-button':
        unset($_SESSION['show_go_back_to_bos']);
        $res = array('ok');
        break;
    case 'tournament-statuses-localized-strings':
        $res = $th->getStatusesLocalizedStrings($_REQUEST['lang']);
        break;
}

echo json_encode($res);
