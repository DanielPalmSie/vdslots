<?php

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';


$_REQUEST['edate'] = limitEdate($_REQUEST['sdate'], $_REQUEST['edate']);

$stats = phive('Cashier')->transactionsSearch($_REQUEST);

?>
<?php drawStartEndJs() ?>
<div class="pad-stuff-ten" style="width: 1000px;">
  <form action="" method="get">
    <Table border="0" cellspacing="2" cellpadding="2">
      <?php drawStartEndHtml() ?>
      <tr>
	<td>Transaction type:</td>
	<td>
          <?php dbSelect('type[]', array(
            3 => 'Deposits',
            4 => 'Bonus Rewards',
            5 => 'Affiliate Payouts',
            8 => 'Withdrawals',
            13 => 'Refunds',
            14 => 'Bonus Activations',
            20 => 'Sub Affiliate Payouts',
            31 => 'Casino Weekend Booster (loyalty)',
            32 => 'Casino Races',
            33 => 'SMS Fees',
            43 => 'Inactivity fees',
          ), $_GET['type'], array(), '', true) ?>
	</td>
      </tr>
      <tr>
        <td>Currency:</td>
        <td>
          <?php cisosSelect(true) ?>
        </td>
      </tr>
      <tr>
        <td>Part of Description:</td>
        <td>
          <?php dbInput('descr', $_REQUEST['descr']) ?>
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
          <?php phive("UserSearch")->csvBtn() ?>
	</td>        
      </tr>
    </table>
  </form>
  
  <?php phive('UserSearch')->showCsv($stats) ?>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
        <th>&nbsp;</th>
        <?php foreach(array_keys($stats[0]) as $headline): ?>          
          <th> <?php echo ucfirst($headline) ?> </th>
        <?php endforeach ?>
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach($stats as $row): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
        <td> <a href="/account/<?php echo $row['username'] ?>/" target="_blank" rel="noopener noreferrer">Account</a></td>
        <?php foreach($row as $col): ?>
          <td> <?php echo $col ?> </td>
        <?php endforeach ?>
      </tr>
      <?php $i++; endforeach; ?>
    </tbody>
  </table>
  <br clear="all" />
</div>
