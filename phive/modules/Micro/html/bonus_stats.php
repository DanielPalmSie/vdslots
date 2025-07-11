<?php

// TODO henrik remove

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
setCur($_POST['currency']);

$start_date 	= empty($_POST['start_date']) ? date('Y-m-01') : $_POST['start_date'];
$end_date 		= empty($_POST['end_date']) ? date('Y-m-t') : $_POST['end_date'];

$cashier = phive('Cashier');

$rewarded 	= $cashier->getTransactionsByTypeDay(' = 4 ', $start_date, $end_date, false, ciso());
$activated 	= $cashier->getTransactionsByTypeDay(' = 14 ', $start_date, $end_date, false, ciso());

$activated_total = phive()->sum2d($activated, 'amount');
$rewarded_total = phive()->sum2d($rewarded, 'amount');

$transactions = array();
foreach($rewarded as $r)
  $transactions[ $r['timestamp'] ]['reward_sum'] = $r['amount'];
foreach($activated as $a)
  $transactions[ $a['timestamp'] ]['activated_sum'] = $a['amount'];

ksort($transactions);


$bstats = array();
foreach(phive('Currencer')->getAllCurrencies() as $code => $cur)
  $bstats[$code] = phive('Bonuses')->getBonusStats($start_date, $end_date, $code);

function prBonus($stats, $top_head){
  $ncs = array('dep_amount', 'fail_amount', 'award_amount', 'first_dep_amount');
  $nums = array('dep_count', 'first_dep_count', 'active_count');
  $first = current($stats);
  unset($first['id']);
  unset($first['created_at']);
  $headlines = array_keys($first);
  phive('UserSearch')->showCsv($stats);
?>
<strong><?php echo $top_head ?> </strong>
<table class="stats_table">
  <thead>
    <tr class="stats_header">
      <th><?php echo 'Date' ?></th>
      <?php foreach($headlines as $h): ?>
	<th><?php echo ucfirst(str_replace('_', ' ', $h)) ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach($stats as $date => $r):
      unset($r['id']);
      unset($r['created_at']);
    ?>
    <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
      <td><?php echo $date ?></td>
      <?php foreach($r as $col => $val): ?>
	<td> 
	  <?php echo in_array($col, $ncs) ? nfCents($val, true) : $val; ?> 
	</td>
      <?php endforeach ?>
    </tr>
    <?php $i++; endforeach ?>
    <tr class="stats_header">
      <th></th>
      <?php foreach($headlines as $h): ?>
	<td>
          <?php
          if(in_array($h, $ncs)){
            $sum = phive()->sum2d($stats, $h);
            nfCents($sum);
          }else if(in_array($h, $nums)){
            $sum = phive()->sum2d($stats, $h);
            echo $sum;
          }
          ?> 
        </td>
      <?php endforeach ?>
    </tr>
  </tbody>
</table>
<?php
}

?>
<div class="pad10">

  <?php foreach($bstats as $cur => $stats): ?>
    <?php prBonus($stats, $cur) ?>
  <?php endforeach ?>
  <?php phive('UserSearch')->showCsv($transactions, array('activated_sum', 'reward_sum')) ?>
<table class="stats_table">
    <tr class="stats_header">
      <td>Date</td>
      <td>Activated Amount (<?php ciso(true) ?>)</td>
      <td>Rewarded Amount (<?php ciso(true) ?>)</td>
    </tr>
    <?php $i = 0; foreach($transactions as $stamp => $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
        <td> <?php echo $stamp ?> </td>
        <td> <?php echo $r['activated_sum'] / 100 ?> </td>
        <td> <?php echo $r['reward_sum'] / 100 ?> </td>
      </tr>
    <?php $i++; endforeach ?>
    <tr class="stats_header">
      <td>&nbsp;</td>
      <td><?php echo $activated_total / 100 ?></td>
      <td><?php echo $rewarded_total / 100 ?></td>
    </tr>
  </table>

<br>
<br>
<table class="big_headline">
  <tr>
    <td>Activated total - Rewarded total (<?php ciso(true) ?>):</td>
    <td><?php echo ($activated_total - $rewarded_total) / 100 ?></td>
  </tr>
</table>
<br>
<br>
<span class="big_headline">Total bonus balances on site in all currencies: <?php echo phive('Bonuses')->getTotalBalances() / 100 ?></span>
<br>
<br>
<form action="" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<table>
  <tr>
    <td>Start Date:</td>
    <td>
      <?php dbInput('start_date', $start_date) ?>
    </td>
  </tr>
  <tr>
    <td>End Date:</td>
    <td>
      <?php dbInput('end_date', $end_date) ?>
    </td>
  </tr>
  <tr>
    <td>Currency:</td>
    <td>
      <?php cisosSelect() ?>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
      <?php dbSubmit('Submit') ?>
      <?php phive("UserSearch")->csvBtn() ?>
    </td>
  </tr>
</table>
</form>
</div>
