<?php
require_once 'TestCasinoCashier.php';
class TestNeteller extends TestCasinoCashier{

    function deposit($u, $amount, $email = 'netellertest_EUR@neteller.com'){
        $res = $this->mtsDeposit($u, $amount, 'neteller', ['email' => $email]);
        print_r($res);
    }

    function withdraw($u, $amount, $email = 'netellertest_EUR@neteller.com', $client_id = '908379'){
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
    }


    function sendWebHook($mts_tr, $event_type = 'PAYMENT_HANDLE_PAYABLE'){
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
    }
    

    
}
