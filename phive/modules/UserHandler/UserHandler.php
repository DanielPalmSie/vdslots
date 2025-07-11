<?php

use Carbon\Carbon;
use Laraphive\Contracts\IpBlock\IpBlockInterface;

require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Group.php';

// TODO henrik remove
define("USER_STATUS_NEW", 		1);
define("USER_STATUS_INACTIVE",	2);
define("USER_STATUS_BANNED",	4);
define('GROUPS', 				4);
define('GROUPS_MEMBERS',	 	5);

/**
 * A class with a bunch of methods pertaining to handling users and user information.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_settings The wiki page for the users settings table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users The wiki page for the users table.
*/
class UserHandler extends PhModule {
    const PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG = 'payout all funds - failed attempt';


    public const SYSTEM_USER = 'system';
    public const SYSTEM_AML52_PAYOUT_USER = 'system_aml52_payout';

    /**
     * @var DBUser An instance of the currently logged in user.
     */
    public $currentUser;

    /**
     * @var bool In some situations it is useful to see a page as a logged out person would but still be able to edit it.
     */
    public $simulatedLoggedOut;

    /**
     * @var string Cached login error descriptor.
     */
    public $login_error;

    public const SELF_EXCLUSION_POSITIVE = 'Y';

    /**
     * by default false, if set to true it mean current users_session will be marked with otp = 1
     *
     * @var bool
     */
    private $otp_validated_session = false;

    private array $aml52ExclusionsConfig = [];

    // TODO henrik remove
    public function getTableName($table_id)
    {
        switch($table_id)
            {
            case GROUPS:
                return $this->getSetting('db_groups');
            case GROUPS_MEMBERS:
                return $this->getSetting('db_groups_members');
            default:
                return 'users';
            }
    }

    // TODO henrik remove
    public function getTableStructure($table_id){
        $table_str = $this->getTableName($table_id);
        return phive('SQL')->loadArray("SHOW COLUMNS FROM " . $table_str);
      //$sql = phive('SQL');
      //$sql->query(
      //  "SHOW COLUMNS FROM " . $table_str);
      //$structure = $sql->fetchArray();
        //return $structure;
    }

    /**
     * Factory method for User.
     *
     * @see User
     * @param int|array $ud User identifier information, either the user id or a users array / row.
     *
     * @return User The user object.
     */
    public function newUser($ud){
        return new User($ud);
    }

    /**
     * Factory method for Group.
     *
     * @param mixed $pParent User handler object.
     * @param int|array $groupIdOrArray The group id or array (db row).
     * @param $name The name of the group, required if creating a new group.
     *
     * @return Group The Group objefct.
     */
    public function newGroup($pParent, $groupIdOrArray, $name=null){
        return new Group($pParent, $groupIdOrArray, $name);
    }

    /**
     * Counts users based on passed in filters. Example usage is to check for duplicate accounts.
     *
     * @param string $col The base column to work with, for example email.
     * @param string $val The value to look for.
     * @param string $tbl The table, typically users.
     * @param string $where_extra Extra WHERE statements for additional filtering.
     *
     * @return int The count.
     */
    function countUsersWhere($col, $val, $tbl = 'users', $where_extra = ''){
        $str = "SELECT COUNT(*) FROM $tbl WHERE $col = '$val' $where_extra";

        if ($col == 'reg_ip'){ //for this col we don't need to search in a shards
            $res = phive('SQL')->getValue($str);
        } else {
            $res = phive('SQL')->shs('merge', '', null, 'users')->getValue($str);
        }

        $result = is_array($res) ? array_sum($res) : $res;
        if (!empty($result)) {
            return $result;
        }

        if (phive('SQL')->disabledNodeIsActive()) {
            $res = phive('SQL')->onlyMaster()->getValue($str);
        }

        /*
        if ($col == 'username' && $this->getSetting('scale_back') === true) {
            $res_archive = (int)phive('SQL')->doDb('archive')->getValue($str);
            if (!empty($res_archive)) {
                return is_array($res) ? array_sum($res) + $res_archive : (int)$res + $res_archive;
            }
        }
        */

      return is_array($res) ? array_sum($res) : $res;
    }

    /**
     * Wrapper around newUser().
     *
     * A simple wrapper that checks if we already have a logged in user, if we have we return it, otherwise
     * we fetch the user from the database.
     *
     * @param int $userId The id of the user to fetch.
     * @param bool $from_master return the user data from master
     *
     * @return User The User object.
     * @see DBUser
     * @see User
     */
    public function getUser($userId = 0, $from_master = false) {
        if($userId === 0 && $this->simulatedLoggedOut)
            return null;
        else if (is_object($this->currentUser) && ($userId === 0 || $this->currentUser->getId() == $userId))
            return $this->currentUser;
        else if ($userId === 0)
            return null;
        else if(is_array($userId))
            return $this->newUser($userId);
        else{
            $user = $this->newByAttr('id', $userId, $from_master);
            if ($user->getId())
                return $user;
            else
                return null;
        }
    }

