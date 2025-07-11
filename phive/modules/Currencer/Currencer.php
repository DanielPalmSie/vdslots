<?php
require_once __DIR__ . '/../../api/PhModule.php';

/*
 * This class wraps the currencies table and fx_rates table.
 *
 * @link https://wiki.videoslots.com/index.php?title=DB_table_currencies The wiki docs for the table behind this logic / class.
 * @link https://wiki.videoslots.com/index.php?title=DB_table_fx_rates The wiki docs for the FX rates table.
 */

class Currencer extends PhModule
{

    // TODO henrik remove
    private $currency;

    /**
     * @var array An array to cache the currency data, we typically need the currency data multiple times
     * in the process of serving a request, but we only need to fetch it once from the database.
     */
    public $cur_cache = [];

    /**
     * Returns the configured base site currency, eg: EUR.
     *
     * @param string $cur Optional ISO3 override which will be returned instead.
     *
     * @return string The ISO3 currency code.
     */
    function baseCur($cur = '')
    {
        if (empty($cur)) {
            return $this->getSetting('base_currency');
        }
        return $cur;
    }

    // TODO henrik remove
    function baseCurSign($cur = '')
    {
        $cur = $this->baseCur($cur);
        return $cur == 'USD' ? '$' : '€';
    }

    // TODO henrik remove
    public function addCurrency($data)
    {
        $sql = phive('SQL');
        $table = $this->getSetting('db_currencies');
        return $sql->insertArray($table, $data);
    }

    /**
     * Updates a currency by way of the currency code which is the primary key.
     *
     * Typically only the multiplier column is updated (every day) with a new FX rate.
     *
     * @param array $data The currency data.
     *
     * @return bool True if the update query wasv successful, false otherwise.
     */
    public function updateCurrency($data)
    {
        $sql = phive('SQL');
        $table = $this->getSetting('db_currencies');
        $code = $data['code'];
        unset($data['code']);
        return $sql->updateArray($table, $data, "`code`=" . $sql->escape($code));
    }

    /**
     * Gets a currency by way of the currency ISO3 code and optionally a date.
     *
     * The reason for the optional date is that we might need to get historical FX rates for display
     * in for instance BO GUIs, if it is passed in we query the fx_rates table instead of the currencies table.
     *
     * @param string $code Currency ISO3 code.
     * @param string $date Time string, can be anything that strtotime() can convert to a Y-m-d date.
     *
     * @return array The currency.
     */
    public function getCurrency($code, $date = '')
    {
        if (!empty($date)) {
            $date = date('Y-m-d', strtotime($date));
            if ($date != phive()->today()) {
                if (!empty($this->cur_cache[$code . $date])) {
                    return $this->cur_cache[$code . $date];
                }
                $this->cur_cache[$code . $date] = phive('SQL')->loadAssoc("SELECT * FROM fx_rates WHERE `code` = '$code' AND day_date = '$date'");
                if (empty($this->cur_cache[$code . $date])) {
                    phive('Logger')->getLogger('Currencer')->logTrace("Fatal error: currency $code: $date missing!",
                        'ERROR');
                } else {
                    return $this->cur_cache[$code . $date];
                }
            }
        }
        if (!empty($this->cur_cache[$code])) {
            return $this->cur_cache[$code];
        }
        $table = $this->getSetting('db_currencies');
        $this->cur_cache[$code] = phive('SQL')->loadAssoc("SELECT * FROM `$table` WHERE `code`=" . phive('SQL')->escape($code));
        return $this->cur_cache[$code];
    }

    /**
     * Gets the currency symbol with the help of the ISO3 symbol, eg USD will return $.
     *
     * @param string $cur_iso The ISO3 symbol.
     *
     * @return string The currency symbol.
     */
    function getCurSym($cur_iso)
    {
        return phive("SQL")->getValue("SELECT symbol FROM currencies WHERE code = '$cur_iso'");
    }

    // TODO henrik remove
    public function setCurrency($code)
    {
        $this->currency = $this->getCurrency($code);
    }

