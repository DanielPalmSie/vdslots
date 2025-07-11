<?php
require_once __DIR__ . '/../../phive.php';
$loc = phive('Localizer');
phive('UserHandler')->login();
require_once __DIR__ . '/../../../diamondbet/html/display.php';
phive('Localizer')->setLanguage($_REQUEST['lang'], true);

function dieJson($msg, $result = 'ok'){
    die(json_encode(['result' => $result, 'msg' => $msg]));
}

/**
 * Wrapper on RgLimitsActions->getReturnMsg
 * needed to keep compatibility with phive response array format/translations
 *
 * @param $res
 * @param null $action_type
 * @return array
 */
function getChangeMsg($res, $action_type = null){
    list($res, $msg) = phive('DBUserHandler/RgLimitsActions')->getReturnMsg($res, $action_type);
    $res = !empty($res) ? 'ok' : 'nok';
    $msg = $msg === 'user.not.logged.in' ? 'no user' : t2($msg, ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);
    return [$res, $msg];
}

$uh    = phive('UserHandler');
$u_obj = cuPl();
if(empty($u_obj)){
    die('no user');
}

$c      = phive('Cashier');
$rg     = rgLimits();
$pdata  = json_decode($_POST['data'], true);
$action = $_POST['action'];
// other POST params not included in the json decoded data in $pdata.
$extra_data = [
    'indefinitely' => $_POST['indefinitely'] === 'yes',
    'num_days' => $_POST['num_days'],
    'num_hours' => $_POST['num_hours'],
    'extra' => $_POST['extra'],
    'opted_in' => $_POST['opted_in'],
    'rg_duration' => $_POST['rg_duration'],
    'range' => $_POST['range'],
    'answer' => $_POST['answer'],
];

if($action == 'save_resettable'){
    $resettable_limits = $rg->getByTypeUser($u_obj, $pdata['type']);
    $action            = empty($resettable_limits) ? 'add_resettable' : 'change_resettable';
}
// TODO - ugly fix to have cross-brand-limit-xxx available on a global, instead of passing around the full data object on multiple functions.
//  there is a special case for "save_resettables" as data structure is different, so i'm highjacking it there too.
//  need to be reworked /Paolo
if($action !== 'save_resettables') {
    $_POST["cross-brand-limit-{$pdata['type']}"] = $pdata["cross-brand-limit-{$pdata['type']}"];
}

