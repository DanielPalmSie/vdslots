<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../rbl/html/display.php';

die('deprecated');

$sql = phive('SQL');
$sql->setDb('mail_db');

if(!empty($_POST['submit_mail'])){
  
  $insert = array(
    'start_num' 		=> 0,
    'chunk_size' 		=> 100,
    'body'	 			=> $_POST['content'],
    'subject'	 		=> $_POST['subject'],
    'unsubscribe_link'	=> $_POST['unsubscribe_link'],
    'site'		 		=> $_POST['sites_list'],
    'criterias'	 		=> phive('MailHandler2')->getJobCriterias());
  
  $sql->insertArray('jobs', $insert);
  echo "<strong>New job saved successfully.</strong><br><br>";
}

if(!empty($_POST['get_info'])){ 
  $j = array('criterias' => phive('MailHandler2')->getJobCriterias(), 'start_num' => 0, 'chunk_size' => 50);
?>
  <strong>Total Count: <?php echo phive('MailHandler2')->getJobCount() ?></strong>
  <br>
  <strong>First 50:</strong>
  <br>
  <?php drawStatsTable(
    phive('MailHandler2')->getMailsFromJob($j, false, true), 
    array('email', 'phone', 'country', 'newsletter', 'firstname', 'lastname', 'sex', 'preferred_lang', 'tags'), 
    array('Email', 'Phone', 'Country', 'Newsletter', 'Firstname', 'Lastname', 'Sex', 'Pref. Lang.', 'Tags')); 
  $sql->setDb();
  exit;
}	
$sql->setDb();
?>
<script>
$(document).ready(function(){
	$("#get_info").click(function(){
		$.post("/phive/modules/MailHandler2/html/start_job.php", {
			preferred_lang: $("#preferred_lang").val(),
			newsletter: $("#newsletter").val(),
			country: $("#country").val(),
			get_info: "yep"
		},
		function(res){
			$("#info_display").html(res);
		});
	});
});
</script>

<strong>Leave a field empty to disregard that criteria, leaving all empty will send to all.</strong>
<br>
<br>
<form method="post" action="">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<table>
	<tr>
		<td>Language, ex: sv or en:</td>
		<td> <?php rblInput($_POST['preferred_lang'], 'preferred_lang') ?> </td>
	</tr>
	<tr>
		<td>Country, ex: Sweden:</td>
		<td> <?php rblInput($_POST['country'], 'country') ?> </td>
	</tr>
	<tr>
		<td>Newsletter (1 to send to people who want it, empty to disregard and send to all):</td>
		<td>
			<?php rblInput(empty($_POST['newsletter']) ? 1 : $_POST['newsletter'], 'newsletter') ?> 
		</td>
	</tr>
	<?php if(phive()->moduleExists('Site')): ?>
		<tr>
			<td>Send via this site:</td>
			<td colspan="2"><?php phive('Site')->sitesSelect(false, false, array(), false, false) ?></td>
		</tr>
	<?php endif ?>
	<tr>
		<td>Unsubscribe link:</td>
		<td>
			Yes: <input type="radio" name="unsubscribe_link" value="1" checked="checked" />
			No: <input type="radio" name="unsubscribe_link" value="0" />
		</td>
	</tr>
	<tr>
		<td>Subject:</td>
		<td> <?php rblInput($_POST['subject'], 'subject') ?> </td>
	</tr>
	<tr>
		<td colspan="2">
			Content:<br>
			<?php phive('InputHandler')->printTextArea("large","content","content",$_POST['content'],"300px","400px") ?> 
		</td>
	</tr>
	<tr>
		<td>
			<br>
			<?php rblInput('Submit', 'submit_mail', "width: 100px", 'submit') ?>
		</td>
		<td></td>
	</tr>
</table>
</form>
<br>
<br>
<div id="get_info">Show Result</div>
<br>
<div id="info_display"></div>
