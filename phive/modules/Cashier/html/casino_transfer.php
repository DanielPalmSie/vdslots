<?php
require_once __DIR__ . '/../../../admin.php';
if(isset($_POST['transfer_money'])){
  if(is_numeric($_POST['amount'])){

    $uh = phive('UserHandler');
    
    $to = cu($_POST['user_id']);
    
    $tr_id = phive('Cashier')->transactUser(
      $to,
      $_POST['amount'],
      $_POST['description'],
      null,
      null,
      $_POST['transactiontype'],
      false
    );

    $descr = cu()->getUsername()." transferred {$_POST['amount']} to ".$to->getUsername();
    
    $uh->logIp(cu()->getId(), $to->getId(), 'cash_transactions', $descr, $tr_id);
    $uh->logAction($to, $descr, 'money_transfer');
    
    echo "<h2>Transfer successful</h2>";
  }
  else{
    echo "<h2>Transfer failed</h2>";
  }
}else if(isset($_GET['user_id'])){
  $user = cu($_GET['user_id']);
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
<p>Transfer cash to <?php echo $user->getUsername(); ?></p>
<form method="post" accept-charset="utf-8">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>"/>
  <table>
    <tr>
      <td>Amount to transfer <strong>in cents:</strong></td>
      <td> <input type="text" name="amount" value=""/></td>
    </tr>
    <tr>
      <td>Description: </td><td><input type="text" name="description" value="Admin transferred money"/></td>
    </tr>
    <tr>
      <td>Transaction Type:
	<br>* 1.) Bet
	<br>* 2.) Win
	<br>* 3.) Deposit (warning, you probably wan't to use Add Deposit instead!!)
	<br>* 4.) Bonus reward
	<br>* 5.) Affiliate payout
	<br>* 6.) Voucher payout
	<br>* 7.) Bet Refund
	<br>* 8.) Withdrawal
	<br>* 9.) Chargeback (make sure the amount is negative, ex: -1000)
	<br>* 10.) Bonus Bet
	<br>* 11.) Bonus Win
	<br>* 12.) Jackpot Win
	<br>* 13.) Other Refund
	<br>* 14.) Activated bonus
	<br>* 15.) Failed bonus
	<br>* 20.) Sub aff payout
	<br>* 29.) Buddy transfer.
	<br>* 31.) Casino Weekend Booster / loyalty.
	<br>* 32.) Casino Race.
	<br>* 33.) SMS Fee.
        <br>* 34.) Casino MP buy in
        <br>* 35.) Casino MP pot cost
        <br>* 36.) Casino MP skill point award
        <br>* 37.) Casino MP buy in with skill points
        <br>* 38.) MP cash win
        <br>* 39.) MP skill points win
        <br>* 40.) MP skill points top 3 bonus win
        <br>* 41.) Guranteed prize diff
	<br>* 42.) Test cash for test account.
	<br>* 43.) Inactivity fee.
        <br>* 44.) MG tournament registration fee.
        <br>* 45.) MG tournament rebuy/addon.
        <br>* 46.) MG tournament payout.
        <br>* 47.) MG tournament cancellation.
        <br>* 48.) Casino MP fixed cash balance pay back.
        <br>* 49.) Casino MP pot cost with skill points.
      </td>
      <td><input type="text" name="transactiontype" value="9"/></td>
    </tr>
  </table>

  <p><input type="submit" name="transfer_money" value="Transfer"></p>
</form>
<br/>
<?php
}
