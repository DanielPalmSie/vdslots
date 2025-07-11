<?php 
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function showBonusCode($bcode){
  if(empty($_REQUEST['bonus_code']))
    return true;
  if($_REQUEST['bonus_code'] == $bcode)
    return true;
  return false;
}

function showDepositor($p){
  if(empty($_REQUEST['depositors_filter']))
    return true;
  if($_REQUEST['depositors_filter'] == 'depositors' && $p['dep_num'] > 0)
    return true;
  if($_REQUEST['depositors_filter'] == 'non_depositors' && empty($p['dep_num']))
    return true;
  return false;
}

function bCodeStats($affe_id, $width = 850, $width2 = 940, $widths = array(500, 240, 200)){
  $aff 		= phive('Affiliater');
  $players 	= phive()->group2d( $aff->getBonusCodeDeposits($affe_id, $_REQUEST['sdate'], $_REQUEST['edate'], $_REQUEST['only_registered'] == 'on' ? 'reg' : ''), 'bonus_code', false );
?>
<form action="" method="get">
  <table class="simple_airy_table" style="width: <?php echo $width ?>px;">
    <tr>
      <td>
	<span class="small-bold"> <?php et('start.date') ?>:&nbsp; </span>
      </td>
      <td>
	<?php dbInput('sdate', empty($_REQUEST['sdate']) ? date('Y-m-01') : $_REQUEST['sdate'], 'text', 'narrow-input') ?>
      </td>
      <td>
	<span class="small-bold"> <?php et('show.depositors') ?>:&nbsp; </span>
      </td>
      <td>
	<input type="radio" name="depositors_filter" value="depositors" />
      </td>
      <td>
	<span class="small-bold"> <?php et('show.only.registered') ?>:&nbsp; </span>
      </td>
      <td>
	<input type="checkbox" name="only_registered" />
      </td>
      <td></td>
    </tr>
    <tr>
      <td>
	<span class="small-bold"> <?php et('end.date') ?>:&nbsp; </span>
      </td>
      <td>
	<?php dbInput('edate', empty($_REQUEST['edate']) ? date('Y-m-d') : $_REQUEST['edate'], 'text', 'narrow-input') ?>
      </td>
      <td>
	<span class="small-bold"> <?php et('show.non.depositors') ?>:&nbsp; </span>
      </td>
      <td>
	<input type="radio" name="depositors_filter" value="non_depositors" />
      </td>
      <td>
	<span class="small-bold"> &nbsp;<?php et('bonus.code') ?>:&nbsp; </span>
      </td>
      <td>
	<?php dbSelect('bonus_code', phive('Affiliater')->bonusCodesSelect($affe_id, 'bonus_code'), '', array( '', t('select') ), 'narrow-input') ?>
      </td>
      <td>
	<input type="submit" class="submit" name="submit-stats" value="<?php et('submit') ?>" />
      </td>
    </tr>
  </table>
</form>
<h3><?php echo t('bcode.stats.headline') ?></h3>
<?php foreach($players as $bcode => $sub): ?>
  <?php if(showBonusCode($bcode)):
  $total_reg_count = $aff->getBonusCodeRegisteredCount($bcode, '2011-01-01', date('Y-m-d'));
  $month_reg_count = $aff->getBonusCodeRegisteredCount($bcode, date('Y-m-01'), date('Y-m-d'));
  $dep_total = 0;
  ?>
    <h3><?php echo $bcode ?></h3>
    <strong> <?php echo t('registered.total').' '.$total_reg_count.', '.t('registered.this.month').' '.$month_reg_count ?> </strong>
    <br />
    <br />
    <table class="zebra-tbl" style="width:<?php echo $width2 ?>px;">
      <col width="<?php echo $widths[1] ?>"/>
      <col width="<?php echo $widths[2] ?>"/>
      <col width="<?php echo $widths[3] ?>"/>
      <tr class="zebra-header">
	<td> <?php et('username') ?> </td>
	<td> <?php et('registration.date') ?> </td>
	<td> <?php et('deposits') ?> </td>
      </tr>
      <?php $i = 0; foreach($sub as $p): ?>
      <?php if(showDepositor($p)): ?>
	<tr class="<?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
	  <td><?php echo $p['username'] ?></td>
	  <td> <?php echo phive()->lcDate($p['register_date'], '%x') ?> </td>
	  <td> <?php $dep_total += $p['dep_num']; echo $p['dep_num'] ?> </td>
	</tr>
	<?php $i++; endif; ?>
<?php endforeach; ?>
<tr class="zebra-header">
  <td>  </td>
  <td> </td>
  <td> <?php echo $dep_total ?> </td>
</tr>
    </table>
      <?php endif ?>
<?php endforeach?>

<?php }


