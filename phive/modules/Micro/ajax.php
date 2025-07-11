<?php

use Carbon\Carbon;
use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;
use Laraphive\Phive\Decorators\DBUserHandler\DBUserHandlerDecorator;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\RgEvaluationPopup\GetRgEvaluationPopupFormatter;
use Videoslots\User\NetDepositLimit\CustomerNetDepositLimitService;
use Videoslots\User\Services\UpdatePrivacyDashboardSettingsService;
use Videoslots\User\TrophyAward\TrophyAwardServiceFactory;
use Videoslots\User\NetDepositLimit\NetDepositLimitService;
use Videoslots\User\TermsConditionsVersion\UpdateTermsConditionsVersionService;
use Videoslots\ContactUs\SendContactUsService;
use Laraphive\Domain\Content\DataTransferObjects\ContactUs\SendContactUsData;
use Videoslots\User\Registration\RegisterStep1Service;
use Videoslots\User\TrophyBonus\TrophyBonusService;

$GLOBALS['no-session-refresh'] = true;
if(in_array($_REQUEST['action'], array('delete', 'pay', 'verify', 'unverify', 'pay-all', 'retry-frb')))
    require_once __DIR__ . '/../../admin.php';
else
    require_once __DIR__ . '/../../phive.php';


require_once __DIR__ . '/../../../diamondbet/html/display.php';

phive('Localizer')->setFromReq();
phive('Licensed')->forceCountry($_REQUEST['iso'] ?? $_REQUEST['country']);

$cur_player = cuPl();

$rg_evaluation = phive('RgEvaluation/RgEvaluation');

/*TODO WARNING: When adding new methods , dont forget to use the admin check above.
Please re-check if all admin methods are session tested...*/
if (phive()->getSetting('log_micro_ajax', false) === true) {
    phive()->dumpTbl('micro_ajax', [remIp(), $_REQUEST], $cur_player);
}

