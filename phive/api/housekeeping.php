<?php
require_once __DIR__ . '/../phive.php';
$bcnt = phive('BoxHandler')->purgeDanglingBoxes();
?>
<div style="padding: 10px;">
	<?php echo "$bcnt boxes were deleted." ?><br/>
</div>
