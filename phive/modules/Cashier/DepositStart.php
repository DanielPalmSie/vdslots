<?php

use Videoslots\Mts\MtsClient;
use Videoslots\Mts\Url\UrlHandler;
use Videoslots\MtsSdkPhp\Endpoints\Deposits\DepositInitResource;
use Videoslots\MtsSdkPhp\Endpoints\Deposits\SwishDeposit;
use Videoslots\MtsSdkPhp\MtsClientFactory;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;
use Videoslots\MtsSdkPhp\Endpoints\DataTransferObjects\CardData;
use Videoslots\MtsSdkPhp\Endpoints\DataTransferObjects\UserData;
use Videoslots\MtsSdkPhp\Endpoints\Deposits\CredoraxDeposit;
use Videoslots\MtsSdkPhp\Endpoints\Helpers\UserHelper;
require_once 'CashierCommon.php';

class DepositStart extends CashierCommon{

    public function init($context = 'phive', $u_obj = null){
        $res = parent::init($context, $u_obj);
        return is_string($res) ? $res : true;
    }

    public function setReloadCode($args){
        if(empty( phive('Bonuses')->getCurReload($this->u_obj)) ){
            phive('Bonuses')->setCurReload(trim($args['bonus']), $this->u_obj);
        }
    }

    public function executeCard($action, $args){

        $this->mts->cur_card_hash = $args['cardHash'];

        $newPaymentApi = $this->isNewPaymentFlow($args['network'] ?? '');

        $args['province'] = $this->u_obj->getMainProvince();

        switch($action){
            case 'repeat':
                $supplier    = $args['network'];
                $this->mts->setSupplier($supplier);
                $repeat_type = $this->mts->getCcSetting()[$supplier]['repeat_type'];
                $repeated_id = (int)$args['repeat_id'];

                if (isset($args['card_id']) && !$this->cashier->checkCreditCardIsActive($this->u_obj, 'creditcard', $args['card_id'])) {
                    return 'bad.ccard.number';
                }

                if($repeat_type == 'oneclick') {
                    list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $args['ccSubSup'], 'in');
                    if (!empty($err)) {
                        return array_shift($err);
                    }
                    $cents = $amount * 100;
                }else{
                    $cents = $this->cashier->cleanUpNumber($args['amount']) * 100;
                }

                // We unset prior data that might screw up which supplier gets chosen.
                $this->mts->unsetSess('3d_result');

                if($this->u_obj->isDepositBlocked())
                    return 'deposit.blocked.html';

                if (!$this->cashier->canQuickDepositViaCard($this->u_obj) && $repeat_type != 'oneclick') {
                    return 'err.deposit.cash.limit';
                }

                $over_limit_check_result = $this->checkDepositOverLimit($this->u_obj, $args['deposit_type'], $cents);
                if (!empty($over_limit_check_result)) {
                    return $over_limit_check_result;
                }

                if (!$this->cashier->checkDailyCardCashLimit($cents, $this->u_obj)) {
                    return 'err.daily.deposit.cash.limit';
                }

                $this->mts->setExtraData($args);

                if ($newPaymentApi === true) {
                    $mtsClient = new MtsClient(
                        phive('Cashier')->getSetting('mts'),
                        phive('Logger')->channel('payments')
                    );

                    try {
                        if ($this->isCredoraxDepositViaOldEndpoint()) {
                            $result = $mtsClient->quickDeposit($args['network'], $this->credoraxQuickDepositParams($args, (int) $cents));
                        } else {
                            $result = $mtsClient->deposit(
                                $args['network'],
                                $this->credoraxParams($args, (int)$cents, true)
                            );
                        }

                        if (isset($result['errors'])) {
                            return $this->mts->errorsParse($result['errors'], "use.{$this->cc_handler->altString($this->u_obj)}.instead.html");
                        }

                        return $result;
                    } catch (\Throwable $e) {
                        return 'err.unknown';
                    }
                }

                $result = $this->mts->failover('depositRepeat', '', $repeated_id, $cents, $this->cashier->max_quick_amount, $args['cvc'], $repeat_type);

                $supplier = $this->mts->supplier;

                if ($result['success']) {

                    // It's a success so we add the pending deposit amount.
                    rgLimits()->addPendingDeposit($this->u_obj, $cents);

                    // If notification goes from MTS then we dont need to deposit cash here.

                    if (in_array($supplier, [Supplier::WireCard])) {
                        $repeated_status = empty($result['status']) ? 'approved' : $result['status'];
                        $to_credit       = $repeated_status == 'pending' ? false : true;
                        $cnum            = $result['result']['card_data']['card_num'];

                        $deposit_result = phive('Casino')->depositCash(
                            $this->u_obj,
                            $result['result']['amount'],
                            $supplier,
                            $result['result']['reference_id'],
                            $this->cc_handler->getCardType($cnum),
                            $cnum,
                            '',
                            false,
                            $repeated_status,
                            null,
                            $result['result']['id'],
                            '',
                            0,
                            $to_credit
                        );

                        if ($deposit_result === false) {
                            return 'err.unknown';
                        }
                    }

                    if($repeat_type != 'oneclick'){
                        $this->u_obj->incSetting('n-quick-deposits');
                    }

                    return $result;
                }

                phive('Cashier')->dumpDepositFail('ccard', $result, $this->u_obj->getId());
                $alt = $this->cc_handler->altString($this->u_obj);

                return $this->mts->errorsParse($result['errors'], "use.$alt.instead.html");

                break;

            case 'check_card':
                // We unset prior data that might screw up which supplier gets chosen.
                $this->mts->unsetSess('3d_result');

                $this->mts->setCreditCardData($args);

                $this->mts->setExtraData($args);

                $card_status = false;

                $c_hash = null;
                // TODO cardstatus, check if verified in the dmapi here instead
                if(!empty($args['cardnumber'])){
                    $c_hash = $this->cc_handler->getSixFourAsterisk($args['cardnumber']);
                }

                $cents = (int)$this->cashier->cleanUpNumber($args['amount']) * 100;

                list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $args['ccSubSup'], 'in');
                if (!empty($err)) {
                    return array_shift($err);
                }

