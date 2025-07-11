<?php
require_once __DIR__ . '/../../../admin.php';
include_once '../Redirect.php'; 
$redir = phive('Redirect');
$func = $_POST['func'];
if(!empty($func)){
	$redir->$func();
	exit;
}

?>
<html>
<head>
</head>
<body>
<?php $redir->renderJform(); ?>
</body>
</html>