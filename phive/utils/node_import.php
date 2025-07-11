<?php
/*
 * NOTE that correct schema needs to exist before this script is run, import separately if you need it.
 * NOTE also that any *.sql files in the dump dir needs to be removed in case you don't want to import them ofc.
 *
 * Example invocation to skip bets and wins and do a dry run:
 * php node_import.php type=light dir=feting/tmp/node_dumps test=true
 *
 * Example invocation to skip only do first_deposits and race_entries and do a dry run:
 * php node_import.php do_tables=first_deposits,race_entries dir=feting/tmp/node_dumps test=true
 *
 * Run in two different terminals at the same time to do two nodes at the same time:
 * php node_import.php type=light dir=feting/tmp/node_dumps nodes=0
 * php node_import.php type=light dir=feting/tmp/node_dumps nodes=1
*/

require_once __DIR__ . '/../phive.php';

function doCmd($cmd, $exec = true){
    if($exec)
        shell_exec($cmd);
    else
        echo "$cmd\n";
}

function doTable($tbl, $ex_tbls, $do_tbls){
    if(!empty($do_tbls) && !in_array($tbl, $do_tbls))
        return false;
    if(!empty($ex_tbls) && in_array($tbl, $ex_tbls))
        return false;
    return true;
}

function gdprObfuscate($db, $tbl){
    if($tbl == 'users'){
        $db->query("UPDATE users SET email = 'xxxxxx@xxxxx.com', firstname = 'xxxxxxxxxx', lastname = 'xxxxxxxxxxxxx', address = 'xxxxxxxxxxxxx', city = 'xxxxxxxxxxxxx', zipcode = 'xxxxxxxxxxxxx', dob = '1976-01-01', reg_ip = '', cur_ip = '', sex = 'Female', mobile = '35612341234'");
    }
}

if(!isCli())
    exit;

// We shift off the first arg which is always the file name of the executing file.
array_shift($_SERVER['argv']);

$args = [];
while($_SERVER['argv']){
    $tmp               = array_shift($_SERVER['argv']);
    list($key, $value) = explode('=', $tmp);
    $args[$key]        = $value;
}

if($args['type'] == 'light')
    $ex_tbls = ['bets', 'wins', 'wins_mp', 'bets_mp', 'wins_tmp', 'bets_tmp'];
else if(!empty($args['excl_tables']))
    $ex_tbls = explode(',', $args['excl_tables']);
else if(!empty($args['do_tables']))
    $do_tbls = explode(',', $args['do_tables']);

$base_dir   = $args['dir'];
$do_exec    = $args['test'] == 'true' ? false : true;
$import_cnt = empty($args['dump_cnt']) ? 10 : $args['dump_cnt'];
$sql        = phive('SQL');
$num_nodes  = count($sql->getSetting('shards'));

if(!empty($args['nodes']))
    $do_only_nodes = explode(',', $args['nodes']);

if($import_cnt % $num_nodes != 0)
    die("\nYour import count of $import_cnt is not compatible with your local node count which is $num_nodes\n");

if(empty($base_dir))
    die("\nThe dir=foo/bar argument is required!\n");

echo "\nStarting import of global tables\n";

// This loop is actually unnecessary but is here in order to get the exact same state as on live,
// for debugging reasons if nothing else.
foreach($sql->getSetting('shards') as $num => $shard_info){

    if(!empty($do_only_nodes) && !in_array($num, $do_only_nodes)){
	continue;
    }
    
    $pwd        = $shard_info['password'];
    $usr        = $shard_info['username'];
    $host       = $shard_info['hostname'];
    $db         = $shard_info['database'];
    $base_fname = "/$base_dir/node$num";

    // If do_table_type=sharded we skip the global tables.
    if($args['do_table_type'] != 'sharded'){
        foreach($sql->getSetting('global_tables') as $gt){
            if(!doTable($gt, $ex_tbls, $do_tbls))
                continue;
            if($do_exec)
                $sql->sh($num)->query("TRUNCATE $gt");
            echo "Importing global table $gt into shard $num\n";
            if(!file_exists("$base_fname.$gt.sql")){		
                //doCmd("rm -f $base_fname.$gt.sql", $do_exec);
                doCmd("bzip2 -dk $base_fname.$gt.sql.bz2", $do_exec);
            }
            doCmd("mysql -u$usr -p$pwd -h$host --default_character_set utf8 $db < $base_fname.$gt.sql", $do_exec);
            gdprObfuscate($sql->sh($num), $gt);

        }
    }
    
    echo "\n\n";

    // If do_table_type=global we skip the sharded tables.
    if($args['do_table_type'] != 'global'){
        foreach($sql->getSetting('sharded_tables') as $st){
            if(!doTable($st, $ex_tbls, $do_tbls))
                continue;
            if($do_exec)
                $sql->sh($num)->query("TRUNCATE $st");
            // We loop all dumped file numbers.
            for($i = 0; $i < $import_cnt; $i++){
                $base_fname = "/$base_dir/node$i";
                // We check with modulo which files should be imported into the current node.
                if($i % $num_nodes == $num){
                    echo "Importing from dump $i: sharded table $st into shard $num\n";
                    if(!file_exists("$base_fname.$st.sql")){		
                        //doCmd("rm -f $base_fname.$st.sql", $do_exec);
                        doCmd("bzip2 -dk $base_fname.$st.sql.bz2", $do_exec);
                    }
                    doCmd("mysql -u$usr -p$pwd -h$host --default_character_set utf8 $db < $base_fname.$st.sql", $do_exec);
                    gdprObfuscate($sql->sh($num), $st);
                }
            }
        }
    }

    echo "-------- Next Node --------- \n\n\n";
}

// We set all passwords to 123456 as we're on local anyway, and clear out reg_ip to make testing the registration flow easier.
// Because we have ofuscated the email it won't match with the username which is not obfuscated, we will therefore get login issues because the username contains
// @, so we replace @ with _ in order to get a canonical and unique username we can use instead.
$sql->shs()->query("UPDATE users SET password = 'c79194b0356573ee78398fc6486b4644', reg_ip = '', username = REPLACE(username, '@', '_')");

shell_exec("curl logbak.videoslots.com/events/?tag=videoslots_com_backup_restore_success");

