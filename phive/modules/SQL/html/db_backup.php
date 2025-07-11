<?php
require_once __DIR__ . '/../../../phive.php';

$db = phive('SQL')->getSetting('database');
$host = phive('SQL')->getSetting('hostname');
$mysqldump = phive('SQL')->getSetting('backup_mysqldump');
$folder = phive('SQL')->getSetting('backup_folder');
$zipper = phive('SQL')->getSetting('backup_compressor');
$user = phive('SQL')->getSetting('username');
$pass = phive('SQL')->getSetting('password');

if ($folder && $mysqldump)
{
	if ($pass)
		$pass_str = "-p$pass";
	else
		$pass_str = "";
	
	// using French time indication (e.g. 20h30) to avoid semicolon
	$date = date("Y-m-d-H\hi");
	$filename = $folder . "/$db-$date.sql";
	
	if ($zipper)
	{
		if (strpos('gzip', $zipper) != -1)
			$filename .= '.gz';
		elseif (strpos('bzip2', $zipper) != -1)
			$filename .= '.bz2';
		else
			$filename .= '.compressed';
	}
	
	// Does not use read-lock (-x), so there is a possibility of discrepancies, but
	//  this is better than shutting down the whole website for seconds.
	$sh_dump = "$mysqldump -u $user $pass_str -h $host $db";
	
	if ($zipper)
		$sh_full = "$sh_dump | $zipper > $filename";
	else
		$sh_full = "$sh_dump > $filename";

	// Scribble password so we don't output it to log or so.
	//  I've added -p so that if someone is stupid and has the same user/pass
	//  it won't scribble both.
	$sh_cosmetic = $sh_full;
	if ($pass)
		$sh_cosmetic = str_replace("-p".$pass, "-p*******", $sh_cosmetic);
	
	echo $sh_cosmetic;
	exec($sh_full, $output, $ret);
	
	if ($ret === 1)
	{
		trigger_error("MySQL dump failed. Check if executable exists and folder has writing permissions. Click to see the shell command.---".$sh_cosmetic, E_USER_ERROR);
	}
}
else
{
	trigger_error("Trying to do a MySQL dump without having specified folder and/or mysqldump executable", E_USER_ERROR);
}
?>