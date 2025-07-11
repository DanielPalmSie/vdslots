<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

if(!empty($_POST['lang'])){
  phive('Localizer')->setLanguage($_POST['lang']);
  phive('Localizer')->setNonSubLang($_POST['lang']);
}

$c 		= phive('Cashier');
$s 		= $_SESSION[ $c->getSetting('session_data') ];
$err 		= array();
$deposit_bonus 	= '';

$user = cuPl();

if($_POST['action'] == 'withdraw'){
    
    $supplier = empty($_POST['supplier']) ? 'bank' : phive()->rmNonAlphaNums($_POST['supplier']);
  
  list($err, $amount) = $c->transferStart($user, $supplier, 'out');
  //$amount 		= checkAmount($err, $_POST['amount'], true);
  $cents = $amount * 100;
  
    //if(!$user->isVerified())
    //$err['verified'] = "err.user.not.verified";	
  
  // validate only here
  if ($supplier != 'citadel') {
    $cols = array('bank_city', 'swift_bic');

    foreach ($cols as $col) {
      if (empty($_POST[$col]))
        $err[$col] = "err.empty";
    }
  }

  // this fields is used to fill database
  $cols = array('bank_name', 'bank_address', 'bank_city', 'swift_bic');
  
  if (empty($_POST['iban']) && empty($_POST['bank_account_number']))
    $err['acc_iban'] = "err.empty";
  
  if(empty($_SESSION['mg_username']))
    $err['session'] = 'err.nosession';	

  if(empty($err)){
    $cols = array_merge($cols, array('iban', 'bank_account_number', 'bank_code', 'bank_clearnr', 'bank_name'));
    $ud = $user->data;
    $insert = array('bank_receiver' => "{$ud['firstname']} {$ud['lastname']}", 'bank_country' => $ud['country']);
    foreach($cols as $col)
      $insert[$col] = isset($_POST[$col]) ? $_POST[$col] : '';
    
        if($supplier == 'citadel' && !$c->supplierIsBank('citadel')) {
            $new_amount         = $cents - $c->getOutDeduct($cents, 'citadel', $user);
            $insert['aut_code'] = $new_amount;
        }else{
            $insert['aut_code'] = $cents;
        }
        
      $result = phive('Cashier')->insertPendingBank($user, $cents, $insert, $supplier);
      if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
          phive('Bonuses')->failDepositBonuses( $user->getId(), "Bank withdrawal" );
      }
      if($result === false)
	$err['database'] = 'err.unknown';
      else
	$new_balance = $cur_balance - $amount;	
      
  }
}

$translate = "";
foreach($err as $field => $errstr)
  $translate .= t('register.'.$field) . ': ' . t($errstr)."<br>"; 

die( json_encode(array("error" => $translate, "new_balance" => $new_balance) ) );
