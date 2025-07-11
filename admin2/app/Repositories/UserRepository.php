<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.12.22.
 * Time: 12:45
 */

namespace App\Repositories;

use App\Classes\Settings;
use App\Constants\Networks;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\Common;
use App\Helpers\DataFormatHelper;
use App\Models\Action;
use App\Models\Config;
use App\Models\Currency;
use App\Models\EmailQueue;
use App\Models\LgaLog;
use App\Models\RaceEntry;
use App\Models\User;
use App\Models\UserDailyBalance;
use App\Models\UserDailyStatistics;
use App\Models\UserSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Supplier;

class UserRepository
{
    /** @var  User $user */
    protected $user;

    protected $cached_data = [];

    /**
     * User repository constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function getCurrentUsername()
    {
        return cu()->data['username'];
    }

    public static function getCurrentUserId()
    {
        return cu()->data['id'];
    }

    public static function isCurrentSuperAdmin()
    {
        return self::getCurrentUsername() == 'admin';
    }

    public static function usersFromCountry(string $country, int $balanceMin, array $filterUserIds = []): array
    {
        $usersFilter = '';

        if (!empty($filterUserIds)) {
            $usersFilter = sprintf(' AND u.id IN (%s) ', implode(', ', $filterUserIds));
        }

        return phive('SQL')->shs()->loadArray(<<<SQL
SELECT
    DISTINCT u.id,
    u.cash_balance AS balance,
    u.country
FROM users AS u
WHERE u.country = '$country' AND u.cash_balance > $balanceMin $usersFilter
SQL
        );
    }

    public static function usersWithAML52Block(string $country, int $balanceMin, bool $previouslySelfExcluded = false, array $filterUserIds = []): array
    {
        $usersFilter = '';

        if (!empty($filterUserIds)) {
            $usersFilter = sprintf(' AND u.id IN (%s) ', implode(', ', $filterUserIds));
        }

        if ($previouslySelfExcluded) {
            return phive('SQL')->shs()->loadArray(<<<SQL
SELECT
    DISTINCT u.id,
    u.cash_balance AS balance,
    u.country
FROM users AS u
JOIN triggers_log AS tl ON tl.user_id = u.id AND tl.trigger_name = 'AML52'
LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting IN ('excluded-date', 'external-excluded', 'indefinitely-self-excluded')
WHERE u.country = '$country' AND u.cash_balance > $balanceMin $usersFilter
AND CASE
    WHEN us.setting = 'excluded-date' and us.value <= CURDATE() AND u.active = 0 THEN 1
    WHEN us.setting = 'external-excluded' and us.value != '' THEN 1
    WHEN us.setting = 'indefinitely-self-excluded' and us.value = 1 THEN 1
    ELSE 0
END
SQL
            );
        }

        $firstOfMonth = date('Y-m-01');
        return phive('SQL')->shs()->loadArray(<<<SQL
SELECT
    DISTINCT u.id,
    u.cash_balance AS balance,
    u.country
FROM users AS u
JOIN triggers_log AS tl ON tl.user_id = u.id AND tl.trigger_name = 'AML52' AND tl.created_at >= '$firstOfMonth'
LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting IN ('unexclude-date', 'external-excluded', 'indefinitely-self-excluded')
WHERE u.country = '$country' AND u.cash_balance > $balanceMin $usersFilter
AND CASE
    WHEN us.setting = 'unexclude-date' and us.value >= CURDATE() THEN 1
    WHEN us.setting = 'external-excluded' and us.value != '' THEN 1
    WHEN us.setting = 'indefinitely-self-excluded' and us.value = 1 THEN 1
    ELSE 0
END
SQL
        );
    }

    /**
     * @return User
     * @throws Exception
     */
    public static function getCurrentUser()
    {
        if (!empty($_SESSION['user_id'])) {
            return User::find($_SESSION['user_id']);
        } elseif (!empty($_SESSION['username'])) {
            return User::findByUsername($_SESSION['username']);
        } else {
            throw new Exception("Your current session is not valid or expired already", 403);
        }
    }

    public static function getUsernameFromID($user_id)
    {
        return User::find($user_id)->username;
    }

    /**
     * @return mixed
     */
    public static function getCurrentId()
    {
        return $_SESSION['user_id'];
    }

    public function getSessionFromHistory($offset, $limit)
    {
        return UserSession::sh($this->user->getKey())->where('user_id', $this->user->getKey())->limit($limit)->offset($offset)->orderBy('id', 'desc')->first();
    }

    public function getPreviousSession()
    {
        return $this->getSessionFromHistory(1, 1);
    }

    public function getCurrentSession()
    {
        return $this->getSessionFromHistory(0, 1);
    }

    //TODO check shards
    public static function getLanguages()
    {
        return DB::table('users')->selectRaw("distinct preferred_lang AS language")->get()->reduce(function($carry, $el) {
            if ($el->language != ""){
                $carry[$el->language] = strtoupper($el->language);
            }
            return $carry;
        }, []);

    }

    /* User settings related functions */
    public function hasSetting($setting)
    {
        return $this->user->settings()->where('setting', $setting)->exists();
    }

    public function getSetting($setting)
    {
        return $this->user->settings()->where('setting', $setting)->get(['value'])->first()->value;
    }

    /**
     * Return true if the user has at least one of the settings for KYC dob checks
     *
     * @return bool
     */
    public function hasDobCheck()
    {
        return $this->hasSetting('id3global_res') || $this->hasSetting('experian_res');
    }

    /**
     * Return true if the user has at least one of the settings for KYC PEP/SL checks
     *
     * @return bool
     */
    public function hasPepCheck()
    {
        return $this->hasSetting('id3global_pep_res') || $this->hasSetting('acuris_pep_res');
    }

    /**
     * Will return a string based on the result stored on the DB. (Ex. 1 => PASS, 0 => REFER)
     * The mapping is based ExternalKyc.php "DOB_RESULTS" constant in Phive.
     *
     * We first check if either of the result is a PASS and return that.
     * Otherwise we check for the single results (Experian, ID3) and return the first of them that exist.
     *
     * Called by fixUserObject, this function to work require $user->block_repo->populateSettings(); to be called before
     *
     * @return string
     */
    public function getDobCheckResult()
    {
        // Ex. ['ALERT' => -1, 'REFER' => 0, 'PASS' => 1]
        $mapping = phive('DBUserHandler/ExternalKyc')::DOB_RESULTS;

        if($this->user->block_repo->underAgeVerificationPassed()) {
            return array_search($mapping['PASS'], $mapping);
        }

        $id3_res = $this->getSetting('id3global_res');
        // We need strict comparison with '0' as string otherwise 0 values will not being displayed correctly.
        if (!empty($id3_res) || $id3_res === '0') {
            return array_search($id3_res, $mapping);
        }

        $experian_res = $this->getSetting('experian_res');
        // We need strict comparison with '0' as string otherwise 0 values will not being displayed correctly.
        if (!empty($experian_res) || $experian_res === '0') {
            return array_search($experian_res, $mapping);
        }

        return '';
    }

