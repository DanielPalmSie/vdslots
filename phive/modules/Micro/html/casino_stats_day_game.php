<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$mg = phive('MicroGames');

$sday = '01';
$eday = '31';

$join_users = false;
$join_extra = '';

setCur($_REQUEST['currency']);

if(!empty($_REQUEST['day']))
    $sday = $eday = $_REQUEST['day']; 

$start_date 	= empty($_REQUEST['year']) ? date('Y-m-01') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$sday";
$end_date 		= empty($_REQUEST['year']) ? date('Y-m-t') : "{$_REQUEST['year']}-{$_REQUEST['month']}-$eday"; 

if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
    $start_date 	= $_REQUEST['sdate'];
    $end_date 		= $_REQUEST['edate'];	
}

if(empty($_REQUEST['game'])){
    $where_extra = '';
    $group_by = 'gs.game_ref, gs.device_type';
}else{
    $where_extra = " AND gs.game_ref = '{$_REQUEST['game']}' ";
    $group_by = 'gs.date';
}

if(!empty($_REQUEST['user_col']) && !empty($_REQUEST['user_val'])){
    $join_users = true;
    $user_column = phive('SQL')->escapeColumn($_REQUEST['user_col']) ?? null;
    $user_value = phive('SQL')->escape(strtoupper($_REQUEST['user_val']), false) ?? null;

    // Checks if the country value match the following pattern CA-ON, {COUNTRY}-{PROVINCE}
    if ($user_column === 'province' && preg_match('/^[A-Z]{2}-[A-Z]{2}$/', $user_value)) {
        list($country, $province) = explode("-", $user_value);
        $join_extra .= " INNER JOIN users_settings as us ON us.user_id = u.id and us.setting = 'main_province' and us.value = '{$province}' ";
        $where_extra .= " AND u.country " .  urldecode($_REQUEST['user_comp']) . " '{$country}' ";
    } else {
        $where_extra .= " AND u.{$_REQUEST['user_col']} ".urldecode($_REQUEST['user_comp'])." '{$_REQUEST['user_val']}' ";
    }
}


$cur = ciso();
$where_extra .= " AND gs.currency = '$cur' ";

//if(!empty($_REQUEST['device_type']))
//	$where_extra .= " AND mg.device_type = '{$_REQUEST['device_type']}' ";

if(!empty($_REQUEST['network']))
    $where_extra .= " AND gs.network = '{$_REQUEST['network']}' ";

//phive('SQL')->debug = true;
$stats = $mg->getDailyGameStats($start_date, $end_date, $group_by, $where_extra, $join_users, $_REQUEST['device_type'], false, $join_extra);
//phive("SQL")->printDebug();

$num_cols = $mg->statsNumCols();

$tsarr = array(0 => 'date', 1 => 'integer', 2 => 'text', 3 => 'integer');

foreach ($num_cols as $value) {
    $tsarr[] = 'bigcurrency';
}

$csv_cols = empty($_REQUEST['game']) ? array() : array('date');

if(!empty($_REQUEST['as_csv']))
    $csv_cols = array_merge($csv_cols, array('game_name', 'device_type', 'played_times'), $num_cols);

tableSorter("stats-table", $tsarr);

?>
<div style="padding: 10px;">
    <br>
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
	    <tr>
		<td>Day:</td>
		<td>
		    <?php dbInput('day', $_REQUEST['day']) ?>
		</td>
	    </tr>
	    <?php drawStartEndHtml() ?>
	    <tr>
		<td>Device Type (html5, flash or android):</td>
		<td>
		    <?php dbInput('device_type', $_REQUEST['device_type']) ?>
		</td>
	    </tr>
	    <tr>
		<td>Network (bsg, microgaming, nyx):</td>
		<td>
		    <?php dbInput('network', $_REQUEST['network']) ?>
		</td>
	    </tr>
	    <tr>
		<td>Currency:</td>
		<td>
		    <?php cisosSelect(true, $_REQUEST['currency']) ?>
		</td>
	    </tr>
	    <tr>
		<td>Game:</td>
		<td>
		    <?php dbSelect("game", phive("MicroGames")->selAllGamesShowDevice('ext_game_name'), '', array('', 'Select Game')) ?>
		</td>
	    </tr>
	    <?php searchUserCol() ?>
	    <tr>
		<td>&nbsp;</td>
		<td>
		    <?php dbSubmit('Submit') ?>
		    <?php phive("UserSearch")->csvBtn() ?>
		</td>
	    </tr>
        </table>
    </form>
    <br>
    <strong>Current cash: <?php efEuro( phive('Cashier')->getTotalCash(ciso()) ) ?></strong><br>
    <strong>Current bonus balances, all currencies: <?php nfCents( phive('Bonuses')->getTotalBalances() ) ?></strong>
    <?php if(!empty($_REQUEST['as_csv'])): ?>
        <br>
        <br>
        <?php phive('UserSearch')->handleCsv($stats, $csv_cols, $csv_cols) ?>
    <?php endif ?>
    <br>
    <br>
    <strong><?php echo "From: $start_date To: $end_date" ?></strong>
    <br>
    <br>
    <strong><?php echo "All numbers in ".ciso() ?>.</strong>
    <br>
    <br>
    <table class="stats_table" id="stats-table">
	<thead>
	    <tr class="stats_header">
		<?php foreach($mg->statsHeadlines() as $h): ?>
		    <th><?php echo $h ?></th>
		<?php endforeach ?>
	    </tr>
	</thead>
	<tbody>
	    <?php $i = 0; foreach($stats as $r): ?>
		<tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
		    <td> <?php echo empty($_REQUEST['game']) ? '' : $r['date'] ?> </td>
		    <td> <?php echo $r['game_name'] ?> </td>
		    <td> <?php echo phive("Casino")->getDeviceStr($r['device_type']) ?> </td>
		    <td> <?php echo $r['played_times'] ?> </td>
		    <?php foreach($num_cols as $col): ?>
			<td> <?php nfCents($r[$col]) ?> </td>
		    <?php endforeach ?>
		</tr>
	    <?php $i++; endforeach ?>
	</tbody>
	<tfoot>
	    <tr class="stats_header">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td> <?php echo phive()->sum2d($stats, 'played_times') ?> </td>
		<?php foreach($num_cols as $col): ?>
		    <td> <?php echo nfCents(phive()->sum2d($stats, $col)) ?> </td>
		<?php endforeach ?>
	    </tr>
	</tfoot>
    </table>
</div>
