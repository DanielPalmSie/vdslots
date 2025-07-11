<?php
ini_set("memory_limit", "300M");
ini_set("max_execution_time", "30000");
require_once __DIR__ . '/../../../admin.php';
function populateTriggers($mh, $selected = ''){ ?>
<option value="">Choose</option>
<?php foreach($mh->getMails() as $trigger): ?>
  <option value="<?php echo $trigger ?>" <?php if($selected == $trigger) echo "selected=\"selected\"" ?>><?php echo $trigger ?></option>
<?php endforeach ?>
<?php
}

function getQuery(){
  if(!isset($_SESSION['last_search'])){
    trigger_error("No search exists!");
    return null;
  }
  return $_SESSION['last_search'];
}

function countUsers(){ return phive('SQL')->getValue("SELECT COUNT(*) FROM (".$_SESSION['last_search'].") AS user_table"); }

function sendMailToUsers($mh, $raw = true)
{
    phive('SQL')->query(getQuery());
    $raw_users = [];
    while ($user_raw = phive('SQL')->fetch()) {
        $raw_users[] = $user_raw;
    }
    $raw_users = phive('MailHandler2')->filterMarketingBlockedUsers($raw_users);
    foreach ($raw_users as $user_raw) {
        $html = '';

        if ($mh->getSetting('domain') != null && $_POST['unsubscribe_link'] == 1) {
            $html = $mh->getUnsubExtra($user_raw['email'], $mh->getSetting('domain'), $_POST['trigger']);
        }

        $u = cu($user_raw['id']);
        $html = rep($_POST['content'] . $html, $u);
        $u->marketing_blocked = false;
        if ($raw) {
            $mh->sendMailFromString($html, $_POST['subject'], $u, $_POST['language'], $mh->getDefaultReplacers($u), null, null, null, null, 1);
            $msg = "System sent mail with subject: {$_POST['subject']}";
        } else {
            $mh->sendMail($_POST['trigger'], $u, $mh->getDefaultReplacers($u), null, null, null, null, null, 1);
            $msg = "System sent mail with trigger: {$_POST['trigger']}";
        }

        if (phive()->moduleExists('DBUserHandler'))
            phive('UserHandler')->logAction($u, $msg, "mass_mail", false, $admin);
    }

    setDefCur();

    echo countUsers();
}

/**
 * Send template email to the logged in user using default replacers,
 * and __REPLACER-PLACEHOLDER__ for the specific ones.
 * @param $mh MailHandler2
 */
function sendToLoggedInUser($mh)
{
    $user = cu();

    if(empty($user)) {
        return false;
    }

    $html = '';
    $domain = $mh->getSetting('domain');
    if ($domain != null && $_POST['unsubscribe_link'] == 1) {
        $mh->getUnsubExtra($user['email'], $domain, $_POST['trigger']);
    }

    $html = rep($_POST['content'] . $html, $user);
    /*
        Get all replaces (pattern __REPLACERS__) from the email html content
        create an array with __REPLACER__ as key and __REPLACER-PLACEHOLDER__ as value
        and merge it with default values from getDefaultReplacers().

        This is need for replacers that are specifict to some emaills like __BALANCE__
    */

    preg_match_all('/(?<replacer>__[a-zA-Z\d]*__)/',$html,$matches);
    $replacers = array_merge(
        array_combine(
                $matches['replacer'],
                array_map(function ($replacer) {
                    return substr($replacer,0, -2) . "-PLACEHOLDER__";
                }, $matches['replacer'])
        ),
        $mh->getDefaultReplacers($user)
    );

    $mh->sendMailFromString($html, $_POST['subject'], $user, $_POST['language'], array_merge($replacers, $replacers), null, null, null, null, 1);
    $msg = "System sent mail with subject: {$_POST['subject']}";

    if (phive()->moduleExists('DBUserHandler')) {
        phive('UserHandler')->logAction($user, $msg, "test_mail", false, cu());
    }

    return true;
}

$mh = phive('MailHandler2');

$_POST['trigger'] = trim($_POST['trigger']);

if(!empty($_POST['from_name']))
  $mh->from_name = $_POST['from_name'];

if(!empty($_POST['from_mail']))
  $mh->from_mail = $_POST['from_mail'];

$admin = cu();

switch($_REQUEST['action']){
  case 'new':
    $mh->addMail($_POST['trigger'], $_POST['subject'], $_POST['content'], '', $_POST['language']);
    echo "ok";
    break;
  case 'edit':
    $mh->editMail($_POST['trigger'], $_POST['subject'], $_POST['content'], '', $_POST['language']);
    echo "ok";
    break;
  case 'get-mail':
    echo json_encode($mh->getRawStrings($_POST['trigger'], $_POST['language']));
    break;
  case 'populate-triggers':
    populateTriggers($mh, trim($_POST['trigger']));
    break;
  case 'send-preflang-mail':
    sendMailToUsers($mh, false);
    break;
  case 'send-mail':
    sendMailToUsers($mh, true);
    break;
  case 'send-to-me':
    echo sendToLoggedInUser($mh) ? 'ok' : 'error';
    break;
  case 'delete':
    $mh->purgeMail($_POST['trigger']);
    echo "ok";
    break;
}

