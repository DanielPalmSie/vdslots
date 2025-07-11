<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 02/03/16
 * Time: 09:35
 */

namespace App\Helpers;

use App\Models\BankCountry;
use App\Models\Config;
use App\Models\Currency;
use App\Models\Game;
use App\Models\Segment;
use App\Models\Trigger;
use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Collection;
use Silex\Application;

class DataFormatHelper
{
    public static function nf($euro_cents, $divide_by = 100, $dec = 2)
    {
        if (empty($euro_cents)) {
            return number_format(0, $dec);
        } elseif (!is_numeric($euro_cents) || is_nan($euro_cents) || is_infinite($euro_cents)) {
            return $euro_cents;
        } else {
            return number_format($euro_cents / $divide_by, $dec);
        }
    }

    public static function pf($cents, $decimals = 2, $symbol = '%')
    {
        if (empty($cents) || is_nan($cents) || is_infinite($cents)) {
            return "0$symbol";
        } else {
            return number_format($cents * 100, $decimals) . $symbol;
        }
    }

    /**
     * @param string $str
     * @param Currency|array $currency
     * @return mixed
     */
    public static function getBonusName($str, $currency)
    {
        if (is_array($currency)) {
            $mod = $currency['mod'];
            $symbol = $currency['symbol'];
        } else {
            $mod = $currency->mod;
            $symbol = $currency->symbol;
        }

        if (strpos($str, '{') === false) {
            return $str;
        }
        $str = preg_replace_callback('|\{\{([^\{]+)\}\}|', function ($m) use ($mod) {
            $arr = phive()->trimArr(explode(":", $m[1]));
            if ($arr[0] == 'modm') {
                $amount = $arr[1] * $mod;
                return $amount > 999 ? number_format($amount) : $amount;
            } else if ($arr[0] == 'modd') {
                $amount = $arr[1] / $mod;
                return $amount > 999 ? number_format($amount) : $amount;
            } else {
                return $m[0];
            }
        }, $str);
        return str_replace('{{csym}}', $symbol, $str);
    }

    public static function getXpThreshold($lvl)
    {
        $lvl = (int)$lvl;
        if (empty($lvl) || $lvl < 0) {
            return 0;
        } elseif ($lvl === 1) {
            return 100;
        } else {
            $multi = $lvl <= 20 ? 10 : 100;
            return $lvl * 100 * $multi;
        }
    }

    public static function getCommentTagName($tag)
    {
        $tag_list = [
            'bonus_entries' => 'Bonuses',
            'discussion' => 'Discussion',
            'complaint' => 'Complaint',
            'limits' => 'Limits',
            'phone_contact' => 'Phone contact',
            'trophy_awards_ownership' => 'Trophies',
            'vip' => 'VIP',
            'communication' => 'Communication',
            'sar' => 'SAR',
            'amlfraud' => 'Fraud & AML',
            'mlro' => 'MLRO',
            'rg-risk-group' => 'Rg Risk Score',
            'aml-risk-group' => 'AML Risk Score',
            'manual-flags' => 'Manual Flags',
            'sportsbook' => 'Sportsbook',
            'automatic-flags' => 'Automatic flags',
            'rg-evaluation' => 'RG Evaluation',
            'rg-action' => 'RG Action',
            'account-closure' => 'Account Closure',
        ];

        return isset($tag_list[$tag]) ? $tag_list[$tag] : 'Uncategorized';
    }

    public static function getRewardStatus($status)
    {
        $status_list = [
            1 => 'Active',
            2 => 'Used',
            3 => 'Expired'
        ];

        return isset($status_list[$status]) ? $status_list[$status] : 'Uncategorized';
    }

    public static function getPopularForums($code = null)
    {
        $forums = [
            'cl' => 'Casinolistings',
            'ag' => 'Askgamblers',
            'cm' => 'Casinomeister',
            'agd' => 'Affiliate guard dog',
            'gc' => 'Gamblingcity',
            'fs' => 'Fruity Slots',
            'ba' => 'Backinamo',
        ];

        if (is_null($code)) {
            return $forums;
        } else {
            return isset($forums[$code]) ? $forums[$code] : $code;
        }
    }

