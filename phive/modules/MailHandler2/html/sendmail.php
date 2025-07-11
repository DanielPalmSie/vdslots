<?php
ini_set("memory_limit", "300M");
ini_set("max_execution_time", "30000");

require_once __DIR__ . '/../../../admin.php';
die("Peter turned this off to see if it is used because it's not going to work anymore");
printJS();
$mh = phive('MailHandler2');

if(!empty($_POST['from_name']))
  $mh->from_name = $_POST['from_name'];

if(!empty($_POST['from_mail']))
  $mh->from_mail = $_POST['from_mail'];


$admin = phive("UserHandler")->getUserByUsername('admin');

/*
$no_tiny = phive('Pager')->fetchSetting('no_tiny');
if($no_tiny == 'true')
$mh->setSetting('USE_TINYMCE', false);
 */

if(isset($_POST['send_mail'])){

  if(isset($_POST['user_id'])){
    $user = cu($_POST['user_id']);
    if($user){
      $mh->sendMailFromString($_POST['content'],
			      $_POST['subject'],
			      $user,
			      $_POST['lang'],
			      $mh->getDefaultReplacers($user));
      echo "Successfully sent a mail to ".$user->getUsername();
    }

  } else if(!empty($_FILES['csv']['tmp_name'])) {
    foreach(file($_FILES['csv']['tmp_name']) as $email){
      $mh->sendMailToEmail(
	array('content' => $_POST['content'], 'subject' => $_POST['subject']),
	trim($email)
      );
    }
    echo "Emails were sent successfully.";
  } else if(isset($_POST['q_id'])) {
    phive('SQL')->query(getQuery($_POST['q_id']));
    while($user_raw = phive('SQL')->fetch()){
      if($user_raw['newsletter'] != 0){
	$html = '';
	if($mh->getSetting('domain') != null && $_POST['unsubscribe_link'] == 1)
	  $html = $mh->getUnsubExtra($user_raw['email'], $mh->getSetting('domain'));

	$html 	= $_POST['content'].$html;
	$u 	= cu($user_raw['id']);
	$mh->sendMailFromString(
	  $html,
	  $_POST['subject'],
	  $u,
	  $_POST['lang'],
	  $mh->getDefaultReplacers($u),
	  null,
	  null,
	  null,
	  null,
	  1
	);
	if(phive()->moduleExists('DBUserHandler'))
	  phive('UserHandler')->logAction($u, "System sent mail with subject: {$_POST['subject']}", "mass_mail", false, $admin);
      }
    }
?>
Successfully sent mail to <?php echo countUsers($_POST['q_id']); ?> users.
<?php
}
} else if (isset($_POST['send_with_default'])) {
    if (isset($_POST['user_id'])) {
        $user = cu($_POST['user_id']);
        if ($user) {
            $mh->sendMail($_POST['mail_trigger'],
                $user,
                $mh->getDefaultReplacers($user));
            echo "Successfully sent a mail to " . $user->getUsername();

        }
    } else if (isset($_POST['q_id'])) {
        phive('SQL')->query(getQuery($_POST['q_id']));
        $users = [];
        while ($user_raw = phive('SQL')->fetch()) {
            if ($user_raw['newsletter'] !== 0) {
                $users[] = $user_raw;
            }
        }
        $users = phive('MailHandler2')->filterMarketingBlockedUsers($users);
        foreach ($users as $user_raw) {
            $u = cu($user_raw['id']);
            $u->marketing_blocked = $user_raw['marketing_blocked'];
            $mh->sendMail($_POST['mail_trigger'], $u, $mh->getDefaultReplacers($u), null, null, null, null, null, 1);
        }

        echo "Successfully sent mails to " . countUsers($_POST['q_id']) . " users";
    }
}
else if(isset($_POST['mail_trigger']) && $_POST['mail_trigger'] != "null"){ //Mailtrigger show mail
	                                                                   if($_POST['lang'] == "use_user_lang"){
    $mail = phive('MailHandler2')->getMail($_POST['mail_trigger']);
    if (isset($_POST['user_id'])){
      $user = cu($_REQUEST['user_id']);
      printChooseEmail($mh->getMails(),$mail['mail_trigger'],"null",$user);
      printSendLocalized($mail,$user);
    }
    else if(isset($_POST['q_id'])){
      printChooseEmail($mh->getMails(),$mail['mail_trigger'],"null",null,$_POST['q_id']);
      printSendLocalized($mail,null,$_POST['q_id']);

    }
  }
	                                                                   else{
    $mail = phive('MailHandler2')->getMail($_POST['mail_trigger'],$_POST['lang'],true);
    $langs = phive('MailHandler2')->getAvailableLanguages($_POST['mail_trigger'],true);
    if (isset($_POST['user_id'])){
      $user = cu($_REQUEST['user_id']);
      printChooseEmail($mh->getMails(),$mail['mail_trigger'],$_POST['lang'],$user);
      printShowEMail($mail,$_POST['lang'],$user);
    }
    else if(isset($_POST['q_id'])){
      printChooseEmail($mh->getMails(),$mail['mail_trigger'],$_POST['lang'],null,$_POST['q_id']);
      printShowEMail($mail,$_POST['lang'],null,$_POST['q_id']);
    }
  }

                                                                           }
