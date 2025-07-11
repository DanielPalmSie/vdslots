<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class UpdateIpInDepositsAndPendingWithdrawalsTables extends Migration
{
    private const IP_LENGTH = 20;

    private string $table_actions = 'actions';
    private string $table_deposits = 'deposits';
    private string $table_pending_withdrawals = 'pending_withdrawals';
    private string $file_name_deposits;
    private string $file_name_withdrawals;
    private string $file_name_errors;

    public function init()
    {
        $this->file_name_deposits = getenv('STORAGE_PATH') . '/changed_ips_deposit.csv';
        $this->file_name_withdrawals = getenv('STORAGE_PATH') . '/changed_ips_withdrawals.csv';
        $this->file_name_errors = getenv('STORAGE_PATH') . '/changed_ips_errors.csv';

        file_put_contents($this->file_name_deposits,'id,old_ip,ip' . PHP_EOL, FILE_APPEND);
        file_put_contents($this->file_name_withdrawals,'id,old_ip,ip' . PHP_EOL, FILE_APPEND);
        file_put_contents($this->file_name_errors,'entity_type,entity_id,user_id,old_ip,ip' . PHP_EOL, FILE_APPEND);
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->updateDepositsIps();

        $this->updatePendingWithdrawalsIps();

        echo PHP_EOL . "You can check deposit's IPs were changed in the file {$this->file_name_deposits}";
        echo PHP_EOL . "You can check withdrawal's IPs were changed in the file {$this->file_name_withdrawals}" . PHP_EOL;
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        // This migration shouldn't be rollback
    }

    /**
     * @param int $user_id
     * @param string $timestamp
     *
     * @return string
     */
    private function getUserIp(int $user_id, string $timestamp): string
    {
        $descr = DB::shTable($user_id, $this->table_actions)
            ->where('tag','cur_ip')
            ->where('created_at','<=', $timestamp)
            ->where('target', $user_id)
            ->orderByDesc('id')
            ->value('descr');

        $ip = '';

        if (!empty($descr)) {
            $parts = explode('cur_ip to ', $descr);

            if (count($parts) === 2) {
                $ip = $parts[1];
            }
        }

        return $ip;
    }

    /**
     * Find deposits with `ip_num` length = 20
     * Check if we have IP for such users in the table `actions` with length > 20 (so it was trimmed while storing into the table `deposits`)
     * Update `ip_num` in the table `deposits`
     */
    private function updateDepositsIps(): void
    {
        DB::loopNodes(function (Connection $connection) {
            $connection->table($this->table_deposits)
                ->select(['id', 'user_id', 'timestamp', 'ip_num'])
                ->whereRaw('LENGTH(ip_num) = ' . self::IP_LENGTH)
                ->orderBy('id')
                ->each(function (stdClass $deposit) use ($connection) {
                    $ip = $this->getUserIp($deposit->user_id, $deposit->timestamp);

                    if (!empty($ip) && $ip !== $deposit->ip_num && strlen($ip) > self::IP_LENGTH) {
                        $connection
                            ->table($this->table_deposits)
                            ->where('id', $deposit->id)
                            ->update(['ip_num' => $ip]);

                        file_put_contents($this->file_name_deposits,"{$deposit->id},{$deposit->ip_num},{$ip}" . PHP_EOL, FILE_APPEND);
                        echo PHP_EOL . "DEPOSIT #{$deposit->id}. IP is changed: {$deposit->ip_num} => {$ip}" . PHP_EOL;
                    } else {
                        file_put_contents($this->file_name_errors,"'deposit',{$deposit->id},{$deposit->user_id},{$deposit->ip_num},{$ip}" . PHP_EOL, FILE_APPEND);
                        echo PHP_EOL . "EMPTY IP. Deposit #{$deposit->id}. User #{$deposit->user_id}. IP: {$ip}" . PHP_EOL;
                    }
                });
        }, true);
    }

    /**
     * Find pending_withdrawals with `ip_num` length = 20
     * Check if we have IP for such users in the table `actions` with length > 20 (so it was trimmed while storing into the table `pending_withdrawals`)
     * Update `ip_num` in the table `pending_withdrawals`
     */
    private function updatePendingWithdrawalsIps(): void
    {
        DB::loopNodes(function (Connection $connection) {
            $connection->table($this->table_pending_withdrawals)
                ->select(['id', 'user_id', 'timestamp', 'ip_num'])
                ->whereRaw('LENGTH(ip_num) = ' . self::IP_LENGTH)
                ->orderBy('id')
                ->each(function (stdClass $withdrawals) use ($connection) {
                    $ip = $this->getUserIp($withdrawals->user_id, $withdrawals->timestamp);

                    if (!empty($ip) && $ip !== $withdrawals->ip_num && strlen($ip) > self::IP_LENGTH) {
                        $connection
                            ->table($this->table_pending_withdrawals)
                            ->where('id', $withdrawals->id)
                            ->update(['ip_num' => $ip]);

                        file_put_contents($this->file_name_withdrawals,"{$withdrawals->id},{$withdrawals->ip_num},{$ip}" . PHP_EOL, FILE_APPEND);
                        echo PHP_EOL . "WITHDRAWAL #{$withdrawals->id}. IP is changed: {$withdrawals->ip_num} => {$ip}" . PHP_EOL;
                    } else {
                        file_put_contents($this->file_name_errors,"'withdrawals',{$withdrawals->id},{$withdrawals->user_id},{$withdrawals->ip_num},{$ip}" . PHP_EOL, FILE_APPEND);
                        echo PHP_EOL . "EMPTY IP. Withdrawals #{$withdrawals->id}. User #{$withdrawals->user_id}. IP: {$ip}" . PHP_EOL;
                    }
                });
        }, true);
    }
}
