<?php

require_once __DIR__ . '/../../api/PhModule.php';

class Sportsbook extends PhModule
{
    const BET_TYPES = [
        'single' => 'single',
        'multi' => 'multi',
        'system' => 'system',
    ];

    const STATS = [
        'staked' => 'SUM(amount)',
        'won' => 'SUM(amount)',
        'lost' => 'SUM(amount)',
        'void' => 'void',
        'count' => 'COUNT(*)',
    ];

    const SPECIFIER_MODIFIERS = [
        '-' => 'formatNegativeSpecifier',
        '+' => 'formatNeutralSpecifier',
        '!' => 'translateOrdinalSpecifier'
    ];

    const SPECIFIERS_PATTERN = '/{([^}]+)}/';

    const HANDICAPPED_SPECIFIER_KEY = 'hcp';

    const POOL_BET_PRODUCT = 'P';

    const SPORTSBOOK_PRODUCT = 'S';

    const PRODUCTS = [
        self::SPORTSBOOK_PRODUCT => [
            'op_fee' => 0.15
        ],
        self::POOL_BET_PRODUCT => [
            'op_fee' => 0,
            'gross_calc' => 0.12
        ]
    ];

    const BANK_FEE = 0.09;

    /** @var string $start */
    public $start;
    /** @var string $end */
    public $end;
    /** @var int $user_id */
    public $user_id;

    /**
     * Setup basic filter data
     *
     * @param string $start
     * @param string $end
     * @param int $user_id
     * @return Sportsbook
     */
    public function init($start, $end, $user_id)
    {
        $this->start = $start;
        $this->end = $end;
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    private function formatHandicappedValue($value): string
    {
        return (is_numeric($value) && $value >= 0) ? "+{$value}" : $value;
    }

    /**
     * returns the beta testing flag from the config file
     * @return bool
     **/
    public function isBetaTest(): bool
    {
        return $this->getSetting('beta_testing');
    }

    /**
     * Checks if the user has permission to work with sportsbook
     * @param string $tag
     * @param User|null $user
     * @return bool
     */
    public function hasSportsbookPermission(string $tag = "", User $user = null):bool {
        $test_points = ['sportsbook', 'sportsbook-prematch', 'sportsbook-live'];

        if (empty($tag)) {
            foreach ($test_points as $link_alias) {
                if (p(phive('Menuer')->getMenuerPrefix() . $link_alias, $user)) {
                    return true;
                }
            }
        } else {
            return p($tag, $user);
        }

        return false;
    }

    /**
     * Format negative specifiers, from (-goals) with input value=2 to -2, or (-hcp) with input value=-2 to +2
     *
     * @param string $key
     * @param $value
     * @return float|int|string
     */
    private function formatNegativeSpecifier(string $key, $value)
    {
        return ($key === static::HANDICAPPED_SPECIFIER_KEY)
            ? $this->formatHandicappedValue($value * -1)
            : $value * -1;
    }

    /**
     * Format neutral specifiers, from (+goals) with input value=2 to 2, or (+hcp) with input value=2 to +2
     *
     * @param string $key
     * @param $value
     * @return string
     */
    private function formatNeutralSpecifier(string $key, $value)
    {
        return $key === static::HANDICAPPED_SPECIFIER_KEY
            ? $this->formatHandicappedValue($value)
            : $value;
    }

    /**
     * Format the ordinal specifiers, from (!goalnr) with input value=2 to 2nd
     *
     * @param string $key
     * @param $value
     * @return mixed
     */
    private function translateOrdinalSpecifier(string $key, $value)
    {
        return t("sb.ordinal.{$value}.name");
    }

    /**
     * Generate query for both list of items and aggregates
     *
     * @param string $limit
     * @param string $sport
     * @param string $type
     * @param string $columns
     * @param bool $order
     * @return string
     */
    private function generateQuery($limit = '', $sport = '', $type = '', $columns = '*', $order = true, $bet_type = 'bet')
    {
        $sportsbook_product = self::SPORTSBOOK_PRODUCT;

        if (empty($type) || empty(self::BET_TYPES[$type])) {
            $type = '';
        } else {
            $type = " AND ticket_type = '$type'";
        }
        if ($columns === 'void') {
            $columns = "SUM(amount)";
            $void = "AND (result = 0 AND win_amount > 0)";
        }

        if (empty($sport) || empty($this->getSports($sport))) {
            $sport = '';
        } else {
            $sport = " AND id IN (
                SELECT sport_transaction_id
                FROM sport_transaction_details
                WHERE sport = '$sport'
                    AND user_id = {$this->user_id}
            )";
        }

        $order = $order ? "ORDER BY created_at DESC" : "";

        return "
            SELECT $columns
            FROM sport_transactions
            WHERE created_at BETWEEN '{$this->start}' AND '{$this->end}'
            AND user_id = {$this->user_id}
            $void $type $sport
            AND product ='$sportsbook_product'
            AND bet_type = '$bet_type'
            AND ignore_sportsbook_history IS NULL
            $order
            $limit
        ";
    }