    public static function getFollowUpOptions($option = null)
    {
        $options = [
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'halfyearly' => 'Half yearly',
            'yearly' => 'Yearly'
        ];

        if (is_null($option)) {
            return $options;
        } else {
            return isset($options[$option]) ? $options[$option] : $option;
        }
    }

    public static function getFollowUpGroups($option = null, $type = null)
    {
        $options = [
            'green' => 'Green',
            'yellow' => 'Yellow',
            'orange' => 'Orange',
            'red' => 'Red',
            'purple' => 'Purple',
            'black' => 'Black'
        ];

        $specific_options['aml'] = ['blue' => 'Blue'];

        if (!empty($type) && isset($specific_options[$type])) {
            $options =  array_merge($options, $specific_options[$type]);
        }

        if (is_null($option)) {
            return $options;
        } else {
            return isset($options[$option]) ? $options[$option] : $option;
        }
    }

    public static function getManualFlags($option = null)
    {
        $options = [
            'deposit_block' => 'Disallowed to deposit',
            'play_block' => 'Disallowed to play',
            'manual-fraud-flag' => 'Manual fraud flag [Active]',
            'too_many_rollbacks-fraud-flag' => 'Too many rollbacks fraud flag [Active]',
            'total-withdrawal-amount-limit-reached-fraud-flag' => 'Total withdrawal amount reached fraud flag [Active]',
            'suspicious-email-fraud-flag' => 'Suspicious email fraud flag [Active]',
            'negative-balance-since-deposit' => 'Negative balance since deposit fraud flag [Active]',
            'sar-flag' => 'Manual SAR flag [Active]',
            'amlmonitor-flag' => 'Manual AML monitor flag [ACTIVE]',
            'agemonitor-flag' => 'Manual AGE verification monitor flag [ACTIVE]'
        ];

        if (is_null($option)) {
            return $options;
        } else {
            return isset($options[$option]) ? $options[$option] : $option;
        }
    }

    public static function getMonitoringLogOptions($option = null)
    {
        $options = [
            'rg-monitoring' => 'Responsible Gaming Monitoring',
            'aml-monitoring' => 'AML Monitoring',
//            'fraud-monitoring' => 'Fraud Monitoring',
            //'monitoring-check' => 'Uncategorized'
        ];

        if (is_null($option)) {
            return $options;
        } else {
            return isset($options[$option]) ? $options[$option] : $option;
        }
    }

    /**
     * Return the action text for a specific setting.
     *
     * @param null $setting
     * @return array|mixed|null
     */
    public static function getRgMonitoringActions($setting = null)
    {
        $actions = [
            'ask_gamble_too_much' => ['label' => 'Ask gamble too much', 'message' => 'Do you gamble too much?'],
            'ask_bet_too_high' => ['label' => 'Ask bet too high', 'message' => 'Do you bet too high?'],
            'ask_play_too_long' => ['label' => 'Ask play too long', 'message' => 'Do you play too long?'],
        ];

        if (is_null($setting)) {
            return $actions;
        } else {
            return isset($actions[$setting]) ? $actions[$setting] : $setting;
        }
    }

    public static function getCurrencyList()
    {
        return Currency::select('symbol', 'code')->get();
    }

    public static function getCityList()
    {
        return [
            ["name"=>"Trosa"]
        ];
    }

    public static function getExternalGameNameList()
    {
        return Game::select('ext_game_name')->distinct()->get()->toArray();
    }

    public static function getGameIdList()
    {
        return Game::select('game_id')->distinct()->get()->toArray();
    }

    public static function getCountryList($common_first = true)
    {
        $countries = BankCountry::select('iso', 'printable_name')->get();

        if (!$common_first) {
            return $countries->toArray();

        } else {
            $p_list = ['SE' => 11, 'FI' => 10, 'DE' => 9, 'FR' => 8, 'NL' => 7, 'NO' => 6, 'PL' => 5, 'IT' => 4, 'AU' => 3, 'CA' => 2, 'GB' => 1];
            $sorted = $countries->sortByDesc(function ($country, $key) use ($p_list) {
                return $p_list[$country['iso']];
            });
            return $sorted->toArray();
        }
    }

