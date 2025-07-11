<?php
require_once 'TestCasinoCashier.php';
class TestPaymentiq extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->db->truncate('trans_log');
    }

    function testPaymentiqWithdrawal($u_obj, $scheme = 'venuspoint', $amount = 10000){

        if($scheme == 'venuspoint'){
            $insert = [
                'payment_method' => 'paymentiq',
                'net_account'    => 'U1234',
                'net_email'      => 'MTIz'
            ];
        }

        if($scheme == 'interac'){
            $insert = [
                'bank_code'           => '664',
                'bank_clearnr'        => '1234',
                'bank_account_number' => '567896987'
            ];
        }

        $insert['scheme'] = $scheme;
        $insert['payment_method'] = 'paymentiq';
        $insert['aut_code'] = $amount;
        
        $pid = phive('Cashier')->insertPendingCommon($u_obj, $amount, $insert);

        $p            = $this->c->getPending($pid);

        echo "Withdrawal row:\n";
        print_r($p);        
        
        $mts          = Mts::getInstance(Supplier::Paymentiq);
        $piq_amount   = $p['aut_code'];
        $piq_currency = $u_obj->getCurrency();
        
        if($p['scheme'] == 'venuspoint'){
            // We add an extra 2% so that the credited VP JPY amount won't be lower than our debited JPY amount.
            $piq_amount   = round(chg($u_obj, 'USD', $p['amount'], 1.02));
            $piq_currency = 'USD';
        }
        
        $res = $mts->withdraw($p['id'], $p['user_id'], $piq_amount, '', [
            'sid'          => $u_obj->getCurrentSession()['id'],
            'country'      => $u_obj->getCountry(),
            'sub_supplier' => $p['scheme'],
            'currency'     => $piq_currency,
            'ip'           => $u_obj->getAttr('cur_ip'),
            'username'     => $p['net_account'],
            'password'     => $p['net_email'],
            'bank_country'          => $p['bank_country'],
            'bank_account_number'   => $p['bank_account_number'],
            'bank_receiver'         => $p['bank_receiver'],
            'bank_name'             => $p['bank_name'],
            'bank_code'             => !empty($p['bank_code']) ? $p['bank_code'] : '',
            'bank_branch_code'      => !empty($p['bank_clearnr']) ? $p['bank_clearnr'] : '',
            'bank_address'          => $p['bank_address']
        ]);
        
        $result = $mts->withdrawResult($res);

        echo "Withdrawal call result:\n";
        print_r($result);

        list($mts_id, $ext_id, $result, $msg) = $result;

        $mts_tr = $this->getMtsTr($u_obj, $mts_id);

        return $mts_tr;
    }

    function testPaymentiqNotification($u, $action, $provider, $mts_tr, $do_card_data = false, $tr_action = 'deposit'){
        $attrs             = $u->data;
        $attrs['currency'] = $mts_tr['currency'];
        $attrs['tx_id']    = $mts_tr['id'];
        
        $body = [
            'sessionId'    => uniqid().'-'.$mts_tr['id'],
            'userId'       => '100.'.$u->getId(),
            'txId'         => rand(111111, 999999),
            'txAmountCy'   => $mts_tr['currency'],
            'provider'     => $provider,
            'attributes'   => $attrs,
            'txAmount'     => ($tr_action == 'deposit' ? $mts_tr['amount'] : -$mts_tr['amount']) / 100
        ];

        if($do_card_data){
            $body['maskedAccount']  = '401288******1881';
            $body['expiryMonth'] = '10';
            $body['expiryYear']  = '2025';
            $body['accountId']   = uniqid();
        }
        
        $http_auth = [CURLOPT_USERPWD => 'test:test123'];
        $url = $this->mts_base_url."user/transfer/deposit/confirm/paymentiq/$action";
        print_r(['url' => $url, 'sending' => $body]);
        $res = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'mts-piq-notification', 'POST', '', $http_auth);
        print_r(['result' => $res]);
        // Idempotency check
        $res = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'mts-piq-notification', 'POST', '', $http_auth);
        print_r(['result' => $res]);
    }

    function ccDeposit($u, $psp, $notify = true, $reset = false){
        $mts = new Mts();
        $mts->setSupplier('paymentiq'); 
        $u->verify();

        if($reset){
            $this->clearTable($u, 'pending_withdrawals');
            $this->clearTable($u, 'deposits');
            $this->db->doDb('dmapi')->truncate('documents');
            $this->db->doDb('mts')->truncate('recurring_transactions', 'transactions');
            $this->db->doDb('mts')->truncate('accounts');
        }

        $res = $mts->authorize($u, 2500, [
            'sub_supplier' => $psp,
            'cvc'          => 'sdfsadfsdf',
            'accountId'    => uniqid(),
            'sessionId'    => uniqid()
        ]);

        if($notify){
            $mts_tr = $this->getMtsTr($u);
            $mts_tr['status'] = 0;
            $this->mts_db->save('transactions', $mts_tr);

            $provider = ucfirst($psp);
            
            $this->testPaymentiqNotification($u, 'verifyuser', $provider, $mts_tr, true);
            $this->testPaymentiqNotification($u, 'authorize', $provider, $mts_tr, true);
            $this->testPaymentiqNotification($u, 'transfer', $provider, $mts_tr, true);

            // Repeat section
            $this->db->doDb('dmapi')->query("UPDATE documents SET status = 2");
        }
    }
    
}
