<?php

require_once "IdScan.php";
include_once "../../phive.php";

$uid = $_GET['uid'];
$action = $_GET['action'];
$context = $_GET['context'];

$idscan = new IdScan();
$idscan->init();
$isMobile = phive()->isMobile();

if ($action == 'step2data' && isset($uid)) {
    //get saved Step2 Data
    $userData = $idscan->getSavedUserData($uid);

    echo json_encode($userData['step2data']);
    exit;
}

$journeyToken = $idscan->generateToken();
$brandedCss = phive()->getSetting("branded_css") ?? '';


if ($uid && $journeyToken['access_token']) {
    $userData = $idscan->getSavedUserData($uid);

    //if step2data is available and result of IDScan is not 'failed' for user
    if (count($userData) && $userData['status'] != 'failed') {
        $hashedUid = $userData['hashed_uid'];

        $uidData = json_encode([['name' => 'uid', 'value' => $hashedUid], ['name' => 'brand', 'value' => phive('Distributed')->getSetting('local_brand')]]);

        ?>
        <link rel="stylesheet" type="text/css" href="/diamondbet/css/<?= $brandedCss ?>idscan.css">
        <link rel="stylesheet" type="text/css" href="css/styles.css">
        <script src="./js/customize.js"></script>
        <script src="./js/idscan-jcs.34238f34fc499ba812a7.js"></script>
        <div id="idscan" class="idscan">Auto Capture</div>
        <div id="root-idscan" class="container-idscan"
             style="display:flex; flex-direction: column;"></div>
        <script>
            function isIos(){
                return navigator.userAgent.match(/iPhone|iPad|iPod/i);
            }

            if (isIos()) {
                const rootId = document.getElementById('root-idscan');
                rootId.classList.add('idscan__iphone-mobile');
            }

            const translationDictionary = {
                PROVIDER_TITLE_GATEWAY: "<?php echo t('idscan.prodvider_title_gateway'); ?>",
                PROVIDER_TITLE_LIVENESS: "<?php echo t('idscan.prodvider_title_liveness'); ?>",
                CANCEL_JOURNEY: "<?php echo t('idscan.upload.button.Cancel'); ?>",
                RESULTS_CONTINUE: "<?php echo t('idscan.upload.button.Continue'); ?>",
                CLOSE: "<?php echo t('idscan.upload.button.close'); ?>",
                PROVIDER_TITLE_RESULTS: "<?php echo t('idscan.prodvider_title_results'); ?>",
                PROVIDER_TITLE_LOGIN: "<?php echo t('idscan.prodvider_title_login'); ?>",
                RESULTS_NEW_JOURNEY: "<?php echo t('idscan.result_new_journey'); ?>",
                RESULTS_FINISH: "<?php echo t('idscan.result_finish'); ?>",
                PROVIDER_TITLE_SMART_CAPTURE: "<?php echo t('idscan.provider_titile_smart_capture'); ?>",
                LOGIN_SUBMIT: "<?php echo t('idscan.login_submit'); ?>",
                UPLOAD_PHOTO: "<?php echo t('idscan.upload_photo'); ?>",
                AUTO_CAPTURE: "<?php echo t('idscan.auto_capture'); ?>",
                CAPTURE_PHOTO: "<?php echo t('idscan.capture_photo'); ?>",
                RESULT_PAGE_TITLE: "<?php echo t('idscan.identity.title'); ?>",
                VERIFICATION_SUCCESS: "<?php echo t('idscan.identity.verification_success'); ?>",
                VERIFICATION_SUCCESS_DESCRIPTION: "<?php echo t('idscan.identity.verification_success_description'); ?>",
                VERIFICATION_FAILED: "<?php echo t('idscan.identity.verification_failed'); ?>",
                VERIFICATION_FAILED_DESCRIPTION: "<?php echo t('idscan.identity.verification_failed_description'); ?>",
                INSTRUCTIONS: '<?php echo t("idscan.instructions"); ?>',
            };

            var isLoggedIn = '1';
            var resultStatus = 'Failed';
            var uid = '<?= $hashedUid ?>';
            var context = '<?= $context ?>';
            var isMobile = '<?= $isMobile ?>';

            function onContinue() {
                window.top.location.href = "/idscan/?action=proceed&uid=" + uid;
            }

            function onClose() {
                if (resultStatus === 'Failed') {
                    window.top.location.href = "/idscan/?action=proceed&uid=" + uid;
                } else {
                    window.top.location.href = "/?signout=true";
                }
            }

            // Prevent safari loading from cache when back button is clicked
            window.onpageshow = function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            };

            var idscanId = document.getElementById('idscan');

            let journeryCancelClicked = false
            var container = new window.GBG.Idscan.JourneyContainer({
                backendUrl: "<?= $idscan->getSdkURL() ?>",
                container: "#root-idscan",
                token: "<?= $journeyToken['access_token'] ?>",
                smartCapture: {
                    workerScriptUrl: "./ides-micro.6debf39446d93ab3321c.js",
                    asmScriptUrl: "./idesmicro_asm.js"
                },
                tripleScanGuidancePage: true,

                onJourneyEvent: function (event, meta, state) {
                    if (state.action === 'IDENTITY:FRONT' && state.inputProvider === 'FILESYSTEM' && event === 'DISPLAY:STEP') {
                        idscanId.innerHTML = translationDictionary.UPLOAD_PHOTO;
                    } else if (state.action === 'IDENTITY:FRONT' && state.inputProvider === 'SMART_CAPTURE' && event === 'DISPLAY:STEP') {
                        idscanId.innerHTML = translationDictionary.AUTO_CAPTURE;
                    } else if (state.action === 'IDENTITY:FRONT' && state.inputProvider === 'SMART_CAPTURE' && event === 'CAMERA:CHANGE') {
                        idscanId.innerHTML = translationDictionary.CAPTURE_PHOTO;
                    }
                    if (event === 'JOURNEY:CANCEL') {
                        journeryCancelClicked = true;
                    }

                    if (journeryCancelClicked) {
                        idscanId.innerHTML = translationDictionary.RESULT_PAGE_TITLE;
                    }

                    console.log(event, meta, state);

                    if (event === 'JOURNEY:END') {
                        const result = state.journey.currentResult
                        resultStatus = result === undefined ? "Failed" : result;

                        if(result == 'Pass'){
                            window.top.location.href = "/idscan/?action=proceed&uid=" + uid;
                            return;
                        }
                    }

                },

                fileUploadOnCameraCaptureJourneys: true,
                previewPages: {
                    documentStep: {
                        smartCapture: true,
                        manualCapture: false
                    },
                    facematchStep: {
                        manualCapture: false
                    },
                    poaStep: {
                        manualCapture: false
                    },
                    passiveLivenessStep: {
                        manualCapture: false
                    },
                    covidStep: {
                        manualCapture: false,
                        fileUpload: true
                    }
                },
                manualCapture: {
                    enabled: true,
                    timeout: 15
                },
                assetsDirectory: 'assets',
                // token:tokenValue,
                // onJourneyEvent: onJourneyEventCallBack,
                templates: templateDictionary,
                dictionary: translationDictionary,
                additionalData: <?= $uidData ?>,
                isVerboseLogEnabled: true, //for general app timing logs
                hideLogo: true,
                hideAutoCaptureHints: false,
            });

            container.initialize();
        </script>

        <?php
    }
}
?>
