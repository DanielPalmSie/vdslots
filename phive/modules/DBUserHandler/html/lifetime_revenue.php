<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$uh = phive("UserHandler");


$reg_start_date = empty($_REQUEST['reg_start_date']) ? '2010-01-01' : $_REQUEST['reg_start_date'];
$reg_end_date = empty($_REQUEST['reg_end_date']) ? phive()->today() : $_REQUEST['reg_end_date'];
$start_date = empty($_REQUEST['start_date']) ? '2010-01-01' : $_REQUEST['start_date'];
$end_date = empty($_REQUEST['end_date']) ? phive()->today() : $_REQUEST['end_date'];

if(!empty($_REQUEST['submit'])){
  $res = $uh->lifetimeRevStats($reg_start_date, $reg_end_date, $start_date, $end_date, $_REQUEST['bonus_code'], $_REQUEST['country'], $_REQUEST['currency']);
}

?>
<?php advancedStatsTable($res, 'ym', false, false, 'ym') ?>
<div class="pad10">  
<form action="" method="get">
  <table>
    <tr>
      <td>Start Date of Registration:</td>
      <td>
        <?php dbInput('reg_start_date', $reg_start_date) ?>
      </td>
    </tr>
    <tr>
      <td>End Date of Registration:</td>
      <td>
        <?php dbInput('reg_end_date', $reg_end_date) ?>
      </td>
    </tr>
    <tr>
      <td>Start Date of Play period:</td>
      <td>
        <?php dbInput('start_date', $start_date) ?>
      </td
    </tr>
    <tr>
      <td>End Date of Play period:</td>
      <td>
        <?php dbInput('end_date', $end_date) ?>
      </td>
    </tr>
    <tr>
      <td>Bonus Code:</td>
      <td>
        <?php dbInput('bonus_code', $_REQUEST['bonus_code']) ?>
      </td>
    </tr>
    <tr>
      <td>Country (iso2):</td>
      <td>
        <?php dbInput('country', $_REQUEST['country']) ?>
      </td>
    </tr>
    <tr>
      <td>Currency:</td>
      <td>
        <?php cisosSelect(true) ?>
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



