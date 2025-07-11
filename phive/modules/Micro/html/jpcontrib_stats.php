<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$start_date 	= empty($_POST['start_date']) ? date('Y-m-01') : $_POST['start_date'];
$end_date 		= empty($_POST['end_date']) ? date('Y-m-t') : $_POST['end_date']; 

$contributions = phive('MicroGames')->getJpContribStatsByDay($start_date, $end_date);
$total_contributions = 0;

$bbcontributions = phive('MicroGames')->getJpContribStatsByDay($start_date, $end_date, 1);
$total_bb_contributions = 0;

?>
<div style="padding: 10px;">
<br>
<strong>Current cash: <?php efEuro( phive('Cashier')->getTotalCash() ) ?></strong><br>
<strong>Current bonus balances: <?php efEuro( phive('Bonuses')->getTotalBalances() ) ?></strong>
<br>
<table>
	<tr>
		<td style="vertical-align: top;">
			<span class="big_headline">Cash Bet Jackpot Contributions</span>
			<table class="stats_table">
				<tr class="stats_header">
					<td>Date</td>
					<td>Game</td>
					<td>Amount (<?php ciso(true) ?>)</td>
				</tr>
				<?php $i = 0; foreach($contributions as $c): 
					$amount = $c['amount'] / 100;
					$total_contributions += $amount;
				?>
					<tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
						<td><?php echo $c['date'] ?></td>
						<td><?php echo $c['game'] ?></td>
						<td><?php echo $amount ?></td>
					</tr>
				<?php $i++; endforeach ?>
				<tr class="stats_header">
					<td></td>
					<td></td>
					<td><?php echo $total_contributions ?></td>
				</tr>
			</table>
		</td>
		<td style="vertical-align: top;">
			<span class="big_headline">Bonus Bet Jackpot Contributions</span>
			<table class="stats_table">
				<tr class="stats_header">
					<td>Date</td>
					<td>Game</td>
					<td>Amount (<?php ciso(true) ?>)</td>
				</tr>
				<?php $i = 0; foreach($bbcontributions as $c): 
					$amount = $c['amount'] / 100;
					$total_bb_contributions += $amount;
				?>
					<tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
						<td><?php echo $c['date'] ?></td>
						<td><?php echo $c['game'] ?></td>
						<td><?php echo $amount ?></td>
					</tr>
				<?php $i++; endforeach ?>
				<tr class="stats_header">
					<td></td>
					<td></td>
					<td><?php echo $total_bb_contributions ?></td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<br>
<br>
<form action="" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<table>
	<tr>
		<td>Start Date:</td>
		<td>
			<td><?php dbInput('start_date', $start_date) ?></td>
		</td>
	</tr>
	<tr>
		<td>End Date:</td>
		<td>
			<td><?php dbInput('end_date', $end_date) ?></td>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<td><?php dbSubmit('Submit') ?></td>
		</td>
	</tr>
</table>
</form>
</div>