    /**
     * Will return the result string that is stored on the DB. (compared to DOB we don't need a reverse mapping)
     * The mapping is based ExternalKyc.php "PEP_RESULTS" constant in Phive.
     *
     * We first check if either of the result is a PASS and return that.
     * Otherwise we check for the single results (Acuris, ID3) and return the first of them that exist.
     *
     * Called by fixUserObject, this function to work require $user->block_repo->populateSettings(); to be called before
     *
     * @return string
     */
    public function getPepCheckResult()
    {
        // Ex. ['ALERT' => 'ALERT', 'REFER' => 'REFER', 'PASS' => 'PASS']
        $mapping = phive('DBUserHandler/ExternalKyc')::PEP_RESULTS;

        if(!$this->user->block_repo->isPepOrSanction()) {
            return array_search($mapping['PASS'], $mapping);
        }

        $id3_pep_res = $this->getSetting('id3global_pep_res');
        if (!empty($id3_pep_res)) {
            return $id3_pep_res;
        }

        $acuris_pep_res = $this->getSetting('acuris_pep_res');
        if (!empty($acuris_pep_res)) {
            return $acuris_pep_res;
        }

        return '';
    }

    public function getId3CheckStatus()
    {
        $map = [
            '-1' => 'ALERT',
            '0' => 'NO MATCH',
            '1' => 'PASS',
            '2' => 'REFER'
        ];

        $id3_res = $this->getSetting('id3global_res');
        if (empty($id3_res) && $id3_res != 0) {
            return '';
        }

        return isset($map[$id3_res]) ? $map[$id3_res] : $id3_res;
    }

    public function getSettingTimestamp($setting)
    {
        return $this->user->settings()->where('setting', $setting)->get(['created_at'])->first()->created_at;
    }

    public function getAllSettings()
    {
        return new Settings($this->user->settings()->select('setting', 'value')->get());
    }

    public function getLga($key)
    {
        return LgaLog::sh($this->user->getKey())->select('val')->where(['user_id' => $this->user->getKey(), 'nm' => $key])->first()->val;
    }

    /**
     * @param $setting
     * @param $value
     * @param bool $log_action
     * @param null $actor_id
     * @param string $tag
     * @return Model|bool
     * @throws Exception
     */
    public function setSetting($setting, $value, $log_action = true, $actor_id = null, $tag = '')
    {
        if ($log_action) {
            $actor = is_null($actor_id) ? $this->getCurrentUser() : User::find((int)$actor_id);
            ActionRepository::logAction($this->user, "{$actor->username} set $setting to $value", empty($tag) ? $setting : $tag);
        }

        $result = $this->hasSetting($setting) ?
            $this->user->settings()->where('setting', $setting)->first()->update(['value' => $value]) :
            $this->user->settings()->create(['setting' => $setting, 'value' => $value, 'created_at' => Carbon::now()->format('Y-m-d H:i:s')]);
        if ($result) {
            if ($result instanceof Model && $result->isDirty()) {
                return false;
            }
            if (in_array($setting, (lic('getLicSetting', ['cross_brand'], $this->user->id)['sync_settings_with_remote_brand']), true)){
                cu($this->user->id)->sendSettingToRemoteBrand($setting, $value);
                return $result;
            }
        }
        return $result;
    }

    /**
     * Delete all settings provided for the currently selected user.
     * We can pass either a single string, or an array of strings.
     * TODO check shards
     * @param string|string[] $settings
     * @return bool
     * @throws Exception
     */
    public function deleteSetting($settings)
    {
        $settings = is_array($settings) ? $settings : [$settings];
        foreach ($settings as $setting) {
            $res = $this->user->settings()->where('setting', $setting)->first();
            if ($res) {
                ActionRepository::logAction($this->user, "{$this->getCurrentUsername()} removed $setting", "deleted_setting");
                $res->delete();
                if (in_array($setting, (lic('getLicSetting', ['cross_brand'], $this->user->id)['sync_settings_with_remote_brand']), true)){
                    $this->syncResetSettingWithRemoteBrand($this->user, $setting);
                }
            }
        }
        return true;
    }

    /**
     * Sync reset setting with remote brand and log the action if successful.
     *
     * @param $user
     * @param $setting
     * @param bool $log_action default is true
     * @return bool
     * @throws Exception
     */
    public function syncResetSettingWithRemoteBrand($user, $setting, bool $log_action = true): bool
    {
        $user_id = $user->getKey();
        $remote = phive('Distributed')->getSetting('remote_brand');
        $local = getLocalBrand();
        $user_id_remote = phive('Site/Linker')->getUserRemoteId($user_id);

        if (empty($user_id_remote) || !in_array($setting, lic('getLicSetting', ['cross_brand'], $user_id)['sync_settings_with_remote_brand'], true)) {
            return false;
        }

        $response = toRemote($remote, 'syncResetSettingWithRemoteBrand', [$user_id_remote, $setting, true, $local]);
        $actor = self::getCurrentUser();

        $action_message = "{$actor->username} " . ($response['success']
                ? "set $setting to 0"
                : "couldn't set $setting to 0");

        if ($log_action) {
            ActionRepository::logAction($this->user, $action_message, $setting);
        }

        return $response['success'];
    }

    /**
     * Port of "refreshSetting" from phive DBUser, similar to setSetting, but update the timestamp too
     *
     * @param string $setting
     * @param mixed $value
     * @param bool $log_action
     * @param null|int $actor_id
     * @param string $tag
     * @return bool|Model|int
     * @throws Exception
     */
    public function refreshSetting(string $setting, $value, $log_action = true, $actor_id = null, $tag = '')
    {
        if ($log_action) {
            $actor = is_null($actor_id) ? $this->getCurrentUser() : User::find((int)$actor_id);
            ActionRepository::logAction($this->user, "{$actor->username} updated $setting to $value", empty($tag) ? "update_$setting" : $tag);
        }
        $result = $this->hasSetting($setting) ?
            $this->user->settings()->where('setting', $setting)->first()->update(['value' => $value, 'created_at' => Carbon::now()->format('Y-m-d H:i:s')]) :
            $this->user->settings()->create(['setting' => $setting, 'value' => $value, 'created_at' => Carbon::now()->format('Y-m-d H:i:s')]);

        if ($result) {
            if ($result instanceof Model && $result->isDirty()) {
                return false;
            }
            if (in_array($setting, (lic('getLicSetting', ['cross_brand'], $this->user->id)['sync_settings_with_remote_brand']), true)){
                $this->syncSetSettingWithRemote($this->user, $setting, $value);
                return $result;
            }
        }

    }

    /**
     * Admin2 wrapper for phive Licensed "trackUserStatusChanges"
     *
     * @param $status
     */
    public function trackUserStatusChanges($status)
    {
        $user_id = $this->user->getKey();
        lic('trackUserStatusChanges', [$user_id, $status], $user_id);
    }

