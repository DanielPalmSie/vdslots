<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;

    $logger = phive('Logger')->getLogger('cron');

    $logger->info('diamondbet/soap/every5min.php started');

    $logger->info('clearOutDateSetting started');
    $uh = phive("UserHandler");
    $uh->clearOutDateSetting('change-cinfo-unlock-date');
    $uh->clearOutDateSetting('change-address-unlock-date');
    $logger->info('clearOutDateSetting finished');

    if(phive()->getSetting('lga_reality') === true){

        $logger->info('rgLimits::changeCron started');
        rgLimits()->changeCron();
        $logger->info('rgLimits::changeCron finished');

        $logger->info('rgLimits::resetCron started');
        rgLimits()->resetCron();
        $logger->info('rgLimits::resetCron finished');

        $logger->info('rgLimits::resetForcedLockCron started');
        rgLimits()->resetForcedLockCron();
        $logger->info('rgLimits::resetForcedLockCron finished');
    }

    $logger->info('unlockLocked started');
    phive("UserHandler")->unlockLocked();
    $logger->info('unlockLocked finished');

    $logger->info('onEvery5Min started');
    lics('onEvery5Min');
    $logger->info('onEvery5Min finished');

    if (!phive()->getSetting('has_dedicated_reporting_cron', false)) {
        $logger->info('onEvery5MinReporting started');
        lics('onEvery5MinReporting');
        $logger->info('onEvery5MinReporting finished');
    }

    $logger->info('republishFailedMessages started');
    phive('History')->republishFailedMessages();
    $logger->info('republishFailedMessages finished');

    $logger->info('reduceUsersLosLimitToNDL started');
    // Temporary cron to set loss limit for GB users
    reduceUsersLossLimitToNDL();
    $logger->info('reduceUsersLosLimitToNDL finished');

    $logger->info('syncNdtResetsWithLossResetsStamp started');
    // Temporary cron to sync Loss and NDT limit for GB users.
    syncNdtResetsWithLossResetsStamp();
    $logger->info('syncNdtResetsWithLossResetsStamp finished');

    $logger->info('syncNDLResetsAtWithNDTResetsStamp started');
    // Temporary cron to sync NDL and NDT limit for GB users.
    syncNDLResetsAtWithNDTResetsStamp();
    $logger->info('syncNDLResetsAtWithNDTResetsStamp finished');
    
    $logger->info('diamondbet/soap/every5min.php finished');

    
}

/**
 * Sets loss limit to NDL, if loss limit > NDL
 */
