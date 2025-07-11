<?
header('Content-type: text/html; charset=utf-8');
require_once __DIR__ . '/../../../phive.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<title>Edit Menus</title>
</head>
<body>
<?
// Requirecs Pager / SQL / Menuer / Former

function validate_parent_id($post)
{
	if ($post['menu_id'] == 0 || // This means new menu and any parent should be fine.
		phive('Menuer')->isValidParent($post['menu_id'], $post['parent_id']))
		return new PhMessage(PHM_OK);
	else
		return new PhMessage(PHM_ERROR, "Improper parent-child relation (possibly causing infinite loop)");
}

$error = "";

function setup_forms($id, $menu, &$error)
{
	$url = phive('Pager')->getPathNoTrailing();
	$menus = phive('Menuer')->getListboxData();
	$former = phive('Former');
	$former->reset();
	$pages = phive('Pager')->getListboxData();

	$pages[0] = "Root or manual address &darr;";
	if ($menu === false)
	{
		$id = null;
		$error = "No menu item by that ID.";
	}
	$renderer_rows = new FormRendererRows();

	$form0 = new Form('addremove', $renderer_rows);
	$form0->addEntries(
		new EntrySubmit('new_menu', array(
			"default"=>'New menu item',
			"action"=>'?arg0=new')));
	if ($id)
	{
		$form0->addEntries(
			new EntryHidden('menu_id', $id),
			new EntrySubmit('delete_menu', array(
				"default"=>'Delete this menu item',
				"action"=>'?arg0=delete')));		
	}
	
	$former->addForms($form0);
	
	if ($id !== null)
	{
		$form = new Form('updatemenu');
		$form->addEntries(
			new EntryHidden('menu_id', $id),
			new EntryLocalizable('name', array(
				"name"=>"Name",
				"default"=>$menu['name'])),
			new EntryLocalizable('alias', array(
				"name"=>"Alias",
				"default"=>$menu['alias'],
				"mandatory"=>true)),
			new EntryLink('link', array(
				"name"=>"Link",
				"default"=>$menu['link'],
				"default_page_id"=>$menu['link_page_id'],
				"pages"=>$pages)),
			new EntryBoolean('new_window', array(
				"caption"=>"Open in new window",
				"default"=>$menu['new_window'])),
			new EntryList('parent_id', array(
				"name"=>"Parent",
				"options"=>$menus,
				"validation"=>"validate_parent_id",
				"default"=>$menu['parent_id'],
				"mandatory"=>true)));
			
		// Add either update or add button
		if ($id == 0)
			$form->addEntries(new EntrySubmit('add_menu', 'Add menu item'));
		else
			$form->addEntries(new EntrySubmit('update_menu', 'Update menu item'));
		
		$former->addForms($form);
	}
}

// $arg = id, $act = action
$menuer = phive('Menuer');
$former = phive('Former');
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
	$msg = $menuer->deleteEntry($id);
	if ($msg->getType() & PHM_SIMPLE_FATAL)
	{
		$error = $msg->getMessage();
	}
	else $id = 0;
}
else
if ($id && $act==='moveup')
{
	$menuer->move('up', $id);
	$id = null;
}
else
if ($id && $act==='movedown')
{
	$menuer->move('down', $id);
	$id = null;
}

if ($id!==null)
{
	if ($id > 0)
		$menu = $menuer->getMenu($id);
	else
		$menu = array();
}	

setup_forms($id, $menu, $error);
$ret = $former->handleResponse();

// Edit menu ($id=0: new menu)
if ($ret && $former->submitted() === 'update_menu')
{
	$menu = $former->getArray();
	$ret = $menuer->updateEntry($menu);
	if (!$ret)
		$error = "Menu could not be updated.";
	setup_forms($id, $menu, $error);
}
else
if ($ret &&	$former->submitted() === 'add_menu')
{
	$menu = $former->getArray();
	$newid = $menuer->updateEntry($menu);
	if (!$newid)
		$error = "Menu could not be added.";
	else
	{
		echo "<meta http-equiv='refresh' content='0;url=?arg0=$newid'>";
	}
}

if ($error)
	echo "<p>" . $error . "</p>";

$former->output();

// List all menus (as a hierarchy)
$menus = $menuer->getHierarchy();

?>
<hr />
<table border="0">
	<thead>
		<td></td>
		<td>Alias</td>
		<td>Name</td>
		<td>Link</td>
	</thead>
<?

foreach ($menus as $menu)
{
	$indent = $menu['depth']*7;
	$style = "text-decoration: none; color: black";
?>
	<tr>
		<td>
			<a style="<?=$style?>" href="?arg0=<?=$menu['menu_id']?>&amp;arg1=moveup">&uarr;</a>
			<a style="<?=$style?>" href="?arg0=<?=$menu['menu_id']?>&amp;arg1=movedown">&darr;</a>
		</td>
		<td style="padding-left: <?=$indent?>px">
			<a href="?arg0=<?=$menu['menu_id']?>"><?=$menu['alias']?></a>
		</td>
		<td>
			<?=$menu['name']?>
		</td>
		<td><?=$menu['link']?></td>
	</tr>
<?
}
?>
</table>
</body>
</html>