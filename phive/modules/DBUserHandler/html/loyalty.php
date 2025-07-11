<?php
require_once __DIR__ . '/../../../admin.php';

//Not in use / deprecated
exit;

$uh = phive('UserHandler');

$winners = array();

switch($_GET['action']){
	case 'start_lottery':
		$winners = $uh->runLottery($_GET['num_prizes']);
		break;
	case 'reset_loyalty':
		$uh->eraseAllLoyalty();
		break;
	default:
		break;
}

?>

<br>
<div style="margin-left:20px;">
<div>
	Total loyalty points on site: <?php echo $uh->getTotalLoyalty() ?>
</div>
<br>
<div>
	<a href="?action=reset_loyalty">Delete all loyalty points.</a>
</div>

<br><br>


<div>
	Run lottery: 
	<form method="get">
		<?php dbInput('action', 'start_lottery', 'hidden') ?>
		Number of prizes: <?php dbInput('num_prizes', 1) ?>
		<?php dbInput('submit_lottery', 'Submit', 'submit') ?>
	</form>
</div>

<br><br>

<?php if(!empty($winners)): ?>
<strong>Winners:</strong>
<div>
	<?php foreach($winners as $w): ?>
		<a href="/account/<?php echo $w['username'] ?>/" target="_blank" rel="noopener noreferrer"><?php echo $w['username'] ?></a><br>
	<?php endforeach ?>
</div>
<?php endif ?>

<br><br>
</div>
