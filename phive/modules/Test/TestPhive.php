<?php

use Carbon\Carbon;

class TestPhive
{

    const CONSOLE_COLOR_GREEN = "\033[32m";
    const CONSOLE_COLOR_RED = "\e[0;31m";
    const CONSOLE_COLOR_WHITE = "\033[1m";
    const CONSOLE_COLOR_BLUE = "\e[0;34m";
    const CONSOLE_COLOR_RESET = "\e[0m";

    const TEST_PASSED = self::CONSOLE_COLOR_GREEN . '✓';
    const TEST_FAILED = self::CONSOLE_COLOR_RED . '✗';
    const DEFAULT_TEST_PASSED_MESSAGE = 'Test Passed';
    const DEFAULT_TEST_FAILED_MESSAGE = 'Test Failed';

    const INPUT_HEADING = self::CONSOLE_COLOR_WHITE . PHP_EOL . PHP_EOL . "=== INPUT === " . PHP_EOL . PHP_EOL . self::CONSOLE_COLOR_RESET;
    const OUTPUT_HEADING = self::CONSOLE_COLOR_WHITE . PHP_EOL . PHP_EOL . "=== OUTPUT === " . PHP_EOL . PHP_EOL . self::CONSOLE_COLOR_RESET;
    const ASSERTION_HEADING = self::CONSOLE_COLOR_WHITE . PHP_EOL . PHP_EOL . "=== ASSERTION === " . PHP_EOL . PHP_EOL . self::CONSOLE_COLOR_RESET;

    const DEFAULT_ADDITIONAL_USER_TABLES = [
        'deposits',
        'first_deposits',
        'cash_transactions',
        'triggers_log',
        'actions' => 'target',
        'users_settings',
        'users_sessions',
        'users_game_sessions',
        'risk_profile_rating_log',
        'users_daily_stats',
        'users_realtime_stats',
        'users_daily_balance_stats',
        'rg_limits',
        'users_daily_unique_bets',
    ];

    /**
     * @var bool
     */
    private bool $print_input_data = true;

    /**
     * @var bool
     */
    private bool $print_output_data = true;

    /**
     * @var bool
     */
    private bool $print_assertion_result = true;

    public $class;

    /**
     * @var array
     */
    public array $test_methods = [];

    /**
     * @var bool
     */
    public bool $only_handlers = false;

    /**
     * @var DBUser
     */
    public DBUser $user;

    /**
     * @var array
     */
    public array $handlers = [];

    /**
     * @var bool
     */
    public bool $sql_debug = false;

    /**
     * @var array
     */
    public array $info = [];

    /**
     * @var bool|object|Phive
     */
    protected $db;

    /**
     * @var bool|object|Phive
     */
    protected $rgModule;

    function __construct($module)
    {
        $this->class = phive($module);
        $this->db = phive('SQL');
        $this->rgModule = phive('Cashier/Rg');
    }

    function clearMtsTable($u_obj, $tbls)
    {
        $this->clearTable($u_obj, $tbls, null, 'mts');
    }

    function clearTable($u_obj, $tbls, $uid_col = 'user_id', $db = '')
    {
        $tbls = !is_array($tbls) ? [$tbls] : $tbls;
        $db = !empty($db) ? phive('SQL')->doDb($db) : phive('SQL');
        foreach ($tbls as $tbl) {
            $db->delete($tbl, [$uid_col => $u_obj->getId()], $u_obj->getId());
        }
    }

    function printTable($u_obj, $table, $uid_col = 'user_id')
    {
        $res = phive('SQL')->sh($u_obj)->loadArray("SELECT * FROM $table WHERE $uid_col = {$u_obj->getId()}");
        print_r($res);
    }

    function printJson($json)
    {
        $arr = json_decode($json, true);
        print_r($arr);
    }

    function truncateLog()
    {
        phive('SQL')->truncate('trans_log');
    }

