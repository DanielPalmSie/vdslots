<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$c = phive('Cashier');

$smith = phive()->getModule('Permission');
// Getting all the user permission and flatten in array
$groups = $smith->getUsersGroups(uid(), true);
// Determine is the current user is a normal agent or has (Head, Team Leader) super agent.
$isSuperAgent = (in_array(65, $groups) || in_array(66, $groups));

$_SESSION['paid_pending'] = array();
$micro = phive('Casino');
$table 		= empty($_GET['table']) ? 'pending_withdrawals' : $_GET['table'];
if($table == 'pending_withdrawals'){
    $transactions = phive('SQL')->shs('merge', '', null, 'pending_withdrawals')->loadArray(
        "SELECT DISTINCT
             pw.id AS id, pw.deducted_amount, pw.amount, pw.payment_method, pw.scheme AS scheme, pw.ref_code AS ref_code, pw.description, users.username, users.id AS user_id, pw.ppal_email, pw.mb_email, pw.net_account, pw.timestamp, pw.stuck,
             pw.iban, pw.bank_receiver, pw.bank_name, pw.bank_code, pw.bank_address, pw.bank_city, pw.bank_country, pw.bank_account_number, pw.swift_bic
         FROM pending_withdrawals pw, users WHERE users.id = pw.user_id AND pw.status = 'pending' AND payment_method != 'bank'"
    );
}else if($table != 'queued_transactions')
    $transactions = phive('SQL')->shs('merge', '', null, $table)->loadArray("SELECT DISTINCT $table.*, users.username FROM $table, users WHERE users.id = $table.user_id");
else
    $transactions = phive('Cashier')->getQueue();
?>
<script src="/phive/js/jquery.min.js" type="text/javascript" charset="utf-8"></script>
<script src="/phive/js/jquery.json.js" type="text/javascript" charset="utf-8"></script>
<input type="hidden" id="csrf_token" value="<?php echo $_SESSION['token'];?>"/>
<script type="text/javascript">
    // this page is including jquery.js twice, but i'm not sure if it's loaded "alone" in some cases so i'm duplicating here the code needed for Ajax POST request to work
    // See config at http://api.jquery.com/jquery.ajax/
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.getElementById('csrf_token').value
        }
        , statusCode: {
            // response 403 returned by csrf token verification for ajax calls
            403: function(response) {
                var message,json;
                // to avoid json parsing errors if something else than json is returned on 403
                try {
                    json = /json/.test(response.getResponseHeader('content-type')) ? response.responseJSON : JSON.parse(response.responseText);
                } catch(e) {
                    json = {};
                }
                if(json.error) {
                    switch(json.error) {
                        case 'invalid_origin':
                        case 'invalid_token':
                            message = json.message; break;
                        default:
                            message = 'Something went wrong.'
                    }
                }
                fancyShow(message);
            }
        }
    });
