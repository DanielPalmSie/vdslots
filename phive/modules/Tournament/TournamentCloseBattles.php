<?php

require_once __DIR__ . '/Tournament.php';

class TournamentCloseBattles extends Tournament{

    /** @var SQL $db */
    public $db;

    function __construct(){
        $this->db = phive('SQL');
        $this->mc = mCluster('mp');
    }

    function phAliases () { 
        return array('Tournament'); 
    
    }
    /**
     * Get all related tournament data for close battles
     *
     * @param int $tournament_id
     * 
     * @return array
     */
    function getAllRelatedTournaments($tournament_id = null){

        //initializing array
        $tournament_list = array();

        //where clause for the query
        $where = "WHERE te.win_amount > 0 AND t.status = 'finished' AND te.result_place = 0 ";

        if($tournament_id != null){
            $where .= "AND t.id = $tournament_id";
        }
        
        //target sql for get tournament_ids of given criteria (win_amount > 0 ,status = 'finished and result_place = 0)
        $sql_by_criteria = "SELECT t.id, t.tournament_name FROM tournaments t JOIN tournament_entries te ON t.id=te.t_id $where group by te.t_id;";

        //looping in every shard to fetch target data of above query
        $this->db->loopShardsSynced(function($db, $shard, $id) use($sql_by_criteria, &$tournament_list){
            $tournament_list = array_merge($tournament_list, $this->db->sh($id)->loadArray($sql_by_criteria));
        });
        
        //remove duplicate tournament ids from the array
        $final_tournament_list = array_unique($tournament_list, SORT_REGULAR);

        if($tournament_id != null){
            return $final_tournament_list[0];

        } 

        return $final_tournament_list;

    }

     /**
     * Get all related tournament entries data for close battle
     *
     * @param array $tournament_list
     * 
     * @return array
     */
    function getRelatedTournamentEntries($tournament_list = null){

        //initializing array
        $tournament_entries_list = array();

        if(!empty($tournament_list)){
            foreach ($tournament_list as $t) {
                //tournament id
                $t_id = $t['id'];
    
                //get all tournament entries data for tournament id
                $sql = "SELECT te.t_id, te.result_place FROM tournaments t JOIN tournament_entries te ON t.id=te.t_id WHERE t.id = {$t_id} ";
    
                //looping in every shard to fetch target data
                $this->db->loopShardsSynced(function($db, $shard, $id) use($sql, &$tournament_entries_list){
                    $tournament_entries_list = array_merge($tournament_entries_list, $this->db->sh($id)->loadArray($sql));                
    
                    //adding corresponding shard id to each tournament entries
                    $tournament_entries_list = array_map(function($s) use ($id){
                        return $s + ['shard' => $id];
                    }, $tournament_entries_list);
                });
    
            }
        }

        return $tournament_entries_list;
        
    }

    /**
     * close, calculate prizes and pay unpaid finished battles
     * 
     * @param array $tournament_entries_list
     *
     * @return boolean
     */
    function closeAndPayUnpaidBattles($tournament_entries_list = null){

        if(!empty($tournament_entries_list)) {

            //initializing multidimensional array for 3 scenarios that is occuring
            //Scenario 1 : All users from tournamnet_entries table with result_place=0 for every user from a tournament
            //Scenario 2 : All users from one or more nodes, but not all from tournamnet_entries table with result_place=0 for every user from a tournament 
            //Scenario 3 : All users from all nodes, since an specific result palce from tournament_entries table with result_place=0 for every user from a tournament

            $tournament_list = array(
                'scenario1' => array(),
                'scenario2' => array(),
                'scenario3' => array()
            );

            foreach($tournament_entries_list as $t_id => $value){

                //arranging data array for scenario1
                if(empty($tournament_list['scenario1'][$value['t_id']])){
                    $tournament_list['scenario1'][$value['t_id']] = array();
                }

                $tournament_list['scenario1'][$value['t_id']][] = $value['result_place'];

                //arranging data array for scenario2
                if(empty($tournament_list['scenario2'][$value['t_id']][$value['shard']])){
                    $tournament_list['scenario2'][$value['t_id']][$value['shard']] = array();
                }

                $tournament_list['scenario2'][$value['t_id']][$value['shard']][] = $value['result_place'];

                //arranging data array for scenario3
                if(empty($tournament_list['scenario3'][$value['t_id']])){
                    $tournament_list['scenario3'][$value['t_id']] = array();
                }

                $tournament_list['scenario3'][$value['t_id']][] = $value['result_place'];
                
            }

            //preparing data array for scenario1
            $first_scenario = $this->arrangeFirstScenarioData($tournament_list);

            //preparing data array for scenario2
            $second_scenario = $this->arrangeSecondScenarioData($first_scenario['updated_tournament_list']);

            //preparing data array for scenario3
            $third_scenario = $this->arrangeThirdScenarioData($second_scenario['updated_tournament_list']);

            //closing and pay out for scenario1
            if(!empty($first_scenario['first_scenario_data'])){
                $this->closeAndCalcBattlesOnAllNodes($first_scenario['first_scenario_data']);
            } 

            //closing and pay out for scenario2
            if(!empty($second_scenario['second_scenario_data'])){
                $this->closeAndCalcBattles($second_scenario['second_scenario_data'], $second_scenario['shard_data'], true, array());
            } 

            //closing and pay out for scenario3
            if(!empty($third_scenario)){
                $this->closeAndCalcBattles(array_keys($third_scenario), array(), false, $third_scenario);
            }

        }
        
    }

