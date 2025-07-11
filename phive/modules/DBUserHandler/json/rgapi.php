<?php
require_once __DIR__ . '/../../../phive.php';

// TODO henrik remove if not used

$req = $_REQUEST;

if($req['key'] != phive('UserHandler')->getSetting('rgapi-key')) {
    die(json_encode(['error' => "wrong api key"]));
}

//$res = $req;
//echo json_encode($res);
$db = phive('SQL');
$uh = phive('UserHandler');
$sdate = phive()->fDate($req['start-time']);
$edate = phive()->fDate($req['end-time']);
$response = [];

switch($req['action']){
    case 'reg-or-updated':
        $regs       = $uh->getRegsBetween($sdate, $edate);
        $actions    = $uh->getActions($req['start_time'], $req['end_time'], '', "AND tag IN('profile-update-success', 'profile-update-by-admin')");
        $uids       = phive()->arrCol($actions, 'target');
        $uid_str    = $db->makeIn($uids);
        $updates    = $db->shs('merge', '', null, 'users')->loadArray("SELECT * FROM users WHERE id IN($uid_str)");
        $result     = array_merge($regs, $updates);
        if(!empty($result)) {
            foreach($result as &$r){
                if(empty($r['active']))
                    $r['block_reason_code'] = $db->sh($r, 'id', 'users_blocked')->getValue("SELECT reason FROM users_blocked WHERE user_id = {$r['id']} ORDER BY id DESC");
            }
            $response = ['success' => 'true', 'result' => $result];
        } else {
            $response = ['success' => 'true', 'result' => 'No users found'];
        }

        break;

    case 'registrations':
        $regs       = $uh->getRegsBetween($sdate, $edate);
        $response = ['success' => 'true', 'result' => $regs];
        break;

    case 'expired_doc':
        // Unverify the users account
        $user = cu($req['user_id']);
        $user->unVerify();

        // Send an email to the user
        $mh = phive('MailHandler');
        $mh->sendMail('id.picexpired', $user);
        break;
        
    default:
        $response = ['success' => 'false', 'error' => 'No applicable action'];
        break;
}
header('Content-type: application/json');
echo json_encode($response);
