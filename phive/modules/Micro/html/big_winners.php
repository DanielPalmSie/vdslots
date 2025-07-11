<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$c = phive("Cashier");
$date = empty($_REQUEST['date']) ? phive()->yesterday() : $_REQUEST['date'];
$by_balance = json_decode(miscCache("$date-bigwin-balance"), true);
foreach($by_balance as &$b)
  $b['username'] = ud($b['user_id'])['username'];
$by_amount = json_decode(miscCache("$date-bigwin-amount"), true);
foreach($by_amount as &$a)
  $a['username'] = ud($a['user_id'])['username'];
?>
<div style="padding: 10px;">
  <strong>By Balance</strong>
  <br/>
  <br/>
  <?php printStatsTable(array_keys($by_balance[0]), $by_balance) ?>
  <br/>
  <br/>
  <strong>By Won Amount</strong>
  <br/>
  <br/>
  <?php printStatsTable(array_keys($by_amount[0]), $by_amount) ?>
  <br/>
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
