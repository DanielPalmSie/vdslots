<?php

use Carbon\Carbon;
use DBUserHandler\DBUserRestriction;
use DBUserHandler\Session\WinlossBalance;
use DBUserHandler\Session\WinlossBalanceInterface;
use Laraphive\Domain\User\DataTransferObjects\LoginCommonData;

require_once __DIR__ . '/../UserHandler/User.php';

/**
 * An object wrapper around mainly the users and users_settings tables. This contains more specific logic applicable
 * to a casino.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_settings The wiki page for the users settings table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users The wiki page for the users table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_comments The wiki page for the users comments table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_sessions The wiki page for users sessions.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_user_flags The wiki page for user flags.
 */
class DBUser extends User{

    public const CDD_REQUESTED = 'requested';
    public const CDD_INPROGRESS = 'inprogress';
    public const CDD_APPROVED= 'approved';

    public const SCV_EXPORT_STATUS_VALUE_INITIATED = 'initiated';
    public const SCV_EXPORT_STATUS_VALUE_HISTORY_MESSAGE_CREATED = 'history-message-created';
    public const SCV_EXPORT_STATUS_VALUE_FAILED = 'failed';
    public const SCV_EXPORT_STATUS_VALUE_SCV_ERROR = 'scv-error';
    public const SCV_EXPORT_STATUS_VALUE_COMPLETED = 'completed';
    public const SCV_EXPORT_STATUS_VALUE_INCORRECT_LINK= 'incorrect-link';
    public const SCV_ALLOWED_EXPORT_STATUSES = [
        self::SCV_EXPORT_STATUS_VALUE_INITIATED,
        self::SCV_EXPORT_STATUS_VALUE_HISTORY_MESSAGE_CREATED,
        self::SCV_EXPORT_STATUS_VALUE_FAILED,
        self::SCV_EXPORT_STATUS_VALUE_SCV_ERROR,
        self::SCV_EXPORT_STATUS_VALUE_COMPLETED,
        self::SCV_EXPORT_STATUS_VALUE_INCORRECT_LINK,
    ];
    public const GA_COOKEI_NAME = '_ga';
    public const GA_CLIENT_COOKIE_NAME = 'ga_cookie_id';

    /**
     * Starts / inserts a database sesson row, typically happens when the user logs in.
     *
     * @param string The $fingerprint is a piece of information used to identify a device.
     * @param bool $otp_validated_session True if the user authenticated via OTP (2 factor).
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData|null $request
     *
     * @return int The user sessions row id.
     */
    function startSession($fingerprint = null, $otp_validated_session = false, LoginCommonData $request = null){
        $uid = $this->getId();
        $insert = [
            'updated_at'  => phive()->hisNow(),
            'equipment'   => (is_null($request) || !$request->getIsApi()) ? phive()->deviceType() : $request->getDevice(),
            'user_id'     => $uid,
            'ip'          => $request->getIp() ?: remIp(),
            'otp'         => (int)$otp_validated_session,
            'end_reason'  => '',
            'ip_classification_code'  => '',
            'login_method' => $request->getLoginMethod() ?: '',
        ];

        if(!empty($fingerprint)){
            $insert['fingerprint'] = md5($fingerprint);
        }

        $cur_sess_id = phive('SQL')->sh($uid)->insertArray('users_sessions', $insert);

        if(is_null($request) || !$request->getIsApi()){
            $_SESSION['cur_sess_id'] = $cur_sess_id;
        }
        return $cur_sess_id;
    }

    /**
     * Gets a user session row from the database, the thing here is the LIMIT clause that controls whether or not we get the
     * latest, prior or some other row.
     *
     * @param string $limit The LIMIT clause.
     *
     * @return array The session row.
     */
    function getSessionFromHistory($limit = "0,1"){
        $uid = $this->getId();
        return phive('SQL')->sh($uid)->loadAssoc("SELECT * FROM users_sessions WHERE user_id = $uid ORDER BY id DESC LIMIT $limit");
    }

    /**
     * By default we are operating the updates on the latest users_sessions.
     * In some special cases we need to update the previous session instead, otherwise we end up with an unclosed session.
     *
     * Ex. "login from different client"
     * - previous session was still open, then when we login we create a new one, and we need to log the logout reason on the previous session
     *
     * @param array $update_arr The key => value array to update the session with.
     * @param boolean $previous_session Whether or not to update the previous session or not.
     * @return bool True if the update executed successfully.
     */
    public function updateSession($update_arr = [], $previous_session = false){
        $cur_sess = $this->getCurrentSession();
        $cur_id = $cur_sess['id'];
        if($previous_session) {
            $prev_sess = $this->getPreviousSession();
            $cur_id = $prev_sess['id'];
        }
        $update_arr = empty($update_arr) ? ['updated_at' => phive()->hisNow()] : $update_arr;
        $conditions = [
            'id' => $cur_id,
            'ended_at' => '0000-00-00 00:00:00'
        ];

        return phive('SQL')->sh($this->userId)->updateArray('users_sessions', $update_arr, $conditions);
    }

