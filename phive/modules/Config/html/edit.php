<?php
require_once __DIR__ . '/../../../admin.php'; 
$conf = phive('Config');
$func = $_POST['func'];
if(!empty($func)){
	$conf->$func();
	exit;
}

?>
<html>
<head>
</head>
<body>
<?php $conf->renderJform(); ?>
</body>
</html>