    public function getBlockTypes(): array
    {
      return [
          'play_block' => 'Play Block',
          'withdrawal_block' => 'Withdraw Block',
          'deposit_block' => 'Deposit Block',
          'super-blocked' => 'Super Blocked',
          'restrict' => 'Restrict',
          'mp-chat-block' => 'Chat Blocked',
          'excluded-date' => 'Self Exclusion',
          'cdd_check' => 'Cdd Check',
           ];
    }

    public static function getProvinceListByCountry(string $country = '', bool $include_all = false)
    {
        if (empty($country)) {
            return [];
        }

        $provinces = DB::table('license_config')
            ->select('config_value')
            ->where('config_tag', 'provinces')
            ->where('license', $country)
            ->get()
            ->map(function($result) {
                $result = json_decode($result->config_value, true);
                return [
                    'iso' => $result['iso_code'],
                    'printable_name' => $result['province']
                ];
            })
            ->sort()
            ->toArray();

        if ($include_all) {
            array_unshift($provinces, [
                'iso' => 'all',
                'printable_name' => t('all')
            ]);
        }

        return $provinces;
    }

    /**
     *
     * @param null $id
     * @return array|mixed|string
     */
    public static function getLimitsNames($id = null, $include_sportsbook = false)
    {
        if($include_sportsbook === true) {
            ['loss' => $sportsbook_loss, 'wager' => $sportsbook_wager] = phive()->getSetting('products')['sportsbook']['rg'];
        }

        $limits = [
            'deposit' => 'Deposit limit',
            'loss' => $include_sportsbook ? 'Casino Loss limit' : 'Loss limit',
            $sportsbook_loss => $include_sportsbook ? 'Sportsbook Loss limit' : null,
            'wager' => $include_sportsbook ? 'Casino Wager limit' : 'Wager limit',
            $sportsbook_wager => $include_sportsbook ? 'Sportsbook Wager limit' : null,
            'betmax' => 'Max bet limit',
            'timeout' => 'Timeout limit',
            'login' => 'Login limit',
            'customer_net_deposit' => 'Net Deposit limit',
            'net_deposit' => 'Casino Net Deposit Threshold',
            'balance' => 'Maximum allowed balance',
//           'rc' => 'Reality check limit',
        ];

        $limits = array_filter($limits);

        if (empty($id)) {
            return $limits;
        }

        return $limits[$id] ?? 'Limit not defined';
    }

    /**
     * @return array
     */
    public static function getLimitsTimeSpanList() {
        return [
            'day' => [
                'title' => 'Daily',
                'description' => '(24 hours)'
            ],
            'week' => [
                'title' => 'Weekly',
                'description' => '(7 days)'
            ],
            'month' => [
                'title' => 'Monthly',
                'description' => '(30 days)'
            ],
        ];
    }

    static function getProvinces($country)
    {
        return phive('Licensed')->doLicense($country, 'getProvinces', []);
    }

    public static function getAMLFlags(): array {
        $fraudTypes = phive('Cashier')->fraud->getFraudTypes();

        $flagKeys = array_map(fn($flag) => $flag . "-fraud-line", $fraudTypes);

        $modifiedStrings = array_map(function($type) {
            $string = str_replace(['_', '-'], ' ', $type);
            return ucfirst($string);
        }, $fraudTypes);

        return array_combine($flagKeys, $modifiedStrings);
    }

    public static function getStuckStatuses(): array {
        $cahier = phive('Cashier');

        $statuses = [
            $cahier::NON_STUCK => 'Non-Stuck',
            $cahier::STUCK_NORMAL => 'Stuck',
            $cahier::STUCK_OVER => 'Over',
            $cahier::STUCK_UNKNOWN => 'Unknown'
        ];

        return $statuses;
    }


    /**
     * @param $country
     * @return string
     */
    public static function getCurrencyFromCountry($country)
    {
        $country = strtolower($country);
        $currency = Currency::get()->first(function($el) use ($country) {
            return strpos($el->countries, $country) !== false;
        });

        return empty($currency) ? 'EUR' : $currency->code;
    }


    public static function getSelect2FormattedData($input, $columns_map) {
        return array_values(array_values(array_map(function ($el) use ($columns_map) {
            $aux = [];
            foreach ($columns_map as $key => $column) {
                $aux[$key] = $el[$column];
            }
            return $aux;
        }, $input)));
    }