if(!empty($_REQUEST['action']))
  exit;

$replacers 		= $mh->getDefaultReplacers(cu());
?>
<script type="text/javascript" src="/phive/js/ckeditor/ckeditor.js"></script>
<script>

 jQuery(document).ready(function(){

   var phpScript = '/phive/modules/MailHandler2/html/mailmanager.php';

   var ck = CKEDITOR.replace('content',{
     skin : 'kama',
     height: 400
   });

   var action = '';

   var getEmail = function(){
     $.post(phpScript, {action: "get-mail", trigger: $("#editmail_name_input").val(), language: $("#lang_input").val()}, function(res){
       $("#subject_input").val(res.subject || '');
       ck.setData(res.content);
     }, "json");
   }

   function switchToEdit(){
     action = 'edit';
     $("#editarea").show();
     $("#editmail_name_label").show();
     $("#editmail_name_input").show();
     $("#newmail_name_label").hide();
     $("#newmail_name_input").hide();
   }

   function switchToNew(){
     action = 'new';
     $("#editarea").show();
     $("#editmail_name_label").hide();
     $("#editmail_name_input").hide();
     $("#newmail_name_label").show();
     $("#newmail_name_input").show();
     ck.setData('');
     $("#subject_input").val('');
   }

   function getTriggerVal(){
     return action == 'new' ? $("#newmail_name_input").val() : $("#editmail_name_input").val();
   }

   function validateEmailFields(){
     err = '';
     if(getTriggerVal() == '')
     err = 'Email needs a trigger.';
     else if($("#subject_input").val() == '')
     err = 'Email needs a subject.';
     else if(ck.getData() == '')
     err = 'Email needs content.';
     return err;
   }

   function getSaveSendOptions(actionVal){
     return {
       action: actionVal,
       trigger: getTriggerVal(),
       subject: $("#subject_input").val(),
       language: $("#lang_input").val(),
       from_name: $("#from_name").val(),
       from_email: $("#from_email").val(),
       unsubscribe_link: $("#unsubscribe_link").val(),
       content: ck.getData()
     };
   }

   function sendMail(action){
     var err = validateEmailFields();
     if(err != '')
     alert(err);
     else{
       $("#loader").show();
       $.post(phpScript, getSaveSendOptions(action), function(res){
	 if(res != 'fail')
	 $("#msg-area").html("Mail was sent to " + res + " users.");
	 $("#loader").hide();
       });
     }
   }

   function refreshTriggerDropdown(){
     $.post(phpScript, {action: "populate-triggers", trigger: getTriggerVal()}, function(res){
       $("#editmail_name_input").html(res);
     });
   }

   $("#new_mail").click(function(){
     switchToNew();
   });

   $("#edit_mail").click(function(){
     switchToEdit();
   });

   $("#lang_input").change(function(){
     if(action == 'edit')
     getEmail.call();
   });

   $("#replacer_input").change(function(){
     ck.insertText( $(this).val() );
   });

   $("#editmail_name_input").change(getEmail);

   $("#save_mail").click(function(){
     var err = validateEmailFields();
     if(err != '')
     alert(err);
     else{
       $.post(phpScript, getSaveSendOptions(action), function(res){
	 if(res == 'ok'){
	   $("#msg-area").html("Mail successfully saved.");
	   if(action == 'new'){
	     switchToEdit();
	     refreshTriggerDropdown()
	   }
	 }
       });
     }
   });

 $("#send_to_me").click(function() {
     var err = validateEmailFields();
     if (err != '') {
        alert(err);
     } else{
         $.post(phpScript, getSaveSendOptions('send-to-me'), function(res){
             if(res == 'ok'){
                $("#msg-area").html("Mail successfully sent to you.");
             } else {
                 mboxMsg("There was an error sending the email.", false, '', 360, true, false, "Error");
             }
         });
     }
 });

   $("#delete_mail").click(function(){
     var err = validateEmailFields();
     if(err != '')
     alert(err);
     else{
       $.post(phpScript, getSaveSendOptions('delete'), function(res){
	 if(res == 'ok'){
	   $("#msg-area").html("Mail successfully deleted.");
	   switchToNew();
	   refreshTriggerDropdown();
	 }
       });
     }
   });

   $("#send_mail").click(function(){ sendMail('send-mail'); });
   $("#send_preflang_mail").click(function(){ sendMail('send-preflang-mail'); });
 });
