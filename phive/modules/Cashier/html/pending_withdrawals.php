<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../SQL/html/db_view.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

// TODO henrik is this even used anymore? If not remove.

$c 	= phive('Cashier');

$_GET['method'] = 'bank';

if(phive()->moduleExists('Site'))
    $GLOBALS['site'] = phive('Site');

if(isset($_POST['approve'])){
    $pend = $c->getPending($_POST['id']);
    
    //if($pend['payment_method'] == "neteller"){
        //phive('Neteller')->test = true;
    //    $net = phive('Neteller')->approvePending($_POST['id'], 0.98, false);
    //}

    $c->approvePending($_POST['id']);

    $pend_id = (int)$_POST['id'];
    $user = cu($pend['user_id']);
    $actor = cu();
    phive('SQL')->sh($pend, 'user_id', 'pending_withdrawals')->updateArray('pending_withdrawals', array('approved_by' => $actor->getId()), array('id' => $pend['id']));
    phive('UserHandler')->logAction($user, " approved withdrawal by {$pend['payment_method']} of {$pend['amount']} with internal id of $pend_id for user {$user->getUsername()}", 'approved-withdrawal', true, $actor);

} else if(isset($_POST['disapprove'])){
    $c->disapprovePending($_POST['id'], false);
    $pend = $c->getPending($_POST['id']);
    $user = cu($pend['user_id']);
    
    if($pend['payment_method'] == "bank"){
        
        $replacers = phive('MailHandler2')->getDefaultReplacers($user);
        $replacers["__METHOD__"] = "bank";
        $replacers["__AMOUNT__"] = printMoney($_POST['amount']);
        phive('MailHandler2')->sendMail("cashier.withdraw.disapproved.bank",$user,$replacers);
        
    } else if($pend['payment_method'] == "neteller"){
        
        $replacers = phive('MailHandler2')->getDefaultReplacers($user);
        $replacers["__METHOD__"] = "neteller";
        $replacers["__AMOUNT__"] = printMoney($_POST['amount']);
        phive('MailHandler2')->sendMail("cashier.withdraw.disapproved.neteller",$user,$replacers);
        
    }
}else if(!empty($_POST['verify'])){
    phive('Cashier')->sendVerifyReminder($_POST['user_id']);
    echo "Verification email sent.";
}

$pend_start 	= 0;
$method 		= null;
$method_txt 	= "both";
$status 		= "pending";
$status_txt 	= "pending";
if(isset($_GET['method'])){
    if($_GET['method'] != "both")
        $method = $_GET['method'];
    
    $method_txt = $_GET['method'];
    
} if(isset($_GET['status'])){
    if($_GET['status'] != "all")
        $status = $_GET['status'];
    
    $status_txt = $_GET['status'];
    
} if(isset($_GET['pend_start']))
    $pend_start = $_GET['pend_start'];

$top 				= array("id","user_id","amount", 'currency',"payment_method","status","nolink" => "View");
$default_order_by 	= "timestamp";
$default_order 		= "ASC";
$res_per_page 		= 50;
$page 				= getPage();
$total 				= $c->getTotalPendings($status,$method);
$pages 				= 1 + (float)($total -1)/$res_per_page;
$order 				= getOrder($default_order);
$order_by 			= getOrderBy($top,$default_order_by);
$start 				= ($page-1)*$res_per_page;
$db_data 			= $c->getPendings($start,$res_per_page,$order_by,$order,$status,$method);
if(phive()->moduleExists('Site'))
    $db_data 			= $GLOBALS['site']->filterUsers($db_data, $_GET['site']);

