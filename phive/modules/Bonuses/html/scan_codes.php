<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';
if(!empty($_POST['code']))
	$result = phive('Affiliater')->scanSiteCodes($_POST['code']);
	
?>
<div style="padding: 10px;">
<form action="" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<table>
	<tr>
		<td>Code:</td>
		<td><?php dbInput('code', $_POST['code']) ?></td>
	</tr>
	<tr>
		<td><input type="submit" name="submit_code" value="Submit"></td>
		<td></td>
	</tr>
</table>
</form>
<br/>
<br/>
<?php if(!empty($result)): ?>
<table>
	<?php foreach($result as $type => $arr): 
		if(empty($arr))
			continue;
		$info 	= $arr[0];
		$header = array_keys($info);
	?>
	<tr>
		<td><?php echo ucfirst($type) ?></td>
	</tr>
	<tr>
		<td>
			<table class="stats_table">
				<tr class="stats_header">
					<?php foreach($header as $key): ?>
						<td><?php echo $key?></td>
					<?php endforeach ?>
				</tr>
				<tr>
					<?php foreach($info as $value): ?>
						<td><?php echo $value?></td>
					<?php endforeach ?>
				</tr>
			</table>
		</td>
	</tr>
	<?php endforeach ?>
</table>
<?php elseif(!empty($_POST['code'])): ?>
	 No entires using <strong><?php echo $_POST['code'] ?></strong> were found. 
<?php endif ?>
</div>