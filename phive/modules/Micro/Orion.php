<?php
class Orion{

  function __construct(){
    $this->qf = phive('QuickFire');
    $this->op_id = $this->qf->getSetting('op_id');
  }
  
  //method is either GetCommitQueueData or GetRollbackQueueData
  function getQ($method, $op_id){

    $qf = phive('QuickFire');
    ob_start();
?>
  <methodCall>
    <methodName><?php echo $method ?></methodName>
    <params>
      <param>
      <value>
        <array>
          <data>
            <value>
              <int><?php echo $op_id ?></int>
            </value>
          </data>
        </array>
      </value>
    </param>
    </params>
  </methodCall>
  <?php
  $xml = ob_get_clean();
  phive()->dumpTbl('orion_getq', $xml);
  $r = $this->request($xml, $method);

  if(!$r)
    return array();

  $r = $r->xpath('//struct');

  $res = array();

  foreach($r as $i){
    $tmp = array();
    foreach($i->member as $pair){
      $pair   = (array)$pair;
      $value  = (array)$pair['value'];
      $tmp[ trim($pair['name']) ] = trim(array_shift($value));
    }
    $tmp['GameRef'] = $this->getGameRef($tmp['GameName']);
    $res[] = $tmp;
  }

  return $res;
  }

  function getGameRef($mg_str){
    if(empty($this->games))
      $this->games = phive('MicroGames')->getAllGames();
    foreach($this->games as $g){
      if(strpos($mg_str, $g['game_name']) !== false)
        return $g['ext_game_name'];
    }
    return "";
  }

  function commitOrRollback($el, $method, $cur_game = ''){
    phive()->dumpTbl("orion_method", $method);
    $qf 	= phive('QuickFire');
    //if($qf->getSetting('has_ggi') === true)  
    //  $user 	= cu($el['username']);
    //else
    $user 	= phive('UserHandler')->getUserByUsername($el['username']);
    $mg_id      = $this->getActionId($user->data, $el['mg_id']);
    $currency   = $user->getCurrency();
    if(!empty($user) && !is_float($el['amount'] + 0)){
      $el['user_id'] 	= $user->getId();
      $el['amount'] 	= abs($el['amount']);
      if(empty($cur_game))
        $cur_game = phive('MicroGames')->getByGameRef($el['game_ref']);
      else
        $el['game_ref'] = $cur_game['ext_game_name'];
      if($method == 'RollbackQueue'){
        $bet = $qf->getBetByMgId($mg_id);
        if(empty($bet))
          $el['balance'] 	= $qf->changeBalance($user, "-{$el['amount']}", $el['trans_id'], 1);          
        $bet_amount 		= $el['amount'];          
        $jp_contrib 		= $bet_amount * $cur_game['jackpot_contrib'];
        $insert = array(
          'trans_id' 	=> $el['trans_id'],
          'amount'	=> $bet_amount,
          'game_ref'	=> $el['game_ref'],
          'user_id'	=> $el['user_id'],
          'currency'	=> $currency,
          'mg_id'	=> $mg_id,
          'balance'	=> $el['balance'],
          'op_fee'	=> $bet_amount * $cur_game['op_fee'],
          'jp_contrib'  => $jp_contrib
        );
        if(empty($bet))
          phive('SQL')->sh($insert, 'user_id', 'bets')->insertArray('bets', $insert);
        $insert['mg_id']       = $mg_id."ref";
        $insert['amount']      = 0;
        $insert['op_fee']      = 0;
        $insert['jp_contrib']  = 0;
        phive('SQL')->sh($insert, 'user_id', 'bets')->insertArray('bets', $insert);        
      }

      $ext_id = $qf->getBetByMgId($mg_id, 'wins');

      if(empty($ext_id)){
        $el['op_fee'] 		= $el['amount'] * $cur_game['op_fee'];
        $el['award_type'] 	= 7;
        $el['balance'] 		= $qf->changeBalance($user, "{$el['amount']}", $el['trans_id'], 7);
        $el['currency']         = $currency;
        unset($el['username']);
        unset($el['row_id']);
        unset($el['mg_userid']);
        $el['mg_id'] = $mg_id;
        $ext_id = phive('SQL')->sh($el, 'user_id', 'wins')->insertArray('wins', $el);
      }else
        $ext_id = $ext_id['id'];

      return $ext_id;
    }else
      echo "no user";
  }

