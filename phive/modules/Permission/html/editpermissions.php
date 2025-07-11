<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/functions.php';

function setup_form()
{
	$former = phive('Former');
	$former->reset();
	
	$renderer = new FormRendererRows();
	$form = new Form('addtag', $renderer);
	$form->addEntries(
		new EntryText('tag', array('width'=>"240px")),
		new EntryText('mod_desc', array('width'=>"240px")),
		new EntrySubmit('addtag', array('default'=>"Add tag", 'action'=>'?')));
	
	$former->addForms($form);
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

if ($_GET['remove'])
{
	$boss->removeTag($_GET['remove']);
}

setup_form();
$ret = $former->handleResponse();
if ($ret && $former->submitted() === 'addtag')
{
	$r = $boss->addTag($former->getValue('tag'), $former->getValue('mod_desc'));
	if (!$r)
		echo "<p>Tag could not be added</p>";
}

$former->output('addtag');
?>
<hr />
<table>
	<tr>
		<th align="left">Tag</th>
		<th align="left">Parameter Description</th>
		<th></th>
	</tr>
	
	<?php foreach($boss->getAvailableTags() as $it):?>
		<tr class="<?=swapRow()?>">
		<td width="50%"><?=tag($it['tag'])?></td>
		<td width="50%"><?=$it['mod_desc']?></td>
		<td><a href="?remove=<?=$it['tag']?>">X</td>
		</tr>
	<?php endforeach; ?>
	
</table>