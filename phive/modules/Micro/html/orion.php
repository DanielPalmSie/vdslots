<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
require_once __DIR__ . '/../Orion.php';

$or = new Orion();

function printQtable($or, $method){ ?>

  <form method="post" action="">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <table>
      <tr>
	<td>Username</td>
	<td>MG User ID</td>
	<td>Trans. No</td>
	<td>Game</td>
	<td>Amount</td>
	<td>MG Ref.</td>
	<td>RowId</td>
      </tr>
      <?php $i = 0; foreach($or->getQ($method) as $r): ?>
	<tr>
	  <td><?php dbInput("row[$i][username]", $r['LoginName']) ?></td>
	  <td><?php dbInput("row[$i][mg_userid]", $r['UserId']) ?></td>
	  <td><?php dbInput("row[$i][trans_id]", $r['TransactionNumber']) ?></td>
	  <td>
	    MG Game Name: <?php echo $r['GameName'] ?>
	    <br/>
	    <?php dbSelect("row[$i][game_ref]", phive('MicroGames')->allGamesSelect('ext_game_name'), $r['GameRef'], array('', 'Choose Game')) ?>
	  </td>
	  <td><?php dbInput("row[$i][amount]", $r['ChangeAmount']) ?></td>
	  <td><?php dbInput("row[$i][mg_id]", $r['MgsReferenceNumber']) ?></td>
	  <td><?php dbInput("row[$i][row_id]", $r['RowId']) ?></td>
	</tr>
      <?php $i++; endforeach; ?>	
      <tr>
	<td> <input type="submit" name="submit" value="Submit" /> </td>
      </tr>
    </table>
    <input type="hidden" name="qtype" value="<?php echo $method ?>" />
  </form>
<?php }

if(empty($_POST['submit'])){
  printQtable($or, 'GetCommitQueueData');
  printQtable($or, 'GetRollbackQueueData');
}else{
  $to_mg = array();
  foreach($_POST['row'] as $r){
    $method = $_POST['qtype'] == 'GetCommitQueueData' ? 'CommitQueue' : 'RollbackQueue';
    $r['id'] = $id = $or->commitOrRollback($r, $method);
    if(!is_numeric($id))
      echo $id. " for operation with MG id/ref: {$r['mg_id']} ";
    else
      $to_mg[] = $r;
  }
  if($or->validate($method, $to_mg)){
    echo "MG returned error copy paste the below and email to the developer(s): <br/>" . var_export($to_mg, true);
  }else
  echo "The queue was successfully posted.";
}




