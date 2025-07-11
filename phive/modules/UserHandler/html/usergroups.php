<?php
require_once __DIR__ . '/../../../admin.php';

// TODO henrik remove this

function setup_form($user){
  $uh = phive('UserHandler');
  $former = phive('Former');

  $former->reset();
  $_groups     = $user->getGroups();
  $_all_groups = $uh->getAllGroups();
  $groups      = $all_groups = array();
  foreach ($_groups as $g)
    $groups[$g->getId()] = $g->getName();
  foreach ($_all_groups as $g)
    $all_groups[$g->getId()] = $g->getName();
  
  // Construct an array with missing groups
  $list     = array_diff($all_groups, $groups);
  $renderer = new FormRendererRows();
  $form     = new Form('addgroup', $renderer);
  
  $form->addEntries(
    new EntryHidden('user_id', $user->getId()),
    new EntryList('group_id', array('options'=>$list)),
    new EntrySubmit('addgroup', "Join group"));
  
  $former->addForms($form);
}

$uh     = phive('UserHandler');
$former = phive('Former');
$user   = cu($_GET['id']);

echo '<p><a href="?p=editusers&id=' . $_GET['id'] . '">Back</a></p><hr />';

if(!$user)
  echo "<p>Invalid user ID</p>";
else{
  if($_GET['leave'])
    $uh->leaveGroup($user, $_GET['leave']);
  //$user->leaveGroup($_GET['leave']);
  
  setup_form($user);
  $ret = $former->handleResponse();
  
  if ($ret && $former->submitted() === 'addgroup'){
    $uh->joinGroup($user, $former->getValue('group_id'));
    setup_form($user);
  }
  
  $former->output('addgroup');
  
  echo "<hr />";
  // List group
  $groups = $user->getGroups();
  if (empty($groups))
    echo "User belongs to no groups";
  else{
    foreach ($groups as $group)
      echo $group->getName() . ' [<a href="?p=usergroups&id=' . $_GET['id'] . '&leave=' . $group->getId() . '">X</a>]' . "<br />";
  }
}
