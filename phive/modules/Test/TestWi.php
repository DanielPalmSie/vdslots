<?php
class TestWi extends TestStandalone
{

    function __construct(){
        $this->url = "http://www.videoslots.loc/diamondbet/soap/wi.php?action=";
        $this->context = 'DESKTOP';
        $this->ticket = 'SUCCESS';
        $this->wi = phive('Wi');
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
        $this->setTicket($user->getId(), $this->ticket);
    }

    function testIdempotency($user, $game, $bamount, $wamount){
        $bmg_id = $this->randId();
        $wmg_id = $this->randId();
        echo $this->bet($user->getId(), $game['ext_game_id'], $this->ticket, $bamount, $bmg_id, $bmg_id, $user->getCurrency());
        echo $this->bet($user->getId(), $game['ext_game_id'], $this->ticket, $bamount, $bmg_id, $bmg_id, $user->getCurrency());
        echo $this->win($user->getId(), $game['ext_game_id'], $this->ticket, $wamount, $wmg_id, $wmg_id, $user->getCurrency());
        echo $this->win($user->getId(), $game['ext_game_id'], $this->ticket, $wamount, $wmg_id, $wmg_id, $user->getCurrency());
    }

    
  function post($arr, $action){
    $xml_action     = $this->wi->actionMap($action);
    $url            = $this->url.$action;
    $xml            = '';
    $arr['context'] = $this->context;
    list($xml_type, $xml_ns) = $this->wi->getTypeNs($action);
    //$xml_ns = 'ns3';
    foreach($arr as $key => $val)
      $xml .= "<$key>$val</$key>";
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><'.$xml_ns.':'.$xml_action.' xmlns:ns2="http://williamsinteractive.com/integration/vanilla/api/common" xmlns:ns3="http://williamsinteractive.com/integration/vanilla/api/player" xmlns:ns4="http://williamsinteractive.com/integration/vanilla/api/transaction">
     '.$xml.'
     </'.$xml_ns.':'.$xml_action.'>';
    echo "Sending: \n\n $xml \n\n to $url";
    $res = phive()->post($url, $xml, 'application/xml');
    echo "\n\n Got $res back \n\n";
  }

  function authenticate($uid, $gref, $ticket){
    return $this->post(array('accountRef' => $uid, 'gameCode' => $gref, 'ticket' => $ticket), 'authenticate');
  }

  function getBalance($uid, $gref, $ticket){
    return $this->post(array('accountRef' => $uid, 'gameCode' => $gref, 'ticket' => $ticket), 'getBalance');
  }

  function bet($uid, $gref, $ticket, $amount, $tid, $rid, $currency){
    return $this->post(array('accountRef' => $uid, 'gameCode' => $gref, 'ticket' => $ticket, 'amount' => $amount, 'gameRoundId' => $rid, 'transactionId' => $tid, 'currency' => $currency), 'transferToGame');
  }

  function win($uid, $gref, $ticket, $amount, $tid, $rid, $currency){
    return $this->post(array('accountRef' => $uid, 'gameCode' => $gref, 'ticket' => $ticket, 'amount' => $amount, 'gameRoundId' => $rid, 'transactionId' => $tid, 'currency' => $currency), 'transferFromGame');
  }

  function rollback($uid, $gref, $cancel_tid){
    return $this->post(array('accountRef' => $uid, 'gameCode' => $gref, 'canceledTransactionId' => $cancel_tid), 'cancelTransferToGame');    
  }
  
  function setTicket($uid, $ticket){
    phMset(mKey($uid, 'wi-ticket'), $ticket);
  }
  
  
  
}
