<?php

use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';


if(!empty($_GET['lang'])){
  phive('Localizer')->setLanguage($_GET['lang']);
  phive('Localizer')->setNonSubLang($_GET['lang']);
}

$c 		= phive('Cashier');
$s 		= $_SESSION[ $c->getSetting('session_data') ];
$err 	= array();

$user = cu();

setCur($user);

if($_POST['action'] == 'deposit'){

  $c->setReloadCode();
  
  $amount = checkAmount($err, $_POST['amount']);
  
  $amount = $amount * 100;
  
  if(!phiveApp(PspConfigServiceInterface::class)->checkUpperLimit($user, $amount, 'euteller', PspActionType::IN))
    $err['amount'] = "err.toomuch";

  if(!phiveApp(PspConfigServiceInterface::class)->checkLowerLimit($user, $amount, 'euteller', PspActionType::IN))
    $err['amount'] = "err.toolittle";

  list($res,) = $c->checkOverLimits($user, $amount);
  if($res) {
      $err['amount'] = 'deposits.over.limit.html';
  }

  if(empty($err)){
    rgLimits()->addPendingDeposit($user, $amount);
    $our_debit_amount 	= $amount + phive('Cashier')->getSetting('euteller_player_fee');
    //$in_fee 			= phive('Cashier')->getInFee($our_debit_amount, 'euteller');
    //$debit_amount 		= number_format(($our_debit_amount - $in_fee) / 100, 2);
    $debit_amount 		= number_format($our_debit_amount / 100, 2, '.', '');
    //$debit_amount		= $our_debit_amount / 100;
    $order_id 			= phive('SQL')->nextAutoId('transfer_tokens');
    $security 			= md5($order_id.$c->getSetting('euteller_customer_id').$c->getSetting('euteller_secret').$debit_amount);
    //OrderId+CustomerId+PaymentAmount+Secret
    $stored_security 	= md5($order_id.$c->getSetting('euteller_customer_id').$debit_amount.$c->getSetting('euteller_secret'));
    
    $fields = array(
      'customer' 		=> $c->getSetting('euteller_customer_id'),
      'orderid' 		=> $order_id,
      'amount' 			=> $debit_amount,
      'security' 		=> $security,
      'addfield[username]'	=> $user->getUsername(),
      'addfield[site_type]'	=> $_POST['box_type']
    );

    $c->insertToken($user, $amount, $stored_security);    
    $url = "https://payment.euteller.com/?" . http_build_query($fields);
    die(json_encode(array("url" => $url)));
  }
  
  $translate = "";
  foreach($err as $field => $errstr)
    $translate .= t('register.'.$field) . ': ' . t($errstr)."<br>"; 
  
  if(!empty($_POST['ajax']))
    die( json_encode(array("errors" => $translate) ) );
}

if(!empty($err))
  $GLOBALS['euteller_result'] = array("errors" => $translate);	
