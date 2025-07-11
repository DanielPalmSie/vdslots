<?php

namespace App\Classes\GoAML;

use App\Classes\GoAML\Enum\TransactionType;
use App\Classes\Mts;
use App\Models\Deposit;
use App\Models\FxRate;
use App\Models\User;
use App\Models\Withdrawal;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Silex\Application;
use SimpleXMLElement;

class GoAML
{
    const PROVIDER_TITLES = [
      'ecopayz' => 'EcoPayz',
      'prepaid_card' => 'Prepaid Card',
      'astro' => 'Prepaid Card',
      'smsvoucher' => 'Prepaid Card',
      'ecovoucher' => 'Prepaid Card',
      'entropay' => 'Prepaid Card',
      'flexepin' => 'Flexepin',
      'skrill' => 'Skrill',
      'trustly' => 'Trustly',
      'puggle' => 'Zimpler Mobile',
      'siru' => 'Siru Mobile',
      'neosurf' => 'Neosurf',
      'instadebit' => 'Instadebit',
      'paysafe' => 'Paysafecard',
      'payground' => 'SMSVoucher',
      'neteller' => 'Neteller',
      'citadel' => 'Citadel',
      'interac' => 'Interac',
      'paypal' => 'Paypal',
      'ideal' => 'iDeal',
      'worldpay' => 'WorldPay',
      'wirecard' => 'Wirecard',
      'visa' => 'VISA',
      'mc' => 'MasterCard',
      'adyen' => 'Adyen',
      'entercash' => 'Entercash',
      '' => 'ANNAN',
    ];

    const CARD_TYPES = [
      "visa" => "/^4/",
      "visa2" => "/^[vV]/",
      "mc" => "/^5[1-5]/",
      "mc2" => "/^[mM]/",
    ];

    const SUFFIX_TRANSACTION_TYPE = [
        'deposit' => 'DEP',
        'withdrawals' => 'WIT',
        'failed_deposit' => 'FDE'
    ];

    const COMMENT_FAILED_TRANSACTION = 'Failed Transaction';

    private $mts_accounts;

    const PAYMENT_METHOD_TYPE = [
        'entercash' => TransactionType::BANK,
        'puggle' => TransactionType::MOBILE,
        'adyen' => TransactionType::BANK,
    ];

    /**
     * Generate and export the report for GoAML
     *
     * @param Application $app
     * @param $user
     * @param $jurisdiction
     * @param $comment
     * @param $report_type
     * @param $indicators
     *
     * @param $all_request_data
     * @return bool|string
     * @throws \Exception
     */
    public function export(
        Application $app,
        $user,
        $jurisdiction,
        $comment,
        $report_type,
        $indicators,
        $all_request_data
    ) {
        $currency = $user->currency;

        $transactions_withdrawals = Withdrawal::query()
          ->select(
              'pending_withdrawals.id',
              'timestamp',
              'currency',
              'amount',
              'payment_method',
              'status',
              'ip_num',
              'ext_id',
              'payment_method',
              'scheme',
              'swift_bic',
              'iban',
              'bank_name',
              'bank_account_number',
              'fx.multiplier'
          );
        $transactions_withdrawals = $this->leftJoinWithFxRates($transactions_withdrawals)->where('user_id', $user->getKey())->get()->toArray();

        $transactions_deposits = Deposit::query()
          ->select(
              'deposits.id',
              'timestamp',
              'currency',
              'amount',
              'dep_type',
              'ip_num',
              'ext_id',
              'dep_type',
              'scheme',
              'card_hash',
              'display_name',
              'fx.multiplier'
          );
        $transactions_deposits = $this->leftJoinWithFxRates($transactions_deposits)->where('user_id', $user->getKey())->get()->toArray();

        $mts = new Mts($app, 30);

        $user_mts_account = $mts->getUserAccounts($user->id);

        if (!is_null($user_mts_account)) {
            $this->setMtsAccounts($user_mts_account);
        }

        $failed_deposits = $mts->getFailedDepositsLite(
            $user->id,
            $user->register_date,
            Carbon::now()->toDateTimeString()
        );

        $export = new Export();

        $brand = new Brand($app);
        $brand_infos = $brand->getBrandInfos(phive('Distributed')->getSetting('local_brand'));

        $transactions_chunked = array_chunk(array_merge($transactions_deposits, $transactions_withdrawals), 1000);

        $chunks_length = count($transactions_chunked);
        /**
         * Go AML platform has a limit of 1000 transactions_withdrawals per report
         * so we have to separate our transactions_withdrawals into chunks of 1000 each
         * we can create a new xml file for each 1000 transactions_withdrawals
         */
        foreach ($transactions_chunked as $key => $transaction_chunk) {
            // if the transaction number is lower than 1000 we want to export the xml directly
            // else we generate a zip file and add all the xml files inside and export that one
            if (($chunks_length + count($failed_deposits)) > 1) {
                $xml = $this->createReportAndPersonNode($currency, $report_type, $comment, $jurisdiction, $brand_infos);

                foreach ($transaction_chunk as $transactions) {
                    if (isset($transactions['dep_type'])) {
                        $this->addDeposits((object)$transactions, $user, $xml, $all_request_data, $jurisdiction, $brand_infos);
                    } else {
                        $this->addWithdrawals((object)$transactions, $user, $xml, $all_request_data, $jurisdiction, $brand_infos);
                    }
                }

                if (isset($indicators) && !empty($indicators)) {
                    $this->addReportIndicators($xml, $indicators);
                }

                if ($key === $chunks_length) {
                    $this->addFailedDepositsLoop($failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand_infos);
                }

                $export->prepareZipFile($xml);
            } else {
                $xml = $this->createReportAndPersonNode($currency, $report_type, $comment, $jurisdiction, $brand_infos);
                $this->addTheTransactionLoops($transactions_withdrawals, $transactions_deposits, $failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand_infos);
            }
        }
        // in case of no results for the given user and jurisdiction redirect back and add a message to the session
        if ($chunks_length == 0) {
            return false;
        }

        if ($chunks_length == 1) {
            if (isset($indicators) && !empty($indicators)) {
                $this->addReportIndicators($xml, $indicators);
            }

            return $export->exportGoAML($xml, $user, $jurisdiction);
        } else { // if there are more than one xml file we want to export the zip file
            $this->addFailedDepositsLoop($failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand_infos);
            $export->prepareZipFile($xml);

            $export->exportZipTransactions($user, $jurisdiction);
        }

        return true;
    }

