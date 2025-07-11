<?php
class TestRedtiger extends TestStandalone
{

    function __construct(){
        $this->rt = phive('Redtiger');
        //$this->url = 'https://bmitbox6.videoslots.com/diamondbet/endpoints/redtiger/rgi/';
        $this->url = 'https://test2.videoslots.com/diamondbet/endpoints/redtiger/rgi/';
        //$this->url = 'http://www.videoslots.loc/diamondbet/endpoints/redtiger.php?';
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    function post($data, $extra_url = ''){
        $json = json_encode($data);
        $url = $this->url.$extra_url;
        $user = $this->rt->getSetting("test_api_user"); 
        $pwd  = $this->rt->getSetting("test_api_pwd"); 
        $key = base64_encode("$user:$pwd");
        echo "Sending: $json\n\n To: $url\n\n";
        $r = phive()->post($url, $json, 'application/json', ["Authorization: Basic ".$key], 'redtigertest');
        echo "Result: \n\n $r \n\n";
    }
    
    function getbalance($token){
        $data = [
            'userToken' => $token,
            'userIP'    => "21.15.185.12",  
            'channel'   => "Macau"          
        ];
        //return $this->post($data, 'action=token&param=user');
        return $this->post($data, 'token/user');
    }

    function winJackpot($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken' => $token,
            'channel'   => 'Some nice channel - not used',
            'roundEnd'  => true, // | false
            'transaction' => [
                'rgsGameId' => $gref,
                'rgsRoundId' => rand(1, 100),
                'rgsTxId'   => $tx_id,
                'amount'    => $amount,
                'currency'  => $currency,
                'details' => [
                    //currently not respected
                    [
                        'action' => 'WIN',
                        'amount' => '10.00',
                        'description' => 'Line wins',
                    ],
                    [
                        'action' => 'WIN',
                        'amount'  => '1000.00',
                        'description' => 'Jackpot win',

                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/payin');
    }

    function winJackpotOnly($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken' => $token,
            'channel'   => 'Some nice channel - not used',
            'roundEnd'  => true, // | false
            'transaction' => [
                'rgsGameId' => $gref,
                'rgsRoundId' => rand(1, 100),
                'rgsTxId'   => $tx_id,
                'amount'    => $amount,
                'currency'  => $currency,
                'details' => [
                    //currently not respected
                    [
                        'action' => 'WIN',
                        'amount'  => '1000.00',
                        'description' => 'Jackpot win',
                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/payin');
    }


    function winMultiplier($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken' => $token,
            'channel'   => 'Some nice channel - not used',
            'roundEnd'  => true, // | false
            'transaction' => [
                'rgsGameId' => $gref,
                'rgsRoundId' => rand(1, 100),
                'rgsTxId'   => $tx_id,
                'amount'    => $amount,
                'currency'  => $currency,
                'details' => [
                    //currently not respected
                    [
                        'action' => 'WIN',  //NO_WIN|BONUS|REFUND|JACKPOT
                        'amount' => '10.00',
                        'description' => 'line',
                    ],
                    [
                        'action' => 'WIN',
                        'amount'  => '10.00',
                        'description' => 'multiplier',

                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/payin');
    }


    function win($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken' => $token,
            'channel'   => 'Malta',
            'roundEnd'  => true, // | false
            'transaction' => [
                'rgsGameId' => $gref,
                'rgsRoundId' => rand(1, 100),
                'rgsTxId'   => $tx_id,
                'amount'    => $amount,
                'currency'  => $currency,
                'details' => [
                    //currently not respected
                    [
                        'action' => 'WIN',  //NO_WIN|BONUS|REFUND|JACKPOT
                        'amount' => '10.00',
                        'description' => 'Line wins',
                    ],
                ]
            ]
        ];
        return $this->post($data, 'transactions/payin');
    }

    function bet($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken'     => $token,
            'channel'       => 'Malta',
            'roundStart'    => false,
            'transaction'   => [
                'rgsGameId' => $gref,
                'rgsRoundId'    => rand(1, 100),
                'rgsTxId'       => $tx_id,
                'amount'        => $amount,
                'currency'      => $currency,
                'details' => [  // I'm not dealig with this part on the server side
                    [
                        'action'        => 'BET',
                        'amount'        => $amount,
                        'description'   => "Any Red"
                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/payout');
    }

    function getTransaction($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken'     => $token,
            'channel'       => 'Malta',
            'roundStart'    => false,
            'transaction'   => [
                'rgsGameId' => $gref,
                'rgsRoundId'    => rand(1, 100), // not used
                'rgsTxId'       => $tx_id,
                'amount'        => $amount,
                'currency'      => $currency,
                'details' => [
                    [
                        'action'        => 'BET',
                        'amount'        => '1.00',
                        'description'   => "Any Red"
                    ],
                    [
                        'action'        => 'BET',
                        'amount'        => '1.00',
                        'description'   => "Position 16"
                    ],
                    [
                        'action'        => 'JACKPOT_CONTRIBUTION',
                        'amount'        => '0.10',
                        'description'   => "Total jackpot contribution from all stakes"
                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/'.$tx_id);
    }

    function refund($token, $gref, $tx_id, $amount, $currency){
        $data = [
            'userToken'     => $token,
            'channel'       => 'Malta',
            'roundStart'    => false,
            'transaction'   => [
                'rgsGameId' => $gref,
                'rgsRoundId'    => rand(1, 100), // not used
                'rgsTxId'       => $tx_id,
                'amount'        => $amount,
                'currency'      => $currency,
                'details' => [
                    [
                        'action'        => 'REFUND', //this is the only thing we need, total is in amount above anyway
                        'amount'        => '1.00',
                        'description'   => "Any Red"
                    ],
                    [
                        'action'        => 'BET',
                        'amount'        => '1.00',
                        'description'   => "Position 16"
                    ]
                ]
            ]
        ];
        return $this->post($data, 'transactions/payin');
    }



    public function putTokenToRedis($userId, $gameId = '', $device = 'desktop')
    {
        $gameId = $this->putRt($gameId);
        $token = mKey($userId, phive()->uuid());
        phMset($token, json_encode(array('token' => $token, 'userId' => $userId, 'gameid' => $gameId, 'device' => $device)));
        return $token;
    }



    /**
     * Get the user data and refresh the token in Redis
     *
     * @param null $userToken
     * @return bool
     */
    public function getDataFromToken($userToken = null){
        $userId = null;
        $tokenData = phMget($userToken);
        if(!empty($tokenData)){
            $tokenArray = json_decode($tokenData, true);
            phM('expire', $userToken, 7200); // +2 hours
            $userId = $tokenArray['userId'];
            $this->session_data = $tokenArray;
        } elseif(strpos($userToken, '[') !== false) {
            $userId = getMuid($userToken);
            if($this->game_action != 'refund')
                return false;
            $a = explode('_', $userToken);
            if(!empty($a[0])){
                $userId = $a[0];
            }
        }

        $this->ud = ud($userId);
        $this->uid = $userId;

        return $tokenArray;
    }

    function putRt($game_id){
        return "redtiger_".$game_id;
    }
}
