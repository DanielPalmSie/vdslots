<?php

use Carbon\Carbon;
use DBUserHandler\CurrencyMoveStatus;
use DBUserHandler\DBUserRestriction;
use Laraphive\Contracts\EventPublisher\EventPublisherInterface;
use Laraphive\Contracts\IpBlock\IpBlockInterface;
use Laraphive\Domain\Payment\DataTransferObjects\Requests\WithdrawRequestData;
use Laraphive\Domain\Payment\DataTransferObjects\Responses\WithdrawResponseData;
use Laraphive\Domain\Payment\Factories\DepositProviderFactory;
use Laraphive\Domain\Payment\Factories\WithdrawResponseFactory;
use Laraphive\Domain\User\Actions\GetStep1FieldsAction;
use Laraphive\Domain\User\Actions\Step1FieldsFactory;
use Laraphive\Domain\User\Actions\Steps\DataTransferObjects\FinalizeRegistrationStep1Data;
use Laraphive\Domain\User\Actions\Steps\DataTransferObjects\Step1FieldsData;
use Laraphive\Domain\User\DataTransferObjects\AccountHistoryData;
use Laraphive\Domain\User\DataTransferObjects\AccountHistoryResponse;
use Laraphive\Domain\User\DataTransferObjects\DBUserData;
use Laraphive\Domain\User\DataTransferObjects\FinalizeRegistrationStep1Response;
use Laraphive\Domain\User\DataTransferObjects\FinalizeRegistrationStep2Response;
use Laraphive\Domain\User\DataTransferObjects\EditProfile\EditProfileResponseData;
use Laraphive\Domain\User\DataTransferObjects\LoginCommonData;
use Laraphive\Domain\User\DataTransferObjects\LoginData;
use Laraphive\Domain\User\DataTransferObjects\LoginKycData;
use Laraphive\Domain\User\DataTransferObjects\LoginKycEventData;
use Laraphive\Domain\User\DataTransferObjects\LoginKycResponse;
use Laraphive\Domain\User\DataTransferObjects\LoginWithApiResponse;
use Laraphive\Domain\User\DataTransferObjects\RegisterUserStep1Data;
use Laraphive\Domain\User\DataTransferObjects\RegisterUserStep2Data;
use Laraphive\Domain\User\DataTransferObjects\Requests\GetGameRtpGraphRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\GetGameRtpGraphResponseData;
use Laraphive\Domain\User\DataTransferObjects\Requests\GetRtpRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\GetRtpResponseData;
use Laraphive\Domain\User\DataTransferObjects\Responses\ImportUserFromBrandPopupResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateIntendedGamblingData;
use Laraphive\Domain\User\DataTransferObjects\UpdateUserPasswordResponse;
use Laraphive\Domain\User\DataTransferObjects\UserContactData;
use Laraphive\Domain\User\DataTransferObjects\ValidateStep1FieldsResponse;
use Laraphive\Domain\User\DataTransferObjects\CaptchaResponseData;
use Laraphive\Domain\User\DataTransferObjects\ValidateStep2FieldsResponse;
use Laraphive\Domain\User\DataTransferObjects\SimilarityCheckData;
use Laraphive\Domain\User\Factories\CountryServiceFactory;
use Laraphive\Domain\User\Factories\EditProfileFactory;
use Laraphive\Domain\User\Factories\GetGameRtpGraphResponseDataFactory;
use Laraphive\Domain\User\Factories\GetRtpResponseDataFactory;
use Laraphive\Domain\User\Factories\RegisterUserStep1Factory;
use Laraphive\Domain\User\Factories\ImportUserFromBrandPopupFactory;
use Laraphive\Domain\User\Factories\RegisterUserStep2RequestFactory;
use Laraphive\Domain\User\Factories\UserRtpSearchFactory;
use Laraphive\Domain\User\DataTransferObjects\GameHistoryData;
use Laraphive\Domain\User\DataTransferObjects\GameHistoryResponse;
use Laraphive\Domain\User\DataTransferObjects\ListDocumentsResponse;
use Laraphive\Domain\User\DataTransferObjects\UploadDocumentsResponse;
use Laraphive\Domain\User\Factories\Response\FinalizeRegistrationStep1ResponseFactory;
use Laraphive\Support\DataTransferObjects\ErrorsOrEmptyResponse;
use Laraphive\Domain\User\Factories\GameHistoryFactory;
use Laraphive\Domain\User\DataTransferObjects\LoginHistoryData;
use Laraphive\Domain\User\DataTransferObjects\LoginHistoryResponse;
use Laraphive\Domain\User\Factories\AccountHistoryFactory;
use Laraphive\Domain\User\Factories\LoginHistoryFactory;
use RgEvaluation\Factory\DynamicVariablesSupplierResolver;
use Videoslots\FraudDetection\AssignEvent;
use Videoslots\FraudDetection\FraudFlags\SourceOfFundsRequestedFlag;
use Videoslots\HistoryMessages\InterventionHistoryMessage;
use Laraphive\Domain\User\DataTransferObjects\CheckRgLimitsResponse;
use Laraphive\Domain\User\DataTransferObjects\NotificationHistoryData;
use Videoslots\User\Factories\EditProfileAccountInfoFactory;
use Videoslots\User\Factories\EditProfileContactInfoFactory;
use Videoslots\User\Factories\EditProfilePersonalInfoFactory;
use Videoslots\User\Factories\EditProfileTermsConditionsFactory;
use Videoslots\User\Factories\LoginRedirectsServiceFactory;
use Laraphive\Domain\User\DataTransferObjects\Requests\UserRtpSearchRequestData;
use Laraphive\Domain\User\DataTransferObjects\Responses\UserRtpSearchResponseData;
use Laraphive\Domain\Content\DataTransferObjects\Requests\EventsRequestData;
use Laraphive\Domain\Payment\Constants\PspActionType;
use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

require_once __DIR__ . '/../UserHandler/UserHandler.php';
require_once __DIR__ . '/DBUser.php';
require_once __DIR__ . '/UserStatus.php';
require_once __DIR__ . '/../Cashier/Mts.php';
require_once __DIR__ . '/../BoxHandler/boxes/diamondbet/CashierDepositBoxBase.php';
require_once __DIR__ . '/../BoxHandler/boxes/diamondbet/MobileDepositBoxBase.php';
require_once __DIR__ . '/../BoxHandler/boxes/diamondbet/MobileWithdrawBoxBase.php';
require_once __DIR__ . '/../BoxHandler/boxes/diamondbet/DesktopWithdrawBoxBase.php';
require_once __DIR__ . '/../Licensed/Libraries/Bluem.php';
require_once __DIR__ .'/../../traits/HasSitePublisherTrait.php';
require_once __DIR__ .'/UserDocuments.php';

/**
 * A class with a bunch of methods pertaining to handling users and user information which covers more casino specific
 * features than UserHandler which it extends.
 *
 * TODO henrik cleanup all sh() and shs() calls here and in UserHandler too.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_settings The wiki page for the users settings table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users The wiki page for the users table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_triggers_log The wiki page for the triggers table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_actions The wiki page for the actions table.
 */
class DBUserHandler extends UserHandler {
    use HasSitePublisherTrait;

    /**
     * @var string Timestamp that will override NOW() when inserting triggers if it is not empty.
     */
    public $force_created_at;

    /** @var ZignSec */
    public $zs;

    /** @var ZignSec API version 5 */
    public $zs5;

    // TODO henrik move this to the place where it is used instead, no need for a constant.
    const USER_SIMILARIRY_TEST_CASES = [
            ['firstname', 'lastname', 'dob', 'zipcode'],
            ['firstname', 'lastname', 'dob', 'mobile'],
            ['firstname', 'lastname', 'dob', 'email'],
            ['firstname', 'lastname', 'dob', 'address', 'city'],
            ['firstname', 'lastname', 'address', 'country'],
            ['firstname', 'password', 'address', 'country'],
            ['firstname', 'password', 'zipcode', 'country'],
            ['lastname', 'dob', 'address', 'country'],
            ['lastname', 'password', 'address', 'country'],
            ['lastname', 'password', 'zipcode', 'country'],
            ['dob', 'address', 'country', 'password'],
            ['dob', 'zipcode', 'country', 'password']
    ];

    // event used in websocket communicating client connected
    const WS_EVENT_OPEN = 'open';
    // event used in websocket communicating client disconnected
    const WS_EVENT_CLOSE = 'close';

    const USER_BLOCK_STATUS_NO_BLOCK = -1;

    /**
     * @var int
     */
    private const AUTH_TOKEN_USER_ID_KEY = 0;

    /**
     * @var int
     */
    private const AUTH_TOKEN_TOKEN_ID_KEY = 1;

    /**
     * @var int
     */
    private const AUTH_TOKEN_TOKEN_KEY = 2;

    /** @var \Laraphive\Domain\User\Actions\Step1FieldsFactory */
    private $step1FieldsFactory;

    /**
     * @var \Laraphive\Domain\User\Actions\GetStep1FieldsAction
     */
    private $getStep1FieldsAction;

    /**
     * @return \Laraphive\Domain\User\Actions\Step1FieldsFactory
     */
    private function step1FieldsFactory(): Step1FieldsFactory
    {
        if (!$this->step1FieldsFactory) {
            $this->step1FieldsFactory = phiveApp(Step1FieldsFactory::class);
        }

        return $this->step1FieldsFactory;
    }

    /**
     * @return \Laraphive\Domain\User\Actions\GetStep1FieldsAction
     */
    private function getGetStep1FieldsAction(): GetStep1FieldsAction
    {
        if (!$this->getStep1FieldsAction) {
            $this->getStep1FieldsAction = phiveApp(GetStep1FieldsAction::class);
        }

        return $this->getStep1FieldsAction;
    }

    function phAliases()	{ return array('UserHandler'); }

    /**
     * This is being run after the constructor which avoids having to have a special load order in modules.php.
     */
    function phInstall()
    {
        $this->loc = phive('Localizer');
        $this->zs = phive('DBUserHandler/ZignSec');
        $this->zs5 = phive('DBUserHandler/ZignSecV5');
    }

    // TODO henrik remove
    public function useLoaderOnLogin()
    {
        if ($this->getSetting('login_loader') !== true) {
            return null;
        }

        if (!in_array(cuCountry(), $this->getSetting('login_loader_countries'))) {
            return null;
        }
        return '1';
    }

    /**
     * Wrapper around getNewestTrigger() to get only the cnt value.
     *
     * @uses DBUserHandler::getNewestTrigger()
     *
     * @param int $uid The user id the logged trigger belongs to.
     * @param string $trigger The trigger name.
     *
     * @return int The amount of times the trigger has triggered.
     */
    function getTriggerCounter($uid, $trigger){
        return $this->getNewestTrigger($uid, $trigger)['cnt'];
    }

    /**
     * This function is used as a condition to know if we need to fire a trigger or not, if FALSE we fire.
     * We return TRUE instead if the user has triggered at least once the requested trigger in the last "X FREQUENCY".
     * - X is a number (Ex. 30 - DEFAULT)
     * - Y is a time identifier (Ex. DAY - DEFAULT, WEEK, MONTH, YEAR)
     *
     * Ex. with default setting:
     * - if we triggered the first time on 2020-03-10, the next time that this let us fire the trigger will be on 2020-04-09
     *
     * @param int $uid The user id the logged trigger belongs to.
     * @param string $trigger The trigger name.
     * @param int $time The time interval (typically in days).
     * @param string $frequency The frequency (typically days).
     * @return bool True if the user has triggered, false otherwise.
     */
    public function hasTriggeredLastPeriod($uid, $trigger, $time = 30, $frequency = "DAY") {
        $uid = uid($uid);
        return phive('SQL')->sh($uid)->getValue("SELECT count(*) as cnt FROM triggers_log WHERE user_id = $uid AND trigger_name = '{$trigger}' AND created_at > (NOW() - INTERVAL {$time} {$frequency})") > 0;
    }

    /**
     * Similar to the above "hasTriggeredLastPeriod" will allow a trigger to be fired only once a month.
     * FALSE means that we can fire the trigger, TRUE that it was already fired.
     *
     * This function doesn't care about the number of days between the previously fired trigger and the current one.
     * Ex. we can fire an event on 2020-03-25 and then fire again on the 2020-04-01.
     *
     * @param int|DBUser $uid The user id the logged trigger belongs to.
     * @param string $trigger The trigger name.
     * @return bool True if the user has triggered, false otherwise.
     */
    public function hasTriggeredCurrentMonth($uid, $trigger)
    {
        $last_triggered = $this->getTriggerCreationDate($uid, $trigger);
        return phive()->hisNow($last_triggered, 'Y-m') == phive()->hisNow('', 'Y-m') && !empty($last_triggered);
    }

    /**
     * Gets the most recently inserted trigger row of a certain type.
     *
     * @param int $uid The user id the logged trigger belongs to.
     * @param string $trigger The trigger name.
     *
     * @return array The trigger row.
     */
    function getNewestTrigger($uid, $trigger){
        $uid = uid($uid);
        return phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM triggers_log WHERE user_id = $uid AND trigger_name = '$trigger' ORDER BY id DESC LIMIT 1");
    }

    /**
     * Wrapper around getNewestTrigger() to get only the created_at value.
     *
     * @uses DBUserHandler::getNewestTrigger()
     *
     * @param int|DBUser $uid The user id the logged trigger belongs to.
     * @param string $trigger The trigger name.
     *
     * @return string The created_at timestamp.
     */
    function getTriggerCreationDate($uid, $trigger){
        return $this->getNewestTrigger($uid, $trigger)['created_at'];
    }

    // TODO henrik remove
    function updateNewestTrigger($u, $trigger, $updates){
        $id = $this->getNewestTrigger($u, $trigger)['id'];
        return phive('SQL')->sh($u, 'id')->updateArray('triggers_log', $updates, ['id' => $id]);
    }

    /**
     * A setter for $force_created_at.
     *
     * @param $created_at The timestamp to override NOW() with when inserting triggers.
     *
     * @return null
     */
    public function setTriggerCreatedAt($created_at)
    {
        $this->force_created_at = $created_at;
    }

