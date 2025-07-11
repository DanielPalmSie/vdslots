<?
require_once __DIR__ . '/../../phive/admin.php';
require_once __DIR__.'/../html/display.php';

$menuer = phive()->getModule('Menuer');
$menus = $menuer->getChildren('admin');

foreach($menus as $category): ?>
	<div style="text-align:left;">
	<h2 style="clear: left"><?=$category['name']?></h2>
	<?php
	$submenus = $menuer->getChildren($category['alias']);
	if($submenus):
	$chunks = array_chunk($submenus, 10);
	foreach($chunks as $chunk):
	?>
		<table>
			<tr>
			<?php foreach($chunk as $sub): ?>
			<td style="padding: 5px;">
				<?php dbButton($sub['name'],substr($sub['linkparams'],6)); ?>
			</td>
			<?php endforeach;?>
			</tr>
		</table>
	<?php endforeach;?>
	<?php endif;?>
	</div>
<?php endforeach; ?>