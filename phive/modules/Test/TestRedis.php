<?php
class TestRedis extends TestPhive{

    function asArr($pat){
        phMset("{$pat}foo", 'value1');
        phMset("{$pat}bar", 'value2');
        phMset("{$pat}baz", 'value3');
        $res = phM('asArr', "$pat*");
        return $res;
    }

    function dumpKeyCounts(){
        $redis = phive('Redis');

        $clusters = array_keys($redis->getSetting('proxy'));

        $port_range = [];
        foreach($clusters as $cl){
            $port_range = array_merge(array_column($redis->getSetting($cl), 'port'), $port_range);
        }

        asort($port_range);
        $min_port = array_shift($port_range);
        $max_port = array_pop($port_range);

        $host = $redis->getSetting('hosts')[0]['host'];
        
        foreach(range($min_port, $max_port) as $port){
            $cnt = $redis->exec('dbsize', [], $host, $redis->prefix, $port);
            echo "$port: $cnt\n";
        }
    }
    
    function delAll($pat){
        $this->asArr($pat);
        phM('delAll', "$pat*");
        $res = phM('asArr', "$pat*");
        return $res;        
    }

    function testHashing(){
        echo "\n ---- Hash affinity testing start ----\n";

        foreach(['devtestse', 'devtestno', 'devtestca', 'devtestau'] as $uname){
            $uid = uid($uname);

            foreach(range(1, 10) as $num){
                $key = "somekey$num";
                phMsetShard($key, $num, $uid);
                $val = phMgetShard($key, $uid);
                echo empty($val) ? "Value for $key misssing!\n" : "Value for $key: $val\n";
            }

        }
        $res = phM('asArr', '*');
        echo "asArr dump, should not be empty:\n";
        print_r($res);
        phM('dumpClusterKeys');
        echo "\n ---- Hash affinity testing end ----\n";
    }

    function testMpClustering(){
        echo "\n ---- mCluster testing start ----\n";
        $mc = mCluster('mp');
        $arr = ['foo' => 'bar'];
        $mc->setJson('tid-1', $arr, 36000);
        echo "TTL of tid-1: ".$mc->ttl('tid-1')."\n";
        $arr = $mc->getJson('tid-1');
        echo "Arr:\n";
        print_r($arr);
        echo "\n ---- mCluster testing end ----\n";

        echo "\n ---- BoS chat start ----\n";
        $th = phive('Tournament');
        $t = ['id' => 1];
        $chat_msg1 = ['msg' => 'Foo!'];
        $chat_msg2 = ['msg' => 'Bar!'];
        $th->addToChatContents($t, $chat_msg1);
        sleep(1);
        $res = $th->getAllChatMsgs();
        echo "After first chat message:\n";
        print_r($res);
        $th->addToChatContents($t, $chat_msg2);
        $res = $th->getAllChatMsgs();
        echo "After second chat message:\n";
        print_r($res);
        $mc->dumpClusterKeys();
        echo "\n ---- BoS chat end ----\n";
    }

    function testLocalizerCluster(){
        $loc = phive('localizer');
        $mc = mCluster('localizer');
        echo "\n ---- Localizer cluster testing start ----\n";
        foreach($loc->getLanguages() as $lang){
            echo t('your.savings', $lang)."\n";
            echo t('1.month', $lang)."\n";
        }
        $mc->dumpClusterKeys();
        echo "\n ---- Localizer cluster testing end ----\n";
    }

    function setGetShard($key, $uid){
        // default / hosts
        phMsetShard($key, 'abc', $uid);
        $value = phMgetShard($key, $uid);
        if(empty($value)){
            echo "setGetShard value is empty for $key\n";
        }
    }

    function setGetUuid(){
        // pexec
        $uuid    = uuidSet([1, 2, 3]);
        $res     = uuidGet($uuid);
        if(empty($res)){
            echo "setGetUuid value is empty for $uuid\n";
        }
    }

    function testCluster($cluster_key, $key, $value){
        $cluster = mCluster($cluster_key);
        $cluster->set($key, $value);
        $res = $cluster->get($key);
        if(empty($res)){
            echo "testCluster value for the key $key is empty on the $cluster_key";
        }
    }
    
    function stressTest($num_players = 50, $num_secs = 120){
        $this->db  = phive('SQL');
        $players   = [];
        for($i = 0; $i < $num_players; $i++){
            $id = rand(5000001, 5683107);
            //$players[] = $this->db->loadAssoc("SELECT * FROM users WHERE id = $id");
            $players[] = $id;
        }

        //$all_games   = $this->db->loadArray("SELECT * FROM micro_games LIMIT 5");
        //$loc_aliases = $this->db->loadCol("SELECT * FROM localized_strings WHERE language = 'en' ORDER BY alias DESC LIMIT 10", 'alias');
        $all_games = ['an' => ['array' => 'foo'], 'of' => ['games' => 'bar'], ['and', 'so', 'on'], [1,2,3,4,5,6,7,8,9]];
        $loc_aliases = ['your.savings'];
        $keys        = str_split('abcdefghijklmnopqrstuvwxyz');
        
        $smicro = microtime(true);
        for($i = 1; $i <= $num_secs; $i++){
            $smicro   = microtime(true);
            $duration = 0;
            echo "Starting $i test\n";
            foreach($players as $uid){
                echo "Starting with player $uid\n";
                // Pretend to set some default data, with override.
                foreach($keys as $key){
                    $rkey = $uid.$key;
                    // Here we try to roughly simulate how often certain Redis calls are made.
                    $this->setGetShard($rkey.'1', $uid);
                    // qcache
                    phQset($uid.'query', $all_games);
                    $this->setGetShard($rkey.'2', $uid);
                    $games   = phQget($uid.'query');
                    if(empty($games)){
                        echo "Qcache games are empty\n";
                    }
                    // localizer
                    $content = t($loc_aliases[0], 'en');
                    $this->setGetUuid();
                    $this->testCluster('mp', $rkey, 'abc');
                    $this->testCluster('uaccess', $rkey, 'abc');
                    $this->setGetShard($rkey.'2', $uid);
                    $this->setGetUuid();
                    $this->testCluster('uaccess', $rkey, 'abc');
                }
                echo "Ending with player {$uid}\n";
            }
            echo "Ending $i test\n";
           
            //$duration = (microtime(true) - $smicro) * 1000000;
            //if($duration < 1000000)
            //    usleep(1000000 - $duration);
        }

        $emicro = microtime(true);
        $duration = $emicro - $smicro;

        phive()->dumpTbl('redis-stress-test-duration', $duration);
        
    }
    
}