    /**
     * Return the data we have on mts accounts table for a given supplier
     *
     * @param $supplier
     * @return mixed
     */
    public function getMtsAccount($supplier)
    {
        foreach ($this->mts_accounts as $mts_account) {
            if ($mts_account['supplier'] == $supplier) {
                return $mts_account;
            }
        }
    }

    /**
     * Set the suppliers we have on mts
     *
     * @param $accounts
     */
    public function setMtsAccounts($accounts)
    {
        $this->mts_accounts = $accounts;
    }

    /**
     * Add the loops for the transaction when its a single xml
     *
     * @param $transactions_withdrawals
     * @param $transactions_deposits
     * @param $failed_deposits
     * @param $user
     * @param $xml
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    private function addTheTransactionLoops($transactions_withdrawals, $transactions_deposits, $failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand)
    {
        foreach ($transactions_withdrawals as $transaction) {
            $this->addWithdrawals((object)$transaction, $user, $xml, $all_request_data, $jurisdiction, $brand);
        }

        foreach ($transactions_deposits as $transaction) {
            $this->addDeposits((object)$transaction, $user, $xml, $all_request_data, $jurisdiction, $brand);
        }

        $this->addFailedDepositsLoop($failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand);
    }

    /**
     * add the failed deposits
     *
     * @param $failed_deposits
     * @param $user
     * @param $xml
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    private function addFailedDepositsLoop($failed_deposits, $user, $xml, $all_request_data, $jurisdiction, $brand)
    {
        foreach ($failed_deposits as $transaction) {
            $this->addFailedDeposits((object)$transaction, $user, $xml, $all_request_data, $jurisdiction, $brand);
        }
    }

    /**
     * Create the initial Reporting node with the reporting person
     *
     * @param $currency
     * @param $report_type
     * @param $comment
     * @param $jurisdiction
     * @param $brand
     * @return SimpleXMLElement
     * @throws \Exception
     */
    private function createReportAndPersonNode($currency, $report_type, $comment, $jurisdiction, $brand)
    {
        $xml = $this->createReportingNodeWithEntity($jurisdiction->getDefaultCurrency($currency), $brand, $report_type);
        $this->addReportingPersonNode($xml, $comment, $jurisdiction, $brand);

        return $xml;
    }

    /**
     * Create the maine node for the report with the reporting entity node as
     * child
     *
     * @param $currency
     *
     * @param $brand
     * @param string $report_type
     * @return SimpleXMLElement
     */
    public function createReportingNodeWithEntity(
        $currency,
        $brand,
        $report_type = "STR"
    ) {
        //Formated Todays date
        $date = $this->formatDateForAml(Carbon::now());

        $xml = simplexml_load_file($this->getResourcesFolder() . "report.xml");
        $xml->rentity_id = "REPORTING_ENTITY_ID";
        $xml->rentity_branch = $brand->institution_name;
        $xml->submission_code = "E";
        $xml->report_code = $report_type;
        $xml->entity_reference = "ENTITY_REFERENCE_ID_PLACEHOLDER";
        $xml->report_date = $date;
        $xml->currency_code_local = $currency;

        return $xml;
    }

    /**
     * Format the given date to the one required by AML YYYY-MMDDTHH:MM:SS
     *
     * @param $date
     *
     * @return string
     */
    public function formatDateForAml($date)
    {
        return Carbon::parse($date)->format("Y-m-d\TH:i:s");
    }

    /**
     * Return the resources path for goaml xmls
     *
     * @return string
     */
    public function getResourcesFolder()
    {
        return __DIR__ . "/../../../resources/xml/goaml/";
    }

    /**
     * Add the reporting person with the comment (Reason) to the xml
     *
     * In order to preserve order of the nods we need to add them manually to
     * the report xml because the validator takes the ordering in consideration
     *
     * Fields that are generated with "addOptionalFields" function are nodes
     * that are optional
     *
     * @param $xml
     * @param $comment
     * @param $jurisdiction
     * @param $brand
     * @throws \Exception
     */
    public function addReportingPersonNode($xml, $comment, $jurisdiction, $brand)
    {
        /** @var User $actor */
        $actor = UserRepository::getCurrentUser();

        $r_person_dob = $this->formatDateForAml($actor->dob);

        $this->addNodesWithValues('gender', $actor->sex[0], $xml->reporting_person);
        $xml->reporting_person->first_name = $actor->firstname;
        $xml->reporting_person->last_name = $actor->lastname;
        $this->addNodesWithValues('birthdate', $r_person_dob, $xml->reporting_person);
        $this->addNodesWithValues('nationality1', 'NATIONALITY_PLACEHOLDER', $xml->reporting_person);
        $this->addNodesWithValues('email', $actor->email, $xml->reporting_person);

        if ($jurisdiction->isNodeActive('location')) {
            $this->addLocation($xml, $brand, $jurisdiction->getSelectedJurisdiction());
        }

        $this->addNodesWithValues('reason', $comment, $xml);
        $this->addNodesWithValues('action', 'ACTION_PLACEHOLDER', $xml);
    }

    /**
     * Add fields to the XML if the value is set, it will add a child to the
     * given xml node
     *
     * @param $field_name
     * @param $field_value
     * @param $xml
     */
    public function addNodesWithValues($field_name, $field_value, $xml)
    {
        if (!empty($field_value)) {
            $xml->addChild($field_name, $field_value);
        }
    }

