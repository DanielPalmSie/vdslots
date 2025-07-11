<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class PaymentSwishBoxBase extends DiamondBox
{
    private $transactionId;
    /**
     * @var string
     */
    private string $url;
    /**
     * @var string
     */
    private string $qrcode;

    /**
     * @var string
     */
    private string $successUrl;

    /**
     * @var string
     */
    private string $failedUrl;

    public function init()
    {
        $transaction_id = $_GET['transaction_id'] ?? '';

        if (empty($transaction_id)) {
            die('transaction_id is empty');
        }

        $this->transactionId = $transaction_id;

        phive('PayNPlay')->logger->debug('PayNPlay SwishBox', [
            'transaction_id' => $transaction_id
        ]);

        $swishData = phive('PayNPlay')->getSwishIframeData($this->transactionId);

        if (! $swishData['url'] || ! $swishData['qrcode']) {
            die('wrong transaction id');
        }

        $this->url = $swishData['url'];
        $this->qrcode = $swishData['qrcode'];

        //redirect based on WS result
        $this->successUrl = $swishData['successUrl'];
        $this->failedUrl = $swishData['failUrl'];

        //to display limit popup we need to redirect to success
        if ($swishData['amount'] > $swishData['limit'] && isset($swishData['limit'])) {
            header('location: '. $this->successUrl . '?strategy=strategy_swish&step=2&transaction_id=' . $this->transactionId);
            exit;
        }

        if($swishData['status'] == 'REJECT'){
            header('location: '. $this->failedUrl . '?strategy=strategy_swish&step=2&transaction_id=' . $this->transactionId);
            exit;
        }
    }

    public function printHTML()
    {
        jsTag("var cashierWs = '" . phive('UserHandler')->wsUrl('cashier', true, [], '', 'swish' . $this->transactionId) . "';");
        loadCss("/diamondbet/css/" . brandedCss() . "paynplayswishbox.css");
        ?>

        <script language="JavaScript">
            let intervalId = null;
            function onCloseCallback() {
                if (intervalId) {
                    clearInterval(intervalId);
                }

                const f = () => {
                    mgAjax({
                        action: 'check-pnp-transaction-status',
                        ext_id: '<?php echo $this->transactionId ?>'
                    }, function (result) {
                        var result = JSON.parse(result);
                        if (result.status === 'success') {
                            window.location.href = "<?php echo $this->successUrl . '?strategy=strategy_swish&step=2&transaction_id=' . $this->transactionId; ?>";
                            clearInterval(intervalId);
                        }
                    });
                };

                intervalId = setInterval(f, 5000);
            }

            doWs(cashierWs, function (e) {
                var wsRes = JSON.parse(e.data);
                console.log('websocket notification:' + wsRes);

                if (wsRes.msg_raw == 'deposit.complete') {
                    window.location.href = "<?php echo $this->successUrl . '?strategy=strategy_swish&step=2&transaction_id=' . $this->transactionId; ?>";
                } else {
                    window.location.href = "<?php echo $this->failedUrl . '?strategy=strategy_swish&step=2&transaction_id=' . $this->transactionId; ?>";
                }
            }, onCloseCallback);
        </script>

        <div class="swish-qr-container">
            <img src="/diamondbet/images/<?= brandedCss() ?>/pay-n-play/swish-logo-big.png" alt="Swish Logo" class="swish-logo" />
            <br />

            <?php if (phive()->isMobile()) { ?>
                <script language="JavaScript">
                    const swishUrl = "<?php echo $this->url; ?>";

                    function openApp() {
                        const postObj = {
                            type: 'paynplay',
                            action: 'swish-redirect',
                            result: {
                                'type': 'swish-redirect',
                                'url': swishUrl
                            }
                        };

                        window.parent.postMessage(postObj, window.location.origin);
                    }

                    var appOpened = false;

                    openApp();
                    appOpened = true;

                    $(window).on('focus', function () {
                        if (!appOpened) {
                            openApp();
                            appOpened = true;
                        }
                    });

                    $(document).ready(function (){
                        $(".swish-logo").click(function(){
                            openApp();
                        });
                    })

                </script>

                <span class="scan-qr-text"><?php et('paynplay.open.app'); ?></span>
                <br />

            <?php } else { ?>
                    <span class="scan-qr-text"><?php et('paynplay.scan.qr'); ?></span>
                    <br />
                    <img alt="Swish QR Code" class="swish_deposit_qrcode" src="<?= $this->qrcode ?>">
                <?php
            }
            ?>
            <br />
            <button class="btn btn-l deposit_popup-btn deposit_popup-btn--cancel w-300" onclick="window.parent.closePopup('paynplay-box', true, false);"><?php et('Cancel'); ?></button>
        </div>
        <?php
    }
}
