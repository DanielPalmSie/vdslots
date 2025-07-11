<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$sql          = phive('SQL');
$eu_countries = explode(' ', phive('Config')->getValue('countries', 'eu'));
$eu_in        = $sql->makeIn($eu_countries);
$countries    = phive('Localizer')->getAllBankCountries('iso');
if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
  $start_date 	= $_REQUEST['sdate'];
  $end_date 	= $_REQUEST['edate'];
}else{
  $start_date 	= date('Y-m-01');
  $end_date 	= date('Y-m-t');
}

$display_currency = empty($_REQUEST['currency']) ? 'EUR' : $_REQUEST['currency'];
$cisos = cisos();
$networks = phive('MicroGames')->getNetworks();
$network_stats = array();
if(empty($_REQUEST['network'])){
  foreach($networks as $n)
    $network_stats[$n] = phive('MicroGames')->getTax($start_date, $end_date, $eu_in, $n, $_REQUEST['currency']);
}else
  $network_stats[$_REQUEST['network']] = phive('MicroGames')->getTax($start_date, $end_date, $eu_in, $_REQUEST['network'], $_REQUEST['currency']);

//$tmp          = phive('UserHandler')->getCasinoStats($start_date, $end_date, 'country', "AND u.country IN($eu_in)", '', '', '', '', true, 'EUR');
$tmp          = phive('UserHandler')->getCasinoStats($start_date, $end_date, 'us.country', "AND us.country IN($eu_in)", '', '', '', $display_currency, true);

//print_r($tmp);
//ta bort bank och op Fee
//lägg till jp contrib
$uh           = phive('UserHandler');
$cols         = array('country', 'bets', 'wins', 'jp_contrib', 'gross', 'rewards', 'tax_percent', 'tax', 'vat_percent', 'vat', 'cnt', 'reg_cnt');
$headlines    = array('Country', 'Wagers', 'Wins', 'JP Contrib', 'Gross', 'Player Rewards', 'Tax', 'Tax', 'VAT %', 'VAT', 'Active', 'New');
$stats        = array();
foreach($tmp as $iso => $r){
  $rewards     = $r['rewards'] + $r['paid_loyalty'];
  //$op_fees     = $r['op_fee'] + $r['jp_fee'];
  //$bank_fees   = $r['bank_fee'] + $r['real_aff_fee'];
  //$profit      = $r['gross'] - $rewards - $op_fees - $bank_fees;
  $stats[$iso] = array(
    'country'     => $iso,
    'bets'        => $r['bets'],
    'wins'        => $r['wins'],
    'jp_contrib'  => $r['jp_contrib'],
    'gross'       => $r['gross'],
    'rewards'     => $rewards,
    //'op_fees'     => $op_fees,
    //'bank_fees'   => $bank_fees,
    'reg_cnt'     => $uh->countInPeriod($start_date, $end_date, 'register_date', "AND country = '$iso'"),
    'cnt'         => count($uh->getDailyStats($start_date, $end_date, "AND country = '$iso' AND bets > 0 GROUP BY user_id")),
    'tax_percent' => $countries[$iso]['tax'],
    'tax'         => $r['tax'],
    'vat_percent' => $countries[$iso]['vat'] * 100,
    'vat'         => $r['gross'] * $countries[$iso]['vat']
  );
}

function drawStatsTable($stats, $headlines, $map){ ?>
<?php if(!empty($_REQUEST['as_csv'])): ?>
  <br>
  <br>
  <?php phive('UserSearch')->handleCsv($stats, $map, $headlines) ?>
<?php endif ?>
<table id="stats-table" class="stats_table">
  <thead>
    <tr class="stats_header">
      <?php foreach($headlines as $h): ?>
        <th><?php echo $h ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach($stats as $iso => $r): ?>
    <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
      <?php foreach($map as $col): ?>
        <td> <?php echo (is_numeric($r[$col]) && !in_array($col, array('cnt', 'reg_cnt', 'tax_percent'))) ? nfCents($r[$col]) : $r[$col] ?> </td>
      <?php endforeach ?>
    </tr>
    <?php $i++; endforeach ?>
  </tbody>
</table>  
<?php }

?>
<div class="pad10">
<br>
<strong><?php echo "From: $start_date To: $end_date" ?>. All numbers in <?php echo $display_currency ?>.</strong>
<br>
<br>
<?php foreach($network_stats as $network => $rows): ?>
  <br/>
  <strong><?php echo ucfirst($network) ?></strong>
  <?php drawStatsTable(
    $rows,
    array('Country', 'Wagers', 'Wins', 'JP Contrib', 'Gross', 'Player Rewards', 'Tax Rate', 'Tax', 'VAT Rate', 'VAT', 'Active'),
    array('country', 'bets', 'wins', 'jp_contrib', 'gross', 'rewards', 'tax_percent', 'tax', 'vat_percent', 'vat', 'cnt')) ?>
<?php endforeach ?>
<br>
<?php if(empty($_REQUEST['network'])) drawStatsTable($stats, $headlines, $cols) ?>
<br>
<?php drawStartEndJs() ?>
<form action="" method="get">

  <table>
    <?php drawStartEndHtml() ?>
    <tr>
      <td>Select Network</td>
      <td>
        <?php dbSelect('network', array_combine($networks, $networks), '', array('', 'Select')) ?>
      </td>
    </tr>
    <tr>
      <td>Select Currency</td>
      <td>
        <?php dbSelect('currency', array_combine($cisos, $cisos), '', array('', 'Select')) ?>
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

</div>
