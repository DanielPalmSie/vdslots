<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class IdScanBoxBase extends DiamondBox
{
    private $uid;
    private $action;
    private $context;

    public function init()
    {
        $this->uid = $_GET['uid'];
        $this->action = $_GET['action'];
        $this->context = $_GET['context'];
    }

    public function printHTML()
    {
        //Displaying popups on registration
        if (llink($this->url) == '/registration-idscan' || llink($this->url) == '/mobile/register-idscan') {
            drawFancyJs();
            loadJs("/phive/js/jQuery-UI/".$this->getJQueryUIVersion()."jquery-ui.min.js");
            loadJs("/phive/modules/IdScan/js/qrcode.min.js");
            loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
            if (phive()->isMobile()) {
                loadCss("/diamondbet/css/" . brandedCss() . "new-registration-mobile.css");
            }


            $id_wrapper = 'registration-wrapper';
            $class_header_center = 'registration-header-center-idscan';
            $class_wrapper = 'registration-step2';
            ?>

            <div id="<?php echo $id_wrapper; ?>" class="<?php echo $class_wrapper; ?>">

                <div class="registration-header-idscan">
                    <div class="registration-header-left-idscan">
                        <!-- link to chat -->
                        <div id="chat-registration"
                             onclick="<?php echo 'window.parent.' . phive('Localizer')->getChatUrl() ?>"></div>

                    </div>
                    <div class="<?php echo $class_header_center; ?>">
                        <?php et("idscan.identity.title"); ?>
                    </div>
                    <div class="registration-header-right-idscan">
                        <!--icon to close this box -->
                        <div id="close-registration-box" onclick="parent.$.multibox('close', 'registration-box')">X
                        </div>
                    </div>
                </div>

                <div class="registration-container result_page__container">
                    <div class="lic-mbox-container result__page">
                        <div class="center-stuff" id="idscan_popup_description">
                            <p>
                                <?php et('idscan.identity.description') ?>
                            </p>
                            <p class="read_qrcode">
                                <?php et('idscan.identity.qrscan.description') ?>
                            </p>
                        </div>

                        <div class="result__container" id="result__container_failure">
                            <img src="/phive/modules/IdScan/assets/images/result-failed.svg">
                            <div class="result__content">
                                <div class="result__content-text result__content-text-bold"> <?php et('idscan.identity.verification_failed') ?></div>
                                <div class="result__content-text"> <?php et('idscan.identity.verification_failed_description') ?></div>
                            </div>
                        </div>

                        <div id="qrcode"></div>
                    </div>
                    <div class="center-stuff qr-code__action">
                        <button class="btn btn-l btn-default-l idscan-btn"
                                onclick="redirectToIdscan()"><?php et('idscan.upload.button.document') ?></button>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                var hashed_uid = '<?= $this->uid ?>';

                const params = new URLSearchParams(window.location.search);
                const isFailure = !!params.get('failure');
                document.getElementById('qrcode').classList.remove('hidden');
                document.getElementsByClassName('read_qrcode')[0].classList.remove('hidden');
                document.getElementById('close-registration-box').classList.remove('visibility-hidden-force');


                if (isFailure) {
                    document.getElementById('idscan_popup_description').classList.add('hidden');
                    document.getElementById('result__container_failure').classList.add('show')
                    document.getElementById('qrcode').classList.add('hidden');
                    document.getElementsByClassName('idscan-btn')[0].innerHTML = "<?php echo et('idscan.upload.button.close'); ?>";
                }else {
                    document.getElementById('close-registration-box').classList.add('visibility-hidden-force');
                }

                if (!isMobile()) {
                    new QRCode(document.getElementById("qrcode"), '<?= phive()->getSiteUrl() ?>/mobile/idscan/?uid=' + hashed_uid);

                    doWs('<?php echo phive('UserHandler')->wsUrl('idscanproceed' . $this->uid,
                        false) ?>', function (e) {
                        closeVsWS()
                        top.window.location = '<?= phive()->getSiteUrl() ?>/idscan/?uid=<?= $this->uid ?>&action=proceed';
                    });
                }


                // If mobile & not failed changes button text to continue
                if (isMobile() && !isFailure) {
                    document.getElementsByClassName('idscan-btn')[0].innerHTML = "<?php echo et('idscan.upload.button.Continue'); ?>";
                }

                if (isMobile()) {
                    document.getElementsByClassName('read_qrcode')[0].classList.add('hidden');
                }

                function redirectToIdscan() {
                    if (isFailure) {
                        // redirect to main
                        top.$.multibox('close', 'registration-box');
                        return;
                    }
                    if (isMobile()) {
                        window.location.href = '/mobile/idscan/?uid=' + hashed_uid;
                    } else {
                        window.top.location = '/idscan/?uid=' + hashed_uid;
                    }
                }

            </script>

            <?php
        } elseif ($this->action == "proceed") {
            //Registering user with previously submitted Step2 Data
            loadJs("/phive/modules/DBUserHandler/js/registration.js");
            $data = phive('IdScan')->getSavedUserData($this->uid);
            $step1 = $data['step1data'];
            $step2 = $data['step2data'];

            $contact = $data['contact'];
            $request = $data['request']; //requesting IDScan verification on demand
            $status = $data['status'];
            $uid = $data['uid'];

            $user = cu();

            if($request['type'] == 'manual' && $status == 'success'){
                phive('UserHandler')->logAction($uid, 'Successful IDScan verification', 'IDScan');
                phive('IdScan')->resetRestrictions($this->uid);
                phive('UserHandler')->logAction($uid, 'Resetting KYC blocks', 'IDScan');
                phive('IdScan')->removeTemporaryData('status', $user);
                phive('IdScan')->removeTemporaryData('request', $user);

                jsGoTo('/');

            } else if(count($contact) && $status == 'success') {
                phive('UserHandler')->logAction($uid, 'Successful IDScan verification', 'IDScan');
                phive('IdScan')->resetRestrictions($this->uid);
                phive('UserHandler')->logAction($uid, 'Resetting KYC blocks', 'IDScan');

                $_SESSION['toupdate'] = $contact;

                phive('IdScan')->removeTemporaryData('contact', $user);
                phive('IdScan')->removeTemporaryData('status', $user);

                $urlPrefix = phive()->isMobile() ? '/mobile' : '';
                jsGoTo($urlPrefix . '/account/'.$uid.'/update-account/');

            } else if (count($step1) && $status == 'success') {
                phive('UserHandler')->logAction($uid, 'Finishing registration process on successful IDScan', 'IDScan');
                phive('IdScan')->resetRestrictions($this->uid);
                phive('UserHandler')->logAction($uid, 'Resetting KYC blocks', 'IDScan');

                $_SESSION['rstep1'] = $step1;
                $_SESSION['show_successful_idscan_verification'] = 1;

                ?>


                <script>
                    showLoader(function () {
                        $.getJSON("/phive/modules/IdScan/index.php?uid=<?= $this->uid ?>&action=step2data", function (data) {
                            Registration.submitStep2(data);
                        });
                    }, true);
                </script>
                <?php
            } else {
                phive('UserHandler')->logAction($uid, 'Trying to proceed with registration without IDScan verification', 'IDScan');
            }
        } else {
            //Displaying IDScan module
            ?>
            <script>
                onClose = null;
                $.multibox({
                    url: '/phive/modules/IdScan/index.php?uid=<?= $this->uid ?>&action=<?= $this->action ?>&context=<?= $this->context ?>',
                    id: "id-scan",
                    type: 'iframe',
                    width: isMobile() ? '100%' : '770px',
                    height: isMobile() ? '100%' : '90%',
                    globalStyle: {overflow: 'hidden'},
                    cls: 'id-scan-cls mbox-deposit',
                    overlayOpacity: 0.7,
                    useIframeScrollingAttr: false,
                    enableScrollbar: true
                });
            </script>
            <?php
        }
    }
}
