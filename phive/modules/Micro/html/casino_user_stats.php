<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

function printBetWinTable($arr, $wins = false){
  $total = 0;
  $map = array(2 => 'Normal', 4 => 'Jackpot', 7 => 'Refund');
  $games = phive("MicroGames")->allGamesSelect('ext_game_name', '', '1');
  phive('UserSearch')->showCsv($arr);  
  ?>

  <table class="stats_table" style="width: 900px;">
    <tr class="stats_header">
      <td>Type</td>
      <td>Date</td>
      <td>Amount (<?php ciso(true) ?>)</td>
      <td>Game</td>
      <td>Balance (<?php ciso(true) ?>)</td>
      <td>Bonus Bet</td>
      <td>ID</td>
      <td>Trans Id</td>
      <?php if($wins): ?>
        <td>Trans Type</td>
      <?php else: ?>
        <td>Cashb. (c)</td>
      <?php endif ?>
    </tr>
    <?php $i = 0; foreach($arr as $r):
      $total += $r['amount'];
      $type = $map[(int)$r['award_type']];
    ?>
      <tr <?php echo $type == 'Jackpot' ? 'style="background-color: red;" ' : "" ?> class="<?php echo ($i % 2 == 0 ? 'grey' : 'white') ?>_fill" >
        <td> <?php echo $r['tr_type'] ?> </td>
        <td> <?php echo $r['created_at'] ?> </td>
        <td> <?php echo $r['amount'] / 100 ?> </td>
        <td> <?php echo $games[$r['game_ref']] ?> </td>
        <td> <?php echo $r['balance'] / 100 ?> </td>
        <td> <?php echo $r['bonus_bet'] ?> </td>
        <td> <?php echo $r['mg_id'] ?> </td>
        <td> <?php echo $r['trans_id'] ?> </td>
        <?php if($wins): ?>
          <td><?php echo $type ?></td>
        <?php else: ?>
          <td><?php echo $r['loyalty'] ?></td>
        <?php endif ?>
      </tr>
    <?php $i++; endforeach ?>
    <tr class="stats_header">
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><?php echo $total / 100 ?></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
    </tr>
  </table>
<?php }

$start_date = empty($_REQUEST['start_date']) ? date('Y-m-01') : $_REQUEST['start_date'];
$end_date   = limitEdate($start_date, empty($_REQUEST['end_date']) ? date('Y-m-t') : $_REQUEST['end_date']);

if(!empty($_REQUEST['username']))
  $user = phive('UserHandler')->getUserByUsername($_REQUEST['username']);

if(!empty($_REQUEST['game_ref']))
  $extra = " AND game_ref = '{$_REQUEST['game_ref']}' ";

$suffix = empty($_REQUEST['battle']) ? '' : '_mp';

  //die;

if(!empty($user)){
  if (empty($_REQUEST['onlytransactions'])){
    $bets = phive('QuickFire')->getBetsOrWinsForUser('bets'.$suffix, $user->getId(), $start_date, $end_date, $_REQUEST['bonus_bet'], $extra);
    $wins = phive('QuickFire')->getBetsOrWinsForUser('wins'.$suffix, $user->getId(), $start_date, $end_date, $_REQUEST['bonus_bet'], $extra);
  }
  
  foreach($bets as &$b)
    $b['tr_type'] = 'bet';    
  foreach($wins as &$w)
    $w['tr_type'] = 'win';
  if(!empty($_REQUEST['chron_list']))
    $all = phive()->sort2d(array_merge($bets, $wins), 'created_at');     
  $other = phive('Cashier')->getUserTransactions($user, array(1, 2, 3, 4, 5, 7, 8 ,9, 12, 13, 14, 15), '', array($start_date, $end_date));
  $realCash = phive('Cashier')->getCashBalanceDate($end_date, $user);
  setCur($user);
}

?>
  <div style="margin-bottom: 100px;">
<table style="position: absolute; left: 0; background-color: white; width: 900px;">
  <tr>
    <td>
      <table class="stats_table">
        <tr>
          <td><h3>Real cash at the end date</h3></td>
        </tr>
        <tr class="stats_header">
          <td>Total Amount</td>
        </tr>
        <tr>
          <td><?php efEuro($realCash / 100) ?></td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <br>
      <br>
      <form action="" method="get">
        <table>
          <tr>
            <td>Start Date:</td>
            <td>
              <?php dbInput('start_date', $start_date) ?>
            </td>
          </tr>
          <tr>
            <td>End Date:</td>
            <td>
              <?php dbInput('end_date', $end_date) ?>
            </td>
          </tr>
          <tr>
            <td>Username:</td>
            <td>
              <?php dbInput('username', $_REQUEST['username']) ?>
            </td>
          </tr>
          <tr>
            <td>Bonus bet (1 to list only bonus transactions, 0 only non-bonus transactions, omit to list all):</td>
            <td>
              <?php dbInput('bonus_bet', $_REQUEST['bonus_bet']) ?>
            </td>
          </tr>
          <tr>
            <td>Game:</td>
            <td>
              <?php dbSelect('game_ref', phive('MicroGames')->allGamesSelect('ext_game_name', "active = 1", 1, array('game_name', 'device_type')), '', array('', 'Select Game')) ?>
            </td>
          </tr>
          <tr>
            <td>List Chronologically:</td>
            <td>
              <?php dbCheck('chron_list', $_REQUEST['chron_list']) ?>
            </td>
          </tr>
          <tr>
              <td>Battle:</td>
              <td>
                  <?php dbCheck('battle') ?>
              </td>
          </tr>
          <tr>
            <td>Show only transactions:</td>
            <td>
              <?php dbCheck('onlytransactions', $_REQUEST['onlytransactions']) ?>
          <tr>
            <td>&nbsp;</td>
            <td>
              <?php dbSubmit('Submit') ?>
              <?php phive("UserSearch")->csvBtn() ?>
            </td>
          </tr>
        </table>
      </form>
    </td>
  </tr>
  <tr>
    <?php if(!empty($all)): ?>
      <td>
        <h3>Game History</h3>
        <?php printBetWinTable($all, true) ?>
      </td>
    <?php else: ?>
      <?php if (!empty($bets) || !empty($wins)): ?>
      <td>
        <h3>Bets</h3>
        <?php printBetWinTable($bets) ?>
      </td>
      <td style="vertical-align: top;" >
        <h3>Wins</h3>
        <?php printBetWinTable($wins, true) ?>
      <?php endif ?>
    <?php endif ?>
        <tr><td>
    <h3>Other Transactions</h3>
    <?php printStatsTable(array('amount', 'description', 'timestamp', 'transactiontype'), $other, []) ?>
      </td>
  </tr>
</table>
  </div>
