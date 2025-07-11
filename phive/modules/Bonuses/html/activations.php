<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$_REQUEST['edate'] = limitEdate($_REQUEST['sdate'], $_REQUEST['edate']);

if(!empty($_REQUEST['sdate'])){  
  $sstamp = "{$_REQUEST['sdate']} 00:00:00";
  $estamp = "{$_REQUEST['edate']} 23:59:59";
}else{
  $sstamp = date('Y-m-d 00:00:00');
  $estamp = phive()->hisNow();
}


if (!empty($_REQUEST['user_id']))
    $where_uid = " AND be.user_id = {$_REQUEST['user_id']} ";

$where_status = empty($_REQUEST['status']) ? '' : " AND be.status = '{$_REQUEST['status']}' ";

$str = "SELECT be.*, bt.bonus_name
        FROM bonus_entries be
        INNER JOIN bonus_types AS bt ON be.bonus_id = bt.id
        LEFT JOIN actions AS ac ON be.user_id = ac.target AND ac.tag = CONCAT('activated-', be.bonus_id) AND created_at <= '$sstamp' AND created_at >= '$estamp'   
        WHERE be.activated_time >= '$sstamp' AND be.activated_time <= '$estamp' $where_status $where_uid
        ORDER BY be.activated_time DESC";

$rows = phive('SQL')->shs('merge', 'activated_time', 'desc', 'bonus_entries')->loadArray($str);



?>
<div style="padding: 10px;">
  <?php if(!empty($_REQUEST['as_csv'])): ?>
    <br>
    <br>
    <?php phive('UserSearch')->handleCsv($gres) ?>
  <?php endif ?>
  <strong>Activated bonuses between <?php echo $sstamp ?> and <?php echo $estamp ?></strong>
  <br>
  <br>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
        <th><?php echo 'Activation Time' ?></th>
        <th><?php echo 'Player' ?></th>
        <th><?php echo 'Bonus' ?></th>
        <th><?php echo 'Bonus Status' ?></th>
        <th><?php echo 'Bonus Balance' ?></th>
        <th><?php echo 'Bonus Type' ?></th>
        <th><?php echo 'Activator' ?></th>
      </tr>
    </thead>
    <?php foreach($rows as $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
        <td><?php echo $r['activated_time'] ?></td>
        <td><a href="<?php echo getUserBoLink($r['user_id'])?>bonuses" target="_blank" rel="noopener noreferrer"><?php echo $r['user_id'] ?></a><br/></td>
        <td><?php echo $r['bonus_name'] ?></td>
        <td><?php echo $r['status'] ?></td>
        <td><?php echo $r['balance'] ?></td>
        <td><?php echo $r['bonus_type'] ?></td>
        <td><?php echo empty($r['actor']) ? 'player' : $r['actor'] ?></td>
      </tr>
    <?php endforeach ?>
  </table>
  <br>
  <?php drawStartEndJs() ?>
  <form action="" method="get">  
    <table>
      <?php drawStartEndHtml() ?>
      <tr>
        <td>
          <label>User ID:</label>
        </td>
        <td>
          <?php dbInput('user_id', $_REQUEST['user_id']) ?><br />
        </td>
      </tr>
      <tr>
        <td>
          <label>Status default displays all (active, pending or failed):</label>
        </td>
        <td>
          <?php dbInput('status', $_REQUEST['status']) ?><br />
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
  </form>
</div>
