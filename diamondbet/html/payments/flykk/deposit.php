<?php
    loadJs("/phive/js/jquery.min.js");
    loadJs("/phive/js/utility.js");
    loadJs("/phive/js/multibox.js");
    loadJs("/phive/js/mg_casino.js");
    loadJs("/phive/js/cashier.js");

    $channel = phive()->isMobile() ? 'mobile' : 'desktop';
    phive('Casino')->setJsVars($channel == 'mobile' ? 'mobile' : 'normal');

    $no_iframe = $_GET['no_iframe'];
    $transaction_id = $_GET['tr_id'] ?? '';
    $embedded_script_source = $_GET['embedded_script_source'] ?? '';
    $return_urls = json_encode(unserialize(base64_decode($_GET['return_urls'] ?? [])));
?>

<html>
    <head>
        <link type="text/css" rel="stylesheet" charset="UTF-8" href="index.css"/>
        <script src="<?= $embedded_script_source; ?>"></script>
        <style>
            .container-payment {
                position: absolute;
                width: 100vw;
                height: 100%;
                top: 0;
                right: 0;
                bottom: 0;
                box-sizing: border-box;
            }
        </style>
    </head>

    <body>
        <div id="wrapper-container">
            <div class="container-holder">
                <div class="container-payment" id="isignthis-container"></div>
            </div>
        </div>
    </body>

    <script>
        const noIframe = <?php echo $no_iframe; ?>;
        const returnUrls = <?php echo $return_urls; ?>;
        const options = {
            transaction_id: "<?php echo $transaction_id; ?>",
            container_id: "isignthis-container",
            minimum_height: window.screen.height,
            useEmbeddedCss: true
        };

        isignthis
            .setup(options)
            .done(function (e) {})
            .continueLater(function (e) {})
            .route(function (e) {})
            .resized(function (e) {})
            .fail(function (e) {
                saveFELogs('payments', 'error', 'Flykk: General failure to initialise the Embedded UI', { 'callback_response': JSON.stringify(e) });
            })
            .completed(function (e) {
                if (e.state === 'SUCCESS') {
                    mobileSpecificIframeRedirect(returnUrls.success_url, noIframe);
                } else {
                    mobileSpecificIframeRedirect(returnUrls.fail_url, noIframe);
                    saveFELogs('payments', 'error', 'Flykk: Deposit Not Completed', { 'callback_response': JSON.stringify(e) });
                }
            })
            .publish();
    </script>
</html>
