<?php

require_once __DIR__ . '/CasinoProvider.php';

class Swintt extends CasinoProvider
{
    protected $gp_name = __CLASS__;

    protected $receive_final_win = false;

    protected $confirm_win = true;

    protected $confirm_round = true;

    protected $force_http_ok_response = true;

    /**
     * Overrides the base class to allow partial refunds.
     */
    protected $partial_refund = true;


    /**
     * Saves the raw decoded request received from the game provider.
     * Overrides the base method to add better logging data.
     */
    protected function decodeRequest()
    {
        $request = file_get_contents('php://input');

        $this->raw_request = json_decode($request, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->request_data_type = 'json';
        } else {
            $this->raw_request = simplexml_load_string($request);
            if ($this->raw_request !== false) {
                $this->request_data_type = 'xml';
            } else {
                $this->raw_request = $request;
                $this->request_data_type = 'unknown';
            }
        }

        if ($this->raw_request instanceof SimpleXMLElement) {
            $user_identifier = $this->getXmlAttribute($this->raw_request, "acctid");
            $user_identifier = $this->trimUserPrefix($user_identifier);
            $this->loadUser($user_identifier);

            $this->game_provider_method_name = $this->getXmlAttribute($this->raw_request, "type");
        }
        $this->game_provider_method_name = $this->game_provider_method_name ?: 'invalid_request';

        $this->logRequest();
    }

    /**
     * Overrides the base class method to add all methods and parameters which need to be executed for this request.
     * For example if the game provider sends a 'betAndWin' request then the child class should call
     *   $this->addWalletMethod('bet', ['user_id' => 1, 'amount' => 10,  'game_id' => 2560 ...]]);
     *   $this->addWalletMethod('win', ['user_id' => 1, 'amount' => 860, 'game_id' => 2560 ...]]);
     * This method should validate that required parameters exist.
     *
     * @throws Exception If the request is invalid.
     */
    protected function addWalletMethods()
    {
        if (!($this->raw_request instanceof SimpleXMLElement)) {
            throw new Exception("Invalid XML request.", self::EXCEPTION_CODE_INVALID_REQUEST);
        }

        switch ($this->game_provider_method_name) {
            case "getBalanceReq":
            case "finalizeReq":
                $this->addWalletMethodGetBalance();
                break;

            case "fundTransferReq":
                $this->addWalletMethodBetOrWin();
                break;

            default:
                break;
        }
    }

    /**
     * @throws Exception
     */
    protected function addWalletMethodGetBalance()
    {
        $parameters = [
            'user_id' => $this->user_identifier,
            'currency' => $this->getXmlAttribute($this->raw_request, "cur"),
            'game_id' => $this->getXmlAttribute($this->raw_request, "gameid"),
        ];

        $this->validateGetBalance($parameters);

        $this->addWalletMethod('balance', $parameters);
    }

    /**
     * @throws Exception
     */
    protected function addWalletMethodBetOrWin()
    {
        $count_transactions = (int)$this->getXmlAttribute($this->raw_request, "transactions");

        if ($count_transactions) {
            foreach ($this->raw_request->children() as $xml_transaction) {
                $this->addWalletMethodBetOrWinTransaction($xml_transaction);
            }
        } else {
            $this->addWalletMethodBetOrWinTransaction($this->raw_request);
        }
    }

