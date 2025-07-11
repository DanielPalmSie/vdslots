<?php
require_once __DIR__ . '/../../api/PhModule.php';

// TODO henrik remove these, they are not used
define('ERR_FAILED_MYSQL', 	'Connection with MySQL server %s failed.');
define('ERR_FAILED_PGSQL', 	'Connection with pgSQL server %s failed.');
define('ERR_FAILED_SELECT', 	'Could not select database %s');
define('ERR_FAILED_RESOURCE', 	'Error using resource %d');
define('ERR_NO_FUNCTION', 	'No such function exists for database type %s');
define('ERR_NO_HANDLE', 	'No connection with SQL server.');
define('ERR_RESOURCE_POP', 	'Cannot pop resource: %s.');

// TODO henrik do array() -> [] replace.

/*
 * This is the base class that wraps the mysqli family of built in methods in order to query the databases.
 * It also contains a few rudimentary SQL builders.
*/
class SQL extends PhModule {

    /**
     * @link https://www.php.net/manual/en/mysqli.init.php mysqli_init() at PHP.net.
     * @link https://www.php.net/manual/en/book.mysqli Mysqli documentation at PHP.net.
     * @var object The mysqli resource used for interacting with the database as returned by mysqli_init().
     */
    private $_handle = false;

    // TODO henrik remove this
    private $_id = 0;

    // TODO henrik remove this
    private $row = 0;

    /**
      * @var bool Variable that keeps track of DB connection status, true if connected, false otherwise.
     */
    private $con_status = false;

    /**
     * @see SQL::doDb()
     * @var array An array with potential connections to various databases.
     */
    private $exts = array();


    public $is_shard = false;

    /**
     * An array of connections to the various shards if the database is sharded.
     * @var array
     */
    private $shards = array();

    /**
     * @var bool Enable query debug into $GLOBALS['sql_profiling']
     * To see the queries add at the end of the "Common pages" on diamondbet (Ex. generic.php, mobile.php)
     * echo "<pre>"; phive("SQL")->printDebug(); phive("SQL")->clearDebug();
     */
    private $debug = false;

    /**
     * @var float (in second) This will only log queries that takes more than the threshold, ex. 0.1
     */
    private $debug_threshold = 0;

    /**
     * @var SQL The connection we currently want to use in order to query the master database, this is to
     * create affinity in a CGI context as we don't want to open more connections than necessary, ie we
     * randomly pick one node and then we stick with that one.
     */
    public $master_read_from = null;

    /** @var bool $keep_last_error_message - request to store the last error message */
    public bool $keep_last_error_message = false;
    /** @var string $last_error_message - last error message */
    public string $last_error_message = '';

    /**
     * @var SQL This is an instance of SQL that will try to use read-only connections if available
     */
    private SQL $read_only_instance;
    /**
     * @var SQL
     */
    protected SQL $writable_instance;

    /*
     * Used to keep track of SQL connection retry attempts used for is_archive processes.
     */
    private int $forked_retry_attempts = 0;

    /**
     * Connectes and returns a DB connection handle object.
     *
     * @TODO henrik this method is a bit misnamed, it should be renamed so the name fits with the action better.
     *
     * @return object
     */
    function hasConnection(){
        $this->connect();
        return $this->_handle;
    }

    /**
     * This is a wrapper around a fairly uncommon scenario where we either want to query a specific node for
     * something or the whole node cluster by way of SQL::shs().
     *
     * @see SQL::shs()
     * @see SQL::sh()
     *
     * @param int $id The shard id, if -1 we loop all the shards and aggregate instead.
     * @param array $shs_args The arguments to pass to shs() in case we want to aggregate all nodes.
     *
     * @return mixed The result.
     */
    function getDbOrShsById($id, $shs_args = []){
        return (int)$id >= 0 ? $this->sh((int)$id) : call_user_func_array([$this, 'shs'], $shs_args);
    }

    /**
     * The basic sharding logic, just a modulo on the user id to the the shard number.
     *
     * @param int $user_id The unique user id (primary key).
     *
     * @return int The remainder.
     */
    function getNodeByUserId($user_id){
        $sh_conf = $this->getSetting('shards');
        if(!empty($sh_conf)){
            return $user_id % count($sh_conf);
        }
        return null;
    }

    /**
     * Depending on the context we handle connection errors differently.
     *
     * @param string $exception_msg Exception error message.
     * @param string $log_msg Log error message.
     *
     * @return null|string String that can be acted upon, eg 'retry' to retry the connection, or null if nothing to do.
     */
    function handleConnectionError($exception_msg = 'Database connection failed.', $log_msg = ''){

        if(!empty($log_msg)){
            error_log($log_msg);
        }

        if(phive()->isQueued()) {
            // We're in a queued context, we want to retry as we're not worried about connection buildup in this context, ie we're
            // looking at only one or a few processes anyway.
            $this->con_status = false;
            phive('Logger')->getLogger('queue_messages')->debug("CronEventHandler::handleConnectionError RETRY ", [$log_msg]);
            return 'retry';
        }

        if(phive()->isArchive()) {
            if ($this->forked_retry_attempts < 3) {
                phive('Logger')->getLogger('archive')->info("SQL::handleConnectionError Retry forked attempt : " . $this->forked_retry_attempts, [$log_msg, $exception_msg]);
                $this->forked_retry_attempts++;
                return 'retry';
            } else {
                phive('Logger')->getLogger('archive')->info("SQL::handleConnectionError Retry forked attempts exhausted. Going to fail.");
                throw new Exception($exception_msg);
            }
        }

        if($this->is_shard){
            // Web context and shard query, we fail silently and do not retry to avoid connection buildup.
            // We fail silently because we don't want to stop execution just because one shard failed.
            $this->con_status = true;
        } else {
            // We set status to false in case the calling context catches the exception and wants to retry.
            $this->con_status = false;
            // Web context and not shard query, we exit.
            throw new Exception($exception_msg);
        }
    }

    /**
     * Wrapper around Mysqli::real_connect()
     *
     * @param object $conn The Mysqli connection.
     *
     * @return bool True if the connection worked, false otherwise.
     */
    public function _connect($conn){
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        if($conn->real_connect($this->con_details["hostname"], $this->con_details["username"], $this->con_details["password"], '', $this->con_details["port"])){
            $this->_handle = $conn;
            $this->con_status = true;
            return true;
        }
        return false;
    }

    /**
     * The main connection logic.
     *
     * Note that the connection details are set in the constructor.
     *
     * @return mixed False if the connection attempt didn't work, the handle mysqli resource / object if it was successful.
     */
    function connect(){
        if($this->con_status === true)
            return false;
        $this->_handle = false;
        $conn = mysqli_init();
        if(empty($this->con_details)){
            $arr = array(
                'hostname' => $this->getSetting("hostname"),
                'username' => $this->getSetting("username"),
                'password' => $this->getSetting("password"),
                'port' => $this->getSetting("port")
            );

            $this->con_details = $arr;
        }


        if(empty($this->con_details['port']))
            $this->con_details['port'] = 3306;

        //if we're trying to connect to (default) db and do not succeed
        if($this->_connect($conn) === false) {
            $error_res = $this->handleConnectionError(
                "Error DB-001: general connection error.",
                "DB connection error - {$conn->connect_error}, Host: {$this->con_details["hostname"]}, Query: {$this->query_string}"
            );

            if($error_res === 'retry'){
                foreach([5, 10, 20, 40] as $sleep_duration){
                    phive('Logger')->getLogger('bos_logs')->info("sql_connection_retry", [
                        'sleep_duration' => $sleep_duration,
                        'db_conn_err' => $conn->connect_error,
                        'host' => $this->con_details["hostname"]
                    ]);
                    if (! empty($conn)) {
                        $conn->close();
                    }
                    sleep($sleep_duration);
                    $conn = mysqli_init();
                    if($this->_connect($conn)){
                        break;
                    }
                }
            }
        }

        // We have reached the end of the line and can still not connect so we abort here.
        if($this->con_status === false || $this->_handle === false){
            return false;
        }

        $db = empty($this->con_details['database']) ? $this->getSetting("database") : $this->con_details['database'];

        if(mysqli_select_db($this->_handle, $db) === false) {
            $this->_handle = false;
            $this->handleConnectionError("Error DB-002: database could not be selected.");
            return false;
        }

        mysqli_query($this->_handle, "SET NAMES '{$this->getSetting('charset')}'");
        mysqli_query($this->_handle, "SET CHARACTER SET $this->getSetting('charset')");
        if(!empty($this->con_details['extra'])){
            foreach($this->con_details['extra'] as $setting => $setting_value)
                mysqli_query($this->_handle, "SET $setting = $setting_value");
        }

        return $this->_handle;
    }

    /**
     * Reconnects if the connection status is false.
     *
     * It is preferable to use this method instead of just connect() to avoid going through the whole
     * connection process if we're already connected.
     *
     * @return mixed The connection handle if we connected successfully, false or null otherwise.
     */
    function connectIfDisconnected(){
        if($this->con_status !== true)
            return $this->connect();
    }

    /**
     * The constructor.
     *
     * The constructor takes a key to a configured connection array, or such an array directly. It then stores
     * the connection info in a member variable.
     *
     * @param array|string $key The connection key or array.
     *
     * @return null
     */
    function __construct($key = '') {
        $con_details = null;
        if(!empty($key)){
            //We're explicitly passing in connection details so we're using them.
            if(is_array($key))
                $con_details = $key;
            else{
                //We're referring to configured setting, ie $this->setSetting('archive' [ ... ])
                $con_details = $this->getSetting($key);
                //We have an alias to configured settings
                if(is_string($con_details))
                    $con_details = $this->getSetting($con_details);
            }
        }

        $this->con_details = $con_details;
    }

    // TODO henrik remove this
  function mailNotify($msg, $to_emails = ''){
    $domain = phive()->getSetting('domain');
    $from = 'admin@'.$domain;
    $to_emails = empty($to_emails) ? $this->getSetting('notify_emails') : $to_emails;
    foreach($to_emails as $email){
      $insert = array(
        'to'          => $email,
        'replyto'     => $from,
        'subject'     => 'Message from system at '.phive()->getSetting('domain'),
        'messageHTML' => $msg,
        'from'        => $from,
        'from_name'   => "System at $domain",
        'priority'    => 0
      );
      $mail_db = $this->getSetting('maildb');
      if(empty($mail_db))
        $this->insertArray('mailer_queue', $insert);
      else
        phive('SQL')->doDb('maildb')->insertArray('mailer_queue', $insert);
    }
  }

    /**
     * Returns an array with all the tables in the database.
     *
     * @return array The numerical array with all the table names.
     */
    function getTables(){
        $res = phive()->flatten($this->loadArray("SHOW TABLES"));
        return $res;
    }

    /**
     * A method that keeps track of connections to various databases, this is possible as all objects in Phive are singletons.
     *
     * @param string $key The config key for the database connection info.
     * @param int $num The node number in case the config key points to an array of node configs.
     *
     * @return SQL The SQL object.
     */
    function doDb($key, $num = null){
        if(empty($key)){
            return null;
        }

        if(empty($this->exts[$key.$num])){
            $db = new SQL( $num === null ? $key : $this->getSetting($key)[$num] );
            $db->setSetting('global_tables', []);
            $db->setSetting('sharding_status', false);
            $this->exts[$key.$num] = $db;
        }

        return $this->exts[$key.$num];
    }

    // TODO henrik remove this
  function replaceTable($tbl, $key = 'archive'){
    $db = $this->doDb($key);
    $db->query("DROP TABLE `$tbl`");
    $db->query($this->getCreate($tbl));
    foreach($this->loadArray("SELECT * FROM `$tbl`") as $el)
      $db->insertArray($tbl, $el);
  }

    // TODO henrik remove this
  function updateTblSchema($tbl, $key = 'archive'){
    $struct_diff = $this->updateStructure($tbl, $key);
    $index_diff = $this->updateIndexes($tbl, $key);
    return array('struct_diff' => $struct_diff, 'index_diff' => $index_diff);
  }

    // TODO henrik remove this
  function updateIndexes($tbl, $key = 'archive'){
    $sql 	= "SHOW INDEX FROM $tbl";
    $local 	= $this->loadArray($sql, 'ASSOC', 'Key_name');
    $rem_db = $this->doDb($key);
    $remote = $rem_db->loadArray($sql, 'ASSOC', 'Key_name');
    if(empty($remote))
      return false;

    foreach($remote as $rf => $rst){
      if(empty($local[$rf]))
        $rem_db->query("ALTER TABLE `$tbl` DROP INDEX `$rf`");
    }

    foreach($local as $f => $st){
      if($st['Key_name'] == 'PRIMARY')
        $idx_type = 'PRIMARY';
      else
        $idx_type = empty($st['Non_unique']) ? 'UNIQUE' : 'INDEX';

      foreach(array('Cardinality') as $remove){
        unset($remote[$f][$remove]);
        unset($local[$f][$remove]);
      }

      if($remote[$f] != $local[$f]){
        $rem_db->query("ALTER TABLE `$tbl` DROP INDEX `$f`");
        $rem_db->query("ALTER TABLE `$tbl` ADD $idx_type `$f` ( `{$st['Column_name']}` ) ");
      }
    }

    return array_diff_assoc($local, $remote);
  }