    private function cardIsExpired(string $cardId, array $cards, array $insert, string $uid): bool
    {
        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = phive('Logger')
            ->getLogger('payments');

        $expired = (
            !array_key_exists($cardId, $cards) ||
            (
                $cards[$cardId]['exp_year'] < date('Y') || (
                    $cards[$cardId]['exp_year'] ==  date('Y') &&
                    $cards[$cardId]['exp_month'] <= date('n')
                )
            )
        );

        if ($expired) {
            $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                'pending' => $cards,
                'reason' => 'expired card',
                'uid' => $uid,
            ]);
        }

        return $expired;
    }

    private function isCard(array $transaction): bool
    {
        if ($transaction['scheme'] === Supplier::SWISH) {
            return false;
        }

        return isset($transaction['ref_code'])
            ? (!empty($transaction['scheme']) && !empty($transaction['ref_code']))
            : !empty($transaction['card_hash']);
    }

    public function suppliersAvailableForAutoPayout(): array
    {
        return [
            'credorax',
            'worldpay',
            'ecopayz',
            'neteller',
            'paypal',
            'trustly',
            'swish'
        ];
    }

    private function excludeCountryProvinceForAML52AutoPayout(DBUser $user): bool
    {
        $this->getAml52ExclusionsConfig();

        if (empty($this->aml52ExclusionsConfig)) {
            return false;
        }

        $exclusionsData = phive('Config')->getValueFromTemplate($this->aml52ExclusionsConfig);
        $exclusions = phive('Cashier')->filterNonZeroNonEmptyArray($exclusionsData);
        if (empty($exclusions)) {
            return false;
        }

        $userCountry = $user->getCountry();
        $userProvince = $user->getMainProvince();

        foreach ($exclusions as $country => $provinces) {
            if ($userCountry === strtoupper($country)) {
                $provincesArray = empty($provinces) ? [] : array_map(fn($province) => strtoupper(trim($province)), explode(',', $provinces));
                if (empty($provincesArray) || in_array($userProvince, $provincesArray)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getAml52ExclusionsConfig(): void
    {
        if (empty($this->aml52ExclusionsConfig)) {
            $this->aml52ExclusionsConfig = phive('Config')->getByNameAndTag('aml52-auto-payout-exclude-country-province', 'AML');
        }
    }

    public function aml52Payout(int $user_id, array $suppliers, bool $force = false): bool
    {
        $user = cu($user_id);

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = phive('Logger')->getLogger('payments');

        if ($this->excludeCountryProvinceForAML52AutoPayout($user)) {
            $logger->error("Auto payout is not enabled for this country or province.", [
                'user_id' => $user->getId(),
                'country' => $user->getCountry(),
                'province' => $user->getMainProvince()
            ]);
            return false;
        }

        $result = $this->payoutAll($user_id, $suppliers, $force);

        if ($result) {
            return true;
        }

        /** @var Config $config */
        $config = phive('Config');
        if ($config->featureFlag('aml52-payout-details-request-email') &&
            $user->hasSettingExpired('aml52-payout-details-requested'))
        {
            /** @var MailHandler2 $mailHandler */
            $mailHandler = phive('MailHandler2');
            $requestSent = $mailHandler->payoutDetailsRequest($user);

            if ($requestSent) {
                $user->refreshSetting('aml52-payout-details-requested', 1);
            } else {
                $logger->error('Missing country template for AML52 payout email!', [
                    'user_id' => $user->getId(),
                    'country' => $user->getCountry(),
                ]);
            }
        }

        return false;
    }

    public function payoutAll(int $user_id, array $suppliers, bool $force = false): bool
    {
        $user = cu($user_id);

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = phive('Logger')->getLogger('payments');

        /** @var CasinoCashier $casinoCashier */
        $casinoCashier = phive('CasinoCashier');

        $filtered = [];
        $pendingWithdrawals = $casinoCashier->getPendingsUser($user_id);
        $cards = Mts::getInstance('', $user_id)->getCards();

        foreach ($pendingWithdrawals as $pending) {
            $paymentMethod = $pending['payment_method'];
            if (Supplier::Trustly === $paymentMethod) {
                $uid = Supplier::Trustly;
            } else {
                $uid = $paymentMethod . '-' . ($pending['scheme'] ?: $pending['iban'] ?: $pending['net_account'] ?: 'general');
            }

            if (
                !in_array($paymentMethod, $suppliers) ||
                $this->isPaymentMethodDisabled($paymentMethod, $user->getCountry())
            ) {
                $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                    'pending' => $pending,
                    'reason' => 'filtered (or disabled) method',
                    'uid' => $uid,
                ]);

                continue;
            }

            if (!$force && $pending['status'] === 'disapproved') {
                $filtered[] = $uid;
                $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                    'pending' => $pending,
                    'reason' => 'disapproved (failed)',
                    'uid' => $uid,
                ]);

                continue;
            }

            if (in_array($uid, $filtered)) {
                $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                    'pending' => $pending,
                    'reason' => 'subsequent failure',
                    'uid' => $uid,
                ]);
                continue;
            }

            if (Supplier::Trustly === $paymentMethod) {
                if ($this->trustlyPayoutAll($user)) {
                    return true;
                }

                $filtered[] = Supplier::Trustly;

                continue;
            }

            if (
                $this->isCard($pending) &&
                $this->cardIsExpired($pending['ref_code'], $cards, $pending, $uid)
            ) {
                continue;
            }

            if ($paymentMethod === Supplier::PAYPAL) {
                $pending['mb_email'] = $user->getSetting('paypal_email') ?? '';
                $pending['net_account'] = $user->getSetting('paypal_payer_id') ?? '';
            }

            if ($paymentMethod === Supplier::EcoPayz) {
                $accounts = Mts::getInstance('', $user)->getAccounts(Supplier::EcoPayz);
                $account = end($accounts);
                $pending['net_account'] = $account['value'];
            }

            $pending['amount'] = $user->getBalance();
            $pending['aut_code'] = $user->getBalance();
            $pending['deducted_amount'] = 0;
            $pending['created_by'] = uid(self::SYSTEM_AML52_PAYOUT_USER); //REVIEW: it is ok to use this user also for no-aml52 payouts?
            unset(
                $pending['id'],
                $pending['status'],
                $pending['real_cost'],
                $pending['timestamp'],
                $pending['description'],
                $pending['approved_by'],
                $pending['approved_at'],
                $pending['flushed'],
                $pending['stuck'],
                $pending['mts_id'],
                $pending['ext_id']
            );

            if ($this->insertPendingForUser($pending, $user, $uid)) {
                return true;
            }
        }

        $deposits = $casinoCashier->getDeposits(
            '',
            '',
            $user_id,
            '',
            false,
            '',
            "AND status = 'approved'",
            '',
            '',
            false,
            "timestamp DESC"
        );

        foreach ($deposits as $deposit) {
            $paymentMethod = $deposit['scheme'] === Supplier::SWISH
                ? Supplier::SWISH
                : $deposit['dep_type'];

            if (Supplier::Trustly === $paymentMethod) {
                $uid = Supplier::Trustly;
            } else {
                $uid = $paymentMethod . '-' . ($deposit['card_hash'] ?: 'general');
            }

            if (in_array($uid, $filtered)) {
                $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                    'pending' => $deposit,
                    'reason' => 'subsequent failure',
                    'uid' => $uid,
                ]);

                continue;
            }

            if (Supplier::Trustly === $paymentMethod) {
                if ($this->trustlyPayoutAll($user)) {
                    return true;
                }

                $filtered[] = Supplier::Trustly;

                continue;
            }

            if (
                !in_array($paymentMethod, $suppliers) ||
                $this->isPaymentMethodDisabled($paymentMethod, $user->getCountry()) ||
                (
                    $this->isCard($deposit) &&
                    !in_array($paymentMethod, [
                        Supplier::CREDORAX,
                        Supplier::Worldpay,
                    ])
                )
            ) {
                $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                    'pending' => $deposit,
                    'reason' => 'filtered (or disabled) method',
                    'uid' => $uid,
                ]);

                continue;
            }

            $insertPending = [];

            if ($this->isCard($deposit)) {
                $key = array_search($deposit['card_hash'], array_column($cards, 'card_num'));
                if ($key === false) {
                    $logger->info(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                        'pending' => $cards,
                        'reason' => 'card number not found in users cards',
                        'uid' => $uid,
                    ]);

                    continue;
                }

                $cardId = array_keys($cards)[$key] ?? '';
                if ($this->cardIsExpired($cardId, $cards, $deposits, $uid)) {
                    $filtered[] = $uid;

                    continue;
                }

                $insertPending['ref_code'] = $cardId;
            }

            $insertPending['user_id'] = $user_id;
            $insertPending['payment_method'] = $paymentMethod;
            $insertPending['amount'] = $user->getBalance();
            $insertPending['aut_code'] = $user->getBalance();
            $insertPending['deducted_amount'] = 0;
            $insertPending['created_by'] = uid(self::SYSTEM_AML52_PAYOUT_USER);
            $insertPending['currency'] = $deposit['currency'];
            $insertPending['wallet'] = $deposit['scheme'];
            $insertPending['scheme'] = $deposit['card_hash'];

            switch ($paymentMethod) {
                case Supplier::PAYPAL:
                    $insertPending['mb_email'] = $user->getSetting('paypal_email') ?? '';
                    $insertPending['net_account'] = $user->getSetting('paypal_payer_id') ?? '';
                    break;

                case Supplier::SWISH:
                    $insertPending['net_account'] = $deposit['card_hash'];
                    unset($insertPending['scheme'], $insertPending['wallet']);
                    break;

                // No default needed since no additional action is required for other payment methods.
            }

            if (!$this->insertPendingForUser($insertPending, $user, $uid)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function trustlyPayoutAll(DBUser $user): bool
    {
        $pending = [];
        $pending['payment_method'] = Supplier::Trustly;
        $pending['amount'] = $user->getBalance();
        $pending['aut_code'] = $user->getBalance();
        $pending['currency'] = $user->getCurrency();
        $pending['deducted_amount'] = 0;
        $pending['created_by'] = uid(self::SYSTEM_AML52_PAYOUT_USER);

        return $this->insertPendingForUser($pending, $user, Supplier::Trustly);
    }

    private function insertPendingForUser(array $pending, DBUser $user, string $uid): bool
    {
        /** @var CasinoCashier $casinoCashier */
        $casinoCashier = phive('CasinoCashier');

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = phive('Logger')
            ->getLogger('payments');

        $pid = $casinoCashier->insertPendingCommon($user, $user->getBalance(), $pending);

        if (empty($pid)) {
            $logger->warning(self::PAYOUT_ALL_FUNDS_FAILED_ATTEMPT_LOG, [
                'pending' => $pending,
                'reason' => 'cannot insert pending_withdrawal',
                'uid' => $uid,
            ]);

            return false;
        }

        return true;
    }
    /**
     * Wrapper around a Config call to check if the passed in user is from the EU.
     *
     * // TODO henrik remove the pass by reference, objects are passed by reference by default.
     *
     * @param User &$user The user object.
     *
     * @return bool True if the user is in the EU, false otherwise.
     */
    function userIsEu(&$user){
        $eu_countries = phive('Config')->getValue('countries', 'eu');
        return in_array($user->getCountry(), explode(' ', $eu_countries));
    }

    /**
     * Gets a users row from the database.
     *
     * @param int $uid The id of the user to get.
     *
     * @return array The users row.
     */
    function getRawUser($uid){
        $uid = intval($uid);
        return phive("SQL")->sh($uid, '', 'users')->loadAssoc("SELECT * FROM users WHERE id = $uid");
    }

    /**
     * Gets settings based on a passed in filter that is not user specific.
     *
     * @param string $where The WHERE statement to filter on.
     * @param string $key Optional column to use as key for the result array, if omitted a numeric array will be returned.
     *
     * @return array The result array.
     */
    function rawSettingsWhere($where, $key = false){
        $str = "SELECT * FROM users_settings WHERE $where";
        return phive('SQL')->shs('merge', '', null, 'users_settings')->loadArray($str, 'ASSOC', $key);
    }

    /**
     * Deletes all of a user's settings based on a filter.
     *
     * @param string $where The WHERE clause to filter on.
     * @param mixed $uid The user identifying element.
     *
     * @return bool True if the settings where deleted, false otherwise.
     */
    function deleteSettingsWhere($where, $uid){
        $u_obj = cu($uid);
        if(empty($u_obj)){
            return false;
        }
        $settings = phive('SQL')->sh($uid)->loadArray("SELECT * FROM users_settings WHERE $where AND user_id = $uid");
        $u_obj->deleteSettings(array_column($settings, 'setting'));
        return true;
    }

    /**
     * Gets a settings row based on user id and setting identifier / name.
     *
     * @param int $user_id The user id.
     * @param string $setting The setting.
     *
     * @return array The result array / setting row.
     */
    function getRawSetting($user_id, $setting){
        $user_id = intval($user_id);
        return phive("SQL")->sh($user_id, '', 'users_settings')->loadAssoc("SELECT * FROM users_settings WHERE user_id = $user_id AND setting = '$setting'");
    }

    //TODO henrik remove
    function getSettingSelect(){
        $str = "SELECT DISTINCT * FROM users_settings WHERE setting NOT RLIKE '[0-9]' AND setting NOT LIKE 'login-allowed-%' GROUP BY setting";
        $res = phQget($str);
        if(empty($res)){
            $res = phive('SQL')->loadKeyValues($str, 'setting', 'setting');
            phQset($str, $res);
        }
        return $res;
    }

    /**
     * Caches the current user by an arbitrary column and column value as the selector.
     *
     * @uses UserHandler::newUser()
     * @see UserHandler::newUser()
     * @param string $aname The column name to use.
     * @param string $avalue The column value to look for.
     * @param bool $from_master return the user data from master
     * @return User The User object.
     */
    function newByAttr($aname, $avalue, $from_master = false){
        $val = phive('SQL')->escape($avalue, false);
        $user = phive('SQL')->loadAssoc("", "users", "$aname = '$val'");
        if ($from_master) {
            return $this->newUser($user);
        }
        $user = phive('SQL')->sh($user, 'id')->loadAssoc("", "users", "id = {$user['id']}");
        return $this->newUser($user);
    }

    /**
     * Similar to getUser() but instead of requiring a user id this method can work with any column.
     *
     * @param string $attr_name The column / attribute name.
     * @param string $attr_value The column / attribute value.
     * @param bool $from_master return the user data from master
     *
     * @return User The User object.
     */
    function getUserByAttr($attr_name, $attr_value, $from_master = false){
        if(empty($attr_value))
            return null;
        if(is_object($this->currentUser) && $this->currentUser->data[$attr_name] == $attr_value)
            return $this->currentUser;
        else{
            $user = $this->newByAttr($attr_name, $attr_value, $from_master);
            if (!empty($user->data['id']))
                return $user;
            else
                return null;
        }
    }

    /**
     * A wrapper around getUserByAttr() focused on the username and email.
     *
     * @uses UserHandler::getUserByAttr()
     * @see UserHandler::getUserByAttr()
     *
     * @param string $username The username.
     * @param bool $from_master return the user data from master
     *
     * @return User The resultant user object.
     */
    public function getUserByUsername($username, $from_master = false) {
        $username = str_replace([' ', '(', ')', '=', "'"], '', $username);
        $user = null;

        if (strpos($username, '@') !== false) {
            $user = $this->getUserByAttr('email', $username, $from_master);
        }

        return $user ?? $this->getUserByAttr('username', $username, $from_master);
    }

    // TODO henrik remove this
    public function getGroup($groupId){
        $group = $this->newGroup($this, $groupId);
        if ($group->getId())
            return $group;
        else
            return null;
    }

    // TODO henrik remove this
    public function getGroupByName($groupName)
    {
        $group = $this->newGroup($this, null, $groupName);
        if ($group->getId())
            return $group;
        else
            return null;
    }

    /**
     * Returns the age with the help of the date of birth.
     *
     * @param string $dob The date of birth to use.
     *
     * @return int The age.
     */
    function ageFromDoB($dob) {
        return floor(phive()->subtractTimes(phive()->today(), $dob, 'y'));
    }

    public function calculateAgeFromDate(string $dateOfBirth): int
    {
        return Carbon::parse($dateOfBirth)->age;
    }

    /**
     * Gets the value of a column in the users table, directly so no caching is in play.
     *
     * @param int $uid The user id.
     * @param string $attr The column / attribute.
     *
     * @return string The value.
     */
    function getFreshAttr($uid, $attr){
        $uid = intval($uid);
        return phive('SQL')->sh($uid, '', 'users')->getValue("SELECT `$attr` FROM users WHERE id = $uid");
    }

    /*
       function getAttr($attr, $where = '', $tbl = 'users'){
       $where = empty($where) ? "id = ".$this->getUser()->getId() : $where;
       return phive('SQL')->getValue("SELECT $attr FROM $tbl WHERE $where");
       }
    */

    // TODO henrik remove
    public function deleteGroup($groupId){
        pOrDie('edit.groups');
        $sql = phive("SQL");
        $table = $this->getSetting('db_groups');
        return $sql->query("DELETE FROM `$table` WHERE `group_id`=" . (int)$groupId);
    }

    // TODO henrik remove this, refactor all invocations to use SQL directly.
    function getColumns($tbl){
        return phive('SQL')->getColumns($tbl);
    }

    // TODO henrik remove
    public function getAllGroups($where = null, $extra = null) {
        $select	= "*";
        $from	= $this->getSetting("db_groups");

        $q = phive("SQL")->makeQuery($select, $from, $where, $extra);
        phive("SQL")->query($q);

        $Groups = array();
        while ($r = phive("SQL")->fetch('ASSOC')) {
            $Groups[] = new Group($this, $r);
        }

        $array = phive('SQL')->fetchArray();
        return $Groups;
    }

    // TODO henrik remove
    function simpleValidation($username, $password){
        $user 	= $this->getUserByUsername($username);
        $r 		= $this->checkPassword($user, $password);
        return $r ? $user : false;
    }

    /**
     * Crate unique token that contains UID for login with external service (Ex. BankID)
     *
     * @param mixed $u User identifying information.
     * @return string Universally unique identifier.
     */
    public function createLoginToken($u){
        $uuid = phive()->uuid();
        phMset($uuid, uid($u), 60);
        return $uuid;
    }

    /**
     * Login with external token (Ex. callback from BankID webhook)
     *
     * @param string $token The UUID / token.
     * @return bool|User|null The result, User object if successful.
     */
    public function loginWithToken($token)
    {
        $uid = phMget($token);
        phMdel($token);
        if (empty($uid)) {
            return false;
        }
        $u = cu($uid);
        if (empty($u)) {
            return false;
        }
        $this->markSessionAsOtpValidated();
        return $this->login($u->getId(), '', true, false);
    }

    /**
     * Mark the current user_sessions as OTP validated.
     */
    public function markSessionAsOtpValidated()
    {
        $this->otp_validated_session = true;
    }

    /**
     * get OTP value for current session.
     * @return bool True if validated, false otherwise.
     */
    public function getOtpValidated()
    {
       return $this->otp_validated_session;
    }

    /**
     * Login logic and housekeeping related to the login.
     *
     * @param string $username The username to try to login with.
     * @param string $password The password to try to login with.
     * @param bool $needpasswd If false we don't check the password.
     *
     * @return User|false The user object if login was successful, false otherwise.
     */
    public function login($username = null, $password = null, $needpasswd = true){
        // The user might came here from the mobile Battle of Slots, which is runnung on the Vue.js website.
        // Persist the session var show_go_back_to_bos into the next session
        $show_go_back_to_bos = false;
        if(isset($_SESSION['show_go_back_to_bos'])) {
            $show_go_back_to_bos = true;
            $newsite_go_back_url = $_SESSION['newsite_go_back_url'];
        }

        phive()->sessionStart();

        if($show_go_back_to_bos) {
            /** @var URL $p_url */
            $p_url = phive('Http/URL');
            $_SESSION['show_go_back_to_bos'] = true;
            $_SESSION['newsite_go_back_url'] = $p_url->prependMobileDirPart($newsite_go_back_url);
        }

        if ($username === null || $password === null) {
            if ($_SESSION['user_id'] 	=== null || $_SESSION['username'] === null || $_SESSION['password'] === null) {
                $this->setLoginError();
                return false;
            }

            if ($_SESSION['user_id'] !== null && $_SESSION['username'] !== null && $_SESSION['password'] !== null){
                $this->currentUser = $this->getUserByUsername($_SESSION['username']);

                if($needpasswd)
                    $r = $this->checkPassword($this->currentUser, $_SESSION['password']);

                //if($this->currentUser->isBlocked())
                //    $r = false;

                if ($r === false) {
          $this->currentUser = null;
                    $this->setLoginError();
                    return false;
                } else {
          return $this->currentUser;
                }
            }
        } else if ($username !== null && $password !== null) {
            $this->currentUser = $this->getUserByUsername($username);
            if(empty($this->currentUser))
                $r = false;
            else if($needpasswd)
                $r = $this->checkPassword($this->currentUser, $password);
            else
                $r = true;

            if ($r === false) {
                $this->currentUser = null;
                $this->setLoginError('LOGIN_ERROR_FAILED');
                return false;
            } else if ($r !== false) {
                $_SESSION['user_id']	= $this->currentUser->getId();
                $_SESSION['username']	= $username;
                $_SESSION['password']	= $password;
                $this->loginSuccessful($username);
                return $this->currentUser;
            }
        }
    }

    /**
     * Wiping out the session data and nullifying currentUser will destroy the logged in session.
     *
     * @return bool True if we reached the return statement, ie if the logout worked.
     */
    public function logout() {
        $_SESSION = array();
        $this->currentUser = null;
        $_SESSION['home_page'] = true;
        return true;
    }

    /**
     * Logs a failed login in the failed_logins table.
     *
     * @param int $uid The id of the user that failed login (if one is contextually available).
     * @param string $uname The given username that failed to login.
     * @param string $reg_country The country the user that failed to login registered from.
     * @param string $login_country The ISO2 that the login attempt comes from.
     * @param int $active Whether ot not the user is active (not blocked) or not (blocked).
     * @param string $reason_tag A tag that identifies the reason for the failed login.
     *
     * @return int The id of the new failed login row.
     */
    function logFailedLogin($uid, $uname, $reg_country, $login_country, $active = 1, $reason_tag = 'failed_login_without_reason'){
        if(empty($uid))
            $uid = ud($uname)['id'];
        if(empty($login_country))
            $login_country = phiveApp(IpBlockInterface::class)->getCountry();
        $ip = remIp();
        return phive('SQL')->sh($uid, '', 'failed_logins')->insertArray('failed_logins', array(
            'user_id' => $uid,
            'username' => $uname,
            'reg_country' => $reg_country,
            'login_country' => $login_country,
            'active' => $active,
            'ip' => $ip,
            'reason_tag' => $reason_tag
        ));
    }

    // TODO henrik remove
    public function createGroup($groupName){
        pOrDie('edit.groups');
        $sql = phive('SQL');
        $table_str = $this->getSetting('db_groups');
        $r = $sql->query(
            "INSERT IGNORE " . $table_str . " SET `name`=" . $sql->escape($groupName));
        $id = $sql->insertBigId();
        if ($id)
            return $this->getGroup($id);
        else
            return false;
    }

    // TODO henrik remove
    public function generateSalt()
    {
        return substr(md5(uniqid(rand(), true)), 0, 4);
    }

    /**
     * Hashes a password.
     *
     * @param string $password The clear / raw password to hash.
     * @param string $salt A salt to use, if omitted we use a configured salt.
     *
     * @return string The hashed password.
     */
    public function encryptPassword($password, $salt=null)
    {
        if ($salt===null)
            $salt = $this->getSalt();
        return md5($salt . md5($password));
    }

    /**
     * Checks if the passed in password matches the one one file, used in the login process.
     *
     * @uses UserHandler::encryptPassword() in order to encrypt the passed in password.
     * @see UserHandler::encryptPassword()
     * @param User $user The user object whose password we want to check.
     * @param string $password The given password we want to check against the one we have on file.
     *
     * @return bool True if they match, false otherwise.
     */
    public function checkPassword($user, $password){
        if (!$user)
            return false;
        $enc_pass = $this->encryptPassword($password, $this->getSalt());
        return ($user->getPassword() === $enc_pass);
    }

    /**
     * If the user fail a login set into DB failed_logins with the number of attempts
     *
     * @param DBUser $user The user object whose password we want to check.
     * @param string|null $password
     * @param bool $is_password_required
     *
     * @return bool|array - array on action; 'true' when no action required
     */
    public function handleLoginPasswordCheck(DBUser $user, ?string $password, bool $is_password_required)
    {
        if ($this->checkPassword($user, $password) || !$is_password_required) {
            return true;
        }

        if ($user->hasExceededLoginAttempts()) {
            return false;
        }

        $failed_logins = $user->getSetting('failed_logins') ? $user->getSetting('failed_logins') + 1 : 1;
        $login_attempts_allowed = $this->getSetting('login_attempts');
        $user->setSetting('failed_logins', $failed_logins);

        // Log failed attempt for wrong password inserted
        $req_country = remIp() == '127.0.0.1' ? 'JP' : phiveApp(IpBlockInterface::class)->getCountry();
        $this->logFailedLogin($user->getId(), $user->getUsername(), $user->getCountry(), $req_country, (int)$user->getAttribute('active'), 'failed_login_wrong_password');
        $this->logAction($user, "Standard login: wrong password", 'failed_login_wrong_password');
        phive()->dumpTbl(
            'login-failed-password',
            ['user_id' => $user->getId(), 'attempt' => $login_attempts_allowed - $failed_logins],
            $user->getId()
        );

        return ['login_fail_attempts', ['attempts' => $login_attempts_allowed - $failed_logins]];
    }

    /**
     * The configured salt.
     *
     * TODO use something else instead that varies from user to user such as the DOB.
     *
     * @return string The salt.
     */
    public function getSalt()
    {
        return $this->getSetting('password_salt');
    }

    /**
     * Setter for the cached login error.
     *
     * @param string $code The login error tag / code.
     *
     * @return null
     */
    public function setLoginError($code=null)
    {
        $this->login_error = $code;
    }

    /**
     * Getter for the cached login error.
     *
     * @return string $code The login error tag / code.
     */
    public function getLoginError()
    {
        return $this->login_error;
    }

    /**
     * Housekeeping related to a successful login.
     *
     * @param $username TODO henrik remove.
     *
     * @return null
     */
    public function loginSuccessful($username=null){
        $this->currentUser->setAttribute('last_login', phive()->hisNow());
    }

    // TODO henrik remove.
    function setUsrAttr($uid, $attr, $val){
        return phive('SQL')->sh($uid, '', 'users')->updateArray('users', array($attr => $val), array('id' => $uid));
    }

    /**
     * Sets the simulate logged out flag, the logic is used in order to see pages as a logged out person even though the admin
     * is logged in, typically needed in CMS contexts.
     *
     * @param bool $value The boolean to set, true if we want to simulate, false if not.
     *
     * @return null
     */
    public function simulateLoggedOut($value = true){
        $this->simulatedLoggedOut = $value;
    }

    // TODO henrik remove
    function setUsrSetting($uid, $setting, $value){
        return phive("SQL")->sh($uid, '', 'users_settings')->save('users_settings', array('user_id' => $uid, 'setting' => $setting, 'value' => $value));
    }

    /**
     * Wrapper around getUserByAttr() for getting a user by email.
     *
     * @param string $email The email.
     *
     * @return User The user object.
     */
    public function getUserByEmail($email) {
        return $this->getUserByAttr('email', $email);
    }

    // TODO henrik remove
    function searchAll($p, $rake = true, $show_affiliate = false){

        $partials = array('username', 'email', 'firstname', 'lastname', 'bonus_code', 'mobile', 'alias');
        $exact = array('preferred_lang', 'country', 'id', 'currency');
        $dates = array('last_login', 'register_date');

        $_SESSION['asc_desc'] = '';
        $_SESSION['order_by'] = '';

        $where = '';
        foreach($partials as $partial){
            if(!empty($p[$partial]))
                $where .= " AND users.$partial LIKE '%{$p[$partial]}%' ";
        }

        foreach($exact as $e){
            if($p[$e] != '')
                $where .= " AND users.$e = '{$p[$e]}' ";
        }

        if(!empty($p['user_col']) && !empty($p['user_val']))
            $where .= " AND users.{$p['user_col']} ".urldecode($p['user_comp'])." '{$p['user_val']}' ";

        foreach($dates as $d){
            if(!empty($p[$d]))
                $where .= " AND DATE(users.$d) >= '{$p[$d]}' ";
        }

        if(!empty($p['register_before_date']))
            $where .= " AND DATE(users.register_date) < '{$p['register_before_date']}'  ";

        if(!empty($p['notin_country'][0]))
            $where .= " AND users.country NOT IN(".phive('SQL')->makeIn($p['notin_country']).") ";

        if(!empty($p['user_ids']))
            $where .= " AND users.id IN({$p['user_ids']}) ";

        $join 	= '';
        $from 	= '';
        $group 	= '';
        $having = '';

        if(!empty($p['in_amount'])){
            $from 	.= ", deposits.amount AS deposit_amount ";
            $join 	.= " LEFT JOIN deposits ON deposits.user_id = users.id AND deposits.amount >= {$p['in_amount']} ";
            $where 	.= " AND deposits.amount IS NOT NULL ";
            if(!empty($p['in_since']))
                $join .= " AND DATE(deposits.timestamp) >= '{$p['in_since']}' ";
            $group 	= " GROUP BY users.id ";
        }else if($p['in_amount'] == '0'){
            $from 	.= ", deposits.amount AS deposit_amount ";
            $join 	.= " LEFT JOIN deposits ON deposits.user_id = users.id ";
            $where 	.= " AND deposits.amount IS NULL ";
            $group 	= " GROUP BY users.id ";
        }

        if(!empty($p['out_amount'])){
            $from 	.= ", pending_withdrawals.amount AS withdrawal_amount ";
            $amount = intval($p['out_amount']);
            $join 	.= " LEFT JOIN pending_withdrawals ON pending_withdrawals.user_id = users.id AND pending_withdrawals.amount >= ".$amount;
            $where 	.= " AND pending_withdrawals.amount IS NOT NULL ";
            if(!empty($p['out_since']))
                $join .= " AND DATE(pending_withdrawals.timestamp) >= '{$p['out_since']}' ";
            $group 	= " GROUP BY users.id ";
        }

        if(!empty($p['setting_setting']) && !empty($p['setting_value'])){
            $setting_comp = urldecode($p['setting_comp']);
            $from 	.= ", users_settings.value AS setting ";
            $join 	.= " LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.value $setting_comp '{$p['setting_value']}' AND users_settings.setting = '{$p['setting_setting']}' ";
            $where 	.= " AND users_settings.value $setting_comp '{$p['setting_value']}' ";
            $group 	= " GROUP BY users.id ";
        }

        if(!empty($p['setting_setting']) && empty($p['setting_value'])){
            $from 	.= ", users_settings.value AS setting ";
            $join 	.= " LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = '{$p['setting_setting']}' ";
            $where 	.= " AND (users_settings.value = '0' OR users_settings.value IS NULL) ";
            $group 	= " GROUP BY users.id ";
        }

        if(!empty($p['calls'])){
            $from 	.= ", users_settings.value AS calls ";
            $join 	.= " LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'calls' ";
            //$having = " HAVING (calls = '1' OR calls IS NULL) ";
            //$group 	= " GROUP BY users.id ";
        }

        if($show_affiliate){
            $from 	.= ", affiliate.username AS affiliate ";
            $join 	.= " LEFT JOIN users AS affiliate ON affiliate.user_id = users.affe_id ";
        }

        $_SESSION['cur_query'] = "SELECT DISTINCT users.*$from FROM users
                                      $join
                                  WHERE 1 $where
                                      $group
                                      $having";

        $_SESSION['last_search'] = $_SESSION['cur_query'];
    }

    // TODO henrik remove
    function searchAddSort(){
        if(empty($_GET['order_by']) && empty($_SESSION['order_by']))
            $_SESSION['order_by'] = 'last_login';
        else if(!empty($_GET['order_by']))
            $_SESSION['order_by'] = $_GET['order_by']; /*TODO This is very vulnerable to SQLi. Not gonna bother fixing it since its only called on admin side. Please dont use this method further.*/

        //$_SESSION['cur_query'] 	= empty($_SESSION['cur_query']) ? $query : $_SESSION['cur_query'];

        if(!empty($_GET['order_by']))
            $_SESSION['asc_desc'] 	= empty($_SESSION['asc_desc']) ? 'DESC' : ($_SESSION['asc_desc'] == 'DESC' ? 'ASC' : 'DESC');
        else
            $_SESSION['asc_desc'] 	= empty($_SESSION['asc_desc']) ? 'DESC' : $_SESSION['asc_desc'];

        return $_SESSION['cur_query']." ORDER BY {$_SESSION['order_by']} ".$_SESSION['asc_desc'];
    }

    // TODO henrik remove
    function searchAsCsvDownload() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $query = $this->searchAddSort();
        return phive('SQL')->shs('merge', '', null, 'users')->loadArray($query);
    }

    /**
     * Detect if email address was unsubscribed by the system
     * @param $email
     * @param $user
     * @param string $type
     * @return bool
     */
    public function isEmailUnsubscribed($email, $user, $type = 'promo'): bool
    {
        if ($type !== 'promo') {
            return false;
        }
        if (empty($user) || !($user instanceof DBUser)) {
            $user = phive('UserHandler')->getUserByEmail($email);
        }
        if (empty($user)) {
            return false;
        }
        return $user->isUnsubscribed();
    }

    /**
     * Check self-exclusion restrictions of a user on another brand
     * @param User|int|string $user
     * @return bool
     */
    public function checkRemoveRemoteSelfExclusion($user): bool
    {
        $user = cu($user);

        if (empty($remote_user_id = linker()->getUserRemoteId($user, true))) {
            return false;
        }

        $remote = getRemote();

        if (is_array($remote_user_id)) {
            $remote_user_id = json_encode($remote_user_id);
            phive('Logger')->log('checkRemoveRemoteSelfExclusion',
                [
                    'message' => "User has linked multiple remote accounts {$remote_user_id} on {$remote}",
                    'user' => $user->getId()
                ]);
            return false;
        }

        $response = toRemote($remote,
            'remoteRemoveSelfExclusion',
            [$remote_user_id],
            2
        );
        if (!empty($response)) {
            phive('UserHandler')->logAction($user->getId(),
                "Remove remote self exclusion from {$remote} resulted in {$response['result']['success']}",
                "remove-remote-self-exclusion");
            return true;
        } else {
            phive('UserHandler')->logAction($user->getId(),
                "Remove remote self exclusion failed due to no response from {$remote} ",
                "remove-remote-self-exclusion");
            return false;
        }
    }

    /**
     * Handle remote self-exclusion
     * @param int|string $user_id
     * @return bool
     */
    public function handleRemoteSelfExclusion($user_id) : bool
    {
        $user = cu($user_id);
        list($block, $result) = lic('hasInternalSelfExclusion', [$user, false], $user);

        if ($block && $result === self::SELF_EXCLUSION_POSITIVE) {
            return $this->checkRemoveRemoteSelfExclusion($user_id);
        } else {
            return false;
        }
    }


    public function loginAsUser(string $username): void
    {
        $this->currentUser = $this->getUserByUsername($username);
    }

    private function isPaymentMethodDisabled(string $paymentMethod, string $country): bool
    {
        /** @var CasinoCashier $casinoCashier */
        $casinoCashier = phive('CasinoCashier');
        $config = $casinoCashier->getFullPspConfig()[$paymentMethod]['withdraw'];

        if (!($config['active'] ?? false)) {
            return true;
        }

        $isExcluded = in_array($country, $config['excluded_countries']);
        $isIncluded = in_array($country, $config['included_countries']);

        if ($isExcluded) {
            return true;
        } elseif ($isIncluded) {
            return false;
        }

        return false;
    }
}

