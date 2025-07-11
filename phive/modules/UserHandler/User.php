<?php

/**
 * An object wrapper around mainly the users and users_settings tables. This is the generic logic that can be used
 * on both casino and affiliate sites.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_settings The wiki page for the users settings table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users The wiki page for the users table.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_users_comments The wiki page for the users comments table.
 */
class User {

    // TODO henrik remove
    protected static $c = __CLASS__;

    /**
     * @var int The user id for easy access.
     */
    public $userId;

    /**
     * @var array The users database table row.
     */
    public $data = array();

    /**
     * Constructor for the User object.
     *
     * @param mixed $ud User data or user id.
     *
     * @return object The User object.
     */
    public function __construct($ud){
        if(is_numeric($ud))
            $this->userId = $ud;
        else{
            $this->userId = $ud['id'];
            $this->data   = $ud;
        }
    }

    /**
     * Basic preload function that is responsible for assigning various member variables.
     *
     * @return bool True if the data could be loaded, false otherwise.
     */
    public function preload() {
        $sql = phive('SQL');
        $uid = $this->getId();
        // Why would we ever have a situation where there is no user id so we need to fetch by username?
        // Can this be removed?
        if(empty($uid))
            $r = $sql->shs('merge', '', null, 'users')->loadAssoc('', 'users', ['username' => $this->data['username']]);
        else
            $r = $sql->sh($uid, '', 'users')->loadAssoc('', 'users', ['id' => $uid]);

        if(empty($r)){
            $this->userId = null;
            return false;
        } else {
            $this->data = $r;
            $this->userId = $r['id'];
            return true;
        }
    }

    /**
     * Simple getter around the array data that corresponds to the columns in the users table.
     *
     * @param null|string $key - used to get the value of a specific $key
     * @return array|mixed The user data.
     */
    public function getData($key = null)
    {
        if (!is_null($key)) {
            return $this->data[$key];
        }

        return $this->data;
    }

    /**
     * Update user data on shards and optionally on master
     *
     * TODO henrik remove the $do_master arg, refactor all invocations.
     *
     * @param array $data The associative array of column names as keys and values as values to use for the update.
     * @return bool False if the update query had errors or db connection failed, true otherwise.
     */
    public function updateData($data, $do_master = false)
    {
        pOrDie('edit.user.raw');
        $sql = phive('SQL');
        $uid = $this->getId();
        $where = "`id`= $uid";
        foreach ($data as $key => $value)
            $data[$key] = filter_var($data[$key], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        $user_old = cu($uid);

        if ($do_master) {
            $sql->updateArray('users', $data, $where);
        }

        $result = $sql->sh($uid, '', 'users')->save('users', $data, $where);

        lic('onUserCreatedOrUpdated', [$uid, $data, $user_old ? $user_old->getData() : []]);

        return $result;
    }

    /**
     * Gets the value of a column name in the users row, eg email.
     *
     * @param string $select The column name.
     * @param bool $fresh Whether or not to get a cached value or straight from the database.
     *
     * @return int|string The value.
     */
    function getAttribute($select, $fresh = false){
        if($select == 'hidden')
            return '';

        if(empty($this->data))
            $this->preload();

        if($fresh){
            $val = $this->getCurAttr($select, true);
            $this->data[$select] = $val;
        }

        return $this->data[$select];
    }

    /**
     * Alias of getAttribute()
     *
     * @uses User::getAttribute()
     * @see User::getAttribute()
     *
     * @param string $attr The column name.
     * @param bool $fresh Whether or not to get a cached value or straight from the database.
     *
     * @return int|string The value.
     */
    function getAttr($attr, $fresh = false){
        return $this->getAttribute($attr, $fresh);
    }

    /**
     * Bypasses the $data array and gets a users row column value straight from the database.
     *
     * @param string $attr The column name.
     * @param bool $fresh true - don't fetch from replica; false - fetch from replica
     *
     * @return int|string The value.
     */
    function getCurAttr($attr, bool $fresh = false)
    {
        $uid = $this->getId();
        $str = "SELECT $attr FROM users WHERE id = $uid";

        if ($fresh) {
            return phive('SQL')->sh($uid, '', 'users')->getValue($str);
        }

        return phive('SQL')->readOnly()->sh($uid, '', 'users')->getValue($str);
    }

    /**
     * Fetches user settings for the user in question based on a regex.
     *
     * @link https://mariadb.com/kb/en/regexp/ The MariaDB knowledge base for RLIKE / REGEXP.
     *
     * @param string $regex_str The regex.
     *
     * @return array The settings.
     */
    function getSettingsByRegex($regex_str){
        $uid = $this->userId;
        $str = "SELECT * FROM users_settings WHERE `user_id` = $uid AND `setting`  RLIKE " . phive('SQL')->escape($regex_str);
        return phive('SQL')->sh($uid, '', 'users_settings')->loadArray($str);
    }

    /**
     * Gets the cash_balance row value, NOTE that this always uses fresh so always incurs a DB call.
     *
     * @return int The balance.
     */
    function getBalance(){
        return $this->getAttribute('cash_balance', true);
    }

    /**
     * Sets UPDATEs a row column value in users for the current user.
     *
     * @param string $attribute The column name.
     * @param mixed $value The value.
     * @param $noescape TODO henrik remove, check all invocations.
     *
     * @return bool True if no errors in executing the query, false otherwise.
     */
    function setAttribute($attribute, $value, $noescape=false){
        if ($attribute == null || $this->getId() == null)
            return false;
        if (!$this->data)
            $this->preload();
        $this->data[$attribute] = $value;
        $uid   = $this->getId();
        $value = phive('SQL')->escape($value, false);
        $query = "UPDATE users SET $attribute = '$value' WHERE id = $uid";

        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->query($query);
            return phive('SQL')->sh($uid, '', 'users')->query($query);
        }

        return phive('SQL')->query($query);
    }

