<?php
class TestSql extends TestPhive{

    function __construct(){
        $this->db = phive('SQL');        
    }

    function setupSlaveScaleBack($date1, $date2){
        foreach(range(0, count($this->db->getShards()) - 1) as $i){
            
            $slave_db   = $this->db->doDb('slave_shards', $i);
            $archive_db = $this->db->doDb('shard_archives', $i);
            
            foreach(['wins', 'bets', 'bets_mp', 'wins_mp'] as $tbl){
                $slave_db->query("TRUNCATE $tbl");
                $archive_db->query("TRUNCATE $tbl");
                foreach([$date1, $date2] as $y => $date){
                    $insert = [
                        'user_id'    => "1$i",
                        'amount'     => 100 - $y,
                        'game_ref'   => 'mgs_cops_and_robbers',
                        'created_at' => $date,
                        'mg_id'      => uniqid(),
                        'currency'   => 'EUR'
                    ];
                    $slave_db->insertArray($tbl, $insert);
                }
            }
        }
    }

    function scaleBackBiggestWin($u, $date1, $date2){
        $res = phive('MicroGames')->getBiggestWin($u->getId(), 'mgs_cops_and_robbers', $date1, $date2);
        print_r($res);
    }

    function scaleBackGetBets($u){
        $res = phive('Casino')->getBets($u);
        print_r($res);
    }

    function scaleBackRtpSessionGraph($u, $sstamp, $estamp){
        $this->clearTable($u, ['users_game_sessions']);
        $session_id = $this->db->sh($u)->insertArray('users_game_sessions', [
            'start_time' => $sstamp,
            'end_time'   => $estamp,
            'game_ref'   => 'mgs_cops_and_robbers',
            'bet_amount' => 100,
            'win_amount' => 100,
            'user_id'    => $u->getId()
        ]);

        $res = phive('MicroGames')->rtpGetSessionGraph($u, $session_id);
        print_r($res);
    }
    
    
    function dailyGameStats($day, $make = true){
        $sdate = "$day 00:00:00";
        $edate = "$day 23:59:59";
        if($make){
            echo "Making tmp tables\n";
            phive('Casino')->makeBetWinTmp($sdate, $edate);
        }
        $this->db->loopShardsSynced(function($db, $shard, $id) use($day){
            echo "Sh num: $id\n";
            $db->truncate('users_daily_game_stats');
            phive('MicroGames')->calcGameUserStats($day, $db);
        }); 
    }    
    
    function dailyMpStats($day){
        $this->db->loopShardsSynced(function($db, $shard, $id) use($day){
            echo "Sh num: $id\n";
            $db->truncate('users_daily_stats_mp');
            phive('Tournament')->calcDailyStats($day, $db);
        }); 
    }    

    function dailyUserStats($day, $make = true){
        $sdate = "$day 00:00:00";
        $edate = "$day 23:59:59";
        if($make){
            echo "Making tmp tables\n";
            phive('Casino')->makeBetWinTmp($sdate, $edate);
        }
        $this->db->loopShardsSynced(function($db, $shard, $id) use($sdate, $edate){
            echo "Sh num: $id\n";
            $db->truncate('users_daily_stats');
            phive('Cashier')->calcUserCache($sdate, $edate, $db);
        });
    }
    
