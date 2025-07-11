<?php
require_once __DIR__ . '/../../phive.php';

if(!empty($_GET['lang'])){
    phive('Localizer')->setLanguage($_GET['lang']);
    phive('Localizer')->setNonSubLang($_GET['lang']);
}

/** @var DBUserHandler $uh */
$uh = phive('UserHandler');
$err = [];

if(!empty($_POST['dob'])) {
    // validate DOB
    if(!checkdate($_POST['birthmonth'], $_POST['birthdate'], $_POST['birthyear'])) {
        $err['birthdate'] = 'invalid';
    } else {

        // update user object
        $dob = "{$_POST['birthyear']}-{$_POST['birthmonth']}-{$_POST['birthdate']}";
        $user = cu();
        $user->setAttribute('dob', $dob);

        $result = ['status' => 'ok'];
    }

} elseif (!empty($_POST['zipcode'])) {
    $user = cu();
    if (empty($user)) {
        $err['zipcode'] = 'nouser';
    } elseif ($uh->validateZipcode($err, $user->getCountry(), $_POST['zipcode'])) {
        $user->setAttribute('zipcode', phive()->rmWhiteSpace($_POST['zipcode']));
        unset($_SESSION['zipcode_pending']);
        if (lic('hasExternalSelfExclusion', [], $user) !== false) {
            phive('UserHandler')->logout('locked');
        }
        $result = ['status' => 'ok'];
    }
} elseif (!empty($_POST['nid'])) {
    $result = ['status' => 'ok'];

    if (empty($user = cu())) {
        $err['nid'] = 'nouser';
    } else {
        $nid = phive()->rmNonNums($_POST['nid']);
        $country = $user->getCountry();

        if (!lic('validateNid', [$nid]) || $uh->doubleNid($nid, $country)) {
            $err['personal_number'] = 'invalid.personal.number';
        }

        if (lic('getDataFromNationalId')) {
            if (lic('getDataFromNationalId', [$country, $nid, $user]) === false) {
                $err['personal_number'] = 'invalid.personal.number';
            }
        }
    }
}

$GLOBALS['result'] = empty($err) ? $result : array("status" => 'err', 'info' => $uh->errorZone($err, true));

echo json_encode($GLOBALS['result']);

