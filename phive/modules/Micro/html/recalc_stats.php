<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

if(!empty($_REQUEST['date'])){
  phive('Cashier')->recalcDay($_REQUEST['date'], false, true, false);
  echo "<br/>Stats for {$_REQUEST['date']} has been calculdated.<br/>";
}
?>
<div class="pad10">
  <form action="" method="get">
    <table>
      <tr>
        <td>Date:</td>
        <td>
          <?php dbInput('date') ?>
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
