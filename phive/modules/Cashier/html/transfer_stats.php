<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function depStatusInfo($d){
  if(empty($d['fail_info']))
    return $d['status'];
  else{
    $info = json_decode($d['fail_info'], true);
    return $info['reason'];
  }
}

setCur($_REQUEST['currency']);

$start_date 	= empty($_REQUEST['start_date']) ? date('Y-m-01') : $_REQUEST['start_date'];
$end_date 	= limitEdate($start_date, empty($_REQUEST['end_date']) ? date('Y-m-t') : $_REQUEST['end_date']);
$pop_end 	= $end_date;
$end_date 	.= ' 23:59:59';
$limit 		= empty($_REQUEST['limit']) ? 60 : $_REQUEST['limit'];

$where_extra = empty($_REQUEST['ext_id']) ? '' : " AND (ext_id LIKE '%{$_REQUEST['ext_id']}%' OR loc_id LIKE '%{$_REQUEST['ext_id']}%') ";

if(!empty($_REQUEST['min_amount']))
  $where_extra .= " AND amount >= {$_REQUEST['min_amount']} ";

if(!empty($_REQUEST['card_hash']))
  $where_extra .= " AND card_hash LIKE '%{$_REQUEST['card_hash']}%' ";

if(!empty($_REQUEST['max_amount']))
  $where_extra .= " AND amount <= {$_REQUEST['max_amount']} ";

if(!empty($_REQUEST['username'])){
  $user 		= phive('UserHandler')->getUserByUsername($_REQUEST['username']);
  $user_id 	= $user->getId();
  setCur($user);
}

$c = phive('Cashier');

if(!empty($_REQUEST['submit']))
  $_GET['page'] = '';

if(empty($_GET['page']) || !empty($_REQUEST['submit'])){
  $method			= $_REQUEST['method'];
  $status 		= empty($_REQUEST['status']) ? 'approved' : $_REQUEST['status'];
  $sql_status		= empty($status) ? '' : " = '$status' ";

  if(empty($_REQUEST['in_out']) || $_REQUEST['in_out'] == 'w'){
    //$sdate, $edate, $status = '', $method = '', $user_id = '', $group_by = false, $where_extra = '', $date_col = 'timestamp', $cur = ''
    $pending_date_col = $status == 'pending' ? 'timestamp' : 'approved_at';
    $outs = $c->getPendingGroup($start_date, $end_date, $sql_status, $method, $user_id, false, $where_extra, $pending_date_col, ciso(), $limit, 'DESC');
  }else
    $outs = array();

  if(empty($_REQUEST['in_out']) || $_REQUEST['in_out'] == 'd'){
    $ins = $status != 'disapproved' ?
      $c->getDeposits($start_date, $end_date, $user_id, $method, false, '', $where_extra, ciso(), $limit) :
      $c->getFailedDeposits($start_date, $end_date, $user_id, $method, false, '', $where_extra, ciso(), $limit);
  }else
    $ins = array();

  $all			= phive()->sort2d(array_merge($ins, $outs), 'exec_stamp', 'desc');

  $_SESSION['all_transactions'.$user_id] = $all;
}else
  $all = $_SESSION['all_transactions'.$user_id];
$per_page 				= empty($_REQUEST['per_page']) ? (empty($_SESSION['per_page']) ? 30 : $_SESSION['per_page']) : $_REQUEST['per_page'];
$_SESSION['per_page'] 	= $per_page;
$p 						= phive('Paginator');
$p->setPages(count($all), '', $per_page);
$or_all 				= $all;
$all 					= array_slice($all, $p->getOffset($per_page), $per_page);


$tots = array();

$map = $c->getPaymentMapping();

foreach($map as $short => $long){
  $tots[$short]['period'] 	= $c->getStatsForPaymentType($short, $start_date, $end_date, $user_id, ciso());
  $tots[$short]['all_time'] 	= $c->getStatsForPaymentType($short, '2011-01-01', $end_date, $user_id, ciso());
}


$tot_tots = array();

if($_GET['action'] == 'cancel-dc-deposit'){
    $d 		= phive('Cashier')->getDeposit($_GET['id']);
    $res 	= phive('WireCard')->cancel($d['ext_id']);
    if($res['status'] == 1){
        $u 	= cu($d['user_id']);
        phive('Cashier')->chargeback($u, $d['amount'], "Deposit fraud cancellation.");
        //phive('Cashier')->transactUser($u, -$d['amount'], "Deposit fraud cancellation.", null, null, 9, false);
        phive('Cashier')->cancelDeposit($d);
        $res['status'] = 'success';
    }else
    $res['status'] = 'failure';
}

