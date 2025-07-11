<?php
require_once 'TestGp.php';

class TestOryx extends TestGp
{

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
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        foreach ($p_aAction as $key => $aAction) {
            $this->_m_sGpMethod = $aAction['command'];
            $this->_m_sMethod = $this->_m_oGp->getWalletMethodByGpMethod($aAction['command']);
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
        }


        //print_r($aParameters);

        $url = $this->_m_sUrl;

        $method = $this->_m_sGpMethod;

        if(!empty($aParameters['bet'])){
            $method = 'bet';
        }

        if(!empty($aParameters['win'])){
            $method = 'win';
        }

        $map = [
            'authenticate' => $url . '/players/' . $aParameters['token'] . '/authenticate',
            'balance' => $url . '/players/' . $aParameters['playerId'] . '/balance',
            'bet' => $url . '/game-transactions/' . $aAction['parameters']['bet']['transactionId'],
            'win' => $url . '/game-transactions/' . $aAction['parameters']['win']['transactionId']
        ];

        $aParameters['url'] = $map[$method];
        
//        $this->setUrl($this->_m_sUrl . '/players/' . $aParameters['token'] . '/authenticate');
//        $this->setUrl($this->_m_sUrl . '/players/' . $aParameters['playerId'] . '/balance');
//        $this->setUrl($this->_m_sUrl . '/game-transaction'); // this can be a regular transaction or a cancellation of a round
//        $this->setUrl($this->_m_sUrl . '/game-transactions/' . $aAction['parameters']['transactionId']);
        // $this->setUrl($this->_m_sUrl . '/free-rounds/finish'); // when the free round finishes and we update the player's balance with the total winnings

        
        return $this->_post($aParameters);
    }
    
    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.re
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _urlParams(array $p_aParameters = array())
    {
        $a = array();
        $aAddParams = array();

        if(empty($this->sess_key)){
            $sToken = $this->_m_oGp->getGuidv4($p_aParameters['playerId']);
            $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
            $this->sess_key = $sToken;
        } else {
            $sToken = $this->sess_key;
        }
        
        $aAddParams['method'] = $this->_m_sGpMethod;

        $aAddParams['token'] = $sToken;

        if($this->_m_sGpMethod === 'game-transaction') {

            if(isset($p_aParameters['transactionId'])) {
                $aAddParams['transactionId'] = $p_aParameters['transactionId'];
            }
            if(isset($p_aParameters['roundId'])) {
                $aAddParams['roundId'] = $p_aParameters['roundId'];
            }

            if(isset($p_aParameters['roundAction'])) {
                $aAddParams['roundAction'] = $p_aParameters['roundAction'];
            }

            if(isset($p_aParameters['bet'])) {
                $aAddParams['bet'] = array(
                    'transactionId' => (isset($p_aParameters['bet']['transactionId']) ? $p_aParameters['bet']['transactionId'] : $this->_getHash()),
                    'amount' =>  $this->_m_oGp->convertFromToCoinage($p_aParameters['bet']['amount'],
                        Gp::COINAGE_UNITS, Gp::COINAGE_UNITS),
                    'timestamp' => date('Y-m-d\TH:i:s\.\0\0\0O', time()),
                );

                if(isset($p_aParameters['jackpotAmount'])) {
                    $aAddParams['bet']['jackpotAmount'] = $p_aParameters['jackpotAmount'];
                }
            }

            if(isset($p_aParameters['win'])) {
                $aAddParams['win'] = array(
                    'transactionId' => (isset($p_aParameters['win']['transactionId']) ? $p_aParameters['win']['transactionId'] : $this->_getHash()),
                    'amount' =>  $this->_m_oGp->convertFromToCoinage($p_aParameters['win']['amount'],
                        Gp::COINAGE_UNITS, Gp::COINAGE_UNITS),
                    'timestamp' => date('Y-m-d\TH:i:s\.\0\0\0O', time()),

                );
            }

            if(isset($p_aParameters['freeRoundId'])) {
                $aAddParams['freeRoundId'] = $p_aParameters['freeRoundId'];
            }if(isset($p_aParameters['freeRoundExternalId'])) {
                $aAddParams['freeRoundExternalId'] = $p_aParameters['freeRoundExternalId'];
            }

        } else if ($this->_m_sGpMethod == 'freeround-end') {
            $aAddParams['playerId'] = $this->_m_iUserId;
            $aAddParams['freeRoundId'] = $p_aParameters['freeRoundId'];
            $aAddParams['freeRoundExternalId'] = $p_aParameters['freeRoundExternalId'];
            $aAddParams['wins'] = $p_aParameters['wins'];
            $aAddParams['gameCode'] = $p_aParameters['gameCode'];
            $aAddParams['transactionId'] = $p_aParameters['transactionId'];
        }

        if(isset($p_aParameters['action']) || isset($p_aParameters['roundAction'])) {
            if(isset($p_aParameters['action'])) {
                $aAddParams['action'] = $p_aParameters['action'];
                if($p_aParameters['action'] == 'CANCEL') {
                    if(isset($p_aParameters['transactionId'])) {
                        $aAddParams['transactionId'] = $p_aParameters['transactionId'];
                    } else if(isset($p_aParameters['transactionIds'])) {
                        $aAddParams['transactionIds'] = $p_aParameters['transactionIds'];
                    }
                }
            } else if(isset($p_aParameters['roundAction'])) {
                $aAddParams['roundAction'] = $p_aParameters['roundAction'];
                if($p_aParameters['roundAction'] == 'CANCEL') {
                    if(isset($p_aParameters['transactionId'])) {
                        $aAddParams['transactionId'] = $p_aParameters['transactionId'];
                    } else if(isset($p_aParameters['transactionIds'])) {
                        $aAddParams['transactionIds'] = $p_aParameters['transactionIds'];
                    }
                }
            }
        }


        // authentication
        if($this->_m_sGpMethod !== 'authenticate') {
            if(!isset($aAddParams['wins'])) {
                $aAddParams['playerId'] = $this->_m_iUserId;
            }
        }

        if($this->_m_sGpMethod !== 'freeround-end') {
            $aAddParams['gameCode'] = $this->_m_mGameId;
        }


        $a = $aAddParams;
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
        $url = $p_aData['url'];
        unset($p_aData['url']);
        
        $sValue = json_encode($p_aData);
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $url . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        
        return phive()->post($url, $sValue, 'application/json', '', 'oryx-out', 'POST') . PHP_EOL;
    }

    
    public function doFullRun($args){
        $this->setGameId($args['gid'])->forceSecureToken(false)->setUserId($args['uid'])->setUrl($args['url'])->outputRequest(true);

        $res = $this->setupAjaxInitGameSession($args);

        $aAction = array(array('command' => 'authenticate', 'parameters' => []));
        $res = $this->exec($aAction); 
        echo "\nAuth Result: $res \n\n";
        
        $aAction = array(array('command' => 'balance', 'parameters' => []));
        $res = $this->exec($aAction); 
        echo "\nBalance Result: $res \n\n";
        
        $aAction = array(array('command' => 'game-transaction','parameters' => array(
            'bet' => [
                'amount' => $args['bet'],
                'transactionId' => $args['mg_id'],
            ],
        )));
        $res = $this->exec($aAction); 
        echo "\nBet Result: $res \n\n";

        $aAction = array(array('command' => 'game-transaction','parameters' => array(
            'win' => [
                'amount' => $args['win'],
                'transactionId' => $args['mg_id'],
            ],
        )));
        
        $res = $this->exec($aAction);
        echo "\nWin Result: $res \n";
    }
}
