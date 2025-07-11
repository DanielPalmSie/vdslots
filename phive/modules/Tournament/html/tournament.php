<?php
require_once __DIR__ . '/../../../admin.php';

$th = phive('Tournament');
if(!empty($_REQUEST['cancel']))
  $th->cancel($_REQUEST['cancel']);
if(!empty($_REQUEST['pause']))
  $th->pause($_REQUEST['pause']);
if(!empty($_REQUEST['resume']))
  $th->resume($_REQUEST['resume']);
if(!empty($_REQUEST['calc']))
  $th->endTournament($_REQUEST['calc']);
$ts = $th->getAllWhere("prizes_calculated = 0 AND status NOT IN('cancelled')");
?>
<div class="pad10">
  <p>
    Note that cancelling finished tournaments is <strong>NOT</strong> supported.<br>
    <br>
    tht + cash-balance &#x2192; balance + house fee + pot cost<br>
    tht + cash-fixed &#x2192; balance + house fee + pot cost<br>
    tht + win-prog &#x2192; balance + win amount + pot cost + house fee<br>
    tht + win-fixed &#x2192; cost + house fee<br>
    <br>
    thw + cash-balance &#x2192; balance + house fee + pot cost<br>
    thw + cash-fixed &#x2192; balance + house fee + pot cost<br>
    thw + win-prog &#x2192; <strong>this combo should not exist in the system for real money tournaments</strong><br>
    thw + win-fixed &#x2192; cost + house fee<br>
  </p>
  <table>
    <tr>
      <td>Name</td>
      <td># Regs.</td>
      <td>Start Format</td>
      <td>Category</td>
      <td>Created At</td>
      <td>Started At</td>
      <td>MTT Start At</td>
      <td>Status</td>
      <td>Paused</td>
      <td>Calculated</td>
      <td>Tpl ID</td>
    </tr>
    <?php drawTblBody(
      $ts,
      array('tournament_name', 'registered_players', 'start_format', 'category', 'created_at', 'start_time', 'mtt_start', 'status', 'pause_calc', 'prizes_calculated', 'tpl_id'),
      array(
        'cancel' => array('id', 'Cancel'),
        'pause' => array('id', 'Pause'),
        'resume' => array('id', 'Resume'),
        'calc' => array('id', 'Calculate Prizes')))
    ?>
  </table>
</div>
