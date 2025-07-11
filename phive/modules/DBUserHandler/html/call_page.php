<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/m/Form.php';
use MVC\Form as F;

function getSums($users) {
  $sql = phive('SQL');
  $ins = $sql->makeIn(array_keys($users));
  $str = "
    SELECT SUM(amount) AS amount_sum, user_id, currency, c.multiplier
    FROM deposits
    LEFT JOIN currencies c ON currency = c.code
    WHERE status = 'approved' AND user_id IN($ins)
    GROUP BY user_id";
  return $sql->loadObjects($str, 'user_id');
}

function getColor($sum) {
  $sums = array(100 => "blue", 500 => "green", 1000 => "red", 10000 => "purple");
  foreach ($sums as $threshold => $color) {
    if ($sum <= $threshold) 
      return $color;
  }
  return "purple";
}

function callPartSql($md){
  return "LEFT JOIN users_comments us ON u.id = us.user_id
          LEFT JOIN users_settings us2 ON u.id = us2.user_id AND us2.setting LIKE 'called%' AND us2.created_at LIKE '%-$md%'
          LEFT JOIN users_settings us3 ON u.id = us3.user_id AND us3.setting LIKE 'called%' AND us3.created_at NOT LIKE '%-$md%'";  
}

function getEmailRegSql($md, $reg_date, $v_email = 'no'){
  $call_part = callPartSql($md);
  return "SELECT               
              u.id, 
              u.username, 
              u.country, 
              u.mobile, 
              CONCAT_WS(' ', u.firstname, u.lastname) AS fullname, 
              NULLIF(u.affe_id,0) AS aff_id, 
              us4.setting, 
              us4.value,
              us.comment AS cm, 
              us2.setting AS called,
              us3.setting AS called_before 
          FROM users u 
          LEFT JOIN users_settings AS us4 ON us4.user_id = u.id AND us4.setting = 'email_code_verified' AND us4.value = '$v_email' 
          $call_part
          WHERE DATE(u.register_date) = '$reg_date'";
}

function drawRes($res, $hline, $sums){ ?>
  <div class="col" id="day_since_register">
    <h3><?php echo $hline ?></h3>
    <ul>
      <?php
      foreach ($res as $user_id => $user) {
        $aff           = $user->aff_id !== NULL ? "A" : "";
        $cmnt          = $user->cm != NULL ? "c" : "none";
        $oddeven       = ($i % 2 === 0) ? 'fill-odd' : 'fill-even';
        $called_before = ($user->called_before === NULL) ? "no" : "";
        $sum           = empty($sums[$user_id]->amount_sum) ? 0 : $sums[$user_id]->amount_sum / $sums[$user_id]->multiplier / 100;
        
        if ($user->called === NULL) {
          $i++;
          $sum     = ($sum != 0) ? $sum : '';
          $deposit = ($sum != '') ? "1" : "0";
          $color   = ($sum != 0) ? getColor($sum) : "default";
          $sum     = (p('call_list.deposit.sum')) ? $sum : "";
          echo '<li data-country="' . $user->country . '" data-deposit="' . $deposit . '" data-phonenumber="' . $user->mobile . '" class="' . $oddeven . '" id="' . $user->id . '"><span class="userinfo">[' . $user->country . '] <a href="/admin/userprofile/?username=' . $user->username . '">' . $user->username .
               '</a> <span class="' . $color . '">' . $user->fullname . '</span> ' . $sum . '</span><span class="cmnt_' . $cmnt . '"><img src="'. getMediaServiceUrl() . '/file_uploads/cvmdmsg.png" alt="comments" /></span><span class="called_before_' . $called_before . '"><img src="'.getMediaServiceUrl().'/file_uploads/topmost-menu-phone.png"/></span><span class="right"><span class="aff_' . $aff . '">' . $aff . '</span><span class="plus">+</span></span></li>';
        }
      }
      ?>
    </ul>
  </div>
  <?php
}

$sql       = phive('SQL');
$md        = date('m-d');
$ym        = date("Y") - 1;
$yesterday = phive()->yesterday();
$today     = phive()->today();
$countries = array("ALL" => "All");
$filter    = array(0 => "Show all players", 1 => "Show players without deposit", 2 => "Show deposit players");
$filter2   = array(
  "birthday" => "Players with birthday today", 
  "inactive_60days" => "Inactive for 60 days", 
  "year_since_register" => "Registered a year ago", 
  "reg_yesterday_no_email" => "Registered yesterday but have not confirmed email",
);

foreach (array(1,3,6,9,12,15) as $i => $days) 
  $filter2[$days."_days_since_register_email"] = "Registered $days days ago and has verified email";

$call_part = callPartSql($md);
  