    function prLog(){
        $res = phive('SQL')->loadArray("SELECT * FROM trans_log ORDER BY id DESC LIMIT 10");
        print_r($res);
    }

    /*
    function clearTable($u_obj, $table, $uid_col = 'user_id'){
        phive('SQL')->delete($table, [$uid_col => $u_obj->getId()], $u_obj);
    }

    function printTable($u_obj, $table, $uid_col = 'user_id'){
        $res = phive('SQL')->sh($u_obj)->loadArray("SELECT * FROM $table WHERE $uid_col = {$u_obj->getId()}");
        print_r($res);
    }
    */
    function deleteByUser()
    {
        $args = func_get_args();
        $u = array_shift($args);
        foreach ($args as $tbl) {
            echo "Deleting {$u->getUsername()}'s $tbl rows \n";
            phive('SQL')->delete($tbl, ['user_id' => $u->getId()], $u->getId());
        }
    }

    function getLatest($u, $tbl, $cnt = 5)
    {
        return phive('SQL')->sh($u, 'id')->loadArray(
            "SELECT * FROM $tbl WHERE user_id = {$u->getId()} ORDER BY id DESC LIMIT $cnt"
        );
    }

    function printLatest($u, $tbl, $cnt = 5)
    {
        $latest = $this->getLatest($u, $tbl, $cnt);
        echo "Latest $tbl:\n";
        print_r($latest);
        echo "\n";
    }

    function printAll($tbl, $u = '')
    {
        $db = empty($u) ? phive('SQL') : phive('SQL')->sh($u, 'id');
        $all = $db->loadArray("SELECT * FROM $tbl");
        echo "All $tbl\n:";
        print_r($all);
        echo "\n";
    }

    function resetPlayer($u, $main_province = '')
    {
        phive('Casino')->depositCash($u, 50000, 'trustly', uniqid());
        $u->setAttr('active', 1);
        $u->deleteSettings(
            'play_block',
            'withdrawal_block',
            'restrict',
            'super-blocked',
            'similar_fraud',
            'unexclude-date',
            'unlock-date',
            'lock-date',
            'lock-hours'
        );
        $u->setSetting('verified', 1);
        $u->setSetting('dont_verify_on_login', 1);
        $u->setPpVersion();
        $u->setTcVersion();

        if($main_province === 'ON') {
            $u->setSetting('industry', 'Administration, Business, Marketing or Finance');
            $u->setSetting('occupation', 'Accountant');
            $u->setSetting('building', 'SeaView Building');
            $u->setSetting('nationality', $u->getCountry());
            $u->setSetting('main_province', $main_province);
            $u->setSetting('jurisdiction', 'AGCO');
        }
    }