    /**
     * @param $limit
     * @return mixed
     */
    public static function getOldLimitName($limit) {
        return [
            'deposit' => 'dep-lim',
            'loss' => 'lgaloss-lim',
            'wager' => 'lgawager-lim',
            'betmax' => 'betmax-lim',
            'timeout' => 'lgatime-lim',
            'balance' => 'balance-lim'
        ][$limit];
    }


    /**
     * @param        $countries
     * @param string $delimiter
     *
     * @return array
     */
    public static function formatCountries($countries, $delimiter = " ")
    {
        $countries = is_string($countries)
            ? explode($delimiter, $countries)
            : $countries;

        return BankCountry::query()
            ->select('iso', 'printable_name')
            ->whereIn('iso', $countries)
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->iso => $row->printable_name];
            })
            ->pipe(function ($rows) use ($countries) {
                // if there's any missed country,
                // just add it's iso in result
                foreach ($countries as $country) {
                    if (!$rows[$country]) {
                        $rows[$country] = $country;
                    }
                }
                return $rows;
            })
            ->all();
    }

    public static function formatProvince($licenses, $delimiter = " ")
    {
        $licenses = is_string($licenses)
            ? explode($delimiter, $licenses)
            : $licenses;

        $countries = [];
        $provinces = [];
        foreach ($licenses as $license) {
            $parts = explode('-', $license);
            $countries[] = $parts[0];
            $provinces[] = $parts[1];
        }

        return DB::table('license_config')
            ->select('config_value', 'license')
            ->where('config_tag', 'provinces')
            ->whereIn('license', $countries)
            ->get()
            ->map(function($result) {
                $data = json_decode($result->config_value, true);
                return [
                    'iso' => $data['iso_code'],
                    'name' => $data['province'],
                    'value' => strtoupper($result->license) . '-' . ($data['iso_code'])
                ];
            })
            ->filter(function($result) use ($provinces) {
                return in_array($result['iso'], $provinces);
            })
            ->toArray();
    }


    /**
     *  This will explode string then trim each element
     *  If keep_empty = false keep non empty elements
     * @param      $str
     * @param      $delimiter
     * @param bool $keep_empty
     *
     * @return Collection
     */
    public static function getListFromString(
        $str,
        $delimiter,
        $keep_empty = false
    ) {
        return collect(explode($delimiter, $str))
            ->map(function ($item) {
                return trim($item);
            })
            ->filter(function ($item) use ($keep_empty) {
                return $keep_empty or $item != '';
            });
    }


    public static function htmlEntityToObject($entity) {
        return json_decode(html_entity_decode($entity));
    }


    public static function getSegments($id = null)
    {
        $segments = [
            1 => 'Full customer',
            2 => 'Highroller',
            3 => 'VIP',
            4 => 'Diamond'
        ];

        if (is_null($id)) {
            return $segments;
        } else {
            return isset($segments[$id]) ? $segments[$id] : 'Segment not defined';
        }
    }

    public static function getWinType($type)
    {
        $map = [
            2 => 'Normal',
            4 => 'Jackpot',
            7 => 'Refund'
        ];

        return isset($map[$type]) ? $map[(int)$type] : 'Type not defined';
    }

    public static function getCashTransactionsTypeName($type = null)
    {
        //In comments are transactions that are actually doing a debit / credit of users.cash_balance
        //Bets -
        //Wins +
        $type_list = [
            1 => 'Bet',
            2 => 'Win',
            3 => 'Deposit', // +
            4 => 'Bonus reward', // + payout of successfully turned over cash balance bonus, type casino and the 11 welcome freespin
            5 => 'Affiliate payout', // +
            6 => 'Voucher payout',
            7 => 'Bet refund', // + / -
            8 => 'Withdrawal', // -
            9 => 'Chargeback', // -
            12 => 'Jackpot win',
            13 => 'Normal refund', // + / -
            14 => 'Activated bonus', // + in case of casinowagers, type casinowager and more, but not all // we need more type for this, depending on what it is
            15 => 'Failed bonus', //
            20 => 'Sub aff payout', // +
            28 => 'Old VIP deduction',
            29 => 'Buddy transfer', // + / -
            31 => 'Casino loyalty', // +
            32 => 'Casino race', // +
            33 => 'SMS fee',
            34 => 'Casino tournament buy in', // -
            35 => 'Casino tournament pot cost',
            36 => 'Casino tournament skill point award',
            37 => 'Casino tournament buy in with skill points',
            38 => 'Tournament cash win', //31, 32, 51, 41 +
            39 => 'Tournament skill points win',
            40 => 'Tournament skill points top 3 bonus win',
            41 => 'Guaranteed prize diff',
            42 => 'Test cash for test account', // +
            43 => 'Inactivity fee', // -
            44 => 'MG tournament registration fee',
            45 => 'MG tournament rebuy/addon',
            46 => 'MG tournament payout',
            47 => 'MG tournament cancellation',
            48 => 'Casino tournament fixed cash balance pay back',
            49 => 'Casino tournament pot cost with skill points',
            50 => 'Withdrawal deduction', // - ?
            51 => 'FRB Cost',
            52 => 'Casino tournament house fee', // -
            53 => 'Failed casino bonus winnings', // -
            54 => 'Casino tournament rebuy', // -
            55 => 'Casino tournament freeroll cost',
            56 => 'Casino tournament house fee skill point cost',
            57 => 'Casino tournament with reward prizes',
            58 => 'Casino tournament pot cost paid by the house',
            59 => 'Casino tournament recovered freeroll money',
            60 => 'Zeroing out of balance due to too high win rollback amount', // -
            61 => 'Cancel / Unreg of casino tournament buy in', // +
            62 => 'Cancel / Unreg of casino tournament pot cost',
            63 => 'Cancel / Unreg of casino tournament house fee', // +
            64 => 'Cancel / Unreg of casino tournament rebuy', // +
            65 => 'Cancel of casino tournament, payback of win amount', // -
            /* New transaction types added regarding the rewards refactor split done */
            66 => 'Cash balance bonus credit',      // done -> liability increasxe
            67 => 'Cash balance bonus debit',       // done -> liability decrease
            68 => 'Wager bonus credit',             // done
            69 => 'Wager bonus payout / shift',     // done -> liability increase
            70 => 'Wager bonus debit',              // done
            71 => 'FRB bonus shift, winnings start to turn over',               // done
            72 => 'FRB bonus debit',                // done -> liability decrease
            73 => 'Tournament ticket credit',       // not done
            74 => 'Tournament ticket shift',        // done -> reward
            75 => 'Tournament ticket debit',        // done -> failed reward
            76 => 'Trophy top up credit',           //not done -> liability increase
            77 => 'Trophy top up shift',            //done -> reward, should be treated as liability increase for now
            78 => 'Trophy top up debit',            //not done -> liability decrease
            79 => 'Trophy deposit top up credit',   //not done -> liability increase
            80 => 'Trophy deposit top up shift',    //done -> reward, should be treated as liability increase for now
            81 => 'Trophy deposit top up debit',    //not done -> liability decrease
            82 => 'Zeroing out of balance: difference between rolled back win amount and balance', //done -> reward
            83 => 'Tournament win after prize calculation', //Do nothing, nothing changes the win would not have been credited for increased liability anyway
            84 => 'Bonus Top Up Cash',                      //done -> liability increase
            85 => 'Tournament joker prize',         //not live yet, bundled as reward -> liability increase
            86 => 'Tournament bounty prize',         //not live yet, bundled as reward -> liability increase
            87 => 'Chargeback not enough money diff', //no liability
            88 => 'Failed race / loyalty du to super block',
            89 => 'Temporary account closure, forfeited winnings', //liability decrease
            90 => 'Reactivated failed casino bonus winnings', // liability increase
            91 => 'Liability adjustment',
            92 => 'Chargeback settlement', // A debit to remove money to settle a chargeback -> liability decrease
            93 => 'Ignorable liability adjustment',
            94 => 'WoJ Mini Jackpot',
            95 => 'WoJ Major Jackpot',
            96 => 'WoJ Mega Jackpot',
            97 => 'BoS buyin with prize ticket',
            98 => 'Voided bets', //To be considered in the stats
            99 => 'Voided wins', //To be considered in the stats
            100 => 'Transfer to Booster Vault',
            101 => 'Transfer from Booster Vault',
            103 => 'Undone withdrawal',
            104 => 'Turnover tax on wager',
            105 => 'Turnover tax on wager refund',
            106 => 'Sports agent fee', // debit money from user
            107 => 'Sports agent fee refund' // credit money to user on cancellation
        ];

        if (empty($type)) {
            return $type_list;
        } else {
            return isset($type_list[$type]) ? $type_list[$type] : 'Not defined';
        }
    }

    public static function transformScheme($cnum)
    {
        $fchar = $cnum[0];

        if ($fchar == '4') {
            return 'visa';
        }

        if ($fchar == '5') {
            return 'mc';
        }

        $twochar = substr($cnum, 0, 2);

        $twomap = [
            '37' => 'amex',
            '34' => 'amex',
            '36' => 'diners',
            '38' => 'diners'
        ];

        if (!empty($twomap[$twochar])) {
            return $twomap[$twochar];
        }

        $threechar = (int)substr($cnum, 0, 3);

        if ($threechar >= 300 && $threechar <= 305) {
            return 'diners';
        }

        $fourchar = (int)substr($cnum, 0, 4);

        $fourmap = [
            '6011' => 'diners',
            '2014' => 'enroute',
            '2149' => 'enroute',
            '2131' => 'jcb',
            '1800' => 'jcb'
        ];

        if (!empty($fourmap[$fourchar])) {
            return $fourmap[$fourchar];
        }

        if ($fchar == '3') {
            return 'jcb';
        }

        return '';
    }

    public static function getCardType($cnum, $return_hash = false)
    {
        if (mb_strlen($cnum) != 19) {
            return $cnum;
        }

        if ($return_hash) {
            return self::transformScheme($cnum) .' '. $cnum;
        } else {
            return self::transformScheme($cnum);
        }
    }

    public static function getCardDetails(array $card, string $subSupplier = '', bool $showCardNumber = false): string
    {
        $cardDetails = [];

        if ($showCardNumber && $card['card_num']) {
            $cardDetails[] = $card['card_num'];
        }

        if ($card['card_class']) {
            $cardDetails[] = "Type: {$card['card_class']}";
        }

        if ($card['brand_name']) {
            $cardDetails[] = "Brand: {$card['brand_name']}";
        }

        if ($subSupplier !== 'applepay' && $card['issuer_name']) {
            $cardDetails[] = "Issuer: {$card['issuer_name']}";
        }

        return implode(' | ', $cardDetails);
    }

    public static function getLanguages() {
        return phive('Localizer')->getLangSelect();
    }

    public static function getSex() {
        return [
            "male" => "Male",
            "female" => "Female"
        ];
    }

    public static function getDepositMethods() {
        return \App\Repositories\TransactionsRepository::getDepositMethods();
    }

    public static function getPeriodLimitDuration() {
        return [
            '-1'=>'None',
            'day' => 'Day',
            'month' => 'Month',
            'week' => 'Week'
        ];
    }
    public static function getLimits() {
        return [
            'dep-lim'       => 'Deposit limit',
            'lgaloss-lim'   => 'Loss limit',
            'lgawager-lim'  => 'Wager limit',
            'betmax-lim'    => 'Max bet limit',
            'lgatime-lim'   => 'Time-out Limit',
        ];
    }
    public static function getHasLimitsOptions() {
        return [
            '1' => 'Yes',
            '0' => 'No'
        ];
    }
    public static function hasBonusOptions() {
        return [
            '1' => 'Yes',
            '0' => 'No'
        ];
    }
    public static function languageOptions() {
        return \App\Repositories\UserRepository::getLanguages();
    }
    public static function booleanOptions() {
        return [
            '1' => 'Yes',
            '0' => 'No'
        ];
    }
    public static function simpleBooleanOptions() {
        return [
            '1' => 'Yes',
            '0' => 'No'
        ];
    }
    public static function gameTypeOptions() {
        return [
            "blackjack"             => "Blackjack",
            "live"                  => "Live",
            "other"                 => "Other",
            "rival-special-win"     => "Rival Special Win",
            "roulette"              => "Roulette",
            "scratch-cards"         => "Scratch Cards",
            "slots"                 => "Slots",
            "slots_jackpot"         => "Slots Jackpot",
            "system"                => "System",
            "table"                 => "Table",
            "videoslots"            => "Videoslots",
            "videoslots_jackpot"    => "Videoslots jackpot",
            "videoslots_jackpotbsg" => "Videoslots jackpot bsg",
            "sng"               => "Battle of slots - Sit & Go",
            "mtt"               => "Battle of slots - Scheduled Battles",
        ];
    }

    public static function mainGameCategoryOptions() {
        return [
            "bos"   => "Battle of slots",
            "other" => "All games except Battle of slots"
        ];
    }

    public static function campaignTypeOptions() {
        return [
            '1' => 'Sms',
            '2' => 'Email',
            '3' => 'Notification'
        ];
    }

    public static function deviceTypeOptions() {
        return [
            '0' => 'Desktop',
            '1' => 'Mobile'
        ];
    }

    public static function preferedDeviceTypeOptions() {
        return [
            //  m > 0 and d > m
            '0' => 'Mainly Desktop',
            //  d > 0 and m > d
            '1' => 'Mainly Mobile',
            //  m = 0 and d > 0
            '2' => 'Only   Desktop',
            //  d = 0 and m > 0
            '3' => 'Only   Mobile',
            //  d = 0 and m = 0
            '4' => 'None'

        ];
    }

    public static function lastSessionOptions() {
        return [
            0 => 'Lost all',
            1 => 'Lost big',
            2 => 'Lost',
            3 => 'Break even',
            4 => 'Win',
            5 => 'Win Big',
            6 => 'No session found'
        ];
    }

    public static function activeInactiveOptions() {
        return [
            '1' => 'Active',
            '0' => 'Inactive'
        ];
    }

    /**
     * @return array
     */
    public static function segmentOptions() {
        return Segment::with('groups')->get()->mapWithKeys(function($item) {
            return [$item->id => [
                'title' => $item->name,
                'options' => $item->groups->map(function($group) {
                    return [$group->id => $group->name];
                })
            ]];
        });
    }

    /**
     * convert php array into sql array
     * @param $arr
     * @param bool $strings
     * @return string
     */
    public static function arrayToSql($arr, $strings = true)
    {
        return $strings ?
            "('" . implode("','", $arr) . "')"
            : "(" . implode(",", $arr) . ")";
    }

    /**
     * @param $hours
     * @return float|int
     */
    public static function convertHoursToSeconds($hours)
    {
        $minutes = $hours * 60;
        $seconds = $minutes * 60;

        return $seconds;
    }

    /**
     * Returns list of triggers by jurisdiction or list of triggers for all jurisdictions
     *
     * @param string|null $jurisdiction
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     * @throws \Exception
     */
    public static function manualFlags(?string $jurisdiction = null)
    {
        if ($jurisdiction) {
            $triggers = Config::getValue(
                "$jurisdiction-manual-flags",
                "jurisdictions",
                [],
                false,
                true,
                true
            );
        } else {
            $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
            $jurisdictions_triggers = [];
            foreach ($country_jurisdiction_map as $jur) {
                $jurisdictions_triggers[] = Config::getValue(
                    "$jur-manual-flags",
                    "jurisdictions",
                    [],
                    false,
                    true,
                    true
                );
            }
            $triggers = array_unique(array_merge([], ...$jurisdictions_triggers));
        }


        return Trigger::query()
            ->whereIn('name', $triggers ?? [])
            ->get();
    }

    public static function getTranferBrands(){
        return [
          'kungaslottet' => 'Kungaslottet'
        ];
    }

    public static function getNodesList()
    {
        return DB::getNodesList();
    }

    public static function getInterventionCauses()
    {
        return [
            'fraud' => 'Verification',
            'social' => 'Social behavior',
            'problem-gambling' => 'Problem gambling',
            'other' => 'Other'
        ];
    }

    public static function convertToEuro($user_currency, $amount, Application $app, $use_mod = false)
    {
        $currency = Currency::where('code', $user_currency)->first();

        if (empty($currency)) {
            if (empty($app)) {
                error_log("$user_currency not found in db.");
            } else {
                $app['monolog']->addError('Dataformathelper:convertToEuro', ["$user_currency not found in db."]);
            }
        }

        $converter = $use_mod ? $currency->mod : $currency->multiplier;

        return $amount / 100 / $converter;
    }
}