    /**
     * Generate query for both list of items and aggregates
     *
     * @param string $limit
     * @param string $columns
     * @param bool $order
     * @param string $bet_type
     * @return string
     */
    private function generateQueryPoolx($limit = '', $columns = '*', $order = true, $bet_type = 'bet')
    {
        $poolbet_product = self::POOL_BET_PRODUCT;

        if ($columns === 'void') {
            $columns = "SUM(amount)";
            $void = "AND (result = 0 AND win_amount > 0)";
        }

        $order = $order ? "ORDER BY created_at DESC" : "";

        return "
            SELECT $columns
            FROM sport_transactions
            WHERE created_at BETWEEN '{$this->start}' AND '{$this->end}'
            AND user_id = {$this->user_id}
            $void
            AND product ='$poolbet_product'
            AND bet_type = '$bet_type'
            AND ignore_sportsbook_history IS NULL
            $order
            $limit
        ";
    }

    /**
     * Get list of transactions
     *
     * @param string $limit
     * @param string $sport
     * @param string $type
     * @return mixed
     */
    public function getTransactions($limit = '', $sport = '', $type = '', $bet_type = 'bet')
    {
        return phive('SQL')->sh($this->user_id)->loadArray(
            $this->generateQuery($limit, $sport, $type, '*',  $bet_type)
        );
    }

    public function getByBetTypeAndProductForTicketIds(string $bet_type, array $ticket_ids, bool $order = true, string $product = self::SPORTSBOOK_PRODUCT)
    {
        $order = $order ? "ORDER BY created_at DESC" : "";

        $ticket_ids_in = phive('SQL')->makeIn($ticket_ids);

        $query =  "
            SELECT *
            FROM sport_transactions
            WHERE ticket_id IN ($ticket_ids_in)
            AND user_id = {$this->user_id}
            AND bet_type = '$bet_type'
            AND product = '$product'
            AND ignore_sportsbook_history IS NULL
            $order
        ";

        return phive('SQL')->sh($this->user_id)->loadArray(
            $query
        );
    }

    public function getPoolxTransactions($limit = '', $bet_type = 'bet')
    {
        return phive('SQL')->sh($this->user_id)->loadArray(
            $this->generateQueryPoolx($limit,'*',  true, $bet_type)
        );
    }

    /**
     * Get specific aggregates based stats required
     *
     * @param $item
     * @param $sport
     * @param $type
     * @return mixed
     */
    public function getStats($item, $sport, $type)
    {
        $transactions = $this->getTransactions('', $sport, $type);
        $ticket_ids = array_column($transactions, 'ticket_id');

        switch ($item) {
            case 'count':
                $result = count($transactions);
                break;
            case 'staked':
                $result = array_sum(array_column($transactions, 'amount'));
                break;
            case 'won':
                $wins = $this->getByBetTypeAndProductForTicketIds('win', $ticket_ids);
                $result = array_sum(array_column($wins, 'amount'));
                break;
            case 'lost':
                $lost = array_filter($transactions, function ($transaction) {
                    return (bool) $transaction['ticket_settled'] ;
                });
                $result = array_sum(array_column($lost, 'amount'));
                break;
            case 'void':
                $voids = $this->getByBetTypeAndProductForTicketIds('void', $ticket_ids);
                $result = array_sum(array_column($voids, 'amount'));
                break;
            default:
                throw new InvalidArgumentException("Wrong $item argument");
        }

        return $result;
    }