    /**
     * Get mocked user
     *
     * @param string $country
     * @param string $main_province
     * @param int    $age
     *
     * @return DBUser
     * @throws DateInvalidOperationException
     */
    function getTestPlayer(string $country = 'GB', int $age = 30, string $main_province = '', ?string $registered_at = null): DBUser
    {
        $currency = phive("Licensed/{$country}/{$country}")->getForcedCurrency() ?: 'EUR';
        $email = 'user.for.testing@test.phive.videoslots.com';
        $dob = (new DateTime('now'))
            ->sub(DateInterval::createFromDateString("{$age} years"));
        $user = [
            'email' => $email,
            'mobile' => '49145454657851265',
            'country' => $country,
            'sex' => 'Male',
            'lastname' => 'Hammerbush',
            'firstname' => 'Frederic',
            'address' => 'Bahamas',
            'city' => 'Nuevo Sol',
            'zipcode' => '91101',
            'dob' => $dob->format('Y-m-d'),
            'preferred_lang' => 'en',
            'username' => $email,
            'password' => 'c79194b0356573ee78398fc6486b4644',
            'bonus_code' => ' ',
            'register_date' => '2019-06-17',
            'reg_ip' => '84.172.232.19',
            'verified_phone' => 1,
            'friend' => ' ',
            'alias' => ' ',
            'cur_ip' => '127.0.0.1.',
            'currency' => $currency,
            'nid' => '123456',
        ];

        $user_id = phive('SQL')->insertArray('users', $user);

        $user['id'] = $user_id;
        $user['email'] = $user_id . $user['email'];
        $user['username'] = $user_id . $user['username'];
        $user['mobile'] = '8112345678' . $user_id;
        $user['lastname'] = $user['lastname'] . $user_id;
        $user['firstname'] = $user['firstname'] . $user_id;
        $user['alias'] = $user['alias'] . $user_id;
        $user['nid'] = $user['nid'] . $user_id;
        $user['address'] = $user['address'] . $user_id;
        $user['city'] = $user['city'] . $user_id;

        phive('SQL')->insertArray('users', $user, null, true);
        if (phive('SQL')->isSharded('users')) {
            phive('SQL')->sh($user_id)->insertArray('users', $user);
        }

        $db_user = cu($user_id);
        if (!empty($registered_at)) {
            $registered_at = Carbon::parse($registered_at)->toDateTimeString();
        } else {
            $registered_at = Carbon::now()->subMonths(10)->subHours(10)->toDateTimeString();
        }

        $db_user->setSetting('registration_end_date', $registered_at);
        phive('SQL')->sh($user_id)->updateArray(
            'users_settings',
            ['created_at' => $registered_at],
            ['setting' => 'registration_end_date']
        );
        $this->resetPlayer($db_user, $main_province);

        return $db_user;
    }

    /**
     * <example>
     *
     * $additional_tables = [
     *   'table_name' => 'user_id_column', // we use this for tables that does not have column 'user_id'
     *   'just_table_name', // script automaticly assume the column is 'user_id'
     * ]
     *
     * </example>
     * @param $user_id
     * @param array $additional_tables
     *
     * @return void
     */
    function cleanupTestPlayer($user_id, $additional_tables = [])
    {
        $queries = [];
        $additional_tables += self::DEFAULT_ADDITIONAL_USER_TABLES;

        foreach ($additional_tables as $key => $value) {
            $column_name = 'user_id';
            if (is_int($key)) {
                $table_name = $value;
            } else {
                $column_name = $value;
                $table_name = $key;
            }

            $queries[] = "DELETE FROM {$table_name} WHERE {$column_name} = '{$user_id}';";
        }

        $queries[] = "DELETE FROM users WHERE id = '{$user_id}';";

        foreach ($queries as $query) {
            phive('SQL')->query($query);
            if (phive('SQL')->isSharded('users')) {
                phive('SQL')->sh($user_id)->query($query);
            }
        }
    }

    /**
     * Return mocked game array
     *
     * @param string $ext_game_name
     * @param string $network
     * @param string $device_type
     * @param string $device_type_num
     * @return array
     */
    public function getTestGame(
        string $ext_game_name = 'Mocked_test_game',
        string $network = 'microgaming',
        string $device_type = 'flash',
        string $device_type_num = '0'
    ): array {
        phive('SQL')->shs()->query("DELETE FROM micro_games WHERE ext_game_name = '{$ext_game_name}'");

        $game = [
            'game_name' => 'Mocked test game',
            'tag' => 'videoslots',
            'game_id' => $ext_game_name,
            'languages' => 'de,en,fi,no,sv',
            'ext_game_name' => $ext_game_name,
            'client_id' => '10001',
            'module_id' => '60013',
            'game_url' => 'mocked-test-game',
            'meta_descr' => '#game.meta.description.mocked-test-game',
            'bkg_pic' => 'html_copsnrobbersmr.jpg',
            'html_title' => '#game.meta.title.mocked-test-game',
            'played_times' => '0',
            'orion_name' => 'Flash - AWP - Mocked test game',
            'ribbon_pic' => '1',
            'included_countries' => '',
            'blocked_logged_out' => '',
            'operator' => $network,
            'network' => $network,
            'device_type' => $device_type,
            'device_type_num' => $device_type_num,
        ];

        $id = phive('SQL')->insertArray('micro_games', $game);

        $game['id'] = $id;
        $game['game_name'] = $game['game_name'] . $id;
        $game['client_id'] = $game['client_id'] . $id;
        $game['module_id'] = $game['module_id'] . $id;

        phive('SQL')->insertArray('micro_games', $game, null, true);

        return $game;
    }

