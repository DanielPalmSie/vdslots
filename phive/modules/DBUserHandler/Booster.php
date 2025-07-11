<?php

class Booster extends PhModule
{
    /** @var SQL $db */
    protected $db;

    /** @var Config $config */
    protected $config;

    /** @var Casino $casino */
    protected $casino;

    /** @var Logger $cron_logger */
    protected $cron_logger;

    /** @var Logger $pay_logger */
    protected $pay_logger;

    const VAULT_KEY = 'booster_vault';

    const OPT_OUT_KEY = 'disabled_booster_vault';
    const MIN_PAYOUT_AMOUNT = 50.00;
    const REDIS_CACHE_KEY = '_booster_vault_';
    const DEFAULT_CACHE_TTL = 60; // 1-hour
    const CACHE_KEY_BALANCE = 'vault_balance';
    const TYPE_REWARD = 'REWARD';
    const TYPE_RELEASE = 'RELEASE';
    const DEFAULT_TRANSACTION_TYPES = [
        self::TYPE_REWARD    => 100,
        self::TYPE_RELEASE   => 101
    ];
    const AUTO_RELEASE_TAG = 'booster-vault-auto';
    const INTERVAL_DAILY = 'daily';
    const INTERVAL_MONTHLY = 'monthly';

    public function __construct()
    {
        $this->db           = phive('SQL');
        $this->config       = phive('Config');
        $this->casino       = phive('Casino');
        $this->cron_logger  = phive('Logger')->getLogger('cron');
        $this->pay_logger   = phive('Logger')->getLogger('payments');
    }

