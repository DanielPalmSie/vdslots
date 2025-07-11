<?php

use Carbon\Carbon;
use ClosedLoop\ClosedLoopFacade;
use GuzzleHttp\Exception\ClientException;
use Laraphive\Support\Encryption;
use ClosedLoop\ClosedLoopFactory;
use ClosedLoop\ClosedLoopHelper;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlagRegistry;
use Videoslots\FraudDetection\FraudFlags\LowWagerFlag;
use Videoslots\FraudDetection\FraudFlags\WithdrawalLimitFlag;
use Videoslots\FraudDetection\FraudFlags\WithdrawLast24HoursLimitFlag;
use Videoslots\FraudDetection\RevokeEvent;
use Videoslots\HistoryMessages\BonusHistoryMessage;
use Videoslots\HistoryMessages\CashTransactionHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\InterventionHistoryMessage;
use Videoslots\HistoryMessages\TournamentCashTransactionHistoryMessage;
use Videoslots\Mts\MtsClient;
use Videoslots\Mts\Request\TrustlyPayoutRequest;
use Videoslots\Mts\Request\TrustlySelectAccountRequest;
use Videoslots\MtsSdkPhp\Endpoints\Payouts\PayoutResource;
use Videoslots\MtsSdkPhp\Endpoints\Payouts\SwishPayout;
use Videoslots\MtsSdkPhp\MtsClientFactory;
use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once __DIR__ . '/Cashier.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Mts.php';
require_once __DIR__ . '/../../traits/ObfuscateTrait.php';
require_once 'Fraud.php';

/**
 * An extension of the base Cashier class with logic that deals with running a casino cashier.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_first_deposits For keeping track of if a user has deposited or not.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bank_countries For misc. settings per country.
 * @link https://wiki.videoslots.com/index.php?title=Videoslots_Cashier For general info around the config.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_queued_transactions
 * @link https://wiki.videoslots.com/index.php?title=DB_table_cash_transactions The wiki docs for the cash_transactions table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_deposits The wiki docs for the deposits table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_pending_withdrawals The wiki docs for the pending_withdrawals table.
 */
class CasinoCashier extends Cashier
{
    /**
     * A trait for obfuscating array values, which can be used to mask sensitive log data.
     */
    use \ObfuscateTrait;

    /**
     * Represent the non-stuck value for the transaction
     * @var int
     */
    const NON_STUCK = 0;

    /**
     * Represent the stuck normal value for the transaction
     * @var int
     */
    const STUCK_NORMAL = 1;
    /**
     * Represent the stuck normal value for the transaction
     * @var int
     */
    const STUCK_OVER = 2;
    /**
     * Represent the stuck unknown value for the transaction
     * @var int
     */
    const STUCK_UNKNOWN = 9;
    const SCHEDULE_QUEUED_TRANSACTIONS = "06:NZ|07:GB|08:SE|09:DK|10:FI|11:SI|12:NO|13:IT|14:ES|15:NA|16:CA";
    const SCHEDULE_CLASH_OF_SPINS = "01:AU|08:RU|09:FI|10:DK|11:SE|12:GB|13:NO|14:NA|15:NL|16:NZ|17:SI|18:IE|19:CA|20:MT|21:IT";

    const SUPPLIER_CONNECTION_ERROR = 100;
    const REJECTED_BY_SUPPLIER = 300;
    const INTERNAL_ERROR_CODE_ACCOUNT_NOT_FOUND = 401;

    /**
     * Used to represent an unredeemed award in reporting
     */
    public const CURRENCY_CASH_REWARD = 'CashReward';

    /**
     * @see Fraud
     * @var Fraud Fraud object container.
     */
    public $fraud;

    /**
     * @var array An array to cache documents fetched from the DMAPI, there is not point in querying repetedly.
     */
    public $documents = [];

    /**
     * @var int Cache value for the maximum repeat / oneclick amount that can be deposited, any fetched repeat deposit that is
     * above this value will not display.
     */
    public $max_quick_amount;

    /**
     * @var array Cache to avoid double queries in the same execution.
     */
    public $cache = [];

    public const USER_SETTINGS_ACCOUNT_KEY_PER_PSP = [
        'skrill' => 'mb_email',
        'neteller' => 'net_account',
        'paypal' => 'paypal_payer_id',
        'flykk' => 'flykk_uid'
    ];

    private const PSP_ACCOUNT_WITHDRAWAL_COLUMN_MAPPING = [
        'skrill' => 'mb_email',
        'neteller' => 'net_account'
    ];

    /**
     * This is how we're able to do phive('Cashier') to get an instance of this class.
     *
     * @return array An array with Cashier in it.
     */
    function phAliases()
    {
        return array('Cashier');
    }

    /**
     * The constructor doesn't do much more than instantiating SQL and Fraud, the reason for storing an instance of SQL is that
     * it looks better with $this->db than phive('SQL') in hundreds of places.
     *
     * @return null
     */
    function __construct()
    {
        $this->db = phive('SQL');
        $this->fraud = new Fraud();
    }

    /**
     * Some rare and unusal deposit flows require the deposit to be in a non-approved state initially.
     *
     * Once we get a notification / callback that the deposit has cleared we update its status to approved. Users who have
     * pending deposits will be limited in how much they can withdraw etc.
     *
     * @param array $deposit The deposit to approve.
     *
     * @return bool True of the deposit status was updated, false otherwise.
     */
    function approveDeposit($deposit)
    {
        if ($deposit['status'] == 'pending') {
            $this->updateDeposit($deposit, 'status', 'approved');
            return true;
        }
        return false;
    }

    public function retSuccess($msg = 'ok', $data = [])
    {
        return ['success' => true, 'msg' => $msg, 'data' => $data];
    }

    public function retFail($msg = 'error', $data = [])
    {
        return ['success' => false, 'msg' => $msg, 'data' => $data];
    }

    /**
     * Checks if a user is allowed to make a withdraw. If the user is verified,
     * and the document for the respective payment provider is verified,
     * then we allow the withdraw.
     * If not we check if the total deposits exceed the configured threshold.
     *
     * @param DBUser $user
     * @param string $psp
     * @param string $scheme                Important for Entercash, where we need the subsupplier to check the correct document.
     * @param string $bank_account_number   For future implications where we need to check the bank account number too
     * @param int $card_id                  The card id is needed to identify the correct credit card
     * @param string $card_hash             NOT RECOMMENDED: If we don't have the card_id, but only the card hash,
     *                                      we can use that for checking the card, but it can give wrong results
     *                                      when a user has with multiple cards with the same hash.
     * @param array $paymentData            This the data submitted from the GUI.
     *
     * @return array True in under success key if the user can withdraw, false otherwise.
     */
    public function canWithdraw(DBUser $user, string $psp = '', string $scheme = '', string $bank_account_number = '', int $card_id = 0, string $card_hash = '', array $paymentData = []): array
    {
        if ($user->isTemporal()) {
            return $this->retFail('temp.account');
        }

        if ($user->withdrawalBlocked()) {
            return $this->retFail('withdraw.blocked');
        }

        if ($psp === 'ccard') {
            // We have a credit card so we don't want any data passed in as bank account number to be respected.
            $bank_account_number = '';
        } else {
            // We have a non-card source so we reset any data passed in as card hash.
            $card_hash = '';
        }

        $withdraw_only_verified_user = licSetting('withdraw_only_verified_user', $user);
        $user_verified = $user->isVerified();

        if ($withdraw_only_verified_user && !$user_verified) {
            return $this->retFail('verify', ['reason' => 'User not verified.', 'context' => [
                'user_id' => $user->getId(),
                'psp' => $psp,
                'scheme' => $scheme,
                'withdraw_only_verified_user' => $withdraw_only_verified_user,
                'user_verified' => $user_verified,
            ]]);
        }

        $restriction = $user->getDocumentRestrictionType();
        if ($restriction) {
            return $this->retFail($restriction);
        }

        $source_of_funds_check_result = $this->hasForcedShowSourceOfFunds($user);
        if (!is_null($source_of_funds_check_result)) {
            return $this->retFail('source.of.funds', $source_of_funds_check_result);
        }

        if ($this->hasSourceOfIncomePending($user)) {
            return $this->retFail('source.of.funds.pending');
        }

        if (empty($psp)) {
            return $this->retSuccess();
        }

        $errors = $this->validateInpayProfileData($user, $psp);
        if (!empty($errors)) {
            return $this->retFail('wrong.fields', $errors);
        }

        $psp_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp);
        if ($this->bypassKyc($psp_config, $user)) {
            return $this->retSuccess();
        }

        $lic_do_kyc = licSetting('do_kyc', $user);

        // We are here because we're looking at a bank type option with kyc default off, but we have an License override that forces us to check the
        // status of bankpic, we don't want default behaviour because that will lock onto bankaccountpic which we want to exclude from non-display
        // related business logic (for now). /Henrik
        if ($psp_config['type'] == 'bank' && $psp_config['do_kyc'] === false && $lic_do_kyc) {
            $document_status = phive('Dmapi')->checkProviderStatus($user->getId(), 'bank', $scheme, $bank_account_number, $card_id, $card_hash);
        } elseif (in_array($psp, ['trustly', 'swish'])) {
            return $this->retSuccess();
            /*$document_status = phive('Dmapi')->checkDocumentStatusBySubtag($user->getId(), $psp, '', $bank_account_number);

            if ($document_status != 'approved') {
                $depCount = (int) $this->getDepositCount($user->getId(), $psp, "AND card_hash = '{$bank_account_number}' AND status = 'approved'");
                if (!$depCount) {
                    return $this->retFail(
                        'Selected Account Not Verified',
                        [
                            'reason' => 'Document not approved.',
                            'display_block_message' => false,
                            'context' => [
                                'user_id' => $user->getId(),
                                'psp' => $psp,
                                'account_number' => $bank_account_number,
                                'document_status' => $document_status,
                            ]
                        ]
                    );
                }
            }*/
        } else {
            $document_status = phive('Dmapi')->checkProviderStatus($user->getId(), $psp, $scheme, $bank_account_number, $card_id, $card_hash);
        }

        if ($document_status == 'deactivated') {
            return $this->retFail('deactivated.card');
        }

        if ($lic_do_kyc && $document_status != 'approved') {
            return $this->retFail('verify', ['reason' => 'Document not approved.', 'context' => [
                'user_id' => $user->getId(),
                'psp' => $psp,
                'scheme' => $scheme,
                'lic_do_kyc' => $lic_do_kyc,
                'document_status' => $document_status,
            ]]);
        }

        if ($this->thresholdReached($user) && $document_status != 'approved') {
            return $this->retFail(
                'verify',
                [
                    'reason' => 'Threshold reached but document not approved.',
                    'context' => [
                        'user_id' => $user->getId(),
                        'psp' => $psp,
                        'scheme' => $scheme,
                        'document_status' => $document_status,
                    ]
                ]
            );
        }

