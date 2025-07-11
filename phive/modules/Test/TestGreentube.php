<?php
require_once 'TestGp.php';

class TestGreentube extends TestGp
{
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     *
     * @return mixed Depends on the response of the requested url
     */
    public function exec($p_aAction)
    {
        
        (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        foreach ($p_aAction as $key => $aAction) {
            
            $this->_m_sMethod = $aAction['command'];
            $this->_m_sGpMethod = $this->_m_oGp->getGpMethodByWalletMethod($this->_m_sMethod);
            
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
            
            // this GP provides only one command x request
            break;
        }
        
        return $this->_post($aParameters);
    }
    
    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _urlParams(array $p_aParameters = array())
    {
        
        $a = array();
        $a['TransactionType'] = $this->_m_sGpMethod;
        $a['TransactionCreationDate'] = date('Y-m-d\TH:i:s\.\0\0\0O', time());
        $a['TransactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
        if (isset($p_aParameters['amount'])) {
            $a['Amount'] = (($this->_m_sMethod === '_bet') ? '-' : '') . $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],
                    Gp::COINAGE_CENTS, Gp::COINAGE_CENTS);
        }
        if (in_array($this->_m_sMethod, array('_bet', '_win', '_cancel', '_roundEnd'))) {
            echo 'Test: ' . $p_aParameters['roundid'] . PHP_EOL;
            $a['EntityReferences'][0] = array(
                'EntityType' => 'CasinoRound',
                'EntityId' => (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10))
            );
        }
        $a['CurrencyCode'] = $this->_m_sUserCurrency;
        $a['Game'] = array(
            'GameId' => $this->_m_mGameId,
            'GameName' => 'blabla'
        );
        
        return $a;
    }
    
    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is
     *               send to the url upfront.
     */
    
    protected function _post($p_aData)
    {
        $url = $this->_m_sUrl; 
        if(empty($p_aData['token']) && empty($this->session_token)){
            $sToken = $this->_m_oGp->getGuidv4($this->_m_iUserId);
            $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
            $this->session_token = $sToken;
        } else {
            $sToken = $p_aData['token'] ?? $this->session_token;
        }
        
        $sValue = json_encode($p_aData);

        // Examples from the LIVE access log, your NGINX config needs to be able to handle the rewrites for these things:
        // /diamondbet/soap/greentube.php/Cash/Users/6916445?CurrencyCode=EUR&PartnerUserSessionKey=u69164452b7e1903d3bc47e9e2d3000046412d96&GameId=149
        // /diamondbet/soap/greentube.php/Cash/Users/6443721/Transactions?PartnerUserSessionKey=u64437216f6cc09f6b38a368c34a00003489737c
        // /diamondbet/soap/greentube.php/Entities/CasinoRound/16344345874
        
        if ($this->_m_sMethod === '_balance') {
            $url .= '/Cash/Users/' . $this->_m_iUserId . '?CurrencyCode=' . $this->_m_sUserCurrency . '&GameId=' . $this->_m_mGameId . '&PartnerUserSessionKey=' . $sToken;
            $method = 'GET';
        } elseif ($this->_m_sMethod === '_roundEnd') {
            $url .= '/Entities/CasinoRound';
            $method = 'PUT';
            $sValue = '{
				  "EntityType": "CasinoRound",
				  "EntityId": "' . $p_aData['EntityReferences'][0]['EntityId'] . '",
				  "State": "Finished",
				  "EndDate": "2017-07-13T16:31:37.483",
				  "InitiatorUserId": "' . $this->_m_iUserId . '",
				  "InitiatorCurrencyCode": "' . $this->_m_sUserCurrency . '",
				  "EntityReferences": [
				    {
				      "EntityType": "CasinoRound",
				      "EntityId": "' . $p_aData['EntityReferences'][0]['EntityId'] . '",
				      "References": [],
				      "EntityDetails": {}
				    },
				    {
				      "EntityType": "CasinoSession",
				      "EntityId": "129086",
				      "References": [],
				      "EntityDetails": {}
				    }
				  ],
				  "Game": {
				    "GameId": "' . $this->_m_mGameId . '",
				    "GameName": ""
				  }
				}';
        } else {
            $url .= '/Cash/Users/' . $this->_m_iUserId . '/Transactions?PartnerUserSessionKey=' . $sToken;
            $method = 'POST';
        }
        
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $url . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        return phive()->post($url, $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out', $method) . PHP_EOL . PHP_EOL;
        
    }

    /**
     * @return mixed|void
     */
    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

}
