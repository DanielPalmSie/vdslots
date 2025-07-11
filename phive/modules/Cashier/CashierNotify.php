<?php

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\FailedDepositHistoryMessage;
use Videoslots\HistoryMessages\FailedWithdrawalHistoryMessage;

require_once __DIR__ . '/../../api.php';

class CashierNotify{

    /** @var Cashier|CasinoCashier $cashier*/
    public $cashier;

    /** @var DBUser */
    public $u_obj;

    public function init(string $context = 'phive')
    {
        $this->context = $context;

        if (!isCli()) {
            if ($context === 'phive') {
                header('Content-type: application/json');
            }

            $cashier = phive('Cashier');

            if ($cashier->getSetting('skip_ip_validation')) {
                return true;
            }

            $remoteIp = remIp();
            $allowedIps = $cashier->getSetting('mts_ips');

            if (!in_array($remoteIp, $allowedIps)) {
                throw new AccessDeniedHttpException('Access denied');
            }
        }

        return true;
    }

    public function defaultInit($args = null, $context = 'phive')
    {
        $this->init($context);

        $input =  $context == 'phive' ? file_get_contents('php://input') : $args;
        $this->args = $context == 'phive' ? json_decode($input, true) : $args;

        if(empty($this->args['data'])){
            phive('Logger')
                ->getLogger('payments')
                ->error("Input data invalid", [
                    'input' => $input,
                    'args' => $this->args,
                ]);
            return $this->fail('Input data invalid');
        }

        $this->u_obj = cu($this->args['data']['user_id']);
        if(!empty($this->u_obj)){
            $this->udb = phive('SQL')->sh($this->u_obj);
        }
        $this->action = $this->args['action'];

        if(empty($this->action)){
            return $this->fail('Action is missing');
        }

        $this->mts = new Mts();

        return true;
    }

    public function executeFail(array $data = null): array
    {

        // Fail notify has $arr['data']['data'] where the top array is just base 64 encoded,
        // this logic requires that the top 'data' array has first been decoded.
        $failed_data = json_decode($data['data'], true);

        try {
            $historyMessageData = [
                'transaction_id' => (int) $data['transaction_id'],
                'user_id' => (int) $data['user_id'],
                'reference_id' => (string) $data['reference_id'],
                'amount' => (int) $data['amount'],
                'currency' => $data['currency'],
                'sub_supplier' => (string) $data['sub_supplier'] ?? '',
                'supplier' => $data['supplier'],
                'supplier_display' => (string) $data['supplier_display'] ?? '',
                'card_num' => (string) $data['account'] ?? '',
                'card_id' => (int) $data['card_id'],
                'card_duplicate' => $data['card_duplicate'] ?? [],
                'type' => (string) $data['scheme'] ?? '',
                'extra' => $failed_data ?? [],
                'ip' => ud($data['user_id'])['cur_ip'],
                'event_timestamp' => time(),
            ];

            try {
                if ((int)$data['transaction_type'] === 0) {
                    $report_type = 'failed_deposit';
                    $history_message = new FailedDepositHistoryMessage($historyMessageData);
                } else {
                    $report_type = 'failed_withdrawal';
                    $history_message = new FailedWithdrawalHistoryMessage($historyMessageData);
                }

                /** @uses Licensed::addRecordToHistory() */
                lic('addRecordToHistory',
                    [
                        $report_type,
                        $history_message
                    ],
                    $data['user_id']
                );
            } catch (InvalidMessageDataException $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data on CashierNotify", [
                        'report_type' => $report_type,
                        'args' => $historyMessageData,
                        'validation_errors' => $exception->getErrors(),
                        'user_id' => $data['user_id']
                    ]);
            }

