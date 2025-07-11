<?php
require_once 'TestGp.php';

class TestIgt extends TestGp
{
    
    function __construct()
    {
        $this->url = "http://www.videoslots.loc/diamondbet/soap/igt.php";
        $this->injectDependency(phive('Igt'))
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
        $iUuId = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($iUuId, $this->_m_iUserId, $this->_m_mGameId);
        
        if (isset($p_aParameters['freespin'])) {
            if ($p_aParameters['freespin'] === true) {
                $aFreespins = $this->_m_oGp->getBonusEntryByGameIdAndFRbRemaining($this->_m_iUserId, $this->_m_mGameId, '', $this->_m_oGp->getGpName());
                $frb_denomination = $aFreespins['frb_denomination'];
                $frb_lines = $aFreespins['frb_lines'];
                $frb_id = $aFreespins['id'];
            } else {
                $frb_denomination = $p_aParameters['freespin']['frb_bet'];
                $frb_lines = $p_aParameters['freespin']['frb_lines'];
                $frb_id = $p_aParameters['freespin']['frb_id'];
            }
        }
        return array(
            'userId' => $this->_m_iUserId,
            'userPassword' => $this->_getUserPasswd(),
            'userName' => $this->_m_sUsername,
            'amountBet' => (isset($p_aParameters['amountBet']) ? '<RGSAction action="STAKE" amount="' . $this->_m_oGp->convertFromToCoinage($p_aParameters['amountBet'],
                    Igt::COINAGE_CENTS, Igt::COINAGE_UNITS) . '" actionId="' . $txn . '-00" />' : ''),
            'amountWin' => (isset($p_aParameters['amountWin']) ? '<RGSAction action="WIN" amount="' . $this->_m_oGp->convertFromToCoinage($p_aParameters['amountWin'],
                    Igt::COINAGE_CENTS, Igt::COINAGE_UNITS) . '" actionId="' . $txn . '-01" />' : ''),
            'gameId' => (isset($p_aParameters['gameId']) ? $p_aParameters['gameId'] : $this->_m_mGameId),
            'txn' => $txn,
            'finished' => (isset($p_aParameters['finished']) ? $p_aParameters['finished'] : 'Y'),
            'secureToken' => (($this->_m_bForceSecureToken === true) ? '1234567890' : strtoupper($iUuId)),
            'currency' => $this->_m_sUserCurrency,
            'timestampiso8601' => date('c', time()),
            'promotions' => ((isset($p_aParameters['promotions']) && $p_aParameters['promotions'] == 'Y') ? ' promotions="Yes"' : ''),
            'fundMode' => (isset($p_aParameters['freespin']) ? '<FundMode type="FREESPIN" id="' . $frb_id . '" num_lines="' . $frb_lines . '" stake_per_line="' . $this->_m_oGp->convertFromToCoinage(mc($frb_denomination,
                    $this->_m_sUserCurrency, 'multi', false), Gp::COINAGE_CENTS, Gp::COINAGE_UNITS) . '" />' : ''),
        );
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
        
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/TestIgt/request/' . $this->_m_sGpMethod . '.xml');
        
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
}