    function complete($arr, $op_id){
      $qf = phive('QuickFire');
      ob_start();
    ?>
      <methodCall>
        <methodName>ManuallyCompleteGame</methodName>
        <params>
          <param>
          <value>
            <array>
              <data>
                <?php foreach($arr as $el): ?>
                  <value>
                    <struct>
                      <member>
                        <name>ServerId</name>
                        <value>
                          <int><?php echo $op_id ?></int>
                        </value>
                      </member>
                      <member>
                        <name>RowId</name>
                        <value>
                          <long><?php echo $el['RowId'] ?></long>
                        </value>
                      </member>
                    </struct>
                  </value>
                <?php endforeach ?>
              </data>
            </array>
          </value>
          </param>
        </params>
      </methodCall>
      <?php
      $xml = ob_get_clean();
      phive()->dumpTbl('orion_complete' ,$xml);
      $r = $this->request($xml, $method);
      if($r !== false){
        $r = $r->xpath('//boolean');
        //xmltest
        $r = (array)$r[0];
        return $r[0];
      }
      return false;
    }

  /*
   * $method is either RollbackQueue or CommitQueue
   */
  function validate($method, $arr){
    $qf = phive('QuickFire');
    ob_start();
    ?>
      <methodCall>
       <methodName>ManuallyValidateBet</methodName>
       <params>
        <param>
         <value>
          <array>
           <data>
           <?php foreach($arr as $el): ?>
           <value>
             <struct>
              <member>
               <name>UserId</name>
               <value>
                <int><?php echo $el['mg_userid'] ?></int>
               </value>
              </member>
              <member>
               <name>ServerId</name>
               <value>
                <int><?php echo $qf->getSetting('op_id') ?></int>
               </value>
              </member>
              <member>
               <name>RowId</name>
               <value>
                <long><?php echo $el['row_id'] ?></long>
               </value>
              </member>
              <member>
               <name>UnlockType</name>
               <value>
                <string><?php echo $method ?></string>
               </value>
              </member>
              <member>
               <name>ExternalReference</name>
               <value>
                <string><?php echo $el['id'] ?></string>
               </value>
              </member>
             </struct>
            </value>
            <?php endforeach ?>
           </data>
          </array>
         </value>
        </param>
       </params>
      </methodCall>
  <?php
    $xml = ob_get_clean();
    phive()->dumpTbl('orion_validate' ,$xml);
    $r = $this->request($xml, $method);
    if($r !== false){
      $r = $r->xpath('//boolean');
      //xmltest
      $r = (array)$r[0];
      return $r[0];
    }
    return false;
  }


  function request($xml, $op = '', $action = 'vanguardadmin', $headers = '', $type = 'xmlrpc2'){
    $ss 	= phive('QuickFire')->allSettings();
    if(empty($headers)){
      $headers = "Request-Id: ".phive()->uuid()."\r\n".
                 "Authorization: Basic ".base64_encode($ss['va_username'].":".$ss['va_password'])."\r\n";
    }

      /*
    $options 	= array('http' =>
      array(
        'method'  	=> 'POST',
        'timeout'         => 10,
        'header'  	=> $headers,
        'content' 		=> $xml));
      */
      
    $orion_url = str_replace(array('%1', '%2'), array($action, $type), $ss['orion_url']);
    
    phive()->dumpTbl("orion_out_{$op}_options", array($orion_url, $options, $ss));
    
      //$r = file_get_contents($orion_url, false, stream_context_create($options));
      $r = phive()->post($orion_url, $xml, 'text/xml', $headers, '', 'POST', 10);

    phive()->dumpTbl("orion_{$op}_res", array($r, $http_response_header));

    $r = preg_replace('|&#x\d+;?|sim', '', $r);

    try{
      return new SimpleXMLElement($r);
    }catch(Exception $e){
      return false;
    }
  }
  
  function getOpIds(){
    $qf = phive('QuickFire');
    //return array($qf->getSetting('op_id'), $qf->getSetting('op_id_GB'));
    return array($qf->getSetting('op_id'));
  }

  function getFreeGameXml($action, $body = ''){
    $me = $this;
    return phive()->ob(function() use($action, $body, $me){
  ?>
    <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
      <s:Body>
        <GetFreegames xmlns="http://mgsops.net/AdminAPI_Freegame">
          <request xmlns:a="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
            <a:ServerId><?php echo $this->op_id ?></a:ServerId>
          </request>
        </GetFreegames>
      </s:Body>
    </s:Envelope>
  <?php  
    });    
  }