                $over_limit_check_result = $this->checkDepositOverLimit($this->u_obj, $args['deposit_type'], $cents);
                if (!empty($over_limit_check_result)) {
                    return $over_limit_check_result;
                }

                if (!$this->cashier->checkDailyCardCashLimit($cents, $this->u_obj)) {
                    return 'err.daily.deposit.cash.limit';
                }

                // $card_status will be true if card is verified or has a successful deposit already.
                // The result is that there will only be SMSes for cards that have no deposits, ie new, OR cards that have been approved in the BO.
                // If card status is false we check if card is 3D Secure, if it is we skip SMS too.
                if($card_status == false){
                    $card_status = @$this->mts->failover('check3d', $c_hash, $this->u_obj, $cents, $c_hash)['three_d'];
                }

                // We have a NON 3D deposit, therefore we must add it to the pending deposits
                if($card_status !== true){
                    rgLimits()->addPendingDeposit($this->u_obj, $cents);
                }

                // TODO, get rid of sms_ok, 3d cards do not sms verify anyway so it should be the only thing we need.
                $_SESSION['sms_ok'] = $_SESSION['3d_card'] = $card_status;

                return $this->success(['card_status' => $card_status]);
                break;
            default:
                // Normal deposit

                $this->mts->cur_cc_bin = $args['bin'];
                $c_hash = $this->mts->cur_card_hash;
                $c_hash = null;
                $card = null;

                //TODO we need to refactor this logic, there are too many possible loopholes here
                if(!empty($args['cardnumber'])){
                    $cards = $this->mts->getCard($this->cc_handler->getSixFourAsterisk($args['cardnumber']), $args['expirydate']);
                    if (!empty($cards)) {
                        $doc_active_candidate = 0;
                        foreach ($cards as $card) {
                            $status = phive('Dmapi')->checkDocumentStatus($this->u_obj->getId(), 'creditcardpic', '', $card['id']);
                            if($status != 'deactivated') {
                                $doc_active_candidate++;
                            }
                        }
                        if (empty($doc_active_candidate) ) {
                            phive()->dumpTbl('check_cards', (array)$cards);
                            return 'bad.ccard.number';
                        }
                    }

                    if (!$this->cc_handler->luhnCheck($args['cardnumber'])) {
                        return 'bad.ccard.number';
                    }

                    $c_hash = $this->cc_handler->getSixFourAsterisk($args['cardnumber']);

                    if(!empty($c_hash)){
                        // We get the card from the MTS.
                        $card = $this->mts->getCardFromHash($c_hash);
                    }
                }

