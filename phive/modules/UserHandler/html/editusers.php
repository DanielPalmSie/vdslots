<?php
require_once __DIR__ . '/../../../admin.php';

// TODO henrik remove if not used

function setup_form($user){
    $former = phive('Former');
    $uh = phive('UserHandler');
    $form = new Form('edituser');
    $data = null;
    $table_id = 1;
    $t = $table_id;
    $form->addEntries(new EntryHidden('table_id', $table_id));
    $s = $uh->getTableStructure($table_id);
    if ($user)
        $data = $user->getData($table_id);

    if ($data===array())
        $data = array('id' => $user->getId());

    foreach ($s as $col){
        if (!($col['Key']=='PRI' && !$user) && $col['Field'] != 'password')
            $form->addEntries(new EntryText($col['Field'], array('name'=>$col['Field'], 'default'=>$data[$col['Field']], 'constant'=>($col['Key']=='PRI'))));
    }

    if ($user){
        $action = 'updateuser';
        $str = 'Update user';
    }else{
        $action = 'adduser';
        $str = 'Add user';
    }

    $form->addEntries(new EntrySubmit($action, $str));
    $former->addForms($form);	
}

if(isset($_GET['settings']) || !empty($_GET['action'])){
    pOrDie('edit.user.raw');
    require_once __DIR__ . '/../../../api/crud/crud.php';
    $action = !empty($_GET['action']) ? $_GET['action'] : 'insert'; 
    $op_map = array('user_id' => array('table' => 'users', 'idfield' => 'id', 'dfield' => 'username'));
    $id = $_GET['id'];
    $gets = array('action' => $action, 'p' => 'editusers', 'id' => $id, 'filterby[user_id]' => $id, 'settings' => 'true');
    Crud::table('users_settings', true)->filterBy($op_map, false)->setUrl($gets)->renderInterface('id', $op_map);
}else{
    $former = phive('Former');
    $uh = phive('UserHandler');    
    $user = null;
    if ($_GET['id']){
        $user = cu($_GET['id']);
        if ($user)
            $_groups = $user->getGroups();
        $groups = array();
        foreach ($_groups as $g)
            $groups[] = $g->getName();
        
        if (!$user)
            echo "<p>User not found</p>";
        else{
            if (phive()->moduleExists('Permission'))
	        $permissions = phive('Permission')->permissionCount($user);
            else
	        $permissions = -1;
            
            $form_del = new Form('delete_user', new FormRendererRows());
            $form_del->addEntries(new EntryHidden('user_id', $user->getId()));
            
            $former->addForms($form_del); ?>
    <div style="float:right"><?php $former->output('delete_user') ?></div>
    
    <p>Groups: <?=empty($groups)?"(no groups)":implode(', ', $groups)?> [<a href="?p=usergroups&amp;id=<?=$_GET['id']?>">edit</a>]</p>
    <?php if ($permissions!==-1): ?>
        <p>Permissions: <?=$permissions?> [<a href="?p=userpermissions&amp;id=<?=$_GET['id']?>">edit</a>]</p>
    <?php endif; ?>
    <hr />
    
    <a href="?p=<?=$_GET['p']?>&amp;id=<?=$_GET['id']?>">Basic data</a> | 
    <a href="?p=<?=$_GET['p']?>&amp;id=<?=$_GET['id']?>&amp;filterby[user_id]=<?=$_GET['id']?>&amp;settings">User settings</a>
    <hr />
<?php
        }
  }
  
  setup_form($user);
  $former->handleResponse();

  if ($former->submitted()=='adduser'){
    echo "Can not add users any more.";
  }else if ($former->submittedForm()=='edituser'){
    $data = $former->getArray();
    $table_id = $data['table_id'];
    unset($data['table_id']);
    $ret = $user->updateData($data);
    if ($ret instanceof PhMessage)
      echo "<p>" . $ret->getMessage() . "</p>";
    else if ($ret)
      echo "<p>Updated</p>";
    else
      echo "<p>Update failed</p>" . phive('SQL')->getError();
    $user->preload();
    setup_form($user);
  }else if ($former->submittedForm() == 'delete_user'){
    if (phive('UserHandler')->deleteUserById($former->getValue('user_id'))){
      echo "<p>Deleted!</p>";
      $nooutput = true;
    }else
    echo "<p>User could not be deleted</p>";
  }

  if (isset($_GET['password']))
    echo "<p>The new user was given password <b>{$_GET['password']}</b>. Change it from the set password page.</p>";

  if (!isset($nooutput))
    $former->output('edituser');
}