  function getFreeGames(){
    $ss 	= phive('QuickFire')->allSettings();
    $xml =  $this->getFreeGameXml('GetFreegames');
    //$xml = utf8_encode($xml);
    //$strlen = mb_strlen($xml);
    $strlen = strlen($xml);
    $uuid = phive()->uuid();
    $auth = base64_encode($ss['va_username'].":".$ss['va_password']);
    $headers = "Content-Type: text/xml; charset=utf-8\r\n".
               "Request-Id: $uuid\r\n".
               "SOAPAction: \"http://mgsops.net/AdminAPI_Freegame/IFreegameAdmin/GetFreegames\"\r\n".
               "Accept-Encoding: gzip, deflate\r\n".
               "Authorization: Basic $auth\r\n".
               "Host: webserver8.bluemesa.mgsops.net\r\n".
               "Content-Length: $strlen\r\n".
               "Expect: 100-continue\r\n";
    $r = $this->request($xml, 'GetFreeGames', 'FreegameAdmin', $headers, 'soap');
    //phive()->dumpTbl('orion_free_games', $r);
      //xpath the data here, save as CSV make it downloadable in admin
      return $r;
  }  

  function failFRBonus($ud, $entry, $mg_bid){
    ob_start();
  ?>
    <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
      <s:Body>
        <adm:RemovePlayersFromFreegame xmlns:adm="http://mgsops.net/AdminAPI_Freegame">
          <adm:request>
            <ori:PlayerActions xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures">
              <ori:PlayerAction>
                <ori:ISOCurrencyCode><?php echo $ud['currency'] ?></ori:ISOCurrencyCode>
                <ori:LoginName><?php echo $ud['username'] ?></ori:LoginName>
                <ori:PlayerStartDate><?php echo date('Y-m-d').'T'.date('H:i:s').'+00:00' ?></ori:PlayerStartDate>
                <ori:InstanceId><?php echo $entry['id'] ?></ori:InstanceId>
                <ori:OfferId><?php echo $mg_bid ?></ori:OfferId>
                <ori:Sequence><?php echo phive()->uuid() ?></ori:Sequence>
              </ori:PlayerAction>
            </ori:PlayerActions>
            <ori:Sequence xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures"><?php echo phive()->uuid() ?></ori:Sequence>
            <ori:ServerId xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures"><?php echo $this->op_id ?></ori:ServerId>          
          </adm:request>
        </adm:RemovePlayersFromFreegame>
      </s:Body>
    </s:Envelope>
    <?php
    $xml = ob_get_clean();
    $ss 	= phive('QuickFire')->allSettings();
    //$xml = utf8_encode($xml);
    //$strlen = mb_strlen($xml);
    $strlen = strlen($xml);
    $uuid = phive()->uuid();
    $auth = base64_encode($ss['va_username'].":".$ss['va_password']);
    $headers = "Content-Type: text/xml; charset=utf-8\r\n".
               "Request-Id: $uuid\r\n".
               "SOAPAction: \"http://mgsops.net/AdminAPI_Freegame/IFreegameAdmin/RemovePlayersFromFreegame\"\r\n". //don't forget this when refactoring
               "Accept-Encoding: gzip, deflate\r\n".
               "Authorization: Basic $auth\r\n".
               "Host: webserver8.bluemesa.mgsops.net\r\n".
               "Content-Length: $strlen\r\n".
               "Expect: 100-continue\r\n";
    return $this->request($xml, 'RemovePlayersFromFreegame', 'FreegameAdmin', $headers, 'soap');
  }
  
