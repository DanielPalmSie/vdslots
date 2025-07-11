<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../Former/FormerCommon.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$fh = new FormerCommon();

$ciso = $_REQUEST['currency'] = pCur('stats', $_REQUEST['currency']);
if(empty($ciso))
    $in_cur = 'EUR';


$country_extra1 = '';
$country_extra2 = '';
$join_province = '';
$join_province2 = '';
$join_province3 = '';
$province = '';

if(!p('stats.country.all')) {
    //Can't pick country as user column, because then we would have a compound query, ie FI and NO
    if($_REQUEST['user_col'] == 'country') {
        $_REQUEST['user_col'] = '';
    }
    $allowed_country = pCountry('stats.country', '');
    $country_extra1 = " AND users.country = '$allowed_country' ";
    $country_extra2 = " AND u.country = '$allowed_country' ";
}

$aff    = phive('Affiliater');

extract(handleDatesSubmit());

$where_extra1 = $country_extra1;
$where_extra2 = $country_extra2;


if(!empty($_REQUEST['user_col']) && !empty($_REQUEST['user_val'])) {

    $user_column = trim($_REQUEST['user_col']) ?? null;
    $user_value = trim(strtoupper($_REQUEST['user_val'])) ?? null;
    $user_comp = urldecode($_REQUEST['user_comp']) ?? null;

    if ($user_column === 'province' && preg_match('/^[A-Z]{2}-[A-Z]{2}$/', $user_value)) {
        list($country, $province) = explode("-", $user_value);
       
        $join_province .= " INNER JOIN users_settings AS us ON us.user_id = users.id AND us.setting = 'main_province' AND us.value = '{$province}' ";
        $join_province2 .= " INNER JOIN users_settings AS us ON us.user_id = u.id AND us.setting = 'main_province' AND us.value = '{$province}' ";
        $join_province3 .= " INNER JOIN users_settings AS us ON us.user_id = udstats.user_id AND us.setting = 'main_province' AND us.value = '{$province}' ";

        $where_extra1 .= " AND users.country ".$user_comp." '{$country}' ";
        $where_extra2 .= " AND u.country ".$user_comp." '{$country}' ";
    } else {
        $where_extra1 .= " AND users.{$user_column} ".$user_comp." '{$user_value}'";
        $where_extra2 .= " AND u.{$user_column} ".$user_comp." '{$user_value}'";
    }
   
}

if(!empty($_REQUEST['reg_start_date']) && !empty($_REQUEST['reg_end_date'])) {
    $where_extra1 .= " AND DATE(users.register_date) >= '{$_REQUEST['reg_start_date']}' AND DATE(users.register_date) <= '{$_REQUEST['reg_end_date']}' ";
    $where_extra2 .= " AND DATE(u.register_date) >= '{$_REQUEST['reg_start_date']}' AND DATE(u.register_date) <= '{$_REQUEST['reg_end_date']}' ";
}

// We skip the scheme filtering for queries that doesn't involve deposits/first_deposits tables
// TODO double check if we need to force the filtering from those conditions too (in that case we must add a JOIN)
$new_extra1 = $where_extra1;
$new_extra2 = $where_extra2;

if(!empty($_REQUEST['scheme'])) {
    $where_extra1 .= " AND deposits.scheme = ".phive('SQL')->escape($_REQUEST['scheme']);
    $where_extra2 .= " AND cs.scheme = ".phive('SQL')->escape($_REQUEST['scheme']);
}


$bonus_code = empty($_REQUEST['bonus_code']) ? 'all' : $_REQUEST['bonus_code'];

if(!empty($_REQUEST['group_by']))
    $mgroup = $type = $_REQUEST['group_by'];

$sstamp                 = "$sdate 00:00:00";
$estamp                 = "$edate 23:59:59";

$new_sdate              = empty($_REQUEST['reg_start_date']) ? $sdate : $_REQUEST['reg_start_date'];
$new_edate              = empty($_REQUEST['reg_end_date']) ? $edate : $_REQUEST['reg_end_date'];

$bonus_code_extra       = (empty($bonus_code) || $bonus_code == 'all') ? '' : " AND u.bonus_code = '$bonus_code'";

$email_activated        = phive('UserHandler')->settingsByDate($new_sdate, $new_edate, 'email_code_verified', 'yes', $mgroup, false, $ciso, $new_extra2, $province);
$aff_email_activated    = phive('UserHandler')->settingsByDate($new_sdate, $new_edate, 'email_code_verified', 'yes', $mgroup, true, $ciso, $new_extra2.$bonus_code_extra, $province);

$aff_new_members 	= $aff->getUsersFromAffiliate($bonus_code, $new_sdate, $new_edate, $mgroup, $ciso, $new_extra1, false, $_REQUEST['node'], $join_province);
$new_members 		= $aff->getUsersFromAffiliate('', $new_sdate, $new_edate, $mgroup, $ciso, $new_extra1, false, $_REQUEST['node'], $join_province);

$aff_new_depositors     = $aff->getFirstDepositorsFromAffiliate($bonus_code, $sstamp, $estamp, $mgroup, $ciso, $where_extra1, $_REQUEST['deposit_alt'], $_REQUEST['node'], $join_province);
$new_depositors 	= $aff->getFirstDepositorsFromAffiliate('', $sstamp, $estamp, $mgroup, $ciso, $where_extra1, $_REQUEST['deposit_alt'], $_REQUEST['node'], $join_province);

