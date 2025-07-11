<?php
class TestMultislot extends TestStandalone{

    function __construct(){
        $this->url = "http://www.videoslots.loc/diamondbet/soap/multislot.php";
        $this->pwd = uniqid();
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
        $this->initSess($this->token, $this->pwd, $user->getId(), $game['ext_game_name']);
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bet_mg_id = $this->randId();
        $win_mg_id = $this->randId();
        echo $this->bet($this->token, $bet_mg_id, $bamount)."\n";
        echo $this->bet($this->token, $bet_mg_id, $bamount)."\n";
        echo $this->win($this->token, $win_mg_id, $wamount)."\n";
        echo $this->win($this->token, $win_mg_id, $wamount)."\n";
    }

    
  function initSess($token, $pwd, $user_id, $gref){
    phMset("multislot-password-{$user_id}", $pwd);
    phM('hmset', $token, array('gref' => $gref, 'user_id' => $user_id, 'password' => $pwd));
  }
  
  function authenticate($pwd, $uid){
    return $this->get('authenticate', array('password' => $pwd, 'user_id' => $uid));
  }

  function getBalance($token){
    return $this->get('getBalance', array('token' => $token));
  }

  function bet($token, $tid, $amount){
    return $this->get('bet', array('token' => $token, 'transaction_id' => $tid, 'amount' => $amount));
  }

  function win($token, $tid, $amount){
    return $this->get('win', array('token' => $token, 'transaction_id' => $tid, 'amount' => $amount));
  }

  function rollback($tid){
    return $this->get('rollback', array('transaction_id' => $tid));
  }
  
  function get($action, $req){
    $str = http_build_query(array_merge(array('action' => $action), $req));
    echo $this->url."?".$str."\n";
    return file_get_contents($this->url."?".$str);
  }
}
