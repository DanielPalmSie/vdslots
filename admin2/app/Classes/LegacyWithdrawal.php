<?php
/**
 * IMPORTANT NOTICE: This class contains a lot of legacy code so no time wasted porting code that is in the phive project
 * and can be updated. So then this will avoid less modifications to this project related code. This should be ported
 * in a proper way, i.e. using an API or something like that.
 *
 * Withdrawal fees for manual opeartions removed on https://videoslots.atlassian.net/browse/BAN-4935
 *
 * Created by PhpStorm.
 * User: ricardo
 * Date: 7/29/16
 * Time: 11:46 AM
 */

namespace App\Classes;

use App\Helpers\Common;
use App\Helpers\DataFormatHelper;
use App\Models\CashTransaction;
use App\Repositories\UserRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use DBUser;
use Symfony\Component\Routing\Generator\UrlGenerator;

class LegacyWithdrawal
{
    /** @var  Application $app */
    protected $app;

    public $pendingWithdrawalId;

    public $new_balance;

    public $cashTransactionId;

    public $result;

    public $user;

    public $bypass_docs_check = false;

    public static $supported_methods = [
        'neteller',
        'skrill',
        'bank',
        'entercash',
        'instadebit',
        'ecopayz',
        'wirecard',
        'citadel',
        'cubits',
        'adyen',
        'worldpay',
        'credorax',
        'inpay',
        'paypal',
        'zimpler',
        'mifinity',
        'muchbetter',
        'trustlyAccountPayout',
        'swish'
    ];

    /**
     * LegacyWithdrawal constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cashier = phive('Cashier');
        $this->pendingWithdrawalId = null;
        $this->new_balance = null;
        $this->cashTransactionId = null;
        $this->result = [];
    }

    public function bypassDocumentsCheck()
    {
        $this->bypass_docs_check = true;
        $this->cashier->bypass_docs_check = true;
    }

    private function insertPendingAndUpdateBalance(DBUser $user, int $amount, array $params): bool
    {
        $params['created_by'] = UserRepository::getCurrentUserId();

        $pendingWithdrawalId = phive('Cashier')->insertPendingCommon($user, $amount, $params, true);

        if (!$pendingWithdrawalId) {
            $this->app['monolog']->addError("insertPendingCommon error on {$params['payment_method']}");
            return false;
        }

        $this->pendingWithdrawalId = $pendingWithdrawalId;

        $cashTransaction = CashTransaction::sh($user->getId())
            ->where('user_id', $user->getId())
            ->where('parent_id', $pendingWithdrawalId)
            ->first('id');

        $this->cashTransactionId = $cashTransaction->id;

        phive('Bonuses')->failDepositBonuses($user->getId(), ucfirst($params['payment_method']) . ' Withdrawal');

        return true;
    }

    /**
     * @param int user_id
     * @param Request $request
     * @return bool
     */

    public function muchbetter(int $user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id',$user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $muchbetter_mobile = $request->get('muchbetter_mobile');
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user,'muchbetter','out', $amount);

