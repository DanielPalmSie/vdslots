<?php

// TODO henrik remove this

require_once __DIR__ . '/../../../admin.php';

function setup_forms($id, $group, &$error)
{
  $former = phive('Former');
  $former->reset();
  
  if ($group === false)
  {
    $id = null;
    $error = "No group by that ID";
  }
  
  $renderer_rows = new FormRendererRows();

  $form0 = new Form('addremove', $renderer_rows);
  $form0->addEntries(
    new EntrySubmit('new_group', array(
      "default"=>'New group',
      "action"=>'?p=' . $_GET['p'] . '&arg0=new')));

  $former->addForms($form0);
  
  if ($id)
  {
    $form0->addEntries(
      new EntryHidden('group_id', $id),
      new EntrySubmit('delete_group', array(
	"default"=>'Delete this group')));
  }
  
  if ($id !== null)
  {
    $form = new Form('updategroup');
    $form->addEntries(
      new EntryHidden('group_id', $id),
      new EntryText('name', array(
	"name"=>"Name",
	"default"=>($group)?$group->getName():'',
	"mandatory"=>true)));
    
    // Add either update or add button
    if ($id == 0)
      $form->addEntries(new EntrySubmit('add_group', 'Add group'));
    else
      $form->addEntries(new EntrySubmit('update_group', 'Update group'));
    
    $former->addForms($form);
  }
}

// $arg = id, $act = action
$former = phive('Former');
$uh = phive('UserHandler');
$arg = $_GET['arg0'];
$act = $_GET['arg1'];

if ($arg === null || $arg === '0')
  $id = null;
else
  if ($arg === 'new')
    $id = 0;
else
  if ((float)$arg !== 0)
    $id = (float)$arg;
else
  $id = null;

if ($id && $act==='delete')
{
  $msg = $uh->deleteGroup($id);
  if ($msg->getType() & PHM_SIMPLE_FATAL)
  {
    echo $msg->getMessage();
  }
  else $id = 0;
}

$group = null;
// Edit page ($id=0: new page)
if ($id!==null)
{
  if ($id > 0)
    $group = $uh->getGroup($id);
}


// Pseudo
setup_forms($id, $group, $error);
$ret = $former->handleResponse();

if ($ret && $former->submitted() === 'update_group')
{
  $ret = $group->editName($former->getValue('name'));
  if (!$ret)
    echo "Could not update group";
  setup_forms($id, $group);	
}
else
  if ($ret &&	$former->submitted() === 'add_group')
{
  $ret = $uh->createGroup($former->getValue('name'));
  $newid = phive('SQL')->insertBigId();
  if ($newid)
  {
    echo "<meta http-equiv='refresh' content='0;url=?p=$_GET[p]&arg0=$newid'>";
  }
  else
    echo "Could not add group";
}
else
  if ($ret && $former->submitted() === 'delete_group')
{
  $ret = $uh->deleteGroup($former->getValue('group_id'));
}

$former->output();

// List all pages (as a hierarchy)
$groups = $uh->getAllGroups();
?>
<hr />
<table border="0">
  <thead>
    <td>ID</td>
    <td>Name</td>
    <td>Members</td>
    <?php if (phive()->moduleExists('Permission')): ?>
      <td>Permissions</td>
    <?php endif; ?>
  </thead>
  <?php foreach ($groups as $group): ?>
    <tr>
      <td><?=$group->getId()?></td>
      <td><a href="?p=<?=$_GET['p']?>&amp;arg0=<?=$group->getId()?>"><?=$group->getName()?></a></td>
      <td>
	<a href="?p=listusers&amp;group=<?=$group->getId()?>">
	  <?= $group->memberCount() ?>
	</a>
      </td>		
      <?php if (phive()->moduleExists('Permission')): ?>
	<td>
	  <a href="?p=grouppermissions&amp;id=<?=$group->getId()?>">
	    <?= phive('Permission')->permissionCount($group) ?>
	  </a>
	</td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table>