$gets 				= "method=$method_txt&status=$status_txt";
$data 				= array();
foreach ($db_data as $d) {
    $data[] = array(array("text" => $d['id']),
		    array("text" => $d['user_id']." (".$d['username'].")"),
		    array("text" => $d['amount']),
		    array("text" => $d['currency']),
		    array("text" => $d['payment_method']),
		    array("text" => $d['status']),
		    array("text" => "View","link" => "?approve=".$d['id']."&$gets"));
}
$all = array("width" 		=> 680,
	     "default_order" => "DESC",
	     "order_by" 		=> $order_by,
	     "order" 		=> $order,
	     "page" 			=> $page,
	     "pages" 		=> $pages,
	     "top" 			=> $top,
	     "gets" 			=> $gets,
	     "data" 			=> $data);

if(isset($_GET['approve'])){
    for ($i =0; $i <= count($db_data); $i++){
        if($db_data[$i]['id'] == $_GET['approve']){
            $pend_start = $i;
            break;
        }
    }
    if(isset($_GET['next'])){
        if ($pend_start == (count($db_data) - 1)){
            $pend_start = 0;
        }
        else{
            ++$pend_start;
        }
        printPending($db_data[$pend_start]['id'],$gets, 1);
    }
    elseif(isset($_GET['prev'])){
        if ($pend_start == 0){
            $pend_start = (count($db_data) - 1);
        }
        else{
            --$pend_start;
        }
        printPending($db_data[$pend_start]['id'],$gets, 1);
    }
    else{
        printPending($db_data[$pend_start]['id'],$gets, 1);
    }
}
else{
    printPending($db_data[$pend_start]['id']);
}

if(!empty($_POST['csvsubmit'])){
    include_once '../../Raker/Parser.php';
    $parser = new Parser();
    $rows 	= $parser->csvToArr(';', file_get_contents($_FILES['csvfile']['tmp_name']));
    foreach($rows as $r){
        if(is_numeric($r['user_id']))
            $user = cu($r['user_id']);
        else
            $user = phive('UserHandler')->getUserByUsername($r['user_id']);
        
        if(!empty($user)){
            $c->depositToUser($user, $r['amount'], $r['description']);
            echo "Transferred {$r['amount']} to ".$user->getUsername()." <br>";
        }else
        echo "Wrong user id / username: {$r['user_id']} <br>";
    }
}

if(!empty($_POST['file_sdate']) && !empty($_POST['file_edate']))
    phive('Cashier')->getShbFile($_POST['file_sdate'], $_POST['file_edate']);

if(isset($_GET['delete']))
    unlink($_GET['delete']);

/*
   if($c_class == 'Cashier'){
   printFilterForm($method_txt, $status_txt);
   printNetCsv();
   }
 */

printBankFileForm();

?>
<table class="stats_table">
    <tr class="stats_header">
        <td></td>
        <td>ID</td>
        <td>User ID</td>
        <td>Amount</td>
        <td>Currency</td>
        <td>Status</td>
        <td></td>
    </tr>
    <?php foreach($db_data as $r): ?>
        <tr>
            <td>
                <?php drawFlagSpans($r['user_id']) ?>
            </td>
            <td><?php echo $r['id'] ?></td>
            <td><?php echo $r['user_id'] ?></td>
            <td><?php echo $r['amount'] ?></td>
            <td><?php echo $r['currency'] ?></td>
            <td><?php echo $r['status'] ?></td>
            <td><a href="<?php echo "?approve={$r['id']}&method=bank&status={$r['status']}" ?>">View</a></td>
        </tr>
    <?php endforeach ?>
</table>
<?php

//printTable($all);

function printBankFileForm(){
?>
    <h2>Get Bank Transfer File:</h2>
    <form method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        Start date (YYYY-MM-DD): <input type="text" name="file_sdate"/><br>
        End date (YYYY-MM-DD): <input type="text" name="file_edate"/><br>
        <input type="submit" value="Submit">
    </form>
<?php
}

function printNetCsv(){
?>
<br>
<h2>Upload CSV (internal cash transfers):</h2>
<form enctype="multipart/form-data" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr>
      <td>
	<input type="file" name="csvfile" />
      </td>
      <td align="right">
	<input type="submit" name="csvsubmit" value="Upload CSV"/>
      </td>
    </tr>
  </table>
</form>
<br>
<?php
}