        return $this->retSuccess();
    }

    private function thresholdReached(DBUser $user): bool
    {
        $thresholds = phive('Config')->valAsArray('withdrawal-limits', 'total-deposits', ' ', ':');
        $threshold = $thresholds[$user->getCountry()] ?? 0;
        return $threshold && $this->getUserDepositSum($user->getId()) >= mc($threshold, $user);
    }

    /**
     * Validates the user profile based on the Inpay requirements.
     *
     * @param User $user The user whose profile data needs to be validated.
     * @param string $payment_provider The payment provider to be checked against ('inpay' in this case).
     * @return array An associative array containing validation errors, if any.
     */
    protected function validateInpayProfileData(User $user, string $payment_provider): array
    {
        $errors = [];

        // Only perform validation for the 'inpay' payment provider.
        if ($payment_provider !== 'inpay') {
            return $errors;
        }

        $countryCode = $user->getCountry();

        // Validate profile data based on the user's country.
        if ($countryCode === 'JP') {
            if (!preg_match('/^\d{3}\-?\d{4}$/', $user->getData('zipcode'))) {
                $errors['zipcode'] = 'invalid.zipcode';
            }
        } elseif ($countryCode === 'AU') {
            if (!preg_match('/^\d{3,4}$/', $user->getData('zipcode'))) {
                $errors['zipcode'] = 'invalid.zipcode';
            }
        } elseif ($countryCode === 'BR') {
            if (empty($user->getData('email'))) {
                $errors['email'] = 'email.is.required';
            }
            /*if (empty($user->getData('nid'))) {
                $errors['nid'] = 'nid.is.required';
            }*/
            /*if (trim($user->getMainProvince()) === '') {
                $errors['province'] = 'province.is.required';
            }*/
        }

        return $errors;
    }

    protected function getInpayValidationRules(string $country = ''): array
    {
        $defaultCountryRules = [
            'bank_name' => ['required' => true],
            'iban' => ['iban' => true, 'required' => true], //, 'regex' => '^[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{1,30}$'
        ];

        $gbCountryRules = [
            'bank_name' => ['required' => true],
            'iban' => ['iban' => true, 'required' => true, 'regex' => '^(GB)[0-9]{2}[a-zA-Z0-9]{1,30}$']
        ];

        $europeanCountryRules = $specificCountryRules = [
            'bank_name' => ['required' => true],
            'bank_account_number' => ['required' => true, 'regex' => ''],
            'bank_clearnr' => ['required' => true, 'regex' => ''],
        ];

        $nonEuropeanCountryRules = [
            'bank_name' => ['required' => true],
            'iban' => ['iban' => true, 'required' => true],
            'swift_bic' => ['required' => true, 'regex' => '^[A-Z0-9]{8}$|^[A-Z0-9]{11}$'],
        ];

        $countryRules = [
            'PL' => $defaultCountryRules,
            'DK' => $defaultCountryRules,
            'MD' => $defaultCountryRules,
            'MK' => $defaultCountryRules,
            'GB' => $gbCountryRules,
            'IM' => $gbCountryRules,
            'ZA' => $specificCountryRules,
            'ID' => $specificCountryRules,
            'TH' => $specificCountryRules,
            'JP' => [
                'bank_name' => ['required' => true],
                'bank_account_number' => ['required' => true, 'regex' => '^\d{6,9}$'],
                'bank_clearnr' => ['required' => true, 'regex' => '^\d{7,7}$'],
            ],
            'AU' => [
                'bank_account_number' => ['required' => true, 'regex' => '^\d{5,9}$'],
                'bank_clearnr' => ['required' => true, 'regex' => '^\d{6}$'],
            ],
            'NZ' => [
                'bank_name' => ['required' => true],
                'bank_account_number' => ['required' => true, 'regex' => '^\d{9,10}$'],
                'bank_clearnr' => ['required' => true, 'regex' => '^\d{6}$'],
                'swift_bic' => ['required' => true, 'regex' => '^[A-Z0-9]{8}$|^[A-Z0-9]{11}$'],
            ],
            'CA' => [
                'bank_name' => ['required' => true],
                'bank_account_number' => ['required' => true, 'regex' => '^\d{7,12}$'],
                'bank_clearnr' => ['required' => true, 'regex' => '^\d{9,9}$'],
                'swift_bic' => ['required' => true, 'regex' => '^[A-Z0-9]{8}$|^[A-Z0-9]{11}$'],
            ],
            'IN' => [
                'bank_name' => ['required' => true],
                'bank_account_number' => ['required' => true, 'regex' => ''],
                'bank_clearnr' => ['required' => true, 'regex' => '^[A-Za-z]{4}0[\dA-Za-z]{6}$'],
            ],
            'BR' => [
                'bank_name' => ['required' => true],
                'bank_account_number' => ['required' => true],
                'nid' => ['required' => true, 'regex' => '^\d{11,11}$'],
            ],
            'HU' => [
                'bank_name' => ['required' => true],
                'iban' => ['iban' => true, 'required' => true],
                'bank_account_number' => ['required' => true, 'regex' => ''],
            ]
        ];

        $countryRules = $countryRules[$country] ?? (
            phive('UserHandler')->userIsEu(cuPl()) ?
                $europeanCountryRules :
                $nonEuropeanCountryRules
            );
        $countryRules['amount'] = ['required' => true, 'regex' => '^\d+(\.\d{1,2})?$'];

        return $countryRules;
    }

    protected function getInpayValidationMessages(?string $country = null): array
    {
        return [
            'bank_account_number' => ['regex' => t('invalid.account.number')],
            'bank_clearnr' => $country == 'NZ'
                ? ['regex' => t('invalid.bank_clearing_bsb')]
                : ['regex' => t('invalid.bank_clearing_system_id')],
            'swift_bic' => ['regex' => t('invalid.swift.bic.format')],
            'iban' => ['iban' => t('cashier.error.required_valid_iban'), 'regex' => t('cashier.error.required_valid_iban')],
            'amount' => ['regex' => t('cashier.error.amount')],
            'nid' => ['regex' => t('invalid.nid')],
        ];
    }

    public function getFrontEndWithdrawValidationRules($psp, $country): array
    {
        if ($psp === 'inpay') {
            return [
                'rules' => $this->getInpayValidationRules($country),
                'messages' => $this->getInpayValidationMessages($country),
            ];
        } elseif ($psp === 'payanybank' || $psp === 'wpsepa') {
            return [
                'rules' => [
                    'iban' => [
                        'iban' => true,
                    ],
                    'swift_bic' => [
                        'regex' => '^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$'
                    ],
                ],
                'messages' => [
                    'iban' => [
                        'iban' => t('cashier.error.required_valid_iban'),
                    ],
                    'swift_bic' => [
                        'regex' => t('cashier.error.required_valid_swift_bic')
                    ],
                ]
            ];
        } else {
            return [
                'rules' => [
                    'iban' => [
                        'iban' => true,
                    ]
                ],
                'messages' => [
                    'iban' => [
                        'iban' => t('cashier.error.required_valid_iban'),
                    ],
                ]
            ];
        }
    }

    /**
     * Returns JS validations to perform on Deposit input fields.
     *
     * @param $user
     * @return array
     */
    public function getFrontEndDepositValidationRules($user)
    {
        $user = cu($user);
        $is_iban_check_enabled = licSetting('bluem', $user)['iban_check']['enabled'];

        if ($is_iban_check_enabled) {
            return [
                'rules' => [
                    'iban' => [
                        'iban' => true,
                    ],
                ],
                'messages' => [
                    'iban' => [
                        'iban' => t('ibanincorrect'),
                    ],
                ]
            ];
        }

        return [];
    }

    /**
     * Handles jurisdictional override of the PSP config when it comes to whether or not a
     * PSP should need document verification before withdrawals via that PSP should be allowed.
     *
     * @param array $config The PSP config entry.
     * @param DBUser $u_obj The user object.
     *
     * @return bool True if KYC can be skipped, false otherwise.
     */
    public function bypassKyc($config, $u_obj)
    {
        if (licSetting('do_kyc', $u_obj)) {
            return false;
        }

        return $config['do_kyc'] === false;
    }

    function getBankAccountsFromDocuments(DBUser $user): array
    {
        if (empty($this->documents)) {
            $this->documents = phive('Dmapi')->getDocuments($user->getId());
        }

        $bank_accounts = [];
        foreach ($this->documents as $document) {
            if ($document['tag'] !== 'bankaccountpic') {
                continue;
            }

            $supplier = $document['supplier'] ?? $document['card_data']['supplier'] ?? null;
            $sub_supplier = $document['sub_supplier'] ?? $document['card_data']['sub_supplier'];

            switch ($supplier) {
                case 'trustly':
                    $account_ext_id = $document['account_ext_id'] ?? $document['card_data']['account_ext_id'] ?? null;

                    if (empty($account_ext_id)) {
                        break;
                    }

                    $account_ref = $document['subtag'];

                    $bank_accounts[$account_ext_id] = [
                        'supplier' => $supplier,
                        'sub_supplier' => $sub_supplier,
                        'account_ref' => $account_ref,
                        'account_ext_id' => $account_ext_id,
                        'encrypted_account_ext_id' => (new Encryption())->encrypt($account_ext_id),
                        'display_name' => ucwords(str_replace('_', ' ', $sub_supplier)) . ' - ' . $account_ref,
                        'user_currency' => $user->getCurrency() // utilise within printBankAccountsDropDown()
                    ];
                    break;

                case 'swish':
                    $swish_mobile = $document['account_ext_id'] ?? $document['subtag'];
                    $bank_accounts[$swish_mobile] = [
                        'supplier' => $supplier,
                        'sub_supplier' => $sub_supplier,
                        'account_ext_id' => $swish_mobile,
                        'account_ref' => $swish_mobile,
                        'display_name' => ucwords(str_replace('_', ' ', $sub_supplier)) . ' - ' . $swish_mobile
                    ];
                    break;

                default:
                    if (!empty($document['subtag'])) {
                        $bank_accounts[$document['subtag']] = $document['subtag'];
                    }
                    break;
            }
        }

        return $bank_accounts;
    }

    /**
     * Checks if a user has a pending Source of Funds document by looping through his documents.
     * Pending in this case means the document is requested, or rejected.
     * We allow withdrawals if the form is 'processing', but in that case
     * the withdrawal needs to be flagged for approval.
     *
     * @param DBUser $user
     * @return mixed returns the doc for now so I can use it to get the id
     */
    function getPendingSourceOfFunds($user)
    {
        $documents = phive('Dmapi')->getDocuments($user->getId());

        foreach ($documents as $document) {
            if ($document['tag'] == 'sourceoffundspic' && in_array($document['status'], ['requested', 'rejected'])) {
                return $document;
            }
        }

        return false;
    }

    /**
     * They should not be able to withdraw/ deposit if proofofwealthpic or proofofsourceoffundspic
     *
     * @param DBUser $user
     * @return bool True if there are pending docs, false otherwise.
     */
    function hasPendingIncomeDocs($user)
    {
        if ($user->hasSetting('proof_of_source_of_funds_activated') || $user->hasSetting('proof_of_wealth_activated')) {
            $documents = phive('Dmapi')->getDocuments($user->getId());
            foreach ($documents as $document) {
                if (($document['tag'] == 'proofofwealthpic' || $document['tag'] == 'proofofsourceoffundspic') && $document['status'] != 'approved') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * We check if we need to show the source of funds doc
     *
     * @param DBUser $user
     * @return bool|array
     */
    public function hasForcedShowSourceOfFunds($user)
    {
        $source_of_funds = $this->getPendingSourceOfFunds($user);
        //this case when source of wealth declaration is rejected but not submitted by user
        if (($user->hasSetting('source_of_funds_status') && $user->getSetting('source_of_funds_status') != 'processing') || $this->hasPendingIncomeDocs($user) !== false) {
            if (!empty($source_of_funds)) {
                // Start waiting time here
                if (empty($user->getSetting('source_of_funds_waiting_since'))) {
                    $user->setSetting('source_of_funds_waiting_since', phive()->hisNow());
                }
                // We should show the source of wealth declaration form if it is requested or rejected
                if ($source_of_funds['status'] != 'processing') {
                    return ['source_of_funds', $source_of_funds];
                }
            }

            return false;
        } else {
            //when user submit the form, and it is in the state of processing (for GB user only)
            if ((!$source_of_funds || in_array($source_of_funds['status'], ['rejected', 'requested'])) && lic('checkSourceFundsStatus', [$user], $user)) {
                return false;
            }
        }
        return null;
    }

    /**
     * Check if source of income is still requested and not approved
     * @param DBUser $user
     * @return bool
     */
    public function hasSourceOfIncomePending($user)
    {
        return $user->hasSetting('source_of_income_status') && $user->getSetting('source_of_income_status') !== 'approved';
    }

    /**
     * This method is used in order to prevent all users from showing up at the same time to claim some kind of promo which could
     * overload the site / system.
     *
     * @return array An array with the country to run on this hour in position 0 and the countries to NOT run in position 1.
     */
    public function getScheduledCountries(string $countries_schedule)
    {
        //TODO does not support several countries on the same hour yet!!
        $schedule        = phive()->fromDualStr($countries_schedule);
        $current_hour    = date("H");
        $scheduled = $schedule[$current_hour];
        $not_scheduled = $scheduled == 'NA' ? array_values($schedule) : [];

        return [$scheduled, $not_scheduled];
    }

    /**
     * Pays out queued transactions.
     *
     * Note that if getScheduledCountries() returns NA as the current country then that means in effect Res of the World (ROW) and we then
     * create an SQL statement that excludes the other countries in the configuration array.
     *
     * @uses CasinoCashier::getScheduledCountries()
     *
     * @param int $type The queued transaction type.
     * @param string $country ISO2 country code which will override the configured schedule if present.
     * @param bool $pay If true we pay out the money, if false we're only looking at a dry run.
     *
     * @return null
     */
    function payQdTransactions($type, $country = '', $pay = true)
    {
        list($scheduled, $not_scheduled) = $this->getScheduledCountries(self::SCHEDULE_QUEUED_TRANSACTIONS);
        $cur_country = empty($country) ? $scheduled : $country;

        if (!empty($cur_country)) {
            if ($cur_country == 'NA') {
                $in_countries = phive('SQL')->makeIn(array_values($not_scheduled));
                $this->autoPay($type, "NOT IN($in_countries)", $pay);
            } else {
                $this->autoPay($type, "= '$cur_country'", $pay);
            }
        }
    }

    /**
     * Reverts an already approved withdrawal.
     *
     * Performs misc. logging and potentially an email alert in addition to actually setting the status of the withdrawal to disapproved
     * and credits the user.
     *
     * TODO henrik remove the dumpTbl call and get rid of the $data argument, refactor all invocations.
     *
     * @param int $ext_id The external PSP id for this withdrawal that we will use to get it.
     * @param string $type PSP network, both this one and the ext_id is needed as they form a unique key together.
     * @param mixed $data <- TODO henrik remove
     * @param bool $send_mail Whether or not to send an email notification.
     *
     * @return null
     */
    function revertPending($ext_id, $type, $data, $send_mail = false, $notification = false)
    {
        $p = is_array($ext_id)
        ? $ext_id
           : $this->db->shs('merge', '', null, 'pending_withdrawals')->loadAssoc('', 'pending_withdrawals', ['ext_id' => $ext_id, 'payment_method' => $type]);

        if (empty($p)) {
            return false;
        }

        // Idempotency protection
        if ($p['status'] == 'disapproved') {
            return false;
        }

        phive()->dumpTbl('reverted_withdrawal_' . $type, $data, $p['user_id']);
        phive('UserHandler')->logAction($p['user_id'], "$type credited withdrawal with id {$p['id']}, it has been disapproved.", $type);
        $this->disapprovePending($p, true, $notification, true, 0, true); // We send email to player

        $u_obj = cu($p['user_id']);
        // We increase the deposit limit again.
        if (!empty($u_obj) && phive('Config')->getValue('deposit-limits', 'deduct-withdrawals') == 'yes') {
            rgLimits()->incType($u_obj, 'deposit', $p['aut_code']);
            rgLimits()->incType($u_obj, 'customer_net_deposit', $p['aut_code']);
        }

        if ($send_mail) {
            $mh      = phive('MailHandler2');
            $subject = "Reverted withdrawal $type: $ext_id";
            $body    = "User id: {$p['user_id']}, amount: {$p['amount']}.";
            $mh->mailLocal($subject, $body, 'payments');
            $mh->mailLocal($subject, $body, 'payments_manager');
        }
    }

    /**
     * TODO if this is ever turned on again it won't work, we're supposed to check with the DMAPI if the cards are verified
     * before getting rid of the card flag.
     *
     * @param DBUser $user
     * @return bool
     */
    function handleCardDeposit($user)
    {
        if (phive('Config')->getValue('withdrawal-flags', 'ccard-fraud-flag') === 'no') {
            return true;
        }
        if ($user->hasSetting('no-ccard-fraud-flag')) { //No sense to check when we should prevent the flag for the customer
            return true;
        }
        //Get all cards for the current user from the mts.
        //[OLD LOGIC] Loop the cards and if we find a card that is neither verified nor approved we set the flag
        //[NEW LOGIC] Flag if count($cards) > 1
        $cards = Mts::getInstance('', $user->getId())->getCards();
        if (count($cards) > 1) {
            $this->setCardFraudFlag($user);
            return false;
        } elseif ($user->hasSetting('ccard-fraud-flag')) {
            $user->deleteSetting('ccard-fraud-flag');
        }
        return true;
    }

    /**
     * Checks if a PSP alternative is a bank option or not, if it is configured as a bank out alternative
     * we relax KYC restrictions, this is because options such as Trustly and Zimpler have already
     * done KYC during the deposit so no need for us to slap on a second unnecessary KYC process.
     *
     * @param DBuser $user The user object.
     * @param string $action Deposit or withdraw.
     * @param string $x The PSP name.
     *
     * @return bool True if it is a bank, false otherwise.
     */
    function xAsBank($user, $action, $x)
    {
        if (empty($user)) {
            return false;
        }
        if ($action == 'deposit') {
            return false;
        }
        if ($this->bypassKyc(phiveApp(PspConfigServiceInterface::class)->getPspSetting($x), $user)) {
            return true;
        }
        if (!in_array($user->getCountry(), phive('Config')->valAsArray($x, 'out-countries'))) {
            return false;
        }
        return true;
    }

    /**
     * This is just an alias of CasinoCashier::xAsBank().
     *
     * @uses CasinoCashier::xAsBank()
     */
    function providerAsBank($user, $type, $action = 'withdraw')
    {
        return $this->xAsBank($user, $action, $type);
    }

    public function supplierIsBank(string $supplier): bool
    {
        $suppliers = phive('Cashier')->getSetting('psp_config_2');

        return in_array($suppliers[$supplier]['type'] ?? '', ['bank', 'ebank'], true);
    }

    /**
     * Simple wrapper around a common line to get either the bank account number or the IBAN from
     * a withdrawal row.
     *
     * @param array $p The withdrawal.
     *
     * @return string The IBAN or the account number, depending on which one is set in the withdrawal.
     */
    function getIbanOrAcc($p)
    {
        return empty($p['iban']) ? $p['bank_account_number'] : $p['iban'];
    }

    /**
     * Checks if we are dealing with a credit card based on type.
     *
     * TODO henrik this needs to be fixed, there are more scenarios now where we're looking at a non CC
     * transaction via a base CC network. Use the PSP config and then the main config and check via.
     *
     * @param string $type The PSP network.
     * @param string $scheme The PSP.
     * @return boolean True if card, false otherwise.
     */
    function typeIsCard($type, $scheme)
    {
        // We also have Trustly deposits with Adyen, so in that case we are not dealing with a card
        if ($type == 'adyen' && $scheme == 'trustly') {
            return false;
        }
        return in_array($type, (new Mts())->getCcSuppliers());
    }

    /**
     * Gets the ISO3 code for a country with the help of an ISO2 code.
     *
     * Some PSPs require us to send the ISO3 version of a country code, this is how we do that conversion
     * as we internally always just use the ISO2 code.
     *
     * @param string $iso2 The ISO2 code.
     *
     * @return string The ISO3 code.
     */
    function getIso3FromIso2($iso2)
    {
        return phive('SQL')->getValue("SELECT iso3 FROM bank_countries WHERE iso = '$iso2'");
    }

    /**
     * Gets the ISO2 code for a country with the help of an ISO3 code.
     *
     * Some PSPs send us the ISO3 version of a country code, this is how we do the conversion
     * as we internally always just use the ISO2 code.
     *
     * @param string $iso3 The ISO3 code.
     *
     * @return string The ISO2 code.
     */
    function getIso2FromIso3($iso3)
    {
        return phive('SQL')->getValue("SELECT iso FROM bank_countries WHERE iso3 = '$iso3'");
    }

    /**
     * Gets the PSP schemes a player has deposited via.
     *
     * Schemes are in effect the sources, we could for example have Adyen as the dep_type and VISA and MC as scehemes for a user.
     *
     * @param int $uid The user id.
     * @param string $type Optionally the main / network PSP to get schemes for.
     *
     * @return array The array of schemes.
     */
    function getSchemes($uid, $type = '')
    {
        $where_type = empty($type) ? '' : "AND dep_type = '$type'";
        return $this->db->sh($uid, '', 'deposits')->load1DArr("SELECT scheme FROM deposits WHERE user_id = $uid $where_type GROUP BY scheme", 'scheme');
    }

    /**
     * There is no fail-safe way to know if a given string corresponds to a credit card or not and there are no database flags either.
     * Possible card formats in the database are: 4263 87** **** 1454, 4*** **** **** 1454, 4444********* etc
     * However other formats like the following are NOT credit cards: ****617399
     * Probably the best format to search for is ' **** ' because all credit card deposits after 2018 are stored as ' **** ', and only
     * deposits before 2018 are stored with format 4444*********.
     * @param $str
     * @return bool
     */
    public function isCardHash($str)
    {
        return strpos($str, ' **** ') !== false;
    }

    private function getOutPSPs()
    {
        if (empty($this->out_suppliers)) {
            // Something like this is perfect to cache on a per request basis as it won't change often.
            $this->out_suppliers = Mts::getInstance()->getOutSuppliers();
        }
        return $this->out_suppliers;
    }

    public function getClosedLoopFacade(?string $closedLoopStartTimeStamp = null): ClosedLoopFacade
    {
        $logger = phive('Logger')->channel('payments');
        $closedLoopFactory = new ClosedLoopFactory($this, $logger);

        /*
         * The value may be an empty string from the BO (in case of a cleared loop or no loop at all).
         * If any loop data is available, we will display all closedLoopData.
        */
        if ($closedLoopStartTimeStamp === null) {
            $closedLoopStartTimeStamp = cuPl()->getSetting('closed_loop_start_stamp');
        }

        return $closedLoopFactory->create($closedLoopStartTimeStamp);
    }

    /**
     * This method is used in the admin BO for displaying the closed-loop overview.
     */
    public function getClosedLoopData(DBUser $user, string $closedLoopStartTimeStamp): array
    {
        $closedLoopFacade = $this->getClosedLoopFacade($closedLoopStartTimeStamp);
        return $closedLoopFacade->closedLoopData($user);
    }

    public function getUserDocuments(?DBUser $user = null): array
    {
        if (empty($this->documents) && $user) {
            $this->documents = phive('Dmapi')->getDocuments($user->getId());
        }

        return $this->documents;
    }

    public function getAntiFraudSchemeByConfig($u_obj)
    {
        if (phive('Config')->isCountryIn('cashier', 'fifo-countries', $u_obj->getCountry())) {
            return 'fifo';
        }

        return 'closed_loop';
    }

    public function getAntiFraudScheme($u_obj)
    {
        if (!empty($this->anti_fraud_scheme)) {
            return $this->anti_fraud_scheme;
        }

        $is_user_country_fifo = phive('Config')->isCountryIn('cashier', 'fifo-countries', $u_obj->getCountry());
        if ($is_user_country_fifo) {
            $u_obj->deleteSetting('closed_loop_start_stamp');
            $u_obj->deleteSetting('closed_loop_cleared');
        }

        if (!$u_obj->hasSetting('closed_loop_start_stamp')) {
            if ($u_obj->hasSetting('closed_loop_cleared')) {
                // To avoid FIFO taking over again.
                $this->anti_fraud_scheme = 'none';
            } else {
                $this->anti_fraud_scheme = 'fifo';
            }
        } else {
            $this->anti_fraud_scheme = $this->getAntiFraudSchemeByConfig($u_obj);
        }

        return $this->anti_fraud_scheme;
    }

    // TODO BAN-12013: Refactor and relocate the function under the `ClosedLoop` namespace.
    public function closedLoopStartStampCron(?int $user_id = null)
    {

        $cl_duration_set = phive('Config')->valAsArray('cashier', 'closed-loop-duration', '|', ':', ['ROW' => 45]);

        $actor = cu();
        if (empty($actor)) {
            $actor_id = uid('system');
            $actor_uname = 'system';
        } else {
            $actor_id = $actor->getId();
            $actor_uname = $actor->getUsername();
        }

        if (is_null($user_id)) {
            $starttime = microtime(true); //start time for crob job
            //add logs when the cronjob starts.
            phive('Logger')
                ->getLogger('payments')
                ->info(
                    "Closed loop reset cron job started at " . date(DATE_RFC822) . " by {$actor_uname} with id {$actor_id}.",
                    [
                    'closed-loop-duration' => $cl_duration_set,
                    ]
                );

            foreach ($cl_duration_set as $country => $cl_duration) {
                $cutoff_stamp = phive()->hisMod("-$cl_duration day");
                $country_condition = $country != 'ROW'
                    ? "u.country = '$country'"
                    : "u.country NOT IN ('" . implode("','", array_keys($cl_duration_set)) . "')";

                $query_from_where = "
                        FROM users AS u
                INNER JOIN users_settings AS us ON u.id = us.user_id
                       WHERE
                       $country_condition
                       AND us.setting = 'closed_loop_start_stamp'
                       AND us.value < '$cutoff_stamp'";

                for ($shard_id = 0; $shard_id <= 9; $shard_id++) {
                    // add logs to actions table with logs for users that has 45 days closed-loop reset cycle completed
                    $insert_actions = <<<EOS
                            INSERT INTO actions (actor, target, descr, tag, actor_username)
                            SELECT
                            $actor_id,
                            u.id,
                            CONCAT_WS(' ', 'Removed closed loop start stamp:', us.value, 'because older than', '$cutoff_stamp'),
                            'closed_loop_cron',
                            '$actor_uname'
                             $query_from_where
                        EOS;

                    phive('SQL')->sh($shard_id)->query($insert_actions);
                    // add the closed_loop_cleared tag for the users that has 45 closed-loop reset cycle completed in users_settings
                    $insert_setting = <<<EOS
                            INSERT INTO users_settings (user_id, setting, value)
                            SELECT
                            u.id,
                            'closed_loop_cleared',
                            '1'
                             $query_from_where
                        EOS;
                    phive('SQL')->sh($shard_id)->query($insert_setting);
                    //delete closed_loop_start_stamp from user settings
                    $delete_setting = <<<EOS
                            DELETE us.*
                            $query_from_where
                            EOS;
                    phive('SQL')->sh($shard_id)->query($delete_setting);
                }
            }
           // it will give the run time in seconds to the nearest microsecond due to get_as_float parameter being true.
            $endtime = microtime(true);
            $duration = $endtime - $starttime;//calculates total time taken in seconds
            //add logs to the /var/log/phive/cron_job.log files.
            phive('Logger')
                ->getLogger('payments')
                ->info(
                    "Cron Job End: Closed loop reset cron job end at " . date(DATE_RFC822) . " by {$actor_uname} with id {$actor_id}.",
                    [
                    'closed-loop-duration' => $cl_duration_set,
                    'time_taken_by_cron_job' => $duration // time taken by closed-loop
                    ]
                );
        } else {
            phive('Logger')
                ->getLogger('payments')
                ->info("Closed loop reset job started by {$actor_id} ({$actor_uname}) for user {$user_id}.");

            $u_obj = cu($user_id);
            $cl_duration = $cl_duration_set[$u_obj->getCountry()] ?? $cl_duration_set['ROW'];

            $cutoff_stamp = phive()->hisMod("-$cl_duration day");
            $query = "SELECT value
                FROM users_settings
                WHERE user_id = $user_id AND setting = 'closed_loop_start_stamp' AND value < '$cutoff_stamp'";

            $stamp = phive('SQL')->sh($user_id)->getValue($query);
            if ($stamp) {
                // Stamp is older than closed loop duration (e.g. 45 days).
                $u_obj->deleteSetting('closed_loop_start_stamp');
                // To avoid FIFO taking over again.
                $u_obj->setSetting('closed_loop_cleared', 1);
                phive('UserHandler')->logAction($u_obj, "Removed closed loop start stamp: {$stamp} because older than $cutoff_stamp", 'closed_loop_cron');
            }

            phive('Logger')
                ->getLogger('payments')
                ->info("Closed loop reset job finished by {$actor_id} ({$actor_uname}) for user {$user_id}.", [
                    'cl_duration_set' => $cl_duration_set,
                    'cl_duration' => $cl_duration,
                    'cutoff_stamp' => $cutoff_stamp,
                    'query' => $query,
                    'stamp' => $stamp,
                ]);
        }
    }

    /**
     * Originally a cron that was supposed to run every hour.
     *
     * NOTE that this script might be running with arbitrary periodicity, not necessarily every hour.
     *
     * This is the basic entry point for automatic approvals, ie if a user has a pending withdrawal
     * it will be automatically approved by this cron job in case it matches certain requirements such as not
     * being too large, the user being fully KYC approved, not having certain fraud flats etc.
     *
     * @return null
     */
    function hourCron()
    {
        $system_user = cu('system');
        $uid         = uid($system_user);
        $out_methods = phive('Config')->getByTagValues('auto-withdraw-option');
        $limit       = phive('Config')->getValue('auto-withdraw', 'limit');
        foreach ($out_methods as $m => $on) {
            if ($on != 'yes') {
                continue;
            }
            $pendings = $this->getPendings(null, null, 'status', 'DESC', 'pending', $m);
            foreach ($pendings as $p) {
                if (!empty($p['stuck'])) {
                    continue;
                }

                if ((int)$p['amount'] >= (int)mc($limit, $p['currency'])) {
                    phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', ['stuck' => 2], ['id' => $p['id']]);
                    continue;
                }
                $u = cu($p['user_id']);
                if ($this->fraud->hasFraudFlag($u)) {
                    continue;
                }
                phive()->dumpTbl('auto-approve-withdrawal', $p, $p);
                if (phive()->getSetting('q-pend') === true) {
                    phive('Site/Publisher')->single('main', 'Cashier', 'approvePending', [$p['id'], (float)$p['amount'], $uid]);
                } else {
                    $this->approvePending($p['id'], (float)$p['amount'], $uid);
                }
            }
        }
    }

    /**
     * Simple helper method to be able to show correct status color in the approval pending withdrawal admin interface.
     *
     * @param DBuser $user The user object.
     * @param array $type_or_tr Pending withdrawal row.
     * @param string $default Potential override in case the row isn't either stuck or over the limit.
     * @param array $flags
     *
     * @return array An array with the label in the first position and the class in the second.
     */
    function getRowCssClass($user, $type_or_tr = '', string $default = 'fill-odd', array $flags = []): array
    {
        if (in_array('bonus_abuse-fraud-flag', $flags)) {
            return ['default', "class='bonus-abuse-tr-line' onmouseover='show()' onmouseout='hide()' title='Status: Bonus Abuse Flag'"];
        }
        foreach ([static::STUCK_NORMAL => 'stuck', static::STUCK_OVER => 'over', static::STUCK_UNKNOWN => 'unknown'] as $status_num => $status_label) {
            if ($type_or_tr['stuck'] == $status_num) {
                return [$status_label, "class='$status_label-tr-line' onmouseover='show()' onmouseout='hide()' title='Status: $status_label'"];
            }
        }

        return ['default', "class='$default'"];
    }

    /**
     * Wrapper for a common logging scenario into the trans_log table.
     *
     * @uses Phive::dumpTbl()
     *
     * @param string $key A tag / identifier.
     * @param array $data The data to dump.
     * @param int $uid User id.
     * @param string $reason The reason for the deposit failover.
     *
     * @return null
     */
    function dumpDepositFail($key, $data, $uid, $reason = 'cancel')
    {
        phive()->dumpTbl("deposit{$reason}-$key", $data, $uid);
    }

    /**
     * Gets a transaction by way of the description.
     *
     * NOTE, it is typically not a good idea to build some kind of abstractions on top of the description column,
     * this method should only be used as an emergency.
     *
     * @param string $descr The description to look for.
     * @param int $uid The user id of the description.
     *
     * @return array The transaction.
     */
    function getTransactionByDescr($descr, $uid)
    {
        $where = array('description' => $descr, 'user_id' => $uid);
        return phive('SQL')->sh($where, 'user_id', 'cash_transactions')->loadAssoc('', 'cash_transactions', $where);
    }

    /**
     * Gets total withdrawal sum in a pending state over all time for display in the admin BO.
     *
     * @param string $cur The currency to filter on.
     *
     * @return int The total sum.
     */
    function getPendingTotal($cur)
    {
        $res = phive('SQL')->shs('merge', '', null, 'pending_withdrawals')->loadArray("SELECT SUM(amount) FROM pending_withdrawals WHERE currency = '$cur' AND status = 'pending'");
        return array_sum(phive()->flatten($res));
    }

    /**
     * Gets all withdrawals with a certain status for a certain user.
     *
     * @param int $uid The user id.
     * @param string $status The status.
     * @param array $extra Optional extra where clauses to filter the results further.
     *
     * @return array The withdrawals.
     */
    function userPendingsByStatus($uid, $status = 'pending', $extra = array())
    {
        return array_reverse(phive('SQL')->sh($uid, '', 'pending_withdrawals')->arrayWhere('pending_withdrawals', array_merge(array('user_id' => $uid, 'status' => $status), $extra)));
    }

    /**
     * Stores current cache balances per currency and optionally per country.
     *
     * This typically runs at midnight and the generated data is needed for regulators and display in the BO.
     *
     * @param bool $do_countries True if we store per country and and currency, false if only per currency.
     *
     * @return null
     */
    function cacheBalances($do_countries = false)
    {
        foreach (cisos() as $ciso) {
            if ($do_countries) {
                $countries = phive('SQL')->shs('merge', '', null, 'users')->loadArray("SELECT country FROM users GROUP BY country");
                $countries = array_unique($countries);
                foreach ($countries as $key => $country) {
                    $cash = 0;
                    $cash = $this->getTotalCash($ciso, false, $country['country']);
                    if (empty($cash)) {
                        continue;
                    }
                    phive()->miscCache(
                        date('Y-m-d') . '-cash_balance-' . $country['country'] . '-' . $ciso,
                        serialize(array('real' => $cash))
                    );
                }
            } else {
                $ext_cash = 0;
                $ext_bonus_cash = 0;
                phive()->miscCache(
                    date('Y-m-d') . '-cash-balance-' . $ciso,
                    serialize(
                        array(
                            'pending' => $this->getPendingTotal($ciso),
                            'real' => $this->getTotalCash($ciso),
                            'bonus' => phive('Bonuses')->getTotalBalances(" = 'active' ", false, $ciso),
                            'timestamp' => phive()->hisNow()
                        )
                    )
                );
            }
        }
    }

    /**
     * Transaction search for the admin BO.
     *
     * @param array $req Search filters submitted in the BO GUI.
     *
     * @return array The found transactions.
     */
    function transactionsSearch($req)
    {
        $str = '';
        if (!empty($req['sdate'])) {
            $str .= " AND `timestamp` > '{$req['sdate']}' ";
        }
        if (!empty($req['edate'])) {
            $str .= " AND `timestamp` < '{$req['edate']}' ";
        }
        if (!empty($req['type'])) {
            $str .= " AND transactiontype IN(" . implode(',', $req['type']) . ") ";
        }
        if (!empty($req['currency'])) {
            $str .= " AND ct.currency = '{$req['currency']}' ";
        }
        if (!empty($req['descr'])) {
            $str .= " AND description LIKE '%{$req['descr']}%' ";
        }

        if (!empty($req['username'])) {
            $user = cu($req['username']);
            if (!empty($user)) {
                $user_id = $user->getId();
                $str .= " AND ct.user_id = $user_id ";
            }
        }

        if (!empty($str)) {
            $str = "SELECT ct.*, u.username FROM cash_transactions ct, users u WHERE ct.user_id = u.id" . $str . " ORDER BY `timestamp` LIMIT 0,3000";
            $db = empty($user_id) ? phive('SQL')->shs('merge', 'timestamp', 'asc', 'cash_transactions') : phive('SQL')->sh($user_id, '', 'cash_transactions');
            return $db->loadArray($str);
        }
        return array();
    }

    /*
     * A wrapper around CasinoCashier::cleanupNumber() with some extra logic being executed.
     *
     * We first clean up the amount and if we're looking at a withdrawal we apply some bonus related restrictions / modifications.
     *
     * @return float Amount as float with two decimals.
     */
    function checkAmount(&$err, $amount, $withdrawal = false, $user = '')
    {
        $user = empty($user) ? cuPl() : $user;
        $amount = empty($amount) ? $_POST['amount'] : $amount;
        $amount = $this->cleanUpNumber($amount);
        if ($withdrawal) {
            $amount = $this->handleDepBonuses($user->getId(), $amount * 100) / 100;
        }
        if (empty($amount)) {
            $err['amount'] = 'err.empty';
        }
        return $amount;
    }

    /**
     * Gets the number of people who have made a bet.
     *
     * The main thing here is the GROUP BY option which determines what is returned and how, ie which group interval to use:
     * month, day or date typically but can be any users column.
     *
     * TODO henrik, change this to getBettorsCount.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $by_month What to group on.
     * @param string $cur Currency (ISO3).
     * @param string $country (ISO2).
     * @param int $node Optional node number if we're only looking to query a specific node, otherwise they're all queried and aggregated.
     *
     * @return array The counts as values with the group by as key.
     */
    function getBettersCount($sdate, $edate, $by_month, $cur, $country, $node = -1, $join_province = '')
    {
        $join = '';

        if ($by_month === true) {
            $by_month = 'udstats.month';
            $group1 = ", COUNT(udstats.user_id) AS month_count, DATE_FORMAT(udstats.date, '%Y-%m') AS month_num";
            $group_by = 'month_num';
            $group2 = 'GROUP BY month_num, udstats.user_id';
        } elseif ($by_month == 'day') {
            $group1 = ', COUNT(udstats.user_id) AS day_count, DAYOFMONTH(udstats.date) AS day_num';
            $group2 = 'GROUP BY day_num, udstats.user_id';
            $group_by = 'day_num';
        } elseif ($by_month == 'date') {
            $group1 = ', COUNT(udstats.user_id) AS day_count, DATE(udstats.date) AS date';
            $group2 = 'GROUP BY date, udstats.user_id';
            $group_by = 'date';
        } elseif (!empty($by_month)) {
            $group1 = ", COUNT(udstats.user_id) AS {$by_month}_count";
            $group2 = "GROUP BY $by_month, udstats.user_id";
            $group_by = $by_month;
        } else {
            $group_by = false;
        }

        $where_cur  = empty($cur) ? '' : "AND udstats.currency = '$cur'";
        $where_country  = empty($country) ? '' : "AND udstats.country = '$country'";

        if (!empty($join_province)) {
            $join .= $join_province;
        }

        $str = "SELECT udstats.user_id$group1 FROM users_daily_stats As udstats
                    $join
                WHERE `date` >= '$sdate'
                    AND `date` <= '$edate'
                    AND udstats.bets > 0
                    $where_cur $where_country $group2";

        $res = array();

        $db = $node >= 0 ? phive('SQL')->sh($node) : phive('SQL');
        $tmp =  $db->load2DArr($str, $group_by);
        foreach ($tmp as $key => $vals) {
            $res[$key][$by_month . '_count'] = count($vals);
        }
        return $res;
    }

    /**
     * Clear inactive accounts, used in crons, manually or any other functions
     *
     * @param string $sdate Do accounts that last login is before this date
     * @param string $description Custom description
     * @param int $transaction_type
     * @param bool $deduct_all If we are going to deduct all balance put this to true
     * @param null $where_extra
     */
    public function clearInactive($sdate, $description = 'Inactivity fee', $transaction_type = 43, $deduct_all = false, $where_extra = null)
    {
        $str = "SELECT * FROM users WHERE last_login != '0000-00-00 00:00:00' AND DATE(last_login) < '$sdate' AND cash_balance > 0 ";
        if (!empty($where_extra)) {
            $str .= $where_extra;
        }
        $inactive = phive("SQL")->shs('merge', '', null, 'users')->loadArray($str);
        $cleared = [];
        foreach ($inactive as $u) {
            $should_skip = (!$deduct_all && date('d', strtotime($sdate)) != date('d', strtotime($u['last_login'])))
                || !lic('isInactivityFeeEnabled', [], $u);

            if ($should_skip) {
                continue;
            }

            if ($deduct_all === true) {
                $to_deduct = $u['cash_balance'];
            } else {
                $to_deduct = min($u['cash_balance'], mc(100, $u['currency']));
            }

            $user = cu($u['id']);
            if (is_object($user)) {
                phive('Cashier')->transactUser(
                    $user,
                    -$to_deduct,
                    $description,
                    null,
                    null,
                    $transaction_type,
                    true
                );
                $cleared[] = $u;
            }
        }
        return $cleared;
    }

    /**
     * A simple setter for the current FIFO session variable.
     *
     * @param mixed $psp The FIFO PSP.
     *
     * @return null
     */
    public function setCurrentFifo($psp)
    {
        $_SESSION['current_fifo'] = $psp;
    }

    /**
     * A simple getter for the current FIFO session variable.
     *
     * @return string $psp The FIFO PSP.
     */
    public function getCurrentFifo()
    {
        return $_SESSION['current_fifo'];
    }

    /**
     * This PSP is a fallback for the player's country and we have to ignore FIFO to avoid a situation where
     * there are no withdrawal options because the player should not have to deposit in order to be able to withdraw
     * unless absolutely necessary.
     *
     * @param string $psp The PSP.
     * @param DBUser $u_obj The user object.
     *
     * @return bool True if it is a fallback option, false otherwise.
     */
    public function isWithdrawFallback($psp, $u_obj)
    {
        $fallbackCountries = phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp, 'withdraw')['fallback_for_countries'];

        if (!empty($fallbackCountries)) {
            return in_array($u_obj->getCountry(), $fallbackCountries);
        }

        return in_array($psp, $this->getBankSuppliers());
    }

    /**
     * Checks if the PSP in question is the current FIFO PSP.
     *
     * From top to bottom:
     * 1. First we check if this alternative is a withdrawal fallback, if it is we return true right away.
     * 2. First we get the currently FIFO and its potential deposit that makes it the FIFO.
     * 3. In case the Config indicates we next we loop all the passed in PSPs or all configured PSPs
     * in case none are passed in. We check if each one matches the current PSP and is a KYC exempt PSP, if we have a match
     * we return true.
     * 4. Next we check if we have a value passed in (eg a card number), if we have a value and the deposit is not empty
     * we check if the passed in column (eg card_hash) in the deposit matches the passed in value. If that is the case
     * we return true if the current PSP matches the passed in PSP.
     * 5. Finally we return true if the current PSP is the passed in PSP, false otherwise.
     *
     * @param string $psp The PSP to check.
     * @param string $value Optional value to check against a potentially existing FIFO deposit.
     * @param string $col The column to use in order to check against the $value.
     * @param array $psps Optional array of PSPs to use, otherwise the full config is used.
     * @param DBUser $u_obj The user object we need to work with in case $psps is empty.
     *
     * @return bool True if the PSP to check is the current FIFO PSP, false otherwise.
     */
    public function isCurrentFifo($psp, $value = null, $col = 'card_hash', $psps = null, $u_obj = null)
    {
        if (empty($psp)) {
            return false;
        }

        if ($u_obj && $this->getAntiFraudScheme($u_obj) != 'fifo') {
            // We're on closed loop for this user so we return true in order for fifo to not block anything.
            return true;
        }

        if (!empty($u_obj) && $this->isWithdrawFallback($psp, $u_obj)) {
            return true;
        }

        list($current_psp, $deposit) = $this->getCurrentFifo();

        if (empty($current_psp)) {
            $mts = Mts::getInstance('', $u_obj);
            $cards = $mts->rpc('query', 'recurring', 'getAllCardsForWithdraw', ['user_id' => $u_obj->getId()]);
            $psps = $this->getAllAllowedPsps($u_obj, 'withdraw');
            $fifo_data = $this->getFifo($u_obj, $psps, $cards);
            $this->setCurrentFifo($fifo_data);
            list($current_psp, $deposit) = $this->getCurrentFifo();
        }

        if (empty($current_psp)) {
            return true;
        }

        // In case we always want BANK to be available
        if (phive('Config')->getValue('banks', 'bypass_fifo') == 'yes' && $current_psp != 'paypal') {
            $psps = $psps ?? $this->getAllAllowedPsps($u_obj, 'withdraw', 'desktop');

            foreach ($psps as $cpsp => $config) {
                if ($config['do_kyc'] === false && ($cpsp == $psp || $config['option_of'] == $psp)) {
                    return true;
                }
            }
        }

        if (!empty($value) && !empty($deposit)) {
            return $current_psp == $psp && $deposit[$col] == $value;
        }

        return $current_psp == $psp;
    }

    private function cantTransfer(DBUser $user): bool
    {
        return
            $this->getSetting('disable-transfers', false) ||
            $user->isBlocked() ||
            $user->isSuperBlocked() ||
            (
                $user->getSetting('excluded-date') &&
                $user->getSetting('unexclude-date')
            )
        ;
    }

    private function depositFirst(DBUser $user, string $type): bool
    {
        $depositFirst = (bool)($this->getResolvedPspConfigValue(
            $user,
            'deposit_first',
            true,
            $type
        )
        );

        return $depositFirst && $this->getPspDepCount($user->getId(), $type) == 0;
    }

    /**
     * Common logic that is run at the start of every deposit and withdrawal.
     *
     * Here we perform common logic like converting the submitted amount to cents and checking various limits
     * and FIFO restrictions in case of a withdrawal.
     *
     * TODO henrik remove the dump line.
     *
     * TODO henrik make sure we have the card_id here so we can use that in the can withdraw function
     *
     * @param array $args This the data submitted from the GUI.
     * @param DBUser $user The user.
     * @param string $type The PSP (network, eg Adyen).
     * @param string $inout In if deposit, out if withdrawal.
     * @param string $amount The user submitted amount.
     * @param bool $check_lower Some PSPs require us to support a lower amount than our typical minimum, this flag will
     * make sure the lower limit test does not get executed if it is true.
     * @param string $sub_type Sub PSP (origin, eg Sofort if Sofort is via Adyen).
     * @param string $card_hash An obfuscated card hash.
     * @param string int $card_id Card id in the MTS.
     * @param bool $check_fifo Whether or not to disable FIFO checks.
     * @return array With potential errors and amount as float with two decimals.
     */
    public function transferStart($args, $user, $type, $inout, $amount = '', $check_lower = true, $sub_type = '', $card_hash = '', $card_id = 0, $check_fifo = true)
    {
        $amount = empty($amount) ? $args['amount'] : $amount;

        $err = array();

        if ($this->cantTransfer($user)) {
            $err['verified'] = 'transaction.blocked.html';
        }

        if (empty(phive('Bonuses')->getCurReload($user)) && $inout == PspActionType::IN) {
            phive('Bonuses')->setCurReload(trim($args['bonus']));
        }

        $amount = $this->checkAmount($err, $amount, $inout === 'out', $user);
        $cents = (int)ceil($amount * 100);

        if (empty($cents)) {
            $err['amount'] = 'err.empty';
        }

        if (!phiveApp(PspConfigServiceInterface::class)->checkUpperLimit($user, $cents, $type, $inout)) {
            $err['amount'] = "err.toomuch";
        }

        $cl_data = null;
        $cl_key = null;
        if ($inout === PspActionType::OUT) {
            // We need the Closed Loop data in order to be able to override the minimum amount check.
            $closedLoopFacade = $this->getClosedLoopFacade();
            $cl_data = $closedLoopFacade->getApplicableClosedLoopData($user);

            // TODO: To be handled in BAN-12402
            if ($type == 'swish') {
                $cl_key = $args['swish_mobile'];
            } elseif (in_array($type, $this->getBankSuppliers())) {
                $cl_key = $args['bank_account_number'];
            } else {
                $cl_key = !empty($card_hash) ? $card_hash : $type;
            }

            $cl_entry = $cl_data[$cl_key];
            if (!empty($cl_entry)) {
                if ($cl_entry['status'] === ClosedLoopHelper::STATUS_PENDING_DISABLED) {
                    $err['amount'] = 'err.disabled.by.closed.loop';
                } elseif ($cl_entry['status'] === ClosedLoopHelper::STATUS_OPEN) {
                    $lower_limit = phiveApp(PspConfigServiceInterface::class)->getLowerLimit($type, PspActionType::OUT, $user);
                    if ($cents < min($cl_entry['remaining_amount'], $lower_limit)) {
                        // Lower than both remaining closed loop amount and minimum limit, we assign error and prevent further checking of limit.
                        $check_lower = false;
                        $err['amount'] = "err.toolittle";
                    } elseif ($cl_entry['remaining_amount'] < $lower_limit) {
                        // Remaining limit is lower than lowest limit but the withdraw amount is higher than remaining so we just turn off further
                        // min limit checking and call it a day.
                        $check_lower = false;
                    }
                }
            }
        }

        if ($check_lower && !phiveApp(PspConfigServiceInterface::class)->checkLowerLimit($user, $cents, $type, $inout)) {
            $err['amount'] = "err.toolittle";
        }

        if ($inout == PspActionType::IN) {
            if ($user->isDepositBlocked() || $user->isTemporalDepositBlocked() || $user->isCrossBrandCheckBlocked()) { //Leaving this here for now see ch45766 /Ricardo
                $err['verified'] = 'deposit.blocked.html';
            }
        }

        if ($inout == PspActionType::OUT) {
            $res = (new ClosedLoopHelper($this))->validateClosedLoopWithdrawal($user, $cents, $cl_key, $cl_data);
            if ($res['success'] === false) {
                return [$res, $amount];
            }

            if ($check_fifo && !$this->isCurrentFifo($type, $card_hash, 'card_hash', null, $user)) {
                $err['fifo'] = 'err.wrong.fifo';
            }

            if ($type != 'ccard' && $this->depositFirst($user, $type)) {
                $err['deposit_first'] = 'err.depositfirst';
            }

            $bank_account_number = $args['bank_account_number'] ?? ''; // may be $args['iban'] too

            $can_withdraw_result = $this->canWithdraw($user, $type, $sub_type, $bank_account_number, $card_id, $card_hash, $args);

            if ($can_withdraw_result['success'] === false) {
                $result_data = $can_withdraw_result['data'];

                if (!empty($result_data)) {
                    if ($result_data[0] === 'wrong_user_fields') {
                        $err['verified'] = reset($result_data[1]);
                    } else {
                        $result_data['errors'] = t($can_withdraw_result['msg']);
                        $result_data['error_msg_alias'] = $can_withdraw_result['msg'];
                        $err['verified'] = $result_data;
                    }
                } else {
                    $map = ['verify' => 'err.user.not.verified', 'pending.documents' => 'err.pic.verified'];
                    $err['verified'] = $map[ $can_withdraw_result['msg'] ] ?? $can_withdraw_result['msg'];
                }
            }

            $cur_balance = (int)$user->getAttribute('cash_balance');
            if ($cur_balance < $cents) {
                $err['amount'] = 'err.lowbalance';
            }

            if (empty($err) && !$this->getDepCount($user->getId())) {
                $user->setSetting('nodeposit-fraud-flag', 1);
            }

            // If there was a problem with the money transfer, log it
            if (is_string($err['amount']) && strlen($err['amount']) > 1) {
                phive('Logger')
                    ->getLogger('payments')
                    ->error("Money Transfer Failed for user {$user->getId()}", [
                        'function'      => 'CasinoCashier::transferStart',
                        'amount'        => $amount, // Note this could be different from what the user requested due to limits
                        'reason'        => $err['amount'],
                        'action'        => $inout,
                        'psp'           => $type,
                        'check_lower'   => $check_lower,
                        'check_fifo'    => $check_fifo,
                        'card_id'       => $card_id
                    ]);
            }
        }

        return array($err, $amount);
    }

    // TODO henrik remove when Trustly is via MTS
    public function transferEnd($err, $do_translate = true, $extra = []): array
    {
        $translate = "";
        $return = [];
        if ($do_translate) {
            foreach ($err as $field => $errstr) {
                if (!is_array($errstr)) {
                    $translate .= t2('register.' . $field) . ': ' . t2($errstr) . "<br>";
                } else {
                    $return = ['errors' => $errstr];
                }
            }
        } else {
            $translate = $err;
        }

        if (empty($return)) {
            $return = ["error" => $translate];
        }

        if (!empty($extra)) {
            $return = array_merge($return, $extra);
        }

        return $return;
    }

    /**
     * Gets the total sum of all cash balances.
     *
     * @param string $cur Optional ISO3 code to sum only a specific currency.
     * @param bool $change Whether or not to do FX to the casino currency.
     * @param string $country Optional ISO2 code for a country to sum only that country.
     *
     * @return int The total sum.
     */
    function getTotalCash($cur = '', $change = false, $country = '')
    {
        if ($change) {
            $str = "SELECT SUM(u.cash_balance / c.multiplier) AS amount FROM users u, currencies c WHERE u.currency = c.code";
        } else {
            $where  = empty($cur) ? '' : "WHERE currency = '$cur'";
            if ($country) {
                $where .= empty($country) ? '' : " AND country = '$country'";
            }
            $str = "SELECT SUM(cash_balance) FROM users $where";
        }
        $res = phive('SQL')->shs('merge', '', null, 'users')->loadArray($str);
        return array_sum(phive()->flatten($res));
    }

    /**
     * Tries to approve a pending withdrawal, this is typically a cron action or manually by a P&F agent.
     *
     * @param int $pend_id The id of the withdrawal to approve.
     * @param int $amount Optional amount override, eg if we want to apply a withdrawal fee.
     * @param int $approver_id The user id of the approver.
     * @param int $user_id The user id of the owner of the withdrawal, it's used to pick the correct DB node.
     *
     * @return string A success or fail message for display in the GUI, note that this message is meaningless in case
     * withdrawals are qeueued which they are if the casino is running both cron approvals and manual approvals at the
     * same time. In that case success or fail messages will be displayed via websocket in the GUI instead, sent from
     * the queue server executing the approvals in the queue.
     */
    function approvePending($pend_id, $amount = 0, $approver_id = '', $user_id = null)
    {
        if (phMgetShard('pending-withdrawal-being-processed-id-' . $pend_id, $user_id)) {
            die("Transaction $pend_id is already being processed, please try again in a few minutes.");
        }

        phMsetShard('pending-withdrawal-being-processed-id-' . $pend_id, $pend_id, $user_id, 360);

        if (phive()->getSetting('q-pend') === true) {
            phive()->dumpTbl('pending-ws', func_get_args());
        }

        $approver_id = empty($approver_id) ? uid() : $approver_id;
        if (empty($approver_id)) {
            $approver_id = uid('system');
        }

        $result = $this->payPending($pend_id, $amount, $approver_id, $user_id);

        $msg = 'Transfer failed';

        if ($result === true) {
            $msg = 'Transfer successful';
        }

        if (!empty($this->withdraw_msg)) {
            $msg .= ', msg: ' . $this->withdraw_msg;
        }

        if (phive()->getSetting('q-pend') === true) {
            toWs(['id' => $pend_id, 'msg' => $msg, 'performedBy' => $approver_id, 'action' => 'approve'], 'pendingwithdrawals', 'na');
        }

        phMdelShard('pending-withdrawal-being-processed-id-' . $pend_id, $user_id);

        return $msg;
    }

    public function approveAndConfirmWithdrawal(array $pendingWithdrawal)
    {
        parent::approvePending($pendingWithdrawal['id'], $pendingWithdrawal['approved_by']);

        $user = cu($pendingWithdrawal['user_id']);

        if (phive()->moduleExists("MailHandler2") && !phive()->moduleExists("PRUserHandler")) {
            require_once __DIR__ . '/../../../diamondbet/html/display.php';
            $replacers = phive('MailHandler2')->getDefaultReplacers($user);
            $replacers["__METHOD__"] = ucfirst($pendingWithdrawal['payment_method']);
            $replacers["__AMOUNT__"] = nfCents($pendingWithdrawal['amount'], true);
            phive("MailHandler2")->sendMail('withdrawal.ok', $user, $replacers);
        }
    }

    public function hasWithdrawalConfirmationCallback(string $supplier): bool
    {
        $pspWithdrawConfig = phiveApp(PspConfigServiceInterface::class)->getPspSetting($supplier, 'withdraw');

        return $pspWithdrawConfig['callback_confirmation'] ?? false;
    }

    /**
     * Removes a pending withdrawal from the ability of the withdrawing user to cancel it before it has been approved.
     *
     * @param $p The withdrawal to flush.
     *
     * @return bool Whether or not the DB query was successful.
     */
    function flushPending($p)
    {
        $p['flushed'] = 1;
        return phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->save('pending_withdrawals', $p);
    }

    /**
     * Disapproves a pending withdrawal.
     *
     * @uses Cashier::disapproveWithdrawal() In order to change the actual status of the withdrawal.
     *
     * @param int $pend_id The id of the withdrawal to be disapproved.
     * @param bool $redeposit Whether or not to credit the user back the money which is typically the case as the user
     * is typically debited when submitting the withdrawal.
     * @param bool $notification Whether or not a call to the PSP is needed in order to execute the withdrawal disapproval.
     * @param bool $ignore_status Whether or not to do anything in case the status is something else than pending already.
     * @param int $add_to_refund In case for instance some kind of withdrawal fee needs to be credited back too we pass it in here.
     * @param bool $send_email Whether or not to email the withdrawing user a notification that the withdrawal was denied.
     * @param int $user_id User who performed the action
     *
     * @return int The current player balance (including potential credits).
     */
    function disapprovePending($pend_id, $redeposit = true, $notification = false, $ignore_status = false, $add_to_refund = 0, $send_email = false, $user_id = null)
    {
        $pend_id = is_numeric($pend_id) ? (int)$pend_id : $pend_id['id'];
        $dclick_key = "pending-$pend_id-CasinoCashier";

        /*Initial issue: BAN-10407
        Quick solution: Pass 0 as a user_id to make the Redis Key non user-specific to prevent the user and system actions to execute the process simultaneously
        Suggestion for the future improvement: BAN-10487*/
        dclickStart($dclick_key, 0);

        $mtsClient = new MtsClient(
            phive('Cashier')->getSetting('mts'),
            phive('Logger')->channel('payments')
        );
        $p = $this->getPending($pend_id);

        if ($p['status'] == 'disapproved') {
            return dclickEnd($dclick_key, true, 0);
        }

        $transactionType = Cashier::CASH_TRANSACTION_NORMALREFUND;

        $transaction = phive('Cashier')->getCashTransaction($pend_id, $transactionType, $p['user_id']);
        if (!empty($transaction)) {
            phive('Cashier')->logCashTransaction(['transaction_type' => $transactionType, 'parent_id' => $pend_id], $transaction);
            return dclickEnd($dclick_key, true, 0);
        }

        $pend_id = $p['id'];

        if (!in_array($p['status'], ['pending', 'processing']) && !$ignore_status) {
            return dclickEnd($dclick_key, false, 0);
        }

        if (!$notification && $p['mts_id']) {
            $continue = true;
            $currentUser = cu();
            $actorId = is_object($currentUser) ? cu()->getId() : null;
            $supplier = $p['payment_method'];

            switch ($supplier) {
                case Supplier::Trustly:
                case Supplier::SWISH:
                    try {
                        $res = $mtsClient->withdrawCancel($p['mts_id'], [
                            'customer_transaction_id' => $p['id'],
                            'user_id' => $p['user_id'],
                            'reference_id' => $p['ext_id'],
                            'actor_id' => $actorId,
                            'country' => cu($p['user_id'])->getCountry(),
                        ]);

                        if ($res['success'] === true && $res['data']['processed'] ?? false) {
                            phive()->dumpTbl("$supplier-disapprove-wd", ['id' => $p['id'], 'actor' => $actorId], $p['user_id']);
                        } else {
                            phive("SQL")->sh($p)->updateArray('pending_withdrawals', ['status' => 'processing'], "id = $pend_id AND status='pending'");
                            $continue = false;
                        }
                    } catch (\Throwable $e) {
                        phive()->dumpTbl("$supplier-disapprove-wd-error", $e->getMessage(), $p['user_id']);
                        $continue = false;
                    }

                    break;
                case Supplier::Zimpler:
                    Mts::getInstance($supplier)->transferRpc('disapproveWithdrawal', ['transaction_id' => $p['mts_id']]);
                    break;
            }

            if (!$continue) {
                return dclickEnd($dclick_key, false, 0);
            }
        }

        $result = phive('Casino')->changeBalance($p['user_id'], $p['amount'] + $add_to_refund, 'Withdrawal Refund', $transactionType, '', 0, 0, false, $pend_id);
        if ($result !== false) {
            parent::disapprovePending($pend_id, false, $send_email, $user_id);
            toWs(['id' => $pend_id, 'msg' => 'Cancelled', 'performedBy' => $user_id, 'action' => 'cancel'], 'pendingwithdrawals', 'na');
        }

        $this->withdrawalAttemptMonitoring($pend_id);

        return dclickEnd($dclick_key, $result, 0);
    }

    /**
     * Checks for deposit bonuses and fails them if there is a profit on them, typically
     * used when performing a withdrawal to cancel / fail these bonuses.
     *
     * @param mixed $user_id Some kind of data containing information we can use to fetch a user.
     * @param int $amount Typically a withdrawal amount, the failing of the bonuses can change
     * the user's balance, if that happens and the withdrawal amount is smaller than the new balance
     * we return the new balance to limit the withdrawal to that amount.
     *
     * @return int The allowed withdrawal amount.
     */
    function handleDepBonuses($user_id, $amount)
    {
        $user   = cu($user_id);
        $db_profit  = (int)$this->getDepBonusProfit($user->getId());
        if ($db_profit > 0) {
            //forfeiting the active bonus when time of withdrawal
            if (
                in_array(phive('BrandedConfig')->getBrand(), phive('BrandedConfig')->getWithdrawalForfeitBrands()) &&
                phive('DBUserHandler')->isWithdrawalForfeitAllowedForUserCountry($user)
            ) {
                phive('Bonuses')->failDepositBonuses($user->getId(), "Failed because of withdrawal");
            }
            $cur_balance = $user->getCurAttr('cash_balance');
            if ($amount > $cur_balance) {
                $amount = $cur_balance;
            }
        }
        return $amount;
    }

    /**
     * Inserts a failed transaction, currently happens when the MTS reports a fail.
     *
     * @param array $insert The array to insert.
     *
     * @return bool|int Integer with the new id if insert was successful, boolean false if insert failed.
     */
    public function insertFailedTransaction($insert)
    {
        if (empty($insert['user_id'])) {
            return false;
        }

        if (empty($insert['notified_at'])) {
            $insert['notified_at'] = phive()->hisNow();
        }

        return phive('SQL')->insertArray('failed_transactions', $insert);
    }


    /**
     * Basic wrapper around CasinoCashier::processWithdrawal() in order to run the withdrawal processing in a forked
     * process in case we are so configured. The reason for running forked is as usual to avoid blocking the user's
     * experience, all the processing will be done non-blocking in the background, like setting misc. fraud flags etc.
     *
     * @uses CasinoCashier::processWithdrawal()
     *
     * @param DBUser $user The user object.
     * @param int $pid The withdrawal id.
     *
     * @return null
     */
    public function startProcessWithdrawal($user, $pid)
    {
        $this->db->sh($user)->updateArray('pending_withdrawals', ['status' => 'preprocessing'], ['id' => $pid]);
        if ($this->getSetting('withdrawal_preprocessing') === true) {
            phive()->pexec('CasinoCashier', 'processWithdrawal', [$user->getId(), $pid]);
        } else {
            $this->processWithdrawal($user->getId(), $pid);
        }
    }

    /**
     * Some PSPs require a redirect of the user before the withdrawal can be saved in the pending state, such as KYC or authentication.
     * In that case we simply start with saving the withdrawal in the initiated state without doing any debits etc of the
     * withdraw amount. If the KYC / authentication was later successful we go from this initiated state to the pending state and
     * at the same time debit the user, fail bonuses etc.
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount to withdraw.
     * @param array $insert The data to insert into pending_withdrawals.
     *
     * @return int|bool The withdrawal id if success, false otherwise.
     */
    public function insertInitiatedWithdrawal($user, $amount, $insert)
    {
        $insert['status'] = 'initiated';
        return $this->insertPendingCommon($user, $amount, $insert, false);
    }

    /**
     * Common logic used when creating withdrawals.
     *
     * Let's go through things from top to bottom:
     *
     * 1. We begin by creating the array to insert, among other things storing the IP of the user at time of withdrawal.
     * 2. Then we typically debit the withdrawal amount even though the withdrawal hasn't been approved yet, we will
     * credit that money back in case the withdrawal gets disapproved. Note that if the jurisdiction won't let
     * the user cancel the withdrawal we insert the withdrawal with flushed 1 right away.
     * 3. In case we're looking at a withdrawal that will start in the initiated state we exit here by returning
     * the new withdrawal id immediately.
     * 4. Finally we invoke the whole process withdrawa logic with the fraud checks and so on.
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount to withdraw.
     * @param array $insert Data to insert into the pending_withdrawals table.
     * @param bool $changeBalance whether to debit or not, typically yes / true.
     *
     * @return int The new withdrawl id.
     */
    function insertPendingCommon($user, $amount, $insert, $changeBalance = true)
    {

        if (empty($amount)) {
            return 'empty.amount';
        }

        if (empty($insert['payment_method'])) {
            return false;
        }

        $insert = array_merge($insert, [
            'user_id' => $user->getId(),
            'amount' => $amount,
            'ip_num' => $user->getAttr('cur_ip')
        ]);

        if (empty($insert['created_by'])) {
            $insert['created_by'] = $user->getId();
        }

        if (empty($insert['currency'])) {
            $insert['currency'] = $user->getAttr('currency');
        }

        $isStarted = phive("SQL")->sh($insert)->beginTransaction();

        if (!$isStarted) {
            return false;
        }

        if (lic('noReverseWithdrawals', [], $user) === true) {
            $insert['flushed'] = 1;
        }

        $withdrawalId = phive('SQL')->sh($insert)->insertArray('pending_withdrawals', $insert);

        if ($changeBalance) {
            $newBalance = phive('Casino')->changeBalance(
                $user,
                -$amount,
                'Withdrawal',
                Cashier::CASH_TRANSACTION_WITHDRAWAL,
                '',
                0,
                0,
                false,
                $withdrawalId
            );

            if ($newBalance === false) {
                phive("SQL")->sh($insert)->rollbackTransaction();
                return false;
            }
        }

        $isCommitted = phive("SQL")->sh($insert)->commitTransaction();

        if (!$isCommitted) {
            phive("SQL")->sh($insert)->rollbackTransaction();
            return false;
        }

        // We don't want to do the withdrawal processing in case we just initiate a withdrawal.
        if ($insert['status'] == 'initiated') {
            return $withdrawalId;
        }

        $this->startProcessWithdrawal($user, $withdrawalId);

        return $withdrawalId;
    }

    /**
     * Handles various flags / scenarios that need to be determined before a withdrawal can be stored with the pending status.
     *
     * In short this method will do the flag determinations and when done set the status to pending. The reason for the
     * status change is to prevent the withdrawal to show up in the P&F GUI as a withdrawal that can be approved and to
     * prevent the cron job from approving it automatically before the flags have been set.
     *
     * The various flags might prevent auto cron approvals, in that case the withdrawal will have to be exclusively manually
     * handled by the P&F agents which will have to determine whether or not some suspicious activity has actually taken place or not.
     *
     * @param int|DBUser $user_id The user id or object that the withdrawal belongs to.
     * @param int $pending_id The withdrawal id.
     * @param bool $respect_status If true will abort processing in case the status of the withdrawal is not preprocessing.
     *
     * @return bool Whether not the final status update was successful or not.
     */
    public function processWithdrawal($user_id, $pending_id, $respect_status = true)
    {
        $preprocessing = phive("SQL")->sh($user_id)->loadAssoc("SELECT * FROM pending_withdrawals WHERE id = " . (int)$pending_id);

        if (empty($preprocessing) || ($preprocessing['status'] != 'preprocessing' && $respect_status)) {
            phive()->dumpTbl('withdrawal-preprocessing', ['msg' => 'Not proper status on preprocessing withdrawal', 'pending' => $preprocessing], $user_id);
            return;
        }

        /** @var DBUser $user */
        $user = cu($user_id);
        $pending = $this->getPendingsUser($user->getId(), "= 'approved'", 'LIMIT 0,1')[0];

        $flag_configs = phive('Config')->getByTagValues('withdrawal-flags');

        if (empty($pending['timestamp'])) {
            $last_withdrawal_stamp = phive()->toDate($user->data['register_date']) . ' 00:00:00';
            $description = "Registration";
        } else {
            $last_withdrawal_stamp = $pending['timestamp'];
            $description = "Withdrawal";
        }
        $description .= " date: $last_withdrawal_stamp<br>";

        $dep_sum = $this->sumTransactionsByType($user->getId(), 3, $last_withdrawal_stamp, phive()->hisNow());
        $last_deposit = $this->getLatestDeposit($user);
        $last_dep_stamp = empty($last_deposit) ? 'none' : $last_deposit['timestamp'];

        /* Wager fraud flag */
        if ($last_withdrawal_stamp != 'none' && phive()->moduleExists('Casino')) {
            $sess_sums = phive('UserHandler')->sumGameSessions($user->getId(), $last_withdrawal_stamp);
            $betsum = $sess_sums['bet_amount'];
            $winsum = $sess_sums['win_amount'];
            $total = $betsum - $winsum;
            $description .= "In period: staked: $betsum, won: $winsum, gr rev: $total, deposited: $dep_sum <br>";

            $lowWagerFlag = LowWagerFlag::create($pending_id);
            if ($betsum < $dep_sum && !phive('Config')->isCountryIn('withdrawal-flags', 'low-wager-flag-exclude-countries', $user->getCountry())) {
                $lowWagerFlag->assign($user, AssignEvent::ON_WITHDRAWAL_PROCESS);
            } else {
                $lowWagerFlag->revoke($user, RevokeEvent::ON_WITHDRAWAL_PROCESS);
            }
        }

        /* Bonus fraud flag */
        if (phive('Config')->getValue('withdrawal-flags', 'bonus-fraud-flag') === 'yes') {
            $prior = $this->getPendingsUser($user->getId());
            if (empty($prior) && $last_dep_stamp != 'none') {
                $betsum = phive('QuickFire')->getBetsOrWinSumForUser('bets', $user->getId(), phive()->toDate($user->data['register_date']) . ' 00:00:00');
                $limit = phive('Config')->getValue('limits', '10-free-wager-requirement');
                if (chg($user, '', (int)$betsum) < $limit) {
                    $user->setSetting('bonus-fraud-flag', 1);
                }
            }
        }

        $fraud = new Fraud();

        if ($this->getSetting('withdrawal_liability_flag') === true) {
            /* Liability fraud flag */
            $liability = $fraud->getLiabilityUnallocatedAmounts($user);
            $diff = abs($liability['diff']);
            if ($diff >= 100) { //threshold is 100 cents as per Alex request
                $liab_thold = phive('Config')->getValue('withdrawal-flags', 'liability-threshold', 1000);
                if ($diff >= chg(phive("Currencer")->baseCur(), $user, $liab_thold, 1)) {
                    $this->setLiabilityFraudFlag($user_id, "Liability flag added. Internal id: $pending_id, opening bal: {$liability['opening']}, net liability: {$liability['net']}, closing bal: {$liability['closing']}, since: {$liability['since']}");
                    $description .= "Liability check: found a {$liability['diff']} cents difference between {$liability['since']} and now<br>";
                    $fraud->sendLiabilityNotification($user, $liability);
                } else {
                    $this->removeLiabilityFraudFlag($user_id);
                    $description .= "Liability check: success<br>";
                }
            } else {
                $this->removeLiabilityFraudFlag($user_id);
                $description .= "Liability check: success<br>";
            }
        }

        /* Withdrawal monthly limit flag */
        //  You should be able to withdraw deposits – withdrawals + €30,000
        $last_30_days = $fraud->getWithdrawalDepositSum($user_id, 1);
        $config_max = phive('Config')->getValue('out-limits', 'withdrawal-max-30-days-limit', 3000000);
        $withdrawal_month_limit = chg(phive("Currencer")->baseCur(), $user, $config_max);
        $wdLimitFlag = WithdrawalLimitFlag::create($pending_id);
        if (intval($last_30_days['withdrawals'] - $last_30_days['deposits']) > intval($withdrawal_month_limit)) {
            $wdLimitFlag->assign($user, AssignEvent::ON_WITHDRAWAL_PROCESS);
            $description .= "Over the max withdrawal limit. Last 30 days: total deposited {$last_30_days['deposits']} cents / withdraw {$last_30_days['withdrawals']} cents<br>";
        } else {
            $wdLimitFlag->revoke($user, RevokeEvent::ON_WITHDRAWAL_PROCESS);
        }

        /* Withdrawal daily limit flag */
        // Should flag, once in 24 hours, anyone that has made more than €5000 withdrawals in 24 hour period.
        $wd_last_24_h = phive('CasinoCashier')->getWithdrawalsInPeriod($user->getId(), phive()->hisMod("-1 day"), "IN('approved', 'pending', 'preprocessing') AND status <> 'disapproved'", null, 'timestamp', 'currency, SUM(amount) as amount_sum, COUNT(*) as count')[0];
        $wd_last_24_h_limit = chg(phive("Currencer")->baseCur(), $user, phive('Config')->getValue('out-limits', 'withdrawal-max-24-hours-limit', 500000));
        $limitLast24hFlag = WithdrawLast24HoursLimitFlag::create($pending_id);
        if (intval($wd_last_24_h['amount_sum']) > intval($wd_last_24_h_limit)) {
            if ($user->hasSettingExpired('withdraw_last_24_hours_limit-fraud-flag', 1)) {
                $limitLast24hFlag->assign($user, AssignEvent::ON_WITHDRAWAL_PROCESS);
                $description .= "Over the max last 24 hours withdrawal limit. Total withdraw last 24 hours {$wd_last_24_h['amount_sum']} {$wd_last_24_h['currency']} cents<br>";
            }
        } else {
            $limitLast24hFlag->revoke($user, RevokeEvent::ON_WITHDRAWAL_PROCESS);
        }

        /* SNG unfinished battles flag */
        // anyone who has played a sng battle where majority of users didn't finish their spins
        if ($this->getSetting('unfinished_sng_battles_flag') === true) {
            if ($fraud->getUnfinishedTournamentsFlagData($user, $last_withdrawal_stamp) === true) {
                $user->setSetting('majority_unfinished_battles-fraud-flag', 1);
            }
        }

        /* SNG majority battle spins done in sng flag 2 */
        //where majority of spins for a users has been done in a sng battle.
        if ($this->getSetting('majority_sng_battles_flag') === true) {
            if ($fraud->isMajoritySngTournaments($user_id, $last_withdrawal_stamp) === true) {
                $user->setSetting('majority_sng_battles-fraud-flag', 1);
            }
        }


        /* Multi deposit method fraud flag
         *  commented as requested in story# 163891
         */
        /*if ($this->getSetting('multi_method_flag') === true) {
           $multi_dep_data = $fraud->getMultiDepositsData($user, $this->getSetting('multi_method_flag_data'), $this->getSetting('multi_method_flag_do_scheme'), $last_withdrawal_stamp);
           if ($multi_dep_data['result'] === true) {
           $user->setSetting('multi_deposit-fraud-flag', 1);
           $description .= "Deposit methods on the last {$multi_dep_data['days']} days: " . implode(',', $multi_dep_data['methods']) . "<br>";
           phive()->dumpTbl('multi-deposits-flag', $multi_dep_data, $user_id);
           }
           }*/

        $fraudFlagRegistry = new FraudFlagRegistry($pending_id);
        $fraudFlagRegistry->assign(
            $user,
            AssignEvent::ON_WITHDRAWAL_PROCESS,
            ['config' => $flag_configs, 'withdrawal' => $preprocessing]
        );

        $description .= $fraud->checkBonusAbuse($user, $flag_configs);

        /* Money laundry */
        if (!empty($pending['id'])) {
            $w1 = ['timestamp' => $last_withdrawal_stamp, 'id' => $pending['id'], 'currency' => $preprocessing['currency']];
            $w2 = ['timestamp' => phive()->hisNow(), 'id' => $preprocessing['id']];
            $wager_sum = empty($betsum) ? 0 : $betsum;
            $fraud->insertRow($user->getId(), $w1, $w2, ['amount' => $wager_sum]);
        }

        phive()->pexec('Cashier/Arf', 'invoke', ['onWithdrawal', $user->getId()]);
        $res = phive('SQL')->sh($user)->updateArray('pending_withdrawals', ['status' => 'pending', 'description' => $description], "id = {$preprocessing['id']}");
        lic('manipulateFraudFlag', [$user,$preprocessing['payment_method'], $preprocessing['scheme'], $pending_id, AssignEvent::ON_WITHDRAWAL_PROCESS], null, null, $user->data['country']);
        return $res;
    }

    /**
     * Cron job that redos the processing stage in case it didn't execute properly, every 30 minutes.
     *
     * @param int $interval How far back in time (in minutes) to go in order to get "stuck" withdrawals that needs to be redone.
     *
     * @return null
     */
    public function endPreprocessingWithdrawals($interval = 30)
    {
        $query = "SELECT * FROM pending_withdrawals WHERE status = 'preprocessing' AND timestamp <= DATE_SUB(NOW(), INTERVAL {$interval} MINUTE)";

        $miss_calc_list = phive("SQL")->shs('merge', null, null, 'pending_withdrawals')->loadArray($query);

        foreach ($miss_calc_list as $preprocessing) {
            phive()->dumpTbl("preprocessing-failed", $preprocessing);
            $res = $this->processWithdrawal($preprocessing['user_id'], $preprocessing['id']);
            if (empty($res)) {
                phive('SQL')->sh($preprocessing, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', [
                    'description' => "Background process that calculates the fraud flags for this withdrawal failed<br>",
                    'status' => 'pending',
                    'stuck' => 1
                ], "id = {$preprocessing['id']}");
            }
        }
    }

    /**
     * Sets a liability flag in case the user's balance differs from the transaction history, ie the balance has somehow
     * been increased (or decreased) directly without a corresponding transaction being created.
     *
     * @param DBUser $user The user object.
     * @param string $message Action log message.
     *
     * @return null
     */
    public function setLiabilityFraudFlag($user, $message = 'Liability fraud flag added')
    {
        $u = is_object($user) ? $user : cu($user);
        if ($u->hasSetting('no-liability-fraud-flag')) {
            return;
        }
        $u->setSetting('liability-fraud-flag', 1);
        phive('UserHandler')->logAction($u, $message, 'withdrawal-liability-flag');
    }

    /**
     * Removes a liability fraud flag, typically called when the liability difference has been reconciled.
     *
     * @param DBUser $user The user object.
     * @param string $message Action log message.
     *
     * @return null
     */
    public function removeLiabilityFraudFlag($user, $message = 'Liability fraud flag removed')
    {
        $u = is_object($user) ? $user : cu($user);
        if ($u->hasSetting('liability-fraud-flag')) {
            $u->deleteSetting('liability-fraud-flag');
            phive('UserHandler')->logAction($u, $message, 'withdrawal-liability-flag');
        }
    }

    /**
     * Wrapper around CasinoCashier::insertPendingCommon() for the withdraw-with-card scenario.
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount to withdraw.
     * @param string $ref_code The MTS card id.
     * @param string $aut_code The withdraw amount after applied fees.
     * @param string $scheme The originator, ex: visa or mc.
     * @param $loc_id TODO henrik remove this and refactor the invocation.
     * @param string $supplier The PSP network the withdrawal will go via.
     *
     * @return int The new withdrawl id.
     */
    function insertPendingCard($user, $amount, $ref_code, $aut_code, $scheme, $loc_id, $supplier = 'wirecard', $type = '')
    {
        return $this->insertPendingCommon($user, $amount, array(
            'ref_code'        => $ref_code,
            'aut_code'        => $aut_code,
            'payment_method'  => $supplier,
            'wallet'          => $type,
            'deducted_amount' => $amount - $aut_code,
            'scheme'          => $scheme,
            'loc_id'          => $loc_id));
    }

    /**
     * Wrapper around CasinoCashier::insertPendingCommon() for the withdraw-with-bank (or bank like PSP) scenario.
     *
     * @param DBUser $user The user object.
     * @param int $amount The amount to withdraw.
     * @param array $insert Data to insert into the pending_withdrawals table.
     * @param string $bank_method Depending on whether we want to treat the picked PSP as a generic bank or with
     * unique branding and / or KYC flow we pass in 'bank' or the PSP name here.
     * @param bool $change_balance whether to debit or not, typically yes / true.
     *
     * @return int The new withdrawl id.
     */
    function insertPendingBank($user, $amount, $insert, $bank_method = 'bank', $change_balance = true)
    {
        $insert['payment_method'] = $bank_method;
        return $this->insertPendingCommon($user, $amount, $insert, $change_balance);
    }

    /**
     * Gets the basic Gross Game Revenue (GGR) from the user's POV.
     *
     * @param int $user_id The user id.
     * @param string $sdate The start date.
     * @param string $edate The end date.
     *
     * @return int The GGR.
     */
    function getNormalGamingResult($user_id, $sdate = '', $edate = '')
    {
        $betsum         = phive('QuickFire')->getBetsOrWinSumForUser('bets', $user_id, $sdate, $edate, 0);
        $winsum         = phive('QuickFire')->getBetsOrWinSumForUser('wins', $user_id, $sdate, $edate, 0);
        return $winsum - $betsum;
    }

    /**
     * Gets the GGR for the user during play with a deposit bonus.
     *
     * @param int $user_id The user id.
     * @param int $entry_id The bonus entry id.
     *
     * @return int The GGR, if negative we return 0.
     */
    function getDepBonusProfit($user_id, $entry_id = '')
    {
        if (empty($entry_id)) {
            $dep_entries = phive('Bonuses')->getDepositBonusEntries($user_id, 'active');
            $entry     = array_shift($dep_entries);
        } else {
            $entry = phive('Bonuses')->getBonusEntry($entry_id, $user_id);
        }
        $user    = cu($user_id);

        if (empty($entry) || empty($user)) {
            return 0;
        }

        $profit  = $this->getNormalGamingResult($user_id, $entry['activated_time']);

        return $profit > 0 ? $profit : 0;
    }

    // TODO henrik remove
    function hasWithdrawnSince($method, $user_id, $period = '-30 day', $status = "IN('approved','pending')")
    {
        $result = $this->getPendingGroup(date('Y-m-d', strtotime($period)), date('Y-m-d H:i:s'), $status, $method, $user_id);
        return empty($result) ? false : true;
    }

    /**
     * Common logic related to paying out pending bank withdrawals.
     *
     * Bank withdrawals typically require us to store more information than withdrawals via other methods,
     * that info is handled here.
     *
     * @uses Mts::withdraw()
     * @uses Mts::withdrawResult()
     *
     * @param array $p The pending withdrawal.
     * @param DBUser $user The user object.
     * @param array $merge Potential override values that will be merged on top of the default params.
     *
     * @return array An array with result related information.
     */
    public function payPendingBank($p, $user, $merge = [])
    {
        $mts = Mts::getInstance($p['payment_method']);
        $params = [
            'currency'              => $p['currency'],
            'bank_receiver'         => $p['bank_receiver'],
            'bank_name'             => $p['bank_name'],
            'bank_code'             => !empty($p['bank_code']) ? $p['bank_code'] : '',
            'bank_branch_code'      => !empty($p['bank_clearnr']) ? $p['bank_clearnr'] : '',
            'bank_address'          => $p['bank_address'],
            'bank_city'             => $p['bank_city'],
            'state'                 => $p['bank_city'],
            'bank_country'          => $p['bank_country'],
            'bank_account_number'   => $p['bank_account_number'],
            'swift_bic'             => $p['swift_bic'],
            'iban'                  => $p['iban'],
            'email'                 => $user->getAttr('email'),
            'bank_receiver_address' => $user->getFullAddress(),
            'transaction_description' => phive()->getSetting('domain'),
            'province'                 => $user->getSetting('main_province') ?? ''
        ];
        $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, array_merge($params, $merge));
        return $mts->withdrawResult($res);
    }

    /**
     * Main logic that is being run on on withdrawal approvals.
     *
     * We've got three main sections here, first the preparation section, then sending the actual call to the MTS which looks different depending
     * on PSP. And finally handling the return result from the MTS, especially if it is successful.
     *
     * TODO henrik remove all those duplicate Mts::getInstance() lines.
     * TODO henrik remove the Supplier constants usage, it's redundant.
     * TODO henrik remove all the redundant calls to withdraw and withdraw result.
     *
     * @param int $pend_id The id of the withdrawal.
     * @param int $amount The amount to withdraw, it will override the amount stored in the pending withdrawal row.
     * @param int $approver_id The user id of the approver.
     * @param int $user_id The user id of the person withdrawing money.
     *
     * @return array|boolean The MTS result array or false in case of failure.
     */
    function payPending(
        $pend_id,
        $amount = 0,
        $approver_id = 0,
        $user_id = null
    ) {
        $this->getSetting('test');

        $mtsClient = new MtsClient(
            phive('Cashier')->getSetting('mts'),
            phive('Logger')->channel('payments')
        );

        $mtsSdkClient = MtsClientFactory::create(
            $this->settings_data['mts']['base_url'],
            $this->settings_data['mts']['key'],
            $this->settings_data['mts']['consumer_custom_id'],
            phive('Logger')->channel('payments'),
            true
        );

        // TODO this with the withdraw message is ugly, refactor the whole logic.
        // It will be much easier to refactor if all PSPs go through the  MTS.
        $this->withdraw_msg = '';

        $p = $this->getPending($pend_id, $user_id);

        $user = cu($p['user_id']);

        if (empty($p) || empty($user)) {
            return false;
        }

        if ($p['status'] != 'pending' || $p['status'] == 'processing') {
            return false;
        }

        $this->adjustWithdrawalDeductedAmount($p, $user);

        $p['status'] = 'processing';
        phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->save('pending_withdrawals', $p);

        if ($amount > 0 && $amount < $p['amount']) {
            if (is_numeric($p['aut_code']) && $p['aut_code'] > $amount) {
                $p['aut_code'] = $amount;
                phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->save('pending_withdrawals', $p);
            }

            $p['amount'] = $amount;
        }

        if (empty($p) || $p['amount'] <= 0) {
            return false;
        }

        $result = true;
        $mts_id = 0;
        $ext_id = '';
        $bank = [];
        $fees   = 0;

        // $p['aut_code'] contains the authorized amount, typically the original withdrawal amount - fees
        list($conv_currency, $conv_cents) = phive('Currencer')->convertLegacyCurrency($user, $p['aut_code'], 1.05);

        switch ($p['payment_method']) {
            case 'bank':
                $result = true;
                break;

            case 'euteller':
            case 'siirto':
                list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user, ['sub_supplier' => $p['scheme'], 'account_id' => $p['net_account']]);
                break;

            case Supplier::Mifinity:
                // Mapping depending on what's going on, if we're looking at a PayAnyBank withdrawal then we have stored acccount type in net_account,
                // if we're looking at a Mifinity wallet withdrawal we want to send the Mifinity account id which we also have stored in net_account.
                $account_key = $p['scheme'] == 'payanybank' ? 'account_type' : 'account_id';
                list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user, ['sub_supplier' => $p['scheme'], $account_key => $p['net_account']]);
                break;

            case 'payretailers':
                $params = [
                    'document_type' => $p['net_email'],
                    'account_type'  => $p['net_account'],
                    'sub_supplier'  => $p['scheme']
                ];
                if ($p['scheme'] == 'pix') {
                    $params['recipient_pix_key'] = $p['mb_email'];
                }
                list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user, $params);
                break;

            case 'inpay':
                $province_name = phive('Licensed')->getProvinceNameByIso($user->getSetting('main_province'));
                $extra = [
                    'user_address' => $user->getAttr('address'),
                    'province_name' => $province_name
                ];

                list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user, $extra);
                break;

            case 'citadel':
                list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user);
                break;

            case 'ecopayz':
                $mts = Mts::getInstance(Supplier::EcoPayz);
                $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, ['account_id' => $p['net_account'], 'currency' => $p['currency']]);
                list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res);
                break;

            case 'skrill':
                $lang  = $user->getAttr('preferred_lang');
                $mts   = Mts::getInstance(Supplier::Skrill);
                $res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
                    'currency'     => $p['currency'],
                    'email'        => $p['mb_email'],
                    'subject'      => t('mb.withdraw.emailsubject', $lang),
                    'note'         => t('mb.withdraw.emailnote', $lang),
                    'sub_supplier' => $p['scheme']
                ]);
                list($mts_id, $ext_id, $result, $msg, $notify_agent) = $mts->withdrawResult($res);
                break;

            case 'neteller':
                $mts = Mts::getInstance(Supplier::Neteller);
                $res = $mts->withdraw($p['id'], $p['user_id'], $conv_cents, -1, [
                    'email'      => $p['net_account'],
                    'currency'   => $conv_currency
                ]);
                list($mts_id, $ext_id, $result, $msg, $notify_agent) = $mts->withdrawResult($res);
                break;

            case 'astropaywallet':
                $mts = Mts::getInstance('astropaywallet');
                $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
                    'phone' => empty($p['net_account']) ? $user->getMobile() : $p['net_account']
                ]);
                list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res);
                break;

            case 'muchbetter':
                $mts = Mts::getInstance('muchbetter');
                $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
                    'transaction_description' => phive()->getSetting('domain'),
                    'currency'                => $p['currency'],
                    'phone'                   => $p['net_account']
                ]);
                list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res);
                break;

            case 'instadebit':
                if ($this->getSetting('instadebit_via_mts') === true) {
                    try {
                        $res = $mtsClient->withdraw(
                            Supplier::Instadebit,
                            [
                                'transaction_id' => $p['mts_id'],
                                'customer_transaction_id' => $p['id'],
                                'user_id' => $p['user_id'],
                                'actor_id' => $approver_id,
                                'country' => $user->getCountry(),
                                'province' => $user->getMainProvince(),
                                'currency' => $p['currency'],
                                'amount' => $p['amount'],
                                'ip' => $p['ip_num'] ?? remIp() ?? '0.0.0.0',
                            ],
                            [
                                'Device-Type' => $this->getPendingWithdrawalEquipment($user, $p['id']),
                            ]
                        );

                        if (isset($res['data'])) {
                            $mts_id = $res['data']['id'] ?? $mts_id;
                            $ext_id = $res['data']['ext_id'] ?? $ext_id;
                        }

                        $result = true;

                        phive()->dumpTbl('instadebit-approve-wd-result', ['res' => $res, 'actor' => $approver_id]);
                    } catch (\Throwable $e) {
                        $result = $msg = $e->getMessage();

                        phive()->dumpTbl('instadebit-approve-wd-error', $result);
                    }
                } else {
                    require_once __DIR__ . '/Instadebit.php';
                    $insta = new Instadebit();
                    $result = $insta->payout($p);
                }

                break;

            case 'paypal':
                $mts   = Mts::getInstance('paypal');
                $res   = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
                    'currency' => $p['currency'],
                    'email'    => $p['mb_email'],
                    'payer_id' => $p['net_account']
                ]);
                list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res);
                break;

            case 'zimpler':
                $res = Mts::getInstance($p['payment_method'])->transferRpc('approveWithdrawal', [
                    'transaction_id'          => $p['mts_id'],
                    'customer_transaction_id' => $p['id'],
                    'user_id'                 => $p['user_id'],
                    'reference_id'            => $p['ext_id'],
                    'actor_id'                => $approver_id,
                    'country'                 => $user->getCountry(),
                ]);

                phive()->dumpTbl('zimpler-approve-wd', ['res' => $res, 'actor' => $approver_id]);

                if ($res['success'] === true) {
                    $result = true;
                } else {
                    $result = is_array($res['errors']) ? reset($res['errors']) : $res['errors'];
                }

                phive()->dumpTbl('zimpler-approve-wd-result', $result);

                break;
            case Supplier::Trustly:
                try {
                    $res = $mtsClient->withdraw(
                        Supplier::Trustly,
                        [
                            'transaction_id' => $p['mts_id'],
                            'customer_transaction_id' => $p['id'],
                            'user_id' => $p['user_id'],
                            'reference_id' => $p['ext_id'],
                            'actor_id' => $approver_id,
                            'country' => $user->getCountry(),
                            'currency' => $p['currency'],
                            'amount' => $p['amount'],
                            'account_id' => $p['ref_code'],
                            'ip' => $p['ip_num'] ?? remIp() ?? '0.0.0.0',
                        ],
                        [
                            'Device-Type' => $this->getPendingWithdrawalEquipment($user, $p['id']),
                        ]
                    );

                    if (isset($res['success']) && $res['success'] === true) {
                        $user->deleteSetting('trustly_country-fraud-flag');
                    }

                    if (isset($res['data'])) {
                        $mts_id = $res['data']['id'] ?? $mts_id;
                        $ext_id = $res['data']['orderid'] ?? $ext_id;
                        $bank = $res['data']['bank'] ?? $bank;
                    }

                    $result = true;

                    phive()->dumpTbl('trustly-approve-wd-result', ['res' => $res, 'actor' => $approver_id]);
                } catch (ClientException $e) {
                    $responseBody = $e->getResponse()->getBody()->getContents();
                    $result = json_decode($responseBody, true);

                    phive()->dumpTbl('trustly-wd-error', array_merge($result, [
                        'pending_withdrawal_id' => $p['id'],
                    ]), $p['user_id']);
                    $mts_id = $result['transaction_id'];
                } catch (\Throwable $e) {
                    phive()->dumpTbl('trustly-wd-exception', [
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'pending_withdrawal_id' => $p['id'],
                    ], $p['user_id']);

                    $result = [
                        'code' => self::SUPPLIER_CONNECTION_ERROR,
                        'transaction_id' => 0,
                    ];
                }

                break;

            case Supplier::Swish:
                if (!empty($p['mts_id'])) {
                    $endpoint = SwishPayout::retry(
                        $p['mts_id'],
                        $user->getNid(),
                        $user->getSetting('swish_mobile'),
                        $approver_id,
                        $user->getCountry()
                    );
                } else {
                    $endpoint = SwishPayout::initialize(
                        $p['aut_code'],
                        $p['user_id'],
                        $user->getNid(),
                        $user->getSetting('swish_mobile'),
                        $p['id'],
                        $user->getCountry(),
                        $p['currency'],
                        $p['ip_num'] ?? remIp() ?? '0.0.0.0'
                    );
                    $endpoint->withDeviceType($this->getPendingWithdrawalEquipment($user, $p['id']));
                }

                $res = $mtsSdkClient->call($endpoint);

                if ($res instanceof PayoutResource) {
                    $mts_id = $res->id;
                    $ext_id = $res->orderId;

                    $result = true;
                } else {
                    $mts_id = $res->transactionId;
                    $result = $res->body;
                }

                break;

            case Supplier::Paymentiq:
                $mts = Mts::getInstance(Supplier::Paymentiq);

                $ud = $user->data;

                $arr = [
                    'sid'              => $user->getCurrentSession()['id'],
                    'country'          => $user->getCountry(),
                    'sub_supplier'     => $p['scheme'],
                    'currency'         => $user->getCurrency(),
                    'ip'               => $user->getAttr('cur_ip'),
                    'username'         => $p['net_account'],
                    'password'         => $p['net_email'],
                    'bank_code'        => $p['bank_code'],
                    'bank_branch_code' => $p['bank_clearnr'],
                    'bank_account_number' => $p['bank_account_number'],
                    'locale'         => $user->getMainProvince()
                ];

                $res = $mts->withdraw($p['id'], $user, $p['aut_code'], '', $arr);

                list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res);
                break;

                // If it is a CC supplier we execute, doesn't matter if it is turned OFF in the config or not,
                // we respect what we have in the pending withdrawal line.
            case (in_array($p['payment_method'], array_keys((new Mts())->getCcSetting()))):
                if (!empty($p['iban']) || !empty($p['bank_account_number'])) {
                    // Adyen bank / sepa withdrawal for instance.
                    list($mts_id, $ext_id, $result, $msg) = $this->payPendingBank($p, $user);
                } else {
                    $via_network = $this->getPspRoute($user, $p['payment_method'], 'ccard_psps');
                    $mts = Mts::getInstance($via_network, $p['user_id']);
                    if ($via_network != $p['payment_method']) {
                        // The network differs from the payment method so we set the payment method as sub supplier for that network.
                        $mts->setSubSupplier($p['payment_method']);
                    }

                    if (in_array($p['wallet'], ['applepay', 'googlepay'])) {
                        $mts->setSubSupplier($p['wallet']);
                    }

                    // Failovers at this point in the flow have been turned OFF for now, it complicates things too much, better
                    // disapprove and the player tries again to get different CC PSP.
                    // OR P&F could get a new feature to manually change the CC PSP for a retry with different PSP
                    // /Henrik
                    //$res = $mts->failover('withdraw', $p['scheme'], $p['id'], $p['user_id'], $p['aut_code'], $p['ref_code']);

                    $res = $mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], $p['ref_code']);
                    list($mts_id, $ext_id, $result, $msg) = $mts->withdrawResult($res, 'Missing card, amount or withdrawal row');

                    /*
                       // This is OFF for the time being as we don't fail (or want to fail over) over at this stage anymore anyway, so the supplier will never be different. /Henrik
                       if($mts->supplier != $p['payment_method'] && $result === true){
                       // The withdrawal failed over so we have to update the payment method.
                       $p['payment_method'] = $mts->supplier;
                       phive('SQL')->sh($p)->save('pending_withdrawals', $p);
                       }
                     */
                }
                break;
        }

        $this->withdraw_msg = $msg;

        $update_array = [
            'amount' => $p['amount'],
            'deducted_amount' => $amount > 0 ? $p['amount'] - $p['aut_code'] : $p['deducted_amount'],
            'real_cost' => $this->getOutFee($p['amount'], $p['payment_method'], $user, $p['scheme']),
        ];

        if (!empty($ext_id)) {
            $update_array['ext_id'] = $ext_id;
        }
        if (!empty($mts_id)) {
            $update_array['mts_id'] = $mts_id;
        }
        if (!empty($bank)) {
            $update_array['bank_name'] = $bank['name'] ?? '';
            $update_array['bank_account_number'] = $bank['account_number'] ?? '';
            $update_array['bank_country'] = $bank['country'] ?? '';
        }

        $actor = cu($approver_id);
        if (empty($actor)) {
            $actor = cu('system');
        }

        if ($result === true && !empty($p['payment_method'])) {
            $user = cu($p['user_id']);

            rgLimits()->decType($user, 'net_deposit', $p['aut_code']);
            rgLimits()->decType($user, 'customer_net_deposit', $p['aut_code']);

            // We decrease the deposit limit progress
            if (phive('Config')->getValue('deposit-limits', 'deduct-withdrawals') == 'yes') {
                rgLimits()->decType($user, 'deposit', $p['aut_code']);
            }

            if (phive()->moduleExists('Trophy')) {
                phive('Trophy')->onEvent('withdraw', $p['user_id']);
            }

            if (in_array($p['payment_method'], array('skrill', 'neteller', 'wirecard')) && !empty($p['aut_code'])) {
                $p['amount'] = $p['aut_code'];
            }

            $update_array['approved_by'] = $actor->getId();

            if (!$this->hasWithdrawalConfirmationCallback($p['payment_method'])) {
                $this->approveAndConfirmWithdrawal(array_merge($p, $update_array));
            } else {
                phive('UserHandler')->logAction(
                    $user,
                    "processing withdrawal and waiting for callback confirmation by {$p['payment_method']} of {$p['amount']} with internal id of {$p['id']}",
                    'processing-withdrawal',
                    true,
                    $actor
                );

                $this->withdraw_msg .= ' It will be approved after receiving callback confirmation from PSP.';
            }

            phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')
                ->updateArray('pending_withdrawals', $update_array, ['id' => $p['id']]);

            $this->withdrawalAttemptMonitoring($pend_id);
        } elseif (isset($result['code'])) {
            $return = false;

            switch ((int)$result['code']) {
                case self::INTERNAL_ERROR_CODE_ACCOUNT_NOT_FOUND:
                    if (!in_array($p['payment_method'], [Supplier::Trustly])) {
                        $update = [
                            'stuck' => static::STUCK_UNKNOWN,
                            'flushed' => 1,
                            'status' => 'pending',
                            'mts_id' => (int)$result['transaction_id'] ?? 0,
                        ];

                        phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $update, array('id' => $p['id']));

                        $this->withdrawalAttemptMonitoring($pend_id);
                        break;
                    };

                    try {
                        $res = $mtsClient->selectAccount($p['payment_method'], [
                            'country' => $user->getCountry(),
                            'locale' => phive('Localizer')->getLocale(phive('Localizer')->getLanguage()),
                            'successUrl' => phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/trustly_select_account.php?result=success'),
                            'failUrl' => phive()->getSiteUrl('', true, 'phive/modules/PayNPlay/html/trustly_select_account.php?result=failure'),
                            'ip' => $p['ip_num'] ?? remIp() ?? '0.0.0.0',
                            'transactionId' => $mts_id,
                            'firstName' => $user->getAttr('firstname'),
                            'lastName' => $user->getAttr('lastname'),
                            'email' => $user->getAttr('email'),
                            'personId' => $user->getNid(),
                        ]);

                        phive()->dumpTbl('select_account', [
                            'res' => $res,
                            'payment_method' => $p['payment_method'],
                        ]);

                        phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')
                            ->updateArray(
                                'pending_withdrawals',
                                [
                                    'mts_id' => $mts_id,
                                    'payment_method' => $p['payment_method']
                                ],
                                [
                                    'id' => $p['id']
                                ]
                            );

                        return [
                            'success' => $res['success'] == true,
                            'result' => [
                                'transactionId' => $res['data']['orderid'] ?? null,
                                'url' => $res['data']['url'] ?? null,
                            ],
                        ];
                    } catch (Throwable $e) {
                        $this->disapproveAndRefundWithdrawal($p, $user, $update_array, $actor);

                        phive('UserHandler')->logAction(
                            $user,
                            "failed to process the {$p['payment_method']} withdrawal with internal ID $pend_id due to missing account details.",
                            'disapproved-withdrawal',
                            true,
                            $actor
                        );

                        $withdrawal = $this->getPending($pend_id);
                        $this->commentAml52Payout($withdrawal, 'Account details are missing; unable to proceed with auto payout.');
                        break;
                    }
                case self::REJECTED_BY_SUPPLIER:
                    $this->disapproveAndRefundWithdrawal($p, $user, $update_array, $actor);

                    phive('UserHandler')->logAction(
                        $user,
                        "failed withdrawal by {$p['payment_method']} of {$p['amount']} with internal id of $pend_id for user {$user->getUsername()}",
                        'disapproved-withdrawal',
                        true,
                        $actor
                    );
                    $this->withdrawalAttemptMonitoring($pend_id);

                    toWs(['id' => $pend_id, 'msg' => 'Cancelled', 'performedBy' => $approver_id, 'action' => 'approve'], 'pendingwithdrawals', 'na');
                    break;
                case self::SUPPLIER_CONNECTION_ERROR:
                    //TODO: Verify if we should change the stuck status in case of connection error
                    $update = [
                        'stuck' => static::STUCK_NORMAL,
                        'flushed' => 1,
                        'status' => 'pending',
                        'mts_id' => (int)$result['transaction_id'] ?? 0,
                    ];

                    phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $update, array('id' => $p['id']));

                    $return = [
                        'success' => false,
                        'code' => $result['code'],
                    ];
                    break;
                default:
                    $update = [
                        'stuck' => static::STUCK_UNKNOWN,
                        'flushed' => 1,
                        'status' => 'pending',
                        'mts_id' => (int)$result['transaction_id'] ?? 0,
                    ];

                    phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $update, array('id' => $p['id']));

                    $this->withdrawalAttemptMonitoring($pend_id);
            }

            return $return;
        } else {
            //Withdrawals that are stuck should not be retried by the auto logic since they might be executed twice at the third-party
            $update = [
                'stuck' => static::STUCK_NORMAL,
                'flushed' => 1,
                'status' => 'pending'
            ];

            // When error start with code 114 or 500 we have to update the stuck level to 9.
            // Possible errors with these codes:
            // 114. Supplier is offline.
            // 114. No response from {$this->supplierName} URL: $url.
            // 114. Application Error, Supplier: {$this->supplierName}, Error message: {$e->getMessage()}
            // 500. Application Error
            if (in_array(substr($msg, 0, 3), ['114', '500'])) {
                $update['stuck'] = static::STUCK_UNKNOWN;
            }

            if (!empty($notify_agent)) {
                $update['description'] = $p['description'] . "Before taking any action check with {$p['payment_method']} about the status of the transaction. <br>";
            }

            phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', $update, array('id' => $p['id']));

            $this->withdrawalAttemptMonitoring($pend_id);
        }

        // Pay Pending Withdraw Successful
        if ($result === true) {
            lic(
                'manipulateFraudFlag',
                [$user,$p['payment_method'], $p['scheme'], $pend_id, AssignEvent::ON_WITHDRAWAL_SUCCESS],
                null,
                null,
                $user->data['country']
            );
        }

        return $result;
    }

    public function disapproveAndRefundWithdrawal($p, $user, $update_array, $actor)
    {
        phive('SQL')->sh($p, 'user_id', 'pending_withdrawals')
            ->updateArray(
                'pending_withdrawals',
                array_merge($update_array, [
                    'status' => 'disapproved',
                    'flushed' => 1,
                    'approved_at' => phive()->hisNow(),
                    'approved_by' => $actor->getId(),
                ]),
                ['id' => $p['id']]
            );

        $this->transactUser(
            $user,
            $update_array['amount'],
            "Withdrawal Refund",
            null,
            null,
            self::CASH_TRANSACTION_NORMALREFUND,
            true,
            '',
            0,
            $p['id']
        );
    }

    public function getPendingWithdrawalEquipment(DBUser $user, int $pendingWithdrawalId): string
    {
        $pendingWithdrawal = $this->getPending($pendingWithdrawalId, $user);

        if ($this->isManualPendingWithdrawal($pendingWithdrawal)) {
            return 'pc';
        }

        $cashTransaction = $this->getTransaction($user, [
            'parent_id' => $pendingWithdrawal['id'],
            'transactiontype' => self::CASH_TRANSACTION_WITHDRAWAL,
        ]);

        if (!isset($cashTransaction['session_id'])) {
            return 'unknown';
        }

        $userEquipment = phive('SQL')->sh($user->getId())->loadAssoc("SELECT equipment FROM users_sessions WHERE id = {$cashTransaction['session_id']}");

        return $userEquipment['equipment'] ?? 'unknown';
    }


    /**
     * A typical SQL statement builder and executor to get transactions by type.
     *
     * @param int $user_id The user id.
     * @param int $type The transaction type.
     * @param string $sdate Start stamp / date.
     * @param string $edate End stamp / date.
     * @param string $cur ISO3 currency code.
     * @param false $convert Whether or not to convert to the casino currency.
     * @param string $group_by Optional group by clause.
     *
     * @return int|array If $user_id is empty we query all nodes and return an array of sums,
     * otherwise just the integer sum for that user.
     */
    function sumTransactionsByType($user_id, $type, $sdate, $edate = '', $cur = '', $convert = false, $group_by = '')
    {
        if ($convert) {
            $amount_col = 'cash_transactions.amount / currencies.multiplier';
            $join       = "LEFT JOIN currencies ON currencies.code = cash_transactions.currency";
            $select = " SUM($amount_col) AS amount, cash_transactions.user_id ";
        } else {
            $amount_col = 'amount';
            $select = empty($user_id)   ? " SUM($amount_col) "      : " SUM($amount_col) AS amount, cash_transactions.user_id ";
        }

        $sdate      = empty($sdate)     ? date('2000-01-01 00:00:00')   : $sdate;
        $edate      = empty($edate)     ? date('Y-m-d H:i:s')       : $edate;
        $type       = is_string($type)  ? $type             : "= $type ";
        $where      = empty($user_id)   ? ''                : " AND cash_transactions.user_id = $user_id ";
        if (empty($group_by)) {
            $group_by   = empty($user_id)   ? ''                    : " GROUP BY cash_transactions.user_id ";
        }
        $where_cur  = empty($cur)       ? ''                : " AND cash_transactions.currency = '$cur' ";

        $sql = "SELECT $select FROM cash_transactions
                $join
                WHERE cash_transactions.timestamp >= '$sdate'
                AND cash_transactions.timestamp <= '$edate'
                AND cash_transactions.transactiontype $type
                $where
                $where_cur
                $group_by";

        if (!empty($user_id)) {
            $amount = phive('SQL')->sh($user_id)->loadAssoc($sql);
            return empty($amount['amount']) ? 0 : $amount['amount'];
        } else {
            return phive('SQL')->shs('merge', '', null, 'cash_transactions')->loadArray($sql);
        }
    }

    /**
     * @deprecated
     *
     * Gets all transactions by type and possibly user.
     *
     * Getting transactions by type and user is fairly trivial, the complex stuff here are the joing and the grouping
     * in order to provide data for various listings in the BO for instance. Note that we aggregate all nodes if
     * no override database or valid user id is passed to this method.
     *
     * @param int $type The transaction type.
     * @param string $sdate Start stamp / date.
     * @param string $edate End stamp / date.
     * @param string $group_by A group by "action", i.e. this is not a real group statement but an indication
     * of more complex logic to run than generating a simple GROUP BY clause.
     * @param int $user_id The user id.
     * @param string $order_dir The ordering direction, ASC or DESC, the ordering column is always cash_transactions.timestamp.
     * @param SQL $db An SQL instance in case database selection needs to be orverridden.
     *
     * @return array The result array.
     */
    function getTransactionsByTypeAndUser($type, $sdate, $edate = '', $group_by = '', $user_id = '', $order_dir = 'DESC', $db = '')
    {
        $edate = empty($edate) ? date('Y-m-d H:i:s') : $edate;

        if (!empty($type)) {
            $type = is_string($type) ? $type : "= $type ";
        }

        $where_type = empty($type)      ? '' : "AND ct.transactiontype $type";
        $where_user = empty($user_id)   ? '' : "AND ct.user_id = $user_id";

        if ($group_by == 'user') {
            $group1     = ', DATE(ct.timestamp) AS day_date, SUM(ct.amount) AS amount_sum';
            $group2     = 'GROUP BY ct.user_id';
            $use_key    = 'user_id';
            $join   = 'LEFT JOIN users ON ct.user_id = users.id';
            $select = ', users.username';
        } elseif ($group_by == 'type') {
            $group1     = ', DATE(ct.timestamp) AS day_date, SUM(ct.amount) AS amount_sum';
            $group2     = 'GROUP BY ct.transactiontype';
            $use_key    = 'transactiontype';
            $join   = 'LEFT JOIN users ON ct.user_id = users.id';
            $select = ', users.username';
        } elseif ($group_by == 'day') {
            $group1     = ', DATE(ct.timestamp) AS day_date, SUM(ct.amount) AS amount_sum';
            $group2     = 'GROUP BY day_date';
            $use_key    = 'day_date';
        }

        $sql = "SELECT ct.* $select$group1 FROM cash_transactions ct
          $join
          WHERE ct.timestamp >= '$sdate'
          AND ct.timestamp <= '$edate'
          $where_user
          $where_type
          $group2
          ORDER BY ct.timestamp $order_dir";

        if (empty($db)) {
            $db = empty($user_id) ? phive('SQL')->shs('merge', '', null, 'cash_transactions') : phive('SQL')->sh($user_id, '', 'cash_transactions');
        }

        $res = $db->loadArray($sql, 'ASSOC', $use_key);

        if ($group_by == 'type user') {
            $res = phive()->sum3d(phive()->group2d($res, 'user_id', false), 'transactiontype', 'amount');
        }

        return $res;
    }

    /**
     * Gets all transactions placed by type and date range
     *
     * @param int $type The transaction type
     * @param string $date_from Start date. Format: Y-m-d / Y-m-d H:i:s
     * @param string $date_to End date. Format: Y-m-d / Y-m-d H:i:s
     * @param int|null $shard_id Shard ID
     *
     * @return array
     */
    public function getTransactionsAmountByTypeAndDate(int $type, string $date_from, string $date_to, ?int $shard_id = null): array
    {
        $db = $shard_id === null
        ? phive('SQL')->readOnly()
            : phive('SQL')->readOnly()->sh($shard_id);

        $query  = <<<SQL
            SELECT SUM(ct.amount) AS amount_sum, ct.user_id
            FROM cash_transactions ct
            WHERE ct.timestamp BETWEEN '{$date_from}' AND '{$date_to}'
                AND ct.transactiontype = {$type}
            GROUP BY ct.user_id
        SQL;

        return $db->loadArray($query, 'ASSOC', 'user_id');
    }

    /**
     * An SQL statement builder and executor that sums deposits.
     *
     * TODO henrik make $to_cur a bool.
     *
     * @param int $type The transaction type.
     * @param string $sdate Start stamp / date.
     * @param string $edate End stamp / date.
     * @param int $uid Optional user id, if passed in we query only that user's node.
     * @param string $extra Optional extra WHERE filtering.
     * @param bool|string $to_cur If not empty we convert to the casino currency.
     *
     * @return array The array of deposits.
     */
    function sumDepositsByTypeDate($type, $sdate = '', $edate = '', $uid = '', $extra = '', $to_cur = '')
    {
        $sdate = empty($sdate) ? date('Y-m') . '-01 00:00:00' : $sdate;
        $edate = empty($edate) ? date('Y-m-t') . ' 23:59:59' : $edate;
        $where_type = empty($type) ? '' : "AND d.dep_type = '$type'";
        $where_uid = empty($uid) ? '' : "AND d.user_id = $uid";
        if (empty($to_cur)) {
            $sel = 'd.amount';
        } else {
            $sel = 'd.amount / c.multiplier';
            $join = "LEFT JOIN currencies AS c ON c.code = d.currency";
        }
        $str = "
      SELECT SUM($sel) FROM deposits d
      $join
      WHERE d.timestamp >= '$sdate'
          AND d.timestamp <= '$edate' $where_type $where_uid $extra";

        if (!empty($uid)) {
            return phive('SQL')->sh($uid, '', 'deposits')->getValue($str);
        }
        return array_sum(phive()->flatten(phive('SQL')->shs('merge', '', null, 'cash_transactions')->loadArray($str)));
    }

    /**
     * Logic that determines if we are to apply a withdrawal fee or not, only one free withdrawal per day is allowed.
     *
     * @param DBUser &$user The user object.
     *
     * @return int The amount to deduct / the fee.
     */
    function getRepeatDed(&$user)
    {
        if (licSetting('disable_withdraw_repeat_fee', $user)) {
            return 0;
        }

        if (!empty($this->repeat_ded)) {
            return $this->repeat_ded;
        }
        if (empty($user)) {
            return 0;
        }
        $sdate = date('Y-m-d 00:00:00');
        $edate = phive()->hisNow();
        $count = $this->getWithdrawCount($sdate, $edate, $user->getId());
        if (!empty($count)) {
            $this->repeat_ded = mc(250, $user);
        } else {
            $this->repeat_ded = 0;
        }
        return $this->repeat_ded;
    }

    /**
     * Displays the withdrawal fee, some providers are always free.
     *
     * TODO henrik remove puggle.
     * TODO henrik optimize this, move the get repeat deduct call to the bottom and return immediately on the other tests.
     * TODO henrik remove $network_name
     *
     * @uses CasinoCashier::getRepeatDed()
     *
     * @param string $dep_type The PSP name.
     * @param DBUser $user The user object.
     * @param string $action Deposit or withdraw.
     * @param string $network_name
     * @param bool $isApi
     *
     * @return string The amount or percent to deduct display string.
     */
    function getDisplayDeduct($dep_type, $user, $action = 'withdraw', $network_name = '', $isApi = false)
    {
        $ret = 0;

        switch ($dep_type) {
            case 'wirecard':
                $ret = 0;
                break;
            case 'bank':
                $ret = '0%';
                break;
            case 'trustly':
                //$ret = '2.95%';
                $ret = 0;
                break;
            case 'neteller':
                $ret = 0;
                break;
            case 'skrill':
                $ret = 0;
                break;
            default:
                $ret = 0;
                break;
        }

        if (empty($ret) && $action == 'withdraw') {
            $ret = $this->getRepeatDed($user);
        }

        if ($this->supplierIsBank($dep_type) && $action == 'withdraw') {
            $ret = '0%';
        }

        if ($dep_type == 'puggle') {
            $reg = '5%';
        }

        //if($dep_type == 'citadel' && $action == 'deposit')
        //    return number_format(chg('CAD', $user, 150, 1) / 100, 2);

        if ($action == 'deposit') {
            $ret = ($this->getInDeductPcent(0, $dep_type, '', $user, false) * 100) . '%';
        }

        // FE related scripts need to be loaded for this to work with efEuro()
        return is_string($ret) || $isApi ? $ret : efEuro($ret, true);
    }

    /**
     * Gets the withdrawal fee for the user.
     *
     * @param int $cents The amount to withdraw.
     * @param string $dep_type The psp name.
     * @param DBUser $user The user object.
     * @param bool $round Whether to round the returned fee or not, to two decimals.
     *
     * @return float The fee.
     */
    function getOutDeduct($cents, $dep_type, $user = '', $round = true)
    {

        if (is_object($user)) {
            $free = $user->getSetting('free_withdrawals');
            if (!empty($free)) {
                return 0;
            }
        }

        $ret = 0;

        switch ($dep_type) {
            case 'wirecard':
                $ret = 0;
                break;
            case 'trustly':
                //$ret = $cents * 0.0295;
                $ret = 0;
                break;
            case 'neteller':
                $ret = 0;
                break;
            case 'skrill':
                $ret = 0;
                break;
            default:
                $ret = 0;
                break;
        }

        $repeat_fee = 0;

        // TODO make sure that they are defined as provider as bank and remove this
        // but only after Trustly is via the MTS. /Henrik
        if (
            !in_array($dep_type, ['trustly', 'instadebit', 'inpay'])
        ) {
            $repeat_fee = $this->getRepeatDed($user);
        }

        $ret = $ret > $repeat_fee ? $ret : $repeat_fee;

        return $round ? round($ret, 2) : $ret;
    }

    // TODO henrik just return 0 here.
    function getInDeductPcent($amount, $dep_type, $ctype, $user = '')
    {
        switch ($dep_type) {
            case 'wirecard':
                $pcent = phive('Config')->getValue("in-fees", "dc-fee");
                break;
            case 'paysafe':
                $pcent = phive('Config')->getValue("in-fees", "paysafe-fee");
                break;
            case 'puggle':
                $pcent = phive('Config')->getValue("in-fees", "puggle-fee");
                break;
            case 'skrill':
                return 0;
                break;
            case 'neteller':
                return 0;
            case 'trustly':
                $pcent = phive('Config')->getValue("in-fees", "trustly-fee");
            case 'euteller':
                return 0;
                break;
            default:
                return 0;
                break;
        }
        return $pcent;
    }

    // TODO henrik return 0 here.
    function getInDeduct($amount, $dep_type, $ctype, $user = '', $straight_ded = true)
    {
        if (is_object($user)) {
            $free = $user->getSetting('free_deposits');
            if (!empty($free)) {
                return 0;
            }
        }

        $pcent = $this->getInDeductPcent($amount, $dep_type, $ctype, $user);

        if (empty($pcent)) {
            return 0;
        }

        if ($straight_ded) {
            return $amount * $pcent;
        }

        return $amount - ($amount / (1 + $pcent));
    }

    public function logOneToOneRelationshipViolationAction(DBUser $user, string $psp, string $pspAccountId, int $pspAccountOwnerId): void
    {
        $message = "User with id {$user->getId()} just deposited via $psp with $pspAccountId which is owned by $pspAccountOwnerId";
        phive('UserHandler')->logAction($user, $message, 'fraud');
    }

    /**
     * Retrieves withdrawals made with a specific PSP account, such as a Neteller email.
     *
     * @param string $account The external PSP identifier (e.g., Neteller email or username).
     *
     * @return array|bool An array of withdrawals made by a different persons using the same external PSP ID,
     * or false if an error occurs.
     */
    public function getDuplicateAccountUsage(int $userId, string $psp, string $account)
    {
        return $this->getDuplicateAccountUsageViaWithdrawals($userId, $psp, $account)
            ?: $this->getDuplicateAccountUsageViaSettings($userId, $psp, $account);
    }

    /**
     * @return array|false
     */
    private function getDuplicateAccountUsageViaWithdrawals(int $userId, string $psp, string $account)
    {
        $withdrawalColumn = self::PSP_ACCOUNT_WITHDRAWAL_COLUMN_MAPPING[$psp];
        if (!$withdrawalColumn) {
            return false;
        }

        $oldIds = cu($userId)->getPreviousCurrencyUserIds();
        $whereExtra = !empty($oldIds) ? " AND user_id NOT IN (" . phive('SQL')->makeIn($oldIds) . ")" : '';

        $query = "
            SELECT user_id
            FROM pending_withdrawals
            WHERE user_id != $userId
                $whereExtra
                AND payment_method = '$psp'
                AND status = 'approved'
                AND {$withdrawalColumn} = '{$account}'
                GROUP BY user_id
        ";

        return phive('SQL')->shs()->loadArray($query);
    }

    /**
     * @return array|false
     */
    private function getDuplicateAccountUsageViaSettings(int $userId, string $psp, string $account)
    {
        $settingKey = self::USER_SETTINGS_ACCOUNT_KEY_PER_PSP[$psp];
        if (!$settingKey) {
            return false;
        }

        return phive('UserHandler')->rawSettingsWhere(
            "user_id != {$userId} AND setting = '{$settingKey}' AND value = '{$account}'"
        );
    }

    public function hasDuplicateAccountUsage(int $userId, string $psp, string $account): bool
    {
        return !empty($this->getDuplicateAccountUsage($userId, $psp, $account));
    }

    /**
     * This method calculates bank fees for deposits.
     *
     * We first check by PSP and if the calculated fee is lower than a configured minimum we return the
     * configured minimum. If it is higher we return the individual fee. The bank fees will then later
     * be deducted from affiliate commissions.
     *
     * @param int $amount The amount that was deposited.
     * @param string $dep_type The PSP network.
     * @param string $ctype The PSP source.
     * @param DBUser $user The user object.
     *
     * @return float The fee.
     */
    function getInFee($amount, $dep_type, $ctype, $user)
    {
        switch ($dep_type) {
            case 'muchbetter':
                $fee = $amount * 0.04;
                break;
            case 'cashtocode':
            case 'ctcevoucher':
                $fee = $amount * 0.075;
                break;
            case 'paypal':
                $map = [
                    'SEK' => 325,
                    'DKK' => 260,
                    'GBP' => 20
                ];
                $fee = ($amount * 0.045) + (int)$map[$user->getCurrency()];
                break;
            case 'citadel':
                $fee = chgCents('EUR', $user, 160, 1);
                break;
            case 'wirecard':
                $fee = ($amount * 0.015) + chgCents('EUR', $user, 15);
                break;
            case 'emp':
                if ($ctype == 'ideal') {
                    $fee = 85;
                } else {
                    $eu_countries = phive('Config')->valAsArray('countries', 'emp_eu');
                    $emp_fee      = in_array($user->getCountry(), $eu_countries) ? 0.061 : 0.071;
                    $fee          = ($amount * $emp_fee) + chgCents('EUR', $user, 18, 1);
                }
                break;
            case 'skrill':
                $fee = $this->getMbInFee($amount, $ctype, $user);
                break;
            case 'neteller':
                $fee = max(chgCents('USD', $user, 100), $amount * 0.029);
                break;
            case 'paymentiq':
                $piq_fee = chgCents('EUR', $user, 8);
                $map = [
                    'interac'       => ($amount * 0.0035) + chgCents('CAD', $user, 65) + ($amount * 0.0398),
                    'interaconline' => ($amount * 0.0035) + chgCents('CAD', $user, 65) + ($amount * 0.0398),
                    'vega'          => $amount * 0.048,
                    'kluwp'         => ($amount * 0.04) + chgCents('EUR', $user, 13),
                    'cleanpay'      => ($amount * 0.04) + chgCents('EUR', $user, 13),
                    'cardeye'      => ($amount * 0.04) + chgCents('EUR', $user, 13)
                ];
                $fee = $map[$ctype] + $piq_fee;
                break;
            case 'worldpay':
                $map = ['mc' => 0.01, 'visa' => 0.0075, 'maestro' => 0.009];
                $fee = $amount * $map[strtolower($ctype)];
                if (empty($fee)) {
                    $fee = ($amount * 0.0131) + chgCents('EUR', $user, 7.5);
                }
                break;
            case 'adyen':
                $map = [
                    // The fee for trusty is a blend 1.80% with a minimum of €0.35. Besides that you also have the tiered transaction fee which applies for all transactions starting at €0.075 and going down with volume
                    'trustly' => max($amount * 0.018, chgCents('EUR', $user, 35)) + chgCents('EUR', $user, 7.0),
                    'giropay' => ($amount * 0.013) + chgCents('EUR', $user, 20),
                    'directEbanking'  => $amount * 0.02
                ];
                $fee = $map[$ctype];
                // We're looking at a card deposit.
                if (empty($fee)) {
                    $map = ['mc' => 0.012, 'visa' => 0.0075, 'maestro' => 0.009];
                    $fee = $amount * $map[strtolower($ctype)];
                    if (empty($fee)) {
                        $fee = ($amount * 0.0131) + chgCents('EUR', $user, 7.5);
                    }
                }
                break;
            case 'trustly':
                $fee = chgCents('EUR', $user, $ctype == 'ideal' ? 30 : 150);
                break;
            case 'instadebit':
                $fee = max($amount * 0.05, chgCents('CAD', $user, 150));
                break;
            case 'euteller':
                $fee = 0;
                break;
            case 'paysafe':
                $fee = $amount * 0.095;
                break;
            case 'ecopayz':
                $map = array('AUD' => array(70, 0.035), 'CAD' => array(70, 0.035), 'EUR' => array(50, 0.035), 'GBP' => array(40, 0.035), 'USD' => array(70, 0.035), 'SEK' => array(440, 0.025), 'JPY' => array(6000, 0.035));
                $config = $map[$user->getCurrency()];
                $fee = ($amount * $config[1]) + $config[0];
                break;
            case 'sofort':
                $fee = ($amount * 0.02) + chgCents('EUR', $user, 20);
                break;
            case 'payground':
                $fee = $amount * 0.04;
            case 'smsvoucher':
                $fee = $amount * 0.04;
            case 'paylevo':
                $fee = $user->getAttr('country') == 'SE' ? 0.045 : 0.055;
                $fee = $amount * $fee;
                break;
            case 'puggle':
                $country = $user->getAttr('country');
                if ($country == 'SE') {
                    $percent = 0.041;
                }
                if ($country == 'FI') {
                    $percent = 0.051;
                }
                if (empty($percent)) {
                    $percent = 0.075;
                }
                $fee = $amount * $percent;
                break;
            case 'flexepin':
                $fee = $amount * 0.06;
                break;
            case 'neosurf':
                $percent = 0.059;
                // first 60 days 5%, approx launch time 5 january 2017
                if (time() < mktime(0, 0, 0, 3, 7, 2017)) {
                    $percent = 0.05;
                }
                $fee = $amount * $percent;
                break;
            case 'swish':
                // This value is in SEK but OK because we only support SEK atm anyway
                $fee = 200;
                break;
        }

        $general_in_fee = $this->getSetting('deposit_fee');
        if (!empty($general_in_fee) && $fee / $amount < $general_in_fee) {
            return $amount * $general_in_fee;
        }

        return $fee;
    }


    /**
     * This method calculates bank fees for withdrawals.
     *
     * We first check if we have a global override in the form of a setting. If we have we return that
     * configured percentage applied to the the withdrawal amount.
     *
     * @param int $amount The amount that was deposited.
     * @param string $dep_type The PSP network.
     * @param DBUser $user The user object.
     * @param string $scheme The PSP source.
     *
     * @return float The fee.
     */
    function getOutFee($amount, $dep_type, $user, $scheme = "")
    {

        $general_out_fee = $this->getSetting('withdraw_fee');
        if (!empty($general_out_fee)) {
            return $amount * $general_out_fee;
        }

        switch ($dep_type) {
            case 'muchbetter':
                return $amount * 0.04;
            case 'paypal':
                $map = [
                    'SEK' => 10000,
                    'DKK' => 10000,
                    'GBP' => 1000
                ];
                return min($amount * 0.02, $map[$user->getCurrency()] ?? 10);

            case 'inpay':
                return chgCents('EUR', $user, 1000, 1);
                break;
            case 'citadel':
                return chgCents('EUR', $user, 160, 1);
                break;
            case 'bank':
                return 0;
                break;
            case 'worldpay':
                if (!empty($scheme)) {
                    $card_type = phive('WireCard')->getCardType($scheme);
                    if (!empty($card_type)) {
                        if ($card_type == 'visa') {
                            return chgCents('EUR', $user, 125, 1);
                        } elseif ($card_type == 'mc') {
                            return chgCents('EUR', $user, 150, 1);
                        }
                    }
                }
                break;
            case 'adyen':
                return chgCents('EUR', $user, 130, 1); //cents
                break;
            case 'wirecard':
                return chgCents('EUR', $user, 180, 1); //cents
                break;
            case 'skrill':
                return $this->getMbOutFee($amount, $user, $scheme);
                break;
            case 'neteller':
                return 0;
                break;
            case 'trustly':
                return chgCents('EUR', $user, 125, 1);
                //return chgCents('EUR', $user, 50) + ($amount * 0.0295);
                break;
            case 'instadebit':
                return chgCents('EUR', $user, 100, 1);
            case 'ecopayz':
                $map = array('AUD' => array(40, 0.025), 'CAD' => array(40, 0.025), 'EUR' => array(30, 0.025), 'GBP' => array(20, 0.025), 'USD' => array(40, 0.025), 'SEK' => array(260, 0.015));
                $config = $map[$user->getCurrency()];
                return ($amount * $config[1]) + $config[0];
                break;
            case 'paymentiq':
                $piq_fee = chgCents('EUR', $user, 8);
                $map = [
                    'interac'         => chgCents('CAD', $user, 300),
                    'interaconline'   => chgCents('CAD', $user, 300),
                    'vega'            => $amount * 0.038
                ];
                return $map[$scheme] + $piq_fee;
                break;
        }
    }

    // TODO henrik, get rid of this, move it into the in fee method.
    function getMbInFee($amount, $ctype, $user)
    {
        $ctype = empty($ctype) ? 'wallet' : $ctype;
        switch ($ctype) {
            case 'sofort':
            case 'giropay':
                return ($amount * 0.013) + chgCents('EUR', $user, 10);
                break;
            case 'rapid':
                return max(($amount * 0.03) + chgCents('EUR', $user, 29), chgCents('EUR', $user, 100));
                break;
            case 'wallet':
                $rate = 0.03;
                break;
            case 'card':
                $rate = 0.03;
                break;
            default:
                $rate = 0.039;
                break;
        }
        return round(chgCents('EUR', $user, 29) + ($amount * $rate));
    }

    // TODO henrik, get rid of this, move it into the out fee method
    function getMbOutFee($amount, $user, $sub_psp = '')
    {
        if ($sub_psp == 'rapid') {
            return min(chgCents('EUR', $user, 29) + ($amount * 0.01), chgCents('EUR', $user, 3000));
        }
        return min(chgCents('EUR', $user, 50), $amount * 0.01);
    }

    /**
     * Common SQL generator plus fetcher for counting rows in either deposits or pending_withdrawals.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $table The table.
     * @param $stamp_col TODO henrik remove this
     * @param int $user_id Optional user id, if omitted all nodes will be aggregated.
     * @param string $extra Optional extra WHERE clauses.
     *
     * @return int The count.
     */
    function getCommonCount($sdate, $edate, $table, $stamp_col, $user_id = '', $extra = '')
    {
        $where_user = empty($user_id) ? '' : " AND user_id 	= $user_id ";
        $sql = "SELECT COUNT(*) FROM $table
            WHERE `$stamp_col` >= '$sdate'
            AND `$stamp_col` <= '$edate'
            $where_user
            $extra";

        if (!empty($user_id)) {
            return phive('SQL')->sh($user_id, '', $table)->getValue($sql);
        }

        return array_sum(phive()->flatten(phive('SQL')->shs('merge', '', null, $table)->loadArray($sql)));
    }

    function getDepCount(int $userId, string $type = '', string $scheme = ''): int
    {
        $whereType = '';
        $whereScheme = '';

        if (!empty($type)) {
            $whereType = " AND dep_type = '$type' ";

            if ($scheme === '') {
                $scheme = $type;
            }

            $whereScheme = $type == $scheme
                ? " AND (scheme = '$scheme' OR scheme = '') "
                : " AND scheme = '$scheme' ";

            if ($scheme == 'applepay') {
                $whereScheme = " AND scheme = '$scheme' ";
            }

            // TODO: Temporary fix for BAN-12254. Remove after BAN-11483 is completed.
            if (isPnp() && $type === 'swish') {
                $whereScheme = " AND (scheme = 'trustly' OR scheme = '') ";
            }
        }

        $startDate = phive()->getZeroDate();
        $endDate = phive()->hisNow();

        return (int)$this->getCommonCount($startDate, $endDate, 'deposits', 'timestamp', $userId, "$whereType $whereScheme");
    }

    function getPspDepCount(int $userId, string $psp): int
    {
        $pspConfig = phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp);

        $network = $psp;
        $scheme  = '';

        if (!empty($pspConfig['via']['network'])) {
            $network = $pspConfig['via']['network'];
            $scheme = $psp;
        }

        if ($network == 'applepay') {
            $network = '';
        }

        //TODO Quick fix, to be cleaned up
        if ($psp == 'swish') {
            $network = 'swish';
            $scheme  = 'trustly';
        }

        return $this->getDepCount($userId, $network, $scheme);
    }

    /**
     * Gets the withdraw count for a time period and user.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param int $user_id Optional user id, if omitted all nodes will be aggregated.
     *
     * @return int The count.
     */
    function getWithdrawCount($sdate, $edate, $user_id = '')
    {
        return $this->getCommonCount($sdate, $edate, 'pending_withdrawals', 'timestamp', $user_id, "AND status IN('pending', 'approved')");
    }

    /**
     * Gets queued transactions.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_queued_transactions Wiki description of the table.
     *
     * @param int $type The type of queued transaction.
     * @param string $where_country Optional ISO2 country code.
     *
     * @return array The array of transactions.
     */
    function getQuedByType($type, $where_country = '')
    {
        if (!empty($where_country)) {
            $where_country = " AND u.country $where_country";
        }
        $str = "SELECT qt.*, u.currency FROM queued_transactions qt LEFT JOIN users AS u ON u.id = qt.user_id WHERE qt.transactiontype = $type $where_country";
        return phive('SQL')->shs('merge', '', null, 'queued_transactions')->loadArray($str);
    }

    /**
     * Creates some stats on currently queued transactions and sends them via email, the recipient
     * can then manually scan and make sure that the numbers look more or less OK and if not
     * take action before the queue is paid out.
     *
     * @param int $type The type of queued transaction.
     * @param string $subject Email subject.
     *
     * @return null
     */
    function autoPayStatsEmail($type, $subject)
    {
        $ts     = $this->getQuedByType($type);
        $csv    = new ParseCsv\Csv();
        $csv->linefeed = "\n";
        $fname  = "auto$type.csv";
        $file   = "/tmp/$fname";
        file_put_contents($file, $csv->output(false, null, $ts, array_keys($ts[0])));
        $tos    = explode(',', phive('Config')->getValue('auto', 'email_to'));
        foreach ($tos as $to) {
            $grouped   = phive()->group2d($ts, 'currency');
            $sums      = phive()->sum3d($grouped, 'currency', 'amount', true, true);
            $body      = "Auto payment sums in whole currency units:<br/>";
            $eur_total = 0;
            foreach ($sums as $cur => $val) {
                $eur_total   += chg($cur, 'EUR', $val);
                $display_val = round($val / 100);
                $count       = count($grouped[$cur]);
                $body        .= "$cur: $display_val, count: $count<br/>";
            }
            $eur_total /= 100;
            $tot_count = count($tids);
            $body      .= "EUR total: $eur_total, total count: $tot_count <br/>";
            phive('MailHandler2')->sendRawMail($to, $subject, $body, '', true, 'Auto Payments', array('type' => 'text', 'disposition' => 'attachement', 'file' => $file, 'fname' => $fname));
        }
    }

    /**
     * This method begins by getting all queued transactions of a certain type and then passing their
     * ids to CasinoCashier::payAll(). This method is typically run in a cron job.
     *
     * @param int $type The type of queued transaction.
     * @param string $where_country Optional ISO2 country code.
     * @param bool $pay Pays out the qeueued amounts if true.
     *
     * @return null
     */
    function autoPay($type, $where_country = '', $pay = true)
    {
        $ts   = $this->getQuedByType($type, $where_country);
        $tids = phive()->arrCol($ts, 'id');

        if ($pay) {
            $this->payAll($tids);
        }
    }


    public function getPspsByType($type)
    {
        return $this->getGroupedPsps('type')[$type];
    }

    public function getGroupedPsps($group_by, $psps = null)
    {
        return phive()->group2d($psps ?? phiveApp(PspConfigServiceInterface::class)->getPspSetting(), $group_by, true, true);
    }

    /**
     * Gets people that have not deposited in a certain period.
     *
     * @param SQL $db Potential override with a certain node database, if missing we assume that sharding
     * is not in effect and will simply query the master / single and only database.
     * @param string $sdate Start date.
     * @param string $edate End date.
     *
     * @return array An array of user ids belonging to people who didn't deposit.
     */
    function getNonDepositors($db, $sdate, $edate)
    {
        $sql    = empty($db) ? phive('SQL') : $db;
        $deps   = $sql->loadCol("SELECT user_id FROM deposits WHERE `timestamp` >= '$sdate' AND `timestamp` <= '$edate' GROUP BY user_id", 'user_id');
        $users  = $sql->loadCol("SELECT id FROM users", 'id');
        return array_diff($users, $deps);
    }

    /**
     * This method is typically run in a cron job to zero out people who have not been active (ie deposited) within a certain
     * timespan and who still have money left from the sign up cash boost.
     *
     * @param SQL $db DB connection override.
     * @param string $day The date to clear.
     *
     * @return null
     */
    function resetFreeMoney($db, $day)
    {
        $sql    = empty($db) ? phive('SQL') : $db;
        $trs    = $sql->loadCol(
            "
            SELECT user_id FROM cash_transactions WHERE `timestamp` >= '$day 00:00:00' AND `timestamp` <= '$day 23:59:59' AND transactiontype = 14 AND description = '#welcome.deposit'",
            'user_id'
        );

        if (empty($trs)) {
            return;
        }

        $ndeps  = $this->getNonDepositors($sql, '2010-01-01', date('Y-m-d H:i:s'));

        if (empty($ndeps)) {
            return;
        }

        $transaction_type = 15;
        $description = "Free money and no deposit.";
        foreach (array_intersect($ndeps, $trs) as $uid) {
            $balance = $sql->getValue("SELECT cash_balance FROM users WHERE id = $uid");
            $user = cu($uid);
            if ($balance <= 0) {
                continue;
            }

            $t_id = $this->insertTransaction($uid, -$balance, $transaction_type, $description);
            $sql->query("UPDATE users SET cash_balance = 0 WHERE id = $uid");

            $cash_transaction_history_data = [
                'user_id'          => (int) $uid,
                'transaction_id'   => (int) $t_id,
                'amount'           => -(int) $balance,
                'currency'         => $user->getCurrency(),
                'transaction_type' => $transaction_type,
                'parent_id'        => 0,
                'description'      => $description,
                'event_timestamp'  => time(),
            ];

            try {
                $history_message = new CashTransactionHistoryMessage($cash_transaction_history_data);

                /** @uses \Licensed::addRecordToHistory() */
                lic(
                    'addRecordToHistory',
                    [
                        'cash_transaction',
                        $history_message
                    ],
                    $user
                );
            } catch (InvalidMessageDataException $e) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error(
                        $e->getMessage(),
                        [
                            'topic'             => 'cash_transaction',
                            'validation_errors' => $e->getErrors(),
                            'trace'             => $e->getTrace(),
                            'data'              => $cash_transaction_history_data
                        ]
                    );
            }
        }
    }

    /**
     * Gets a deposit by the ext_id column (ie the PSP unique id).
     *
     * @param string $ext_id The PSP id.
     * @param string $type The PSP.
     *
     * @return array The deposit in the form of an array.
     */
    function getDepByExt($ext_id, $type)
    {
        return phive("SQL")->shs('merge', '', null, 'deposits')->loadAssoc("SELECT * FROM deposits WHERE ext_id = '$ext_id' AND dep_type = '$type'");
    }

    /**
     * Can be used if we simply want the total deposit count for a user.
     *
     * @see CasinoCashier::getDepCount() Is a more advanced analogue to this method.
     *
     * @param int $user_id The user id.
     * @param string $type Optional PSP.
     * @param string $extra Optional where clauses.
     *
     * @return int The count.
     */
    function getDepositCount($user_id, $type = '', $extra = '')
    {
        $where_type = empty($type) ? '' : "AND dep_type = '$type'";
        $sql = "SELECT COUNT(*) FROM deposits WHERE user_id = $user_id $where_type $extra";
        return phive('SQL')->sh($user_id)->getValue($sql);
    }

    public function getApprovedDepositsCount(int $userId): int
    {
        return (int) $this->getDepositCount($userId, '', " AND status = 'approved'");
    }

    public function hasOnlyOneApprovedDeposit(int $userId): bool
    {
        return $this->getApprovedDepositsCount($userId) === 1;
    }

    /**
     * Gets the FIFO option as per:
     * 1. If the user has a BO assigned setting for the FIFO PSP we immediately return it.
     * 2. If not we loop the deposits as per the fifo_months setting for how many months we should go back in time.
     * 3. If there are no deposits or no deposits via a PSP that supports withdrawals we simply return the first
     * PSP with do_kyc wet to false.
     *
     * @param DBUser $u_obj The user to work with.
     * @param array $psps The PSP config array to pass in.
     * @param array $cards An array containing one or more sub arrays with card info.
     *
     * @return array|null The PSP that is the FIFO or null if none could be found.
     */
    public function getFifo($u_obj, $psps, $cards = [])
    {

        if ($u_obj->hasSetting('fifo_psp')) {
            return [$u_obj->getSetting('fifo_psp')];
        }

        // Do we have a FIFO reset? If not we use the configured FIFO months setting.
        $sstamp = $u_obj->hasSetting('fifo_date') ? $u_obj->getSetting('fifo_date') : phive()->hisMod("-{$this->getSetting('fifo_months')} month");

        if (empty($this->cache['deposits'])) {
            $this->cache['deposits'] = $this->getDeposits(
                $sstamp,            // sdate
                phive()->hisNow(),  // edate
                $u_obj->getId(),    // user_id
                '',                 // type
                false,              // group_by
                '',                 // limit
                "AND status = 'approved'", // where_extra
                '',                 // cur
                '',                 // limit_cnt
                false,              // join_external
                "id ASC"            // order_by
            );
        }

        $depositsListToObfuscate = $this->cache['deposits'] ? array_slice($this->cache['deposits'], 0, 5, true) : "No Deposit record available";

        phive('Logger')->getLogger('payments')->debug(
            'FIFO Start date and Deposit list:',
            [
                'user' => $u_obj->userId,
                'Current_FIFO_start_stemp' => $sstamp,
                'deposits_list' => is_array($depositsListToObfuscate) ? $this->obfuscateArray($depositsListToObfuscate) : $depositsListToObfuscate
            ]
        );

        foreach ($this->cache['deposits'] as $d) {
            foreach ($psps as $psp => $config) {
                if ($psp == 'applepay' && $d['scheme'] != 'applepay') {
                    continue;
                }

                // Any deposit without scheme is captured here, such as Skrill wallet.
                if (empty($d['scheme']) && $d['dep_type'] == $psp) {
                    return [$psp];
                }

                // For instance ccard or applepay type.
                if ($config['be_type'] == 'ccard' && in_array($d['dep_type'], (new Mts())->getOutCcSuppliers())) {
                    foreach ($cards as $card) {
                        // Card hashes do not match so we continue with the next card.
                        if ($d['card_hash'] != $card['card_num']) {
                            continue;
                        }

                        if ($d['scheme'] == $card['sub_supplier']) {
                            // Here we have eg stored applepay in the scheme column of the deposit and the sub_supplier column of the recurring.
                            if (in_array($d['scheme'], ['applepay'])) {
                                return [$card['sub_supplier'], $d];
                            }
                            return [$psp, $d];
                        }

                        // Card sub_supplier is applepay (network: worldpay) or bambora (network: paymentiq).
                        // Card card_scheme is visa etc.

                        // We can match the schemes (visa, mc etc), and we have already matched card hashes, so not much more to do.
                        if (!empty($card['card_scheme']) && $d['scheme'] == $card['card_scheme']) {
                            return [$psp, $d];
                        }
                    }
                }

                $psp = $config['option_of'] ?? $psp;
                if (!empty($d['scheme']) && $d['scheme'] == $psp) {
                    return [$psp];
                }

                // Some PSPs might have the main supplier name also in the scheme column, we catch them here.
                if ($d['dep_type'] == $psp && $d['scheme'] == $psp) {
                    return [$psp];
                }
            }
        }

        foreach ($psps as $psp => $config) {
            if ($config['do_kyc'] === false) {
                return [$psp];
            }
        }

        return null;
    }

    /**
     * Clears out all expired fifo date overrides
     *
     * @param string $threshold Optional override date that will be used instead of configured fifo month offset.
     *
     * @return null
     */
    public function clearUsersFifoDates($thold = null)
    {
        $thold    = $thold ?? phive()->hisMod("-{$this->getSetting('fifo_months')} month");
        $settings = phive('UserHandler')->rawSettingsWhere("setting = 'fifo_date'");
        foreach ($settings as $setting) {
            // If older than the configured date we remove it.
            if ($setting['value'] < $thold) {
                // We delete via the user object in order to get logging.
                cu($setting['user_id'])->deleteSetting('fifo_date');
            }
        }
    }

    /**
     * An alias for CasinoCashier::getDepCommon() with the $table argument hardcoded to deposits.
     *
     * @uses CasinoCashier::getDepCommon()
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id User id.
     * @param string $type The PSP name.
     * @param string $group_by Group by "action" which determines what is selected and the actual GROUP BY clause.
     * @param string $limit LIMIT clause.
     * @param string $where_extra Extra WHERE clauses.
     * @param string $cur ISO2 currency code.
     * @param int $limit_cnt If $limit is omitted this will form the length part in a LIMIT offset, length clause.
     * @param bool $join_external TODO henrik remove this, the table isn't even used
     * @param string $order_by Optional ORDER BY clause.
     *
     * @return array The result array.
     */
    function getDeposits($sdate, $edate, $user_id = '', $type = '', $group_by = false, $limit = '', $where_extra = '', $cur = '', $limit_cnt = '', $join_external = false, $order_by = "`timestamp` DESC")
    {
        return $this->getDepCommon('deposits', $sdate, $edate, $user_id, $type, $group_by, $limit, $where_extra, $cur, $limit_cnt, $join_external, $order_by);
    }

    /**
     * Gets a deposit by id.
     *
     * @param int $dep_id The deposit id.
     * @param int $uid Optional user id, if passed in it will be used to select the correct node.
     *
     * @return array The deposit.
     */
    function getDeposit($dep_id, $uid = '')
    {
        if (!empty($uid)) {
            return phive('SQL')->sh($uid)->loadAssoc("", "deposits", array("id" => $dep_id, 'user_id' => $uid));
        }
        return phive('SQL')->shs('merge', '', null, 'deposits')->loadAssoc("", "deposits", array("id" => $dep_id));
    }

    public function getUserDepositByMtsTransactionId($userId, $mtsTransactionId)
    {
        return phive('SQL')->readOnly()->sh($userId)
            ->loadAssoc(
                "",
                "deposits",
                ["mts_id" => $mtsTransactionId]
            );
    }

    /**
     * Reverts a successful deposit, typically being run a part of a chargeback process.
     *
     * @param DBUser $user The user object.
     * @param array $data Data sent from the MTS.
     * @param bool $chargeback Whether or not to try and claw back money and block the player etc in addition to just changing the status of the deposit.
     * @param string $psp The PSP name, needed in case we want to do the full chargeback.
     *
     * @return bool Returns true if deposit successfully reverted, and false if chargeback is not allowed due to
     * insufficient balance or deposit already disapproved.
     */
    function revertDeposit($user, $data, $chargeback = true, $psp = '', bool $insufficientBalanceAllowed = true, int $userBlockStatus = 9)
    {
        $deposit = phive('SQL')->sh($user, 'id', 'deposits')->loadAssoc("", "deposits", array("mts_id" => $data['transaction_id']));
        if (!$deposit || ($deposit['status'] == 'disapproved')) {
            return false;
        }

        $deposit['status'] = 'disapproved';
        phive('SQL')->sh($user, 'id', 'deposits')->save('deposits', $deposit);

        if ($chargeback) {
            $res = $this->chargeback(
                $user,
                $data['amount'],
                "{$data['supplier']} chargeback / cancel for deposit with supplier id " . $data['reference_id'],
                true,
                $psp,
                $insufficientBalanceAllowed,
                $userBlockStatus
            );

            if (!$res) {
                return false;
            }
        }

        return true;
    }

    /**
     * Wrapper around running an SQL statement that updates an arbitrary column in a deposit with an arbitrary value.
     *
     * @param array $deposit The deposit data.
     * @param string $ukey The key / column to update.
     * @param string $uvalue The value to update with.
     *
     * @return null
     */
    function updateDeposit($deposit, $ukey, $uvalue)
    {
        phive('SQL')->sh($deposit, 'user_id', 'deposits')->query("UPDATE deposits SET $ukey = '$uvalue' WHERE id = {$deposit['id']}");
    }

    /**
     * Sets the status of a deposit to disapproved.
     *
     * @param array $deposit The deposit.
     *
     * @return null
     */
    function cancelDeposit($deposit)
    {
        $this->updateDeposit($deposit, 'status', 'disapproved');
    }

    /**
     * An alias for CasinoCashier::getDepCommon() with the $table argument hardcoded to failed deposits.
     *
     * @uses CasinoCashier::getDepCommon()
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id User id.
     * @param string $type The PSP name.
     * @param string $group_by Group by "action" which determines what is selected and the actual GROUP BY clause.
     * @param string $limit LIMIT clause.
     * @param string $where_extra Extra WHERE clauses.
     * @param string $cur ISO2 currency code.
     * @param int $limit_cnt If $limit is omitted this will form the length part in a LIMIT offset, length clause.
     * @param bool $join_external TODO henrik remove this, the table isn't even used
     * @param string $order_by Optional ORDER BY clause.
     *
     * @return array The result array.
     */
    function getFailedDeposits($sdate, $edate, $user_id = '', $type = '', $group_by = false, $limit = '', $where_extra = '', $cur = '', $limit_cnt = '', $join_external = false)
    {
        return $this->getDepCommon('failed_deposits', $sdate, $edate, $user_id, $type, $group_by, $limit, $where_extra, $cur, $limit_cnt, $join_external);
    }

    /**
     * Gets total deposit sum by user and status(es).
     *
     * @param int $uid The user id.
     * @param string $status The status(es)
     * @param string $extra Optional WHERE filtering.
     *
     * @return int The sum.
     */
    function getUserDepositSum($uid, $status = "'approved'", $extra = '')
    {
        $sql = "SELECT SUM(amount) AS amount FROM deposits WHERE user_id = $uid AND status IN($status) $extra";
        return phive('SQL')->sh($uid, '', 'deposits')->getValue($sql);
    }

    /**
     * Simple SQL builder and DB getter for getting deposits for a user with custom ordering, grouping, limiting and filtering.
     *
     * @uses SQL::loadArray()
     *
     * @param int $uid The user id.
     * @param string $order_by ORDER BY clause.
     * @param string $where_extra Extra WHERE filtering.
     * @param string $limit LIMIT clause.
     * @param string $group_by GROUP BY clause.
     *
     * @return array An array of deposits.
     */
    function getUserDeposits($uid, $order_by = '', $where_extra = '', $limit = '', $group_by = '')
    {
        $uid = uid($uid);
        if (!empty($where_extra)) {
            $where_extra = " AND $where_extra ";
        }

        if (!empty($limit)) {
            $limit = "LIMIT $limit";
        }

        if (!empty($group_by)) {
            $group_by = "GROUP BY $group_by";
        }

        return phive('SQL')->sh($uid)->loadArray("SELECT * FROM deposits WHERE user_id = $uid $where_extra $group_by $order_by $limit");
    }

    /**
     * Gets deposits to display for "repeat".
     *
     * This repeat functionality is not to be confused with real recurring / repeat / oneclick deposits
     * which only need a prior deposit id sent to the PSP in order to repeat the deposit. What we're looking at here is just a normal deposit flow
     * that uses the amount of the deposit to repeat, nothing more. This functionality is mostly used to promote certain PSPs over others by
     * giving the illusion that this so called repeat deposit will be quicker and more painless than a normal deposit.
     *
     * @param DBUser $u_obj User object.
     * @param array $dep_types An array of PSPs whose deposits we want to look for.
     * @param array $not_schemes An array of sources / schemes we do not want to be a part of the result.
     * @param int $limit The limit, note that this is a limit we apply in PHP after first querying for 20 rows which is just an arbitrarily chosen
     * number that should return more than enough rows that we then subsequently prune further.
     *
     * @return array The deposits.
     */
    public function getDepositsForRepeat(DBUser $u_obj, array $dep_types = [], array $not_schemes = [], int $limit = 0): array
    {
        if (empty($dep_types)) {
            return [];
        }

        $sql = "SELECT *, CONCAT(scheme, amount) AS scheme_amount FROM deposits WHERE user_id = {$u_obj->getId()}";
        $sql .= " AND dep_type IN({$this->db->makeIn($dep_types)})";
        if ($not_schemes) {
            $sql .= " AND scheme NOT IN({$this->db->makeIn($not_schemes)})";
        }

        if (in_array('trustly', $dep_types, true)) {
            $sql .= " AND (dep_type != 'trustly' OR (scheme != '' AND card_hash != ''))";
        }

        $sql .= " ORDER BY id DESC LIMIT 20";

        $res = phive('SQL')->sh($u_obj)->loadArray($sql, 'ASSOC', 'scheme_amount');

        return $limit ? array_slice($res, 0, $limit) : $res;
    }

    /**
     * Loops the cashier config and tries to find a match based on display name.
     *
     * @link https://wiki.videoslots.com/index.php?title=Videoslots_Cashier For general info around the config.
     *
     * @param string $display_name The display name.
     *
     * @return array|null Array the config entry if found, null otherwise.
     */
    function getPspSettingFromDisplayName($display_name)
    {
        foreach (phiveApp(PspConfigServiceInterface::class)->getPspSetting() as $psp => $setting) {
            if (strtolower($display_name) == strtolower($setting['display_name'])) {
                return [$psp, $setting];
            }
        }
        return null;
    }

    /**
     * Return the sum of all deposits and withdrawals for a user in the requested status
     * Ex. ['deposit' => 123456 , 'withdrawal' => 123132];
     *
     * @param int $uid Tthe user id.
     * @param string $status The status.
     * @return array The array of sums.
     */
    public function getUserDepositAndWithdrawalSum($uid, $status = 'approved')
    {
        $sql = "
        SELECT
	        'deposit' as type, IFNULL(sum(amount),0) as sum
        FROM
            deposits
        WHERE
            deposits.user_id = $uid
            AND deposits.status = '$status'
        UNION
        SELECT
            'withdrawal' as type, IFNULL(sum(amount),0) as sum
        FROM
            pending_withdrawals
        WHERE
            pending_withdrawals.user_id = $uid
            AND pending_withdrawals.status = '$status'
        ";
        $data = phive('SQL')->sh($uid)->loadArray($sql);
        $res = [];
        foreach ($data as $row) {
            $res[$row['type']] = $row['sum'];
        }
        return $res;
    }

    /**
     * SQL builder and getter with the following "group by" actions:
     * - day: takes the timestamp column and runs DATE() on it and sums amount and real_cost per day.
     * - total: simply sums the numbers up, no grouping in the SQL takes place.
     * - total_cc: like total but filters out everything except the CC providers.
     * - user: SQL groups by user and sums per user.
     *
     * TODO henrik create migration to remove external_transactions?
     * TODO henrik remove method, day <- not used.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_deposits The wiki docs for the deposits table.
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id User id.
     * @param string $type The PSP name.
     * @param string $group_by Group by "action" which determines what is selected and the actual GROUP BY clause.
     * @param string $limit LIMIT clause.
     * @param string $where_extra Extra WHERE clauses.
     * @param string $cur ISO2 currency code.
     * @param int $limit_cnt If $limit is omitted this will form the length part in a LIMIT offset, length clause.
     * @param bool $join_external TODO henrik remove this, the table isn't even used
     * @param string $order_by Optional ORDER BY clause.
     *
     * @return array The result array.
     */
    function getDepCommon($table, $sdate, $edate, $user_id = '', $type = '', $group_by = false, $limit = '', $where_extra = '', $cur = '', $limit_cnt = '', $external_transactions = false, $order_by = "`timestamp` DESC")
    {
        if (empty($sdate)) {
            $sdate = '2000-01-01';
        }

        if (empty($edate)) {
            $edate = phive()->hisNow();
        }

        $cols = '*';

        if ($group_by == 'method, day') {
            $ret = array();
            foreach (array('neteller', 'skrill', 'wirecard', 'euteller') as $type) {
                $ret[$type] = $this->getDeposits($sdate, $edate, $user_id, $type, 'day');
            }
            return $ret;
        }

        if ($group_by == 'day') {
            $group1     = ', DATE(timestamp) AS day_date, SUM(amount) AS amount_sum, SUM(real_cost) AS cost_sum';
            $group2     = 'GROUP BY day_date';
            $group_by   = 'day_date';
        }

        if ($group_by == 'total' || $group_by == 'total_cc') {
            $group1     = 'SUM(amount) AS amount_sum, SUM(real_cost) AS cost_sum, SUM(deducted_amount) AS deduct_sum';
            $cols       = '';
        }

        if ($group_by == 'user') {
            $group1     = 'user_id, SUM(amount) AS amount_sum, SUM(real_cost) AS cost_sum, SUM(deducted_amount) AS deduct_sum';
            $group2     = 'GROUP BY user_id';
            $group_by = 'user_id';
            $cols   = '';
        }

        if ($group_by == 'total_cc' || $type == 'card') {
            $main_ccs = (new Mts())->getMainCcSuppliers();
            $where_type = " AND dep_type IN ({$this->db->makeIn($main_ccs)}) AND scheme IN('visa','mc', 'jcb', 'maestro', 'amex', 'card')";
        } else {
            if (is_array($type)) {
                $where_type = " AND dep_type IN(" . phive('SQL')->makeIn($type) . ")";
            } else {
                $where_type = empty($type) ? '' : " AND dep_type = '$type' ";
            }
        }

        $where_user   = empty($user_id) ? '' : " AND user_id = $user_id ";
        if (empty($where_user)) {
            $where_cur  = empty($cur) ? '' : "AND currency = '$cur'";
        }

        if (!empty($limit_cnt) && empty($limit)) {
            $limit = " LIMIT 0, $limit_cnt";
        }

        if ($external_transactions) {
            $join_external = " LEFT JOIN external_transactions e ON t.ext_id = e.ext_id ";
            $external_cols = ", e.ext_id AS external_id, e.transaction_data AS transaction_data";
        }

        $sql = "SELECT {$cols}$group1, timestamp AS exec_stamp $external_cols FROM $table t
          $join_external
          WHERE `timestamp` >= '$sdate'
          AND `timestamp` <= '$edate'
          $where_type
          $where_user
          $where_extra
          $where_cur
          $group2
          ORDER BY $order_by
          $limit";

        if (empty($this->is_shard)) {
            $db = empty($user_id) ? phive('SQL')->shs('merge', 'timestamp', 'desc', $table) : phive('SQL')->sh($user_id, '', $table);
        } else {
            $db = $this->db;
        }
        //TODO test this, especially the total scenario
        return $group_by == 'total' ||  $group_by == 'total_cc' ? phive()->sum2d($db->loadArray($sql)) : $db->loadArray($sql, 'ASSOC', $group_by);
    }

    // TODO henrik is this even used anymore? If yes rename it to get rid of the MGA / LGA association, if not remove pending_withdrawals.php
    function lgaWithdrawCheck($uid, $estamp, $currency)
    {
        $s = cuSetting('verified', $uid);
        if (empty($s)) {
            return false;
        }
        $sstamp = $s['created_at'];
        $diff = (strtotime($estamp) - strtotime($sstamp));
        if ($diff < 15811200) {
            return false;
        }
        $res = $this->getPendingGroup($sstamp, $estamp, "IN('approved', 'pending')", '', $uid, 'total');
        if (mc($res['amount_sum'], $currency, 'div') >= 233000) {
            return $res;
        }
        return false;
    }


    // TODO henrik is this even used anymore? If yes rename it to get rid of the MGA / LGA association if not remove lga_check.php
    function lgaCheckList()
    {
        $str = "
          SELECT sum( pw.amount ) AS amount_sum, us.created_at, pw.currency, pw.user_id, c.mod, u.username, pw.id AS id, us.value
          FROM pending_withdrawals pw
          LEFT JOIN users_settings AS us ON us.user_id = pw.user_id AND setting = 'verified'
          LEFT JOIN currencies AS c ON c.code = pw.currency
          LEFT JOIN users AS u ON pw.user_id = u.id
          WHERE pw.timestamp >= us.created_at AND pw.status IN ('approved', 'pending')
          GROUP BY pw.user_id
          HAVING (amount_sum / c.mod) > 233000 AND us.value LIKE '1'
          ORDER BY us.created_at ASC";

        return phive('SQL')->shs('merge', 'created_at', 'asc', 'pending_withdrawals')->loadArray($str);
    }

    /**
     * SQL builder and getter for pending_withdrawals with the following "group by" actions:
     * - day: takes the timestamp column and runs DATE() on it and sums per day / date.
     * - total: simply sums the numbers up, no grouping in the SQL takes place.
     * - user: SQL groups by user and sums per user.
     *
     * TODO henrik remove method, day <- not used.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_pending_withdrawals The wiki docs for the pending_withdrawals table.
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param string $status Withdrawal status to filter on.
     * @param string $method The PSP name.
     * @param int $user_id User id.
     * @param string $group_by Group by "action" which determines what is selected and the actual GROUP BY clause.
     * @param string $where_extra Extra WHERE clauses.
     * @param string $date_col "timestamp" or "approved_at".
     * @param string $cur ISO2 currency code.
     * @param string $limit LIMIT clause.
     * @param string $asc_desc Sort order, ASC or DESC.
     *
     * @return array The result array.
     */
    function getPendingGroup($sdate, $edate, $status = '', $method = '', $user_id = '', $group_by = false, $where_extra = '', $date_col = 'timestamp', $cur = '', $limit = '', $asc_desc = 'ASC')
    {
        $cols = '*';
        if ($group_by == 'method, day') {
            $ret = array();
            foreach (array('neteller', 'skrill', 'wirecard', 'bank', 'trustly') as $method) {
                $ret[$method] = $this->getPendingGroup($sdate, $edate, $status, $method, $user_id, 'day', $where_extra, $date_col, $cur, $limit);
            }
            return $ret;
        }

        if ($group_by == 'day') {
            $group1     = ', DATE(timestamp) AS day_date, SUM(amount) AS amount_sum, SUM(deducted_amount) AS deduct_sum, SUM(real_cost) AS cost_sum';
            $group2     = 'GROUP BY day_date';
            $group_by   = 'day_date';
        }

        if ($group_by == 'total') {
            $group1 = 'SUM(amount) AS amount_sum, SUM(real_cost) AS cost_sum, SUM(deducted_amount) AS deduct_sum';
            $cols   = '';
        }

        if ($group_by == 'user') {
            $group1     = 'user_id, SUM(amount) AS amount_sum, SUM(real_cost) AS cost_sum, SUM(deducted_amount) AS deduct_sum';
            $group2     = 'GROUP BY user_id';
            $group_by   = 'user_id';
            $cols       = '';
        }

        $where_status   = empty($status)    ? '' : " AND `status` $status ";
        /*user_id is user input here*/
        $user_id = intval($user_id);
        $where_user     = empty($user_id)   ? '' : " AND user_id = $user_id ";
        $where_method   = empty($method)    ? '' : " AND payment_method = '$method' ";
        if (empty($where_user)) {
            $where_cur  = empty($cur) ? '' : "AND currency = '$cur'";
        }

        if (!empty($limit)) {
            $limit = " LIMIT 0, $limit";
        }

        $sql = "SELECT {$cols}$group1, approved_by, approved_at AS exec_stamp FROM pending_withdrawals
            WHERE `$date_col` >= '$sdate'
            AND `$date_col` <= '$edate'
            $where_status
            $where_user
            $where_method
            $where_extra
            $where_cur
            $group2
            ORDER BY `timestamp` $asc_desc
            $limit";

        if (empty($this->is_shard)) {
            $db = empty($user_id) ? phive('SQL')->shs('merge', 'timestamp', $asc_desc, 'pending_withdrawals') : phive('SQL')->sh($user_id, '', 'pending_withdrawals');
        } else {
            $db = $this->db;
        }

        //TODO test this, especially the total scenario
        return $group_by == 'total' ? phive()->sum2d($db->loadArray($sql)) : $db->loadArray($sql, 'ASSOC', $group_by);
    }

    /**
     * Gets row count from deposits or pending_withdrawals, grouped by user id and PSP.
     *
     * @param string $table The table to use.
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id The user id, if not empty it will be used for shard selection, if empty we aggregate all shards.
     * @param string $cur The ISO2 currency code.
     *
     * @return array The array of counts.
     */
    function getCountCommon($table, $sdate, $edate, $user_id = '', $cur = '')
    {
        $field      = $table == 'pending_withdrawals' ? 'payment_method' : 'dep_type';
        $where_user = empty($user_id) ? '' : " AND user_id = $user_id ";
        $where_cur  = empty($cur) ? '' : " AND currency = '$cur' ";
        $sql = "SELECT user_id, COUNT(*) AS count, $field
            FROM $table
            WHERE status = 'approved'
            AND `timestamp` >= '$sdate'
            AND `timestamp` <= '$edate'
            $where_user
            $where_cur
            GROUP BY user_id, $field";

        // TODO test this
        $db = empty($user_id) ? phive('SQL')->sh($user_id, '', $table) : phive('SQL')->shs('merge', '', null, $table);
        $tmp = phive()->group2d($db->loadArray($sql), $field);

        $rarr = array();
        foreach ($tmp as $method => $data) {
            $rarr[$method] = array('total' => phive()->sum2d($data, 'count'), 'unique' => count($data));
        }

        return $rarr;
    }

    /**
     * Gets row count from pending_withdrawals, grouped by user id and PSP.
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id The user id, if not empty it will be used for shard selection, if empty we aggregate all shards.
     * @param string $cur The ISO2 currency code.
     *
     * @return array The array of counts.
     */
    function countOutByType($sdate, $edate, $user_id = '', $cur = '')
    {
        return $this->getCountCommon('pending_withdrawals', $sdate, $edate, $user_id, $cur);
    }


    /**
     * Gets row count from deposits, grouped by user id and PSP.
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id The user id, if not empty it will be used for shard selection, if empty we aggregate all shards.
     * @param string $cur The ISO2 currency code.
     *
     * @return array The array of counts.
     */
    function countInByType($sdate, $edate, $user_id = '', $cur = '')
    {
        return $this->getCountCommon('deposits', $sdate, $edate, $user_id, $cur);
    }

    /**
     * Vraps misc. methods to return an array with transfer information.
     *
     * @param string $type The PSP name.
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param int $user_id The user id, if not empty it will be used for shard selection, if empty we aggregate all shards.
     * @param string $cur The ISO2 currency code.
     *
     * @return array The result array.
     */
    function getStatsForPaymentType($type, $sdate, $edate, $user_id = '', $cur = '')
    {
        $wstats     = $this->getPendingGroup($sdate, $edate, " = 'approved' ", $type, $user_id, 'total', '', 'approved_at', $cur);
        $dstats     = $this->getDeposits($sdate, $edate, $user_id, $type, 'total', '', '', $cur);
        $no_ins     = $this->countInByType($sdate, $edate, $user_id, $cur);
        $no_outs    = $this->countOutByType($sdate, $edate, $user_id, $cur);
        $total      = $dstats['amount_sum'] + $wstats['amount_sum'];
        $fees       = $dstats['cost_sum'] + $wstats['cost_sum'];

        $rarr = array();

        $rarr['Deposits']       = $dstats['amount_sum'];
        $rarr['# Deposits']         = $no_ins[$type]['total'];
        $rarr['# Unique Deposits']  = $no_ins[$type]['unique'];
        $rarr['Withdrawals']        = $wstats['amount_sum'];
        $rarr['# Withdrawals']      = $no_outs[$type]['total'];
        $rarr['# Unique Withdrawals']   = $no_outs[$type]['unique'];
        $rarr['Total transactions']     = $total;
        $rarr['Deducted Fee']       = $wstats['deduct_sum'] + $dstats['deduct_sum'];
        $rarr['Transfer Fees']      = $fees;
        if ($type == 'wirecard') {
            $rarr['Chargebacks']    = abs($this->sumTransactionsByType($user_id, 9, $sdate, $edate, $cur));
        }
        $rarr['Effective %']        = ($fees / $total) * 100;
        $rarr['Balance on account'] = $dstats['amount_sum'] - $wstats['amount_sum'];

        return $rarr;
    }

    function getTransactionsByTypeDay($type, $sdate, $edate = '', $date_keys = false, $cur = '')
    {
        $edate  = empty($edate) ? phive()->hisNow() : $edate;
        $type   = is_string($type) ? $type : "= $type ";
        $where_cur  = empty($cur) ? '' : "AND currency = '$cur'";
        $sql    = "SELECT SUM(amount) AS amount, DATE(timestamp) AS timestamp FROM cash_transactions
        WHERE `timestamp` >= '$sdate'
        AND `timestamp` <= '$edate'
        AND transactiontype $type
        $where_cur
        GROUP BY DATE(`timestamp`)
        ORDER BY timestamp";

        $db = phive('SQL')->readOnly()->shs(['action' => 'sum'], 'timestamp', 'asc', 'cash_transactions');

        if ($date_keys) {
            return $db->loadArray($sql, 'ASSOC', 'timestamp');
        }
        return $db->loadArray($sql);
    }

    /**
     * Gets queued_transactions with users and bonus_entries joined, aggregates all nodes.
     *
     * @return array The result rows.
     */
    function getQueue()
    {
        return phive('SQL')->shs('merge', '', null, 'queued_transactions')->loadArray("
            SELECT qt.*, u.username, be.balance FROM queued_transactions qt
            LEFT JOIN users AS u ON u.id = qt.user_id
            LEFT JOIN bonus_entries AS be ON be.id = qt.bonus_entry");
    }

    /**
     * Gets a transaction by id, will query all nodes in order to find the transaction so should be used
     * ONLY in a context where we don't have a user id.
     *
     * @param int $id The transaction id.
     * @param string $table The table to query.
     *
     * @return array The row.
     */
    function getTrans($id, $table = "queued_transactions")
    {
        $id = intval($id);
        return phive('SQL')->shs('merge', '', null, $table)->loadAssoc("SELECT * FROM $table WHERE id = " . $id);
    }


    /**
     * Gets a transaction by id, will query all nodes in order to find the transaction so should be used
     * ONLY in a context where we don't have a user id.
     *
     * @param int $id The transaction id.
     * @param string $table The table to query.
     *
     * @return bool Whether or not the query was successful.
     */
    function deleteTrans($id, $table = "queued_transactions")
    {
        $id = intval($id);
        return phive('SQL')->shs('merge', '', null, $table)->query("DELETE FROM $table WHERE id = " . $id);
    }

    /**
     * Gets cash_transactions for a user.
     *
     * @param mixed $user User identifier, ie empty we go for the currently logged in user.
     * @param mixed $type Transaction type information, if array it will be converted to an IN() statement.
     * @param int $limit Will be used in a LMIT statement as the length part, offset will be 0.
     * @param array $dates An array with start date / stamp in the first position and end date / stamp in the second.
     *
     * @return array An array with transactions.
     */
    function getUserTransactions($user = '', $type = '', $limit = '', $dates = array(), $order_by = "timestamp", $offset = 0)
    {
        $user_id = ud($user)['id'];

        $offset = (int) $offset;

        $where_dates = empty($dates) ? '' : "AND `timestamp` >= '{$dates[0]}' AND `timestamp` <= '{$dates[1]}' ";
        $where_type = '';

        if (!empty($type)) {
            if (is_numeric($type)) {
                $where_type = " AND transactiontype = $type ";
            }
            if (is_string($type)) {
                $where_type = $type;
            }
            if (is_array($type)) {
                $where_type = " AND transactiontype IN(" . phive('SQL')->makeIn($type) . ") ";
            }
        }
        $sql = "SELECT * FROM cash_transactions
            WHERE cash_transactions.user_id = $user_id $where_type $where_dates
            ORDER BY `$order_by` DESC " . phive('SQL')->getLimit($limit, $offset);

        return phive('SQL')->readOnly()->sh($user_id, '', 'cash_transactions')->loadArray($sql);
    }

    /**
     * Returns an array with the type as the key and the description as the value of all possible
     * transaction types.
     *
     * Ex: usage $cashier->getTransactionTypes()[$type] in order to get the description for a specific type.
     *
     * @return array The array of type => descriptions.
     */
    function getTransactionTypes()
    {
        return [
            '' => 'All',
            3 => 'Deposit',
            4 => 'Cash balance bonus payout / reward',
            5 => 'Affiliate payout',
            6 => 'Voucher payout',
            7 => 'Bet refund',
            8 => 'Withdrawal',
            9 => 'Chargeback',
            12 => 'Jackpot win',
            13 => 'Normal refund',
            14 => 'Activated bonus',
            15 => 'Failed bonus',
            //      16 => 'Rakeback',
            //      17 => 'Poker bonus completed',
            //      18 => 'Poker affiliate payout',
            //      19 => 'Poker vip payout',
            20 => 'Sub aff payout',
            //      21 => 'To poker',
            //      22 => 'From poker',
            //      23 => 'Internal rake race reward',
            //      24 => 'To Casino',
            //      25 => 'From Casino',
            //      26 => 'Poker affiliate deduction',
            //      27 => 'External rake race reward',
            28 => 'Old VIP deduction',
            29 => 'Buddy transfer',
            //      30 => 'Failed poker cash bonus',
            31 => 'Casino loyalty',
            32 => 'Casino race',
            33 => 'SMS fee',
            34 => 'Casino tournament buy in',
            35 => 'Casino tournament pot cost',
            36 => 'Casino tournament skill point award',
            37 => 'Casino tournament buy in with skill points',
            38 => 'Tournament cash win',
            39 => 'Tournament skill points win',
            40 => 'Tournament skill points top 3 bonus win',
            41 => 'Guaranteed prize diff',
            42 => 'Test cash for test account',
            43 => 'Inactivity fee',
            //44 => 'MG tournament registration fee',
            //45 => 'MG tournament rebuy/addon',
            //46 => 'MG tournament payout',
            //47 => 'MG tournament cancellation',
            48 => 'Casino tournament fixed cash balance pay back',
            49 => 'Casino tournament pot cost with skill points',
            50 => 'Withdrawal deduction',
            51 => 'FRB Cost',
            52 => 'Casino tournament house fee',
            53 => 'Failed casino bonus winnings',
            54 => 'Casino tournament rebuy',
            55 => 'Casino tournament freeroll cost',
            56 => 'Casino tournament house fee skill point cost',
            57 => 'Casino tournament with reward prizes',
            58 => 'Casino tournament pot cost paid by the house',
            59 => 'Casino tournament recovered freeroll money',
            60 => 'Zeroing out of balance due to too high win rollback amount',
            61 => 'Cancel / Unreg of casino tournament buy in',
            62 => 'Cancel / Unreg of casino tournament pot cost',
            63 => 'Cancel / Unreg of casino tournament house fee',
            64 => 'Cancel / Unreg of casino tournament rebuy',
            65 => 'Cancel of casino tournament, payback of win amount',
            66 => 'Cash balance bonus credit',      // done -> liability increasxe
            67 => 'Cash balance bonus debit',       // done -> liability decrease
            68 => 'Wager bonus credit',             // done
            69 => 'Wager bonus payout / shift',     // done -> liability increase
            70 => 'Wager bonus debit',              // done
            71 => 'FRB bonus shift, winnings start to turn over',               // done
            72 => 'FRB bonus debit',                // done -> liability decrease
            73 => 'Tournament ticket credit',       // not done -> liability increase
            74 => 'Tournament ticket shift',        // done -> reward
            75 => 'Tournament ticket debit',        // done -> failed reward, liability decrease
            76 => 'Trophy top up credit',           //not done -> liability increase
            77 => 'Trophy top up shift',            //done -> reward, should be treated as liability increase for now
            78 => 'Trophy top up debit',            //not done -> liability decrease
            79 => 'Trophy deposit top up credit',   //not done -> liability increase
            80 => 'Trophy deposit top up shift',    //done -> reward, should be treated as liability increase for now
            81 => 'Trophy deposit top up debit',    //not done -> liability decrease
            82 => 'Zeroing out of balance: difference between rolled back win amount and balance', //done -> reward
            83 => 'Tournament win after prize calculation', //Do nothing, nothing changes the win would not have been credited for increased liability anyway
            84 => 'Bonus Top Up Cash',                      //done -> liability increase
            85 => 'Tournament joker prize',         //not live yet, bundled as reward -> liability increase
            86 => 'Tournament bounty prize',         //not live yet, bundled as reward -> liability increase
            87 => 'Chargeback not enough money diff'  ,
            88 => 'Failed race / loyalty du to super block',
            89 => 'Temporary account closure, forfeited winnings', //liability decrease
            90 => 'Reactivated failed casino bonus winnings', // liability increase
            91 => 'Liability adjustment',
            92 => 'Chargeback settlement', // A debit to remove money to settle a chargeback -> liability decrease
            93 => 'Ignorable liability adjustment', //
            94 => 'WoJ Mini Jackpot',
            95 => 'WoJ Major Jackpot',
            96 => 'WoJ Mega Jackpot',
            97 => 'BoS buyin with prize ticket',
            98 => 'Voided bets', //To be considered in the stats
            99 => 'Voided wins', //To be considered in the stats
            100 => 'Transfer to Booster Vault',
            101 => 'Transfer from Booster Vault',
            103 => 'Undone withdrawal',
            104 => 'Turnover tax on wager',
            105 => 'Turnover tax on wager refund'
        ];
    }

    /**
     * Gets all PSP settings from the config, both normal and card PSPs.
     *
     * @return array The array with all PSPs on this form: PSP => config array.
     */
    public function getFullPspConfig()
    {
        return array_merge(
            phiveApp(PspConfigServiceInterface::class)->getPspSetting(),
            $this->getSetting('ccard_psps')
        );
    }

    /**
     * Typically used in order to display a drop down of all PSP options or otherwise loop them all.
     *
     * @param $more Optional extra array of options.
     *
     * @return array The array of PSPs with both the name in both the key and the value position.
     */
    function getPaymentMapping($more = array())
    {
        $base = array_keys($this->getFullPspConfig());
        $base = array_combine($base, $base);
        $full = array_merge($base, $more);
        asort($full);
        return $full;
    }

    /**
     * A simple wrapper around getting the network for a PSP and if none exists simply return the PSP
     * as it then can be considered to be a standalone PSP (ie it is its own network).
     *
     * @param DBUser $u_obj The user object.
     * @param string $psp The PSP.
     * @param string $config_key The key to use in the "top level" of the CasinoCashier configs, ie the $key
     * in this statement from CasinoCashier.config.php: $this->setSetting($key, ... ).
     *
     * @return string|null The PSP if a network could be found, null otherwise.
     */
    function getPspRoute($u_obj, $psp, $config_key = null)
    {
        return $this->getPspNetwork($u_obj, $psp, $config_key) ?? $psp;
    }

    /**
     * Gets the network for a specific PSP.
     *
     * @param DBUser $u_obj The user object.
     * @param string $psp The PSP.
     * @param string $config_key The key to use in the "top level" of the CasinoCashier configs, ie the $key
     * in this statement from CasinoCashier.config.php: $this->setSetting($key, ... ).
     *
     * @return string|null The PSP if a network could be found, null otherwise.
     */
    function getPspNetwork($u_obj, $psp, $config_key = null)
    {
        $u_obj = cu($u_obj);
        $config = $this->getPspSettingDeprecated($psp, null, $config_key);

        $override_network = $this->getOverrideNetwork($config, $u_obj, $config_key);
        if ($override_network) {
            return $override_network;
        }

        foreach ($config['alias_of'] as $alias_psp) {
            // TODO this needs to be made context aware, ie deposit or withdrawal but should work like this for
            // now for Zimpler / Trustly. /Henrik
            if ($this->doPspByConfig($u_obj, 'deposit', $alias_psp, null, $config_key)) {
                return $alias_psp;
            }
        }

        if ($this->doPspByConfig($u_obj, 'via', $psp, null, $config_key)) {
            return $this->getNetworkRoute($u_obj, $config);
        }

        return null;
    }

    /**
     * Retrieves the override network based on the provided configuration and user object jurisdiction.
     *
     * @param array $config The configuration array.
     * @param object $u_obj The user object.
     * @param string $config_key The configuration key.
     * @return string|null The override network if found, or null if not found.
     */
    private function getOverrideNetwork($config, $u_obj, $config_key)
    {
        if (isset($config['network_overrides'])) {
            $override_network = $config['network_overrides'][$u_obj->getCountry()];
            if ($override_network && $this->doPspByConfig($u_obj, 'deposit', $override_network, null, $config_key)) {
                return $override_network;
            }
        }

        return null;
    }

    /**
     * Gets the network route from a PSP config section.
     *
     * @param DBUser $u_obj The user object.
     * @param array $config The config.
     *
     * @return null|string Null if network could not be determined, the network name otherwise.
     */
    public function getNetworkRoute($u_obj, $config)
    {
        $network = $config['via']['network'];

        if (empty($network)) {
            return null;
        }

        if (is_string($network)) {
            // Not an array so it's the network.
            return $network;
        }

        // An array so we try to get the routing for the country in question and if not found rest of the world.
        $network_string = $network[$u_obj->getCountry()];

        return $network_string ?? $network['ROW'];
    }

    /**
     * Returns an array with PSPs as the key and the network as the value.
     *
     * @param DBUser $u_obj The user object.
     *
     * @return array The result array.
     */
    function getPspNetworkMapping($u_obj)
    {
        return array_map(function ($psp) use ($u_obj) {
            return $this->getPspRoute($u_obj, $psp);
        }, $this->getPaymentMapping());
    }

    /**
     * Gets an array for use with a HTML drop down of all PSP networks.
     *
     * @param DBUser $u_obj Optional user object.
     *
     * @return array The result array.n
     */
    function getNetworkSelect($u_obj = null)
    {
        $u_obj = cu($u_obj);
        $ret   = [];
        $tmp   = array_unique(array_values($this->getPspNetworkMapping($u_obj)));
        return array_combine(array_map('ucfirst', $tmp), $tmp);
    }

    /**
     * Checks if a row is a deposit or not.
     *
     * @param array $row The database row.
     *
     * @return bool True if deposit, false otherwise.
     */
    function isDeposit($row)
    {
        return array_key_exists('dep_type', $row);
    }

    /**
     * Returns an array with the PSP as the key and the users_settings.setting as the value.
     *
     * The value in users_settings.value is then the actual PSP id, whatever that might refer to,
     * be it phone, email or some other id.
     *
     * @return array The array.
     */
    function getDepositInfoMap()
    {
        // TODO henrik refactor this so we can just do "{$psp}_user_id".
        return  array(
            'skrill'        => 'mb_email',
            'neteller'          => 'net_account',
            'trustly'           => 'has_trustly',
            'ecopayz'           => 'has_eco',
            'instadebit'    => 'instadebit_user_id',
            'ideal'             => 'has_ideal',
            'siirto'            => 'siirto_mobile',
            'swish'             => 'swish_mobile',
            'paypal'            => 'paypal_payer_id',
            'muchbetter'        => 'muchbetter_mobile',
            'vega'              => 'vega_username',
            'mifinity'          => 'has_mifinity',
            'astropaywallet'    => 'has_astropaywallet',
            'zimplerbank'       => 'zimpler_user_id', // The deposit method is Zimpler
            'rapid'             => 'has_rapid',
            'interac'           => 'has_interac',
            'venuspoint'        => 'has_venuspoint',
            'payanybank'        => 'has_payanybank'
        );
    }

    /**
     * Gets all external PSP user info / ids such as signup email, phome or account numbers.
     *
     * TODO henrik refactor this so we can just do "{$psp}_user_id".
     * TODO henrik refactor this so  we just loop the cashier config, no more Supplier class in Mts.php
     *
     * @param DBUser $user User object.
     *
     * @return array The PSP external user ids.
     */
    function getDepositInfo($user)
    {
        if (!is_object($user)) {
            return array();
        }

        $ss = $user->getKvSettings();

        $res = [];
        $map = $this->getDepositInfoMap();
        foreach ($map as $psp => $alias) {
            $res[$psp] = $ss[$alias];
        }

        $res['paysafe']  = $this->getDeposits('', '', $user->getId(), 'paysafe');
        $res['euteller'] = $this->getDeposits('', '', $user->getId(), 'euteller');
        $nid = $user->getNid();
        $res['payretailers'] = empty($nid) ? $this->getDeposits('', '', $user->getId(), 'payretailers') : $nid;

        // We do the default ones which are just has_psp, ex: has_interac.
        foreach (Mts::getPsps() as $lbl => $psp) {
            if (empty($res[$psp])) {
                $res[$psp] = $ss["{$psp}_user_id"] ?? $ss["has_{$psp}"];
            }
        }

        return $res;
    }

    /**
     * Gets an array of applicable banks for the user in question.
     *
     * Used in order to create drop downs etc.
     *
     * @param DBUser $u_obj The user object.
     *
     * @return array The result array.
     */
    public function getUserBanks($u_obj)
    {
        return $this->db->loadArray("SELECT * FROM banks WHERE country = '{$u_obj->getCountry()}' ORDER BY bank_name");
    }

    /**
     * Gets the latest deposit for a user.
     *
     * @param DBUser $user User object.
     *
     * @return array The latest deposit.
     */
    function getLatestDeposit($user)
    {
        if (!is_object($user)) {
            return array();
        }
        return array_shift($this->getDeposits('', '', $user->getId(), '', false, false, '', '', 1));
    }

    /**
     * A cron job that runs every day in order to remove the card fraud flag from people who have made fewer than two deposits with different cards.
     *
     * @param string $sstamp Start stamp.
     *
     * @return null
     */
    function ccardFraudCron($sstamp = '')
    {
        $sstamp = empty($sstamp) ? phive()->hisMod("-30 day") : $sstamp;
        $str    = "SELECT user_id FROM users_settings WHERE setting = 'ccard-fraud-flag'";
        foreach (phive('SQL')->shs('merge', '', null, 'users_settings')->loadCol($str, 'user_id') as $uid) {
            $deposits = phive('SQL')->sh($uid, '', 'deposits')->loadArray("SELECT id, card_hash FROM deposits WHERE user_id = $uid AND `timestamp` >= '$sstamp' AND card_hash != '' GROUP BY card_hash");
            if (count($deposits) < 2) {
                cu($uid)->deleteSetting('ccard-fraud-flag');
            }
        }
    }

    /**
     * Note that currently this gets deactivated via config on the only invocation at handleCardDeposit
     *
     * @param DBUser $user User object.
     */
    function setCardFraudFlag(&$user)
    {
        if ($user->hasSetting('no-ccard-fraud-flag')) {
            return;
        }

        $log_id = phive('UserHandler')->logAction($user->getId(), "profile-blocked|fraud - ccard fraud flag set", 'intervention');
        try {
            $data = [
                'id'                => (int) $log_id,
                'user_id'           => (int) $user->getId(),
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
            ], $user);
        } catch (InvalidMessageDataException $exception) {
            phive('Logger')
                ->getLogger('history_message')
                ->error("Invalid message data on CasinoCashier", [
                    'report_type' => 'intervention_done',
                    'args' => $data,
                    'validation_errors' => $exception->getErrors(),
                    'user_id' => $data['user_id']
                ]);
        }
        $user->setSetting('ccard-fraud-flag', 1);
    }

    /**
     * Capitalizes the first letter in the PSP name and returns it.
     *
     * @param string $method The PSP.
     * @param $display TODO henrik remove this.
     *
     * @return The PSP name, eg neteller becomes Neteller.
     */
    function getPaymentMethod($method, $display = false)
    {
        return ucfirst($method);
    }

    /**
     * Typically used in BO listings to get the sum of transactions over all time, can be used to
     * quickly and easily determine how profitable a user is or has been.
     *
     * @param string $date The cutoff date / stamp, all transactions before this date will be summed up.
     * @param int|DBUser $user The user object or user id.
     * @param string $type WHERE clause filter on transaction type type.
     *
     * @return int The sum.
     */
    function getCashBalanceDate($date, $user = null, $type = '!= 4')
    {
        $user_id    = is_object($user) ? $user->getId() : $user;
        $where      = empty($user_id) ? '' : " AND user_id = $user_id ";
        $group      = empty($user_id) ? '' : " GROUP BY user_id ";
        $sql        = "SELECT SUM(amount) AS sum FROM cash_transactions WHERE `timestamp` <= '$date' AND transactiontype $type $where $group";
        // TODO test this
        if (empty($user_id)) {
            return array_sum(phive()->flatten(phive('SQL')->shs('merge', '', null, 'cash_transactions')->loadArray($sql)));
        }
        return phive('SQL')->readOnly()->sh($user_id, '', 'cash_transactions')->getValue($sql);
    }

    /**
     * We check if the user has exceeded the company imposed max deposit limit of 20k in a day.
     * This will cause the user to be play blocked during deposit operation via "depositCash()" and fire an email to F&P.
     *
     * A setting "dep_lim_checked" will be added to the users that trigger this limit, in case of this happening more
     * than once the setting will be removed and re-added so we can keep track in the user actions of all the occurrence.
     *
     * @param $uid
     * @param bool $site_only - If FALSE we check if any of the user deposit limit has been exceed, and fire an extra email to F&P.
     * @return bool|int|mixed
     */
    function checkDepLimitPlayBlock($uid, $site_only = false)
    {
        $user = cu($uid);
        if (empty($user)) {
            return true; // We don't want to show any limit warnings if we don't have a user so we return true.
        }

        //This is for RG limit remaining with negative amount
        if ($site_only === false && !empty(rgLimits()->hasLimits($user, 'deposit'))) {
            $remaining = rgLimits()->getMinLeftByType($user, 'deposit');
            if ($remaining < 0) {
                phive()->pexec('MailHandler2', 'depLimPlayBlockMail', array($uid, $remaining, 'rg'));
                return $remaining;
            }
        }

        if ($user->hasSetting('dep_lim_checked')) {
            return true;
            /* TODO reverted for now until I clarify this functionality
               if (phive()->hisNow(phive('UserHandler')->getRawSetting($uid, 'dep_lim_checked')['created_at'], 'Y-m-d') == phive()->hisNow('', 'Y-m-d')) {
               return true;
               } else {
               $user->deleteSetting('dep_lim_checked');
               }*/
        }
        $site_limit = (int)$user->getSettingOrGlobal('dep-lim-playblock', 'in-limits');
        if (empty($site_limit)) {
            return true;
        }
        $sums = $this->getDeposits(date('Y-m-d') . ' 00:00:00', '', $uid, '', 'total');
        if (mc($site_limit, $user) <= $sums['amount_sum']) {
            $user->setSetting('dep_lim_checked', 1);
            phive()->pexec('MailHandler2', 'depLimPlayBlockMail', array($uid, $sums['amount_sum'], 'site'));
            return $sums['amount_sum'];
        }

        return true;
    }

    /**
     * This is currently used in conjunction with card deposits to check if the user can repeat a certain
     * deposit and if the user is allowed to deposit with a non 3D secure card.
     *
     * @param int $cents The amount to deposit.
     * @param DBUser $user User object.
     * @param string $sdate Start date / stamp for the check, if the deposit sum is higher than the limit in the date
     * period we refuse.
     * @param string $edate End date /stamp for the time range that will be used in the check.
     * @param string $type The deposit method type, currently only **card** is used.
     * @param $key // TODO henrik remove this.
     *
     * @return bool True if the amount to be deposited ($cents) plus the total deposit sum in the period
     * is lower than or equal to the limit, false otherwise.
     */
    function checkInCashLimit($cents, $user, $sdate = '', $edate = '', $type = '', $key = 'total')
    {

        // NOTE, this logic only works for CC atm.

        $conf_key = "in-verify-$key-$type";

        $site_limit = (int)phive('Config')->getValue("in-limits", $conf_key);

        if ($site_limit == 0) {
            return false;
        }

        $site_limit = mc($site_limit, $user);

        $edate = empty($edate) ? date('Y-m-d') . ' 23:59:59' : $edate;
        $sdate = empty($sdate) ? date('Y-m-d') . ' 00:00:00' : $sdate;
        if (!empty($user)) {
            $user_id = $user->getId();
        }

        $sums = $this->getDeposits($sdate, $edate, $user_id, $type, 'total_cc');

        return ($sums['amount_sum'] + $cents) <= $site_limit;
    }

    /**
     * Checks permanently set user settings and if the total deposit sum has reached them.
     *
     * Potentially also checks jurisdictional limit which is basically just a country wide limit.
     *
     * @param DBUser $user User object.
     * @param int $deposit The amount to be deposited.
     * @param bool $do_global If true we perform the jurisdictional / country wide check.
     *
     * @return array True if limit has been reached, false otherwise.
     */
    function checkOverLimits($user, $deposit = 0, $do_global = true)
    {

        if (empty($user)) {
            return [false, null];
        }

        if (rgLimits()->reachedType($user, 'net_deposit', 0, true)) {
            lic('addCommentWhenCNDLReached', [$user], $user);
            return [true, 'show-net-deposit-limit-message'];
        }

        if (rgLimits()->reachedType($user, rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT, $deposit)) {
            $ndl = rgLimits()->getLimit($user, rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT, 'month');
            $available_limit = ($ndl['cur_lim'] - $ndl['progress']) / 100;
            return [
                true,
                rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT,
                [
                    'available_limit' => $available_limit,
                    'currency' => $user->getCurrency(),
                    'till_date' => date('Y-m-d', (strtotime($ndl['resets_at'])))
                ]
            ];
        }

        if (phive()->getSetting('lga_reality') === true) {
            // When $do_global is false we are doing some "pre-check" when loading cashier box ...
            if (empty($do_global)) {
                // ... in this scenario for SE customers when "allow_global_limit_ovveride" is true we want to display
                // a special popup that is shown inside cashier, instead of the generic error message "limit reached"
                // that is fired before even loading the cashier box [ch112119]
                $rgl = rgLimits()->getLicDepLimit($user);
                if (!empty($rgl['allow_global_limit_override'])) {
                    return [false, null];
                }
            }

            if (rgLimits()->reachedType($user, 'deposit', $deposit)) {
                return [true, null];
            }
        }

        if ($do_global && rgLimits()->reachedLicDepLimit($user, $deposit)) {
            return [true, null];
        }

        // Check for balance limit injected here
        if (lic('hasBalanceTypeLimit', [$user], $user)) {
            $balance_limit = rgLimits()->getSingleLimit($user, 'balance');
            $balance = $user->getBalance();

            if (($balance + $deposit) > $balance_limit['cur_lim']) {
                return [true, 'will-exceed-balance-limit'];
            }
        }

        $dep_period = $user->getSetting('permanent_dep_period');
        $dep_limit = $user->getSetting('permanent_dep_lim');

        if (!empty($dep_period) && !empty($dep_limit)) {
            $dep_info = $this->getDeposits(phive()->hisMod("-$dep_period day"), phive()->hisNow(), $user->getId(), '', 'total');
        }

        if ((empty($dep_info['amount_sum']) || empty($dep_limit)) && phive()->getSetting('lga_reality') !== true) {
            $dep_limit = $user->getSetting('dep-lim');
            $dep_period = $user->getSetting('dep-period');

            if (!empty($dep_limit)) {
                $dep_info = $this->getDeposits(phive()->hisMod("-$dep_period day"), phive()->hisNow(), $user->getId(), '', 'total');
            }
        }

        if (!empty($dep_limit) && !empty($dep_info['amount_sum']) && $dep_info['amount_sum'] > $dep_limit) {
            return [true, null];
        } else {
            return [false, null];
        }
    }

    /**
     * Checks if a total daily limit has been reached by way of card deposits. It's an easy and simple way of preventing
     * too much damage in case stolen cards are used to deposit with.
     *
     * @param int $cents The amount to be deposited.
     * @param DBUser $user User object.
     * @param string $date The date on Y-m-d format to sum deposits for.
     *
     * @return bool True if the limit has not yet been reached, false otherwise.
     */
    function checkDailyCardCashLimit($cents, $user, $date = '')
    {
        $user_limit = $user->getSetting("in-dc-day-limit");

        $date = empty($date) ? date('Y-m-d') : $date;

        if (empty($user_limit)) {
            return true;
        }

        $sums = $this->getDeposits($date . ' 00:00:00', $date . ' 23:59:59', $user->getId(), (new Mts())->getCcSuppliers(), 'total');

        return ($sums['amount_sum'] + $cents) <= $user_limit;
    }

    /**
     * Undoes a transaction by way of creating a new one of the same type with the opposite amount.
     *
     * The new transaction gets the undone transation id concatenated with -cancelled as its description,
     * we also check that description in order to achieve idempotency so we can't undo the same transaction
     * multiple times.
     *
     * This method is typically used in tournaments to enable the user to un/de-register and get his buy-in etc
     * credited back.
     *
     * @param array $t The transaction to undo.
     * @param bool $transact Whether or not to actually debit / credit the user's balance.
     * @param string $new_tr_type Optional override in case the undo transaction is to have a different type than
     * the undone transaction.
     * @param int $tournament_id Id of the tournament related to the transaction. 0 is the default value and it means
     * that the transaction is not related to any tournament.
     *
     * @return bool True if the transaction was undone, false if not, eg if we got stopped by the idempotency check.
     */
    public function undoTransaction($t, $transact = true, $new_tr_type = '', int $tournament_id = 0)
    {
        $cancel_descr = array_pop(explode('-', $t['description']));
        if ($cancel_descr == 'cancelled') {
            return false;
        }
        $u               = cu($t['user_id']);
        $tr_cancel_descr = $t['id'] . '-cancelled';
        $old             = $this->getTransactionByDescr($tr_cancel_descr, $t['user_id']);
        if (!empty($old)) {
            return false;
        }
        $amount          = -$t['amount'];
        $new_tr_type = empty($new_tr_type) ? $t['transactiontype'] : $new_tr_type;
        if ($transact) {
            $u->incrementAttribute('cash_balance', $amount);
            rgLimits()->onCashTransaction($u, $new_tr_type, $amount);
            realtimeStats()->onCashTransaction($u, $new_tr_type, $amount);
        }
        $new_tr_id = $this->insertTransaction($u, $amount, $new_tr_type, $tr_cancel_descr);

        if ($tournament_id !== 0) {
            $topic = 'tournament_cash_transaction';
            $history_message = new TournamentCashTransactionHistoryMessage(
                [
                    'user_id'          => (int) $u->getId(),
                    'transaction_id'   => (int) $new_tr_id,
                    'amount'           => (int) $amount,
                    'currency'         => $u->getCurrency(),
                    'transaction_type' => (int) $new_tr_type,
                    'parent_id'        => 0,
                    'description'      => $tr_cancel_descr,
                    'tournament_id'    => $tournament_id,
                    'device_type'      => phive()->isMobile() ? 1 : 0,
                    'event_timestamp'  => time(),
                ]
            );
        } else {
            $topic = 'cash_transaction';
            $history_message = new CashTransactionHistoryMessage(
                [
                    'user_id'          => (int) $u->getId(),
                    'transaction_id'   => (int) $new_tr_id,
                    'amount'           => (int) $amount,
                    'currency'         => $u->getCurrency(),
                    'transaction_type' => (int) $new_tr_type,
                    'parent_id'        => 0,
                    'description'      => $tr_cancel_descr,
                    'event_timestamp'  => time(),
                ]
            );
        }
        lic('addRecordToHistory', [
            $topic,
            $history_message
        ], $u);

        $t['description'] .= '-cancelled';
        phive('SQL')->sh($t)->save('cash_transactions', $t);
        return true;
    }

    /**
     * Simply gets the user's first_deposits row.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_first_deposits For keeping track of if a user has deposited or not.
     *
     * @param int $user_id The user id.
     *
     * @return array The first deposit.
     */
    public function getFirstDeposit($user_id)
    {
        $user_id = (int)$user_id;
        return phive('SQL')->sh($user_id)->loadAssoc("SELECT * FROM first_deposits WHERE user_id = {$user_id}");
    }

    /**
     * Simply gets the user's total deposit row.
     *
     *
     * @param int $user_id The user id.
     *
     * @return array The total deposit.
     */
    public function getTotalDeposits($user_id)
    {
        $user_id = (int)$user_id;
        return phive('SQL')->sh($user_id, '', 'deposits')->loadArray("SELECT * FROM deposits WHERE user_id = $user_id");
    }

    // TODO henrik remove, use getFirstDeposit instead.
    function getFirstTimeDeposits($user_id)
    {
        return phive('SQL')->sh($user_id, '', 'first_deposits')->loadArray("SELECT * FROM first_deposits WHERE user_id = $user_id");
    }

    /**
     * Checks if a user has deposited or not.
     *
     * TODO henrik remove the straight getValue query, just use getFirstDeposit instead.
     *
     * @param int $user_id The user id.
     *
     * @return bool True if a deposit has been made, false otherwise.
     */
    function hasDeposited($user_id)
    {
        if (empty($user_id)) {
            return false;
        }
        $user_id = (int)$user_id;
        $res = phive('SQL')->sh($user_id)->getValue("SELECT COUNT(*) FROM first_deposits WHERE user_id = $user_id");
        return !empty($res);
    }

    /**
     * Wrapper around Cashier::insertTransaction() in order to insert a casino bonus activation transaction.
     *
     * @uses Cashier::insertTransaction()
     * @uses Bonuses::getTransactionType()
     *
     * @param int $uid The user id.
     * @param int $amount The money amount that was activated.
     * @param int $bid The bonus id (bonus_types.id).
     * @param string $bonus_type The bonus type (bonus_entries.bonus_type).
     * @param int $eid Bonus entry id (bonus_entries.id).
     *
     * @return null
     */
    function insertBonusActivation($uid, $amount, $bid = 0, $bonus_type = '', $eid = 0)
    {
        // get transaction type
        $trans_type = phive('Bonuses')->getTransactionType($bonus_type, 'credit');
        $user = cu($uid);

        $tr_id = $this->insertTransaction($user, $amount, $trans_type, 'Bonus Activation', $bid);
        if (in_array($bonus_type, ['casino', 'casinowager'])) { //casino awards on bonus_entries.balance field, which is used as bonus_balance
            try {
                $data = [
                    'user_id'          => (int) $uid,
                    'transaction_id'   => (int) $tr_id,
                    'amount'           => (int) $amount,
                    'currency'         => $user->getCurrency(),
                    'transaction_type' => (int) $trans_type,
                    'parent_id'        => 0,
                    'description'      => 'Bonus Activation',
                    'event_timestamp'  => time(),
                ];

                /** @uses Licensed::addRecordToHistory() */
                lic(
                    'addRecordToHistory',
                    [
                    'bonus',
                    new BonusHistoryMessage($data)
                    ],
                    $user
                );
            } catch (InvalidMessageDataException $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data on CasinoCashier", [
                        'report_type' => 'bonus',
                        'args' => $data,
                        'validation_errors' => $exception->getErrors(),
                        'user_id' => $data['user_id']
                    ]);
            }
        }
    }

    /**
     * Can be used to recalculate daily stats in case something went wrong during the
     * ordinary calculations.
     *
     * Not tested sharded yet! /Henrik
     * TODO henrik, remove the ini_set, it was only needed to make up for a mistake by Daniel S.
     *
     * @param string $date The day (Y-m-d).
     * @param bool $only_user Whether or not to only recalculate user related stats.
     * @param bool $make Whether or not we need to calculate the bets_tmp and wins_tmp tables or not.
     * @param bool $do_mp Whether or not to do mp / tournament / BoS stats too.
     * @param bool $include_sports Include also recalculation of sports data
     *
     * @return null
     */
    function recalcDay($date, $only_user = false, $make = true, $do_mp = true, $include_sports = false)
    {
        $sql    = phive('SQL');

        $sdate  = $date . ' 00:00:00';
        $edate  = $date . ' 23:59:59';

        $date = date('Y-m-d', strtotime($edate));
        $this->udsInProgress($date, 'restart');

        if ($make) {
            phive('Casino')->makeBetWinTmp($sdate, $edate);
        }

        $sql->shs(false)->query("DELETE FROM users_daily_stats WHERE `date` = '$date'");

        if ($do_mp) {
            $sql->shs(false)->query("DELETE FROM users_daily_stats_mp WHERE `date` = '$date'");
        }

        $sql->shs(false)->query("DELETE FROM users_daily_stats_total WHERE `date` = '$date'");

        //without this it times out after 30 seconds.
        ini_set('max_execution_time', '30000');

        if ($sql->isSharded('users_daily_stats')) {
            $sql->loopShardsSynced(function ($db, $shard, $id) use ($sdate, $edate, $date) {
                phive('Cashier')->calcUserCache($sdate, $edate, $db);
            });
            phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats', $date);
        } else {
            phive('Cashier')->calcUserCache($sdate, $edate);
        }

        if (hasMp() && $do_mp) {
            if ($sql->isSharded('users_daily_stats_mp')) {
                $sql->loopShardsSynced(function ($db, $shard, $id) use ($date) {
                    phive('Tournament')->calcDailyStats($date, $db);
                });
                phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_mp', $date);
            } else {
                phive('Tournament')->calcDailyStats($date);
            }
            //phive('Tournament')->mpDailyStats($date);
        }

        if (!$only_user) {
            $sql->shs(false)->query("DELETE FROM users_daily_game_stats WHERE `date` = '$date'");
            $sql->query("DELETE FROM network_stats WHERE `date` = '$date'");

            if ($sql->isSharded('users_daily_game_stats')) {
                $sql->loopShardsSynced(function ($db, $shard, $id) use ($date) {
                    phive('MicroGames')->calcGameUserStats($date, $db);
                });
                phive('UserHandler')->aggregateUserStatsTbl('users_daily_game_stats', $date);
            } else {
                phive('MicroGames')->calcGameUserStats($date);
            }
            phive('MicroGames')->calcNetworkStats($date);
        }

        if ($include_sports) {
            $sql->shs(false)->query("DELETE FROM users_daily_stats_sports WHERE `date` = '$date'");

            if ($sql->isSharded('users_daily_stats_sports')) {
                $sql->loopShardsSynced(function ($db) use ($sdate, $edate, $date) {
                    phive('Micro/Sportsbook')->calcSportsBookDailyStats($sdate, $edate, $date, $db);
                });
                phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_sports', $date);
            } else {
                phive('Micro/Sportsbook')->calcSportsBookDailyStats($sdate, $edate, $date);
            }
        }
    }

    /**
     * Inserts a queued transaction.
     *
     * @deprecated - Use Booster.php
     *
     *
     * @param int $uid The user id.
     * @param int $amount The amount to insert.
     * @param string $desc Description for this transaction.
     * @param int $type Transaction type.
     *
     * @return null
     */
    function qTrans($uid, $amount, $desc, $type)
    {
        $s = phive('UserHandler')->getRawSetting($uid, 'super-blocked');
        if (!empty($s['value'])) {
            phive('UserHandler')->logAction($uid, "did not get $amount of type $type because he is super blocked", "queued_transactions", true);
            return;
        }
        phive("SQL")->sh($uid, '', 'queued_transactions')->insertArray('queued_transactions', array(
            'user_id'          => $uid,
            'amount'           => $amount,
            'description'    => $desc,
            'transactiontype'  => $type));
    }

    /**
     * A cron job that queues up loyalty transactions for payment in a time period.
     *
     * @deprecated - Booster Migration
     *
     * @param string $sday Start date (Y-m-d).
     * @param string $eday End date (Y-m-d).
     *
     * @return null
     */
    function qLoyalty($sday, $eday)
    {
        // Booster Migration Logic
        // Do not queue countries that have been moved to the new logic
        $exc_countries = phive('DBUserHandler/Booster')->getSetting('countries_enabled');
        $exclude = (!empty($exc_countries))
            ? ' AND u.country NOT IN(' . phive('SQL')->makeIn($exc_countries) . ')'
            : '';
        // End - Booster Migration Logic
        $thold = phive("Config")->getValue('vip', 'casino-loyalty-thold');
        $uds_table = phive('UserHandler')->dailyTbl();

        $index = 1;
        foreach (phive('UserHandler')->getCasinoStats($sday, $eday, 'us.user_id', $exclude, '', '', '', '', false, '', false, $uds_table) as $r) {
            if (empty($r['gen_loyalty']) || $r['gen_loyalty'] < mc($thold, $r['currency'])) {
                continue;
            }

            // Saving 5 logs in megariches
            // Will remove after we get logs
            if (phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_MEGARICHES && $index <= 5) {
                $logger = phive('Logger')->getLogger('cron');
                $logger->info(sprintf('Monday cron started: user_id: %s, loyalty: %s', $r['user_id'], $r['gen_loyalty']));
                $index++;
            }
            $this->qTrans($r['user_id'], $r['gen_loyalty'], 'Casino Loyalty', 31);
        }
    }

    /**
     * Gets an array of taxes with the country ISO2 as the key for each tax rate.
     *
     * @return array The map.
     */
    function getTaxMap()
    {
        return phive('SQL')->loadKeyValues("SELECT * FROM bank_countries", 'iso', 'tax');
    }

    /**
     * Gets an array of VATs with the country ISO2 as the key for each VAT rate.
     *
     * @return array The map.
     */
    function getVatMap()
    {
        return phive('SQL')->loadKeyValues("SELECT * FROM bank_countries", 'iso', 'vat');
    }

    /**
     * Calculates misc. lifetime stats for quick display in BI listings.
     *
     * @param SQL $db If empty a master only context is assumed, otherwise we assume that $db is a node connection.
     *
     * @return null
     */
    function cacheLifetimeStats($db = null)
    {
        $sql = !empty($db) ? $db : phive('SQL');
        $today = phive()->today();

        $start = '2011-01-01';
        $cols = array('us.ndeposits', 'us.nwithdrawals', 'us.nbusts', 'us.deposits', 'us.withdrawals', 'us.bets', 'us.tax', 'us.wins', 'us.jp_contrib', 'us.frb_ded', 'us.rewards', 'us.fails', 'us.bank_fee',
                      'us.op_fee', 'us.aff_fee', 'us.site_rev', 'us.bank_deductions', 'us.jp_fee', 'us.real_aff_fee', 'us.site_prof', 'us.gen_loyalty', 'us.paid_loyalty',
                      'us.frb_wins', 'us.frb_cost', 'us.gross');
        $num_cols       = phive('SQL')->makeSums($cols, "");

        $str = "SELECT us.user_id, us.username, us.firstname, us.lastname, $num_cols
            FROM users_daily_stats us
            WHERE date >= '$start' AND us.date <= '$today'
            GROUP BY us.user_id
            ORDER BY gross";
        $res = $sql->loadArray($str);

        // We truncate the node before we insert
        $sql->query("TRUNCATE users_lifetime_stats");
        //$x = $sql->insertTable('users_lifetime_stats', $res, array(), array(), 2);
        $sql->insert2DArr('users_lifetime_stats', $res);
    }

    /**
     * Calculates misc. stats on a per user and day basis in the users_daily_stats table, first on each node.
     *
     * Once the data is on each node it is aggregated to the master database and the table with the same name there. That way
     * doing SQL::shs() to get this data is not necessary which offlodes the nodes somewhat.
     *
     * @param string $sdate Start stamp for the period to cache.
     * @param string $edate End stamp for the period to cache.
     * @param SQL $db If passed in it will be assued to be a node connection.
     *
     * @return null
     */
    function calcUserCache($sdate, $edate, $db = '')
    {
        $is_shard = ! empty($db);
        $this->setDb($db, $is_shard);
        $db = $this->db;
        $taxmap = $this->getTaxMap();
        $vatmap = $this->getVatMap();
        $bw_prefix = 'tmp';
        $tbl       = 'users_daily_stats';

        $bonus_types = $this->getCashTransactionsBonusTypes();

        $week_day       = date('N', strtotime($sdate));
        $loyalty_limits = explode(',', phive('Config')->getValue('limits', 'loyalty'));
        //TODO this needs to be its own calculation happening after both normal and mp daily stats calc
        $loyalty_limit  = $loyalty_limits[$week_day - 1];

        $shard_id = $db->is_shard ? $db->my_sh_id : null;
        $cols     = $this->getColsForDailyStats();
        $u        = [];
        foreach ($cols as $key => $type) {
            phive()->addCol2d(
                $this->getTransactionsAmountByTypeAndDate($type, $sdate, $edate, $shard_id),
                $u,
                ['amount_sum' => $key]
            );
        }

        $bets = $db->loadArray("SELECT * FROM bets_{$bw_prefix}");

        foreach ($bets as $b) {
            $uid = $b['user_id'];
            $u[$uid]['gen_loyalty'] += $b['loyalty'];
            $u[$uid]['bets']        += $b['amount'];
            $u[$uid]['bets_fee']    += $b['op_fee'];
            $u[$uid]['jp_contrib']  += $b['jp_contrib'];
        }

        $casino = phive('Casino')->setDb($db, $is_shard);
        $wins = $casino->getComplStatsPerUser("wins_{$bw_prefix}", $sdate, $edate, " AND tbl.bonus_bet != 3 AND tbl.award_type != 4");
        phive()->addCol2d($wins, $u, array('amount_sum' => 'wins', 'op_fee_sum' => 'wins_fee'));
        $frb_wins = $casino->getComplStatsPerUser("wins_{$bw_prefix}", $sdate, $edate, " AND tbl.bonus_bet = 3 ");
        // Revert to default DB to make sure Casino is using default in potentially subsequent logic
        phive('Casino')->setDb();

        foreach ($frb_wins as $fw) {
            $uid = $fw['user_id'];
            $u[$uid]['wins_fee'] += abs($fw['op_fee']);
            $u[$uid]['frb_wins'] += $fw['amount_sum'];
        }

        $deposits    = $this->getDeposits($sdate, $edate, '', '', 'user');
        $withdrawals = $this->getPendingGroup($sdate, $edate, " = 'approved' ", '', '', 'user', '', 'approved_at');

        phive()->addCol2d($deposits, $u, array('amount_sum' => 'deposits', 'cost_sum' => 'deposits_cost_sum', 'deduct_sum' => 'deposit_deductions'));
        phive()->addCol2d($withdrawals, $u, array('amount_sum' => 'withdrawals', 'cost_sum' => 'withdrawals_cost_sum', 'deduct_sum' => 'withdraw_deductions'));

        $cday    = date('Y-m-d', strtotime($sdate));
        $inserts = array();

        foreach ($u as $uid => $r) {
            $user = cu($uid);

            if (!is_object($user)) {
                continue;
            }
            $udata   = $user->data;
            $uid = intval($uid);
            $t       = $db->loadAssoc("SELECT username, firstname, lastname, affe_id, country FROM users WHERE id = $uid");
            $move_us = array('bets', 'wins', 'deposits', 'withdrawals', 'rewards', 'fails', 'jp_contrib', 'paid_loyalty', 'frb_wins', 'gen_loyalty', 'frb_cost');
            $t       = phive()->moveit($move_us, $r, $t);

            if (!empty($loyalty_limit)) {
                $cur_loyalty_limit = mc($loyalty_limit, $udata['currency']);
                $t['gen_loyalty']  = $cur_loyalty_limit < $t['gen_loyalty'] ? $cur_loyalty_limit : $t['gen_loyalty'];
            }

            if (!empty($r['voided_bets'])) {
                $r['bets'] -= $r['voided_bets'];
                $t['bets'] = $r['bets'];
            }

            if (!empty($r['voided_wins'])) {
                $r['wins'] -= $r['voided_wins'];
                $t['wins'] = $r['wins'];
            }

            if (!empty($r['undone_withdrawals'])) {
                $t['withdrawals'] = $r['withdrawals'] - $r['undone_withdrawals'];
            }

            $t['firstname']          = $db->realEscape($udata['firstname']);
            $t['lastname']           = $db->realEscape($udata['lastname']);
            $t['user_id']            = $uid;
            $t['date']               = $cday;
            $t['currency']           = $udata['currency'];
            $t['gross']              = $r['bets'] - $r['wins'] - $r['jp_contrib'];
            $t['op_fee']             = $r['bets_fee'] - $r['wins_fee'];
            $t['frb_cost']           = $r['frb_cost'];
            $t['chargebacks']        = abs($r['chargebacks']);
            $t['transfer_fees']      = abs($r['deposits_cost_sum']) + abs($r['withdrawals_cost_sum']);
            $t['bank_fee']           = $t['chargebacks'] + $t['transfer_fees'] + $r['sms_fees'];
            $t['bank_deductions']    = $r['deposit_deductions'] + $r['withdraw_deductions'];
            $t['province']           = $user->getMainProvince();

            //here we bundle back the types that are failed rewards
            $t['fails']              = abs($r['fails']) + abs($r['failed_winnings']) + abs($r['inactivity_fee']) + abs($r['cash_balance_bonus_debit'])
            + abs($r['frb_bonus_debit']) + abs($r['tournament_ticket_debit']) + abs($r['temp_account_forfeit']);

            //here we bundle back the types that are rewards
            foreach ($bonus_types as $type) {
                $t['rewards'] += abs($r[$type]);
            }

            if (!empty($taxmap[$udata['country']])) {
                $t['tax']          = ($t['gross'] - $t['rewards'] - $t['paid_loyalty']) * $taxmap[$udata['country']];
            }

            // Conceptual difference between tax and vat is that tax allows for deducting rewards, vat does not.
            if (!empty($vatmap[$udata['country']])) {
                $t['tax']          = $t['gross'] * $vatmap[$udata['country']];
            }

            /* Tax deduction calculating */
            $t['tax_deduction'] = 0;
            if (!empty($r['tax_deduction'])) {
                $t['tax_deduction'] = $r['tax_deduction'];
                if (!empty($r['tax_deduction_refund'])) {
                    $t['tax_deduction'] = $r['tax_deduction'] + $r['tax_deduction_refund'];
                }
            }

            $net                     = $t['gross'] - $t['tax'] - $t['bank_fee'] - $t['op_fee'] - $t['rewards'] - $t['paid_loyalty']; // - $t['jp_fee']; // + $t['fails'];
            $t['before_deal']        = $net;
            $t['site_rev']           = $net + $t['bank_deductions'] + $t['fails'];
            $t['site_prof']          = $t['site_rev'] + $t['tax_deduction'];
            $inserts[] = $t;
        }

        $date = date('Y-m-d', strtotime($edate));
        $this->udsInProgress($date, 'start');

        $db->insert2DArr($tbl, $inserts);

        $this->udsInProgress($date, 'end');

        //Revert to default DB to avoid issues if cashier methods are used in subsequent execution.
        $this->setDb();
    }

    /**
     * We save the status of the users daily stats data generation to be able to avoid issues on the withdrawals liability calculations
     *
     * @param string $date Day date (Y-m-d).
     * @param string $action The action: restart, start or end.
     *
     * @return null
     */
    public function udsInProgress($date, $action)
    {
        $reports_info = phive('SQL')->loadKeyValues("SELECT * FROM misc_cache WHERE id_str LIKE 'reports-%-users_daily_stats'", 'id_str', 'cache_value');

        if ($action == 'start' || $action == 'restart') {
            if (empty($reports_info['reports-processing-users_daily_stats'])) {
                phive()->miscCache('reports-processing-users_daily_stats', $date);
            } else {
                phive('SQL')->save('misc_cache', ['cache_value' => $date, 'id_str' => 'reports-processing-users_daily_stats']);
            }
        } elseif ($action == 'end') {
            if (empty($reports_info['reports-last-users_daily_stats'])) {
                phive()->miscCache('reports-last-users_daily_stats', $date);
            } elseif ($reports_info['reports-last-users_daily_stats'] < $date) {
                phive('SQL')->save('misc_cache', ['cache_value' => $date, 'id_str' => 'reports-last-users_daily_stats']);
            }

            phive('SQL')->delete('misc_cache', ['id_str' => 'reports-processing-users_daily_stats']);
        }
    }

    /**
     * Email that is being sent out to users to remind them to do tye KYC process.
     *
     * @param int $user_id The user id.
     *
     * @return null
     */
    function sendVerifyReminder($user_id)
    {
        if (phive()->moduleExists("MailHandler2") && !empty($user_id)) {
            $user       = cu($user_id);
            $replacers  = phive('MailHandler2')->getDefaultReplacers($user);
            $user->setSetting('verify_mail_sent', date('Y-m-d'));
            phive("MailHandler2")->sendMail('verify.account', $user, $replacers);
        }
    }

    /**
     * Notifies a user by way of email and / or SMS about a transaction that was credited or debited to the user's account.
     *
     * TODO henrik, nfCents() is now in Phive base so remove the display inclusion.
     * TODO henrik move efEuro(), efIso() and cfPlain() to Currencer.php, global namespace if they are used, otherwise remove.
     *
     * @param array $trans The transaction.
     * @param DBUser $user The user.
     * @param int $trans_amount
     * @param $convert_amount=false
     *
     * @return xxx
     */
    public function notifyUserTransaction($trans, $user, $trans_amount, $convert_amount = false)
    {
        if (is_numeric($trans)) {
            // Used for awardedracepayout at the moment
            $trans = [
                'bonus_entry' => 0,
                'transactiontype' => $trans,
                'user_id' => $user->getId(),
            ];
        }

        if ($trans['bonus_entry'] != 0) {
            phive('Bonuses')->approve($trans['bonus_entry'], [], $user->getId());
            if (phive()->moduleExists("MailHandler2")) {
                phive("MailHandler2")->sendMail("bonus.completed", $user);
            }
        } elseif (in_array($trans['transactiontype'], $this->getSetting('qrel-email')) && phive()->moduleExists("MailHandler2")) {
            require_once __DIR__ . '/../../../diamondbet/html/display.php';
            $replacers          = phive('MailHandler2')->getDefaultReplacers($user);
            $replacers["__AMOUNT__"]    = $convert_amount ? nfCents($trans_amount, true) : $trans_amount;
            phive("MailHandler2")->sendMail("transtype.{$trans['transactiontype']}.approved", $user, $replacers);
        }

        if (in_array($trans['transactiontype'], $this->getSetting('qrel-sms')) && phive()->moduleExists("Mosms")) {
            setCur($user);
            $sms_trigger = "transtype.{$trans['transactiontype']}.approved.sms";
            $msg = t2($sms_trigger, array($convert_amount ? efEuro($trans_amount, true) : $trans_amount), $user->getLang());
            if (phive('DBUserHandler')->canSendTo($user, null, 'transtype')) {
                if (phive('Mosms')->putInQ($user, $msg, false)) {
                    phive('UserHandler')->logAction($trans['user_id'], "Sent {$sms_trigger} SMS with $trans_amount in cents: " . $msg, 'sms');
                }
            }
        }
    }

    /**
     * We check if withdrawals are still pending in a range of the previous hour, it is not done with a less than
     * because then the email will trigger every hour.
     */
    public function checkPendingWithdrawals(): void
    {
        $startTime = phive()->hisMod('-49 hour', phive()->hisNow(), 'Y-m-d H:') . '00:00';
        $endTime = phive()->hisMod('+1 hour', $startTime, 'Y-m-d H:i:s');

        $lastSuccessfulCronTime = phive('Config')->getValue('pending-withdrawals-cron', 'last-successful');
        $startTime = !empty($lastSuccessfulCronTime) ? $lastSuccessfulCronTime : $startTime;

        // Sanity check for an unexpected scenario:
        // If the start time and end time are identical, it indicates that the cron job ran twice within the same hour.
        if ($startTime === $endTime) {
            phive('Logger')->getLogger('payments')->warning(
                'Cron Job: Multiple runs for 48 hours pending withdrawal emails in an hour.',
                [
                    'startTime' => $startTime,
                    'endTime' => $endTime
                ]
            );
            return;
        }

        $pendings = phive('SQL')->shs()->loadArray("SELECT id, user_id
                    FROM pending_withdrawals
                    WHERE status = 'pending' AND timestamp >= '{$startTime}' AND timestamp < '{$endTime}';");

        if (!empty($pendings)) {
            $this->sendWithdrawalNotifications($pendings);
        }

        $this->updateLastSuccessfulCron($endTime);
    }

    private function sendWithdrawalNotifications(array $pendings): void
    {
        $subject = "Notification: Withdrawals pending for more than 48 hours";
        $content = "<p>Currently there are withdrawals that have been pending more than 48 hours.";

        $groupedData = array_reduce($pendings, function (array $carry, array $item) {
            $carry[$item['user_id']][] = $item['id'];
            return $carry;
        }, []);

        foreach ($groupedData as $user_id => $withdrawal_ids) {
            $count = count($withdrawal_ids);
            $content .= "<p>User_id $user_id has $count withdrawal" . ($count > 1 ? 's' : '') . ": ";
            $content .= "(" . implode(', ', $withdrawal_ids) . ")</p>";
        }

        phive('MailHandler2')->mailLocalFromConfig($subject, $content, 'withdrawal-pending-sla');
    }

    private function updateLastSuccessfulCron(string $endTime): void
    {
        phive('SQL')->save('config', [
            'config_tag' => 'pending-withdrawals-cron',
            'config_name' => 'last-successful',
            'config_value' => $endTime,
            'config_type' => json_encode(['type' => 'datetime', 'format' => 'Y-m-d H:i:s'])
        ], ['config_tag' => 'pending-withdrawals-cron', 'config_name' => 'last-successful']);
    }

    /**
     * Releases a queued transaction which means that the queued transaction gets removed and its amount
     * gets credited to the user's balance.
     *
     * @deprecated
     *
     * @param int $tid The id of the queued transaction.
     * @param string $table The queued transaction table name, typically queueed_transactions.
     * @param bool $ajax Whether or not we are in an XHR context, if yes we simply echo the result, otherwise we return.
     *
     * @return null|string The result.
     */
    function releaseQdTransaction($tid, $table, $ajax = true)
    {
        $micro      = phive('Casino');
        $trans      = $this->getTrans($tid, $table);
        $user       = cu($trans['user_id']);

        // TODO remove this logging when we're sure booster payment it's working
        phive('Logger')->log('payQdTransactions autoPay payAll releaseQdTransaction user', [
            'transaction_id' => $tid,
            'table' => $table,
            'user_id' => $trans['user_id'],
            'time' => date('Y-m-d H:i:s')
        ]);

        if (!is_object($user)) {
            echo "User missing, $table details:<br>";
            print_r($trans);
            exit;
        }

        if ($user->isSuperBlocked()) {
            phive('UserHandler')->logAction($user, "Super blocked so did not payout tr type {$trans['transactiontype']} of amount {$trans['amount']}", 'super-blocked');
            phive('SQL')->delete($table, ['id' => $tid], $user->getId());
            return true;
        }

        $trans_amount = $trans['amount']; //> 0 ? $trans['amount'] : phive('Bonuses')->getBalance($trans['bonus_entry']);

        // TODO remove this logging when we're sure booster payment it's working
        phive('Logger')->log('payQdTransactions autoPay payAll releaseQdTransaction amount', [
            'transaction_id' => $tid,
            'table' => $table,
            'user_id' => $user->getId(),
            'amount' => $trans_amount,
            'time' => date('Y-m-d H:i:s')
        ]);

        if ($trans_amount > 0) {
            $result    = $micro->changeBalance($user, $trans_amount, $trans['description'], $trans['transactiontype'], false);
            $event_map = array(31 => 'awardedcashback', 32 => 'awardedracepayout');
            $tag       = $event_map[$trans['transactiontype']];
            if (!empty($tag)) {
                uEvent($tag, $trans_amount, '', '', $user->data);
            }
        } else {
            $result = 'balance unchanged';
        }

        if ($micro->okResult($result)) {
            setCur($user);
            phive('SQL')->delete($table, ['id' => $tid], $user->getId());

            // we add the new weekend booster amount to the existing one
            if ($trans['transactiontype'] == 31) {
                $trans_amount += (int) phive('DBUserHandler/Booster')->releaseBoosterVault($user, false, 0, true);
            }

            $this->notifyUserTransaction($trans, $user, $trans_amount, true);

            setDefCur();

            if ($ajax) {
                echo "New balance: $result";
            } else {
                return $result;
            }
        } else {
            if ($ajax) {
                echo 'fail';
            } else {
                return 'fail';
            }
        }
    }

    /**
     * Returns the max amount and the max amount of times in a row that is allowed to do via quick
     * deposits.
     *
     * @return array An array with the limits.
     */
    function getQuickLimits()
    {
        $str = phive('Config')->getValue('in-limits', 'quick-deposit');
        $ret = array();
        foreach (explode(' ', $str) as $sub) {
            $tmp = explode(':', $sub);
            if (!empty($tmp[0])) {
                $ret[strtolower($tmp[0])] = array('num_times' => $tmp[1], 'max_limit' => $tmp[2]);
            }
        }
        return $ret;
    }

    /**
     * Checks whether or not a user can do repeat / oneclick deposits, typically with a credit card.
     *
     * First of all we check if the user has gotten quick deposits turned off completely, if that is the
     * case return false right away. After that we check if the user has made too many quick deposits in a row,
     * if he has we also return false, finally we check if the amount is too big to do via quick deposits,
     * if it is we return false too.
     *
     * @param DBUser $user The user object.
     * @param int $cents The amount to quick deposit.
     *
     * @return bool True if the user is allowed, false otherwise.
     */
    function canQuickDepositViaCard($user, $cents = 0)
    {
        if (phive('Cashier')->getSetting('quick_deposit')['ccard']['active'] === false) {
            return false;
        }

        $qlimits = $this->getQuickLimits($user);
        $country = strtolower($user->getAttr('country'));
        $num_times = !isset($qlimits[$country]) ? $qlimits['default']['num_times'] : $qlimits[$country]['num_times'];
        $max_amount = !isset($qlimits[$country]) ? $qlimits['default']['max_limit'] : $qlimits[$country]['max_limit'];

        // We convert with the pretty multiplier to get a rough limit to send the the MTS for instance.
        $this->max_quick_amount = mc($max_amount, $user);

        $num_times_setting = $user->getSetting('n-quick-deposits-limit');
        if ($num_times_setting !== false) {
            $num_times = min($num_times, $num_times_setting);
        }
        if (empty($num_times)) {
            return false;
        }
        $n_quick = $user->getSetting('n-quick-deposits');
        if ($n_quick >= $num_times) {
            return false;
        }
        if (empty($cents)) {
            return true;
        }
        if ($cents >= $this->max_quick_amount) {
            return false;
        }
        return true;
    }

    public function checkCreditCardIsActive(DBUser $user, string $payment_provider, int $card_id)
    {

        $status = phive('Dmapi')->checkProviderStatus($user->getId(), $payment_provider, '', '', $card_id);

        $notActiveStatuses = [
            'rejected',
            'deactivated',
        ];

        if (in_array($status, $notActiveStatuses)) {
            return false;
        }

        return true;
    }

    /**
     * The user submits a reload code within the context of his logged in session in the browser, but the
     * #define eposit success call comes by way of an API callback where we do not have access to that session
     * which means we need to store the reload code in Redis on submit so that it is available when
     * the API call comes.
     *
     * @param DBUser $user The user object.
     *
     * @return null
     */
    function setReloadCode($user = '')
    {
        if (empty(phive('Bonuses')->getCurReload($user))) {
            phive('Bonuses')->setCurReload(trim($_REQUEST['bonus']), $user);
        }
    }

    // TODO henrik remove when all PSPs are via the MTS
    function insertToken(&$user, $amount, $security, $ext_info = null, $site_type = '', $lang = '')
    {
        $this->setReloadCode($user);
        $ud = $user->data;
        if (!empty($site_type)) {
            $site_type = $_POST['box_type'];
        }
        if (!empty($lang)) {
            phive('Localizer')->getLanguage();
        }

        phive('SQL')->insertArray('transfer_tokens', array(
            'security' => $security,
            'user_id' => $ud['id'],
            'amount' => $amount,
            'ext_info' => $ext_info,
            'username' => $ud['username']));
    }

    // TODO henrik remove when all PSPs are via the MTS
    function deleteTokenRow($token)
    {
        phive('SQL')->query("DELETE FROM transfer_tokens WHERE security = '$token'");
    }

    // TODO henrik remove when all PSPs are via the MTS
    function getTokenRow($token)
    {
        return phive("SQL")->loadAssoc("SELECT * FROM transfer_tokens WHERE security = '$token'");
    }

    /**
     * Pays a list of ids for queued transactions.
     *
     * @deprecated - Booster Migration
     *
     * @param array $tids The array of ids to qeueued transactions.
     * @param string $table Table to work with, typically queued_transactions.
     *
     * @return null
     */
    function payAll($tids, $table = 'queued_transactions')
    {
        phive("Logger")->logPromo('mass_q_release');
        if (phive()->moduleExists('Trophy')) {
            phive()->pexec('Trophy', 'payoutEvent', [$tids], 500, true);
        }
        foreach ($tids as $tid) {
            // TODO remove this logging when we're sure booster payment it's working
            phive('Logger')->log('payQdTransactions autoPay payAll', [
                'transaction_id' => $tid,
                'table' => $table,
                'time' => date('Y-m-d H:i:s')
            ]);

            $result = $this->releaseQdTransaction($tid, $table, false);
            if ($result === 'fail') {
                die('fail');
            }
        }
    }

    // TODO henrik remove when Instadebit is via the MTS.
    function postData($url, $p)
    {
        $pstr  = http_build_query($p);
        return phive()->post($url, $pstr, 'application/x-www-form-urlencoded');
    }

    /**
     * Wrapper around CasinoCashier::doPspByConfig().
     *
     * @uses CasinoCashier::doPspByConfig()
     *
     * @param DBUser $u_obj The user object.
     * @param string $alt The PSP.
     * @param string $action Action, deposit or withdraw.
     * @param string $channel The channel / device, some options might not be available on mobile and vice versa.
     *
     * @return bool True if we are to display the PSP, false otherwise.
     */
    function withdrawDepositAllowed($user, $alt, $action, $channel = null)
    {
        return $this->doPspByConfig($user, $action, $alt, $channel);
    }

    /**
     * Returns configurations for all PSPs that the user in question are allowed / can use.
     * The most important user attribute here is the user country, all PSP options are limited
     * by which country the user is from.
     *
     * @param DBUser $u_obj The user object>
     * @param string $action Action, deposit or withdraw.
     * @param string|null $channel The channel / device, some options might not be available on mobile and vice versa.
     * @param array|null $psps Optional config array of PSP to filter, if empty we simply get the full array in the CasinoCashier config.
     *
     * @return array The array of PSP configs.
     */
    function getAllAllowedPsps(DBUser $u_obj, string $action, ?string $channel = null, ?array $psps = null): array
    {
        $psps = $psps ?? phiveApp(PspConfigServiceInterface::class)->getPspSetting();

        $ret  = [];
        foreach ($psps as $psp => $config) {
            if ($this->withdrawDepositAllowed($u_obj, $psp, $action, $channel)) {
                if (array_key_exists('option_of', $config)) {
                    $ret[$config['option_of']]['options'][$psp] = $config;
                } else {
                    $ret[$psp] = $config;
                }
            }
        }

        return $ret;
    }

    /**
     * Gets a failover option.
     *
     * If $psp has a directive looking like this: 'failover' => 'trustly' then Trustly's config section will be returned
     * AND stored in a session variable that will be used in CasinoCashier::doPspByConfig().
     *
     * We might end up in a situation where the network does not have a failover clause but the sub_supplier has,
     * that's why we check both and lock on to the first one that has a failover.
     *
     * @param string $supplier The PSP, eg zimplerbank.
     * @param string $sub_supplier The PSP, eg SEB.
     *
     * @return bool|array Array if there is a failover, false otherwise.
     */
    function addAndGetFailover($supplier, $sub_supplier)
    {
        $failover = phiveApp(PspConfigServiceInterface::class)->getPspSetting($supplier)['failover'];
        if (empty($failover)) {
            $failover = phiveApp(PspConfigServiceInterface::class)->getPspSetting($sub_supplier)['failover'];
        }
        if (!empty($failover)) {
            $failover_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting($failover);
            $_SESSION['failover_psps'][$failover] = $failover_config;
            return ['psp' => $failover, 'config' => $failover_config];
        }

        return false;
    }

    /**
     * Wrapper around $this->getSetting('psp_config')[$psp][$action]
     * Use getPspSetting from laraphive this is left for 3rd parameter use case will be removed once logic clean up
     *
     * @param string $psp The PSP.
     * @param string $action The action, deposit or withdraw.
     *
     * @return array The config.
     *
     * @deprecated
     */
    public function getPspSettingDeprecated($psp = null, $action = null, $config_key = null)
    {
        $setting = $this->getSetting($config_key ?? 'psp_config_2');

        if (!empty($psp)) {
            return !empty($action) ? $setting[$psp][$action] : $setting[$psp];
        }

        return $setting;
    }

    /**
     * THI METHOD IS KEEP ONLY TO PREVENT ISSUE ON ADMIN2 DEPLOY WILL GET REMOVE ON NEXT MR
     *
     * Wrapper around $this->getSetting('psp_config')[$psp][$action]
     *
     * @param string $psp The PSP.
     * @param string $action The action, deposit or withdraw.
     *
     * @return array The config.
     *
     * @deprecated
     */
    public function getPspSetting($psp = null, $action = null, $config_key = null)
    {
        $setting = $this->getSetting($config_key ?? 'psp_config_2');

        if (!empty($psp)) {
            return !empty($action) ? $setting[$psp][$action] : $setting[$psp];
        }

        return $setting;
    }

    public function getResolvedPspConfigValue(
        DBUser $user,
        string $configKey,
        $defaultValue,
        string $psp,
        array $pspConfig = null
    ) {
        if (is_null($pspConfig)) {
            $pspConfig = phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp);
        }

        return $pspConfig[$configKey][$user->getCountry()]
            ?? $pspConfig[$configKey]['DEFAULT']
            ?? $pspConfig[$configKey]
            ?? $defaultValue;
    }

    /**
     * Get the sub-suppliers list (PSPs list that uses other network for example: Rapid is the sub-supplier of skrill network)
     *
     * @return array The sub-suppliers list.
     */
    public function getSubSupplierList()
    {
        $list = [];
        $all_setting = $this->getSetting('psp_config_2');

        foreach ($all_setting as $psp => $setting) {
            if (isset($setting['via']['network'])) {
                $list[] = $psp;
            }
        }

        $list = array_diff($list, ['sepa', 'wpsepa']);
        return $list;
    }

    /**
     * Checks if a PSP supports withdrawals.
     *
     * @param string $psp The PSP.
     *
     * @return bool True if yes, false if no.
     */
    public function pspHasWithdraw($psp)
    {
        return phiveApp(PspConfigServiceInterface::class)->getPspSetting($psp, 'withdraw')['active'] === true;
    }

    /**
     * Returns a single PSP options that will be used as a fast deposit by showing its logo prominently here
     * and there, once the logo / button is clicked a special interface will show up where the player can
     * quickly make a deposit via this PSP.
     *
     * @param DBUser $u_obj The user object.
     * @param bool $return_deposit Whether or not to return the whole deposit instead.
     *
     * @return null|string The PSP name in case a fast psp was found, null otherwise.
     */
    public function getFastPsp($u_obj = null, $return_deposit = false)
    {
        $u_obj = cu($u_obj);
        if (empty($u_obj)) {
            return null;
        }

        $country = $u_obj->getCountry();

        if (!in_array($country, $this->getSetting('fast_countries'))) {
            return null;
        }

        if (rgLimits()->reachedType(cu($u_obj), 'net_deposit', 0, true, false)) {
            return null;
        }

        if (phive()->isLocal()) {
            unset($_SESSION['fast_deposit']);
        }

        if (!empty($_SESSION['fast_deposit'])) {
            return $_SESSION['fast_deposit'];
        }

        if (phive()->isLocal()) {
            unset($_SESSION['fast_deposit']);
        }

        if (!isset($_SESSION['fast_deposits'])) {
            $_SESSION['fast_deposits'] = $this->getUserDeposits($u_obj, "ORDER BY `timestamp` DESC", '', 1);
        }

        $psp_config = phiveApp(PspConfigServiceInterface::class)->getPspSetting();

        foreach ($_SESSION['fast_deposits'] as $d) {
            // We try and get by scheme first, eg Skandia via Zimpler.
            $config = $psp_config[$d['scheme']];
            if (!empty($config)) {
                $psp = $d['scheme'];

                $parent_config = $psp_config[$d['dep_type']];

                // We check if the config even exists in the main array, if not it's a CC network and if that
                // is the case we rely on what's configured in the VISA or MC section after all, ie we don't care about which network there
                // and if it's on or not as we just pick whatever is working / turned on for the CCs.
                // Otherwise it could be something like SEB via Swish and we want to respect Swish being turned off.
                if (!empty($parent_config) && !$this->doPspByConfig($u_obj, 'deposit', $d['dep_type'])) {
                    continue;
                }
            } else {
                $config = $psp_config[$d['dep_type']];
                $psp = $d['dep_type'];
            }

            if (empty($config)) {
                continue;
            }

            if (!$this->doPspByConfig($u_obj, 'deposit', $psp)) {
                continue;
            }

            $_SESSION['fast_deposit'] = $psp;

            $res = $return_deposit ? $d : $psp;
            break;
        }

        if (empty($res)) {
            $default = $this->getSetting('preselect_defaults')[$u_obj->getCountry()];
            if (is_array($default)) {
                $default = end($default);
            }
            return $default;
        } else {
            return $res;
        }


        /*
           // TODO remove this if we're still fine with the new "latest deposit is the fast deposit" logic by say Q1 2022. /Henrik
           foreach($this->getPspSetting() as $psp => $config){
           if(!$this->doPspByConfig($u_obj, 'deposit', $psp)){
           continue;
           }
           if(!empty($config['deposit']['fast_countries']) && in_array($country, $config['deposit']['fast_countries'])){
           return $psp;
           }
           }
         */

        return null;
    }

    /**
     * This method uses settings in CasinoCashier.config.php in order to determine if we display the PSP or not.
     *
     * The configs are on this format:
     * ```php
     * 'entercash' => [
     *   'withdraw' => [
     *       'active'             => true,
     *       'excluded_countries' => ['SE', 'FI', 'GB'],
     *       'ideal' => [
     *           'active' => false
     *       ],
     *       'forced_currencies' => [
     *           'SE' => ['SEK'],
     *           'FI' => ['EUR']
     *       ]
     *   ],
     *   'deposit' => [
     *       'active'             => true,
     *       'included_countries' => ['SE', 'FI'],
     *       'forced_currencies' => [
     *           'SE' => ['SEK'],
     *           'FI' => ['EUR']
     *       ]
     *   ]
     * ]
     * ```
     *
     * @link https://wiki.videoslots.com/index.php?title=Videoslots_Cashier
     *
     * @param DBUser $user The user object.
     * @param string $action The action: deposit|withdraw.
     * @param string $psp The PSP.
     * @param string $channel The channel, mobile or desktop.
     * @param string $config_key If passed in it will override the default config key to use.
     *
     * @return boolean True if we are to display the PSP, false otherwise.
     */
    function doPspByConfig($user, $action, $psp, $channel = null, $config_key = null)
    {

        // We have a failover PSP so we must allow it.
        if (!empty($_SESSION['failover_psps'][$psp])) {
            return true;
        }

        $base_config = $this->getPspSettingDeprecated($psp, null, $config_key);

        $current_device = phive()->deviceType();

        // Checking for stuff like Apple and Android Pay.
        $isInvalidDevice = !empty($base_config['devices']) && !in_array($current_device, $base_config['devices']);

        if ($isInvalidDevice && $psp !== 'googlepay') {
            return false;
        }

        if ($isInvalidDevice && $psp === 'googlepay' && (phive()->isIosDevice() || !phive()->isChrome())) {
            return false;
        }

        $config = $base_config[$action];

        if (empty($config)) {
            // If PSP is not configured at all for the action in question we don't show it.
            return false;
        }

        // Some things should not display on mobile and vice versa
        if (!empty($channel)) {
            $channels = $config['channels'] ?? $base_config['channels'];
            if (!empty($channels) && !in_array($channel, $channels)) {
                return false;
            }
        }

        if ($config['active'] !== true) {
            // If PSP is not explicitly turned on we return false.
            return false;
        }

        if (empty($user)) {
            return false;
        }

        $province = $user->getMainProvince();
        $country  = $user->getCountry();
        $currency = $user->getCurrency();
        $reg_date = $user->getAttr('register_date');


        // TODO, remove this when not needed anymore
        $handlePriorTrustlySchemes = function () use ($user, $psp) {

            // This will route everything via Zimpler BANK.
            return false;

            if (empty($this->user_schemes)) {
                $this->user_schemes = $this->getSchemes($user->getId(), 'trustly');
            }

            if (empty($this->user_schemes)) {
                return false;
            }

            $trustly_banks = [
                'sparbanken syd',
                'ica banken',
                'skandiabanken',
                'forex',
                'danske bank',
                'länsförsäkringar'
            ];

            foreach ($trustly_banks as $bank) {
                if (in_array($bank, $this->user_schemes)) {
                    // We show Trustly because zimplerbank is not instant for the above banks.
                    return true;
                }
            }

            return false;
        };


        if ($psp == 'trustly' && $action != 'withdraw') {
            $res = $handlePriorTrustlySchemes();
            if ($res) {
                return $res;
            }
        }

        if (in_array($psp, ['zimplerbank', 'trustly']) && $action == 'withdraw') {
            $res = $handlePriorTrustlySchemes();
            // If Trustly we return the result as is, if psp is zimplerbank we need to do the opposite as we need to "show"
            // one or the other, not both at the same time.
            if ($res) {
                return $psp == 'trustly';
            }
        }

        if (!isset($this->fetched_first_deposit)) {
            $this->first_deposit = $this->getFirstDeposit($user->getId());
            $this->fetched_first_deposit = true;
        }

        // If included_nodes is defined and the user's node is not in the array of allowed nodes we return false.
        // This is to enable gradual rollout of new suppliers, if the supplier replaces an old supplier the old supplier
        // needs to have the rest of the nodes included.
        if (!empty($config['included_nodes'][$country])) {
            // TODO, remove this when not needed anymore
            if (empty($this->first_deposit) || ($this->first_deposit['timestamp'] > '2020-03-26 10:00:00')) {
                return $psp == 'zimplerbank';
            } else {
                return in_array($this->db->getNodeByUserId($user->getId()), $config['included_nodes'][$country]);
            }
        }

        if (!empty($config['excluded_countries_before'][$country])) {
            // Players from this country if they never deposited or their first deposit was done before a certain date
            if (!empty($this->first_deposit) && strtotime($this->first_deposit['timestamp']) < strtotime($config['excluded_countries_before'][$country])) {
                return false;
            }
        }

        if (!empty($config['excluded_countries']) && in_array($country, $config['excluded_countries'])) {
            // If we have included countries configured and user's country IS in the array we return false.
            return false;
        }

        if (!empty($config['excluded_currencies']) && in_array($currency, $config['excluded_currencies'])) {
            // If we have included currencies configured and user's currency IS in the array we return false.
            return false;
        }

        $forced_currencies = $config['forced_currencies'][$country];
        if (!empty($forced_currencies) && !in_array($currency, $forced_currencies)) {
            // If we have forced currencies configured and the user's currency is NOT in the user's country array we return false.
            return false;
        }

        if (!empty($config['included_countries_before'][$country])) {
            // Players from this country if they never deposited or their first deposit was done before a certain date, remove the country from
            // included_countries in this case as this has priority over that setting, otherwise the option will always show for that
            // country which is not what you want.
            if (!empty($this->first_deposit) && strtotime($this->first_deposit['timestamp']) < strtotime($config['included_countries_before'][$country])) {
                return true;
            }
        }

        if (!empty($config['fallback_for_currencies']) && in_array($currency, $config['fallback_for_currencies'])) {
            // If we have fallback currencies configured and user's currency IS in the array we return true,
            // this behaviour overrides all the below filters as it returns true immediately.
            return true;
        }

        if (!empty($config['included_countries']) && !in_array($country, $config['included_countries'])) {
            // If we have included countries configured and user's country is NOT in the array we return false.
            return false;
        }

        if (!empty($config['included_currencies']) && !in_array($currency, $config['included_currencies'])) {
            // If we have included currencies configured and user's currency is NOT in the array we return false.
            return false;
        }

        // Block specific PSPs for players based on specific provinces.
        $excluded_provinces = $config['excluded_provinces'][$country];
        if (!empty($province) && !empty($excluded_provinces) && in_array($province, $excluded_provinces)) {
            // If the user's province is in the list of excluded_provinces of the specified country within specific PSP,
            // then we return false, because we don't let that PSP be used.
            return false;
        }

        // If we manage to get past all the above tests we return true.
        return true;
    }

    /**
     * In case we allow the user to use a different account on the same PSP we have to reset the KYC situation
     * and this method does just that.
     *
     * @param DBUser $user The user object.
     * @param array $data The request that was sent from the MTS.
     * @param string $data_key Which key whose value we're after in the MTS request.
     * @param string $setting The user setting.
     * @param string $pic_key Which document to reset / reject.
     *
     * @return null
     */
    function handlePspAccIdChange($user, $data, $data_key, $setting, $pic_key)
    {
        if (!empty($data['extra'][$data_key])) {
            $new_psp_id = $data['extra'][$data_key];
            $old_psp_id = $user->getSetting($setting);
            $user->setSetting($setting, $new_psp_id);
            if ($new_psp_id != $old_psp_id) {
                // Reject the files from the Skrill document, and set the document to requested
                phive('Dmapi')->rejectAllFilesFromDocument($user->getId(), $pic_key);
            }
        }
    }

    /**
     * Triggers the "onFailedDeposit" ARF checks for the customer
     *
     * @param DBUser|null $user - in case we are outside of DepositStart context (Ex. CashierBoxBase, forcing the
     * @param string|null $action
     */
    public function fireOnFailedDeposit(?DBUser $user = null, ?string $action = null): void
    {
        if (empty($user) || $action !== 'deposit') {
            return;
        }

        phive()->pexec('Cashier/Arf', 'invoke', ['onFailedDeposit', $user->getId()]);
    }

    /**
     * @return int[]
     */
    public function getColsForDailyStats(): array
    {
        return [
            'rewards'                                => 14, // +1
            'paid_loyalty'                           => 31,
            'casino_race'                            => 32, // +2
            'fails'                                  => 15, // -1
            'failed_winnings'                        => 53, // -2
            'chargebacks'                            => 9,
            'sms_fees'                               => 33,
            'inactivity_fee'                         => 43, // -3
            'frb_cost'                               => 51, // +3
            'cash_balance_bonus_credit'              => 66, // +4 reward
            'cash_balance_bonus_debit'               => 67, // -4 failed reward
            //'wager_bonus_credit'                     => 68, // reward
            'wager_bonus_payout_shift'               => 69, // +5  reward
            //'wager_bonus_debit'                      => 70, // failed reward
            //'frb_bonus_credit'                       => 71, // reward
            'frb_bonus_debit'                        => 72, // -5 failed reward
            //'tournament_ticket_credit'               => 73, // reward
            'tournament_ticket_shift'                => 74, // +6 reward
            'tournament_ticket_debit'                => 75, // -6 failed reward
            //'trophy_top_up_credit'                   => 76, // in the future
            'trophy_top_up_shift'                    => 77, // +7 reward
            //'trophy_top_up_debit'                    => 78, // failed reward
            //'trophy_deposit_top_up_credit'           => 79, // reward
            'trophy_deposit_top_up_shift'            => 80, // +8
            //'trophy_deposit_top_up_debit'            => 81, // failed reward
            'zeroing_out_balance'                    => 82, // +9 reward
            //            'tournament_win_after_prize_calculation' => 83,
            'bonus_top_up_cash'                      => 84, // +10 reward
            'tournament_joker_prize'                 => 85, // +11 reward
            'tournament_bounty_prize'                => 86, // +12 reward
            'reactivated_bonus_winnings'             => 90, // +12 reward
            'woj_mini_jackpot'                       => 94, // reward
            'woj_major_jackpot'                      => 95, // reward
            'woj_mega_jackpot'                       => 96, // reward
            'voided_bets'                            => 98, // deduct from bets
            'voided_wins'                            => 99,
            'undone_withdrawals'                     => 103, // deduct from withdrawals
            'tax_deduction'                          => 104,  //
            'tax_deduction_refund'                   => 105  //
        ];
    }

    /**
     * @param $user
     * @return int
     */
    public function getRemoteUserWithdrawDepositSum($user): int
    {
        /** @var DBUser $user */
        $user = cu($user);

        $remote_user_id = $user->getRemoteId();

        if (empty($remote_user_id) || !lic('isCddEnabled', [], $user)) {
            return 0;
        }

        $user_currency = $user->getCurrency();
        $threshold = lic('getLicSetting', ['cross_brand'], $user)['threshold'];

        if (empty($threshold)) {
            return 0;
        }

        $remote_brand = getRemote();

        $response = toRemote(
            $remote_brand,
            'getRemoteUserWithdrawDepositTotal',
            [$remote_user_id, $user_currency]
        );

        if (!$response['success']) {
            phive('UserHandler')->logAction(
                $user,
                "Getting withdrawal-deposit total from {$remote_brand} resulted in {$response['result']}",
                'get-remote-user-withdraw-deposit-total'
            );
            return 0;
        }

        return (int)$response['result'];
    }

    /**
     * Returns the list of values from @see getColsForDailyStats() that count as bonus/rewards
     * @return string[]
     */
    public function getCashTransactionsBonusTypes(): array
    {
        return [
            'rewards',
            'casino_race',
            'frb_cost',
            'cash_balance_bonus_credit',
            'trophy_top_up_shift',
            'trophy_deposit_top_up_shift',
            'wager_bonus_payout_shift',
            'tournament_joker_prize',
            'zeroing_out_balance',
            'bonus_top_up_cash',
            'reactivated_bonus_winnings',
            'tournament_bounty_prize',
            'tournament_ticket_shift',
            'woj_mini_jackpot',
            'woj_major_jackpot',
            'woj_mega_jackpot'
        ];
    }


    /**
     * Every start of the month clear the balance of all players that have been inactive for more than 540 days
     * Excluding Spain
     *
     * @return void
     */
    public function balanceClearanceForInactivePlayers()
    {
        $fileName = 'ClearedPlayerBalance.csv';
        $adminMail =  explode(',', phive('Config')->getValue('auto', 'email_to'));
        $players = $this->clearInactive(Carbon::now()->subDays(540), 'Failed bonus', 15, true, "AND country != 'ES'");
        $f = fopen($fileName, 'w');
        if ($f) {
            fputcsv($f, array('Player ID', 'Balance', 'Currency', 'value of adjustment transaction created'));
            foreach ($players as $player) {
                phive('SQL')->sh($player['id'], '', 'users_comments')->insertArray('users_comments', array(
                    'user_id'         => $player['id'],
                    'comment'         => 'Failed bonus can be restored after completion of EDD on player request. Inform payment and risk about player request.',
                    'sticky'          => 0,
                    'tag'             => '',
                    'foreign_id'      => 0,
                    'foreign_id_name' => 'id'
                ));

                fputcsv($f, array($player['id'], $player['cash_balance'], $player['currency'], -$player['cash_balance']));
            }
            fclose($f);
            $emailId =  phive('MailHandler2')->sendMail('inactive_players_email', cu('admin'), null, null, 'notifications@videoslots.com', $adminMail[0]);
            phive('MailHandler2')->addAttachments($emailId, $fileName, file_get_contents($fileName), 'text/csv');
            unlink($fileName);
        } else {
            echo 'File Error';
        }
    }

    // TODO Pass the value by reference after the actual fix
    // It is just logs a debug data for now. The original record will not be modified.
    private function adjustWithdrawalDeductedAmount(array $p, DBUser $user)
    {
        // Withdrawal fee should be adjusted in case the withdrawals are being approved in an order
        // different from the creation order
        $timestamp = strtotime($p['timestamp']);
        $startOfDay = date('Y-m-d 00:00:00', $timestamp);
        $endOfDay = date('Y-m-d 23:59:59', $timestamp);

        $approvedWithdrawalsCreatedByPlayer = $this->getWithdrawalsInPeriod(
            $p['user_id'],
            $startOfDay,
            "='approved' and user_id=created_by",
            $endOfDay
        );
        $psp = $p['payment_method'];

        // This is required because we store the network name but not the actual psp name
        // as payment method in the pending withdrawals table
        if (empty($p['scheme']) && ($p['iban'] || $p['bank_account_number'])) {
            if ($psp == 'worldpay') {
                $psp = 'wpsepa';
            } elseif ($psp == 'zimpler') {
                $psp = 'zimplerbank';
            }
        }

        if (
            count($approvedWithdrawalsCreatedByPlayer) == 0
            || $this->supplierIsBank($psp)
            || $this->isManualPendingWithdrawal($p)
        ) {
            $p['deducted_amount'] = 0;
            $p['aut_code'] = $p['amount'];
        } else {
            $p['deducted_amount'] = $this->getOutDeduct($p['amount'], $p['payment_method'], $user);
            $p['aut_code'] = $p['amount'] - $p['deducted_amount'];

            if ($p['currency'] == 'GBP' && $p['amount'] - $p['aut_code'] > 250) {
                phive('Logger')->getLogger('payments')->info("INVALID_DEDUCTED_AMOUNT", [
                    'user_id' => $user->getId(),
                    'user_currency' => $user->getCurrency(),
                    'currency' => phive("Currencer")->getCurrency($user->getCurrency()),
                    'mc' => mc(250, $user),
                    'pw' => $p,
                ]);
            }
        }
    }

    public function getBankSuppliers(): array
    {
        return [
            'trustly',
        ];
    }

    public function normalizeDepositScheme(string $str): string
    {
        return str_replace(['ä', 'ö', 'å', 'õ', 'ü', ' '], ['a', 'o', 'a', 'o', 'u', '_'], mb_strtolower($str));
    }

    public function validateBonusCode(string $bonusCode): array
    {
        $reload = phive('Bonuses')->getReload($bonusCode);
        if (empty($reload)) {
            return [
                "status" => "error",
                "message" => t("bonus.no.match"),
            ];
        }

        return [
            "status" => "success",
            "message" => "", // we do not have response message for success
        ];
    }

    public function generateRedirectUrl(): string
    {
        $baseUrl = empty($_GET['show_url']) ? llink('/cashier/deposit/') : $_GET['show_url'];

        $queryParams = [];

        if (!empty($_GET['status'])) {
            $queryParams['status'] = $_GET['status'];
        }

        $queryString = http_build_query($queryParams);

        if (!empty($queryString)) {
            $separator = strpos($baseUrl, '?') === false ? '?' : '&';
            return $baseUrl . $separator . $queryString;
        }

        return $baseUrl;
    }

    public function hasPrepaidDepositLimit(
        DBUser $user,
        int $cents,
        ?string $supplier = null,
        ?string $cardBin = null
    ): array {
        $supplier = $supplier ?? '';
        $cardBin = $cardBin ?? '';

        $limitData = lic(
            'hasPrepaidDepositLimit',
            [$supplier, $cardBin, $user->getId(), $cents],
            $user
        );

        if (!$limitData) {
            return [];
        }

        $cashierCommon = new CashierCommon();
        $cashierCommon->error_action = $limitData['limit'];

        return $cashierCommon->fail(
            'Prepaid deposit limit',
            true,
            $limitData['params'] ?? []
        );
    }
}

/**
 * Uses bank_countries.ded_pc in order to determine if a percentage deduction is to be abpplied,
 * typically used in order to compensate for taxes that have to be paid to the country, eg deducting
 * from affiliate profits to make up for taxes on the GGR (Gross Game Revenue).
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_bank_countries For misc. settings per country.
 *
 * @param int $amount The amount we want to apply the deduction to.
 * @param mixed $u_info User identification information.
 * @param bool $cache Whether or not to cache the query to the bank countries table.
 *
 * @return float The amount to deduct.
 */
function dedPc($amount, $u_info = '', $cache = true)
{
    if (!$cache) {
        unset($GLOBALS['cur_bank_country']);
    }
    if (empty($GLOBALS['cur_bank_country'])) {
        $country                     = cuCountry($u_info);
        //$country                     = empty($u_info) ? phive('UserHandler')->getUsrCountry() : ud($u_info)['country'];
        $GLOBALS['cur_bank_country'] = phive('Localizer')->getBankCountryByIso($country);
        if (empty($GLOBALS['cur_bank_country'])) {
            return $amount;
        }
    }
    return $amount * (1 - $GLOBALS['cur_bank_country']['ded_pc']);
}