  function awardFRBonus($ud, $entry, $mg_bid){
    ob_start();
  ?>
    <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
      <s:Body>
        <adm:AddPlayersToFreegame xmlns:adm="http://mgsops.net/AdminAPI_Freegame">
          <adm:request>
            <ori:PlayerActions xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures">
              <ori:PlayerAction>
                <ori:ISOCurrencyCode><?php echo $ud['currency'] ?></ori:ISOCurrencyCode>
                <ori:LoginName><?php echo $ud['username'] ?></ori:LoginName>
                <ori:PlayerStartDate><?php echo date('Y-m-d').'T'.date('H:i:s').'+00:00' ?></ori:PlayerStartDate>
                <ori:InstanceId><?php echo $entry['id'] ?></ori:InstanceId>
                <ori:OfferId><?php echo $mg_bid ?></ori:OfferId>
                <ori:Sequence><?php echo phive()->uuid() ?></ori:Sequence>
              </ori:PlayerAction>
            </ori:PlayerActions>
            <ori:Sequence xsi:nil="true" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures"><?php echo phive()->uuid() ?></ori:Sequence>
            <ori:ServerId xmlns:ori="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures"><?php echo $this->op_id ?></ori:ServerId>          
          </adm:request>
        </adm:AddPlayersToFreegame>
      </s:Body>
    </s:Envelope>
    <?php
    $xml = ob_get_clean();
    $ss 	= phive('QuickFire')->allSettings();
    //$xml = utf8_encode($xml);
    //$strlen = mb_strlen($xml);
    $strlen = strlen($xml);
    $uuid = phive()->uuid();
    $auth = base64_encode($ss['va_username'].":".$ss['va_password']);
    $headers = "Content-Type: text/xml; charset=utf-8\r\n".
               "Request-Id: $uuid\r\n".
               "SOAPAction: \"http://mgsops.net/AdminAPI_Freegame/IFreegameAdmin/AddPlayersToFreegame\"\r\n". //don't forget this when refactoring
               "Accept-Encoding: gzip, deflate\r\n".
               "Authorization: Basic $auth\r\n".
               "Host: orionapi2.gameassists.co.uk\r\n".
               "Content-Length: $strlen\r\n".
               "Expect: 100-continue\r\n";

    phive()->dumpTbl('orion_frb_out', [$headers, $xml], $ud);
    
    //return value: <s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><AddPlayersToFreegameResponse xmlns="http://mgsops.net/AdminAPI_Freegame"><AddPlayersToFreegameResult xmlns:a="http://schemas.datacontract.org/2004/07/Orion.Contracts.FreegameAdmin.DataStructures" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:PlayerActions><a:PlayerAction><a:Error i:nil="true"/><a:ISOCurrencyCode>EUR</a:ISOCurrencyCode><a:LoginName>devtest1</a:LoginName><a:PlayerStartDate>2015-09-08T16:01:16+02:00</a:PlayerStartDate><a:Success>true</a:Success><a:InstanceId>1</a:InstanceId><a:OfferId>269</a:OfferId><a:Sequence>49fd7294-a5a6-1d68-1afb-00005ea7fa00</a:Sequence></a:PlayerAction></a:PlayerActions><a:Sequence i:nil="true"/><a:HasErrors>false</a:HasErrors></AddPlayersToFreegameResult></AddPlayersToFreegameResponse></s:Body></s:Envelope>
    return $this->request($xml, 'AddPlayersToFreegame', 'FreegameAdmin', $headers, 'soap');
  }
    
    
  function completeQ(){
    foreach($this->getOpIds() as $op_id){
      foreach($this->getQ('GetFailedEndGameQueue', $op_id) as $r)
        $to_mg[] = $r;

      if(!empty($to_mg)){
        if($this->complete($to_mg, $op_id) === false)
          phive()->dumpTbl("orion_complete_failure", $to_mg);
        else
          echo "The complete queue was successfully posted.";
      }
    }
  }

  function workQ(){
    $map = array(
      'LoginName' 		=> 'username',
      'TransactionNumber' 	=> 'trans_id',
      'UserId' 			=> 'mg_userid',
      'ChangeAmount' 		=> 'amount',
      'MgsReferenceNumber' 	=> 'mg_id',
      'RowId' 			=> 'row_id'
    );
       
    foreach($this->getOpIds() as $op_id){      
      foreach(array('GetCommitQueueData', 'GetRollbackQueueData') as $method){
        $to_mg = array();
        foreach($this->getQ($method, $op_id) as $r){
          $execute_m = $method == 'GetCommitQueueData' ? 'CommitQueue' : 'RollbackQueue';
          $game = phive('MicroGames')->getByGameRef($r['GameRef']);
          if(empty($game))
            $game = phive('SQL')->loadAssoc('', 'micro_games', "orion_name = '{$r['GameName']}'");
          if(!empty($game)){
            $el = array();
            foreach($map as $mgkey => $ourkey)
              $el[$ourkey] = $r[$mgkey];          
            $el['id'] = $id = $this->commitOrRollback($el, $execute_m, $game);
            if(!is_numeric($id)){
              phive()->dumpTbl("orion_{$execute_m}_failure", $el);
            }else
            $to_mg[] = $el;
          }else{
            phive()->dumpTbl("orion_missing_game", $r);
          }
        }      
        
        if(!empty($to_mg)){
          if($this->validate($execute_m, $to_mg) === false){
            phive()->dumpTbl("orion_validate_failure", $to_mg);
          }else
            echo "The queue was successfully posted.";
        }            
      }
    }
  }

    /**
     * Ported from QuickFire.php to avoid breaking existing usage, after we switched to licSetting.
     * Probably Orion is not used anymore, couldn't find instances and can be removed.
     *
     * @param $ud
     * @param int $aid
     * @return string
     */
    public function getActionId(&$ud, $aid = 0)
    {
        $aid = abs(empty($aid) ? $this->params['actionid'] : $aid);
        $op_id = phive('QuickFire')->getOpId($ud['country']);
        return "$aid-$op_id";
    }
}
