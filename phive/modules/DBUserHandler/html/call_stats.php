<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/m/Form.php';
//require_once __DIR__ . '/../../../../diamondbet/html/display.php';
use MVC\Form as F;

$callreasons = array("year_since_register" => "Registered year ago", "birthday" => "Birthday", "inactive_60days" => "60 days inactivity");
$array_callers[0] = "SELECT";
$relevantPeriod = array(
  "946707779" => "All time",
  "86400" => "1 day",
  "259200" => "3 days",
  "604800" => "1 week",
  "1209600" => "2 weeks",
  "2419200" => "1 month",
  "4838400" => "2 months",
  "7257600" => "3 months");
$listoptions = array(0 => "Show players with deposit row(s)", 1 => "Show players without deposit row(s)", 2 => "Show all called players");
$sql = phive('SQL');
$permission_global = p('callstats.global_stats');
if (!$permission_global) {
  $_POST["caller"] = cu()->getId();
}

if (date("m") == 1) {
  $m = 12;
  $y = date("Y") - 1;
} else {
  $m = date("m");
  $y = date("Y");
}

$sdate = (isset($_POST["sdate"])) ? $_POST["sdate"] : date("{$y}-{$m}-d");
$edate = (isset($_POST["edate"])) ? $_POST["edate"] : date("Y-m-d");

$where_add           = (isset($_POST["caller"]) && $_POST["caller"] != 0) ? " AND us.value='" . intval($_POST["caller"]) . "'" : "";
$where_add_dates     = " AND us.created_at >= '{$sdate}' AND us.created_at <='{$edate}' ";
$where_add_relevance = (isset($_POST['relevance'])) ? " AND (UNIX_TIMESTAMP(timestamp) < UNIX_TIMESTAMP(created_at) + {$_POST['relevance']} OR ISNULL(timestamp))" : "";

$sql_str = "SELECT 
              u.username, 
              CONCAT_WS(' ',u.firstname,u.lastname) AS fullname, 
              UNIX_TIMESTAMP(u.last_login) AS last_login, 
              u.country, 
              u.id, 
              us.setting AS called, 
              created_at, 
              us.value, 
              UNIX_TIMESTAMP(created_at) AS unixtime, 
              COUNT(dep.amount) AS deposits_after, 
              ROUND((SUM(dep.amount)) / c.multiplier / 100, 2) AS amount_after, 
              UNIX_TIMESTAMP(MAX(dep.timestamp)) AS last_deposit 
            FROM users_settings us 
            LEFT JOIN (SELECT * FROM deposits dep WHERE status IN ('approved','pending')) AS dep ON us.user_id = dep.user_id AND dep.timestamp >= us.created_at
            LEFT JOIN currencies c ON dep.currency = c.code 
            LEFT JOIN users u ON (u.id = us.user_id) 
            WHERE us.setting LIKE 'called%'" . $where_add_dates . $where_add . $where_add_relevance . " GROUP BY id ORDER BY unixtime";

//echo $sql_str;

$calls = $sql->loadObjects($sql_str);

//print_r($calls);

$i = 0;

$passCallsArr = array();
foreach ($calls as $obj) {
  if ($obj->unixtime != NULL) {
    $passCallsArr[$obj->id] = $obj->unixtime;
  }
}


$passCallsArr = serialize($passCallsArr);

$callers = $sql->loadArray("SELECT DISTINCT value FROM users_settings WHERE setting LIKE 'called%'", 'ASSOC', 'value');

foreach ($callers as $x => $y) {
  $array_callers[$x] = $y['value'];
}

$ins = $sql->makeIn(array_values($array_callers));
$usernames = $sql->loadArray("select id,username from users where id in (" . $ins . ")");
foreach ($usernames as $x => $user) {
  $array_callers[$user['id']] = $user['username'];
}

