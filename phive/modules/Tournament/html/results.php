<?php
require_once __DIR__ . '/../../../admin.php';
$sql                 = phive('SQL');
$th                  = phive('Tournament'); 
list($sdate, $edate) = phive()->timeSpan('-1 week');
$sdate               = phive()->emptyDef($_REQUEST['sdate'], $sdate);
$edate               = phive()->emptyDef($_REQUEST['edate'], $edate);
$res                 = $th->getAllWhere($sql->tRng($sdate, $edate, 'end_time', ''));
drawStartEndJs();
?>
<div class="pad10">
  <p>
    <strong>Showing dates: <?php echo $sdate.' - '.$edate ?></strong>
  </p>
  <form action="" method="get">
    <?php drawStartEndHtml() ?>
    <?php dbSubmit('Submit') ?>
  </form>
  <table class="v-align-top">
    <tr>
      <td>
        <?php drawTable($res, array('id', 'tournament_name', 'category', 'status', 'registered_players', 'start_time', 'end_time', 'start_format', 'play_format', 'win_format'), array('tid' => array('id', 'Results'))); ?>
      </td>
      <td>
        <?php
        if(!empty($_REQUEST['tid'])){
          //$entries = $th->getEntriesWhere(array('t_id' => $_REQUEST['tid']));
          $str = "SELECT te.*, u.id as user_id, u.firstname, u.lastname FROM tournament_entries te, users u WHERE te.t_id = {$_REQUEST['tid']} AND te.user_id = u.id";
          $res = $sql->shs('merge', '',null,'tournament_entries')->loadArray($str);
          $res = phive()->sort2d($res, 'result_place');
          drawTable($res, array('t_id', 'user_id', 'firstname', 'lastname', 'won_amount', 'result_place', 'win_amount', 'status', 'biggest_win', 'rebuy_times', 'rebuy_cost', 'turnover', 'updated_at', 'highest_score_at'));
        }        
        ?>
      </td>
    </tr>
  </table>  
</div>