    /**
     * Alias of setAttribute().
     *
     * @uses User::setAttribute()
     * @see User::setAttribute()
     *
     * @param string $attribute The column name.
     * @param mixed $value The value.
     * @param $noescape TODO henrik remove, check all invocations.
     *
     * @return bool True if no errors in executing the query, false otherwise.
     */
    function setAttr($attr, $value, $noescape = false){
        $this->setAttribute($attr, $value, $noescape);
    }

    // TODO henrik remove
    function copyData($from, $to){
        $this->data[$to] = $this->data[$from];
    }

    /**
     * Alias of incrementAttribute()
     *
     * @uses User::incrementAttribute()
     * @see User::incrementAttribute()
     *
     * @param string $attribute The column name.
     * @param int $amount The amount.
     * @param bool $unsigned Indicates if attribute is unsigned
     *
     * @return bool True if the query was successful, false otherwise.
     */
    function incAttr(string $attribute, int $amount, bool $unsigned = false): bool
    {
        return $this->incrementAttribute($attribute, $amount, $unsigned);
    }

    /**
     * Increments a row column.
     *
     * @param string $attribute The column name.
     * @param int $amount The amount.
     * @param bool $unsigned Indicates if attribute is unsigned
     *
     * @return bool True if the query was successful, false otherwise.
     */
    public function incrementAttribute(string $attribute, int $amount, bool $unsigned = false): bool
    {
        $uid = $this->getId();
        if ($uid === null) {
            return false;
        }

        $where = "`id` = $uid";
        if ($unsigned) {
            $where .= " AND `$attribute`+$amount >= 0";
        }

        $sql = phive('SQL');
        $result = $sql->incrValue('users', $attribute, $where, (float)$amount, [], $uid);

        if ($result === false) {
            return false;
        }

        if ($sql->sh($uid)->affectedRows() == 0) {
            return false;
        }

        if (!$this->data)
            $this->preload();
        $this->data[$attribute] += $amount;

        return true;
    }

    /**
     * Gets a user setting value.
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if passed we get the setting with that id instead of the user_id.
     * @param bool $strip_slashes Whether or not to run stripslashes() on the return.
     *
     * @return mixed The value.
     */
    public function getSetting($setting, $id = '', $strip_slashes = false){
        $id  = intval($id);
        $sql = phive('SQL');
        if(empty($id))
            $str = "SELECT `value` FROM users_settings WHERE `user_id` = {$this->userId} AND `setting`=" . $sql->escape($setting);
        else
            $str = "SELECT `value` FROM users_settings WHERE `id`= $id";
        $res = $sql->sh($this->userId, '', 'users_settings')->getValue($str);
        return $strip_slashes ? stripslashes($res) : $res;
    }

