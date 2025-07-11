<?php
require_once '/var/www/videoslots/phive/phive.php';
if(!isCli())
    exit;
$sql = phive('SQL')->doDb('clog');  
$res = $sql->loadArray("select * from entry");

$srev     = $_SERVER['argv'][1];
$erev     = $_SERVER['argv'][2];
$branch   = $_SERVER['argv'][3];
if(empty($branch))
    $branch = 'default';
$repo    = $_SERVER['argv'][4];
$release = $_SERVER['argv'][5]; 
$str   = shell_exec("hg log -r$srev:$erev --only-branch $branch --template 'branch^{branch}&rev^{node|short}&author^{author}&descr^{desc}&date^{date|isodate}&files^{files}\n'");
$arr   = [];
$lines = explode("\n", $str);
array_pop($lines);
//print_r($lines);
foreach($lines as $line){
    $res = [];
    $res['todo']['author'] = 'test';
    foreach(explode('&', $line) as $tmp){
        list($key, $value) = explode('^', $tmp);
        if($key == 'descr'){
            $cmds = explode('#', $value);
            if(count($cmds) > 0){
                $value = array_shift($cmds);
                $type        = explode('|', array_shift($cmds));
                $res['type'] = trim(array_shift($type));
                if($res['type'] == 'ignore'){
                    continue 2;
                }
                $status      = explode('|', array_shift($cmds));
                if(count($type) > 0){
                    unset($res['todo']['author']);
                    if(array_shift($status) == 'status'){
                        while($person = trim(array_shift($type)))
                            $res['todo'][$person] = array_shift($status);
                    }
                }
            }
        }
        $res[$key] = $value;
    }
    $arr[] = $res;
}

foreach($arr as &$change){
    if(!empty($change['todo']['author'])){
        $change['todo'][$change['author']] = $change['todo']['author'];
        unset($change['todo']['author']);
    }    
}


reset($arr);

foreach($arr as $row){
    $str   = '';
    $todo = '';
    $tester = '';
    $status = '';
    foreach($row['todo'] as $tester => $status)
        $str .= '- '.ucfirst($tester).': '.$status."\n";
    $row['todo'] = $str;
    $row['repo'] = $repo;
    $row['crelease'] = $release;
    //print_r($row);
    $old = $sql->loadAssoc("SELECT * FROM entry WHERE rev = '{$row['rev']}'");
    if(!empty($old))
        continue;
    $sql->save('entry', $row);
}



//*
$str   = '';
$arr = $sql->loadArray("SELECT todo, branch, date, rev, author, descr, amendment, repo, crelease, status FROM entry WHERE crelease = '$release' AND status != 'ignore'");
foreach($arr as $ch){
    foreach($ch as $key => $value){
        if($key == 'todo'){
            $str .= "\n".ucfirst($key).': '.$value;
        }else
        $str .= ucfirst($key).': '.$value."\n";
    }
    $str .= "\n\n";
}
//*/

echo $str;

file_put_contents("change_log_{$branch}.txt", $str);

// srev erev branch repo release

// phive
// php hg_change_log.php 11877 12084 July2017 phive July2017

// BO
// php ./../videoslots/phive/utils/hg_change_log.php 1661 1755 June2017 BO June2017

// diamondbet
//php ./../phive/utils/hg_change_log.php 2485 2523 June2017 videoslots June2017

//print_r($arr);
//file_put_contents('changelog.txt', $str);
//echo $str;

