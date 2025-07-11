<?php

require_once __DIR__ . '/Gp.php';

class Evolution extends Gp
{
    // -Transaction Type: transactional (not trusted)
    //  OPENING/CLOSURE OF TRANSACTION CONSIDERS MATCH ON
    //  VERIFICATION OF REFID OF TRANSACTION BEING OPENED/CLOSED (DEBIT/CREDIT REQUEST)
    /*
            2.1. BET_DOES_NOT_EXIST
            2.2. BET_ALREADY_SETTLED (point «» or «b»)
            2.3. BET_ALREADY_SETTLED (point «» or «b»)
    */
    // - mixed (not gamewise)
    // Active session support type: TYPE1 - MULTIPLE SIMULTANEOUS ACTIVE SESSIONS PER USER ARE SUPPORTED.
    // Table ID dependency type: TYPE1 - OPERATOR CREATES INTERNAL DEPENDENCY ON TABLE ID SUPPLIED BY EVOLUTION


    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is
     * transaction ID)
     *
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the
     * same as bet set or Round Id is not an integer. to false otherwise true. Default true. Make sure that the round ID send by GP is an integer.
     * @var boolean
     */
    protected $_m_bByRoundId = false;

    /**
     * Does the GP keeps track and send the total winnings at the end of the free rounds or do they send free spin bets/win 1 by 1.
     * Default: null
     */
    protected $_m_bFrwSendPerBet = false;

    /**
     * Insert frb into the bet table so in case a frw comes in we can check if it has a matching frb
     * Most likely property $_m_bFrwSendPerBet should be set to true as well so each freespin can be confirmed against a valid freespin bet.
     * If the GP keeps track of the total winning and send us only 1 final request they probably dont send a freespin bet request fr it so
     * this property $_m_bConfirmFrbBet should be set to false
     * Must be true when frb can be canceled and frb_remaining needs to +1 again
     * @var bool
     */
    protected $_m_bConfirmFrbBet = false;

    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     *
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = true;

    /**
     * Check if incoming win request has a valid bet transaction.
     * Note: this is NOT for freespins but for real money play
     * @var bool
     */
    protected $_m_bConfirmBet = false;

    /**
     * Auto append the round ID (bets/wins:trans_id) to the transaction ID (bets/wins:mg_id) in the bets/wins table
     * in case the round ID as provided by GP (trans_id) is not an integer. This will allow us to find a bet by round ID
     * even if the round ID is not an integer. If true mg_id will look like <gp_prefix>_<txnID>#<roundID>.
     * If $this->_m_bByRoundId === true it will check inside mg_id instead of trans_id
     * Note: once the database bets/wins:trans_id can support varchar(100) this should always be false for all GP's
     * @var bool
     */
    protected $_m_bAutoAppendRoundIdToTxnId = true;

    /**
     * The header content type for the response to the GP
     *
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON;

    /**
     * Do we force respond with a 200 OK response to the GP
     *
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;

    private $_m_aMethodsMappedApi = [
        'balance' => '_balance',
        'debit' => '_bet',
        'credit' => '_win',
        'cancel' => '_cancel',
        //'sid' => '_end',
        'check' => '_init',
        'sid' => '_sid',
        'kasinospil' => 'createKasinoSpilSafeReport',
        'endofday' => 'createEndOfDaySafeReport',
        'openSession' => 'openSession',
        'closeSession' => 'closeSession',
        'promo_payout' => 'promoPayout'
    ];

    private $_m_sToken = '';
    private $_m_sUuId = '';
    private $_txn_length = 64;


    /**
     * Array with all possible response errors to GP
     * @example
     * 'ER05' => array (
     *   'responsecode' => 200, // used to send header to GP if not enforced 200 OK
     *   'status' => 'REJECTED', // used to send header to GP if not enforced 200 OK
     *   'return' => 'default', // if not string 'default' it will response this to GP
     *   'code' => 'ER01', // set this to whatever error-code the GP wants to receive
     *   'message' => 'Duplicate Transaction ID.' // set this to whatever error-message the GP wants to receive
     * )
     * @var array
     */
    private $_m_aErrors = [
        'ER01' => [
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => 'TEMPORARY_ERROR', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ],
        'ER03' => [
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => 'INVALID_TOKEN_ID',
            'message' => 'The authentication credentials are incorrect.'
        ],
        'ER04' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => true,
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction with same ID has been cancelled previously.'
        ],
        'ER05' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction with same ID does exist already.'
        ],
        'ER06' => [
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => 'INSUFFICIENT_FUNDS',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ],
        'ER08' => [
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'BET_DOES_NOT_EXIST',
            'message' => 'Transaction does not exist.'
        ],
        'ER09' => [
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'Player not found.'
        ],
        'ER10' => [
            'responsecode' => 200,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'Game is not active'
        ],
        'ER11' => [
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => 'INVALID_SID',
            'message' => 'Token not found.'
        ],
        'ER15' => [
            'responsecode' => 403,
            'status' => 'FORBIDDEN',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'IP Address forbidden.'
        ],
        'ER16' => [
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'Invalid request.'
        ],
        'ER19' => [
            'responsecode' => 200,
            'status' => 'STAKE_TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => 'BET_DOES_NOT_EXIST',
            'message' => 'Transaction does not exist.'
        ],
        'ER27' => [
            'responsecode' => 200,
            'status' => 'INVALID_PARAMETER',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'Session player ID doesn\'t match request Player ID.'
        ],
        'ER28' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_ALREADY_CANCELLED',
            'return' => 'default',
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction ID has been canceled already.'
        ],
        'ER35' => [
            'responsecode' => 200,
            'status' => 'ROUND_ALREADY_CANCELLED',
            'return' => true,
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Round ID has been canceled already.'
        ],
        'ER18' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WITH_SAME_ROUNDID',
            'return' => 'default',
            'code' => 'BET_ALREADY_EXIST',
            'message' => 'Transaction with same round ID does exist already.'
        ],
        'ER37' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => true,
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction with same ID has been cancelled previously.'
        ],
        'ER38' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_DUPLICATE',
            'return' => true,
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction with same ID does exist already.'
        ],
        'ER40' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WITH_SAME_ROUNDID',
            'return' => 'default',
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Transaction with same round ID does exist already.'
        ],
        'ER41' => [
            'responsecode' => 200,
            'status' => 'PLAYER_INACTIVE',
            'return' => 'default',
            'code' => 'ACCOUNT_LOCKED',
            'message' => 'Player has inactive status.'
        ],
        'ER42' => [
            'responsecode' => 200,
            'status' => 'TRANSACTION_WAS_CANCELLED',
            'return' => 'default',
            'code' => 'BET_ALREADY_SETTLED',
            'message' => 'Bet for this win request is cancelled already.'
        ],
        'ER43' => [
            'responsecode' => 200,
            'status' => 'FINAL_ERROR_ACTION_FAILED',
            'return' => 'default',
            'code' => 'FINAL_ERROR_ACTION_FAILED',
            'message' => 'The transaction been cancelled before'
        ],
        'ER44' => [
            'responsecode' => 200,
            'status' => 'INVALID_PARAMETER',
            'return' => 'default',
            'code' => 'INVALID_PARAMETER',
            'message' => 'Invalid promoPayout type.'
        ]
    ];

    /**
     * The name of the logger to use
     * @var string
     */
    protected string $logger_name = 'evolution';

    /**
     * Used on endpoint request if we should display error messages.
     * @var bool
     */
    private $display_api_error_messages = false;

    /**
     * Stores the session key
     * @var false|string
     */
    private $session_token;

    /**
     * Deposit/withdraw transaction amount in units
     * @var float
     */
    private $transaction_amount;

    /** Balance before win/bet, for debugging purposes
     * @var float
     */
    private $balance_before;

    /**
     * Current round, used for debugging purposes
     * @var int
     */
    private $transaction_id;

    /**
     * Promo payout request can be sent in different scenarios, for now only game payout related
     */
    private $_m_aPromoTransactionTypes = [
        'FromGame' => 4,
        'FreeRoundPlayableSpent' => 8,
        'JackpotWin' => 12,
        'RewardGameMinBetLimitReached' => 4,
        'RewardGameWinCapReached' => 4,
        'RewardGamePlayableSpent' => 4,
        'RtrMonetaryReward' => 4,
    ];

    /**
     * Set the defaults
     * Separate function so it can be called also from the classes that extend TestGp class
     *
     * @return Gp
     */
    public function setDefaults()
    {

        $this
            ->_mapGpMethods($this->_m_aMethodsMappedApi)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_checkDeclaredProperties()
            ->_setWalletActions();
        return $this;
    }

    /**
     * Returns the endpoint for Evolution Rewards API and replaces the placeholders in the URL.
     * The endpoints were defined in the property $this->_m_apiEndpointsFRB.
     *
     * @params string $key The key of the endpoint.
     * @params $user
     * @param array $context An array with data to replace in the API endpoint.
     *
     * Example: $context = [
     *   "/:campaignId/" => $campaign_id,
     *   "/:playerId/" => $user_id,
     * ];
     *
     * @return array|string|string[]
     */
    protected function getFreeSpinEndpoint(string $key, $user, array $context = [])
    {
        $base_url = $this->getLicSetting('frb_api_url', $user);
        $endpoint = $this->getLicSetting("frb_api_{$key}", $user);
        if (empty($base_url) || empty($endpoint) || empty($user)) {
            $this->logger->error(__METHOD__, [
                'user_id' => is_object($user) ? $user->getId() : $user['id'],
                'frb_api_url' => $base_url,
                "frb_api_{$key}" => $endpoint,
                'endpoint' => $endpoint,
                'context' => $context,
            ]);

            return false;
        }

        return preg_replace(
            array_keys($context),
            array_values($context),
            "{$base_url}{$endpoint}"
        );
    }

    /**
     * Returns the correct ext_ids of bonus according to user jurisdiction.
     *
     * The campaign id is stored in the database in the field ext_ids of the table bonus_types.
     *
     * Example:
     *   ext_ids => MT:2b5fddc1-66ff-45d3-a518-be0eaa89fcb2|GB:12sfddc1-66ff-45d3-a518-be0eaa89fc90
     *
     * This function will return the campaign according to user jurisdiction. For example, for MT user
     * it will return 2b5fddc1-66ff-45d3-a518-be0eaa89fcb2;
     *
     * @param array $bonus Bonus from table "bonus_types"
     * @param $user
     * @return mixed|string|false
     */
    protected function getCampaignIdFromBonus(array $bonus, $user)
    {
        $parsed_ext_ids = [];
        $ext_ids_countries = explode('|', $bonus['ext_ids']);

        foreach ($ext_ids_countries as $ext_id_country) {
            $ext_id_data = explode(':', $ext_id_country);
            $parsed_ext_ids[$ext_id_data[0]] = $ext_id_data[1];
        }

        $user_lic = phive('Licensed')->getLicCountryProvince($user);
        $user_lic = trim($user_lic, "-");
        $excluded_countries = explode(" ", $bonus['excluded_countries']);

        if (in_array($user_lic, $excluded_countries)) {
            return false;
        }

        if(!in_array($user_lic, phive('Licensed')->getSetting('licensed_countries'))) {
            $user_lic = 'ROW';
        }

        return $parsed_ext_ids[$user_lic] ?? false;
    }

    /**
     * Generic method to call Evolution API v3 using basic authentication
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param string $idempotency
     * @return mixed
     */
    private function apiRequest($method = 'POST', string $url, array $params, string $idempotency = '')
    {
        $api_pwd = $this->getSetting('token');
        $api_user = $this->getLicSetting('casinokey', $this->user);
        $headers[] = 'Authorization: Basic ' . base64_encode("{$api_user}:{$api_pwd}");
        if (!empty($idempotency)) {
            $headers[] = "Idempotency-Key: $idempotency";
        }

        $response = phive()->post(
            $url,
            json_encode($params),
            'application/json',
            $headers,
            "evolution_api_request",
            $method
        );

        return json_decode($response, true);
    }

    /**
     * Will be called from CasinoBonuses::addFreeSpin().
     * It is triggered by the user when he clicks on "My prizes" on his account.
     *
     * This method is being used to create the voucher in the Evolution BO.
     */
    public function awardFRBonus($p_iUserId, $p_sGameIds, $p_iFrbGranted, $bonus_name, $p_aBonusEntry)
    {
        $user = cu($p_iUserId);
        $bonus = phive('Bonuses')->getBonus($p_aBonusEntry['bonus_id']);
        $campaign_id = $this->getCampaignIdFromBonus($bonus, $user);

        $api_url = $this->getFreeSpinEndpoint('issue_voucher', $user, ['/:campaignId/' => $campaign_id]);
        if (!$api_url || !$campaign_id) {
            $this->logger->error(__METHOD__, [
                'user' => uid(),
                'api_url' => $api_url,
                'campaign_id' => $campaign_id,
                'bonus_entries_id' => $p_aBonusEntry['id'],
                'bonus_type_id' => $p_aBonusEntry['bonus_id'],
                'license' => phive('Licensed')->getLicCountryProvince($user),
                'mesage' => 'error generating the api url to issue the voucher',
            ]);
            return 'fail';
        }

        $idempotency_key = $this->getHash($p_aBonusEntry['id']);
        $params = [
            "playerId" => $user->getId(),
            "currency" => $user->getCurrency(),
            "settings" => [
                "\$type" => "freeRounds",
                "freeRoundsCount" => $bonus['reward'],
            ],
        ];

        $response = $this->apiRequest('POST', $api_url, $params, $idempotency_key);
        if ($response['state'] === 'Active') {
            return $this->getTransactionPrefix() . $response['pk']['voucherId'];
        }

        $this->logger->error(__METHOD__, [
            'user' => uid(),
            'api_url' => $api_url,
            'request' => $params,
            'response' => $response,
            'bonus_entries_id' => $p_aBonusEntry['id'],
            'bonus_type_id' => $p_aBonusEntry['bonus_id'],
            'mesage' => 'error creating the voucher in Evolution BO',
        ]);

        return 'fail';
    }

    /**
     * Will be called from CasinoBonuses::fail()
     */
    public function cancelFRBonus($user_id, $bonus_entry_ext_id)
    {
        $user = cu($user_id);
        $this->user = $user;
        $api_url = $this->getFreeSpinEndpoint('close_voucher', $user, [
            '/:reason/' => 'Forfeited',
            '/:playerId/' => $user->getId(),
            '/:voucherId/' => str_replace($this->getTransactionPrefix(), '', $bonus_entry_ext_id),
        ]);

        if (!$api_url) {
            $this->logger->error('Evolution::frb Error generating the api url to close voucher', [
                'user' => uid(),
                'bonus_entry_ext_id' => $bonus_entry_ext_id,
            ]);
            return;
        }

        $this->apiRequest('PUT', $api_url, []);
    }

    /**
     * Pre process data received from GP
     *
     * @return object
     */
    public function preProcess()
    {
        $this->setDefaults();

        $fileGetContent = $this->_m_sInputStream;
        $params = (empty($fileGetContent) ? $_REQUEST : $fileGetContent);
        $oData = (empty($fileGetContent) ? json_decode(json_encode($params), false) : json_decode($params, false));

        $this->_setGpParams($oData);

        if ($oData === null) {
            // request is unknown
            $this->_logIt([__METHOD__, 'unknown request']);
            $this->_response($this->_getError(self::ER16));
            die();
        }

        $aJson = $aAction = [];

        // Define which service is requested
        $getInput = filter_input_array(INPUT_GET, [
            'action' => FILTER_SANITIZE_ENCODED,
            'token' => FILTER_SANITIZE_ENCODED
        ]);
        $casinoMethod = isset($getInput['action']) ? $getInput['action'] : '';

        if (empty($casinoMethod)) {
            // method to execute not found
            $this->_response($this->_getError(self::ER16));
            $this->_logIt([
                __METHOD__,
                'Evolution method not found',
                $casinoMethod,
                print_r($_GET, true),
                print_r($_REQUEST, true)
            ]);
            die();
        } else {
            $this->_setGpMethod($casinoMethod);
        }

        $mSessionData = null;

        if (!empty($oData->sid)) {
            $this->_m_sToken = $this->session_token = $oData->sid;
            $mSessionData = $this->fromSession($this->session_token);
            $this->_logIt([__METHOD__, 'Check session: ' . $oData->sid, print_r($mSessionData, true)]);
        }

        if (isset($oData->currency)) {
            $aJson['currency'] = $oData->currency;
        }

        if (isset($oData->uuid)) {
            $this->_m_sUuId = $oData->uuid;
        }

        if (isset($_GET['authToken'])) {
            $aJson['token'] = $_GET['authToken'];
        }

        // SAFE reports
        if (in_array($casinoMethod, ['kasinospil', 'endofday'])) {
            $aJson['state'] = 'single';
            $aJson['action']['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aJson['action']['parameters'] = (array)$oData;

            $this->_m_oRequest = json_decode(json_encode($aJson), false);
            $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true)]);

            return $this;
        }

        if (isset($mSessionData->userid)) {
            $aJson['playerid'] = $mSessionData->userid;

            $oData->userId = $this->getUsrId($oData->userId);
            if (isset($oData->userId) && (int)$oData->userId !== (int)$aJson['playerid']) {
                $this->logger->error(__METHOD__, [
                  'method' => $casinoMethod,
                  'request' => $oData,
                  'session_data' => $mSessionData
                ]);

                $this->_response($this->_getError(self::ER27));
            }
        } elseif (isset($oData->userId) || $casinoMethod == 'sid') {
            $aJson['playerid'] = $oData->userId;
        } else {
            $this->_logIt([__METHOD__, 'UID not found.']);
            $this->_response($this->_getError(self::ER09));
        }

        if (isset($oData->game->details->table->id)) {
            $aJson['skinid'] = $oData->game->details->table->id;
            $this->_logIt([__METHOD__, 'GID by $oData->game->details->table->id', $oData->game->details->table->id]);
            // $aJson['skinid'] = $oData->game->type;
            // $this->_logIt([__METHOD__, 'GID by $oData->game->type', $oData->game->type]);
        } else {
            if (!empty($mSessionData->gameid)) {
                $aJson['skinid'] = $mSessionData->gameid;
            } else {
                $this->_logIt([__METHOD__, 'GID not found']); // not an error in check metchod
            }
        }

        if (in_array($casinoMethod, ['debit', 'credit', 'cancel'])) {
            $aJson['state'] = 'single';
            $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
            $aAction[0]['parameters'] = [
                'transactionid' => $oData->transaction->id,
                'roundid' => $oData->transaction->refId
            ];
            $this->transaction_id = $oData->transaction->refId;
            if ($casinoMethod !== 'cancel') {
                // page 79 exclude amount check from cancel requests
                $aAction[0]['parameters']['amount'] = $this->convertFromToCoinage($oData->transaction->amount,
                    self::COINAGE_UNITS, self::COINAGE_CENTS);
                $this->transaction_amount = $aAction[0]['parameters']['amount'];
            }
            $aJson['action'] = $aAction[0];
        } else {
            if ($casinoMethod === 'promo_payout') {
                $aJson['state'] = 'single';
                $aAction[0]['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aAction[0]['parameters'] = [
                    'userid' => $oData->userId,
                    'transactionid' => $oData->promoTransaction->id,
                    'type' => $oData->promoTransaction->type,
                    'amount' => $this->convertFromToCoinage($oData->promoTransaction->amount, self::COINAGE_UNITS,
                        self::COINAGE_CENTS)
                ];
                $aJson['action'] = $aAction[0];
                $this->transaction_amount = $aAction[0]['parameters']['amount'];
                $this->transaction_id = $aAction[0]['parameters']['transactionid'];

                $transaction_type = $oData->promoTransaction->type ?? '';
                if ($transaction_type === 'FreeRoundPlayableSpent') { // Received at the end of the free spins rounds
                    $aJson['freespin']['id'] = "{$this->getTransactionPrefix()}{$oData->promoTransaction->voucherId}";
                }
            } else {
                $aAction['command'] = $this->getWalletMethodByGpMethod($casinoMethod);
                $aJson['state'] = 'single';
                $aJson['action'] = $aAction;
            }
        }

        // Evolution has no freespins
        //print_r($aJson);die;
        $this->_m_oRequest = json_decode(json_encode($aJson), false);
        $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true)]);

        return $this;
    }


    /**
     * Set the current user data and external session
     * @param $sMethod
     */
    public function setUserData($sMethod)
    {
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
            $this->setExternalSession($sMethod);
        }
    }

    /**
     * Set the external game session data
     *
     * @param $sMethod
     */
    public function setExternalSession($sMethod)
    {
        if ($this->getLicSetting('send_external_session')) {
            $ud = $this->_getUserData();
            if (lic('hasGameplayWithSessionBalance', [], $ud) === true) {
                $session_data = $this->fromSession($this->session_token);
                $token = $session_data->ext_session_id ?? $this->_m_sToken;
                $this->setExternalSessionByToken($ud, $token); // Not 100% sure we can bind to transactionRefId

            }
        } else {
            $user = cu($this->_getUserData());
            if (!empty($this->_m_sToken) && $sMethod !== '_init' && lic('hasGameplayWithSessionBalance', [],
                    $user) === true) {
                $this->setExternalSessionByToken($user, $this->_m_sToken);
            }
        }
    }

    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {
        $mResponse = false;

        $this->logger->debug(__METHOD__, [
            'request' => $this->_m_oRequest,
        ]);

        if ($this->_m_oRequest->token === $this->getSetting('tokenwallet')) {
            // check if the commands requested do exist
            $this->_setActions();

            if (isset($this->_m_oRequest->freespin)) {
                $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id, 'ext_id');
                if ($this->_isFreespin()) {
                    $this->_m_oRequest->freespin->id = $this->_getFreespinData('id');
                    $this->_m_oRequest->skinid = $this->stripPrefix($this->_getFreespinData('game_id'));
                } else {
                    $this->_response($this->_getError(self::ER17));
                }
            }

            // Set the game data by the received skinid (is gameid)
            if (isset($this->_m_oRequest->skinid)) {
                $this->_setGameData();
                if (!$this->isCurrentGameActive()) {
                    $mResponse = $this->_getError(self::ER10);
                    $this->_response($mResponse);
                }
            }

            // execute all commands
            foreach ($this->_getActions() as $key => $oAction) {
                $sMethod = $oAction->command;
                // Update the user data before each command
                $this->setUserData($sMethod);

                $this->_setWalletMethod($sMethod);

                // command call return either an array with errors or true on success
                if (property_exists($oAction, 'parameters')) {
                    if (in_array($sMethod, ['_win', '_bet', 'promoPayout'])) {
                        $this->acquireLock('evolution' . $sMethod, $this->_m_oRequest->playerid);
                        $this->balance_before = $this->_getBalance();// refresh user data (wrong balance problem)
                    }
                    $mResponse = $this->$sMethod($oAction->parameters);
                } else {
                    $mResponse = $this->$sMethod();
                }
                if ($mResponse !== true) {
                    $this->logger->error(__METHOD__, [
                        'resp' => $mResponse,
                    ]);
                    break;
                }
                $this->logger->debug(__METHOD__, [
                    'resp' => $mResponse,
                ]);
            }
        } else {
            // auth token not valid
            $mResponse = $this->_getError(self::ER03);
        }

        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
    }

    protected function _response($p_mResponse)
    {
        $aResp = [];
        $aResp['uuid'] = $this->_m_sUuId;
        //$aResp['retransmission'] = false;
        $s_method = $this->getGpMethod();
        if ($p_mResponse === true) {
            $aResp['status'] = 'OK';
            switch ($s_method) {
                case 'sid':
                    $aResp['sid'] = $this->_sid(true);
                case 'check':
                    break;
                case 'debit':
                case 'credit':
                case 'promo_payout':
                    $balance = $this->_getBalance();
                    $aResp['balance'] = $this->convertFromToCoinage($balance, self::COINAGE_CENTS, self::COINAGE_UNITS,
                        2);
                    $aResp['bonus'] = 0.00;
                    $this->releaseLock('evolution' . $this->_m_aMethodsMappedApi[$s_method],
                        $this->_m_oRequest->playerid);
                    $this->dumpTst('evolution-locks', [
                        'method' => $this->_m_aMethodsMappedApi[$s_method],
                        'transaction' => $this->transaction_id,
                        'amount' => $this->transaction_amount,
                        'balance_before' => $this->balance_before,
                        'balance_after' => $balance,
                        'is_balance_diff_ok' => ($this->balance_before + ($s_method == 'debit' ? -1 : 1) * $this->transaction_amount) == $balance
                    ]);

                    break;
                case 'balance':
                case 'cancel':
                    $balance = $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS,
                        self::COINAGE_UNITS, 2);
                    $aResp['balance'] = $balance;
                    $aResp['bonus'] = 0.00;
                $this->logger->debug("evolution_balance", [
                    'normal_balance' => $this->_getBalance(),
                    'total_balance' => $this->_getBalance([],[],true),
                    'uuid' => $aResp['uuid']
                ]);
                    break;
                case 'openSession':
                    $aResp = array_merge($aResp, $this->lic($this->getLicCountry(), 'openSessionResponse'));
                    $this->logger->debug(__METHOD__, [
                        'method' => $s_method,
                        'resp' => $aResp,
                    ]);
                    break;
                default:
                    break;
            }
            if ($s_method == 'credit') {
                $this->checkNextRound($this->_getUserData());
            }
        } else {
            $aResp['status'] = $p_mResponse['code'];
            if (remIp() === '127.0.0.1' || $this->display_api_error_messages) {
                $aResp['message'] = $p_mResponse['message'];
            }
        }

        $this->_setResponseHeaders($p_mResponse);

        $result = json_encode($aResp);
        $this->_logIt([__METHOD__, print_r($this->_m_oRequest, true), print_r($p_mResponse, true), $result]);
        $this->logger->debug(__METHOD__, [
            'response' => $p_mResponse,
            'result' => $result,
        ]);
        echo $result;
        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }

    /**
     * Get the url of the script that launches the game
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $mgs = phive('MicroGames');
        $desktopOrMobile = $p_sTarget == 'desktop' ? 0 : 1;

        if (!phive()->isMobile() || $mgs->gameInIframe($mgs->getByGameId("evolution_" . $p_mGameId,
                $desktopOrMobile))) {
            $url = phive()->getSiteUrl() . "/diamondbet/evolution.php?game_id=$p_mGameId&lang=$p_sLang&target=$p_sTarget";
        } else {
            $url = $this->getIframeUrl($p_mGameId, $p_sLang, $p_sTarget);
        }

        if (!empty($_SESSION['token_uid'])) {
            $url .= "&mp_id={$_SESSION['token_uid']}";
        }

        return $url;

    }

    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @return string The url to open the game
     */
    public function getIframeUrl($p_mGameId, $p_sLang = '', $p_sTarget = '')
    {
        $this->initCommonSettingsForUrl();

        $user = cu();

        $token = '';

        if (!empty($user)) {
            $uid = $user->getId();
            $ud = $user->getData();
            $token = $this->getGuidv4($uid);
        }

        if (!empty($_REQUEST['mp_id'])) {
            $this->setUserLaunchedGame($_REQUEST['mp_id']);
        }

        $this->_m_oRequest->skinid = $p_mGameId;
        $this->_setGameData();
        $aGameData = $this->_getGameData();

        $sGameTableId = $this->getGameTableId($aGameData['game_id']);
        $sGameCategory = $aGameData['module_id'];
        $sGameInterface = $this->getGameInterface($sGameCategory);
        $this->_logIt([
            __METHOD__,
            'gameid:' . $aGameData['game_id'],
            'tableID: ' . $sGameTableId,
            ' category: ' . $sGameCategory
        ]);
        $aPost = [];
        $aPost['uuid'] = $this->getGuidv4();
        $aPost['config'] = [
            'brand' => [
                'id' => $this->getLicSetting('brandid', $uid),
                'skin' => $this->getLicSetting('skinid', $uid)
            ],
            'game' => [
                'category' => $sGameCategory,
                'interface' => $sGameInterface,
                'table' => ['id' => $sGameTableId],
            ],
            'channel' => [
                'wrapped' => false,
                'mobile' => (($p_sTarget === 'mobile') ? true : false)
            ],
            'urls' => [
                'cashier' => $this->getCashierUrl(false, $p_sLang, $p_sTarget),
                'responsibleGaming' => phive('Licensed')->getRespGamingUrl($user, $p_sLang),
                'lobby' => $this->getLobbyUrl(false, $p_sLang, $p_sTarget)
            ],
        ];

        // if game category is blackjack clear table id config  as We want to go to the blackjack lobby
        if ($sGameCategory == 'blackjack') {
            unset($aPost['config']['game']['table']);
        }

        // Evolution doesn't have demo mode
        if (isLogged()) {
            $aPost['player'] = [
                'id' => $uid,
                'update' => true,
                'firstName' => 'ABC', // placeholder to not break functionality, because of GDPR
                'lastName' => 'DEF', // placeholder to not break functionality, because of GDPR
                'country' => 'MT', // placeholder to not break functionality, because of GDPR
                'language' => $p_sLang,
                'currency' => $ud['currency'],
                'session' => [
                    'id' => $token,
                    'ip' => '213.165.171.122'
                    // this is an malta DNS, placeholder to not break functionality, because of GDPR
                ],
            ];

            $maxBet = phive('Gpr')->getMaxBetLimit(cu($user));
            if (!empty($maxBet)) {
                $aPost['player']['maxBet'] = $maxBet;
            }

            if ($this->isTournamentMode()) {
                $aPost['player']['id'] = $_REQUEST['mp_id'];
                $aPost['player']['currency'] = $this->getPlayCurrency($ud, $this->t_eid);
                $aPost['player']['country'] = $this->getLicSetting('bos-country', $uid);
            }

            // for RC check, GP will autoshow popup to player
            if (in_array($ud['country'], ['GB', 'MT'])) {
                $aPost['config']['brand']['skin'] = $this->getLicSetting('skinid', $uid);
            }
        }

        $url = $this->launch_url;

        $result = phive()->post(
            $url,
            json_encode($aPost),
            Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON,
            '',
            $this->getGamePrefix() . 'out',
            'POST'
        );

        $oData = json_decode($result, false);
        $this->_logIt([__METHOD__, print_r($result, true), print_r($oData, true)]);
        if (isset($oData->entry)) {
            $this->toSession($token, $ud['id'], $p_mGameId, $p_sTarget);
            if ($this->getLicSetting('send_external_session') !== true) {
                lic('initGameSessionWithBalance', [$user, $token, $aGameData], $user);
            }
            $this->_logIt([__METHOD__, $oData->entry, print_r($aPost, true)]);
            return $this->getLicSetting('hostname', $uid) . $oData->entry;
        } elseif (isset($oData->errors)) {
            $this->_logIt([__METHOD__, 'launchurl3: ' . $url, print_r($aPost, true)]);
            return false;
        }
        return false;
    }

    /**
     * Returns a new session token
     * This function should only be available at test environments for testing purpose
     *
     * @param bool $get
     * @return bool|string
     */
    private function _sid($get = false)
    {
        if ($this->getSetting('test') === true) {
            $token = $this->getGuidv4($this->_m_oRequest->playerid);
            $this->toSession($token, $this->_m_oRequest->playerid, '');
            return $get ? $token : true;
        }
        return false;
    }

    function getGameTableId($game_id)
    {
        $parts = explode('_', $game_id);
        return $parts[1];
    }

    public function getGameInterface($category = '')
    {
        $interfaceMapping = [
            'csp' => 'hd1',
            'rng-blackjack' => 'hd1',
            'rng-roulette' => 'hd1'
        ];
        return $interfaceMapping[$category] ?? 'view2';
    }

    /**
     * Create a SAFE (DK) report. Make the request on SAFE to create a new KasinoSpil report.
     *
     * @param stdClass $parameters
     * @return array
     * @throws Exception
     */
    private function createKasinoSpilSafeReport(stdClass $parameters)
    {
        $game_session = $this->mapGameSessionForEvolution($parameters->game_session, $parameters->game_id);
        if ($game_session['error'] === true) {
            $result = $game_session['message'];
        } else {
            $result = lics('customEvery5Min', [
                'game_session' => $game_session['data'],
                'report_key' => 'KasinoSpil_evolution'
            ]);

            $result = !empty($result[0]) ? $result[0] : false;
        }

        if ($result !== true) {
            $this->display_api_error_messages = true;

            return [
                'code' => 400,
                'message' => (is_string($result)) ? $result : 'Report could not be inserted'
            ];
        }

        return $result;
    }

    /**
     * Create the needed game session structure from the data sent from Evolution. Used by SAFE as a strategy pattern.
     *
     * @param $game_session_data
     * @param $game_id
     * @return array|string
     * @throws Exception
     */
    private function mapGameSessionForEvolution($game_session_data, $game_id)
    {
        try {
            $game_session_data = json_decode($game_session_data);
        } catch (Exception $exception) {
            return ['error' => true, 'message' => 'Invalid data'];
        }

        if (!$game_id) {
            return ['error' => true, 'message' => 'Game id is required'];
        }

        $game_id = $this->getGamePrefix() . $game_id;
        $game = phive('MicroGames')->getByGameRef($game_id);
        if (!$game) {
            return ['error' => true, 'message' => 'Game doesn\'t exist'];
        }

        $required_data = [
            'playerId',
            'betAmount',
            'depositAmount',
            'roundCount',
            'channel',
            'startDate',
            'endDate',
            'rngIdentifiers'
        ];

        $are_data_ok = $this->validateRequestObject($required_data, $game_session_data);
        if ($are_data_ok !== true) {
            return ['error' => true, 'message' => $are_data_ok];
        }

        return [
            'error' => false,
            'data' => [
                'id' => phive()->uuid(),
                'user_id' => $game_session_data->playerId,
                'bet_amount' => (int)$game_session_data->betAmount / 10000,
                'win_amount' => (int)$game_session_data->depositAmount / 10000,
                'bet_cnt' => $game_session_data->roundCount,
                'channel' => $game_session_data->channel,
                'start_time' => (new DateTime($game_session_data->startDate))->format('Y-m-d H:i:s'),
                'end_time' => (new DateTime($game_session_data->endDate))->format('Y-m-d H:i:s'),
                'game_ref' => $game_id,
                'rng_version' => $game_session_data->rngIdentifiers[0]->rngUniqueId,
                'game_version' => $game_session_data->rngIdentifiers[0]->rngUniqueId,
            ]
        ];
    }

    /**
     * SAFE (DK) EndOfDay report. Make the request on SAFE to insert data for EndOfDay report.
     *
     * @param stdClass $parameters
     * @return array
     * @throws Exception
     */
    private function createEndOfDaySafeReport(stdClass $parameters)
    {
        $end_of_day_data = $this->mapEndOfDayDataForEvolution($parameters->data);
        if ($end_of_day_data['error'] === true) {
            $result = $end_of_day_data['message'];
        } else {
            $result = lics('customEndOfDayReports', [
                'end_of_day_data' => $end_of_day_data['data'],
                'report_key' => 'EndOfDay_evolution'
            ]);

            $result = !empty($result[0]) ? $result[0] : null;
        }

        if ($result !== true) {
            $this->display_api_error_messages = true;

            return [
                'code' => 400,
                'message' => (is_string($result)) ? $result : 'Report could not be inserted'
            ];
        }

        return $result;
    }

    /**
     * Create the required data for report of end of day for evolution. Used by SAFE as a strategy pattern.
     *
     * @param $end_of_day_data
     * @return array
     * @throws Exception
     */
    private function mapEndOfDayDataForEvolution($end_of_day_data)
    {
        try {
            $end_of_day_data = json_decode($end_of_day_data);
        } catch (Exception $exception) {
            return ['error' => true, 'message' => 'Invalid data'];
        }

        $required_data = ['betAmount', 'depositAmount', 'roundCount', 'date'];

        $are_data_ok = $this->validateRequestObject($required_data, $end_of_day_data);
        if ($are_data_ok !== true) {
            return ['error' => true, 'message' => $are_data_ok];
        }

        return [
            'error' => false,
            "data" => [
                'bet_amount' => (int)$end_of_day_data->betAmount / 10000,
                'win_amount' => (int)$end_of_day_data->depositAmount / 10000,
                'EndOfDayRapportAntalSpil' => $end_of_day_data->roundCount,
                'date' => (new DateTime($end_of_day_data->date))->format('Y-m-d'),
            ]
        ];
    }

    /**
     * Check if the object is missing required instances.
     *
     * @param $required_data
     * @param $request
     * @return bool|string
     */
    private function validateRequestObject($required_data, $request)
    {
        foreach ($required_data as $instance_name) {
            if (!property_exists($request, $instance_name)) {
                return "{$instance_name} is required";
            }
        }

        return true;
    }

    /**
     * helper method to get the current user lic country to properly use one method or other depending on the license
     * @return mixed|string
     */
    private function getLicCountry()
    {
        if (empty($this->lic_country)) {
            $this->lic_country = phive('Licensed')->getLicCountry($this->user);
        }
        return $this->lic_country;
    }

    /**
     * Mapping to the license openSession
     * @return mixed|null
     */
    private function openSession()
    {
        return $this->lic($this->getLicCountry(), 'openSession');
    }

    /**
     * Mapping to the license closeSession
     * @return mixed|null
     */
    private function closeSession()
    {
        return $this->lic($this->getLicCountry(), 'closeSession');
    }

    /**
     * Open session Create IT AAMS external game session
     */
    public function openSessionIT(): bool
    {
        $params = $this->getGpParams();
        // first check if session does not exist
        $this->setExternalSessionByToken($this->user, $params->transactionRefId);
        if (!empty($this->session_entry)) {
            return true;
        }

        // else start a new session
        $stake = $this->convertFromToCoinage($params->sessionBalance, self::COINAGE_UNITS, self::COINAGE_CENTS);
        $session_id = lic('createNewExternalSession',
            [$this->user, $this->_getGameData(), $params->transactionRefId, $stake], $this->user);
        $this->logger->debug(__METHOD__, [
            'stake' => $stake,
            'session_id' => $session_id,
        ]);
        if (!empty($session_id)) {
            $this->setExternalSessionByToken($this->user,
                $params->transactionRefId); // If this is able to set the session then session creation was successful
            // store Evolution session id so we are able to match bets/wins with the external session
            $session_data = $this->fromSession($this->session_token);
            $session_data->ext_session_id = $params->transactionRefId;
            phMset(mKey($this->getUidFromToken($this->session_token), $this->session_token),
                json_encode($session_data));
            return true;
        }

        return false;
    }

    /**
     * Response sent on open session
     *
     * @return array[]
     */
    public function openSessionResponseIT(): array
    {
        $ext_session = $this->getLicSessionService($this->user)->getExtGameSessionById($this->session_entry['external_game_session_id']);
        return [
            'aamsSession' => [
                'id' => $ext_session['ext_session_id'],
                'ticket' => $this->session_entry['participation_id'],
            ]
        ];
    }

    /**
     * Set the external game session by the transactionRefId
     *
     */
    public function closeSessionIT()
    {
        $params = $this->getGpParams();
        // first check if session does not exist
        $this->setExternalSessionByToken($this->user, $params->transactionRefId);
        if ($this->isGameSessionFinished()) {
            return true;
        }
        $session_data = $this->fromSession($this->session_token);
        if ($session_data && !empty($session_data->ext_session_id) && $session_data->ext_session_id == $params->transactionRefId) {
            unset($session_data->ext_session_id);
            phMset(mKey($this->getUidFromToken($this->session_token), $this->session_token),
                json_encode($session_data));
        }
        if (!empty($this->session_entry) && $this->finishExternalGameSession($this->user)) {
            return true;
        }
        return ['code' => 'SESSION_DOES_NOT_EXIST'];
    }

    /**
     * Enables round table
     *
     * @return bool
     */
    public function doConfirmByRoundId(): bool
    {
        return !($this->isTournamentMode() || $this->_isFreespin());
    }

    /**
     * Handle promo_payout request, currently only "FromGame" type.
     * Payout transaction cannot be correlated to any individual debit/_bet transaction.
     */
    public function promoPayout()
    {
        $gpParams = $this->getGpParams();
        $promoTransaction = $gpParams->promoTransaction;
        $user = cu($gpParams->userId ?? null);
        $transaction_type = $this->_m_aPromoTransactionTypes[$promoTransaction->type ?? null];
        $amount = $this->convertFromToCoinage($promoTransaction->amount, self::COINAGE_UNITS, self::COINAGE_CENTS);

        if (empty($transaction_type)) {
            return $this->_m_aErrors['ER44'];
        } else {
            if (empty($user)) {
                return $this->_m_aErrors['ER09'];
            }
        }

        $description = sprintf('EvolutionPromoPayout:%s:%s', $promoTransaction->id, $promoTransaction->type);
        $prev_transaction = phive('CasinoCashier')->getTransactionByDescr($description, $user->getId());
        if (!empty($prev_transaction)) {
            return $this->_m_aErrors['ER05'];
        }

        if ($transaction_type !== 8) {
            $current_payout_id = phive('Cashier')->transactUser($user, $amount, $description, null, null,
                $transaction_type, false,);
            return empty($current_payout_id) ? $this->_m_aErrors['ER01'] : true;
        }

        if (!$this->_isFreespin()) {
            return $this->_m_aErrors['ER17'];
        } elseif ($this->_getFreespinData('frb_remaining') <= 0) {
            return $this->_m_aErrors['ER05'];
        }

        $this->_m_bFrwSendPerBet = true;
        $this->_m_bUpdateBonusEntriesStatusByWinRequest = false;
        $result = $this->_win($this->_m_oRequest->action->parameters);
        if ($result === true) {
            $this->_m_bFrwSendPerBet = false;
            $this->_m_bUpdateBonusEntriesStatusByWinRequest = true;
            return $this->_handleFspinWin($amount);
        }

        return $result;
    }

    /**
     * @return void
     */
    private function setUserLaunchedGame($token_uid = null)
    {
        if (!empty($token_uid)) {
            $this->uid = $this->getUsrId($token_uid);
            $this->user = cu($this->uid);
            return;
        }

        $this->user = cu();
        if (!empty($this->user)) {
            $this->uid = $this->user->getId();
        }
    }


    /**
     *
     * @param stdClass $parameters
     * @return array|bool|mixed
     */

    public function _cancel($parameters)
    {
        foreach ($this->getTransactionsTables() as $transaction_table) {
            // get transaction (wins/bets) db entry
            $transaction = $this->_getTransactionById($parameters->transactionid, $transaction_table);
            // get round details for this $transaction
            $round_details = $this->_getRoundDetails($parameters->roundid, $transaction['id'], $transaction_table);

            // For Evolution if a round is finished and we try to cancel a bet or win, then we need to send BET_ALREADY_SETTLED
            if (!empty($round_details['is_finished'])) {
                return $this->_m_aErrors['ER38'];
            } else {
                // whenever a bet is cancelled but round is not finished we need to send a different error
                // basically our code is working fine its just that Evolution wants us to send a different error in case a cancel request comes in for already cancelled bet or a request is for a  win for which the bet is cancelled already
                $existing_bet = $this->_getbetById($round_details['bet_id']);
                if (!empty($existing_bet) && preg_match($this->getCancelledTransactionRegex($this->_txn_length),
                        $existing_bet['mg_id'])) {
                    return $this->_m_aErrors['ER42'];
                }
            }
        }

        return Gp::_cancel($parameters);
    }

    /**
     * @param stdClass $p_oParameters
     * @return array|bool|mixed
     */
    public function _bet(stdClass $p_oParameters)
    {
        // If it is receiving a bet that has been cancelled
        // it will return the error FINAL_ERROR_ACTION_FAILED
        if (!empty($this->_getTransactionById($p_oParameters->transactionid, self::TRANSACTION_TABLE_BETS, true))) {
            return $this->_m_aErrors['ER43'];
        }

        $this->logger->debug(__METHOD__, [
            'response' => $p_oParameters,
        ]);
        return parent::_bet($p_oParameters);
    }

    /**
     *
     *
     * @param stdClass $parameters
     * @return array|bool|mixed
     */

    public function _win($parameters)
    {
        // get transaction wins db entry
        $transaction = $this->_getTransactionById($parameters->transactionid, self::TRANSACTION_TABLE_WINS);
        // get round details for this $transaction
        $round_details = $this->_getRoundDetails($parameters->roundid, $transaction['id'],
            self::TRANSACTION_TABLE_WINS);

        if (!empty($round_details)) {
            // if we receive a duplicate win request for which round is already settled
            if (!empty($transaction) && $parameters->amount == $transaction['amount'] && !empty($round_details['is_finished'])) {
                return $this->_m_aErrors['ER38'];
            } else {
                // if we receive a win request for which bet is already cancelled
                // Casino::confirmWin() only checks that there is a round(implying that a bet was made for this win request),
                // but we need a second check to see if bet is cancelled or not aswell
                $existing_bet = $this->_getbetById($round_details['bet_id']);
                if (!empty($existing_bet) && preg_match($this->getCancelledTransactionRegex($this->_txn_length),
                        $existing_bet['mg_id'])) {
                    return $this->_m_aErrors['ER42'];
                }
            }
        }

        if ($this->_isFreespin() && !empty($transaction)) {
            // If we receive a duplicate win request at the end of free spins round.
            return $this->_m_aErrors['ER05'];
        }

        $this->logger->debug(__METHOD__, [
            'response' => $parameters,
        ]);
        return Gp::_win($parameters);
    }

    /**
    * @param $ext_round_id
    * @param $transaction_id
    * @param $transaction_table
    * @return array
    */
    private function _getRoundDetails($ext_round_id, $transaction_id, $transaction_table)
    {
        $where = ['ext_round_id' => $this->getTransactionPrefix().$ext_round_id];
        if(!empty($transaction_id)){
            if($transaction_table == 'bets')
                $where['bet_id'] = $transaction_id;
            else if($transaction_table == 'wins')
                $where['win_id'] = $transaction_id;
        }
        $sql = "SELECT * FROM rounds " . phive('SQL')->makeWhere($where);
        return phive('SQL')->sh($this->uid)->loadAssoc($sql) ?: [];
    }

    /**
     * @param $bet_id
     * @return array
     */
    private function _getbetById($bet_id)
    {
        $where = ['id' => $bet_id];
        //TODO: I cannot find a function in code base to get a bet based on bet.id and also check if the round was cancelled, if there is one such function then remove this and use that function
        //GP::_getTransactionById() has above two things but it uses mg_id so putting up a query here to get bet by id
        $sql = "SELECT * FROM " .self::TRANSACTION_TABLE_BETS . " " . phive('SQL')->makeWhere($where);
        return phive('SQL')->sh($this->uid)->loadAssoc($sql) ?: [];
    }

    public function parseJackpots()
    {
        $api_urls = $this->getAllJurSettingsByKey('jp_api_url');
        $api_usernames = $this->getAllJurSettingsByKey('jp_api_username');
        $api_passwords = $this->getAllJurSettingsByKey('jp_api_password');

        $jackpots = [];

        foreach ($api_urls as $jurisdiction => $api_url) {
            $api_username = $api_usernames[$jurisdiction] ?? null;
            $api_password = $api_passwords[$jurisdiction] ?? null;

            if (empty($api_url) || empty($api_username) || empty($api_password)) {
                $this->logger->error(__METHOD__, [
                    'api_url' => $api_url,
                    'api_username' => $api_username,
                    'api_password' => $api_password,
                    'jurisdiction' => $jurisdiction,
                    'message' => 'Missing API credentials for Evolution(Netent) jackpot',
                ]);
                continue;
            }

            $jackpots += $this->getNetentJackpots($api_url, $api_username, $api_password, $jurisdiction);
            $jackpots += $this->getRedtigerJackpots($api_url, $api_username, $api_password, $jurisdiction);
        }

        return $jackpots;
    }

    private function getNetentJackpots(string $api_url, string $api_username, string $api_password, string $jurisdiction)
    {
        $currency = phive('Currencer')->getCurrencyByCountryCode(explode('-', $jurisdiction)[0])['code'] ?? 'EUR';
        $headers[] = "Authorization: Basic " . base64_encode("{$api_username}:{$api_password}");

        $jackpots = [];
        $response = json_decode(phive()->get("{$api_url}/netent?currency={$currency}", 10, $headers, "evolution_netent_jackpot_curl"), true);

        foreach ($response as $jackpot) {
            foreach ($jackpot['tableIds'] as $game_id) {
                $game_id = "evolution_{$game_id}";
                $game = phive('MicroGames')->getByGameId($game_id);
                
                if (empty($game)) {
                    $this->logger->info(__METHOD__, [
                        'jackpot' => $jackpot,
                        'game_id' => "$game_id",
                        'jurisdiction' => $jurisdiction,
                        'message' => 'Game not found for Evolution(Netent) jackpot',
                    ]);
                    continue;
                }

                $jackpots[] = [
                    'jp_id' => 'evolution_' . $jackpot['jackpotName'],
                    'jp_name' => $jackpot['jackpotName'],
                    'module_id' => $game['ext_game_name'],
                    'network' => 'evolution',
                    'jp_value' => round($jackpot['jackpotAmount'] * 100),
                    'currency' => $jackpot['jackpotCurrency'],
                    'local' => 0,
                    'jurisdiction' => $jurisdiction,
                    'game_id' => $game['game_id']
                ];
            }
        }

        return $jackpots;
    }


    private function getRedtigerJackpots(string $api_url, string $api_username, string $api_password, string $jurisdiction)
    {
        // Redtiger endpoint returns only EUR currency
        $headers[] = 'Authorization: Basic ' . base64_encode("{$api_username}:{$api_password}");
        $response = json_decode(phive()->get($api_url . '/redtiger', 10, $headers, "evolution_redtiger_jackpot_curl"), true);

        $res = [];
        $jackpots = $response['result']['jackpots'] ?? [];
        foreach ($jackpots as $jackpot) {
            foreach ($jackpot['pots'] as $pot) {
                foreach ($jackpot['tableIds'] as $game_id) {
                    $game = phive('MicroGames')->getByGameId('evolution_' . $game_id);

                    if (empty($game)) {
                        $this->logger->info(__METHOD__, [
                            'jackpot_id' => $pot['key'],
                            'game_id' => $game_id,
                            'jurisdiction' => $jurisdiction,
                            'message' => 'Game not found for Evolution(Redtiger) jackpot',
                        ]);
                        continue;
                    }

                    $res[] = [
                        'jp_id' => 'evolution_' . $pot['key'],
                        'jp_name' => 'Red Tiger - ' . $pot['name'],
                        'module_id' => $game['ext_game_name'],
                        'network' => 'evolution',
                        'jp_value' => round($pot['amount'] * 100),
                        'currency' => $pot['currency'],
                        'local' => 0,
                        'jurisdiction' => $jurisdiction,
                        'game_id' => $game['game_id']
                    ];
                }
            }
        }

        return $res;
    }
}