    /**
     * This function will get the provider title and the type
     * and it will check if is the type card if its a master or visa vcard
     *
     * @param $transaction
     * @param $provider
     * @param $provider_title
     * @param $type
     * @param $jurisdiction
     */
    public function getProviderTitleAndType($transaction, &$provider, &$provider_title, $type, $jurisdiction)
    {
        $method = $this->getPaymentMethod($transaction);

        $provide = phive('CasinoCashier')->getPspSettingDeprecated($method);

        if (empty($provide)) {
            $card_type = self::CARD_TYPES;

            if ($type == 'deposit') {
                $type = '';
                $this->visaOrMasterDeposit($transaction, $card_type, $type);
            } else {
                $this->visaOrMasterWithdrawal($transaction, $card_type, $type);
            }
            if (isset($type)) {
                $provide = phive('CasinoCashier')->getPspSettingDeprecated($type);
            }
        }

        $provider = $jurisdiction->getPaymentType($provide['type']);

        if ($provide['type'] == 'mobile' && $method === 'swish') {
            $provider = $jurisdiction->getPaymentType('swish');
        }

        if ($provide['type'] == 'mobile' && $method === 'siru') {
            $provider = $jurisdiction->getPaymentType('siru');
        }

        if ($method == 'zimpler') {
            $provider = $jurisdiction->getPaymentType('zimpler');
        }

        if ($method === 'puggle') {
            $provider = $jurisdiction->getPaymentType('puggle');
        }

        if ($method === 'entercash') {
            $provider = $jurisdiction->getPaymentType('entercash');
        }

        $provider_title = self::PROVIDER_TITLES[$method];
        if (!isset($provider_title)) {
            $provider_title = $provide['display_name'];
        }
    }

    /**
     * Add the deposits to the xml
     *
     * @param $deposit
     * @param $user
     * @param $xml
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    public function addDeposits(
        $deposit,
        $user,
        $xml,
        $all_request_data,
        $jurisdiction,
        $brand
    ) {
        $transactions_xml = '';

        $this->getProviderTitleAndType($deposit, $provider, $provider_title, 'deposit', $jurisdiction);

        $this->addTransactionNode($deposit, $transactions_xml, $jurisdiction, $provider_title, $all_request_data);

        $this->addJurisdictionBasedNode($transactions_xml, $deposit, 'late_deposit', $jurisdiction, 'false');

        $transactions_xml->transmode_code = $provider;
        $transactions_xml->transmode_comment = $provider_title;

        $transactions_xml->amount_local = $jurisdiction->getTransactionAmount($deposit);

        $this->addDepositTransactionToXml($user, $transactions_xml, $deposit, $all_request_data, $jurisdiction, $brand);

        $this->SXMLAppendChild($xml, $transactions_xml);
    }

    /**
     * run a regular expresion and scheme check on transaction type to see if it was a
     * visa or master card payment
     *
     * @param $transaction
     * @param $card_type
     * @param $type
     */
    public function visaOrMasterDeposit($transaction, $card_type, &$type)
    {
        if ($this->isVisa($card_type, $transaction->scheme, $transaction->card_hash ?? null)) {
            $type = "visa";
        }

        if (preg_match(
            $card_type['mc'],
            $transaction->card_hash
        ) || preg_match(
            $card_type['mc2'],
            $transaction->scheme,
            $matches2
        )) {
            $type = "mc";
        }
    }

    /**
     * run a regular expresion and scheme check on transaction type to see if it was a
     * visa or master card payment
     *
     * @param $transaction
     * @param $card_type
     * @param $type
     */
    public function visaOrMasterWithdrawal($transaction, $card_type, &$type)
    {
        if ($this->isVisa($card_type, $transaction->scheme, $transaction->card_hash ?? null)) {
            $type = "visa";
        }
        if (preg_match(
            $card_type['mc'],
            $transaction->scheme
        ) || preg_match(
            $card_type['mc2'],
            $transaction->scheme,
            $matches2
        )) {
            $type = "mc";
        }
    }

    /**
     * Check if its a visa card by the transaction scheme
     * @param $card_type
     * @param $scheme
     * @return bool
     */
    public function isVisa($card_type, $scheme, $card_hash)
    {
        return preg_match(
                $card_type['visa'],
                $scheme
            ) || preg_match(
                $card_type['visa2']
                , $scheme, $matches2
            ) || $scheme === 'VISA' ||
            preg_match(
                $card_type['visa'],
                $card_hash
            );
    }

    /**
     * If the provider is empty we want to check if its a Master card or Visa
     * and we update the provider data if so
     *
     * @param $provide
     * @param $transaction
     */
    public function isEmptyProvider(&$provide, $transaction)
    {
        if (!empty($provide)) {
            return;
        }

        $provide = $this->checkPaymentMethod($provide, $this->getPaymentMethod($transaction));

        $card_type = self::CARD_TYPES;
        $this->visaOrMasterDeposit($transaction, $card_type, $type);

        $provide = empty($type) ? array_merge($provide, ['display_name' => $transaction->display_name]) : phive('CasinoCashier')->getPspSettingDeprecated($type);
    }

    /**
     * We check if we have the paypal email user to make the transaction
     *
     * @param $transaction
     *
     * @return bool
     */
    public function hasPaypalEmail($transaction)
    {
        $type = (isset($transaction->dep_type) || isset($transaction->supplier)) ? 'deposit' : 'withdraw';

        $method = $this->getPaymentMethod($type, $transaction);

        return $method == 'paypal' && $this->getMtsAccount('paypal');
    }

    /**
     * Assign the paypal email to the account
     */
    public function assignPayPalEmail()
    {
        return $this->getMtsAccount('paypal')['ext_id'];
    }

    /**
     * Get payment method based on the type
     *
     * @param $transaction_type
     * @param $transaction
     * @return mixed
     */
    public function getPaymentMethod($transaction)
    {
        if (isset($transaction->dep_type)) {
            return $transaction->dep_type ? $transaction->dep_type : $transaction->supplier;
        }
        return $transaction->payment_method;
    }

    /**
     * Get transaction type
     *
     * @param $transaction
     * @return string
     */
    public function getTransactionType($transaction)
    {
        return isset($transaction->dep_type) ? 'deposit' : 'withdrawals';
    }

