<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

phive()->dumpTbl('euteller_end', $_REQUEST);

function eutellerEnd(){
  
  $c 		= phive('Cashier');
  $err 		= '';
  $username 	= '';
  $id           = intval($_REQUEST['orderid']);
  $security     = phive('SQL')->escape($_REQUEST['security'],false);
  $where 	= " WHERE id = $id AND security = '$security' ";
  $token        = phive('SQL')->loadAssoc("SELECT * FROM transfer_tokens $where");
  $user         = cu($token['user_id']);
  if(!empty($_REQUEST['error']))
    $err = 'err.euteller.cancelled';
  else if(!empty($token)){
    phive('Localizer')->setLanguage($token['lang']);
    $res = phive('QuickFire')->depositCash($token['username'], $token['amount'], 'euteller', $_REQUEST['bankref']);
    if($res === false){
      $err = 'err.unknown';
    }else
      phive('SQL')->query("DELETE FROM transfer_tokens $where");
  }else
  $err = 'err.euteller.notoken';
  
  return array($err, $token['site_type']);
}

list($err, $site_type) = eutellerEnd();

$site_type = $_REQUEST['addfield']['site_type'];

?>
<html>
  <head>
    <title>Euteller Finish</title>
    <?php loadCss("/diamondbet/css/" . brandedCss() . "all.css") ?>
    <link rel="stylesheet" type="text/css" href="/diamondbet/css/<?php echo brandedCss() ?>mb.css" />
    <?php loadCss("/diamondbet/css/cashier.css") ?>
  </head>
  <body>
    <?php 
    $key = $site_type == 'mobile' ? 'euteller_mobile_return_url' : 'euteller_return_url';
    $url = phive("Cashier")->getSetting($key); 
    jsInclude("/phive/js/utility.js");
    jsTag("goTo('http://{$_SERVER['HTTP_HOST']}$url?euteller_end=true&euteller_err=$err');")
    ?>
  </body>
</html>