    /**
     * Closes a session by updating the current users sessions row with a proper ended_at row etc.
     *
     * @param string $reason Session end reason.
     * @param bool $do_arf Whether or not to excute misc. AML, RG and / or Fraud flagging logic.
     * @param boolean $previous_session Whether or not to update the previous session or not.
     * @return bool True if the update executed successfully.
     */
    public function endSession($reason, $do_arf = true, $previous_session = false){

        $res = $this->updateSession(['updated_at' => phive()->hisNow(), 'end_reason' => $reason, 'ended_at' => phive()->hisNow()], $previous_session);

        if ($do_arf) {
            phive()->pexec('Cashier/Arf', 'invoke', ['onSessionEnd', $this->userId]);
        }

        // TODO henrik remove and fix the actually issue of unclosed sessions instead.
        if(phive('DBUserHandler')->getSetting('forcefully_close_old_session', false) !== false) {
            $uid = $this->getId();
            phive('SQL')->sh($uid)->query("
                UPDATE users_sessions
                SET ended_at = updated_at, end_reason = 'Timeout sessions'
                WHERE user_id = '{$uid}'
                AND ended_at = '".phive()->getZeroDate()."'
                AND created_at < DATE_SUB(NOW() , INTERVAL 1 DAY)
                LIMIT 100
            ");
        }

        return $res;
    }

    /**
     * Closes a Game session by updating the current users game sessions row with a proper end_time row etc.
     *
     * @return bool True if the update executed successfully.
     */
    public function endGameSessionByGameSessionId($gameSessionId,$uid)
    {
        return phive('SQL')->sh($uid)->query("
            UPDATE users_game_sessions
            SET end_time = now()
            WHERE user_id = '{$uid}'
            AND id = '{$gameSessionId}'
        ");
    }

    /**
     * Gets the previous session.
     *
     * @uses DBUser::getSessionFromHistory()
     *
     * @return array The previous session row.
     */
    function getPreviousSession(){
        return $this->getSessionFromHistory("1,2");
    }


    /**
     * Gets the current / latest session.
     *
     * @uses DBUser::getSessionFromHistory()
     *
     * @return array The current session row.
     */
    function getCurrentSession(){
        return $this->getSessionFromHistory("0,1");
    }

    /**
     * Gets the current / latest session length in a specific time unit.
     *
     *  @uses Phive::subtractTimes()
     *
     * @param string $format How do we want to report the result? In years (y), days (d), hours (h), minutes (m) or seconds (s)?
     * @param int $precision The precision number to pass to round.
     *
     * @return int|float The session length in the indicated format.
     */
    function getSessionLength($format = 'h', $precision = 0){
         return phive()->subtractTimes(time(), strtotime($this->getCurrentSession()['created_at']), $format, $precision);
    }

    /**
     * Sets a flag, the user_flags table is similar to the users_settings table but the flag is both the key and the value
     * at the same time.
     *
     * @param mixed $flag The flag.
     *
     * @return int The id of the inserted flag row.
     */
    function setFlag($flag){
        return phive('SQL')->sh($this->userId, '', 'user_flags')->insertArray('user_flags', ['user_id' => $this->getId(), 'flag' => $flag]);
    }

    /**
     * We never need to get anything from the flags table, only check if flags exist or not and this method does that.
     *
     * @param mixed $flag The flag.
     *
     * @return bool True if the flag exists, false if not.
     */
    function hasFlag($flag){
        $flag = phive('SQL')->sh($this->userId, '', 'user_flags')->getValue('', 'flag', 'user_flags', ['user_id' => $this->getId(), 'flag' => $flag]);
        return !empty($flag);
    }

    // TODO henrik remove
    function getBankAccounts(){
        $rows = $this->getAllSettings("setting LIKE '%-bank-acc-num-%'");
        $ret  = [];
        foreach($rows as $r){
            list($bank_alias, $acc_num) = explode(',', str_replace('-bank-acc-num-', ',', $r['setting']));
            $ret[$bank_alias][]         = $acc_num;
        }
        return $ret;
    }

    /**
     * @param string $trigger_name
     * @return bool
     */
    public function hasTriggerFlag(string $trigger_name): bool
    {
        $log = phive('SQL')->sh($this->getId())->loadObject(
            "SELECT id FROM triggers_log WHERE user_id = {$this->getId()} AND trigger_name = '{$trigger_name}' ORDER BY created_at DESC LIMIT 1"
        );

        return isset($log->id);
    }

    /**
     * Sometimes people want to be able to login from a different country because they're on a vacation for instance.
     *
     * Basic wrapper around DBUser::setSetting().
     *
     * @uses DBUser::setSetting()
     *
     * @param string $country ISO2 country code.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    function addLoginCountry($country){
        return $this->setSetting('login-allowed-'.$country, 1);
    }

    /**
     * Checks if the user can login from the indicated country.
     *
     * Basic wrapper around User::hasSetting().
     *
     * @uses User::hasSetting()
     *
     * @param string $country ISO2 country code.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    function allowedLoginCountry($country){
        return $this->hasSetting('login-allowed-'.$country);
    }

    /**
     * Checks if a user is KYC verified.
     *
     * @return bool True if verified, false otherwise.
     */
    function isVerified(){
        $v = $this->getSetting('verified');
        return !empty($v);
    }

    public function withdrawalBlocked(): bool
    {
        return $this->getSetting('withdrawal_block') ||
            (lic('isCddEnabled', [], $this) && (
                ($cdd = $this->getSetting('cdd_check')) &&
                ($cdd === static::CDD_REQUESTED || $cdd === static::CDD_INPROGRESS)
                )
            ) ||
            $this->hasSetting('cdd_withdrawal_block');
    }

    /**
     * Sets a Redis key -> value in the correct shard based on user id. This is the same
     * thing as the global phMsetShard() function but as an object method.
     *
     * @see phMsetShard()
     * @uses phMset()
     *
     * @param string $key The key to set data under.
     * @param string|array $value The data / value to store.
     * @param int $expire Expire in seconds.
     *
     * @return null TODO henrik remove the return as phMset() does not return anything.
     */
    function mSet($key, $value, $expire = 36000){
        return phMset(mKey($this, $key), $value, $expire);
    }


    /**
     * Gets a Redis value.
     *
     * @see phMgetShard()
     * @uses phMget()
     *
     * @param string $key The key to get data from>.
     * @param int $expire Expire in seconds (a refresh on access).
     *
     * @return string The data.
     */
    function mGet($key, $expire = 36000){
        return phMget(mKey($this, $key), $expire);
    }


    /**
     * Removes a Redis value.
     *
     * @see phMdelShard()
     * @uses phMdel()
     *
     * @param string $key The key to erase.
     *
     * @return null TODO henrik remove the return as phMdel() does not return anything.
     */
    function mDel($key){
        return phMdel(mKey($this, $key));
    }

    /**
     * Unverifies a user.
     *
     * @return null
     */
    function unVerify()
    {
        $setting = 'verified';
        $this->deleteSetting($setting);
        $this->resetSettingOnRemoteBrand($setting);
        $this->triggerStatusChange();
    }

    /**
     * Blocks a user from making deposits.
     *
     * @return null
     */
    function depositBlock()
    {
        $setting = 'deposit_block';
        $this->setSetting($setting, 1);
        $this->sendSettingToRemoteBrand($setting, '1');
    }

    /**
     * Removes deposit block for the user.
     */
    function resetDepositBlock(): void
    {
        $setting = 'deposit_block';
        $this->deleteSetting($setting);
        $this->resetSettingOnRemoteBrand($setting);
    }


    /**
     * Removes withdrawal block for user.
     */
    function resetWithdrawBlock(): void
    {
        $setting = 'withdrawal_block';
        $this->deleteSetting($setting);
        $this->resetSettingOnRemoteBrand($setting);
    }

    /**
     * Update the CDD status for both local and remote.
     *
     * @param $status
     */
    function updateCDDStatusForLocalAndRemote($status): void
    {
        if (!$this->shouldSyncCDDStatus()) {
            return;
        }

        $setting = 'cdd_check';

        $this->setSetting($setting, $status);
        $this->sendSettingToRemoteBrand($setting, $status);
    }

    /**
     * Trigger a CDD check for the user for the first time
     *
     * @param string $reason The reason for triggering CDD.
     */
    function triggerCDD(string $reason): void
    {
        $cdd_requested = self::CDD_REQUESTED;
        $cdd_setting = 'cdd_check';
        $this->setSetting($cdd_setting, $cdd_requested);
        $this->sendSettingToRemoteBrand($cdd_setting, $cdd_requested);
        $this->logCDDActions($reason);
    }


    /**
     * Check if user passed CDD.
     * @return bool
     */
    function isCDDChecked(): bool
    {
        return $this->hasSetting('cdd_check');
    }

    /**
     * If cdd config is enabled
     * return true
     * @return bool
     */
    function shouldUpdateCDDStatus(): bool
    {
        return lic('isCddEnabled', [], $this);
    }

    /**
     *  Assigns a status (requested, processing, approved), based on the combination
     * of statuses of two documents (POI and POA).
     *
     * The function evaluates the statuses of two documents and determines the combined status according to the
     * following rules:
     *
     * - If both documents have a status of 'requested,' the combined status is 'requested.'
     * - If either document has a status of 'requested' or 'processing,' the combined status is 'requested.'
     * - If either document has a status of 'processing' and the other has a status of 'approved,' the combined status
     *   is 'processing.'
     * - If both documents have a status of 'approved,' the combined status is 'approved.'
     * - In all other cases, the combined status is 'requested.'
     * @param array $status_array
     * @return void
     */
    function assignCDDStatus(array $status_array): void
    {
        if (!$this->shouldSyncCDDStatus()) {
            return;
        }

        // Default to 'requested' if no other condition matches
        $cdd_status = static::CDD_REQUESTED;

        $status_map = [
            'requested-requested' => static::CDD_REQUESTED,
            'requested-processing' => static::CDD_REQUESTED,
            'requested-approved' => static::CDD_REQUESTED,
            'processing-processing' => static::CDD_INPROGRESS,
            'processing-approved' => static::CDD_INPROGRESS,
            'processing-requested' => static::CDD_REQUESTED,
            'approved-processing' => static::CDD_INPROGRESS,
            'approved-approved' => static::CDD_APPROVED,
            'approved-requested' => static::CDD_REQUESTED,
            'approved-rejected' => static::CDD_REQUESTED,
            'rejected-rejected' => static::CDD_REQUESTED
        ];

        sort($status_array);

        $combined_status = implode('-', $status_array);

        // Check if the combination of statuses exists in the map.
        if (array_key_exists($combined_status, $status_map)) {
            $cdd_status = $status_map[$combined_status];
        }


        $this->updateCDDStatusForLocalAndRemote($cdd_status);
    }

    /**
     * Determines if the user is associated with a single brand based on the first deposit data.
     *
     * This method checks the remote system to fetch the first deposit information
     * for the user. If the remote user has a successful first deposit or the result contains data,
     * the user is not considered a single-brand user. Otherwise, they are.
     *
     * @return bool True if the user is single-brand; false otherwise.
     */
    public function isSingleBrandUser(): bool
    {
        $remote = getRemote();
        $remote_first_deposit = toRemote($remote, 'getFirstDeposit', [$this->getRemoteId()]);

        return !($remote_first_deposit['success'] || !empty($remote_first_deposit['result']));
    }


    /**
     * Update the CDD flag based on document status changes.
     *
     * @param bool|null $is_single_brand Optional parameter to determine which function to call.
     *                             Default is false (call cross brand).
     * @return void
     */
    public function updateCDDFlagOnDocumentStatusChange($is_single_brand = null): void
    {
        if(is_null($is_single_brand)){
            $is_single_brand = $this->isSingleBrandUser();
        }

        $method = $is_single_brand
            ? 'updateCDDFlagOnDocumentStatusChangeForSingleBrand'
            : 'updateCDDFlagOnDocumentStatusChangeCrossBrand';

        $this->$method();
    }
    /**
     * Updating the CDD flag whenever the statuses of required documents change for a user.
     *
     * @return void
     */
    function updateCDDFlagOnDocumentStatusChangeCrossBrand(): void
    {
        if (!$this->shouldSyncCDDStatus()) {
            return;
        }

        $documents_to_sync = lic('getDocumentsToSync', [], $this);
        $remote = getRemote();
        $statuses = [];

        foreach ($documents_to_sync as $document) {
            $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($document);
            $status = $this->getSetting($doc_type_setting_name);
            if (empty($status)) {
                $remote_setting = toRemote(
                    $remote,
                    'getRemoteSetting',
                    [$this->getRemoteId(), $doc_type_setting_name]
                );
                if ($remote_setting['success']) {
                    $status = $remote_setting['result'];
                } else {
                    phive('UserHandler')->logAction($this,
                                                    "Couldn't get {$doc_type_setting_name} from {$remote}",
                                                    'cdd_check');
                }
            }
            $statuses[] = $status;

        }

        $this->assignCDDStatus($statuses);
    }

    /**
     * Update the CDD flag based on document status changes for single-brand users.
     *
     * @return void
     */
    public function updateCDDFlagOnDocumentStatusChangeForSingleBrand(){
        $documents_to_sync = lic('getDocumentsToSync', [], $this);
        $documents = Phive('Dmapi')->getUserDocumentsV2($this->getId());
        $document_statuses = [];

        foreach ($documents_to_sync as $tag) {
            $latest_document = linker()->getLatestLocalDocumentFile($documents, $tag);
            $document_statuses[] = $latest_document['status'] ?? null;
        }

        $this->assignCDDStatus($document_statuses);
    }


    /** If user has cdd config enabled and has cdd_check setting
     * return true
     * @return bool
     */
    function shouldSyncCDDStatus(): bool
    {
        return (lic('isCddEnabled', [], $this)) && !empty($this->getRemoteId()) && $this->isCDDChecked();
    }

    function logCDDActions(string $log_msg): void
    {
        phive('UserHandler')->logAction($this,
                                        $log_msg,
                                        "cdd_check");
    }

    /**
     * A jurisdictional status where the player is blocked from making deposits and play games.
     * This typically happens after a test period after which the user gets restricted if he / she fails
     * to KYC verify.
     * @param string $restriction_reason
     *
     * @return bool True if the SQL query was without errors, false otherwise.
     */
    function restrict(string $restriction_reason): bool
    {
        lic('trackUserStatusChanges', [$this, UserStatus::STATUS_RESTRICTED], $this);
        $description = (new DBUserRestriction)->getRestrictionDescription($restriction_reason);
        phive('UserHandler')->logAction($this, "Restriction reason: $description", 'restriction');
        $this->setSetting('restriction_reason', $restriction_reason);
        lic('onUserRestricted', [$this, $restriction_reason], $this);

        return $this->setSetting('restrict', 1);
    }

    /**
     * Checks if a user is restricted.
     *
     * @return bool True if restricted, false otherwise.
     */
    function isRestricted(){
        return (int)$this->getSetting('restrict') === 1;
    }

    /**
     * Removes a restriction and restriction reason (settings).
     *
     * @return bool True if the SQL query was without errors, false otherwise.
     */
    public function unRestrict(): bool
    {
        $user_setting = 'restrict';
        $result = $this->deleteSetting($user_setting);
        if ($this->getSetting('restriction_reason') === DBUserRestriction::CDD_CHECK) {
            $this->deleteSetting('cdd_withdrawal_block');
        }
        $this->deleteSetting('restriction_reason');

        if (!$result) {
            return false;
        }

        $this->triggerStatusChange([$user_setting]);

        return true;
    }

    /**
     * Updates the status of the user if some action performed may have affected the current_status
     *
     * @param array $ignore_user_settings
     * @return void
     */
    public function triggerStatusChange(array $ignore_user_settings = []): void
    {
        if (lic('isEnabledStatusTracking', [], $this)) {
            $status = (string)lic('getAllowedUserStatus', [$this, $ignore_user_settings], $this);

            lic('trackUserStatusChanges', [$this, $status], $this);
        }
    }

    /**
     * Get which type of restriction the player is subject to, for more info see CH19669
     *
     * 1) SDD Fail
     * 2) Deposit / Withdraw > 2k
     * 3) Outdated Docs
     *
     * @return bool|string String if something is missing, false otherwise.
     */
    function getDocumentRestrictionType() {
        if($this->isRestricted()) {
            return 'restrict.msg.verify.documents';
        }

        // check for restriction only if cdd triggered on the user
        if ($this->isCDDChecked()) {
            if ($this->hasSetting('poa_approved')) {
                $poa_approved = $this->getSetting('poa_approved');
                if ($poa_approved === 'expired') {
                    return 'restrict.msg.expired.documents.html';
                }
            }

            if ($this->hasSetting('poi_approved')) {
                $poi_approved = $this->getSetting('poi_approved');
                switch ($poi_approved) {
                    case 'processing':
                        return 'restrict.msg.processing.documents';
                        break;
                    case 'rejected':
                        return 'restrict.msg.outdated.documents';
                        break;
                    case 'expired':
                        return 'restrict.msg.expired.documents.html';
                        break;
                    default:
                        return false;
                }
            }

            //Document expiration date can be updated from admin. Always need to check files not from cache
            phive('Dmapi')->flagDocsForRefresh($this->getId());
            $documents = phive('Dmapi')->getDocuments($this->getId());


            if($documents) {
                foreach($documents as $doc) {
                    // For a cross brand user, this takes care of the scenario
                    // where the user has both document settings and documents in dmapi
                    if ($doc['tag'] === 'idcard-pic' && $this->hasSetting('poi_approved')) {
                        continue;
                    }

                    if ($doc['tag'] === 'addresspic' && $this->hasSetting('poa_approved')) {
                        continue;
                    }

                    if($doc['tag'] === 'idcard-pic') {

                        if ($doc['status'] === 'processing'){
                            return 'restrict.msg.processing.documents';
                        }

                        if ($doc['status'] === 'rejected'){
                            return 'restrict.msg.outdated.documents';
                        }

                        // we skip null to avoid false positive for old users from when expiry_data was not settable value in the BO.
                        if($doc['expiry_date'] <= phive()->today() && $doc['expiry_date'] !== null) {
                            //change also status of a file
                            $lastFile = end($doc['files']);
                            $fileId = $lastFile['id'];

                            phive('Dmapi')->updateFileStatus($this->getId(), $fileId, 'expired', $this->getId());
                            phive('Dmapi')->expireDocument($doc, $this->getId());
                            return 'restrict.msg.outdated.documents';
                        }
                    }

                    if(phive('Config')->isCountryIn('kyc', 'expire-missing-docs', $this->getCountry())){
                        if(in_array($doc['tag'], ['idcard-pic', 'addresspic']) && !empty($doc['expired'])){
                            return 'restrict.msg.expired.documents.html';
                        }
                    }
                }
            }
        }

        // no document verification is required.
        return false;
    }

    /**
     * Checks if a user is allowed to deposit or not.
     *
     * @return bool True if blocked, false otherwise.
     */
    public function isDepositBlocked(): bool
    {
        return (int)$this->getSetting('deposit_block') === 1 ||
            (int)$this->getSetting('restrict') === 1 ||
            $this->hasExceededBalanceLimit() ||
            (lic('isCddEnabled', [], $this) && ($this->getSetting('cdd_check') === static::CDD_REQUESTED)) ||
            !$this->hasCurTc();
    }

    /**
     * @return bool|int
     */
    public function getRemoteId()
    {
        return linker()->getUserRemoteId($this);
    }

    /**
     * A temporary block that gets set when a call to an external KYC service is sent and removed when
     * we get the reply back. If KYC OK at that point nothing happens, if not OK some other block
     * will typically get set.
     *
     * @return bool True if temporary blocked, false otherwise.
     */
    public function isTemporalDepositBlocked(){
        return (int)$this->getSetting('tmp_deposit_block') === 1;
    }

    /**
    * A temporary deposit block that is added during registration that is removed after the
    * cross brand check is over
    *
    * @return bool True if Cross Brand Check Block was added
    */
    public function isCrossBrandCheckBlocked() {
        return (int)$this->getSetting('cross_brand_check_block') === 1;
    }

    /**
     * Whether the account is a temporal one
     *
     * @return bool
     */
    public function isTemporal()
    {
        return (int)$this->getSetting('temporal_account') === 1;
    }

    public function hasCompanyDetails(): bool
    {
        $settings = $this->getSettingsIn(['citizenship', 'company_name', 'company_address', 'company_phone_number']);
        return count($settings) === 4;
    }

    /**
     * Set balance_limit_exceeded setting, required when balance limit is reached.
     */
    public function setBalanceLimitExceeded(): void
    {
        $this->setSetting('balance_limit_exceeded', 1);
    }

    /**
     * Remove balance_limit_exceeded setting
     */
    public function removeBalanceLimitExceeded(): void
    {
        $this->deleteSetting('balance_limit_exceeded');
    }


    /**
     * Set a setting only on the remote.
     *
     * @param string $setting The name of the setting to be set.
     * @param mixed $value The value to be set for the setting.
     *
     * @return void
     */
    public function setDocumentSettingOnlyOnOneBrand($setting, $value): void
    {
        $remote_brand = getRemote();
        $user_id = $this->userId;
        $remote_user_id = linker()->getUserRemoteId($user_id);

        if (!empty($remote_user_id) && !$this->hasSetting($setting) && (in_array($setting, lic('getDocumentsSettingToSetOnlyOnRemote', [], $this)))) {
            $response = toRemote(
                $remote_brand,
                'syncSettingsWithRemoteBrand',
                [$remote_user_id, $setting, $value]
            );
            $result_message = " resulted in " . ($response['success'] === true ? 'true' : 'false');

            phive('UserHandler')->logAction($this,
                                            "Added {$setting} to {$remote_brand} for user {$user_id} {$result_message}. OnDocument change setting",
                                            $setting);
        } else {
            $this->setSetting($setting, $value);
        }
    }

    /**
     * Check if setting balance_limit_exceeded is set for user.
     */
    public function hasExceededBalanceLimit(): bool
    {
        return $this->hasSetting('balance_limit_exceeded');
    }

    public function hasExceededLoginAttempts(): bool
    {
        $failed_logins = $this->getSetting('failed_logins') ?: 0;
        $allowed_attempts = phive('DBUserHandler')->getSetting('login_attempts');

        return $failed_logins >= $allowed_attempts;
    }

    public function hasExceededLoginOtpAttempts(): bool
    {
        $failed_logins = $this->getSetting('failed_login_otp_attempts') ?: 0;
        $allowed_attempts = phive('DBUserHandler')->getSetting('login_otp_attempts');

        return $failed_logins >= $allowed_attempts;
    }

    /**
     * @param $setting
     * @param $value
     * @return bool|void
     */
    public function sendSettingToRemoteBrand($setting, $value)
    {
        $remote_brand = getRemote();
        $user_id = $this->userId;
        $remote_user_id = linker()->getUserRemoteId($user_id);

        if (!empty($remote_user_id) && (in_array($setting, lic('getLicSetting', ['cross_brand'], $this)['sync_settings_with_remote_brand'], true))) {
            $response = toRemote(
                $remote_brand,
                'syncSettingsWithRemoteBrand',
                [$remote_user_id, $setting, $value]
            );

            $result_message = " resulted in " . ($response['success'] === true ? 'true' : 'false');

            phive('UserHandler')->logAction($this,
                                            "Added {$setting} to {$remote_brand} for user {$user_id} {$result_message}",
                                            $setting);

            return $response['success'] === true;
        }
    }

    /**
     * @param string $setting
     * @return bool
     */
    public function resetSettingOnRemoteBrand($setting)
    {
        if (in_array($setting, lic('getLicSetting', ['cross_brand'], $this)['no_sync_settings_after_removal'])) {
            return true;
        }

        $remote_brand = getRemote();
        $user_id = $this->userId;
        $remote_user_id = linker()->getUserRemoteId($user_id);

        if (!empty($remote_user_id) && in_array($setting, lic('getLicSetting', ['cross_brand'], $this)['sync_settings_with_remote_brand'], true)) {
            $response = toRemote(
                $remote_brand,
                'syncResetSettingWithRemoteBrand',
                [$remote_user_id, $setting]
            );
            $result_message = " resulted in " . ($response['success'] === true ? 'true' : 'false');

            phive('UserHandler')->logAction($this,
                                            "Deleting {$setting} from {$remote_brand} for user {$user_id} {$result_message}",
                                            $setting);

            return $response['success'] === true;
        }
    }


    /**
     * Bocks a user from game play.
     *
     * @return null
     */
    function playBlock(){
        $setting = 'play_block';
        $this->setSetting($setting, 1);
        $this->sendSettingToRemoteBrand($setting, '1');
    }

    /**
     * Bocks a user from withdrawing.
     *
     * @return null
     */
    function withdrawBlock()
    {
        $setting = 'withdrawal_block';
        $this->setSetting($setting, '1');
        $this->sendSettingToRemoteBrand($setting, '1');
    }

    /**
     * Set a flag in user for required_limits_not_set
     *
     * @return bool
     */
    public function setRequiredLimitsNotSet()
    {
        return $this->setSetting('required_limits_not_set', 1);
    }

    /**
     * Lift the flag in user for required_limits_not_set
     *
     * @return bool
     */
    public function resetRequiredLimitsFlag()
    {
        return $this->deleteSetting('required_limits_not_set');
    }

    /**
     * Check if the flag required_limits_not_set is set for user
     *
     * @return bool
     */
    public function hasSetRequiredLimits()
    {
        return !$this->hasSetting('required_limits_not_set');
    }

    /**
     * Reset bock for a user from game play.
     */
    public function resetPlayBlock(): void{
        $setting = 'play_block';
        $this->deleteSetting($setting);
        $this->resetSettingOnRemoteBrand($setting);
    }

    /**
     * Checks if a user is play blocked.
     *
     * @return bool True if play blocked, false otherwise.
     */
    public function isPlayBlocked(): bool
    {
        return $this->hasExceededBalanceLimit() ||
            lic('isPlayBlocked', [$this], $this) ||
            (lic('isCddEnabled', [], $this) && ($this->getSetting('cdd_check') === static::CDD_REQUESTED));
    }


    /**
     * Checks if a user is sportbook play blocked.
     *
     * @return bool True if sportsbook play blocked, false otherwise.
     */
    public function isSportsbookPlayBlocked(): bool
    {
        return $this->isPlayBlocked();
    }

    /**
     * Checks if a user is allowed to get bonuses, this check typically happens before we send out a promo
     * email to the user with a bonus offer, if this method returns true we don't send the email.
     *
     * @return bool True if bonus blocked, false otherwise.
     */
    function isBonusBlocked(){
        if(empty($GLOBALS['bonus_limits']))
            $GLOBALS['bonus_limits'] = phive('Config')->getByTagValues('bonus_limits');

        $conf        = $GLOBALS['bonus_limits'];

        // No need for sharding logic here as users_lifetime_stats is being calculated only on the master.
        $uls         = phive('SQL')->loadAssoc("SELECT * FROM users_lifetime_stats WHERE user_id = {$this->getId()}");
        $ratio       = $uls['rewards'] / $uls['bets'];
        $wager_thold = mc($conf['wager_thold'], $this);

        if($uls['bets'] < $wager_thold && (float)$conf['low_wager_bblock_ratio'] < $ratio){
            return true;
        }

        if($uls['bets'] >= $wager_thold && (float)$conf['high_wager_bblock_ratio'] < $ratio){
            return true;
        }

        return false;
    }

    /**
     * Get a list of Bonus IDs the current user has that require to be
     * forfeited before being able to deposit
     *
     * @see SQL::loadAssoc()
     * @see SQL::sh()
     *
     * @return array [int]
     */
    public function getBonusesToForfeitBeforeDeposit(): array {
        $user_id    = intval($this->getId());
        $today      = date('Y-m-d');
        $bonuses    = phive(SQL::class)
            ->sh($user_id, '', 'bonus_entries')
            ->loadAssoc("
                SELECT be.id
                FROM bonus_entries be, bonus_types bt
                WHERE user_id = {$user_id}
                  AND be.bonus_id = bt.id
                  AND (be.status = 'active' OR be.status = 'approved')
                  AND be.start_time <= '{$today}'
                  AND be.end_time >= '{$today}'
                  AND bt.deposit_active_bonus = 1
            ");

        return (is_array($bonuses)) ? $bonuses : [];
    }

    /**
     * Helper function to return the number of days since the account was created
     *
     * @return int
     */
    public function registerSince()
    {
        try {
            return (new \DateTime($this->getAttr('register_date')))->diff(new \DateTime())->days;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Used in order to run different logic if the user is a LIVE test user.
     *
     * @return bool True if it is a test account, false otherwise.
     */
    function isTestAccount() {
        return $this->hasSetting('test_account');
    }

    /**
     * Gets the user's alias, the alias is something we show in social contexts so is not something private
     * or a security risk if displayed.
     *
     * @param bool $decode The alias is stored HTML entity encoded but if the context requires it this parameter
     * controls if the alias should be decoded or not.
     *
     * @return string The alias.
     */
    function getAlias($decode = false){
        if (!empty($this->getSetting('privacy-pinfo-hidealias'))) {
            return 'Anonymous' . base_convert($this->data['id'], 10, 9);
        }
        $alias = empty($this->data['alias']) ? $this->data['firstname'] : $this->data['alias'];
        if($decode)
            return html_entity_decode($alias, ENT_QUOTES);
        return $alias;
    }

    // TODO henrik remove
    function getToggleBlockAction(){
        return $this->isBlocked(true) ? 'activate' : 'block';
    }


    // TODO henrik remove
    function isAdminBlocked(){
        if($this->isBlocked()){
            $unlock = $this->getSetting('unlock-date');
            if(empty($unlock))
                return true;
        }
        return false;
    }

  /**
   * Checks if the user has deposited or not.
   *
   * @return bool True if user has deposited, false otherwise.
   */
    function hasDeposited(){
        return phive()->isEmpty(phive('SQL')->sh($this->userId, '', 'first_deposits')->loadArray("SELECT * FROM first_deposits WHERE user_id = {$this->getId()}")) ? false : true;
    }

    // TODO henrik remove
    function isSimilarName($name) {

    }

    // TODO henrik remove
    function getSegment($month = '') {
        $uh = phive('UserHandler');
        $uh->setVipLevels();
        $month = empty($month) ? phive()->hisMod('-1 month', '', 'Y-m') : $month;

        $uid = $this->getId();
        $str = "
            SELECT bets / cur.multiplier AS bets, deposits / cur.multiplier AS deposits, site_prof / cur.multiplier AS site_prof
            FROM users_monthly_stats AS us
            LEFT JOIN users AS u ON us.user_id = u.id
            LEFT JOIN currencies AS cur ON cur.code = u.currency
            WHERE us.date = '$month' AND us.user_id = $uid";

        $month_stats = phive('SQL')->loadAssoc($str);

        $bets = $month_stats['bets'] / 100;
        $deps = $month_stats['deposits'] / 100;
        $prof = $month_stats['site_prof'] / 100;

        foreach(['betlevels', 'deplevels', 'proflevels'] as $label)
            $$label = $uh->vip_levels[$label];

        $level = array();
        foreach (array(0,1,2,3) as $i => $l) {
            if ($bets >= $betlevels[$i])
                $level['bet'] = $i + 1;
            if ($deps >= $deplevels[$i])
                $level['dep'] = $i + 1;
            if ($prof >= $proflevels[$i])
                $level['prof'] = $i + 1;
        }
        $level = max(array_values($level));

        return array('level'=> $level);
    }

    // TODO henrik remove
    function setSegment($segment) {
        $this->setSetting('segment', $segment);
    }

    // TODO henrik remove
    function getCurrentSegment($default = 1) {
        $segment = $this->getSetting('segment');
        if (empty($segment))
            return $default;
        return $segment;
    }

    /**
     * Concatenates address related columns into a full postal address.
     *
     * @return string The full address.
     */
    function getFullAddress(){
        $str = '';
        foreach(array('address', 'zipcode', 'city', 'country') as $f)
            $str .= $this->getAttr($f).", ";
        return trim($str, ' ,');
    }

  /**
   * Gets the user's preferred language (ISO2 language code, ex: sv for Sweden).
   *
   * @return string The language.v
   */
    function getLang(){
        $lang = $this->getAttr('preferred_lang');
        if(empty($lang))
            return phive('Localizer')->getDefaultLanguage();
        return $lang;
    }

    /**
     * Tries to first get an attribute with the help of the key, if the attribute does NOT exist we
     * try and return a setting with the same name.
     *
     * Note that we're testing for NULL here, so if we have an empty attribute it will be returned.
     *
     * @param $key The attribute field name / setting alias.
     *
     * @return mixed The value.
     */
    function getAttrOrSetting($key){
        return $this->getAttr($key) ?? $this->getSetting($key);
    }

    /**
     * Gets the user's country.
     *
     * @return string The ISO2 country code.
     */
    function getCountry(){
        return $this->getAttr('country');
    }

    /**
     * @return string
     */
    public function getCity():string {
        return $this->getAttr('city') ?? '';
    }

    /**
     * @return string
     */
    public function getAddress():string {
        return $this->getAttr('address') ?? '';
    }


    /**
     * @return string
     */
    public function getZipcode():string {
        return $this->getAttr('zipcode') ?? '';
    }


    /**
     * Get players main province. String is generated based on country eg 'CA-ON'
     *
     * @return mixed String if user has main province, false otherwise.
     */
    function getProvince(){
        $province = $this->getSetting('main_province');
        return !empty($province) ? $this->getCountry() . "-" . $province : false;
    }

  /**
   * Gets the user's currency.
   *
   * @return string The ISO3 currency code.
   */
    function getCurrency(){
        return $this->getAttr('currency');
    }

    /**
     * Gets the user's province.
     *
     * @return string The user's province abbreviation code. Ex.Ontario (ON). If province does not exist then return empty string.
     */
    function getMainProvince(){
        return $this->getSetting('main_province') ?: '';
    }

    public function getDataByName(string $name)
    {
        if ($name === 'main_province') {
            $provinceAcronym = $this->getSetting('main_province');

            $residenceProvinceList = lic('getProvinces', [], $this);

            foreach ($residenceProvinceList as $acronym => $name) {
                if ($acronym === $provinceAcronym) {
                    return $acronym;
                }
            }

            return null;
        }

        if ($name === 'building') {
            return (($this->getSetting('building') !== false) ? $this->getSetting('building') : null);
        }

        return $this->getAttr($name);
    }

    // TODO henrik remove
    function setSiteLanguage(){
        phive('Localizer')->setLanguage($this->getAttr('preferred_lang'), true);
    }

    /**
     * Description
     *
     * @param $pwd
     * @param $force_update=false
     *
     * @return xxx
     */
    function setPassword($pwd, $force_update = false){
        parent::setPassword($pwd);
        if($force_update)
            $this->setSetting('pwd_changed', 'yes');
        return true;
    }

    // TODO henrik remove
    function deletePic($pic){
        $this->deleteSettingByValue($pic);
        $base = phive('Filer')->getSetting('UPLOAD_PATH');
        unlink($base."/user-files/$pic");
    }

    // TODO henrik remove and all invocations
    function rejectPic($pic){
        $this->deleteSetting($pic, '');
        $actor = cu();
        if(is_object($actor) && $actor != $this)
            phive('UserHandler')->logAction($this, $actor->getUsername()." rejected id pic $pic", "rejected_idpic");
        $base = phive('Filer')->getSetting('UPLOAD_PATH');
        unlink($base."/user-files/$pic");
    }

    /**
     * Simple check of date of birth.
     *
     * @link https://www.php.net/manual/en/datetime.format.php
     *
     * @param string $dob The date of birth in Y-m-d (ex: 1976-01-31) format.
     *
     * @return bool True if the passed in DOB is the same as the one in the user row, false otherwise.
     */
    function checkDob($dob){
        return $this->getAttribute('dob') == $dob;
    }

    /**
     * Gets the mobile number column value from the users row.
     *
     * @return string The mobile number.
     */
    function getMobile(){
        return $this->getAttr('mobile');
    }

    /**
     * @param string $secret_question
     * @param string $secret_answer
     * @return bool
     */
    public function checkSecretAnswer(string $secret_question, string $secret_answer): bool
    {
        return $this->getSetting('security_question') == $secret_question
            && $this->getSetting('security_answer') == $secret_answer;
    }

    /**
     * Checks if the user's country is in a contry config, ie if the config value of a list of ISO2 codes
     * contains the user's country.
     *
     * @uses Config::getValue()
     *
     * @param string $name The config name.
     * @param string $tag The config tag.
     *
     * @return bool True if the country is in the config, false otherwise.
     */
    function checkCountryConfig($name, $tag){
        $countries = phive('Config')->getValue($tag, $name);
        $country = $this->getAttribute('country');
        return strpos(strtolower($countries), strtolower($country)) !== false;
    }

    // TODO henrik remove
    public function deleteSettingByValue($val){
        $setting = $this->getSettingByValue($val);
        if(!empty($setting)){
            $actor = cu();
            if(is_object($actor) && $actor != $this)
                phive('UserHandler')->logAction($this, $actor->getUsername()." removed {$setting['setting']} with value $val", "deleted_setting");
            return parent::deleteSettingByValue($val);
        }
    }

    /**
     * Deletes a setting
     *
     * @uses User::deleteSetting()
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if passed in we use it instead of the user id.
     * @param bool $read_from_master Optional, use master DB
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    function deleteSetting($setting, $id = '', $read_from_master = true){
        $actor = cu();
        $has_setting = $this->hasSetting($setting, $id, $read_from_master);
        if (!$has_setting) {
            return false;
        }
        $this->logActionOnSettingChange('removed', $setting, null, $actor);
        return parent::deleteSetting($setting, $id);
    }

    // TODO henrik remove
    function hasAlias(){
        $alias = $this->getAttribute('alias');
        return !empty($alias);
    }

    // TODO henrik remove
    function getPlayBalance($bonus_cash = true){
        $cash_balance = $this->getBalance('cash_balance', true);
        if($bonus_cash)
            $cash_balance += phive('Bonuses')->getBalanceByUser($this);
        return $cash_balance;
    }

    /**
     * Sets a user attribute, ie column value in the users row.
     *
     * @param string $attribute The column name.
     * @param mixed $value The value to set.
     * @param bool $noescape TODO henrik remove, cehck all invocations.
     * @param string $extra Extra information that we want to add in the comments
     *
     * @return bool True if no errors in executing the query, false otherwise.
     */
    public function setAttribute($attribute, $value, $noescape = false, $extra = ''): bool
    {
        $actor = cu();
        $log_value = $extra ? "$value [$extra]" : $value;

        if (is_object($actor) && $actor !== $this) {
            $comment = $actor->getUsername() . " set $attribute to $log_value";
        } else if (isCli()) {
            $comment = "Cron set $attribute to $log_value";
        } else {
            $comment = $this->getUsername() . " set $attribute to $log_value";
        }

        if (!empty($comment)) {
            phive('DBUserHandler')->logAction($this, $comment, $attribute);
        }

        return parent::setAttribute($attribute, $value, $noescape);
    }

    /**
     * Unsubscribes from promo emails.
     *
     * @return null
     */
    function unsubscribe(){$this->setSetting("generic_communications", 0);}

    /**
     * Subscribes to promo emails.
     *
     * @return null
     */
    function subscribe(){$this->setSetting("generic_communications", 1);}

    /**
     * The users.active column controls if a user is considered blocked or not, if it is
     * 0 the user can't login.
     *
     * @return null
     */
    function block(){
        $this->setAttribute('active', 0);
        lic('trackUserStatusChanges', [$this, UserStatus::STATUS_BLOCKED], $this);
        phive('UserHandler')->logoutUser($this->getId());
    }

    /**
     * A super block is a normal block in combination with a setting which controls who can unblock
     * or not in the BO, only people with a certain permission can unlock super blocked users.
     *
     * @param $zero_out TODO henrik remove and the associated conditional.
     * @param bool $update_status - by default we update user status, but there are some cases (Ex. self-exclusion) where we want to log a different status, even if the player is superblocked.
     */
    function superBlock($zero_out = true, $update_status = true, $log_out = true)
    {
        $this->unsubscribe();
        $this->setAttribute('active', 0);
        $this->setSetting("super-blocked", 1);
        $this->deleteSettings('lock-hours', 'unlock-date');

        if($zero_out){
            $cash_balance = $this->getAttr('cash_balance');
            if(!empty($cash_balance))
                phive('QuickFire')->changeBalance($this, -$cash_balance, "Super blocked cash balance zeroed.", 15);
        }
        if($update_status) {
            lic('trackUserStatusChanges', [$this, UserStatus::STATUS_SUPERBLOCKED], $this);
        }
        if ($log_out) {
            phive('UserHandler')->logoutUser($this->getId());
        }
    }

    /**
     * Perform a remote super block action for a user.
     *
     * @param bool $zero_out
     * @param bool $update_status
     * @return bool Whether the super block action was successful.
     */
    public function superBlockRemote($zero_out = true, $update_status = true, $log_out = true)
    {
        $user_id= $this->getId();
        $remote_brand = getRemote();
        $remote_user_id = $this->getRemoteId();

        if (in_array(__FUNCTION__, lic('getLicSetting', ['cross_brand'], $this)['methods_to_sync'], true)) {
            try {
                $response = toRemote(
                    $remote_brand,
                    'superBlock',
                    [$remote_user_id, $zero_out, $update_status, $log_out]
                );

                $success = $response['success'] ?? false;
                $result_message = $success ? 'true' : 'false';

                phive('UserHandler')->logAction(
                    $this,
                    "Added Super block to {$remote_brand} for user {$user_id} resulted in {$result_message}",
                    'super-blocked'
                );

                return $success;
            } catch (Exception $e) {
                error_log("Error in superBlockRemote: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Checks if the user is super blocked or not.
     *
     * @return bool True if super blocked, false otherwise.
     */
    function isSuperBlocked(){
        $block = $this->getSetting('super-blocked');
        return !empty($block);
    }

    /**
     * Sets the version of the T&Cs that the user just agreed to, any potential bocks due to not accepting
     * the T&Cs are removed. Note that the lic() function is used in order to support different kinds of
     * T&Cs depending on the jurisdiction the user lives in.
     *
     * @return null
     */
    function setTcVersion(){
        $this->deleteSetting('tac_block');
        $this->setSetting('tc-version', lic('getTermsAndConditionVersion'));
    }

    /**
     * Sets the version of the Bonus T&Cs that the user just agreed to
     *
     * @return void
     */
    public function setBtcVersion(): void
    {
        if (lic('hasBonusTermsConditions')) {
            $btc_version = lic('getBonusTermsAndConditionVersion');

            if ($btc_version) {
                $this->deleteSetting('bonus_tac_block');
                $this->setSetting('bonus-tc-version', $btc_version);
            }
        }
    }

    /**
     * Sets the version of the T&Cs for the sport that the user just agreed to, any potential bocks due to not accepting
     * the T&Cs are removed. Note that the lic() function is used in order to support different kinds of
     * T&Cs depending on the jurisdiction the user lives in.
     *
     * @return void
     */
    public function setSportTcVersion(): void
    {
        $this->deleteSetting('tac_block_sports');
        $this->setSetting('tc-version-sports', lic('getTermsAndConditionVersion', ['sports']));
    }

    /**
     * Checks if the user has accepted the latest version of the T&C.
     *
     * @return bool True if yes, false if no.
     */
    function hasCurTc(){
        return $this->getSetting('tc-version') == lic('getTermsAndConditionVersion');
    }

    /**
     * Checks if the user has accepted the latest version of the Bonus T&C.
     *
     * @return bool
     */
    public function hasCurBtc(): bool
    {
        return $this->getSetting('bonus-tc-version') == lic('getBonusTermsAndConditionVersion');
    }

    /**
     * Checks if the user has accepted the latest version of the T&C for sports.
     *
     * @return bool True if yes, false if no.
     */
    function hasCurTcSports(){
        return $this->getSetting('tc-version-sports') === lic('getTermsAndConditionVersion', ['sports']);
    }

    /**
     * Sets the current version of the privacy policy for the player.
     *
     * @return void
     */
    function setPpVersion()
    {
        $this->setSetting('pp-version', lic('getPrivacyPolicyVersion'));
    }


    /**
     * Checks if the user has the current version of the privacy policy.
     *
     * @return bool
     */
    function hasCurPp()
    {
        return $this->getSetting('pp-version') == lic('getPrivacyPolicyVersion');
    }

    public function setAttrsToSession(): void
    {
        $_SESSION['local_usr'] = $this->data;
    }

    /**
     * We check if the setting from at least one of the providers exist. (we don't mind the result in this case)
     *
     * @return bool Will return TRUE if the user DOB verification was already done once.
     */
    public function hasDoneAgeVerification()
    {
        return $this->hasSetting('id3global_res') || $this->hasSetting('experian_res');
    }

    /**
     * Will return TRUE if one of the providers has returned a successful value.
     *
     * @return bool
     */
    public function isAgeVerified()
    {
        return (int)$this->getSetting('id3global_res') === 1 || (int)$this->hasSetting('experian_res') === 1;
    }

    /**
     * THIS IS A PORT OF ADMIN2 "isPEPOrSanction"
     *
     * We check if the results returned by the providers we use for "checkPEPSanctions" during registration / recurrent
     * check are not valid, this mean we have a PEP/SL user.
     *
     * Valid values are:
     * - for id3global: "PASS"
     * - for acuris: "PASS" or "NO MATCH"
     *
     * Fail scenario are if:
     * - BOTH failed
     * - 1 failed but the other users_settings doesn't exist
     *
     * IMPORTANT: if one fail but the other one is passing the user will not be blocked.
     *
     * @return bool False if PEP or otherwise KYC issues, true otherwise.
     */
    public function isPepOrSanction()
    {
        $id3_setting_exist = $this->hasSetting('id3global_pep_res');
        $id3_setting_value = $this->getSetting('id3global_pep_res');
        $acuris_setting_exist = $this->hasSetting('acuris_pep_res');
        $acuris_setting_value = $this->getSetting('acuris_pep_res');

        $id3_is_pep = $id3_setting_exist && $id3_setting_value !== 'PASS';
        $acuris_is_pep = $acuris_setting_exist && !in_array($acuris_setting_value, ['PASS', 'NO MATCH']);

        return ($id3_is_pep && $acuris_is_pep)
            || ($id3_is_pep && !$id3_setting_exist)
            || ($acuris_is_pep && !$acuris_setting_exist);
    }

    function hasComment($tag, $text): bool
    {
        $str = "SELECT 1 FROM users_comments WHERE user_id = {$this->userId} AND tag='{$tag}' AND comment LIKE '%{$text}%' LIMIT 1";
        return !empty(phive('SQL')->sh($this->userId, '', 'users_comments')->loadArray($str, 'ASSOC'));
    }

    function hasCommentCurrentMonth($tag, $text): bool
    {
        $str = "SELECT
                    1
                FROM
                    users_comments
                WHERE
                    user_id = {$this->userId}
                    AND tag = '{$tag}'
                    AND comment LIKE '%{$text}%'
                    AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m')
                LIMIT 1";
        return !empty(phive('SQL')->sh($this->userId, '', 'users_comments')->loadArray($str, 'ASSOC'));
    }

    /**
     * Adds a comment to the user, the comments are used internally in order for admins to keep track of
     * eg misc actions that have been taken with regards to the user.
     *
     * @param string $comment The comment.
     * @param int $sticky Sticky (1) or not (0), if sticky the comment will display above of non sticky comments.
     * @param string $tag A tag / grouping for the comment that can be used in SELECTs, if foreign id is passed this needs to contain
     * the foreign table name in order to be able to subsequently JOIN on with the help of the foreign info.
     * @param int $foreign_id An id of a related table row in a different table.
     * @param string $foreign_id_name The column name (or alias) typically used in SELECTs in order to get the foreign data.
     *
     * @return int The id of the inserted comment.
     */
    function addComment($comment, $sticky = 0, $tag = '', $foreign_id = '', $foreign_id_name = 'id'){
        phive("UserHandler")->logAction(
            $this,
            "added a comment: ".var_export($comment, true),
            "comment",
            true, $this->cur_user);
        return phive('SQL')->sh($this->userId, '', 'users_comments')->insertArray('users_comments', array(
            'user_id'         => $this->getId(),
            'comment'         => $comment,
            'sticky'          => $sticky,
            'tag'             => $tag,
            'foreign_id'      => $foreign_id,
            'foreign_id_name' => $foreign_id_name
        ));
    }

  // TODO henrik remove
  function extractNid(){
    $country         = $this->getAttr('country');
    $extra           = $this->getSetting('nid_extra');
    if(empty($extra))
      return '';
    list($y, $m, $d) = explode('-', $this->getAttr('dob'));
    switch($country){
      case 'SE':
        return $y.$m.$d.$extra;
        break;
      case 'FI':
        $delim = $y >= 2000 ? 'A' : '-';
        $y = substr($y, -2, 2);
        return $d.$m.$y.$delim.$extra;
        break;
    }
    return '';
  }

  // TODO henrik delete
  function deleteComment($comment_id){
    $comment_id = intval($comment_id);
    return phive('SQL')->sh($this->userId, '', 'users_comments')->query("DELETE FROM users_comments WHERE `id`= $comment_id");
  }

    /**
     * Gets a user setting and if the the setting doesn't exist on the user we get a value from the config table instead.
     *
     * @uses Config::getValue()
     *
     * @param string $setting The user setting.
     * @param string $gtag The config tag.
     * @param string $gname The config name.
     *
     * @return mixed The setting or config value.
     */
    function getSettingOrGlobal($setting, $gtag, $gname = ''){
        if(empty($gname))
            $gname = $setting;
        $default = phive("Config")->getValue($gtag, $gname);
        return $this->getSettingOrDefault($setting, $default);
    }


    /**
     * Gets a user setting and if the the setting doesn't exist on the user we return the passed in $default value instead.
     *
     * @param string $setting The user setting.
     * @param mixed $default The default value.
     *
     * @return mixed The setting or default value.
     */
    function getSettingOrDefault($setting, $default){
        $setting = $this->getSetting($setting);
        if(empty($setting))
            return $default;
        return $setting;
    }

    /**
     * Gets the user's loyalty deal in percent.
     *
     * @return float The number to multiply each bet with.
     */
    function getLoyaltyDeal(){
        return $this->getSettingOrGlobal('casino_loyalty_percent', 'vip', 'casino-loyalty-percent');
    }

    // TODO henrik not currently used, remove?
    function getSp(){
        return $this->getSetting('skill_points');
    }

    // TODO henrik not currently used, remove?
    function incSp($amount){
        return $this->incSetting('skill_points', $amount);
    }

    /**
     * Generates and returns a URL to the user's account page, or BO page if indicated.
     *
     * @param string $acc_page The sub page, eg **bonuses**.
     * @param bool $bo If true we instead display the main page of the users account page in the BO.
     *
     * @return string The URL.
     */
    public function accUrl($acc_page = '', $bo = false)
    {
        if ($bo == true) {
            return phive('UserHandler')->getBOAccountUrl($this->getId());
        } else {
            return phive('DBUserHandler')->getUserAccountUrl($acc_page, $this->getLang(), false, $this->getId());
        }
    }

    /**
     * KYC verifies a user.
     *
     * We set users.verified to 1, delete any third party auto verification failure blocks
     * and unrestrict the user, mail the user and finally hand out a trophy.
     *
     * @return null
     */
    function verify(){
        $old_value = $this->getSetting('verified');
        $this->setSetting("verified", 1, true);
        $this->sendSettingToRemoteBrand("verified", '1');
        $this->deleteSetting('experian_block');
        $this->deleteSetting('temporal_account');
        $this->unRestrict();
        lic('onVerify',[$this, $old_value], $this);
        if(!empty($old_value))
            return;
        if(phive()->moduleExists("MailHandler2"))
            phive("MailHandler2")->sendMail('account.verified', $this);
        if(phive()->moduleExists('Trophy'))
            phive('Trophy')->onEvent('verify', $this);
    }

    /**
     * Sends PEP Check Failure block - experian_block to remote brand in case it is configured for the jurisdiction.
     *
     * @return bool|void
     */
    public function sendPEPFailureBlockToRemoteBrand()
    {
        $remote_brand = getRemote();
        $user_id = $this->getId();
        $remote_user_id = $this->getRemoteId();
        $setting = 'experian_block';

        if (
            !empty($remote_user_id)  &&
            in_array($setting, lic('getLicSetting', ['cross_brand'], $this)['sync_settings_with_remote_brand'], true)
        ) {
            $response = toRemote(
                $remote_brand,
                'syncPEPFailureBlockWithRemoteBrand',
                [$remote_user_id]
            );

            $result_message = " resulted in " . ($response['success'] === true ? 'true' : 'false');

            phive('UserHandler')->logAction(
                $this,
                "Added PEP block - {$setting} to {$remote_brand} for user {$user_id} {$result_message}",
                $setting
            );

            return $response['success'] === true;
        }
    }

    /**
     * Just a wrapper around getSetting() with a json_decode() tacked on, used for fetching arrays
     * from settings.
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if passed we get the setting with that id instead of the user_id.
     * @param bool $strip_slashes Whether or not to run stripslashes() on the return.
     *
     * @return mixed The value.
     */
    public function getJsonSetting($setting, $id = '', $strip_slashes = false){
        $setting = $this->getSetting($setting, $id, $strip_slashes);
        if(empty($setting)){
            // null or similar, we just return to avoid errors with json_decode trying to parse that
            return [];
        }
        return json_decode($setting, true);
    }


    /**
     * This is just setSetting() but with a json_encode() on the value to save before storing,
     * used for storing arrays in settings.
     *
     * @uses DBUser::setSetting()
     *
     * @param string $setting The setting.
     * @param mixed $value The value to set.
     * @param bool $log_action Whether or not to log an action.
     * @param int $actor_id The user id of the admin in case the setting was set from the BO.
     * @param string $tag Action tag.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    public function setJsonSetting($setting, $value, $log_action = false, $actor_id = 0, $tag = ''){
        return $this->setSetting($setting, json_encode($value), $log_action, $actor_id, $tag);
    }

    /**
     * Sets a setting.
     *
     * @uses DBUserHandler::logAction()
     *
     * @param string $setting The setting.
     * @param mixed $value The value to set.
     * @param bool $log_action Whether or not to log an action.
     * @param int|DBUser $actor_id The user id of the admin in case the setting was set from the BO.
     * @param string $tag Action tag.
     * @param bool $isTimestamp to save timestamp, default false
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    public function setSetting(string $setting, $value, bool $log_action = true, $actor_id = 0, string $tag = '', bool $isTimestamp = false)
    {
        if ($log_action) {
            $this->logActionOnSettingChange('set', $setting, $value, $actor_id, $tag);
        }

        if (phive('Optimove') && phive('Optimove')->isEnabled() && phive('Optimove')->isPrivacySetting($setting)) {
            phive('Optimove')->processPrivacySettings($value);
        }

        return parent::setSetting($setting, $value, $isTimestamp);
    }

    /**
     * Sets a user setting if it doesn't exist.
     *
     * @param string $setting The setting key.
     * @param string $value The setting value.
     *
     * @return null
     */
    public function setMissingSetting($setting, $value){
        if(!$this->hasSetting($setting)){
            $this->setSetting($setting, $value);
        }
    }

    /**
     * A simple wrapper to update the "created_at" timestamp on a setting when is getting updated.
     * it will delete and recreate the setting.
     *
     * @param string $setting The setting.
     * @param mixed $value The value to set.
     * @param bool $log_action Whether or not to log an action.
     * @return null
     */
    public function refreshSetting(string $setting, $value, $log_action = true)
    {
        if (!empty($log_action)) {
            $this->logActionOnSettingChange('updated', $setting, $value);
        }
        return parent::setSetting($setting, $value, true);
    }

    /**
     * Common function to handle logging into actions table logic on users_settings modification.
     * Action string naming is used to keep consistency with previous logging description.
     *
     * @param string $action - set|removed|updated - Ex. setSetting => "set", deletedSetting => "removed", refreshSetting => "updated
     * @param string $setting - setting name
     * @param mixed $value - setting value
     * @param int|DBUser $actor_id - who performed the action, provided only if the actor is not the user.
     * @param string $tag - action table tag, can be overridden on demand
     */
    private function logActionOnSettingChange(string $action, string $setting, $value = '', $actor_id = 0, string $tag = '')
    {
        $default_tag = $setting;
        $update_if_different_actor = true;
        switch ($action) {
            case 'set':
                // we set a new value from a different actor only if the value has changed
                $update_if_different_actor = $this->getSetting($setting) != $value;
                break;
            case 'removed':
                $default_tag = isCli() ? "deleted_$setting" : "deleted_setting";
                break;
            case 'updated': // refresh
                $default_tag = "update_$setting";
                break;
        }
        $tag = empty($tag) ? $default_tag : $tag;
        $actor = cu($actor_id);

        if (is_object($actor) && $actor !== $this && $update_if_different_actor) {
            phive('UserHandler')->logAction($this, $actor->getUsername() . " $action $setting to $value", $tag);
        } elseif (isCli()) {
            phive('UserHandler')->logAction($this, "Cron $action $setting to $value", $tag);
        } else {
            phive('UserHandler')->logAction($this, $this->getUsername() . " $action $setting to $value", $tag);
        }
    }

    // TODO henrik remove and remove invocations.
    function hasUnpaidInvoices(){
        $base_cur = phive('Currencer')->getSetting('base_currency');
        $cs = phive('Config')->getByTag('pending-deposits-thresholds');
        $str = "SELECT *, SUM(amount) AS amount_sum FROM deposits
                WHERE dep_type IN('smsbill', 'sofort')
                AND user_id = {$this->getId()}
                AND status IN('pending', 'disapproved')
                GROUP BY user_id";
        $deposits = phive('SQL')->sh($this->userId, '', 'deposits')->loadArray($str, 'ASSOC', 'dep_type');
        foreach($deposits as $provider => $d){
            if($cs[$provider]['config_value'] < mc($d['amount_sum'], $this))
                return true;
        }
        return false;
    }

    /**
     * Sets a national identification number in the users.nid column.
     *
     * @param string $nid The NID.
     *
     * @return bool False if the update didn't execute (perhaps because of duplicate), true otherwise.
     */
    function setNid($nid){
        if(in_array($this->getCountry(), ['SE', 'DK'])){
            // Just numbers
            $nid = phive()->rmNonNums($nid);
        } else {
            $nid = phive()->rmWhiteSpace($nid);
        }
        $str     = "SELECT * FROM users WHERE id != {$this->getId()} AND nid = '$nid' AND country = '{$this->getCountry()}' LIMIT 1";
        $other   = phive('SQL')->shs()->loadAssoc($str);
        if(!empty($other)){
            $descr = "Tried to set NID to $nid but already taken by: ".$other['id'];
            phive('UserHandler')->logAction($this, $descr, 'duplicate_nid');
            $this->addComment($descr);
            return false;
        }
        $this->setAttr('nid', $nid);
        return true;
    }

    /**
     * Getter for users.nid.
     *
     * @return string The NID.
     */
    function getNid(){
        return $this->getAttr('nid');
    }

    /**
     * Simple wrapper for hasAttr('nid').
     *
     * @return bool True if the NID exists, false otherwise.
     */
    function hasNid(){
        return $this->hasAttr('nid');
    }

    /**
     * Gets game categories that are locked due to RG (Responsible Gambling) limits.
     *
     * @uses RgLimits::getByTypeUser()
     *
     * @return array The game categories.
     */
    function getRgLockedGames()
    {
        $limits = rgLimits()->getByTypeUser($this, ['lockgamescat']);
        $rg_limits = [];

        foreach ($limits as $limit) {
            $rg_limits = array_merge($rg_limits, explode("|", $limit['extra']));
        }

        return array_filter($rg_limits);
    }

    /**
     * Gets game categories and datetime period, that are locked due to RG (Responsible Gambling) limits.
     *
     * @uses RgLimits::getByTypeUser()
     *
     * @return array The game categories.
     */
    public function getRgLockedGamesAndPeriod(): array
    {
        $limits = rgLimits()->getByTypeUser($this, ['lockgamescat']);
        $rg_limits = [];

        foreach ($limits as $limit) {
            $rg_limits[$limit['extra']] = $limit['changes_at'];
        }

        return array_filter($rg_limits);
    }

    /**
     * We check if the requested game "tag" is in the list of gamebreak24 locked games
     * or if the user has selected "all_categories".
     *
     * @param $game_tag
     * @return bool
     */
    public function isGameLocked($game_tag)
    {
        $locked_games = $this->getRgLockedGames();
        return in_array($game_tag, $locked_games) || in_array('all_categories', $locked_games);
    }

    /**
     * We deal with categories from the MENU, so we need to add the other missing "game tag" when we lock a category.
     *
     * @param $categories
     * @return array
     */
    public function expandLockedGamesCategories($categories)
    {
        if (in_array("live-casino", $categories)) {
            array_push($categories, "table");
        }
        if (in_array("jackpots", $categories)) {
            array_push($categories, "videoslots_jackpot");
            array_push($categories, "slots_jackpot");
        }
        return array_filter($categories);
    }

    /**
     * We need to extract the MENU category from the "game tag".
     *
     * @param $game_tag
     * @return array
     */
    public function extractCategoryFromLockedGame($game_tag)
    {
        if (in_array($game_tag, ['videoslots_jackpot', 'slots_jackpot'])) {
            $game_tag = 'jackpots';
        }
        if (in_array($game_tag, ['table'])) {
            $game_tag = 'live-casino';
        }
        $categories = lic('getGamebreak24Categories');
        $game_tag = array_filter($categories, function ($category) use ($game_tag) {
            return $category['alias'] === $game_tag;
        });
        return array_values($game_tag);
    }

    /**
     * Get game categories what need to be extracted and not visibly in UI menu
     *
     * @return array
     */
    public function getExtractedCategoriesFromVisibleMenu(): array
    {
        return ['videoslots_jackpot', 'slots_jackpot', 'table'];
    }

    /**
     * Checking categories and theirs time period to save them with proper period of time
     *
     * @param $locked_categories_and_period array Categories and theirs period from DB
     * @param $checked_categories           array Checked categories from UI
     * @param $unchecked_categories         array Unchecked categories from UI
     */
    public function savingCategoriesRegardingToTimePeriod(
        array $locked_categories_and_period,
        array $checked_categories,
        array $unchecked_categories
    ) {
        /* We checking 'checked' categories from UI and compare these with from DB and
        saving/re-saving these to DB regarding to 'indefinite' block time period
        here is $num_days = 36500 days to be like `indefinite` (period == 0) */
        if (!empty($checked_categories)) {
            $num_days = 36500;
            foreach ($checked_categories as $checked_category) {
                foreach ($locked_categories_and_period as $category => $period) {
                    if ($checked_category === $category && $period != 0) {
                        rgLimits()->removeRgLimitByTypeAndExtra($this, 'lockgamescat', $category);
                    }
                    /* 'all_categories' not category itself, just for UI */
                    if ($checked_category === 'all_categories') {
                        continue;
                    }
                    rgLimits()->addLimit($this,'lockgamescat', 'na', $num_days, $checked_category, true);
                }
            }
        }

        /* We checking 'unchecked' categories from UI and compare these with from DB and
        re-saving these to DB regarding to `cooloff`($num_days) block time period */
        if (!empty($unchecked_categories)) {
            $num_days = lic('getLicSetting', ['gamebreak_indefinite_cool_off_period']);
            foreach ($unchecked_categories as $unchecked_category) {
                foreach ($locked_categories_and_period as $category => $period) {
                    if ($unchecked_category === $category && $period == 0) {
                        rgLimits()->removeRgLimitByTypeAndExtra($this, 'lockgamescat', $category);
                        if ($unchecked_category === 'all_categories') {
                            continue;
                        }
                        rgLimits()->addLimit($this, 'lockgamescat', 'na', $num_days, $unchecked_category, true);
                    }
                }
            }
        }
    }

    /**
     * Return the comment tag
     *  - refers to BO user comments (from admin2 getCommentTagName)
     *
     * @param $type - value in [RG/AML/FR]
     * @return string
     */
    public function getCommentTagByType($type) {
        return strtolower($type) . "-risk-group";
    }

    /**
     * Generate the OTP code which will be sent via SMS and EMAIL.
     * The setting need to be REFRESHED, otherwise the TTL check will fail.
     *
     * @return int
     */
    public function generateOtpCode()
    {
        $ttl = phive('DBUserHandler')->getSetting('otp_ttl', 15); // minutes
        $setting = $this->getWholeSetting('otp_code');
        if (!empty($setting) && $setting['created_at'] >= phive()->hisMod("- $ttl minutes") ) {
            return $setting['value'];
        }
        $code = rand(100000, 999999);
        $this->refreshSetting('otp_code', $code);
        return $code;
    }

    /**
     * Check if the user provided OTP code is valid and not expired.
     *
     * @param $code
     * @return bool
     */
    public function validateOtpCode($code)
    {
        $setting = $this->getWholeSetting('otp_code');
        $ttl = phive('DBUserHandler')->getSetting('otp_ttl', 15); // minutes
        if(empty($setting)) {
            phive('UserHandler')->logAction($this, "No OTP was requested on this account", 'otp-fail');
            return false;
        }
        if ($this->hasSettingExpired('otp_code', $ttl, 'minutes')) {
            phive('UserHandler')->logAction($this, "OTP expired after {$ttl} minutes", 'otp-fail');
            return false;
        }
        if ($setting['value'] == $code) {
            phive('UserHandler')->logAction($this, "OTP verified successfully", 'otp-valid');
            return true;
        } else {
            phive('UserHandler')->logAction($this, "OTP provided ({$code}) is not the one received ({$setting['value']})", 'otp-fail');
            return false;
        }
    }

    /**
     * Saving in Redis an event to send to Google, pixel companies or affiliates companies
     *
     * @param $key
     * @param array $params
     */
    public function setTrackingEvent($key, array $params = [])
    {
        phive('DBUserHandler')->logAction($this->userId, json_encode($params), 'setTrackingEvent');
        if (isset($params['model']) && isset($params['model_id'])) {
            $this->setFirstPartyData($key, $params);
        }

        if (!isExternalTrackingEnabled()) {
            return;
        }

        $event = phMgetShard($key, $this->userId);
        if (empty($event) && phive()->getSetting('enable_logs_in_google_events')) {
            phive('Logger')->getLogger('google-analytics')->info($key, ['tag'=>"google-analytics-start-event-{$key}", 'user_id'=>$this->getId()]);
        }

        if (!isset($params['analytics']) && !isset($params['pixel']) && !isset($params['pr_room'])) {
            $params['analytics'] = false;
            $params['pixel'] = false;
            $params['pr_room'] = false;
        }
        phMsetShard($key, $params, $this->userId);

        if (phive()->getSetting('enable_logs_in_google_events')) {
            if (empty($event)) {
                phive('Logger')->getLogger('google-analytics')->info($key, ['tag'=>"google-analytics-set-in-redis-{$key}", 'user_id'=>$this->getId()]);
                phive('Logger')->getLogger('google-analytics')->info(phMgetShard($key, $this->getId()), ['tag'=>"google-analytics-set-tracking-{$key}", 'user_id'=>$this->getId()]);
            } else {
                phive('Logger')->getLogger('google-analytics')->info(phMgetShard($key, $this->getId()), ['tag'=>"google-analytics-update-tracking-{$key}", 'user_id'=>$this->getId()]);
            }
        }
    }

    /**
     * Getting from Redis the variables to send to Google or pixel companies
     *
     * @param $key
     * @return array
     */
    public function getTrackingEvent($key): array
    {
        return json_decode(phMgetShard($key,  $this->userId),true) ?? [];
    }

    /**
     * Remove the key from Redis when the system already sent the events to the companies
     *
     * @param $key
     */
    function removePixelKey($key)
    {
        if (phive()->getSetting('enable_logs_in_events')) {
            phive('Logger')->getLogger('google-analytics')->info("google-analytics-delete-redis-{$key}", ['key' => $key, 'user_id' => $this->getId()]);
        }

        phMdelShard($key, $this->userId);
    }

    /**
     * Saving first party data
     *
     * @param string $key
     * @param array $params
     * @return object|bool
     */
    public function setFirstPartyData(string $key, array $params)
    {
        if (empty($params['model']) && empty($params['model_id'])) {
            return false;
        }

        $model = $params['model'] ?? '';
        $modelId = $params['model_id'] ?? '';
        $btag = $params['btag'] ?? '';

        $uid = $this->getId();
        $insert = [
            'model'         => $model,
            'model_id'      => $modelId,
            'slug'          => $key,
            'created_at'    => phive()->hisNow(),
            'device'        => phive()->deviceType(),
            'browser'       => $this->getBrowser(),
            'user_agent'    => $this->getBrowser(true),
            'user_id'       => $this->getId(),
            'ga_cookie_id'  => $this->getGaId(),
            'traffic_source'=> $this->getMarketName(),
            'country_in'    => $this->getCurrentCountry(),
            'is_gtm_enabled' => isExternalTrackingEnabled(),
            'is_gtm_blocked' => $this->getGtmWorkingStatus(),
            'btag'          => $btag,
        ];

        $findQuery = "SELECT * FROM analytics a WHERE a.model = '{$model}' AND a.model_id = $modelId AND a.slug = '{$key}'";
        $hasData = phive('SQL')->sh($uid)->loadArray($findQuery);
        if (!$hasData && $insert['browser'] != "Other") {
            return phive('SQL')->sh($uid)->insertArray('analytics', $insert);
        }
        return false;
    }

    /**
     * check if GTM is working and GA ID is generated
     * @return bool
     */
    public function getGtmWorkingStatus(): bool
    {
        if (isset($_COOKIE[static::GA_CLIENT_COOKIE_NAME])) {
            return false;
        }
        return true;
    }

    /*
     * Simply get GA client ID 1111.1111
     * return $clientId;
     * */
    public function getGaId(): string {
        $name = static::GA_COOKEI_NAME;
        $custom_cookie_name = static::GA_CLIENT_COOKIE_NAME;
        if (isset($_COOKIE[$custom_cookie_name])) {
            return $_COOKIE[$custom_cookie_name];
        }

        if (isset($_COOKIE[$name])) {
            $c = explode(".", $_COOKIE[$name]);
            return $c[2].'.'.$c[3];
        }
        return '';
    }

    /**
     * @return string
     */
    public function getMarketName(): string
    {
        return $this->getJurisdiction();
    }

    /**
     * getBrowser() is used to get the browser name and user agent based on conditions mentioned below.
     *
     * $this->getBrowser() is used to get only browser name
     * $this->getBrowser(true) is used to get user agent ($_SERVER['HTTP_USER_AGENT']);
     * $this->getBrowser(false, $user['uagent']) is used to get browser name from user agent saved in database.
     * $this->getBrowser() will send cli if used command line interface
     *
     * @param bool $agent
     * @param string $agentName
     * @return string
     */
    public function getBrowser(bool $agent = false, string $agentName = ''): string
    {
        $user_agent = !empty($agentName) ? $agentName : $_SERVER['HTTP_USER_AGENT'];

        if (isCli()) {
            $user_agent = "CLI";
        }

        if ($agent) {
            return $user_agent;
        }

        if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) {
            return 'Opera';
        } elseif (strpos($user_agent, 'Edge') || strpos($user_agent, 'Edg')) {
            return 'Edge';
        } elseif (strpos($user_agent, 'Chrome')) {
            return 'Chrome';
        } elseif (strpos($user_agent, 'Safari')) {
            return 'Safari';
        } elseif (strpos($user_agent, 'Firefox')) {
            return 'Firefox';
        } elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) {
            return 'Internet Explorer';
        } elseif (strtolower($user_agent) == 'cli') {
            return 'CLI';
        } else {
            return 'Other';
        }
    }

    /**
     * Get current country of the user based on IP address
     * @return string
     */
    public function getCurrentCountry(): string
    {
        return phive('IpBlock')->getCountry();
    }

    /**
     * Separate firstname, in firstanme + middlename, ex: Lars Henrik Carl return [Lars, Henrik Carl]
     *
     * @return array
     */
    public function getSplitName()
    {
        $name = explode(" ", trim($this->data['firstname']));
        $middlename = implode(" ", array_slice($name, 1));
        return [$name[0], $middlename];
    }

    /**
     * Method that get user restrictions and blocks settings.
     *
     * @return array
     */
    public function getBlocksAndRestrictions(): array
    {
        $in = phive('SQL')->makeIn(['super-blocked', 'play_block', 'restrict']);

        return $this->getKvSettings("setting IN($in)");
    }

    /**
     * Detect if user was unsubscribed
     * @return bool
     */
    public function isUnsubscribed(): bool
    {
        return !empty($this->getSetting('unsubscribed_email'));
    }

    public function getPspUid($psp = null){
        if(empty($psp)){
            return null;
        }
        return $this->getSetting($psp.'_user_id');
    }

    /**
     * Check if flag bluem_iban_check_passed has been raised
     */
    public function isIBANCheckPassed(): bool
    {
        return $this->hasSetting('bluem_iban_check_passed');
    }

    /**
     * Check if setting bluem_iban_suggested_name has some value
     */
    public function isFlaggedMisTypedFromIBAN(): bool
    {
        return $this->hasSetting('bluem_iban_suggested_name');
    }

    /**
     * Get value from user settings for bluem_iban_suggested_name
     */
    public function getIBANSuggestedName()
    {
        return $this->getSetting('bluem_iban_suggested_name');
    }

    /**
     * Check if user is self-locked
     */
    public function isSelfLocked(): bool
    {
        return $this->isBlocked() && $this->hasSetting('unlock-date');
    }

    /**
     * Check if user is self-excluded
     *
     * @return bool
     */
    public function isSelfExcluded(): bool
    {
        return $this->isBlocked() && $this->hasSetting('excluded-date');
    }

    /**
     * @param int|null $user_game_session_id
     * @return WinlossBalanceInterface
     */
    public function winLossBalance(?int $user_game_session_id = null): WinlossBalanceInterface
    {
        return new WinlossBalance($this, $user_game_session_id);
    }

    /**
     * Calculates user's jurisdiction on the fly and returns value
     *
     * @return string
     */
    public function getCalculatedJurisdiction(): string
    {
        $country = licJur($this);
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');

        return $country_jurisdiction_map[$country] ?? $country_jurisdiction_map['default'];
    }

    /**
     * Returns user's jurisdiction from user's settings or calculate and store jurisdiction with returning the value.
     *
     * @return string
     */
    public function getJurisdiction(): string
    {
        $jurisdiction = $this->getSetting('jurisdiction');

        if (!$jurisdiction) {
            $jurisdiction = $this->getCalculatedJurisdiction();
            $this->setSetting('jurisdiction', $jurisdiction);
        }

        return $jurisdiction;
    }

    /**
     * @param string $start_date Y-m-d
     * @param string $end_date Y-m-d
     *
     * @return int
     */
    public function getNetLossBetweenDates(string $start_date, string $end_date): int
    {
        $user_id = $this->getId();
        $start_date = Carbon::parse($start_date)->toDateString();
        $end_date = Carbon::parse($end_date)->toDateString();
        $query = phive('Cashier/Rg')->netLossDBQuery($start_date, $end_date, $user_id);
        $data = phive('SQL')->sh($user_id)->loadAssoc($query);

        return (int)($data['net_loss'] ?? 0);
    }

    /*
     * Do procedures after RG popup shown:
     * - set user setting 'popup-shown-RGX'
     * - create variable 'rg-popup-shown' in Redis memory
     * - add entry into 'users_comments' with tag 'automatic-flags'
     * - start RG evaluation
     *
     * @param string $trigger_name
     * @return bool
     */
    public function rgPopupShown(string $trigger_name): bool
    {
        $user_id = $this->getId();
        $popup_shown_at = Carbon::now()->toDateTimeString();
        $popups_interval_in_minutes = (int)phive('Config')->getValue('RG', 'popupsInterval', 60);
        $expire = $popups_interval_in_minutes * 60;
        phMsetShard('rg-popup-shown', $popup_shown_at, $user_id, $expire);
        phive('RgEvaluation/RgEvaluation')->startEvaluation($this, $trigger_name);
        phive('UserHandler')->logAction($this,
            "{$trigger_name} Popup shown to customer.",
            'automatic-flags'
        );
        return $this->setSetting("popup-shown-" . $trigger_name, $popup_shown_at, false);
    }

    /**
     * Sets/Updates the user's importing status to SCV and logs the changes
     *
     * @param string $status
     * @return bool
     */
    public function setOrUpdateSCVExportStatus(string $status): bool
    {
        $user_id = $this->getId();

        if (!$user_id) {
            return false;
        }

        if (!in_array($status, self::SCV_ALLOWED_EXPORT_STATUSES)) {
            return false;
        }

        phive('UserHandler')->logAction(
            $this,
            "Status updated to: {$status}",
            'scv_import_status'
        );

        phive('SQL')->sh($user_id)->save(
            'scv_export_status',
            ['user_id' => $user_id, 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')],
            ['user_id' => $user_id]
        );

        return true;
    }

    /**
     * Get all previous currency user ids.
     * During currency transition process new user is created and data from old user is transferred to the new user.
     * However, some tables are not transferred (e.g. bets).
     * This function can be used to access non-transferred user data.
     *
     * @return array
     */
    public function getPreviousCurrencyUserIds(): array
    {
        $prev_ids = [];
        $prev_id = $this->getSetting('mvcur_old_id');

        while ($prev_id) {
            $prev_ids[] = $prev_id;
            $prev_id = cu($prev_id)->getSetting('mvcur_old_id');
        }

        return $prev_ids;
    }

    public function hasCompletedRegistration(): bool
    {
        return !$this->hasSetting('registration_in_progress') && $this->hasSetting('registration_end_date');
    }

    /**
     * @param int $interval_in_days
     *
     * @return int
     */
    public function getFrequencyChangeOfLimits(int $interval_in_days = 30): int
    {
        $sql = "
            SELECT
                COUNT(DISTINCT created_at) as count
            FROM
                actions
            WHERE
                target = {$this->getId()}
                AND actor = {$this->getId()}
                AND tag IN ('deposit-rgl-change', 'deposit-rgl-remove', 'wager-rgl-remove', 'wager-rgl-change')
                AND created_at > (NOW() - INTERVAL {$interval_in_days} DAY)";

        $res = phive('SQL')->readOnly()->sh($this->getId(), '')->loadObject($sql);

        return (int)$res->count;
    }

    /**
     * Returns registration timestamp base on:
     * - registration_end_date
     * - first users_sessions
     *
     * @return array
     */
    public function getRegistrationTimestamp(): array
    {
        if ($this->hasCompletedRegistration()) {
            $setting = $this->getWholeSetting('registration_end_date');
            return [
                'registered_at' => $setting['created_at'],
                'source' => 'registration_end_date'
            ];
        }

        $last_login = $this->getAttr('last_login');
        $uid = $this->getId();

        if (!empty($last_login)) {
            $first_session_at = phive('SQL')
                ->sh($uid, '', 'users_sessions')
                ->getValue("SELECT created_at FROM users_sessions WHERE user_id = $uid ORDER BY created_at ASC LIMIT 1");

            if (!empty($first_session_at)) {
                return [
                    'registered_at' => $first_session_at,
                    'source' => 'users_sessions'
                ];
            }
        }

        return [
            'registered_at' => null,
            'source' => null
        ];
    }

    /**
     * Returns first registration from the oldest account base on:
     * - c{brand_id}_matches (all brands)
     * - registration_end_date local (if it has no c{brand_id}_matches)
     * - registration_end_date remote
     *
     * @return array
     */
    public function getFirstBrandRegistrationTimestamp(): array
    {
        $remoteBrandId = distId(getRemote());
        $brand_matches_setting_name = "c{$remoteBrandId}_matches";
        if ($this->hasSetting($brand_matches_setting_name)) {
            try {
                $cross_brand_matches = json_decode($this->getSetting($brand_matches_setting_name), true, 512,
                    JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $cross_brand_matches = [];
            }
            $timestamps = array_column($cross_brand_matches, 'registered_at');
            $earliest = min($timestamps);

            if ($earliest) {
                return [
                    'registered_at' => Carbon::parse($earliest)->toDateTimeString(),
                    'source' => $brand_matches_setting_name,
                ];
            }
        }

        $data = $this->getRegistrationTimestamp();
        $registered_at = $data['registered_at'];
        $source = $data['source'];
        $user_remote_id = $this->getRemoteId();

        if ($user_remote_id) {
            $response = toRemote(
                getRemote(),
                'getRemoteFirstRegistrationTimestamp',
                [$user_remote_id]
            );

            if (!empty($response['success']) && !empty($response['result']['registered_at'])) {
                $remote_registered_at = Carbon::parse($response['result']['registered_at']);
                $local_registered_at = Carbon::parse($registered_at);

                if ($remote_registered_at < $local_registered_at) {
                    $source = $response['result']['source'];
                    $registered_at = $remote_registered_at->toDateTimeString();
                }
            }
        }

        return [
            'registered_at' => $registered_at,
            'source' => $source
        ];
    }

    public function deleteExternalVerificationData(): void
    {
        $this->deleteSetting('nid');
        $this->deleteSetting('nid_data');
        $this->deleteSetting('verified');

        if (!lic('isNeedRequiredForRegistration', [], $this)) {
            $this->setAttribute('nid', '');
            $this->deleteSetting('verified-nid');
        }
    }

    /**
     * Checks if the user has privacy settings
     * @return bool
     */
    public function hasPrivacySettings(): bool
    {
        /** @var PrivacyHandler $ph */
        $ph = phive('DBUserHandler/PrivacyHandler');
        return $ph->hasPrivacySettings($this);
    }
}
