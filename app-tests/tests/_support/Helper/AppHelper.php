<?php
namespace Helper;

require_once __DIR__ . '/../../../vendor/fzaninotto/faker/src/autoload.php';

use Faker\Factory as FakerFactory;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class AppHelper extends \Codeception\Module
{
    /**
     * Set up before test suite.
     *
     * @param array $settings
     */
    public function _beforeSuite($settings = [])
    {
        // Sets the PHP error reporting level to match that of the LXC containers and Production.
        error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE);
    }

    /**
     * Tear down after test suite.
     */
    public function _afterSuite()
    {
    }

    /**
     * Returns an array of attributes for inserting a user into db.users.
     *
     * @return array
     */
    public static function makeDbUser(): array
    {
        $faker = FakerFactory::create('en_US');

        $data = [
            'email' => $faker->email,
            'mobile' => '99' . $faker->randomNumber(6),
            'country' => 'MT',
            'sex' => $faker->randomElement(['Male', 'Female']),
            'lastname' => $faker->lastName,
            'firstname' => $faker->firstName,
            'address' => $faker->streetAddress,
            'city' => $faker->city,
            'zipcode' => 'VLT-' . $faker->randomElement(['1010', '1011', '1012']),
            'dob' => $faker->date($format = 'Y-m-d', $max = '-20 years'),
            'preferred_lang' => 'en',
            'username' => $faker->unique()->userName,
            'password' => 'Abc123456',
            'bonus_code' => '',
            'register_date' =>  date('Y-m-d H:i:s'),
            'cash_balance' => 10 * $faker->numberBetween(0, $max = 9000),
            'reg_ip' => $faker->ipv4,
            'active' => 1,
            'friend' => '',
            'alias' => '',
            'last_logout' => $faker->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d H:i:s'),
            'cur_ip' => $faker->ipv4,
            'logged_in' => $faker->numberBetween(0, 1),
            'currency' => 'EUR',
            'nid' => '',
        ];
        $data['username'] = $data['email'];
        return $data;
    }

    /**
     * Inserts a user into db.users if it does not already exist.
     *
     * @param $sql_module. The Phive SQL module.
     * @param string $username
     * @param array $extra_values. Key-value array of values to insert for the game. The keys must match the table columns.
     * @return int The user ID.
     * @example dbInsertUser(phive('SQL'), 'devtestse', ['cash_balance' => 100000, 'country' => 'SE', 'currency' => 'SEK', 'preferred_lang' => 'sv']);
     */
    public static function dbInsertUser($sql_module, string $username, array $extra_values = [])
    {
        $params = [
            'username' => 'devtestmt',
            'email' => 'devtest6@devtest.com',
            'mobile' => '44321456987456',
            'country' => 'MT',
            'newsletter' => 0,
            'sex' => 'Male',
            'lastname' => 'Stiller',
            'firstname' => 'Ben',
            'address' => '45 Baker Street',
            'city' => 'Kent',
            'zipcode' => 'HU4578MU',
            'dob' => '1973-03-01',
            'preferred_lang' => 'en',
            'password' => 'c79194b0356573ee78398fc6486b4644',       // 123456
            'bonus_code' => '',
            'register_date' => date("Y-m-d"),
            'cash_balance' => 100000,
            'bust_treshold' => 0,
            'reg_ip' => '217.174.248.203',
            'active' => 1,
            'verified_phone' => 0,
            'friend' => '',
            'alias' => 'Ben',
            'cur_ip' => '10.0.10.1',
            'logged_in' => 1,
            'currency' => 'EUR',
            'affe_id' => 0,
            'nid' => '',
        ];

        $username = preg_replace("/\W/", '', strtolower($username));
        $params['username'] = trim($username);

        if ($extra_values['email'] ?? false) {
            $params['email'] = trim($extra_values['email']);
        } else {
            $params['email'] = $params['username'] . "@devtest.com";
        }

        /**
         * Inserts the user into the database if it does not exist already.
         */
        $extra = array_diff_key($extra_values, array_flip(['id', 'username', 'email']));
        $params = array_merge($params, $extra);

        $sql = "SELECT id FROM users WHERE username = '{$params['username']}'";
        $result = $sql_module->loadAssoc($sql);
        if ($result['id'] ?? 0) {
            return $result['id'];
        }

        $id = $sql_module->insertArray('users', $params);

        if ($id && $sql_module->isSharded('users')) {
            $params['id'] = $id;
            $sql_module->sh($id)->insertArray("users", $params);
        }
    }

    /**
     * Inserts a game into db.micro_games if it does not already exist.
     *
     * @param $sql_module. The Phive SQL module.
     * @param string $game_provider
     * @param string $game_id
     * @param string $game_name
     * @param bool|null $is_desktop. true to insert just the desktop game, false to insert just the mobile game, null to insert both desktop and mobile.
     * @param array $extra_values. Key-value array of values to insert for the game. The keys must match the table columns.
     * @return int The ID of the inserted game. When inserting both desktop and mobile games the ID of the desktop game is returned.
     * @example dbInsertGame(phive('SQL'), 'Swintt', '15608', 'Leokan', null, ['payout_percent' => 0.952, 'num_lines' => 5]);
     */
    public static function dbInsertGame($sql_module, string $game_provider, string $game_id, string $game_name, bool $is_desktop = null, array $extra_values = [])
    {
        $params = [
            'game_name' => 'Dragons and Magic',
            'tag' => 'videoslots',
            'game_id' => 'stakelogic_11360094',
            'languages' => 'de,en,es,fi,in,it,ja,no,sv',
            'ext_game_name' => 'stakelogic_11360094',
            'client_id' => 0,
            'module_id' => '',
            'width' => 1024,
            'height' => 576,
            'popularity' => 0,
            'game_url' => 'dragons-and-magic-stakelogic',
            'meta_descr' => '#game.meta.descr.stakelogic-dragons-and-magic',
            'bkg_pic' => 'dragonsandmagic_bg.jpg',
            'html_title' => '#game.meta.title.stakelogic-dragons-and-magic',
            'jackpot_contrib' => 0,
            'op_fee' => 0.15,
            'stretch_bkg' => 0,
            'played_times' => 0,
            'orion_name' => '',
            'device_type' => 'html5',
            'operator' => 'Stakelogic',
            'network' => 'stakelogic',
            'branded' => 0,
            'active' => 1,
            'blocked_countries' => 'CA AF AS AR BS BH BD BE BA MM CI CU CY CD DA DK EG ES ER FJ FR GU HT VA HK IT IR IQ IL JO KZ KE KW KG LB LR LY LT MP MZ NG KP PK PS PR QA RE RW SA SN SG SO LK SD SY TZ TR UM VI UA AE US VE VN YE ZW RO',
            'retired' => 0,
            'device_type_num' => 1,
            'payout_percent' => 0.945,
            'min_bet' => 20,
            'max_bet' => 8000,
            'ribbon_pic' => '',
            'enabled' => 1,
            'volatility' => 8,
            'num_lines' => 243,
            'max_win' => 180,
            'auto_spin' => 1,
            'included_countries' => '',
            'multi_channel' => 0,
            'mobile_id' => 0,
            'blocked_logged_out' => '',
            'payout_extra_percent' => 0,
        ];

        $params['operator'] = trim($game_provider);
        $params['network'] = preg_replace("/\W|_/", '', strtolower($params['operator']));

        $params['game_name'] = trim($game_name);
        $normalized_game_name = preg_replace('/\s+/', '-', strtolower($params['game_name']));
        $normalized_game_name = preg_replace("/[^A-Za-z0-9\-]/", '', $normalized_game_name);

        $raw_game_id = preg_replace("/^{$params['network']}_/", '', trim($game_id));
        $params['game_id'] = "{$params['network']}_{$raw_game_id}";

        if ($extra_values['ext_game_name'] ?? false) {
            $s = preg_replace("/^{$params['network']}_/", '', trim($extra_values['ext_game_name']));
            $params['ext_game_name'] = "{$params['network']}_{$s}";
        } else {
            $params['ext_game_name'] = $params['game_id'];
        }

        if (isset($extra_values['device_type'])) {
            $params['device_type'] = $extra_values['device_type'];
        } else {
            if (is_null($is_desktop)) {
                $params['device_type'] = 'flash';
            } else {
                $params['device_type'] = ($is_desktop ? 'flash' : 'html5');
            }
        }

        if (isset($extra_values['device_type_num'])) {
            $params['device_type_num'] = $extra_values['device_type_num'];
        } else {
            if (is_null($is_desktop)) {
                $params['device_type_num'] = 0;
            } else {
                $params['device_type_num'] = ($is_desktop ? 0 : 1);
            }
        }
        $is_desktop_device = ($params['device_type_num'] == 0);

        if (isset($extra_values['game_url'])) {
            $params['game_url'] = $extra_values['game_url'];
        } else {
            $params['game_url'] = $is_desktop_device ? "{$normalized_game_name}-{$params['network']}" : '';
        }

        if (isset($extra_values['meta_descr'])) {
            $params['meta_descr'] = $extra_values['meta_descr'];
        } else {
            $params['meta_descr'] = $is_desktop_device ? "#game.meta.descr.{$params['network']}-{$normalized_game_name}" : '';
        }

        if (isset($extra_values['bkg_pic'])) {
            $params['bkg_pic'] = $extra_values['bkg_pic'];
        } else {
            $g = str_replace('-', '', $normalized_game_name);
            $params['bkg_pic'] = $is_desktop_device ? "{$g}_bg.jpg" : "html-{$g}.jpg";
        }

        if (isset($extra_values['html_title'])) {
            $params['html_title'] = $extra_values['html_title'];
        } else {
            $params['html_title'] = $is_desktop_device ? "#game.meta.title.{$params['network']}-{$normalized_game_name}" : '';
        }

        /**
         * Inserts the game into the database if it does not exist already.
         */
        $extra = array_diff_key($extra_values, array_flip(['id', 'operator', 'network', 'game_name', 'game_id', 'ext_game_name', 'device_type', 'device_type_num', 'game_url', 'meta_descr', 'bkg_pic', 'html_title']));
        $params = array_merge($params, $extra);

        $sql = "SELECT id FROM micro_games WHERE ext_game_name = '{$params['ext_game_name']}' AND device_type_num = {$params['device_type_num']}";
        $result = $sql_module->loadAssoc($sql);

        if (!($result['id'] ?? false)) {
            $sql_module->shs()->insertOrUpdate("micro_games", $params, ['active' => 1]);
        }

        $result = $sql_module->loadAssoc($sql);
        $id = $result['id'] ?? 0;

        /**
         * Inserts the mobile game if it does not exist already.
         */
        if ($id && is_null($is_desktop) && $is_desktop_device) {
            $mobile_id = self::dbInsertGame($sql_module, $game_provider, $game_id, $game_name, false, $extra_values);

            if ($mobile_id) {
                $sql = "UPDATE micro_games SET mobile_id = {$mobile_id} WHERE id = {$id}";
                $sql_module->shs()->query($sql);
            }
        }

        return $id;
    }

    /**
     * Returns a single row for the SQL SELECT statement.
     *
     * @param string $sql The escaped SQL query.
     * @return array|null The database row or null if not found.
     */
    public static function dbSelect(string $sql)
    {
        $db_row = phive('SQL')->loadAssoc($sql);
        return is_array($db_row) ? $db_row : null;
    }

    /**
     * @param string $user_identifier
     * @return array. The 1st element is db.users.id. The 2nd element is db.tournament_entries.id or null.
     */
    public static function getUserAndTournamentId(string $user_identifier): array
    {
        $arr = explode('e', $user_identifier);
        return [(int)$arr[0], ($arr[1] ?? null) ? (int)$arr[1] : null];
    }

    /**
     * Returns the user or tournament cash_balance in cents.
     * @param $sql_module
     * @param string $user_identifier
     * @return int|null Balance in cents.
     */
    public static function getUserBalance($sql_module, string $user_identifier)
    {
        list($user_id, $tournament_entry_id) = self::getUserAndTournamentId($user_identifier);

        if (empty($tournament_entry_id)) {
            $r = self::dbSelect("SELECT cash_balance FROM users WHERE id = {$user_id}");
            $balance = ($r['cash_balance'] ?? null);
        } else {
            $r = self::dbSelect("SELECT cash_balance FROM tournament_entries WHERE id = {$tournament_entry_id} AND user_id = {$user_id}");
            $balance = ($r['cash_balance'] ?? null);
        }
        return $balance;
    }
}