            $insert = [
                'type' => $data['transaction_type'],
                'user_id' => $data['user_id'],
                'supplier' => $data['supplier'],
                'scheme' => $data['scheme'],
                'account' => $data['account'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'ext_id' => $data['reference_id'],
                'mts_id' => $data['transaction_id'],
                'error_code' => $data['error_code'],
                'created_at' => $data['created_at'],
                'data' => json_encode($failed_data)
            ];

            if (phive('Cashier')->insertFailedTransaction($insert) === false) {
                phive('Logger')
                    ->getLogger('payments')
                    ->error("Failed transaction was not stored.", [
                        'insert_data' => $insert
                    ]);

                return $this->fail('Failed transaction was not stored.');
            } else {
                phive()->pexec('Cashier/Arf', 'invoke', ['onFailedDeposit', $data['user_id']]);
                if (!empty($card_country)) {
                    try {
                        phive('Cashier/Fr')->checkCardCountry(cu($data['user_id']), $failed_data['card_country'], $failed_data['card_id']);
                    } catch (Exception $e) {
                        error_log("Deposit fail notify error: {$e->getMessage()}");
                    }
                }
                return $this->success('ok');
            }

        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        return $this->success('ok');
    }

    public function executeDefault(): array
    {
        phive('Logger')
            ->getLogger('payments')
            ->debug('CashierNotify', [
                'action' => $this->args['action'],
                'data' => $this->args['data'],
            ]);

        try {
            $data = $this->args['data'];

            switch ($this->action) {
                case 'ACCOUNT':

                    if ($data['supplier'] === 'instadebit') {
                        return $this->success(['Instadebit account is already handled on deposit notification']);
                    }

                    if (empty($this->u_obj)) {
                        return $this->fail('No user');
                    }

                    phive('Cashier')->fraud->checkTrustlyCountryFlag($this->u_obj, $data['origin_country']);

                    if (!empty($data['nid'])) {
                        $this->u_obj->setMissingSetting('nid_extra', $data['nid']);
                    }

                    if ($data['type'] == 'deposit') {
                        $this->updateDepositAccountDetails($data, $data['id']);
                    } elseif ($data['type'] == 'withdrawal') {
                        // We're looking at a withdrawal.
                        $withdrawal = $this->udb->loadAssoc("", "pending_withdrawals", ["mts_id" => $data['id']]);
                        $update_array = [];

                        if (empty($withdrawal['bank_name'])) {
                            $update_array['bank_name'] = $data['sub_supplier'];
                        }

                        if (empty(phive('Cashier')->getIbanOrAcc($withdrawal))) {
                            $acc_type = $data['account_type'] == 'iban' ? 'iban' : 'bank_account_number';
                            $update_array[$acc_type] = $data['partial_source_id'];
                        }

                        if (empty($withdrawal['bank_country'])) {
                            $update_array['bank_country'] = $data['origin_country'];
                        }

                        if (!empty($update_array)) {
                            $this->udb->updateArray('pending_withdrawals', $update_array, ['id' => $withdrawal['id']]);
                        }
                    } else {
                        return $this->fail('Unsupported type');
                    }
                    break;
                case 'DEACTIVATE_CARD':
                    $card = $data;
                    $actor_id = uid('system');
                    $result = phive('Dmapi')->updateDocumentStatusForCreditCard($actor_id, $card['user_id'], $card['id'], 'deactivated');
                    if ($result !== true) {
                        return $this->fail('Unable to update card status at Dmapi');
                    }
                    break;
                case 'ADD_DOCUMENT': // deprecated
                    $status = $data['status'] ?? 'requested';

                    phive('Dmapi')->createEmptyDocument($data['user_id'], $data['supplier'], $data['type'], $data['ref'], $data['id'], 0, $data, $status);
                    break;
                case 'DOCUMENT':
                    $tag = phive('Dmapi')->getDocumentTypeFromMap($data['supplier'], $data['type']);

                    phive('Dmapi')->updateOrCreateDocument(
                        $data['user_id'],
                        $data['customer_id'],
                        $tag,
                        $data['ref'],
                        $data,
                        $data['status'] ?? null
                    );
                    break;
                case 'MISMATCHING_ACCOUNT':
                    $user = cu($data['user_id']);

                    phive('UserHandler')->logAction($user, "The user attempted to use an account linked to a different NID \"{$data['mismatched_person_id']}\" than the one used during registration. The account was not added as an option for withdrawal.", 'nid-mismatch', true, 'system');

                    if (!$user->hasComment('amlfraud', $data['mismatched_person_id'])) {
                        $user->addComment("Fraud attempt to add a withdrawal account linked to an NID that is not the one used during registration - \"{$data['mismatched_person_id']}\".", 1, 'amlfraud');
                    }

                    if ($data['type'] == 'deposit') {
                        phive('Cashier/Aml')->nidMismatch($user, $data['mismatched_person_id']);
                    }
                    break;
                case 'APPROVE_DOCUMENT': // deprecated
                    $tag = phive('Dmapi')->getDocumentTypeFromMap($data['supplier'], $data['type']);

                    phive('Dmapi')->updateOrCreateDocument(
                        $data['user_id'],
                        $data['customer_id'],
                        $tag,
                        $data['ref'],
                        $data
                    );
                    break;
                default:
                    return $this->fail('Unknown action');
                    break;
            }

            return $this->success('OK');
        } catch (UnprocessableEntityHttpException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->fail([$e->getMessage()]);
        }
    }

