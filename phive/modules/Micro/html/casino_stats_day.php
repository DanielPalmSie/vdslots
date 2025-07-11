<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$uh = phive("UserHandler");
$sday = '01';
$eday = '31';

if(!empty($_REQUEST['day']))
  $sday = $eday = $_REQUEST['day'];

$country_extra = '';
if(!p('stats.country.all')){
    //Can't pick country as user column, because then we would have a compound query, ie FI and NO
    if($_REQUEST['user_col'] == 'country')
        $_REQUEST['user_col'] = '';
    $allowed_country = pCountry('stats.country', '');
    $country_extra = " AND us.country = '$allowed_country' ";
}

$where_extra = $country_extra;
if(!empty($_REQUEST['user_col']) && !empty($_REQUEST['user_val'])){
    $join_users = true;
    $user_col = strtolower($_REQUEST['user_col']);
    if (($user_col == 'country') || ($user_col == 'province')) {
        $value = count(explode('-', $_REQUEST['user_val'])) > 1 ? explode('-', $_REQUEST['user_val'])[1] : $_REQUEST['user_val'];
        $where_extra .= " AND us.{$user_col} = '{$value}' ";
    } else {
        $where_extra .= " AND u.{$user_col} = '{$_REQUEST['user_val']}' ";
    }
}

if(!empty($_REQUEST['reg_start_date']) && !empty($_REQUEST['reg_end_date'])){
    $join_users = true;
    $where_extra .= " AND DATE(u.register_date) >= '{$_REQUEST['reg_start_date']}' AND DATE(u.register_date) <= '{$_REQUEST['reg_end_date']}' ";
}

if(!empty($_REQUEST['bonus_code'])){
    $join_users = true;
    $where_extra .= " AND u.bonus_code >= '{$_REQUEST['bonus_code']}' ";
    
}

if(isset($_REQUEST['month']) && empty($_REQUEST['month']) && !empty($_REQUEST['year'])){
  $group_by 	= "month";
  $start_date 	= $_REQUEST['year']."-01-01";
  $end_date 	= $_REQUEST['year']."-12-31";
}else{
  $start_date 	= empty($_REQUEST['year']) ? date('Y-m-01') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$sday";
  $end_date 	= empty($_REQUEST['year']) ? date('Y-m-t') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$eday";
  $group_by 	= 'day_date';
}

if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
  $start_date 	= $_REQUEST['sdate'];
  $end_date 	= $_REQUEST['edate'];
}

$group_by = empty($_REQUEST['user_group_by']) ? $group_by : $_REQUEST['user_group_by'];

if (strtolower($group_by) == 'country')
    $group_by = 'us.country';

if (strtolower($group_by) == 'province') {
    $group_by = 'us.province';
}

$_REQUEST['currency'] = pCur('stats', $_REQUEST['currency']);
if(empty($_REQUEST['currency']))
  $in_cur = 'EUR';


$tbl = empty($_REQUEST['tbl']) ? 'users_daily_stats' : $_REQUEST['tbl'];

$stats = $uh->getCasinoStats($start_date, $end_date, $group_by, $where_extra, '', '', '', $_REQUEST['currency'], $join_users, $in_cur, false, $tbl);

//foreach($stats as &$r)
//  $r['real_aff_fee'] += $r['aff_sub_prof'];

// TODO this is not product safe, will fetch everything, white labels too if we had had any
$sql_str = "SELECT SUM(amount) AS amount, currency, DATE(`timestamp`) as day_date FROM cash_transactions WHERE transactiontype = 5 AND `timestamp` BETWEEN '$start_date' AND '$end_date' GROUP BY currency";
// The call to partner.videoslots.com
$pr_payouts = prrpc([$sql_str, 'ASSOC', 'currency']);

$pr_day_date = current($pr_payouts)['day_date'];
if(!empty($in_cur)){
    $pr_amount = 0;
    foreach($pr_payouts as $cur => $sub)
        $pr_amount += chg($sub['currency'], $in_cur, $sub['amount'], 1, $sub['day_date']);
    $pr_payout = ['amount' => $pr_amount, 'day_date' => $pr_day_date];
}else{
    $pr_payout = $pr_payouts[$_REQUEST['currency']];
}

