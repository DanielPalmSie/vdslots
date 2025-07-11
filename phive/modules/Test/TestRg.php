<?php

use Carbon\Carbon;

class TestRg extends TestPhive{

    function __construct(){
        $this->db = phive('SQL');
        $this->rg = phive('DBUserHandler/RgLimits');
    }

    function deleteLimits($u){
        $this->clearTable($u, 'rg_limits');
    }
    
    function addLimit($u, $type, $time_span, $limit){
        $this->rg->addLimit($u, $type, $time_span, $limit);
        $limits = $this->rg->getByTypeUser($u, $type);
        print_r($limits);
    }

    function incType($u, $type, $amount){
        $this->rg->incType($u, $type, $amount);
        $limits = $this->rg->getByTypeUser($u, $type);
        print_r($limits);
    }

    function testBet($u, $wager = 100){
        $this->deleteLimits($u);
        $this->addLimit($u, 'wager', 'month', 40000);
        $this->addLimit($u, 'wager', 'week', 10000);
        foreach(range(0, 10) as $num){
            $res = $this->rg->onBet($u, $wager);
            echo "Result #$num: " . ($res ? 'true' : 'false') . "\n"; 
        }
        $limits = $this->rg->getByTypeUser($u, 'wager');
        print_r($limits);
    }

    function testBetWin($u, $wager = 100, $win = 50, $reset = false){
        if($reset){
            $this->deleteLimits($u);
            $this->addLimit($u, 'loss', 'month', 40000);
            $this->addLimit($u, 'loss', 'week', 10000);
            $this->addLimit($u, 'wager', 'week', 10000);
        }
        
        $res = $this->rg->onBetCheck($u, $wager, true);

        if($res !== true){
            echo "Bet check failed on: \n";
            print_r($res);
        } else {
            echo "Bet check passed \n";
        }
        
        $this->rg->onBetInc($u, $wager, true);

        $this->rg->onWin($u, $win);
        
        $limits1 = $this->rg->bet_lims;
        $limits2 = $this->rg->getByTypeUser($u, ['loss', 'login']);
        print_r([$limits1, $limits2]);
    }

    function testOldLim($u_obj){
        $this->deleteLimits($u_obj);
        $this->addLimit($u_obj, 'deposit', 'day', 10000);
        $this->changeLimits($u_obj, 'deposit');
        $this->changeCron($u_obj);
        echo "Normal non-removal result:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
        $this->rg->revertToOldLimits($u_obj);
        echo "Normal after revert:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
        
        $this->deleteLimits($u_obj);
        $this->addLimit($u_obj, 'deposit', 'day', 10000);
        $this->rg->removeLimit($u_obj, 'deposit');
        $this->changeCron($u_obj);
        echo "Removal result:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
        $this->rg->revertToOldLimits($u_obj);
        echo "Removal after revert:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
        
        $this->db->sh($u_obj)->updateArray('rg_limits', ['old_lim' => 99999999999], ['user_id' => $u_obj->getId()]);
        echo "Before rejecting old limits:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');

        $this->rg->rejectOldLimits($u_obj);
        echo "After rejecting old limits:\n";
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
    }
    
    function resetCron($u){
        $this->rg->resetCron(phive()->hisMod('+2 month'));
        $limits = $this->rg->getByTypeUser($u);
        print_r($limits);
    }

    function changeLimits($u, $type, $limit = 1000000000, $time_span = 'day'){
        $this->rg->changeLimit($u, $type, $limit, $time_span);
        $limits = $this->rg->getByTypeUser($u, $type);
        print_r($limits);
    }

    function changeCron($u){
        $this->rg->changeCron(phive()->hisMod('+2 month'));
        $limits = $this->rg->getByTypeUser($u);
        print_r($limits);
    }

