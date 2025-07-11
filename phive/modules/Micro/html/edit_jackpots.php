<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
?>
<div style="padding: 10px;">
	<p>
	 	<strong>Note that this table is only for viewing purposes, the jackpots are cleared and imported everyday via an MG feed, no changes made here will be permanent.</strong>
	</p>
	<?php Crud::table('micro_jps')->renderInterface('id') ?>
</div>