    // TODO henrik remove this
  function updateStructure($tbl, $key = 'archive'){
    $sql 	= "SHOW COLUMNS FROM $tbl";
    $local 	= $this->loadArray($sql, 'ASSOC', 'Field');
    $rem_db = $this->doDb($key);
    $remote = $rem_db->loadArray($sql, 'ASSOC', 'Field');
    if(empty($remote)){
      $rem_db->query($this->getCreate($tbl));
      return 'new';
    }

    foreach($local as $f => $st){
      $def = '';
      if(!empty($st['Default']) || $st['Default'] === '0')
        $def = 'DEFAULT '.(strpos($st['Default'], 'TIMESTAMP') === false ? "'{$st['Default']}'" : $st['Default']);
      if(empty($remote[$f]))
        $rem_db->query("ALTER TABLE `$tbl` ADD `$f` {$st['Type']} NOT NULL $def");
      else if($remote[$f] != $local[$f])
        $rem_db->query("ALTER TABLE `$tbl` CHANGE `$f` `$f` {$st['Type']} NOT NULL $def");
    }

    foreach($remote as $rf => $rst){
      if(empty($local[$rf]))
        $rem_db->query("ALTER TABLE `$tbl` DROP `$rf`");
    }

    return array_diff_assoc($local, $remote);
  }

    //todo wont work with new optimizations
    // TODO henrik remove this
  function switchToSlave(){
    $this->_nhandle = $this->_handle;
    $slave 			= $this->getSetting('slave_config');
    $this->_handle 	= $this->_shandle = mysqli_connect($slave["hostname"], $slave["username"], $slave["password"]);
    if($this->_handle){
      $this->query("SET NAMES '" . $this->getSetting('charset') . "'");
      $this->query("SET CHARACTER SET " . $this->getSetting('charset'));
    }
    return $slave;
  }

    //todo wont work with new optimizations
    // TODO henrik remove this
  function toSlave(){
    if($this->getSetting('select_from_slave') == true){
      if(empty($this->_shandle))
        $this->switchToSlave();
      else
        $this->_handle = $this->_shandle;

      if($this->_handle === false)
        $this->toNormal();
      else
        mysqli_select_db($this->_handle, $slave['database']);
    }
  }

    //todo wont work with new optimizations
    // TODO henrik remove this
  function toNormal(){
    if($this->getSetting('select_from_slave') == true){
      $this->_handle = $this->_nhandle;
      mysqli_select_db($this->_handle, $this->getSetting('database'));
    }
  }

  /**
   * Alias for SQL::tblIs($tbl, 'sharded_tables')
   *
   * @see SQL::tblIs()
   *
   * @param string $tbl The table we want to check sharding status on.
   *
   * @return bool True if sharded, false otherwise.
   */
    function isSharded($tbl){
        return $this->tblIs($tbl);
    }

    // TODO henrik remove this
    function joinTbl(&$to, $tbl, $key, $cols = []){
        if(empty($to))
            return;
        $ids     = phive()->arrCol($to, $key);
        $str     = $this->makeIn($ids);
        $col_str = empty($cols) ? '*' : implode(',', $cols);
        //file_put_contents('dump.sql', "SELECT $col_str,id FROM $tbl WHERE id IN($str)");
        $from    = $this->shs('merge', '', null, $tbl)->loadArray("SELECT $col_str,id FROM $tbl WHERE id IN($str)", 'ASSOC', 'id');
        //print_r(array_slice($from, 0, 2, true));
        $keys    = array_keys(current($from));
        $keys    = array_combine($keys, $keys);
        unset($keys['id']);
        foreach($to as &$sub)
            $sub = phive()->mapit($keys, $from[$sub[$key]], $sub);
    }

    /**
     * Here we wrap some common logic that is used to determine what category a table belongs to, if it is sharded
     * global or master only.
     *
     * @param string $tbl The table name.
     * @param string $setting_key The config key to the list of table names in a certain category, ex: global_tables or sharded_tables.
     * @param bool $check_if_shard Boolean used to prevent issues with having a shard connection and then running this on it, that could cause
     * the shard to re-connect when it is already connected.
     *
     * @return bool True if it is in the indicated category, false otherwise.
     */
    function tblIs($tbl, $setting_key = 'sharded_tables', $check_if_shard = true){
        if($this->getSetting('sharding_status') !== true)
            return false;
        if($check_if_shard && $this->is_shard)
            return false;
        return in_array($tbl, $this->getSetting($setting_key));
    }

    /**
     * Typically used in order to perform updates and inserts on a global table, those two operations need to be performed
     * on all the nodes in the same way.
     *
     * TODO henrik $default and $return_first are not really used properly here, do we even need them as args to this method?
     *
     * @param string $table The table.
     * @param string $method The method in this class.
     * @param array $args The arguments to pass to the method.
     * @param mixed $default ?
     * @param bool $return_first ?
     *
     * @return ?
     */
    function doGlobal($table, $method, $args, $default = '', $return_first = false){
        if($this->isGlobal($table)){
            if($this->getSetting('shs_synced') === true){
                $this->applySynced($method, $args, '', $default, $return_first);
            }else{
                //Note that we set return to true to block until all inserts / updates have finished, in order to avoid an undefined situation
                // TODO henrik remove this.
                $this->doParaSql($method, $args, true, false, $default);
            }
        }
        return $default;
    }

    /**
     * Simple check on a table to know if it is global or not.
     *
     * @param string $tbl The table.
     *
     * @return bool True if it is global, false otherwise.
     */
    function isGlobal($tbl){
        return $this->tblIs($tbl, 'global_tables');
    }

    /**
     * Checks if a table is an archive
     * @param string $table
     * @return bool
     */
    public function isArchive(string $table): bool
    {
        return $this->tblIs($table, 'archive_tables');
    }

    /**
     * This is the method used to query all shards at the same time.
     *
     * Raw query on all nodes, no need for a return value:
     * phive('SQL')->shs('')->query("UPDATE race_entries SET prize = 0, spot = 0 WHERE r_id = {$r['id']}");
     *
     * Merge result from all nodes (typically what you want) and sort by race balance descending
     * $entries = phive('SQL')->shs('merge', 'race_balance', 'desc')->loadArray($str);
     *
     * Don't merge result (typically if you want to debug to see what each node is returning)
     * $a_3d_array = phive('SQL')->shs('')->loadArray("SELECT * FROM table WHERE date = '2017-10-10'");
     *
     * Find a single row in all nodes (NOTE that this should be a common thing as it is a "heavy" operation):
     * $user = phive('SQL')->shs()->loadAssoc("SELECT * FROM users WHERE email = 'foo@bar.com'")
     *
     * Get the cash balance of the player with email foo@bar.com (NOTE that this should be a common thing as it is a "heavy" operation):
     * $user = phive('SQL')->shs()->getValue("SELECT cash_balance FROM users WHERE email = 'foo@bar.com'")
     *
     * Get 50 random tournament entries with user_id as the key, note the argument to array_slice to preserve the user id as key:
     * $entries = array_slice($this->db->shs()->loadArray("SELECT * FROM tournament_entries ORDER BY RAND() LIMIT $num_players", 'ASSOC', 'user_id'), 0, $num_players, true);
     *
     * TODO henrik get rid of $tbl and refactor all invocations.
     *
     * @link https://wiki.videoslots.com/index.php?title=Shard_Logic More info in the wiki.
     * @see Phive::sort2d()
     *
     * @param string $do The action to perform, can be either merge or sum.
     * @param string $sort_key Since the data is fetched from multiple databases there is no way an ORDER BY will work properly, this argument
     * specifies which key / column the merged result should be sorted on.
     * @param string|array $flags If string it's just asc or desc, otherwise an array with asc and desc in case we're sorting on multiple columns.
     * @param string $tbl If supplied a check on the table will happen, ie if the table is not sharded we don't set the loop params.
     *
     * @return SQL The SQL object for chaining.
     */
    function shs($do = 'merge', $sort_key = '', $flags = null, $tbl = ''){
        //Do we have a sharded table? If not abort.
        if(!empty($tbl) && !$this->isSharded($tbl) && !$this->isGlobal($tbl))
            return $this;
        if(!empty($this->getShards()))
            $this->shard_loop_params = [$do, $sort_key, $flags];
        return $this;
    }

    /**
     * Used to apply a member method on data that is present in several boxes, ie sharded or global tables.
     *
     * A common scenario for global tables is an update that needs to happen on all nodes whereas the most common scenario for sharded
     * tables is to fetch an aggregated result from all nodes.
     *
     * TODO henrik refactor the name of this method, it's also used for shards, use something like loopCluster
     * TODO henrik is $default really used or can it be removed? A load2DArr is using it here but looks like we don't need to pass it along or?
     *
     * @param string $method The method of this class to run.
     * @param array $args The arguments to pass to the method.
     * @param $default ?
     * @param bool $return_first True in case we just want to return the result as soon as it is found, ie in case something like getValue is used with shs().
     *
     * @return mixed The result of the database query.
     */
    function loopGlobal($method, $args, $default = '', $return_first = false){
        if(empty($this->shard_loop_params) || $this->is_shard || $this->getSetting('sharding_status') !== true)
            return $default;
        $tmp = $this->shard_loop_params;

        // TODO henrik this is BS, first unshifting and then adding to the end to get the correct arg order for the call below,
        // should be refactored.
        array_unshift($tmp, $method, $args, $default);
        // We add the return first "hit" or not flag to the end
        $tmp[] = $return_first;

        $this->shard_loop_params = [];
        return call_user_func_array([$this, 'loopShards'], $tmp);
    }

    // TODO henrik, what is the point of having loopGlobal, doGlobal, loopShards and applySynced as separate methods? Can this be refactored into one method?
    function applySynced($method, $args, $action, $default, $return_first = false){
        if($this->is_shard || empty(phive('SQL')->getShards()))
            return $default;
        $res = [];
        foreach($this->getShards() as $id => $shard){
            if ($this->isOnDisabledNode($id)) {
                continue;
            }
            $db     = $this->sh($id);
            $tmp    = call_user_func_array([$db, $method], $args);
            $res[]  = empty($tmp) ? [] : $tmp;
            if($return_first && !empty($tmp)){
                // If we're only looking for one result (eg loadAssoc) then it does not make sense to continue looping the nodes.
                break;
            }
        }
        if($action == 'merge')
            $res = phive()->flatten($res, true);
        return $res;
    }

    /**
     * Used internally in order to run logic over all shards.
     *
     * TODO henrik sum3dAssoc only takes one key (string) as the do only key, the below logic implies that it can also handle an array, fix that. With
     * that in mind do we even need the do_not / do_only logic? Get to the bottom of it.
     *
     * @param string $method The SQL method to execute.
     * @param array $args The arguments to pass to $method.
     * @param mixed $default Optinal default value to return in case we do not execute any logic, that can happen if this object
     * is already in a shard context or if we are not sharded.
     * @param string $do An action to apply to the result, default is to just merge each sub array into one big array.
     * @param string|array $sort_key Which column(s) to sort on after merging the arrays.
     * @param string|array $flags How the column(s) should be sorted asc or desc.
     * @param bool $return_first Whether or not to immediately return the first fetched value, typically used in situations
     * where a method such as getValue() is used with shs().
     *
     * @return mixed The fetched data.
     */
    function loopShards($method, $args, $default, $do = 'merge', $sort_key = '', $flags = null, $return_first = false){
        if(is_string($do) && !empty($do)){
            $tmp            = [];
            $tmp['action']  = $do;
            $tmp['do_only'] = [];
            $tmp['do_not']  = [];
            $do             = $tmp;
        }

        if($this->getSetting('shs_synced') === true){
            $res = $this->applySynced($method, $args, $do['action'], $default, $return_first);
        }else{
            // TODO henrik remove this
            $res = $this->doParaSql($method, $args, true, $do['action'] == 'merge', $default);
        }

        $use_key       = $this->use_key;
        $this->use_key = false;

        if(!empty($sort_key))
            $res = phive()->sort2d($res, $sort_key, $flags);

        if($do['action'] == 'sum'){
            if(!empty($do['do_not'])){
                $do['do_only'] = array_diff(array_keys(current(array_filter($res)[0])), $do['do_not']);
            }

            $res = phive()->sum3dAssoc($res, $do['do_only']);
        }

        if(!empty($use_key) && !is_array($use_key)){
            //print_r($res);
            $res = phive()->reKey($res, $use_key);
        }

        return $res;
    }

