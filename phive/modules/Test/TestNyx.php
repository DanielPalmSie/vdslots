<?php
class TestNyx extends TestPhive
{

    function __construct(){
        $this->url = "http://www.videoslots.loc/diamondbet/soap/nyx.php";
        $this->token = "6a7a19e9-18d0-ac49-3ac1-0000107b257e";
        $this->apiversion = '1.2';
        $this->nyx = phive('Nyx');
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
        $this->setToken($this->token, $user->getId(), $game['ext_game_name']);
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bet_mg_id = $this->randId();
        $win_mg_id = $this->randId();
        echo $this->wager($this->url, $this->token, $user->getId(), $bet_mg_id, $bamount / 100, $game['ext_game_id']);
        echo $this->wager($this->url, $this->token, $user->getId(), $bet_mg_id, $bamount / 100, $game['ext_game_id']);
        echo $this->win($this->url, $this->token, $user->getId(), $win_mg_id, $wamount / 100, $game['ext_game_id']);
        echo $this->win($this->url, $this->token, $user->getId(), $win_mg_id, $wamount / 100, $game['ext_game_id']);
    }


  function setToken($token, $uid, $gref, $extra = []){
      $tarr = array_merge(['user_id' => $uid, 'game_ref' => $gref, 'token' => $token], $extra);
      phM('hmset', $token, $tarr, 1800);
      $this->nyx->updateToken($token, 'device_type', 'flash');
  }

    function getUrl($base, $token, $request){
        if(empty($token)){
            // We use the token returned by the getaccount() call.
            $token = $this->token;
        }
        //$token_arr = $this->nyx->loadToken($token);
        $device_map = ['flash' => 'desktop', 'html5' => 'mobile'];
        $nyx = phive('Nyx');
        if($this->apiversion === '1.2')
            $sess_key = $request == 'getaccount' ? 'sessionid' : 'gamesessionid';
        else
            $sess_key = 'sessionid';
        return "$base?".http_build_query(array(
            'apiversion' => $this->apiversion,
            'loginname' => $nyx->getSetting('loginname'),
            'password' => $nyx->getSetting('password'),
            $sess_key => $token,
            'device' => 'desktop',
            'request' => $request));
    }

  function getbalance($url, $token){
    $url = $this->getUrl($url, $token, 'getbalance');
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  function getaccount($url, $token){
      $url = $this->getUrl($url, $token, 'getaccount');
      echo "Testing $url\n\n";
      $res = file_get_contents($url);
      preg_match('|(<GAMESESSIONID>)(.+)(</GAMESESSIONID>)|', $res, $m);
      $this->token = $m[2];
      return $res;
  }

  function wager($url, $token, $uid, $mg_id, $amount, $gid, $key = 'betamount', $request = 'wager'){
    $roundid = rand(100, 10000);
    $gpid = 100;
    $url = $this->getUrl($url, $token, $request).'&'.http_build_query(array(
      'accountid' => $uid, 'transactionid' => $mg_id, $key => $amount,
      'nogsgameid' => $gid, 'gpid' => $gpid, 'roundid' => $roundid));
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  function win($url, $token, $uid, $mg_id, $amount, $gid, $key = 'result'){
    return $this->wager($url, $token, $uid, $mg_id, $amount, $gid, $key, 'result');
  }

  function rollback($url, $token, $uid, $mg_id, $amount, $gid, $key = 'rollbackamount', $request = 'rollback'){
    return $this->wager($url, $token, $uid, $mg_id, $amount, $gid, $key, $request);
  }

  function ping($url){
    $url = $this->getUrl($url, '', 'ping');
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  //rollback and rollback of bet with bonus part

}
