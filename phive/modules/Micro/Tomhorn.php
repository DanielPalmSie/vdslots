<?php

require_once __DIR__ . '/CasinoProvider.php';

class Tomhorn extends CasinoProvider
{
    protected $gp_name = __CLASS__;

    protected $receive_final_win = false;

    protected $confirm_win = true;

    protected $confirm_round = true;

    private $secret_key = '';

    private $partner_id = '';

    protected $map_gp_methods = [
        'GetBalance'            => 'balance',
        'Withdraw'              => 'bet',
        'Deposit'               => 'win',
        'RollbackTransaction'   => 'rollback',
        'freespinDeposit'       => 'freespinWin',
        'freespinWithdraw'      => 'freespinBet'
    ];

    const EXCEPTION_CODE_UNAUTHORIZED_INVALID_PARTNER_ID = 15001;
    const EXCEPTION_CODE_UNAUTHORIZED_INVALID_HASH = 15002;

    private $errors = [
        'ER03' => [
            'responsecode' => 401,
            'status' => 'Invalid Sign',
            'return' => 'default',
            'code' => 'ER03',
            'message' => 'Check if valid key was used and if the data was sent in a valid format.',
            'exception_code' => self::EXCEPTION_CODE_UNAUTHORIZED_INVALID_HASH,
        ],
        'ER05' => [
            'responsecode' => 200,
            'status' => 'Duplicate reference',
            'return' => 'default',
            'code' => 'ER05',
            'message' => 'Deposit or withdrawal with specified reference was already processed successfully.',
            'exception_code' => self::EXCEPTION_CODE_DUPLICATE_TRANSACTION,
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 'ER06',
            'message' => 'Insufficient funds available to complete the transaction.',
            'exception_code' => self::EXCEPTION_CODE_INSUFFICIENT_FUNDS,
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER08',
            'message' => 'Transaction with the specified reference hasn\'t ever been recorded.',
            'exception_code' => self::EXCEPTION_CODE_TRANSACTION_NOT_FOUND,
        ],
        'ER09' => [
            'responsecode' => 200,
            'status' => 'Identity not found',
            'return' => 'default',
            'code' => 'ER09',
            'message' => 'Cannot find specified identity.',
            'exception_code' => self::EXCEPTION_CODE_USER_NOT_FOUND,
        ],
        'ER18' => [
            'responsecode' => 200,
            'status' => 'Duplicate reference',
            'return' => 'default',
            'code' => 'ER18',
            'message' => 'Deposit or withdrawal with specified reference was already processed successfully.',
            'exception_code' => self::EXCEPTION_CODE_IDEMPOTENCY,
        ],
        'ER19' => [
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'ER19',
            'message' => 'Bet not found for this round.',
            'exception_code' => self::EXCEPTION_CODE_MATCHING_TRANSACTION_NOT_FOUND,
        ],
        'ER29' => [
            'responsecode' => 200,
            'status' => 'Duplicate reference',
            'return' => 'default',
            'code' => 'ER29',
            'message' => 'Specified transaction was already rolled back',
            'exception_code' => self::EXCEPTION_CODE_TRANSACTION_ALREADY_ROLLED_BACK,
        ],
        'ER32' => [
            'responsecode' => 200,
            'status' => 'DUPLICATE_GAME_ROUND_ID',
            'return' => 'default',
            'code' => 'ER32',
            'message' => 'Transaction with specified game round ID was already processed successfully.',
            'exception_code' => self::EXCEPTION_CODE_DUPLICATE_WIN_FOR_ROUND,
        ],
        'ER35' => [
            'responsecode' => 200,
            'status' => 'UNKNOWN_GAME_ROUND_ID',
            'return' => 'default',
            'code' => 'ER35',
            'message' => 'Unknown game round ID.',
        ],

        'ER50' => [
            'responsecode' => 200,
            'status' => 'Invalid Partner Id',
            'return' => 'default',
            'code' => 'ER50',
            'message' => 'Unknown partner or partner is disabled.',
            'exception_code' => self::EXCEPTION_CODE_UNAUTHORIZED_INVALID_PARTNER_ID,
        ],
    ];

    private $map_wallet_error_codes = [
        'ER01' => '1', // general error
        'ER33' => '1', // Invalid request parameter
        'ER34' => '1', // Invalid request parameter
        'ER35' => '1', // Unknown game round ID
        'ER36' => '1', // Unknown game round ID
        'ER10' => '2', // Game not found => Invalid request
        'ER11' => '2', // Invalid session => General error
        'ER16' => '2', // Invalid request
        'ER03' => '3', // SIGN/AUTH validity of sign
        'ER50' => '4', // invalid partner id
        'ER09' => '5', // Identity/player not found
        'ER06' => '6', // insufficient funds
        'ER29' => '9', // player already cancelled
        'ER05' => '11', // Duplicate reference/transaction
        'ER18' => '11', // transaction inserted successfully
        'ER31' => '11', // win present in round
        'ER32' => '11', // duplicate round id
        'ER08' => '12', // Unknown transaction / transaction not found
        'ER19' => '12', // Unknown transaction / transaction not found
    ];

