<?php
class TestYggdrasil
{

    function __construct(){
        $this->url = 'http://www.videoslots.loc/diamondbet/soap/yggdrasil.php';
        $this->token = uniqid();
        $this->ygg = phive('Yggdrasil');
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
        $this->ygg->insertToken($this->token, $user->getId(), $game['ext_game_name']);
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bmg_id = $this->randId();
        $wmg_id = $this->randId();
        echo $this->wager($bamount / 100, $bmg_id, $bmg_id, $user->getId());
        echo $this->endwager($wamount / 100, $wmg_id, $wmg_id, $user->getId());
    }
    
    function getRes($action, $arr){
        $url = $this->url."?action=$action&".http_build_query($arr);
        echo "Calling: $url \n";
        return file_get_contents($url)."\n";
    }

    function playerInfo(){
        return $this->getRes('playerinfo', array('sessiontoken' => $this->token));
    }

    function getbalance(){
        return $this->getRes('getbalance', array('sessiontoken' => $this->token));
    }

    function wager($amount, $mg_id, $tr_id, $uid){
        $res = $this->getRes('wager', array(
            'sessiontoken'  => $this->token,
            'reference'     => $mg_id,
            'amount'     => "$amount",
            'playerid'     => $uid,
            'subreference'  => $tr_id));
        echo "Wager result: $res";
    }

    function endwager($amount, $mg_id, $tr_id, $uid, $action = 'endwager'){
        $res = $this->getRes($action, [
            'sessiontoken'  => $this->token,
            'reference'     => $mg_id,
            'amount'     => "$amount",
            'playerid'     => $uid,
            'subreference'  => $tr_id
        ]);

        echo "Win result: $res";
    }

    function appendwagerresult($amount, $mg_id, $tr_id, $uid){
        return $this->endwager($amount, $mg_id, $tr_id, $uid, 'appendwagerresult');
    }
  
    function appendwagergoods($amount, $mg_id, $tr_id, $uid){
        return $this->endwager($amount, $mg_id, $tr_id, $uid, 'appendwagergoods');
    }

    function refund($mg_id, $tr_id, $action, $uid){
        return $this->getRes($action, array(
            'sessiontoken'  => $this->token,
            'playerid'     => $uid,
            'subreference'  => $tr_id,
            'reference'     => $mg_id));
    }

}