</script>
<script>
 var trans_ids = [];
 var automatic = false;
 var bind_actions = {};
 var exec_actions = {};

 function setupAction(action, func){
     var selector = "div[id^='"+action+"-']";
     if(bind_actions[selector] == 'done')
         return;
     bind_actions[selector] = 'done';
     $(selector).click(function(){
         if(exec_actions[$(this).attr('id')] == 'done')
             return;
         exec_actions[$(this).attr('id')] = 'done';
         var me 			= $(this);
         var myId 		= me.attr("id").split("-").pop();

         if(action == 'deletenomail'){
             var phpAction 	= 'delete';
             var mailAction 	= 'no';
         }else{
             var phpAction 	= action;
             var mailAction 	= 'yes';
         }

         me.html('<img src="/phive/images/ajax-loader.gif"/>');

         $.post("/phive/modules/Micro/ajax.php", {table: '<?php echo $table ?>', send_mail: mailAction, action: phpAction, id: myId, amount: $("#amount-"+myId).html()}, function(res){
             func.call(me, me, res);
         });
     });
 }

 $(document).ready(function(){

     <?php if(phive()->getSetting('q-pend') === true): ?>
     doWs('<?php echo phive('UserHandler')->wsUrl('pendingwithdrawals', false) ?>', function(e) {
         var res = JSON.parse(e.data);
         $("#pay-"+res.id).html(res.msg);
         //console.log(res);
     });
     <?php endif ?>

     setupAction('pay', function(me, res){
         <?php if(phive()->getSetting('q-pend') !== true): ?>
         if(res == "fail")
             me.html('The transaction failed.');
         else
             me.html(res);
         <?php endif ?>

         if(automatic == true)
             $("#pay-"+trans_ids.pop()).trigger('click');

         if(res != 'ok')
             me.html(res);

         me.off('click');
     });

     setupAction('deletenomail', function(me, res){
         if(res == "fail")
             me.html('The entry could not be deleted.');
         else if(res == 'ok'){
             me.html('The entry was deleted.');
             var myId 		= me.attr("id").split("-").pop();
             $("#pay-"+myId).remove();
         }else
         me.html(res);

         me.off('click');
     });

     setupAction('unverify', function(me, res){
         if(res == "fail")
             me.html('Something went wrong.');
         else if(res == 'ok'){
             me.html('The player has been unverified.');
             var myId = me.attr("id").split("-").pop();
             $("#pay-"+myId).remove();
         }else
         me.html(res);
         me.off('click');
     });


     setupAction('delete', function(me, res){
         if(res == "fail")
             me.html('The entry could not be deleted.');
         else if(res == 'ok'){
             me.html('The entry was deleted.');
             var myId 		= me.attr("id").split("-").pop();
             $("#pay-"+myId).remove();
         }else
         me.html(res);
         me.off('click');
     });

     setupAction('verify', function(me, res){
         if(res == 'ok')
             me.html('Verify mail sent.');
         else
             me.html(res);

         me.off('click');
     });

     $("#start_auto").click(function(){
         automatic = true;
         buildIds();
         $("#pay-"+trans_ids.pop()).trigger('click');
     });

     $("#pay_all").click(function(){
         doAll($(this), 'pay', ".stats_table", "/phive/modules/Micro/ajax.php", 'pay-all');
     });

     $("#stop_auto").click(function(){
         automatic = false;
     });

     $('div[id^="change39"]').click(function(){
         var myId 		= $(this).attr("id").split("-").pop();
         var amount 		= $("#amount-"+myId);
         amount.html( Math.round( amount.html() * (1 - 0.039) ) );
     });

     $('div[id^="changecustom"]').click(function(){
         var myId 		= $(this).attr("id").split("-").pop();
         $("#amount-"+myId).html( $("#customval-"+myId).val() );
     });

     //$('#majority-fraud-line').hover();

 });
