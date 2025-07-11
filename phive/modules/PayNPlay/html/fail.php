<?php

require_once __DIR__.'/../../../phive.php';

$transaction_id = $_GET['transaction_id'] ?? '';

if (empty($transaction_id)) {
    die('transaction_id is empty');
}

$redis_key = 'pnp'.$transaction_id;
$data = phMgetArr($redis_key);
phMdel($redis_key);

if (empty($data) || !is_array($data) || empty($data['orderid'])) {
    phive('PayNPlay')->logger->error('PayNPlay: Missing data for transaction_id: '.$transaction_id, $data);
}

phive('PayNPlay')->logger->debug('PayNPlay login: Redirect Fail', [
    'transaction_id' => $transaction_id,
    'data' => $data,
]);

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

die('error');
