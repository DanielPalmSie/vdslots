<?php

use Carbon\Carbon;

require_once __DIR__ . '/../../api/PhModule.php';

class Distributed extends PhModule
{
    /** @var int $timeout */
    public $timeout;

    /** @var bool $debug */
    public $debug;

    /** @var array $rollback_data */
    private $rollback_data = [];

    /** @var int $timeout */
    private $remote_brand_id;


    const ERROR_NONNUMERICSRCBRNDID = 1;
    const ERROR_ALREADYMOVED = 2;
    const ERROR_ALREADYEXISTS = 3;
    const ERROR_FAILED = 4;
    const ERROR_ONLYFORADMINTRANSFER = 5;

    const SUCCESS_CODE = 10;

    /**
     * Distributed constructor.
     */
    public function __construct()
    {
        $this->timeout = $this->getSetting('timeout', 10);
        $this->debug = $this->getSetting('dump_debug', false);
    }

    /**
     * Setter for the timeout
     *
     * @param $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * @param $param
     * @return array
     */
    public function test($param)
    {
        return ['response_with_param' => $param, 'remote' => $this->getRemoteId()];
    }

    /**
     * We check if the customer exists in the local brand.
     * TODO add similarity checks
     *
     * Attempts for PWD check has to be max 6.
     *
     * @param string $username
     * @param string $password
     * @param bool $return_user
     * @param array $restricted_countries
     * @param bool $skip_password_check - used from DK nemid login context, we provide only email, as external login (NemID) will take place.
     * @return array
     */
    public function userInBrand($username, $password, $return_user = false, $restricted_countries = [], $skip_password_check = false)
    {
        $user = cu($username);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        if (in_array($user->getCountry(), $restricted_countries)) {
            return $this->fail('restricted-country');
        }

        if (!$skip_password_check && $user->getPassword() !== $password && !phive('UserHandler')->checkPassword($user, $password)) {
            return $this->fail('wrong-password');
        }

        if ($user->isBlocked() || $user->isSuperBlocked()) {
            return $this->fail('user-account-blocked');
        }

        return $this->success(empty($return_user) ? 'user-exists' : $user);
    }

    /**
     * We match the customer: for MGA can be only email, UK has complex criteria, SE/DK has NID to match.
     *
     * @param int $user_id
     * @param string $type
     * @param array $data
     *
     * @return array|mixed
     */
    public function matchUser($user_id, $type, $data)
    {
        $method = "match{$type}";

        if (!method_exists(linker(), $method)) {
            return $this->fail("Match type {$type} not supported.");
        }

        $res = call_user_func_array([linker(), $method], [$data]);

        if (!empty($res)) {
            $matches = count($res['matches']) > 1 ? $res['matches'] : [];

            return $this->success([
                'user_id' => $this->linkCustomer($res['main_match_id'], $this->getRemoteId(), $user_id),
                'matches' => $matches,
                'brand_id' => $this->getLocalBrandId(),
            ]);
        }

        return $this->success(0);
    }

    /**
     * We return the user data from the local brand
     *
     * @param $username
     * @param $password
     * @param bool $skip_password_check - used from DK nemid login context, we provide only email, as external login (NemID) will take place.
     * @return array
     */
    public function getUserDataFromBrand($username, $password, $skip_password_check = false)
    {
        $validate = $this->userInBrand($username, $password, true, [], $skip_password_check);
        if ($validate['success'] == false) {
            return $this->fail($validate['result']);
        }

        list($user, $settings, $permissions, $permission_groups) = $this->getDataFromUser($validate['result'], $this->getSetting('local_brand'));

        if (empty($user)) {
            return $this->fail('get-data-failed');
        }

        return $this->success(compact('user', 'settings', 'permissions', 'permission_groups'));
    }

    /**
     * @param $username
     * @param $password
     * @param bool $skip_password_check - used from DK nemid login context, we provide only email, as external login (NemID) will take place.
     * @return bool|int
     *
     * @api
     */
    public function createUserFromRemoteBrand($username, $password, $skip_password_check = false)
    {
        $remote = $this->getSetting('remote_brand');
        $local = $this->getSetting('local_brand');
        $data = toRemote($remote, 'getUserDataFromBrand', [$username, $password, $skip_password_check]);
        list($status, $code, $source_brand_user_id, $new_user_id, $msg) = $this->createPlayerFromBrand($remote, $local, $data, true);

        if($status){
            toRemote($remote, 'confirmMovedPlayer', [
                $source_brand_user_id,
                $this->getSetting('brand_map')[$local],
                $new_user_id,
            ]);
            return +$source_brand_user_id;
        }

        return $code;
    }


    /**
     * Method to create or update admin users on remote brands
     *
     * @param string $brand
     * @param string $username
     * @return false|string
     */
    public function copyUserToBrand(string $brand, string $username) {
        $from = $this->getSetting('local_brand');
        $data = $this->getUserDataFromBrand($username, null, true);

        list($status, $code, $source_brand_user_id, $new_user_id, $msg) = toRemote($brand, 'updatePlayerFromBrand', [$from, $brand, $data, true]);

        if($status){
            return $this->success($msg);
        }

        return $this->fail($msg);
    }

    /**
     * Get current rg limit for user
     *
     * @param $user_id
     * @param $type
     * @param $time_span
     * @return array
     */
    public function getCurrentLimit($user_id, $type, $time_span)
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $rgl = RgLimits()->getLimit($user, $type, $time_span);

