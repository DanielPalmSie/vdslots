<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 18/02/2016
 * Time: 15:27
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Classes\LegacyWithdrawal;
use App\Classes\Mts;
use App\Extensions\Database\Eloquent\Builder;
use App\Helpers\DataFormatHelper;
use App\Helpers\DateHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\URLHelper;
use App\Models\Config;
use App\Models\Deposit;
use App\Models\EmailQueue;
use App\Models\IpLog;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\ManualAdjustmentFlag;
use Videoslots\HistoryMessages\DepositHistoryMessage;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;

class TransactionsRepository
{
    /** @var Application $app */
    protected $app;

    /** @var  User $user */
    public $user;

    /** @var  Mts $mts */
    public $mts;

    const NETELLER = 'neteller';

    const SKRILL = 'skrill';

    const PAYPAL = 'paypal';

    const SUSPICIOUS_CASES_LIMIT_CONFIG_NAME = 'funds-transfer-to-same-user-per-month-alert';

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->mts = new Mts($app);
    }

    public function addManualAdjustmentFlag(User $target, $amount, $currency, $type, $description, $internal_id, $actor = null)
    {
        $now = Carbon::now()->toDateTimeString();

        if (!$target->repo->hasSetting('manual_adjustment-fraud-flag')) {

            switch ($type) {
                case 'deposit':
                    $event = AssignEvent::ON_DEPOSIT_START;
                    break;
                case 'withdrawal';
                    $event = AssignEvent::ON_WITHDRAWAL_START;
                    break;
                default:
                    $event = AssignEvent::ON_CASH_TRANSACTION;
            }

            ManualAdjustmentFlag::create($internal_id)->assign(
                cu($target->id),
                $event,
                ['source' => 'backoffice']
            );
        }

        if (empty($actor)) {
            $actor = UserRepository::getCurrentUser();
        }

        $subject = "Manual cash adjustment notification";
        $body = "<div> <p>Manual cash adjustment done on a customer account.</p> <p>Details:</p> ";
        $body .= "<ul><li><b>Actor:</b> " . URLHelper::printUserProfileLink($this->app, $actor->username) . "</li>";
        $body .= "<li><b>Amount:</b> " . DataFormatHelper::nf($amount) . " {$currency}</li>";
        $body .= "<li><b>Target: </b> " . URLHelper::printUserProfileLink($this->app, $target->username) . "</li>";
        $body .= "<li><b>Internal ID: </b> {$internal_id}</li>";
        $body .= "<li><b>Type:</b> {$type}</li><li><b>Description:</b> {$description}</li><li><b>Timestamp:</b> {$now}</li>";
        $body .= "</ul></div>";

        EmailQueue::sendInternalNotification($subject, $body, Config::getValue('manual-adjustment', 'emails', 'alexander@videoslots.com, wesley.self@videoslots.com, mattias@videoslots.com, david.cutajar@videoslots.com, payments@videoslots.com', false, true));
    }

    /**
     * TODO create a proper validator
     * TODO port core functionality
     * PHIVE: function depositCash($username, $cents, $type, $ext_id, $scheme = '', $card_hash = '', $loc_id = '', $deduct = false, $status = 'approved', $deduct_cents = null, $mts_id=0, $display_name = '')
     * @param Request $request
     * @param User $user
     * @param $methods
     * @return array
     */
    public function addDeposit(Request $request, User $user, $methods)
    {
        $form_fields = $request->request->all();

        if (!is_numeric($form_fields['amount'])) {
            return ['success' => false, 'message' => 'Deposit failed. Amount is not numeric.'];
        } elseif (empty($form_fields['dep_type']) || !in_array($form_fields['dep_type'], $methods)) {
            return ['success' => false, 'message' => 'Deposit failed. Invalid deposit type.'];
        } elseif (empty($form_fields['ext_id'])) {
            return ['success' => false, 'message' => 'Deposit failed. External Id cannot by empty.'];
        } elseif (!empty($form_fields['ext_id']) && Deposit::where(['ext_id' => $form_fields['ext_id'], 'dep_type' => $form_fields['dep_type']])->count() > 0) {
            return ['success' => false, 'message' => 'Deposit failed. Ext id already found at the database'];
        } else {
            $res = $this->validateManualDeposit($form_fields);
            if ($res['success'] === false) {
                return $res;
            } elseif ($res['action'] == 'reset-scheme') {
                unset($form_fields['scheme']);
            }
            $actor = UserRepository::getCurrentUser();
            if ($this->checkManualDailyLimit($user, $actor, $form_fields['amount']) === true) {
                return ['success' => false, 'message' => 'Daily transfer limit exceeded'];
            }
            $user_legacy = phive('UserHandler')->getUser($user->id);
            $qf = phive('QuickFire');

            $display_name = '';

            if ($form_fields['dep_type'] == 'paymentiq'){
                $display_name = ucfirst($form_fields['scheme']);
            }

            $result = $qf->depositCash($user_legacy, $form_fields['amount'], $form_fields['dep_type'], $form_fields['ext_id'], $form_fields['scheme'], $form_fields['card_hash'], '', false, 'approved', null, 0, $display_name);
            if ($result !== false) {
                $log_message = $actor->username . " deposited {$form_fields['amount']} {$user->currency} cents to {$user->username}";
                IpLog::logIp($actor, $user, IpLog::TAG_CASH_TRANSACTIONS, $log_message, $qf->cur_tr_id);
                IpLog::logIp($actor, $user, IpLog::TAG_DEPOSITS, $log_message, $qf->did);
                //todo add description from the form?
                $this->addManualAdjustmentFlag($user, $form_fields['amount'], $user->currency, 'deposit', $log_message, $qf->did, $actor);

                $msg_args = [];

                try {
                    $msg_args = [
                        'transaction_id' => 0,
                        'user_id' => (int) $user->id,
                        'reference_id' => (string)$form_fields['ext_id'],
                        'amount' => (int)$form_fields['amount'],
                        'currency' => $user->currency,
                        'sub_supplier' => (string) $form_fields['scheme'] ?? '',
                        'supplier' => (string) $form_fields['dep_type'],
                        'supplier_display' => (string) $display_name ?? '',
                        'card_num' => (string) $form_fields['card_hash'] ?? '',
                        'card_id' => 0,
                        'card_duplicate' => [],
                        'type' => (string) $form_fields['dep_type'] ?? '',
                        'new_card' => null,
                        'extra' => ['scheme' => $form_fields['scheme']],
                        'ip' => $user_legacy->getAttr('cur_ip'),
                        'event_timestamp' => Carbon::now()->timestamp
                    ];

                    /** @uses \Licensed::addRecordToHistory() */
                    lic('addRecordToHistory',
                        [
                            'deposit',
                            new DepositHistoryMessage($msg_args)
                        ], $user_legacy);
                } catch (InvalidMessageDataException $e) {
                    $this->app['monolog']->addError(json_encode($e->getErrors()), $msg_args);
                } catch (\Exception $e) {
                    $this->app['monolog']->addError($e->getMessage(), $msg_args);
                }

                return ['success' => true, 'message' => 'Deposit successful.'];
            } else {
                return ['success' => false, 'message' => 'Deposit failed.'];
            }
        }
    }

    private function checkManualDailyLimit(User $user, User $actor, $new_amount)
    {
        $bindings = [
            'start_date' => Carbon::now()->startOfDay()->toDateTimeString(),
            'actor' => $actor->id,
            'tag' => IpLog::TAG_CASH_TRANSACTIONS
        ];

        $acc = DB::shsAggregate('ip_log', "SELECT ROUND(sum(ct.amount/c.multiplier)/100, 2) AS sum_amount
                                            FROM ip_log
                                              LEFT JOIN cash_transactions ct ON ct.id = ip_log.tr_id
                                              LEFT JOIN currencies c ON c.code = ct.currency
                                            WHERE actor = :actor AND tag = :tag
                                              AND ip_log.created_at >= :start_date", $bindings)[0];

        $config_amount = Config::getValue('credit-daily-limit-eur', 'manual-adjustments', 10000, true);

        if ((float)($acc->sum_amount + ($new_amount / 100)) > (float)mc($config_amount, cu($user->getKey()))) {
            return true;
        }

        return false;
    }

    public function checkCancellationsCount(User $user, User $actor, $type = IpLog::TAG_CANCEL_WITHDRAWAL)
    {
        $bindings = [
            'start_date' => Carbon::now()->startOfDay()->toDateTimeString(),
            'actor' => $actor->id,
            'tag' => $type
        ];

        $count = DB::shsAggregate('ip_log', "SELECT count(*) as cnt
                                            FROM ip_log
                                            WHERE actor = :actor AND tag = :tag
                                              AND ip_log.created_at >= :start_date", $bindings)[0]->cnt;

        $config = Config::getValue('cancel-withdrawals-daily-limit', 'manual-adjustments', 10, true);

        return (int)$count > (int)$config ? true : false;
    }

    public function insertPendingWithdrawal(Request $request, User $user)
    {
        $form_fields = $request->request->all();
        $lw = new LegacyWithdrawal($this->app);
        $dev_mode = false;

        if (isset($form_fields['insert-withdrawal-no-docs']) && p('user.create.withdrawal.no.docs.verified')) {
            $lw->bypassDocumentsCheck();
        }

        if ($this->app['env'] === 'dev' && isset($form_fields['insert-withdrawal-test-dev'])) {
            $lw->bypassDocumentsCheck();
            $dev_mode = true;
        }

        $method = lcfirst(str_replace(' ', '', $form_fields['payment_method']));

        if (in_array($method, $lw::$supported_methods)) {
            $res = $lw->{$method}($user->getKey(), $request);
            if ($res && $dev_mode !== true) {
                $actor = UserRepository::getCurrentUser();
                $log_message = $actor->username . " inserted a {$form_fields['amount']} {$user->currency} cents pending withdrawal to {$user->username}";

                if ($lw->cashTransactionId) {
                    IpLog::logIp($actor, $user, IpLog::TAG_CASH_TRANSACTIONS, $log_message, $lw->cashTransactionId);
                }

                IpLog::logIp($actor, $user, IpLog::TAG_MANUAL_WITHDRAWAL, $log_message, $lw->pendingWithdrawalId);

                //todo add description from the form?
                $this->addManualAdjustmentFlag($user, $form_fields['amount'], $user->currency, 'withdrawal', $log_message, $lw->pendingWithdrawalId, $actor);
            }

            return $lw->result;
        } else {
            return [
                'success' => false,
                'message' => 'Method not supported'
            ];
        }
    }

    public function transferCash(Request $request, User $user)
    {
        $form_fields = $request->request->all();
        if (!is_numeric($form_fields['amount'])) {
            return ['success' => false, 'message' => 'Amount is not numeric.'];
        } elseif (empty($form_fields['transactiontype'])) {
            return ['success' => false, 'message' => 'Transaction type cannot be an empty field'];
        } elseif ($user->cash_balance < -(int)$form_fields['amount']) {
            return ['success' => false, 'message' => 'Cash balance is lower than the transferred amount'];
        } else {
            $actor = UserRepository::getCurrentUser();
            if ($this->checkManualDailyLimit($user, $actor, $form_fields['amount']) === true) {
                return ['success' => false, 'message' => 'daily transfer limit exceeded'];
            }
            $to = cu($user->getKey());
            $tr_id = phive('Cashier')->transactUser(
                $to,
                $form_fields['amount'],
                $form_fields['description'],
                null,
                null,
                $form_fields['transactiontype'],
                false
            );

            $log_message = "{$actor->username} deposited {$form_fields['amount']} {$user->currency} cents to {$user->username}";

            IpLog::logIp($actor, $user, IpLog::TAG_CASH_TRANSACTIONS, $log_message, $tr_id);
            ActionRepository::logAction($user, $log_message, 'money_transfer', false, $actor);
            $this->addManualAdjustmentFlag(
                $user,
                $form_fields['amount'],
                $user->currency,
                DataFormatHelper::getCashTransactionsTypeName($form_fields['transactiontype']),
                $form_fields['description'],
                $tr_id,
                $actor
            );

            $notification = new NotificationRepository($this->app);
            $notification->checkForExceededAllowedAction($user, $actor, "cash");

            return ['success' => true, 'message' => ''];
        }
    }

    /**
     * TODO I know that is a shitty function. this one should be refactored as addDeposit
     * @param array $form_fields
     * @return array
     */
    private function validateManualDeposit($form_fields)
    {
        $res['success'] = true;
        switch ($form_fields['dep_type']) {
            case 'wirecard':
                if (empty($form_fields['scheme'])) {
                    $res = ['success' => false, 'message' => 'Deposit failed. Scheme/Bank name/Subtype (VISA, MC,...) cannot be empty for a Wirecard deposit.'];
                }
                break;
            case 'emp':
                if (empty($form_fields['scheme'])) {
                    $res = ['success' => false, 'message' => 'Deposit failed. Scheme/Bank name/Subtype (VISA, MC,...) cannot be empty for a Emp deposit.'];
                }
                break;
            case 'entercash':
                if (empty($form_fields['scheme'])) {
                    $res = ['success' => false, 'message' => 'Deposit failed. Scheme/Bank name/Subtype cannot be empty for a Entercash deposit.'];
                }
                break;
            case 'trustly':
                if (empty($form_fields['scheme'])) {
                    $res = ['success' => false, 'message' => 'Deposit failed. Scheme/Bank name/Subtype cannot be empty for a Trustly deposit.'];
                }
                break;
            case 'paymentiq':
                if (empty($form_fields['scheme'])) {
                    $res = ['success' => false, 'message' => 'Deposit failed. Scheme/Bank name/Subtype cannot be empty for a PaymentIQ deposit.'];
                }
                break;
            default:
                $res['action'] = 'reset-scheme';
        }
        return $res;
    }

    /**
     * Temporal solution until we dev a form builder to be able to manage easily frankenstein's forms
     *
     * @param User $user
     * @return array
     */
    public function getInsertWithdrawalForm(User $user)
    {
        $last_bank = $user->withdrawals()->where('payment_method', 'bank')->where('status', 'approved')->orderBy('id', 'desc')->first();
        $last_citadel = $user->withdrawals()->where('payment_method', 'citadel')->where('status', 'approved')->orderBy('id', 'desc')->first();
        $last_entercash = $user->withdrawals()->where('payment_method', 'entercash')->where('status', 'approved')->orderBy('id', 'desc')->first();
        $last_inpay = $user->withdrawals()->where('payment_method', 'inpay')->where('status', 'approved')->orderBy('id', 'desc')->first();
        $eco_account = $user->withdrawals()->where('payment_method', 'ecopayz')->where('status', 'approved')->orderBy('id', 'desc')->first()['net_account'];
        $last_bank['nid'] = $last_bank['nid'] ?? $user->nid;
        $last_inpay['nid'] = $last_inpay['nid'] ?? $user->nid;

        $res = [
            'neteller' => [
                [
                    'name' => 'net_account',
                    'label' => 'Account number',
                    'placeholder' => 'Neteller acc. number',
                    'more' => '',
                    'value' => $user->repo->getSetting('net_account')
                ]
            ],
            'skrill' => [
                [
                    'name' => 'mb_email',
                    'label' => 'Skrill email',
                    'placeholder' => '',
                    'value' => $user->repo->getSetting('mb_email')
                ]
            ],
            'muchbetter' => [
                [
                    'name' => 'muchbetter_mobile',
                    'label' => 'Mobile Number',
                    'placeholder'=> 'MuchBetter acc. number',
                    'more' => '',
                    'value' => $user->repo->getSetting('muchbetter_mobile')
                ]
            ],
            'cubits' => [
                [
                    'name' => 'btc_address',
                    'label' => 'Bitcoin Address',
                    'placeholder' => '',
                    'value' => $user->repo->getSetting('btc_address')
                ]
            ],
            'bank' => [
                [
                    'name' => 'bank_city',
                    'label' => 'Bank city',
                    'placeholder' => '',
                    'value' => @$last_bank['bank_city']
                ],
                [
                    'name' => 'swift_bic',
                    'label' => 'SWIFT / BIC',
                    'placeholder' => '',
                    'value' => @$last_bank['swift_bic'],
                    'validation' => 'swift_bic-no-space'
                ],
                [
                    'name' => 'bank_account_number',
                    'label' => 'Account Number (if non-SEPA or otherwise required)',
                    'placeholder' => '',
                    'more' => '',
                    'value' => '',
                    'validation' => 'bank_account_number-no-space'
                ],
                [
                    'name' => 'bank_clearnr',
                    'label' => 'Bank clearing system ID (if required by the provider)',
                    'placeholder' => 'Bank clearing number',
                    'value' => $last_bank['bank_clearnr'] ?? null
                ],
                [
                    'name' => 'nid',
                    'label' => 'National ID number (if required by the provider)',
                    'placeholder' => 'National ID number',
                    'value' => $last_bank['nid'] ?? null
                ],
                [
                    'name' => 'bank_name',
                    'label' => 'Bank Name (if required by the provider, required by mifinity)',
                    'placeholder' => '',
                    'value' => ''
                ],
                [
                    'name' => 'actual_payment_method',
                    'label' => 'Main Provider',
                    'placeholder' => '',
                    'value' => ''
                ],
                [
                    'name' => 'scheme',
                    'label' => 'Sub provider (ex: payanybank for mifinity, leave empty otherwise)',
                    'placeholder' => '',
                    'value' => '',
                    'validation' => 'scheme-no-space'
                ],
                [
                    'name' => 'iban',
                    'label' => 'IBAN (if SEPA or otherwise required)',
                    'placeholder' => '',
                    'more' => '',
                    'value' => @$last_bank['iban'],
                    'validation' => 'iban-no-space'
                ]
            ],
            'inpay' => [
                [
                    'name' => 'bank_name',
                    'label' => 'Bank name',
                    'placeholder' => '',
                    'value' => $last_inpay['bank_name'] ?? null
                ],
                [
                    'name' => 'iban',
                    'label' => 'IBAN',
                    'placeholder' => '',
                    'more' => '',
                    'value' => $last_inpay['iban'] ?? null
                ],
                [
                    'name' => 'bank_account_number',
                    'label' => 'Account number',
                    'placeholder' => '',
                    'more' => '',
                    'value' => $last_inpay['bank_account_number'] ?? null
                ],
                [
                    'name' => 'bank_clearnr',
                    'label' => 'Bank clearing system ID',
                    'placeholder' => 'Bank clearing number',
                    'value' => $last_inpay['bank_clearnr'] ?? null
                ],
                [
                    'name' => 'nid',
                    'label' => 'National ID number',
                    'placeholder' => 'National ID number',
                    'value' => $last_inpay['nid'] ?? null
                ],
                [
                    'name' => 'swift_bic',
                    'label' => 'SWIFT / BIC',
                    'placeholder' => '',
                    'value' => $last_inpay['swift_bic'] ?? null
                ],
            ],
            'citadel' => [
                [
                    'name' => 'bank_name',
                    'label' => 'Bank name',
                    'placeholder' => 'Can be empty',
                    'value' => @$last_citadel['bank_name']
                ],
                [
                    'name' => 'bank_code',
                    'label' => 'Bank code',
                    'placeholder' => '',
                    'value' => @$last_citadel['bank_code']
                ],
                [
                    'name' => 'bank_clearnr',
                    'label' => 'Bank Branch Code',
                    'placeholder' => 'Bank clearing number',
                    'value' => @$last_citadel['bank_clearnr']
                ]
            ],
            'entercash' => [
                [
                    'name' => 'bank_name',
                    'label' => 'Bank name',
                    'placeholder' => 'Optional',
                    'value' => @$last_entercash['bank_name']
                ]
            ],
            'instadebit' => [],
            'wirecard' => [],
            'adyen' => [],
            'worldpay' => [],
            'credorax' => [],
            'ecopayz' => [
                [
                    'name' => 'account_number',
                    'label' => 'ecoPayz account number',
                    'placeholder' => '',
                    'value' => @$eco_account
                ]
            ],
            'paypal' => [
                [
                    'name' => 'ppal_email',
                    'label' => 'Paypal email',
                    'placeholder' => '',
                    'value' => $user->repo->getSetting('paypal_email')
                ]
            ],
        ];

        if ($user->country == 'SE') {
            $res['entercash'] = array_merge($res['entercash'], [
                [
                    'name' => 'clearnr',
                    'label' => 'Sort code',
                    'placeholder' => '',
                    'value' => @$last_entercash['bank_clearnr']
                ],
                [
                    'name' => 'bank_account_number',
                    'label' => 'Account number',
                    'placeholder' => '',
                    'value' => @$last_entercash['bank_account_number']
                ]
            ]);
        } else {
            $res['entercash'] = array_merge($res['entercash'], [
                [
                    'name' => 'swift_bic',
                    'label' => 'SWIFT / BIC',
                    'placeholder' => '',
                    'value' => @$last_entercash['swift_bic']
                ],
                [
                    'name' => 'iban',
                    'label' => 'IBAN',
                    'placeholder' => '',
                    'value' => @$last_entercash['iban']
                ]
            ]);
        }

        if ($user->country == 'CA') {
            $res['bank'] = array_merge($res['bank'], [
                [
                    'name' => 'bank_code',
                    'label' => 'Financial Institution Number',
                    'placeholder' => '',
                    'value' => ''
                ], [
                    'name' => 'bank_clearnr',
                    'label' => 'Bank Sort Code',
                    'placeholder' => '',
                    'value' => ''
                ],
            ]);
        }

        if (in_array($user->country, phive('Cashier')->getSetting('psp_config_2')['zimplerbank']['withdraw']['included_countries'])) {
            $res['zimpler'] = array();
        }

        $res['Trustly Account Payout'] = [
            [
                'name' => 'account',
                'label' => 'Bank Account',
                'placeholder' => '',
                'value' => ''
            ]
        ];

        $res['swish'] = [
            [
                'name' => 'account',
                'label' => 'Mobile Number',
                'placeholder' => '',
                'value' => ''
            ]
        ];

        if (phive('UserHandler')->userIsEu(phive('UserHandler')->getUser($user->id))) {
            $res['citadel'][] = [
                'name' => 'iban',
                'label' => 'IBAN',
                'placeholder' => '',
                'more' => '',
                'value' => @$last_citadel['iban']
            ];
        } else {
            $res['citadel'][] = [
                'name' => 'bank_account_number',
                'label' => 'Account number',
                'placeholder' => '',
                'more' => '',
                'value' => @$last_citadel['bank_account_number']
            ];
        }

        return $res;
    }

    public function getInsertWithdrawalBankProviders(): array
    {
        return [
            'entercash',
            'citadel',
            'cubits',
            'inpay',
            'instadebit',
            'mifinity',
            'worldpay',
            'zimpler',
        ];
    }

    public function setCardsStatus($user, $cards){
        for ($i = 0; $i <= $cards.count(); $i++){
            $status = phive('Dmapi')->checkDocumentStatus($user->id, 'creditcardpic', '', $cards[$i]['id']);
            if($status == 'deactivated') {
                $cards[$i]['active'] = 0;
            } else{
                $cards[$i]['active'] = 1;
            }
        }
        return $cards;
    }

    public function getTransferCashSelectList()
    {
        $full_list = DataFormatHelper::getCashTransactionsTypeName();
        $full_list[3] = 'Deposit (warning, you probably want to use Add Deposit instead!!)';
        $full_list[9] = 'Chargeback (make sure the amount is negative, ex: -1000)';

        //return array_slice($full_list, 0, 35, true);
        return $full_list;
    }

    public static function getDepositMethods($as_array = false)
    {
        $types = Deposit::selectRaw('DISTINCT dep_type')->get()->unique('dep_type');
        if ($as_array) {
            $res = [];
            foreach ($types as $dep_type) {
                $res[] = $dep_type->dep_type;
            }
            return $res;
        } else {
            return $types;
        }
    }

    public static function getMethods(
        Application $app,
        Request     $request,
        ?string     $type = null,
        ?int        $userId = null
    ): array
    {
        return self::getMethodsAndSubMethods($app, $request, $type, null, $userId);
    }

    public static function getSubMethods(
        Application $app,
        Request     $request,
        ?string     $type = null,
        ?string     $method = null,
        ?int        $userId = null
    ): array
    {
        return self::getMethodsAndSubMethods($app, $request, $type, $method, $userId);
    }

    public static function getMethodsAndSubMethods(
        Application $app,
        Request     $request,
        ?string     $type = null,
        ?string     $method = null,
        ?int        $userId = null
    ): array
    {
        $type = $request->get('type') ?? $type;
        $method = $request->get('method') ?? $method;
        $userId = $request->get('user_id') ?? $userId;

        $queryDep = empty($userId) ? DB::table('deposits') : DB::shTable($userId, 'deposits');
        $queryWd = empty($userId) ? DB::table('pending_withdrawals') : DB::shTable($userId, 'pending_withdrawals');

        $paymentFilterService = $app['payments_method_submethod_filter_service'];

        // When a specific method is provided, we proceed to retrieve its subMethods.
        if ($method) {
            [$depositSubMethodColumn, $withdrawalSubMethodColumn] = $paymentFilterService->columnUsedForSubMethod($method);

            $queryDep->selectRaw('DISTINCT ' . $depositSubMethodColumn . ' COLLATE utf8_general_ci AS m')
                ->where('dep_type', $method);
            $queryWd->selectRaw('DISTINCT ' . $withdrawalSubMethodColumn . ' COLLATE utf8_general_ci AS m')
                ->where('payment_method', $method);
        } else {
            $queryDep->selectRaw('DISTINCT dep_type AS m');
            $queryWd->selectRaw('DISTINCT payment_method AS m');
        }

        $process = function ($array) {
            return collect($array)->unique('m')->pluck('m')->sort()->reject(function ($name) {
                return empty($name);
            })->all();
        };

        switch ($type) {
            case 'deposits':
                $optionsFromDB = $process($queryDep->get());
                $optionsFromConfig = $paymentFilterService->getMethodsAndSubMethodsFromConfig($method, 'deposit');
                break;

            case 'withdrawals':
                $optionsFromDB = $process($queryWd->get());
                $optionsFromConfig = $paymentFilterService->getMethodsAndSubMethodsFromConfig($method, 'withdraw');
                break;

            default:
                $optionsFromDB = $process($queryDep->union($queryWd)->get());
                $optionsFromConfig = $paymentFilterService->getMethodsAndSubMethodsFromConfig($method);
                break;
        }

        $paymentFilterService->filterMethodAndSubMethodData(
            $optionsFromDB,
            $method ? 'dbSubMethod' : 'dbMethod',
            $method
        );

        $allOptions = array_merge(
            $optionsFromDB,
            $method
                ? array_values($optionsFromConfig)
                : array_keys($optionsFromConfig)
        );

        $uniqueOptions = array_unique($allOptions);
        sort($uniqueOptions);

        return $uniqueOptions;
    }

    public static function getWithdrawalDetails($w)
    {
        $res = '';
        !empty($w->net_email) ? $res .= $w->net_email . ' ' : null;
        !empty($w->net_account) ? $res .= $w->net_account . ' ' : null;
        !empty($w->mb_email) ? $res .= $w->mb_email . ' ' : null;
        !empty($w->bank_name) ? $res .= $w->bank_name . ' ' : null;
        !empty($w->iban) ? $res .= $w->iban . ' ' : null;
        !empty($w->bank_account_number) ? $res .= $w->bank_account_number . ' ' : null;
        $wallet = !empty($w->wallet) ? $w->wallet : '' ;

        if (mb_strlen($w->scheme) == 19) {
            $res .= ucwords($wallet) . ' ' . $w->scheme;
        } else {
            $res .= ucwords($w->scheme);
        }

        return $res;
    }

    public function getDepositDetails($deposit)
    {
        if (in_array($deposit->dep_type, [self::NETELLER, self::SKRILL, self::PAYPAL])) {
            return $this->formatAccount($deposit);
        } else {
            return ucwords($deposit->scheme) . ' ' . $deposit->card_hash;
        }
    }

    private function formatAccount($deposit)
    {
        $settings_map = [
            self::NETELLER => 'net_account',
            self::SKRILL => 'mb_email',
            self::PAYPAL => 'paypal_email'
        ];

        if (empty($this->{@$settings_map[$deposit->dep_type]})) {
            if (!$this->user->repo->hasSetting(@$settings_map[$deposit->dep_type])) {
                if (!empty($deposit->card_hash)){
                    return "Account: {$deposit->card_hash}";
                } else {
                    $this->{@$settings_map[$deposit->dep_type]} = 'NA';
                    return 'NA';
                }
            } else {
                $actions_data = $this->getHistoricData(@$settings_map[$deposit->dep_type], $deposit->user_id, $deposit->timestamp);
                if (!empty($actions_data)) {
                    $description = explode(' ', $actions_data['descr']);
                    $acc = $description[count($description) - 1];
                } else {
                    $acc = $this->user->repo->getSetting(@$settings_map[$deposit->dep_type]);
                }
                return "Account: {$acc}";
            }
        } else {
            return $this->{@$settings_map[$deposit->dep_type]} == 'NA' ? $this->{@$settings_map[$deposit->dep_type]} : "Account: {$this->{@$settings_map[$deposit->dep_type]}}";
        }
    }

    public function getHistoricData($user_setting, $user_id, $before_date)
    {
        $user_register_date = $this->user->register_date;
        $sql = "SELECT * FROM actions WHERE tag = '$user_setting' AND target = $user_id AND (created_at < '$before_date' AND created_at > '$user_register_date') ORDER BY created_at desc LIMIT 0,1";
        return phive('SQL')->sh(cu($user_id), 'id')->loadAssoc($sql);
    }

    public function approveDeposit($req)
    {
        $dep = Deposit::where('id', (int)$req->get('id'))->first();
        $dep->status = 'approved';
        return $dep->save();
    }

    public function getDeposits($req)
    {
        $date_range = DateHelper::validateDateRange($req);
        $res = Deposit::where('deposits.timestamp', '>=', $date_range['start_date']);
        if (!empty($req->get('status')))
            $res->where('status', $req->get('status'));
        // TODO sort by id desc, how to do?
        return $res->get();
    }

    /**
     * @param User $user
     * @param DateRange $date_range
     * @param Request $request
     * @param bool $initial
     * @return array
     */
    public function getUserDepositsData($user, $date_range, $request, Application $app, $initial = true)
    {
        $deposits_query = Deposit::sh($user->getKey())->whereBetween('timestamp', $date_range->getWhereBetweenArray())
            ->where('user_id', $user->getKey());

        if ($initial) {
            $methods_list = $deposits_query->selectRaw("DISTINCT dep_type as dep_type")->orderByDesc('deposits.timestamp')->get()->pluck('dep_type')->all();
        }

        $deposits_query->selectRaw("deposits.*, '' as actor, ip_log.descr as description, '' as details")
            ->leftJoin('ip_log', function ($join) {
                $join->on('ip_log.tr_id', '=', 'deposits.id')
                    ->where('ip_log.tag', '=', 'deposits');
            });

        if (!empty($request->get('dep_custom'))) {
            $deposits_query->where('deposits.dep_type', strtolower($request->get('dep_custom')));
        }
        $this->user = $user;
        $paginator = new PaginationHelper($deposits_query, $request, ['length' => 25, 'order' => ['column' => 'deposits.timestamp', 'order' => 'DESC']]);
        $page = $paginator->getPage($initial);

        (new Mts($app))->addTransactionDetails(collect($page['data']));

        $tmp = [];
        foreach ($page['data'] as $elem) {
            $elem->descr = $elem->ip_log->descr;
            $elem->details = $this->getDepositDetails($elem);
            $elem->details .= $elem->transaction_details['credit_card']['details'] ?? '';
            $elem->dep_type = ucwords($elem->dep_type);
            $elem->scheme = ucwords($elem->scheme);
            $elem->card_hash = ucwords($elem->card_hash);
            $elem->actor = is_null($elem->ip_log->actor_username) ? 'System' : $elem->ip_log->actor_username;
            $elem->amount = $elem->amount / 100;
            $elem->status = ucwords($elem->status);

            $tmp[] = $elem;
        }
        $page['data'] = $tmp;
        unset($tmp);

        return [
            'paginator' => $page,
            'methods_list' => isset($methods_list) ? $methods_list : null,
            'columns' => [] //todo after new select is done, add column list
        ];
    }

    /**
     * @param User $user
     * @param DateRange $date_range
     * @return mixed
     */
    public function getUserWithdrawalData($user, $date_range, Application $application)
    {
        /** @var Collection $withdrawals */
        $withdrawals = Withdrawal::shWith($user->getKey(), 'actor')
            ->whereBetween('timestamp', $date_range->getWhereBetweenArray())
            ->where('user_id', $user->getKey())
            ->get();

        $methodsList = $withdrawals->pluck('payment_method')->unique()->all();

        $withdrawals = (new Mts($application))->addTransactionDetails($withdrawals);

        return compact('withdrawals', 'methodsList');
    }

    /**
     * @param User $user
     * @param DateRange $date_range
     * @param array $ignore_types
     * @return mixed
     */
    public function getUserTransactions($user, $date_range, $ignore_types = [3, 8])
    {
        return $user->cashTransactions()
            ->whereNotIn('transactiontype', $ignore_types)->orderBy('timestamp', 'DESC')
            ->whereBetween('timestamp', $date_range->getWhereBetweenArray())
            ->get();
    }

    /**
     * Due to the failure where manual deposits added before february 2016 does not insert deposit record in ip_log table
     * just only cash_transactions, we need to handle that manually here.
     *
     * @param User $user
     * @param DateRange $date_range
     * @return mixed
     */
    public function getUserManualDepositData($user, $date_range)
    {
        $bug_date = Carbon::create(2016, 2)->endOfMonth();

        /** @var Builder $query_deposits */
        $query_deposits = IpLog::shWith($user->getKey(), 'actorUser', 'deposit')
            ->where('ip_log.target', $user->getKey())
            ->where('ip_log.tag', 'deposits');

        /** @var Builder $query_cash_transactions */
        $query_cash_transactions = IpLog::shWith($user->getKey(), ['actorUser', 'transaction' => function (Builder $query) {
            $query->where('cash_transactions.transactiontype', 3);
        }])
            ->leftJoin('users', 'users.id', '=', 'ip_log.actor')
            ->where('ip_log.target', $user->getKey())
            ->where('ip_log.tag', IpLog::TAG_CASH_TRANSACTIONS);

        if ($date_range->getStart()->greaterThan($bug_date)) {
            return $query_deposits->whereBetween('created_at', $date_range->getWhereBetweenArray())->get();

        } elseif ($date_range->getEnd()->lessThan($bug_date)) {
            return $query_cash_transactions->whereBetween('ip_log.created_at', $date_range->getWhereBetweenArray())->get();

        } else {
            return $query_cash_transactions->whereBetween('created_at', [$date_range->getStart('timestamp'), $bug_date->endOfDay()->format('Y-m-d H:i:s')])
                ->union($query_deposits->whereBetween('created_at', [$bug_date->endOfDay()->format('Y-m-d H:i:s'), $date_range->getEnd('timestamp')]))->get();
        }
    }

    /**
     * @param User $user
     * @param string|null $starts_at
     * @return mixed
     */
    public function getClosedLoops(User $user, string $starts_at)
    {
        $u_obj = cu($user->getKey());
        $closed_loops = phive('Cashier')->getClosedLoopData($u_obj, $starts_at);

        foreach ($closed_loops as $account => &$data) {
            $data['account'] = $account;
        }
        return $closed_loops;
    }
}