// When the user changes their max bet limit, show a dedicated message to that scenario
$dynamicAlias = $action == "save_betmax" ? "rglimits.change.success.betmax" : "rglimits.added.successfully";
$msg = t2($dynamicAlias, ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);
$res = 'ok';
$new_action = lic('overrideRgAction', [$action, $_POST], $u_obj);
$action = $new_action ? $new_action : $action;
switch($action){
    case 'remove':
        list($message, $res) = phive('DBUserHandler/RgLimitsActions')->removeAction($pdata['type']);

        dieJson($message, $res);
        break;

    case 'save_resettables':
        // TODO - ugly fix to have cross-brand-limit-xxx available on a global for RG popups
        //  this function has 1 extra nesting level compared to RG page,  need to be reworked /Paolo
        foreach($pdata['crossBrandFlags'] as $type => $value) {
            $_POST["cross-brand-limit-{$type}"] = $value;
        }
        foreach($pdata['limits'] as $type => $limits){
            $resettable_limits = $rg->getByTypeUser($u_obj, $type);
            list($tmp_res, $tmp_msg) = empty($resettable_limits) ? phive('DBUserHandler/RgLimitsActions')->addResettable($u_obj, $type, $limits) : phive('DBUserHandler/RgLimitsActions')->changeResettable($u_obj, $limits, $resettable_limits);
            if($tmp_res === 'nok'){
                $msg = $tmp_msg;
                $res = $tmp_res;
            }
        }

        if ($pdata['type'] === $rg::TYPE_DEPOSIT) {
            $rg->logCurrentLimit($u_obj);
        }

        dieJson($msg, $res);
        break;

    // TODO check if this can be removed/reworked into addResettable(), not sure why we have 2 different ways of doing it /Paolo
    case 'add_resettable':
        list($msg, $res) = phive('DBUserHandler/RgLimitsActions')->addResettableAction($pdata['type'], $pdata['limits']);
        if($res == 'nok') {
            dieJson($msg, $res);
        }
        break;

    case 'change_resettable':
        $result = phive('DBUserHandler/RgLimitsActions')->changeResettableAction($pdata['type'], $pdata['limits'], $resettable_limits);
        dieJson(...$result);
        break;

    case 'save_betmax':
        list($msg, $res) = phive('DBUserHandler/RgLimitsActions')->updateBetMaxAction($pdata['time_span'], $pdata['limit'], true);
        dieJson($msg, $res);
        break;

    case 'save_balance':
    case 'save_timeout':
    case 'save_rc':
        list($messages, $res) = phive('DBUserHandler/RgLimitsActions')->updateSingleAction($pdata['type'], $pdata['limit'], true);
        dieJson($messages[0], $res);
        break;
    case 'lock-games-categories':
        list($msg, $res) = phive('DBUserHandler/RgLimitsActions')->gameBreak24Action($_POST['extra'], true);
        dieJson($msg, $res);
        break;

    case 'lock-unlock-games-categories-indefinite':
        /* Adding to checked and unchecked categories missed categories from the MENU */
        $checked_categories   = $u_obj->expandLockedGamesCategories($_POST['checked'] ?? []);
        $unchecked_categories = $u_obj->expandLockedGamesCategories($_POST['unchecked'] ?? []);

        /* Get locked categories and their time period of blocking from DB */
        $locked_categories_and_period = $u_obj->getRgLockedGamesAndPeriod();

        /* If we don't have any saved categories in DB, we saving these to DB regarding to 'indefinite' block time period
            here is $num_days = 36500 days to be like `indefinite` (period == 0) */
        if (empty($locked_categories_and_period)) {
            $num_days = 36500;
            foreach ($checked_categories as $checked_category) {
                /* 'all_categories' not category itself, just for UI */
                if ($checked_category === 'all_categories') {
                    continue;
                }
                $rg->addLimit($u_obj, 'lockgamescat', 'na', $num_days, $checked_category, true);
            }
        /* Otherwise if we have already saved categories in DB, we should recheck/compare them
            to save with correct period of blocking time (`cooloff` or indefinite) */
        } else {
            $u_obj->savingCategoriesRegardingToTimePeriod($locked_categories_and_period, $checked_categories, $unchecked_categories);
        }
        break;
    case 'undo-withdrawals-opt-in-out':
        $msg = $rg->saveLimit($u_obj, 'undo_withdrawal_optout', 'na', empty($_POST['opted_in']));
        break;
    case 'exclude':
        $msg = phive('DBUserHandler/RgLimitsActions')->excludeAction($_POST['rg_duration'], true);
        dieJson($msg);
        break;
    case 'exclude-permanent':
        $msg = phive('DBUserHandler/RgLimitsActions')->excludePermanentlyAction(true);
        dieJson($msg);
        break;
    case 'intended_gambling':
        $msg = '';
        if (empty($range = $_POST['range'])) {
            $msg = t('intended_gambling.form.error');
        } else {
            $u_obj->setSetting('intended_gambling', $range);
        }
        break;
    case 'exclude-indefinite':
        $msg = phive('DBUserHandler/RgLimitsActions')->excludeIndefiniteAction(true);
        dieJson(t($msg));
        break;
    /**
     * Handle manual RG checkup action requested from the BO.
     *
     * We log what the user click (Yes | No)
     * If user click "Yes" then we trigger the second popup (Enforce user to set a limit | Logout the user and lock him for 24h)
     */
    case 'ask_gamble_too_much':
    case 'ask_bet_too_high':
    case 'ask_play_too_long':
        $u_obj->deleteSetting($action);
        $answer_yes = $_POST['answer'] === 'yes'; // values can be "yes | no"
        $next_action = [];
        if($answer_yes) {
            // action name of the next popup that needs to be displayed (Action will be matching the file name under "rg_popups/")
            $map_next_action = [
                'ask_gamble_too_much' => ['action' => 'gamble_too_much_lockout', 'type' => 'logout_popup', 'add_setting' => false],
                'ask_bet_too_high' => ['action' => 'force_max_bet_protection', 'type' => 'set_limit_popup', 'add_setting' => true],
                'ask_play_too_long' => ['action' => 'force_login_limit', 'type' => 'set_limit_popup', 'add_setting' => true],
            ];
            $next_action = $map_next_action[$action];

            // some actions require to add a new setting to the user
            // For now we only have "FLAG" settings (Value = 1), if we need to set a different value we can add an extra key (Ex. "setting_value") to $map_next_action
            if($next_action['add_setting']) {
                $setting_value = $next_action['setting_value'] ?? 1;
                $u_obj->setSetting($next_action['action'], $setting_value);
            }

            // We need to logout the player + exclude for 24h
            if($next_action['action'] === 'gamble_too_much_lockout') {
                $rg->addLimit($u_obj, 'lock', 'na', 1);
                // giving few seconds before logging out the player to have the time to display the info popup
                phive()->pexec('DBUserHandler', 'logoutUser', [$u_obj->getId()], 5000000);
            }
        }
        $map_action_log_msg = [
            'ask_gamble_too_much' => 'Do you gamble too much?',
            'ask_bet_too_high' => 'Do you bet too high?',
            'ask_play_too_long' => 'Do you play too long?',
        ];
        $answer_msg = $answer_yes ? 'Yes' : 'No';
        $msg = "User answered '{$answer_msg}' to '{$map_action_log_msg[$action]}'";
        // extra note on logged action
        $msg .= $action === 'gamble_too_much' ? ' - a 24 hour lock is now in place' : '';

        $uh->logAction($u_obj, $msg, $action);

        // We don't need to fire "onSetLimit" when logging the "ask_xxx" cause no real change of the limits is done
        die(json_encode([
            'next_action'=>$next_action['action'],
            'action_type' => $next_action['type'],
        ]));
        break;
    case 'save_province':
        header('Content-Type: application/json; charset=utf-8');

        $user = cu();

        if(empty($user)) {
            die(json_encode([
                'success' => false,
                'msg' => t('no.user')
            ]));
        }

        $province = $_POST['province'];
        $provinces = lic('getProvinces', [], $user);

        if(empty($province) || !isset($provinces[$province])) {
            die(json_encode([
                'success' => false,
                'msg' => t('province.error.description')
            ]));
        }

        $user->setSetting('main_province', $province);
        unset($_SESSION['show_add_province_popup']);

        die(json_encode([
            'success' => true,
            'msg' => t('province.saved.success')
        ]));
    case 'save_nationality_pob':
        header('Content-Type: application/json; charset=utf-8');

        $user = cu();

        if(empty($user)) {
            die(json_encode([
                'success' => false,
                'msg' => t('no.user')
            ]));
        }

        $nationality = $_POST['nationality'];
        $nationalities = lic('getNationalities', [], $user);

        if(empty($nationality) || !isset($nationalities[$nationality])) {
            die(json_encode([
                'success' => false,
                'msg' => t('nationality.error.description')
            ]));
        }

        $place_of_birth = $_POST['pob'];

        if(empty($place_of_birth)) {
            die(json_encode([
                'success' => false,
                'msg' => t('place.of.birth.error.required')
            ]));
        }

        $user->setSetting('nationality', $nationality);
        $user->setSetting('place_of_birth', $place_of_birth);

        $user->deleteSetting('nationality_birth_country_required');
        unset($_SESSION['show_add_nationalityandpob_popup']);

        die(json_encode([
            'success' => true,
            'msg' => t('nationalityandpob.saved.success')
        ]));
    case 'save_nationality':
        header('Content-Type: application/json; charset=utf-8');

        $user = cu();

        if(empty($user)) {
            die(json_encode([
                'success' => false,
                'msg' => t('no.user')
            ]));
        }

        $nationality = $_POST['nationality'];
        $nationalities = lic('getNationalities', [], $user);

        if(empty($nationality) || !isset($nationalities[$nationality])) {
            die(json_encode([
                'success' => false,
                'msg' => t('nationality.error.description')
            ]));
        }

        $user->setSetting('nationality', $nationality);

        $user->deleteSetting('nationality_required');
        $user->deleteSetting('nationality_update_required');
        unset($_SESSION['show_add_nationality_popup']);

        die(json_encode([
            'success' => true,
            'msg' => t('nationality.saved.success')
        ]));
    case 'save_company_info':
        header('Content-Type: application/json; charset=utf-8');

        $user = cu();

        if(empty($user)) {
            die(json_encode([
                'success' => false,
                'msg' => t('no.user')
            ]));
        }

        $nationalities = lic('getNationalities');

        $citizenship = $_POST['citizenship'];
        $company_name = $_POST['company_name'];
        $company_address = $_POST['company_address'];
        $company_phone_number = $_POST['company_phone_number'];

        if(
            (empty($citizenship) || !isset($nationalities[$citizenship]))
            || empty($company_name) || empty($company_address) || empty($company_phone_number)
        ) {
            die(json_encode([
                'success' => false,
                'msg' => t('company-details-popup.error.invalid-data')
            ]));
        }

        $user->setSetting('citizenship', $citizenship);
        $user->setSetting('company_name', $company_name);
        $user->setSetting('company_address', $company_address);
        $user->setSetting('company_phone_number', $company_phone_number);

        die(json_encode([
            'success' => true,
        ]));
    case 'set_company_details_popup_shown_flag':
        header('Content-Type: application/json; charset=utf-8');

        $user = cu();

        if(empty($user)) {
            die(json_encode([
                'success' => false,
                'msg' => t('no.user')
            ]));
        }

        $user->setSetting('company_details_popup_shown', 1);

        die(json_encode([
            'success' => true,
        ]));
    case 'asknolimits':
        unset($_SESSION['show_add_limits_popup']);

        die(json_encode([
            'success' => true,
            'msg' => true
        ]));
    case 'idscancontinue':
        unset($_SESSION['show_successful_idscan_verification']);

        die(json_encode([
            'success' => true,
            'msg' => true
        ]));

    default:
        list($res, $msg) = phive('DBUserHandler/RgLimitsActions')->execute($action, $pdata, $extra_data);
        dieJson($msg);
}

phive('Cashier/Arf')->invoke('onSetLimit', $u_obj);

dieJson($msg);
