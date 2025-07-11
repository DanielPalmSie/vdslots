<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once 'TestCasinoCashier.php';
class TestCashierEndpoints extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->dstart = phive('Cashier/DepositStart');
        $this->wstart = phive('Cashier/WithdrawStart');
        $this->notify = phive('Cashier/CashierNotify');
        $this->psps   = phiveApp(PspConfigServiceInterface::class)->getPspSetting();
        //$this->db->truncate('trans_log');
    }

    function init($u, $psp){
        list($extra, $u_obj) = $this->getExtra($psp);
        $u = $u_obj ?? $u;
        $this->extra = $extra;
        $this->dstart->init('phive', $u);
        $this->wstart->init('phive', $u);
        $this->notify->init('phive');
    }


    
    // amount=20&action=deposit&ccSubSup=visa&cardnumber=4012000300001003&expirydate=10%2F20&expiryYear=2020&expiryMonth=10&cv2=003&bin=401200&supplier=ccard&network=wirecard
    /*
       amount	"10"
       action	"deposit"
       ccSubSup	"visa"
       expirydate	"10/20"
       expiryYear	"2020"
       expiryMonth	"10"
       worldpay_encrypted_data	"eyJhbGciOiJSU0ExXzUiLCJlbmMiOiJBMjU2R0NNIiwia2lkIjoiMSIsImNvbS53b3JsZHBheS5hcGlWZXJzaW9uIjoiMS4wIiwiY29tLndvcmxkcGF5LmxpYlZlcnNpb24iOiIxLjAuMSIsImNvbS53b3JsZHBheS5jaGFubmVsIjoiamF2YXNjcmlwdCJ9.XjKt-Md3wDCUpLxboOevRpKq5msLVFwWIDcMDKpTm7zm20NTeHGWSegrAT6K33Xy5px7O29D7F-MeiOEsxjSw2e_gU2rPZ4-NKl60VXOhvIWo2pdKq6PkunP91NIFzr7uM53WcjAfrGNI6QU_uLeEW6V9U2sbzA_pckL_fYNvhMUeUE-NSDvyqsY3WF49RoOTPuw5owB_ow5IYntKkfvmEKA7mDaDEhI4rHR1lsJHqnYtCEJSQEtSkooDMyxTPgiKR9ZlV5L-8U6mwPMnrMG5PAZlhmXrhdadv3izdBvlmCbGT7ppN3Wpmo8MK0Y0QFa07mFkpnhT-VT1dmayKzKpQ.V_ug5Lon0NAC2ucg.Bt04NdEkQWotvQ6Evw78rN3r1XPPNdDVJ9EbaajYJQk-Q5IuuFz1cETrMilcb9xPZ8AlFTC761gonXsUBvXuUdt8qRzSY7gbJZSqnuDdCtTq-y_fNu8vQuHC8Jh_LzXmBBN-RN-urtKCv6BojzKn.s7i6YV-uYD-Cc_6ubEw4dg"
       adyen_encrypted_data	"adyenjs_0_1_20_1$GCNwsdmajjDRKFAMWa9nikiaeCtPkXuRHRzeDgMA3q55sy2g+4pq6XWRd252r6cN/V1AOSQfdR1sm/QBcVQggELm2uePuCtOirrLV4yqXyE0w+dsv0HzCrwD+ZhYPJpjaYoX259p3JU1pUct5TMP8+ham2H9U3omWCte/quyhZ69xSPvcUUagxalo9XHZvx1tiprHybKGUsQLdYjg2H8psnf7/bSEPZe+/3w/1XSUwSP0PQmvy15Uk3uGmktp7dx99Ky8FrZCqYeyv408uH38b4OdZjsPqJSID3leYUaG69jYjs6S+IQ1y/NTCTMPMOadIHYVx6b5xgvLUfJxr+cOQ==$m6HA+5FlRGA9f/RL7kyvngrFwY/7cjJfBeWOJsKJy/NJyZYn6sCkE/1xjyq3Cc9ci4gxa9m8+xkvRR/FyZwMPF1vDDzKYq6Z5rd0/lCzdVb1SLzmG4L2q5jtrJv9nZFzfo5/1Czi3kcUOJTGz0Td8MrS91qRKIhHApTQHI3PoZQdbA0Y0jcDWbqDtG8tJBllER3a3l4NURd08S2PNX2ryakiCtMkOYWvQ2aXCrkdkYHV+OLkIC4lDqUFAfea1gwWIH4/MWo/hoXhNGD+1u72li+fvROsZsAiAPsvE47My+up80v/muApKhXpvQvOaB6YY1lfQf9tPM9SWkM5ZqSGfPUzI1cRm9zaxSyOmqfz06cLM8H+SVcOfirxCEYzruGDx97hlPCxj8aV+LueIX+CaN/eITficOBonkz5ms99bODkuYEmKtHtkF1S6uLwqbqwer/Eqj8CBNe3jOq/NXzZ4/R2Jkk744DqPK/abhKFreDsiFTff44q"
       bin	"401200"
       supplier	"ccard"
       network	"adyen"
*/


    // TODO auto tests for CC here.
    
    function getExtra($psp){
        $map = [
            'neteller' => [
                'net_account' => 'netellertest_EUR@neteller.com'
            ],
            'skrill' => [
                'email' => 'vadim.jefimenko@videoslots.com'
            ],
            'flexepin' => [
                'u_obj' => cu('devtestca'),
                'code' => '123456789'
            ],
            'neosurf' => [
                'u_obj' => cu('devtestfi'),
                'code' => '26U2P0QE0E'
            ],
            'seb' => [
                'u_obj' => cu('devtestse'),
                'network' => 'zimplerbank'
            ],
            'zimpler' => [
                'u_obj' => cu('devtestse')
            ],
            'sofort' => [
                'u_obj' => cu('devtestde'),
                'email' => 'vadim.jefimenko@videoslots.com'
            ],
            'paysafe' => [
                'u_obj' => cu('devtestde')
            ],
            // amount=10&action=deposit&bonus=&supplier=interac&network=paymentiq&method=etransfer
            'interac' => [
                'u_obj' => cu('devtestca'),
                'method' => 'etransfer'
            ],
            'muchbetter' => [
                'phone' => '44123456789'
            ],
            'siru' => [
                'u_obj' => cu('devtestfi'),
                'amount' => '25'
            ],
            'paypal' => [
                'u_obj' => cu('devtestgb'),
                'email' => 'sb-uevug1362524@personal.example.com'
            ],
            'cashtocode' => [
                'u_obj' => cu('devtestde')
            ],
            'instadebit' => [
                'u_obj' => cu('devtestca')
            ],
            'citadel' => [
                'u_obj' => cu('devtestca')
            ]
        ];

        $extra = (array)$map[$psp];
        if(!empty($extra['u_obj'])){
            $u_obj = $extra['u_obj'];
            unset($extra['u_obj']);
            return [$extra, $u_obj];
        }
        
        return [$extra, null];
    }
    
    function testPspDeposit($psp, $action = 'deposit'){

        echo "$psp:\n";
        
        $this->mts_db->truncate('transaction_logs');
        $this->db->truncate('trans_log');
        // amount=10
        // action=deposit&
        // bonus=&
        // net_account=david.cutajar%40videoslots.com&
        // supplier=neteller&
        // network=neteller
        $args = array_merge([
            'amount'   => 250,
            'network'  => $this->c->getPspRoute($this->dstart->u_obj, $psp),
            'supplier' => $psp
        ], $this->extra);

        print_r($this->dstart->execute($action, '', $args));

        //echo "\nTrans Log:\n";
        //print_r($this->db->loadArray("SELECT * FROM trans_log"));

        echo "\nLatest MTS Transaction:\n";
        $tr = $this->mts_db->loadAssoc("SELECT * FROM transactions ORDER BY id DESC");
        $tr['data'] = json_decode($tr['data'], true);
        print_r($tr);
        
        echo "\nMTS Log:\n";        
        foreach($this->mts_db->loadArray("SELECT * FROM transaction_logs") as $log){
            $log['data_in'] = json_decode($log['data_in'], true);
            $log['data_out'] = json_decode($log['data_out'], true);
            print_r($log);
        }
    }

    
    

}
