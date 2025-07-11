<?php
class TestLoad extends TestPhive{

  function init($n){
    phive('SQL')->query("UPDATE users SET cash_balance = 1000000");
    $str         = "SELECT * FROM users WHERE active = 1 AND id NOT IN(SELECT user_id FROM users_settings WHERE setting = 'super-blocked') ORDER BY RAND() LIMIT 0,$n";
    $this->ps    = phive('SQL')->loadArray($str);
    $this->games = phive('SQL')->loadArray("SELECT * FROM micro_games WHERE network = 'netent' AND tag = 'videoslots' AND device_type = 'flash' ORDER BY RAND()");
    foreach($this->ps as &$pl){
      shuffle($this->games);
      $pl['game'] = $this->games[0];
    }
    return $this;
  }

  /*
  function initNetent(){
    $net             = TestPhive::getModule('Netent');
    $net->url        = "http://www.videoslots.loc/diamondbet/soap/netent.php";
    $net->caller_id  = 'testmerchant';
    $net->caller_pwd = 'testing';
  }
  
  function loginPlayer($username){
    phive('UserHandler')->login($username, '', false, false);
  }

  function startPlay($username, $gref){
    $this->loginPlayer($username);
    $game = phive('MicroGames')->getByGameRef($gref);
    phive('MicroGames')->onPlay($game);
  }
  */
  
  function doGameRounds($secs = 1){
    for($i = 1; $i <= $secs; $i++){
      foreach($this->ps as $pl){
        $this->sendGameRound($pl);
        usleep(1000000 / count($this->ps));
      }
    }
  }
  
  function sendGameRound(&$pl){
    pclose(popen('php '.__DIR__."/test_load_send.php {$pl['user_id']} {$pl['game']['ext_game_name']} >> /dev/null 2>&1 &", 'r'));
  }
  
}