if(!empty($pr_payout)){
    foreach($stats as $date => &$stat){
        if($date == $pr_payout['day_date']){
            $stat['site_prof'] -= $pr_payout['amount'];
            $stat['real_aff_fee'] = $pr_payout['amount'];
        }
        
    }    
}

$num_cols = phive('UserHandler')->casinoStatsNumCols();
array_pop($num_cols);
$num_cols = array_merge($num_cols, ['ngr', 'site_prof', 'tax_deduction']);

$headline_cols = phive('UserHandler')->casinoStatsHeadlines();
array_pop($headline_cols);
$headline_cols = array_merge($headline_cols, ['NGR', 'Profit', 'Tax deduction']);

// Ska vara Bets-Wins-Jackpot deductions - TAX - Act. Tot - Loyalty = NGR
//Correction by Alex: Should be Bets - Wins - Jackpot Deductions - Tax - Act. Tot. + Fail Tot - Loyalty + Deductions = NGR.
foreach($stats as &$r){
    $r['ngr'] = $r['bets'] - $r['wins'] - $r['jp_contrib'] - $r['tax'] - $r['rewards'] + $r['fails'] - $r['paid_loyalty'] + $r['bank_deductions'];
}

advancedStatsTable($stats, strtolower($_REQUEST['user_group_by']), true, true, 'date', $num_cols, 'users_daily_stats', $headline_cols);

$cols = phive('SQL')->getColumns('users_daily_stats');
?>
<?php drawStartEndJs() ?>
<div class="pad10">
  Stats table used: <strong><?php echo $tbl ?></strong><br/>
  <form action="" method="get">
    <table border="0" cellspacing="5" cellpadding="5">
      <tr>
        <td>
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


            <tr>
                <td>Bonus Code:</td>
                <td>
                    <?php dbInput('bonus_code', empty($_REQUEST['bonus_code']) ? '' : $_REQUEST['bonus_code']) ?>
                </td>
            </tr>

            <tr>
                <td>Register start date:</td>
                <td>
                    <?php dbInput('reg_start_date', empty($_REQUEST['reg_start_date']) ? '' : $_REQUEST['reg_start_date']) ?>
                </td>
            </tr>

            <tr>
                <td>Register end date:</td>
                <td>
                    <?php dbInput('reg_end_date', empty($_REQUEST['reg_end_date']) ? '' : $_REQUEST['reg_end_date']) ?>
                </td>
            </tr>
            
            <?php drawStartEndHtml() ?>
            <tr>
              <td>Currency:</td>
              <td>
                <?php cisosSelect(true, $_REQUEST['currency']) ?>
              </td>
            </tr>
            <tr>
              <td>Group by User column (eg: country, sex, city, bonus_code, verified_phone):</td>
              <td>
                <?php dbInput('user_group_by', $_REQUEST['user_group_by']) ?>
              </td>
            </tr>
            <tr>
              <td>User column (eg: country, sex, city, bonus_code, verified_phone, province):</td>
              <td>
                <?php dbInput('user_col', $_REQUEST['user_col']) ?>
              </td>
            </tr>
            <tr>
              <td>User value (eg: se, Male/Female, stockholm, code, 0/1, CA-ON or just ON):</td>
              <td>
                <?php dbInput('user_val', $_REQUEST['user_val']) ?>
              </td>
            </tr>
            <tr>
              <td>Table:</td>
              <td>
                <?php dbSelect('tbl', array('users_daily_stats' => 'User Stats', 'users_daily_stats_total' => 'User Stats Total'), 'users_daily_stats') ?>
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
          <table border="0" cellspacing="5" cellpadding="5">
            <tr><td>Graph:</td></tr>
            <tr>
              <td>
                <?php dbSelect('graph_col[]', array_combine($cols, $cols), '', array(), '', true) ?>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </form>
</div>
