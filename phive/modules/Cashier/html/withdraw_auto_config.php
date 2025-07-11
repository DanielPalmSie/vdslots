<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
if($_GET['action'] != 'update')
  $list = phive('Config')->getByTag('auto-withdraw-option');
else
  jsReloadBase();
?>
<div class="pad-stuff-ten">
  <?php Crud::table('config', true)->renderInterface('id', array(), true, null, $list) ?>
</div>
