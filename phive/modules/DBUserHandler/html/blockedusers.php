<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$start_date = empty($_REQUEST['start_date']) ? date('Y-m-d') : $_REQUEST['start_date'];
$end_date = limitEdate($start_date, empty($_REQUEST['end_date']) ? date('Y-m-d', strtotime('tomorrow')) : $_REQUEST['end_date']);
if(!empty($_REQUEST['user_id']))
    $user_id = uid($_REQUEST['user_id']);
$blocks = phive('UserHandler')->getBlocks($start_date, $end_date, $user_id);

?>
<form action="" method="post" class="pad10">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <label for="start_date">Start Date:</label>
  <?php dbInput('start_date', $start_date)?><br />
  <label for="end_date">End Date:</label>
  <?php dbInput('end_date', $end_date)?><br />
  <label for="user_id">Player:</label>
  <?php dbInput('user_id', $user_id)?><br />
  <?php dbSubmit('Submit')?>
</form>

<table class="stats_table" width="99%">
  <tr class="stats_header">
    <td>User</td>
    <td width="50%">Reason</td>
    <td>Date</td>
    <td>IP</td>
  </tr>
  <?php
  $i = 0;
  foreach ($blocks as $b):
	   $block_country = ($b['reason'] != '2') ? '' : Phive('IpBlock')->getCountry($b['ip']) . " allowed: " . implode(", ", phive('DBUserHandler')->getAllowedCountries($b['username']));
  ?>
  <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white'?>_fill" >
    <td><a href="<?php echo getUserBoLink($b['user_id']) ?>" target="_blank" rel="noopener noreferrer"><?php echo $b['user_id'] ?></a><br/></td>
    <td><?php echo t("blocked.reason." . $b['reason'])?> <?php echo $block_country;?></td>
    <td><?php echo $b['date']?></td>
    <td><?php echo $b['ip']?></td>
  </tr>
  <?php $i++;endforeach;?>
</table>
