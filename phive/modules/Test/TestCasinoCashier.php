<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

class TestCasinoCashier extends TestPhive{

    function __construct(){
        $this->db           = phive('SQL');
        $this->mts_db       = phive('SQL')->doDb('mts');
        $this->dmapi_db     = phive('SQL')->doDb('dmapi');
        $this->c            = phive('Cashier');
        $this->casino       = phive('Casino');
        $this->mts          = new Mts();
        $this->mts_base_url = $this->c->getSetting('mts')['base_url'];
    }

    function depositCash($u_obj, $amount, $psp, $scheme = ''){
        $this->casino->depositCash($u_obj, $amount, $psp, uniqid(), $scheme, '', '', false, 'approved', null, rand(1000000, 2000000));
    }

    function setFirstDeposit($u, $dep_type = 'worldpay'){
        $this->db->sh($u)->insertArray('first_deposits', [
            'amount' => 10000,
            'user_id' => $u->getId(),
            'currency' => $u->getCurrency(),
            'dep_type' => $dep_type,
            'deposit_id' => rand(1000000, 2000000)
        ]);
    }

    function getLatestDeposit($u){
        return $this->db->sh($u)->loadAssoc("SELECT * FROM deposits WHERE user_id = {$u->getId()} ORDER BY id DESC");
    }

    function getLatestPending($u){
        return $this->db->sh($u)->loadAssoc("SELECT * FROM pending_withdrawals WHERE user_id = {$u->getId()} ORDER BY id DESC");
    }

    function truncLog(){
        $this->db->truncate('trans_log');
    }

    function getMtsTr($u, $id = ''){
        $where_id = empty($id) ? '' : " AND id = $id ";
        return $this->mts_db->loadAssoc("SELECT * FROM transactions WHERE user_id = {$u->getId()} $where_id ORDER BY id DESC");
    }

    function refereshMtsTr($u, $mts_tr){
        return $this->getMtsTr($u, $mts_tr['id']);
    }

    function prLog($tag = ''){
        $where_tag = empty($tag) ? '' : "WHERE tag = '$tag'";
        $res       = $this->db->loadArray("SELECT * FROM trans_log $where_tag");
        print_r($res);
    }

    function testRevertPending($pending){
        $this->c->revertPending($pending['ext_id'], $pending['payment_method'], ['msg' => 'Test of revert pending']);
    }

    function setFifo($u_obj, $psp){
        $this->clearTable($u_obj, 'deposits');
        $this->depositCash($u_obj, 10000, $psp);
    }

    function testFifo($u_obj, $deposits = [], $action = ''){

        $u_obj->deleteSetting('closed_loop_start_stamp');
        $u_obj->deleteSetting('closed_loop_cleared');

        $psps = $this->c->getAllAllowedPsps($u_obj, 'withdraw', 'desktop');

        $insertDeposit = function($psp, $amount, $u_obj, $scheme, $card_hash = ''){
            $deposit = [
                'timestamp' => phive()->hisMod("-5 month"),
                'dep_type'  => $psp,
                'amount'    => $amount,
                'user_id'   => $u_obj->getId(),
                'ext_id'    => uniqid(),
                'scheme'    => $scheme,
                'card_hash' => $card_hash
            ];
            $this->db->sh($u_obj)->insertArray('deposits', $deposit);
        };

        switch($action){
            case 'cards':
                $mts   = new Mts('', $u_obj);
                $cards = $mts->getCards();
                foreach($cards as $c){
                    $insertDeposit('worldpay', 2000, $u_obj, 'visa', $c['card_num']);
                }
                break;
            default:
                if(!empty($deposits)){
                    foreach($deposits as $d){
                        $insertDeposit($d['psp'], $d['amount'], $u_obj, $d['scheme'], $d['card_hash']);
                    }
                }
                break;
        }

        $fifo_psp = $this->c->getFifo($u_obj, $psps, $cards);
        echo "Fifo PSP for {$u_obj->getUsername()}: \n";
        print_r($fifo_psp);
    }


    function testPayPending($u, $amount){
        $pending = [
            'currency'            => $u->getCurrency(),
            'bank_receiver'       => $u->getFullName(),
            'bank_name'           => 'The bank',
            'bank_code'           => 'a123',
            'bank_clearnr'        => 1234,
            'bank_address'        => 'The address',
            'bank_city'           => 'Toronto',
            'bank_country'        => $u->getCountry(),
            'bank_account_number' => 'a12345',
            'swift_bic'           => 'CACA',
            'iban'                => 'CA00000001234',
            'amount'              => $amount,
            'aut_code'            => $amount,
            'user_id'             => $u->getId(),
            'payment_method'      => 'bank',
            'ext_id'              => uniqid()
        ];

        $extra = $pending;

        $extra['bank_branch_code'] = 1234;
        $extra['email']            = $u->getAttr('email');

        $pid = $this->db->sh($u, 'id')->insertArray('pending_withdrawals', $pending);

        $this->c->payPending($pid);

        $refreshed = $this->getLatestPending($u);
        echo "Pending after withdrawal:\n";
        print_r($refreshed);
        return $refreshed;
    }

    function testCitadelWithdrawal($u, $amount){
        $pending = [
            'currency'            => $u->getCurrency(),
            'bank_receiver'       => $u->getFullName(),
            'bank_name'           => 'The bank',
            'bank_code'           => 'a123',
            'bank_clearnr'        => 1234,
            'bank_address'        => 'The address',
            'bank_city'           => 'Toronto',
            'bank_country'        => $u->getCountry(),
            'bank_account_number' => 'a12345',
            'swift_bic'           => 'CACA',
            'iban'                => 'CA00000001234',
            'amount'              => $amount,
            'aut_code'            => $amount,
            'user_id'             => $u->getId(),
            'payment_method'      => 'citadel'
        ];

        $extra = $pending;

        $extra['bank_branch_code'] = 1234;
        $extra['email']            = $u->getAttr('email');

        $pid = $this->db->sh($u, 'id')->insertArray('pending_withdrawals', $pending);

        $this->mts->setSupplier('citadel');

        $res = $this->mts->withdraw($pid, $u->getId(), $pending['aut_code'], '', $extra);

        print_r($res);
    }

    function clearCcFailData(){
        foreach($this->mts->getCcSuppliers() as $psp){
            $this->mts->unsetSess('failed_cc_suppliers', $psp);
        }
        $this->mts->unsetSess('3d_result');
    }

    function testCcFailover($u, $cc_psp, $c_hash = null){
        $mts                     = new Mts('', $u);
        $_SESSION['cc_supplier'] = $cc_psp;
        $mts->unsetSess('3d_result');
        $enrolled                = $mts->failover('check3d', $c_hash, $u, 2000, $c_hash);
        print_r($enrolled);
    }

  // Use 4012000300006002 to test non 3DS.
  function testWirecardCheckCard($u, $card_num = '4012000300001003', $post = []){
      $_SESSION['cc_supplier'] = 'wirecard';

      $u->setAttr('verified_phone', 1);
      $u->setSetting('card_sms', 0);

      if(empty($post)){
          $this->post = [
              'cardnumber'  => $card_num,
              'cv2'         => '003',
              'expirydate'  => '01/19',
              'expiryMonth' => '01',
              'expiryYear'  => '2019',
              'amount'      => '20'
          ];
      }else{
          $this->post = $post;
      }

      $dc          = phive('WireCard');
      $mts         = Mts::getInstance('', $u->getId());
      $mts->unsetSess('3d_result');
      $mts->setCreditCardData($this->post);
      $card_status = $mts->isActiveAndValid($dc->getSixFourAsterisk($this->post['cardnumber']));
      $c_hash      = $dc->getSixFourAsterisk($this->post['cardnumber']);
      $cents       = intval($this->c->cleanUpNumber($this->post['amount']) * 100);
      $card_status = @$mts->failover('check3d', $c_hash, $u, $cents, $c_hash)['three_d'];
      /*
        Array
        (
        [3d_result] => Array
        (
        [4012 00** **** 1003] => Array
        (
        [three_d] => 1
        [status] => Y
        [supplier] => wirecard
        )

        )

        )
       */
      print_r($_SESSION['mts']);
  }


    function testWirecardEnrollPlusDeposit($u){
        /*
           amount	20
           box_type	quick
           cardnumber	4012000300001003
           cv2	003
           expirydate	01/19
           expiryMonth	01
           expiryYear	2019
           lang	en
         */



    }

    function removeCard($c_hash){
        $this->mts_db->delete('credit_cards', ['card_num' => $c_hash]);
        $this->dmapi_db->delete('documents', ['sub_tag' => $c_hash]);
    }

    function deletePws($u){
        $this->db->delete('pending_withdrawals', ['user_id' => $u->getId()], $u->getId());
    }

    function resetForTesting($u){
        $this->deletePws($u);
        $this->db->delete('deposits', ['user_id' => $u->getId()], $u->getId());
        $this->db->delete('lga_log', ['user_id' => $u->getId()], $u->getId());
        $this->db->truncate('trans_log', 'mosms_check');
        $this->db->delete('users_settings', "user_id = {$u->getId()} AND setting LIKE '%lim%'", $u->getId());
        phMdel(mKey($u, 'pending-deposits'));
    }

    function initCardsAndRecurring($u, $psp = 'adyen'){
        $this->resetMts();
        phive('SQL')->doDb('dmapi')->truncate('documents', 'files');

        //
        $card = ['user_id' => $u->getId(), 'exp_year' => '2030', 'exp_month' => '10', 'three_d' => 1, 'customer_id' => 100];
        foreach(['5212 34** **** 1234', '4212 34** **** 1237', '4444*********'] as $cnum){
            $card['card_num'] = $cnum;
            $card_id          = $this->mts_db->insertArray('credit_cards', $card);
            $transaction = [
                'customer_id' => 100,
                'customer_transaction_id' => random_int(1, 1000000),
                'user_id' => $u->getId(),
                'card_id' => $card_id,
                'amount' => 2000,
                'currency' => $u->getCurrency(),
                'supplier' => $psp,
                'sub_supplier' => 'visa',
                'status' => 10
            ];
            // Certain logic requires a successful deposit with the card.
            $this->mts_db->insertArray('transactions', $transaction);

            $recurring = [
                'customer_id' => 100,
                'user_id'     => $u->getId(),
                'card_id'     => $card_id,
                'supplier'    => $psp,
                'ext_id'      => uniqid(),
                'currency'    => $u->getCurrency(),
                'amount'      => 10000,
                'created_at'  => phive()->hisNow(),
                'updated_at'  => phive()->hisNow()
            ];
            $this->mts_db->insertArray('recurring_transactions', $recurring);
            phive('Dmapi')->createEmptyDocument($u->getId(), $psp, 'visa', $card_id, $card_id);

        }
        $this->dmapiApproveAll();
    }

    function dmapiApproveAll(){
        phive('SQL')->doDb('dmapi')->query("UPDATE documents SET status = 2");

    }

    function resetDmapi(){
        $this->dmapi_db->truncate('documents', 'files');
    }

    function resetMts(){
        $this->mts_db->truncate('transactions', 'credit_cards', 'transaction_logs', 'recurring_transactions');
    }

    /**
     * Used to test Mts::failover()
     *
     * Example invocation: $tc->mtsFailover('check3d', '4212 34** **** 1237', $u, 10000, '4212 34** **** 1237')
     * $tc->mtsPrepareCc() must've been run before we call this.
     *
     * @param mixed
     *
     * @return array The result.
     */
    function mtsFailover(){
        $res = call_user_func_array([$this->mts, 'failover'], func_get_args());
        print_r($res);
    }

