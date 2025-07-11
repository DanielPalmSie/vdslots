<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
$sstamp                 = "$sdate 00:00:00";
$estamp                 = "$edate 23:59:59";

$aff 			= phive('Affiliater');

if(!empty($_REQUEST['sdate']) && !empty($_REQUEST['edate'])){
    $start_date 	= $_REQUEST['sdate'];
    $end_date 	= $_REQUEST['edate'];	
}else{
    $start_date 	= date('Y-m')."-01";
    $end_date 	= date('Y-m-t');
}

$_REQUEST['sdate'] = $start_date; 
$_REQUEST['edate'] = $end_date;

$sstamp            = "$start_date 00:00:00";
$estamp            = "$end_date 23:59:59";

$stats        = $aff->getCacheForAff('', $start_date, $end_date, 'affiliate', 'affiliate_daily_stats', array(), '', true, '', true);
$manager_ids  = phive('SQL')->makeIn(array_unique(phive()->_compact(phive()->arrCol($stats, 'aff_manager'))));
$managers     = phive('SQL')->loadKeyValues("SELECT * FROM users WHERE id IN($manager_ids)", 'id', 'username');

if(!p('complete.affiliate.overview')){
    //$aff_ids  = phive('SQL')->makeIn(phive()->arrCol($stats, 'affe_id'));
    //$settings = phive('SQL')->loadKeyValues("SELECT * FROM users_settings WHERE user_id IN($aff_ids) AND setting = 'aff_manager'", 'user_id', 'value');
    $cur_uid  = uid();
    $stats    = array_filter($stats, function($stat) use($cur_uid){
        return (int)$stat['aff_manager'] == (int)$cur_uid;
    });
}

$header_cols = array('Firstname','Username','Email','Before Deal', 'Profit', 'Currency', 'Deal (%)', 'Reg.', 'NDC', 'Dep. Am.');
$cols = array('firstname', 'username', 'email', 'before_deal', 'real_prof', 'currency');

$users_count = $aff->getUsersFromAffiliate('all', $start_date, $end_date, 'affiliate');
$deps_count  = $aff->getFirstDepositorsFromAffiliate('all', $sstamp, $estamp, 'affiliate');
$dep_amounts = $aff->getDepositsFromAffiliate('all', $sstamp, $estamp, 'affiliate', '', '', '', true);
$tsarr       = array('text', 'text', 'text', 'bigcurrency', 'bigcurrency', 'text', 'bigcurrency', 'bigcurrency', 'bigcurrency', 'bigcurrency', 'text');


tableSorter("stats-table", $tsarr);

$afftotal=0;
$before_deal=0;
$real_prof=0;

?>
<div class="pad-stuff-ten" style="width: 1000px;">
    <?php drawStartEndJs() ?>
    <br/>
    <form>
        <table>
            <?php drawStartEndHtml() ?>
            <tr>
                <td></td>
                <td><?php dbSubmit('Submit') ?></td>
            </tr>
        </table>
    </form>
    <br/>
    <table id="stats-table" class="stats_table">
        <thead>
            <tr class="stats_header">
                <?php foreach($header_cols as $headline): ?>
                    <th> <?php echo $headline ?> </th>
                <?php endforeach ?>
                <th> <?php echo 'Manager' ?> </th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; foreach($stats as $row): ?>
                <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
                    <?php foreach($cols as $col): ?>
                        <?php if($col == 'username'): ?>
                            <td><a href="/affiliate/account/<?php echo $row[$col] ?>/"> <?php echo $row[$col] ?> </a> </td>
                        <?php else: $$col+=$row[$col]; ?>
                            <td> <?php echo is_numeric($row[$col]) ? nfCents($row[$col]) : $row[$col] ?> </td>
                        <?php endif ?>          
                    <?php endforeach ?>
                    <td><?php echo $aff->getCasinoRatePercent($row['affe_id'], $row['gross']) * 100 ?></td>
                    <td><?php $ucounttotal+=$users_count[$row['affe_id']]['affiliate_count']; echo $users_count[$row['affe_id']]['affiliate_count'] ?></td>
                    <td><?php $depscounttotal+=$deps_count[$row['affe_id']]['affiliate_count']; echo $deps_count[$row['affe_id']]['affiliate_count'] ?></td>
                    <td><?php $afftotal+=$dep_amounts[$row['affe_id']]['affiliate_total']; echo nfCents($dep_amounts[$row['affe_id']]['affiliate_total']) ?></td>
                    <td><?php echo $managers[$row['aff_manager']] ?></td>
                </tr>
            <?php $i++; endforeach; ?>
            <tr style="background:#000000;color:#ffffff;">
                <td>Total <?=$i?></td>
                <td></td>
                <td></td>
                <td><?php echo nfCents($before_deal);?></td>
                <td><?php echo nfCents($real_prof)?></td>
                <td></td>
                <td></td>
                <td><?php echo $ucounttotal; ?></td>
                <td><?php echo $depscounttotal; ?></td>
                <td><?php echo nfCents($afftotal)?></td>
            </tr>
        </tbody>
    </table>
</div>
