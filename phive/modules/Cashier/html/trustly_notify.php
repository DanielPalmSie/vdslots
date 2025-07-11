<?php
require_once __DIR__ . '/../../../api.php';
require_once(__DIR__ . "/../Trustly.php");

/** @var CasinoCashier $c */
$c = phive("Cashier");

$input 	= file_get_contents("php://input");
if(empty($input))
  die('nok');

phive()->dumpTbl('trustly_notify_json', $input);
$req 	= json_decode($input);
$data 	= $req->params->data;
$uuid 	= $req->params->uuid;

if(!Trustly::verify($req->method, $uuid, $data, $req->params->signature)){
    phive()->dumpTbl('trustly_err', $req);
  exit;
}


//$req = json_decode('{"params":{"signature":"cfX8+HhbhRqD78izQjtfO4QJ+nP6f+IHi/kOnzSkrZwVir65bvjHnrXVxpayeixfxjBVeCqpmoWrBNQvE1pviUGOueSk+L8ejppjfWtM7E9JeltoyAbjbEuB3CTT5WVZCppNRXZP7GlF3GwO3digg5/oOBRgkY1I3mUIEtw67TsaL8eejtXPT/u98pFl3tORDfb044lp2zPa07am6ZCUSZFdfxQpD9o/eDt+RwJqiuD0ndvKGksyfGztBkQEm5RcZDu+uP77eVuxM1Opj5TTQ9nyFYRM0b5fmHN07IrrF7QGz7RBtgLFpcHVMotkdPTju6OLm84NMDC8gWdCH3wAXg==","data":{"currency":"EUR","amount":"100.000000000000000001357300000000000000000000","timestamp":"2012-10-14 17:03:44.550921+00","enduserid":"hsarvell","notificationid":"1327704785","orderid":"2872587865","messageid":"d7005824"},"uuid":"f4d4f269-c743-4972-8f4b-fb80e25da5cf"},"version":"1.1","method":"credit"}');

$sql = phive('SQL');

//$data 	= $req->params->data;
//$uuid 	= $req->params->uuid;

if($req->method == 'account'){
    $method   = 'account';
    $data_obj = array('status' => 'OK');
    $attrs    = $data->attributes;
    $where_dep = " ext_id = '{$data->orderid}' AND dep_type = 'trustly'";
    $d	      = $sql->shs('merge', '', null, 'deposits')->loadAssoc('', 'deposits', $where_dep);
    $p        = $sql->shs('merge', '', null, 'pending_withdrawals')->loadAssoc('', 'pending_withdrawals', " ext_id = '{$data->orderid}' AND payment_method = 'trustly'");

    $disp_name  = $attrs->bank;
    $scheme     = strtolower($attrs->bank);
    $acc_num    = $attrs->descriptor;

    //The account call came before the credit call we wait 20 seconds before we update
    if(empty($d) && empty($p)){  //Temporal fix, if we don't have the deposit or the wd in the database we reply with fail so they retry
        phive()->dumpTbl('trustly-not-found-on-acc', $req);
        $data_obj['status'] = 'FAILED';
        /*
        $q = "UPDATE deposits SET display_name = '$disp_name', scheme = '$scheme', card_hash = '$acc_num' WHERE $where_dep";
        if($sql->isSharded('deposits'))
            phive()->pexec('SQL', 'pShard', [$d['user_id'], 'query', $q], 20000000, true);
        else
            phive()->pexec('SQL', 'query', [$q], 20000000, true);
        */
    }

    $nid_extra = substr($attrs->personid, -4);
    if(!empty($d)){
        $d['display_name'] = $disp_name;
        $d['scheme']       = $scheme;
        $d['card_hash']    = $acc_num;
        $u                 = cu($d['user_id']);

        if(in_array(strtolower($scheme), ['seb', 'swedbank', 'sparbanken']))
            $u->setSetting('hide-trustly-withdraw', 1);

        $sql->sh($d, 'user_id', 'deposits')->save('deposits', $d);
        phive()->pexec('Cashier/Arf', 'invoke', ['bankReceiverCheck', $u->getId(), "'{$data->attributes->name}'", 'trustly']);
    }else if(!empty($p)){
        $p['bank_name'] = $disp_name;
        $u              = cu($p['user_id']);
        $sql->sh($p, 'user_id', 'pending_withdrawals')->save('pending_withdrawals', $p);

    }
    if(!empty($u)) {
        $u->setSetting('nid_extra', $nid_extra);
    } else { //Getting the user id from the token in case we don't have the deposit or the withdrawal yet
        $t_query = "FROM transfer_tokens WHERE security = 'trustly{$data->orderid}'";
        $token = $sql->loadAssoc("SELECT * {$t_query}");
        phive()->dumpTbl('trustly-account', $token);
        if (!empty($token)) {
            $u = cu($token['user_id']);
            if (strpos($data->messageid, 'w') !== false) {
                $sql->query("DELETE {$t_query}");
            }
        }
    }

    $c->fraud->checkTrustlyCountryFlag($u, $data->attributes->clearinghouse);
}

