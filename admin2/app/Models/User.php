<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.16.
 * Time: 16:36
 */

namespace App\Models;

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\FModel;
use App\Repositories\ActionRepository;
use App\Repositories\BlockRepository;
use App\Repositories\UserRepository;
use App\Repositories\UserSettingsRepository;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

/**
 * @property int $id
 * @property string $currency
 */
class User extends FModel
{
    public $obfuscated = '*******';

    protected $guarded = ['id'];

    /** @var  UserRepository $repo */
    public $repo;

    /** @var  BlockRepository $block_repo */
    public $block_repo;

    /** @var  UserSettingsRepository $block_repo */
    public $settings_repo;

    /**
     * disable/enable created_at, updated_at fields
     */
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key for this table. Do not remove or edit as it is used to get the shard_key
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Detect if user was not previously unsubscribed
     * @return bool
     */
    public function isSubscribed(): bool
    {
        return empty($this->repo->getSetting('unsubscribed_email'));
    }

    /**
     * Find user by email address
     * @param $email
     * @return User|Model|Builder|null
     */
    public static function findByEmail($email)
    {
        return self::where('email', $email)->first();
    }

    /**
     * Mark user as unsubscribed from emails
     * @param $email
     * @return string|null
     */
    public static function unsubscribe($email): ?string
    {
        $user = self::findByEmail($email);
        if (!$user) {
            return "Tried to unsubscribe {$email} but user not found.";
        }

        try {
            $user->repo->setSetting('unsubscribed_email', 1, true, null, 'hard-bounce');
        } catch (Exception $e) {
            return "Tried to unsubscribe {$user->id}: {$e->getMessage()}";
        }

        return null;
    }

    public function getShardKeyName()
    {
        return $this->primaryKey;
    }

    public function __construct(array $attributes = [])
    {
        $this->repo = new UserRepository($this);
        $this->settings_repo = new UserSettingsRepository($this);
        $this->block_repo = new BlockRepository($this);
        parent::__construct($attributes);
    }

    /**
     * Save user info with option to do it in both master and shards
     *
     * @param array $options
     * @param false $do_master
     * @return bool
     * @throws Exception
     */
    public function save(array $options = [], $do_master = false)
    {
        $original_attributes = $this->getOriginal();
        $dirty = $this->getDirty();

        try {
            if ($do_master) {
                DB::getMasterConnection()
                    ->table($this->table)
                    ->where('id', '=', $this->id)
                    ->update($dirty, true);
            }
        } catch (Exception $e) {
            error_log("Admin2 user update error: " . $e->getMessage());
        }

        $parent_res = parent::save($options);

        if ($parent_res) {
            $actor = UserRepository::getCurrentUser();
            foreach ($dirty as $key => $val) {
                ActionRepository::logAction($this, "{$actor->username} set $key to $val", $key, false, $actor);
            }

            // If model attributes were changed save them in users_changes_stats table
            if(!empty($dirty)) {
                lic('onUserCreatedOrUpdated', [$this->id, $dirty, $original_attributes], $this->id);
            }
        }
        return $parent_res;
    }

    public function populateSettings()
    {
        $this->settings_repo->populateSettings();
        $this->block_repo->populateSettings();

        return $this;
    }

    /*TODO test if this one will still work with sharded user table
    public static function find($id)
    {
        return self::find($id)
        return DB::shTable($id, (new static())->getTable())->where('id', $id)->first();
    }*/

    public static function findByUsername($username)
    {
        if (is_numeric($username)) {
            $user = self::where('id', $username)->first();
        }

        return $user ?? self::where('username', $username)->first();
    }

