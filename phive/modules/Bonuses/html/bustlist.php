<?php
die('deprecated');

require_once __DIR__ . '/../../../admin.php';

$sdate = empty($_POST['sdate']) ? date('Y-m-01', strtotime('-1 month')) : $_POST['sdate'];
$edate = empty($_POST['edate']) ? date('Y-m-t', strtotime('-1 month')) : $_POST['edate']; 

if(!empty($_POST['submit_dates'])){
    $busts = phive('Bonuses')->getBustList($sdate, $edate);
    $mails = phive('MailHandler2')->getMailsSelect();
}

if (!empty($_POST['submit_busts'])) {
    if (!empty($_POST['bonus_id']) && !empty($_POST['mail_trigger'])) {
        /** @var User[] $users */
        $users = [];
        foreach ($_POST['total_bust'] as $user_id => $bust_amount) {
            if (!empty($bust_amount)) {
                $users[] = cu($user_id);
                phive('Bonuses')->addBustBonus($user_id, $_POST['bonus_id'], $bust_amount);
            }
        }
        if (phive()->moduleExists("MailHandler2")) {
            foreach (phive('MailHandler2')->filterMarketingBlockedUsers($users) as $user) {
                phive("MailHandler2")->sendMail($_POST['mail_trigger'], $user);
            }
        }
        echo "Bust bonuses were activated.";
    } else
        echo "Mail alias/trigger and/or bonus selection missing.";
}

?>

<form action="" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <?php if(!empty($busts)): ?>
	<table class="stats_table">
	    <tr class="stats_header">
		<td>Username</td>
		<td>Bust Total</td>
		<td>Bust Count</td>
	    </tr>
	    <?php foreach($busts as $b): ?>
		<tr class="fill-odd">
		    <td>
			<a target="_blank" href="/account/<?php echo $b['username'] ?>" rel="noopener noreferrer">
			    <?php echo $b['username'] ?>
			</a>
		    </td>
		    <td><?php dbInput("total_bust[{$b['user_id']}]", $b['total_bust']) ?></td>
		    <td><?php echo $b['bust_count'] ?></td>
		</tr>
	    <?php endforeach ?>
	</table>
	<p>
	    <label for="mail_trigger">Choose mail:</label>
	    <?php dbSelect('mail_trigger', $mails, '', array('', 'Select')) ?>
	</p>	
	<p>
	    <label for="bonus_id">Choose bonus:</label>
	    <?php dbSelectWith("bonus_id", phive('Bonuses')->getNonDeposits(date('Y-m-d')), 'bonus_id', 'bonus_name', '', array('', 'Select')) ?>
	</p>	
	<p><input type="submit" name="submit_busts" value="Submit"></p>
    <?php endif ?>
    <table>
	<tr>
	    <td>Start date</td>
	    <td><?php dbInput('sdate', $sdate) ?></td>
	</tr>
	<tr>
	    <td>End date</td>
	    <td><?php dbInput('edate', $edate) ?></td>
	</tr>
	<tr>
	    <td><input type="submit" name="submit_dates" value="Submit"></td>
	    <td></td>
	</tr>
    </table>
</form>
