<?php
class TestGamesos extends TestPhive{

  function post($action, $arr){
    $arr['merch_pwd'] = $this->merch_pwd;
    $arr['merch_id'] = $this->merch_id;
    $str = http_build_query($arr);
    $options = array(
      'http' => array(
        'method'  	=> 'POST',
        'header'  	=> "Content-type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($str)."\r\nConnection: close\r\n",
        'content' 	=> $str));

      echo "Sending $str\n\n";
      
    $r = file_get_contents($this->url."?action=$action", false, stream_context_create($options));
    return $r;
  }

  function validateTicket($ticket){
    return $this->post(
      'validate_ticket',
      array('ticket' => $ticket));
  }

  function getBalance($uid, $gref){
    return $this->post(
      'get_balance',
      array(
        'cust_id' => $uid,
        'cust_session_id' => $this->ticket
      ));
  }

  function credit($uid, $gref, $amount, $tid){
    return $this->post(
      'credit',
      array(
        'game_code' => $gref,
        'cust_id' => $uid,
        'amount' => $amount,
        'trx_id' => $tid
      ));
  }
  
  function debit($uid, $gref, $amount, $tid){
    return $this->post(
      'debit',
      array(
        'game_code' => $gref,
        'cust_id' => $uid,
        'amount' => $amount,
        'trx_id' => $tid
      ));
  }

    function setTicket($ticket, $uid, $gid){
        phMset($ticket, json_encode(array('user_id' => $uid, 'game_ref' => 'gamesos'.$gid)));
        $this->ticket = $ticket;
    }
  
  function realityCheckStatus($uid){
    return $this->post(
      'realityCheckStatus',
      array(
        'cust_id' => $uid,
      ));
  }
  function notifyRealityCheckChoice($uid){
    return $this->post(
      'notifyRealityCheckChoice',
      array(
        'cust_id' => $uid,
        'action_id' => $this->action_id,
        'action_type' => $this->action_type,
      ));
  }
  
  public function setMerchId(){
      $this->merch_id = Phive('Gamesos')->getSetting('merch_id');
      return $this;
  }
  public function setMerchPwd(){
      $this->merch_pwd = Phive('Gamesos')->getSetting('merch_pwd');
      return $this;
  }


  public function setActionId($actionId){
    $this->action_id = $actionId;
    return $this;
  }
  public function setActionType($actionType){
    $this->action_type = $actionType;
    return $this;
  }

  public function setUrl($url){
    $this->url = $url;
    return $this;
  }
  public function injectDependency($p_oDependency){
  switch ($p_oDependency){
    case $p_oDependency instanceof Gamesos:
      $this->_m_oGp = $p_oDependency;
      break;

    case $p_oDependency instanceof UserHandler:
      $this->_m_oUserHandler = $p_oDependency;
      break;

    default:
      return false;       
  }
  return $this;
}
  
}
