<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
$crud = Crud::table('bonus_types', true);
?>
<div style="padding: 10px; width: 80%;">
	<a href="<?php echo llink('/admin/') ?>">Back</a>
	<br/>
	<br/>
	<?php et('manage.bonuses.info.html') ?>
</div>
<div style="overflow-x: scroll; width: 80%; margin: 10px;">
<?php
$cur_date = date('Y-m-d');
$crud->deleteOff()->setWhere("expire_time >= '$cur_date'")->renderInterface();
?>
<button onclick="showId('expired_bonuses', 500)">Show Expired Bonuses</button>
<div style="display: none;" id="expired_bonuses">
<p>
	<strong>Expired Bonuses:</strong>
</p>
<?php 
Crud::table('bonus_types', true, true)->deleteOff()->setWhere("expire_time < '$cur_date'")->renderInterface('id', array(), false);
?>
</div>
</div>
<br/>
<br/>
