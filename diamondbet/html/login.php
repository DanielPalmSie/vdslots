<?php
require_once __DIR__ . '/../../phive/phive.php';
/** @var UserHandler $uh */
$uh = phive()->getModule('UserHandler');

if(isset($_GET['logout'])){
    $uh->logout();
    // This doesn't look that good
    echo "<meta http-equiv='refresh' content='0;url=/'>";
    exit();
} else {
    // Do we have an admin login?
    if(isset($_POST['login_name'])){
        $cur_uname = empty($_POST['login_name']) ? $_POST['login_username'] : $_POST['login_name'];
        $u         = $uh->getUserByUsername($cur_uname);
        // Do we have a user with basic permisssions?
        if(is_object($u) && p('admin_top', $u)){
            $cur_pwd = empty($_POST['password']) ? $_POST['login_password'] : $_POST['password'];
            if(!$uh->initUser($cur_uname, $cur_pwd)){
                $_SESSION['login_failed'] = $uh->getLoginError();
                $_SESSION[$cur_uname]['login_tries']++;
                $uh->logFailedLogin('', $cur_uname, 'MT', '', 1, $reason='failed_login_admin_wrong_password');
                $uh->logAction($cur_uname, "Admin login: user with permission but wrong password", 'failed_login_admin_wrong_password');
                $rem_ip = remIp();
                $from   = phive('MailHandler2')->getLocalEmail('notifications');
                phive('MailHandler2')->saveRawMail('Failed login attempt', "Failed login attempt from $rem_ip", $from, $u->getAttr('email'), $from, 0);
                $uh->logIp('', $u->getId(), 'login', "Failed login attempt.");
                if($_SESSION[$_POST['login_name']]['login_tries'] > 2)
                    phive('UserHandler')->addBlock($cur_uname, 5);
            }else{
                $_SESSION['login_failed'] = false;
                if((int)$u->getAttribute('active') === 0){
                    $uh->logout('blocked');
                    die('Blocked');
                }

                if(!phive()->isLocal() && phive()->subtractTimes(phive()->today(), $u->getSetting('last_pwd_update'), 'd') > 42 && empty($_POST['new_password'])){
                    $uh->logout('forced pwd change');
                    phive('Redirect')->to('/changepwd');
                }
            }
        }
        // User without permission trying to log in as admin
        else {
            $country = $u ? cuCountry($u->getId()) : null;
            $uh->logFailedLogin('', $cur_uname, $country, '', 1, $reason='failed_login_admin_no_permission');
            $uh->logAction($cur_uname, "Admin login: user without permission", 'failed_login_admin_no_permission');
        }
    }else
        $uh->initUser(); // This is needed to setup currentUser in UserHandler etc.
}


/**
 * Set country (language) by subdomain
 */
$host = explode('.', $_SERVER['HTTP_HOST']);

$loc = phive('Localizer');
$cur = phive('Currencer');

if(phive('Pager')->getSetting('loc_type') == 'dir'){
  $loc->setCurNonSubCountry();
  $cur->setSessionCurrencyValue();
}else if (count($host) == 3){
  if (!$loc->setCountryBySubdomain($host[0])){
    $http = 'http' . phive()->getSetting('http_type');
    header("Location: $http://" . $host[1] . "." . $host[2] . "/");
    exit;
  }
}else
  $loc->setDefaultCountry();

//sv_SE.ISO8859-1
//print_r($loc);
//echo $loc->getCountryValue('setlocale');
//exit;

/**
 * Translator stuff
 */
if(isset($_GET['language'])){
  $lang = $_SESSION['language'];
  if ($_GET['language'] == "")
    $_SESSION['language'] = "";
  else{
    // First check if the language exists, that way people can't create new
    //  permission tags by tampering with the get variables.
    if (phive('Localizer')->languageExists($_GET['language']) &&
	p('translate.' . $_GET['language'])){
      $_SESSION['language'] = $_GET['language'];
    }else
      $_SESSION['language'] = 'denied';
  }
}