    //TODO check shards
    public function getBalance(Carbon $date = null)
    {
        if (empty($date) || $date->isTomorrow()) {
            return $this->getMainBalance() + $this->getBonusBalance();
        } else {
            $res = UserDailyBalance::selectRaw('sum(cash_balance) + sum(bonus_balance) as balance')
                ->where([
                    'user_id' => $this->user->getKey(),
                    'source' => 0,
                    'date' => $date->toDateString()
                ])->first()->balance;
            return empty($res) ? 0 : $res;
        }
    }

    public function getMainBalance($with_format = null)
    {
        if (is_null($with_format)) {
            return $this->user->cash_balance;
        } else {
            return DataFormatHelper::nf($this->user->cash_balance);
        }
    }

    public function getBonusBalance($with_format = null)
    {
        $bonus_balance = ReplicaDB::shSelect($this->user->getKey(), 'bonus_entries', "SELECT SUM(balance) AS bal FROM bonus_entries WHERE user_id = :user_id AND status  = 'active'", ['user_id' => $this->user->getKey()]);

        if (is_null($with_format)) {
            return $bonus_balance[0]->bal;
        } else {
            return DataFormatHelper::nf($bonus_balance[0]->bal);
        }
    }

    public function hasVault() {
        return phive('DBUserHandler/Booster')->hasBoosterVault($this->user->getKey());
    }

    public function getVaultBalance() {
        return DataFormatHelper::nf(phive('DBUserHandler/Booster')->getVaultBalance($this->user->getKey()));
    }

    public function getDepositsSum(Carbon $date, $from_deposits_table = true)
    {
        if ($from_deposits_table) {
            $res = ReplicaDB::shTable($this->user->getKey(), 'deposits AS d')
                ->selectRaw('SUM(d.amount) as sa')
                ->where('d.user_id', $this->user->getKey())->where('d.status', '!=', 'disapproved')
                ->whereBetween('d.timestamp', [$date->startOfDay()->toDateTimeString(), $date->endOfDay()->toDateTimeString()])
                ->first()->sa;
            return is_null($res) ? 0 : $res;
        } else {
            return $this->getCashTransactionsSum($date, 3);
        }
    }

    public function getWithdrawalsSum(Carbon $date, $from_pending_table = true)
    {
        if ($from_pending_table) {
            $res = ReplicaDB::shTable($this->user->getKey(), 'pending_withdrawals AS pw')
                ->selectRaw('SUM(pw.amount) as sa')
                ->where('pw.user_id', $this->user->getKey())->where('pw.status', '!=', 'disapproved')
                ->whereBetween('pw.timestamp', [$date->startOfDay()->toDateTimeString(), $date->endOfDay()->toDateTimeString()])
                ->first()->sa;
            return is_null($res) ? 0 : $res;
        } else {
            return $this->getCashTransactionsSum($date, 8);
        }
    }


    public function getCashTransactionsSum(Carbon $date, $type = false, $extra = '')
    {
        if (is_int($type)) {
            $res = ReplicaDB::shSelect($this->user->getKey(), 'cash_transactions', "SELECT sum(amount) AS sa FROM cash_transactions AS ct WHERE ct.user_id = :user_id AND ct.transactiontype = :type AND ct.timestamp BETWEEN :start_date AND :end_date $extra", [
                'start_date' => $date->startOfDay()->toDateTimeString(),
                'end_date' => $date->endOfDay()->toDateTimeString(),
                'user_id' => $this->user->getKey(),
                'type' => $type
            ])[0]->sa;

        } elseif (is_array($type)) {
            $ids = join(', ', $type);
            $res = ReplicaDB::shSelect($this->user->getKey(), 'cash_transactions', "SELECT sum(amount) AS sa FROM cash_transactions AS ct WHERE ct.user_id = :user_id AND ct.transactiontype IN ({$ids}) AND ct.timestamp BETWEEN :start_date AND :end_date $extra", [
                'start_date' => $date->startOfDay()->toDateTimeString(),
                'end_date' => $date->endOfDay()->toDateTimeString(),
                'user_id' => $this->user->getKey()
            ])[0]->sa;

        } elseif ($type === false) {
            $query = ReplicaDB::shTable($this->user->getKey(), 'cash_transactions')->selectRaw('sum(amount) as sa')
                ->where('user_id', $this->user->getKey())
                ->whereBetween('timestamp', [
                    $date->startOfDay()->toDateTimeString(),
                    $date->endOfDay()->toDateTimeString()
                ]);
            if (is_array($extra)) {
                $query->whereRaw($extra['sql'], $extra['bindings']);
            } else {
                $query->whereRaw($extra);
            }

            $res = $query->first()->sa;

        } else {
            throw new Exception("Type not valid in getCashTransactions");
        }
        return is_null($res) ? 0 : $res;
    }

    public function getBetsWinsSum(Carbon $date, $table)
    {
        $res = ReplicaDB::shSelect($this->user->getKey(), $table, "SELECT sum(amount) as sa FROM {$table} WHERE created_at BETWEEN :start_date AND :end_date AND user_id = :user_id", [
            'start_date' => $date->startOfDay()->toDateTimeString(),
            'end_date' => $date->endOfDay()->toDateTimeString(),
            'user_id' => $this->user->getKey()
        ]);

        return $res[0]->sa;
    }

    /*
     *
        progress / cost
        2068 / 20000 = 0.1034
        balance: 900
        1000 * (1 - 0.1034) = 896.6
        reward * (1 - (progress / cost)) = 896.6
        stagger percent = 0.1, payouts at 9000, 8000 etc
        stagger percent = 0.2, payouts at 8000, 6000 etc
     */
    public function getRewardsBalance($with_format = null)
    {
        $entries = ReplicaDB::shSelect($this->user->getKey(), 'bonus_entries', "SELECT * FROM bonus_entries WHERE status = 'active' AND user_id = :user_id AND bonus_type = 'casinowager'", [
            'user_id' => $this->user->getKey()
        ]);

        $sum = 0;
        foreach ($entries as $e) {
            $sum += (empty($e->stagger_percent)) ? $e->reward : $e->reward - $this->getStaggerPaid($e);
        }
        return empty($with_format) ? $sum : DataFormatHelper::nf($sum);
    }

    private function getStaggerPaid($e)
    {
        $stagger_amount = $e->reward * $e->stagger_percent;
        $reward_progress = $e->reward * ($e->progress / $e->cost);
        $nlvls = floor($reward_progress / $stagger_amount);
        return $nlvls * $stagger_amount;
    }