/**
 * Gets the currently logged in user.
 *
 * @param mixed $uid User identifying element.
 * @param string $key TODO henrik remove
 * @param bool $from_master return the user data from master
 * @return bool|string|DBUser The result, DBUser if successful.
 */
function cu($uid = '', $key = 'id', $from_master = false){
    if(empty($uid)){
        $user = phive("UserHandler")->currentUser;
        if(empty($user))
            $uid = $_SESSION['user_id'];
        if(empty($uid))
            $uid = $_SESSION['mg_id'];
    }

    if(!empty($user))
        return $user;

    if(is_object($uid))
        return $uid;
    else if(is_array($uid)){
        return phive('UserHandler')->getUser((int)$uid[$key], $from_master);
    }else if(is_numeric($uid))
        return phive('UserHandler')->getUser((int)$uid, $from_master);
    else if(is_string($uid))
        return phive('UserHandler')->getUserByUsername($uid, $from_master);
    return false;
}

/**
* Current User Memory Set
*
* This is a wrapper around DBUser::mSet()
*
* @uses DBUser::mSet()
* @see DBUser::mSet()
*
* @param string $key The Redis key.
* @param string $value The value.
* @param mixed $uid User identifying element.
* @param int $expire Redis expiration in seconds.
*
* @return null|bool Null if all went well, false otherwise.
*/
function cuMset($key, $value, $uid = '', $expire = 36000){
    $u = cu($uid);
    if(!empty($u))
        return $u->mSet($key, $value, $expire);
    return false;
}

