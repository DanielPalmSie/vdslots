<?php
class TestZignSec extends TestPhive{

    function __construct(){
        $this->db     = phive('SQL');
        $this->zs     = phive('DBUserHandler/ZignSec');
        $this->config = phive('UserHandler')->getSetting('zignsec_v2');
    }

    function testStart($u, $new_nid = '', $action = 'Authenticate', $context = 'login'){
        $start_data = ['sid' => session_id(), 'uid' => $uid, 'context' => $context];
        $res = $this->zs->extvIdStart($u, $new_nid, $action);
        print_r($res);
        $req_id = $res['result']['id'];
        // We make use of the returned request id by using it both as a key for Redis data and we also store it in the session.
        phMsetArr($req_id.'.start', $start_data);
    }

    /*
       // Not used atm
    function testPoll($u, $nid = ''){
        $_SESSION['current_nid'] = $nid;
        $res = $this->zs->getBankIdResult($u, phMget('cur-bid-orderref-test'));
        $this->printJson($res);
    }
    */
    
    function testHook($nid, $u = null){
        $zs_id = 'abc123';

        $arr = [
            'id' => $zs_id
        ];

        if(is_array($u)) {
            $arr['identity'] = [
                'PersonalNumber' => $nid,
                'FirstName'      => strtoupper($u['firstname']),
                'LastName'       => strtoupper($u['lastname']),
                'FullName'       => strtoupper($u['firstname'].' '.$u['lastname']),
                'CountryCode'    => strtoupper($u['country'])
            ];  
        } else {
            $arr['identity'] = [
                'PersonalNumber' => $nid,
                'FirstName'      => strtoupper($u->getAttr('firstname')),
                'LastName'       => strtoupper($u->getAttr('lastname')),
                'FullName'       => strtoupper($u->getFullName()),
                'CountryCode'    => strtoupper($u->getCountry())
            ];
        }
        
        $json = json_encode($arr);
        $hash = hash_hmac('sha256', $json, $this->config['auth_key']);
        $url  = phive()->getSiteUrl().'/phive/modules/DBUserHandler/json/zignsec_bankid_webhook.php';
        $res  = phive()->post($url, $json, 'application/json', ["X-ZignSec-Hmac-SHA256: $hash"], 'zsec-webhook-test');

        print_r($res);
        
    }

    function getExtData($u, $nid, $req_id = null){
        $res = $this->zs->getExtData($u->getCountry(), $nid, $req_id);
        print_r($res);
    }
    
}
