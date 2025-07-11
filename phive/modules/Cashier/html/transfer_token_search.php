<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$sdate = empty($_REQUEST['sdate']) ? date('Y-m-d 00:00:00') : $_REQUEST['sdate'];
$edate = empty($_REQUEST['edate']) ? date('Y-m-d 23:59:59') : $_REQUEST['edate'];

if(!empty($_REQUEST['token']))          
  $where_token = "AND security LIKE '%{$_REQUEST['token']}%'";

if(!empty($_REQUEST['user_id'])){
  $where_uid = "AND user_id = '{$_REQUEST['user_id']}'";
}

$sql = "SELECT * FROM transfer_tokens WHERE created_at >= '$sdate' AND created_at <= '$edate' $where_uid $where_token";

$rows = phive('SQL')->loadArray($sql);

?>
<div class="pad10">
  <?php drawStartEndJs() ?>
  <form action="" method="get">
    <table border="0" cellspacing="5" cellpadding="5">
      <?php drawStartEndHtml() ?>
      <tr>
        <td>Part of token:</td>
        <td>
          <?php dbInput('token', $_REQUEST['token']) ?>
        </td>
      </tr>
      <tr>
        <td>And / Or user id:</td>
        <td>
          <?php dbInput('user_id', $_REQUEST['user_id']) ?>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          <?php dbSubmit('Submit') ?>
        </td>
      </tr>
    </table>
  </form>
<br>
<table id="stats-table" class="stats_table">
  <thead>
    <tr class="stats_header">
        <th>Created At</th>
        <th>Token</th>
        <th>User id</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 0; foreach($rows as $r): ?>
    <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
      <td><?php echo $r['created_at']  ?></td>
      <td><?php echo $r['security']  ?></td>
      <td><?php echo $r['user_id']  ?></td>
    </tr>
    <?php $i++; endforeach ?>
  </tbody>
</table>
<br>
</div>
