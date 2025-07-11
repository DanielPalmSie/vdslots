<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.01.08.
 * Time: 14:03
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Classes\Mts;
use App\Helpers\DateHelper;
use App\Models\Config;
use App\Models\Deposit;
use App\Models\IpLog;
use App\Models\User;
use App\Repositories\AccountsRepository;
use App\Repositories\TransactionsRepository;
use App\Repositories\UserRepository;
use App\Repositories\LimitsRepository;
use Carbon\Carbon;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class TransactionsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/get-methods/', 'App\Controllers\TransactionsController::getMethods')
            ->bind('transactions.get-methods');

        $factory->get('/get-sub_methods/', 'App\Controllers\TransactionsController::getSubMethods')
            ->bind('transactions.get-sub_methods');

        return $factory;
    }

    public function getMethods(Application $app, Request $request)
    {
        return $app->json(TransactionsRepository::getMethods($app, $request));
    }

    public function getSubMethods(Application $app, Request $request)
    {
        return $app->json(TransactionsRepository::getSubMethods($app, $request));
    }

    public function showUserTransactions(Application $app, User $user)
    {
        return $app['blade']->view()->make('admin.user.transactions.index', compact('app', 'user'))->render();
    }

    public function transferCash(Application $app, User $user, Request $request)
    {
        $repo = new TransactionsRepository($app);
        $type_list = $repo->getTransferCashSelectList();

        if ($request->isMethod('POST')) {
            $res = $repo->transferCash($request, $user);
            if ($res['success'] === true) {
                $app['flash']->add('success', "Cash transfer successful. {$res['message']}");
                return $app->redirect($app['url_generator']->generate('admin.user-transactions-other', ['user' => $user->id]));
            } else {
                $app['flash']->add('warning', "Cash transfer failed. Reason: {$res['message']}");
            }
        }

        return $app['blade']->view()->make('admin.user.transactions.transfer-cash', compact('app', 'user', 'type_list', 'message'))->render();
    }

    public function transferCashVerify(Application $app, User $user, Request $request) {
        $user->populateSettings();
        $amount = (int)$request->get('amount');

        $db_user = cu($user->getKey());

        // Check balance limit if applicable
        if(lic('hasBalanceTypeLimit', [$db_user], $db_user)) {
            $balance_limit = rgLimits()->getSingleLimit($db_user, 'balance');

            $balance = $db_user->getBalance();
            $remaining_amount = \App\Helpers\DataFormatHelper::nf($balance_limit['remaining']);

            if($balance_limit['cur_lim'] < ($balance + $amount)) {
                return $app->json([
                    'message' => "Are you sure you want to transfer this amount to user? " .
                        "This will exceed the players maximum balance limit <br> " .
                        "Customer has only {$remaining_amount} {$user->currency} remaining, this transfer will play block and deposit block the customer.",
                    'remaining' => $balance_limit['remaining'],
                    'amount'=> $amount
                ],400);
            }
        }

        return $app->json([]);
    }

    public function addDeposit(Application $app, User $user, Request $request)
    {
        $repo = new TransactionsRepository($app);
        $methods_list = $repo::getDepositMethods(true);
        if ($request->isMethod('POST')) {
            $res = $repo->addDeposit($request, $user, $methods_list);
            if ($res['success'] == false) {
                $app['flash']->add('danger', $res['message']);
            } else {
                $app['flash']->add('success', $res['message']);
                return $app->redirect($app['url_generator']->generate('admin.user-transactions-deposit', ['user' => $user->id]));
            }
        }

        $limits_repo    = new LimitsRepository($app);
        $deposit_limits = $limits_repo->getDepositLimits($user);

        $has_deposit_limits = false;
        if(count($deposit_limits) > 0) {
            $has_deposit_limits = true;
        }

        return $app['blade']->view()->make(
                    'admin.user.transactions.add-deposit',
                    compact('app', 'user', 'methods_list', 'has_deposit_limits', 'deposit_limits')
                )->render();
    }

    public function addDepositVerify(Application $app, User $user, Request $request) {
        $user->populateSettings();
        $amount = (int)$request->get('amount');

        // Check all limits: daily, weekly, and monthly.
        // If one of those will be exceeded, we need to show the popup.
        $limits_repo    = new LimitsRepository($app);
        $deposit_limits = $limits_repo->getDepositLimits($user);

        foreach ($deposit_limits as $deposit_limit) {
            if($deposit_limit->remaining < $amount) {
                $remaining_amount = \App\Helpers\DataFormatHelper::nf($deposit_limit->remaining);

                return $app->json([
                'message' => "Are you sure you want to create this deposit? " .
                    "This will exceed the players deposit limit for time span '{$deposit_limit->time_span}'. <br> " .
                    "Customer has only {$remaining_amount} {$user->currency} remaining, this deposit will play block the customer.",
                'remaining' => $deposit_limit->remaining,
                'amount'=> $amount
            ],400);
            }
        }

        // Check balance limit if applicable
        $db_user = cu($user->getKey());
        if(lic('hasBalanceTypeLimit', [$db_user], $db_user)) {
            $balance_limit = rgLimits()->getSingleLimit($db_user, 'balance');

            $balance = $db_user->getBalance();
            $remaining_amount = \App\Helpers\DataFormatHelper::nf($balance_limit['remaining']);

            if($balance_limit['cur_lim'] < ($balance + $amount)) {
                return $app->json([
                    'message' => "Are you sure you want to create this deposit? " .
                        "This will exceed the players maximum balance limit <br> " .
                        "Customer has only {$remaining_amount} {$user->currency} remaining, this deposit will play block and deposit block the customer.",
                    'remaining' => $balance_limit['remaining'],
                    'amount'=> $amount
                ],400);
            }
        }

        return $app->json([]);
    }

    public function createWithdrawal(Application $app, User $user, Request $request)
    {
        $repo = new TransactionsRepository($app);
        $form_data = $repo->getInsertWithdrawalForm($user);
        $app['monolog']->addError("[BO-MANUAL-WITHDRAWAL] ". json_encode($request->request->all()));

        if ($request->isMethod('POST')) {
            if ($request->isXmlHttpRequest() && $request->get('render_subform') == 1) {
                $method = $request->get('payment_method');
                if (in_array($method, ['wirecard', 'adyen', 'worldpay', 'credorax'])) {
                    try {
                        $cards = $repo->mts->getCardsList($user->getKey(), [
                            'user_id' => $user->getKey(),
                            'can_withdraw' => 1,
                            'card_id' => 0,
                            'suppliers' => $method,
                            'withdraw_only' => true
                        ]);
                        $form_data['cards_list'] = $repo->setCardsStatus($user, $cards);
                    } catch (\Exception $e) {
                        $app['monolog']->addError("[BO-MANUAL-WITHDRAWAL] {$e->getMessage()}");
                        return $app->json([
                            'html' => "<p><b>Temporal network issue connecting to MTS please reload the page and try again.</p>"
                        ]);
                    }
                    if (empty($form_data['cards_list']) || count($form_data['cards_list']) == 0) {
                        $app['monolog']->addError("[BO-MANUAL-WITHDRAWAL] Card list empty for {$user->getKey()}");
                        return $app->json([
                            'html' => "<p><b>No card available to create a withdrawal.</b> Select another provider from the list</p>"
                        ]);
                    }
                } elseif ($method === 'Trustly Account Payout') {
                    $accountsRepository = new AccountsRepository($app);
                    $form_data['accounts'] = $accountsRepository->getUserAccounts($user->id, \Supplier::Trustly);
                } elseif ($method === \Supplier::Swish) {
                    $accountsRepository = new AccountsRepository($app);
                    $form_data['accounts'] = $accountsRepository->getUserAccounts($user->id, \Supplier::Swish);
                }

                $bankProviders = $repo->getInsertWithdrawalBankProviders();

                return $app->json([
                    'html' => $app['blade']->view()->make('admin.user.transactions.partials.insert-withdrawal-subform', compact('app', 'user', 'form_data', 'bankProviders', 'method'))->render()
                ]);
            } else {
                if ($request->get('amount') < 0) {
                    $app['flash']->add('danger', "Amount cannot be negative");
                    return new RedirectResponse($request->headers->get('referer'));
                }

                $res = $repo->insertPendingWithdrawal($request, $user);

                if (!$res['success']) {
                    $app['flash']->add('danger', $res['message']);
                    return new RedirectResponse($request->headers->get('referer'));
                } else {
                    if ($res['redirectUrl']) {
                        $app['flash']->add('success', 'The pending withdrawal status will be updated upon receiving the notification from the PSP.');

                        return new RedirectResponse($res['redirectUrl']);
                    }

                    $app['flash']->add('success', $res['message']);

                    return $app->redirect($app['url_generator']->generate('admin.user-transactions-withdrawal', ['user' => $user->id]));
                }
            }
        }

        $payAnyBankExtraFieldsConfig = phive('CasinoCashier')->getPspSetting('payanybank')['withdraw']['extra_fields'];

        return $app['blade']->view()->make('admin.user.transactions.insert-withdrawal', compact('app', 'user', 'form_data', 'payAnyBankExtraFieldsConfig'))->render();
    }

    /**
     * todo port legacy function
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function cancelWithdrawal(Application $app, User $user, Request $request)
    {
        $actor = UserRepository::getCurrentUser();

        if ((new TransactionsRepository($app))->checkCancellationsCount($user, $actor) === true) {
            $app['flash']->add('warning', "Daily cancellation limit exceeded.");
            return new RedirectResponse($request->headers->get('referer'));
        }
        $withdrawal_id = $request->get('id');
        if (!empty($withdrawal_id)) {
            $res = phive('Cashier')->disapprovePending($withdrawal_id, false, true, true);
            if ($res !== false) {
                IpLog::logIp($actor, $user, IpLog::TAG_CANCEL_WITHDRAWAL, "{$actor->username} cancelled withdrawal with internal id of {$withdrawal_id}", $withdrawal_id);
            }
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    public function cancelPendingWithdrawal(Application $app, User $user, Request $request)
    {
        $withdrawal_id = $request->get('id');
        $action = $request->get('action');
        if ($action == 'flush_pending' && !p('flush.pending')) {
            $app->abort(403);
        } else {
            $p = phive('Cashier')->getPending($withdrawal_id);
            if ($action == 'delete') {
                phive('Cashier')->disapprovePending($p, false);
            } elseif ($action == 'flush' && empty($p['flushed'])) {
                phive('Cashier')->flushPending($p);
            } else {
                $app->abort(404, "Action not supported");
            }
        }
        return new RedirectResponse($request->headers->get('referer'));
    }

    public function listUserDeposits(Application $app, User $user, Request $request)
    {
        $repo = new TransactionsRepository($app);

        if ($is_post = $request->isMethod('POST')) {
            foreach ($request->get('form') as $key => $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_6_MONTHS);

        $deposits_data = $repo->getUserDepositsData($user, $date_range, $request, $app, !$is_post);

        if ($request->isMethod('POST')) {
            return $app->json($deposits_data['paginator']);
        }

        $initial = ['data' => $deposits_data['paginator']['data'], 'defer_option' => $deposits_data['paginator']['recordsTotal'], 'initial_length' => 25, 'order' => ['column' => 1, 'type' => "desc"]];

        return $app['blade']->view()->make('admin.user.transactions.deposits', compact('app', 'user', 'sort', 'date_range', 'deposits_data', 'initial'))->render();
    }

    public function listUserFailedDeposits(Application $app, User $user, Request $request)
    {
        if(!empty($request->get('form'))) {
            $request->request->set('date-range', $request->get('form')[0]['value']);
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_6_MONTHS);

        $page = $request->get('page', 0);
        $length = $request->get('length', 10);

        $mts = new Mts($app);
        $deposits = $mts->getFailedDeposits(
          $user->getKey(),
          $date_range->getStart('timestamp'),
          $date_range->getEnd('timestamp'),
          1000,
          null,
          null,
          false,
          true,
          $page,
          $length
        );

        if ($request->isMethod('POST') && $request->get('draw') !== null) {
            return $app->json([
                'draw' => intval($request->get('draw')),
                'recordsTotal' => $deposits['recordsTotal'] ?? count($deposits['data'] ?? []),
                'recordsFiltered' => $deposits['recordsFiltered'] ?? count($deposits['data'] ?? []),
                'data' => $deposits['data'] ?? []
            ]);
        }

        if ($request->isMethod('POST')) {
            return $app->json($deposits);
        }

        $sort = ['column' => 0, 'type' => "desc", 'start_date' => $date_range->getStart('timestamp'), $date_range->getStart('timestamp')];

        return $app['blade']->view()->make('admin.user.transactions.failed-deposits', compact('app', 'user', 'date_range', 'deposits', 'sort'))->render();
    }

    public function listUserWithdrawals(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_6_MONTHS);

        $withdrawals = (new TransactionsRepository($app))->getUserWithdrawalData($user, $date_range, $app);

        return $app['blade']->view()->make('admin.user.transactions.withdrawals', compact('app', 'user', 'date_range', 'withdrawals'))->render();
    }

    public function listUserManualDeposits(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_6_MONTHS);

        $repo = new TransactionsRepository($app);
        $repo->user = $user;

        $manual_deposits = $repo->getUserManualDepositData($user, $date_range);

        $sort = ['column' => 1, 'type' => "desc"];

        return $app['blade']->view()->make('admin.user.transactions.manual', compact('app', 'user', 'manual_deposits', 'repo', 'date_range', 'sort'))->render();
    }

    public function listUserOtherTransactions(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_30_DAYS);

        $cash_transactions = (new TransactionsRepository($app))->getUserTransactions($user, $date_range, [3, 8, 98, 99]);

        $sort = ['column' => 2, 'type' => "desc"];

        return $app['blade']->view()->make('admin.user.transactions.others', compact('app', 'user', 'date_range', 'cash_transactions', 'sort'))->render();
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function listUserClosedLoops(Application $app, User $user, Request $request)
    {
        $repo = new TransactionsRepository($app);

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_30_DAYS);
        $countryDurations = Config::getValue('closed-loop-duration', 'cashier', null, false, true, true);
        $duration = (int)($countryDurations[$user->country] ?? $countryDurations['ROW'] ?? 45);

        $starts_at = '';
        if ($request->isMethod('POST')) {
            $user_repo = new UserRepository($user);

            $ends_at = $request->get('ends-at');
            if ($ends_at) {
                $ends_at = date('Y-m-d H:i:s', strtotime($ends_at));
                $starts_at = date('Y-m-d H:i:s', strtotime("-$duration day", strtotime($ends_at)));

                $user_repo->setSetting('closed_loop_start_stamp', $starts_at);
                $user_repo->deleteSetting('closed_loop_cleared');
                phive('Cashier')->closedLoopStartStampCron($user->id);

                $app['flash']->add('success', "Update the 'Resets At' date successfully");
            } else {
                $app['flash']->add('danger', "'Resets At' field is mandatory");
            }
        } else {
            $starts_at = $user->getSetting('closed_loop_start_stamp') ?: '';
            $ends_at = $starts_at ? date('Y-m-d H:i:s', strtotime("+$duration day", strtotime($starts_at))) : '';
        }

        $currency = $user->currency;
        $closed_loops = $repo->getClosedLoops($user, $starts_at);

        $bankSuppliers = phive('CasinoCashier')->getBankSuppliers();

        foreach ($closed_loops as &$psp) {
            $psp['account_pretty'] = in_array($psp['source_psp'], $bankSuppliers)
                ? ucwords($psp['source_scheme'] . ' - ' . $psp['account'])
                : ucwords($psp['account']);
        }

        $sort = ['column' => 0, 'type' => "asc"];

        return $app['blade']->view()->make(
            'admin.user.transactions.closed-loops',
            compact('app', 'user', 'date_range', 'closed_loops', 'currency', 'starts_at', 'ends_at', 'duration', 'sort')
        )->render();
    }
}
