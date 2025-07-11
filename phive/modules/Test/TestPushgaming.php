<?php
require_once 'TestGp.php';

class TestPushgaming extends TestGp
{
    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    public function exec($args){

    }

    /**
     * Post the data in JSON format
     *
     * @param array $p_arr An array with data to post.
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is
     *               send to the url upfront.
     */
    protected function _post($p_arr)
    {
        $json = null;
        if(!empty($p_arr['params'])){
            $json = json_encode($p_arr['params']);
        }

        $url = $this->url . '/rgs/hive/' . $p_arr['url'];
        //$url = $this->url . '/rgs/hive/player/mupp';

        $headers = [
            'Operator-API-Key: '.$this->_m_oGp->getSetting('api_key')
            //'Operator-API-Key: apa'
        ];

        if(!empty($this->sess_key)){
            $headers[] = 'Authorization: Bearer '.$this->sess_key;
        }

        echo 'URL: ' . $url . PHP_EOL . "DATA: " . $json;

        $res = phive()->post($url, $json, 'application/json', $headers, $this->_m_oGp->getGpName() . '-out', $p_arr['method'] ?? 'POST', '', [], 'UTF-8', true);
        list($res_body, $res_code, $res_headers) = $res;
        echo "\n{$p_arr['action']} Result Code: {$res_headers['http_code']}";
        echo "\n{$p_arr['action']} Result Body:\n";
        print_r(json_decode($res_body, true));
        echo "\n\n";
    }

    public function getBaseParams(){
        return [
            'ipAddress' => '127.0.0.1',
            'channel' => 'PC',
            'clientType' => 'FLASH',
            'userAgent' => 'Mozilla/5.0'
        ];
    }

    public function authorize($args){
        $this->sess_key = $this->_m_oGp->getGuidv4($args['uid']);
        $this->_m_oGp->toSession($this->sess_key, $args['uid'], $args['gref'], $args['device']);
        $arr = [
            'url'    => 'player/auth?rgsGameId='.$this->_m_oGp->stripPrefix($args['gref']),
            'action' => 'auth',
            'method' => 'POST',
            'params' => $this->getBaseParams()
        ];
        return $this->_post($arr);
    }

    public function wallet($args){
        $arr = [
            'url'    => "player/{$args['uid']}/wallet",
            'action' => 'wallet',
            'method' => 'GET',
        ];
        return $this->_post($arr);
    }

    public function txn($args, $type, $amount, $actions = null, $txn_deadline = null, $test_idempotency = false){
        $complete = $type == 'WIN' || is_array($actions);

        $actions = $actions ?? [
                [
                    'rgsActionId' => phive()->uuid(),
                    'amount' => $amount / 100,
                    'type' => $type
                ]
            ];

        $u_obj = cu($args['uid']);
        $arr = [
            'params' => [
                'txnDeadline' => $txn_deadline ?? phive()->hisMod('+1 day', null, 'Y-m-d\TH:i:s\.B\Z'),
                'rgsTxnId' => phive()->uuid(),
                'rgsPlayId' => phive()->uuid(),
                'rgsRoundId' => phive()->uuid(),
                'playerId' => $args['uid'],
                'playComplete' => phive()->getJsBool($complete),
                'roundComplete' => phive()->getJsBool($complete),
                'currency' => $u_obj->getCurrency(),
                'actions' => $actions,
                'rgsGameId' => $this->_m_oGp->stripPrefix($args['gref'])
            ],
            'action' => 'txn',
            'method' => 'POST',
            'url'    => 'txn'
        ];

        $this->_post($arr);

        if($test_idempotency){
            $this->_post($arr);
        }

        return $arr;
    }

    public function cancel($args, $txn_id, $test_idempotency = false){

        $u_obj = cu($args['uid']);
        $arr = [
            'params' => [
                'playerId' => $args['uid'],
                'playComplete' => phive()->getJsBool(true),
                'roundComplete' => phive()->getJsBool(true),
            ],
            'action' => 'cancel',
            'method' => 'POST',
            'url'    => "txn/$txn_id/cancel"
        ];

        if($test_idempotency){
            $this->_post($arr);
        }

        return $this->_post($arr);
    }


    public function frbWin($args, $entry_id){
        $res = $this->setupAjaxInitGameSession($args);
        //print_r($res);

        $this->url = $args['url'];
        $this->authorize($args);

        $this->txn($args, null, null, [[
            'rgsActionId' => $entry_id,
            'amount'      => $args['win'] / 100,
            'type'        => 'RGS_FREEROUND_CLEARDOWN'
        ]]);
    }

