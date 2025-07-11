<?php
//TODO deprecated because of Orion
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
require_once __DIR__ . '/../Orion.php';

$req_fields = array();

foreach(array('username', 'trans_id', 'game_ref', 'amount', 'mg_id') as $field)
  $req_fields[$field] = array('text', 'nullStr');

$err = phive('QuickFire')->validate($req_fields);

$qf = phive('QuickFire');

if(!empty($err)){
  echo "No field can be empty. </br>";
}else{
  $user = phive('UserHandler')->getUserByUsername($_POST['username']);
  
  if(!empty($user) && !is_float($_POST['amount'] + 0)){
    $_POST['user_id'] = $user->getId();
    
    $cur_game 		= phive('MicroGames')->getByGameRef($_POST['game_ref']);
    
    if($_POST['action'] == 'Rollback'){
      $bet = $qf->getBetByMgId($_POST['mg_id']);
      if(empty($bet)){
	$bet_amount 		= floor($_POST['amount'] * (1 - $cur_game['jackpot_contrib']));
	$jp_contrib 		= $_POST['amount'] - $bet_amount;
	$_POST['balance'] 	= $qf->changeBalance($user, "-{$_POST['amount']}", $_POST['trans_id'], 1);
	phive('SQL')->insertArray('bets', array(
	  'trans_id' 	=> $_POST['trans_id'],
	  'amount'	=> $bet_amount,
	  'game_ref'	=> $_POST['game_ref'],
	  'user_id'	=> $_POST['user_id'],
	  'mg_id'	=> $_POST['mg_id'],
	  'balance'	=> $_POST['balance'],
	  'currency'	=> $_POST['currency'],
	  'op_fee'	=> $bet_amount * $cur_game['op_fee'],
	  'jp_contrib'=> $jp_contrib
	));
      }
    }
    
    $ext_id = $qf->getBetByMgId($_POST['mg_id'], 'wins');
    
    if(empty($ext_id)){
      $_POST['op_fee'] 		= $_POST['amount'] * $cur_game['op_fee'];
      $_POST['award_type'] 	= 7;
      $_POST['balance'] 		= $qf->changeBalance($user, "{$_POST['amount']}", $_POST['trans_id'], 7);
      phive('SQL')->insertPost('wins');
      $ext_id = phive('SQL')->insertBigId();
    }else
    $ext_id = $ext_id['id'];
    
    echo "Rollback / Commit executed successfully, External Reference: <strong>$ext_id</strong> </br>";
  }else
  echo "No user with that username / Login Name or did you write a euro number, eg 1.56? </br>";	
}
?>

<form method="post" action="">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr>
      <td>Login Name of player:</td>
      <td><?php dbInput('username', $_POST['username']) ?></td>
    </tr>
    <tr>
      <td>Transaction No:</td>
      <td><?php dbInput('trans_id', $_POST['trans_id']) ?></td>
    </tr>
    <tr>
      <td>Game:</td>
      <td><?php dbSelect("game_ref", phive('MicroGames')->allGamesSelect('ext_game_name'), $_POST['game'], array('', 'Choose Game')) ?></td>
    </tr>
    <tr>
      <td>Change Amount (in cents):</td>
      <td><?php dbInput('amount', $_POST['amount']) ?></td>
    </tr>
    <tr>
      <td>MGS Bet/Win Reference:</td>
      <td><?php dbInput('mg_id', $_POST['mg_id']) ?></td>
    </tr>
    <tr>
      <td>Currency</td>
      <td><?php dbInput('currency', $_POST['currency']) ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><input type="submit" name="action" value="Commit" /></td>
      <td><input type="submit" name="action" value="Rollback" /></td>
    </tr>
  </table>
</form>