</script>
<div class="pad-stuff-ten">
  <table>
    <tr>
      <td>
	<p>
	  Example of advanced replacing: "Hi, this is a great jackpot: __micro_jps||jp_id||15||jp_name__ with value: __micro_jps||jp_id||15||jp_value||currency__"
	</p>
	<p>
	  <strong>micro_jps</strong> is the table to query, <strong>jp_id</strong> the column to use fo the query, <strong>15</strong> is the value to use for the query,
	  <strong>jp_value</strong> is the column to fetch content from, <strong>currency</strong> is the formatting to use. The above then means fetch the jackpot with
	  jp id 15 from micro_jps, display the jackpot value with currency formatting. If there is no formatting the content will be displayed as is.
	</p>
	<p>
	  The current poker bad beat jackpot can be inserted like this: --POKERBADBEAT--
	</p>
	<p>
	  <strong>Deleting an email will delete that email in all languages, regardless of which language you've currently chosen.</strong>
	</p>
	<input type="button" id="new_mail" value="Create New Mail"/>
	<input type="button" id="edit_mail" value="Use/Edit Existing Mail"/>
      </td>
    </tr>
    <?php if(isset($_REQUEST['to_last_search'])): ?>
      <tr>
	<td>
	  <p>
	    <strong>Sending mail to <?php echo countUsers() ?> users.</strong>
	  </p>
	  <p>
	    If you send an existing mail using users' preferred language the system will use the mail trigger to fetch the content. The content you then see below
	    in the editor is irrelevant. <strong>Make sure the mail exists in all languages of all users you send to in that case!</strong>
	  </p>
	</td>
      </tr>
    <?php endif ?>
  </table>
  <div id="msg-area" style="padding: 10px; background-color: #FEE;">

  </div>
  <div id="editarea" style="display: none;">
    <table>
      <tr>
	<td>
	  <span id="newmail_name_label">Email Trigger</span>
	  <span id="editmail_name_label">Choose Trigger</span>
	</td>
	<td>
	  <input id="newmail_name_input" type="text" style="width: 800px;" />
	  <select id="editmail_name_input">
	    <?php populateTriggers($mh) ?>
	  </select>
	</td>
      </tr>
      <tr>
	<td>
	  <span id="lang_label">Choose Language</span>
	</td>
	<td>
	  <select id="lang_input">
	    <?php foreach(phive("Localizer")->getLangSelect() as $val => $label): ?>
	      <option value="<?php echo $val ?>"><?php echo $val ?></option>
	    <?php endforeach ?>
	  </select>
	</td>
      </tr>
      <?php if(isset($_REQUEST['to_last_search'])): ?>
	<tr>
	  <td>
	    <span>From Name (will be used instead of default if not empty)</span>
	  </td>
	  <td>
	    <input id="from_name" type="text" style="width: 800px;" />
	  </td>
	</tr>
	<tr>
	  <td>
	    <span>From Email (will be used instead of default if not empty)</span>
	  </td>
	  <td>
	    <input id="from_email" type="text" style="width: 800px;" />
	  </td>
	</tr>
	<tr>
	  <td>
	    <span>Unsubscribe Link:</span>
	  </td>
	  <td>
	    Yes:<input type="radio" id="unsubscribe_link" name="unsubscribe_link" value="1" checked="checked" />
	    No:<input type="radio" name="unsubscribe_link" value="0" />
	  </td>
	</tr>
      <?php endif ?>
      <tr>
	<td>
	  <span>Insert Replacer</span>
	</td>
	<td>
	  <select id="replacer_input">
	    <option value="">Default replacers</option>
	    <?php foreach ($replacers as $replacer => $value): ?>
	      <option value="<?php echo $replacer ?>"><?php echo $replacer ?></option>
	    <?php endforeach ?>
	  </select>
	</td>
      </tr>
      <tr>
	<td>
	  <span>Subject</span>
	</td>
	<td>
	  <input id="subject_input" type="text" style="width: 800px;" />
	</td>
      </tr>
      <tr>
	<td>
	  <span>Content</span>
	</td>
	<td>
	  <textarea id="content"></textarea>
	</td>
      </tr>
      <tr>
	<td>
	  <?php if(!isset($_REQUEST['to_last_search']) && p('email.manager.delete.mail')): ?>
	    <input type="button" id="delete_mail" value="Delete Mail"/>
	  <?php endif ?>
	</td>
	<td>
	  <?php if(isset($_REQUEST['to_last_search'])): ?>
	    <input type="button" id="send_mail" value="Send Mail"/>
	    <input type="button" id="send_preflang_mail" value="Send Mail Using Preferred Language"/>
	  <?php else: ?>
	    <input type="button" id="save_mail" value="Save Mail"/>
	  <?php endif ?>
        <input type="button" id="send_to_me" value="Test Mail"/>
	  <img id="loader" src="/phive/images/ajax-loader.gif" style="display: none;"/>
	</td>
      </tr>
    </table>

  </div>


</div>