?>
<script type="text/javascript">
 $(document).ready(function(){
   $('#caller').change(function(){
     $("#form1").submit();
   });
   $('#relevance').change(function(){$("#form1").submit();});

   $('#filter').change(function(){
     var filter = $("#filter option:selected").val();
     console.log(filter);
     $('.trow_info').each(function(){
       $(this).removeClass('open');
       $(this).hide();
     });
     $('tbody > .tr').each(function() {
       if (filter == 2)
         $(this).show();
       else if (filter == 0 && $(this).data('deposits') > 0)
         $(this).show();
       else if (filter == 1 && $(this).data('deposits') == 0)
         $(this).show();
       else if (filter < 1)
         $(this).hide();
       else
         $(this).hide();       
     });
   });
   
   $('#filter').trigger('change');
   $('#list').on('click','tr',function(){
     var userid=$(this).data('id');
     if ($(this).data('deposits')=="0") return false;
     if ($('tr[data-userid="'+userid+'"]').hasClass('open')) {
       $('tr[data-userid="'+userid+'"]').removeClass('open');
       $('tr[data-userid="'+userid+'"]').hide();
       return false;
     }
     $.get("/jsr/callstatsplayer/"+userid+"/"+$("#sdate").val()+"/"+$("#edate").val()+"/"+$("#relevance option:selected").val()+"/"+$(this).data('lasspand')+"/", function(data){
       $('tr[data-userid="'+userid+'"]').html('<td colspan="8">'+data+'</td>');
       $('tr[data-userid="'+userid+'"]').show();
       $('tr[data-userid="'+userid+'"]').addClass('open');
     });
   });


   $("#sdate").change(function(){
     return false;
   });

   $('tr').show();
   
 });
</script>
<style>
 .trow_info {font-size:12px;}
 label {padding:10px 0 10px 0;display:inline-block;width:150px;}
 tbody .tr:hover {background:#faa8a2;cursor:pointer;}
 .trow_info ul {list-style:none;}
 .trow_info li  {}
 .trow_info span {
   margin:0 0 0 250px;}
 .trow_info .header {color:#cc0000;}
 #list th {text-align:left;background:#ccc;}
 #list td {border:1px solid gray;}
 #list {
   width:100%;
   margin: 0px;
   padding: 0px;
 }
</style>
<div class="pad10">
  <form id="form1" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <?php if ($permission_global) {
      echo F::labelSelectList("Select caller", "caller", $array_callers, (isset($_POST['caller'])) ? $_POST['caller'] : null);
    }

    ?>
    <br />
    <?=F::labelInput("Select start date", "sdate", array("type" => "date", "value" => $sdate))?><br />
    <?=F::labelInput("Select end date", "edate", array("type" => "date", "value" => $edate))?><br />
    <?=F::labelSelectList("Select relevant period", "relevance", $relevantPeriod, $_POST['relevance'])?><br />
    <?=F::labelSelectList("Filter", "filter", $listoptions, $_POST['filter'])?><br />
    <?=F::labelInput("Search", "submitsearch", array("type" => "submit"))?><br />
  </form>
  <div id="searchres">
  </div>
  <!--<tr><th>Username</th><th>Full name</th><th>Call date</th><th>Caller</th><th>Call reason</th><th>Last deposit</th><th>Deposit amount since last call</th><th># deposits after last call</th></tr>-->
  <div id="list">
    <table class="list">
      <thead>
        <tr>
          <th width="9%">Username</th>
          <th width="19%">Full name</th>
          <th width="11%">Call date</th>
          <th width="11%">Last login</th>
          <th width="11%">Caller</th>
          <th width="5%">Call reason</th>
          <th width="11%">Last deposit</th>
          <th>Deposit amount since last call</th>
          <th># deposits after last call</th>
        </tr>
      </thead>
      <tbody>
        <?
        $total_amount = 0;
        foreach ($calls as $call):
	$total_amount += $call->amount_after;
	list($null, $date, $reason) = explode("-", $call->called);
	$class = ($call->last_login < $call->unixtime) ?: "red";
	?>
	  <tr data-deposits="<?=$call->deposits_after?>" data-id="<?=$call->id?>" data-lasspand="<?=$call->unixtime?>" class="tr">
	    <td>
              <a href="<?php echo getUserBoLink($call->username) ?>">
                <?=$call->username?>
              </a>
            </td>
	    <td>
              <?=$call->fullname?>
              <?php echo !empty($call->country) ? $call->country : ''; ?>
            </td>
	      <td><?=date("d.m.y H:i", $call->unixtime)?></td>
	      <td class="<?=$class?>"><?=date("d.m.y H:i", $call->last_login)?></td>
	      <td><?=$array_callers[$call->value]?></td>
	      <td><?=$callreasons[$reason]?></td>
	      <td><?=$call->last_deposit == NULL ? '' : date("d.m.y H:i", $call->last_deposit)?></td>
	      <td><?=$call->amount_after?></td>
	      <td><?=$call->deposits_after?></td>
	  </tr>
	  <tr class="trow_info" data-userid="<?=$call->id?>"></tr>
	  <?php endforeach?>
      </tbody>
    </table>
    <br />
    <?php if ($total_amount != 0):?>
      Total sum deposit after calls within relevance period: <?=$total_amount?>
      <?endif?>
  </div>
</div>