    /**
     * This method handles printing assertion messages.
     *
     * @param bool $res
     * @param string|null $false_msg
     * @param string|null $true_msg
     * @param bool|null $die_on_false
     * @param bool|null $print_heading
     */
    function msg(
        bool $res,
        $false_msg = null,
        $true_msg = null,
        $die_on_false = false,
        $print_heading = false
    ) {
        if (empty($false_msg)) {
            $false_msg = self::DEFAULT_TEST_FAILED_MESSAGE;
        }

        if (empty($true_msg)) {
            $true_msg = self::DEFAULT_TEST_PASSED_MESSAGE;
        }

        if ($this->print_assertion_result) {
            if ($print_heading) {
                echo self::ASSERTION_HEADING;
            }

            if ($res) {
                $this->printAssertionMessage(self::TEST_PASSED, $true_msg);
            }

            if (!$res) {
                $this->printAssertionMessage(self::TEST_FAILED, $false_msg);
                if ($die_on_false) {
                    exit;
                }
            }
        }
    }

    /**
     * Internal method used for correctly printing out false or true messages after assertion
     *
     * @param string $prefix
     * @param string $message
     */
    private function printAssertionMessage(string $prefix, string $message = '')
    {
        echo $prefix;
        if (!empty($message)) {
            echo " " . $message . self::CONSOLE_COLOR_RESET;
        }
        echo PHP_EOL;
    }

    /**
     * This method is used for printing out all the input data we use in our tests
     *
     * @param string $data
     * @param bool $print_heading
     *
     * @return void
     */
    function printInputData(string $data, bool $print_heading = false)
    {
        if ($this->print_input_data) {
            if ($print_heading) {
                echo self::INPUT_HEADING;
            }

            echo self::CONSOLE_COLOR_BLUE . $data . PHP_EOL . self::CONSOLE_COLOR_RESET;
        }
    }

    /**
     * This method is used for printing out all the output data we use in our tests
     *
     * @param string $data
     * @param bool $print_heading
     *
     * @return void
     */
    function printOutputData(string $data, bool $print_heading = false)
    {
        if ($this->print_output_data) {
            if ($print_heading) {
                echo self::OUTPUT_HEADING;
            }

            echo self::CONSOLE_COLOR_BLUE . $data . PHP_EOL . self::CONSOLE_COLOR_RESET;
        }
    }

    /**
     * @return int
     */
    function randId(): int
    {
        return rand(1000000, 1000000000);
    }

    function prepare($user, $game)
    {
    }

    static function getModule($module)
    {
        if(empty($module)){
            return false;
        }
        require_once __DIR__ . "/Test$module.php";
        $class_name = "Test$module";
        return new $class_name($module);
    }

    static function doTests($to_test, $type, $setup = array())
    {
        foreach ($to_test as $module => $methods) {
            $obj = self::getModule($module);
            call_user_func_array(array($obj, 'setup'), $setup);
            $obj->setInfo($type);
            if (!empty($methods)) {
                $obj->info = array_intersect_key($obj->info, array_combine($methods, $methods));
            }
            $obj->test();
        }
    }

    function testMethods()
    {
        foreach ($this->test_methods as $method => $args) {
            $res = call_user_func_array([$this->class, $method], $args);
            echo "Method: $method, res:\n";
            if (is_array($res[0])) {
                $res = array_pop($res);
            }
            print_r($res);
            echo "\n";
        }
    }

    function doHandlers($to_test, $type, $setup = array())
    {
        $this->only_handlers = true;
        self::doTests($to_test, $type, $setup);
    }