    /**
     * Tomhorn constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->overruleErrors($this->errors);
    }

    /**
     * Returns the URL to the diamondbet file.
     *
     * @param array $game
     * @param string $lang
     * @param string $device
     * @param bool $show_demo
     * @return string|null
     */
    public function getUrl($game, $lang = '', $device = '', $show_demo = false)
    {
        $this->initCommonSettingsForUrl();

        $user = cu();

        if(!empty($user)) {
            $uid = empty($_SESSION['token_uid']) ? $user->getId() : $_SESSION['token_uid'];
        }

        $url_params = [
            'game_ref'  => $this->stripGamePrefix($game['ext_game_name']),
            'lang'      => $lang,
            'device'    => $device,
            'userid'    => $uid,
        ];

        $url_params = array_filter($url_params);

        $launch_url = $this->launch_url . '?' . http_build_query($url_params);

        return $launch_url;
    }

    /**
     * @param $regulator
     * @param $rcParams
     * @return array|mixed
     */
    public function addCustomRcParams($regulator, $rcParams)
    {
        $custom_rc_params = [
            'var:realitycheck_startduration'    => true,
            'var:realitycheck_interval'         => true,
            'var:realitycheck_historyurl'       => true,
            'var:realitycheck_exiturl'          => true,
        ];

        return array_merge($rcParams, $custom_rc_params);
    }

    /**
     * @return string[]
     */
    public function getMapRcParameters()
    {
        return [
            'var:realitycheck_startduration'    => 'rcElapsedTime',
            'var:realitycheck_interval'         => 'rcInterval',
            'var:realitycheck_historyurl'       => 'rcHistoryUrl',
            'var:realitycheck_exiturl'          => 'rcLobbyUrl'
        ];
    }

    /**
     * @param $rcParams
     * @return mixed
     */
    public function processRcParams($rcParams)
    {
        $rcParams['var:realitycheck_startduration'] = $rcParams['var:realitycheck_startduration'] * 60;
        $rcParams['var:realitycheck_exiturl']       = $this->wrapUrlInJsForRedirect($rcParams['var:realitycheck_exiturl']);
        $rcParams['var:realitycheck_historyurl']    = $this->wrapUrlInJsForRedirect($rcParams['var:realitycheck_historyurl']);
        $rcParams['var:realitycheck_interval']      = $rcParams['var:realitycheck_interval'] * 60;

        return $rcParams;
    }

    /**
     * @param $regulator
     * @param $rcParams
     * @return array|mixed
     */
    protected function mapRcParameters($regulator, $rcParams)
    {
        $mapping = [];

        $mapping = array_merge((array)$mapping, (array)$this->getMapRcParameters());

        $this->logDebug($mapping);

        $rcParams = phive()->mapit($mapping, $rcParams, [], false);

        $rcParams = (array)$this->processRcParams($rcParams);

        $this->logDebug('rc_params after mapit: ' . print_r($rcParams, true));

        return $rcParams;
    }

