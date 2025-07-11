<?php


namespace App\Classes\GoAML;

use App\Models\FxRate;
use Carbon\Carbon;

use App\Classes\GoAML\Enum\TransactionType;

class Jurisdiction
{
    /**
     * Here we configure the payment types and their code for the jurisdictions
     *
     * @var \string[][]
     */
    private $payment_types = [
        'SE' => [
            'ccard'     => 'KORTT',
            'bank'      => 'KOVER',
            'zimpler'   => 'KOVER',
            'swish'     => 'SWISH',
            'ewallet'   => 'KOVER',
            'siru'      => 'KOVER',
            'wirecard'  => 'KORTT',
            'worldpay'  => 'KORTT',
            'pcard'     => 'KORTT',
            'entercash' => 'KOVER',
            'puggle'    => 'KOVER',
            ''          => 'ANNAN',
        ],
        'MT' => [
            'ccard'     => 'C',
            'bank'      => 'C',
            'zimpler'   => 'C',
            'swish'     => 'C',
            'siru'      => 'C',
            'ewallet'   => 'C',
            'wirecard'  => 'C',
            'worldpay'  => 'C',
            'pcard'     => 'C',
            ''          => '-',
        ]
    ];

    /**
     * @var string[]
     */
    private $default_currency = [
        'MT' => 'EUR'
    ];