    /**
     * Gets the whole users settings row and checks if it is empty or not, we do this because we can't check
     * the value as it might actually contain something that evaluates as empty, like for instance 0.
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if passed we get the setting with that id instead of the user_id.
     * @param bool $read_from_master Optional, use master DB
     *
     * @return bool True if the setting exists, false otherwise.
     */
    function hasSetting($setting, $id = '', $read_from_master = false){
        $value = $this->getWholeSetting($setting, $id, $read_from_master);
        return !empty($value);
    }

    /**
     * Checks if an attribute (users column) is empty / null.
     *
     * @param string $attr The column.
     *
     * @return bool True if it is not empty or explicitly set to 0, false otherwise.
     */
    function hasAttr($attr){
        $data = $this->getAttr($attr);
        if($data === 0){
            return true;
        }
        return !empty($data);
    }

    // TODO henrik remove
    function getAllComments(){
        $str = "SELECT DISTINCT * FROM users_comments WHERE user_id = {$this->userId} ORDER BY sticky DESC, created_at DESC";
        return phive('SQL')->sh($this->userId, '', 'users_comments')->loadArray($str, 'ASSOC');
    }

    // TODO henrik remove
    function getAllComplaints(){
        return $this->getAllSettings(" tag = 'complaint' ");
    }

    /**
     * Gets all setting rows.
     *
     * @param string $where Optional WHERE clauses.
     * @param bool $setting_as_key Whether or not the setting will be the key for each settings row / sub array.
     *
     * @return array The settings.
     */
    function getAllSettings($where = '', $setting_as_key = false){
        if(!empty($where))
            $where = " AND $where";
        $str = "SELECT DISTINCT * FROM users_settings WHERE user_id = {$this->userId} $where";
        return phive('SQL')->readOnly()->sh($this->userId, '', 'users_settings')->loadArray($str, 'ASSOC', $setting_as_key ? 'setting' : false);
    }

    /**
     * Gets a setting list.
     *
     * @param array $settings
     * @param bool $setting_as_key
     *
     * @return array
     */
    public function getSettingsIn(array $settings, bool $setting_as_key = false): array
    {
        $in = phive('SQL')->makeIn($settings);
        $settings_return = $this->getAllSettings("setting IN($in)", $setting_as_key);
        return (is_array($settings_return)) ? $settings_return : [];
    }

    /**
     * Gets all settings with the setting name as the key and the setting value as the value.
     *
     * @param string $where Optional WHERE clauses.
     *
     * @return array The settings.
     */
    function getKvSettings($where = ''){
        $rarr = array();
        foreach($this->getAllSettings($where) as $s)
            $rarr[$s['setting']] = $s['value'];
        return $rarr;
    }

    /**
     * Gets all settings with the setting name as the key and the setting value as the value.
     *
     * @param string $part The partial string that will be matched on the setting name.
     *
     * @return array The settings.
     */
    function getSettingsByPartial($part){
        return $this->getKvSettings("setting LIKE '%$part%'");
    }

    /**
     * Checks if the user is blocked or not by checking if the active column is 0 (blocked) or 1 (not blocked).
     *
     * @param bool $fresh Whether or not to fetch a fresh value to check.
     *
     * @return bool True if the user is blocked, false otherwise.
     */
    function isBlocked($fresh = false){
        $v = $this->getAttr('active', $fresh);
        return empty($v);
    }


    /**
     * Gets a setting by a partial, note that if there are more than one matching setting
     * the returned result will be random.
     *
     * @param string $part The partial string that will be matched on the setting name.
     *
     * @return array The setting.
     */
    function getSettingByPartial($part){
        $arr = $this->getSettingsByPartial($part);
        return array_shift($arr);
    }

    /**
     * Saves multiple settings in one go.
     *
     * @param array $arr The key -> value array where the key is the setting and value the setting value.
     *
     * @return null
     */
    function setSettings($arr){
        foreach($arr as $key => $val)
            $this->setSetting($key, $val);
    }

