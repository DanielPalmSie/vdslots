<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 09/03/16
 * Time: 15:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Models\Action;
use App\Models\BonusType;
use App\Models\IpLog;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class BonusRepository
{
    /** @var Application $app */
    protected $app;

    /** @var int $exclusive Config coming from old project ported from Bonuses.config.php */
    protected $exclusive = 1;

    const SUSPICIOUS_CASES_LIMIT_CONFIG_NAME = 'bonuses-rewards-to-same-user-per-month-alert';

    /**
     * BonusRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getRewardsList(User $user, DateRange $date_range, Request $request)
    {
        $start_date_sql = '';
        $end_date_sql = '';
        $limit = '';
        $query_parameters = ['user_id' => $user->getKey()];
        if (!empty($date_range->getRange())) {
            $start_date_sql = "AND be.activated_time >= :start_date";
            $query_parameters['start_date'] = $date_range->getStart('timestamp');
        }
        if (!empty($date_range->getEnd())) {
            $end_date_sql = "AND be.activated_time <= :end_date";
            $query_parameters['end_date'] = $date_range->getEnd('timestamp');
        }
        if ($request->get('results', 50) != 'all') {
            $query_parameters['limit'] = $request->get('results', 50);
            $limit = "LIMIT :limit";
        }

        return DB::shSelect($user->getKey(), 'bonus_entries',"SELECT
                                  be.id                                       AS entry_id,
                                  bt.bonus_type                               AS bonus_type,
                                  bt.bonus_name                               AS bonus,
                                  bt.exclusive                                AS exclusive,
                                  IF(be.status = 'failed', 0, SUM(ct.amount)) AS bonus_amount,
                                  be.reward                                   AS bonus_reward,
                                  be.activated_time                           AS activation_time,
                                  be.last_change                              AS last_change,
                                  be.status                                   AS bonus_status,
                                  be.cost                                     AS wager_req,
                                  be.progress / be.cost * 100                 AS progress
                                    FROM bonus_entries be
                                      LEFT JOIN bonus_types bt ON be.bonus_id = bt.id
                                      LEFT JOIN cash_transactions ct ON be.id = ct.entry_id
                                    WHERE be.user_id = :user_id
                                    $start_date_sql $end_date_sql
                                    GROUP BY be.id
                                    ORDER BY be.last_change DESC $limit", $query_parameters);
    }

    public function getRewardsTransactions(User $user, DateRange $date_range, Request $request)
    {
        $start_date_sql = '';
        $end_date_sql = '';
        $limit = '';
        $query_parameters = ['user_id' => $user->getKey()];
        if (!empty($date_range->getRange())) {
            $start_date_sql = "AND ct.timestamp >= :start_date";
            $query_parameters['start_date'] = $date_range->getStart('timestamp');
        }
        if (!empty($date_range->getEnd())) {
            $end_date_sql = "AND ct.timestamp <= :end_date";
            $query_parameters['end_date'] = $date_range->getEnd('timestamp');
        }
        if ($request->get('results', 50) != 'all') {
            $query_parameters['limit'] = $request->get('results', 50);
            $limit = "LIMIT :limit";
        }

        return DB::shSelect($user->getKey(), 'cash_transactions',"SELECT
                                  bt.bonus_type AS bonus_type,
                                  bt.bonus_name AS bonus,
                                  ct.entry_id AS be_id,
                                  ct.amount AS bonus_amount,
                                  ct.currency AS currency,
                                  ct.transactiontype AS transaction_type,
                                  ct.description AS description,
                                  ct.timestamp AS transaction_time,
                                  be.activated_time AS activation_time,
                                  be.status AS bonus_status,
                                  be.cost AS wager_req,
                                  be.progress / be.cost * 100 AS progress,
                                  uc.comment AS comment
                                FROM cash_transactions ct
                                  LEFT JOIN bonus_types bt ON ct.bonus_id = bt.id
                                  LEFT JOIN bonus_entries be ON be.id = ct.entry_id
                                  LEFT JOIN users_comments uc ON uc.foreign_id = be.id
                                WHERE ct.user_id = :user_id
                                $start_date_sql $end_date_sql
                                AND (ct.bonus_id > 0 OR ct.entry_id > 0)
                                AND ct.transactiontype IN (4, 14, 15, 51, 53, 66, 67, 68, 69, 70, 71, 72, 73, 75, 76, 78, 79, 81)
                                ORDER BY ct.id DESC $limit", $query_parameters);
    }

    // TODO: Does not get the new vouchers in voucher_code table. Fix that.
    public function getVouchersData($date_range, User $user, $pending = false)
    {
        if ($pending == false) {
            $vouchers_query = DB::shTable($user->getKey(), 'vouchers')
                ->selectRaw('vouchers.*, trophy_awards.description, bonus_types.bonus_name')
                ->leftJoin('trophy_awards', 'vouchers.award_id', '=', 'trophy_awards.id')
                ->leftJoin('bonus_types', 'vouchers.bonus_id', '=', 'bonus_types.id')
                ->where('user_id', $user->getKey());

            if (empty($date_range['end_date'])) {
                $vouchers_query->where('redeem_stamp', '>', $date_range['start_date']);
            } else {
                $vouchers_query->whereBetween('redeem_stamp', $date_range);
            }

            $res = $vouchers_query->orderBy('redeem_stamp', 'desc')->get();

            $usr = $user->getKey();
            $vouchers = DB::shsSelect('actions',
                "SELECT voucher_codes.*, actions.created_at AS redeem_stamp,trophy_awards.description, bonus_types.bonus_name, COUNT(*)-1 as redeemed  FROM actions
                 LEFT JOIN voucher_codes ON binary voucher_codes.voucher_code = binary actions.descr
                 LEFT JOIN bonus_types ON voucher_codes.bonus_id = bonus_types.id
                 LEFT JOIN trophy_awards ON voucher_codes.award_id = trophy_awards.id
                 WHERE (actions.tag = 'voucher' OR actions.tag = 'voucher-redeemed') AND actions.target = $usr
                 GROUP BY voucher_codes.voucher_code"
            );

            return $res->merge($vouchers);
        } else {
            $res = DB::select("");

        }

    }

    /**
     * @return array
     */
    public function getBonusTypes()
    {
        /** @var Collection $col */
        $col = BonusType::selectRaw('DISTINCT type')->get();
        return $col->pluck('type')->all();
    }

    /**
     * @param User $user
     * @param int $limit
     * @param int $type
     * @param string $date
     * @return mixed
     */
    public function getBonusList($user, $limit, $type = null, $date = null)
    {
        $query_date = empty($date) ? $date = Carbon::now()->format('Y-m-d') : $date;

        $bonus_list_query = BonusType::select("bonus_types.*", "micro_games.*", "bonus_types.id as b_id")
            ->where('expire_time', '>=', $query_date);

        if ($limit == 0) {
            $bonus_list_query->where('deposit_limit', 0);
        } else {
            $bonus_list_query->where('deposit_limit', '>', 0);
        }

        if (!empty($type)) {
            $bonus_list_query->where('type', $type);
        }

        $bonus_list_query->leftJoin('micro_games', function($join)
            {
                $join->on('micro_games.ext_game_name', '=', 'bonus_types.game_id');
                $join->whereRaw("bonus_types.game_id is not null and trim(bonus_types.game_id) <> ''");
            })
            ->where(function ($query) use ($user) {
                $query->where('micro_games.blocked_countries', 'NOT LIKE', "%{$user->country}%")
                    ->orWhereNull('micro_games.blocked_countries');
            });

        return $bonus_list_query->get();
    }

    /**
     * todo port legacy functions
     * @param Request $request
     * @param User $user
     * @return string
     */
    public function addBonus(Request $request, User $user)
    {
        $form_elements = $request->request->all();
        $actor = UserRepository::getCurrentUser();

        if ($request->get('f-id') == 'normal') {
            if (empty($form_elements['normal-select']) || empty($form_elements['normal-bonus-comment'])) {
                return 'Elements not filled';
            }
            $msg = 'Bonus was added successfully';
            $comment = $form_elements['normal-bonus-comment'];

            $new_entry_id = phive('Bonuses')->addUserBonus($user->id, $form_elements['normal-select'], true);

            if ($new_entry_id === false) {
                return "Not possible to add this bonus.";
            }

            if ($new_entry_id) {
                phive('UserHandler')->getUser($user->id)->addComment($comment, 0, 'bonus_entries', $new_entry_id, 'id');

                if ($form_elements['deposit-select']) {
                    $bonus_name = phive('Bonuses')->getBonus($form_elements['deposit-select'])->bonus_name;
                }
                if ($form_elements['deposit-bonus-amount']) {
                    $bonus_amount = "{$form_elements['deposit-bonus-amount']} {$user->currency} cents";
                }

                $log_message = "{$actor->username} activated bonus with id {$form_elements['normal-select']} for {$user->username}";
                IpLog::logIp($actor, $user->id, IpLog::TAG_BONUS_ACTIVATING, $log_message);

                $notification = new NotificationRepository($this->app);
                $notification->checkForExceededAllowedAction($user, $actor, "bonus");
            } else {
                phive('UserHandler')->logAction($user->id, " activated bonus with id " . $form_elements['normal-select'], 'activated-bonus', true, phive('UserHandler')->getUser());
            }

            return $msg;

        } elseif ($request->get('f-id') == 'deposit') {
            if (empty($form_elements['deposit-bonus-amount']) || empty($form_elements['deposit-select']) || empty($form_elements['deposit-bonus-comment'])) {
                return json_encode($form_elements);
            }
            $msg = 'Bonus was added successfully';
            $entry_id = phive('Bonuses')->addDepositBonus($user->id, $form_elements['deposit-select'], $form_elements['deposit-bonus-amount'], true);

            if (!empty($entry_id)) {
                phive('UserHandler')->logAction($user->id, " activated bonus with id " . $form_elements['deposit-select'], 'activated-bonus', true, phive('UserHandler')->getUser());

                $log_message = "{$actor->username} activated bonus with id {$form_elements['deposit-select']} for {$user->username}";
                IpLog::logIp($actor, $user->id, IpLog::TAG_BONUS_ACTIVATING, $log_message);

                $notification = new NotificationRepository($this->app);
                $notification->checkForExceededAllowedAction($user, $actor, "bonus");
            } else {
                $msg = 'A pending or existing bonus prevented the bonus from being added';
            }

            return $msg;

        } else {
            return 'Invalid method';
        }
    }

}