    public function getDepositsList(?Carbon $start_date = null, ?Carbon $end_date = null): array
    {
        $date_filter = ($start_date && $end_date)
            ? "AND timestamp BETWEEN '{$start_date->format('Y-m-d H:i:s')}' AND '{$end_date->format('Y-m-d H:i:s')}'"
            : "";

        $query = "
            SELECT dep_type, card_hash, scheme, SUM(amount) AS dep_sum
            FROM deposits
            WHERE user_id = {$this->user->getKey()}
                AND status = 'approved' $date_filter
            GROUP BY dep_type, card_hash, scheme
            ORDER BY dep_sum DESC
        ";

        $deposits_to_compare = $deposits = ReplicaDB::shSelect($this->user->getKey(), 'deposits', $query);

        // Filtering and grouping old format PANs
        foreach ($deposits_to_compare as $deposit_index => $deposit) {
            if (
                in_array($deposit->dep_type, [Supplier::Emp, Supplier::WireCard])
                && strpos($deposit->card_hash, " ") === false
            ) {
                $card_number = $this->getNormalizeCardNumber($deposit->card_hash);

                foreach ($deposits_to_compare as $compare_deposit_index => $compare_deposit) {
                    $compare_card_number = $this->getNormalizeCardNumber($deposit->card_hash);

                    if (
                        $card_number == $compare_card_number
                        && $compare_deposit->card_hash != $deposit->card_hash
                        && $deposit->dep_type == $compare_deposit->dep_type
                    ) {
                        $deposits[$compare_deposit_index]->dep_sum += $deposit->dep_sum;
                        unset($deposits[$deposit_index]);
                    }
                }
            }
        }

        $result = ['sum' => 0, 'list' => []];

        foreach ($deposits as $deposit) {
            $result['sum'] += $deposit->dep_sum;
            $result['list'][$deposit->dep_type]['method_sum'] = ($result['list'][$deposit->dep_type]['method_sum'] ?? 0) + $deposit->dep_sum;

            switch ($deposit->dep_type) {
                case Supplier::Neteller:
                    $scheme = $this->user->settings_repo->settings->{'net_account'};
                    break;

                case Supplier::Skrill:
                    $scheme = $deposit->scheme ?: $this->user->settings_repo->settings->{'mb_email'};
                    break;

                case Supplier::PAYPAL:
                    $scheme = $this->user->settings_repo->settings->{'paypal_email'};
                    break;

                default:
                    $scheme = strtoupper($deposit->scheme);
                    break;
            }

            $result['list'][$deposit->dep_type]['data'][] = [
                'method' => $deposit->dep_type,
                'scheme' => $scheme,
                'card_hash' => $deposit->card_hash,
                'amount' => $deposit->dep_sum
            ];
        }

        return $result;
    }

    public function getWithdrawalsList(?Carbon $start_date = null, ?Carbon $end_date = null): array
    {
        $date_filter = ($start_date && $end_date)
            ? "AND timestamp BETWEEN '{$start_date->format('Y-m-d H:i:s')}' AND '{$end_date->format('Y-m-d H:i:s')}'"
            : "";

        $query = "
            SELECT
                payment_method, wallet, scheme, net_email, net_account, mb_email,
                CASE WHEN char_length(scheme) > 19 THEN substring(scheme, 6) ELSE scheme END AS mod_scheme,
                bank_name, iban, bank_account_number, SUM(amount) AS with_sum
            FROM pending_withdrawals
            WHERE user_id = {$this->user->getKey()}
                AND status = 'approved' $date_filter
            GROUP BY bank_name, payment_method, wallet, mod_scheme, bank_account_number, net_account, mb_email, iban
            ORDER BY with_sum DESC;
        ";

        $withdrawals = ReplicaDB::shSelect($this->user->getKey(), 'pending_withdrawals', $query);

        $result = ['sum' => 0, 'list' => []];
        foreach ($withdrawals as $withdrawal) {
            $accountDetails = [
                $withdrawal->iban,
                $withdrawal->bank_account_number,
                trim(strtoupper($withdrawal->wallet) . ' ' . $withdrawal->scheme),
                $withdrawal->net_account,
                $withdrawal->mb_email
            ];

            $account_number = current(array_filter($accountDetails));

            $multiple_accounts_label = '';
            switch ($withdrawal->payment_method) {
                case Supplier::Trustly:
                    $group_key = $withdrawal->payment_method;
                    $single_account_label = "{$withdrawal->payment_method} {$withdrawal->bank_name} {$account_number}";
                    $multiple_accounts_label = "{$withdrawal->bank_name} {$account_number}";
                    break;

                case Supplier::SWISH:
                case Supplier::Skrill:
                    $group_key = $withdrawal->payment_method;
                    $single_account_label = "{$withdrawal->payment_method} {$account_number}";
                    $multiple_accounts_label = "{$account_number}";
                    break;

                default:
                    $group_key = $withdrawal->bank_name;
                    $single_account_label = "{$withdrawal->bank_name} {$withdrawal->payment_method} {$account_number}";

                    if ($account_number) {
                        $multiple_accounts_label = "{$withdrawal->payment_method} {$account_number}";
                    }
                    break;
            }

            $result['sum'] += $withdrawal->with_sum;
            $result['list'][$group_key]['method_sum'] = ($result['list'][$group_key]['method_sum'] ?? 0) + $withdrawal->with_sum;

            $result['list'][$group_key]['data'][] = [
                'method' => $withdrawal->payment_method,
                'account_number' => $account_number,
                'single_account_label' => trim($single_account_label),
                'multiple_accounts_label' => trim($multiple_accounts_label),
                'scheme' => $withdrawal->bank_name,
                'amount' => $withdrawal->with_sum
            ];
        }

        return $result;
    }

