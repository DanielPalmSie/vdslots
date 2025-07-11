<?php
require_once __DIR__ . '/../../../phive.php';

/**
 * Redirecting Instadebit notifications to the MTS new endpoint
 *
 * After releasing the Instadebit new integration and updating notification endpoints from Instadebit side,
 * we can remove this file
 */
if (phive("Cashier")->getSetting('instadebit_via_mts')) {
    $mtsSettings = phive('Cashier')->getSetting('mts');
    $url = str_replace('/api/0.1', '/api/1.0', $mtsSettings['base_url']) . 'notification/instadebit';
    $params = [
        'http' => [
            'method' => 'POST',
            'content' => http_build_query($_POST)
        ]
    ];

    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', false, $ctx);

    if ($fp) {
        echo @stream_get_contents($fp);
    } else {
        echo "Error loading $url";
        http_response_code(500);
    }

    exit;
}

require_once(__DIR__ . "/../Instadebit.php");
$insta = new Instadebit();
$insta->verify();
$p 	= $_POST;
phive()->dumpTbl("instadebit_nudge_post", $p);
$sql 	= phive('SQL');
$token 	= phive("Cashier")->getTokenRow($p['merchant_txn_num']);
if(!empty($token)){
  $user	= cu($token['user_id']);

  if (empty($p['user_id'])) {
      phive()->dumpTbl("instadebit_nudge_user_error", $p, $user);
  }

  // TODO: This file is no longer used and will be addressed in the Instadebit cleanup story.
  phive('Cashier/Fraud')->instadebitFraudChecks(uid($user), $p['user_id']);

  $amount = round(100 * $p['txn_amount']);
  if(!empty($amount)){
    $old 		        = phive("Cashier")->getDepByExt($p['merchant_txn_num'], 'instadebit');
    if(empty($old)){
        if($p['txn_status'] == 'S'){
            $res = phive('QuickFire')->depositCash($user, $amount, 'instadebit', $p['merchant_txn_num'], '', '', '', false, 'approved', null, 0, '', 0, true, ['instadebit_user_id' => $p['user_id'] ?? '']);

            if (!in_array($res, ['no_user', 'deposit_exists', false])) {
                phive('Dmapi')->createEmptyDocument($user->getId(), 'instadebit');
            }
        }
      else
        $res = true;

      if($res === false)
        die('ok');

    }
    $sql->query("DELETE FROM transfer_tokens WHERE security = '{$p['merchant_txn_num']}'");
  }else
    phive()->dumpTbl('instadebit_nudge_err', $p, $user);
}else
  phive()->dumpTbl('instadebit_nudge_no_trans_token', $p);



echo "OK";


