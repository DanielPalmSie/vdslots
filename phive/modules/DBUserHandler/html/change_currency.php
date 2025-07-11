<?php

use DBUserHandler\CurrencyMoveStatus;

require_once __DIR__ . '/../../../admin.php';

if (isset($_REQUEST['uid'])) {
    $user = cu($_REQUEST['uid']);
    if (empty($user)) {
        die(json_encode(['status' => 'error']));
    }
    $status = (int)$user->getSetting('currency_move_status');
    if ($status === CurrencyMoveStatus::FINISHED) {
        $res = ['status' => 'finished'];
    } else if ($status === CurrencyMoveStatus::FAILED) {
        $res = [
            'status' => 'failed',
            'message' => $user->getSetting('currency_move_status_fail_reason'),
        ];
    } else {
        $res = ['status' => $status];
    }

    die(json_encode($res));
}

if (isset($_POST['user_id']) && isset($_POST['currency'])) {

    /** @var DBUserHandler $uh */
    $uh = phive('UserHandler');

    if (!p('users.change_currency')) {
        echo "<p>Error: You don't have permission to change currency</p>";
    } else {
        list($status, $message) = $uh->validateUserToCurrency(trim($_POST['user_id']), strtoupper($_POST['currency']), '_old');
        if ($status === false) {
            echo "<div id='currency-change-message' class='pad-stuff-ten'><p>{$message}</p></div>";
        } else {
            phive()->pexec('UserHandler', 'moveUserToCurrency', [trim($_POST['user_id']), strtoupper($_POST['currency']), '_old', false]);
            echo "<div id='currency-change-message' class='pad-stuff-ten'><p>Currency change for user {$_POST['user_id']} to {$_POST['currency']} in progress...</p></div>";
            ?>
            <script type=application/javascript>
                $(document).ready(function(){
                    $('#currency-change-form').hide();
                    checkStatus('<?php echo trim($_POST['user_id']) ?>');
                });
            </script>
            <?php
        }
    }

}

if (p('users.change_currency')):
    ?>
    <div id='currency-change-form' class="pad-stuff-ten">
        <p>Change customer's currency,
            <strong>won't work for countries with
                forced currency (Sweden, Denmark, Italy, Spain).</strong>
            <br/>
        </p>
        <?php if (!empty($result)): ?>
            <p class="error"><strong>
                    <?php echo $result ?>
                </strong></p>
        <?php endif ?>
        <form method="post">

            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
            <table>
                <tr>
                    <td>
                        <label for="user_id">User id:</label>
                    </td>
                    <td>
                        <input type="text" name="user_id"/>
                    </td>
                </tr>
                <tr>
                    <td>
                        New Currency:
                    </td>
                    <td>
                        <?php cisosSelect(true, '', 'currency', 'site_input', array(), false, false, false) ?>
                    </td>
                </tr>
            </table>
            <br/>
            <input type="submit" value="Submit">
        </form>
    </div>
<?php endif ?>

<script type=application/javascript>
    function checkStatus(user_id) {
        setInterval(function () {
            $.ajax({
                url: "/phive/modules/DBUserHandler/html/change_currency.php?uid=" + user_id,
                type: 'GET',
            })
            .done(function (data) {
                var obj = JSON.parse(data);
                let message = null;

                if (obj.message) {
                    message = obj.message;
                } else if (obj.status === 'finished') {
                    message = '<p>Currency change done successfully. <a href="/admin/change-currency/">Click here to do another customer.</a></p>';
                }

                if (message) {
                    $('#currency-change-message').html(message)
                }
                return false;
            });
        }, 10000);
    }
</script>
