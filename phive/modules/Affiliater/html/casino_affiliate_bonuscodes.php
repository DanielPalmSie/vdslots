<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

switch ($_REQUEST['action']) {
  case 'ini-search':
    $str = $_REQUEST['search_str'];
    $where = " AND (u.username LIKE '%$str%' OR u.email LIKE '%$str%' OR bc.bonus_code LIKE '%$str%') ";
?>
<table id="stats-table" class="stats_table">
  <tr class="stats_header">
    <th>Username</th>
    <th>Email</th>
    <th>Bonus Code</th>
  </tr>
  <?php $i = 0; foreach(phive("Affiliater")->getAffiliateAndBonusCodes($where) as $aff): ?>
  <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
    <td>
      <a href="<?php echo llink("/affiliate/account/{$aff['username']}/playerstats/") ?>">
	<?php echo $aff['username'] ?>
      </a>
    </td>
    <td><?php echo $aff['email'] ?></td>
    <td>
      <a href="<?php echo getUsersFromAffiliateLink(phive()->modDate('', '-1 month'), phive()->today(), $aff['id'], ciso(), $aff['bonus_code']) ?>">
	<?php  echo $aff['bonus_code'] ?>
      </a>
    </td>
  </tr>
  <?php $i++; endforeach ?>
</table>
<?php
exit;
break;
default:

break;
}

?>
<script>
 jQuery(document).ready(function(){
   var phpScript = '/phive/modules/Affiliater/html/casino_affiliate_bonuscodes.php';
   $("#main-search").keyup(function(event){
     var cur = $(this);
     if(cur.val().length >= 3){
       $.get(phpScript, {action: "ini-search", search_str: cur.val()}, function(res){
	 if(res){
	   $("#ini-result-holder").html(res);
	 }
       });
     }
   });
 });
</script>
<div style="padding: 10px;">
  <p>
    <strong>Search for partial affiliate username, bonus code or email:</strong>
    <?php dbInput('main-search') ?>
  </p>
  <div id="ini-result-holder">
    
  </div>
</div>