    /*
       curl --location --request POST 'https://mesh.eu.integration.dev.pushgaming.com/mesh/test/all' \
       --header 'Content-Type: application/json' \
       --header 'API-Key: testApiKey' \
       --header 'Accept: application/json' \
       --data-raw '{
       "igpCode": "iguana",
       "ccyCode": "GBP"not-real"rgsGameId": "fatrabbit1",
       "playerId": "f35b2d19-f1eb-452f-84ed-42c3f69cc102",
       "playerAuthToken": "30b70373-bb2d-4efe-96e5-30776c3cba47",
       "expiredPlayerId": "f35b2d19-f1eb-452f-84ed-42c3f69cc102",
       "expiredPlayerAuthToken": "51e7a744-6b3a-4153-bc60-ef8c2e839dd3",
       "disableLaunch": "true",
       "disableFreeroundsTests": "false",
       "useTxnDeadlineTombstone": "true",
       "lobbyUrl": "",
       "accountUrl": "",
       "nonWhitelistedLobbyUrl": "",
       "nonWhitelistedAccountUrl": "",
       "expiredAccessToken": "51e7a744-6b3a-4153-bc60-ef8c2e839dd3",
       "freeRoundsId": "not-real"
       }'
    */
    public function runAllMeshTests($args, $token){
        $u_obj = cu($args['uid']);
        $headers = ["API-Key: ".$this->_m_oGp->getLicSetting('mesh_test_key')];
        $params = [
            "igpCode"                 => $this->_m_oGp->getLicSetting('igp_code'),
            "ccyCode"                 => $u_obj->getCurrency(),
            "rgsGameId"               => "blazeofra1-01",
            "playerId"                => $args['uid'],
            "playerAuthToken"         => $token,
            "expiredPlayerId"         => "51e7a744-6b3a-4153-bc60-ef8c2e839df3",
            "expiredPlayerAuthToken"  => "5235886u6ba044877f5c6928e28f00aaa6665275",
            "disableLaunch"           => true,
            "disableFreeroundsTests"  => true,
            "useTxnDeadlineTombstone" => true,
            "lobbyUrl"                 => "",
            "accountUrl"               => "",
            "nonWhitelistedLobbyUrl"   => "",
            "nonWhitelistedAccountUrl" => "",
            "expiredAccessToken"       => "5235886u6ba044877f5c6928e28f00aaa6665275",
            "freeRoundsId"             => "not-real"
        ];

        $url = 'https://mesh.eu.integration.dev.pushgaming.com/mesh/test/all';
        $res = phive()->post($url, $params, 'application/json', $headers, 'pushgaming-mesh-all');
        return $res;
    }

    public function getError($key){
        return $this->_m_oGp->setDefaults()->getError($key);
    }

    public function response($key){
        return $this->_m_oGp->setDefaults()->response($key);
    }


    public function missingOrExpiredToken($args){
        $res = $this->setupAjaxInitGameSession($args);
        //print_r($res);

        $this->url = $args['url'];
        $this->authorize($args);
        $this->wallet($args);
        $this->sess_key = '5235886u6ba044877f5c6928e28f00aaa6665275';
        $this->txn($args, 'STAKE', $args['bet']);
    }

    public function doCancel($args){
        $this->url = $args['url'];
        $this->authorize($args);
        $this->wallet($args);
        $arr = $this->txn($args, 'STAKE', $args['bet']);
        $this->cancel($args, $arr['params']['rgsTxnId'], true);
    }

    public function tombstone($args){
        $res = $this->setupAjaxInitGameSession($args);
        $this->url = $args['url'];
        $this->authorize($args);
        $this->wallet($args);
        $this->txn($args, 'STAKE', $args['bet'], null, phive()->hisMod('-1 day', null, 'Y-m-d\TH:i:s\.B\Z'));
    }

    public function doFullRun($args){
        $res = $this->setupAjaxInitGameSession($args);
        print_r($res);

        $this->url = $args['url'];
        $this->authorize($args);
        $this->wallet($args);

        // Tombstone test
        //$this->txn($args, 'STAKE', $args['bet'], null, phive()->hisMod('-1 day', null, 'Y-m-d\TH:i:s\.B\Z'));
        //exit;

        // Cancel test
        //$arr = $this->txn($args, 'STAKE', $args['bet']);
        //$this->cancel($args, $arr['params']['rgsTxnId'], true);

        // To test Insufficient funds
        //cu($args['uid'])->setAttr('cash_balance', 0);

        // Normal play with bet and win
        //$this->txn($args, 'STAKE', $args['bet']);

        // idempotency test
        $this->txn($args, 'STAKE', $args['bet'], null, null, true);

        //cu($args['uid'])->setAttr('cash_balance', 100000);

        $this->txn($args, 'WIN', $args['win']);

    }


}
