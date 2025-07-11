<?php
require_once __DIR__ . '/../../../admin.php';
$mh 		= phive('MailHandler2');
$require_lang 	= $mh->getSetting("ENABLE_LANG") || $mh->getSetting("LOCALIZE");
$all_langs 	= getAllLangs();

if(isset($_POST['save']) && ($_POST['lang'] !== "null" || !$require_lang)){

  if($_POST['new_mail_trigger'] != ""){

    $mh->addMail($_POST['new_mail_trigger'],$_POST['subject'],$_POST['content'],$_POST['replacers'],$_POST['lang']);
    echo "Created new mail: ".$_POST['new_mail_trigger']." in language ".$_POST['lang'];
    $mail = $mh->getMail($_POST['new_mail_trigger'],$_POST['lang']);

  } else {

    $mh->editMail($_POST['mail_trigger'],$_POST['subject'],$_POST['content'],$_POST['replacers'],$_POST['lang']);
    echo "Edited mail: ".$_POST['mail_trigger']." in language ".$_POST['lang'];
    $mail = $mh->getMail($_POST['mail_trigger'],$_POST['lang']);

  }
} else if(isset($_GET['delete']))
$mh->removeMail($_GET['delete'],$_GET['lang']);
else if(isset($_GET['new_mail']))
$new = true;
else if(isset($_POST['lang']) && $_POST['lang'] != "null"){
  $mail = $mh->getMail($_POST['mail_trigger'],$_POST['lang']);
  $mail['lang'] = $_POST['lang'];
}

$all_mail_triggers = $mh->getMails();
$show_form 	   = sizeof($mail) > 1 || $new;
?>
<script type="text/javascript" charset="utf-8">
 var all_langs = <?php createJavascriptArray($all_langs) ?>;

 function edit_lang(){
   $('#edit_lang').submit();
 }

 function edit_mail(){
   $('#edit_mail').submit();
 }

 function verify_mail(){
   var val1 = $('#mail_trigger').val();
   var val2 = $('#new_mail_trigger').val();
   if(val1 == "" && val2 == ""){
     alert('You have to choose mail or create new mail name');
     return false;
   }
   return true;
 }

 function update_new_mail_langs(){
   var new_mail = $('#new_mail_trigger').val();
   var mail = $('#mail_trigger').val();
   if (new_mail.length > 0){
     addLanguages(all_langs,'new_lang'),'new_lang';
   }
   else{
     for(var i = 0; i < available_langs.length; i++){
       if(available_langs[i][0] == mail){
	 var arr = available_langs[i][1];
	 addLanguages(arr,'new_lang',false);
       }
     }
   }
 }

 function update_choose_langs(user_triggered){
   var mail = $('#choose_trigger').val();

   if(user_triggered)
   cur_lang = '';

   if(mail == "null")
   $('#choose_lang').hide();
   else{
     $('choose_lang').show();
     for(var i = 0; i < mails_langs.length; i++){
       if(mails_langs[i][0] == mail){

	 var arr = mails_langs[i][1];
	 addLanguages(arr,'choose_lang',true);
       }
     }
     if(user_triggered){
       var lang_opt = $('#choose_lang');
       if(lang_opt.length == 1)
       $('#choose_mail').submit();
     }
   }

   $('#editmailform').hide();
 }

 function removeAll(id){
   $("#"+id).html('');
 }

 function addLanguages(langs, id, add_choose){
   removeAll(id);
   var elSel = $("#"+id);
   if(langs.length > 1 && add_choose){
     elSel.append('<option value="null">Choose Language</option>');
   }
   for (var i = 0; i < langs.length; i++){
     if(langs[i] == cur_lang)
     elSel.append('<option value="'+langs[i]+'">'+langs[i]+'</option>');
     else
     elSel.append('<option value="'+langs[i]+'" selected="selected">'+langs[i]+'</option>');
   }
 }

 function insert_replacer(){
   var selected = $('#choose_rep').val();
   tinyMCE.execCommand('mceInsertContent', false, selected);
 }

</script>
<style type="text/css" media="screen">
 table.editmail{
   border: 1px solid black;
   margin-top:10px;
 }
 table.editmail td
 {
   vertical-align:middle;
 }
 table.editmail .input
 {
   width:400px;
 }
 table.editmail textarea
 {
   width: 400px;
   height:300px;
 }
 option{
   padding-right:10px;
 }

</style>

<table>
  <tr>
    <td colspan="3">
      <input type="button" name="new_mail" value="Create new mail" onclick="window.location='?new_mail=true'"/>
    </td>
  </tr>
