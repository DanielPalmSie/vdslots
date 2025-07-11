<?php
    if (!phive('DBUserHandler/Booster')->getSetting('render_unpaid_report')) {
        echo "<p style='padding:10px'>This report is disabled</p>";
        return;
    }
?>

<h1 style="padding-top:20px;padding-bottom:20px;text-align:center">Users that did not receive a payout last Friday</h1>

<?php $pending = phive('DBUserHandler/Booster')->getUsersThatMissedAutoPayout(); ?>

<table class="table" cellpadding="0" cellspacing="0" width="100%">
    <thead>
    <tr>
        <th style="padding:10px">User ID</th>
        <th style="padding:10px">Pending Balance</th>
    </tr>
    </thead>
    <tbody>
    <?php
        if (count($pending)) {
            foreach ($pending as $uid => $balance) {
                echo <<<HTML
                    <tr>
                        <td style='text-align:center'>{$uid}</td>
                        <td style='text-align:center'>{$balance}</td>
                    </tr>
                HTML;
            }
        } else {
            echo <<<HTML
                <tr>
                    <td colspan="2" style="text-align:center">Everyone was paid</td>
                </tr>
            HTML;
        }
    ?>
    </tbody>
</table>
<p>&nbsp;</p>
