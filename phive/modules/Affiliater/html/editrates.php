<?php
require_once __DIR__ . '/../../../admin.php';
if(isset($_POST['update_default'])){
    saveDefaultRates();
}
if(isset($_POST['update_partner_rate'])){
    savePartnerRates();
}
if(isset($_GET['delete_rate'])){
    phive('Affiliater')->deleteRate($_GET['delete_rate']);
}
if(isset($_GET['user_id'])){
    $user = cu($_GET['user_id']);
    if($user)
	echo "<h2>Editing rates for ".$user->getUsername()."<br /><br />";
}
else{
    echo "<h2>Editing global rates</h2>";
}
getDefaultRates(($user)?$user->getId():null);
getPartnerRates(($user)?$user->getId():null);
function getDefaultRates($user_id = null){
    $default = phive('Affiliater')->getRates(null,$user_id);
?>
    <h2>Default rates</h2>
    <form method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
	<?php if (is_numeric($user_id)): ?>
	    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
	<?php endif ?>
	<table>
	    <tr>
		<th>
		    Start amount
		</th>
		<th>
		    Rate
		</th>
	    </tr>
	    <?php foreach ($default as $d): ?>
		<tr>
		    <td>
			<input type="hidden" name="rate_id[ ]" value="<?php echo $d['rate_id']; ?>" />
			<input type="text" name="start_amount[<?php echo $d['rate_id']; ?>]" value="<?php echo $d['start_amount']; ?>" />
		    </td>
		    <td>
			<input type="text" name="rate[<?php echo $d['rate_id']; ?>]" value="<?php echo $d['rate']; ?>"/>
		    </td>
		    <td>
			<input type="button" name="delete" value="Delete" onclick="window.location = '?delete_rate=<?php echo $d['rate_id']; ?><?php if(is_numeric($user_id)) echo "&user_id=$user_id"; ?>'"/>	
		    </td>
		</tr>
	    <?php endforeach ?>
	    <tr>
		<td>
		    <input type="text" name="new_start_amount" value=""/>
		</td>
		<td>
		    <input type="text" name="new_rate" value=""/>
		</td>
	    </tr>
	    <tr>
		<td colspan="2">
		    <input type="submit" name="update_default" value="Update"/>
		</td>
	    </tr>
	</table>
    </form>
<?php
}
function getPartnerRates($user_id = null){
	?>
	<br />
	<h2>Partner rates</h2>
	<?php
	$partners = phive('Raker')->getPartners();
	foreach ($partners as $p) {
		$rates = phive('Affiliater')->getRates($p['partner_id'],$user_id);
			?>
			<br />
			<form  method="post" accept-charset="utf-8">
				<?php if (is_numeric($user_id)): ?>
					<input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
				<?php endif ?>
				<input type="hidden" name="partner_id" value="<?php echo $p['partner_id']; ?>"/>
				<h3><?php echo $p['full_name']; ?></h3>
				<table>
					<tr>
						<th>
							Start amount
						</th>
						<th>
							Rate
						</th>
					</tr>
				<?php foreach ($rates as $r): ?>
					<tr>
						<td>
							<input type="hidden" name="rate_id[ ]" value="<?php echo $r['rate_id']; ?>" />
							<input type="text" name="start_amount[<?php echo $r['rate_id']; ?>]" value="<?php echo $r['start_amount']; ?>" />
						</td>
						<td>
							<input type="text" name="rate[<?php echo $r['rate_id']; ?>]" value="<?php echo $r['rate']; ?>"/>
						</td>
						<td>
							<input type="button" name="delete" value="Delete" onclick="window.location = '?delete_rate=<?php echo $r['rate_id']; ?><?php if(is_numeric($user_id)) echo "&user_id=$user_id"; ?>'"/>	
						</td>
					</tr>
				<?php endforeach ?>
					<tr>
						<td>
							<input type="text" name="new_start_amount" value=""/>
						</td>
						<td>
							<input type="text" name="new_rate" value=""/>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="submit" name="update_partner_rate" value="Update"/>
						</td>
					</tr>
				</table>
			</form>
			<?php

	}
}
function savePartnerRates(){
	foreach ($_POST["rate_id"] as $rate_id) {
		phive('Affiliater')->updateRate($rate_id,$_POST['rate'][$rate_id],$_POST['start_amount'][$rate_id]);
	}
	if($_POST['new_start_amount'] != "" and $_POST['new_rate'] != ""){
		phive('Affiliater')->createRate($_POST['partner_id'],isset($_POST['user_id'])?$_POST['user_id']:null,$_POST['new_rate'],$_POST['new_start_amount']);
	}
}

function saveDefaultRates(){
	foreach ($_POST["rate_id"] as $rate_id) {
		phive('Affiliater')->updateRate($rate_id,$_POST['rate'][$rate_id],$_POST['start_amount'][$rate_id]);
	}
	if($_POST['new_start_amount'] != "" and $_POST['new_rate'] != ""){
		phive('Affiliater')->createRate(null,isset($_POST['user_id'])?$_POST['user_id']:null,$_POST['new_rate'],$_POST['new_start_amount']);
	}
}
