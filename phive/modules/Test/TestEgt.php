<?php
require_once 'TestGp.php';

class TestEgt extends TestGp
{
    private bool $set_session = true;
    
    function __construct()
    {
        $this->url = "https://gp-dev.videoslots.com/diamondbet/soap/egt.php";
        $this->injectDependency(phive('Egt'))
            ->injectDependency(phive('UserHandler'));
    }
    
    function prepare($user, $game)
    {
        $this->setGameId($game['ext_game_id'])
            ->forceSecureToken(false)
            ->setUserId($user->getId())
            ->setUrl($this->url)
            ->outputRequest(true);
    }
    
    function testIdempotency($user, $game, $bamount, $wamount)
    {
        $mg_id = $this->randId();
        $arr = [
            [
                'command' => 'betWin',
                'parameters' => [
                    'amountBet' => $bamount,
                    'amountWin' => $wamount,
                    'transactionid' => $mg_id
                ]
            ]
        ];
        
        echo $this->exec($arr);
        
    }
    
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     * @return mixed Depends on the response of the requested url
     */
    public function exec($p_aAction)
    {
        
        (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        
        foreach ($p_aAction as $key => $aAction) {
            
            $this->_m_sGpMethod = $aAction['command'];
            
            $aParameters = (isset($aAction['parameters']) ? $this->_xmlParams($aAction['parameters']) : $this->_xmlParams());
            
            // this GP provides only one command x request
            break;
        }
        
        return $this->_post($this->_parseFile($aParameters));
    }
    
    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     * @return array
     */
    private function _xmlParams(array $p_aParameters = array())
    {
        
        $txn = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
        $rndId = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : rand(100000,10000000));
        
        $iUuId = $this->_m_oGp->getGuidv4();
        if ($this->set_session) {
            $this->_m_oGp->toSession($iUuId, $this->_m_iUserId, $this->_m_mGameId);
        }

        if (isset($p_aParameters['freespin'])) {
            if ($p_aParameters['freespin'] === true) {
                $aFreespins = $this->_m_oGp->getBonusEntryByGameId($this->_m_iUserId, $this->_m_mGameId);
                $frb_denomination = $aFreespins['frb_denomination'];
                $frb_lines = $aFreespins['frb_lines'];
                $frb_id = $aFreespins['id'];
            } else {
                $frb_denomination = $p_aParameters['freespin']['frb_bet'];
                $frb_lines = $p_aParameters['freespin']['frb_lines'];
                $frb_id = $p_aParameters['freespin']['frb_id'];
            }
        }
               
        $res = array(
            'userId' => $this->_m_iUserId,
            'userPassword' => $this->_getUserPasswd(),
            'userName' => $this->_m_sUsername,
            'amountBet' => (isset($p_aParameters['amountBet']) ?  $this->_m_oGp->convertFromToCoinage($p_aParameters['amountBet'],Egt::COINAGE_CENTS, Egt::COINAGE_CENTS): ''),
            'amountWin' => (isset($p_aParameters['amountWin']) ?  $this->_m_oGp->convertFromToCoinage($p_aParameters['amountWin'],Egt::COINAGE_CENTS, Egt::COINAGE_CENTS): ''),
            'gameId' => (isset($p_aParameters['gameId']) ? $p_aParameters['gameId'] : $this->_m_mGameId),
            'transferId' => $txn,
            'gameNumber'=> $rndId,
            'secureToken' => (($this->_m_bForceSecureToken === true) ? '1234567890' : strtoupper($iUuId)),
            'currency' => $this->_m_sUserCurrency,
            'finished' => (isset($p_aParameters['finished']) ? $p_aParameters['finished'] : 'ROUND_END')
        );

        return $res;
    }
    
    /**
     * Parse xml file and replace the placeholders with their value
     * @param array $p_aParams
     * @return string
     */
    private function _parseFile($p_aParams)
    {
        
        foreach ($p_aParams as $key => $val) {
            unset($p_aParams[$key]);
            $p_aParams['{{' . $key . '}}'] = $val;
        }
        
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/TestEgt/request/' . $this->_m_sGpMethod . '.xml');
        
        return str_replace(array_keys($p_aParams), array_values($p_aParams), $sXml);
    }
    
    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     */
    protected function _post($p_sXml)
    {
        
        //$this->_m_sUrl .= '/';
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "XML:" . PHP_EOL . $p_sXml;
        }
        
        //$p_sXml = '<GameRequest><Header><GameDetails gameId="200-1230-001" name="Bubble Craze" channel="INT" presentation="FLSH" class="RGS"/><Customer userId="5235889" skinCode="VS01" ccyCode="EUR" sessionId="110708" countryCode="FI" language="en" secureToken="null"/><Auth username="igt-user" password="sdff-OPwg_QyOsA"/></Header><Play><RGSGame finished="Y" txnId="10203001" subGameIND="N"><RGSAction action="STAKE" amount="0.50" actionId="10203001-00"/><RGSAction action="WIN" amount="0.00" actionId="10203001-01"/></RGSGame></Play></GameRequest>';
        //post($url, $content, $type = 'application/json', $headers = '', $debug_key = '', $method = 'POST'){
        return phive()->post($this->_m_sUrl, $p_sXml, 'text/xml', '', $this->_m_oGp->getGpName() . '-out', 'POST');
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    /**
     * Set the session in memory or not
     * @param bool $set_session
     * @return TestEgt
     */
    public function setSession(bool $set_session = true): TestEgt
    {
        $this->set_session = $set_session;
        return $this;
    }
}
