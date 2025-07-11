<?php

use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\InterventionHistoryMessage;
use Videoslots\HistoryMessages\DepositHistoryMessage;

require_once 'CashierNotify.php';

class DepositNotify extends CashierNotify{

    public $orig_transaction;

    public function init($args){
        $res = parent::init();
        if($res === true){
            $this->tr_type = 'deposit';
            $res = $this->transactionInit($args);
        }
        return $res;
    }

    public function execute($args): array
    {
        phive('Logger')
            ->getLogger('payments')
            ->debug('DepositNotify', $args);

        // If we have a table we get a potentially old transaction that we will use to achieve idempotency in some cases.
        $this->orig_transaction = $this->udb->loadAssoc("SELECT * FROM deposits WHERE mts_id = {$this->mts_id}");

        $args = $this->mts->doNotifyFx($args, $this->u_obj);

        // Potential supplier override if not null, will be stored in deposits.dep_type.
        $main_supplier = null;
        $sub_supplier  = null;

        $cardHash = $args['card_num'];

        $insufficientBalanceAllowed = true;
        $userBlockStatus = 9; // @see https://gitlab.videoslots.com/platform/admin2/-/blob/main/app/Classes/BlockType.php

        if ((bool)$args['extra']['cancelled']) {
            switch ($args['supplier']) {
                case Supplier::Trustly:
                    if (isset($args['extra']['values']['method']) && $args['extra']['values']['method'] === 'cancel') {
                        return $this->success();
                    }

                    $insufficientBalanceAllowed = false;
                    $userBlockStatus = 16;
                    break;
                case Supplier::Swish:
                    if (isPNP()) {
                        toWs([
                            'msg_raw' => 'deposit.cancelled',
                        ],
                            'cashier',
                            'swish' . $args['reference_id']
                        );
                    } else {
                        toWs(['msg' => t('deposit.failed.html', $this->u_obj->getLang())], 'cashier', getSid($this->u_obj));
                    }
                    return $this->success();
                case Supplier::Instadebit:
                    $args['amount'] += 100;
                    break;
            }

            // cancelled deposit here -> chargeback
            $res = $this->cashier->revertDeposit(
                $this->u_obj,
                $args,
                true,
                $args['supplier'],
                $insufficientBalanceAllowed,
                $userBlockStatus,
            );
            lic('onCancelledDeposit', [$this->u_obj, $args], $this->u_obj);
            return $this->success(['status' => $res]);
        } else {
            $approveDeposit = function(){};

            // Normal deposit
            // Idempotency logic start
            // We look for prior deposits to achieve idempotency.

            // We have an old transaction
            if(!empty($this->orig_transaction)){

                $approveDeposit = function() use ($args){
                    if($args['extra']['status'] == 'approved'){
                        $this->cashier->approveDeposit($this->orig_transaction);
                        return $this->success();
                    }
                };

                //BAN-11842 fix PayPal ext_id inconsistency
                //TODO: BAN-12241 Review and standardize the usage of deposits.ext_id property
                if ($this->orig_transaction['dep_type'] == Supplier::PAYPAL) {
                    $this->orig_transaction['ext_id'] = $args['reference_id'];
                    $this->udb->save('deposits', $this->orig_transaction);
                }

                // We have a supplier that does not support status changes, so we stop execution and return an error.
                if(!in_array($args['supplier'], [Supplier::Adyen, Supplier::Worldpay])){
                    phive()->dumpTbl('MTS_NOTIFY_ERROR', ['Deposit duplicate try ID', $this->orig_transaction['id']], $this->u_obj);
                    return $this->success('Deposit already exists');
                }else{

                    // We have a supplier that supports status changes so we check action against current status.
                    // If it is the same action we abort.
                    if($args['extra']['status'] == $this->orig_transaction['status']){
                        phive()->dumpTbl('MTS_NOTIFY_ERROR', ['Deposit duplicate try ID', $this->orig_transaction['id']], $this->u_obj);
                        return $this->success('Deposit already exists');
                    }
                }
            }

            // Idempotency logic end

            // depositCash params
            $deposit_status = 'approved';
            $fees = 0;
            $depositCashExtraParams = [];

            // We set the PSP user id as a setting in case it is sent, can be used in order to accomplish FIFO etc.
            if(!empty($args['extra']['psp_user_id'])){
                $this->u_obj->setSetting($args['supplier'].'_user_id', $args['extra']['psp_user_id']);
            }

            switch($args['supplier']) {
                case 'siirto':
                case Supplier::Swish:
                    $this->u_obj->setSetting('swish_mobile', $args['extra']['account_id']);

                    if (isPNP()) {
                        toWs([
                            'msg' => t('deposit.complete', $this->u_obj->getLang()),
                            'msg_raw' => 'deposit.complete',
                        ], 'cashier', 'swish' . $args['reference_id']);
                    } else {
                        toWs(['msg' => t('deposit.complete', $this->u_obj->getLang())], 'cashier', getSid($this->u_obj));
                    }

                    break;

                case 'muchbetter';
                    Mts::setPhone($this->u_obj, 'muchbetter', $args['extra']['mobile']);
                    break;

                case Supplier::Instadebit:
                    $depositCashExtraParams['instadebit_user_id'] = $args['extra']['instadebit_user_id'];

                    phive('Cashier/Fraud')->instadebitFraudChecks(
                        $this->u_obj->getId(),
                        $args['extra']['instadebit_user_id'],
                        $this->orig_transaction['id']
                    );

                    break;
                case Supplier::Citadel:
                    if (isset($args['extra']['repeat'])) {
                        //error_log('CITADEL:'.json_encode($args['extra']));
                        $mh          = phive('MailHandler2');
                        $body        = "Username: {$this->u_obj->getUserName()}, amount: {$args['amount']}.";
                        $subject     = "Repeat Citadel deposit";
                        $emails      = ['payments'];
                        if (!empty(rgLimits()->hasLimits($this->u_obj, 'deposit'))) {
                            $subject     = "Repeat Citadel deposit with deposit limit";
                            $this->u_obj->playBlock();

                            $log_id = phive('UserHandler')->logAction($this->u_obj->getId(), "profile-blocked|fraud - {$subject}", 'intervention');
                            try {
                                $data = [
                                    'id'                => (int) $log_id,
                                    'user_id'           => (int) $this->u_obj->getId(),
                                    'begin_datetime'    => phive()->hisNow(),
                                    'end_datetime'      => '',
                                    'type'              => 'profile-blocked',
                                    'cause'             => 'fraud',
                                    'event_timestamp'   => time(),
                                ];

                                    /** @uses Licensed::addRecordToHistory() */
                                lic('addRecordToHistory', [
                                    'intervention_done',
                                    new InterventionHistoryMessage($data)
                                ], $this->u_obj);
                            } catch (InvalidMessageDataException $exception) {
                                phive('Logger')
                                    ->getLogger('history_message')
                                    ->error("Invalid message data on DepositNotify", [
                                        'report_type' => 'intervention_done',
                                        'args' => $data,
                                        'validation_errors' => $exception->getErrors(),
                                        'user_id' => $data['user_id']
                                    ]);
                            }

                            $emails  = ['compliance', 'fraud_mail'];
                        }
                        else if($this->u_obj->isBlocked()) {
                            $subject = "Repeat Citadel deposit with block";
                            $emails  = ['compliance', 'fraud_mail'];
                        }

                        //error_log('MAIL_SENT: '.$subject);

                        foreach($emails as $email)
                            $mh->mailLocal($subject, $body, $email);
                        phive('UserHandler')->logAction($p['user_id'], "Citadel repeat deposit.", 'deposit');

                    }
                    break;
                case Supplier::Emp:
                    //Scenario #3 and #4 supported now on repeated deposits
                    if ($this->orig_transaction['status'] == 'pending') {
                        phive()->dumpTbl('emp-repeated-notify', $args);
                        phive('MailHandler2')->mailLocal("Emp repeated notify received", "Pending and not credited deposit got a notification {$args['transaction_id']}", '', 'ricardo.ruiz@videoslots.com');
                        $orig_transaction['status'] = $args['extra']['status'];

                        $insert_res = $this->udb->save('deposits', $orig_transaction);
                        if (!empty($insert_res) && $args['extra']['status'] == 'approved') { #4 we also credit
                            $result = $this->changeBalance($this->u_obj, $orig_transaction['amount'], 'Deposit', Cashier::CASH_TRANSACTION_DEPOSIT, '', 0, 0, false, $this->orig_transaction['id']);
                        }
                        Mts::stop();
                    }

                    if (!isset($args['extra']['repeated'])) {
                        Mts::getInstance()->resetOnSuccess($this->u_obj);
                    }
                    break;
                case Supplier::WireCard:
                    if (!isset($args['extra']['repeated']))
                    {
                        Mts::getInstance()->resetOnSuccess($this->u_obj);
                    }
                    break;

                case Supplier::Paysafe:
                    $this->u_obj->setSetting('has_paysafe', 'yes');
                    break;

                case Supplier::Skrill:
                    $this->cashier->handlePspAccIdChange($this->u_obj, $args, 'pay_from_email', 'mb_email', 'skrillpic');
                    break;

                case 'neteller':
                    // We store the account email in case none was stored before (ie on first time deposit).
                    $this->u_obj->setMissingSetting('net_account', $args['extra']['email']);
                    break;

                case 'payretailers':
                    if(in_array($this->u_obj->getCountry(), ['BR', 'CO', 'CL'])){
                        $unverified_nid = $this->u_obj->getSetting('unverified_nid');
                        if(!empty($unverified_nid)){
                            $this->u_obj->setNid($unverified_nid);
                            $this->u_obj->deleteSetting('unverified_nid');
                        }
                    }
                    break;

                case 'paypal':
                    $paypal_id = $args['extra']['payer_id'];
                    $paypal_email = $args['extra']['email'];

                    // We get request from MTS before proceed with the capture (complete transaction), to check if the teh account has already been used by another user.
                    // Return true if we need to handleDuplicateAccount scenario and reject the deposit.
                    if($args['extra']['check_double']){
                        $others = $this->cashier->getDuplicateAccountUsage(uid($this->u_obj), 'paypal', $paypal_id);
                        if(!empty($others)){
                            $this->cashier->logOneToOneRelationshipViolationAction($this->u_obj, 'paypal', $paypal_id, $others[0]['user_id']);
                            return $this->success(['other_user' => $others[0]['user_id'] ?? '']);
                        }

                        if(!empty($paypal_id) && !$this->u_obj->hasSetting('paypal_payer_id')){
                            $this->u_obj->setSetting('paypal_payer_id', $paypal_id);
                        }

                        if(!empty($paypal_email) && !$this->u_obj->hasSetting('paypal_email')){
                            $this->u_obj->setSetting('paypal_email', $paypal_email);
                        }

                        return $this->fail();
                    }

                    $this->cashier->handlePspAccIdChange($this->u_obj, $args, 'payer_id', 'paypal_payer_id', 'paypalpic');

                    break;

                case Supplier::Adyen:
                    $deposit_status = $args['extra']['status'];
                    // Scenario #2 and #3 (implicit)
                    if($deposit_status == 'failed') {

                        // Not enough money
                        if($this->u_obj->getBalance() < $args['amount']){
                            // Not enough money so might be malicious, in any case the player owes us money so we block.
                            // We refrain from trying to claw anything back, player is blocked anyway, fraud / payments
                            // has to figure out what to do.
                            $msg = "A pending withdrawal by {$this->u_obj->getUsername()} expired, balance: {$this->u_obj->getBalance()}, amount: {$args['amount']}.";
                            phive('UserHandler')->logAction($this->u_obj, $msg, 'fraud');
                            phive('MailHandler2')->mailLocal("{$args['supplier']} pending expired for {$this->u_obj->getUsername()}", $msg, 'fraud_mail');
                            $log_id = phive('UserHandler')->logAction($this->u_obj->getId(), "profile-blocked|fraud - {$msg}", 'intervention');
                            try {
                                $data = [
                                    'id'                => (int) $log_id,
                                    'user_id'           => (int) $this->u_obj->getId(),
                                    'begin_datetime'    => phive()->hisNow(),
                                    'end_datetime'      => '',
                                    'type'              => 'profile-blocked',
                                    'cause'             => 'fraud',
                                    'event_timestamp'   => time(),
                                ];

                                /** @uses Licensed::addRecordToHistory() */
                                lic('addRecordToHistory', [
                                    'intervention_done',
                                    new InterventionHistoryMessage($data)
                                ], $this->u_obj);
                            } catch (InvalidMessageDataException $exception) {
                                phive('Logger')
                                    ->getLogger('history_message')
                                    ->error("Invalid message data on DepositNotify", [
                                        'report_type' => 'intervention_done',
                                        'args' => $data,
                                        'validation_errors' => $exception->getErrors(),
                                        'user_id' => $data['user_id']
                                    ]);
                            }
                            $this->u_obj->block();
                        }else{
                            $this->cashier->transactUser($this->u_obj, -$args['amount'], "Deposit {$args['transaction_id']} expired" , null, null, Cashier::CASH_TRANSACTION_NORMALREFUND, false, '', 0, $this->orig_transaction['id']);
                        }

                        Mts::stop();
                    }else {
                        // Scenario #4 make approval here and exit as user already was credited
                        $approveDeposit();
                    }
                    break;
                case Supplier::Worldpay:
                    phive('Logger')->getLogger('worldpay')->debug("deposit_notify_execute", [
                        'url' => $_SERVER['REQUEST_URI'] ?? null,
                        'file' => __METHOD__ . '::' . __LINE__,
                        'args' => $args
                    ]);
                    //$approveDeposit();
                    break;
                case Supplier::EcoPayz:
                    $this->u_obj->setSetting('has_eco', '1');
                    break;
                case Supplier::Paymentiq:
                    $sub_supplier = $args['sub_supplier'];
                    if(in_array($sub_supplier, ['vega'])){
                        $this->u_obj->setSetting($sub_supplier.'_username', $args['extra']['account_username']);
                    }

                    if(in_array($sub_supplier, ['cardeye', 'cleanpay', 'kluwp', 'epaysolution']) || !$this->cashier->pspHasWithdraw($sub_supplier)){
                        // It's a CC so was sent separately.
                        $this->create_document = false;
                    }

                    // We have the CC originator in scheme so we put the sub supplier as dep_type, the fact that PIQ is the network
                    // needs to be implicit knowledge unless we add a column with network to the deposits table.
                    if(in_array($args['sub_supplier'], ['cardeye', 'cleanpay', 'kluwp', 'epaysolution', 'bambora'])){
                        $main_supplier = $args['sub_supplier'];
                        // CC originator becomes the sub supplier.
                        $sub_supplier = $args['type'];
                    }

                    $display_name = ucfirst($sub_supplier);
                    $this->u_obj->setSetting('has_'.$sub_supplier, '1');
                    break;

                case 'ideal':
                    $sub_supplier = $args['sub_supplier'];
                    break;

                case Supplier::FLYKK:
                    $this->create_document = false;

                    $flykkAccountId = $args['extra']['identity']['uid'] ?? '';
                    $this->saveOrRefreshPspAccountId(Supplier::FLYKK, $flykkAccountId);

                    break;
            }

            if ($args['supplier'] == Supplier::Citadel) {
                $setting_alias = 'has_citadel';
            } else {
                $setting_alias = "has_{$args['type']}";
            }

            $this->u_obj->setSetting($setting_alias, 1);

            $main_supplier = $main_supplier ?? $args['supplier'];
            $sub_supplier = $sub_supplier ?? $args['type'];

            //if(phive('UserHandler')->getSetting('create_requested_documents') && empty($args['extra']['prepaid'])) { //todo removed for now
            if($this->create_document && phive('UserHandler')->getSetting('create_requested_documents') && !in_array($main_supplier, $this->mts->getCcSuppliers())) {
                 phive()->dumpTbl('deposit-notify-dmapi-args', [$this->u_obj->getId(), $main_supplier, $sub_supplier, $args['card_num'], $args['card_id']]);
                // Create empty document in dmapi if this is not a prepaid card.
                phive()->pexec(
                    'Dmapi',
                    'createEmptyDocument',
                    [
                        $this->u_obj->getId(),
                        $main_supplier,
                        $sub_supplier,
                        $args['card_num'],
                        $args['card_id']
                    ],
                    500,
                    true
                );
            }

            // Scenario #1, #5 and #6
            if(empty($display_name)){
                $display_name = !empty($args['supplier_display']) ?
                                $args['supplier_display'] :
                                (empty($args['card_num']) ? $this->mts->getDisplayName($args['supplier'], $args['type']) : '');
            }

            if($args['extra']['deposit_type'] == 'undo'){
                $this->cashier->transactUser($this->u_obj, $args['amount'], "Undone {$args['supplier']} withdrawal with external id {$args['reference_id']}", null, null, Cashier::CASH_TRANSACTION_UNDOWITHDRAWAL, true, '', 0, $args['extra']['parent_id']);
            } else {
                $new_balance = phive('Casino')->depositCash(
                    $this->u_obj,
                    $args['amount'],
                    $main_supplier,
                    $args['reference_id'],
                    $sub_supplier,
                    $cardHash,
                    '',
                    true,
                    $deposit_status,
                    null,
                    $args['transaction_id'],
                    $display_name,
                    $fees,
                    true,
                    $depositCashExtraParams
                );

                toWs(['new_balance' => $new_balance], 'cashier');

                if (array_key_exists('account', $args['extra'])) {
                    $this->updateDepositAccountDetails($args['extra']['account'], $args['transaction_id']);
                }

                $this->startGoogleEvent($args);

                // For kungaslottet
                if (phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_KUNGASLOTTET) {
                    phive('PayNPlay')->updateBonusCode([
                        'ip' => $this->u_obj->getAttr('reg_ip'),
                        'user_id' => $args['user_id']
                    ], $args['transaction_id']);
                }

                // report transaction to license
                $this->reportTransaction('deposit', $args['amount'], $args['supplier'], $this->u_obj);

                $args['transaction_id'] = (int) $args['transaction_id'];
                $args['user_id'] = (int) $args['user_id'];
                $args['reference_id'] = (string) $args['reference_id'];
                $args['amount'] = (int) $args['amount'];
                $args['card_id'] = (int) $args['card_id'];
                $args['card_num'] = (string) $args['card_num'] ?? '';
                $args['type'] = (string) $args['type'] ?? '';
                $args['event_timestamp'] = time();
                $args['ip'] = $this->u_obj->getAttr('cur_ip');
                try {
                    /** @uses Licensed::addRecordToHistory() */
                    lic('addRecordToHistory', ['deposit', new DepositHistoryMessage($args)], $this->u_obj);
                } catch (InvalidMessageDataException $exception) {
                    phive('Logger')
                        ->getLogger('history_message')
                        ->error("Invalid message data exception on DepositNotify", [
                            'report_type' => 'deposit',
                            'args' => $args,
                            'validation_errors' => $exception->getErrors(),
                            'user_id' => $this->u_obj->getId(),
                        ]);
                }

                phive('DBUserHandler')->checkExpiredDocuments(
                    $this->u_obj,
                    $args['transaction_id'],
                    $main_supplier,
                    $sub_supplier, $args['card_num']
                );

                if ($args['supplier'] == Supplier::Trustly) {
                    $this->u_obj->setSetting('has_trustly', 1);
                    $paypal_to_trustly_pending_tr_id = $this->u_obj->getSetting('paypal_to_trustly_pending_tr_id');
                    if ($paypal_to_trustly_pending_tr_id == $args['transaction_id']) {
                        $this->u_obj->deleteSetting('paypal_to_trustly_pending_tr_id');
                        $this->u_obj->deleteSetting('closed_loop_start_stamp');
                        $this->u_obj->setSetting('closed_loop_cleared', 1);
                        $this->u_obj->setSetting('show_trustly_withdrawal_popup', 1);
                    }
                } else {
                    $this->u_obj->deleteSetting('show_trustly_withdrawal_popup');
                }
            }

            if (!empty($args['extra']['card_country'])) {
                try {
                    phive('Cashier/Fr')->checkCardCountry($this->u_obj, $args['extra']['card_country'], $args['card_id']);
                } catch (Exception $e) {
                    error_log("Deposit notify error: {$e->getMessage()}");
                }
            }

            $user =$this->u_obj;

            //send deposit postback to raventrack
            phive()->postBackToRaventrack("deposit", $user, $args['amount'] / 100);

            //if the supplier is from gb and psp is credorax skip this part
            if (!lic('checkSupplierAndCountry', [$args['supplier']], $user->data)){
                //check if the user is from ES to skip unVerify, otherwise it interferes with user status.
                if ($args['new_card'] == true && !lic('skipUnVerify', [], $user)) {
                    $this->u_obj->unVerify();
                }
            }
            if ($args['supplier'] === 'ideal') {
                $tr = $this->cashier->getDepByExt($args['reference_id'] ?? 0, 'ideal');
                if ($tr) {
                    $req = [
                        'transaction_id' => $tr['id'],
                        'iban' => $args['extra']['external']['iban'],
                        'user_full_name' => $args['extra']['external']['user_full_name'],
                    ];
                    $res = lic('postValidateDeposit', [$this->u_obj, $req], $this->u_obj);
                    phive()->dumpTbl('deposit-notification-post-validation', $res, $this->u_obj->getId());
                    if ($res['transaction_rejected'] ?? false) {
                        return $this->success($res);
                    }
                }
            }
        }

        lic('onSuccessfulDeposit', [$this->u_obj, $args], $this->u_obj);
        return $this->success();
    }