    /**
     * Configuration of different codes used on the xml report for each jurisdiction
     *
     * @var array[]
     */
    private $transaction_codes = [
        'SE' => [
            'ccard'     => [
                'funds_code' => 'KORT',
                'funds_code_company' => 'KTERM',
                'personal_account_type' => 'KORT',
                'non_bank' => 1,
            ],
            'pcard'     => [
                'funds_code' => 'KORT',
                'funds_code_company' => 'KTERM',
                'personal_account_type' => 'KORT',
                'non_bank' => 1,
            ],
            'bank'      => [
               'funds_code' => '',
               'funds_code_company' => '',
               'personal_account_type' => 'BKTO',
               'non_bank' => 0,
            ],
            'swish'     => [
                'funds_code' => 'SWISH',
                'funds_code_company' => 'SWISH',
                'personal_account_type' => 'SWISH',
                'non_bank' => 1,
            ],
            'ewallet'   => [
                'funds_code' => '',
                'funds_code_company' => '',
                'personal_account_type' => 'PSP',
                'non_bank' => 1,
            ],
            'mobile'   => [
                'funds_code' => '',
                'funds_code_company' => '',
                'personal_account_type' => 'BKTO',
            ],
            'puggle'   => [
                'funds_code' => '',
                'funds_code_company' => '',
            ],
            'entercash'   => [
                'funds_code' => '',
                'funds_code_company' => '',
                'personal_account_type' => 'BKTO',
            ],
            'unknown'   => [
                'funds_code' => 'ANNAN',
                'funds_code_company' => 'ANNAN',
                'personal_account_type' => 'ANNAN',
                'display_name' => 'ANNAN'
            ],
        ],
        'MT' => [
            'ccard'     => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => '19',
            ],
            'pcard'     => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => '12',
                'non_bank' => 1,
            ],
            'bank'      => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => 5,
                'non_bank' => 0,
            ],
            'swish'     => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => '13',
            ],
            'ewallet'   => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => '21',
            ],
            'mobile'   => [
                'funds_code' => 'B',
                'funds_code_company' => 'B',
                'personal_account_type' => '13',
            ],
            'unknown'   => [
                'funds_code' => '-',
                'funds_code_company' => '-',
                'personal_account_type' => 'U',
                'display_name' => 'UNKNOWN'
            ],
        ]
    ];

    /**
     * Config of nodes activity and/or the database column to get the data from
     *
     * @var array[]
     */
    private $node_options = [
        'SE' => [
            'teller'     => [
                'active' => true,
                'db_column' => 'ip_num'
            ],
            'late_deposit'     => [
                'active' => true,
            ],
            'to_signatory' => [
                'active' => true,
            ],
            'from_signatory' => [
                'active' => true,
            ],
            'swish_number' => [
               'active' => true,
            ],
            'location' => [
                'active' => false,
            ],
            'transaction_description' => [
                'active' => false,
            ],
            'id_number' => [
                'active' => false,
            ],
            'issue_country' => [
                'active' => false,
            ],
            'status_code' => [
                'active' => false,
            ],
            'internal_ref_number' => [
                'active' => true,
                'db_column' => 'ext_id'
            ],
            'is_primary' => [
                'active' => false,
            ],
            'comments' => [
                'active' => false
            ],
        ],
        'MT' => [
            'teller'     => [
                'active' => false,
                'db_column' => ''
            ],
            'late_deposit' => [
                'active' => false,
            ],
            'to_signatory' => [
                'active' => true,
            ],
            'from_signatory' => [
                'active' => true,
            ],
            'swish_number' => [
                'active' => false,
            ],
            'location' => [
                'active' => true,
            ],
            'transaction_description' => [
                'active' => true,
            ],
            'id_number' => [
                'active' => true,
            ],
            'issue_country' => [
                'active' => true,
            ],
            'status_code' => [
                'active' => false,
            ],
            'internal_ref_number' => [
                'active' => false,
                'db_column' => 'ext_id'
            ],
            'is_primary' => [
                'active' => true,
            ],
            'comments' => [
                'active' => true
            ],
            'from_foreign_currency' => [
               'active' => true
            ],
            'foreign_currency_code' => [
                'active' => true
            ],
            'foreign_amount' => [
                'active' => true
            ],
            'foreign_exchange_rate' => [
                'active' => true
            ]
        ]
    ];

    /**
     * Configure the identification types for each jurisdiction
     *
     * @var \string[][]
     */
    private $identifiers_types = [
        'SE' => [
            'gaming' => 'SPELK',
            'role' => 'KONTO',
            'status_code' => 'AKTIV',
            'unknown' => 'ANNAN',
        ],
        'MT' => [
            'gaming' => '23',
            'role' => 'PS',
            'status_code' => 'A',
            'unknown' => '-',
        ]
    ];

    /**
     * Configure the swish numbers for videoslots for the different banks
     *
     * @var \string[][]
     */
    private $swish_number = [
        'swedbank' => 1235094727,
        'seb' =>  1235840350
    ];
    
    /**
     * Configure the strings to identify a missing information for each jurisdiction
     *
     * @var \string[][]
     */
    private $missing_string = [
        'SE' => 'Saknas',
        'MT' => 'Unknown'
    ];

    /**
     * Configure the fields to be fallback from account node
     *
     * @var \string[][]
     */
    private $fallback_accounts = [
        'SE' => [
            'default' => 'ext_id'
        ],
        'MT' => [
            TransactionType::BANK => 'id',
            TransactionType::EWALLET => 'id',
            'adyen' => 'iban',
            'default' => null
        ]
    ];

    /**
     * The jurisdiction currently reporting on
     *
     * @var
     */
    private $selected_jurisdiction;

    /**
     * Create a new jurisdiction instance.
     *
     * @param $jurisdiction
     */
    public function __construct($jurisdiction)
    {
        $this->setPaymentTypes($jurisdiction);
    }
    
    /**
     * Generate needed codes by the payment types the transaction is on for the chosen jurisdiction
     *
     * @param $transaction_type
     * @param $provide
     * @param $transaction
     * @param $type
     * @param $funds_code
     * @param $funds_code_company
     * @param $funds_comment
     * @param $personal_account_type
     * @param $non_bank
     */
    public function generateTransactionCodes($transaction_type, $user, &$provide, $transaction, $type, &$funds_code, &$funds_code_company, &$funds_comment, &$personal_account_type, &$non_bank)
    {
        if ($provide['type'] == 'bank') {
            $funds_code = $this->getTransactionCodes('bank', 'funds_code');
            $funds_code_company = $this->getTransactionCodes('bank', 'funds_code_company');
            $personal_account_type = $this->getTransactionCodes('bank', 'personal_account_type');
            $non_bank = $this->getTransactionCodes('bank', 'non_bank');
        } else {
            if ($provide['type'] == 'ccard' || $provide['type'] == 'pcard') {
                $funds_code = $this->getTransactionCodes($provide['type'], 'funds_code');

                if ($transaction_type == 'worldpay') {
                    $funds_comment = 'VIDEOSLOTSSALES';
                } elseif ($transaction_type == 'adyen') {
                    $funds_comment = 'PandaMediaLtdCOM';
                }
                
                $funds_code_company = $this->getTransactionCodes($provide['type'], 'funds_code_company');
                $personal_account_type = $this->getTransactionCodes($provide['type'], 'personal_account_type');
                $provide['display_name'] = !empty($type) ? $type : $provide['display_name'];

                if ($type == 'mc') {
                    $provide['display_name'] = 'Mastercard';
                }
            } else {
                if ($provide['type'] == 'ewallet' || $transaction_type === 'siru') {
                    $funds_code = $this->getTransactionCodes('ewallet', 'funds_code');
                    $funds_code_company = $this->getTransactionCodes('ewallet', 'funds_code_company');
                    $personal_account_type = $this->getTransactionCodes('ewallet', 'personal_account_type');
                } else {
                    if ($provide['type'] == 'mobile') {

                        if ($transaction_type === 'zimpler') {
                            $funds_code = $this->getTransactionCodes('mobile', 'funds_code');
                            $funds_code_company = $this->getTransactionCodes('mobile', 'funds_code_company');
                            $personal_account_type = $this->getTransactionCodes('mobile', 'personal_account_type');
                        }

                        if ($transaction_type === 'swish') {
                            $funds_code = $this->getTransactionCodes('swish', 'funds_code');
                            $funds_code_company = $this->getTransactionCodes('swish', 'funds_code_company');
                            $personal_account_type = $this->getTransactionCodes('swish', 'personal_account_type');
                            $this->prepareSwish($user, $funds_comment, $transaction);
                        }

                        if ($transaction_type === 'puggle') {
                            $funds_code = $this->getTransactionCodes('puggle', 'funds_code');
                            $funds_code_company = $this->getTransactionCodes('puggle', 'funds_code_company');
                        }

                    } else {
                        $funds_code = $this->getTransactionCodes('unknown', 'funds_code');
                        $funds_code_company = $this->getTransactionCodes('unknown', 'funds_code_company');
                        $personal_account_type = $this->getTransactionCodes('unknown', 'personal_account_type');

                        if (empty($provide['display_name'])) {
                            $provide['display_name'] = $this->fallBackDisplayName($transaction_type, $transaction) ?? $this->getTransactionCodes('unknown', 'display_name');
                        }
                    }
                }
            }
        }
    }

    /**
     * Return fallback to display names for the current jurisdiction
     * @param $transactionType
     * @param $transaction
     * @return mixed
     */
    public function fallBackDisplayName($transactionType, $transaction)
    {
        $display_name = $transactionType == 'deposit' ? $transaction->dep_type : $transaction->payment_method;
        return $display_name ?? $transaction->supplier;
    }

    /**
     * Prepare the swish funds_comment if we have a swish we want to separate
     * the client swish from our reporting entities swish
     *
     * @param $user
     * @param $funds_comment
     * @param $transaction
     */
    private function prepareSwish($user, &$funds_comment, $transaction)
    {
        if ($this->isNodeActive('swish_number')) {
            if ($transaction->payment_method) {
                $provide = phive('CasinoCashier')->getPspSettingFromDisplayName($transaction->bank_name);
                $entity_number = $this->swish_number[$provide[0]];
            } else {
                $entity_number = $this->getEntitiesSwishNumber($transaction);
            }
            $funds_comment['swish_client'] = ($user->repo->getSetting('swish_mobile')) ? $user->repo->getSetting('swish_mobile') : $user->mobile;
            $funds_comment['swish_reporting_entity'] = $entity_number;
        } else {
            $funds_comment = ($user->repo->getSetting('swish_mobile')) ? $user->repo->getSetting('swish_mobile') : $user->mobile;
        }
    }

    /**
     * Get the entities swish if there is no payment method we fallback to SEB as that was the default
     * before May 2020
     *
     * @param $transaction
     * @return int|string[]
     */
    public function getEntitiesSwishNumber($transaction)
    {
        $payment_method = $transaction->scheme ?? $transaction->supplier;

        $method = ($payment_method != 'swish') ? $payment_method : $transaction->sub_supplier;

        return $this->swish_number[$method] ?? $this->swish_number['seb'];
    }

    /**
     * Return the payment type code for the current jurisdiction
     *
     * @param $type
     * @return string
     */
    public function getPaymentType($type)
    {
        return $this->payment_types[$this->selected_jurisdiction][$type];
    }

    /**
     * Return the transaction codes for the current jurisdiction
     *
     * @param $type
     * @param $code
     * @return mixed
     */
    public function getTransactionCodes($type, $code)
    {
        return $this->transaction_codes[$this->selected_jurisdiction][$type][$code];
    }

    /**
     * Return the current jurisdiction reporting on
     *
     * @return mixed
     */
    public function getSelectedJurisdiction()
    {
        return $this->selected_jurisdiction;
    }

    /**
     * Return identifier type code for the selected jurisdiction and type
     *
     * @param $type
     * @return string
     */
    public function getTypes($type)
    {
        return $this->identifiers_types[$this->selected_jurisdiction][$type];
    }

    /**
     * Return the missing string code for the selected jurisdiction
     *
     * @return string|string[]
     */
    public function getIsMissingString()
    {
        return $this->missing_string[$this->selected_jurisdiction];
    }

    /**
     * Set the payment types to the current jurisdiction
     *
     * @param $jurisdiction
     */
    public function setPaymentTypes($jurisdiction)
    {
        $this->selected_jurisdiction = $jurisdiction;
    }

    /**
     * Return the nodes settings for the given node
     *
     * @param $node
     * @return mixed
     */
    public function nodeSettings($node)
    {
        return $this->node_options[$this->selected_jurisdiction][$node]['db_column'];
    }

    /**
     * Return if the given node is active
     *
     * @param $node
     * @return bool
     */
    public function isNodeActive($node)
    {
        if ($this->node_options[$this->selected_jurisdiction][$node]['active']) {
            return true;
        }
        return false;
    }

    /**
     * Return the fallback for account node when the correct value is empty
     * @param $transaction
     * @param $type
     * @param $dep_type_or_payment_method
     * @return mixed|string|string[]
     */
    public function getFallbackAccount($transaction, $type, $dep_type_or_payment_method)
    {
        if ($this->isMobileAndBank($type, $dep_type_or_payment_method)) {
            $type = TransactionType::BANK;
        }

        $fallback_accounts = $this->fallback_accounts[$this->selected_jurisdiction];
        $fallback = $fallback_accounts[$dep_type_or_payment_method] ?? $fallback_accounts[$type];
        $fallback = $fallback ?? $fallback_accounts['default'];

        if (empty($fallback)) {
            return $this->getIsMissingString();
        }

        return !empty($transaction->$fallback) ? $transaction->$fallback : $transaction->id;
    }

    /**
     * Return if is a mobile and bank payment
     * @param $type
     * @param $dep_type_or_payment_method
     * @return bool
     */
    public function isMobileAndBank($type, $dep_type_or_payment_method)
    {
        return $type === 'mobile' && $dep_type_or_payment_method === 'zimpler';
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getPersonalAccountType($type)
    {
        return $this->transaction_codes[$this->selected_jurisdiction][$type]['personal_account_type'] ?? null;
    }

    /**
     * @param $transaction
     * @return float|int
     */
    public function getTransactionAmount($transaction)
    {
        if (empty($this->default_currency[$this->selected_jurisdiction])) {
            return nfCents($transaction->amount, true, '.', '');
        }
        return nfCents($transaction->amount / ($transaction->multiplier ?? 1), true, '.', '');
    }

    /**
     * @param $currency
     * @return string
     */
    public function getDefaultCurrency($currency)
    {
        return $this->default_currency[$this->selected_jurisdiction] ?? $currency;
    }
}
