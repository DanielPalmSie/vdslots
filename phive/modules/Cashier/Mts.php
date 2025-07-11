<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

// TODO henrik remove this, use the cashier config instead.
class Supplier
{
    const WireCard   = 'wirecard';
    const Worldpay   = 'worldpay';
    const Emp        = 'emp';
    const Adyen      = 'adyen';
    const Sofort     = 'sofort';
    const Entercash  = 'entercash';
    const Paysafe    = 'paysafe';
    const Citadel    = 'citadel';
    const NeoSurf    = 'neosurf';
    const Flexepin   = 'flexepin';
    const PayGround  = 'payground';
    const EcoPayz    = 'ecopayz';
    const EcoVoucher = 'ecovoucher';
    const Neteller   = 'neteller';
    const Skrill     = 'skrill';
    const Neosurf    = 'neosurf';
    const Paymentiq  = 'paymentiq';
    const Trustly    = 'trustly';
    const Instadebit = 'instadebit';
    const Siru       = 'siru';
    const Inpay      = 'inpay';
    const Swish      = 'swish';
    const Zimpler    = 'zimpler';
    const Mifinity   = 'mifinity';
    public const FLYKK      = 'flykk';
    public const SWISH = 'swish';
    public const PAYPAL = 'paypal';
    public const CREDORAX = 'credorax';
    public static $vouchers = [self::Flexepin, self::NeoSurf, self::Paysafe];
}

// TODO henrik remove this
class MtsError
{
    const InvalidTransaction = 100; //Generic error when we don't know what happened
    const InsufficientFunds = 101;
    const CardHolderNotParticipating = 102;
    const CvcWrong = 103;
    const NotPermitted = 104;
    const InsufficientFundsOrCreditLimit = 106;
    const CardHolderFailedOrCancelled = 107;
    const InvalidCard = 108;
    const CardExpired = 109;
    const CardStolen = 110;
    const UnableToVerifyEnrollment = 111;
    const RiskRejection = 112;
    const DuplicateRequest = 113;
    const SupplierOffline = 114;
    const TransactionNotFound = 115;
}

// TODO henrik remove this
class CardStatus
{
    const NotActive = 0;
    const Active = 1;
}


// TODO henrik remove this
class CardVerifyStatus
{
    const Unverified = 0;
    const Verified = 1;
    const Rejected = 2;
}

// TODO henrik remove this
class CardActions
{
    const DeleteScan = 'delete-scan';
    const Delete = 'delete';
    const Status = 'status';
    const Activate = 'activate';
}

/*
 * This class contains the logic we need in order to send proper calls to the MTS service.
 *
 * @deprecated Use \Videoslots\MtsSdkPhp\MtsClient instead
*/
class Mts
{
    /*
     * @var CasinoCashier an instance of the cashier so we don't have to write phive('Cashier') all the time.
     */
    public $cashier;

    /*
     * @var The mts section in the casino cashier config.
     */
    public $settings;

    /*
     * @var This is the supplier we will send as supplier => skrill for instance.
     */
    public $supplier;

    /*
     * @var Extra params to be sent to the MTS are stored here.
     */
    public $extra_params;

    /*
     * @var Extra params. Stored from $argv, but we don't send this data to MTS.
     */
    public $data_repository;

    /*
     * @var Local cache of the id of the user we're currently working with.
     */
    public $user_id;

    /*
     * @var Sub suppliers for the current suppliers are stored here.
     */
    public $sub_suppliers;

    /*
     * @var The type of deposit, currently only used in order to display custom return / success message for type undo.
     */
    public $deposit_type;

    /*
     * @see Cashier::transactUser() For more info on how it works with parent id.
     *
     * @var In case we're looking at a deposit that is undoing a withdrawal we put the id of the withdrawal here. It will later be used with transact user and stored in cash_transactions.parent_id.
     */
    public $parent_id;

    /*
     * @var The card hash of the currently used card on this form: 1111 11** **** 1111
     */
    public $cur_card_hash;

    // TODO henrik remove
    protected $headers;

    // TODO henrik remove
    protected $isMobile;

    // TODO henrik remove
    private $run_cnt;

    /**
     * The constructor, it sets a bunch of member variables.
     *
     * @param string $supplier The PSP to work with.
     * @param mixed $userId User identification information in order to fetch the user that is making the deposit / withdrawal.
     * @param $isMobile TODO henrik remove
     *
     * @return null
     */
    function __construct($supplier = '', $userId = 0, $isMobile = false)
    {
        $this->supplier           = $supplier;
        $this->user               = cu($userId); // Marshalling to enable $userId to be anything depending on the situation
        $this->user_id            = uid($userId); // Marshalling to enable $userId to be anything depending on the situation
        $this->isMobile           = $isMobile;
        $this->cashier            = phive('Cashier');
        $this->settings           = $this->cashier->getSetting('mts');
        $this->extra_params       = [];
        $this->db                 = phive('SQL');
        $this->run_cnt            = 0;
        $this->sub_suppliers      = [];
        $this->idempotent_methods = [];
        $this->failed_suppliers   = [];
        $this->cur_cc_bin         = null;
    }

    /**
     * Sets deposit type and parent id member variables.
     *
     * TODO henrik intify parent id.
     *
     * @param $deposit_type The type of deposit, currently only used in order to display custom return / success message for type undo.
     * @param int $parent_id In case we're looking at a deposit that is undoing a withdrawal we put the id of the withdrawal here. It will later be used with transact user and stored in cash_transactions.parent_id.
     *
     * @return xxx
     */
    public function setDepositType($deposit_type = null, $parent_id = null){
        $this->deposit_type = $deposit_type;
        $this->parent_id = $parent_id;
    }

    /**
     * Common phone number handling for PSPs that require a phone number as ID or otherwise.
     *
     * We first check the applicable user setting, if there is no setting we use the passed in
     * number by first cleaning it up (creating the canonical number) and then saving it as the user setting.
     *
     * @param DBUser $user The user to work with.
     * @param string $psp The PSP to work with.
     * @param string $phone Optional initial save of phone number.
     * @param bool $force_set If true we bypass the whole forced return of the setting if it exits.
     *
     * @return string The user's phone number for this PSP.
     */
    public static function setPhone($user, $psp, $phone = '', $force_set = false){
        if(!$force_set){
            $phone_setting = self::getPhone($user, $psp);
            if(!empty($phone_setting)){
                return $phone_setting;
            }
        }

        if(!empty($phone)){
            $phone = self::getCanonicalPhone($phone);
            $user->setSetting($psp.'_mobile', $phone);
        }

        return $phone;
    }

    /**
     * Creates the canonical phone number without leading zeroes or plusses.
     *
     * @param string $phone The phone number.
     *
     * @return string The canonical phone number.
     */
    public static function getCanonicalPhone($phone){
        return ltrim(phive()->rmNonNums($phone), '0');
    }

    /**
     * Wrapper around getting user phone number for current PSP that is using the phone number as ID or otherwise.
     *
     * @param DBUser $user The user.
     * @param string $psp The PSP.
     *
     * @return string|null The number if it exists, null otherwise.
     */
    public static function getPhone($user, $psp){
        $phone_setting = $user->getSetting($psp.'_mobile');
        return empty($phone_setting) ? null : $phone_setting;
    }

    // TODO henrik refactor this to use the casino cashier config.
    public static function getPsps(){
        return (new ReflectionClass('Supplier'))->getConstants();
    }

    /**
     * Gets all the configs, both the CC configs used for backend routing and the FE related configs.
     *
     * @return array The merged configs.
     */
    public function getAllPspConfigs(){
        return array_merge(phiveApp(PspConfigServiceInterface::class)->getPspSetting(), $this->getCcSetting());
    }

    /**
     * Returns the config setting for the passed in supplier.
     *
     * @param string|null Optional supplier, if omitted we try to get the set sub supplier and if not set the supplier.
     * @return array The settings.
     */
    public function getPspConfig($supplier = null){
        $supplier = $supplier ?? $this->getSubOrSupplier();
        return $this->getAllPspConfigs()[$supplier];
    }

    /**
     * Sets user info.
     *
     * @param mixed $u User id information.
     *
     * @return null
     */
    public function setUser($u){
        $this->user = cu($u);
        $this->user_id = uid($u);
    }

    /**
     * Used in XHR contexts to terminate execution and return results to the client.
     *
     * @param array $arr The array to json encode and send to the client.
     *
     * @return null
     */
    public static function stop($arr = ['success' => true]){
        echo json_encode($arr);
        exit;
    }

    /**
     * Logs in case logging is configured for the particular method.
     *
     * @see Phive::dumpTbl()
     *
     * @param string $tag The tag to use for the data log dump.
     * @param array $data The data to dump.
     * @param string $method The HTTP method so we can configure logging based on method, eg skip logging of GETs.
     *
     * @return string The key or empty string.
     */
    public function debugLog($tag, $data, $method = 'GET'){
        if($this->cashier->getSetting('mts_debug')[$method] === true){
            phive()->dumpTbl($tag, $data, $this->user ?? 0);
        }
    }

    /**
     * RPC wrapper for fraud / rg / aml related queries to the MTS, typically used in order to quickly get data from the MTS
     * that is needed on the Casino side in order to determine if a fraud flag is to be saved or not.
     *
     * @param string $method The class method to call on the MTS side.
     * @param array $args The arguments to pass to the method.
     * @param string $class The class to use on the MTS side, typically Arf.
     *
     * @return array An array of arrays, no objects as we pass in true as the second argument to json_decode.
     */
    public function arf($method, $args = [], $class = 'Arf'){
        $req = ['class' => $class, 'method' => $method, 'args' => $args];
        return $this->jsonPost('arf/execute', $req, 'ARF');
    }

