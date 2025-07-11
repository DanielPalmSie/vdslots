<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$c = phive("Cashier");
$date = empty($_REQUEST['date']) ? phive()->yesterday() : $_REQUEST['date'];
$jps = phive('MicroGames')->getLocalJpBalances();
$jp_tot = phive('MicroGames')->getLocalJpBalance();
?>
<div style="padding: 10px;">
<br>
<br>
<?php foreach(cisos() as $ciso):
  $b = miscCache("$date-cash-balance-$ciso");
  if(empty($b))
    continue;
  $b = unserialize($b);
  $bonus_balance = $b['bonus'];
  ?>
    <strong>Site cash on <?php echo $date ?> (<?php echo $ciso ?>): <?php nfCents($b['real']) ?></strong><br>
    <strong>Pending cash on <?php echo $date ?> (<?php echo $ciso ?>): <?php nfCents($b['pending']) ?></strong><br>
    <br/>
<?php endforeach ?>
<strong>Bonus cash on <?php echo $date ?> (all currencies): <?php nfCents($bonus_balance) ?></strong><br>
<br>
<br>
<br>
<br>
<?php foreach(cisos() as $ciso): ?>
  <strong>Current site cash (<?php echo $ciso ?>): <?php nfCents( $c->getTotalCash($ciso) ) ?></strong><br>
<?php endforeach ?>
  <strong>Current bonus balances, all currencies: <?php nfCents( phive('Bonuses')->getTotalBalances() ) ?></strong>
<br>
<br>
<br>
<?php setDefCur() ?>
<?php foreach($jps as $jp): ?>
  <?php echo $jp['jp_name'].': '.efEuro($jp['jp_value'], true) ?><br/>
<?php endforeach ?>
<?php echo 'JP Total: '.efEuro($jp_tot, true) ?>
<br>
<br>
<br>
<form action="" method="get">
<table>
  <tr>
    <td>Date:</td>
    <td>
      <?php dbInput('date', $date) ?>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
      <?php dbSubmit('Submit') ?>
    </td>
  </tr>
</table>
</form>
</div>