    /**
     * Logs a trigger in the triggers_log table.
     *
     * @param DBUser $u The user.
     * @param string $trigger The trigger name.
     * @param string $descr Information about what triggered the trigger.
     * @param bool $retrigger Do we want at re-trigger or not?
     * @param bool $date Do we only want to re-trigger once per day?
     * @param int $counter If empty counter will default to 0.
     * @param string $extra Extra information that might be needed in certain situations.
     *
     * @return int The id of the trigger.
     */
    function logTrigger($u, $trigger, $descr = '', $retrigger = true, $date = true, $counter = '', $extra = '', $txt = '')
    {
        if (in_array($trigger, $this->getSetting('no_arf', []))) {
            return false;
        }
        $u = cu($u);
        $uid = $u->getId();

        // We don't want to retrigger so we look for a potentially already existing trigger of the same type
        // and return / exit if it does.
        if (!$retrigger) {
            $old = phive('SQL')->sh($uid)->getValue("SELECT id FROM triggers_log WHERE user_id = $uid AND trigger_name = '$trigger'");
            if (!empty($old))
                return false;
        }

        if ($date) {
            [$start, $end] = phive()->todaySpan();
            $old = phive('SQL')->sh($uid)->getValue("SELECT id FROM triggers_log WHERE user_id = $uid AND trigger_name = '$trigger' AND created_at >= '$start'");
            if (!empty($old))
                return false;
        }

        $insert = ['user_id' => $uid, 'trigger_name' => $trigger, 'descr' => phive()->ellipsis($descr, 255), 'cnt' => $counter, 'data' => $extra, 'txt' => $txt];
        if (!empty($this->force_created_at)) {
            $insert['created_at'] = $this->force_created_at;
        }
        $result = phive('SQL')->sh($uid)->insertArray('triggers_log', $insert);
        $log_id = phive('UserHandler')->logAction($uid, "set-flag|mixed - Triggered flag {$trigger}", 'intervention');
        lic('triggerGrsRecalculation', [$trigger, $u], $u);
        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
                'intervention_done',
                new InterventionHistoryMessage([
                    'id'                => (int) $log_id,
                    'user_id'           => (int) $uid,
                    'begin_datetime'    => phive()->hisNow(),
                    'end_datetime'      => '',
                    'type'              => 'set-flag',
                    'flag'              => $trigger,
                    'event_timestamp'   => time(),
                ]),
        ], $uid);
        $this->addUserCommentOnRgPopup($u, $trigger);
        phive('RgAction/RgTriggerActionPerformer')->executeAction($u, $trigger);

        return $result;
    }

    /**
     * Removes a trigger from table triggers_log.
     *
     * @param mixed $user Element containing user identifying information.
     * @param string $trigger The trigger name.
     *
     * @return bool True if the DELETE query was successful, false otherwise.
     */
    function removeTrigger($user, $trigger)
    {
        $user_id = uid($user);

        return phive('SQL')->delete('triggers_log', ['user_id' => $user_id, 'trigger_name' => $trigger], $user_id);
    }

    /**
     * Gets the json decoded values for a specific and most recent trigger for a user.
     *
     * @param int $user_id
     * @param string $trigger_name
     * @return array
     */
    public function getArrayFromLastTriggerData(int $user_id, string $trigger_name): array
    {
        $trigger_data = phive('SQL')->sh($user_id)->getValue("
            SELECT data
            FROM triggers_log
            WHERE user_id = {$user_id}
              AND trigger_name = '{$trigger_name}'
            ORDER BY created_at DESC
            LIMIT 1;
        ");

        if (empty($trigger_data)) {
            return [];
        }

        return json_decode($trigger_data, true);
    }

    // TODO henrik remove
    function hasVerifiedContactInfo($user, $settings = []){
        if(empty($settings))
            $settings = $user->getAllSettings('', true);
        // We're dealing with a legacy player that doesn't even have this info so we give the player a pass.
        if(empty($settings['sms_code_verified']) && empty($settings['email_code_verified']))
            return true;
        if($settings['sms_code_verified']['value'] == 'yes')
            return true;
        if($settings['email_code_verified']['value'] == 'yes')
            return true;
        return false;
    }

    /**
     * This method will aggregate stats from all shards / nodes into the master database to avoid having to subsequently query all
     * nodes in various BI (Business Intelligence) GUIs.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_users_daily_game_stats The wiki page for the daily game stats table.
     * @link https://wiki.videoslots.com/index.php?title=DB_table_users_daily_stats The wiki page for the daily stats table..
     * @param string $tbl The stats table.
     * @param string $date The date to aggregate.
     *
     * @return null
     */
    function aggregateUserStatsTbl($tbl, $date){
        if(!phive('SQL')->isSharded($tbl))
            return false;
        $rows = phive('SQL')->shs('merge', '', null, $tbl)->loadArray("SELECT * FROM $tbl WHERE `date` = '$date'");
        foreach($rows as &$r)
            unset($r['id']);
        // We don't use SQL->delete here as it would run on all nodes too, not what we want in this case.
        phive('SQL')->query("DELETE FROM $tbl WHERE `date` = '$date'");
        phive('SQL')->insertTable($tbl, $rows);
    }

    /**
     * This is the entrypoint for checking if the registering user is trying to create duplicate accounts.
     *
     * @link https://www.php.net/manual/en/function.levenshtein.php
     * @param mixed $ud User identifying element.
     * @param int $thold Levenshtein distance to use for the check (used on multiple concatenated user attributes to determine similarity).
     *
     * @return string **OK** if all is good, an error message otherwise, ie in case the new account is too similar to an old account.
     */
    function lgaFraudCheck($ud, $thold = 30)
    {
        $cur_u = cu($ud);
        if(empty($cur_u))
            return 'ok';
        $simstr = $this->getSimilarUsers($ud, $thold);
        if(!empty($simstr)) {
            $fraud_msg = t('too.similar.html');

            $cur_u->setSetting('similar_fraud', 1);
            $this->addBlock($cur_u, 10);
            $this->logAction($cur_u, "Blocked because too similar to $simstr", 'block');

            //This is here temporary to prevent customers with an account already registered to not to be able to use their previous account
            $cur_u->deleteSetting('nid');
            $cur_u->deleteSetting('nid_data');

            $this->logout('possibly duplicate account');
            $_SESSION = array();
            return $fraud_msg;
        }
        return 'ok';
    }

    /**
     * Checks all user accounts againts the passed in user data / info and returns all that are similar enough.
     *
     * @link https://www.php.net/manual/en/function.levenshtein.php
     * @param mixed $ud User identifying element.
     * @param int $thold Levenshtein distance to use for the check (used on multiple concatenated user attributes to determine similarity).
     *
     * @return string A comma separated string with the usernames of the old accounts that are similar.
     */
    function getSimilarUsers($ud, $thold = 30)
    {
        [$similar, $therest] = $this->checkSimilar($ud, $thold);
        if(!empty($similar)) {
            $simstr = '';
            foreach($similar as $u) {
                $simstr .= $u['user']['username'].',';
            }
            $simstr = rtrim($simstr, ',');
            return $simstr;
        }
        return '';
    }

    // TODO henrik remove if rgapi.php is not used.
    function getRegsBetween($sstamp, $estamp){
        $str = "SELECT * FROM users ".phive('SQL')->tRng($sstamp, $estamp, 'DATE(register_date)', 'WHERE');
        return phive('SQL')->shs('merge', '', null, 'users')->loadArray($str);
    }

    // TODO henrik remove if action_search.php is not used.
    function getActions($sstamp, $estamp, $limit, $where_tag = '', $where_descr = '', $where_uid = ''){
        $rng   = phive('SQL')->tRng($sstamp, $estamp, 'created_at', 'WHERE');
        $limit = empty($limit) ? '' : "LIMIT $limit";
        $sql   = "SELECT * FROM actions $rng $where_tag $where_descr $where_uid $limit";
        return phive('SQL')->shs('merge', '', null, 'actions')->loadArray($sql);
    }

    /**
     * Gets one action with the help of the action tag and the target user id.
     *
     * @param string $tag The tag.
     * @param int $uid The user id.
     * @param string $descr Optional description and if not empty will be used to match the action description as well.
     *
     * @return array The action.
     */
    function getActionByTagUid($tag, $uid, $descr = '')
    {
        $uid = uid($uid);
        $where_descr = empty($descr) ? '' : "AND descr = '$descr'";
        $str = "SELECT * FROM actions WHERE tag = '$tag' AND target = $uid $where_descr";

        return phive('SQL')->sh($uid, '', 'actions')->loadAssoc($str);
    }

    /**
     * Gets one action with the help of the action tag, the target user id and created at timestamp.
     *
     * @param string $tag The tag.
     * @param int $uid The user id.
     * @param string $created_at The created at timestamp.
     * @param string $descr Optional description and if not empty will be used to match the action description as well.
     *
     * @return array The action.
     */
    function getActionByTagUidCreatedAt($tag, $uid, $created_at, $descr = '')
    {
        $uid = uid($uid);
        $where_descr = empty($descr) ? '' : "AND descr LIKE '%{$descr}%'";
        $str = "SELECT * FROM actions WHERE tag = '{$tag}' AND target = {$uid} AND created_at = '{$created_at}'  {$where_descr}";

        return phive('SQL')->sh($uid, '', 'actions')->loadAssoc($str);
    }

    /**
     * Gets actions for a target user.
     * Support filtering by:
     * - tag(s)
     * - custom where condition.
     * and
     * - sorting
     * - limit
     *
     * @param DBUser $user The target user.
     * @param array $tags filter by tag(s).
     * @param array $where_extra Array with extra WHERE clauses Ex. ["column = 'something'", "..."].
     * @param array $sort_by Sort by column/order based on key/value Ex. ['created_at' => 'desc'].
     * @param string $limit Optional LIMIT clause. Ex. "100" or "200,100"
     *
     * @return array An array of actions.
     */
    public function getUserActions($user, $tags = [], $where_extra = [], $sort_by = ['created_at' => 'desc'], $limit = ''){
        $where = [];
        if(!empty($tags)) {
            $tags = phive('SQL')->makeIn($tags);
            $where[] = "tag in ($tags)";
        }
        if(!empty($where_extra)) {
            $where = array_merge($where, $where_extra);
        }
        $where_sql = empty($where) ? '' : ' AND '.implode(' AND ', $where);
        $user_id = uid($user);
        if (!empty($sort_by)){
            $order_by = "Order by " .array_key_first($sort_by);
            $order_sort = array_pop($sort_by);
        }else{
            $order_by = '';
            $order_sort = '';
        }

        $limit = !empty($limit) ? "LIMIT $limit" : '';
        $sql = "SELECT *  FROM actions WHERE target = $user_id $where_sql $order_by $order_sort $limit";
        return phive('SQL')->sh($user_id)->loadArray($sql);
    }

    /**
     * A wrapper around Phive::getSiteUrl() but with the added logic of determininig the subdomain by way of IP
     * and a configuration array, typically used in order to route certain countries to a different sub than www
     * Russians to for instace ru.
     *
     * @param string $country ISO2 country code, if omitted the request IP will be used to determine country.
     * @param $lang TODO henrik remove
     *
     * @return string The site URL.
     */
    function getSiteUrl($country = '', $lang = ''){
        $country = empty($country) ? phive('IpBlock')->getCountry() : $country;
        $domain = $this->getSetting('domains')[$country];
        return phive()->getSiteUrl($domain);
    }

    /**
     * Returns the configured daily stats table, typically users_daily_stats.
     *
     * @return string The table.
     */
    function dailyTbl(){
        $tbl = $this->getSetting('users_stats_table');
        if(empty($tbl))
            $tbl = 'users_daily_stats';
        return $tbl;
    }

    /**
     * A general purpose method to get the count by way of a date / timestamp column in a table, eg
     * the number of registrations between two dates.
     *
     * @param string $sdate The start date / stamp.
     * @param string $edate The end date / stamp.
     * @param string $col The column to use, eg created_at.
     * @param string $extra Extra WHERE filters.
     * @param string $tbl The table.
     *
     * @return int The count.
     */
    function countInPeriod($sdate, $edate, $col, $extra = '', $tbl = 'users'){
        $str = "SELECT COUNT(*) FROM $tbl WHERE `$col` >= '$sdate' AND `$col` <= '$edate' $extra";
        return array_sum(phive()->flatten(phive('SQL')->readOnly()->shs('merge', '', null, $tbl)->loadArray($str)));
    }

    /**
     * Gets rows from a stats table between two dates, we fetch from the master so the date range needs to
     * contain data that has been fully aggregated from the nodes first.
     *
     * @see DBUserHandler::aggregateUserStatsTbl()
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $extra Optional extra WHERE statements.
     * @param string $tbl The stats table to use.
     * @param bool|string $by_key False if numerical result array is wanted, the column whose value to use as the key otherwise.
     *
     * @return array The result array of stat rows.
     */
    function getDailyStats($sdate, $edate, $extra = '', $tbl = 'users_daily_stats', $by_key = false){
        $where_date = $sdate == $edate ? "`date` = '$sdate'" : "`date` >= '$sdate' AND `date` <= '$edate'";
        $str = "SELECT * FROM $tbl WHERE $where_date  $extra";
        //return phive('SQL')->shs('merge', '', null, $tbl)->loadArray($str, 'ASSOC', $by_key);
        return phive('SQL')->loadArray($str, 'ASSOC', $by_key);
    }

    /**
     * Overloaded newUser() but instead of UserHandler's version that returns a User object we here return a DBUser object instead.
     *
     * @param array|int $ud User row / id.
     *
     * @return DBUser The user object.
     */
    public function newUser($ud){
        return new DBUser($ud);
    }

    // TODO henrik remove this
    function joinGroup(&$user, $gid){
        $this->joinLeaveGroup($user, $gid, 'added to', 'joinGroup');
    }

    // TODO henrik remove this
    function leaveGroup(&$user, $gid){
        $this->joinLeaveGroup($user, $gid, 'removed from', 'leaveGroup');
    }

    // TODO henrik remove this
    function joinLeaveGroup(&$user, $gid, $descr, $func){
        pOrDie('edit.groups');
        $group = $this->getGroup($gid);
        $user->$func($gid);
        $this->logAction($user, ": {$user->getUsername()} was $descr {$group->getName()}", 'group', true, cu());
        $this->logIp(cu(), $user, 'group', "{$user->getUsername()} was $descr {$group->getName()}");
    }

    /**
     * Logs the ip of the current actor, ususally in connection with actions like fund transfers etc.
     *
     * @param int $aid The actor id.
     * @param int $tid The id of the user that is the target of the actor's action.
     * @param string $tag Action tag.
     * @param string $descr Action description.
     * @param int $tr_id Potential transaction id that was the result of the action.
     *
     * @return null
     */
    function logIp($aid, $tid, $tag, $descr, $tr_id = 0){
        $tid   = uid($tid);
        $actor = ud($aid);
        $ip    = remIp();
        if(empty($ip))
            $ip = $actor['cur_ip'];
        phive('SQL')->sh($tid, '', 'ip_log')->insertArray('ip_log', array(
            'ip_num'         => $ip,
            'actor'          => $actor['id'],
            'target'         => $tid,
            'descr'          => $descr,
            'tag'            => $tag,
            'tr_id'          => $tr_id,
            'actor_username' => mb_strimwidth($actor['username'], 0, 25)
        ));
    }

    /**
     * Get user settings based on a different user setting.
     *
     * @param string $mainSetting The main setting we want to look for.
     * @param string $filterSetting The other setting that needs to be empty / absent.
     * @param string $condition The value the main setting will compared with.
     * @param string $conditionOperator The operator that the main setting value will be compared with.
     * @return array The result array of settings.
     */
    function getSettingsWhereNotOtherSettingIs($mainSetting, $filterSetting, $condition, $conditionOperator = '=')
    {
        return phive('SQL')->shs('merge')->loadArray("
            SELECT us.*
            FROM users_settings AS us
            LEFT JOIN (
                SELECT DISTINCT user_id
                FROM users_settings
                WHERE setting = '$filterSetting'
            ) AS us2 ON us.user_id = us2.user_id
            WHERE setting = '$mainSetting'
              AND value $conditionOperator '$condition'
              AND us2.user_id IS NULL;
        ");
    }

    /**
     * Get all excluded users who are not indefinitely self excluded.
     *
     * @uses DBUserHandler::getSettingsWhereNotOtherSettingIs() which it is wrapping.
     *
     * @param string $condition The value the main setting will compared with.
     * @return array The result array of settings.
     */
    function getExcludedButNotPermanently($condition)
    {
        return $this->getSettingsWhereNotOtherSettingIs('unexclude-date', 'indefinitely-self-excluded', $condition, '<');
    }

    /**
     * Automatically unexclude users who are not indefinitely self excluded.
     *
     * Method is called from cronjob.
     */
    function unexcludeCron()
    {
        $today = phive()->today();
        $to_unexclude = $this->getExcludedButNotPermanently($today);

        foreach ($to_unexclude as $un) {
            $u = cu($un['user_id']);
			phive('DBUserHandler/PrivacyHandler')->clearAll($u);
			$u->deleteSettings('lock-hours', 'lock-date', 'unlock-date', 'super-blocked', 'unexclude-date', 'aml52-payout-details-requested');
            $u->setSetting('unexcluded-date', $today);


			if($this->handleRemoteSelfExclusion($u->getId())){
                phive('Logger')->info('unexcludeCron',
                    [   'message' => "User:{$u->getId()} has remote self-exclusion block: self-exclusion lift",
                        'user' => $u->getId()
                    ]);
            }else{
                phive('Logger')->info('unexcludeCron',
                    [   'message' => "User:{$u->getId()} has no remote self-exclusion block: no actions required",
                        'user' => $u->getId()
                    ]);
            }
            lic('trackUserStatusChanges', [$u, UserStatus::STATUS_DORMANT], $u);
        }
    }

    /**
     * Get all excluded users who are not indefinitely self excluded.
     *
     * @uses DBUserHandler::getSettingsWhereNotOtherSettingIs() which it is wrapping.
     *
     * @return array The result array of settings.
     */
    function getNotClosedPermanentSelfExclusions()
    {
        return $this->getSettingsWhereNotOtherSettingIs('indefinitely-self-excluded', 'closed_account', 1);
    }

    /**
     * Automatically close expired permanently excluded account.
     *
     * Method is called from cronjob.
     */
    function updatePermanentExclusion()
    {
        $exclusions = $this->getNotClosedPermanentSelfExclusions();

        foreach ($exclusions as $exclusion) {
            if (empty($user = cu($exclusion['user_id']))) {
                continue;
            }

            $unexclude_date = $user->getSetting('unexclude-date');

            // If there is no un exclude date and if a year has not passed since the exclusion came into effect do nothing.
            if (!phive()->validateDate($unexclude_date) && phive()->subtractTimes(time(), strtotime($exclusion['created_at']), 'y', 0, false) < 1) {
                continue;
            }

            // If there is un exclude date, and time has not passed do nothing.
            if(phive()->validateDate($unexclude_date) && time() < strtotime($unexclude_date)) {
                continue;
            }
            if (!licSetting('close_indefinitely_self_excluded_country', $user)){
                continue;
            }
            // "close" the old account to allow registration of the same email/username + nid.
            // Applicable when exclude date has passed if present, else if a year has passed since exclusion came into effect.
            $this->closeAccount($user);
        }
    }

    /**
     * Close a user account
     * @param $user
     */
    public function closeAccount($user)
    {
        $user->updateData([
            'nid' => "closed_{$user->getData('nid')}",
            'email' => "closed_{$user->getId()}_{$user->getData('email')}",
            'username' => "closed_{$user->getId()}_{$user->getData('username')}",
            'mobile' => "closed_{$user->getId()}_{$user->getData('mobile')}",
        ], true);

        $user->setSetting('closed_account', 1);
        lic('trackUserStatusChanges', [$user, UserStatus::STATUS_SUSPENDED], $user);
    }

    /**
     * Common SQL helper for doing FX in the DB queries.
     *
     * @param string $join_tbl_prefix The short alias of the table whose rows we want to do FX on.
     * @param string $join_tbl_date_col The date column we need to use in order to get the correct FX rate for that date.
     * @param string $cur_prefix The alias that the fx_rates table has in the main query.
     *
     * @return string The FX rates JOIN query part.
     */
    function leftJoinCurrencies($join_tbl_prefix = '', $join_tbl_date_col = 'date', $cur_prefix = 'cur'){
        $join_tbl_prefix = empty($join_tbl_prefix) ? '' : "$join_tbl_prefix.";
        return " LEFT JOIN fx_rates AS $cur_prefix ON $cur_prefix.code = {$join_tbl_prefix}currency AND $cur_prefix.day_date = $join_tbl_prefix$join_tbl_date_col";
    }

    /**
     * Gets misc. stats, not just revenue and between two dates (not necessarily lifetime) so this method name could be better.
     *
     * @param string $sreg A date that the users' registration date needs to be equal to or bigger.
     * @param string $ereg A date that the users' registration date needs to be equal to or smaller.
     * @param string $splay A start date that rows in the stats table needs to be bigger than or equal to.
     * @param string $eplay An end date that rows in the stats table needs to be smaller than or equal to.
     * @param string $bcode If passed the users' bonuscode needs to be equal to this value.
     * @param string $country If passed the users' country needs to be equal to this value.
     * @param string $currency If passed the users' currency needs to be equal to this value.
     * @param string $group_by If passed it will override the default year and month (ym) grouping.
     *
     * @return array The result array with stat rows.
     */
    function lifetimeRevStats($sreg, $ereg, $splay, $eplay, $bcode = '', $country = '', $currency = '', $group_by = ''){
        $where_bcode      = empty($bcode)    ? '' : "AND u.bonus_code = '$bcode'";
        $where_country    = empty($country)  ? '' : "AND u.country = '$country'";
        if(empty($currency)){
            $in_cur_join    = $this->leftJoinCurrencies('us');
            $extras 	      = "SUM(us.gross / cur.multiplier) AS gross, SUM(us.site_prof / cur.multiplier) AS site_prof2";
            $num_cols       = phive('SQL')->makeSums($this->casinoStatsCols(), " / cur.multiplier");
        }else{
            $where_currency = "AND us.currency = '$currency'";
            $extras         = "SUM(us.gross) AS gross, SUM(us.site_prof) AS site_prof2";
            $num_cols       = phive('SQL')->makeSums($this->casinoStatsCols());
        }

        $group_by = empty($group_by) ? 'ym' : $group_by;

        $str = "SELECT DATE_FORMAT(us.date, '%Y-%m') AS ym, us.user_id, $num_cols, $extras, DATE(u.register_date) AS register_date, u.bonus_code, u.country
            FROM users_daily_stats us
            INNER JOIN users AS u ON u.id = us.user_id AND DATE(u.register_date) >= '$sreg' AND DATE(u.register_date) <= '$ereg' $where_bcode $where_country
            $in_cur_join
            WHERE us.date >= '$splay' AND us.date <= '$eplay'
            $where_currency
            GROUP BY $group_by
            ORDER BY ym";
        return phive('SQL')->readOnly()->shs('merge', 'ym', 'asc', 'users_daily_stats')->loadArray($str);
    }

  /**
   * Removes some limiting settings that might be in effect when the user deposits money.
   *
   * @param DBUser $user The user object.
   *
   * @return null
   */
    function resetOnDeposit($user){
        $user->deleteSettings('bonus_block', 'monthly-week3-num', 'monthly-week2-num', 'deposit-freeroll-num');
    }

    /**
     * This method is called to display the earned cashback as a notifcation and potentially in the global event feed as soon as a game session
     * is ended.
     *
     * @uses DBUserHandler::doNotification() in order to send the notification.
     *
     * @param int $delay Delay in seconds, a delay might be needed in order to let the currently requested page load properly.
     * @param string $gid The game id of the game that was played.
     * @param float $loyalty The loyalty / cashback amount earned during the session.
     * @param int $uid The id of the user.
     *
     * @return null
     */
    function earnedCashback($delay = 0, $gid = '', $loyalty = '', $uid = ''){
        if(empty($uid)) {
            $uid = $_SESSION['mg_id'];
        }
        if(empty($uid)) {
            return;
        }
        $ud = empty($_SESSION['local_usr']) ? cu($uid)->data : $_SESSION['local_usr'];
        $loyalty = empty($loyalty) ? phMget(mKey($uid, 'earned-loyalty')) / 100 : $loyalty;
        if(empty($loyalty) || $loyalty < 1) {
            return;
        }
        $game = phive('MicroGames')->getByGameId($gid);

        if (!empty($game) && !$game['retired'] && $game['active'] && $game['enabled'] ) {
            if(empty($delay)) {
                phive('UserHandler')->doNotification('earnedcashback', $ud, '', $loyalty, $game['game_name'], $game['game_id']);
            } else {
                phMset(mKey($uid, 'earned-loyalty'), 0);
                phive()->pexec('UserHandler', 'earnedCashback', array(0, $game['game_id'], $loyalty, $uid), $delay * 1000000);
                return;
            }
            if($loyalty > 10) {
                uEvent('earnedcashback', $loyalty, $game['game_name'], $game['game_id'], $uid);
            }
        }

        phMset(mKey($uid, 'earned-loyalty'), 0);
    }

    /**
     * The persistent storage of notifications is cleared out every day, typically we get rid of
     * notifications older than 14 days.
     *
     * TODO henrik refactor to use delete batched.
     *
     * @param string $date The cutoff date to use in order to delete older notifications than that date.
     *
     * @return null
     */
    function clearOldNotifications($date = null){
        $date = empty($date) ? phive()->hisMod('-14 day') : $date;
        phive('SQL')->shs()->query("DELETE FROM users_notifications WHERE created_at < '$date'");
    }

    /**
     * Sends a websocket notification to a user.
     *
     * @param string $tag The type of notification, is used to display correct icon etc.
     * @param array $ud User data / row.
     * @param array $ev_arr An array of information needed in order to display the notification properly as well as in the
     * event feed in case the user has allowed event feed display.
     * @param int $amount The amount in case the notification is related to a monetary amount.
     * @param string $name The game name in case the notification is related to a game.
     * @param string $url A URL, eg the play URL of the game in case the notification is related to a game.
     * @param string $img Potential image override, if empty we try and display image depending on context.
     *
     * @return null
     */
    function doNotification($tag, $ud, $ev_arr = '', $amount = '', $name = '', $url = '', $img = ''){
        if($this->getSetting('has_notifications') !== true)
            return;
        if(!licSetting('enable_legacy_booster', $ud) && in_array($tag, ['awardedcashback', 'earnedcashback', 'transfervault'])) {
            return;
        }
        $ev_arr = empty($ev_arr) ? $this->getEvArr($tag, $ud, $amount, $name, $url, $img) : $ev_arr;
        $json = json_encode($ev_arr);
        unset($ev_arr['fname']);
        $ev_arr['user_id'] = $ud['id'];
        phive('SQL')->sh($ud, 'id', 'users_notifications')->insertArray('users_notifications', $ev_arr);
        $s = $this->getRawSetting($ud['id'], 'show_notifications');
        if($s['value'] !== '0'){
            phM('lpush', mKey($ud['id'], 'events'), $json, 18000);
            $ev_obj = (object)$ev_arr;
            toWs(array('img' => $this->eventImage($ev_obj, true, $img), 'str' => $this->eventString($ev_obj, 'you.', $ud['preferred_lang'])), 'notifications', $ud['id']);
        }
    }

    /**
     * Gets an array with information that is needed for displaying events and notifications correctly.
     *
     * @param string $tag The event / notification tag / type.
     * @param array $ud User data / row.
     * @param int $amount Monetary amount if applicable.
     * @param string $name The game name in case the notification is related to a game.
     * @param string $url A URL, eg the play URL of the game in case the notification is related to a game.
     * @param string $img Potential image override, if empty we try and display image depending on context.
     *
     * @return array The result array.
     */
    function getEvArr($tag, $ud, $amount = '', $name = '', $url = '', $img = ''){
        if(is_array($name))
            $name = phive()->toDualStr($name);
        $hide = $this->getRawSetting($ud['id'], 'privacy-pinfo-hidealias');
        $hide = $hide ? (int)$hide['value'] == 1 : false;
        return array('fname' => $hide ? 'Anonymous' . base_convert($ud['id'], 10, 9) : $ud['firstname'], 'country' => $ud['country'], 'tag' => $tag, 'amount' => $amount, 'name' => $name, 'url' => $url, 'currency' => $ud['currency'], 'img' => $img);
    }

    // TODO henrik remove
    function getNotificationRules() {
        return array(
            'A' => 'Deposit amount in EUR cents in 24 hours larger than ',
            'B' => 'Win amount in EUR cents in 1 spin larger than ',
            'C' => 'Cash balance in EUR cents goes above '
        );
    }

    // TODO henrik remove
    function getNotificationKey($i) {
        $array = array(
            'A' => 'deposit_amount_24h',
            'B' => 'win_amount_one_spin',
            'C' => 'cash_balance_over_x_amount'
        );
        return $array[$i];
    }

    /**
     * Pushes a websocket notification / event and depending on both user settings and the $show_in_feed argument
     * we also show the notification / event in the public event feed.
     *
     * @param string $tag The event / notification tag / type.
     * @param int $amount Monetary amount if applicable.
     * @param string $name The game name in case the notification is related to a game.
     * @param string $url A URL, eg the play URL of the game in case the notification is related to a game.
     * @param array $ud User data / row.
     * @param string $img Potential image override, if empty we try and display image depending on context.
     * @param bool $show_in_feed If the event / notification should show in the public news / events feed or not.
     *
     * @return null
     */
    function addEvent($tag, $amount = '', $name = '', $url = '', $ud = array(), $img = '', $show_in_feed = true){
        $ud = ud($ud);
        if(empty($ud))
            return;
        if(empty($_SESSION['mg_id']))
            setCur($ud['currency']);
        $s = $this->getRawSetting($ud['id'], 'show_in_events');
        $time = time();
        $ev_arr = $this->getEvArr($tag, $ud, $amount, $name, $url, $img);
        $json = json_encode($ev_arr);
        if($s['value'] !== '0' && $show_in_feed){
            if((int)getCur()['legacy'] === 0)
                phM('lpush', "events", $json, 18000);
        }
        $map = array('climbedlevel', 'trophyreward', 'usereward', 'trophyaward', 'advancedinrace',
                     'awardedcashback', 'awardedracepayout', 'bonuspayout', 'activatedbonus', 'freespins',
                     'mpbuyin', 'mpstart', 'mpwin', 'transfervault');
        if(in_array($tag, $map))
            $this->doNotification($tag, $ud, $ev_arr);
    }

    /**
     * Used to create a JS websocket, used with the global doWs() JS function.
     * @param string $tag The channel name / tag.
     * @param bool $do_sid If true only notifications for this player will show for this channel (as the session id is unique per player).
     * windows displaying the site.
     * @param array $events possible values: ['open', 'close'].
     * @param string $events_handler redis key where the event handler information is stored, see DBUserHandler:addWsCloseListener.
     * @param string|null $customSid
     *
     * @return string The ws(s)://... url.
     */
    public function wsUrl($tag, $do_sid = true, $events = [], $events_handler = '', $customSid = null)
    {
        phive()->loadApi('ws');

        if(is_null($customSid)) {
            if ($do_sid) {
                $currentSessionId = session_id();
                $sid =  empty($currentSessionId) ? getSid(cu()->getId()) : $currentSessionId;
            } else {
                $sid = null;
            }
        } else {
            $sid = $customSid;
        }

        $url = Ws::wsUrl($sid, $tag);
        if (empty($events) || empty($events_handler)) {
            return $url;
        }
        $events = implode(',', $events);
        return "{$url}?events={$events}&handler={$events_handler}";
    }

    /**
     * Gets the the websocket channel name for a given tag and user
     *
     * @param string $tag
     * @param $user_id
     * @param bool $encrypted
     * @return string
     */
    public function wsChannel(string $tag, $user_id = null, $encrypted = true): string
    {
        phive()->loadApi('ws');
        $sid = is_numeric($user_id) ? getSid($user_id) : $user_id;
        $ss = phive()->getSetting('websockets');
        $e_ss = $ss[$ss['engine']];

        return $encrypted ? Ws::getHashedChannel($e_ss['secret_key'], $sid, $tag) : Ws::getChannel($e_ss['secret_key'], $sid, $tag);
    }

    /**
     * When the ws closes, ws server will send an ajax request to the lic function passed in the data parameter
     * Works similar to the javascript licJson function
     * Handy for tracking when the user leaves a page
     *
     * @param string $tag
     * @param string $lic_func
     * @param array $options
     * @param DBUser|null $user
     * @param int $max_retries
     * @param int $sleep
     * @return string
     */
    public function addWsCloseListener(string $tag, string $lic_func, array $options, DBUser $user = null, int $max_retries = 3, int $sleep = 5000000)
    {
        $options = array_merge($options, [
            'lic_func' => $lic_func,
            'country' => cuCountry($user),
            'domain'  => phive()->getSetting('full_domain'),
            'user_id' => uid($user)
        ]);
        $ws_channel = $this->wsChannel($tag, $user->getId());
        phMsetArr($ws_channel, $options);
        phive()->pexec('DBUserHandler', 'wsCreatedListener', [$ws_channel, $tag, $options, 0, $max_retries, $sleep], $sleep, $options['user_id']);
        return $ws_channel;
    }

    /**
     * Tracks that websocket with listener was created correctly, and if after 15 seconds ws wasn't created, we trigger the ws listener
     * so it can perform any queued action.
     * Necessary to handle the edge case were the websocket fails to be created when the user leaves the page to soon or some browser malfunction
     *
     * @param $ws_channel
     * @param $ws_tag
     * @param $options
     * @param int $retries
     * @param int $max_retries
     * @param int $sleep
     */
    public function wsCreatedListener($ws_channel, $ws_tag, $options, int $retries = 0, int $max_retries = 3, int $sleep = 5000000)
    {
        $channelInfo = phMgetArr($ws_channel);

        if (!isset($channelInfo['ws_started'])) {
            if ($retries > $max_retries) {
                lic('ajax'.ucfirst($options['lic_func']), [$options], $options['user_id']);
            } else {
                $user_id = uid($channelInfo['user_id']);
                toWs(['ping' => true], $ws_tag, $user_id);
                phive()->pexec('DBUserHandler', 'wsCreatedListener', [$ws_channel, $ws_tag, $options, $retries + 1, $max_retries, $sleep], $sleep, $user_id);
            }
        }
    }

    /**
     * Gets the latest notifications for a specific user from the persistent database.
     *
     * TODO henrik refactor to use loadArray() instead so SQL::loadObjects() can be removed.
     *
     * @param int $uid The user id.
     * @param int $limit The amount of notifications to get.
     *
     * @return array An array of objects where each object is a notification.
     */
    function getLatestNotifications($uid, $limit = 4, $offset = 0){
        $limit = intval($limit);
        $uid = empty($uid) ? $_SESSION['mg_id'] : $uid;
        if(empty($uid)){
            return [];
        }
        $uid = intval($uid);
        $str = "SELECT un.*, u.currency FROM users_notifications un, users u WHERE un.user_id = $uid AND un.user_id = u.id ORDER BY un.created_at DESC LIMIT $offset,$limit";
        return phive('SQL')->sh($uid, '', 'users')->loadObjects($str);
    }

    /**
     * Gets the notification count after a certain timestamp for s certain user.
     *
     * @param string $stamp The timestampl
     * @param int $uid The user id.
     *
     * @return int The count.
     */
    function getNotificationCountSince($stamp, $uid = null){
        $uid = intval($uid);
        $uid = empty($uid) ? $_SESSION['mg_id'] : $uid;
        if(empty($uid)){
            return;
        }
        $str = "SELECT COUNT(*) FROM users_notifications WHERE created_at > '$stamp' AND user_id = $uid";
        return (int)phive('SQL')->sh($uid, '', 'users_notifications')->getValue($str);
    }

    /**
     * Gets all notifications from the Redis cached event feed for a certain user.
     *
     * @param bool $decode Whether or not to decdode the JSON.
     * @param int $uid The user id.
     *
     * @return array The result array, with each element either containing JSON or being a sub array.
     */
    function getNotifications($decode = true, $uid = ''){
        $uid = empty($uid) ? $_SESSION['mg_id'] : $uid;
        if(empty($uid)){
            return [];
        }
        $arr = phM('lrange', mKey($uid, 'events'), 0, -1);
        phM('ltrim', mKey($uid, 'events'), 1, 0);
        if($decode){
            return array_map('json_decode', $arr);
        }
        return $arr;
    }

    /**
     * Used to fix the display of complicated first names, ex: czesław john becomes Czesław John.
     *
     * @param $fname The first name.
     *
     * @return string The fit-for-display firstname.
     */
    function fixFname($fname){
        $fname = str_replace("&amp;#39;", '\'', $fname);
        $fname = mb_convert_case(mb_strtolower($fname), MB_CASE_TITLE);
        foreach(array('-', ' ') as $delim){
            $arr = explode($delim, $fname);
            if(count($arr) == 1)
                continue;
            array_walk($arr, function(&$str){ $str = mb_convert_case(trim($str), MB_CASE_TITLE); });
            $fname = implode($delim, $arr);
            break;
        }
        return $fname;
    }

    /**
     * @param EventsRequestData $data
     * @return array
     */
    function getEvents(EventsRequestData $data): array
    {
        $data = lic('filterEvents', [array_map('json_decode', phM('lrange', 'events', $data->getStart(), $data->getOffset()))], $data->getUserId());
        return $data ?? [];
    }

    /**
     * Every event and notification contains a thumbnail image and this method is responsible for figuring out which one
     * to display for the given event / notification.
     *
     * @param object $ev The event.
     * @param bool $return Whether to return or (true) or output the image location (false).
     * @param string $img Optional image override in case the event object does not contain an image name.
     *
     * @return string The image name.
     */
    function eventImage($ev, $return = false, $img = ''){
        $tmp = phive('Localizer')->getCompNotificationStr($ev->name, null, false);

        if(!empty($ev->img))
            $img = $ev->img;
        else if(empty($img) && !empty($tmp))
            [$not_used, $img] = explode('.', $tmp);

        if(empty($img)){
            switch($ev->tag){
                case 'freespins':
                    $img = "{$ev->amount}_freespins";
                    break;
                case 'woningame':
                    $img = "woningame{$this->getWonEventLvl($ev)}";
                    break;
                default;
                    $img = $ev->tag;
                    break;
            }
        }

        return fupUri("events/{$img}_event.png", $return);
    }

    /**
     * Gets a string used with Localizer in order to describe the event in an arbitrary amount of languages.
     *
     * @param object $ev The event object.
     * @param string $prefix A prefix that will decide the context, eg **you.**climbedlevel for the notification description
     * and perhaps **feed.**climbedlevel for the event feed.
     * @param string $lang Optional ISO2 language code to use, if null the currently selected language will be used.
     *
     * @return string The string that described the event / notification.
     */
    function eventString($ev, $prefix = '', $lang = null){
        $alias = $ev->tag == 'woningame' ? "woningame{$this->getWonEventLvl($ev)}.event" : "{$ev->tag}.event";
        $str  = $this->loc->getString($prefix.$alias, $lang);

        $tags_with_integer_amount = ['freespins', 'advancedinrace', 'climbedlevel', 'finishedclash'];

        if(!in_array($ev->tag, $tags_with_integer_amount) && is_numeric($ev->amount)){
            $ev->amount /= 100;
            $ev->amount = round($ev->amount, 2);
        }

        $tmp  = (array)$ev;
        return rep($this->loc->replaceAssoc($tmp, $str, $lang));
    }

    /**
     * Typically run in a per minute cron job to prune the length of the in-memory event feed and keep only the 99
     * most recent events.
     *
     * @param int $cnt The amount of events to keep.
     *
     * @return null
     */
    function trimEvents($cnt = 99, $logId = "na"){
        try {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::trimEvents start " . $logId);
            phM('ltrim', 'events', 0, $cnt);
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::trimEvents end " . $logId);
        } catch (\Throwable $e) {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::trimEvents " . $logId, [$e]);
        }
    }

    /**
     * Special logic for handling FRB events.
     *
     * @param int $num The number of spins that were awarded, won etc.
     * @param int $uid User id, current logged in user will be used if omitted.
     * @param string $game_id The game the FRB pertains to.
     *
     * @return null Note that if the game can not be found or the spins are not in the $show array we do nothing.
     */
    function fspinEvent($num, $uid = '', $game_id = ''){
        if(empty($num) || empty($game_id))
            return;
        $game = phive('MicroGames')->getByGameId($game_id);
        if(empty($game))
            return;
        $show = array(5, 10, 11, 15, 25, 20, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 150, 200);
        if(!in_array($num, $show))
            return;
        $this->addEvent("freespins", $num, $game['game_name'], $game_id, $uid);
    }

    /**
     * This logic determines which image and content to display in case of a big win, it is basically just a mapping
     * between certain amounts (in the casino default currency) and levels so eg a 10000 EUR win results in the display
     * of a level 9 event and between 5k and 10k results in a level 8 event.
     *
     * @param object $ev The event.
     *
     * @return int The level.
     */
    function getWonEventLvl($ev){
        $amount = mc($ev->amount, $ev->currency, 'div');
        $levels = array(200 => 1, 300 => 2, 500 => 3, 1000 => 4, 2500 => 5, 5000 => 6, 10000 => 7, 50000 => 8, 100000 => 9);
        return phive()->getLvl($amount, $levels, 10);
    }

    /**
     * Display helper, will return the translated error in case a string alias is passed in but simply return the error
     * in case an already translated and ready for display error content is passed in.
     *
     * @param string $err The error alias or error content.
     * @param string $start Prefix to add to the error alias eg username becomes register.err.username.
     *
     * @return null This method echoes the result.
     */
    function prErr($err, $start = 'register.err.'){
        if(!empty($err)){
            if(strpos($err, ' ') === false)
                echo t($start.$err);
            else
                echo $err;
        }
    }

    /**
     * Checks if a users account is the same as another's account.
     *
     * @param array $u1 The player we want to check for similarity against an already existing player with those attributes.
     * @param array $u2 The other player.
     *
     * @return boolean True if same, false otherwise.
     */
    function isSame($u1, $u2)
    {
        if($u1['password'] == $u2['password'] && $u1['id'] != $u2['id'] && $u1['country'] == $u2['country'] && $u1['sex'] == $u2['sex'] && $u1['dob'] == $u2['dob']) {
            $this->logAction($u1, "Too similar to {$u2['username']} (is same)");
            cu($u1)->addComment("Too similar to {$u2['username']} (is same)", 0);
            return true;
        }

        return false;
    }

    /**
     * Checks for similar accounts using the levenshtein distance.
     *
     * @param array $u1 The player we want to check for similarity against an already existing player with those attributes.
     * @param array $u2 The other player.
     * @param int $thold The levenshtein threshold distance to use.
     *
     * @return boolean True if similar, false otherwise.
     */
    function isSimilar($u1, $u2, $thold = 0)
    {
        // The same player can not be similar to himself.
        if($u1['id'] == $u2['id'])
            return false;

        $ld_fields = ['email', 'lastname', 'firstname', 'address', 'city', 'zipcode', 'username'];

        $ld = 0;
        foreach ($ld_fields as $field) {
            $ld += levenshtein(strtolower($u1[$field]), strtolower($u2[$field]));
        }

        if ($ld <= $thold) {
            return true;
        }
        return false;
    }

    /**
     * Checks for similar accounts.
     *
     * Creates normalized strings that consist of the user attributes from test cases and compares them.
     * This method will log both an action and a user comment if a match is found.
     *
     * @uses Phive::fold()
     * @see Phive::fold()
     * @param array $u1 The player we want to check for similarity against an already existing player with those attributes.
     * @param array $u2 The other player.
     *
     * @return bool True is too similar, false otherwise.
     */
    function isAlmostSame($u1, $u2){

        // The same player can not be similar to himself.
        if($u1['id'] == $u2['id'])
            return false;

        foreach(self::USER_SIMILARIRY_TEST_CASES as $test_case_array){
            $u1_str = '';
            $u2_str = '';

            //build fold string
            foreach ($test_case_array as $user_attr) {
                $u1_str .= phive()->fold($u1[$user_attr]);
                $u2_str .= phive()->fold($u2[$user_attr]);
            }

            if($u1_str == $u2_str){
                $this->logAction($u1, "Too similar to {$u2['username']} (almost same)");
                cu($u1)->addComment("Too similar to {$u2['username']}", 0);
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for similar users.
     *
     * Used in the registration process to block attempts to register duplicate accounts in order to for instance
     * try and abuse the welcome bonus.
     *
     * First we check against exact match, we do that by checking if the passwords used are exactly the same and the country and sex etc are the same too.
     *
     * Secondly we check for almost the same using {@see DBUserHandler::isAlmostSame() isAlmostSame()}.
     *
     * Finally we check the levenshtein distance using some select fields / columns {@see DBUserHandler::isSimilar() isSimilar()}.
     *
     * @see DBUserHandler::isSame()
     * @see DBUserHandler::isAlmostSame()
     * @see DBUserHandler::isSimilar()
     *
     * @param array $ud    The new player to check.
     * @param int   $thold The levenshtein threshold distance to use.
     * @param array $users The users to check against.
     *
     * @return array The result, the similar users in the first position of the array and the non-similar users in the second position.
     */
    function checkSimilar($ud, $thold = 0, $users = '')
    {
        $old_ids = cu($ud['id'])->getPreviousCurrencyUserIds();
        // Will only catch people who are not changing their dob, country or sex between regs.
        $users = empty($users) ? phive('SQL')->shs('merge', '', null, 'users')->arrayWhere('users', array('country' => $ud['country'], 'sex' => $ud['sex'], 'dob' => $ud['dob'])) : $users;
        $res = array();
        $countUsers = count($users);
        for ($i = 0; $i < $countUsers; $i++) {
            $u = $users[$i];

            // TODO Remove using isUserAccountClose function and change the function name to isUserAccountClosed in IT.php
            if(
                $ud['id'] != $u['id'] &&
                !in_array($u['id'], $old_ids) &&
                !lic('isUserAccountClose', [$u], $u) &&
                !lic('isUserAccountClosed', [$u], $u)
            ){

                if ($this->isSame($ud, $u)) {
                    $res[] = array('user' => $u, 'ld' => $ld, 'pwd' => 'Yes', 'descr' => 'is.same');
                    unset($users[$i]);
                    continue;
                }

                if ($this->isAlmostSame($ud, $u)) {
                    $res[] = array('user' => $u, 'ld' => $ld, 'pwd' => 'No', 'descr' => 'almost.same');
                    unset($users[$i]);
                    continue;
                }

                if ($this->isSimilar($ud, $u, $thold)) {
                    $res[] = array('user' => $u, 'ld' => $ld, 'pwd' => 'No', 'descr' => 'lev.distance');
                    unset($users[$i]);
                    continue;
                }

            }else
                unset($users[$i]);
        }
        return array($res, $users);
    }

    /**
     * This cron job logic that will DELETE settings based on the setting key and its value, if the value represents
     * a value that is older than the timeout the setting will be deleted.
     *
     * @param string $key The setting.
     * @param string $timeout The timeout value.
     *
     * @return null
     */
    function clearOutDateSetting($key, $timeout = ''){
        $timeout = empty($timeout) ? phive()->hisNow() : $timeout;
        phive("SQL")->shs('', '', null, 'users_settings')->query("DELETE FROM users_settings WHERE setting = '$key' AND value <= '$timeout'");
    }

    /**
     * A method that will return the user's total cash, both real money and bonus money.
     *
     * @param DBUser|int $user User object or id.
     * @param bool $bonus_too Whether ot not to add the bonus money.
     *
     * @return int The total.
     */
    function userTotalCash($user, $bonus_too = false){
        if(is_numeric($user))
            $user = cu($user);
        if(!is_object($user))
            return false;
        $mg = phive('QuickFire');
        $cash = $user->getAttr('cash_balance');
        if($bonus_too)
            $cash += phive('Bonuses')->getBalanceByUser($user);
        return $cash;
    }

    /**
     * SQL builder helper that will return misc common stat columns.
     *
     * @return array The columns.
     */
    function casinoMpStatsCols(){
        return array('us.bets', 'us.wins', 'us.jp_contrib', 'us.gross', 'us.house_fee', 'us.rewards', 'us.mp_adj', 'us.tax', 'us.op_fee', 'us.site_rev', 'us.freeroll_cost', 'us.freeroll_recovered',
                     'us.free_pot_cost', 'us.guaranteed_cost', 'us.cb', 'us.win_sum', 'us.rebuy_sum', 'us.buyin_sum');
    }

    /**
     * A wrapper around DBUserHandler::casinoMpStatsCols() that will strip the table alias before returning the columns.
     *
     * @uses DBUserHandler::casinoMpStatsCols()
     *
     * @return array The columns.
     */
    function casinoMpStatsNumCols(){
        return str_replace('us.', '', $this->casinoMpStatsCols());
    }

    /**
     * SQL builder helper that will return misc common stat columns.
     *
     * @return array The columns.
     */
    function casinoStatsCols(){
        return array('us.ndeposits', 'us.nwithdrawals', 'us.nbusts', 'us.deposits', 'us.withdrawals', 'us.bets', 'us.mp_adj', 'us.tax', 'us.wins', 'us.jp_contrib', 'us.frb_ded', 'us.rewards', 'us.fails', 'us.bank_fee',
                     'us.op_fee', 'us.aff_fee', 'us.site_rev', 'us.bank_deductions', 'us.jp_fee', 'us.real_aff_fee', 'us.site_prof', 'us.gen_loyalty', 'us.paid_loyalty',
                     'us.frb_wins', 'us.frb_cost', 'us.tax_deduction');
    }

    /**
     * SQL builder helper that will return misc common stat columns.
     *
     * @return array The columns.
     */
    function casinoStatsNumCols(){
        return array('deposits', 'withdrawals', 'bets', 'wins', 'jp_contrib', 'gross', 'mp_adj', 'tax', 'frb_cost', 'rewards', 'fails', 'paid_loyalty', 'bank_fee', 'op_fee', 'real_aff_fee', 'bank_deductions', 'site_prof');
    }

    /**
     * SQL builder helper that will return misc common stat columns.
     *
     * @return array The columns.
     */
    function casinoAllStatsNumCols(){
        return array('deposits', 'withdrawals', 'bets', 'wins', 'jp_contrib', 'gross', 'mp_adj', 'tax', 'frb_cost', 'rewards', 'fails', 'paid_loyalty', 'bank_fee', 'op_fee', 'real_aff_fee', 'bank_deductions', 'ndeposits', 'nwithdrawals', 'nbusts', 'frb_wins', 'jp_fee', 'frb_ded', 'chargebacks', 'transfer_fees', 'site_prof', 'gen_loyalty', 'before_deal', 'site_rev');
    }

    /**
     * GUI helper that will return common stats headlines.
     *
     * @return array The headlines.
     */
    function casinoStatsMpHeadlines(){
        return array('Bets', 'Wins', 'Jp Ded.', 'Gross', 'House Rake', 'Rewards', 'Adj.', 'Tax', 'Op. Fees', 'Site Rev.', 'Froll Cost', 'Froll Rec.', 'Free Pot Cost', 'Gteed Cost', 'CB', 'Wins', 'Rebuys', 'Buy-ins', 'B. ID');
    }

    /**
     * GUI helper that will return common stats headlines.
     *
     * @return array The headlines.
     */
    function casinoStatsHeadlines(){
        return array('Deposits', 'Withdrawals', 'Bets', 'Wins', 'Jp Ded.', 'Gross', 'Adj.', 'Tax', 'FRB Cost', 'Act. Tot.', 'Fail Tot.', 'Loyalty', 'Bank Fees',
                     'Op. Fees', 'Aff. Prof.', 'Deductions', 'Site. Prof.');
    }

    /**
     * This method sums all game sessions in a certain timespan for a certain user.
     *
     * @param int $uid The user id.
     * @param string $stime Start stamp of period.
     * @param string $etime End stamp of period.
     * @param string $group_by Group by column.
     *
     * @return array The summed up sessions, in case we group by user id the array will be one dimensional.
     */
    function sumGameSessions($uid, $stime, $etime = '', $group_by = 'user_id'){
        $uid    = (int)$uid;
        $etime  = empty($etime) ? phive()->hisNow() : $etime;
        $str    = "SELECT SUM(bet_amount) AS bet_amount, SUM(win_amount) AS win_amount, SUM(result_amount) AS result_amount, game_ref FROM users_game_sessions WHERE (end_time >= '$stime' OR end_time = '00-00-00 00:00:00') AND start_time <= '$etime' AND user_id = $uid GROUP BY $group_by";
        $method = $group_by == 'user_id' ? 'loadAssoc' : 'loadArray';
        return phive('SQL')->sh($uid)->$method($str);
    }

    /**
     * Will sum up all games sessions for a user that belong to one of that user's login sessions.
     *
     * @param int $uid The user id.
     * @param int $session_id The session id.
     *
     * @return array The summed up columns.
     */
    public function sumGameSessionsBySessionId($uid, $session_id) {
        $str  = "SELECT SUM(bet_amount) AS bet_amount, SUM(win_amount) AS win_amount, SUM(result_amount) AS result_amount FROM users_game_sessions WHERE user_id = {$uid} AND session_id = {$session_id}";
        return phive('SQL')->sh($uid, '', 'users_game_sessions')->loadAssoc($str);
    }

    /**
     * Gets the game associated to a game session by session id
     *
     * @param int $user_id
     * @param int $users_game_session_id the id column of table users_game_session
     * @return array|false|string The game if found
     */
    public function getGameFromGameSessionId(int $user_id, int $users_game_session_id): array
    {
        $str = "SELECT game.* FROM users_game_sessions ugs, micro_games game WHERE ugs.id = {$users_game_session_id} AND game.ext_game_name = ugs.game_ref AND game.device_type_num = ugs.device_type_num;";
        return phive('SQL')->sh($user_id)->loadAssoc($str);
    }

    // TODO henrik move this to testuser or testphive.
    function resetTestUser($username){
        $user = cu($username);
        $user->setAttr('cash_balance', 0);
        $this->purgeUserData($user->getId());
    }

    // TODO henrik move this to testuser or testphive.
    function purgeUserData($uid){
        $uid = intval($uid);
        $tbls = array('bets', 'wins', 'users_daily_stats', 'users_daily_game_stats', 'users_blocked', 'trans_log', 'cash_transactions', 'bonus_entries');
        foreach($tbls as $tbl)
            phive("SQL")->sh($uid, '', $tbl)->query("DELETE FROM $tbl WHERE user_id = $uid");
    }

    // TODO henrik remove>
    function getAllSegments() {
        return array(1 => 'Full customer', 2 => 'Highroller', 3 => 'VIP', 4 => 'Diamond');
    }

    // TODO henrik remove
    function setVipLevels(){
        if(!empty($this->vip_levels))
            return $this->vip_levels;
        foreach(array(0,1,2,3) as $level) {
            $this->vip_levels['betlevels'][$level] = phive("Config")->getValue("vip-levels", "vip-level-$level-bets");
            $this->vip_levels['deplevels'][$level] = phive("Config")->getValue("vip-levels", "vip-level-$level-deposits");
            $this->vip_levels['proflevels'][$level] = phive("Config")->getValue("vip-levels", "vip-level-$level-prof");
        }
    }

    /**
     * Gets all game sessions between to dates for a specific user.
     *
     * @param int $uid The user id.
     * @param string $stime Start stamp of period.
     * @param string $etime End stamp of period.
     * @param bool $hide_zero_wager Whether ot nor to include game sessions where zero wagers took place.
     *
     * @return array The sessions.
     */
    function getGameSessions($uid, $stime, $etime, $hide_zero_wager = false) {
        if ($hide_zero_wager) {
            $where_wager_not_zero = " AND s.bet_amount > 0 ";
        }
        $sql_str = "SELECT s.*, g.game_name
                    FROM users_game_sessions AS s
                    LEFT JOIN micro_games g ON s.game_ref = g.ext_game_name AND s.device_type_num = g.device_type_num
                    WHERE s.user_id = $uid AND s.start_time >= '$stime' AND s.start_time <= '$etime' $where_wager_not_zero";
        $sessions = phive('SQL')->sh($uid, '', 'users_game_sessions')->loadArray($sql_str);
        return $sessions;
    }

    // TODO henrik remove
    function getTopWinnersOrLosers($hours = 24, $order_by = "DESC", $limit = 10) { // order_by DESC for winners, ASC for losers
        $sd = phive()->hisMod("-{$hours} hours");
        $str =
        "SELECT u.username, u.currency, SUM(gs.win_amount) AS winsum, SUM(gs.bet_amount) AS betsum, SUM(gs.result_amount) AS ressum, SUM(gs.result_amount / cur.multiplier) AS res_eur FROM users_game_sessions gs
         LEFT JOIN users u ON gs.user_id = u.id
         LEFT JOIN currencies AS cur ON cur.code = u.currency
         WHERE gs.start_time > '$sd'
         GROUP BY gs.user_id
             ORDER BY res_eur {$order_by} LIMIT 0, $limit";

        $res = phive('SQL')->shs('merge', 'res_eur', strtolower($order_by), 'users_game_sessions')->loadArray($str);

        if ($order_by == "DESC") {
            usort($res, function ($a, $b) {
                return $b['res_eur'] - $a['res_eur'];
            });
        } else{
            usort($res, function ($a, $b) {
                return $a['res_eur'] - $b['res_eur'];
            });
        }

        return array_slice($res, 0, 25);
    }

    /**
     * Helper method for SQL generation to in turn fetch data for various stats GUIs.
     *
     * @param string $username This is misnamed, should really be called $group_by.
     * @param array $num_cols Numerical columns to sum up.
     * @param mixed $where Currently just a pass through, not used or manipulated.
     * @param bool $join_users Also pass through but might change before returned in the result array.
     * @param string $order_by What to order on, pass through but might change before returned.
     * @param string $gross The gross profit column, can change before being returned.
     *
     * @return array The result array with all the components, some new, some unchanged and some changed as compared to the function arguments.
     */
    function getCasinoStatsGroup($username, $num_cols, $where, $join_users, $order_by, $gross){
        if(!empty($username) && in_array($username, array('us.user_id','country', 'sex', 'verified_phone', 'bonus_code', 'city'))){
            $join_users       = true;
            $num_cols 	= phive('SQL')->makeSums($num_cols);
            if ($username == 'us.user_id') {
                $group_by = "GROUP BY $username";
                $group_by2 = 'user_id';
            } else{
                $group_by 	= "GROUP BY u.$username";
                $group_by2 	= $username;
            }
        }else{
            if(empty($username)){
                $group_by 	= " GROUP BY us.username ";
                $num_cols 	= phive('SQL')->makeSums($num_cols);
                if(empty($order_by))
                    $order_by 	= 'us.site_prof';
            }
            else if($username == 'us.country') {
                $num_cols 	= phive('SQL')->makeSums($num_cols);
                $gross = 'SUM(us.gross) AS gross';
                $group_by = "GROUP BY $username";
                $group_by2 = 'country';
                $join_users       = true;
            }
            else if($username == 'week') {
                $num_cols       = implode(',', $num_cols);
                $select         = ', WEEKOFYEAR(date) AS week_num';
                $group_by2      = 'week_num';
                $group_by       = 'GROUP BY week_num';
            }
            else if($username == 'month'){
                $num_cols 	= phive('SQL')->makeSums($num_cols);
                $select 	= ', MONTH(us.date) AS month_num';
                $group_by 	= 'GROUP BY month_num';
                $group_by2 	= 'month_num';
            }else if($username == 'day_date'){
                $group_by 	= " GROUP BY us.date ";
                $num_cols 	= phive('SQL')->makeSums($num_cols);
                $group_by2 	= 'date';
            }else if($username == 'day'){
                $num_cols 	= implode(',', $num_cols);
                $select 	= ', DAYOFMONTH(date) AS day_num';
                $group_by2 	= 'day_num';
                $group_by 	= 'GROUP BY day_num';
            }else if($username == 'currency'){
                $num_cols 	= phive('SQL')->makeSums($num_cols);
                $group_by2 	= 'currency';
                $group_by 	= 'GROUP BY currency';
                $gross 		= "us.currency, SUM(us.gross) AS gross";
                $where		= '';
            }else{
                $num_cols 	= implode(',', $num_cols);
                //$gross        = "SUM(us.gross) AS gross";
                $gross 		= "us.gross";
            }
        }

        return array($select, $num_cols, $gross, $group_by, $group_by2, $order_by, $join_users, $where);

    }

    /**
     * Initialization logic for building the SQL needed for various statistics views.
     *
     * @param string $username User username in case we want stats for only a specific user.
     * @param string $col_func Function to be called in order to get the columns to display.
     * @param string $gross_extra Extra columns to sum up.
     * @param string $order_by ORDER BY column.
     * @param int $limit The length / count part in the LIMIT statement, no limit statement will be created if this arguemnt is empty.
     * @param string $order_type Asc or desc.
     * @param string $cur ISO2 code in case we want to filter on a certain currency.
     * @param string $in_cur ISO2 code for currency we want to FX the result to, we might for instance want to display SE stats (which contains a lot of SEK) in EUR.
     *
     * @return array An array with mostly SQL sub statements to be subsequently used in creating a complete SQL statement.
     */
    function initCasinoStatsGet($username, $col_func = 'casinoStatsCols', $gross_extra = '', $order_by = '', $limit = '', $order_type = '', $cur = '', $in_cur = ''){
        if(!in_array($username, array('day_date', 'day', 'week', 'month', 'country', 'sex', 'verified_phone', 'bonus_code', 'city', 'us.country', 'us.user_id'))){
            $where = empty($username) ? '' : " AND us.username = '$username' ";
        }

        $gross      = "us.currency, SUM(us.gross) AS gross, SUM(us.gen_loyalty) AS generated_loyalty $gross_extra";
        $num_cols 	= $orig_cols = $this->$col_func();
        $order_by 	= empty($order_by) 	? 'us.date' : $order_by;
        $limit	= empty($limit) 	? '' 	    : "LIMIT 0,$limit";
        $order_type	= empty($order_type) 	? 'DESC'    : strtoupper($order_type);
        if(empty($where)){
            if(!empty($in_cur) && empty($cur))
                $in_cur_join = $this->leftJoinCurrencies('us');
                //$in_cur_join = "LEFT JOIN currencies AS cur ON cur.code = us.currency";
            $where_cur 	= empty($cur) ? '' : "AND us.currency = '$cur'";
        }
        return array($where, $gross, $num_cols, $orig_cols, $order_by, $limit, $order_type, $in_cur_join, $where_cur);
    }


    /**
     * SQL builder helper that finalizes sub queries such as a potential FX join and a potential join on the users table.
     *
     * @param string &$select Main SELECT statement passed in as a reference so will be changed in place.
     * @param string &$join Main JOIN statement passed in as a reference so will be changed in place.
     * @param array $num_cols The money columns which will be modified if FX is needed.
     * @param string $gross The gross revenue number, will be modified in case FX is needed, otherwise it is a pass-through.
     * @param string $in_cur ISO2 currency code for the currency we want to FX all numbers to.
     * @param string $cur ISO2 code for a currency filter, if present we don't do FX.
     * @param string $where WHERE filters, if present we don't do FX.
     * @param bool $join_users True if we want to join the users table, false otherwise.
     * @param array $orig_cols Money columns that will be used for FX in case we end up doing FX.
     *
     * @return array The modified number columns and gross revenue select statement.
     */
    function casinoStatsEnd(&$select, &$join, $num_cols, $gross, $in_cur, $cur, $where, $join_users, $orig_cols){
        if(!empty($in_cur) && empty($cur) && empty($where)){
            $num_cols 	= phive('SQL')->makeSums($orig_cols, " / cur.multiplier");
            $gross 		= "SUM(us.gross / cur.multiplier) AS gross";
        }

        if($join_users){
            $select  .= " , u.* ";
            $join    .= " LEFT JOIN users AS u ON u.id = us.user_id ";
        }

        return array($num_cols, $gross);
    }

    /**
     * SQL builder to get BoS / mp / tournament stats.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $username Username if we want for a specific user.
     * @param string $where_extra Extra WHERE filters.
     * @param string $order_by Order by column.
     * @param string $order_type Desc / asc.
     * @param int $limit Limit length / count.
     * @param string $cur ISO2 currency code to get for a specific currency.
     * @param bool $join_users Whether or not to join the users table.
     * @param string $in_cur ISO2 currency code to FX / display in a specific currency.
     * @param string $category Optional filter on BoS category.
     * @param string $prize_type Optional filter on BoS prize type.
     * @param string $network Optional GP network to filter on.
     *
     * @return array The result array with stats.
     */
    function getCasinoStatsMp($sdate, $edate, $username = '', $where_extra = '', $order_by = '', $order_type = '', $limit = '', $cur = '', $join_users = false, $in_cur = '', $category = '', $prize_type = '', $network = '', $join_province = false){
        [$where, $gross, $num_cols, $orig_cols, $order_by, $limit, $order_type, $in_cur_join, $where_cur] = $this->initCasinoStatsGet($username, 'casinoMpStatsCols', '', $order_by, $limit, $order_type, $cur, $in_cur);
        [$select, $num_cols, $gross, $group_by, $group_by2, $order_by, $join_users, $where]               = $this->getCasinoStatsGroup($username, $num_cols, $where, $join_users, $order_by, $gross);
        $join = '';
        [$num_cols, $gross] = $this->casinoStatsEnd($select, $join, $num_cols, $gross, $in_cur, $cur, $where, $join_users, $orig_cols);

        if(!empty($category) || !empty($prize_type) || !empty($network)){
            $join_mps   = "INNER JOIN tournaments AS mp ON us.t_id = mp.id";
            $select_mps = ", mp.category, mp.prize_type, us.t_id";
        }

        if(!empty($category) || !empty($prize_type)){
            $where_mps  = '';
            $where_mps .= empty($category) ? '' : "AND mp.category = '$category'";
            $where_mps .= empty($prize_type) ? '' : " AND mp.prize_type = '$prize_type'";
            $group_by   = "GROUP BY us.t_id";
            $group_by2  = null;
        }

        if(!empty($network)){
            $select_mps .= ", mp.game_ref, mg.network ";
            $join_mps .= " INNER JOIN micro_games AS mg ON mp.game_ref = mg.ext_game_name AND mg.network = '$network' AND device_type_num = 0";
        }

	    if ($join_users && !empty($join_province)) {
		    $join .= $join_province;
	    }

        $sql = "SELECT DISTINCT us.date, $gross, us.user_id, us.username, us.firstname, us.lastname, $num_cols $select $select_mps
                FROM users_daily_stats_mp us
                    $join
                    $in_cur_join
                    $join_mps
                WHERE us.date >= '$sdate'
                    AND us.date <= '$edate'
                    $where
                    $where_cur
                    $where_extra
                    $where_mps
                    $group_by
                    ORDER BY $order_by $order_type $limit";
        return phive('SQL')->readOnly()->shs()->loadArray($sql, 'ASSOC', $group_by2);
    }

    /**
     * SQL builder to get daily stats.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $username Username if we want for a specific user.
     * @param string $where_extra Extra WHERE filters.
     * @param string $order_by Order by column.
     * @param string $order_type Desc / asc.
     * @param int $limit Limit length / count.
     * @param string $cur ISO2 currency code to get for a specific currency.
     * @param bool $join_users Whether or not to join the users table.
     * @param string $in_cur ISO2 currency code to FX / display in a specific currency.
     * @param $affe_sub // TODO henrik remove
     * @param string $uds_table The stats table to query.
     *
     * @return array The result array with stats.
     */
    function getCasinoStats($sdate, $edate, $username = '', $where_extra = '', $order_by = '', $order_type = '', $limit = '', $cur = '', $join_users = false, $in_cur = '', $affe_sub = false, $uds_table = 'users_daily_stats', $uid = null){
        $affe_sub = false;
        [$where, $gross, $num_cols, $orig_cols, $order_by, $limit, $order_type, $in_cur_join, $where_cur] = $this->initCasinoStatsGet($username, 'casinoStatsCols', ", SUM(us.paid_loyalty) AS paid_loyalty", $order_by, $limit, $order_type, $cur, $in_cur);
        [$select, $num_cols, $gross, $group_by, $group_by2, $order_by, $join_users, $where]               = $this->getCasinoStatsGroup($username, $num_cols, $where, $join_users, $order_by, $gross);
        $join = '';

        [$num_cols, $gross] = $this->casinoStatsEnd($select, $join, $num_cols, $gross, $in_cur, $cur, $where, $join_users, $orig_cols);

        $sql = "SELECT us.date, $gross, us.user_id, us.username, us.firstname, us.lastname, $num_cols $select
                FROM $uds_table us
                    $join
                    $in_cur_join
                WHERE us.date >= '$sdate'
                    AND us.date <= '$edate' $where $where_cur $where_extra $group_by
                    ORDER BY $order_by $order_type $limit";

        if (phive('BrandedConfig')->getBrand() === phive('BrandedConfig')::BRAND_MEGARICHES) {
            $logger = phive('Logger')->getLogger('cron');
            $logger->info(sprintf('Monday cron started: query: %s, group: %s', $sql, $group_by2));
        }

        if (!empty($uid)){
            return phive('SQL')->sh($uid)->loadArray($sql, 'ASSOC', $group_by2);
        }

        //return phive('SQL')->shs('merge', $order_by, $order_type, $uds_table)->loadArray($sql, 'ASSOC', $group_by2);
        return phive('SQL')->readonly()->loadArray($sql, 'ASSOC', $group_by2);
    }

    /**
     * Unblocks a user depending on if the unlock date has passed or not.
     *
     * @return void
     */
    public function unlockLocked(): void
    {
        $user_list = phive('SQL')->shs()
            ->loadArray("SELECT * FROM users_settings WHERE setting = 'unlock-date' AND TIMESTAMPDIFF(SECOND , value, NOW()) > 1");

        foreach ($user_list as $s) {
            $user = cu($s['user_id']);

            if (is_object($user)) {
                $user_id = $user->getId();
                $users_settings = ['unlock-date', 'lock-date', 'lock-hours'];
                $user->deleteSettings($users_settings);
                phive('SQL')->delete('rg_limits', ['type' => 'lock', 'user_id' => $user_id], $user_id);

                $activate_user = true;
                $is_registration_completed = !$user->hasSetting('registration_in_progress');

                if ($user->isSuperBlocked()) {
                    $this->logAction($user, 'Cron did not activated user due to super block.', 'unlock');
                    $activate_user = false;
                } elseif (lic('isEnabledStatusTracking', [], $user) && $is_registration_completed) {
                    $status = (string) lic('getAllowedUserStatus', [$user, $users_settings], $user);

                    lic('trackUserStatusChanges', [$user, $status], $user);

                    if (!lic('isActiveStatus', [$status], $user)) {
                        $activate_user = false;
                    }
                } else if (phive('DBUserHandler')->isSelfExcluded($user)) {
                    $this->logAction($user, 'Cron did not activated user due to self exclusion.', 'unlock');
                    $activate_user = false;
                }

                if ($activate_user) {
                    $user->setAttribute('active', '1');
                    $this->logAction($user, 'Cron has activated user.', 'unlock');
                }
            }
        }
    }

    /**
     * Display helper for generating an area that displays misc. errors.
     *
     * @param array $err The errors.
     * @param bool $ret Whehter to return (true) or just output the HTML elements (alse).
     *
     * @return string The HTML in case $ret is true.
     */
    function errorZone($err, $ret = false){
        if($ret){
            ob_start();
            echo '<br clear="all">';
        }
        if(!empty($err)){
        ?>
            <div id="errorZone" class="errors">
                <?php foreach($err as $field => $e): ?>
                    <?php echo t('register.'.$field).': '; $this->prErr( $e ); ?><br>
                <?php endforeach ?>
            </div>
        <?php
        }

        if($ret){
            return ob_get_clean();
        }
    }

    /**
     * Display helper for generating an area that displays misc. errors.
     *
     * @param array $errors The errors.
     * @param bool $return Whehter to return (true) or just output the HTML elements (alse).
     *
     * @return string The HTML in case $return is true.
     */
    function errorZone2($errors, $return = false)
    {
        if ($return) {
            ob_start();
        }
        if (!empty($errors)) {
        ?>
          <div id="errorZone" class="errors">
              <?php foreach($errors as $field => $error): ?>
                  <?php echo t(str_replace('_', '.', $field)).': '; echo t($error); ?>
                  <br>
              <?php endforeach ?>
          </div>
        <?php
        }
        if ($return) {
            return ob_get_clean();
        }
      }

    /**
     * Display helper to figure out which user columns are to be displayed in the first registration form / page.
     *
     * @param string $key The column to check.
     * @return boolean True if the field should be on the first page / form, false otherwise.
     */
    function isAllowedFieldForStep1($key)
    {
        if (empty($allowed_fields = lic('validateRegFields'))) {
            $allowed_fields = [
                'password',
                'email',
                'country',
                'mobile',
                'bonus_code'
            ];
        }

        if ($this->getSetting("show_username") === true) {
            $allowed_fields[] = 'username';
        }

        return in_array($key, $allowed_fields);
    }

    /**
     * Display helper to figure out which user columns are to be displayed in the second registration form / page.
     *
     * @param string $key The column to check.
     * @param DBUser $user The current user.
     * @return boolean True if the field should be on the second page / form, false otherwise.
     */
    function isAllowedFieldForStep2($key, $user)
    {
        $allowed_fields = [
            'firstname',
            'lastname',
            'address',
            'zipcode',
            'city',
            'bonus_code',
            'dob',
            'preferred_lang',
            'currency',
            'sex',
            //'newsletter',  // removed due to consent replacing newsletter
            //'occupation',  // Uncomment when we start using this field in the form
        ];

        if($user->getAttribute('country') == 'DK') {
            $allowed_fields[] = 'personal_number'; //TODO henrik moove to lic
        }

        if(in_array($key, $allowed_fields)) {
            return true;
        }
        return false;
    }

    /**
     * This is used by the new registration, and will be called when step 1 is submitted.
     *
     * @param array $request_data
     * @return DBUser The DBUser.
     */
    function createUser($request_data) {
        $props = array();
        $pwd_field = 'password'; // removed from parameters because this method was always called without arguments

        foreach($request_data as $key => $value) {
            // check if the submitted fields are allowed
            if($this->isAllowedFieldForStep1($key)) {
                $props[$key] = $value;
            }
        }

        $props['bonus_code']        = $request_data['bonus_code'];
        $props['register_date']     = date('Y-m-d h:i:s');
        $props['mobile']            = phive('Mosms')->cleanUpNumber($request_data['country_prefix'].$request_data['mobile']);

        if(isPNP(cu($request_data['email']), $request_data['country'])){
            $props['reg_ip'] = $_SESSION['rstep1']['pnp_ip'] ?? remIp();
        } else {
            $props['reg_ip'] = remIp();
        }

        $props['last_login']        = '0000-00-00 00:00:00';

        // Override the default database value for newsletter, this needs to be 0 for all new users.
        $props['newsletter'] = 0;

        // We set the password to a uuid in case it is empty.
        $props[$pwd_field] = $this->encryptPassword( empty($request_data[$pwd_field]) ? phive()->uuid() : $request_data[$pwd_field] );

        $cols = $this->getColumns('users');

        $data = array();
        trackRegistration($props, "createUser_rawData");
        foreach($props as $key => $value){
            $key = strtolower($key);
            if(in_array($key, $cols)){
                $data[$key] = html_entity_decode(trim($value === null ? '' : $value), ENT_QUOTES|ENT_XHTML);
            }
        }

        if ($this->getSetting("show_username") !== true) {
            $data['username'] = $data['email'];
        }
        trackRegistration($data, "createUser_afterSanitization");


        // insert user into database and send welcome email
        $user_id = phive('SQL')->createUserOnNonDisabledNode($data);
        if(phive('SQL')->isSharded('users')){
            $data['id'] = $user_id;
            phive('SQL')->sh($user_id)->insertArray('users', $data);
        }

        $user = cu($user_id);

        if(phive('Config')->getValue('lga', 'reg-email') != 'yes') {
            phive('MailHandler2')->sendWelcomeMail($user);
        }

        return $user;
    }

    /**
     * This is used by the new registration, when step 2 is submitted.
     * However, it is also used by step 1 IF the user goes back to step 1 from step 2
     * in which case we allow the user to change his email or mobile number,
     *
     * @param DBUser $user The user object.
     * @param array $request_data
     *
     * @return int The user id.
     */
    function updateUser($user, $request_data, $step = 'step2') {
        $props = array();
        $fields = lic('mappingRequestFields', [$request_data]);

        $pwd_field = 'password'; // removed from parameters because this method was always password

        if (empty($fields)) {
            $fields = $request_data;
        }
        foreach($fields as $key => $value) {
            // check if the submitted fields are allowed
            if($step == 'step2' && $this->isAllowedFieldForStep2($key, $user)) {
                $props[$key] = $value;
            } elseif($step == 'step1' && in_array($key, ['email', 'mobile', 'country'])) {
                $props[$key] = $value;
            }
        }

        if(!empty($fields['mobile'])) {
            $props['mobile']        = phive('Mosms')->cleanUpNumber($fields['country_prefix'].$fields['mobile']);
        }

        if(!empty($fields[$pwd_field])) {
            $props[$pwd_field] = $this->encryptPassword($fields[$pwd_field]);
        }

        // update reg_ip, "register date should be on the day he completes first step" -> Alex
        //$props['register_date']     = date('Y-m-d h:i:s');

        //on PNP registration step1 and step2 are submitted in a same moment. No need to reset IP
        if(!isPNP()){
            $props['reg_ip'] = remIp();
        }

        if(empty($props['dob'])) {
            $props['dob'] = "{$fields['birthyear']}-{$fields['birthmonth']}-{$fields['birthdate']}";
        }

        $cols = $this->getColumns('users');

        $data = array();
        trackRegistration($props, "updateUser_rawData");
        foreach($props as $key => $value){
            $key = strtolower($key);
            if(in_array($key, $cols)){
                $data[$key] = html_entity_decode(trim($value === null ? '' : $value), ENT_QUOTES | ENT_XHTML);
            }
        }

        // update the username if the email was submited and we don't show the username in the registration form
        if (isset($fields['email']) && isset($data['email']) && strpos($user->getUsername(), '@') !== false) {
            $data['username'] = $data['email'];
        }

        // SE Users can never change email or mobile number from step 2
        $forced_currency  = lic('getForcedCurrency');
        $data['currency'] = empty($forced_currency) ? $data['currency'] : $forced_currency;

        if (empty($data['currency'])) {
            unset($data['currency']);
        }

        $forced_language = lic('getForcedLanguage');
        if (!empty($forced_language)) {
            $data['preferred_lang'] = $forced_language;
        }

        $user_id = $user->getId();
        $where   = "id = $user_id";

        trackRegistration($props, "updateUser_afterSanitization");
        $second_lastname = trim($fields['lastname_second']);
        if (!empty($second_lastname)) {
            $data['lastname'] = $data['lastname'] . ' ' . html_entity_decode($second_lastname, ENT_QUOTES|ENT_XHTML);
        }
        if (!empty($fields['personal_number'])) {
            $data['nid'] = $fields['personal_number'];
            unset($data['personal_number']);
        }

        phive('SQL')->sh($user->getId(), '', 'users')->updateArray('users', $data, $where);
        // We update the master too in case users is sharded, if not it was already updated on the line above.
        if(phive('SQL')->isSharded('users')) {
            phive('SQL')->updateArray('users', $data, $where);
        }

        // injecting user extra fields from request to user data
        foreach (lic('getUserSettingsFields') as $extra_field_name){
            $field_name = lic('shouldGetInputName', [$extra_field_name]);
            if(!empty($request_data[$field_name]) && !isset($data[$extra_field_name])) {
                $data[$extra_field_name] = $request_data[$field_name];
            }
        }

        // inject extra data for migrated users
        if($request_data['is_migration'] === 1 && $migration_fields = lic('getUserMigrationExtraFields', [$user])) {
            $data = array_merge($data, $migration_fields);
        }

        // step2 also includes data from step1 by report consistency purpose
        if($step === 'step2') {
            lic('onUserCreatedOrUpdated', [$user_id, array_merge($user->getData(), $data)]);
        }

        lic('saveExtraInformation', [$user, $fields], $user);

        return $user_id;
    }

    /**
     * This is used by the old registration, and by other actions that update the user's profile.
     * Before removing this function, we need to refactor those other actions too.
     *
     * @param bool $update Whether this is an update (true) or an insert (false).
     * @param string $pwd_field The name of the password field.
     *
     * @return int The user id.
     */
    function createUpdateUser($update = false, $pwd_field = 'password'){
        $props = array();

        foreach($_POST as $key => $value)
            $props[$key] = $value;

        if(!$update){

            $props['bonus_code']	= empty($_SESSION['affiliate']) ? $_POST['bonus_code'] : $_SESSION['affiliate'];
            $props['bonus_code']    = phive()->rmNonAlphaNums($props['bonus_code']);
            if(empty($props['dob']))
                $props['dob'] 		= "{$_POST['birthyear']}-{$_POST['birthmonth']}-{$_POST['birthdate']}";
            $props['register_date']	= date('Y-m-d h:i:s');
            $props['mobile']		= phive('Mosms')->cleanUpNumber($_POST['country_prefix'].$_POST['mobile']);
            $props['reg_ip']		= remIp();
        }

        if(!$update || !empty($_POST[$pwd_field])){
            $props[$pwd_field] = $this->encryptPassword($_POST[$pwd_field]);
            if($update){
                $_SESSION['password'] = $_SESSION['mg_password'] = $_POST[$pwd_field];
            }
        }else{
            unset($props['password']);
        }

        if(!$update)
            $cols = $this->getColumns('users');
        else
            $cols = array('preferred_lang', 'password');

        $data = array();
        foreach($props as $key => $value){
            $key = strtolower($key);
            if(in_array($key, $cols)){
                $data[$key] = html_entity_decode(trim($value === null ? '' : $value), ENT_QUOTES|ENT_XHTML);
            }
        }

        if (strpos($data['username'], '@') !== false) {
            $data['username'] = $data['email'];
        }

        if (!empty(lic('getForcedLanguage')) && !empty($data['preferred_lang'])) {
            $data['preferred_lang'] = lic('getForcedLanguage');
        } else {
            unset($data['preferred_lang']);
        }

        $user_id = 0;

        if($update){
            $user_id = uid();
            $user_old = cu($user_id);
            $where_id = "id = '{$user_id}'";
            phive('SQL')->sh($user_id, '', 'users')->updateArray('users', $data, $where_id);
            phive()->pexec('Cashier/Fr', 'invoke', ['updateUserOnExternalKycMonitoring', $user_id]);
        }else{
            $user_id = phive('SQL')->insertArray('users', $data);
            if(phive('SQL')->isSharded('users')){
                $data['id'] = $user_id;
                phive('SQL')->sh($user_id)->insertArray('users', $data);
            }

            $user = cu($user_id);
            if(phive('Config')->getValue('lga', 'reg-email') != 'yes')
                phive('MailHandler2')->sendWelcomeMail($user);
            $bcode = empty($_SESSION['affiliate']) ? $_POST['bonus_code'] : $_SESSION['affiliate'];
        }

        lic('onUserCreatedOrUpdated', [$user_id, $data, !empty($user_old) ? $user_old->getData() : []]);

        return $user_id;
    }

    /**
     * Reset password logic.
     *
     * @param mixed $username User identifying element.
     *
     * @return bool True if the password was reset successfully, false otherwise.
     */
    function resetPwd($username)
    {
        $user = cu($username);

        if (!empty($user) && !$user->isSuperBlocked() && !phive('DBUserHandler')->wasSelfExcluded($user)) {
            $password = phive()->randCode();
            $user->setPassword($password, true);
            $replacers = phive('MailHandler2')->getDefaultReplacers($user);
            $replacers['__PASSWORD__'] = $password;
            $replacers['__FIRSTNAME__'] = $user->getAttribute('firstname');
            phive('MailHandler2')->sendMail("resetpassword", $user, $replacers, null, null, null, null, null, 0);
            if ((int)$this->getBlockReason($user->getId()) === 5) {
                $this->removeBlock($user);
            }
            return true;
        }
        return false;
    }

    /**
     * Forgot username logic, this is more or less legacy since we started using the email as the username.
     *
     * @param string $email The email to send the username to.
     *
     * @return bool True if the username email was queued successfully, false otherwise.
     */
    function emailUsername($email){
        $user = $this->getUserByEmail($email);
        if(!empty($user)){
            $replacers = phive('MailHandler2')->getDefaultReplacers($user);
            $replacers['__FIRSTNAME__'] = $user->getAttribute('firstname');
            $replacers['__USERNAME__'] = $user->getAttribute('username');
            phive('MailHandler2')->sendMail("emailusername", $user, $replacers, null, null, null, null, null, 0);
            return true;
        }
        return false;
    }

    /**
     * Returns an array of all registration fields that are required and their validations.
     *
     * @param array $filter Optional filter to only get that subset of fields.
     *
     * @return array The result array with the field name as key and validation rules as the array value.
     */
    function getReqFields($filter = array()){
        $req_fields = array(
            'password' 	 => array('password', 'strictPassword', array($this->getSetting('password_min_length'))),
            'firstname'  => array('text', 'validateTextField', array('/^[a-zA-Z\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{1E00}-\x{1EFF}\'\-\s]{1,50}$/u', 1, 50, 'register.custom.error.message')),
            'lastname'   => array('text', 'validateTextField', array('/^[a-zA-Z\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{1E00}-\x{1EFF}\'\-\s]{1,50}$/u', 1, 50, 'register.custom.error.message')),
            'email' 	 => array('text', 'email'),
            'address' 	 => array('text', 'validateTextField', array('/^[a-zA-Z0-9_\x{00C0}-\x{00FF}\x{0100}-\x{017F},.\-\'&\/#\s:åäöÅÄÖ]{3,50}$/u', 3, 50, 'register.address.error.message')),
            'zipcode'	 => array('text', 'validateTextField', array('/^[a-zA-Z0-9\-\s]{3,20}$/u', 3, 20, 'register.zipcode.error.message')),
            'city'       => array('text', 'validateTextField', array('/^[a-zA-Z\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{1E00}-\x{1EFF}\'\-\s]{1,50}$/u', 1, 50, 'register.custom.error.message')),
            'place_of_birth' => array('text', 'validateTextField', array('/^[a-zA-Z\x{00C0}-\x{00FF}\x{0100}-\x{017F}\x{1E00}-\x{1EFF}\'\-\s]{1,50}$/u', 1, 50, 'register.custom.error.message')),
            'birthyear'  => array('text', 'reqBirthYear', array(4)),
            'birthmonth' => array('text', 'reqBirthMonth', array(2)),
            'birthdate'  => array('text', 'nothing'),
            'country' 	 => array('text', 'nothing'),
            'mobile' 	 => array('text', 'reqTelephone')
        );

        if(phive('UserHandler')->getSetting('full_registration') === true) {
            $req_fields['password2'] = array('password');
            $req_fields['secemail'] = array('email');
        }

        if ($this->getSetting('show_username') === true) {
            $req_fields['username'] = array('text', 'strictUsername', array(3));
        }

        // TODO henrik port to lic
        if ($this->showNationalId($_REQUEST['country'])) {
            $req_fields['personal_number'] = array('text', 'nothing');
        }

        if(phive("Currencer")->getSetting('multi_currency') == true)
            $req_fields['currency'] = array('text', 'nothing');

        if (in_array('building', $filter) && cu()->getSetting('building')) {
            $req_fields['building'] = array('text', 'nothing');
        }

        foreach($filter as $field)
            $ret[$field] = $req_fields[$field];
        return empty($filter) ? $req_fields : $ret;
    }

    /**
     * Checks if a user exists based on an arbitrary column, eg email or mobile.
     *
     * @uses IpBlock::ipIncLimit() To prevent too many requests from the same IP in order to stop bots.
     *
     * @param string $attr The database column.
     * @param string $value The value of database column.
     * @param bool $return If true - returns check result. If false - prints 'available' or 'taken'
     *
     * @return bool|void The result.
     */
    function checkExistsByAttr($attr, $value, $return = false) {
        $sql = "SELECT * FROM users WHERE $attr = '{$value}'";

        $user = phive('SQL')->shs('merge', '', null, 'users')->loadAssoc($sql);

        if (empty($user) && phive('SQL')->disabledNodeIsActive()) {
            $user = phive('SQL')->onlyMaster()->loadAssoc($sql);
        }

        $result = 'available';

        if(!empty($user)){
            $sql = "SELECT * FROM users_settings WHERE users_settings.user_id = {$user['id']} AND users_settings.setting = 'registration_in_progress'";
            $user_setting = phive('SQL')->readOnly()->sh($user['id'])->loadAssoc($sql);
            if (empty($user_setting) || $user_setting['value'] !== '1') {
                phive('IpBlock')->ipIncLimit();
                $result = 'taken';
            }
        }

        if ($return) {
            return $result === 'taken';
        } else {
            echo $result;
        }
    }

    /**
     * Checks if a posted email address is already taken by someone.
     *
     * @param array &$err Error array to get the error to return in case $return_err is true.
     * @param string $where_extra Extra WHERE clauses.
     * @param bool $return_err Whether or not to return an error message or not.
     * @param ?string $email E-mail to check. Will be used form $_POST if not provided
     *
     * @return string|null The error message in case there is an error and we want to return something, null otherwise.
     */
    function doubleEmail(&$err, $where_extra = '', $return_err = false, ?string $email = null)
    {
        $email = $email ?? $_POST['email'];

        if ($this->countUsersWhere('email', $email, 'users', $where_extra) > 0) {
            $user = cu($email);

            if (!empty($user) && !$user->hasSetting('registration_in_progress')) {
                $err['email'] = 'email.error.update';
                phive('IpBlock')->ipIncLimit();
            }
        }

        if ($return_err) {
            return $err['email'];
        }
    }

    /**
     * Check the length of the mobile number is correct for different jurisdictions
     *
     * @param $err
     * @param bool $return_err
     * @param null $mobile
     * @return string
     */
    public function isMobileLengthCorrect(&$err, $mobile = null, $prefix = null)
    {
        if (empty($mobile)) {
            $mobile = $_POST['mobile'];
        }
        if (empty($prefix)) {
            $prefix = $_POST['country_prefix'];
        }
        $user = cu();
        //send  prefix + mobile
        if (!lic('isMobileLengthCorrect', [$mobile, $prefix], $user) && empty($err['mobile'])) {
            $err['mobile'] = 'mobile.too.long';
        }
    }

    /**
     * Check if the mobile number is correct (unique and no longer than 14 characters for GB players)
     *
     * @param $err
     * @param string $where_extra
     * @param bool $return_err
     * @param null $prefix
     * @param null $mobile
     * @return mixed
     */
    public function isValidMobile(&$err, $where_extra = '', $return_err = false, $prefix = null, $mobile = null)
    {
        $this->doubleMobile($err, $where_extra, $return_err, $prefix, $mobile);
        $this->isMobileLengthCorrect($err, $mobile, $prefix);
        if ($return_err) {
            return $err['mobile'];
        }
    }

    /**
     * Checks if a posted mobile number is already taken by someone.
     *
     * @param array &$err Error array to get the error to return in case $return_err is true.
     * @param string $where_extra Extra WHERE clauses.
     * @param bool $return_err Whether or not to return an error message or not.
     * @param string $prefix Optional country prefix, should be empty if the whole number including country is passed in in $mobile.
     * @param string $mobile The mobile number to check.
     *
     * @return string|null The error message in case there is an error / duplicate and we want to return something, null otherwise.
     */
    function doubleMobile(&$err, $where_extra = '', $return_err = false, $prefix = null, $mobile = null)
    {
        if (empty($prefix)) {
            $prefix = !empty($_POST['country_prefix']) ? $_POST['country_prefix'] : $_SESSION['rstep1']['country_prefix'];
        }
        if (empty($mobile)) {
            $mobile = $_POST['mobile'];
        }

        $mobile = phive('Mosms')->cleanUpNumber($prefix . $mobile);
        if ($this->countUsersWhere('mobile', $mobile, 'users', $where_extra) > 0) {
            $user = cu($_POST['email']);
            if (!empty($user) && $user->getMobile() === $mobile && $user->hasSetting('registration_in_progress')) {
                return null;
            }

            $err['mobile'] = 'mobile.taken';
            phive('IpBlock')->ipIncLimit();
        }

        if ($return_err) {
            return $err['mobile'];
        }
    }

    /**
     * Checks if we have another user with the same NID.
     *
     * @param string $nid The NID to check.
     * @param string $country ISO2 country code of the NID to check.
     * @param bool $return_value Whether or not return the other user or bool.
     *
     * @return bool|DBUser Depending on $return_value we return the other user or true if duplicate exists, false otherwise.
     */
    public function doubleNid($nid, $country, $return_value = false)
    {
        $other = $this->getUserByNid($nid, $country);
        if ($return_value) {
            return $other;
        }
        return !empty($other);
    }

    /**
     * Sends the email with the email verification code.
     *
     * @param bool $regenerate Whether to regenerate a new code or not.
     * @param null|int $user_id
     * @param bool $translate
     *
     * @return null|string Success message in case code was sent successfully, false otherwise.
     */
    public function sendEmailCode($regenerate = false, int $user_id = null, bool $translate = true)
    {
        $alias = 'email.code.sent';
        $user_id = $user_id ?? $_SESSION['rstep1']['user_id'];
        $user = cu($user_id);
        if(!empty($user)){
            $code = $user->getSetting('email_code');
            $uuid = $user->getSetting('hashed_uuid');
            if(empty($code) || empty($uuid) || $regenerate){
                $code = rand(1111, 9999);
                $user->setSetting('email_code', $code);
                $user->setSetting('email_code_verified', 'no');
                $uuid = phive()->uuid();
                $user->setSetting('hashed_uuid', $uuid);
            }
            $url = phive('UserHandler')->getSiteurl()."/?email_code={$code}&uid={$uuid}";
            $mh = phive('MailHandler2');
            $replacers 	= $mh->getDefaultReplacers($user);
            $replacers['__CODE__'] = $code;
            $replacers['__URL__'] = $url;
            $conf = $mh->getSetting('prio_config');
            $from = empty($conf) ? null : $conf['from_email'];
            $mh->sendMail('email-code', $user, $replacers, cLang(), $from, $from, null, null, 0);

            return $translate ? t($alias) : $alias;
        }
    }

    /**
     * Sends the sms with the verification code. This is typically used to verify phone number or 2 factor authentication.
     *
     * @param bool $regenerate Whether to regenerate a new code.
     * @param null|int $user_id
     *
     * @return null
     */
    function sendSmsCode($regenerate = false, int $user_id = null)
    {
        $user_id = $user_id ?? $_SESSION['rstep1']['user_id'];
        $user = cu($user_id);
        if(empty($user))
            return false;

        $code = $user->getSetting('sms_code');
        if(empty($code) || $regenerate){
            $code = rand(1111, 9999);
            $user->setSetting('sms_code', $code);
            $user->setSetting('sms_code_verified', 'no');
        }

        phive('Mosms')->putInQ($user, t('mosms.verification.msg').' '.$user->getSetting('sms_code'), false);
    }

    /**
     * Which identifying column to display, depending on a configuration value.
     *
     * @return string The field name (translated), eg Email or Username.
     */
    public function getLoginFirstInput($translate = true)
    {
        if($this->getSetting('show_username') === false) {
            $alias = 'email';
        } else {
            $alias = 'username';
        }

        return $translate ? t($alias) : $alias;
    }

     // TODO henrik port to lic
    public function showNationalId($country = null, $type = 'registration')
    {
        if ($type == 'popup' && $this->getSetting("deactivate_nid_popup") === true) {
            return false;
        }
        if (empty($country)) {
            $country = phive('IpBlock')->getCountry();
        }

        if(lic('hasExtVerification', [], null, null, $country)){
            // Current bank id logic clashes with this logic so turning it off here if bank id is active.
            return false;
        }

        return in_array($country, $this->getSetting("show_nid_countries"));
    }

    /**
     * Validates the new registration step 1
     *
     * @return array All validation errors, empty array if no errors.
     */
    function validateStep1($req_fields, $check_unique = true, $chk_cond = true, $chk_priv = true)
    {
        $err = phive('QuickFire')->validate(empty($req_fields) ? $this->getReqFields() : $req_fields);

        if($this->getSetting('show_username') === true && is_numeric($_POST['username']))
            $err['username'] = 'numerical';

        if(empty($_SESSION['rstep2'])) {
            if(!phive()->isLocal() && $this->countUsersWhere('reg_ip', remIp()) > 2) {
                $err['ip'] = 'ip.toomany';
            }
        }

        if($this->getSetting('full_registration') === true && $_POST['email'] != $_POST['secemail']) {
            $err['email'] = 'email.not.same';
        }

        // Update user when he went back to step 1 from step 2, unique fields need to be unique, or equal to what's in the session/database
        if(!empty($_SESSION['rstep2'])) {
            $check_unique = false;
            // do not allow the user to go back to step 1 and change the username
            if($this->getSetting('show_username') === true && $_POST['username'] != $_SESSION['rstep1']['username']) {
                $_POST['username'] = $_SESSION['rstep1']['username'];
            }

            if($_POST['email'] != $_SESSION['rstep1']['email']) {
                // email was changed
                $this->doubleEmail($err);
            }

            if($_POST['mobile'] != $_SESSION['rstep1']['mobile']) {
                // mobile was changed
                $this->isValidMobile($err);

            }
        }

        if($check_unique){
            if($this->getSetting('show_username') === true && $this->countUsersWhere('username', $_POST['username']) > 0) {
                $err['username'] = 'username.taken';
            }

            $this->isValidMobile($err);

            $this->doubleEmail($err);
        }

        if($_POST['conditions'] != 'on' && $_POST['conditions'] != 1 && $chk_cond)
            $err['conditions'] = 'not.checked';

        if($_POST['privacy'] != 'on' && $_POST['privacy'] != 1 && $chk_priv)
            $err['privacy'] = 'not.checked';

        if(empty($_POST['preferred_lang'])) {
            $_POST['preferred_lang'] = 'en';
        }

        // TODO henrik pass in post country as the second arg to doubleNid.
        if ($this->showNationalId($_POST['country']) && $this->doubleNid($_POST['personal_number']) === true) {
            $err['personal_number'] = 'invalid.personal.number';
        }

        return $err;
    }

    /**
     * Validates the new registration step 2
     *
     * @return array All validation errors, empty array if no errors.
     */
    function validateStep2(
        $req_fields,
        $chk_over18,
        $user,
        array $request = [],
        array $required_step2_fields = [],
        array $provinces = []
    ) {
        $req_fields = empty($req_fields) ? $this->getReqFields() : $req_fields;

        /** @var \QuickFire $quickFire */
        $quickFire = phive('QuickFire');

        $err = $quickFire->validateV2($req_fields, $request);

        if($request['over18'] != 'on' && $request['over18'] != 1 && $chk_over18 && !licSetting('skip_over18_check'))
            $err['over18'] = 'not.checked';

        $this->validateAge($err, $request['dob'], $user->getCountry(), $user->getId());
        $this->validateSexGender($err, $request['sex']);
        $this->validatePreferredLanguage($err, $request['preferred_lang']);
        $this->validateCurrency($err, $request['currency']);
        $this->validateIsBlockedCountry($err, $user->getCountry());

        $country = $user->getProvince() ? $user->getProvince() : $user->getCountry();
        // Validate zipcode for certain countries
        $this->validateZipcode($err, $country, $request['zipcode']);
        $this->validateNationality($err, $request['nationality'], $required_step2_fields);
        $this->validatePlaceOfBirth($err, $request['place_of_birth'], $required_step2_fields);

        if (lic('shouldValidateMainProvince', [$request['main_country']], $user)) {
            $this->validateMainProvince($err, $request['main_province'], $provinces);
        }

        if(isset($request['loginCommonData']) && !$request['loginCommonData']->isPnp()){
            $this->validateEmailCode($err, $request['email_code'], $user);
        }

        // Custom validation per jurisdiction
        $extra_errors = lic('extraRegistrationValidations', ['step2', $request, $user->getId()], $user);
        $err = array_merge($err, $extra_errors);

        return $err;
    }

    /**
     * Validates user age
     *
     * @param array $err
     * @param string $birthdate
     * @param string $country
     * @param int $user_id
     *
     * @return bool
     */
    public function validateAge(array &$err, string $birthdate, string $country, int $user_id): bool
    {
        $age = date_diff(date_create($birthdate), date_create(date("Y-m-d")))->y;
        $required_age = licSetting('allowed_age', cuRegistration($user_id));

        if (empty($required_age)) {
            $required_age = phive('SQL')->getValue(
                "SELECT reg_age FROM bank_countries WHERE iso = '{$country}'"
            );
        }

        $isDateValid  = $this->validateDate($birthdate);

        if (!$isDateValid) {
            $err['birthdate'] = 'wrong.birthdate';
        }

        if ($age < $required_age) {
            $err['birthdate'] = 'wrong.birthdate.tooyoung';
        }

        return !isset($err['birthdate']);
    }

    /**
     * Validates user sex gender
     *
     * @param array $err
     * @param string $sex
     *
     * @return bool
     */
    public function validateSexGender(array &$err, string $sex): bool
    {
        if (!in_array($sex, ['Male', 'Female'])) {
            $err['sex'] = 'wrong.sex';
        }

        return !isset($err['sex']);
    }

    /**
     * Validates user preferred language
     *
     * @param array $err
     * @param string $preferredLang
     *
     * @return bool
     */
    public function validatePreferredLanguage(array &$err, string $preferredLang): bool
    {
        $excluded_languages = lic('getExcludedRegistrationLanguages', [], cuRegistration());
        $languagesList = phive("Localizer")->getLangSelect("WHERE selectable = 1", $excluded_languages);

        if (!in_array($preferredLang, array_keys($languagesList))) {
            $err['preferred_lang'] = 'wrong.preferred_lang';
        }

        return !isset($err['preferred_lang']);
    }

    /**
     * Validate user currency
     *
     * @param array $err
     * @param string $currency
     *
     * @return bool
     */
    public function validateCurrency(array &$err, ?string $currency): bool
    {
        if (licSetting('skip_currency_validation')) {
            return true;
        }

        $currencies = $this->getCurrencies();

        if (!in_array($currency, $currencies)) {
            $err['currency'] = 'wrong.currency';
        }

        return !isset($err['currency']);
    }

    /**
     * Validate if user is from a blocked country
     *
     * @param array $err
     * @param string $country
     *
     * @return bool
     */
    public function validateIsBlockedCountry(array &$err, string $country): bool
    {
        $country = strtoupper($country);
        $blockedCountries = phive('Config')->valAsArray('countries', 'block');

        if(in_array($country, $blockedCountries)) {
            $err['country'] = 'wrong.country.blocked';
        }

        return !isset($err['country']);
    }

    /**
     * Validates the zipcode.
     *
     * @param array $err We pass in errors by referecne and add a zipcode error to the array in case the zipcode does not validate.
     * @param string $zipcode The zipcode to validate.
     * @return bool True if all is good, false otherwise.
     */
    function validateZipcode(&$err, $country, $zipcode = null)
    {
        // TODO henrik licify this.
        $zipcode_rules = [
            'GB' => [
                'min_length' => 5,
                'max_length' => 10,
            ],
            'CA-ON' => [
                'is_format_valid' => function ($zipcode) {
                    if (strlen($zipcode) !== 7) {
                        return false;
                    }

                    $zipcode_char_array = str_split($zipcode);
                    if (
                        !preg_match('/[A-Z]/', $zipcode_char_array[0]) ||
                        !is_numeric($zipcode_char_array[1]) ||
                        !preg_match('/[A-Z]/', $zipcode_char_array[2]) ||
                        !preg_match('[ ]', $zipcode_char_array[3]) ||
                        !is_numeric($zipcode_char_array[4]) ||
                        !preg_match('/[A-Z]/', $zipcode_char_array[5]) ||
                        !is_numeric($zipcode_char_array[6])
                    ) {
                        return false;
                    }

                    return true;
                }
            ]
        ];
        if(key_exists($country, $zipcode_rules)) {
            $zipcode_length = strlen(empty($zipcode) ? $_POST['zipcode'] : $zipcode);
            if(key_exists('min_length', $zipcode_rules[$country])) {
                if($zipcode_length < $zipcode_rules[$country]['min_length']) {
                    $err['zipcode'] = 'zipcode.unvalid.'.$country;
                }
            }
            if(key_exists('max_length', $zipcode_rules[$country])) {
                if($zipcode_length > $zipcode_rules[$country]['max_length']) {
                    $err['zipcode'] = 'zipcode.unvalid.'.$country;
                }
            }
            if(key_exists('is_format_valid', $zipcode_rules[$country])) {
                if (!$zipcode_rules[$country]['is_format_valid']($zipcode)) {
                    $err['zipcode'] = 'zipcode.unvalid.'.$country;
                }
            }
        }

        return !isset($err['zipcode']);
    }

    /**
     * Validates the date with format('Y-m-d')
     *
     * @param string $date
     *
     * @return bool
     */
    function validateDate(string $date): bool
    {
        $today = new DateTime('now');
        $requestedDate = DateTime::createFromFormat('Y-m-d', $date);

        $dateArray = explode('-', $date);
        $isDateValid = checkdate($dateArray[1], $dateArray[2], $dateArray[0]);

        return $requestedDate && $requestedDate < $today && $isDateValid;
    }

    /**
     * @param array $err
     * @param string|null $nationality
     * @param array $reqFields
     *
     * @return bool
     */
    function validateNationality(array &$err, ?string $nationality, array $reqFields): bool
    {
        $isNationalityValid = !empty((lic('getNationalities') ?? [])[$nationality]);

        if ($nationality !== null && !in_array('nationality', $reqFields)) {
            $err['nationality'] = 'register.nationality.not.required.message';
        }

        if ($nationality !== null && !$isNationalityValid) {
            $err['nationality'] = 'register.nationality.error.message';
        }

        return !isset($err['nationality']);
    }


    /**
     * @param array $err
     * @param string|null $placeOfBirth
     * @param array $reqFields
     *
     * @return bool
     */
    function validatePlaceOfBirth(array &$err, ?string $placeOfBirth, array $reqFields): bool
    {
        if ($placeOfBirth === null && in_array('place_of_birth', $reqFields)) {
            $err['place_of_birth'] = 'place.of.birth.error.required';
        }

        if ($placeOfBirth !== null && !in_array('place_of_birth', $reqFields)) {
            $err['place_of_birth'] = 'register.place_of_birth.not.required.message';
        }

        return !isset($err['place_of_birth']);
    }

    /**
     * @param array $err
     * @param string|null $province
     * @param array $provinces
     *
     * @return bool
     */
    function validateMainProvince(array &$err, ?string $province, array $provinces): bool
    {
        $isProvinceValid = !empty(($provinces ?? [])[$province]);

        if ($province !== null && !$provinces) {
            $err['main_province'] = 'register.main_province.not.required.message';
        }

        if (!empty($provinces) && !$isProvinceValid) {
            $err['main_province'] = 'register.main_province.not.available';
        }

        return !isset($err['main_province']);
    }

    /**
     * @param array $err
     * @param string $emailCode
     * @param DBUser $user
     *
     * @return bool
     */
    function validateEmailCode(array &$err, ?string $emailCode, DBUser $user): bool
    {
        if ($emailCode !== null && !lic('verifyCommunicationChannel', null, $user)) {
            $err['email_code'] = ['register.email_code.not.required.message'];
        }

        return !isset($err['email_code']);
    }

    /** Validates OTP token which user got by email or sms
     * @param int $user_id
     * @param string $code
     *
     * @return array
     */
    public function validateTokenOtp(int $user_id, string $code): array
    {
        $user = cu($user_id);
        $errors = [];

        if ($code == $user->getSetting('email_code') && !empty($user->getSetting('email_code'))) {
            $user->setSetting('email_code_verified', 'yes');
            unset($_SESSION['email_code_shown']);
            phive()->pexec('Cashier/Fr', 'removeEmailAndPhoneCheckFlag', [$user->getId()]);
        } elseif ($code == $user->getSetting('sms_code') && !empty($user->getSetting('sms_code'))) {
            $user->setSetting('sms_code_verified', 'yes');
            $user->setSetting('email_code_verified', 'yes');
            $user->setAttr('verified_phone', 1);
            unset($_SESSION['sms_code_shown']);
            phive()->pexec('Cashier/Fr', 'removeEmailAndPhoneCheckFlag', [$user->getId()]);
        } else {
            $errors = ['email_code' => t('wrong.email.code')];
            phive()->pexec('Cashier/Fr', 'emailAndPhoneCheck', [$user->getId()]);
        }

        return $errors;
    }

    /**
     * @param $request
     * @param $user
     * @return string
     */
    public function validateCommunicationChannelCode($request, $user): string {
        if (!empty($request['email_code'])) {
            $errors = [];

            if ($request['email_code'] == $user->getSetting('email_code') && !empty($user->getSetting('email_code'))) {
                $user->setSetting('email_code_verified', 'yes');

                if(isPNP()){
                    $user->deleteSetting('registration_in_progress');
                }

                //$user->setAttribute('verified_email', '1');
                unset($_SESSION['email_code_shown']);
                $user->verify();
            } elseif ($request['email_code'] == $user->getSetting('sms_code') && !empty($user->getSetting('sms_code'))) {
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');

                if(isPNP()){
                    $user->deleteSetting('registration_in_progress');
                }

                $user->setAttr('verified_phone', 1);
                unset($_SESSION['sms_code_shown']);
                $user->verify();
            } else {
                $errors = ['email_code' => t('wrong.email.code')];
                return json_encode(["success" => false, "messages" => $errors]);
            }

            phive('Cashier/Fr')->checkForSuspiciousEmail($user);

            return json_encode(["success" => true, "messages" => $errors]);
        }
        return "";
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateIntendedGamblingData $data
     *
     * @return void
     */
    public function setIntendedGambling(UpdateIntendedGamblingData $data)
    {
        $user = cu($data->getUserId());
        $user->setSetting('intended_gambling', $data->getRange());
    }

    /**
     * Get all currencies by code
     *
     * @return array
     */
    function getCurrencies(): array
    {
        $currencies = [];

        foreach (cisos(false, false, false) as $r) {
            $currencies[$r['code']] = $r['code'];
        }

        return $currencies;
    }

    /**
     * Validates the date of birth.
     *
     * @param array &$err Error array passed in as reference, we will add an error to it in case something is off.
     * @param DBUser $user Optional user object, in case it is missing the currently logged in user will be used.
     *
     * @return null The caller have to inspect the $err array for the aditional error in case it wants to check for a fail.
     */
    function checkDob(&$err, $user = '')
    {
        if(isset($_POST['birthdate'])) {
            // check if date is valid
            if(!checkdate($_POST['birthmonth'], $_POST['birthdate'], $_POST['birthyear'])) {
                $err['dob'] = 'invalid';
            } else {
                if(empty($_REQUEST['birthyear']) || empty($_REQUEST['birthmonth']) || empty($_REQUEST['birthdate']))
                    $err = array('dob' => 'not.empty');
                else
                    $_POST['dob'] = "{$_REQUEST['birthyear']}-{$_REQUEST['birthmonth']}-{$_REQUEST['birthdate']}";

                // check if user is > 18
                $dob = $_POST['dob'];
                $_REQUEST['dob'] = $dob;

                if(empty($user)) {
                    $user = cu();
                }

                $country = $user->getAttribute('country');

                $allowed_age = licSetting('allowed_age', $user);

                if (!$allowed_age){
                    //need to be moved to a EE country lincensed file
                    $allowed_age = ($country == 'EE') ? 21 : 18;
                }

                $allowed_stamp = strtotime("-{$allowed_age} year");
                $dob_stamp = strtotime($dob);

                if($dob_stamp > $allowed_stamp && empty($err))
                    $err['dob'] = 'tooyoung';
            }
        }
    }

    /**
     * Validates address fields (address, city, zipcode, building)
     *
     * @param array &$err Error array passed in as reference, we will add an error to it in case something is off.
     * @param bool $hasBuilding Check if user has building information.
     *
     * @return null The caller have to inspect the $err array for the aditional error in case it wants to check for a fail.
     */
    function validateAddress(&$err, $hasBuilding = false)
    {
        if (isset($_POST['address'], $_POST['city'], $_POST['zipcode'])) {
            if (strlen($_POST['address']) > 0) {
                $addressLength = $hasBuilding ? strlen($_POST['address']) + strlen($_POST['building']) : strlen($_POST['address']);
                if ($addressLength > 50) {
                    $err['address'] = 'too.long';
                    if ($hasBuilding) {
                        $err['building'] = 'too.long';
                    }
                }
            } else {
                $err['address'] = 'not.empty';
            }

            if (strlen($_POST['city']) > 50) {
                $err['city'] = 'too.long';
            } elseif (strlen($_POST['city']) === 0) {
                $err['city'] = 'not.empty';
            }

            if (strlen($_POST['zipcode']) > 10) {
                $err['zipcode'] = 'too.long';
            } elseif (strlen($_POST['zipcode']) === 0) {
                $err['zipcode'] = 'not.empty';
            }
        }
    }

    /**
     * Validates depending on the scenario.
     *
     * // TODO henrik refactor, remove the !$update part and all unnecessary arguments.
     *
     * @param string $update The scenario.
     * @param array $req_fields Required fields and their validation actions.
     *
     * @return array An array with errors if something didn't validate, or empty in case all is good.
     */
    function validateUser($update, $req_fields, $chk_over18 = true, $chk_cond = true,  $check_unique = false, $step = 2, $chk_priva = true){

        if($update && empty($req_fields))
            return array();

        $err = phive('QuickFire')->validate(empty($req_fields) ? $this->getReqFields() : $req_fields);

        if($update == 'cinfo'){
            $user = cu($_POST['user_id']);
            $where_extra = " AND id != ".$_POST['user_id'];
            if (isset($_POST['email'])) {
                $this->doubleEmail($err, $where_extra);
            }
            if (isset($_POST['mobile'])) {
                $this->doubleMobile($err, $where_extra);
            }
            $this->checkDob($err);
            if (isset($_POST['zipcode'])) {
                $_POST['zipcode'] = lic('formatZipcode', [$_POST['zipcode']]);
                $country = $user->getProvince() ? $user->getProvince() : $user->getCountry();
                $this->validateZipcode($err, $country, $_POST['zipcode']);
            }
            $hasBuilding = cu()->getSetting('building') && in_array('building', array_keys($req_fields));
            $this->validateAddress($err, $hasBuilding);
        }

        if ($update == 'chpwd') {
            $user = cu($_POST['user_id']);
            $checkPassword = phive('UserHandler')->checkPassword($user, $_POST['password0']);

            if (!$checkPassword) {
                $err['password'] = 'password0';
            } else if ($_POST['password'] != $_POST['password2']) {
                $err['password'] = 'password2';
            }


        }

        if(!$update){
            if($_POST['over18'] != 'on' && $_POST['over18'] != 1 && $chk_over18)
                $err['over18'] = 'not.checked';

            if($_POST['birthdate'] == '00')
                $err['birthdate'] = 'empty';

            if($_POST['birthmonth'] == '00')
                $err['birthmonth'] = 'empty';

            if($_POST['birthyear'] == '0000')
                $err['birthyear'] = 'empty';

            if($_POST['conditions'] != 'on' && $_POST['conditions'] != 1 && $chk_cond)
                $err['conditions'] = 'not.checked';

            if($_POST['privacy'] != 'on' && $_POST['privacy'] != 1 && $chk_priva)
                $err['privacy'] = 'not.checked';

            if($this->countUsersWhere('reg_ip', remIp()) > 2)
                $err['ip'] = 'ip.toomany';

            if(in_array(strtoupper($_POST['country']), phive('Config')->valAsArray('countries', 'block')))
                $err['country'] = 'blocked.country';

            if(is_numeric($_POST['username']))
                $err['username'] = 'numerical';

            if($check_unique){
                if($this->getSetting('show_username') === true && $this->countUsersWhere('username', $_POST['username']) > 0)
                    $err['username'] = 'username.taken';

                if($step == 2)
                    $this->doubleMobile($err);
                else{
                    if($this->getSetting('full_registration') === true && $_POST['email'] != $_POST['secemail'])
                        $err['email'] = 'email.not.same';
                }

                $this->doubleEmail($err);
            }
        }

        if(empty($_POST['preferred_lang']))
            $_POST['preferred_lang'] = 'en';

        return $err;
    }

    /**
     * Returns a new user object based on an arbitrary column / attribute and its value to use for lookup.
     *
     * @param string $aname Column / attribute name.
     * @param mixed $avalue Column attribute value.
     * @param bool $from_master return the user data from master
     *
     * @return DBUser The resultant user object.
     */
    function newByAttr($aname, $avalue, $from_master = false){
        $val = phive('SQL')->escape($avalue, false);

        if ($from_master) {
            $master_user = phive('SQL')->loadAssoc("", "users", "$aname = '$val'");
            return new DBUser($master_user);
        }
        // If users is sharded and attribute name is username we first query the master to get the id.
        // The reason is that we want to avoid querying all nodes just to get one user, if we do like
        // this we can route to the correct shard.
        if(phive('SQL')->isSharded('users') && $aname == 'username'){
            $master_user = phive('SQL')->loadAssoc("", "users", "$aname = '$val'");
            if(!empty($master_user)){
                $aname  = 'id';
                $val    = $master_user['id'];
            }
        }

        $db  = $aname == 'id' ? phive('SQL')->sh($val, '', 'users') : phive('SQL')->shs('merge', '', null, 'users');
        $arr = $db->loadAssoc("", "users", "$aname = '$val'");
        return new DBUser($arr);
    }

    /**
     * Self exclude user for a specific $duration with the option to make the exclusion $permanent
     *
     * @param DBUser $u The user.
     * @param int $duration The exclude duration in days.
     * @param bool $permanent True if we want to make the exclusion last until manually removed.
     * @param string $force_date In case we want to for the date instead of using days
     * @param bool $from_remote If this method is called by a remote brand then we should not send a self exclusion request back
     * @param $indefinite True if we want the exclusion to last indefinitely.
     */
    public function selfExclude($u, $duration, $permanent = false, $force_date = null, $from_remote = false, $indefinite = false)
    {
        $permanent = empty($permanent) ? '' : "Permanent";
        $indefinite = empty($indefinite) ? '' : "Indefinite";
        $_SESSION['skip_websocket_logout'] = true;
        $this->addBlock($u, 4);
        $this->logAction($u, "Self Excluded $permanent", "profile-lock", true);
        $u->setSetting('excluded-date', phive()->hisNow());
        $user_id = $u->getId();

        if (empty($force_date)) {
            $u->setSetting('unexclude-date', date('Y-m-d', strtotime("+$duration day")));
        } else {
            $u->setSetting('unexclude-date', $force_date);
        }
        if ($permanent || $indefinite) {
            $u->setSetting('indefinitely-self-excluded', 1);
        }

        if (!$from_remote && !empty(lic('getLicSetting', ['cross_brand'], $u)['check_self_exclusion'])) {
            $remote_brand = getRemote();

            $remote_user_id = linker()->getUserRemoteId($user_id);

            if (!empty($remote_user_id)) {
                $response = toRemote(
                    $remote_brand,
                    'syncSelfExclusionSettingWithRemoteBrand',
                    [$remote_user_id, $duration, $permanent, $indefinite]
                );

                phive('UserHandler')->logAction($user_id,
                                                    "Added self exclusion to {$remote_brand} for user {$user_id} resulted in {$response['success']}",
                                                    "add-to-remote-self-exclusion");

                return $response['success'] === true;

            }
        }
    }

    /**
     * Checks if a user is self-excluded.
     *
     * @param DBUser $user The user object.
     * @return bool True if self excluded, false otherwise.
     */
    function isSelfExcluded($user)
    {
        if ($user->getSetting('excluded-date') && $user->getSetting('unexclude-date')) {
            return true;
        }
        return false;
    }

    /**
     * Was self-excluded before but still not unlocked, as per the original UKGC requirements for S-E the user needs to personally request an unlock
     * action from support agents and will be blocked indefinitely until this happens even though the S-E has been removed by a cron.
     *
     * Once the block has been removed manually the user is fully reinstated and **NOTE** that this method will then not return as
     * expected, it will return false even though the user strictly was S-E in the past.
     *
     * @param DBUser $user The user object.
     * @return bool True if user has gotten the S-E removed but is still waiting for manual unblock.
     */
    public function wasSelfExcluded($user)
    {
        return ($user->isBlocked() && !$user->getSetting('super-blocked') && $user->getSetting('excluded-date') && $user->getSetting('unexcluded-date')) ? true : false;
    }

    /**
     * Called when external services like Gamstop, ROFUS or Spelpaus indicates that a user should be self excluded.
     *
     * @param DBUser $user
     * @return bool True if the S-E process executed, false if not which is the case if the user is already S-E.
     */
    function externalSelfExclude($user)
    {
        $user = cu($user);
        if ($this->isSelfExcluded($user) || $this->isExternalSelfExcluded($user)) {
            return false;
        }
        $block = $this->addBlock($user, 13);
        if ($block === 'blocked_already') {
            $this->logAction($user, "User already blocked when externally self excluding", "external-exclusion-block");
        }

        $this->logAction($user, "Self Excluded on external register", "profile-lock");
        $user->setSetting('external-excluded', phive()->hisNow());

        lic('lowerNDLOnExternalSelfExclusion', [$user], $user);

        if (lic('isEnabledStatusTracking', [], $user)) {
            $status = (string)lic('getAllowedUserStatus', [$user], $user);

            lic('trackUserStatusChanges', [$user, $status], $user);
        }

        return true;
    }

    /**
     * Removes an external self exclusion, typically called if the external service indicates that the user is not S-E anymore.
     * Removes the user block(active=1) only if:
     * 1. The user is not Spanish
     * 2. The user isn't super-blocked
     * 3. The user wasn't blocked(active=0) when the external self exclusion happened
     * 4. The user does not have active internal self exclusion
     * 5. The user does not have expired internal self exclusion which still needs to be manually lifted
     * 6. The user does not have internal self lock
     *
     * @param DBUser $user The user object.
     *
     * @return bool True if we reach the end of the method.
     */
    public function removeExternalSelfExclusion($user): bool
    {
        $user = cu($user);
        $shouldRemoveUserBlockAfterExternalSelfExclusionEnds = lic('shouldRemoveUserBlockAfterExternalSelfExclusionEnds', ['user' => $user], $user);
        $external_excluded = $user->getSetting('external-excluded');
        $external_se_day = $external_excluded['value'];
        $now = phive()->hisNow();
        $users_settings = ['external-excluded'];
        $user->deleteSettings($users_settings);
        $user->setSetting('external-unexcluded-date', $now);
        $this->logAction($user, "Self-exclusion on external register since {$external_se_day} ended on {$now}.", "profile-unlock");

        if (licSetting('external_excluded_auto_reactivation_disallowed', $user)) {
            lic('trackUserStatusChanges', [$user, UserStatus::STATUS_DORMANT], $user);
            $this->logAction($user, 'Cron did not unlock user due to regulator requirement. Manual activation required.', "removeExternalLock");

            return true;
        }

        if ($user->isSuperBlocked()) {
            $this->logAction($user, 'Cron did not unlock user due to super block.', 'removeExternalLock');

            return true;
        }

        if ($user->isSelfExcluded() || $user->isSelfLocked()) {
            $this->logAction(
                $user,
                'Cron did not unlock user due to self-exclusion or self-lock.',
                'removeExternalLock'
            );

            return true;
        }

        if ($shouldRemoveUserBlockAfterExternalSelfExclusionEnds) {
            $user->setAttribute('active', '1');
        }

        return true;
    }

    /**
     * Checks if a user is self-excluded in an external database like GamStop or Rofus.
     *
     * @param DBUser $user The user object.
     * @return bool True if yes, the user is S-E, false otherwise.
     */
    function isExternalSelfExcluded($user)
    {
        if ($user->getSetting('external-excluded')) {
            return true;
        }
        return false;
    }


    /**
     * Standard blocks a user
     *
     * **The block reasons are as follows:**
     * 0 Failed 3 deposits in a row
     * 1 Failed 3 SMS validations in a row
     * 2 Wrong country
     * 3 Admin locked
     * 4 User locked himself
     * 5 Tried to login 3 times with the wrong password
     * 6 Failed SMS authentication
     * 7 Wrong code from email link.
     * 8 Failed 3 login attempts.
     * 9 PSP chargeback
     * 10 Too similar to existing user
     * 11 Temporary account block
     * 12 Failed PEP/SL check
     * 13 External self exclusion
     * 14 Underage
     * 15 Death
     * 16 Suspected fraud
     * 17 Blocked for ignoring Intensive Gambling check
     *
     * @param int|string|DBUser $username
     * @param int $reason
     * @param bool $unlock
     * @param string $unlock_date
     * @return bool|int|string
     */
    function addBlock($username, $reason, $unlock = false, $unlock_date = ''){
        $user    = cu($username);
        $active  = $user->getAttr('active');
        if(empty($active))
            return 'blocked_already';
        $user->block();
        if($unlock){
            $unlock_date 	= empty($unlock_date) ? phive()->hisMod('+1 day') : $unlock_date;
            $user->setSetting('unlock-date', $unlock_date);
        }
        // TODO check if we need to define more scenarios based on the block reason ID
        $map_block_reason = [
            4 => UserStatus::STATUS_SELF_EXCLUDED,
            13 => UserStatus::STATUS_EXTERNALLY_SELF_EXCLUDED,
            15 => UserStatus::STATUS_DECEASED,
            16 => UserStatus::STATUS_UNDER_INVESTIGATION,
            17 => UserStatus::STATUS_BLOCKED,
        ];
        $user_status = $map_block_reason[$reason] ?? UserStatus::STATUS_BLOCKED;
        lic('trackUserStatusChanges', [$user, $user_status], $user);

        if (in_array($reason, [4, 13])) {
            $current_status = $user->getSetting('current_status');
            if ($current_status !== $user_status) {
                phive('Logger')->getLogger('user_status')->log("User status inconsistent with the block reason.",
                    [
                        'user_id' => $user->getId(),
                        'current_status' => $current_status,
                        'status' => $user_status
                    ]
                );
            }
        }

        /**
         * No need to trigger event if
         * 3 = Admin locked [Separate trigger added in admin2]
         * 4 = User locked himself
         * 13 = External self exclusion
         */
        if(!in_array($reason, [3, 4, 13])) {
            $cause = $this->reasonToInterventionCauseMapping((int)$reason);
            $log_id = phive('UserHandler')->logAction($user->getId(), "profile-blocked|{$cause} - Player account blocked {$reason}", 'intervention');

            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                    'intervention_done',
                    new InterventionHistoryMessage([
                        'id'                => (int) $log_id,
                        'user_id'           => (int) $user->getId(),
                        'begin_datetime'    => phive()->hisNow(),
                        'end_datetime'      => '',
                        'type'              => 'profile-blocked',
                        'cause'             => $cause,
                        'event_timestamp'   => time(),
                    ])
            ], $user);
        }

        return phive('SQL')->sh($user, 'id', 'users_blocked')->insertArray('users_blocked', array('username' => $user->getUsername(), 'user_id' => $user->getId(), 'reason' => $reason, 'ip' => remIp()));
    }


    /**
     * @param int $reason
     * @return string
     */
    private function reasonToInterventionCauseMapping(int $reason): string
    {
        switch ($reason) {

            case 9: // PSP chargeback
            case 10: // Too similar to existing user
            case 12: // Failed PEP/SL check
            case 16: // Suspected fraud
                $cause = 'fraud';
                break;
            case 14: // Underage
                $cause = 'problem-gambling';
                break;
            default:
                $cause = 'other';
                break;

        }

        return $cause;
    }



    /**
     * Gets an explanatory string from the block reason code.
     *
     * TODO henrik add the missing ones.
     *
     * @param int $num The block reason code.
     *
     * @return string The informative string.
     */
    function getBlockReasonStr($num){
        $map = [0 => 'Failed 3 deposits in a row',
                1 => 'Failed 3 SMS validations in a row',
                2 => 'Wrong country',
                3 => 'Admin locked',
                4 => 'User locked himself',
                5 => 'Tried to login 3 times',
                17 => 'Ignoring Intensive Gambling check',
        ];
        return $map[$num];
    }

    /**
     * Removes a block from a user.
     *
     * @param DBUser $user The user object.
     *
     * @return void
     */
    public function removeBlock($user): void
    {
        $users_settings = [];

        if ($user->getSetting('excluded-date') && $user->getSetting('unexclude-date')) {
            $reason = $this->getBlockReasonStr($this->getBlockReason($user->getId()));
            $this->logAction($user, "User unlocked, original block reason: {$reason}", 'block', false, cu());
            $users_settings = ['excluded-date', 'unexclude-date'];
            $user->deleteSettings($users_settings);
        }

        $activate_user = true;

        if ($user->isSuperBlocked()) {
            $this->logAction($user, 'Cron did not activated user due to super block.', 'unlock');
            $activate_user = false;
        } elseif(lic('isEnabledStatusTracking', [], $user)) {
            $status = (string) lic('getAllowedUserStatus', [$user, $users_settings], $user);

            lic('trackUserStatusChanges', [$user, $status], $user);

            if (!lic('isActiveStatus', [$status], $user)) {
                $activate_user = false;
            }
        }

        if ($activate_user) {
            $user->setAttribute('active', '1');
            $this->logAction($user, 'Cron has activated user.', 'unlock');
        }
    }

    /**
     * Gets rows from the block log.
     *
     * @param string $startDate Start date of range.
     * @param string $endDate End date of range.
     * @param int $uid Optional user id filter.
     *
     * @return array The result array.
     */
    function getBlocks($startDate, $endDate, $uid = ''){
        if(!empty($uid)){
            $db = phive('SQL')->sh($uid);
            $where = "AND user_id = $uid";
        }else{
            $db = phive('SQL')->shs();
        }
        $str = "SELECT * FROM users_blocked WHERE `date` <= '$endDate' AND `date` >= '$startDate' $where ORDER BY date ASC";
        return $db->loadArray($str);
    }

    /**
     * Gets the reason / code for the latest block of a user.
     *
     * @param int $uid The user id.
     *
     * @return int The reason code.
     */
    function getBlockReason($uid){
        $uid = intval($uid);
        $str = "SELECT * FROM users_blocked WHERE user_id = $uid ORDER BY id DESC LIMIT 0,1";
        $res = phive('SQL')->sh($uid, '', 'users_blocked')->loadAssoc($str);
        return $res['reason'];
    }

    /**
     * Gets the full bank country row for a user.
     *
     * @param DBUser $user The user object.
     *
     * @return array The bank country row.
     */
    function userBankCountry($user){
        return phive('SQL')->loadAssoc('', 'bank_countries', "iso = '{$user->getAttribute('country')}'");
    }

    // TODO henrik remove
    function getArchiveTbls(){
          return array(
              'bonus_entries'       => 'id',
              'cash_transactions'   => 'id',
              'deposits'            => 'id',
              'first_deposits'      => 'id',
              'pending_withdrawals' => 'id',
              'users_games_favs'    => '',
              'users_settings'      => 'id',
              'race_entries'        => '',
              'tournament_entries'  => '',
              'trophy_award_ownership' => '',
              'users_blocked'       => '',
              'users'               => 'id'
          );
      }


    // TODO henrik remove
    function deleteUser($uid){
        $uid = intval($uid);
        $db = phive('SQL');
        foreach($this->getArchiveTbls() as $tbl => $pid){
            $pid = empty($pid) ? 'id' : $pid;
            $str = "SELECT * FROM $tbl WHERE user_id = $uid";
            $rows = $db->loadArray($str);
            foreach($rows as $el){
                $str = "DELETE FROM $tbl WHERE $pid = {$el[$pid]}";
                echo "$str\n";
                $db->query($str);
            }
        }
    }

    // TODO henrik remove
    function archiveUser($uid, $delete = true){

        return false;

        $uid = intval($uid);
        $db = phive('SQL');
        foreach($this->getArchiveTbls() as $tbl => $pid){
            $pid   = empty($pid) ? 'id' : $pid;
            $field = $tbl == 'users' ? 'id' : 'user_id';
            $str = "SELECT * FROM $tbl WHERE $field = $uid";
            $rows = $db->loadArray($str);
            foreach($rows as $el){
                if($db->doDb('archive')->insertArray($tbl, $el, null, false, false)){
                    if($delete){
                        $str = "DELETE FROM $tbl WHERE $pid = {$el[$pid]}";
                        echo "$str\n";
                        $db->query($str);
                    }
                }else
                    echo "Insert into $tbl archive failed\n";
            }
        }
    }

    // TODO henrik remove
    function syncArchive(){
        return 0;

        $archived = phive('SQL')->doDb('archive')->loadArray("SELECT id FROM users");
        $cnt = 0;
        foreach($archived as $ua){
            $u = ud($ua['id']);
            if(!empty($u)){
                $cnt++;
                $this->deleteUser($u['id']);
            }
        }
        echo $cnt;
    }


    // TODO henrik remove
    function unarchiveUser($uid, $enforce_username = false)
    {
          return false;

        if (empty($uid))
            return false;
        $ud = $this->getFromArchive($uid, $enforce_username);
        if (empty($ud))
            return false;
        $uid = $ud['id'];
        $db = phive('SQL');

        //First I do users to check if there is an issue inserting in the master, then I don't do the rest
        $el = $db->doDb('archive')->loadAssoc("SELECT * FROM users WHERE id = $uid");
        // Shard insert
        $res1 = $db->sh($uid)->insertArray('users', $el, null, false, false);

        lic('onUserCreatedOrUpdated', [$uid, $el]);

        if ($res1 === false) {
            //Prevent unarchiving if master insert fails
            phive()->dumpTbl("unarchive", "Unarchive failed for {$uid}");
            return false;
        }
        // Master insert
        $res2 = $db->insertArray('users', $el, null, false, false);
        // If both the master and shard node insert worked we delete from the archive
        if ($res1 && $res2)
            $db->doDb('archive')->query("DELETE FROM users WHERE id = $uid");

        foreach ($this->getArchiveTbls() as $tbl => $pid) {
            if ($tbl != 'users') {
                $field = 'user_id';
                $pid = empty($pid) ? 'id' : $pid;
                $str = "SELECT * FROM $tbl WHERE $field = $uid";
                $rows = $db->doDb('archive')->loadArray($str);
                foreach ($rows as $el) {
                    if ($db->sh($uid)->insertArray($tbl, $el, null, false, false)) {
                        $str = "DELETE FROM $tbl WHERE $pid = {$el[$pid]}";
                        //echo "$str\n";
                        $db->doDb('archive')->query($str);
                    }
                }
            }
        }

        $user = cu($ud['id']);
        if (is_object($user)) {
            // user unarchived, but might be missing documents
            $this->createDocumentsForUnarchivedUsers($user);
        }
        return true;
    }


    // TODO henrik remove
    function createDocumentsForUnarchivedUsers($user)
    {
        // user unarchived, but might be missing documents
        if(phive('UserHandler')->getSetting('create_requested_documents')) {
            // Create empty documents in dmapi for ID, Address and Bank
            phive('Dmapi')->createEmptyDocument($user->getId(), 'idcard-pic');
            phive('Dmapi')->createEmptyDocument($user->getId(), 'addresspic');
            phive('Dmapi')->createEmptyDocument($user->getId(), 'bankpic');

            // Check if we have missing documents for deposit methods
            $sql = "SELECT * FROM deposits WHERE user_id = {$user->getId()}";
            $deposits = phive('SQL')->sh($user, 'id', 'deposits')->loadArray($sql);
            foreach($deposits as $deposit) {
                $document_type = phive('Dmapi')->getDocumentTypeFromMap($deposit['dep_type'], $deposit['scheme']);
                if(!empty($document_type) && $document_type != 'creditcardpic') {
                    phive('Dmapi')->createEmptyDocument($user->getId(), cleanShellArg($document_type));
                }
            }
        }
    }

    // TODO henrik remove
    function getFromArchive($username, $enforce_username = false){
        return [];

        if($enforce_username)
            $key = 'username';
        else
            $key = is_numeric($username) ? 'id' : 'username';
        return phive('SQL')->doDb('archive')->loadAssoc('', 'users', array($key => $username));
    }

    //TODO henrik remove this
    function archiveUsers(){
          $db   = phive('SQL');
          foreach($this->getArchiveTbls() as $tbl => $pid)
              $db->updateTblSchema($tbl);
          $p    = phive('Permission');
          $aff  = phive('Affiliater');
          $date = phive()->hisMod('-18 month');
          $str  = "SELECT id FROM users WHERE last_login < '$date' AND id NOT IN(SELECT user_id FROM users_daily_stats)";
          $uids = $db->load1DArr($str, 'id');
          foreach($uids as $uid){
              $user = cu($uid);
              $aff_info = $aff->getAffInfo($uid);
              $balance = $user->getBalance();
              if((int)$p->permissionCount($user) !== 0 || !empty($aff_info) || !empty($balance))
                  continue;
              echo "$uid\n";
              $this->archiveUser($uid);
          }
    }

    //TODO henrik remove this
    function archiveActions(){
        phive('SQL')->archiveTable('actions');
    }

    /**
     * Inserts a row into the actions table, not that a system user needs to be present in the database as
     * that user is used as default in various context such as cron jobs.
     *
     * @link https://wiki.videoslots.com/index.php?title=DB_table_actions The wiki page for the actions table.
     *
     * @param mixed $target An element containing id information for the target user to create the action for.
     * @param string $descr Description of the logged action that contains more info than the tag can convey.
     * @param string $tag Action tag.
     * @param bool $add_uname Whether or not to add the username of the actor to the description.
     * @param mixed $actor An element containing id information for the actor user that executed this action.
     *
     * @return int|bool The id of the inserted action or false in case the action could not be created.
     */
    function logAction($target, $descr = '', $tag = '', $add_uname = false, $actor = null){
        $target = cu($target);
        if(empty($target))
            return false;

        $target_id = $target->getId();

        $actor = empty($actor) ? cu() : cu($actor);

        if(empty($actor)){
            $actor_id = uid('system');
            $actor_uname = 'system';
        }else{
            $actor_id = $actor->getId();
            $actor_uname = $actor->getUsername();
        }

        if($actor_id == $target_id){
            $target->updateSession();
        }

        $insert = array(
            'actor' 	       => $actor_id,
            'target' 	       => $target_id,
            'descr'	       => $add_uname ? $actor_uname." $descr" : $descr,
            'tag'	       => $tag,
            'actor_username' => mb_strimwidth($actor_uname, 0, 25)
        );

        return phive('SQL')->sh($target_id, '', 'actions')->insertArray('actions', $insert);
    }

    /**
     * @param $user_id
     * @param $cur
     * @param $postfix
     * @return array
     */
    public function validateUserToCurrency($user_id, $cur, $postfix)
    {
        if (empty($cur)) {
            return [false, 'Error CC1 - Currency is missing.'];
        }

        $user = cu($user_id);
        if (!is_object($user)) {
            return [false, "Error CC2 - Customer {$user_id} not found."];
        }

        if (preg_match('|@.*' . $postfix . '|', $user->getAttribute('email'))) {
            return [false, 'Error CC3 - Customer has changed currency already once.'];
        }

        if ($user->getCurrency() == $cur) {
            return [false, 'Error CC4 - Currency is the same as the one in the account.'];
        }

        $forced_currency = lic('getForcedCurrency', [], $user);
        if (!empty($forced_currency) && $forced_currency !== $cur) {
            return [false, 'Error CC5 - Customer jurisdiction does not allow currency changes.'];
        }

        if ($user->getSetting('mvcur_new_id')) {
            return [false, 'Error CC6 - Customer has changed currency already once.'];
        }

        if ($user->getSetting('currency_move_status') === CurrencyMoveStatus::INITIATED) {
            return [false, 'Error CC7 - Currency change for this customer is already in progress.'];
        }

        return [true, $user];
    }

    function getUpdatedPostfix(string $username, string $postfix): string {

        $postfixValue = $postfix;

        $query = "SELECT username FROM users u WHERE username like '{$username}%' ORDER BY id DESC LIMIT 1";

        $old_users = phive('SQL')->shs()->load1DArr($query, 'username');

        if(!empty($old_users)) {
            $splitArray = explode("_", $old_users[0]);

            $lastValue = end($splitArray);

            if ($lastValue === "old") {
                $postfixValue = "_old_1";
            } elseif (is_numeric($lastValue)) {
                $nextValue = number_format($lastValue) + 1;
                $postfixValue = "_old_" . $nextValue;
            }
        }
        return $postfixValue;
    }

    /**
     * Moves a user from one currency to another, old data is not moved, such as bets and wins. A new user with the new
     * currency is created and the old user is locked and retired.
     *
     * @param mixed $user_id Identifying element for the user to move.
     * @param string $cur ISO2 currency code of the currency to move to.
     * @param string $postfix The postfix to use for the old user's attributes such as email in order to prevent duplicates.
     * @param bool $debug Whether or not to output debug information, this is handy when running manually on the CLI in order to keep
     * track of what is going on and the progress.
     *
     * @return DBUser|array The new user.
     */
    public function moveUserToCurrency($user_id, $cur, $postfix = '_old', $debug = true){

        $debug = function ($string = '') use ($debug){
            if ($debug) {
                echo "$string\n";
            }
        };

        $sql = phive("SQL");

        [$status, $res] = $this->validateUserToCurrency($user_id, $cur, $postfix);
        if ($status === false) {
            $user = cu($user_id);
            if ($user) {
                $user->setSetting('currency_move_status', CurrencyMoveStatus::FAILED, false);
                $user->setSetting('currency_move_status_fail_reason', $res, false);
            }

            return [$status, $res];
        }

        /** @var DBUser $user **/
        $user = $res;

        $user->setSetting('currency_move_status', CurrencyMoveStatus::INITIATED, false);

        $insert = $update = $user->data;
        $old_id = $update['id'];

        $db = $sql->sh($old_id);

        // The users table on each shard does not have the proper primary key offset as they are inserted by way of
        // an id generated in the master all the time, therefore we need to get the latest ID and increase it with the
        // shard count.
        $new_id = $db->getValue("SELECT id FROM users ORDER BY id DESC LIMIT 1") + $sql->shCount();

        $mobile_without_postfix = $insert['mobile'];
        $email_without_postfix = $insert['email'];
        $username_without_postfix = $insert['username'];

        $new_postfix = phive()->uuid();

        $insert['id'] = $new_id;
        $insert['currency'] = $cur;
        $insert['cash_balance'] = 0;
        // Add temporal postfixes to new user data, so values won't clash with old user data
        $insert['mobile'] .= $new_postfix;
        $insert['email'] .= $new_postfix;
        $insert['username'] .= $new_postfix;

        // We insert the new user into the master
        $master_insert_result = $sql->insertArray('users', $insert);

        if ($master_insert_result === false) {
            $res = 'Error CC8 - Failed insert into master.';

            $user->setSetting('currency_move_status', CurrencyMoveStatus::FAILED, false);
            $user->setSetting('currency_move_status_fail_reason', $res, false);

            return [false, $res];
        }

        $updatedUsername = $update['username'].$postfix;
        $postfix = $this->getUpdatedPostfix($updatedUsername, $postfix);

        $update['mobile'] .= $postfix;
        $update['email'] .= $postfix;
        $update['username'] .= $postfix;

        $update['password'] .= '_old';
        $update['active'] = 0;
        $update['bonus_code'] = '';

        $old_balance = $update['cash_balance'];
        $old_cur = $update['currency'];
        unset($update['cash_balance']);

        //We unset the nid on the old account if there
        if(!empty($update['nid'])) {
            $update['nid'] = '';
        }

        // We save in the shard as well as the master.
        $sql->sh($user, 'id')->save('users', $update);
        $sql->save('users', $update);

        lic('onUserCreatedOrUpdated', [$user_id, $update, $user ? $user->getData() : []]);

        // Logout user to prevent further transactions on the old account after the move.
        $this->logoutUser($old_id);

        phive("Casino")->changeBalance($user, -$old_balance, 'Currency change', 13);

        $insert['mobile'] = $mobile_without_postfix;
        $insert['email'] = $email_without_postfix;
        $insert['username'] = $username_without_postfix;

        // We insert into the same shard as the old
        $db->insertArray('users', $insert);

        // Save new user values without postfix into master
        $sql->save('users', $insert);

        $new_user = cu($new_id);

        lic('onUserCreatedOrUpdated', [$new_id, $insert]);

        // We add 2% to avoid any arguments about FX rates
        $balance = phive("Casino")->changeBalance($new_user, chg($update['currency'], $cur, $old_balance, 1.02), 'Currency change', 13);

        $map = ['actions' => 'target', 'ip_log' => 'target'];
        //avoid updating these fields(in db: "CURRENT_TIMESTAMP | on update CURRENT_TIMESTAMP") ,
        $keep_existing_timestamp_fields = [
            'users_game_sessions' => 'start_time',
            'bonus_entries' => 'last_change',
            'vouchers' => 'redeem_stamp',
            'rg_limits' => 'updated_at'
        ];

        $tbls = [
            'actions',
            'ip_log',
            'trophy_events',
            'trophy_award_ownership',
            'tournament_entries',
            'users_settings',
            'bonus_entries',
            'race_entries',
            'users_sessions',
            'vouchers',
            'first_deposits',
            'users_game_sessions',
            'users_comments',
            'users_games_favs',
            'users_blocked',
            'user_flags',
            'triggers_log',
            'risk_profile_rating_log',
            'users_notifications',
            'rg_limits',
            'queued_transactions',
            'jackpot_wheel_log',
            'users_game_filters'
        ];

        $udb = phive('SQL')->sh($old_id, '', '', true);

        $debug("starting with tables");
        foreach ($tbls as $tbl) {

            $debug($tbl);

            $col = $map[$tbl];
            if(empty($col)) {
                $col = 'user_id';
            }

            $db->applyToRows("SELECT * FROM $tbl WHERE $col = $old_id", function ($r) use ($udb, $tbl, $new_id, $col, $keep_existing_timestamp_fields) {
                $update = [$col => $new_id];
                $timestamp_col = $keep_existing_timestamp_fields[$tbl];
                if (!empty($timestamp_col)) {
                    $update[$timestamp_col] = $r[$timestamp_col];
                }

                // Don't copy mvcur_old_id and mvcur_stamp settings to new user, so we can keep track of currency change history
                if ($tbl === 'users_settings' && in_array($r['setting'], ['mvcur_old_id', 'mvcur_stamp'])) {
                    return false;
                }

                $udb->updateArray($tbl, $update, ['id' => $r['id']]);
            });
        }
        $debug("tables done - starting with limits");

          // Convert rg_limits from user currency to new currency
        $in_str = $db->makeIn(rgLimits()->getMoneyLimits());
        $limits = $db->loadArray("SELECT * FROM rg_limits WHERE user_id = $new_id AND type IN ($in_str)");
        foreach($limits as $limit){
            $new_limit = $limit;
            foreach(['cur_lim', 'new_lim', 'progress'] as $col){
                $new_limit[$col] = chg($old_cur, $cur, $limit[$col], 1);
            }
            $db->updateArray('rg_limits', $new_limit, ['id' => $new_limit['id']]);
        }
        $debug("limits done - starting with queued transactions");
        //Convert queued_transactions from user currency to new currency for Weekend Booster
        $qts = $db->loadArray("SELECT * FROM queued_transactions WHERE user_id = $new_id");
        foreach($qts as $qt){
            $qt['amount'] = chg($old_cur, $cur, $qt['amount'], 1);
            $db->updateArray('queued_transactions', $qt, ['id' => $qt['id']]);
        }

        // We prevent them from doing repeat deposits as those would be executed using the old currency
        $new_user->deleteSettings('n-quick-deposits-limit', 'n-quick-deposits');
        $dmapi = new Dmapi();
        $dmapi->changeUserId($old_id, $new_id);
        Mts::getInstance()->changeCardOwner($old_id, $new_id);

        $this->logAction($old_id, "Changed currency, new user id: $new_id.", "changed_currency");
        $this->logAction($new_id, "Changed currency, old user id: $old_id.", "changed_currency");

        $user->setSetting('mvcur_new_id', $new_user->getId());
        $user->setSetting('currency_move_status', CurrencyMoveStatus::FINISHED, false);
        $user->deleteSetting('currency_move_status_fail_reason');

        $new_user->setSetting('mvcur_old_id', $user->getId());
        $new_user->setSetting('mvcur_stamp', phive()->hisNow());
        $new_user->setSetting('currency_changed_from', $old_cur);
        $new_user->setSetting('currency_changed_to', $cur);

        // Send email to the user to inform him of the currency change.
        $mh = phive('MailHandler2');
        $replacers = $mh->getDefaultReplacers($new_user);
        $conf = $mh->getSetting('prio_config');
        $from = empty($conf) ? null : $conf['from_email'];

        // TODO uncomment the below line if we start using this thing for real from the BO again.
        // $mailresult = $mh->sendMail('changed-currency', $new_user, $replacers, $new_user->getLang(), $from, $from, null, null, 0);

        $debug("Currency move done");

        return [true, $new_user];
    }

    /**
     * Checks if a user's session has timed out by querying the Redis cluster directly.
     *
     * @param int|string $uid User id or Redis key.
     * @param int $timeout_seconds
     *
     * @return bool True if user has timed out, false otherwise.
     */
    public function hasTimedOut($uid, $timeout_seconds = 1800) {
        $key = is_numeric($uid) ? mKey($uid, 'uaccess') : $uid;
        $ttl = mCluster('uaccess')->ttl($key);
        if(empty($ttl)) {
            return false;
        }
        return $ttl > (int)$timeout_seconds ? false : true;
    }

    /**
     *  Here we timeout the session with the time that was set on session creation @see setSid()
     */
    public function timeoutSessions($logId = "na")
    {
        try {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutSessions start " . $logId);

            $session_manager = phive()->getSessManager();
            $memory_cluster = mCluster('uaccess');

            foreach ($memory_cluster->keys('*uaccess') as $key) {
                $result = explode(':', $memory_cluster->get($key));

                if (!empty($result) && count($result) > 1) {
                    $session_id = $result[0];
                    $timeout_seconds = $result[1];
                } else {
                    $session_id = $result[0] ?? '';
                    $timeout_seconds = phive()->getSetting('default_sess_timeout', 1800);
                }
                if (!$this->hasTimedOut($key, $timeout_seconds)) {
                    continue;
                }

                $uid = getMuid($key);

                if (!empty($uid)) {
                    phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutSessions temp, uid: " . $uid . " key: " . $key . " gen:" . $logId);
                    // Remove from set
                    $this->logLogout($uid, 'timeout');
                    $session_manager->destroy($session_id);
                }

                $memory_cluster->del($key);
            }
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutSessions end " . $logId);
        } catch (\Throwable $e) {
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::timeoutSessions "  . $logId, [$e]);
        }
    }

    /**
     * Logs out a user with just the user id, typically used when the session id is not available such as in a
     * cron job context or admin / BO context.
     *
     * @param int $uid The user id.
     * @param string $reason Logout reason, will be displayed by way of WS to the user.
     */
    public function logoutUser($uid, $reason = 'forced logout'){
        $sid = getSid($uid);
        if(!empty($sid)){
            $this->logLogout($uid, $reason);
            $sm = phive()->getSessManager();
            $sm->destroy($sid);
            mCluster('uaccess')->del(mKey($uid, 'uaccess'));
        }
    }

    /**
     * Logs a user out needs to be able to access the current session to logout from so is typically called
     * by way of a user action.
     *
     * @param string $reason Logout reason.
     * @param bool $redir Whether or not to redirect to the homepage.
     * @param bool $destroy_session Whether or not to completely destroy the current session (true) or just erase all the data (false).
     * @param LoginCommonData|null $request Request object
     * @param bool $preserve_csrf_token Whether to preserve the CSRF token in the session after logout.
     *
     * @return bool True if end of method was reached.
     */
    function logout(
        $reason = 'logout',
        $redir = false,
        $destroy_session = true,
        ?LoginCommonData $request = null,
        bool $preserve_csrf_token = false
    ) {

        if (empty($this->currentUser)) {
            $this->currentUser = cu();
        }
        $previous_session = false;
        if(is_object($this->currentUser)){
            if (!$_SESSION['OBSOLETE']) {
                $this->currentUser->endSession($reason, false,$previous_session); // this happen inside logLogout too, see if the current logic can be improved. Do not remove for now!! /Paolo
                $uid = $this->currentUser->getId();
                $this->logLogout($uid, $reason);
                mCluster('uaccess')->del(mKey($uid, 'uaccess'));
            }
        }

        $csrf_token = $_SESSION['token'] ?? null;

        parent::logout();

        if(phive()->getSetting('sess_handler') != 'file' && !empty(phive()->sess) && $destroy_session){
            phive()->sess->destroy();
        } else {
            // The cookie / session id is maintained in case the caller explicitly wants it for some reason.
            // But we have to get rid of the session data otherwise we don't logout at all.
            $_SESSION = [];

            // In some cases we want to keep the CSRF token in the session to avoid token mismatch popup shown to the user.
            // E.g. we call logout() function if user gets error during login.
            // if user tries to login again without page reload - the token mismatch popup is shown.
            // By preserving CSRF token we avoid this behavior.
            if ($preserve_csrf_token) {
                $_SESSION['token'] = $csrf_token;
            }
        }

        if(!is_null($request) && $request->getIsApi()){
            [$uid,,] = explode('|', $request->getAuthToken(), 3);
            phive('SQL')->sh($uid)->delete('personal_access_tokens', ['token' => $request->getEncryptedAuthToken()]);
        }

        if($redir)
            phive('Redirect')->to('');

        return true;
    }

    /**
     * For parameters explanation @see logout()
     */
    public function logoutAndPreserveCsrfToken($reason = 'logout', $redir = false, LoginCommonData $request = null): bool {
        return $this->logout($reason, $redir, false, $request, true);
    }

    /**
     * Housekeeping common to various logout and login scenarios, mostly related to Redis data which is separate from the
     * session data that needs to be cleared out on logout.
     *
     * @param int $uid The user id.
     * @param string $action **in** or **out**.
     * @param string $reason Logout reason.
     * @param bool $previous_session Whether or not to update the previous session or not.
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData $request
     *
     * @return null
     */
    function logInOut($uid, $action, $reason = '', $previous_session = false, LoginCommonData $request = null){
        if($action == 'out'){
            $u = cu($uid);
            if(empty($u))
                return;
            $u->setAttr('logged_in', 0);
            $u->setAttribute('last_logout', phive()->hisNow());
            $u->mSet('earned-loyalty', 0);
            $u->mDel('events');
            $u->mDel('winloss');
            $u->mDel('current-client');

            rgLimits()->resetRc($u, [], true);

            lic('cleanUniqueGameSession', [$u], $u);

            phive('Casino')->finishGameSession($uid);

            $u->endSession($reason, true, (bool) $previous_session);

            phive('SQL')->sh($uid)->query(
                "DELETE FROM users_settings WHERE `user_id` = {$uid} AND `setting` LIKE 'popup-shown-%'"
            );
            $timeout_content = t('timeout.reason.'.str_replace(' ', '.', $reason), $u->getLang());
            if(empty($timeout_content)){
                error_log("Fatal error, no content for this reason: $reason, user id: {$u->getLang()}, check with Henrik.");
                $timeout_content = 'unknown';
            }
            if(empty($_SESSION['skip_websocket_logout']) && empty($_SESSION['skip_pnp_logout']) && $previous_session) {
                toWs(t2('msg.logged.out', [$timeout_content], $u->getLang()), 'logoutmsg'.$uid, 'na');
                //Sometimes first message is not delivered properly. This is follow up message with 5 seconds delay
                $this->queueLogoutMessage($timeout_content, $u->getLang(), $previous_session, 5000);
            }
        }else{
            if(phive()->moduleExists('Trophy'))
                phive('Trophy')->onEvent('login', $uid, 5000000);
            phMdel(mKey($uid, 'winloss'));
            //dev_num is 0 for desktop and 1 for mobile, can be used directly with device_type_num in micro_games everywhere non-cli
            phMset(mKey($uid, 'current-client'), phive()->getCurrentDeviceNum(), 86400);
        }

        phive('DBUserHandler/PrivacyHandler')->onLogInOut($uid, $action == 'out');
    }

    /**
     * Queue a logout message to be sent via WebSocket
     *
     * @param string $timeout_content The timeout content message
     * @param string $lang The user's language
     * @param string|bool $previous_session The previous session ID
     * @return void
     */
    private function queueLogoutMessage($timeout_content, $lang, $previous_session, $delay = 0)
    {
        $message = t2('msg.logged.out', [$timeout_content], $lang);
        $tag = 'logoutmsg'.substr($previous_session, 0, 5);

        phiveApp(EventPublisherInterface::class)
            ->fire('authentication', 'AuthenticationLogoutEvent', [
                'message' => $message,
                'tag' => $tag,
                'uid' => 'na'
            ], $delay);
    }

    /**
     * Wrapper around logInOut() for the logout scenario.
     *
     * @param int $user_id The user id to log out.
     * @param string $reason The logout reason.
     *
     * @return null
     */
    function logLogout($user_id, $reason = '') {
        $this->logInOut($user_id, 'out', $reason);
    }

    /**
     * We need to take care of closing the old session before setting "logged_in" to 1 on the user table,
     * otherwise if an old session exist when doing "logInOut - out" to end the previous session
     * we are setting that attribute to "0" on the currently logged in user.
     *
     * @param null $username TODO henrik remove this, refactor invocations.
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData $request
     *
     * @return null
     */
    public function loginSuccessful($username = null, LoginCommonData $request = null)
    {
        $uid = $this->currentUser->getId();

        if (phive()->getSetting('sess_handler') != 'file' && $this->currentUser->getUsername() != 'admin') {
            if(is_null($request) || !$request->getIsApi()) {
                $old_sid = phive()->sess->setUid($uid);
            } else {
                $session_key = mKey($uid, 'session');
                $old_sid = phMget($session_key);
                if(!empty($old_sid)) {
                    phMdel($old_sid);
                    phMdel($session_key);
                    phMdel("sessionuid-$old_sid");
                }
                $new_sid = 'token|'.$request->getEncryptedAuthToken();
                phMset($session_key, $new_sid);
                phMset("sessionuid-" . $new_sid, $uid);
            }

            if (!empty($old_sid)) {
                $old_session_data = explode('|', $old_sid, 2);
                if($old_session_data[0] == 'token') {
                    phive('SQL')->sh($uid)->delete('personal_access_tokens', ['token' => $old_session_data[1]]);
                }
                // in this case we need to target the previous session.
                $this->logInOut($uid, 'out', 'login from different client', $old_sid);
            }
        }

        $this->currentUser->setAttribute('last_login', phive()->hisNow());

        $ip = $request ? $request->getIp() : remIp();
        if(isPNP() && !is_null($request)){
            $ip = phive('PayNPlay')->getIp($request->getPnpTransactionId());
        }

        $this->setCurrentIp($ip);
        $this->currentUser->setAttribute('logged_in', 1);
        $this->currentUser->setSetting('uagent', $_SERVER['HTTP_USER_AGENT']);
        if ($GLOBALS['no-session-refresh'] !== true) {
            $session_id = (is_null($request) || !$request->getIsApi()) ? session_id() : $request->getEncryptedAuthToken();
            setSid($session_id, $uid, $this->currentUser);
        }
        phMset(mKey($uid, 'earned-loyalty'), 0);
        if (!$this->currentUser->hasSetting('registration_in_progress')) {
            lic('onSuccessfulLogin', [$this->currentUser], $this->currentUser);
            if ($this->currentUser->hasSetting('brand_check_in_progress')) {
                $this->getSitePublisher()
                    ->fire('distributed-retry', 'Site/Linker', 'brandLink', [uid($this->currentUser)]);
            }
        }

        $this->logInOut($uid, 'in');
    }

    /**
     * Saves the current user IP and logs the country where this IP belongs to
     *
     * @param string|null $ip
     * @return void
     */
    public function setCurrentIp(?string $ip = null): void
    {
        $cur_ip = $ip ?? remIp();
        // We try to get the country first from maxmind
        $cur_ip_country = $cur_ip == '127.0.0.1' ? 'JP' : phive('IpBlock')->getGeoCountryRecord($cur_ip)->country->isoCode;
        // If we don't get anything we log not found
        $country = $cur_ip_country ?: 'No country-IP match';

        $this->currentUser->setAttribute('cur_ip', $cur_ip, false, $country);
    }

    /**
     * Common logic that defines if the user is forced to complete a deposit before being able to perform other actions.
     * The logic is controlled via a DB config "on/off", and doesn't apply for "test_accounts" or "admin users".
     *
     * The function will return FALSE if the user is already prevented from performing actions cause of other flags.
     * (Ex. not latest T&C or PP, restricted, deposit blocked, etc..)
     *
     * @param null|int|DBUser $u
     * @return bool
     */
    public function doForceDeposit($u = null){
        $u = empty($u) ? cuPl() : $u;

        if(empty($u)){
            return false;
        }

        if (!phive('DBUserHandler/PrivacyHandler')->hasPrivacySettings($u)) return false;

        if(lic('showRgLimitsOnRegistrationComplete', [$u], $u)) {
            return false;
        }

        if(
            phive('Config')->getValue('deposit', 'force') != 'on'
            || $u->isTestAccount() || privileged($u) || !$u->hasCurTc() || !$u->hasCurPp()
            || (lic('isSportsbookEnabled') && !$u->hasCurTcSports())
            || (lic('hasBonusTermsConditions') && !$u->hasCurBtc())
        ) {
            return false;
        }

        if (
            $u->isDepositBlocked() || $u->isRestricted() || $u->hasSetting('experian_block')
            || $u->hasDeposited() || $u->hasSetting('tac_block')
            || $u->hasSetting('tac_block_sports') || $u->hasSetting('bonus_tac_block')
        ) {
            return false;
        }

        if(phive('Config')->isCountryIn('deposit', 'force-exempt', $u->getCountry())){
            return false;
        }

        return true;
    }

    /**
     * Logic executed to finalise the registration, this is considered the last step of the flow even if the account
     * has been blocked before (i.e. on the login done before this step on registration) or in the normal flow.
     *
     * Prevent similarity check on new accounts associated to indefinitely closed accounts
     *
     * @param DBUser $user
     * @param bool $skip_fraud_checks
     * @param bool $is_api
     * @param array $request_data
     *
     * @return array
     */
    public function registrationEnd($user, $skip_fraud_checks = false, bool $is_api = false, array $request_data = [])
    {
        phive('DBUserHandler/Booster')->initBoosterVault($user);

        lic('onRegistrationEnd', [$user], $user);

        // We do not update field `register_date` when user completes registration. User can complete registration in 2 or more days
        // That's why we should have `registration_end_date` setting. It is used in RUD & RUT reports (Spain). It might be useful for other jurisdictions as well
        $user->setSetting('registration_end_date', date('Y-m-d h:i:s'));
        $user->setSetting('jurisdiction', $user->getCalculatedJurisdiction());

        if ($user->getAttr('register_date') <= $this->getSetting('registration_unclosed_cutoff', '2020-10-28')) {
            lic('checkKyc', [$user], $user);
            $user->deleteSetting('registration_in_progress');
            return [$user, 'ok'];
        }

        if(!$is_api){
            $user->setTrackingEvent('registered', ['triggered' => 'yes', 'model' => 'users', 'model_id' => $user->getId()]);
        }

        $do_fraud = phive('Config')->getValue('lga', 'reg-fraud-check');
        $ud = $user->data;
        $fraud_msg = 'ok';

        if($skip_fraud_checks === false && $do_fraud == 'yes' && !$user->hasSetting('id_before_exclusion')) {
            $fraud_msg = phive('UserHandler')->lgaFraudCheck($ud);
        }

        if($fraud_msg == 'ok') {
            $recurrent = isPNP();
            phive('Logger')->getLogger('registration')->debug("DBUserHandler::registrationEnd", [phive('Licensed')->getLicCountry()]);
            lic('checkKyc', [$user, $recurrent], $user);
        } else {
            $user->deleteSetting('registration_in_progress');
            $this->logAction(
                $user,
                "Fraud check failed, fraud message: " . $fraud_msg,
                "fraud_check_failed"
            );
            return [$user, $fraud_msg];
        }

        if (phive('Distributed')->getSetting('check_remote_brand') === true) {
            $cross_brand = licSetting('cross_brand', $user);
            if ($cross_brand && !empty($cross_brand['do_brand_link'])) {
                $user->setSetting('brand_check_in_progress', 1);
                phiveApp(EventPublisherInterface::class)
                    ->fire(
                        'authentication',
                        'AuthenticationBrandLinkEvent',
                        [uid($user), empty($cross_brand['check_self_exclusion']) ? 'no' : 'yes'],
                        0
                    );
            }
        }

        /**
         * registration is uncompleted utils user details popups got submitted, so we have to keep
         * registration_in_progress setting
         */

        if(!isPNP()) {
          $user->deleteSetting('registration_in_progress');
        }

        uEvent('openaccount', '', '', '', $user->data);

        $user->setTcVersion();
        $user->setPpVersion();
        if (lic('isSportsbookEnabled')) {
            $user->setSportTcVersion();
        }

        return [$user, 'ok'];
    }

    /**
     * Wrapper around DBUser::accUrl() to prevent a fatal error in case the user's session has timed out etc.
     *
     * @uses DBUser::accUrl()
     *
     * @param int $uid The user id.
     *
     * @return string The account URL in case all went well, empty string otherwise.
     */
    function accUrl($uid = null){
        $u = cu($uid);
        if(!empty($u))
            return cu($uid)->accUrl();
        return '';
    }

    /**
     * Will return the common url for the user account taking into account platform (desktop/mobile) and language
     * if the $page is specified that will be appended at the end.
     *
     * @param integer|DBUser $user - user id or user instance
     * @param string $acc_page - the requested account page (Ex. profile, game-history, documents, ...) if empty goes to 'my-profile'
     * @param string $lang - pass this if you want a specific language and is not the current one
     * @param string|boolean $force_platform - pass this if you want force the link a platform (mobile/desktop) - false will autodetect (only for mobile/desktop)
     * @return string full url to requested page.
     */
    public function getUserAccountUrl($acc_page = '', $lang = null, $force_platform = false, $user = null) {

        $map = [
            'desktop' => '/account/',
            'mobile' => '/mobile/account/',
        ];

        if (!empty($force_platform) && array_key_exists($force_platform, $map)) {
            $prefix = $map[$force_platform];
        } else {
            $prefix = $map[phive()->isMobile() ? 'mobile' : 'desktop'];
        }

        if (empty($uid = uid($user))) {
            return phive()->getSiteUrl();
        }
        $uri = empty($acc_page) ? $prefix : $prefix . $uid . '/' . $acc_page . '/';

        return phive()->getSiteUrl() . llink($uri, $lang);
    }

    /**
     * As BO is not language dependant we create a different link.
     *
     * @param mixed $user Element with user identification data.
     * @param string $acc_page Sub page.
     * @return string The URL.
     */
    public function getBOAccountUrl($user = null, $acc_page = '')
    {

        if (empty($uid = uid($user))) {
            return phive()->getSiteUrl() . '/admin2/user/';
        }
        $base_uri = phive()->getSiteUrl() . "/admin2/userprofile/{$uid}/";

        return empty($acc_page) ? $base_uri : $base_uri . $acc_page . '/';
    }

    /**
     * Get welcome banner link
     *
     * @param string $type
     * @param bool $isApi
     * @return string|null
     *
     * @api
     */
    public function getBannerOverlayLink(string $type, bool $isApi = false): ?string
    {
        if (lic('hideRegistrationBannerLinks')) {
            return null;
        }

        $typeMapping = phive('Redirect')->getSetting('mapping_overlay_keys');
        $type = $typeMapping[$type] ?? $type;

        $jur = licJur();
        $jurKey = "overlay_link_{$type}_{$jur}";

        $sql = "SELECT attribute_name, attribute_value FROM boxes_attributes
                    WHERE box_id = '1210' AND (attribute_name = 'overlay_link_{$type}' OR attribute_name = '{$jurKey}')";
        $links = phive()->to1d(phive('SQL')->loadArray($sql), 'attribute_name', 'attribute_value');

        $jurLink = empty($links[$jurKey]) ? $links["overlay_link_{$type}"] : $links[$jurKey];

        return $isApi ? $jurLink : llink($jurLink);
    }

    /**
     * Gets settings from all nodes, typically used to power BO interfaces.
     *
     * @param string $sdate Start date.
     * @param string $edate End date.
     * @param string $setting The setting to aggregate.
     * @param string $setting_value If passed in the setting needs to have this value.
     * @param string|bool $by_month Controls how to aggregate and what to group on.
     * @param bool $has_aff True if we want settings belonging to people with a bonus code, false if we want everyone.
     * @param string $cur ISO2 currency to filter on.
     * @param string $where_extra Extra WHERE clauses.
     *
     * @return array The result array with settings.
     */
    function settingsByDate($sdate, $edate, $setting, $setting_value = '', $by_month = false, $has_aff = false, $cur = '', $where_extra = '', $province = ''){
        $sdate = empty($sdate) ? '2000-01-01' : $sdate;
        $edate = empty($edate) ? date('Y-m-d') : $edate;
        $settings_array = [];

        if($by_month === true){
            $group1 = ", COUNT(us.user_id) AS month_count, DATE_FORMAT(us.created_at, '%Y-%m') AS month_num";
            $group2 = 'GROUP BY month_num';
            $group_by = 'month_num';
        }else if($by_month == 'day'){
            $group1 = ', COUNT(us.user_id) AS day_count, DAYOFMONTH(us.created_at) AS day_num';
            $group2 = 'GROUP BY day_num';
            $group_by = 'day_num';
        }else if($by_month == 'date'){
            $group1 = ', COUNT(us.user_id) AS day_count, DATE(us.created_at) AS date';
            $group2 = 'GROUP BY date';
            $group_by = 'date';
        }else if(!empty($by_month)){
            $group1 = ", COUNT(us.user_id) AS {$by_month}_count";
            $group2 = "GROUP BY $by_month";
            $group_by = $by_month;
        }else
            $group_by = false;

        $join = "LEFT JOIN users AS u ON us.user_id = u.id";

        if($has_aff)
            $where_aff = "AND u.bonus_code != ''";

        if(!empty($cur))
            $where_cur = "AND u.currency = '$cur'";


        if($setting_value !== '')
            $settings_array[$setting] = $setting_value;

        if(!empty($province))
            $settings_array['main_province'] = $province;

        if(!empty($settings_array)){
            $settings = "('" . implode("', '", array_keys($settings_array)) . "')";
            $values = "('". implode("', '", array_values($settings_array)) . "')";
        }


        $str = "SELECT us.*$group1 FROM users_settings us
        $join
        WHERE DATE(us.created_at) >= '$sdate'
        AND DATE(us.created_at) <= '$edate'
        AND us.setting  IN $settings
        AND us.value IN $values
        $where_aff $where_cur $where_extra $group2";

        return phive('SQL')->shs(['action' => 'sum', 'do_not' => [$group_by]], '', null, 'users_settings')->loadArray($str, 'ASSOC', $group_by);
    }

    /**
     * Gets all the countries a user is allowed to login from.
     *
     * @param mixed $username User identifying element.
     *
     * @return array An array of settings with the allowed countries.
     */
    function getAllowedCountries($username) {
        $uid = ud($username)['id'];
        $str = "SELECT setting FROM users_settings WHERE user_id = {$uid} AND setting LIKE 'login-allowed-%'";
        return phive('SQL')->sh($uid, '', 'users_settings')->loadKeyValues($str, 'setting', 'setting');
    }

    // TODO henrik remove
    function getAllOccupations()
    {
        $sql = 'SELECT id, occupation_alias FROM occupations ORDER BY occupation_alias ASC';
        $occupations = phive('SQL')->loadArray($sql);

        return $occupations;
    }

    // TODO henrik remove
    function getOccupationSelect()
    {
        $occupations = $this->getAllOccupations();

        $occupations_select = array();
        foreach ($occupations as $key => $occupation) {
            $occupations_select[$occupation['id']] = t($occupation['occupation_alias']);
        }

        // add extra option 'Other: please specify'
        $occupations_select['other'] = t('other.please.specify');

        return $occupations_select;
    }

    /**
     * This method re-logs in the user for a new fresh session.
     *
     * @uses DBUserHandler::login() in order to re-login.
     *
     * @param string $username The login method needs a username.
     * @param string $password The login method needs a password.
     *
     * @return DBUser|string The created user object in case of successful login, an error string otherwise.
     */
    function reload($username, $password, $needpasswd = true){
        $username = empty($username) ? $_SESSION['mg_username'] : $username;
        $password = empty($password) ? $_SESSION['mg_password'] : $password;
        $this->logout('session reload');
        return $this->login($username, $password, false, $needpasswd);
    }

    /**
     * Is executed when a login happens via a code sent by way of SMS.
     *
     * @uses DBUserHandler::login() in order to login.
     *
     * @param string $username The login method needs a username.
     * @param string $password The login method needs a password.
     *
     * @return DBUser|string The created user object in case of successful login, an error string otherwise.
     */
    function smsLogin($username, $password){
        $uh = phive('UserHandler');
        if(!isLogged()) {
            $user = $uh->login($username, $password);
            $_SESSION['mg_username'] 	= $username;
            $_SESSION['mg_password'] 	= $password;
            $user->setAttrsToSession();
            $_SESSION['mg_id']		= $_SESSION['local_usr']['id'];
            phMset(mKey($user, 'login'), 'yes');
            $user->setTrackingEvent('logged-in', ['triggered' => 'yes']);
            uEvent('login');
            return $user;
        }
    }

    /**
     * A wrapper around UserHandler::login() used for logging in administrators, they don't need the full housekeeping
     * that players / users need.
     *
     * @uses UserHandler::login()
     *
     * @param string $username The user login username.
     * @param string $password The user password.
     * @param bool $needpasswd Whether ot not the password is needed in order to execute the login, sometimes it is
     * not needed when logging in with tokens etc, the validation has already taken place by other means.
     *
     * @return User|false The user object if login was successful, false otherwise.
     */
    function initUser($username = null, $password = null, $needpasswd = true){
        return parent::login($username, $password, $needpasswd);
    }

    /**
     * Wrapper around UserHandler::login() in order to login administrators, there is no neeed for all the housekeeping
     * involved with logging in a player / user.
     *
     * @uses UserHandler::login()
     *
     * @param string $username The admin username.
     * @param string $password The admin password.
     * @param bool $needpasswd Is a password needed or not?
     *
     * @return User|false The user object if login was successful, false otherwise.
     */
    public function adminLogin($username = null, $password = null, $needpasswd = true){
        $user = $this->getUserByUsername($username);
        return parent::login($username, $password, $needpasswd);
    }

    /**
     * Login via an XHR call typically expects a JSON return, this method handles that.
     *
     * @param bool|string|DBUser $login_res A login result, a user object if success, error string or false if fail.
     * @param array $action An array with methodname and args to pass to the method on the JS side upon return.
     * @param Closure $success_callback A callback to execute in case the login was successful.
     * @return array array The result array to convert to JSON and return to the FE / JS side.
     */
    function getLoginAjaxContextRes($login_res = null, $action = null, $success_callback = null)
    {
        $login_msg = '';
        $success = false;
        $redirect_url = null;

        if (is_object($login_res)) {
            /** @var DBUser $login_res */
            $success = true;
            $redirect_url = llink('/', $login_res->getLang());

            if ($success_callback) {
                $success_callback($login_res, $success, $login_msg);
            }
        } elseif (is_string($login_res)) {
            $login_msg = t("blocked.$login_res.html");
        } elseif (is_array($login_res)) {
            $login_msg = t2("blocked.$login_res[0].html", $login_res[1]);
        } elseif ($login_res == false) {
            $login_msg = t('login.error');
        }

        return [
            'success' => $success,
            'login_context' => true,
            'result' => [
                'msg' => $login_msg,
                'action' => $action,
                'redirect_url' => $redirect_url
            ]
        ];
    }

    /**
     * Return helper, will return just the result, ie DBUser if success or error message / false if fail if not in an XHR
     * context. If we are in an XHR context we return all arguments so that the JS caller knows what to do next.
     *
     * @return mixed DBUser in case of non XHR success, string / false in case of non XHR fail. Array in an XHR context.
     */
    private function handleAjax(...$args) {
        if (!empty($this->ajax_context)) {
            return $args;
        }
        return $args[0];
    }

    /**
     * Login user
     *
     * Context list:
     * - uname-pwd-login | normal login(username+password)
     * - otp-login | otp login(username+password+otp)
     * - uname-ext-service | external verification login(username)
     *
     * The login will fail in at least the following situations:
     *  - User is indefinitely/permanently self excluded
     *  - User tries to login from a restricted country
     *  - User can't be found in database
     *  - RG time limit reached
     *  - Wrong password
     *  - User id blocked/self excluded/external self excluded
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData $request
     * @param string $username The user login username.
     * @param string $password The user password.
     * @param bool $check_country Whether or not to check if the login attempt comes from the same country as the user
     * registered from.
     * @param bool $needpasswd Whether ot not the password is needed in order to execute the login, sometimes it is
     * not needed when logging in with tokens etc, the validation has already taken place by other means.
     * @param bool $from_registration Context flag, if true it means we're trying to auto login after registration is completed.
     *
     * @return DBUser|string The created user object in case of successful login, an error string otherwise.
     */
    public function loginCommon(LoginCommonData $request, $username = null, $password = null, $check_country = false, $needpasswd = true, $from_registration = false)
    {
        if (isLogged()) {
            return $this->handleAjax(cu());
        }

        phive()->sessionStart();

        $user = cu($username);

        $login_attempts = $this->getSetting('login_attempts');
        $is_otp_captcha_request = $this->isOtpCaptchaRequest($request, $user);

        // possible values: loginCommon.otp-captcha, loginCommon.token_login,  loginCommon.otp-login, loginCommon.uname-pwd-login,
        $key = $is_otp_captcha_request ? 'loginCommon.otp-captcha' : 'loginCommon.'. $request->getAction();

        if (!$request->getIsApi() && limitAttempts($key, $username, $login_attempts) === true) {
            return $this->handleAjax(['login_fail_attempts', ['attempts' => $login_attempts]]);
        }

        if (empty($user)) {
            $master_user = cu($username, null, true);
            $master_user_id = null;

            if (!empty($master_user)) {
                $master_user_id = $master_user->getId();
            }

            if (!empty($master_user_id) && phive('SQL')->isOnDisabledNode($master_user_id)) {
                return $this->handleAjax('node_down');
            }
        } elseif (phive('SQL')->isOnDisabledNode($user->getId())) {
            return $this->handleAjax('node_down');
        }

        $reg_country = $req_country = null;

        if (is_object($user)) {
            if (lic('loginBlocked', [$user], $user) === true) {
                phive()->dumpTbl('login-blocked-jurisdictional', $user->getId(), $user->getId());
                return $this->handleAjax(['login_fail_attempts', ['attempts' => $login_attempts]]);
            }

            $is_user_active = !empty($user->getAttribute('active'));
            if ($is_user_active && $user->isSuperBlocked()) {
                $user->superBlock();
                return $this->handleAjax('inactive');
            }

            if($user->isSelfLocked()){
                return $this->handleAjax('self_locked');
            }

            if($user->isSelfExcluded()){
                return $this->handleAjax('self_excluded');
            }

            if(isPNP() && $user->hasSetting('indefinitely-self-excluded')){
                return $this->handleAjax('self_excluded');
            }

            if ($user->hasSetting('indefinitely-self-excluded')) {
                return $this->handleAjax($from_registration ? 'excluded.permanent' : ['login_fail', ['attempts' => $login_attempts]]);
            }

            $active = (int)$user->getAttribute('active');

            if(!$active){
                return $this->handleAjax('inactive');
            }

            $remip = $request->getIp() ?: remIp();

            $is_whitelisted = isWhitelistedIp($remip) || $user->isTestAccount();

            $reg_country = $user->getCountry();
            $reg_province = $user->getMainProvince();
            $req_country = phiveApp(IpBlockInterface::class)->getCountry($remip);

            $wrong_country_error = function ($user, $reg_country, $req_country, $remip, $active, $req_type = null) use ($request) {
                $failedTag = 'failed_login_not_allowed_country';
                $actionTag = 'failed_login';

                if($req_type){
                    $failedTag.= '_'.$req_type;
                    $actionTag.= '_'.$req_type;
                }

                $this->logFailedLogin(
                    $user->getId(),
                    $user->getUsername(),
                    $reg_country,
                    $req_country,
                    $active,
                    $failedTag
                );

                $this->logAction(
                    $user,
                    "Trying to login from $req_country with IP $remip",
                    $actionTag
                );

                phive()->dumpTbl('wrong_country_ip', $remip, $user->getId());
                $this->logoutAndPreserveCsrfToken('wrong country', false, $request);
                return $this->handleAjax('country');
            };


            if (!$is_whitelisted && phive('Config')->isCountryIn('login', 'login-restricted', $req_country)) {
                return $wrong_country_error($user, $reg_country, $req_country, $remip, $active);
            }

            //Verification only for App requests
            if(!$is_whitelisted && phive()->isMobileApp() && !$this->isAppAllowedCountry($remip)){
                return $wrong_country_error($user, $reg_country, $req_country, $remip, $active, 'app');
            }

            if (!$is_whitelisted && $req_country != $reg_country && empty($active)) {
                phive()->dumpTbl('login-blocked-reg-ip-mismatch',
                    ['req_country' => $req_country, 'reg_country' => $reg_country], $user->getId());
                return $this->handleAjax(['login_fail_attempts', ['attempts' => $login_attempts]]);
            }

            if (!$is_whitelisted && !phive('DBUserHandler')->isRegistrationAndLoginAllowed($remip)) {
                phive()->dumpTbl('login-blocked-jurisdictional-ip', $user->getId(), $user->getId());
                return $this->handleAjax(['login_fail_attempts', ['attempts' => $login_attempts]]);
            }

            if (!$is_whitelisted && phive('Config')->getValue('login', 'enable-country-check') === 'yes') {
                // We check if country should be excluded from the country check.
                $excluded_countries = phive('Config')->valAsArray('login', 'exclude-from-country-check');
                if (in_array($reg_country, $excluded_countries)) {
                    $check_country = false;
                } else {
                    $check_country = true;
                }
            } else {
                $check_country = false;
            }

            $captcha_check_result = $this->handleLoginCaptchaCheck($request, $user, $req_country, $check_country);
            if (is_array($captcha_check_result)) {
                return $this->handleAjax(...$captcha_check_result);
            }

            // We check if the user should be login from a different domain p.e: .it or .es instead of .com
            $iso_domain_redirection = phiveApp(IpBlockInterface::class)->getIsoDomainRedirection($reg_country, $reg_province);
            if (!$is_whitelisted && $iso_domain_redirection) {
                return $this->handleAjax(null, [
                    "method" => "replaceUrl",
                    "params" => [$iso_domain_redirection . '?show_login=true']
                ]);

            }
        } else {
            if ($this->getSetting('check_remote_brand', false)) {
                $restricted_countries = phive('Config')->valAsArray('countries', 'transfer-blocked');
                $user_exist_on_brand = toRemote(getRemote(), 'userInBrand', [$username, phive('DBUserHandler')->encryptPassword($password), false, $restricted_countries]);
                if ($user_exist_on_brand['success'] == true) {
                    return $this->handleAjax('import_from_brand', [
                        "method" => "showImportFromBrand",
                        "params" => [
                            t('import.user.from.brand.title'),
                            t('import.user.from.brand.description'),
                            t('import.user.from.brand.yes'),
                            t('import.user.from.brand.no'),
                            phive('Casino')->getRegistrationUrl(false),
                        ]
                    ]);
                }
            }

            if ($this->getSetting('scale_back') === true) {
                $this->unarchiveUser($username);
                $user = cu($username);
            }

            if (!is_object($user)) {
                phive()->dumpTbl('login-failed-no-user', ['username' => $username]);
                return $this->handleAjax(['login_fail_attempts', ['attempts' => $login_attempts]]);
            }
        }

        // We stop people from trying to hack admin accounts in the frontend here.
        if (phive('Permission')->hasAnyPermission($user)) {
            $this->logoutAndPreserveCsrfToken('not_player', false, $request);
            return $this->handleAjax('not_player');
        }

        if (! $this->isCorrectLoginMethod($user, $request, $needpasswd)) {
            return $this->handleAjax('login_method_not_allowed');
        }

        $password_check_result = $this->handleLoginPasswordCheck($user, $password, $needpasswd);
        $is_password_correct = $password_check_result === true;

        $otp_check_result = $this->handleLoginOtpCheck($request, $user, $req_country, $is_password_correct, $is_otp_captcha_request);
        if (is_array($otp_check_result)) {
            return $this->handleAjax(...$otp_check_result);
        }

        if (! $is_password_correct) {
            return $this->handleAjax($password_check_result);
        }

        $lic_res = lic('onLogin', [$user, $request->getIsApi()], $user);
        if (is_string($lic_res)) {
            return $this->handleAjax($lic_res);
        } elseif (is_array($lic_res)){
            return $lic_res;
        }

        $IdScanVerificationEnabled = lic('IdScanVerificationEnabled', [$user], $user);
        $IdScanVerification = lic('IdScanVerification', [$user->getSetting('hashed_uuid')]);

        //we display display IDscan popup if check is necessary or result is failure
        if ($IdScanVerificationEnabled && ($IdScanVerification == 'check' || $IdScanVerification == 'failed') && !$request->getIsApi() && !$request->isPnp()) {
            $is_mobile = phive()->isMobile();
            $hashed_uid = $user->getSetting('hashed_uuid');
            $failure = '';
            if ($IdScanVerification == 'failed'){
                $failure = 'failure=1';
            }

            return $this->handleAjax('needs-idscan', [
                "method" => $is_mobile ? "goTo" : "showRegistrationBox",
                "params" => $is_mobile ? ["/mobile/register-idscan/?uid=$hashed_uid&$failure"] : ["/registration-idscan/?uid=$hashed_uid&$failure"]
            ]);
        }

        //display step2 for those who hasn't finished registration
        if (!$this->hasFinishedRegistrationStep2($user)) {
            return $this->handleAjax('go_to_step_two', !$request->getIsApi() ? $this->showRegistrationStep2($user, $password) : []);
        } else if ($this->isMigrating($user)) {
            //also for those who finished step 2 but are migrating from other Jurisdiction
            $this->prefillRegistrationStep2($user);
            return $this->handleAjax('go_to_step_two', !$request->getIsApi() ? $this->showRegistrationStep2($user, $password) : []);
        }

        $user->deleteSetting('failed_logins');
        $user->deleteSetting('failed_login_otp_attempts');

        if(!$request->getIsApi()){
            $twoFactorAuth = $user->getSetting('2-factor_authentication');
            if($twoFactorAuth == 1){
                lic('generate2faCode', [$user->getId()], $user);
                $_SESSION['show_add_two_factor_authentication_popup'] = true;
            }

            $_SESSION['mg_username'] = $username;
            $_SESSION['mg_password'] = $password;
            $user->setAttrsToSession();
            $_SESSION['mg_id'] = $_SESSION['local_usr']['id'];
            $_SESSION['user_id'] = $_SESSION['local_usr']['id'];
        }

        $fingerprint = $request->getFingerprint();

        if (!$this->isAuthTokenLogin($request) && !$this->isPNPValidationBeforeLogin($request)) {
            $user->startSession($fingerprint, $this->getOtpValidated(), $request);
            phive()->regenerateSession();
        }

        phMset(mKey($user, 'login'), 'yes');
        if(!$request->getIsApi()){
            $user->setTrackingEvent('logged-in', ['triggered' => 'yes']);
        }

        uEvent('login');

        $this->currentUser = $user;
        if (!$this->isAuthTokenLogin($request) && !$this->isPNPValidationBeforeLogin($request)) {
            $this->loginSuccessful(null, $request);
        }

        //this is here as I need to session action to update the zipcode
        $err = [];
        if (!$this->validateZipcode($err, $user->getCountry(), $user->getAttr('zipcode'))) {
            if(!$request->getIsApi()) {
                $_SESSION['zipcode_pending'] = 1;
            }
            return $this->handleAjax('zipfailed', showEmptyDobBox('zipcode'));
        }

        if ($user->getAttribute('dob') == '0000-00-00') {
            // show message to user that he needs to fill in his DOB
            return $this->handleAjax('fillin-dob', showEmptyDobBox());
        }

        $active = (int)$user->getAttribute('active');
        $inactive_account_error = function ($user, $username, $reg_country, $req_country, $active) use ($request){
            $this->logFailedLogin($user->getId(), $username, $reg_country, $req_country, $active, $reason = 'failed_login_blocked');
            $this->logAction($user, "Standard login: user is blocked", 'failed_login_blocked');
            $this->logoutAndPreserveCsrfToken('locked', false, $request);
            return $this->handleAjax('inactive');
        };

        // We're looking at a jurisdiction that requires login with NID and the player does not have set NID yet (ie market that was previously unregulated)
        // so we store the user id to be able to subsequently connect it to a verified NID and log the player out, ie the player has to now login with NID and
        // we store it when / if it becomes verified.
        if (lic('hasExtVerification', [], $user) && lic('needsNid', [$user], $user)) {
            if (empty($active)) {
                return $inactive_account_error($user, $username, $reg_country, $req_country, $active);
            }

            phMset(session_id() . '-nid-verification-uid', $user->getId());
            $this->legacy_nid = $user->getSetting('nid');
            $this->logoutAndPreserveCsrfToken('needs-nid', false, $request);
            lic('startMissingNidVerification', [$user, $request->getIsApi()]);
            return $this->handleAjax('needs-nid', [
                "method" => "goToVerify",
                "params" => [
                    array_flip(phive('Licensed')->getSetting('licensed_languages') ?? [])[$user->getCountry()],
                    $this->legacy_nid
                ]
            ]);
        }

        // Avoid duplication action: registration process already include checking of external self exclusion
        if(!$from_registration || !$user->getSetting(Bluem::CRUKS_SERVICE_WAS_DOWN_FLAG)) {
            try {
                $ext_excluded = lic('hasExternalSelfExclusion', [$user], $user);
                if ($ext_excluded !== false) {
                    $this->logAction($user, "Standard login: external self-exclusion check failed", 'failed_login_blocked');
                    phiveApp(EventPublisherInterface::class)
                        ->fire('authentication', 'AuthenticationLoginWhenSelfExcludedEvent', [$user->getId()], 0);
                    $this->logoutAndPreserveCsrfToken('locked', false, $request);
                    //todo Once the login_res stuff is refactored we can customize the messages
                    return $this->handleAjax('external-self-excluded');
                }
            } catch (Exception $e) {
                $this->logAction(
                    $user,
                    sprintf(
                        'Standard login: external self-exclusion check failed due to external system error, customer can try again (Exception: %s)',
                        $e->getMessage()
                    ),
                    'failed_login_blocked'
                );

                $this->logoutAndPreserveCsrfToken('locked', false, $request);
                return $this->handleAjax('login_fail_try_again');
            }
        }

        [$block, $result] = lic('hasInternalSelfExclusion', [$user], $user);
        if ($block !== false) {
            if ($result !== 'Y') {
                $this->logAction($user, "Standard login: internal self-exclusion check failed due to system error, customer can try again", 'failed_login_blocked');
                return $this->handleAjax('login_fail_try_again');
            }
            $this->logAction($user, "Standard login: customer is currently self excluded in another brand", 'failed_login_blocked');
            phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationLoginWhenSelfExcludedEvent', [$user->getId()], 0);
            $this->logoutAndPreserveCsrfToken('locked', false, $request);
            return $this->handleAjax('inactive');
        }

        if (lic('checkRemoteSelfExclusionByNid', [$user], $user) === true) {
            $remote = lic('getLicSetting', ['check_self_exclusion_remote_brand'], $user);
            $user->addComment("Login blocked due to {$remote} account - self exclusion by nid", 0, "failed_login_blocked");
            phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationLoginWhenSelfExcludedEvent', [$user->getId()], 0);
            $this->logoutAndPreserveCsrfToken('locked', false, $request);
            return $this->handleAjax('inactive');
        }

        // Since the active status can be change on `hasExternalSelfExclusion` we need to refresh the user active check
        $is_user_active = (int)cu($user->userId)->getAttribute('active');
        if (empty($is_user_active)) {
            return $inactive_account_error($user, $username, $reg_country, $req_country, $active);
        }

        phiveApp(EventPublisherInterface::class)
            ->fire('authentication', 'AuthenticationLoginEvent', [$user->getId()], 0);
        lic('onLoginRGCheck', [$user], $user);

        //We check on cases where registration is not completed
        if (!$user->hasSetting('registration_end_date')) {
            [$user, $fraud_res] = $this->registrationEnd(
                $user,
                $user->hasSetting('similar_fraud'),
                $request->getIsApi()
            );

            if ($fraud_res != 'ok') {
                return $this->handleAjax('inactive');
            }
        }

        if ($user->hasSetting('pwd-change-on-next-login')) {
            $this->logoutAndPreserveCsrfToken('needs-pwd-change', false, $request);

            $fifteen_minutes = 15 * 60;
            phMset('pwd-change-on-next-login-user-id-'.session_id(), $user->userId, $fifteen_minutes);

            return $this->handleAjax('needs-pwd-change', [
                'method' => 'goToResetPassword',
                'params' => []
            ]);
        }

        return $this->handleAjax($user);
    }

    private function isCorrectLoginMethod(DBUser $user, LoginCommonData $request, bool $needs_password): bool
    {
        if ($user->isTestAccount()) {
            return true;
        }

        // user tries to login using password with pnp or bankid mode
        if ((isPNP() || isBankIdMode()) && $needs_password) {
            return false;
        }

        // user tries to login using bankid with pnp mode
        if (isPNP() && $request->getAction() === 'token_login') {
            return false;
        }

        return true;
    }

    private function isOtpCaptchaRequest(LoginCommonData $request, $user): bool
    {
        return is_object($user)
            && !empty($request->getLoginCaptcha())
            && $user->hasExceededLoginOtpAttempts();
    }

    /**
     * Handler for showing and checking CAPTCHA during login.
     * Handles both regular and OTP CAPTCHAs.
     *
     * @param LoginCommonData $request
     * @param DBUser $user
     * @param string|null $req_country
     * @param bool $check_country
     * @return array|true - array on action; 'true' when no action required
     */
    private function handleLoginCaptchaCheck(LoginCommonData $request, DBUser $user, ?string $req_country, bool $check_country)
    {
        $reg_country = $user->getCountry();

        $is_check_for_otp_popup_required = empty($request->getLoginCaptcha())
            && $this->isOtpRequired($user, $req_country)
            && !$request->isPnp();

        if ($is_check_for_otp_popup_required) {
            if ($this->shouldIncreaseFailedLoginOtpAttempts($request, $user)) {
                $user->incSetting('failed_login_otp_attempts');
            }

            if ($user->hasExceededLoginOtpAttempts()) {
                // for API we block user instead of showing CAPTCHA
                if ($request->getIsApi()) {
                    $this->addBlock($user, 5, true);
                    $user->deleteSetting('failed_login_otp_attempts');

                    return ['inactive'];
                }

                return ['login_otp_fail', [
                    'method' => 'showLoginOTPCaptcha',
                    'params' => [PhiveValidator::captchaImg('', true)]
                ]];
            }
        }

        $captcha_setting = "captcha-login-allowed-$req_country";

        if (!$this->isLoginCaptchaEnabled($user, $req_country, $check_country)) {
            return true;
        }

        if (!empty($request->getLoginCaptcha())) {
            $captcha_code = $request->getIsApi()
                ? phMget($request->getCaptchaSessionKey())
                : PhiveValidator::captchaCode(true);

            if ($captcha_code != $request->getLoginCaptcha()) {
                $user->incSetting('failed_login_captcha_attempts');
                phive()->dumpTbl('captcha', ['failed', $reg_country, $req_country, remIp()], $user);
                return ['captcha.err'];
            }

            phive()->dumpTbl('captcha', ['success', $reg_country, $req_country, remIp()], $user);
            $user->refreshSetting($captcha_setting, 1, false);
            $user->deleteSetting('failed_login_otp_attempts');
            $user->deleteSetting('failed_login_captcha_attempts');
        } else if (!in_array($request->getDevice(), ['app_iphone', 'app_android'])) {
            $ttl = $this->getSetting('captcha-check-days-interval', 30);
            if ($user->hasSettingExpired($captcha_setting, $ttl)) {
                phive()->dumpTbl('captcha', ['requested', $reg_country, $req_country, remIp()], $user);
                return ['country', [
                    'method' => 'showLoginCaptcha',
                    'params' => [PhiveValidator::captchaImg('', true)]
                ]];
            }
        }

        return true;
    }

    private function shouldIncreaseFailedLoginOtpAttempts(LoginCommonData $request, DBUser $user): bool
    {
        return !$user->hasExceededLoginOtpAttempts()
            && $request->getOtp()
            && !$user->validateOtpCode($request->getOtp());
    }

    /**
     * @param DBUser $user
     * @param string|null $req_country
     * @param bool $check_country
     * @return bool
     */
    private function isLoginCaptchaEnabled(DBUser $user, ?string $req_country, bool $check_country): bool
    {
        if ($user->hasExceededLoginOtpAttempts()) {
            return true;
        }

        $reg_country = $user->getCountry();

        return $check_country && !empty($reg_country) && !empty($req_country) && $req_country !== $reg_country
            && phiveApp(IpBlockInterface::class)->getIsTest() !== true
            && !$user->allowedLoginCountry($req_country);
    }

    /**
     * @param LoginCommonData $request
     * @param DBUser $user
     * @param string|null $req_country
     * @param bool $is_password_correct
     * @param bool $is_otp_captcha_request
     * @return array|true - array on action; 'true' when no action required
     */
    private function handleLoginOtpCheck(
        LoginCommonData $request,
        DBUser $user,
        ?string $req_country,
        bool $is_password_correct,
        bool $is_otp_captcha_request
    ) {
        if (!$this->isOtpRequired($user, $req_country) || $request->isPnp()) {
            return true;
        }

        $is_otp_login_request = $request->getAction() === 'otp-login';

        $should_provide_otp = $is_otp_captcha_request || (
                !$is_otp_login_request
                && ($user->hasSetting('force_otp_check') || $user->hasExceededLoginAttempts() || !$this->isCurrentIpOrFpValidated($user, remIp(), $request->getFingerprint()))
            );

        if ($should_provide_otp) {
            $user->deleteSetting('otp_code');
            phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationSendOtpCodeEvent', [$user->getId()], 0);
            return ['needs-otp', [
                'method' => 'goToOtp',
                'params' => [t2('otp.description', ['phone_last_3' => substr($user->getMobile(), -3)])]
            ]];
        }

        if ($is_otp_login_request) {
            if ($request->getOtp() && !$user->validateOtpCode($request->getOtp())) {
                return ['login_otp_fail'];
            }

            $this->markSessionAsOtpValidated();
            $user->deleteSetting('otp_code');
            $user->deleteSetting('force_otp_check');
            $user->deleteSetting('failed_login_otp_attempts');
            $user->deleteSetting('failed_logins');

            if ($is_password_correct || $request->getIsApi()) {
                return true;
            }

            return ['show-login', [
                'method' => 'showDefaultLogin',
                'params' => [true]
            ]];
        }

        return true;
    }

    /**
     * Login user
     *
     * Context list:
     * - uname-pwd-login | normal login(username+password)
     * - otp-login | otp login(username+password+otp)
     * - uname-ext-service | external verification login(username)
     *
     * The login will fail in at least the following situations:
     *  - User is indefinitely/permanently self excluded
     *  - User tries to login from a restricted country
     *  - User can't be found in database
     *  - RG time limit reached
     *  - Wrong password
     *  - User id blocked/self excluded/external self excluded
     *
     * @param string $username The user login username.
     * @param string $password The user password.
     * @param bool $check_country Whether or not to check if the login attempt comes from the same country as the user
     * registered from.
     * @param bool $needpasswd Whether ot not the password is needed in order to execute the login, sometimes it is
     * not needed when logging in with tokens etc, the validation has already taken place by other means.
     * @param bool $from_registration Context flag, if true it means we're trying to auto login after registration is completed.
     *
     * @return DBUser|string The created user object in case of successful login, an error string otherwise.
     */
    public function login($username = null, $password = null, $check_country = false, $needpasswd = true, $from_registration = false)
    {
        $request = LoginCommonData::fromArray([
            'is_API' => false,
            'login_captcha' => $_POST['login_captcha'],
            'fingerprint' => $_SERVER['HTTP_X_DEVICE_FP'],
            'action' => $_REQUEST['action'],
            'otp' => $_POST['otp'],
            'login_method' => empty($password) ? 'ext-service' : 'pwd-login',
            'ip' => (isPNP(cu($username)) && $_SESSION['rstep1']['pnp_ip']) ? $_SESSION['rstep1']['pnp_ip'] : remIp(),
        ]);

        return $this->loginCommon($request, $username, $password, $check_country, $needpasswd, $from_registration);
    }

    /**
     * Login user through API
     *
     * Context list:
     * - uname-pwd-login | normal login(username+password)
     * - otp-login | otp login(username+password+otp)
     * - uname-ext-service | external verification login(username)
     *
     * The login will fail in at least the following situations:
     *  - User is indefinitely/permanently self excluded
     *  - User tries to login from a restricted country
     *  - User can't be found in database
     *  - RG time limit reached
     *  - Wrong password
     *  - User id blocked/self excluded/external self excluded
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginData $loginData
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\LoginWithApiResponse
     */
    public function loginWithAPI(LoginData $loginData): LoginWithApiResponse
    {
        $request = $loginData->getRequest();

        if (!empty($request->getLoginCaptcha())) {
            $allowed_captcha_attempt = $this->getSetting('allowed_captcha_attempt', 5);
            if (!empty($allowed_captcha_attempt)) {
                $too_many_attempts = limitAttempts('uname-pwd-logincaptcha', $request->getLoginCaptcha(), $allowed_captcha_attempt);
                if ($too_many_attempts) {
                    return LoginWithApiResponse::createError('captcha.toomanyattempts');
                }
            }
        }

        $response = $this->loginCommon(
            $request,
            $loginData->getUsername(),
            $loginData->getPassword(),
            $loginData->getCheckCountry(),
            $loginData->getNeedPasswd(),
            $loginData->getFromRegistration()
        );

        $error = $this->validateLoginCommonResponse($response);

        $loginRedirectsService = LoginRedirectsServiceFactory::create();
        $actions = $loginRedirectsService->getLoginRedirectActions($response);

        if ($error !== null && !in_array($error, LoginWithApiResponse::REASONS)) {
            return LoginWithApiResponse::createError($error);
        }

        if (! $response instanceof DBUser) {
            $response = cu($loginData->getUsername());
        }

        $country_prefix = phive('Cashier')->phoneFromIso($response->data['country']);
        $dbUserData = DBUserData::fromArray([
            'email' => $response->data['email'],
            'country' => $response->data['country'],
            'mobile' => $response->data['mobile'],
            'country_prefix' => $country_prefix,
            'currency' => $response->data['currency']
        ]);

        return LoginWithApiResponse::createSuccess($dbUserData, $error, $actions);
    }


    /**
     * Called by MTS after receiving notification request from Trustly
     * - If the user doesn't exist we will create the player and return a success response
     * - If the user exists we will run kyc verification and return a success response if the verification was successful
     *
     * @param LoginKycData $loginKycData
     * @return LoginKycResponse
     * @throws Exception
     */
    public function loginKYC(LoginKycData $loginKycData): LoginKycResponse
    {
        $pnp = phive('PayNPlay');
        $loginCommonData = $loginKycData->getRequest();
        $personId = $pnp->formatPersonId($loginKycData->getPersonId());

        if(!$personId){
            $response = 'Invalid user id';

            $logData['response'] = $response;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'error');

            return logToActionsAndReturn(
                LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $response)
            );
        }

        $personIP = $loginKycData->getIp();
        $transactionId = $loginKycData->getTransactionId();

        $logData = [
            'transactionId' => $transactionId,
            'personid' => $personId,
            'ip' => $personIP
        ];

        //Currency validation
        $countryCurrency = $pnp->getCountryCurrencies();
        if(!in_array($loginKycData->getCurrency(), array_values($countryCurrency))){
            $response = 'Invalid currency';

            $logData['response'] = $response;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'error');

            return logToActionsAndReturn(
                LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $response)
            );
        }

        //saving IP of a user to get it on a PnP registration flow
        $redisData['ip'] = $personIP;
        $pnp->setTransactionDataToRedis($transactionId, $redisData);

        $registeredUser = $this->getUserByAttr('nid', $personId);
        $isAlreadyRegistered = $registeredUser && $this->hasFinishedRegistrationStep2($registeredUser);

        if (! $isAlreadyRegistered) {
            $ip = $loginKycData->getIp();
            if ($bonus_code= $pnp->getBonusDataFromRedis($ip)) {
                $loginCommonData->setBonusCode($bonus_code);
            }

            $response = $this->pnpRegistration($loginCommonData, $loginKycData);
        } else {
            $response = $this->pnpVerification($loginCommonData, $loginKycData, $registeredUser);
        }

        //PNP result
        $logData['response'] = $response->toArray();
        $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

        if ($response->isError()) {
            logToActionsAndReturn($response);
        }

        //saving result of a verification to a Redis (used on PayNPlay/html/success.php | fail.php to show a result of a verification)
        $redisData = array_merge($redisData, $response->toArray());
        $pnp->setTransactionDataToRedis($transactionId, $redisData);

        return $response;
    }

    /**
     * Endpoint is used to set transaction verification status directly
     * without running KYC verification on our end
     *
     * @param LoginKycEventData $loginKycData
     * @return LoginKycResponse
     */
    public function loginKYCEvent(LoginKycEventData $loginKycData): LoginKycResponse
    {
        $pnp = phive('PayNPlay');

        $status = $loginKycData->getStatus();
        $message = $loginKycData->getMessage();

        $logData = [
            'status' => $status,
            'message' => $message
        ];

        if(!in_array($status, [LoginKycResponse::RESPONSEFINISH, LoginKycResponse::RESPONSECONTINUE])){
            $response = 'Invalid status';
            $logData['response'] = $response;
            $pnp->log('PayNPlay-loginKYCEvent-response', $logData, 'error');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $response);
        }

        $redisPnp = [];
        $redisPnp['status'] = $status; //ACCEPT or REJECT

        if($message){
            $redisPnp['message'] = $message; //Reason of a REJECTION
        }

        phMsetArr('pnp'.$loginKycData->getTransactionId(), $redisPnp);

        $response = 'Status set successfully';
        $logData['response'] = $response;
        $pnp->log('PayNPlay-loginKYCEvent-response', $logData, 'debug');

        return LoginKycResponse::createSuccess(LoginKycResponse::RESPONSECONTINUE, $response, 0);
    }

    /**
     * Check if IP is blocked for registration / login based on app allowed countries list.
     *
     * @param string|null $ip
     * @return bool
     */
    public function isAppAllowedCountry(?string $ip = null):bool {
        if (!$ip) {
            $ip = remIp();
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        $country = phive('IpBlock')->getJurisdictionFromIp($ip);
        return phive('Config')->isCountryIn('app', 'allowed-countries', $country);
    }

    /**
     * Check if IP is blocked for registration / login based on excluded countries list.
     * Also check if user is trying to register from disabled jurisdiction.
     *
     * @param null|string $ip
     *
     * @return bool
     */
    public function isRegistrationAndLoginAllowed($ip = null): bool
    {
        if (!$ip) {
            $ip = remIp();
        }

        if (isWhitelistedIp($ip)) {
            return true;
        }

        $country = phive('IpBlock')->getJurisdictionFromIp($ip);
        $user = cu();
        $is_admin = $user && privileged($user);

        // allow admin registration/login (needed for PayNPlay test accounts flow)
        if ($is_admin) {
            return true;
        }

        // prevent registration/login for users from disabled jurisdictions
        if (!phive('Licensed')->isActive($country)) {

            // not MGA country and specific license (e.g. UKGC, DGA, ...) is not active - prevent login/registration
            $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
            $is_mga_country = !isset($country_jurisdiction_map[$country]);
            if (!$is_mga_country) {
                return false;
            }

            // MGA country but MGA is not active - prevent login/registration
            $is_mga_active = phive('Licensed')->isActive('MT');
            if (!$is_mga_active) {
                return false;
            }
        }

        $blocked_countries = phive('Config')->valAsArray('exclude-countries', 'login-and-registration-blocked-countries');
        return !in_array($country, $blocked_countries);
    }

    /**
     * Paynplay verification for existing users
     *
     * @param LoginCommonData $loginCommonData
     * @param LoginKycData $loginKycData
     * @param User $user
     * @return LoginKycResponse
     */
    private function pnpVerification(LoginCommonData $loginCommonData, LoginKycData $loginKycData, User $user): LoginKycResponse {
        $loggedinUserId = $loginKycData->getUserId();
        $userId = $user->getId();
        $userIP = $loginKycData->getIp();

        //Saving real IP of a user to a session to be used later in loginCommon
        $_SESSION['rstep1']['pnp_ip'] = $userIP;

        //Logged in user verification
        if($loggedinUserId && $loggedinUserId != $userId){
            //Goal is to block logged-in user from using any other Trustly account except his own
            $message = "Trying to deposit with another registered account (Logged-in ID: $loggedinUserId; Deposit User ID: $userId)";

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
        }

        $response = $this->loginCommon($loginCommonData, $user->getId(), null, false, false, false);

        if ($response instanceof DBUser) {
            $this->pnpUpdateUserProfile($loginKycData, $user);

            $depositAmount = $loginKycData->getAmount();
            $limit = null;
            //skipping deposit verification on a logins without deposit
            if($depositAmount) {

                if ($response->isDepositBlocked()) {
                    return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, "deposit-block");
                }

                $limitsCheck = phive('Cashier')->checkOverLimits($response, $depositAmount);
                if ($limitsCheck[0]) {
                    $isError = true;
                    switch ($limitsCheck[1]) {
                        case null:
                            $message = 'deposit-reached';
                            break;
                        case 'show-net-deposit-limit-message':
                            $message = 'casino-net-deposit-threshold-reached';
                            break;
                        case rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT:
                            //this one can return true with a limit to adjust
                            if ($limitsCheck[2]['available_limit'] > 0) {
                                $isError = false;
                                break;
                            }
                            $message = 'customer-net-deposit-reached';
                            break;
                        case 'will-exceed-balance-limit':
                            $message = 'balance-limit-reached';
                            break;
                        default:

                    }
                    if ($isError) {
                        return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
                    }
                }

                $possibleAmount = $this->pnpDepositVerification($response);

                if($depositAmount > $possibleAmount){
                    $limit = $possibleAmount;
                }
            }


            $message = 'Successful PNP verification';
            $this->logAction($response, $message, "pnp");
            return LoginKycResponse::createSuccess(LoginKycResponse::RESPONSECONTINUE, $message, $user->getId(), $limit);
        } else {
            if($response == 'inactive'){
                $message = 'blocked';
            } elseif (in_array($response, ['self_excluded', 'self_locked'])){
                $message = 'self-excluded';
            } else {
                $message = $response;
            }

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
        }
    }

    /**
     * Get minimum amount user can deposit
     *
     * @param DBUser $user
     * @return int
     */
    private function pnpDepositVerification(DBUser $user): int{
        $rgLimits = rgLimits();
        $limits = $rgLimits->getByTypeUser($user, 'deposit');
        $netDepositLimit = $rgLimits->getLimit($user, $rgLimits::TYPE_CUSTOMER_NET_DEPOSIT, 'month');

        if(!count($limits) && !$netDepositLimit){
            $c = phive('Cashier');
            $psp = 'trustly';

            return phiveApp(PspConfigServiceInterface::class)->getUpperLimit($psp, PspActionType::IN);
        }

        $possibleAmounts = [$netDepositLimit['cur_lim'] - $netDepositLimit['progress']];

        foreach ($limits as $rgl) {
            $cur_lim = $rgl['cur_lim'];
            $progress = $rgl['progress'];
            $possibleAmounts[] = $cur_lim - $progress;
        }

        return min($possibleAmounts);
    }

    /**
     * @param LoginKycData $loginKycData
     * @param User $user
     * @return void
     */
    private function pnpUpdateUserProfile(LoginKycData $loginKycData, User $user):void {
        $p = [];
        $p['firstname'] = $loginKycData->getFirstname();
        $p['lastname'] = $loginKycData->getLastname();
        $p['dob'] = $loginKycData->getBirthdate();
        $p['address'] = $loginKycData->getAddress();
        $p['city'] = $loginKycData->getCity();
        $p['zipcode'] = $loginKycData->getZipcode();

        $data = $user->getData();

        $toUpdate = [];
        $toUpdate = array_filter($p, function ($pv, $pk) use ($data) {
            return array_key_exists($pk, $data) && $pv != $data[$pk];
        }, ARRAY_FILTER_USE_BOTH);


        if(count($toUpdate)){
            $user->setContactInfo($p);

            phive("UserHandler")->logAction(
                $user,
                "Updated profile with: " . var_export($toUpdate, true),
                "profile-update-by-trustly-kyc",
                true);
        }

    }

    /**
     * Is used to create a new users via PlayNPlay registration process
     * Return two type of answers
     * ['stasus': 'REJECT', 'message': 'reason of a failure']
     * or
     * ['status': 'ACCEPT': 'message': 'new registration', 'userid': 12345]
     *
     *
     * @param LoginCommonData $loginCommonData
     * @param LoginKycData $loginKycData
     * @return LoginKycResponse
     * @throws Exception
     */
    private function pnpRegistration(LoginCommonData $loginCommonData, LoginKycData $loginKycData): LoginKycResponse{
        $pnp = phive('PayNPlay');
        $loggedinUserId = $loginKycData->getUserId();
        $personId = $pnp->formatPersonId($loginKycData->getPersonId());
        $transactionId = $loginKycData->getTransactionId();
        $personIP = $loginKycData->getIp();

        $logData = [
            'transactionId' => $transactionId,
            'personid' => $personId,
            'ip' => $personIP
        ];

        //Logged-in user tries another Trustly account
        if($loggedinUserId){
            $message = "Trying to deposit with another unregistered account (Logged-in User ID: $loggedinUserId; Deposit NID: $personId)";

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
        }

        $countries = phive('Cashier')->getBankCountries('', true);
        $pnpCurrencies = $pnp->getCountryCurrencies();
        $userCountry = [];

        foreach ($countries as $country) {
            if ($country['printable_name'] == $loginKycData->getBirthCountry()) {
                $userCountry = $country;
                break;
            }
        }

        $userCountryISO = $userCountry['iso'];
        //Trustly PNP registration is valid only for a countries with a personal id data
        if (!in_array($userCountryISO, phive('PayNPlay')->getValidCountries())) {
            $response = 'Not a valid country for PnP registration';

            $logData['response'] = $response;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'error');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $response);
        }

        $validCurrency = $pnpCurrencies[$userCountryISO];
        if(!$validCurrency){
            $response = 'No currency set for this country. Invalid registration';

            $logData['response'] = $response;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'error');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $response);
        }

        $depositAmount = $loginKycData->getAmount();
        if (empty($depositAmount) ) {
            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, "Registration without deposit is not allowed");
        }

        $transactionId = $loginKycData->getTransactionId();
        $personIP = $loginKycData->getIp();
        $mobile = random_int(10000000, 99999999);
        $email = $pnp->generateEmail($transactionId);

        $_SESSION['rstep1']['pnp_user'] = $personId;
        $_SESSION['rstep1']['pnp_transaction_id'] = $transactionId;
        $_SESSION['rstep1']['pnp_ip'] = $personIP;

        $step1 = new RegisterUserStep1Data(
            $email,
            $userCountry['iso'],
            $mobile,
            $userCountry['calling_code'],
            '',
            true,
            true,
            $loginCommonData->getDevice(),
            $loginKycData->getTransactionId(),
            $loginKycData->getTransactionId(),
            $loginCommonData->getBonusCode()
        );

        $step1Validation = $this->validateStep1Fields($step1, true);

        if (count($step1Validation->getErrors())) {
            $errors = json_encode($step1Validation->getErrors());

            $logData['response'] = $errors;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $errors);
        }

        $step1Result = $this->finalizeRegistrationStep1($step1);

        if (count($step1Result->getErrors())) {
            $errors = json_encode($step1Result->getErrors());

            $logData['response'] = $errors;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $errors);
        }

        //Step 1 data is placed in a database
        $registeredUserId = $step1Result->getUserId();
        $this->currentUser = cu($registeredUserId);

        // we set this here to skip the goToVerify step
        $this->currentUser->setAttr('nid', $personId);
        $this->currentUser->setSetting('verified-nid', 1);

        if(preg_match('/^test/', $transactionId)){
            $this->currentUser->setSetting('test_account', 1);
        }

        //Based on a Trustly docs some banks have different binary logic interpretation
        $gender = ($loginKycData->getGender() == 'F')? 'Female' : 'Male';

        $step2 = new RegisterUserStep2Data(
            $loginKycData->getFirstname(),
            $loginKycData->getLastname(),
            '',
            $loginKycData->getAddress(),
            $loginKycData->getCity(),
            $loginKycData->getZipcode(),
            'en',
            $validCurrency,
            $loginKycData->getBirthdate(),
            $gender,
            random_int(1000, 9999),
            null,
            $userCountryISO,
            null,
            null,
            $loginCommonData->getBonusCode(),
            $loginCommonData
        );

        $step2Validation = $this->validateStep2Fields($step2);

        if(count($step2Validation->getErrors())){
            $errors = json_encode($step2Validation->getErrors());

            $logData['response'] = $errors;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $errors);
        }

        $step2Result = $this->finalizeRegistrationStep2($step2);

        if(count($step2Result->getErrors())){
            $is_external_excluded = $step2Result->getErrors()['messages'][0] === 'external-self-excluded';
            if ($is_external_excluded) {
                $message = 'self-excluded';

                $logData['response'] = $message;
                $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

                return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
            }

            $errors = json_encode($step2Result->getErrors());

            $logData['response'] = $errors;
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $errors);
        }

        $loggedinUserId = $step1Result->getUserId();

        if(cu($loggedinUserId)->getSetting('id3global_pep_res') !== 'PASS' && !cu($loggedinUserId)->isTestAccount()){
            $message = 'blocked';

            $logData['response'] = 'id3global-pep-failure';
            $pnp->log('PayNPlay-loginKYC-response', $logData, 'debug');

            return LoginKycResponse::createError(LoginKycResponse::RESPONSEFINISH, $message);
        }

        $message = "Successful new PNP registration";
        $this->logAction($loggedinUserId, $message, "pnp");
        return LoginKycResponse::createSuccess(LoginKycResponse::RESPONSECONTINUE, $message, $loggedinUserId);
    }

    /**
     * @param string $authToken
     *
     * @return bool
     */
    public function loginWithAuthToken(string $authToken): bool
    {
        if (!$this->validateAuthToken($authToken)) {
            return false;
        }

        [$userId, $tokenId, $token] = explode('|', $authToken, 3);
        $encryptedToken = hash('sha256', $token);
        $result = phive('SQL')->sh($userId)
            ->loadAssoc("SELECT id FROM personal_access_tokens WHERE id = {$tokenId} AND token = '{$encryptedToken}'");

        if (empty($result)) {
            return false;
        }

        $result = phive('SQL')->sh($userId)
            ->loadAssoc("SELECT username FROM users WHERE id = {$userId}");

        if (empty($result)) {
            return false;
        }

        $request = LoginCommonData::fromArray([
            'is_API' => false,
            'auth_token' => $authToken,
            'encrypted_auth_token' => $encryptedToken,
            'action' => 'uname-pwd-login',
            'login_method' => 'auth-token-login',
            'ip' => remIp(),
        ]);

        $result = $this->loginCommon($request, $result['username'], null, false, false);

        return !is_string($result);
    }

    /**
     * @param LoginCommonData $request
     * @return bool
     */
    private function isAuthTokenLogin(LoginCommonData $request): bool
    {
        return !$request->getIsApi() && $request->getAuthToken() !== '';
    }

    /**
     * Verifies if transaction is PNP related
     * and was not created by a PayNPlay->loginUserAfterRedirect
     *
     * @param LoginCommonData $request
     * @return bool
     */

    private function isPNPValidationBeforeLogin(LoginCommonData $request): bool {
        return $request->isPnp() && !preg_match('/^login-/', $request->getPnpTransactionId());
    }

    /**
     * @param string $authToken
     *
     * @return bool
     */
    private function validateAuthToken(string $authToken): bool
    {
        $authData = explode('|', $authToken, 3);

        return count($authData) == 3
            && filter_var($authData[self::AUTH_TOKEN_USER_ID_KEY], FILTER_VALIDATE_INT) !== false
            && filter_var($authData[self::AUTH_TOKEN_TOKEN_ID_KEY], FILTER_VALIDATE_INT) !== false
            && strlen($authData[self::AUTH_TOKEN_TOKEN_KEY]) > 0;
    }

    /**
     * @param \DBUser|string $data
     *
     * @return string|null
     */
    private function validateLoginCommonResponse($data)
    {
        if (is_string($data)) {
            return $data;
        } elseif (is_array($data)) {
            if($data[0] === 'login_fail_attempts'){
                return ['login_fail_attempts', t2("blocked.login_fail_attempts.html", $data[1])];
            }
            return $data[0];
        } elseif ($data instanceof DBUser) {
            if ($data->getSetting('pwd_changed') === 'yes') {
                return 'pwd_changed';
            }

            if (phive('UserHandler')->doForceDeposit($data)) {
                return 'first_deposit';
            }
        }

        return null;
    }

    /**
     * @param int $user_id
     *
     * @return int|null
     */
    public function getLoginSessionTimeout(int $user_id): ?int
    {
        $res = mCluster('uaccess')->get(mKey($user_id, 'uaccess'));

        return empty($res) ? null : (int)explode(':', $res)[1];
    }

    /**
     * Extend current session expire time for a given user
     * Used from laraphive to extend API Auth token expiration.
     *
     * @param int $user_id
     * @param int $timeout expire time in seconds
     *
     * @return int
     *
     * @api
     */
    public function setLoginSessionTimeout(int $user_id, int $timeout): int
    {
        $session_id = getSid($user_id);
        $to_redis = $session_id . ':' . $timeout;
        mCluster('uaccess')->set(mKey($user_id, 'uaccess'), $to_redis, $timeout);

        return $timeout;
    }

    /**
     * Send an OTP to both emails and phone numbers.
     *
     * @param DBUser|int $user User id or a user object.
     */
    public function sendOtpCode($user)
    {
        $user = cu($user);
        if (empty($user) || $user->isBlocked()) {
            return;
        }
        $code = $user->generateOtpCode();
        phive('MailHandler2')->sendOtpMail($user, $code);

        phive('Mosms')->zSsendValidation($user, null, $code);

        $this->logAction($user, "OTP {$code} sent to customer mobile and email", 'otp-sent');

        phive()->dumpTbl('otp-sent', $code , $user);
    }

    /**
     * Check if the login is done from a device that was previously validated with OTP, otherwise OTP validation is needed
     * We check for the following scenarios:
     * - OK - same IP used during registration
     * - OK - IP or FP previously validated
     * - OK - user did a successful deposit from IP (no FP on deposits) + validate all previous OK deposit session IPs
     * - OK - is a test account
     * - KO - it's a new IP & FP (new device)
     *
     * @param $user DBUser User object>
     * @param $ip string player IP
     * @param $fp string player Fingerprint
     * @return bool True if validated, false otherwise.
     */
    public function isCurrentIpOrFpValidated($user, $ip, $fp)
    {
        $user_id = $user->getId();
        $fp = md5($fp);
        if ($user->getAttr('reg_ip') === $ip) {
            return true;
        }

        $validated_session = "user_id = {$user_id} AND otp = 1 AND (ip = '{$ip}' OR fingerprint = '{$fp}')";
        $validated_session = phive('SQL')->sh($user_id)->loadAssoc("", "users_sessions", $validated_session, true);
        if (!empty($validated_session)) {
            return true;
        }

        $has_deposited_from_ip = "user_id = {$user_id} AND status = 'approved' AND ip_num = '{$ip}'";
        $has_deposited_from_ip = phive('SQL')->sh($user_id)->loadAssoc("", "deposits", $has_deposited_from_ip, true);
        if (!empty($has_deposited_from_ip)) {
            $this->validatePreviousSessionWithDeposit($user);
            return true;
        }

        if($user->isTestAccount()) {
            return true;
        }

        return false;
    }

    /**
     * We trigger this as a once in a lifetime operation to validate all the IPs from which the user
     * has deposited from.
     * TODO henrik move this to onlogin in fraud
     * After first time, the $validated_session check will match before this triggers.
     *
     * @param DBUser $user User object.
     */
    private function validatePreviousSessionWithDeposit($user)
    {
        $valid_deposit_ips = phive('SQL')->sh($user)->loadArray("
            SELECT ip_num FROM deposits WHERE user_id = {$user->getId()} AND status = 'approved' GROUP BY ip_num
        ");

        $valid_deposit_ips = array_column($valid_deposit_ips ,'ip_num');

        if (!empty($valid_deposit_ips)) {
            $valid_deposit_ips = phive('SQL')->makeIn($valid_deposit_ips);
            phive('SQL')->sh($user)->query("
                UPDATE users_sessions SET otp = 1 WHERE user_id = {$user->getId()} AND ip IN ({$valid_deposit_ips})
            ");
        }
    }

    /**
     * Check if OTP is enabled for user country, by default ALL countries are subject to OTP.
     * If user is subject to OTP we check for the following thing to prevent OTP to fire:
     * - session is already validated (Ex. external auth)
     * - user is blocked
     * - user is self-excluded
     * - user is external-self-excluded
     *
     * If "otp_countries" setting exists (Array of ISO), then OTP will apply only for those countries.
     * If user tries to login with wrong password for certain amount of attempts ('allowed_attempts' config) - OTP will apply.
     *
     * @param DBUser $user User object.
     * @param null $req_country ISO of country
     * @return bool True if required, false otherwise.
     */
    public function isOtpRequired($user, $req_country = null)
    {
        if ($user->hasExceededLoginAttempts()) {
            return true;
        }

        $countries = $this->getSetting('otp_countries', []);

        // If setting contains ALL we enable for any country
        if(in_array('ALL', $countries)) {
            $check_is_needed = true;
        } else {
            $country = $user->getCountry();
            $check_is_needed = in_array($country, $countries);
        }

        if ($user->getSetting('force_otp_check')) {
            $check_is_needed = true;
        }

        if(empty($check_is_needed)) {
            if(!empty($this->getSetting('otp_on_country_mismatch'))
                && !empty($req_country)
                && $country != $req_country
            ) {
                return true;
            }
            return false;
        }

        if ($this->getOtpValidated()
            || $user->isBlocked()
            || $this->isSelfExcluded($user)
            || $this->isExternalSelfExcluded($user)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the user has finished the registration step 2.
     *
     * @param DBUser $user User object.
     * @return bool True if has finished step 2, false otherwise.
     */
    function hasFinishedRegistrationStep2($user)
    {
        // return false if only 1 of the required fields is empty
        if(empty($user->data['firstname']) || empty($user->data['lastname']) || empty($user->data['sex']) || empty($user->data['address'])) {
            return false;
        }

        return true;
    }


    /**
     * Checks if user need to be migrated and migration flow is necessary
     *
     * @param $user
     * @return bool
     */

    public function isMigrating($user): bool {
        //trigger migration flow
        if($user->getSetting('migrated') === "0"){
            return true;
        }

        return false;
    }


    /**
     * Prefills step 2 popup with previously saved data. Is used by a migration flow
     *
     * @param $user
     * @return void
     */
    public function prefillRegistrationStep2($user){
        $_SESSION['rstep2'] = $user->data;

        $dobar = explode('-', $user->data['dob']);
        $_SESSION['rstep2']['birthyear'] = $dobar[0];
        $_SESSION['rstep2']['birthmonth'] = $dobar[1];
        $_SESSION['rstep2']['birthdate'] = $dobar[2];

        $_SESSION['rstep2']['migration'] = true;
    }



    /**
     * This opens step 2 of the registration.
     *
     * @param DBUser $user DBUser object
     * @param string $password The unencrypted password
     * @param string $email_code The email verification code to send to the given email address.
     * @param bool $need_password Whether or not we need to login with a password.
     * @return null
     */
    function showRegistrationStep2($user, $password, $email_code = '', $need_password = true)
    {
        require_once __DIR__ . '/../../../phive/modules/DBUserHandler/Registration/RegistrationHtml.php';
        return RegistrationHtml::initRegistrationStep2($user, $password, $email_code, $need_password);
    }


    /**
     * Validates the Source of Funds form.
     * This also allows validating a single field.
     * Returns an empty array if no errors are found.
     *
     * @param int $user_id The user id.
     * @param array $formdata Submitted form data.
     * @return array An array of errors, empty array if no errors.
     */
    function validateSourceOfFundsForm($user_id, $formdata)
    {
        $errors = [];
        if(!checkdate($formdata['submission_month'], $formdata['submission_day'], $formdata['submission_year'])) {
            $errors['date'] = 'register.err.invalid';
        }



        if(!$this->validateFundingMethods($formdata)) {
            $errors['funding_methods'] = 'funding.methods.empty';
        }

        if(!$this->validateAnnualIncome($formdata)) {
            $errors['annual_income'] = 'annual.income.empty';
        }

        $occupation_dropdown_enabled = licSetting('occupation_dropdown_enabled', cu($user_id));

        if($occupation_dropdown_enabled) {
            $fieldNameMap = [
                'occupation' => 'job.title',
            ];
            $occupations = lic('getOccupations', [$formdata['industry'], cu($user_id)], cu($user_id));
            if (!empty($formdata['occupation']) && !empty($occupations) && !in_array($formdata['occupation'], $occupations)) {
                $errors[$fieldNameMap['occupation']] = 'occupational.form.validation.validJobTitle';
            }
        } else {
            $fieldNameMap = [];
        }

        foreach ($this->getRequiredFieldsSourceOfFundsForm() as $required_field) {
            if ($required_field === "password") {
                continue;
            }else if(empty(trim($formdata[$required_field]))) {
                $mappedField = $fieldNameMap[$required_field] ?? $required_field;
                $errors[$mappedField] = 'register.err.empty';
            }
        }

        return $errors;
    }

    /**
     * Validates the annual income submitted by the Source of Funds form.
     * If no annual income is selected, the no income explanation needs to be filled in.
     *
     * @param array $formdata Submitted form data.
     * @return bool True if all good, false otherwise.
     */
    function validateAnnualIncome($formdata)
    {
        return !empty($formdata['annual_income']);
    }

    /**
     * Validates the funding methods submitted by the Source of Funds form.
     * At least 1 option should be selected, if others is not specified.
     *
     * TODO henrik clean this up.
     *
     * @param array $formdata Submitted form data.
     * @return bool True if all good, false otherwise.
     */
    function validateFundingMethods($formdata)
    {
        $valid = false;

        if(empty($formdata['others'])) {
            if($formdata['salary'] == 1) {
                $valid = true;
            }
            if($formdata['business'] == 1) {
                $valid = true;
            }
            if($formdata['income'] == 1) {
                $valid = true;
            }
            if($formdata['dividend'] == 1) {
                $valid = true;
            }
            if($formdata['interest'] == 1) {
                $valid = true;
            }
            if($formdata['gifts'] == 1) {
                $valid = true;
            }
            if($formdata['pocket_money'] == 1) {
                $valid = true;
            }
        } else {
            $valid = true;
        }

        return $valid;
    }

    /**
     * Saves the Source of Funds form in the database, table users_source_of_funds.
     *
     * @param int $user_id The user id.
     * @param array $formdata Submitted form data.
     */
    function prepareSourceOfFundsData($user_id, $formdata)
    {
        $funding_methods = $this->getSubmittedFundingMethods($formdata);

        $data = [
            'user_id'                => $user_id,
            'document_id'            => intval($formdata['document_id']),
            'name_of_account_holder' => $formdata['name_of_account_holder'],
            'name'                   => $formdata['name'],
            'address'                => $formdata['address'],
            'funding_methods'        => $funding_methods,
            'other_funding_methods'  => $formdata['others'],
            'industry'               => $formdata['industry'],
            'occupation'             => $formdata['occupation'],
            'annual_income'          => $formdata['annual_income'],
            'currency'               => cuAttr('currency', $user_id),
            'no_income_explanation'  => $formdata['no_income_explanation'],
            'your_savings'           => $formdata['your_savings'],
            'savings_explanation'    => $formdata['savings_explanation'],
            'date_of_submission'     => $formdata['submission_year'] . '-' . $formdata['submission_month'] . '-' . $formdata['submission_day'],
            'form_version'           => $formdata['form_version'],
        ];

        return $data;
    }

    /**
     * Get the submitted funding methods as a comma separated string.
     *
     * TODO henrik clean this up, create a getter for the fields and use both here and in validate funding methods
     *
     * @param array $formdata Submitted form data.
     *
     * @return string The result string.
     */
    function getSubmittedFundingMethods($formdata)
    {
        $funding_methods = [];
        if($formdata['salary'] == 1) {
            $funding_methods[] = 'salary';
        }
        if($formdata['business'] == 1) {
            $funding_methods[] = 'business';
        }
        if($formdata['income'] == 1) {
            $funding_methods[] = 'income';
        }
        if($formdata['dividend'] == 1) {
            $funding_methods[] = 'dividend';
        }
        if($formdata['interest'] == 1) {
            $funding_methods[] = 'interest';
        }
        if($formdata['gifts'] == 1) {
            $funding_methods[] = 'gifts';
        }
        if($formdata['pocket_money'] == 1) {
            $funding_methods[] = 'pocket_money';
        }

        return implode(', ', $funding_methods);
    }

    /**
     * Get all required fields for the Source of Funds form.
     *
     * @return array The fields.
     */
    function getRequiredFieldsSourceOfFundsForm()
    {
        return [
            'name_of_account_holder',
            'address',
            'occupation',
            'name',
            'submission_day',
            'submission_month',
            'submission_year',
            'password'
        ];
    }

    /**
     * Determines whether or not a specific mail or sms or notification can be send to a specific user.
     *
     * Factors that determine if the email / sms / notification can be sent or not are user settings and regulations.
     *
     * @param mixed $user User identifying element.
     * @param string|null $mail The mail tag / alias.
     * @param string|null $sms The sms tag / alias.
     * @param string|null $notification The notification tag / alias.
     *
     * @return bool True if email / sms / notification can be sent, false otherwise.
     */
    public function canSendTo($user, $mail = null, $sms = null, $notification = null)
    {
        /** @var PrivacyHandler $pkg */
        $pkg = phive('DBUserHandler/PrivacyHandler');

        if (empty($db_user = cu($user))) return false;

        $user_country = $db_user->getCountry();

        $user_ip                = $db_user->getAttr('cur_ip');
        $user_email             = $db_user->getAttribute('email');
        $ip_blocked_countries   = phive('Config')->valAsArray('countries', 'ip-block');
        $is_blocked_country     = in_array($user_country, $ip_blocked_countries, true);
        $is_whitelisted_ip      = $user_ip && phive('IpGuard')->isWhitelistedIp((string)$user_ip);
        $is_admin               = privileged($user);
        $has_vs_domain          = str_contains($user_email, '@videoslots');

         if ($is_blocked_country && !$is_whitelisted_ip && !$is_admin && !$has_vs_domain) return false;

        if (!is_null($mail)) {
            $channel    = $pkg::CHANNEL_EMAIL;
            $trigger    = $mail;
        } else if (!is_null($sms)) {
            $channel    = $pkg::CHANNEL_SMS;
            $trigger    = $sms;
        } else if (!is_null($notification)) {
            $channel    = $pkg::CHANNEL_APP;
            $trigger    = $notification;
        } else {
            return false; // No type was specified, block it
        }

        $blocked_promo_countries = phive()->getSetting('blocked_promotion_countries');

        $settings = $pkg->getTriggerSettings($channel, $trigger);
        if (!$settings) {
            phive('Logger')->error("Trigger '{$trigger}' has not been configured for the '{$channel}' channel. Blocking send out");
            return false;
        }

        if ($pkg->requiresConsent($channel, $trigger)) {
            if ($db_user->isBlocked() || $db_user->isSuperBlocked()) {
                return false;
            }

            // userIsMarketingBlocked check here only when it was not done already in a parent method
            if (is_null($db_user->marketing_blocked) && lic('userIsMarketingBlocked', [$db_user], $db_user)) {
                return false;
            }

            // exit here because marketing_blocked was already confirmed from a parent method
            // exit if user has intensive gambler trigger
            if ($db_user->marketing_blocked || lic('isIntensiveGambler', [$db_user], $db_user)) {
                return false;
            }

            // if email is promo and country is blocked from promo emails
            if (in_array($user_country, $blocked_promo_countries)) return false;
            return $pkg->canReceiveTrigger($db_user, $channel, $trigger);
        }

        // no consent is required here
        return true;
    }

    /**
     * Display helper for the privacy form.
     *
     * @deprecated Move to PrivacyHandler
     * @param bool $all Whether or not to return all the form helper data or just the privacy settings.
     * @param bool $exclude_optout Whether or not to return the no optout option.
     *
     * @return array The array to use in order to generate the GUI elements.
     */
    public function getDataFormPrivacyForm($all = false, $exclude_optout = false) {
        return [];
    }


    /**
     * Update all privacy dashboard settings that can be opted out, enable/disable all.
     * When calling this we will mark that the user has reviewed his privacy setting,
     * so it will not prompted anymore to fill them in.
     *
     * @deprecated Replace with PrivacyHandler
     * @param DBUser $user The user object.
     * @param string $action The action: "opt-in" OR "opt-out"
     */
    public function privacySettingsDoAll($user, $action = 'opt-in')
    {
        /** @var PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');
        $ph->setAllPrivacySettings($user, strtolower($action) === 'opt-in');

        if ($action == 'opt-in') {
            $user->deleteSetting('show_ps_after_first_dep');
        }
    }

    /**
     * Gets the user with a certain National Identification Number (NID).
     *
     * @param string $nid The NID.
     * @param string $country ISO2 country code for the user's country.
     * @param bool $clean Used to skip the sanitization of $nid.
     * @return DBUser|null The user object or null if no user was found.
     */
    public function getUserByNid($nid, $country = '', $clean = true)
    {
        if ($clean) {
            $nid = lic('sanitizeNid', [$nid]);
        }
        $str = "SELECT * FROM users WHERE nid = '$nid' AND country = '$country' LIMIT 1";
        $ud = phive('SQL')->shs()->loadAssoc($str);
        return empty($ud) ? null : cu($ud);
    }

    /**
     * Logic that triggers when a new expired document is uploaded and is either POI or POA. If the customer doesn't
     * have already the reward configured (if any) then we give it to the customer only once.
     *
     * @param array $document
     * @param DBUser $user
     */
    public function onMissingDocumentUpload($document, $user)
    {
        if (!empty($document['expired']) && in_array($document['tag'], ['addresspic', 'idcard-pic'])) {

            phive('UserHandler')->logAction($user, "Uploaded missing file for {$document['tag']}",
                'uploaded-missing-file', true);

            $award_id = phive('Config')->getValue('kyc', 'expire-missing-docs-award-id');
            if (!empty($award_id)) {
                if (!empty($user) && !$user->hasSetting('expire-missing-docs-award-id')) {
                    phive('Trophy')->giveAward($award_id, $user);
                    $user->setSetting('expire-missing-docs-award-id', $award_id);
                }
            }
        }
    }

    /**
     * NOTE: this can be removed if we get the pics back or when we feel like we don't need this anymore.
     *
     * @param DBUser $user
     * @param int|string $mtsTransactionId
     * @param string $main_supplier
     * @param string $sub_supplier
     * @param string $card_hash
     */
    public function checkExpiredDocuments($user, $mtsTransactionId, $main_supplier, $sub_supplier = '', $card_hash = '')
    {
        $country_to_expire = phive('Config')->isCountryIn('kyc', 'expire-missing-docs', $user->getCountry());

        $node_to_expire = in_array(phive('SQL')->getNodeByUserId($user->getId()), $this->getSetting('expire_docs_nodes', [0]));

        $docs = phive('Dmapi')->getDocuments($user->getId());

        foreach ($docs as $doc) {
            if (($country_to_expire || $node_to_expire) && empty($doc['exists_on_disk'])) {

                if (!in_array($doc['tag'], ['addresspic', 'idcard-pic', 'bankpic'])) {
                    if ($doc['tag'] == 'creditcardpic' && !empty($card_hash) && $doc['subtag'] != $card_hash) {
                        // We're looking at a deposit with a card but the card hash does not match with the current card hash in the loop,
                        // therefore we move on to the next doc.
                        continue;
                    }

                    $document_type = phive('Dmapi')->getDocumentTypeFromMap($main_supplier, $sub_supplier);
                    if (empty($document_type)) {
                        // We could not get the document type so we do nothing.
                        continue;
                    }
                }

                if (in_array($doc['tag'], ['addresspic', 'idcard-pic', 'bankpic', $document_type])) {
                    $res = phive('Dmapi')->expireDocument($doc, $user->getId());
                    if (empty($res['errors'])) {
                        phive('UserHandler')->logAction($user,
                            "The {$doc['tag']} document with id {$doc['id']} was expired because of missing files.",
                            'expired-missing-file');
                    }
                }
            }

            if ($doc['tag'] === 'sourceoffundspic' && $doc['status'] === 'approved' && empty($doc['source_of_funds_data'])) {
                $res = phive('Dmapi')->updateDocumentStatus(uid('system'), $doc['id'], 'archived', $user->getId());
                $user->setSetting('source_of_funds_status', 'requested');
                $user->setSetting('source_of_funds_activated', '1');

                $deposit = phive('CasinoCashier')->getUserDepositByMtsTransactionId(
                    $user->getId(),
                    $mtsTransactionId
                );

                SourceOfFundsRequestedFlag::create(!empty($deposit) ? $deposit['id'] : null)->assign(
                    $user,
                    AssignEvent::ON_DEPOSIT_SUCCESS
                );

                if (empty($res['errors'])) {
                    phive('UserHandler')->logAction($user,
                        "The {$doc['tag']} document with id {$doc['id']} was expired because of missing data.",
                        'expired-missing-sowd');
                }
            }

        }
    }

    /**
     * A check for if a player should be restricted or not.
     *
     * Algo is as follows:
     * 1. We check if user didn't complete "Source of wealth declaration" for more than 30 days, if so we restrict + enforce a popup.
     * 2. We do nothing if the player is verified as that means that the necessary docs are OK.
     * 3. We start looping the docs and if a doc is not in the required status we do the total in and out amount check,
     * if it is larger than the threshold we return true.
     * 4. We check if the necessary docs even exists, if one or both are missing we do the threshold check.
     * 5. If we passed all the tests we can return false, indicating that the player should not be restricted.
     *
     * @param DBUser $u_obj The user object.
     * @param Closure $restrict Optional closure to override the threshold check logic.
     *
     * @return bool True if player should be restricted, false otherwise.
     */
    public function doCheckRestrict($u_obj, $restrict = null){
        // SOWD - standard automatic flow from user actions (set when first visiting withdrawal page)
        // We do not check for this if the document is under verification (source_of_funds_status = processing)
        $first_visit_on_withdraw = $u_obj->getSetting('source_of_funds_waiting_since');
        if(!empty($first_visit_on_withdraw) && $u_obj->getSetting('source_of_funds_status') != 'processing') {
            $time = phive('Config')->getValue('documents', "sourceoffunds_expiry", '30');
            $frequency = phive('Config')->getValue('AML', "AML51-frequency", 'DAY');
            if($first_visit_on_withdraw <= phive()->hisMod("-$time $frequency")) {
                $u_obj->setSetting('sowd-enforce-verification', 1);
                return DBUserRestriction::SOWD;
            }
        }
        // SOWD - enforced flow from BO when rejecting a document
        if(!empty($u_obj->getSetting('sowd-enforce-verification'))) {
            return DBUserRestriction::SOWD;
        }

        if($u_obj->isVerified()){
            return false;
        }

        $reason = lic('shouldBeRestricted', [$u_obj], $u_obj);

        if (!empty($reason)) {
            return $reason;
        }

        $uid = uid($u_obj);

        $restrict = $restrict ?? function() use ($u_obj, $uid){
            $thold = chg(phive('Currencer')->baseCur(), $u_obj, phive('Config')->getValue('restrict', 'thold'), 1);
            $deposits_and_withdrawals = phive('Cashier')->getUserDepositAndWithdrawalSum($uid);
            $sum = $deposits_and_withdrawals['deposit'] + $deposits_and_withdrawals['withdrawal'];
            return $sum >= $thold ? DBUserRestriction::CDD_CHECK : false;
        };

        $docs = phive('Dmapi')->getUserDocumentsV2($uid);

        if ($docs == 'service not available') { //When DMAPI is not available we don't do anything
            return false;
        }
        $statuses = ['approved', 'processing'];
        $result = 0;
        foreach($docs as $doc){
            if(in_array($doc['tag'], ['idcard-pic', 'addresspic'])){
                if(!in_array($doc['status'], $statuses)){
                    return $restrict();
                }
                $result++;
            }
        }

        // The necessary documents do not even exist.
        if ($result < 2) {
            return $restrict();

        }

        return false;
    }

    /**
     * Wraps doCheckRestrict() in order to determine if a user should be unrestricted.
     *
     * @uses DBUserHandler::doCheckRestrict()
     *
     * @param DBUser $u_obj The user object.
     * @param string $document_tag Type of document to check.
     */
    public function doCheckUnrestrict($u_obj, $document_tag) {
        if (in_array($document_tag, ['idcard-pic', 'addresspic'])) {
            if($u_obj->isRestricted() && $this->doCheckRestrict($u_obj,  function(){ return true; }) === false){
                $u_obj->unRestrict();
            }
        }
    }

    /**
     * Set ajax_context on DBUserHandler.
     *
     * @param bool $value True if we are in an AJAX / XHR context.
     * @return $this We return $this to enable chaining.
     */
    public function setAjaxContext($value = true)
    {
        $this->ajax_context = $value;

        return $this;
    }

    /**
     * Handle returning (as in wants to play again) permanent self excluded user.
     *
     * @param DBUser $user The user object.
     */
    public function handleReturningPermanentSelfExcludedUser($user)
    {
        if (empty(lic('permanentSelfExclusion')) || empty($user->getNid())) {
            return;
        }

        // try to get the permanent self excluded account associated to $nid
        $associated_nid_account = lic('expiredPermanentExclusionAccount', [$user->getNid()], $user);
        if (empty($associated_nid_account)) {
            return;
        }

        $user->setSetting('id_before_exclusion', $associated_nid_account->getId());
        $associated_nid_account->setSetting('id_after_exclusion', $user->getId());
    }

    /**
     * Send email/sms code for users who were not verified externally .
     *
     * @param DBUser $user
     * @return DBUserHandler
     */
    public function handleInternalUserVerification(DBUser $user): DBUserHandler
    {
        if((isOneStep() || isBankIdMode()) && ($user->getSetting('email_code_verified') === 'yes' || $user->getSetting('sms_code_verified') === 'yes')){
            return $this;
        }

        if (lic('verifyCommunicationChannel', null, $user)) {
            $this->sendEmailCode(false, $user->getId());
            $this->sendSmsCode(false, $user->getId());
        } else {
            $user->setSetting('sms_code_verified', 'yes');
            $user->setSetting('email_code_verified', 'yes');
        }

        return $this;
    }

    /**
     *  Get registration step2 fields config
     * Wrapper of RegistrationHTML getStep2FieldsV2 to acce
     *
     * @param bool $translate
     * @param ?$u
     * @param array $data data refactor from global variables
     * @return array
     * @throws Exception
     */
    public function getStep2Fields(bool $translate = true, $u = null, array $data = []): array
    {
        require_once __DIR__ . '/Registration/RegistrationHtml.php';

        $result = [];
        $user = cu($u);

        if ($user->hasSetting('nid_data')) {
            $lookupData = json_decode($user->getSetting('nid_data'), true);
        } elseif (!empty($user->getNid())) {
            $lookupData = lic('lookupNid', [$user]);
            if (!empty($lookupData)) {
                $lookupData = $lookupData->getResponseData();
            }
        }

        if (lic('hasPrepopulatedStep2') && !empty($lookupData)) {
            $data['rstep2'] = lic('getPersonLookupHandler', [], $user)->mapLookupData($lookupData);
            $_SESSION['rstep2_disabled'] = $data['rstep2'];
        }

        $registrationStep2Fields = phive()->flatten(lic('registrationStep2Fields'));
        $fields = RegistrationHtml::getStep2FieldsV2($translate, $u, $data);

        foreach ($registrationStep2Fields as $fieldName) {
            if (isset($fields[$fieldName])) {
                $result[$fieldName] = $fields[$fieldName];
            }
        }

        return $result;
    }

    /**
     * Registration data based on provided country ISO.
     *
     * TODO henrik refactor this, we do not include display related logic in a base class in order to
     * return HTML inside JSON.
     *
     * @param string|null $iso ISO2 country code.
     * @return array
     */
    public function getRegistrationData($iso = null)
    {
        if (empty($iso)) {
            $iso = phive('Licensed')->getLicCountry();
        }

        require_once __DIR__ . '/Registration/RegistrationHtml.php';

        $getStep1FieldsAction = $this->getGetStep1FieldsAction();
        $fields = $this->prepareStep1Fields($iso);

        $a = lic('loadJs', [true]);

        $iso_domain_redirection = phive('IpBlock')->getIsoDomainRedirection($iso);
        $maintenance = lic('getLicSetting', ['scheduled_maintenance']);
        $disable_mitID = licSetting('mit_id_disabled') ?? true;
        $is_maintenance_mode = !empty($maintenance) && $maintenance['enabled'];
        $is_allowed_auth = $this->isRegistrationAndLoginAllowed();
        return [
            "disabled_mitid" => $disable_mitID,
            "iso" => $iso,
            "fields" => $getStep1FieldsAction->getFields($fields, true),
            "button" => [
                "click" => "handleRegistrationStep1()",
                "message" => lic('getRegistrationMessage'),
                "disabled" => $is_maintenance_mode || !$is_allowed_auth
            ],
            "extra_button" => licHtml('register_second_button', null, true),
            "scripts" => array_values(array_merge([getFileWithCacheBuster("/phive/modules/Licensed/Licensed.js")], lic('loadJs', [true], null, null, $iso))),
            "checkboxes" => RegistrationHtml::getStep1Checkboxes(),
            "iso_domain_redirection" => $iso_domain_redirection ? $iso_domain_redirection . '?signup=true' : false
        ];
    }

    /**
     * @param string $iso
     *
     * @return array
     */
    private function prepareStep1Fields(string $iso): array
    {
        $step1FromSession = $_SESSION['rstep1'] ?? [];
        // Disable password prefill
        $step1FromSession['password'] = '';

        $referringFriend = filter_input(INPUT_POST, 'referring_friend', FILTER_SANITIZE_STRING);

        $step1FieldsData = Step1FieldsData::fromArray($step1FromSession + ["referring_friend" => $referringFriend]);
        $countryService = CountryServiceFactory::create();

        return $this->step1FieldsFactory()->make(
            $iso,
            $countryService,
            false,
            $step1FieldsData
        );
    }

    /**
     * @param DBUser $user
     */
    public function getUserLocalTimezone($user)
    {
        $timezone = phive('IpBlock')->getLocalTimeZone($user->getAttr('cur_ip') ?: $user->getAttr('reg_ip'));

        if (empty($timezone)) {
            $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $user->getCountry())[0] ?? null;
        }

        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        return $timezone;
    }

    /**
     * Count Login Frequency By Period
     *
     * @param int    $user_id
     * @param string $start_date
     * @param string $end_date
     *
     * @return int
     */
    public function countLoginsByPeriod(int $user_id, string $start_date, string $end_date): int
    {
        $sql = "SELECT COUNT(*) as logins
                FROM actions
                WHERE tag = 'last_login'
                AND target = $user_id
                AND created_at  > '$start_date'
                AND created_at  < '$end_date'
        ";
        return phive('SQL')->sh($user_id)->loadArray($sql)[0]['logins'];
    }

    /**
     * @return array
     */
    public function getResidenceCountryList(): array
    {
        require_once __DIR__ . '/Registration/RegistrationHtml.php';

        return RegistrationHtml::getResidenceCountriesList();
    }

    /**
     * @param bool $add_blocked
     * @return array
     */
    public function getCallingCodes(bool $add_blocked = false): array
    {
        $data = phive('SQL')->loadArray("SELECT iso, calling_code FROM bank_countries");

        if (! $add_blocked) {
            $allowed_sms_countries = phive('Config')->valAsArray('sms', 'countries');

            $data = array_filter($data, fn ($item) => in_array($item['iso'], $allowed_sms_countries, true));
        }

        $result = [];
        foreach ($data as $item) {
            $result[$item['iso']] = $item['calling_code'];
        }

        return $result;
    }

    public function getCallingCodesForDropdown(): array
    {
        $iso_calling_code_map = phive('DBUserHandler')->getCallingCodes();
        $calling_codes = array_unique(array_values($iso_calling_code_map));
        $calling_codes = array_filter($calling_codes, function($code) {
            return $code !== '0';
        });

        asort($calling_codes);

        return array_combine($calling_codes, $calling_codes);
    }

    /**
     * Validates user's input as first part of registration process.
     *
     * Is used by `/auth/registration-register-1` endpoint.
     *
     * Uses refactored (from global variables) version of method @link  RegistrationHtml::validateStep1Fields
     *
     * @api
     *
     * @param  \Laraphive\Domain\User\DataTransferObjects\RegisterUserStep1Data  $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\ValidateStep1FieldsResponse
     */
    public function validateStep1Fields(RegisterUserStep1Data $data, bool $isApi): ValidateStep1FieldsResponse
    {
        require_once __DIR__ . "/Registration/RegistrationHtml.php";

        $country = $data->getCountry();

        $form = [
          "email" => $data->getEmail(),
          "country" => $country,
          "mobile" => $data->getMobile(),
          "country_prefix" => $data->getCountryPrefix(),
          "password" => $data->getPassword(),
          "conditions" => $data->getIsTerms(),
          "privacy" => $data->getIsPolicy(),
        ];

        if($_SESSION['rstep1']['pnp_user']){
            $form['personal_number'] = $_SESSION['rstep1']['pnp_user'];
        }

        [$errors] = RegistrationHtml::validateStep1FieldsV2($form, $country, false, $isApi);

        return new ValidateStep1FieldsResponse($errors);
    }

    /**
     * Finalizes registration step 1.
     *
     * Is used inside `/auth/registration-register-1` endpoint.
     *
     * Uses refactored (from global variables) version of method @link  RegistrationHtml::finalizeRegistrationStep1
     *
     * @api
     *
     * @param  \Laraphive\Domain\User\DataTransferObjects\RegisterUserStep1Data  $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\FinalizeRegistrationStep1Response
     */
    public function finalizeRegistrationStep1(RegisterUserStep1Data $data): FinalizeRegistrationStep1Response
    {
        require_once __DIR__ . "/Registration/RegistrationHtml.php";

        $request = [
            "email" => $data->getEmail(),
            "country" => $data->getCountry(),
            "mobile" => $data->getMobile(),
            "country_prefix" => $data->getCountryPrefix(),
            "password" => $data->getPassword(),
            "conditions" => $data->getIsTerms(),
            "privacy" => $data->getIsPolicy(),
            "cur_req_id" => $data->getCurReqId(),
            "bonus_code" => $data->getBonusCode()
        ];


        if (RegistrationHtml::intermediaryStepRequired($request)) {
            $context = $this->getRegistrationContext($request);

            $intermediaryStep = lic(
                'initIntermediaryStep',
                [$context, $data->getSessionId(), true]
            );

            return FinalizeRegistrationStep1ResponseFactory::createWithThirdPartyVerificationFields($intermediaryStep);
        }

        $finalizeRegistrationStep1Data = $this->createFinalizeRegistrationStep1Data($data->getEmail());

        [$dbUser, $errors] = RegistrationHtml::finalizeRegistrationStep1V2($request, $finalizeRegistrationStep1Data);

        if ($dbUser === false || count($errors)) {
            return FinalizeRegistrationStep1ResponseFactory::createError($errors);
        }

        return FinalizeRegistrationStep1ResponseFactory::createSuccess(intval($dbUser->getId()));
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RegisterUserStep2Data $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\ValidateStep2FieldsResponse
     */
    public function validateStep2Fields(RegisterUserStep2Data $data): ValidateStep2FieldsResponse
    {
        require_once __DIR__ . "/Registration/RegistrationHtml.php";

        $user = $this->currentUser;

        $request = RegisterUserStep2RequestFactory::create($data, $user->getId());

        $errors = RegistrationHtml::validateStep2FieldsV2($request, $user, false);

        $errors2 = lic('openAccountNaturalPerson', [$user, false]);

        if (is_array($errors2)) {
            $errors = array_merge($errors, $errors2);
        }

        return new ValidateStep2FieldsResponse($errors);
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\RegisterUserStep2Data $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\FinalizeRegistrationStep2Response
     */
    public function finalizeRegistrationStep2(RegisterUserStep2Data $data): FinalizeRegistrationStep2Response
    {
        require_once __DIR__ . "/Registration/RegistrationHtml.php";

        $user = $this->currentUser;

        $request = RegisterUserStep2RequestFactory::create($data, $user->getId());

        $response = RegistrationHtml::finalizeRegistrationStep2V2($request, $user, [], true);
        $responseCount = count($response);

        if ($responseCount == 2) {
            [$data, $errors] = $response;
        } elseif ($responseCount == 1) {
            $errors = $response[0];
        } else {
            $errors = [];
        }

        if (!empty($errors)) {
            $response = RegistrationHtml::failureResponse($errors);
            return FinalizeRegistrationStep2Response::createError($response);
        }

        return FinalizeRegistrationStep2Response::createSuccess();
    }

    /**
     * Implement `Widthdraw` endpoint.
     *
     * @api
     *
     * @param \Laraphive\Domain\Payment\DataTransferObjects\Requests\WithdrawRequestData $data
     *
     * @return \Laraphive\Domain\Payment\DataTransferObjects\Responses\WithdrawResponseData
     */
    public function withdraw(WithdrawRequestData $data): WithdrawResponseData
    {
        $responseFactory = new WithdrawResponseFactory();
        $handler = phive('Cashier/WithdrawStart');

        $initResult = $handler->init("phive", cu());
        if ($initResult !== true) {
            $handler->failStop($initResult, false, true);

            return $responseFactory->createErrorResponse($initResult);
        }

        $args = $this->createWithdrawArgs($data);

        // TODO: Temporary change - This should be removed once Phive is refactored to handle amounts in cents in [PM-1704]
        $args[1]['amount'] = $data->amountInCents ? $args[1]['amount'] / 100 : $args[1]['amount'];

        $executeResult = call_user_func_array([$handler, 'execute'], $args);

        $error = is_string($executeResult) ? $executeResult
            : $executeResult['error_msg_alias'] ?? $executeResult['errors'] ?? null;

        if ($error) {
            $handler->failStop($error, false, true);

            return $responseFactory->createErrorResponse($error, $executeResult['error_msg_params'] ?? null);
        }

        return $responseFactory->createSuccessResponse($executeResult['amount']);
    }

    /**
     * @param \Laraphive\Domain\Payment\DataTransferObjects\Requests\WithdrawRequestData $data
     *
     * @return array
     */
    private function createWithdrawArgs(WithdrawRequestData $data): array
    {
        return [
            'card',
            [
                "action"       => "withdraw",
                "supplier"     => $data->getPaymentMethod(),
                "network"      => $data->getPaymentMethod(),
                "ccard_select" => $data->getCardId(),
                "amount"       => $data->getAmount(),
            ],
        ];
    }

    /**
     * Create FinalizeRegistrationStep1Data DTO. If user with $email exists DTO will contain user's id, otherwise
     * `empty` object returned.
     *
     * @param string $email
     *
     * @return \Laraphive\Domain\User\Actions\Steps\DataTransferObjects\FinalizeRegistrationStep1Data
     */
    private function createFinalizeRegistrationStep1Data(string $email): FinalizeRegistrationStep1Data
    {
        $user = $this->getUserByEmail($email);
        if (!is_null($user)) {
            return FinalizeRegistrationStep1Data::fromArray([
                'userId' => (int)$user->getId(),
            ]);
        }

        return FinalizeRegistrationStep1Data::createEmpty();
    }

    /**
     * Update password logic.
     *
     * @param int $userId
     * @param string $password
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateUserPasswordResponse
     *
     * @api
     */
    public function updatePassword(int $userId, string $password): UpdateUserPasswordResponse
    {
        $error = PhiveValidator::start($password)->strictPassword($this->getSetting('password_min_length'));

        if (! empty($error)) {
            return (new UpdateUserPasswordResponse(sprintf('password.err.%s', $error), true));
        }

        $user = $this->getUser($userId);

        if (empty($user)) {
            return (new UpdateUserPasswordResponse('no.user.found', true));
        }

        $user->setPassword($password);
        $user->deleteSetting('pwd_changed');

        return new UpdateUserPasswordResponse();
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData $loginCommonData
     * @param string $reason
     *
     * @return void
     *
     * @api
     */
    public function logoutWithAPI(LoginCommonData $loginCommonData, string $reason): void
    {
        $this->logout($reason, false, true, $loginCommonData);
    }

    /**
     * Get user documents
     *
     * @api
     *
     * @param int $user_id
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\ListDocumentsResponse
     */
    public function listDocuments(int $user_id): ListDocumentsResponse
    {
        $user = cu($user_id);
        $raw_documents = phive('Dmapi')->getUserDocumentsV2($user_id);

        if ($raw_documents == 'service not available') {
            return ListDocumentsResponse::createError(t('cannot_get_documents'));
        }

        $documents = (new UserDocuments($raw_documents, $user))->formatDocuments();
        $can_withdraw = phive('Cashier')->canWithdraw($user);
        $restriction = $user->getDocumentRestrictionType();
        $account_status = $user->getSetting("verified") == '1' ? '1' : '2';
        $name = 'withdrawal_documents';
        $message = '';

        if (! is_bool($restriction) || $can_withdraw['success'] === false) {
            $message = t($restriction);
        }

        return ListDocumentsResponse::createSuccess($account_status, $name, $message, $documents);
    }

    /**
     * Endpoint for documents uploading
     *
     * @api
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UploadDocumentsResponse
     */
    public function uploadDocuments(): UploadDocumentsResponse
    {
        require_once __DIR__ . "/../BoxHandler/boxes/diamondbet/GmsSignupUpdateBoxBase.php";
        $gms_signup_update_box = new GmsSignupUpdateBoxBase();
        $gms_signup_update_box->cur_user = cu();
        $document_type = $_POST['document_type'];
        $document = phive('dmapi')->getDocumentById($_POST['document_id'], $gms_signup_update_box->cur_user->getId());

        if($document['tag'] != $document_type) {
            return new UploadDocumentsResponse(['Wrong document type']);
        }

        if($document['status'] == UserDocuments::STATUS_APPROVED) {
            return new UploadDocumentsResponse(["You can't upload documents for approved status"]);
        }

        if ($document['tag'] == UserDocuments::TAG_ID_CARD_PIC) {
            if (isset($_POST['idtype']) && ! in_array($_POST['idtype'], ['PASSPORT', 'ID_CARD', 'DRIVING_LICENSE'])) {
                return new UploadDocumentsResponse(['Wrong id type']);
            }

            if (isset($_POST['idtype']) && $_POST['idtype'] == 'PASSPORT' && count($_FILES) > 1) {
                return new UploadDocumentsResponse(['You can upload only one file for passport']);
            }

            if($document['status'] == UserDocuments::STATUS_PROCESSING) {
                return new UploadDocumentsResponse(["You can't upload documents for progressing status"]);
            }
        } elseif ($document['tag'] == UserDocuments::TAG_SOURCE_OF_INCOME_PIC) {
            if (isset($_POST['income_types'])
                && is_array($_POST['income_types'])
                && array_diff($_POST['income_types'], UserDocuments::INCOME_TYPES)) {
                return new UploadDocumentsResponse(['Wrong income type']);
            }
        }

        $errors = $gms_signup_update_box->handleUploads2(true);

        return new UploadDocumentsResponse($errors);
    }

    /**
     * Endpoint for RG limits checking
     *
     * @api
     *
     * @param int $amount
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\CheckRgLimitsResponse
     */
    public function checkRgLimits(int $amount): CheckRgLimitsResponse
    {
        $response = phive('Casino')->lgaLimitsCheck(cu(), false, $amount);

        if ($response === 'OK') {
            return new CheckRgLimitsResponse();
        }

        return new CheckRgLimitsResponse($response);
    }

    function getDepositLimitResponse($userId, $deposit = 0): array
    {
       $user = cu($userId);
       $limit_response = [];
       if (rgLimits()->reachedType($user, rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT, $deposit, true)) {
           $ndl = rgLimits()->getLimit($user, rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT, 'month');
           $current_limit = rnfCents($ndl['cur_lim']) . " " . $user->getCurrency();
           $limit_response = [
                    'type' => rgLimits()::TYPE_CUSTOMER_NET_DEPOSIT,
                    'reset_date' => date('Y-m-d', (strtotime($ndl['resets_at']))),
                    'current_limit' => $current_limit
           ];
        }

        $limits = rgLimits()->getByTypeUser($user, rgLimits()::TYPE_DEPOSIT);
        foreach ($limits as $limit) {
            if (rgLimits()->isReached($limit, 0, true)) {
                $current_limit = rnfCents($limit['cur_lim']) . " " . $user->getCurrency();

                $temp = [
                    'type' => rgLimits()::TYPE_DEPOSIT,
                    'reset_date' => $limit['resets_at'],
                    'current_limit' => $current_limit
                ];
                if (empty($limit_response) || $temp['reset_date'] < $limit_response['reset_date']) {
                    $limit_response = $temp;
                }
            }
        }

        return $limit_response;
    }

    /**
     * @api get game history endpoint
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\GameHistoryData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\GameHistoryResponse
     */
    public function getGameHistory(GameHistoryData $data): GameHistoryResponse
    {
        require_once __DIR__ . '/../../../diamondbet/boxes/AccountBox.php';
        $account_box = new AccountBox();
        $account_box->init(true);
        $account_box->cur_user = cu();
        $response = $account_box->getGameHistory($data);
        $factory = new GameHistoryFactory($response);

        return $factory->createResponse();
    }

    /**
     * @param int $cents
     * @param string $decPoint
     * @param string $thousandsSep
     *
     * @return int|string|null
     */
    public function formatCents(int $cents, string $decPoint = '.', string $thousandsSep = ',')
    {
        return rnfCents($cents, $decPoint, $thousandsSep);
    }

    /**
     * @api get login history endpoint
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginHistoryData $loginHistoryData
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\LoginHistoryResponse
     */
    public function loginHistory(LoginHistoryData $loginHistoryData): LoginHistoryResponse
    {
        $session_history_box = phive('BoxHandler')->getRawBox('SessionHistoryBox');
        $session_history_box->init(cu());
        $start_date = $session_history_box->getFilterDate($loginHistoryData->getStartDate(), '-1 day');
        $end_date = $session_history_box->getFilterDate($loginHistoryData->getEndDate());
        $listResponse = $session_history_box->getUserSessions($start_date, $end_date, $loginHistoryData->getPage(), $loginHistoryData->getLimit());
        $paginator = $session_history_box->getPaginator();
        $factory = new LoginHistoryFactory($listResponse, $paginator->getTotal());

        return $factory->createResponse();
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\AccountHistoryData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\AccountHistoryResponse
     */
    public function getAccountHistory(AccountHistoryData $data): AccountHistoryResponse
    {
        require_once __DIR__ . '/../../../diamondbet/boxes/AccountBox.php';
        $account_box = new AccountBox();
        $account_box->init(true);
        $account_box->setTrTypes();
        $account_box->cur_user = cu();
        $response = $account_box->getAccountHistory($data);

        foreach ($response['transactions'] as &$item) {
            $item['description'] = $account_box->prTrDescr($item, true);
        }

        $factory = new AccountHistoryFactory();

        return $factory->createResponse($response);
    }

    /**
     * Endpoint for get edit profile
     *
     * @param UserContactData $data
     * @param bool $isApi
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\EditProfile\EditProfileResponseData
     * @api
     */
    public function getEditProfile(UserContactData $data, bool $isApi = false): EditProfileResponseData
    {
        $user = cu($data->getId());

        $response = $this->getEditProfileFields($user, $data, $isApi);
        $factory = new EditProfileFactory();

        return $factory->createResponse($response);
    }

    /**
     * Endpoint for getting Import User From Brand popup content
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\Responses\ImportUserFromBrandPopupResponseData
     * @api
     */
    public function getImportUserFromBrandPopupData(): ImportUserFromBrandPopupResponseData
    {
        $popup_data = [
            'title' => 'import.user.from.brand.title',
            'description' => 'import.user.from.brand.description',
            'submit' => 'import.user.from.brand.yes',
            'cancel' => 'import.user.from.brand.no',
        ];

        $factory = new ImportUserFromBrandPopupFactory();

        return $factory->createResponse($popup_data);
    }

    /**
     * @param DBUser $user
     * @param UserContactData $data
     * @param bool $isApi
     *
     * @return array
     */
    private function getEditProfileFields(DBUser $user, UserContactData $data, bool $isApi): array
    {
        $excluded_languages = lic('getExcludedRegistrationLanguages', [], $user);
        $languagesList = phive("Localizer")
            ->filterLanguageOptions($user)
            ->getLangSelect("WHERE selectable = 1", $excluded_languages);

        $currencies = phive('DBUserHandler')->getCurrencies();

        $personalInfo = (new EditProfilePersonalInfoFactory())
            ->createPersonalInfo($user, $languagesList, $currencies, $isApi);
        $contactInfo = (new EditProfileContactInfoFactory())->createContactInfo($user, $data, $isApi);
        $accountInfo = (new EditProfileAccountInfoFactory())->createAccountInfo();
        $termsConditions = (new EditProfileTermsConditionsFactory())->createTermsConditions();

        return compact('personalInfo', 'contactInfo', 'accountInfo', 'termsConditions');
    }

    /**
     * @param int $userId
     * @param array $data
     *
     * @return \Laraphive\Support\DataTransferObjects\ErrorsOrEmptyResponse
     */
    public function updateContactInformation(int $userId, array $data): ErrorsOrEmptyResponse
    {
        $user = cu($userId);
        lic('updateEmail', [$userId, $data['email']], $userId);
        $updated_province = lic('updateUserProvince', [$userId, $data['city']], $userId);
        if (!empty($updated_province['error'])) {
            return new ErrorsOrEmptyResponse(['province.update.fail']);
        }
        if ($updated_province && !$updated_province['found']) {
            return new ErrorsOrEmptyResponse(['contact.details.updated.city_not_found']);
        }
        cu()->setContactInfo($data);

        phive("UserHandler")->logAction(
            $userId,
            "Updated profile with following info: " . var_export($data, true),
            "profile-update-success",
            true);

        phive()->pexec('Cashier/Fr', 'invoke', ['updateUserOnExternalKycMonitoring', $userId]);

        return new ErrorsOrEmptyResponse();
    }

    /**
     * Common logic for filtering an array of db rows on by whether or not a country is allowed by each database row.
     *
     * @param array $db_rows The database rows.
     * @param DBUser $u_obj The user to work with, if omitted we check session or IP data.
     * @param string $excluded_key Excluded countries field name override.
     * @param string $included_key Included countries field name override.
     *
     * @return array The filtered array.
     */
    public function filterByCountry($db_rows, $u_obj = null, $excluded_key = 'excluded_countries', $included_key = 'included_countries'){
        $country  = empty($u_obj) ? cuCountry() : $u_obj->getCountry();
        return array_filter($db_rows, function($row) use($country, $excluded_key, $included_key){
            return $this->allowCountry($row, $country, $excluded_key, $included_key);
        });
    }

    /**
     * Common logic for determining if a given country is allowed by a certain database row.
     *
     * @param array $db_row The database row.
     * @param string $country ISO2 country code.
     * @param string $excluded_key Excluded countries field name override.
     * @param string $included_key Included countries field name override.
     *
     * @return bool False if the row should be excluded from view etc from the country in question, true otherwise.
     */
    public function allowCountry($db_row, $country, $excluded_key = 'excluded_countries', $included_key = 'included_countries'){

        $country = strtolower($country);

        if(!empty($db_row[$included_key])){
            // Country included has highest priority, we immediately return whether or not the country is included.
            return strpos(strtolower($db_row[$included_key]), $country) !== false;
        }

        if(!empty($db_row[$excluded_key]) && strpos(strtolower($db_row[$excluded_key]), $country) !== false){
            // The country is excluded, we return false.
            return false;
        }

        // No instructions on whether or not to include or exclude the country so we include per default by returning true.
        return true;
    }

    /**
     * This function is called on Login by VS-AUTH consumer.
     *
     * @param int $user_id
     * @return void
     */
    public function onLoginEvent(int $user_id) {
        $user = cu($user_id);

        if ($user->hasDeposited() || $user->getSetting('source_of_funds_waiting_since')) {
            // Check if we need to restrict the player (Ex. enforce him to fill SOWD docs after 30 day)

            if (empty($user->isRestricted())) {
                $restriction_reason = phive('DBUserHandler')->doCheckRestrict($user);

                if ($restriction_reason) {
                    $user->restrict($restriction_reason);
                }
            }
            rgLimits()->doCheckNetDepositLimit($user);
        }
    }

    /**
     * @param int $userId
     * @param string $passwordCurrent
     * @param string $password
     * @param string $passwordConfirmation
     *
     * @return array{user_id: int, err: array}
     *
     * @api
     */
    public function handleUpdateProfilePassword(
        int $userId,
        string $passwordCurrent,
        string $password,
        string $passwordConfirmation
    ): array {
        $_POST['password0'] = $passwordCurrent;
        $_POST['password'] = $password;
        $_POST['password2'] = $passwordConfirmation;
        $_SESSION['mg_username'] = cu($userId)->data['username'];

        return $this->updateProfilePassword(true);
    }

    /**
     * @param bool $update
     *
     * @return array{user_id: int, err: array}
     */
    public function updateProfilePassword(bool $update): array
    {
        $user_id = 0;

        $req_fields = $this->getReqFields(['password0', 'password', 'password2']);
        $err = $this->validateUser('chpwd', $req_fields);

        if (empty($err)) {
            $user_id = $this->createUpdateUser($update);
        }

        return ['user_id' => $user_id, 'err' => $err];
    }

   public function getRegistrationContext($request): string
    {
        $allowedCountries = phive('DBUserHandler')->getSetting('zignsec_v5')['has_mit_id'];
        return in_array($request['country'], $allowedCountries) || $request['mitID']
            ? RegistrationHtml::CONTEXT_REGISTRATION_MITID
            : RegistrationHtml::CONTEXT_REGISTRATION;
    }


    /**
     * @param SimilarityCheckData $data
     * @return int
     */
    public function similarityCheck(SimilarityCheckData $data): int {
        $user = cu($data->getId());

        if (empty($user)) {
            phive('Logger')->getLogger('payments')->info(
                "MTS provided a non existing userID on the DB, check the account and remove it via script.",
                ['user_id' => $data->getId()]
            );

            return 0;
        }

        if ($data->getPersonId()) {
            if ($user->getNid() === $data->getPersonId()) {
                return 100;
            }

            if ($user->getCountry() . $user->getNid() === $data->getPersonId()) {
                return 100;
            }
        }

        $userFullname = $user->getFullName();
        $userAddress = $user->getAddress();
        $userCity = $user->getCity();
        $userZipcode = $user->getZipcode();

        $dataFullname = $data->getFullname();
        $dataAddress = $data->getAddress();
        $dataCity = $data->getCity();
        $dataZipcode = $data->getZipcode() ?? '';


        $defaultWeights = [
            'full_name' => 0.5,
            'city' => 0.2,
            'address' => 0.2,
            'zip_code' => 0.1
        ];

        $similarities = [
            'full_name' => $this->calculateJaroWinklerSimilarity($userFullname, $dataFullname),
            'city' => $this->calculateLevenshteinSimilarity($userCity, $dataCity),
            'address' => $this->calculateJaroWinklerSimilarity($this->normalizeAddress($userAddress), $this->normalizeAddress($dataAddress)),
            'zip_code' => ($this->normalizeZipcode($userZipcode) === $this->normalizeZipcode($dataZipcode)) ? 100 : null
        ];

        $overallSimilarity = $this->calculateOverallSimilarity($similarities, $defaultWeights);
        return (int)$overallSimilarity;
    }

    /**
     * @param $input1
     * @param $input2
     * @return float|int|null
     */
    private function calculateLevenshteinSimilarity($input1, $input2) {
        if (empty($input1) || empty($input2)) return null;
        $distance = levenshtein(strtolower($input1), strtolower($input2));
        $maxLength = max(strlen($input1), strlen($input2));
        return ($maxLength == 0) ? 100 : (($maxLength - $distance) / $maxLength * 100);
    }

    /**
     * @param $input1
     * @param $input2
     * @return float|null
     */
    private function calculateJaroWinklerSimilarity($input1, $input2) {
        if (empty($input1) || empty($input2)) return null;
        similar_text(strtolower($input1), strtolower($input2), $percentage);
        return $percentage;
    }

    /**
     * @param $address
     * @return string
     */
    private function normalizeAddress($address) {
        if (empty($address)) return '';
        $address = strtolower($address);
        $address = str_replace(['st.', 'ave', 'blvd'], ['street', 'avenue', 'boulevard'], $address);
        return trim(preg_replace('/\s+/', ' ', $address));
    }

    /**
     * @param string $zipcode
     * @return string
     */
    private function normalizeZipcode(string $zipcode): string {
        return preg_replace('/[^a-zA-Z0-9]/', '', $zipcode);
    }


    /**
     * @param $similarities
     * @param $defaultWeights
     * @return float|int
     */
    private function calculateOverallSimilarity($similarities, $defaultWeights) {
        $validSimilarities = [];
        $validWeights = [];

        foreach ($similarities as $field => $value) {
            if (!is_null($value)) {
                $validSimilarities[] = $value;
                $validWeights[] = $defaultWeights[$field];
            }
        }

        if (count($validSimilarities) < 2) {
            return 0;
        }

        $weightSum = array_sum($validWeights);
        $normalizedWeights = array_map(function($weight) use ($weightSum) {
            return $weight / $weightSum;
        }, $validWeights);

        $overallSimilarity = 0;
        foreach ($validSimilarities as $index => $similarity) {
            $overallSimilarity += $similarity * $normalizedWeights[$index];
        }

        return round($overallSimilarity, 2);
    }

    /**
     * This method is used on page loading to check if a user being forced to verify documents for further redirection
     *
     * @param DBUser $user
     * @param string $request_uri
     *
     * @return bool
     */
    public function isUserEnforcedToDocumentVerification(DBUser $user, string $request_uri): bool
    {
        return $user->isRestricted() &&
            !(
                strpos($request_uri, '/cashier/withdraw/') !== false &&
                $user->getSetting('restriction_reason') === DBUserRestriction::SOWD
            );
    }

    /**
     * @param $data
     * @return ValidateStep1FieldsResponse
     */
    public function checkCountryCaptcha($data): CaptchaResponseData
    {
        require_once __DIR__ . "/Registration/RegistrationHtml.php";


        $responseFactory = new RegisterUserStep1Factory();
        $country = $data->getCountry();
        $errors = RegistrationHtml::checkCountryCaptcha($data, $country, true);
        return $responseFactory->createError($errors);
    }

    /**
     * @param UserRtpSearchRequestData $data
     * @return UserRtpSearchResponseData
     */
    public function getRtpGameSessions(UserRtpSearchRequestData $data): UserRtpSearchResponseData
    {
        $user = cu();
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');

        $translations = [
            'translations' => [
                'view' => t('view'),
                'week' => t('week'),
                'bet' => t('bet'),
                'win' => t('win')
            ]
        ];

        if ($data->getSessionId()) {
            $result = $mg->rtpGetBetsWins($user, $data->getSessionId(), [0, 30]);
            return (new UserRtpSearchFactory())->createSuccess($result, $translations);
        }

        // Generate cache key
        $cacheKey = serialize(array_merge(
            [
                'action' => 'getRtpGameSessions',
                'userId' => $user->userId
            ],
            $data->getFilledData()
        ));

        // Check for cached result
        $cachedResult = phQget($cacheKey);
        if (!empty($cachedResult)) {
            return (new UserRtpSearchFactory())->createSuccess($cachedResult, $translations);
        }

        // Combine date and time into a single string for the start and end dates
        $dateFrom = $data->getStartDate();
        $dateTo = $data->getEndDate();

        // Format the dates into the desired format
        $dates = [
            $dateFrom->format('Y-m-d H:i:s'),
            $dateTo->format('Y-m-d H:i:s')
        ];

        // Retrieve the game sessions using the formatted dates
        try {
            $result = $mg->rtpGetGameSessions($user, $data->getGameId(), $dates, $data->getSort());
        } catch (Exception $e) {
            return (new UserRtpSearchFactory())->createError($e->getMessage());
        }

        // Cache the result
        phQset($cacheKey, $result, 15);

        // Return the result wrapped in a UserRtpSearchResponseData object
        return (new UserRtpSearchFactory())->createSuccess($result, $translations);
    }

    public function getGameRtpGraph(GetGameRtpGraphRequestData $data): GetGameRtpGraphResponseData
    {
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        $user = cu();

        if ($data->getSessionId()) {
            $result =  $mg->rtpGetSessionGraph($user, $data->getSessionId());
            return (new GetGameRtpGraphResponseDataFactory())->createSuccess($result);
        }

        $cached_result = phQget(
            $cache_key = serialize(
                array_merge(
                    [
                        'action' => 'getGameRtp',
                        'userId' => $user->userId
                    ],
                    $data->getFilledData()
                )
            )
        );

        if(!empty($cached_result)) {
            return (new GetGameRtpGraphResponseDataFactory())->createSuccess($cached_result);
        }

        $dates = false;
        if ($data->getStartDate() && $data->getEndDate()) {
            $dates = [
                $data->getStartDate()->toDateTimeString(),
                $data->getEndDate()->toDateTimeString()
            ];
        }

        $result = $mg->rtpGetGraph(
            $user,
            $data->getGameId(),
            $data->getRtp(),
            $dates,
            $data->getType()
        );

        phQset($cache_key, $result, 15);

        return (new GetGameRtpGraphResponseDataFactory())->createSuccess($result);
    }

    /**
     *
     * @return GetGameRtpGraphResponseData
     */
    public function getRtpOverall(): GetRtpResponseData
    {
        $user = cu();
        $cached_result = phQget(
            $cache_key = serialize(
                [
                    'action' => 'getRtpOverall',
                    'userId' => $user->userId
                ]
            )
        );

        if(!empty($cached_result)) {
            return (new GetRtpResponseDataFactory())->createSuccess($cached_result);
        }

        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        $result = $mg->rtpGetByUserAPI($user);

        phQset($cache_key, $result, 15);

        return (new GetRtpResponseDataFactory())->createSuccess($result);
    }

    public function getRtp(GetRtpRequestData $data): GetRtpResponseData
    {
        $user = cu();
        $cached_result = phQget(
            $cache_key = serialize(
                array_merge(
                    [
                        'action' => 'getRtp',
                        'userId' => $user->userId
                    ],
                    $data->getFilledData(),
                )
            )
        );
        if(!empty($cached_result)) {
            return (new GetRtpResponseDataFactory())->createSuccess($cached_result);
        }
        /** @var MicroGames $mg */
        $mg = phive('MicroGames');
        $result = [];
        $dates = false;
        if ($data->getStartDate() && $data->getEndDate()) {
            $dates = [
                $data->getStartDate()->toDateTimeString(),
                $data->getEndDate()->toDateTimeString()
            ];
        }

        $limit = $data->getLimit() ? (int)$data->getLimit() : 5;
        $page = $data->getPage() ? max(1, (int)$data->getPage()) : 1;
        $offset = ($page - 1) * $limit;
        $limits = [$offset, $limit];

        switch($data->getType()){
            case GetRtpRequestData::TYPE_HIGHEST:
                $result = $mg->rtpGetListByUser($user, $data->getGame(), $dates, 'DESC', $limits);
                break;
            case GetRtpRequestData::TYPE_LOWEST:
                $result = $mg->rtpGetListByUser($user, $data->getGame(), $dates, 'ASC', $limits);
                break;
            case GetRtpRequestData::TYPE_ALL:
                $result = $mg->rtpGetListByUser($user, $data->getGame(), $dates, 'DESC', $limits, true);
                break;
            case GetRtpRequestData::TYPE_RECENT:
                $result = $mg->rtpGetListAll($user, $data->getGame(), $dates, 'DESC', $limits);
                break;
        }

        $result = $mg->formatRtpResult($result, $user);

        phQset($cache_key, $result, 15);

        return (new GetRtpResponseDataFactory())->createSuccess($result);
    }

    /**
     * @param User|int|null $user
     * @return User|null
     */
    public function getCuOrReg($user): ?User
    {
        if ($user instanceof User) return $user;

        $user = cu($user);
        if (empty($cu)) $user = cuRegistration(intval($user) > 0 ? $user : null);

        return ($user instanceof User) ? $user : null;
    }

    /**
     * Add user comment for a RG trigger in case when RG popup skipped due to internal interval 'popupsInterval'.
     *
     * @param DBUser $user
     * @param        $trigger
     *
     * @return void
     */
    private function addUserCommentOnRgPopup(DBUser $user, $trigger)
    {
        $rg_trigger_popups = lic('getLicSetting', ['rg-trigger-popups'], $user);

        if (in_array($trigger, $rg_trigger_popups)) {
            $risk_tag = phive("Cashier/Arf")->getLatestRatingScore($user->getId(), 'RG', 'tag');
            $dynamicVariablesSupplier = new DynamicVariablesSupplierResolver($user);
            $comment = t2(
                "{$trigger}.user.comment",
                $dynamicVariablesSupplier->resolve($trigger)->getUserCommentsVariables(['tag' => $risk_tag]),
                phive()->getSetting('user_comment_lang')
            );
            $user->addComment($comment, 0, 'automatic-flags');
        }
    }

	/**
	 * @param $user
	 * @return bool
	 */
	public function isWithdrawalForfeitAllowedForUserCountry($user): bool
	{
		if (phive('BrandedConfig')->getBrand() === BrandedConfig::BRAND_MEGARICHES) {
			return $user->getJurisdiction() != 'SGA';
		}

        return true;
	}
}

/**
* A global function wrapper around addEvent().
*
* @uses DBUserHandler::addEvent().
*
*
* @param string $tag The event / notification tag / type.
* @param int $amount Monetary amount if applicable.
* @param string $name The game name in case the notification is related to a game.
* @param string $url A URL, eg the play URL of the game in case the notification is related to a game.
* @param array $ud User data / row.
* @param string $img Potential image override, if empty we try and display image depending on context.
* @param bool $show_in_feed If the event / notification should show in the public news / events feed or not.
*
* @return null
*/
function uEvent($tag, $amount = '', $name = '', $url = '', $ud = array(), $img = '', $show_in_feed = true){
    if(phive('UserHandler')->getSetting('has_events') === true)
        phive('UserHandler')->addEvent($tag, $amount, $name, $url, $ud, $img, $show_in_feed);
}

/**
* A global helper function to determine if a user with a certain user id is logged in or not.
*
* @param int|null $uid If not null we might be in a CLI context so we check the DB users row for the logged_in flag.
*
* @return bool True if logged in, false otherwise.
*/
function isLogged($uid = null){
    if(!empty($_SESSION['mg_username']) || !empty($_SESSION['user_id']))
        return true;
    if(!empty($uid)){
        $ud = ud($uid);
        return empty($ud['logged_in']) ? false : true;
    }
    return false;
}

/**
* Global helper to return the game_id of the game being currently played.
*
* @param int $uid The id of the user we want to check, currently logged in user will be used if empty.
*
* @return string The game id, will be empty if the user is not playing anything.
*/
function curPlaying($uid = ''){
    if(empty($uid))
        $uid = cuPlId();
    return phMget(mKey($uid, 'curgid'));
}

/**
* Returns the id of the currently logged in user.
*
* @return int|null The user id if somebody is logged in, null otherwise.
*/
function cuPlId(){
    return $_SESSION['mg_id'];
}

// TODO henrik remove
function setCurPl($uobj){
    $_SESSION['mg_id'] = $uobj->getId();
    $_SESSION['mg_username'] = $uobj->getUsername();
    phive('Localizer')->setLanguage($uobj->getAttr('preferred_lang'), true);
    phive('UserHandler')->currentUser = $uobj;
}

/**
* Will try and use cu() with the user id first and if that search doesn't return anything non empty
* it will try and fetch the currently logged in user.
*
* This is more or less an alias around cu() but was used to separate fetching of player in the context
* of a logged in admin user. That situation is not relevant anymore since we launched the standalone
* BO.
*
* @param int $uid The user id, which is typically the case when we want to make a DB call to get the user,
* empty otherwise.
*
* @return null|DBUser The resultant DBUser object or null if no user could be found.
*/
function cuPl($uid = ''){
    if(!empty($uid))
        $u = cu($uid);
    if(empty($u))
        $u = cu();
    return $u;
}

/**
* A global wrapper to be able to get one of the currently logged in user's attributes / columns without triggering
* a fatal error in case nobody is logged in.
*
* @param string $attr The attribute / db column.
* @param int $uid The user id.
* @param bool $static Whether or not the attribute can be fetched from the cache or not, eg stuff like country and DOB.
*
* @return mixed The attribute value.
*/
function cuPlAttr($attr, $uid = '', $static = false){
    if($static && empty($uid))
        return $_SESSION['local_usr'][$attr];
    $u = cuPl($uid);
    if(!empty($u))
        return $u->getAttr($attr);
    return '';
}

/**
 * A global wrapper to be able to get one of the currently logged in user's settings without triggering
 * a fatal error in case nobody is logged in.
 *
 * @param string $key The setting name / key.
 * @param int $uid The user id.
 * @param mixed $default What to return in case the user does not exist (or is not logged in).
 *
 * @return mixed The setting value.
 */
function cuPlSetting($key, $uid = '', $default = ''){
    $u = cuPl($uid);
    if(!empty($u))
        $res = $u->getSetting($key);
    return empty($res) ? $default : $res;
}

/**
* Extremely handy function to get the country of the current logged in user or **visitor**, in case we're
* looking at a visitor the Maxmind IP database is used in order to resolve the country.
*
* @uses IpBlock::getCountry() in order to the the country from the IP (if needed).
*
* @param int $uid The user id.
* @param bool $get_cached Whether or not the attribute can be fetched from the cache or not, if off we override
* fetching reg country and checks IP anyway which is a way to detect VPN usage.
*
* @return string ISO2 country code.
*/
function cuCountry($uid = '', $get_cached = true){
    // If local site we can't do testing with VPNs to spoof IP anyway so we just use the users.country setting
    if($get_cached || phive()->isLocal())
        $country = cuPlAttr('country', $uid, true);
    if(empty($country))
        $country = phive('IpBlock')->getCountry();
    // We typically end up here if we're on local and not logged in
    if(empty($country))
        $country = phive('IpBlock')->getSetting('test_from_country');
    if(empty($country))
        $country = 'MT';
    return $country;
}

/**
* Sets / saves a user attribute / db column **and** the cached session value.
*
* @param string $attr The attribute.
* @param mixed $value The value to save.
*
* @return null
*/
function cuPlSetAttr($attr, $value){
    cu()->setAttribute($attr, $value);
    $_SESSION['local_usr'][$attr] = $value;
}

/**
 * Alias around a common load line to make coding quicker.
 *
 * @return RgLimits
 */
function rgLimits(){
    return phive('DBUserHandler/RgLimits');
}

/**
 * Alias around a common load line to make coding quicker.
 *
 * @return RealtimeStats
 */
function realtimeStats(){
    return phive('DBUserHandler/RealtimeStats');
}

/**
 * Helper method used to store user request data on key steps of the registration process.
 *
 * @param null $data The HTTP request data.
 * @param "" $context This can be set as a context label in case we need to track a particular activity with a context.
 * @return null
 */
function trackRegistration($data = null, $context = "") {
    if (empty($data)) {
        $data = $_REQUEST;
        unset($data['password']);
    }
    return phive()->dumpTbl('track-registration', [$data, remIp(), phive('IpBlock')->getCountry(), $context]);
}
