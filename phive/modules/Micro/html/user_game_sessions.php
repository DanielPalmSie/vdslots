<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

// TODO remove this, not used anymore

if(!empty($_REQUEST['submit']) || !empty($_REQUEST['as_csv'])){  
  $stime = $_REQUEST['sdate']." 00:00:00";
  $etime = $_REQUEST['edate']." 23:59:59";
}

$qf = phive('QuickFire');
$uh = phive('UserHandler');
$ud = cu($_REQUEST['username'])->data;

$res = $uh->getGameSessions($ud['id'], $stime, $etime);

$cres = array();
$keys = phM('keys', "gsess-current-{$ud['id']}*");
foreach($keys as $k){
  $tmp = array_map(function($el){ return json_decode($el, true); }, phM('lrange', $k, 0, -1));
  $tmp = array_reverse($tmp);
  $cres[] = phive('UserHandler')->makeGameSession($tmp);
}

$str = "SELECT DISTINCT io.*
        FROM users_sessions io, users u 
        WHERE io.created_at >= '$stime' AND io.created_at <= '$etime'
            AND io.user_id = u.id
            AND u.username = '{$_REQUEST['username']}'
            ORDER BY created_at ASC";
$inouts = phive('SQL')->loadArray($str);

function drawSessionTable($gres, $headline = 'Historical Game Sessions'){
?>
  <table>
    <tr>
      <td style="vertical-align: top">
        <?php if(!empty($_REQUEST['as_csv'])): ?>
          <br>
          <br>
          <?php phive('UserSearch')->handleCsv($gres) ?>
        <?php endif ?>
        <strong><?php echo $headline ?></strong>
        <table id="stats-table" class="stats_table">
          <thead>
            <tr class="stats_header">
              <th><?php echo 'Game' ?></th>
              <th><?php echo 'Wager Tot.' ?></th>
              <th><?php echo 'Win Tot.' ?></th>
              <th><?php echo 'Res. Tot.' ?></th>
              <th><?php echo 'Start Bal.' ?></th>
              <th><?php echo 'End Bal.' ?></th>
              <th><?php echo 'Start Time' ?></th>
              <th><?php echo 'End Time' ?></th>
            </tr>
          </thead>
          <?php foreach($gres as $r): ?>
            <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
              <td><?php echo $r['game_name'] ?></td>
              <td><?php nfCents($r['bet_amount']) ?></td>
              <td><?php nfCents($r['win_amount']) ?></td>
              <td><?php nfCents($r['result_amount']) ?></td>
              <td><?php nfCents($r['balance_start']) ?></td>
              <td><?php nfCents($r['balance_end']) ?></td>
              <td><?php echo $r['start_time'] ?></td>
              <td><?php echo $r['end_time'] ?></td>
            </tr>
          <?php endforeach ?>
        </table>
      </td>
    </tr>    
  </table>  
  <?php
}


?>
<div style="padding: 10px;">
  <strong><?php echo "From: $stime To: $etime".'. All numbers in '.$ud['currency'] ?>.</strong>
  <br>
  <br>
  <?php drawSessionTable($res) ?>
  <br>
  <?php drawSessionTable($cres, 'Game Sessions in Progress') ?>
  <br>  
  <?php if(!empty($_REQUEST['as_csv'])): ?>
    <br>
    <br>
    <?php phive('UserSearch')->handleCsv($inouts) ?>
  <?php endif ?>
  <strong>Logged sessions</strong>
  <br>
  <table id="stats-table" class="stats_table">
    <thead>
      <tr class="stats_header">
        <th><?php echo 'Start' ?></th>
        <th><?php echo 'End' ?></th>
      </tr>
    </thead>
    <?php foreach($inouts as $r): ?>
      <tr class="<?php echo $i % 2 == 0 ? 'fill-odd' : 'fill-even' ?>">
        <td><?php echo $r['created_at'] ?></td>
        <td><?php echo phive()->isEmpty($r['ended_at']) ? 'In Progress' : $r['ended_at'] ?></td>
      </tr>
    <?php endforeach ?>
  </table>
  <br>
  <?php drawStartEndJs() ?>
  <form action="" method="get">  
    <table>
      <?php drawStartEndHtml() ?>
      <tr>
        <td>Username:</td>
        <td>
          <?php dbInput('username', $_REQUEST['username']) ?>
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