function reduceUsersLossLimitToNDL()
{
    $config = phive('SQL')->lb()->loadAssoc(
        phive('Config')->getSelect()."config_name = 'responsible-gambling' AND config_tag = 'loss-limits'"
    );
    $config_value = phive('Config')->getValueFromTemplate($config);
    $jurisdictions = array_keys(array_filter($config_value, function($config_value){
        return strcasecmp($config_value, 'NDL') === 0;
    }));
    $jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
    $country_jurisdiction = array_intersect($jurisdiction_map, $jurisdictions);

    foreach($country_jurisdiction as $country => $jurisdiction){
        if ($jurisdiction === 'AGCO') {
            $user_country = strstr($country, "-", true);
            $province = substr(strrchr($country, "-"), 1);
            $limits = Phive("SQL")->shs()->loadArray("
                SELECT
                    u.id                as user_id,
                    rg_loss.id          as loss_limit_id,
                    rg_loss.cur_lim     as current_loss_limit,
                    rg_loss.time_span   as type_of_limit,
                    rg_netDep.cur_lim   as current_net_dep_limit
                FROM
                    users as u
                JOIN rg_limits as rg_netDep
                    ON rg_netDep.user_id = u.id AND rg_netDep.type = 'net_deposit'
                JOIN rg_limits as rg_loss
                    ON rg_loss.user_id = u.id AND rg_loss.type = 'loss'
                JOIN users_settings as us
                    ON us.user_id = u.id AND us.setting = 'main_province' AND us.value = '{$province}'
                WHERE
                    rg_loss.cur_lim > rg_netDep.cur_lim
                    AND u.country = '{$user_country}'
                    AND rg_netDep.cur_lim > 0;
            ");
        } else if ($jurisdiction === 'MGA') {
            $except_countries = array_flip(array_filter($jurisdiction_map, function($k){
                return !in_array($k, ['CA-ON', 'default']);
            }, ARRAY_FILTER_USE_KEY));
            $countries = phive('SQL')->makeIn($except_countries);
            $bank_countries = phive('SQL')
                ->loadKeyValues("SELECT iso FROM bank_countries WHERE iso NOT IN($countries)", 'iso', 'iso');
            $countries_list = phive('SQL')->makeIn($bank_countries);
            $limits = phive("SQL")->shs()->loadArray("
                SELECT
                    u.id                as user_id,
                    rg_loss.id          as loss_limit_id,
                    rg_loss.cur_lim     as current_loss_limit,
                    rg_loss.time_span   as type_of_limit,
                    rg_netDep.cur_lim   as current_net_dep_limit
                FROM
                    users as u
                JOIN rg_limits as rg_netDep
                    ON rg_netDep.user_id = u.id AND rg_netDep.type = 'net_deposit'
                JOIN rg_limits as rg_loss
                    ON rg_loss.user_id = u.id AND rg_loss.type = 'loss'
                LEFT JOIN users_settings as us
                    ON us.user_id = u.id AND us.setting = 'main_province'
                WHERE
                    rg_loss.cur_lim > rg_netDep.cur_lim
                    AND u.country IN ({$countries_list})
                    AND (us.value IS NULL OR us.value != 'ON')
                    AND rg_netDep.cur_lim > 0;
            ");
        } else {
            $limits = phive("SQL")->shs()->loadArray("
                SELECT
                    u.id                as user_id,
                    rg_loss.id          as loss_limit_id,
                    rg_loss.cur_lim     as current_loss_limit,
                    rg_loss.time_span   as type_of_limit,
                    rg_netDep.cur_lim   as current_net_dep_limit
                FROM
                    users as u
                JOIN rg_limits as rg_netDep
                    ON rg_netDep.user_id = u.id AND rg_netDep.type = 'net_deposit'
                JOIN rg_limits as rg_loss
                    ON rg_loss.user_id = u.id AND rg_loss.type = 'loss'
                WHERE
                    rg_loss.cur_lim > rg_netDep.cur_lim
                    AND u.country = '{$country}'
                    AND rg_netDep.cur_lim > 0;
            ");
        }

        if (empty($limits)) {
            continue;
        }

        foreach ($limits as $limit) {
            $user_id = (int)$limit['user_id'];

            phive("SQL")->sh($user_id)->query("
                UPDATE rg_limits SET cur_lim = {$limit['current_net_dep_limit']}
                WHERE user_id = {$limit['user_id']} AND id = {$limit['loss_limit_id']}
            ");

            $message = "Temporary cron job updated Loss Limit {$limit['type_of_limit']} from: {$limit['current_loss_limit']} to {$limit['current_net_dep_limit']}";
            phive('UserHandler')->logAction($limit['user_id'], $message, 'comment');
        }
    }
}

function syncNdtResetsWithLossResetsStamp()
{
    $sql = Phive("SQL");
    $jurisdictions = phive('Config')
        ->valAsArray('RG', 'rg-limits-fixed-cycle-reset-timestamp-in-jurisdictions', ',');
    $jurisdictions_where = $sql->makeIn($jurisdictions);
    // First get users that need updates
    $usersToUpdate = $sql->shs()->loadArray("SELECT DISTINCT u.id as user_id, LOSS.resets_at as resets_at
        FROM rg_limits AS NDL
        JOIN users AS u ON NDL.user_id = u.id
        JOIN rg_limits AS LOSS ON u.id = LOSS.user_id
            AND LOSS.type = 'loss'
            AND LOSS.time_span = 'month'
        LEFT JOIN users_settings AS us ON NDL.user_id = us.user_id AND us.setting = 'jurisdiction'
        WHERE u.country = 'GB'
          AND us.value NOT IN ({$jurisdictions_where})
          AND NDL.type = 'net_deposit'
          AND NDL.time_span = 'month'
          AND (NDL.resets_at <> LOSS.resets_at)");

    if (empty($usersToUpdate)) {
        return;
    }

    $updatedCount = 0;

    foreach ($usersToUpdate as $user) {
        $resets_at = date('Y-m-d H:i:s', strtotime($user['resets_at']));
        $result = $sql->sh($user['user_id'])->query("
            UPDATE rg_limits
            SET resets_at = TIMESTAMP('$resets_at')
            WHERE user_id = {$user['user_id']}
              AND type = 'net_deposit'
              AND time_span = 'month'");

        if ($result !== false) {
            $message = "NDL resets_at time successfully updated to LOSS resets_at time";
            phive('UserHandler')->logAction($user['user_id'], $message, 'comment');
            $updatedCount++;
        }
    }
}

function syncNDLResetsAtWithNDTResetsStamp()
{
    $sql = Phive("SQL");
    $jurisdictions = phive('Config')
        ->valAsArray('RG', 'rg-limits-fixed-cycle-reset-timestamp-in-jurisdictions', ',');
    $jurisdictions_where = $sql->makeIn($jurisdictions);
    // First get users that need updates
    $usersToUpdate = $sql->shs()->loadArray("SELECT DISTINCT u.id as user_id, NDT.resets_at as resets_at
    FROM rg_limits AS NDL
    JOIN users AS u ON NDL.user_id = u.id
    JOIN rg_limits AS NDT ON u.id = NDT.user_id
        AND NDT.type = 'net_deposit'
        AND NDT.time_span = 'month'
    LEFT JOIN users_settings AS us ON NDT.user_id = us.user_id AND us.setting = 'jurisdiction'
    WHERE u.country = 'GB'
      AND us.value NOT IN ({$jurisdictions_where})
      AND NDL.type = 'customer_net_deposit'
      AND NDL.time_span = 'month'
      AND (NDL.resets_at <> NDT.resets_at)");

    if (empty($usersToUpdate)) {
        return;
    }

    $updatedCount = 0;

    foreach ($usersToUpdate as $user) {
        $resets_at = date('Y-m-d H:i:s', strtotime($user['resets_at']));
        $result = $sql->sh($user['user_id'])->query("
        UPDATE rg_limits
        SET resets_at = TIMESTAMP('$resets_at')
        WHERE user_id = {$user['user_id']}
          AND type = 'customer_net_deposit'
          AND time_span = 'month'");

        if ($result !== false) {
            $message = "NDL resets_at time successfully updated to NDT resets_at time";
            phive('UserHandler')->logAction($user['user_id'], $message, 'comment');
            $updatedCount++;
        }
    }
}