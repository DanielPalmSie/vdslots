<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
?>
<div style="padding: 10px;">
	<p>
	 	<strong></strong><strong></strong><br>
	</p>
</div>
<?php
Crud::table('partner_category')->renderInterface('id', array(
	'partner_id' => array('table' => 'raker_partners', 'idfield' => 'partner_id', 'dfield' => 'full_name'),
	'category_id' => array('table' => 'news_categories', 'idfield' => 'category_id', 'dfield' => 'category_alias')
));