    /**
     * @param $transaction
     * @return mixed
     */
    public function getProvideByScheme($transaction)
    {
        if (!empty($transaction->dep_type) || !empty($transaction->supplier)) {
            $provide = phive('CasinoCashier')->getPspSettingDeprecated($transaction->dep_type ?? $transaction->supplier);
        }

        if ($transaction->dep_type === TransactionType::SWISH) {
            return $provide;
        }

        $scheme_provide = phive('CasinoCashier')->getPspSettingDeprecated($transaction->scheme);
        return ($transaction->scheme && !empty($scheme_provide)) ? $scheme_provide : $provide;
    }

    /**
     * Add a Deposit transaction and all its nodes to the XML to be exported
     *
     * @param $user
     * @param $transactions_xml
     * @param $transaction
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    public function addDepositTransactionToXml(
        $user,
        $transactions_xml,
        $transaction,
        $all_request_data,
        $jurisdiction,
        $brand
    ) {
        $transactions_from_client = simplexml_load_file($this->getResourcesFolder() . "from_client.xml");

        $provide = $this->getProvideByScheme($transaction);

        $type = '';

        $this->isEmptyProvider($provide, $transaction);

        $non_bank = 1;
        $personal_account_type = $jurisdiction->getTypes('unknown');

        $funds_code = '';
        $funds_code_company = '';
        $funds_comment = null;

        $method = $this->getPaymentMethod($transaction);

        $jurisdiction->generateTransactionCodes($method, $user, $provide, $transaction, $type, $funds_code, $funds_code_company, $funds_comment, $personal_account_type, $non_bank);

        $transactions_from_client->from_funds_code = $funds_code;

        if (!$this->addSwishAccountNode($jurisdiction, $transactions_from_client, 'from_funds_comment', $funds_comment)) {
            $this->addNodesWithValues(
              'from_funds_comment',
              str_replace(' ', '', $transaction->card_hash),
              $transactions_from_client
            );
        }
        $this->addExtraCurrencyData($transaction, $jurisdiction, $transactions_from_client, 'from_foreign_currency');

        if ($jurisdiction->isNodeActive('t_conductor')) {
            $this->addPerson($transactions_from_client->t_conductor, $all_request_data, $user, $jurisdiction);
        }

        $this->addNodesWithValues('from_account', '', $transactions_from_client);


        $this->isEmptyProvider($provide, $transaction);

        $transactions_from_client->from_account->institution_name = (!empty($provide['display_name'])) ? $provide['display_name'] : $jurisdiction->getIsMissingString();
        if ($provide['type'] == 'bank' || $provide['type'] == 'ewallet' ||  $provide['type'] == 'mobile') {
            $transactions_from_client->from_account->institution_name = $provide['display_name'];
        }

        if ($provide['type'] == 'ccard') {
            $transactions_from_client->from_account->swift = $jurisdiction->getIsMissingString();
        } elseif ($provide['type'] == 'bank' || $provide['type'] == 'ewallet') {
            $transactions_from_client->from_account->swift = ($provide['bics']['SE'])? $provide['bics']['SE'] : $jurisdiction->getIsMissingString();
        } else {
            $transactions_from_client->from_account->swift = $jurisdiction->getIsMissingString();
        }

        $transactions_from_client->from_account->non_bank_institution = $non_bank;
        $account_fallback = $jurisdiction->getFallbackAccount(
            $transaction,
            $provide['type'],
            $transaction->dep_type ?? $transaction->supplier
        );

        if (in_array($provide['type'], ['ccard', 'pcard'])) {
            $transactions_from_client->from_account->account = !empty($transaction->card_hash) ? str_replace(' ', '', ($transaction->card_hash)) : $account_fallback;
        } elseif (in_array($provide['type'], ['bank', 'ewallet']) || $jurisdiction->isMobileAndBank($provide['type'], $transaction->dep_type)) {
            if (!$this->addSwishAccountNode($jurisdiction, $transactions_from_client->from_account, 'account', $funds_comment)) {
                $account = (!empty($transaction->card_hash)) ? str_replace(' ', '', ($transaction->card_hash)) : $account_fallback;
                if ($this->hasPaypalEmail($transaction)) {
                    $account = $this->assignPayPalEmail();
                }
                $transactions_from_client->from_account->account = $account;
            }
        } else {
            $transactions_from_client->from_account->account = $account_fallback;
            if ($provide['type'] == 'mobile') {
                $this->addSwishAccountNode($jurisdiction, $transactions_from_client->from_account, 'account', $funds_comment);
            }
        }

        $transactions_from_client->from_account->currency_code = $transaction->currency;

        if ($provide['type'] == 'bank') {
            $this->addNodesWithValues('iban', $transaction->card_hash, $transactions_from_client->from_account);
        }

        $transactions_from_client->from_account->personal_account_type = $personal_account_type;

        if ($jurisdiction->isNodeActive('from_signatory')) {
            $this->addJurisdictionBasedNode($transactions_from_client->from_account->signatory, $transaction, 'is_primary', $jurisdiction, true);
            $this->addPerson($transactions_from_client->from_account->signatory->t_person, $all_request_data, $user, $jurisdiction);
            $transactions_from_client->from_account->signatory->role = $jurisdiction->getTypes('role');
        }

        if ($provide['type'] == 'ccard') {
            $transactions_from_client->from_account->beneficiary = $provide['display_name'];
        }

        $transactions_from_client->from_country = $user->country;

        $this->SXMLAppendChild($transactions_xml, $transactions_from_client);

        $transactions_to_entity = simplexml_load_file($this->getResourcesFolder() . "to_entity.xml");

        $transactions_to_entity->to_funds_code = $funds_code_company;

        if(!$this->addSwishAccountNode($jurisdiction, $transactions_to_entity, 'to_funds_comment', $funds_comment, false)) {
            $this->addNodesWithValues(
              'to_funds_comment',
              str_replace(' ', '', $funds_comment),
              $transactions_to_entity
            );
        }

        $this->addNodesWithValues('to_account', '', $transactions_to_entity);

        $this->addVideoslotsAccount($transactions_to_entity, 'to_account', $all_request_data, $user, $jurisdiction, $brand);

        $transactions_to_entity->to_country = 'MT';

        $this->SXMLAppendChild($transactions_xml, $transactions_to_entity);
    }

    /**
     * Add a given node to the provided xml if there is a swish number value
     * if its the clients swish use the clients number as value other wise use
     * tho companies number for the report
     *
     * @param $jurisdiction
     * @param $transactions_xml
     * @param $node
     * @param null $funds_comment
     * @param bool $is_client
     *
     * @return bool
     */
    public function addSwishAccountNode($jurisdiction, $transactions_xml, $node, $funds_comment = null, $is_client = true)
    {
        if((isset($funds_comment) && is_array($funds_comment))  && $jurisdiction->isNodeActive('swish_number')) {
            $transactions_xml->$node = ($is_client) ? $funds_comment['swish_client'] : $funds_comment['swish_reporting_entity'];
            return true;
        }

        return false;
    }

