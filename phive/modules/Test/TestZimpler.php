<?php
require_once 'TestCasinoCashier.php';
class TestZimpler extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('zimpler');
        $this->db->truncate('trans_log');
    }

    function getExtra($u){
        return [
            'nid'               => $u->getNid(),
            'site'              => phive()->getSetting('domain'),
            'site_display_name' => phive()->getSetting('domain')
        ];
        
    }
    
    function deposit($u, $amount, $sub_supplier = 'zimpler'){
        $this->mts->setUser($u);

        $zimpler_extra = $this->getExtra($u);

        $zimpler_extra['payment_methods'] = $sub_supplier == 'zimplerbank' ? 'bank' : 'bill';

        $res = $this->mts->deposit($u, $amount, $zimpler_extra);
        
        print_r(['deposit_result' => $res]);
    }

    function notification($mts_tr, $u, $state = "authorized"){
        $params = [
            'ref'     => $mts_tr['id'],
            'state'   => $state,
            'id'      => uniqid(),
            'user_id' => $u->getId(),
            'kyc_info' => [
                'first_name' => $u->data['firstname'],
                'last_name' => $u->data['lastname'],
                'national_identification_number' => "19440512-4030",
                'country_code' => $u->getCountry(),
                'user_id' => uniqid()
            ],
        ];

        if($u->getCountry() == 'SE'){
            $bank_account = [
                'clearing_number' => '5356',
                'account_number' => '123456789',
                'type' => 'national-SE'
            ];
        } else {
            $bank_account = [
                'account_number' => $u->getCountry().'00000123456789',
                'type' => 'iban'
            ]; 
        }

        $params['kyc_info']['bank_account'] = $bank_account;
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=zimpler";
        $res = phive()->post($url, $params, 'application/json', '', 'mts-zimpler-notification');
        print_r(['notification_result' => $res]);
    }

    function withdraw($user, $cents){
        $err = [];

        $insert = [
            'payment_method' => 'zimpler',
            'amount'         => $cents,
            'currency'       => $user->getCurrency(),
            'bank_name'      => 'SEB'
        ];
        
        $pid = phive('Cashier')->insertInitiatedWithdrawal($user, $cents, $insert);
        if(!empty($pid)){
            Mts::getInstance('zimpler', $user)->doBankWithdrawalWithRedirect($err, $user, $cents, $pid, $this->getExtra($user));
        }
        
        // Mts::getInstance('zimpler', $user)->doBankWithdrawalWithRedirect($err, $user, $cents, array_merge($this->getExtra($user), ['iban' => $iban]));
        print_r(['withdraw_error' => $err]);
    }

    function approveWithdrawal($u){
        $p = $this->getLatestPending($u);
        print_r($p);
        $res = Mts::getInstance($p['payment_method'])->transferRpc('approveWithdrawal', [
            'transaction_id'          => $p['mts_id'],
            'customer_transaction_id' => $p['id'],
            'user_id'                 => $p['user_id'],
            'reference_id'            => $p['ext_id'],
            'actor_id'                => 1234
        ]);
        print_r(['withdraw_approve_result' => $res]);
    }    

}
