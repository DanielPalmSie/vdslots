<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$sdate = date('Y-m-d', strtotime('-30 month'));
$users = phive('SQL')->shs('merge', '', null, 'users')->loadArray("SELECT * FROM users WHERE DATE(last_login) < '$sdate' AND cash_balance > 0");
?>
<div class="pad-stuff-ten" style="width: 1000px;">
  <strong>Players without any transactions for 30 months having non-zero cash balance:</strong>  
  <?php if(empty($users)): ?>
    <p>No Players found.</p>
  <?php endif ?>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">        
        <th>Player</th>
        <th>Cash Balance</th>        
        <th>Currency</th>        
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach($users as $u): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
          <td><a href="<?php echo getUserBoLink($u['id']) ?>" target="_blank" rel="noopener noreferrer"><?php echo $u['id'] ?></a><br/></td>
          <td> <?php echo $u['cash_balance'] / 100 ?> </td>                
          <td> <?php echo $u['currency'] ?> </td>                
      </tr>
      <?php $i++; endforeach; ?>
    </tbody>
  </table>
  <br clear="all" />
</div>
