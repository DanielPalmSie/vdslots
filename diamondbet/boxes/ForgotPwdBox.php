<?php
require_once __DIR__.'/../../phive/modules/BoxHandler/boxes/diamondbet/ForgotPwdBoxBase.php';
class ForgotPwdBox extends ForgotPwdBoxBase{
	function printHTML(){?>
		<div class="frame-block">
			<div class="frame-holder">
				<?php $this->printForgotContent() ?>
			</div>
		</div>
	<?php }
}