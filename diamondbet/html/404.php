<?php

function boxDenied()
{
?>

<div class="box expandablebox">
	<div class="top"><div class="header"><?php echo t("403-denied.header"); ?></div></div>
	<div class="main" style="min-height: 204px">
		<div class="content">
		    <?php if (!empty(cu())):?>
		<?php echo t("403-denied.content.logged-in.html"); ?>
		<?php else: ?>
		<?php echo t("403-denied.content.logged-out.html"); ?>
		<?php endif; ?>
		</div>
	</div>
	<div class="bottom"></div>
</div>
<?
}
?>
