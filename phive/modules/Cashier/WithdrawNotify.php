<?php

use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\WithdrawalHistoryMessage;

require_once 'CashierNotify.php';

class WithdrawNotify extends CashierNotify{

    public function init($args){
        $res = parent::init();
        if($res === true){
            $this->tr_type = 'withdrawal';
            $res = $this->transactionInit($args);
        }
        return $res;
    }

    public function execute($args){

        phive('Logger')
            ->getLogger('payments')
            ->info('WithdrawNotify', $args);

        $withdrawal = $this->cashier->getPending($args['extra']['customer_transaction_id'], $this->u_obj);

        if (phive('CasinoCashier')->getSettingArrayIntersect('mts_debug.log.payment_provider', ['*', $args['supplier']], true)) {
            phive()->dumpTbl('info ' . __METHOD__ . '::' . __LINE__, ['args' => $args, 'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)], $this->u_obj ? $this->u_obj->getId() : 0);
        }

        $args['transaction_id'] = (int) $args['transaction_id'];
        $args['user_id'] = (int) $args['user_id'];
        $args['reference_id'] = (string) $args['reference_id'];
        $args['amount'] = (int) $args['amount'];
        $args['card_id'] = (int) $args['card_id'];
        $args['card_num'] = (string) $args['card_num'] ?? '';
        $args['type'] = (string) $args['type'] ?? '';
        $args['ip'] = $this->u_obj->getAttr('cur_ip');

        if ($withdrawal) {
            if ($args['transaction_id']) {
                $withdrawal['mts_id'] = $args['transaction_id'];
            }

            if ($args['reference_id']) {
                $withdrawal['ext_id'] = $args['reference_id'];
            }

            $this->udb->save('pending_withdrawals', $withdrawal);
        }

        if((bool)$args['extra']['cancelled']){
            // Cancelled withdrawal, perhaps wrong acc. number or something, we don't care, the money has to go back

            switch($args['supplier']){
                case Supplier::Trustly:
                    return $this->disapproveTrustlyWithdrawal($withdrawal, $args);

                case Supplier::PAYPAL:
                case Supplier::SWISH:
                    $withdrawal = $this->cashier->getPending($args['extra']['customer_transaction_id'], $this->u_obj);
                    $this->cashier->revertPending($withdrawal, $args['supplier'], $args, true, true);
                    break;

                case Supplier::Zimpler:
                    if(empty($withdrawal)){
                        return $this->fail('No Zimpler withdrawal found.');
                    }

                    $isCanceled = $this->cancelWithdrawalIfInProgress($withdrawal);
                    if ($isCanceled) {
                        return $this->fail();
                    }

                    return $this->success();

                case Supplier::Inpay:
                    // NOTE that Inpay supports various intermediary states but we do not currently make use of that fact.

                default:
                    if(!empty($args['extra']['customer_transaction_id'])){
                        $withdrawal = $this->cashier->getPending($args['extra']['customer_transaction_id'], $this->u_obj);
                        $this->cashier->revertPending($withdrawal, $args['supplier'], $args, true);
                    } else {
                        $this->cashier->revertPending($args['reference_id'], $args['supplier'], $args, true);
                    }
                    break;
            }

        } else {

            // Normal withdrawal, we typically don't do anything here, the withdrawal has already been executed on this side.

            if(in_array($args['supplier'], ['trustly', 'zimpler'])){

                switch($args['extra']['call_type']){
                    case 'debit':

                        $pid = $args['extra']['customer_transaction_id'];
                        if(empty($pid)){
                            return $this->fail('No customer transaction id');
                        }

                        if($withdrawal['status'] != 'initiated'){
                            // idempotency protection
                            return $this->success('already processed');
                        }

                        $current_balance   = $this->u_obj->getBalance();

                        if($args['amount'] != $withdrawal['amount']){
                            // The player picked amount is different from what was first picked on our end so we update the pending withdrawal with the new info.
                            $withdrawal['amount']          = $args['amount'];
                            $withdrawal['aut_code']        = $withdrawal['amount'] - $withdrawal['deducted_amount'];
                        }

                        $authorized_amount = $withdrawal['aut_code'];

                        if ($current_balance === false) {
                            return $this->fail('err.database.unknown');
                        }

                        if ($authorized_amount > $current_balance) {
                            phive()->dumpTbl("trustly-wd-debug", [$authorized_amount, $current_balance]);
                            return $this->fail('err.lowbalance');
                        }

                        $new_balance = phive('Casino')->changeBalance($this->u_obj, -$authorized_amount, 'Withdrawal', Cashier::CASH_TRANSACTION_WITHDRAWAL, '', 0, 0, false, $withdrawal['id']);
                        toWs(['new_balance' => $new_balance], 'cashier');

                        /*
                           // TODO needs to be deducted in ebank_start.php instead in case we want to start with
                           // withdrawal fees due to multiple withdrawals in the same day. And then credited
                           // in case the withdrawal fails, we don't deduct here, we need the check and the deduct
                           // immediately when the player submits the withdrawal. /Henrik
                           if (!empty($ded_amount)) {
                           phive('Casino')->changeBalance($this->u_obj, -$ded_amount, 'Withdrawal Deduction', 50);
                           }
                         */

                        $withdrawal['ext_id']          = $args['reference_id'];
                        $withdrawal['payment_method']  = $args['supplier'];
                        $withdrawal['real_cost']       = $this->cashier->getOutFee($authorized_amount, $args['supplier'], $this->u_obj);
                        $withdrawal['mts_id']          = $args['transaction_id'];

                        $this->udb->save('pending_withdrawals', $withdrawal);

                        // This call will automatically change the status to pending from initiated.
                        $this->cashier->startProcessWithdrawal($this->u_obj, $pid);

                        if ($this->cashier->isManualPendingWithdrawal($withdrawal)) {
                            $this->insertManualWithdrawalIPLog($withdrawal, $args['user_id']);
                        }

                        if(!$this->u_obj->hasNid()){
                            $nid = $this->u_obj->getSetting('unverified_nid');
                            if(!empty($nid)){
                                // The unverified NID has now been verified by a third party.
                                $this->u_obj->setNid($nid);
                            }
                        }

                        //forfeiting the active bonus when time of withdrawal
                        if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
                            phive('Bonuses')->failDepositBonuses($this->u_obj->getId(), "{$args['supplier']} withdrawal");
                        }

                        $args['event_timestamp']  = time();
                        /** @uses Licensed::addRecordToHistory() */
                        try {
                            lic('addRecordToHistory', ['withdrawal', new WithdrawalHistoryMessage($args)], $this->u_obj);
                        } catch (InvalidMessageDataException $exception) {
                            phive('Logger')
                                ->getLogger('history_message')
                                ->error("Invalid message data exception on WithdrawNotify", [
                                    'report_type' => 'debit',
                                    'args' => $args,
                                    'validation_errors' => $exception->getErrors(),
                                    'user_id' => $this->u_obj->getId(),
                                ]);
                        }

                        Mts::stop(['success' => true]);

                        break;
                    case 'credit':
                        if ($args['supplier'] === "trustly") {
                            return $this->disapproveTrustlyWithdrawal($withdrawal, $args);
                        }
                }

                if ($this->isPendingTrustly($args)) {
                    $withdrawal['mts_id'] = $args['transaction_id'];

                    $this->udb->save('pending_withdrawals', $withdrawal);
                }
            }
        }

        if ($args['extra']['transaction_status'] == 'success') {
            $withdrawal['ext_id']      = $args['reference_id'];
            $this->udb->save('pending_withdrawals', $withdrawal);

            $withdrawalConfirmationCallback = $args['extra']['withdrawal_confirmation_callback'] ??
                phive('Cashier')->hasWithdrawalConfirmationCallback($args['supplier']);

            if ($withdrawal['status'] === 'processing' && $withdrawalConfirmationCallback) {
                phive('Cashier')->approveAndConfirmWithdrawal($withdrawal);

                $this->cashier->withdrawalAttemptMonitoring($withdrawal['id']);
            }

            //the previous block for trustly|zimpler has an early exit
            $args['event_timestamp']  = time();
            /** @uses Licensed::addRecordToHistory() */
            try {
                lic('addRecordToHistory', ['withdrawal', new WithdrawalHistoryMessage($args)], $this->u_obj);
            } catch (InvalidMessageDataException $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data exception on WithdrawNotify", [
                        'report_type' => 'transaction success',
                        'args' => $args,
                        'validation_errors' => $exception->getErrors(),
                        'user_id' => $this->u_obj->getId(),
                    ]);
            }
        }

