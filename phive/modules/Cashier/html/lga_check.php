<?php

// TODO henrik is this even used anymore? If not remove.

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$rows = phive('Cashier')->lgaCheckList();
$now = time();
?>
<script>
 $(document).ready(function(){
   $("div[id^='unverify-']").click(function(){
     var me 	= $(this);
     var uid 	= me.attr("id").split("-").pop();     
     mgAjax({action: "unverify", user_id: uid}, function(ret){
       me.html(ret);
     });
   });
 });
</script>
<div style="padding: 10px;">
  <br/>
  Lists all users who have withdrawn and are trying (status pending) to widthdraw more than 2330 EUR (or equivalent in other currencies) since they were verified if the time since verification is more than 6 months.
  <br/>
  <br/>
  If they have withdrawn more than 2330 EUR but the time that has lapsed is less than 6 months they are listed blue.
  <br/>
  <br/>
<table class="stats_table">
  <tr class="stats_header">
    <td>Player</td>
    <td>Tot. Withdrawn Amount</td>
    <td>Verification Date</td>
    <td>Currency</td>
    <td></td>
  </tr>
  <?php $i = 0; foreach($rows as $r): ?>
  <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
    <td style="<?php echo "background-color: #".(($now - strtotime($r['created_at'])) > 15811200 ? "faa" : "aaf").";" ?>">
      <a href="<?php echo getUserBoLink($r['user_id']) ?>" target="_blank" rel="noopener noreferrer"><?php echo $r['user_id'] ?></a><br/>
    </td>
    <td> <?php nfCents($r['amount_sum']) ?> </td>
    <td> <?php echo $r['created_at'] ?> </td>
    <td> <?php echo $r['currency'] ?> </td>
    <td>
      <div id="unverify-<?php echo $r['user_id'] ?>" style="cursor:pointer;">Unverify</div>
    </td>
  </tr>
  <?php $i++; endforeach ?>
</table>
</div>