$aff_depositors 	= $aff->getDepositorsFromAffiliate($bonus_code, $sstamp, $estamp, $mgroup, $_REQUEST['deposit_alt'], $ciso, $where_extra2, $_REQUEST['node'], $join_province2);
$depositors 		= $aff->getDepositorsFromAffiliate('', $sstamp, $estamp, $mgroup, $_REQUEST['deposit_alt'], $ciso, $where_extra2, $_REQUEST['node'], $join_province2);

$aff_deposits 		= $aff->getDepositsFromAffiliate($bonus_code, $sstamp, $estamp, $mgroup, $_REQUEST['deposit_alt'], $ciso, $where_extra1, $in_cur, '', $_REQUEST['node'], $join_province);
$deposits 		= $aff->getDepositsFromAffiliate('', $sstamp, $estamp, $mgroup, $_REQUEST['deposit_alt'], $ciso, $where_extra1, $in_cur, '', $_REQUEST['node'], $join_province);


//easy, = active
$betters                = phive('Cashier')->getBettersCount($sdate, $edate, $mgroup, $ciso, $allowed_country, $_REQUEST['node'], $join_province3);

if(!empty($_REQUEST['group_by'])){
    $tmp = array();
    foreach(array($aff_new_members, $aff_new_depositors, $aff_deposits, $aff_depositors, $new_members, $new_depositors, $deposits, $depositors) as $arr)
        $tmp = array_merge($tmp, array_keys($arr));
    $loop = array_unique($tmp);
}else if($type == 'month')
    $loop = $fh->getYearMonths($sdate, true, $edate);
else
    $loop = range(1, date('t', strtotime($edate)));

$tsarr = array(0 => 'date');

foreach(range(1, 10) as $key) {
    $tsarr[$key] = 'bigcurrency';
}

tableSorter("stats-table", $tsarr);

$stats = array();
foreach($loop as $mnum){
    $tmp = array();
    if(empty($_REQUEST['group_by']))
        $tmp[t( $type == 'month' ? 'month': 'day' )] = ($type == 'month' ? $mnum : padMonth($mnum)); //$mnum;
    else
        $tmp[$_REQUEST['group_by']] = $mnum;
    
    $tmp['Active']                = $betters[$mnum]["{$type}_count"] + 0; 
    $tmp[t('new.members')]        = $new_members[$mnum]["{$type}_count"] + 0;
    $tmp['V. Email']              = $email_activated[$mnum]["{$type}_count"];
    $tmp[t('new.depositors')]     = $new_depositors[$mnum]["{$type}_count"] + 0;
    $tmp[t('deposits')]           = $deposits[$mnum]["{$type}_count"] + 0;
    $tmp[t('depositors')]         = $depositors[$mnum]["{$type}_count"] + 0;
    $tmp[t('deposit.amount')]     = nfCents($deposits[$mnum]["{$type}_total"], true);
    $tmp['Dep. Ded. Amount']      = nfCents($deposits[$mnum]["ded_{$type}_total"], true);
    $tmp[t('aff.new.members')]    = $aff_new_members[$mnum]["{$type}_count"] + 0;
    $tmp['Aff. V. Email']         = $aff_email_activated[$mnum]["{$type}_count"];
    $tmp[t('aff.new.depositors')] = $aff_new_depositors[$mnum]["{$type}_count"] + 0;
    $tmp[t('aff.deposits')]       = $aff_deposits[$mnum]["{$type}_count"] + 0;
    $tmp[t('aff.depositors')]     = $aff_depositors[$mnum]["{$type}_count"] + 0;
    $tmp[t('aff.deposit.amount')] = nfCents($aff_deposits[$mnum]["{$type}_total"], true);
    $stats[] = $tmp;
}

?>
<div class="pad-stuff-ten" style="width: 1000px;">
    <?php yearDateForm('', true, true, true, false, true, true, true, false, true) ?>
    <br/>
    <h3><?php echo t('from').' '.$sdate.' '.t('to').' '.$edate.', all money numbers in '.(empty($ciso) ? "$in_cur (converted and summed)" : $ciso) ?></h3>
    Note that non-affiliate stats does not exclude affiliate stats.
    <?php phive('UserSearch')->showCsv($stats) ?>
    <h3><?php echo t('member.stats') ?></h3>
    <table id="stats-table" class="stats_table">
        <thead>
            <tr class="stats_header">
                <?php foreach(array_keys($stats[0]) as $headline): ?>
                    <th> <?php echo $headline ?> </th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; foreach($stats as $row): ?>
                <tr class="<?php echo wDayCls($i % 2 == 0 ? 'fill-odd' : 'fill-even', $mnum, true) ?>">
                    <?php foreach($row as $col): ?>
                        <td> <?php echo $col ?> </td>
                    <?php endforeach ?>
                </tr>
            <?php $i++; endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="stats_header">
                <td>  </td>
                <td> <?php echo phive()->sum2d($betters, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($new_members, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($email_activated, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($new_depositors, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($deposits, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($depositors, "{$type}_count") ?> </td>
                <td> <?php nfCents( phive()->sum2d($deposits, "{$type}_total") ) ?> </td>
                <td> <?php nfCents( phive()->sum2d($deposits, "ded_{$type}_total") ) ?> </td>
                <td> <?php echo phive()->sum2d($aff_new_members, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($aff_email_activated, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($aff_new_depositors, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($aff_deposits, "{$type}_count") ?> </td>
                <td> <?php echo phive()->sum2d($aff_depositors, "{$type}_count") ?> </td>
                <td> <?php nfCents( phive()->sum2d($aff_deposits, "{$type}_total") ) ?> </td>
            </tr>
        </tfoot>
    </table>
    <br clear="all" />
</div>
