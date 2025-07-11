<?php
require_once __DIR__.'/../../../phive.php';

$transaction_id = $_GET['transaction_id'] ?? '';

phive('PayNPlay')->logger->debug('PayNPlay success', [
    'transaction_id' => $transaction_id
]);

if (empty($transaction_id)) {
    die('transaction_id is empty');
}

$strategy = $_GET['strategy'];
$step = $_GET['step'];


//Common event for all Deposit Clients
phive('PayNPlay')->onDepositSuccess($transaction_id, $strategy, $step);

//Retrieve transaction from a Redis
$data = phive('PayNPlay')->getTransactionDataFromRedis($transaction_id);

phive('PayNPlay')->updateBonusCode($data, $transaction_id);

phive('PayNPlay')->logger->debug('PayNPlay login: Redirect Success', [
    'transaction_id' => $transaction_id,
    'data' => $data,
]);

?>

    <script type="text/javascript">
        // send postmessage to parent window with success or failure
        const postObj = {
            type: 'paynplay',
            action: 'deposit',
            result: {
                'status': '<?php echo $data['status']; ?>',
                'message': '<?php echo $data['message']; ?>',
                'transaction_id': '<?php echo $transaction_id; ?>',
                'limit': '<?php echo $data['limit']; ?>',
            }
        };

        window.parent.postMessage(postObj, window.location.origin);
    </script>

<?php