    public function getStatsPoolx($item)
    {
        $transactions = $this->getPoolxTransactions();
        $ticket_ids = array_column($transactions, 'ticket_id');

        switch ($item) {
            case 'count':
                $result = count($transactions);
                break;
            case 'staked':
                $result = array_sum(array_column($transactions, 'amount'));
                break;
            case 'won':
                $wins = $this->getByBetTypeAndProductForTicketIds('win', $ticket_ids, true, self::POOL_BET_PRODUCT);
                $result = array_sum(array_column($wins, 'amount'));
                break;
            case 'lost':
                $lost = array_filter($transactions, function ($transaction) {
                    return (bool) $transaction['ticket_settled'] ;
                });
                $result = array_sum(array_column($lost, 'amount'));
                break;
            case 'void':
                $voids = $this->getByBetTypeAndProductForTicketIds('void', $ticket_ids, true, self::POOL_BET_PRODUCT);
                $result = array_sum(array_column($voids, 'amount'));
                break;
            default:
                throw new InvalidArgumentException("Wrong $item argument");
        }

        return $result;
    }

    /**
     * Get a key => value list of all sports in sportsbook sorted by the name of the sport,
     * where the key is the alias of the sport and the value is the name of the sport in English
     * E.g.
     * [
     *   ...
     *   ['sb.sport.2.name' => 'Basketball'],
     *   ...
     *   ['sb.sport.1.name' => 'Soccer'],
     *   ...
     * ]
     *
     * @param null $sport
     * @return mixed|null
     */
    public function getSports($sport = null)
    {
        $language = phive('Localizer')->getLanguage();
        $sports = phive('SQL')->loadArray(
            "SELECT original_name as value, name, ls.value as translated_name FROM sport_sports_list sl
             JOIN localized_strings ls ON sl.name = ls.alias
             WHERE ls.language = '$language'"
        );

        $sports = phive()->sort2d($sports, 'translated_name');
        $sports = array_reduce($sports, function ($carry, $item) {
            $carry[$item['value']] = $item['name'];
            return $carry;
        }, []);

        if (empty($sport)) {
            return $sports;
        }

        return $sports[$sport];
    }

    /**
     * Get a key => value list of the sports the current user has betted on,
     * where the key is the sport original name in english and the value is the alias of the sport
     * E.g.
     * [
     *   ...
     *   'Baseball' => 'sb.sport.sr:sport:3.name'
     *   'Soccer' => 'sb.sport.sr:sport:1.name',
     *   ...
     * ]
     *
     * @return array
     */
    public function getSportsBettedOn()
    {
        $language = phive('Localizer')->getLanguage();

        $sports_betted_on =  phive('SQL')->sh($this->user_id)->loadCol(
            "SELECT DISTINCT sport FROM sport_transaction_details WHERE user_id = {$this->user_id}",
            "sport"
        );

        $sports = phive('SQL')->loadArray(
            "SELECT original_name as value, name, ls.value as translated_name FROM sport_sports_list sl
             JOIN localized_strings ls ON sl.name = ls.alias
             WHERE ls.language = '$language'
            "
        );

        $sports = phive()->sort2d($sports, 'translated_name');
        $sports = array_reduce($sports, function ($carry, $item) use ($sports_betted_on) {
            if(in_array($item['value'], $sports_betted_on)) {
                $carry[$item['value']] = $item['name'];
            }
            return $carry;
        }, []);

        return $sports;
    }


    /**
     * Translate an sportsbook string, with input specifiers and competitors to its final translated value
     * e.g. input string = {$competitor1} vs {$competitor2} {-hcp};
     * specifiers = hcp=3
     * competitors = [
     *  ["competitor_name": "Buyuksehir", "competitor_qualifier": "home"],
     *  ["competitor_name": "Fenerbahce", "competitor_qualifier": "away"]
     * ]
     *
     * output = Buyuksehir vs Fenerbahce (-3)
     *
     * @param string $string
     * @param string $specifiers
     * @param array $competitors
     * @return string
     */
    public function translateStringUsingSpecifiersAndCompetitors(
        string $string,
        string $specifiers,
        array $competitors = []
    ): string
    {
        $text = t($string);

        if (count($competitors)) {
            $text = $this->replaceSportsbookDictionaryOnString($text, $competitors);
        }

        $specifiers_dictionary = $this->getSpecifiersDictionaryFromString($text, $specifiers);
        return $this->replaceSportsbookDictionaryOnString($text, $specifiers_dictionary);
    }