    // TODO henrik refactor this, move it into the only place where it is called.
    function makeShardConn(){
        // We need to store the shard count in all cases in order to be able to determine auto increment values.
        $this->setSetting('sh_count', count($this->getSetting('shards')));
        //To prevent endless recursion
        $this->setSetting('shards', false);
        $this->setSetting('global_tables', false);
        $this->is_shard = true;
    }

    /**
     * Wrapper around a common scenario where we want the shards or null if the database is not sharded.
     *
     * @return array|null The shard config array if sharded, null otherwise.
     */
    function getShards(){
        if($this->getSetting('sharding_status') === true)
            return $this->getSetting('shards');
        return null;
    }

    /**
     * The main shard routing logic.
     *
     * @param mixed $el_id Various user related info which can be used in order to extract a user id that in turn is used for routing to the correct shard.
     * @param string $key A hint in case we're looking at an assoc array and the user id is not under the typical user_id key.
     *
     * @return array An array with the shard id in the first position and the shard config in the second.
     */
    function getShardConf($el_id, $key = 'user_id'){
        $sh_conf = $this->getShards();
        if(empty($sh_conf) || $el_id === false) //We're not sharded
            return $this;

        if(is_object($el_id)){
            // If we're looking at a user object we can force the key to be id in case the key is the default user_id.
            // That way we can now make calls like this: $db->sh($user)->loadArray( ... ).
            if(get_class($el_id) == 'DBUser' && $key == 'user_id'){
                $key = 'id';
            }
            $el_id = $el_id->data[$key];
        } else if(is_array($el_id)){
            $el_id = $el_id[$key];
        }

        if(empty($el_id) && $el_id !== 0)
            return $this;

        $sh_id = $el_id % count($sh_conf);
        return [$sh_id, $sh_conf[$sh_id]];
    }

    /**
     * A simpler wrapper around the common scenario when we want to execute a closure on all nodes.
     *
     * @param closure $func The function / closure.
     *
     * @return null
     */
    function loopShardsSynced($func){
        foreach($this->getShards() as $id => $shard){
            $db = $this->sh($id);
            $func($db, $shard, $id);
        }
    }

    /**
     * Simple wrapper to get the number of shards.
     *
     * @return int The shard count.
     */
    function shCount(){
        $sh_count = $this->getSetting('sh_count');
        return empty($sh_count) ? count($this->getSetting('shards')) : $sh_count;
    }

    /**
     * Main entry point for shard selection.
     *
     * Get all deposits from user with id 123:
     * phive('SQL')->sh(123)->loadArray("SELECT * FROM deposits WHERE user_id = 123")
     *
     * Get one deposit from user with id 123 with deposit id 1234:
     * phive('SQL')->sh(123)->loadAssoc("SELECT * FROM deposits WHERE id = 1234")
     *
     * Get total deposit sum for user with id 123:
     * phive('SQL')->sh(123)->getValue("SELECT SUM(amount) FROM deposits WHERE user_id = 123")
     *
     * Update / insert a row:
     * phive('SQL')->sh(123)->save('table', $row)
     *
     * Atomic increase of value by 5 (note the user id at the end, it's needed for correct shard picking):
     * phive('SQL')->incrValue('table', 'number_of_people', ['id' => $row['id']], 5, [], 123);
     *
     * Using keys:
     * An int will not need any hinting.
     * An array / object where the key is user_id doesn't need hinting either.
     * A user object / array needs this: ->sh($u, 'id)->...
     *
     * TODO henrik do like this instead: if(empty($el_id) && $el_id !== 0)
     * TODO henrik get rid of $tbl and refactor all invocations.
     *
     * @param mixed $el_id An entity that contains a user id that can be used for shard routing.
     * @param string $key A hint in case we're looking at an assoc array and the user id is not under the typical user_id key.
     * @param string $tbl Table hinting in case the table is sharded on some systems but not on others.
     * @param $force_new=false
     *
     * @return xxx
     */
    function sh($el_id, $key = 'user_id', $tbl = '', $force_new = false){
        if($el_id === null){
            $this->shard_err = true;
        }

        if($el_id === ''){
            $this->shard_err = true;
        }

        if($el_id === []){
            $this->shard_err = true;
        }

        if($el_id === false){
            $this->shard_err = true;
        }

        if(!empty($tbl) && !$this->isSharded($tbl))
            return $this;
        $res = $this->getShardConf($el_id, $key);
        if(is_object($res)) //We're not sharded
            return $res;
        list($sh_id, $tmp_conf) = $res;
        if(empty($this->shards[$sh_id]) || $force_new){
            $tmp_conf['extra']['auto_increment_increment'] = $this->shCount();
            $tmp_conf['extra']['auto_increment_offset']    = $sh_id + 1;
            $tmp = new SQL($tmp_conf);
            $tmp->my_sh_id = $sh_id;
            $tmp->makeShardConn();
            if($force_new)
                return $tmp;
            $this->shards[$sh_id] = $tmp;
        }
        return $this->shards[$sh_id];
    }

    // TODO henrik remove this.
    function doMaster($func, $args, $id = '', $key = 1, $table = ''){
        //If we're not using a shard config we're already in the master and do nothing.
        if(!isset($this->my_sh_id))
            return;

        //If we don't want to sync to master or we're not sharded we do nothing
        if($this->getSetting('sync') != 'master' || $this->getSetting('sharding_status') !== true)
            return;

        if(empty($table) || !$this->tblIs($table, 'sharded_tables', false))
            return;

        $sql = phive('SQL');
        $old_setting = $sql->getSetting('sharding_status');
        //Prevent recursive call to children
        $sql->setSetting('sharding_status', false);
        //If we have an id from the slave and the insert array doesn't already contain an id
        if(!empty($id) && empty($args[$key]['id']))
            $args[$key]['id'] = $id;

        call_user_func_array([$sql, $func], $args);
        $sql->setSetting('sharding_status', $old_setting);
    }

    /**
     * Wrapper around a common conditional.
     *
     * @return bool True if the handle exists, false otherwise.
     */
    function hasHandle() {
        return $this->_handle !== false;
    }

  // TODO henrik remove this
  function query_($string){
      error_log($string);
      return $this->query($string);
  }

  /**
   * Main query function that invokes mysqli_query().
   *
   * This is mostly a wrapper around mysqli_query() and loopGlobal() with some debugging happening in case it's configured.
   *
   * @param string $string The SQL query string.
   *
   * @return bool True if the query was successful, false otherwise, **note** that **only broken** SQL will result in a false return.
   * Trying to update a nonexisting row etc will return **true** as the SQL syntax is still correct.
   */
    function query($string) {

        if (!empty($this->shard_err) && $this->getSetting('log_empty_shard')) {
            phive('Logger')->getLogger('SQL')->logTrace('SHARD_ERROR: '.$string, 'ERROR');
            $this->shard_err = false;
        }

        $this->query_string = $string;

        if($this->connectIfDisconnected() === false)
            return false;

        //var_dump($string);
        /*
        if($this->hasHandle() === false){
            $handle = $this->connect();
            if($handle === false)
                return false;
        }
        */

        //echo ' connected ';

        if($this->getSetting('debug_sql') === true) {
            $ins_str = mysqli_real_escape_string($this->_handle, $string);
            mysqli_query($this->_handle, "INSERT INTO `sql_log` (`dump_txt`, `tag`) VALUES ('$ins_str', 'debug');");
            $res     = mysqli_query($this->_handle, "EXPLAIN " . $string);
            if (is_resource($res)) {
                $LogMessage 	=  "'$string'\n---";
                $LogMessage 	.= print_r(mysqli_fetch_assoc($res), true);
                error_log($LogMessage, E_USER_NOTICE);
            }
        }

        if((property_exists($this, 'debug') && $this->debug === true) || (array_key_exists('sql_debug', $GLOBALS) && $GLOBALS['sql_debug'] === true))
            $t_start = microtime(true);

        $res = mysqli_query($this->_handle, $string);

        if((property_exists($this, 'debug') && $this->debug === true) || (array_key_exists('sql_debug', $GLOBALS) && $GLOBALS['sql_debug'] === true)){
            $t_end = microtime(true) - $t_start;
            if($t_end > $this->debug_threshold) {
                $GLOBALS['sql_profiling'][] = array('sql' => $string, 'time' => $t_end);
            }
        }

        if ($this->keep_last_error_message && $res === false) {
            $this->last_error_message = "MYSQLI ERROR - ". mysqli_error($this->_handle) . ' - Query: ' . $string;
        }

        //Will only loop if prepared with $sql->shs()->...
        $res = $this->loopGlobal('query', [$string], $res, false);

        if ($res === false) { //TODO refactor this CH28869 /Ricardo
            $err_str = "MYSQLI ERROR - ". mysqli_error($this->_handle) . ' - Query: ' . $string;
            if($this->getSetting('debug_error') === true) {
                file_put_contents('/tmp/mysqlerror.log', "$err_str\n", FILE_APPEND);
            }
            if($this->getSetting('debug_error_log', true) === true) {
                // Skip queries that start with EXPLAIN. We use it in sanitizeString and don't need to log them, as they are not SQL.
                $has_explain = preg_match('/^EXPLAIN\b/', $string);
                if(!$has_explain) {
                    error_log($err_str);
                }
            }
            return false;
        } else {
            $this->resource = $res;
            return true;
        }

    }

    // TODO henrik remove this
  function addDebug($str){
    $GLOBALS['sql_profiling'][] = $str;
  }

    /**
     * Clears the sql_profiling global, typically used in testing scenarios.
     *
     * @return null
     */
    function clearDebug(){
        $GLOBALS['sql_profiling'] = array();
    }

    /**
     * Logic for printing debug / profiling info.
     *
     * TODO henrik remove $tag
     *
     * @param bool $save True if the info should be saved in the database, false otherwise.
     * @param bool $sort True if we want to sort the result by execution time with slowest first.
     * @param $tag ?
     *
     * @return null
     */
    function printDebug($save = false, $sort = true, $tag = ''){

        if($sort)
            $tmp = phive()->sort2d($GLOBALS['sql_profiling'], 'time', 'desc');
        else
            $tmp = $GLOBALS['sql_profiling'];
        if($save){
            $aSqlDump = $tmp;//var_export($tmp, true);
            $i = 1;
            foreach($aSqlDump as $key => $aQueryLog){
                $aQueryLog['sql'] = mysqli_real_escape_string($this->_handle, $aQueryLog['sql']);
                //$aQueryLog['time'] = ($aQueryLog['time']*1000);
                if($i< 10){
                    echo $aQueryLog['time'] . PHP_EOL;
                    $i++;
                }
                //echo $aQueryLog['sql'] . PHP_EOL;
                //echo "INSERT INTO `sql_log` (`dump_txt`, `exec_time`) VALUES ('" . $aQueryLog['sql'] . "', " . $aQueryLog['time'] . ");" . PHP_EOL;
                mysqli_query($this->_handle, "INSERT INTO `sql_log` (`dump_txt`, `exec_time`) VALUES ('" . $aQueryLog['sql'] . "', " . $aQueryLog['time'] . ");");
                //$this->insertArray('sql_log', array('dump_txt' => var_export($tmp, true), 'tag' => $tag), null, false, false);
            }
        } else{
            print_r($tmp);
        }
    }

  // TODO henrik get rid of this, use getValue (->result()) or loadAssoc instead (->fetch()).
  function queryAnd($string){
    $this->query($string);
    return $this;
  }

  // TODO henrik remove this
  function escAndQuery($str){
    return $this->queryAnd( $this->escape($str, false) );
  }

    /**
     * Returns the number of affected rows as a result of the latest query.
     *
     * @return int The number of affected rows.
     */
    function affectedRows() {
        if($this->hasHandle() === false)
            return false;

        if($this->resource === false) {
            die("No resource for checking affected rows.");
            return false;
        }

        return mysqli_affected_rows($this->_handle);
    }

  // TODO Henrik get rid of this, refactor all invocations.
  function insertId() { return $this->insertBigId(); }

    /**
     * Returns the next value the auto incrementing column will have for a certain table.
     *
     * @param string $table The table.
     *
     * @return int The value.
     */
    function nextAutoId($table){
        $status = $this->loadAssoc("SHOW TABLE STATUS LIKE '$table'");
        $id = $status['Auto_increment'];
        return $id;
    }