    function testSwishNotification($u, $mts_tr, $status = 'PAID'){
        $attrs          = $u->data;
        $attrs['tx_id'] = $mts_tr['id'];

        $body = [
            'id'                    => $mts_tr['reference_id'],
            'payeePaymentReference' => $mts_tr['id'],
            'payerAlias'            => '467099999999',
            'amount'                => $mts_tr['amount'] / 100,
            'currency'              => $u->getCurrency(),
            'status'                => $status
        ];

        print_r(['sending', $body]);

        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=swish";
        $res = phive()->post($url, $body, 'application/json', '', 'mts-swish-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $body, 'application/json', '', 'mts-swish-notification');
        print_r([$res]);
    }

    function failedMtsCcSupplier($u, $action, $c_hash, $failed_supplier){
        $this->mts->failed_suppliers[] = $failed_supplier;
        echo "$failed_supplier should not show in the below output at all.\n\n";
        $this->mtsCcSupplier($u, $action, $c_hash);
    }

    function cCsupplierInsertDeposit($u, $supplier, $c_hash){
        $uid                = uid($u);
        $insert = [
            'user_id'   => $uid,
            'scheme'    => 'visa',
            'dep_type'  => $supplier,
            'card_hash' => $c_hash,
            'ext_id'    => uniqid(),
            'loc_id'    => uniqid(),
            'ip_num'    => '1.1.1.1',
            'display_name' => 'VISA',
            'amount'    => 2000
        ];
        $this->db->sh($uid)->save('deposits', $insert);
    }

    function mtsCcSupplier($u, $c_hash = '', $insert_suppliers = []){
        $uid                = uid($u);
        $this->mts->user_id = $uid;
        $this->mts->user    = $u;
        $this->db->delete('deposits', ['user_id' => $uid], $uid);

        foreach($insert_suppliers as $insert_supplier){
            $this->db->delete('deposits', ['user_id' => $uid], $uid);
            $this->cCsupplierInsertDeposit($u, $insert_supplier, $c_hash);
            echo "This should not be $insert_supplier: {$this->mts->getCcSupplier('deposit', $c_hash)}\n";
        }

        $this->db->delete('deposits', ['user_id' => $uid], $uid);
        foreach($insert_suppliers as $insert_supplier){
            $this->cCsupplierInsertDeposit($u, $insert_supplier, $c_hash);
        }

        for($i = 0; $i < 10; $i++){
            echo $this->mts->getCcSupplier('deposit', $c_hash)."\n";
        }

        $last_supplier = end($insert_suppliers);

        echo "Testing withdrawal, this should show $last_supplier: {$this->mts->getCcSupplier('withdraw', $c_hash)}\n";
    }

    function setupMtsCcDeposits($u, $card_info){
        foreach($card_info as $psp => $c_hash){
            $cards = $this->mts_db->loadArray("SELECT * FROM credit_cards WHERE card_num = '$c_hash'");
            foreach($cards as $c){
                $insert = [
                    'customer_id' => 100,
                    'user_id'     => $c['user_id'],
                    'card_id'     => $c['id'],
                    'amount'      => 2000,
                    'currency'    => $u->getCurrency(),
                    'supplier'    => $psp,
                    'status'      => 10
                ];
                $this->mts_db->insertArray('transactions', $insert);
            }
            $this->cCsupplierInsertDeposit($psp, $c_hash);
        }
    }

    function testCanWithdraw($u){
        $uid = $u->getId();
        $u->unVerify();
        $this->db->delete('deposits', ['user_id' => $uid], $uid);

        $this->casino->depositCash($uid, 10000, 'wirecard', uniqid(), 'visa', '3243 56** **** 1234', 122);
        $res = phive()->getJsBool($this->c->canWithdraw($u));
        echo "Can withdraw result: $res\n";

        $this->casino->depositCash($uid, 100000, 'wirecard', uniqid(), 'visa', '3243 56** **** 1234', 122);
        $res = phive()->getJsBool($this->c->canWithdraw($u));
        echo "Can withdraw result: $res\n";

        $u->verify();
        $res = phive()->getJsBool($this->c->canWithdraw($u));
        echo "Can withdraw result: $res\n";
    }

    function prTrLog($uid, $tag = ''){
        $tag_where = empty($tag) ? '' : "WHERE trigger_name = '$tag'";
        if(empty($uid))
            $log = $this->db->shs('merge', '', null, 'triggers_log')->loadArray("SELECT * FROM triggers_log $tag_where");
        else
            $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log $tag_where");
        print_r($log);
    }


    function testFrOnDeposit($uid){
        $u      = cu($uid);
        $this->clearTable($u, ['deposits', 'cash_transactions', 'triggers_log', 'bonus_entries', 'first_deposits']);
        $uid    = $u->getId();
        $casino = phive('Casino');
        $this->c->insertTransaction($uid, 1000, 92, 'chargeback');
        $casino->depositCash($uid, 10000, 'wirecard', uniqid(), 'visa', '3243 56** **** 1234', 122);
        $casino->depositCash($uid, 10000, 'wirecard', uniqid(), 'visa', '3243 56** **** 1234', 122);
        echo "This should not show something as we have prior deposits of the same type:\n";
        phive('Cashier/Fr')->onDeposit($uid, $casino->did);
        $this->prTrLog($uid, 'FRD31');

        echo "This should show something as we do not have prior deposits of the same type:\n";
        $casino->depositCash($uid, 10000, 'instadebit', uniqid(), 'visa', '32431234', 122);
        phive('Cashier/Fr')->onDeposit($uid, $casino->did);
        $this->prTrLog($uid, 'FRD31');
    }

    function testFrEverydayCron($product = 'videoslots'){
        $this->db->truncate('triggers_log', 'deposits', 'users_game_sessions', 'users_daily_stats', 'users_sessions');
        $pr_db = phive('SQL')->doDb('partnerroom');
        $pr_db->truncate('pixel_registrar', 'pixel_first_deposit');

        // FRD21
        echo "This should not show anything as conversion is high:\n";
        foreach(range(1, 100) as $uid){
            $pr_db->insertArray('pixel_registrar', ['uid' => $uid, 'bonus_code' => 'FRTEST', 'product' => $product]);
        }
        foreach(range(1, 10) as $uid){
            $pr_db->insertArray('pixel_first_deposit', ['uid' => $uid, 'bonus_code' => 'FRTEST', 'product' => $product]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD21');

        echo "This should show something as conversion is low:\n";
        $pr_db->truncate('pixel_first_deposit');
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD21');

        // FRD29
        echo "This should not show anything as player profit is too low:\n";
        foreach(range(1, 2) as $uid){
            $this->db->sh($uid, '', 'deposits')->insertArray('deposits', ['user_id' => $uid, 'amount' => 1000, 'currency' => 'SEK', 'ext_id' => uniqid()]);
            $this->db->sh($uid, '', 'deposits')->insertArray('deposits', ['user_id' => $uid, 'amount' => 2000, 'currency' => 'SEK', 'ext_id' => uniqid()]);
            $this->db->sh($uid, '', 'users_game_sessions')->insertArray('users_game_sessions', [
                'user_id'         => $uid,
                'result_amount'   => 5000
            ]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD29');

        echo "This should show something as player profit is too high:\n";
        foreach(range(1, 2) as $uid){
            $this->db->sh($uid, '', 'users_game_sessions')->insertArray('users_game_sessions', [
                'user_id'         => $uid,
                'result_amount'   => 5000000
            ]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD29');

        //FRD26
        echo "This should not show anything as not enough sessions:\n";
        foreach(range(1, 2) as $uid){
            foreach(range(1, 3) as $scnt){
                $this->db->sh($uid, '', 'users_game_sessions')->insertArray('users_game_sessions', [
                    'user_id'         => $uid,
                    'win_amount'      => 1010,
                    'bet_amount'      => 1000,
                    'result_amount'   => 10,
                    'game_ref'        => 'yggdrasil_7339'
                ]);
            }
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD26');

        echo "This should not show anything as not enough profit:\n";
        foreach(range(1, 2) as $uid){
            foreach(range(1, 3) as $scnt){
                $this->db->sh($uid, '', 'users_game_sessions')->insertArray('users_game_sessions', [
                    'user_id'         => $uid,
                    'win_amount'      => 1010,
                    'bet_amount'      => 1000,
                    'result_amount'   => 10,
                    'game_ref'        => 'yggdrasil_7339'
                ]);
            }
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD26');

        echo "This should not show anything as enough profit and positive sessions:\n";
        foreach(range(1, 2) as $uid){
            foreach(range(1, 3) as $scnt){
                $this->db->sh($uid, '', 'users_game_sessions')->insertArray('users_game_sessions', [
                    'user_id'         => $uid,
                    'win_amount'      => 2000,
                    'bet_amount'      => 1000,
                    'result_amount'   => 1000,
                    'game_ref'        => 'yggdrasil_7339'
                ]);
            }
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD26');

        //FRD25
        echo "This should not show anything as enough rewards and duplicate sessions:\n";
        foreach(range(1, 2) as $uid){
            $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', ['user_id' => $uid, 'ip' => $uid]);
            $this->db->sh($uid, '', 'users_daily_stats')->insertArray('users_daily_stats', ['user_id' => $uid, 'deposits' => 100, 'rewards' => 10]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD25');

        echo "This should not show anything as not enough duplicate sessions:\n";
        foreach(range(1, 2) as $uid){
            $this->db->sh($uid, '', 'users_daily_stats')->insertArray('users_daily_stats', ['user_id' => $uid, 'deposits' => 100, 'rewards' => 10000]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD25');

        echo "This should show something as enough duplicate sessions and rewards:\n";
        foreach(range(1, 3) as $uid){
            $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', ['user_id' => $uid, 'ip' => 1]);
            $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', ['user_id' => $uid, 'ip' => 1]);
        }
        phive('Cashier/Fr')->everydayCron();
        $this->prTrLog(0, 'FRD25');



    }

    function testFrOnlogin($uid, $current_ip = '', $other_ip = ''){
        $u   = cu($uid);
        $uid = $u->getId();
        $this->db->truncate('triggers_log', 'users_sessions');
        $ins = ['user_id' => $uid];

        foreach(range(1, 3) as $ip){
            $ins['ip'] = $ip;
            $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        }
        echo "This should show empty because not enough different IPs:\n";
        phive('Cashier/Fr')->ipLinks($u);
        $this->prTrLog($uid);
        foreach(range(4, 6) as $ip){
            $ins['ip'] = $ip;
            $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        }
        echo "This should show something because enough different IPs:\n";
        phive('Cashier/Fr')->ipLinks($u);
        $this->prTrLog($uid);

        $this->db->truncate('triggers_log', 'users_sessions');
        $ins['ip']          = $current_ip;
        $ins['fingerprint'] = 1;
        $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        echo "This should show empty because same IP and fingerprint and IP from correct country:\n";
        phive('Cashier/Fr')->onLogin($uid);
        $this->prTrLog($uid, 'FRD19');

        echo "This should not be empty because IP from wrong country:\n";
        $this->db->truncate('triggers_log', 'users_sessions');
        $ins['ip']          = $current_ip;
        $ins['fingerprint'] = 1;
        $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        $ins['ip']          = $other_ip;
        $cur_sess_id = $this->db->sh($uid, '', 'users_sessions')->insertArray('users_sessions', $ins);
        phive('Cashier/Fr')->onLogin($uid);
        $this->prTrLog($uid, 'FRD19');


        echo "This should not be empty because IP from wrong country and unknown fingerprint:\n";
        $ins['fingerprint'] = 2;
        $ins['id']          = $cur_sess_id;
        $this->db->sh($uid, '', 'users_settings')->save('users_sessions', $ins);
        phive('Cashier/Fr')->onLogin($uid);
        $this->prTrLog($uid, 'FRD19');

        echo "This should be empty because not enough duplicate accounts:\n";
        unset($ins['id']);
        $ins['user_id'] = 1;
        $this->db->sh(1, '', 'users_sessions')->insertArray('users_sessions', $ins);
        phive('Cashier/Fr')->onLogin($uid);
        $this->prTrLog($uid, 'FRD20');

        echo "This should not be empty because enough duplicate accounts:\n";
        $ins['user_id'] = 2;
        $this->db->sh(2, '', 'users_sessions')->insertArray('users_sessions', $ins);
        $ins['user_id'] = 3;
        $this->db->sh(3, '', 'users_sessions')->insertArray('users_sessions', $ins);
        $ins['user_id'] = 4;
        $this->db->sh(4, '', 'users_sessions')->insertArray('users_sessions', $ins);
        phive('Cashier/Fr')->onLogin($uid);
        $this->prTrLog($uid, 'FRD20');


    }

    function testAmlOnWithdrawal($uid){
        $this->db->truncate('money_laundry', 'triggers_log');
        $u    = cu($uid);
        $uid  = $u->getId();
        $ins  = ['user_id' => $uid, 'dep_sum' => 1000, 'wager_sum' => 1000];
        $this->db->sh($uid)->insertArray('money_laundry', $ins);

        echo "Should be empty because have not achieved tholds:\n";
        phive('Cashier/Aml')->onWithdrawal($uid);
        $log = $this->db->sh($uid)->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        echo "Should not be empty because a lot of deposits with very little wager:\n";
        $ins  = ['user_id' => $uid, 'dep_sum' => 1000000, 'wager_sum' => 1000];
        $this->db->sh($uid)->insertArray('money_laundry', $ins);
        phive('Cashier/Aml')->onWithdrawal($uid);
        $log = $this->db->sh($uid)->loadArray("SELECT * FROM triggers_log");
        print_r($log);

    }

    function testAmlOnDeposit($uid, $delete = true, $loop = 5){
        if($delete){
            foreach(['deposits', 'users_settings', 'triggers_log'] as $tbl)
                $this->db->delete($tbl, ['user_id' => $uid], $uid);
        }
        $u = cu($uid);

        /*
        // The AML1 test is probably outdated so commented for now.
        // AML1 (2k), 12 (10k) and 16 (20k)
        $ins = [
            'user_id' => $uid,
            'amount'  => 10000,
            'dep_type' => 'wirecard',
            'ext_id'   => uniqid()
        ];
        $this->db->sh($uid, '', 'deposits')->insertArray('deposits', $ins);
        //echo "This should be empty:\n";

        echo "After 100 EUR deposit:\n";
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        $ins['amount'] += 200000;
        $ins['ext_id'] = uniqid();
        $this->db->sh($uid, '', 'deposits')->insertArray('deposits', $ins);
        echo "After 2100 EUR in deposits:\n";
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        $ins['amount'] += 500000;
        $ins['ext_id'] = uniqid();
        $this->db->sh($uid, '', 'deposits')->insertArray('deposits', $ins);
        echo "After 7100 EUR in deposits:\n";
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);

        $ins['amount'] += 2000000;
        $ins['ext_id'] = uniqid();
        $this->db->sh($uid, '', 'deposits')->insertArray('deposits', $ins);
        echo "After 27100 EUR in deposits:\n";
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log");
        print_r($log);
        */

        //AML5
        echo "After 10 voucher deposits:\n";
        if($delete){
            $this->mts_db->query("DELETE FROM transactions WHERE user_id = $uid");
            $this->mts_db->query("DELETE FROM credit_cards WHERE user_id = $uid");
        }

        $card_id     = $this->mts_db->insertArray('credit_cards', ['user_id' => $uid, 'prepaid' => 1]);
        $created_at  = phive()->hisNow();
        $base_insert = ['created_at' => $created_at, 'user_id' => $uid, 'amount' => 100000, 'status' => 10];

        foreach(range(1, $loop) as $i){
            $this->mts_db->insertArray('transactions', array_merge($base_insert, ['supplier' => 'paysafe']));
        }

        foreach(range(1, $loop) as $i){
            $this->mts_db->insertArray('transactions', array_merge($base_insert, ['supplier' => 'wirecard', 'card_id' => $card_id]));
        }

        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid)->loadArray("SELECT * FROM triggers_log WHERE trigger_name = 'AML5' AND user_id = $uid");
        print_r($log);

        /*
        //AML8
        // The AML8 test is probably outdated so commented for now.
        //We insert a third option to get 3
        echo "After 3 different PSP deposits in 48 hours since reg:\n";
        $u->setAttr('register_date', phive()->today());
        $this->db->sh($uid, '', 'deposits')->insertArray('deposits', ['user_id' => $uid, 'dep_type' => 'entercash', 'amount' => 1000, 'ext_id' => uniqid()]);
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadAssoc("SELECT * FROM triggers_log WHERE trigger_name = 'AML8'");
        print_r($log);

        //AML8
        //We insert a third option to get 3
        echo "After 3 different PSP deposits when old reg:\n";
        $u->setAttr('register_date', '2015-01-01');
        phive('Cashier/Aml')->onDeposit($uid);
        $log = $this->db->sh($uid, '', 'triggers_log')->loadArray("SELECT * FROM triggers_log WHERE trigger_name = 'AML8'");
        print_r($log);
        */


    }

    /*
       Field 	Type 	Null 	Key 	Default 	Extra
       id 	bigint(20) unsigned 	NO 	PRI 	NULL	auto_increment
       customer_id 	int(11) 	NO 	MUL 	NULL
       user_id 	bigint(20) 	NO 	MUL 	NULL
       card_num 	varchar(50) 	NO 		NULL
       transaction_id 	bigint(20) unsigned 	NO 	MUL 	NULL
       exp_year 	smallint(6) 	NO 		NULL
       exp_month 	tinyint(4) 	NO 		NULL
       three_d 	tinyint(4) 	NO 		0
       active 	tinyint(4) 	NO 	MUL 	NULL
       card_scan 	varchar(60) 	NO 	MUL 	NULL
       verified 	tinyint(3) unsigned 	NO 		NULL
       approved 	tinyint(3) unsigned 	NO 	MUL 	0
       is_unique 	tinyint(3) unsigned 	NO 	MUL 	1
       country 	char(2) 	NO 		NULL
       created_at 	timestamp 	NO 		0000-00-00 00:00:00
       updated_at 	timestamp 	NO 		0000-00-00 00:00:00
       deleted_at 	timestamp 	YES 	MUL 	NULL
     */
    function amlTestNearExpired($uid, $months_to_expiry = 1, $amount_sum = 50000){
        $month = phive()->modDate('', '+5 month', 'n');
        $year  = phive()->modDate('', '+5 month', 'Y');
        $ud    = ud($uid);
        $u     = cu($uid);
        $this->mts_db->truncate('transactions', 'credit_cards');
        $ins = [
            'user_id'   => $ud['id'],
            'card_num'  => '4012 00** **** 1003',
            'exp_year'  => $year,
            'exp_month' => $month
        ];

        $card_id = $this->mts_db->insertArray('credit_cards', $ins);
        $tr_ins = [
            'user_id' => $ud['id'],
            'card_id' => $card_id,
            'amount'  => 1000
        ];
        $tr_id   = $this->mts_db->insertArray('transactions', $tr_ins);

        echo "This should return empty because too low amount and too far from expiry:\n";
        $res = phive('Cashier/Mts')->arf('cCnearExpireGet', [$ud['id'], $months_to_expiry, $amount_sum]);
        print_r($res);

        $tr_ins['amount'] = $amount_sum;
        $this->mts_db->insertArray('transactions', $tr_ins);
        echo "This should return empty because too far from expiry:\n";
        $res = phive('Cashier/Mts')->arf('cCnearExpireGet', [$ud['id'], $months_to_expiry, $amount_sum]);
        print_r($res);

        $month = phive()->modDate('', '+1 month', 'n');
        $year  = phive()->modDate('', '+1 month', 'Y');
        $this->mts_db->updateArray('credit_cards', ['exp_month' => $month, 'exp_year' => $year], ['id' => $card_id]);
        echo "This should return something:\n";
        print_r([$ud['id'], $months_to_expiry, $amount_sum]);
        $res = phive('Cashier/Mts')->arf('cCnearExpireGet', [$ud['id'], $months_to_expiry, $amount_sum]);
        print_r($res);

    }

    function mtsCCWithdrawal($user, $cents, $card_id, $supplier){
        $pid = $this->c->insertPendingCommon($user, $cents, array(
            'deducted_amount' => 0,
            'payment_method'  => $supplier,
            'ref_code'        => $card_id,
            'aut_code'        => $cents));
        $p     = $this->db->sh($user, 'id')->loadAssoc("SELECT * FROM pending_withdrawals WHERE id = $pid");
        $lang  = $user->getAttr('preferred_lang');

        $mts = new Mts($supplier, $user);
        $res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], $card_id, $user->data);

        //$res = $mts->failover('withdraw', $p['scheme'], $p['id'], $p['user_id'], $p['aut_code'], $p['ref_code'], $user->data);

        //$mts   = Mts::getInstance($supplier);
        //$res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], $card_id, $user->data);

        print_r($res);

        // Idempotency test
        //$res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], $card_id, $user->data);
        //print_r($res);
    }


    function mtsTestSkrillWithdrawal($user, $mb_email, $cents = 1000, $sub_supplier = ''){
        $pid = $this->c->insertPendingCommon($user, $cents, array(
            'mb_email'        => $mb_email,
            'deducted_amount' => 0,
            'payment_method'  => 'skrill',
            'aut_code'        => $cents,
            'scheme'          => $sub_supplier
        ));
        $p     = $this->db->sh($user, 'id', 'pending_withdrawals')->loadAssoc("SELECT * FROM pending_withdrawals WHERE id = $pid");
        $lang  = $user->getAttr('preferred_lang');
        $mts   = Mts::getInstance(Supplier::Skrill);
        $res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
            'currency'     => $user->getCurrency(),
            'email'        => $p['mb_email'],
            'subject'      => t('mb.withdraw.emailsubject', $lang),
            'note'         => t('mb.withdraw.emailnote', $lang),
            'sub_supplier' => $p['scheme']
        ]);
        $res = $mts->withdrawResult($res);
        print_r($res);
        // Idempotency test
        $res = $mts->withdrawResult($res);
        print_r($res);
    }

    function mtsTestSkrillDeposit($user, $mb_email, $cents, $sub_supplier = ''){
        $mts = Mts::getInstance(Supplier::Skrill, $user);

        $extra = [
            'skrill_email' => $mb_email,
            'email'        => $mb_email,
            'return_url_target'	=> 3,
            'cancel_url_target'	=> 3,
            'return_url'        => 'returnurl',
            'cancel_url'        => 'cancelurl'
        ];

        if(!empty($sub_supplier)){
            $extra['sub_supplier'] = $sub_supplier;
        }

        $result = $mts->deposit($user, $cents, $extra);
        print_r($result);
        return $this->mts_db->loadAssoc("SELECT * FROM transactions WHERE user_id = {$user->getId()} ORDER BY id DESC LIMIT 0,1");
    }

    function mtsSkrillNotification($mts_tr, $mb_email, $status = 2, $sub_supplier = ''){
        $amount          = empty($amount) ? $mts_tr['amount'] : $amount;
        $notification_id = rand(1000000, 1000000000);
        $data = [
            'transaction_id'    => $mts_tr['id'],
            'rec_payment_id'    => uniqid(),
            'mb_transaction_id' => rand(1000000, 10000000),
            'pay_from_email'    => $mb_email,
            'status'            => $status
        ];

        if(!empty($sub_supplier)){
            // NGP  for Rapid for instance
            $data['payment_type'] = $sub_supplier;
        }

        $content = http_build_query($data);

        $url = $this->mts_base_url."user/transfer/deposit/confirm?supplier=skrill";

        $res = phive()->post($url, $content, 'application/x-www-form-urlencoded', '', 'mts-skrill-notification');
        print_r([$res]);
        // Idempotency check
        $res = phive()->post($url, $content, 'application/x-www-form-urlencoded', '', 'mts-skrill-notification');
        print_r([$res]);

    }


    function testResetFreeMoney($u){
        $date 	        = date('Y-m-d', strtotime('-1 day'));
        $threshold_date = phive()->modDate($date, '-15 day');
        $sql            = $this->db;
        foreach(['cash_transactions', 'deposits'] as $tbl)
            $sql->delete($tbl, ['user_id' => $u->getId()], $u->getId());
        $this->c->insertTransaction($u, 1000, 14, '#welcome.deposit', 0, $threshold_date);
        if($sql->isSharded('cash_transactions')){
            $sql->loopShardsSynced(function($db, $shard, $id) use($threshold_date){
                phive('Cashier')->resetFreeMoney($db, $threshold_date);
            });
        }else
            phive('Cashier')->resetFreeMoney(false, $threshold_date);
        $trs = $sql->sh($u, 'id', 'cash_transactions')->loadArray("SELECT * FROM cash_transactions WHERE user_id = {$u->getId()}");
        print_r($trs);
    }

   function mtsGetWithdrawalCards($u){
        $mts = new Mts(Supplier::WireCard, $u->getId());
        $res = $mts->getCards(0, CardVerifyStatus::Verified, CardStatus::Active, [Supplier::WireCard], true);
        print_r($res);

    }

    function mtsGetCards($u){
        $mts = new Mts('', $u);
        $cards = $mts->getCards();
        print_r($cards);
    }

    function getWdTestCards(){
        return [
            ['card_num' => '4012000300001003', 'exp_year' => 2019, 'exp_month' => 1, 'active' => 1, 'verified' => 1, 'approved' => 1, 'customer_id' => 100, 'three_d' => 1],
            ['card_num' => '5413330300001006', 'exp_year' => 2019, 'exp_month' => 1, 'active' => 1, 'verified' => 1, 'approved' => 1, 'customer_id' => 100, 'three_d' => 1]
        ];
    }

    function mtsCreateCards($u, $cards){
        $res = [];
        foreach($cards as $c){
            $hash = phive('WireCard')->getSixFourAsterisk($c['card_num']);
            $res[$hash] = $card_id = $this->mts_db->insertArray('credit_cards', [
                'user_id'     => $u->getId(),
                'card_num'    => $hash,
                'customer_id' => $c['customer_id'],
                'exp_year'    => $c['exp_year'],
                'exp_month'   => $c['exp_month'],
                'three_d'     => $c['three_d'],
                'active'      => $c['active'],
                'verified'    => $c['verified'],
                'approved'    => $c['approved'],
                'country'     => $u->getCountry()
            ]);
            $this->mts_db->query("UPDATE credit_cards SET deleted_at = NULL WHERE id = $card_id");
        }
        return $res;
    }


    /*
       $c->mts_db->truncate('credit_cards', 'transactions');
       $cards = $c->getWdTestCards();
       $trs = [
       ['type' => 0, 'customer_id' => 100, 'amount' => 10000, 'supplier' => 'wirecard', 'status' => 10],
       ['type' => 0, 'customer_id' => 100, 'amount' => 20000, 'supplier' => 'wirecard', 'status' => 10]
       ];
       $c->mtsCreateCards($u, $cards);
       $c->mtsCreateTransactions($u, $cards, $trs);
     */
    function mtsCreateTransactions($u, $cards, $transactions){
        while($cards){
            $tmp  = array_shift($cards);
            $tr   = array_shift($transactions);
            $hash = phive('WireCard')->getSixFourAsterisk($tmp['card_num']);
            $card = $this->mts_db->loadAssoc("SELECT * FROM credit_cards WHERE card_num = '$hash'");
            $tr_id = $this->mts_db->insertArray('transactions', [
                'type'                    => $tr['type'],
                'customer_id'             => $tr['customer_id'],
                'customer_transaction_id' => $tr['customer_transaction_id'],
                'user_id'                 => $u->getId(),
                'card_id'                 => $card['id'],
                'reference_id'            => $tr['reference_id'],
                'extra_id'                => $tr['extra_id'],
                'amount'                  => $tr['amount'],
                'currency'                => $u->getCurrency(),
                'supplier'                => $tr['supplier'],
                'sub_supplier'            => $tr['sub_supplier'],
                'data'                    => $tr['data'],
                'status'                  => $tr['status']
            ]);
            $card['transaction_id'] = $tr_id;
            $this->mts_db->save('credit_cards', $card);
        }
    }

    function mtsValidate3dReturn($u, $supplier, $pares, $md, $ip = '195.158.92.198'){
        $res = Mts::getInstance($supplier, $u->getId())->setSupplier($supplier)->validate3dReturn(['MD' => $md, 'PaRes' => $pares, 'ip' => $ip]);
        print_r($res);
    }

    // Needs mtsPrepareCc() to run before invocation
    function mtsCcDeposit($u, $amount, $supplier){
        return $this->mtsDeposit($u, $amount, $supplier);
    }

    function approveKyc($u, $nid = null){
        $u->setAttr('nid', $nid ?? 123456789);
        $this->clearTable($u, ['rg_limits']);
        $u->deleteSettings('tmp_deposit_block', 'temporal_account', 'deposit_block', 'restrict', 'play_block', 'source_of_funds_status', 'fifo_psp', 'fifo_date', 'source_of_funds_status', 'proof_of_source_of_funds_activated', 'proof_of_wealth_activated', 'test_account');
        $u->verify();
    }

    function mtsPrepareCc($u, $amount, $cnum, $expireYear, $expireMonth, $cvv, $cipher = '', $is_pci = true){
        $params = [
            'generationtime' => date('Y-m-d\TH:i:s.000+00:00'),
            'number'         => $cnum,
            'expiryMonth'    => $expireMonth,
            'expiryYear'     => '20'.$expireYear,
            'holderName'     => trim($u->getFullName()),
            'cvc'            => $cvv
        ];

        $expireDate = "$expireMonth/$expireYear";

        $params_str = http_build_query($params);

        if(empty($cipher)){
            $site   = phive()->getSiteUrl();
            /*
               // This crap only works half the time, as of v63 chrome headless is useless /Henrik
               $url = "/usr/bin/google-chrome-stable --headless --disable-gpu --dump-dom $site/phive/modules/Cashier/html/get_adyen_cipher.php?$params_str";
               $cipher = shell_exec($url);
               $cipher_arr = explode('||', $cipher);
               print_r($cipher_arr);
               $cipher = $cipher_arr[3];
             */
            echo "\nGo to this url in a browser for the cipher, it will be valid for about 20 hours:\n\n$site/phive/modules/Cashier/html/get_adyen_cipher.php?$params_str\n\n";
            exit;

        }

        $this->mts->user_id = $u->getId();
        $this->mts->user    = $u;

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36';

        $arr = [
            'cardnumber'              => $cnum,
            'expirydate'              => $expireDate,
            'cv2'                     => $cvv,
            'encrypted_data'          => md5($cnum),
            'adyen_encrypted_data'    => $cipher,
            'worldpay_encrypted_data' => $cipher
        ];

        // If we want to test a scenario where we are not PCI compliant we can't send any card details so setting them to null.
        if(!$is_pci){
            unset($arr['cardnumber']);
            unset($arr['expirydate']);
            unset($arr['cv2']);
            unset($arr['encrypted_data']);
        }

        // Note that the md5 is not encryption but we don't care we just want a unique string when we test.
        $this->mts->setCreditCardData($arr);
    }

    /*
       Using devtestse:

       Non-3D card:
       array (
       'cardnumber' => '4111111111111111',
       'expirydate' => '08/18',
       'cv2' => '737'
       )

       $tc->mtsPrepareCc() must've been run before we call this.

     */
    function mtsAuthorize($u, $amount, $supplier = 'adyen'){
        $this->mts->setSupplier($supplier);
        $res = $this->mts->authorize($u, $amount);
        print_r($res);
        return $res;
    }

    function mtsCapture($u, $mts_id){
        $mts = new Mts(Supplier::Adyen, $u->getId());
        $mts->setExtraParams(['mts_id' => $mts_id]);
        $res = $mts->capture($u, $amount);
        print_r($res);
        return $res;
    }

    function enableRepeatsForUser($u){

    }

    function mtsRepeat($u, $repeat_id, $amount, $thold, $supplier, $cvc = 737, $type = 'recurring'){
        $mts = new Mts($supplier, $u->getId());
        $res = $mts->depositRepeat($repeat_id, $amount, $thold, $cvc, $type);
        print_r($res);
        return $res;
    }

    function mtsGetRepeated($u, $amount = null){
        $mts = new Mts();
        $res = $mts->getRepeated($u->getId(), $amount);
        print_r($res);
    }

    function mtsGetCardsForRepeat($u, $thold){
        $mts = new Mts(Supplier::WireCard, $u->getId());
        $res = $mts->getCardsForRepeatedDeposits($thold);
        print_r($res);
        return $res;
    }

    function mtsActiveAndValid($u, $cnum = '4012000300002001'){
        $mts = new Mts(Supplier::WireCard, $u->getId());
        $cnum = phive('WireCard')->getSixFourAsterisk($cnum);
        $res = $mts->isActiveAndValid($cnum);
        print_r([$res]);
    }



    /*
    $expDate = expireDate($values['expire_date']);

    $job_id = uniqid('');

    $data = ['@attributes' => ['xmlns:xsi' => 'http://www.w3.org/1999/XMLSchema-instance'],
             'W_REQUEST' => [
                 'W_JOB' => [
                     'JobID' => $job_id,
                     'BusinessCaseSignature' => $this->bCode,
                     'FNC_CC_ENROLLMENT_CHECK' => [
                         'FunctionID' => '',
                         'CC_TRANSACTION' => [
                             'CREDIT_CARD_DATA' => [
                                 'CreditCardNumber' => $values['ccard_num'],
                                 'CVC2' => $values['cvv'],
                                 'ExpirationYear' => $expDate[1],
                                 'ExpirationMonth' => $expDate[0],
                                 'CardHolderName' => ucfirst($values['first_name']).' '.ucfirst($values['last_name'])
                             ],
                             'CONTACT_DATA' => [
                                 'IPAddress' => !empty($values['ip']) ? $values['ip'] : $values['ip']
                             ],
                             'TransactionID' => $job_id,
                             'Amount' => ($values['amount'] * 100),
                             'Currency' => strtoupper($values['currency']),
                             'CountryCode' => strtoupper($values['country']),
                             'Usage' => Config::get('customerId').' enrollment check: '.$job_id,
                             'CORPTRUSTCENTER_DATA' => [
                                 'ADDRESS' => [
                                     'FirstName' => ucfirst($values['first_name']),
                                     'LastName' => ucfirst($values['last_name']),
                                     'Address1' => '',
                                     'Address2' => '',
                                     'City' => '',
                                     'ZipCode' => '',
                                     'State' => '',
                                     'Country' => '',
                                     'Phone' => '',
                                     'Email' => !empty($values['email']) ? $values['email'] : ''
                                 ]
                             ]
                         ]
                     ]
                 ]
             ]
    ];
     */
    function mtsEnroll($u, $cnum = '4012000300002001', $cvv = '001', $exp_date = '01/19'){
        $mts = new Mts(Supplier::WireCard, $u);
        $mts->setCreditCardData($cnum, $exp_date, $cvv);
        $res = $mts->enrollOnly($u, 100.00);
        print_r($res);
    }


    function removeAllFraudFlags($u){
        $this->db->sh($u, 'id')->query("DELETE FROM users_settings WHERE (setting LIKE '%flag%' OR setting LIKE '%source%') AND user_id = {$u->getId()}");
    }

    function deleteUserDeps($u){
        $this->db->delete('deposits', ['user_id' => uid($u)], uid($u));
    }

    // $c->testMajorityDate($u, $type1 = 'neteller', 'skrill', 'wirecard', '**** 1234', '**** 4567');
    // $c->testMajorityDate($u, $type1 = 'neteller', 'skrill', 'entercash', 'seb', 'swedbank');
    // $c->testMajorityDate($u, 'ecopayz', 'neteller', 'skrill', 'mupp@test.com', 'apa@test.com');
    function testMajorityDate($u, $type1 = 'neteller', $type2 = 'skrill', $type = 'wirecard', $card1 = '**** 1234', $card2 = '**** 4567'){

        //*
        // Test case 1, we deposit more with the first type.
        echo "Case 1 main PSPs, no other scheme or card data\n";
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, $type1, $this->randId());
        phive('Casino')->depositCash($u, 100, $type2, $this->randId());
        $res = $this->c->checkMajorityDeposits($u, $type1);
        $this->msg($res, "$type1 is blocked from withdrawals, this is wrong, should be allowed", '');
        $res = $this->c->checkMajorityDeposits($u, $type2);
        $this->msg($res == false, "$type2 is not blocked from withdrawals, this is incorrect, should be disallowed", '');

        // Test case 2, we deposit more with card1.
        echo "Case 2 same provider with two different cards \n";
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, $type, $this->randId(), 'visa', $card1);
        phive('Casino')->depositCash($u, 100, $type, $this->randId(), 'visa', $card2);
        $res = $this->c->checkMajorityDeposits($u, $type, $card1);
        $this->msg($res, "$card1 is blocked from withdrawals, this is wrong, should be allowed", '');
        $res = $this->c->checkMajorityDeposits($u, $type, $card2);
        $this->msg($res == false, "$card2 is not blocked from withdrawals, this is incorrect, should be disallowed", '');
        //*/

        // Test case 3, we deposit more with the first type than card 2.
        echo "Case 3 two different providers, one without card comparing with card\n";
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, $type1, $this->randId(), 'giropay');
        phive('Casino')->depositCash($u, 100, $type, $this->randId(), 'visa', $card2);
        //$res = $this->c->checkMajorityDeposits($u, $type1, '', 'giropay');
        //$this->msg($res, "$type1 is blocked from withdrawals, this is wrong, should be allowed", '');
        $res = $this->c->checkMajorityDeposits($u, $type, $card2);
        $this->msg($res, '', "$card2 is not blocked, this is incorrect");

        echo "Case 4, same card from different PSPs, only works if type1 and type are card suppliers, eg adyen and wirecard\n";
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, $type1, $this->randId(), 'visa', $card2);
        phive('Casino')->depositCash($u, 100, $type, $this->randId(), 'visa', $card2);
        $res = $this->c->checkMajorityDeposits($u, $type1, $card2, 'visa');
        $this->msg($res, "$card2 via $type1 is blocked from withdrawals, this is wrong, should be allowed", '');
        $res = $this->c->checkMajorityDeposits($u, $type, $card2, 'visa');
        $this->msg($res, "$card2 via $type is blocked from withdrawals, this is wrong, should be allowed", '');

        echo "Case 5, major type is Trustly and we check against Adyen with trustly as scheme \n";
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, 'adyen', $this->randId(), 'trustly');
        phive('Casino')->depositCash($u, 200, 'trustly', $this->randId(), 'abank');
        phive('Casino')->depositCash($u, 300, 'adyen', $this->randId(), 'visa', $card2);
        $res = $this->c->checkMajorityDeposits($u, 'adyen', $card2, 'visa');
        $this->msg($res, '', "$card2 via adyen is not blocked from withdrawals, this is wrong");
        $res = $this->c->checkMajorityDeposits($u, 'trustly');
        $this->msg($res, "Trustly is blocked from withdrawals, this is wrong\n", '');
        $res = $this->c->checkMajorityDeposits($u, 'adyen', '', 'trustly');
        $this->msg($res, "Trustly via adyen is blocked from withdrawals, this is wrong\n", '');
    }

    function testWithdrawalLimitFlag() {

        $sek_user = cu('devtestse');
        $this->removeAllFraudFlags($sek_user);
        $eur_user = cu('devtestfi');
        $this->removeAllFraudFlags($eur_user);

        echo __METHOD__ . " Case 0.\n";

        $eur_dep_cash = 100000;
        $sek_dep_cash = $eur_dep_cash * 10;
        phive('Casino')->depositCash($sek_user, $sek_dep_cash, 'neteller', $this->randId(), '');
        phive('Casino')->depositCash($eur_user, $eur_dep_cash, 'neteller', $this->randId(), '');
        phive('Cashier')->transactUser($sek_user, 1000000000000, "Test money." , null, null, 42);
        phive('Cashier')->transactUser($eur_user, 10000000000, "Test money." , null, null, 42);

        $eur_low_pending = 1000;
        $sek_low_pending = $eur_low_pending * 10;
        phive('Cashier')->insertPendingCommon($sek_user, $sek_low_pending, ['net_account' => 'test@test.com', 'payment_method' => 'neteller']);
        phive('Cashier')->insertPendingCommon($eur_user, $eur_low_pending, ['net_account' => 'test@test.com', 'payment_method' => 'neteller']);

        $res0sek = $sek_user->getSetting('withdraw_limit-fraud-flag');
        $this->msg(empty($res0sek), "SEK player has been flagged, this is incorrect.", '', true);

        $res0eur = $eur_user->getSetting('withdraw_limit-fraud-flag');
        $this->msg(empty($res0eur), "EUR player has been flagged, this is incorrect.", '', true);

        echo __METHOD__ . " Case 1.\n";

        phive('Cashier')->insertPendingCommon($sek_user, (($sek_dep_cash - $sek_low_pending) + (40000 * 1000)), ['net_account' => 'test@test.com', 'payment_method' => 'neteller']);
        phive('Cashier')->insertPendingCommon($eur_user, (($eur_dep_cash - $eur_low_pending) + (40000 * 100)), ['net_account' => 'test@test.com', 'payment_method' => 'neteller']);

        $res0sek = $sek_user->getSetting('withdraw_limit-fraud-flag');
        $this->msg(!empty($res0sek), "SEK player has not been flagged, this is incorrect.", '', true);

        $res0eur = $eur_user->getSetting('withdraw_limit-fraud-flag');
        $this->msg(!empty($res0eur), "EUR player has not been flagged, this is incorrect.", '', true);


    }

    function printMajorityFlags($u){
        $flags = $this->db->sh($u, 'id')->loadArray("SELECT * FROM users_settings WHERE (setting LIKE '%flag%' OR setting LIKE '%source%') AND user_id = {$u->getId()}");
        print_r($flags);
    }

    function testDeactivatedMajority($u, $psp1 = 'neteller', $psp2 = 'skrill'){
        $uid = uid($u);
        $ndb = $this->db->sh($uid);
        $this->dmapi_db->query("UPDATE documents SET status = 2");
        $ndb->query("DELETE FROM deposits WHERE user_id = ".$uid);
        // psp1 needs to be the majority
        $ndb->insertArray('deposits', ['user_id' => $uid, 'dep_type' => $psp1, 'amount' => 10000, 'status' => 'approved']);
        $ndb->insertArray('deposits', ['user_id' => $uid, 'dep_type' => $psp2, 'amount' => 1000, 'status' => 'approved']);
        if($this->c->checkMajorityDeposits($uid, $psp2) == false){
            echo "$psp2 is currently not majority, this is correct.\n";
        }
        if($this->c->checkMajorityDeposits($uid, $psp1) == false){
            echo "$psp1 is being blocked by majority, should not happen.\n";
        }
        // We deactivate the majority.
        $this->dmapi_db->query("UPDATE documents SET status = 5 WHERE tag = '{$psp1}pic'");
        phive('Dmapi')->resetDocs($uid);
        //if($this->c->canWithdraw($u, $psp1)){
        //    echo "$psp1 can be used to withdraw, should not happen.\n";
        //}

        // We check again, current majority should now be psp2.
        if($this->c->checkMajorityDeposits($uid, $psp2) == false){
            echo "$psp2 is being blocked by majority, should not happen.\n";
        }

    }

    function testMajorityFlag($u, $type1 = 'neteller', $type2 = 'skrill', $type = 'wirecard', $card1 = '**** 1234', $card2 = '**** 4567') {
        // TODO test the flag
        $ext_id = $this->randId();
        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);

        //*
        // We deposit 100 with card / bank, this should not result in a flag
        echo __METHOD__ . " Case 0.\n";
        $this->removeAllFraudFlags($u);
        phive('Casino')->depositCash($u, 100, $type, $this->randId(), $card1, $card1);
        $this->c->checkMajorityDeposits($u, $type, $card1, $card1);
        $res0 = $u->getSetting('majority-fraud-flag');
        $settings = $u->getAllSettings();
        //print_r($settings);
        //$this->printMajorityFlags($u);
        $this->msg(empty($res0) !== false, "A deposit with $type of 100 DID result in a fraud flag when checking $type", '', true);

        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);

        // This should not result in a flag
        echo __METHOD__ . " Case 1.\n";
        phive('Casino')->depositCash($u, 100, $type1, $ext_id, '');
        $this->c->checkMajorityDeposits($u, $type1);
        $res1 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res1) !== false, "A deposit with $type1 of 100 and 200 with $type2 DID result in a fraud flag when checking $type1", '', true);
        //$settings = $u->getAllSettings();
        //print_r($settings);

        // This should result in a flag
        echo __METHOD__ . " Case 2.\n";
        phive('Casino')->depositCash($u, 200, $type2, $ext_id, '');
        $this->c->checkMajorityDeposits($u, $type2);
        $res2 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res2) === false, "A deposit with $type1 of 100 and 200 with $type2 DID NOT result in a fraud flag when checking $type2", '', true);


        // We deposit 100 with card / bank, this should not result in a flag
        echo __METHOD__ . " Case 3.\n";
        $this->removeAllFraudFlags($u);
        phive('Casino')->depositCash($u, 100, $type, $this->randId(), $card1, $card1);
        $this->c->checkMajorityDeposits($u, $type, $card1, $card1);
        $res3 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res3) !== false, "A deposit with $type of 100 DID result in a fraud flag when checking $type", '', true);

        //exit;

        // We deposit another 200 with card / bank, this should result in a flag
        echo __METHOD__ . " Case 4.\n";
        phive('Casino')->depositCash($u, 200, $type, $this->randId(), $card1, $card1);
        $this->c->checkMajorityDeposits($u, $type, $card1, $card1);
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4) === false, "A deposit with $type|$card1 of another 200 DID NOT result in a fraud flag when checking", '', true);

        // We deposit 200 with another card / bank, this should not result in a flag
        echo __METHOD__ . " Case 5.\n";
        $this->removeAllFraudFlags($u);
        phive('Casino')->depositCash($u, 200, $type, $this->randId(), $card2, $card2);
        $this->c->checkMajorityDeposits($u, $type, $card2, $card2);
        $res5 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res5) !== false, "A deposit with $type|$card2 of 200 DID result in a fraud flag when checking $type", '', true);

        // We deposit another 200 with the second card / bank, this should result in a flag
        echo __METHOD__ . " Case 6.\n";
        phive('Casino')->depositCash($u, 200, $type, $this->randId(), $card2, $card2);
        $this->c->checkMajorityDeposits($u, $type, $card2, $card2);
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4) === false, "A deposit with $type of another 200 with $card2 DID NOT result in a fraud flag when checking", '', true);

        echo __METHOD__ . " Case 7 We test the new Trustly via Adyen logic, Trustly -> Trustly via Adyen\n";
        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, 'trustly', $this->randId());
        $this->c->checkMajorityDeposits($u, 'trustly');
        // Here we revert to legacy in order to test that:
        $u->deleteSetting('majority_source_current');
        $u->setSetting('majority_flag_current', 'trustly');
        phive('Casino')->depositCash($u, 300, 'adyen', $this->randId(), 'trustly');
        $this->c->checkMajorityDeposits($u, 'adyen', '', 'trustly');
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4), 'A deposit with Trustly followed by a deposit with Trustly via Adyen should NOT result in a flag.', '');
        //$this->printMajorityFlags($u);

        echo __METHOD__ . " Case 8 We test the new CC via several PSPs logic\n";
        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, 'wirecard', $this->randId(), 'visa', $card2);
        $this->c->checkMajorityDeposits($u, 'trustly');
        phive('Casino')->depositCash($u, 300, 'adyen', $this->randId(), 'visa', $card2);
        $this->c->checkMajorityDeposits($u, 'adyen', $card2);
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4), 'A deposit with Wirecard followed by a deposit with Adyen with the same card should NOT result in a flag.', '');

        echo __METHOD__ . " Case 9 We test the new Trustly via Adyen logic, Trustly via Adyen -> Trustly\n";
        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, 'adyen', $this->randId(), 'trustly');
        $this->c->checkMajorityDeposits($u, 'adyen');
        // Here we revert to legacy in order to test that:
        $u->deleteSetting('majority_source_current');
        $u->setSetting('majority_flag_current', 'adyen');
        $u->setSetting('majority_sub_flag_current', 'trustly');
        phive('Casino')->depositCash($u, 300, 'trustly', $this->randId());
        $this->c->checkMajorityDeposits($u, 'trustly');
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4), 'A deposit with Trustly via Adyen followed by a deposit with Trustly should NOT result in a flag.', '');
        //*/

        echo __METHOD__ . " Case 9 We test Skrill without scheme followed by Skrill with scheme\n";
        $this->removeAllFraudFlags($u);
        $this->deleteUserDeps($u);
        phive('Casino')->depositCash($u, 200, 'skrill', $this->randId());
        $this->c->checkMajorityDeposits($u, 'skrill');
        // Here we revert to legacy in order to test that:
        $u->deleteSetting('majority_source_current');
        $u->setSetting('majority_flag_current', 'skrill');
        $u->setSetting('majority_sub_flag_current', 'skrill');
        phive('Casino')->depositCash($u, 300, 'skrill', $this->randId(), 'card', 'apa@apan.com');
        $this->c->checkMajorityDeposits($u, 'skrill');
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4), '', 'A deposit with Skrill followed by a new deposit with new email should result in a flag.');
        phive('Casino')->depositCash($u, 350, 'neteller', $this->randId());
        $u->deleteSetting('majorit-fraud-flag');
        $this->c->checkMajorityDeposits($u, 'neteller');
        $res4 = $u->getSetting('majority-fraud-flag');
        $this->msg(empty($res4), '', '500 in total through Skrill over all emails followed by Neteller should result in a flag.');

        //$this->printMajorityFlags($u);

        //$settings = $u->getAllSettings();
        //print_r($settings);

    }

    function resetSupplier($supplier){
        $sql = $this->db;
        $sql->shs('', '', null, 'pending_withdrawals')->query("DELETE FROM pending_withdrawals WHERE payment_method = '$supplier'");
        $sql->shs('', '', null, 'deposits')->query("DELETE FROM deposits WHERE dep_type = '$supplier'");
        $sql->truncate('trans_log', 'cash_transactions');
        $mts = phive('SQL')->doDb('mts');
        $mts->truncate('transaction_logs');
        $mts->query("DELETE FROM transactions WHERE supplier = '$supplier'");
    }


    function insertEnterCashPending($u, $amount){
        $pending = [
            'user_id'             => $u->getId(),
            'payment_method'      => 'entercash',
            'amount'              => $amount,
            'currency'            => $u->getCurrency(),
            'bank_name'           => 'SEB',
            'bank_clearnr'        => '5304',
            'bank_account_number' => '0249096',
            'aut_code'            => $amount
        ];
        return $this->db->sh($pending, 'user_id', 'pending_withdrawals')->insertArray('pending_withdrawals', $pending);
    }

    /*
       Status: 0=initiated, 1=pending, 2=3D pending, 10=success, -1=failed
       Type: '0 = deposit, 1 = withdrawal'
     */
    function insertMtsTransaction($u, $supplier, $amount, $status = 1, $ext_id = '', $type = 0, $card_id = 0, $sub_supplier = ''){
        $this->db->useDb('mts');
        $arr = [
            'type'                     => $type,
            'customer_id'              => 100,
            'customer_transaction_id'  => '',
            'user_id'                  => $u->getId(),
            'card_id'                  => $card_id,
            'reference_id'             => $ext_id,
            'amount'                   => $amount,
            'currency'                 => $u->getCurrency(),
            'supplier'                 => $supplier,
            'sub_supplier'             => $sub_supplier,
            'status'                   => $status,
            'created_at'               => phive()->hisNow(),
            'updated_at'               => phive()->hisNow()
        ];
        $res = $this->db->insertArray('transactions', $arr);
        $this->db->useDb();
        $arr['id'] = $res;
        return $arr;
    }

    /*

       {
       "id": 887924,
       "type": 1,
       "customer_id": 100,
       "customer_transaction_id": "",
       "user_id": 5423691,
       "card_id": 0,
       "reference_id": "12498731",
       "extra_id": "12498731",
       "amount": 20000,
       "currency": "AUD",
       "supplier": "citadel",
       "sub_supplier": "",
       "data": "{\"error_message\":\"\",\"error_number\":0,\"remittance_detail_list\":{\"RemittanceDetail\":{\"internal_reference\":\"12498731\",\"merchant_reference\":\"100.887924\",\"result_code\":0,\"transaction_date\":\"2017-05-04T00:00:00-07:00\"}},\"request_date\":\"2017-05-04T03:30:21.466-07:00\",\"request_id\":70399459,\"response_date\":\"2017-05-04T03:30:24.704-07:00\"}",
       "status": 10,
       "created_at": "2017-05-04 10:30:21",
       "updated_at": "2017-05-04 10:33:02"
       }

       <?xml version="1.0" encoding="UTF-8"?>
        <TransactionDetailsDocument>
            <transaction_list>
                <TransactionDetail>
                    <merchant_store_id>75726299</merchant_store_id>
                    <transaction_date>2017-04-26</transaction_date>
                    <transaction_type>SD</transaction_type>
                    <transaction_subtype>0</transaction_subtype>
                    <method>instant_credit</method>
                    <amount>10.0000</amount>
                    <currency_code>AUD</currency_code>
                    <internal_reference>218008</internal_reference>
                    <original_internal_reference/>
                    <merchant_reference>100.689068</merchant_reference>
                    <journal_notes>Instant payment deposit Orig. Tx Details: MT0000327924</journal_notes>
                    <journal_entry_number>1</journal_entry_number>
                    <country>AU</country>
                    <group_id>105720</group_id>
                    <entry_type>credit</entry_type>
                </TransactionDetail>
            </transaction_list>
        </TransactionDetailsDocument>
     */
    //SD  = deposit
    //SDR = chargeback of deposit
    //SWR = failed withdrawal
    function mtsCitadelNotification($mts_tr, $u, $type = 'SD'){

        $map = [
            'SD'  => 'credit',
            'SDR' => 'debit',
            'SWR' => 'credit'
        ];

        $method = $map[$type];

        $base = [
            'merchant_store_id'           => 75726299,
            'transaction_date'            => phive()->today(),
            'transaction_type'            => $type,
            'transaction_subtype'         => 0,
            'method'                      => 'instant_'.$method,
            'amount'                      => ($mts_tr['amount'] / 100).'0000',
            'currency_code'               => $mts_tr['currency'],
            'internal_reference'          => $mts_tr['reference_id'],
            'merchant_reference'          => $mts_tr['customer_id'].'.'.$mts_tr['id'],
            'journal_notes'               => 'Instant payment deposit Orig. Tx Details: MT0000327924',
            'journal_entry_number'        => 1,
            'country'                     => $u->getCountry(),
            'group_id'                    => 105720,
            'entry_type'                  => $method
        ];

        if($type == 'SWR'){
            $base['original_internal_reference'] = $mts_tr['reference_id'];
        }

        $inner_xml = phive()->xmlFromArr($base);
        $xml = 'xml='.urlencode('<?xml version="1.0" encoding="UTF-8" ?><TransactionDetailsDocument><transaction_list><TransactionDetail>'.$inner_xml.'</TransactionDetail></transaction_list></TransactionDetailsDocument>');

        $url = $this->mts_base_url."user/transfer/deposit/confirm?supplier=citadel";

        return phive()->post($url, $xml, 'application/x-www-form-urlencoded', ['User-Agent: Jakarta Commons-HttpClient/3.0.1'], 'mts-citadel-notification');
    }

    function mtsEcopayzNotification($u, $mts_tr){
        $pwd = '6BYUez3PPQOw';
        $amount = $mts_tr['amount'] / 100;
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <SVSPurchaseStatusNotificationRequest>
                <StatusReport>
                    <StatusDescription/>
                    <Status>4</Status>
                    <SVSTransaction>
                        <SVSCustomerAccount>1100185618</SVSCustomerAccount>
                        <ProcessingTime>2017-06-01 12:18:08</ProcessingTime>
                        <Result>
                            <Description></Description>
                            <Code></Code>
                        </Result>
                        <BatchNumber>7015839</BatchNumber>
                        <Id>{$mts_tr['reference_id']}</Id>
                    </SVSTransaction>
                    <SVSCustomer>
                        <IP>195.158.92.198</IP>
                        <PostalCode>Malta1335</PostalCode>
                        <Country>MT</Country>
                        <LastName>Test</LastName>
                        <FirstName>Panda Media Ltd</FirstName>
                    </SVSCustomer>
                </StatusReport>
                <Request>
                    <MerchantFreeText/>
                    <CustomerIdAtMerchant>{$u->getId()}</CustomerIdAtMerchant>
                    <MerchantAccountNumber>109807</MerchantAccountNumber>
                    <Currency>{$u->getCurrency()}</Currency>
                    <Amount>$amount</Amount>
                    <TxBatchNumber>0</TxBatchNumber>
                    <TxID>{$mts_tr['id']}</TxID>
                </Request>
                <Authentication>
                    <Checksum>$pwd</Checksum>
                </Authentication>
            </SVSPurchaseStatusNotificationRequest>";

        $checksum = md5($xml);
        $new_xml = preg_replace('|<Checksum>[^<]+</Checksum>|', '<Checksum>'.$checksum.'</Checksum>', $xml);

        echo "\n\n Sending: $new_xml \n\n";

        $url  = $this->mts_base_url."user/transfer/deposit/confirm?supplier=ecopayz";
        $body = http_build_query(['XML' => $new_xml, 'status' => 'transfer']);
        $res = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'mts-ecopayz-notification');

        print_r($res);

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <SVSPurchaseStatusNotificationRequest>
                <TransactionResult>
                    <ErrorCode>0</ErrorCode>
                    <Message>OK</Message>
                    <SvsTxID>{$mts_tr['reference_id']}</SvsTxID>
                    <ClientTransactionID>{$mts_tr['id']}</ClientTransactionID>
                </TransactionResult>
            </SVSPurchaseStatusNotificationRequest>";


        echo "\n\n Sending: $xml \n\n";

        $url  = $this->mts_base_url."user/transfer/deposit/confirm?supplier=ecopayz";
        $res = phive()->post($url.'&status=callback', $xml, 'application/x-www-form-urlencoded', '', 'mts-ecopayz-notification');

        print_r($res);
    }

    function mtsWithdrawalEcopayz($u, $cents, $acc_num = '1100185618'){

        $p_id = phive('Cashier')->insertPendingCommon($u, $cents, array(
            'payment_method'  => 'ecopayz',
            'net_account'     => $acc_num,
            'deducted_amount' => 0,
            'aut_code'        => $cents));

        $p = $this->c->getPending($p_id);
        $p['status'] = 'pending';
        $this->db->sh($u, 'id')->save('pending_withdrawals', $p);

        $mts = Mts::getInstance(Supplier::EcoPayz);

        echo "Paying first withdrawal\n";
        $this->c->payPending($p_id, $cents);

        $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, ['account_id' => $p['net_account'], 'currency' => $p['currency']]);
        list($mts_id, $ext_id, $result) = $mts->withdrawResult($res);
        print_r([$mts_id, $ext_id, $result]);

        echo "Paying second withdrawal with same id\n";
        $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, ['account_id' => $p['net_account'], 'currency' => $p['currency']]);
        list($mts_id, $ext_id, $result) = $mts->withdrawResult($res);
        print_r([$mts_id, $ext_id, $result]);

        $mts_db = phive('SQL')->doDb('mts');
        $res = $mts_db->loadArray("SELECT * FROM transactions WHERE customer_transaction_id = $p_id");
        print_r($res);
    }

    function mtsDepositEcopayz($u, $amount){
        $res = $this->mtsDeposit($u, $amount, 'ecopayz');
        print_r($res);
    }

    function mtsDeposit($u, $amount, $supplier, $params = []){
        $this->mts->setExtraParams(array_merge($this->mts->extra_params, $params));
        $this->mts->setSupplier($supplier);
        $res = $this->mts->deposit($u, $amount);
        print_r([$res]);
        return $res;
    }

    function mtsDepositVoucher($supplier, $u, $amount, $code = '123'){
        $this->mtsDeposit($u, $amount, $supplier, ['code' => $code]);
    }

    function mtsDepositAllStandard($u, $amount){
        foreach([Supplier::Citadel, Supplier::PayGround, Supplier::EcoPayz] as $psp){
            echo "$psp\n";
            $this->mtsDeposit($u, $amount, $psp);
        }
    }

    function mtsDepositAll($u, $amount){
        $this->mtsDepositAllStandard($u, $amount);
        echo "Paysafe\n";
        $this->mtsPaysafeDeposit($u, $amount);
        echo "Skrill\n";
        $this->mtsTestSkrillDeposit($u, 'henrik.sarvell@videoslots.com', $amount);
        echo "Entercash\n";
        $this->mtsTestEntercashDeposit($amount);
        $u_fi = cu('devtestfi');
        echo "Neteller\n";
        $this->mtsDepositNeteller($u_fi, $amount);
        echo "Neosurf\n";
        $this->mtsDepositVoucher(Supplier::Neosurf, $u, $amount);
        echo "Flexepin\n";
        $this->mtsDepositVoucher(Supplier::Flexepin, $u, $amount);
        echo "Cubits\n";
        $this->mtsDepositCubits($u, $amount);
    }

    /*
       {
       "transaction_id": "261951",
       "user_id": "5235889",
       "reference_id": "C816720147039635469341",
       "amount": "1000",
       "supplier": "wirecard",
       "card_num": "401200******6002",
       "card_duplicate": {
       "user_id": "5207828"
       },
       "type": "visa",
       "new_card": false,
       "extra": [

       ]
       }
     */
    function depositNotify($arr = [], $u = null){
        if(!is_string($arr)){
            if(empty($arr)){
                $arr = [
                    'transaction_id' => rand(1000000, 1000000000),
                    'user_id' => $u->getId(),
                    'reference_id' => uniqid(),
                    'amount' => 1000,
                    'supplier' => 'wirecard',
                    "card_num" => "401200******6002",
                    "type" => "visa",
                    "new_card" => false,
                    "extra" => []
                ];
            }
            $str = json_encode($arr);
        }else
            $str = $arr;
        $body = http_build_query(['data' => base64_encode($str)]);
        $url  = phive()->getSiteUrl().'/phive/modules/Cashier/html/deposit_notify.php';
        $res  = phive()->post($url, $body, 'application/x-www-form-urlencoded', '', 'depositnotify_test');
        echo $res;
    }

    function testRevertDeposit($u, $supplier){
        $amount = 10000;
        $arr = [];
        $casino = phive('Casino');
        $ext_id = uniqid();
        $mts_id = rand(1000000, 1000000000);
        $casino->depositCash($u, $amount, $supplier, $ext_id, '', '', '', false, 'approved', null, $mts_id);
        $new_id = $casino->did;
        $arr['extra']          = ['cancelled' => true];
        $arr['transaction_id'] = $mts_id;
        $arr['reference_id']   = $new_id;
        $arr['user_id']        = $u->getId();
        $arr['supplier']       = $supplier;
        $arr['amount']         = $amount;
        $this->depositNotify($arr, $u);
    }

    function testQtrans($uname, $amount = 100){
        $u = cu($uname);
        $descr = uniqid();
        $this->c->qTrans($u->getId(), $amount, $descr, 31);
        $this->c->autoPay(31);
        $latest = $this->db->sh($u, 'id', 'cash_transactions')->loadAssoc("SELECT * FROM cash_transactions WHERE user_id = {$u->getId()} ORDER BY id DESC LIMIT 0,1");
        print_r($latest);
    }


    function insertPendingBank($uname, $amount = 10000){
        $user = cu($uname);
        $insert = [];
        $result = $this->c->insertPendingBank($user, $amount, $insert, 'bank');
    }


  function testBetWin($user, $bet, $win){
    $net = TestPhive::getModule('Netent');
    $mg_id = rand(1000000, 1000000000);
    $r_id = rand(1000000, 1000000000);
    $uid = 'vs_'.$user->getId();
    $gid = 'starburst_sw';
    $sid = uniqid();
    $net->setGame($sid, "netent_$gid");
    $net->url = "http://www.videoslots.loc/diamondbet/soap/netent.php";
    $net->caller_id = 'testmerchant';
    $net->caller_pwd = 'testing';
    echo $net->withdrawAndDeposit($uid, $gid, $mg_id, $r_id, $bet.'.00', $win.'.00');
  }

    function lowwager($uname, $out_date, $betsum, $dep_sum){
        $this->db->truncate('bets', 'cash_transactions', 'pending_withdrawals', 'users_settings');
        $u = cu($uname);
        $this->db->insertArray('pending_withdrawals', ['user_id' => $u->getId(), 'amount' => 100]);
        //$this->c->insertTransaction($u->getId(), 100, 8, 'test', 0, $out_date." 00:00:00");
        $this->c->insertTransaction($u->getId(), $dep_sum, 3, 'test', 0);
        $this->db->insertArray('bets', ['amount' => $betsum, 'user_id' => $u->getId(), 'mg_id' => uniqid()]);
        sleep(1);
        $this->c->insertPendingCommon($u, 100, ['payment_method' => 'skrill']);

    }

    function chargeback($uname){
        $u = cu($uname);
        phive('SQL')->truncate('cash_transactions');
        $u->setAttr('cash_balance', 100);
        echo "Testing charging back 200 with 100 balance.\n";
        phive('Cashier')->chargeback($u, 200, 'test');
        $rows = phive('SQL')->loadArray("SELECT * FROM cash_transactions");
        echo "Result:\n";
        print_r($rows);
        $balance = cu($uname)->getBalance();
        echo "User balance: $balance\n";
        phive('SQL')->truncate('cash_transactions');
        $u->setAttr('cash_balance', 200);
        echo "Testing charging back 100 with 200 balance.\n";
        phive('Cashier')->chargeback($u, 100, 'test');
        echo "Result:\n";
        $rows = phive('SQL')->loadArray("SELECT * FROM cash_transactions");
        print_r($rows);
        $balance = cu($uname)->getBalance();
        echo "User balance: $balance\n";
    }

    function payPending($u, $pid){
        phive('Site/Publisher')->single('main', 'Cashier', 'approvePending', [$_POST['id'], (float)trim($_POST['amount']), uid($u)]);

    }

    function testNetworkMethods(){
        echo "getPspNetwork with devtestno and paysafe: {$this->c->getPspNetwork(cu('devtestno'), 'paysafe')} \n";
        echo "getPspNetwork with devtestgb and paysafe: {$this->c->getPspNetwork(cu('devtestgb'), 'paysafe')} \n";

        echo "Get network mapping\n";
        print_r($this->c->getPspNetworkMapping(cu('devtestgb')));
    }

    function testWithdrawDepositAllowed($user, $action = 'deposit'){
        foreach(phiveApp(PspConfigServiceInterface::class)->getPspSetting() as $alt => $config){
            if($this->c->WithdrawDepositAllowed($user, $alt, $action)){
                echo "$alt: OK\n";
            } else {
                echo "$alt: NOT OK\n";
            }
        }
    }

    function testRemoveLossLimits(){
        $u = cu('devtestuk');
        $uid = $u->data['id'];
        $action = "lgaloss-lim";
        $str = "loss";
        $rstr = "losslim";
        $sum = 0;
        $lim = 150;
        $new_duration = 'month';
        $do_action = "updated";
        $today = phive()->today();
        phive('SQL')->delete('triggers_log', ["user_id" => $uid, 'trigger_name' => 'RG21'], $uid);;
        phive('SQL')->delete('users_settings', ["user_id" => $uid, 'setting' => 'lgaloss-lim'], $uid);
        phive('SQL')->sh($uid)->insertArray('users_settings', ['setting' => 'lgaloss-lim', 'value' => 10, 'user_id' => $uid]);
        phive('DBUserHandler')->rgAction($u, $action, $str, $rstr, $sum, $lim, $new_duration, $do_action);
        $trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log where user_id = $uid AND trigger_name ='RG21'");
        if (!empty($trigger))
            echo "RG21,Increasing the loss limit ok";
        else echo "ERROR !!!!!!!! Increasing the loss limit";
        echo chr(10);
        phive('SQL')->delete('triggers_log', ["user_id" => $uid, 'trigger_name' => 'RG21'], $uid);;
        $do_action = "remove";
        phive('DBUserHandler')->rgAction($u, $action, $str, $rstr, $sum, $lim, $new_duration, $do_action);
        $trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log where user_id = $uid AND trigger_name ='RG21' and descr = 'Removed loss limit'  ");
        if (!empty($trigger))
            echo "RG21,Removing the loss limit ok";
        else echo "ERROR !!!!!!!!,Removing the loss limit";
        echo chr(10);
        $lim = 25;
        phive('SQL')->delete('triggers_log', ["user_id" => $uid, 'trigger_name' => 'RG21'], $uid);;
        $do_action = "updated";
        phive('DBUserHandler')->rgAction($u, $action, $str, $rstr, $sum, $lim, $new_duration, $do_action);
        $trigger = phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log where user_id = $uid AND trigger_name = 'RG21'");
        if (empty($trigger))
            echo "ok, no trigger on  decreasing the limits ";
        else  echo "ERROR !!!!!!!!  decreasing the loss limit";
        echo chr(10);

    }


    function testFailedDeposit(){

        $uid = 5662609;
        $ext_id = uniqid();
        $deposit = [
            'user_id' => $uid,
            'type' => 0,
            'supplier' => 'wirecard',
            'scheme' => 'mc',
            'account' => '4012 00** **** 1003',
            'currency' => 'EUR',
            'amount' => 1000,
            'ext_id' => $ext_id,
            'mts_id' => 25896,
            'error_code' => 110,
            'data' => 'error_message'
        ];
        phive('SQL')->sh($uid, '', 'failed_transactions')->insertArray('failed_transactions', $deposit);
        phive('Cashier/Rg')->onFailedDeposit($uid);
        $today = phive()->today();
        $empty_trigger = phive('SQL')->loadAssoc("SELECT * FROM triggers_log where user_id = $uid AND trigger_name='RG18' and date(created_at)=$today ");
        if (empty($empty_trigger))
            echo 'OK, not enough amount to trigger';
        else echo "ERROR!!!!!!!!!!!";
        echo chr(10);
        $ext_id = uniqid();
        $deposit2 = [
            'user_id' => $uid,
            'type' => 0,
            'supplier' => 'wirecard',
            'scheme' => 'mc',
            'account' => '4012 00** **** 1003',
            'currency' => 'EUR',
            'amount' => 9001,
            'ext_id' => $ext_id,
            'mts_id' => 25897,
            'error_code' => 110,
            'data' => 'error_message'
        ];
        phive('SQL')->sh($uid, '', 'failed_transactions')->insertArray('failed_transactions', $deposit2);
        phive('Cashier/Rg')->onFailedDeposit($uid);
        $trigger = phive('SQL')->loadAssoc("SELECT * FROM triggers_log where user_id = $uid AND trigger_name='RG18' and date(created_at)=$today ");
        if (empty($trigger))
            echo 'OK, trigger RG18';
        else  echo 'ERROR!!!!!!!!!!, trigger RG18';
        echo chr(10);

    }

    function testBigWinMultiplier($user_id) {
        $game = phive('MicroGames')->getByGameRef('netent_starburst_not_mobile_sw');
        $user = cu($user_id);
        $rg = phive('Cashier/Rg');
        $rg->onBet($user, ['amount'=> 20], $game);
        $rg->onWin($user, ['amount'=> 100000], $game);

        sleep(3);
        $today = phive()->today();
        $sql = "SELECT * FROM triggers_log where user_id = '$user_id' AND trigger_name = 'RG14' and date(created_at)='$today'";
        $trigger = $this->db->sh($user_id)->loadAssoc($sql);

        if (empty($trigger)) {
            echo "ERROR! RG14 failed to trigger.\n";
            return false;
        }

        echo "OK! RG14 triggered\n";
        return true;
    }

    /**
     * RG14 - Big Win at the Sportsbook
     * Customer have triggered a big win that multiplied the last `single` bet at least X times the current day.
     *
     * @return bool
     */
    function testBigWinMultiplierAtTheSportsbook()
    {
        $trigger = 'RG14';
        $interval = DateInterval::createFromDateString('15 years');
        $dob = (new DateTime('now'))->sub($interval);
        $user = $this->getTestPlayer();
        $user->updateData(['dob' => $dob->format('Y-m-d')]);
        $user_id = $user->getId();
        $body = ['user_id' => $user_id, 'jurisdiction' => $user->getJurisdiction()];
        phive()->postToBoApi("/risk-profile-rating/calculate-score/all", $body);
        sleep(4);
        $ticket_id = 111111;

        $sport_transactions = [
            [
                "ext_id" => "546c0547-9df4-4ecb-8362-90885b412982",
                "user_id" => $user_id,
                "ticket_id" => $ticket_id,
                "ticket_type" => "single",
                "ticket_settled" => 1,
                "settled_at" => "2023-06-05T05:56:02.000Z",
                "amount" => 5000000,
                "currency" => "EUR",
                "balance" => 0,
                "bet_type" => "win",
                "result" => 1,
            ],
            [
                "ext_id" => "546c0547-9df4-4ecb-8362-90885b412982",
                "user_id" => $user_id,
                "ticket_id" => $ticket_id,
                "ticket_type" => "single",
                "ticket_settled" => 0,
                "settled_at" => null,
                "amount" => 1000,
                "currency" => "EUR",
                "balance" => 8,
                "bet_type" => "bet",
                "result" => 0,
            ],
        ];

        phive('SQL')->insert2DArr('sport_transactions', array_values($sport_transactions));
        phive('Cashier/Rg')->bigWinMultiplierAtTheSportsbook($user, $ticket_id);
        sleep(3);

        $log = $user->hasTriggerFlag($trigger);
        $info = "User ID: {$user_id}" . chr(10);

        // clean up DB
        $this->cleanupTestPlayer(
            $user_id,
            [
                'sport_transactions',
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (! $log) {
            echo "$trigger ERROR  !!!!! $info";
            return false;
        }

        echo "$trigger OK. $info" . chr(10);
        return true;
    }

    public function testHadBigWinOfXTheirBet()
    {
        $trigger = 'RG76';
        $user = $this->getTestPlayer();
        $user_id = $user->getId();

        $game = phive('MicroGames')->getByGameRef('netent_starburst_not_mobile_sw');
        $rg = phive('Cashier/Rg');
        $rg->onBet($user, ['amount'=> 25], $game);
        $rg->onWin($user, ['amount'=> 100000], $game);
        $this->printOutputData($trigger, true);

        sleep(3);
        $log = $this->doesUserHaveTrigger($user_id, $trigger);
        $this->msg($log);
        $info = "User ID: {$user_id}" . chr(10);

        $this->cleanupTestPlayer(
            $user_id,
            [
                'triggers_log',
                'actions' => 'target',
                'users_settings',
                'risk_profile_rating_log',
            ]
        );

        if (!$log) {
            echo "$trigger ERROR  !!!!! $info";
            return false;
        }

        echo "$trigger OK. $info" . chr(10);
        return true;
    }

    function testDoubleOther($u1_obj, $u2_obj){
        $email = 'a@b.com';
        $this->db->shs()->query("DELETE FROM users_settings WHERE setting = 'paypal_email'");
        $this->db->shs()->query("DELETE FROM pending_withdrawals WHERE payment_method = 'paypal'");

        $u2_obj->setSetting('paypal_email', $email);
        $res = $this->c->getDuplicateAccountUsage($u1_obj->getId(), 'paypal', $email);
        print_r($res);

        $this->db->sh($u1_obj)->insertArray('pending_withdrawals', [
            'user_id' => $u1_obj->getId(),
            'payment_method' => 'paypal',
            'amount' => 2000,
            'status' => 'approved'
        ]);

        $this->db->sh($u1_obj)->insertArray('pending_withdrawals', [
            'user_id' => $u1_obj->getId(),
            'payment_method' => 'paypal',
            'amount' => 2000,
            'mb_email' => $email,
            'status' => 'approved'
        ]);

        $this->db->sh($u1_obj)->insertArray('pending_withdrawals', [
            'user_id' => 123,
            'payment_method' => 'paypal',
            'amount' => 2000,
            'mb_email' => $email,
            'status' => 'approved'
        ]);

        $u1_obj->setSetting('mvcur_old_id', '123');

        $this->db->sh($u2_obj)->insertArray('pending_withdrawals', [
            'user_id' => $u2_obj->getId(),
            'payment_method' => 'paypal',
            'amount' => 2000,
            'mb_email' => $email,
            'status' => 'approved'
        ]);

        $res = $this->c->getDuplicateAccountUsage($u1_obj->getId(), 'paypal', $email);
        print_r($res);
    }


    function saveMtsRecurring($mts_tr, $u = null){
        if(empty($mts_tr) && !empty($u)){
            $mts_tr = $this->getMtsTr($u);
        }

        $insert = [
            'customer_id' => 100,
            'user_id' => $mts_tr['user_id'],
            'card_id' => $mts_tr['card_id'],
            'supplier' => $mts_tr['supplier'],
            'ext_id' => uniqid(),
            'currency' => $mts_tr['currency'],
            'amount' => $mts_tr['amount'],
            'sub_supplier' => $mts_tr['sub_supplier']
        ];

        $this->mts_db->insertArray('recurring_transactions', $insert);
    }

    function mtsCCWithdrawalSetup($u, $supplier){
        $mts_db = $this->db->doDb('mts');

        $cid = $mts_db->insertArray('credit_cards', ['user_id' => $u->getId(), 'card_num' => '4444*********', 'customer_id' => 100]);
        $mts_db->insertArray('recurring_transactions', [
            'customer_id' => 100,
            'customer_id' => 100,
            'user_id' => $u->getId(),
            'card_id' => $cid,
            'supplier' => $supplier,
            'ext_id' => 123,
            'currency' => $u->getCurrency(),
            'amount' => 2000
        ]);

        return $cid;
    }

    function mtsDisapproveWithdrawal($u){
        $p = $this->getLatestPending($u);
        print_r($p);
        $res = Mts::getInstance('zimpler')->transferRpc('disapproveWithdrawal', ['transaction_id' => $p['mts_id']]);
        print_r(['withdraw_disapprove_result' => $res]);
    }

    function insertWithdrawal($u_obj, $amount, $psp, $scheme = '', $status = 'approved', $extra = []){
        $insert = [
            'user_id' => $u_obj->getId(),
            'payment_method' => $psp,
            'amount' => $amount,
            'status' => $status,
            'scheme' => $scheme
        ];
        $this->db->sh($u_obj)->insertArray('pending_withdrawals', array_merge($insert, $extra));
    }

    function testAntiFraudSelection($u){
        $config = phive('Config')->getByTag('cashier')['fifo-countries'];
        $config['config_value'] = '';
        phive('SQL')->save('config', $config);
        $u->deleteSetting('closed_loop_start_stamp');
        $this->c->handleClosedLoopStartStamp($u);
        $this->c->anti_fraud_scheme = null;
        $this->msg($this->c->getAntiFraudScheme($u) == 'closed_loop', 'Player should be on closed loop!', null, true);

        $this->c->anti_fraud_scheme = null;

        $config['config_value'] = $u->getCountry();
        phive('SQL')->save('config', $config);
        $this->msg($this->c->getAntiFraudSchemeByConfig($u) != 'closed_loop', 'Config should NOT be on closed loop!', null, true);
        $u->deleteSetting('closed_loop_start_stamp');
        $this->c->handleClosedLoopStartStamp($u);
        $this->c->anti_fraud_scheme = null;
        $this->msg($this->c->getAntiFraudScheme($u) != 'closed_loop', 'Player should NOT be on closed loop!', null, true);


    }

    function testClosedLoopCron($u){
        $this->c->handleClosedLoopStartStamp($u);
        $now = phive()->hisNow();
        $u->setSetting('closed_loop_start_stamp', $now);
        $this->c->closedLoopStartStampCron();
        $this->msg($u->hasSetting('closed_loop_start_stamp'), 'Player should have setting!', null, true);
        $u->setSetting('closed_loop_start_stamp', '2020-01-01 00:00:00');
        $this->c->closedLoopStartStampCron();
        $this->msg(!$u->hasSetting('closed_loop_start_stamp'), 'Player should NOT have start stamp setting!');
        $this->msg($u->hasSetting('closed_loop_cleared'), 'Player should have cleared setting!');
    }

    function testClosedLoop($u, $c_hash = '4444*********'){
        $config = phive('Config')->getByTag('cashier')['fifo-countries'];
        $config['config_value'] = '';
        phive('SQL')->save('config', $config);
        $u->deleteSetting('closed_loop_start_stamp');
        $u->deleteSetting('closed_loop_cleared');

        $this->c->handleClosedLoopStartStamp($u);
        $this->msg($u->hasSetting('closed_loop_start_stamp'), 'Player should have setting!', null, true);
        sleep(2);

        $this->clearTable($u, ['deposits', 'pending_withdrawals']);

        $this->casino->depositCash($u, 2000, 'paypal', uniqid());
        $this->casino->depositCash($u, 2000, 'worldpay', uniqid(), 'visa', $c_hash);
        $this->casino->depositCash($u, 2000, 'adyen', uniqid(), 'visa', $c_hash);
        $this->casino->depositCash($u, 2000, 'mifinity', uniqid(), 'mifinity');

        $this->insertWithdrawal($u, 1000, 'paypal');
        $this->insertWithdrawal($u, 1000, 'worldpay', $c_hash);
        $this->insertWithdrawal($u, 1000, 'mifinity', 'payanybank');

        $res = $this->c->getApplicableClosedLoopData($u, 'desktop');
        $this->msg(!empty($res), 'Applicable CL options should exist!', null, true);

        $res = $this->c->validateClosedLoopWithdrawal($u, 10000, $c_hash);
        $this->msg($res != ['success' => true], 'Withdrawal attempt 1 should be refused but isnt!', null, true);

        $res = $this->c->validateClosedLoopWithdrawal($u, 10000, 'paypal');
        $this->msg($res != ['success' => true], 'Withdrawal attempt 2 should be refused but isnt!', null, true);

        $res = $this->c->validateClosedLoopWithdrawal($u, 1000, $c_hash);
        $this->msg($res == ['success' => true], 'Withdrawal attempt 3 should be accepted but isnt!', null, true);

        $res = $this->c->validateClosedLoopWithdrawal($u, 1000, 'paypal');
        $this->msg($res == ['success' => true], 'Withdrawal attempt 4 should be accepted but isnt!', null, true);
    }

}
