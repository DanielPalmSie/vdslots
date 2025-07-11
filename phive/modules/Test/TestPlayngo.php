<?php
require_once 'TestStandalone.php';

class TestPlayngo extends TestStandalone
{

    function __construct(){
        $this->url = "http://www.videoslots.loc/diamondbet/soap/playngo.php";
        $this->token = uniqid();
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    function prepare($user, $game){
        phMset("playngo-{$user->getUsername()}", $user->getId());
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bet_mg_id = $this->randId();
        $win_mg_id = $this->randId();
        $uid = $user->getId();
        echo $this->reserve($uid, $bamount / 100, $bet_mg_id, $game['ext_game_name'], $bet_mg_id, $this->token);
        echo $this->reserve($uid, $bamount / 100, $bet_mg_id, $game['ext_game_name'], $bet_mg_id, $this->token);
        echo $this->release($uid, $wamount / 100, $win_mg_id, $game['ext_game_name'], $win_mg_id, $this->token);
        echo $this->release($uid, $wamount / 100, $win_mg_id, $game['ext_game_name'], $win_mg_id, $this->token);
    }

    
  function post($xml){
    $options = array(
      'http' => array(
        'method'  	=> 'POST',
        'header'  	=> 'Content-type: application/json',
        'content' 	=> $xml));
    echo "Sending: \n\n$xml\n\n To: {$this->url}\n\n";
    $r = file_get_contents($this->url, false, stream_context_create($options));
    return "$r\n\n";
  }
  
  function authenticate($username, $token){
    $xml = "<authenticate>
			<username>$username</username>
			<password>abc</password>
			<extra>xyz</extra>
			<productId>1</productId>
			<CID></CID>
			<clientIP>1.2.3.4</clientIP>
			<contextId>0</contextId>
			<accessToken>$token</accessToken>
			<language>en_GB</language>
		</authenticate>";
    return $this->post($xml);
  }
  
  function balance($uid, $gid, $token){
    $xml = "<balance>
			<externalId>$uid</externalId>
			<productId>1</productId>
			<currency>EUR</currency>
			<gameId>$gid</gameId>
			<accessToken>$token</accessToken>
			</balance>";	
    return $this->post($xml);
  }
  
  function reserve($uid, $amount, $tid, $gid, $rid, $token){
    $xml = "<reserve>
			<externalId>$uid</externalId>
			<productId>1</productId>
			<transactionId>$tid</transactionId>
			<real>$amount</real>
			<currency>EUR</currency>
			<gameId>$gid</gameId>
			<gameSessionId>abc</gameSessionId>
			<contextId>abc</contextId>
			<accessToken>$token</accessToken>
			<roundId>$rid</roundId>
			</reserve>";	
    return $this->post($xml);
  }
  
  function cancelReserve($uid, $amount, $tid, $rid, $token){
    $xml = "<cancelReserve>
			<externalId>$uid</externalId>
			<productId>1</productId>
			<transactionId>$tid</transactionId>
			<real>$amount</real>
			<currency>EUR</currency>
			<accessToken>$token</accessToken>
			<roundId>$rid</roundId>
			</cancelReserve>";	
    return $this->post($xml);
  }

  function freespinRelease($uid, $amount, $tid, $gid, $rid, $token, $entry_id){
    $xml = "<release>
            <externalId>$uid</externalId>			
	    <productId>1</productId>
	    <transactionId>$tid</transactionId>
	    <real>$amount</real>
	    <currency>EUR</currency>
	    <gameId>$gid</gameId>
	    <gameSessionId>abc</gameSessionId>
	    <contextId>abc</contextId>
	    <accessToken>$token</accessToken>
	    <roundId>$rid</roundId>
            <freegameExternalId>$entry_id</freegameExternalId>
	</release>";	
    return $this->post($xml);
  }
  
  function release($uid, $amount, $tid, $gid, $rid, $token){
    $xml = "<release>
			<externalId>$uid</externalId>
			<productId>1</productId>
			<transactionId>$tid</transactionId>
			<real>$amount</real>
			<currency>EUR</currency>
			<gameId>$gid</gameId>
			<gameSessionId>abc</gameSessionId>
			<contextId>abc</contextId>
			<accessToken>$token</accessToken>
			<roundId>$rid</roundId>
			</release>";	
    return $this->post($xml);
  }

  function frbRelease($uid, $amount, $tid, $gid, $rid, $token, $bid){
    $xml = "<release>
              <externalId>$uid</externalId>
              <productId>1</productId>
              <transactionId>$tid</transactionId>
              <real>$amount</real>
              <currency>EUR</currency>
              <gameSessionId>abc</gameSessionId>
              <contextId>0</contextId>
              <state>0</state>
              <type>1</type>
              <gameId>$gid</gameId>
              <accessToken>$token</accessToken>
              <roundId>$rid</roundId>
              <jackpotGain>0.00</jackpotGain>
              <jackpotLoss>0.00</jackpotLoss>
              <freegameExternalId>$bid</freegameExternalId>
              <turnover>0</turnover>
              <freegameFinished>0</freegameFinished>
              <externalGameSessionId/>
          </release>";	
    return $this->post($xml);
  }
  
  
}
