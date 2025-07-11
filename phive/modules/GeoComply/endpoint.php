<?php
require_once "../../phive.php";
require_once "./GeoComplyLogger.php";

$geoComply = phive('GeoComply');
$geoComplyLogger = new \GeoComply\GeoComplyLogger(phive('DBUserHandler'));
use \GeoComply\Exceptions\InvalidArgumentException as InvalidArgumentException;

$geoComplyLicense = licSetting('geocomply');
$geoComplyCredentials = $geoComply->getSetting('auth');
$geoComplyData = array_merge($geoComplyLicense, $geoComplyCredentials);

$geoComply->init($geoComplyData);

$userID = $geoComply->getUserId();
$action = $_REQUEST['action'];
$packet = $_POST['packet'];
$logTag = $_POST['tag'];
$reason = $_REQUEST['reason'] ?? 'Login';

$ts = gmdate("D, d M Y H:i:s") . " GMT";
header("Expires: $ts");
header("Last-Modified: $ts");
header("Pragma: no-cache");
header("Cache-Control: private, no-cache, no-store, must-revalidate");

switch ($action) {
    case 'init':
        $license = $geoComply->getLicenseKey();
        init($license['key'], $geoComplyData, $userID, $reason);
        break;
    case 'poll':
        poll($geoComply);
    case 'decrypt':
        decrypt($packet, $geoComply);
        break;
    case 'login':
        $data = [];
        $data['response'] = 'ok';
        $data['username'] = $_SESSION['gcusername'];
        $data['password'] = $_SESSION['gcpassword'];
        echo json_encode($data);
        break;
    case 'force-request':
        forceRequest($geoComply);
    case 'geolocationfailed':
        $failure_popup = moduleHtml('GeoComply', 'location_verification_failed_popup', true, null);
        $troubleshooter_popup = moduleHtml('GeoComply', 'troubleshooter_popup', true, null);

        echo json_encode(['failure_popup'=>$failure_popup, 'troubleshooter_popup'=>$troubleshooter_popup]);
        break;
    case 'log':
        logUserAction($geoComplyLogger, $userID, $logTag);
        break;

}

//initialisation of GeoComply for connect method
function init($key, $credentials, $userID, $reason)
{
    $FESettings = [];

    $FESettings['installerID'] = $credentials['installerID'];
    $FESettings['envId'] = $credentials['envId'];
    $FESettings['oobeeUrl'] = $credentials['oobeeUrl'];
    $FESettings['customFields'] = $credentials['customFields'];
    $FESettings['license'] = $key;
    $FESettings['userId'] = $userID;
    $FESettings['reason'] = $reason;
    $FESettings['debug'] = $credentials['debug'];
    $FESettings['polling_interval'] = $credentials['polling_interval'];
    $FESettings['app_url'] = $credentials['app_url'];

    echo json_encode($FESettings);
    exit;
}


/**
 * Determines do we need to request a new GeoComply packet
 * @param GeoComply $geoComply
 * @return void
 */
function poll(GeoComply $geoComply)
{
    $data = $geoComply->getUserGeoComplyData();
    if($data['session_id'] != session_id()){
        $data['valid'] = 0;
    }

    $data['to_expiration'] = (int)($data['valid'] ? $data['valid'] - microtime(true) : 0);

    echo json_encode($data);
    exit;
}

/**
 * Decryption function for a frontend calls
 *
 * @param $packet
 * @param GeoComply $geoComply
 * @return void
 */
function decrypt($packet, GeoComply $geoComply)
{
    $data = $geoComply->parsePacket($geoComply->decryptGeoPacket($packet));

    echo json_encode($data);
    exit;
}


function forceRequest(GeoComply $geoComply){
    $geoComply->deleteGeoComplyData();
    exit;
}


function logUserAction(\GeoComply\GeoComplyLogger $logger, $userID, $logTag){
    try {
        $response = $logger->log($userID, $logTag);
    } catch (InvalidArgumentException $e) {
        $response = $e->getMessage();
        phive('Logger')->getLogger('geocomply')->error($response);
    }

    if($response){
        echo json_encode($response);
    }

    exit;
}
