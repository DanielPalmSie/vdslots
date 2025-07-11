<?php
require_once 'TestCasinoCashier.php';
class TestMuchbetter extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('muchbetter');
        $this->db->truncate('trans_log');
    }

    function deposit($u, $amount, $phone){
        $this->mts->setUser($u);

        $much_better_mobile = Mts::getPhone($u, 'muchbetter') ?? Mts::getCanonicalPhone($phone);
        $extra = array_merge(
            $this->mts->getDepositBaseParams($u, $amount),
            ['transaction_description' => phive()->getSetting('domain'), 'phone' => $much_better_mobile]
        );
        
        $res = $this->mts->deposit($u, $amount, $extra);
        print_r($res);
    }

    function withdraw($u_obj, $amount){
        $pid = phive('Cashier')->insertPendingCommon($u_obj, $amount, [
            'payment_method' => 'muchbetter',
            'net_account'    => Mts::getPhone($u_obj, 'muchbetter'),
        ]);

        $p            = $this->c->getPending($pid);
        echo "Withdrawal row:\n";
        print_r($p);
        
        $mts          = Mts::getInstance('muchbetter');
        $currency = $u_obj->getCurrency();
        
        $res = $mts->withdraw($p['id'], $p['user_id'], $p['amount'], '', [
            'transaction_description' => 'Test',
            'currency'                => $currency,
            'phone'                   => $p['net_account'],
        ]);
        
        $result = $mts->withdrawResult($res);

        echo "Withdrawal call result:\n";
        print_r($result);

        list($mts_id, $ext_id, $result, $msg) = $result;

        $mts_tr = $this->getMtsTr($u_obj, $mts_id);

        return $mts_tr;
    }

    function notification($mts_tr, $u, $amount = null){
        $attrs          = $u->data;
        $attrs['tx_id'] = $mts_tr['id'];
        
        $body = [
            'merchantInternalRef'   => $mts_tr['id'],
            'amount'                => ($amount ?? $mts_tr['amount'])  / 100,
            'currency'              => $u->getCurrency(),
            'status'                => 'PENDING'
        ];

        print_r(['sending', $body]);
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=muchbetter";
        $res = phive()->post($url, $body, 'application/json', '', 'mts-muchbetter-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $body, 'application/json', '', 'mts-muchbetter-notification');
        print_r([$res]);
    }    
}