    /**
     * Used to get the latest auto id that was created.
     *
     * This is useful to get the primary auto incrementing id of a just inserted row.
     *
     * @return int The id.
     */
    function insertBigId(){
        return $this->queryAnd("SELECT LAST_INSERT_ID()")->result();
    }

  // TODO henrik remove this
  function inStrings($str, $sep = ','){
    return empty($str) ? '' : "'".str_replace($sep, "'$sep'", $str)."'";
  }

  // TODO henrik remove this
  function timestamp($time = ''){
    $time = empty($time) ? time() : $time;
    return date('Y-m-d H:i:s', $time);
  }

    /**
     * This method can be used in case the row count of a certain SELECT is desired without actually fetching said rows.
     *
     * @return int The count.
     */
    function numRows(){
        if($this->hasHandle() === false || empty($this->resource))
            return false;
        return mysqli_num_rows($this->resource);
    }

    /**
     * The main logic that other methods that return one or multiple rows wraps.
     *
     * @param string $result_type The return type, in rare cases  an associative array is not desired or needed, then NUM can be passed in instead.
     *
     * @return array The result.
     */
    function fetch($result_type = 'ASSOC') {
        if(empty($this->resource))
            return false;
        if ($result_type == 'NUM')
            return mysqli_fetch_row($this->resource);
        else if ($result_type == 'ASSOC')
            return mysqli_fetch_assoc($this->resource);
        else
            return mysqli_fetch_array($this->resource);
    }

    // TODO henrik remove, use loadAssoc instead.
  function fetchResult($sql){
    return $this->queryAnd($sql)->fetch();
  }

  // TODO henrik use loadAssoc instead.
  function loadObject($sql){
    $arr = $this->queryAnd($sql)->fetch();
    $obj = new stdClass();
    foreach($arr as $key => $value)
      $obj->$key = $value;
    return $obj;
  }

  /**
   * Get's the maximum id of a table.
   *
   * @param string $tbl The table.
   * @param string $col The column, typically id.
   *
   * @return int The value.
   */
    function getMaxId($tbl, $col = 'id'){
        return (int)$this->getValue("SELECT COALESCE($col, 0) FROM $tbl ORDER BY id DESC LIMIT 0,1");
    }

    /**
     * Shards out a single database over X amount of shards.
     *
     * @param int $sh_num Shard count.
     * @param array $tbls An array of tables to shard out.
     * @param array $row_cnt Row count, chunk size that gets sharded out in each iteration.
     * @param bool $truncate Whether or not to truncate the table on the shard before starting to copy data.
     *
     * @return int The shard count.
     */
    function shardOut($sh_num, $tbls, $row_cnt = 10000, $truncate = false){
        $shard_db  = $this->sh($sh_num);
        $shard_cnt = count($this->getSetting('shards'));

        foreach($tbls as $tbl){
            if(is_array($tbl))
                list($tbl, $id_col) = $tbl;
            else
                $id_col = $tbl == 'users' ? 'id' : 'user_id';

            echo "Shard: $sh_num, Table: $tbl\n";

            if($truncate)
                $shard_db->truncate($tbl);
            $remaining = 1;
            // 1. Check max id on node, and get it.
            $max_id = $shard_db->getMaxId($tbl);
            while(true){
                $start_time = time();
                // 2. Select all rows with higher id on master with modulo sh_num
                // 3. Insert them into node
                $sql_str = "SELECT * FROM $tbl WHERE id > $max_id AND $id_col % $shard_cnt = $sh_num LIMIT 0, $row_cnt";
                //phive()->dumpTbl('sql', $sql_str);
                $rows    = $this->loadArray($sql_str);
                if(!empty($rows)){
                    if(!$shard_db->insertTable($tbl, $rows, [], [], 100)){
                        echo "No rows";
                        error_log($sql_str);
                        break;
                    }
                }

                // 4. Update shard_status (only used to see progress of sharding) with new max id etc
                $max_id     = $shard_db->getMaxId($tbl);
                $master_max = $this->getMaxId($tbl);
                $insert = [
                    'tbl'       => $tbl,
                    'node_num'  => $sh_num + 1,
                    'cur_id'    => $max_id,
                    'max_id'    => $master_max,
                    'remaining' => $master_max - $max_id
                ];
                $duration = time() - $start_time;
                $this->save('shard_status', $insert);

                //No more rows to do.

                if(empty($rows))
                    break;
            }
        }
        return $sh_num;
    }

    /**
     * This applies a closure to a stream of results.
     *
     * Sometimes we want to work with data sets that are simply too big to load into memory, in that case we can simply
     * pass in a closure that takes each row as its argument and we only keep one row in memory at a time.
     *
     * Note that using this method in order to delete rows results in an undefined situation! Use SQL::deleteBatched() for that instead.
     *
     * @see SQL::deleteBatched()
     *
     * @param string $str The SELECT query that will generate the rows.
     * @param closure $func The function / closure that will be applied to each row.
     * @param bool $return Whether or not to build a return / result set from each application of the closure, typically we do not want that.
     *
     * @return array The result array if we want to build a result set, an empty array otherwise.
     */
    function applyToRows($str, $func, $return = false){
        $res = $this->query($str);
        if($res === false || empty($this->resource))
            return false;
        $ret = [];
        while($r = mysqli_fetch_assoc($this->resource)){
            if($return)
                $ret[] = $func($r);
            else
                $func($r);
        }
        return $ret;
    }

    /**
     * Uses batching to avoid table locks with DELETE WHERE, or massive data loads if we're
     * forced to load the whole dataset at once.
     *
     * @param string $str The SQL string used for fetching rows.
     * @param closure $deleteFunc The function executed in order to delete each row.
     * @param int $batch_size The batch size to use, defaults to 1000.
     * @param bool $echo_batch_num Whether or not to echo each batch number in order
     * to provide a status update, defaults to false
     *
     * @return null
     */
    function deleteBatched($str, $deleteFunc, $batch_size = 1000, $echo_batch_num = false){
        $batch_num = 0;
        while(true){
            $res = $this->loadArray($str." LIMIT $batch_size");
            if(empty($res)){
                break;
            }

            if($echo_batch_num){
                echo $batch_num."\n";
            }

            foreach($res as $r){
                $deleteFunc($r);
            }
            $batch_num++;
        }
    }

    /**
     * The base logic behind SQL::loadArray()
     *
     * Fetches a result set in the form of an array from a result resource.
     *
     * @param string $result_type The result type, typically ASSOC.
     * @param string|array $use_key Whether or not to use a result sub key's value for the main / parent key to each sub.
     * @param string $delim The delimiter in case $use_key is an array.
     *
     * @return array The result array.
     */
    function fetchArray($result_type = 'ASSOC', $use_key = false, $delim = '_'){
        $arr = array();
        if(is_string($use_key)){
            while ($r = $this->fetch($result_type))
                $arr[ $r[$use_key] ] = $r;
        }else if(is_array($use_key)){
            //$keys = array_combine($use_key, $use_key);
            while ($r = $this->fetch($result_type)){
                $tmp = array();
                foreach($use_key as $key)
                    $tmp[$key] = $r[$key];
                $arr[ implode($delim, $tmp) ] = $r;
            }
        }else{
            while ($r = $this->fetch($result_type))
                $arr[] = $r;
        }

        return $arr;
    }

  // TODO henrik remove this
  function fetchObjects($use_key = false){
    $arr = $this->fetchArray('ASSOC', $use_key);
    foreach($arr as &$sub){
      $obj = new stdClass();
      foreach($sub as $key => $value)
        $obj->$key = $value;
      $sub = $obj;
    }
    return $arr;
  }

  // TODO henrik refactor all invocations to use loadArray instead and remove this method.
    function loadObjects($sql){
        $res = $this->loadArray($sql);
        $ret = [];
        foreach($res as &$sub){
            $obj = new stdClass();
            foreach($sub as $key => $value)
                $obj->$key = $value;
            $ret[] = $obj;
        }
        return $ret;
    }

    /**
     * Perhaps the most used method in this whole class.
     *
     * It takes a SELECT query and typically returns an array of this format: [0 => ['key' => 'value ... ], 1 => ... ]
     *
     * shs() usage examples:
     * $users = phive('SQL')->shs('sum')->loadArray("SELECT COUNT(id) as count FROM users WHERE DATE(last_login) < '2016-12-01' AND country IN ('SE', 'FI')");
     *
     * Daily user game stats aggregation view example:
     *
     * $dstats = phive("SQL")->shs([
     * 'action' => 'sum',
     * 'do_only' => array_merge($sums_arr, ['gross', 'overall_gross', 'site_gross'])
     * ], '', null, 'users_daily_game_stats')->loadArray($str, 'ASSOC', array('game_ref', 'device_type'));
     *
     * @param string $sql The SELECT query string.
     * @param string $result_type The result type, typically ASSOC.
     * @param string|array|bool $use_key Whether or not to use a result sub key's value for the main / parent key to each sub, string / array for the key(s) to use
     * false to not use a key.
     * @param string $delim The delimiter in case $use_key is an array.
     *
     * @return array The result array.
     */
    function loadArray($sql, $result_type = 'ASSOC', $use_key = false, $delim = '_'){
        $args = func_get_args();
        $this->use_key = $use_key;
        //We start out with trying to loop all nodes
        $res = $this->loopGlobal('loadArray', $args, 'na', false);
        $this->use_key = false;
        if($res !== 'na')
            return $res;
        //If sharding has been turned off we fetch from a single database
        //$this->toSlave();
        $this->query($sql);
        //$this->toNormal();
        return $this->fetchArray($result_type, $use_key, $delim);
    }

    /**
     * This is for grouping the result set on a certain key in the result set.
     *
     * Ex:
     * $res = $sql->load2DArr("SELECT * FROM users WHERE country IN('SE', 'FI') LIMIT 20", 'country');
     * The above $res will look like this: ['FI' => [0 => ['id' => ...] ... ], 'SE' => [0 => ['id' => ...] ... ]]
     *
     * Used with shs() like this: shs(false ... ) -> no merge of result
     *
     * @param string $sql The SELECT query string.
     * @param string $key The column / key to for the main array keys.
     *
     * @return array The result array.
     */
    function load2DArr($sql, $key){
        $arr = array();
        $this->use_key = $key;
        $res = $this->loopGlobal('loadArray', [$sql, 'ASSOC', $key], 'na', false);
        $this->use_key = false;
        if($res !== 'na'){
            $arr = [];
            foreach($res as $top_key => $sub){
                foreach($sub as $key => $r)
                    $arr[ $key ][] = $r;
            }
            return $arr;
            //    return phive()->sum3dAssoc($res);
        }
        //$this->toSlave();
        $this->query($sql);
        //$this->toNormal();
        while ($r = $this->fetch())
            $arr[ $r[$key] ][] = $r;
        return $arr;
    }

    /**
     * Simple wrapper around the scenario when we have a simple query.
     *
     * Example: arrayWhere('users', ['country' => 'SE']) which is more convenient than writing the whole statment and passing it to loadArray() directly.
     *
     * @param string $tbl The table.
     * @param array|string $where The where clause.
     * @param string $use_key Key to use, otherwise we get a numerical array of associative arrays.
     * @param bool $no_empty Whether or not to filter out empty values from the where array, typically needed in case it is generated programmatically.
     *
     * @return array The result array.
     */
    function arrayWhere($tbl, $where, $use_key = false, $no_empty = false){
        $where = $this->makeWhere($where, $no_empty);
        $str = "SELECT * FROM $tbl $where";
        return $this->loadArray($str, 'ASSOC', $use_key);
    }

    /**
     * Typically used to generate arrays for use in drop downs.
     *
     * Ex result: ['SE' = 'Sweden', 'FI' => 'Finland' ... ] for a country drop down where the iso2 code will be used
     * in the database and the value is what will display for each option.
     *
     * @param string $sql The SELECT query.
     * @param string $kfield The column / field that will be used for the key.
     * @param string|array $vfield The column / field that will be used for the value.
     * @param string $delim If the $vfield is an array the columns will be concatenated with this separator.
     *
     * @return array The result array.
     */
    function loadKeyValues($sql, $kfield, $vfield, $delim = ' - '){
        $rarr = array();
        foreach($this->loadArray($sql) as $row){
            if(is_string($vfield))
                $rarr[$row[$kfield]] = $row[$vfield];
            else{
                $value = '';
                foreach ($vfield as $col)
                    $value .= $row[$col].$delim;
                $rarr[$row[$kfield]] = phive()->trimStr($delim, $value);
            }
        }
        return $rarr;
    }

    /**
     * Gets an array of all columns in a table.
     *
     * @param string $tbl The table.
     * @param bool $not_primary Whether or not to include the primary key or not.
     *
     * @return array The result array.
     */
    function getColumns($tbl, $not_primary = false){
        $rarr = array();
        foreach($this->loadArray('SHOW COLUMNS FROM '.$tbl) as $col_info){
            if($not_primary && $col_info['Key'] == 'PRI')
                continue;
            $rarr[ $col_info['Field'] ] = $col_info['Field'];
        }
        return $rarr;
    }

