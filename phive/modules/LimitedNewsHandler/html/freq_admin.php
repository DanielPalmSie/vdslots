<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
?>
<div style="padding: 10px;">
	<p>
	 	<strong> Lang(uage):</strong> the main language (the language the articles are written in) to apply the frequency to.
	</p>
	<p>
	 	<strong> Freq(uency):</strong> the amount of articles to be published per hour for the chosen language.
	</p>
</div>
<?php
Crud::table('news_freq')->renderInterface();
?>