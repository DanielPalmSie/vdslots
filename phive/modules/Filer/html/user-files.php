<?php
require_once __DIR__ . '/../../../admin.php';

$filer = phive('Filer');

$user = cu($_REQUEST['id']);

if (!empty($_FILES)){
  $fid 		= uniqid();
  $new_name 	= $filer->uploadFile('myfile', 'user-files', $fid, true);
  if($new_name){
    $user->setSetting('file_'.time(), $new_name);
  }else{
  }
}

if(!empty($_POST['delete_setting'])){
  $filer->deleteFile('user-files/'.$user->getSetting('', $_POST['delete_setting']));
  $user->deleteSetting('', $_POST['delete_setting']);
}

?>
<form enctype="multipart/form-data" action="" method="POST">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table style="padding: 10px; margin: 10px;">
    <tr>
      <td>
	Upload new file:<br><br> 
	<input name="myfile" type="file" />
	<br>
	<br>
	<input type="hidden" value="<?php echo $_REQUEST['id'] ?>" name="id" />
	<input type="submit" value="Upload File" />			
      </td>
    </tr>
  </table>
</form>
<div class="pad-stuff-ten scroll-div">
  <?php foreach($user->getAllSettings(" (setting LIKE 'file_%' OR setting LIKE 'idpic' OR setting LIKE 'addresspic') ") as $f): 
  list($file, $stamp) = explode('_', $f['setting']);
  ?>
    <p>
      Uploaded: <?php echo date('Y-m-d', $stamp) ?>
      <br/>
      <?php imgOrPdf(getMediaServiceUrl() . "/file_uploads/user-files/".$f['value'], 500, 250) ?>
      <form action="" method="POST">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	<input type="hidden" value="<?php echo $f['id'] ?>" name="delete_setting" />
	<input type="hidden" value="<?php echo htmlspecialchars($_REQUEST['id']) ?>" name="id" />
	<input type="submit" value="Delete File" />
      </form>
    </p>
  <?php endforeach ?>
</div>
