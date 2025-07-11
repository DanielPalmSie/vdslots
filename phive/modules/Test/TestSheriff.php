<?php
class TestSheriff extends TestStandalone
{
    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    function getSignature($arr){
		$serialized = $this->salt.http_build_query($arr);
		return sha1($serialized);
	}
	
	function post($action, $to_json){
		$to_json['signature'] = $this->getSignature($to_json);
		$json = json_encode($to_json);
	    $options = array(
    		'http' => array(
            	'method'  	=> 'POST',
            	'header'  	=> 'Content-type: application/json',
            	'content' 	=> $json));
	
	    $r = file_get_contents($this->url."?action=$action", false, stream_context_create($options));
		echo "Response: ".$r;
	    return json_decode($r, true);
	}
	
	function uidSid($uid, $sid){
		return array('player_reference' => $uid, 'session_id' => $sid);
	}
	
	function validate($uid, $sid, $xgref = ''){
		$siduid = $this->uidSid($uid, $sid);
		$params = empty($xgref) ? $siduid : array_merge($siduid, array('custom' => array('gref' => $xgref)));
		return $this->post('validate', $params);
	}
	
	function balance($uid, $sid, $gid, $cur){
		return $this->post('balance', array('player_reference' => $uid, 'game_id' => $gid, 'session_id' => $sid, 'currency' => $cur));
	}
	
	function common($action, $real, $bonus, $shid, $uid, $gid, $sid){
		return $this->post($action, array(
			'id' => $shid,
			'player_reference' => $uid,
			'game_id' => $gid,
			'session_id' => $sid,
			'gamehash_id' => rand(1, 1000),
			'gamerun_id' => rand(1, 1000),
			'currency' => 'EUR',
			'transaction' => array('real' => $real, 'bonus' => $bonus),
			'step' => rand(1, 100)
		));
	}
	
	function debit($real, $bonus, $shid, $uid, $gid, $sid){
		return $this->common('debit', $real, $bonus, $shid, $uid, $gid, $sid);
	}
	
	function credit($real, $bonus, $shid, $uid, $gid, $sid){
		return $this->common('credit', $real, $bonus, $shid, $uid, $gid, $sid);
	}
	
	function rollback($real, $bonus, $shid, $uid, $gid, $sid){
		return $this->common('rollback', $real, $bonus, $shid, $uid, $gid, $sid);
	}
	
	function ping(){
		return $this->post('ping');
	}
}