    // TODO henrik remove
    public function deleteCurrency($code)
    {
        $sql = phive('SQL');
        $table = $this->getSetting('db_currencies');
        return $sql->delete($table, "`code`=" . $sql->escape($code));
    }

    /**
     * Gets all rows from the currencies table.
     *
     * @param bool $legacy True if we want to fetch legacy currencies too, false otherwise.
     * A legacy currency is a currency that was officially supported once but is not anyrmoe but
     * we still need to support it in some contexts, eg allowing players to withdraw their legacy
     * balances.
     *
     * @return array The currencies.
     */
    function getAllCurrencies($legacy = true)
    {
        $where = empty($legacy) ? " WHERE legacy = 0" : '';
        return phive('SQL')->readonly()->loadArray("SELECT * FROM `currencies`{$where} ORDER BY `code`", 'ASSOC',
            'code');
    }

    /**
     * Gets the FX rate for a currency.
     *
     * @param string $code The ISO3 code for the currency.
     *
     * @return float The FX rate.
     */
    function getMultiplier($code)
    {
        if ($code == $this->baseCur()) {
            return 1;
        }

        $table = $this->getSetting('db_currencies');
        return phive('SQL')->db($table)->getValue("SELECT `multiplier` FROM `$table` WHERE `code`=" . $sql->escape($code));
    }

    // TODO henrik remove
    public function addBaseCurrency()
    {
        if ($this->addCurrency(array('code' => $this->getSetting('base_currency'), 'multiplier' => 1.0))) {
            return new PhMessage(PHM_OK);
        } else {
            return new PhMessage(PHM_ERROR, "Could not create base currency");
        }
    }

    /**
     * The core function that performs FX from one currency to another.
     *
     * @param mixed $from This can be either an array if we have the currency row already, an object if we have the user
     * or an ISO3 code if that is all we have.
     * @param mixed $to Same goes here as for $from.
     * @param int $amount The amount to exchange.
     * @param float $keep An exchange rate fee, we might need to apply that in case we are subjected to FX fees ourselves
     * as a result of this internal conversion.
     * @param string $date Optional date for a historical FX rate.
     *
     * @return float The exchanged amount.
     */
    public function changeMoney($from, $to, $amount, $keep = 0.99, $date = '')
    {

        $to = empty($to) ? $this->baseCur() : $to;

        if ($from == $to) {
            return $amount;
        }

        if (is_string($from)) {
            $from = $this->getCurrency($from, $date);
        }

        if (is_string($to)) {
            $to = $this->getCurrency($to, $date);
        }

        $amount = ($keep * $amount * $to['multiplier']) / $from['multiplier'];
        return round($amount, 2);
    }

    // TODO henrik remove
    function parseRate($from, $to)
    {
        $map = array('AUD' => 'currency/aud-australian-dollar');
        $s = phive()->get("http://www.xe.com/{$map[$to]}");
        $matches = array();
        preg_match('|href="/currencycharts/\?from=' . $from . '&amp;to=' . $to . '">([0-9\.]+)|', $s, $matches);
        if (isset($matches[1])) {
            return $matches[1];
        } else {
            return false;
        }
    }

    // TODO henrik remove
    function getNetEurRate($code)
    {
        return $this->parseRate('EUR', $code);
    }

    // TODO henrik remove
    function getNetCurrencyRate($code, $withname = false)
    {

        $base_cur = $this->getSetting('base_currency');

        if (empty($base_cur)) {
            $base_cur = 'EUR';
        }

        if ($base_cur == $code) {
            return 1;
        }

        return $this->parseRate($base_cur, $code);
    }