    /**
     * Overrides the base class method to add all methods and parameters which need to be executed for this request.
     * For example if the game provider sends a 'betAndWin' request then the child class should call
     *   $this->addWalletMethod('bet', ['user_id' => 1, 'amount' => 10,  'game_id' => 2560 ...]]);
     *   $this->addWalletMethod('win', ['user_id' => 1, 'amount' => 860, 'game_id' => 2560 ...]]);
     * This method should validate that required parameters exist.
     * Note that 'name', i.e. the user ID, can be either the real user ID (int) or the user ID suffixed with the tournament ID (string).
     *
     * @throws Exception If the request is invalid.
     */
    protected function addWalletMethods()
    {
        $wallet_method_name = $this->getWalletMethodName();
        $this->validateWalletMethodExists($wallet_method_name);

        $wallet_method_parameters = null;
        switch ($wallet_method_name) {
            case 'balance':
                $wallet_method_parameters = $this->getParametersForBalanceWalletMethod();
                break;

            case 'bet':
                $wallet_method_parameters = $this->getParametersForBetWalletMethod();
                break;

            case 'win':
                $wallet_method_parameters = $this->getParametersForWinWalletMethod();
                break;

            case 'rollback':
                $wallet_method_parameters = $this->getParametersForRollbackWalletMethod();
                break;
        }

        if ($wallet_method_parameters['amount'] ?? false) {
            $wallet_method_parameters['amount'] = $this->convertCoinage($wallet_method_parameters['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS);
        }
        $wallet_method_parameters['device'] = (int)(phive()->isMobile());

        $this->addWalletMethod($wallet_method_name, $wallet_method_parameters);
    }

    /**
     * Returns the wallet method name.
     *
     * @return false|string
     */
    protected function getWalletMethodName()
    {
        return $this->map_gp_methods[$this->getGameProviderMethodName()] ?? false;
    }

    /**
     * Validates the raw request and returns the parameters for the 'balance' wallet method.
     *
     * @return array The normalized parameter array.
     * @throws Exception Throws an exception is a required parameter is missing or empty.
     */
    protected function getParametersForBalanceWalletMethod(): array
    {
        $required = [
            'partnerID' => 'partner_id',
            'sign' => 'sign',
            'name' => 'user_id',
            'currency' => 'currency',
        ];

        foreach ($required as $k => $v) {
            if (empty($this->raw_request[$k] ?? false)) {
                throw new Exception("Missing request parameter: {$k}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
            $normalizedParameters[$v] = $this->raw_request[$k];
        }

        $optional = [
            'sessionID' => 'session_id',
            'gameModule' => 'game_id',
        ];

        foreach ($optional as $k => $v) {
            if (array_key_exists($k, $this->raw_request)) {
                $normalizedParameters[$v] = $this->raw_request[$k];
            }
        }

        return $normalizedParameters;
    }

    /**
     * Validates the raw request and returns the parameters for the 'bet' wallet method.
     *
     * @return array The normalized parameter array.
     * @throws Exception Throws an exception is a required parameter is missing or empty.
     */
    protected function getParametersForBetWalletMethod(): array
    {
        $required = [
            'partnerID' => 'partner_id',
            'sign' => 'sign',
            'name' => 'user_id',
            'sessionID' => 'session_id',
            'gameModule' => 'game_id',
            'gameRoundID' => 'round_id',
            'reference' => 'transaction_id',
            'amount' => 'amount',
            'currency' => 'currency',
        ];

        foreach ($required as $k => $v) {
            if (empty($this->raw_request[$k] ?? false)) {
                throw new Exception("Missing request parameter: {$k}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
            $normalizedParameters[$v] = $this->raw_request[$k];
        }

        $optional = [
            'fgbCampaignCode' => 'fgbCampaignCode',
        ];

        foreach ($optional as $k => $v) {
            if (array_key_exists($k, $this->raw_request)) {
                $normalizedParameters[$v] = $this->raw_request[$k];
            }
        }

        return $normalizedParameters;
    }

    /**
     * Validates the raw request and returns the parameters for the 'win' wallet method.
     *
     * @return array The normalized parameter array.
     * @throws Exception Throws an exception is a required parameter is missing or empty.
     */
    protected function getParametersForWinWalletMethod(): array
    {
        $required = [
            'partnerID' => 'partner_id',
            'sign' => 'sign',
            'name' => 'user_id',
            'sessionID' => 'session_id',
            'gameModule' => 'game_id',
            'gameRoundID' => 'round_id',
            'reference' => 'transaction_id',
            'amount' => 'amount',
            'currency' => 'currency',
        ];

        foreach ($required as $k => $v) {
            if (empty($this->raw_request[$k] ?? false)) {
                throw new Exception("Missing request parameter: {$k}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
            $normalizedParameters[$v] = $this->raw_request[$k];
        }

        $optional = [
            'type' => 'winType',
            'fgbCampaignCode' => 'fgbCampaignCode',
            'isRoundEnd' => 'isRoundEnd',
        ];

        foreach ($optional as $k => $v) {
            if (array_key_exists($k, $this->raw_request)) {
                $normalizedParameters[$v] = $this->raw_request[$k];
            }
        }

        return $normalizedParameters;
    }

    /**
     * Validates the raw request and returns the parameters for the 'rollback' wallet method.
     *
     * @return array The normalized parameter array.
     * @throws Exception Throws an exception is a required parameter is missing or empty.
     */
    protected function getParametersForRollbackWalletMethod(): array
    {
        $required = [
            'partnerID' => 'partner_id',
            'sign' => 'sign',
            'name' => 'user_id',
            'reference' => 'transaction_id',
        ];

        foreach ($required as $k => $v) {
            if (empty($this->raw_request[$k])) {
                throw new Exception("Missing request parameter: {$k}.", self::EXCEPTION_CODE_INVALID_REQUEST);
            }
            $normalizedParameters[$v] = $this->raw_request[$k];
        }

        $optional = [
            'sessionID' => 'session_id',
        ];

        foreach ($optional as $k => $v) {
            if (array_key_exists($k, $this->raw_request)) {
                $normalizedParameters[$v] = $this->raw_request[$k];
            }
        }

        return $normalizedParameters;
    }

    /**
     * Validates the authentication of the request.
     *
     * @throws Exception
     */
    protected function validateRequestAuthentication()
    {
        if (!$this->uid) {
            throw new Exception("User not found.", self::EXCEPTION_CODE_USER_NOT_FOUND);
        }

        $method_name = null;
        $parameters = $this->wallet_methods[0] ?? null;

        $license_setting = $this->getLicSetting('skinid', $this->uid);
        if (($parameters['partner_id'] ?? '') != $license_setting) {
            throw new Exception(
                "Invalid authorization credentials.",
                self::EXCEPTION_CODE_UNAUTHORIZED_INVALID_PARTNER_ID,
                new Exception("Invalid partner ID. User [{$this->uid}]. Expected [{$license_setting}] but received [{$parameters['partner_id']}].")
            );
        }

        $our_hash = $this->generateSignFromParams($this->raw_request, $this->uid);
        if (($parameters['sign'] ?? '') != $our_hash) {
            throw new Exception(
                "Invalid authorization credentials. Expected [{$our_hash}] but received [{$parameters['sign']}].",
                self::EXCEPTION_CODE_UNAUTHORIZED_INVALID_HASH,
                new Exception("Invalid hash sign. Expected [{$our_hash}] but received [{$parameters['sign']}].")
            );
        }
    }

    /**
     * Validates the parameters for the 'bet' method.
     *
     * @param $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateBet(&$parameters = null)
    {
        parent::validateBet($parameters);

        $this->validateUserGameSession($parameters);
    }

    /**
     * Validates that a session exists for the user and game.
     *
     * @param mixed $parameters
     * @throws Exception
     */
    protected function validateUserGameSession(&$parameters)
    {
        $external_session_id = $this->getExternalSession($parameters['user_id'], $parameters['game_id']);

        if (!$external_session_id) {
            throw new Exception(
                "Invalid session.",
                self::EXCEPTION_CODE_INVALID_SESSION,
                new Exception("Session not found for user [{$parameters['user_id']}] and game [{$parameters['game_id']}].")
            );
        }
        if ($external_session_id != $parameters['session_id']) {
            throw new Exception(
                "Invalid session.",
                self::EXCEPTION_CODE_INVALID_SESSION,
                new Exception(sprintf(
                    "Invalid external session ID for user [%s] and game [%s]. Expected [%s] but received [%s].",
                    $parameters['user_id'],
                    $parameters['game_id'],
                    $external_session_id,
                    $parameters['session_id']
                ))
            );
        }
    }

    /**
     * Validates the parameters for the 'win' method.
     * Overrides the base method so that the error message returned to the game provider indicates the missing parameters
     * with the parameter name from the original request.
     *
     * @param $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateWin(&$parameters = null)
    {
        parent::validateWin($parameters);

        $this->validateUserGameSession($parameters);
    }

    /**
     * Validates the parameters for the 'rollback' method.
     * Overrides the base method so that the error message returned to the game provider indicates the missing parameters
     * with the parameter name from the original request.
     *
     * @param $parameters The normalized parameter names and values.
     * @throws Exception
     */
    protected function validateRollback(&$parameters = null)
    {
        $this->setRollbackAmount($parameters);

        parent::validateRollback($parameters);
    }

    /**
     * The CasinoProvider implementation expects an 'amount' parameter and matches it against the transaction 'amount'.
     * For rollbacks TomHorn does not send an 'amount' parameter however, just the transaction ID, so we add the 'amount' parameter manually.
     *
     * @param $parameters
     */
    private function setRollbackAmount(&$parameters = null)
    {
        $transaction_id = $parameters['transaction_id'];
        $this->prefixTransaction($transaction_id);

        $transaction = $this->getExistingTransaction($transaction_id);
        if (empty($transaction)) {
            throw new Exception("Transaction not found.", self::EXCEPTION_CODE_TRANSACTION_NOT_FOUND);
        }

        $parameters['amount'] = $transaction['amount'] ?? 0;
    }

    /**
     * Returns a boolean indicating whether or not to verify if the player is blocked.
     * Typically we should not verify this for rollback actions.
     *
     * @return bool
     */
    protected function isEnabledVerifyIfPlayerIsBlocked(): bool
    {
        return in_array($this->getGameProviderMethodName(), ['Withdraw', 'Deposit']);
    }

    /**
     * Dynamically generates an authentication hash sign so we can compare it to the hash sign received in the request.
     * 'IsRoundEnd' is ignored because their docs specify it's not part of the list that's concatenated
     *
     * @param $params
     * @param mixed $uid
     * @return string
     */
    public function generateSignFromParams($params, $uid = null)
    {
        $message = '';
        $uid = $uid ?? $this->getUid();
        $secret_key = $this->getLicSetting('secret_key', $uid);
        $log = ['input' => []];

        foreach ($params as $key => $value) {
            if($key != 'sign' && $key != 'IsRoundEnd') {
                if ($key == 'amount') {
                    $value = $this->nf2TwoDec($value);
                }
                $message .= $value;
                $log['input'][$key] = $value;
            }
        }

        $hash = $this->getSign($secret_key, $message);

        $log['generated hash'] = $hash;
        $log_key = strtolower(get_class($this)) . '-authentication-hash-sign';
        $this->logDebug($log, $log_key);

        return $hash;
    }

    /**
     * Generates the response to send to the game provider.
     * If the request was successful then we return
     * [
     *  'Code' => 0,
     *  'Message' => '',
     * ]
     *
     * @param mixed $response
     * @return false|mixed|string|null
     */
    public function response($response)
    {
        if ($response === true) {

            $response_body = [
                'Code' => 0,
                'Message' => '',
            ];

            $ud = $this->user->getData();

            $game_provider_method_name = $this->getGameProviderMethodName();
            switch ($game_provider_method_name) {
                case 'GetBalance':
                    $balance = $this->getPlayerBalance();
                    $response_body['Balance'] = [
                        'Amount' => $this->convertCoinage($balance, self::COINAGE_CENTS, self::COINAGE_UNITS),
                        'Currency' => ($ud ? $this->getPlayCurrency($ud) : ciso()),
                    ];
                    break;
                case 'Withdraw':
                case 'Deposit':
                    $balance = $this->getPlayerBalance();
                    $response_body['Transaction'] = [
                        'Balance' => $this->convertCoinage($balance, self::COINAGE_CENTS, self::COINAGE_UNITS),
                        'Currency' => $ud ? $this->getPlayCurrency($ud) : ciso(),
                        'ID' => ($this->wallet_method == 'bet') ? $this->wallet_txn_bets : $this->wallet_txn_wins,
                    ];

                    if(!empty($this->getFreespins())) {
                        $response_body['Transaction']['ID'] = 0;
                    }
                    break;
                default:
                    break;
            }
        } else {
            if (!is_array($response)) {
                $response = [
                    'code' => 'ER01',
                    'message' => (string)$response,
                ];
            }
            $response_body = [
                'Code' => $this->map_wallet_error_codes[$response['code']] ?? 1,
                'Message' => $response['message']
            ];
        }

        $this->setResponseHeaders($response);
        $result = json_encode($response_body);

        return $result;
    }

    /**
     * We create the 'campaign' for the game so we can start applying freespins to the players
     * If we're creating the campaign we save the code in the ext_ids of the bonus_type for future reference
     * We apply the player to that campaign for that specific game
     *
     * @param $uid
     * @param $game_ids
     * @param $frb_granted
     * @param $bonus_name
     * @param $bonus_entry
     * @return mixed|void
     */
    public function awardFRBonus($uid, $game_ids, $frb_granted, $bonus_name, $bonus_entry)
    {
        $this->logDebug('parameters (($uid, $game_ids, $frb_granted, $bonus_name, $bonus_entry): '
            .$uid.' - '.$game_ids.' - '.$frb_granted.' - '.$bonus_name.' - '.print_r($bonus_entry, true));

        $campaign_code = '';

        // here we're checking if there's a campaign code for the player's license environment, there is at least one code in ext_ids
        if(!empty($game_ids)) {
            $campaign_codes = explode("|", $game_ids);
            $license_campaign_code = $this->getLicSetting('campaign_code', $uid);

            // we see if we have the license's campaignCode
            foreach ($campaign_codes as $key => $code) {
                if(strpos($code, $license_campaign_code) !== false) {
                    $campaign_code = $code;
                }
            }

            $this->logDebug(print_r($campaign_codes, true) . " " . $license_campaign_code . " " . $campaign_code);

            // this means that a campaign for this environment wasn't made yet and we need to append the new campaign code
            if(empty($campaign_code)) {
                $campaign_code = $this->createCampaignAndSaveCode($uid, $bonus_entry, $bonus_name);
            }

        } else { // here means that no campaign at all was made and we're starting from scratch
            $campaign_code = $this->createCampaignAndSaveCode($uid, $bonus_entry, $bonus_name, true);
        }

        // something went wrong
        if(empty($campaign_code)) {
            $this->logInfo($this->getError('ER01'));
            return false;
        }

        $player_campaign = $this->assignPlayerToCampaign($uid, $campaign_code);

        if($player_campaign['Code'] !== 0) {
            $this->logInfo($this->getError('ER01'));
            return false;
        }

        return true;
    }

    /**
     * This will get the existing bonus_type and either insert or update the ext_ids with campaign codes from the provider
     *
     * @param $uid
     * @param $bonus_entry
     * @param $bonus_name
     * @param bool $create
     * @return bool
     */
    private function createCampaignAndSaveCode($uid, $bonus_entry, $bonus_name, $create = false)
    {
        $bonus_type = phive('Bonuses')->getBonus($bonus_entry['bonus_id']);
        $bonus_game_id = $this->stripGamePrefix($bonus_type['game_id']);

        $campaign = $this->createCampaign($uid, $bonus_game_id, $bonus_name, $bonus_entry);

        if($campaign['Code'] !== 0) {
            return false;
        }

        $campaign_code = $campaign['Campaign']['Code'];

        $new_ext_ids = $bonus_type['ext_ids'] . '|' . $campaign_code;

        phive('SQL')->updateArray('bonus_types', ['ext_ids' => $new_ext_ids], ['id' => $bonus_type['id']]);

        return $campaign_code;
    }

    /**
     * Posts a 'GetIdentity' request to the game provider.
     *
     * @param mixed $uid  this can also be the user_id.tournament_id
     * @return array The json decoded response
     */
    protected function getGameProviderIdentity($uid)
    {
        $message = $this->partner_id . $uid;

        $params = [
            'partnerID' => $this->partner_id,
            'sign' => $this->getSign($this->secret_key, $message),
            'name' => $uid
        ];

        return $this->postTo($params, '/GetIdentity', $uid);
    }

    /**
     * Posts a 'CreateIdentity' request to the game provider.
     *
     * @param int|string $uid The user ID or the user_id.tournament_id
     * @return array The json decoded response
     */
    protected function createGameProviderIdentity($uid)
    {
        $user_data  = cu($uid)->getData();

        $partnerId      = $this->partner_id;
        $name           = $uid;
        $display_name   = $user_data['username'];
        $currency       = $this->isTournament($uid) ? phive('Tournament')->curIso() : $user_data['currency'];
        $password       = $name.'_videoslots';

        $key        = $this->secret_key;
        $message    = $partnerId.$name.$display_name.$currency.$password;

        $params = [
            'partnerID' => $partnerId,
            'sign' => $this->getSign($key, $message),
            'name' => $name,
            'displayName' => $display_name,
            'currency' => $currency,
            'parent' => '',
            'type' => '',
            'password' => $password,
            'details' => ''
        ];

        return $this->postTo($params, '/CreateIdentity', $uid);
    }

    /**
     * Posts a 'CreateSession' request to the game provider.
     * This will create a session on their end, check if one hasn't been cancelled and save the id in redis
     *
     * @param int|string $user_id User ID or user ID + tournament ID.
     * @param string $game_id
     * @return mixed The json decoded response
     */
    protected function createGameProviderSession($user_id, $game_id)
    {
        $partnerId = $this->partner_id;
        $key       = $this->secret_key;

        $response = $this->getGameProviderIdentity($user_id);
        if (!$this->isResponseOK($response)) {
            $response = $this->createGameProviderIdentity($user_id);
            if (!$this->isResponseOK($response)) {
                $this->logError("Aborting because game provider failed to create identity.");
                return false;
            }
        }

        $name = $response['Identity']['Name'];
        $message  = $partnerId . $name;

        $params = [
            'partnerID' => $partnerId,
            'sign' => $this->getSign($key, $message),
            'name' => $name
        ];

        $response = $this->postTo($params, '/CreateSession', $user_id);
        if (!is_array($response) || !array_key_exists('Code', $response)) {
            $this->logError([
                'success' => false,
                'message' => "Aborting because game provider failed to create identity.",
                'response' => $response,
            ]);
            return false;
        }

        $game_provider_response_code_session_still_active = 1005;
        if ($response['Code'] === $game_provider_response_code_session_still_active) {
            $close_session = $this->closeGameProviderSession($user_id, $response['Session']['ID']);
            if (!$this->isResponseOK($close_session)) {
                $this->logError([
                    'success' => false,
                    'message' => "Aborting because game provider failed to close session.",
                    'response' => $response,
                ]);
                return false;
            }
            $response = $this->createGameProviderSession($user_id, $game_id);
        }
        if (!$this->isResponseOK($response)) {
            $this->logError([
                'success' => false,
                'message' => "Aborting because game provider failed to create session.",
                'response' => $response,
            ]);
            return false;
        }

        $this->setExternalSessionId($response['Session']['ID'], $user_id, $game_id);

        return $response;
    }

    /**
     * Checks if the game provider response indicates success.
     *
     * @param $response
     * @return bool True if the game provider response indicates success.
     */
    protected function isResponseOK($response): bool
    {
        return is_array($response) && array_key_exists('Code', $response) && ($response['Code'] === 0);
    }

    /**
     * Posts a 'CloseSession' request to the game provider.
     *
     * @param $uid
     * @param $session_id
     * @return array The json decoded response
     */
    protected function closeGameProviderSession($uid, $session_id)
    {
        $partnerId  = $this->partner_id;

        $key        = $this->secret_key;
        $message    = $partnerId.$session_id;

        $params = [
            'partnerID' => $partnerId,
            'sign' => $this->getSign($key, $message),
            'sessionID' => $session_id
        ];

        return $this->postTo($params, '/CloseSession', $uid);
    }

    /**
     * Posts a 'GetSession' request to the game provider.
     *
     * @param int|string $user_id User ID or user ID + tournament ID.
     * @param string $external_session The external (game provider's) session ID.
     * @return array The json decoded response
     */
    protected function getGameProviderSession($user_id, $external_session)
    {
        $partnerId = $this->partner_id;
        $key = $this->secret_key;
        $message = $partnerId . $external_session;

        $params = [
            'partnerID' => $partnerId,
            'sign' => $this->getSign($key, $message),
            'sessionID' => $external_session
        ];

        return $this->postTo($params, '/GetSession', $user_id);
    }

    /**
     * Posts a 'GetPlayMoneyModuleInfo' request to the game provider.
     *
     * @param int|string $uid
     * @param string $game_id
     * @return array The json decoded response
     */
    protected function getGameProviderPlayMoneyModuleInfo($uid, string $game_id)
    {
        $message = ($this->partner_id . $game_id);

        $params = [
            'partnerID' => $this->partner_id,
            'sign' => $this->getSign($this->secret_key, $message),
            'module' => $game_id,
        ];
        return $this->postTo($params, '/GetPlayMoneyModuleInfo', $uid);
    }

    /**
     * Posts a 'GetModuleInfo' request to the game provider.
     *
     * @param $uid
     * @param string $game_id
     * @param string $game_provider_session
     * @return array The json decoded response
     */
    protected function getGameProviderModuleInfo($uid, $game_id, $game_provider_session)
    {
        $message = ($this->partner_id . $game_provider_session . $game_id);

        $params = [
            'partnerID' => $this->partner_id,
            'sign' => $this->getSign($this->secret_key, $message),
            'module' => $game_id,
            'sessionID' => $game_provider_session,
        ];
        return $this->postTo($params, '/GetModuleInfo', $uid);
    }

    /**
     * This is the method used on their end to call for the demoplay and real play games
     *
     * @param string $game_id
     * @param mixed $user_id The user ID, e.g. 5541343, or the user ID + tournament ID, e.g. 5541343e28278194
     * @return mixed
     */
    public function getGameModuleParams($game_id, $user_id)
    {
        $this->partner_id = $this->getLicSetting('partnerId', $this->getUsrId($user_id));
        $this->secret_key = $this->getLicSetting('secret_key', $this->getUsrId($user_id));
        $device_id = (int)(phive()->isMobile());

        if (!isLogged()) {
            $response = $this->getGameProviderPlayMoneyModuleInfo($user_id, $game_id);
            $this->logInfo([
                'success' => $this->isResponseOK($response),
                'user_id' => $user_id,
                'game_id' => $game_id,
                'response' => $response
            ], get_class($this) . "_game-launch");
            return $response;
        }

        try {
            $this->uid = $user_id;
            $this->validateGameIsEnabled($game_id, $device_id);
        } catch (Exception $e) {
            $this->logError($e->getMessage(), get_class($this) . "_game-launch-error");
            return false;
        }

        $external_session = $this->getExternalSession($user_id, $game_id);
        if ($external_session) {
            $session_response = $this->getGameProviderSession($user_id, $external_session);
            if (!$this->isResponseOK($session_response)) {
                $session_response = $this->createGameProviderSession($user_id, $game_id);
            }
        } else {
            $session_response = $this->createGameProviderSession($user_id, $game_id);
        }

        if (($session_response['Session']['State'] ?? null)  == 'Closed') {
            $session_response = $this->createGameProviderSession($user_id, $game_id);
        }

        if (!($session_response['Session']['ID'] ?? false)) {
            $this->logError("Game provider failed to create new session.", get_class($this) . "_game-launch-error");
            return false;
        }

        $module_response = $this->getGameProviderModuleInfo($user_id, $game_id, $session_response['Session']['ID']);
        if ($this->isResponseOK($module_response)) {
            $log = [
                'success' => true,
                'request' => [
                    'user_id' => $user_id,
                    'game_id' => $game_id,
                    'game provider session id' => $session_response['Session']['ID'],
                ],
                'response' => $module_response,
            ];
            $this->logInfo($log, get_class($this) . "_game-launch");
        } else {
            $log = [
                'success' => false,
                'request' => [
                    'user_id' => $user_id,
                    'game_id' => $game_id,
                    'game provider session id' => $session_response['Session']['ID'],
                ],
                'response' => $module_response,
            ];
            $this->logError($log, get_class($this) . "_game-launch-error");
        }
        return $module_response;
    }

    /*** Freespin functions ***/

    /**
     * @param $uid
     * @param $bonus_game_id
     * @param $bonus_name
     * @param $bonus_entry
     * @return bool|mixed
     */
    public function createCampaign($uid, $bonus_game_id, $bonus_name, $bonus_entry)
    {
        $ud                 = cu($uid)->getData();

        $partnerId          = $this->getLicSetting('partnerId', $uid);
        $campaign_name      = $bonus_name;
        $module             = $bonus_game_id;
        $currency           = $ud['currency'];
        $games_per_player   = $bonus_entry['frb_remaining'];

        $time_from          = phive()->hisNow($bonus_entry['start_time'], "Y-m-d\TH:i:s");
        $time_to            = phive()->hisNow($bonus_entry['end_time'], "Y-m-d\TH:i:s");

        $key                = $this->getLicSetting('secret_key', $uid);
        $message            = $partnerId.$campaign_name.$module.$currency.$games_per_player.$time_from.$time_to;

        $sign               = $this->getSign($key, $message);

        $params = [
            'partnerID' => $partnerId,
            'sign' => $sign,
            'campaignName' => $campaign_name,
            'module' => $module,
            'currency' => $currency,
            'gamesPerPlayer' => $games_per_player,
            'timeFrom' => $time_from,
            'timeTo' => $time_to
        ];

        return $this->postTo($params, '/CreateCampaign', $uid);
    }

    /**
     * @param $uid
     * @return bool|mixed
     */
    public function getCustomerCampaigns($uid)
    {
        $partnerId          = $this->getLicSetting('partnerId', $uid);
        $key                = $this->getLicSetting('secret_key', $uid);
        $type               = 'FreeGamesBonus';

        $message            = $partnerId.$type;

        $sign               = $this->getSign($key, $message);

        $params = [
            'partnerID' => $partnerId,
            'sign' => $sign,
            'type' => $type
        ];

        return $this->postTo($params, '/GetCustomerCampaigns', $uid);
    }

    /**
     * This method requires data received from either the create/get campaign
     *
     * @param $uid
     * @param $campaign_code
     * @return bool|mixed
     */
    public function assignPlayerToCampaign($uid, $campaign_code)
    {
        $ud                 = cu($uid)->getData();

        $partnerId          = $this->getLicSetting('partnerId', $uid);
        $players            = $uid;
        $currency           = $this->isTournament($uid) ? phive('Tournament')->curIso() : $ud['currency'];

        $key                = $this->getLicSetting('secret_key', $uid);
        $message            = $partnerId.$players.$campaign_code.$currency;

        $sign               = $this->getSign($key, $message);

        $params = [
            'partnerID' => $partnerId,
            'sign' => $sign,
            'players' => $players,
            'campaignCode' => $campaign_code,
            'currency' => $currency
        ];

        return $this->postTo($params, '/AssignPlayerToCampaign', $uid);
    }

    private function getSign($key, $message) {
        return strtoupper(hash_hmac('sha256', pack('A*', $message), pack('A*', $key)));
    }

    /**
     * Generic post method for all the requests we have to Tomhorn
     *
     * @param $params
     * @param $uri
     * @param $uid
     * @return bool|mixed
     */
    private function postTo($params, $uri, $uid)
    {
        $params_json = json_encode($params);

        $base_url = $this->getLicSetting('service_url', $uid);

        $url = $base_url . $uri;
        $debug_key = $this->getGamePrefix() . 'out';

        $response = null;
        try {
            $response = phive()->post($url, $params_json, $this->http_content_type, '', $debug_key, 'POST');
        } catch (\Throwable $e) {
            $this->logError([
                'exception' => $e->getMessage(),
                'uri' => $uri,
                'params' => $params_json,
            ]);
        }
        $decoded_response = json_decode($response, true);

        $log = [
            'success' => false,
            'url' => "POST: {$url}",
            'http content type' => $this->http_content_type,
            'request' => $params,
            'response' => $decoded_response,
        ];

        if ($this->isResponseOK($decoded_response)) {
            $log['success'] = true;
            $this->logDebug($log, get_class($this) . " " . debug_backtrace()[1]['function']);
        } else {
            $log = array_merge($log, [
                'success' => false,
                'raw request' => $params_json,
                'raw_response' => $response,
            ]);
            $this->logError($log, get_class($this) . " " . debug_backtrace()[1]['function']);
        }

        return $decoded_response;
    }

    /**
     * @param $user_id
     * @param $fgb_campaign_code
     * @param $game_id
     */
    public function setFreespinByFrbCampaignCode($user_id, $fgb_campaign_code, $game_id)
    {
        $columns = ['bt.ext_ids' => $fgb_campaign_code];

        $freespins = $this->getBonusEntryByColumns($user_id, $game_id, null, $columns);

        $this->setFreespins((array)$freespins);
    }
}