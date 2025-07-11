<?php
require_once __DIR__ . '/../phive.php';

if(!isCli())
    exit;

class Nh{

    function __construct(){
        $this->db         = phive('SQL')->doDb('network_hosts');
        $this->target_dir = '../../hostfiles/';
        if(!file_exists($this->target_dir)){
            mkdir($this->target_dir);
        }
    }

    function createFiles($for_dc, $for_tags, $using_dcs, $using_tags){
        $sql_str      = "SELECT * FROM machines WHERE dc = '$for_dc' AND tag IN({$this->db->makeIn($for_tags)})";
        $for_machines = $this->db->loadArray($sql_str);
        foreach($for_machines as $m){
            $str = "
127.0.0.1 localhost
127.0.0.1 {$m['aliases']}

# The following lines are desirable for IPv6 capable hosts
::1     localhost ip6-localhost ip6-loopback
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters

";

            $sql_str      = "SELECT * FROM machines WHERE dc IN({$this->db->makeIn($using_dcs)}) AND tag IN({$this->db->makeIn($using_tags)}) ORDER BY INET_ATON(ip)";
            //echo $sql_str;
            //exit;
            $add_machines = $this->db->loadArray($sql_str);
            foreach($add_machines as $am){
                $str .= "{$am['ip']} {$am['aliases']}\n";
            }
            
            file_put_contents($this->target_dir.$m['nm'], $str);
        }
    }

    function cpFiles(){
        $dir = new DirectoryIterator($this->target_dir);
        foreach($dir as $fileinfo) {
            if(!$fileinfo->isDot()) {
                //print_r($fileinfo);
                //echo $fileinfo->getPathName()."\n";
                echo "scp {$fileinfo->getPathName()} root@{$fileinfo->getFilename()}:/etc/hosts\n";
            }
        }
    }
    
}

$nh = new Nh();

$nh->createFiles('melita', ['physical', 'safe'], ['melita', 'bmit'], ['physical', 'lxc', 'san', 'safe']);
$nh->cpFiles();