  // TODO henrik remove this
  function allButPri($tbl){
    return "`".implode('`,`', $this->getColumns($tbl, true))."`";
  }

  // TODO henrik remove this
  function insertPost($table, $post = array(), $where = null, $replace = false){
    $cols = $this->getColumns($table);
    $post = empty($post) ? $_POST : $post;
    $data = array();
    foreach($post as $key => $value){
      $key = strtolower($key);
      if(in_array($key, $cols))
        $data[$key] = $value;
    }
    return $this->insertArray($table, $data, $where, $replace);
  }


  /**
   * Loads a single row from a table.
   *
   * Totally contrieved shs usage example (if you know the user id you use sh() but whatever, you get the point):
   * $user = phive('SQL')->shs('merge')->loadAssoc("SELECT * FROM users WHERE id = 20916");
   * print_r($user);
   *
   * Use shs('sum')->loadArray( ... ) if you need aggregations.
   *
   * @param string $sql The SELECT statement, can be empty.
   * @param string $tbl The table, must be non empty in case $sql is empty.
   * @param string|array $where The where clause, must be non empty if $sql is empty.
   * @param bool $limit Whether or not to limit the query to one row, note that this method will only return one entry regardless.
   *
   * @return array The result array.
   */
    function loadAssoc($sql, $tbl = '', $where = '', $limit = false){
        $where_str = $this->makeWhere($where);
        if(!empty($tbl))
            $sql = "SELECT * FROM $tbl $where_str ".$sql;
        else if(!empty($where))
            $sql .= " $where_str";
        if($limit)
            $sql .= " LIMIT 0,1";
        //$this->toSlave();
        $res = $this->loopGlobal('loadAssoc', [$sql], 'na', true);
        if($res !== 'na'){
            return $res;
        }else{
            $this->query($sql);
            return $this->fetch();
        }
        //$this->toNormal();
    }

    /**
     * Returns a simple array of values.
     *
     * This method will in effect retrieve a column from the table.
     *
     * @param string $sql The SELECT query.
     * @param string $by_key The column to fetch.
     * @param string $res_key Optional key to use in case we want to use another column as the key instead of a simple numerical array.
     *
     * @return array The result array.
     */
    function load1DArr($sql, $by_key, $res_key = ''){
        $arr = array();
        if(empty($res_key)){
            foreach($this->loadArray($sql) as $row)
                $arr[] = $row[ $by_key ];
        }else{
            foreach($this->loadArray($sql) as $row)
                $arr[ $row[$res_key] ] = $row[ $by_key ];
        }
        return $arr;
    }

    /**
     * Alias of a common load1DArr scenario.
     *
     * @param string $sql The SELECT query.
     * @param string $col The column to fetch.
     *
     * @return array The result array.
     */
    function loadCol($sql, $col){
        return $this->load1DArr($sql, $col);
    }

    /**
     * Used to get a single value from the database.
     *
     * Completely contrieved shs() example (use sh() if you know the user id, but you get the point):
     * echo phive('SQL')->shs('merge')->getValue("SELECT username FROM users WHERE id = 20916");
     *
     * Use shs('sum')->loadArray( ... )[0] to do aggregations, example:
     * echo current(phive('SQL')
     * ->shs('sum', '', null, 'users')
     * ->loadArray("SELECT count(*) FROM users u WHERE DATE(last_login) >= '2016-04-01' AND DATE(last_login) <= '2016-10-01'")[0]);
     *
     * @param string $sql The SELECT statement.
     * @param string $field Needed in case $sql is empty.
     * @param string $tbl Needed in case $sql is empty.
     * @param string|array $where Needed in case $sql is empty.
     *
     * @return int|string The value.
     */
    function getValue($sql, $field = '', $tbl = '', $where = ''){
        if(!empty($tbl))
            $sql = "SELECT $field FROM $tbl ".self::makeWhere($where);
        $res = $this->loopGlobal('getValue', [$sql], 'na', true);
        if($res !== 'na'){
            return $res[0];
        }else{
            $this->query($sql);
            return $this->result();
        }
        //$this->toSlave();
        //$this->query($sql);
        //$this->toNormal();

        //return $this->result();
    }

    /**
     * This is the base logic for fetching only one value from the database.
     *
     * TODO henrik refactor out the args, $id is not used and $row seems pointless.
     *
     * @used-by SQL::getValue()
     *
     * @return mixed The value if successful, false otherwise.
     */
    function result($id = null, $row = 0){
        if(empty($this->resource))
            return false;
        if (mysqli_num_rows($this->resource) > $row){
            $this->resource->data_seek($row);
            $datarow = $this->resource->fetch_array();
            return $datarow[0];
        }else
            return false;
    }

    // TODO henrik remove this.
  function close (){
      if($this->hasHandle() === false)
          return true;
      $this->con_status = false;
      return mysqli_close($this->_handle);
  }

    /**
     * Getter for the database resource handle.
     *
     * @return object|bool The handle, or false.
     */
    function getHandle() { return $this->_handle; }

  // TODO henrik remove this
  function status (){
    if($this->hasHandle() === false)
      return $this->error;

    return mysqli_stat($this->_handle);
  }

  // TODO henrik remove this
  function quoted($str){ return strpos($str, "'") === 0 ? true : false; }

  /**
   * This is basically a wrapper around mysqli_real_escape_string()
   *
   * @link https://www.php.net/manual/en/mysqli.real-escape-string.php PHP.net docs.
   *
   * @param string $str The string to escape.
   * @param boole $quote Whether or not to encapsulate the string in single quotes or not.
   *
   * @return string The escaped string.
   */
    function escape($str = null, $quote = true){
        if($this->connectIfDisconnected() === false)
            return false;
        $str = mysqli_real_escape_string($this->_handle, $str);
        return $quote === true ? "'" . $str . "'" : $str;
    }

    /**
     * Removes certain key words and characters to prevent SQL injection.
     *
     * @param string $str The string to be filtered.
     *
     * @return string The cleaned up string.
     */
    function removeQuery($str){
        return str_replace(['select', '(', ')', 'from'], '', strtolower($str));
    }

    /**
     * This is basically a wrapper around mysqli_real_escape_string()
     *
     * @link https://www.php.net/manual/en/mysqli.real-escape-string.php PHP.net docs.
     *
     * @param string $str The string to escape.
     *
     * @return string The escaped string.
     */
    function realEscape($str){
        if($this->connectIfDisconnected() === false)
            return false;
        return mysqli_real_escape_string($this->_handle, $str);
    }

    // TODO henrik remove this, replace all calls with removeQuery calls instead.
  function stripQuotes($str){
    return str_replace(array("'", '"'), '', $str);
  }

  // TODO henrik remove this
  function escapeArray($arr, $quote = true){
    $rarr = array();
    foreach($arr as $key => $value)
      $rarr[$key] = $this->escape($value, $quote);
    return $rarr;
  }

    /**
     * Cleans up desc and asc to prevent SQL injections.
     *
     * @param string $str The SQL partial string to check.
     *
     * @return string The clean keyword.
     */
    function escapeAscDesc($str){
        if (strcasecmp($str,'desc') == 0)
            return 'DESC';
        elseif (strcasecmp($str,'asc') == 0)
            return 'ASC';
        else
            return ''; // equivalent of doing ORDER BY `field` (without order)
    }

    /**
     * Cleans up a column name.
     *
     * @param string $str The column name.
     *
     * @return string The cleaned up column name.
     */
    function escapeColumn($str){
        if (preg_match('/^`?[a-zA-Z0-9_]`?+[\.`?[a-zA-Z0-9_]+`?]*$/', $str))
            return $str;
        else
            return '1'; // which will still give valid SQL code.
    }

    /**
     * Sanitizes a yyyy-mm-dd date by removing all non-numbers from each component.
     *
     * @param string $date The date.
     *
     * @return string The sanitized date.
     */
    function sanitizeDate($date){
        if(empty($date)){
            return $date;
        }
        $date = phive()->rmWhiteSpace($date);
        $arr  = explode('-', $date);
        $rarr = [];
        foreach($arr as $part){
            $rarr[] = phive()->rmNonNums($part);
        }
        return implode('-', $rarr);
    }

