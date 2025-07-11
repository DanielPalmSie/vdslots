<?php
require_once __DIR__ . '/../../../admin.php';
$v = phive('Vouchers');

if(!empty($_POST['submit_series']) && !empty($_POST['stub']) && !empty($_POST['number'])){
	$v->createSeries($_POST['stub'], $_POST['number'], $_POST['bonus_id'], $_POST['affe_id'], $_POST['pwd'], $_POST['exclusive']);
	echo "<br><strong>The series: {$_POST['stub']}, was added.</strong><br>";	
}else if(!empty($_POST['submit_csv'])){
	$dir 		= __DIR__ . '/../../../../temp/';
	shell_exec("rm $dir*.*");
	$file 		= uniqid().'.csv';
	$file_name 	= $dir.$file;
	file_put_contents($file_name, $v->asCsv($_POST['stub']));
}
?>
<div style="margin:10px; padding:10px;">
<strong>Series to CSV:</strong><br>
<?php if(!empty($_POST['submit_csv'])): ?>
<br>
<a href="/temp/<?php echo $file ?>">Download</a>
<br>
<?php endif ?>
<form action="" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	<p>Series name:</p>
	<p><input type="text" name="stub" /></p>		
	<p><input type="submit" name="submit_csv" value="Download"></p>
</form>
<br>
<br>
<strong>Add new series:</strong><br>
<form action="" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	<p>Series name:</p>
	<p><input type="text" name="stub" /></p>	
	<p>Number of vouchers:</p>
	<p><input type="text" name="number" /></p>
	<p>Password/Code (leave empty for automatic generation which is unique for each voucher):</p>
	<p><input type="text" name="pwd" /></p>
	<p>Exclusive (set to 0 to allow players to activate several times):</p>
	<p><input type="text" name="exclusive" value="1" /></p>
	<p>Select Bonus:</p>
	<?php dbSelectWith("bonus_id", phive('Bonuses')->getNonDeposits(date('Y-m-d'), 'normal'), 'id', 'bonus_name') ?>
	<p>Select Affiliate:</p>
	<?php dbSelectWith("affe_id", array_merge(array(array('user_id' => 0, 'username' => 'None')),phive('Affiliater')->getAllAffiliatesExt()), 'user_id', 'username') ?>
	<p><input type="submit" name="submit_series" value="Add Series"></p>
</form>
</div>
