<?php
require_once __DIR__ . '/../phive.php';

if(!isCli())
    exit;

class Km{

    function __construct(){
        $this->db = phive('SQL')->doDb('key_management');
    }

    // User handling
    function addUsersToProfile($unames, $profile_name){
        if($unames == 'all')
            $unames = $this->db->load1Darr("SELECT * FROM `users`", 'username');

        foreach($unames as $uname)
            $this->addUserToProfile($uname, $profile_name);
    }

    function removeUsersFromProfile($unames, $profile_name){
        if($unames == 'all')
            $unames = $this->db->load1Darr("SELECT * FROM `users`", 'username');

        foreach($unames as $uname)
            $this->removeUserFromProfile($uname, $profile_name);
    }

    function addProfilesToUser($profiles, $uname){
        foreach($profiles as $profile)
            $this->addUserToProfile($uname, $profile);
    }

    function copyProfileToUser($from_uname, $to_uname)
    {
        $from_user = $this->db->loadAssoc("SELECT * FROM `users` WHERE username = '$from_uname'");
        $to_uname = $this->db->loadAssoc("SELECT * FROM `users` WHERE username = '$to_uname'");
        $profile_list = $this->db->loadArray("SELECT * FROM `up_conn` WHERE user_id = {$from_user['id']}");

        if (empty($from_user) || empty($to_uname) || empty($profile_list)) {
            return false;
        }

        foreach ($profile_list as $profile) {
            $this->db->insertArray('up_conn', ['profile_id' => $profile['profile_id'], 'user_id' => $to_uname['id']]);
        }
        return true;
    }
    
    function addUserToProfile($uname, $profile_name){
        $u = $this->db->loadAssoc("SELECT * FROM `users` WHERE username = '$uname'");
        $p = $this->db->loadAssoc("SELECT * FROM `profiles` WHERE profile_name = '$profile_name'");
        if(empty($u) || empty($p))
            return false;
        $this->db->insertArray('up_conn', ['profile_id' => $p['id'], 'user_id' => $u['id']]);
    }

    function removeUserFromProfile($uname, $profile_name){
        $u = $this->db->loadAssoc("SELECT * FROM `users` WHERE username = '$uname'");
        if(empty($u))
            return false;        
        if($profile_name == 'all'){
            $ps = $this->db->loadArray("SELECT * FROM `profiles`");
            foreach($ps as $p)
                $this->db->delete('up_conn', ['profile_id' => $p['id'], 'user_id' => $u['id']]);            
        }else{
            $p = $this->db->loadAssoc("SELECT * FROM `profiles` WHERE profile_name = '$profile_name'");
            if(empty($p))
                return false;
            $this->db->delete('up_conn', ['profile_id' => $p['id'], 'user_id' => $u['id']]);
        }
    }

    function getProfilesWithUsers(){
        $profiles = $this->db->loadArray("SELECT * FROM profiles", 'ASSOC', 'profile_name');
        foreach($profiles as &$profile)
            $profile['users'] = $this->db->loadArray("SELECT u.* FROM users u, up_conn c WHERE c.profile_id = {$profile['id']} AND u.id = c.user_id "); // Implicit inner join
        return $profiles;
    }

    
    // Machine handling
    function addMachinesToProfile($hostnames, $profile_name){
        foreach($hostnames as $hostname)
            $this->addMachineToProfile($hostname, $profile_name);
    }

    function addMachineToProfile($hname, $profile_name){
        $m = $this->db->loadAssoc("SELECT * FROM `machines` WHERE hostname = '$hname'");
        $p = $this->db->loadAssoc("SELECT * FROM `profiles` WHERE profile_name = '$profile_name'");
        if(empty($m) || empty($p))
            return false;
        $this->db->insertArray('mp_conn', ['profile_id' => $p['id'], 'machine_id' => $m['id']]);
    }

