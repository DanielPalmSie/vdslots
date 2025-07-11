<?php

require_once __DIR__ . '/Gp.php';

class Leander extends Gp
{

    /**
     * Name of GP. Used to prefix the bets|wins::game_ref (which is the game ID) and bets|wins::mg_id (which is transaction ID)
     * @var string
     */
    protected $_m_sGpName = __CLASS__;

    /**
     * Find a bet by transaction ID or by round ID.
     * Mainly when a win comes in to check if there is a corresponding bet. If the transaction ID used for win is the same as bet set to false otherwise true.
     * Default true. Make sure that the round ID send by GP is an integer
     * @var boolean
     */
    protected $_m_bByRoundId = true;

    /**
     * Is the bonus_entries::balance updated after each FRB request with the FRW or does the GP keeps track and send the total winnings at the end of the free rounds.
     * Default: true (balance is updated per bet)
     */
    protected $_m_bFrwSendPerBet = true;

    /**
     * Update the status in bonus_entries table by the last FRW request from the GP in the _win() method. Default: true.
     * If set to false make sure that $this->_handleFspinWin() is called from the extended class.
     * @var bool
     */
    protected $_m_bUpdateBonusEntriesStatusByWinRequest = false;

    /**
     * Insert frb into bet table so in case a frw comes in we can check if it has a matching frb
     * @var bool
     */
    protected $_m_bConfirmFrbBet = false;

    /**
     * The header content type for the response to the GP
     * @var string
     */
    protected $_m_sHttpContentType = Gpinterface::HTTP_CONTENT_TYPE_TEXT_XML;

    /**
     * Do we force respond with a 200 OK response to the GP
     * @var bool
     */
    protected $_m_bForceHttpOkResponse = true;

    /**
     * Does the GP keep track of the FRB count on their side (as with Leander) then set as true to let them keep count
     * to avoid conflicts.
     * @var bool
     */
    protected $_m_bInhouseFrbCounter = true;

    private $_m_sSecureToken = '';

    private $_m_sTxnId = '';

    /**
     * Map GP methods requests to the correct class method name
     * Keys are the method names received by the GP request and the values are the class method names
     * @var array
     */
    private $_m_aMapGpMethods = array(
        'initializeGame' => '_init',
        'requestBalance' => '_balance',
        'updateBalance' => '_bet', // or win depends on operation param is CREDIT | DEBIT
        'updateBalanceForced' => '_win', // win after network issues
        'voidTransaction' => '_cancel',
        'playStatus' => '_frbStatus',
        'finalizeGame' => '_end'
    );

    private $_m_aErrors = array(
        'ER01' => array(
            'responsecode' => 500, // used to send header to GP if not enforced 200 OK
            'status' => 'SERVER_ERROR', // used to send header to GP if not enforced 200 OK
            'return' => 'default', // if not default this will overrule what's responsed to GP
            'code' => '515', // change this to whatever the GP likes to receive as code
            'message' => 'Internal Server Error.' // change this to whatever the GP likes to receive as message
        ),
        'ER02' => array(
            'responsecode' => 405,
            'status' => 'COMMAND_NOT_FOUND',
            'return' => 'default',
            'code' => '515',
            'message' => 'Command not found.'
        ),
        'ER03' => array(
            'responsecode' => 401,
            'status' => 'UNAUTHORIZED',
            'return' => 'default',
            'code' => '515',
            'message' => 'The authentication credentials are incorrect.'
        ),
        'ER06' => array(
            'responsecode' => 200,
            'status' => 'INSUFFICIENT_FUNDS',
            'return' => 'default',
            'code' => '509',
            'message' => 'Insufficient money in player\'s account to fulfill operation.'
        ),
        'ER07' => array(
            'responsecode' => 200,
            'status' => 'TRANSACTION_MISMATCH',
            'return' => 'default',
            'code' => '508',
            'message' => 'Transaction details do not match.'
        ),
        'ER08' => array(
            'responsecode' => 404,
            'status' => 'TRANSACTION_NOT_FOUND',
            'return' => 'default',
            'code' => '508',
            'message' => 'Invalid refund, transaction ID does not exist.'
        ),
        'ER09' => array(
            'responsecode' => 404,
            'status' => 'PLAYER_NOT_FOUND',
            'return' => 'default',
            'code' => '501',
            'message' => 'Player not found.'
        ),
        'ER10' => array(
            'responsecode' => 404,
            'status' => 'GAME_NOT_FOUND',
            'return' => 'default',
            'code' => '503',
            'message' => 'Game is not found.'
        ),
        'ER11' => array(
            'responsecode' => 498,
            'status' => 'TOKEN_NOT_FOUND',
            'return' => 'default',
            'code' => '502',
            'message' => 'Token not found.'
        ),
        'ER16' => array(
            'responsecode' => 400,
            'status' => 'REQUEST_INVALID',
            'return' => 'default',
            'code' => '504',
            'message' => 'Invalid request.'
        ),
    );

