<?php
require_once 'TestCasinoCashier.php';
class TestInstadebit extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('instadebit');
        $this->db->truncate('trans_log');
    }

    function deposit($u, $amount){
        $this->mts->setUser($u);
        $res = $this->mts->deposit($u, $amount);
        print_r($res);
    }

    function withdraw($user, $cents, $insert_pending = true){
        $this->mts->setUser($user);
        if($insert_pending){
            $this->c->insertPendingCommon($user, $cents, [
                'payment_method'  => 'instadebit',
                'aut_code'        => $cents
            ]);
        }
        $p   = $this->getLatestPending($user);
        $res = $this->mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, ['currency' => $p['currency']]);
        print_r($res);
    }

    function notification($mts_tr, $u, $status = 'S', $action = ''){
        $params = [
            'merchant_txn_num' => $mts_tr['id'],
            'txn_num'          => uniqid(),
            'txn_status'       => $status,
            'user_id'          => $u->data['firstname'].'-'.$u->data['lastname']
        ];
        
        $url     = $this->mts_base_url."user/transfer/deposit/confirm?supplier=instadebit";
        if(!empty($action)){
            $url .= "&action=$action";
        }
        $content = http_build_query($params);
        $res     = phive()->post($url, $content, 'application/x-www-form-urlencoded', '', 'mts-instadebit-notification');
        print_r($res);
    }

    function failDeposit($mts_tr, $u){
        $this->notification($mts_tr, $u, 'C', 'notify');
    }
    
    
}