    /**
     * @return $this|bool
     * @throws Exception
     */
    public function setupBonusAndRewards() {
        $stats = DB::table('users_lifetime_stats')
            ->where('users_lifetime_stats.user_id', '=', $this->id)
            ->selectRaw('users_lifetime_stats.bets, users_lifetime_stats.rewards')
            ->first();

        if (!$stats) {
            return false;
        }

        foreach ((array)$stats as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function getBonusBalance()
    {
        return $this->bonusEntries()->where('status', 'active')->sum(DB::raw('bonus_entries.balance'));
    }

    public function getLang()
    {
        return empty($this->preferred_lang) ? 'en' : $this->preferred_lang;
    }

    public function getFullName()
    {
        return ucfirst($this->firstname) . ' ' . ucfirst($this->lastname);
    }

    public function hasNid()
    {
        return !empty($this->nid) || $this->repo->hasSetting('nid');
    }

    public function getNid($status = '')
    {
        if (!empty($status)) {
            $verified_nid = $this->repo->getSetting('verified-nid');
            $status = empty($verified_nid) ? '(Not verified)' : '(Verified)';
        }

        $nid = !empty($this->nid) ? $this->nid : $this->repo->getSetting('nid');
        return $nid . ' ' . $status;
    }

    /**
     * @return bool
     */
    public function hasCompletedRegistration(): bool
    {
        return $this->last_login != phive()->getZeroDate() || $this->repo->hasSetting('registration_end_date');
    }

    /**
     * todo port legacy code
     * @param $username
     * @return bool
     */
    public static function unarchive($username)
    {
        if (empty($username)) {
            return false;
        }
        return phive('UserHandler')->unarchiveUser($username);
    }

    public function addToRecentList($username = null)
    {
        $username = empty($username) ? $this->username : $username;
        if (isset($_SESSION['recent-users'])) {
            $recent_list = json_decode($_SESSION['recent-users'], true);
            if (count($recent_list) > 9) {
                array_shift($recent_list);
            }
            if (!in_array($username, $recent_list[$this->id])) {
                $recent_list[$this->id] = $username;
                $_SESSION['recent-users'] = json_encode($recent_list);
            }
        } else {
			$recent_list[$this->id] = $username;
            $_SESSION['recent-users'] = json_encode($recent_list);
        }
        return $this;
    }

    public static function bulkInsert(array $data, $key = 'id', $connection = null)
    {
        return parent::bulkInsert($data, 'id', $connection);
    }

    public function dailyStats()
    {
        return $this->hasMany('App\Models\UserDailyStatistics', 'user_id', 'id');
    }

    public function dailyStatsSports()
    {
        return $this->hasMany('App\Models\UserDailyStatisticsSports', 'user_id', 'id');
    }

    public function queuedTransactions()
    {
        return $this->hasMany('App\Models\QueuedTransaction', 'user_id', 'id');
    }

    public function raceEntries()
    {
        return $this->hasMany('App\Models\RaceEntry', 'user_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\UserComment', 'user_id', 'id');
    }

    public function settings()
    {
        return $this->hasMany('App\Models\UserSetting', 'user_id', 'id');
    }

    public function actions()
    {
        return $this->hasMany('App\Models\Action', 'actor', 'id');
    }

    public function actionsOnMe()
    {
        return $this->hasMany('App\Models\Action', 'target', 'id');
    }

    public function permissions()
    {
        return $this->hasMany('App\Models\PermissionUser', 'user_id', 'id');
    }

    public function deposits()
    {
        return $this->hasMany('App\Models\Deposit', 'user_id', 'id');
    }

    public function withdrawals()
    {
        return $this->hasMany('App\Models\Withdrawal', 'user_id', 'id');
    }

    public function cashTransactions()
    {
        return $this->hasMany('App\Models\CashTransaction', 'user_id', 'id');
    }

    public function ipLog()
    {
        return $this->hasMany(IpLog::class, 'target', 'id');
    }

    public function tournamentEntries()
    {
        return $this->hasMany('App\Models\TournamentEntry', 'user_id', 'id');
    }

    public function userSessions()
    {
        return $this->hasMany('App\Models\UserSession', 'user_id', 'id');
    }

    public function gameSessions()
    {
        return $this->hasMany('App\Models\UserGameSession', 'user_id', 'id');
    }

    public function bets()
    {
        return $this->hasMany('App\Models\Bet', 'user_id', 'id');
    }

    public function betsMp()
    {
        return $this->hasMany(BetMp::class, 'user_id', 'id');
    }

    public function wins()
    {
        return $this->hasMany('App\Models\Win', 'user_id', 'id');
    }

    public function winsMp()
    {
        return $this->hasMany(WinMp::class, 'user_id', 'id');
    }

    public function dailyGameStats()
    {
        return $this->hasMany('App\Models\UserDailyGameStatistics', 'user_id', 'id');
    }

    public function bonusEntries()
    {
        return $this->hasMany('App\Models\BonusEntry', 'user_id', 'id');
    }

    public function affiliate()
    {
        return $this->hasOne(User::class, 'id', 'affe_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(UserComplaint::class, 'user_id', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_members', 'user_id', 'group_id');
    }

    public function wheelLogs()
    {
        return $this->hasMany('App\Models\JackpotWheelLog', 'wheel_id', 'id');
    }

    public function dailyBoosterStats()
    {
        return $this->hasMany(UserDailyBoosterStat::class, 'user_id', 'id');
    }

    public function getSetting($setting)
    {
        return $this->repo->getSetting($setting);
    }

    /*
     * Replace sensitive information with asterisk symbol
     */
    public function obfuscate() {

        $skipped_keys = [
            'id', 'register_date', 'cash_balance', 'currency'
        ];

        foreach (array_keys($this->toArray()) as $key) {
            if (!in_array($key, $skipped_keys)) {
                $this->{$key} = $this->obfuscated;
            }
        }
    }

    /**
     * Requires populateSettings before being used.
     *
     * @return mixed
     */
    public function isForgotten() {
        return !empty($this->settings_repo->settings->forgotten);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rgLimits() {
        return $this->hasMany('App\Models\RgLimits', 'user_id','id');
    }

    /**
     * @param $mobile
     */
    public function setMobile($mobile) {
        $this->mobile = $mobile;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function beBettor()
    {
        return $this->hasMany('App\Models\BeBettor', 'user_id', 'id');
    }

    public function firstDeposit()
    {
        return $this->hasMany('App\Models\FirstDeposit', 'user_id', 'id');
    }

    public function getPrivacySettings()
    {
        return phive('DBUserHandler/PrivacyHandler')->getPrivacySettings($this->id);
    }
}