else if($_POST['mail_trigger'] === "null"){ // new mail
	                                   if (isset($_POST['user_id'])){
    $user = cu($_REQUEST['user_id']);
    printChooseEmail($mh->getMails(),"null",null,$user);
    printShowEmail(null,null,$user);
  }
	                                   else if(isset($_POST['q_id'])){
    printChooseEmail($mh->getMails(),"null",null,null,$_POST['q_id']);
    printShowEmail(null,null,null,$_POST['q_id']);
  }
                                           }
else if(isset($_GET['to_last_search'])){
  $id = 'users_query'.uniqid();
  $_SESSION[$id] = $_SESSION['last_search'];
  printChooseEmail($mh->getMails(),null,null,null,$id);
}
else if(isset($_GET['user_id'])){
  $user = cu($_GET['user_id']);
  if ($user)
    printChooseEmail($mh->getMails(),null,null,$user);
}else if(isset($_GET['from_csv'])){
  printChooseEmail(array());
  printShowEmail(null,null,null,null);
}


function printChooseEmail($emails,$choosen,$choosen_lang = null,$user = null,$q_id = null){
  $mails_langs = array();
  foreach ($emails as $m) {
    $l = phive('MailHandler2')->getAvailableLanguages($m);
    if(count($l) > 0){
      $mails_langs[$m] = $l;
    }

  }
?>
<script type="text/javascript" charset="utf-8">
 var mails = new Array(<?php $not_first = false; foreach ($mails_langs as $m => $l) {
			 if($not_first)
			   echo ",";
			 echo "new Array(";
			 echo "'".$m."'";
			 echo ",'".implode("','",$l)."'";
			 echo ")";
			 $not_first = true;
		       } ?>);
 var cur_lang = '<?php echo $choosen_lang; ?>';
</script>

<form action="." method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table>
    <tr>
      <td colspan="3">
	<?php if($q_id): ?>
	  Sending mail to <?php echo countUsers($q_id); ?> users
	  <input type="hidden" name="q_id" value="<?php echo $q_id; ?>" id="q_id"/>
	<?php elseif(!isset($_GET['from_csv'])): ?>
	  Sending mail to <a href="/profile/user/<?php echo $user->getUsername(); ?>/" target="_blank" rel="noopener noreferrer">
	    <?php echo $user->getUsername(); ?> (<?php echo $user->getAttribute("preferred_lang"); ?>)
	  </a>
	  <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>" id="user_id"/>
	<?php endif; ?>
      </td>
    </tr>
    <tr>
      <td>
	<select name="mail_trigger" onchange="update_langs()" id="mail_trigger">
	  <option value="null">Create new mail</option>
	  <?php foreach ($emails as $mt): ?>
	    <option value="<?php echo $mt; ?>" <?php if($choosen == $mt) echo 'selected="selected"'; ?>><?php echo $mt; ?></option>
	  <?php endforeach ?>
	</select>

      </td>
      <td width="200">
	<select name="lang" id="lang" style="display:none">
	  <option value="">Choose mail trigger first </option>
	</select>
      </td>
      <td>
	<input type="submit" name="submit_choose_mail" value="Choose" id="submit_choose_mail"/>
</form>
      </td>
    </tr>
  </table>
  <script type="text/javascript" charset="utf-8">
   update_langs();
  </script>

  <?php
  }

  function printShowEmail($mail,$lang = null,$user= null,$q_id=null){
    if($user){
      $rep_user = $user;
    }
    else if($q_id){
      $rep_user = cu();
    }
    if($rep_user){
      $replacers = phive('MailHandler2')->getDefaultReplacers($rep_user);
    }
  ?>
  <script type="text/javascript" charset="utf-8">
   var replacers = new Array(new Array(<?php $first = true; foreach ($replacers as $k => $v):
			                                                               if(!$first)
				                                                         echo ",";
			               echo "new Array('$k','$v')";
			               $first = false;
		                       endforeach ?>));
  </script>
  <form action="." method="post" accept-charset="utf-8" enctype="multipart/form-data">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <?php if ($user): ?>
      <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>"/>
    <?php endif ?>
    <?php if ($q_id): ?>
      <input type="hidden" name="q_id" value="<?php echo $q_id; ?>"/>
    <?php endif ?>
    <?php if ($lang): ?>
      <input type="hidden" name="lang" value="<?php echo $lang; ?>"/>
    <?php endif ?>
    <table style="width:100%">
      <?php if(phive()->moduleExists('Site')): ?>
	<tr>
	  <td colspan="2"><?php phive('Site')->allSitesSelect(false, true) ?></td>
	</tr>
      <?php endif ?>
      <?php if(isset($_GET['from_csv'])): ?>
	<tr>
	  <td colspan="2">Sending mail to:<input type="file" name="csv" value="" id=""/></td>
	</tr>
      <?php endif ?>
      <tr>
	<td colspan="2">
	  Unsubscribe Link:<br>
	  Yes:<input type="radio" name="unsubscribe_link" value="1" checked="checked" />
	  No:<input type="radio" name="unsubscribe_link" value="0" />
	</td>
      </tr>
      <tr>
	<td>Subject</td>
	<td>
	  <input type="text" name="subject" value="<?php echo $mail['subject']; ?>" id="subject"/>&nbsp;
	  <p style="float:right">
	    <select name="choose_rep" id="choose_rep" onchange="insert_replacer()">
	      <option value="">Available replacers</option>
	      <?php foreach ($replacers as $replacer => $value): ?>
		<option value="<?php echo $replacer; ?>"><?php echo $replacer; ?></option>
	      <?php endforeach ?>
	    </select>
	  </p>
	</td>
      </tr>
      <tr>
	<td>From Name</td>
	<td>
	  <input type="text" name="from_name" value="" id="from_name"/>&nbsp;
	</td>
      </tr>
      <tr>
	<td>From Email</td>
	<td>
	  <input type="text" name="from_mail" value="" id="from_mail"/>&nbsp;
	</td>
      </tr>
      <tr>
	<td>
	  Content
	</td>
	<td>

	  <?php
	  phive('InputHandler')->printTextArea("large","content","content",$mail['content'],"300px","400px");
	  ?>
	</td>
      </tr>
      <tr>
	<td>
	  <input type="submit" name="send_mail" value="Send mail" id="send_mail"/>
	  <td>
	    <?php if ($rep_user): ?>
	      <a href="#" onclick="return show_mail('content',0,true)">View mail</a> in user <a href="/profile/user/<?php echo $rep_user->getUsername(); ?>/"><?php echo $rep_user->getUsername(); ?>'s</a> view
	    <?php endif ?>
	  </td>
      </tr>
    </table>
  </form>
  <?php
  }

  function printSendLocalized($mail,$user = null,$q_id = null){
  ?>
  <form action="." method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="mail_trigger" value="<?php echo $mail['mail_trigger']; ?>"/>
    <?php
    if($user){
      $mail = phive('MailHandler2')->getMail($mail['mail_trigger'],$user->getAttribute("preferred_lang"));
      $replacers = phive('MailHandler2')->getDefaultReplacers($user);
      printHiddenDiv("content",$mail['content'])
    ?>
    <script type="text/javascript" charset="utf-8">
     var replacers = new Array(new Array(<?php $first = true; foreach ($replacers as $k => $v):
				                                                         if(!$first)
					                                                   echo ",";
				         echo "new Array('$k','$v')";
				         $first = false;
			                 endforeach ?>));
    </script>
    <p><a href="#" onclick="return show_mail('content',0,false);">View mail</a></p>
    <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>"/>
    <?php

    }
    else if($q_id && countUsers($q_id) < 100){
      $users = getUsers($q_id);
      $users = getUsersFromArray($users);
      $langs = phive('MailHandler2')->getAvailableLanguages($mail['mail_trigger']);
      $users_by_lang =array();
      foreach ($users as $u) {
	$users_by_lang[$u->getAttribute("preferred_lang")][] = $u;
      }
      $index = 0;
      foreach($users_by_lang as $lang => $us){
	$m = phive('MailHandler2')->getMail($mail['mail_trigger'],$lang);
	printHiddenDiv("content_".$lang,$m['content']);
    ?>
    <p><?php if(strlen($m['content']) == 0): ?><span style="color:red">WARNING, no mail found</span><?php endif; ?> <?php echo $lang; ?> has <?php echo count($us); ?> users, <a href="#" onclick="return show_mail('content_<?php echo $lang; ?>',<?php echo $index; ?>,false);">View mail in <?php echo $us[0]->getUsername(); ?>'s view</a></p>
    <?php
    $index++;
    }

    ?>
    <script type="text/javascript" charset="utf-8">
     var replacers = new Array(<?php $first = true; foreach ($users_by_lang as $lang => $us):
			                                                              $replacers = phive('MailHandler2')->getDefaultReplacers($us[0]);
			       if(!$first)
				 echo ",";
			       ?>new Array(<?php $first1 = true; foreach ($replacers as $k => $v):
				                                                            if(!$first1)
					                                                      echo ",";
				           echo "new Array('$k','$v')";
				           $first1 = false;
			                   endforeach ?>)<?php $first=false; endforeach ?>);
    </script>
    <input type="hidden" name="q_id" value="<?php echo $q_id; ?>"/>

    <?php

    }
    ?>
    <p><input type="submit" name="send_with_default" value="Send mail"/></p>
  </form>

  <?php


  }
  function printHiddenDiv($id,$content){
  ?>
  <div id="<?php echo $id; ?>" style="display:none"><?php echo $content; ?></div>
  <?php

  }
  function getUsersFromArray($users){
    $users_o = array();
    foreach ($users as $u) {
      $users_o[] = cu($u['id']);
    }
    return $users_o;
  }
  function getQuery($q_id){
    if(!isset($_SESSION[$q_id])){
      trigger_error("Tried to fetch query with invalied q_id: ".$q_id);
      return null;
    }
    return $_SESSION[$q_id];
  }
  function getUsers($q_id){
    phive("SQL")->query(getQuery($q_id));
    $users = phive("SQL")->fetchArray();
    return $users;
  }
  function countUsers($q_id){
    phive('SQL')->query("SELECT COUNT(*) FROM (".getQuery($q_id).") AS user_table");
    return phive('SQL')->result();
  }
  function printJS(){
  ?>
  <script type="text/javascript" charset="utf-8">

   function show_mail(id,rep_id,is_input){
     if(is_input){
       tinyMCE.triggerSave()

       var content = document.getElementById(id).value;
     }
     else{
       var content = document.getElementById(id).innerHTML;
     }
     content = replace(content,rep_id);
     var newwindow = window.open('','name','height=400,width=400');
     var tmp = newwindow.document;
     tmp.write(content);
     tmp.close();
     return false;
   }
   function replace(content,rep_id){
     for(var i=0;i<replacers[rep_id].length;i++){
       var pos = content.indexOf(replacers[rep_id][i][0]);
       while (pos != -1){
	 content = content.replace(replacers[rep_id][i][0],replacers[rep_id][i][1]);
	 pos = content.indexOf(replacers[rep_id][i][0]);
       }
     }
     return content;
   }

   function update_langs(){
     var mail = document.getElementById('mail_trigger').value;
     if(mail == "null")
     document.getElementById('lang').style.display = 'none';
     else{
       document.getElementById('lang').style.display = 'block';
       for(var i = 0;i<mails.length;i++){
	 if(mails[i][0] == mail){
	   var arr = mails[i];
	   addLanguages(arr);
	 }
       }
     }


   }
   function removeAll()
   {
     var elSel = document.getElementById('lang');
     var i;
     for (i = elSel.length - 1; i>=0; i--) {
       elSel.remove(i);

     }
   }
   function addLanguages(langs){
     removeAll();
     var elSel = document.getElementById('lang');
     var elOptNew = document.createElement('option');
     elOptNew.text = 'Use users default language    ';
     elOptNew.value = 'use_user_lang';
     if(cur_lang == 'use_user_lang')
     elOptNew.selected = true;
     try {
       elSel.add(elOptNew, null); // standards compliant; doesn't work in IE
     }
     catch(ex) {
       elSel.add(elOptNew);
     }
     for (var i = 1;i<langs.length;i++){
       var elOptNew = document.createElement('option');
       elOptNew.text = langs[i];
       elOptNew.value = langs[i];
       if(elOptNew.value == cur_lang)
       elOptNew.selected = true;
       try {
	 elSel.add(elOptNew, null); // standards compliant; doesn't work in IE
       }
       catch(ex) {
	 elSel.add(elOptNew); // IE only
       }
     }
   }
   function insert_replacer(){
     var selected = document.getElementById('choose_rep').value;
     tinyMCE.execCommand('mceInsertContent',false,selected);
   }
   function show_all_users(){
     var vis = document.getElementById('all_users').style.display;
     if(vis == 'block'){
       document.getElementById('all_users').style.display = 'none';
     }
     else
     document.getElementById('all_users').style.display = 'block';
     return false;
   }


  </script>
  <?php
  }
  ?>