    /**
     * Add the account node for videoslots account
     *
     * @param $transaction
     * @param $subnode
     * @param $all_request_data
     * @param $user
     * @param $jurisdiction
     * @param $brand
     */
    public function addVideoslotsAccount($transaction, $subnode, $all_request_data, $user, $jurisdiction, $brand)
    {
        $transaction->$subnode->institution_name = $brand->institution_name;
        $transaction->$subnode->institution_code = $brand->institution_code;
        $transaction->$subnode->non_bank_institution = 1;
        $transaction->$subnode->account = $user->id;
        $transaction->$subnode->currency_code = $user->currency;
        $transaction->$subnode->account_name = $user->username;
        $transaction->$subnode->client_number = $user->id;
        $transaction->$subnode->personal_account_type = $jurisdiction->getTypes('gaming');

        $this->addJurisdictionBasedNode($transaction->$subnode->signatory, $transaction, 'is_primary', $jurisdiction, true);

        $this->addPerson($transaction->$subnode->signatory->t_person, $all_request_data, $user, $jurisdiction);

        $transaction->$subnode->signatory->role = $jurisdiction->getTypes('role');

        $transaction->$subnode->opened = $this->formatDateForAml($user->register_date);

        $transaction->$subnode->balance = $user->cash_balance;
        $transaction->$subnode->date_balance = $this->formatDateForAml(Carbon::now());
        $transaction->$subnode->status_code = $jurisdiction->getTypes('status_code');
    }

    /**
     * Add the locations node for the report
     *
     * @param $transaction
     * @param $brand
     * @param $jurisdiction
     */
    public function addLocation($transaction, $brand, $jurisdiction)
    {
        $transaction->location->address_type = $brand->address_type[$jurisdiction];
        $transaction->location->address = $brand->address;
        $transaction->location->city = $brand->city;
        $transaction->location->zip = $brand->zip;
        $transaction->location->country_code = $brand->country_code;
    }