    /**
     * Replace the sportsbook dictionary into a string
     *
     * @param string $string
     * @param array $dictionary
     * @return string
     */
    public function replaceSportsbookDictionaryOnString(string $string, array $dictionary): string
    {
        foreach ($dictionary as $key => $value) {
            $string = str_replace(['{$' . $key . '}', '{' . $key . '}'], [$value, $value], $string);
        }
        return $string;
    }

    /**
     * Translate a string with specifiers using the specifiers as entry
     *
     * @param string $string base string e.g. "Real Madrid vs Barcelona ({-hcp}) And {!goalnr} goal
     * @param string $specifiers specifiers string e.g. hcp=2|goalnr=1
     * @return array ['-hcp' => -2, '!goalnr' => 1st]
     */
    public function getSpecifiersDictionaryFromString(string $string, string $specifiers): array
    {
        $specifiers_dictionary = array_reduce(explode('|', $specifiers), static function ($carry, $specifier) {
            $specifier_item = explode('=', $specifier);
            $carry[$specifier_item[0]] = $specifier_item[1];

            return $carry;
        });

        preg_match_all(static::SPECIFIERS_PATTERN, $string, $specifiers_in_string);

        return array_reduce(
            $specifiers_in_string[1],
            function ($carry, $item) use ($specifiers_dictionary) {
                if (in_array($item[0], array_keys(static::SPECIFIER_MODIFIERS))) {
                    $method = static::SPECIFIER_MODIFIERS[$item[0]];
                    $specifier_key = substr($item, 1);

                    $carry[$item] = $this->{$method}($specifier_key, $specifiers_dictionary[$specifier_key]);
                } else {
                    $carry[$item] = $this->formatNeutralSpecifier($item, $specifiers_dictionary[$item]);
                }

                return $carry;
            },
            []
        );
    }