        return $this->success();
    }

    private function isPendingTrustly($args): bool
    {
        return $args['supplier'] === Supplier::Trustly && $args['extra']['transaction_status'] == 'pending';
    }

    /**
     * @param array $withdrawal An array of withdrawal details.
     *
     * @return bool True if the withdrawal was canceled, false otherwise.
     */
    private function cancelWithdrawalIfInProgress($withdrawal): bool
    {
        if ($withdrawal['status'] === 'initiated') {
            $updates = [
                'status' => 'disapproved',
                'approved_at' => phive()->hisNow()
            ];

            $this->udb->updateArray('pending_withdrawals', $updates, ['id' => $withdrawal['id']]);

            $withdrawalAmount = $withdrawal['amount'];
            $withdrawalId = $withdrawal['id'];
            $userId = $withdrawal['user_id'];
            $actionDescription = "withdrawal cancelled by {$userId} of {$withdrawalAmount} with id of {$withdrawalId}";
            phive('UserHandler')->logAction($userId, $actionDescription, 'cancelled-withdrawal', true, $userId);

            $this->addCanceledWithdrawalCashTransactions($userId, $withdrawalId, $withdrawalAmount);

            return true;
        }

        return false;
    }

    private function addCanceledWithdrawalCashTransactions(int $userId, int $withdrawalId, int $withdrawalAmount): void
    {
        $user = cu($userId);

        $cashier = phive('Cashier');
        $withdrawalEntry = $cashier->prepareCashTransactionData($user, -$withdrawalAmount, Cashier::CASH_TRANSACTION_WITHDRAWAL, 'Withdrawal', $withdrawalId, $user->getBalance() - $withdrawalAmount);
        $refundEntry = $cashier->prepareCashTransactionData($user, $withdrawalAmount, Cashier::CASH_TRANSACTION_NORMALREFUND, 'Withdrawal Refund', $withdrawalId);

        phive('SQL')->sh($user)->insertTable('cash_transactions', [$withdrawalEntry, $refundEntry]);
    }

    private function disapproveTrustlyWithdrawal($withdrawal, $args)
    {
        if(empty($withdrawal)){
            return $this->fail('No Trustly withdrawal found.');
        }

        $isCanceled = $this->cancelWithdrawalIfInProgress($withdrawal);
        if ($isCanceled) {
            return $this->fail();
        }

        // Idempotency
        if($withdrawal['status'] == 'disapproved') {
            return $this->success();
        }

        $res = $this->cashier->disapprovePending(
            $withdrawal,
            false,
            true,
            true,
            (int)$withdrawal['deducted_amount'],
            false,
            $args['extra']['actor_id'] ?? null
        );

        if ($withdrawal['status'] == 'approved') {
            rgLimits()->incType($this->u_obj, 'net_deposit', $withdrawal['aut_code']);
            rgLimits()->incType($this->u_obj, 'customer_net_deposit', $withdrawal['aut_code']);
        }

        if ($res != true) {
            $updates = [
                "status"      => "disapproved",
                "approved_by" => $args['extra']['actor_id'],
                "approved_at" => phive()->hisNow()
            ];
            $this->udb->updateArray('pending_withdrawals', $updates, ['id' => $withdrawal['id']]);
            phive('UserHandler')->logAction($withdrawal['user_id'], " disapproved withdrawal by {$withdrawal['user_id']} of {$withdrawal['amount']} with id of {$withdrawal['id']}", 'disapproved-withdrawal', true, $args['extra']['actor_id']);

            return $this->fail();
        }

        if (phive()->moduleExists('MailHandler2')) {
            $replacers               = phive('MailHandler2')->getDefaultReplacers($this->u_obj);
            $replacers["__METHOD__"] = 'Trustly';
            $replacers["__AMOUNT__"] = nfCents($withdrawal['amount'], true);
            phive("MailHandler2")->sendMail('withdrawal.denied', $this->u_obj, $replacers);
        }

        return $this->success();
    }

    private function insertManualWithdrawalIPLog(array $withdrawal, int $userId): bool
    {
        $cashTransaction = $this->cashier->getCashTransaction(
            $withdrawal['id'],
            Cashier::CASH_TRANSACTION_WITHDRAWAL,
            $userId
        );

        if ($cashTransaction) {
            $actorUsername = cu($withdrawal['created_by'])->getAttr('username');

            phive('SQL')->sh($userId)->insertArray('ip_log', [
                'ip_num' => remIp(),
                'actor' => $withdrawal['created_by'],
                'target' => $userId,
                'descr' => "$actorUsername inserted a {$withdrawal['amount']} {$withdrawal['currency']} " .
                    "cents pending withdrawal to {$this->u_obj->getAttr('username')}",
                'tag' => 'cash_transactions',
                'tr_id' => $cashTransaction['id'],
                'actor_username' => $actorUsername
            ]);

            return true;
        }

        return false;
    }
}
