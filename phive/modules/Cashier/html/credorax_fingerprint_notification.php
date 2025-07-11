<html>
<head> </head>
<body>
<?php
$isFingerprintDone = false;

if (isset($_POST['threeDSMethodData'])) {
    $decodedString = base64_decode($_POST['threeDSMethodData']);

    if ($decodedString) {
        $parsedData = json_decode($decodedString, true);

        if (isset($parsedData['threeDSServerTransID'])) {
            $isFingerprintDone = true;
        }
    }
}
?>
<script>
    (function () {
        if (cashier.isMobileApp) {
            sendToFlutter({
                data: {
                    'isFingerprintDone': <?php echo $isFingerprintDone ?>,
                },
                trigger_id: 'deposit', //TODO: check with mobile dev
                debug_id: 'cashier.handleEnd' //TODO: check with mobile dev
            });

            return;
        }

        parent.document.credoraxFingerPrintDone = <?php echo json_encode($isFingerprintDone); ?>;
    })();
</script>
</body>
</html>
