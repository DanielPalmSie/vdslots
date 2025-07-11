<?php
require_once 'TestCasinoCashier.php';
class TestSiirto extends TestCasinoCashier{

    function deposit($u, $amount, $supplier = 'siirto'){
        $res = $this->mtsDeposit($u, $amount, $supplier, ['accountid' => $u->getMobile()]);
        
        /*
        $res = $this->mtsDeposit($u, $amount, 'neteller', ['email' => $email]);
        print_r($res);
        */
    }

    
    function withdraw($u, $amount, $email = 'netellertest_EUR@neteller.com', $client_id = '908379'){

        /*
        $insert = ['net_account' => $email];
        $p_id = phive('Cashier')->insertPendingCommon($u, $amount, $insert);

        $p = $this->c->getPending($p_id);

        $p['status'] = 'pending';
        $this->db->sh($u, 'id')->save('pending_withdrawals', $p);

        echo "Paying first withdrawal\n";
        $this->c->payPending($p_id, $amount);

        // We can't use payPending here as it will try and prevent idempotency by way of pending status, we
        // get past that so we can test the MTS idempotency protection.
        echo "Paying second withdrawal with same id\n";        
        $mts = Mts::getInstance(Supplier::Neteller);
        $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
            'email' => $p['net_account'],
            'currency' => $p['currency']
        ]);
        
        list($mts_id, $ext_id, $result) = $mts->withdrawResult($res);
        $mts_db = phive('SQL')->doDb('mts');
        $res = $mts_db->loadArray("SELECT * FROM transactions WHERE customer_transaction_id = $p_id");
        print_r($res);
        */
    }

    function sendEutellerDepositWebHook($u, $mts_tr){

        $url = 'http://mts.videoslots.loc/api/0.1/user/transfer/deposit/confirm?supplier=euteller';
        
        $params = [
            'orderid' => $mts_tr['id'],
            'bankref' => 11122211
        ];

        phive()->post($url, $params, 'application/json', '', 'euteller-webhook');
        
        $params = [
            'method'             => 'kyc_data',
            'customer'           => 'Video_slots_test',
            'merchant_reference' => $mts_tr['id'],
            'last_update'        => 'abc'
        ];

        $str = '';
        foreach($params as $value){
            $str .= $value;
        }

        $params['status_text'] = 'PAID';

        $kyc = [
            'account_owner' => $u->getFullName(),
            'iban_hashed'   => 'EUTxxxxx',
            'iban_masked'   => 'FI1234XXXXXXXX5678',
        ];

        foreach($kyc as $value){
            $str .= $value;
        }

        $kyc['bank_name'] = 'Nordea';

        $params['kyc'] = $kyc;
        
        $params['security'] = hash('sha256', $str.'x3c65hKG8Vj3eiMfMK75nMc7kw');

        return phive()->post($url, $params, 'application/json', '', 'euteller-webhook');
    }

    function sendEutellerWithdrawWebHook($u, $mts_tr){
        $params = [
            'data[status]'        => 140,
            'data[customer]'      => 'Video_slots_test',
            'data[transactionid]' => $mts_tr['id'],
            'request_timestamp'   => phive()->hisNow()
        ];

        $str = implode('&', $params);
        
        $params['security'] = hash('sha256', $str.'&jtey4fyt4r1rizrb7ximko');
        
        $params = http_build_query($params);
        
        $url = 'http://mts.videoslots.loc/api/0.1/user/transfer/deposit/confirm?supplier=euteller';

        return phive()->post($url, $params, 'application/x-www-form-urlencoded', '', 'euteller-webhook');
    }

    function sendWebHook($u, $mts_tr){

        $params = [
            'orderid'  => $mts_tr['id'],
            'siirto'   => 'siirto',
            'customer' => 'Video_slots_test',
            'amount'   => 20.00
        ];

        $str = '';
        foreach($params as $value){
            $str .= $value;
        }

        $params['security'] = hash('sha256', $str.'x3c65hKG8Vj3eiMfMK75nMc7kw');

        $params['bankReference'] = uniqid();
        $params['state_text'] = 'PAID';
        
        $body = http_build_query($params);

        $url = 'http://mts.videoslots.loc/api/0.1/user/transfer/deposit/confirm?supplier=siirto';
        
        return phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'siirto-webhook');
        
        
        /*
        $payload = [
            'merchantRefNum' => $mts_tr['id'],
            'id'             => phive()->uuid()
        ];

        $body = [
            'eventType' => $event_type,
            'payload'   => $payload
        ];

        $url = 'http://mts.videoslots.loc/api/0.1/user/transfer/deposit/confirm?supplier=neteller';
        
        return phive()->post($url, $body, 'application/json', '', 'neteller-webhook');
        */
    }
    

    
}