$action = $_REQUEST['action'];
if(!empty($action)){
  switch($action){
    case 'birthday':
      $hline = "Birthday boys and girls";
      $q = "SELECT u.id, u.username, u.country, u.mobile, CONCAT_WS(' ', u.firstname, u.lastname) AS fullname, NULLIF(u.affe_id,0) AS aff_id, us.comment AS cm, us2.setting AS called,us3.setting AS called_before
            FROM users u
            $call_part
            WHERE u.dob LIKE '%$md' AND u.active = 1 AND u.mobile != '' GROUP BY id ORDER BY u.username";
      break;
    case 'inactive_60days':
      $hline = "Did not deposit in 60 days";
      $d60ago = phive()->modDate('', '-60 day');
      $q = "SELECT u.id, u.username, u.country, u.mobile, CONCAT_WS(' ', u.firstname, u.lastname) AS fullname, NULLIF(u.affe_id,0) AS aff_id, ct.timestamp, us.comment AS cm,us2.setting AS called,us3.setting AS called_before
            FROM users u
            LEFT JOIN cash_transactions ct ON u.id = ct.user_id
            $call_part
            WHERE u.last_login LIKE '$d60ago%' AND u.active = 1 AND u.mobile != '' AND ct.transactiontype=3 AND ct.timestamp LIKE '$d60ago%' GROUP BY id ORDER BY u.username";
      break;
    case 'year_since_register':
      $hline = "A year since registration";
      $q = "SELECT u.id, u.username, u.country, u.mobile, CONCAT_WS(' ', u.firstname, u.lastname) AS fullname, NULLIF(u.affe_id,0) AS aff_id, us.comment AS cm, us2.setting AS called,us3.setting AS called_before
            FROM users u
            $call_part
            WHERE DATE(u.register_date) = '$ym-$md' AND u.active = 1 AND u.mobile != '' GROUP BY id ORDER BY u.username";
      break;
    case 'reg_yesterday_no_email':
      $hline = "Players registered yesterday that haven't confirmed their email";
      $q = getEmailRegSql($md, $yesterday);
      break;
    default:
      if(strpos($action, 'days_since_register_email') !== false){
        $days = array_shift(explode('_', $action));
        $q = getEmailRegSql($md, phive()->modDate('', "-$days day"), 'yes');
      }
      $hline = "$days days since registration with verified email";
      break;
  }
  //echo $q;
  $res = phive('SQL')->loadObjects($q, 'id');
  $sums = getSums($res);
  if($_REQUEST['country'] != 'ALL')
    $res = array_filter($res, function($u) { return $u->country == $_REQUEST['country']; });
  
  if(!empty($_REQUEST['deposited'])){
    $res = array_filter($res, function($u) use ($sums) {
      $deposited = (int)$_REQUEST['deposited'];
      if($deposited === 1 && empty($sums[$u->id]))
        return true;
      if($deposited === 2 && !empty($sums[$u->id]))
        return true;
      return false;
    });
  }
  drawRes($res, $hline, $sums);
  exit;
}
$countries = phive('Cashier')->getBankCountries('', true);
?>
<script type="text/javascript" src="/phive/js/jquery.hovercard.min.js"></script>  
<script>

 function initCallJs(){
   var arr = [];
   var phtml = '<span class="call">Called</span><span class="comment">Comment</span><span class="phnrnfunc">Wrong number</span><span class="emailed">Emailed</span><span class="callagainfunc">Call again</span><span class="noanswer">No answer</span><span class="profile">Profile</span><span class="reward">Reward</span><span class="skype">Skype call</span><br /><br/><br/><span class="userinfo_hover">';
   var ahtml = '</span>';
   $('.col').on('click','.call', function(){
     var reason=$(this).closest(".col").attr("id");
     var c=confirm('Confirm called, this will hide the player from the list?');
     if (c==true) {
       var id=$(this).closest("li").attr("id");
       $.get("/jsr/setcalled/"+id+"/"+reason+"/", function (data) {
         $("#"+id).remove();

       });
     }
   });

   $('.col').on('click','.callagainfunc', function(){
     var c=confirm('Mark to be called again?');
     if (c==true) {
       var id=$(this).closest("li").attr("id");
       $.get("/jsr/markcallagain/"+id+"/", function (data) {
         $("#"+id).remove();
       });
     }
   });
   $('.col').on('click','.noanswer', function(){
     var c=confirm('Did not answer?');
     if (c==true) {
       var id=$(this).closest("li").attr("id");
       $.get("/jsr/marknoanswer/"+id+"/", function (data) {
       });
     }
   });
   $('.col').on('click','.phnrnfunc', function(){
     var c=confirm('Mark phone number not working and hide?');
     if (c==true) {
       var id=$(this).closest("li").attr("id");
       $.get("/jsr/setnfuncnumber/"+id+"/", function (data) {
         $("#"+id).remove();

       });
     }
   });

   $('.col').on('click','.emailed', function(){
     var c=confirm('Mark player as emailed?');
     if (c==true) {
       var id=$(this).closest("li").attr("id");
       $.get("/jsr/setemailed/"+id+"/", function (data) {
       });
     }
   });

   $('.col').on('click','.profile', function(){
     var target=$(this).closest("li").find("a").attr("href");
     window.open(target);
   });

   $('.col').on('click','.reward', function(){
     var id=$(this).closest("li").attr("id");
     var target='/admin/addreward/?user_id='+id;
     window.open(target);
   });


   $('.col').on('click','.comment', function(){
     var c=prompt('Add comment:');
     if (c.length >5) {
       var _this=$(this);
       var id=_this.closest("li").attr("id");
       $.post("/jsr/postcomment/"+id+"/", {comment: c}).done(function(data){
         _this.closest("li").find(".cmnt_none").toggle();
         delete arr[id];
       });
     }

   });

   $('.plus').hovercard({
     detailsHTML: '<p></p>',
     openOnLeft: true,
     width: 490,
     onHoverIn: function () {
       var _this=$(this)
         var id=$(this).closest("li").attr("id");
       var xurl = '/jsr/getaname/'+id+'/';
       var _this = $(this).find("p");
       if (!arr[id]) {
         $.ajax({
           url: xurl,
           type: 'GET',
           dataType: 'html',
           beforeSend: function () {
             _this.prepend('<p class="loading-text">Loading data...</p>');
           }
         })
         .done(function (data) {
            arr[id] = phtml + _this.closest("li").find(".userinfo").html() + ahtml + 'Phone number : ' + _this.closest("li").data("phonenumber") + "<br />" + data;
            _this.empty();
            _this.html(arr[id]);
          })
          .always(function () {
            $('.loading-text').remove();
          });
       } else {
         _this.html(arr[id]);
       }
     }
   });

   $('.col').on('click', '.skype', function(){
     var nr = $(this).closest("li").data("phonenumber");
     window.open('skype:+'+nr+'?call');
   });
   
 }

 function doGetPlayers(){
   var country   = $("#f_country option:selected").val();
   var deposited = $("#f_show option:selected").val();
   var action     = $("#f_show2 option:selected").val();
   $.post('/phive/modules/DBUserHandler/html/call_page.php', {country: country, deposited: deposited, action: action}, function(res){
     $("#call-res").html(res);
     initCallJs();
   });
 }
 
 $(document).ready(function(){
   $('#f_country').change(function(){ doGetPlayers(); });
   $('#f_show').change(function(){ doGetPlayers();});
   $('#f_show2').change(function(){ doGetPlayers(); });   
 });
