<?php
require_once __DIR__ . '/../../../phive.php';

$pnp = phive('PayNPlay');

$transaction_id = $_GET['transaction_id'] ?? '';
$bankIdTransactionData = $pnp->getBankIDTransactionData($transaction_id);

if(!count($bankIdTransactionData)) {
    die('missing transaction data');
}

$bankIdRequestId = $bankIdTransactionData['bankIdRequestId'];

$strategy = $_GET['strategy'] ?? 'strategy_swish';
$step = $_GET['step'] ?? 1;

$pnp->logger->debug('BankID redirection to success URL', [
    'transaction_id' => $transaction_id,
    'bankid_transaction_id' => $bankIdRequestId,
    'strategy' => $strategy,
    'step' => $step,
]);

if (empty($transaction_id)) {
    die('transaction_id is empty');
}

//Common event for all Deposit Clients
$pnp->onDepositSuccess($transaction_id, $strategy, $step);

//edge case when BankID verification is successful, but we are not allowing user to login because of a missing deposit
$data = $pnp->getTransactionDataFromRedis($transaction_id);
if ($data['status'] != 'ACCEPT') {
    $error = $data['message'];
?>
    <script type="text/javascript">
        // send postmessage to parent window with success or failure
        window.parent.postMessage({
            type: 'paynplay',
            action: 'error',
            result: {
                error: '<?php echo $error; ?>',
            }
        }, window.location.origin);

        top.$.multibox('close', 'paynplay-box');
    </script>

<?php
} else {
?>

    <script type="text/javascript">
        // send postmessage to parent window with success or failure
        const postObj = {
            type: 'paynplay',
            action: 'login',
            result: {
                'status': 'ACCEPT',
                'message': 'Successful BankID login',
                'transaction_id': '<?php echo $transaction_id; ?>',
                'limit': '<?php echo $data['limit']; ?>',
            }
        };

        window.parent.postMessage(postObj, window.location.origin);
    </script>

<?php
}
