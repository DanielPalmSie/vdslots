<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 02/03/2016
 * Time: 15:27
 */

namespace App\Repositories;

use App\Classes\Dmapi;
use App\Classes\Mts;
use App\Models\EmailQueue;
use App\Models\User;
use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;
use Silex\Application;

class UserProfileRepository
{
    /**
     * TODO move this function to getSegments at UserRepo, it is there but not working, using this one in the meanwhile
     * @param $user
     * @return array
     */
    public static function getSegments(User $user)
    {
        $segments = [];

        $u = cu($user->id);
        $allSegments = phive('UserHandler')->getAllSegments();
        //$segments['this_month'] = $allSegments[!empty($user->repo->getSetting('segment')) ? $user->repo->getSetting('segment') : $user->repo->getSetting(date('Y-m', strtotime('-1 month')))['level']];
        //$segments['last_month'] = $allSegments[$user->repo->getSetting(date('Y-m', strtotime('-2 month')))['level']];
        $segments['this_month'] = $allSegments[!empty($u->getSetting('segment')) ? $u->getSetting('segment') : $u->getSegment(date('Y-m', strtotime('-1 month')))['level']];
        $segments['last_month'] = $allSegments[$u->getSegment(date('Y-m', strtotime('-2 month')))['level']];

        return $segments;
    }

