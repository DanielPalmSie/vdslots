<?php
require_once __DIR__ . '/../../../phive.php';

if(!empty($_GET['lang'])){
    phive('Localizer')->setLanguage($_GET['lang'], true);
}

if($_POST['action'] != 'withdraw'){
    $handler = phive('Cashier/DepositStart');
    $args    = [$_POST['action'], '', $_POST];
} else {
    $handler = phive('Cashier/WithdrawStart');
    $args    = ['', $_POST];
}

$res = $handler->init();
if($res !== true){
    // Showstopping error string.
    $handler->failStop($res);
}

$res = call_user_func_array([$handler, 'execute'], $args);

if(is_string($res)){
    // Showstopping error string.
    $handler->failStop($res);
} elseif(is_array($res) && empty($res['success']) && empty($res['failover'])) {
    $handler->failStop($res);
}

$handler->stop($res);
