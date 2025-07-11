<?php
require_once __DIR__ . '/../../../admin.php';

// TODO henrik remove

header("Content-type: application/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=players.csv");
header("Pragma: no-cache");
header("Expires: 0");

$uh = phive('DBUserHandler');
$result = $uh->searchAsCsvDownload();

$cols = empty($_GET['cols']) ? array('email', 'lastname', 'firstname', 'username', 'cash_balance') : explode(',', $_GET['cols']);

echo implode(',', $cols)."\n";

foreach ($result as $user){
	$str = '';
	foreach($cols as $c)
		$str .= $user[$c].',';
	echo trim($str, ',')."\n";
}
