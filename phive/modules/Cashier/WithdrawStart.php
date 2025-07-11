<?php

use Laraphive\Support\Encryption;
use Videoslots\FraudDetection\FraudFlagRegistry;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\MtsSdkPhp\Endpoints\Payouts\SwishPayout;

require_once 'CashierCommon.php';

class WithdrawStart extends CashierCommon{

    public function init($context = 'phive', $u_obj = null){
        $res = parent::init($context, $u_obj);
        return is_string($res) ? $res : true;
    }

    public function reachedLicWithdrawLimit($cents){
        $res = rgLimits()->reachedLicWithdrawLimit($this->u_obj, $cents);
        if ($res !== false) {
            return [
                'success' => false,
                'errors' => [
                    'action' => 'get_raw_html',
                    'params' => ['module' => 'Licensed', 'file' => 'global_withdraw_limit']
                ]
            ];
        }
        return false;
    }

    /**
     * On Licenses with custom logic for withdrawable funds, we display a popup showing the player withdrawable and non withdrawable funds
     * if he tries to withdraw a sum above his available withdrawable balance
     *
     * @param $cents
     * @return array|false
     */
    public function completedLicWageringRequirements($cents)
    {
        $balance_available_to_withdraw = lic('getWithdrawStartAllowedBalance',[$this->u_obj], $this->u_obj);
        if ($balance_available_to_withdraw !== false && $balance_available_to_withdraw < $cents) {
            return [
                'success' => false,
                'errors' => [
                    'action' => 'get_html_popup',
                    'params' => ['module' => 'Licensed', 'file' => 'non_withdrawable_balance_error']
                ]
            ];
        }
        return false;
    }

    public function executeCard($args){
        $card_id   = $args['ccard_select'];

        if (empty($card_id)) {
            return 'mts.card_invalid.error';
        }

        // Get this card from the MTS, and check the status in Dmapi
        $card = $this->mts->getCards($card_id);

        if (!$card) {
            return 'mts.card_invalid.error';
        }

        if ($args['supplier'] == 'ccard') {
            // We set supplier based on pre-existing deposits.
            $supplier = $this->mts->getCcSupplier('withdraw', $card['card_num'], $card_id);
        } else {
            $supplier = $args['network'];
            if (in_array($supplier, ['applepay', 'googlepay'])) {
                $rtrs = $this->mts->getStoredTrsWhere(['card_id' => $card_id]);
                $supplier = $rtrs[0]['supplier'] ?? null;
            }
        }
        if (!$supplier) {
            phive('Logger')->getLogger('payments')->error("Missing PSP", [
                'user_id' => $this->u_obj->userId,
                'request' => $args,
                'backtrace' => array_merge([['file' => __FILE__, 'line' => __LINE__]], debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ]);
            return 'err.withdrawal.failed';
        }

        list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $args['supplier'], 'out', '', true, '', $card['card_num'], $card_id);

        if (!empty($err)) {
            if (isset($err['success'])) {
                // We already have a proper return array so we just return it.
                return $err;
            } elseif (isset($err['amount'])) {
                return $err['amount'];
            }

            $status = phive('Dmapi')->checkDocumentStatus($this->u_obj->getId(), 'creditcardpic', '', $card['id']);
            if($status == 'deactivated') {
                return 'err.ccard.nodeposit';
            } else if($status != 'approved'){
                return 'err.card.not.verified';
            }

            return array_shift($err);
        }

        $cents = $amount * 100;

        $reached_lic = $this->reachedLicWithdrawLimit($cents);
        if($reached_lic !== false){
            return $reached_lic;
        }

        $wagering_req_lic = $this->completedLicWageringRequirements($cents);
        if($wagering_req_lic !== false){
            return $wagering_req_lic;
        }

        $new_amount = $cents - $this->cashier->getOutDeduct($cents, $supplier, $this->u_obj);
        if ($new_amount <= 0) {
            phive('Logger')->getLogger('payments')->error("Amount is lower than 0 after fees!", $args);
            return 'err.toolittle';
        }

        $type = $args['supplier'] == 'applepay' ? 'applepay' : $card['type'];
        $result     = $this->cashier->insertPendingCard($this->u_obj, $cents, $card_id, $new_amount, $card['card_num'], '', $supplier, $type);

        //forfeiting the active bonus when time of withdrawal
        if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
            phive('Bonuses')->failDepositBonuses( $this->u_obj->getId(), "CCard withdrawal" );
        }

