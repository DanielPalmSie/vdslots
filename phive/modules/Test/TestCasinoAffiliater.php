<?php
class TestCasinoAffiliater extends TestPhive{

    function testCalcProfit($aid, $amount){
        
    }

    function testCpa($snum, $enum, $bcode, $dep_amount, $wager_amount){
        foreach(range($snum, $enum) as $num){
            $user = [
                'username' => 'testaffplayer'.$num,
                'bonus_code'    => $bcode
            ];
            $uid = phive('SQL')->insertArray('users', $user);
            $deposit = ['amount' => $dep_amount, 'dep_type' => 'skrill', 'currency' => 'EUR', 'user_id' => $uid];
            phive('SQL')->save('deposits', $deposit);
            $bet = ['amount' => $wager_amount, 'mg_id' => uniqid(), 'game_ref' => 'netent_secretofthestones_sw', 'currency' => 'EUR', 'user_id' => $uid];
            phive('SQL')->save('bets', $bet);
        }
    }

    function compareDaily($date){
        $aff_stats   = phive('SQL')->loadArray("SELECT SUM(real_prof) AS amount, currency FROM affiliate_daily_stats WHERE `day_date` = '$date' GROUP BY CURRENCY");
        $users_stats = phive('SQL')->loadArray("SELECT SUM(real_aff_fee) AS amount, currency FROM users_daily_stats WHERE `date` = '$date' GROUP BY CURRENCY");
        $aff_total = 0;
        foreach($aff_stats as $r)
            $aff_total += chg($r['currency'], 'EUR', $r['amount'], 1, $date);
        $users_total = 0;
        foreach($users_stats as $r)
            $users_total += chg($r['currency'], 'EUR', $r['amount'], 1, $date);
        print_r([$aff_total, $users_total]);
    }
    
}
