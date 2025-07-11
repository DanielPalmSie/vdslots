<?php
require_once __DIR__ . '/../../../admin.php';
$not_changeable = array('admin', 'Stevizzz');
if(!empty($_POST['username']) && !in_array($_POST['username'], $not_changeable)){
	$user = phive('UserHandler')->getUserByUsername($_POST['username']);
	if(!empty($user)){
		$user->setAttribute('password', 'c79194b0356573ee78398fc6486b4644');
		echo "User password resetted successfully.";	
	}else
		echo "No user with the username ".$_POST['username'];
}else{
	?>
		<form method="post">
		    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
			Username: <input type="text" name="username"/>
			<input type="submit" name="save_settings" value="Save" id="save_settings"/>
		</form>
	<?php
}
?>