    function addAllNonLockLimits($u){
        $this->deleteLimits($u);
        $types = array_diff($this->rg->all_types, $this->rg->lock_types);
        foreach($types as $type){
            $limit = rand(100, 10000);
            if($this->rg->isResettable($type)){
                foreach($this->rg->time_spans as $tspan){
                    $limit = rand(100, 10000);
                    $this->rg->addLimit($u, $type, $tspan, $limit);
                }
            } else {
                $this->rg->addLimit($u, $type, 'na', $limit);
            }
        }
        $limits = $this->rg->getByTypeUser($u);
        print_r($limits);
    }

    function testMinLeftGrouped($u_obj){
        $this->rg->addPendingDeposit($u_obj, 100);
        $limits = $this->rg->getByTypeUser($u_obj);
        print_r($limits);
        $res = $this->rg->getMinLeftGrouped($u_obj);
        print_r($res);
        $this->rg->removePendingDeposit($u_obj, 100);
    }

    function testAddPendingDeposit($u_obj, $amount = 100){
        $this->rg->addPendingDeposit($u_obj, $amount);
        echo $this->rg->getExtraAmount('deposit', $u_obj)."\n";
        echo $this->rg->getMinLeftByType($u_obj, 'deposit')."\n";
    }

    function testRemovePendingDeposit($u_obj, $amount = 100){
        $this->rg->removePendingDeposit($u_obj, $amount);
        echo $this->rg->getExtraAmount('deposit', $u_obj)."\n";
        echo $this->rg->getMinLeftByType($u_obj, 'deposit')."\n";
    }

    function testResettableTimelimit($u_obj, $type, $progress = null){
        $this->rg->startProgressableTimeLimit($u_obj, $type);
        $limits = $this->rg->getByTypeUser($u_obj, $type);
        echo "After start:\n";
        print_r($limits);
        
        sleep(2);
        
        $this->rg->progressResettableTimeLimit($u_obj, $type, [], $progress, true);
        $limits = $this->rg->getByTypeUser($u_obj, $type);
        echo "After progress:\n";
        print_r($limits);
        
        $res = $this->rg->checkProgressableTimeLimit($u_obj, $type, true);
        echo "\n";
        echo $res ? 'We did not hit the limit' : 'We hit the limit';
        echo "\n";
    }
    
    function testSingleTimeLimit($u, $type = 'timeout', $progress = null, $rgl = []){
        echo "$type limit before progress:\n";
        print_r($this->rg->getSingleLimit($u, $type));

        $this->rg->progressTimeLimit($u, $type, $rgl, $progress);

        foreach(range(1, 5) as $num){
            $this->rg->progressTimeLimit($u, $type, $rgl, $progress);
            echo "$type limit after progress $num:\n";
            print_r($this->rg->getSingleLimit($u, $type));
            sleep(1);
        }

        if($this->rg->checkTimeLimit($u, $type, true, $rgl) === false){
            echo "Limit has been reached:\n";
        } else {
            echo "Limit has NOT been reached:\n";
        }
        //$this->rg->resetTimeLimit($u, $rgl, $type);
        echo "Final $type limit:\n";
        print_r($this->rg->getSingleLimit($u, $type));        
    }

    function testStartRc($u){
        $rgl = $this->rg->getRcLimit($u);
        print_r($rgl);

        $this->rg->startRc($u, $rgl);

        $rgl = $this->rg->getRcLimit($u);
        print_r($rgl);
    }

    function printTriggers($u_obj, $table){
        $this->printTable($u_obj, $table);
    }

    function deleteTriggers($u_obj){
        $this->clearTable($u_obj, 'triggers_log');
    }
    
    function testOnRgLimitsChange($u_obj, $limit, $rgl, $action = 'change'){
        $this->deleteTriggers($u_obj);
        phive('Cashier/Arf')->invoke('onRgLimitChange', $u_obj, $limit, $rgl, $action);
        $this->printTriggers($u_obj);
    }
    
