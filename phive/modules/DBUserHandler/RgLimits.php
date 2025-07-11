<?php

use Carbon\Carbon;
use Licensed\LicenseFactory;
use Videoslots\HistoryMessages\RGLimitChangeHistoryMessage;

/**
* A wrapper for handling the RG logic related to the rg_limits table.
*/
class RgLimits
{
    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_BALANCE = 'balance';
    public const TYPE_RC = 'rc';
    public const TYPE_BETMAX = 'betmax';
    public const TYPE_NET_DEPOSIT = 'net_deposit';
    public const TYPE_CUSTOMER_NET_DEPOSIT = 'customer_net_deposit';
    public const TYPE_LOSS = 'loss';
    public const ACTION_CURRENT = 'current';
    private const LIMIT_VALUE_REMOVED = -1;
    public const RESET_SPANS_IN_HOURS = ['day'=>24, 'week' => 168, 'month' => 720];

    private $users_cache = [];
    /**
     * @see Licensed::getRgChangeStamp()
     * @see RgLimits::getChangeStamp()
     * When using this variable "cooloff duration" will be handled with the following priority:
     * 1) forced by setting
     * 2) via jurisdictional config
     * 3) default config
     */
    const DEFAULT_COOLOFF = 'default-cooloff';

    /**
     * We use this variable to keep track if the enforce setting is already removed
     * This is done to skip multiple queries on users_settings on the limits with multiple time spans
     *
     * @var array
     */
    private $enforced_popup_removed = [
        'login' => false,
        'betmax' => false,
    ];

    /**
     * This is the list of rg limits that can be incremented or decremented
     *
     * @var array
     */
    private $incrementable_limits;

    /**
     * Determines if a admin2-backoffice request initiated the class.
     *
     * @var bool
     */
    public bool $admin2_context;
    /**
     * Determines if a remote request initiated the class.
     *
     * @var bool
     */
    public bool $remote_request_context;

    public const RG_LIMITS_LOG_TABLE = 'rg_limits_log';
    public const REQUEST_TYPE_ADD = 'add';
    public const REQUEST_TYPE_CHANGE = 'change';
    public const REQUEST_TYPE_REMOVE = 'remove';
    public const REQUEST_TYPE_REMOVE_NO_COOL_OFF = 'remove_no_cool_off';
    public const REQUEST_TYPE_ADD_FORCED_UNTIL = 'add_forced_until';
    public const REQUEST_TYPE_REMOVE_FORCED_UNTIL = 'remove_forced_until';
    /**
     * rg_limits_log.request_type enums
     */
    public const REQUEST_TYPE_ENUMS = [
        self::REQUEST_TYPE_ADD,
        self::REQUEST_TYPE_CHANGE,
        self::REQUEST_TYPE_REMOVE,
        self::REQUEST_TYPE_ADD_FORCED_UNTIL,
        self::REQUEST_TYPE_REMOVE_FORCED_UNTIL,
        self::REQUEST_TYPE_REMOVE_NO_COOL_OFF,
    ];

    public const REQUESTER_TYPE_USER = 'user';
    public const REQUESTER_TYPE_BO_USER = 'bo_user';
    public const REQUESTER_TYPE_SYSTEM = 'system';
    /**
     * rg_limits_log.requester_type enums
     */
    public const REQUESTER_TYPE_ENUMS = [
        self::REQUESTER_TYPE_USER,
        self::REQUESTER_TYPE_BO_USER,
        self::REQUESTER_TYPE_SYSTEM,
    ];

    /**
     * All other month limits will have the same resets_at timestamp
     *
     * @var array|string[]
     */
    private array $month_resets_at_timestamp_sources = [];

    function __construct(){
        $this->db         = phive('SQL');
        $this->uh         = phive('UserHandler');
        $this->flag_types = ['undo_withdrawal_optout'];
        $this->lock_types = ['lock', 'exclude', 'lockgamescat'];
        $this->resettable = ['wager', 'loss', 'deposit', 'login', 'net_deposit', 'customer_net_deposit'];
        $this->grouped_resettables = ['wager' => ['wager'], 'loss' => ['loss']];
        $this->individual_resettables = ['deposit', 'login', 'net_deposit'];
        $this->fixed_reset_time = ['net_deposit'];
        $this->time_types = ['rc', 'timeout', 'login'];
        $this->no_progress_types = ['betmax', 'undo_withdrawal_optout'];
        $this->month_resets_at_timestamp_sources = ['loss', 'deposit'];
        $this->all_types  = [
            'deposit'                   => 'money',
            'wager'                     => 'money',
            'loss'                      => 'money',
            'betmax'                    => 'money',
            'timeout'                   => 'm',
            'rc'                        => 'm',
            'lock'                      => 'd',
            'exclude'                   => 'd',
            'exclude_indefinite'        => 'bool',
            'login'                     => 'h',
            'lockgamescat'              => 'd',
            'undo_withdrawal_optout'    => 'bool',
            'net_deposit'               => 'money',
            self::TYPE_BALANCE          => 'money',
            self::TYPE_CUSTOMER_NET_DEPOSIT => 'money',
        ];
        $this->incrementable_limits = ['wager', 'loss', 'deposit', 'login', 'net_deposit', self::TYPE_BALANCE, self::TYPE_CUSTOMER_NET_DEPOSIT];
        $this->time_spans           = ['day', 'week', 'month'];
        $this->custom_time_spans    = [
            'net_deposit'               =>  ['day', 'week'],
            self::TYPE_CUSTOMER_NET_DEPOSIT => ['month'],
        ];
        $this->setProductLimits();
        // $this->time_span_values = ['na' => 0, 'day' => 1, 'week' => 7, 'month' => 30];
        $this->admin2_context = false;
        $this->remote_request_context = false;
    }

    /**
     * Get a licensed limit
     *
     * @param DBUser $u_obj
     * @param string $limit
     * @return false|mixed
     */
    public function getLicLimit($u_obj, $limit)
    {
        $rgl = licSetting('limits', $u_obj)[$limit];

        if (empty($rgl) || empty($rgl['limit'])) {
            return false;
        }

        return $rgl;
    }

    /**
     * Return the global limit object on jurisdictional level
     *
     * @param $u_obj DBUser
     * @return bool|mixed
     */
    public function getLicDepLimit($u_obj)
    {
        return $this->getLicLimit($u_obj, 'deposit');
    }

    /**
     * Detect if we can skip the deposit limit reminder
     * Skip when setting is configured and deposit limit not reached
     *
     * @param DBUser $user
     * @return bool
     */
    public function skipLicDepLimitReminder($user): bool
    {
        $rgl = $this->getLicDepLimit($user);
        // When we don't allow the "override logic" we ALWAYS show the popup, in the popup we decide if reminder/reached
        if (empty($rgl['allow_global_limit_override'])) {
            return true;
        }
        // otherwise it will be shown only when limit is reached/exceeded
        return !$this->reachedType($user, 'deposit', 0, true);
    }

    public function getLicWithdrawLimit($u_obj){
        $res = $this->getLicLimit($u_obj, 'withdraw');
        if ($res['limit'] === 'config') {
            $res['limit'] = phive('Config')->getValue('withdrawal-limits', 'daily-limit-se-sek', 0);
        }
        if (empty($res['limit'])) {
            return false;
        }
        return $res;
    }

    public function getLicWithdrawLimitProgress($u_obj, $rgl, $extra_amount = 0){
        list($start_stamp, $end_stamp) = phive()->todaySpan();
        $rng = $this->db->tRng($start_stamp, $end_stamp, 'timestamp');
        $sql = "SELECT SUM(amount) FROM pending_withdrawals WHERE user_id = {$u_obj->getId()} AND status NOT IN ('disapproved', 'initiated') $rng";
        $sum = $this->db->sh($u_obj)->getValue($sql);
        $res = [chg($u_obj->getCurrency(), $rgl['currency'], $sum, 1)];
        if(!empty($extra_amount)){
            $res[] = chg($u_obj->getCurrency(), $rgl['currency'], $extra_amount, 1);
        }
        return $res;
    }

    public function reachedLicWithdrawLimit($u_obj, $amount = 0){
        $rgl = $this->getLicWithdrawLimit($u_obj);
        if(empty($rgl)){
            return false;
        }
        list($progress, $amount) = $this->getLicWithdrawLimitProgress($u_obj, $rgl, $amount);
        return $progress + $amount > $rgl['limit'] ? $rgl['limit'] - $progress : false;
    }

    public function getLicDepLimitStartEndDate(){
        //return phive()->getWeekStartEnd();
        // TODO uncomment above and get rid of the below second week in July 2020.
        //list($start, $end) = phive()->getWeekStartEnd();
        $start = date("Y-m-d", strtotime('monday this week'));
        $end = date("Y-m-d", strtotime('sunday this week'));

        $first_week_start = '2020-07-02 00:00:00';
        $first_week_end   = '2020-07-05 23:59:59';
        if(phive()->hisNow() < $first_week_end){
            return [$first_week_start, $first_week_end];
        } else {
            return [$start.' 00:00:00', $end.' 23:59:59'];
        }
    }

    public function getLicDepLimitProgress($u_obj, $rgl, $extra_amount = 0){
        list($week_start, $week_end) = $this->getLicDepLimitStartEndDate();
        $sums                        = phive('Cashier')->getDeposits($week_start, '', $u_obj->getId(), '', 'total');
        return chg($u_obj->getCurrency(), $rgl['currency'], $sums['amount_sum'] + $extra_amount, 1);
    }