    /**
     * Gets rates for the currencies in question from currencydata.xe.com.
     *
     * This should be run ONCE per day in a cron job, we only have 10k calls / day so can not be used in any other way
     * than updating our own local values.
     *
     * @link https://xecdapi.xe.com/docs/v1/authentication#/Mid-market%20Rates/get_v1_convert_from
     *
     * @param array|string $currencies Array of ISO codes or comma separated string of ISO codes.
     *
     * @return array The rates vs the base currency with the ISO code as key, ex: ['CLP' => 905, 'PEN' => 4]
     */
    public function xeGetRates($currencies)
    {
        $conf = $this->getSetting('xe');
        $auth_header = "Authorization: Basic " . base64_encode($conf['user'] . ":" . $conf['key']);
        $get_str = http_build_query([
            'amount' => 1,
            'from' => $this->baseCur(),
            'to' => is_array($currencies) ? implode(',', $currencies) : $currencies
        ]);
        $url = 'https://xecdapi.xe.com/v1/convert_from?' . $get_str;
        $res = phive()->post($url, '', 'application/json', [$auth_header], '', 'GET');
        $ret = [];
        if (!empty($res)) {
            $rates = json_decode($res, true);
            if (!empty($rates) && !empty($rates['to'])) {
                foreach ($rates['to'] as $to_currency) {
                    $ret[$to_currency['quotecurrency']] = $to_currency['mid'];
                }
            }
        }
        return $ret;
    }


    /**
     * Cron job to update all currencies by way of the ECB daily XML file with all FX rates.
     *
     * Note that we update the currencies table and we insert into the fx_rates.
     *
     * @return null
     */
    function updateCurrenciesInEur()
    {
        $xml = phive()->get('https://www.ecb.int/stats/eurofxref/eurofxref-daily.xml');
        preg_match_all("|currency='([^']+)' rate='([^']+)'|", $xml, $m);
        $res = array_combine($m[1], $m[2]);
        $base_cur = $this->baseCur();
        $multiplier_failed = false;

        // XE rates that are not supported by ECB
        $xe_rates = $this->xeGetRates($this->getSetting('xe')['currencies']);

        if (empty($res)) {
            $yesterday = phive()->yesterday();
            error_log("ECB is down or XML is changed so copying yesterday which is: $yesterday, XML: $xml");
            $rates = phive('SQL')->loadArray("SELECT * FROM fx_rates WHERE day_date = '$yesterday'");
            foreach ($rates as $rate) {
                phive('SQL')->insertArray('fx_rates', [
                    'day_date' => phive()->today(),
                    'code' => $rate['code'],
                    'multiplier' => $rate['multiplier']
                ]);

                if ((empty($rate['multiplier']) || $rate['multiplier'] == 'inf' || $rate['multiplier'] == 0) && !$multiplier_failed) {
                    $multiplier_failed = true;
                }
            }
        } else {
            foreach (phive("SQL")->loadArray("SELECT * FROM currencies WHERE code != '$base_cur'") as $c) {
                $rate = $res[$c['code']];

                if (empty($rate)) {
                    // The rate was not found in the ECB data, we try XE instead.
                    $rate = $xe_rates[$c['code']];
                }

                if (!empty($rate)) {
                    $this->updateCurrency(array('code' => $c['code'], 'multiplier' => $rate));
                }

                phive('SQL')->insertArray('fx_rates', [
                    'day_date' => phive()->today(),
                    'code' => $c['code'],
                    'multiplier' => $rate
                ]);

                if ((empty($rate) || $rate == 'inf' || $rate == 0) && !$multiplier_failed) {
                    $multiplier_failed = true;
                }
            }
            phive('SQL')->insertArray('fx_rates',
                ['day_date' => phive()->today(), 'code' => $base_cur, 'multiplier' => 1]);

            if (phive()->moduleExists('MailHandler2') && $multiplier_failed) {

                $subject = "<p>Missing Currency Multiplier in Databases</p>";
                $content = "<p>The multiplier values are missing from fx_rates database</p>";
                $to = phive()->getSetting('dev_support_mail') ?? 'devsupport@videoslots.com';

                phive('MailHandler2')->mailLocal($subject, $content, '', $to);
            }
        }
    }

