<?php

require_once "../../phive.php";

require_once "IdScan.php";
require_once "IdScanParser.php";
require_once "IdScanDocumentRequest.php";

$idscan = new IdScan();
$idscan->init();
$idscanParser = new IdScanParser();

$logger = phive('Logger')->getLogger('id-scan');

$action = $_GET['action'];

if ($action == "OnJourneyFinished") {
    $journey = json_decode(file_get_contents('php://input'), true);
    $journeyId = $journey['JourneyId'];
    $logger->info('OnJourneyFinished reached on: ' . phive('Distributed')->getSetting('local_brand'));
    $logger->info("JourneyID: " . $journeyId);

    $tokenData = $idscan->generateToken('investigation');

    if (isset($tokenData['access_token'])) {
        $token = $tokenData['access_token'];

        $idscan->setToken($token);
        $journeyData = $idscan->getJourney($journeyId);

        if ($idscanParser->parse($journeyData)) {
            $brand = $idscanParser->getBrand();

            //IDScan verification is for another brand
            if ($brand != phive('Distributed')->getSetting('local_brand')) {
                $logger->info("Forwarding request to brand: " . $brand);

                $remoteBrandURLs = $idscan->getRemoteBrandWebhookURL();
                $remoteWebhookURL = $remoteBrandURLs[$brand];

                if ($remoteWebhookURL) {
                    $logger->info("Forward URL: " . $remoteWebhookURL);
                    forwardRequest($remoteWebhookURL);
                    exit;
                }
            }

            $hashed_uuid = $idscanParser->getHashedUid();

            if ($idscanParser->passed()) {
                $idscan->setVerificationStatus($hashed_uuid, 'success');
                //reload Desktop QR code page to proceed with a registration
                toWs([], 'idscanproceed'.$hashed_uuid, 'na');

                //saving document
                $journeyImageUrl = $idscanParser->getJourneyImageUrl('ID Document', 'WhiteImage');
                $userData = $idscan->getSavedUserData($hashed_uuid);

                if ($journeyImageUrl && $userData){
                    $userId = $userData['uid'];
                    $userCountry = $idscanParser->getCountryCode();
                    $expiryDate = $idscanParser->getExpiryDate();
                    $expiryDateStatus = $idscanParser->getExpiryDateStatus();

                    $request = new \IdScan\IdScanDocumentRequest();
                    $user = cu($userId);
                    $user->setSetting('idscan_response', json_encode($journeyData));
                    $request->setUid($userId);
                    $request->setCountryCode($userCountry);
                    $request->setJourneyID($journeyId);
                    $request->setJourneyImage($journeyImageUrl);
                    $request->setExpiryDate($expiryDate);
                    $request->setExpiryDateStatus($expiryDateStatus);

                    if (!$request->isValidExpiryDate()){
                        $user->setSetting('idscan_expiry_status', 1);
                        $user->addComment('Skipping automatic approval. Automated expire date processing on document failed', 1);
                    }
                    if($request->verify()){
                        phive()->fire(
                                'IdScan',
                                'IdscanSaveDocument',
                                [$token, $userId, $userCountry, $journeyId, $journeyImageUrl, $expiryDate, $expiryDateStatus],
                                0,
                                function () use ($idscan, $request) {
                                    $idscan->saveDocuments($request);
                                }
                        );
                    }



                } else {
                    $logger->error("Missing journey image URL. Journey ID ", $journeyId);
                }

            } else {
                $userData = $idscan->getSavedUserData($hashed_uuid);
                $user = cu($userData['uid']);

                phive('DBUserHandler')->logAction($userData['uid'], "ID Scan verification failed, skipping. Journey ID: ".$journeyId , 'IDScan');
                // Fake idscan verification status in order to allow user to login
                $idscan->setVerificationStatus($hashed_uuid, 'success');

                // Set additional settings for this user so we know his idscan failed for future checks
                $user->setSetting('id_scan_failed', 1);
                $user->setSetting('id_scan_failed_login_redirect', 1);
                if (!$user->isPlayBlocked()) {
                    $user->playBlock();
                }
                if (!$user->isDepositBlocked()) {
                    $user->depositBlock();
                }

                toWs([], 'idscanproceed' . $hashed_uuid, 'na');
            }
        }

    } else {
        $logger->critical("Missing token");
    }
}

function forwardRequest($remoteWebhookURL)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$remoteWebhookURL?action=OnJourneyFinished");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
    $data = curl_exec($ch);
    curl_close($ch);
}


echo "OK";
