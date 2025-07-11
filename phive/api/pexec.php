<?php
require_once __DIR__ . '/../phive.php';
if(!isCli())
    exit;
$script = array_shift($_SERVER['argv']);
$sleep 	= array_shift($_SERVER['argv']);
if(strpos($sleep, '-') !== false){
    $tmp       = explode('-', $sleep);
    $sleep     = (int)$tmp[0];
    $exec_time = (int)$tmp[1];
}
usleep((int)$sleep);
$module = array_shift($_SERVER['argv']);
$func 	= array_shift($_SERVER['argv']);

$key = $_SERVER['argv'][0];

if(phive('Redis')->keyIsPartitioned($key)){
    // We have a Redis partitioned key
    $args = mCluster('pexec')->getJson($key);
}else{
    //Do we have the args stored in Redis with an uuid?
    $args = uuidGet($key);
}

if($args === false){
    $args = $_SERVER['argv'];
    $args = array_map(function($arg){ return $arg == 'na' ? '' : $arg;  }, $args);
}else
    $channel = $_SERVER['argv'][1]; //Not ideal but currently works only with the uuid logic, this shouldn't be a requirement

// If execution timeout has not been explicitly passed in.
if(!isset($exec_time)){
    // We get the configured pexec timeout
    $config_exec_time = phive()->getSetting('pexec_timeout');
    // If it is not set we default to 65
    if($config_exec_time === null)
        $config_exec_time = 65;
    // If we have a channel (ie we expect a result) we default to infinite.
    $exec_time = empty($channel) ? $config_exec_time : 0;
}

$GLOBALS['is_forked'] = true;

if(!empty($exec_time))
    ini_set('max_execution_time', $exec_time);

if(strpos($module, 'Test:') === 0){
    require_once __DIR__ . '/../modules/Test/TestPhive.php';
    list($na, $test_module) = explode(':', $module);
    $obj = TestPhive::getModule($test_module);
    $res = call_user_func_array([$obj, $func], $args);    
}else if($module != 'na')
    $res = phive()->apply($module, $func, $args);
else
    $res = call_user_func_array($func, $args);

if(!empty($channel)){

    //$str = json_encode($res);
    //$debug = substr($str, 0, 20);
    //error_log("Published to: $channel, content: ".$debug);

    phM('lpush', "rchannel".$channel, json_encode($res));
    //phive()->rmqPublish($channel, $debug);
    phM('publish', $channel, 'ok');
}