    function testRgTriggersOnBet($u_obj){
        $rg_tr = phive('Cashier/Rg');
        $last_deposit = phive('Cashier')->getLatestDeposit($u_obj);
        $this->deleteTriggers($u_obj);
        $bet  = ['amount' => 1];
        $game = ['tag' => 'roulette'];
        $rg_tr->onBet($u_obj, $bet, $game);
        echo "This should print an empty array:\n";
        $this->printTriggers($u_obj);

        $this->deleteTriggers($u_obj);
        $bet['amount'] = $last_deposit['amount'] + 1000000;
        $rg_tr->onBet($u_obj, $bet, $game);
        echo "This should print an RG15 trigger:\n";
        $this->printTriggers($u_obj);

        $this->deleteTriggers($u_obj);
        $game['tag'] = 'slots';
        $rg_tr->onBet($u_obj, $bet, $game);
        echo "This should print an RG12 and an RG15 trigger:\n";
        $this->printTriggers($u_obj);
    }
    

    function testLogAction($u_obj){
        $limits = [
            ['limit' => 100],
            ['limit' => 200],
            ['limit' => 300],
        ];

        $this->rg->logAction($u_obj, 'deposit', $limits, 'change');
        $this->printTable($u_obj, 'actions', 'target');
    }

    function testWithdrawalDepLimDeduction($u_obj){
        $this->deleteLimits($u_obj);
        $tc = TestPhive::getModule('CasinoCashier');
        $this->rg->addAllByType($u_obj, 'deposit', 100000);
        $this->rg->incType($u_obj, 'deposit', 50000);
        $pending = $tc->testPayPending($u_obj, 30000);
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
        $tc->testRevertPending($pending);
        $this->rg->applyToType($u_obj, 'deposit', 'print_r');
    }

    /*************************************************************************************************/
    /*
        Instructions to run the following tests
        * testBalanceLimitDecreased,
        * testBalanceLimitIncreased,
        * testRgLimitBalanceExceeds,
        * testRgLimitIsSavedCorrectly,
        * testBlocksAppliedOnBalanceExceeds,
        * testBlocksLiftedOnBalanceChange

        Get id for any user which we can use for testing.

        Create a test php file inside videoslots/diamondbet/test folder [eg. test_balance_rg_limit.php]
        Add following lines to the file

        <?php
        require_once __DIR__ . '/../../phive/phive.php';
        require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';
        require_once __DIR__ . '/../../phive/vendor/autoload.php';

        Phive('Test/TestRg')->testRgLimitIsSavedCorrectly();
        Phive('Test/TestRg')->testBalanceLimitDecreased();
        Phive('Test/TestRg')->testBalanceLimitIncreased();
        Phive('Test/TestRg')->testRgLimitBalanceExceeds();
        Phive('Test/TestRg')->testBlocksAppliedOnBalanceExceeds();
        Phive('Test/TestRg')->testBlocksLiftedOnBalanceChange();
        Phive('Test/TestRg')->testBlocksLiftedAfterNewLimitIsInEffect();
    */
    /*************************************************************************************************/
    public function testBalanceLimitDecreased()
    {
        echo "Executing testBalanceLimitDecreased ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        $new_limit = 1000;
        RgLimits()->saveLimit($u_obj, 'balance', 'na', 2000);
        RgLimits()->saveLimit($u_obj, 'balance', 'na', $new_limit);

        // now get limit from DB
        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $current_balance = $u_obj->getBalance();
        $progress = ($new_limit < $current_balance) ? $new_limit : $current_balance;

        $this->assertEquals($new_limit, $limit['cur_lim'], 'Current limit is correct.');
        $this->assertEquals($progress, $limit['progress'], 'Progress is correct.');
        $this->assertEquals(0, $limit['new_lim'], 'New limit is correct.');
        $this->assertEmpty($u_obj->getSetting('balance-limit-exceeded'), 'Asserting that balance_limit_exceeded is not set');

        $this->removeTestPlayer($u_obj);
    }

