<?php
require_once __DIR__ . '/../../../admin.php';
function validate_parent_id($post){
  if ($post['page_id'] == 0 || phive('Pager')->isValidParent($post['page_id'], $post['parent_id']))
    return new PhMessage(PHM_OK);
  else
    return new PhMessage(PHM_ERROR, "Improper parent-child relation (possibly causing infinite loop)");
}

function setup_forms($id, $page, &$error){
  $pages = phive('Pager')->getListboxData();
  $former = phive('Former');
  $former->reset();
  
  if ($page === false){
    $id = null;
    $error = "No page by that ID";
  }
  
  $renderer_rows = new FormRendererRows();

  $form0 = new Form('addremove', $renderer_rows);
  $form0->addEntries(
    new EntrySubmit('cache', array(
      "default"=>'Cache paths',
      "action"=>'?arg0=cache')),
    new EntrySubmit('new_page', array(
      "default"=>'New page',
      "action"=>'?arg0=new')));

  $former->addForms($form0);

  if ($id){
    
    if(p('delete.page'))
      $delete_page_btn = new EntrySubmit('delete_page', array("default"=>'Delete this page', "action"=>'?arg0=' . $id . '&arg1=delete'));
    
    $form0->addEntries(new EntryHidden('page_id', $id), $delete_page_btn);
  }
  
  if ($id !== null){
    $form = new Form('updatepage');
    $form->addEntries(
      new EntryHidden('page_id', $id),
      new EntryLocalizable('alias', array(
	"name"=>"Alias",
	"default"=>$page['alias'],
	"mandatory"=>true)),
      new EntryLocalizable('filename', array(
	"name"=>"Filename",
	"default"=>$page['filename'],
	"mandatory"=>true)),
      new EntryList('parent_id', array(
	"name"=>"Parent",
	"options"=>$pages,
	"validation"=>"validate_parent_id",
	"default"=>$page['parent_id'],
	"mandatory"=>true)));
    
    // Add either update or add button
    if ($id == 0)
      $form->addEntries(new EntrySubmit('add_page', 'Add page'));
    else
      $form->addEntries(new EntrySubmit('update_page', 'Update page'));
    
    $former->addForms($form);
    
    foreach (phive('Pager')->getSettings($page['page_id']) as $setting){
      
      if(p('edit.page.setting')){
	$update_btn = new EntrySubmit('update_setting', 'Update');
	$remove_btn = new EntrySubmit('remove_setting', 'Remove'); 
      }
      
      $form2 = new Form('setting'.$setting['setting_id'], $renderer_rows);
      $form2->addEntries(
	new EntryHidden('setting_id', $setting['setting_id']),
	new EntryText('name', array(
	  "name"=>"Name",
	  "default"=>$setting['name'],
	  "mandatory"=>true)),
	new EntryLocalizable('value', array(
	  "name"=>"Value",
	  "default"=>$setting['value'])),
	$update_btn,
	$remove_btn);

      $former->addForms($form2);
    }
    
    if ($id){
      // Add a new setting form
      $form3 = new Form('add_setting', $renderer_rows);
      $form3->addEntries(
	new EntryHidden('page_id', $id),
	new EntryText('name', array(
	  "mandatory"=>true)),
	new EntryLocalizable('value'),
	new EntrySubmit('add_setting', 'Add Setting'));
      $former->addForms($form3);
    }
  }
}

// $arg = id, $act = action
$former = phive('Former');
$pager = phive('Pager');
$arg = $_GET['arg0'];
$act = $_GET['arg1'];

if ($arg === null || $arg === '0')
  $id = null;
else if ($arg === 'new')
  $id = 0;
else if ((float)$arg !== 0)
  $id = (float)$arg;
else
  $id = null;

if ($arg === 'cache'){
  $pager->cacheAll();
  echo "<p>Paths cached</p>";
} else if ($id && $act==='delete'){
  if(p('delete.page'))
    $msg = $pager->deleteEntry($id);
  
  if ($msg->getType() & PHM_SIMPLE_FATAL)
    echo $msg->getMessage();
  else 
    $id = 0;
}


// Edit page ($id=0: new page)
if ($id!==null){
  if ($id > 0)
    $page = $pager->getPage($id);
  else
    $page = array("filename" => $pager->getSetting("default_filename"));
}


// Pseudo
setup_forms($id, $page, $error);
$ret = $former->handleResponse();

if ($ret && $former->submitted() === 'update_page'){
  $page = $former->getArray();
  $newid = $pager->updateEntry($page);
  if (!$newid)
    echo "Could not update page";
  setup_forms($id, $page, $error);
}else if ($ret &&	$former->submitted() === 'add_page'){
  $page = $former->getArray();
  $newid = $pager->updateEntry($page);
  
  if ($newid)
    echo "<meta http-equiv='refresh' content='0;url=?arg0=$newid'>";
  else
    echo "Could not add page";
}
else if ($ret && $id != 0 && ($former->submitted() === 'update_setting' || $former->submitted() === 'add_setting')){
  $setting = $former->getArray();
  if(p('edit.page.setting'))
    $pager->updateSetting($setting);
  setup_forms($id, $page, $error);
} else if ($former->submitted() === 'remove_setting') {
  if(p('edit.page.setting'))
    $pager->deleteSetting($former->getValue('setting_id'));
  setup_forms($id, $page, $error);
}

$former->output();

$menu = phive('Menuer')->getCurMenu($id);
if (!empty($menu) && (!empty($menu['included_countries']) || !empty($menu['excluded_countries']))) {
    echo  "<div style='margin-top: 15px; margin-left: 8px'><b>Country settings inherited from the menu linked (page will not be accessible due to this)</b>
            <ul style='margin-top: 1px'><li>Excluded countries: {$menu['excluded_countries']}</li>
            <li> Included countries: {$menu['included_countries']}</li></ul>
            </div>";
}

// List all pages (as a hierarchy)
$pages = $pager->getHierarchy();

?>
<hr />
<table border="0">
  <thead>
    <td>Alias</td>
    <td>Filename</td>
  </thead>
  <?
  foreach ($pages as $page)
  {
    $indent = $page['depth']*7;
  ?>
    <tr>
      <td style="padding-left: <?=$indent?>px">
	<a href="?arg0=<?=$page['page_id']?>"><?=$page['alias']?></a>
      </td>
      <td><?=$page['filename']?></td>
    </tr>
  <?
  }
  ?>
</table>