    /**
     * Creates a synthetic row from misc. settings for the default site currency, ie it is creating
     * an array that looks like a row from the currencies table in the database.
     *
     * @return array The array of data.
     */
    function defCur()
    {
        $ss = $this->allSettings();
        return array('code' => $ss['cur_iso'], 'symbol' => $ss['cur_sym'], 'mod' => $ss['mod']);
    }

    /**
     * update current session currency value based on request parameter or jurisdiction based currency
     * @return void
     */
    function setSessionCurrencyValue() {
        $currency = $_REQUEST['site_currency'] ?? lic('getForcedCurrency', []);
        $this->setSessionCur($currency);
    }

    /**
     * Sets the session currency.
     *
     * Note that if we are in a logged out context we get the country by way of the IP number and then
     * use the country to get the most appropriate currency. As a last resort we use the default site
     * currency.
     *
     * @param array $cur If passed in it will be used for explicitly setting the session currency.
     *
     * @return null
     * @uses setCur() To set the the currency for the remaining execution.
     *
     */
    function setSessionCur($cur = '')
    {
        $user = cu();

        if (is_object($user)) {
            return setCur($user);
        }

        if (!empty($cur)) {
            $_SESSION['cc'] = $cur;
        }

        if (empty($_SESSION['cc'])) {
            $res = phive('IpBlock')->getCountry();
            if (!empty($res)) {
                $cur = phive("SQL")->loadAssoc('', 'currencies', "LOWER(countries) LIKE '%" . strtolower($res) . "%'");
            }
            if (empty($cur)) {
                $cur = $this->defCur();
            }
            $_SESSION['cc'] = $cur;
        }

        setCur($_SESSION['cc']);
    }

    public function getVisitorCurrency(?int $userId)
    {
        $user = cu($userId);
        if (is_object($user)) {
            return $user->getAttr('currency');
        }
        $country = phive('IpBlock')->getCountry();
        $currency = phive("SQL")->loadAssoc('', 'currencies', "LOWER(countries) LIKE '%" . strtolower($country) . "%'");
        if (empty($currency)) {
            $currency = $this->defCur();
        }
        return $currency['code'];
    }

    /**
     * Potentially converts a legacy currency amount into the default currency.
     *
     * TODO henrik fix the spelling of legacy_currencies.
     *
     * @param DBUser $u User object.
     * @param int $cents The amount to potentially convert.
     * @param float $conv_rate Modification to the conversion rate.
     *
     * @return array With the new currency and new amount.
     */
    function convertLegacyCurrency($u, $cents, $conv_rate = 1)
    {
        $def_currency = $this->getSetting('base_currency');
        $legacy_currencies = $this->getSetting('legacy_curencies');
        $user_currency = $u->getCurrency();
        if (in_array($user_currency, $legacy_currencies)) {
            return [$def_currency, floor(chg($user_currency, $def_currency, $cents, $conv_rate))];
        }
        return [$user_currency, $cents];
    }

    /**
     * In rare cases we need to round cents up to the nearest whole unit, this method does that whilst keeping the cents format.
     * Example: 15566 becomes 15600 and 15536 becomes 15500.
     *
     * @param int $cents The cents, if it is a decimal number it will be rounded to cents.
     *
     * @return int The whole units in cents so will contain 00 at the end.
     */
    public function roundCentsToWholeUnit($cents)
    {
        // Remove potential fractional cents.
        $cents = round($cents);

        $remainder = $cents % 100;
        if ($remainder == 0) {
            // We already have a number with whole units.
            return $cents;
        }

        if ($remainder < 50) {
            // We round down if lower than 50.
            return $cents - $remainder;
        } else {
            // We round up if more than 50.
            return $cents + (100 - $remainder);
        }
    }

    /**
     * Returns the currency data for the given country
     *
     * @param string $country_code in alpha-2 format, ie: MT, IT, SE
     * @return array|null
     */
    public function getCurrencyByCountryCode(string $country_code) : ?array {
        $currencies = phive('Currencer')->getAllCurrencies();

        $currency = array_filter($currencies, function ($currency) use ($country_code) {
            return str_contains(strtoupper($currency['countries']), strtoupper($country_code));
        });

        return empty($currency) ? null : reset($currency);
    }