    /**
     * Arrange data array for scenario 1
     * Scenario 1 : All users from tournamnet_entries table with result_place=0 for every user from a tournament
     * 
     * @param array $tournament_list
     * 
     * @return array
     */
    function arrangeFirstScenarioData($tournament_list){

        //initializing array
        $first_scenario_data = array();
        $return_array = array();

        foreach($tournament_list['scenario1'] as $t_id => $list){
            //check if all the result place data for specific tournament is 0
            if (count(array_unique($list)) === 1 && end($list)['result_place'] == 0) {

                $first_scenario_data[] = $t_id;

                //remove scenario1 data from array of scenario2 if exists
                if(!empty($tournament_list['scenario2'][$t_id])){
                    unset($tournament_list['scenario2'][$t_id]); 
                }
                
                //remove scenario1 data from array of scenario3 if exists
                if(!empty($tournament_list['scenario3'][$t_id])){
                    unset($tournament_list['scenario3'][$t_id]);
                }
                 
            }
        }

        $return_array['updated_tournament_list'] = $tournament_list;
        $return_array['first_scenario_data'] = $first_scenario_data;

        return $return_array;

    }

    /**
     * Arrange data array for scenario 2
     * Scenario 2 : All users from one or more nodes, but not all from tournamnet_entries table with result_place=0 for every user from a tournament
     * 
     * @param array $tournament_list
     * 
     * @return array
     */
    function arrangeSecondScenarioData($tournament_list){

        //initializing arrays
        $filtered_tournament_list1 = array();
        $shard_data = array();
        $return_array = array(); 

       foreach($tournament_list['scenario2'] as $t_id => $list){
           
           foreach($list as $k => $val){
               //check if all the result place data for specific node and a tournament is 0
               if (count(array_unique($val)) === 1 && end($val)['result_place'] == 0)  {
                   $all_shard_array[$t_id][] = $k;
                   if(!in_array($t_id, $filtered_tournament_list1)){
                       $filtered_tournament_list1[] = $t_id;
                   } 
                   $shard_data[$t_id][] = $k;
               }
           }

           //differenciate the scenario2 from scenario3
           if(in_array($t_id, $filtered_tournament_list1)){
               $all_shard_data = $list;
               foreach($shard_data[$t_id] as $id){
                   unset($all_shard_data[$id]);
               }
               $zero_flag = false;
               foreach($all_shard_data as $value){
                   if (in_array(0,$value)){
                       $zero_flag = true;
                       break;
                   }
               }
               if($zero_flag){
                   $index = array_search($t_id, $filtered_tournament_list1);
                   if (false !== $index) {
                       unset($filtered_tournament_list1[$index]);
                       unset($shard_data[$t_id]);
                   }
               } else {
                   unset($tournament_list['scenario3'][$t_id]);
               }
           }
            
       }

       $return_array['updated_tournament_list'] = $tournament_list;
       $return_array['second_scenario_data'] = $filtered_tournament_list1;
       $return_array['shard_data'] = $shard_data;

       return $return_array;

   }

   /**
     * Arrange data array for scenario 3
     * Scenario 3 : All users from all nodes, since an specific result palce from tournament_entries table with result_place=0 for every user from a tournament
     * 
     * @param array $tournament_list
     * 
     * @return array
     */
    function arrangeThirdScenarioData($tournament_list){

        //initializing array
        $third_scenario_data = array();

        foreach($tournament_list['scenario3'] as $t_id => $list){
            if(in_array(0, $list)){
                //get the max paid out result place of the specific tournament
                $third_scenario_data[$t_id] = max($tournament_list['scenario3'][$t_id]);
            }
            
        }

        return $third_scenario_data;

    }