    /**
     * Used for misc. custom calls to the MTS when we don't want to create a new endpoint.
     *
     * @param string $method The method to directly call on the PSP object.
     * @param array $params The arguments to pass to the method.
     *
     * @return array An array of arrays, no objects as we pass in true as the second argument to json_decode.
     */
    public function transferRpc($method, $params = [])
    {
        $params = array_merge(['method' => $method], $params);
        if (!empty($this->supplier)) {
            $params = array_merge(['supplier' => $this->supplier], $params);
        }
        return $this->jsonPost('transfer/call', $params, 'TRANSFER_RPC');
    }

    /**
     * More MTS RPC exposure, this time misc. stuff in the Rpc folder.
     *
     * @param string $action ATM we only have one: query.
     * @param string $class The RPC class to use.
     * @param string $method The method to call on the RPC class.
     * @param array $args The arguments to the method.
     *
     * @return array An array of arrays, no objects as we pass in true as the second argument to json_decode.
     */
    public function rpc($action, $class, $method, $args = []){
        $req = ['action' => $action, 'class' => $class, 'method' => $method, 'args' => $args];
        return $this->jsonPost('rpc/execute', $req, 'RPC');
    }

    /**
     * Gets the display name for the PSP.
     *
     * @param string $supplier The PSP.
     * @param string $sub_supplier Sub PSP override which will be used if supplied.
     *
     * @return string The configured display name if there is one, otherwise just the supplier or sub supplier internal name.
     */
    public function getDisplayName($supplier, $sub_supplier = null){
        $key = $sub_supplier ?? $supplier;
        $res = $this->getAllPspConfigs()[$key]['display_name'];
        return $res ?? $key;
    }

    /**
     * Factory method.
     *
     * @param string $supplier The PSP to work with.
     * @param mixed $user The user id.
     *
     * @return Mts The Mts instance.
     */
    public static function getInstance($supplier='', $userId=0)
    {
        return new Mts($supplier, $userId);
    }

    /**
     * Some e-bank options involve a KYC / bank account verification step upon withdrawal which forces us to have the user be redirected
     * to the PSP and when the user successfully validates / verifies we get a callback, it is only then that we set the withdrawal to
     * pending and also debit the amount from the user's balance.
     *
     * The initial withdraw status will be **initiated** and we do not do anything more than just creating the pending_withdrawals row
     * with that status in the database.
     *
     * @param array &$err An array with errors passed as reference that we will add an error to in case something goes wrong.
     * @param DBUser $user The user object.
     * @param int $cents The amount to withdraw.
     * @param int $pid The withdrawal id.
     * @param array $extra Extra parameters to send to the MTS.
     *
     * @return null We either add an error to the error array or stop execution and send the result JSON to the client / browser.
     * @throws Exception
     */
    public function doBankWithdrawalWithRedirect(&$err, $user, $cents, $pid, $extra = []){
        $extra = array_merge($this->getWithdrawBaseParams($user, $cents), $extra);

        if (!empty($pid)){
            $extra['customer_transaction_id'] = $pid;
        }

        $result = $this->withdraw(0, $user->getId(), $cents, '', $extra);

        if (!$result['success']) {
            $err["{$this->supplier}.error"] = !empty($result['result']['errors'])
                ? reset($result['result']['errors'])
                : "{$this->supplier}.unknown.error";

            return null;
        }

        $withdrawal = $this->cashier->getPending($pid);

        if (!empty($result['result']['id'])) {
            $update_array['mts_id'] = $result['result']['id'];

            phive('SQL')
                ->sh($withdrawal, 'user_id', 'pending_withdrawals')
                ->updateArray('pending_withdrawals', $update_array, ['id' => $withdrawal['id']]);
        }

        if (!empty($result['result']['url'])) {
            if ($extra['admin_source']) {
                return json_encode($result);
            }

            die(json_encode(['url' => $result['result']['url'], 'msg' => t2("max.withdrawal.is", [$cents / 100])]));
        }

        return null;
    }

