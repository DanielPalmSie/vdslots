<?php
class TestMicroGames extends TestPhive {

    function __construct(){
        $this->mg = phive('MicroGames');
        $this->c = phive('Casino'); 
    }


    function setInfo($type){
	$this->info = array(
	    'incPlayedTimes'				=> array("mgs_megamoolah_html5", "mgs_megamoolah"),
	    'incCache'					=> array("mgs_megamoolah_html5", "mgs_megamoolah"),
	    'getPopular' 				=> "10,videoslots",
	    'getPlayedOn' 				=> "2012-09-11",
	    'getRand' 					=> "10",
	    'getAllGames'				=> "game_name LIKE '%mega%'",
	    'getGamesOrderLimit' 			=> "played_times,10",
	    'allGamesSelect' 				=> "game_id,tag = 'videoslots'",
	    'allGamesSelect' 				=> "",
	    'getTaggedBy'				=> array("", "videoslots", "videoslots,5,10", "videoslots,0,10,5wheelslots.cgames"),
	    'getRecentPlayed'				=> array("20916", array('args' => array(phive("UserHandler")->getUserByUsername('Antarai')))),
	    'getByGameId'				=> "CopsAndRobbers",
	    'getByGameRef'				=> "mgs_cops_and_robbers",
	    'getByGameUrl'				=> "cops-and-robbers",
	    'getGameRefById'				=> "CopsAndRobbers",
	    'getGameTagByRef'				=> "mgs_cops_and_robbers",
	    'getGameJpContribByRef'			=> "mgs_megamoolah",
	    'getGameLang'				=> "MegaMoolah,sv",
	    'getIdWithGid'				=> "MegaMoolah",
	    'getAllTags'				=> "",
	    'getSubWhere'				=> "",
	    'getSubWhere'				=> "tag = 'videoslots'",
	    'getAllSubTags'				=> "videoslots",
	    'getGroupedByTag'				=> array('args' => array(array('videoslots' => 'Videoslots'))),
	    'getUrl'					=> "MegaMoolah",
	    'getAllJpIds'				=> "",
	    'getJpSum'					=> "",
	    'getAllJps'					=> "",
	    'getJp'					=> "1",
	    'getJpsGrouped'				=> "",
	    'getProgressives'				=> "",
	    'getBetSumPerGameDay'			=> array("mgs_megamoolah,2012-05-01 00:00:00,2012-05-31 23:59:59,0", "mgs_megamoolah,2012-05-01 00:00:00,2012-05-31 23:59:59,1"),
	    'getJpContribStatsByDayGame' 	        => "2012-09-01 00:00:00,2012-09-31 23:59:59",
	    'getJpContribStats' 			=> "2012-09-01 00:00:00,2012-09-31 23:59:59",
	    'getAllJpsGames'				=> array("gms.tag = 'videoslots_jackpot'", "gms.tag = 'videoslots'", ""),
	    'groupBySub'				=> array('', array('args' => array(array('new.cgames'))))
	);
	
	$this->handlers = array(
	    'incPlayedTimes' 	=> function($obj, $gref){
		echo $gid;
		return "Played times for $gref: ".phive("SQL")->getValue('', 'played_times', 'micro_games', "game_ref = '$gref'");
	    },
	    'incCache' 			=> function($obj, $gid){
		$date = phive()->today();
		return "Played times in cache for $gref and $date: ".phive("SQL")->getValue('', 'played_times', 'game_cache', "game_ref = '$gref' AND day_date = '$date'");
	    });
	
	if($type == 'quick'){
	    unset($this->info['getBetSumPerGameDay']);
	    unset($this->info['getJpContribStatsByDayGame']);
	    unset($this->info['getJpContribStats']);
	}
    }

    // Second arg is primary game id key
    function testGameSession($uid, $id = 2322){
        phM('delAll', '*');
        phive('SQL')->truncate('users_game_sessions');
        $user                = cu($uid);
        $uid                 = uid($uid);
        $game                = $this->mg->getById($id);
        $game['game_ref']    = $game['ext_game_name'];
        $game['device_type'] = $game['device_type_num'];
        $ins                 = array_merge(['user_id' => $uid], $game, ['amount' => 1000]);        
        $this->c->startGsess($ins);

        echo "Current session key: ".$this->c->getSessKey($ins, 'current')."\n";
        echo "First: \n";
        print_r(phM('asArr', '*'));

        
        phM('lpush', $this->c->getSessKey($ins, 'current'), json_encode(array_merge(array('type' => 'bet', 'created_at' => phive()->hisNow()), $ins)), 36000);
        phM('lpush', $this->c->getSessKey($ins, 'current'), json_encode(array_merge(array('type' => 'win', 'created_at' => phive()->hisNow()), $ins)), 36000);
        echo "Second: \n";
        print_r(phM('keys', '*'));

        $arr = phive('Redis')->getRange($this->c->getSessKey($ins, 'current'));
        print_r($arr);
        
        
        $this->c->finishUniqueGameSession($this->mg->uniqueGameSession($user, $game, 'gsess-stime-'), '', $user->data);


        echo "Finished session, users game sessions:\n";
        print_r(phive('SQL')->loadArray("SELECT * FROM users_game_sessions"));
        //$this->mg->timeoutGameSessions();
    }

    function testFilterBlocked($arr, $country){
        $games = phive('SQL')->loadArray("SELECT * FROM micro_games", 'ASSOC', 'game_id');
        $me = $this;
        return array_filter($arr, function($b) use ($games, $country, $me){
            if(empty($b['game_id']))
                return true;
            return !$me->isBlocked($games[$b['game_id']], $country);
        });
    }

    function TestIsBlocked(&$game, $country = null){
        $u = cu('wolfydan');
        if(empty($u))
            return false;

        $cur_country = empty($country) ? $u->getCountry() : $country;
        if (empty($cur_country)) {
            $cur_country = null; // Remove "needle is empty" warning.
        }
        if(strpos($game['blocked_countries'], $cur_country) !== false)
            return true;
        if(empty($game['included_countries']))
            return false;
        if(strpos($game['included_countries'], $cur_country) === false)
            return true;
        // New AU player block
        if($cur_country == 'AU'){
            $cnt = phive('Cashier')->getDepositCount(uid(), '', " AND `timestamp` BETWEEN '2017-01-01 00:00:00' AND '2017-09-07 00:00:00'");
            if(empty($cnt) && !$u->hasSetting('bypass-au-playcheck')){
                return true;
            }
        }
        return false;
    }
    
    
}
