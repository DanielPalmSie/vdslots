<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

?>
<div style="width: 950px; overflow: scroll;">
<?php
$cur_date = date('Y-m-d');
Crud::table('bonus_types')->hideControls()->deleteOff()->setWhere("expire_time >= '$cur_date'")->renderInterface('id', array(), false);
?>
<button onclick="showId('expired_bonuses', 500)">Show Expired Bonuses</button>
<div style="display: none;" id="expired_bonuses">
<p>
	<strong>Expired Bonuses:</strong>
</p>
<?php 
Crud::table('bonus_types', false, true)->hideControls()->deleteOff()->setWhere("expire_time < '$cur_date'")->renderInterface('id', array(), false);
?>
</div>
</div>
<br/>
<br/>