</script>
<style type="text/css">
 .container {display:flex;height:100%;}
 .col {width:33%;margin:0 auto;}
 .container ul {list-style:none;margin:0 0 0 0;padding:0 0 0 0;}
 .container li {color:#000000;padding:5px 0 15px 0;margin:0 0 0 0;}
 .act {background:#cc0000;}
 .right {float:right;}
 .aff_A {background:#000;color:#aaa;margin:5px 5px 5px 0;padding:2px 2px 2px 2px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .aff_named {background: #aaa;}
 .cmnt_c {}
 .called_before_no {display:none;}
 .cmnt_none {display:none;}
 .plus {cursor:pointer;z-index:2;background:#fad336;color:#000;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .call {cursor:pointer;position:absolute;top:2px;right:35px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .skype {cursor:pointer;position:absolute;top:38px;right:155px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .comment {cursor:pointer;position:absolute;top:2px;right:86px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .phnrnfunc {cursor:pointer;position:absolute;top:2px;right:155px;background:#aa0000;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .callagainfunc {cursor:pointer;position:absolute;top:38px;right:225px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .profile {cursor:pointer;position:absolute;top:38px;right:35px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .reward {cursor:pointer;position:absolute;top:38px;right:86px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .emailed {cursor:pointer;position:absolute;top:38px;right:296px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .noanswer {cursor:pointer;position:absolute;top:38px;right:366px;background:green;color:#ffffff;padding:5px 5px 5px 7px;border-radius:7px;-moz-border-radius: 7px;-khtml-border-radius: 7px;-webkit-border-radius: 7px;}
 .userinfo_hover {position:absolute;left:1px;top:1px;font-size:14px;}
 .red {color:#cc0000;}
 .green {color:#00cc00;}
 .blue {color:#0000cc;}
 .purple {color:#aa00aa;}
 label {padding:10px 0 10px 0;display:inline-block;width:150px;}
</style>
<div class="pad10">
  Total deposit <font class="blue"><=100</font> <font class="green"><= 500</font> <font class="red"><=1000</font> <font class="purple">1000+</font>
  <br /><img src="<?php echo getMediaServiceUrl(); ?>/file_uploads/topmost-menu-phone.png"/>has been called before <img src="<?php echo getMediaServiceUrl(); ?>/file_uploads/cvmdmsg.png" alt="comments" />has comments <font class="aff_A">A</font>has affiliate
  <div class="selection">
    <p>
      <?php dbSelect("f_country", array_diff_key(phive('Cashier')->displayBankCountries($countries), phive('Config')->valAsArray('countries', 'block')), $_POST['country'], array('ALL', 'All Countries')) ?>
      <br/>
      <?=F::labelSelectList("Show", 'f_show', $filter)?><br />
      <?=F::labelSelectList("Show", 'f_show2', $filter2)?><br />
    </p>
  </div>
  <div id="call-res" class="container">
  </div>
</div>
