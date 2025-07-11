<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

setCur($_REQUEST['currency']);

if(!empty($_REQUEST['username'])){
  $user = phive("UserHandler")->getUserByUsername($_REQUEST['username']);
  if(is_object($user))
    setCur($user->getAttr('currency'));
}

$sday = '01';
$eday = '31';

$where_extra = '';

if(!empty($_REQUEST['user_col']) && !empty($_REQUEST['user_val'])){
    $join_users = true;
    $user_col = $_REQUEST['user_col'];
    $user_val = $_REQUEST['user_val'];
    $user_comp = urldecode($_REQUEST['user_comp']);
    // Province search limited to Canada. Matches pattern CA-ON (COUNTRY-PROVINCE), sets currency to CAD.
    if (strtolower($user_col) === 'province') {
        setCur('CAD');
        if (preg_match('/^[A-Z]{2}-[A-Z]{2}$/', strtoupper($user_val))) {
            list($country, $province) = explode("-", $user_val);
            $where_extra .= " AND us.{$user_col} {$user_comp} '{$province}' ";
            $where_extra .= " AND us.country = '{$country}'";
        } else {
            $where_extra .= " AND us.{$user_col} {$user_comp} '{$user_val}' ";
        }
    } else {
        $where_extra .= " AND u.{$user_col} {$user_comp} '{$user_val}' ";
    }
}

if(!empty($_REQUEST['stats_col']) && !empty($_REQUEST['stats_comp'])){
  $where_extra .= " AND us.{$_REQUEST['stats_col']} ".urldecode($_REQUEST['stats_comp'])." '{$_REQUEST['stats_val']}' ";
}

if($_REQUEST['user_active'] != ''){
  $join_users = true;
  $where_extra .= " AND u.active = {$_REQUEST['user_active']} ";
}

if(empty($_REQUEST['submit_all_time'])){
  if(!empty($_REQUEST['day']))
    $sday = $eday = $_REQUEST['day'];

  $start_date 	= empty($_REQUEST['year']) ? date('Y-m-01') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$sday";
  $end_date 		= empty($_REQUEST['year']) ? date('Y-m-t') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$eday";

  if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
    $start_date 	= $_REQUEST['sdate'];
    $end_date 		= $_REQUEST['edate'];
  }
}else{
  $start_date 	= "2011-03-10";
  $end_date 		= phive()->today();
}

$limit = empty($_REQUEST['limit']) ? 100 : $_REQUEST['limit'];

//$sdate, $edate, $username = '', $where_extra = '', $order_by = '', $order_type = '', $limit = '', $cur = ''
$stats = phive('UserHandler')->getCasinoStats($start_date, $end_date, !empty($_REQUEST['username']) ? $_REQUEST['username'] : 'us.user_id', $where_extra, $_REQUEST['order_by'], $_REQUEST['desc_asc'], $limit, ciso(), $join_users);

$num_cols = array('deposits', 'withdrawals', 'bets', 'wins', 'gross', 'rewards', 'fails', 'gen_loyalty',
                  'bank_fee', 'op_fee', 'real_aff_fee', 'bank_deductions', 'jp_contrib', 'site_prof');

$int_cols = array('ndeposits', 'nwithdrawals', 'nbusts');

$all_cols = array_merge($num_cols, $int_cols);

$tsarr = array(0 => 'string');

foreach ($all_cols as $value) {
  $tsarr[] = 'bigcurrency';
}

$csv_cols = !empty($_REQUEST['username']) ? array('date') : array();

if(!empty($_REQUEST['as_csv']))
  $csv_cols = array_merge($csv_cols, array('username'), $int_cols, $num_cols);

tableSorter("stats-table", $tsarr);

function printStatsDayUserFormCommon($all_cols){?>
<tr>
  <td>Sort By:</td>
  <td>
    <?php dbSelect('order_by', array_combine($all_cols, $all_cols)) ?>
  </td>
</tr>
<tr>
  <td>Order (desc or asc):</td>
  <td>
    <?php dbInput('desc_asc', empty($_REQUEST['desc_asc']) ? 'desc' : $_REQUEST['desc_asc']) ?>
  </td>
</tr>
<tr>
  <td>Limit:</td>
  <td>
    <?php dbInput('limit', empty($_REQUEST['limit']) ? 100 : $_REQUEST['limit']) ?>
  </td>
</tr>
<tr>
  <td>Username:</td>
  <td>
    <?php dbInput('username', $_REQUEST['username']) ?>
  </td>
</tr>
<tr>
  <td>Currency:</td>
  <td>
    <?php cisosSelect(true) ?>
  </td>
</tr>
<?php searchUserCol() ?>
<tr>
  <td>User blocked status:</td>
  <td>
    <?php dbSelect('user_active', array('1' => 'Not Blocked', '0' => 'Blocked'), $_REQUEST['user_active'], array('', 'Both')) ?>
  </td>
</tr>
</tr>
<tr>
  <td>Stats column:</td>
  <td>
    <?php dbInput('stats_col', $_REQUEST['stats_col']) ?>
  </td>
</tr>
</tr>
<tr>
  <td>Stats comparator, &gt; (greater than) , &lt; (lower than), = (equal) or != (not equal):</td>
  <td>
    <?php dbSelect('stats_comp', array(urlencode('>') => '>', urlencode('<') => '<', urlencode('=') => '=', urlencode('!=') => '!='), $_REQUEST['stats_comp']) ?>
  </td>
</tr>
<tr>
  <td>Stats value:</td>
  <td>
    <?php dbInput('stats_val', $_REQUEST['stats_val']) ?>
  </td>
</tr>
<?php }