switch($_REQUEST['action']){
    case 'sms-login':
        if($_POST['code'] == $_SESSION['sms-login-token']){
            $user = phive('UserHandler')->smsLogin($_SESSION['sms-login-username'], $_SESSION['sms-login-password']);
            unset($_SESSION['sms-login-attempts']);
            unset($_SESSION['sms-login-username']);
            unset($_SESSION['sms-login-password']);
            unset($_SESSION['sms-login-token']);
            echo json_encode(['success' => true]);
        } else {
            $_SESSION['sms-login-attempts'] += 1;
            if($_SESSION['sms-login-attempts'] >=3){
                phive('UserHandler')->addBlock($_SESSION['sms-login-username'], 6);
                echo json_encode(['success' => false, 'action' => 'restart-login']);
                die();
            }
            echo json_encode(['success' => false]);
        }
        exit;

    case 'check-psp-limits':
        $psp = $_REQUEST['psp'];
        $amount = $_REQUEST['amount'] * 100;

        if (!phiveApp(PspConfigServiceInterface::class)->checkUpperLimit(cu(), $amount, $psp, PspActionType::IN)) {
            echo t('err.toomuch');
            break;
        }

        if (!phiveApp(PspConfigServiceInterface::class)->checkLowerLimit(cu(), $amount, $psp, PspActionType::IN)) {
            echo t('err.toolittle');
            break;
        }

        echo 'ok';
        break;
    case 'check-over-limits':
        list($res, $action) = phive("Cashier")->checkOverLimits(cu(), 0, false);
        if ($res && !empty($action)) {
            echo $action;
        } else {
            echo $res ? t('deposits.over.limit.html') : 'ok';
        }
        break;
    case 'load-stat' :
        phive("Logger")->debug('insertLoad', ['loc'=>$_REQUEST['loc']]);
        echo 'ok';
        break;
    case 'tac-action':
        if(empty($cur_player))
            die('no user');
        $update_terms_conditions_version_service = new UpdateTermsConditionsVersionService();
        $accept = $_REQUEST['tacation'] == 'accept';
        $reject = $_REQUEST['tacation'] == 'cancel';
        $update_terms_conditions_version_service->updateTermsConditionVersion($cur_player, $accept);

        if (lic('shouldOptOutMarketingOnTermsRejection')) {
            if ($reject) {
                /** @var DBUserHandler $DBUserHandler */
                $DBUserHandler = phive('DBUserHandler');
                $DBUserHandler->privacySettingsDoAll($cur_player, 'opt-out');
            }
        }

        echo 'ok';
        break;
    case 'bonus-tac-action':
        if (empty($cur_player)) {
            die('no user');
        }

        if ($_REQUEST['btcaction'] == 'accept') {
            $cur_player->setBtcVersion();
        } else {
            $cur_player->setSetting('bonus_tac_block', 1);
        }

        echo 'ok';

        break;
    case 'tac-sport-action':
        if(empty($cur_player))
            die('no user');
        if($_REQUEST['tacation'] == 'accept') {
            $cur_player->setSportTcVersion();
        }else {
            $cur_player->setSetting('tac_block_sports', 1);
        }
        echo 'ok';
        break;

    case 'prp-action':
        if(empty($cur_player))
            die('no user');

        if(!empty($_REQUEST['prpaction']) )
            $cur_player->setPpVersion();

        echo 'ok';
        break;

    case 'update-cinfo':
        if (!empty($_SESSION['toupdate'])) {
            if (isset($_SESSION['toupdate']['username']) && strpos($_SESSION['toupdate']['username'], '@') !== false) {
                $_SESSION['mg_username'] = $_SESSION['toupdate']['username'];
                $_SESSION['username'] = $_SESSION['toupdate']['username'];
            }
            if (isset($_SESSION['toupdate']['email'])) {
                if (filter_var($_SESSION['mg_username'], FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['toupdate']['username'] = $_SESSION['toupdate']['email'];
                }
            }

            $user = cu($_SESSION['mg_id']);

            $is_mobile_or_email_changed = (isset($_SESSION['toupdate']['email']) &&
                    $_SESSION['toupdate']['email'] !== $user->getAttribute('email')) ||
                (isset($_SESSION['toupdate']['mobile']) &&
                    $_SESSION['toupdate']['mobile'] !== $user->getMobile());

            $newContactInfo = phive('DBUserHandler')->updateContactInformation($_SESSION['mg_id'], $_SESSION['toupdate']);
            if ($newContactInfo->hasErrors()) {
                $firstError = $newContactInfo->getErrors()[0];
                if($firstError === 'province.update.fail') {
                    unset($_SESSION['toupdate']);
                }
                et($firstError);
                break;
            }

            $user->setAttrsToSession();

            if ($is_mobile_or_email_changed) {
                $user->setSetting('change-cinfo-unlock-date', phive()->hisMod('+30 day'));
            }

            $user->setSetting('change-address-unlock-date', phive()->hisMod('+30 day'));

            unset($_SESSION['toupdate']);
            et('contact.details.updated.successfully');
        }
        break;
    case 'pnp-update-userinfo':
        echo phive('PayNPlay')->updateUserInfo($_REQUEST, $cur_player);
        break;
    case 'validate-code':
        if(!$cur_player){
            $cur_player = cuRegistration();
        }

        if(empty($cur_player)) {
            throw new Exception("No user found to validate code");
        }

        echo phive('DBUserHandler')->validateCommunicationChannelCode($_REQUEST, $cur_player);
        break;
    case 'pnp-welcome-mail':
        echo phive('MailHandler2')->sendWelcomeMail($cur_player);
        break;
    case 'deposit-pending':
        if(!empty($_SESSION['mg_id'])){
            $pendings = phive('Cashier')->userPendingsByStatus($_SESSION['mg_id'], 'pending', array('flushed' => 0));
            $pending = empty($pendings) ? array() : $pendings[0];
            if(!empty($pending))
                depositTopPending($pending);
        }
        echo ' ';
        break;
    case 'activate-welcome-offers':
        echo phive('Bonuses')->activateWelcomeOffers($_REQUEST['user_id']);
        break;
    case 'set_play_status' :
        phMset(mKey($_SESSION['mg_id'], 'is_playing'), empty($_REQUEST['status']) ? 0 : 1, 60);
        echo 'ok';
        break;
    case 'get_play_status' :
        $status = $cur_player->mGet('is_playing');
        echo empty($status) ? "false" : t('already.playing.html');
        break;
    case 'poli-end':
        if($_REQUEST['poli_action'] == 'cancel'){
            et('poli.transaction.cancelled.html');
            exit;
        }

        sleep(3);

        require_once __DIR__.'/../Cashier/Poli.php';
        $poli 	= new Poli();
        $user 	= cu();
        $info 	= unserialize($user->getSetting('poli_nudge'));
        $user->deleteSetting('poli_nudge');
        if($info['TransactionStatusCode'] == 'Completed'){
            echo $poli->getMsg($info);
        }else if(empty($info)){
            $res = $poli->credit($_SESSION['poli_token']);
            if($res === false){
                et('err.unknown');
                exit;
            }

            $info 	= unserialize($user->getSetting('poli_nudge'));
            if(!empty($info))
                echo $poli->getMsg($info);
            else
                et('poli.service.unavailable');
        }else
        echo $poli->getErrMsg($info, $user);
        break;
    case 'get-phone-us' :
        phoneUsMailForm();
        break;
    case 'submit-phone-us':
        require_once __DIR__.'/../Former/Validator.php';
        $err = array();
        $ret = array();

        foreach(array('country', 'mobile', 'email', 'question') as $f){
            if(empty($_POST[$f]))
                $err[$f] = 'empty.err';
        }

        if(PhiveValidator::captchaCode() != $_POST['captcha'])
            $err['captcha'] = 'captcha.err';

        $validator = PhiveValidator::start($_POST['email']);
        if ($validator->email()->error) {
            $err['email'] = $validator->error;
        }

        if(empty($err)){
            $mh 	= phive('MailHandler2');
            $from 	= "notifications@".$mh->getSetting('domain').".com";
            $country 	= phive("Localizer")->getBankCountryByIso($_POST['country']);
            $clean 	= phive("Mosms")->cleanUpNumber($_POST['mobile']);
            $content 	= "Email: {$_POST['email']} <br><br>
        Phone #: {$country['calling_code']} $clean <br><br>
        Message: {$_POST['question']}";
            $mh->saveRawMail('Phone Me Request', $content, $from, $mh->getSetting('support_mail'), $from, 0);
            $ret['res'] = t('phone.me.success.html');
        }else
        $ret['res'] = 'fail';

        foreach($err as $f => $e)
            $ret['error'] .= t('register.'.$f).' '.t($e).'<br>';

        echo json_encode($ret);

        break;
    case 'send-email-us':
        require_once __DIR__ . '/../Former/Validator.php';
        $err = array();
        $ret = array();

        $validator = PhiveValidator::start($_POST['from']);
        if ($validator->email()->error) {
            $err['email'] = $validator->error;
        }

        foreach (array('from', 'subject', 'message') as $f) {
            if (empty($_POST[$f])) {
                $err[$f] = 'empty.err';
            }
        }
        if (PhiveValidator::captchaCode() != $_POST['captcha']) {
            $err['captcha'] = 'captcha.err';
        }

        if (empty($err)) {
            $service = new SendContactUsService();
            $ret['res'] = t($service->send(new SendContactUsData(
                $_POST['from'],
                $_POST['subject'],
                $_POST['message']
            )));
        } else {
            $ret['res'] = 'fail';
        }

        foreach ($err as $f => $e) {
            $ret['error'] .= t($e).'<br>';
        }

        echo json_encode($ret);

        break;
    case 'get_registration_country_info':
        $data = phive('DBUserHandler')->getRegistrationData($_REQUEST['iso']);
        die(json_encode($data));
        break;
    case 'get_game' :
        echo json_encode(phive('MicroGames')->getByGameId($_POST['game_id']));
        break;
    // TODO old way of calling mobile game...will be removed when we have a stable new way to call games with all the checks in place (See "get-mobile-game-launcher-url") + See CH22091 for cleanup instruction
    case 'get_mobile_url' :
        $url = '';
        $mg = phive('MicroGames');


        if(phive()->getSetting('lga_reality') === true && isLogged()){
            $msg = phive('Casino')->lgaLimitsCheck('', false);
            if ($msg != 'OK')
                $url = "/mobile/message/?showstr=$msg";
        }
        if(empty($url)){
            $args         = $_REQUEST;
            $lang = $_REQUEST['lang'];
            $args['type'] = 'mobile';
            $mg->handle_redirect_url = true;
            $g   = $mg->getByGameRef($_POST['game_ref'], 1);
            list($url, $redirect_url) = $mg->onPlay($g, $args); // final game url
            // Check if we are going to show this game in an iframe or not
            if ($mg->gameInIframe($g)){
                if (phive('Licensed')->getSetting('debug_iframe') === true) {
                    phive()->dumpTbl('debug_iframe', [$_REQUEST, $g, $args]);
                }
                $encodedUrl = urlencode($url);
                $endpoint = phive('Casino')->getSetting('mobile_iframe_url') ?? "/phive/modules/Micro/html/play_mobile.php";
                $url = "$endpoint?gref={$_POST['game_ref']}&lang=$lang&url={$encodedUrl}";
            }

            $_SESSION['cur_mobile_url'] = $url;
            if ($redirect_url) {
                $_SESSION['rg_login_info_callback'] = $url;
                $url = $redirect_url;
            }
        }
        echo json_encode(['url' => $url]);
        break;
    case 'viewed-resp-gaming':
        if(lic('hasViewedResponsibleGaming', [$cur_player], $cur_player)){
            die('ok');
        }

        $occupation = trim($_POST['occupation']);
        $industry = trim($_POST['industry']);
        $max_loss_limit = lic('getHighestAllowedLossLimit', [$cur_player], $cur_player) ?? PHP_INT_MAX;
        $spending_amount = phive('Cashier')->cleanUpNumber($_POST['spending_amount']);
        $is_over_the_max_loss_limit = ($spending_amount * 100) > $max_loss_limit;
        $spending_amount = $is_over_the_max_loss_limit ? $max_loss_limit / 100 : $spending_amount;

        if(!empty($cur_player) && !empty($occupation) && !empty($spending_amount)){
            $previous_occupation = $cur_player->getSetting('occupation');

            $cur_player->setSetting('viewed-resp-gaming', 1);
            $cur_player->setSetting('occupation', $occupation);
            $cur_player->setSetting('spending_amount', $spending_amount * 100);
            $cur_player->addComment('An RG interaction with customers was made before their first game play. We asked the player to consider reviewing our limit tools on our responsible gambling page before first play. We especially requested the user to set a loss limit. The user agreed to set a loss limit to '. $spending_amount  . ' ' .  $cur_player->getCurrency(), 0, 'rg-action');

            if(!lic('hasViewedOccupationPopup', [$cur_player]) && !empty($industry)) {
                $cur_player->setSetting('industry', $industry);
                $cur_player->setSetting('updated-occupation-data', 1);
            }

            rgLimits()->addAllByType($cur_player, 'loss', $spending_amount * 100);

            if (!empty($previous_occupation)) {
                phive('UserHandler')->logAction($cur_player, "Customer updated occupation from {$previous_occupation} to {$occupation}", 'occupation-update');
            }

            if ($is_over_the_max_loss_limit) {
                $comment = "When checking customers affordability, we took an action to lower limit to $spending_amount
                    amount. Customer can request higher limit by submitting evidence that they can afford it.";
                $cur_player->addComment($comment, 0, 'automatic-flags');
                $original_limit = (int)$_POST['spending_amount'];
                $comment = "We lowered customers loss limit to $spending_amount amount from $original_limit
                    due to affordability information we have on customer.";
                $cur_player->addComment($comment, 0, 'rg-action');
                echo 'over-limit';
            } else {
                echo 'ok';
            }
        }else{
            echo 'nok';
        }
        break;
    case 'update-occupation-data':
        $isSowdForm = isset($_POST['isSowdForm']) ? $_POST['isSowdForm'] : false;

        if(!$isSowdForm && lic('hasViewedResponsibleGaming', [$cur_player], $cur_player) &&  lic('hasViewedOccupationPopup', [$cur_player], $cur_player)){
            die('ok');
        }

        $occupation = $_POST['occupation'];
        $industry = $_POST['industry'];
        if(!empty($cur_player) && !empty($occupation)){
            $previous_occupation = $cur_player->getSetting('occupation');

            $cur_player->setSetting('updated-occupation-data', 1);
            $cur_player->setSetting('occupation', $occupation);
            $cur_player->setSetting('industry', $industry);
            $cur_player->deleteSetting('show_occupation_popup');

            if (!empty($previous_occupation)) {
                phive('UserHandler')->logAction($cur_player, "Customer updated occupation from {$previous_occupation} to {$occupation}", 'occupation-update');
            }
           echo 'ok';
        }else{
            echo 'nok';
        }
        break;
    case 'viewed-account-policy':
        if(!empty($cur_player))
            $cur_player->setSetting('viewed-account-policy', 1);
        echo 'ok';
        break;
    case 'request-net-deposit-limit-increase':

        $netDepositLimitService = new NetDepositLimitService();
        $result = $netDepositLimitService->increaseLimit();

        if (! $result) {
            return 'nok';
        }

        echo 'ok';
        break;
    case 'request-customer-net-deposit-limit-increase':

        $customerNetDepositLimitService = new CustomerNetDepositLimitService();
        $result = $customerNetDepositLimitService->increaseLimit();

        if (! $result) {
            return 'nok';
        }

        echo 'ok';
        break;
    case 'unique-game-session-check':
        $result = ['success' => true];
        if (empty($cur_player)) {
            $result['success'] = false;
        } else {
            $_SESSION['next_unique_game_session_id'] = $_POST['cache'] ? $_POST['game_session_tag'] : null;
            $sessions = lic('handleWsUserGameSessions', [$cur_player->getId()], $cur_player);
            // if more than 1 session already
            // or if the only existing session is not the one provided on game_session_tag
            if ((count($sessions) > 1) || (!empty($sessions) && $sessions[0] !== $_POST['game_session_tag'])) {
                $result['success'] = false;
                $result['message'] = 'game-session-limit-unique.block';
            }
        }
        die(json_encode($result));
    case 'check_game':
        $mg = phive('MicroGames');

        $show_demo = filter_var($_POST['show_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (lic('noDemo', [$show_demo]) || (!$show_demo && !isLogged())) {
            echo 'registration';
            exit;
        }

        if(empty($_POST['game_ref'])){
            $game = $mg->getByGameId($_POST['game_id']);
            $play_func = "playGameNow('{$_POST['game_id']}', '', '{$game['game_url']}')";
        }else{
            $game = $mg->getByGameRef($_POST['game_ref']);
            $play_func = "playGameNowByRef('{$_POST['game_ref']}', '', '{$game['game_url']}')";
        }
        if ($game['tag'] == 'live-casino' && !isLogged()) { // if launching game tag is live-casino and user !isLogged
            echo 'registration'; // will show registration popup
            exit;
        }

        if(empty($cur_player)){
            die('ok');
        }

        list($res, $entry) = phive('Bonuses')->isWrongGame($_SESSION['mg_id'], $game);

        $map = ['force_self_assessment_test' => 'force_self_assesment_popup', 'force_deposit_limit' => 'force_deposit_limit'];
        foreach($map as $setting => $html_file){
            if($cur_player->hasSetting($setting)){
                die($html_file);
            }
        }


        /* user can play without restrictions
        if($cur_player->getDocumentRestrictionType() !== false){
            die('restricted');
        }*/

        if (!is_null($doc = phive('CasinoCashier')->hasForcedShowSourceOfFunds($cur_player))) {
            $res = 'show_source_of_funds:' . $doc[1]['id'];
            die($res);
        }

        $popup = lic('showSessionBalancePopups', [$cur_player, $game, true, true], $cur_player);

        if(!empty($_POST['isBos']) && ($_POST['user_country'] === 'ES')){
            //when switching from BOS to a normal game in Spain, delete this redis entry to avoid it being set too early
            phMdelShard('ext-game-session-stake');
        }

        // Set game session balance from the game page only
        if (!empty($popup) && $popup !== 'game_session_balance_set') {
            die($popup);
        }

        if($res !== false){
            $bonus = phive('Bonuses')->getBonus($entry['bonus_id']);
            failBonusWrongGame($play_func, $bonus);
            exit;
        }

        lic('preventMultipleGameSessions', [$cur_player, false, true], $cur_player);

        echo 'ok';
        break;

    case 'get_game_mode' :
        $game = phive('MicroGames')->getByGameId($_POST['game_id']);
        // As data passed from FE to this AJAX call is not consistent, we fallback to check if ext_game_name is passed instead.
        if (empty($game)) {
            $game = phive('MicroGames')->getByGameRef($_POST['game_id']);
        }
        $jsongame = json_encode($game);
        echo "<script>var curGame = $jsongame;</script>";
        //topPlayBar($game);
        break;
    case 'get_pretty_total':
        efEuro(phive('UserHandler')->userTotalCash($_SESSION['mg_id']));
        break;
    case 'get_balances':
        if(!empty($_SESSION['mg_id'])){
            $balances = phive('Casino')->balances();
            echo json_encode($balances);
        }else
        echo json_encode(array('bonus_balance' => 0, 'cash_balance' => 0));
        break;

    case 'get_logged_balances' :
        if(!empty($_SESSION['mg_id'])){
            $balances = phive('Casino')->balances();
            $balances['msg'] = phive()->ob('noCashBox');
            echo json_encode($balances);
        }else
        echo json_encode(array('bonus' => 'not_logged', 'normal' => 'not_logged'));
        break;
    case 'verify_email':
        // NOTE: for the new registration we are not doing this here anymore,
        // instead it's verified together with the other fileds in step 2
        $user = cu();
        if(empty($user))
            die('no user');
        if($_REQUEST['code'] == $user->getSetting('email_code')){
            $user->setSetting('email_code_verified', 'yes');
            phive('MailHandler2')->sendWelcomeMail($user);
            unset($_SESSION['email_code_shown']);
            echo 'ok';
        }else
            et('wrong.email.code');
        break;
    case 'lga_limits':
        if(empty($cur_player))
            die('no player');
        $msg = $cur_player->mGet('lgalimit-msg');
        if(empty($msg))
            echo 'OK';
        else{
            $cur_player->mSet($key, '');
            echo t($msg);
        }
        break;
    case 'verify' :
        phive('Cashier')->sendVerifyReminder($_POST['id']);
        echo 'ok';
        break;
    case 'check_withdrawal' :
        // TODO henrik remove
        $result = phive('Cashier')->hasWithdrawnSince('', $cur_player>getId(), '-30 day');
        echo $result ? t('withdraw.warning.html') : 'ok';
        break;
    case 'check_username_local' :
        $too_many_attempts = limitAttempts($_REQUEST['action']);

        if($too_many_attempts) {
            echo 'toomanyattempts';
        } else {
            phive('UserHandler')->checkExistsByAttr('username', $_POST['attr']);
        }
        break;
    case 'reg_select':
        $fc 		= new FormerCommon();
        $end_year 	= phive('SQL')->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$_POST['country']}'");
        dbSelect("birthyear", $fc->getYears($end_year), '', array('', t('year')));
        break;
    case 'check_username' :
        phive('UserHandler')->checkExistsByAttr('username', $_POST['attr']);
        break;
    case 'check_email' :
        $too_many_attempts = limitAttempts($_REQUEST['action'], $_POST['attr']);

        if($too_many_attempts) {
            echo 'toomanyattempts';
        } else {
            // When a user goes back to step 1 of the registration, the email should be allowed to be the same as the email that has been saved for that user
            if(!empty($_SESSION['rstep2']) && $_SESSION['rstep1']['email'] == $_POST['attr']) {
                echo 'available';
            } else {
                phive('UserHandler')->checkExistsByAttr('email', $_POST['attr']);
            }
        }
        break;
    case 'check_mobile' :
        $too_many_attempts = limitAttempts($_REQUEST['action'], $_POST['attr']);

        if($too_many_attempts) {
            echo 'toomanyattempts';
        } else {
            // When a user goes back to step 1 of the registration, the mobile should be allowed to be the same as the mobile that has been saved for that user
            if(!empty($_SESSION['rstep2']) && $_SESSION['rstep1']['full_mobile'] == $_POST['attr']) {
                echo 'available';
            } else {
                phive('UserHandler')->checkExistsByAttr('mobile', $_POST['attr']);
            }
        }
        break;
    case 'url-from-id' :
        echo phive()->getSiteUrl() . phive('MicroGames')->getUrl($_POST['game-id']);
        break;
    case 'send-email-code':
        $regenerate = !!$_REQUEST['regenerate'];

        $result = phive('UserHandler')->sendEmailCode($regenerate);
        echo $result;
        break;
    case 'new-pwd':
        phive()->sessionStart();

        $user = cu();

        /** @var \Laraphive\Domain\User\DataTransferObjects\UpdateUserPasswordResponse $response */
        $response = phive('UserHandler')->updatePassword($user->getId(), $_REQUEST['pwd']);

        $has_errors = $response->hasErrors();
        $error = $response->getError();

        if (!$has_errors) {
            phive('UserHandler')->reload('', $_REQUEST['pwd']);
        }

        echo $has_errors ? t($error) : 'ok';
        break;
    case 'new-pwd-on-login':
        phive()->sessionStart();

        $user_id = phMget('pwd-change-on-next-login-user-id-'.session_id());

        if (!$user_id) {
            echo 'expired';
            break;
        }

        $user = cu($user_id);
        $new_password = $_REQUEST['pwd'];

        $used_same_password = phive('UserHandler')->checkPassword($user, $new_password);
        if ($used_same_password) {
            echo t('login.reset-password.error.used-previously');
            break;
        }

        /** @var \Laraphive\Domain\User\DataTransferObjects\UpdateUserPasswordResponse $response */
        $response = phive('UserHandler')->updatePassword($user->getId(), $new_password);

        $has_errors = $response->hasErrors();
        $error = $response->getError();

        if (!$has_errors) {
            phMdel('pwd-change-on-next-login-user-id-'.session_id());
            $user->deleteSetting('pwd-change-on-next-login');
        }

        echo $has_errors ? t($error) : 'ok';
        break;
    case 'is_deposit_blocked':
        $user = cu();

        if(empty($user)) {
            echo json_encode([
                'is_logged_in' => false,
            ]);
            break;
        }

        echo json_encode([
            'is_logged_in' => true,
            'is_deposit_blocked' => $user->isDepositBlocked(),
        ]);
        break;
    case 'pay-all':
        $tids = json_decode($_POST['ids'], true);
        phive('Cashier')->payAll($tids);
        echo 'All the transactions were successfully released.';
        break;
    case 'delete' :
        $table 	= empty($_POST['table']) ? 'queued_transactions' : $_POST['table'];

        if($table == 'queued_transactions'){

            $result = phive('Cashier')->deleteTrans($_POST['id'], $table);
            $trans = phive('Cashier')->getTrans($_POST['id']);
            if($trans['bonus_entry'] != 0)
                phive('Bonuses')->fail($trans['bonus_entry']);
            phive("UserHandler")->logAction($trans['user_id'], "Cancelled queued transaction of type ".$trans['transactiontype'], "cancel-queued");
            echo empty($result) ? 'fail' : 'ok';

        }else if($table == 'pending_withdrawals'){

            $p = phive('Cashier')->getPending($_POST['id']);

            if (!in_array($p['status'], ['pending', 'processing'])) {
                echo "Can't delete {$p['status']} withdrawal";
                exit;
            }

            if (
                ($p['status'] === 'pending' && !p('accounting.section.pending-withdrawals.actions.cancel')) ||
                ($p['status'] === 'processing' && !p('accounting.section.pending-withdrawals.actions.cancel-processing'))
            ) {
                echo 'Permission denied to delete withdrawal';
                exit;
            }

            if (cu($p['user_id'])->hasSetting('source_of_funds_requested-fraud-flag')) {
                et('pending.cancelled.failed');
                exit;
            }

            $result = phive('Cashier')->disapprovePending($_POST['id'], false, false, false, 0, $_REQUEST['send_mail'] != 'no', uid());

            if($result === false)
                echo "Error: the user's balance could not be restored.";
            else{
                echo "Withdrawal cancelled successfully. New balance: $result.";
                $user = cu($p['user_id']);
                lic(
                    'manipulateFraudFlag',
                    [$user,$p['payment_method'], $p['scheme'], $_POST['id'], AssignEvent::ON_WITHDRAWAL_CANCELLED],
                    null,
                    null,
                    $user->data['country']
                );
            }
        }
        break;
    case 'unverify':
        if(!empty($_POST['user_id']))
            $user    = cu($_POST['user_id']);
        else{
            $p       = phive('Cashier')->getPending($_POST['id']);
            $user    = cu($p['user_id']);
        }
        //idpic
        //addresspic
        foreach(array('idpic', 'addresspic') as $stag){
            $pic = $user->getSetting($stag);
            $user->deletePic($pic); //TODO henrik remove
            $user->deleteSetting("{$stag}_orig");
            $user->deleteSetting($stag);
        }
        $user->deleteSetting('id-verified');
        $user->deleteSetting('address-verified');
        $user->unVerify();
        if(phive()->moduleExists("MailHandler2"))
            phive("MailHandler2")->sendMail('verification.timeout', $user);
        //echo "Player unverified. New balance: $result.";
        echo "Player unverified.";
        break;
    case 'pay' :
        $uid = uid();
        if (empty($uid)) {
            die("No session so quitting");
        }

        $postId = $_POST['id'];
        if (in_array($postId, $_SESSION['paid_pending'])) {
            die("Already paid pending with id {$postId}");
        }

        $table = empty($_POST['table']) ? 'queued_transactions' : $_POST['table'];
        if ($table == 'pending_withdrawals') {
            $pending = phive('Cashier')->getPending($postId);
            if ($pending['status'] != 'pending') {
                die("Already {$pending['status']}");
            }

            if (phive('Cashier')->fraud->checkAdjustmentFlag($pending, $uid) !== true) {
                die("You cannot approve a withdrawal if you made a manual adjustment on this account yourself.");
            }
            if(phive()->getSetting('q-pend') === true){
                phive('Site/Publisher')->single('main', 'Cashier', 'approvePending', [$_POST['id'], (float)trim($_POST['amount']), $uid]);
                echo 'ok';
            } else {
                echo phive('Cashier')->approvePending($postId, (float)trim($_POST['amount']), $uid);
            }
            $_SESSION['paid_pending'][] = $postId;
        } else if ($table == 'queued_transactions') {
            $_SESSION['paid_pending'][] = $postId;
            phive('Cashier')->releaseQdTransaction($postId, 'queued_transactions');
        }
        break;
    case 'use-trophy-award':
        $trophyAwardService = TrophyAwardServiceFactory::create();

        $response = $trophyAwardService->activateTrophyAward((int)$_REQUEST['aid'], $cur_player);

        echo json_encode($response);
        break;
    case 'cancel-pending':
        $pid = (int)$_REQUEST['id'];
        $p = phive('Cashier')->getPending($pid);
        if (cu($p['user_id'])->hasSetting('source_of_funds_requested-fraud-flag')) {
            et('pending.cancelled.failed');
            exit;
        }
        if($p['user_id'] == $_SESSION['mg_id'])
            phive('Cashier')->disapprovePending($p, false);
        et('pending.cancelled.refresh');
        modalTwo('reload.game', 'location.reload(false)', 'continue.game', 'mboxClose()');
        break;
    case 'get-bonus':
        $b = phive('Bonuses')->getBonus((int)$_REQUEST['bid']);
        echo json_encode($b);
        break;
    case 'retry-frb':
        $e = phive('Bonuses')->getBonusEntry((int)$_REQUEST['eid']);
        $b = phive('Bonuses')->getBonus((int)$e['bonus_id']);
        $mod = phive($b['bonus_tag']);
        if($mod->frbStatus($e) === 'activate'){
            $res = $mod->awardFRBonus($e['user_id'], $e['ext_id'], $b['reward'], $b['bonus_name'], $e);
            if($res == 'fail')
                die('Bonus activation failed.');

            if($res === false)
                die('Connection problem, try again.');

            $mod->activateFreeSpin($e, '', $b, $res);
            echo 'Bonus activated successfully, it might take a while before remote system reports success.';
        }
        break;
    case 'on-deposit-complete':
        $uid = $_SESSION['mg_id'];
        $res = phive('Cashier')->checkDepLimitPlayBlock($uid, true);
        if($res !== true){
            et('dep.limit.playblock.html');
        }else {
            echo 'ok';
        }
        break;
    case 'reality-check-msg':
        if(!is_object($cur_player))
            die('no player');
        $ud            = $cur_player->data;
        $win_loss      = $cur_player->winLossBalance()->getTotal();
        $play_duration = round(phive()->subtractTimes(phive()->hisNow(), $ud['last_login'], 'm') / 60, 2);
        echo tAssoc('lga.reality.msg.html', array('hours' => $play_duration, 'csym' => $ud['currency'], 'winloss' => nfCents($win_loss, true)));
        break;
    case 'reality-check-duration':
        if(!is_object($cur_player))
            die('no player');
        $ud         = $cur_player->data;
        $win_loss   = $cur_player->winLossBalance()->getTotal();
        $intv       = phive('Casino')->startAndGetRealityInterval();
        $rc_arr     = [];
        $rg_arr     = [];

        if ($intv) {
          $play_duration = round(phive()->subtractTimes(phive()->hisNow(), $ud['last_login'], 'm'), 2);
            $rc_arr = array(
                'status'                => 'ok',
                'message'               => 'user has reality checks',
                'play_duration_minutes' => $play_duration,
                'csym'                  => $ud['currency'],
                'winloss'               => nfCents($win_loss, true),
                'player_check_interval' => $intv);
        }else{
            $rc_arr = array('status' => 'info', 'message' => 'user doesnt have reality checks');
        }

        echo json_encode(['rc' => $rc_arr]);

        break;
    case 'set-reality-check-duration':
        $rci = abs((int)$_POST['reality_check_interval']);
        $response = lic('isValidRealityCheckDuration', [$rci], $cur_player);

        if ($response['status'] === 'error') {
          echo json_encode($response);
          return false;
        }

        rgLimits()->saveLimit($cur_player, 'rc', 'na', $rci);
        echo json_encode($response);
        break;
    case 'init-reality-check':
        if(empty($cur_player)) {
            die( json_encode('no user'));
        }
        $lang = $_POST['lang'] ?? phive('Localizer')->getCurNonSubLang();
        $rc_configs = lic('getRcConfigs',[], $cur_player);

        $allow_empty_rc = lic('getLicSetting', ['allow_empty_rc']);
        $show_rc_popup_on_first_game_only = lic('getLicSetting', ['show_rc_popup_on_first_game_only']);

        $rc_msg_data = lic('getRealityCheck', [$cur_player, $lang, $_POST['ext_game_name']], $cur_player);
        $trigger_popup = !empty(rgLimits()->getRcLimit($cur_player));
        $game_play_paused = lic('gamePlayPaused', [$cur_player], $cur_player);
        $log_action_when_trigger = $_POST['log_action_when_trigger'];

        // we already paused the game so just update the timer
        if ($game_play_paused) {
            $log_action_when_trigger = 'false';
        }
        /**
         * $to_show_popup, on initial page load, is true only when we need to setup the RC the first time
         * Additional config `allow_empty_rc` added to not show RC popup even if RC is not set for user. [Case - ES]
         * otherwise it will be handled by logic on "logRealityCheckAction"
         */
        $to_show_popup = (!$allow_empty_rc && !$trigger_popup) && $log_action_when_trigger === 'false';
        if ($trigger_popup && $log_action_when_trigger === 'true') {
            $to_show_popup = lic('logRealityCheckAction', [$cur_player, $rc_msg_data, $_POST['ext_game_name']], $cur_player);
            if ($to_show_popup && !$game_play_paused) { // reached limit
                lic('pauseGamePlay', [$cur_player], $cur_player);
            }
        }

        $refused_reality_check = $cur_player->hasSetting('refused-reality-check');

        if ($show_rc_popup_on_first_game_only && !$trigger_popup) {
            $to_show_popup = false;
            if (!$refused_reality_check) {
                $to_show_popup = true;
            }
        }

        $rc_info = [
            'status' => 'ok',
            'rc_show_dialog' => $trigger_popup || $allow_empty_rc || $refused_reality_check ? 'false' : 'true',
            'htmlMsg' => $rc_msg_data,
            'rc_elapsedTime' => lic('rcElapsedTime',[$cur_player],$cur_player),
            'to_show_popup' => $to_show_popup,
            'remaining_play_pause' => (int)lic('gamePlayPaused', [$cur_player, true], $cur_player)
        ];

        echo json_encode(array_merge($rc_configs, $rc_info));
        break;
    case "refused-reality-check":
        lic('onRealityCheckRefused',[$cur_player], $cur_player);
        break;
    case "save-step-2-in-session":
        $data = $_REQUEST['data'];

        foreach ($data as $key => $value) {
            $_SESSION['rstep2'][$key] = $value;
        }

        $_SESSION['rstep1']['full_mobile']      = $_SESSION['rstep1']['country_prefix'] . $_SESSION['rstep1']['mobile'];

        break;
    case "get-woj-jackpots":
        echo phive('DBUserHandler/JpWheel')->getCache(false);
        break;
    case 'update-privacy-settings':
        try {
            if (!$cur_player) $cur_player = cuRegistration();
            if (empty($cur_player)) throw new Exception("No user found on update privacy settings");

            if (privileged()) {
                echo json_encode(['status' => 'nok', 'message' => "<p>Update privacy settings by admin not supported.</p>", 'title' => 'Error']);
                break;
            }

            /** @var DBUserHandler $uh */
            $uh = phive('DBUserHandler');

            /** @var PrivacyHandler $ph */
            $ph = phive('DBUserHandler/PrivacyHandler');

            $service = new UpdatePrivacyDashboardSettingsService();

            if (!empty($_REQUEST['privacyaction'])) {
                $privacy_action_map = [
                    'accept' => 'opt-in',
                    'cancel' => 'opt-out',
                ];
                $privacy_action = $privacy_action_map[$_REQUEST['privacyaction']] ?? false;
                if(empty($privacy_action)) {
                    throw new Exception("Requested privacyaction mapping doesn't exist");
                }

                $uh->privacySettingsDoAll($cur_player, $privacy_action);

                $forceLinkPlatform= empty($_REQUEST['mobile']) ?  'desktop' : 'mobile';
                $link = phive('UserHandler')->getUserAccountUrl('', cLang(), $forceLinkPlatform);
                $res = ['status' => 'ok', 'link' => $link];
                echo json_encode($res);
                break;
            }

            $form_data = [];
            foreach ($_REQUEST['params'] as $elem) $form_data[$elem['name']] = $elem['value'];

            $ph->saveFormData($form_data);
            $res = ['status' => 'ok', 'message' => t('privacy.settings.success.message.html')];
        } catch (Exception $e) {
            error_log($e->getMessage());
            $res = ['status' => 'nok', 'message' => t('privacy.settings.error.message.html')];
        }

        $res['title'] = t('privacy.update.form.title');
        echo json_encode($res);
        break;
    case "get-tournament-from-ticket-award":
        $tournament = phive('Tournament')->getTournamentFromAwardId($_POST['award_id']);

        echo json_encode(['tournament_id'=>empty($tournament) ? null : $tournament['id']]);
        break;
    case 'update-booster-preference':
        $has_opted_out = $_POST['hasOptedOut'] === 'true';
        $user   = getCurUserIdOnAdminAction();
        $msg    = ($has_opted_out)
            ? t('my.booster.vault.opted.out')
            : t('my.booster.vault.opted.in');
        phive('DBUserHandler/Booster')->optToVault($user, !$has_opted_out);
        echo json_encode(['msg' => $msg]);
        break;
    case 'add-booster-to-credit':
        $user_id = $cur_player ? $cur_player->getId() : null;
        $user_id = !empty(getCurUserIdOnAdminAction()) ? getCurUserIdOnAdminAction() : $user_id;

        $result = phive('DBUserHandler/Booster')->addBoosterToCredit($user_id);
        echo json_encode($result);
        break;

    case "get-highest-rtp":
        $device_type = $_POST['device_type'] ?? 'flash';
        $data = phive('MicroGames')->getByPaymentRatio($_POST['date'], 'payout_ratio DESC LIMIT 1', $device_type);

        $rtp = ceil($data[0]['payout_ratio']*100);
        echo json_encode([ 'rtp' => $rtp ]);
        break;

    /**
     * This is used only for the new mobile game page, we return a JSON instead of printing the HTML like on the "normal" mobile website.
     * The filtering applied here are for searching the game name and/or for the main "tags" (New Games, Hot, Popular, Last Played)
     * Params (GET):
     * - filter - filter by a category (search|new|hot|popular|last_played)
     * - search - search string Ex. "starb"
     * - lang - default to "en"
     */
    case 'mobile-game-search':
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        $games = [];
        $error = [];
        $games_clean = [];
        $where_extra = '';

        $filter = $_GET['filter'];

        // This applies to all the filters except for "search"
        if(!empty($_GET['search'])) {
            $_GET['search'] = trim(phive()->rmNonAlphaNumsNotSpaces($_GET['search']));
            $where_extra = "mg.game_name LIKE '%{$_GET['search']}%'";
        }

        switch($filter) {
            case 'search':
                // when opening the list without any search string we display the same result as the popular filter by default
                if(empty($_GET['search'])) {
                    $games = $mg->getTaggedByWrapper('popular', 'mobile', null, true, $where_extra, true);
                } else {
                    $games = $mg->getAllGames(
                        "mg.game_name LIKE '%{$_GET['search']}%' AND mg.active = 1 ",
                        "*",
                        "html5",
                        true);
                }
                break;
            case 'new':
                $games = $mg->getTaggedByWrapper('subtag_footer', 'mobile', 'new.cgames', true, $where_extra, true);
                break;
            case 'hot':
            case 'popular':
                // same logic from desktop on FooterMenuBoxBase with a mobile filter applied.
                $games = $mg->getTaggedByWrapper($filter, 'mobile', null, true, $where_extra, true);
                break;
            case 'favorites':
                $user_id = $cur_player ? $cur_player->getId() : null;
                $games = $mg->getFavorites($user_id, $where_extra, 'added-desc', 'html5');
                if(empty($games)) {
                    $error = [
                        'msg' => t('no.favorites.yet'),
                        'action' => [
                            'label' => t('add.games'),
                            'type' => 'redirect-link',
                            'to' => llink('/mobile/favourites/')
                         ]
                    ];
                }
                break;
            case 'last_played':
                $games = $mg->getLastPlayed('mobile_last_played');
                if(!empty($_GET['search'])) {
                    $pattern = "/{$_GET['search']}/i";
                    $games = array_filter($games, function ($game) use ($pattern) {
                        return preg_match($pattern, $game['game_name']);
                    });
                }
                break;
            default: break;
        }
        // if the searched data contains boosted games we will show them first
        $boosted_games_to_display = 12; // TODO average value calculated from box_attributes "weekend_booster_plus_rows" (3) * "rcount" (4) this comes from client side ("3,4,5")
        list($games, $booster_games) = $mg->extractBoostedGamesFromList($games, $boosted_games_to_display);
        $games = array_merge($booster_games, $games);

        // chunking the result to first 100 to avoid loading too much data
        $chunks = array_chunk($games, 100);

        // Returning only the bare minimum data + Adding extra info (img, ribbon_pic)
        foreach($chunks[0] as $game) { // returning only the first chunk.
            $game_clean = [
                'game_name' => $game['game_name'],
                'ext_game_name' => $game['ext_game_name'],
                'network' => $game['network'],
                'img' => $mg->carouselPic($game),
//                'ribbon_pic' => displayGameRibbonImage($game, ['weekend_booster' => $this->weekend_booster_plus_pic], true),
                'ribbon_pic' => displayGameRibbonImage($game, ['weekend_booster' => 'booster'], true), // is "weekend_booster_plus_pic" even used?? cannot find nowhere in code, except for having the default value set...
            ];
            // "current_games_network" is passed by the client during search
            $mg->addMultiplayGameFlags($game_clean, $_GET['current_games_network'], $_GET['changing_game_index']);

            $games_clean[] = $game_clean;
        }

        if(!count($games_clean)) {
            // generic error message if no games are returned by the search, and no previous error is set
            if(empty($error)) {
                $error = [
                    'msg' => t('game.search.no.results'),
                    'action' => [
                        'label' => t('mobile.games.filter.reset'),
                        'type' => 'reset-search',
                        'to' => null
                    ]
                ];
            }
        }
        echo json_encode(['games' => $games_clean, 'error' => $error]);
//        echo json_encode($games_clean);
        break;
    /**
     * Return the URL to the new game page.
     */
    case 'get-mobile-game-page-url':
        $mg = phive('MicroGames');
        $game_ref = $_POST['game_ref'];
        $game = $mg->getByGameRef($_POST['game_ref'], 1);

        // we try to check if the game_id was passed, instead of the ext_game_name (Ex. from notification playGameDepositCheckBonus())
        // and we need to check the desktop version of the game in this case (cause notification actions were enabled only on desktop before)
        if(empty($game)) {
            $game = $mg->getByGameId($game_ref, 0);
        }

        // if a specific url doesn't exist for the mobile game we use the desktop game_url (this check is handled in diamondbet mobile_game.php too)
        // Gets the desktop game_url even if the desktop version is disabled.
        if(empty($game['game_url'])) {
            $game = $mg->getDesktopGame($game, null);
        }
        echo json_encode(['url' => '/mobile/play/'.$game['game_url']]);
        break;
    /**
     * Params required: (POST)
     * - game_ref - micro_games.ext_game_name
     * - lang - default to "en"
     */
    case 'get-mobile-game-launcher-url':
        $url = '';
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        $lang = $_POST['lang'] ?? 'en';
        $game_ref = $_POST['game_ref'];

        $return_obj = [
            'game' => [
                'launch_url' => false,
                'network' => false,
            ],
            'error' => null,
            'redirect' => null
        ];

        if(empty($game_ref)) {
            $return_obj['error'] = t('no.game.html'); // empty POST param
            die(json_encode($return_obj));
        }

        $game = $mg->getByGameRef($game_ref, 1); // 1 = mobile
        // TODO handle the error properly
        if(empty($game)) {
            $return_obj['error'] = t('no.game.html'); // empty game on DB
            die(json_encode($return_obj));
        }

        if(empty($url)){
            $args = $_POST;
            $args['type'] = 'mobile';

            $mg->handle_redirect_url = true;
            list($url, $redirect_url) = $mg->onPlay($game, $args); // final game url + redirect (if another action is required before)

            if(empty($url)) {
                $return_obj['error'] = t('invalid.launch.url');
                die(json_encode($return_obj));
            }
            // we need to redirect the player to do some action before he can play...
            if(!empty($redirect_url)) {
                $return_obj['redirect'] = $redirect_url;
                $desktop_game = $mg->getDesktopGame($game);
                // ...then after the action is completed, we redirect to the game selected inside mobile game search.
                // A manual override to "rg_login_info_callback" is needed here, as we want to go to a different page.
                $_SESSION['rg_login_info_callback'] = '/mobile/play/'.$desktop_game['game_url'];
                die(json_encode($return_obj));
            }
        }
        $return_obj['game'] = [
            'network' => $game['network'],
            'game_id' => $game['game_id'],
            'ext_game_name' => $game['ext_game_name'],
            'launch_url' => $url
        ];

        if (!empty($_POST['current_games']) && isset($_POST['changing_game_index']) && $_POST['changing_game_index'] == 0) {
            phive('Casino')->finishGameSession($cur_player->getId(), array_column($_POST['current_games'], 'ext_game_name'));

            if (!empty($cur_player) && lic('hasGameSessionRestrictions', [$cur_player], $cur_player)) {
                $return_obj['game']['launch_url'] = false;
                $return_obj['redirect'] = $_SERVER['HTTP_REFERER'];
                die(json_encode($return_obj));
            }
        }
        // "current_games_network" is passed by the client during search
        $mg->addMultiplayGameFlags($return_obj['game'], $_POST['current_games_network'], $_POST['changing_game_index']);
        die(json_encode($return_obj));
        break;
    case 'close-game-session':
        if(!$cur_player || empty($_POST['game_refs'])) {
            exit;
        }

        /** @var Casino $casino */
        $casino = phive('Casino');
        $casino->finishGameSession($cur_player->getId(), $_POST['game_refs']);
        break;
    case 'notify-new-game-session-open':
        if(!$cur_player) {
            exit;
        }

        lic('notifyNewGameSessionOpen', [$cur_player, $_POST['new_session']]);
        echo json_encode([ 'success' => true ]);
        break;

    case 'init-post-registration-popup':

        header('Content-Type: application/json; charset=utf-8');

        if(empty($cur_player)) {
            die(json_encode([
                'show_popup' => false,
                'reload' => false
            ]));
        }

        $post_registration_popups = licSetting('post_registration_popup', $cur_player);

        $popup = '';
        foreach ($post_registration_popups as $post_registration_popup) {

            // check if the rg limit is already saved for user.
            $limit = rgLimits()->getSingleLimit($cur_player, $post_registration_popup);

            if(empty($limit)) {
                $popup = "register_set_{$post_registration_popup}_limit_popup";

                $cur_player->setRequiredLimitsNotSet();
                break;
            }
        }

        if(empty($popup)) {
            $cur_player->resetRequiredLimitsFlag();
        }

        die(json_encode([
            'show_popup' => !empty($popup),
            'popup' => $popup,
            'reload' => empty($popup) && phive('UserHandler')->doForceDeposit($cur_player) // Reload the page if there is no deposit done yet. This prompting deposit popup
        ]));

        break;
    case 'validate-post-registration-limits':
        $limit_data = $_POST['limit_data'];

        header('Content-Type: application/json; charset=utf-8');

        $daily_limit = $weekly_limit = $monthly_limit = 0;
        $type = $limit_data['type'];

        $errors = [];
        foreach($limit_data['limits'] as $limit) {

            if(empty($limit['limit'])) {
                $key = $type.'-limit-'.$limit['time_span'];
                $errors[$key] = t("post-registration.{$key}.invalid");
                continue;
            }

            if($limit['time_span'] === 'day') {
                $daily_limit = $limit['limit'];
                continue;
            }

            if($limit['time_span'] === 'week') {
                $weekly_limit = $limit['limit'];
                continue;
            }

            if($limit['time_span'] === 'month') {
                $monthly_limit = $limit['limit'];
            }

        }

        if(empty($daily_limit) || empty($weekly_limit) || empty($monthly_limit)) {
            echo json_encode([
                'status' => 'error',
                'empty' => true,
                'errors' => $errors
            ]);
            break;
        }

        if($weekly_limit < $daily_limit || $weekly_limit > $monthly_limit) {
            $errors["{$type}-limit-week"] = t("post-registration.{$type}-limit-week.invalid");
        }

        if($monthly_limit < $daily_limit || $monthly_limit < $weekly_limit) {
            $errors["{$type}-limit-month"] = t("post-registration.{$type}-limit-month.invalid");
        }

        echo json_encode([
            'status' => empty($errors) ? 'success' : 'error',
            'errors' => $errors
        ]);

        break;
    case 'validate_registration_captcha':
        header('Content-Type: application/json; charset=utf-8');

        $service = new RegisterStep1Service();
        $data = $service->validateCaptcha($_POST['captcha_code']);

        if(! is_null($data)) {
            echo json_encode([
                'status' => 'error',
                'error_type' => $data->getType(),
                'timeout' => $data->getTimeout(),
                'message' => t($data->getMessage())
            ]);

            break;
        }

        echo json_encode([
            'status' => 'success'
        ]);

        break;
    case 'get_external_verification_mitID':
        /** we have to unset it just in case */
        unset($_SESSION['mitid_user_registration']);
        unset($_SESSION['mitid_user_connect_account']);

        $lang = ucfirst($_REQUEST['lang']);
        if ($_REQUEST['registration']) {
            $_SESSION['mitid_user_registration'] = true;
        }

        $too_many_attempts = limitAttempts('get_external_verification_mitID', 'mitID', 10);
        if ($too_many_attempts) {
            echo json_encode([
                'success' => false,
                'msg'     => t('register.toomanyattempts')
            ]);
            break;
        }

        $user = cu($_REQUEST['username']);

        if (empty($user)) {
            echo json_encode([
                'success' => false,
                'action'  => '',
                'msg'     => t('mitid.error')
            ]);
            break;
        }

        $mit_id_data = json_decode($user->getSetting('mit_id_data'), true);

        /**
         * We try to login user who has `user.settings.nid_data.idProviderPersonId`
         * We try to connect account for user who doesn't have `user.settings.nid_data.idProviderPersonId`
         */
        if (isset($mit_id_data['idProviderPersonId'])) {
            phive()->externalAuditTbl(
                "zignsec-mitid-session-decoded-result-identity_verification-dk",
                $_SESSION['mitid_session'],
                $user,
                0,
                200
            );
            $form_data = phive('DBUserHandler')->zs5->getMitIdForm($lang);
        } else {
            $_SESSION['mitid_user_connect_account'] = true;
            phive()->externalAuditTbl(
                "zignsec-mitid-session-decoded-result-match_cpr-dk",
                $_SESSION['mitid_session'],
                $user,
                0,
                200
            );
            $form_data = phive('DBUserHandler')->zs5->getMitIdCprMatch([
                'lang' => $lang,
                'nid' => $user->getNid(),
            ]);
        }

        if (empty($form_data['success']) || empty($form_data['result']['data']['redirect_url'])) {
            $success = false;
            $redirect_url = '';
        } else {
            $success = true;
            $redirect_url = $form_data['result']['data']['redirect_url'];
            $_SESSION['mitid_session'] = $form_data['result']['data']['id'];
            $_SESSION['mitid_user'] = $_REQUEST['username'];
            $_SESSION['mitid_user_lang'] = $_REQUEST['lang'];
        }

        echo json_encode([
            'success' => $success,
            'action'  => $redirect_url,
            'msg'     => t('mitid.error')
        ]);
        break;

    case 'check_deposit_limit':
        $check_deposit_limit = phive('DBUserHandler/RgLimits')->getDepositLimitWarning();
        echo $check_deposit_limit;
        break;
    case 'get_match_cpr_mitID':
        if ($_REQUEST['registration']) {
            $_SESSION['mitid_user_registration'] = true;
        }

        $too_many_attempts = limitAttempts('get_match_cpr_mitID', 'mitID', 10);
        if ($too_many_attempts) {
            echo json_encode([
                'success' => false,
                'msg'     => t('register.toomanyattempts')
            ]);
            break;
        }

        $get_cpr_data = phive('DBUserHandler')->zs5->getMitIdCprMatch($_REQUEST);
        if (empty($get_cpr_data['success']) || empty($get_cpr_data['result']['data']['redirect_url'])) {
            $success = false;
            $redirect_url = '';
        } else {
            $success = true;

            $_SESSION['cur_req_id'] = $get_cpr_data['result']['data']['id'];

            $redirect_url = $get_cpr_data['result']['data']['redirect_url'];
            $_SESSION['rstep1']['nid'] = $_REQUEST['nid'];
            $_SESSION['rstep1']['country'] = $_REQUEST['country'];
            $_SESSION['rstep1']['email'] = $_REQUEST['username'];
            $_SESSION['rstep1']['registration'] = $_REQUEST['registration'];
            $_SESSION['rstep1']['lang'] = $_REQUEST['lang'];
            $_SESSION['rstep1']['mobile'] = $_REQUEST['mobile'];
            $_SESSION['mitid_session'] = $get_cpr_data['result']['data']['id'];
            $_SESSION['mitid_user'] = $_REQUEST['username'];
            $_SESSION['mitid_user_lang'] = $_REQUEST['lang'];
        }
        phive()->externalAuditTbl(
            "zignsec-mitid-session-decoded-result-match_cpr-dk",
            $_SESSION['mitid_session'],
            cu($_REQUEST['username']),
            0,
            200
        );
        echo json_encode([
            'success' => $success,
            'action'  => $redirect_url,
            'msg'     => t('mitid.error')
        ]);
        break;

    case 'close-responsible-gaming-popup-box':
        unset($_SESSION['show_responsible_gaming_message_popup']);
        return json_encode([
            'status' => 'success'
        ]);
        break;
    case 'js_logs':
        $res = phive('Casino')->saveFELogs($_REQUEST['data']);
        die(json_encode($res));
        break;
    case 'validate_authentication_code':
        header('Content-Type: application/json; charset=utf-8');
        $auth_code = $_POST['auth_code'];
        $user = cu();

        if($auth_code !== $user->getSetting('2fa_code')){
            echo json_encode([
                'status' => 'error',
                'message' => 'auth.error.description'
            ]);
            break;
        }

        echo json_encode([
            'status' => 'success'
        ]);
        $user->deleteSetting('2fa_code');
        unset($_SESSION['show_add_two_factor_authentication_popup']);


        break;
    case 'get_2fa_code':
        $user = cu()->getId();
        lic('generate2faCode', [$user]);
        break;
    case 'bos_conf':
        $country = phive('IpBlock')->getCountry(remIp());
        $countries = phive('Config')->valAsArray('countries', 'ip-block');
        $is_whitelisted_ip = phive('IpGuard')->isWhitelistedIp(remIp());

        if (in_array($country, $countries) && !$is_whitelisted_ip) {
            $bos_conf = json_encode([
                'ACCESS_DENIED' => true,
                'REDIRECT'      => '/forbidden-country/'
            ]);
        } else {
            $bos_conf = json_encode(phive('Tournament')->getSetting('vue_config_api_endpoints'));
        }

        echo "var BOS_CONFIG_ENDPOINTS = $bos_conf;";
        break;
    case 'ga4_gtm':
        $google_analytics_data['google_tag_manager'] = phive()->getDomainSetting('google_tag_manager');
        $google_analytics_data['google_analytic_tag_key'] = phive()->getDomainSetting('google_analytic_tag_key');
        $google_analytics_data['enable_google_analytics_4'] = phive()->getDomainSetting('enable_google_analytics_4');
        $google_analytics_data['google_analytic_4_measurement_id'] = phive()->getDomainSetting('google_analytic_4_measurement_id');
        $google_analytics_config = json_encode('null');
        if(isset($_COOKIE['cookies_consent_info'])) {
           $google_analytics_config = json_encode($google_analytics_data);
       }
        echo "var GOOGLE_ANALYTICS_CONFIG = $google_analytics_config";
        break;
    case 'get_paynplay_iframe_url':
        echo phive('PayNPlay')->getDepositIframeUrl($_POST);
        break;
    case 'login_paynplay_after_redirect':
        echo phive('PayNPlay')->loginUserAfterRedirect($_POST);
        break;
    case 'check-pnp-transaction-status':
        $ext_id = phive('SQL')->escape($_REQUEST['ext_id'], false);

        phive('PayNPlay')->logger->info('PayNPlay Swish polling start', [
            'transaction_id' => $ext_id
        ]);

        $data = phive('PayNPlay')->getTransactionDataFromRedis($ext_id);

        if (empty($data) || empty($data['userid'])) {
            phive('PayNPlay')->logger->info('PayNPlay Swish fetching data from redis', [
                'ext_id' => $ext_id
            ]);

            echo json_encode([
                'status' => false,
            ]);
            break;
        }

        $user_id = $data['userid'];

        $sql = "SELECT * FROM deposits WHERE ext_id = $ext_id AND status = 'approved' LIMIT 1";
        $first_deposit = phive('SQL')->sh($user_id)->loadArray($sql)[0] ?? null;

        if ($first_deposit && $first_deposit['status'] === 'approved') {
            $status = 'success';
        } else {
            $status = 'error';
        }

        phive('PayNPlay')->logger->info('PayNPlay Swish polling status', [
            'status' => $status,
            'id'     => $user_id
        ]);

        echo json_encode([
            'status' => $status,
        ]);

        break;
    case 'nyx_gcm_url':
        echo json_encode(phive('Nyx')->getSetting('nyx_gcm_url'));
        break;
    case 'save-seasonal-promotion-session':
        $tag = strtoupper(json_decode($_POST['tag'], true)); // No need to json_encode
        $promotions_theme = phive('MailHandler2')->getSetting('seasonal_promotions_partner')['PROMOTION_THEME'];
        if (isset($promotions_theme[$tag])) {
            $_SESSION['seasonal_promot'] = $promotions_theme[$tag];
        }
        break;
    case 'save-seasonal-promotion-info':
        $promotions = phive('MailHandler2')->sendEmailForSeasonalPromotion();
        break;
    case 'saveCookie':
        $cookie['cookies_consent_info'] = isset($_COOKIE['cookies_consent_info']) ? $_COOKIE['cookies_consent_info'] : [];
        $cookie['cookieConsent'] = phive('BrandedConfig')->getBrand() . "_cookie_consent";
        $cookieInfo = json_encode($cookie);
        echo "var COOKIE_INFO = $cookieInfo;";
        break;
    case 'remove_currency_changed_settings':
        if (!$cur_player) die(['success' => false]);

        $cur_player->deleteSetting('currency_changed_from');
        $cur_player->deleteSetting('currency_changed_to');

        die([
            'success' => true,
        ]);
    case 'check-rg-popups':
        $response = [
            'success' => false,
            'trigger' => null,
            'error' => null,
        ];

        if (!$cur_player) {
            $response['error'] = 'user_logged_out';
            echo json_encode($response);
            return;
        }
        $popup_shown_at = phMgetShard('rg-popup-shown', $cur_player->getId());

        if (! empty($popup_shown_at)) {
            echo json_encode($response);
            return;
        }

        $rg_popup_data = lic('getQueuedRGPopupInfo', [$cur_player], $cur_player);

        if (! empty($rg_popup_data['trigger_name'])) {
            $db_user_handler_decorator = new DBUserHandlerDecorator(
                phive('UserHandler'),
                phive('BoxHandler')->getDiamondBoxBase('GmsSignupUpdateBoxBase')
            );
            $popup_data = $db_user_handler_decorator->getRgEvaluationPopup($rg_popup_data['trigger_name']);
            $response = [
                'success'  => true,
                'trigger'  => $rg_popup_data['trigger_name'],
                'data' => [
                    'header' => GetRgEvaluationPopupFormatter::formatHeaderData($popup_data->getHeaderData()),
                    'description' => GetRgEvaluationPopupFormatter::formatDescriptionData($popup_data->getDescriptionData()),
                    'actions' => GetRgEvaluationPopupFormatter::formatActionsData($popup_data->getActionsData()),
                ]
            ];
            phive('UserHandler')->logAction(
                $cur_player,
                "{$rg_popup_data['trigger_name']} flag was selected to be to shown to the customer",
                'rg-evaluation'
            );
        }
        echo json_encode($response);
        break;
    case 'rg-popup-shown':
        $response = ['success' => false];

        if (!$cur_player) {
            echo json_encode($response);
            return;
        }

        if (!empty($_POST['trigger'])) {
            if ($cur_player->rgPopupShown((string)$_POST['trigger'])) {
                $response = ['success' => true];
            }
        }

        echo json_encode($response);
        break;
    case "rg-popup-action":
        $response = ['success' => false];
        $event = $_POST['event'] ?? null;
        $trigger = $_POST['trigger'] ?? null;
        $allowed_events = ['take-a-break', 'edit-limits', 'continue'];
        $allowed_triggers = lic('getLicSetting', ['rg-trigger-popups'], $cur_player);

        if (!$cur_player || !in_array($event, $allowed_events, true) || !in_array($trigger, $allowed_triggers, true)) {
            echo json_encode($response);
            return;
        }

        if (!empty($event) && !empty($trigger)) {
            $response = ['success' => true];
            phive('UserHandler')->logAction($cur_player,
                "Customer clicked '{$event}' for {$trigger} popup.",
                'automatic-flags'
            );
        }

        echo json_encode($response);
        break;
    case 'get-occupation-list':
        $response = ['success' => false];
        $industry = $_REQUEST['industry'] ?? null;
        if(!empty($industry)) {
            $occupations = lic('getOccupations', [$industry, $cur_player]);
            echo json_encode([
                'success' => true, 'occupation' => $occupations
            ]);
            return;
        }

        echo json_encode($response);
        break;
    case "check-intensive-gambler-popup":
        $response = ['success' => false];

        if (!$cur_player) {
            $response['error'] = 'user_logged_out';
            echo json_encode($response);
            return;
        }

        if (
            !lic("isIntensiveGambler", [$cur_player], $cur_player) ||
            $cur_player->hasSetting('intensive_gambler_warning_accepted')
        ) {
            echo json_encode($response);
            return;
        }

        $popup_shown_at_key = 'intensive_gambler_warning_shown';
        $popup_shown_at = phMgetShard($popup_shown_at_key, $cur_player->getId());

        if (!$popup_shown_at) {
            phMsetShard($popup_shown_at_key, phive()->hisNow(), $cur_player->getId());

            if (!$cur_player->hasSetting($popup_shown_at_key)) {
                $cur_player->setSetting($popup_shown_at_key, phive()->hisNow());
            }
            echo json_encode(['success' => true]);
            return;
        }

        if (Carbon::parse($popup_shown_at)->diffInHours(Carbon::now()) >= 1) {
            phMsetShard($popup_shown_at_key, phive()->hisNow(), $cur_player->getId());
            echo json_encode(['success' => true]);
            return;
        }


        echo json_encode($response);
        break;
    case "rg-tools-answer":
        if (!$cur_player) {
            $response['success'] = false;
            $response['error'] = 'user_logged_out';
            echo json_encode($response);
            return;
        }

        lic('handleRgToolsAnswer', [$cur_player, $_REQUEST['answer']], $cur_player);

        echo json_encode(['success' => true]);

        break;
    case 'forfeit-bonuses-to-deposit':
        if (!($cur_player instanceof DBUser)) return json_encode(['success' => true]);

        $service = new TrophyBonusService();
        $data = $service->forfeitBonusesToDeposit();

        if (is_string($data)) {
            return json_encode(['error' => t($data)]);
        }

        return json_encode(['success' => true]);
    case 'log_error':
        if (empty($cur_player)) {
            echo json_encode(['success' => false, 'error' => 'No user']);
            return;
        }

        phive()->dumpTbl($_POST['tag'], $_POST['data'], $cur_player);
        echo json_encode(['success' => true]);
        return;

    case 'get-fallback-play-url':
        $response = ['success' => false];

        if (($cur_player instanceof DBUser)) {
            $entry = phive('Tournament')->getPossibleEntry($_REQUEST['game_id'] ?? 0, $cur_player);

            if (!empty($entry)) {
                $response = ['success' => true, 'url' => phive('Tournament')->getPlayUrl($entry)];
            }
        }

        echo json_encode($response);
        return;
    case 'create-ws-for-split-game':
        if(empty($_POST['ext_game_name'])){
            phive('Logger')->getLogger('web_sockets')->error('Error creating new ws for split game, ext_game_name was empty');
            exit;
        }

        echo phive('UserHandler')->wsUrl('lgalimitmsg'. $_POST['ext_game_name']);
        break;
}

/**
 * If the admin is watching the user profile and click on an action we simulate the action as being done by the current player
 * This will work only if an extra "userId" parameter is passed in the params and the action is in the "whitelisted_actions"
 *
 * Ex. admin going to weekend booster page and releasing the "booster funds" for the player.
 *
 * @return mixed|null
 */
function getCurUserIdOnAdminAction() {
    $whitelisted_actions = ['update-booster-preference', 'add-booster-to-credit'];

    if(!in_array($_REQUEST['action'], $whitelisted_actions) || !privileged()) {
        return null;
    }

    return $_POST['userId'] ?? null;
}
