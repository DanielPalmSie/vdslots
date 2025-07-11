<?php
require_once 'TestGp.php';

class TestNolimit extends TestGp
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
            $this->_m_sGpMethod = $aAction['command'];
            $this->_m_sMethod = $this->_m_oGp->getWalletMethodByGpMethod($aAction['command']);
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
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
        
        $a = $aAddParams = $aParams = array();
        $sToken = $this->_m_oGp->getGuidv4($this->_m_iUserId);
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        
        $a['jsonrpc'] = '2.0';
        $a['method'] = $this->_m_sGpMethod;
        $a['id'] = $this->_m_oGp->getGuidv4();
        $a['params'] = array();
        $a['params']['identification'] = array('name' => $this->_m_oGp->getSetting('operator'), 'key' => $this->_m_oGp->getSetting('secretkey'));
        
        if ($this->_m_sGpMethod !== 'wallet.validate-token') {
            $a['params']['userId'] = $this->_m_iUserId;
            $a['params']['token'] = $sToken;
        } else {
            $a['params']['extId1'] = $sToken;
            $a['params']['game'] = $this->_m_mGameId;
        }
        
        if(in_array($this->_m_sGpMethod, array('wallet.rollback', 'wallet.withdraw', 'wallet.deposit'))) {
            $a['params']['information']['gameRoundId'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10));
            $a['params']['information']['time'] = date('Y-m-d\TH:i:s\.\0\0\0O', time());
            $a['params']['information']['game'] = $this->_m_mGameId;
            $a['params']['information']['uniqueReference'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
            if(isset($p_aParameters['freespinid'])){
                $a['params']['promoName'] = $p_aParameters['freespinid'];
            }
            
            if($this->_m_sGpMethod !== 'wallet.rollback') {
                $a['params'][str_replace('wallet.','',$this->_m_sGpMethod)] = array('amount' => $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS, Gp::COINAGE_UNITS), 'currency' => $this->_m_sUserCurrency);
            }
        }
        
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
        
        $sValue = json_encode($p_aData);
        echo $sValue;
        $sValue = '{"jsonrpc":"2.0","method":"wallet.withdraw","params":{"identification":{"name":"VIDEOSLOTS","key":"szNhfqSP"},"userId":"5235889","extId1":"u52358890632fc63b464fe88f3ad00005235f55d","withdraw":{"amount":0.20,"currency":"EUR"},"information":{"uniqueReference":"W2499908-1","gameRoundId":"2499908","game":"Oktoberfest","time":"2017-11-30 09:50:11"}},"id":"fb7ee673-5adb-4c3c-9b9a-e97473c885ac"}';
        
        $this->_m_sUrl .= '?action=' . $this->_m_sGpMethod;
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        return phive()->post($this->_m_sUrl, $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out', 'POST') . PHP_EOL;
        
    }
}
