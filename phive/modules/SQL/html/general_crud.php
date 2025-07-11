<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

?>
<div style="width: 950px; overflow: scroll;">
<?php Crud::table($_GET['table'])->showSearchArea()->setSqlStr()->renderInterface() ?>
</div>
<br/>
<br/>