    /**
     * Sets the sub supplier member variable.
     *
     * @param string $supplier The sub supplier.
     *
     * @return Mts in order to enable fluency / chaining.
     */
    public function setSubSupplier($supplier)
    {
        // TODO henrik we have both this and the de facto usage of passing in sub supplier in $extra,
        // refactor so we do it in only one way.
        $this->sub_supplier = $supplier;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubSupplier(): ?string
    {
        return $this->sub_supplier;
    }

    /**
     * Sets the supplier member variable.
     *
     * @param string $supplier The supplier.
     *
     * @return Mts in order to enable fluency / chaining.
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * Getter for the supplier member variable.
     *
     * @return string The supplier.
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    // TODO henrik remove
    function getCcBin($card_hash){
        return substr(str_replace(' ', '', $card_hash), 0, 6);
    }

    /**
     * Simple wrapper to the the card section in the cashier config.
     *
     * @return array The config.
     */
    function getCcSetting(){
        return phive('Cashier')->getSetting('ccard_psps');
    }

    /**
     * Returns all CC supplier config sections that are applicable for the current user.
     *
     * @uses CasinoCashier::doPspByConfig()
     *
     * @param string $action Deposit or withdraw.
     *
     * @return array The filtered array.
     */
    function getValidCcSupplierConfigs($action = 'deposit'){
        // Here we filter based on active / inactive, allowed countries etc.
        return array_filter($this->getCcSetting(), function($conf, $supplier) use ($action){
            if(empty($this->user)){
                return $conf[$action]['active'] === true;
            }

            return $this->cashier->doPspByConfig($this->user, $action, $supplier, null, 'ccard_psps');
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * If action is missing we simply return the full CC config, otherwise the result of getValidCcSupplierConfigs().
     *
     * @uses Mts::getValidCcSupplierConfigs().
     * @uses Mts::getCcSetting().
     *
     * @param string $action Deposit or withdraw.
     *
     * @return array The config array, possibly filtered if $action is provided.
     */
    function getCcSuppliers($action = ''){
        return array_keys(empty($action) ? $this->getCcSetting() : $this->getValidCcSupplierConfigs($action));
    }

    /**
     * Checks whether or not a supplier is a CC supplier or not.
     *
     * @param string $supplier The PSP.
     *
     * @return bool True if CC, false otherwise.
     */
    function isCcSupplier($supplier){
        return in_array($supplier, $this->getCcSuppliers());
    }

    /**
     * Returns the main CC providers that we typically default to using, a main provider must also support withdrawals to cards.
     *
     * @return array An array of all main providers as configured in the cashier config.
     */
    function getMainCcSuppliers(){
        return array_keys(array_filter($this->getCcSetting(), function($conf){
            return (bool)$conf['is_main'];
        }));
    }

    /**
     * Returns all card providers that are available for deposits for the current user.
     *
     * @return array An array of the providers.
     */
    function getInCcSuppliers(){
        return $this->getCcSuppliers('deposit');
    }

    /**
     * Gets all non card providers, this method does not filter with the help of user data but simply returns all
     * active non card PSPs.
     *
     * @param string $action Deposit or withdraw.
     *
     * @return array The array of PSPs.
     */
    function getNonCcSuppliers($action = 'deposit'){
        return array_keys(array_filter(phiveApp(PspConfigServiceInterface::class)->getPspSetting(), function($config) use ($action){
            return $config[$action]['active'] === true;
        }));
    }

    /**
     * Wrapper around getNonCcSuppliers() with the action hardcoded.
     *
     * @uses Mts::getNonCcSuppliers()
     *
     * @return array All the non card PSPs that support withdrawals and are active.
     */
    function getNonCcOutSuppliers(){
        return $this->getNonCcSuppliers('withdraw');
    }


    /**
     * Wrapper around getNonCcSuppliers() with the action hardcoded.
     *
     * @uses Mts::getNonCcSuppliers()
     *
     * @return array All the non card PSPs that support deposits and are active.
     */
    function getNonCcInSuppliers(){
        return $this->getNonCcSuppliers('deposit');
    }

    /**
     * Wrapper around getCcSuppliers()
     *
     * @uses Mts::getCcSuppliers()
     *
     * @return array The suppliers.
     */
    function getOutCcSuppliers(){
        return $this->getCcSuppliers('withdraw');
    }

    /**
     * Gets all active suppliers that support withdrawals.
     *
     * @return array The PSPs.
     */
    function getOutSuppliers(){
        return array_merge($this->getOutCcSuppliers(), $this->getNonCcOutSuppliers());
    }

    /**
     * Gets a CC supplier route, eg paymentiq for kluwp.
     *
     * @param string $supplier The supplier, eg kluwp.
     *
     * @return string The network, eg paymentiq.
     */
    function getCcRoute($supplier){
        return $this->cashier->getPspRoute($this->user, $supplier, 'ccard_psps');
    }

    /**
     * Gets all card PSPs that support client side encryption via logic that we load from our server.
     *
     * @return array The array of PSPs.
     */
    function getLocalJsCcs(){
        return $this->getCcPspsByKey('pub_key');
    }

    /**
     * Gets all card PSPs that support client side encryption.
     *
     * @return array The array of PSPs.
     */
    function getEncryptedCcs(){
        return array_merge($this->getLocalJsCcs(), $this->getExternalJsCcs());
    }

    /**
     * Gets all card PSPs that require us to load their JS externally.
     *
     * @return array The array of PSPs.
     */
    function getExternalJsCcs(){
        return $this->getCcPspsByKey('js_urls');
    }

    /**
     * Gets all card PSPs that has a non empty value in a certain field / key.
     *
     * @return array The array of PSPs.
     */
    function getCcPspsByKey($key){
        return array_filter($this->getValidCcSupplierConfigs('deposit'), function($conf, $supplier) use($key){
            return !empty($conf[$key]);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Handles override logic when we decide which card provider we want to route to, overrides can be based on specific BINs or
     * originators, VISA should perhaps go via provider X for Brits but MC via provider Y.
     *
     * @param string $country The user's country.
     * @param string $card_hash Obfuscated card number.
     * @param string $action Withdraw or deposit.
     * @param string $current_supplier The supplier that we want to choose if there is no override.
     *
     * @return string|bool The supplier override or false if we don't want to override.
     */
    function ccSupplierOverride($country, $card_hash, $action, $current_supplier = ''){
        $country = empty($country) ? $this->user->getCountry() : $country;
        // We check for a BIN override for the current country, eg Norwegians with MuchBetter cards should be routed via WC.
        if(!empty($card_hash) && $action == 'deposit'){
            $cur_bin = !empty($card_hash) ? substr(str_replace(' ', '', $card_hash), 0, 6) : $this->cur_cc_bin;
            if(strlen($cur_bin) == 6){
                foreach($this->getCcSetting() as $cc_psp => $config){
                    if($config['deposit']['active'] && in_array($cur_bin, $config['deposit']['included_bins'][$country]) && !in_array($cc_psp, $this->failed_suppliers)){
                        return $cc_psp;
                    }
                }
            }
        }

        $cc_configs = phive()->sort2d($this->getValidCcSupplierConfigs('deposit'), 'priority');

        $card_type = phive('WireCard')->getCardType(empty($card_hash) ? $this->cur_cc_bin : $card_hash);

        // We put the current supplier first in the array to give it highest priority.
        if(!empty($current_supplier)){
            $cc_configs = array_merge([$current_supplier => $this->getCcSetting()[$current_supplier]], $cc_configs);
        }

        // Checks if the user should use certain providers in a given sequence of deposits.
        if ($this->cur_card_hash) {
            $supplier = $this->getCreditCardProviderBySequences(array_diff(array_keys($cc_configs), $this->failed_suppliers));
            if ($supplier) {
                return $supplier;
            }
        }

        foreach($cc_configs as $supplier => $config){
            $originators = $config[$action]['originators'] ?? $config['originators'];
            if(in_array($card_type, $originators) && !in_array($supplier, $this->failed_suppliers)){
                // If the current supplier is valid, ie we support the originator etc then we return false as we do not want to override it.
                return $supplier == $current_supplier ? false : $supplier;
            }
        }

        return false;
    }

    /**
     * Checks the config 'ccard_psps_sequences' to see which apply to this user, if any, and returns the 1st provider for which the user
     * does not yet have a deposit with the current card. For a given card, we want 1 deposit for each provider and in the sequence order.
     *
     * @param array $providers The array of active providers for this user.
     * @return string|null The provider to use for the next deposit, or null if there is no override.
     * @example getCreditCardProviderBySequences(['worldpay', 'bambora']);
     */
    private function getCreditCardProviderBySequences(array $providers): ?string
    {
        if (!$providers || !$this->user) {
            return null;
        }
        $sequences = phive('Cashier')->getSetting('ccard_psps_sequences') ?: [];
        foreach ($sequences as $sequence) {
            if (in_array($this->user->getCountry(), $sequence['included_countries'])) {
                $override = $this->getCreditCardProviderBySequence($sequence['psps'], $providers);
                if ($override) {
                    phive()->dumpTbl('info ' . __METHOD__ . '::' . __LINE__, ['supplier_override' => $override, 'card_hash' => $this->cur_card_hash, 'sequence' => $sequence, 'providers' => $providers, 'failed_providers' => $this->failed_suppliers, 'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)], $this->user->getId());
                }
                return $override;
            }
        }
        return null;
    }

    /**
     * Returns the 1st provider in the sequence for which the user does not yet have a deposit with the current card.
     *
     * @param array $provider_sequence The sequence of providers to use for deposits with the current card number.
     * @param array $providers The array of active providers for this user.
     * @return string|null The provider to use for the next deposit, or null if there is no override.
     * @example getCreditCardProviderBySequence(['worldpay', 'bambora', 'worldpay'], ['worldpay', 'bambora']);
     */
    private function getCreditCardProviderBySequence(array $provider_sequence, array $providers): ?string
    {
        $sequence = array_intersect($provider_sequence, $providers);
        if (empty($sequence)) {
            return null;
        }

        $deposits = $this->getCreditCardProvidersByDeposits($this->user, $this->cur_card_hash, array_unique($sequence));
        $deposits = array_column($deposits, 'count', 'dep_type');
        foreach ($sequence as $provider) {
            if (!($deposits[$provider] ?? null)) {
                return $provider;
            }
        }
        return array_pop($sequence);
    }

    /**
     * Returns the credit card deposits for a user, grouped by provider.
     * This method does not check the deposit status because currently we only support 'approved' and 'disapproved', and 'disapproved'
     * is used both for a deposit which is immediately rejected and also for a successful deposit which is subsequently cancelled.
     *
     * @param DBUser $user The user.
     * @param string|null $card_hash If specified, indicates the card hash to find deposits for.
     * @param array|null $suppliers If specified, indicates the suppliers to find deposits for.
     * @return array The records grouped by supplier.
     * @example getCreditCardProvidersByDeposits($user, '411111******1111', ['worldpay', 'bambora']);
     */
    public function getCreditCardProvidersByDeposits(DBUser $user, ?string $card_hash = null, ?array $suppliers = null): array
    {
        $sql_suppliers = !$suppliers ? '' : ("AND dep_type IN (" . phive('SQL')->makeIn($suppliers) . ")");
        $sql_card_hash = !$card_hash ? '' : ("AND card_hash = " . phive('SQL')->escape($card_hash));

        $sql = <<<EOS
SELECT dep_type, COUNT(*) AS count
FROM deposits
WHERE
    user_id = {$user->getId()}
    AND scheme IN ('visa','mc', 'jcb', 'maestro', 'amex', 'card', 'applepay')
    {$sql_suppliers}
    {$sql_card_hash}
GROUP BY dep_type
EOS;
        $res = phive('SQL')->sh($user->getId())->loadArray($sql);
        return $res ?: [];
    }

    /**
     * Logic for getting the PSP to use for the CC transaction in question.
     *
     * Tries to:
     * 1. If we're looking at a deposit we inspect if we have a card hash or not, the absence of a card hash implies a non-PCI submit (ie Adyen), otherwise
     *     PCI submit (ie Emp or Wirecard).
     * 2. Then we check which supplier(s) have been used to make deposits, if Adyen hasn't been used adyen is picked, if Wirecard hasn't been used Wirecard is picked.
     * 3. If both have been used we load balance with the help of the config setting. BUT PCI compliance support needs to match our compliance level AND player's country can
     *     not be in the supplier's list of blocked countries.
     * 4. PSPs that are down are ignored at all times.
     *
     * @return string The supplier.
     */
    public function getCcSupplier($action = 'deposit', $card_hash = '', $card_id = null, array $extra = []){
        $currency = $this->user->getCurrency();
        $country  = $this->user->getCountry();

        $this->failed_suppliers = $this->getSess('failed_cc_suppliers');
        if(empty($this->failed_suppliers)){
            $this->failed_suppliers = [];
        }

        $cc_psps = array_keys(phive()->sort2d($this->getValidCcSupplierConfigs($action), 'priority'));
        $cc_psps = array_diff($cc_psps, $this->failed_suppliers);
        $cc_psps = is_array($extra['cc_psps']) ? array_intersect($cc_psps, $extra['cc_psps']) : $cc_psps;

        if(empty($cc_psps)){
            return false;
        }

        if(!empty($card_hash)){
            $where_card = " AND card_hash = '$card_hash' ";
        }

        if($action == 'deposit')
            $group_by = " GROUP BY dep_type ";
        else
            $limit = "LIMIT 1";

        $get_sql = function($cc_psps, $group_by = '', $limit = '') use ($where_card){

            $cc_in = $this->db->makeIn($cc_psps);

            $str   = "SELECT * FROM deposits
                        WHERE dep_type IN($cc_in)
                        AND user_id = {$this->user_id}
                        AND scheme IN('visa','mc', 'jcb', 'maestro', 'amex', 'card', 'applepay')
                        AND status = 'approved'
                        $where_card
                        $group_by ORDER BY id DESC $limit";

            return $str;

        };

        $str = $get_sql($cc_psps, $group_by, $limit);

        $res = phive('SQL')->sh($this->user_id)->loadArray($str, 'ASSOC', 'dep_type');

        if($action == 'withdraw'){

            // Card has not been used to deposit with with any of the proper PSPs, ie prior Wirecard deposits
            if(empty($res)){
                $str   = $get_sql($this->getOutCcSuppliers(), '', 'LIMIT 1');
                return phive('SQL')->sh($this->user_id)->loadAssoc($str)['dep_type'];
            }

            if(!empty($card_id)){
                // We get all stored transactions from the MTS
                $st_trs = $this->applyCached('getStoredTrsWhere');
                if(!empty($st_trs)){
                    $deposits = phive('SQL')->sh($this->user_id)->loadArray($get_sql($this->getOutCcSuppliers()), 'ASSOC', 'dep_type');
                    // We start looping the local deposits
                    foreach($deposits as $supplier => $dep){
                        // We loop all stored transactions for each local deposit
                        foreach($st_trs as $st_tr){
                            // The stored transaction is connected to the same card the player wants a payout to.
                            // We therefore check if the supplier has been deposited via, locally, if yes we can safely return the supplier.
                            if($card_id == $st_tr['card_id'] && $supplier == $st_tr['supplier']){
                                return $supplier;
                            }
                        }
                    }
                }
            }

            reset($res);
            return current($res)['dep_type'];
        }

        foreach($cc_psps as $supplier){
            if(empty($res[$supplier]) && !$this->isFailed($supplier)){
                return $supplier;
            }
        }

        // We remove suppliers who are down.
        $suppliers = array_values(array_diff($cc_psps, $this->failed_suppliers));

        // No need to load balance if we don't have more than one supplier left.
        if(count($suppliers) == 1){
            return $suppliers[0];
        }

        $routing = [];
        $full_config = $this->getCcSetting();

        foreach($suppliers as $supplier){
            $weight = $full_config[$supplier]['load_balance_weight'];

            if(empty($weight)){
                continue;
            }
            if(is_array($weight)){
                $weight = $weight[$country] ?? $weight['DEFAULT'];
            }
            $routing[$supplier] = $weight;
        }

        if(empty($routing)){
            // We're looking at a scenario where we don't have any routing, eg ['kluwp', 'emp'], so we just return the first one which should be the one
            // with the highest priority.
            return $suppliers[0];
        }

        $ret = phive()->loadBalance($routing);
        return $ret;
    }

    /**
     * POSTs JSON in the HTTP body to the MTS.
     *
     * @param string $call The last part of the endpoint.
     * @param array $params The params to json encode and put in the HTTP body.
     * @param string|null $log_key Log key for overriding the HTTP verbs.
     *
     * @return array An array of arrays, no objects as we pass in true as the second argument to json_decode.
     */
    function jsonPost($call, $params = [], $log_key = null){
        $content   = json_encode($params);
        $result    = phive()->post($this->settings['base_url'].$call, $content, 'application/json', ["X-API-KEY: ".$this->settings['key']], '', 'POST');
        $this->debugLog("MTS::$call", ['request' => $params, 'reply' => $result], $log_key ?? 'POST');
        return json_decode($result, true);
    }

    /**
     * The main HTTP method that does the HTTP POST to the MTS for non RPC calls.
     *
     * @param string $call The action.
     * @param array $params The data to send.
     * @param string $method The method, typically POST.
     *
     * @return array The result array.
     */
    public function request(string $call, array $params = [], string $method = 'POST', array $headers = []): array
    {
        if ($this->supplier != '') {
            // Here we potentially override the supplier to use at the last minute if we're looking at a card,
            // an example is kluwp -> paymentiq.
            $params['supplier'] = $this->isCcSupplier($this->supplier) ? $this->getCcRoute($this->supplier) : $this->supplier;
            if($params['supplier'] != $this->supplier){
                // In case we override the main supplier the actual supplier will become the sub supplier.
                $params['sub_supplier'] = $this->supplier;
            } else {
                $params['sub_supplier'] = $params['sub_supplier'] ?? $this->sub_supplier;
            }
        }

        if ($params['supplier'] == Supplier::EcoVoucher) {
            $params['supplier'] = Supplier::EcoPayz;
            $params['voucher'] = 1;
        }

        if ($method != 'GET') {
            $content = json_encode($params);
        } else {
            $content = '';
            $call .= '?'.http_build_query($params);
        }

        $normalizedHeaders = [];
        foreach ($headers as $key => $value) {
            $normalizedHeaders[] = $key . ': ' . $value;
        }

        $fdn = phive()->getSetting('full_domain');
        $response = phive()->post(
            $this->settings['base_url'] . $call,
            $content,
            'application/json',
            array_merge($normalizedHeaders, [
                'X-API-KEY: ' . $this->settings['key'],
                'X-CONSUMER-CUSTOM-ID: ' . $fdn,
            ]),
            '',
            $method,
            '',
            [],
            'UTF-8',
            true
        );

        if ($response === false) {
            return ['success' => false, 'errors' => ['MTS request failed.']];
        }

        $result = json_decode($response[0] ?? '', true);

        if (is_null($result)) {
            return ['success' => false, 'errors' => ['Invalid MTS response data format.']];
        }

        $s =
            (isset($params['supplier']) ? " {$params['supplier']}" : '') .
            (isset($params['sub_supplier']) ? " {$params['sub_supplier']}" : '')
        ;

        $this->debugLog("MTS::$call{$s}", ['request' => $params, 'reply' => $response[0] ?? null, 'url' => $this->settings['base_url'] . $call], $method);

        return $result;
    }

    /**
     * This is an override of the default behaviour which is to juse send the user back to a generic success / fail URL, so in case
     * we have something custom in the mts config section we use that instead.
     *
     * @param DBUser $u_obj The user object.
     * @param string $status Fail or success.
     *
     * @return bool|string The URL if we have an override, false otherwise.
     */
    public function getConfiguredReturnUrl($u_obj, $status){
        $base_url = phive('UserHandler')->getSiteUrl($u_obj->getCountry());

        if(!empty($this->cashier->getSetting('mts')[$status.'_url'])){
            return $base_url.llink($this->cashier->getSetting('mts')[$status.'_url'], $u_obj->getLang());
        }

        return false;
    }

    /**
     * Returns a fail URL.
     *
     * @param DBUser $u The user object.
     * @param string $action Deposit or withdraw.
     *
     * @return string The URL.
     */
    function getFailUrl($u, $action = 'deposit', $reason = 'fail'){
        $configured_url = $this->getConfiguredReturnUrl($u, $reason);
        if(!empty($configured_url)){
            return $configured_url;
        }

        return $this->getReturnUrl($u, '&action=' . $reason, $action);
    }

    /**
     * A simple wrapper around return the sub supplier / originator / scheme if it exists and if not, the network / main supplier.
     *
     * @return string The sub supplier or supplier.
     */
    function getSubOrSupplier(){
        return $this->sub_supplier ?? $this->supplier;
    }

    /**
     * Gets the params needed for webview if those are present on the url.
     * display_mode & auth_token and returns them as string
     *
     * @return string webview URL parameters.
     */
    function getWebViewRequestParams(): string {
        $webviewExtra = '';

        if (isset($_GET['display_mode'])) {
            $webviewExtra .= '&display_mode=' . $_GET['display_mode'];
        }

        return $webviewExtra;
    }

    /**
     * Gets the return URL based on config settings and player language.
     *
     * A setting of iframe => false will make sure that the cashier box opens properly on return.
     *
     * @param mixed $u User data to work with, can be a DBUser object, id or an array.
     * @param string $extra Extra arguments, typically '&status=failed' to generate a fail url.
     *
     * @return string The return URL.
     */
    function getReturnUrl($u, $extra = '', $action = 'deposit'){

        $configured_url = $this->getConfiguredReturnUrl($u, 'success');
        if(!empty($configured_url)){
            return $configured_url;
        }

        $ud       = ud($u);
        $base_url = phive('UserHandler')->getSiteUrl($ud['country']);
        $lang     = $ud['preferred_lang'];

        if(!empty($this->cashier->getSetting('mts')['success_url'])){
            return $base_url.llink($this->cashier->getSetting('mts')['success_url']);
        }

        // There is no route for "/cashier/repeat" so change it to a route which exists. See db.pages.
        // "$action = 'repeat'" is set for Trustly in phive/modules/BoxHandler/boxes/diamondbet/CashierDepositBoxBase.php::renderStandardRepeats
        $url_action = ($action === 'repeat') ? 'deposit' : $action;
        $page     = "/cashier/$url_action/?end=true&supplier=".$this->getSubOrSupplier();

        if (!empty($this->deposit_type)) {
            $extra .= '&d_type='. $this->deposit_type;
        }

        $webviewParams = $this->getWebViewRequestParams();
        if (!empty($webviewParams)) {
            $extra .= $webviewParams;
        }

        $psp_config = $this->getPspConfig();
        $psp_iframe_config = $psp_config['iframe_overrides'][$u->getCountry()] ?? $psp_config['iframe'];

        if(!phive()->isMobile() && $psp_iframe_config === false && $action !== 'withdraw'){
            return $base_url.llink('/?show_deposit=true&show_url=', $lang).urlencode($base_url.llink($page, $lang).$extra);
        }

        $return_url = (phive()->isMobile() ? '/mobile' : '').$page;

        // Check if we have some special scenario to handle and return the URL, otherwise we proceed normally.
        $overridden_url = $this->getOverriddenReturnUrl($return_url, $extra, $_POST['action'], $_POST['deposit_type'], $_POST['override_return_url_type']);
        if(!empty($overridden_url)) {
            return $overridden_url;
        }

        return $base_url.llink($return_url, $lang).$extra;
    }

    /**
     * PSPs typically require 3 URLs for the abort / cancel, fail and success scenario, they will send the user
     * back to the corresponding one, depending on what happened. This method will supply the PSP with those URLs.
     *
     * @param DBUser $u_obj The user object.
     * @param string $action Despoit or withdraw.
     *
     * @return array The array of URLs.
     */
    function getUrls($u_obj, $action){
        return [
            'cancel_url' => $this->getFailUrl($u_obj, $action, 'cancel'),
            'fail_url'   => $this->getFailUrl($u_obj, $action),
            'return_url' => $this->getReturnUrl($u_obj, '', $action)
        ];
    }

    /**
     * Common method to handle special cases for return_url on interactions with PSP.
     *
     * If you need to add special interaction for a single page, you can add the following variable in the JS of the page
     * "cashierOverrideReturnUrlType" and specify a type to handle in this function.
     *
     * Ex: When player does an "undo" of a withdrawal we need to redirect him to "account-history" instead of
     *     the generic deposit success/fail page on mobile OR popup on desktop.
     *
     * @param $return_url - standard url
     * @param $extra - extra qs params
     * @param $action - deposit|withdrawal
     * @param $deposit_type - undo
     * @param null $override_type - ingame-iframe-box | account-history
     * @return bool|string
     */
    public function getOverriddenReturnUrl($return_url, $extra, $action, $deposit_type, $override_type = null)
    {
        if (empty($override_type)) {
            return false;
        }

        // deposit from mobile in game
        if(phive()->isMobile() && $action === 'deposit' && $override_type === 'ingame-iframe-box') {
            $query_string = explode('?', $return_url)[1];
            return phive('Casino')->getAjaxBoxInIframeUrl($action, $query_string).$extra;
        }

        // undo from account history page
        if($action === 'deposit' && $override_type === 'account-history' && $deposit_type === 'undo') {
            return phive('UserHandler')->getUserAccountUrl('account-history');
        }

        return false;
    }

    /**
     * A simple wrapper with the $action argument to getBaseParams() hardcoded.
     *
     * @uses Mts::getBaseParams()
     *
     * @param DBUser $u The user object.
     * @param int $cents The amount to withdraw.
     *
     * @return array The base params.
     */
    function getWithdrawBaseParams($u, $cents){
        return $this->getBaseParams($u, $cents, 'withdraw');
    }


    /**
     * A simple wrapper with the $action argument to getBaseParams() hardcoded.
     *
     * @uses Mts::getBaseParams()
     *
     * @param DBUser $u The user object.
     * @param int $cents The amount to deposit.
     *
     * @return array The base params.
     */
    function getDepositBaseParams($u, $cents){
        return $this->getBaseParams($u, $cents, 'deposit');
    }

    //$cents

    /**
     * Gets the base parameters we send to the MTS regardless of the PSP we're working with atm.
     *
     * @param DBUser $u The user object.
     * @param int $cents The amount
     * @param string $action Deposit or withdraw.
     *
     * @return array The base params.
     */
    function getBaseParams($u, $cents, $action)
    {
        $u          = cu($u);
        $ud         = $u->data;
        $urls       = $this->getUrls($u, $action);
        $base       = $this->getArgsFromUserData($u);
        $params     = ['amount' => $cents, 'supplier' => $this->supplier];

        unset($ud['id']);
        $params['ip']          = $ud['cur_ip'];
        $params['phone']       = $ud['mobile'];
        $params['sid']         = $u->getCurrentSession()['id'];
        $params['uagent']      = $u->getSetting('uagent');
        $params['uaccept']     = $_SERVER['HTTP_ACCEPT'];
        $params['psp_user_id'] = (string)$u->getSetting($this->supplier.'_user_id');

        return array_merge($base, $params, $urls);
    }

    /**
     * Some PSPs do not support a certain currency but we want players with that currency to
     * be able to deposit / withdraw via that PSP anyway, if that is the case we do FX on our
     * side before we send the call to the MTS.
     *
     * @param array $params The parameters to send to the MTS that we now override amount and currency on.
     * @param string $action Deposit or withdraw.
     *
     * @return array The potentially changed params.
     */
    public function fxOverride($params, $action)
    {
        $sub_supplier = $this->sub_supplier ?? $params['sub_supplier'];
        $full_config  = $this->cashier->getFullPspConfig();

        $fx_currency = $this->fxCountryCurrency($full_config, $sub_supplier, $action, $params['country']);

        if (!$fx_currency) {
            $fx_config = $full_config[$this->supplier][$action]['fx'] ?? [];
            if (!$fx_config && $sub_supplier) {
                $fx_config = $full_config[$sub_supplier][$action]['fx'] ?? [];
            }

            if (!$fx_config) {
                return $params;
            }

            $fx_currency = is_string($fx_config) ? $fx_config : $fx_config[$params['currency']];
        }

        if ($fx_currency && $params['currency'] != $fx_currency) {
            $old_currency = $params['currency'];
            $old_amount = $params['amount'];
            $params['amount']   = (int)round(chg($params['currency'], $fx_currency, $params['amount'], 1));
            $params['currency'] = $fx_currency;
            phive()->dumpTbl(__METHOD__ . '::' . __LINE__, [
                "message" => "Converting transaction currency from $old_currency $old_amount to $fx_currency {$params['amount']}.",
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'request' => $_REQUEST,
                'args' => $params,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
            ], 0);
        }

        return $params;
    }

    protected function fxCountryCurrency($full_config, $sub_supplier, $action, $country)
    {
        $fx_currency = $full_config[$this->supplier][$action]['fx_country'][$country] ?? null;
        if (!$fx_currency && $sub_supplier) {
            $fx_currency = $full_config[$sub_supplier][$action]['fx_country'][$country] ?? null;
        }

        return $fx_currency;
    }

    /**
     * If we get a notification from the MTS and the currency differs we do FX to the
     * user's currency.
     *
     * @param array $args The parameters we got from the MTS.
     * @param DBUser $u_obj The user object.
     *
     * @return array The parameters with a possibly new amount.
     */
    public function doNotifyFx($args, $u_obj){
        if(!empty($args['currency']) &&  $args['currency'] != $u_obj->getCurrency()){
            $args['amount'] = ceil(chg($args['currency'], $u_obj, $args['amount'], 1));
        }
        return $args;
    }

    /**
     * Client side encryption failed for some reason, perhaps networking error when loading external JS, so we have to
     * skip this particular CC as the encrypted data is missing.
     *
     * @param $arr The POST data.
     *
     * @return null
     */
    public function failMissingEncryptionData($arr){
        foreach($this->getEncryptedCcs() as $psp => $conf){
            if(isset($arr[$psp."_encrypted_data"]) && empty($arr[$psp."_encrypted_data"])){
                $this->failed_suppliers = $psp;
            }
        }
    }

    /**
     * Sets card specific data that will be sent to the MTS.
     *
     * @param array $arr The array of data to set.
     *
     * @return Mts We return $this in order to enable chaining / fluency.
     */
    public function setCreditCardData($arr)
    {
        $map = [
            'ccard_num' => 'cardnumber',
            'cvv' => 'cv2',
            'expire_date' => 'expirydate',
        ];

        $tmp = phive()->mapit($map, $arr);

        foreach($this->getEncryptedCcs() as $psp => $conf){
            if(isset($arr[$psp."_encrypted_data"]) && !empty($arr[$psp."_encrypted_data"])){
                $tmp[$psp."_encrypted_data"] = $arr[$psp."_encrypted_data"];
            }
        }

        $this->extra_params = $tmp;
        return $this;
    }

    public function setExtraData($arr)
    {
        $this->data_repository = $arr;
    }

    protected function getExtraData()
    {
        $dataRepository = $this->data_repository;

        if ($this->supplier === Supplier::Worldpay) {
            return [
                'session_id' => $dataRepository['session_id'] ?? null,
                'province' => $dataRepository['province'] ?? null,
                'browserInfo' => $dataRepository['browserInfo'] ?? []
            ];
        }

        return [];
    }

    /**
     * Sets Trustly specific info to send to the MTS.
     *
     * @param DBUser $user User object.
     * @param string $sub_supplier Origin / source, typically a bank like SEB or Nordea.
     * @param string $action Deposit or withdraw.
     *
     * @return array The array to merge with the base array that will be sent to the MTS.
     */
    public function getTrustlyExtra($user, $sub_supplier = '', $action = 'deposit'){
        $extra_wd = [
            'person_id'    => $user->getNid(),
            'success_url'  => $this->getReturnUrl($user, '', $action),
            'fail_url'     => $this->getFailUrl($user, $action),
            'url_target'   => '_self',
            'locale'       => $user->getAttr('preferred_lang') . '_' . $user->getAttr('country'),
            'ip'           => remIp(),
            'sub_supplier' => strtolower($sub_supplier),
        ];



        if ($action === 'withdraw') {
            $country = phive('Localizer')->getBankCountryByIso($user->getAttribute('country'));

            if (!empty($country)) {
                $extra_wd['clearinghouse'] = strtoupper($country['name']);
            }

            $extra_wd['address'] = $user->getFullAddress();
            $extra_wd['dob'] = $user->getAttr('dob');
        }
        return array_merge(ud($user), $extra_wd);
    }

    /**
     * @param $user
     * @param string $sub_supplier
     * @param string $action
     * @return array
     */
    public function getIdealExtra($user, $sub_supplier = '', $action = 'deposit'): array
    {
        $org_sub_supplier = $this->getSubSupplier();
        $this->setSubSupplier($this->supplier);
        $return_url = $this->getReturnUrl($user, '', $action);
        $this->setSubSupplier($org_sub_supplier);

        return [
            'return_url'  => $return_url,
            'ip'           => remIp(),
            'sub_supplier' => strtolower($sub_supplier)
        ];
    }

    // TODO Get rid of this logic if possible as I don't like having a mutable like this, better pass as arg instead which
    // is functionality that already exists, no need for the current confusing multiple ways of doing things /Henrik
    public function setExtraParams(array $params)
    {
        $this->extra_params = $params;
        return $this;
    }

    // TODO henrik remove this
    public function setIdealData($u)
    {
        $ud = is_array($u) ? $u : $u->data;

        $this->extra_params = [
            'ideal' => 1,
            'return_url' => $this->getReturnUrl($ud)
        ];
    }

    // TODO henrik remove
    public function depositValidate($code)
    {
        return  $this->request('user/transfer/deposit/validate', ['supplier' => $this->supplier, 'code' => $code], 'GET');
    }

    /**
     * Overrides the sub supplier in case several have been configured in the cashier config, is for example used
     * in order to load balance Swish deposits between SEB and Swedbank.
     *
     * @param array $params Instead of passing by reference we pass in the parameters array we might change and return it.
     * @param string $action Deposit or withdraw.
     *
     * @return array The parameters with the sub_supplier key possibly changed.
     */
    public function overrideSubSupplier($params, $action = 'deposit'){
        $config = $this->getPspConfig($this->supplier)[$action];
        if(!empty($config['sub_suppliers'])){
            $params['sub_supplier'] = phive()->loadBalance($config['sub_suppliers']);
        }
        return $params;
    }

    /**
     * Basic stuff that needs to be done before we send a deposit call to the MTS. We also perform the call itself here.
     *
     * @param string $action Various deposit related actions like authorize, capture or simply deposit.
     * @param DBUser $u The user object.
     * @param int $amount The amount to deposit.
     * @param array $extra Extra params to send to the MTS.
     *
     * @return array The MTS result array.
     */
    public function depositCommon($action, $u, $amount, $extra = array())
    {
        if(!empty($this->deposit_type)){
            $extra['deposit_type'] = $this->deposit_type;
            $extra['parent_id'] = $this->parent_id;
        }

        if(!empty($this->cur_card_hash)){
            $extra['card_hash'] = $this->cur_card_hash;
        }

        $params = $this->getDepositBaseParams($u, $amount);
        // TODO this looks ridiculous, refactor so we have ONE extra array, NOT 2, it just adds complexity for zero value in return!
        $params = array_merge($params, (array)$this->extra_params);
        $params = array_merge($params, (array)$extra);
        $params = $this->overrideSubSupplier($params, 'deposit');
        $params = $this->fxOverride($params, 'deposit');

        $result = $this->request(
            'user/transfer/'.$action,
                $params ?? [],
            'POST',
            ['Device-Type' => phive()->deviceType()],
        );

        if(!empty($result['result']['psp_user_id'])){
            $u->setSetting($this->supplier.'_user_id', $result['result']['psp_user_id']);
        }

        return $result;
    }

    public function validateMerchant($params){
        return $this->request('merchant/validate', $params);
    }

    /**
     * Wrapper around adding a value to the mts session array.
     *
     * @param string $main_k The main array to add a value to.
     * @param mixed $v The value.
     *
     * @return mixed Returns the value to set to enable simultaneous assignment in the caller context.
     */
    public function addSess($main_k, $v){
        if(empty($sub_k))
            $_SESSION['mts'][$main_k][] = $v;
        return $v;
    }

    /**
     * Wrapper around setting a value in the mts session array.
     *
     * @param string $main_k The main array to focus on.
     * @param string $sub_k The sub array key to focus on, if empty we assume that the main key does
     * not point to an array, but is just an atom.
     * @param mixed $v The value to set.
     *
     * @return mixed Returns the value to set to enable simultaneous assignment in the caller context.
     */
    public function setSess($main_k, $sub_k, $v){
        if(empty($sub_k))
            $_SESSION['mts'][$main_k] = $v;
        else
            $_SESSION['mts'][$main_k][$sub_k] = $v;
        return $v;
    }


    /**
     * Wrapper around getting a value from the mts session array.
     *
     * @param string $main_k The main array to focus on.
     * @param string $sub_k The sub array key to focus on, if empty we assume that the main key does
     * not point to an array, but is just an atom.
     *
     * @return mixed The value to get with the help of the keys.
     */
    public function getSess($main_k, $sub_k = ''){
        if(empty($sub_k))
            return $_SESSION['mts'][$main_k];
        else
            return $_SESSION['mts'][$main_k][$sub_k];
    }


    /**
     * Wrapper around deleting a value from the mts session array.
     *
     * @param string $main_k The main array to focus on.
     * @param string $sub_k The sub array key to focus on, if empty we assume that the main key does
     * not point to an array, but is just an atom.
     *
     * @return Mts Returns $this to enable chaining / fluency.
     */
    public function unsetSess($main_k, $sub_k = ''){
        if(empty($sub_k))
            unset($_SESSION['mts'][$main_k]);
        else
            unset($_SESSION['mts'][$main_k][$sub_k]);
        return $this;
    }

    /**
     * The main deposit entry point for making deposit calls to the MTS.
     *
     * This method resets the 3D secure session data to avoid stale data when / if player makes another CC deposit.
     * The 3D data caching is needed in order to reconcile two different PSP behaviours:
     *
     * 1. The PSP supports an enroll check which we initially call in order to determine if we're looking at a 3D flow or not.
     *     If we are not looking at a 3D flow we make a subsequent call to charge the non 3D card immediately. If we are looking
     *     at a 3D card we would have gotten the redirect URL in the first call so we do not call the MTS again but just redirect
     *     immediately.
     *
     * 2. The PSP does not support an enroll check but will charge a non 3D card immediately, in this case we will make the
     *     authorize call directly, we're done and there is no need for a subsequent capture call, we simply return
     *     success to the client in case of a non 3D card. In case of a 3D card we redirect the user as per usual.
     *
     * @param DBUser $u The user object.
     * @param int $amount The amount to deposit.
     * @param array $extra Extra parameters to send to the MTS.
     * @param string $c_hash Obfuscated card number.
     *
     * @return array The MTS result array.
     */
    public function deposit($u, $amount, $extra = [], $c_hash = ''){
        // We have an authorized transaction with a 3D card, nothing more to do than just return.
        if(!empty($this->getSess('3d_result', $c_hash)['url'])){
            $res               = $this->getSess('3d_result', $c_hash);
            $res['success']    = true;
            $res['result']     = $res;
            // If we have a successful deposit call we add the amount to the pending amount
            rgLimits()->addPendingDeposit($u, $amount);
            return $res;
        }

        // We supply mts_id if it exists, will be used by Adyen to capture a non-3D deposit, a 3D is take care of by the
        // above conditional anyway.
        $res = $this->depositCommon('deposit', $u, $amount, array_merge($extra, $this->getExtraData(), ['mts_id' => $this->getSess('3d_result', $c_hash)['id']]));
        // We remove the session data as we're now at the end of the line, nothing more to do, next deposit is a new player initiated action.
        $this->unsetSess('3d_result');
        return $res;
    }

    /**
     * Wrapper around depositCommon() in order to make an authorize card call.
     *
     * @param DBUser $u The user object.
     * @param int $amount The amount to deposit.
     * @param array $extra Extra params to send to the MTS.
     *
     * @return array The MTS result array.
     */
    public function authorize($u, $amount, $extra = array()){
        if(empty($extra['bin'])){
            $extra['bin'] = $_POST['bin'];
        }

        $extra['only_debit_card'] = !empty(lic("isIntensiveGambler", [$u], $u));

        return $this->depositCommon('deposit/authorize', $u, $amount, array_merge($extra, $this->getExtraData()));
    }

    /**
     * Wrapper around depositCommon() in order to make a capture card call.
     *
     * @param DBUser $u The user object.
     *
     * @return array The MTS result array.
     */
    public function capture($u){
        return $this->depositCommon('deposit/capture', $u, 0);
    }

    /**
     * Makes a repeat / recurring / oneclick deposit call to the MTS. The main thing here is that we need the id that the repeat / recurring transction
     * has in the MTS database.
     *
     * @param int $repeated_id The id of the repeat / recurring transaction in the MTS database, this table in turn
     * contains the external PSP token / id that is needed.
     * @param int $amount The amount to deposit, typically not needed as the stored transaction's amount is the one that will be used.
     * @param int $amount_thold The threshold, if not empty will be checked agains the stored amount on the MTS side and if it is lower
     * the MTS will refuse. This is a fraud protection feature as we can not shift liability to the card issuer for repeats.
     * @param int $cvc The card CVC, is often needed in order to authorize the repeat transaction.
     * @param string $type The repeat type, recurring or oneclick, one click in a card context implies / means the need for a CVC verification.
     * @param array $extra_params Extra params to send to the MTS.
     *
     * @return array The MTS return array.
     */
    public function depositRepeat($repeated_id = 0, $amount = 0, $amount_thold = 0, $cvc = 0, $type = 'recurring', $extra_params = [])
    {
        $u          = cu($this->user_id);
        $ud         = $u->data;
        $base_arr   = $this->getDepositBaseParams($u, $amount);

        $arr = [
            'amount_thold' => (int)$amount_thold,
            'repeated_id'  => (int)$repeated_id,
            'cvc'          => $cvc,
            'type'         => $type,
            'only_debit_card' => !empty(lic("isIntensiveGambler", [$u], $u)),
        ];

        return $this->request(
            'user/transfer/deposit/repeat',
            array_merge($base_arr, $arr, $extra_params, $this->getExtraData()),
            'POST',
            ['Device-Type' => phive()->deviceType()],
        );
    }

    /**
     * Simple mapping from casino database column names to what the MTS accepts, ie more reasonable names like
     * birth_date instead of dob.
     *
     * @param DBUser $u_obj User object.
     *
     * @return array The re-mapped data array.
     */
    public function getArgsFromUserData($u_obj){
        $ud = $u_obj->data;
        // Most of the below is total BS to have to send because for some reason Paymentiq needs the data.
        return [
            'user_id'      => $ud['id'],
            'currency'     => $ud['currency'],
            'country'      => $ud['country'],
            'language'     => $ud['preferred_lang'],
            'first_name'   => $ud['firstname'],
            'last_name'    => $ud['lastname'],
            'birth_date'   => $ud['dob'],
            'address'      => $ud['address'],
            'zipcode'      => $ud['zipcode'],
            'email'        => $ud['email'],
            'city'         => $ud['city'],
            'sex'          => $ud['sex'],
            'dob'          => $ud['dob'],
            'mobile'       => $ud['mobile'],
            'ip'           => $ud['cur_ip'],
            'cash_balance' => $ud['cash_balance'],
            'device'       => phive()->isMobile() ? 'mobile' : 'desktop'
        ];
    }

    /**
     * This method sends the withdraw call to the MTS upon P&F approval of the pending withdrawal.
     *
     * @param int $customerTransactionId Pending withdrawal id.
     * @param int $userId The user id the withdrawl belongs to.
     * @param int $cents The amount to withdraw.
     * @param int $cardId The id of the card on the MTS side if this is a card withdrawal.
     * @param array $extra_params Extra params to send to the MTS.
     *
     * @return array The MTS result.
     */
    public function withdraw($customerTransactionId, $userId, $cents, $cardId = '', $extra_params = []): array
    {
        $user = cu($userId);
        $settings = $user->getSettingsIn(['uagent', 'main_province'], true);

        $data = array_merge(
            [
                'amount'                  => $cents,
                'card_id'                 => $cardId,
                'nid'                     => $user->getNid(),
                'customer_transaction_id' => $customerTransactionId,
                'psp_user_id'             => $user->getPspUid($this->supplier),
                'user_country'            => $user->getCountry(),
                'province'                => $settings['main_province']['value'] ?? null,
                'uagent'                  => $settings['uagent']['value'] ?? null,
            ],
            $this->getArgsFromUserData($user),
            $extra_params,
            ['sid' => $user->getCurrentSession()['id']]
        );

        $data = $this->fxOverride($data, 'withdraw');

        return $this->request('user/transfer/withdraw', $data, 'POST', [
            'Device-Type' => phive('Cashier')->getPendingWithdrawalEquipment($user, $customerTransactionId),
        ]);
    }

    /**
     * A simple transformer that will convert an MTS result array into something that can be used with list() in order to
     * easily create separate variables for the return data.
     *
     * @param array $res The MTS result array.
     * @param string $msg Error message in case the MTS result is a failure.
     *
     * @return array A one dimensional result array for use with list() in the caller context
     */
    public function withdrawResult($res = '', $msg = '')
    {
        $notify_agent = true;
        $success = false;

        if (!empty($res['result'])) {
            $mts_id = $res['result']['id'];
            $ext_id = $res['result']['reference_id'];
        }

        if (!empty($res['success'])) {
            $success = true;
            $notify_agent = false;
        }

        $errors = $this->errorsFromResponse($res, $msg);

        return [$mts_id, $ext_id, $success, $errors, $notify_agent];
    }

    private function errorsFromResponse($res, $msg): ?string
    {
        if (!empty($res['code'])) {
            return sprintf('mts-code-%d', $res['code']);
        }

        if (empty($res)) {
            return !empty($msg) ? $msg : $this->supplier . ': MTS communication problem';
        }

        if (empty($res['errors'])) {
            return null;
        }

        return is_array($res['errors']) ? implode('. ', $res['errors']) : (string)$res['errors'];
    }

    /**
     * Gets external account information from the MTS, eg all EcoPayz accounts that have been used by a player.
     *
     * @param string $supplier Main PSP / network PSP.
     * @param string $sub_supplier Sub PSP / source PSP.
     *
     * @return array An array of the accounts.
     */
    public function getAccounts($supplier = '', $sub_supplier = '')
    {
        $params = ['user_id' => $this->user_id, 'supplier' => $supplier, 'supplier' => $supplier, 'sub_supplier' => $sub_supplier];
        $result = $this->request('user/accounts/list', $params, 'GET');
        return phive()->reKey($result, 'id');
    }

    /**
     * Gets a card from the MTS based on the obfuscated card number.
     *
     * @param string $hash The obfuscated card number.
     *
     * @return array The card.
     */
    public function getCardFromHash($hash){
        foreach($this->getCards() as $c)
        {
            if($c['card_num'] == $hash)
                return $c;
        }
        return [];
    }

    /**
     * Get repeated transactions for display.
     *
     * Will get all recurring_transactions from the MTS that are applicable for repeat / quick deposit for the user in question.
     *
     * @param $user_id The user id.
     * @param $max_amount The max amount that should be possible to repeat, in the user's currency.
     *
     * @return The result array.
     */
    public function getRepeated($user_id, $max_amount = null, $supplier = ''){
        $params             = empty($max_amount) ? [] : ['max_amount' => $max_amount];
        $params['supplier'] = $supplier;
        return $this->request("recurring/user/$user_id/forrepeat/", $params, 'GET')['repeats'];
    }

    /**
     * Applies Mts methods and stores the result in a member variable, used when we want to prevent unnecessary / repeat calls to external
     * services in order to get the same data.
     *
     * @param $method The method to call as a string.
     * @param $args The arguments that the method needs, an arbitrary amount.
     *
     * @return mixed The result.
     */
    public function applyCached(){
        $args   = func_get_args();
        $method = array_shift($args);
        if(isset($this->$method)){
            return $this->$method;
        }
        $this->$method = call_user_func_array([$this, $method], $args);
        return $this->$method;
    }

    /**
     * Gets recurring transactions from the MTS for the current user.
     *
     * @param array $where Optional WHERE filters.
     *
     * @return array The transactions.
     */
    public function getStoredTrsWhere($where = []){
        if(!empty($this->user_id)){
            $where['user_id'] = $this->user_id;
        }

        return $this->request("recurring/where/", $where, 'GET');
    }

    /**
     * Gets a card from the MTS.
     *
     * @param string $card_number The obfuscated card number.
     * @param string $expiry_date The expiry date on the MM/YY format.
     *
     * @return array The card.
     */
    public function getCard($card_number, $expiry_date)
    {
        if (empty($this->user_id)) {
            return false;
        }

        $expDate = explode('/', $expiry_date); //todo use an unique function also used in isDuplicate, it is here for now to let changes and don't affect the other function
        $dates = [(int) $expDate[0], (int) ((strlen($expDate[1]) == 4) ? $expDate[1] : '20'.$expDate[1])];
        $params = [
            'user_id' => $this->user_id,
            'card_number' => $card_number,
            'expiry_month' => $dates[0],
            'expiry_year' => $dates[1]
        ];

        $result = $this->request('user/cards/get', $params, 'GET');

        if (empty($result['success'])) {
            return false;
        }

        return phive()->reKey($result['card'], 'id');
    }


    /**
     * Gets a list of cards from the MTS.
     *
     * TODO cardstatus, remove $active and $canWithdrawOnly, cards are / will be filtered by way of a dmapi call anyway.
     *
     * @param int $cardId The id of a single card in the MTS, if supplied only that card will be returned.
     * @param array $suppliers An array of suppliers to filter on.
     * @param int $amount_thold Will return cards with deposits under this threshold amount if passed in.
     *
     * @return array The cards.
     */
    public function getCards($cardId = 0, $suppliers = false, $amount_thold = 0)
    {
        $params = ['user_id' => $this->user_id, 'can_withdraw' => 1, 'card_id' => $cardId];

        if(!empty($amount_thold)){
            $params['amount_thold'] = $amount_thold;
        }

        if ($suppliers !== false) {
            $params['suppliers'] = implode(',', $suppliers);
            $params['suppliers'] = trim($params['suppliers'], ',');
        }

        $result = $this->request('user/cards/list', $params, 'GET');

        if ($cardId != 0) {
            if (isset($result[0])) {
                return $result[0];
            }
            return false;
        }

        return phive()->reKey($result, 'id');
    }

    // TODO henrik remove
    public function getSubSuppliers($country = '', $type = 'deposit')
    {
        // $type = withdrawal / deposit /

        if (!empty($this->sub_suppliers)) {
            return $this->fetchSubSuppliersList($type);
        } else {
            $params = [
                'supplier' => $this->supplier,
                'country' => $country
            ];

            if(empty($this->idempotent_methods[__METHOD__.'-'.$type])){
                $result = $this->request('get-sub-suppliers', $params, 'GET');
                $this->sub_suppliers = $result['result'];
            }

            $this->idempotent_methods[__METHOD__.'-'.$type] = true;
            return $this->fetchSubSuppliersList($type);
        }
    }

    // TODO henrik remove
    private function fetchSubSuppliersList($type)
    {
        $items = [];

        foreach ($this->sub_suppliers as $item) {
            if ($type == 'deposit' && $item['deposit'] == 1) {
                $items[$item['system_name']] = $item;
            }
            else if ($type == 'withdrawal') {
                if ($item['withdrawal'] == 1) {
                    $items[$item['system_name']] = $item;
                }
                else if (!isset($items[$item['system_name']]) && $items[$item['system_name']]->withdrawal == 0) {
                    $items[$item['system_name']] = $item;
                }
            }
        }

        return $items;
    }

    // TODO henrik remove
    public function isDuplicate($card_num, $exp_date)
    {
        $expDate = explode('/', $exp_date);
        $dates   = [(int) $expDate[0], (int) ((strlen($expDate[1]) == 4) ? $expDate[1] : '20'.$expDate[1])];
        $params  = ['user_id' => $this->user_id, 'card_num' => $card_num, 'exp_year' => $dates[1], 'exp_month' => $dates[0]];
        $result  = $this->request('user/cards/duplicate', $params, 'GET');
        return $result['success'];
    }

    /**
     * Used in case we need to move a user from one currency to another.
     *
     * @param int $userId The old / current user id.
     * @param int $newUserId The new user id after the move.
     *
     * @return bool True if success, false otherwise.
     */
    public function changeCardOwner($userId, $newUserId)
    {
        $result = $this->request('user/cards/change-owner', ['user_id' => $userId, 'new_user_id' => $newUserId], 'POST');
        return $result['success'];
    }

    // TODO henrik remove
    public function isActiveAndValid($card_num)
    {
        $params = ['user_id' => $this->user_id, 'card_num' => $card_num];

        phive()->dumpTbl('mts-card-check-out', $params, $this->user_id);

        $result = $this->request('user/cards/check', $params, 'GET');

        phive()->dumpTbl('mts-card-check-in', $result, $this->user_id);

        return $result['success'];
    }

    /**
     * Resets session variables and a setting that affects system behaviour in relation to card deposits.
     *
     * **sms_tries**: a user can only attempt at verifying by way of SMS for non 3D cards 3 times before betting temporary blocked.
     * **n-quick-deposits**: we reset this user setting, it controls how many quick / repeat / oneclick deposits that can be
     * made before a normal deposit with the full 3D flow has to be made again.
     *
     * TODO remove the failed_deposits session variable reset, it is not used anymore.
     * TODO remvoe the pass by reference, objects are always passed by ref.
     *
     * @param DBUser $user The user object.
     *
     * @return null
     */
    public function resetOnSuccess(&$user)
    {
        $_SESSION['failed_deposits'] = 0;
        $_SESSION['sms_tries'] = 0;
        $user->setSetting('n-quick-deposits', 0);
    }

    /**
     * Resets the 3D secure cached return data, typically used on 3D return in order enable a new deposit with a 3D card.
     *
     * @param $user TODO henrik remove this.
     *
     * @return null
     */
    public function resetOnReturn($user = ''){
        $this->unsetSess('3d_result');
        // We reset the fast / latest deposit session cache.
        unset($_SESSION['fast_deposit']);
        unset($_SESSION['fast_deposits']);
    }

    /**
     * This class supports automatic failovers between suppliers, ie if the first choice is down we can try another.
     * This method simply check if the passed in supplier / PSP is in the array / cache of failed suppliers.
     *
     * @param string $supplier The supplier.
     *
     * @return bool True if it is failed, false if not.
     */
    public function isFailed($supplier){
        return in_array($supplier, $this->failed_suppliers);
    }

    /**
     * Adds a supplier to the cache of failed suppliers.
     *
     * @param string $supplier The supplier to add.
     *
     * @return null
     */
    public function failSupplier($supplier){
        $this->failed_suppliers[] = $supplier;
        $this->addSess('failed_cc_suppliers', $supplier);
    }

    /**
     * Automatic recursive failover wrapper.
     *
     * Used in cases where we want to failover between different credit card PSPs (for now) in case one is down.
     * Keeps track of which supplier to use internally over several different calls, like check3d followed by deposit.
     * Example call: $mts->failover('check3d', $c_hash, $u, $amount, $c_hash);
     *
     * @param mixed The parameters we want to use with the real method, first two must always be the method and the card hash.
     *
     * @return mixed The result of the call to the MTS.
     */
    public function failover(){

        $this->fcnt++;
        if($this->fcnt > 4){
            exit;
        }

        $args           = func_get_args();
        $orig_args      = $args;
        $method         = array_shift($args);
        $action         = $method == 'withdraw' ? 'withdraw' : 'deposit';
        $cached_actions = ['deposit', 'check3d'];
        $c_hash         = array_shift($args);

        $override       = $this->ccSupplierOverride($this->user->getCountry(), $c_hash, $action, $_SESSION['cc_supplier']);

        // The whole CC supplier selection currently happens when the deposit / withdraw box
        // is loaded as the client side is necessarily too coupled with the server logic.
        if(!empty($_SESSION['cc_supplier']) && empty($override)){
            $this->supplier = $_SESSION['cc_supplier'];
            // We unset this here as it is only needed intially, we'll keep track of the CC PSP via 3d_result from now on if need be.
            // DO NOT remove or uncomment this line as doing that will totally break automatic failover.
            unset($_SESSION['cc_supplier']);
        } else if(!empty($override)) {
            $this->supplier = $override;
        }

        if(in_array($method, $cached_actions)){
            $cur_supplier = $this->getSess('3d_result', $c_hash)['supplier'];
            if(!empty($cur_supplier)){
                $this->supplier = $cur_supplier;
            }
        }

        $orig_supplier = $this->supplier;

        $res = call_user_func_array([$this, $method], $args);

        if(empty($res) || ($res['success'] === false && $res['errors']['code'] == 114)){
            $this->failSupplier($this->supplier);
        }else{
            $this->unsetSess('failed_cc_suppliers', $this->supplier);
        }

        // If we have a 3D card in the session it will have priority, if we're looking at a
        // withdrawal we ignore session data.
        if(!empty($c_hash) && !empty($this->getSess('3d_result', $c_hash)) && in_array($method, $cached_actions)){
            $three_d_result = $this->getSess('3d_result', $c_hash);
            if(!$this->isFailed($three_d_result['supplier'])){
                return $three_d_result;
            }else{
                // We've ended up in a situation where the initial authorise call was successful but then the
                // a subsequent call has failed, this should not happen as no other calls should be made between
                // the initial check3d / authorize call and the deposit call.
                phive()->dumpTbl('mts-exception', ['failed_suppliers' => $this->failed_suppliers, 'args' => $args], $this->user);
                return false;
            }
        }

        if(empty($res) || ($res['success'] === false && $res['errors']['code'] == 114)){
            // Mark current supplier as failed.
            //$this->failSupplier($this->supplier);

            // We get a new random supplier which is not a failed supplier.
            $this->supplier = $this->getCcSupplier($action, $c_hash);
            // echo "{$this->fcnt}-4, {$this->supplier}\n";
            // We stop if getCcSupplier has exhausted all options then we're fucked, not much more to do than give up.
            if(empty($this->supplier) || $this->supplier == $orig_supplier)
                return false;
            // Invoke self again using a different supplier.
            return call_user_func_array([$this, 'failover'], $orig_args);
        }

        // We get a random supplier if this is the first time during exection that we call failover, otherwise we pick the supplier we're working with.
        if(empty($this->supplier))
            $this->supplier = $this->getCcSupplier($action, $c_hash);

        return $res;
    }

    /**
     * Gets whether a card is 3D or not.
     *
     * This method authorizes a card if the supplier is Adyen, in that case we need to stay with Adyen
     * for the remainder of this session for this card or until the transaction has failed or succeeded,
     * 3D or no 3D.
     *
     * @param DBUser $u The DBUser object.
     * @param int $amount The amount to deposit in cents.
     * @param string $c_hash The obfuscated card number.
     *
     * @return array The result.
     *
     * @uses authorize
     */
    public function check3d($u, $amount, $c_hash){
        if(!empty($this->getSess('3d_result', $c_hash))){
            return $this->getSess('3d_result', $c_hash);
        }

        $func   = $this->getCcSetting()[$this->supplier]['3d_check'] ?? 'authorize';
        $result = $this->$func($u, $amount);

        if(!$result['success']){
            return $result;
        }

        $res = $this->setSess('3d_result', $c_hash, array_merge($result['result'], ['supplier' => $this->supplier]));

        return $res;
    }

    /**
     * We check if we have 3D secure return by way of the Supplier::$css array.
     *
     * @return bool True if we have a return, false otherwise
     */
    public function isCcReturn(){
        foreach($this->getCcSuppliers() as $psp){
            if(!empty($_REQUEST[$psp.'_end'])){
                return true;
            }
        }
        return false;
    }

    /**
     * Sends an enrollment check to the MTS for a certain card, the card info needs to be stored in
     * $this->extra_params before calling this method.
     *
     * @param DBUser $u User object.
     * @param int $amount The amount that is to be deposited.
     *
     * @return array MTS result array.
     */
    public function enrollOnly($u, $amount)
    {
        $params = $this->getDepositBaseParams($u, $amount);
        $params = array_merge($params, $this->extra_params);
        $result = $this->request('user/cards/enroll', $params);
        return $result;
    }

    // TODO henrik remove, this needs to be implemented 100% inside a test class instead.
    public function testFailover($c_hash = ''){
        //print_r(['mts_f>ailed' => $_SESSION['mts']]);
        print_r(['current_supplier' => $this->supplier, 'cnt' => $this->fcnt]);
        return ['success' => false, 'errors' => ['code' => 114]];
    }

    /**
     * Handles error message display, returns a localized string key / alias no matter what.
     *
     * @param array $errors Either an array of error strings or a fail result from the MTS.
     * @param string $default The default string.
     *
     * @return string The localized string key / alias.
     */
    public function errorsParse($errors, $default = null)
    {
        $map = [
            // wirecard
            101 => 'mts.card_insufficient_funds.error',
            102 => 'mts.card_holder_not_participating.error',
            103 => 'mts.card_cvc_code_wrong.error',
            104 => 'mts.card_not_permitted.error',
            106 => 'mts.card_limit_exceeds.error',
            107 => 'mts.card_holder_failed_or_cancelled.error',
            108 => 'mts.card_invalid.error',
            109 => 'mts.card_expired.error',
            110 => 'mts.card_stolen.error',
        ];

        if (!empty($errors['code']) && isset($map[$errors['code']])) {
            return $map[$errors['code']];
        }

        $errorStrings = [
            'credit.card.not.allowed',
            'credit.card.blocked',
            'card.bin.not.allowed'
        ];

        foreach ($errors as $error) {
            if (in_array($error, $errorStrings)) {
                return $error;
            }
        }

        return $default ?? 'mts.transaction_failed.error';
    }

    /**
     * Gets cards by the first six numbers
     *
     * @param mixed $u Some unique user info.
     * @param array $bin_codes The bincodes to use for fetching cards.
     *
     * @return array The cards from that user with those bin codes.
     */
    public function getCardsByBincodes($u, $bin_codes){
        return @$this->jsonPost('user/cards/get-by-bincodes', ['user_id' => uid($u), 'bin_codes' => $bin_codes], 'BIN_CODES')['cards'];
    }

    /**
     * Gets card type from card hashes
     *
     * @param array $card_hashes
     *
     * @return array The card type
     */
    public function getCardType($card_hashes){
        return @$this->jsonPost('user/cards/get-card-type', ['card_numbers' => $card_hashes], 'CARD_TYPE');
    }

    /**
     * Adapter for Apple/Google Pay
     * Just delegates all work to getCreditCardProviderBySequences method
     *
     * @param array $providers The array of active providers for this user.
     * @return string|null The provider to use for the next deposit, or null if there is no override.
     * @example getAppleOrGooglepayProviderBySequences(['worldpay', 'credorax']);
     */
    public function getAppleOrGooglepayProviderBySequences(array $providers): ?string
    {
        return $this->getCreditCardProviderBySequences($providers);
    }
}