    /**
     * Sets one setting.
     *
     * @param string $setting The setting to save.
     * @param mixed $value The value to save in the setting.
     * @param bool $update_timestamp If set to true it will update "created_at" to now
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    public function setSetting(string $setting, $value, bool $update_timestamp = false)
    {
        $sql = phive('SQL');
        $data = [
            'user_id' => $this->userId,
            'setting' => $setting,
            'value' => $value
        ];
        if (!empty($update_timestamp)) {
            $data['created_at'] = phive()->hisNow();
        }
        return $sql->sh($this->userId)->save('users_settings', $data);
    }


    /**
     * Sets contact info, logs and calls lic functions on update
     *
     * @param array $new_settings
     * @return void
     */
    function setContactInfo(array $new_settings):void {
        //moving specific fields to be saved in a user_settings table
        $userOldData = $this->getData();
        $userNewData = $new_settings;
        $settings_to_update = array_intersect_key($new_settings, array_flip(['building', 'main_province', 'nationality', 'calling_code']));
        if($settings_to_update) {
            foreach($settings_to_update as $key => $val) {
                $userOldData[$key] = $this->getSetting($key);
                if($val) {
                    $this->setSetting($key, $val);
                }
                unset($new_settings[$key]);
            }
        }

        // updating shard db
        phive("SQL")->sh($this->userId, 'id', 'users')->updateArray('users', $new_settings, array('id' => $this->userId));
        // updating master db
        phive("SQL")->updateArray('users', $new_settings, array('id' => $this->userId));

        $this->refresh();
        lic('onAccountUpdate', [$this->userId], $this->userId);
        lic('onUserCreatedOrUpdated',
            [$this->userId, $userNewData, $userOldData]);

    }

    /**
     * Gets a whole settings row.
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if used we fetch with it instead of the user id.
     * @param bool $read_from_master Optional, use master DB
     *
     * @return array The setting.
     */
    public function getWholeSetting($setting, $id = '', $read_from_master = false){
        $sql = $read_from_master ? phive('SQL') : phive('SQL')->readOnly();
        if(empty($id))
            $str = "SELECT * FROM users_settings WHERE `user_id`=" . $this->userId . " AND `setting`=" . $sql->escape($setting);
        else
            $str = "SELECT * FROM users_settings WHERE `id`= $id";
        return $sql->sh($this->userId, '', 'users_settings')->loadAssoc($str);
    }

    /**
     * Will check if the setting has expired: return true if setting is missing or has expired (default 30days)
     *
     * @param string $setting The setting.
     * @param int $time The amount of time units to use, typically days.
     * @param string $frequency The time unit, default is **day**.
     * @param string $key The settings column to use, default created_at.
     * @return bool True if it is older than the time expression, false otherwise.
     */
    public function hasSettingExpired($setting, $time = 30, $frequency = "day", $key = 'created_at')
    {
        $setting = $this->getWholeSetting($setting);
        if (empty($setting)) {
            return true;
        }
        if ($setting[$key] < phive()->hisMod("- $time $frequency")) {
            return true;
        }
        return false;
    }

    /**
     * Increases a setting.
     *
     * TODO henrik this is not ACID and check if log action is used.
     *
     * @param string $setting The setting.
     * @param int $num The number to increase with.
     * @param bool $log_action Whether or not to log the update in the actions table.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    function incSetting($setting, $num = 1, $log_action = false){
        return $this->setSetting($setting, $this->getSetting($setting) + $num, $log_action);
    }

    /**
     * Decreases a setting.
     *
     * TODO henrik this is not ACID
     *
     * @param string $setting The setting.
     * @param int $num The number to decrease with.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    function decSetting($setting, $num = 1){
        return $this->setSetting($setting, $this->getSetting($setting) - $num);
    }

    /**
     * Deletes a setting
     *
     * @param string $setting The setting.
     * @param int $id Optional id override, if passed in we use it instead of the user id.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    public function deleteSetting($setting, $id = ''){
        $id = intval($id);
        $sql 		= phive('SQL');
        if(empty($id))
            $str = "DELETE FROM users_settings WHERE `user_id`= {$this->userId} AND `setting`=" . $sql->escape($setting);
        else
            $str = "DELETE FROM users_settings WHERE `id`= $id";
        return $sql->sh($this->userId, '', 'users_settings')->query($str);
    }

    /**
     * Deletes multiple settings
     *
     * @uses User::deleteSetting()
     * @see User::deleteSetting()
     *
     * @param string|array An arbitrary amount of arguments, each argument is a setting to delete, if array
     * we loop it and delete all settings in the array.
     *
     * @return null
     */
    function deleteSettings(){
        $arr = func_get_args();
        if(is_array($arr[0]))
            $arr = $arr[0];
        foreach($arr as $s)
            $this->deleteSetting($s);
    }

    // TODO henrik remove
    function getSettingByValue($val){
        $sql 		= phive('SQL');
        return phive('SQL')->sh($this->userId, '', 'users_settings')->loadAssoc("SELECT * FROM users_settings WHERE `user_id`= {$this->userId} AND `value`=" . $sql->escape($val));
    }