    public function testBalanceLimitIncreased()
    {
        echo "Executing testBalanceLimitIncreased ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        $increased_balance = 2000;
        $initial_balance = 500;
        RgLimits()->saveLimit($u_obj, 'balance', 'na', 1000);
        RgLimits()->onBalanceChanged($u_obj, $initial_balance);
        RgLimits()->saveLimit($u_obj, 'balance', 'na', $increased_balance); // Mock change rg limit

        // now get limit from DB
        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $changes_at = date('Y-m-d', strtotime("+7 day", time())); // Get 7th day.

        $this->assertEquals(1000, $limit['cur_lim'], 'Current limit is correct.');
        $this->assertEquals($increased_balance, $limit['new_lim'], 'New limit is correct.');
        $this->assertEquals($initial_balance, $limit['progress'], 'Progress is correct.');
        $this->assertEquals($changes_at, date('Y-m-d', strtotime($limit['changes_at'])), 'Changes at is set after 7 days');
        $this->assertEmpty($u_obj->getSetting('balance-limit-exceeded'), 'Asserting that balance_limit_exceeded is not set');

        $this->removeTestPlayer($u_obj);
    }

    public function testRgLimitBalanceExceeds()
    {
        echo "Executing testRgLimitBalanceExceeds ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 2500);
        RgLimits()->getLimit($u_obj, 'balance', 'na');
        Phive('Casino')->changeBalance($u_obj, 1500);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 1400);

        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $this->assertEquals(1400, $limit['cur_lim'], 'Current balance limit is correct');
        $this->assertEquals(1400, $limit['progress'], 'Progress is correct');
        $this->assertEquals(true, $u_obj->hasExceededBalanceLimit(), 'Asserting that balance_limit_exceeded is set');
        $this->assertEquals(true, $u_obj->isDepositBlocked(), 'Asserting user is deposit blocked');
        $this->assertEquals(true, $u_obj->isPlayBlocked(), 'Asserting user is play blocked');

