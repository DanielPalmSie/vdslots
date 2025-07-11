<?php
require_once __DIR__ . '/../../../admin.php';
$year    = empty($_GET['year']) ? date('Y') : $_GET['year'];
$month   = empty($_GET['month']) ? date('m') : $_GET['month'];
$f       = new FormerCommon();
$days    = $f->getDaysInMonth($year, $month);
foreach($days as &$d)
  $d = "$year-$month-$d";
$weeks   = array_chunk($days, 7);
?>
<div class="pad10">
  <form method="GET" action="">
    <table>
      <tr>
        <td>Year</td>
        <td><?php dbInput('year', $year) ?></td>
      </tr>
      <tr>
        <td>Month</td>
        <td><?php dbInput('month', $month) ?></td>
      </tr>
    </table>
    <?php dbSubmit('Submit') ?>
  </form>
  <br/>
  <br/>
  <table class="stats-table">
    <?php foreach($weeks as $w): ?>
      <tr>
        <?php foreach($w as $day): ?>
          <td style="vertical-align: top; background-color: #ccc; padding: 5px;">
            <?php echo $day ?>
            <br/>
            <strong>
              <?php echo phive('MailHandler2')->mailSchedule($day) ?>
            </strong>
          </td>
        <?php endforeach ?>
      </tr>
    <?php endforeach ?>
  </table>
</div>
