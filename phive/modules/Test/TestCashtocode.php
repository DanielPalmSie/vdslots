<?php
require_once 'TestCasinoCashier.php';
class TestCashtocode extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('cashtocode');
        $this->db->truncate('trans_log');
    }

    function deposit($u, $amount){
        $this->mts->setUser($u);
        $extra = $this->mts->getDepositBaseParams($u, $amount);
        $res = $this->mts->deposit($u, $amount, $extra);
        print_r($res);
    }

    function notification($mts_tr, $u, $amount = null){
        $attrs          = $u->data;
        $attrs['tx_id'] = $mts_tr['id'];

        $headers = ['Authorization: Basic ' . base64_encode('test:test')];
        
        $body = [
            'foreignTransactionId'  => $mts_tr['id'],
            'amount'                => ($amount ?? $mts_tr['amount'])  / 100,
            'currency'              => $u->getCurrency(),
            'status'                => 'PENDING'
        ];

        print_r(['sending', $body]);
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=cashtocode";
        $res = phive()->post($url, $body, 'application/json', $headers, 'mts-cashtocode-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $body, 'application/json', $headers, 'mts-cashtocode-notification');
        print_r([$res]);
    }    
}