        $this->removeTestPlayer($u_obj);
    }

    public function testBlocksAppliedOnBalanceExceeds()
    {
        echo "Executing testBlocksAppliedOnBalanceExceeds..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 1000);
        Phive('Casino')->changeBalance($u_obj, 1500);

        // now get limit from DB
        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $this->assertEquals(1000, $limit['cur_lim'], 'Current balance limit is correct');
        $this->assertEquals(1000, $limit['progress'], 'Progress is correct');
        $this->assertEquals(true, $u_obj->hasExceededBalanceLimit(), 'Asserting that balance_limit_exceeded is set');
        $this->assertEquals(true, $u_obj->isDepositBlocked(), 'Asserting user is deposit blocked');
        $this->assertEquals(true, $u_obj->isPlayBlocked(), 'Asserting user is play blocked');

        $this->removeTestPlayer($u_obj);
    }

    public function testBlocksLiftedOnBalanceChange()
    {
        echo "Executing testBlocksLiftedOnBalanceChange ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 1000);
        Phive('Casino')->changeBalance($u_obj, 1500);

        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        echo self::CONSOLE_COLOR_BLUE . "Assertions before mocking withdrawal ----------------" . PHP_EOL;
        $this->assertEquals(1000, $limit['cur_lim'], 'Current balance limit is correct');
        $this->assertEquals(1000, $limit['progress'], 'Progress is reached and is equal to limit');
        $this->assertEquals(true, $u_obj->hasExceededBalanceLimit(), 'Asserting that balance_limit_exceeded is set');
        $this->assertEquals(true, $u_obj->isDepositBlocked(), 'Asserting user is deposit blocked');
        $this->assertEquals(true, $u_obj->isPlayBlocked(), 'Asserting user is play blocked');

        Phive('Casino')->changeBalance($u_obj, -800); // Now mock withdrawal, making balance lower than limit

        // now get limit from DB
        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        echo self::CONSOLE_COLOR_BLUE. "Assertions after mocking withdrawal ----------------" . PHP_EOL;
        $this->assertEquals(1000, $limit['cur_lim'], 'Current balance limit is correct');
        $this->assertEquals(700, $limit['progress'], 'Progress is set to 700 [1500-800], mocking 800 is withdrawn');
        $this->assertEmpty($u_obj->getSetting('balance_limit_exceeded'), 'Asserting balance_limit_exceeded is not set.');
        $this->assertEquals(false, $u_obj->isDepositBlocked(), 'Asserting deposit blocked is lifted');
        $this->assertEquals(false, $u_obj->isPlayBlocked(), 'Asserting play blocked is lifted');

        $this->removeTestPlayer($u_obj);
    }

    public function testRgLimitIsSavedCorrectly()
    {
        echo "Executing testRgLimitIsSavedCorrectly ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        Phive('Casino')->changeBalance($u_obj, 1500);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 2000);
        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $this->assertEquals(2000, $limit['cur_lim'], 'Cur balance is correct');
        $this->assertEquals(1500, $limit['progress'], 'Progress is correct');
        $this->assertEquals(0, $limit['new_lim'], 'New limit is not set');

        $this->removeTestPlayer($u_obj);
    }

    public function testBlocksLiftedAfterNewLimitIsInEffect()
    {
        echo "Executing testBlocksLiftedAfterNewLimitIsInEffect ..........................". PHP_EOL;

        $u_obj = $this->getTestPlayer('NL');
        $this->resetRgLimitBalance($u_obj);

        RgLimits()->saveLimit($u_obj, 'balance', 'na', 1000);
        Phive('Casino')->changeBalance($u_obj, 1500);
        RgLimits()->getSingleLimit($u_obj, 'balance');

        $this->assertEquals(true, $u_obj->hasExceededBalanceLimit(), 'Asserting balance_limit_exceeded is not set.');

        $changes_at = date('Y-m-d', strtotime("+7 day", time())); // Get 7th day.
        RgLimits()->saveLimit($u_obj, 'balance', 'na', 2000);

        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');
        $this->assertEquals($changes_at, date('Y-m-d', strtotime($limit['changes_at'])), 'Asserting changes_at is set after 7 days');

        // Call \RgLimits::changeCron() with 8 days ahead of now
        RgLimits()->changeCron(phive()->hisMod('+8 days'));

        $limit = RgLimits()->getSingleLimit($u_obj, 'balance');

        $this->assertEquals(2000, $limit['cur_lim'], 'Asserting that increased limit is now set as current limit');
        $this->assertEquals(0, $limit['new_lim'], 'Asserting that new limit is now 0');
        $this->assertEquals(false, $u_obj->hasExceededBalanceLimit(), 'Asserting balance_limit_exceeded is lifted');
        $this->assertEquals(false, $u_obj->isDepositBlocked(), 'Asserting deposit_block is lifted');
        $this->assertEquals(false, $u_obj->isPlayBlocked(), 'Asserting play_block is lifted');

        $this->removeTestPlayer($u_obj);
    }

    public function testGetFixedCycleResetStamp(string $country)
    {
        $user_registered_at = Carbon::now()->subDays(4);
        $u_obj = $this->getTestPlayer($country, 30, '', $user_registered_at->toDateTimeString());
        $month_hours = rgLimits()::RESET_SPANS_IN_HOURS['month'];
        $test_time_span = 'month';
        $time_spans = ['day', 'week', 'month'];
        $setting = 'rg-month-limit-set-at';
        $this->printOutputData("Case1: User has no rg limits. Resets at time is based on registration time");
        $registered_at = $u_obj->getWholeSetting('registration_end_date')['created_at'];
        $now = Carbon::now();

        foreach ($time_spans as $time_span) {
            $source_timestamp = Carbon::parse($registered_at);
            $resets_at = rgLimits()->getFixedCycleResetStamp($u_obj, $time_span, new DateTime());
            $resets_at_time = Carbon::parse($resets_at);
            $hours = rgLimits()::RESET_SPANS_IN_HOURS[$time_span];
            while ($source_timestamp->lessThan($now)) {
                $source_timestamp->addHours($hours);
            }

            $msg = "Time span: {$time_span} | Registration time: {$registered_at} + {$hours} hours until now | modified resets at: {$resets_at}";
            $this->msg(
                (
                    $source_timestamp->day === $resets_at_time->day &&
                    $source_timestamp->hour === $resets_at_time->hour &&
                    $source_timestamp->minute === $resets_at_time->minute &&
                    $source_timestamp->second === $resets_at_time->second
                ),
                $msg,
                $msg
            );
        }

        $u_obj->deleteSetting($setting);
        sleep(3);
        $this->printOutputData("Case2: User has 'deposit' month rg limits. Resets at time is based on the limit");
        $this->addLimit($u_obj, 'deposit', $test_time_span, 40000);
        $u_obj->deleteSetting($setting);
        $limits = $this->rg->getByTypeUser($u_obj, 'deposit');
        $chg_deposit_resets_at = Carbon::parse($registered_at)->addHours(24);
        $this->db->sh($u_obj)->updateArray(
            'rg_limits',
            [
                'resets_at' => $chg_deposit_resets_at->toDateTimeString()
            ],
            [
                'id' => $limits['month']['id']
            ]
        );
        $limits = $this->rg->getByTypeUser($u_obj, 'deposit');
        print_r($limits);
        $deposit_resets_at = rgLimits()->getFixedCycleResetStamp($u_obj, $test_time_span, new DateTime());
        $msg = "Deposit limit resets_at time: {$chg_deposit_resets_at->toDateTimeString()} + {$month_hours} hours | modified resets at: {$deposit_resets_at}";
        $expected_resets_at = $chg_deposit_resets_at->copy()->addHours($month_hours);
        $this->msg($expected_resets_at->toDateTimeString() === $deposit_resets_at, $msg, $msg);

        $u_obj->deleteSetting($setting);
        sleep(3);
        $this->printOutputData("Case3: User has 'deposit' and 'loss' month rg limits. Resets at time is based on 'loss' limit");
        $this->addLimit($u_obj, 'loss', $test_time_span, 40000);
        $u_obj->deleteSetting($setting);
        $chg_loss_resets_at = Carbon::parse($registered_at)->addHours(48);
        $limits = $this->rg->getByTypeUser($u_obj, 'loss');
        $this->db->sh($u_obj)->updateArray('rg_limits', ['resets_at' => $chg_loss_resets_at->toDateTimeString()], ['id' => $limits['month']['id']]);
        $limits = $this->rg->getByTypeUser($u_obj, 'loss');
        print_r($limits);
        $loss_resets_at = rgLimits()->getFixedCycleResetStamp($u_obj, $test_time_span, new DateTime());
        $msg = "Source limit (loss) resets_at time: {$chg_loss_resets_at->toDateTimeString()} + {$month_hours} hours | resets at: {$loss_resets_at}";
        $expected_resets_at = $chg_loss_resets_at->copy()->addHours($month_hours);
        $this->msg($expected_resets_at->toDateTimeString() === $loss_resets_at, $msg, $msg);

        $this->removeTestPlayer($u_obj);
    }

    private function resetRgLimitBalance(DBUser $u_obj)
    {
        phive('SQL')->delete('rg_limits', ['type' => 'balance', 'time_span' => 'na'], $u_obj->getId());
        Phive('Casino')->changeBalance($u_obj, (-1*$u_obj->getBalance())); // Resetting balance for user to simply testing scenario.
        $u_obj->removeBalanceLimitExceeded();
    }

    private function removeTestPlayer(DBUser $u_obj)
    {
        $this->cleanupTestPlayer($u_obj->getId(), [
            'users_settings' => 'user_id',
            'rg_limits' => 'user_id',
            'actions' => 'target',
            ]
        );
    }

    protected function assertEquals($value1, $value2, $message = '')
    {
        $this->msg(($value1 == $value2), $message, $message);
    }

    protected function assertEmpty($value, $message = '')
    {
        $this->msg(empty($value), $message, $message);
    }
}