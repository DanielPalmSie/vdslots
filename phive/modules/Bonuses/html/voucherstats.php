<?php
require_once __DIR__ . '/../../../admin.php';
$v = phive('Vouchers');
if(!empty($_POST['submit_series'])){
  $vouchers = $v->getVouchersByName($_POST['voucher_name']);
  if(!empty($vouchers)){
    $aff = cu($vouchers[0]['affe_id']);
    $redeemers = $v->getRedeemers($_POST['voucher_name']);
    $entries = array();
    $bsum = 0;
    $dsum = 0;
    
    foreach($redeemers as &$r){
      $entry 			= $v->getBonusEntryFromVoucher($r); 
      $bsum 			+= $entry['balance']; 
      $depsum 			= phive('Cashier')->sumTransactionsByType($r['user_id'], 3, $r['redeem_stamp']);
      $dsum 			+= $depsum;
      $entry['deposits'] 	= $depsum;
      $entries[] 		= $entry;
      $r['extra'] 		= $entry;      
    }
    $bonus = phive('Bonuses')->getBonus($redeemers[0]['bonus_id']);
  }
}
?>
<div style="margin:10px; padding:10px;">
  <strong>Pick voucher series:</strong><br>
  <form action="" method="post" accept-charset="utf-8">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <?php dbSelectWith("voucher_name", $v->getVoucherSeries(), 'voucher_name', 'voucher_name') ?>
    <p><input type="submit" name="submit_series" value="Submit"></p>
  </form>
  <br>
  <?php if(!empty($vouchers)): ?>
    <strong>All amounts in whole currency units (not cents).</strong>
    <br>
    <br>
    <table>
      <tr>
	<td>Total number of vouchers:</td>
	<td>
	  <strong><?php echo count($vouchers) ?></strong>
	</td>
      </tr>
      <tr>
	<td>Total number of unredeemed (negative values indicate failures):</td>
	<td>
	  <strong><?php echo count($vouchers) - phive()->sum2d($vouchers, 'redeemed') ?></strong>
	</td>
      </tr>
      <?php if(!empty($aff)): ?>
	<tr>
	  <td>Affiliate:</td>
	  <td>
	    <a href="/account/<?php echo $aff->getId() ?>/"><?php echo $aff->getId() ?></strong>
	  </td>
	</tr>
      <?php endif ?>
      <tr>
	<td>Total bonus balances:</td>
	<td>
	  <strong><?php echo $bsum / 100 ?></strong>
	</td>
      </tr>
      <tr>
	<td>Total deposits since redeem date:</td>
	<td>
	  <strong><?php echo $dsum / 100 ?></strong>
	</td>
      </tr>
      <tr>
	<td>Total bonus cash lost:</td>
	<td>
	  <strong><?php echo (phive()->sum2d($entries, 'reward') - $bsum) / 100 ?></strong>
	</td>
      </tr>
    </table>
    <br>
    <br>
    <strong>
      Bonus: <?php echo $bonus['bonus_name'] ?>
      <br/>
      Bonus ID: <?php echo $bonus['id'] ?>
    </strong>
    <br>
    <br>
    <strong>Redeemers:</strong><br>
    <table class="stats_table">
      <tr class="stats_header">
	<td>Player</td>
	<td>Bonus Balance</td>
	<td>Deposit sum since redeem date</td>
	<td>Status</td>
      </tr>
      
      <?php $i=0; foreach($redeemers as $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill">
	<td>
        <a href="<?php echo getUserBoLink($r['user_id']) ?>" target="_blank" rel="noopener noreferrer"><?php echo $r['user_id'] ?></a><br/>
    </td>
	<td>
	  <?php echo $r['extra']['balance'] / 100 ?>
	</td>
	<td>
	  <?php echo $r['extra']['deposits'] / 100 ?>
	</td>
	<td>
	  <?php echo $r['redeemed'] == 1 ? 'success' : 'fail' ?>
	</td>
      </tr>
      <?php $i++; endforeach; ?>
      
    </table>
  <?php endif ?>
</div>
