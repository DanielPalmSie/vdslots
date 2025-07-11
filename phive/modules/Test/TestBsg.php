<?php
require_once 'TestStandalone.php';

class TestBsg extends TestStandalone {

    function __construct(){
        $this->url = "http://www.videoslots.loc/diamondbet/soap/";
        $this->bsg = phive('Bsg');
        phive('Bsg')->test = true;
        $this->token = "002d7318-afc8-af88-5363-00002eb0d363";
        
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
        $this->setToken($this->token, $user->getId());
        $this->setGame($user->getId(), $game['ext_game_name']);
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bet_mg_id = $this->randId();
        $win_mg_id = $this->randId();
        $uid   = $user->getId();
        echo $this->bet($this->url, $uid, $bamount, $game['ext_game_id'], 'false', '', $bet_mg_id);
        echo $this->bet($this->url, $uid, $bamount, $game['ext_game_id'], 'false', '', $bet_mg_id);
        echo $this->win($this->url, $uid, $wamount, $game['ext_game_id'], 'false', '', $win_mg_id);
        echo $this->win($this->url, $uid, $wamount, $game['ext_game_id'], 'false', '', $win_mg_id);
    }

    function getHash($arr){
        $str = implode('', $arr).phive('Bsg')->getLicSetting('pass_key');
        echo $str." :: ";
        return md5($str);
    }
    
    function setGame($uid, $gid){
        phive('Bsg')->setSessData($uid, ['original_game_ref' => $gid, 'device_type' => 'flash']);
    }
    
    function setToken($key, $uid){
        phMset($key, $uid);
    }

  function authenticate($url, $token){
    $url = "{$url}authenticate.do?token=$token&hash=".$this->getHash(array($token));
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  function betWin($url, $params, $negativebet){
    $hash 				= $this->getHash($params);
    $params['negativeBet'] 	= $negativebet;
    $params['action'] 	= 'betresult';
    $pstr 				= http_build_query($params);
    $url 				= "{$url}/betresult.do?$pstr&hash=".$hash;
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

    function bet($url, $uid, $amount, $gid, $rf = 'false', $negativebet = '', $mg_id = ''){
        $mg_id = empty($mg_id) ? rand(1000000, 1000000000) : $mg_id;

        $params = array(
            'userId' 			=> $uid,
            'bet' 			=> "$amount|".$mg_id,
            'isRoundFinished' 	        => $rf,
            'roundId'			=> rand(1000, 1000000),
            'gameId' 			=> $gid
        );
        return $this->betWin($url, $params, $negativebet);
    }

    function win($url, $uid, $amount, $gid, $rf = 'false', $negativebet = '', $mg_id = ''){
        $mg_id = empty($mg_id) ? rand(1000000, 1000000000) : $mg_id;
        $params = array(
            'userId' 			=> $uid,
            'win' 			=> "$amount|".$mg_id,
            'isRoundFinished' 	        => $rf,
            'roundId'			=> rand(1000, 1000000),
            'gameId' 			=> $gid,
        );
        return $this->betWin($url, $params, $negativebet);
    }

  function refundBet($url, $uid, $bid){
    $url = "{$url}/refundBet.do?action=refundBet&userId={$uid}&casinoTransactionId={$bid}&hash=".$this->getHash(array($uid, $bid));
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  function bonusWin($url, $uid, $bonus_id, $amount){
    $params = array(
      'userId' 			=> $uid,
      'bonusId'			=> $bonus_id,
      'amount'			=> $amount
    );
    $hash 						= $this->getHash($params);
    //$params['action'] 			= 'bonuswin';
    $params['transactionId'] 	= rand(1000000, 1000000000);
    $pstr 						= http_build_query($params);
    $url 						= "{$url}/bonuswin.do?$pstr&hash=".$hash;
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

  function accountinfo($url, $uid){
    $url = "{$url}/accountinfo.do?&userId=$uid&hash=".$this->getHash(array($uid));
    echo "Testing $url\n\n";
    return file_get_contents($url);
  }

    function truncBonuses(){
        phive('SQL')->query("TRUNCATE TABLE bonus_types");
        phive('SQL')->query("TRUNCATE TABLE bonus_entries");
    }

    function createFspinBonus($uid, $gid, $ext_ids, $turnover, $status){
        $bonus = array(
            'id' => 10000000,
            'expire_time' => '2100-01-01',
            'num_days' => 100,
            'cost' => 0,
            'reward' => 10,
            'bonus_name' => 'bsg test',
            'rake_percent' => $turnover,
            'bonus_type' => 'freespin',
            'exclusive' => 2,
            'bonus_tag' => 'bsg',
            'type' => 'reward',
            'game_tags' => 'slots,videoslots,scratch-cards,other,roulette,blackjack,videopoker,videoslots_jackpotbsg,videoslots_jackpot,videoslots_jackpotsheriff',
            'game_percents' => '1,1,1,0.1,0.1,0.1,0.1,0,0,0',
            'ext_ids' => $ext_ids,
            'progress_type' => 'bonus',
            'game_id' => $gid
        );
        phive('SQL')->sh($uid)->insertArray('bonus_types', $bonus);
        $entry = array(
            'reward' => 10,
            'bonus_tag' => 'bsg',
            'bonus_id' => 10000000,
            'progress_type' => 'bonus',
            'user_id' => $uid,
            'start_time' => '2010-01-01',
            'end_time' => '2100-01-01',
            'status' => $status,
            'game_tags' => 'slots,videoslots,scratch-cards,other,roulette,blackjack,videopoker,videoslots_jackpotbsg,videoslots_jackpot,videoslots_jackpotsheriff',
            'game_percents' => '1,1,1,0.1,0.1,0.1,0.1,0,0,0',
            'bonus_type' => 'freespin',
            'ext_id' => 1000
        );
        phive('SQL')->sh($uid)->insertArray('bonus_entries', $entry);
    }

    function setRealityCheckInterval($url, $bankId, $uid, $interval) {
        $url = "{$url}setRealityCheckInterval.do?bankId={$bankId}&intervalInSeconds={$interval}&userId={$uid}&hash=".$this->getHash(array($uid,$bankId,$interval));
        echo "Testing $url\n\n";
        return file_get_contents($url);
    }

      function getRealityCheckInterval($url, $bankId, $uid) {
          $url = "{$url}getRealityCheckInterval.do?bankId={$bankId}&userId={$uid}&hash=".$this->getHash(array($uid,$bankId,$interval));
          echo "Testing $url\n\n";
          return file_get_contents($url);
      }

    public function doFullRun($args){
        $uid = $args['uid'];
        $gid = $args['gid'];
        $url = $args['url'];

        $res = $this->setupAjaxInitGameSession($args);
        
        $token = mKey($arg['uid'], phive()->uuid());
        $this->setToken($token, $uid);
        $this->setGame($uid, $gid);
        echo "Authenticate result: \n".$this->authenticate($url, $token)."\n\n";
        echo "Account result: \n".$this->accountinfo($url, $uid)."\n\n";
        echo "Bet result: \n".$this->bet($url, $uid, $args['bet'], $gid)."\n\n";
        echo "Win result: \n".$this->win($url, $uid, $args['win'], $gid)."\n\n";
    }
}
