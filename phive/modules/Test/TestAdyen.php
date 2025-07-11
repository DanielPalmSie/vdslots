<?php
require_once 'TestCasinoCashier.php';
class TestAdyen extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->db->truncate('trans_log');
    }

    function testSepaWithdrawal($u, $cents){
        $insert = [
            'iban'           => 'DE36444488881234567890',
            'payment_method' => 'adyen',
            'aut_code'       => $cents
        ];

        $pid = $this->c->insertPendingCommon($u, $cents, $insert);

        $res = $this->c->payPending($pid, $cents);
        print_r([$res]);
        
    }
    
    /*
       Adyen trustly / bank:
       {
       "originalReference":"",
       "reason":"",
       "merchantAccountCode":"PandaMediaLtdCOM",
       "eventCode":"PENDING",
       "operations":"",
       "success":"true",
       "paymentMethod":"trustly",
       "currency":"SEK",
       "pspReference":"8814661774487887", <- ext id to videoslots
       "merchantReference":"175647", <- mts id
       "value":"50000",
       "live":"false",
       "eventDate":"2016-06-17T15:30:48.55Z",
       "supplier":"adyen"}

       ALTER TABLE `credit_cards` ADD `cipher` VARCHAR(500) NOT NULL AFTER `deleted_at`, ADD `is_new` TINYINT(1) NOT NULL DEFAULT '1' AFTER `cipher`, ADD INDEX (`cipher`), ADD INDEX (`is_new`); 

       require_once __DIR__ . '/../../phive/phive.php';
       require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';


       $sql 	= phive('SQL');
       $uh 	= phive('UserHandler');
       $m	= phive('Casino');
       $c      = phive('Cashier');

       $tc     = TestPhive::getModule('CasinoCashier');
       $tu     = TestPhive::getModule('User');

       $u      = cu('devtestse');

       $cipher = '';

       $card = ['card_num' => '4212345678901237', 'exp_year' => '20', 'exp_month' => '10', 'cvv' => '737', 'three_d' => 'Y'];
       $amount = 9000;

       $tc->mtsPrepareCc($u, $amount, $card['card_num'], $card['exp_year'], $card['exp_month'], $card['cvv'], $cipher, false);
       //$tc->mtsAuthorize($u, $amount, 'adyen');

       $mts_tr = $sql->doDb('mts')->loadAssoc("select * from transactions order by id desc limit 1");
       //$res1 = $tc->mtsAdyenNotification($mts_tr, $u, 'true', 'adyen', 'AUTHORISATION', true, $card);
       $res2 = $tc->mtsAdyenNotification($mts_tr, $u, 'true', 'adyen', 'CAPTURE', true, $card);
       print_r([$res1, $res2]);
     */
    function mtsAdyenNotification($mts_tr, $u, $success = 'true', $method = 'giropay', $ev_code = 'AUTHORISATION', $cc = false, $extra = []){
        $notification_id = rand(1000000, 1000000000);
        $params = [
            'pspReference'          => $mts_tr['reference_id'],
            'originalReference'     => '',
            'merchantAccountCode'   => 'PandaMediaLtdCOM',
            'merchantReference'     => $mts_tr['id'],
            'value'                 => $mts_tr['amount'],
            'currency'              => $u->getCurrency(),
            'eventCode'             => $ev_code,
            'success'               => $success
        ];

        $data_for_signature = implode(':', $params);
        $card_bin           = substr($extra['card_num'], 0, 6);
        $card_summary       = substr($extra['card_num'], -4);
        
        if($ev_code == 'AUTHORISATION'){
            $extra = [
                "additionalData_alias"                 => md5($extra['card_num']),
                "additionalData_expiryDate"            => $extra['exp_month'].'/20'.$extra['exp_year'],
                "additionalData_cardSummary"           => $card_summary,
                "additionalData_cardBin"               => $card_bin,
                'additionalData_shopperReference'      => "100.{$u->getId()}",
                'additionalData_threeDOfferedResponse' => $extra['three_d'], // Y or N
                'additionalData_issuerCountry'         => $u->getCountry(),
                'additionalData_bankName'              => 'Deutsche Bank',
                'additionalData_iban'                  => 'DE36444488881234567890',
                'additionalData_recurring_recurringDetailReference' => uniqid()
            ];
            $params = array_merge($params, $extra);
        }
        
        $mac                                    = phive('Cashier')->getSetting('adyen_return_mac');
        $params['paymentMethod']                = $method;
        $signature                              = base64_encode(hash_hmac('sha256', $data_for_signature, pack("H*", $mac), true));
        $params['additionalData_hmacSignature'] = $signature;
        $url                                    = $this->mts_base_url."user/transfer/deposit/confirm?supplier=adyen";
        $content                                = http_build_query($params);
        return phive()->post($url, $content, 'application/x-www-form-urlencoded', '', 'mts-adyen-notification');
    }


    function startBankDeposit($u, $amount, $sub_supplier, $params = []){
        $this->mts->setSubSupplier($sub_supplier);
        $this->mts->setUser($u);
        return $this->mtsDeposit($u, $amount, 'adyen', $params);
    }
    

}