    /**
     * @var string
     */
    protected string $logger_name = 'leander';

    /**
     * Set the defaults
     * Seperate function so it can be called also from the classes that extend TestGp class
     * @return Gp
     */
    public function setDefaults()
    {

        $this
            ->_mapGpMethods($this->_m_aMapGpMethods)
            ->_whiteListGpIps($this->getSetting('whitelisted_ips'))
            ->_overruleErrors($this->_m_aErrors)
            ->_checkDeclaredProperties()
            ->_setWalletActions();
        return $this;
    }


    /**
     * Set the return function to true if we are going to use the rounds table
     * @return bool true or false
     */
    public function doConfirmByRoundId()
    {
        return !($this->isTournamentMode() || $this->_isFreespin());
    }

    public function preProcess()
    {

        $aJson = $aAction = array();
        $method = $key = null;

        $this->setDefaults();

        $params = $_REQUEST;

        $this->_setGpParams($params);

        if (empty($params)) {
            // request is empty
            $this->logger->error(__METHOD__, [$_REQUEST, 'Empty Request-'.self::ER16]);
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER16));
            die();
        }

        $aMethods = $this->_getMappedGpMethodsToWalletMethods();

        // Define which service/method is requested/to use
        $urlMethod = substr(strrchr(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), '/'), 1);
        foreach ($aMethods as $key => $value) {
            if ($key == $urlMethod) {
                $method = $value;
                $this->_setGpMethod($key);
                break;
            }
        }

        $this->_logIt([__METHOD__, 'Method:' . $method]);
        if (empty($method)) {
            // method to execute not found
            $this->logger->error(__METHOD__ , ['Method to execute not found']);
            $this
                ->_setHttpContentType(Gpinterface::HTTP_CONTENT_TYPE_TEXT_HTML)
                ->_forceHttpOkResponse(false)
                ->_setResponseHeaders($this->_getError(self::ER02));
            die();
        }

        if (!empty($params['gameMode']) && $params['gameMode'] == 'FUN') {
            $this->_response(true);
        }

        // check if session does exist if so get the data from the sesssion
        $mSessionData = null;
        if (!empty($params['token'])) {

            $mSessionData = $this->fromSession($params['token']);
            $this->_logIt([__METHOD__, 'session-data:' . print_r($mSessionData, true)]);
            $this->logger->debug(__METHOD__ , ['session-data:', $mSessionData]);
            if (!empty($mSessionData)) {
                $this->_m_sSecureToken = $params['token'];
                $aJson['playerid'] = $this->getUsrId($mSessionData->userid);
                $aJson['skinid'] = $mSessionData->gameid;
                $aJson['device'] = $mSessionData->device;
            }

            $this->_m_sSecureToken = empty($this->_m_sSecureToken) ? $params['token'] : $this->_m_sSecureToken;

        }

        if (!isset($aJson['playerid']) && !empty($params['userId'])) {
            $aJson['playerid'] = $this->getUsrId($params['userId']);
        }

        if (!isset($aJson['skinid']) && !empty($params['gameId'])) {
            $aJson['skinid'] = $params['gameId'];
        }

        if (!isset($aJson['device']) && !empty($params['channel'])) {
            $aJson['device'] = (($params['channel'] == 'mobile') ? 'mobile' : 'desktop');
        }

        if (!empty($params['transactionId'])) {
            $this->_m_sTxnId = $params['transactionId'];
        }

        // single transaction to process
        $aJson['state'] = 'single';

        $aAction[0]['command'] = $method;

        if (in_array($this->getGpMethod(), array('updateBalance', 'updateBalanceForced', 'voidTransaction'))) {
            switch ($params['operation']) {
                case 'DEBIT':
                    $aAction[0]['command'] = '_bet';
                    break;
                case 'CREDIT':
                    $aAction[0]['command'] = '_win';
                    break;
            }

            $aAction[0]['parameters'] = array(
                'amount' => $this->convertFromToCoinage($params['amount'], self::COINAGE_UNITS, self::COINAGE_CENTS),
                'transactionid' => $this->_m_sTxnId,
                'roundid' => $params['playId'],
            );


            if ($method == '_cancel') {
                unset($aAction[0]['parameters']['amount']);
            }
        }

        // detect for freespin
        if (!empty($params['promotionCode']) && (!empty($params['playingFreePlay']) || (!empty($params['playStatus']) && in_array($params['playStatus'],
                        array('3', '4'))))) {
            $aJson['freespin'] = array(
                'id' => $params['promotionCode'],
                'status' => (!empty($params['playStatus']) ? $params['playStatus'] : 0),
                'finished' => (!empty($params['promotionFinished']) ? $params['promotionFinished'] : false)
            );
        }

        $aJson['action'] = $aAction[0];
        $this->_m_oRequest = json_decode(json_encode($aJson), false);

        //var_dump($this->_m_oRequest);
        $this->_logIt([__METHOD__, print_r($aJson, true), print_r($this->_m_oRequest, true)]);
        $this->logger->debug(__METHOD__, [$aJson, $this->_m_oRequest]);
        //die;
        return $this;
    }

    /**
     * Execute the requested command from gaming provider
     * @return void
     */
    public function exec()
    {

        $mResponse = false;

        //if(in_array($this->_m_oRequest->callerauth, $this->getSetting('callerauth')) && sha1($this->_m_oRequest->callerauth . $this->_m_oRequest->callerpassword) == sha1($this->_m_oRequest->callerauth . $this->getSetting('callerpassword'))){
        // check if the commands requested do exist
        $this->_setActions();

        // Update the user data before each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }

        // Set the game data by the received skinid (is gameid)
        if (isset($this->_m_oRequest->skinid)) {
            $game = null;
            if ($this->isTournamentMode()) {
                $iso = $this->getLicSetting('bos-country', $this->user);
                if (!empty($iso)) {
                    $gco = phive('MicroGames')->getGameCountryOverrideByOverride($this->user, $this->getGamePrefix() . $this->_m_oRequest->skinid, $iso, true);
                    if (!empty($gco)) {
                        $game = phive('MicroGames')->getById($gco['game_id']);
                    }
                }
            }
            $this->_setGameData(true, $game);
        }

        if (isset($this->_m_oRequest->freespin)) {
            $this->_setFreespin($this->_m_oRequest->playerid, $this->_m_oRequest->freespin->id);
            if (!$this->_isFreespin()) {
                // the frb doesn't exist or is missing??
                $this->logger->error(__METHOD__, [self::ER17]);
                $this->_response($this->_getError(self::ER17));
            }
        }

        // execute all commands
        foreach ($this->_getActions() as $key => $oAction) {

            // Update the user data before each command
            if (isset($this->_m_oRequest->playerid)) {
                $this->_setUserData();
            }

            $sMethod = $oAction->command;
            $this->_setWalletMethod($sMethod);

            // command call return either an array with errors or true on success
            if (property_exists($oAction, 'parameters')) {
                $mResponse = $this->$sMethod($oAction->parameters);
            } else {
                $mResponse = $this->$sMethod();
            }
            if ($mResponse !== true) {
                // some error occurred
                break;
            }
        }
        //} else {
        // secret key not valid
        //   $mResponse = $this->_getError(self::ER03);
        // }

        // Update the user data after each command
        if (isset($this->_m_oRequest->playerid)) {
            $this->_setUserData();
        }
        $this->_response($mResponse);
    }

    /**
     * Update the status in the bonus entries table when a FRB round has finished
     * @return bool
     */
    protected function _frbStatus()
    {
        if ($this->_m_oRequest->freespin->finished == 'True') {
            // it is a FRB and if finished = 'True' then FRB has finished and we will not receive FRW anymore.
            $this->_handleFspinWin();
        }
        return true;
    }

    protected function _response($p_mResponse)
    {

        $aUserData = $this->_getUserData();

        $this->_setResponseHeaders($p_mResponse);

        $sXml = '';

        if ($p_mResponse === true) {

            if (!in_array($this->getGpMethod(), array('playStatus', 'finalizeGame'))) {
                $sXml .= '<accountBalance>' . $this->convertFromToCoinage($this->_getBalance(), self::COINAGE_CENTS,
                        self::COINAGE_UNITS) . '</accountBalance>';
                $sXml .= '<accountCurrency>' . strtoupper($this->getPlayCurrency($aUserData)) . '</accountCurrency>';

                $maxBet = phive('Gpr')->getMaxBetLimit(cu($aUserData['id']));
                if($maxBet){
                    $sXml .= '<sessionMaxBet>'. sprintf("%.2f", $maxBet) .'</sessionMaxBet>';
                }
            }

            if (in_array($this->getGpMethod(), array('updateBalance', 'voidTransaction'))) {
                // Page 15 manual: In the response message, the transaction id and currency should always match the values sent in the request. Those are for sanity check.
                $sXml .= '<transactionId>' . $this->_m_sTxnId . '</transactionId>';
            }

        } else {
            // error
            $sXml = '<errorCode>' . ($p_mResponse['code'] ?? $p_mResponse['responsecode']) . '</errorCode><errorMessage>' . $p_mResponse['message'] . '</errorMessage>';
        }

        // sometimes we get finalizeGame request when the game session has expired on our side
        // we should still attach the token so that the gp can finalize the rounds on their end
        if($this->getGpMethod() === 'finalizeGame' && empty($this->_m_sSecureToken)){
            $this->_m_sSecureToken = $_REQUEST['token'];
        }

        $sXml = str_replace('{{replaceMe}}', $sXml,
            '<?xml version="1.0"?><response result="' . (($p_mResponse !== true) ? 'ERROR' : 'OK') . '" timestamp="' . date('Ymd\THis') . 'Z">{{replaceMe}}' . (!empty($this->_m_sSecureToken) ? '<token>' . $this->_m_sSecureToken . '</token>' : '') . '</response>');
        $this->logger->debug(__METHOD__, [$sXml]);
        $this->_logIt([__METHOD__, $sXml]);
        echo $sXml;

        $this->_setEnd(microtime(true))->_logExecutionTime();
        die();
    }


    /**
     * Get the launcher url to launch a game from the GP
     *
     * @param string $p_mGameId The game_ext_name from the microgames table without the prefix so basically the game ID as provided by the GP
     * @param string $p_sLang The lang code
     * @param string $p_sTarget The target. desktop|mobile
     * @param bool $show_demo
     * @return string The url to open the game
     */
    protected function _getUrl($p_mGameId, $p_sLang = '', $p_sTarget = '', $show_demo = false)
    {
        $aUrl = array();
        $aUrl['gameId'] = $p_mGameId;
        $aUrl['gameMode'] = 'FUN';
        $aUrl['lobbyURL'] = $this->getLobbyUrlForGameIframe(false, $p_sLang, $p_sTarget);
        $aUrl['channel'] = $p_sTarget;

        $user_obj = cu();

        if (!empty($user_obj)) {

            $iUserId = $user_obj->getId();
            $ud = ud($user_obj);
            $aUrl['gameMode'] = 'REAL';
            $aUrl['locale'] = phive('Localizer')->getCountryValue('langtag', $ud['preferred_lang']);
            $aUrl['siteId'] = $this->getLicSetting('siteid', $user_obj) ;
            if (empty($_SESSION['token_uid'])) {
                $sSecureToken = $this->getGuidv4($iUserId);
            }else {
                $iUserId = $sSecureToken = $_SESSION['token_uid'];
                $this->getUsrId($iUserId);
            }
            $aUrl['token'] = $sSecureToken;
            $aUrl['userId'] = $iUserId;
            $aUrl['currency'] = strtoupper($this->getPlayCurrency($ud));
            $aUrl['rm'] = $this->getJurisdiction($user_obj);

            switch ($p_sTarget) {
                case 'mobile':
                    $aRc = $this->_getRc();
                    if (!empty($aRc)) {
                        $aUrl['rcPeriod'] = $aRc['reality_check_interval'];
                        $aUrl['rcElapsedSeconds'] = '1'; // todo
                    }
                    break;
                default:
                    // own solution
                    break;

            }
            $this->toSession($sSecureToken, $iUserId, $p_mGameId, $p_sTarget);
        } else {
            $aUrl['siteId'] = $this->getLicSetting('siteid');
            $aUrl['currency'] = strtoupper(lic('getForcedCurrency', []) ?: $this->getSetting('freeplaycurrency'));
            $aUrl['locale'] = phive('Localizer')->getLocale($p_sLang, 'langtag');
        }

        if ($aUrl['locale'] == 'en-GB') {
            $aUrl['locale'] = 'en-US';
        }

        $aUrl['locale'] = str_replace('_', '-', $aUrl['locale']);

        $aUrl['origin'] = phive()->getSetting('domain');
        $this->logger->debug(__METHOD__, [$aUrl]);
        return $this->getLicSetting('url', $user_obj) . '?' . http_build_query($aUrl);
    }

    public function importNewGames(
        $p_bUseDefaultBkg = true,
        $p_iActive = 1,
        $p_bImport = false,
        array $p_aIds = array()
    ) {
    }

    public function fxJps($jps, $base_cur = 'EUR', $change = true)
    {
        $cur = phive('Currencer');
        $ret = [];
        foreach($cur->getAllCurrencies() as $ciso => $c){
            foreach($jps as $jp){
                $jp['jp_value'] = $change ? $cur->changeMoney($base_cur, $ciso, $jp['jp_value']) : $jp['jp_value'];
                $jp['currency'] = $ciso;
                $jp['jp_id'] = $jp['jp_id'];
                $ret[] = $jp;
            }
        }
        return $ret;
    }

    public function parseJackpots()
    {
        $inserts = [];
        $jp_api_endpoints = $this->getAllJurSettingsByKey('jp_url');

        foreach ($jp_api_endpoints as $jurisdiction => $api_endpoint) {
            $raw_jackpot_mapping = $this->getRawJackpotMapping($api_endpoint);
            $currencies = array_keys(phive('Currencer')->getAllCurrencies());

            foreach ($currencies as $currency) {
                $current_endpoint = $this->buildEndpoint($api_endpoint, $currency);
                $response_data = $this->getResponseData($current_endpoint);

                $jackpots = $response_data['jackpots']['jackpot'] ?? [];

                foreach ($jackpots as $jackpot) {
                    if (isset($jackpot['funMode']) && $jackpot['funMode'] === 'true') {
                        continue;
                    }

                    $jackpot_name = $jackpot['jackpotName'] ?? null;
                    if (!$jackpot_name || !isset($raw_jackpot_mapping[$jackpot_name])) {
                        continue;
                    }

                    $raw_jackpot = $raw_jackpot_mapping[$jackpot_name];
                    $unique_game_codes = $this->getUniqueGameCodes($raw_jackpot);
                    if (empty($unique_game_codes)) {
                        continue;
                    }

                    foreach ($unique_game_codes as $game_code) {
                        $game = $this->findLeanderGameByCode($game_code);

                        if (empty($game)) {
                            $this->logger->info(__METHOD__, [
                                'jackpot'      => $jackpot,
                                'game_id'      => $game_code,
                                'jurisdiction' => $jurisdiction,
                                'message'      => 'Game not found for Leander jackpot',
                            ]);
                            continue;
                        }

                        $inserts[] = [
                            'jp_value'     => floor($jackpot['progressiveAccumulatedInRequestedCurrency'] * 100),
                            'jp_id'        => $jackpot['jackpotName'],
                            'jp_name'      => $game['game_name'],
                            'module_id'    => $game['game_id'],
                            'network'      => $this->getGpName(),
                            'currency'     => $currency,
                            'local'        => 0,
                            'jurisdiction' => $jurisdiction,
                            'game_id'      => $game['game_id']
                        ];
                    }
                }
            }
        }

        return array_filter($inserts);
    }

    /**
     * Finds a Leander game by its code.
     *
     * This method attempts to locate a game. It generates possible game ID variants
     * by appending a prefix and handles potential formatting issues such
     * as extra spaces. The method iterates through these variants and
     * returns the first matching game found.
     *
     * @param string $game_code The code of the game to search for.
     * @return array|null The game object if found, or null if no matching game is found.
     */
    private function findLeanderGameByCode(string $game_code): ?array
    {
        $prefix = 'leander_';

        $game_id_variants = [
            $prefix . $game_code,
            $prefix . ' ' . $game_code,
        ];

        foreach ($game_id_variants as $game_id) {
            $game = phive('MicroGames')->getByGameId($game_id);
            if (!empty($game)) {
                return $game;
            }
        }

        return null;
    }


    /**
     * Retrieves the raw jackpot mapping from the given API endpoint.
     *
     * This method fetches response data from the specified API endpoint and processes
     * it to extract a mapping of jackpot items indexed by their 'name' attribute.
     *
     * @param string $api_endpoint The API endpoint to fetch the raw jackpot data from.
     * 
     * @return array An associative array of jackpot items indexed by their 'name',
     *               or an empty array if no jackpot items are found.
     */

    private function getRawJackpotMapping(string $api_endpoint): array
    {
        $raw_response_data = $this->getResponseData($api_endpoint . '&raw=true');

        return isset($raw_response_data['jackpots']['item']) ?
            array_column($raw_response_data['jackpots']['item'], null, 'name') : [];
    }


    /**
     * Builds the API endpoint URL for the given endpoint and currency.
     *
     * @param string $api_endpoint The base API endpoint to be used.
     * @param string $currency The currency code to be included in the endpoint.
     * @return string The constructed API endpoint URL.
     */
    private function buildEndpoint(string $api_endpoint, string $currency): string
    {
        $current_endpoint = preg_replace('/&currency=(\w+)/', '', $api_endpoint);
        return "{$current_endpoint}&currency={$currency}";
    }

    /**
     * Retrieves the response data from the specified API endpoint.
     *
     * @param string $endpoint The API endpoint to fetch data from.
     * @return array The response data retrieved from the endpoint.
     */
    private function getResponseData(string $endpoint): array
    {
        $response = phive()->get($endpoint, '', '', $this->getGpName() . '-curl', 10);
        $response = simplexml_load_string($response);
        return json_decode(json_encode($response), true);
    }

    /**
     * Retrieves a list of unique game IDs from the provided raw jackpot data.
     *
     * @param array $raw_jackpot The raw jackpot data containing game information.
     * @return array An array of unique game IDs extracted from the raw jackpot data.
     */
    private function getUniqueGameCodes(array $raw_jackpot): array
    {
        $unique_game_codes = [];
        if (isset($raw_jackpot['jackpot_games']['item'])) {
            $game_codes = array_column($raw_jackpot['jackpot_games']['item'], 'game_code');
            $unique_game_codes = array_keys(array_flip($game_codes));
        }
        return $unique_game_codes;
    }

    /**
     * Assigns on the supplier side the FS for the player.
     * The player can open the game anytime. The GP already 'knows' about the freespins available to the player.
     * GP keeps track itself and send the wins at the end when all FRB are finished.
     *
     * @param int $user_id The user ID
     * @param string $game_id The game id, Leander uses unique ids so no multiple ids required
     * @param int $free_spins The frb given to play
     * @param string $bonus_name
     * @param array $bonus_entry The entry from bonus_types table
     * @return bool|string|int If not false than empty string is returned otherwise false (freespins are not activated)
     */
    public function awardFRBonus($user_id, $game_id, $free_spins, $bonus_name, $bonus_entry)
    {
        $this->logger->debug(__METHOD__ . '(1)', [
            'user_id' => $user_id,
            'game_id' => $game_id,
            'bonus_entry' => $bonus_entry['id'],
            'no_out' => $this->getSetting('no_out'),
        ]);

        if($this->getSetting('no_out') === true) {
            return true;
        }

        $user_obj = cu($user_id);
        $this->logger->debug(__METHOD__, [$user_id, $bonus_entry]);
        $this->_logIt([__METHOD__, 'user-data:' . print_r($user_obj->getData(), true), 'Freespin:' . print_r($bonus_entry, true)]);

        if (empty($bonus_entry)) {
            return false;
        }

        $res = $this->apiRequest($this->getLicSetting('api_url', $user_obj), [
            "service" => "add_free_spin",
            "site_id" => $this->getLicSetting('siteid', $user_obj),
            "game_mode" => "REAL",
            "game_code" => $this->stripPrefix(phive('MicroGames')->overrideGameRef($user_obj, $game_id)),
            "plays_total" => $free_spins,
            "total_bet" => $this->convertFromToCoinage($this->_getFreespinValue($user_id, $bonus_entry['id']), self::COINAGE_CENTS, self::COINAGE_UNITS),
            "user_identifier" => (string)$user_id,
            "start_date" => $bonus_entry['start_time'],
            "expiration_date" => $bonus_entry['end_time'],
            "promotion_code" => $bonus_entry['id'],
            "allowed_currency_id" => $user_obj->getCurrency(),
            "currency_id" => $user_obj->getCurrency()
        ], $user_obj);

        $this->logger->debug(__METHOD__ . '(2)', [
            'res' => $res,
            'user_id' => $user_id,
            'bonus_entry' => $bonus_entry['id'],
        ]);

        if ($res === false) {
            return false;
        }

        return $res['data']['promotion_definition_id'] ?? false;
    }

    /**
     * This function is used to cancel the FS bonus on the supplier side.
     * @param $user_id
     * @param $bonus_entry_ext_id
     * @return bool
     */
    public function cancelFRBonus($user_id, $bonus_entry_ext_id) {
        $user_obj = cu($user_id);

        $data = [
            "service" => "disable_free_spin_user",
            "promotion_definition_id" => $bonus_entry_ext_id,
            "user_identifier" => (string) $user_id,
            "site_id" => $this->getLicSetting('siteid', $user_obj),
        ];

        $response = $this->apiRequest($this->getLicSetting('api_url', cu($user_id)), $data, cu($user_id));
        return $response !== false;
    }

    /**
     *
     * @param string $url
     * @param array $request_data
     * @param DBUser $user
     * @return false|array
     */
    private function apiRequest($url, $request_data, $user)
    {
        try{
            $extra = [
                CURLOPT_USERPWD => $this->getLicSetting('api_user', $user) . ':' . $this->getLicSetting('api_password', $user)
            ];
            $debug_key = $this->getSetting('extended_logs') === true ? 'leander-api' : null;
            $result = phive()->post($url, json_encode($request_data), 'application/json', '', 'leander-api', 'POST', '', $extra, 'UTF-8', true);

            $this->logger->debug(__METHOD__, [
                'user_id' => $user->getId(),
                'url' => $url,
                'request' => $request_data,
                'response' => $result,
            ]);

            if ($result[1] == 200) {
                return json_decode($result[0], true);
            } else {
                phive()->dumpTbl("leander-api-request-error", $result);
                return false;
            }
        } catch(Exception $e){
            $this->logger->debug(__METHOD__, [
                'url' => $url,
                'user_id' => $user->getId(),
                'message' => $e->getMessage(),
            ]);
            phive()->dumpTbl("leander-api-request-exception", [$e->getMessage(), $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * For providers like Leander, we don't have a way of identifying whether a round has been finished when
     * The user loses.
     *
     * By default, when the round was created we settled is_finished = 1 to avoid unfinished rounds.

     * @param int $user_id
     * @param string|null $round_id
     * @return array
     */
    public function getLastUnfinishedRound(int $user_id, ?string $round_id = null): array
    {
        return $this->getRound($user_id, $round_id, true, true, true);
    }

    /**
     * @param $user_id
     * @param $bet_id
     * @param $ext_round_id
     * @param int $win_id
     * @param bool|null $is_finished
     * @return bool|int
     */
    public function insertRound($user_id, $bet_id, $ext_round_id, int $win_id = 0, ?bool $is_finished = false)
    {
        return parent::insertRound($user_id, $bet_id, $ext_round_id, $win_id, true);
    }

    /**
     * Get URL to launch game
     *
     * @param string $gameId
     * @param int $userId
     * @param string $lang
     * @param string $target
     *
     * @return string|null
     *
     * @api
     */
    public function getApiUrl(
        string $gameId,
        int $userId,
        string $lang,
        string $target = 'mobile'
    ): ?string {
        $url = $this->_getUrl(trim($this->stripPrefix($gameId)), $lang, $target, false, $userId);

        return $url === false ? null : $url;
    }
}
