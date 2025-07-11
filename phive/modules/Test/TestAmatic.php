<?php
require_once 'TestGp.php';

class TestAmatic extends TestGp
{
    
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
            
            $this->_m_sMethod = $aAction['command'];
            
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
        $iUuId = strtoupper($this->_m_oGp->getGuidv4());
        $this->_m_oGp->toSession($iUuId, $this->_m_iUserId, $this->_m_mGameId);
        
        return array(
            'callerId' => $this->_m_oGp->getSetting('callerId'),
            'callerPassword' => $this->_m_oGp->getSetting('callerPassword'),
            'sessionToken' => (($this->_m_bForceSecureToken === true) ? '1234567890' : $iUuId),
            'playerName' => (($this->_m_bAnonUser === true) ? '' : PHP_EOL . '<playerName>' . $this->_m_iUserId . '</playerName>'),
            'currency' => $this->_m_sUserCurrency,
            'gameId' => (isset($p_aParameters['gameId']) ? $p_aParameters['gameId'] : $this->_m_mGameId),
            'gameIDNumber' => (isset($p_aParameters['gameId']) ? $p_aParameters['gameId'] : $this->_m_mGameId),
            'sessionId' => (($this->_m_bForceSecureToken === true) ? '1234567890' : $iUuId),
            'amountBet' => (isset($p_aParameters['amountBet']) ? $this->_m_oGp->convertFromToCoinage($p_aParameters['amountBet'],
                Gp::COINAGE_CENTS, Gp::COINAGE_CENTS) : ''),
            'amountWin' => (isset($p_aParameters['amountWin']) ? $this->_m_oGp->convertFromToCoinage($p_aParameters['amountWin'],
                Gp::COINAGE_CENTS, Gp::COINAGE_CENTS) : ''),
            'reason' => '',
            'clientType' => 0, // desktop
            'transactionRef' => $txn,
            'gameRoundRef' => '', // PHP_EOL . '<gameRoundRef>{{gameRoundRef}}</gameRoundRef>',
            'jackpotAmount' => '', // PHP_EOL . '<jackpotAmount>{{jackpotAmount}}</jackpotAmount>',
            'bonusBet' => '', // PHP_EOL . '<bonusBet>{{bonusBet}}</bonusBet>',
            'bonusWin' => '', // PHP_EOL . '<bonusWin>{{bonusWin}}</bonusWin>'
            'bigWin' => '', // PHP_EOL . '<bigWin>{{bigWin}}</bigWin>'
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
        $this->_m_oGp->setDefaults();
        $sXml = file_get_contents(realpath(dirname(__FILE__)) . '/TestAmatic/request/' . substr($this->_m_sMethod,
                1) . '.xml');
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
        
        $ns = $this->_m_oGp->getSetting('namespace');
        $service = $this->_m_oGp->getSetting('service_request');
        $xmlnsurl = $this->_m_oGp->getNsUrl();
        $gpmethod = $this->_m_oGp->getGpMethodByWalletMethod($this->_m_sMethod);
        
        $p_sXml = "
    <$ns:Envelope xmlns:$ns=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:$service=\"$xmlnsurl\">
    <$ns:Header/>
    <$ns:Body>
    <$service:$gpmethod>
    $p_sXml
    </$service:$gpmethod>
    </$ns:Body>
    </$ns:Envelope>";
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "XML:" . PHP_EOL . $p_sXml;
        }
        
        return phive()->post($this->_m_sUrl, $p_sXml, 'text/xml', '', $this->_m_oGp->getGpName() . '-out', 'POST');
    }
}
