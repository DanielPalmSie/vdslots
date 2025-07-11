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

$insta 	= new Instadebit();
$insta->verify();
$p 	= $_REQUEST;
phive()->dumpTbl("instadebit_notify_post", $p);
$sql 	= phive('SQL');
$old 	= phive("Cashier")->getDepByExt($p['merchant_txn_num'], 'instadebit');

if($old['status'] == 'disapproved')
    die("ok");

$old['status'] 	= 'disapproved';
$user 		= cu($old['user_id']);
if($user){
    phive()->dumpTbl("instadebit_notify_post", $user);
    $amount = round(100 * $p['txn_amount']);
    //phive("Cashier")->withdrawFromUser($user, $amount + 100, "Instadebit chargeback for deposit ".$old['id'], 9);
    //if($user->getAttr('cash_balance') < 0)
    //    $user->setAttr('cash_balance', 0);
    phive("SQL")->sh($old, 'user_id', 'deposits')->save('deposits', $old);
    phive('Cashier')->chargeback($user, $amount + 100, "Instadebit chargeback for deposit ".$old['id'], true, 'instadebit');
}

echo 'ok';