    function setup($username = 'hsarvell', $reset_user = false, $reset_tables = false)
    {
        $uh = phive('UserHandler');
        $this->user = $uh->getUserByUsername($username);
    }

    function niceArgs($args): string
    {
        $ret = '';
        foreach ($args as $value) {
            if (is_object($value)) {
                $ret .= ' ' . get_class($value) . ' ';
            } else {
                if (is_array($value)) {
                    $ret .= ' array(' . implode(',', $value) . ') ';
                } else {
                    $ret .= " $value ";
                }
            }
        }
        return $ret;
    }

    function testCase($func, $args)
    {
        if (is_array($args)) {
            $args = $args['args'];
        }

        if (!empty($args)) {
            $arg_arr = is_string($args) ? explode(',', $args) : $args;
        } else {
            $arg_arr = array();
        }

        phive("SQL")->clearDebug();

        if ($this->only_handlers !== true) {
            if (empty($arg_arr)) {
                $res = $this->class->$func();
            } else {
                $res = call_user_func_array(array($this->class, $func), $arg_arr);
            }

            //if(is_array($args))
            //   $args = str_replace("\n", '', $this->niceArgs($args));
        } else {
            $handler = $this->handlers[$func];
            if (!empty($handler)) {
                $res = empty($arg_arr) ? $handler($this) : call_user_func_array(
                    $handler,
                    array_merge(array($this), $arg_arr)
                );
                echo "Calling $func with $args gave result: $res.\n";
                if (empty($res) && $this->sql_debug) {
                    phive("SQL")->printDebug();
                }
            }
        }

        if (empty($res)) {
            echo "Calling $func with $args gave empty result.\n";
            phive("SQL")->printDebug();
        } else {
            echo "Calling $func with $args OK.\n";
        }
    }

    function test()
    {
        $GLOBALS['sql_debug'] = true;
        foreach ($this->info as $func => $args) {
            if (is_array($args) && empty($args['args'])) {
                foreach ($args as $case) {
                    $this->testCase($func, $case);
                }
            } else {
                $this->testCase($func, $args);
            }
        }
    }

    /**
     * @param bool $print_input_data
     *
     * @return void
     */
    function setPrintInputData(bool $print_input_data)
    {
        $this->print_input_data = $print_input_data;
    }

    /**
     * @param bool $print_output_data
     *
     * @return void
     */
    function setPrintOutputData(bool $print_output_data)
    {
        $this->print_output_data = $print_output_data;
    }

    /**
     * @param bool $print_assertion_result
     *
     * @return void
     */
    function setPrintAssertionResult(bool $print_assertion_result)
    {
        $this->print_assertion_result = $print_assertion_result;
    }

    /**
     * Parse xml file and replace the placeholders with their value.
     *
     * @param $params
     * @param $directory
     * @param $filename
     * @return string
     */
    protected function parseFile($params, $directory, $filename): string
    {
        $needles = array_map(
            function ($key) {
                return '{{' . $key . '}}';
            },
            array_keys($params)
        );

        $xml = file_get_contents(
            realpath(dirname(__FILE__)) . '/../Test/' . $directory . '/request/' . $filename . '.xml'
        );

        return str_replace($needles, $params, $xml);
    }

    public function cliBos($u_obj, $args){
        return [];
    }

    public function cliUser($u_obj, $args){
        return [];
    }

    public function setupGameSession($args, $truncate = true){
        $casino = phive('Casino');
        if($casino->useExternalSession($args['u_obj'])){
            $sql = phive('SQL');
            $sh = phive('Test/SpainHelper');
            if($truncate){
                $sql->truncate('users_game_sessions');
                $sql->truncate('ext_game_participations');
                $sql->truncate('users_sessions');
            }
            return $sh->setupGameSession($sql, $casino, $args['u_obj'], $args['gref']);
        }
        return ['ext game sessions not used for this palyer'];
    }