        return $this->success(['userId' => $user->userId, 'currency' => $user->getCurrency(), 'rgl' => $rgl]);
    }

    /**
     * Updates existing or inserts new rg limit record
     *
     * @param $uid
     * @param $type
     * @param $time_span
     * @param $limit
     * @return array
     */
    public function saveLimit($uid, $type, $time_span, $limit)
    {
        $isSuccess = rgLimits()->saveLimit(cu($uid), $type, $time_span, $limit);

        if ($isSuccess) {
            return $this->success($isSuccess);
        } else {
            return $this->fail($isSuccess);
        }
    }

    /**
     * Remove an RG Limit
     *
     * @param $u_obj
     * @param $type
     * @return array
     */
    public function removeLimit($u_obj, $type)
    {
        return $this->success(rgLimits()->removeLimit($u_obj, $type));
    }

    /**
     * Add an RG Limit
     *
     * @param $uid
     * @param $type
     * @param $limits
     * @return array
     */
    public function addLimit($uid, $type, $limits)
    {
        $u_obj = cu($uid);
        if(empty($u_obj)) {
            $this->fail('not-found');
        }
        $rg_limits = rgLimits();
        // In case limit already exist on remote brand, we need to fire "changeResettable" call instead of "addResettable".
        $existing_limits = $rg_limits->getByTypeUser($u_obj, $type);
        if(empty($existing_limits)) {
            $result = phive('DBUserHandler/RgLimitsActions')->addResettable($u_obj, $type, $limits, true);
        } else {
            $result = phive('DBUserHandler/RgLimitsActions')->changeResettable($u_obj, $limits, $existing_limits, true);
        }

        if ($type === $rg_limits::TYPE_DEPOSIT) {
            $rg_limits->logCurrentLimit($u_obj);
        }

        return $this->success($result);
    }

    /**
     * Change an RG Limit
     *
     * @param $uid
     * @param $type
     * @param $limits
     * @return array
     */
    public function changeLimit($uid, $type, $limits)
    {
        // we need to fetch the resettable on the current brand to handle proper comparison.
        $u_obj = cu($uid);
        if(empty($u_obj)) {
            $this->fail('not-found');
        }
        $rg_limits = rgLimits();
        $resettable_limits = $rg_limits->getByTypeUser($u_obj, $type);

        $pretty_limits = $limits;
        foreach ($pretty_limits as $i => $rgl) {
            if (empty($rgl['limit'])) {
                continue;
            }
            $clean_limit = rgLimits()->prettyLimit($rgl['type'], $rgl['limit'], true);
            // convert cents values to EUR
            $pretty_limits[$i]['limit'] = $clean_limit;
        }
        $result = $this->success(phive('DBUserHandler/RgLimitsActions')->changeResettable($u_obj, $pretty_limits, $resettable_limits, true));

        if ($type === $rg_limits::TYPE_DEPOSIT) {
            $rg_limits->logCurrentLimit($u_obj);
        }

        return $result;
    }

    /**
     * Get all loses for specified username
     *
     * @param $user_id
     * @return array
     */
    public function getLossesForUser($user_id): array
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $col = 'result_amount';
        $user_losses = phive('MicroGames')->sumColsFromGameSessions(
            $user,
            [$col],
            [
                Carbon::now()->subtract('month', 1)->toDateTimeString(),
                Carbon::now()->toDateTimeString()
            ],
        );

        return $this->success($user_losses[$col] * -1);
    }

    /**
     * Get last loss stamp for specified username from users_game_sessions table
     *
     * @param $user_id
     * @return array|string
     */
    public function getLastLossForUser($user_id)
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $col = 'last_loss';
        $user_game_session = phive('MicroGames')->getLastLossFromGameSessions(
            $user,
            [
                Carbon::now()->subtract('month', 1)->toDateTimeString(),
                Carbon::now()->toDateTimeString()
            ]
        );

        return $user_game_session[$col];
    }

    /**
     * @param $machine
     * @param $arr
     * @param float|null $timeout
     * @param null $url
     * @param bool $repeat
     * @return mixed
     */
    public function post($machine, $arr, $timeout = null, $url = null, $repeat = true)
    {
        if (empty($url)) {
            $ss = $this->allSettings();
            $url = $ss['machines'][$machine];
        }

        $url .= '/phive/modules/Site/json/exec.php';

        $this->dumpLog('dist_post_data', array(func_get_args(), 'url' => $url));

        $arr['pwd'] = $this->getSetting('pwd');

        $timeout = $timeout ?? $this->timeout;

        $result = phive()->post($url, json_encode($arr), 'application/json', ['X-brand: '.phive('BrandedConfig')->getBrand()], 'dist', 'POST', $timeout);

        $this->dumpLog('dist_post_res', $result);

        return json_decode($result, true);
    }

    /**
     * We check the IP of the source machine, if there is no whitelisting we don't allow until at least 1 IP is set
     */
    public function validateIP()
    {
        $ip = remIp();
        if (empty($this->getSetting('ips')) || !in_array($ip, $this->getSetting('ips'))) {
            $this->retJsonOrDie($this->fail("IP $ip not allowed"), true);
        }
    }

    /**
     * Setter for the remote id variable
     *
     * @param int $remote_brand_id
     */
    public function setRemoteId($remote_brand_id)
    {
        $this->remote_brand_id = $remote_brand_id;
    }

    /**
     * Getter for the remote id variable
     *
     * @return int
     */
    private function getRemoteId()
    {
        return $this->remote_brand_id;
    }

    /**
     * TODO this was copied from previous implementation to be refactor for something stronger in the future
     *
     * @param $req
     */
    public function validateAuth($req)
    {
        if (empty($req['pwd']) || $req['pwd'] != $this->getSetting('pwd')) {
            $this->retJsonOrDie($this->fail('Authentication failed'), true);
        }

        if (!empty($req['remote'])) {
            $this->setRemoteId($req['remote']);
        }
    }

    /**
     * TODO add a config with a method whitelist
     *
     * @param $req
     * @return mixed
     */
    public function exec($req)
    {
        $obj = phive($req['class']);
        $obj->remote = true;
        $this->remote = true;
        $GLOBALS['is_remote_call'] = true;
        return call_user_func_array(array($obj, $req['method']), $req['params']);
    }

    /**
     * @return bool
     */
    public function isRemoteCall(): bool{
        return $GLOBALS['is_remote_call'] ?? false;
    }

    function getToken($ud){
        $token = phive()->uuid();
        phMset($token, json_encode($ud), 3600);
        return $token;
    }

    function getCols($table){
        $allowed_cols = array();
        foreach(phive('SQL')->loadObjects("SHOW COLUMNS IN ".$table) as $col)
            $allowed_cols[] = $col->Field;
        return $allowed_cols;
    }

    function saveData($table, $data){
        $insert = $this->filterCol($table, $data);
        phive('SQL')->save($table, $insert, null, true);
        echo phive('SQL')->insertBigId();
    }

    function filterCol($table, $data){
        $allowed_cols = $this->getCols($table);
        $allowed_cols = array_combine($allowed_cols, $allowed_cols);
        return array_intersect_key($data, $allowed_cols);
    }

    function machineDo($machine, $arr, $action, $url = ''){
        $arr['action'] = $action;
        $arr['pwd']    = $this->getSetting('pwd');
        return $this->post($machine, $arr, $url);
    }

    function machineExec($machine, $class, $method, $params, $url = '', $do_ws = true){
        if(!is_array($params))
            $params = array($params);
        $res = $this->machineDo($machine, array('class' => $class, 'method' => $method, 'params' => $params), 'exec', $url);
        if(($res === false || $res === 'nok') && $do_ws)
            toWs(array('msg' => "$class: $method did not execute on machine $machine"), 'adminerror', $_SESSION['user_id']);
        return $res;
    }

    function doAllSort($class, $method, $params, $sort_by, $flag){
        $res = call_user_func_array('array_merge', $this->doAll($class, $method, $params));
        if(!empty($sort_by))
            $res = phive()->sort2d($res, $sort_by, $flag);
        return $res;
    }

    function doAll($class, $method, $params, $flatten){
        $res = array();
        foreach($this->getSetting('machines') as $num => $url)
            $res[ $num ] = $this->machineExec( $num, $class, $method, $params, $url );
        if($flatten)
            return phive()->flatten($res);
        return $res;
    }

    function getMachineUrlFromUid($uid){
        $ms  = $this->getSetting('machines');
        return $ms[$this->getMachineFromUid($uid)];
    }

    function getMachineFromUid($uid){
        $machines = $this->getSetting('machines');
        return $uid % count($machines);
    }

    function movePlayers($machine, $players){
        $map = array(
            'actions'             => array('uid' => 'target'),
            'bets'                => '',
            'bonus_entries'       => array('pid' => 'id'),
            'busts'               => '',
            'cash_transactions'   => array('pid' => 'id'),
            'deposits'            => array('pid' => 'id'),
            'failed_bonuses'      => '',
            'failed_deposits'     => array('pid' => 'id'),
            'failed_logins'       => '',
            'failed_transactions' => array('pid' => 'id'),
            'first_deposits'      => array('pid' => 'id'),
            'lga_log'             => '',
            'mosms_check'         => '',
            'pending_withdrawals' => array('pid' => 'id'),
            'sms_queue'           => '',
            'users_games_favs'    => '',
            'users_settings'      => '',
            'users_blocked'      => '',
            'wins'                => '');
        $sql = phive('SQL');
        foreach($players as $u){
            foreach($map as $tbl => $conf){
                $uid_field = empty($conf['uid']) ? 'user_id' : $conf['uid'];
                $pid_field = empty($conf['pid']) ? 'id' : $conf['pid'];
                $rows = $sql->arrayWhere($tbl, array($uid_field => $u['id']));
                $res = $this->moveRows($machine, $rows, $tbl, $pid_field);
                if(!$res)
                    return false;
            }
            $res = $this->machineExec($machine, 'SQL', 'insertArray', array('users', $u));
            if($res)
                $sql->delete('users', array('id' => $u['id']));
            else
                return false;

        }
    }

    public function rofusRequest($base_url, $xml, $timeout, $extra, $extra_headers = '', $debug_key = 'rofus')
    {
        return phive()->post($base_url, $xml, 'text/xml', $extra_headers, $debug_key, 'POST', $timeout, $extra);
    }

    /**
     * To check internal self exclusion
     *
     * @param array|string $user_ids List of users ordered by match level or a single user id
     */
    public function checkSelfExclusion($user_ids)
    {

        $checkSelfExclusion = function ($user_id) {
            $user = cu($user_id);
            if (empty($user)) {
                return false;
            }

            $settings = $user->getSettingsIn(['excluded-date', 'unexclude-date', 'indefinitely-self-excluded']);

            if (count($settings) >= 2) {
                $result = ['status' => 'Y'];
                foreach ($settings as $setting) {
                    $result[$setting['setting']] = $setting['value'];
                }
            } else {
                if (!empty($setting_value = $user->getSetting('unexcluded-date')) && $user->isSelfLocked() === false) {
                    $result = [
                        'status' => 'P',
                        'unexcluded-date' => $setting_value,
                        'excluded-date' => $user->getSetting('excluded-date'),
                        'active' => $user->getAttr('active')
                    ];
                } else {
                    $result = ['status' => 'N'];
                }
            }
            return $result;
        };

        if (!is_array($user_ids)) {
            if (empty($result = $checkSelfExclusion($user_ids))) {
                return $this->fail('not-found');
            } else {
                return $this->success($result);
            }
        }

        $results = [];
        foreach ($user_ids as $user_id) {
            $result = $checkSelfExclusion($user_id);
            if ($result['status'] == 'Y') {
                return $this->success($result);
            }
            $results[] = $result;
        }

        if (count(array_unique($results)) === 1 && current($results) === false) {
            return $this->fail('not-found');
        }

        return $this->success($results[0]);
    }

    /**
     * To check internal self lock
     *
     * @param array|string $user_ids List of users ordered by match level or a single user id
     */
    public function checkSelfLock($user_ids)
    {
        $checkSelfLock = function ($user_id) {
            $user = cu($user_id);
            if (empty($user)) {
                return false;
            }

            if ($user->isSelfLocked()) {
                $result = [
                    'status' => 'Y',
                    'lock-hours' => $user->getSetting('lock-hours'),
                    'lock-date' => $user->getSetting('lock-date'),
                    'unlock-date' => $user->getSetting('unlock-date'),
                ];
            } else {
                $result = ['status' => 'N'];
            }
            return $result;
        };

        if (!is_array($user_ids)) {
            if (empty($result = $checkSelfLock($user_ids))) {
                return $this->fail('not-found');
            } else {
                return $this->success($result);
            }
        }

        $results = [];
        foreach ($user_ids as $user_id) {
            $result = $checkSelfLock($user_id);
            if ($result['status'] == 'Y') {
                return $this->success($result);
            }
            $results[] = $result;
        }

        if (count(array_unique($results)) === 1 && current($results) === false) {
            return $this->fail('not-found');
        }

        return $this->success($results[0]);
    }

    /**
     * Moving games across brands
     *
     * 1. We reset destination brand (local) as any game might have been activated
     * 2. We select every game from source, if game exists in local brand we activate it, remove sv, da and it as languages
     *          and remove SE from blocked countries
     * 3. We fetch all trophies from source brand and we copy them to local brand
     *
     * @param $network
     * @param string $source_database
     * @param bool $reset_local_brand In case a game was enabled to do testing we disable everything to avoid duplicates
     */
    public function moveGamesToBrand($network, $source_database = 'vs_master_reader', $reset_local_brand = true)
    {

        $trophy_games = phive('SQL')->doDb($source_database)
            ->loadArray("SELECT DISTINCT ext_game_name FROM micro_games WHERE network = '{$network}' GROUP BY ext_game_name;");

        echo "Moving trophies\n";
        foreach ($trophy_games as $game) {
            if ($reset_local_brand === true) {
                $game_exists = phive('SQL')->loadArray("SELECT * FROM micro_games WHERE ext_game_name = '{$game['ext_game_name']}'");
                if (empty($game_exists)) { //Game does not exists in local brand we don't do trophy
                    continue;
                }
            } else {
                $game_active = phive('SQL')->loadArray("SELECT * FROM micro_games WHERE ext_game_name = '{$game['ext_game_name']}' AND active = 1");
                if (!empty($game_active)) { //Game is active in local brand we don't do trophy
                    continue;
                }
            }

            $trophies = phive('SQL')->doDb($source_database)->loadArray("SELECT * FROM trophies WHERE game_ref = '{$game['ext_game_name']}'");

            foreach ($trophies as $trophy) {
                unset($trophy['id']);
                phive("SQL")->save('trophies', $trophy);
            }
            echo "+";
        }

        if ($reset_local_brand === true) {
            phive('SQL')->shs()->query("UPDATE micro_games SET active = 0 WHERE network = '{$network}' AND active = 1;");
        }

        echo "Activating games\n";
        $active_games = phive('SQL')->doDb($source_database)
            ->loadArray("SELECT * FROM micro_games WHERE network = '{$network}' AND active = 1;");

        foreach ($active_games as $game) {

            $update_query = "UPDATE micro_games SET active = 1 WHERE id = {$game['id']}";

            phive('SQL')->shs()->query($update_query);
            echo "+";
        }

        echo "Done\n";
    }

    /**
     * Function will sync the country blocking for a given country to match same as the source brand
     *
     * @param string $network
     * @param string $country
     * @param string $source_database
     * @return int
     */
    public function syncGameCountryBlockToBrand($network, $country, $source_database = 'vs_master_reader')
    {
        $games = phive('SQL')->doDb($source_database)->loadArray("SELECT DISTINCT ext_game_name FROM micro_games
                                                                            WHERE network = '{$network}'
                                                                             AND blocked_countries NOT LIKE '%{$country}%'
                                                                          GROUP BY ext_game_name;");
        $i = 0;
        foreach ($games as $game) {
            $local_games = phive('SQL')->loadArray("SELECT * FROM micro_games WHERE ext_game_name = '{$game['ext_game_name']}'");
            if (empty($local_games)) { //Game does not exists in local brand we don't do anything
                continue;
            }

            foreach ($local_games as $local_game) {
                $countries = explode(' ', $local_game['blocked_countries']);
                if (($key = array_search($country, $countries)) !== false) {
                    unset($countries[$key]);
                }
                $countries = implode(' ', $countries);

                phive('SQL')->shs()->query("UPDATE micro_games SET blocked_countries = '{$countries}' WHERE id = {$local_game['id']}");

                $i++;
            }
        }

        return $i;
    }

    /**
     * This function moves localized strings between Brands
     *
     * @param string $source_database
     * @param string $language
     * @param array $exceptions
     * @return bool
     */
    public function moveLocalizedStringsToBrand(string $language, $source_database = 'vs_master_reader', $exceptions = null)
    {
        if (!empty($source_database) && phive('Localizer')->languageExists($language)) {
            $extra = !empty($exceptions) ? "AND alias NOT IN (" . phive('SQL')->makeIn($exceptions) . ")" : "";
            $localized_strings = phive('SQL')->doDb($source_database)
                ->loadArray("SELECT * FROM localized_strings WHERE language = '{$language}' {$extra}");
            echo "Moving localized strings\n";
            foreach ($localized_strings as $string) {
                phive("SQL")->save('localized_strings', $string);
                echo "+";
            }
            echo "\nDone\n";
            return true;
        }
        return false;
    }


    /**
     * @param $query
     * @param string $source_brand
     * @param string $destination_brand
     */
    public function movePlayersToBrand($query, $source_brand = 'videoslots', $destination_brand = 'mrvegas')
    {
        $source_brand_id = $this->getSetting('brand_map')[$source_brand];
        $destination_brand_id = $this->getSetting('brand_map')[$destination_brand];

        /** @var DBUserHandler $uh */
        $uh =  phive('DBUserHandler');

        $users_list = phive('SQL')->shs()->loadArray($query);

        $failed = $sent = 0;

        foreach (array_chunk($users_list, 1000) as $users_chunk) {

            $to_send = [];
            foreach ($users_chunk as $user) {

                $user_obj = cu($user);

                if (empty($user_obj)) {
                    $failed++;
                    continue;
                }

                if ($user_obj->isSuperBlocked() || $uh->isSelfExcluded($user_obj) || $uh->isExternalSelfExcluded($user_obj)) {
                    $failed++;
                    continue;
                }

                list($user, $settings, $permissions, $permission_groups) = $this->getDataFromUser($user_obj, $source_brand);

                $user_obj->setSetting("c{$destination_brand_id}_id", 'moving', false);

                $sent++;

                $to_send[] = [$source_brand, $destination_brand, $user, $settings];

            }

            phive('Site/Publisher')->bulk('brand-worker', 'Distributed', 'createPlayerFromBrand', $to_send, true, $destination_brand);
        }

        echo "Total sent: {$sent}, failed: {$failed}.\n";
    }

    /**
     * @param $user_obj
     * @param string $source_brand
     * @return array|bool[]
     */
    private function getDataFromUser($user_obj, $source_brand = 'videoslots')
    {
        $source_brand_id = $this->getSetting('brand_map')[$source_brand];

        $user = ud($user_obj);
        if (empty($user)) {
            return [false, false];
        }

        $user['bonus_code'] = '';
        $user['affe_id'] = 0;
        $user['cash_balance'] = 0;

        $settings = [
            'pp-version' => ['1.0',''],
            "c{$source_brand_id}_id" => [$user['id'], '']
        ];

        $extra_settings = ['email_code_verified', 'acuris-qrcode', 'acuris_pep_res', 'acuris_full_res',
            'experian_res', 'experian_error', 'id3global_full_res', 'id3global_res', 'id3global_pep_res',
            'experian_block', 'deposit_block', 'sms_code_verified', 'nid_data', 'verified-nid'];

        $lic_extra_settings = lic('getUserSettingsFields', [], $user) ?: [];

        $extra_settings = array_merge($extra_settings, $lic_extra_settings);

        $in_settings = phive('SQL')->makeIn($extra_settings);

        $extra_settings_from_db = phive('SQL')->sh($user_obj)
            ->loadArray("SELECT * FROM users_settings WHERE user_id = {$user_obj->getId()} AND setting IN({$in_settings})");
        foreach ($extra_settings_from_db as $row) {
            $settings[$row['setting']] = [$row['value'], $row['created_at']];
        }

        $permissions = phive('SQL')->loadArray("SELECT * FROM permission_users WHERE user_id={$user_obj->getId()}");
        $permission_groups = phive('SQL')->loadArray("SELECT * FROM groups_members WHERE user_id={$user_obj->getId()}");

        unset($user['id']);

        return [$user, $settings, $permissions, $permission_groups];
    }

    /**
     * Get the internal id of the current local brand, meaning where the code is executed.
     *
     * @return mixed
     */
    public function getLocalBrandId()
    {
        return $this->getBrandIdByName($this->getSetting('local_brand'));
    }

    /**
     * Get the pretty name of a brand typically for display purposed like in admin2 (ex. Videoslots, Mr Vegas)
     *
     * @param string $name Normalised brand name (videoslots, mrvegas)
     * @return mixed
     */
    public function getBrandPrettyName($name)
    {
        return $this->getSetting('brand_map_pretty_name')[$name];
    }

    /**
     * Get the id of a brand given a remote
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getBrandIdByName($name)
    {
        return $this->getSetting('brand_map')[$name];
    }

    /**
     * @param $source_brand_user_id
     * @param $brand_id
     * @param $destination_id
     * @param array $matches Full list of matches with the level of matching
     * @return int
     */
    public function linkCustomer($source_brand_user_id, $brand_id, $destination_id, array $matches = [])
    {
        if (empty($source_brand_user_id)) {
            return false;
        }

        $user = cu($source_brand_user_id);
        if (empty($user)) {
            error_log("confirmMovedPlayer error - source uid {$source_brand_user_id} - dest uid {$destination_id} - User does not exists in this brand");
            return false;

        } else {
            $do_action_log = lic('logActionOnMatch', [], $user) === true;
            $key = "c{$brand_id}_id";
            if (!$user->hasSetting($key)) {
                $user->setSetting($key, $destination_id, $do_action_log);
            }
            if (!empty($matches)) {
                $user->setSetting("c{$brand_id}_matches", json_encode($matches), $do_action_log);
            }
            if ($do_action_log) {
                phive('UserHandler')->logAction($user, "Account linked with {$this->getSetting('remote_brand')} id {$destination_id}",
                    'brand-link', false);
            }
            return $source_brand_user_id;
        }
    }

    /**
     * @param       $source_brand_user_id
     * @param       $brand_id
     * @param       $destination_id
     * @param array $matches
     *
     * @return array
     */
    public function confirmMovedPlayer($source_brand_user_id, $brand_id, $destination_id, array $matches = [])
    {
        $this->linkCustomer($source_brand_user_id, $brand_id, $destination_id, $matches);

        return $this->success('ok');
    }

    /**
     * @param $source_brand
     * @param $destination_brand
     * @param $data
     * @param $return_response
     * @return array
     */
    public function createPlayerFromBrand($source_brand, $destination_brand, $data, $return_response = false): array
    {
        $msg = '';
        $user_data = $data['result']['user'];
        $settings = $data['result']['settings'];
        $permissions = $data['result']['permissions'];
        $permissionGroups = $data['result']['permission_groups'];

        /** @var SQL $sql */
        $sql = phive('SQL');

        $source_brand_id = $this->getSetting('brand_map')[$source_brand];
        $destination_brand_id = $this->getSetting('brand_map')[$destination_brand];
        $brand_key = "c{$source_brand_id}_id";
        $source_brand_user_id = $settings[$brand_key][0];

        if (!is_numeric($source_brand_user_id)) {
            $msg = "Source brand user id is not numeric: ". json_encode(compact('source_brand_user_id', 'user_data'));
            error_log($msg);
            return [false, self::ERROR_NONNUMERICSRCBRNDID, $source_brand_user_id, null, $msg];
        }
        //Check if original brand setting exists
        $already_moved = phive('SQL')->shs()->loadArray("SELECT * FROM users_settings WHERE setting = '{$brand_key}' AND value = '{$source_brand_user_id}'");

        if (count($already_moved) > 0) {
            $local_brand_user_id = $already_moved[0]['user_id'];
            $msg = "User was already moved with source brand id {$source_brand_user_id}. ID: $local_brand_user_id";
            error_log($msg);
            return [false, self::ERROR_ALREADYMOVED, $source_brand_user_id, $local_brand_user_id, $msg];
        }

        //Check if email/username or mobile exists
        $where_extra = " OR mobile = '{$user_data['mobile']}' OR username = '{$user_data['mobile']}'";
        if (phive('UserHandler')->countUsersWhere('email', $user_data['email'], 'users', $where_extra) > 0) {
            $msg = "User already exists in brand with details: {$user_data['mobile']}, {$user_data['email']}, {$user_data['username']}";
            error_log($msg);
            return [false, self::ERROR_ALREADYEXISTS, $source_brand_user_id, null, $msg];
        }

        //Check if currency exists in brand we set the default
        $has_currency = phive("SQL")->getValue("SELECT COUNT(*) FROM currencies WHERE code = '{$user_data['currency']}'");
        if (empty($has_currency)) {
            $user_data['currency'] = phive("Currencer")->baseCur();
        }

        $has_language = phive("SQL")->getValue("SELECT COUNT(*) FROM languages WHERE language = '{$user_data['preferred_lang']}'");
        if (empty($has_language)) {
            $user_data['preferred_lang'] = phive('Localizer')->getDefaultLanguage();
        }

        $user_data['register_date'] = date('Y-m-d h:i:s');
        $user_data['last_login'] = '0000-00-00 00:00:00';

        $user_id = phive('SQL')->insertArray('users', $user_data);
        if (empty($user_id)) {
            $msg = "User failed to be created with data: ". json_encode($user_data);
            error_log($msg);
            return [false, self::ERROR_FAILED, $source_brand_user_id, null, $msg];
        }

        $user_data['id'] = $user_id;
        phive('SQL')->sh($user_id)->insertArray('users', $user_data);

        $this->updatePlayerSettings($user_id, $settings);

        if(count($permissions)){
            $this->updatePlayerPermissions($user_id, $permissions);
        }

        if(count($permissionGroups)){
            $this->updatePlayerPermissionGroups($user_id, $permissionGroups);
        }


        $user = cu($user_id);

        lic('onUserCreatedOrUpdated', [$user_id, $user_data]);

        /**
         * TODO compare this with the ones from "onRegistrationEnd" & "createApprovedDocumentsAndVerify",
         *      process should be different in case of externally verified customers "verified-nid"
         *      some docs should be pre-approved. /Paolo
         */
        phive('Dmapi')->createInitialEmptyDocuments($user_id);

        $this->logActionOnTransfer($user, $source_brand, $source_brand_user_id);

        if ($return_response) {
            return [true, self::SUCCESS_CODE, $source_brand_user_id, $user_id, 'User has been successfully created. User ID: '.$user_id];
        }

        $arguments = [$source_brand_user_id, $destination_brand_id, $user_id];
        return phive('Site/Publisher')->single('brand-worker', 'Distributed', 'confirmMovedPlayer', $arguments, true, $source_brand);
    }

    /**
     * @param int $user_id
     * @param array $settings
     * @return void
     */
    private function updatePlayerSettings(int $user_id, array $settings): void {
        foreach ($settings as $key => $value) {
            $to_insert = ['user_id' => $user_id, 'setting' => $key, 'value' => $value[0]];
            if (!empty($value[1])) {
                $to_insert['created_at'] = $value[1];
            }
            phive('SQL')->sh($user_id)->save('users_settings', $to_insert);
        }
    }

    /**
     * Replaces user's permissions
     *
     * @param int $user_id
     * @param array $permissions
     * @return void
     */
    private function updatePlayerPermissions(int $user_id, array $permissions): void {
        $sql = phive('SQL');
        $sql->delete('permission_users', "user_id=$user_id", $user_id);

        for ($i = 0; $i < count($permissions); $i++) {
            $permission = $permissions[$i];
            $permission['user_id'] = $user_id;
            $sql->save('permission_users', $permission);
        }
    }

    /**
     * Replace user in a permission groups
     *
     * @param int $user_id
     * @param array $permission_groups
     * @return void
     */
    private function updatePlayerPermissionGroups(int $user_id, array $permission_groups): void {
        $sql = phive('SQL');
        $sql->delete('groups_members', "user_id=$user_id", $user_id);

        for ($i = 0; $i < count($permission_groups); $i++) {
            $permission_group = $permission_groups[$i];
            $permission_group['user_id'] = $user_id;
            $sql->save('groups_members', $permission_group);
        }
    }

    /**
     * Method tries to create user on a brand. Is user exists method updates user's data
     *
     * @param $source_brand
     * @param $destination_brand
     * @param $data
     * @param $return_response
     * @return array
     */
    public function updatePlayerFromBrand($source_brand, $destination_brand, $data, $return_response = false): array
    {
        $settings = $data['result']['settings'];
        $permissions = $data['result']['permissions'];
        $permissionsGroups = $data['result']['permission_groups'];
        $usersData = $data['result']['user'];

        if(!count($permissions)){
            return [false, self::ERROR_ONLYFORADMINTRANSFER, null, null, "It is possible to transfer users only with admin permissions"];
        }

        $response = $this->createPlayerFromBrand($source_brand, $destination_brand, $data, $return_response);
        list($status, $code, $source_brand_user_id, $local_user_id, $msg) = $response;


        //if user already exists we update settings and permissions
        if($status === false && $code == 2) {
            $sql = phive('SQL');

            $user = cu($local_user_id);

            $usersData['id'] = $local_user_id;
            // updating shard db
            phive("SQL")->sh($local_user_id, 'id', 'users')->updateArray('users', $usersData, array('id' => $local_user_id));
            // updating master db
            phive("SQL")->updateArray('users', $usersData, array('id' => $local_user_id));

            $this->updatePlayerSettings($local_user_id, $settings);

            if(count($permissions)){
                $this->updatePlayerPermissions($local_user_id, $permissions);
            }

            if(count($permissionsGroups)){
                $this->updatePlayerPermissionGroups($local_user_id, $permissionsGroups);
            }

            $this->logActionOnTransferUpdate($user, $source_brand, $source_brand_user_id);

            $msg = "User $local_user_id was successfully updated with data: ". json_encode($data);

            return [true, self::SUCCESS_CODE, $source_brand_user_id, $local_user_id, $msg];

        }

        return $response;
    }

    /**
     * Accepts the requests triggered from the other brand to insert the game (desktop+mobile) & game tags
     *
     * The following checks will be done, and we fail if any of the condition is met:
     * - desktop ext_game_name is already present on the DB - we check first desktop (flash) then mobile (html5)
     * - mobile ext_game_name is already present on the DB - we need to cover GP with different ext_game_name + mobile only scenario
     * -
     *
     * @param array $data
     *
     * @return array
     */
    public function moveGameToBrand($data)
    {
        list(
            $desktop_game_data,
            $mobile_game_data,
            $game_tags, // list of tag_id
            $bonus_types,
            $trophies,
            $trophy_awards,
            $images
            ) = $data;

        $desktop_game = phive('MicroGames')->getByGameRef($desktop_game_data['ext_game_name'], 'flash');
        if (!empty($desktop_game)) {
            return ['success' => false, 'message' => 'Desktop Game already exists'];
        }
        // from desktop game data
        $mobile_game = phive('MicroGames')->getByGameRef($desktop_game_data['ext_game_name'], 'html5');
        if (!empty($mobile_game)) {
            return ['success' => false, 'message' => 'Mobile Game already exists'];
        }

        // from mobile game data
        $mobile_game = phive('MicroGames')->getByGameRef($mobile_game_data['ext_game_name']);
        if ($mobile_game) {
            return ['success' => false, 'message' => 'Mobile Game already exists'];
        }

        try {
            $inserted_desktop_game = $this->insertGameAndTags($desktop_game_data, $game_tags);
            $inserted_mobile_game = $this->insertGameAndTags($mobile_game_data, $game_tags);
            if (!empty($inserted_desktop_game ?: $inserted_mobile_game)) {
                $bonus_types = $this->insertBonusTypes($bonus_types);
                $trophy_awards = $this->insertTrophyAwards($trophy_awards, $bonus_types);
                $this->insertTrophies($trophies, $trophy_awards);
            }
        } catch (\Exception $e) {
            phive('Logger')->error("move_games_to_brand", $e);
            $this->rollback();
            return [
                'success' => false,
                'message' => 'Internal error. Changes were rolled back.'
            ];
        }

        if (!empty($inserted_desktop_game) && !empty($inserted_mobile_game)) {
            $images_message = $this->saveImages($inserted_desktop_game, $images);
            return [
                'success' => true,
                'game' => $inserted_desktop_game,
                'mobile_game' => $inserted_mobile_game,
                'message' => "Game created. $images_message"
            ];
        }

        return [
            'success' => false,
            'message' => 'Something went wrong: ' .
                'desktop ' . (empty($inserted_desktop_game) ? 'KO' : 'OK') . ', ' .
                'mobile ' . (empty($inserted_mobile_game) ? 'KO' : 'OK')
        ];
    }

    /**
     * When an account transfer happen we want to log an action on the user.
     *
     * @param $user
     * @param $source_brand
     * @param $source_brand_user_id
     * @return mixed
     */
    private function logActionOnTransfer($user, $source_brand, $source_brand_user_id){
        $skipTransferAction = lic('getLicSetting', ['skip_transfer_action_log'], $user);
        if (!empty($skipTransferAction)) {
            return false;
        }

        $description = "Account transferred from '{$source_brand}', original id {$source_brand_user_id}";
        return phive('UserHandler')->logAction($user, $description, 'brand-account-transfer', false);
    }


    /**
     * When an update of account happens from source brand we want to log an action on the user.
     *
     * @param $user
     * @param $source_brand
     * @param $source_brand_user_id
     * @return mixed
     */
    private function logActionOnTransferUpdate($user, $source_brand, $source_brand_user_id){
        $description = "Account was successfully updated from '{$source_brand}', original id {$source_brand_user_id}";
        return phive('UserHandler')->logAction($user, $description, 'brand-account-update', false);
    }



    /**
     * Insert value in the database and keep the old_id for future usage
     * The old_is is used to keep the relationships between different types of data
     * The $unique_column is the column which has the 'UNIQUE' constraint applied
     *
     * Secondary role of this method is to cache data required for rollback
     *
     * @param string $table
     * @param array $item
     * @param null|string $unique_column
     * @return mixed
     */
    private function insertAndSetOldId($table, $item, $unique_column = null)
    {
        $old_id = $item['id'];
        unset($item['id']);
        // try to insert
        $item['id'] = phive('SQL')->insertArray($table, $item);

        // failed to insert because the item is already in the table
        // so we have to get the local ID for the existing entry based on the $unique_column
        if (empty($item['id'])) {
            if (!empty($unique_column)) {
                $item['id'] = phive('SQL')->loadAssoc("SELECT * FROM $table WHERE $unique_column = '{$item[$unique_column]}'")['id'];
            } else {
                $item['id'] = $old_id;
            }
        } else {
            // rollback only when item didn't previously exist in the local database
            $this->rollback_data[$table][] = $item['id'];
        }

        $item['old_id'] = $old_id;
        return $item;
    }

    /**
     * Get the new id of a specific item based on old_id
     *
     * @param string $old_id
     * @param array $items
     * @return int|mixed
     */
    private function getNewId($old_id, $items)
    {
        $old_id = (int)$old_id;
        if (empty($old_id)) {
            return 0;
        }

        foreach ($items as $item) {
            if ((int)$item['old_id'] === $old_id) {
                return $item['id'];
            }
        }
        return 0;
    }

    /**
     * Insert bonus types
     *
     * @param $bonus_types
     * @return mixed
     */
    private function insertBonusTypes($bonus_types)
    {
        foreach ($bonus_types as &$bonus_type) {
            $bonus_type = $this->insertAndSetOldId('bonus_types', $bonus_type);
        }

        return $bonus_types;
    }

    /**
     * Insert trophy awards
     *
     * @param $trophy_awards
     * @param $bonus_types
     * @return mixed
     */
    private function insertTrophyAwards($trophy_awards, $bonus_types)
    {
        foreach ($trophy_awards as &$trophy_award) {
            $trophy_award['bonus_id'] = $this->getNewId($trophy_award['bonus_id'], $bonus_types);
            $trophy_award = $this->insertAndSetOldId('trophy_awards', $trophy_award, 'alias');
        }

        return $trophy_awards;
    }

    /**
     * Insert trophies
     *
     * @param $trophies
     * @param $trophy_awards
     * @return mixed
     */
    private function insertTrophies($trophies, $trophy_awards)
    {
        foreach ($trophies as &$trophy) {
            $trophy['award_id'] = $this->getNewId($trophy['award_id'], $trophy_awards);
            $trophy = $this->insertAndSetOldId('trophies', $trophy, 'alias');
        }

        return $trophies;
    }

    /**
     * Insert game and game tags
     *
     * @param array $game_data
     * @param array $game_tags
     * @return false
     */
    private function insertGameAndTags(array $game_data, array $game_tags)
    {
        if (empty($game_data)) {
            return true;
        }

        $inserted_game = phive('SQL')->save("micro_games", $game_data);

        if (!$inserted_game) {
            return false;
        }

        // Fetch the inserted game (we need the ID) and assign the tags to it (if they exist)
        $game = phive('MicroGames')->getByGameRef($game_data['ext_game_name']);
        $this->rollback_data['micro_games'][] = $game['id'];
        foreach ($game_tags as $game_tag) {
            $this->insertAndSetOldId('game_tag_con', [
                'tag_id' => $game_tag,
                'game_id' => $game['id']
            ]);
        }
        return $game;
    }

    /**
     * Rollback inserted data
     */
    private function rollback()
    {
        try {
            foreach ($this->rollback_data as $table => $ids) {
                $ids = implode(',', $ids);
                phive('SQL')->query("DELETE FROM $table WHERE id IN ($ids)");
            }
        } catch (\Exception $e) {
            phive('Logger')->error("move_games_to_brand_rollback", [$this->rollback_data, $e]);
        }
    }

    /**
     * @param $game
     * @param $images
     * @return string
     */
    private function saveImages($game, $images)
    {
        $message = "Images not saved.";
        if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            $response = call_user_func_array([phive('Dmapi'), 'uploadPublicFile'], $images['thumbs_dmapi']);
            if (!empty($response) && empty($response['errors'])) {
                return "";
            }
            return $message;
        }

        try {
            $target = phive('Filer')->getFilePath('thumbs/' . $game['game_id'] . '_c.jpg');
            $downloaded = file_get_contents($images['thumbs']);
            if (!$downloaded) {
                throw new \Exception("Can't thumbnail download file.");
            }
            $saved = file_put_contents($target, $downloaded);
            if (!$saved) {
                throw new \Exception("Can't save thumbnail file.");
            }
            chmod($target,0777);
            return "";
        } catch (\Exception $e) {
            phive('Logger')->error("move_games_to_brand", [$images, $e->getMessage()]);
        }
        return $message;
    }

    /**
     * Get data to cancel a session for DK Reports from secondary brand
     *
     * @param $user_game_session_id - not used by remote brand but needed as function param
     * @param $user_id - not used by remote brand but needed as function param
     * @param $table - bets/wins used to determine which info we need to amend.
     * @param $amount
     * @param $session - full session array to send to remote brand
     * @return array|bool
     */
    public function cancelSessionReportFromSecondaryBrand($user_game_session_id, $user_id, $table, $amount, $session, $country)
    {
        if ($this->getSetting('safe_main_brand') !== true) {
            return $this->fail('Cancel Session Report is done in the Main Brand');
        }

        phive('Licensed')->doLicense($country, 'cancelReportSession', [$user_game_session_id, $user_id, $table, $amount, $session]);
        return $this->success('ok');
    }

    /**
     * @param $country
     * @param $date
     * @param $type
     * @param $cursor_regenerate
     * @param $cursor_secondary
     * @param $forced_values
     * @return array
     */
    public function getDataReportFromSecondaryBrand($country, $date, $type, $cursor_regenerate, $cursor_secondary, $forced_values)
    {
        if ($this->getSetting('safe_main_brand') === true) {
            return $this->fail('Remote data has to be from secondary brand');
        }
        $sessions = phive('Licensed')->doLicense($country, 'getDataReportFromSecondaryBrand', [$country, $date, $type, $cursor_regenerate, $cursor_secondary, $forced_values]);
        return $this->success($sessions);
    }

    /**
     * Return all data for affordability check from a remote brand for a specific user
     *
     * @param $user_id
     * @return false|mixed
     */
    function remoteAffordabilityCheck($user_id)
    {
        $user = cu($user_id);
        return lic('getAffordabilityChecks', [$user_id], $user);
    }

    /**
     * Return all data for vulnerability check from a remote brand for a specific user
     *
     * @param $user_id
     * @return false|mixed
     */
    function remoteVulnerabilityCheck($user_id) {
        $user = cu($user_id);
        return lic('getVulnerabilityChecks',[$user_id], $user);
    }

    /**
     * Remove self-exclusion to a local user linked on a remote brand
     * where the call has been requested
     *
     * @param string $user_id
     * @return array
     */
    public function remoteRemoveSelfExclusion(string $user_id) : array
    {
        $user = cu($user_id);
        try {
            if (!empty($user)) {
                $settings = lic('getLicSetting', ['cross_brand'], $user)['self_exclusion_settings'];
                if (!$settings) {
                    return $this->success('Remote user shouldnt be unexcluded');
                }

                foreach ($settings as $setting) {
                    $user->deleteSettings($setting);
                }

                $user->setSetting('unexcluded-date', phive()->today());
                lic('trackUserStatusChanges', [$user, UserStatus::STATUS_DORMANT], $user);

                return $this->success('Remote user unexcluded');
            } else {
                return $this->fail('Remote user does not exist');
            }
        } catch (\Exception $e) {
            return $this->fail('Error while unexcluding remote user');
        }
    }

    public function remoteCheckSelfExclusionByNid(string $nid, string $country) : array
    {
        $user = phive('UserHandler')->getUserByNid($nid, $country);

        if (empty($user)) {
            return $this->fail('Remote user does not exist');
        }

        $result = $this->checkSelfExclusion($user->getId());

        if ($result['result']['status'] === 'Y') {
            $self_excluded_settings = $user->getSettingsIn(['excluded-date', 'unexclude-date', 'indefinitely-self-excluded']);
            $settings = [];
            foreach ($self_excluded_settings as $setting) {
                $settings[$setting['setting']] = $setting['value'];
            }
            return $this->success([
                'excluded' => true,
                'settings' => $settings
            ]);
        }

        return $this->success(['excluded' => false]);

    }

    /**
     * Check if remote brand user has self-exclude settings, if no - add them and logout
     * @param $user_id
     * @param $duration
     * @param $permanent
     * @param $indefinite
     * @return array
     */
    public function syncSelfExclusionSettingWithRemoteBrand($user_id, $duration, $permanent = false, $indefinite = false): array
    {
        $self_exclusion_check = $this->checkSelfExclusion($user_id);

        if ($self_exclusion_check['result']['status'] === 'Y') {
            return $this->fail($self_exclusion_check);
        }

        phive('UserHandler')->logoutUser($user_id, 'logout user because of self-exclusion on another brand');

        phive('DBUserHandler')->selfExclude(cu($user_id), $duration, $permanent, null, true, $indefinite);

        return $this->success($user_id);
    }

    /**
     * @param $user_id
     * @param $setting
     * @param $value
     * @return array
     */
    public function syncSettingsWithRemoteBrand($user_id, $setting, $value){
        $user = cu($user_id);
        if(empty($user->getSetting($setting))) {
            $user->setSetting($setting, $value);
            return $this->success($user_id);
        } elseif($user->getSetting($setting) !== $value){
            $user->refreshSetting($setting, $value);
            return $this->success($user_id);
        }

        return $this->fail($user_id);
    }

    /**
     * Checks and resets a setting for the given user based on config:
     * - If the setting is in the list of settings that can be reset by the admin, the method proceeds with resetting it.
     * - If the setting is in the restricted list (`no_sync_settings_after_removal`), it blocks the removal
     * - Otherwise, it proceeds with resetting the setting for the user.
     *
     * @param int $user_id
     * @param string $setting
     * @param bool $isAdmin
     * @param string $source_brand
     * @return array
     */
    public function syncResetSettingWithRemoteBrand($user_id, $setting, bool $isAdmin = false, string $source_brand = ''): array
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        if (empty($user->getSetting($setting))){
            return $this->fail('setting-not-found');
        }

        $lic_setting = lic('getLicSetting', ['cross_brand'], $user);
        $no_sync_settings = $lic_setting['no_sync_settings_after_removal'] ?? [];
        $sync_reset_settings_admin = $lic_setting['sync_reset_settings_with_remote_brand_admin2'] ?? [];

        // If setting is restricted but admin has permission to override, allow removal
        if (in_array($setting, $no_sync_settings, true) && !($isAdmin && in_array($setting, $sync_reset_settings_admin, true))) {
            return $this->fail("Setting '$setting' cannot be removed due to config restriction.");
        }

        // Regular reset flow
        $user->deleteSetting($setting);
        $remote_brand = getRemote();

        if ($isAdmin) {
            phive("UserHandler")->logAction(
                $user,
                "Admin set {$setting} to 0 after {$remote_brand} check on {$source_brand}",
                $setting
            );
        }

        return $this->success($user_id);
    }

    /**
     * @param int|string $user_id
     * @param string $type
     * @param $time_span
     * @param $amount
     * @param $user_currency
     * @return array
     */
    public function incRemoteLimit($user_id, $type, $time_span, $amount, $user_currency)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $amount = chg($user_currency, $user->getCurrency(), $amount, 1);
        $rgl = rgLimits()->getLimit($user, $type, $time_span);

        if (!empty($rgl)) {
            return $this->success(rgLimits()->incLimit($rgl, $amount));
        }
        return $this->fail('limit-not-found');
    }

    /**
     * @param int|string $user_id
     * @param string $type
     * @param $time_span
     * @param int $amount
     * @param $user_currency
     * @return array
     */
    public function decRemoteLimit($user_id, $type, $time_span, $amount, $user_currency)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $amount = chg($user_currency, $user->getCurrency(), $amount, 1);
        $rgl = rgLimits()->getLimit($user, $type, $time_span);

        if (!empty($rgl)) {
            return $this->success(rgLimits()->decLimit($rgl, $amount));
        }
        return $this->fail('limit-not-found');
    }

    /**
     * @param int|string $user_id
     * @param string $type
     * @param $time_span
     * @param $limit
     * @param $extra
     * @param $user_currency
     * @param $progress
     * @return array
     */
    public function addRemoteLimit($user_id, $type, $time_span, $limit, $extra, $user_currency, $progress = null)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $limit = chg($user_currency, $user->getCurrency(), $limit, 1);
        if (!is_null($progress)) {
            $progress = chg($user_currency, $user->getCurrency(), $progress, 1);
        }

        $rg_limits_module = rgLimits();
        $rg_limits_module->setRemoteRequestContext();

        return $this->success($rg_limits_module->addLimit($user, $type, $time_span, $limit, $extra, false, $progress));
    }

    /**
     * @param int|string $user_id
     * @param $type
     * @return array
     */
    public function removeRemoteLimit($user_id, $type)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $rg_limits_module = rgLimits();
        $rg_limits_module->setRemoteRequestContext();

        return $this->success($rg_limits_module->removeLimit($user, $type));
    }

    /**
     * @param $user_id
     * @param $type
     * @param $time_span
     * @param $limit
     * @param $user_currency
     * @param $progress
     * @return array
     */
    public function changeRemoteLimit($user_id, $type, $time_span, $limit, $user_currency, $progress = null)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $limit = chg($user_currency, $user->getCurrency(), $limit, 1);
        if (!is_null($progress)) {
            $progress = chg($user_currency, $user->getCurrency(), $progress, 1);
        }

        $action = 'change';
        $increased = null;

        $rg_limits_module = rgLimits();
        $rg_limits_module->setRemoteRequestContext();

        return $this->success($rg_limits_module->changeLimit($user, $type, $limit, $time_span, [], $action, $increased, false, $progress));
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getFirstDeposit($user_id)
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $deposit = phive('Cashier')->getFirstDeposit($user_id);

        return $this->success($deposit);
    }

    /**
     * Returns the last deposit transaction (without any status reference)
     *
     * @param $user_id
     * @return array
     */
    public function getLastDeposit($user_id): array
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $deposit = phive('Cashier')->getDeposits('', '', $user_id, '', false, '', '', '', 1);

        return $this->success([
            'deposit' => $deposit[0] ?? [],
            'brand' => phive('Distributed')->getSetting('local_brand')
        ]);
    }

    public function getRemoteFirstRegistrationTimestamp($user_id): array
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $data = $user->getRegistrationTimestamp();
        $brand = phive('Distributed')->getSetting('local_brand');

        if (!empty($data['registered_at'])) {
            $data['source'] = $brand . "_" . $data['source'];
            return $this->success($data);
        }

        return $this->fail($data);
    }

    public function getRgLimitLastResetsAt(array $user_ids): array
    {
        $sql = phive('SQL');
        $resettable_limits = $sql->makeIn(rgLimits()->resettableTypes());

        $sharded_list = $sql->shardUsers($user_ids);
        $data = [];

        foreach ($sharded_list as $shard => $user_list) {
            $ids = $sql->makeIn($user_list);
            $query = "SELECT user_id, time_span, max(resets_at) as max_reset_at FROM rg_limits
                            WHERE user_id in ({$ids}) AND
                            type IN ({$resettable_limits})
                            GROUP BY user_id, time_span";
            $data = array_merge($data, $sql->readOnly()->sh($shard)->loadArray($query));
        }

        if (!empty($data)) {
            return $this->success($data);
        }

        return $this->fail(null);
    }

    /**
     * Resets 'resets_at' timestamps on remote brands according to user settings:
     *  - 'rg-month-limit-set-at'
     *
     * @param int  $user_id
     * @param array $max_limits
     *
     * @return array
     * @throws Exception
     */
    public function updateResetsAtTimestampForResettableLimits(int $user_id, array $max_limits): array
    {
        $user = phive('DBUserHandler')->newUser(['id' => $user_id]);
        $rg_limits_module = rgLimits();
        $sql_module = phive('SQL');
        $resettable_limits = $rg_limits_module->getResettAble($user);
        $resettable_limits_types = $sql_module->makeIn($rg_limits_module->resettableTypes());
        $grouped_limits = phive()->group2d($resettable_limits, 'time_span');
        $localBrand = getLocalBrand();
        $result = [];

        foreach ($grouped_limits as $time_span => $limits) {
            $time_span_max_reset_at = $max_limits[$time_span] ?? null;

            if(empty($time_span_max_reset_at)){
                continue;
            }

            $new_resets_at = $rg_limits_module->getFixedCycleResetStamp(
                $user,
                $time_span,
                new DateTime($time_span_max_reset_at)
            );
            $query = "UPDATE rg_limits SET resets_at = '{$new_resets_at}' " .
                "WHERE user_id = '{$user_id}' AND time_span = '{$time_span}' " .
                "AND `type` IN ({$resettable_limits_types})";

            $sql_module->readOnly()->sh($user_id)->query($query);
            foreach ($limits as $rg_limit) {
                $result[] = [
                    'brand' => $localBrand,
                    'user_id' => $user_id,
                    'type' => $rg_limit['type'],
                    'time_span' => $rg_limit['time_span'],
                    'old_resets_at' => $rg_limit['resets_at'],
                    'new_resets_at' => $new_resets_at,
                ];
            }
        }
        return $this->success($result);
    }

    /**
     * @param $user_id
     * @param $rgl
     * @param $type
     * @param $started_at
     * @return array
     */
    public function resetRemoteTimeLimit($user_id, $time_span, $type, $started_at)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $rgl = rgLimits()->getLimit($user, $type, $time_span);

        return $this->success(rgLimits()->resetTimeLimit($user, $rgl, $type, $started_at));
    }

    public function progressRemoteResettableTimeLimit($user_id, $type, $time_span, $progress, $use_cache = false)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $rgl = rgLimits()->getLimit($user, $type, $time_span);

        return $this->success(rgLimits()->progressResettableTimeLimit($user, $type, $rgl, $progress, $use_cache));
    }

    /**
     * @param             $uid
     * @param             $type
     * @param             $time_span
     * @param string|null $brand - returns result for specific brand (used by SCV)
     *
     * @return array
     */
    public function getRemoteUserLimit($uid, $type, $time_span, ?string $brand = null)
    {
        $user = cu($uid);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        return $this->success(rgLimits()->getLimit($user, $type, $time_span));
    }

    /** Checking the response from remote brand
     *
     * @param $response
     * @param $user
     * @param $action
     * @param $type
     * @param $time_span
     * @param $limit
     * @param $remote_brand
     * @return string
     */
    function checkSyncResponse($response, $user_id, $action, $type, $time_span, $limit, $remote_brand)
    {
        $negativeMessage = "Failed to {$action} for {$user_id} {$type} {$time_span} {$limit} to {$remote_brand}";
        if($response === null){
            return $negativeMessage . ". Can not connect to remote";
        }

        return $negativeMessage . $response['result'];
    }

    /**
     * @param $user
     * @param $type
     * @return int
     */
    public function shouldSyncForRemoteUser($user, $type): int
    {
        $remote_user_id = 0;
        if (!phive('Distributed')->isRemoteCall()) {
            //we do the cu() after we check remoteCall, to avoid several DB calls when not needed
            $user = cu($user);
            if (lic('shouldSyncLimit', [$type], $user)) {
                $remote_user_id = (int)linker()->getUserRemoteId($user->getId());
            }
        }
        return $remote_user_id;
    }


    /**
     * @param $user_id
     * @param $user_currency
     * @return array
     */
    public function getRemoteUserWithdrawDepositTotal($user_id, $user_currency): array
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }
        $transactions = phive('Cashier')->getTransactionSumsByUserIdProvider(($user_id), strtotime($user->data['register_date']), phive()->hisNow());
        $sum = $transactions['sum_deposits'] + $transactions['sum_withdrawals'];
        $sum_currency = chg($user->getCurrency(), $user_currency, $sum, 1);

        return $this->success($sum_currency);
    }

    /**
     * @param $user_id
     * @return array
     */
    public function getRemoteDocuments($user_id): array
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $documents = phive('Dmapi')->getUserDocumentsV2($user_id);
        if (is_array($documents) && !empty($documents)) {
            $local_brand = getLocalBrand();
            return $this->success([$local_brand => $documents]);
        }


        return $this->fail("Could not get documents from remote brand");
    }


    /**
     * @param $user_id
     * @param $setting_name
     * @return array
     */
    public function getRemoteSetting($user_id, $setting_name): array
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $setting_value = $user->getSetting($setting_name);

        if (! empty($setting_value)) {
            return $this->success($setting_value);
        }

        return $this->fail("{$setting_name} not found");
    }

    /**
     * @param $user_id
     * @param $document_tag
     * @param $status
     * @return array
     */
    public function updateRemoteDocumentStatus($user_id, $document_tag, $status)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $document = phive('Dmapi')->getDocumentByTag($document_tag, $user_id);
        if (empty($document['id'])) {
            return $this->fail("Could not find document {$document_tag} on remote brand");
        }

        $result = phive('Dmapi')->updateDocumentStatus($user_id, $document['id'], $status, $user_id);

        if (empty($result['errors'])) {
            return $this->success("Status of the {$document_tag} changed to {$status}");
        }

        return $this->fail("Status updating failed on document {$document_tag}");

    }

    /**
     * @param string $document_tag
     * @param int $user_id
     * @return array
     */
    public function rejectAllDocumentsOnRemote(string $document_tag, int $user_id): array
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }


        if (phive('Dmapi')->rejectAllFilesFromDocument($user_id, $document_tag)) {
            return $this->success($document_tag);
        }

        return $this->fail("Could not get {$document_tag} from remote brand");
    }

    /**
     * Get the status of a document from a remote brand
     *
     * @param $user_id
     * @param string $tag The tag of the document to get the status for
     * @return array An associative array containing 'status' if successful, or 'error' if failed.
     */
    public function getRemoteDocumentStatus($user_id, string $tag)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $status = phive('Dmapi')->getDocumentStatusFromTag($user, $tag);

        if (!empty($status)) {
            return $this->success($status);
        }

        return $this->fail("Could not get {$tag} status from the remote brand");
    }

    /**
     * Set or delete document status setting on remote brand
     *
     * @param $user_id int
     * @param $document_status_setting string document status setting to be updated
     * @param $value string value of the document status setting
     * @param $original_doc_brand string brand in which the latest document was uploaded
     * @return array
     */
    public function updateDocumentStatusSettingOnRemoteBrand($user_id, $document_status_setting, $value, $original_doc_brand)
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        if (!in_array($document_status_setting, lic('getDocumentsSettingToSetOnlyOnRemote', [], $user))) {
            return $this->fail("Not allowed to sync the setting {$document_status_setting}");
        }

        $local_brand = getLocalBrand();
        if ($local_brand === $original_doc_brand) {
            $user->deleteSetting($document_status_setting);
            return $this->success("Deleted {$document_status_setting}");
        }

        $user->refreshSetting($document_status_setting, $value);
        return $this->success("Status of the {$document_status_setting} changed to {$value}");
    }


    /**
     * Performs a chat block action for a user in a tournament.
     *
     * @param int $user_id The ID of the user to perform the chat block action for.
     * @param int $tournament_id The ID of the tournament event.
     * @param int $days The number of days for which the chat block should be applied (default is 0).
     *                  If not provided or set to 0, it will result in an indefinite chat block.
     *
     * @return array The result of the chat block action.
     *               - If successful, returns a success response with the user ID.
     *               - If the user ID is empty, returns a failure response with the reason 'user-not-found'.
     */
    public function doChatBlock($user_id, $tournament_id, $days = 0)
    {
        if (empty($user_id)) {
            return $this->fail('user-not-found');
        }

        phive('Tournament')->doChatBlock($user_id, $tournament_id, $days);

        return $this->success($user_id);
    }

    /**
     * Set the super block status for a user.
     *
     * @param $user_id The user ID.
     * @param bool $zero_out
     * @param bool $update_status
     * @param bool $log_out
     *
     * @return array The result of the super block action. If successful, returns success with the user ID. If fails, returns fail with the reason.
     */
    public function superBlock($user_id, $zero_out = true, $update_status = true, $log_out = true)
    {
        if (empty($user_id)) {
            return $this->fail('user-not-found');
        }

        $user = cu($user_id);
        $user->superBlock($zero_out, $update_status, $log_out);

        return $this->success($user_id);
    }

    /**
     * Locks a user.
     *
     * @param $user_id
     * @param $days
     *
     * @return array Returns an array representing the result of the lock action:
     *               - If successful, returns a success response with the user ID.
     *               - If the user ID is empty, returns a failure response with the reason 'user-not-found'.
     */
    public function lock($user_id, $days): array
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        rgLimits()->addLimit($user, 'lock', 'na', $days);

        phive('UserHandler')->logAction($user, "Synced remote profile lock", "brand-self-lock", true);

        return $this->success($user_id);
    }

    /**
     * Sets PEP Check Failure received from remote brand.
     *
     * @param $user_id
     * @return array
     */
    public function syncPEPFailureBlockWithRemoteBrand($user_id)
    {
        $user = cu($user_id);

        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $external_kyc_module = phive("DBUserHandler/ExternalKyc");
        $external_kyc_module->logAndBlockPep($user);

        return $this->success($user_id);
    }

    /**
     * Distributed method to set/update SCV export status for the user and to set distributed scv customer id if any
     * at the brand where the request is received.
     *
     * @param $user_id
     * @param $status
     * @param $scv_customer_id
     * @return array
     *         - If successful, returns a success response with the user ID.
     *         - If the user ID is empty, returns a failure response with the reason 'user-not-found'.
     *         - If updating the status fails we send 'scv-export-status-update-failed'.
     *         - If we don't have scv configured and cannot set the link on the account
     *              return 'scv-export-user-link-failed'
     */
    public function updateSCVExportStatus($user_id, $status, $scv_customer_id = null): array
    {
        $user = cu($user_id);
        if (empty($user)) {
            return $this->fail('user-not-found');
        }

        $success = $user->setOrUpdateSCVExportStatus($status);
        if (!$success) {
            return $this->fail('scv-export-status-update-failed');
        }

        $setting = distKey('scv');
        if (!empty($scv_customer_id && empty($setting))) {
            return $this->fail('scv-export-user-link-failed');
        }

        if (!empty($scv_customer_id)) {
            $user->setSetting($setting, $scv_customer_id);
        }

        return $this->success($user_id);
    }

    public function updateRemoteBrandId($user_id, $remote_id): array
    {
        if (empty($user_id)) {
            return $this->fail('Missing params');
        }

        $user = cu($user_id);
        if (empty($user)) {
            phive('Logger')->getLogger('brand_link')
                ->error('Invalid user sent to updateRemoteBrandId');
            return $this->fail('User does not exist');
        }

        $remote_brand = getRemote();
        $user->setSetting(distKey($remote_brand), $remote_id);

        phive('UserHandler')->logAction(
            $user,
            "Account linked with {$remote_brand} id {$remote_id} by updateRemoteBrandId",
            'brand-link'
        );
        return $this->success($user_id);
    }

}