    public function calcSportsBookDailyStats($sdate, $edate, $date, $db = '')
    {
        $types_mapping = [
            'bet' => 'bets',
            'win' => 'wins',
            'void' => 'void'
        ];

        $is_shard = !empty($db);
        $this->setDb($db, $is_shard);
        $db = $this->db;

        $grouped_bets = $db->loadArray("
        SELECT sp.user_id, sp.bet_type, SUM(sp.amount) as 'amount', u.currency, u.country, sp.network, sp.product
        FROM sport_transactions sp
        LEFT JOIN users u ON u.id = sp.user_id
        WHERE bet_type IN ('bet', 'win', 'void')
        AND created_at >= '$sdate'
        AND created_at <= '$edate'
        GROUP BY sp.user_id, sp.network, sp.product, sp.bet_type
    ");

        /* Initialize array to store bet amounts per user, network, and product */
        $user_bets = [];

        /* Organize bet amounts by user, network, and product, and bet type */
        foreach ($grouped_bets as $grouped_bet) {
            $user_id = $grouped_bet['user_id'];
            $network = $grouped_bet['network'];
            $product = $grouped_bet['product'];
            $bet_type = $grouped_bet['bet_type'];
            $amount = $grouped_bet['amount'];

            /* Store the bet, win, and void amounts for each user/network/product combination */
            $user_bets[$user_id][$network][$product][$bet_type] = $amount;
            $user_bets[$user_id][$network][$product]['currency'] = $grouped_bet['currency'];
            $user_bets[$user_id][$network][$product]['country'] = $grouped_bet['country'];
        }

        /* Initialize and make inserts */
        $inserts = $this->processUserBets($user_bets, $types_mapping, $date);

        $db->insert2DArr('users_daily_stats_sports', array_values($inserts));
        $this->setDb();
    }

    private function processUserBets(array $user_bets, array $types_mapping, string $date): array
    {
        $inserts = [];

        foreach ($user_bets as $user_id => $networks) {
            foreach ($networks as $network => $products) {
                foreach ($products as $product => $bets) {
                    /* Calculate gross, opp_fee, tax, before deal */
                    $gross = $this->calculateGross($bets, $product);
                    $op_fee = $this->calculateOpFee($gross, $product);
                    $tax = $this->calculateTax($gross, $bets['country'], $user_id);
                    $bank_fee =  $this->calculateBankFee($gross);
                    $before_deal = $this->calculateBeforeDeal($gross, $op_fee, $tax, $bank_fee);

                    /* Prepare insert data */
                    $insert_key = $this->generateInsertKey($user_id, $network, $product);
                    $inserts[$insert_key] = $this->prepareInsertData($user_id, $network, $product, $bets, $gross, $op_fee, $tax, $before_deal, $date);

                    /* Add bet, win, and void amounts */
                    $this->addBetAmounts($inserts[$insert_key], $bets, $types_mapping);
                }
            }
        }

        return $inserts;
    }

    private function generateInsertKey(int $user_id, string $network, string $product): string
    {
        return "{$user_id}_{$network}_{$product}";
    }

    private function prepareInsertData(int $user_id, string $network, string $product, array $bets, int $gross, float $op_fee, float $tax, float $before_deal, string $date): array
    {
        return [
            'user_id' => $user_id,
            'network' => $network,
            'product' => $product,
            'currency' => $bets['currency'],
            'country' => $bets['country'],
            'date' => $date,
            'gross' => $gross,
            'op_fee' => $op_fee,
            'tax' => $tax,
            'before_deal' => $before_deal
        ];
    }

    private function addBetAmounts(array &$insert_data, array $bets, array $types_mapping): void
    {
        foreach ($types_mapping as $bet_type => $column) {
            $insert_data[$column] = $bets[$bet_type] ?? 0;
        }
    }

    private function calculateGross(array $bets, string $product): int
    {
        $bet_amount = $bets['bet'] ?? 0;
        $win_amount = $bets['win'] ?? 0;
        $void_amount = $bets['void'] ?? 0;

        /* In case of 'P' (pool betting) product */
        if ($product === self::POOL_BET_PRODUCT) {
            return ($bet_amount - $void_amount) * self::PRODUCTS[$product]['gross_calc'];
        }

        return $bet_amount - $win_amount - $void_amount;
    }

   private function calculateOpFee(int $gross, string $product): float
   {
       return $gross * self::PRODUCTS[$product]['op_fee'];
   }

    private function calculateTax(int $gross, string $country, int $user_id): float
    {
        $tax_map = phive('CasinoCashier')->getTaxMap();
        $vat_map = phive('CasinoCashier')->getVatMap();

        if (!empty($tax_map[$country])) {
            return $gross * $tax_map[$country];
        } elseif (!empty($vat_map[$country])) {
            return $gross * $vat_map[$country];
        } else {
            phive('Logger')->log('Cannot calculate user tax', [
                'user_id' => $user_id,
                'user_country' => $country,
                'time' => date('Y-m-d H:i:s')
            ]);

            return 0.00;
        }
    }

    private function calculateBankFee(int $gross): float
    {
        return $gross * self::BANK_FEE;
    }

    private function calculateBeforeDeal(int $gross, float $op_fee, float $tax, float $bank_fee): float
    {
        return $gross - $op_fee - $bank_fee - $tax ;
    }

    public function shouldTransformMenuItemToMaintenance(string $alias, string $maintenanceSetting, string $settingKey = 'maintenance_menus'): bool
    {
        if (!$this->isMaintenanceMenu($alias, $settingKey)) {
            return false;
        }

        if (!lic($maintenanceSetting)) {
            return false;
        }

        if (!phive('Sportsbook')->isLoggedInAsMaintenanceUser()) {
            return false;
        }

        return true;
    }

    public function isLoggedInAsMaintenanceUser(): bool
    {
        $loggedInUser = cu();

        if ($loggedInUser && cu($this->user_id)->isTestAccount()) {
            return true;
        }

        if ($loggedInUser && privileged($loggedInUser)) {
            return true;
        }

        return false;
    }

    public function isMaintenanceMenu(string $alias, string $settingKey = 'maintenance_menus'): bool
    {
        return in_array($alias, phive('Sportsbook')->getSetting($settingKey));
    }
}
