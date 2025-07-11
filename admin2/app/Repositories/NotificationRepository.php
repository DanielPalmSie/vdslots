<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 14/03/16
 * Time: 15:14
 */

namespace App\Repositories;

use App\Helpers\URLHelper;
use App\Models\Config;
use App\Models\EmailQueue;
use App\Models\IpLog;
use App\Models\User;
use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class NotificationRepository
{
    /** @var Application $app */
    protected $app;

    const SAME_USER_MONTHLY_TRANSFER_FLAG = 'funds-to-single-account-monthly-eur';

    const SAME_USER_MONTHLY_BONUS_FLAG = 'bonus-to-single-account-monthly';

    /**
     * NotificationRepository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * The function checks if someone (actor) sends a bonus to the same user (target)
     * @param User $target
     * @param ?User $actor
     * @param "bonus"|"cash" $action_type
     * @return bool
     */
    public function checkForExceededAllowedAction(User $target, ?User $actor, $action_type): bool
    {
        $types_array = [
            "bonus" => [
                'tag' => IpLog::TAG_BONUS_ACTIVATING,
                'mailer_subject' => 'Multiple bonuses/awards given to the same user within the last month notification',
                'limit_tag' => BonusRepository::SUSPICIOUS_CASES_LIMIT_CONFIG_NAME,
                'flag' => self::SAME_USER_MONTHLY_BONUS_FLAG
            ],
            "cash" => [
                'tag' => IpLog::TAG_CASH_TRANSACTIONS,
                'mailer_subject' => 'Multiple funds transferring to the same user within the last month notification',
                'limit_tag' => TransactionsRepository::SUSPICIOUS_CASES_LIMIT_CONFIG_NAME,
                'flag' => self::SAME_USER_MONTHLY_TRANSFER_FLAG
            ]
        ];

        $type = $types_array[$action_type];

        if (empty($type)) {
            $this->app['monolog']->addError("Exceeded allowed actions: invalid type '{$action_type}' requested");
            return false;
        }
        
        $type["limit"] = (int) Config::getValue($type["limit_tag"], 'manual-adjustments', 10, false, false);

        if (empty($actor)) {
            $actor = UserRepository::getCurrentUser();
        }

        $start_date = Carbon::now()->startOfDay()->subDays(30)->toDateTimeString();
        $end_date = Carbon::now()->endOfDay()->toDateTimeString();

        $bonusesMonthlyCount = (int) phive('SQL')->shs()->getValue("
            SELECT count(*)
            FROM ip_log
            WHERE actor = '{$actor->id}'
                AND tag = '{$type["tag"]}'
                AND target = '{$target->id}'
                AND created_at >= '{$start_date}'
                AND created_at <= '{$end_date}'
        ");

        if ($bonusesMonthlyCount >= $type["limit"]) {
            $this->flagUser($target, $type["flag"], $type["mailer_subject"], $actor);
            return true;
        } else {
            return false;
        }
    }

    /**
     * The function sends internal notification
     * @param User $target
     * @param string $flag
     * @param string $subject
     * @param ?User $actor
     * @return void
     */
    public function flagUser(User $target, $flag, $subject, ?User $actor)
    {
        if (empty($actor)) {
            $actor = UserRepository::getCurrentUser();
        }

        $now = Carbon::now()->toDateTimeString();
        $targetProfileLink = URLHelper::printUserProfileLink($this->app, $target->username);
        $actorProfileLink = URLHelper::printUserProfileLink($this->app, $actor->username);
        
        $first_body_line = $subject ? "<p>{$subject}.</p>" : "";
        $body = "
            <div>
                {$first_body_line}
                <p>Details:</p>
                <ul>
                    <li><b>Actor:</b> {$actorProfileLink}</li>
                    <li><b>Target user:</b> {$targetProfileLink}</li>
                    <li><b>Timestamp:</b> {$now}</li>
                </ul>
            </div>
        ";

        if ($flag && !$actor->repo->hasSetting($flag)) {
            $actor->repo->setSetting($flag, 1);
        }

        EmailQueue::sendInternalNotification(
            $subject ? $subject : "",
            $body,
            Config::getValue(
                'manual-adjustment',
                'emails',
                'payments@videoslots.com',
                false,
                true
            )
        );
    }

}