    function removeMachineFromProfile($hname, $profile_name){
        $m = $this->db->loadAssoc("SELECT * FROM `machines` WHERE hostname = '$hname'");
        if($profile_name == 'all'){
            $ps = $this->db->loadArray("SELECT * FROM `profiles`");
            foreach($ps as $p)
                $this->db->delete('mp_conn', ['profile_id' => $p['id'], 'machine_id' => $m['id']]);
        } else {
            $p = $this->db->loadAssoc("SELECT * FROM `profiles` WHERE profile_name = '$profile_name'");
            if(empty($m) || empty($p))
                return false;
            $this->db->delete('mp_conn', ['profile_id' => $p['id'], 'machine_id' => $m['id']]);
        }
    }

    function getProfilesWithMachines(){
        $profiles = $this->db->loadArray("SELECT * FROM profiles", 'ASSOC', 'profile_name');
        foreach($profiles as &$profile)
            $profile['machines'] = $this->db->loadArray("SELECT m.* FROM machines m, mp_conn c WHERE c.profile_id = {$profile['id']} AND m.id = c.machine_id "); // Implicit inner join
        return $profiles;
    }

    
    // General handling
    function generateAuthFiles(){
        $base_dir = __DIR__ . "/../../keys";
        $res = [];
        $ures = $this->getProfilesWithUsers();
        $mres = $this->getProfilesWithMachines();
        foreach($mres as $profile => $profile_data){
            foreach($profile_data['machines'] as $m){                 
                foreach($ures[$profile]['users'] as $u){
                    $str = empty($u['ips']) ? '' : "from=\"{$u['ips']}\" ";
                    $str .= $u['pub_key'].' '.$u['username'];
                    $res[$m['hostname']][] = $str; 
                }
            }
        }
        foreach($res as $hostname => $keys){
            $keys = array_unique($keys);
            $folder = "$base_dir/$hostname";
            if(!file_exists($folder))
                mkdir($folder);
            $key_str = implode("\n", $keys);
            file_put_contents("$folder/authorized_keys", $key_str);
        }
        return $res;
    }
    

    function addAllIpUsersToProfile($profile){
        $unames = $this->db->load1Darr("SELECT * FROM `users` WHERE ips != ''", 'username');
        $this->addUsersToProfile($unames, $profile);        
    }
    
}

$km = new Km();

//We copy from nicky all access to daniel.massa
//$km->copyProfileToUser('nicky', 'daniel.massa');

//$km->removeUserFromProfile('justin', 'affiliates');
//$km->removeUserFromProfile('justin', 'banner_service');

//$km->addProfilesToUser(['office_physical'], 'justin');

//$km->addAllIpUsersToProfile('logbak');

//$km->removeUsersFromProfile(['jonathan', 'jesus'], 'office_physical');
//$km->removeUserFromProfile('fasthosts_kayako_partner', 'all');
//$km->removeUsersFromProfile(['jonathan', 'jesus'], 'office_physical');
//$km->removeUserFromProfile('salvatore', 'all');
//$km->removeUsersFromProfile(['justin', 'daniel'], 'office_mysql_node');
//$km->addUsersToProfile('all', 'bmit');

//$km->addMachinesToProfile(['fileserver'], 'fileserver');
//$km->addUsersToProfile(['fileserver'], 'promos');
//$km->addMachinesToProfile(['office2'], 'pkr');
//$km->addUsersToProfile(['balveg', 'charlelie'], 'fileserver');
//$km->addUsersToProfile(['balveg', 'charlelie'], 'promos');
//$km->addUsersToProfile(['charlelie'], 'newsite');

//$km->addProfilesToUser(['promos', 'stage', 'fileserver', 'psptest', 'gptest'], 'javier');

//office_physical
/*
$km->addMachinesToProfile(['newdb1', 'newdb2'], 'newsite');
$km->addMachinesToProfile(['codereviews'], 'codereviews');
$km->addProfilesToUser(['promos'], 'codereviews');
$km->addProfilesToUser(['codereviews'], 'ricardo');
$km->addProfilesToUser(['codereviews'], 'henrik');
//*/