        if ($result === false) {
            phive('Logger')->getLogger('payments')->error("Error on withdraw!", $args);
            return 'err.unknown';
        }

        return ['amount' => $this->u_obj->getBalance() / 100];
    }

    public function executeDefault($args){
        $initial_state = 'pending';

        $is_bank = isset($args['iban']) || isset($args['bank_account_number']);

        $this->loadPspFile();

        if ($is_bank) {
            if (!empty($args['iban'])) {
                $args['iban'] = strtoupper(phive()->rmNonAlphaNums($args['iban']));
                $iban_error = PhiveValidator::start($args['iban'])->iban()->error;
                if (!empty($iban_error)) {
                    return $iban_error;
                }
            }
            // We need this for bank account drop downs etc if not KYC.
            // Note that Trustly Direct does not need this as we insert this info in the Dmapi via
            // a callback from Trustly.
            $res = phive('Dmapi')->createEmptyBankDocument($this->u_obj, $args, $this->limits_supplier);
        }

        if ($args['supplier'] == 'trustly' && !isPNP()) {
            $selectedAccountId = (new Encryption())->decrypt($args['account_select']);

            if (!$selectedAccountId) {
                return 'invalid.selected.account';
            }

            $account = null;
            $documents = phive('Dmapi')->getDocuments($this->u_obj->getId());
            foreach ($documents as $document) {
                $documentAccountExtId = $document['account_ext_id'] ?? $document['card_data']['account_ext_id'] ?? null;
                if ($documentAccountExtId == $selectedAccountId) {
                    $account = $document;
                    break;
                }
            }

            if (!$account) {
                return 'invalid.selected.account';
            }

            $args['bank_account_number'] = $account['card_data']['value'] ?? $account['subtag'];
            $args['bank_name'] = $account['sub_supplier'] ?? $account['card_data']['sub_supplier'];
        } elseif ($args['supplier'] == 'swish' && !isPNP()) {
            $documents = phive('Dmapi')->getDocuments($this->u_obj->getId());
            $subTags = array_column($documents, 'subtag');
            if (!in_array($args['swish_mobile'], $subTags)) {
                return 'invalid.selected.account';
            }
        } elseif ($args['supplier'] == 'swish' && isPNP()) {
            $args['swish_mobile'] = $this->u_obj->getSetting('swish_mobile');
        }

        list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $this->limits_supplier, 'out', '', true, $this->limits_supplier);

        if(!empty($err)){
            if(isset($err['success'])){
                // We already have a proper return array so we just return it.
                return $err;
            }
            // We're looking at an array of translateable errors so we just return the first one.
            return array_shift($err);
        }

        $cents = $amount * 100;

        $reached_lic = $this->reachedLicWithdrawLimit($cents);
        if($reached_lic !== false){
            return $reached_lic;
        }

        // Jurisdiction specific validations before a withdrawal.
        $err_msg = lic('validateWithdraw', [$this->u_obj, $args], $this->u_obj);
        if ($err_msg) {
            return $err_msg;
        }

        $wagering_req_lic = $this->completedLicWageringRequirements($cents);
        if($wagering_req_lic !== false){
            return $wagering_req_lic;
        }

        $new_amount               = 0;
        $insert                   = (array)$res['data'];
        $insert['payment_method'] = $this->supplier;
        $extra                    = [];

        switch($this->supplier){
            case 'trustly':
                $mts           = new Mts('trustly', $this->u_obj);
                $extra         = array_merge($mts->getTrustlyExtra($this->u_obj, '', 'withdraw'), [
                    'min_amount' => $cents,
                    'max_amount' => $cents,
                ]);

                if (!isPNP()) {
                    $insert['ref_code'] = $selectedAccountId ?? '';
                }
                break;

            case 'swish':
                $insert['payment_method'] = Supplier::Swish;
                $insert['net_account'] = $args['swish_mobile'];
                break;

            case 'skrill':
                if($this->sub_supplier != 'skrill'){
                    if ($this->sub_supplier === 'rapid') {
                        $rapid_count = phive('Cashier')->getDepCount($this->u_obj->getId(), $this->supplier, $this->sub_supplier);
                        if (!$rapid_count) {
                            return 'depositfirst.html';
                        }
                    }

                    $insert['scheme'] = $this->sub_supplier;
                } else {
                    $skrill_email       = $this->skrillCommon($args);
                    if($skrill_email['success'] === false){
                        // Failure so we just return the returned error.
                        return $skrill_email['errors'];
                    }
                    $insert['mb_email'] = $skrill_email;
                }

                $extra['province']          = $this->u_obj->getMainProvince();
                $extra['firstname']         = $this->u_obj->getAttr('firstname');
                $extra['lastname']          = $this->u_obj->getAttr('lastname');
                $extra['date_of_birth']     = $this->u_obj->getAttr('dob');
                break;

            case 'mifinity':
                if(isset($args['account_id'])){
                    if(empty($args['account_id'])){
                        return 'err.missing.account_id';
                    }
                    $this->u_obj->setSetting('mifinity_account', $args['account_id']);
                    $insert['net_account'] = $args['account_id'];
                    $insert['scheme']      = 'wallet';
                } else {
                    $error_str = $this->handleLocalBankFieldsAndNid($args, $insert);
                    if(!empty($error_str)){
                        return $error_str;
                    }
                    $insert['scheme'] = 'payanybank';
                }
                break;

            case 'paypal':
                $paypal_email = $this->u_obj->getSetting('paypal_email');
                $paypal_id    = $this->u_obj->getSetting('paypal_payer_id');
                if(empty($paypal_id)){
                    return 'err.empty';
                } else {
                    $insert['net_account'] = $paypal_id;
                    $insert['mb_email']    = $paypal_email;
                }
                break;

            case 'paymentiq':
                $grab = [
                    'vega' => [
                        'username' => 'net_account',
                        'password' => 'net_email'
                    ]
                ];

                foreach($grab[$this->sub_supplier] as $from => $to){
                    $tmp = trim($args[$from]);
                    if(empty($tmp)){
                        return 'err.missing.'.$from;
                    }
                    $insert[$to] = $tmp;
                }

                $insert['net_email']      = base64_encode($insert['net_email']);
                $insert['scheme']         = $this->sub_supplier;
                $insert['payment_method'] = $this->supplier;
                break;

            case 'payretailers':
                $error_str = $this->handleLocalBankFieldsAndNid($args, $insert);
                if(!empty($error_str)){
                    return $error_str;
                }

                $insert['net_email'] = (int)$args['document_type'];

                if(!empty($args['pix_id'])){
                    $insert['mb_email'] = $args['pix_id'];
                }

                $insert['scheme'] = $this->sub_supplier ?? '';
                break;

            case 'inpay':

                // Brazilians need to submit NID (CPF) in order to be able to withdraw via Inpay
                $country = $this->u_obj->getCountry();
                if($country === 'BR'){
                    if(empty($args['nid'])){
                        return 'nid.empty';
                    }

                    if(!$this->u_obj->hasNid()){
                        $nid_res = $this->u_obj->setNid(phive()->rmWhiteSpace($args['nid']));
                        if(!$nid_res){
                            return 'nid.taken';
                        }
                    }
                }

                $insert['bank_receiver'] = $this->u_obj->getFullName();
                $insert['bank_country']  = $this->u_obj->getCountry();
                break;

            case 'zimpler':
                $initial_state = 'initiated';
                $extra = $this->extra;

                $extra['destination_account'] = $args['destination_account'];
                $extra['iban'] = $args['iban'];

                // The nid field exists in the args so we know it was in the form, that is the only thing
                // we care about at this point. If it doesn't exist in the form we're looking at a scenario
                // where we are 100% sure that the player has it, eg SE player in SGA licensed casino.
                if(isset($args['nid'])){
                    $nid = $this->u_obj->getNid();
                    if(empty($nid)){
                        $nid     = phive()->rmWhiteSpace($args['nid']);
                        $nid_err = PhiveValidator::start($nid)->nid($this->u_obj)->error;
                        if(!empty($nid_err)){
                            return "nid.$nid_err";
                        }
                        $this->u_obj->setSetting('unverified_nid', $nid);
                    }
                    $extra['nid'] = $nid;
                }

                break;

            case 'muchbetter':
                $insert['net_account'] = Mts::getPhone($this->u_obj, 'muchbetter');
                break;

            case 'astropay':
                $insert['net_account'] = Mts::setPhone($this->u_obj, 'astropay', $args['phone']);
                break;

            case 'instadebit':
                $insta_id = $this->u_obj->getSetting('instadebit_user_id');
                if(empty($insta_id))
                    return "err.nodeposit";
                break;

            case 'neteller':
                $insert['net_account'] = $this->u_obj->getSetting('net_account');
                break;

            case 'ecopayz':
                $has_eco = $this->u_obj->getSetting('has_eco');
                if(empty($has_eco)){
                    return 'err.nodeposit';
                }

                if(empty($args['accnumber'])){
                    return 'err.empty.accnumber';
                }

                $insert['net_account'] = $args['accnumber'];
                break;

            case 'citadel':
                if (empty($args['iban']) && empty($args['bank_account_number'])){
                    return "acc_iban.err.empty";
                }
                $insert['bank_receiver'] = $this->u_obj->getFullName();
                $insert['bank_country'] = $this->u_obj->getCountry();
                break;

            default:
                // Here we define all suppliers which need to fall down into the default logic.
                break;
        }


        if(!empty($res['errors'])){
            return $this->fail($res['errors'], false);
        }

        $new_amount = $cents;
        if (!phive('Cashier')->supplierIsBank($args['supplier'])) {
            $new_amount = $cents - $this->cashier->getOutDeduct($cents, $this->limits_supplier, $this->u_obj);
        }

        if ($new_amount <= 0) {
            phive('Logger')->getLogger('payments')->error("Amount is lower than 0 after fees!", $args);
            return 'err.toolittle';
        }

        $insert['deducted_amount'] = $cents - $new_amount;
        $insert['aut_code']        = empty($new_amount) ? $cents : $new_amount;

        $map = [
            'bank_name',
            'iban',
            'swift_bic',
            'bank_account_number',
            'bank_clearnr',
            'bank_city',
            'bank_code'
        ];

        $insert = phive()->moveit($map, $args, $insert);

        // TODO remove this when Trustly is via the MTS, and refactor the form to user bank_clearnr and bank_account_number instead.
        $insert = phive()->mapit(['bank_clearnr' => 'clearnr', 'bank_account_number' => 'accnumber'], $args, $insert, false);

        if (empty($insert['payment_method'])) {
            phive('Logger')->getLogger('payments')->error("Missing PSP", [
                'request' => $args,
                'insert' => $insert,
                'this_supplier' => $this->supplier,
                'initial_state' => $initial_state ?? null,
                'backtrace' => array_merge([['file' => __FILE__, 'line' => __LINE__]], debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
            ]);
            return 'err.withdrawal.failed';
        }

        if($initial_state == 'initiated'){
            $pid = phive('Cashier')->insertInitiatedWithdrawal($this->u_obj, $cents, $insert);
            if(!empty($pid)){
                Mts::getInstance($this->supplier, $this->u_obj)
                    ->doBankWithdrawalWithRedirect($err, $this->u_obj, $cents, $pid, $extra);

                if (isset($err['zimpler.error'])) {
                    return $err['zimpler.error'];
                }
            }
        } else {
            $pid = phive('Cashier')->insertPendingCommon($this->u_obj, $cents, $insert);
        }

        if (empty($pid)) {
            phive('Logger')->getLogger('payments')->error("Error on withdraw!", $args);
            return 'err.withdrawal.failed';
        }

        if ($this->isAccountPayout($this->supplier)) {
            return $this->approveAccountPayout($this->u_obj, $pid);
        }

        //forfeiting the active bonus when time of withdrawal
        if(in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands())) {
            phive('Bonuses')->failDepositBonuses($this->u_obj->getId(), "{$this->supplier} withdrawal");
        }

        return $this->success('ok');
    }

    private function isAccountPayout(string $supplier): bool
    {
        return in_array($supplier, [
            Supplier::Trustly,
            Supplier::Swish,
        ]);
    }

    private function approveAccountPayout($user, $withdrawalId)
    {
        $fraud = new Fraud();
        $withdrawal = phive('Cashier')->getPending($withdrawalId);

        if ($fraud->hasFraudFlag($user)) {
            phive()->dumpTbl('directly-approve-withdrawal-suspended', ['id' => $withdrawalId], $user->userId);

            return $this->success('ok');
        }

        phive()->dumpTbl('directly-approve-withdrawal', ['id' => $withdrawalId], $user->userId);

        $result = $this->cashier->payPending($withdrawalId, (float)$withdrawal['amount'], uid(), $user->userId);
        if ($result === true) {
            toWs(['id' => $withdrawalId, 'msg' => 'Transfer successful', 'performedBy' => uid(), 'action' => 'approve'], 'pendingwithdrawals', 'na');

            return $this->success('ok');
        }

        if (is_array($result) && $result['success'] === false && $result['code'] == CasinoCashier::SUPPLIER_CONNECTION_ERROR) {
            return $this->success('ok');
        }

        return $result;
    }

    public function success($res): array
    {
        return array_merge(parent::success($res), ['amount' => $this->u_obj->getBalance() / 100]);
    }

    public function handleLocalBankFieldsAndNid($args, &$insert){
        $error_str = $this->isLocalBankFieldsWrong($args);
        if(!empty($error_str)){
            return $error_str;
        }

        $insert['net_account'] = phive()->rmNonAlphaNums($args['account_type']);

        $error_str = $this->handleNid($args);
        if(!empty($error_str)){
            return $error_str;
        }
    }

    public function isLocalBankFieldsWrong($args){
        foreach(['document_type', 'account_type', 'bank_code', 'bank_clearnr', 'bank_account_number'] as $field_to_check){
            // Note the strict '0' check at the end there in order to work around the fact that empty('0') returns true.
            if(isset($args[$field_to_check]) && empty($args[$field_to_check]) && $args[$field_to_check] !== '0'){
                return $field_to_check.'.empty';
            }
        }
        return false;
    }

    public function handleNid($args){
        if(isset($args['nid']) && empty($args['nid'])){
            return 'nid.empty';
        }

        if(isset($args['nid']) && !$this->u_obj->hasNid()){
            $nid_res = $this->u_obj->setNid(phive()->rmWhiteSpace($args['nid']));
            if(!$nid_res){
                return 'nid.taken';
            }
        }

        return false;
    }

    public function execute($source, $args)
    {
        dclickStart('withdraw');

        if ($this->u_obj->isTestAccount() && $this->cashier->getSetting('test') !== true) {
            // We only allow withdrawals from test accounts on test environments.
            return 'err.test.account.no.withdrawal';
        }

        if (!empty($args['amount'])) {
            $amount_error = PhiveValidator::start($args['amount'])->amount()->error;

            if (!empty($amount_error)) {
                return $amount_error;
            }
        }

        $fraudFlagRegistry = new FraudFlagRegistry();
        $fraudFlagRegistry->assign(
            $this->u_obj,
            AssignEvent::ON_WITHDRAWAL_START,
            $args
        );

        switch ($source) {
            case 'card':
                $res = $this->cardInit('withdraw')->executeCard($args);
                break;
            default:
                $res = $this->defaultInit('withdraw', $args)->executeDefault($args);
                break;
        }

        $fraudFlagRegistry->postTransactionCreationHandler(
            $this->u_obj,
            AssignEvent::ON_WITHDRAWAL_START,
            $args
        );

        dclickEnd('withdraw');
        return $res;
    }
}
