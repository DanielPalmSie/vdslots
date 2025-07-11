<?php

// TODO remove if not used.
exit;

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$url = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$uid = intval($url[2]);

if ($url[1] == "getaname"){

  $res = phive('SQL')->getValue('','affe_id','users','id = '.$uid);
  $affiliate = ud($res)['username'];
  $user = cu($uid);
  $cmnts = $user->getSettingsByPartial('comment');
  $last_login = $user->getAttr('last_login');

  foreach($cmnts as $info => $cmnt){
    list($x, $time) = explode("-", $info);
    echo '<img src="'.getMediaServiceUrl().'/file_uploads/cvmdmsg.png" alt="comments" />'.date("d.m.Y H:i",$time) . ": $cmnt <br />";
  }

  $calls = $user->getSettingsByPartial('called');

  foreach ($calls as $info => $cmnt){
    list($x, $time, $reason) = explode("-", $info);
    echo '<img src="'.getMediaServiceUrl().'/file_uploads/topmost-menu-phone.png"/> Called '.date("d.m.Y H:i",$time) . " by ".ud($cmnt)['id']." (".$reason.") <br />";
  }

  //error_log('sending back '.$user);
  if(!empty($affiliate))
    echo 'User\'s affiliate is : <a href="/account/'.$affiliate.'/admin/">'.$affiliate.'</a><br />';
  echo "Last login : ".$last_login."<br />";
  $date_lastbet = phive('SQL')->getValue("SELECT date FROM users_daily_stats WHERE user_id=".$uid." AND bets > 0 ORDER BY id DESC LIMIT 1");
  if(!empty($date_lastbet))
    echo "Last bet date : ".$date_lastbet;


}

if ($url[1]=="postcomment"){
  cu($uid)->addComment($_POST["comment"]);
}

if ($url[1]=="setcalled"){
  //cu()->getId();
  $reason =(isset($url[3]))?"-".$url[3]:"";
  cu($uid)->setSetting("called-".time().$reason,cu()->getId(), true, null, "called-".$reason);
}

if ($url[1]=="setemailed"){
  //cu()->getId();
  $reason =(isset($url[3]))?"-".$url[3]:"";
  cu($uid)->setSetting("emailed".time().$reason,cu()->getId(), true, null, "emailed-".$reason);
  cu($uid)->addComment("Sent email. $reason // ".cu()->getUsername());
}

if ($url[1]=="markcallagain"){
  cu($uid)->setSetting("callagain",cu()->getId(), true, null, "callagain");
  cu($uid)->addComment("Call again // ".cu()->getUsername());
}
if ($url[1]=="marknoanswer"){
  cu($uid)->setSetting("noanswer",cu()->getId(), true, null, "noanswer");
  cu($uid)->addComment("Tried to call, no answer // ".cu()->getUsername());
}

if ($url[1]=="setnfuncnumber"){
  cu($uid)->setAttr("verified_phone",0);
  cu($uid)->addComment("Marked phone number incorrect // ".cu()->getUsername());
}



if($url[1] == "callstatslist"){
  $sql = phive('SQL');
  $where_add = (isset($_GET["getcaller"])) ? "AND us.value='".intval($_GET["getcaller"])."'" : "";
  #$where_add_dates = (isset($_POST['sdate']) || isset($_POST['edate'])) ? " AND us.created_at >='{$_POST['sdate']}' AND us.created_at <='{$_POST['edate']}' " : "";
  $where_add_dates = "";

  $sql_str = "SELECT u.username, CONCAT_WS(' ', u.firstname, u.lastname) AS fullname, u.id, us.setting AS called, created_at, us.value AS callvalue, UNIX_TIMESTAMP(created_at) AS unixtime,
		COUNT(ct.transactiontype) AS deposits_after, ROUND((SUM(ct.amount)) / c.multiplier / 100, 2) AS amount_after, MAX(ct.timestamp) AS last_deposit
	FROM users_settings us 
	LEFT JOIN (SELECT * FROM cash_transactions ct 
		WHERE transactiontype = 3) AS ct ON us.user_id = ct.user_id AND ct.timestamp >= us.created_at 
	LEFT JOIN currencies c ON ct.currency = c.code 
	LEFT JOIN users u ON (u.id = us.user_id) 
	WHERE us.setting LIKE 'called%' $where_add_dates $where_add GROUP BY id";

  $sql->loadObjects($sql_str);

  echo json_encode($calls);

}

if ($url[1] == "callstatsplayer"){
  $sql = phive('SQL');
  $sdate = $url[3];
  $edate = $url[4];
  $relevance = $url[5];
  $lastcall = $url[6];
  $relevance = $lastcall + $relevance;
  
  $sql_str = "SELECT UNIX_TIMESTAMP(timestamp) as unixtime, ROUND((SUM(amount)) / c.multiplier / 100, 2) as amount FROM deposits d
	LEFT JOIN currencies c ON d.currency = c.code
	WHERE user_id = $uid AND status = 'approved' AND timestamp >= '$sdate' AND timestamp <= '$edate' 
	AND UNIX_TIMESTAMP(timestamp) >= $lastcall AND UNIX_TIMESTAMP(timestamp) <= $relevance GROUP BY unixtime";
  
  $stats = $sql->loadArray($sql_str);
  $newarr = array();
  echo '<ul><li class="header">Time<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Amount EUR</span></li>';
  foreach ($stats as $stat)
    echo '<li>'.date("d.m.Y H:i", $stat["unixtime"]).'<span>'.$stat["amount"].'</span></li>';
  echo '</ul>';
}
