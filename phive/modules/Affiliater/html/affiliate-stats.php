<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../Former/FormerCommon.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function addToTotal(&$total, $amount){
  $total += $amount;
  return $amount;
}

function playerStats($affe_id, $width = 940){
  $fh   = new FormerCommon();
  $aff 	= phive('Affiliater');

  extract(handleDatesSubmit());

  $where_extra = empty($_REQUEST['bonus_code']) ? '' : "bonus_code = '{$_REQUEST['bonus_code']}'";

  if(!empty($_REQUEST['bonus_code'])){
    $where_extra1 = " AND users.bonus_code = '{$_REQUEST['bonus_code']}' ";
    $where_extra2 = " AND u.bonus_code = '{$_REQUEST['bonus_code']}' ";
  }

  $bonus_code = empty($_REQUEST['bonus_code']) ? $affe_id : $_REQUEST['bonus_code'];

  $sstamp                 = "$sdate 00:00:00";
  $estamp                 = "$edate 23:59:59";
  
  $new_members 	          = $aff->getUsersFromAffiliate($bonus_code, $sdate, $edate, $mgroup, '', $where_extra1);
  $new_depositors         = $aff->getFirstDepositorsFromAffiliate($bonus_code, $sdate, $edate, $mgroup, '', $where_extra1);
  $deposits               = $aff->getDepositsFromAffiliate($bonus_code, $sstamp, $estamp, $mgroup, '', '', $where_extra1);
  $depositors             = $aff->getDepositorsFromAffiliate($bonus_code, $sdate, $edate, $mgroup, '', '', $where_extra2);

  $stats 		  = $aff->getCacheForAff($affe_id, $sdate, $edate, $mgroup, 'affiliate_daily_stats', array(), '', false, $_REQUEST['bonus_code']);
  
  foreach($stats as &$r)
    $r['op_fees'] += $r['tax'];
    
  $sums 		= $aff->sumCachedColsForAff($affe_id, $sdate, $edate);
  $has_sub 		= $aff->hasSubDeal($affe_id);

  if($has_sub){
    $sub_stats 		= $aff->getCacheForAff($affe_id, $sdate, $edate, $mgroup, 'sub_casino_affiliate_earnings');
  }

  $rate			= $aff->getCasinoRatePercent($affe_id, max(0, $sums['before_deal']));
  $sub_rate		= $aff->getCasinoRatePercent($affe_id, max(0, $sub_sums['before_deal']), 'sub');

  $total		= 0;
  $sub_total		= 0;

  if($type == 'month')
    $loop = $fh->getYearMonths($sdate, true, $edate);
  else
    $loop = range(1, date('t', strtotime($edate)));

?>
<?php yearDateForm($affe_id, false, false, false, true) ?>
<br/>
<h3><?php echo t('from').' '.$sdate.' '.t('to').' '.$edate ?></h3>
<br/>
<h3><?php echo t('member.stats') ?></h3>
<table class="zebra-tbl" style="width:<?php echo $width ?>px;">
  <tr class="zebra-header">
    <td> <?php et( $type == 'month' ? 'month': 'day' ) ?> </td>
    <td> <?php et('new.members') ?> </td>
    <td> <?php et('new.depositors') ?> </td>
    <td> <?php et('deposits') ?> </td>
    <td> <?php et('depositors') ?> </td>
    <td> <?php et('deposit.amount') ?> </td>
  </tr>
  <?php $i = 0; foreach($loop as $mnum): ?>
  <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
    <td> <?php echo $type == 'month' ? phive()->lcDate($mnum, '%b %g') : phive()->lcDate($year.'-'.$month.'-'.padMonth($mnum), '%b %d') ?> </td>
    <td> <?php echo $new_members[$mnum]["{$type}_count"] + 0 ?> </td>
    <td> <?php echo $new_depositors[$mnum]["{$type}_count"] + 0 ?> </td>
    <td> <?php echo $deposits[$mnum]["{$type}_count"] + 0 ?> </td>
    <td> <?php echo $depositors[$mnum]["{$type}_count"] + 0 ?> </td>
    <td> <?php efEuro($stats[$mnum]["deposits"]) ?> </td>
  </tr>
  <?php $i++; endforeach; ?>
  <tr class="zebra-header">
    <td>  </td>
    <td> <?php echo phive()->sum2d($new_members, "{$type}_count") ?> </td>
    <td> <?php echo phive()->sum2d($new_depositors, "{$type}_count") ?> </td>
    <td> <?php echo phive()->sum2d($deposits, "{$type}_count") ?> </td>
    <td> <?php echo phive()->sum2d($depositors, "{$type}_count") ?> </td>
    <td> <?php efEuro( phive()->sum2d($stats, "deposits") ) ?> </td>
  </tr>
</table>
<br clear="all" />
<h3><?php et('revenue.stats') ?></h3>
<table class="zebra-tbl" style="width:<?php echo $width ?>px;">
  <tr class="zebra-header">
    <td> <?php et( $type == 'month' ? 'month': 'day' ) ?> </td>
    <td> <?php et('before.deal') ?> </td>
    <td> <?php et('my.deal') ?> </td>
    <td> <?php et('revenue') ?> </td>
  </tr>
  <?php $i = 0; foreach($loop as $mnum):
    if($type == 'month')
      $total += max(0, $stats[$mnum]["prof"]);
    else
      $total += $stats[$mnum]["prof"];
  ?>
  <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
    <td> <?php echo $type == 'month' ? phive()->lcDate($mnum, '%b %g') : phive()->lcDate($year.'-'.$month.'-'.padMonth($mnum), '%b %d') ?> </td>
    <td> <?php efEuro($stats[$mnum]["before_deal"]) ?> </td>
    <td> <?php echo ($rate * 100) . '%'  ?> </td>
    <td> <?php efEuro($stats[$mnum]["prof"]) ?> </td>
  </tr>
  <?php $i++; endforeach; ?>
  <tr class="zebra-header">
    <td>  </td>
    <td> <?php efEuro( phive()->sum2d($stats, 'before_deal') ) ?> </td>
    <td>  </td>
    <td> <?php efEuro( max(0, $total) ) ?> </td>
  </tr>
</table>

<?php if($has_sub): ?>
  <br clear="all" />
  <h3><?php et('sub.casino.revenue.stats') ?></h3>
  <table class="zebra-tbl" style="width:<?php echo $width ?>px;">
    <tr class="zebra-header">
      <td> <?php et( $type == 'month' ? 'month': 'day' ) ?> </td>
      <td> <?php et('sub.net') ?> </td>
      <td> <?php et('my.deal') ?> </td>
      <td> <?php et('revenue') ?> </td>
    </tr>
  <?php $i = 0; foreach($loop as $mnum):
      if($type == 'month')
        $sub_total += max(0, $sub_stats[$mnum]["prof"]);
      else
        $sub_total += $sub_stats[$mnum]["prof"];
    ?>
    <tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
      <td> <?php echo $type == 'month' ? phive()->lcDate($mnum, '%b %g') : phive()->lcDate($year.'-'.$month.'-'.padMonth($mnum), '%b %d') ?> </td>
      <td> <?php efEuro($sub_stats[$mnum]["before_deal"]) ?> </td>
      <td> <?php echo ($sub_rate * 100) . '%'  ?> </td>
      <td> <?php efEuro($sub_stats[$mnum]["prof"]) ?> </td>
    </tr>
    <?php $i++; endforeach; ?>
    <tr class="zebra-header">
      <td>  </td>
      <td> <?php efEuro( phive()->sum2d($sub_stats, 'before_deal') ) ?> </td>
      <td>  </td>
      <td> <?php efEuro( max(0, $sub_total) ) ?> </td>
    </tr>
  </table>
<?php endif ?>
<?php }


