<?php
ini_set("memory_limit", "250M");
ini_set("max_execution_time", "30000");

echo '<a href="/admin">Back</a><br><br>';

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../rbl/html/display.php';

$sql 	= phive('SQL');

if(!empty($_POST['sdate'])){
    $where 	= $_POST['completely'] == 'true' ? 'cash_balance != 0' : "cash_balance <= 11 AND cash_balance > 0";
    $str	= "SELECT * FROM users WHERE '{$_POST['sdate']} 00:00:00' > last_login AND $where";
    $eus 	= $sql->shs('merge', '', null, 'users')->loadArray($str);
    
    foreach($eus as $eu){
	$cash = $eu['cash_balance'];
	$user = cu($eu['user_id']);
	phive('Cashier')->transactUser($user, -$cash, "Inactivity fee.");
	echo "Deducted $cash from user with id: ".$eu['user_id'].", last login was: {$eu['last_login']}<br>";
    }
}
?>
<br/>
<br/>
<form method="post" action="">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    Inactivity start date (yyyy-mm-dd): <?php rblInput('', 'sdate') ?>
    Zero out everyone, if set to false then only people with balance 1-11 are zeroed: <br/> 
    <?php rblInput('false', 'completely') ?>
    <?php rblSubmit("Submit") ?>
</form>
