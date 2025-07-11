<?php
require_once 'TestCasinoCashier.php';
class TestCredorax extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('credorax');
        $this->db->truncate('trans_log');
    }

    function testWithdraw($u){
        $p           = $this->getLatestPending($u);
        $p['mts_id'] = 0;
        $p['status'] = 'pending';
        $this->db->sh($u)->save('pending_withdrawals', $p);
        return $this->c->payPending($p['id']);
    }
    
    function getReqValues($u, $amount){
        $args = [
            'expire_date' => '12/27',
            'card_hash' => '4012 00** **** 1112',
            'bin' => '401200'
        ];

        $enc_data = [
            'PKey' => uniqid(),
            'z2' => 0,
            'ResponseID' => uniqid(),
            '3d_version' => '1.0',
            'b2' => 1,
            'expiryMonth' => '12',
            'expiryYear' => '2027',
        ];

        $args['credorax_encrypted_data'] = $enc_data;

        return array_merge($this->mts->getDepositBaseParams($u, $amount), $args);
    }
    
    function testNotification($u, $amount){

        $mts_tr = $this->getMtsTr($u);
        $mts_tr['status'] = 2;
        $values = $this->getReqValues($u, $amount);
        $mts_tr['data'] = json_encode(['values' => $values, 'response_id' => uniqid()]);
        $this->mts_db->save('transactions', $mts_tr);

        $body = [
            'a1' => $mts_tr['id'],
            'g1' => uniqid(),
            'z2' => 0
        ];

        $body['K'] = hash('sha256', $body['a1'].$body['g1'].$body['z2'].'UPO4GS5H');
        
        print_r(['sending', $body]);

        $body = http_build_query($body);
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=credorax";
        $res = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'mts-credorax-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'mts-credorax-notification');
        print_r([$res]);
        
        // Repeat section
        $this->db->doDb('dmapi')->query("UPDATE documents SET status = 2");
    }

/*
   amount	"10"
   action	"deposit"
   ccSubSup	"visa"
   expirydate	"12/27"
   expiryYear	"2027"
   expiryMonth	"12"
   cardHash	"4012+00**+****+1112"
   credorax_encrypted_data[PKey]	"e2cdbabe6a464bc6ad1ae22e64950cfa"
   credorax_encrypted_data[z2]	"0"
   credorax_encrypted_data[z3]	"Transaction+has+been+executed+successfully."
   credorax_encrypted_data[ResponseID]	"5f51138a-9db9-4e63-992f-0abf344f7d12"
   credorax_encrypted_data[3d_version]	"1.0"
   credorax_encrypted_data[b2]	"1"
   credorax_encrypted_data[3ds_compind]	"N"
   credorax_encrypted_data[expiryMonth]	"12"
   credorax_encrypted_data[expiryYear]	"2027"
   bin	"401200"
   supplier	"ccard"
   network	"credorax"
*/    
    function ccAuthorize($u, $amount, $reset = false){
        $mts = new Mts();
        $mts->setSupplier('credorax'); 
        $u->verify();

        if($reset){
            $this->clearTable($u, 'pending_withdrawals');
            $this->clearTable($u, 'deposits');
            $this->db->doDb('dmapi')->truncate('documents');
            $this->db->doDb('mts')->truncate('recurring_transactions', 'transactions');
            $this->db->doDb('mts')->truncate('accounts');
        }

        $args = $this->getReqValues($u, $amount);
        
        return $mts->authorize($u, $amount, $args);
    }
}