/**
* Current User Memory Get
*
* This is a wrapper around DBUser::mGet().
*
* @uses DBUser::mGet()
* @see DBUser::mGet()
*
* @param string $key The key to get.
* @param mixed $uid User identifying element.
* @param int $expire Redis expiration in seconds.
*
* @return string|bool The value in case all went well, false otherwise.
*/
function cuMget($key, $uid = '', $expire = 36000){
    $u = cu($uid);
    if(!empty($u))
        return $u->mGet($key, $expire);
    return false;
}

/**
* Wrapper around User::getSetting() with an extra check that prevents a fatal error in case there is no
* currently logged in user.
*
* @param string $key The key to get.
* @param mixed $uid User identifying element.
*
* @return string|bool The setting in case all went well, false otherwise.
*/
function cuSetting($key, $uid = ''){
  $user = cu($uid);
  if(empty($user))
    return false;
  return $user->getSetting($key);
}

/**
 * Wrapper around User::getAttr() with an extra check that prevents a fatal error in case there is no
 * currently logged in user.
 *
 * @param string $key The key to get.
 * @param mixed $uid User identifying element.
 *
 * @return string|bool The attribute in case all went well, false otherwise.
 */
function cuAttr($key, $uid = ''){
  $user = cu($uid);
  if(empty($user))
    return false;
  return $user->getAttr($key);
}

