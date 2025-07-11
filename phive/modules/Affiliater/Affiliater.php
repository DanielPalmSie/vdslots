<?php
require_once __DIR__ . '/../../api/PhModule.php';

class Affiliater extends PhModule {

  public function createRelation($affiliate_id, $user_id){
    $affiliate_id = intval($affiliate_id);
    $user_id = intval($user_id);
    if(!empty($affiliate_id) && !empty($user_id))
       return phive('SQL')->query("UPDATE users SET affe_id = $affiliate_id WHERE id = $user_id");
    return false;
  }

  public function updateRate($rate_id,$rate,$start_amount){
    $updates = array("rate" 		=> $rate,
            "start_amount" 	=> $start_amount);
    phive("SQL")->updateArray($this->getSetting("rates"),$updates,"rate_id = ".phive("SQL")->escape($rate_id));
  }

  public function deleteRate($rate_id){
    phive("SQL")->query("DELETE FROM ".$this->getSetting("rates")." WHERE rate_id = ".phive("SQL")->escape($rate_id));
  }
  
  function affe301(){
    if(!empty($_GET['referral_id'])){
      $_SESSION['affiliate'] = $_GET['referral_id'];
      phive('Pager')->samePageSansParams();
    }else if(strpos($_GET['dir'], 'referral_id=') !== false){
      list($crap, $ref_id) = explode('=', $_GET['dir']);
      $_SESSION['affiliate'] = $ref_id;
      phive('Pager')->samePageSansParams();
    }
  }

    function rpc($params = [], $class = 'SQL', $method = 'loadArray'){
        $url = phive()->getSetting('pr_url').'/phive/modules/Site/json/exec.php';
        $call = [
            'pwd' => phive()->getSetting('pr_pwd'),
            'class'  => $class,
            'method' => $method,
            'params' => $params
        ];
        return json_decode(phive()->post($url, $call, 'application/json', '', 'prrpc', 'POST', 10), true);
    }
    
}

function prrpc($params = [], $class = 'SQL', $method = 'loadArray'){
    return phive('Affiliater')->rpc($params, $class, $method);
}
