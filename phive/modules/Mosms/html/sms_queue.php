<?php
ini_set("memory_limit", "300M");
ini_set("max_execution_time", "30000");
require_once __DIR__ . '/../../../admin.php';

if(isset($_GET['send'])){
	$msgs 			= phive('Localizer')->getAllTranslations('sms.message');
	$default_lang 	= phive('Localizer')->getDefaultLanguage();
	$insert 		= array();
	$numbers		= array();
	foreach(phive('SQL')->loadArray($_SESSION['last_search']) as $u){
		
		$number 	= phive('Mosms')->cleanUpNumber($u);
		
		if(empty($u['mobile']) || empty($u['verified_phone']) || in_array($number, $numbers))
			continue;

		$numbers[] 	= $number;
		
		$msg 		= empty($msgs[$u['preferred_lang']]) ? $msgs[$default_lang] : $msgs[$u['preferred_lang']];  
		$msg 		= phive("SQL")->escape($msg, false);
		$insert[] 	= array('user_id' => $u['id'], 'msg' => $msg);
	}
	phive('SQL')->insert2DArr('sms_queue', $insert);
	phive("Logger")->logPromo('mass_sms', $msg);
}

?>
<div class="pad10">
<?php if(isset($_GET['send'])): ?>
Queue has been saved.
<?php else: ?>
String to use, note that it must conform to the maximum allowable length for an SMS:<br/><br/>  
<strong><?php et('sms.message') ?></strong>
<br/>
<br/>
<form action="?send" method="post">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	<input type="submit" value="Submit SMS batch"/>
</form>
<?php endif ?>
</div>