/**
* User Data (get)
*
* As opposed to cu() this function returns an associative array of the users row.
*
* @param mixed $data User identifying element.
*
* @return array The user row / data in case all went well, an empty array otherwise.
*/
function ud($data = null){
  if(empty($data))
    return cu()->data;
  if(is_array($data))
    return $data;
  if(is_numeric($data))
    return phive('UserHandler')->getRawUser($data);
  if(is_object($data))
    return $data->data;
  if(is_string($data)){
    $obj = phive('UserHandler')->getUserByUsername($data);
    return empty($obj) ? array() : $obj->data;
  }
  return array();
}

/**
* Gets the current user's id.
*
* @param mixed $data User identifying element.
*
* @return int 0 if failure, the user id in case of success.
*/
function uid($data = null){
    if(is_numeric($data))
        return $data;
    if(!empty($_SESSION['mg_id']))
        return $_SESSION['mg_id'];
    $ud = ud($data);
    return empty($ud) ? 0 : $ud['id'];
}

/**
* This function (in combination with dclickEnd()) can be used to try and prevent double clicks
* by users in contexts where a double click is absolutely not wanted such as payments.
*
* @uses phMgetShard() in order to store state and if we have true / not empty we stop
* code execution.
* @see phMgetShard()
*
* @param string $key The Redis key to check.
* @param mixed $uid The UserID
*
* @return bool True if no double click was detected.
*/
function dclickStart($key, $uid = null){
    if(isCli())
        return true;

    if (is_null($uid)) {
        $uid = uid();
    }

    if(!empty(phMgetShard($key, $uid)))
        die('dclick');
    // 10 seconds expiry should be enough
    phMsetShard($key, 'yes', $uid, 10);
    return true;
}

