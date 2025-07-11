<?php
//TODO hg remove this file?
exit;
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$start_date 	= empty($_REQUEST['start_date']) ? date('Y-m-01') : $_REQUEST['start_date'];
$end_date 		= empty($_REQUEST['end_date']) ? date('Y-m-t') : $_REQUEST['end_date']; 

if(!empty($_REQUEST['username'])){
	$user 		= phive('UserHandler')->getUserByUsername($_REQUEST['username']);
	$user_id 	= $user->getId();
}

$c = phive('Cashier');
$result = $c->getCashBalancePeriod($start_date, $end_date, $user);	


$fields = array_keys(current($result));
$additions = array_fill(0, count($fields) - 1, ' ('.ciso().')');
array_unshift($additions, '');

foreach($result as &$sub){
	foreach($sub as &$value)
		$value = nf2($value, true, 100);
}


?>
<div style="padding: 10px;">
<br>
<br>
<?php if(empty($_REQUEST['username'])): ?>
	<strong>Current site cash: <?php efEuro( $c->getTotalCash() ) ?></strong><br>
	<strong>Current site bonus balances: <?php efEuro( phive('Bonuses')->getTotalBalances() ) ?></strong>
<?php elseif(!empty($user_id)): ?>
	<strong>Current user cash: <?php efEuro( $user->getAttribute('cash_balance') ) ?></strong><br>
	<strong>Current user bonus balances: <?php efEuro( phive('Bonuses')->getBalanceByUser($user_id) ) ?></strong>
<?php else: ?>
	<strong>User could not be found.</strong>
<?php endif ?>
<br>
<br>
<strong>Bonus balance:</strong> activated sum - failed sum.<br>
<strong>Other balance:</strong> deposits, withdrawals etc.<br>
<strong>Bet balance:</strong> sum of bets, bonus bets, wins and bonus wins.<br>
<strong>Total:</strong> sum of bonus balance, other balance, jp sum and bet balance.<br>
<br>
<?php printStatsTable($fields, $result, $additions) ?>
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
		<td>Username:</td>
		<td>
			<?php dbInput('username', $_REQUEST['username']) ?>
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