if($req->method == 'debit'){

  $method		= 'debit';

  $d = $sql->sh($data->enduserid)->loadAssoc('', 'deposits', " ext_id = '{$data->orderid}' AND dep_type = 'trustly'");

  if(is_numeric($data->enduserid))
    $user = cu($data->enduserid);
  else
    $user = phive('UserHandler')->getUserByUsername($data->enduserid);

  phive()->dumpTbl('trustly_debit', $req, $user);

  if(empty($d) && is_object($user)){
      //forfeiting the active bonus when time of withdrawal
      if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
           phive('Bonuses')->failDepositBonuses( $user->getId(), "Trustly withdrawal" );
      }
    phive()->dumpTbl('trustly_failed_bonuses', '', $user);

    setCur($user);
    $err 	= array();
    $current_balance = $user->getBalance();
    if($current_balance === false)
      $err['database'] = 'err.unknown';

    phive()->dumpTbl('trustly_got_balances', '', $user);

    if(empty($err)){
      $amount 	= round($data->amount, 2);


      list($err, $amount) = phive('Cashier')->transferStart($_POST, $user, 'trustly', 'out', $amount, false, '', '', '', false);

      $cents = $amount * 100;

      $ded_amount = round(phive('Cashier')->getOutDeduct($cents, 'trustly', $user, false));


      //if($c->hasWithdrawnSince('', $user->getId(), '-30 day'))
      //	$total_amount = round($cents * 1.04058);
      //else
      $total_amount = $cents + $ded_amount;

      if($total_amount > $current_balance)
	$err['amount'] = 'err.lowbalance';

      if(empty($err)){
        if(!empty($ded_amount))
	  phive('QuickFire')->changeBalance($user, -$ded_amount, 'Withdrawal Deduction', 50);

          //$dbres = phive('QuickFire')->changeBalance($user, -$cents, 'Withdrawal', 8);

        phive()->dumpTbl('trustly_changed_balances', '', $user);

	//if($dbres !== false){
          $dbres = phive('Cashier')->insertPendingCommon($user, $cents, array(
	    'loc_id' 		=> $data->messageid,
	    'ext_id' 		=> $data->orderid,
	    'payment_method' 	=> 'trustly',
	    'deducted_amount'	=> $ded_amount,
	    'real_cost'	        => $c->getOutFee($cents, 'trustly', $user)
	  ));
	  $data_obj = array('status' => 'OK');
	//}

        phive()->dumpTbl('trustly_inserted_pending', '', $user);

      }else
        phive()->dumpTbl('trustly_debit_err', $err, $user);
    }else
      phive()->dumpTbl('trustly_debit_err', $err, $user);
  }

  if(empty($data_obj))
    $data_obj = array('status' => 'FAILED');
}

if($req->method == 'credit'){
  $method		= 'credit';
  $user = cu($data->enduserid);

  setCur($user);
  phive()->dumpTbl('trustly_credit', $req, $user);

  if(strpos($data->messageid, 'w') !== false){
    //$sql, $tbl = '', $where = ''
      $p		= $sql->sh($user, 'id')->loadAssoc('', 'pending_withdrawals', " ext_id = '{$data->orderid}' AND payment_method = 'trustly'");
      phive()->dumpTbl('trustly_credit_pending', $p, $user);
    if($p['status'] == 'disapproved')
      $res 	= 'OK';
    else{
      $res 	= phive('Cashier')->disapprovePending($p, false, true, true, (int)$p['deducted_amount'], true) == true ? 'OK' : 'FAILURE';
    }
    $data_obj 	= array('status' => $res);
  }else{
    $where 	= " WHERE security = 'trustly{$data->orderid}' ";
    $tstring	= "SELECT * FROM transfer_tokens $where";
    phive()->dumpTbl('trustly_token_sql', $tstring, $user);
    $token 	= $sql->loadAssoc($tstring);

    $extra_info = json_decode($token['ext_info'], true);
    if(!empty($extra_info['sub_type']) && $extra_info['sub_type'] == "ideal"){
        $scheme = 'ideal';
    } else {
        $scheme = phive()->subtractTimes(phive()->hisNow(), $token['created_at'], 'h') > 2 ? 'normal' : 'instant';
    }

    if(!empty($token)){
      list($err, $amount) = phive('Cashier')->transferStart($_POST, $user, 'trustly', 'in', $data->amount, false);
      $cents 	= $amount * 100;
      if(empty($err)){
	$user->setSetting('has_trustly', 'yes');

	$sql->query("DELETE FROM transfer_tokens $where");
	$depres = phive('QuickFire')->depositCash($user, $cents, 'trustly', $data->orderid, $scheme);
        phive('Dmapi')->createEmptyDocument($user->getId(), 'trustly', $scheme);

        if($depres !== false){
	  $data_obj = array('status' => 'OK');
        }else
	  $data_obj = array('status' => 'FAILED');
      }else{
	$data_obj = array('status' => 'FAILED');
	phive()->dumpTbl('trustly_credit_err', $err, $user);
      }
    }else{
      $data_obj = array('status' => 'OK');
      phive()->dumpTbl('trustly_fail', $req, $user);
    }
  }
}

$signature = Trustly::sign($method, $uuid, $data_obj);

$ret_obj = array(
  'version' 	=> 1.1,
  'result'	=> array(
    'signature' => $signature,
    'uuid'	=> $uuid,
    'method'	=> $method,
    'data'	=> $data_obj));

phive()->dumpTbl('trustly_ret_obj', json_encode($ret_obj), $user);

die(json_encode($ret_obj));