    /**
     * @param DBUser $u_obj
     * @param int $amount
     * @return bool
     */
    public function reachedLicDepLimit($u_obj, $amount = 0)
    {
        $special_limit = lic('hasSpecialLimit', [$u_obj, 'deposit', $amount], $u_obj);
        if (!empty($special_limit)) {
            return true;
        }

        $rgl = $this->getLicDepLimit($u_obj);
        // in case we "allow_global_limit_override" we check only users rg_limits and ignore global one.
        if (!empty($rgl) && empty($rgl['allow_global_limit_override'])) {
            // We add the pending deposits.
            $amount   = $amount + $this->getExtraAmount('deposit', $u_obj);
            $progress = $this->getLicDepLimitProgress($u_obj, $rgl, $amount);
            if ($progress > $rgl['limit']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Wrapper around getSingleLimit logic to return if the user is opted-in/out from undo_withdrawals
     *
     * @param DBUser $u_obj
     * @param bool $return_limit - if true will return an array with flag + limit.
     * @return bool|array
     */
    public function hasUndoWithdrawals($u_obj, $return_limit = false)
    {
        if(empty($u_obj)) {
            return false;
        }
        $limit = $this->getSingleLimit($u_obj, 'undo_withdrawal_optout');
        return $return_limit ? [empty($limit['cur_lim']), $limit] : empty($limit['cur_lim']);
    }

    /*
    // This is needed if we ever want to support variable cool offs
    function getTimeSpanVal($span){
        return $this->time_span_values[$span];
    }

    function isMoreLiberalTimeSpan($cur_span, $new_span){
        return $this->getTimeSpanVal($new_span) < $this->getTimeSpanVal($cur_span);
    }
    */

    function getMoneyLimits(){
        return $this->getLimitsByType('money');
    }

    function getLimitsByType($type){
        $res = [];
        foreach($this->all_types as $rgl => $rgl_type){
            if($type == $rgl_type){
                $res[] = $rgl;
            }
        }
        return $res;
    }

    /**
     * Used to determine that the request is being sent from admin2.
     *
     * @return $this
     */
    public function setAdmin2Context() {
        $this->admin2_context = true;
        return $this;
    }

    /**
     * Shows if a request comes from admin2.
     *
     * @return bool
     */
    public function isAdmin2Context(): bool
    {
        return $this->admin2_context;
    }

    /**
     * Adds to the context if We are handling a request initiated at a remote brand.
     *
     * @return $this
     */
    public function setRemoteRequestContext() {
        $this->remote_request_context = true;
        return $this;
    }

    /**
     * Shows if a request came from a remote brand.
     *
     * @return bool
     */
    public function isRemoteContext(): bool
    {
        return $this->remote_request_context;
    }

    function getUnit($type){
        $type = is_array($type) ? $type['type'] : $type;
        return $this->all_types[$type];
    }

    function displayUnit($type, $u_obj){
        $currency = $u_obj->getCurrency();
        $unit     = $this->getUnit($type);
        switch($unit){
            case 'money':
                return $currency;
            case 'bool':
                return '';
            default:
                return $unit;
        }
    }

    function cleanInput($type, $limit){
        if($this->getUnit($type) == 'money'){
            return phive('Cashier')->cleanUpNumber($limit) * 100;
        }
        return (int)$limit;
    }

    function prettyLimit($type, $limit, $no_decimal = false, bool $translate = true){
        // We're looking at some kind of content so we return it right away.
        if(!is_numeric($limit)){
            return $limit;
        }

        if(empty($limit)){
            return '';
        }

        if($limit < 0){
            $remove_alias = 'rg.limit.to.be.removed';
            return $translate ? t($remove_alias) : $remove_alias;
        }

        switch($this->getUnit($type)){
            case 'money':
                return empty($limit) ? '' : ($no_decimal ? round($limit / 100) : phive()->twoDec($limit));
            default:
                return $limit;
        }
    }

    /**
     * Return the progress value, if is a TIME type it will converted the value from seconds to limit unit
     * - A money type will return progress (it's already in cent)
     * - A time type will return a rounded amount divided by unit. Ex. "3600" (s), will return 1 (h)
     *
     * @param $rgl
     * @return float
     */
    private function convertProgressToCurLim($rgl)
    {
        $unit = $this->getUnit($rgl);
        if ($this->isTimeType($rgl)) {
            $multi_map = ['m' => 60, 'h' => 3600, 'd' => 86400];
            return round($rgl['progress'] / $multi_map[$unit], 2);
        } else {
            return $rgl['progress'];
        }
    }

    /**
     * Return the cur_lim value, if is a TIME type it will converted the value from limit unit to seconds
     * - A money type will return progress (it's already in cent)
     * - A time type will return a multiplied amount by unit. Ex. 1 (h) will return 3600 (s)
     *
     * @param $rgl
     * @return float
     */
    private function convertCurLimToProgress($rgl)
    {
        $unit = $this->getUnit($rgl);
        if ($this->isTimeType($rgl)) {
            $multi_map = ['m' => 60, 'h' => 3600, 'd' => 86400];
            return $rgl['cur_lim'] * $multi_map[$unit];
        } else {
            return $rgl['cur_lim'];
        }
    }

    /**
     * Return the remaining amount for the current limit:
     * - "limit - progress" or 0 (we cannot display < 0 values)
     * - OR "Not applicable" for limits without progress.
     *
     * @param $rgl
     * @return bool|mixed|string|string[]|null
     */
    function getRemaining($rgl){
        if(empty($rgl)){
            return false;
        }

        if($this->isNoProgressType($rgl)){
            return t('na');
        }

        $progress = $this->convertProgressToCurLim($rgl);

        return max($rgl['cur_lim'] - $progress, 0);
    }

    function getType($el){
        return is_array($el) ? $el['type'] : $el;
    }

    /**
     * Return if the limit is reached, and if reached log an action on the customer.
     * In case of "money" type we increment by $to_add amount the current progress (Ex. bet, deposit)
     *
     * @param $rgl
     * @param int $to_add
     * @param bool $return_on_exact_match - if TRUE will return even on exact match, not only when exceeding the limit
     * @param bool $log_action - In case where we don't want to log the action even if reached and over the progress
     * @return bool
     */
    function isReached($rgl, $to_add = 0, $return_on_exact_match = false, $log_action = true)
    {
        $cur_lim = $this->convertCurLimToProgress($rgl);
        $progress = $rgl['progress'];
        if ($this->getUnit($rgl) == 'money') {
            $progress += (int)$to_add;
        }

        $reached = $progress > $cur_lim;
        // as this is used only to check if we reached the limit on deposit box we don't need to fire logAction below.
        if($return_on_exact_match && $progress == $cur_lim) {
            return true;
        }

        if ($reached && $log_action) {
            if($rgl['type'] === 'net_deposit') {
                $log_msg = "User reached monthly limit: {$rgl['cur_lim']}, progress: $progress ";
            } else {
                $log_msg = "User reached {$rgl['time_span']} {$rgl['type']} limit:{$rgl['cur_lim']}";
            }
            phive('DBUserHandler')->logAction($rgl['user_id'], $log_msg, "reached-{$rgl['type']}-{$rgl['time_span']}");
        }

        return $reached;
    }

    /**
     * Wrapper to determine if the passed limit is of time_type
     *
     * @param $type
     * @return bool
     */
    private function isTimeType($type)
    {
        return in_array($this->getType($type), $this->time_types);
    }

    /**
     * Wrapper to determine if the passed limit is not subject to progress
     *
     * @param $type
     * @return bool
     */
    private function isNoProgressType($type)
    {
        return in_array($this->getType($type), $this->no_progress_types);
    }

    /**
     * Wrapper to determine if the reset time is fixed - end of the day, end of the week, etc
     *
     * @param $type
     * @return bool
     */
    private function isFixedResetTime($type)
    {
        return in_array($this->getType($type), $this->fixed_reset_time);
    }

    /**
     * Wrapper to determine if the passed limit is of bool type (checking on unit type)
     *
     * @param $type
     * @return bool
     */
    private function isBoolType($type)
    {
        return in_array($this->getUnit($type), ['bool']);
    }

    function isResettable($type){
        return in_array($this->getType($type), $this->resettable);
    }

    function isLock($type){
        return in_array($this->getType($type), $this->lock_types);
    }

    /**
     * We get timestamps, whether it is custom defined or just default as per time_spans var
     *
     * @param $type
     * @return string[]|void
     */
    public function getTimeSpans($type) {
        $type = $this->getType($type);
        if (array_key_exists($type, $this->custom_time_spans)) {
            return $this->custom_time_spans[$type];
        } else {
            return $this->time_spans;
        }
    }

    /**
     * We get either the reset timestamp by default which is +1 the time span or an overridden version.
     *
     * When the type has a fixed timestamp then is end of day, week or month.
     *
     * @param string $time_span
     * @param string $country
     * @return mixed
     */
    public function getResetStamp($time_span, $type, $country)
    {
        if ($this->isFixedResetTime($type)) {
            switch ($time_span) {
                case 'day':
                    return date("Y-m-d 23:59:59");
                case 'week':
                    return date("Y-m-d 23:59:59", strtotime('sunday this week'));
                case 'month':
                    return date("Y-m-d 23:59:59", strtotime('last day of this month'));
                default:
                    return false;
            }
        } else {
            return lic('getResetStamp', [$time_span, $type], null, null, $country)
                ?: phive()->hisMod("+1 $time_span");
        }
    }

    /**
     * Returns modified ResetStamp based on specific requirements and procedures
     *
     * If the user has a 'loss' limit set, the 'net_deposit' must have the same period dates as the user’s 'loss' limit dates.
     * If a customer does not have 'loss' limit set, the 'net_deposit' should reset at the end of the month. (As it is, calendar month).
     *
     * @param DBUser $user
     * @param        $type
     * @param        $time_span
     * @param bool $cron $cron used to prevent sync between 'resets_at' of 'net_deposit' with 'resets_at' of 'loss'
     *                   if the 'loss' limit exists but has not been reset yet due to schedule
     *
     * @return false|mixed
     */
    public function getModifiedResetStamp(DBUser $user, $type, $time_span, bool $cron = false)
    {
        if (
            $type === "net_deposit" &&
            $time_span === "month" &&
            lic('getLicSetting', ['sync_ndl_resets_with_loss_limit_resets'], $user)
        ) {
            $ndl = $this->getLimit($user, 'loss', 'month');
            if (! empty($ndl['resets_at'])) {
                if ($cron && Carbon::parse($ndl['resets_at']) < Carbon::now()) {
                    return phive()->hisMod("+1 $time_span");
                }
                return $ndl['resets_at'];
            }
        }

        return $this->getResetStamp($time_span, $type, $user->getCountry());
    }

    /**
     * Returns modified fixed-cycle ResetStamp based on specific requirements and procedures
     * Base on the source timestamp we calculate 'resets_at' timestamp for day|week|month following same time
     * for 'day' and 'week' time-spans and same day and time for 'month' time-span
     *
     * @param DBUser   $user
     * @param string   $time_span
     * @param DateTime $current_timestamp
     *
     * @return string
     * @throws Exception
     */
    public function getFixedCycleResetStamp(DBUser $user, string $time_span, DateTime $current_timestamp): string
    {
        if (!array_key_exists($time_span, static::RESET_SPANS_IN_HOURS)) {
            throw new RuntimeException(__METHOD__ . " Unknown time span $time_span");
        }
        $resets_at_source = $this->getOrSetFixedCycleResetsAtSourceTimestamp($user);
        $source_timestamp = new DateTime($resets_at_source);
        $hours = static::RESET_SPANS_IN_HOURS[$time_span];
        $interval = new DateInterval("PT{$hours}H");
        // just in case if the $source_timestamp is too far from now we roll it back before now
        // to avoid same timestamp for all time spans
        while ($source_timestamp > $current_timestamp) {
            $source_timestamp->sub($interval);
        }
        // now we can correctly increment to the nearest future value
        while ($source_timestamp < $current_timestamp) {
            $source_timestamp->add($interval);
        }

        return $source_timestamp->format('Y-m-d H:i:s');
    }

    /**
     * Base on PHNX-4898 requirements, source resets_at timestamp may have four different sources:
     * - user setting 'rg-month-limit-set-at' is high prio
     * - loss resets_at (if it has no 'rg-month-limit-set-at')
     * - deposit resets_at (if it has no 'loss' limit)
     * - first brand registration timestamp (if it has no 'deposit')
     *
     * @param DBUser $user
     *
     * @return null|string
     */
    public function getOrSetFixedCycleResetsAtSourceTimestamp(DBUser $user): string
    {
        $setting = 'rg-month-limit-set-at';
        $source_timestamp = $user->getSetting($setting);

        if (!empty($source_timestamp)) {
            return $source_timestamp;
        }

        $user_remote_id = $user->getRemoteId();
        $remote = getRemote();
        $source_timestamp = null;

        if ($user_remote_id) {
            $response = toRemote($remote, 'getLastDeposit', [$user_remote_id]);

            if (!empty($response['result']['deposit']['timestamp'])) {
                $local_deposit = phive('Cashier')->getDeposits('', '', $user->getId(), '', false, '', '', '', 1);
                $local_deposit_at = !empty($local_deposit[0]['timestamp']) ? Carbon::parse($local_deposit[0]['timestamp']) : null;
                $remote_deposit_at = Carbon::parse($response['result']['deposit']['timestamp']);

                if (!$local_deposit_at || $remote_deposit_at > $local_deposit_at) {
                    $limit = toRemote(
                        $remote,
                        'getRemoteUserLimit',
                        [$user_remote_id, self::TYPE_LOSS, 'month', $response['result']['brand']]
                    );

                    if (empty($limit['result']['resets_at'])) {
                        $limit = toRemote(
                            $remote,
                            'getRemoteUserLimit',
                            [$user_remote_id, self::TYPE_DEPOSIT, 'month', $response['result']['brand']]
                        );
                    }

                    if (!empty($limit['result']['resets_at'])) {
                        $source_timestamp = $limit['result']['resets_at'];
                    }
                }
            }
        }

        if (empty($source_timestamp)) {
            $limit = $this->getLimit($user, self::TYPE_LOSS, 'month');

            if (empty($limit['resets_at'])) {
                $limit = $this->getLimit($user, self::TYPE_DEPOSIT, 'month');
            }

            if (!empty($limit['resets_at'])) {
                $source_timestamp = $limit['resets_at'];
            }
        }

        if (empty($source_timestamp)) {
            $data = $user->getFirstBrandRegistrationTimestamp();
            $source_timestamp = $data['registered_at'];
        }

        if (!empty($source_timestamp)) {
            $user->setSetting($setting, $source_timestamp);
        }

        if ($user_remote_id) {
            toRemote(
                $remote,
                'syncSettingsWithRemoteBrand',
                [$user_remote_id, $setting, $source_timestamp, true] //4th param, force sync update
            );
        }

        return $source_timestamp;
    }

    function checkTimeSpan($time_span){
        return in_array($time_span, array_merge($this->time_spans, ['na']));
    }

    function checkType($type){
        return in_array($type, array_keys($this->all_types));
    }

    function checkSpanType($time_span, $type){
        if(!$this->checkTimeSpan($time_span)){
            return false;
        }

        if(!$this->checkType($type)){
            return false;
        }

        return true;
    }

    function sqlWhereTimePassed($col, $stamp){
        $zero = phive()->getZeroDate();
        return "$col < '$stamp' AND $col != '$zero'";
    }

    function sqlWhereUser($u_obj, $extra = ''){
        $str = "SELECT * FROM rg_limits WHERE user_id = {$u_obj->getId()} $extra";
        return $str;
    }

    function getLimit($u_obj, $type, $time_span){
        return $this->db->sh($u_obj)->loadAssoc($this->sqlWhereUser($u_obj, " AND type = '$type' AND time_span = '$time_span'"));
    }

    /**
     * Return the timestamp with the cool off period for the limit.
     * Behaviour can be overridden for specific jurisdictions and type (Ex. login on SE)
     * Accepted modifier are DEFAULT_COOLOFF, that will handle cooloff based on "settings/config table"
     * or a specific timespan "day|week|month" (Ex. used for betmax limit)
     *
     * @param string $modifier - DEFAULT_COOLOFF|day|week|month
     * @param array $rgl - current limit
     * @param string $iso - needed for not logged in context (Ex. Admin2 and CRON)
     * @return bool|mixed
     */
    function getChangeStamp($modifier = self::DEFAULT_COOLOFF, $rgl = [], $iso = null)
    {
        return lic('getRgChangeStamp', [$modifier, $rgl['type'], $iso], $rgl['user_id']);
    }

    function addAllByType($u_obj, $type, $limit){
        foreach($this->getTimeSpans($type) as $time_span){
            $this->addLimit($u_obj, $type, $time_span, $limit);
        }
    }

    /**
     * Adds a limit
     *
     * @param DBUser   $u_obj            The DBUser object.
     * @param string   $type             The type such as deposit, wager or loss.
     * @param string   $time_span        An enum string, can be day, week or month.
     * @param int      $limit            The limit amount.
     * @param string   $extra            Extra info/config for the limit.
     * @param boolean  $allow_duplicated Used to allow duplicated user_id+type+time_span
     * @param null|int $progress         If progress is passed then we want this value as new progress
     *
     * @return mixed If the insert was not successful we return false, the unique id of the inserted row otherwise.
     * @throws Exception
     */
    function addLimit($u_obj, $type, $time_span, $limit, $extra = null, $allow_duplicated = false, $progress = null){
        if(empty($u_obj)){
            return false;
        }

        $limit = (int)$limit;

        if(empty($u_obj)){
            return false;
        }

        if(!$this->checkSpanType($time_span, $type)){
            return false;
        }

        if (empty($allow_duplicated)) {
            $rgl = $this->getLimit($u_obj, $type, $time_span);
            if(!empty($rgl)){
                return false;
            }
        }

        if(empty($limit) && !$this->isBoolType($type)) {
            return false;
        }

        // To prevent overflow issues.
        if($limit >= PHP_INT_MAX) {
            $limit = PHP_INT_MAX;
        }

        $insert = [
            'user_id'    => $u_obj->getId(),
            'cur_lim'    => $limit,
            'time_span'  => $time_span,
            'progress'   => is_null($progress) ? 0 : $progress,
            'updated_at' => phive()->hisNow(),
            'created_at' => phive()->hisNow(),
            'type'       => $type
        ];

        if (!empty($extra)) {
            if (empty($insert['extra'] = $this->processExtra($extra))) {
                return false;
            }
        }

        if ($this->isResettable($type)) {

            if ($this->shouldApplyFixedCycleTimestamp($u_obj)) {
                $insert['resets_at'] = $this->getFixedCycleResetStamp($u_obj, $time_span, new DateTime());
            } else {
                $insert['resets_at'] = $this->getModifiedResetStamp($u_obj, $type, $time_span);

                if ($type === "loss" && $time_span === "month") {
                    $this->syncNdlResetsWithLossResetsStamp($u_obj, $insert['resets_at']);
                }
            }
        }


        if($this->isLock($type)){
            $insert['changes_at'] = phive()->hisMod("+$limit day");
        }

        if($type === self::TYPE_BALANCE) {
            $insert['progress'] = $u_obj->getBalance();
        }

        if($type == 'lock'){
            // We convert to hours for the change date stamp.
            $limit = 24 * ($limit > 3000000 ? 3000000 : $limit);
            // Some regulation (Ex. GB) impose an upper limit on the lock days, periods greater than that should be achieved using Self Exclusion by the user.
            $max_lock_days = lic('getLockAccountMaxDays', [], $u_obj);
            if(!empty($max_lock_days)) {
                $max_lock_days_in_hours = $max_lock_days * 24;
                $limit = $limit > $max_lock_days_in_hours ? $max_lock_days_in_hours : $limit;
            }

            $unlock_at = phive()->hisMod("+{$limit} hour");

            phive("UserHandler")->logAction($u_obj, "Locked Account", "profile-lock", true);
            $u_obj->setSetting('lock-hours', $limit);
            $u_obj->setSetting('lock-date', phive()->hisNow());
            $u_obj->setSetting('unlock-date', $unlock_at);

            $this->uh->addBlock($u_obj, 4, true, $unlock_at);

            // We overwrite with the fixed correct limit.
            $insert['cur_lim'] = $limit;
            $insert['changes_at'] = $unlock_at;
        }

        if ($type == 'exclude') {
            $this->uh->selfExclude($u_obj, $limit, !empty($extra['permanent']));
        }

        if ($type == 'exclude_indefinite') {
            $this->uh->selfExclude($u_obj, '', '', '', '', !empty($extra['indefinite']));
        }

        if($type === self::TYPE_BALANCE) {
            // If limit set is below current balance, this will not happen normally as this limit will be set before deposits are done.
            if($insert['cur_lim'] < $insert['progress']) {
                $insert['progress'] = $insert['cur_lim'];
                $u_obj->setBalanceLimitExceeded();
            }
        }

        // Filter RG Limit when is money and log in Action table
        $this->convertUserCurrencyAndLogAction($u_obj, $type, 'add', $limit, implode('',$extra));

        $limit = $type === 'lockgamescat' ? $extra : $limit;
        $this->addRgLimitToHistory($u_obj->getId(), $type, $limit, $time_span, '', $insert['changes_at']);

        $this->removeEnforcedPopup($u_obj, $type);

        //net_deposit has special rules for creation, no need to sync its creation
        if ($type !== static::TYPE_NET_DEPOSIT) {
            $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($u_obj, $type);

            if ($remote_user_id !== 0) {
                $remote_brand = getRemote();
                $user_currency = $u_obj->getCurrency();

                $response = toRemote(
                    $remote_brand,
                    'addRemoteLimit',
                    [$remote_user_id, $type, $time_span, $limit, $extra, $user_currency]
                );

                if (!$response['success']) {
                    phive('UserHandler')->logAction(
                        $u_obj->getId(),
                        phive('Distributed')->checkSyncResponse(
                            $response,
                            $u_obj->getId(),
                            'add Limit',
                            $type,
                            $time_span,
                            $limit,
                            $remote_brand
                        ),
                        $type
                    );
                }
            }
        }

        $this->addRgLimitsLog(
            $u_obj,
            $insert['type'],
            $insert['time_span'],
            0,
            $insert['cur_lim'],
            self::REQUEST_TYPE_ADD,
        );

        return $this->db->sh($insert)->insertArray('rg_limits', $insert);
    }

    /**
     * Remove all limits for a specified type after the cooloff period is applied.
     * !! If limit is forced from BO it cannot be removed by the user -> nothing happen
     *
     * @param DBUser $u_obj
     * @param $type
     * @return array|bool|mixed
     */
    public function removeLimit($u_obj, $type){
        if(empty($u_obj)){
            return false;
        }

        if(!$this->checkType($type)){
            return false;
        }

        $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($u_obj, $type);
        $remote_brand = getRemote();

        if ($remote_user_id !== 0) {
            $response = toRemote(
                $remote_brand,
                'removeRemoteLimit',
                [$remote_user_id, $type]
            );

            if (!$response['success']) {
                phive('UserHandler')->logAction($u_obj->getId(),
                                                phive('Distributed')->checkSyncResponse($response,
                                                                                        $u_obj->getId(),
                                                                                        'remove limit',
                                                                                        $type,
                                                                                        null,
                                                                                        null,
                                                                                        $remote_brand),
                                                $type);
            }
        }


        $limits = $this->getByTypeUser($u_obj, $type);
        $cur = current($limits);

        if(in_array($type, ['betmax', 'lock', 'exclude', 'exclude_indefinite'])){
            if(empty($cur)){
                return false;
            }

            if($cur['time_span'] == 'na'){
                // We remove immediately as there is no cool off.
                $this->db->delete('rg_limits', ['id' => $cur['id']], $cur['user_id']);

                $applied_at = phive()->hisNow();
                $post_value = 0;

                $return = true;
            } else {
                // We get the cool off.
                $chg_stamp = $this->getChangeStamp($cur['time_span'], $cur, $u_obj->getCountry());
                $this->db->sh($u_obj)->updateArray('rg_limits', ['changes_at' => $chg_stamp, 'new_lim' => -1], ['id' => $cur['id']]);

                $applied_at = $chg_stamp;
                $post_value = -1;

                $return = $chg_stamp;
            }

            $this->addRgLimitsLog(
                $u_obj,
                $type,
                $cur['time_span'],
                $cur['cur_lim'],
                $post_value,
                self::REQUEST_TYPE_REMOVE,
                $applied_at,
            );

            return $return;
        } else if ($type === self::TYPE_NET_DEPOSIT) {
            $this->db->delete('rg_limits', ['type' => $type, 'user_id' => $u_obj->getId()], $u_obj->getId());

            $this->addRgLimitsLog(
                $u_obj,
                $type,
                $cur['time_span'],
                $cur['cur_lim'],
                0,
                self::REQUEST_TYPE_REMOVE,
            );

            return true;
        }
        else {
            $chg_stamp = $this->getChangeStamp(self::DEFAULT_COOLOFF, $limits[0], $u_obj->getCountry());
            foreach($limits as $rgl){
                if ($res = $this->exitIfForced($rgl, null, false)) {
                    return $res;
                }
                //removing the limit date so the date will be added after admins approval
                $changeDate = licSetting('remove_deposit_limit') ? '' : $chg_stamp;
                $this->db->sh($u_obj)->updateArray('rg_limits',
                    ['changes_at' => $changeDate, 'new_lim' => -1],
                    ['id' => $rgl['id']]);

                $this->addRgLimitsLog(
                    $u_obj,
                    $type,
                    $rgl['time_span'],
                    $rgl['cur_lim'],
                    -1,
                    self::REQUEST_TYPE_REMOVE,
                    $changeDate
                );
            }
            return $chg_stamp;
        }

    }

    /**
     * Changes a limit
     *
     * Limits that are more conservative are applied immediately whereas more
     * liberal limits need a cool down of X days before they are applied.
     * X is defined by:
     * @see Licensed::getRgChangeStamp
     * @see Licensed::getCooloffPeriod
     *
     * @param DBUser $u_obj The user object to work with.
     * @param string $type The type of limit we want to change.
     * @param int $limit The limit.
     * @param string $time_span The time span of the limit we want to work with.
     * @param array $limits
     * @param string $action_type Value in [change, raise, lower]
     * @param null|boolean $increased
     * @param bool $autorevert - Applies only on "lowering" scenario (Ex. SE reduce deposit to 5K)
     *                          if set to true "new_lim" will be prefilled with "cur_lim" and will revert automatically after cooloff
     * @param null|int $progress - If added, progress column will be updated with this value
     *
     * @return bool True if the update went well, false otherwise.
     */
    function changeLimit($u_obj, $type, $limit, $time_span = 'na', $limits = [], &$action_type = 'change', &$increased = null, $autorevert = false, $progress = null){
        if(empty($u_obj)){
            return false;
        }

        $limit = (int)$limit;

        if(!$this->checkSpanType($time_span, $type)){
            return false;
        }

        $limits = empty($limits) ? $this->getByTypeUser($u_obj, $type) : $limits;

        if(empty($limits)){
            return false;
        }

        $cur = $limits[$time_span];

        // Implies that we're trying to add a limit which this is the wrong place for.
        if(empty($cur)){
            return false;
        }

        if ((int)$cur['new_lim'] !== (int)$limit) {
            $increased = ((int)$limit > (int)$cur['cur_lim']);
        }

        if($cur['cur_lim'] == $limit && $cur['new_lim'] != -1 && $cur['cur_lim'] == $cur['new_lim']){
            // Failsafe scenario to remove the forced popup when the user set a limit equal to the current one, but still setting one.
            // Otherwise the popup would be forced at all time (this is an edge case, people that already have limits shouldn't be asked for limit popups)
            $this->removeEnforcedPopup($u_obj, $type);

            // Nothing to do
            return true;
        }

        if ($res = $this->exitIfForced($cur, $limit)) {
            return $res;
        }

        $ret     = true;
        $upd_arr = ['new_lim' => $limit, 'time_span' => $time_span];
        $extra   = '';
        $log_applied_change = false;

        // TODO check if we can move this into standard limit logic with some check on "bool" type.
        //  but being in a rush I don't want to break existing stuff ATM. /Paolo
        if($type == 'undo_withdrawal_optout'){
            if(!empty($limit)) {
                // we are "opting-out", so it will take place immediately
                $upd_arr = ['cur_lim' => $limit, 'time_span' => $time_span];
                $action_type = 'lower';
            } else {
                // we are "opting-in", so the standard cool-off period is in place.
                // being a flag (0/1) it's not subject to a time_span, so we apply default value
                $upd_arr['changes_at'] = $this->getChangeStamp(self::DEFAULT_COOLOFF, $cur, $u_obj->getCountry());
            }
        } elseif($type == 'betmax'){
            if($cur['time_span'] == 'na' || $cur['cur_lim'] > $limit){
                // We update immediately as there is no cool off or we don't want to apply the cool off.
                $upd_arr = ['cur_lim' => $limit, 'time_span' => $time_span];
                $action_type = 'lower';
            } else {
                // We get the cool off.
                $upd_arr['changes_at'] = $this->getChangeStamp($cur['time_span'], $cur, $u_obj->getCountry());
            }
        } else if($type === self::TYPE_BALANCE) {
            // Limit decreased, current balance lower than new limit, then set user as play and deposit blocked.
            // Limit increased, set cool off period to apply the limit.
            if($cur['cur_lim'] > $limit) {
                if($limit < $cur['progress']) {
                    $u_obj->setBalanceLimitExceeded();
                }

                $upd_arr = ['cur_lim' => $limit, 'time_span' => $time_span];
                $action_type = 'lower';
            } else {
                $upd_arr['changes_at'] = $this->getChangeStamp(self::DEFAULT_COOLOFF, $cur, $u_obj->getCountry());
            }
        } else if($type === self::TYPE_NET_DEPOSIT) {
            // in the case of a net_deposit update, we don't set any cool-off period
            $upd_arr = ['cur_lim' => $limit, 'time_span' => $time_span];
        }
        else if($cur['cur_lim'] < $limit){
            // We're looking at a more liberal limit so we need to apply a cool off.
            $chg_stamp = $this->getChangeStamp(self::DEFAULT_COOLOFF, $cur, $u_obj->getCountry());
            // We need to update the changes_at date for all limits that already have a cool off.
            foreach($limits as $rgl){
                // We don't do anything with limits that do not already have a cool off.
                if(phive()->isEmpty($rgl['changes_at'])){
                    continue;
                }
                $this->db->sh($u_obj)->updateArray('rg_limits', ['changes_at' => $chg_stamp], ['id' => $rgl['id']]);
            }
            $upd_arr['changes_at'] = $chg_stamp;
            $ret                   = $chg_stamp;
        } else {
            $new_limit = 0;
            $action_type = 'lower';
            //decreasing the limit is done immediately so we should log it
            $log_applied_change = true;

            // exception for when the user wants to set the new_lim to cur_lim
            if ($cur['cur_lim'] == $limit) {
                $new_limit = $limit;
                //no decrease to be logged when the limit remains the same
                $log_applied_change = false;
            }

            // We're looking at a less liberal limit so we apply right away and get rid of potential cool offs.
            $upd_arr = ['cur_lim' => $limit, 'time_span' => $time_span, 'changes_at' => phive()->getZeroDate(), 'new_lim' => $new_limit];
            if(!empty($autorevert)) {
                $upd_arr['new_lim'] = $cur['cur_lim'];
                $upd_arr['changes_at'] = $this->getChangeStamp(self::DEFAULT_COOLOFF, $cur, $u_obj->getCountry());
                $extra = 'auto revert after cooloff';
            }
        }

        // Current progress is larger than the new limit so we will reach the limit right away.
        // an array with "type" and "cur_lim" need to passed here to properly calculate the limit for Time types too.
        $tmp_limit_array = ['type' => $cur['type'], 'cur_lim' => $limit];
        if ($cur['progress'] > $this->convertCurLimToProgress($tmp_limit_array)) {
            // We set the progress to be the same as the new limit even though it is actually larger
            // to prevent CS confusion.
            $upd_arr['progress'] = $limit;
        }

        // If progress is passed then we want this value as new progress
        if (!is_null($progress)) {
            $upd_arr['progress'] = (int)$progress;
        }

        $this->db->sh($u_obj)->updateArray('rg_limits', $upd_arr, ['id' => $cur['id']]);

        $this->addRgLimitsLog(
            $u_obj,
            $type,
            $time_span,
            $cur['cur_lim'],
            $limit,
            self::REQUEST_TYPE_CHANGE,
            $upd_arr['changes_at'] === "0000-00-00 00:00:00" ? '' : $upd_arr['changes_at'],
        );

        // Filter RG Limit when is money and log in Action table
        $this->convertUserCurrencyAndLogAction($u_obj, $type, 'change', $limit, $extra);

        //decreasing the deposit limits is immediate, so we should log the applied change and report it
        if ($log_applied_change && $type == static::TYPE_DEPOSIT){
            $this->logAppliedLimit($u_obj);
        }

        // If there is no changes at, refers to limit being applied.
        if(empty($rgl['changes_at'])) {
            $this->addRgLimitToHistory($u_obj->getId(), $type, $upd_arr['new_lim'], $time_span);
        }

        $this->removeEnforcedPopup($u_obj, $type);

        $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($u_obj, $type);
        $remote_brand = getRemote();
        $user_currency = $u_obj->getCurrency();

        if ($remote_user_id !== 0) {
            $response = toRemote(
                $remote_brand,
                'changeRemoteLimit',
                [$remote_user_id, $type, $time_span, $limit, $user_currency, $upd_arr['progress']]
            );

            if (!$response['success']) {
                phive('UserHandler')->logAction($u_obj->getId(),
                    phive('Distributed')->checkSyncResponse($response,
                        $u_obj->getId(),
                        'change Limit',
                        $type,
                        $time_span,
                        $limit,
                        $remote_brand),
                    $type);
            }
        }


        return $ret;
    }

    function saveVariableCoolOffLimit($u_obj, $type, $time_span, $limit){

        // Currently only bet max.
        $limits  = $this->getByTypeUser($u_obj, $type);

        if(empty($limits)){
            return $this->addLimit($u_obj, $type, $time_span, $limit);
        }

        $cur_limit = $limits[$time_span];

        if(empty($cur_limit)){
            // We're looking at a change of time span / cool off.
            // NOTE that this cool off currently does not apply to itself, ie if I pick first one week
            // and then one day the one day update will happen right away, NOT after one week.
            $cur_limit              = current($limits);
            $cur_limit['time_span'] = $time_span;
            $limits                 = [$time_span => $cur_limit];
        }

        return $this->changeLimit($u_obj, $type, $limit, $time_span, $limits);
    }

    function saveLimit($u_obj, $type, $time_span, $limit){
        $limits  = $this->getByTypeUser($u_obj, $type);
        if(empty($limits)){
            return $this->addLimit($u_obj, $type, $time_span, $limit);
        }
        return $this->changeLimit($u_obj, $type, $limit, $time_span, $limits);
    }

    function resettableSql($extra_where, $where_uid = ''){
        return "SELECT rg_limits.*, u.country FROM rg_limits
                    LEFT JOIN users u ON rg_limits.user_id = u.id
                        WHERE rg_limits.type IN({$this->db->makeIn($this->resettable)}) $where_uid $extra_where";
    }

    /**
     * Gets all resettable limits for a user using optional filtering, a resettable limit is for instance wager.
     *
     * @param DBUser $u_obj The user object.
     * @param string $extra_where The optional where clause to use for filtering.
     *
     * @return array The array of limits.
     */
    function getResettAble($u_obj, $extra_where = ''){
        if(empty($u_obj)){
            return [];
        }
        $where_uid = "AND user_id = ".$u_obj->getId();
        return $this->db->sh($u_obj)->loadArray($this->resettableSql($where_uid, $extra_where));
    }

    function getAllResettable($extra_where){
        return $this->db->shs()->loadArray($this->resettableSql('', $extra_where));
    }

    /**
     * Fetches all the limits that are applicable when making a bet.
     *
     * @param DBUser $u_obj The user object.
     * @param bool $get_cached Whether or not we want to cache the result or not, handy for GPs that are doing the bet and win in the same call to avoid two trips to the DB per call.
     * @param string $product this will be the identifier for the limits we want
     * @return array The array of RG limits.
     */
    public function getBetLimits($u_obj, $get_cached = true, $product = '')
    {
        if(empty($u_obj)){
            return [];
        }

        if(isset($this->bet_lims) && $get_cached){
            return $this->bet_lims;
        }

        $list_of_bet_limits = [
            $this->getProductLimitType('wager', $product),
            $this->getProductLimitType('loss', $product),
            'betmax',
            'rc',
            'timeout',
            'login'
        ];

        $where_type_sql = "  AND `type` IN(" . $this->db->makeIn($list_of_bet_limits) . ")";

        $this->bet_lims = $this->db->sh($u_obj)->loadArray($this->sqlWhereUser($u_obj, $where_type_sql));

        return $this->bet_lims;
    }

    /**
     * Checks if the limit in question is a limit that can be increased or decreased.
     *
     * @param array &$rgl The limit to check.
     *
     * @return bool True if it is a decreasable and increasable limit.
     */
    function isIncDecLimit(&$rgl){
        return in_array($rgl['type'], $this->incrementable_limits);
    }

    /**
     * Gets all limits for all timespans of a certain type for a certain user.
     *
     * @param DBUser $u_obj The user object.
     * @param string $type The type, ex: deposit, leave it empty to get all limits for the user in question.
     *
     * @return array An array of limits.
     */
    function getByTypeUser($u_obj = null, $type = ''){
        if(empty($u_obj)){
            return [];
        }

        if(empty($type)){
            $use_key    = false;
            $where_type = '';
        } else {
            $where_type = is_array($type) ? "AND `type` IN({$this->db->makeIn($type)})" : "AND `type` = '$type'";
            $use_key    = is_array($type) ? false : 'time_span';
        }

        return $this->db->readOnly()->sh($u_obj)->loadArray($this->sqlWhereUser($u_obj, $where_type), 'ASSOC', $use_key);
    }

    function hasLimits($u_obj = null, $type = ''){
        if(empty($u_obj)){
            return false;
        }
        $limits = $this->getByTypeUser($u_obj, $type);
        return !empty($limits);
    }

    /**
     * We check if all time spans are set for a given limit
     *
     * @param null $u_obj
     * @param string $type
     * @return bool
     */
    public function hasLimitsOnAllTimeSpans($u_obj = null, $type = '')
    {
        if(empty($u_obj)){
            return false;
        }
        $type = $this->getType($type);
        return count($this->getByTypeUser($u_obj, $type)) === count(rgLimits()->getTimeSpans($type));
    }

    function applyToType($u_obj, $type, $func){
        $limits = $this->getByTypeUser($u_obj, $type);
        foreach($limits as $rgl){
            $func($rgl);
        }
    }

    /**
     * Progresses all limits of a certain type for a certain user.
     *
     * @param DBUser $u_obj The user.
     * @param string $type The type.
     * @param int $amount The amount to progress / increase with.
     *
     * @return void
     */
    function incType($u_obj, $type, $amount){
        $amount = (int)$amount;
        $this->applyToType($u_obj, $type, function($rgl) use ($amount){
            $this->incLimit($rgl, $amount);
        });
    }

    function decType($u_obj, $type, $amount){
        $amount = (int)$amount;
        $this->applyToType($u_obj, $type, function($rgl) use ($amount){
            $this->decLimit($rgl, $amount);
        });
    }

    function getExtraAmount($el, $u_obj = null){
        if($this->getType($el) == 'deposit'){
            // We have to respect pending deposit amounts if we're looking at a deposit.
            return $this->getPendingDeposit($u_obj);
        }
        return 0;
    }

    /**
     * Used to check if a certain type of limit has been reached, all time spans, for a particular user.
     * Typically used to check if player can deposit or not.
     *
     * @param DBUser $u_obj The user object.
     * @param string $type The type of limit.
     * @param int $amount The amount that the player wants to for instance deposit.
     * @param bool $return_on_exact_match - if TRUE will return even on exact match, not only when exceeding the limit
     * @param bool $log_action - In case where we don't want to log the action even if reached and over the progress
     *
     * @return bool True if the limit has been reached, false if not.
     */
    function reachedType($u_obj, $type, $amount = 0, $return_on_exact_match = false, $log_action = true){
        $amount       = (int)$amount;
        $limits       = $this->getByTypeUser($u_obj, $type);
        // We have to respect pending deposit amounts if we're looking at a deposit.
        $extra_amount = $this->getExtraAmount($type, $u_obj);
        foreach($limits as $rgl){
            if($this->isReached($rgl, $amount + $extra_amount, $return_on_exact_match, $log_action)){
                return true;
            }
        }
        return false;
    }


    /**
     * Increases the progress of a limit, if it goes above or reaches the current limit we set the reached column to 1.
     *
     * Net deposit has to increase above the progress, rest will not to avoid customer confusion.
     *
     * @param array &$rgl The limit.
     * @param int $amount The amount to progress with.
     *
     * @return mixed
     */
    function incLimit(&$rgl, $amount)
    {

        if (!$this->isIncDecLimit($rgl)) {
            return false;
        }


        $rgl['progress'] += $amount;
        if ($rgl['progress'] >= $rgl['cur_lim'] && $rgl['type'] != 'net_deposit') {
            $rgl['progress'] = $rgl['cur_lim'];
        }

        $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($rgl['user_id'], $rgl['type']);
        $remote_brand = getRemote();
        $user_currency = cu($rgl['user_id'])->getCurrency();

        if ($remote_user_id !== 0) {
            $response = toRemote(
                $remote_brand,
                'incRemoteLimit',
                [$remote_user_id, $rgl['type'], $rgl['time_span'], $amount, $user_currency]
            );

            if (!$response['success']) {
                phive('UserHandler')->logAction($rgl['user_id'],
                                                phive('Distributed')->checkSyncResponse($response,
                                                                                        $rgl['user_id'],
                                                                                        'increase Limit',
                                                                                        $rgl['type'],
                                                                                        $rgl['time_span'],
                                                                                        $rgl['cur_lim'],
                                                                                        $remote_brand),
                                                $rgl['type']);
            }
        }



        // TODO this might result in incorrect updates / increases in extreme situations, for a totally safe increase
        // SQL::incrValue() should be used instead. But then we would instead end up with progress above the limit in some
        // cases.
        return $this->db->sh($rgl)->save('rg_limits', $rgl);
    }

    /**
     * Should happen on every bet.
     *
     * This method:
     * 1.) We get the relevant rg limits that we need to check based on the product passed.
     * 2.) First checks if we have reached any applicable limits.
     * 3.) And if we have not it will progress all applicable limits.
     *
     * @param DBUser $u_obj The user object.
     * @param int $amount The bet amount, if it is empty we assume that this method is only called in a context where we want to check the limit without performing any progression.
     * @param bool $get_cached Whether or not to get cached data.
     * @param string $product Name of the product calling the function, default is empty meaning it's coming directly Videoslots
     *
     * @return bool False if if we have reached a limit, true otherwise.
     */
    function onBetCheck($u_obj, $amount = 0, $get_cached = true, $product = '')
    {
        $this->bet_lims = $this->getBetLimits($u_obj, $get_cached, $product);

        if(empty($this->bet_lims)){
            // No limits were found so we just return true right away.
            return true;
        }

        $product_rg_limits = array_values(phive()->getSetting('products')[$product]['rg']) ?? array_keys($this->all_types);

        // First pass, we check if any limits are reached.
        foreach($this->bet_lims as &$rgl) {
            if(!in_array($rgl['type'], $product_rg_limits)) {
                continue;
            }
            if ($rgl['type'] == 'betmax' && !empty($amount) && $rgl['cur_lim'] < $amount) {
                return $rgl;
            } else if (in_array($rgl['type'], $this->time_types) && !empty($amount) && $this->isReached($rgl)) {
                return $rgl;
            } else {
                $wager_limit_type = $this->getProductLimitType('wager', $product);
                $loss_limit_type = $this->getProductLimitType('loss', $product);

                if (in_array($rgl['type'], [$wager_limit_type, $loss_limit_type])) {
                    if (empty($amount)) {
                        if ($rgl['progress'] >= $rgl['cur_lim']) {
                            return $rgl;
                        }
                    } else if ($this->isReached($rgl, $amount)) {
                        return $rgl;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Happens asyncronously every time a bet is placed, will increase the wager and the loss eg limits depending on
     * whether it's sportsbook or casino.
     *
     * @param $u_obj
     * @param $amount
     * @param bool $get_cached
     * @param string $product
     * @return bool
     */
    public function onBetInc($u_obj, $amount, $get_cached = true, $product = '')
    {
        $this->setProductLimits($u_obj->getId());
        $this->bet_lims = $this->getBetLimits($u_obj, $get_cached, $product);

        // Second pass, we increase limits.
        foreach($this->bet_lims as &$rgl){
            switch($rgl['type']){
                case 'betmax':
                    // Do nothing.
                    break;
                case 'login':
                    $this->progressResettableTimeLimit($u_obj, '', $rgl);
                    break;
                case 'timeout':
                case 'rc':
                    // Some idiotic suppliers (eg Playtech) require us to keep track of how RG progression, this is the only way we can do that
                    // we progress on each bet, far from accurate but the best we can do.
                    $this->progressRc($u_obj, $rgl);
                    break;
                default:
                    $this->incLimit($rgl, $amount);
                    break;
            }
        }

        return true;
    }

    /**
     * Check balance limit on balance changed each time.
     * If balance exceeds limit; play and deposit block is applied to user. [In case of wining]
     * If balance is below limit; play and deposit block is lifted. [In case of withdrawal]
     *
     * @param DBUser $u_obj
     * @param $balance
     */
    public function onBalanceChanged(DBUser $u_obj, $balance)
    {
        $rgl = $this->getLimit($u_obj, self::TYPE_BALANCE, 'na');
        if(empty($rgl)) {
            return;
        }

        $rgl['progress'] = 0; // Hack to set progress to 0 as current balance will be set as progress.

        // Check if balance is now lower than limit, happens in case of withdrawals
        $this->setOrResetBalanceLimitExceeded($u_obj, $rgl, $balance);

        $this->incLimit($rgl, $balance);
    }

    /**
     * Sets or resets balance limit flag for user, based on if balance exceeds limit set
     *
     * @param $u_obj
     * @param $rgl
     * @param null $balance
     */
    private function setOrResetBalanceLimitExceeded($u_obj, $rgl, $balance = null): void
    {
        $balance = $balance ?? $u_obj->getBalance();
        if(($balance < $rgl['cur_lim']) && $u_obj->hasExceededBalanceLimit()) {
            $u_obj->removeBalanceLimitExceeded();
            return;
        }

        if($balance > $rgl['cur_lim']) {
            $u_obj->setBalanceLimitExceeded();
        }
    }

    /**
     * Used to decrease a limit, it happens for instance for the loss limit when a win is registered.
     *
     * @param array &$rgl The limit.
     * @param int $amount The amount to decrease with.
     *
     * @return bool true if the limit was successfully decreased, false otherwise.
     */
    function decLimit(&$rgl, $amount)
    {
        if (!$this->isIncDecLimit($rgl)) {
            return false;
        }

        $rgl['progress'] -= $amount;

        $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($rgl['user_id'], $rgl['type']);
        $remote_brand = getRemote();
        $user_currency = cu($rgl['user_id'])->getCurrency();

        if ($remote_user_id !== 0) {
            $response = toRemote(
                $remote_brand,
                'decRemoteLimit',
                [$remote_user_id, $rgl['type'], $rgl['time_span'], $amount, $user_currency]
            );

            if (!$response['success']) {
                phive('UserHandler')->logAction($rgl['user_id'],
                                                phive('Distributed')->checkSyncResponse($response,
                                                                                        $rgl['user_id'],
                                                                                        'decrease Limit',
                                                                                        $rgl['type'],
                                                                                        $rgl['time_span'],
                                                                                        $rgl['cur_lim'],
                                                                                        $remote_brand),
                                                $rgl['type']);
            }
        }


        return $this->db->sh($rgl)->save('rg_limits', $rgl);
    }

    /**
     * Needs to be called on every win (or paid out reward that should affect this limit) in order to decrease loss limits.
     *
     * @param DBUser $u_obj The user object.
     * @param int $amount The amount that was won.
     * @param string $product The product wanting the rg limit ex. sportsbook
     * @return bool Always returns true.
     */
    function onWin($u_obj, $amount, $product = ''){
        $u_obj = cu($u_obj);
        if(empty($amount)){
            return true;
        }

        $this->setProductLimits($u_obj->getId());
        $loss_type = $this->getProductLimitType('loss', $product);
        $this->bet_lims = !isset($this->bet_lims) ? $this->getByTypeUser($u_obj, $loss_type) : $this->bet_lims;

        if(empty($this->bet_lims)){
            // No limits were found so we just return true right away.
            return true;
        }

        foreach($this->bet_lims as &$rgl){
            if($rgl['type'] != $loss_type){
                continue;
            }

            $this->decLimit($rgl, $amount);
        }

        return true;
    }

    /**
     * Used on any cash transaction that affects loss or wager limit
     *
     * @param DBUser $user
     * @param int $type
     * @param int|string $amount
     */
    public function onCashTransaction($user, $type, $amount)
    {
        // TODO compare with admin2 query to see if anything is missing.
        try {
            $user = cu($user);
            $abs_amount = abs($amount);
            if (in_array($type, [31, 32, 38, 61, 63, 64, 66, 69, 77, 80, 82, 84, 85, 86, 90])) {
                $this->decType($user, 'loss', $abs_amount);
            } elseif (in_array($type, [34, 52, 54, 67, 72])) {
                $this->incType($user, 'loss', $abs_amount);
            }
            if (in_array($type, [61, 62, 63])) {
                $this->decType($user, 'wager', $abs_amount);
            } elseif (in_array($type, [34, 35, 52])) {
                $this->incType($user, 'wager', $abs_amount);
            }
        } catch (Exception $e) {
            error_log("On cash transaction limit update failed: {$e->getMessage()}");
        }
    }

    /**
     * Should be run as a cron job every minute to reset progress on all applicable resettable limits.
     *
     * @param string $now Optional timestamp that can be used for testing.
     *
     * @return void
     */
    function resetCron($now = '')
    {
        $now = empty($now) ? phive()->hisNow() : $now;
        $this->setProductLimits('', true);
        $limits = $this->getAllResettable("AND ".$this->sqlWhereTimePassed('rg_limits.resets_at', $now));
        $rgl_to_string = function ($rgl) {
            return str_replace(' ', '', "progress:{$rgl['progress']},started_at:{$rgl['started_at']},resets_at:{$rgl['resets_at']}");
        };

        // IDs of users for which `deposit` limits are changed
        $users_ids_to_log = [];

        foreach ($limits as $rgl) {
            $before_reset = $rgl;
            $old = $rgl_to_string($rgl);

            $rgl['progress'] = 0;
            $user = cu($rgl['user_id']);
            if (!$user) {
                phive('Logger')->getLogger('cron')->error("Trying to reset not existing user {$rgl['user_id']}");
                continue;
            }

            if ($this->shouldApplyFixedCycleTimestamp($user)) {
                try {
                    $rgl['resets_at'] = $this->getFixedCycleResetStamp($user, $rgl['time_span'], new DateTime());
                } catch (Throwable $e) {
                    phive('Logger')
                        ->getLogger('cron')
                        ->error("Not enough data to calculate the 'resets_at' timestamp for user {$rgl['user_id']} " .
                            "based on first registration timestamp. Switched to default strategy 'getModifiedResetStamp'. Error: {$e->getMessage()}");
                    $rgl['resets_at'] = $this->getModifiedResetStamp($user, $rgl['type'], $rgl['time_span'], true);
                }
            } else {
                $rgl['resets_at'] = $this->getModifiedResetStamp($user, $rgl['type'], $rgl['time_span'], true);
            }

            unset($rgl['country']);
            $rgl['started_at'] = phive()->getZeroDate();
            $saved = (int)$this->db->sh($rgl)->save('rg_limits', $rgl);
            $new = $rgl_to_string($rgl);
            phive('DBUserHandler')->logAction($rgl['user_id'], "old-[$old];new-[$new];saved:$saved;", "reset-{$rgl['type']}-{$rgl['time_span']}");

            if ($rgl['type'] === self::TYPE_DEPOSIT) {
                $users_ids_to_log[$rgl['user_id']] = $rgl['user_id'];
            }

            if ($rgl['type'] === self::TYPE_NET_DEPOSIT && $before_reset['progress'] >= $before_reset['cur_lim']) {
                lic('notifyNetDepositLimitReset', [$rgl], $user);
            }
        }

        // we need to create action only for changed deposit limits
        foreach ($users_ids_to_log as $user_id) {
            $this->logCurrentLimit($user_id);
        }
    }

    /**
     * @param $datetime
     * @return void
     */
    function resetForcedLockCron($datetime = "")
    {
        $datetime = empty($datetime) ? phive()->hisNow() : $datetime;
        phive('SQL')->shs()->updateArray('rg_limits', ['forced_until' => null], "forced_until < '$datetime'");
    }

    /**
     * When cool off ends, notify the new limit applied
     * Returns true if email was sent
     *
     * @param $rgl
     * @return bool|void
     */
    public function notifyCoolOffEnded($rgl)
    {
        $user = $this->users_cache[$rgl['user_id']];
        if (empty($user)) {
            $user = $this->users_cache[$rgl['user_id']] = cu($rgl['user_id']);
        }

        if (!lic('shouldNotifyOnRgLimitCoolOffEnd', [], $user)) {
            return false;
        }

        return phive("MailHandler2")->sendMail('new-rg-limit-activated', $user);
    }

    /**
     * Notify net deposit limit reset, notify the new limit applied
     * Returns true if email was sent
     *
     * @param $rgl
     * @return bool|void
     */
    public function notifyNetDepositLimitReset($rgl)
    {
        $user = $this->users_cache[$rgl['user_id']];
        if (empty($user)) {
            $user = $this->users_cache[$rgl['user_id']] = cu($rgl['user_id']);
        }

        $enabled_countries = array_filter(
            explode(
                " ",
                phive('Config')->getValue('monthly-net-deposit-limit-reset-email', 'monthly-net-deposit-limit-reset-email-countries', '')
            )
        );

        if (!in_array($user->getCountry(), $enabled_countries)) {
            return false;
        }

        return phive("MailHandler2")->sendMail('monthly-net-deposit-limit-reset', $user);
    }

    /**
     * Should be run as a cron job every minute in order to update limits whose cool off period has expired.
     *
     * The below works as follows:
     * 1. We loop all limits with a lower changes_at stamp than now.
     * 2. We run a rgOnChangeCron to possibly get overridden values in the limit.
     * 3. We're looking at a lock "limit" which is currently not connected to any other logic as the settings are used for that instead, so we just delete it.
     * or the has ordered not a more liberal limit but a complete removal so we just remove delete it.
     * 4. We need to override the delete (in case we're looking at a limit removal) if we are to be able to restore the limit, we do that
     * by setting the changes_at date to the zero date to make the removal end up in a static limbo state that will require player action.
     *
     * @param string $now Optional timestap that can be used during testing.
     *
     * @return void
     */
    function changeCron($now = '')
    {
        $now = empty($now) ? phive()->hisNow() : $now;
        $limits = $this->db->shs()->loadArray("SELECT * FROM rg_limits WHERE ".$this->sqlWhereTimePassed('changes_at', $now));
        $users_to_log = [];
        // 1
        foreach ($limits as $rgl) {
            // 2
            $cur_rgl = phive('Licensed')->rgOnChangeCron($rgl);

            //rgOnChangeCron returns false if user doesn't exists
            if (!$cur_rgl) {
                phive('Logger')->getLogger('cron')->error("Trying to reset not existing user {$rgl['user_id']}");
                continue;
            }

            $user_id = $cur_rgl['user_id'];

            if ($cur_rgl['type'] === self::TYPE_DEPOSIT && !isset($users_to_log[$user_id])) {
                $users_to_log[$user_id] = $user_id;
            }

            // 3
            // Player has ordered not a more liberal limit but a complete removal, OR we're looking at a lock "limit"
            // which is currently not connected to any other logic as the settings are used for that instead.
            if ($cur_rgl['new_lim'] == -1 || $this->isLock($cur_rgl)) {
                $this->db->delete('rg_limits', ['id' => $cur_rgl['id']], $cur_rgl['user_id']);
                $this->notifyCoolOffEnded($cur_rgl);
                continue;
            }

            if ($cur_rgl['new_lim'] == -1) {
                if (empty($cur_rgl['old_lim'])) {
                    // 5
                    $this->db->delete('rg_limits', ['id' => $cur_rgl['id']], $cur_rgl['user_id']);
                    $this->notifyCoolOffEnded($cur_rgl);
                    continue;
                }
            } else {
                // Bool types can be 0 on the DB.
                if (!empty($cur_rgl['new_lim']) || $this->isBoolType($cur_rgl)) {
                    $cur_rgl['cur_lim'] = $cur_rgl['new_lim'];
                    $cur_rgl['new_lim'] = '';

                    $this->addRgLimitToHistory((int) $user_id, $cur_rgl['type'], $cur_rgl['cur_lim'], $cur_rgl['time_span'], $cur_rgl['updated_at']);
                }
            }

            // 4
            $cur_rgl['changes_at'] = phive()->getZeroDate();
            $this->db->sh($cur_rgl)->save('rg_limits', $cur_rgl);
            $this->notifyCoolOffEnded($cur_rgl);

            if($cur_rgl['type'] === self::TYPE_BALANCE) {
                $this->setOrResetBalanceLimitExceeded(cu($user_id), $cur_rgl);
            }
        }

        // we need to create action only for changed deposit limits
        foreach ($users_to_log as $user_id) {
            $this->logCurrentLimit($user_id);
            $this->logAppliedLimit($user_id);
        }
    }

    function getOldLimits($u_obj){
        return array_filter($this->getByTypeUser($u_obj), function($rgl){
            return !empty($rgl['old_lim']);
        });
    }

    function rejectOldLimits($u_obj){
        $users_ids_to_log = [];

        foreach ($this->getOldLimits($u_obj) as $rgl) {
            if($rgl['new_lim'] == -1){
                // If we're looking at a limit to be removed we just delete it.
                $this->db->delete('rg_limits', ['id' => $rgl['id']], $rgl['user_id']);
            } else {
                // Else the cron has already updated with the new limit so we just set the old_lim to 0 to
                // prevent any old limit logic from acting on it from here on.
                $rgl['old_lim'] = 0;
                $this->db->sh($u_obj)->save('rg_limits', $rgl);
            }

            if ($rgl['type'] === self::TYPE_DEPOSIT) {
                $users_ids_to_log[$rgl['user_id']] = $rgl['user_id'];
            }
        }

        $u_obj->deleteSetting('has_old_limits');

        // we need to create action only for changed deposit limits
        foreach ($users_ids_to_log as $user_id) {
            $this->logCurrentLimit($user_id);
        }
    }

    function revertToOldLimits($u_obj){
        $rgls = $this->getByTypeUser($u_obj);
        $users_ids_to_log = [];

        foreach ($rgls as $rgl) {
            if (empty($rgl['old_lim'])) {
                continue;
            }

            // We only do this if the old limit was lower
            if($rgl['old_lim'] < $rgl['cur_lim'] || $rgl['new_lim'] == -1){
                $this->logAction($u_obj, $rgl['type'], ['limit' => $rgl['cur_lim']], 'revert-to-old-limit', "old limit: ".$rgl['old_lim']);
                $rgl['cur_lim'] = $rgl['old_lim'];
                // If we revert a limit removal we need to change the new_lim value to 0 from -1,
                // we also reset changes_at even though it should strictly be unnecessary at this point (ie should already be a zero date)
                if($rgl['new_lim'] == -1){
                    $rgl['new_lim']    = 0;
                    $rgl['changes_at'] = phive()->getZeroDate();
                }
            }

            // We remove the old limit to avoid re-triggers.
            $rgl['old_lim'] = 0;
            $this->db->sh($u_obj)->save('rg_limits', $rgl);

            if ($rgl['type'] === self::TYPE_DEPOSIT) {
                $users_ids_to_log[$rgl['user_id']] = $rgl['user_id'];
            }
        }

        $u_obj->deleteSetting('has_old_limits');

        // we need to create action only for changed deposit limits
        foreach ($users_ids_to_log as $user_id) {
            $this->logCurrentLimit($user_id);
        }
    }

    function getSingleLimit($u_obj, $type){
        if(empty($u_obj)){
            return false;
        }
        return $this->db->sh($u_obj)->loadAssoc($this->sqlWhereUser($u_obj, "AND `type` = '$type'"));
    }

    /**
     * Used to get the timeout limit in order to stop gameplay.
     *
     * @param DBUser $u_obj The user object.
     *
     * @return array The timeout limit.
     */
    function getTimeOutLimit($u_obj){
        return $this->getSingleLimit($u_obj, 'timeout');
    }

    function getRcCountries(){
        return explode(' ', phive('Config')->getValue('lga', 'reality-check-countries'));
    }

    function getRcLimit($u_obj = null){
        return $this->getSingleLimit($u_obj, 'rc');
    }

    function progressResettableTimeLimit($u_obj, $type = '', $rgl = [], $progress = null, $use_cache = false){
        if($use_cache && !empty($this->rgls)){
            $rgls = $this->rgls;
        } else {
            $rgls = !empty($rgl) ? [$rgl] : $this->getByTypeUser($u_obj, $type);
        }

        foreach($rgls as &$rgl){
            if(phive()->isEmpty($rgl['started_at'])){
                $rgl['started_at'] = phive()->hisNow();
            } else {
                // As opposed to the logic for the single time limits we need to increase the progress, NOT set
                // it in an absolute fashion.
                $rgl['progress'] += !empty($progress) ? $progress : time() - strtotime($rgl['started_at']);
                // We have to reset started at on every progress since we're incrementing.
                $rgl['started_at'] = phive()->hisNow();
            }

            $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($u_obj, $type);
            $remote_brand = getRemote();

            if ($remote_user_id !== 0) {
                $response = toRemote(
                    $remote_brand,
                    'progressRemoteResettableTimeLimit',
                    [$remote_user_id, $type, $rgl['time_span'], $rgl['progress'], $use_cache]
                );

                if (!$response['success']) {
                    phive('UserHandler')->logAction($u_obj->getId(),
                                                    phive('Distributed')->checkSyncResponse($response,
                                                                                            $u_obj->getId(),
                                                                                            'progress Resettable Time Limit',
                                                                                            $type,
                                                                                            $rgl['time_span'],
                                                                                            $rgl['cur_lim'],
                                                                                            $remote_brand),
                                                    $rgl['type']);
                }
            }


            $this->db->sh($u_obj)->save('rg_limits', $rgl);
        }

        if($use_cache){
            $this->rgls = $rgls;
        }
    }

    function startProgressableTimeLimit($u_obj, $type){
        // If we want to work with the login limit we run this on login for instance.
        $rgls = $this->getByTypeUser($u_obj, $type);
        foreach($rgls as $rgl){
            $rgl['started_at'] = phive()->hisNow();
            $this->db->sh($u_obj)->save('rg_limits', $rgl);
        }
    }

    function checkProgressableTimeLimit($u_obj, $type, $use_cache = false){
        if($use_cache && !empty($this->rgls)){
            $rgls = $this->rgls;
        } else {
            $rgls = $this->getByTypeUser($u_obj, $type);
        }

        if($use_cache){
            $this->rgls = $rgls;
        }

        foreach($rgls as $rgl){
            if(!$this->checkTimeLimit($u_obj, $type, false, $rgl)){
                return false;
            }
        }
        return true;
    }

    function progressTimeLimit($u_obj, $type = '', $rgl = [], $progress = null){
        $rgl = empty($rgl) ? $this->getSingleLimit($u_obj, $type) : $rgl;

        if(empty($rgl)){
            return true;
        }

        if(phive()->isEmpty($rgl['started_at'])){
            $rgl['started_at'] = phive()->hisNow();
        } else {
            $rgl['progress'] = !empty($progress) ? $progress : time() - strtotime($rgl['started_at']);
        }

        return $this->db->sh($u_obj)->save('rg_limits', $rgl);
    }

    function progressTimeout($u_obj, $progress = null){
        return $this->progressTimeLimit($u_obj, 'timeout', [], $progress);
    }

    function progressRc($u_obj, array $rgl = [], $progress = null)
    {

        if (empty($progress) && !empty($rgl) && !phive()->isEmpty($rgl['started_at'])) {
            $total_time = time() - strtotime($rgl['started_at']);
            $progress = empty($rgl['progress']) ? $total_time : $rgl['progress'] + ($total_time - $rgl['progress']);
        }

        return $this->progressTimeLimit($u_obj, 'rc', $rgl, $progress);
    }

    /**
     * @param $u_obj
     * @param array $rgl
     * @param string $type reflects the 'type' column in the rg_limits table
     * @param string $started_at datetime
     * @param bool $force force a reset despite current progress
     * @return true
     */
    function resetTimeLimit($u_obj, array $rgl = [], string $type = '', string $started_at = '', bool $force = false){
        if(!is_object($u_obj)){
            return true;
        }
        $rgl               = empty($rgl) ? $this->getSingleLimit($u_obj, $type) : $rgl;
        // The limit is missing but that's OK, it means the player hasn't set a limit
        // of the type in question.
        if(empty($rgl)){
            return true;
        }

        if($type === 'rc' && !$force){
            // prevent reset rc progress if not reached
            $rgl['progress'] = ($this->convertProgressToCurLim($rgl) >= $rgl['cur_lim']) ? 0 : $rgl['progress'];
        } else {
            $rgl['progress'] = 0;
        }
        $rgl['started_at'] = empty($started_at) ? phive()->getZeroDate() : $started_at;

        $remote_user_id = phive('Distributed')->shouldSyncForRemoteUser($rgl['user_id'], $rgl['type']);
        $remote_brand = getRemote();

        if ($remote_user_id !== 0) {
            $response = toRemote(
                $remote_brand,
                'resetRemoteTimeLimit',
                [$remote_user_id, $rgl['time_span'], $rgl['type'], $rgl['started_at']]
            );

            if (!$response['success']) {
                phive('UserHandler')->logAction($rgl['user_id'],
                                                phive('Distributed')->checkSyncResponse($response,
                                                                                        $rgl['user_id'],
                                                                                        'reset Time Limit',
                                                                                        $rgl['type'],
                                                                                        $rgl['time_span'],
                                                                                        $rgl['cur_lim'],
                                                                                        $remote_brand),
                                                $rgl['type']);
            }
        }



        return $this->db->sh($u_obj)->save('rg_limits', $rgl);
    }

    function resetTimeout($u_obj, $rgl = []){
        return $this->resetTimeLimit($u_obj, $rgl, 'timeout');
    }

    /**
     * @param DBUser $u_obj
     * @param array $rgl
     * @param bool $force force a reset despite current progress
     * @return true
     */
    function resetRc(DBUser $u_obj, array $rgl = [], bool $force){
        return $this->resetTimeLimit($u_obj, $rgl, 'rc', '', $force);
    }

    function startRc($u_obj, $rgl = []){
        $started_at = '';

        if(! empty($rgl['started_at']) && ! ($this->convertProgressToCurLim($rgl) >= $rgl['cur_lim'])) {
            $started_at = phive()->isEmpty($rgl['started_at']) ? phive()->hisNow() : $rgl['started_at'];
        }

        return $this->resetTimeLimit($u_obj, $rgl, 'rc', $started_at);
    }

    function checkTimeLimit($u_obj, $type, $reset = true, $rgl = []){
        $rgl = empty($rgl) ? $this->getSingleLimit($u_obj, $type) : $rgl;

        if(empty($rgl)){
            return true;
        }

        // We have crossed the timeout limit and game play should now be stopped.
        if($this->isReached($rgl)){
            if($reset){
                $this->resetTimeLimit($u_obj, $rgl, $type);
            }
            return false;
        }

        return true;
    }

    function checkTimeout($u_obj){
        return $this->checkTimeLimit($u_obj, 'timeout');
    }

    function checkRc($u_obj){
        return $this->checkTimeLimit($u_obj, 'rc');
    }

    /**
     * Increase/Decrease redis "pending-deposits":
     * - we increase the amount when a deposit start
     * - we decrease the amount when a deposit end (notify)
     *
     * There are some edge cases were we are not sure that we will get a notification, so we are not increasing on deposit start (Ex. CC)
     * In that case if the limit goes below 0, we are at risk of allowing the customer to deposit more than his set rg_limit
     * for this reason we are forcing this variable to never be below 0.
     *
     * @param $u_obj
     * @param $amount
     * @return bool|void
     */
    public function pendingDepositCommon($u_obj, $amount)
    {
        if(!$this->hasLimits($u_obj, 'deposit')){
            return true;
        }
        $expires_in = phive("Config")->getValue('in-limits', 'deposit-limit-pending-timeout-seconds', 900);
        phMinc(mKey($u_obj, 'pending-deposits'), $amount, $expires_in);

        if($this->getPendingDeposit($u_obj) < 0) {
            phMsetShard('pending-deposits', 0, $u_obj, $expires_in);
        }
    }

    function addPendingDeposit($u_obj, $amount){
        $this->pendingDepositCommon($u_obj, $amount);
    }

    function removePendingDeposit($u_obj, $amount){
        $this->pendingDepositCommon($u_obj, -$amount);
    }

    function getPendingDeposit($u_obj){
        return phMgetShard('pending-deposits', $u_obj);
    }

    /**
     * Gets the minimum limit in a group / time span.
     *
     * @param array $limits The group of limits, NOTE that the array needs to be of the same type!
     *
     * @return int The minimum limit.
     */
    function getMin($limits = [], $u_obj = null){
        if(empty($limits)){
            return false;
        }

        $left         = [];
        $extra_amount = $this->getExtraAmount($limits[0], $u_obj);

        foreach($limits as $rgl){
            $left[] = $this->isResettable($rgl) ? $rgl['cur_lim'] - $rgl['progress'] - $extra_amount : $rgl['cur_lim'];
        }

        return min($left);
    }

    function getMinLeftByType($u_obj, $type){
        $limits = $this->getByTypeUser($u_obj, $type);
        return $this->getMin($limits, $u_obj);
    }

    function getGrouped($u_obj, $only_types = [], $key_tspans = false){
        $limits = $this->getByTypeUser($u_obj, $only_types);
        $res    = phive()->group2d($limits, 'type');
        if(!$key_tspans){
            return $res;
        }
        $ret = [];
        foreach($res as $type => $limits)
        {
            $ret[$type] = phive()->reKey($limits, 'time_span');
        }
        return $ret;
    }

    function getMinLeftGrouped($u_obj, $only_types = []){
        $grouped = $this->getGrouped($u_obj, $only_types);
        $mins    = [];

        foreach($grouped as $type => $group){
            $mins[$type] = $this->getMin($group, $u_obj);
        }

        return $mins;
    }

    function logAction($u_obj, $type, $limits, $action, $extra = ''){

        if(is_array($limits[0])){
            $str = implode(',', array_map(function($rgl){ return (int)$rgl['limit']; }, $limits));
        } else if(is_numeric($limits)){
            if (!$this->isResettable($type)){
                $str = $limits;
            } else {
                return;
            }
        } else {
            $str = $limits['limit'];
        }

        if(str_contains($str, ",") && ($this->all_types[$type] === 'money')){
            $user_currency = $u_obj->getCurrency();
            $money_limits = explode (",", $str);

            $limit_day = rnfCents($money_limits[0]) . " " . $user_currency;
            $limit_week = rnfCents($money_limits[1]) . " " . $user_currency;
            $limit_month = rnfCents($money_limits[2]) . " " . $user_currency;

            $str = "{$limit_day},{$limit_week},{$limit_month}";
        }

        phive('UserHandler')->logAction($u_obj, "Action: $action, Type: $type, Limits: $str, Extra: $extra", "$type-rgl-$action", true);
    }

    /**
     * Save current RG limits into table `action` (log of limits).
     * It didn't replace the related logAction because of historical consideration.
     * Because at the moment we haven't checked if we can simply remove it.
     *
     * We need this log for report (f.e. Spain RUD report)
     *
     * @param DBUser|int|null $user
     * @param string $type
     *
     * @return void
     */
    public function logCurrentLimit($user, string $type = self::TYPE_DEPOSIT): void
    {
        // Warning!! Any changes made to the description must be incorporated into jurisdictional reporting. Please contact Reporting Team and co-ordinate with relevant Product Owners.
        $user = cu($user);

        if (empty($user)) {
            phive('Logger')->getLogger('rg_limits')->error(
                'User not found',
                ['method' => 'RgLimits::logCurrentLimit']
            );
            return;
        }

        $limits = $this->getByTypeUser($user, $type);

        $limit_day = $limits['day']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;
        $limit_week = $limits['week']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;
        $limit_month = $limits['month']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;

        $user_currency = $user->getCurrency();
        if($limit_day != -1){
            $limit_day = rnfCents($limit_day) . " " . $user_currency;
            $limit_week = rnfCents($limit_week) . " " . $user_currency;
            $limit_month = rnfCents($limit_month) . " " . $user_currency;
        }

        $description = "Limits: {$limit_day},{$limit_week},{$limit_month}";
        $tag = $type . '-rgl-' . self::ACTION_CURRENT;

        phive('UserHandler')->logAction($user, $description, $tag);
    }

    public function logAppliedLimit($user, string $type = self::TYPE_DEPOSIT): void
    {
        // Warning!! Any changes made to the description must be incorporated into jurisdictional reporting. Please contact Reporting Team and co-ordinate with relevant Product Owners.
        $user = cu($user);

        if (empty($user)) {
            phive('Logger')->getLogger('rg_limits')->error(
                'User not found',
                ['method' => 'RgLimits::logAppliedLimit']
            );
            return;
        }

        $limits = $this->getByTypeUser($user, $type);

        $limit_day = $limits['day']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;
        $limit_week = $limits['week']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;
        $limit_month = $limits['month']['cur_lim'] ?? self::LIMIT_VALUE_REMOVED;

        $description = "{$limit_day},{$limit_week},{$limit_month}";

        $this->logAction($user, $type, ['limit' => $description], 'applied');
    }

    /**
     * Log all active limits in actions table to have the history of limits for the player
     *
     * @param int $user_id
     * @param string $type
     * @param string|int $limit
     * @param string $time_span
     * @param mixed $requested_date
     * @param mixed $end_datetime
     */
    public function addRgLimitToHistory(int $user_id, string $type, $limit, string $time_span, $requested_date = '', $end_datetime = ''): void
    {
        $requested_date = !empty($requested_date) ? $requested_date : phive()->hisNow();
        $limit_logs = new RGLimitChangeHistoryMessage([
            'user_id'          => (int) $user_id,
            'request_datetime' => $requested_date,
            'start_datetime'   => phive()->hisNow(),
            'end_datetime'     => $end_datetime,
            'type'             => $type,
            'limit'            => (int) $limit,
            'time_window'      => $time_span,
            'event_timestamp'  => time(),
        ]);

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', ['rg_limit_change', $limit_logs], $user_id);
    }

    /**
     * @param DBUser $u_obj
     * @param $type
     * @param $forced_until
     * @return mixed
     */
    function forceLimit($u_obj, $type, $forced_until) {
        // can't convert string to time
        if (strtotime($forced_until) === false) {
            return false;
        }

        // invalid type
        if(!$this->checkType($type)) {
            return false;
        }

        // user not found
        if (empty($user_id = $u_obj->getId())) {
            return false;
        }

        $update = $this->db->sh($u_obj)->updateArray(
            'rg_limits',
            $update = compact('forced_until'),
            $where = compact('type', 'user_id')
        );

        if ($update) {
            $limits = $this->getByTypeUser($u_obj, $type);
            foreach ($limits as $limit) {
                $this->addRgLimitsLog(
                    $u_obj,
                    $type,
                    $limit['time_span'],
                    $limit['cur_lim'],
                    $limit['cur_lim'],
                    self::REQUEST_TYPE_ADD_FORCED_UNTIL,
                    null,
                    $forced_until
                );
            }
        }

        return $update;
    }

    /**
     * @param DBUser $u_obj
     * @param $type
     * @return bool
     */
    function removeForcedLimit($u_obj, $type) {
        // invalid type
        if(!$this->checkType($type)) {
            return false;
        }

        // user not found
        if(empty($u_obj)){
            return false;
        }

        $user_id = uid($u_obj);

        $limits = $this->getByTypeUser($u_obj, $type);
        foreach ($limits as $limit) {
            $this->addRgLimitsLog(
                $u_obj,
                $type,
                $limit['time_span'],
                $limit['cur_lim'],
                $limit['cur_lim'],
                self::REQUEST_TYPE_REMOVE_FORCED_UNTIL
            );
        }

        return $this->db->sh($u_obj)->updateArray(
            'rg_limits',
            [
                'forced_until' => null
            ],
            $where = compact('type', 'user_id')
        );
    }


    /**
     * Limits may not be altered when: limit is forced, forced limit is not expired and the new limit is not a decrease
     *
     * @param $limit_object
     * @param $new_value
     * @param bool $check_decrease
     * @return array|bool
     */
    function exitIfForced($limit_object, $new_value, $check_decrease = true) {
        $limit_object = (array)$limit_object;

        // limit is not forced
        if (empty($limit_object['forced_until']) || $limit_object['forced_until'] == '0000-00-00 00:00:00') {
            return false;
        }

        // forced limit expired
        if (strtotime(phive()->hisNow()) > strtotime($limit_object['forced_until'])) {
            return false;
        }

        // check if the new value is a decrease
        if ($check_decrease && $this->isIncDecLimit($limit_object) && $new_value < $limit_object['cur_lim']) {
            return false;
        }

        if (empty($this->admin2_context)) {
            dieJson(t('limit.is.forced'), 'nok');
        }

        return ['msg' => t('limit.is.forced')];
    }

    /**
     * Validations for the extra params of a limit
     * For now only make sure that is not bigger than 255
     *
     * @param string $extra
     * @return bool
     */
    public function processExtra($extra)
    {
        return strlen($extra) < 255 ? $extra : false;
    }

    /**
     * From the BO we can add a setting to the user to enforce the setup of a limit.
     * When that is done the user will see a popup on every page load that will enforce him to put a limit.
     *
     * When adding/changing a limit we check if the setting exist, and we remove it
     *
     * @param $u_obj
     * @param $type
     */
    public function removeEnforcedPopup($u_obj, $type)
    {
        if($this->enforced_popup_removed[$type]) {
            return;
        }

        // TODO change the setting name to remove the mapping and have all the "force setting" to be like "force_TYPE_limit" /Paolo
        $map_type_with_setting = [
            'login' => 'force_login_limit',
            'betmax' => 'force_max_bet_protection',
        ];

        if($u_obj->getSetting($map_type_with_setting[$type]) == 1) {
            $u_obj->deleteSetting($map_type_with_setting[$type]);
            $this->enforced_popup_removed[$type] = true;
        }
    }

    /**
     * Method to be used to generate tags based on $limit actions
     *
     * @param $limit
     * @param $action
     * @return string
     */
    public function getActionTag($limit, $action)
    {
        return "$limit-rg-$action";
    }

    /**
     * Method used to get all actions for one or more $limits
     *
     * @param string|array $limits
     * @return array
     */
    public function getAllActionTags($limits)
    {
        $all = [];
        $limits = is_array($limits) ? $limits : [$limits];

        foreach ($limits as $limit) {
            $all = array_merge($all, ["$limit-rgl-add", "$limit-rgl-change", "$limit-rgl-remove"]);
        }

        return $all;
    }

    /**
     * We're going though the list of available products in the current jurisdiction and checking that it has been
     * globally activated otherwise we do not apply those limits.
     */
    private function setProductLimits($user_id = "", $force_all = false)
    {
        $list_of_products = phive()->getSetting('products');
        foreach($list_of_products as $product => $limits) {
            $should_process = $force_all ||
                $product !== "sportsbook" ||
                lic('isSportsbookEnabled', ["", $user_id]) ||
                lic('isPoolxEnabled');

            if ($should_process) {
                foreach ($limits['rg'] as $org_limit => $new_limit) {
                    $this->all_types[$new_limit] = 'money';
                    $this->resettable[] = $new_limit;
                    $this->incrementable_limits[] = $new_limit;
                    $this->grouped_resettables[$org_limit][] = $new_limit;
                }
            }
        }
    }

    /**
     * This will return the limit type we want, either the normal type or the product type if it exists
     *
     * @param $type
     * @param string $product
     * @return string
     */
    public function getProductLimitType($type, $product = '')
    {
        $product_type = "{$type}-{$product}";

        if(key_exists($product_type, $this->all_types)) {
            return $product_type;
        }

        return $type;
    }

    /**
     * Properly update lock limit "changes_at" with @see RgLimits::getChangeStamp() cooloff period
     * when action is performed by an admin, common logic would zero out the column.
     * If the limit doesn't exist already nothing happen.
     *
     * @param int|null $user_id
     * @return bool
     */
    public function revokeLockLimit(int $user_id = null)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return false;
        }
        $cur_limit = $this->getByTypeUser($user, 'lock');
        if (empty($cur_limit)) {
            return false;
        }
        $cur_limit = $cur_limit['na'];
        $chg_stamp = $this->getChangeStamp(self::DEFAULT_COOLOFF, $cur_limit, $user->getCountry());

        $this->db->sh($user)->updateArray('rg_limits', ['changes_at' => $chg_stamp], ['id' => $cur_limit['id']]);
        return true;
    }

    /**
     * We check if user is present, config is enabled for the jurisdiction and it has not limits first.
     *
     * Once this is done, we check the SOWd content and add limits according to configs.
     *
     * @param DBUser $user
     * @return false|mixed|void
     */
    public function doCheckNetDepositLimit($user)
    {
        if (empty($user)) {
            return false;
        }

        $jur = licJur($user);

        if (phive("Config")->getValue('affordability', "net-deposit-limit-status-{$jur}") != 'on') {
            return false;
        }

        if ($this->hasLimitsOnAllTimeSpans($user, 'net_deposit')) {
            return false;
        }

        $documents = phive('Dmapi')->getUserDocumentsV2(uid($user));

        $candidate_doc = false;
        foreach ($documents as $document) {
            if ($document['tag'] == 'sourceoffundspic' && $document['status'] == 'approved') {
                $candidate_doc = $document;
                break;
            }
        }

        if (empty($candidate_doc)) {
            return false;
        }

        /** @var SourceOfFunds $sourceOfFunds */
        $sourceOfFunds = phive('DBUserHandler/SourceOfFunds');

        $income_level_key = $sourceOfFunds->getIncomeLevelKey(
            $user->getCurrency(),
            $candidate_doc['source_of_funds_data']['low_income'],
            $candidate_doc['source_of_funds_data']['top_income']
        );

        $savings_level_key = $sourceOfFunds->getSavingsLevelKey(
            $user->getCurrency(),
            $candidate_doc['source_of_funds_data']['low_savings'],
            $candidate_doc['source_of_funds_data']['top_savings']
        );

        $config_val = phive('Config')->getValue('affordability', "net-deposit-limit-cents-{$jur}");

        if (empty($config_val)) {
            return false;
        }

        $time_spans = array_flip($this->getTimeSpans('net_deposit'));
        foreach ($time_spans as $k => $v) {
            $time_spans[$k] = 0;
        }

        foreach (explode(' ', $config_val) as $config) {
            list($level_key, $timespan_key, $limit) = explode(':', $config);
            if (empty($limit)) {
                continue;
            }
            if ($level_key == $income_level_key || $level_key == $savings_level_key) {
                $time_spans[$timespan_key] += $limit;
            }
        }

        foreach ($time_spans as $k_time_span => $limit_value) {
            $this->addLimit($user, 'net_deposit', $k_time_span, mc($limit_value, $user));
        }

        return true;
    }

    /**
     * Change Limit Now called for ES
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param string $timespan
     *
     * @return bool
     */
    public function updateChangesAtLimitToNow(DBUser $u_obj, string $type, string $timespan): bool
    {
        $limits = $this->getByTypeUser($u_obj, $type);

        if (!isset($limits[$timespan])) {
            return false;
        }

        return $this->db->sh($u_obj)->updateArray('rg_limits', ['changes_at' => phive()->hisNow(time() - 1)], ['id' => $limits[$timespan]['id']]);
    }

    /**
     * Change Reset at for Limit called in ES
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param string $timespan
     * @param string $reset_at
     *
     * @return bool
     */
    public function setResetAtLimit(DBUser $u_obj, string $type, string $timespan, string $reset_at): bool
    {
        $limits = $this->getByTypeUser($u_obj, $type);

        if (!isset($limits[$timespan])) {
            return false;
        }

        return $this->db->sh($u_obj)->updateArray('rg_limits', ['resets_at' => $reset_at], ['id' => $limits[$timespan]['id']]);
    }

    /**
     * Removing rg limit for specific user by type and extra fields
     *
     * @param DBUser $user_obj The DBUser object.
     * @param string $type     The type of rg limit.
     * @param string $extra    Extra info/config for the limit.
     *
     * @return bool
     */
    public function removeRgLimitByTypeAndExtra(DBUser $user_obj, string $type, string $extra): bool
    {
        return $this->db->delete('rg_limits', ['type' => $type, 'extra' => $extra], $user_obj->getId());
    }

    /**
     * Filtering and Converting RG Limit from cents to User Currency when is money,
     * and log in Action table
     *
     * @param DBUser $user_obj  The DBUser object.
     * @param string $type  The type of rg limit.
     * @param string $action  Action : add or change limit.
     * @param integer $limit  The Limit value.
     * @param string $extra  Extra info/config for the limit.
     *
     * @return void
     */
    private function convertUserCurrencyAndLogAction(DBUser $user_obj, string $type, string $action, int $limit, string $extra = null): void
    {
        if($this->getUnit($type) === 'money'){
            $user_currency = $user_obj->getCurrency();
            $str = rnfCents($limit) . " " . $user_currency;
            phive('UserHandler')->logAction($user_obj, "Action:$action, Type:$type, Limits: $str, Extra:$extra", "$type-rgl-$action", true);
        } else {
            $this->logAction($user_obj,$type,$limit,$action);
        }
    }

    public function getDepositLimitWarning()
    {


        $timeSpan = null;
        $limits = $this->getByTypeUser(cu(), 'deposit');
        foreach ($limits as $limit) {
            if ($this->isReached($limit, 0, true) && ($timeSpan  === null || $limit['resets_at'] > $timeSpan )) {
                $timeSpan  = $limit['resets_at'];
            }
        }
        return !empty($timeSpan) ? $timeSpan: false;
    }

    /**
     * @param DBUser $u_obj
     * @param string $loss_resets_at
     *
     * @return void
     */
    protected function syncNdlResetsWithLossResetsStamp(DBUser $u_obj, string $loss_resets_at): void
    {
        if (!lic('getLicSetting', ['sync_ndl_resets_with_loss_limit_resets'], $u_obj)) {
            return;
        }

        $ndl = $this->getLimit($u_obj, 'net_deposit', 'month');

        if (empty($ndl)) {
            return;
        }

        $result = $this->db->sh($u_obj)->updateArray('rg_limits',
            ['resets_at' => $loss_resets_at],
            ['id' => $ndl['id']]);

        if (!$result) {
            return;
        }

        phive('UserHandler')->logAction(
            $u_obj->getId(),
            "NDL ‘resets_at’ value synced with Loss Limit ‘resets_at’. Next reset at {$loss_resets_at}",
            'reset-net_deposit-month'
        );
    }

    /**
     * Tris method is triggered automatically from Admin2 on event ConfigUpdated on updating 'global-customer-net-deposit'
     *
     * @param string $action set|align
     *
     * @return void
     */
    public function refreshCustomerNetDepositLimit(string $action): void
    {
        $config = phive('Config')->getByNameAndTag('global-customer-net-deposit', 'RG');
        $global_customer_net_deposit_limits = phive('Config')->getValueFromTemplate($config);

        foreach ($global_customer_net_deposit_limits as $jurisdiction => $limit) {
            $force_ndt = false;
            try {
                if (strtoupper($limit) === 'NDT') {
                    $force_ndt = true;
                    $license = LicenseFactory::licenseByJurisdiction($jurisdiction);
                    $default_limit = (int)$license->getLicSetting('net_deposit_limit')['month'];

                    if (empty($default_limit)) {
                        continue;
                    }
                    $limit = $default_limit;
                }

                if ($action === 'set') {
                    $this->addCustomerNetDepositLimitFromGlobalSetting($jurisdiction, (int)$limit, $force_ndt);
                } else {
                    $this->alignCustomerNetDepositLimitWithGlobalSetting($jurisdiction, (int)$limit, $force_ndt);
                }
            } catch (Exception $e) {
                error_log(
                    "Alignment " . static::TYPE_CUSTOMER_NET_DEPOSIT . " with global setting for
                    jurisdiction {$jurisdiction} failed: {$e->getMessage()}"
                );
                continue;
            }
        }
    }

    /**
     * Priority always takes a customer's 'net_deposit' as an own threshold.
     * If a customer doesn't have 'net_deposit' then 'global-customer-net-deposit' setting will be as NDT
     *
     * @param string $jurisdiction
     * @param int    $limit
     * @param bool   $force_ndt
     *
     * @return void
     */
    public function alignCustomerNetDepositLimitWithGlobalSetting(string $jurisdiction, int $limit, bool $force_ndt): void
    {
        $limits_to_update = phive('SQL')->shs()
            ->loadArray("SELECT
                        rl.id,
                        rl.user_id,
                        rl.cur_lim,
                        u.currency,
                        rl_cndt.cur_lim as customer_ndt
                    FROM rg_limits AS rl
                    JOIN users_settings AS j ON (j.user_id = rl.user_id AND j.setting = 'jurisdiction')
                    JOIN users AS u ON (u.id = rl.user_id)
                    JOIN currencies AS c ON u.currency = c.code
                    LEFT JOIN rg_limits AS rl_cndt ON (rl_cndt.user_id = u.id AND rl_cndt.`type` = 'net_deposit' AND rl_cndt.time_span = 'month')
                    WHERE rl.`type` = 'customer_net_deposit'
                      AND rl.time_span = 'month'
                      AND j.value = '{$jurisdiction}'
                      AND (rl.cur_lim > ({$limit} * c.`mod`) OR rl.cur_lim > rl_cndt.cur_lim)"
            );

        foreach ($limits_to_update as $rg_limit) {
            $user = phive('DBUserHandler')->newUser([
                'id' => $rg_limit['user_id'],
                'currency' => $rg_limit['currency'],
            ]);
            $system_ndt_in_user_currency = mc($limit, $user);

            if ($force_ndt && !empty($rg_limit['customer_ndt'])) {
                $limit = (int)$rg_limit['customer_ndt'];
            } else {
                $limit = (int)$system_ndt_in_user_currency;
            }

            if ((int)$rg_limit['cur_lim'] === $limit) {
                continue;
            }

            $this->changeLimit($user, static::TYPE_CUSTOMER_NET_DEPOSIT, $limit, 'month');
            phive('DBUserHandler')->logAction(
                $user,
                static::TYPE_CUSTOMER_NET_DEPOSIT . " aligned with 'global-customer-net-deposit' setting.
            Customer limit: {$rg_limit['cur_lim']}. Config limit: {$limit}",
                'comment'
            );
            $user->addComment("We stopped the customer from setting an NDL of {$rg_limit['cur_lim']}
            and we set the limit to {$limit} due to affordability information we have on the customer.",
                0,
                'rg-action'
            );
        }
    }

    /**
     * Priority always takes a customer's 'net_deposit' as an own threshold.
     *  If a customer doesn't have 'net_deposit' then 'global-customer-net-deposit' setting will be as NDT
     *
     * @param string $jurisdiction
     * @param int    $limit
     * @param bool   $force_ndt
     *
     * @return void
     */
    public function addCustomerNetDepositLimitFromGlobalSetting(string $jurisdiction, int $limit, bool $force_ndt)
    {
        $users = phive('SQL')->shs()
            ->loadArray("SELECT
                        u.id,
                        u.country,
                        u.currency,
                        u.register_date,
                        rl.cur_lim,
                        rl_cndt.cur_lim as customer_ndt
                    FROM users AS u
                    JOIN users_settings AS j ON (j.user_id = u.id AND j.setting = 'jurisdiction')
                    JOIN users_settings AS r ON (r.user_id = u.id AND r.setting = 'registration_end_date')
                    LEFT JOIN rg_limits AS rl ON (rl.user_id = u.id AND rl.`type` = 'customer_net_deposit' AND rl.time_span = 'month')
                    LEFT JOIN rg_limits AS rl_cndt ON (rl_cndt.user_id = u.id AND rl_cndt.`type` = 'net_deposit' AND rl_cndt.time_span = 'month')
                    WHERE rl.cur_lim IS NULL AND j.value = '{$jurisdiction}'"
            );

        foreach ($users as $user_data) {
            $user = phive('DBUserHandler')
                ->newUser([
                    'id' => $user_data['id'],
                    'country' => $user_data['country'],
                    'currency' => $user_data['currency'],
                    'register_date' => $user_data['register_date'],
                ]);

            if ($force_ndt && !empty($user_data['customer_ndt'])) {
                $limit = $user_data['customer_ndt'];
            } else {
                $limit = mc($limit, $user);
            }

            $this->addLimit($user, static::TYPE_CUSTOMER_NET_DEPOSIT, 'month', $limit);

            phive('DBUserHandler')->logAction(
                $user,
                static::TYPE_CUSTOMER_NET_DEPOSIT . " is set according to 'global-customer-net-deposit' setting.
            Customer limit: {$limit}",
                'comment'
            );
        }
    }

    /**
     * Determines the requester's user_id and if the request was made by the user, bo user or system related logic.
     *
     * @return array
     */
    protected function getRequesterIdAndType(): array
    {
        if (isCli()) {
            return [cu('system')->getId(), self::REQUESTER_TYPE_SYSTEM];
        }

        $user = cu();
        if (!$user instanceof DBUser) {
            return [false, false];
        }

        $user_id = cu()->getId();

        if ($this->isAdmin2Context()) {
            return [$user_id, self::REQUESTER_TYPE_BO_USER];
        }

        return [$user_id, self::REQUESTER_TYPE_USER];
    }

    /**
     * Logs changes related to user's rg_limits into rg_limits_log table.
     *
     * @param DBUser $user
     * @param string $limit_type
     * @param string $limit_span
     * @param int $pre_value
     * @param int $post_value
     * @param string $request_type
     * @param string|null $applied_at
     * @param string|null $forced_until
     * @return bool Boolean true if insert was successful, false otherwise.
     */
    public function addRgLimitsLog(
        DBUser $user,
        string $limit_type,
        string $limit_span,
        int $pre_value,
        int $post_value,
        string $request_type,
        string $applied_at = null,
        string $forced_until = null
    ): bool
    {
        try {
            $user_id = $user->getId();

            if (!in_array($request_type, self::REQUEST_TYPE_ENUMS)) {
                throw new Exception("Invalid request type: {$request_type}");
            }

            if (!$this->checkType($limit_type)) {
                throw new Exception("Invalid limit type: {$limit_type}");
            }

            list($requester_id, $requester_type) = $this->getRequesterIdAndType();
            if (!$requester_id || !$requester_type) {
                throw new Exception("Actor could not be determined.");
            }

            $insert =             [
                'user_id' => $user_id,
                'requester_id' => $requester_id,
                'requester_type' => $requester_type,
                'limit_type' => $limit_type,
                'limit_span' => $limit_span,
                'pre_value' => $pre_value,
                'post_value' => $post_value,
                'request_type' => $request_type,
                'is_remote_request' => $this->isRemoteContext(),
            ];

            if (!empty($forced_until)) {
                $insert['forced_until'] = $forced_until;
                $insert['is_forced'] = true;
            }

            if (!empty($applied_at)) {
                $insert['applied_at'] = $applied_at;
            }

            if ($request_type === self::REQUEST_TYPE_REMOVE_FORCED_UNTIL) {
                $insert['is_forced'] = true;
            }

            return $this->db->sh($user_id)->insertArray(self::RG_LIMITS_LOG_TABLE, $insert, null, false, false);
        } catch (Throwable $exception) {
            phive('Logger')
                ->getLogger()
                ->error(__METHOD__, [
                    "Error message:" => $exception->getMessage(),
                    "user_id" => $user->getId(),
                    "limit_type" => $limit_type,
                    "limit_span" => $limit_span,
                    "pre_value" => $pre_value,
                    "post_value" => $post_value,
                    "request_type" => $request_type,
                    "applied_at" => $applied_at,
                    "forced_until" => $forced_until,
                    "isAdmin2Context" => $this->isAdmin2Context(),
                    "isRemoteContext" => $this->isRemoteContext()
                ]);
        }

        return false;
    }

    /**
     * Based on PHNX-4898 the fixed-cycle timestamp based on three sources such as:
     * - loss limit
     * - deposit limit
     * - first registration timestamp
     *
     * @param DBUser $user
     *
     * @return bool
     */
    public function shouldApplyFixedCycleTimestamp(DBUser $user): bool
    {
        if ($user->hasSetting('rg-month-limit-set-at')) {
            return true;
        }

        $jurisdiction = $user->getJurisdiction();
        $jurisdictions = phive('Config')
            ->valAsArray('RG', 'rg-limits-fixed-cycle-reset-timestamp-in-jurisdictions', ',');

        return in_array($jurisdiction, $jurisdictions, true);
    }

    public function resettableTypes(): array
    {
        return $this->resettable;
    }
}
