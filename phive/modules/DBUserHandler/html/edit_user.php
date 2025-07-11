<?php
require_once __DIR__ . '/../../../admin.php';

$uh = phive('DBUserHandler');

if(!empty($_POST['submit'])){
    $user = $uh->getExtUser($_POST['user_id']);
    
    foreach($user as $key => $value)
	$user[$key] = $_POST[$key];
    phive('SQL')->updateArray('users', $user, "user_id = ".$user['user_id']);
}

if($_GET['action'] == 'load'){
    $user = $uh->getExtUser($_GET['user_id']);
}

?>
<html>
    <head>
    </head>
    <body>
	<form method="post" >
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	    <table>
		<?php foreach($user as $field => $value): ?>
		    <tr>
			<td>
			    <?php echo $field?>:
			</td>
			<td>
			    <input type="text" name="<?php echo $field ?>" value="<?php echo $value ?>" />
			</td>
		    </tr>
		<?php endforeach ?>
	    </table>
	    <input type="submit" name="submit" value="Save"/>
	</form>
    </body>
</html>