</script>
<div id="doAllResult"></div>
<table class="stats_table" style="width: 1500px; margin-left: -200px;">
    <tr class="stats_header">
        <td></td>
        <td></td>
        <td>Recipient</td>
        <td>Description</td>
        <td>Amount</td>
        <td>Currency</td>
        <td>Tot. Dep. Sum</td>
        <?php if($table == 'pending_withdrawals'): ?>
            <td>Deducted</td>
            <!-- <td></td> -->
            <td>Payment Method</td>
            <td>Actions</td>
            <td>Unverify</td>
        <?php endif ?>
        <td></td>
        <td></td>
    </tr>
    <?php foreach($transactions as $t):
    $cur_user = cu($t['user_id']);
    $verified = $cur_user->getSetting('verified');
    $mail_sent = $cur_user->getSetting('verify_mail_sent');
    $currency = empty($t['currency']) ? $cur_user->getAttr('currency') : $t['currency'];
    ?>
        <?php if((empty($_GET['istype']) && empty($_GET['nottype'])) || ($_GET['istype'] == $t['transactiontype'] || ($_GET['nottype'] != $t['transactiontype'] && !empty($_GET['nottype'])))):
            $classes = phive('Cashier')->fraud->getFlags($cur_user);
            $flags = array_keys($classes);
            list($label, $class) = phive('Cashier')->getRowCssClass($cur_user, $t, 'fill-odd', $flags);
        ?>
            <tr <?php echo $class ?>
                <?php
                    $fraudFlagColor = lic('getWithdrawalFraudFlagColor', [$cur_user->data['id']], null, null, $cur_user->data['country']);
                    echo $fraudFlagColor ? "style='background-color: {$fraudFlagColor}'" : '';
                ?>
            >
                <td>
                    <?php drawFlagSpans($cur_user, $classes) ?>
                </td>
                <td>
                    <?php if(pIfExists($flags) && ($t['stuck'] != CasinoCashier::STUCK_UNKNOWN || $isSuperAgent)): ?>
                        <div id="pay-<?php echo $t['id'] ?>" style="cursor:pointer;">Pay</div>
                    <?php endif ?>
                </td>
                <td>
                    <a target="_blank" rel="noopener noreferrer" href="/admin2/userprofile/<?php echo $t['user_id'] ?>/">
                        <?php echo $t['username'] ?>
                    </a>
                </td>
                <td><?php echo $t['description'] ?></td>
                <td>
                    <div id="amount-<?php echo $t['id'] ?>">
                        <?php echo !empty($t['balance']) ? $t['balance'] : $t['amount'] ?>
                    </div>
                </td>
                <td>
                    <?php echo $currency ?>
                </td>
                <td>
                    <?php echo phive("Cashier")->getUserDepositSum($t['user_id'], "'approved','pending'") ?>
                </td>
                <?php if($table == 'pending_withdrawals'): ?>
                    <td>
                        <?php echo $t['deducted_amount'] ?>
                    </td>
                    <!--
                 <td>
                 <div id="change39-<?php echo $t['id'] ?>" style="cursor:pointer;">3.9%</div>
                 <input id="customval-<?php echo $t['id'] ?>" type="text" value="<?php echo $t['amount'] ?>" />
                 <div id="changecustom-<?php echo $t['id'] ?>" style="cursor:pointer;">Change</div>
                 </td>
                 -->
                    <td>
                        <?php
                        if (in_array($t['payment_method'], (new Mts())->getMainCcSuppliers())) {
                            echo $t['payment_method'];
                            echo "<br>Card:".$t['scheme']."<br>Ref Code:".$t['ref_code'];
                            echo "<br>Cards:";
                            foreach(Mts::getInstance($t['payment_method'], $t['user_id'])->getCards() as $c) {
                                echo "<br>Num: {$c['card_num']}, verified: {$c['verified']}";
                            }
                        } else {
                            $scheme = '';
                            if ($t['scheme'] && $t['scheme'] !== $t['payment_method']) {
                                $scheme = $t['scheme']  . ' - ';
                            }
                            echo $scheme . $t['payment_method'];
                        }
                        if($t['payment_method'] == 'neteller') {
                            echo "<br>Net acc. #: ".$t['net_account'];
                        }
                        if($t['payment_method'] == 'moneybookers') {
                            echo "<br>MB email: ".$t['mb_email'];
                        }
                        if ($t['payment_method'] == Supplier::Citadel) {
                            echo !empty($t['iban']) ? '<br>IBAN: '.$t['iban'] : '';
                            echo !empty($t['bank_account_number']) ? '<br>Account nr: '.$t['bank_account_number'] : '';
                            echo '<br>Receiver: '.$t['bank_receiver'];
                            echo '<br>Bank: '.$t['bank_name'];
                            echo !empty($t['bank_code']) ? '<br>Bank Code: '.$t['bank_code'] : '';
                            echo !empty($t['bank_clearnr']) ? '<br>Branch Code: '.$t['bank_clearnr'] : '';
                            echo !empty($t['bank_address']) ? '<br>Address: '.$t['bank_address'] : '';
                            echo '<br>City: '.$t['bank_city'];
                            echo '<br>Country: '.$t['bank_country'];
                            echo '<br>SWIFT: '.$t['swift_bic'];
                            //print_r($t);
                        }
                        ?>
                    </td>
                    <td>
                        <div id="verify-<?php echo $t['user_id'] ?>" style="cursor: pointer;">
                            <?php echo empty($verified) ? (empty($mail_sent) ? 'No - send mail' : "No - email sent: $mail_sent, send again" ) : 'Yes - send again' ?>
                        </div>
                    </td>
                    <td>
                        <div id="unverify-<?php echo $t['id'] ?>" style="cursor:pointer;">Unverify</div>
                        <?php
                        $res = phive('Cashier')->lgaWithdrawCheck($t['user_id'], phive()->hisNow(), $t['currency']);
                        if($res):
                        ?>
                            <div class="red"><?php echo $res['amount_sum'] ?></div>
                        <?php endif ?>
                    </td>
                <?php endif ?>

                <td>
                    <?php if($t['stuck'] != CasinoCashier::STUCK_UNKNOWN || $isSuperAgent): ?>
                    <div id="delete-<?php echo $t['id'] ?>" style="cursor:pointer;">Cancel</div>
                    <?php endif ?>
                </td>
                <td>
                    <?php if($t['stuck'] != CasinoCashier::STUCK_UNKNOWN || $isSuperAgent): ?>
                    <div id="deletenomail-<?php echo $t['id'] ?>" style="cursor:pointer;">Cancel, <br/>No Mail</div>
                    <?php endif ?>
                </td>
            </tr>
        <?php endif ?>
    <?php endforeach ?>
    <tr>
        <td>
            <!-- <button id="start_auto">Start Auto</button>  -->
        </td>
        <td>
            <!-- <button id="stop_auto">Stop Auto</button> -->
        </td>
        <td>
            <?php if($table != 'pending_withdrawals'): ?>
                <button id="pay_all">Pay All</button>
            <?php endif ?>
        </td>
        <td></td>
        <td></td>
    </tr>
</table>

