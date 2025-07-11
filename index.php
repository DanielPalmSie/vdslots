<?php
require_once dirname(__FILE__) . '/phive/phive.php';

require_once dirname(__FILE__) . '/diamondbet/html/login.php';

phive('Redirect')->handleSignupReferal();


require_once dirname(__FILE__) . '/go.php';

//Move to phive/phive.php later
$country = phive('IpBlock')->getCountry(remIp());
$countries = phive('Config')->valAsArray('countries', 'ip-block');
// TODO make a popup when page is first loaded that "we do not accept players from your country"
if(in_array($country, $countries))
    die("Unfortunately we do not accept players from your country, please contact support@videoslots.com for more information and inquiries.");

if(empty($_GET['referral_id']))
    phive('Affiliater')->affe301();

phive('Localizer')->redirectToUsersNonSub($_GET['dir']);
/** @var Pager $pager */
$pager = phive()->getModule('Pager');
$dir = explode('/', $_GET['dir']);
//Get Page Id
$page_id = $pager->getPageFromDir($dir);
/*var_dump($page_id);*/
if (!$page_id) {
    // Не нашли страницу — отдать 404
    header("HTTP/1.1 404 Not Found");
    // Или: http_response_code(404);

    echo "Страница не найдена";
    // Или можете подключить шаблон 404.php
    // include __DIR__ . '/views/404.php';

    exit;
}

$smith = phive()->getModule('Permission');

if ($page_id)
{
    if ($pager->get('check_permission', $page_id) && $smith)
    {
        if (phive()->getModule('Permission')->hasPermission('pager.page.' . $page_id))
            $pager->executeByID($page_id);
        else
        {
            $page = $pager->getPageByAlias('.denied');
            if ($page)
                $pager->executeByID($page['page_id']);
            else
                exit("Permission denied");
        }
    }
    else
    {
        $pager->executeByID($page_id);
    }
}