    // TODO henrik remove
    public function deleteSettingByValue($val){
        $sql 		= phive('SQL');
        if(!empty($val))
            return $sql->sh($this->userId, '', 'users_settings')->query("DELETE FROM users_settings WHERE `user_id`= {$this->userId} AND `value`=" . $sql->escape($val));
        return false;
    }

    // TODO henrik remove
    public function joinGroup($groupId){
        return phive('SQL')->save('groups_members', array('group_id' => $groupId, 'user_id' => $this->getId()));
    }

    //Currently not used anywhere
    // TODO henrik remove
    public function joinGroupByName($group){
        $group = phive('UserHandler')->getGroupByName($group);
        if ($group)
            return $this->joinGroup($group->getId());
        else
            return false;
    }

    // TODO henrik remove
    public function leaveGroup($groupId){
        $sql = phive('SQL');
        $table_str = phive('UserHandler')->getSetting('db_groups_members');
        $r = $sql->query(
            "DELETE FROM " . $table_str . " WHERE `group_id`=" .
            $sql->escape($groupId) . " AND `user_id`=" .
            $sql->escape($this->userId));
        return $r;
    }

    /**
     * Changes the user's password.
     *
     * @param string $password The new password.
     *
     * @return bool True if the query executed without a hard error, false otherwise.
     */
    public function setPassword($password) {
        $enc_password = phive('UserHandler')->encryptPassword($password);
        $q = "UPDATE users  SET password = " .  phive('SQL')->escape($enc_password) . " WHERE id=" . $this->userId . " LIMIT 1";
        return phive('SQL')->sh($this->userId)->query($q);
    }

    // TODO henrik remove
    public function info() {
        if($this->getId() === null) {
            return null;
        }

        ob_start();
        echo ("<pre>");
        var_dump($this);
        var_dump($_SESSION);
        echo ("</pre>");
        $r = ob_get_contents();
        ob_end_clean();

        return $r;
    }

    // TODO henrik remove
    public function isMemberOf($gid){
        $gid = (int)$gid;
        $sql = "SELECT COUNT(user_id) FROM groups_members WHERE group_id = $gid AND user_id = {$this->userId}";
        $count = phive('SQL')->queryAnd($sql)->result();
        return $count == 0 ? false : true;
    }

    /**
     * Re-fetches the users row and stores it in the $data member variable.
     *
     * @return null
     */
    function refresh(){
        $this->data = phive("SQL")->sh($this->userId, '', 'users')->loadAssoc('', 'users', array('id' => $this->getId()));
    }

    /**
     * Getter for the user id.
     *
     * @return int The user id.
     */
    public function getId() {             return $this->userId; }

    /**
     * Getter for the username.
     *
     * @return int The username.
     */
    public function getUsername() {	return $this->data['username']; }


    /**
     * @return string
     */
    public function getFirstName(): string {
        return ucfirst($this->data['firstname']);
    }

    /**
     * @return string
     */
    public function getLastName(): string {
        return ucfirst($this->data['lastname']);
    }

    /**
     * Getter for the user full name, concatenates firstname and lastname with a space in between.
     *
     * @return int The full user name.
     */
    public function getFullName() {	return ucfirst($this->data['firstname']).' '.ucfirst($this->data['lastname']); }

    /**
     * Getter for the user password.
     *
     * @return int The user password.
     */
    public function getPassword() {	return $this->getAttribute('password'); }

    /**
     * Returns the groups the user is a member of.
     *
     * @used-by Permission::hasPermission()
     *
     * @param string $extraSQL Potentially some extra SQL to run after the GROUP BY statement.
     *
     * @return array An array of groups.
     */
    public function getGroups($extraSQL = null) {
        $groupTable		= phive('UserHandler')->getSetting("db_groups");
        $memberTable	= phive('UserHandler')->getSetting("db_groups_members");

        $q = "SELECT $groupTable.* FROM $groupTable, $memberTable WHERE $groupTable.group_id = $memberTable.group_id AND $memberTable.user_id = {$this->getId()} ORDER BY $groupTable.name $extraSQL";

        $rArray = phive('SQL')->loadArray($q);

        $Groups = array();
        foreach($rArray as $r)
            $Groups[] = new Group($this, $r);

        return $Groups;
    }

    // TODO henrik remove
    public function getProfileLink(){ return $this->getUsername(); }

    // TODO henrik remove
    public function getTableName($table_id){ return phive('UserHandler')->getTableName($table_id); }

}
