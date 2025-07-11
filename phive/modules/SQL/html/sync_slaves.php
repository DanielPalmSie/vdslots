<?php

function syncAction(){
  if(!empty($_POST['submit']))
    return phive("SQL")->syncTable($_POST['table'], $_POST['slave']);
  return false;
}

function syncRow(){
  $row = phive("SQL")->loadAssoc($sql, $_POST['table'], array($_POST['field'] => $_POST['value']));
  return phive('SQL')->syncRow($_POST['table'], $row, $_POST['slave']);
}

function syncInterface($tbls){
  $slaves = phive("SQL")->getSetting('slaves');
?>
<?php foreach($tbls as $tbl): ?>
  <form action="" method="POST">
     <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <table>
      <tr>
	<td style="width: 300px;">
	  Table
	</td>
	<td></td>
      </tr>
      <tr>
	<td style="width: 300px;">
	  <?php echo $tbl ?>
	  <input type="hidden" value="<?php echo $tbl ?>" name="table" />
	</td>
	<?php foreach($slaves as $slave): ?>
	  <td style="width: 200px;">
	    <input type="submit" value="<?php echo "Sync to $slave" ?>" name="submit" />
	    <input type="hidden" value="<?php echo $slave ?>" name="slave" />
	  </td>
	<?php endforeach ?>
      </tr>
    </table> 
  </form>
<?php endforeach ?>
<?php
}