?>
<div style="padding: 10px;">
  Note that if this function is used on the current month affiliate profits might end up being higher than shown here due to potential future income that
  moves the affiliate in question up in his commission structure.
  <br>
  <br>
  <strong>
    Note that generated loyalty is calculated "fresh" when the caching cron runs, it might not match the daily wager total as its effective time interval is between the cron job runs, not 0 -> 24.
  </strong>
  <br>
  <br>
  <table border="0" cellspacing="5" cellpadding="5">
    <tr>
      <td>
	<?php drawStartEndJs() ?>
	<form action="" method="get">
	  <table>
	    <tr>
	      <td>Year:</td>
	      <td>
		<?php dbInput('year', empty($_REQUEST['year']) ? date('Y') : $_REQUEST['year']) ?>
	      </td>
	    </tr>
	    <tr>
	      <td>Month:</td>
	      <td>
		<?php dbInput('month', empty($_REQUEST['month']) ? date('m') : $_REQUEST['month']) ?>
	      </td>
	    </tr>
	    <?php drawStartEndHtml() ?>
	    <tr>
	      <td>Day:</td>
	      <td>
		<?php dbInput('day', empty($_REQUEST['day']) ? '' : $_REQUEST['day']) ?>
	      </td>
	    </tr>
	    <?php printStatsDayUserFormCommon($all_cols) ?>
	    <tr>
	      <td>&nbsp;</td>
	      <td>
		<?php dbSubmit('Submit') ?>
		<?php phive("UserSearch")->csvBtn() ?>
	      </td>
	    </tr>
	  </table>
	</form>
      </td>
      <td>
	<strong>All time:</strong>
	<br>
	<form action="" method="get">
	  <table>
	    <?php printStatsDayUserFormCommon($all_cols) ?>
	    <tr>
	      <td>&nbsp;</td>
	      <td>
		<?php dbSubmit('Submit', 'submit_all_time') ?>
	      </td>
	    </tr>
	  </table>
	</form>
      </td>
    </tr>
  </table>
  <br>
  <?php phive('UserSearch')->prSendToBrowseUsersForm($stats) ?>
  <br>
  <br>
  <strong>Current cash: <?php efEuro( phive('Cashier')->getTotalCash(ciso()) ) ?></strong><br>
  <strong>Current bonus balances, all currencies: <?php nfCents( phive('Bonuses')->getTotalBalances() ) ?></strong>
  <br>
  <br>
  <strong><?php echo "From: $start_date To: $end_date" ?></strong>
  <br>
  <br>
  <strong>All numbers in <?php ciso(true) ?>.</strong>
  <?php if(!empty($_REQUEST['as_csv'])): ?>
    <br>
    <br>
    <?php phive('UserSearch')->handleCsv($stats, $csv_cols, $csv_cols) ?>
  <?php endif ?>
  <br>
  <br>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
	<?php if(!empty($_REQUEST['username'])): ?>
	  <th>Date</th>
	<?php endif ?>
	<th>Username</th>
	<th>N D</th>
	<th>N W</th>
	<th>N B</th>
	<th>Deposits</th>
	<th>Withdrawals</th>
	<th>Bets</th>
	<th>Wins</th>
	<th>Gross</th>
	<th>Act Tot</th>
	<th>Fail Tot</th>
	<th>Gen Loyalty</th>
	<th>Bank Fees</th>
	<th>Op Fees</th>
	<th>Aff Prof</th>
	<th>Deductions</th>
	<th>JP Contrib</th>
  <th>Site Prof</th>
  <th>Backend</th>
      </tr>
    </thead>
    <?php $i = 0; foreach($stats as $r): ?>
    <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
      <?php if(!empty($_REQUEST['username'])): ?>
	<td> <?php echo !empty($_REQUEST['submit_all_time']) ? 'All Time' : $r['date'] ?> </td>
      <?php endif ?>
      <td> <a href="<?php echo llink("/admin/userprofile?username={$r['username']}") ?>"><?php echo $r['username'] ?></a> </td>
      <?php foreach($int_cols as $icol): ?>
	<td> <?php echo $r[$icol] ?> </td>
      <?php endforeach ?>
      <?php foreach($num_cols as $col): ?>
	<td> <?php echo nfCents($r[$col]) ?> </td>
      <?php endforeach ?>
    <td><a href="/admin/userprofile/?username=<?= $r['username'] ?>">profile</a></td>
    </tr>
    <?php $i++; endforeach ?>
    <tfoot>
      <tr class="stats_header">
	<td>&nbsp;</td>
	<?php if(!empty($_REQUEST['username'])): ?>
	  <td>&nbsp;</td>
	<?php endif ?>
	<?php foreach($int_cols as $icol): ?>
	  <td> <?php echo phive()->sum2d($stats, $icol) ?> </td>
	<?php endforeach ?>
	<?php foreach($num_cols as $col): ?>
	  <td> <?php echo nfCents(phive()->sum2d($stats, $col)) ?> </td>
	<?php endforeach ?>
      </tr>
    </tfoot>
  </table>
  <br>
</div>