    /**
     * Checks if $user is in a country where this booster format is supported
     *
     * @param DBUser|int|null $user
     * @return bool
     */
    public function canUseBooster($user = null): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        return in_array($user->getCountry(), $this->getSetting('countries_enabled', []));
    }

    /**
     * Checks if the supplied user can use the booster vault
     *
     * @param DBUser|int|null $user
     * @return bool
     */
    public function hasBoosterVault($user = null): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        return $this->canUseBooster($user) && !$this->optedOutToVault($user);
    }

    /**
     * Initialize the booster vault setting
     *
     * @param int|DBUser|null $user
     * @return void
     */
    public function initBoosterVault($user = null): void
    {
        $user = cu($user);
        if (empty($user)) return;

        if ($this->canUseBooster($user)) $user->setMissingSetting(self::VAULT_KEY, 0);
    }

    /**
     * @param DBUser|int|null $user
     * @param mixed $win_amount
     * @param mixed $win_id
     * @return bool
     */
    public function transferWinAmount($user, $win_amount, $win_id)
    {
        $user = cu($user);
        if (empty($user)) return false;

        if (empty($win_id) || !$this->hasBoosterVault($user)) return false;

        $percentage     = $this->config->getValue('weekend-booster-win-vault', 'percentage', 0.5);
        $min_transfer   = $this->config->getValue('weekend-booster-win-vault', 'min-transfer', 1);

        $to_booster = $win_amount * $percentage / 100;

        if ($to_booster < $min_transfer) return false;

        return $this->transferAmount($user, round($to_booster), 'Transfer to Booster Vault', self::TYPE_REWARD, $win_id);
    }

    /**
     * @param DBUser $user
     * @param float $amount
     * @param string $description
     * @param string $type
     * @param string $win_id
     * @return bool
     */
    public function transferAmount(
        DBUser $user,
        float $amount,
        string $description = 'Booster Vault Transfer',
        string $type = self::TYPE_REWARD,
        string $win_id = ''
    ): bool {
        $result = $this->casino->changeBalance(
            $user,
            ($type === self::TYPE_REWARD) ? -$amount : $amount,
            $description,
            $this->getTransactionType($type, $user->getCountry()),
            '', 0, 0, false, $win_id
        );

        if ($result === false) return false;

        $this->setVaultBalance($user, $amount, true);
        return true;
    }

    /**
     * Release a user's booster vault
     *
     * @param int|DBUser $user
     * @param bool $partial
     * @param int $amount This is used only if $partial is true
     * @param bool $skip_notify
     * @param bool $marketing_blocked // @todo: Is this needed? Booster release is transactional not marketing
     * @param string $log_tag
     * @return false|int
     */
    public function releaseBoosterVault(
        $user = null,
        bool $partial = false,
        int $amount = 0,
        bool $skip_notify = false,
        bool $marketing_blocked = false,
        string $log_tag = 'booster-vault'
    ) {
        /** @var DBUser $user */
        $user = cu($user);
        if (empty($user)) return false;

        $available_vault = (int) $this->getVaultBalance($user);

        // Partial release of amount is allowed only in AUTO mode.
        $release_amount = (!empty($amount) && $partial) ? $amount : $available_vault;

        // User doesn't have enough balance or trying to release negative amount
        if ($available_vault <= 0 || $release_amount <= 0) return false;

        if ($release_amount > $available_vault) {
            $this->pay_logger->error('Booster Vault Release Error: Tried to release amount greater than available', [
                'tried_to_release'  => $release_amount,
                'available_amount'  => $available_vault,
                'user'              => $user->getId()
            ]);

            return false;
        }

        $user->marketing_blocked = $marketing_blocked; // @todo: Is this needed?
        phive('Localizer')->setLanguage($user->getLang(), true);

        $closing_balance    = $available_vault - $release_amount;
        $log_message        = "Booster Vault Released: {$release_amount} cents, leaving user with {$closing_balance} cents";
        $transaction_type   = $this->getTransactionType(self::TYPE_RELEASE, $user->getCountry());

        if ($this->casino->changeBalance($user, $release_amount, $log_message, $transaction_type, false) !== false) {
            $this->setVaultBalance($user, -$release_amount, true);
            phive('UserHandler')->logAction($user, $log_message, $log_tag, true);
            uEvent('transfervault', $release_amount, '', '', $user->data);

            if (!$skip_notify) {
                phive('CasinoCashier')->notifyUserTransaction($transaction_type, $user, $release_amount, true);
            }

            toWs(['msg' => t('my.booster.vault.get.funds.credited'), 'success' => true], 'booster-release', $user->getId());

            return $release_amount;
        }

        return false;
    }

    /**
     * Release each country if the current hour in the day matches
     * the schedule specified in CasinoCashier
     *
     * @see CasinoCashier::SCHEDULE_QUEUED_TRANSACTIONS
     *
     * @return void
     */
    public function releaseBySchedule()
    {
        /** @var CasinoCashier $cashier */
        $cashier = phive('CasinoCashier');

        /** @var MailHandler2 $mailer */
        $mailer = phive('MailHandler2');

        /** @var Publisher $publisher */
        $publisher = phive('Site/Publisher');

        $this->cron_logger->info('Releasing Booster Vaults for users that accumulated a balance greater then ' . self::MIN_PAYOUT_AMOUNT);

        list($scheduled, $not_scheduled) = $cashier->getScheduledCountries(CasinoCashier::SCHEDULE_QUEUED_TRANSACTIONS);

        if (empty($scheduled)) return;
        $release = ($scheduled == 'NA')
            ? $this->getUsersWithPendingBalance(['exclude' => $not_scheduled], self::MIN_PAYOUT_AMOUNT)
            : $this->getUsersWithPendingBalance(['include' => $scheduled], self::MIN_PAYOUT_AMOUNT);

        if (empty($release)) return;

        $log = ($scheduled == 'NA') ? 'not in: ' . implode(',', $not_scheduled) : 'in: ' .$scheduled;

        $count = count($release);
        $this->cron_logger->info("Releasing Booster Vault for {$count} users {$log}.");

        $allowed = $mailer->filterMarketingBlockedUsers(array_column($release, 'id'), true);
        array_walk($release, function (&$item) use ($allowed) {
            $item = [$item['id'], true, $item['balance'], false, !in_array($item['id'], $allowed), self::AUTO_RELEASE_TAG];
        });

        $publisher->bulk('booster-vault',
            'DBUserHandler/Booster',
            'releaseBoosterVault',
            $release
        );
    }

    /**
     * Get all the users and their pending vault balance
     * filtered by $countries and that have a balance greater than $min_value
     *
     * @see Booster::parseIncludeExclude() For $countries parameter
     *
     * @param array|null $countries
     * @param float $min_value
     * @return array
     */
    public function getUsersWithPendingBalance(?array $countries = null, float $min_value = 0): array
    {
        $vault_key      = self::VAULT_KEY;
        $country_where  = (!empty($countries)) ? 'AND ' . $this->parseIncludeExclude($countries, 'u.country') : '';
        $users_where[]  = ($min_value !== 0) ? "value > {$min_value}" : null;
        $users_where[]  = "setting = '{$vault_key}'";
        $users_where    = implode(' AND ', array_filter($users_where));

        $sql = <<<SQL
            SELECT u.id AS id, balances.value AS balance
            FROM users AS u
                JOIN (SELECT user_id, CONVERT(value, FLOAT) as value FROM users_settings WHERE {$users_where})
                AS balances ON balances.user_id = u.id
            WHERE
                u.active = 1
                AND u.id NOT IN (
                    # Exclude players that are not eligible for pay out
                    SELECT user_id
                    FROM users_settings
                    WHERE (
                        (setting = 'super-blocked' AND value = 1)                   OR
                        (setting = 'indefinitely-self-excluded' AND value = 1)      OR
                        (setting = 'unexclude-date' AND DATE(value) > DATE(NOW()))  OR
                        (setting = 'unlock-date' AND DATE(value) > DATE(NOW()))
                    )
                )
                {$country_where}
        SQL;

        return $this->db->shs()->loadArray($sql);
    }

    /**
     * Returns true if user opted out of booster vault,
     * else false
     *
     * @param int|DBUser|null $user
     * @return bool
     */
    public function optedOutToVault($user = null): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        return $user->hasSetting(self::OPT_OUT_KEY);
    }

    /**
     * @param int|DBUser|null $user
     * @param bool $opt_in
     * @return bool
     */
    public function optToVault($user = null, bool $opt_in = true): bool
    {
        $user = cu($user);
        if (empty($user)) return false;

        // Bust the cache
        $this->setCacheValue(self::CACHE_KEY_BALANCE, $user, null, 0);

        if ($opt_in) return $user->deleteSetting(self::OPT_OUT_KEY);
        return $user->setSetting(self::OPT_OUT_KEY, 1);
    }

    /**
     * Returns the list of all the wins for a specific player on a single day
     * with how much that contributed to the new booster
     *
     * @param int $uid
     * @param $date Y-m-d
     * @return array|bool|mixed|string|null
     */
    public function getWinsWithBoostedAmount($uid, $date, $page = 1, $page_size = 20) {
        if(empty($page) || !is_numeric($page) || $page < 1) { // to prevent fiddling with GET param
            $page = 1;
        }
        $offset = ($page - 1) * $page_size;

        $sql = "
            SELECT
                wins.id,
                wins.amount,
                wins.created_at,
                wins.currency,
                ct.amount as transferred_to_vault,
                mg.game_name
            FROM
                wins
            LEFT JOIN
	            micro_games mg ON mg.ext_game_name = wins.game_ref -- left join to avoid scenario with missing game name
            LEFT JOIN
                cash_transactions ct
                ON wins.id = ct.parent_id
                    AND ct.transactiontype = 100
                    AND ct.user_id = $uid
            WHERE
                wins.user_id = $uid
                AND created_at like '$date%'
            GROUP BY wins.id
            ORDER BY created_at ASC";
        $result = $this->db->sh($uid)->loadArray($sql);
        // I slice via PHP to avoid doing a second query to grab the total count for the paginator.
        $count = count($result);
        $result = array_slice($result, $offset, $page_size);
        return [$result, $count];
    }

    /**
     * Return for the request year|month|week combination the amount of weekend booster (vault) the player earned.
     * In case of year data is aggregated by month.
     *
     * @param $uid
     * @param null $year
     * @param null $month
     * @param null $week
     * @return array|mixed|string
     */
    public function getAggreatedWinsFromCashTransactions($uid, $year = null, $month = null, $week = null) {
        $year = (int)$year;
        $month = (int)$month;
        $week = (int)$week;

        $group_by = ' GROUP BY month';
        if(empty($year)) {
            $year = date('Y');
        }
        $year_where = "AND YEAR(date) = '$year'";

        $month_where = '';
        if(!empty($month)) {
            $month_where = " AND MONTH(date) = '$month'";
            $group_by = ' GROUP BY day';
        }
        // week is only used in admin2, we need to remove filtering by year/month
        // to prevent excluding days in a week spanning across 2 months/years Ex. 28-29-30-31-1-2-3
        $week_where = '';
        if(!empty($week)) {
            $year_where = "";
            $month_where = '';
            $week = str_pad($week, 2, '0', STR_PAD_LEFT);
            list($week_start, $week_end) = phive()->getWeekStartEnd("{$year}W{$week}");
            $week_where = " AND date BETWEEN '$week_start' AND '$week_end'";
            $group_by = ' GROUP BY day';
        }

        $sql = "
            SELECT
                currency,
                SUM(generated_booster) as transferred_to_vault,
                date,
                YEAR(date) as year,
                MONTH(date) as month,
                DAY(date) as day
            FROM
                users_daily_booster_stats
            WHERE
                user_id = {$uid}
                $year_where
                $month_where
                $week_where
            $group_by
            ORDER BY
                date ASC
        ";

        $res = phQget($sql);
        if(!empty($res)) {
            return $res;
        }

        $res = $this->db->sh($uid)->loadArray($sql);

        phQset($sql, $res, 7200);

        return $res;
    }

    /**
     * Return the sum of all the "$vault_key" from the user settings converted into EUR.
     * @return bool
     */
    public function getTotalVaultBalance()
    {
        $str = "SELECT IFNULL(SUM(us.value / c.multiplier), 0) AS total_booster_vault
                    FROM users_settings us
                      INNER JOIN users AS u ON u.id = us.user_id
                      INNER JOIN currencies AS c ON u.currency = c.code
                    WHERE us.setting = '". self::VAULT_KEY. "';";

        return current(phive('SQL')->shs('sum')->loadArray($str)[0]);
    }

    /**
     * Return the $user vault balance
     * Note: If balance is lower than 0, we return 0
     *
     * @param DBUser|int|null $user
     * @return float
     */
    public function getVaultBalance($user = null): float
    {
        $user = cu($user);
        if (empty($user)) return 0;

        // Check the cache first
        $cache = $this->getCacheValue(self::CACHE_KEY_BALANCE, $user);
        if(!empty($cache)) return (float) $cache;

        $amount = $user->getSetting(self::VAULT_KEY);

        $this->setCacheValue(self::CACHE_KEY_BALANCE, $user, $amount);
        return $amount;
    }

    /**
     * Set/Update the user's vault balance
     *
     * @param int|DBUser|null $user The user to modify
     * @param float $amount The amount to set / update
     * @param bool $update If set to true will increment to existing vault balance
     * @return Booster
     */
    public function setVaultBalance($user, float $amount, bool $update = false): Booster
    {
        $user = cu($user);
        if (empty($user)) return $this;

        if ($update) {
            $amount += $this->getVaultBalance($user);
        }

        $user->setSetting(self::VAULT_KEY, $amount, false);
        $this->setCacheValue(self::CACHE_KEY_BALANCE, $user, $amount);

        return $this;
    }

    /**
     * Get the transaction type to use for the type and country specified
     *
     * @param string $type This should be either REWARD / RELEASE
     * @param string $country 2-Letter Country Code
     * @return int
     */
    public function getTransactionType(string $type, string $country): int
    {
        $country    = strtoupper($country);
        $type       = strtoupper($type);
        $settings   = $this->getSetting('transaction_types', []);
        $map        = (isset($settings[$type])) ? $settings[$type] : [];

        foreach ($map as $code => $countries) if (in_array($country, $countries)) return $code;

        if (isset(self::DEFAULT_TRANSACTION_TYPES[$type])) return self::DEFAULT_TRANSACTION_TYPES[$type];

        return 0;
    }

    /**
     * Verifies that there are no pending vault payouts
     * as of last friday
     *
     * @return Booster
     * @throws DateMalformedStringException
     */
    public function verifyAndNotify(): Booster
    {
        // Check that we have the correct configs
        $config = $this->getSetting('vault_verifier');

        if (is_array($config) && isset($config['support_email']) && filter_var($config['support_email'], FILTER_VALIDATE_EMAIL)) {
            $unpaid = $this->getUsersThatMissedAutoPayout();
            if (!empty($unpaid)) {
                phive("MailHandler2")->sendRawMail(
                    $config['support_email'],
                    'Vault Payout Issue',
                    'There are ' . count($unpaid) . ' user that did not receive a payout last Friday.' .
                    'Check the report at /admin/booster-pending for further information.'
                );
            }
        }

        return $this;
    }

    /**
     * Get users and their pending balance which haven't been paid
     * out up to $cutoff date (last friday if not specified)
     *
     * @param DateTime|null $cutoff
     * @return array
     * @throws DateMalformedStringException
     */
    public function getUsersThatMissedAutoPayout(DateTime $cutoff = null)
    {
        if (is_null($cutoff)) {
            // Default to Last Friday
            $cutoff = new DateTime();
            $cutoff->modify('-'. ($cutoff + 2) . ' day');
            $cutoff->setTime(23, 59, 59);
        }

        // Get users that have a pending balance
        $pending = $this->getUsersWithPendingBalance(null, self::MIN_PAYOUT_AMOUNT);

        $unpaid = [];
        foreach ($pending as $p) {
            $user = cu($p['id']);
            if (empty($user)) continue;

            if (!$this->receivedAutomaticPayout($user, $cutoff)) {
                $unpaid[$p['id']] = $p['balance'];
            }
        }

        return $unpaid;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function addBoosterToCredit(int $user_id): array
    {
        if (!empty($user_id)) {
            return ((int) $this->releaseBoosterVault($user_id) > 0)
                ? ['msg' => 'success', 'success' => true]
                : ['msg' =>  t('booster.release.error.no.funds'), 'success' => false];
        } else {
            return ['msg' => t('timeout.reason.timeout'), 'success' => false];
        }
    }

    /**
     * Get Booster stats for a user for dates between $start & $end
     *
     * @param string $interval
     * @param DBUser|int|null $user
     * @param DateTime|null $start // If not provided will default to 1st Jan of this year
     * @param DateTime|null $end // If not provided will default to 31st Dec of this year
     * @return array
     * @throws InvalidArgumentException If interval provided is not valid
     * @throws Exception If there is a problem with the dates
     */
    public function getStats(
        string $interval = self::INTERVAL_MONTHLY,
        DateTime $start = null,
        DateTime $end   = null,
        $user = null
    ): array {
        $user = cu($user);
        if (empty($user)) return [];

        if (strtolower($interval) === self::INTERVAL_DAILY) {
            if (is_null($start))    $start  = new DateTime('first day of this month');
            if (is_null($end))      $end    = new DateTime('last day of this month');
            $group      = 'DAY(date)';
            $diff       = (int) $start->diff($end)->format('%d') + 1;
            $increment  = 'D';
        } else if (strtolower($interval) == self::INTERVAL_MONTHLY) {
            if (is_null($start))    $start  = new DateTime('first day of January this year');
            if (is_null($end))      $end    = new DateTime('last day of December this year');
            $group      = 'MONTH(date)';
            $diff       = (int) $start->diff($end)->format('%m') + 1;
            $increment  = 'M';
        } else {
            throw new InvalidArgumentException("Invalid interval $interval");
        }

        $start_day = $start->format('Y-m-d');
        $end_day   = $end->format('Y-m-d');

        $data = [];
        for ($i = 0; $i < $diff; $i++) {
            $date = new DateTime($start_day);
            $date->add(new DateInterval('P' . $i . $increment));
            $data[$i] = [
                'interval' => $i + 1,
                'earned' => 0,
                'released' => 0,
                'day' => $date->format('d'),
                'month' => $date->format('m'),
                'year' => $date->format('Y')
            ];
        }

        $sql = <<<SQL
            SELECT
                {$group} as 'interval',
                ABS(SUM(generated_booster)) as earned,
                ABS(SUM(released_booster)) as released
            FROM users_daily_booster_stats
            WHERE user_id = {$user->getId()} AND date >= '{$start_day}' AND date <= '{$end_day}'
            GROUP BY {$group}
        SQL;

        foreach ($this->db->sh($user->getId())->loadArray($sql) as $row) {
            $data[ $row['interval'] - 1 ]['earned']     = $row['earned'];
            $data[ $row['interval'] - 1 ]['released']   = $row['released'];
        }

        return $data;
    }

    /**
     * Check if a particular user received an automatic payout
     * by the provided the date and time (if provided)
     *
     * @param DBUser $user
     * @param DateTime|null $by
     * @return bool
     */
    private function receivedAutomaticPayout(DBUser $user, DateTime $by = null): bool
    {
        $where = [
            "target = {$user->getId()}",
            "tag = '" . self::AUTO_RELEASE_TAG . "'"
        ];

        if (!is_null($by)) $where[] = "created_at > '" . $by->format('Y-m-d H:i:s') . "'";

        $where  = implode(' AND ', $where);
        $sql    = "SELECT COUNT(*) AS CNT FROM actions WHERE {$where}";

        return (int) $this->db->sh($user->getId(), 'id', 'users')->getValue($sql) > 0;
    }

    /**
     * Get the redis cache key for this $type & user
     *
     * @param string $type
     * @param DBUser $user
     * @return string
     */
    private function makeCacheKey(string $type, DBUser $user): string
    {
        return self::REDIS_CACHE_KEY . '_' . strtolower($type) . '_' . $user->getId();
    }

    /**
     * Get a value from the Redis cache
     *
     * @param string $type
     * @param DBUser $user
     * @return mixed
     */
    private function getCacheValue(string $type, DBUser $user)
    {
        return phQget($this->makeCacheKey($type, $user));
    }

    /**
     * Set a value in the redis cache
     *
     * @param string $type
     * @param DBUser $user
     * @param mixed $value
     * @param int $ttl Minutes
     * @return Booster
     */
    private function setCacheValue(string $type, DBUser $user, $value, int $ttl = self::DEFAULT_CACHE_TTL): Booster
    {
        phQset($this->makeCacheKey($type, $user), $value, $ttl * 60);
        return $this;
    }

    /**
     * Parse the array $select where its formatted is;
     * ['include' => [], 'exclude' => []]
     * into an `IN` and `NOT IN` clause
     *
     * @param array $select
     * @param string $field
     * @return string
     */
    private function parseIncludeExclude(array $select, string $field): string
    {
        $where = [];

        if (isset($select['include'])) {
           $where[] = $field . ' IN (' . $this->db->makeIn($select['include']) . ')';
        }

        if (isset($select['exclude'])) {
            $where[] = $field . ' NOT IN (' . $this->db->makeIn($select['exclude']) . ')';
        }

        return (!empty($where)) ? implode(' AND ', $where) : '';
    }
}