    function testSharded($uid = '', $truncate = true){
        if($truncate){
            //We start with truncating the table on all nodes and the master.
            $this->db->truncate('trophy_events');
            sleep(1);
            $this->viewShardStatus('after truncate');
        }

        $user      = empty($uid) ? ud('devtestfi') : ['id' => $uid];
        $trophy_id = 10;
        $game_ref  = 'foo_bar';

        $te = ['user_id' => $user['id'], 'trophy_id' => $trophy_id, 'game_ref' => $game_ref];
        
        $this->db->sh($te)->insertArray('trophy_events', $te);
        $this->viewShardStatus('after first insert');

        $te['game_ref'] = 'foo_bar_after_save_1';
        $this->db->sh($te)->save('trophy_events', $te);
        $this->viewShardStatus('after first save');

        $te['trophy_id'] = 12;
        $te['game_ref'] = 'foo_bar_after_save_2';
        $this->db->sh($te)->save('trophy_events', $te);
        $this->viewShardStatus('after second save');

        $this->db->sh($te)->updateArray('trophy_events', ['game_ref' => 'foo_bar_after_update'], ['user_id' => $te['user_id'], 'trophy_id' => 12]);
        $this->viewShardStatus('after update array');

        echo "Testing fetch from all nodes:\n";
        $res = $this->db->shs()->loadArray("SELECT id, user_id, trophy_id, game_ref FROM trophy_events");
        print_r($res);

        echo "Testing fetch from one node with loadAssoc:\n";
        $res = $this->db->sh($te)->loadAssoc("SELECT id, user_id, trophy_id, game_ref FROM trophy_events WHERE trophy_id = 12 AND user_id = {$user['id']}");
        print_r($res);

        echo "Testing delete:\n";
        $res = $this->db->delete('trophy_events', ['trophy_id' => 10], $user['id']);
        $this->viewShardStatus('after delete');

    }

    function viewShardStatus($extra){
        echo "Master ($extra):\n";
        $sql = "SELECT id, user_id, trophy_id, game_ref FROM trophy_events";
        print_r($this->db->loadArray($sql));
        echo "Nodes ($extra):\n";
        $this->db->loopShardsSynced(function($sh_db, $sh) use ($sql){
            print_r($sh_db->loadArray($sql));
        });        
    }
    
    
    function viewGlobalStatus($extra){
        echo "Master ($extra):\n";
        print_r($this->db->loadArray("SELECT id, bonus_name FROM bonus_types"));
        echo "Nodes ($extra):\n";
        $this->db->loopShardsSynced(function($sh_db, $sh) use ($table){
            print_r($sh_db->loadArray("SELECT id, bonus_name FROM bonus_types"));
        });        
    }
    
    function testShardedGlobals(){
        //We start with truncating the table on all nodes and the master.
        $this->db->shs()->truncate('bonus_types');
        sleep(1);
        $this->viewGlobalStatus('after truncate');

        $this->db->insertArray('bonus_types', ['bonus_name' => 'Bonus 1']);
        sleep(1);
        $this->viewGlobalStatus('after initial insert');
        
        $b = $this->db->loadArray("SELECT * FROM bonus_types")[0];
        $b['bonus_name'] = 'Bonus 1 after save';
        $this->db->save('bonus_types', $b);
        sleep(1);
        $this->viewGlobalStatus('after first save');

        $this->db->save('bonus_types', ['bonus_name' => 'Bonus 2']);
        sleep(1);
        $this->viewGlobalStatus('after second save');

        $this->db->updateArray('bonus_types', ['bonus_name' => 'Bonus 2 after update array'], ['id' => 2]);
        sleep(1);
        $this->viewGlobalStatus('after update array');
        
    }

    function verifyGlobalTbl($tbl){
        $master_rows = $this->db->loadArray("SELECT * FROM $tbl");
        $this->db->loopShardsSynced(function($sh_db) use($tbl, $master_rows){
            $shard_rows = $this->db->loadArray("SELECT * FROM $tbl");
            foreach($shard_rows as $key => $row){
                if($row !== $master_rows[$key]){
                    echo "These two rows are not equal:\n";
                    echo "The master row:\n";
                    print_r($master_rows[$key]);
                    echo "The shard row:\n";
                    print_r($row);
                    exit;
                } else {
                    $shard_hash = md5(json_encode($row));
                    $master_hash = md5(json_encode($master_rows[$key]));
                    echo "$master_hash : $shard_hash\n";
                }
            }
        });
    }
    
    
    
}