</table>
<?php
printChooseEmail($all_mail_triggers,$mail['mail_trigger'],$mail['lang']);
//printChooseMail($all_mail_triggers,$langs,$mail['mail_trigger'],$mail['lang']); ?>
<?php if($show_form)
printEditMail($all_mail_triggers,$all_langs,$mail);
?>

  <?php
  function printChooseEmail($emails,$choosen_mail = null,$choosen_lang = null){
    $mails_langs = array();
    foreach ($emails as $m) {
      $l = phive('MailHandler2')->getAvailableLanguages($m);
      if(count($l) > 0){
	$mails_langs[] = array($m,$l);
      }

    }
  ?>
  <?php loadJs("/phive/js/jquery.min.js"); ?>
  <script type="text/javascript" charset="utf-8">
   var mails_langs = <?php createJavascriptArray($mails_langs); ?>;
   var cur_mail = '<?php echo $choosen_mail; ?>'
   var cur_lang = '<?php echo $choosen_lang; ?>';
  </script>
  <form action="" method="post" accept-charset="utf-8" id="choose_mail">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <table>
      <tr>
	<td>
	  <select name="mail_trigger" onchange="update_choose_langs(true)" id="choose_trigger">
	    <option value="null">Choose mail to edit</option>
	    <?php foreach ($emails as $mt): ?>
	      <option value="<?php echo $mt; ?>" <?php if($choosen_mail == $mt) echo 'selected="selected"'; ?>><?php echo $mt; ?></option>
	    <?php endforeach ?>
	  </select>

	</td>
	<td width="200">
	  <select name="lang" id="choose_lang" style="display:none" onchange="submit_choose()">
	    <option value="">Choose mail trigger first </option>
	  </select>
  </form>
	</td>

      </tr>
    </table>
    <script type="text/javascript" charset="utf-8">
     update_choose_langs(false);
    </script>

    <?php
    }

    function printEditMail($emails,$all_langs,$mail = null){
      $replacers = phive('MailHandler2')->getDefaultReplacers(cu());
      $available_langs = array();
      foreach ($emails as $m) {
	$l = phive('MailHandler2')->getAvailableLanguages($m);
	$av = array_diff($all_langs,$l);
	if(count($av) > 0){
	  $available_langs[] = array($m,$av);
	}

      }
    ?>
    <script type="text/javascript" charset="utf-8">
     var available_langs = <?php createJavascriptArray($available_langs) ?>;
    </script>
    <table class="editmail" id="editmailform">
      <?php if(!$mail): ?>
	<tr>
	  <td colspan="3" style="text-align:center"><p style="font-size:14px;"><u>Create new mail</u></p></td>
	</tr>
	<div class="former">
	  <tr>
	    <td colspan="2">
	      <form action="" method="post" accept-charset="utf-8" onsubmit="return verify_mail()">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
		<select name="mail_trigger" onchange="update_new_mail_langs()" style="float:left" id="mail_trigger">
		  <option value="">Choose name</option>
		  <?php foreach ($available_langs as $mt): ?>
		    <option value="<?php echo $mt[0]; ?>"><?php echo $mt[0]; ?></option>
		  <?php endforeach ?>
		</select>
		<p>&nbsp;
		  or create new <input type="text" name="new_mail_trigger" value="" onkeyup="update_new_mail_langs()" id="new_mail_trigger"/>
		</p>
	    </td>
	  </tr>
	  <tr>
	    <td>Choose language:</td>
	    <td colspan="2">
	      <select name="lang" id="new_lang">
		<?php foreach ($all_langs as $l): ?>
		  <option value="<?php echo $l; ?>">
		    <?php echo $l; ?>
		  </option>
		<?php endforeach ?>
	      </select>
	    </td>
	  </tr>
      <?php endif; ?>
      <tr>
	<td>
	  <?php if($mail): ?>
	    <form action="" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	      <input type="hidden" name="mail_trigger" value="<?php echo $mail['mail_trigger']; ?>" />
	      <input type="hidden" name="lang" value="<?php echo $mail['lang']; ?>"/>
	  <?php endif; ?>
	  Subject:
	</td>

	<td colspan="2">
	  <input type="text" name="subject" value="<?php echo $mail['subject']; ?>" class="input"/>
	</td>
      </tr>
      <tr>
	<td>Added replacers:</td>
	<td colspan="2">
	  <input type="text" name="replacers" value="<?php echo $mail['replacers']; ?>" id="replacers" class="input"/>
	  <p style="float:right">
	    <select name="choose_rep" id="choose_rep" onchange="insert_replacer()">
	      <option value="">Default replacers</option>
	      <?php foreach ($replacers as $replacer => $value): ?>
		<option value="<?php echo $replacer; ?>"><?php echo $replacer; ?></option>
	      <?php endforeach ?>
	    </select>
	  </p>
	</td>
      </tr>
      <tr>
	<td>
	  Content:
	</td>

	<td colspan="2">
	  <?php
	  phive('InputHandler')->printTextArea("large","content","content",$mail['content'],"300px","400px");
	  ?>
	</td>
      </tr>
      <tr>
	<td>
	  <input type="submit" name="save" value="Save"/>
	    </form>

	</td>
	<td><input type="button" name="delete" value="Delete this mail" onclick="window.location = '?delete=<?php echo $mail['mail_trigger']; ?>&lang=<?php echo $mail['lang']; ?>'"/>
	</td>
	<td>&nbsp;</td>
      </tr>
	</div>
    </table>
    <?php
    }
    function createJavascriptArray($array){
      if(is_array($array)){
	$first = true;
	echo "new Array(";
	foreach ($array as $a) {
	  if(!$first){
	    echo ",";
	  }
	  createJavascriptArray($a);
	  $first = false;
	}
	echo ")";
      }
      else{
	echo "'".$array."'";
      }
    }
    function getAllLangs(){
      $al = phive('Localizer')->getAllLanguages();
      $all_langs = array();
      foreach ($al as $a) {
	$all_langs[] = $a['language'];
      }
      return $all_langs;
    }
    ?>
