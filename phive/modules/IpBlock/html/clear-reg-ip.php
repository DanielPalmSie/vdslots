<?php
require_once __DIR__ . '/../../../admin.php';

if(!empty($_GET['ip']) && p('clear.ips')){
	phive("SQL")->shs('', '', null, 'users')->query("UPDATE `users` SET reg_ip = '' where reg_ip = '{$_GET['ip']}'");
	echo 'IP was cleared.';
}
?>
<br/>
<br/>
<form method="GET" action="">
	Enter IP: <input name="ip" type="text" />
	<input type="submit" value="Submit" />
</form>
