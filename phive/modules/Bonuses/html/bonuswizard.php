<?php
require_once __DIR__ . '/../../../admin.php';
// cost is always 50x reward

function retExit($msg, $status = 'fail'){
  echo json_encode(array('status' => $status, 'message' => $msg));
  exit;
}

if(!empty($_POST['type'])){
  $data 	= phive()->to1d(json_decode($_POST['data'], true), 'name', 'value');

  if(empty($data['expire_time']))
    retExit("You forgot to set an expire time.");
  
  if(empty($data['bonus_name']))
    retExit("The bonus needs a name.");
  
  if(!in_array($_POST['type'], array('voucherfspins', 'fspinsdeposit')))
    unset($data['ext_ids']);
  else if(empty($data['ext_ids']))
    retExit("You forgot to choose games for the free spin bonus.");
  
  if(!in_array($_POST['type'], array('voucherfspins', 'vouchercash')))
    unset($data['reward']);
  else if(empty($data['voucher_code']))
    retExit("You forgot to choose a voucher code.");
  
  if(!empty($data['aff_username'])){
    $aff 	= phive("UserHandler")->getUserByUsername($data['aff_username']);
    if(empty($aff))
      retExit("Affiliate doesn't exist.<br>");
  }
  
  if(!empty($data['bonus_code'])){
    
    $old = phive("Bonuses")->getByCode($data['bonus_code']);
    
    if(!empty($old))
      retExit("Bonus Code already belongs to <strong>{$old['bonus_name']}</strong>.<br>");
    else{
      $aff2 = phive("Affiliater")->getAffByBonusCode($data['bonus_code']);
      if(!empty($aff) && !empty($aff2)){
	if($aff->getId() != $aff2->getId())
	  retExit("Bonus Code already belongs to <strong>".$aff2->getUsername()."</strong>.<br>");
      }
    }		
  }
  
  if(!empty($data['reload_code'])){
    $old = phive("Bonuses")->getReload($data['reload_code'], '2000-01-01', true);
    if(!empty($old))
      retExit("A reload bonus with the reload code {$data['reload_code']} already exists.");
  }
  
  if(!empty($data['voucher_code'])){
    $old = phive("Vouchers")->getVoucherByNameCode($data['voucher_code'], $data['voucher_code']);
    if(!empty($old))
      retExit("Voucher with code {$data['voucher_code']} already exists.<br>");
    else
      $voucher_amount = empty($data['voucher_amount']) ? 300 : min(300, $data['voucher_amount']);
  }
  
  if(!empty($data['reward'])){
    if($_POST['type'] == 'voucherfspins')
      $data['reward'] = $data['reward'] > 10 ? 10 : $data['reward'];
    else
      $data['reward'] = $data['reward'] > 1000 ? 1000 : $data['reward'];
    if($_POST['type'] == 'vouchercash')
      $data['cost'] = 50 * $data['reward'];
  }
  
  if(!empty($data['loyalty_percent']))
    $data['loyalty_percent'] = min($data['loyalty_percent'], 1);
  
  if(!empty($data['deposit_multiplier']) && $_POST['type'] == 'fspinsdeposit')
    $data['deposit_multiplier'] = min($data['deposit_multiplier'], 1);
  
  if(!empty($data['ext_ids'])){
    if($_POST['type'] == 'voucherfspins')
      $data['game_tags'] = is_array($data['ext_ids']) ? implode(',', $data['ext_ids']) : $data['ext_ids'];
    $data['ext_ids'] = is_array($data['ext_ids']) ? implode('|', $data['ext_ids']) : $data['ext_ids'];
    $data['bonus_tag'] = 'bsg';
  }
  
  $voucher_code = $data['voucher_code'];
  unset($data['voucher_code']);
  unset($data['voucher_amount']);
  unset($data['aff_username']);
  
  $insert = phive("Bonuses")->templateToArr('bonuswizard', $_POST['type']);
  foreach($data as $key => $value){
    if(!empty($value))
      $insert[$key] = $value;
  }
  
    $bonus_id = phive("SQL")->insertArray('bonus_types', $insert);
  
  if($bonus_id){
    if($_POST['type'] == 'vouchercash' || $_POST['type'] == 'voucherfspins')
      phive("Vouchers")->createSeries($voucher_code, $voucher_amount, $bonus_id, empty($aff) ? '' : $aff->getId(), $voucher_code);
    retExit("{$data['bonus_name']} was saved successfully.", 'success');
  }
  
  retExit("Nothing was done.");
  exit;
}


