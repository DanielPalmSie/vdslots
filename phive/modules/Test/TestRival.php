<?php
class TestRival extends TestStandalone
{

  function __construct(){
      $this->rival = phive('Rival');
      $this->url = "http://www.videoslots.loc/diamondbet/soap/rival.php";
      $this->secret = 'JVUhUwaHK8rGysgbcMKq4jM1ONwStFGE';
      $this->dev_type = 'flash';
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
        $this->user = $user;
        $this->game = $game;
        $sid = uniqid();
        phMset($sid, json_encode(array('user_id' => $user->getId(), 'game_ref' => $game['ext_game_name'], 'lang' => 'en', 'device_type' => $this->dev_type)));             
    }

    function getReq($amount, $min_balance, $mg_id = ''){
        $mg_id = empty($mg_id) ? $this->randId() : $mg_id;
        $req = [
            'id'         => uniqid(),
            'playerid'   => $this->user->getId(),
            'sessionid'  => '',
            'amount'     => $amount,
            'transid'    => $mg_id,
            'gameid'     => $this->game['ext_game_id'],
            'function'   => 'updatebalance'
        ];

        if(empty($min_balance))
            $req['minbalance'] = $min_balance;
        
        return [
            $req,
            hash_hmac('sha256', urldecode(http_build_query($req)), $this->secret)
        ];
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bmg_id = $this->randId();
        $wmg_id = $this->randId();
        //$update_balance = $wamount - $bamount;
        list($breq, $bhash) = $this->getReq(-$bamount / 100, $bamount / 100, $bmg_id); 
        list($wreq, $whash) = $this->getReq($wamount / 100, 0, $wmg_id);
        //Rival will never retry, rollback is issued instead.
        echo $this->updateBalance($breq, $bhash);
        echo $this->updateBalance($wreq, $whash);
    }   

    
  function post($func, $data, $echo = true){
    $json = json_encode($data);
    $options = array(
      'http' => array(
        'method'  	=> 'POST',
        'ignore_errors' => true,
        'header'  	=> 'Content-type: application/json',
        'content' 	=> $json));
    $url = $this->url;
    if($echo)
      echo "Sending: $json\n\n To: $url ($func)\n\n";
    $r = file_get_contents($url, false, stream_context_create($options));
    echo "Result: \n\n $r \n\n";
      return json_decode($r, true);
  }

  function status(){
    return $this->post('status', "");
  }
  function getbalance($uid, $sid, $hash){

    $data = array('playerid' => $uid, 'sessionid' => $sid, 'function' => 'getbalance', 'hmac' => $hash);

    return $this->post('getbalance', $data);
  }
  function validate($uid, $sid, $hash){

    $data = array('playerid' => $uid, 'sessionid' => $sid, 'function' => 'validate', 'hmac' => $hash);
    return $this->post('validate', $data);
  }

  function updateBalance($req, $hash){
    $req['hmac'] = $hash;
    return $this->post('updatebalance', $req);
  }

  function rollback($req, $hash) {
    $req['hmac'] = $hash;
    return $this->post('rollback', $req);
  }

}