function printFilterForm($method,$status){
  function printSelect($txt,$name,$data,$selected){
?>
<?php echo $txt; ?>
<select name="<?php echo $name; ?>">
  <?php foreach ($data as $d): ?>
    <option value="<?php echo $d; ?>" <?php if($selected == $d) echo 'selected="selected"'; ?>>
      <?php echo $d; ?>
    </option>
  <?php endforeach ?>
</select>
<?php
}
?>
<br />
<form  method="get" accept-charset="utf-8">
  <table>
    <tr>
      <td>
	<?php printSelect("Method:","method",array("both","neteller","bank"),$method); ?>
      </td>
      <td>
	<?php printSelect("Status:","status",array("all","pending","approved","disapproved"),$status); ?>
      </td>
      <?php if(phive()->moduleExists('Site')): ?>
	<td>
	  Site:
	  <?php rblSelect('site', $GLOBALS['site']->sitesByUser() ); ?>
	</td>
      <?php endif ?>
      <td>
	<input type="submit" value="Filter">
      </td>
    </tr>
  </table>
</form>
<br />
<?php
}

function printPending($id,$gets = null,$pend_start=0){
  $c 		= phive('Cashier');
    $pend 	= $c->getPending($id);
    $cur_user 	= cu($pend['user_id']);
    $def_cur    = phive('Currencer')->getSetting('base_currency');
  if($pend):
?>
<p>
  <?php if ($pend_start > 0): ?>
    <a href="?<?php echo $gets."&approve=".$id . "&prev=1"?>">Prev</a>
  <?php endif ?>
  <a href="?<?php echo $gets."&approve=".$id . "&next=1"?>">Next</a>
  
  <table>
    <?php if ($pend['payment_method'] == "bank"): 
				         $dark = "#bbbbbb";
    $light = "#eeeeee";
    $color = $dark;
    ?>
      <?php foreach ($pend as $k => $v): 
				  if(substr($k,0,3) != "net"):?>
	<?php if ($k == "user_id"): 
			$user = cu($v);
	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>"><?php echo "user"; ?>:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php profileLink($user->getUsername(), $v) ?> (<?php echo $v; ?>)</td>
	  </tr>
	<?php else: ?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>"><?php echo $k.(($k == 'bank_clearnr') ? ' (BSB number for Australia)' : '')?>:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo $v; ?></td>
	  </tr>	
	<?php endif ?>
	<?php if ($k == "amount"): 
			$color 		= ($color == $dark)?$light:$dark;
	$mult 		= phive('Currencer')->getMultiplier("EUR");
	$cur_amount = $v / 100;
	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>">Amount (<?php echo $pend['currency'] ?>):</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo $cur_amount ?></td>
	  </tr>
	<?php endif ?>
	<?php 
	$color = ($color == $dark)?$light:$dark;
	endif; ?>
      <?php endforeach ?>
      
    <?php elseif($pend['payment_method'] == "neteller"): 
				            $dark = "#bbbbbb";
    $light = "#eeeeee";
    $color = $dark;
    ?>
      <?php foreach ($pend as $k => $v): 
				  if(substr($k,0,4) != "bank" && $k != "iban" && $k != "swift_bic"):?>
	<?php if ($k == "user_id"): 
			$user = cu($v);
	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>"><?php echo "user"; ?>:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo $user->getUsername(); ?> (<?php echo $v; ?>)</td>
	  </tr>
	<?php else: ?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>"><?php echo $k; ?>:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo $v; ?></td>
	  </tr>	
	<?php endif ?>
	<?php if ($k == "amount"): 
			$color = ($color == $dark)?$light:$dark;
	$mult = phive('Currencer')->getMultiplier("EUR");
	$cur_amount = sprintf("%.2f",($mult*$v*0.98)); 
	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>">Amount - 2%:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo sprintf("%.2f",($v*0.98)); ?></td>
	  </tr>
	  <?php $color = ($color == $dark)?$light:$dark;	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>">Amount <?php cs(true) ?>:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo sprintf("%.2f",($mult*$v)); ?></td>
	  </tr>
	  <?php $color = ($color == $dark)?$light:$dark;	?>
	  <tr >
	    <td style="background-color:<?php echo $color; ?>">Amount <?php cs(true) ?> -2%:</td>
	    <td style="background-color:<?php echo $color; ?>"><?php echo $cur_amount ?></td>
	  </tr>
	<?php endif ?>
	<?php 
	$color = ($color == $dark)?$light:$dark;
	endif; ?>
      <?php endforeach ?>
    <?php endif; ?>
  </table>
  <?php if ($pend['status'] == "pending"): ?>
    <table>
      <tr>
	<td style="vertical-align: top;">
	  <form action="<?php echo phive('Pager')->getPath()."?$gets&pend_start=$pend_start"; ?>" method="post" accept-charset="utf-8">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	    <input type="hidden" name="id" value="<?php echo $pend['id']; ?>"/>
	    <input type="hidden" name="amount" value="<?php echo $cur_amount ?>"/>
	    <input type="hidden" name="disapprove" value="" id="disapprove"/>
	    <p><input type="submit" value="Disapprove"></p>
	  </form>
	</td>
	<td>&nbsp;</td>
	<td style="vertical-align: top;">
	  <form action="<?php echo phive('Pager')->getPath()."?$gets&pend_start=$pend_start"; ?>" method="post" accept-charset="utf-8">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	    <input type="hidden" name="id" value="<?php echo $pend['id']; ?>"/>
	    <input type="hidden" name="amount" value="<?php echo $cur_amount ?>"/>
	    <input type="hidden" name="approve" value="" id="approve"/>
	    <?php if(phive()->moduleExists('DBUserHandler')): ?>
	      Bank Fee in <strong>Cents</strong>:
	      <input type="text" name="real_cost" value="" />
	    <?php endif ?>
	    <p><input type="submit" value="Approve"></p>
	  </form>
	</td>
	<td>
            <strong>Amount in <?php echo $def_cur ?> cents: <?php echo chgCents($pend['currency'], $def_cur, $pend['amount']) ?> </strong>
        </td>
	<td style="vertical-align: top;">
	  <form action="" method="post" accept-charset="utf-8">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	    <input type="hidden" name="user_id" value="<?php echo $pend['user_id'] ?>"/>
	    <input type="hidden" name="verify" value="yes" />
	    <p><input type="submit" value="Send verification email"></p>
	  </form>
	  <?php if($cur_user->getSetting('verified') != 1): ?>
	    <?php echo 'Not verified, verification email sent: '.$cur_user->getSetting('verify_mail_sent') ?>
	  <?php endif ?>
	</td>
	<td>&nbsp;</td>
	<td style="vertical-align: top;">
          <script>
           $(document).ready(function(){
             $("div[id^='unverify-']").click(function(){
               var me 	= $(this);
               var pid 	= me.attr("id").split("-").pop();     
               mgAjax({action: "unverify", id: pid}, function(ret){
                 me.html(ret);
               });
             });
           });
          </script>
          <div id="unverify-<?php echo $pend['id'] ?>" style="cursor:pointer;">Unverify</div>
          <?php
          $res = phive('Cashier')->lgaWithdrawCheck($pend['user_id'], phive()->hisNow(), $pend['currency']);
          if($res):
          ?>
          <div class="red"><?php echo $res['amount_sum'] ?></div>
  <?php endif ?>
        </td>
      </tr>
    </table>
    <br/>
  <?php endif ?>
  <?php
  endif;
  }
  ?>