$bsg_select = phive("MicroGames")->allGamesSelect('ext_game_name', "network = 'bsg' AND tag IN('videoslots', 'slots', 'videoslots_jackpotbsg')", '1');
$new_version_jquery_ui = phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';
?>
<script src="/phive/js/jQuery-UI/<?= $new_version_jquery_ui ?>jquery-ui.min.js" type="text/javascript" charset="utf-8"></script>
<script src="/phive/js/jquery.json.js" type="text/javascript" charset="utf-8"></script>
<link rel="stylesheet" href="/phive/js/ui/css/ui-lightness/ui.css" type="text/css" charset="utf-8" />
<style>
 form input{
   float: right;
   width: 100px;
 }
 
 form div{
   width: 350px;
   clear: both;
   margin: 15px;
 }
 
 .bwizard td{
   vertical-align: top;
 }
 
 .bwizard li{
   text-decoration:underline;
   cursor: pointer;
   font-size: 14px;
   margin: 5px;
   list-style: none;
 }
 
 .selected-li{
   font-weight: bold;
 }
 
</style>
<script>
 var btype = '';
 $(document).ready(function(){
   $(".bwizard li").click(function(){
     $(".bwizard form div").hide();
     $(".bwizard li").removeClass('selected-li');
     $(this).addClass('selected-li');
     btype = $(this).attr('id');
   });
   
   $("#casinowager").click(function(){
     $(".all-type, .deposit-type").show();
   });
   
   $("#fspinsdeposit").click(function(){
     $(".all-type, .deposit-type, .fspin-type").show();
   });
   
   $("#vouchercash").click(function(){
     $(".all-type, .voucher-type").show();
   });
   
   $("#voucherfspins").click(function(){
     $(".all-type, .fspin-type, .voucher-type").show();
   });
   
   var dpOpts = {dateFormat: 'yy-mm-dd'};
   jQuery("#expire_time").datepicker(dpOpts);
   
   $(".bwizard form div").hide();
   
   $(".bwizard form").submit(function(){
     $.post('/phive/modules/Bonuses/html/bonuswizard.php', {type: btype, data: $.toJSON($(this).serializeArray())}, function(ret){
       if(ret.status == 'success'){
	 $('input[type=text]').val('');
	 $("option:selected").prop("selected", false);
       }
       $('.errors').html(ret.message);
     }, 'json');
     return false;
   });

 });
</script>
<div class="pad-stuff-ten bwizard">
  <table>
    <tr>
      <td style="width: 365px;">
	<ul>
	  <li id="casinowager">
	    Casino Wager Deposit Bonus
	  </li>
	  <li id="fspinsdeposit">
	    Free Spins Deposit Bonus
	  </li>
	  <li id="vouchercash">
	    Voucher Cash Bonus
	  </li>
	  <li id="voucherfspins">
	    Voucher Free Spins Bonus
	  </li>
	</ul>
	<form>
	  <div class="all-type"> Expire Time: <?php dbInput('expire_time') ?> </div>
	  <div class="all-type"> Bonus Name: <?php dbInput('bonus_name') ?> </div>
	  <div class="deposit-type"> Deposit Limit (cents): <?php dbInput('deposit_limit') ?> </div>
	  <div class="deposit-type"> Deposit Multiplier: <?php dbInput('deposit_multiplier') ?> </div>
	  <div class="deposit-type"> Bonus Code: <?php dbInput('bonus_code') ?> </div>
	  <div class="deposit-type"> Reload Code: <?php dbInput('reload_code') ?> </div>
	  <div class="deposit-type"> Loyalty Percent (float, 0.5 = 50%): <?php dbInput('loyalty_percent') ?> </div>
	  <div class="voucher-type"> Reward (cents/spins): <?php dbInput('reward') ?> </div>
	  <div class="fspin-type bsg-ids"> Free Spin Games: <?php dbSelect('ext_ids', $bsg_select, '', '', '', true);  ?> </div>
	  <div class="fspin-type"> Free Spin Cost (cents) (total cost unless deposit FRB in which case it is per spin): <?php dbInput('frb_cost');  ?> </div>
	  <br/>
	  <br/>
	  <div class="voucher-type"> Included Countries (iso2, ex: au,se): <?php dbInput('included_countries') ?> </div>
	  <div class="voucher-type"> Voucher Code: <?php dbInput('voucher_code') ?> </div>
	  <div class="voucher-type"> Number of Vouchers: <?php dbInput('voucher_amount') ?> </div>
	  <div class="voucher-type"> Username of Affiliate: <?php dbInput('aff_username') ?> </div>
	  <?php dbSubmit('Submit') ?>
	</form>
      </td>
      <td>
	<?php et('bwizard.info.html') ?>
      </td>
    </tr>
  </table>
  <div class="errors">
    
  </div>
</div>

