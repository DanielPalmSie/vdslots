<?php
require_once __DIR__ . '/../../phive/phive.php';

function prCmd($rr, $cmd)
{
    echo $cmd . "\n";
    if ($rr) {
        shell_exec($cmd);
    }
}

function getDirectorySize($path)
{
    $bytestotal = 0;
    $path = realpath($path);
    if ($path !== false && $path != '' && file_exists($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $bytestotal += $object->getSize();
        }
    }
    return $bytestotal;
}

if (!isCli()) {
    exit;
}

$rr             = true;
$s              = phive('SQL')->getSetting('node_backup');
$rem_mysql_pwd  = $s['remote_mysql_pwd'];
$rem_user       = $s['rem_user'];
$stamp          = date('Ymd');
$base           = $s['local_base_folder']; //"/mnt/data/backup/";
$rem_base       = $s['rem_base_folder']; //"/mnt/data/backup/";
$folder         = $base . $stamp . '/';
$rem_folder     = $rem_base . $stamp . '/';
$rem_machine    = $s['rem_machine'];
$rem_san        = $s['rem_san'];
$rem_san_folder = $s['rem_san_folder'];
$fbase          = $s['base_name'];

prCmd($rr, "mkdir $folder");

// This should be run on the machine running the slaves (melita4)
foreach (phive('SQL')->sh(1)->getTables() as $tbl) {
    if (in_array($tbl, ['wins', 'bets', 'bets_mp', 'wins_mp', 'rounds'])) {
        continue;
    }

    foreach (phive('SQL')->getSetting('slave_shards') as $i => $conf) {
        $i = (string)$i;

        /*
          // Don't remove these lines, we might have to bring them back if we do the dump in an async fashion.
          "ssh root@lxc{$i}_slave 'mysqldump --no-create-info --skip-tz-utc --single-transaction --skip-add-drop-table --user $rem_user --password=$rem_mysql_pwd videoslots $tbl > $tbl.sql'",
          "ssh root@lxc{$i}_slave 'bzip2 $tbl.sql'",
          "scp root@lxc{$i}_slave:/root/$tbl.sql.bz2 {$folder}node$i.$tbl.sql.bz2",
          "ssh root@lxc{$i}_slave 'rm $tbl.sql.bz2'"
        */

        $cur_db_host = $conf['hostname'];
        $cur_file = "{$folder}node$i.$tbl.sql.xz";
        $commands = [
            // We dump the table to our host folder and compress it
            "mysqldump -h{$cur_db_host} -u{$rem_user} -p{$rem_mysql_pwd} --no-create-info --skip-tz-utc --single-transaction --skip-add-drop-table $fbase $tbl | xz -T12 -8 -vc > $cur_file"
        ];

        foreach ($commands as $cmd) {
            prCmd($rr, $cmd);
        }
    }
}

prCmd($rr, "rsync -aP -e ssh $folder root@{$rem_machine}:$rem_folder");

// Turned off because we initiate the sync on the SAN nowadays.
//prCmd($rr, "rsync -avz -e ssh $folder root@{$rem_san}:$rem_san_folder$stamp/");
//prCmd($rr, "ssh root@{$rem_san} 'chmod 777 $rem_san_folder$stamp/ -R'");

$fsize = getDirectorySize($folder);

$arr = array(
    'database_name' => $fbase,
    'local_ip'      => gethostname(),
    'local_folder'  => $folder,
    'remote_ip'     => $rem_machine,
    'remote_folder' => $rem_folder,
    'file_name'     => $folder,
    'file_size'     => $fsize
);

phive('SQL')->insertArray('backup_log', $arr);