/**
* Used to clean up the key set by dclickStart() in order to allow certain clicks again.
*
* @param string $key The Redis key to delete.
* @param mixed $return An arbitrary value in order to allow this method to be called AND return
* from the parent function / method at the same time.
* @param mixed $uid The UserID
*
* @return mixed The return value.
*/
function dclickEnd($key, $return = null, $uid = null){
    if(isCli()){
        return $return;
    }

    if (is_null($uid)) {
        $uid = uid();
    }

    phMdelShard($key, $uid);
    return $return;
}

/**
* Returns the user's registration country in case there is a logged in user, if
* no user is logged in we get the country from the requesting IP.
*
* @param mixed $u_info User identifying element.
*
* @return string ISO2 country code.
*/
function getCountry($u_info = null){
    $country = ud($u_info)['country'];
    return empty($country) ? phive('IpBlock')->getCountry() : $country;
}

/**
 * Get the user id + handle the Special scenario during registration between step1 and step2
 * Otherwise we will not get the userId
 *
 * @param ?mixed user check cu().
 *
 * @return bool|DBUser|string
 */
function cuRegistration($user_id = null)
{
    $user = !empty($user_id) ? cu($user_id) : cu();
    if (!empty($_SESSION['rstep1'])) {
        $user = empty($user) ? cu($_SESSION['rstep1']['user_id']) : $user;
    } elseif (!empty($_GET['uid'])) { // TODO check with @Goran where this is used and if we need to keep it... probably from registration email... /Paolo
        $user = cu($_GET['uid']);
    }

    return $user;
}

/**
 * @param $value
 * @param string $tag
 * @return mixed
 */
function logToActionsAndReturn($value, string $tag = 'error')
{
    try {
        $toEncode = is_object($value) && method_exists($value, 'toArray')
            ? $value->toArray()
            : $value;

        $toLog = json_encode($toEncode, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        $toLog = "Encoding failed: " . $e->getMessage();
    }

    $user = cuRegistration();

    if (!$user) {
        phive()->dumpTbl($tag, $toLog);

        return $value;
    }

    phive('DBUserHandler')->logAction($user->getId(), $toLog, $tag, false, $user->getId());

    return $value;
}
