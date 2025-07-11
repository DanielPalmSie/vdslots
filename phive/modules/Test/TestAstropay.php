<?php
require_once 'TestCasinoCashier.php';
class TestAstropay extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->db->truncate('trans_log');
    }

    function withdraw($u_obj, $amount, $psp = 'astropay'){
        $pid = phive('Cashier')->insertPendingCommon($u_obj, $amount, [
            'payment_method' => $psp,
            'net_account'    => Mts::getPhone($u_obj, 'astropay'),
        ]);

        $p = $this->c->getPending($pid);
        echo "Withdrawal row:\n";
        print_r($p);
        
        $mts      = Mts::getInstance($psp);
        $currency = $u_obj->getCurrency();
        
        $res = $mts->withdraw($p['id'], $p['user_id'], $p['amount'], '', [
            'phone' => $p['net_account'],
        ]);
        
        $result = $mts->withdrawResult($res);

        echo "Withdrawal call result:\n";
        print_r($result);

        list($mts_id, $ext_id, $result, $msg) = $result;

        $mts_tr = $this->getMtsTr($u_obj, $mts_id);

        phive('Cashier')->approvePending($pid);
        
        return $mts_tr;
    }

    function testNotification($u, $mts_tr, $status = 'CANCELED'){

        $control = sha1('4XeZZg5R9PHKSjy1veG09jS9OpEdCHhV'.$mts_tr['reference_id'].$status);
        
        $body = [
            'x_reference' => $mts_tr['id'],
            'status'      => $status,
            'id_cashout'  => $mts_tr['reference_id'],
            'control'     => $control
        ];

        $body_str = http_build_query($body);
        
        print_r(['sending', $body]);
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=astropay";
        $res = phive()->post($url, $body_str, 'application/x-www-form-urlencoded', '', 'mts-astropay-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $body_str, 'application/x-www-form-urlencoded', '', 'mts-astropay-notification');
        print_r([$res]);
    }

    function testWalletNotification($u, $mts_tr, $status = 'APPROVED', $secret = '4XeZZg5R9PHKSjy1veG09jS9OpEdCHhV'){
        if($mts_tr['type'] == 1){
            // Withdrawal
            $body = [
                'cashout_id'          => uniqid(),
                'merchant_cashout_id' => $mts_tr['id'],
                'cashout_user_id'     => 123456,
                'merchant_user_id'    => '100.'.$mts_tr['user_id'],
                'status'              => $status
            ];
        } else {
            // Deposit
            $body = [
                'deposit_id'          => uniqid(),
                'merchant_deposit_id' => $mts_tr['id'],
                'deposit_user_id'     => 123456,
                'merchant_user_id'    => '100.'.$mts_tr['user_id'],
                'status'              => $status
            ]; 
        }

        $url       = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=astropaywallet";
        $signature = hash_hmac('sha256', json_encode($body), $secret, false);
        $res       = phive()->post($url, $body, 'application/json', ["Signature: $signature"], 'mts-astropaywallet-notification');
        print_r($res);
        $res       = phive()->post($url, $body, 'application/json', ["Signature: $signature"], 'mts-astropaywallet-notification');
        print_r($res);
    }    
}