    /**
     * @param Carbon $start_date
     * @param Carbon $end_date
     * @return Collection
     */
    public function getGameStats($start_date = null, $end_date = null)
    {
        if (empty($this->cached_data['game_stats'])) {
            if (!empty($start_date) && !empty($end_date)) {
                $date_sql = "AND users_daily_game_stats.date BETWEEN '{$start_date->format('Y-m-d')}' AND '{$end_date->format('Y-m-d')}'";
            } else {
                $date_sql = '';
            }
            $user_id = $this->user->getKey();
            $res = ReplicaDB::shSelect($user_id, 'users_daily_game_stats', "SELECT
                      micro_games.game_name,
                      game_ref,
                      wag_sum,
                      win_sum,
                      wag_sum / total_wag * 100      AS percentage,
                      users_games_favs.id AS fav_id,
                      (wag_sum - win_sum)      AS gross,
                      win_sum / wag_sum * 100        AS rtp
                    FROM (SELECT
                            game_ref,
                            sum(bets)                 AS wag_sum,
                            sum(wins)                 AS win_sum,
                            count(game_ref)           AS played_times,
                            (SELECT sum(bets)
                             FROM users_daily_game_stats
                             WHERE user_id = $user_id $date_sql) AS total_wag,
                            (SELECT sum(wins)
                             FROM users_daily_game_stats
                             WHERE user_id = $user_id $date_sql) AS total_win,
                            (SELECT count(id)
                             FROM users_daily_game_stats
                             WHERE user_id = $user_id $date_sql) AS total_fav
                          FROM users_daily_game_stats
                          WHERE user_id = $user_id $date_sql
                          GROUP BY game_ref
                          ORDER BY wag_sum DESC) d
                    LEFT JOIN micro_games ON d.game_ref = micro_games.ext_game_name
                    LEFT JOIN users_games_favs ON users_games_favs.user_id = $user_id AND micro_games.id = users_games_favs.game_id
                    GROUP BY game_ref");

            return $this->cached_data['game_stats'] = collect($res);
        } else {
            return $this->cached_data['game_stats'];
        }

    }

    /**
     * @param Carbon $start_date
     * @param Carbon $end_date
     * @param bool $with_format
     * @return string
     */
    public function getWagerData($start_date = null, $end_date = null, $with_format = true)
    {
        $cached_key = "wager-data-$start_date-$end_date";
        if (empty($this->cached_data[$cached_key])) {
            if(replicaDatabaseSwitcher() == true) {
                $query = $this->user->setConnection($this->user->setReplicaConnectionName($this->user->getKey()))->dailyStats();
            } else {
                $query = $this->user->dailyStats();
            }

            if (empty($start_date) && empty($end_date)) {
                $res = $query->sum('bets');
            } elseif (empty($end_date)) {
                $res = $query->where('date', '>=', $start_date->format('Y-m-d'))->sum('bets');
            } else {
                $res = $query->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])->sum('bets');
            }
            $this->cached_data[$cached_key] = $res;
        }

