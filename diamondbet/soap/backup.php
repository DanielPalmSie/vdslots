<?php
require_once __DIR__ . '/../../phive/phive.php';

function prCmd($rr, $cmd)
{
    echo $cmd . "\n";
    if ($rr) {
        shell_exec($cmd);
    }
}

if (!isCli()) {
    exit;
}

$rr             = true;
$s              = phive('SQL')->getSetting('backup');
$base           = $s['local_base_folder']; //"/mnt/data/backup/";
$target         = $s['target_ip']; //"77.68.38.12";
$tfolder        = $s['target_folder']; //"/mnt/pool1/nodebackups/nodedumps/";
$stamp          = date('Ymd');
$fbase          = $s['base_name'];
$local_filename = "{$base}{$fbase}{$stamp}.sql.bz2";
$rem_mysql_pwd  = $s['remote_mysql_pwd'];
$rem_user       = $s['rem_user'];
$rem_san        = $s['rem_san'];
$rem_san_folder = $s['rem_san_folder'];
$db_host        = $s['local_ip'];

$commands = array(
    "ssh root@$target 'rm {$tfolder}{$fbase}.sql'",
    "ssh root@$target 'mysqldump {$fbase} --skip-tz-utc --single-transaction -h$db_host -u$rem_user -p$rem_mysql_pwd > {$tfolder}{$fbase}.sql'",
    "ssh root@$target 'rm {$tfolder}{$fbase}.sql.bz2'",
    "ssh root@$target 'bzip2 -k {$tfolder}{$fbase}.sql'",
    "ssh root@$target 'mv {$tfolder}{$fbase}.sql.bz2 {$tfolder}{$fbase}{$stamp}.sql.bz2'"
);

if ($fbase === "videoslots") {
    $commands[] = "scp root@{$target}:{$tfolder}{$fbase}{$stamp}.sql.bz2 $local_filename";
    // "scp $local_filename root@{$rem_san}:{$rem_san_folder}{$stamp}.sql.bz2", // Turned off because we initiate the sync on the SAN nowadays.
}

foreach ($commands as $cmd) {
    prCmd($rr, $cmd);
}

$fsize = filesize($local_filename);

$arr = array(
    'database_name' => $fbase,
    'local_ip'      => $db_host,
    'local_folder'  => $base,
    'remote_ip'     => $target,
    'remote_folder' => $tfolder,
    'file_name'     => "$fbase{$stamp}.sql.bz2",
    'file_size'     => $fsize / 1000000
);

phive('SQL')->insertArray('backup_log', $arr);
