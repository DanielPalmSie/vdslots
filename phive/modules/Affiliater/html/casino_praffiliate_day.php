<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

setCur($_REQUEST['currency']);

if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
  $start_date 	= $_REQUEST['sdate'];
  $end_date 		= $_REQUEST['edate'];	
}else{
  $start_date 	= date('Y-m')."-01";
  $end_date 		= date('Y-m-t');
}

$_REQUEST['sdate'] = $start_date; 
$_REQUEST['edate'] = $end_date;

if(empty($_REQUEST['group_by']))
  $_REQUEST['group_by'] = 'affiliate';

if(empty($_REQUEST['use_table']))
  $_REQUEST['use_table'] = 'affiliate_daily_stats';

//$affe_id, $sdate, $edate, $by_month = false, $tbl = 'affiliate_daily_stats', $extra = array(), $cur = ''
//users_daily_stats affiliate_daily_stats
$stats = phive('PRAffiliater')->getCacheForAff('', $start_date, $end_date, $_REQUEST['group_by'], $_REQUEST['use_table'], array(), ciso(), $_REQUEST['only_real'], $_REQUEST['bonus_code']);

if(in_array($_REQUEST['use_table'], array('affiliate_daily_stats', 'affiliate_daily_bcodestats'))){
  $num_cols = array('bets', 'wins', 'gross', 'admin_fee', 'deposits', 'rewards', 'fails', 'op_fees', 'bank_fees', 'jp_fee', 'tax', 'paid_loyalty', 'prof', 'real_prof');
  $header_cols = array('Bets','Wins','Gross', "Admin Fee",'Deposits','Act. Tot.','Fail Tot.','Op. Fees','Bank Fees', 'JP Fee', 'Tax', 'Loyalty', 'Aff. Prof.', 'Aff. Real Prof.');
}

if($_REQUEST['use_table'] == 'users_daily_stats'){
  $num_cols = array('bets', 'wins', 'gross', 'deposits', 'withdrawals', 'rewards', 'fails', 'bank_fee', 'site_prof', 'nbusts');
  $header_cols = array('Bets','Wins','Gross','Deposits', 'Withdrawals', 'Act. Tot.','Fail Tot.', 'Bank Fees', 'Site. Prof.', '# Busts');
}

$total_cols = array('deposits', 'withdrawals', 'bets', 'wins', 'gross', 'rewards', 'fails', 'paid_loyalty', 'bank_fee', 'op_fee', 'real_aff_fee', 'bank_deductions', 'jp_contrib', 'site_prof');

if(!empty($_REQUEST['graph_total'])){
  $group_total_by = $_REQUEST['group_by'] == 'day_date' ? 'day_date' : 'month';
  $total_stats 	= phive('UserHandler')->getCasinoStats($start_date, $end_date, $group_total_by, '', '', '', '', ciso());
  $to_graph 		= $stats;
  $graph_cols 	= $_REQUEST['graph_col'];
  foreach($_REQUEST['graph_total'] as $total_col){
    foreach($total_stats as $d => $sub){
      $to_graph[$d]["Total $total_col"] = $sub[$total_col];
    }
    $graph_cols[] = "Total $total_col";
  }  
}else{
  $to_graph 	= $stats;
  $graph_cols = $_REQUEST['graph_col']; 
}


$tsarr = array(0 => 'text');

foreach ($num_cols as $value) 
  $tsarr[] = 'bigcurrency';

if(!empty($_REQUEST['as_csv']))
  $csv_cols = array_merge(array($_REQUEST['group_by']), $num_cols);

tableSorter("stats-table", $tsarr);