    /**
     * function used for closing tournaments and claculate payout and prizes for Scenario 1
     * 
     * Scenario 1 : All users from tournamnet_entries table with result_place=0 for every user from a tournament with status=‘finished’ and win_amount>0
     * 
     * @param array $all_tournaments
     */
    function closeAndCalcBattlesOnAllNodes($all_tournaments){

        foreach ($all_tournaments as $t) {

            $where = ['id' => $t];
            $tournament = $this->db->loadAssoc('', 'tournaments', $where, true);

            phMdel('prizes-calculated' . $tournament['id']);
            $this->calcPrizes($tournament, $skip_check = true); 
        }

    }

    /**
     * close the tournaments by tournament id. 
     * 
     * @param $t_id
     * 
     */
    function closeBattlesById($t_id){

        //looping in every shard to update data
        $this->db->loopShardsSynced(function($db, $shard, $id) use($t_id){
            $this->db->sh($id)->updateArray('tournament_entries',
                    ['status' => "finished" ],
                    ['t_id' => $t_id, 'status' => "open"]
                );
        });

    }

    /**
     * function used for closing tournaments and claculate prizes for both Scenario 2 and  Scenario 3
     * 
     * Scenario 2 : All users from one or more nodes, but not all from tournamnet_entries table with result_place=0 for every user from a tournament with status=‘finished’ and win_amount>0
     * Scenario 3 : All users from all nodes, since an specific result palce from tournament_entries table with result_place=0 for every user from a tournament with status=‘finished’ and win_amount>0
     * 
     * @param array $all_tournaments
     * @param array $node_list
     * @param boolean $node_flag  If Scenario 2 node_flag will be true 
     * @param array $last_result_place_list
     *  
     */
    function closeAndCalcBattles($tournament_list, $node_list, $node_flag, $last_result_place_list){

        if(!empty($tournament_list)){
            foreach ($tournament_list as $t_id){

                $where = ['id' => $t_id];
                $t = $this->db->loadAssoc('', 'tournaments', $where, true);

                $this->closeBattlesById($t['id']);

                $this->ladder = [];

                if($node_flag){
                    $skip_check = true;

                    if((!$skip_check) && (!empty($t['prizes_calculated']))){
                        return;
                    }

                } else {
                    $skip_check = false;
                }
                

                $t['prizes_calculated'] = 1;
                $this->save($t);
                $tpl                    = $this->getParent($t);
                $users                  = array();
                $entries                = $this->sortLeaderboard($t, $this->entries($t));

                foreach($entries as $e){
                    $users[$e['id']] = cu($e['user_id']);
                }

                if (!empty($tpl['award_ladder_tag'])) {

                    //handle award and prizes and do the payouts
                    $this->handleAwardAndPrizes($entries, $t, $users, true, $node_list, $node_flag, $last_result_place_list);

                    if(!$node_flag){
                        $this->save($t);
                        $this->handleBountyPrize($t);
                    }

                }

                if($node_flag){
                    $this->save($t);
                    $this->handleBountyPrize($t);
                }
                
            
                $this->setPrizeAmount($entries, $t);

                //handle award and prizes and do the payouts
                $this->handleAwardAndPrizes($entries, $t, $users, false, $node_list, $node_flag, $last_result_place_list);
            
                $this->save($t);
                $this->handleBountyPrize($t);
            
                $this->initMem($t, false);

            }
        }

    }

    /**
     * handles awards, prizes and cash balances
     * 
     * @param array $entries
     * @param array $t
     * @param array $users
     * @param boolean $award_flag If award_flag is true handles and the prizeawards and giveAward from Trophy.php othervise hadles cash prizes
     * @param array $node_list shard db id for each tournaments
     * @param boolean $node_flag If Scenario 2 node_flag will be true 
     * @param array $last_result_place_list Last calculated result place for Scenario 3
     */

