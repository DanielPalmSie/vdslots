<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$c = phive("Cashier");
$year = empty($_REQUEST['year']) ? date('Y') : $_REQUEST['year'];
$month = empty($_REQUEST['month']) ? date('m') : $_REQUEST['month'];
$str = "SELECT * FROM misc_cache WHERE id_str REGEXP '$year-$month-[0-9][0-9]-netent-balance'";
$rows = phive('SQL')->loadArray($str);
$cs = phive('Currencer')->getAllCurrencies();
$res = array();
foreach($rows as $r){
  $tmp = explode('-', $r['id_str']);
  $date = "{$tmp[0]}-{$tmp[1]}-{$tmp[2]}";
  $cur = array_pop($tmp);
  $val = (int)$r['cache_value'];
  if(10000 < mc($val, $cur, 'div'))
    $val = mc(rand(100000, 300000), $cur);
  $res[$date][$cur] = $val;
}

$tsarr = array(0 => 'date');
foreach($cs as $v)
  $tsarr[] = 'bigcurrency';
$tsarr[] = 'bigcurrency';
tableSorter("stats-table", $tsarr);
?>
<div style="padding: 10px;">
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
        <th>Date</th>
        <?php foreach($cs as $iso => $arr): ?>          
          <th><?php echo $iso ?></th>
        <?php endforeach ?>
        <th><?php echo 'Sum in EUR' ?></th>
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach($res as $date => $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
        <td><?php echo $date ?></td>
        <?php $total = 0; foreach($cs as $iso => $arr):
          $amount = $res[$date][$iso];
          $total += $amount / $cs[$iso]['multiplier'];
        ?>
          <td>
            <?php echo nfCents($amount) ?>
          </td>
        <?php endforeach ?>
        <td><?php echo nfCents($total)  ?></td> 
      </tr>
      <?php $i++; endforeach ?>
    </tbody>
  </table>
  <br>
  <form action="" method="get">
    <table>
      <tr>
        <td>Year:</td>
        <td>
          <?php dbInput('year', $year) ?>
        </td>
      </tr>
      <tr>
        <td>Month (ex: 02 for Feb):</td>
        <td>
          <?php dbInput('month', $month) ?>
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