?>
<div style="padding: 10px;">
  Note that if this function is used on the current month affiliate profits might end up being higher than shown here due to potential future income that
  moves the affiliate in question up in his commission structure.
  <br>
  <br>
  <?php drawStartEndJs() ?>
  <form action="" method="get">
    <table>
      <tr>
	<td>
	  <table>
	    <?php drawStartEndHtml() ?>
	    <tr>
	      <td>Currency:</td>
	      <td>
		<?php cisosSelect(false, ciso()) ?>
	      </td>
	    </tr>
	    <tr>
	      <td>Show only "real" affiliates:</td>
	      <td>
		<?php dbCheck("only_real", $_REQUEST['only_real']) ?>
	      </td>
	    </tr>
	    <tr>
	      <td>Use table:</td>
	      <td>
		<?php dbSelect("use_table", array('users_daily_stats' => 'Daily user cache', 'affiliate_daily_bcodestats' => "Daily affiliate bonus code stats"), $_REQUEST['use_table']) ?>
	      </td>
	    </tr>
	    <tr>
	      <td>Bonus code (if not empty the table used will <br/> be the daily bonus code stats table):</td>
	      <td>
		<?php dbInput("bonus_code", $_REQUEST['bonus_code']) ?>
	      </td>
	    </tr>
	    <tr>
	      <td>Sum by:</td>
	      <td>
		<?php dbSelect("group_by", array('affiliate' => 'Affiliate', 'bonus_code' => 'Bonus Code', 'month_num' => "Month", 'day_date' => 'Date'), $_REQUEST['group_by']) ?>
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
	</td>
	<td>
	  <table>
	    <tr><td>Graph:</td></tr>
	    <tr>
	      <td>
		<?php dbSelect('graph_col[]', array_combine($num_cols, $num_cols), '', array(), '', true) ?>
	      </td>
	    </tr>
	  </table>
	</td>
	<td>
	  <table>
	    <tr><td>Compare with total:</td></tr>
	    <tr>
	      <td>
		<?php dbSelect('graph_total[]', array_combine($total_cols, $total_cols), '', array(), '', true) ?>
	      </td>
	    </tr>
	  </table>
	</td>
      </tr>
    </table>
  </form>
  <br>
  <strong>All numbers in <?php ciso(true) ?>.</strong>
  <?php if(!empty($_REQUEST['as_csv'])): ?>
    <br>
    <br>
    <?php phive('UserSearch')->handleCsv($stats, $csv_cols, $csv_cols) ?>
  <?php endif ?>
  <br>
  <br>
  <?php highChart($to_graph, $graph_cols, 'day_date') ?>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
	<th>&nbsp;</th>
	<?php foreach($header_cols as $col): ?>
	  <th><?php echo $col ?></th>
	<?php endforeach ?>
      </tr>
    </thead>
    <tbody>
      <?php $i = 0; foreach($stats as $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
	<td> 
	  <?php if($_REQUEST['group_by'] == 'affiliate'): ?>
<!--	    <a href="--><?php //echo getUsersFromAffiliateLink($start_date, $end_date, $r['affe_id'], ciso(), $_REQUEST['bonus_code']) ?><!--">-->
<!--	      -&gt;-->
<!--	    </a>-->
<!--	    &nbsp;-->
<!--	    <a href="--><?php //echo llink("/affiliate/account/{$r['username']}/playerstats/") ?><!--">-->
	      <?php echo $r['username'] ?>
<!--	    </a> -->
	  <?php elseif($_REQUEST['group_by'] == 'bonus_code'): ?>
	    <?php echo $r[ 'bonus_code' ] ?>
	  <?php else: ?>
	    <?php echo $r[ 'day_date' ] ?>
	  <?php endif ?>
	</td>
	<?php foreach($num_cols as $col): ?>
	  <td> <?php nfCents($r[$col]) ?> </td>
	<?php endforeach ?>
      </tr>
      <?php $i++; endforeach ?>
    </tbody>
    <tfoot>
      <tr class="stats_header">
	<td>&nbsp;</td>
	<?php foreach($num_cols as $col): ?>
	  <td> <?php echo nfCents(phive()->sum2d($stats, $col)) ?> </td>
	<?php endforeach ?>
      </tr>
    </tfoot>
  </table>
  <br>
</div>
