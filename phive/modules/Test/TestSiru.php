<?php
require_once 'TestCasinoCashier.php';
class TestSiru extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('siru');
        $this->db->truncate('trans_log');
    }

    function deposit($u, $amount){
        $this->mts->setUser($u);
        $res = $this->mts->deposit($u, $amount, [
            'phone'              => $u->data['mobile'],
            'trans_headline'     => 'Videoslots Deposit',
            'return_success_url' => phive('UserHandler')->getSiteUrl().llink('/cashier/deposit/?siru_end=true'),
            'return_cancel_url'  => phive('UserHandler')->getSiteUrl().llink('/cashier/deposit/?siru_end=true&action=fail'),
            'return_fail_url'    => phive('UserHandler')->getSiteUrl().llink('/cashier/deposit/?siru_end=true&action=fail')
        ]);
        print_r($res);
    }

    function notification($mts_tr, $u, $event = 'success'){
        $to_sign = [
            'siru_uuid'                  => phive()->uuid(),
            'siru_merchantId'            => $this->c->getSetting('siru_mid')[$u->getCountry()],
            'siru_submerchantReference'  => 'abc123',
            'siru_purchaseReference'     => $mts_tr['id'],
            'siru_event'                 => $event
        ];

        $signature                 = hash_hmac('sha512', implode(';', $to_sign), phive('Cashier')->getSetting('siru_secret'));
        $to_sign['siru_signature'] = $signature;        
        $url                       = $this->mts_base_url."user/transfer/deposit/confirm?supplier=siru";
        $res                       = phive()->post($url, $to_sign, 'application/json', '', 'mts-siru-notification');
        print_r($res);
    }

}