    /**
     * Creating tracking event and saving track key in user_settings.
     *
     * @param $args // deposit request params
     * @return // nothing
     */
    private function startGoogleEvent($args) {
        $depositeData = $this->udb->loadAssoc("SELECT * FROM deposits WHERE mts_id = {$args['transaction_id']}");

        $cashier    = phive('Cashier');
        $count      = count($cashier->getFirstTimeDeposits($this->u_obj->getId()));
        $depCount   = count($cashier->getTotalDeposits($this->u_obj->getId()));
        $cent       = $args['amount'] / 100;
        $key        = "gtm_deposit_{$this->u_obj->getId()}_{$args['supplier']}_{$cent}";

        if ($this->u_obj->hasSetting($key)) {
            $googleEventkey = ($count === 1 && $depCount === 1) ? 'started-first-deposit' : 'started-subsequent-deposit';
            $userSetting = $this->u_obj->getSetting($key);
            $userSetting = json_decode(base64_decode($userSetting), true);

            $this->u_obj->setTrackingEvent($googleEventkey, [
                'triggered' => 'yes',
                'id' => $depositeData['id'],
                'amount'=> round(chg($depositeData['currency'], 'EUR', $depositeData['amount'], 1) / 100, 2),
                'type' => $depositeData['dep_type'],
                'deposit_type' => $depositeData['type'],
                'deposit_data' => $depositeData,
                'payment_method' => $depositeData['dep_type'],
                'group_transaction_id' => $userSetting['random'],
                'gtm_key' => $key,
                'bonus_code' => trim($userSetting['bonus_code'])
            ]);
        }
    }

    private function saveOrRefreshPspAccountId(string $psp, string $accountId): void
    {
        $accountIdKey = CasinoCashier::USER_SETTINGS_ACCOUNT_KEY_PER_PSP[$psp];
        $currentAccountId = $this->u_obj->getSetting($accountIdKey);
        if (empty($currentAccountId)) {
            $this->u_obj->setSetting($accountIdKey, $accountId);
            $this->u_obj->setSetting('has_' . $psp, '1');
        } else if ($accountId != $currentAccountId) {
            $this->u_obj->refreshSetting($accountIdKey, $accountId);
        }
    }
}