    /**
     * If the player's bet did not win anything then Swintt still send us a (win) "fundTransferReq" request with amount  = 0.
     *
     * @throws Exception
     */
    private function addWalletMethodBetOrWinTransaction(SimpleXMLElement $xml_element)
    {
        $parameters= [
            'user_id' => $this->user_identifier,
        ];

        $map = [
            'session_id' => 'playerhandle',
            'transaction_id' => 'txnid',
            'amount' => 'amt',
            'currency' => 'cur',
            'game_id' => 'gameid',
            'round_id' => 'handid',
        ];

        foreach ($map as $wallet_param => $request_param) {
            $parameters[$wallet_param] = $this->getXmlAttribute($xml_element, $request_param);
            if (($wallet_param != 'amount') && empty($parameters[$wallet_param])) {
                throw new Exception("Invalid request.", self::EXCEPTION_CODE_INVALID_REQUEST,
                    new Exception("Missing request parameter: {$request_param} ({$wallet_param}).")
                );
            }
        }

        if (!is_numeric($parameters['amount'])) {
            throw new Exception("Invalid request.", self::EXCEPTION_CODE_INVALID_REQUEST,
                new Exception("Invalid request parameter: amt (amount): {$parameters['amount']}.")
            );
        }
        $parameters['amount'] = $this->convertCoinage($parameters['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);
        $method = ($parameters['amount'] < 0) ? 'bet' : 'win';
        $parameters['amount'] = abs($parameters['amount']);

        $cached_user_identifier = $this->getCachedExternalSession($parameters['session_id'], $parameters['game_id']);
        if ($cached_user_identifier != $this->user_identifier) {
            throw new Exception(
                "Unauthorized.",
                self::EXCEPTION_CODE_USER_NOT_FOUND,
                new Exception("Invalid user. Expected user {$this->user_identifier} for external session {$parameters['session_id']} and game {$parameters['game_id']} but cache returned {$cached_user_identifier}.")
            );
        }

        $cancel_transaction_id = $this->getXmlAttribute($xml_element, "canceltxnid");
        if ($cancel_transaction_id) {
            $method = "rollback";
            $parameters['transaction_id'] = $cancel_transaction_id;
        }

        $this->addWalletMethod($method, $parameters);
    }

    /**
     * Overrides the base class method.
     *
     * @param array $rollback
     * @param array $original_transaction
     * @param string $typeof_original_transaction. 'bet', 'rolled_back_bet', 'win', 'rolled_back_win'
     * @throws Exception
     */
    protected function allowRollback(array $rollback, array $original_transaction, string $typeof_original_transaction)
    {
        if ($typeof_original_transaction != 'bet') {
            throw new Exception("Invalid request.", self::EXCEPTION_CODE_INVALID_REQUEST,
                new Exception("Only bets can be rolled back.")
            );
        }
    }

    /**
     * Generates the response to send to the game provider.
     *
     * @param mixed $response
     */
    public function response($response)
    {
        switch ($this->game_provider_method_name) {
            case "getBalanceReq":
            case "finalizeReq":
                $response_body = $this->getResponseGetBalance($response);
                break;

            case "fundTransferReq":
                $response_body = $this->getResponseBetOrWin($response);
                break;

            default:
                $response = simplexml_load_string("<cw type='invalid_request' err='9999'/>");
                $response_body = $response->asXML();
        }

        $this->setResponseHeaders($response);
        header('Content-Type: application/xml');
        return $response_body;
    }

    /**
     * @param $result
     * @return mixed
     */
    protected function getResponseGetBalance($result)
    {
        if ($this->game_provider_method_name == "finalizeReq") {
            $xml = "<cw type=\"finalizeResp\"";
        } else {
            $xml = "<cw type=\"getBalanceResp\" bonusamt=\"0\"";
        }

        if ($result !== true) {
            switch ($result['exception_code'] ?? '') {
                case self::EXCEPTION_CODE_USER_NOT_FOUND:
                 $error_code = 1000;
                 break;

                case self::EXCEPTION_CODE_INVALID_CURRENCY:
                    $error_code = 1001;
                    break;

                default:
                    $error_code = 9999;
                    break;
            }

            $xml .= " err=\"{$error_code}\" />";
            $this->response = simplexml_load_string($xml);
            return $this->response->asXML();
        }

        $balance_cents = $this->getPlayerBalance();
        $balance_units = $this->convertCoinage($balance_cents, self::COINAGE_CENTS, self::COINAGE_UNITS);
        $formatted_balance = number_format($balance_units, 2, '.', '');

        $xml .= " cur=\"{$this->user_identifier_currency}\" amt=\"{$formatted_balance}\" err=\"0\" />";
        $this->response = simplexml_load_string($xml);
        return $this->response->asXML();
    }

    /**
     * @param $result
     * @return mixed
     */
    protected function getResponseBetOrWin($result)
    {
        while ($result !== true) {
            switch ($result['exception_code'] ?? '') {
                case self::EXCEPTION_CODE_USER_NOT_FOUND:
                case self::EXCEPTION_CODE_TRANSACTION_NOT_FOUND:
                    $error_code = 1000;
                    break;

                case self::EXCEPTION_CODE_INVALID_CURRENCY:
                    $error_code = 1001;
                    break;

                case self::EXCEPTION_CODE_USER_BANNED:
                case self::EXCEPTION_CODE_USER_BLOCKED:
                case self::EXCEPTION_CODE_USER_INACTIVE:
                    $error_code = 1004;
                    break;

                case self::EXCEPTION_CODE_INSUFFICIENT_FUNDS:
                    $error_code = 1002;
                    break;

                case self::EXCEPTION_CODE_IDEMPOTENCY:
                case self::EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK:
                    break 2;

                default:
                    $error_code = 9999;
                    break;
            }
            $xml = "<cw type='fundTransferResp' bonusamt='0' err='{$error_code}' />";
            $this->response = simplexml_load_string($xml);
            return $this->response->asXML();
        }

        $balance_cents = $this->getPlayerBalance();
        $balance_units = $this->convertCoinage($balance_cents, self::COINAGE_CENTS, self::COINAGE_UNITS);
        $formatted_balance = number_format($balance_units, 2, '.', '');

        $xml = "<cw type='fundTransferResp' cur='{$this->user_identifier_currency}' amt='{$formatted_balance}' bonusamt='0' err='0'/>";
        $this->response = simplexml_load_string($xml);
        return $this->response->asXML();
    }

    /**
     * Returns the URL to the diamondbet file.
     * This method is called by CasinoProvider::getDepUrl.
     *
     * @param array $game
     * @param string $lang
     * @param string $device
     * @param bool $show_demo
     */
    public function getUrl($game, $lang = '', $device = '', $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $user = cu();
        $uid = null;
        if (!empty($user)) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
        }

        $url_params = [
            'game_ref' => $this->stripGamePrefix($game['ext_game_name']),
            'game_name' => $game['module_id'],
            'lang' => $lang,
            'device' => $device,
            'userid' => $uid,
        ];

        $url_params = array_filter($url_params);
        $launch_url = $this->launch_url . '?' . http_build_query($url_params);

        return $launch_url;
    }

    /**
     * Returns the iFrame URL to launch the game.
     *
     * Swinnt specifications indicate that we must send them a request to generate a user session on their side
     * then include this external session in the game launch URL.
     * We send our authentication credentials to Swintt in the player login request (create user session).
     * The subsequent requests which Swintt send us (GetBalance, Bet, Win etc) contain no authentication credentials
     * other than the user session. To authenticate their requests we therefore save this user session to cache
     * and match it against incoming requests.
     * Logs (db.trans_log) show the user and name of the request so we load the user and set game_provider_method_name for nicer logs.
     *
     * @param $user_identifier. A normal user ID (e.g. '5541343') or a Tournament user ID (e.g. '5541343e777777').
     * @param $game_ref
     * @param $game_name
     * @param $language
     */
    public function getGameLaunchUrl(string $user_identifier = null, string $game_ref = null, string $game_name = null, string $language = null)
    {
        $this->game_provider_method_name = __FUNCTION__;

        $device_id = (phive()->isMobile() === false) ? 0 : 1;
        $log_data = [
            'user_identifier' => $user_identifier,
            'game_ref' => $game_ref,
            'game_name' => $game_name,
            'language' => $language,
            'device_id' => $device_id,
        ];

        try {
            $this->loadUser($user_identifier);
            if (!$this->uid) {
                throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
            }
            $this->logInfo($log_data);

            $prefixed_game_ref = $this->addGamePrefix($game_ref);
            $this->validateGameIsEnabled($prefixed_game_ref, $device_id);

            $response = $this->postLogin();
            if (!$response) {
                throw new Exception("Error getting game token from game provider.");
            }
            $external_session = (string)$response['token'];
            $this->cacheExternalSession($external_session, $game_ref);
        } catch (Exception $e) {
            $log_key = sprintf("%s_res-error-%s", $this->getGameProviderName(), $this->game_provider_method_name);
            $this->logError(array_merge(['status' => 'error', 'message' => $e->getMessage(), 'debug' => $e->getFile() . '::' . $e->getLine()], $log_data), $log_key);
            return false;
        }

        $params = [
            'playerHandle' => $external_session,
            'gameId' => $this->stripGamePrefix($game_ref),
            'gameName' => $this->stripGamePrefix($game_name),
            'gameType' => 0,
            'gameSuite' => 0,
            'account' => $this->user_identifier_currency,
            'lang' => $this->user->getData('preferred_lang'),
            'brandedLoader' => 'swintt',
        ];
        $url = rtrim($this->getLicSetting('game_provider_game_url', $this->user), "/") . '?' . http_build_query($params);

        $log_key = sprintf("%s_%s", $this->getGameProviderName(), $this->game_provider_method_name);
        $this->logInfo(array_merge(['game_url' => $url], $log_data), $log_key);
        return $url;
    }

    /**
     * @throws Exception
     */
    protected function postLogin()
    {
        $user_prefix = $this->getLicSetting('user_id_prefix', $this->user);
        $root_partner = $this->getLicSetting('partner_id', $this->user);
        $sub_partner_id = $this->getLicSetting('sub_partner_id', $this->user);
        $tester = (int)$this->user->isTestAccount();
        $partner_id = $sub_partner_id ?: $root_partner;

        $sub_partner = !empty($sub_partner_id) ? '<partner partnerId="' . $sub_partner_id . '" partnerType="1" />' : '';
        $country = $this->user ? $this->user->getData('country') : "xx";
        $currency = $this->user_identifier_currency ?: "EUR";

        $xml = <<<EOS
<logindetail>
    <player
        account="{$currency}"
        country="{$country}"
        userName="{$this->user->getUsername()}"
        partnerId="{$partner_id}"
        tester="{$tester}"
        commonWallet="1"
    />
    <partners>
        <partner partnerId="zero" partnerType="0" />
        <partner
            partnerId="{$root_partner}"
            partnerType="1"
        />
        $sub_partner
    </partners>
</logindetail>
EOS;

        $user_identifier = $user_prefix . $this->user_identifier;
        $uri = "/cip/gametoken/{$user_identifier}";
        $response = $this->postTo($uri, $xml);
        return $response;
    }

    /**
     * @param string $uri
     * @param string $xml
     * @return SimpleXMLElement
     */
    protected function postTo(string $uri, string $xml)
    {
        $base_url = $this->getLicSetting('game_provider_api_url', $this->user);
        $full_url = sprintf("%s/%s", rtrim($base_url, "/"), ltrim($uri, "/"));
        $log_key = $this->getGamePrefix() . 'out';
        $response = null;
        $extra_headers = '';

        $response = phive()->post($full_url, $xml, 'application/xml', $extra_headers, $log_key, 'POST');
        return simplexml_load_string($response);
    }

    /**
     * Swintt creates an external session ID per player, so different Swintt games for the same player might have the same external session ID.
     * We add the game ID to the cache key so that when they send us the external session ID and game in their requests,
     * we can verify that it matches the expected game and user identifier.
     *
     * @param $external_session
     * @param $game_id
     */
    protected function cacheExternalSession($external_session, $game_id = null)
    {
        $key = sprintf("%s__%s", $this->getGameProviderName(), trim($external_session));
        phMset($key, $this->user_identifier);

        $key = sprintf("%s__%s__%s", $this->getGameProviderName(), trim($external_session), trim($game_id));
        phMset($key, $this->user_identifier);

        $log_key = sprintf("%s_%s", $this->getGameProviderName(), __FUNCTION__);
        $this->logDebug(['key' => $key, 'value' => $this->user_identifier], $log_key);
    }

    /**
     * Returns the user_identifier for the external session ID and optionally the game.
     *
     * @param $external_session
     * @param $game_id
     * @return string
     */
    protected function getCachedExternalSession($external_session, $game_id = null)
    {
        $key = sprintf("%s__%s", $this->getGameProviderName(), trim($external_session));
        if ($game_id) {
            $key .= '__' . trim($game_id);
        }

        $value = (string)phMget($key);
        $log_key = sprintf("%s_%s", $this->getGameProviderName(), __FUNCTION__);
        $this->logDebug(['key' => $key, 'value' => $value], $log_key);
        return $value;
    }

    /**
     * Trims the user prefix ("VS-") from the user identifier, if present.
     *
     * @param $user_identifier
     */
    protected function trimUserPrefix($user_identifier = null)
    {
        if ($user_identifier) {
            $user_prefix = $this->getLicSetting('user_id_prefix');
            if (strpos($user_identifier, $user_prefix) === 0) {
                return substr($user_identifier, strlen($user_prefix));
            }
        }
        return false;
    }

    /**
     * Simulates a call to Swintt Player Login in order to mock an external session ID.
     * This method should only be called by unit tests.
     *
     * @param string $user_identifier
     * @param $game_id
     * @return string The mocked external user session ID.
     */
    public function getMockExternalSession(string $user_identifier, $game_id = null)
    {
        $this->loadUser($user_identifier);
        $external_session = hexdec(uniqid());
        if ($game_id) {
            $game_id = $this->stripGamePrefix($game_id);
        }
        $this->cacheExternalSession($external_session, $game_id);
        return $external_session;
    }
}