         if (!$user->isVerified()) {
             $err['muchbetter_mobile'] = 'err.user.not.verified';
           }
        }

        $cents = (int) round($amount * 100);

        if (empty($muchbetter_mobile)) {
            $err['muchbetter_mobile'] = 'err.empty';
        }

        $temp_balance = phive('Casino')->balances($user);
        if (!$temp_balance) {
            $err['database'] = 'err.unknown';
        } else {
            $cur_balance = $temp_balance['cash_balance'] / 100;
        }

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] =  'err.lowbalance';
            $this->new_balance = $cur_balance;
        }

        if (empty($err) && phive('Cashier')->hasDuplicateAccountUsage($user->getId(),'muchbetter', $muchbetter_mobile)) {
            $err['amount'] = 'err.duplicate';
        }

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'net_account' => $muchbetter_mobile,
                'payment_method'    => 'muchbetter',
                'aut_code'          => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
               $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function neteller($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $net_account = $request->get('net_account');
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'neteller', 'out', $amount);

            if (!$user->isVerified())
                $err['net_account'] = 'err.user.not.verified';
        }

        $cents = (int) round($amount * 100);

        if (empty($net_account))
            $err['net_account'] = "err.empty";

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
            $this->new_balance = $cur_balance;
        }

        if (empty($err) && phive('Cashier')->hasDuplicateAccountUsage($user->getId(), 'neteller', $net_account))
            $err['amount'] = "err.duplicate";

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'net_account' => $net_account,
                'payment_method' => 'neteller',
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function skrill($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $mb_email = $request->get('mb_email');
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'skrill', 'out', $amount);
            if (!$user->isVerified()) {
                $err['email'] = 'err.user.not.verified';
            }
        }

        //if (!$this->cashier->mbCheckEmail($mb_email)) {
        //    $err['email'] = 'err.notinmb';
        //}

        $cents = (int) round($amount * 100);

        if (empty($mb_email)) {
            $err['email'] = 'err.empty';
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false) {
            $err['database'] = 'err.unknown';
            $this->app['monolog']->addError("Balance error on skrill", $tmp);
        } else {
            $cur_balance = $tmp['cash_balance'] / 100;
        }

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if ($this->cashier->hasDuplicateAccountUsage($user->getId(), 'skrill', $mb_email)) {
            $err['amount'] = "err.duplicate";
        }

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'mb_email' => $mb_email,
                'payment_method' => 'skrill',
                'aut_code' => $cents,
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function cubits($user_id, Request $request)
    {
        $user = cu($user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $btc_address = $request->get('btc_address');
        $err = [];

        if ($user->getCountry() != 'AU') {
            $do_docs = phive('Cashier')->getSetting('cubits_allow_withdrawal_without_docs');
            if ($do_docs !== true || $this->bypass_docs_check !== true) {
                list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'cubits', 'out', $amount);
                if (!$user->isVerified()) {
                    $err['email'] = 'err.user.not.verified';
                }
            }
        }

        $cents = (int) round($amount * 100);

        if (empty($btc_address)) {
            $err['btc.address'] = 'err.empty';
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false) {
            $err['database'] = 'err.unknown';
            $this->app['monolog']->addError("Balance error on cubits", $tmp);
        } else {
            $cur_balance = $tmp['cash_balance'] / 100;
        }

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'payment_method' => 'cubits',
                'bank_account_number' => $btc_address,
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function citadel($user_id, Request $request)
    {
        $type = (!empty($request->get('payment_method')) && $request->get('payment_method') == 'citadel') ? 'citadel' : 'bank';

        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $iban = $request->get('iban');

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, $type, 'out', $amount, false);
            if (!$user->isVerified())
                $err['verified'] = "err.user.not.verified";
        }
        //
        //$amount 		= checkAmount($err, $_POST['amount'], true);
        $cents = (int) round($amount * 100);

        // validate only here
        if (empty($request->get('payment_method')) || $request->get('payment_method') != 'citadel') {
            $cols = ['bank_city', 'swift_bic'];

            foreach ($cols as $col) {
                if (empty($request->get($col))) {
                    $err[$col] = "err.empty";
                }
            }
        }

        // this fields is used to fill database
        $cols = array('bank_name', 'bank_address', 'bank_city', 'swift_bic');

        if (empty($iban) && empty($request->get('bank_account_number'))) {
            $err['acc_iban'] = "err.empty";
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false) {
            $err['database'] = 'err.unknown';
        } else {
            $cur_balance = $tmp['cash_balance'] / 100;
            if (intval($tmp['cash_balance']) < intval($cents)) {
                $err['amount'] = 'err.lowbalance';
            }
        }

        if (empty($err)) {
            $cols = array_merge($cols, array('iban', 'bank_account_number', 'bank_code', 'bank_clearnr', 'bank_name'));
            $ud = $user->data;
            $insert = [
                'bank_receiver' => "{$ud['firstname']} {$ud['lastname']}",
                'bank_country' => $iban ? substr($iban, 0, 2) : $ud['country'],
                'aut_code' => $cents,
                'created_by' => UserRepository::getCurrentUserId()
            ];

            foreach ($cols as $col) {
                $insert[$col] = !empty($request->get($col)) ? $request->get($col) : '';
            }

            $result = phive('Cashier')->insertPendingBank($user, $cents, $insert, $type);
            phive('Bonuses')->failDepositBonuses($user->getId(), "Bank withdrawal");
            if ($result === false) {
                $err['database'] = 'err.unknown';
            } else {
                $this->pendingWithdrawalId = $result;
                $this->new_balance = $cur_balance - $amount;
            }
        }
        return $this->manageResult($err);
    }

    public function bank($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'bank', 'out', $amount);
        }

        $cents = (int) round($amount * 100);
        $cols = array('bank_city', 'swift_bic', 'bank_name', 'scheme', 'iban', 'bank_account_number', 'bank_code', 'bank_clearnr');
        $iban = $request->get('iban');

        if (
            empty($iban) &&
            (
                empty($request->get('bank_account_number')) ||
                $request->get('actual_payment_method') === 'mifinity'
            )
        ) {
            $err['acc_iban'] = "err.empty";
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false) {
            $err['database'] = 'err.unknown';
        } else {
            $cur_balance = $tmp['cash_balance'];
        }

        if (empty($err) && $cur_balance < $cents) {
            $err['amount'] = 'err.lowbalance';
        }

        $paymentMethod = $request->get('actual_payment_method') ?: null;
        if (!$paymentMethod) {
            $err['Main Provider'] = 'err.empty';
        }

        if (empty($err)) {
            $ud = $user->data;
            $insert = array('bank_receiver' => "{$ud['firstname']} {$ud['lastname']}", 'bank_country' => $iban ? substr($iban, 0, 2) : $ud['country']);
            foreach ($cols as $col) {
                $insert[$col] = $request->get($col);
            }

            // If it is a manual wire the admin should leave actual_payment_method empty for a plain bank.
            $insert['payment_method'] = $paymentMethod;
            $insert['aut_code']       = $cents;

            $result = $this->insertPendingAndUpdateBalance($user, $cents, $insert);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $cents;
            }
        }

        return $this->manageResult($err);
    }

    /**
     *
     * Required: (bic and iban) or (clearnr and accnumber), bank_name can be empty
     * @param $user_id
     * @param Request $request
     * @return bool
     */
    public function entercash($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'entercash', 'out', $amount, true, $_REQUEST['bank_name']);
        }

        if (empty($request->get('swift_bic')) && empty($request->get('clearnr')))
            $err['bic_clearnr'] = 'err.empty';

        if (empty($request->get('iban')) && empty($request->get('bank_account_number')))
            $err['acc_iban'] = 'err.empty';

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if (empty($err)) {
            $cents = (int) round($amount * 100);

            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'payment_method' => 'entercash',
                'bank_account_number' => str_replace([' ', '-'], '', $request->get('bank_account_number')),
                'swift_bic' => str_replace([' ', '-'], '', $request->get('swift_bic')),
                'iban' => str_replace([' ', '-'], '', $request->get('iban')),
                'bank_name' => $request->get('bank_name'),
                //'bank_receiver'           => $_POST['acc_id'],
                'bank_clearnr' => str_replace([' ', '-'], '', $request->get('clearnr')),
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }


    /**
     *
     * Required: bank_name, bic and account number needed
     * @param $user_id
     * @param Request $request
     * @return bool
     */
    public function inpay($user_id, Request $request)
    {
        $user = cu($user_id);
        $this->user = $user;
        $ud = ud($user);
        $amount = $request->get('amount') / 100;
        $iban = $request->get('iban');
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'inpay', 'out', $amount, true);
        }

        if (empty($iban) && empty($request->get('bank_account_number'))) {
            $err['acc'] = 'err.empty';
        }

        if (empty($request->get('bank_name'))) {
            $err['bank_name'] = 'err.empty';
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if(!empty($request->get('nid')) && !$user->hasNid()){
            $nid_res = $user->setNid(phive()->rmWhiteSpace($request->get('nid')));
            if(!$nid_res){
                $err['database'] = 'nid.taken';
            }
        }

        if (empty($err)) {
            $cents = (int) round($amount * 100);

            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'payment_method' => 'inpay',
                'bank_name' => $request->get('bank_name'),
                'iban' => $iban,
                'bank_account_number' => str_replace([' ', '-'], '', $request->get('bank_account_number')),
                'swift_bic' => str_replace([' ', '-'], '', $request->get('swift_bic')),
                'bank_clearnr' => str_replace([' ', '-'], '', $request->get('bank_clearnr')),
                'bank_receiver' => "{$ud['firstname']} {$ud['lastname']}",
                'bank_country' => $iban ? substr($iban, 0, 2) : $ud['country'],
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function adyen($user_id, Request $request) {
        return $this->wirecard($user_id, $request, 'adyen');
    }

    public function worldpay($user_id, Request $request) {
        return $this->wirecard($user_id, $request, 'worldpay');
    }

    public function credorax($user_id, Request $request) {
        return $this->wirecard($user_id, $request, 'credorax');
    }

    public function wirecard($user_id, Request $request, $psp ='wirecard')
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $card_id = $request->get('card_id');
        $amount = $request->get('amount') / 100;
        $err = [];

        if ($this->bypass_docs_check !== true) {
            if (!$user->isVerified())
                $err[$psp] = 'err.user.not.verified';
        }

        $mts = new Mts($this->app);
        $query_params = [
            'user_id' => $user_id,
            'card_id' => $card_id,
            'suppliers' => $psp,
            'can_withdraw' => 1
            //'verified' => 1, removed for now
            //'active' => 1
        ];
        $card_list = $mts->getCardsList($user_id, $query_params);
        foreach ($card_list as $elem) {
            if ($elem['id'] == $card_id) {
                $card = $elem;
                break;
            }
        }

        if (empty($card)) {
            $err[$psp] = 'err.card.not.verified';
        } else {
            $pci_num = $card['card_num'];
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if ($this->bypass_docs_check !== true) {
            //list($err, $amount) = $this->cashier->transferStart($user, $psp, 'out', $amount);
            $status = phive('Dmapi')->checkDocumentStatus($user->getId(), 'creditcardpic', '', $card['id']);
            if($status == 'deactivated') {
                $err['nodeposit'] = 'err.ccard.nodeposit';
            }
        }

        if (!empty($err))
            return $this->manageResult($err);

       /* $scheme = $dc->getCardType($pci_num);

        if (!$dc->canWithdraw($user, $scheme))
            $err['wirecard'] = 'err.nowithdraw.country';*/

        $cents = (int) round($amount * 100);

        $result = $this->insertPendingAndUpdateBalance($user, $cents, [
            'ref_code' => $card_id,
            'aut_code' => $cents,
            'payment_method' => $psp,
            'scheme' => $pci_num,
            'loc_id' => null
        ]);

        if (!$result) {
            $err['database'] = 'err.unknown';
        } else {
            $this->new_balance = $cur_balance - $amount;
        }

        return $this->manageResult($err);
    }

    public function instadebit($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'instadebit', 'out', $amount);

            if (!$user->isVerified())
                $err['instantdebit'] = 'err.user.not.verified';
        }

        $insta_id = $user->getSetting('instadebit_user_id');

        $cents = (int) round($amount * 100);

        if (empty($insta_id))
            $err['instadebit_user_id'] = "err.nodeposit";

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if (empty($err)) {

            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'payment_method' => 'instadebit',
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function ecopayz($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'ecopayz', 'out', $amount);

            if (!$user->isVerified())
                $err['ecopayz'] = 'err.user.not.verified';

            $has_eco = $user->getSetting('has_eco');
            if (empty($has_eco))
                $err['has_ecopayz'] = "err.nodeposit";
        }

        $cents = (int) round($amount * 100);

        if (empty($request->get('account_number')))
            $err['accnumber'] = "err.empty";


        $tmp = phive('Casino')->balances($user);
        if ($tmp === false)
            $err['database'] = 'err.unknown';
        else
            $cur_balance = $tmp['cash_balance'] / 100;

        if (empty($err) && $cur_balance < $amount)
            $err['amount'] = 'err.lowbalance';

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'payment_method' => 'ecopayz',
                'net_account' => $request->get('account_number'),
                'aut_code' => $cents
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    public function paypal($user_id, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $paypal_email = $request->get('ppal_email');
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier->transferStart($request->request->all(), $user, 'paypal', 'out', $amount);
            if (!$user->isVerified()) {
                $err['email'] = 'err.user.not.verified';
            }
        }

        $cents = (int) round($amount * 100);

        if (empty($paypal_email)) {
            $err['email'] = 'err.empty';
        }

        $tmp = phive('Casino')->balances($user);
        if ($tmp === false) {
            $err['database'] = 'err.unknown';
            $this->app['monolog']->addError("Balance error on paypal", $tmp);
        } else {
            $cur_balance = $tmp['cash_balance'] / 100;
        }

        if (empty($err) && $cur_balance < $amount) {
            $err['amount'] = 'err.lowbalance';
        }

        if ($this->cashier->hasDuplicateAccountUsage($user->getId(), 'paypal', $paypal_email)) {
            $err['amount'] = "err.duplicate";
        }

        if (empty($err)) {
            $result = $this->insertPendingAndUpdateBalance($user, $cents, [
                'ppal_email' => $paypal_email,
                'net_account' => $user->getSetting('paypal_payer_id'),
                'payment_method' => 'paypal',
                'aut_code' => $cents,
            ]);

            if (!$result) {
                $err['database'] = 'err.unknown';
            } else {
                $this->new_balance = $cur_balance - $amount;
            }
        }

        return $this->manageResult($err);
    }

    private function logError($msg)
    {
        $this->app['monolog']->addError("[BO-MANUAL-WITHDRAWAL] $msg");
    }

    private function manageResult(array $err, string $redirectUrl = null)
    {
        if (empty($err)) {
            $msg = "New manual withdrawal inserted with internal id: {$this->pendingWithdrawalId}. New cash balance: " . DataFormatHelper::nf($this->new_balance * 100) . ".";
            $this->logError($msg);
            $this->result = [
                'success' => true,
                'message' => $msg,
                'redirectUrl' => $redirectUrl
            ];
            return true;
        } else {
            $this->logError("Error inserting manual withdrawal: " . json_encode($err));
            $translate = "";
            foreach ($err as $field => $errstr) {
                $translate .= tAssoc('register.' . $field, ['ciso'=>!empty($this->user) ? $this->user->getCurrency() : ciso()]) . ': ' . t($errstr) . "<br>";
            }
            $this->result = [
                'success' => false,
                'message' => !empty($translate) ? $translate : "Error not defined. [Dump: " . json_encode($err) . "]."
            ];
            return false;
        }
    }

    /**
     * Create manual withdrawal via Zimpler
     *
     * @param $user_id
     * @param Request $request
     * @return bool
     */
    public function zimpler($user_id, Request $request): bool
    {
        $err = [];
        $type = 'zimpler';
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        $this->user = $user;
        $amount = $request->get('amount') / 100;

        if ($this->bypass_docs_check !== true) {
            [$err, $amount] = $this->cashier->transferStart($request->request->all(), $user, $type, 'out', $amount);
            if (!empty($err)) {
                return $this->manageResult($err);
            }
            if (!$user->isVerified()) {
                return $this->manageResult(['email' => "err.user.not.verified"]);
            }
        }

        $balance = phive('Casino')->balances($user);
        if ($balance === false) {
            $this->app['monolog']->addError("Balance error on " . $type, $balance);
            return $this->manageResult(['database' => "err.unknown"]);
        }

        $cur_balance = $balance['cash_balance'] / 100;
        if ($cur_balance < $amount) {
            return $this->manageResult(['amount' => "err.lowbalance"]);
        }

        $cents = (int) round($amount * 100);
        $lastApprovedPending = phive('Cashier')->getLastPending($type, $user->getId());
        if(!empty($lastApprovedPending)) {
            $extra['destination_account'] = $lastApprovedPending['iban'] ?: $lastApprovedPending['bank_account_number'];
            if (!empty($extra['destination_account'])) {
                $insert = [
                    "payment_method" => $type,
                    "aut_code" => $cents,
                    "iban" => $extra['destination_account'],
                    'created_by' => UserRepository::getCurrentUserId()
                ];
                $pid = phive('Cashier')->insertInitiatedWithdrawal($user, $cents, $insert);

                if (!empty($pid)) {
                    if ($user->getCountry() == 'FI' || $user->getCountry() == 'SE') {
                        $extra['nid'] = $user->getNid();
                    }
                    $non_kyc_countries =  phive('Cashier')->getSetting('psp_config_2')['zimplerbank']['non_kyc_countries'] ?? ['NL'];
                    if (in_array($user->getCountry(), $non_kyc_countries)) {
                        $extra['iban'] = $extra['destination_account'];
                    }

                    $extra['site'] = phive()->getSetting('domain');
                    $extra['site_display_name'] = phive()->getSetting('domain');
                    $extra['admin_source'] = true;
                    $result = phive('Cashier/Mts')::getInstance($type, $user)->doBankWithdrawalWithRedirect($err, $user, $cents, $pid, $extra);

                    $where_str = phive('SQL')->makeWhere([
                        'user_id'         => $user_id,
                        'session_id'      => $user->getCurrentSession()['id'],
                        'transactiontype' => 8
                    ]);
                    $sql = "SELECT * FROM cash_transactions {$where_str} ORDER BY id desc LIMIT 0,1";
                    $this->cashTransactionId   = phive('SQL')->sh($user, 'id')->loadAssoc($sql)['id'];
                }
            } else {
                return $this->manageResult(["User bank details are missing"]);
            }
        }else {
            return $this->manageResult(["No withdrawal details found"]);
        }

        phive('Bonuses')->failDepositBonuses($user->getId(), "Zimpler Withdrawal");

        if ($result === false) {
            $this->app['monolog']->addError("insertPendingCommon error on " . $type);
            $err['database'] = 'err.unknown';
        } else {
            if (!is_numeric($this->cashTransactionId)) {
                $this->app['monolog']->addError("trans id is not numeric error on " . $type);
                $err['database'] = 'err.unknown';
            }
            $this->new_balance = $cur_balance - $amount;
            $this->pendingWithdrawalId = $pid;
        }

        return $this->manageResult($err);
    }

    public function trustly($userId, Request $request): bool
    {
        $user = phive('UserHandler')->newByAttr('id', $userId);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $paymentMethod = 'trustly';
        $err = [];

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier
                ->transferStart($request->request->all(), $user, $paymentMethod, 'out', $amount);

            if (!empty($err)) {
                return $this->manageResult($err);
            }

            if (!$user->isVerified()) {
                return $this->manageResult(['verified' => 'err.user.not.verified']);
            }
        }

        $balance = phive('Casino')->balances($user);

        if (!$balance) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $currentBalance = $balance['cash_balance'] / 100;

        if ($currentBalance < $amount) {
            $this->new_balance = $currentBalance;
            return $this->manageResult(['amount' => 'err.lowbalance']);
        }

        $cents = (int) round($amount * 100);

        $pid = phive('Cashier')->insertInitiatedWithdrawal($user, $cents, [
            "payment_method" => $paymentMethod,
            "aut_code" => $cents,
            'created_by' => UserRepository::getCurrentUserId()
        ]);

        if (empty($pid)) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $this->pendingWithdrawalId = $pid;

        $extra = array_merge(
            phive('Cashier/Mts')::getInstance($paymentMethod, $user)->getTrustlyExtra($user, '', 'withdraw'),
            [
                'min_amount' => $cents,
                'max_amount' => $cents,
                'admin_source' => true,
                'return_url' => $this->app['url_generator']->generate('admin.user-transactions-withdrawal', [
                        'user' => $userId
                    ], UrlGenerator::ABSOLUTE_URL),
                'fail_url' => $this->app['url_generator']->generate('admin.user-insert-withdrawal', [
                        'user' => $userId
                    ], UrlGenerator::ABSOLUTE_URL)
            ]
        );

        $result = phive('Cashier/Mts')::getInstance($paymentMethod, $user)
            ->doBankWithdrawalWithRedirect($err, $user, $cents, $pid, $extra);

        if (!$result) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $this->new_balance = $currentBalance - $amount;
        $result = json_decode($result, true);

        return $this->manageResult($err, $result['result']['url']);
    }

    public function trustlyAccountPayout(int $userId, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $userId);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $paymentMethod = 'trustly';
        $err = [];

        $account = json_decode($request->get('account'), true);
        if (!$account) {
            return $this->manageResult(['Account' => 'err.unknown']);
        }

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier
                ->transferStart($request->request->all(), $user, $paymentMethod, 'out', $amount);

            if (!empty($err)) {
                return $this->manageResult($err);
            }

            if (!$user->isVerified()) {
                return $this->manageResult(['verified' => 'err.user.not.verified']);
            }
        }

        $balance = phive('Casino')->balances($user);

        if (!$balance) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $currentBalance = $balance['cash_balance'] / 100;

        if ($currentBalance < $amount) {
            $this->new_balance = $currentBalance;
            return $this->manageResult(['amount' => 'err.lowbalance']);
        }

        $cents = (int) round($amount * 100);

        $result = $this->insertPendingAndUpdateBalance($user, $cents, [
            'payment_method' => $paymentMethod,
            "bank_name" => $account['bank_name'],
            "bank_account_number" => $account['account_number'],
            "ref_code" => $account['account_external_id'],
            'aut_code' => $cents
        ]);

        if (!$result) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $this->new_balance = $currentBalance - $amount;

        return $this->manageResult($err);
    }

    public function swish(int $userId, Request $request)
    {
        $user = phive('UserHandler')->newByAttr('id', $userId);
        $this->user = $user;
        $amount = $request->get('amount') / 100;
        $paymentMethod = 'swish';
        $err = [];

        $account = json_decode($request->get('account'), true);
        if (!$account) {
            return $this->manageResult(['Account' => 'err.empty']);
        }

        if ($this->bypass_docs_check !== true) {
            list($err, $amount) = $this->cashier
                ->transferStart($request->request->all(), $user, $paymentMethod, 'out', $amount);

            if (!empty($err)) {
                return $this->manageResult($err);
            }

            if (!$user->isVerified()) {
                return $this->manageResult(['verified' => 'err.user.not.verified']);
            }
        }

        $balance = phive('Casino')->balances($user);

        if (!$balance) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $currentBalance = $balance['cash_balance'] / 100;

        if ($currentBalance < $amount) {
            $this->new_balance = $currentBalance;
            return $this->manageResult(['amount' => 'err.lowbalance']);
        }

        $cents = $amount * 100;

        $result = $this->insertPendingAndUpdateBalance($user, $cents, [
            'payment_method' => $paymentMethod,
            'net_account' => $account['account_external_id'],
            'aut_code' => $cents
        ]);

        if (!$result) {
            return $this->manageResult(['database' => 'err.unknown']);
        }

        $this->new_balance = $currentBalance - $amount;

        return $this->manageResult($err);
    }
}