    public function getBase64Body($args){
        return json_decode(base64_decode($args['data']), true);
    }

    public function getUser($args){
        $user = cu($args['user_id']);
        if(empty($user)){
            return $this->fail('User not found');
        }
        return $user;
    }

    public function transactionInit($args){
        $this->mts             = new Mts();
        $this->cashier         = phive('Cashier');
        $this->create_document = true;
        $this->u_obj           = $this->getUser($args);
        if(is_array($this->u_obj)){
            // Could not find the user.
            return $this->u_obj;
        }
        $this->udb = phive('SQL')->sh($this->u_obj);
        phive()->dumpTbl('MTS_NOTIFY', $args, $this->u_obj);

        foreach(['user_id', 'amount', 'supplier', 'reference_id', 'transaction_id'] as $col){
            if(empty($args[$col])){
                return $this->fail('Parameter empty: '.$col);
            }
        }

        $this->mts_id = (int)$args['transaction_id'];
        return true;
    }

    public function fail($errors = []){
        return ['success' => false, 'errors' => is_string($errors) ? [$errors] : $errors];
    }

    public function success($result = []){
        return ['success' => true, 'result' => is_string($result) ? [$result] : $result];
    }

    public function stopFail($errors = []){
        $this->stop($this->fail($errors));
    }

    public function stop($res){
        die(json_encode($res));
    }

    /**
     * call the report transaction
     *
     * @param string $action
     * @param int $amount
     * @param string $supplier
     * @param DBUser $user
     * @param bool $async
     */
    public function reportTransaction(string $action, int $amount, string $supplier, DBUser $user)
    {
        // sanitize amount
        $amount = (int)phive('Cashier')->cleanUpNumber($amount);

        if(in_array($action, ['deposit', 'withdraw']) && $amount > 0) {
            return lic('dispatchReportTransactionJob', [$action, $supplier, $amount, $user->getId()], $user);
        }
    }

    public function updateDepositAccountDetails(array $data, int $mtsTransactionId): void
    {
        $deposit = $this->udb->loadAssoc("", "deposits", ["mts_id" => $mtsTransactionId]);

        if (empty($deposit)) {
            $errorMessage = "Deposit not found for mtsTransactionId: {$mtsTransactionId}, Supplier: {$data['supplier']}";
            throw new UnprocessableEntityHttpException($errorMessage);
        }

        $displayName = $deposit['dep_type'] === Supplier::SWISH
            ? $this->mts->getDisplayName($deposit['dep_type'])
            : null;

        if (empty($deposit['card_hash']) && empty($deposit['scheme'])) {
            $updateData = [
                'display_name' => $displayName ?? $data['display_name'] ?? $data['sub_supplier'],
                'scheme' => strtolower($data['sub_supplier']),
                'card_hash' => $data['partial_source_id']
            ];

            $this->udb->updateArray('deposits', $updateData, ['id' => $deposit['id']]);
        }

        phive()->pexec('Cashier/Arf', 'invoke', ['bankReceiverCheck', $this->u_obj->getId(), "'{$data['receiver_name']}'", $data['supplier']]);
    }
}