    /**
     * Sanitizes a string by looking at common SQL injection patterns. If a pattern is found the value returned will be a kind of
     * error value with the original string base 64 encoded.
     *
     * @param string $val The string.
     *
     * @return array An array with the injection type / tag in the first position, the sanitized value in the second position.
     * If no pattern was detected the first position will have null and the second the string / value untouched.
     */
    function sanitizeString($val){
        $ret = [null, $val];

        $val = trim($val);
        $lower_val = strtolower($val);

        $cases = ['char(', 'chr(', 'load_file(', 'ascii(', 'benchmark(', 'sleep(', 'substring(',
            'sha1(', 'user(', 'md5(', 'compress(', 'encode(', 'version(', 'row_count(', 'password(',
            'nslookup', 'gethostbyname', 'PG_SLEEP', "response.write("
        ];

        foreach($cases as $case){
            // We exclude password fields and gql queries.
            if(strpos($lower_val, $case) !== false){
                return ['function', "This was blocked, type function, start: $val :end"];
            }
        }

        // Hex from start to finish
        if(preg_match('|^0x[0-9a-f]+$|i', $lower_val)){
            return ['hex', "This was blocked, type hex from start to finish, start: $val :end"];
        }

        // Intermixed multiple hexes.
        if(preg_match_all('|0x[0-9a-f]|i', $lower_val) > 1){
            $base64_decoded = base64_decode($val, true);
            // Are we looking at some ordinary text or non 64 encoding?
            if(empty($base64_decoded) || max(array_map('ord', str_split($base64_decoded))) > 260){
                return ['hex', "This was blocked, type hex, start: $val :end"];
            }
        }

        // As simple number does not need to be examined as we capture the hex case above.
        if(is_numeric($val)){
            return $ret;
        }

        // We explode the string if contains "/" to check and whitelist anything starting with "admin2" as first param
        if(strpos($val, "/")) {
            $dir = explode("/", $val);
            if($dir[0] === 'admin2') {
                return $ret;
            }
        }

        $keywords = 'delete|insert|truncate|update|replace|rename|alter|select|union';
        // We lock on to a situation where have something else than for instance updated or truncates such as update{whitespace},
        // update0x34 or update/**/table.
        if(preg_match("/({$keywords})[^a-z]/i", $lower_val)){
            if(preg_match('|0x[0-9a-f]|i', $val)){
                return ['hex', "This was blocked, type hex, start: $val :end"];
            } else {
                // TODO henrik configure master tables list to avoid the get tables query all the time perhaps?
                $all_tables = array_unique(array_merge($this->getSetting('sharded_tables'), $this->getTables()));

                foreach($all_tables as $tbl){
                    if(preg_match("/\b{$tbl}\b/i", $lower_val)){

                        $explain_str = "EXPLAIN $val";

                        // We run an explain to check if the query executes or not, "Delete from languages" should be blocked
                        // but "Delete string in all languages" should pass through.
                        $explain_result = $this->isSharded($tbl)
                            ? $this->sh(rand(0, count($this->getSetting('shards')) - 1))->loadAssoc($explain_str)
                            : $this->loadAssoc($explain_str);

                        if(!empty($explain_result)){
                            return ['keyword', "This was blocked, type keyword, start: $val :end"];
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Sanitizes an array by looking at common SQL injection patterns. If a pattern is found the value returned will be a kind of
     * error value with the original string base 64 encoded.
     *
     * Note that JSON and / or sub arrays are sanitized recursively.
     *
     * @uses SQL::sanitizeString() to sanitize each value in the array.
     *
     * @param array $arr The value to sanitize.
     * @param string $action Action, eg get to use for logging in case something needs to be sanitized.
     * @param bool|null $is_json We need to separate JSON arrays and request "arrays", ie var[foo]=bar&var[fizz]=buzz.
     * @return array The sanitized array.
     */
    function sanitizeArray($arr, $action, $is_json = null){
        $tag = 'na';
        foreach($arr as $key => $val){

            foreach(['password', 'encrypted_data'] as $partial){
                if(strpos($key, $partial) !== false){
                    // We skip passwords, they are hashed anyway, before put into queries, also encrypted data.
                    continue 2;
                }
            }

            if(in_array($key, ['query', 'comment', 'content', 'cvc'])){
                // GQL and comments are ignored.
                continue;
            }

            foreach(['value' => 'action', 'abstract' => 'publish'] as $keep_field => $present_field){
                if($key == $keep_field && isset($arr[$present_field])){
                    // Various admin actions
                    continue 2;
                }
            }

            $is_json = $is_json ?? false;

            if(is_array($val)){
                $json_arr = $val;
                $is_json = false;
            } else {
                $json_arr = json_decode($val, true);
                $is_json = true;
            }
            if(is_array($json_arr)){
                $json_valid = true;
                foreach($json_arr as $json_key => $json_val){
                    if(is_array($json_val)){
                        $json_arr[$json_key] = $this->sanitizeArray($json_val, $action, $is_json);
                    } else {
                        list($tag, $val) = $this->sanitizeString($json_val);
                        if(!empty($tag)){
                            $json_arr[$json_key] = $val;
                            $json_valid = false;
                        }
                    }
                }

                // We assign the possibly sanitized array as JSON again.
                $arr[$key] = $is_json ? json_encode($json_arr) : $json_arr;

                if(!$json_valid){
                    phive()->dumpTbl("sql_injection_{$tag}_".$action, ['ip' => remIp(), 'host' => gethostname(), 'uri' => $_SERVER['REQUEST_URI'], 'request' => $arr]);
                }
            } else {
                list($tag, $val) = $this->sanitizeString($val);
                if(!empty($tag)){
                    $arr[$key] = $val;
                    phive()->dumpTbl("sql_injection_{$tag}_".$action, ['ip' => remIp(), 'host' => gethostname(), 'uri' => $_SERVER['REQUEST_URI'], 'request' => $arr]);
                }
            }
        }
        return $arr;
    }

    /**
     * Alias of SQL::escapeColumn()
     *
     * @param string $str The column name.
     *
     * @return string The cleaned up column name.
     */
    function escapeOrderBy($str) { return $this->escapeColumn($str); }

    // TODO henrik remove this
    function escapeLimit($number){ return (float)$number; }

    /**
     * SQL generation method that can be used to create the SELECT part of a query.
     *
     * @param mixed $select The select part, if it is an array it will be joined with commas.
     *
     * @return string The SELECT statement
     */
    function makeSelect($select = null) {
        if($select === null)
            $r = "*";
        elseif(is_array($select))
            $r = implode(',', $select);
        else
            $r = $select;
        return "SELECT " . $r;
    }

  /**
   * Used to generate multiple SUM()s in a statement.
   *
   * @param array $cols The names of the columns to sum up.
   * @param string $extra Possible extra to pass in, eg '/ 100' in order to get whole units instead of cents.
   * @param string $prefix Optional prefix in case the table of the columns is aliased.
   *
   * @return string The generated string with all the SUM()s.
   */
    function makeSums($cols, $extra = '', $prefix = ''){
        $str = '';
        foreach($cols as $sum => $c){
            if(strpos($c, '.') !== false)
                list($tbl, $sum) = explode('.', $c);
            else if(is_int($sum))
                $sum = $c;
            if(!empty($prefix))
                $c = "$prefix.$c";
            $str .= " SUM( $c {$extra}) AS $sum,";
        }
        return trim($str, ',');
    }

    /**
     * Generator of the WHERE clause in a statement.
     *
     * @param array|string $where The data to generate the WHERE statement from, if this is an associative array the keys
     * will be used for the column names and the values for the values they have to be.
     * @param bool $no_empty Whether or not we want to ignore empty values or not, if true we ignore.
     * @param string $start_with We typically want WHERE here but sometimes AND is needed in case we've got an initial WHERE
     * clause already that we want to build on.
     *
     * @return string The WHERE clause.
     */
    function makeWhere($where = null, $no_empty = false, $start_with = 'WHERE') {
        $r = '';
        if($where === null)
            return $r;
        if(is_array($where)) {
            $r = '';
            foreach($where as $key => $value) {
                // If we want empty values or the value is in fact not empty.
                if($no_empty == false || trim($value) != ''){
                    if($r !== '')
                        $r.= " AND";
                    $r.= ' `'.$key . "`=" . self::escape($value);
                }
            }
            $r = $start_with.' ' . $r;
        }else if(is_numeric($where)){
            $r = "$start_with id = ".(int)$where;
        }else if($where != '')
            $r = $start_with.' '. $where;

        return $r;
    }

    // TODO henrik remove this
    function getServerVersion(){
        return $this->getValue("SELECT VERSION()");
    }

    /**
     * Checks if a table exists in the database.
     *
     * @param string $tbl The table.
     *
     * @return bool True if it exists, false otherwise.
     */
    function hasTable($tbl){
        $sql_str = "SELECT * FROM information_schema.tables
                    WHERE table_schema = '{$this->con_details['database']}'
                    AND table_name = '$tbl'
                    LIMIT 1";
        return !empty($this->loadArray($sql_str));
    }

    /**
     * Creates a temporary table and uses the supplied WHERE clause to populate the newly created table with data from the original table.
     *
     * @param string $tbl The table, if it for instance is wins then the temporary table will be named wins_tmp.
     * @param string|array $where The data used to create the WHERE clause.
     *
     * @return null
     */
    function makeTmp($tbl, $where){
        $tmp_tbl = $tbl.'_tmp';
        $str     = "INSERT INTO $tmp_tbl SELECT * FROM $tbl ".$this->makeWhere($where);

        $func = function($db) use($tbl, $tmp_tbl, $str){
            if(!$this->hasTable($tmp_tbl)){
                $def = $db->getTableDefinition($tbl);
                $db->query("CREATE TABLE IF NOT EXISTS $tmp_tbl $def ENGINE = InnoDB");
            }
            $db->query($str);
        };

        // Currently doesn't work with global tables but I can't imagine any use case scenarios atm /Henrik
        if($this->isSharded($tmp_tbl)){
            $this->loopShardsSynced(function($db) use($func){
                $func($db);
            });
        } else {
            $func($this);
        }
    }

    /**
     * Truncates a temporary table, uses SQL::shs() in order to truncate in every DB in the whole DB cluster.
     *
     * @param string $tbl The table without the _tmp part.
     *
     * @return SQL This object to enable a fluent interface.
     */
    function delTmp($tbl){
        $this->shs('', '', null, $tbl.'_tmp')->query("TRUNCATE TABLE {$tbl}_tmp");
        return $this;
    }

    /**
     * Helper method needed in order to get CREATE table statements that can be used to create a new table in a different database.
     *
     * @param string $tbl The table.
     *
     * @return string The CREATE statement.
     */
    function getCreate($tbl){
        $res = phive("SQL")->loadAssoc("SHOW CREATE TABLE $tbl");
        return $res['Create Table'];
    }

    /**
     * Gets the table definition part out of a CREATE table statement.
     *
     * TODO henrik use getCreate instead of the loadArray
     *
     * @param string $tbl The table.
     *
     * @return string The table definition.
     */
    function getTableDefinition($tbl){
        $res = $this->loadArray("SHOW CREATE TABLE $tbl");
        preg_match("|`$tbl` (.+) ENGINE|sim", $res[0]['Create Table'], $m);
        return $m[1];
    }

    /**
     * Uses the schema for a table in one database to automatically create the table in another database.
     *
     * @param SQL $from_db The SQL objects that connects to the database we want to get the schema from.
     * @param SQL $to_db The SQL objects that connects to the database we want to create the new table on.
     * @param string $tbl The table.
     *
     * @return null
     */
    function createTable($from_db, $to_db, $tbl){
        $query = $from_db->getCreate($tbl);
        $to_db->query($query);
    }


    /** Takes a 2D associative array and returns a comma separated string for use in an IN() call.
     *
     * @uses Phive::arrCol()
     *
     * @param array $arr The array.
     * @param string $key The sub array key to use.
     *
     * @return string The comma separated string.
     */
    function makeInWith($arr, $key){
        $val_arr = phive()->arrCol($arr, $key);
        return $this->makeIn($val_arr);
    }

    /**
     * Returns a comma separated string for use in IN() calls.
     *
     * @param array $arr The array with values to comma join.
     *
     * @return string The comma separated string.
     */
    public function makeIn($arr){
        if(is_string($arr)){
            if(strpos($arr, ',') !== false) {
                $arr = explode(',', $arr);
            } else {
                return $this->escape($arr);
            }
        }

        $ret = '';
        foreach($arr as $str){
            $ret .= $this->escape($str).',';
        }
        return substr($ret, 0, -1);
    }

    /**
     * This method can be used in order to build queries with the help of mostly only arrays.
     *
     * @param array $select The array of column names to select.
     * @param string $from The table(s) to fetch from.
     * @param array $where The WHERE information.
     * @param string $extra Optional extras like ORDER BY or LIMIT or some such.
     *
     * @return string The query.
     */
    function makeQuery($select, $from, $where, $extra = null) {
        $q = self::makeSelect($select) . ' FROM ' . $from . ' ' . self::makeWhere($where);
        if($extra !== null) {
            $q .= ' ' . self::escape($extra, false);
        }
        return $q;
    }

  // TODO henrik remove this
  function makeQueryAnd($select, $from, $where, $extra = null){
    $this->query( $this->makeQuery( $select, $from, $where, $extra ) );
    return $this;
  }

    /**
     * Inserts multiple rows at the same time.
     *
     * @param string $table The table to insert into.
     * @param array $arr The associative 2D array with the data to insert.
     * @param bool $replace Whether or not to replace in case the data to insert has duplicates in the database.
     * @param bool $break_on_fail Whether or not to stop script execution in case one of the insert statements fail.
     *
     * @return null
     */
    function insert2DArr($table, $arr, $replace = false, $break_on_fail = false){
        foreach($arr as $el){
            if(!empty($el['user_id']))
                $res = $this->sh($el, 'user_id', $table)->insertArray($table, $el, null, $replace, false);
            else
                $res = $this->insertArray($table, $el, null, $replace, false);
            if($break_on_fail && !$res){
                print_r($el);
                exit;
            }
        }
    }

    /**
     * Used in case we need to insert a huge amount of data in one go.
     *
     * @param string $table The table to insert into.
     * @param array $rows The data to insert.
     * @param array $fields The keys whose values in each sub array to be inserted.
     * @param array $map Optional map in case the keys in the data array does not mach the table column names.
     * @param int $chunk_size To avoid a too large SQL statement to be created we will insert in chunks and with this argument the chunk
     * size can be controlled.
     * @param bool $onDuplicate If true and $fieldsToUpdate is not empty then an insert will not happen in case of unique key collission.
     * @param array $fieldsToUpdate The fields to update in case of unique key collission.
     *
     * @return bool True if all queries executed successfully, false otherwise.
     */
    function insertTable($table, $rows, $fields = array(), $map = array(), $chunk_size = 10, $onDuplicate = false, $fieldsToUpdate = array()){
        $chunks = array_chunk($rows, $chunk_size);

        foreach($chunks as $arr){
            $fields = empty($fields) ? array_keys($arr[0]) : $fields;
            if(!empty($map)){
                $tmparr	= array();
                foreach($arr as $sub){
                    $tmp = array();
                    foreach($map as $from)
                        $tmp[] = $sub[$from];
                    $tmparr[] = $tmp;
                }
                $arr = $tmparr;
            }

            $fstr = "";
            foreach($fields as $field)
                $fstr .= "`$field`,";
            $fstr = trim($fstr, ',');

            $sql = "INSERT INTO `$table` ($fstr) VALUES ";
            foreach($arr as $sub){
                $sql .= "(";
                $substr = "";
                foreach($sub as $value)
                    $substr .= $this->escape($value).",";
                $sql .= trim($substr, ',')."),";
            }

            if($onDuplicate && !empty($fieldsToUpdate)){
                $sql = trim($sql, ',');
                $sql .= " ON DUPLICATE KEY UPDATE ";
                foreach ($fieldsToUpdate as $field) {
                    $sql .= "`$field` = VALUES($field),";
                }
            }

            $sql = trim($sql, ',');

            $res = $this->query($sql);
            if($res === false)
                return false;
        }
        return true;
    }

    // TODO henrik remove this.
  function update2DArr($table, $arr, $id_key = 'id'){
    foreach($arr as $row)
      $this->insertArray($table, $row, array($id_key => $row[ $id_key ] ), false, false);
  }

  // TODO henrik remove this.
  function save2d($table, $arr){
    foreach($arr as $r)
      $this->save($table, $r);
  }

    /**
     * The basic key = value generation method for inserts.
     *
     * @param array $array The array to convert into an INSERT key = value statement.
     *
     * @return string The key = value, ... string.
     */
    function arrayToInsert($array){
        $q = '';
        foreach($array as $key => $value) {
            if(is_null($value))
                $q .= " `$key`= '' ,";
            else
                $q .= " `$key` =" . $this->escape($value,true) . ",";
        }
        return trim($q, ",");
    }

    /**
     * The base method for generating whole INSERT or UPDATE (in some cases) statements.
     *
     * @param string $table The table to insert into.
     * @param array $array The data to insert.
     * @param mixed $where Optional WHERE clause data.
     * @param bool $replace TODO henrik remove this one, it is not used.
     *
     * @return string The generated INSERT / UPDATE statement.
     */
    function getInsertSql($table, $array, $where = null, $replace=false){
        if (!is_array($array))
            $this->logAndDie("Input must be an array.");

        if ($replace)
            $q = "REPLACE INTO";
        else if ($where === null)
            $q	= "INSERT INTO";
        else
            $q	= "UPDATE";

        $q .= " `$table` SET ".$this->arrayToInsert($array);

        if($where !== null)
            $q .= " " . self::makeWhere($where);

        return $q;
    }

    /**
     * The main logic behind wrappers such as SQL::save()
     *
     * @used-by SQL::save()
     *
     * @TODO henrik no need to return $res in two different places.
     *
     * @param string $table The table to insert into.
     * @param array $iarray The array to be inserted.
     * @param array $uarray An array used for updating the row with new values if the row already exists.
     * @param bool $do_global If false global updates / inserts are bypassed.
     *
     * @return bool The result of the query that was executed on the master DB.
     */
    function insertOrUpdate($table, $iarray, $uarray, $do_global = true){
        $args      = func_get_args();
        $q         = $this->getInsertSql($table, $iarray)." ON DUPLICATE KEY UPDATE ".$this->arrayToInsert($uarray);
        $before_id = $this->insertBigId();
        $res       = $this->query($q);
        $after_id  = $this->insertBigId();
        // Pass $do_global as true if you want to bypass updates on the nodes
        if($this->isGlobal($table) && $do_global){
            if($before_id != $after_id){
                //It was an insert so we are sure we don't need to update shards.
                $iarray['id'] = $after_id;
                $this->doGlobal($table, 'insertArray', [$table, $iarray], $res);
            }else{
                //It was an update so we update on all shards.
                $this->doGlobal($table, 'insertOrUpdate', $args, $res);
            }
            return $res;
        }else{
            $this->doMaster('insertOrUpdate', $args, $after_id, 1, $table);
            return $res;
        }
    }

    /**
     * Load balancer for selects the master.
     *
     * @return SQL In order to achieve a fluent interface / chaining.
     */
    function lb(){
        if($this->getSetting('master_load_balancing') !== true){
            // We don't want to load balance or read from replica at all.
            return $this;
        }

        $load_balance = $this->getSetting('master_load_balancing_hosts');

        if(empty($load_balance)){
            // We don't have a balancer configured but config indicates that we want to read from replica so we do that.
            return $this->doDb('replica');
        }

        // This is to create affinity for a specific node so we don't connect to all of them in the same script
        // which would hurt performance. On the other hand,
        if(isset($this->master_read_from)){
            return $this->master_read_from;
        }

        $host_key = phive()->loadBalance($load_balance);

        // If we're executing in the context of a cron job or a queue then we need to randomize
        // to avoid potentially massive amounts of connections to the same host, so we skip caching / affinity.
        if(phive()->isQueued() || phive()->isCron()){
            return $this->doDb($host_key);
        }

        $this->master_read_from = $this->doDb($host_key);
        return $this->master_read_from;
    }

    // TODO henrik remove this
    function pShard(){
        $args   = func_get_args();
        $sh_id  = array_shift($args);
        $method = array_shift($args);
        $obj    = $this->sh($sh_id);
        return call_user_func_array([$obj, $method], $args);
    }

    /**
     * A wrapper around SQL::insertOrUpdate()
     *
     * Withour the $where argument this simply defaults to SQL::insertOrUpdate(), however if $where is supplied
     * we fetch the row in question and update its values with the help of the data array, after that we pass it to
     * SQL::insertOrUpdate().
     *
     * @uses SQL::insertOrUpdate()
     *
     * @param string $table The table to save / insert data into.
     * @param array $arr The data to insert.
     * @param mixed $where Data used for the WHERE clause.
     *
     * @return bool True if the query was successful, false otherwise.
     */
    function save($table, $arr, $where = ''){
        if(!empty($where)){
            $cur = $this->loadAssoc('', $table, $where);
            foreach($arr as $key => $value)
                $cur[$key] = $value;
            $arr = $cur;
        }

        return $this->insertOrUpdate($table, $arr, $arr);
    }

    /**
     * Will insert a row but as opposed to SQL::save() there is now option to partially update the database row,
     * the full array to be inserted will be used for the update / replace (if that is desirable as indicated by
     * the $were and / or $replace arguments).
     *
     * @see SQL::save()
     *
     * @param string $table The table to insert into.
     * @param array $array The array with data to insert.
     * @param array|string $where Optional WHERE info.
     * @param bool $replace Whether or not to use REPLACE INTO.
     * @param bool $ret_new_id Whether or not to return the id of the newly inserted row, default yes / true.
     *
     * @return bool|int Integer in case we want the id of the new row back, boolean true if insert was successful, false otherwise.
     */
    function insertArray($table, $array, $where = null, $replace = false, $ret_new_id = true){
        $args = func_get_args();
        $q = $this->getInsertSql($table, $array, $where, $replace);
        if($q === false)
            return false;
        $res = $this->query($q);
        if($res === false)
            return $res;
        $id = $this->insertBigId();

        //If the table is sharded globally we first get the id to use from the master, otherwise it won't end up being the same on all shards
        if($this->isGlobal($table)){
            if(empty($array['id']) && empty($where)){
                $array['id'] = $id;
                $q           = $this->getInsertSql($table, $array, $where, $replace);
            }
            $res = $ret_new_id ? $array['id'] : $res;
            $this->doGlobal($table, 'insertArray', [$table, $array, $where, $replace, false], $res);
            return $res;
        }else{
            $this->doMaster('insertArray', $args, $id, 1, $table);
            if($ret_new_id === false)
                return $res;
            return $id;
        }
    }

    // TODO henrik remove this, doDb() should be used instead.
    function useDb($db = ''){
        $db = empty($db) ? $this->getSetting("database") : $db;
        $this->query("USE $db");
    }

    /**
     * Wrapper around SQL::insertArray()
     *
     * @uses SQL::insertArray()
     *
     * @param string $table The table to update.
     * @param array $array The data to update with.
     * @param array|string $where The WHERE info.
     *
     * @return bool True if successful, false otherwise.
     */
    function updateArray($table, $array, $where){

        if ($where === null) {
            $this->logAndDie("Not allowed to preform update without WHERE statement.", E_USER_WARNING);
            return false;
        }

        return $this->insertArray($table, $array, $where, false, false);
    }

    /**
     * Small helper to do date ranges in WHERE clauses.
     *
     * @param string $s The start date / stamp.
     * @param string $e The end date / stamp.
     * @param string $field The table column.
     * @param string $and The SQL operator, WHERE or AND.
     *
     * @return string The finished string.
     */
    function tRng($s, $e, $field = 'created_at', $and = 'AND'){
        return "$and `$field` >= '$s' AND `$field` <= '$e'";
    }

    /**
     * Deletes one or more rows.
     *
     * This method is global aware and shard aware if $uid is passed in. Note that we check for empty
     * where values as we don't want to accidentally delete all rows that have nothing in some column.
     *
     * @param string $table The table to delete from.
     * @param array $where The WHERE info.
     * @param int $uid Optional user id in order to find the correct shard to delete from.
     *
     * @return bool True if the DELETE query was successful, false otherwise.
     */
    function delete($table, $where = array(), $uid = null){
        if(empty($where))
            return false;
        if (is_array($where)) {
            foreach ($where as $key => $val) {
                if (empty($val))
                    return false;
            }
        }
        $cur_where = $this->makeWhere($where);
        if(empty($cur_where))
            return false;

        if($this->isSharded($table) && !empty($uid)){
            return $this->sh($uid)->delete($table, $where);
        }
        if($this->isGlobal($table)){
            $this->doGlobal($table, 'delete', [$table, $where]);
        }

        return $this->query("DELETE FROM $table $cur_where");
    }

    /**
     * Small helper for the LIMIT clause.
     *
     * @param int $limit The amount of rows to fetch.
     * @param int $start The offset.
     *
     * @return string The LIMIT clause.
     */
    function getLimit($limit, $start = 0){
        return empty($limit) ? '' : "LIMIT $start,$limit";
    }

  // TODO henrik merge in node scaleback and check if this method is still used, if not remove it.
    function archiveFailover($res, $sql, $method = 'loadArray'){
        if(empty($res) && $this->settingExists('archive')){
            $adb = $this->doDb('archive');
            if($adb->hasConnection()){
                return call_user_func_array([$adb, $method], [$sql]);
            }
        }
        return $res;
    }

    /**
     * Prepends data from archive nodes
     *
     * This method takes a reference to a result array as its first argument and will preppend the archived data
     * depending on whether any can be found.
     *
     * @param array &$res The result array to prepend to.
     * @param int $user_id The user id to work with.
     * @param string $sstamp The start stamp to work with, if this stamp is more recent than the most recent data in the
     * archive then we do nothing.
     * @param string $query The SQL query that was used to generate the non archived data, we will run it on the archived
     * @param string $table The table to query.
     * data to get an identical result.
     * @param string $method The method to run on the SQL object.
     *
     * @return null We work with a reference so we don't return anything.
     */
    function prependFromNodeArchive(&$res, $user_id, $sstamp, $query, $table, $method = 'loadArray'){

        if(empty($this->getSetting('shard_archives'))){
            return;
        }

        if(empty($res)){
            $res = [];
        }

        $archive_end_stamp = phive()->getMiscCache("node-archive-end-date-{$table}");

        if(empty($archive_end_stamp)){
            return;
        }

        if(!empty($sstamp) && $archive_end_stamp < $sstamp){
            return;
        }

        $adb    = $this->doDb('shard_archives', $this->getNodeByUserId($user_id));
        $to_add = call_user_func_array([$adb, $method], [$query]);

        if(empty($to_add)){
            return;
        }

        $res    = is_array(current($res))    ? $res    : [$res];
        $to_add = is_array(current($to_add)) ? $to_add : [$to_add];
        $res    = array_filter(array_merge($to_add, $res));
    }

    /**
     * Prepends data from archive nodes
     *
     * This method takes a reference to a result array as its first argument and will prepend the archived data
     * depending on whether any can be found.
     *
     * The query runs in shs mode (all nodes)
     *
     * @param array &$res The result array to prepend to.
     * @param string $sstamp The start stamp to work with, if this stamp is more recent than the most recent data in the
     * archive then we do nothing.
     * @param string $query The SQL query that was used to generate the non archived data, we will run it on the archived
     * @param string $table The table to query.
     * data to get an identical result.
     * @param string $method The method to run on the SQL object.
     *
     * @return void We work with a reference so we don't return anything.
     */
    public function prependFromArchives(array &$res, string $sstamp, string $query, string $table, $method = 'loadArray'): void
    {

        if (empty($this->getSetting('shard_archives'))) {
            return;
        }

        if (empty($res)) {
            $res = [];
        }

        $archive_end_stamp = phive()->getMiscCache("node-archive-end-date-{$table}");

        if (empty($archive_end_stamp)) {
            return;
        }

        if (!empty($sstamp) && $archive_end_stamp < $sstamp) {
            return;
        }

        $adb = new SQL();
        //load defaults
        $adb->loadSettings();
        //replace shard list
        $adb->setSetting('shards', $this->getSetting('shard_archives'));
        //enable shs mode
        $adb->shs();

        $to_add = $adb->$method($query);

        if (empty($to_add)) {
            return;
        }

        $res = is_array(current($res)) ? $res : [$res];
        $to_add = is_array(current($to_add)) ? $to_add : [$to_add];
        $res = array_filter(array_merge($to_add, $res));
    }


    // TODO henrik remove this
  function archiveTable($tbl, $date = '', $pid = 'id', $debug = false, $update_schema = true, $time_col = 'created_at'){
    $date = empty($date) ? phive()->hisMod('-3 month', '', 'Y-m-01 00:00:00') : "$date 00:00:00";
    if($update_schema)
      $this->updateTblSchema($tbl);
    $str = "SELECT * FROM $tbl WHERE $time_col < '$date' ORDER BY $pid LIMIT 1000";
    if($debug){
      $this->debug = true;
      echo $str;
    }
    while($rows = $this->loadArray($str)){
      foreach($rows as $el){
        if($this->doDb('archive')->insertArray($tbl, $el, null, false, false)){
          $this->query("DELETE FROM $tbl WHERE id = {$el['id']}");
        }else{
          print_r($el);
          $this->logAndDie('Row not inserted in remote db.', E_ERROR);
          exit;
        }

        if($debug){
          print_r($el);
          $this->printDebug();
          exit;
        }
      }
    }
  }

  /**
   * Increments the value in one or more columns of a row.
   *
   * This method needs to be used in all monetary adjustments or when absolute accuracy is needed as the
   * increments happens 100% in the database with all its ACID protections etc in force.
   *
   * @param string $tbl The table of the row we want to work with.
   * @param string $col The column we want to increment.
   * @param array|string $where Typically lookup constraints that will fetch only one row but this method will work on multiple rows too.
   * @param int|array $inc_with If array we increase multiple columns at once.
   * @param array $more_updates Optionally more column updates to run at the same time.
   *
   * @return bool True if the query was successful, false otherwise.
   */
    function incrValue($tbl, $col, $where, $inc_with = 1, $more_updates = [], $uid = ''){
        $where        = $this->makeWhere($where);
        $istr         = '';
        $more_updates = $this->arrayToInsert($more_updates);
        if(is_array($inc_with)){
            foreach($inc_with as $inc_col => $inc_v){
                $inc_v = empty($inc_v) ? 0 : $inc_v;
                $istr .= " $inc_col = $inc_col + $inc_v,";
            }
        } else {
            if(empty($inc_with) && empty($more_updates)){
                // There is no point in incrementing with 0 and there are no extra updates so nothing to do.
                return true;
            }
            $istr = "$col = $col + $inc_with";
        }

        $istr = trim($istr, ', ');
        // If the more updates string is not empty and the increase string is not empty we need a comma separator
        if(!empty($more_updates) && !empty($istr))
            $more_updates = ",$more_updates";
        $str = "UPDATE $tbl SET $istr $more_updates $where";

        if(!empty($uid)){
            $res = $this->sh($uid)->query($str);
            return $res;
        }else{
            return $this->query($str);
        }
    }

    /**
     * Increments a column with ON DUPLICATE KEY UPDATE, similar to @see incrValue but in this case can be used for
     * performance reasons to do atomic updates without having to query first.
     *
     * Note this function should only be used if there is a proper unique index
     *
     * @param string $table
     * @param array $candidates
     * @param array $only_insert
     * @param DBUser|int|null $user
     * @return bool
     */
    public function incrOrInsertValues(string $table, array $candidates, array $only_insert, $user = null)
    {
        if (empty($candidates) || empty($only_insert)) {
            return false;
        }
        $query = $this->getInsertSql($table, array_merge($candidates, $only_insert));

        $query .= ' ON DUPLICATE KEY UPDATE ';

        $to_increase = [];
        foreach($candidates as $inc_column => $inc_value){
            $to_increase[] = "`$inc_column` = `$inc_column` + ". $this->escape($inc_value,false);
        }

        $query .= implode(', ', $to_increase);

        if(!empty($user)){
            return $this->sh($user, '', $table)->query($query);
        }else{
            return $this->query($query);
        }
    }

    /**
     * Will truncate a table, is shard aware and will truncate on all nodes if the table is sharded.
     *
     * @param string An arbitrary amount of table names, if more than one we truncate all of them.
     *
     * @return null
     */
    function truncate(){
      foreach(func_get_args() as $tbl)
          $this->shs('', '', null, $tbl)->query("TRUNCATE $tbl");
    }

    /**
     * Starts a transaction.
     *
     * @return null
     */
    function beginTransaction(){
        return $this->query("START TRANSACTION");
    }

    /**
     * Commits a transaction.
     *
     * @return null
     */
    function commitTransaction(){
        return $this->query("COMMIT");
    }

    /**
     * Rolls back a transaction.
     *
     * @return null
     */
    function rollbackTransaction() { $this->query("ROLLBACK"); }

    /**
     * This method is typically used in situations when we want to create a global table and push out its data
     * from the master to all the nodes in the cluster.
     *
     * @param string $tbl The table to create.
     * @param bool $truncate Truncate if the table already exists on the nodes, yes (true or no (false).
     *
     * @return null
     */
    function createShardTable($tbl, $truncate = false){
        $tbl = is_array($tbl) ? $tbl[0] : $tbl;
        $db_name = $this->getShardConf(0)[1]['database'];
        $res = $this->sh(0)->loadArray("SELECT *
                                       FROM information_schema.tables
                                       WHERE table_schema = '$db_name'
                                           AND table_name = '$tbl'
                                       LIMIT 1");
        if(empty($res)){
            $create_sql = $this->loadArray("SHOW CREATE TABLE $tbl")[0]['Create Table'];
            $this->doParaSql('query', [$create_sql], true);
        }else{
            // Table already exists and we want to truncate it.
            if($truncate){
                $this->loopShardsSynced(function($db) use($tbl){
                    $db->truncate($tbl);
                });
            }
        }
    }

    // TODO henrik remove this
    function syncGlobalTable(){
        foreach(func_get_args() as $tbl){
            // We don't want to accidentally work with a non-global table, ie sharded table.
            if(!$this->isGlobal($tbl)){
                echo "$tbl is not global!\n";
                continue;
            }
            $this->createShardTable($tbl);
            echo "$tbl\n";
            $this->doParaSql('query', ["TRUNCATE $tbl"], true);
            $data = $this->loadArray("SELECT * FROM $tbl");
            $this->doParaSql('insert2DArr', [$tbl, $data], true);
        }
    }

    /**
     * Can be used to turn a master only table into a global table and push the data in the table to all nodes. It is however
     * most commonly used to re-push data in global tables in case the nodes are out of sync with the master for some reason.
     *
     * @param array $tbls An array with the tables to sync.
     * @param int $row_cnt To avoid too large SQL statements we can limit each insert to this amount of rows.
     *
     * @return null
     */
    function syncGlobalTableSynced($tbls, $row_cnt = 10000){
        foreach($tbls as $tbl){
            // We don't want to accidentally work with a non-global table, ie sharded table.
            if(!$this->isGlobal($tbl)){
                echo "$tbl is not global!\n";
                continue;
            }
            $this->createShardTable($tbl);
            $cur_id = 0;
            while(true){
                $sql_str = "SELECT * FROM $tbl LIMIT $cur_id, $row_cnt";
                $rows    = $this->loadArray($sql_str);
                if(empty($rows))
                    break;
                $this->loopShardsSynced(function($shard_db, $sh_info, $sh_num) use($tbl, $rows, $cur_id, $sql_str){
                    echo "Table: $tbl, Shard num: $sh_num, cur id: $cur_id\n";
                    if($cur_id == 0)
                        $shard_db->truncate($tbl);
                    if(!$shard_db->insertTable($tbl, $rows, [], [], 100))
                        error_log($sql_str);

                });
                $cur_id += $row_cnt;
            }
        }
    }

    // TODO henrik remove this
    function doParaSql($method, $args, $return = false, $flatten = false, $default = ''){
        //print_r(func_get_args());
        if($this->is_shard || empty(phive('SQL')->getShards()))
            return $default;
        array_unshift($args, $method);
        return pExecShards('SQL', 'pShard', $args, $return, $flatten);
    }

    // TODO henrik remove this
    function deleteOld($tbl, $thold_val, $col_name = 'created_at', $chunk_size = 10000){
        $str = "SELECT id FROM $tbl WHERE $col_name < '$thold_val' LIMIT 0, $chunk_size";
        while(true){
            $rows = $this->loadArray($str);
            if(empty($rows))
                return;
            foreach($rows as $r)
                $this->delete($tbl, ['id' => $r['id']]);
            echo "{$rows[0]['id']}\n";
        }

    }

    /**
     * Something went wrong, save a trace and close
     * @param $error
     */
    protected function logAndDie($error): void
    {
        phive('Logger')->getLogger('SQL')->logTrace($error, 'CRITICAL');
        die($error);
    }

    /**
     * Returns a read only version if available.
     * It will use "slave_shards" and "replica" configs where available
     */
    public function readOnly(): SQL
    {
        if (!isset($this->read_only_instance)) {
            $shards = $this->getSetting('slave_shards');
            $replica = $this->getSetting('replica');

            if ($shards || $replica) {
                $instance = new SQL();
                //load defaults
                $instance->loadSettings();
                //replace connections with read-only versions. rest of the code will not know the difference
                if ($shards) {
                    $instance->setSetting('shards', $shards);
                }
                if ($replica) {
                    $instance->setSetting('username', $replica['username']);
                    $instance->setSetting('password', $replica['password']);
                    $instance->setSetting('hostname', $replica['hostname']);
                    $instance->setSetting('database', $replica['database']);
                    $instance->setSetting('port', $replica['port']);
                }
                $instance->writable_instance = $this;
                $this->read_only_instance = $instance;
            } else {
                //noop when there's no config
                $this->read_only_instance = $this;
            }
        }

        return $this->read_only_instance;
    }

    /**
     * Get an instance on write mode. It's a noop on main connection
     * @return SQL
     */
    public function writable(): SQL
    {
        if (!isset($this->writable_instance)) {
            return $this;
        }
        return $this->writable_instance;
    }

    /**
     * Check if we have a disabled node
     *
     * @return bool
     */
    public function disabledNodeIsActive(): bool
    {
        return !is_null($this->getSetting('disabled_node'));
    }

    /**
     * Check if user_id or node number refers to a disabled node
     *
     * @param $node
     * @return bool
     */
    public function isOnDisabledNode($node): bool
    {
        $disabled_node = $this->getSetting('disabled_node');
        if (is_null($disabled_node)) {
            return false;
        }
        return $disabled_node === ($node % 10);
    }

    /**
     * Force the connection to master
     *
     * @return SQL
     */
    public function onlyMaster(): SQL
    {
        $sql = new SQL([
            'username' => phive('SQL')->getSetting('username'),
            'password' => phive('SQL')->getSetting('password'),
            'hostname' => phive('SQL')->getSetting('hostname'),
            'database' => phive('SQL')->getSetting('database'),
        ]);
        $sql->setSetting('global_tables', []);
        $sql->setSetting('sharding_status', false);
        return $sql;
    }

    /**
     * Prevent inserting user on the disabled node.
     *
     * @param $data
     * @return bool|int
     */
    public function createUserOnNonDisabledNode($data)
    {
        if (!empty($data) && !empty($data['id'])) {
            // This should never happen but adding this to make sure we never delete existing user_id
            return $data['id'];
        }
        $retries = 3;
        $user_id = phive('SQL')->insertArray('users', $data);

        while (phive('SQL')->isOnDisabledNode($user_id)) {
            // prevent infinity AND don't run a faulty query if $user_id is bool
            if ($retries === 0 || empty($user_id)) {
                if ($retries === 0) {
                    phive('Logger')->warning('Reached 0 retries in selecting a non disabled node.');
                }
                return $user_id;
            }

            // delete existing row from master
            phive('SQL')->onlyMaster()->query("DELETE from users where id = {$user_id};");

            // reinsert the same data in master
            $user_id = phive('SQL')->insertArray('users', $data);

            --$retries;
        }

        return $user_id;
    }

    /**
     * This function separates the list of ids into shard bins,
     * to run only the needed ones instead of shs, and avoid index misses
     * @param int[] $ids
     * @return array [shard=>[ids]]
     */
    public function shardUsers(array $ids): array
    {
        $bins = [];
        foreach ($ids as $id) {
            $bins[$id % 10][] = $id;
        }
        return $bins;
    }

    /**
     * This method is used to set a specific column to NULL for a specific row by the id column.
     *
     * @param string $table The table to update.
     * @param string $column The column to set to NULL.
     * @param int $id The row id.
     *
     * @return void
     */
    public function updateWithNullById(string $table, string $column, int $id)
    {
        $str = "UPDATE {$table} SET {$column} = NULL";

        if($id){
            $str .= " WHERE id = " .$id;
            $this->query($str);
        }
    }

}

/**
* Executes logic on all shards in an async (forked) fashion.
*
* @param string $module The module to use.
* @param string $method The method on that module to execute.
* @param array $args The arguments to pass / apply on that method.
* @param bool $return Whether or not to return the results of the calls.
* **Note** that this feature might or might not work properly.
* @param bool $flatten If a return is expected this flag controls whether or not the result should be
* flattened into one array instead of one sub array per shard.
*
* @return null|array The array if a return is expected, null otherwise
*/
function pExecShards($module, $method, $args = [], $return = false, $flatten = false){
    $shards = phive('SQL')->getShards();
    if(empty($shards))
        return phive()->apply($module, $method, $args);

    $argss = array_map(function($sh_num) use($args){
        return array_merge([$sh_num], (array)$args);
    }, array_keys($shards));

    if($return){
        return phive()->pexecRes($module, $method, $argss, $flatten);
    } else {
        foreach($argss as $args)
            phive()->pexec($module, $method, $args, '100-0', true); // We set unlimited timeout explicitly.
    }
}
