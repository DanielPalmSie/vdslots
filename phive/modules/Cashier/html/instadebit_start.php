<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../Instadebit.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$insta = new Instadebit();

if(!empty($_GET['lang']))
    phive('Localizer')->setLanguage($_GET['lang'], true);

$c 		= phive("Cashier");
$err 	= array();

$user = cu();

setCur($user);

if($_POST['action'] == 'deposit'){

    $c->setReloadCode();

    list($err, $amount) = $c->transferStart($_POST, $user, 'instadebit', 'in');
    //$amount = checkAmount($err, $_POST['amount']);
    $amount = $amount * 100;

    list($res,) = phive('Cashier')->checkOverLimits($user, $amount);
    if($res) {
        $err['amount'] = 'deposits.over.limit.html';
    }

    $user_id = $user->getId();
    $token = uniqid().'_'.$user_id;

    rgLimits()->addPendingDeposit($user, $amount);

    if(empty($err)){
        $c->insertToken($user, $amount, $token);
        die(json_encode(['result' => [
            'form' => [
                'url'  => $c->getSetting('instadebit_url'),
                'fields' =>
                    array_merge([
                        'merchant_txn_num' => $token,
                        'txn_amount'       => $amount / 100,
                        'return_url'       => phive('UserHandler')->getSiteUrl().llink("/cashier/deposit/?instadebit_end=true&token={$token}"),
                    ], $insta->getFormFields($user))
            ]]
        ]));
    }
}

if($_POST['action'] == 'withdraw'){
    list($err, $amount) = $c->transferStart($_POST, $user, 'instadebit', 'out');

    if(!phive('Cashier')->canWithdraw($user, 'instadebit', '')['success']){
        $err['instantdebit'] = 'err.user.not.verified';
    }

    $insta_id = $user->getSetting('instadebit_user_id');

    //$amount = checkAmount($err, $_POST['amount'], true);
    $cents = $amount * 100;

    if(empty($insta_id))
        $err['instadebit_user_id'] = "err.nodeposit";

    if(empty($_SESSION['mg_username']))
        $err['session'] = 'err.nosession';

    /*
    $tmp = phive('Casino')->balances($user);
    if($tmp === false)
      $err['database'] = 'err.unknown';
    else
      $cur_balance = $tmp['cash_balance'] / 100;
    //$cur_balance 	= $user->getAttribute('cash_balance') / 100;

    if(empty($err) && $cur_balance < $amount)
      $err['amount'] = 'err.lowbalance';
    */

    if(empty($err)){

        // TODO henrik remove
        if($c->hasWithdrawnSince('', $user->getId(), '-30 day'))
            $new_amount = $amount - $c->getOutDeduct($cents, 'instadebit', $user);
        else
            $new_amount = $amount;

        //$dbres = phive('QuickFire')->changeBalance($user, -$cents, 'Withdrawal', 8);

        //if($dbres !== false){
        $result = phive('Cashier')->insertPendingCommon($user, $cents, array(
            'payment_method' 	=> 'instadebit',
            'aut_code' 	=> $new_amount * 100));

        //forfeiting the active bonus when time of withdrawal
        if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
            phive('Bonuses')->failDepositBonuses( $user->getId(), "Instadebit withdrawal" );
        }

        if($result === false)
            $err['database'] = 'err.unknown';
        //}else
        //  $err['database'] = 'err.unknown';
    }
}

$translate = "";
foreach($err as $field => $errstr)
    $translate .= t('register.'.$field) . ': ' . t($errstr)."<br>";

die( json_encode(array("errors" => $translate) ) );