//$km->addMachinesToProfile(['melita8'], 'production_physical');

//$km->addProfilesToUser(['production_physical', 'promos'], 'melita8');
//$km->removeMachineFromProfile('server217-174-248-203.live-servers.net', 'all');
//$km->removeMachineFromProfile('server88-208-221-127.live-servers.net', 'all');
//$km->addProfilesToUser(['production_physical'], 'office2');
//$km->addUsersToProfile(['emmanuel'], 'banner_service');
//$km->addProfilesToUser(['production_physical'], 'office2');
//$km->addUsersToProfile(['emmanuel'], 'office_physical');
//$km->addUsersToProfile(['mark'], 'fileserver');
//$km->addUsersToProfile(['mark'], 'psptest');
//$km->addUsersToProfile(['mark'], 'gptest');
//$km->addUsersToProfile(['ricardo'], 'affiliates');
//$km->addUsersToProfile(['ricardo'], 'dmapi');
//$km->addUsersToProfile(['melita3', 'melita4', 'henrik'], 'mysql_node_production');

$res = $km->generateAuthFiles();
//$res = $km->getProfilesWithUsers();
///print_r($res);

//exit;

//$km->removeMachineFromProfile('dmapi', 'mts');
//$km->removeMachineFromProfile('dmapifailover', 'mts');

//$ures = $km->getProfilesWithUsers();
//$mres = $km->getProfilesWithMachines();
//print_r($res);


//foreach(range(0,9) as $num)
//    $km->addMachinesToProfile(['lxc'.$num.'slave'], 'mysql_slave_node_production');


//$km->addUsersToProfile(['henrik', 'salvatore'], 'ws_production');
//$km->addUsersToProfile('all', 'test');
//$km->addUsersToProfile(['henrik', 'salvatore', 'ricardo'], 'production_physical');
//$km->addUsersToProfile(['henrik', 'salvatore', 'ricardo'], 'production_physical');
//$km->addUsersToProfile(['daniel', 'henrik', 'melita1', 'melita2', 'melita3', 'melita4'], 'logbak');
//$km->addUsersToProfile(['justin', 'henrik'], 'affiliates');
//$km->addUsersToProfile(['justin', 'henrik'], 'banner_service');
//$km->addUsersToProfile(['edwin', 'dmapifailover'], 'dmapi');
//$km->addUsersToProfile(['lee'], 'production_physical');
//$km->addUsersToProfile(['lee'], 'kayako');
//$km->addUsersToProfile(['henrik', 'ricardo', 'vadim', 'mtsfailover'], 'mts');
//$km->addUsersToProfile(['henrik'], 'promos');
//$km->addUsersToProfile(['henrik', 'ricardo'], 'ru_proxy');

//$km->addMachinesToProfile(['ws1'], 'ws_production');
//$km->addMachinesToProfile(['melita1.videoslots.com', 'melita2.videoslots.com', 'melita3.videoslots.com', 'melita4.videoslots.com'], 'production_physical');
//$km->addMachinesToProfile(['mts', 'mtsfailover'], 'mts');
//$km->addMachinesToProfile(['dmapi', 'dmapifailover'], 'dmapi');
//$km->addMachinesToProfile(['server217-174-248-203'], 'kayako');
//$km->addMachinesToProfile(['server217-174-248-203'], 'affiliates');
//$km->addMachinesToProfile(['server88-208-220-228'], 'logbak');
//$km->addMachinesToProfile(['server88-208-220-228'], 'banner_service');
//$km->addMachinesToProfile(['server77-68-37-153'], 'promos');
//$km->addMachinesToProfile(['server77-68-37-153'], 'ru_proxy');
//$km->addMachinesToProfile(['server88-208-221-127'], 'test');