    public function setupAjaxInitGameSession($args, $truncate = true){
        $casino = phive('Casino');
        if($casino->useExternalSession($args['u_obj'])){
            $sql = phive('SQL');
            $sh = phive('Test/SpainHelper');
            if($truncate){
                $sql->truncate('users_game_sessions');
                $sql->truncate('ext_game_participations');
                $sql->truncate('users_sessions');
            }
            return $sh->setupAjaxInitGameSession($args);
        }
        return ['ext game sessions not used for this palyer'];
    }

    public function cliInvocation($args = []){
        $args['mg_id'] = $args['mg_id'] ?? rand(1000000, 1000000000);
        $args['r_id'] = $args['r_id'] ?? rand(1000000, 1000000000);
        array_shift($_SERVER['argv']);

        while($_SERVER['argv']){
            $action = array_shift($_SERVER['argv']);
            $value  = array_shift($_SERVER['argv']);
            switch($action){
                case 'a':
                    $uid = (int)phive('SQL')->getValue("select id from users where alias = '$value'");
                    $u = cu($uid);
                    $ud = ud($u);
                    $args = array_merge($args, $this->cliUser($u, $args));
                    break;
                case 'u':
                    $u = cu($value);
                    $ud = ud($u);
                    $uid = (int)$u->getId();
                    $args = array_merge($args, $this->cliUser($u, $args));
                    break;
                case 'e':
                    $args['bos_uid'] = $uid . 'e'. $value;
                    $args = array_merge($args, $this->cliBos($u, $args));
                    break;
                case 'p':
                    $uid .= 'p'.$value;
                    break;
                case 'g';
                    $args['gid'] = $value;
                    break;
                case 'b':
                    $args['bet'] = $value;
                    break;
                case 'w':
                    $args['win'] = $value;
                    break;
                case 'frb':
                    $args['frb'] = $value;
                    break;
                case 'c':
                    $args['channel'] = $value;
                    break;
            }
        }

        return array_merge($args, [
            'u_obj' => $u ?? cu($args['uid']),
            'u_data' => $ud ?? ud($args['uid']),
            'uid' => $uid ?? $args['uid']
        ]);
    }


    public function urlParseVars($url){
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        return $query;
    }

    public function enablePlay($u_obj){
        $tc = TestPhive::getModule('CasinoCashier');
        $u_obj->deleteSettings('super-blocked', 'external-excluded');
        $u_obj->resetPlayBlock();
        $u_obj->deleteSetting('nodeposit-fraud-flag');
        $u_obj->setSetting('current_status', 'ACTIVE');
        $tc->approveKyc($u_obj);
    }

    /**
     * @param int    $user_id
     * @param string $trigger_name
     *
     * @return array
     */
    public function getUserTrigger(int $user_id, string $trigger_name): array
    {
        return phive('SQL')->sh($user_id)
            ->loadArray("SELECT * FROM triggers_log WHERE trigger_name = '{$trigger_name}' AND user_id = {$user_id}");
    }

    public function doesUserHaveTrigger(int $user_id, string $trigger_name): bool
    {
        return !empty($this->getUserTrigger($user_id, $trigger_name));
    }

    public function getTriggerCount(int $user_id, string $trigger_name): int
    {
        return phive('SQL')->sh($user_id)
            ->getValue("SELECT COUNT(id) FROM triggers_log
                WHERE trigger_name = '{$trigger_name}' AND user_id = {$user_id}");
    }

    /**
     * Get the latest risk level of the user.
     *
     * @param int $user_id
     * @param string $type
     * @return string
     */
    public function getLatestRiskLevel(int $user_id, string $type = 'RG'): string
    {
        if (!in_array($type, ['RG', 'AML'])) {
            return "Incorrect type";
        }

        return Phive('SQL')->sh($user_id)->getValue("
            SELECT rating_tag
            FROM risk_profile_rating_log
            WHERE user_id = {$user_id}
              AND rating_type = '{$type}'
            ORDER BY created_at DESC
            LIMIT 1
        ") ?? 'No Rating Found';
    }
}
