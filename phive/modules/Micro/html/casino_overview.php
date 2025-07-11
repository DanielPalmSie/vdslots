<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function prNetwork($game_headlines, $game_stats, $game_cols, $top_head){ ?>
<strong><?php echo $top_head ?> </strong>
<table class="stats_table">
  <thead>
    <tr class="stats_header">
      <?php foreach($game_headlines as $h): ?>
	<th><?php echo $h ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach($game_stats as $network => $r): ?>
    <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
      <td>
	<?php echo $network ?>
      </td>
      <?php foreach($game_cols as $col): ?>
	<td> 
	  <?php if(in_array($col, array('rtp'))) echo $r[$col]; else nfCents($r[$col]); ?> 
	</td>
      <?php endforeach ?>
    </tr>
    <?php $i++; endforeach ?>
  </tbody>
</table>
<?php
phive('UserSearch')->showCsv($game_stats);
}

$start_date = $_REQUEST['sdate'];
$end_date = $_REQUEST['edate'];

if(empty($_REQUEST['sdate']))
  $start_date = date('Y-m-01');
if(empty($_REQUEST['edate']))
  $end_date 	= date('Y-m-t');

$uh 		= phive('UserHandler');
$stats 		= $uh->getCasinoStats($start_date, $end_date, 'currency', '', '', '', '', '', false, '', true);

$num_cols 	= $uh->casinoStatsNumCols();
$headlines 	= array_merge(array('Currency'), $uh->casinoStatsHeadlines());
$currencies     = phive("Currencer")->getAllCurrencies();
$daily_totals	= array();

$rtp_func       = function(&$el, $i){ $el['rtp'] = round(($el['wins'] / $el['bets']) * 100, 4); };

$mg 		= phive('MicroGames');
$game_stats 	= $mg->getGroupedStats($start_date, $end_date, 'network', "", true);

array_walk($game_stats, $rtp_func);

$game_headlines = array_merge(array('Network'), $mg->statsHeadlines(false));
$game_cols	= $mg->statsNumCols(false);

$cur_netws = array();
foreach($currencies as $code => $carr){
  $tmp = $mg->getGroupedStats($start_date, $end_date, 'network', " AND gs.currency = '$code'", false);
  array_walk($tmp, $rtp_func);
  $cur_netws[$code] = $tmp;
}

$game_cols[] = $game_headlines[] = 'rtp';

?>
<?php drawStartEndJs() ?>

<div class="pad-stuff-ten">
  <form action="" method="get">
    <Table border="0" cellspacing="5" cellpadding="5">
      <?php drawStartEndHtml() ?>
      <tr>
	<td>&nbsp;</td>
	<td>
	  <?php dbSubmit('Submit') ?>
          <?php phive("UserSearch")->csvBtn() ?>
	</td>
      </tr>
    </table>
  </form>
  <br/>
  <br/>
  <strong>
    <?php echo "Start date: $start_date, end date: $end_date" ?>
  </strong>
  <br/>
  <br/>
  <strong>Per Currency</strong>
  <table  class="stats_table">
    <thead>
      <tr class="stats_header">
	<?php foreach($headlines as $h): ?>
	  <th><?php echo $h ?></th>
	<?php endforeach ?>
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach($stats as $cur => $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
	<td>
	  <?php echo $cur ?>
	</td>
	<?php foreach($num_cols as $col): ?>
	  <td> 
	    <?php 
	    $daily_totals[$col] += $r[$col] / $currencies[$cur]['multiplier'];
	    nfCents($r[$col]); 
	    ?> 
	  </td>
	<?php endforeach ?>
      </tr>
      <?php $i++; endforeach ?>
    </tbody>
    <tfoot>
      <tr class="stats_header">
	<td>Total in EUR:</td>
	<?php foreach($daily_totals as $col): ?>
	  <td><?php echo nfCents($col) ?></td>
	<?php endforeach ?>
      </tr>
    </tfoot>
  </table>
  <?php phive('UserSearch')->showCsv($stats) ?>
  <?php foreach($cur_netws as $code => $stats): ?>
    <br/>
    <?php prNetwork($game_headlines, $stats, $game_cols, "Network Stats in $code:"); ?>
  <?php endforeach ?>
  <br/>
  <?php prNetwork($game_headlines, $game_stats, $game_cols, "Total Network Stats in EUR (converted and summed):"); ?>
</div>
