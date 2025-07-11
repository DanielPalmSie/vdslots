<?php
require_once __DIR__ . '/../../../phive.php';
$uh = phive('UserHandler');

if (isset($_POST['login_name']))
{
    if (!$_POST['login_name'] || !$_POST['password'])
    {
	$uh->adminLogin();
	$_SESSION['login_failed'] = 'LOGIN_ERROR_FAILED';
    }
    else if (!$uh->adminLogin($_POST['login_name'],$_POST['password']))
    {
	$_SESSION['login_failed'] = $uh->getLoginError();
        $short_pwd = substr($_POST['password'], 0, 3)."...";
        phive()->dumpTbl('admin-login-failure', "Trying with {$_POST['login_name']} / $short_pwd, IP: ".remIp());
    }
}
else if(isset($_GET['logout']))
{
    $uh->logout();
}
else
{
    $uh->adminLogin();
    $_SESSION['login_failed'] = false;
}