/**
 * @deprecated Old function used to do distributed calls
 * TODO to be removed (remove usage on SAFE/CancelPreviousSession.php)
 *
 * @param string $machine The machine to post to.
 * @param string $class The class to call the method on.
 * @param string $method The method to call on the class.
 * @param array $params The params to pass to the method.
 * @return mixed The result.
 */
function dist($machine, $class, $method, $params = [])
{
    $remote = phive('Distributed')->getLocalBrandId();
    return phive('Distributed')->post($machine, compact('class', 'method', 'params', 'remote'));
}

/**
 *
 * Sends a message to a remote machine. To avoid the fact of having calls to any function in the base code and to decouple
 * responsibility on the rest of the code, we only do calls to functions in the Distributed class
 *
 * Works as a wrapper for the post remote machine. @see Distributed::post()
 *
 * @param string $machine The machine to post to.
 * @param callable $method The method to call on the class.
 * @param array $params The params to pass to the method.
 * @param float|int|null $timeout Timeout for the request
 * @return mixed The result.
 */
function toRemote($machine, $method, $params = [], $timeout = null)
{
    return phive('Distributed')->post($machine, [
        'class' => 'Distributed',
        'method' => $method,
        'params' => $params,
        'remote' => phive('Distributed')->getLocalBrandId()
    ], $timeout);
}

/**
 * For now only one remote accepted we return it here
 *
 * @return mixed
 */
function getRemote(){
    return phive('Distributed')->getSetting('remote_brand');
}

/**
 * Return the brand name of the local brand
 *
 * @return mixed
 */
function getLocalBrand(){
    return phive('Distributed')->getSetting('local_brand');
}

/**
 * Check if the remote brand is SCV or not
 *
 * @return bool
 */
function isRemoteBrandSCV(): bool
{
    $remote_brand = getRemote();
    $scv_brand = phive('Distributed')->getSetting('scv_brand');

    return $remote_brand === $scv_brand;
}

/**
 * Gets the local brand id with its key
 *
 * @param string $name The name of the remote, if null we assume local
 * @return string
 */
function distKey($name = null)
{
    return 'c'. distId($name) .'_id';
}

/**
 * Gets the id of the local brand
 *
 * @param string $name The name of the remote, if null we assume local
 * @return mixed
 */
function distId($name = null)
{
    return empty($name) ? phive('Distributed')->getLocalBrandId() : phive('Distributed')->getBrandIdByName($name);
}

/**
 * Alias around a common load line to make coding quicker.
 *
 * @return Linker
 */
function linker(){
    return phive('Site/Linker');
}
