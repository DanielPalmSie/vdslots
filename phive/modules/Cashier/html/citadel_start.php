<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

if(!empty($_GET['lang']))
    phive('Localizer')->setLanguage($_GET['lang'], true);

$c 		= phive('Cashier');
$err 	= array();

$user = cu();

setCur($user);

if ($_POST['action'] == 'deposit') {
    //$c->setReloadCode();

    list($err, $amount) = $c->transferStart($user, 'citadel', 'in');
    if (!empty($err)) {
        return retJsonOrDie('amount', 'err.toolittle', true);
    }

    $amount = $_POST['amount'];

    list($res,) = $c->checkOverLimits($user, $amount * 100);
    if ($res) {
        return retJsonOrDie('amount', 'deposits.over.limit.html', true);
    }

    $mts = new Mts(Supplier::Citadel);

    $result = $mts->deposit($user, $amount * 100);

    die(json_encode($result));
}

$translate = "";
foreach($err as $field => $errstr)
    $translate .= t('register.'.$field) . ': ' . t($errstr)."<br>";

die( json_encode(array("error" => $translate) ) );
