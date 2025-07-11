<?php
class TestGps extends TestPhive{

    function __construct(){
        $this->res = '';
        $this->gps = [
            'bsg'              => ['23', '23'], //done
            'edict'            => ['adp_candyfruits', 'edict_adp_candyfruits'],
            'endorphina'       => ['4OfAKing@ENDORPHINA', 'endorphina_4OfAKing@ENDORPHINA'],
            'gamesos'          => ['footballcupslots', 'gamesosxc_footballcupslots'],
            'Genii'            => ['SmallSoldiersVideoSlot', 'genii_SmallSoldiersVideoSlot'],
            'IGT'              => ['200-1239-001', 'igt_200-1239-001'], // done
            'isoftbet'         => ['1593', 'isoftbet1593'],
            'leander'          => ['ALIBABA', 'leander_ALIBABA'],
            'microgaming'      => ['mgs_cops_and_robbers', 'mgs_cops_and_robbers'], //done
            'multislot'        => ['39', 'mslot39'], //done
            'netent'           => ['lrbaccarat2_sw', 'netent_lrbaccarat2_sw'], //done
            'nyx'              => ['70001', 'nyx70001'], //done
            'playngo'          => ['4', 'playngo4'], //done
            //'pragmatic'        => ['vs25safari', 'pragmatic_vs25safari'],
            'qspin'            => ['colossus', 'qpsincolossus'], //done
            'redtiger'         => ['DragonsLuck', 'redtiger_DragonsLuck'],
            //'rival'            => ['391', 'rival391'], //Rival doesn't resend requests (will roll back anything that fails) so no need to test for idempotency
            'stakelogic'       => ['1002', 'stakelogic_1002'],
            'thunderkick'      => ['tk-magicians-a', 'thunderkick_tk-magicians-a'],
            'wi'               => ['alice', 'wialice'], //done
            'yggdrasil'        => ['5811', 'yggdrasil_5811'] //done
            
        ];
    }

    function getGp($str){
        return TestPhive::getModule(phive('Casino')->getNetworkName($str));
    }
    
    function testBetWinIdempotency($user, $bet_amount, $win_amount, $do_only = []){
        $ud = $user->data;
        $balance = 100000;
        $ud['cash_balance'] = $balance;
        $diff = $win_amount - $bet_amount;
        phive('SQL')->save('users', $ud);
        $expected_balance = $balance + $diff;
        foreach($this->gps as $str => $gid_arr){
            if(!empty($do_only) && !in_array($str, $do_only))
                continue;
            list($ext_game_id, $game_ref) = $gid_arr;
            $gp = $this->getGp($str);
            if(empty($gp))
                continue;
            if(!method_exists($gp, 'testIdempotency'))
                continue;
            $game         = phive('MicroGames')->getByGameRef($game_ref);
            $game['ext_game_id'] = $ext_game_id;            
            $gp->prepare($user, $game);
            $res          = $gp->testIdempotency($user, $game, $bet_amount, $win_amount);
            $new_balance  = $user->getBalance();
            if($new_balance != $expected_balance)
                $this->res .= "\n Idempotency failed for $str, next bet: $next_bet_id|$match_bet_id, next win: $next_win_id|$match_win_id, expected balance: $expected_balance, actual balance: $new_balance\n";
            else
                echo "\n Idempotency OK for $str \n";
            $expected_balance = $new_balance + $diff;
        }
        echo $this->res;
    }
    
}