        return $with_format ? DataFormatHelper::nf($this->cached_data[$cached_key]) : $this->cached_data[$cached_key];
    }

    /**
     * @param Carbon $start_date
     * @param Carbon $end_date
     * @param bool $with_format
     * @return string
     */
    public function getGrossData($start_date = null, $end_date = null, $with_format = true)
    {
        $cached_key = "gross-data-$start_date-$end_date";
        if (empty($this->cached_data[$cached_key])) {
            $query = $this->user->dailyStats();
            if (empty($start_date) && empty($end_date)) {
                $res = $query->sum('gross');
            } elseif (empty($end_date)) {
                $res = $query->where('date', '>=', $start_date->format('Y-m-d'))->sum('gross');
            } else {
                $res = $query->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])->sum('gross');
            }
            /*$query = $this->user->dailyStats();
            $today_start = Carbon::now()->startOfDay()->toDateTimeString();
            $today_query = "SELECT (SELECT IFNULL((sum(amount) - sum(jp_contrib)),0)  AS sum_amount FROM bets
                                WHERE user_id = {$this->user->getKey()} AND created_at >= '{$today_start}')
                                - (SELECT IFNULL(sum(amount),0) AS sum_amount FROM wins
                                WHERE user_id = {$this->user->getKey()} AND created_at >= '{$today_start}') as sum";
            if (empty($start_date) && empty($end_date)) {
                $res = $query->sum('gross') + DB::select($today_query)[0]->sum;
            } elseif (empty($end_date)) {
                $res = $query->where('date', '>=', $start_date->format('Y-m-d'))->sum('gross') + DB::select($today_query)[0]->sum;
            } else {
                $res = $query->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])->sum('gross');
                if ($end_date->isSameDay(Carbon::now())) {
                    $res += DB::select($today_query)[0]->sum;
                }
            }*/
            $this->cached_data[$cached_key] = $res;
        }

        return $with_format ? DataFormatHelper::nf($this->cached_data[$cached_key]) : $this->cached_data[$cached_key];
    }


    /**
     * @param null $start_date
     * @param null $end_date
     * @param bool $with_format
     *
     * @return mixed|string
     */
    public function getNGRData(
        $start_date = null,
        $end_date = null,
        $with_format = true
    ) {
        $key = "ngr-data-$start_date-$end_date";
        $user_id = $this->user->getKey();

        $end_date = $end_date ?? Carbon::now();
        $start_date = $start_date ?? Carbon::parse($this->user->getAttributeValue('register_date'));

        $getCachedData = function () use ($with_format, $key, $end_date) {
            return $with_format
                ? DataFormatHelper::nf($this->cached_data[$key])
                : $this->cached_data[$key];
        };

        $q = "
            SELECT IFNULL((
                SELECT SUM(uds.gross)
                FROM users_daily_stats uds
                WHERE uds.user_id = {$user_id}
                AND uds.date BETWEEN '{$start_date}' AND '{$end_date}'
            ), 0) - IFNULL((
                SELECT SUM(ABS(ct.amount))
                FROM cash_transactions ct
                WHERE ct.user_id = {$user_id}
                AND ct.timestamp BETWEEN '{$start_date}' AND '{$end_date}'
                AND transactiontype IN (
                    14,32,31,51,66,69,74,77,80,82,84,85,86
                )
            ), 0) + IFNULL((
                SELECT SUM(ABS(ct.amount))
                FROM cash_transactions ct
                LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
                WHERE ct.user_id = {$user_id}
                AND ct.timestamp BETWEEN '{$start_date}' AND '{$end_date}'
                AND (
                    ct.transactiontype IN (
                        53,67,72,75,78,81
                    ) OR (
                        ct.transactiontype = 15 AND (
                            bt.bonus_type != 'freespin' OR
                            bt.bonus_type != 'casinowager' OR
                            bt.bonus_type IS NULL
                        )
                    )
                )
            ), 0) as total
        ";

        return empty($this->cached_data[$key])
            ? collect(ReplicaDB::shSelect($user_id, 'users', $q))
                ->tap(function ($data) use ($key) {
                    $this->cached_data[$key] = $data[0]->total;
                })
                ->pipe($getCachedData)
            : $getCachedData();
    }

    /**
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @param bool $with_format
     * @param string|null $network
     * @param string $product
     *
     * @return mixed|string
     */
    public function getSportsWagerData(
        Carbon $start_date = null,
        Carbon $end_date = null,
        bool $with_format = true,
        ?string $network = 'betradar',
        string $product = 'S'
    ) {
        $net_abb = $this->getNetworkAbbreviation($product, $network);
        $cached_key = "$net_abb-wager-data-$start_date-$end_date";
        if (empty($this->cached_data[$cached_key])) {
            $query = $this->user->dailyStatsSports();
            if ($network !== null) {
                $query->where('network', $network);
            }
            $query->where('product', $product);
            if (empty($start_date) && empty($end_date)) {
                $res = $query->sum('bets');
            } elseif (empty($end_date)) {
                $res = $query->where('date', '>=', $start_date->format('Y-m-d'))->sum('bets');
            } else {
                $res = $query->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])->sum('bets');
            }
            $this->cached_data[$cached_key] = $res;
        }

        return $with_format ? DataFormatHelper::nf($this->cached_data[$cached_key]) : $this->cached_data[$cached_key];
    }

    /**
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @param bool $with_format
     * @param string|null $network
     * @param string $product
     *
     * @return mixed|string
     */
    public function getSportsGrossData(
        Carbon $start_date = null,
        Carbon $end_date = null,
        bool $with_format = true,
        ?string $network = 'betradar',
        string $product = 'S'
    ) {
        $net_abb = $this->getNetworkAbbreviation($product, $network);
        $cached_key = "$net_abb-gross-data-$start_date-$end_date";
        if (empty($this->cached_data[$cached_key])) {
            $daily_stats_sports = $this->user->dailyStatsSports();
            $query = $daily_stats_sports->selectRaw('IFNULL(SUM(bets),0) - IFNULL(SUM(wins),0) - IFNULL(SUM(void),0) as ngr');

            if ($network !== null) {
                $query->where('network', $network);
            }
            $query->where('product', $product);

            if (empty($start_date) && empty($end_date)) {
                $res = $query->first()->ngr;
            }
            elseif (empty($end_date)) {
                $res = $query->where('date', '>=', $start_date->format('Y-m-d'))->first()->ngr;
            } else {
                $res = $query->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])->first()->ngr;
            }

            $this->cached_data[$cached_key] = $res;
        }

        return $with_format ? DataFormatHelper::nf($this->cached_data[$cached_key]) : $this->cached_data[$cached_key];
    }

    /**
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @param bool $with_format
     * @param string|null $network
     * @param string $product
     *
     * @return mixed|string
     */
    public function getSportsNGRData(
        Carbon $start_date = null,
        Carbon $end_date = null,
        bool $with_format = true,
        ?string $network = 'betradar',
        string $product = 'S'
    ) {
        //Currently just returning gross because there is no bonus/reward yet for sports
        return $this->getSportsGrossData($start_date, $end_date, $with_format, $network, $product);
    }

    /**
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @return array
     */
    // No need to add new weekend booster here (it's not a bonus)
    public function getRewardsData($start_date = null, $end_date = null)
    {
        if (empty($start_date) && empty($end_date)) {
            $interval_condition = "";
        } elseif (empty($end_date)) {
            $interval_condition = "AND ct.timestamp > '{$start_date->format('Y-m-d')}' ";
        } else {
            $interval_condition = "AND ct.timestamp BETWEEN '{$start_date->format('Y-m-d')}' AND '{$end_date->format('Y-m-d')}' ";
        }
        $rewards_map = [
            31 => 'weekend booster',
            32 => 'casino race',
            51 => 'freespins cost',
            66 => 'casino',
            69 => 'casinowager',
            74 => 'battle tickets',
            77 => 'trophies',
            80 => 'trophies',
            82 => 'zeroing out balance',
            84 => 'casino',
            85 => 'battle joker',
            86 => 'battle bounty'
        ];

        $rewards_list = ReplicaDB::shSelect($this->user->getKey(), 'cash_transactions', "
            SELECT
              ct.transactiontype,
              SUM(IF(ct.timestamp >= :start_month, ct.amount, 0)) AS cur_month_sum,
              SUM(ct.amount) AS all_sum,
              bt.bonus_type,
              ct.description
            FROM cash_transactions ct
              LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
            WHERE ct.user_id = :user_id
              AND transactiontype IN (32,31,51,66,69,74,77,80,82,84,85,86)
              $interval_condition
            GROUP BY ct.transactiontype, bt.bonus_type
        ", [
            'user_id' => $this->user->getKey(),
            'start_month' => Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s')
        ]);

        $type14_list = ReplicaDB::shSelect($this->user->getKey(), 'cash_transactions', "
            SELECT
              ct.transactiontype,
              IF(ct.timestamp >= :start_month, ct.amount, 0) AS cur_month_sum,
              ct.amount AS all_sum,
              bt.bonus_type,
              ct.description
            FROM cash_transactions ct
              LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
            WHERE ct.user_id = :user_id
              AND transactiontype = 14
              $interval_condition
        ", [
            'user_id' => $this->user->getKey(),
            'start_month' => Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s')
        ]);

        $rewards_list = array_merge($rewards_list, $type14_list);

        $res = [
            'sum' => [
                'all' => 0,
                'month' => 0
            ]
        ];
        foreach ($rewards_list as $reward) {
            $res['sum']['all'] += $reward->all_sum;
            $res['sum']['month'] += $reward->cur_month_sum;
            if ($reward->transactiontype == 14 || (!is_null($reward->bonus_type) && $reward->transactiontype != 51)) {
                $tmp_all_name = $this->getUncategorizedBonusesName($reward);
                if ($reward->cur_month_sum > 0) {
                    $tmp_month_name = $this->getUncategorizedBonusesName($reward);
                }
            } else {
                $tmp_all_name = $rewards_map[$reward->transactiontype];
                if ($reward->cur_month_sum > 0) {
                    $tmp_month_name = $rewards_map[$reward->transactiontype];
                }
            }
            $tmp_all_amount = $reward->all_sum;
            $res['list']['all'][$tmp_all_name] = empty($res['list']['all'][$tmp_all_name]) ? $tmp_all_amount : $res['list']['all'][$tmp_all_name] + $tmp_all_amount;
            if ($reward->cur_month_sum > 0) {
                $tmp_month_amount = $reward->cur_month_sum;
                $res['list']['month'][$tmp_month_name] = empty($res['list']['month'][$tmp_month_name]) ? $tmp_month_amount : $res['list']['month'][$tmp_month_name] + $tmp_month_amount;
            }
        }

        return $res;
    }

    private function getUncategorizedBonusesName($reward)
    {
        if (Common::isLike('-aid-', $reward->description)) {
            return DataFormatHelper::getCashTransactionsTypeName(74);
        } elseif ($reward->description == '#partial.bonus.payout') {
            return !empty($reward->bonus_type) ? $reward->bonus_type : 'Partial bonus';
        } elseif (Common::isLike('Free cash reward', $reward->description)) {
            return 'Free cash reward';
        } elseif (Common::isLike('Trophy reward top up', $reward->description)) {
            return 'Trophy reward top up';
        } elseif (Common::isLike('Bonus Activation', $reward->description)) {
            return !empty($reward->bonus_type) ? $reward->bonus_type : 'Bonus activation';
        } elseif (Common::isLike('Admin transferred money', $reward->description)) {
            $sub_type = DataFormatHelper::getCashTransactionsTypeName($reward->transactiontype);
            return "Admin transfer ($sub_type)";
        } elseif (Common::isLike('Bonus reactivation', $reward->description)) {
            return !empty($reward->bonus_type) ? $reward->bonus_type : 'Bonus reactivation';
        } else {
            return empty($reward->bonus_type) ? "uncategorized" : $reward->bonus_type;
        }
    }

    /**
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @return array
     */
    public function getFailedRewardsData($start_date = null, $end_date = null)
    {
        if (empty($start_date) && empty($end_date)) {
            $interval_condition = "";
        } elseif (empty($end_date)) {
            $interval_condition = "AND ct.timestamp > '{$start_date->format('Y-m-d')}' ";
        } else {
            $interval_condition = "AND ct.timestamp BETWEEN '{$start_date->format('Y-m-d')}' AND '{$end_date->format('Y-m-d')}' ";
        }
        $failed_rewards = ReplicaDB::shSelect($this->user->getKey(), 'cash_transactions', "
            SELECT
              SUM(IF(ct.timestamp >= :start_month, ABS(ct.amount), 0)) AS cur_month_sum,
              SUM(ABS(ct.amount)) AS all_sum
            FROM cash_transactions ct
            LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
            WHERE ct.user_id = :user_id
              AND (ct.transactiontype IN (53,67,72,75,78,81) OR
                  (ct.transactiontype = 15 AND (bt.bonus_type != 'freespin' OR bt.bonus_type != 'casinowager' OR bt.bonus_type IS NULL)))
              $interval_condition
        ", [
            'user_id' => $this->user->getKey(),
            'start_month' => Carbon::now()->firstOfMonth()->format('Y-m-d H:i:s')
        ]);

        return [
            'all' => $failed_rewards[0]->all_sum,
            'month' => $failed_rewards[0]->cur_month_sum
        ];
    }

    // No need to add new weekend booster here (it's not a bonus)
    public function getCashbackThisWeek($with_format = null)
    {
        $cashback = UserDailyStatistics::sh($this->user->getKey(), true)
            ->selectRaw('SUM(gen_loyalty) as sum')
            ->where('date', '>=', Carbon::now()->startOfWeek()->format('Y-m-d'))
            ->where('date', '<=', Carbon::now()->format('Y-m-d'))
            ->where('user_id', $this->user->getKey())
            ->first()->sum;

        if (is_null($with_format)) {
            return empty($cashback) ? 0 : $cashback;
        } else {
            return DataFormatHelper::nf(empty($cashback) ? 0 : $cashback);
        }
    }

    // No need to add new weekend booster here (it's not a bonus)
    public function getPendingCashback($with_format = null)
    {
        $pending = ReplicaDB::shSelect($this->user->getKey(), 'queued_transactions', "SELECT DISTINCT amount AS gen_loyalty, queued_transactions.*, users.username
                        FROM queued_transactions
                        LEFT JOIN users ON queued_transactions.user_id = users.id
                         WHERE queued_transactions.user_id = :user_id AND transactiontype = 31
                         GROUP BY queued_transactions.user_id", ['user_id' => $this->user->getKey()])[0]->gen_loyalty;

        if (is_null($with_format)) {
            return empty($pending) ? 0 : $pending;
        } else {
            return DataFormatHelper::nf(empty($pending) ? 0 : $pending);
        }
    }

    public function getCurrentRaceData()
    {
        $user_id = $this->user->getKey();
        $query_res = ReplicaDB::shSelect($user_id, 'race_entries', "SELECT
                              re.r_id,
                              r.prizes,
                              re.race_balance,
                              re.prize * c.mod AS prize,
                              re.spot
                            FROM race_entries re
                              LEFT JOIN races r ON r.id = re.r_id
                              LEFT JOIN currencies c ON c.code = :user_currency
                            WHERE re.user_id = :user_id AND r.closed = 0
                            ORDER BY re.id DESC
                            LIMIT 1", [
            'user_id' => $user_id,
            'user_currency' => $this->user->currency
        ])[0];

        $position = ReplicaDB::shSelect($user_id, 'race_entries', "SELECT spot FROM (SELECT user_id, spot
                                        FROM race_entries
                                        WHERE r_id = :r_id
                                        ORDER BY race_balance DESC
                                        LIMIT 0, :c_limit) AS m WHERE m.user_id = :user_id", [
            'r_id' => $query_res->r_id,
            'c_limit' => count(explode(':', $query_res->prizes)),
            'user_id' => $user_id
        ])[0]->spot;

        if (!empty($position)) {
            return [
                'race_balance' => $query_res->race_balance,
                'spot' => $position,
                'prize' => \App\Helpers\DataFormatHelper::nf($query_res->prize) . ' ' . $this->user->currency
            ];
        } else {
            return [
                'race_balance' => 'n/a',
                'spot' => 'n/a',
                'prize' => 'n/a'
            ];
        }
    }

    public function getClashPayoutForWeek($week_offset = 0, $isReplica = false)
    {
        $start = Carbon::now()->startOfWeek()->addWeeks($week_offset);
        $end = Carbon::now()->endOfWeek()->addWeeks($week_offset);

        $query = RaceEntry::sh($this->user->getKey(), $isReplica)
            ->leftJoin('races', 'races.id', '=', 'r_id')
            ->where('races.start_time', '>=', $start->format('Y-m-d H:i:s'))
            ->where('races.end_time', '<=', $end->format('Y-m-d H:i:s'))
            ->where('user_id', $this->user->getKey());

        if ($end->lt(Carbon::now()))
            $query->where('races.closed', '=', 1);

        //I don't understand this? so the prize is an award? how can we know then the pending payout?we would need to get a proper list of awards and to a query on awards a sum that
        return $query->sum('prize');
    }

    public function getLastIssue()
    {
        if (empty($this->cached_data['last_issue'])) {
            return $this->cached_data['last_issue'] = $this->user->issues()->where('users_issues.status', 1)->first();
        } else {
            return $this->cached_data['last_issue'];
        }
    }

    public function getLastComplaint()
    {
        if (empty($this->cached_data['last_issue'])) {
            $this->cached_data['last_issue'] = $this->user->complaints()->with('actor')->where('status', 1)->first();
        }

        return $this->cached_data['last_issue'];
    }

    public function getAllComments()
    {
        return DB::shsSelect(
            $this->user->getKey(),
            'users_comments',
            "SELECT DISTINCT * FROM users_comments WHERE user_id = {$this->user->getKey()} order by sticky desc, created_at desc"
        );
    }

    public function getCurrencyObject()
    {
        if (empty($this->cached_data['currency'])) {
            return $this->cached_data['currency'] = Currency::where('code', $this->user->currency)->first();
        } else {
            return $this->cached_data['currency'];
        }

    }

    public function getUsernameInForums()
    {
        return $this->user->settings()->where('setting', 'like', 'forum-username-%')->get();
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        $segments = [];

        $all_segments = DataFormatHelper::getSegments();

        $segments['this_month'] = $all_segments[!empty($this->getSetting('segment')) ? $this->getSetting('segment') : $this->getSetting(date('Y-m', strtotime('-1 month')))['level']];
        $segments['last_month'] = $all_segments[$this->getSetting(date('Y-m', strtotime('-2 month')))['level']];

        return $segments;
    }

    /**
     *
     * @param $period
     * @param $first
     * @param $second
     * @param $conditions
     * @param bool $join_profile_rating
     * @return string
     */
    public static function getRiskScoreQuery($period, $first, $second, $conditions, $join_profile_rating = false ) {
        if ($join_profile_rating) {
            $join_profile_rating_select_snippet = ", rprl.rating AS profile_rating, rprl.rating_tag AS profile_rating_tag";
            $join_profile_rating_join_snippet = "
                LEFT JOIN (
                  ".RiskProfileRatingRepository::getLatestProfileRatingQuery()."
                ) AS rprl ON tl.user_id = rprl.user_id
            ";
        }

        //The cast on u.currency is made so the currencies index can be used

        return "
        SELECT '{$period}' AS period,
            tl.user_id, u.username, u.country, u.active,
            $first AS first_score,
            $second AS second_score,
            t.name AS trigger_name,
            concat(
                IFNULL(formus.value, 0),
                IFNULL(proofus.value, 0),
                IFNULL(forcedloss.value, 0),
                IFNULL(forcedwager.value, 0),
                IFNULL(forceddep.value, 0),
                u.active
            ) as declaration_proof
            {$join_profile_rating_select_snippet}
        FROM triggers_log tl
        LEFT JOIN users u ON u.id = tl.user_id
        LEFT JOIN triggers t ON tl.trigger_name = t.name
        LEFT JOIN users_settings formus ON formus.user_id = u.id AND formus.setting = 'source_of_funds_activated'
        LEFT JOIN users_settings proofus ON proofus.user_id = u.id AND proofus.setting = 'proof_of_wealth_activated'
        LEFT JOIN users_settings forceddep ON forceddep.user_id = u.id AND forceddep.setting = 'force-dep-lim'
        LEFT JOIN users_settings forcedloss ON forcedloss.user_id = u.id AND forcedloss.setting = 'force-lgaloss-lim'
        LEFT JOIN users_settings forcedwager ON forcedwager.user_id = u.id AND forcedwager.setting = 'force-lgawager-lim'
        LEFT JOIN currencies c ON c.code = CAST(u.currency AS CHAR(3) CHARACTER SET ascii)
        {$join_profile_rating_join_snippet}
        WHERE 1 $conditions
        AND CASE
            WHEN t.ngr_threshold != 0
                THEN t.ngr_threshold < (
                    SELECT IFNULL((
                        SELECT SUM(uds.deposits)
                        FROM users_daily_stats uds
                        WHERE uds.user_id =  tl.user_id
                        AND uds.date > (NOW() - INTERVAL 1 YEAR)
                    ), 0) / IFNULL(c.multiplier, 1)
                ) / 100
            ELSE 1
        END
        GROUP BY user_id
        ";
    }

    public static function getSelectedLanguages()
    {
        $languages = DB::table('languages')
            ->where('selectable', '=', '1')
            ->get()
            ->pluck('language')
            ->map(function($el) {
                return "lang.$el";
            });

        return DB::table('localized_strings')
            ->where('language', '=', 'en')
            ->whereIn('alias', $languages)
            ->get()
            ->map(function($el) {
                return [
                    'language' => str_replace('lang.', '',$el->alias),
                    'title' => $el->value
                ];
            });
    }

    /**
     * Detect if currently logged in user can do action
     *
     * @param $tag
     * @return bool
     * @throws Exception
     */
    public static function allowedToDoAction($tag)
    {
        $user = self::getCurrentUser();
        $action_count = Action::query()
            ->where('actor', '=', $user->getAttribute('id'))
            ->where(function ($q) use ($tag) {
                return $q->where('tag', '=', $tag)
                    ->orWhere(function ($q) use ($tag) {
                        return $q->where('tag', '=', 'deleted_setting')
                            ->where('descr', 'like', "% removed $tag");
                    });
            })
            ->whereRaw("DATE(created_at) = CURDATE()")
            ->count();

        $daily_limit = (int)Config::getValue('daily-blocks-allowed', 'backoffice-limits', 5, true);

        if ($action_count >= $daily_limit) {
            EmailQueue::sendInternalNotification(
                "Reached limit: {$user->id}",
                "<div><p>User {$user->id} reached the limit of {$daily_limit} for action {$tag}. </p></div>",
                Config::getValue('action-limit-recipients', 'emails', '', false, true) ?? []
            );
            return false;
        }

        return true;
    }

    /**
     * Get first allowed status that can be set to user
     *
     * @param array $ignore_user_settings
     *
     * @return string
     */
    public function getAllowedUserStatus(array $ignore_user_settings = []): string
    {
        $user_id = $this->user->getKey();

        return (string) lic('getAllowedUserStatus', [$user_id, $ignore_user_settings], $user_id);
    }

    /**
     * @param string $user_status
     *
     * @return bool
     */
    public function isActiveStatus(string $user_status): bool
    {
        $user_id = $this->user->getKey();

        return (bool) lic('isActiveStatus', [$user_status], $user_id);
    }

    /**
     * @return bool
     */
    public function isEnabledStatusTracking(): bool
    {
        return (bool) lic('isEnabledStatusTracking', [], $this->user->getKey());
    }

    /**
     * @param mix $entity
     * @param string $key
     * @return string|null
     */
    private function getValueByKey($entity, string $key): ?string
    {
        return is_object($entity) ? $entity->{$key} : $entity[$key] ?? null;
    }

    /**
     * @param int|null $user_id
     * @return string
     */
    public function getJurisdiction(?int $user_id = null): string
    {
        return cu($user_id ?? $this->user->getKey())->getJurisdiction();
    }


    /**
     * Gets player's intended gambling limit
     *
     * @return string
     */
    public function getIntendedGamblingLimit(): string
    {
        $limit = $this->user->getSetting('intended_gambling');
        if (!$limit) {
            return "";
        }
        //Store from and to range in a variable and fill empty variable with null
        list($from, $to) = array_pad(preg_split('/\D/', $limit, -1, PREG_SPLIT_NO_EMPTY), 2, null);
        $limitRange = ($from == $to) ? "{$from}+" : $limit;
        return "{$limitRange} {$this->user->currency}";
    }

    public function getAccountClosureReason(): string
    {
        $reason = $this->user->getSetting('closed_account_reason');

        switch($reason) {
            case "fraud_or_ml":
                return "Fraud/ML related";
            case "rg_concerns":
                return "RG concerns";
            case "general_closure":
                return "General closure request (no compliance or risk concerns)";
            case "duplicate_account":
                return "Duplicate account";
            case "banned_account":
                return "Banned account";
            default:
                return 'Reason invalid / not found';
        }
    }

    private function getNetworkAbbreviation(string $product, ?string $network): string
    {
        if ($product === Networks::BINGO['product']) {
            return 'bingo';
        }

        $abbreviations = [
            'poolx' => 'pbo',
            'bingo' => 'bingo'
        ];

        return $abbreviations[$network] ?? '';
    }

    private function getNormalizeCardNumber(string $card_hash): string
    {
        $card_number = preg_replace('/\D/', '', $card_hash);
        return substr($card_number, 0, 6) . substr($card_number, -4);
    }
}
