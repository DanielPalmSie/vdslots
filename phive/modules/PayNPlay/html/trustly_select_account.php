<?php
$result = $_GET['result'] ?? 'failure';
?>

<script type="text/javascript">
    window.parent.postMessage({
        type: 'paynplay',
        action: 'trustly-select-account',
        result: '<?= $result; ?>',
    }, window.location.origin);

    top.$.multibox('close', 'paynplay-box');
</script>