                list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $args['ccSubSup'], 'in');

                if (!empty($err)) {
                    return array_shift($err);
                }

                $cents = $amount * 100;

                $over_limit_check_result = $this->checkDepositOverLimit($this->u_obj, $args['deposit_type'], $cents);
                if (!empty($over_limit_check_result)) {
                    return $over_limit_check_result;
                }

                if (!$this->cashier->checkDailyCardCashLimit($cents, $this->u_obj)) {
                    return 'err.daily.deposit.cash.limit';
                }

                $this->mts->failMissingEncryptionData($args);
                $this->mts->setCreditCardData($args);
                $this->mts->setExtraData($args);

                if ($newPaymentApi === true) {
                    $mtsClient = new MtsClient(
                        phive('Cashier')->getSetting('mts'),
                        phive('Logger')->channel('payments')
                    );

                    try {
                        $result = $mtsClient->deposit($args['network'], $this->credoraxParams($args, $cents));

                        if (isset($result['errors'])) {
                            return $this->mts->errorsParse($result['errors'], "use.{$this->cc_handler->altString($this->u_obj)}.instead.html");
                        }

                        return $result;
                    } catch (\Throwable $e) {
                        return 'err.unknown';
                    }
                }

                // If there is no card we simply check enrollment right away, the enrollment call will create the card on the MTS side.
                if(empty($card)){
                    // If we have a 3D secure card (90% of the cases) the MTS will insert the card here.
                    // TODO return fail in case there was a validation failure (wrong exp date or cvc) on the MTS side
                    // for all CC PSPs and pick up on that here and abort further processing.
                    $enrolled = $this->mts->failover('check3d', $c_hash, $this->u_obj, $cents, $c_hash);

                    // We have fail on enroll / authorize even though we failed over, not much more to do than return
                    // fail to client.
                    if($enrolled['success'] === false){
                        //phive()->dumpTbl('enrolled return', [$this->mts->errorsParse($enrolled['errors'], "use.{$this->cc_handler->altString($this->u_obj)}.instead.html")]);
                        return $this->mts->errorsParse($enrolled['errors'], "use.{$this->cc_handler->altString($this->u_obj)}.instead.html");
                    }

                    $is_3d = (bool)$enrolled['three_d'];
                }else{
                    $is_3d = (bool)$card['three_d'];
                }

                $_SESSION['3d_card'] = $is_3d;

                if($this->cc_handler->getSetting('no_sms') === true){
                    $_SESSION['3d_card'] = true;
                    $_SESSION['sms_ok'] = true;
                }

                // If we have not either managed to verify our phone or have a proper card (3D or verified) we abort.
                // The sms_ok setting is set to true if we manage to verify our phone, in that case we stop further verifications
                // as the player can easily provably verify furthern cards regardless even if they're not 3D, we save some SMS fees
                // like this but at the same time we make it a little bit easier for a criminal to use new cards.
                if($_SESSION['3d_card'] != true && $_SESSION['sms_ok'] != true){
                    return "err.sms.not.verified";
                }

                if (!$this->u_obj->checkCountryConfig('countries', 'no-limits') && $is_3d == false) {
                    //not enrolled and not in the country white list so we limit the total amount of deposits that can be made if player is not verified
                    if (!$this->cashier->checkInCashLimit($cents, $this->u_obj, '2011-01-01 00:00:00', '', 'card') && !$this->u_obj->isVerified()) {
                        return 'err.deposit.cash.limit';
                    }
                }

                $result = $this->mts->failover('deposit', $c_hash, $this->u_obj, $cents, [], $c_hash);


                //phive()->dumpTbl('cc result:', $result);

                // This is irrelevant, EMP doesn't even exist anymore.
                //if (!empty($result['reason']) && $result['reason'] == 'SUPPLIER_OFFLINE') {
                //    $this->mts->setSupplier(Supplier::Emp);
                //    $result = $this->mts->deposit($this->u_obj, $cents);
                //}

                $errors = [];

                if ($result['success']) {
                    return $result;
                } else {
                     $errors[] = $this->mts->errorsParse($result['errors']);
                }

                $this->cashier->dumpDepositFail('ccard', $result, $this->u_obj->getId());
                $alt = $this->cc_handler->altString($this->u_obj);
                $errors[] = "use.$alt.instead.html";

                //phive()->dumpTbl('cc errors:', $result);

                // No translation in case we're in a context were we don't want that to happen.
                // The calling context will have to translate explicitly.
                return $this->fail($errors, false);

                break;

        }
    }

    public function executeDefault($args){

        // To avoid warnings we set the variable.
        $err_msg = null;

        // We prevent attempts at direct calls here, ie bypassing the FE logic.
        if(!$this->cashier->withdrawDepositAllowed($this->u_obj, $this->limits_supplier, 'deposit')){
            return 'psp.supplier.not.active';
        }

        // Validate merchant call, used by for instance Apple Pay.
        if($args['action'] == 'validatemerchant'){
            $params = [];

            switch($this->sub_supplier){
                case 'applepay':
                    $params['apple_endpoint'] = $args['extUrl'];
                    $params['display_name'] = phive()->getSetting('domain');
                    $params['initiative_context'] = $_SERVER['HTTP_HOST'];
                    break;
            }

            $res = $this->mts->validatemerchant($params);

            if (!$res['success']) {
                $res = $this->cashier->transferEnd(implode('. ', $res['errors']), false);
                $res = $this->fallbackDepositDecorator->decorate($res);
            }

            die(json_encode($res));
        }

        $repeat_id = phive()->rmNonNums($args['repeat_id']);
        if($this->action == 'repeat' && empty($repeat_id)){
            return 'err.repeat.no.id';
        }

        if (!empty($args['nid_extra']) && !$this->u_obj->hasSetting('nid_extra')) {
            $this->u_obj->setSetting('nid_extra', phive()->rmWhiteSpace($args['nid_extra']));
        }

        $this->loadPspFile();

        list($err, $amount) = $this->cashier->transferStart($args, $this->u_obj, $this->limits_supplier, 'in');

        if (!empty($err)) {
            return array_shift($err);
        }

        $cents = $amount * 100;

        $over_limit_check_result = $this->checkDepositOverLimit($this->u_obj, $args['deposit_type'], $cents);
        if (!empty($over_limit_check_result)) {
            return $over_limit_check_result;
        }

        // Jurisdiction specific validations before a deposit.
        $err_msg = lic('validateDeposit', [$this->u_obj, 'bank', $args], $this->u_obj);
        if ($err_msg) {
            return $err_msg;
        }

        switch ($this->supplier) {

            case 'flexepin':
                $cur_choice = empty($args['selected_currency']) ? $this->u_obj->getCurrency() : $args['selected_currency'];

                $result = $this->mts->deposit($this->u_obj, $cents, ['code' => $args['code'], 'currency' => $cur_choice]);

                $deposit_amount = $result['result']['amount'];

                // If the chosen currency is different than used currency we convert.
                if($cur_choice != $this->u_obj->getCurrency()){
                    $deposit_amount = chg($cur_choice, $this->u_obj, $deposit_amount, 1);
                }

                if ($result['success']) {
                    phive('Casino')->depositCash($this->u_obj, $deposit_amount, $this->mts->getSupplier(), $result['result']['external_id'], '', '', '', true, 'approved', null, $result['result']['id']);
                    return $result;
                } else {
                    error_log(strtoupper($this->supplier).'_ERROR: '. $result['errors']);
                    return 'voucher.code.failed';
                }
                break;

            case 'neosurf':
                $mtsClient = new MtsClient(
                    phive('Cashier')->getSetting('mts'),
                    phive('Logger')->channel('payments')
                );

                try {
                    $result = $mtsClient->deposit('neosurf', $this->neosurfParams($cents));

                    return [
                        'success' => true,
                        'result' => [
                            'form' => [
                                'url' => $result['url'],
                                'fields' => $result['props'],
                            ]
                        ]
                    ];
                } catch (\Throwable $e) {
                    return $this->fail('err.unknown');
                }

            case 'zimplerbank':
            case 'zimpler':

                $pmethod                          = $this->sub_supplier == 'zimpler' ? 'bill' : 'bank';
                $extra                            = $this->extra;
                $extra['payment_methods']         = $pmethod;
                $setting                          = phiveApp(PspConfigServiceInterface::class)->getPspSetting($this->limits_supplier);
                $extra['swift_bic']               = $setting['bics'][$this->u_obj->getCountry()];
                if(!empty($extra['swift_bic'])){
                    $extra['supplier_display'] = $setting['display_name'];
                    $extra['sub_supplier']     = $this->limits_supplier;
                } else {
                    $extra['sub_supplier']     = $extra['payment_methods'];
                }

                /*
                   if(!empty($args['swift_bic'])){
                   $extra['swift_bic'] = $args['swift_bic'];
                   }
                 */

                $this->mts->setSupplier('zimpler');
                break;

            case 'paypal':
                $paypal_email = $this->u_obj->getSetting('paypal_email');
                $extra  = array_merge($this->extra, [
                    'account_id' => $this->u_obj->getSetting('paypal_payer_id'),
                    'email'      => empty($paypal_email) ? $this->u_obj->getAttr('email') : $paypal_email
                ]);
                break;

            case 'adyen':
                $map = ['sofort' => 'directEbanking'];
                $extra['sub_supplier'] = $map[$this->sub_supplier];
                break;

            case 'swish':
                return $this->executeSwishDeposit($cents);

            case 'trustly':
                $mtsClient = new MtsClient(
                    phive('Cashier')->getSetting('mts'),
                    phive('Logger')->channel('payments')
                );

                try {
                    $result = $mtsClient->deposit($args['network'], $this->trustlyParams($cents, $args));

                    if (array_key_exists('paypal_to_trustly', $args) && $args['paypal_to_trustly']) {
                        $this->u_obj->setSetting('paypal_to_trustly_pending_tr_id', $result['id']);
                    }
                } catch (\Throwable $e) {
                    return $this->fail('err.unknown');
                }

                rgLimits()->addPendingDeposit($this->u_obj, $cents);

                $result['result'] = $result['data'];

                return $result;

            case 'astropay':
                $extra = phive()->moveit(['code', 'card_num', 'exp_year', 'exp_month'], $args);
                break;

            case 'paymentiq':
                // New FE logic for vega Point and subsequent suppliers.

                // Things we don't want to move over from $args, such as amount
                $exclude = ['amount', 'supplier', 'network'];
                $extra   = array_merge($this->extra, array_filter($args, function($key) use ($exclude){
                    return !in_array($key, $exclude);
                }, ARRAY_FILTER_USE_KEY));

                $extra['locale']  = $this->u_obj->getMainProvince();
                $extra['fail_url']  = $this->mts->getFailUrl($this->u_obj);
                break;

            case 'sofort':
            case 'giropay':
            case 'rapid':
            case 'skrill':

                $extra  = $this->mts->getUrls($this->u_obj, $this->action);
                // We use _self as the target on both desktop (in the iframe) and mobile (main window).
                $extra['return_url_target'] = 3;
                $extra['cancel_url_target'] = 3;

                $extra['province']          = $this->u_obj->getMainProvince();
                $extra['firstname']         = $this->u_obj->getAttr('firstname');
                $extra['lastname']          = $this->u_obj->getAttr('lastname');
                $extra['date_of_birth']     = $this->u_obj->getAttr('dob');

                if($this->sub_supplier != 'skrill'){
                    $extra['sub_supplier'] = $this->sub_supplier;
                }

                if($this->action == 'deposit'){
                    $skrill_email   = $this->skrillCommon($args);
                    if($skrill_email['success'] === false){
                        // Failure so we just return the returned error.
                        return $skrill_email['errors'];
                    }
                    $extra['email'] = $skrill_email;
                }
                break;

            case 'paysafe':

                $extra['province']  = $this->u_obj->getMainProvince();

                $err_msg = 'cant.create.disposition';
                break;

            case 'worldpay':
            case 'credorax':
                $extra = array_merge($this->extra, $this->mts->getUrls($this->u_obj, $this->action));

                if (in_array($this->sub_supplier, ['googlepay', 'applepay'])) {
                    $extra['payment_token'] = $args['payment_token'];
                    $this->mts->setExtraData($args);
                }

                break;

            case 'mifinity':
                $extra['phone']                   = Mts::setPhone($this->u_obj, 'mifinity', $args['phone']);
            case 'astropaywallet':
                $extra['product']                 = 'casino';
                $extra['transaction_description'] = phive()->getSetting('domain');
                $extra['nid']                     = $this->u_obj->getNid();
                break;

            case 'muchbetter':

                $muchbetter_mobile = Mts::getCanonicalPhone($args['phone']);
                $extra = array_merge(
                    $this->mts->getDepositBaseParams($this->u_obj, $cents),
                    ['transaction_description' => phive()->getSetting('domain'), 'mobile' => $muchbetter_mobile, 'brand_name' => phive()->getSetting('domain')]
                );
                $extra['province']  = $this->u_obj->getMainProvince();
                $one_to_one_relation = $this->cashier->settings_data['psp_config_2']['muchbetter']['one_to_one_relation'];
                if ($one_to_one_relation) {
                    $mobile_number_validity = $this->muchbetterPhoneCheck($this->u_obj, $muchbetter_mobile);
                    if (!$mobile_number_validity) {
                        return 'deposit.failed.html';
                    }
                    if (!$this->handleMuchbetterOneToOneRelationship($this->u_obj, $muchbetter_mobile)) {
                        return 'deposit.failed.html';
                    }
                }
                break;

            case 'siirto':
                $extra['accountid'] = phive('Mosms')->splitNumberIntoParts($this->u_obj, $args['accountid'])[2];
                $extra['message'] = phive()->rmNonAlphaNumsNotSpaces($args['message']);
                break;

            case 'neteller':
                if(is_numeric($args['net_account'])){
                    return 'register.err.email';
                } else {
                    $email = $this->u_obj->getSettingOrDefault('net_account', $args['net_account']);
                    if(empty($email)){
                        return 'err.empty';
                    } else {
                        $extra = ['email' => $email];
                    }
                }
                break;

            case 'payretailers':
                $extra = ['description' => t('deposit.description')];

                if(in_array($this->u_obj->getCountry(), ['BR', 'CO', 'CL', 'CR'])){
                    $nid = $this->u_obj->getNid();
                    if(empty($nid)){
                        $this->u_obj->setSetting('unverified_nid', phive()->rmWhiteSpace($args['nid']));
                        $nid = $args['nid'];
                    }
                    $extra['nid'] = $nid;
                }

                if(in_array($this->sub_supplier, ['hosted_visa', 'hosted_mc', 'hosted_ccard'])){
                    $extra['sub_supplier'] = 'webpay';
                } else {
                    $extra['sub_supplier'] = $this->sub_supplier;
                }
                break;

            case 'siru':
                $extra = [
                    'trans_headline' => phive()->getSetting('domain')
                ];
                break;

            case 'ideal':
                $extra = $this->mts->getIdealExtra($this->u_obj, $this->sub_supplier, $this->action);

                // We need the bank's display name to match bank names returned by iDEAL.
                $conf = phiveApp(PspConfigServiceInterface::class)->getPspSetting($this->sub_supplier);
                $extra['sub_supplier_display_name'] = $conf['display_name'] ?? null;
                break;

            case 'instadebit':
                $mtsClient = new MtsClient(
                    phive('Cashier')->getSetting('mts'),
                    phive('Logger')->channel('payments')
                );

                try {
                    $result = $mtsClient->deposit('instadebit', [
                        'user_id' => $this->u_obj->getId(),
                        'amount' => $cents,
                        'currency' => $this->u_obj->getCurrency(),
                        'language' => $this->u_obj->getLang(),
                        'return_url' => phive('UserHandler')->getSiteUrl()
                            .llink('/cashier/deposit/?supplier=instadebit&end=true'),
                        'country' => $this->u_obj->getCountry(),
                        'firstname' => $this->u_obj->getAttr('firstname'),
                        'lastname' => $this->u_obj->getAttr('lastname'),
                        'address' => $this->u_obj->getAttr('address'),
                        'city' => $this->u_obj->getAttr('city'),
                        'province' => $this->u_obj->getMainProvince(),
                        'zipcode' => $this->u_obj->getAttr('zipcode'),
                        'mobile' => $this->u_obj->getAttr('mobile'),
                        'date_of_birth' => $this->u_obj->getAttr('dob')
                    ]);

                    return [
                        'success' => true,
                        'result' => [
                            'form' => [
                                'url' => $result['url'],
                                'fields' => $result['props'],
                            ]
                        ]
                    ];
                } catch (\Throwable $e) {
                    return $this->fail('err.unknown');
                }

            default:
                $extra['accountid'] = phive()->rmNonAlphaNums($args['accountid']);
                $extra['message']   = phive()->rmNonAlphaNumsNotSpaces($args['message']);
                break;
        }

        $failover = [];
        if ($this->action == 'deposit') {
            $res = $this->mts->deposit($this->u_obj, $cents, array_merge($this->extra, $extra));

            if(!$res['success']){
                $failover = $this->fallbackDepositDecorator->decorate($res);
                // We're looking at a fail and a potential failover option is fetched.
                $failover['failover'] = $this->cashier->addAndGetFailover($this->supplier, $this->limits_supplier);
            }  else {
                if (array_key_exists('paypal_to_trustly', $args) && $args['paypal_to_trustly']) {
                    $this->u_obj->setSetting('paypal_to_trustly_pending_tr_id', $res['result']['id']);
                }
            }
        } else {
            $res = $this->mts->depositRepeat($repeat_id, 0, 0, 0, 'recurring', $extra);
        }

        if (!$res['success']) {
            $res = array_merge($res, $failover);

            if(!empty($err_msg)){
                $res['errors'][] = $err_msg;
            }
        } else {
            // It is a success so we add the pending deposit.
            rgLimits()->addPendingDeposit($this->u_obj, $cents);
        }

        if (!$res['success']) {
            return $this->mts->errorsParse($res['errors'], "err.unknown");
        }

        return $res;
    }

    public function execute($action, $source, $args)
    {
        if (!empty($args['amount'])) {
            $amount_error = PhiveValidator::start($args['amount'])->amount()->error;

            if(!empty($amount_error)){
                return $amount_error;
            }
        }

        $prepaidDepositLimit = $this->cashier->hasPrepaidDepositLimit(
            $this->u_obj,
            (int)$args['amount'] * 100,
            $args['network'],
            $args['bin']
        );

        if (!empty($prepaidDepositLimit)) {
            return $prepaidDepositLimit;
        }

        /**
         * Check if the user has to forfeit bonuses before depositing
         * Note: This is a fail-safe mechanism, a user should never reach this point as it's blocked in the frontend
         * @see MobileDepositBoxBase::printHTML()
         * @see DesktopDepositBoxBase::printHTML()
         */
        $user = cu();
        if ($user instanceof DBUser && count($user->getBonusesToForfeitBeforeDeposit())) {
            return t('forfeit.deposit.blocked.error');
        }

        $this->startGoogleEvent($args);
        $this->setReloadCode($args);
        $this->mts->setDepositType($args['deposit_type'], $args['parent_id']);
        switch ($source) {
            case 'card':
                return $this->cardInit($action)->executeCard($action, $args);
            default:
                return $this->defaultInit($action, $args)->executeDefault($args);
        }
    }

    private function neosurfParams(int $cents): array
    {
        return [
            'user_id' => $this->u_obj->getId(),
            'country' => $this->u_obj->getCountry(),
            'amount' => $cents,
            'currency' => $this->u_obj->getCurrency(),
            'language' => $this->u_obj->getLang(),
            'url_success' => $this->mts->getReturnUrl($this->u_obj, '', $this->action),
            'url_fail' => $this->mts->getFailUrl($this->u_obj, $this->action),
            'url_pending' => $this->mts->getReturnUrl($this->u_obj, '', $this->action),
        ];
    }

    private function isNewPaymentFlow(string $supplier): bool
    {
        return $this->mts->getCcSetting()[$supplier]['new_payment_flow'] ?? false;
    }

    private function isCredoraxDepositViaOldEndpoint(): bool
    {
        return false;
    }

    private function trustlyParams(int $cents, array $args): array
    {
        $user = $this->u_obj;
        $userData = $user->data;

        $data = [
            'amount' => $cents,
            'currency' => $user->getCurrency(),
            'country' => $user->getCountry(),
            'locale' => $user->getAttr('preferred_lang') . '_' . $user->getAttr('country'),
            'successUrl' => $this->mts->getReturnUrl($user),
            'failUrl' => $this->mts->getFailUrl($user),
            'ip' => $userData['cur_ip'],
            'userId' => $user->getId(),
            'firstName' => $userData['firstname'],
            'lastName' => $userData['lastname'],
            'email' => $userData['email'],
            'mobile' => $userData['mobile'],
            'subSupplier' => $this->sub_supplier,
        ];

        if ($user->getNid()) {
            $data['personId'] = $user->getNid();
        }

        if ($args['repeat_id']) {
            $data['transactionId'] = $args['repeat_id'];
        }

        return $data;
    }

    private function credoraxParams(array $data, int $cents, bool $quick = false): array
    {
        $redirectUrls = new UrlHandler($this->u_obj, $data['network']);
        $browserInfo = $data['browserInfo'];

        $userData = new UserData(
            $this->u_obj->getId(),
            $this->u_obj->getCurrency(),
            $this->u_obj->getCountry(),
            $this->u_obj->getLang(),
            $this->u_obj->getFirstName(),
            $this->u_obj->getLastName(),
            $this->u_obj->getAddress(),
            $this->u_obj->getZipcode(),
            $this->u_obj->data['email'],
            $this->u_obj->data['dob'],
            $this->u_obj->getCity(),
            $this->u_obj->getMobile(),
            $this->u_obj->data['cur_ip'],
        );

        if ($quick) {
            $endpoint = CredoraxDeposit::quickDeposit(
                $cents,
                $userData,
                $data['credorax_encrypted_data'] ?? $data['cvc'],
                $redirectUrls->getDepositSuccessReturnUrl(),
                $redirectUrls->getDepositFailReturnUrl(),
                $browserInfo['screenHeight'],
                $browserInfo['screenWidth'],
                $this->u_obj->getSetting('uagent'),
                !empty(lic("isIntensiveGambler", [$this->u_obj], $this->u_obj)),
                (int) $data['repeat_id']
            );

            return $endpoint->getBody();
        }

        $endpoint = CredoraxDeposit::deposit(
            $cents,
            $userData,
            $data['credorax_encrypted_data'] ?? $data['cvc'],
            $redirectUrls->getDepositSuccessReturnUrl(),
            $redirectUrls->getDepositFailReturnUrl(),
            $browserInfo['screenHeight'],
            $browserInfo['screenWidth'],
            $this->u_obj->getSetting('uagent'),
            new CardData(
                $data['bin'],
                $data['cardHash'],
                $data['expiryMonth'],
                $data['expiryYear'],
            )
        );

        return $endpoint->getBody();
    }

    private function credoraxQuickDepositParams(array $data, int $cents): array
    {
        $redirectUrls = new UrlHandler($this->u_obj, $data['network']);

        return [
            'recurringId' => $data['repeat_id'],
            'amount' => $cents,
            'currency' => $this->u_obj->getCurrency(),
            'token' => $data['cvc']['PKey'],
            '3ds_method' => $data['cvc']['3ds_method'] ?? null,
            '3ds_compind' => $data['cvc']['3ds_compind'] ?? 'N',
            '3ds_version' => $data['cvc']['3d_version'],
            '3ds_trxid' => $data['cvc']['3ds_trxid'],
            'country' => $this->u_obj->getCountry(),
            'language' => $this->u_obj->getLang(),
            'user_id' => $this->u_obj->data['id'],
            'first_name' => $this->u_obj->data['firstname'],
            'last_name' => $this->u_obj->data['lastname'],
            'address' => $this->u_obj->data['address'],
            'zipcode' => $this->u_obj->data['zipcode'],
            'email' => $this->u_obj->data['email'],
            'date_of_birth' => $this->u_obj->data['dob'],
            'city' => $this->u_obj->data['city'],
            'phone' => $this->u_obj->data['mobile'],
            'ip' => $this->u_obj->data['cur_ip'],
            'uagent' => $this->u_obj->getSetting('uagent'),
            'url_success' => $redirectUrls->getDepositSuccessReturnUrl(),
            'url_fail' => $redirectUrls->getDepositFailReturnUrl(),
            'only_debit_card' => ! empty(lic("isIntensiveGambler", [$this->u_obj], $this->u_obj)),
            'browserInfo' => $data['browserInfo'],
        ];
    }

    /**
     * Creating tracking event and saving track key in user_settings.
     *
     * @param $args // deposit request params
     * @return // nothing
     */
    private function startGoogleEvent($args) {
        $cashier    = phive('Cashier');
        $count      = count($cashier->getFirstTimeDeposits($this->u_obj->getId()));
        $key        = "gtm_deposit_{$this->u_obj->getId()}_{$_REQUEST['network']}_{$_REQUEST['amount']}";
        $rand       = rand();
        $bonusCode  = !phive()->isEmpty(phive('Bonuses')->getCurReload($this->u_obj->getId())) ? phive('Bonuses')->getCurReload($this->u_obj->getId()) : 'no-bonus-code';

        $paymentData = [
            'user' => $this->u_obj->getId(),
            'first_deposit' => $count,
            'network' => $args['network'],
            'amount' => $args['amount'],
            'random' => $rand,
            'bonus_code' => $bonusCode,
        ];

        $this->u_obj->setSetting($key, base64_encode(json_encode($paymentData)));

        $googleEventkey = ($count === 0) ? 'started-first-deposit' : 'started-subsequent-deposit';
        $googleEventData =  [
            'triggered' => 'yes',
            'id' => $rand,
            'amount'=> $args['amount'],
            'deposit_type' => $args['network'],
            'deposit_data' => $args,
            'group_transaction_id' => $rand,
            'gtm_key' => $key,
            'bonus_code' => $bonusCode,
            'payment_method' => $args['network']
        ];

        $this->u_obj->setTrackingEvent($googleEventkey, $googleEventData);
    }

    /**
     * @param DBUser $u_obj
     * @param        $deposit_type
     * @param        $cents
     *
     * @return array|string|null
     */
    private function checkDepositOverLimit(DBUser $u_obj, $deposit_type, $cents)
    {
        list($res, $action, $params) = $this->cashier->checkOverLimits($u_obj, $cents);

        if ($deposit_type !== 'undo' && $res) {
            $this->error_action = $action;

            if ($action === 'customer_net_deposit') {
                return $this->fail('Customer exceed net deposit limit', false, $params ?? []);
            }
            return 'deposits.over.limit.html';
        }

        return null;
    }

    private function executeSwishDeposit($amount): array
    {
        $endpoint = new SwishDeposit(
            $amount,
            $this->u_obj->getCurrency(),
            $this->u_obj->getCountry(),
            $this->u_obj->getId(),
            $this->u_obj->getNid(),
            phive()->isMobileApp() ? '' : $this->mts->getReturnUrl($this->u_obj),
            phive()->isMobileApp() ? '' : $this->mts->getFailUrl($this->u_obj)
        );
        $endpoint->withDeviceType(phive()->deviceType());

        $settings = phive('Cashier')->getSetting('mts');
        $mtsSdkClient = MtsClientFactory::create(
            $settings['base_url'],
            $settings['key'],
            $settings['consumer_custom_id'],
            phive('Logger')->channel('payments'),
            true
        );

        $result = $mtsSdkClient->call($endpoint);

        if (!$result instanceof DepositInitResource) {
            return $this->fail('err.unknown');
        }

        rgLimits()->addPendingDeposit($this->u_obj, $amount);

        return [
            'success' => true,
            'result' => [
                'orderid' => $result->orderId,
                'url' => $result->url,
                'qrcode' => $result->qrcode,
            ]
        ];
    }
}