    /**
     *  Append a node and all its children to a given node
     *
     * @param SimpleXMLElement $to
     * @param SimpleXMLElement $from
     */
    public function SXMLAppendChild(
        SimpleXMLElement $to,
        SimpleXMLElement $from
    ) {
        $toDom = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    /**
     * Add the Person node on the given xml
     *
     * @param $transaction
     * @param $all_request_data
     * @param $user
     * @param $jurisdiction
     */
    public function addPerson(
        &$transaction,
        $all_request_data,
        $user,
        $jurisdiction
    ) {
        $date_of_birth = isset($all_request_data['dob']) ? $all_request_data['dob'] : $user['dob'];
        $ssn = isset($all_request_data['ssn']) ? $all_request_data['ssn'] : $user['ssn'];
        $foreign_personal_identify_country = isset($all_request_data['foreign_personal_identify_country']) ? $all_request_data['foreign_personal_identify_country'] : $user['foreign_personal_identify_country'];

        $transaction->gender = (isset($all_request_data['sex'][0])) ? $all_request_data['sex'][0] : $user['sex'][0];
        $transaction->first_name = (isset($all_request_data['firstname'])) ? $all_request_data['firstname'] : $user['firstname'];
        $transaction->middle_name = (isset($all_request_data['middle_name'])) ? $all_request_data['middle_name'] : $user['middle_name'];
        $transaction->prefix = (isset($all_request_data['prefix'])) ? $all_request_data['prefix'] : $user['prefix'];
        $transaction->last_name = (isset($all_request_data['lastname'])) ? $all_request_data['lastname'] : $user['lastname'];
        $this->addNodesWithValues(
            'birthdate',
            $this->formatDateForAml($date_of_birth),
            $transaction
        );
        $transaction->birth_place = (isset($all_request_data['birth_place'])) ? $all_request_data['birth_place'] : $user['birth_place'];
        $transaction->mothers_name = (isset($all_request_data['country_birth'])) ? $all_request_data['country_birth'] : $user['country_birth'];
        $this->addNodesWithValues(
            'ssn',
            $ssn,
            $transaction
        );

        $transaction->passport_number = (isset($all_request_data['foreign_personal_identify'])) ? $all_request_data['foreign_personal_identify'] : $user['foreign_personal_identify'];

        $this->addNodesWithValues(
            'passport_country',
            $foreign_personal_identify_country,
            $transaction
        );

        $this->addJurisdictionBasedNode($transaction, $transaction, 'id_number', $jurisdiction, $all_request_data['number']);

        $this->addNodesWithValues(
            'nationality1',
            $all_request_data['citizenship1'],
            $transaction
        );

        $this->addNodesWithValues(
            'nationality2',
            $all_request_data['citizenship2'],
            $transaction
        );

        $this->addNodesWithValues(
            'nationality3',
            $all_request_data['citizenship3'],
            $transaction
        );

        $this->addNodesWithValues(
            'residence',
            $all_request_data['residence'],
            $transaction
        );

        $transaction->phones->phone->tph_contact_type = (isset($all_request_data['tph_contact_type'])) ? $all_request_data['tph_contact_type'] : null;
        $transaction->phones->phone->tph_communication_type = (isset($all_request_data['tph_communication_type'])) ? $all_request_data['tph_communication_type'] : null;

        $this->addNodesWithValues(
            'tph_country_prefix',
            $all_request_data['tph_country_prefix'],
            $transaction->phones->phone
        );

        $transaction->phones->phone->tph_number = (isset($all_request_data['tph_number'])) ? $all_request_data['tph_number'] : null;
        $transaction->phones->phone->comments = (isset($all_request_data['comments'])) ? $all_request_data['comments'] : null;

        $transaction->addresses->address->address_type = (isset($all_request_data['address_type'])) ? $all_request_data['address_type'] : null;
        $transaction->addresses->address->address = (isset($all_request_data['address'])) ? $all_request_data['address'] : null;
        $transaction->addresses->address->town = (isset($all_request_data['town'])) ? $all_request_data['town'] : $user['town'];
        $transaction->addresses->address->city = (isset($all_request_data['city'])) ? $all_request_data['city'] : $user['city'];
        $transaction->addresses->address->zip = (isset($all_request_data['zip'])) ? $all_request_data['zip'] : $user['zipcode'];
        $transaction->addresses->address->country_code = (isset($all_request_data['country_code'])) ? $user['country'] : null;

        $this->addNodesWithValues(
            'state',
            $all_request_data['state'],
            $transaction->addresses->address
        );

        $this->addNodesWithValues(
            'email',
            $all_request_data['email'],
            $transaction
        );

        $this->addNodesWithValues(
            'occupation',
            $all_request_data['occupation'],
            $transaction
        );

        $transaction->identification->type = (isset($all_request_data['type'])) ? $all_request_data['type'] : 'ANEID';
        if ($user->nid) {
            $transaction->identification->number = $user->nid;
        } else {
            $transaction->identification->number = (isset($all_request_data['number'])) ? $all_request_data['number'] : null;
        }

        $this->addNodesWithValues(
            'issue_date',
            $this->formatDateForAml(isset($all_request_data['issue_date']) ? $all_request_data['issue_date'] : null),
            $transaction->identification
        );

        $this->addNodesWithValues(
            'expiry_date',
            $this->formatDateForAml(isset($all_request_data['expiry_date']) ? $all_request_data['expiry_date'] : null),
            $transaction->identification
        );

        $transaction->identification->issued_by = (isset($all_request_data['issued_by'])) ? $all_request_data['issued_by'] : null;

        if ($jurisdiction->isNodeActive('issue_country')) {
            $this->addJurisdictionBasedNode($transaction->identification, $transaction, 'issue_country', $jurisdiction, $all_request_data['issue_country']);
        } else {
            $this->addNodesWithValues(
                'issue_country',
                $all_request_data['issue_country'],
                $transaction->identification
            );
        }

        $transaction->identification->comments = (isset($all_request_data['comments'])) ? $all_request_data['comments'] : null;
    }

    /**
     * Get the data needed to generate a transaction description
     *
     * @param $transaction
     * @param $provider_title
     * @param $all_request_data
     * @return mixed|string|string[]
     */
    public function generateTransactionDescription($transaction, $provider_title, $all_request_data)
    {
        $type = isset($transaction->dep_type) ? 'deposit' : 'withdraw';

        if ($type == 'deposit') {
            $method = $transaction->dep_type;
            if (!$method) {
                $method = $transaction->supplier;
            }
            $description = $all_request_data['deposit_description'];
        } else {
            $method = $transaction->payment_method;
            $description = $all_request_data['withdraw_description'];
        }

        $provide = phive('CasinoCashier')->getPspSettingDeprecated($method);
        if (empty($provide)) {
            $card_type = self::CARD_TYPES;

            if ($type == 'deposit') {
                $type = '';
                $this->visaOrMasterDeposit($transaction, $card_type, $type);
            } else {
                $this->visaOrMasterWithdrawal($transaction, $card_type, $type);
            }
            if (isset($type)) {
                $provide = phive('CasinoCashier')->getPspSettingDeprecated($type);
            }
        }

        if ($provide['type'] == 'ccard') {
            $account = str_replace(' ', '', $transaction->card_hash);
        } elseif ($provide['type'] == 'bank' || $provide['type'] == 'ewallet') {
                $account = (!empty($transaction->card_hash)) ? $transaction->card_hash : str_replace(' ', '', $transaction->id);
        } else {
            $account = null;
        }

        return str_replace(
            array("__PROVIDER_NAME__","__ACCOUNT_NUMBER__", "__PAYMENT_TYPE__"),
            array(isset($provider_title) ? $provider_title : null, isset($account) ? $account : null, isset($provide['type']) ? $provide['type'] : null),
            $description
        );
    }

    /**
     * Create the transaction node which will contain a transaction
     *
     * @param $transaction
     * @param $transactions_xml
     */
    public function addTransactionNode(&$transaction, &$transactions_xml, $jurisdiction, $provider_title, $all_request_data)
    {
        $suffix = self::SUFFIX_TRANSACTION_TYPE[$this->getTransactionType($transaction)];
        $transactions_xml = simplexml_load_file($this->getResourcesFolder() . "transactions.xml");
        $transactions_xml->transactionnumber = $suffix.$transaction->id;

        if ($jurisdiction->isNodeActive('transaction_description')) {
            $transactions_xml->transaction_description = $this->generateTransactionDescription($transaction, $provider_title, $all_request_data);
        }

        if (!empty($transaction->ext_id)) {
            $this->addJurisdictionBasedNode($transactions_xml, $transaction, 'internal_ref_number', $jurisdiction);
        }
        $transactions_xml->date_transaction = $this->formatDateForAml($transaction->timestamp);

        $this->addJurisdictionBasedNode($transactions_xml, $transaction, 'teller', $jurisdiction);
    }

    /**
     * Some nodes on the XML have different types of value they need for different jurisdiction
     * so this function will add the node if its active and it will get the column to use for the data
     *
     * If $value is set than it will use that as value to the node
     *
     * @param $transactions_xml
     * @param $transaction
     * @param $node
     * @param $jurisdiction
     * @param null $value
     */
    public function addJurisdictionBasedNode(&$transactions_xml, $transaction, $node, $jurisdiction, $value = null)
    {
        if ($jurisdiction->isNodeActive($node)) {
            if ($value) {
                $transactions_xml->$node = $value;
            } else {
                $value_source = $jurisdiction->nodeSettings($node);
                $transactions_xml->$node = $transaction->$value_source;
            }
        }
    }

    /**
     * Add the withdrawals to the xml
     *
     * @param $withdrawal
     * @param $user
     * @param $xml
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    public function addWithdrawals(
        $withdrawal,
        $user,
        $xml,
        $all_request_data,
        $jurisdiction,
        $brand
    ) {
        $failed_transaction = $withdrawal->status != 'approved';

        $transactions_xml = '';

        $this->getProviderTitleAndType($withdrawal, $provider, $provider_title, 'withdrawal', $jurisdiction);

        $this->addTransactionNode($withdrawal, $transactions_xml, $jurisdiction, $provider_title, $all_request_data);

        $this->addJurisdictionBasedNode($transactions_xml, $withdrawal, 'late_deposit', $jurisdiction, var_export($failed_transaction, true));

        $transactions_xml->transmode_code = $provider;
        $transactions_xml->transmode_comment = $provider_title;

        $transactions_xml->amount_local = $jurisdiction->getTransactionAmount($withdrawal);

        $this->addWithdrawalTransactionToXml($user, $transactions_xml, $withdrawal, $all_request_data, $jurisdiction, $brand);

        if ($failed_transaction) {
            $this->addJurisdictionBasedNode($transactions_xml, $withdrawal, 'comments', $jurisdiction, static::COMMENT_FAILED_TRANSACTION);
        }

        $this->SXMLAppendChild($xml, $transactions_xml);
    }

    /**
     *  Add a Withdrawal transaction and all its nodes to the XML to be exported
     *
     * @param $user
     * @param $transactions_xml
     * @param $transaction
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    public function addWithdrawalTransactionToXml(
        $user,
        $transactions_xml,
        $transaction,
        $all_request_data,
        $jurisdiction,
        $brand
    ) {
        $transactions_from_entity = simplexml_load_file($this->getResourcesFolder() . "from_entity.xml");

        $provide = phive('CasinoCashier')->getPspSettingDeprecated($transaction->payment_method);

        if (empty($provide)) {
            $card_type = self::CARD_TYPES;

            $this->visaOrMasterWithdrawal($transaction, $card_type, $type);

            if (isset($type)) {
                $provide = phive('CasinoCashier')->getPspSettingDeprecated($type);
            }

            if (empty($provide)) {
                $this->isEmptyProvider($provide, $transaction);
            }

        }

        $non_bank = 1;
        $personal_account_type = $jurisdiction->getTypes('unknown');
        $funds_code = '';
        $funds_code_company = '';
        $funds_comment = '';

        $method = $this->getPaymentMethod($transaction);


        $jurisdiction->generateTransactionCodes($method, $user, $provide, $transaction, $type, $funds_code, $funds_code_company, $funds_comment, $personal_account_type, $non_bank);

        $transactions_from_entity->from_funds_code = $funds_code_company;

        $personal_account_by_method = $jurisdiction->getPersonalAccountType($transaction->payment_method);
        if ($personal_account_type === $jurisdiction->getPersonalAccountType('unknown') && !empty($personal_account_by_method)) {
            $personal_account_type = $personal_account_by_method;
        }

        if(!$this->addSwishAccountNode($jurisdiction, $transactions_from_entity, 'from_funds_comment', $funds_comment, false)) {
            $transactions_from_entity->from_funds_comment = $funds_comment;
        }

        $this->addNodesWithValues(
            'from_account',
            '',
            $transactions_from_entity
        );

        $this->addVideoslotsAccount($transactions_from_entity, 'from_account', $all_request_data, $user, $jurisdiction, $brand);

        $transactions_from_entity->from_country = 'MT';

        $this->SXMLAppendChild($transactions_xml, $transactions_from_entity);
        $transactions_to_account = simplexml_load_file($this->getResourcesFolder() . "to_client.xml");

        $transactions_to_account->to_funds_code = $funds_code;

        if(!$this->addSwishAccountNode($jurisdiction, $transactions_to_account, 'to_funds_comment', $funds_comment)) {
            $this->addNodesWithValues('to_funds_comment', str_replace(' ', '', $transaction->scheme), $transactions_to_account);
        }

        $this->addExtraCurrencyData($transaction, $jurisdiction, $transactions_to_account, 'to_foreign_currency');

        $this->addNodesWithValues(
            'to_account',
            '',
            $transactions_to_account
        );

        if ($transaction->scheme) {
            $provide = phive('CasinoCashier')->getPspSettingDeprecated($transaction->scheme);
        }

        $this->isEmptyProvider($provide, $transaction);

        $transactions_to_account->to_account->institution_name = (!empty($provide['display_name'])) ? $provide['display_name'] : $jurisdiction->getIsMissingString();

        if ($provide['type'] === 'bank' || $provide['type'] === 'ewallet' ||  $provide['type'] === 'mobile') {
            $institution_name = (!$transaction->bank_name) ? $provide['display_name'] : $transaction->bank_name;
            $transactions_to_account->to_account->institution_name = $institution_name;
        }

        if ($provide['type'] == 'ccard') {
            $transactions_to_account->to_account->swift = $jurisdiction->getIsMissingString();
        } elseif ($provide['type'] == 'bank' || $provide['type'] == 'ewallet') {
            if ($provide[1]['bics']['SE']) {
                $provide = phive('CasinoCashier')->getPspSettingFromDisplayName($transaction->bank_name);
                $transactions_to_account->to_account->swift = $provide[1]['bics']['SE'];
            } else {
                $transactions_to_account->to_account->swift = $jurisdiction->getIsMissingString();
            }
        } else {
            $transactions_to_account->to_account->swift = $jurisdiction->getIsMissingString();
        }

        $transactions_to_account->to_account->non_bank_institution = $non_bank;

        $account_fallback = $jurisdiction->getFallbackAccount(
            $transaction,
            $provide['type'],
            $transaction->payment_method
        );

        if ($provide['type'] == 'ccard') {
            $transactions_to_account->to_account->account = !empty($transaction->scheme) ? str_replace(' ', '', ($transaction->scheme)) : $account_fallback;
        } elseif (in_array($provide['type'], ['bank', 'ewallet']) || $jurisdiction->isMobileAndBank($provide['type'], $transaction->payment_method)) {
            $account = !empty($transaction->bank_account_number) ? str_replace(' ', '', ($transaction->bank_account_number)) : $account_fallback;
            if ($this->hasPaypalEmail($transaction)) {
                $account = $this->assignPayPalEmail();
            }
            $transactions_to_account->to_account->account = $account;
        } else {
            if (!$this->addSwishAccountNode($jurisdiction, $transactions_to_account->to_account, 'account', $funds_comment)) {
                $transactions_to_account->to_account->account = !empty($transaction->scheme) ? str_replace(' ', '', ($transaction->scheme)) : $account_fallback;
            }
        }

        $transactions_to_account->to_account->currency_code = $transaction->currency;

        if ($provide['type'] == 'bank') {
            $this->addNodesWithValues('iban', $transaction->iban, $transactions_to_account->to_account);
        }

        $transactions_to_account->to_account->personal_account_type = $personal_account_type;

        if ($jurisdiction->isNodeActive('to_signatory')) {
            $this->addJurisdictionBasedNode($transactions_to_account->to_account->signatory, $transaction, 'is_primary', $jurisdiction, true);
            $this->addPerson($transactions_to_account->to_account->signatory->t_person, $all_request_data, $user, $jurisdiction);
            $transactions_to_account->to_account->signatory->role = $jurisdiction->getTypes('role');
        }

        $transactions_to_account->to_account->status_code = $jurisdiction->getTypes('status_code');

        if ($provide['type'] == 'ccard') {
            $transactions_to_account->to_account->beneficiary = $provide['display_name'];
        }

        $transactions_to_account->to_country = $user->country;
        $this->SXMLAppendChild($transactions_xml, $transactions_to_account);
    }

    /**
     * Add reporting indicators
     *
     * @param $xml
     * @param $indicators
     */
    public function addReportIndicators($xml, $indicators)
    {
        $xml->report_indicators = '';
        $indicators = explode("\n", $indicators);

        if (count($indicators) == 1) {
            $this->addNodesWithValues(
                'indicator',
                str_replace("\t\n\r\0\x0B", '', $indicators[0]),
                $xml->report_indicators
            );
        } else {
            foreach ($indicators as $indicator) {
                $this->addNodesWithValues(
                    'indicator',
                    str_replace("\r", '', $indicator),
                    $xml->report_indicators
                );
            }
        }
    }

    /**
     * Add the deposits to the xml
     *
     * @param $deposit
     * @param $user
     * @param $xml
     * @param $all_request_data
     * @param $jurisdiction
     * @param $brand
     */
    public function addFailedDeposits(
        $deposit,
        $user,
        $xml,
        $all_request_data,
        $jurisdiction,
        $brand
    ) {
        $suffix = self::SUFFIX_TRANSACTION_TYPE['failed_deposit'];
        $transactions_xml = simplexml_load_file($this->getResourcesFolder() . "transactions.xml");
        $transactions_xml->transactionnumber = $suffix.$deposit->id;
        $this->addJurisdictionBasedNode($transactions_xml, $deposit, 'internal_ref_number', $jurisdiction);

        $this->getProviderTitleAndType($deposit, $provider, $provider_title, 'deposit', $jurisdiction);

        if ($jurisdiction->isNodeActive('transaction_description')) {
            $transactions_xml->transaction_description = $this->generateTransactionDescription($deposit, $provider_title, $all_request_data);
        }

        $transactions_xml->date_transaction = $this->formatDateForAml($deposit->created_at);
        $this->addJurisdictionBasedNode($transactions_xml, $deposit, 'teller', $jurisdiction);

        $this->getProviderTitleAndType($deposit, $provider, $provider_title, 'deposit', $jurisdiction);

        $this->addJurisdictionBasedNode($transactions_xml, $deposit, 'late_deposit', $jurisdiction, 'true');

        $transactions_xml->transmode_code = $provider;
        $transactions_xml->transmode_comment = $provider_title;

        $transactions_xml->amount_local = $jurisdiction->getTransactionAmount($deposit);

        $this->addDepositTransactionToXml($user, $transactions_xml, $deposit, $all_request_data, $jurisdiction, $brand);

        $this->addJurisdictionBasedNode($transactions_xml, $deposit, 'comments', $jurisdiction, static::COMMENT_FAILED_TRANSACTION);

        $this->SXMLAppendChild($xml, $transactions_xml);
    }

    public function checkPaymentMethod($provide, $payment_method_or_dep_type)
    {
        if (!empty($provide) || !array_key_exists($payment_method_or_dep_type, self::PAYMENT_METHOD_TYPE)) {
            return $provide;
        }

        $provide['type'] = self::PAYMENT_METHOD_TYPE[$payment_method_or_dep_type];
        return $provide;
    }

    /**
     * @param $transactions_xml
     * @param $transaction
     * @param $jurisdiction
     * @return null
     */
    public function addExtraCurrencyData($transaction, $jurisdiction, $transactions_client, $field_name)
    {
        $currency = $transaction->currency;
        if ($currency === $jurisdiction->getDefaultCurrency($currency)) {
            return null;
        }

        $this->addNodesWithValues($field_name, '', $transactions_client);
        $transactions_client->$field_name->foreign_currency_code = $currency;
        $transactions_client->$field_name->foreign_amount = nfCents($transaction->amount, true, '.', '');
        $transactions_client->$field_name->foreign_exchange_rate = $transaction->multiplier ?? 1;
    }

    /**
     * @param $query_builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function leftJoinWithFxRates($query_builder)
    {
        $query_builder->leftJoin('fx_rates as fx', function ($join) {
            $join->on('fx.code', '=', 'currency')->whereRaw('fx.day_date = DATE(timestamp)');
        });
        return $query_builder;
    }
}
