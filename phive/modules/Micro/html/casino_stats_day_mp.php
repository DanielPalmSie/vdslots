<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$uh = phive("UserHandler");
$sday = '01';
$eday = '31';

$join_province = "";
$where_extra = "";

if(!empty($_REQUEST['day']))
  $sday = $eday = $_REQUEST['day'];

if(!empty($_REQUEST['user_col']) && !empty($_REQUEST['user_val'])){
    $join_users = true;

	$user_column = phive('SQL')->escapeColumn($_REQUEST['user_col']) ?? null;
	$user_value = phive('SQL')->escape(strtoupper($_REQUEST['user_val']), false) ?? null;

	// Checks if the province value match the following pattern CA-ON, {COUNTRY}-{PROVINCE}
	if ($user_column === 'province' && preg_match('/^[A-Z]{2}-[A-Z]{2}$/', $user_value)) {
		list($country, $province) = explode("-", $user_value);

		$join_province .= " INNER JOIN users_settings as uset ON uset.user_id = u.id and uset.setting = 'main_province' and uset.value = '{$province}' ";
		$where_extra .= " AND u.country = '{$country}' ";
	} else {
		$where_extra = " AND u.$user_column = '$user_value' ";
	}
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
if(empty($_REQUEST['currency']))
  $in_cur = 'EUR';

//$tbl = empty($_REQUEST['tbl']) ? 'users_daily_stats' : $_REQUEST['tbl'];

$tbl = 'users_daily_stats_mp';

$stats = $uh->getCasinoStatsMp($start_date, $end_date, $group_by, $where_extra, '', '', '', $_REQUEST['currency'], $join_users, $in_cur, $_REQUEST['category'], $_REQUEST['prize_type'], $_REQUEST['network'], $join_province);

$networks = phive('MicroGames')->getNetworks(true);

advancedStatsTable($stats, $_REQUEST['user_group_by'], true, true, 'date', 'casinoMpStatsNumCols', $tbl, 'casinoStatsMpHeadlines', array('t_id'));
$cols      = phive('SQL')->getColumns($tbl);
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
              <td>User column (eg: country, province, sex, city, bonus_code, verified_phone):</td>
              <td>
                <?php dbInput('user_col', $_REQUEST['user_col']) ?>
              </td>
            </tr>
            <tr>
              <td>User value (eg: se, Male/Female, stockholm, code, 0/1):</td>
              <td>
                <?php dbInput('user_val', $_REQUEST['user_val']) ?>
              </td>
            </tr>
            <tr>
              <td>Category:</td>
              <td>
                <?php dbSelect('category', ['normal' => 'Normal', 'freeroll' => 'Freeroll', 'added' => 'Added', 'guaranteed' => 'Guaranteed', 'jackpot' => 'Jackpot'], $_REQUEST['category'], ['', 'Select']) ?>
              </td>
            </tr>
            <tr>
              <td>Prize Type:</td>
              <td>
                <?php dbSelect('prize_type', ['win-prog' => 'Win Prog', 'win-fixed' => 'Win Fixed'], $_REQUEST['prize_type'], ['', 'Select']) ?>
              </td>
            </tr>
            <tr>
                <td>Network:</td>
                <td>
                    <?php dbSelect('network', $networks, $_REQUEST['network'], ['', 'Select']) ?>
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
