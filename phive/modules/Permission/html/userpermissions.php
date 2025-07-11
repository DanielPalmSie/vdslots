<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/functions.php';

function addGroupMark($array)
{
	$array['group'] = true;
	return $array;
}

function setup_form()
{
	$former = phive('Former');
	$former->reset();
	
	$_list = phive('Permission')->getAvailableTags();

	$list = array();
	foreach ($_list as $tag)
	{
		$list[$tag['tag']] = tag($tag['tag']);
	}
	
	$renderer = new FormRendererRows();
	$form = new Form('addtag', $renderer);
	$form->addEntries(
		new EntryList('tag', array('options'=>$list)),
		new EntryText('modifier', array('default'=>"", 'width'=>"100px")),
		new EntrySubmit('grant', array('default'=>"Grant", 'action'=>'?p=userpermissions&amp;id=' . $_GET['id'])),
		new EntrySubmit('deny', array('default'=>"Deny", 'action'=>'?p=userpermissions&amp;id=' . $_GET['id'])));
	
	$former->addForms($form);
	return $list;
}

function swapRow()
{
	static $s = 'light';
	if($s == 'light')
		$s = 'dark';
	else
		$s = 'light';
	return $s;
}

$boss = phive("Permission");
$former = phive('Former');
$user = cu($_GET['id']);

echo '<p><a href="?p=editusers&id=' . $_GET['id'] . '">Back</a></p><hr />';

if (!$user)
	echo "<p>Invalid user ID</p>";
else
{
	if ($_GET['remove'])
	{
		$boss->deletePermission($user, $_GET['remove'], $_GET['modifier']);
	}

	$list = setup_form();
	$ret = $former->handleResponse();
	if ($ret && ($type = $former->submitted()))
	{
		$r = $boss->setPermission($user, $former->getValue('tag'), $former->getValue('modifier'), $type);
		if (!$r)
			echo "<p>Could not add tag</p>";
	}

	$former->output('addtag');
?>

<hr />
<table width="100%">
	<tr>
		<th align="left">Tag</th>
		<th align="left">Modifier</th>
		<th width="70px" align="left">Permission</th>
		<th></th></thead>
	
	<?
		$permissions = $boss->getAllPermissions($user);
		$groups = $user->getGroups();
		if (!empty($groups))
		foreach ($groups as $group)
		{
			$perms = $boss->getAllPermissions($group);
			//$perms = array_map("addGroupMark", $perms);
			//$permissions = mix($perms, $permissions);
			//$permissions = array_intersect($perms, $permissions);//array_merge($permissions, );
		}
	?>
	
	<?php foreach($permissions as $it):?>
		<?php $color = ($it['permission']=='grant')?'green':'red' ?>
		<tr class="<?=swapRow()?>" style="color: <?=$it['group']?'gray':'black'?>">
		<td><?=$list[$it['tag']]?><?=$it['']?></td>
		<td><?=$it['mod_value']?></td>
		<td><font color="<?=$color?>"><?=$it['permission']?></font></td>
		<td align="right" width="10px">
			<?php if (!$it['group']): ?>
			<a href="?p=userpermissions&amp;id=<?=$_GET['id']?>&amp;remove=<?=$it['tag']?>&amp;modifier=<?=$it['mod_value']?>">X</td>
			<?php endif; ?>
		</tr>
	<?php endforeach; ?>
	
</table>
<?
}
?>
