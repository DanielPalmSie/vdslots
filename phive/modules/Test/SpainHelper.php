<?php

class SpainHelper {

    function __construct(){
        $this->wh = phive('DBUserHandler/JpWheel');
    }

    function removeSpain($rows, $tbl, $col, $sql){
        foreach($rows as $row){
            $countries = explode(' ', $row[$col]);
            $countries = array_filter($countries, function($country){
                return $country != 'ES' && $country != 'IT';
            });
            $row[$col] = implode(' ', $countries);
            $sql->save($tbl, $row);
        }
    }

    function retrieveJackpots($es_user, $row_user){
        $es_jps = $this->wh->getWheelJackpots($es_user);
        print_r($es_jps);
        $row_jps = $this->wh->getWheelJackpots($row_user);
        print_r($row_jps);
    }

    function enableGp($sql, $gp = 'microgaming'){
        $rows = $sql->loadArray("SELECT * FROM micro_games WHERE network = '$gp'");
        $this->removeSpain($rows, 'micro_games', 'blocked_countries', $sql);
        foreach($rows as $r){
            foreach(['IT', 'ES'] as $country){
                $sql->insertArray('game_country_versions', [
                    'game_id' => $r['id'],
                    'ext_game_id' => $r['ext_game_name'],
                    'country' => $country,
                    'game_version' => 1,
                    'game_regulatory_code' => uniqid(),
                    'game_type' => 2
                ]);
            }
        }
        $sql->syncGlobalTableSynced(['micro_games']);
        mCluster('qcache')->delAll('*qcache');
    }

    function giveJpContrib($th, $u){
        $gref = 'netent_starburst_not_mobile_sw';
        $cur_game = phive('MicroGames')->getByGameRef($gref);
        $th->giveJackpotContribution($cur_game, $u, 20);
    }

    function getCurrentExtGameSession($u){
        return phive('SQL')->sh($u)->loadAssoc('', 'ext_game_participations', ['user_id' => $u->getId()], true);
    }

    function setupAjaxInitGameSession($args){
        $u = $args['u_obj'];
        $_SESSION['mg_username'] = $u->getUserName();
        $_SESSION['user_id'] = $u->getId();
        //print_r($u);
        $gref = $args['brand_gid'];
        $game = phive('MicroGames')->getByGameRef($gref, 0);

        phive('SQL')->truncate('ext_game_participations');

        $post = [
            'gameLimit' => 60000,
            'balance' => 34,
            'game_ref' => $gref,
            'setReminder' => 1500,
            'restrictFutureSessions' => 432000,
            'lang' => 'es',
            'site_type' => 'normal',
            'return_format' => 'json',
            'lic_func' => 'updateExternalGameSessionBalance'
        ];

        phMdelShard('ext-game-session-balance-before-popup', $u);
        phMdelShard('ext-game-session-stake', $u);
        phMdelShard('ext-game-session-id', $u);

        $res = null;
        if (lic('hasGameplayWithSessionBalance', [], $u)) {
            $res = lic('ajaxUpdateExternalGameSessionBalance', [$post, $u], $u);
            
            echo "ajaxUpdateExternalGameSessionBalance result: \n";
            print_r($res);
            
            if(empty($res['newToken'])){
                die("Token missing from ajax external game session balance result!\n");
            }
        }

        return $res;
    }

    function setupInitGameSession($res, $casino, $u){
        $gref = $_SERVER['argv'][1] ?? "netent_starburst_not_mobile_sw";
        $game = phive('MicroGames')->getByGameRef($gref, 0);

        if (lic('hasGameplayWithSessionBalance', [], $u)) {
            $session_id = lic('initGameSessionWithBalance', [$u, $res['newToken'], $game], $u);
            echo "Session id: $session_id\n";
        }

        if (lic('hasGameplayWithSessionBalance', [], $u)  === true) {
            if (lic('hasExceededTimeLimit', [$u], $u) === true) {
                echo "Has exceeded time limit!\n";
            }
        }

        if($casino->useExternalSession($u)){
            $casino->setExternalSessionByToken($u, $res['newToken']);

            print_r($casino->session_entry);

            if($casino->hasSessionBalance()){
                echo "Has session balance, it is: {$casino->getSessionBalance($u)}\n";
            }
        }

        return $casino->session_entry;
    }

    function setupGameSession($sql, $casino, $u){
        $res = $this->setupAjaxInitGameSession($sql, $casino, $u);
        return $this->setupInitGameSession($res, $casino, $u);
    }


    function testUseJpAward($th, $sql, $u, $jp_id){
        $jp = $sql->loadAssoc('', 'jackpots', ['id' => 13], true);
        $jp['amount'] = 100000;
        $sql->save('jackpots', $jp);
        $ins = [
            'type'        => 'wheel-of-jps',
            'valid_days'  => 7,
            'description' => 'Spanish Test JP Award',
            'alias'       => 'spanish-diamond_wheelofjackpots'.uniqid(),
            'mobile_show' => 1,
            'jackpots_id' => $jp_id
        ];
        $award_id = $sql->insertArray('trophy_awards', $ins);
        $th->giveAward($award_id, $u);
        $th->useJpAward($award_id, $u->getId(), 1, uniqid());
    }

    function setupJps($sql){
        $jps = $sql->loadArray("select * from jackpots");
        foreach($jps as $jp){
            $jp['excluded_countries'] = 'ES';
            $sql->save('jackpots', $jp);
            unset($jp['id']);
            $jp['contribution_next_jp'] = 0;
            $jp['amount_minimum'] = 0;
            $jp['amount'] = 0;
            $jp['name'] = str_replace('Jackpots', 'Botes', $jp['name']);
            $jp['included_countries'] = 'ES';
            $jp['excluded_countries'] = '';
            $sql->insertArray('jackpots', $jp);
        }

    }

}
