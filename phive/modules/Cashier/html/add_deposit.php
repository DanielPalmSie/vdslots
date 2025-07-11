<?php
require_once __DIR__ . '/../../../admin.php';
if(isset($_POST['transfer_money'])){
  if(is_numeric($_POST['amount'])){
    $uh     = phive('UserHandler');
    $user   = cu($_REQUEST['user_id']);
    $qf = phive('QuickFire');
    $cu     = cu();
    $result = $qf->depositCash($user, $_POST['amount'], $_POST['dep_type'], $_POST['ext_id'], $_POST['scheme'], $_POST['card_hash'], '', false, 'approved', null);
    if($result){
      $uh->logIp($cu->getId(), $user->getId(), 'cash_transactions', "{$cu->getUsername()} deposited {$_POST['amount']} to {$user->getUsername()}", $qf->cur_tr_id);
        $uh->logIp($cu->getId(), $user->getId(), 'deposits', "{$cu->getUsername()} deposited {$_POST['amount']} to {$user->getUsername()}", $qf->did);
      echo "<h2>Deposit successful</h2>";
    }
  }else
    echo "<h2>Deposit failed</h2>";
}else if(isset($_GET['user_id']) || isset($_GET['username'])){
  if (isset($_GET['user_id']))
    $user = cu($_GET['user_id']);
  else if (isset($_GET['username'])) {
    $user = phive('UserHandler')->getUserByUsername($_GET['username']);
  }
  if($user){
    printForm($user);
  }
}

function printForm($user){
?>
<style type="text/css" media="screen">
 input[type=text]{
   width: 200px;
 }
</style>
<p>Generate deposit for: <?php echo $user->getUsername(); ?></p>
<form method="post" accept-charset="utf-8">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>"/>
  <input type="hidden" name="action" value="<?php echo $_GET['action']; ?>"/>
  <input type="hidden" name="username" value="<?php echo $_GET['username']; ?>"/>
  <table>
    <tr>
      <td>
        <table>
          <tr>
            <td>Amount <strong>in cents:</strong></td>
            <td> <input type="text" name="amount" value=""/></td>
          </tr>
          <tr>
            <td>Main Type (see right): </td>
            <td><input type="text" name="dep_type" value=""/></td>
          </tr>
          <tr>
              <td>Sub Type: </td>
              <td><input type="text" name="scheme" value=""/></td>
          </tr>
          <tr>
              <td>Card Hash / Bank account number: </td>
              <td><input type="text" name="card_hash" value=""/></td>
          </tr>
          <tr>
            <td>Ext ID: </td>
            <td><input type="text" name="ext_id" value=""/></td>
          </tr>
        </table>
        <p><input type="submit" name="transfer_money" value="Deposit"></p>
      </td>
      <td>
        Type can be one of either:
        <br/>
        <?php foreach(phive('Cashier')->getPaymentMapping() as $method): ?>
          <?php echo $method ?>
          <br/>
        <?php endforeach ?>
      </td>
    </tr>
  </table>
</form>
<?php
}
?>
