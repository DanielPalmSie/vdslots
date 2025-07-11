<?php
require_once __DIR__ . '/../../../phive.php';
require_once(__DIR__ . "/../Trustly.php");

use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

$loc 	= phive('Localizer');
$c 		= phive('Cashier');

if(!empty($_GET['lang']))
  $loc->setLanguage($_GET['lang'], true);

$user 	= cu();

if(!is_object($user))
  exit;

$data_obj = $c->trustlyInit($user);

if($_POST['action'] == 'deposit'){
    list($err, $amount) = $c->transferStart($_POST, $user, 'trustly', PspActionType::IN);

    list($res,) = $c->checkOverLimits($user, $amount * 100);
    if($res) {
        $err['trustly.error'] = 'deposits.over.limit.html';
    }
    
    if(empty($err)){

        // If we Trustly via Adyen flag is turned on AND the player is not in the country list we want to force Trustly standalone for.
        //if($c->trustlyViaAdyen($user))
        //    Mts::adyenBankStart('trustly', $user, $amount);
        
        rgLimits()->addPendingDeposit($user, $amount * 100);
        $data_obj['Attributes']['Amount'] = phive()->decimal($amount);
        $data_obj['MessageID'] = 'd'.uniqid();
        $result 	= Trustly::api('Deposit', $data_obj);
        if(!empty($result->result->data->orderid)){
            $c->insertToken($user, $amount * 100, "trustly{$result->result->data->orderid}");
            die(json_encode(array('url' => $result->result->data->url)));
        }

        if(!empty($result->error))
            $err['trustly.error'] = $result->error;
        else
            $err['trustly.error'] = 'trustly.unknown.error';
    }

    $c->transferEnd($err);
}

if($_POST['action'] == 'withdraw'){
    // We don't care about the data returned or any errors as we're just interested in inserting bank accounts into the dmapi.
    phive('Dmapi')->createEmptyBankDocument($user, $_POST, 'trustly');
    $c->trustlyStartWithdraw($user, $data_obj);
    
}
