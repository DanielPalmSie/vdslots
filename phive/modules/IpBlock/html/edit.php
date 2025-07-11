<?php
require_once __DIR__ . '/../../../admin.php';
include_once '../IpBlock.php'; 
$guard = phive('IpBlock');
$func = $_POST['func'];
if(!empty($func)){
	$guard->$func();
	exit;
}

?>
<html>
<head>
</head>
<body>
<?php $guard->renderJform(); ?>
</body>
</html>