     function handleAwardAndPrizes($entries, $t, $users, $award_flag, $node_list, $node_flag, $last_result_place_list){

        foreach ($entries as $i => $e) {

            if (!$this->canGetPrize($e, $t)){
                continue;
            }

            $u = $users[$e['id']];
            $ud = $u->data;

            $this->wsCalcPrizeFinished($ud, $t, $e);

            $place = $i + 1;
            $this->winEvent($t, $place, $ud);

            if($award_flag){
                $a = $this->getPrizeAward($t, $place, $e['user_id']);
                $res[] = $a;
                $won_str = empty($a) ? 'nothing' : $a['alias'];

                //checks if Scenario 2
                if($node_flag){
                    $user_val = str_split($u->userId);
                    $lastDigit = end($user_val);

                    //checks if the user's last digit maches node id for the specific tournament
                    if(in_array($lastDigit, $node_list[$t['id']])){
                        phive('UserHandler')->logAction($u, "Close Battles: Scenario 2(log1) Won: $won_str, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'close battles');
                        $this->giveRewardsInCloseBattles($u, $place, $t, $a, $ud, $e);
                    }
                } else {
                    //Max calculated result place for Scenario 3 
                    $last_result_place = $last_result_place_list[$t['id']];

                    //if the result place is graterthan max calculated result place do the payouts
                    if ($place > $last_result_place) {
                        phive('UserHandler')->logAction($u, "Close Battles: Scenario 3(log1): Won: $won_str, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'close battles');
                        $this->giveRewardsInCloseBattles($u, $place, $t, $a, $ud, $e);
                    }
                }

            } else {

                if($t['category'] == 'added' && !empty($t['guaranteed_prize_amount'])){
                    // Prize diff is the whole guaranteed as that is now regarded as the added amount.
                    $prize_diff = $t['guaranteed_prize_amount'];
                    // The prize amount is now the whole total of guaranteed and buy ins.
                    $prize_amount = $t['guaranteed_prize_amount'] + $t['prize_amount'];
                }elseif(!empty($t['guaranteed_prize_amount']) && $t['guaranteed_prize_amount'] > $t['prize_amount']){
                    $prize_diff = $t['guaranteed_prize_amount'] - $t['prize_amount'];
                    $prize_amount = $t['guaranteed_prize_amount'];
                }else{
                    $prize_amount = $t['prize_amount'];
                }

                $percent          = $this->getPrizePercent($t, $place);
                $prize            = floor($percent * $prize_amount);
                $prize_changed    = floor($this->chgToUsr($u, $prize));
                $e['won_amount']  = $prize;

                //checks if Scenario 2
                if($node_flag){
                    $user_val = str_split($u->userId);
                    $lastDigit = end($user_val);

                    //checks if the user's last digit maches node id for the specific tournament
                    if(in_array($lastDigit, $node_list[$t['id']])){
                        phive('UserHandler')->logAction($u, "Close Battles: Scenario 2(log2): Won: $prize, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'close battles');
                        $this->givePrizesInCloseBattles($u, $t, $e, $place, $prize_changed,$prize_diff, $percent, $prize);
                    }
                    
                } else {
                    //Max calculated result place Scenario 3
                    $last_result_place = $last_result_place_list[$t['id']];

                    //if the result place is graterthan max calculated result place do the payouts
                    if ($place > $last_result_place) {
                        phive('UserHandler')->logAction($u, "Close Battles: Scenario 3(log2): Won: $prize, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'close battles');
                        $this->givePrizesInCloseBattles($u, $t, $e, $place, $prize_changed,$prize_diff, $percent, $prize);
                    }
                }
            }
        }
    }


    /**
     * handles awards, and cash balances
     * 
     * @param array $u
     * @param int $place
     * @param array $t
     * @param array $a
     * @param array $ud
     * @param array $e
     */
    function giveRewardsInCloseBattles($u, $place, $t, $a, $ud, $e){
        phive('Trophy')->giveAward($a, $ud);
        $e['result_place'] = $place;
        $this->saveEntry($e);
        $this->returnCashBalance($u, $t, $e);
    }

    /**
     * handles prizes, and cash balances
     * 
     * @param array $users
     * @param array $t
     * @param array $e
     * @param int $place
     * @param float $prize_changed
     * @param float $prize_diff
     * @param float $percent
     * @param float $prize
     */
    function givePrizesInCloseBattles($u, $t, $e, $place, $prize_changed,$prize_diff, $percent, $prize){
        if ($t['prize_type'] == 'cash-fixed'){
            $this->returnCashBalance($u, $t, $e);
        }
        $e['result_place'] = $place;
        if (!empty($prize)){
            $this->handleCashPrize($u, $prize_changed, $t, $prize_diff, $percent, $prize, $e);
        }
        $this->saveEntry($e);
    }
}