    /**
     * @param Application $app
     * @param User        $user
     *
     * @return mixed
     * @throws \Exception
     */
    public static function deleteAccount(Application $app, User $user)
    {
        $keys_map = [
            'to' => $user->email,
            'actor' => $user->id,
            'target' => $user->id,
            'user_id' => $user->id,
            'to_mail' => $user->email,
            'username' => $user->username
        ];

        $actions_map = [
            "replace_username" => "replace",
            "empty_object" => "{}",
            "overwrite" => '',
            "delete" => 'delete'
        ];

        /**
         * The downside of this approach is the over configuration
         * But we'll keep it like that to prevent a giant amount of code
         * Self explanation:
         *  <table> : [
         *      'key' => [<column1>, <column2>, ...],
         *      'actions' => [
         *          <columnA> => <action_from_actions_map>,
         *          <columnB> => <action_from_actions_map>,
         *          ...
         *      ]
         *  ]
         * 'key' can contain
         *      either a "raw column" like 'user_id', 'actor', etc
         *      either "condition prefixed column" like 'or.user_id', 'and.actor'
         *        "condition prefixed column":
         *            created to enable configuring different sql operators
         *            structure: implode(<operator>, '.', <column>)
         *            if no operator is provided we have a "raw column" in which case we'll use 'and' by default
         */
        $obfuscate_map = [
            'actions' => [
                'key' => ['actor', 'or.target'],
                'actions' => [
                    'actor_username' => $actions_map['overwrite'],
                    'descr' => $actions_map['replace_username']
                ]
            ],
            'deposits' => [
                'key' => ['user_id'],
                'actions' => [
                    'ip_num' => $actions_map['overwrite'],
                    'card_hash' => $actions_map['overwrite']
                ]
            ],
            'failed_logins' => [
                'key' => ['user_id'],
                'actions' => [
                    'ip' => $actions_map['overwrite'],
                    'username' => $actions_map['overwrite']
                ]
            ],
            'ip_log' => [
                'key' => ['actor', 'or.target'],
                'actions' => [
                    'ip_num' => $actions_map['overwrite'],
                    'actor_username' => $actions_map['overwrite'],
                    'descr' => $actions_map['replace_username']
                ]
            ],

            // !Alert:  handle crm_sent_mails_events before messaging_campaign_users
            //          because the first one depends on the last one
            'crm_sent_mails_events' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'messaging_campaign_users' => [
                'key' => ['user_id'],
                'actions' => [
                    'subject' => $actions_map['replace_username'],
                    'html' => $actions_map['replace_username'],
                    'text' => $actions_map['replace_username'],
                    'smtp_events' => $actions_map['empty_object'],
                    'message_id' => $actions_map['overwrite']
                ]
            ],
            'mosms_check' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
//                this is not a solution because of the unique index on mobile
//                'actions' => [
//                    'mobile' => $actions_map['overwrite']
//                ]
            ],
            'pending_withdrawals' => [
                'key' => ['user_id'],
                'actions' => [
                    'net_email' => $actions_map['overwrite'],
                    'net_account' => $actions_map['overwrite'],
                    'bank_receiver' => $actions_map['overwrite'],
                    'bank_account_number' => $actions_map['overwrite'],
                    'iban' => $actions_map['overwrite'],
                    'mb_email' => $actions_map['overwrite'],
                    'ip_num' => $actions_map['overwrite'],
                    'paypal_email' => $actions_map['overwrite']
                ]
            ],
            'race_entries' => [
                'key' => ['user_id'],
                'actions' => [
                    'firstname' => $actions_map['overwrite']
                ]
            ],
            'tournament_entries' => [
                'key' => ['user_id'],
                'actions' => [
                    'dname' => $actions_map['overwrite']
                ]
            ],
            'triggers_log' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'users_blocked' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'ip' => $actions_map['overwrite'],
                    'actor_username' => $actions_map['overwrite']
                ]
            ],
            'users_comments' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'users_daily_game_stats' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
            'users_daily_stats' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
            'users_daily_stats_mp' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
            'users_daily_stats_total' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
            'users_game_sessions' => [
                'key' => ['user_id'],
                'actions' => [
                    'ip' => $actions_map['overwrite']
                ]
            ],
            'users_lifetime_stats' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
            'users_notifications' => [
                'key' => ['user_id'],
                'actions' => [
                    'url' => $actions_map['overwrite']
                ]
            ],
            'users_sessions' => [
                'key' => ['user_id'],
                'actions' => [
                    'ip' => $actions_map['overwrite'],
                    'fingerprint' => $actions_map['overwrite']
                ]
            ],
            'users_settings' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'allowed_ips' => [
                'key' => ['user_id'],
                'actions' => [
                    'ipnum' => $actions_map['overwrite']
                ]
            ],
            'failed_deposits' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'failed_transactions' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'game_replies' => [
                'key' => ['username'],
                'actions' => $actions_map['delete']
            ],
            'load_stats' => [
                'key' => ['user_id'],
                'actions' => [
                    'ip_num' => $actions_map['overwrite']
                ]
            ],
            'mailer_log' => [
                'key' => ['to'], // email
                'actions' => [
                    'to' => $actions_map['overwrite'],
                    'subject' => $actions_map['replace_username'],
                    'messageHTML' => $actions_map['replace_username'],
                    'messageText' => $actions_map['replace_username'],
                    'to_name' => $actions_map['overwrite']
                ]
            ],
            'mailer_queue_crm' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'mails_sent' => [
                'key' => ['to_mail'], // email
                'actions' => [
                    'to_mail' => $actions_map['overwrite'],
                    'subject' => $actions_map['replace_username'],
                    'content' => $actions_map['replace_username']
                ]
            ],
            'sms_queue' => [
                'key' => ['user_id'],
                'actions' => [
                    'msg' => $actions_map['replace_username']
                ]
            ],
            'transfer_tokens' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite']
                ]
            ],
            'trans_log' => [
                'key' => ['user_id'],
                'actions' => $actions_map['delete']
            ],
            'mailer_queue' => [
                'key' => ['to'],
                'actions' => $actions_map['delete']
            ],
            'users_messages' => [
                'key' => ['user_id'],
                'actions' => [
                    'recipient' => $actions_map['overwrite'],
                    'subject' => $actions_map['replace_username'],
                    'body' => $actions_map['replace_username']
                ]
            ],
            'users_monthly_stats' => [
                'key' => ['user_id'],
                'actions' => [
                    'username' => $actions_map['overwrite'],
                    'firstname' => $actions_map['overwrite'],
                    'lastname' => $actions_map['overwrite']
                ]
            ],
        ];

        // go on each table
        foreach ($obfuscate_map as $table => $map) {

            // init the sql query to be able to directly append (and/or)
            $sql_condition = " 1 = 1 ";
            // each defined key can be structured like <comparator>.<column> or like <column>
            foreach ($map['key'] as $key) {
                $k = explode('.', $key);

                $condition = count($k) > 1 ? $k[0] : 'and';
                $sql_key = count($k) > 1 ? $k[1] : $key;

                $sql_condition .= " {$condition} `{$table}`.`{$sql_key}` = '{$keys_map[$key]}' ";
            }

            if (is_array($map['actions'])) {

                $updated_columns = collect($map['actions'])
                    ->map(function ($value, $key) use ($actions_map, $user) {
                        if ($value == $actions_map['replace_username']) {
                            $value = "REPLACE (`{$key}`, '{$user->username}', '******')";
                            $value = "REPLACE ({$value}, '{$user->lastname}', '******')";
                            $value = "REPLACE ({$value}, '{$user->firstname}', '******')";
                            return "{$key} = {$value}";
                        }
                        return "`{$key}` = '{$value}'";
                    })
                    ->implode(', ');

                $sql_statement = "UPDATE {$table} SET {$updated_columns} WHERE {$sql_condition}";
            } elseif ($map['actions'] == $actions_map['delete']) {
                if ($table == 'crm_sent_mails_events') {
                    $message_ids = collect(DB::shsSelect(
                        'messaging_campaign_users',
                        "select message_id from messaging_campaign_users where user_id = {$user->id}"
                    ))
                        ->map(function ($el) {
                            return $el->message_id;
                        })
                        ->filter(function ($el) {
                            return !empty($el);
                        })
                        ->implode(',');
                    $sql_statement = empty($message_ids)
                        ? ""
                        : "DELETE FROM {$table} WHERE message_id in ({$message_ids})";
                } else {
                    $sql_statement = "DELETE FROM {$table} WHERE {$sql_condition}";
                }
            }

            try {
                if (!empty($sql_statement)) {
                    DB::shsStatement($table, $sql_statement);
                }
            } catch (\Exception $e) {
                // 42S02: we don't have the table
                // PROBABLY: no need to worry about it
                if ($e->getCode() !== '42S02') {
                    $app['monolog']->addError('deleteAccount', [$e->getCode(), $e->getMessage()]);
                    throw new \Exception($e);
                }
            }
        }

        // MTS
        $mts = new Mts($app);
        $mts = $mts->dataManagementRemove($user->id);
        if ($mts['success'] == false) {
            $app['monolog']->addError('deleteAccount:mts', [$mts['errors']]);
            throw new \Exception($mts['errors'][0]);
        }
        // end - MTS

        // DMAPI
        $dmapi = new Dmapi($app);
        $dmapi = $dmapi->dataManagementRemove($user->id);
        if ($dmapi['success'] == false) {
            $app['monolog']->addError('deleteAccount:dmapi', [$dmapi['errors']]);
            throw new \Exception($dmapi['errors'][0]);
        }
        // end - DMAPI

        $username = $user->username;
        $except_keys = [
            'username',
            'currency',
            'country',
            'city',
            'dob',
            'sex',
            'id'
        ];

        foreach ($user->toArray() as $key => $value) {
            $user[$key] = in_array($key, $except_keys)
                ? $value
                : '';
        }
        $user->username = "deleted_" . $user->id;
        $user->save();

        // TODO See how we can handle this logic via CRON for Spain (ES) - automatic after 4 years of suspended
        $user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_CANCELED);

        return $username;
    }

    /**
     * Send the internal emails for when a user reaches the limit
     * for displaying personal data
     *
     * @param $email
     * @param null $actor
     *
     * @throws \Exception
     */
    public function sendDisplayLimitReachedMail($email, $actor = null)
    {
        $now = Carbon::now()->toDateTimeString();

        if (empty($actor)) {
            $actor = UserRepository::getCurrentUser();
        }

        $subject = "The limit for viewing personal data was reached";

        $body = "<div>
                    <p>A user has reached the limit for viewing personal data.</p> <p>Details:</p>
                    <ul>
                        <li><b>Actor:</b> {$actor->username}</li>
                        <li>Date: $now </li>
                    </ul>
                 </div>";

        EmailQueue::sendInternalNotification($subject, $body, $email);
    }

}