    /**
     * @param float $amount The amount to be converted
     * @param $currency
     * @return string The formatted currency amount
     */
    public function formatCurrency(float $amount, $currency): string
    {
        $converted_amount = nf2($amount, true, 100);

        $currency_symbol = $this->getCurSym($currency);

        return $currency_symbol . $converted_amount;
    }

    /**
     * This function will convert a number into it's equivalent for the currencies permitted to be used by the UKGC
     *
     * @param string $curIso The currency we are converting to (ISO format)
     * @param int $num The monetary value we want to convert
     * @return int
     */
    function convertCurrencyFromGBP(string $curIso, int $num)
    {
        $ukgcAllowedCurrencies = [
            'USD' => 1,
            'EUR' => 1,
            'NZD' => 2,
            'CAD' => 1,
            'BRL' => 7,
            'GBP' => 1
        ];

        // return 0 if we try to convert to a forbidden currency
        if(!array_key_exists($curIso, $ukgcAllowedCurrencies)){
            return 0;
        }

        // we multiply the GB player's bet amount (2 or 5), with the corresponding currency conversion rate
        return $num * $ukgcAllowedCurrencies[$curIso];
    }
}

/**
 * Sets the currency to use for the remaining execution.
 *
 * @param mixed $cur If int we assume a user id.
 *
 * @return null
 */
function setCur($cur)
{
    if (is_numeric($cur)) {
        $user = cu($cur);
        if (is_object($user)) {
            $GLOBALS['cc'] = phive("Currencer")->getCurrency($user->getAttr('currency'));
        } else {
            setDefCur();
        }
    } else {
        if (is_string($cur)) {
            $GLOBALS['cc'] = phive("Currencer")->getCurrency($cur);
        } else {
            $GLOBALS['cc'] = is_object($cur) ? phive("Currencer")->getCurrency($cur->getAttr('currency')) : $cur;
        }
    }
}

/**
 * Gets the currently set currency.
 *
 * @return array The currency.
 */
function getCur()
{
    if (empty($GLOBALS['cc'])) {
        setCur(phive('Currencer')->getSetting('cur_iso'));
    }
    return $GLOBALS['cc'];
}

/**
 * This function multiplies a currency with a fixed multiplier.
 *
 * This function is basically behind the so called "pretty FX rate" which is the currencies.mod value.
 * It is typically a multiplier that roughly corresponds to the average real FX rate in relation to the casino
 * default currency. If for instance the default currency is EUR then the SEK rate is 10 and the JPY rate is 100.
 * The reason for this is that it makes for better looking numbers, example: "Get a 10 EUR bonus!" becomes
 * "Get a 100 SEK bonus!" and so on.
 *
 * @param int $amount The amount to multiply.
 * @param object|string $cur_or_user A user object or a currency ISO3 string.
 * @param string $op Operation, multi or div, we typically want to multiply.
 * @param bool $round True if we are to round to the nearest cent, false otherwise.
 *
 * @return int|float The new "pretty" amount.
 */
function mc($amount, $cur_or_user = '', $op = 'multi', $round = true)
{

    if (empty($amount)) {
        return $amount;
    }

    if (empty($cur_or_user)) {
        $cur = getCur();
    } else {
        $cur = phive("Currencer")->getCurrency(is_object($cur_or_user) ? $cur_or_user->getAttr('currency') : $cur_or_user);
    }

    $mod = empty($cur['mod']) ? 1 : $cur['mod'];

    if ($op == 'multi') {
        return $round ? round($amount * $mod) : $amount * $mod;
    } else {
        return $round ? round($amount / $mod) : $amount / $mod;
    }
}

/**
 * Sets the default currency for the remaining execution.
 *
 * @return null
 */
function setDefCur()
{
    $iso = phive('Currencer')->getSetting('cur_iso');
    if (empty($iso)) {
        $GLOBALS['cc'] = array();
    } else {
        $GLOBALS['cc'] = phive("Currencer")->defCur();
    }
}

