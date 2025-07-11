<?php

use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class UpdateNetDepositLimitsForExistingUkPlayers extends Migration
{
    protected $masterConnection;

    const INITIAL_UK_MONTHLY_NET_DEPOSIT_LIMIT = 300000;
    const UK_LIMIT_TYPE = 'net_deposit';
    const UK_LIMIT_TIME_SPAN = 'month';
    const UK_CURRENCY = 'GBP';

    public function init()
    {
        $this->masterConnection = DB::getMasterConnection();
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $remote = getRemote();
        $remote_key = distKey($remote);

        if ($remote === 'mrvegas') {
            if ($this->isShardedDB) {
                DB::loopNodes(function ($connection) use ($remote_key, $remote) {
                    $uk_users = $connection
                        ->table('users')
                        ->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')
                        ->where('users.country', 'GB')
                        ->where('users_settings.setting', '=', $remote_key)
                        ->whereNotNull('users_settings.value')
                        ->get();
                    $this->updateNetDepositLimitForUkUsers($uk_users, $remote);
                });
            } else {
                $uk_users = $this->masterConnection
                    ->table('users')
                    ->leftJoin('users_settings', 'users_settings.user_id', '=', 'users.id')
                    ->where('users.country', 'GB')
                    ->where('users_settings.setting', '=', $remote_key)
                    ->whereNotNull('users_settings.value')
                    ->get();
                $this->updateNetDepositLimitForUkUsers($uk_users, $remote);
            }
        }
    }

    /**
     * @param Collection $uk_users
     * @param string $remote
     * @return void
     */
    private function updateNetDepositLimitForUkUsers(Collection $uk_users, string $remote)
    {
        foreach ($uk_users as $user) {
            $local_first_deposit = phive('Cashier')->getFirstDeposit($user->user_id);
            if (empty($local_first_deposit)) {
                continue;
            }

            $remote_result_response = toRemote($remote, 'getCurrentLimit',
                [
                    $user->email,
                    self::UK_LIMIT_TYPE,
                    self::UK_LIMIT_TIME_SPAN
                ]
            );
            $local_result_response = phive('Distributed')->getCurrentLimit(
                $user->email,
                self::UK_LIMIT_TYPE,
                self::UK_LIMIT_TIME_SPAN
            );

            if ($remote_result_response['success'] && $local_result_response['success']) {
                $remote_first_deposit = toRemote($remote, 'getFirstDeposit', [$remote_result_response['result']['userId']]);
                if (empty($remote_first_deposit)) {
                    continue;
                }

                $remote_result = $remote_result_response['result']['rgl'] ?? null;
                $local_result = $local_result_response['result']['rgl'] ?? null;
                $remote_currency = $remote_result_response['result']['currency'];
                $local_currency = $local_result_response['result']['currency'];

                if (empty($remote_result) && empty($local_result)) {
                    $net_limit_amount_local = self::INITIAL_UK_MONTHLY_NET_DEPOSIT_LIMIT;

                    if ($local_currency !== self::UK_CURRENCY) {
                        $net_limit_amount_local = chg(self::UK_CURRENCY, $local_currency, $net_limit_amount_local, 1);
                    }

                    rgLimits()->addLimit(
                        cu($local_result_response['result']['userId']),
                        self::UK_LIMIT_TYPE,
                        self::UK_LIMIT_TIME_SPAN,
                        $net_limit_amount_local
                    );
                } elseif (!empty($local_result) && !empty($remote_result)) {
                    $local_limit_remaining = $local_result['cur_lim'] - $local_result['progress'];
                    $remote_limit_remaining = $remote_result['cur_lim'] - $remote_result['progress'];
                    $new_limit = min($local_result['cur_lim'], $remote_result['cur_lim']);

                    if ($remote_limit_remaining > $local_limit_remaining) {
                        $new_progress = ($new_limit - $local_limit_remaining) < 0 ? $new_limit : $new_limit - $local_limit_remaining;

                        phive('Distributed')->changeRemoteLimit(
                            $local_result_response['result']['userId'],
                            self::UK_LIMIT_TYPE,
                            self::UK_LIMIT_TIME_SPAN,
                            $new_limit,
                            $local_currency,
                            $new_progress
                        );
                    } elseif ($remote_limit_remaining < $local_limit_remaining) {
                        $new_progress = $remote_limit_remaining < 0 ? $new_limit : $new_limit - $remote_limit_remaining;

                        phive('Distributed')->changeRemoteLimit(
                            $local_result_response['result']['userId'],
                            self::UK_LIMIT_TYPE,
                            self::UK_LIMIT_TIME_SPAN,
                            $new_limit,
                            $remote_currency,
                            $new_progress
                        );
                    }
                } elseif (empty($local_result)) {
                    phive('Distributed')->addRemoteLimit(
                        $local_result_response['result']['userId'],
                        self::UK_LIMIT_TYPE,
                        self::UK_LIMIT_TIME_SPAN,
                        $remote_result['cur_lim'],
                        null,
                        $remote_currency,
                        $remote_result['progress']
                    );
                } elseif (empty($remote_result)) {
                    toRemote($remote, 'addRemoteLimit',
                        [
                            $remote_result_response['result']['userId'],
                            self::UK_LIMIT_TYPE,
                            self::UK_LIMIT_TIME_SPAN,
                            $local_result['cur_lim'],
                            null,
                            $local_currency,
                            $local_result['progress']
                        ]
                    );
                }
            }
        }
    }
}