$int_keys = array('# Deposits', '# Unique Deposits', '# Withdrawals', '# Unique Withdrawals');

?>
<?php if(!empty($res)): ?>
<br>
<br>
Wirecard deposit cancellation result: <strong><?php echo $res['status'] ?></strong>. Message from Wirecard: <strong><?php echo $res['reason'] ?></strong>.
<br>
<br>
<?php endif ?>
<div style="padding: 10px;">
<form action="?action=&id=" method="get">
<table style="width: 1100px;">
  <tr>
    <td>Start Date:</td>
    <td>
      <?php dbInput('start_date', $start_date) ?>
    </td>
    <td>End Date:</td>
    <td>
      <?php dbInput('end_date', $pop_end) ?>
    </td>
  </tr>
  <tr>
    <td>Username:</td>
    <td>
      <?php dbInput('username', $_REQUEST['username']) ?>
    </td>
    <td>Per page:</td>
    <td>
      <?php dbInput('per_page', $per_page) ?>
    </td>
  </tr>
  <tr>
    <td>Status (approved, disapproved, pending):</td>
    <td>
      <?php dbSelect('status', array('approved' => 'approved', 'disapproved' => 'disapproved', 'pending' => 'pending'), $status) ?>
    </td>
    <td>Method:</td>
    <td>
      <?php dbSelect('method', phive('Cashier')->getPaymentMapping(), '', ['', 'Select']) ?>
    </td>
  </tr>
  <tr>
    <td>Show only (d/w):</td>
    <td><?php dbInput('in_out', $in_out) ?></td>
    <td>Part of Ext/Loc ID:</td>
    <td>
      <?php dbInput('ext_id') ?>
    </td>
  </tr>
  <tr>
    <td>Min amount (c):</td>
    <td><?php dbInput('min_amount', $_REQUEST['min_amount']) ?></td>
    <td>Max amount (c):</td>
    <td><?php dbInput('max_amount', $_REQUEST['max_amount']) ?></td>
  </tr>
  <tr>
    <td>Currency:</td>
    <td>
      <?php cisosSelect(true) ?>
    </td>
    <td>Limit:</td>
    <td>
      <?php dbInput('limit', $limit) ?>
    </td>
  </tr>
  <tr>
    <td>Part of Card #</td>
    <td>
      <?php dbInput('card_hash', $_REQUEST['card_hash']) ?>
    </td>
    <td>&nbsp;</td>
    <td>
      <?php dbSubmit('Submit') ?>
      <?php phive("UserSearch")->csvBtn() ?>
    </td>
  </tr>
</table>
</form>
<br>
<?php if(!empty($_REQUEST['as_csv'])): ?>
Withdrawals:
<br>
<?php phive('UserSearch')->handleCsv($outs) ?>
<br>
Deposits:
<br>
<?php phive('UserSearch')->handleCsv($ins) ?>
<br>
<?php endif ?>
<br>
<?php phive('UserSearch')->prSendToBrowseUsersForm($or_all) ?>
<br>
<strong>All amounts in <?php ciso(true) ?>.</strong>
<br>
<br>
<table class="stats_table">
  <tr class="stats_header">
    <td>Date</td>
    <td>Exec. Date</td>
    <td>User</td>
    <td>Method</td>
    <td>Type</td>
    <td>Amount </td>
    <td>Fees </td>
    <td>Deducted </td>
    <td>Status</td>
    <td>Ext ID</td>
    <td>Our Ref</td>
    <td>Card #</td>
    <td>Action</td>
    <td>Appr. By</td>
  </tr>
  <?php $i = 0; foreach($all as $t):
    $uname = ud($t['user_id'])['username'];
  ?>
  <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
    <td><?php echo $t['timestamp'] ?></td>
    <td><?php echo $t['exec_stamp'] ?></td>
    <td>
      <a href="/account/<?php echo $uname ?>/">
        <?php echo $uname ?>
      </a>
    </td>
    <td><?php echo ucfirst($c->isDeposit($t) ? $t['dep_type']." ".$t['scheme'] : $t['payment_method']." ".$t['scheme']) ?></td>
    <td><?php echo $c->isDeposit($t) ? 'Deposit' : 'Withdrawal' ?></td>
    <td><?php nf2($t['amount'], false, 100) ?></td>
    <td><?php nf2($t['real_cost'], false, 100) ?></td>
    <td><?php nf2($t['deducted_amount'], false, 100) ?></td>
    <td><?php echo $c->isDeposit($t) ? depStatusInfo($t) : $t['status'] ?></td>
    <td><?php echo $t['ext_id'] ?></td>
    <td><?php echo $t['loc_id'] ?></td>
    <td><?php echo $c->isDeposit($t) ? $t['card_hash'] : $t['scheme'] ?></td>
    <td>
        <?php if($c->isDeposit($t) && $t['dep_type'] == 'wirecard' && $t['status'] != 'disapproved' && p('cancel.ccard')): ?>
        <button onclick="confirmJump('/admin/transfer-stats/?action=cancel-dc-deposit&id=<?php echo $t['id'] ?>');">Cancel</button>
      <?php endif ?>
    </td>
    <td><?php echo $c->isDeposit($t) ? '' : ud($t['approved_by'])['username'] ?></td>
  </tr>
  <?php $i++; endforeach; ?>