/**
 * Returns (or echoes) the currenct currency symbol, ex: $.
 *
 * @param bool $echo Whether or not to echo instead of returning.
 *
 * @return null|string The currency symbol.
 */
function cs($echo = false)
{
    if (!empty($GLOBALS['cc'])) {
        $sym = $GLOBALS['cc']['symbol'];
    } else {
        $sym = phive('Currencer')->getSetting('cur_sym');
    }

    if ($echo) {
        echo $sym;
    } else {
        return $sym;
    }
}

/**
 * Returns (or echoes) the currenct currency ISO3 code, ex: EUR.
 *
 * @param bool $echo Whether or not to echo instead of returning.
 *
 * @return null|string The currency code.
 */
function ciso($echo = false)
{
    if (!empty($GLOBALS['cc'])) {
        $sym = $GLOBALS['cc']['code'];
    } else {
        $sym = phive('Currencer')->getSetting('cur_iso');
    }
    if ($echo) {
        echo $sym;
    } else {
        return $sym;
    }
}

/**
 * Gets all currency ISO3 codes that are supported as a potentially straight array.
 *
 * @param bool $only_isos If true we just return a simple array with each ISO3 code.
 * If false we return an array of arrays.
 * @param $all =false // TODO henrik remove, refactor all invocations.
 * @param bool $legacy Whether or not to include legacy currencies in the result.
 *
 * @return array The result
 */
function cisos($only_isos = true, $all = false, $legacy = true)
{
    $str = "SELECT * FROM currencies";
    if ($legacy === false) {
        $str .= " WHERE legacy = 0";
    }
    return $only_isos ? phive("SQL")->loadCol($str, 'code') : phive("SQL")->loadArray($str);
}

/**
 * Performs FX from one currendcy to another.
 *
 * @param mixed $from The from source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param mixed $to The to source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param int $amount The amount to convert.
 * @param int $keep FX fee, if it is 2% we put 0.98 here.
 * @param string $date If passed in we get a historical rate.
 *
 * @return float The exchanged amount.
 */
function chg($from, $to, $amount, $keep = 0.99, $date = '')
{
    if ($amount === 0) {
        return 0;
    }
    if (is_object($to)) {
        $to = $to->getAttr('currency');
    }
    if (is_object($from)) {
        $from = $from->getAttr('currency');
    }
    return phive('Currencer')->changeMoney($from, $to, $amount, $keep, $date);
}

/**
 * Alias of chg() in order to change to the site currency.
 *
 * @param mixed $from The from source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param int $amount The amount to convert.
 * @param int $keep FX fee, if it is 2% we put 0.98 here.
 * @param string $date If passed in we get a historical rate.
 *
 * @return float The exchanged amount.
 */
function chgToDefault($from, $amount, $keep = 0.99, $date = '')
{
    return chg($from, phive('Currencer')->baseCur(), $amount, $keep, $date);
}

/**
 * Alias of chg() in order to change from the site currency.
 *
 * @param mixed $to The to source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param int $amount The amount to convert.
 * @param int $keep FX fee, if it is 2% we put 0.98 here.
 * @param string $date If passed in we get a historical rate.
 *
 * @return float The exchanged amount.
 */
function chgFromDefault($to, $amount, $keep = 0.99, $date = '')
{
    return chg(phive('Currencer')->baseCur(), $to, $amount, $keep, $date);
}

/**
 * Alias of chg() that simply rounds the result to the nearest cent.
 *
 * @param mixed $from The from source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param mixed $to The to source, if object we assume user object and try to get the currency attribute
 * otherwise we assume ISO3 code.
 * @param int $amount The amount to convert.
 * @param int $keep FX fee, if it is 2% we put 0.98 here.
 * @param string $date If passed in we get a historical rate.
 *
 * @return int The exchanged amount.
 */
function chgCents($from, $to, $amount, $keep = 0.99, $date = '')
{
    return round(chg($from, $to, $amount, $keep, $date));
}
