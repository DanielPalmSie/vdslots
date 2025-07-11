<?php

namespace Tests\Api\Phive\Modules\Micro\Swintt;

use \ApiTester;
use Helper\AppHelper;
use \SimpleXMLElement;

/**
 * Class BaseCest
 * @package Tests\Api\Phive\Modules\Micro\Swintt
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Swintt/BaseCest
 */
class BaseCest
{
    protected $game_provider_module;
    protected $sql_module;

    protected $user_prefix = "VS-";
    protected $user_identifier;
    protected $phive_user;
    protected $game_id;
    protected $external_session;
    protected $date_format = "D M d H:i:s \U\T\C Y";

    /**
     * BaseCest constructor.
     * @param null $game_provider_module
     * @param null $sql_module
     */
    public function __construct($game_provider_module = null, $sql_module = null)
    {
        $this->game_provider_module = $game_provider_module ?: phive('Swintt');
        $this->sql_module = $sql_module ?: phive('SQL');

        $this->user_identifier = AppHelper::dbInsertUser($this->sql_module, 'devtestmt');
        // TODO: To test Battle of Slots we need first to create a tournament and set the user-tournament balance.
//        $this->user_identifier .= "e12345";

        $this->phive_user = cu($this->user_identifier);
        $this->unblockPhiveUser();

        $this->game_id = 15608;     // Leokan;
        AppHelper::dbInsertGame($this->sql_module, 'Swintt', $this->game_id, 'Leokan');

        $this->external_session = $this->game_provider_module->getMockExternalSession($this->user_identifier, $this->game_id);
    }

    /**
     * Returns the balance of the user (e.g. '5541343') or tournament user .g. '5541343e777777').
     */
    protected function getUserIdentifierBalance()
    {
        $this->game_provider_module->loadUser($this->user_identifier);
        return $this->game_provider_module->getPlayerBalance();
    }

    /**
     * @param array|null $data
     * @return string
     */
    protected function getFundsRequest(array $parameters): string
    {
        // Reloads the user to get the current balance.
        $this->phive_user = cu($this->user_identifier);

        $xml = '';
        foreach ($parameters as $k => $v) {
            $xml .= "\n {$k}=\"{$v}\"";
        }

        return "<cw {$xml}/>";
    }

    /**
     * @param array $transactions
     * @return string
     */
    protected function getMultipleFundsRequest(array $transactions): string
    {
        // Reloads the user to get the current balance.
        $this->phive_user = cu($this->user_identifier);

        $count = count($transactions);
        $acctid = $transactions[0]['acctid'];

        $xml = "<cw type=\"fundTransferReq\" acctid=\"{$acctid}\" transactions=\"{$count}\">";
        foreach ($transactions as $transaction) {
            $xml .= "\n<detail";
            foreach ($transaction as $k => $v) {
                if (!in_array($k, ['type', 'acctid'])) {
                    $xml .= "\n {$k}=\"{$v}\"";
                }
            }
            $xml .= " />";
        }

        return "{$xml}\n</cw>";
    }

    /**
     * @param array|null $data
     * @return string[]
     */
    protected function getFundsParameters(array $data = null): array
    {
        $currency = $this->phive_user->data['currency'] ?: '';
        $amount = isset($data["amt"]) ? floatval($data["amt"]) : -1;
        $transaction_id = hexdec(uniqid());
        $cancel_transaction_id = 0;
        $hand_id = hexdec(uniqid());
        $wager_id = uniqid();
        $device = "web";
        $date = date($this->date_format);

        $parameters = [
            "type" => "fundTransferReq",
            "txnsubtypeid" => "450",
            "acctid" => "{$this->user_prefix}{$this->user_identifier}",
            "cur" => "{$currency}",
            "amt" => null,
            "txnid" => "{$transaction_id}",
            "canceltxnid" => "{$cancel_transaction_id}",
            "gameid" => "{$this->game_id}",
            "handid" => "{$hand_id}",
            "gamedevicetype" => "{$device}",
            "playerhandle" => "{$this->external_session}",
            "wagerid" => "{$wager_id}",
            "dealid" => "{$wager_id}",
            "timestamp" => "{$date}",
        ];

        $is_win = ($amount >= 0);
        if ($is_win) {
            $parameters['txnsubtypeid'] = "460";
            $parameters['pjwincontrib'] = "0.000";
            $parameters['pjresetcontrib'] = "0.000";
            unset($parameters['wagerid']);
            unset($parameters['dealid']);
        }

        if (!empty($data)) {
            $parameters = array_merge($parameters, $data);
        }
        $parameters['amt'] = number_format($amount, 2, '.', '');

        return $parameters;
    }

    /**
     *
     */
    protected function unblockPhiveUser()
    {
        if ($this->phive_user) {
            $this->phive_user->setAttribute('active', 1);
            $this->phive_user->deleteSetting('play_block');
            $this->phive_user->deleteSetting('super-blocked');
        }
    }

    /**
     * Returns the value of an XML element attribute.
     *
     * @param SimpleXMLElement|null $xml_element
     * @param string|null $attribute. The attribute name.
     * @param string $data_type. The data type to cast the value to. If null then the XML attribute object is returned.
     * @return int|mixed|string|null
     *
     * @example getXmlAttribute($response, "amt")
     */
    protected function getXmlAttribute(SimpleXMLElement $xml_element = null, string $attribute = null, $data_type = 'string')
    {
        if ($xml_element instanceof SimpleXMLElement) {
            foreach ($xml_element->attributes() as $k => $v) {
                if ($k == $attribute) {
                    switch ($data_type) {
                        case 'string':
                            return (string)$v;
                        case 'int':
                            return (int)$v;
                        case 'float':
                            return (float)$v;
                        default:
                            return $v;
                    }
                }
            }
        }
        return null;
    }
}