</table>
<br>
<?php if(empty($_REQUEST['username'])): ?>
  <strong>Current site cash: <?php efEuro( $c->getTotalCash(ciso()) ) ?></strong><br>
  <strong>Current site bonus balances, all currencies: <?php nfCents( phive('Bonuses')->getTotalBalances() ) ?></strong>
<?php elseif(!empty($user_id)): ?>
  <strong>Current user cash: <?php efEuro( $user->getAttribute('cash_balance') ) ?></strong><br>
  <strong>Current user bonus balances: <?php efEuro( phive('Bonuses')->getBalanceByUser($user_id) ) ?></strong>
<?php else: ?>
  <strong>User could not be found.</strong>
<?php endif ?>
<br>
<?php $p->render() ?>
<br clear="all">

<?php if(p('view.transfer.totals')): ?>
<table class="stats_table">
  <?php foreach($c->getPaymentMapping() as $ptype): ?>
  <tr class="stats_header">
    <td colspan="3">
      <h3><?php echo ucfirst($ptype) ?></h3>
    </td>
  </tr>
  <tr>
    <td>
      <strong>During Period</strong>
      <table>
        <?php $i = 0; foreach($tots[$ptype]['period'] as $label => $value):
          $tot_tots['period'][$label] += $value;
        ?>
          <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
            <td>
              <strong><?php echo $label ?>:</strong>
            </td>
            <td>
              <strong>
                <?php
                  if(!in_array($label, $int_keys))
                    $label == 'Effective %' ? nf2($value) : efEuro( $value );
                  else
                    echo $value;
                ?>
              </strong>
            </td>
          </tr>
        <?php $i++; endforeach; ?>
      </table>
    </td>
    <td>
      &nbsp;&nbsp;&nbsp;
    </td>
    <td>
      <strong>All Time</strong>
      <table>
        <?php $i = 0; foreach($tots[$ptype]['all_time'] as $label => $value):
          $tot_tots['all_time'][$label] += $value;
        ?>
          <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
            <td>
              <strong><?php echo $label ?>:</strong>
            </td>
            <td>
              <strong>
              <?php
                if(!in_array($label, $int_keys))
                  $label == 'Effective %' ? nf2($value) : efEuro( $value );
                else
                  echo $value;
              ?>
              </strong>
            </td>
          </tr>
        <?php $i++; endforeach; ?>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="3"> &nbsp; </td>
  </tr>
  <?php endforeach?>
  <tr class="stats_header">
    <td colspace="3">
      <h3>Total all methods</h3>
    </td>
  </tr>
  <tr>
    <td>
      <strong>During Period</strong>
      <table>
        <?php $i = 0; foreach($tot_tots['period'] as $label => $value): ?>
          <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
            <td>
              <strong><?php echo $label ?>:</strong>
            </td>
            <td>
              <strong>
                <?php
                  if(!in_array($label, $int_keys))
                    $label == 'Effective %' ? nf2(($tot_tots['period']['Transfer Fees'] / $tot_tots['period']['Total transactions']) * 100) : efEuro( $value );
                  else
                    echo $value;
                ?>
              </strong>
            </td>
          </tr>
        <?php $i++; endforeach; ?>
      </table>
    </td>
    <td>
      &nbsp;&nbsp;&nbsp;
    </td>
    <td>
      <strong>All Time</strong>
      <table>
        <?php $i = 0; foreach($tot_tots['all_time'] as $label => $value): ?>
          <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
            <td>
              <strong><?php echo $label ?>:</strong>
            </td>
            <td>
              <strong>
                <?php
                  if(!in_array($label, $int_keys))
                    $label == 'Effective %' ? nf2(($tot_tots['all_time']['Transfer Fees'] / $tot_tots['all_time']['Total transactions']) * 100) : efEuro( $value );
                  else
                    echo $value;
                ?>
                <?php  ?>
              </strong>
            </td>
          </tr>
        <?php $i++; endforeach; ?>
      </table>
    </td>
  </tr>
</table>
<?php endif ?>

</div>
