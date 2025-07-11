<?php

require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/../../phive/vendor/autoload.php';
require_once(__DIR__ . '/../../phive/modules/Cashier/Fraud.php');

if(isCli()){

    $GLOBALS['is_cron'] = true;

    phive("Bonuses")->failByFailLimit();

    $sql 	= phive('SQL');

    $date 	= date('Y-m-d', strtotime('-1 day'));

    error_log("NO1". date('Y-m-d H:i:s'));

    if($sql->isSharded('cash_transactions')){
        $sql->loopShardsSynced(function($db, $shard, $id) use($date){
            phive('Cashier')->resetFreeMoney($db, phive()->modDate($date, '-15 day') );
        });
    }else
        phive('Cashier')->resetFreeMoney(false, phive()->modDate($date, '-15 day') );

    error_log("NO2". date('Y-m-d H:i:s'));
    $sdate 	= $date.' 00:00:00';
    $edate 	= $date.' 23:59:59';
    phive('Casino')->makeBetWinTmp($sdate, $edate);

    error_log("NO3". date('Y-m-d H:i:s'));

    if($sql->isSharded('users_daily_stats_mp')){
        $sql->loopShardsSynced(function($db, $shard, $id) use($date){
            phive('Tournament')->calcDailyStats($date, $db);
        });
        phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_mp', $date);
    }else
        phive('Tournament')->calcDailyStats($date);

    error_log("NO4". date('Y-m-d H:i:s'));

    if($sql->isSharded('users_daily_stats')){
        $sql->loopShardsSynced(function($db, $shard, $id) use($sdate, $edate, $date){
            phive('Cashier')->calcUserCache($sdate, $edate, $db);
        });
        phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats', $date);
    }else
        phive('Cashier')->calcUserCache($sdate, $edate);

    error_log("NO5". date('Y-m-d H:i:s'));
    //phive('Cashier')->calcUserSessions($sdate, $edate);

    $stamp 	= date('d') == '01' ? strtotime('-1 month') : time();
    //$stamp	= time();

    $sday 	= date('Y-m', $stamp).'-01';
    $eday 	= date('Y-m-t', $stamp);

    if($sql->isSharded('users_daily_game_stats')){
        $sql->loopShardsSynced(function($db, $shard, $id) use($date){
            //phive('MicroGames')->calcGameUserStats($date, $db);
            phive('MicroGames')->fixNetwork($date, $db);
        });
        phive('UserHandler')->aggregateUserStatsTbl('users_daily_game_stats', $date);
    }else{
        phive('MicroGames')->fixNetwork($date);
    }

    //phive('MicroGames')->calcGameUserStats($date);
    error_log("NO6". date('Y-m-d H:i:s'));

    phive('MicroGames')->calcNetworkStats($date);

    error_log("NO7". date('Y-m-d H:i:s'));

    //phive('MicroGames')->calcCasinoRaceStats();

    $today = date('Y-m-d');

    //phive("Cashier")->calcNumInOutBusts($date);

    phive("MicroGames")->calcMonthGameStats(date('Y-m', $stamp), $sday, $eday);

    error_log("NO8". date('Y-m-d H:i:s'));
    //phive('UserHandler')->mailNonDepositors(3);

    if(phive()->isMonday($today)){
        list($sday, $eday) = phive()->getPreviousWeekStartEnd($today);
        if (phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_MEGARICHES) {
            $logger = phive('Logger')->getLogger('cron');
            $logger->info(sprintf('Monday cron started: start-date: %s, end-date: %s, server-datetime-now: %s', $sday, $eday, date('Y-m-d H:i:s')));
        }
        phive('Cashier')->qLoyalty($sday, $eday);
        lics('onMondayMidnight');
        phive('Cashier/Rg')->customerIsTopLoser();
        phive('Cashier/Aml')->customerIsTopDepositor();
    }

    //phive("Bonuses")->blockUnblockedBonusWhores();

    //phive('Bonuses')->bonusStatsCron($date);

    error_log("NO11". date('Y-m-d H:i:s'));


    phive('QuickFire')->bigWinners('balance', 30, $date);
    phive('QuickFire')->bigWinners('amount', 30, $date);

    error_log("NO12". date('Y-m-d H:i:s'));

    if (phive()->moduleExists('Netent')) {
        phive('Netent')->calcBalances($date);
    }

    error_log("NO13". date('Y-m-d H:i:s'));

    phive('UserHandler')->clearOldNotifications();

    error_log("NO14". date('Y-m-d H:i:s'));

    if($sql->isSharded('users_lifetime_stats')){
        $sql->loopShardsSynced(function($db, $shard, $id) use($date){
            phive('Cashier')->cacheLifetimeStats($db);
        });
        // We truncate the master before we aggregate
        phive('SQL')->query("TRUNCATE users_lifetime_stats");
        phive('UserHandler')->aggregateUserStatsTbl('users_lifetime_stats', '0000-00-00');
    }else
        phive('Cashier')->cacheLifetimeStats();

    error_log("NO15". date('Y-m-d H:i:s'));

    phive('Cashier')->ccardFraudCron();
    phive('UserHandler')->unexcludeCron();

    error_log("NO16". date('Y-m-d H:i:s'));

    lics('onEveryday', [$date]);

    if (!phive()->getSetting('has_dedicated_reporting_cron', false)) {
        lics('onEverydayReporting', [$date]);
    }

    error_log("NO17". date('Y-m-d H:i:s'));

    phive('Cashier')->clearUsersFifoDates();

    error_log("NO18". date('Y-m-d H:i:s'));

    error_log("NO19". date('Y-m-d H:i:s'));

    phive('UserHandler')->updatePermanentExclusion();

    error_log("NO20". date('Y-m-d H:i:s'));

    phive('Licensed')->checkPepAlertsCommon();

    phive('Cashier/Fr')->pepYearlyONCheck();

    phive('Cashier/Fr')->pepWorldpayCheck();

    error_log("NO21". date('Y-m-d H:i:s'));

   //update risk_profile_rating_log table
    if($sql->isSharded('risk_profile_rating_log')) {
        $sql->loopShardsSynced(function ($db, $shard, $id) use ($date) {
            phive('Cashier/Arf')->riskProfileRatingLogDailyCron($date, $db);
        });
    }

    lics('closeUserAccountCron');

    error_log("NO22". date('Y-m-d H:i:s'));

    lics('reportSoftwareVersionCron');

    error_log("NO23". date('Y-m-d H:i:s'));

    linker()->bulkBrandLink($date);

    error_log("NO24". date('Y-m-d H:i:s'));

    phive('MicroGames')->recalcGameUserStats($date);
    phive('Cashier')->closedLoopStartStampCron();
    phive('Licensed')->doLicense("IT", 'finishITOrphanGameSession', []);
    phive('Licensed')->expireAndUnVerify();
    lics('resetModifiedNetDepositLimitCron');
    phive('GamesRecommendations')->collectDailyDataCron();

    error_log("NO25". date('Y-m-d H:i:s'));
    try {
        phive('Cashier/Rg')->topXUsersTimeSpentOnSite();
    } catch (Exception $e) {
        error_log("topXUsersTimeSpentOnSite Failed. Message " . $e->getMessage());
    }

    try {
        phive('Cashier/Rg')->topXDepositorsRegisteredInLastYMonths();
    } catch (Exception $e) {
        error_log("topXDepositorsRegisteredInLastYMonths Failed. Message " . $e->getMessage());
    }

    try {
        phive('Cashier/Rg')->topXLosingCustomersInYMonths();
    }catch (\Exception $e){
        error_log(__METHOD__ . " Failed: {$e->getMessage()}");
    }

    try {
        phive('Cashier/Rg')->topXLosingYoungCustomersInYMonths();
    }catch (\Exception $e){
        error_log(__METHOD__ . " Failed: {$e->getMessage()}");
    }

    try {
        phive('Cashier/Rg')->topXWinningCustomersRegisteredInLastYMonths();
    } catch (\Exception $e){
        error_log("topXWinningCustomersInLastYMonths Failed: {$e->getMessage()}");
    }

    error_log("Cron: everyday finished ". date('Y-m-d H:i:s'));
}
