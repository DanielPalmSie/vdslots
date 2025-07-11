<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
?>
<div class="pad10">

</div>
<?php

$crud = Crud::table('ext_banners');
$crud->renderInterface('ext_id');
